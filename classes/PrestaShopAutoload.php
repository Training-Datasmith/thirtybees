<?php

declare (strict_types=1);
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */
/**
 * Class PrestaShopAutoload
 */
class Presta_Shop_Autoload
{
    /**
     * File where classes index is stored
     */
    public const INDEX_FILE = 'cache/class_index.php';
    /**
     * Namespace delimiter
     */
    public const NAMESPACE_DELIMITER = '\\';
    /**
     * @var PrestaShopAutoload singleton instance
     */
    protected static $instance;
    /**
     * @var array Mapping for legacy purposes
     */
    protected static $class_aliases = ['collection' => 'PrestaShopCollection', 'autoload' => 'PrestaShopAutoload', 'backup' => 'PrestaShopBackup', 'logger' => 'PrestaShopLogger', 'attributecore' => 'ProductAttributeCore'];
    /**
     * @var array Map from class name to class information
     */
    public $index = [];
    /**
     * @var bool indicates, if override files should be included in the index as well
     */
    public $_include_override_path = true;
    /**
     * @var string Root directory
     */
    protected string $root_dir;
    /**
     * PrestaShopAutoload constructor.
     */
    protected function __construct()
    {
        $this->root_dir = rtrim(_PS_ROOT_DIR_, '/\\') . DIRECTORY_SEPARATOR;
        $file = $this->root_dir . static::INDEX_FILE;
        if (file_exists($file) && is_readable($file)) {
            $this->index = include $file;
        } else {
            $this->generate_index();
        }
    }
    /**
     * Generate classes index
     */
    public function generate_index(): void
    {
        $classes = array_merge($this->get_classes_from_dir('classes/'), $this->get_classes_from_dir('controllers/'), $this->get_classes_from_dir('Adapter/'), $this->get_classes_from_dir('Core/'));
        if ($this->_include_override_path) {
            $classes = array_merge($classes, $this->get_classes_from_dir('override/classes/'), $this->get_classes_from_dir('override/controllers/'));
        }
        ksort($classes);
        $content = '<?php return ' . var_export($classes, true) . '; ?>';
        // Write classes index on disc to cache it
        $filename = $this->root_dir . static::INDEX_FILE;
        $dirname = dirname($filename);
        $filename_tmp = tempnam($dirname, basename($filename . '.'));
        if ($filename_tmp !== false && file_put_contents($filename_tmp, $content) !== false) {
            if (!@rename($filename_tmp, $filename)) {
                unlink($filename_tmp);
                error_log('Cannot rename temp autoload file');
            } else {
                @chmod($filename, 0666);
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($filename_tmp);
                }
            }
        } else {
            // $filename_tmp couldn't be written. $filename should be there anyway (even if outdated), no need to die.
            error_log('Cannot create temporary autoload file in directory ' . $dirname);
        }
        $this->index = $classes;
    }
    /**
     * Retrieve recursively all classes in a directory and its subdirectories
     *
     * @param string $path Relativ path from root to the directory
     */
    protected function get_classes_from_dir(string $path): array
    {
        $classes = [];
        $root_dir = $this->root_dir;
        foreach (scandir($root_dir . $path) as $file) {
            if ($file[0] != '.') {
                if (is_dir($root_dir . $path . $file)) {
                    $classes = array_merge($classes, $this->get_classes_from_dir($path . $file . '/'));
                } elseif (str_ends_with($file, '.php')) {
                    $content = file_get_contents($root_dir . $path . $file);
                    $file_namespace = $this->resolve_namespace($content);
                    $namespace_pattern = '[\a-z0-9_]*[\]';
                    $pattern = '#\W((abstract\s+)?class|interface)\s+(?P<classname>' . basename($file, '.php') . '(?:Core)?)' . '(?:\s+extends\s+' . $namespace_pattern . '[a-z][a-z0-9_]*)?(?:\s+implements\s+' . $namespace_pattern . '[a-z][\a-z0-9_]*(?:\s*,\s*' . $namespace_pattern . '[a-z][\a-z0-9_]*)*)?\s*\{#i';
                    if (preg_match($pattern, $content, $m)) {
                        $class_name = strtolower($file_namespace . $m['classname']);
                        $classes[$class_name] = ['name' => $m['classname'], 'ns' => rtrim($file_namespace, static::NAMESPACE_DELIMITER), 'path' => $path . $file, 'type' => trim($m[1])];
                        if (str_ends_with($class_name, 'core')) {
                            $override_class = substr($class_name, 0, -4);
                            $classes[$override_class] = ['name' => substr($m['classname'], 0, -4), 'ns' => rtrim($file_namespace, static::NAMESPACE_DELIMITER), 'path' => '', 'type' => $classes[$class_name]['type']];
                        }
                    }
                }
            }
        }
        return $classes;
    }
    /**
     * Extracts namespace from the php file, if exists
     *
     * @param string $content php file content
     * @return string namespace or empty string
     */
    protected function resolve_namespace($content): string
    {
        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            if (preg_match('#^\s*namespace\s+([^\s;]+)\s*;\s*$#', $line, $matches)) {
                return trim($matches[1], static::NAMESPACE_DELIMITER) . static::NAMESPACE_DELIMITER;
            }
        }
        return '';
    }
    /**
     * Get instance of autoload (singleton)
     *
     * @return static
     */
    public static function get_instance()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    /**
     * Retrieve informations about a class in classes index and load it
     *
     *
     * @return mixed
     */
    public function load(string $request_class_name)
    {
        $class_name = strtolower($request_class_name);
        // Retrocompatibility
        if (isset(static::$class_aliases[$class_name]) && !interface_exists($class_name, false) && !class_exists($class_name, false)) {
            return eval('class ' . $request_class_name . ' extends ' . static::$class_aliases[$class_name] . ' {}');
        }
        // regenerate the class index if the requested file doesn't exists
        if (isset($this->index[$class_name]) && $this->index[$class_name]['path'] && !is_file($this->root_dir . $this->index[$class_name]['path']) || isset($this->index[$class_name . 'core']) && $this->index[$class_name . 'core']['path'] && !is_file($this->root_dir . $this->index[$class_name . 'core']['path'])) {
            $this->generate_index();
        }
        // If $classname has not core suffix (E.g. Shop, Product)
        if (!str_ends_with($class_name, 'core')) {
            // If requested class does not exist, load associated core class
            if (isset($this->index[$class_name]) && !$this->index[$class_name]['path']) {
                require_once $this->root_dir . $this->index[$class_name . 'core']['path'];
                if ($this->index[$class_name . 'core']['type'] != 'interface') {
                    $core_definition = $this->index[$class_name . 'core'];
                    $override_definition = $this->index[$class_name];
                    if (isset($core_definition['ns']) && $core_definition['ns']) {
                        $dynamic_override = 'namespace ' . $override_definition['ns'] . ";\n" . $core_definition['type'] . ' ' . $override_definition['name'] . ' extends ' . $core_definition['name'] . ' {}';
                    } else {
                        $dynamic_override = $core_definition['type'] . ' ' . $override_definition['name'] . ' extends ' . $core_definition['name'] . ' {}';
                    }
                    eval($dynamic_override);
                }
            } else {
                // request a non Core Class load the associated Core class if exists
                if (isset($this->index[$class_name . 'core'])) {
                    require_once $this->root_dir . $this->index[$class_name . 'core']['path'];
                }
                if (isset($this->index[$class_name])) {
                    require_once $this->root_dir . $this->index[$class_name]['path'];
                }
            }
        } elseif (isset($this->index[$class_name]['path']) && $this->index[$class_name]['path']) {
            require_once $this->root_dir . $this->index[$class_name]['path'];
        }
    }
    /**
     * @param string $className
     *
     * @return string | null
     */
    public function get_class_path($class_name)
    {
        $class_name = strtolower($class_name);
        return $this->index[$class_name]['path'] ?? null;
    }
}