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

use Encryptor;
use Presta_Shop_Exception;
/**
 * class ErrorDescription
 */
class Error_Description_Core
{
    private string $php_version;
    private string $code_build_for;
    private string $code_revision;
    /**
     * @var string
     */
    protected $error_name;
    /**
     * @var string
     */
    protected $message;
    /**
     * @var string
     */
    protected $source_type;
    /**
     * @var string
     */
    protected $source_file;
    /**
     * @var int
     */
    protected $source_line;
    /**
     * @var array
     */
    protected $source_file_content = [];
    /**
     * @var string
     */
    protected $real_source_file = '';
    /**
     * @var int
     */
    protected $real_source_line = 0;
    /**
     * @var array
     */
    protected $real_source_content = [];
    /**
     * @var array
     */
    protected $extra_sections = [];
    /**
     * @var array
     */
    protected $stack_trace = [];
    /**
     * @var ErrorDescription
     */
    protected $cause;
    /**
     * ErrorDescription constructor
     */
    public function __construct()
    {
        $this->php_version = phpversion();
        $this->code_build_for = static::resolve_code_build_for();
        $this->code_revision = static::resolve_code_revision();
    }
    public function set_error_name(string $error_name): void
    {
        $this->error_name = $error_name;
    }
    public function set_message(string $message): void
    {
        $this->message = $message;
    }
    public function get_error_name(): string
    {
        return $this->error_name;
    }
    public function get_message(): string
    {
        return $this->message;
    }
    public function set_source(string $source_type, string $file, int $line, array $content): void
    {
        $this->source_type = $source_type;
        $this->source_file = $file;
        $this->source_line = $line;
        $this->source_file_content = $content;
    }
    public function set_real_source(string $file, int $line, array $content): void
    {
        $this->real_source_file = $file;
        $this->real_source_line = $line;
        $this->real_source_content = $content;
    }
    public function get_source_type(): string
    {
        return $this->source_type;
    }
    public function get_source_file(): string
    {
        return $this->source_file;
    }
    public function get_source_line(): int
    {
        return (int) $this->source_line;
    }
    public function get_source_file_content(): array
    {
        return $this->source_file_content;
    }
    public function has_source_file_content(): bool
    {
        return !!$this->source_file_content;
    }
    public function get_real_source_file_content(): array
    {
        return $this->real_source_content;
    }
    public function has_real_source_file_content(): bool
    {
        return !!$this->real_source_content;
    }
    public function set_extra_sections(array $extra_sections): void
    {
        $this->extra_sections = $extra_sections;
    }
    public function get_extra_sections(): array
    {
        return $this->extra_sections;
    }
    public function set_stack_trace(array $stacktrace): void
    {
        $this->stack_trace = $stacktrace;
    }
    public function get_stack_trace(): array
    {
        return $this->stack_trace;
    }
    public function set_cause(Error_Description $error_description): void
    {
        $this->cause = $error_description;
    }
    /**
     * @return ErrorDescription | null
     */
    public function get_cause()
    {
        return $this->cause;
    }
    public function has_cause(): bool
    {
        return !is_null($this->cause);
    }
    /**
     * Return the content of the Exception
     * @return string content of the exception.
     */
    public function get_extended_message(): string
    {
        if ($this->get_source_type() === 'smarty') {
            return $this->get_error_name() . ': ' . $this->get_message() . ' in template file ' . Error_Utils::get_relative_file($this->get_source_file());
        }
        return $this->get_error_name() . ': ' . $this->get_message() . ' at line ' . $this->get_source_line() . ' in file ' . Error_Utils::get_relative_file($this->get_source_file());
    }
    public function get_trace_as_string(): string
    {
        $result = '';
        $stack_trace = $this->get_stack_trace();
        if ($stack_trace) {
            $total = count($stack_trace) + 1;
            $separator_len = strlen("{$total}") + 1;
            $separator = str_repeat(' ', $separator_len - 1);
            $result .= '#0' . $separator . Error_Utils::get_relative_file($this->get_source_file()) . '(' . $this->get_source_line() . ")\n";
            $cnt = 1;
            foreach ($stack_trace as $trace) {
                $len = strlen("{$cnt}");
                $separator = str_repeat(' ', $separator_len - $len);
                $result .= '#' . $cnt . $separator . $trace['fileName'] . '(' . $trace['line'] . '): ';
                $result .= $trace['class'] . $trace['type'] . $trace['function'] . '(';
                if ($trace['args']) {
                    $args = array_map(fn($param) => strtok($param, "\n"), $trace['args']);
                    $result .= implode(', ', $args);
                }
                $result .= ')';
                $result .= "\n";
                $cnt++;
            }
        }
        return $result;
    }
    /**
     * @return string
     */
    public function get_php_version()
    {
        return $this->php_version;
    }
    /**
     * @param string $phpVersion
     */
    public function set_php_version($php_version): void
    {
        $this->php_version = $php_version;
    }
    public function get_code_build_for(): string
    {
        return $this->code_build_for;
    }
    public function set_code_build_for(string $code_build_for): void
    {
        $this->code_build_for = $code_build_for;
    }
    public function get_code_revision(): string
    {
        return $this->code_revision;
    }
    public function set_code_revision(string $code_revision): void
    {
        $this->code_revision = $code_revision;
    }
    public function get_real_source_file(): string
    {
        return $this->real_source_file;
    }
    public function set_real_source_file(string $real_source_file): void
    {
        $this->real_source_file = $real_source_file;
    }
    public function get_real_source_line(): int
    {
        return $this->real_source_line;
    }
    public function set_real_source_line(int $real_source_line): void
    {
        $this->real_source_line = $real_source_line;
    }
    public function to_array(): array
    {
        $source = ['type' => $this->get_source_type(), 'file' => $this->get_source_file(), 'line' => $this->get_source_line(), 'content' => $this->get_source_file_content()];
        if ($this->real_source_file) {
            $source['realFile'] = $this->real_source_file;
            $source['realLine'] = $this->real_source_line;
            $source['realContent'] = $this->get_real_source_file_content();
        }
        $data = ['phpVersion' => $this->get_php_version(), 'codeBuildFor' => $this->get_code_build_for(), 'codeRevision' => $this->get_code_revision(), 'errorName' => $this->get_error_name(), 'message' => $this->get_message(), 'source' => $source, 'stackTrace' => $this->get_stack_trace(), 'extra' => $this->get_extra_sections()];
        if ($this->cause) {
            $data['cause'] = $this->cause->to_array();
        }
        return $data;
    }
    /**
     * @return string
     */
    public function serialize()
    {
        return json_encode($this->to_array(), JSON_PRETTY_PRINT);
    }
    /**
     * @return string
     * @throws PrestaShopException
     */
    public function encrypt()
    {
        return Encryptor::get_instance()->encrypt($this->serialize());
    }
    /**
     * @param string $encrypted
     * @return ErrorDescription
     * @throws PrestaShopException
     */
    public static function decrypt($encrypted)
    {
        $decrypted = Encryptor::get_instance()->decrypt($encrypted);
        if (!$decrypted) {
            throw new Presta_Shop_Exception('Failed to decrypt content');
        }
        $array = json_decode((string) $decrypted, true);
        if (!is_array($array) || !$array) {
            throw new Presta_Shop_Exception('Failed to parse content');
        }
        return static::deserialize($array);
    }
    /**
     * @param array $array
     * @throws PrestaShopException
     */
    public static function deserialize($array): \Thirtybees\Core\Error\Error_Description
    {
        $description = new Error_Description();
        $description->set_php_version(static::get_property('phpVersion', $array, false, 'unknown'));
        $description->set_code_build_for(static::get_property('codeBuildFor', $array, false, 'unknown'));
        $description->set_code_revision(static::get_property('codeRevision', $array, false, 'unknown'));
        $description->set_error_name(static::get_property('errorName', $array));
        $description->set_message(static::get_property('message', $array));
        $source = static::get_property('source', $array);
        $description->set_source(static::get_property('type', $source), static::get_property('file', $source), (int) static::get_property('line', $source), static::get_property('content', $source));
        if (array_key_exists('realContent', $source)) {
            $description->set_real_source(static::get_property('realFile', $source), (int) static::get_property('realLine', $source), static::get_property('realContent', $source));
        }
        $description->set_stack_trace(static::get_property('stackTrace', $array));
        $description->set_extra_sections(static::get_property('extra', $array));
        $cause = static::get_property('cause', $array, false);
        if ($cause) {
            $description->set_cause(static::deserialize($cause));
        }
        return $description;
    }
    /**
     * @param string $name
     * @param boolean $required
     * @return mixed
     * @throws PrestaShopException
     */
    protected static function get_property($name, array $array, $required = true, $default = '')
    {
        if (array_key_exists($name, $array)) {
            return $array[$name];
        }
        if ($required) {
            throw new Presta_Shop_Exception("Missing key '{$name}'");
        }
        return $default;
    }
    /**
     * @return string
     */
    protected static function resolve_code_build_for()
    {
        if (defined('_TB_BUILD_PHP_')) {
            return _TB_BUILD_PHP_;
        }
        if (defined('_TB_INSTALL_BUILD_PHP_')) {
            return _TB_INSTALL_BUILD_PHP_;
        }
        return 'unknown';
    }
    /**
     * @return string
     */
    protected static function resolve_code_revision()
    {
        if (defined('_TB_REVISION_')) {
            return _TB_REVISION_;
        }
        if (defined('_TB_INSTALL_REVISION_')) {
            return _TB_INSTALL_REVISION_;
        }
        return 'unknown';
    }
}