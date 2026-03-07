<?php

declare(strict_types=1);
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

use FileLogger;
use SmartyCustom;
use Thirtybees\Core\Error\Response\ErrorResponseInterface;
use Throwable;

/**
 * Class ErrorHandlerCore
 */
class ErrorHandlerCore
{
    public const MAX_ERROR_MESSAGES = 1000;

    public const LEVEL_EMERGENCY = 'emergency';
    public const LEVEL_ALERT     = 'alert';
    public const LEVEL_CRITICAL  = 'critical';
    public const LEVEL_ERROR     = 'error';
    public const LEVEL_WARNING   = 'warning';
    public const LEVEL_NOTICE    = 'notice';
    public const LEVEL_INFO      = 'info';
    public const LEVEL_DEBUG     = 'debug';

    /**
     * @var array[] list of errors, warnings and notices encountered during request processing
     */
    protected $errorMessages = [];

    /**
     * @var object[] psr compliant logger
     */
    protected $loggers = [];

    /**
     * @var callable custom handler of fatal error
     */
    protected $fatalErrorHandler;

    /**
     * Error handler constructor
     *
     * Creates and initialize error handling logic
     */
    public function __construct(protected \Thirtybees\Core\Error\Response\ErrorResponseInterface $errorResponse)
    {
        @ini_set('display_errors', 'off');
        @error_reporting(E_ALL);

        // Set uncaught exception handler
        set_exception_handler($this->uncaughtExceptionHandler(...));

        // Set error handler
        set_error_handler($this->errorHandler(...));

        // register shutdown handler to catch fatal errors
        register_shutdown_function([$this, 'shutdown']);
    }

    public function replay(BootstrapErrorHandler $bootstrapErrorHandler): void
    {
        foreach ($bootstrapErrorHandler->getCollectedErrors() as $error) {
            $this->errorHandler(
                $error['errno'],
                $error['errstr'],
                $error['errfile'],
                $error['errline'],
            );
        }
    }

    public function setErrorResponseHandler(ErrorResponseInterface $errorResponse): void
    {
        $this->errorResponse = $errorResponse;
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
    public function getErrorMessages($includeSuppressed = false, $mask = E_ALL): array
    {
        if ($this->errorMessages) {
            return array_filter($this->errorMessages, function (array $error) use ($includeSuppressed, $mask): bool {
                if (!$includeSuppressed && $error['suppressed']) {
                    return false;
                }
                return (bool)($error['errno'] & $mask);
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
    public function uncaughtExceptionHandler(Throwable $e): void
    {
        static::handleFatalError(ErrorUtils::describeException($e));
    }

    public function handleFatalError(ErrorDescription $errorDescription): never
    {
        $this->logFatalError($errorDescription);
        $this->errorResponse->sendResponse($errorDescription);
        exit;
    }

    public function logFatalError(ErrorDescription $errorDescription): void
    {
        // log all exceptions to file
        $logger = new FileLogger();
        $logger->setFilename(_PS_ROOT_DIR_.'/log/'.date('Ymd').'_exception.log');
        $logger->logError($errorDescription->getExtendedMessage());

        // log exception through custom logger, if set
        if ($this->loggers) {
            $extra = $errorDescription->getExtraSections();
            $stacktrace = $errorDescription->getTraceAsString();
            $previous = $errorDescription->getCause();
            while ($previous) {
                $stacktrace .= "\nCaused by: ";
                $stacktrace .= $previous->getErrorName() . ': ' . $previous->getMessage();
                $stacktrace .= ' at line ' . $previous->getSourceLine();
                $stacktrace .= ' in file ' . ErrorUtils::getRelativeFile($previous->getSourceFile());
                $previous = $previous->getCause();
            }
            $extra[] = [
                'label' => 'Stacktrace',
                'content' => $stacktrace,
            ];

            $error = [
                'errno' => 0,
                'errstr' => $errorDescription->getErrorName() . ': ' . $errorDescription->getMessage(),
                'errfile' => ErrorUtils::getRelativeFile($errorDescription->getSourceFile()),
                'errline' => $errorDescription->getSourceLine(),
                'suppressed' => false,
                'type' => 'Exception',
                'level' => static::getLogLevel(E_ERROR),
                'extra' => $extra,
            ];

            $this->logMessage($error);
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
    public function errorHandler($errno, $errstr, $errfile, $errline): bool
    {
        $suppressed = error_reporting() === 0;
        $file = $errfile;
        $line = $errline;
        $realFile = null;
        $realLine = 0;
        $errno = (int)$errno;

        if (class_exists('SmartyCustom') && SmartyCustom::isCompiledTemplate($file)) {
            $realFile = ErrorUtils::getRelativeFile($errfile);
            $realLine = $errline;
            $file = SmartyCustom::getCurrentTemplate();
            $line = 0;
        }

        $file = ErrorUtils::getRelativeFile($file);

        $error = [
            'errno'       => $errno,
            'errstr'      => $errstr,
            'errfile'     => $file,
            'errline'     => $line,
            'suppressed'  => $suppressed,
            'type'        => static::getErrorType($errno),
            'level'       => static::getLogLevel($errno),
        ];
        if ($realFile) {
            $error['realFile'] = $realFile;
            $error['realLine'] = $realLine;
        }

        if (count($this->errorMessages) < static::MAX_ERROR_MESSAGES) {
            $this->errorMessages[] = $error;
        }
        if (! $suppressed) {
            $this->logMessage($error);
        }

        return $suppressed || static::displayErrorEnabled();
    }

    /**
     * Shutdown handler let us detect and react to fatal errors.
     */
    public function shutdown(): void
    {
        $error = error_get_last();

        if (is_array($error) && static::isFatalError($error['type'])) {
            $errorDescription = ErrorUtils::describeError($error);
            if ($this->fatalErrorHandler && is_callable($this->fatalErrorHandler)) {
                $this->logFatalError($errorDescription);
                call_user_func($this->fatalErrorHandler, $error);
            } else {
                $this->handleFatalError($errorDescription);
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
    public function addLogger($logger, $replay = false): void
    {
        $this->loggers[] = $logger;
        if ($replay) {
            foreach ($this->getErrorMessages(false) as $errorMessage) {
                $this->sendMessageToLogger($logger, $errorMessage);
            }
        }
    }

    /**
     * Allows set custom handler for fatal errors. Returns previous handler, if exists
     *
     * @param callable $callable
     * @return callable | null
     */
    public function setFatalErrorHandler($callable)
    {
        $ret = $this->fatalErrorHandler;
        $this->fatalErrorHandler = $callable;
        return $ret;
    }

    /**
     * Forward error message to psr compliant logger.
     */
    protected function logMessage(array $msg)
    {
        if (! $this->loggers) {
            return;
        }
        foreach ($this->loggers as $logger) {
            $this->sendMessageToLogger($logger, $msg);
        }
    }

    /**
     * Converts $msg to string representation
     *
     * @param array $msg error message
     */
    public static function formatErrorMessage(array $msg): string
    {
        $file = ErrorUtils::getRelativeFile($msg['errfile']);

        return $msg['type'] . ': ' . $msg['errstr'] . ' in ' . $file . ' at line ' . $msg['errline'];
    }

    /**
     * Returns error type for given error level.
     *
     * @param int $errno level of the error raised
     *
     * @return string error type
     */
    public static function getErrorType(int $errno): string
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
    public static function isFatalError($errno): bool
    {
        return (
            $errno === E_USER_ERROR ||
            $errno === E_ERROR ||
            $errno === E_CORE_ERROR ||
            $errno === E_COMPILE_ERROR ||
            $errno === E_RECOVERABLE_ERROR
        );
    }

    /**
     * Returns error PSR log level for given error level.
     *
     * @param int $errno level of the error raised
     *
     * @return string error log level
     */
    public static function getLogLevel(int $errno)
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
    public static function displayErrorEnabled()
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
    protected function sendMessageToLogger($logger, array $msg)
    {
        $message = static::formatErrorMessage($msg);
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
