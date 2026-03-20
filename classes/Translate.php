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
 * Class TranslateCore
 */
class Translate_Core
{
    /**
     * Get a translation for an admin controller
     *
     * @param string $string
     * @param bool $addslashes
     * @param bool $htmlentities
     * @return string
     */
    public static function get_admin_translation($string, string $class = 'AdminTab', $addslashes = false, $htmlentities = true, $sprintf = null)
    {
        static $modules_tabs = null;
        global $_LANGADM;
        if ($modules_tabs === null) {
            try {
                $modules_tabs = Tab::get_module_tab_list();
            } catch (Presta_Shop_Exception) {
                $modules_tabs = [];
            }
        }
        if ($_LANGADM == null) {
            $iso = Context::get_context()->language->iso_code;
            if (empty($iso)) {
                try {
                    $iso = Language::get_iso_by_id((int) Configuration::get('PS_LANG_DEFAULT'));
                } catch (Presta_Shop_Exception) {
                    $iso = 'en';
                }
            }
            if (file_exists(_PS_TRANSLATIONS_DIR_ . $iso . '/admin.php')) {
                include_once _PS_TRANSLATIONS_DIR_ . $iso . '/admin.php';
            }
        }
        if (isset($modules_tabs[strtolower($class)])) {
            $class_name_controller = $class . 'Controller';
            // if this is module admin controller, use module translation
            if (class_exists($class_name_controller)) {
                $module_name = Module::get_module_name_from_class($class_name_controller);
                if ($module_name) {
                    return static::get_module_translation($module_name, $string, $class_name_controller, $sprintf, $addslashes);
                }
            }
        }
        $string = preg_replace("/\\\\*'/", "\\'", $string);
        $key = md5((string) $string);
        if (isset($_LANGADM[$class . $key]) && $_LANGADM[$class . $key] !== '') {
            $str = $_LANGADM[$class . $key];
        } else {
            $str = static::get_generic_admin_translation($string, $key, $_LANGADM);
        }
        if ($htmlentities) {
            $str = htmlspecialchars((string) $str, ENT_QUOTES, 'utf-8');
        }
        $str = str_replace('"', '&quot;', $str);
        if ($sprintf !== null) {
            $str = static::check_and_replace_args($str, $sprintf);
        }
        return $addslashes ? addslashes($str) : stripslashes($str);
    }
    /**
     * Get a translation for a module
     *
     * @param string|Module|ModuleCore $module
     * @param string $string
     * @param array $sprintf
     * @param bool $js
     * @return string
     */
    public static function get_module_translation($module, $string, string $source, $sprintf = null, $js = false)
    {
        global $_MODULES, $_MODULE, $_LANGADM;
        static $lang_cache = [];
        // $_MODULES is a cache of translations for all module.
        // $translations_merged is a cache of wether a specific module's translations have already been added to $_MODULES
        static $translations_merged = [];
        $name = $module instanceof Module ? $module->name : $module;
        $language = Context::get_context()->language;
        if (!isset($translations_merged[$name]) && isset(Context::get_context()->language)) {
            $files_by_priority = [
                // Translations in theme
                _PS_THEME_DIR_ . 'modules/' . $name . '/translations/' . $language->iso_code . '.php',
                _PS_THEME_DIR_ . 'modules/' . $name . '/' . $language->iso_code . '.php',
                // PrestaShop 1.5 translations
                _PS_MODULE_DIR_ . $name . '/translations/' . $language->iso_code . '.php',
                // PrestaShop 1.4 translations
                _PS_MODULE_DIR_ . $name . '/' . $language->iso_code . '.php',
            ];
            foreach ($files_by_priority as $file) {
                if (file_exists($file)) {
                    include_once $file;
                    $_MODULES = !empty($_MODULES) ? $_MODULES + $_MODULE : $_MODULE;
                    //we use "+" instead of array_merge() because array merge erase existing values.
                    $translations_merged[$name] = true;
                }
            }
        }
        $string = preg_replace("/\\\\*'/", "\\'", $string);
        $key = md5((string) $string);
        $cache_key = $name . '|' . $string . '|' . $source . '|' . (int) $js;
        if (!isset($lang_cache[$cache_key])) {
            if ($_MODULES == null) {
                return static::escape_module_translation($string, $sprintf, $js);
            }
            $current_key = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $source) . '_' . $key;
            $default_key = strtolower('<{' . $name . '}thirtybees>' . $source) . '_' . $key;
            $presta_shop_key = strtolower('<{' . $name . '}prestashop>' . $source) . '_' . $key;
            if (str_ends_with($source, 'controller')) {
                $file = substr($source, 0, -10);
                $current_key_file = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $file) . '_' . $key;
                $default_key_file = strtolower('<{' . $name . '}thirtybees>' . $file) . '_' . $key;
                $presta_shop_key_file = strtolower('<{' . $name . '}prestashop>' . $file) . '_' . $key;
            }
            if (isset($current_key_file) && !empty($_MODULES[$current_key_file])) {
                $ret = $_MODULES[$current_key_file];
            } elseif (isset($default_key_file) && !empty($_MODULES[$default_key_file])) {
                $ret = $_MODULES[$default_key_file];
            } elseif (isset($presta_shop_key_file) && !empty($_MODULES[$presta_shop_key_file])) {
                $ret = $_MODULES[$presta_shop_key_file];
            } elseif (!empty($_MODULES[$current_key])) {
                $ret = $_MODULES[$current_key];
            } elseif (!empty($_MODULES[$default_key])) {
                $ret = $_MODULES[$default_key];
            } elseif (!empty($_MODULES[$presta_shop_key])) {
                $ret = $_MODULES[$presta_shop_key];
            } elseif (!empty($_LANGADM)) {
                $ret = static::get_generic_admin_translation($string, $key, $_LANGADM);
            } else {
                $ret = $string;
            }
            $ret = static::escape_module_translation($ret, $sprintf, $js);
            if ($sprintf === null) {
                $lang_cache[$cache_key] = $ret;
            } else {
                return $ret;
            }
        }
        return $lang_cache[$cache_key];
    }
    /**
     * Helper method to escape return value for getModuleTranslation
     *
     * @param string $input
     * @param array $sprintf
     * @param bool $js
     */
    protected static function escape_module_translation($input, $sprintf, $js): string
    {
        if (!$input) {
            return '';
        }
        $ret = stripslashes($input);
        if ($sprintf !== null) {
            $ret = static::check_and_replace_args($ret, $sprintf);
        }
        return $js ? addslashes($ret) : htmlspecialchars($ret, ENT_COMPAT, 'UTF-8');
    }
    /**
     * Check if string use a specif syntax for sprintf and replace arguments if use it
     *
     * @param string $string
     * @param array $args
     *
     * @return string
     */
    public static function check_and_replace_args($string, $args)
    {
        if (preg_match_all('#(?:%%|%(?:[0-9]+\$)?[+-]?(?:[ 0]|\'.)?-?[0-9]*(?:\.[0-9]+)?[bcdeufFosxX])#', $string) && !is_null($args)) {
            if (!is_array($args)) {
                $args = [$args];
            }
            return vsprintf($string, $args);
        }
        return $string;
    }
    /**
     * Return the translation for a string if it exists for the base AdminController or for helpers
     *
     * @param string $string string to translate
     * @param string|null $key md5 key if already calculated (optional)
     * @param array $langArray Global array of admin translations
     *
     * @return string translation
     */
    public static function get_generic_admin_translation($string, $key, array &$lang_array)
    {
        $string = preg_replace("/\\\\*'/", "\\'", $string);
        if (is_null($key)) {
            $key = md5((string) $string);
        }
        if (isset($lang_array['AdminController' . $key])) {
            $str = $lang_array['AdminController' . $key];
        } elseif (isset($lang_array['Helper' . $key])) {
            $str = $lang_array['Helper' . $key];
        } elseif (isset($lang_array['AdminTab' . $key])) {
            $str = $lang_array['AdminTab' . $key];
        } else {
            // note in 1.5, some translations has moved from AdminXX to helper/*.tpl
            $str = $string;
        }
        return $str !== '' ? $str : $string;
    }
    /**
     * Get a translation for a PDF
     *
     * @param string $string
     * @param array|null $sprintf
     *
     * @return string
     */
    public static function get_pdf_translation($string, $sprintf = null)
    {
        global $_LANGPDF;
        $iso = Context::get_context()->language->iso_code;
        if (!Validate::is_lang_iso_code($iso)) {
            Tools::display_error(sprintf('Invalid iso lang (%s)', Tools::safe_output($iso)));
        }
        $override_i18n_file = _PS_THEME_DIR_ . 'pdf/lang/' . $iso . '.php';
        $i18n_file = _PS_TRANSLATIONS_DIR_ . $iso . '/pdf.php';
        if (file_exists($override_i18n_file)) {
            $i18n_file = $override_i18n_file;
        }
        if (!include $i18n_file) {
            Tools::display_error(sprintf('Cannot include PDF translation language file : %s', $i18n_file));
        }
        if (!isset($_LANGPDF) || !is_array($_LANGPDF)) {
            return str_replace('"', '&quot;', $string);
        }
        $string = preg_replace("/\\\\*'/", "\\'", $string);
        $key = 'PDF' . md5((string) $string);
        $str = array_key_exists($key, $_LANGPDF) && $_LANGPDF[$key] !== '' ? $_LANGPDF[$key] : $string;
        if ($sprintf !== null) {
            return static::check_and_replace_args($str, $sprintf);
        }
        return $str;
    }
    /**
     * Compatibility method that just calls postProcessTranslation.
     *
     * @deprecated 1.0.0 renamed this to postProcessTranslation, since it is not only used in relation to smarty.
     */
    public static function smarty_post_process_translation($string, $params)
    {
        return static::post_process_translation($string, $params);
    }
    /**
     * Perform operations on translations after everything is escaped and before displaying it
     *
     * @param string $string
     *
     * @return string
     */
    public static function post_process_translation($string, array $params)
    {
        // If tags were explicitely provided, we want to use them *after* the translation string is escaped.
        if (!empty($params['tags'])) {
            foreach ($params['tags'] as $index => $tag) {
                // Make positions start at 1 so that it behaves similar to the %1$d etc. sprintf positional params
                $position = $index + 1;
                // extract tag name
                $match = [];
                if (preg_match('/^\s*<\s*(\w+)/', (string) $tag, $match)) {
                    $opener = $tag;
                    $closer = '</' . $match[1] . '>';
                    $string = str_replace('[' . $position . ']', $opener, $string);
                    $string = str_replace('[/' . $position . ']', $closer, $string);
                    $string = str_replace('[' . $position . '/]', $opener . $closer, $string);
                }
            }
        }
        return $string;
    }
    /**
     * Helper function to make calls to postProcessTranslation more readable.
     *
     * @param string $string
     * @param array $tags
     *
     * @return string
     */
    public static function pp_tags($string, $tags)
    {
        return static::post_process_translation($string, ['tags' => $tags]);
    }
    /**
     * Get a translation for a front office
     *
     * @param string $input
     * @param string $source template file name
     * @param array $sprintf
     * @param bool $js
     *
     * @return string
     */
    public static function get_front_translation($input, string $source, $sprintf = null, $js = false)
    {
        global $_LANG;
        $string = str_replace('\'', '\\\'', $input);
        $key = $source . '_' . md5($string);
        if ($_LANG != null && isset($_LANG[$key]) && $_LANG[$key] !== '') {
            $msg = $_LANG[$key];
        } elseif ($_LANG != null && isset($_LANG[mb_strtolower($key)]) && $_LANG[mb_strtolower($key)] !== '') {
            $msg = $_LANG[mb_strtolower($key)];
        } else {
            $msg = $input;
        }
        $msg = $js ? addslashes((string) $msg) : stripslashes((string) $msg);
        if ($sprintf !== null) {
            $msg = static::check_and_replace_args($msg, $sprintf);
        }
        return $js ? $msg : Tools::safe_output($msg);
    }
    /**
     * Performs front office template translations
     *
     * This method is called when {l s='xxx'} is used in front office templates
     *
     * @param Smarty_Internal_Template $smarty
     * @return string
     */
    public static function smarty_front_translate(array $params, $smarty)
    {
        if (!isset($params['js'])) {
            $params['js'] = false;
        }
        if (!isset($params['pdf'])) {
            $params['pdf'] = false;
        }
        if (!isset($params['mod'])) {
            $params['mod'] = false;
        }
        if (!isset($params['sprintf'])) {
            $params['sprintf'] = null;
        }
        $filename = $smarty->template_resource;
        $basename = basename((string) $filename, '.tpl');
        if ($params['mod']) {
            return static::post_process_translation(static::get_module_translation($params['mod'], $params['s'], $basename, $params['sprintf'], $params['js']), $params);
        }
        if ($params['pdf']) {
            return static::post_process_translation(static::get_pdf_translation($params['s'], $params['sprintf']), $params);
        }
        if (isset($smarty->source) && str_contains((string) $smarty->source->filepath, DIRECTORY_SEPARATOR . 'override' . DIRECTORY_SEPARATOR)) {
            $basename = 'override_' . $basename;
        }
        return static::post_process_translation(static::get_front_translation($params['s'], $basename, $params['sprintf'], $params['js']), $params);
    }
    /**
     * Performs back office template translations
     *
     * This method is called when {l s='xxx'} is used in back office templates
     *
     * @param Smarty_Internal_Template $smarty
     * @return string
     */
    public static function smarty_admin_translate(array $params, $smarty)
    {
        $htmlentities = !isset($params['js']);
        $pdf = isset($params['pdf']);
        $addslashes = isset($params['slashes']) || isset($params['js']);
        $sprintf = $params['sprintf'] ?? null;
        if ($pdf) {
            return static::post_process_translation(Translate::get_pdf_translation($params['s'], $sprintf), $params);
        }
        $filename = $smarty->template_resource;
        // If the template is part of a module
        if (!empty($params['mod'])) {
            return static::post_process_translation(static::get_module_translation($params['mod'], $params['s'], basename((string) $filename, '.tpl'), $sprintf, isset($params['js'])), $params);
        }
        // If the tpl is at the root of the template folder
        if (dirname((string) $filename) == '.') {
            $class = 'index';
        }
        if (!empty(Context::get_context()->override_controller_name_for_translations)) {
            $class = Context::get_context()->override_controller_name_for_translations;
        } elseif (isset(Context::get_context()->controller)) {
            $class_name = Context::get_context()->controller::class;
            $class = substr($class_name, 0, strpos(strtolower($class_name), 'controller'));
        } else {
            // Split by \ and / to get the folder tree for the file
            $folder_tree = preg_split('#[/\\\\]#', (string) $filename);
            $key = array_search('controllers', $folder_tree);
            // If there was a match, construct the class name using the child folder name
            // Eg. xxx/controllers/customers/xxx => AdminCustomers
            if ($key !== false) {
                $class = 'Admin' . Tools::to_camel_case($folder_tree[$key + 1], true);
            } elseif (isset($folder_tree[0])) {
                $class = 'Admin' . Tools::to_camel_case($folder_tree[0], true);
            }
        }
        return static::post_process_translation(Translate::get_admin_translation($params['s'], $class, $addslashes, $htmlentities, $sprintf), $params);
    }
}