<?php

declare (strict_types=1);
use Guzzle_Http\Client;
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
 * Class LanguageCore
 */
class Language_Core extends Object_Model
{
    public const LANG_CODE_IN_URL_WHEN_MULTI_LANGUAGES = 0;
    public const LANG_CODE_IN_URL_ALWAYS = 1;
    public const LANG_CODE_IN_URL_FOR_NON_DEFAULT_LANGUAGES = 2;
    /** @var array Languages cache */
    protected static $_checked_langs;
    /**
     * @var array[]
     */
    protected static $_LANGUAGES;
    /**
     * @var int[]
     */
    protected static $count_active_languages = [];
    /**
     * @var int|null
     */
    protected static $_cache_language_installation;
    /**
     * @var string Name
     */
    public $name;
    /**
     * @var string 2-letter iso code
     */
    public $iso_code;
    /**
     * @var string 5-letter iso code
     */
    public $language_code;
    /**
     * @var string friendly url code
     */
    public $url_code;
    /**
     * @var string date format http://http://php.net/manual/en/function.date.php with the date only
     */
    public $date_format_lite = 'Y-m-d';
    /**
     * @var string date format http://http://php.net/manual/en/function.date.php with hours and minutes
     */
    public $date_format_full = 'Y-m-d H:i:s';
    /**
     * @var bool true if this language is right to left language
     */
    public $is_rtl = false;
    /**
     * @var bool Status
     */
    public $active = true;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'lang', 'primary' => 'id_lang', 'fields' => ['name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32], 'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(3) unsigned', 'dbDefault' => '0'], 'iso_code' => ['type' => self::TYPE_STRING, 'validate' => 'isLanguageIsoCode', 'required' => true, 'size' => 2, 'dbType' => 'char(2)'], 'language_code' => ['type' => self::TYPE_STRING, 'validate' => 'isLanguageCode', 'size' => 5, 'dbType' => 'char(5)', 'dbNullable' => false], 'url_code' => ['type' => self::TYPE_STRING, 'validate' => 'isLinkRewrite', 'size' => 40, 'dbNullable' => true], 'date_format_lite' => ['type' => self::TYPE_STRING, 'validate' => 'isPhpDateFormat', 'required' => true, 'size' => 32, 'dbType' => 'char(32)', 'dbDefault' => 'Y-m-d'], 'date_format_full' => ['type' => self::TYPE_STRING, 'validate' => 'isPhpDateFormat', 'required' => true, 'size' => 32, 'dbType' => 'char(32)', 'dbDefault' => 'Y-m-d H:i:s'], 'is_rtl' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0']], 'keys' => ['lang' => ['lang_iso_code' => ['type' => Object_Model::KEY, 'columns' => ['iso_code']]], 'lang_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectNodeName' => 'language', 'objectsNodeName' => 'languages'];
    /**
     * @var string[]
     */
    protected $translations_files_and_vars = ['fields' => '_FIELDS', 'errors' => '_ERRORS', 'admin' => '_LANGADM', 'pdf' => '_LANGPDF', 'tabs' => 'tabs'];
    /**
     * LanguageCore constructor.
     *
     * @param int|null $id
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null)
    {
        parent::__construct($id);
    }
    /**
     * Returns an array of language IDs
     *
     * @param bool $active Select only active languages
     * @param int|bool $idShop Shop ID
     *
     * @return int[]
     *
     * @throws PrestaShopException
     */
    public static function get_i_ds($active = true, $id_shop = false)
    {
        return static::get_languages($active, $id_shop, true);
    }
    /**
     * Returns available languages
     *
     * @param bool $active Select only active languages
     * @param int|bool $idShop Shop ID
     * @param bool $idsOnly If true, returns an array of language IDs
     *
     * @return array Languages
     *
     * @throws PrestaShopException
     */
    public static function get_languages($active = true, $id_shop = false, $ids_only = false)
    {
        if (!static::$_LANGUAGES) {
            Language::load_languages();
        }
        $languages = [];
        foreach (static::$_LANGUAGES as $language) {
            if ($active && !$language['active']) {
                continue;
            }
            if ($id_shop && !isset($language['shops'][(int) $id_shop])) {
                continue;
            }
            $languages[] = $ids_only ? (int) $language['id_lang'] : $language;
        }
        return $languages;
    }
    /**
     * Load all languages in memory for caching
     *
     * @throws PrestaShopException
     */
    public static function load_languages(): void
    {
        static::$_LANGUAGES = [];
        $result = Db::read_only()->get_array((new Db_Query())->select('l.*, ls.`id_shop`')->from('lang', 'l')->left_join('lang_shop', 'ls', 'l.`id_lang` = ls.`id_lang`'));
        foreach ($result as $row) {
            if (!isset(static::$_LANGUAGES[(int) $row['id_lang']])) {
                static::$_LANGUAGES[(int) $row['id_lang']] = $row;
            }
            static::$_LANGUAGES[(int) $row['id_lang']]['shops'][(int) $row['id_shop']] = true;
        }
    }
    /**
     * @param int $idLang
     *
     * @return array|false
     */
    public static function get_language($id_lang)
    {
        if (!isset(static::$_LANGUAGES[$id_lang])) {
            return false;
        }
        return static::$_LANGUAGES[(int) $id_lang];
    }
    /**
     * @param string $isoCode
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public static function get_language_code_by_iso($iso_code)
    {
        if (!Validate::is_language_iso_code($iso_code)) {
            throw new Presta_Shop_Exception(Tools::display_error('Fatal error: ISO code is not correct') . ' ' . Tools::safe_output($iso_code));
        }
        return Db::read_only()->get_value((new Db_Query())->select('`language_code`')->from('lang')->where('`iso_code` = \'' . p_sql(strtolower($iso_code)) . '\''));
    }
    /**
     * @param string $code
     *
     * @return bool|Language
     *
     * @throws PrestaShopException
     */
    public static function get_language_by_ietf_code($code)
    {
        if (!Validate::is_language_code($code)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Fatal error: IETF code %s is not correct'), Tools::safe_output($code)));
        }
        // $code is in the form of 'xx-YY' where xx is the language code
        // and 'YY' a country code identifying a variant of the language.
        $lang_country = explode('-', $code);
        // Get the language component of the code
        $lang = $lang_country[0];
        // Find the id_lang of the language.
        // We look for anything with the correct language code
        // and sort on equality with the exact IETF code wanted.
        // That way using only one query we get either the exact wanted language
        // or a close match.
        $id_lang = Db::read_only()->get_value((new Db_Query())->select('`id_lang`, IF(language_code = \'' . p_sql($code) . '\', 0, LENGTH(language_code)) as found')->from('lang')->where('LEFT(`language_code`, 2) = \'' . p_sql($lang) . '\'')->order_by('`found` ASC'));
        // Instantiate the Language object if we found it.
        if ($id_lang) {
            return new Language($id_lang);
        }
        return false;
    }
    /**
     * Return array (id_lang, iso_code)
     *
     * @param bool $active
     *
     * @return array Language (id_lang, iso_code)
     * @throws PrestaShopException
     */
    public static function get_iso_ids($active = true)
    {
        return Db::read_only()->get_array((new Db_Query())->select('`id_lang`, `iso_code`')->from('lang')->where($active ? '`active` = 1' : ''));
    }
    /**
     * @param string $from
     * @param string $to
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function copy_language_data($from, $to)
    {
        $conn = Db::get_instance();
        $result = $conn->get_array('SHOW TABLES FROM `' . _DB_NAME_ . '`');
        foreach ($result as $row) {
            if (preg_match('/_lang/', (string) $row['Tables_in_' . _DB_NAME_]) && $row['Tables_in_' . _DB_NAME_] != _DB_PREFIX_ . 'lang') {
                $result2 = $conn->get_array((new Db_Query())->select('*')->from(bq_sql($row['Tables_in_' . _DB_NAME_]))->where('`id_lang` = ' . (int) $from));
                if (!count($result2)) {
                    continue;
                }
                $conn->delete(b_qsql($row['Tables_in_' . _DB_NAME_]), '`id_lang` = ' . (int) $to);
                $query = 'INSERT INTO `' . $row['Tables_in_' . _DB_NAME_] . '` VALUES ';
                foreach ($result2 as $row2) {
                    $query .= '(';
                    $row2['id_lang'] = $to;
                    foreach ($row2 as $field) {
                        $query .= !is_string($field) && $field == null ? 'NULL,' : '\'' . p_sql($field, true) . '\',';
                    }
                    $query = rtrim($query, ',') . '),';
                }
                $query = rtrim($query, ',');
                $conn->execute($query);
            }
        }
        return true;
    }
    /**
     * @param string $iso_code
     *
     * @return bool|mixed
     *
     * @throws PrestaShopException
     */
    public static function is_installed($iso_code)
    {
        if (static::$_cache_language_installation === null) {
            static::$_cache_language_installation = [];
            $result = Db::read_only()->get_array((new Db_Query())->select('`id_lang`, `iso_code`')->from('lang'));
            foreach ($result as $row) {
                static::$_cache_language_installation[$row['iso_code']] = $row['id_lang'];
            }
        }
        return static::$_cache_language_installation[$iso_code] ?? false;
    }
    /**
     * Check if more on than one language is activated
     *
     * @param int $idShop
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_multi_language_activated($id_shop = null)
    {
        return Language::count_active_languages($id_shop) > 1;
    }
    /**
     * @param int $idShop
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function count_active_languages($id_shop = null)
    {
        if (isset(Context::get_context()->shop) && is_object(Context::get_context()->shop) && $id_shop === null) {
            $id_shop = (int) Context::get_context()->shop->id;
        }
        if (!isset(static::$count_active_languages[$id_shop])) {
            static::$count_active_languages[$id_shop] = Db::read_only()->get_value((new Db_Query())->select('COUNT(DISTINCT l.`id_lang`)')->from('lang', 'l')->inner_join('lang_shop', 'ls', 'ls.`id_lang` = l.`id_lang`')->where('ls.`id_shop` = ' . (int) $id_shop)->where('l.`active` = 1'));
        }
        return static::$count_active_languages[$id_shop];
    }
    /**
     * @throws PrestaShopException
     */
    public static function update_modules_translations(array $modules_list): void
    {
        $languages = Language::get_languages(false);
        clearstatcache();
        foreach ($languages as $lang) {
            $file_path = _PS_TRANSLATIONS_DIR_ . $lang['iso_code'] . '.gzip';
            if (@filemtime($file_path) < time() - 24 * 3600) {
                Language::download_and_install_language_pack($lang['iso_code'], null, null, false);
            }
            if (@file_exists($file_path)) {
                $gz = new Archive_Tar($file_path, true);
                $file_list = array_column(Language::get_language_pack_list_content($lang['iso_code'], $gz), 'filename');
                if ($file_list) {
                    $files_to_extract = [];
                    foreach ($modules_list as $module_name) {
                        $module_file_list = array_filter($file_list, fn($file) => str_contains((string) $file, 'modules/' . $module_name . '/'));
                        $files_to_extract = array_merge($files_to_extract, $module_file_list);
                    }
                    if ($files_to_extract) {
                        $gz->extract_list($files_to_extract, _PS_TRANSLATIONS_DIR_ . '../', '');
                    }
                }
            }
        }
    }
    /**
     * @param string $iso
     * @param string|null $version
     * @param array|null $params
     * @param bool $install
     *
     * @return array|bool
     *
     * @throws PrestaShopException
     */
    public static function download_and_install_language_pack($iso, $version = null, $params = null, $install = true)
    {
        if (!Validate::is_language_iso_code((string) $iso)) {
            return false;
        }
        if ($version == null) {
            $version = _TB_VERSION_;
        }
        $version = implode('.', array_map(intval(...), explode('.', $version, 3)));
        $lang_pack = false;
        $errors = [];
        $file = _PS_TRANSLATIONS_DIR_ . $iso . '.gzip';
        $base_uri = "https://translations.thirtybees.com/packs/{$version}/";
        $guzzle = new Client(['base_uri' => $base_uri, 'timeout' => 20, 'verify' => Configuration::get_ssl_trust_store()]);
        try {
            $lang_pack_link = (string) $guzzle->get("{$iso}.json")->get_body();
        } catch (Throwable $e) {
            $lang_pack_link = false;
            $errors[] = Tools::display_error('Language pack cannot be downloaded from thirtybees.com.');
            $errors[] = sprintf(Tools::display_error('Downloading %s failed (PHP message: %s).'), $base_uri . "{$iso}.json", $e->get_message());
        }
        if (!count($errors)) {
            if (!$lang_pack = json_decode($lang_pack_link)) {
                $errors[] = Tools::display_error('Error occurred when language was checked according to your thirty bees version.');
            } elseif (!static::check_and_add_language($iso, $lang_pack, false, $params)) {
                $errors[] = sprintf(Tools::display_error('An error occurred while creating the language: %s'), $iso);
            }
        }
        if (!Language::get_id_by_iso($iso, true)) {
            return $errors;
        }
        $success = false;
        if (isset($lang_pack->name)) {
            try {
                $guzzle->get("{$iso}.gzip", ['sink' => $file]);
                $success = true;
            } catch (Throwable $e) {
                $errors[] = Tools::display_error('No translations pack available for your version.');
                $errors[] = sprintf(Tools::display_error('Downloading %s failed (PHP message: %s).'), $base_uri . "{$iso}.gzip", $e->get_message());
            }
            if ($success && !@file_exists($file)) {
                if (!is_writable($file)) {
                    $errors[] = sprintf(Tools::display_error('Server does not have permissions for writing %s.'), $file);
                }
            }
        }
        if ($success && $install) {
            $gz = new Archive_Tar($file, true);
            $file_list = Admin_Translations_Controller::filter_translation_files(Language::get_language_pack_list_content((string) $iso, $gz));
            $file_paths = Admin_Translations_Controller::files_list_to_paths($file_list);
            $i = 0;
            $tmp_array = [];
            foreach ($file_paths as $file_path) {
                $path = dirname($file_path);
                if (is_dir(_PS_TRANSLATIONS_DIR_ . '../' . $path) && !is_writable(_PS_TRANSLATIONS_DIR_ . '../' . $path) && !in_array($path, $tmp_array)) {
                    $errors[] = (!$i++ ? Tools::display_error('Translation pack cannot be extracted.') . ' ' : '') . Tools::display_error('The server does not have permissions for writing.') . ' ' . sprintf(Tools::display_error('Please check rights for %s'), $path);
                    $tmp_array[] = $path;
                }
            }
            if (!$gz->extract_list($file_paths, _PS_TRANSLATIONS_DIR_ . '../')) {
                $errors[] = sprintf(Tools::display_error('Cannot decompress the translation file for the following language: %s'), (string) $iso);
            }
            // Clear smarty modules cache
            Tools::clear_cache();
            // Reset cache
            Language::load_languages();
            Admin_Translations_Controller::check_and_add_mails_files((string) $iso, $file_list);
            Admin_Translations_Controller::add_new_tabs((string) $iso, $file_list);
        }
        return count($errors) ? $errors : true;
    }
    /**
     * @param string $iso
     * @param Archive_Tar $tar
     *
     * @return array
     */
    public static function get_language_pack_list_content($iso, $tar)
    {
        $key = 'Language::getLanguagePackListContent_' . $iso;
        if (!Cache::is_stored($key)) {
            if (!$tar instanceof Archive_Tar) {
                return [];
            }
            $result = $tar->list_content();
            if (is_array($result)) {
                Cache::store($key, $result);
                return $result;
            }
            return [];
        }
        return Cache::retrieve($key);
    }
    /**
     * @param string $isoCode
     * @param Language|bool $langPack
     * @param bool $onlyAdd
     * @param array|null $paramsLang
     *
     * @throws PrestaShopException
     * @return bool
     */
    public static function check_and_add_language($iso_code, $lang_pack = false, $only_add = false, $params_lang = null)
    {
        if (!Validate::is_language_iso_code($iso_code)) {
            return false;
        }
        if (Language::get_id_by_iso($iso_code, true)) {
            return true;
        }
        // Initialize the language
        $lang = new Language();
        $lang->iso_code = mb_strtolower($iso_code);
        $lang->language_code = $iso_code;
        // Rewritten afterwards if the language code is available
        $lang->active = true;
        // If the language pack has not been provided, retrieve it from translations.thirtybees.com
        if (!$lang_pack) {
            $version = implode('.', array_map(intval(...), explode('.', _TB_VERSION_, 3)));
            $guzzle = new Client(['base_uri' => "https://translations.thirtybees.com/packs/{$version}/", 'timeout' => 20, 'verify' => Configuration::get_ssl_trust_store()]);
            try {
                $lower_iso = mb_strtolower($iso_code);
                $lang_pack = json_decode((string) $guzzle->get("{$lower_iso}.json")->get_body());
            } catch (Throwable) {
                $lang_pack = false;
            }
        }
        // If a language pack has been found or provided, prefill the language object with the value
        if ($lang_pack) {
            foreach (get_object_vars($lang_pack) as $key => $value) {
                if ($key != 'iso_code' && isset(Language::$definition['fields'][$key])) {
                    $lang->{$key} = $value;
                }
            }
        }
        // Use the values given in parameters to override the data retrieved automatically
        if (is_array($params_lang)) {
            foreach ($params_lang as $key => $value) {
                if ($key != 'iso_code' && isset(Language::$definition['fields'][$key])) {
                    $lang->{$key} = $value;
                }
            }
        }
        if (!$lang->name && $lang->iso_code) {
            $lang->name = $lang->iso_code;
        }
        if (!$lang->validate_fields() || !$lang->validate_fields_lang() || !$lang->add(true, false, $only_add)) {
            return false;
        }
        if (isset($params_lang['allow_accented_chars_url']) && in_array($params_lang['allow_accented_chars_url'], ['1', 'true'])) {
            Configuration::update_global_value('PS_ALLOW_ACCENTED_CHARS_URL', 1);
        }
        Language::_copy_none_flag((int) $lang->id);
        if (Language::copy_default_image($lang->iso_code)) {
            Language::regenerate_default_images($lang->iso_code);
        }
        return true;
    }
    /**
     * Return id from iso code
     *
     * @param string $isoCode Iso code
     * @param bool $noCache
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_iso($iso_code, $no_cache = false)
    {
        if (!Validate::is_language_iso_code($iso_code)) {
            throw new Presta_Shop_Exception(Tools::display_error('Fatal error: ISO code is not correct') . ' ' . Tools::safe_output($iso_code));
        }
        $key = 'Language::getIdByIso_' . $iso_code;
        if ($no_cache || !Cache::is_stored($key)) {
            $id_lang = Db::read_only()->get_value((new Db_Query())->select('`id_lang`')->from('lang')->where('`iso_code` = \'' . p_sql($iso_code) . '\''));
            Cache::store($key, $id_lang);
            return $id_lang;
        }
        return Cache::retrieve($key);
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     * @param bool $onlyAdd
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false, $only_add = false)
    {
        if (!parent::add($auto_date, $null_values)) {
            return false;
        }
        if ($only_add) {
            return true;
        }
        // create empty files if they not exists
        $this->_generate_files();
        // Set default language routes
        Configuration::update_value('PS_ROUTE_product_rule', [$this->id => '{categories:/}{rewrite}']);
        Configuration::update_value('PS_ROUTE_category_rule', [$this->id => '{rewrite}']);
        Configuration::update_value('PS_ROUTE_layered_rule', [$this->id => '{categories:/}{rewrite}{/:selected_filters}']);
        Configuration::update_value('PS_ROUTE_supplier_rule', [$this->id => '{rewrite}']);
        Configuration::update_value('PS_ROUTE_manufacturer_rule', [$this->id => '{rewrite}']);
        Configuration::update_value('PS_ROUTE_cms_rule', [$this->id => 'info/{categories:/}{rewrite}']);
        Configuration::update_value('PS_ROUTE_cms_category_rule', [$this->id => 'info/{categories:/}{rewrite}']);
        $this->load_update_sql();
        return true;
    }
    /**
     * Generate translations files
     *
     * @param string $newIso
     */
    protected function _generate_files($new_iso = null)
    {
        $iso_code = $new_iso ?: $this->iso_code;
        if (!file_exists(_PS_TRANSLATIONS_DIR_ . $iso_code)) {
            if (@mkdir(_PS_TRANSLATIONS_DIR_ . $iso_code)) {
                @chmod(_PS_TRANSLATIONS_DIR_ . $iso_code, 0777);
            }
        }
        foreach ($this->translations_files_and_vars as $file => $var) {
            $path_file = _PS_TRANSLATIONS_DIR_ . $iso_code . '/' . $file . '.php';
            if (!file_exists($path_file)) {
                if ($file != 'tabs') {
                    @file_put_contents($path_file, '<?php
	global $' . $var . ';
	$' . $var . ' = array();
?>');
                } else {
                    @file_put_contents($path_file, '<?php
	$' . $var . ' = array();
	return $' . $var . ';
?>');
                }
            }
            @chmod($path_file, 0777);
        }
    }
    /**
     * loadUpdateSQL will create default lang values when you create a new lang, based on default id lang
     *
     * @return bool true if succeed
     *
     * @throws PrestaShopException
     */
    public function load_update_sql()
    {
        $connection = Db::get_instance();
        $tables = $connection->get_array('SHOW TABLES LIKE \'' . str_replace('_', '\_', _DB_PREFIX_) . '%\_lang\' ');
        $lang_tables = [];
        $ignored_tables = [_DB_PREFIX_ . 'configuration_lang', _DB_PREFIX_ . 'configuration_kpi_lang'];
        foreach ($tables as $table) {
            foreach ($table as $t) {
                if (!in_array($t, $ignored_tables)) {
                    $lang_tables[] = $t;
                }
            }
        }
        $return = true;
        $shops = Shop::get_shops_collection(false);
        foreach ($shops as $shop) {
            /** @var Shop $shop */
            $id_lang_default = Configuration::get('PS_LANG_DEFAULT', null, $shop->id_shop_group, $shop->id);
            foreach ($lang_tables as $name) {
                preg_match('#^' . preg_quote(_DB_PREFIX_) . '(.+)_lang$#i', (string) $name, $m);
                $identifier = 'id_' . $m[1];
                $fields = '';
                // We will check if the table contains a column "id_shop"
                // If yes, we will add "id_shop" as a WHERE condition in queries copying data from default language
                $shop_field_exists = $primary_key_exists = false;
                $columns = $connection->get_array('SHOW COLUMNS FROM `' . $name . '`');
                foreach ($columns as $column) {
                    $fields .= '`' . $column['Field'] . '`, ';
                    if ($column['Field'] == 'id_shop') {
                        $shop_field_exists = true;
                    }
                    if ($column['Field'] == $identifier) {
                        $primary_key_exists = true;
                    }
                }
                $fields = rtrim($fields, ', ');
                if (!$primary_key_exists) {
                    continue;
                }
                $sql = 'INSERT IGNORE INTO `' . $name . '` (' . $fields . ') (SELECT ';
                // For each column, copy data from default language
                reset($columns);
                foreach ($columns as $column) {
                    if ($identifier != $column['Field'] && $column['Field'] != 'id_lang') {
                        $sql .= '(
							SELECT `' . bq_sql($column['Field']) . '`
							FROM `' . bq_sql($name) . '` tl
							WHERE tl.`id_lang` = ' . (int) $id_lang_default . '
							' . ($shop_field_exists ? ' AND tl.`id_shop` = ' . (int) $shop->id : '') . '
							AND tl.`' . bq_sql($identifier) . '` = `' . bq_sql(str_replace('_lang', '', $name)) . '`.`' . bq_sql($identifier) . '`
						),';
                    } else {
                        $sql .= '`' . bq_sql($column['Field']) . '`,';
                    }
                }
                $sql = rtrim($sql, ', ');
                $sql .= ' FROM `' . _DB_PREFIX_ . 'lang` CROSS JOIN `' . bq_sql(str_replace('_lang', '', $name)) . '`)';
                $return = $connection->execute($sql) && $return;
            }
        }
        return $return;
    }
    /**
     * @param int $id
     * @param string|null $iso @Deprecated
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function _copy_none_flag($id, $iso = null)
    {
        $id = (int) $id;
        if ($id) {
            $image_extension = Configuration::get('TB_IMAGE_EXTENSION') ?: 'jpg';
            $target = _PS_LANG_IMG_DIR_ . $id . '.' . $image_extension;
            static::load_languages();
            $language = Language::get_language($id);
            if ($language) {
                $language_code = (string) $language['language_code'];
                if (preg_match('/^[a-zA-Z]{2}-([a-zA-Z]{2})$/', $language_code, $matches)) {
                    $country_code = strtolower($matches[1]);
                    $source = Image_Manager::get_source_image(_PS_IMG_DIR_ . '/flags/', strtolower($country_code), 'png');
                    if (file_exists($source)) {
                        return Image_Manager::convert_image_to_extension($source, $image_extension, $target);
                    }
                }
                return copy(_PS_LANG_IMG_DIR_ . 'none.jpg', $target);
            }
        }
        return false;
    }
    /**
     * @param string $isoCode
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function copy_default_image($iso_code)
    {
        $image_extension = Image_Manager::get_default_image_extension();
        $new_image_dest = _PS_LANG_IMG_DIR_ . $iso_code . '-default.' . $image_extension;
        // This is only relevant for installation
        if (defined('_PS_INSTALL_LANGS_PATH_') && $source_image = Image_Manager::get_source_image(_PS_INSTALL_LANGS_PATH_ . $iso_code . '/img/', $iso_code, 'jpg')) {
            return Image_Manager::convert_image_to_extension($source_image, $image_extension, $new_image_dest);
        }
        // We haven't found the original default image of this language (it only works on installation)
        // Now: we copy the default image of the default language
        $id_lang = Configuration::get('PS_LANG_DEFAULT');
        if ($source_image = Image_Manager::get_source_image(_PS_LANG_IMG_DIR_, Language::get_iso_by_id($id_lang) . '-default')) {
            return Image_Manager::convert_image_to_extension($source_image, $image_extension, $new_image_dest);
        }
        // We still haven't found any default image (shouldn't happen often)
        // Now: we try to find any default image
        foreach (Language::get_languages() as $language) {
            if ($source_image = Image_Manager::get_source_image(_PS_LANG_IMG_DIR_, $language['iso_code'] . '-default')) {
                return Image_Manager::convert_image_to_extension($source_image, $image_extension, $new_image_dest);
            }
        }
        return false;
    }
    /**
     * Saves all default images in all types and resolutions in l folder
     *
     * @param string $iso_code
     * @param string $imageExtension|null image extension
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function regenerate_default_images($iso_code, $image_extension = null)
    {
        $success = true;
        if ($source_image = Image_Manager::get_source_image(_PS_LANG_IMG_DIR_, $iso_code . '-default')) {
            if (!$image_extension) {
                $image_extension = Image_Manager::get_default_image_extension();
            }
            foreach (Image_Type::get_images_types() as $image_type) {
                $dst_file = _PS_LANG_IMG_DIR_ . $iso_code . '-default-' . $image_type['name'] . '.' . $image_extension;
                $success = Image_Manager::resize($source_image, $dst_file, $image_type['width'], $image_type['height'], $image_extension) && $success;
                $dst_file = _PS_LANG_IMG_DIR_ . $iso_code . '-default-' . $image_type['name'] . '2x.' . $image_extension;
                $success = Image_Manager::resize($source_image, $dst_file, $image_type['width'] * 2, $image_type['height'] * 2, $image_extension) && $success;
            }
        } else {
            $success = false;
        }
        return $success;
    }
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_fields()
    {
        $this->iso_code = strtolower($this->iso_code);
        if (empty($this->language_code)) {
            $this->language_code = $this->iso_code;
        }
        return parent::get_fields();
    }
    /**
     * Move translations files after editing language iso code
     *
     * @param string $newIso
     * @throws PrestaShopException
     */
    public function move_to_iso($new_iso): void
    {
        if ($new_iso == $this->iso_code) {
            return;
        }
        if (file_exists(_PS_TRANSLATIONS_DIR_ . $this->iso_code)) {
            rename(_PS_TRANSLATIONS_DIR_ . $this->iso_code, _PS_TRANSLATIONS_DIR_ . $new_iso);
        }
        if (file_exists(_PS_MAIL_DIR_ . $this->iso_code)) {
            rename(_PS_MAIL_DIR_ . $this->iso_code, _PS_MAIL_DIR_ . $new_iso);
        }
        $modules_list = Module::get_modules_dir_on_disk();
        foreach ($modules_list as $module_dir) {
            if (file_exists(_PS_MODULE_DIR_ . $module_dir . '/mails/' . $this->iso_code)) {
                rename(_PS_MODULE_DIR_ . $module_dir . '/mails/' . $this->iso_code, _PS_MODULE_DIR_ . $module_dir . '/mails/' . $new_iso);
            }
            if (file_exists(_PS_MODULE_DIR_ . $module_dir . '/' . $this->iso_code . '.php')) {
                rename(_PS_MODULE_DIR_ . $module_dir . '/' . $this->iso_code . '.php', _PS_MODULE_DIR_ . $module_dir . '/' . $new_iso . '.php');
            }
        }
        foreach (Theme::get_themes() as $theme) {
            /** @var Theme $theme */
            $theme_dir = $theme->directory;
            if (file_exists(_PS_ALL_THEMES_DIR_ . $theme_dir . '/lang/' . $this->iso_code . '.php')) {
                rename(_PS_ALL_THEMES_DIR_ . $theme_dir . '/lang/' . $this->iso_code . '.php', _PS_ALL_THEMES_DIR_ . $theme_dir . '/lang/' . $new_iso . '.php');
            }
            if (file_exists(_PS_ALL_THEMES_DIR_ . $theme_dir . '/mails/' . $this->iso_code)) {
                rename(_PS_ALL_THEMES_DIR_ . $theme_dir . '/mails/' . $this->iso_code, _PS_ALL_THEMES_DIR_ . $theme_dir . '/mails/' . $new_iso);
            }
            foreach ($modules_list as $module) {
                if (file_exists(_PS_ALL_THEMES_DIR_ . $theme_dir . '/modules/' . $module . '/' . $this->iso_code . '.php')) {
                    rename(_PS_ALL_THEMES_DIR_ . $theme_dir . '/modules/' . $module . '/' . $this->iso_code . '.php', _PS_ALL_THEMES_DIR_ . $theme_dir . '/modules/' . $module . '/' . $new_iso . '.php');
                }
            }
        }
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function check_files()
    {
        return Language::check_files_with_iso_code($this->iso_code);
    }
    /**
     * This functions checks if every files exists for the language $iso_code.
     * Concerned files are those located in translations/$iso_code/
     * and translations/mails/$iso_code .
     *
     * @param string $isoCode
     *
     * @return bool true if all files exists
     *
     * @throws PrestaShopException
     */
    public static function check_files_with_iso_code($iso_code)
    {
        if (isset(static::$_checked_langs[$iso_code]) && static::$_checked_langs[$iso_code]) {
            return true;
        }
        foreach (array_keys(Language::get_files_list($iso_code, _THEME_NAME_, false, false, false, true)) as $key) {
            if (!file_exists($key)) {
                return false;
            }
        }
        static::$_checked_langs[$iso_code] = true;
        return true;
    }
    /**
     * @param string $isoFrom
     * @param string $themeFrom
     * @param bool|string $isoTo
     * @param bool|string $themeTo
     * @param bool $select
     * @param bool $check
     * @param bool $modules
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function get_files_list($iso_from, $theme_from, $iso_to = false, $theme_to = false, $select = false, $check = false, $modules = false)
    {
        if (empty($iso_from)) {
            throw new Presta_Shop_Exception('getFilesList: $isoFrom not provided');
        }
        $iso_from = (string) $iso_from;
        $copy = $iso_to && $theme_to;
        $l_path_from = _PS_TRANSLATIONS_DIR_ . $iso_from . '/';
        $t_path_from = _PS_ROOT_DIR_ . '/themes/' . $theme_from . '/';
        $p_path_from = _PS_ROOT_DIR_ . '/themes/' . $theme_from . '/pdf/';
        $m_path_from = _PS_MAIL_DIR_ . $iso_from . '/';
        if ($copy) {
            $l_path_to = _PS_TRANSLATIONS_DIR_ . $iso_to . '/';
            $t_path_to = _PS_ROOT_DIR_ . '/themes/' . $theme_to . '/';
            $p_path_to = _PS_ROOT_DIR_ . '/themes/' . $theme_to . '/pdf/';
            $m_path_to = _PS_MAIL_DIR_ . $iso_to . '/';
        }
        $l_files = ['admin.php', 'errors.php', 'fields.php', 'pdf.php', 'tabs.php'];
        // Added natives mails files
        $m_files = ['account.html', 'account.txt', 'backoffice_order.html', 'backoffice_order.txt', 'bankwire.html', 'bankwire.txt', 'contact.html', 'contact.txt', 'contact_form.html', 'contact_form.txt', 'credit_slip.html', 'credit_slip.txt', 'download_product.html', 'download_product.txt', 'employee_password.html', 'employee_password.txt', 'forward_msg.html', 'forward_msg.txt', 'guest_to_customer.html', 'guest_to_customer.txt', 'in_transit.html', 'in_transit.txt', 'log_alert.html', 'log_alert.txt', 'newsletter.html', 'newsletter.txt', 'order_canceled.html', 'order_canceled.txt', 'order_conf.html', 'order_conf.txt', 'order_customer_comment.html', 'order_customer_comment.txt', 'order_merchant_comment.html', 'order_merchant_comment.txt', 'order_return_state.html', 'order_return_state.txt', 'outofstock.html', 'outofstock.txt', 'password.html', 'password.txt', 'password_query.html', 'password_query.txt', 'payment.html', 'payment.txt', 'payment_error.html', 'payment_error.txt', 'preparation.html', 'preparation.txt', 'refund.html', 'refund.txt', 'reply_msg.html', 'reply_msg.txt', 'shipped.html', 'shipped.txt', 'test.html', 'test.txt', 'voucher.html', 'voucher.txt', 'voucher_new.html', 'voucher_new.txt', 'order_changed.html', 'order_changed.txt'];
        $number = -1;
        $files = [];
        $files_tr = [];
        $files_theme = [];
        $files_mail = [];
        $files_modules = [];
        // When a copy is made from a theme in specific language
        // to an other theme for the same language,
        // it's avoid to copy Translations, Mails files
        // and modules files which are not override by theme.
        if (!$copy || $iso_from != $iso_to) {
            // Translations files
            if (!$check || $iso_from != 'en') {
                foreach ($l_files as $file) {
                    $files_tr[$l_path_from . $file] = $copy ? $l_path_to . $file : ++$number;
                }
            }
            if ($select == 'tr') {
                return $files_tr;
            }
            $files = array_merge($files, $files_tr);
            // Mail files
            if (!$check || $iso_from != 'en') {
                $files_mail[$m_path_from . 'lang.php'] = $copy ? $m_path_to . 'lang.php' : ++$number;
            }
            foreach ($m_files as $file) {
                $files_mail[$m_path_from . $file] = $copy ? $m_path_to . $file : ++$number;
            }
            if ($select == 'mail') {
                return $files_mail;
            }
            $files = array_merge($files, $files_mail);
            // Modules
            if ($modules) {
                $mod_list = Module::get_modules_dir_on_disk();
                foreach ($mod_list as $mod) {
                    $mod_dir = _PS_MODULE_DIR_ . $mod;
                    // Lang file
                    if (file_exists($mod_dir . '/translations/' . $iso_from . '.php')) {
                        $files_modules[$mod_dir . '/translations/' . $iso_from . '.php'] = $copy ? $mod_dir . '/translations/' . $iso_to . '.php' : ++$number;
                    } elseif (file_exists($mod_dir . '/' . $iso_from . '.php')) {
                        $files_modules[$mod_dir . '/' . $iso_from . '.php'] = $copy ? $mod_dir . '/' . $iso_to . '.php' : ++$number;
                    }
                    // Mails files
                    $mod_mail_dir_from = $mod_dir . '/mails/' . $iso_from;
                    $mod_mail_dir_to = $mod_dir . '/mails/' . $iso_to;
                    if (file_exists($mod_mail_dir_from)) {
                        $dir_files = scandir($mod_mail_dir_from);
                        foreach ($dir_files as $file) {
                            if (file_exists($mod_mail_dir_from . '/' . $file) && $file != '.' && $file != '..' && $file != '.svn') {
                                $files_modules[$mod_mail_dir_from . '/' . $file] = $copy ? $mod_mail_dir_to . '/' . $file : ++$number;
                            }
                        }
                    }
                }
                if ($select == 'modules') {
                    return $files_modules;
                }
                $files = array_merge($files, $files_modules);
            }
        } elseif ($select == 'mail' || $select == 'tr') {
            return $files;
        }
        // Theme files
        if (!$check || $iso_from != 'en') {
            $files_theme[$t_path_from . 'lang/' . $iso_from . '.php'] = $copy ? $t_path_to . 'lang/' . $iso_to . '.php' : ++$number;
            // Override for pdf files in the theme
            if (file_exists($p_path_from . 'lang/' . $iso_from . '.php')) {
                $files_theme[$p_path_from . 'lang/' . $iso_from . '.php'] = $copy ? $p_path_to . 'lang/' . $iso_to . '.php' : ++$number;
            }
            $module_theme_files = file_exists($t_path_from . 'modules/') ? scandir($t_path_from . 'modules/') : [];
            foreach ($module_theme_files as $module) {
                if ($module !== '.' && $module != '..' && $module !== '.svn' && file_exists($t_path_from . 'modules/' . $module . '/translations/' . $iso_from . '.php')) {
                    $files_theme[$t_path_from . 'modules/' . $module . '/translations/' . $iso_from . '.php'] = $copy ? $t_path_to . 'modules/' . $module . '/translations/' . $iso_to . '.php' : ++$number;
                }
            }
        }
        if ($select == 'theme') {
            return $files_theme;
        }
        // Return
        return array_merge($files, $files_theme);
    }
    /**
     * @param array $selection
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_selection($selection)
    {
        if (!is_array($selection)) {
            return false;
        }
        $result = true;
        foreach ($selection as $id) {
            $language = new Language($id);
            $result = $result && $language->delete();
        }
        return $result;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (!$this->has_multishop_entries() || Shop::get_context() == Shop::CONTEXT_ALL) {
            if (empty($this->iso_code)) {
                $this->iso_code = Language::get_iso_by_id($this->id);
            }
            // Database translations deletion
            $result = Db::read_only()->get_array('SHOW TABLES FROM `' . _DB_NAME_ . '`');
            $conn = Db::get_instance();
            $table_name_key = 'Tables_in_' . _DB_NAME_;
            foreach ($result as $row) {
                if (empty($row[$table_name_key])) {
                    continue;
                }
                if (!preg_match('/_lang$/', (string) $row[$table_name_key])) {
                    continue;
                }
                $columns = Db::read_only()->get_array('SHOW COLUMNS FROM `' . $row[$table_name_key] . '`');
                $id_lang_column_exists = false;
                foreach ($columns as $column) {
                    if ($column['Field'] == 'id_lang') {
                        $id_lang_column_exists = true;
                    }
                }
                if ($id_lang_column_exists === false) {
                    continue;
                }
                $conn->delete(bq_sql($row[$table_name_key]), '`id_lang` = ' . (int) $this->id);
            }
            // Delete tags
            $conn->delete('tag', '`id_lang` = ' . (int) $this->id);
            // Delete search words
            $conn->delete('search_word', '`id_lang` = ' . (int) $this->id);
            // Files deletion
            foreach (Language::get_files_list($this->iso_code, _THEME_NAME_, false, false, false, true, true) as $key => $file) {
                if (file_exists($key)) {
                    unlink($key);
                }
            }
            $mod_list = scandir(_PS_MODULE_DIR_);
            foreach ($mod_list as $mod) {
                $module_dir = _PS_MODULE_DIR_ . $mod;
                if (file_exists($module_dir) && is_dir($module_dir)) {
                    $module_mails_dir = $module_dir . '/mails/';
                    if (file_exists($module_mails_dir) && is_dir($module_mails_dir)) {
                        Tools::delete_directory($module_mails_dir . $this->iso_code);
                        if (Tools::is_directory_empty($module_mails_dir)) {
                            Tools::delete_directory($module_mails_dir);
                        }
                    }
                    if (file_exists($module_dir . '/' . $this->iso_code . '.php')) {
                        unlink($module_dir . '/' . $this->iso_code . '.php');
                    }
                    if (Tools::is_directory_empty($module_dir)) {
                        Tools::delete_directory($module_dir);
                    }
                }
            }
            if (file_exists(_PS_MAIL_DIR_ . $this->iso_code)) {
                Tools::delete_directory(_PS_MAIL_DIR_ . $this->iso_code);
            }
            if (file_exists(_PS_TRANSLATIONS_DIR_ . $this->iso_code)) {
                Tools::delete_directory(_PS_TRANSLATIONS_DIR_ . $this->iso_code);
            }
            $link = new Link();
            $images = [];
            foreach (Image_Manager::get_allowed_image_extensions(true, true) as $image_extension) {
                $images[] = _PS_LANG_IMG_DIR_ . $this->id . '.' . $image_extension;
                // Flag
                // Adding all possible default images
                $images[] = _PS_LANG_IMG_DIR_ . $this->iso_code . '-default.' . $image_extension;
                foreach (Image_Type::get_images_types() as $image_type) {
                    $images[] = $link->get_default_image_uri($this->iso_code, $image_type['name'], false, $image_extension, true);
                    $images[] = $link->get_default_image_uri($this->iso_code, $image_type['name'], true, $image_extension, true);
                }
            }
            $images = array_unique($images);
            foreach ($images as $image) {
                if (file_exists($image)) {
                    unlink($image);
                }
            }
        }
        if (!parent::delete()) {
            return false;
        }
        return true;
    }
    /**
     * Return iso code from id
     *
     * @param int $idLang Language ID
     *
     * @return string Iso code
     *
     * @throws PrestaShopException
     */
    public static function get_iso_by_id($id_lang)
    {
        if (!static::$_LANGUAGES) {
            static::load_languages();
        }
        return static::$_LANGUAGES[(int) $id_lang]['iso_code'] ?? false;
    }
    /**
     * @param string $dir
     *
     * @return bool
     */
    public static function recurse_delete_dir($dir)
    {
        Tools::display_as_deprecated();
        return Tools::delete_directory($dir);
    }
    /**
     * Return an array of theme
     *
     * @return array([theme dir] => array('name' => [theme name]))
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    protected function _get_themes_list()
    {
        Tools::display_as_deprecated();
        static $themes = [];
        if (empty($themes)) {
            $installed_themes = Theme::get_themes();
            foreach ($installed_themes as $theme) {
                /** @var Theme $theme */
                $themes[$theme->directory] = ['name' => $theme->name];
            }
        }
        return $themes;
    }
    public function get_url_code(): string
    {
        $url_code = (string) $this->url_code;
        if ($url_code) {
            return $url_code;
        }
        return (string) $this->iso_code;
    }
    /**
     *
     *
     * @throws PrestaShopException
     */
    public static function get_url_code_by_id(int $language_id): string
    {
        if (!static::$_LANGUAGES) {
            static::load_languages();
        }
        if (isset(static::$_LANGUAGES[$language_id])) {
            $language = new Language();
            $language->hydrate(static::$_LANGUAGES[$language_id]);
            return $language->get_url_code();
        }
        return '';
    }
}