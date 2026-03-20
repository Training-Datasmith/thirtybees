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
 * Class ConfigurationTestCore
 */
class Configuration_Test_Core
{
    public const NO_ARGUMENTS = false;
    public const TEST_UPLOAD = 'Upload';
    public const TEST_IMG_DIR = 'ImgDir';
    public const TEST_LOG_DIR = 'LogDir';
    public const TEST_CACHE_DIR = 'CacheDir';
    public const TEST_MODULES_DIR = 'ModuleDir';
    public const TEST_THEME_LANG_DIR = 'ThemeLangDir';
    public const TEST_THEME_PDF_LANG_DIR = 'ThemePdfLangDir';
    public const TEST_THEME_CACHE_DIR = 'ThemeCacheDir';
    public const TEST_TRANSLATIONS_DIR = 'TranslationsDir';
    public const TEST_CUSTOMIZABLE_PRODUCTS_DIR = 'CustomizableProductsDir';
    public const TEST_VIRTUAL_PRODUCTS_DIR = 'VirtualProductsDir';
    public const TEST_SYSTEM = 'System';
    public const TEST_FOPEN = 'Fopen';
    public const TEST_CONFIG_DIR = 'ConfigDir';
    public const TEST_FILES = 'Files';
    public const TEST_MAILS_DIR = 'MailsDir';
    public const TEST_MAX_EXECUTION_TIME = 'MaxExecutionTime';
    public const TEST_BCMATH = 'Bcmath';
    public const TEST_GD = 'Gd';
    public const TEST_JSON = 'Json';
    public const TEST_MBSTRING = 'Mbstring';
    public const TEST_OPENSSL = 'OpenSSL';
    public const TEST_PDO_MYSQL = 'PdoMysql';
    public const TEST_XML = 'Xml';
    public const TEST_ZIP = 'Zip';
    public const TEST_GZ = 'Gz';
    public const TEST_INTL = 'Intl';
    public const TEST_SOAP = 'Soap';
    public const TEST_YAML = 'Yaml';
    /**
     * @var array $testFiles
     */
    public static $test_files = ['/cache/smarty/compile', '/classes/log', '/classes/cache', '/config', '/controllers/admin/AdminLoginController.php', '/vendor/autoload.php', '/css', '/download', '/img/404.gif', '/js/tools.js', '/js/jquery/plugins/fancybox/jquery.fancybox.js', '/localization/fr.xml', '/mails', '/modules', '/pdf/order-return.tpl', '/themes/community-theme-default/css/global.css', '/translations/export', '/webservice/dispatcher.php'];
    /**
     * getDefaultTests return an array of tests to executes.
     * key are method name, value are parameters (false for no parameter)
     * all path are _PS_ROOT_DIR_ related
     */
    public static function get_default_tests(): array
    {
        return [static::TEST_UPLOAD => static::NO_ARGUMENTS, static::TEST_CACHE_DIR => 'cache', static::TEST_LOG_DIR => 'log', static::TEST_IMG_DIR => 'img', static::TEST_MODULES_DIR => 'modules', static::TEST_THEME_LANG_DIR => 'themes/' . _THEME_NAME_ . '/lang/', static::TEST_THEME_PDF_LANG_DIR => 'themes/' . _THEME_NAME_ . '/pdf/lang/', static::TEST_THEME_CACHE_DIR => 'themes/' . _THEME_NAME_ . '/cache/', static::TEST_TRANSLATIONS_DIR => 'translations', static::TEST_CUSTOMIZABLE_PRODUCTS_DIR => 'upload', static::TEST_VIRTUAL_PRODUCTS_DIR => 'download', static::TEST_SYSTEM => ['fopen', 'fclose', 'fread', 'fwrite', 'rename', 'file_exists', 'unlink', 'rmdir', 'mkdir', 'getcwd', 'chdir', 'chmod'], static::TEST_FOPEN => static::NO_ARGUMENTS, static::TEST_CONFIG_DIR => 'config', static::TEST_FILES => static::NO_ARGUMENTS, static::TEST_MAILS_DIR => 'mails', static::TEST_MAX_EXECUTION_TIME => static::NO_ARGUMENTS, static::TEST_BCMATH => static::NO_ARGUMENTS, static::TEST_GD => static::NO_ARGUMENTS, static::TEST_JSON => static::NO_ARGUMENTS, static::TEST_MBSTRING => static::NO_ARGUMENTS, static::TEST_OPENSSL => static::NO_ARGUMENTS, static::TEST_PDO_MYSQL => static::NO_ARGUMENTS, static::TEST_XML => static::NO_ARGUMENTS, static::TEST_ZIP => static::NO_ARGUMENTS, static::TEST_YAML => static::NO_ARGUMENTS];
    }
    /**
     * getDefaultTestsOp return an array of tests to executes.
     * key are method name, value are parameters (static::NO_ARGUMENTS for no parameter)
     */
    public static function get_default_tests_op(): array
    {
        return [static::TEST_GZ => static::NO_ARGUMENTS, static::TEST_INTL => static::NO_ARGUMENTS, static::TEST_SOAP => static::NO_ARGUMENTS];
    }
    /**
     * run all test defined in $tests
     *
     * @param array $tests
     *
     * @return array results of tests
     */
    public static function check($tests): array
    {
        $res = [];
        foreach ($tests as $key => $test) {
            $res[$key] = static::run($key, $test);
        }
        return $res;
    }
    /**
     * @param int $arg
     * @return string 'ok' on success, 'fail' or error message on failure.
     */
    public static function run(string $ptr, $arg = 0): string
    {
        $report = '';
        if ($arg) {
            $result = call_user_func_array([static::class, 'test' . $ptr], [$arg, &$report]);
        } else {
            $result = call_user_func_array([static::class, 'test' . $ptr], [&$report]);
        }
        if (!$result) {
            if (strlen($report)) {
                return $report;
            }
            return 'fail';
        }
        return 'ok';
    }
    public static function test_pdo_mysql(): bool
    {
        return extension_loaded('pdo_mysql');
    }
    public static function test_bcmath(): bool
    {
        return extension_loaded('bcmath') && function_exists('bcdiv');
    }
    public static function test_xml(): bool
    {
        return class_exists('SimpleXMLElement');
    }
    public static function test_json(): bool
    {
        return function_exists('json_encode') && function_exists('json_decode');
    }
    public static function test_zip(): bool
    {
        return class_exists('ZipArchive');
    }
    /**
     * @return string
     */
    public static function test_upload(): string|false
    {
        return ini_get('file_uploads');
    }
    /**
     * @return string
     */
    public static function test_fopen(): string|false
    {
        return ini_get('allow_url_fopen');
    }
    /**
     * @param array $funcs
     */
    public static function test_system($funcs, &$report = null): bool
    {
        foreach ($funcs as $func) {
            if (!function_exists($func)) {
                $report = 'Function ' . $func . '() does not exist.';
                return false;
            }
        }
        return true;
    }
    public static function test_intl(): bool
    {
        return extension_loaded('intl');
    }
    public static function test_soap(): bool
    {
        return extension_loaded('soap');
    }
    public static function test_yaml(): bool
    {
        return extension_loaded('yaml');
    }
    public static function test_gd(): bool
    {
        return function_exists('imagecreatetruecolor');
    }
    public static function test_max_execution_time(): bool
    {
        return ini_get('max_execution_time') <= 0 || ini_get('max_execution_time') >= 30;
    }
    /**
     * @return bool
     */
    public static function test_gz()
    {
        if (function_exists('gzencode')) {
            return @gzencode('dd') !== false;
        }
        return false;
    }
    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function test_config_dir($dir, &$report = null)
    {
        return static::test_dir($dir, false, $report);
    }
    /**
     * Test if directory is writable
     *
     * @param string $dir Directory path, absolute or relative
     * @param bool $recursive
     * @param string|null $fullReport
     * @param bool $absolute Is absolute path to directory
     */
    public static function test_dir($dir, $recursive = false, &$full_report = null, $absolute = false): bool
    {
        if ($absolute) {
            $absolute_dir = $dir;
        } else {
            $absolute_dir = rtrim(_PS_ROOT_DIR_, '\/') . DIRECTORY_SEPARATOR . trim($dir, '\/');
        }
        if (!file_exists($absolute_dir)) {
            $full_report = sprintf('Directory %s does not exist.', $absolute_dir);
            return false;
        }
        if (!is_writable($absolute_dir)) {
            $full_report = sprintf('Directory %s is not writable.', $absolute_dir);
            return false;
        }
        if ($recursive) {
            foreach (scandir($absolute_dir, SCANDIR_SORT_NONE) as $item) {
                $path = $absolute_dir . DIRECTORY_SEPARATOR . $item;
                if (in_array($item, ['.', '..', '.git'])) {
                    continue;
                }
                if (is_link($path)) {
                    continue;
                }
                if (is_dir($path)) {
                    if (!static::test_dir($path, $recursive, $full_report, true)) {
                        return false;
                    }
                }
                if (!is_writable($path)) {
                    $full_report = sprintf('File %s is not writable.', $path);
                    return false;
                }
            }
        }
        return true;
    }
    public static function test_file(string $file_relative, &$report = null): bool
    {
        $file = _PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . $file_relative;
        if (!file_exists($file)) {
            $report = 'File or directory ' . $file . ' does not exist.';
            return false;
        }
        if (!is_writable($file)) {
            $report = 'File or directory ' . $file . ' is not writable.';
            return false;
        }
        return true;
    }
    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function test_log_dir($dir, &$report = null)
    {
        return static::test_dir($dir, false, $report);
    }
    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function test_img_dir($dir, &$report = null)
    {
        return static::test_dir($dir, true, $report);
    }
    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function test_module_dir($dir, &$report = null)
    {
        return static::test_dir($dir, true, $report);
    }
    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function test_cache_dir($dir, &$report = null)
    {
        return static::test_dir($dir, true, $report);
    }
    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function test_mails_dir($dir, &$report = null)
    {
        return static::test_dir($dir, true, $report);
    }
    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function test_translations_dir($dir, &$report = null)
    {
        return static::test_dir($dir, true, $report);
    }
    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function test_theme_lang_dir($dir, &$report = null)
    {
        $absolute_dir = rtrim(_PS_ROOT_DIR_, '\/') . DIRECTORY_SEPARATOR . trim($dir, '\/');
        if (!file_exists($absolute_dir)) {
            return false;
        }
        return static::test_dir($dir, true, $report);
    }
    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function test_theme_pdf_lang_dir($dir, &$report = null)
    {
        $absolute_dir = rtrim(_PS_ROOT_DIR_, '\/') . DIRECTORY_SEPARATOR . trim($dir, '\/');
        if (!file_exists($absolute_dir)) {
            return true;
        }
        return static::test_dir($dir, true, $report);
    }
    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function test_theme_cache_dir($dir, &$report = null)
    {
        $absolute_dir = rtrim(_PS_ROOT_DIR_, '\/') . DIRECTORY_SEPARATOR . trim($dir, '\/');
        if (!file_exists($absolute_dir)) {
            return true;
        }
        return static::test_dir($dir, true, $report);
    }
    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function test_customizable_products_dir($dir, &$report = null)
    {
        return static::test_dir($dir, false, $report);
    }
    /**
     * @param string $dir
     * @param string|null $report
     * @return bool
     */
    public static function test_virtual_products_dir($dir, &$report = null)
    {
        return static::test_dir($dir, false, $report);
    }
    public static function test_mbstring(): bool
    {
        return extension_loaded('mbstring');
    }
    public static function test_open_ssl(): bool
    {
        return extension_loaded('openssl') && function_exists('openssl_encrypt');
    }
    /**
     * Test the set of files defined above. Not used by the installer, but by
     * AdminInformationController.
     *
     * @param bool $full
     */
    public static function test_files($full = false): array|bool
    {
        $return = [];
        foreach (static::$test_files as $file) {
            if (!file_exists(rtrim(_PS_ROOT_DIR_, DIRECTORY_SEPARATOR) . str_replace('/', DIRECTORY_SEPARATOR, $file))) {
                if ($full) {
                    $return[] = $file;
                } else {
                    return false;
                }
            }
        }
        if ($full) {
            return $return;
        }
        return true;
    }
}