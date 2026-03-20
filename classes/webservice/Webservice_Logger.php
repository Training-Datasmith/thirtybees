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
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
/**
 * Class WebserviceLoggerCore
 */
class Webservice_Logger_Core
{
    /**
     * @var bool
     */
    protected $enabled;
    /**
     * @var string
     */
    protected $correlation_id;
    /**
     * @var string
     */
    protected $key;
    protected string $file_time;
    /**
     * WebserviceLoggerCore constructor.
     */
    public function __construct()
    {
        $this->enabled = static::resolve_log_enabled_settings();
        $this->correlation_id = Tools::passwd_gen(12);
        $this->file_time = date('Ymd');
    }
    /**
     * Associates webservice key with this logger instance
     */
    public function set_key(Webservice_Key $key): void
    {
        $this->key = $key->key;
        // TODO: add option to enable logging per webservice account
        //
        //  $this->enabled = static::resolveLogEnabledSettings() && $key->log_enabled;
    }
    /**
     * Logs webservice request
     *
     * @param string $url
     * @param array $headers
     * @param string $payload
     */
    public function log_request(string $method, $url, $headers, $payload): void
    {
        // check that logging is enabled
        if (!$this->enabled) {
            return;
        }
        $filename = $this->get_log_filename();
        $formatted_headers = '';
        if ($headers) {
            $str_headers = [];
            foreach ($headers as $key => $value) {
                $str_headers[] = "{$key}={$value}";
            }
            $formatted_headers = ' [' . implode(', ', $str_headers) . ']';
        }
        $url = preg_replace('#//+#', '/', $url);
        $prefix = $this->get_prefix('REQUEST');
        $formatted_message = $prefix . $method . ' ' . $url . $formatted_headers . "\n";
        $formatted_message .= $this->format_payload($prefix, $payload);
        @file_put_contents($filename, $formatted_message, FILE_APPEND);
    }
    /**
     * Logs response
     *
     * @param string $content
     * @param array $errors
     */
    public function log_response($content, $errors, string $time): void
    {
        // check that logging is enabled
        if (!$this->enabled) {
            return;
        }
        $filename = $this->get_log_filename();
        $prefix = $this->get_prefix('RESPONSE');
        $formatted_message = $prefix;
        if ($errors) {
            $formatted_message .= 'Error response generated in ' . $time . " seconds. Errors: \n";
            foreach ($errors as $error) {
                $formatted_message .= $prefix . '  code ' . $error[0] . ': ' . $error[1] . "\n";
            }
        } else {
            $formatted_message .= 'Success response generated in ' . $time . " seconds\n";
            $formatted_message .= $this->format_payload($prefix, $content);
        }
        @file_put_contents($filename, $formatted_message, FILE_APPEND);
    }
    /**
     * Returns true, if logging is allowed by global settings
     */
    private static function resolve_log_enabled_settings(): bool
    {
        try {
            return (bool) Configuration::get_global_value('WEBSERVICE_LOG_ENABLED');
        } catch (Exception) {
            return false;
        }
    }
    /**
     * Returns directory for log files
     */
    public static function get_directory(): string
    {
        $dir = _PS_ROOT_DIR_ . '/log/webservice/';
        if (!is_dir($dir)) {
            @mkdir($dir);
        }
        return $dir;
    }
    /**
     * Returns log file
     */
    protected function get_log_filename(): string
    {
        $dir = static::get_directory();
        if (is_null($this->key)) {
            return $dir . 'webservice_' . $this->file_time . '.log';
        }
        $name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $this->key);
        return $dir . $name . '_' . $this->file_time . '.log';
    }
    /**
     * Returns log line prefix
     *
     * @param string $type log line type
     */
    protected function get_prefix(string $type): string
    {
        $padding = str_repeat(' ', 8 - strlen($type));
        return date('Y/m/d H:i:s') . ' [' . $this->correlation_id . '] [' . $type . $padding . '] ';
    }
    /**
     * Formats payload (request, response)
     *
     * @param string $payload
     */
    protected function format_payload(string $prefix, $payload): string
    {
        $formatted_payload = '';
        $payload = trim((string) $payload);
        if ($payload) {
            $payload = preg_replace("#\n#", "\n{$prefix}", $payload);
            $formatted_payload = $prefix . $payload . "\n";
        }
        return $formatted_payload;
    }
}