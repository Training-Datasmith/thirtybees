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
use Thirtybees\Core\Database\Read_Only_Connection;
/**
 * Class ConfigurationCore
 */
class Configuration_Core extends Object_Model
{
    // Default configuration consts
    // @since 1.0.1
    // Benefit of these constants unclear. --Traumflug 2018-12-18
    public const SEARCH_INDEXATION = 'PS_SEARCH_INDEXATION';
    public const ONE_PHONE_AT_LEAST = 'PS_ONE_PHONE_AT_LEAST';
    public const GROUP_FEATURE_ACTIVE = 'PS_GROUP_FEATURE_ACTIVE';
    public const CARRIER_DEFAULT = 'PS_CARRIER_DEFAULT';
    public const CURRENCY_DEFAULT = 'PS_CURRENCY_DEFAULT';
    public const COUNTRY_DEFAULT = 'PS_COUNTRY_DEFAULT';
    public const REWRITING_SETTINGS = 'PS_REWRITING_SETTINGS';
    public const ORDER_OUT_OF_STOCK = 'PS_ORDER_OUT_OF_STOCK';
    public const LAST_QTIES = 'PS_LAST_QTIES';
    public const CART_REDIRECT = 'PS_CART_REDIRECT';
    public const CONDITIONS = 'PS_CONDITIONS';
    public const RECYCLABLE_PACK = 'PS_RECYCLABLE_PACK';
    public const GIFT_WRAPPING = 'PS_GIFT_WRAPPING';
    public const GIFT_WRAPPING_PRICE = 'PS_GIFT_WRAPPING_PRICE';
    public const STOCK_MANAGEMENT = 'PS_STOCK_MANAGEMENT';
    public const NAVIGATION_PIPE = 'PS_NAVIGATION_PIPE';
    public const PRODUCTS_PER_PAGE = 'PS_PRODUCTS_PER_PAGE';
    public const PURCHASE_MINIMUM = 'PS_PURCHASE_MINIMUM';
    public const PRODUCTS_ORDER_WAY = 'PS_PRODUCTS_ORDER_WAY';
    public const PRODUCTS_ORDER_BY = 'PS_PRODUCTS_ORDER_BY';
    public const SHIPPING_HANDLING = 'PS_SHIPPING_HANDLING';
    public const SHIPPING_FREE_PRICE = 'PS_SHIPPING_FREE_PRICE';
    public const SHIPPING_FREE_WEIGHT = 'PS_SHIPPING_FREE_WEIGHT';
    public const SHIPPING_METHOD = 'PS_SHIPPING_METHOD';
    public const TAX = 'PS_TAX';
    public const SHOP_ENABLE = 'PS_SHOP_ENABLE';
    public const NB_DAYS_NEW_PRODUCT = 'PS_NB_DAYS_NEW_PRODUCT';
    public const SSL_ENABLED = 'PS_SSL_ENABLED';
    public const WEIGHT_UNIT = 'PS_WEIGHT_UNIT';
    public const BLOCK_CART_AJAX = 'PS_BLOCK_CART_AJAX';
    public const ORDER_RETURN = 'PS_ORDER_RETURN';
    public const ORDER_RETURN_NB_DAYS = 'PS_ORDER_RETURN_NB_DAYS';
    public const MAIL_TYPE = 'PS_MAIL_TYPE';
    public const PRODUCT_PICTURE_MAX_SIZE = 'PS_PRODUCT_PICTURE_MAX_SIZE';
    public const PRODUCT_PICTURE_WIDTH = 'PS_PRODUCT_PICTURE_WIDTH';
    public const PRODUCT_PICTURE_HEIGHT = 'PS_PRODUCT_PICTURE_HEIGHT';
    public const INVOICE_PREFIX = 'PS_INVOICE_PREFIX';
    public const INVCE_INVOICE_ADDR_RULES = 'PS_INVCE_INVOICE_ADDR_RULES';
    public const INVCE_DELIVERY_ADDR_RULES = 'PS_INVCE_DELIVERY_ADDR_RULES';
    public const DELIVERY_PREFIX = 'PS_DELIVERY_PREFIX';
    public const DELIVERY_NUMBER = 'PS_DELIVERY_NUMBER';
    public const RETURN_PREFIX = 'PS_RETURN_PREFIX';
    public const INVOICE = 'PS_INVOICE';
    public const PASSWD_TIME_BACK = 'PS_PASSWD_TIME_BACK';
    public const PASSWD_TIME_FRONT = 'PS_PASSWD_TIME_FRONT';
    public const DISP_UNAVAILABLE_ATTR = 'PS_DISP_UNAVAILABLE_ATTR';
    public const SEARCH_MINWORDLEN = 'PS_SEARCH_MINWORDLEN';
    public const SEARCH_BLACKLIST = 'PS_SEARCH_BLACKLIST';
    public const SEARCH_WEIGHT_PNAME = 'PS_SEARCH_WEIGHT_PNAME';
    public const SEARCH_WEIGHT_REF = 'PS_SEARCH_WEIGHT_REF';
    public const SEARCH_WEIGHT_SHORTDESC = 'PS_SEARCH_WEIGHT_SHORTDESC';
    public const SEARCH_WEIGHT_DESC = 'PS_SEARCH_WEIGHT_DESC';
    public const SEARCH_WEIGHT_CNAME = 'PS_SEARCH_WEIGHT_CNAME';
    public const SEARCH_WEIGHT_MNAME = 'PS_SEARCH_WEIGHT_MNAME';
    public const SEARCH_WEIGHT_TAG = 'PS_SEARCH_WEIGHT_TAG';
    public const SEARCH_WEIGHT_ATTRIBUTE = 'PS_SEARCH_WEIGHT_ATTRIBUTE';
    public const SEARCH_WEIGHT_FEATURE = 'PS_SEARCH_WEIGHT_FEATURE';
    public const SEARCH_AJAX = 'PS_SEARCH_AJAX';
    public const TIMEZONE = 'PS_TIMEZONE';
    public const THEME_V11 = 'PS_THEME_V11';
    public const TIN_ACTIVE = 'PS_TIN_ACTIVE';
    public const SHOW_ALL_MODULES = 'PS_SHOW_ALL_MODULES';
    public const BACKUP_ALL = 'PS_BACKUP_ALL';
    public const PRICE_ROUND_MODE = 'PS_PRICE_ROUND_MODE';
    public const CONDITIONS_CMS_ID = 'PS_CONDITIONS_CMS_ID';
    public const TRACKING_DIRECT_TRAFFIC = 'TRACKING_DIRECT_TRAFFIC';
    public const META_KEYWORDS = 'PS_META_KEYWORDS';
    public const DISPLAY_JQZOOM = 'PS_DISPLAY_JQZOOM';
    public const VOLUME_UNIT = 'PS_VOLUME_UNIT';
    public const CIPHER_ALGORITHM = 'PS_CIPHER_ALGORITHM';
    public const ATTRIBUTE_CATEGORY_DISPLAY = 'PS_ATTRIBUTE_CATEGORY_DISPLAY';
    public const CUSTOMER_SERVICE_FILE_UPLOAD = 'PS_CUSTOMER_SERVICE_FILE_UPLOAD';
    public const CUSTOMER_SERVICE_SIGNATURE = 'PS_CUSTOMER_SERVICE_SIGNATURE';
    public const BLOCK_BESTSELLERS_DISPLAY = 'PS_BLOCK_BESTSELLERS_DISPLAY';
    public const BLOCK_NEWPRODUCTS_DISPLAY = 'PS_BLOCK_NEWPRODUCTS_DISPLAY';
    public const BLOCK_SPECIALS_DISPLAY = 'PS_BLOCK_SPECIALS_DISPLAY';
    public const STOCK_MVT_REASON_DEFAULT = 'PS_STOCK_MVT_REASON_DEFAULT';
    public const COMPARATOR_MAX_ITEM = 'PS_COMPARATOR_MAX_ITEM';
    public const ORDER_PROCESS_TYPE = 'PS_ORDER_PROCESS_TYPE';
    public const SPECIFIC_PRICE_PRIORITIES = 'PS_SPECIFIC_PRICE_PRIORITIES';
    public const TAX_DISPLAY = 'PS_TAX_DISPLAY';
    public const SMARTY_FORCE_COMPILE = 'PS_SMARTY_FORCE_COMPILE';
    public const DISTANCE_UNIT = 'PS_DISTANCE_UNIT';
    public const STORES_DISPLAY_CMS = 'PS_STORES_DISPLAY_CMS';
    public const STORES_DISPLAY_FOOTER = 'PS_STORES_DISPLAY_FOOTER';
    public const STORES_SIMPLIFIED = 'PS_STORES_SIMPLIFIED';
    public const SHOP_LOGO_WIDTH = 'SHOP_LOGO_WIDTH';
    public const SHOP_LOGO_HEIGHT = 'SHOP_LOGO_HEIGHT';
    public const EDITORIAL_IMAGE_WIDTH = 'EDITORIAL_IMAGE_WIDTH';
    public const EDITORIAL_IMAGE_HEIGHT = 'EDITORIAL_IMAGE_HEIGHT';
    public const STATSDATA_CUSTOMER_PAGESVIEWS = 'PS_STATSDATA_CUSTOMER_PAGESVIEWS';
    public const STATSDATA_PAGESVIEWS = 'PS_STATSDATA_PAGESVIEWS';
    public const STATSDATA_PLUGINS = 'PS_STATSDATA_PLUGINS';
    public const GEOLOCATION_ENABLED = 'PS_GEOLOCATION_ENABLED';
    public const ALLOWED_COUNTRIES = 'PS_ALLOWED_COUNTRIES';
    public const GEOLOCATION_BEHAVIOR = 'PS_GEOLOCATION_BEHAVIOR';
    public const LOCALE_LANGUAGE = 'PS_LOCALE_LANGUAGE';
    public const LOCALE_COUNTRY = 'PS_LOCALE_COUNTRY';
    public const ATTACHMENT_MAXIMUM_SIZE = 'PS_ATTACHMENT_MAXIMUM_SIZE';
    public const SMARTY_CACHE = 'PS_SMARTY_CACHE';
    public const DIMENSION_UNIT = 'PS_DIMENSION_UNIT';
    public const GUEST_CHECKOUT_ENABLED = 'PS_GUEST_CHECKOUT_ENABLED';
    public const DISPLAY_SUPPLIERS = 'PS_DISPLAY_SUPPLIERS';
    public const DISPLAY_BEST_SELLERS = 'PS_DISPLAY_BEST_SELLERS';
    public const CATALOG_MODE = 'PS_CATALOG_MODE';
    public const GEOLOCATION_WHITELIST = 'PS_GEOLOCATION_WHITELIST';
    public const LOGS_BY_EMAIL = 'PS_LOGS_BY_EMAIL';
    public const COOKIE_CHECKIP = 'PS_COOKIE_CHECKIP';
    public const STORES_CENTER_LAT = 'PS_STORES_CENTER_LAT';
    public const STORES_CENTER_LONG = 'PS_STORES_CENTER_LONG';
    public const USE_ECOTAX = 'PS_USE_ECOTAX';
    public const CANONICAL_REDIRECT = 'PS_CANONICAL_REDIRECT';
    public const IMG_UPDATE_TIME = 'PS_IMG_UPDATE_TIME';
    public const BACKUP_DROP_TABLE = 'PS_BACKUP_DROP_TABLE';
    public const OS_PAYMENT = 'PS_OS_PAYMENT';
    public const OS_PREPARATION = 'PS_OS_PREPARATION';
    public const OS_SHIPPING = 'PS_OS_SHIPPING';
    public const OS_DELIVERED = 'PS_OS_DELIVERED';
    public const OS_CANCELED = 'PS_OS_CANCELED';
    public const OS_REFUND = 'PS_OS_REFUND';
    public const OS_ERROR = 'PS_OS_ERROR';
    public const OS_OUTOFSTOCK = 'PS_OS_OUTOFSTOCK';
    public const OS_BANKWIRE = 'PS_OS_BANKWIRE';
    public const OS_PAYPAL = 'PS_OS_PAYPAL';
    public const OS_WS_PAYMENT = 'PS_OS_WS_PAYMENT';
    public const OS_OUTOFSTOCK_PAID = 'PS_OS_OUTOFSTOCK_PAID';
    public const OS_OUTOFSTOCK_UNPAID = 'PS_OS_OUTOFSTOCK_UNPAID';
    public const OS_COD_VALIDATION = 'PS_OS_COD_VALIDATION';
    public const COOKIE_LIFETIME_FO = 'PS_COOKIE_LIFETIME_FO';
    public const COOKIE_LIFETIME_BO = 'PS_COOKIE_LIFETIME_BO';
    public const RESTRICT_DELIVERED_COUNTRIES = 'PS_RESTRICT_DELIVERED_COUNTRIES';
    public const SHOW_NEW_ORDERS = 'PS_SHOW_NEW_ORDERS';
    public const SHOW_NEW_CUSTOMERS = 'PS_SHOW_NEW_CUSTOMERS';
    public const SHOW_NEW_MESSAGES = 'PS_SHOW_NEW_MESSAGES';
    public const SHOW_NEW_SYSTEM_NOTIFICATIONS = 'TB_SHOW_NEW_SYSTEM_NOTIFICATIONS';
    public const FEATURE_FEATURE_ACTIVE = 'PS_FEATURE_FEATURE_ACTIVE';
    public const COMBINATION_FEATURE_ACTIVE = 'PS_COMBINATION_FEATURE_ACTIVE';
    public const SPECIFIC_PRICE_FEATURE_ACTIVE = 'PS_SPECIFIC_PRICE_FEATURE_ACTIVE';
    public const SCENE_FEATURE_ACTIVE = 'PS_SCENE_FEATURE_ACTIVE';
    public const VIRTUAL_PROD_FEATURE_ACTIVE = 'PS_VIRTUAL_PROD_FEATURE_ACTIVE';
    public const CUSTOMIZATION_FEATURE_ACTIVE = 'PS_CUSTOMIZATION_FEATURE_ACTIVE';
    public const CART_RULE_FEATURE_ACTIVE = 'PS_CART_RULE_FEATURE_ACTIVE';
    public const PACK_FEATURE_ACTIVE = 'PS_PACK_FEATURE_ACTIVE';
    public const ALIAS_FEATURE_ACTIVE = 'PS_ALIAS_FEATURE_ACTIVE';
    public const TAX_ADDRESS_TYPE = 'PS_TAX_ADDRESS_TYPE';
    public const SHOP_DEFAULT = 'PS_SHOP_DEFAULT';
    public const CARRIER_DEFAULT_SORT = 'PS_CARRIER_DEFAULT_SORT';
    public const STOCK_MVT_INC_REASON_DEFAULT = 'PS_STOCK_MVT_INC_REASON_DEFAULT';
    public const STOCK_MVT_DEC_REASON_DEFAULT = 'PS_STOCK_MVT_DEC_REASON_DEFAULT';
    public const ADVANCED_STOCK_MANAGEMENT = 'PS_ADVANCED_STOCK_MANAGEMENT';
    public const ADMINREFRESH_NOTIFICATION = 'PS_ADMINREFRESH_NOTIFICATION';
    public const STOCK_MVT_TRANSFER_TO = 'PS_STOCK_MVT_TRANSFER_TO';
    public const STOCK_MVT_TRANSFER_FROM = 'PS_STOCK_MVT_TRANSFER_FROM';
    public const CARRIER_DEFAULT_ORDER = 'PS_CARRIER_DEFAULT_ORDER';
    public const STOCK_MVT_SUPPLY_ORDER = 'PS_STOCK_MVT_SUPPLY_ORDER';
    public const STOCK_CUSTOMER_ORDER_REASON = 'PS_STOCK_CUSTOMER_ORDER_REASON';
    public const UNIDENTIFIED_GROUP = 'PS_UNIDENTIFIED_GROUP';
    public const GUEST_GROUP = 'PS_GUEST_GROUP';
    public const CUSTOMER_GROUP = 'PS_CUSTOMER_GROUP';
    public const SMARTY_CONSOLE = 'PS_SMARTY_CONSOLE';
    public const INVOICE_MODEL = 'PS_INVOICE_MODEL';
    public const LIMIT_UPLOAD_IMAGE_VALUE = 'PS_LIMIT_UPLOAD_IMAGE_VALUE';
    public const LIMIT_UPLOAD_FILE_VALUE = 'PS_LIMIT_UPLOAD_FILE_VALUE';
    public const TOKEN_ENABLE = 'PS_TOKEN_ENABLE';
    public const BO_FORCE_TOKEN = 'TB_BO_FORCE_TOKEN';
    public const STATS_RENDER = 'PS_STATS_RENDER';
    public const STATS_OLD_CONNECT_AUTO_CLEAN = 'PS_STATS_OLD_CONNECT_AUTO_CLEAN';
    public const STATS_GRID_RENDER = 'PS_STATS_GRID_RENDER';
    public const BASE_DISTANCE_UNIT = 'PS_BASE_DISTANCE_UNIT';
    public const SHOP_DOMAIN = 'PS_SHOP_DOMAIN';
    public const SHOP_DOMAIN_SSL = 'PS_SHOP_DOMAIN_SSL';
    public const SHOP_NAME = 'PS_SHOP_NAME';
    public const SHOP_EMAIL = 'PS_SHOP_EMAIL';
    public const MAIL_METHOD = 'PS_MAIL_METHOD';
    public const SHOP_ACTIVITY = 'PS_SHOP_ACTIVITY';
    public const LOGO = 'PS_LOGO';
    public const FAVICON = 'PS_FAVICON';
    public const STORES_ICON = 'PS_STORES_ICON';
    public const ROOT_CATEGORY = 'PS_ROOT_CATEGORY';
    public const HOME_CATEGORY = 'PS_HOME_CATEGORY';
    public const CONFIGURATION_AGREMENT = 'PS_CONFIGURATION_AGREMENT';
    public const MAIL_SERVER = 'PS_MAIL_SERVER';
    public const MAIL_USER = 'PS_MAIL_USER';
    public const MAIL_PASSWD = 'PS_MAIL_PASSWD';
    public const MAIL_SMTP_ENCRYPTION = 'PS_MAIL_SMTP_ENCRYPTION';
    public const MAIL_SMTP_PORT = 'PS_MAIL_SMTP_PORT';
    public const MAIL_COLOR = 'PS_MAIL_COLOR';
    public const PAYMENT_LOGO_CMS_ID = 'PS_PAYMENT_LOGO_CMS_ID';
    public const ALLOW_MOBILE_DEVICE = 'PS_ALLOW_MOBILE_DEVICE';
    public const CUSTOMER_CREATION_EMAIL = 'PS_CUSTOMER_CREATION_EMAIL';
    public const SMARTY_CONSOLE_KEY = 'PS_SMARTY_CONSOLE_KEY';
    public const ATTRIBUTE_ANCHOR_SEPARATOR = 'PS_ATTRIBUTE_ANCHOR_SEPARATOR';
    public const DASHBOARD_SIMULATION = 'PS_DASHBOARD_SIMULATION';
    public const QUICK_VIEW = 'PS_QUICK_VIEW';
    public const USE_HTMLPURIFIER = 'PS_USE_HTMLPURIFIER';
    public const SMARTY_CACHING_TYPE = 'PS_SMARTY_CACHING_TYPE';
    public const SMARTY_CLEAR_CACHE = 'PS_SMARTY_CLEAR_CACHE';
    public const DETECT_LANG = 'PS_DETECT_LANG';
    public const DETECT_COUNTRY = 'PS_DETECT_COUNTRY';
    public const ROUND_TYPE = 'PS_ROUND_TYPE';
    public const PRICE_DISPLAY_PRECISION = 'PS_PRICE_DISPLAY_PRECISION';
    public const LOG_EMAILS = 'PS_LOG_EMAILS';
    public const CUSTOMER_NWSL = 'PS_CUSTOMER_NWSL';
    public const CUSTOMER_OPTIN = 'PS_CUSTOMER_OPTIN';
    public const PACK_STOCK_TYPE = 'PS_PACK_STOCK_TYPE';
    public const LOG_MODULE_PERFS_MODULO = 'PS_LOG_MODULE_PERFS_MODULO';
    public const DISALLOW_HISTORY_REORDERING = 'PS_DISALLOW_HISTORY_REORDERING';
    public const DISPLAY_PRODUCT_WEIGHT = 'PS_DISPLAY_PRODUCT_WEIGHT';
    public const PRODUCT_WEIGHT_PRECISION = 'PS_PRODUCT_WEIGHT_PRECISION';
    public const ADVANCED_PAYMENT_API = 'PS_ADVANCED_PAYMENT_API';
    public const PAGE_CACHE_CONTROLLERS = 'TB_PAGE_CACHE_CONTROLLERS';
    public const PAGE_CACHE_IGNOREPARAMS = 'PS_ADVANCED_PAYMENT_API';
    public const ROUTE_PRODUCT_RULE = 'PS_ROUTE_product_rule';
    public const ROUTE_CATEGORY_RULE = 'PS_ROUTE_category_rule';
    public const ROUTE_SUPPLIER_RULE = 'PS_ROUTE_supplier_rule';
    public const ROUTE_MANUFACTURER_RULE = 'PS_ROUTE_manufacturer_rule';
    public const ROUTE_CMS_RULE = 'PS_ROUTE_cms_rule';
    public const ROUTE_CMS_CATEGORY_RULE = 'PS_ROUTE_cms_category_rule';
    public const DISABLE_OVERRIDES = 'PS_DISABLE_OVERRIDES';
    public const DISABLE_NON_NATIVE_MODULE = 'PS_DISABLE_NON_NATIVE_MODULE';
    public const CUSTOMCODE_METAS = 'TB_CUSTOMCODE_METAS';
    public const CUSTOMCODE_CSS = 'TB_CUSTOMCODE_CSS';
    public const CUSTOMCODE_JS = 'TB_CUSTOMCODE_JS';
    public const CUSTOMCODE_ORDERCONF_JS = 'TB_CUSTOMCODE_ORDERCONF_JS';
    public const STORE_REGISTERED = 'TB_STORE_REGISTERED';
    public const MAIL_SUBJECT_TEMPLATE = 'TB_MAIL_SUBJECT_TEMPLATE';
    public const API_SERVER_OVERRIDE = 'TB_API_SERVER_OVERRIDE';
    public const ACCOUNTS_SERVER_OVERRIDE = 'TB_ACCOUNTS_SERVER_OVERRIDE';
    public const SSL_TRUST_STORE_TYPE = 'TB_SSL_TRUST_STORE_TYPE';
    public const SSL_TRUST_STORE = 'TB_SSL_TRUST_STORE';
    public const TRACKING_ID = 'TB_TRACKING_UID';
    public const MAIL_TRANSPORT = 'TB_MAIL_TRANSPORT';
    public const BECOME_SUPPORTER_URL = 'TB_SUPPORTER_URL';
    public const SUPPORTER_TYPE = 'TB_SUPPORTER_TYPE';
    public const SUPPORTER_TYPE_NAME = 'TB_SUPPORTER_TYPE_NAME';
    public const CONNECTED = 'TB_CONNECTED';
    public const CONNECT_CODE = 'TB_CONNECT_CODE';
    public const MAINTENANCE_IP_ADDRESSES = 'PS_MAINTENANCE_IP';
    public const LANGUAGE_CODE_IN_URL = 'TB_LANGUAGE_CODE_IN_URL';
    /**
     * List of configuration keys that will raise warnings
     */
    public const DEPRECATED_CONFIG_KEYS = [self::PRICE_DISPLAY_PRECISION => 'Use Currency::getDisplayPrecision() method instead'];
    public const LAST_SEEN_NOTIFICATION_UUID = 'TB_LAST_SEEN_NOTIFICATION_UUID';
    public const CCC_ASSETS_RETENTION_PERIOD = 'TB_CCC_ASSETS_RETENTION_PERIOD';
    public const LOGS_RETENTION_PERIOD = 'TB_LOGS_RETENTION_PERIOD';
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'configuration', 'primary' => 'id_configuration', 'multilang' => true, 'fields' => ['id_shop_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'int(11) unsigned'], 'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'int(11) unsigned'], 'name' => ['type' => self::TYPE_STRING, 'validate' => 'isConfigName', 'required' => true, 'size' => 254], 'value' => ['type' => self::TYPE_STRING, 'size' => Object_Model::SIZE_TEXT], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false]], 'keys' => ['configuration' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']], 'id_shop_group' => ['type' => Object_Model::KEY, 'columns' => ['id_shop_group']]], 'configuration_kpi' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']], 'id_shop_group' => ['type' => Object_Model::KEY, 'columns' => ['id_shop_group']], 'name' => ['type' => Object_Model::KEY, 'columns' => ['name']]], 'configuration_kpi_lang' => ['primary' => ['type' => Object_Model::PRIMARY_KEY, 'columns' => ['id_configuration_kpi', 'id_lang']]]]];
    /**
     * @var array Configuration cache
     */
    protected static $_cache = [];
    /**
     * @var array Vars types
     */
    protected static $types = [];
    /**
     * @var mixed
     */
    protected static $check_deprecated_keys = true;
    /**
     * @var string Key
     */
    public $name;
    /**
     * @var int
     */
    public $id_shop_group;
    /**
     * @var int
     */
    public $id_shop;
    /**
     * @var string Value
     */
    public $value;
    /**
     * @var string Object creation date
     */
    public $date_add;
    /**
     * @var string Object last modification date
     */
    public $date_upd;
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['fields' => ['value' => []]];
    /**
     * @return bool|null
     */
    public static function configuration_is_loaded()
    {
        return isset(static::$_cache[static::$definition['table']]) && is_array(static::$_cache[static::$definition['table']]) && count(static::$_cache[static::$definition['table']]);
    }
    /**
     * WARNING: For testing only. Do NOT rely on this method, it may be removed at any time.
     *
     * @todo    Delegate static calls from Configuration to an instance of a class to be created.
     */
    public static function clear_configuration_cache_for_testing(): void
    {
        static::$_cache = [];
    }
    /**
     * @param string $key
     * @param int|null $idLang
     *
     * @return string|null|false
     *
     * @throws PrestaShopException
     */
    public static function get_global_value($key, $id_lang = null)
    {
        return Configuration::get($key, $id_lang, 0, 0);
    }
    /**
     * Get a single configuration value (in one language only)
     *
     * @param string $key Key wanted
     * @param int $idLang Language ID
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return string|null|false Value
     *
     * @throws PrestaShopException
     */
    public static function get($key, $id_lang = null, $id_shop_group = null, $id_shop = null)
    {
        if (defined('_PS_DO_NOT_LOAD_CONFIGURATION_') && _PS_DO_NOT_LOAD_CONFIGURATION_) {
            return false;
        }
        static::validate_key($key);
        if (!static::configuration_is_loaded()) {
            Configuration::load_configuration();
        }
        $id_lang = (int) $id_lang;
        if ($id_shop === null || !Shop::is_feature_active()) {
            $id_shop = Shop::get_context_shop_id(true);
        }
        if ($id_shop_group === null || !Shop::is_feature_active()) {
            $id_shop_group = Shop::get_context_shop_group_id(true);
        }
        if (!isset(static::$_cache[static::$definition['table']][$id_lang])) {
            $id_lang = 0;
        }
        if ($id_shop && Configuration::has_key($key, $id_lang, null, $id_shop)) {
            return static::$_cache[static::$definition['table']][$id_lang]['shop'][$id_shop][$key];
        }
        if ($id_shop_group && Configuration::has_key($key, $id_lang, $id_shop_group)) {
            return static::$_cache[static::$definition['table']][$id_lang]['group'][$id_shop_group][$key];
        }
        if (Configuration::has_key($key, $id_lang)) {
            return static::$_cache[static::$definition['table']][$id_lang]['global'][$key];
        }
        return false;
    }
    /**
     * Get a single configuration value for a get that has been deprecated.
     *
     * @param string $key Key wanted
     * @param int $idLang Language ID
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return string|null|false Value
     *
     * @throws PrestaShopException
     */
    public static function get_deprecated_key($key, $id_lang = null, $id_shop_group = null, $id_shop = null)
    {
        $save = static::$check_deprecated_keys;
        static::$check_deprecated_keys = false;
        try {
            return static::get($key);
        } finally {
            static::$check_deprecated_keys = $save;
        }
    }
    /**
     * Update deprecated configuration key and value into database
     *
     * @param string $key Key
     * @param mixed $values $values is an array if the configuration is multilingual, a single string else.
     * @param bool $html Specify if html is authorized in value
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function update_deprecated_key($key, $values, $html = false, $id_shop_group = null, $id_shop = null)
    {
        $save = static::$check_deprecated_keys;
        static::$check_deprecated_keys = false;
        try {
            return static::update_value($key, $values, $html, $id_shop_group, $id_shop);
        } finally {
            static::$check_deprecated_keys = $save;
        }
    }
    /**
     * Load all configuration data
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function load_configuration(): void
    {
        static::load_configuration_from_db(Db::read_only());
    }
    /**
     * Load all configuration data, using an existing database connection.
     *
     * @param ReadOnlyConnection $connection Database connection to be used for data retrieval.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function load_configuration_from_db($connection): void
    {
        static::$_cache[static::$definition['table']] = [];
        $rows = $connection->get_array((new Db_Query())->select('c.`name`, cl.`id_lang`, IFNULL(cl.`value`, c.`value`) AS `value`, c.`id_shop_group`, c.`id_shop`')->from(static::$definition['table'], 'c')->left_join(static::$definition['table'] . '_lang', 'cl', 'c.`' . static::$definition['primary'] . '` = cl.`' . static::$definition['primary'] . '`'));
        foreach ($rows as $row) {
            $lang = $row['id_lang'] ?: 0;
            static::$types[$row['name']] = $lang ? 'lang' : 'normal';
            if (!isset(static::$_cache[static::$definition['table']][$lang])) {
                static::$_cache[static::$definition['table']][$lang] = ['global' => [], 'group' => [], 'shop' => []];
            }
            if ($row['id_shop']) {
                static::$_cache[static::$definition['table']][$lang]['shop'][$row['id_shop']][$row['name']] = $row['value'];
            } elseif ($row['id_shop_group']) {
                static::$_cache[static::$definition['table']][$lang]['group'][$row['id_shop_group']][$row['name']] = $row['value'];
            } else {
                static::$_cache[static::$definition['table']][$lang]['global'][$row['name']] = $row['value'];
            }
        }
    }
    /**
     * Check if key exists in configuration
     *
     * @param string $key
     * @param int|null $idLang
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function has_key($key, $id_lang = null, $id_shop_group = null, $id_shop = null)
    {
        static::validate_key($key);
        if (!static::configuration_is_loaded()) {
            Configuration::load_configuration();
        }
        $id_lang = (int) $id_lang;
        if ($id_shop) {
            return isset(static::$_cache[static::$definition['table']][$id_lang]['shop'][$id_shop]) && (isset(static::$_cache[static::$definition['table']][$id_lang]['shop'][$id_shop][$key]) || array_key_exists($key, static::$_cache[static::$definition['table']][$id_lang]['shop'][$id_shop]));
        }
        if ($id_shop_group) {
            return isset(static::$_cache[static::$definition['table']][$id_lang]['group'][$id_shop_group]) && (isset(static::$_cache[static::$definition['table']][$id_lang]['group'][$id_shop_group][$key]) || array_key_exists($key, static::$_cache[static::$definition['table']][$id_lang]['group'][$id_shop_group]));
        }
        return isset(static::$_cache[static::$definition['table']][$id_lang]['global']) && (isset(static::$_cache[static::$definition['table']][$id_lang]['global'][$key]) || array_key_exists($key, static::$_cache[static::$definition['table']][$id_lang]['global']));
    }
    /**
     * Get a single configuration value (in multiple languages)
     *
     * @param string $key Key wanted
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return array Values in multiple languages
     *
     * @throws PrestaShopException
     */
    public static function get_int($key, $id_shop_group = null, $id_shop = null)
    {
        $results_array = [];
        foreach (Language::get_i_ds() as $id_lang) {
            $results_array[$id_lang] = Configuration::get($key, $id_lang, $id_shop_group, $id_shop);
        }
        return $results_array;
    }
    /**
     * Get a single configuration value for all shops
     *
     * @param string $key Key wanted
     * @param int $idLang
     *
     * @return array Values for all shops
     *
     * @throws PrestaShopException
     */
    public static function get_multi_shop_values($key, $id_lang = null)
    {
        $shops = Shop::get_shops(false, null, true);
        $results_array = [];
        foreach ($shops as $id_shop) {
            $results_array[$id_shop] = Configuration::get($key, $id_lang, null, $id_shop);
        }
        return $results_array;
    }
    /**
     * Get several configuration values (in one language only)
     *
     * @throws PrestaShopException
     *
     * @param array $keys Keys wanted
     * @param int $idLang Language ID
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return array Values
     */
    public static function get_multiple($keys, $id_lang = null, $id_shop_group = null, $id_shop = null)
    {
        if (!is_array($keys)) {
            throw new Presta_Shop_Exception('keys var is not an array');
        }
        $id_lang = (int) $id_lang;
        if ($id_shop === null) {
            $id_shop = Shop::get_context_shop_id(true);
        }
        if ($id_shop_group === null) {
            $id_shop_group = Shop::get_context_shop_group_id(true);
        }
        $results = [];
        foreach ($keys as $key) {
            $results[$key] = Configuration::get($key, $id_lang, $id_shop_group, $id_shop);
        }
        return $results;
    }
    /**
     * Update configuration key for global context only
     *
     * This method escapes $values with pSQL().
     *
     * @param string $key
     * @param mixed $values
     * @param bool $html
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function update_global_value($key, $values, $html = false)
    {
        return Configuration::update_value($key, $values, $html, 0, 0);
    }
    /**
     * Update configuration key and value into database (automatically insert if key does not exist)
     *
     * @param string $key Key
     * @param mixed $values $values is an array if the configuration is multilingual, a single string else.
     * @param bool $html Specify if html is authorized in value
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return bool Update result
     *
     * @throws PrestaShopException
     */
    public static function update_value($key, $values, $html = false, $id_shop_group = null, $id_shop = null)
    {
        if (!is_array($values)) {
            $values = [$values];
        }
        // sanitize values
        foreach ($values as &$value) {
            if (!is_null($value) && !is_numeric($value)) {
                if ($html) {
                    // if html values are allowed, just purify html code
                    $value = Tools::purify_html($value);
                } else {
                    // if html values are not allowed, strip tags
                    $value = strip_tags((string) $value);
                }
            }
        }
        return static::update_value_raw($key, $values, $id_shop_group, $id_shop);
    }
    /**
     * Update configuration key and value into database and cache
     *
     * Values are inserted/updated directly using SQL, because using (Configuration) ObjectModel
     * may not insert values correctly (for example, HTML is escaped, when it should not be).
     *
     * @param string $key Key
     * @param mixed $values $values is an array if the configuration is multilingual, a single string else.
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return bool Update result
     *
     * @throws PrestaShopException
     */
    public static function update_value_raw($key, $values, $id_shop_group = null, $id_shop = null)
    {
        static::validate_key($key);
        if ($id_shop === null || !Shop::is_feature_active()) {
            $id_shop = Shop::get_context_shop_id(true);
        }
        if ($id_shop_group === null || !Shop::is_feature_active()) {
            $id_shop_group = Shop::get_context_shop_group_id(true);
        }
        if (!is_array($values)) {
            $values = [$values];
        }
        $conn = Db::get_instance();
        $result = true;
        foreach ($values as $lang => $raw_value) {
            $lang = (int) $lang;
            $value = p_sql($raw_value, true);
            if (Configuration::has_key($key, $lang, $id_shop_group, $id_shop)) {
                // If key exists already, update value.
                if (!$lang) {
                    // Update config not linked to lang
                    $result = $conn->update(static::$definition['table'], ['value' => $value, 'date_upd' => date('Y-m-d H:i:s')], '`name` = \'' . $key . '\'' . Configuration::sql_restriction($id_shop_group, $id_shop), 1, true) && $result;
                } else {
                    // Update multi lang
                    $sql = 'UPDATE `' . _DB_PREFIX_ . static::$definition['table'] . '_lang` cl
                            SET cl.value = \'' . $value . '\',
                                cl.date_upd = NOW()
                            WHERE cl.id_lang = ' . (int) $lang . '
                                AND cl.`' . static::$definition['primary'] . '` = (
                                    SELECT c.`' . static::$definition['primary'] . '`
                                    FROM `' . _DB_PREFIX_ . static::$definition['table'] . '` c
                                    WHERE c.name = \'' . $key . '\'' . Configuration::sql_restriction($id_shop_group, $id_shop) . ')';
                    $result = $conn->execute($sql) && $result;
                }
            } else {
                // If key doesn't exist, create it.
                if (!$config_id = Configuration::get_id_by_name($key, $id_shop_group, $id_shop)) {
                    $data = ['id_shop_group' => $id_shop_group ? (int) $id_shop_group : null, 'id_shop' => $id_shop ? (int) $id_shop : null, 'name' => $key, 'value' => $lang ? null : $value, 'date_add' => ['type' => 'sql', 'value' => 'NOW()'], 'date_upd' => ['type' => 'sql', 'value' => 'NOW()']];
                    $result = $conn->insert(static::$definition['table'], $data, true) && $result;
                    $config_id = $conn->Insert_ID();
                }
                if ($lang) {
                    $result = $conn->insert(static::$definition['table'] . '_lang', [static::$definition['primary'] => $config_id, 'id_lang' => (int) $lang, 'value' => $value, 'date_upd' => date('Y-m-d H:i:s')]) && $result;
                }
            }
        }
        Configuration::set($key, $values, $id_shop_group, $id_shop);
        return $result;
    }
    /**
     * Add SQL restriction on shops for configuration table
     *
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return string
     */
    protected static function sql_restriction($id_shop_group, $id_shop)
    {
        if ($id_shop) {
            return ' AND id_shop = ' . (int) $id_shop;
        }
        if ($id_shop_group) {
            return ' AND id_shop_group = ' . (int) $id_shop_group . ' AND (id_shop IS NULL OR id_shop = 0)';
        }
        return ' AND (id_shop_group IS NULL OR id_shop_group = 0) AND (id_shop IS NULL OR id_shop = 0)';
    }
    /**
     * Return ID a configuration key
     *
     * @param string $key
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return int Configuration key ID
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_name($key, $id_shop_group = null, $id_shop = null)
    {
        static::validate_key($key);
        if ($id_shop === null) {
            $id_shop = Shop::get_context_shop_id(true);
        }
        if ($id_shop_group === null) {
            $id_shop_group = Shop::get_context_shop_group_id(true);
        }
        $sql = 'SELECT `' . static::$definition['primary'] . '`
                FROM `' . _DB_PREFIX_ . static::$definition['table'] . '`
                WHERE name = \'' . $key . '\'
                ' . Configuration::sql_restriction($id_shop_group, $id_shop);
        return (int) Db::read_only()->get_value($sql);
    }
    /**
     * Set TEMPORARY a single configuration value (in one language only)
     *
     * This method expects $values to be escaped with pSQL() already (to change
     * this, we'd need $html in the signature).
     *
     * Note: a need for calling this method directly should be rare.
     *       updateValue() and updateGlobalValue() do this on their own already.
     *
     * @param string $key Key wanted
     * @param mixed $values $values is an array if the configuration is multilingual, a single string else.
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @throws PrestaShopException
     */
    public static function set($key, $values, $id_shop_group = null, $id_shop = null): void
    {
        static::validate_key($key);
        if ($id_shop === null) {
            $id_shop = Shop::get_context_shop_id(true);
        }
        if ($id_shop_group === null) {
            $id_shop_group = Shop::get_context_shop_group_id(true);
        }
        if (!is_array($values)) {
            $values = [$values];
        }
        foreach ($values as $lang => $value) {
            if ($id_shop) {
                static::$_cache[static::$definition['table']][$lang]['shop'][$id_shop][$key] = $value;
            } elseif ($id_shop_group) {
                static::$_cache[static::$definition['table']][$lang]['group'][$id_shop_group][$key] = $value;
            } else {
                static::$_cache[static::$definition['table']][$lang]['global'][$key] = $value;
            }
        }
    }
    /**
     * Delete a configuration key in database (with or without language management)
     *
     * @param string $key Key to delete
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public static function delete_by_name($key)
    {
        static::validate_key($key);
        $conn = Db::get_instance();
        $result = $conn->execute('
        DELETE FROM `' . _DB_PREFIX_ . static::$definition['table'] . '_lang`
        WHERE `' . static::$definition['primary'] . '` IN (
            SELECT `' . static::$definition['primary'] . '`
            FROM `' . _DB_PREFIX_ . static::$definition['table'] . '`
            WHERE `name` = "' . $key . '"
        )');
        $result2 = $conn->delete(static::$definition['table'], '`name` = "' . $key . '"');
        static::$_cache[static::$definition['table']] = null;
        return $result && $result2;
    }
    /**
     * Delete configuration key from current context.
     *
     * @param string $key
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function delete_from_context($key): void
    {
        if (Shop::get_context() == Shop::CONTEXT_ALL) {
            return;
        }
        $id_shop = null;
        $id_shop_group = Shop::get_context_shop_group_id(true);
        if (Shop::get_context() == Shop::CONTEXT_SHOP) {
            $id_shop = Shop::get_context_shop_id(true);
        }
        $id = Configuration::get_id_by_name($key, $id_shop_group, $id_shop);
        $conn = Db::get_instance();
        $conn->delete(static::$definition['table'], '`' . static::$definition['primary'] . '` = ' . (int) $id);
        $conn->delete(static::$definition['table'] . '_lang', '`' . static::$definition['primary'] . '` = ' . (int) $id);
        static::$_cache[static::$definition['table']] = null;
    }
    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_overriden_by_current_context($key)
    {
        if (!Shop::is_feature_active()) {
            return false;
        }
        if (Shop::get_context() == Shop::CONTEXT_ALL) {
            return false;
        }
        if (static::is_lang_key($key)) {
            foreach (Language::get_i_ds(false) as $id_lang) {
                if (static::has_context($key, $id_lang, Shop::get_context())) {
                    return true;
                }
            }
            return false;
        }
        return static::has_context($key, null, Shop::get_context());
    }
    /**
     * Check if a key was loaded as multi lang
     *
     * @param string $key
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_lang_key($key)
    {
        static::validate_key($key);
        return isset(static::$types[$key]) && static::$types[$key] == 'lang';
    }
    /**
     * Check if configuration var is defined in given context
     *
     * @param string $key
     * @param int|null $idLang
     * @param int $context
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @return bool
     */
    public static function has_context($key, $id_lang, $context)
    {
        if (Shop::get_context() == Shop::CONTEXT_ALL) {
            $id_shop = $id_shop_group = null;
        } elseif (Shop::get_context() == Shop::CONTEXT_GROUP) {
            $id_shop_group = Shop::get_context_shop_group_id(true);
            $id_shop = null;
        } else {
            $id_shop_group = Shop::get_context_shop_group_id(true);
            $id_shop = Shop::get_context_shop_id(true);
        }
        if ($context == Shop::CONTEXT_SHOP && Configuration::has_key($key, $id_lang, null, $id_shop)) {
            return true;
        }
        if ($context == Shop::CONTEXT_GROUP && Configuration::has_key($key, $id_lang, $id_shop_group)) {
            return true;
        }
        if ($context == Shop::CONTEXT_ALL && Configuration::has_key($key, $id_lang)) {
            return true;
        }
        return false;
    }
    /**
     * @return bool|array Multilingual fields
     *
     * @throws PrestaShopException
     */
    public function get_fields_lang()
    {
        if (!is_array($this->value)) {
            return true;
        }
        return parent::get_fields_lang();
    }
    /**
     * This method is override to allow TranslatedConfiguration entity
     *
     * @param string $sqlJoin
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_webservice_object_list($sql_join, $sql_filter, $sql_sort, $sql_limit)
    {
        $query = '
        SELECT DISTINCT main.`' . static::$definition['primary'] . '`
        FROM `' . _DB_PREFIX_ . static::$definition['table'] . '` main
        ' . $sql_join . '
        WHERE `' . static::$definition['primary'] . '` NOT IN (
            SELECT `' . static::$definition['primary'] . '`
            FROM ' . _DB_PREFIX_ . static::$definition['table'] . '_lang
        ) ' . $sql_filter . '
        ' . ($sql_sort != '' ? $sql_sort : '') . '
        ' . ($sql_limit != '' ? $sql_limit : '');
        return Db::read_only()->get_array($query);
    }
    /**
     * Validate a configuration key. Throws an exception for invalid keys.
     *
     * @param string $key
     *
     * @throws PrestaShopException
     */
    protected static function validate_key($key)
    {
        if (!Validate::is_config_name($key)) {
            $message = sprintf(Tools::display_error('[%s] is not a valid configuration key'), Tools::htmlentities_utf8($key));
            trigger_error($message, E_USER_WARNING);
            throw new Presta_Shop_Exception($message);
        }
        if (static::$check_deprecated_keys && array_key_exists($key, static::DEPRECATED_CONFIG_KEYS)) {
            $call_point = Tools::get_call_point([Configuration::class]);
            $message = sprintf(Tools::display_error('Configuration key [%s] is deprecated.'), $key) . ' ';
            $message .= trim((string) static::DEPRECATED_CONFIG_KEYS[$key]) . '. ';
            $message .= 'Called from: ' . $call_point['description'];
            trigger_error($message, E_USER_DEPRECATED);
        }
    }
    /**
     * Returns url to thirty bees api server
     *
     * Default api url can be overridden using configuration key TB_API_SERVER_OVERRIDE. This should be used
     * by thirty bees developers only
     *
     * @throws PrestaShopException
     */
    public static function get_api_server(): string
    {
        $base_uri_override = (string) static::get_global_value(static::API_SERVER_OVERRIDE);
        if ($base_uri_override) {
            $base_uri_override = rtrim($base_uri_override, '/');
            if (Validate::is_absolute_url($base_uri_override)) {
                return $base_uri_override;
            }
        }
        return 'https://api.thirtybees.com';
    }
    /**
     *  Returns url to thirty bees accounts server
     *
     *  Default api url can be overridden using configuration key TB_ACCOUNTS_SERVER_OVERRIDE. This should be used
     *  by thirty bees developers only
     *
     * @throws PrestaShopException
     */
    public static function get_accounts_server(): string
    {
        $base_uri_override = static::get_global_value(static::ACCOUNTS_SERVER_OVERRIDE);
        if ($base_uri_override) {
            $base_uri_override = rtrim($base_uri_override, '/');
            if (Validate::is_absolute_url($base_uri_override)) {
                return $base_uri_override;
            }
        }
        return 'https://accounts.thirtybees.com';
    }
    /**
     * Returns path to trust store that should be used to verify SSL connections.
     *
     * If this method returns true, then operation-system trust store will be used
     * If this method returns false, then SSL certificates will not be used
     *
     * @return string | boolean
     * @throws PrestaShopException
     */
    public static function get_ssl_trust_store()
    {
        $type = static::get_global_value(static::SSL_TRUST_STORE_TYPE);
        switch (strtolower($type)) {
            case 'system':
                return true;
            case 'disable':
                return false;
            case 'custom':
            default:
                $path = static::get_global_value(static::SSL_TRUST_STORE);
                if (!$path) {
                    return _PS_TOOL_DIR_ . 'cacert.pem';
                }
                return $path;
        }
    }
    /**
     * Returns unique identifier of this installation, for tracking purposes
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function get_server_tracking_id()
    {
        $tracking_id = static::get_global_value(Configuration::TRACKING_ID);
        if (!$tracking_id) {
            $tracking_id = Tools::passwd_gen(40);
            static::update_global_value(Configuration::TRACKING_ID, $tracking_id);
        }
        return $tracking_id;
    }
    /**
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function get_become_supporter_url()
    {
        $url = static::get_global_value(static::BECOME_SUPPORTER_URL);
        if (!$url) {
            $url = 'https://forum.thirtybees.com/support-thirty-bees/?sid=@SID@';
        }
        return str_replace('@SID@', static::get_server_tracking_id(), $url);
    }
    /**
     * @return array|null
     *
     * @throws PrestaShopException
     */
    public static function get_supporter_info()
    {
        $type = static::get_global_value(static::SUPPORTER_TYPE);
        if ($type) {
            return ['type' => $type, 'name' => static::get_global_value(static::SUPPORTER_TYPE_NAME)];
        }
        return null;
    }
    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_ccc_assets_retention_period()
    {
        $value = (int) static::get(static::CCC_ASSETS_RETENTION_PERIOD);
        if (!$value) {
            // fallback
            if ((int) static::get('TB_KEEP_CCC_FILES')) {
                $value = 180;
                static::update_value(static::CCC_ASSETS_RETENTION_PERIOD, $value);
            }
        }
        return $value;
    }
    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_logs_retention_period()
    {
        $value = (int) static::get(static::LOGS_RETENTION_PERIOD);
        if ($value <= 0) {
            return 180;
        }
        return $value;
    }
    public static function get_valid_config_key(string $key): string
    {
        $str = preg_replace('/[^A-Z0-9_]/', '_', strtoupper($key));
        $str = preg_replace('/_+/', '_', (string) $str);
        return trim((string) $str, '_');
    }
}