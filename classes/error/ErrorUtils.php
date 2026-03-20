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

use Presta_Shop_Exception_Core;
use Smarty_Custom;
use Throwable;
/**
 * Class ErrorUtilsCore
 */
class Error_Utils_Core
{
    public const FILE_CONTEXT_LINES = 30;
    /**
     * Describe error array
     */
    public static function describe_error(array $error): \Thirtybees\Core\Error\Error_Description
    {
        $file = $error['file'];
        $line = $error['line'];
        $error_description = new Error_Description();
        $error_description->set_error_name('Fatal Error');
        $error_description->set_message($error['message']);
        $error_description->set_source('php', $file, $line, []);
        try {
            $smarty_trace = static::get_smarty_trace();
            $is_template = static::is_compiled_template($smarty_trace, $file);
            if ($is_template) {
                $compiled_content = static::read_file($file, $line, static::FILE_CONTEXT_LINES);
                $error_description->set_real_source($file, $line, $compiled_content);
                $file = array_pop($smarty_trace);
                $content = static::read_file($file, 0, -1);
                $error_description->set_source('smarty', $file, 0, $content);
            } else {
                $content = static::read_file($file, $line, static::FILE_CONTEXT_LINES);
                $error_description->set_source('php', $file, $line, $content);
            }
            $stacktrace = [1 => ['class' => '', 'function' => '', 'type' => '', 'fileType' => $is_template ? 'template' : 'php', 'fileName' => static::get_relative_file($error['file']), 'line' => $line, 'args' => null, 'fileContent' => $content, 'suppressed' => false]];
            $error_description->set_stack_trace($stacktrace);
        } catch (Throwable) {
        }
        return $error_description;
    }
    /**
     * Helper method to describe exception
     */
    public static function describe_exception(Throwable $e): \Thirtybees\Core\Error\Error_Description
    {
        $file = $e->get_file();
        $line = $e->get_line();
        $error_description = new Error_Description();
        $error_description->set_error_name(str_replace('PrestaShop', 'ThirtyBees', $e::class));
        $error_description->set_message($e->get_message());
        $error_description->set_source('php', $file, $line, []);
        try {
            $smarty_trace = static::get_smarty_trace();
            if (static::is_compiled_template($smarty_trace, $file)) {
                $compiled_content = static::read_file($file, $line, static::FILE_CONTEXT_LINES);
                $error_description->set_real_source($file, $e->get_line(), $compiled_content);
                $file = array_pop($smarty_trace);
                $error_description->set_source('smarty', $file, 0, static::read_file($file, 0, -1));
            } else {
                $error_description->set_source('php', $file, $line, static::read_file($file, $line, static::FILE_CONTEXT_LINES));
            }
            if ($e instanceof Presta_Shop_Exception_Core) {
                $traces = $e->get_custom_trace();
                $error_description->set_extra_sections($e->get_extra_sections());
            } else {
                $traces = $e->get_trace();
            }
            $stacktrace = [];
            foreach ($traces as $id => $trace) {
                $class = $trace['class'] ?? '';
                $function = $trace['function'] ?? '';
                $type = $trace['type'] ?? '';
                $file_name = $trace['file'] ?? '';
                $line_number = $trace['line'] ?? 0;
                $args = $trace['args'] ?? [];
                $is_template = false;
                $show_lines = static::FILE_CONTEXT_LINES;
                if (static::is_compiled_template($smarty_trace, $file_name)) {
                    $is_template = true;
                    $file_name = array_pop($smarty_trace);
                    $line_number = 0;
                    $show_lines = -1;
                }
                $relative_file = static::get_relative_file($file_name);
                $next_id = $id + 1;
                $current_function = '';
                $current_class = '';
                if (isset($traces[$next_id]['class'])) {
                    $current_class = $traces[$next_id]['class'];
                    $current_function = $traces[$next_id]['function'];
                }
                $stacktrace[] = ['class' => $class, 'function' => $function, 'type' => $type, 'fileType' => $is_template ? 'template' : 'php', 'fileName' => $relative_file, 'line' => $line_number, 'args' => array_map(self::display_argument(...), $args), 'fileContent' => static::read_file($file_name, $line_number, $show_lines), 'description' => static::describe_operation($class, $function, $args), 'suppressed' => static::is_suppressed($relative_file, $current_class, $current_function, $class, $function)];
            }
            $error_description->set_stack_trace($stacktrace);
            $previous = $e->get_previous();
            if ($previous) {
                $error_description->set_cause(static::describe_exception($previous));
            }
        } catch (Throwable) {
        }
        return $error_description;
    }
    /**
     * Method will render argument into string. Similar to var_dump, but will product smaller output
     *
     * @param mixed $variable variable to be rendered
     * @param int $strlen max length of string. If longer then string will be truncated and ... will be added
     * @param int $width maximal number of array items to be rendered
     * @param int $depth maximaln depth that we will traverse
     * @param int $i current depth
     * @param array $objects array of seen objects
     */
    public static function display_argument($variable, $strlen = 80, $width = 50, $depth = 2, $i = 0, $objects = []): string
    {
        $search = ["\x00", "\\a", "\\b", "\f", "\n", "\r", "\t", "\v"];
        $replace = ['\0', '\a', '\b', '\f', '\n', '\r', '\t', '\v'];
        switch (gettype($variable)) {
            case 'boolean':
                return $variable ? 'true' : 'false';
            case 'integer':
            case 'double':
                return (string) $variable;
            case 'resource':
                return '[resource]';
            case 'NULL':
                return 'null';
            case 'unknown type':
                return '???';
            case 'string':
                $len = strlen($variable);
                $variable = str_replace($search, $replace, substr($variable, 0, $strlen));
                $variable = substr($variable, 0, $strlen);
                if ($len < $strlen) {
                    return '"' . $variable . '"';
                }
                return 'string(' . $len . '): "' . $variable . '"...';
            case 'array':
                $len = count($variable);
                if ($i == $depth) {
                    return 'array(' . $len . ') [...]';
                }
                if (!$len) {
                    return 'array(0) []';
                }
                $string = '';
                $keys = array_keys($variable);
                $spaces = str_repeat(' ', $i * 2);
                $string .= "array({$len})\n" . $spaces . '[';
                $count = 0;
                foreach ($keys as $key) {
                    if ($count == $width) {
                        $string .= "\n" . $spaces . '  ...';
                        break;
                    }
                    $string .= "\n" . $spaces . "  [{$key}] => ";
                    if (static::is_sensitive_parameter($key)) {
                        $string .= static::display_argument('*******', $strlen, $width, $depth, $i + 1, $objects);
                    } else {
                        $string .= static::display_argument($variable[$key], $strlen, $width, $depth, $i + 1, $objects);
                    }
                    $count++;
                }
                return $string . ("\n" . $spaces . ']');
            case 'object':
                $id = array_search($variable, $objects, true);
                if ($id !== false) {
                    return $variable::class . '#' . ($id + 1) . ' {...}';
                }
                if ($i == $depth) {
                    return $variable::class . ' {...}';
                }
                $string = '';
                $id = array_push($objects, $variable);
                $array = (array) $variable;
                $spaces = str_repeat(' ', $i * 2);
                $string .= $variable::class . "#{$id}\n" . $spaces . '{';
                $properties = array_keys($array);
                foreach ($properties as $property) {
                    $value = $array[$property];
                    $name = preg_replace('/[^a-zA-Z0-9_]/', '', trim((string) $property));
                    $string .= "\n" . $spaces . "  [{$name}] => ";
                    if (static::is_sensitive_parameter($name)) {
                        $string .= static::display_argument('*******', $strlen, $width, $depth, $i + 1, $objects);
                    } else {
                        $string .= static::display_argument($value, $strlen, $width, $depth, $i + 1, $objects);
                    }
                }
                return $string . ("\n" . $spaces . '}');
            default:
                return print_r($variable, true);
        }
    }
    /**
     * @param string $name
     */
    protected static function is_sensitive_parameter($name): bool
    {
        $name = strtolower($name ?? '');
        $sensitive = ['passwd', 'password', 'secret', 'salt', 'sensitive', 'securekey'];
        if (in_array($name, $sensitive)) {
            return true;
        }
        if (in_array(preg_replace('/[^a-z]/', '', $name), $sensitive)) {
            return true;
        }
        return false;
    }
    /**
     * Returns file path relative to thirtybees root
     *
     * @param string|null $file
     */
    public static function get_relative_file($file): string
    {
        if ($file) {
            return ltrim(str_replace([_PS_ROOT_DIR_, '\\'], ['', '/'], $file), '/');
        }
        return '';
    }
    /**
     * Helper method to downplay some entries from stacktrace. Some entries from stacktrace
     * will be greyed out, and displayed with smaller font, so it does not distract reader
     * when investigating source of error
     *
     * @param string $relativePath relative path of file
     * @param string $class current classname
     * @param string $function currently evaluating function
     * @param string $calledClass class that's being called
     * @param string $calledFunction function being called
     *
     * @return bool if this entry should be suppressed
     */
    protected static function is_suppressed($relative_path, $class, $function, $called_class, $called_function): bool
    {
        // suppress any entries that calls following methods
        $suppress_calls = [['DispatcherCore', 'dispatch'], ['Smarty_Custom_Template', 'fetch'], ['ControllerCore', 'run']];
        foreach ($suppress_calls as $callable) {
            if ($callable[0] === $called_class && $callable[1] === $called_function) {
                return true;
            }
        }
        // suppress these methods
        $suppress_methods = [['DispatcherCore', 'dispatch'], ['DbCore', 'execute'], ['DbCore', 'query'], ['Smarty_Custom_Template', 'fetch'], ['ControllerCore', 'run'], ['HookCore', 'exec'], ['HookCore', 'execWithoutCache'], ['HookCore', 'coreCallHook']];
        foreach ($suppress_methods as $callable) {
            if ($callable[0] === $class && $callable[1] === $function) {
                return true;
            }
        }
        // suppress any entries if filepath starts with following substring
        $paths = ['vendor/', 'classes/SmartyCustom.php', 'config/smarty'];
        foreach ($paths as $match) {
            if (str_starts_with($relative_path, $match)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Helper method to describe special functions in thirtybees codebase, such as
     * method to include sub-template from within smarty, or smarty function to trigger
     * hook.
     *
     * This makes the stacktrace more readable
     *
     * @param string $class class name
     * @param string $function called function
     * @param array $args parameters passed to $class::$function() method
     */
    protected static function describe_operation($class, $function, array $args): ?string
    {
        if ($class === 'Smarty_Internal_Template' && $function === 'getSubTemplate') {
            $template_name = isset($args['0']) && is_string($args['0']) ? static::get_relative_file($args['0']) : '';
            return 'Include sub-template <b>' . $template_name . '</b>';
        }
        if (!$class && $function === 'smartyHook') {
            $hook_name = $args[0]['h'] ?? '';
            return 'Execute hook <b>' . $hook_name . '</b>';
        }
        return null;
    }
    /**
     * Reads $file from disk, and returns $total lines around $line. Result is an array
     * of arrays, with information about line number in file, if the line is highlighted,
     * and actual line
     *
     * @param string $file input file
     * @param int $line index of line in the file. This line will be highlighted
     * @param int $total total number of lines to read. Pass zero to return all lines
     */
    protected static function read_file($file, $line, $total): array
    {
        $ret = [];
        if (!file_exists($file)) {
            return $ret;
        }
        $lines = file($file);
        if ($lines) {
            if ($total > 0) {
                $third = (int) ($total / 3);
                $offset = $line - 2 * $third;
                if ($offset < 0) {
                    $offset = 0;
                }
                $lines = array_slice($lines, $offset, $total);
            } else {
                $offset = 0;
            }
            foreach ($lines as $k => $l) {
                $number = $offset + $k + 1;
                $ret[] = ['number' => $number, 'highlighted' => $number === $line, 'line' => $l];
            }
        }
        return $ret;
    }
    protected static function get_smarty_trace(): array
    {
        if (class_exists('SmartyCustom')) {
            return Smarty_Custom::$trace;
        }
        return [];
    }
    protected static function is_compiled_template(array $smarty_trace, string $file): bool
    {
        if ($smarty_trace) {
            return Smarty_Custom::is_compiled_template($file);
        }
        return false;
    }
}