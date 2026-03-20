<?php

declare (strict_types=1);
namespace Thirtybees\Core\Error;

/**
 * This error handler is instantiated at application bootstrap, before autoload classes are loaded
 * Its purpose is to collect all errors and warnings before full-fledged error handler can be used
 *
 * Important note: No dependency on other classes can be used here. Keep is as simple as possible
 */
class Bootstrap_Error_Handler
{
    private array $errors;
    private bool $collect;
    /**
     * @return BootstrapErrorHandler|null
     */
    public static function get_instance()
    {
        static $instance = null;
        if ($instance === null) {
            $instance = new static();
        }
        return $instance;
    }
    /**
     *  private constructor
     */
    private function __construct()
    {
        $this->collect = true;
        $this->errors = [];
    }
    public function install_error_handler(): void
    {
        @ini_set('display_errors', 'off');
        @error_reporting(E_ALL);
        set_error_handler($this->error_handler(...));
    }
    /**
     * Error handler function
     *
     * @param int $errno level of the error raised
     * @param string $errstr error message
     * @param string $errfile filename that the error was raised in
     * @param int $errline line number the error was raised at
     */
    public function error_handler($errno, $errstr, $errfile, $errline): bool
    {
        if ($this->collect) {
            $this->errors[] = ['errno' => $errno, 'errstr' => $errstr, 'errfile' => $errfile, 'errline' => $errline];
        }
        return false;
    }
    public function get_collected_errors(): array
    {
        $this->collect = false;
        return $this->errors;
    }
}