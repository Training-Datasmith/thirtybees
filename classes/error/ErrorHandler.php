<?php

declare (strict_types=1);
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
namespace Thirtybees\Core\Error;

use File_Logger;
use Smarty_Custom;
use Thirtybees\Core\Error\Response\Error_Response_Interface;
use Throwable;
/**
 * Class ErrorHandlerCore
 */
class Error_Handler_Core
{
    public const MAX_ERROR_MESSAGES = 1000;
    public const LEVEL_EMERGENCY = 'emergency';
    public const LEVEL_ALERT = 'alert';
    public const LEVEL_CRITICAL = 'critical';
    public const LEVEL_ERROR = 'error';
    public const LEVEL_WARNING = 'warning';
    public const LEVEL_NOTICE = 'notice';
    public const LEVEL_INFO = 'info';
    public const LEVEL_DEBUG = 'debug';
    /**
     * @var array[] list of errors, warnings and notices encountered during request processing
     */
    protected $error_messages = [];
    /**
     * @var object[] psr compliant logger
     */
    protected $loggers = [];
    /**
     * @var callable custom handler of fatal error
     */
    protected $fatal_error_handler;
    /**
     * Error handler constructor
     *
     * Creates and initialize error handling logic
     */
    public function __construct(protected \Thirtybees\Core\Error\Response\Error_Response_Interface $error_response)
    {
        @ini_set('display_errors', 'off');
        @error_reporting(E_ALL);
        // Set uncaught exception handler
        set_exception_handler($this->uncaught_exception_handler(...));
        // Set error handler
        set_error_handler($this->error_handler(...));
        // register shutdown handler to catch fatal errors
        register_shutdown_function([$this, 'shutdown']);
    }
    public function replay(Bootstrap_Error_Handler $bootstrap_error_handler): void
    {
        foreach ($bootstrap_error_handler->get_collected_errors() as $error) {
            $this->error_handler($error['errno'], $error['errstr'], $error['errfile'], $error['errline']);
        }
    }
    public function set_error_response_handler(Error_Response_Interface $error_response): void
    {
        $this->error_response = $error_response;
    }
    /**
     * Returns list of collected php error messags
     *
     * @param bool $includeSuppressed if true, result will include even
     *             messages that were suppressed using @ operator
     * @param int $mask message types to return, defaults to E_ALL.
     *
     * @return array[] of collected error messages
     */
    public function get_error_messages($include_suppressed = false, $mask = E_ALL): array
    {
        if ($this->error_messages) {
            return array_filter($this->error_messages, function (array $error) use ($include_suppressed, $mask): bool {
                if (!$include_suppressed && $error['suppressed']) {
                    return false;
                }
                return (bool) ($error['errno'] & $mask);
            });
        }
        return [];
    }
    /**
     * Uncaught exception handler - any uncaught exception will be processed by
     * this method.
     *
     * @param Throwable $e uncaught exception
     */
    public function uncaught_exception_handler(Throwable $e): void
    {
        static::handle_fatal_error(Error_Utils::describe_exception($e));
    }
    public function handle_fatal_error(Error_Description $error_description): never
    {
        $this->log_fatal_error($error_description);
        $this->error_response->send_response($error_description);
        exit;
    }
    public function log_fatal_error(Error_Description $error_description): void
    {
        // log all exceptions to file
        $logger = new File_Logger();
        $logger->set_filename(_PS_ROOT_DIR_ . '/log/' . date('Ymd') . '_exception.log');
        $logger->log_error($error_description->get_extended_message());
        // log exception through custom logger, if set
        if ($this->loggers) {
            $extra = $error_description->get_extra_sections();
            $stacktrace = $error_description->get_trace_as_string();
            $previous = $error_description->get_cause();
            while ($previous) {
                $stacktrace .= "\nCaused by: ";
                $stacktrace .= $previous->get_error_name() . ': ' . $previous->get_message();
                $stacktrace .= ' at line ' . $previous->get_source_line();
                $stacktrace .= ' in file ' . Error_Utils::get_relative_file($previous->get_source_file());
                $previous = $previous->get_cause();
            }
            $extra[] = ['label' => 'Stacktrace', 'content' => $stacktrace];
            $error = ['errno' => 0, 'errstr' => $error_description->get_error_name() . ': ' . $error_description->get_message(), 'errfile' => Error_Utils::get_relative_file($error_description->get_source_file()), 'errline' => $error_description->get_source_line(), 'suppressed' => false, 'type' => 'Exception', 'level' => static::get_log_level(E_ERROR), 'extra' => $extra];
            $this->log_message($error);
        }
    }
    /**
     * Error handler. It only records any error, warning or notice to $errors
     * array and yields to default handler.
     *
     * @param int $errno level of the error raised
     * @param string $errstr error message
     * @param string $errfile filename that the error was raised in
     * @param int $errline line number the error was raised at
     */
    public function error_handler($errno, $errstr, $errfile, $errline): bool
    {
        $suppressed = error_reporting() === 0;
        $file = $errfile;
        $line = $errline;
        $real_file = null;
        $real_line = 0;
        $errno = (int) $errno;
        if (class_exists('SmartyCustom') && Smarty_Custom::is_compiled_template($file)) {
            $real_file = Error_Utils::get_relative_file($errfile);
            $real_line = $errline;
            $file = Smarty_Custom::get_current_template();
            $line = 0;
        }
        $file = Error_Utils::get_relative_file($file);
        $error = ['errno' => $errno, 'errstr' => $errstr, 'errfile' => $file, 'errline' => $line, 'suppressed' => $suppressed, 'type' => static::get_error_type($errno), 'level' => static::get_log_level($errno)];
        if ($real_file) {
            $error['realFile'] = $real_file;
            $error['realLine'] = $real_line;
        }
        if (count($this->error_messages) < static::MAX_ERROR_MESSAGES) {
            $this->error_messages[] = $error;
        }
        if (!$suppressed) {
            $this->log_message($error);
        }
        return $suppressed || static::display_error_enabled();
    }
    /**
     * Shutdown handler let us detect and react to fatal errors.
     */
    public function shutdown(): void
    {
        $error = error_get_last();
        if (is_array($error) && static::is_fatal_error($error['type'])) {
            $error_description = Error_Utils::describe_error($error);
            if ($this->fatal_error_handler && is_callable($this->fatal_error_handler)) {
                $this->log_fatal_error($error_description);
                call_user_func($this->fatal_error_handler, $error);
            } else {
                $this->handle_fatal_error($error_description);
            }
        }
    }
    /**
     * Adds external logger. If $replay parameter is true, then any already
     * collected error messages will be emitted.
     *
     * @param object $logger
     * @param bool $replay
     */
    public function add_logger($logger, $replay = false): void
    {
        $this->loggers[] = $logger;
        if ($replay) {
            foreach ($this->get_error_messages(false) as $error_message) {
                $this->send_message_to_logger($logger, $error_message);
            }
        }
    }
    /**
     * Allows set custom handler for fatal errors. Returns previous handler, if exists
     *
     * @param callable $callable
     * @return callable | null
     */
    public function set_fatal_error_handler($callable)
    {
        $ret = $this->fatal_error_handler;
        $this->fatal_error_handler = $callable;
        return $ret;
    }
    /**
     * Forward error message to psr compliant logger.
     */
    protected function log_message(array $msg)
    {
        if (!$this->loggers) {
            return;
        }
        foreach ($this->loggers as $logger) {
            $this->send_message_to_logger($logger, $msg);
        }
    }
    /**
     * Converts $msg to string representation
     *
     * @param array $msg error message
     */
    public static function format_error_message(array $msg): string
    {
        $file = Error_Utils::get_relative_file($msg['errfile']);
        return $msg['type'] . ': ' . $msg['errstr'] . ' in ' . $file . ' at line ' . $msg['errline'];
    }
    /**
     * Returns error type for given error level.
     *
     * @param int $errno level of the error raised
     *
     * @return string error type
     */
    public static function get_error_type(int $errno): string
    {
        return match ($errno) {
            E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_ERROR, E_RECOVERABLE_ERROR => 'Fatal error',
            E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING, E_WARNING => 'Warning',
            E_USER_NOTICE, E_NOTICE => 'Notice',
            E_USER_DEPRECATED, E_DEPRECATED => 'Deprecation',
            default => 'Unknown error',
        };
    }
    /**
     * Returns true if errno is a fatal error.
     *
     * @param int $errno
     */
    public static function is_fatal_error($errno): bool
    {
        return $errno === E_USER_ERROR || $errno === E_ERROR || $errno === E_CORE_ERROR || $errno === E_COMPILE_ERROR || $errno === E_RECOVERABLE_ERROR;
    }
    /**
     * Returns error PSR log level for given error level.
     *
     * @param int $errno level of the error raised
     *
     * @return string error log level
     */
    public static function get_log_level(int $errno)
    {
        return match ($errno) {
            E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR => static::LEVEL_CRITICAL,
            E_USER_ERROR, E_RECOVERABLE_ERROR, E_ERROR => static::LEVEL_ERROR,
            E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING, E_WARNING, E_USER_DEPRECATED, E_DEPRECATED => static::LEVEL_WARNING,
            E_USER_NOTICE, E_NOTICE => static::LEVEL_NOTICE,
            default => static::LEVEL_DEBUG,
        };
    }
    /**
     * Returns true, if display_errors settings is turned on.
     *
     * @return boolean
     */
    public static function display_error_enabled()
    {
        $value = @ini_get('display_errors');
        return match (strtolower($value)) {
            'on', 'yes', 'true', 'stdout', 'stderr', '1' => true,
            'off', 'no', '0' => false,
            default => (bool) (int) $value,
        };
    }
    /**
     * @param object $logger
     * @return void
     */
    protected function send_message_to_logger($logger, array $msg)
    {
        $message = static::format_error_message($msg);
        match ($msg['level']) {
            static::LEVEL_EMERGENCY => $logger->emergency($message, $msg),
            static::LEVEL_ALERT => $logger->alert($message, $msg),
            static::LEVEL_CRITICAL => $logger->critical($message, $msg),
            static::LEVEL_ERROR => $logger->error($message, $msg),
            static::LEVEL_WARNING => $logger->warning($message, $msg),
            static::LEVEL_NOTICE => $logger->notice($message, $msg),
            static::LEVEL_INFO => $logger->info($message, $msg),
            static::LEVEL_DEBUG => $logger->debug($message, $msg),
            default => $logger->log($msg['level'], $message, $msg),
        };
    }
}