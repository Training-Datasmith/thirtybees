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
 * Class ShopCore
 */
class Shop_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'shop', 'primary' => 'id_shop', 'fields' => ['id_shop_group' => ['type' => self::TYPE_INT, 'required' => true], 'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64], 'id_category' => ['type' => self::TYPE_INT, 'required' => true, 'dbDefault' => '1'], 'id_theme' => ['type' => self::TYPE_INT, 'required' => true, 'dbType' => 'int(1) unsigned'], 'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'], 'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0']], 'keys' => ['shop' => ['id_category' => ['type' => Object_Model::KEY, 'columns' => ['id_category']], 'id_shop_group' => ['type' => Object_Model::KEY, 'columns' => ['id_shop_group', 'deleted']], 'id_theme' => ['type' => Object_Model::KEY, 'columns' => ['id_theme']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['fields' => ['id_shop_group' => ['xlink_resource' => 'shop_groups'], 'id_category' => [], 'id_theme' => []]];
    /** @var int ID of shop group */
    public $id_shop_group;
    /** @var int ID of shop category */
    public $id_category;
    /** @var int ID of shop theme */
    public $id_theme;
    /** @var string Shop name */
    public $name;
    /** @var bool $active */
    public $active = true;
    /** @var bool $deleted */
    public $deleted;
    /** @var string Shop theme name (read only) */
    public $theme_name;
    /** @var string Shop theme directory (read only) */
    public $theme_directory;
    /** @var string Physical uri of main url (read only) */
    public $physical_uri;
    /** @var string Virtual uri of main url (read only) */
    public $virtual_uri;
    /** @var string Domain of main url (read only) */
    public $domain;
    /** @var string Domain SSL of main url (read only) */
    public $domain_ssl;
    /** @var ShopGroup Shop group object */
    protected $group;
    /** @var array List of shops cached */
    protected static $shops;
    /** @var array $asso_tables */
    protected static $asso_tables = [];
    /** @var array $id_shop_default_tables */
    protected static $id_shop_default_tables = [];
    /** @var bool $initialized */
    protected static $initialized = false;
    /**
     * Store the current context of shop (CONTEXT_ALL, CONTEXT_GROUP, CONTEXT_SHOP)
     *
     * @var int $context ;
     */
    protected static $context;
    /**
     * ID shop in the current context (will be empty if context is not CONTEXT_SHOP)
     *
     * @var int $context_id_shop
     */
    protected static $context_id_shop;
    /**
     * ID shop group in the current context (will be empty if context is CONTEXT_ALL)
     *
     * @var int $context_id_shop_group
     */
    protected static $context_id_shop_group;
    /**
     * There are 3 kinds of shop context : shop, group shop and general
     */
    public const CONTEXT_SHOP = 1;
    public const CONTEXT_GROUP = 2;
    public const CONTEXT_ALL = 4;
    /**
     * Some data can be shared between shops, like customers or orders
     */
    public const SHARE_CUSTOMER = 'share_customer';
    public const SHARE_ORDER = 'share_order';
    public const SHARE_STOCK = 'share_stock';
    /**
     * On shop instance, get its theme and URL data too
     *
     * @param int $id
     * @param int $idLang
     * @param int $idShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
        if ($this->id) {
            $this->set_url();
        }
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_url()
    {
        $cache_id = 'Shop::setUrl_' . (int) $this->id;
        if (!Cache::is_stored($cache_id)) {
            $row = Db::read_only()->get_row((new Db_Query())->select('su.physical_uri, su.virtual_uri, su.domain, su.domain_ssl, t.id_theme, t.name, t.directory')->from('shop', 's')->left_join('shop_url', 'su', 's.`id_shop` = su.`id_shop`')->left_join('theme', 't', 't.`id_theme` = s.`id_theme`')->where('s.`id_shop` = ' . (int) $this->id)->where('s.`active` = 1')->where('s.`deleted` = 0')->where('su.`main` = 1'));
            Cache::store($cache_id, $row);
        } else {
            $row = Cache::retrieve($cache_id);
        }
        if (!$row) {
            return false;
        }
        $this->id_theme = (int) $row['id_theme'];
        $this->theme_name = $row['name'];
        $this->theme_directory = $row['directory'];
        $this->physical_uri = $row['physical_uri'];
        $this->virtual_uri = $row['virtual_uri'];
        $this->domain = $row['domain'];
        $this->domain_ssl = $row['domain_ssl'];
        return true;
    }
    /**
     * Add a shop, and clear the cache
     *
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        $res = parent::add($auto_date, $null_values);
        // Set default language routes
        $langs = Language::get_languages(false, $this->id, true);
        Configuration::update_value('PS_ROUTE_product_rule', array_map(fn() => '{categories:/}{rewrite}', $langs));
        Configuration::update_value('PS_ROUTE_category_rule', array_map(fn() => '{rewrite}', $langs));
        Configuration::update_value('PS_ROUTE_supplier_rule', array_map(fn() => '{rewrite}', $langs));
        Configuration::update_value('PS_ROUTE_manufacturer_rule', array_map(fn() => '{rewrite}', $langs));
        Configuration::update_value('PS_ROUTE_cms_rule', array_map(fn() => '{categories:/}{rewrite}', $langs));
        Configuration::update_value('PS_ROUTE_cms_category_rule', array_map(fn() => '{categories:/}{rewrite}', $langs));
        static::cache_shops(true);
        return $res;
    }
    /**
     * @throws PrestaShopException
     */
    public function associate_super_admins(): void
    {
        $super_admins = Employee::get_employees_by_profile(_PS_ADMIN_PROFILE_);
        foreach ($super_admins as $super_admin) {
            $employee = new Employee((int) $super_admin['id_employee']);
            $employee->associate_to((int) $this->id);
        }
    }
    /**
     * Remove a shop only if it has no dependencies, and remove its associations
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (static::has_dependency($this->id) || !$res = parent::delete()) {
            return false;
        }
        $conn = Db::get_instance();
        foreach (static::get_asso_tables() as $table_name => $row) {
            $id = 'id_' . $row['type'];
            if ($row['type'] == 'fk_shop') {
                $id = 'id_shop';
            } else {
                $table_name .= '_' . $row['type'];
            }
            $res = $conn->delete(bq_sql($table_name), '`' . bq_sql($id) . '`=' . (int) $this->id) && $res;
        }
        // removes stock available
        $res = $conn->delete('stock_available', '`id_shop` = ' . (int) $this->id) && $res;
        // Remove urls
        $res = $conn->delete('shop_url', '`id_shop` = ' . (int) $this->id) && $res;
        // Remove currency restrictions
        $res = $conn->delete('module_currency', '`id_shop` = ' . (int) $this->id) && $res;
        // Remove group restrictions
        $res = $conn->delete('module_group', '`id_shop` = ' . (int) $this->id) && $res;
        // Remove country restrictions
        $res = $conn->delete('module_country', '`id_shop` = ' . (int) $this->id) && $res;
        // Remove carrier restrictions
        $res = $conn->delete('module_carrier', '`id_shop` = ' . (int) $this->id) && $res;
        static::cache_shops(true);
        return $res;
    }
    /**
     * Detect dependency with customer or orders
     *
     * @param int $idShop
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function has_dependency($id_shop)
    {
        $has_dependency = false;
        $connection = Db::read_only();
        $nbr_customer = (int) $connection->get_value((new Db_Query())->select('COUNT(*)')->from('customer')->where('`id_shop` = ' . (int) $id_shop));
        if ($nbr_customer) {
            $has_dependency = true;
        } else {
            $nbr_order = (int) $connection->get_value((new Db_Query())->select('COUNT(*)')->from('orders')->where('`id_shop` = ' . (int) $id_shop));
            if ($nbr_order) {
                $has_dependency = true;
            }
        }
        return $has_dependency;
    }
    /**
     * Find the shop from current domain / uri and get an instance of this shop
     *
     * @return Shop
     * @throws PrestaShopException
     */
    public static function initialize()
    {
        // Find current shop from URL
        if (!($id_shop = Tools::get_int_value('id_shop')) || defined('_PS_ADMIN_DIR_')) {
            $found_uri = '';
            $is_main_uri = false;
            $host = Tools::get_http_host();
            $request_uri = rawurldecode((string) $_SERVER['REQUEST_URI']);
            $result = Db::read_only()->get_array((new Db_Query())->select('s.`id_shop`, CONCAT(su.`physical_uri`, su.`virtual_uri`) AS `uri`, su.`domain`, su.`main`')->from('shop_url', 'su')->left_join('shop', 's', 's.`id_shop` = su.`id_shop`')->where('su.domain = \'' . p_sql($host) . '\' OR su.domain_ssl = \'' . p_sql($host) . '\'')->where('s.`active` = 1')->where('s.`deleted` = 0')->order_by('LENGTH(CONCAT(su.`physical_uri`, su.`virtual_uri`)) DESC'));
            $through = false;
            foreach ($result as $row) {
                // An URL matching current shop was found
                if (preg_match('#^' . preg_quote((string) $row['uri'], '#') . '#i', $request_uri)) {
                    $through = true;
                    $id_shop = $row['id_shop'];
                    $found_uri = $row['uri'];
                    if ($row['main']) {
                        $is_main_uri = true;
                    }
                    break;
                }
            }
            // If an URL was found but is not the main URL, redirect to main URL
            if ($through && $id_shop && !$is_main_uri) {
                foreach ($result as $row) {
                    if ($row['id_shop'] == $id_shop && $row['main']) {
                        $request_uri = substr($request_uri, strlen((string) $found_uri));
                        $url = str_replace('//', '/', $row['domain'] . $row['uri'] . $request_uri);
                        $redirect_type = Configuration::get('PS_CANONICAL_REDIRECT');
                        $redirect_code = $redirect_type == 1 ? '302' : '301';
                        $redirect_header = $redirect_type == 1 ? 'Found' : 'Moved Permanently';
                        header('HTTP/1.0 ' . $redirect_code . ' ' . $redirect_header);
                        header('Cache-Control: no-cache');
                        header('Location: ' . Tools::get_shop_protocol() . $url);
                        exit;
                    }
                }
            }
        }
        $http_host = Tools::get_http_host();
        $all_media = array_merge(Configuration::get_multi_shop_values('PS_MEDIA_SERVER_1'), Configuration::get_multi_shop_values('PS_MEDIA_SERVER_2'), Configuration::get_multi_shop_values('PS_MEDIA_SERVER_3'));
        if (!$id_shop && defined('_PS_ADMIN_DIR_') || Tools::is_phpcli() || in_array($http_host, $all_media)) {
            // If in admin, we can access to the shop without right URL
            if (!$id_shop && Tools::is_phpcli() || defined('_PS_ADMIN_DIR_')) {
                $id_shop = (int) Configuration::get('PS_SHOP_DEFAULT');
            }
            $shop = new Shop((int) $id_shop);
            if (!Validate::is_loaded_object($shop)) {
                $shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
            }
            $shop->virtual_uri = '';
            // Define some $_SERVER variables like HTTP_HOST if PHP is launched with php-cli
            if (Tools::is_phpcli()) {
                if (empty($_SERVER['HTTP_HOST'])) {
                    $_SERVER['HTTP_HOST'] = $shop->domain;
                }
                if (empty($_SERVER['SERVER_NAME'])) {
                    $_SERVER['SERVER_NAME'] = $shop->domain;
                }
                if (empty($_SERVER['REMOTE_ADDR'])) {
                    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
                }
            }
        } else {
            $shop = new Shop($id_shop);
            if (!Validate::is_loaded_object($shop) || !$shop->active) {
                // No shop found ... too bad, let's redirect to default shop
                $default_shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
                // Hmm there is something really bad in your Prestashop !
                if (!Validate::is_loaded_object($default_shop)) {
                    throw new Presta_Shop_Exception('Shop not found');
                }
                $params = $_GET;
                unset($params['id_shop']);
                $url = $default_shop->domain;
                if (!Configuration::get('PS_REWRITING_SETTINGS')) {
                    $url .= $default_shop->get_base_uri() . 'index.php?' . http_build_query($params);
                } else {
                    // Catch url with subdomain "www"
                    if (str_starts_with($url, 'www.') && 'www.' . $_SERVER['HTTP_HOST'] === $url || $_SERVER['HTTP_HOST'] === 'www.' . $url) {
                        $url .= $_SERVER['REQUEST_URI'];
                    } else {
                        $url .= $default_shop->get_base_uri();
                    }
                    if (count($params)) {
                        $url .= '?' . http_build_query($params);
                    }
                }
                $redirect_type = Configuration::get('PS_CANONICAL_REDIRECT');
                $redirect_code = $redirect_type == 1 ? '302' : '301';
                $redirect_header = $redirect_type == 1 ? 'Found' : 'Moved Permanently';
                header('HTTP/1.0 ' . $redirect_code . ' ' . $redirect_header);
                header('Location: ' . Tools::get_shop_protocol() . $url);
                exit;
            }
            if (defined('_PS_ADMIN_DIR_') && empty($shop->physical_uri)) {
                $shop_default = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
                $shop->physical_uri = $shop_default->physical_uri;
                $shop->virtual_uri = $shop_default->virtual_uri;
            }
        }
        static::$context_id_shop = $shop->id;
        static::$context_id_shop_group = $shop->id_shop_group;
        static::$context = static::CONTEXT_SHOP;
        return $shop;
    }
    /**
     * @return Address the current shop address
     *
     * @throws PrestaShopException
     */
    public function get_address()
    {
        return static::get_address_for_shop($this->id);
    }
    /**
     * @param int|null $shopId
     *
     * @return Address
     *
     * @throws PrestaShopException
     */
    public static function get_address_for_shop($shop_id)
    {
        $address = new Address();
        $address->company = Configuration::get('PS_SHOP_NAME', null, null, $shop_id);
        $address->id_country = Configuration::get('PS_SHOP_COUNTRY_ID', null, null, $shop_id) ?: Configuration::get('PS_COUNTRY_DEFAULT', null, null, $shop_id);
        $address->id_state = Configuration::get('PS_SHOP_STATE_ID', null, null, $shop_id);
        $address->address1 = Configuration::get('PS_SHOP_ADDR1', null, null, $shop_id);
        $address->address2 = Configuration::get('PS_SHOP_ADDR2', null, null, $shop_id);
        $address->postcode = Configuration::get('PS_SHOP_CODE', null, null, $shop_id);
        $address->city = Configuration::get('PS_SHOP_CITY', null, null, $shop_id);
        return $address;
    }
    /**
     * Get shop theme name
     *
     * @return string
     */
    public function get_theme()
    {
        return $this->theme_directory;
    }
    /**
     * Get shop URI
     *
     * @return string
     */
    public function get_base_uri()
    {
        return $this->physical_uri . $this->virtual_uri;
    }
    /**
     * Get shop URL
     *
     * @param bool|string $autoSecureMode if set to true, secure mode will be checked
     * @param bool|string $addBaseUri if set to true, shop base uri will be added
     *
     * @return string complete base url of current shop
     */
    public function get_base_url($auto_secure_mode = false, $add_base_uri = true)
    {
        if ($auto_secure_mode && Tools::using_secure_mode() && !$this->domain_ssl || !$this->domain) {
            return false;
        }
        $url = [];
        $url['protocol'] = $auto_secure_mode && Tools::using_secure_mode() ? 'https://' : 'http://';
        $url['domain'] = $auto_secure_mode && Tools::using_secure_mode() ? $this->domain_ssl : $this->domain;
        if ($add_base_uri) {
            $url['base_uri'] = $this->get_base_uri();
        }
        return implode('', $url);
    }
    /**
     * Get group of current shop
     *
     * @return ShopGroup
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_group()
    {
        if (!$this->group) {
            $this->group = new Shop_Group($this->id_shop_group);
        }
        return $this->group;
    }
    /**
     * Get root category of current shop
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function get_category()
    {
        return (int) ($this->id_category ?: Configuration::get('PS_ROOT_CATEGORY'));
    }
    /**
     * Get list of shop's urls
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_urls()
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('shop_url')->where('`active` = 1')->where('`id_shop` = ' . (int) $this->id));
    }
    /**
     * Check if current shop ID is the same as default shop in configuration
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_default_shop()
    {
        return $this->id == Configuration::get('PS_SHOP_DEFAULT');
    }
    /**
     * Get the associated table if available
     *
     * @param string $table
     *
     * @return false|array
     */
    public static function get_asso_table($table)
    {
        if (!static::$initialized) {
            static::init();
        }
        return static::$asso_tables[$table] ?? false;
    }
    /**
     * check if the table has an id_shop_default
     *
     * @param string $table
     *
     * @return bool
     */
    public static function check_id_shop_default($table)
    {
        if (!static::$initialized) {
            static::init();
        }
        return in_array($table, static::$id_shop_default_tables);
    }
    /**
     * Get list of associated tables to shop
     *
     * @return array
     */
    public static function get_asso_tables()
    {
        if (!static::$initialized) {
            static::init();
        }
        return static::$asso_tables;
    }
    /**
     * Add table associated to shop
     *
     * @param string $tableName
     * @param array $tableDetails
     *
     * @return bool
     */
    public static function add_table_association($table_name, $table_details)
    {
        if (!isset(static::$asso_tables[$table_name])) {
            static::$asso_tables[$table_name] = $table_details;
        } else {
            return false;
        }
        return true;
    }
    /**
     * Check if given table is associated to shop
     *
     * @param string $table
     *
     * @return bool
     */
    public static function is_table_associated($table)
    {
        if (!static::$initialized) {
            static::init();
        }
        return isset(static::$asso_tables[$table]) && static::$asso_tables[$table]['type'] == 'shop';
    }
    /**
     * Load list of groups and shops, and cache it
     *
     * @param bool $refresh
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function cache_shops($refresh = false): void
    {
        if (!is_null(static::$shops) && !$refresh) {
            return;
        }
        static::$shops = [];
        $employee = Context::get_context()->employee;
        $sql = (new Db_Query())->select('gs.*, s.*, gs.`name` AS `group_name`, s.`name` AS `shop_name`, s.`active`')->select('su.`domain`, su.`domain_ssl`, su.`physical_uri`, su.`virtual_uri`')->from('shop_group', 'gs')->left_join('shop', 's', 's.`id_shop_group` = gs.`id_shop_group`')->left_join('shop_url', 'su', 's.`id_shop` = su.`id_shop` AND su.`main` = 1')->where('s.`deleted` = 0')->where('gs.`deleted` = 0')->order_by('gs.`name`, s.`name`');
        // If the profile isn't a superAdmin
        if (Validate::is_loaded_object($employee) && $employee->id_profile != _PS_ADMIN_PROFILE_) {
            $sql->left_join('employee_shop', 'es', 'es.`id_shop` = s.`id_shop`');
            $sql->where('es.`id_employee` = ' . (int) $employee->id);
        }
        if ($results = Db::read_only()->get_array($sql)) {
            foreach ($results as $row) {
                if (!isset(static::$shops[$row['id_shop_group']])) {
                    static::$shops[$row['id_shop_group']] = ['id' => $row['id_shop_group'], 'name' => $row['group_name'], 'share_customer' => $row['share_customer'], 'share_order' => $row['share_order'], 'share_stock' => $row['share_stock'], 'shops' => []];
                }
                static::$shops[$row['id_shop_group']]['shops'][$row['id_shop']] = ['id_shop' => $row['id_shop'], 'id_shop_group' => $row['id_shop_group'], 'name' => $row['shop_name'], 'id_theme' => $row['id_theme'], 'id_category' => $row['id_category'], 'domain' => $row['domain'], 'domain_ssl' => $row['domain_ssl'], 'uri' => $row['physical_uri'] . $row['virtual_uri'], 'active' => $row['active']];
            }
        }
    }
    /**
     * @return array|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_complete_list_of_shops_id()
    {
        $cache_id = 'Shop::getCompleteListOfShopsID';
        if (!Cache::is_stored($cache_id)) {
            $list = [];
            foreach (Db::read_only()->get_array((new Db_Query())->select('`id_shop`')->from('shop')) as $row) {
                $list[] = $row['id_shop'];
            }
            Cache::store($cache_id, $list);
            return $list;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Get shops list
     *
     * @param bool $active
     * @param int $idShopGroup
     * @param bool $getAsListId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_shops($active = true, $id_shop_group = null, $get_as_list_id = false)
    {
        static::cache_shops();
        $results = [];
        foreach (static::$shops as $id_group => $group_data) {
            foreach ($group_data['shops'] as $id => $shop_data) {
                if ((!$active || $shop_data['active']) && (!$id_shop_group || $id_shop_group == $id_group)) {
                    if ($get_as_list_id) {
                        $results[$id] = $id;
                    } else {
                        $results[$id] = $shop_data;
                    }
                }
            }
        }
        return $results;
    }
    /**
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_urls_shared_cart()
    {
        if (!$this->get_group()->share_order) {
            return false;
        }
        $query = new Db_Query();
        $query->select('domain');
        $query->from('shop_url');
        $query->where('main = 1');
        $query->where('active = 1');
        $query .= static::add_sql_restriction(self::SHARE_ORDER);
        $domains = [];
        foreach (Db::read_only()->get_array($query) as $row) {
            $domains[] = $row['domain'];
        }
        return $domains;
    }
    /**
     * Get a collection of shops
     *
     * @param bool $active
     * @param int $idShopGroup
     *
     * @return PrestaShopCollection Collection of Shop
     *
     * @throws PrestaShopException
     */
    public static function get_shops_collection($active = true, $id_shop_group = null)
    {
        $shops = new Presta_Shop_Collection('Shop');
        if ($active) {
            $shops->where('active', '=', 1);
        }
        if ($id_shop_group) {
            $shops->where('id_shop_group', '=', (int) $id_shop_group);
        }
        return $shops;
    }
    /**
     * Return some informations cached for one shop
     *
     * @param int $shopId
     *
     * @return false|array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_shop($shop_id)
    {
        static::cache_shops();
        foreach (static::$shops as $group_data) {
            if (array_key_exists($shop_id, $group_data['shops'])) {
                return $group_data['shops'][$shop_id];
            }
        }
        return false;
    }
    /**
     * Return a shop ID from shop name
     *
     * @param string $name
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_id_by_name($name)
    {
        static::cache_shops();
        foreach (static::$shops as $group_data) {
            foreach ($group_data['shops'] as $id_shop => $shop_data) {
                if (mb_strtolower((string) $shop_data['name']) == mb_strtolower($name)) {
                    return $id_shop;
                }
            }
        }
        return false;
    }
    /**
     * @param bool $active
     * @param int|null $idShopGroup
     *
     * @return int Total of shops
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_total_shops($active = true, $id_shop_group = null)
    {
        return count(static::get_shops($active, $id_shop_group));
    }
    /**
     * Retrieve group ID of a shop
     *
     * @param int $shopId Shop ID
     * @param bool $asId
     *
     * @return int|array|false Group ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_group_from_shop($shop_id, $as_id = true)
    {
        static::cache_shops();
        foreach (static::$shops as $group_id => $group_data) {
            if (array_key_exists($shop_id, $group_data['shops'])) {
                return $as_id ? $group_id : $group_data;
            }
        }
        return false;
    }
    /**
     * If the shop group has the option $type activated, get all shops ID of this group, else get current shop ID
     *
     * @param int $shopId
     * @param int $type self::SHARE_CUSTOMER | self::SHARE_ORDER
     *
     * @return array
     * @throws PrestaShopException
     */
    public static function get_shared_shops($shop_id, $type)
    {
        if (!in_array($type, [self::SHARE_CUSTOMER, self::SHARE_ORDER, self::SHARE_STOCK])) {
            throw new Presta_Shop_Exception('Wrong argument ($type) in Shop::getSharedShops() method');
        }
        static::cache_shops();
        foreach (static::$shops as $group_data) {
            if (array_key_exists($shop_id, $group_data['shops']) && $group_data[$type]) {
                return array_keys($group_data['shops']);
            }
        }
        return [$shop_id];
    }
    /**
     * Get a list of ID concerned by the shop context (E.g. if context is shop group, get list of children shop ID)
     *
     * @param string|false $share If false, dont check share datas from group. Else can take a Shop::SHARE_* constant value
     *
     * @return int[]
     *
     * @throws PrestaShopException
     */
    public static function get_context_list_shop_id($share = false)
    {
        if (static::get_context() == self::CONTEXT_SHOP) {
            $list = $share ? static::get_shared_shops(static::get_context_shop_id(), $share) : [static::get_context_shop_id()];
        } elseif (static::get_context() == self::CONTEXT_GROUP) {
            $list = static::get_shops(true, static::get_context_shop_group_id(), true);
        } else {
            $list = static::get_shops(true, null, true);
        }
        return $list;
    }
    /**
     * Return the list of shop by id
     *
     * @param int $id
     * @param string $identifier
     * @param string $table
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_shop_by_id($id, $identifier, $table)
    {
        return Db::read_only()->get_array((new Db_Query())->select('`id_shop`, `' . bq_sql($identifier) . '`')->from(bq_sql($table) . '_shop')->where('`' . bq_sql($identifier) . '` = ' . (int) $id));
    }
    /**
     * Change the current shop context
     *
     * @param int $type Shop::CONTEXT_ALL | Shop::CONTEXT_GROUP | Shop::CONTEXT_SHOP
     * @param int $id ID shop if CONTEXT_SHOP or id shop group if CONTEXT_GROUP
     *
     * @throws PrestaShopException
     */
    public static function set_context($type, $id = null): void
    {
        switch ($type) {
            case static::CONTEXT_ALL:
                static::$context_id_shop = null;
                static::$context_id_shop_group = null;
                break;
            case static::CONTEXT_GROUP:
                static::$context_id_shop = null;
                static::$context_id_shop_group = (int) $id;
                break;
            case static::CONTEXT_SHOP:
                static::$context_id_shop = (int) $id;
                static::$context_id_shop_group = static::get_group_from_shop($id);
                break;
            default:
                throw new Presta_Shop_Exception('Unknown context for shop');
        }
        static::$context = $type;
    }
    /**
     * Get current context of shop
     *
     * @return int
     */
    public static function get_context()
    {
        return static::$context;
    }
    /**
     * Get current ID of shop if context is CONTEXT_SHOP
     *
     * @param bool $nullValueWithoutMultishop
     *
     * @return int
     * @throws PrestaShopException
     */
    public static function get_context_shop_id($null_value_without_multishop = false)
    {
        if ($null_value_without_multishop && !static::is_feature_active()) {
            return null;
        }
        return static::$context_id_shop;
    }
    /**
     * Get current ID of shop group if context is CONTEXT_SHOP or CONTEXT_GROUP
     *
     * @param bool $nullValueWithoutMultishop
     *
     * @return int
     * @throws PrestaShopException
     */
    public static function get_context_shop_group_id($null_value_without_multishop = false)
    {
        if ($null_value_without_multishop && !static::is_feature_active()) {
            return null;
        }
        return static::$context_id_shop_group;
    }
    /**
     * @return ShopGroup|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_context_shop_group()
    {
        static $context_shop_group = null;
        if ($context_shop_group === null) {
            $context_shop_group = new Shop_Group((int) static::$context_id_shop_group);
        }
        return $context_shop_group;
    }
    /**
     * Add an sql restriction for shops fields
     *
     * @param bool $share If false, dont check share datas from group. Else can take a Shop::SHARE_* constant value
     * @param string $alias
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function add_sql_restriction($share = false, $alias = null)
    {
        return ' AND ' . static::get_sql_restriction($share, $alias) . ' ';
    }
    /**
     * Returns sql restriction for shops fields
     *
     * @param string|false $share If false, dont check share datas from group. Else can take a Shop::SHARE_* constant value
     * @param string $alias
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_sql_restriction($share = false, $alias = null)
    {
        if ($alias) {
            $alias .= '.';
        }
        $group = static::get_group_from_shop(static::get_context_shop_id(), false);
        if ($share == self::SHARE_CUSTOMER && static::get_context() == self::CONTEXT_SHOP && $group['share_customer']) {
            return $alias . 'id_shop_group = ' . (int) static::get_context_shop_group_id();
        }
        $shop_ids = static::get_context_list_shop_id($share);
        if ($shop_ids && count($shop_ids) == 1) {
            return $alias . '`id_shop` = ' . (int) reset($shop_ids);
        }
        return $alias . '`id_shop` IN (' . implode(', ', static::get_context_list_shop_id($share)) . ')';
    }
    /**
     * Add an SQL JOIN in query between a table and its associated table in multishop
     *
     * @param string $table Table name (E.g. product, module, etc.)
     * @param string $alias Alias of table
     * @param bool $innerJoin Use or not INNER JOIN
     * @param string $on
     * @param bool $forceNotDefault
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function add_sql_association($table, $alias, $inner_join = true, $on = null, $force_not_default = false)
    {
        $table_alias = $table . '_shop';
        if (str_contains($table, '.')) {
            [$table_alias, $table] = explode('.', $table);
        }
        $asso_table = static::get_asso_table($table);
        if ($asso_table === false || $asso_table['type'] != 'shop') {
            return '';
        }
        $sql = ($inner_join ? ' INNER' : ' LEFT') . ' JOIN ' . _DB_PREFIX_ . $table . '_shop ' . $table_alias . '
		ON (' . $table_alias . '.id_' . $table . ' = ' . $alias . '.id_' . $table;
        if ((int) static::$context_id_shop) {
            $sql .= ' AND ' . $table_alias . '.id_shop = ' . (int) static::$context_id_shop;
        } elseif (static::check_id_shop_default($table) && !$force_not_default) {
            $sql .= ' AND ' . $table_alias . '.id_shop = ' . $alias . '.id_shop_default';
        } else {
            $sql .= ' AND ' . $table_alias . '.id_shop IN (' . implode(', ', static::get_context_list_shop_id()) . ')';
        }
        return $sql . ($on ? ' AND ' . $on : '' . ')');
    }
    /**
     * Add a restriction on id_shop for multishop lang table
     *
     * @param string $alias
     * @param int|null $idShop
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function add_sql_restriction_on_lang($alias = null, $id_shop = null)
    {
        if (isset(Context::get_context()->shop) && is_null($id_shop)) {
            $id_shop = (int) Context::get_context()->shop->id;
        }
        if (!$id_shop) {
            $id_shop = (int) Configuration::get('PS_SHOP_DEFAULT');
        }
        return ' AND ' . ($alias ? $alias . '.' : '') . 'id_shop = ' . $id_shop . ' ';
    }
    /**
     * Get all groups and associated shops as subarrays
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_tree()
    {
        static::cache_shops();
        return static::$shops;
    }
    /**
     * @return bool Return true if multishop feature is active and at last 2 shops have been created
     *
     * @throws PrestaShopException
     */
    public static function is_feature_active()
    {
        static $feature_active = null;
        if ($feature_active === null) {
            $connection = Db::read_only();
            $feature_active = $connection->get_value('SELECT value FROM `' . _DB_PREFIX_ . 'configuration` WHERE `name` = "PS_MULTISHOP_FEATURE_ACTIVE"') && $connection->get_value('SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'shop') > 1;
        }
        return $feature_active;
    }
    /**
     * @param int $oldId
     * @param array $tablesImport
     * @param bool $deleted
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function copy_shop_data($old_id, $tables_import = [], $deleted = false): void
    {
        // If we duplicate some specific data, automatically duplicate other data linked to the first
        // E.g. if carriers are duplicated for the shop, duplicate carriers langs too
        if (!$old_id) {
            $old_id = Configuration::get('PS_SHOP_DEFAULT');
        }
        if (!is_array($tables_import)) {
            $tables_import = [];
        }
        if (isset($tables_import['carrier'])) {
            $tables_import['carrier_tax_rules_group_shop'] = true;
            $tables_import['carrier_lang'] = true;
        }
        if (isset($tables_import['cms'])) {
            $tables_import['cms_lang'] = true;
            $tables_import['cms_category'] = true;
            $tables_import['cms_category_lang'] = true;
        }
        $tables_import['category_lang'] = true;
        if (isset($tables_import['product'])) {
            $tables_import['product_lang'] = true;
        }
        if (isset($tables_import['module'])) {
            $tables_import['module_currency'] = true;
            $tables_import['module_country'] = true;
            $tables_import['module_group'] = true;
        }
        if (isset($tables_import['hook_module'])) {
            $tables_import['hook_module_exceptions'] = true;
        }
        if (isset($tables_import['attribute_group'])) {
            $tables_import['attribute'] = true;
        }
        // Browse and duplicate data
        foreach (static::get_asso_tables() as $table_name => $row) {
            if ($tables_import && !isset($tables_import[$table_name])) {
                continue;
            }
            // Special case for stock_available if current shop is in a share stock group
            if ($table_name == 'stock_available') {
                $group = new Shop_Group($this->id_shop_group);
                if ($group->share_stock && $group->have_shops()) {
                    continue;
                }
            }
            $id = 'id_' . $row['type'];
            if ($row['type'] == 'fk_shop') {
                $id = 'id_shop';
            } else {
                $table_name .= '_' . $row['type'];
            }
            if (!$deleted) {
                $res = Db::read_only()->get_row('SELECT * FROM `' . _DB_PREFIX_ . $table_name . '` WHERE `' . $id . '` = ' . (int) $old_id);
                if ($res) {
                    unset($res[$id]);
                    if (isset($row['primary'])) {
                        unset($res[$row['primary']]);
                    }
                    $categories = Tools::get_array_value('categoryBox');
                    if ($table_name == 'product_shop' && count($categories) == 1) {
                        unset($res['id_category_default']);
                        $keys = implode('`, `', array_keys($res));
                        $sql = 'INSERT IGNORE INTO `' . _DB_PREFIX_ . $table_name . '` (`' . $keys . '`, `id_category_default`, ' . $id . ')
								(SELECT `' . $keys . '`, ' . (int) $categories[0] . ', ' . (int) $this->id . ' FROM ' . _DB_PREFIX_ . $table_name . '
								WHERE `' . $id . '` = ' . (int) $old_id . ')';
                    } else {
                        $keys = implode('`, `', array_keys($res));
                        $sql = 'INSERT IGNORE INTO `' . _DB_PREFIX_ . $table_name . '` (`' . $keys . '`, ' . $id . ')
								(SELECT `' . $keys . '`, ' . (int) $this->id . ' FROM ' . _DB_PREFIX_ . $table_name . '
								WHERE `' . $id . '` = ' . (int) $old_id . ')';
                    }
                    Db::get_instance()->execute($sql);
                }
            }
        }
        // Hook for duplication of shop data
        $modules_list = Hook::get_hook_module_exec_list('actionShopDataDuplication');
        if (is_array($modules_list) && count($modules_list) > 0) {
            foreach ($modules_list as $m) {
                if (isset($tables_import['Module' . ucfirst((string) $m['module'])])) {
                    Hook::trigger_event('actionShopDataDuplication', ['old_id_shop' => (int) $old_id, 'new_id_shop' => (int) $this->id], null, (int) $m['id_module']);
                }
            }
        }
    }
    /**
     * @param int $id
     * @param bool $onlyId
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_categories($id = 0, $only_id = true)
    {
        // build query
        $query = new Db_Query();
        if ($only_id) {
            $query->select('cs.`id_category`');
        } else {
            $query->select('DISTINCT cs.`id_category`, cl.`name`, cl.`link_rewrite`');
        }
        $query->from('category_shop', 'cs');
        $query->left_join('category_lang', 'cl', 'cl.`id_category` = cs.`id_category` AND cl.`id_lang` = ' . (int) Context::get_context()->language->id);
        $query->where('cs.`id_shop` = ' . (int) $id);
        $result = Db::read_only()->get_array($query);
        if ($only_id) {
            $array = [];
            foreach ($result as $row) {
                $array[] = $row['id_category'];
            }
            $array = array_unique($array);
        } else {
            return $result;
        }
        return $array;
    }
    /**
     * @deprecated 2.0.0 Use shop->id
     */
    public static function get_current_shop()
    {
        Tools::display_as_deprecated();
        return Context::get_context()->shop->id;
    }
    /**
     * @param string $entity
     * @param int $idShop
     * @param bool $active
     * @param bool $delete
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_entity_ids($entity, $id_shop, $active = false, $delete = false)
    {
        if (!static::is_table_associated($entity)) {
            return false;
        }
        return Db::read_only()->get_array((new Db_Query())->select('entity.`id_' . bq_sql($entity) . '`')->from(bq_sql($entity) . '_shop', 'es')->left_join(bq_sql($entity), 'entity', 'entity.`id_' . bq_sql($entity) . '` = es.`id_' . bq_sql($entity) . '`')->where('es.`id_shop` = ' . (int) $id_shop)->where($active ? 'entity.`active` = 1' : '')->where($delete ? 'entity.deleted = 0' : ''));
    }
    /**
     * Initialize an array with all the multistore associations in the database
     *
     * @return void
     */
    protected static function init()
    {
        static::$id_shop_default_tables = ['product', 'category'];
        $asso_tables = ['carrier' => ['type' => 'shop'], 'carrier_lang' => ['type' => 'fk_shop'], 'category' => ['type' => 'shop'], 'category_lang' => ['type' => 'fk_shop'], 'cms' => ['type' => 'shop'], 'cms_lang' => ['type' => 'fk_shop'], 'cms_category' => ['type' => 'shop'], 'cms_category_lang' => ['type' => 'fk_shop'], 'contact' => ['type' => 'shop'], 'country' => ['type' => 'shop'], 'currency' => ['type' => 'shop'], 'employee' => ['type' => 'shop'], 'hook_module' => ['type' => 'fk_shop'], 'hook_module_exceptions' => ['type' => 'fk_shop', 'primary' => 'id_hook_module_exceptions'], 'image' => ['type' => 'shop'], 'lang' => ['type' => 'shop'], 'meta_lang' => ['type' => 'fk_shop'], 'module' => ['type' => 'shop'], 'module_currency' => ['type' => 'fk_shop'], 'module_country' => ['type' => 'fk_shop'], 'module_group' => ['type' => 'fk_shop'], 'product' => ['type' => 'shop'], 'product_attribute' => ['type' => 'shop'], 'product_lang' => ['type' => 'fk_shop'], 'referrer' => ['type' => 'shop'], 'scene' => ['type' => 'shop'], 'store' => ['type' => 'shop'], 'webservice_account' => ['type' => 'shop'], 'warehouse' => ['type' => 'shop'], 'stock_available' => ['type' => 'fk_shop', 'primary' => 'id_stock_available'], 'carrier_tax_rules_group_shop' => ['type' => 'fk_shop'], 'attribute' => ['type' => 'shop'], 'feature' => ['type' => 'shop'], 'group' => ['type' => 'shop'], 'attribute_group' => ['type' => 'shop'], 'tax_rules_group' => ['type' => 'shop'], 'zone' => ['type' => 'shop'], 'manufacturer' => ['type' => 'shop'], 'supplier' => ['type' => 'shop']];
        foreach ($asso_tables as $table_name => $table_details) {
            static::add_table_association($table_name, $table_details);
        }
        static::$initialized = true;
    }
}