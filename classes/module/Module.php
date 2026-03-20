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
use Guzzle_Http\Client;
use Guzzle_Http\Promise\Utils;
use Thirtybees\Core\Dependency_Injection\Service_Locator;
use Thirtybees\Core\Error\Error_Utils;
/**
 * Class ModuleCore
 */
abstract class Module_Core
{
    public const MODULES_CACHE_FILE = _PS_CACHE_DIR_ . 'api.thirtybees.com.modules.json';
    public const LAST_MODULES_CHECK = 'TB_LAST_MODULES_CHECK';
    public const MODULES_CHECK_INTERVAL = 'TB_MODULES_CHECK_INTERVAL';
    public const CACHE_FILE_TAB_MODULES_LIST = '/config/xml/tab_modules_list.xml';
    /** @var array used by AdminTab to determine which lang file to use (admin.php or module lang file) */
    public static $class_in_module = [];
    /** @var bool Define if we will log modules performances for this session */
    public static $_log_modules_perfs;
    /** @var array $hosted_modules_blacklist */
    public static $hosted_modules_blacklist = ['autoupgrade'];
    /** @var bool Random session for modules perfs logs */
    public static $_log_modules_perfs_session;
    /** @var array Array cache filled with modules informations */
    protected static ?array $modules_cache;
    /** @var array Array cache filled with modules instances */
    protected static $_INSTANCE = [];
    /** @var bool Config xml generation mode */
    protected static $_generate_config_xml_mode = false;
    /** @var array Array filled with cache translations */
    protected static $l_cache = [];
    /** @var array Array filled with cache permissions (modules / employee profiles) */
    protected static $cache_permissions = [];
    /** @var bool $update_translations_after_install */
    protected static $update_translations_after_install = true;
    /** @var bool $_batch_mode */
    protected static $_batch_mode = false;
    /** @var array $_defered_clearCache */
    protected static $_defered_clear_cache = [];
    /** @var array $_defered_func_call */
    protected static $_defered_func_call = [];
    /** @var int Module ID */
    public $id;
    /** @var string $version Version */
    public $version;
    /** @var string $database_version */
    public $database_version;
    /** @var string Registered Version in database */
    public $registered_version;
    /** @var array filled with known compliant PrestaShop versions */
    public $ps_versions_compliancy = [];
    /**
     * @var string Filled with known compliant thirty bees versions
     *             This string contains a SemVer 1.0.0 range
     */
    public $tb_versions_compliancy = '*';
    /** @var string minimal version of thirty bees compliant with this module */
    public $tb_min_version = '1.0.0';
    /** @var array filled with modules needed for install */
    public $dependencies = [];
    /** @var string Unique name */
    public $name;
    /** @var string Human name */
    public $display_name;
    /** @var string A little description of the module */
    public $description;
    /** @var string author of the module */
    public $author;
    /** @var string URI author of the module */
    public $author_uri = '';
    /** @var string Module key */
    public $module_key = '';
    /** @var string $description_full */
    public $description_full;
    /** @var string $additional_description */
    public $additional_description;
    /** @var string $compatibility */
    public $compatibility;
    /** @var int $nb_rates */
    public $nb_rates;
    /** @var float $avg_rate */
    public $avg_rate;
    /** @var array $badges */
    public $badges;
    /** @var bool need_instance */
    public $need_instance = true;
    /** @var string Admin tab corresponding to the module */
    public $tab;
    /** @var bool Status */
    public $active = false;
    /** @var string Fill it if the module is installed but not yet set up */
    public $warning;
    /** @var int $enable_device */
    public $enable_device = 7;
    /** @var array to store the limited country */
    public $limited_countries = [];
    /** @var array names of the controllers */
    public $controllers = [];
    /**
     * @var bool $bootstrap
     *
     * Indicates whether the module's configuration page supports bootstrap
     */
    public $bootstrap = false;
    /** @var array current language translations */
    protected $_lang = [];
    /** @var string Module web path (eg. '/shop/modules/modulename/') */
    protected $_path;
    /** @var string Module local path (eg. '/home/prestashop/modules/modulename/') */
    protected $local_path;
    /** @var array Array filled with module errors */
    protected $_errors = [];
    /** @var array Array  array filled with module success */
    protected $_confirmations = [];
    /** @var string Main table used for modules installed */
    protected $table = 'module';
    /** @var string Identifier of the main table */
    protected $identifier = 'id_module';
    /** @var Context */
    protected $context;
    /** @var Smarty_Data */
    protected $smarty;
    /** @var Smarty_Internal_Template|null */
    protected $current_subtemplate;
    /** @var bool $installed */
    public $installed;
    /** @var string */
    public $confirm_uninstall = '';
    /**
     * Constructor
     *
     * @param string|null $name Module unique name
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($name = null, ?Context $context = null)
    {
        if (isset($this->ps_versions_compliancy) && !isset($this->ps_versions_compliancy['min'])) {
            $this->ps_versions_compliancy['min'] = '1.4.0.0';
        }
        if (isset($this->ps_versions_compliancy) && !isset($this->ps_versions_compliancy['max'])) {
            $this->ps_versions_compliancy['max'] = _PS_VERSION_;
        }
        if (strlen((string) $this->ps_versions_compliancy['min']) == 3) {
            $this->ps_versions_compliancy['min'] .= '.0.0';
        }
        if (strlen((string) $this->ps_versions_compliancy['max']) == 3) {
            $this->ps_versions_compliancy['max'] .= '.999.999';
        }
        // Load context and smarty
        $this->context = $context ?: Context::get_context();
        if (is_object($this->context->smarty)) {
            $this->smarty = $this->context->smarty->create_data($this->context->smarty);
        }
        // If the module has no name we gave him its id as name
        if ($this->name === null) {
            $this->name = $this->id;
        }
        // If the module has the name we load the corresponding data from the cache
        if ($this->name != null) {
            // If cache is not generated, we generate it
            if (static::$modules_cache == null && !is_array(static::$modules_cache)) {
                $id_shop = Validate::is_loaded_object($this->context->shop) ? $this->context->shop->id : Configuration::get('PS_SHOP_DEFAULT');
                static::$modules_cache = [];
                // Join clause is done to check if the module is activated in current shop context
                $result = Db::read_only()->get_array((new Db_Query())->select('m.`id_module`, m.`name`, ms.`id_module` AS `mshop`')->from('module', 'm')->left_join('module_shop', 'ms', 'ms.`id_module` = m.`id_module` AND ms.`id_shop` = ' . (int) $id_shop));
                foreach ($result as $row) {
                    static::$modules_cache[$row['name']] = $row;
                    static::$modules_cache[$row['name']]['active'] = $row['mshop'] > 0 ? 1 : 0;
                }
            }
            // We load configuration from the cache
            if (isset(static::$modules_cache[$this->name])) {
                if (isset(static::$modules_cache[$this->name]['id_module'])) {
                    $this->id = static::$modules_cache[$this->name]['id_module'];
                }
                foreach (static::$modules_cache[$this->name] as $key => $value) {
                    if (property_exists($this, $key)) {
                        $this->{$key} = $value;
                    }
                }
                $this->_path = __PS_BASE_URI__ . 'modules/' . $this->name . '/';
            }
            if (!$this->context->controller instanceof Controller) {
                static::$modules_cache = null;
            }
            $this->local_path = _PS_MODULE_DIR_ . $this->name . '/';
        }
    }
    /**
     * @return bool
     */
    public static function get_batch_mode()
    {
        return static::$_batch_mode;
    }
    /**
     * Set the flag to indicate we are doing an import
     *
     * @param bool $value
     */
    public static function set_batch_mode($value): void
    {
        static::$_batch_mode = (bool) $value;
    }
    public static function process_defered_func_call(): void
    {
        static::set_batch_mode(false);
        foreach (static::$_defered_func_call as $func_call) {
            call_user_func_array($func_call[0], $func_call[1]);
        }
        static::$_defered_func_call = [];
    }
    /**
     * Clear the caches stored in $_defered_clearCache
     *
     * @throws PrestaShopException
     */
    public static function process_defered_clear_cache(): void
    {
        static::set_batch_mode(false);
        foreach (static::$_defered_clear_cache as $clear_cache_array) {
            static::_defered_clear_cache($clear_cache_array[0], $clear_cache_array[1], $clear_cache_array[2]);
        }
        static::$_defered_clear_cache = [];
    }
    /**
     * Clear deferred template cache
     *
     * @param string $templatePath Template path
     * @param int|null $cacheId
     * @param int|null $compileId
     *
     * @return int Number of template cleared
     *
     * @throws PrestaShopException
     */
    public static function _defered_clear_cache($template_path, $cache_id, $compile_id)
    {
        Tools::enable_cache();
        $number_of_template_cleared = Tools::clear_cache(Context::get_context()->smarty, $template_path, $cache_id, $compile_id);
        Tools::restore_cache_settings();
        return $number_of_template_cleared;
    }
    /**
     * @param bool $update
     */
    public static function update_translations_after_install($update = true): void
    {
        Module::$update_translations_after_install = (bool) $update;
    }
    /**
     * Init the upgrade module
     *
     * @param stdClass $module
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function init_upgrade_module($module)
    {
        if ((int) $module->installed == 1 & empty($module->database_version) === true) {
            Module::upgrade_module_version($module->name, $module->version);
            $module->database_version = $module->version;
        }
        // Init cache upgrade details
        static::$modules_cache[$module->name]['upgrade'] = [
            'success' => false,
            // bool to know if upgrade succeed or not
            'available_upgrade' => 0,
            // Number of available module before any upgrade
            'number_upgraded' => 0,
            // Number of upgrade done
            'number_upgrade_left' => 0,
            'upgrade_file_left' => [],
            // List of the upgrade file left
            'version_fail' => 0,
            // Version of the upgrade failure
            'upgraded_from' => 0,
            // Version number before upgrading anything
            'upgraded_to' => 0,
        ];
        // Need Upgrade will check and load upgrade file to the moduleCache upgrade case detail
        $ret = $module->installed && Module::need_upgrade($module);
        return $ret;
    }
    /**
     * Upgrade the registered version to a new one
     *
     * @param string $name
     * @param string $version
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function upgrade_module_version($name, $version)
    {
        return Db::get_instance()->update('module', ['version' => p_sql($version)], '`name` = \'' . p_sql($name) . '\'');
    }
    /**
     * Check if a module need to be upgraded.
     * This method modify the module_cache adding an upgrade list file
     *
     * @param stdClass $module
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function need_upgrade($module)
    {
        static::$modules_cache[$module->name]['upgrade']['upgraded_from'] = $module->database_version;
        // Check the version of the module with the registered one and look if any upgrade file exist
        if (Tools::version_compare($module->version, $module->database_version, '>')) {
            $old_version = $module->database_version;
            $module = Module::get_instance_by_name($module->name);
            if ($module instanceof Module) {
                return $module->load_upgrade_version_list($module->name, $module->version, $old_version);
            }
        }
        return null;
    }
    /**
     * Return an instance of the specified module
     *
     * @param string $moduleName Module name
     *
     * @return Module|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_instance_by_name($module_name)
    {
        if (!Validate::is_module_name($module_name)) {
            trigger_error(Tools::display_error(Tools::safe_output($module_name) . ' is not a valid module name.'), E_USER_NOTICE);
            return false;
        }
        $class_name = strtolower($module_name);
        if (!isset(static::$_INSTANCE[$class_name])) {
            $module = static::module_exists_on_filesystem($module_name) ? Module::core_load_module($module_name) : false;
            static::$_INSTANCE[$class_name] = $module;
        }
        return static::$_INSTANCE[$class_name];
    }
    /**
     * @param string $moduleName
     *
     * @return Module
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function core_load_module($module_name)
    {
        // Define if we will log modules performances for this session
        if (Module::$_log_modules_perfs === null) {
            $modulo = _PS_DEBUG_PROFILING_ ? 1 : Configuration::get('PS_log_modules_perfs_MODULO');
            Module::$_log_modules_perfs = $modulo && mt_rand(0, $modulo - 1) == 0;
            if (Module::$_log_modules_perfs) {
                Module::$_log_modules_perfs_session = mt_rand();
            }
        }
        // Store time and memory before and after hook call and save the result in the database
        if (Module::$_log_modules_perfs) {
            $time_start = microtime(true);
            $memory_start = memory_get_usage(true);
        }
        $module = static::instantiate_module($module_name);
        if (Module::$_log_modules_perfs) {
            $time_end = microtime(true);
            $memory_end = memory_get_usage(true);
            Db::get_instance()->insert('modules_perfs', ['session' => (int) Module::$_log_modules_perfs_session, 'module' => p_sql($module_name), 'method' => '__construct', 'time_start' => p_sql($time_start), 'time_end' => p_sql($time_end), 'memory_start' => $memory_start, 'memory_end' => $memory_end]);
        }
        return $module;
    }
    /**
     *
     * @return Module
     * @throws PrestaShopException
     */
    protected static function instantiate_module(string $module_name)
    {
        if (!class_exists($module_name, false)) {
            include_once _PS_MODULE_DIR_ . $module_name . '/' . $module_name . '.php';
        }
        if (Tools::file_exists_no_cache(_PS_OVERRIDE_DIR_ . 'modules/' . $module_name . '/' . $module_name . '.php')) {
            include_once _PS_OVERRIDE_DIR_ . 'modules/' . $module_name . '/' . $module_name . '.php';
            $override = $module_name . 'Override';
            if (class_exists($override, false)) {
                return Adapter_service_Locator::get($override);
            }
        }
        if (class_exists($module_name, false)) {
            return Adapter_service_Locator::get($module_name);
        }
        throw new Presta_Shop_Exception("Failed to instantiate module '{$module_name}'");
    }
    /**
     * Load the available list of upgrade of a specified module
     * with an associated version
     *
     * @param string $moduleVersion
     * @param string $registeredVersion
     *
     * @return bool to know directly if any files have been found
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function load_upgrade_version_list(string $module_name, $module_version, $registered_version)
    {
        $list = [];
        $upgrade_path = _PS_MODULE_DIR_ . $module_name . '/upgrade/';
        // Check if folder exist and it could be read
        if (file_exists($upgrade_path) && $files = scandir($upgrade_path)) {
            // Read each file name
            foreach ($files as $file) {
                if (!in_array($file, ['.', '..', '.svn', 'index.php']) && preg_match('/\.php$/', $file)) {
                    $tab = explode('-', $file);
                    if (!isset($tab[1])) {
                        continue;
                    }
                    $file_version = basename($tab[1], '.php');
                    // Compare version, if minor than actual, we need to upgrade the module
                    if (count($tab) == 2 && (Tools::version_compare($file_version, $module_version, '<=') && Tools::version_compare($file_version, $registered_version, '>'))) {
                        $list[] = ['file' => $upgrade_path . $file, 'version' => $file_version, 'upgrade_function' => ['upgrade_module_' . str_replace('.', '_', $file_version), 'upgradeModule' . str_replace('.', '', $file_version)]];
                    }
                }
            }
        }
        // No files upgrade, then upgrade succeed
        if (count($list) == 0) {
            static::$modules_cache[$module_name]['upgrade']['success'] = true;
            Module::upgrade_module_version($module_name, $module_version);
        }
        usort($list, ps_module_version_sort(...));
        // Set the list to module cache
        static::$modules_cache[$module_name]['upgrade']['upgrade_file_left'] = $list;
        static::$modules_cache[$module_name]['upgrade']['available_upgrade'] = count($list);
        return (bool) count($list);
    }
    /**
     * Return the status of the upgraded module
     *
     * @param string $moduleName
     *
     * @return bool
     */
    public static function get_upgrade_status($module_name)
    {
        return isset(static::$modules_cache[$module_name]) && static::$modules_cache[$module_name]['upgrade']['success'];
    }
    /**
     * This function enable module $name. If an $name is an array,
     * this will enable all of them
     *
     * @param array|string $name
     *
     * @return true if succeed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function enable_by_name($name)
    {
        // If $name is not an array, we set it as an array
        if (!is_array($name)) {
            $name = [$name];
        }
        $res = true;
        // Enable each module
        foreach ($name as $n) {
            if (Validate::is_module_name($n)) {
                $res = Module::get_instance_by_name($n)->enable() && $res;
            }
        }
        return $res;
    }
    /**
     * This function disable module $name. If an $name is an array,
     * this will disable all of them
     *
     * @param array|string $name
     *
     * @return true
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function disable_by_name($name)
    {
        // If $name is not an array, we set it as an array
        if (!is_array($name)) {
            $name = [$name];
        }
        // Disable each module
        foreach ($name as $n) {
            if (Validate::is_module_name($n)) {
                Module::get_instance_by_name($n)->disable();
            }
        }
        return true;
    }
    /**
     * This function is used to determine the module name
     * of an AdminTab which belongs to a module, in order to keep translation
     * related to a module in its directory (instead of $_LANGADM)
     *
     * @param string $currentClass
     *
     * @return string|false if the class belongs to a module, will return the module name. Otherwise, return false.
     */
    public static function get_module_name_from_class($current_class)
    {
        // check if class file is inside module
        if (!isset(static::$class_in_module[$current_class])) {
            $module_name = false;
            if (class_exists($current_class)) {
                $reflection_class = new ReflectionClass($current_class);
                $file_path = realpath($reflection_class->get_file_name());
                $realpath_module_dir = realpath(_PS_MODULE_DIR_);
                if (str_starts_with($file_path, $realpath_module_dir)) {
                    $module_relative_path = trim(substr($file_path, strlen($realpath_module_dir)), '/\\');
                    if (preg_match('/^([a-zA-Z0-9_-]+)/', $module_relative_path, $matches)) {
                        $module_name = $matches[1];
                    }
                }
            }
            static::$class_in_module[$current_class] = $module_name;
        }
        // return name of the module, or false
        return static::$class_in_module[$current_class];
    }
    /**
     * Return an instance of the specified module
     *
     * @param int $idModule Module ID
     *
     * @return false|Module instance
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_instance_by_id($id_module)
    {
        static $id2name = null;
        if (is_null($id2name)) {
            $id2name = [];
            if ($results = Db::read_only()->get_array((new Db_Query())->select('`id_module`, `name`')->from('module'))) {
                foreach ($results as $row) {
                    $id2name[$row['id_module']] = $row['name'];
                }
            }
        }
        if (isset($id2name[$id_module])) {
            return Module::get_instance_by_name($id2name[$id_module]);
        }
        return false;
    }
    /**
     * @return string
     */
    public static function get_module_name(string $module)
    {
        $iso = substr((string) Context::get_context()->language->iso_code, 0, 2);
        // Config file
        $config_file = _PS_MODULE_DIR_ . $module . '/config_' . $iso . '.xml';
        // For "en" iso code, we keep the default config.xml name
        if ($iso == 'en' || !file_exists($config_file)) {
            $config_file = _PS_MODULE_DIR_ . $module . '/config.xml';
            if (!file_exists($config_file)) {
                return 'Module ' . ucfirst($module);
            }
        }
        // Load config.xml
        libxml_use_internal_errors(true);
        $xml_module = @simplexml_load_file($config_file);
        if (!$xml_module) {
            return 'Module ' . ucfirst($module);
        }
        if (!empty(libxml_get_errors())) {
            libxml_clear_errors();
            return 'Module ' . ucfirst($module);
        }
        libxml_clear_errors();
        // Return Name
        return Translate::get_module_translation((string) $xml_module->name, Module::config_xml_string_format($xml_module->display_name), (string) $xml_module->name);
    }
    /**
     * @param string $string
     *
     * @return string
     */
    public static function config_xml_string_format($string)
    {
        return Tools::htmlentities_decode_utf8($string);
    }
    /**
     * Return available modules
     *
     * @param bool $useConfig in order to use config.xml file in module dir
     * @param bool $loggedOnAddons
     * @param int|bool $idEmployee
     *
     * @return stdClass[] Modules
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_modules_on_disk($use_config = false, $logged_on_addons = false, $id_employee = false)
    {
        // Init var
        $module_list = [];
        $module_name_list = [];
        $modules_name_to_cursor = [];
        $errors = [];
        // Get modules directory list and memory limit
        $modules_dir = Module::get_modules_dir_on_disk();
        $modules_installed = [];
        $conn = Db::read_only();
        $result = $conn->get_array((new Db_Query())->select('m.`name`, m.`version`, mp.`interest`, module_shop.`enable_device`')->from('module', 'm')->join(Shop::add_sql_association('module', 'm'))->left_join('module_preference', 'mp', 'mp.`module` = m.`name` AND mp.`id_employee` = ' . (int) $id_employee));
        foreach ($result as $row) {
            $modules_installed[$row['name']] = $row;
        }
        foreach ($modules_dir as $module) {
            if (Module::use_too_much_memory()) {
                $errors[] = Tools::display_error('All modules cannot be loaded due to memory limit restrictions, please increase your memory_limit value on your server configuration');
                break;
            }
            $iso = substr((string) Context::get_context()->language->iso_code, 0, 2);
            // Check if config.xml module file exists and if it's not outdated
            if ($iso == 'en') {
                $config_file = _PS_MODULE_DIR_ . $module . '/config.xml';
            } else {
                $config_file = _PS_MODULE_DIR_ . $module . '/config_' . $iso . '.xml';
            }
            $xml_exist = file_exists($config_file);
            $need_new_config_file = !$xml_exist || @filemtime($config_file) < @filemtime(_PS_MODULE_DIR_ . $module . '/' . $module . '.php');
            // If config.xml exists and that the use config flag is at true
            if ($use_config && $xml_exist && !$need_new_config_file) {
                // Load config.xml
                libxml_use_internal_errors(true);
                $xml_module = @simplexml_load_file($config_file);
                if (!$xml_module) {
                    $errors[] = Tools::display_error(sprintf('%1s could not be loaded.', $config_file));
                    break;
                }
                foreach (libxml_get_errors() as $error) {
                    $errors[] = '[' . $module . '] ' . Tools::display_error('Error found in config file:') . ' ' . htmlentities($error->message);
                }
                libxml_clear_errors();
                // If no errors in Xml, no need instance and no need new config.xml file, we load only translations
                if (!count($errors) && (int) $xml_module->need_instance == 0) {
                    $item = ['id' => 0, 'warning' => '', 'active' => 0, 'onclick_option' => false, 'premium' => false, 'img' => '', 'displayName' => stripslashes(Translate::get_module_translation((string) $xml_module->name, Module::config_xml_string_format($xml_module->display_name), (string) $xml_module->name)), 'description' => stripslashes(Translate::get_module_translation((string) $xml_module->name, Module::config_xml_string_format($xml_module->description), (string) $xml_module->name)), 'author' => stripslashes(Translate::get_module_translation((string) $xml_module->name, Module::config_xml_string_format($xml_module->author), (string) $xml_module->name)), 'author_uri' => isset($xml_module->author_uri) && $xml_module->author_uri ? stripslashes($xml_module->author_uri) : false, 'canInstall' => true];
                    foreach ($xml_module as $k => $v) {
                        $item[$k] = (string) $v;
                    }
                    if (isset($xml_module->confirm_uninstall)) {
                        $item['confirmUninstall'] = Translate::get_module_translation((string) $xml_module->name, html_entity_decode(Module::config_xml_string_format($xml_module->confirm_uninstall)), (string) $xml_module->name);
                    }
                    $item = (object) $item;
                    $module_list[] = $item;
                    $module_name_list[] = '\'' . p_sql($item->name) . '\'';
                    $modules_name_to_cursor[mb_strtolower(strval($item->name))] = $item;
                }
            }
            // If use config flag is at false or config.xml does not exist OR need instance OR need a new config.xml file
            if (!$use_config || !$xml_exist || isset($xml_module->need_instance) && (int) $xml_module->need_instance == 1 || $need_new_config_file) {
                // If class does not exists, we include the file
                if (!class_exists($module, false)) {
                    $file_path = _PS_MODULE_DIR_ . $module . '/' . $module . '.php';
                    // Get PHP content, strip unwanted parts.
                    $file = preg_replace([
                        "/^﻿/",
                        // UTF-8 BOM
                        '/^\s*<\?php/',
                        // PHP start tag
                        '/\?>\s*$/',
                        // PHP end tag
                        '/\n[\s\t]*?use\s.*?;/',
                    ], '', file_get_contents($file_path));
                    // replace "namespace {...} " syntax with if(false) {...} to avoid syntax error
                    $file = preg_replace('/\n[\s\t]*?namespace\s*{\s*/', 'if (false) {', (string) $file);
                    // If (false) is a trick to not load the class with "eval".
                    // This way require_once will works correctly
                    if (eval('if (false){	' . $file . "\n" . ' }') !== false) {
                        require_once _PS_MODULE_DIR_ . $module . '/' . $module . '.php';
                    } else {
                        $errors[] = sprintf(Tools::display_error('%1$s (parse error in %2$s)'), $module, substr($file_path, strlen(_PS_ROOT_DIR_)));
                    }
                }
                // If class exists, we just instantiate it
                if (class_exists($module, false)) {
                    /** @var Module $tmpModule */
                    $tmp_module = Adapter_service_Locator::get($module);
                    $item = ['id' => (int) $tmp_module->id, 'warning' => $tmp_module->warning, 'name' => $tmp_module->name, 'version' => $tmp_module->version, 'tab' => $tmp_module->tab, 'displayName' => $tmp_module->display_name, 'description' => stripslashes($tmp_module->description ?? ''), 'author' => $tmp_module->author, 'author_uri' => isset($tmp_module->author_uri) && $tmp_module->author_uri ? $tmp_module->author_uri : false, 'limited_countries' => $tmp_module->limited_countries, 'parent_class' => get_parent_class($module), 'is_configurable' => $tmp_module->is_module_configurable(), 'need_instance' => $tmp_module->need_instance, 'active' => $tmp_module->active, 'currencies' => $tmp_module->currencies ?? null, 'currencies_mode' => $tmp_module->currencies_mode ?? null, 'confirmUninstall' => html_entity_decode((string) $tmp_module->confirm_uninstall), 'description_full' => isset($tmp_module->description_full) ? stripslashes($tmp_module->description_full) : null, 'additional_description' => isset($tmp_module->additional_description) ? stripslashes($tmp_module->additional_description) : null, 'compatibility' => isset($tmp_module->compatibility) ? (array) $tmp_module->compatibility : null, 'nb_rates' => isset($tmp_module->nb_rates) ? (array) $tmp_module->nb_rates : null, 'avg_rate' => isset($tmp_module->avg_rate) ? (array) $tmp_module->avg_rate : null, 'badges' => isset($tmp_module->badges) ? (array) $tmp_module->badges : null, 'url' => $tmp_module->url ?? null, 'premium' => false, 'onclick_option' => method_exists($module, 'onclickOption'), 'canInstall' => true];
                    $item = (object) $item;
                    $module_list[] = $item;
                    $modules_name_to_cursor[mb_strtolower((string) $item->name)] = $item;
                    if (!$xml_exist || $need_new_config_file) {
                        static::$_generate_config_xml_mode = true;
                        $tmp_module->_generate_config_xml();
                        static::$_generate_config_xml_mode = false;
                    }
                    unset($tmp_module);
                } else {
                    $errors[] = sprintf(Tools::display_error('%1$s (class missing in %2$s)'), $module, substr($file_path, strlen(_PS_ROOT_DIR_)));
                }
            }
        }
        // Get modules information from database
        if (!empty($module_name_list)) {
            $list = Shop::get_context_list_shop_id();
            $results = $conn->get_array((new Db_Query())->select('m.`id_module`, m.`name`, (SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'module_shop` ms WHERE m.`id_module` = ms.`id_module` AND ms.`id_shop` IN (' . implode(',', $list) . ')) AS `total`')->from('module', 'm')->where('LOWER(m.`name`) IN (' . mb_strtolower(implode(',', $module_name_list)) . ')'));
            foreach ($results as $result) {
                if (isset($modules_name_to_cursor[mb_strtolower((string) $result['name'])])) {
                    $module_cursor = $modules_name_to_cursor[mb_strtolower((string) $result['name'])];
                    $module_cursor->id = (int) $result['id_module'];
                    $module_cursor->active = $result['total'] == count($list) ? 1 : 0;
                }
            }
        }
        // Get native and partner modules
        $language_code = str_replace('_', '-', mb_strtolower((string) Context::get_context()->language->language_code));
        // This array gets filled with requested module images to download (key = module code, value = guzzle promise)
        $image_promises = [];
        $guzzle = new Client(['verify' => Configuration::get_ssl_trust_store(), 'timeout' => 20]);
        if ($modules = static::get_api_modules_info()) {
            foreach ($modules as $name => $module) {
                if (isset($modules_name_to_cursor[mb_strtolower(strval($name))])) {
                    $module_from_list = $modules_name_to_cursor[mb_strtolower(strval($name))];
                    $module_from_list->premium = $module['premium'] ?? false;
                    if ($module_from_list->can_install && $module_from_list->premium) {
                        $module_from_list->can_install = (bool) $module['binary'];
                    }
                    if ($module_from_list->author && $module_from_list->author === $module['author'] && $module_from_list->version && version_compare($module['version'], $module_from_list->version, '>')) {
                        $module_from_list->version_addons = $module['version'];
                    }
                    $modules_name_to_cursor[mb_strtolower(strval($name))] = $module_from_list;
                    continue;
                }
                $item = ['id' => 0, 'warning' => '', 'type' => 'native', 'name' => $name, 'version' => $module['version'], 'tab' => $module['tab'] ?? 'administration', 'displayName' => $module['displayName'][$language_code] ?? $module['displayName']['en-us'] ?? 'Unknown module', 'description' => $module['description'][$language_code] ?? $module['description']['en-us'] ?? '', 'description_full' => $module['description_full'][$language_code] ?? $module['description_full']['en-us'] ?? '', 'author' => $module['author'] ?? 'thirty bees', 'limited_countries' => [], 'parent_class' => '', 'onclick_option' => false, 'is_configurable' => 0, 'need_instance' => 0, 'not_on_disk' => 1, 'active' => 0, 'premium' => $module['premium'] ?? false, 'canInstall' => (bool) $module['binary'], 'url' => $module['url'] ?? ''];
                if (isset($module['img'])) {
                    if (!file_exists(_PS_TMP_IMG_DIR_ . md5((string) $name) . '.png')) {
                        $image_promises[$name] = $guzzle->get_async($module['img'], ['sink' => _PS_TMP_IMG_DIR_ . md5((string) $name) . '.png']);
                    }
                    $item['image'] = '../img/tmp/' . md5((string) $name) . '.png';
                }
                $module_list[] = (object) $item;
            }
        }
        // Download images simultaneously
        if ($image_promises) {
            Utils::settle($image_promises)->wait();
        }
        foreach ($module_list as &$module) {
            if (isset($modules_installed[$module->name])) {
                $module->installed = true;
                $module->database_version = $modules_installed[$module->name]['version'];
                $module->interest = $modules_installed[$module->name]['interest'];
                $module->enable_device = $modules_installed[$module->name]['enable_device'];
            } else {
                $module->installed = false;
                $module->database_version = 0;
                $module->interest = 0;
            }
        }
        if ($errors) {
            $controller = Context::get_context()->controller;
            if (!isset($controller)) {
                echo '<div class="alert error"><h3>' . Tools::display_error('The following module(s) could not be loaded') . ':</h3><ol>';
                foreach ($errors as $error) {
                    echo '<li>' . $error . '</li>';
                }
                echo '</ol></div>';
            } else {
                foreach ($errors as $error) {
                    $controller->errors[] = $error;
                }
            }
        }
        return $module_list;
    }
    /**
     * Return modules directory list
     *
     * @return array Modules Directory List
     *
     * @throws PrestaShopException
     */
    public static function get_modules_dir_on_disk()
    {
        $module_list = [];
        $modules = scandir(_PS_MODULE_DIR_);
        foreach ($modules as $name) {
            if (is_file(_PS_MODULE_DIR_ . $name)) {
                continue;
            }
            if (static::module_exists_on_filesystem($name)) {
                if (!Validate::is_module_name($name)) {
                    throw new Presta_Shop_Exception(sprintf('Module %s is not a valid module name', $name));
                }
                $module_list[] = $name;
            }
        }
        return $module_list;
    }
    /**
     * @return bool
     */
    protected static function use_too_much_memory()
    {
        $memory_limit = Tools::get_memory_limit();
        if (function_exists('memory_get_usage') && $memory_limit != '-1') {
            $current_memory = memory_get_usage(true);
            $memory_threshold = (int) max($memory_limit * 0.15, Tools::is_x86_64arch() ? 4194304 : 2097152);
            $memory_left = $memory_limit - $current_memory;
            if ($memory_left <= $memory_threshold) {
                return true;
            }
        }
        return false;
    }
    /**
     * @param string $moduleName
     * @return bool
     * @deprecated 1.0.0
     */
    final public static function is_module_trusted($module_name)
    {
        Tools::display_as_deprecated();
        return true;
    }
    /**
     * @return array
     */
    public static function get_native_module_list()
    {
        return require _PS_CONFIG_DIR_ . 'default_modules.php';
    }
    /**
     * Return non native module
     *
     * @return array Modules
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_non_native_module_list()
    {
        $query = (new Db_Query())->select('*')->from('module');
        $native_modules = static::get_native_module_list();
        if ($native_modules) {
            $query->where("`name` NOT IN ('" . implode("', '", array_map(p_sql(...), $native_modules)) . "')");
        }
        return Db::read_only()->get_array($query);
    }
    /**
     * Return a list of modules which are not related to themes. These modules
     * should never get installed, enabled of disabled by a theme installation.
     *
     * @return array Module names.
     */
    public static function get_not_theme_related_modules()
    {
        return [
            // Payment modules.
            'authorizeaim',
            'bankwire',
            'custompayments',
            'ecbexchange',
            'paypal',
            'stripe',
            'vatnumber',
            // Dashboard modules.
            'dashactivity',
            'dashgoals',
            'dashproducts',
            'dashtrends',
            // Analytics and statistics modules.
            'ganalytics',
            'gapi',
            'mailchimp',
            'piwikanalyticsjs',
            'statsdata',
            'statsmodule',
            'trackingfront',
            'collectlogs',
            // Installation maintenance modules.
            'apcumanager',
            'coreupdater',
            'cronjobs',
            'crowdin',
            'donationminer',
            'mdimagemagick',
            'opcachemanager',
            'overridecheck',
            'sitemap',
            'tbcleaner',
        ];
    }
    /**
     * Return installed modules
     *
     * @param int $position Take only positionnables modules
     *
     * @return array Modules
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_modules_installed($position = 0)
    {
        $sql = (new Db_Query())->select('m.*')->from('module', 'm');
        if ($position) {
            $sql->left_join('hook_module', 'hm', 'm.`id_module` = hm.`id_module`');
            $sql->left_join('hook', 'h', 'h.`id_hook` = hm.`id_hook`');
            $sql->where('k.`position` = 1');
            $sql->group_by('m.`id_module`');
        }
        return Db::read_only()->get_array($sql);
    }
    /**
     * Generate XML files for trusted and untrusted modules
     *
     * @return true
     *
     * @deprecated 1.0.0
     */
    final public static function generate_trusted_xml()
    {
        Tools::display_as_deprecated();
        return true;
    }
    /**
     * Create the Addons API call from the module name only
     *
     * @param string $moduleName
     *
     * @return bool Returns if the module is trusted by addons.prestashop.com
     *
     * @deprecated 1.0.0
     */
    final public static function check_module_from_addons_api($module_name)
    {
        Tools::display_as_deprecated();
        return false;
    }
    /**
     * Execute modules for specified hook
     *
     * @param string $hookName Hook Name
     * @param array $hookArgs Parameters for the functions
     * @param int|null $idModule
     *
     * @return string modules output
     *
     * @throws PrestaShopException
     * @deprecated 2.0.0
     */
    public static function hook_exec($hook_name, $hook_args = [], $id_module = null)
    {
        Tools::display_as_deprecated();
        return Hook::display_hook($hook_name, $hook_args, $id_module);
    }
    /**
     * @deprecated 2.0.0
     * @return string
     * @throws PrestaShopException
     */
    public static function hook_exec_payment()
    {
        Tools::display_as_deprecated();
        return Hook::display_hook('displayPayment');
    }
    /**
     * Pre call
     *
     * @param string $moduleName
     *
     * @return bool
     */
    public static function pre_call($module_name)
    {
        return true;
    }
    /**
     * @deprecated 2.0.0
     */
    public static function get_paypal_ignore(): void
    {
        Tools::display_as_deprecated();
    }
    /**
     * Returns the list of the payment module associated to the current customer
     *
     * @see PaymentModule::getInstalledPaymentModules() if you don't care about the context
     *
     * @return array module informations
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_payment_modules()
    {
        $context = Context::get_context();
        if (isset($context->cart)) {
            $billing = new Address((int) $context->cart->id_address_invoice);
        }
        $use_groups = Group::is_feature_active();
        $frontend = true;
        $groups = [];
        if (isset($context->employee)) {
            $frontend = false;
        } elseif (isset($context->customer) && $use_groups) {
            $groups = $context->customer->get_groups();
            if (!count($groups)) {
                $groups = [Configuration::get('PS_UNIDENTIFIED_GROUP')];
            }
        }
        $hook_payment = 'Payment';
        $connection = Db::read_only();
        if ($connection->get_value((new Db_Query())->select('`id_hook`')->from('hook')->where('`name` = \'displayPayment\''))) {
            $hook_payment = 'displayPayment';
        }
        $list = Shop::get_context_list_shop_id();
        return $connection->get_array((new Db_Query())->select('DISTINCT m.`id_module`, h.`id_hook`, m.`name`, hm.`position`')->from('module', 'm')->join($frontend ? 'LEFT JOIN `' . _DB_PREFIX_ . 'module_country` mc ON (m.`id_module` = mc.`id_module` AND mc.id_shop = ' . (int) $context->shop->id . ')' : '')->join($frontend && $use_groups ? 'INNER JOIN `' . _DB_PREFIX_ . 'module_group` mg ON (m.`id_module` = mg.`id_module` AND mg.id_shop = ' . (int) $context->shop->id . ')' : '')->join($frontend && isset($context->customer) && $use_groups ? 'INNER JOIN `' . _DB_PREFIX_ . 'customer_group` cg on (cg.`id_group` = mg.`id_group`AND cg.`id_customer` = ' . (int) $context->customer->id . ')' : '')->left_join('hook_module', 'hm', 'hm.`id_module` = m.`id_module`')->left_join('hook', 'h', 'hm.`id_hook` = h.`id_hook`')->where('h.`name` = \'' . p_sql($hook_payment) . '\'')->where(isset($billing) && $frontend ? 'mc.`id_country` = ' . (int) $billing->id_country : '')->where('(SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'module_shop ms WHERE ms.id_module = m.id_module AND ms.id_shop IN(' . implode(', ', $list) . ')) = ' . count($list))->where('hm.`id_shop` IN(' . implode(', ', $list) . ')')->where(count($groups) && $frontend && $use_groups ? 'mg.`id_group` IN (' . implode(', ', $groups) . ')' : '')->group_by('hm.`id_hook`, hm.`id_module`')->order_by('hm.`position`, m.`name` DESC'));
    }
    /**
     * @param string $name
     * @param string $string
     * @param string $source
     *
     * @return string
     *
     * @deprecated 2.0.0 Use Translate::getModuleTranslation()
     */
    public static function find_translation($name, $string, $source)
    {
        return Translate::get_module_translation($name, $string, $source);
    }
    /**
     *
     * @return bool|null
     * @throws PrestaShopException
     */
    public static function is_enabled(string $module_name)
    {
        if (!Cache::is_stored('Module::isEnabled' . $module_name)) {
            $active = false;
            $id_module = (int) Module::get_module_id_by_name($module_name);
            if ($id_module && Db::read_only()->get_value((new Db_Query())->select('`id_module`')->from('module_shop')->where('`id_module` = ' . $id_module)->where('`id_shop` = ' . (int) Context::get_context()->shop->id))) {
                $active = static::module_exists_on_filesystem($module_name);
            }
            Cache::store('Module::isEnabled' . $module_name, (bool) $active);
            return (bool) $active;
        }
        return Cache::retrieve('Module::isEnabled' . $module_name);
    }
    /**
     * Get Unauthorized modules for a client group
     *
     * @param int $groupId
     *
     * @return array|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_authorized_modules($group_id)
    {
        return Db::read_only()->get_array((new Db_Query())->select('m.`id_module`, m.`name`')->from('module_group', 'mg')->left_join('module', 'm', 'm.`id_module` = mg.`id_module`')->where('mg.`id_group` = ' . (int) $group_id));
    }
    /**
     * Insert module into datable
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        Hook::trigger_event('actionModuleInstallBefore', ['object' => $this]);
        // Check module name validation
        if (!Validate::is_module_name($this->name)) {
            $this->_errors[] = Tools::display_error('Unable to install the module (Module name is not valid).');
            return false;
        }
        // Check tb version compliancy
        if (!$this->check_compliancy()) {
            $this->_errors[] = Tools::display_error('The version of your module is not compliant with your thirty bees version.');
            return false;
        }
        // Check module dependencies
        foreach ($this->dependencies as $dependency) {
            if (!Db::read_only()->get_row((new Db_Query())->select('`id_module`')->from('module')->where('LOWER(`name`) = \'' . p_sql(mb_strtolower((string) $dependency)) . '\''))) {
                $error = Tools::display_error('Before installing this module, you have to install this/these module(s) first:') . '<br />';
                foreach ($this->dependencies as $d) {
                    $error .= '- ' . $d . '<br />';
                }
                $this->_errors[] = $error;
                return false;
            }
        }
        // Check if module is installed
        $result = Module::is_installed($this->name);
        if ($result) {
            $this->_errors[] = Tools::display_error('This module has already been installed.');
            return false;
        }
        // Invalidate opcache
        if (function_exists('opcache_invalidate') && file_exists(_PS_MODULE_DIR_ . $this->name)) {
            foreach (new Recursive_Iterator_Iterator(new Recursive_Directory_Iterator(_PS_MODULE_DIR_ . $this->name)) as $file) {
                /** @var SplFileInfo $file */
                if (!str_ends_with($file->get_filename(), '.php')) {
                    continue;
                }
                if ($file->is_link()) {
                    continue;
                }
                opcache_invalidate($file->get_pathname());
            }
        }
        // Install overrides
        try {
            $this->install_overrides();
        } catch (Exception $e) {
            $this->_errors[] = sprintf(Tools::display_error('Unable to install override: %s'), $e->get_message());
            $this->uninstall_overrides();
            return false;
        }
        if (!$this->install_controllers()) {
            return false;
        }
        // Install module and retrieve the installation id
        $conn = Db::get_instance();
        $result = $conn->insert($this->table, ['name' => $this->name, 'active' => 1, 'version' => $this->version]);
        if (!$result) {
            $this->_errors[] = Tools::display_error('Technical error: thirty bees could not install this module.');
            return false;
        }
        $this->id = $conn->Insert_ID();
        // Enable the module for current shops in context
        $this->enable();
        // Clean module cache
        Cache::clean('Module::getModulesNameToIdMap');
        // Permissions management
        $conn->execute('
			INSERT INTO `' . _DB_PREFIX_ . 'module_access` (`id_profile`, `id_module`, `view`, `configure`, `uninstall`) (
				SELECT id_profile, ' . (int) $this->id . ', 1, 1, 1
				FROM ' . _DB_PREFIX_ . 'access a
				WHERE id_tab = (
					SELECT `id_tab` FROM ' . _DB_PREFIX_ . 'tab
					WHERE class_name = \'AdminModules\' LIMIT 1)
				AND a.`view` = 1)');
        $conn->execute('
			INSERT INTO `' . _DB_PREFIX_ . 'module_access` (`id_profile`, `id_module`, `view`, `configure`, `uninstall`) (
				SELECT id_profile, ' . (int) $this->id . ', 1, 0, 0
				FROM ' . _DB_PREFIX_ . 'access a
				WHERE id_tab = (
					SELECT `id_tab` FROM ' . _DB_PREFIX_ . 'tab
					WHERE class_name = \'AdminModules\' LIMIT 1)
				AND a.`view` = 0)');
        // Adding Restrictions for client groups
        Group::add_restrictions_for_module($this->id, Shop::get_shops(true, null, true));
        Hook::trigger_event('actionModuleInstallAfter', ['object' => $this]);
        if (!defined('TB_INSTALLATION_IN_PROGRESS') || !TB_INSTALLATION_IN_PROGRESS) {
            if (Module::$update_translations_after_install) {
                $this->update_module_translations();
            }
        }
        return true;
    }
    /**
     * @return bool
     */
    public function check_compliancy()
    {
        if (version_compare(_PS_VERSION_, $this->ps_versions_compliancy['min'], '<')) {
            return false;
        }
        if (version_compare('1.6.1.20', $this->ps_versions_compliancy['max'], '>')) {
            return false;
        }
        $tb_version = implode('.', array_map(intval(...), explode('.', _TB_VERSION_, 3)));
        return version_compare($tb_version, $this->tb_min_version, '>=');
    }
    /**
     * @param string $moduleName
     *
     * @return bool
     *
     *                getModuleIdByName().
     * @throws PrestaShopException
     */
    public static function is_installed($module_name)
    {
        return (bool) Module::get_module_id_by_name($module_name);
    }
    /**
     * Get ID module by name
     *
     * @param string $name
     *
     * @return int Module ID
     *
     * @throws PrestaShopException
     */
    public static function get_module_id_by_name($name)
    {
        $map = static::get_modules_name_to_id_map();
        $key = strtolower($name);
        return $map[$key] ?? 0;
    }
    /**
     * Get module name by ID
     *
     * @param int $moduleId
     *
     * @return string | null
     * @throws PrestaShopException
     */
    public static function get_module_name_by_id($module_id)
    {
        $module_id = (int) $module_id;
        $map = static::get_modules_name_to_id_map();
        foreach ($map as $module_name => $id) {
            if ($module_id === $id) {
                return $module_name;
            }
        }
        return null;
    }
    /**
     * Returns mapping from modules name -> module IDs
     *
     * @return array
     * @throws PrestaShopException
     */
    protected static function get_modules_name_to_id_map()
    {
        $cache_id = 'Module::getModulesNameToIdMap';
        if (!Cache::is_stored($cache_id)) {
            $sql = (new Db_Query())->select('`id_module`, `name`')->from('module');
            $map = [];
            foreach (Db::read_only()->get_array($sql) as $row) {
                $module_id = (int) $row['id_module'];
                $name = strtolower((string) $row['name']);
                $map[$name] = $module_id;
            }
            Cache::store($cache_id, $map);
            return $map;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Install overrides files for the module
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function install_overrides()
    {
        if (!is_dir($this->get_local_path() . 'override')) {
            return true;
        }
        $result = true;
        foreach (Tools::scandir($this->get_local_path() . 'override', 'php', '', true) as $file) {
            $class = basename($file, '.php');
            if (Presta_Shop_Autoload::get_instance()->get_class_path($class . 'Core') || Module::get_module_id_by_name($class)) {
                $result = $this->add_override($class) && $result;
            }
        }
        return $result;
    }
    /**
     * Get local path for module
     *
     * @return string
     */
    public function get_local_path()
    {
        return $this->local_path;
    }
    /**
     * Add all methods in a module override to the override class
     *
     * @param string $classname
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function add_override($classname)
    {
        try {
            $path = Presta_Shop_Autoload::get_instance()->get_class_path($classname . 'Core');
            if (!$path) {
                // override for module
                $path = 'modules' . DIRECTORY_SEPARATOR . $classname . DIRECTORY_SEPARATOR . $classname . '.php';
                $classname = $classname . 'Override';
                $tmp_class_suffix = '';
            } else {
                // override for core file
                $tmp_class_suffix = 'Override';
            }
            $path_override = $this->get_local_path() . 'override' . DIRECTORY_SEPARATOR . $path;
            if (!file_exists($path_override)) {
                return false;
            }
            file_put_contents($path_override, preg_replace('#(\r\n|\r)#ism', "\n", file_get_contents($path_override)));
            $pattern_escape_com = '#(^\s*?\/\/.*?\n|\/\*(?!\n\s+\* module:.*?\* date:.*?\* version:.*?\*\/).*?\*\/)#ism';
            // resolve path to existing system override file
            $override_path = null;
            $file = Presta_Shop_Autoload::get_instance()->get_class_path($classname);
            if ($file && file_exists(_PS_ROOT_DIR_ . '/' . $file)) {
                $override_path = _PS_ROOT_DIR_ . '/' . $file;
            }
            if ($override_path) {
                // System override file already exists, we have to merge module override file into it
                if (!is_writable($override_path)) {
                    throw new Presta_Shop_Exception(sprintf(Tools::display_error('file (%s) not writable'), $override_path));
                }
                // Make a reflection of the override class and the module override class
                $override_file = $this->load_override_file($override_path);
                $override_class = $this->get_override_file_reflection_class($classname, $override_file, $tmp_class_suffix . 'Original', $override_path);
                $module_file = $this->load_override_file($path_override);
                $module_class = $this->get_override_file_reflection_class($classname, $module_file, $tmp_class_suffix, $path_override);
                // Check if none of the methods already exists in the override class
                foreach ($module_class->get_methods() as $method) {
                    if ($override_class->has_method($method->get_name())) {
                        $method_override = $override_class->get_method($method->get_name());
                        if (preg_match('/module: (.*)/ism', $override_file[$method_override->get_start_line() - 5], $name) && preg_match('/date: (.*)/ism', $override_file[$method_override->get_start_line() - 4], $date) && preg_match('/version: ([0-9.]+)/ism', $override_file[$method_override->get_start_line() - 3], $version)) {
                            if ($name[1] !== $this->name || $version[1] !== $this->version) {
                                throw new Presta_Shop_Exception(sprintf(Tools::display_error('The method %1$s in the class %2$s is already overridden by the module %3$s version %4$s at %5$s.'), $method->get_name(), $classname, $name[1], $version[1], $date[1]));
                            }
                            continue;
                        }
                        throw new Presta_Shop_Exception(sprintf(Tools::display_error('The method %1$s in the class %2$s is already overridden.'), $method->get_name(), $classname));
                    }
                    $module_file = preg_replace('/((:?public|private|protected)\s+(static\s+)?function\s+(?:\b' . $method->get_name() . '\b))/ism', "/*\n    * module: " . $this->name . "\n    * date: " . date('Y-m-d H:i:s') . "\n    * version: " . $this->version . "\n    */\n    \$1", $module_file);
                    if ($module_file === null) {
                        throw new Presta_Shop_Exception(sprintf(Tools::display_error('Failed to override method %1$s in class %2$s.'), $method->get_name(), $classname));
                    }
                }
                // Check if none of the properties already exists in the override class
                foreach ($module_class->get_properties() as $property) {
                    if ($override_class->has_property($property->get_name())) {
                        throw new Presta_Shop_Exception(sprintf(Tools::display_error('The property %1$s in the class %2$s is already defined.'), $property->get_name(), $classname));
                    }
                    $module_file = preg_replace('/((?:public|private|protected)\s)\s*(static\s)?\s*(\$\b' . $property->get_name() . '\b)/ism', "/*\n    * module: " . $this->name . "\n    * date: " . date('Y-m-d H:i:s') . "\n    * version: " . $this->version . "\n    */\n    \$1\$2\$3", $module_file);
                    if ($module_file === null) {
                        throw new Presta_Shop_Exception(sprintf(Tools::display_error('Failed to override property %1$s in class %2$s.'), $property->get_name(), $classname));
                    }
                }
                foreach ($module_class->get_constants() as $constant => $value) {
                    if ($override_class->has_constant($constant)) {
                        throw new Presta_Shop_Exception(sprintf(Tools::display_error('The constant %1$s in the class %2$s is already defined.'), $constant, $classname));
                    }
                    $module_file = preg_replace('/(const\s)\s*(\b' . $constant . '\b)/ism', "/*\n    * module: " . $this->name . "\n    * date: " . date('Y-m-d H:i:s') . "\n    * version: " . $this->version . "\n    */\n    \$1\$2", $module_file);
                    if ($module_file === null) {
                        throw new Presta_Shop_Exception(sprintf(Tools::display_error('Failed to override constant %1$s in class %2$s.'), $constant, $classname));
                    }
                }
                // Insert the methods from module override in override
                $copy_from = array_slice($module_file, $module_class->get_start_line() + 1, $module_class->get_end_line() - $module_class->get_start_line() - 2);
                array_splice($override_file, $override_class->get_end_line() - 1, 0, $copy_from);
                $code = implode('', $override_file);
                file_put_contents($override_path, preg_replace($pattern_escape_com, '', $code));
            } else {
                // system override file does not exist yet, we have to create a new one
                $override_src = $path_override;
                $override_dest = _PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . 'override' . DIRECTORY_SEPARATOR . $path;
                // create destination directory, if needed
                $dir_name = dirname($override_dest);
                if (!is_dir($dir_name)) {
                    $defined_umask = defined('_TB_UMASK_') ? _TB_UMASK_ : 00;
                    $oldumask = umask($defined_umask);
                    @mkdir($dir_name, 0777);
                    umask($oldumask);
                }
                if (!is_writable($dir_name)) {
                    throw new Presta_Shop_Exception(sprintf(Tools::display_error('directory (%s) not writable'), $dir_name));
                }
                // Load module override file
                $module_file = $this->load_override_file($override_src);
                $module_class = $this->get_override_file_reflection_class($classname, $module_file, $tmp_class_suffix, $override_src);
                // For each method found in the override, prepend a comment with the module name and version
                foreach ($module_class->get_methods() as $method) {
                    $module_file = preg_replace('/((:?public|private|protected)\s+(static\s+)?function\s+(?:\b' . $method->get_name() . '\b))/ism', "/*\n    * module: " . $this->name . "\n    * date: " . date('Y-m-d H:i:s') . "\n    * version: " . $this->version . "\n    */\n    \$1", $module_file);
                    if ($module_file === null) {
                        throw new Presta_Shop_Exception(sprintf(Tools::display_error('Failed to override method %1$s in class %2$s.'), $method->get_name(), $classname));
                    }
                }
                // Same loop for properties
                foreach ($module_class->get_properties() as $property) {
                    $module_file = preg_replace('/((?:public|private|protected)\s)\s*(static\s)?\s*(\$\b' . $property->get_name() . '\b)/ism', "/*\n    * module: " . $this->name . "\n    * date: " . date('Y-m-d H:i:s') . "\n    * version: " . $this->version . "\n    */\n    \$1\$2\$3", $module_file);
                    if ($module_file === null) {
                        throw new Presta_Shop_Exception(sprintf(Tools::display_error('Failed to override property %1$s in class %2$s.'), $property->get_name(), $classname));
                    }
                }
                // Same loop for constants
                foreach ($module_class->get_constants() as $constant => $value) {
                    $module_file = preg_replace('/(const\s)\s*(\b' . $constant . '\b)/ism', "/*\n    * module: " . $this->name . "\n    * date: " . date('Y-m-d H:i:s') . "\n    * version: " . $this->version . "\n    */\n    \$1\$2", $module_file);
                    if ($module_file === null) {
                        throw new Presta_Shop_Exception(sprintf(Tools::display_error('Failed to override constant %1$s in class %2$s.'), $constant, $classname));
                    }
                }
                file_put_contents($override_dest, preg_replace($pattern_escape_com, '', $module_file));
                // Invalidate opcache
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($override_dest);
                }
                // Re-generate the class index
                Tools::generate_index();
            }
            return true;
        } catch (Reflection_Exception $e) {
            throw new Presta_Shop_Exception('Failed to add override', 0, $e);
        }
    }
    /**
     * Uninstall overrides files for the module
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function uninstall_overrides()
    {
        if (!is_dir($this->get_local_path() . 'override')) {
            return true;
        }
        $result = true;
        foreach (Tools::scandir($this->get_local_path() . 'override', 'php', '', true) as $file) {
            $class = basename($file, '.php');
            if (Presta_Shop_Autoload::get_instance()->get_class_path($class . 'Core') || Module::get_module_id_by_name($class)) {
                $result = $this->remove_override($class) && $result;
            }
        }
        return $result;
    }
    /**
     * Remove all methods in a module override from the override class
     *
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function remove_override(string $classname)
    {
        try {
            $orig_path = $path = Presta_Shop_Autoload::get_instance()->get_class_path($classname . 'Core');
            if ($orig_path && !$file = Presta_Shop_Autoload::get_instance()->get_class_path($classname)) {
                return true;
            }
            if (!$orig_path && Module::get_module_id_by_name($classname)) {
                $path = 'modules' . DIRECTORY_SEPARATOR . $classname . DIRECTORY_SEPARATOR . $classname . '.php';
            }
            // Check if override file is writable
            if ($orig_path) {
                $override_path = _PS_ROOT_DIR_ . '/' . $file;
            } else {
                $override_path = _PS_OVERRIDE_DIR_ . $path;
            }
            if (!is_file($override_path) || !is_writable($override_path)) {
                return false;
            }
            file_put_contents($override_path, preg_replace('#(\r\n|\r)#ism', "\n", file_get_contents($override_path)));
            if ($orig_path) {
                // Make a reflection of the override class and the module override class
                $override_file = $this->load_override_file($override_path);
                $override_class = $this->get_override_file_reflection_class($classname, $override_file, 'OverrideOriginal_remove', $override_path);
                $module_path = $this->get_local_path() . 'override/' . $path;
                $module_file = $this->load_override_file($module_path);
                $module_class = $this->get_override_file_reflection_class($classname, $module_file, 'Override_remove', $module_path);
                // Remove methods from override file
                foreach ($module_class->get_methods() as $method) {
                    if (!$override_class->has_method($method->get_name())) {
                        continue;
                    }
                    $method = $override_class->get_method($method->get_name());
                    $length = $method->get_end_line() - $method->get_start_line() + 1;
                    $module_method = $module_class->get_method($method->get_name());
                    $override_file_orig = $override_file;
                    $orig_content = preg_replace('/\s/', '', implode('', array_splice($override_file, $method->get_start_line() - 1, $length, array_pad([], $length, '#--remove--#'))));
                    $module_content = preg_replace('/\s/', '', implode('', array_splice($module_file, $module_method->get_start_line() - 1, $length, array_pad([], $length, '#--remove--#'))));
                    $replace = true;
                    if (preg_match('/\* module: (' . $this->name . ')/ism', $override_file[$method->get_start_line() - 5])) {
                        $override_file[$method->get_start_line() - 6] = $override_file[$method->get_start_line() - 5] = $override_file[$method->get_start_line() - 4] = $override_file[$method->get_start_line() - 3] = $override_file[$method->get_start_line() - 2] = '#--remove--#';
                        $replace = false;
                    }
                    if (md5((string) $module_content) != md5((string) $orig_content) && $replace) {
                        $override_file = $override_file_orig;
                    }
                }
                // Remove properties from override file
                foreach ($module_class->get_properties() as $property) {
                    if (!$override_class->has_property($property->get_name())) {
                        continue;
                    }
                    // Replace the declaration line by #--remove--#
                    foreach ($override_file as $line_number => &$line_content) {
                        if (preg_match('/(public|private|protected)\s+(static\s+)?(\$)?' . $property->get_name() . '/i', $line_content)) {
                            if (preg_match('/\* module: (' . $this->name . ')/ism', $override_file[$line_number - 4])) {
                                $override_file[$line_number - 5] = $override_file[$line_number - 4] = $override_file[$line_number - 3] = $override_file[$line_number - 2] = $override_file[$line_number - 1] = '#--remove--#';
                            }
                            $line_content = '#--remove--#';
                            break;
                        }
                    }
                }
                // Remove properties from override file
                foreach ($module_class->get_constants() as $constant => $value) {
                    if (!$override_class->has_constant($constant)) {
                        continue;
                    }
                    // Replace the declaration line by #--remove--#
                    foreach ($override_file as $line_number => &$line_content) {
                        if (preg_match('/(const)\s+(static\s+)?(\$)?' . $constant . '/i', $line_content)) {
                            if (preg_match('/\* module: (' . $this->name . ')/ism', $override_file[$line_number - 4])) {
                                $override_file[$line_number - 5] = $override_file[$line_number - 4] = $override_file[$line_number - 3] = $override_file[$line_number - 2] = $override_file[$line_number - 1] = '#--remove--#';
                            }
                            $line_content = '#--remove--#';
                            break;
                        }
                    }
                }
                $count = count($override_file);
                for ($i = 0; $i < $count; ++$i) {
                    if (preg_match('/(^\s*\/\/.*)/i', $override_file[$i])) {
                        $override_file[$i] = '#--remove--#';
                    } elseif (preg_match('/(^\s*\/\*)/i', $override_file[$i])) {
                        if (!preg_match('/(^\s*\* module:)/i', $override_file[$i + 1]) && !preg_match('/(^\s*\* date:)/i', $override_file[$i + 2]) && !preg_match('/(^\s*\* version:)/i', $override_file[$i + 3]) && !preg_match('/(^\s*\*\/)/i', $override_file[$i + 4])) {
                            for (; $override_file[$i] && !preg_match('/(.*?\*\/)/i', $override_file[$i]); ++$i) {
                                $override_file[$i] = '#--remove--#';
                            }
                            $override_file[$i] = '#--remove--#';
                        }
                    }
                }
                // Rewrite nice code
                $code = '';
                foreach ($override_file as $line) {
                    if ($line == '#--remove--#') {
                        continue;
                    }
                    $code .= $line;
                }
                $to_delete = preg_match('/<\?(?:php)?\s+(?:abstract|interface)?\s*?class\s+' . $classname . '\s+extends\s+' . $classname . 'Core\s*?[{]\s*?[}]/ism', $code);
            }
            if (!isset($to_delete) || $to_delete) {
                Tools::delete_file($override_path);
            } else {
                file_put_contents($override_path, $code);
                if (function_exists('opcache_invalidate')) {
                    opcache_invalidate($override_path);
                }
            }
            // Re-generate the class index
            Tools::generate_index();
            return true;
        } catch (Reflection_Exception $e) {
            throw new Presta_Shop_Exception('Failed to remove module override', 0, $e);
        }
    }
    /**
     * Install module's controllers using public property $controllers
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function install_controllers()
    {
        $themes = Theme::get_themes();
        $theme_meta_value = [];
        foreach ($this->controllers as $controller) {
            $page = 'module-' . $this->name . '-' . $controller;
            $result = Db::read_only()->get_value((new Db_Query())->select('*')->from('meta')->where('`page` = \'' . p_sql($page) . '\''));
            if ((int) $result > 0) {
                continue;
            }
            $meta = new Meta();
            $meta->page = $page;
            $meta->configurable = 1;
            $meta->save();
            if ((int) $meta->id > 0) {
                foreach ($themes as $theme) {
                    /** @var Theme $theme */
                    $theme_meta_value[] = ['id_theme' => $theme->id, 'id_meta' => $meta->id, 'left_column' => (int) $theme->default_left_column, 'right_column' => (int) $theme->default_right_column];
                }
            } else {
                $this->_errors[] = sprintf(Tools::display_error('Unable to install controller: %s'), $controller);
            }
        }
        if (count($theme_meta_value) > 0) {
            return Db::get_instance()->insert('theme_meta', $theme_meta_value);
        }
        return true;
    }
    /**
     * Activate current module.
     *
     * @param bool $forceAll If true, enable module for all shop
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function enable($force_all = false)
    {
        // Retrieve all shops where the module is enabled
        $list = Shop::get_context_list_shop_id();
        if (!$this->id || !is_array($list)) {
            return false;
        }
        // Store the results in an array
        $items = [];
        if ($results = Db::read_only()->get_array((new Db_Query())->select('`id_shop`')->from('module_shop')->where('`id_module` = ' . (int) $this->id)->where(!$force_all ? '`id_shop` IN(' . implode(', ', $list) . ')' : ''))) {
            foreach ($results as $row) {
                $items[] = $row['id_shop'];
            }
        }
        // Enable module in the shop where it is not enabled yet
        foreach ($list as $id) {
            if (!in_array($id, $items)) {
                Db::get_instance()->insert('module_shop', ['id_module' => $this->id, 'id_shop' => $id]);
            }
        }
        return true;
    }
    /**
     * @throws PrestaShopException
     */
    public function update_module_translations(): void
    {
        Language::update_modules_translations([$this->name]);
    }
    /**
     * Run the upgrade for a given module name and version
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function run_upgrade_module()
    {
        $upgrade =& static::$modules_cache[$this->name]['upgrade'];
        foreach ($upgrade['upgrade_file_left'] as $num => $file_detail) {
            foreach ($file_detail['upgrade_function'] as $item) {
                if (function_exists($item)) {
                    $upgrade['success'] = false;
                    $upgrade['duplicate'] = true;
                    break 2;
                }
            }
            include $file_detail['file'];
            // Call the upgrade function if defined
            $upgrade['success'] = false;
            foreach ($file_detail['upgrade_function'] as $item) {
                if (function_exists($item)) {
                    $upgrade['success'] = $item($this);
                }
            }
            // Set detail when an upgrade succeed or failed
            if ($upgrade['success']) {
                $upgrade['number_upgraded'] += 1;
                $upgrade['upgraded_to'] = $file_detail['version'];
                unset($upgrade['upgrade_file_left'][$num]);
            } else {
                $upgrade['version_fail'] = $file_detail['version'];
                // If any errors, the module is disabled
                $this->disable();
                break;
            }
        }
        $upgrade['number_upgrade_left'] = count($upgrade['upgrade_file_left']);
        // Update module version in DB with the last succeed upgrade
        if ($upgrade['upgraded_to']) {
            Module::upgrade_module_version($this->name, $upgrade['upgraded_to']);
        }
        $this->set_upgrade_message($upgrade);
        return $upgrade;
    }
    /**
     * Deactivate the current module.
     *
     * @param bool $forceAll If true, disable module for all shop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function disable($force_all = false): void
    {
        // Disable module for all shops
        Db::get_instance()->delete('module_shop', '`id_module` = ' . (int) $this->id . ' ' . (!$force_all ? ' AND `id_shop` IN(' . implode(', ', Shop::get_context_list_shop_id()) . ')' : ''));
    }
    /**
     * Set errors, warning or success message of a module upgrade
     */
    protected function set_upgrade_message(array $upgrade_detail)
    {
        // Store information if a module has been upgraded (memory optimization)
        if ($upgrade_detail['available_upgrade']) {
            if ($upgrade_detail['success']) {
                $this->_confirmations[] = sprintf(Tools::display_error('Current version: %s'), $this->version);
                $this->_confirmations[] = sprintf(Tools::display_error('%d file upgrade applied'), $upgrade_detail['number_upgraded']);
            } else {
                if (!$upgrade_detail['number_upgraded']) {
                    $this->_errors[] = Tools::display_error('No upgrade has been applied');
                } else {
                    $this->_errors[] = sprintf(Tools::display_error('Upgraded from: %s to %s'), $upgrade_detail['upgraded_from'], $upgrade_detail['upgraded_to']);
                    $this->_errors[] = sprintf(Tools::display_error('%d upgrade left'), $upgrade_detail['number_upgrade_left']);
                }
                if (isset($upgrade_detail['duplicate']) && $upgrade_detail['duplicate']) {
                    $this->_errors[] = sprintf(Tools::display_error('Module %s cannot be upgraded this time: please refresh this page to update it.'), $this->name);
                } else {
                    $this->_errors[] = Tools::display_error('To prevent any problem, this module has been turned off');
                }
            }
        }
    }
    /**
     * Delete module from datable
     *
     * @return bool result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        // Check module installation id validation
        if (!Validate::is_unsigned_id($this->id)) {
            $this->_errors[] = Tools::display_error('The module is not installed.');
            return false;
        }
        // Uninstall overrides
        if (!$this->uninstall_overrides()) {
            return false;
        }
        // Retrieve hooks used by the module
        $conn = Db::get_instance();
        $result = $conn->get_array((new Db_Query())->select('`id_hook`')->from('hook_module')->where('`id_module` = ' . (int) $this->id));
        foreach ($result as $row) {
            $this->unregister_hook((int) $row['id_hook']);
            $this->unregister_exceptions((int) $row['id_hook']);
        }
        foreach ($this->controllers as $controller) {
            $page_name = 'module-' . $this->name . '-' . $controller;
            $meta = $conn->get_value((new Db_Query())->select('`id_meta`')->from('meta')->where('`page` = \'' . p_sql($page_name) . '\''));
            if ((int) $meta > 0) {
                $conn->delete('theme_meta', '`id_meta` = ' . (int) $meta);
                $conn->delete('meta_lang', '`id_meta` = ' . (int) $meta);
                $conn->delete('meta', '`id_meta` = ' . (int) $meta);
            }
        }
        // Disable the module for all shops
        $this->disable(true);
        // Delete permissions module access
        $conn->delete('module_access', '`id_module` = ' . (int) $this->id);
        // Remove restrictions for client groups
        Group::truncate_restrictions_by_module($this->id);
        // Uninstall the module
        if ($conn->delete('module', '`id_module` = ' . (int) $this->id)) {
            Cache::clean('Module::getModulesNameToIdMap');
            return true;
        }
        return false;
    }
    /**
     * Unregister module from hook
     *
     * @param int|string $hookId Hook id (can be a hook name since 1.5.0)
     * @param int[]|null $shopList List of shop
     *
     * @return bool result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function unregister_hook($hook_id, $shop_list = null)
    {
        // Get hook id if a name is given as argument
        if (!is_numeric($hook_id)) {
            $hook_name = (string) $hook_id;
            // Retrocompatibility
            $hook_id = Hook::get_id_by_name($hook_name);
            if (!$hook_id) {
                return false;
            }
        } else {
            $hook_name = Hook::get_name_by_id((int) $hook_id);
        }
        Hook::trigger_event('actionModuleUnRegisterHookBefore', ['object' => $this, 'hook_name' => $hook_name]);
        // Unregister module on hook by id
        $result = Db::get_instance()->delete('hook_module', '`id_module` = ' . (int) $this->id . ' AND `id_hook` = ' . (int) $hook_id . ($shop_list ? ' AND `id_shop` IN(' . implode(', ', array_map(intval(...), $shop_list)) . ')' : ''));
        // Clean modules position
        $this->clean_positions($hook_id, $shop_list);
        Hook::trigger_event('actionModuleUnRegisterHookAfter', ['object' => $this, 'hook_name' => $hook_name]);
        return $result;
    }
    /**
     * Reorder modules position
     *
     * @param bool $idHook Hook ID
     * @param array $shopList List of shop
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function clean_positions($id_hook, $shop_list = null)
    {
        $conn = Db::get_instance();
        $results = $conn->get_array((new Db_Query())->select('`id_module`, `id_shop`')->from('hook_module')->where('`id_hook` = ' . (int) $id_hook)->where($shop_list ? '`id_shop` IN(' . implode(', ', array_map(intval(...), $shop_list)) . ')' : '')->order_by('`position`'));
        $position = [];
        foreach ($results as $row) {
            if (!isset($position[$row['id_shop']])) {
                $position[$row['id_shop']] = 1;
            }
            $conn->update('hook_module', ['position' => $position[$row['id_shop']]], '`id_hook` = ' . (int) $id_hook . ' AND `id_module` = ' . $row['id_module'] . ' AND `id_shop` = ' . $row['id_shop']);
            $position[$row['id_shop']]++;
        }
        return true;
    }
    /**
     * Unregister exceptions linked to module
     *
     * @param int $hookId Hook id
     * @param array $shopList List of shop
     *
     * @return bool result
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function unregister_exceptions($hook_id, $shop_list = null)
    {
        return Db::get_instance()->delete('hook_module_exceptions', '`id_module` = ' . (int) $this->id . ' AND `id_hook` = ' . (int) $hook_id . ($shop_list ? ' AND `id_shop` IN(' . implode(', ', array_map(intval(...), $shop_list)) . ')' : ''));
    }
    /**
     * @param int $device
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function enable_device($device)
    {
        Db::get_instance()->update('module_shop', ['enable_device' => ['type' => 'sql', 'value' => '`enable_device` + ' . (int) $device]], '(`enable_device` &~ ' . (int) $device . ' OR `enable_device` = 0) AND `id_module` = ' . (int) $this->id . ' ' . Shop::add_sql_restriction());
        return true;
    }
    /**
     * @param int $device
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function disable_device($device)
    {
        Db::get_instance()->update('module_shop', ['enable_device' => ['type' => 'sql', 'value' => '`enable_device` - ' . (int) $device]], 'enable_device & ' . (int) $device . ' AND id_module=' . (int) $this->id . Shop::add_sql_restriction());
        return true;
    }
    /**
     * Display flags in forms for translations
     *
     * @param array $languages All languages available
     * @param int $defaultLanguage Default language id
     * @param string $ids Multilingual div ids in form
     * @param string $id Current div id]
     * @param bool $return define the return way : false for a display, true for a return
     * @param bool $useVarsInsteadOfIds use an js vars instead of ids seperate by "¤"
     *
     * @return bool|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function display_flags($languages, $default_language, string $ids, string $id, $return = false, $use_vars_instead_of_ids = false)
    {
        if (count($languages) == 1) {
            return false;
        }
        $image_extension = Image_Manager::get_default_image_extension();
        $output = '
		<div class="displayed_flag">
			<img src="../img/l/' . $default_language . '.' . $image_extension . '" class="pointer" id="language_current_' . $id . '" onclick="toggleLanguageFlags(this);" alt="" />
		</div>
		<div id="languages_' . $id . '" class="language_flags">
			' . $this->l('Choose language:') . '<br /><br />';
        foreach ($languages as $language) {
            if ($use_vars_instead_of_ids) {
                $output .= '<img src="../img/l/' . (int) $language['id_lang'] . '.' . $image_extension . '" class="pointer" alt="' . $language['name'] . '" title="' . $language['name'] . '" onclick="changeLanguage(\'' . $id . '\', ' . $ids . ', ' . $language['id_lang'] . ', \'' . $language['iso_code'] . '\');" /> ';
            } else {
                $output .= '<img src="../img/l/' . (int) $language['id_lang'] . '.' . $image_extension . '" class="pointer" alt="' . $language['name'] . '" title="' . $language['name'] . '" onclick="changeLanguage(\'' . $id . '\', \'' . $ids . '\', ' . $language['id_lang'] . ', \'' . $language['iso_code'] . '\');" /> ';
            }
        }
        $output .= '</div>';
        if ($return) {
            return $output;
        }
        echo $output;
    }
    /**
     * Get translation for a given module text
     *
     * Note: $specific parameter is mandatory for library files.
     * Otherwise, translation key will not match for Module library
     * when module is loaded with eval() Module::getModulesOnDisk()
     *
     * @param string $string String to translate
     * @param bool|string $specific filename to use in translation key
     *
     * @return string Translation
     */
    public function l($string, $specific = false)
    {
        if (static::$_generate_config_xml_mode) {
            return $string;
        }
        return Translate::get_module_translation($this, $string, $specific ?: $this->name);
    }
    /**
     * Connect module to a hook
     *
     * @param string|string[] $hookName Hook name or an array with hook names
     * @param array $shopList List of shop linked to the hook (if null, link hook to all shops)
     *
     * @return bool result
     * @throws PrestaShopException
     */
    public function register_hook($hook_name, $shop_list = null)
    {
        if (!isset($this->id) || !is_numeric($this->id)) {
            return false;
        }
        $return = true;
        if (is_array($hook_name)) {
            $hook_names = $hook_name;
        } else {
            $hook_names = [$hook_name];
        }
        foreach ($hook_names as $hook_name) {
            // Check hook name validation and if module is installed
            if (!Validate::is_hook_name($hook_name)) {
                throw new Presta_Shop_Exception('Invalid hook name');
            }
            $alias = Hook::get_retro_hook_name($hook_name);
            if (!is_callable([$this, 'hook' . $hook_name]) && !is_callable([$this, 'hook' . $alias])) {
                Logger::add_log("Module '{$this->name}' is trying to register hook '{$hook_name}', but does not implement handler", 2, 0, 'Module', $this->id);
                continue;
            }
            if ($alias) {
                $hook_name = $alias;
            }
            Hook::trigger_event('actionModuleRegisterHookBefore', ['object' => $this, 'hook_name' => $hook_name]);
            // Get hook id
            $id_hook = Hook::get_id_by_name($hook_name);
            // If hook does not exist, we create it
            if (!$id_hook) {
                $new_hook = new Hook();
                $new_hook->name = p_sql($hook_name);
                $new_hook->title = p_sql($hook_name);
                $new_hook->live_edit = (bool) preg_match('/^display/i', $new_hook->name);
                $new_hook->position = (bool) $new_hook->live_edit;
                $new_hook->add();
                $id_hook = $new_hook->id;
                if (!$id_hook) {
                    return false;
                }
            }
            // If shop lists is null, we fill it with all shops
            if (is_null($shop_list)) {
                $shop_list = Shop::get_complete_list_of_shops_id();
            }
            $shop_list_employee = Shop::get_shops(true, null, true);
            $conn = Db::get_instance();
            foreach ($shop_list as $shop_id) {
                // Check if already register
                $sql = 'SELECT hm.`id_module`
					FROM `' . _DB_PREFIX_ . 'hook_module` hm, `' . _DB_PREFIX_ . 'hook` h
					WHERE hm.`id_module` = ' . (int) $this->id . ' AND h.`id_hook` = ' . $id_hook . '
					AND h.`id_hook` = hm.`id_hook` AND `id_shop` = ' . (int) $shop_id;
                if ($conn->get_row($sql)) {
                    continue;
                }
                // Get module position in hook
                $sql = 'SELECT MAX(`position`) AS position
					FROM `' . _DB_PREFIX_ . 'hook_module`
					WHERE `id_hook` = ' . (int) $id_hook . ' AND `id_shop` = ' . (int) $shop_id;
                if (!$position = $conn->get_value($sql)) {
                    $position = 0;
                }
                // Register module in hook
                $return = $conn->insert('hook_module', ['id_module' => (int) $this->id, 'id_hook' => (int) $id_hook, 'id_shop' => (int) $shop_id, 'position' => (int) ($position + 1)]) && $return;
                if (!in_array($shop_id, $shop_list_employee)) {
                    $where = '`id_module` = ' . (int) $this->id . ' AND `id_shop` = ' . (int) $shop_id;
                    $return = $conn->delete('module_shop', $where) && $return;
                }
            }
            Hook::trigger_event('actionModuleRegisterHookAfter', ['object' => $this, 'hook_name' => $hook_name]);
        }
        return $return;
    }
    /**
     * Edit exceptions for module->Hook
     *
     * @param int $idHook
     * @param array $excepts List of shopID and file name
     *
     * @return bool result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function edit_exceptions($id_hook, $excepts)
    {
        $result = true;
        foreach ($excepts as $shop_id => $except) {
            $shop_list = $shop_id == 0 ? Shop::get_context_list_shop_id() : [$shop_id];
            $this->unregister_exceptions($id_hook, $shop_list);
            $result = $this->register_exceptions($id_hook, $except, $shop_list) && $result;
        }
        return $result;
    }
    /**
     * Add exceptions for module->Hook
     *
     * @param int $idHook Hook id
     * @param array $excepts List of file name
     * @param array $shopList List of shop
     *
     * @return bool result
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function register_exceptions($id_hook, $excepts, $shop_list = null)
    {
        // If shop lists is null, we fill it with all shops
        if (is_null($shop_list)) {
            $shop_list = Shop::get_context_list_shop_id();
        }
        // Save modules exception for each shop
        $conn = Db::get_instance();
        foreach ($shop_list as $shop_id) {
            foreach ($excepts as $except) {
                if (!$except) {
                    continue;
                }
                $insert_exception = ['id_module' => (int) $this->id, 'id_hook' => (int) $id_hook, 'id_shop' => (int) $shop_id, 'file_name' => p_sql($except)];
                $result = $conn->insert('hook_module_exceptions', $insert_exception);
                if (!$result) {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * Reposition module
     *
     * @param bool $idHook Hook ID
     * @param bool $way Up (0) or Down (1)
     * @param int $position
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_position($id_hook, $way, $position = null)
    {
        foreach (Shop::get_context_list_shop_id() as $id_shop) {
            $sql = 'SELECT hm.`id_module`, hm.`position`, hm.`id_hook`
					FROM `' . _DB_PREFIX_ . 'hook_module` hm
					WHERE hm.`id_hook` = ' . (int) $id_hook . ' AND hm.`id_shop` = ' . $id_shop . '
					ORDER BY hm.`position` ' . ($way ? 'ASC' : 'DESC');
            if (!$res = Db::read_only()->get_array($sql)) {
                continue;
            }
            foreach ($res as $key => $values) {
                if ((int) $values[$this->identifier] == (int) $this->id) {
                    $k = $key;
                    break;
                }
            }
            if (!isset($k) || !isset($res[$k]) || !isset($res[$k + 1])) {
                return false;
            }
            $from = $res[$k];
            $to = $res[$k + 1];
            if (!empty($position)) {
                $to['position'] = (int) $position;
            }
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_module`
				SET `position`= position ' . ($way ? '-1' : '+1') . '
				WHERE position between ' . (int) min([$from['position'], $to['position']]) . ' AND ' . max([$from['position'], $to['position']]) . '
				AND `id_hook` = ' . (int) $from['id_hook'] . ' AND `id_shop` = ' . $id_shop;
            $conn = Db::get_instance();
            if (!$conn->execute($sql)) {
                return false;
            }
            $sql = 'UPDATE `' . _DB_PREFIX_ . 'hook_module`
				SET `position`=' . (int) $to['position'] . '
				WHERE `' . p_sql($this->identifier) . '` = ' . (int) $from[$this->identifier] . '
				AND `id_hook` = ' . (int) $to['id_hook'] . ' AND `id_shop` = ' . $id_shop;
            if (!$conn->execute($sql)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Helper displaying error message(s)
     *
     * @param string|array $error
     *
     * @return string
     */
    public function display_error($error)
    {
        $output = '
		<div class="bootstrap">
		<div class="module_error alert alert-danger" >
			<button type="button" class="close" data-dismiss="alert">&times;</button>';
        if (is_array($error)) {
            $output .= '<ul>';
            foreach ($error as $msg) {
                $output .= '<li>' . $msg . '</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= $error;
        }
        // Close div opened previously
        $output .= '</div></div>';
        return $output;
    }
    /**
     * Helper displaying warning message(s)
     *
     * @param string|string[] $warning
     * @return string
     */
    public function display_warning($warning)
    {
        $output = '
		<div class="bootstrap">
		<div class="module_warning alert alert-warning" >
			<button type="button" class="close" data-dismiss="alert">&times;</button>';
        if (is_array($warning)) {
            $output .= '<ul>';
            foreach ($warning as $msg) {
                $output .= '<li>' . $msg . '</li>';
            }
            $output .= '</ul>';
        } else {
            $output .= $warning;
        }
        // Close div openned previously
        $output .= '</div></div>';
        return $output;
    }
    /**
     * @return string
     */
    public function display_confirmation(string $string)
    {
        return '
		<div class="bootstrap">
		<div class="module_confirmation conf confirm alert alert-success">
			<button type="button" class="close" data-dismiss="alert">&times;</button>
			' . $string . '
		</div>
		</div>';
    }
    /**
     * Return exceptions for module in hook
     *
     * @param int $idHook Hook ID
     *
     * @param bool $dispatch
     *
     * @return array Exceptions
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_exceptions($id_hook, $dispatch = false)
    {
        return Module::get_exceptions_static($this->id, $id_hook, $dispatch);
    }
    /**
     * Return exceptions for module in hook
     *
     * @param int $id_module Module ID
     * @param int $id_hook Hook ID
     *
     * @param bool $dispatch
     *
     * @return array Exceptions
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_exceptions_static($id_module, $id_hook, $dispatch = false)
    {
        $cache_id = 'exceptionsCache';
        if (!Cache::is_stored($cache_id)) {
            $exceptions_cache = [];
            $db_slave = Db::read_only();
            $result = $db_slave->get_array((new Db_Query())->select('*')->from('hook_module_exceptions')->where('`id_shop` IN (' . implode(', ', Shop::get_context_list_shop_id()) . ')'));
            foreach ($result as $row) {
                if (!$row['file_name']) {
                    continue;
                }
                $key = $row['id_hook'] . '-' . $row['id_module'];
                if (!isset($exceptions_cache[$key])) {
                    $exceptions_cache[$key] = [];
                }
                if (!isset($exceptions_cache[$key][$row['id_shop']])) {
                    $exceptions_cache[$key][$row['id_shop']] = [];
                }
                $exceptions_cache[$key][$row['id_shop']][] = $row['file_name'];
            }
            Cache::store($cache_id, $exceptions_cache);
        } else {
            $exceptions_cache = Cache::retrieve($cache_id);
        }
        $key = $id_hook . '-' . $id_module;
        $array_return = [];
        if ($dispatch) {
            foreach (Shop::get_context_list_shop_id() as $shop_id) {
                if (isset($exceptions_cache[$key], $exceptions_cache[$key][$shop_id])) {
                    $array_return[$shop_id] = $exceptions_cache[$key][$shop_id];
                }
            }
        } else {
            foreach (Shop::get_context_list_shop_id() as $shop_id) {
                if (isset($exceptions_cache[$key], $exceptions_cache[$key][$shop_id])) {
                    foreach ($exceptions_cache[$key][$shop_id] as $file) {
                        if (!in_array($file, $array_return)) {
                            $array_return[] = $file;
                        }
                    }
                }
            }
        }
        return $array_return;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_enabled_for_shop_context()
    {
        return static::is_enabled_for_shops($this->id, Shop::get_context_list_shop_id());
    }
    /**
     * This method returns true if module with id $moduleId is enabled for *all* shops specified in $shops array.
     *
     * @param int $moduleId module ID
     * @param int[] $shops list of shops to check
     * @return bool
     * @throws PrestaShopException
     */
    public static function is_enabled_for_shops($module_id, $shops)
    {
        if (!$shops) {
            return false;
        }
        // first, check if module is marked as enabled
        if (!Db::read_only()->get_value((new Db_Query())->select('COUNT(*) n')->from('module_shop')->where('`id_module` = ' . (int) $module_id)->where('`id_shop` IN (' . implode(',', array_map(intval(...), $shops)) . ')')->group_by('`id_module`')->having('n = ' . count($shops)))) {
            return false;
        }
        // if the module is enabled, check if module file exists on filesystem
        return static::module_exists_on_filesystem(static::get_module_name_by_id($module_id));
    }
    /**
     * Returns true, if module file exists in /modules/<modulenName>/<moduleName>.php
     *
     * @param string $moduleName
     *
     * @return bool
     */
    public static function module_exists_on_filesystem($module_name)
    {
        if (!$module_name) {
            return false;
        }
        if (!Validate::is_module_name($module_name)) {
            return false;
        }
        $name = strtolower($module_name);
        return is_dir(_PS_MODULE_DIR_ . $name . DIRECTORY_SEPARATOR) && Tools::file_exists_no_cache(_PS_MODULE_DIR_ . $name . '/' . $name . '.php');
    }
    /**
     * @param string $hook
     *
     * @return false|int
     *
     * @throws PrestaShopException
     */
    public function is_registered_in_hook($hook)
    {
        if (!$this->id) {
            return false;
        }
        return Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->FROM('hook_module', 'hm')->left_join('hook', 'h', 'h.`id_hook` = hm.`id_hook`')->where('h.`name` = \'' . p_sql($hook) . '\'')->where('hm.`id_module` = ' . (int) $this->id));
    }
    /**
     * @param string $file
     * @param string|null $cache_id
     * @param string|null $compile_id
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function display($file, string $template, $cache_id = null, $compile_id = null)
    {
        $module_name = basename($file, '.php');
        if (($overloaded = Module::_is_template_overloaded_static($module_name, $template)) === null) {
            return Tools::display_error('No template found for module') . ' ' . $module_name . ': ' . $template;
        }
        if (Tools::get_isset('live_edit') || Tools::get_isset('live_configurator_token')) {
            $cache_id = null;
        }
        $this->smarty->assign(['module_dir' => __PS_BASE_URI__ . 'modules/' . $module_name . '/', 'module_template_dir' => ($overloaded ? _THEME_DIR_ : __PS_BASE_URI__) . 'modules/' . $module_name . '/']);
        if ($cache_id !== null) {
            Tools::enable_cache();
        }
        $result = $this->get_current_sub_template($template, $cache_id, $compile_id)->fetch();
        if ($cache_id !== null) {
            Tools::restore_cache_settings();
        }
        $this->reset_current_sub_template($template, $cache_id, $compile_id);
        if ($result && _PS_MODE_DEV_ && !Validate::is_json($result)) {
            $tpl_path = $this->get_template_path($template);
            $result = '<!-- START ' . $tpl_path . ' -->' . $result . '<!-- END ' . $tpl_path . ' -->';
        }
        return $result;
    }
    /**
     *
     * @return bool|null|string
     */
    protected static function _is_template_overloaded_static(string $module_name, string $template)
    {
        if (file_exists(_PS_THEME_DIR_ . 'modules/' . $module_name . '/' . $template)) {
            return _PS_THEME_DIR_ . 'modules/' . $module_name . '/' . $template;
        }
        if (file_exists(_PS_THEME_DIR_ . 'modules/' . $module_name . '/views/templates/hook/' . $template)) {
            return _PS_THEME_DIR_ . 'modules/' . $module_name . '/views/templates/hook/' . $template;
        }
        if (file_exists(_PS_THEME_DIR_ . 'modules/' . $module_name . '/views/templates/front/' . $template)) {
            return _PS_THEME_DIR_ . 'modules/' . $module_name . '/views/templates/front/' . $template;
        }
        if (file_exists(_PS_MODULE_DIR_ . $module_name . '/views/templates/hook/' . $template)) {
            return false;
        }
        if (file_exists(_PS_MODULE_DIR_ . $module_name . '/views/templates/front/' . $template)) {
            return false;
        }
        if (file_exists(_PS_MODULE_DIR_ . $module_name . '/' . $template)) {
            return false;
        }
        return null;
    }
    /**
     * @param string|null $cache_id
     * @param string|null $compile_id
     *
     * @return Smarty_Internal_Template
     * @throws SmartyException
     */
    protected function get_current_sub_template(string $template, $cache_id = null, $compile_id = null)
    {
        if (!isset($this->current_subtemplate[$template . '_' . $cache_id . '_' . $compile_id])) {
            $this->current_subtemplate[$template . '_' . $cache_id . '_' . $compile_id] = $this->context->smarty->create_template($this->get_template_path($template), $cache_id, $compile_id, $this->smarty);
        }
        return $this->current_subtemplate[$template . '_' . $cache_id . '_' . $compile_id];
    }
    /**
     * Get realpath of a template of current module (check if template is overriden too)
     *
     *
     * @return string
     */
    public function get_template_path(string $template)
    {
        $overloaded = $this->_is_template_overloaded($template);
        if ($overloaded === null) {
            return null;
        }
        if ($overloaded) {
            return $overloaded;
        }
        if (file_exists(_PS_MODULE_DIR_ . $this->name . '/views/templates/hook/' . $template)) {
            return _PS_MODULE_DIR_ . $this->name . '/views/templates/hook/' . $template;
        }
        if (file_exists(_PS_MODULE_DIR_ . $this->name . '/views/templates/front/' . $template)) {
            return _PS_MODULE_DIR_ . $this->name . '/views/templates/front/' . $template;
        }
        if (file_exists(_PS_MODULE_DIR_ . $this->name . '/' . $template)) {
            return _PS_MODULE_DIR_ . $this->name . '/' . $template;
        }
        return null;
    }
    /**
     * @param string $template
     *
     * @return bool|null|string
     */
    protected function _is_template_overloaded($template)
    {
        return Module::_is_template_overloaded_static($this->name, $template);
    }
    /**
     * @return void
     */
    protected function reset_current_sub_template(string $template, string $cache_id, string $compile_id)
    {
        $this->current_subtemplate[$template . '_' . $cache_id . '_' . $compile_id] = null;
    }
    /**
     * @param string $template
     * @param string|null $cacheId
     * @param string|null $compileId
     *
     * @return bool
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function is_cached($template, $cache_id = null, $compile_id = null)
    {
        if (Tools::get_isset('live_edit') || Tools::get_isset('live_configurator_token')) {
            return false;
        }
        Tools::enable_cache();
        $new_tpl = $this->get_template_path($template);
        $is_cached = $this->get_current_sub_template($template, $cache_id, $compile_id)->is_cached($new_tpl, $cache_id, $compile_id);
        Tools::restore_cache_settings();
        return $is_cached;
    }
    /**
     * Check if the module is transplantable on the hook in parameter
     *
     * @param string $hook_name
     *
     * @return bool if module can be transplanted on hook
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function is_hookable_on($hook_name)
    {
        $retro_hook_name = Hook::get_retro_hook_name($hook_name);
        return is_callable([$this, 'hook' . ucfirst($hook_name)]) || is_callable([$this, 'hook' . ucfirst($retro_hook_name)]);
    }
    /**
     * Check employee permission for module
     *
     * @param string $variable (action)
     * @param object $employee
     *
     * @return bool if module can be transplanted on hook
     *
     * @throws PrestaShopException
     */
    public function get_permission($variable, $employee = null)
    {
        return Module::get_permission_static($this->id, $variable, $employee);
    }
    /**
     * Check employee permission for module (static method)
     *
     * @param int $idModule
     * @param string $variable (action)
     * @param object $employee
     *
     * @return bool if module can be transplanted on hook
     *
     * @throws PrestaShopException
     */
    public static function get_permission_static($id_module, $variable, $employee = null)
    {
        if (!in_array($variable, ['view', 'configure', 'uninstall'])) {
            return false;
        }
        if (!$employee) {
            $employee = Context::get_context()->employee;
        }
        if ($employee->id_profile == _PS_ADMIN_PROFILE_) {
            return true;
        }
        if (!isset(static::$cache_permissions[$employee->id_profile])) {
            static::$cache_permissions[$employee->id_profile] = [];
            $result = Db::read_only()->get_array('SELECT `id_module`, `view`, `configure`, `uninstall` FROM `' . _DB_PREFIX_ . 'module_access` WHERE `id_profile` = ' . (int) $employee->id_profile);
            foreach ($result as $row) {
                static::$cache_permissions[$employee->id_profile][$row['id_module']]['view'] = $row['view'];
                static::$cache_permissions[$employee->id_profile][$row['id_module']]['configure'] = $row['configure'];
                static::$cache_permissions[$employee->id_profile][$row['id_module']]['uninstall'] = $row['uninstall'];
            }
        }
        if (!isset(static::$cache_permissions[$employee->id_profile][$id_module])) {
            throw new Presta_Shop_Exception('No access reference in table module_access for id_module ' . $id_module . '.');
        }
        return (bool) static::$cache_permissions[$employee->id_profile][$id_module][$variable];
    }
    /**
     * Get module errors
     *
     * @return array errors
     */
    public function get_errors()
    {
        return $this->_errors;
    }
    /**
     * Get module messages confirmation
     *
     * @return array conf
     */
    public function get_confirmations()
    {
        return $this->_confirmations;
    }
    /**
     * Get uri path for module
     *
     * @return string
     */
    public function get_path_uri()
    {
        return $this->_path;
    }
    /**
     * Return module position for a given hook
     *
     * @param bool $id_hook Hook ID
     *
     * @return int position
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_position($id_hook)
    {
        $result = Db::read_only()->get_row((new Db_Query())->select('`position`')->from('hook_module')->where('`id_hook` = ' . (int) $id_hook)->where('`id_module` = ' . (int) $this->id)->where('`id_shop` = ' . (int) Context::get_context()->shop->id));
        return $result['position'];
    }
    /**
     * add a warning message to display at the top of the admin page
     *
     * @param string $msg
     */
    public function admin_display_warning($msg): void
    {
        $controller = $this->context->controller;
        if ($controller instanceof Admin_Controller) {
            $controller->warnings[] = $msg;
        } else {
            trigger_error('Method adminDisplayWarning can be called in back-office context only', E_USER_NOTICE);
        }
    }
    /**
     * Return the hooks list where this module can be hooked.
     *
     * @return array Hooks list.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_possible_hooks_list()
    {
        $hooks_list = Hook::get_hooks();
        $possible_hooks_list = [];
        foreach ($hooks_list as &$current_hook) {
            $hook_name = $current_hook['name'];
            $retro_hook_name = Hook::get_retro_hook_name($hook_name);
            if (is_callable([$this, 'hook' . ucfirst((string) $hook_name)]) || is_callable([$this, 'hook' . ucfirst($retro_hook_name)])) {
                $possible_hooks_list[] = ['id_hook' => $current_hook['id_hook'], 'name' => $hook_name, 'title' => $current_hook['title']];
            }
        }
        return $possible_hooks_list;
    }
    /**
     * Return list of displayable hooks where this module can be hooked to
     *
     * By default, only front-office hooks are returned. By setting $includeBackOfficeHooks to true, the result
     * will include even back-office displayable hooks
     *
     * @param bool $includeBackOfficeHooks
     * @return array Hook list
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_displayable_hook_list($include_back_office_hooks = false)
    {
        return array_filter($this->get_possible_hooks_list(), fn(array $hook) => Hook::is_displayable_hook($hook['name'], $include_back_office_hooks));
    }
    /**
     * @param string|null $name
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function get_cache_id($name = null)
    {
        static $suffix;
        if (is_null($suffix)) {
            $cache_array = [];
            if (Configuration::get('PS_SSL_ENABLED')) {
                $cache_array[] = (int) Tools::using_secure_mode();
            }
            if (Shop::is_feature_active()) {
                $cache_array[] = (int) $this->context->shop->id;
            }
            if (Group::is_feature_active() && isset($this->context->customer)) {
                $cache_array[] = (int) Group::get_current()->id;
                $cache_array[] = implode('_', Customer::get_groups_static($this->context->customer->id));
            }
            if (Language::is_multi_language_activated()) {
                $cache_array[] = (int) $this->context->language->id;
            }
            if (Currency::is_multi_currency_activated()) {
                $cache_array[] = (int) $this->context->currency->id;
            }
            $cache_array[] = (int) $this->context->country->id;
            $suffix = '|' . implode('|', $cache_array);
        }
        return ($name ?? $this->name) . $suffix;
    }
    /**
     * @param string $template
     *
     * @return string
     */
    protected function _get_applicable_template_dir($template)
    {
        return $this->_is_template_overloaded($template) ? _PS_THEME_DIR_ : _PS_MODULE_DIR_ . $this->name . '/';
    }
    /**
     * Clear template cache
     *
     * @param string $template Template name
     * @param string|null $cacheId
     * @param int|null $compileId
     *
     * @return false|int Number of template cleared
     *
     * @throws PrestaShopException
     */
    protected function _clear_cache(string $template, $cache_id = null, $compile_id = null)
    {
        static $ps_smarty_clear_cache = null;
        if ($ps_smarty_clear_cache === null) {
            $ps_smarty_clear_cache = Configuration::get('PS_SMARTY_CLEAR_CACHE');
        }
        if (static::$_batch_mode) {
            if ($ps_smarty_clear_cache == 'never') {
                return 0;
            }
            if ($cache_id === null) {
                $cache_id = $this->name;
            }
            $key = $template . '-' . $cache_id . '-' . $compile_id;
            if (!isset(static::$_defered_clear_cache[$key])) {
                static::$_defered_clear_cache[$key] = [$this->get_template_path($template), $cache_id, $compile_id];
            }
        } else {
            if ($ps_smarty_clear_cache == 'never') {
                return 0;
            }
            if ($cache_id === null) {
                $cache_id = $this->name;
            }
            Tools::enable_cache();
            $number_of_template_cleared = Tools::clear_cache(Context::get_context()->smarty, $this->get_template_path($template), $cache_id, $compile_id);
            Tools::restore_cache_settings();
            return $number_of_template_cleared;
        }
        return false;
    }
    /**
     * @throws PrestaShopException
     */
    protected function _generate_config_xml()
    {
        try {
            $xml = new Dom_Document('1.0', 'UTF-8');
            $xml->format_output = true;
            $module_xml = $xml->create_element('module');
            $xml->append_child($module_xml);
            $author_uri = '';
            if (isset($this->author_uri)) {
                $author_uri = $this->author_uri;
            }
            $limited_countries = '';
            if (count($this->limited_countries) == 1) {
                $limited_countries = $this->limited_countries[0];
            }
            $node_data = ['name' => $this->name, 'displayName' => $this->display_name, 'version' => $this->version, 'description' => $this->description, 'author' => $this->author, 'author_uri' => $author_uri, 'tab' => $this->tab, 'confirmUninstall' => $this->confirm_uninstall, 'is_configurable' => $this->is_module_configurable(), 'need_instance' => $this->need_instance, 'limited_countries' => $limited_countries];
            foreach ($node_data as $node => $value) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }
                if (is_string($value) && strlen($value)) {
                    $element = $xml->create_element($node);
                    $element->append_child($xml->create_cdata_section($value));
                } else {
                    $element = $xml->create_element($node, (string) $value);
                }
                $module_xml->append_child($element);
            }
            if (is_writable(_PS_MODULE_DIR_ . $this->name . '/')) {
                $iso = substr((string) Context::get_context()->language->iso_code, 0, 2);
                $file = _PS_MODULE_DIR_ . $this->name . '/' . ($iso == 'en' ? 'config.xml' : 'config_' . $iso . '.xml');
                Tools::delete_file($file);
                @file_put_contents($file, $xml->save_xml());
                @chmod($file, 0664);
            }
        } catch (Dom_Exception $e) {
            throw new Presta_Shop_Exception('Failed to generate module config.xml file', 0, $e);
        }
    }
    /**
     * add a info message to display at the top of the admin page
     *
     * @param string $msg
     *
     * @return void
     */
    protected function admin_display_information($msg)
    {
        $controller = $this->context->controller;
        if ($controller instanceof Admin_Controller) {
            $controller->informations[] = $msg;
        } else {
            trigger_error('Method adminDisplayInformation can be called in back-office context only', E_USER_NOTICE);
        }
    }
    /**
     * Returns reflection class for override file.
     *
     * @param string $classname class name that should be inside override file
     *
     * @param string[] $fileLines override file content
     * @param string $tempClassSuffix suffix for temp class
     * @param string $filename path to override file
     *
     * @throws PrestaShopException
     */
    protected function get_override_file_reflection_class(string $classname, $file_lines, string $temp_class_suffix, $filename): ReflectionClass
    {
        // generate temp class name for override
        $override_class_name = $classname . $temp_class_suffix;
        while (class_exists($override_class_name, false)) {
            $override_class_name = $classname . $temp_class_suffix . uniqid();
        }
        $override_content = preg_replace(['#^\s*<\?(?:php)?#', '#class\s+' . $classname . '(\s+extends\s+([a-z0-9_]+)(\s+implements\s+([a-z0-9_]+))?)?#i'], [' ', 'class ' . $override_class_name . ' extends \stdClass'], implode('', $file_lines));
        try {
            eval($override_content);
        } catch (Throwable $e) {
            $message = $e->get_message() . ' at line ' . $e->get_line();
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Failed to evaluate override file %s: %s'), $filename, $message));
        }
        throw new Presta_Shop_Exception(sprintf(Tools::display_error('Override file %s does not contain class %s'), $filename, $classname));
    }
    /**
     * @return string[]
     * @throws PrestaShopException
     */
    protected function load_override_file(string $file_path)
    {
        if (!file_exists($file_path)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Override file %s does not exists'), $file_path));
        }
        $content_lines = file($file_path);
        if ($content_lines === false) {
            return [];
        }
        // remove empty lines
        return array_filter($content_lines, fn(string $line) => !preg_match("/^\\s*\$/", $line));
    }
    /**
     * Returns true if module can be configured
     *
     * Module can be configured if it implements method getContent()
     *
     * @return bool
     */
    public function is_module_configurable()
    {
        return method_exists($this, 'getContent');
    }
    /**
     * Returns information about modules present on api server
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_api_modules_info()
    {
        if (file_exists(static::MODULES_CACHE_FILE)) {
            $content = file_get_contents(static::MODULES_CACHE_FILE);
            $modules = json_decode($content, true);
            if (is_array($modules)) {
                return $modules;
            }
        }
        return static::check_api_modules_updates(true);
    }
    /**
     * Check for module updates on api server
     *
     * @param bool $force Force check
     *
     * @return false|array Indicates whether the update failed or not needed (returns `false`)
     *                     Otherwise returns the list with modules
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function check_api_modules_updates($force = false)
    {
        $last_check = (int) Configuration::get(static::LAST_MODULES_CHECK);
        $check_interval = (int) Configuration::get(static::MODULES_CHECK_INTERVAL);
        if ($check_interval <= 0) {
            $check_interval = 86400;
        }
        if ($force || $last_check < time() - $check_interval || !file_exists(static::MODULES_CACHE_FILE)) {
            Configuration::update_global_value(static::LAST_MODULES_CHECK, time());
            $guzzle = new Client(['base_uri' => Configuration::get_api_server(), 'verify' => Configuration::get_ssl_trust_store()]);
            try {
                $response = (string) $guzzle->get('updates/modules/all.json', ['headers' => ['X-SID' => Configuration::get_server_tracking_id()]])->get_body();
                $modules = json_decode($response, true);
                $cache = [];
                if ($modules && is_array($modules)) {
                    foreach ($modules as $module_name => &$module) {
                        if (isset($module['versions']['premium']) && is_array($module['versions']['premium'])) {
                            $versions = $module['versions']['premium'];
                            $highest_version = static::find_highest_module_version($versions);
                            if ($highest_version) {
                                $module['premium'] = $module['availableFor'];
                                $module['version'] = $highest_version;
                                $module['binary'] = $versions[$highest_version]['binary'] ?? null;
                                unset($module['versions']);
                                $cache[$module_name] = $module;
                            }
                        } elseif (isset($module['versions']['stable']) && is_array($module['versions']['stable'])) {
                            $versions = $module['versions']['stable'];
                            $highest_version = static::find_highest_module_version($versions);
                            if ($highest_version) {
                                $module['premium'] = false;
                                $module['version'] = $highest_version;
                                $module['binary'] = $versions[$highest_version]['binary'];
                                unset($module['versions']);
                                $cache[$module_name] = $module;
                            }
                        }
                    }
                }
                if (!empty($cache)) {
                    file_put_contents(static::MODULES_CACHE_FILE, json_encode($cache, JSON_PRETTY_PRINT));
                    return $cache;
                }
            } catch (Throwable $e) {
                $error_handler = Service_Locator::get_instance()->get_error_handler();
                $error_handler->log_fatal_error(Error_Utils::describe_exception($e));
            }
        }
        return false;
    }
    /**
     * Find the highest version of a module
     *
     * @param array $moduleVersions Module version info
     *
     * @return string|false Version number, `false` if not found
     *
     * @since 1.0.0
     */
    protected static function find_highest_module_version(array $module_versions)
    {
        $highest = '0.0.0';
        foreach ($module_versions as $version_number => $version_info) {
            if (static::check_module_version_compatibility($version_info)) {
                $version_number = (string) $version_number;
                if (version_compare($version_number, $highest, '>')) {
                    $highest = $version_number;
                }
            }
        }
        return $highest === '0.0.0' ? false : $highest;
    }
    /**
     * @param array $versionInfo
     *
     * @return bool
     */
    protected static function check_module_version_compatibility($version_info)
    {
        if (!is_array($version_info)) {
            return false;
        }
        if (!isset($version_info['compatibility'])) {
            return true;
        }
        $compatibility = $version_info['compatibility'];
        $split = explode(' ', (string) $compatibility);
        if (count($split) === 2) {
            $operator = trim($split[0]);
            $operand = trim($split[1]);
            return version_compare(_TB_VERSION_, $operand, $operator);
        }
        return false;
    }
    /**
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function process_premium_modules(): void
    {
        Module::check_api_modules_updates(true);
        foreach (static::get_modules_on_disk(true) as $module) {
            if ($module->id && $module->premium && !$module->can_install) {
                $instance = static::get_instance_by_id($module->id);
                if (Validate::is_loaded_object($instance)) {
                    $instance->disable(true);
                }
            }
        }
    }
}
function ps_module_version_sort(array $a, array $b): int
{
    return version_compare($a['version'], $b['version']);
}