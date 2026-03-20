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
use Core_Updater\Table_Schema;
use Thirtybees\Core\Initialization_Callback;
/**
 * Class CarrierCore
 */
class Carrier_Core extends Object_Model implements Initialization_Callback
{
    /**
     * getCarriers method filter
     */
    public const PS_CARRIERS_ONLY = 1;
    public const CARRIERS_MODULE = 2;
    public const CARRIERS_MODULE_NEED_RANGE = 3;
    public const PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE = 4;
    public const ALL_CARRIERS = 5;
    public const SHIPPING_METHOD_DEFAULT = 0;
    public const SHIPPING_METHOD_WEIGHT = 1;
    public const SHIPPING_METHOD_PRICE = 2;
    public const SHIPPING_METHOD_FREE = 3;
    public const SHIPPING_PRICE_EXCEPTION = 0;
    public const SHIPPING_WEIGHT_EXCEPTION = 1;
    public const SHIPPING_SIZE_EXCEPTION = 2;
    public const SORT_BY_PRICE = 0;
    public const SORT_BY_POSITION = 1;
    public const SORT_BY_ASC = 0;
    public const SORT_BY_DESC = 1;
    /** @var array $price_by_weight */
    protected static $price_by_weight = [];
    /** @var array $price_by_weight2 */
    protected static $price_by_weight2 = [];
    /** @var array $price_by_price */
    protected static $price_by_price = [];
    /** @var array $price_by_price2 */
    protected static $price_by_price2 = [];
    /** @var array $cache_tax_rule */
    protected static $cache_tax_rule = [];
    /** @var int common id for carrier historization */
    public $id_reference;
    /**
     * @var string Name
     * @deprecated 1.4.0 -- use display name instead
     */
    public $name;
    /** @var string|string[] Name */
    public $display_name;
    /** @var string URL with a '@' for */
    public $url;
    /** @var string|string[] Delay needed to deliver customer */
    public $delay;
    /** @var bool Carrier status */
    public $active = true;
    /** @var bool True if carrier has been deleted (staying in database as deleted) */
    public $deleted = 0;
    /** @var bool Active or not the shipping handling */
    public $shipping_handling = true;
    /** @var bool Behavior taken for unknown range */
    public $range_behavior;
    /** @var bool Carrier module */
    public $is_module;
    /** @var bool Free carrier */
    public $is_free = false;
    /** @var int shipping behavior: by weight or by price */
    public $shipping_method = 0;
    /** @var bool Shipping external */
    public $shipping_external = 0;
    /** @var string Shipping external */
    public $external_module_name;
    /** @var bool Need Range */
    public $need_range = 0;
    /** @var int Position */
    public $position;
    /** @var int maximum package width managed by the transporter */
    public $max_width;
    /** @var int maximum package height managed by the transporter */
    public $max_height;
    /** @var int maximum package deep managed by the transporter */
    public $max_depth;
    /** @var float minimum cart total managed by the transporter */
    public $min_total;
    /** @var bool flag thas specify if min_total value is with or without tax */
    public $min_total_tax;
    /** @var float maximum cart total managed by the transporter */
    public $max_total;
    /** @var bool flag thas specify if max_total value is with or without tax */
    public $max_total_tax;
    /** @var float minimum package weight managed by the transporter */
    public $min_weight;
    /** @var float maximum package weight managed by the transporter */
    public $max_weight;
    /** @var int grade of the shipping delay (0 for longest, 9 for shortest) */
    public $grade;
    /** @var bool prices_with_tax indicates if the shipping prices associated with carrier are with or without tax */
    public $prices_with_tax;
    /** @var int $id_tax_rules_group */
    public $id_tax_rules_group;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'carrier', 'primary' => 'id_carrier', 'multilang' => true, 'multilang_shop' => true, 'fields' => [
        /* Classic fields */
        'id_reference' => ['type' => self::TYPE_INT, 'dbNullable' => false],
        'id_tax_rules_group' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbDefault' => '0', 'dbNullable' => true],
        'name' => ['type' => self::TYPE_STRING, 'validate' => 'isCarrierName', 'required' => true, 'size' => 64],
        'url' => ['type' => self::TYPE_STRING, 'validate' => 'isString'],
        'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbDefault' => '0'],
        'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'shipping_handling' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '1'],
        'range_behavior' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'is_module' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'is_free' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'shipping_external' => ['type' => self::TYPE_BOOL, 'dbDefault' => '0'],
        'need_range' => ['type' => self::TYPE_BOOL, 'dbDefault' => '0'],
        'external_module_name' => ['type' => self::TYPE_STRING, 'size' => 64],
        'shipping_method' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbType' => 'int(2)', 'dbDefault' => '0'],
        'position' => ['type' => self::TYPE_INT, 'dbDefault' => '0'],
        'max_width' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbType' => 'int(10)', 'dbDefault' => '0', 'dbNullable' => true],
        'max_height' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbType' => 'int(10)', 'dbDefault' => '0', 'dbNullable' => true],
        'max_depth' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbType' => 'int(10)', 'dbDefault' => '0', 'dbNullable' => true],
        'min_total' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000', 'dbNullable' => true],
        'min_total_tax' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'max_total' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000', 'dbNullable' => true],
        'max_total_tax' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'min_weight' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'dbDefault' => '0.000000', 'dbNullable' => true],
        'max_weight' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'dbDefault' => '0.000000', 'dbNullable' => true],
        'grade' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbType' => 'int(10)', 'size' => 1, 'dbDefault' => '0', 'dbNullable' => true],
        'prices_with_tax' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        /* Lang fields */
        'display_name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCarrierName', 'required' => true, 'size' => 64],
        'delay' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128, 'dbNullable' => true],
    ], 'associations' => ['zone' => ['type' => self::BELONGS_TO_MANY, 'joinTable' => 'carrier_zone'], 'group' => ['type' => self::BELONGS_TO_MANY, 'joinTable' => 'carrier_group']], 'keys' => ['carrier' => ['deleted' => ['type' => Object_Model::KEY, 'columns' => ['deleted', 'active']], 'id_tax_rules_group' => ['type' => Object_Model::KEY, 'columns' => ['id_tax_rules_group']], 'reference' => ['type' => Object_Model::KEY, 'columns' => ['id_reference', 'deleted', 'active']]], 'carrier_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]], 'carrier_lang' => ['primary' => ['type' => Object_Model::PRIMARY_KEY, 'columns' => ['id_lang', 'id_shop', 'id_carrier']]]]];
    /**
     * @var array[]
     */
    protected $webservice_parameters = ['fields' => ['deleted' => [], 'is_module' => [], 'id_tax_rules_group' => ['getter' => 'getIdTaxRulesGroup', 'setter' => 'setWsTaxRulesGroup', 'xlink_resource' => ['resourceName' => 'tax_rule_groups']]]];
    /**
     * CarrierCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     *
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);
        /**
         * keep retrocompatibility SHIPPING_METHOD_DEFAULT
         *
         * @deprecated 1.5.5
         */
        if ($this->shipping_method == static::SHIPPING_METHOD_DEFAULT) {
            $this->shipping_method = (int) Configuration::get('PS_SHIPPING_METHOD') ? static::SHIPPING_METHOD_WEIGHT : static::SHIPPING_METHOD_PRICE;
        }
        /**
         * keep retrocompatibility id_tax_rules_group
         *
         * @deprecated 1.5.0
         */
        if ($this->id) {
            $this->id_tax_rules_group = $this->get_id_tax_rules_group(Context::get_context());
            $this->fix_names();
        }
        $this->image_dir = _PS_SHIP_IMG_DIR_;
    }
    /**
     * Returns true, if proportionate shipping and wrapping tax is used
     *
     * @return boolean
     */
    public static function use_proportionate_tax()
    {
        try {
            return (bool) Configuration::get('PS_ATCP_SHIPWRAP');
        } catch (Presta_Shop_Exception) {
            return false;
        }
    }
    /**
     * Hydrate function for the Carrier
     *
     * @param int|null $idLang
     *
     *
     * @throws PrestaShopException
     */
    public function hydrate(array $data, $id_lang = null): void
    {
        parent::hydrate($data, $id_lang);
        /**
         * keep retrocompatibility id_tax_rules_group
         *
         * @deprecated PS 1.5
         */
        if ($this->id && !$this->id_tax_rules_group) {
            $this->id_tax_rules_group = $this->get_id_tax_rules_group(Context::get_context());
        }
        $this->fix_names();
    }
    /**
     * Multilang-hydrate function for the Carrier
     *
     * Fill an object with given data. Data must be an array with this syntax:
     * array(
     *   array(id_lang => 1, objProperty => value, objProperty2 => value, etc.),
     *   array(id_lang => 2, objProperty => value, objProperty2 => value, etc.),
     * );
     *
     *
     *
     * @throws PrestaShopException
     */
    public function hydrate_multilang(array $data): void
    {
        parent::hydrate_multilang($data);
        /**
         * keep retrocompatibility id_tax_rules_group
         *
         * @deprecated PS 1.5
         */
        if ($this->id && !$this->id_tax_rules_group) {
            $this->id_tax_rules_group = $this->get_id_tax_rules_group(Context::get_context());
        }
        $this->fix_names();
    }
    /**
     *
     * @return int
     * @throws PrestaShopException
     */
    public function get_id_tax_rules_group(?Context $context = null)
    {
        return static::get_id_tax_rules_group_by_id_carrier((int) $this->id, $context);
    }
    /**
     * @param int $idCarrier
     *
     * @return int
     * @throws PrestaShopException
     */
    public static function get_id_tax_rules_group_by_id_carrier($id_carrier, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $key = 'carrier_id_tax_rules_group_' . (int) $id_carrier . '_' . (int) $context->shop->id;
        if (!Cache::is_stored($key)) {
            $result = Db::read_only()->get_value((new Db_Query())->select('`id_tax_rules_group`')->from('carrier_tax_rules_group_shop')->where('`id_carrier` = ' . (int) $id_carrier)->where('`id_shop` = ' . (int) $context->shop->id));
            Cache::store($key, $result);
            return $result;
        }
        return Cache::retrieve($key);
    }
    /**
     * Return the carrier name from the shop name (e.g. if the carrier name is '0').
     *
     * The returned carrier name is the shop name without '#' and ';' because this is not the same validation.
     *
     * @return string Carrier name
     * @throws PrestaShopException
     */
    public static function get_carrier_name_from_shop_name()
    {
        return str_replace(['#', ';'], '', Configuration::get('PS_SHOP_NAME'));
    }
    /**
     * Get delivery prices for a given shipping method (price/weight)
     *
     * @param string $rangeTable Table name (price or weight)
     * @param int $idCarrier
     *
     * @return array Delivery prices
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_delivery_price_by_ranges($range_table, $id_carrier)
    {
        return Db::read_only()->get_array((new Db_Query())->select('d.`id_' . bq_sql($range_table) . '`, d.`id_carrier`, d.`id_zone`, d.`price`')->from('delivery', 'd')->left_join(bq_sql($range_table), 'r', 'r.`id_' . bq_sql($range_table) . '` = d.`id_' . bq_sql($range_table) . '`')->where('d.`id_carrier` = ' . (int) $id_carrier)->where('d.`id_' . bq_sql($range_table) . '` IS NOT NULL')->where('d.`id_' . bq_sql($range_table) . '` != 0 ' . static::sql_delivery_range_shop($range_table))->order_by('r.`delimiter1`'));
    }
    /**
     * This tricky method generates a sql clause to check if ranged data are overloaded by multishop
     *
     * @param string $rangeTable
     * @param string $alias
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function sql_delivery_range_shop($range_table, $alias = 'd')
    {
        if (Shop::get_context() == Shop::CONTEXT_ALL) {
            $where = 'AND d2.id_shop IS NULL AND d2.id_shop_group IS NULL';
        } elseif (Shop::get_context() == Shop::CONTEXT_GROUP) {
            $where = 'AND ((d2.id_shop_group IS NULL OR d2.id_shop_group = ' . Shop::get_context_shop_group_id() . ') AND d2.id_shop IS NULL)';
        } else {
            $where = 'AND (d2.id_shop = ' . Shop::get_context_shop_id() . ' OR (d2.id_shop_group = ' . Shop::get_context_shop_group_id() . '
					AND d2.id_shop IS NULL) OR (d2.id_shop_group IS NULL AND d2.id_shop IS NULL))';
        }
        return 'AND ' . $alias . '.id_delivery = (
					SELECT d2.id_delivery
					FROM ' . _DB_PREFIX_ . 'delivery d2
					WHERE d2.id_carrier = `' . bq_sql($alias) . '`.id_carrier
						AND d2.id_zone = `' . bq_sql($alias) . '`.id_zone
						AND d2.`id_' . bq_sql($range_table) . '` = `' . bq_sql($alias) . '`.`id_' . bq_sql($range_table) . '`
						' . $where . '
					ORDER BY d2.id_shop DESC, d2.id_shop_group DESC
					LIMIT 1
				)';
    }
    /**
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_id_tax_rules_group_most_used()
    {
        $result = Db::read_only()->get_row((new Db_Query())->select('COUNT(*) AS `n`, c.`id_tax_rules_group`')->from('carrier', 'c')->inner_join('tax_rules_group', 'trg', 'c.`id_tax_rules_group` = trg.`id_tax_rules_group`')->group_by('c.`id_tax_rules_group`')->order_by('n DESC'));
        return isset($result['id_tax_rules_group']) ? (int) $result['id_tax_rules_group'] : false;
    }
    /**
     * @param int $idLang
     * @param bool $activeCountries
     * @param bool $activeCarriers
     * @param bool|null $containStates
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_delivered_countries($id_lang, $active_countries = false, $active_carriers = false, $contain_states = null)
    {
        $conn = Db::read_only();
        $states = $conn->get_array((new Db_Query())->select('s.*')->from('state', 's')->order_by('s.`name` ASC'));
        $result = $conn->get_array((new Db_Query())->select('cl.*, c.*, cl.`name` as `country`, z.`name` as `zone`')->from('country', 'c')->join(Shop::add_sql_association('country', 'c'))->left_join('country_lang', 'cl', 'cl.`id_country` = c.`id_country` AND cl.`id_lang` = ' . (int) $id_lang)->inner_join('carrier_zone', 'cz', 'cz.`id_zone` = c.`id_zone`')->inner_join('carrier', 'cr', 'cr.`id_carrier` = cz.`id_carrier`')->left_join('zone', 'z', 'cz.`id_zone` = z.`id_zone`')->where('cr.`deleted` = 0')->where($active_carriers ? 'cr.`active` = 1' : '')->where($active_countries ? 'c.`active` = 1' : '')->where(!is_null($contain_states) ? 'c.`contains_states` = ' . (int) $contain_states : '')->order_by('cl.`name` ASC'));
        $countries = [];
        foreach ($result as &$country) {
            $countries[$country['id_country']] = $country;
        }
        foreach ($states as &$state) {
            if (!isset($countries[$state['id_country']])) {
                continue;
            }
            /* Does not keep the state if its country has been disabled and not selected */
            if ($state['active'] != 1) {
                continue;
            }
            $countries[$state['id_country']]['states'][] = $state;
        }
        return $countries;
    }
    /**
     * Return the default carrier to use
     *
     * @param array $carriers
     * @param int $defaultCarrier the last carrier selected
     *
     * @return int the id of the default carrier
     *
     * @throws PrestaShopException
     */
    public static function get_default_carrier_selection($carriers, $default_carrier = 0)
    {
        if (empty($carriers)) {
            return 0;
        }
        if ((int) $default_carrier != 0) {
            foreach ($carriers as $carrier) {
                if ($carrier['id_carrier'] == (int) $default_carrier) {
                    return (int) $carrier['id_carrier'];
                }
            }
        }
        foreach ($carriers as $carrier) {
            if ($carrier['id_carrier'] == (int) Configuration::get('PS_CARRIER_DEFAULT')) {
                return (int) $carrier['id_carrier'];
            }
        }
        return (int) $carriers[0]['id_carrier'];
    }
    /**
     * Get carrier using the reference id
     *
     * @param int $idReference
     *
     * @return bool|Carrier
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_carrier_by_reference($id_reference)
    {
        $carrier_data = Db::read_only()->get_array((new Db_Query())->select('*')->from('carrier', 'c')->inner_join('carrier_lang', 'cl', 'cl.`id_carrier` = c.`id_carrier`')->where('c.`id_reference` = ' . (int) $id_reference)->where('c.`deleted` = 0')->where('cl.`id_shop` = ' . (int) Context::get_context()->shop->id)->order_by('c.`id_carrier` DESC'));
        if (!$carrier_data) {
            return false;
        }
        $carrier = new Carrier();
        $carrier->hydrate_multilang($carrier_data);
        return $carrier;
    }
    /**
     * For a given {product, warehouse}, gets the carrier available
     *
     * @param Product $product The id of the product, or an array with at least the package size and weight
     * @param int|null $idWarehouse
     * @param int $idAddressDelivery
     * @param int $idShop
     * @param Cart $cart
     * @param array $error contains an error message if an error occurs
     * @param int|null $combinationId calculate for specific product combination
     *
     * @return array
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public static function get_available_carrier_list(Product $product, $id_warehouse, $id_address_delivery = null, $id_shop = null, $cart = null, &$error = [], $combination_id = 0)
    {
        static $ps_country_default = null;
        if ($ps_country_default === null) {
            $ps_country_default = Configuration::get('PS_COUNTRY_DEFAULT');
        }
        if (is_null($id_shop)) {
            $id_shop = Context::get_context()->shop->id;
        }
        if (is_null($cart)) {
            $cart = Context::get_context()->cart;
        }
        if (!is_array($error)) {
            $error = [];
        }
        $id_address = (int) $id_address_delivery;
        if (!$id_address) {
            $id_address = (int) $cart->id_address_delivery;
        }
        if ($id_address) {
            $id_zone = Address::get_zone_by_id($id_address);
            // Check the country of the address is activated
            if (!Address::is_country_active_by_id($id_address)) {
                return [];
            }
        } else {
            $country = new Country($ps_country_default);
            $id_zone = $country->id_zone;
        }
        // Does the product is linked with carriers?
        $cache_id = 'Carrier::getAvailableCarrierList_' . (int) $product->id . '-' . (int) $id_shop;
        if (!Cache::is_stored($cache_id)) {
            $query = new Db_Query();
            $query->select('id_carrier');
            $query->from('product_carrier', 'pc');
            $query->inner_join('carrier', 'c', 'c.`id_reference` = pc.`id_carrier_reference` AND c.`deleted` = 0 AND c.`active` = 1');
            $query->where('pc.`id_product` = ' . (int) $product->id);
            $query->where('pc.`id_shop` = ' . (int) $id_shop);
            $carriers_for_product = Db::read_only()->get_array($query);
            Cache::store($cache_id, $carriers_for_product);
        } else {
            $carriers_for_product = Cache::retrieve($cache_id);
        }
        $carrier_list = [];
        if (!empty($carriers_for_product)) {
            //the product is linked with carriers
            foreach ($carriers_for_product as $carrier) {
                //check if the linked carriers are available in current zone
                if (static::check_carrier_zone($carrier['id_carrier'], $id_zone)) {
                    $carrier_list[$carrier['id_carrier']] = $carrier['id_carrier'];
                }
            }
            if (empty($carrier_list)) {
                return [];
            }
            //no linked carrier are available for this zone
        }
        // The product is not dirrectly linked with a carrier
        // Get all the carriers linked to a warehouse
        if ($id_warehouse) {
            $warehouse = new Warehouse($id_warehouse);
            $warehouse_carrier_list = $warehouse->get_carriers();
        }
        $available_carrier_list = [];
        $cache_id = 'Carrier::getAvailableCarrierList_getCarriersForOrder_' . (int) $id_zone . '-' . (int) $cart->id;
        if (!Cache::is_stored($cache_id)) {
            $customer = new Customer($cart->id_customer);
            $carrier_error = [];
            $carriers = static::get_carriers_for_order($id_zone, $customer->get_groups(), $cart, $carrier_error);
            Cache::store($cache_id, [$carriers, $carrier_error]);
        } else {
            [$carriers, $carrier_error] = Cache::retrieve($cache_id);
        }
        if (!$carriers) {
            $error = array_merge($error, $carrier_error);
        }
        foreach ($carriers as $carrier) {
            $available_carrier_list[$carrier['id_carrier']] = $carrier['id_carrier'];
        }
        if ($carrier_list) {
            $carrier_list = array_intersect($available_carrier_list, $carrier_list);
        } else {
            $carrier_list = $available_carrier_list;
        }
        if (isset($warehouse_carrier_list)) {
            $carrier_list = array_intersect($carrier_list, $warehouse_carrier_list);
        }
        $cart_weight = 0;
        foreach ($cart->get_products(false, false) as $cart_product) {
            if (isset($cart_product['weight_attribute']) && $cart_product['weight_attribute'] > 0) {
                $cart_weight += $cart_product['weight_attribute'] * $cart_product['cart_quantity'];
            } else {
                $cart_weight += $cart_product['weight'] * $cart_product['cart_quantity'];
            }
        }
        $product_weight = $product->get_weight($combination_id);
        foreach ($carrier_list as $key => $id_carrier) {
            $carrier = new Carrier($id_carrier);
            // Get the sizes of the carrier and the product and sort them to check if the carrier can take the product.
            $carrier_sizes = [(int) $carrier->max_width, (int) $carrier->max_height, (int) $carrier->max_depth];
            $product_sizes = [(int) round($product->get_width($combination_id)), (int) round($product->get_height($combination_id)), (int) round($product->get_depth($combination_id))];
            rsort($carrier_sizes, SORT_NUMERIC);
            rsort($product_sizes, SORT_NUMERIC);
            if ($carrier_sizes[0] > 0 && $carrier_sizes[0] < $product_sizes[0] || $carrier_sizes[1] > 0 && $carrier_sizes[1] < $product_sizes[1] || $carrier_sizes[2] > 0 && $carrier_sizes[2] < $product_sizes[2]) {
                $error[$carrier->id] = static::SHIPPING_SIZE_EXCEPTION;
                unset($carrier_list[$key]);
            }
            if ($carrier->min_total > 0 && $carrier->min_total > $cart->get_order_total($carrier->min_total_tax, Cart::BOTH_WITHOUT_SHIPPING)) {
                $error[$carrier->id] = static::SHIPPING_PRICE_EXCEPTION;
                unset($carrier_list[$key]);
            }
            if ($carrier->max_total > 0 && $carrier->max_total < $cart->get_order_total($carrier->max_total_tax, Cart::BOTH_WITHOUT_SHIPPING)) {
                $error[$carrier->id] = static::SHIPPING_PRICE_EXCEPTION;
                unset($carrier_list[$key]);
            }
            if ($carrier->min_weight > 0 && $cart_weight < $carrier->min_weight) {
                $error[$carrier->id] = static::SHIPPING_WEIGHT_EXCEPTION;
                unset($carrier_list[$key]);
            }
            if ($carrier->max_weight > 0 && $cart_weight > $carrier->max_weight) {
                $error[$carrier->id] = static::SHIPPING_WEIGHT_EXCEPTION;
                unset($carrier_list[$key]);
            }
            if ($carrier->max_weight > 0 && $product_weight > $carrier->max_weight) {
                $error[$carrier->id] = static::SHIPPING_WEIGHT_EXCEPTION;
                unset($carrier_list[$key]);
            }
        }
        return $carrier_list;
    }
    /**
     * @param int $idCarrier
     * @param int $idZone
     *
     * @return int|null
     *
     * @throws PrestaShopException
     */
    public static function check_carrier_zone($id_carrier, $id_zone)
    {
        $cache_id = 'Carrier::checkCarrierZone_' . (int) $id_carrier . '-' . (int) $id_zone;
        if (!Cache::is_stored($cache_id)) {
            $result = Db::read_only()->get_value((new Db_Query())->select('c.`id_carrier`')->from('carrier', 'c')->left_join('carrier_zone', 'cz', 'cz.`id_carrier` = c.`id_carrier`')->left_join('zone', 'z', 'z.`id_zone` = ' . (int) $id_zone)->where('c.`id_carrier` = ' . (int) $id_carrier)->where('c.`deleted` = 0')->where('c.`active` = 1')->where('cz.`id_zone` = ' . (int) $id_zone)->where('z.`active` = 1'));
            Cache::store($cache_id, $result);
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * @param int $idZone
     * @param array $groups group of the customer
     * @param Cart|null $cart
     * @param array $error contains an error message if an error occurs
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_carriers_for_order($id_zone, $groups = null, $cart = null, &$error = [])
    {
        $context = Context::get_context();
        $id_lang = $context->language->id;
        if (is_null($cart)) {
            $cart = $context->cart;
        }
        if (isset($context->currency)) {
            $id_currency = $context->currency->id;
        }
        if (is_array($groups) && !empty($groups)) {
            $result = static::get_carriers($id_lang, true, false, (int) $id_zone, $groups, static::PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
        } else {
            $result = static::get_carriers($id_lang, true, false, (int) $id_zone, [Configuration::get('PS_UNIDENTIFIED_GROUP')], static::PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE);
        }
        $results_array = [];
        foreach ($result as $k => $row) {
            $carrier = new Carrier((int) $row['id_carrier']);
            $shipping_method = $carrier->get_shipping_method();
            if ($shipping_method != static::SHIPPING_METHOD_FREE) {
                // Get only carriers that are compliant with shipping method
                if ($shipping_method === static::SHIPPING_METHOD_WEIGHT && $carrier->get_max_delivery_price_by_weight($id_zone) === false) {
                    $error[$carrier->id] = static::SHIPPING_WEIGHT_EXCEPTION;
                    unset($result[$k]);
                    continue;
                }
                if ($shipping_method === static::SHIPPING_METHOD_PRICE && $carrier->get_max_delivery_price_by_price($id_zone) === false) {
                    $error[$carrier->id] = static::SHIPPING_PRICE_EXCEPTION;
                    unset($result[$k]);
                    continue;
                }
                // If out-of-range behavior carrier is set on "Desactivate carrier"
                if ($row['range_behavior']) {
                    // Get id zone
                    if (!$id_zone) {
                        $id_zone = (int) Country::get_id_zone($context->country->id);
                    }
                    // Get only carriers that have a range compatible with cart
                    if ($shipping_method === static::SHIPPING_METHOD_WEIGHT && !static::check_delivery_price_by_weight($row['id_carrier'], $cart->get_total_weight(), $id_zone)) {
                        $error[$carrier->id] = static::SHIPPING_WEIGHT_EXCEPTION;
                        unset($result[$k]);
                        continue;
                    }
                    if ($shipping_method === static::SHIPPING_METHOD_PRICE && !static::check_delivery_price_by_price($row['id_carrier'], $cart->get_order_total(true, Cart::BOTH_WITHOUT_SHIPPING), $id_zone, $id_currency)) {
                        $error[$carrier->id] = static::SHIPPING_PRICE_EXCEPTION;
                        unset($result[$k]);
                        continue;
                    }
                }
            }
            $row['price'] = $shipping_method === static::SHIPPING_METHOD_FREE ? 0 : $cart->get_package_shipping_cost((int) $row['id_carrier'], true, null, null, $id_zone);
            $row['price_tax_exc'] = $shipping_method === static::SHIPPING_METHOD_FREE ? 0 : $cart->get_package_shipping_cost((int) $row['id_carrier'], false, null, null, $id_zone);
            $file_extension = Image_Manager::get_default_image_extension();
            $row['img'] = file_exists(_PS_SHIP_IMG_DIR_ . (int) $row['id_carrier'] . '.' . $file_extension) ? _THEME_SHIP_DIR_ . (int) $row['id_carrier'] . '.' . $file_extension : '';
            // If price is false, then the carrier is unavailable (carrier module)
            if ($row['price'] === false) {
                unset($result[$k]);
                continue;
            }
            $results_array[] = $row;
        }
        // if we have to sort carriers by price
        $prices = [];
        if (Configuration::get('PS_CARRIER_DEFAULT_SORT') === static::SORT_BY_PRICE) {
            foreach ($results_array as $r) {
                $prices[] = $r['price'];
            }
            if (Configuration::get('PS_CARRIER_DEFAULT_ORDER') === static::SORT_BY_ASC) {
                array_multisort($prices, SORT_ASC, SORT_NUMERIC, $results_array);
            } else {
                array_multisort($prices, SORT_DESC, SORT_NUMERIC, $results_array);
            }
        }
        return $results_array;
    }
    /**
     * Get all carriers in a given language
     *
     * WARNING: by default this method only returns native carrier and excludes carriers added by modules!
     *
     * @param int $idLang Language id
     * @param bool $active Returns only active carriers when true
     *
     * @param bool $delete
     * @param bool $idZone
     * @param int[]|null $idsGroup
     * @param int $modulesFilters Possible values:
     *                             PS_CARRIERS_ONLY
     *                             CARRIERS_MODULE
     *                             CARRIERS_MODULE_NEED_RANGE
     *                             PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE
     *                             ALL_CARRIERS
     *
     * @return array Carriers
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @todo    Check if the query has been fixed and remove the EXISTS subquery ^MD
     */
    public static function get_carriers($id_lang, $active = false, $delete = false, $id_zone = false, $ids_group = null, $modules_filters = self::PS_CARRIERS_ONLY)
    {
        // Filter by groups and no groups => return empty array
        if ($ids_group && (!is_array($ids_group) || !count($ids_group))) {
            return [];
        }
        $sql = (new Db_Query())->select('c.*, cl.`delay`, cl.`display_name`')->from('carrier', 'c')->left_join('carrier_lang', 'cl', 'c.`id_carrier` = cl.`id_carrier` AND cl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('cl'))->left_join('carrier_zone', 'cz', 'cz.`id_carrier` = c.`id_carrier`')->join(Shop::add_sql_association('carrier', 'c'))->where('c.`deleted` = ' . ($delete ? '1' : '0'));
        if ($id_zone) {
            $sql->left_join('zone', 'z', 'z.`id_zone` = ' . (int) $id_zone);
            $sql->where('cz.`id_zone` = ' . (int) $id_zone);
            $sql->where('z.`active` = 1');
        }
        if ($active) {
            $sql->where('c.`active` = 1');
        }
        if ($ids_group) {
            $sql->where('EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'carrier_group
									WHERE ' . _DB_PREFIX_ . 'carrier_group.id_carrier = c.id_carrier
									AND id_group IN (' . implode(',', array_map(intval(...), $ids_group)) . '))');
        }
        switch ($modules_filters) {
            case static::PS_CARRIERS_ONLY:
                $sql->where('c.`is_module` = 0');
                break;
            case static::CARRIERS_MODULE:
                $sql->where('c.`is_module` = 1');
                break;
            case static::CARRIERS_MODULE_NEED_RANGE:
                $sql->where('c.`is_module` = 1 AND c.`need_range` = 1');
                break;
            case static::PS_CARRIERS_AND_CARRIER_MODULES_NEED_RANGE:
                $sql->where('c.`is_module` = 0 OR c.`need_range` = 1');
                break;
        }
        $sql->group_by('c.`id_carrier`');
        $sql->order_by('c.`position` ASC');
        $cache_id = 'Carrier::getCarriers_' . md5((string) $sql->build());
        if (!Cache::is_stored($cache_id)) {
            $carriers = Db::read_only()->get_array($sql);
            Cache::store($cache_id, $carriers);
        } else {
            $carriers = Cache::retrieve($cache_id);
        }
        foreach ($carriers as &$carrier) {
            if ($carrier['display_name']) {
                $carrier['name'] = $carrier['display_name'];
            }
            $carrier['name'] = static::expand_name($carrier['name']);
        }
        return $carriers;
    }
    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    public function get_shipping_method()
    {
        if ($this->is_free) {
            return static::SHIPPING_METHOD_FREE;
        }
        $method = (int) $this->shipping_method;
        if ($this->shipping_method === static::SHIPPING_METHOD_DEFAULT) {
            // backward compatibility
            if ((int) Configuration::get('PS_SHIPPING_METHOD')) {
                $method = static::SHIPPING_METHOD_WEIGHT;
            } else {
                $method = static::SHIPPING_METHOD_PRICE;
            }
        }
        return $method;
    }
    /**
     * @param int $idZone
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function get_max_delivery_price_by_weight($id_zone)
    {
        $cache_id = 'Carrier::getMaxDeliveryPriceByWeight_' . (int) $this->id . '-' . (int) $id_zone;
        if (!Cache::is_stored($cache_id)) {
            $result = Db::read_only()->get_value((new Db_Query())->select('d.`price`')->from('delivery', 'd')->inner_join('range_weight', 'w', 'd.`id_range_weight` = w.`id_range_weight`')->where('d.`id_zone` = ' . (int) $id_zone)->where('d.`id_carrier` = ' . (int) $this->id . ' ' . static::sql_delivery_range_shop('range_weight'))->order_by('w.`delimiter2` DESC'));
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * @param int $idZone
     *
     * @return float|false
     *
     * @throws PrestaShopException
     */
    public function get_max_delivery_price_by_price($id_zone)
    {
        $cache_id = 'Carrier::getMaxDeliveryPriceByPrice_' . (int) $this->id . '-' . (int) $id_zone;
        if (!Cache::is_stored($cache_id)) {
            $result = Db::read_only()->get_value((new Db_Query())->select('d.`price`')->from('delivery', 'd')->inner_join('range_price', 'r', 'd.`id_range_price` = r.`id_range_price`')->where('d.`id_zone` = ' . (int) $id_zone)->where('d.`id_carrier` = ' . (int) $this->id . ' ' . static::sql_delivery_range_shop('range_price'))->order_by('r.`delimiter2` DESC'));
            Cache::store($cache_id, $result);
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * @param int $idCarrier
     * @param float $totalWeight
     * @param int $idZone
     *
     * @return float
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function check_delivery_price_by_weight($id_carrier, $total_weight, $id_zone)
    {
        $id_carrier = (int) $id_carrier;
        $cache_key = $id_carrier . '_' . $total_weight . '_' . $id_zone;
        if (!isset(static::$price_by_weight2[$cache_key])) {
            $result = Db::read_only()->get_row((new Db_Query())->select('d.`price`')->from('delivery', 'd')->left_join('range_weight', 'w', 'd.`id_range_weight` = w.`id_range_weight`')->where('d.`id_zone` = ' . (int) $id_zone)->where((float) $total_weight . ' >= w.`delimiter1`')->where((float) $total_weight . ' < w.`delimiter2`')->where('d.`id_carrier` = ' . $id_carrier . ' ' . static::sql_delivery_range_shop('range_weight'))->order_by('w.`delimiter1` ASC'));
            static::$price_by_weight2[$cache_key] = isset($result['price']);
        }
        $price_by_weight = Hook::get_first_response('actionDeliveryPriceByWeight', ['id_carrier' => $id_carrier, 'total_weight' => $total_weight, 'id_zone' => $id_zone]);
        if (is_numeric($price_by_weight)) {
            static::$price_by_weight2[$cache_key] = round($price_by_weight, _TB_PRICE_DATABASE_PRECISION_);
        }
        return static::$price_by_weight2[$cache_key];
    }
    /**
     * Check delivery prices for a given order
     *
     * @param int $idCarrier
     * @param float $orderTotal Order total to pay
     * @param int $idZone Zone id (for customer delivery address)
     * @param int|null $idCurrency
     *
     * @return float Delivery price
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function check_delivery_price_by_price($id_carrier, $order_total, $id_zone, $id_currency = null)
    {
        $id_carrier = (int) $id_carrier;
        $cache_key = $id_carrier . '_' . $order_total . '_' . $id_zone . '_' . $id_currency;
        if (!isset(static::$price_by_price2[$cache_key])) {
            if (!empty($id_currency)) {
                $order_total = Tools::convert_price($order_total, $id_currency, false);
            }
            $result = Db::read_only()->get_row((new Db_Query())->select('d.`price`')->from('delivery', 'd')->left_join('range_price', 'r', 'd.`id_range_price` = r.`id_range_price`')->where('d.`id_zone` = ' . (int) $id_zone)->where((float) $order_total . ' >= r.`delimiter1`')->where((float) $order_total . ' < r.`delimiter2`')->where('d.`id_carrier` = ' . $id_carrier . ' ' . static::sql_delivery_range_shop('range_price'))->order_by('r.`delimiter1` ASC'));
            static::$price_by_price2[$cache_key] = isset($result['price']);
        }
        $price_by_price = Hook::get_first_response('actionDeliveryPriceByPrice', ['id_carrier' => $id_carrier, 'order_total' => $order_total, 'id_zone' => $id_zone]);
        if (is_numeric($price_by_price)) {
            static::$price_by_price2[$cache_key] = round($price_by_price, _TB_PRICE_DATABASE_PRECISION_);
        }
        return static::$price_by_price2[$cache_key];
    }
    /**
     * Assign one (ore more) group to all carriers
     *
     * @param int|array $idGroupList group id or list of group ids
     * @param array $exception list of id carriers to ignore
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function assign_group_to_all_carriers($id_group_list, $exception = null)
    {
        if (!is_array($id_group_list)) {
            $id_group_list = [$id_group_list];
        }
        Db::get_instance()->delete('carrier_group', '`id_group` IN (' . implode(',', $id_group_list) . ')');
        $carrier_list = Db::read_only()->get_array((new Db_Query())->select('`id_carrier`')->from('carrier')->where('`deleted` = 0')->where(is_array($exception) ? '`id_carrier` NOT IN (' . implode(',', $exception) . ')' : ''));
        if ($carrier_list) {
            $data = [];
            foreach ($carrier_list as $carrier) {
                foreach ($id_group_list as $id_group) {
                    $data[] = ['id_carrier' => $carrier['id_carrier'], 'id_group' => $id_group];
                }
            }
            return Db::get_instance()->insert('carrier_group', $data, false, false, Db::INSERT);
        }
        return true;
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if ($this->position <= 0) {
            $this->position = static::get_higher_position() + 1;
        }
        $this->fix_names();
        if (!parent::add($auto_date, $null_values) || !Validate::is_loaded_object($this)) {
            return false;
        }
        if (!$count = Db::read_only()->get_value('SELECT count(`id_carrier`) FROM `' . _DB_PREFIX_ . $this->def['table'] . '` WHERE `deleted` = 0')) {
            return false;
        }
        if ($count == 1) {
            Configuration::update_value('PS_CARRIER_DEFAULT', (int) $this->id);
        }
        // Register reference
        Db::get_instance()->execute('UPDATE `' . _DB_PREFIX_ . $this->def['table'] . '` SET `id_reference` = ' . $this->id . ' WHERE `id_carrier` = ' . $this->id);
        return true;
    }
    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        $this->fix_names();
        return parent::update($null_values);
    }
    /**
     * Gets the highest carrier position
     *
     * @return int $position
     *
     * @throws PrestaShopException
     */
    public static function get_higher_position()
    {
        $position = Db::read_only()->get_value((new Db_Query())->select('MAX(`position`)')->from('carrier')->where('`deleted` = 0'));
        return is_numeric($position) ? $position : -1;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }
        static::clean_positions();
        $conn = Db::get_instance();
        return $conn->delete('cart_rule_carrier', '`id_carrier` = ' . (int) $this->id) && $conn->delete('module_carrier', '`id_reference` = ' . (int) $this->id_reference) && $this->delete_tax_rules_group(Shop::get_shops(true, null, true));
    }
    /**
     * Reorders carrier positions.
     * Called after deleting a carrier.
     *
     * @return bool $return
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function clean_positions()
    {
        $return = true;
        $result = Db::read_only()->get_array((new Db_Query())->select('`id_carrier`')->from('carrier')->where('`deleted` = 0')->order_by('`position` ASC'));
        $i = 0;
        foreach ($result as $value) {
            $return = Db::get_instance()->update('carrier', ['position' => $i++], '`id_carrier` = ' . (int) $value['id_carrier']);
        }
        return $return;
    }
    /**
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_tax_rules_group(?array $shops = null)
    {
        if (!$shops) {
            $shops = Shop::get_context_list_shop_id();
        }
        $where = 'id_carrier = ' . (int) $this->id;
        if ($shops) {
            $where .= ' AND id_shop IN(' . implode(', ', array_map(intval(...), $shops)) . ')';
        }
        return Db::get_instance()->delete('carrier_tax_rules_group_shop', $where);
    }
    /**
     * Change carrier id in delivery prices when updating a carrier
     *
     * @param int $idOld Old id carrier
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_configuration($id_old): void
    {
        Db::get_instance()->update('delivery', ['id_carrier' => (int) $this->id], '`id_carrier` = ' . (int) $id_old);
    }
    /**
     * Get delivery prices for a given order
     *
     * @param float $totalWeight Total order weight
     * @param int $idZone Zone ID (for customer delivery address)
     *
     * @return float Delivery price
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_delivery_price_by_weight($total_weight, $id_zone)
    {
        $id_carrier = (int) $this->id;
        $cache_key = $id_carrier . '_' . $total_weight . '_' . $id_zone;
        if (!isset(static::$price_by_weight[$cache_key])) {
            $result = Db::read_only()->get_row((new Db_Query())->select('d.`price`')->from('delivery', 'd')->left_join('range_weight', 'w', 'd.`id_range_weight` = w.`id_range_weight`')->where('d.`id_zone` = ' . (int) $id_zone)->where((float) $total_weight . ' >= w.`delimiter1`')->where((float) $total_weight . ' < w.`delimiter2`')->where('d.`id_carrier` = ' . $id_carrier . ' ' . static::sql_delivery_range_shop('range_weight'))->order_by('w.`delimiter1` ASC'));
            if (!isset($result['price'])) {
                static::$price_by_weight[$cache_key] = $this->get_max_delivery_price_by_weight($id_zone);
            } else {
                static::$price_by_weight[$cache_key] = $result['price'];
            }
        }
        $price_by_weight = Hook::get_first_response('actionDeliveryPriceByWeight', ['id_carrier' => $id_carrier, 'total_weight' => $total_weight, 'id_zone' => $id_zone]);
        if (is_numeric($price_by_weight)) {
            static::$price_by_weight[$cache_key] = round($price_by_weight, _TB_PRICE_DATABASE_PRECISION_);
        }
        return static::$price_by_weight[$cache_key];
    }
    /**
     * Get delivery prices for a given order
     *
     * @param float $orderTotal Order total to pay
     * @param int $idZone Zone id (for customer delivery address)
     * @param int|null $idCurrency
     *
     * @return float Delivery price
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_delivery_price_by_price($order_total, $id_zone, $id_currency = null)
    {
        $id_carrier = (int) $this->id;
        $cache_key = $this->id . '_' . $order_total . '_' . $id_zone . '_' . $id_currency;
        if (!isset(static::$price_by_price[$cache_key])) {
            if (!empty($id_currency)) {
                $order_total = Tools::convert_price($order_total, $id_currency, false);
            }
            $result = Db::read_only()->get_row((new Db_Query())->select('d.`price`')->from('delivery', 'd')->left_join('range_price', 'r', 'd.`id_range_price` = r.`id_range_price`')->where('d.`id_zone` = ' . (int) $id_zone)->where((float) $order_total . ' >= r.`delimiter1`')->where((float) $order_total . ' < r.`delimiter2`')->where('d.`id_carrier` = ' . $id_carrier . ' ' . static::sql_delivery_range_shop('range_price'))->order_by('r.`delimiter1` ASC'));
            if (!isset($result['price'])) {
                static::$price_by_price[$cache_key] = $this->get_max_delivery_price_by_price($id_zone);
            } else {
                static::$price_by_price[$cache_key] = $result['price'];
            }
        }
        $price_by_price = Hook::get_first_response('actionDeliveryPriceByPrice', ['id_carrier' => $id_carrier, 'order_total' => $order_total, 'id_zone' => $id_zone]);
        if (is_numeric($price_by_price)) {
            static::$price_by_price[$cache_key] = round($price_by_price, _TB_PRICE_DATABASE_PRECISION_);
        }
        return static::$price_by_price[$cache_key];
    }
    /**
     * Get all zones
     *
     * @return array Zones
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_zones()
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('carrier_zone', 'cz')->left_join('zone', 'z', 'cz.`id_zone` = z.`id_zone`')->where('cz.`id_carrier` = ' . (int) $this->id));
    }
    /**
     * Get a specific zones
     *
     * @param int $idZone
     *
     * @return array Zone
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_zone($id_zone)
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('carrier_zone')->where('`id_carrier` = ' . (int) $this->id)->where('`id_zone` = ' . (int) $id_zone));
    }
    /**
     * Add zone
     *
     * @param int $idZone
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_zone($id_zone)
    {
        $conn = Db::get_instance();
        if ($conn->insert('carrier_zone', ['id_carrier' => (int) $this->id, 'id_zone' => (int) $id_zone])) {
            // Get all ranges for this carrier
            $range_prices = Range_Price::get_ranges($this->id);
            $range_weights = Range_Weight::get_ranges($this->id);
            // Create row in ps_delivery table
            if (count($range_prices) || count($range_weights)) {
                $insert = [];
                if (count($range_prices)) {
                    foreach ($range_prices as $range) {
                        $insert[] = ['id_carrier' => (int) $this->id, 'id_range_price' => (int) $range['id_range_price'], 'id_range_weight' => 0, 'id_zone' => (int) $id_zone, 'price' => 0];
                    }
                }
                if (count($range_weights)) {
                    foreach ($range_weights as $range) {
                        $insert[] = ['id_carrier' => (int) $this->id, 'id_range_price' => 0, 'id_range_weight' => (int) $range['id_range_weight'], 'id_zone' => (int) $id_zone, 'price' => 0];
                    }
                }
                return $conn->insert('delivery', $insert);
            }
            return true;
        }
        return false;
    }
    /**
     * Delete zone
     *
     * @param int $idZone
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_zone($id_zone)
    {
        if (Db::get_instance()->delete('carrier_zone', '`id_carrier` = ' . (int) $this->id . ' AND `id_zone` = ' . (int) $id_zone, 1)) {
            return Db::get_instance()->delete('delivery', '`id_carrier` = ' . (int) $this->id . ' AND `id_zone` = ' . (int) $id_zone);
        }
        return false;
    }
    /**
     * Gets a specific group
     *
     * @return array Group
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_groups()
    {
        return Db::read_only()->get_array((new Db_Query())->select('`id_group`')->from('carrier_group')->where('`id_carrier` = ' . (int) $this->id));
    }
    /**
     * Clean delivery prices (weight/price)
     *
     * @param string $rangeTable Table name to clean (weight or price according to shipping method)
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_delivery_price($range_table)
    {
        $where = '`id_carrier` = ' . (int) $this->id . ' AND (`id_' . bq_sql($range_table) . '` IS NOT NULL OR `id_' . bq_sql($range_table) . '` = 0) ';
        if (Shop::get_context() == Shop::CONTEXT_ALL) {
            $where .= 'AND id_shop IS NULL AND id_shop_group IS NULL';
        } elseif (Shop::get_context() == Shop::CONTEXT_GROUP) {
            $where .= 'AND id_shop IS NULL AND id_shop_group = ' . (int) Shop::get_context_shop_group_id();
        } else {
            $where .= 'AND id_shop = ' . (int) Shop::get_context_shop_id();
        }
        return Db::get_instance()->delete('delivery', $where);
    }
    /**
     * Add new delivery prices
     *
     * @param array $priceList Prices list in multiple arrays (changed to array since 1.5.0)
     * @param bool $delete
     *
     * @return bool Insertion result
     *
     * @throws PrestaShopException
     */
    public function add_delivery_price($price_list, $delete = false)
    {
        if (!$price_list) {
            return false;
        }
        $keys = array_keys($price_list[0]);
        if (!in_array('id_shop', $keys)) {
            $keys[] = 'id_shop';
        }
        if (!in_array('id_shop_group', $keys)) {
            $keys[] = 'id_shop_group';
        }
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'delivery` (' . implode(', ', $keys) . ') VALUES ';
        $db = Db::get_instance();
        foreach ($price_list as $values) {
            if (!isset($values['id_shop'])) {
                $values['id_shop'] = Shop::get_context() == Shop::CONTEXT_SHOP ? Shop::get_context_shop_id() : null;
            }
            if (!isset($values['id_shop_group'])) {
                $values['id_shop_group'] = Shop::get_context() != Shop::CONTEXT_ALL ? Shop::get_context_shop_group_id() : null;
            }
            if ($delete) {
                $db->execute('DELETE FROM `' . _DB_PREFIX_ . 'delivery`
                    WHERE ' . (is_null($values['id_shop']) ? 'ISNULL(`id_shop`) ' : 'id_shop = ' . (int) $values['id_shop']) . '
                    AND ' . (is_null($values['id_shop_group']) ? 'ISNULL(`id_shop`) ' : 'id_shop_group=' . (int) $values['id_shop_group']) . '
                    AND id_carrier=' . (int) $values['id_carrier'] . ($values['id_range_price'] !== null ? ' AND id_range_price=' . (int) $values['id_range_price'] : ' AND (ISNULL(`id_range_price`) OR `id_range_price` = 0)') . ($values['id_range_weight'] !== null ? ' AND id_range_weight=' . (int) $values['id_range_weight'] : ' AND (ISNULL(`id_range_weight`) OR `id_range_weight` = 0)') . '
					AND id_zone=' . (int) $values['id_zone']);
            }
            $sql .= '(';
            foreach ($values as $v) {
                if (is_null($v)) {
                    $sql .= 'NULL';
                } elseif (is_int($v) || is_float($v)) {
                    $sql .= $v;
                } else {
                    $sql .= '\'' . $v . '\'';
                }
                $sql .= ', ';
            }
            $sql = rtrim($sql, ', ') . '), ';
        }
        $sql = rtrim($sql, ', ');
        return $db->execute($sql);
    }
    /**
     * Copy old carrier informations when update carrier
     *
     * @param int $oldId Old id carrier (copy from that id)
     *
     * @throws PrestaShopException
     */
    public function copy_carrier_data($old_id): void
    {
        if (!Validate::is_unsigned_id($old_id)) {
            throw new Presta_Shop_Exception('Incorrect identifier for carrier');
        }
        if (!$this->id) {
            return;
        }
        $file_extension = Image_Manager::get_default_image_extension();
        if ($source_file = Image_Manager::get_source_image(_PS_SHIP_IMG_DIR_, $old_id)) {
            copy($source_file, _PS_SHIP_IMG_DIR_ . '/' . (int) $this->id . '.' . $file_extension);
        }
        if ($source_file_old_tmp_logo = Image_Manager::get_source_image(_PS_TMP_IMG_DIR_, 'carrier_mini_' . (int) $old_id)) {
            if (!isset($_FILES['logo'])) {
                copy($source_file_old_tmp_logo, _PS_TMP_IMG_DIR_ . '/carrier_mini_' . $this->id . '.' . $file_extension);
            }
            unlink($source_file_old_tmp_logo);
        }
        // Copy existing ranges price
        $conn = Db::get_instance();
        foreach (['range_price', 'range_weight'] as $range) {
            $res = $conn->get_array((new Db_Query())->select('`id_' . $range . '` as `id_range`, `delimiter1`, `delimiter2`')->from($range)->where('`id_carrier` = ' . (int) $old_id));
            if (count($res)) {
                foreach ($res as $val) {
                    $conn->insert($range, ['id_carrier' => (int) $this->id, 'delimiter1' => (float) $val['delimiter1'], 'delimiter2' => (float) $val['delimiter2']]);
                    $id_range = (int) $conn->Insert_ID();
                    $id_range_price = $range == 'range_price' ? $id_range : 'NULL';
                    $id_range_weight = $range == 'range_weight' ? $id_range : 'NULL';
                    $conn->execute('
						INSERT INTO `' . _DB_PREFIX_ . 'delivery` (`id_carrier`, `id_shop`, `id_shop_group`, `id_range_price`, `id_range_weight`, `id_zone`, `price`) (
							SELECT ' . (int) $this->id . ', `id_shop`, `id_shop_group`, ' . (int) $id_range_price . ', ' . (int) $id_range_weight . ', `id_zone`, `price`
							FROM `' . _DB_PREFIX_ . 'delivery`
							WHERE `id_carrier` = ' . (int) $old_id . '
							AND `id_' . $range . '` = ' . (int) $val['id_range'] . '
						)
					');
                }
            }
        }
        // Copy existing zones
        $res = $conn->get_array((new Db_Query())->select('*')->from('carrier_zone')->where('`id_carrier` = ' . (int) $old_id));
        foreach ($res as $val) {
            $conn->insert('carrier_zone', ['id_carrier' => (int) $this->id, 'id_zone' => (int) $val['id_zone']]);
        }
        //Copy default carrier
        if (Configuration::get('PS_CARRIER_DEFAULT') == $old_id) {
            Configuration::update_value('PS_CARRIER_DEFAULT', (int) $this->id);
        }
        // Copy reference
        $id_reference = $conn->get_value((new Db_Query())->select('`id_reference`')->from(bq_sql(static::$definition['table']))->where('`id_carrier` = ' . (int) $old_id));
        $conn->update(bq_sql(static::$definition['table']), ['id_reference' => (int) $id_reference], '`id_carrier` = ' . (int) $this->id);
        $this->id_reference = (int) $id_reference;
        // Copy tax rules group
        $conn->execute('INSERT INTO `' . _DB_PREFIX_ . 'carrier_tax_rules_group_shop` (`id_carrier`, `id_tax_rules_group`, `id_shop`)
												(SELECT ' . (int) $this->id . ', `id_tax_rules_group`, `id_shop`
													FROM `' . _DB_PREFIX_ . 'carrier_tax_rules_group_shop`
													WHERE `id_carrier`=' . (int) $old_id . ')');
    }
    /**
     * Check if carrier is used (at least one order placed)
     *
     * @return int Order count for this carrier
     *
     * @throws PrestaShopException
     */
    public function is_used()
    {
        $row = Db::read_only()->get_value((new Db_Query())->select('COUNT(`id_carrier`) as `total`')->from('orders')->where('`id_carrier` = ' . (int) $this->id));
        return (int) $row['total'];
    }
    /**
     * @return bool|string
     *
     * @throws PrestaShopException
     */
    public function get_range_table()
    {
        $shipping_method = $this->get_shipping_method();
        if ($shipping_method === static::SHIPPING_METHOD_WEIGHT) {
            return 'range_weight';
        }
        if ($shipping_method === static::SHIPPING_METHOD_PRICE) {
            return 'range_price';
        }
        return false;
    }
    /**
     * @param bool $shippingMethod
     *
     * @return bool|RangePrice|RangeWeight
     *
     * @throws PrestaShopException
     */
    public function get_range_object($shipping_method = false)
    {
        if (!$shipping_method) {
            $shipping_method = $this->get_shipping_method();
        }
        if ((int) $shipping_method === static::SHIPPING_METHOD_WEIGHT) {
            return new Range_Weight();
        }
        if ((int) $shipping_method === static::SHIPPING_METHOD_PRICE) {
            return new Range_Price();
        }
        return false;
    }
    /**
     * @param Currency|null $currency
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function get_range_suffix($currency = null)
    {
        if (!$currency) {
            $currency = Context::get_context()->currency;
        }
        if ($this->get_shipping_method() === static::SHIPPING_METHOD_PRICE) {
            return $currency->sign;
        }
        return Configuration::get('PS_WEIGHT_UNIT');
    }
    /**
     * @param int $idTaxRulesGroup
     * @param bool $allShops
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_tax_rules_group($id_tax_rules_group, $all_shops = false)
    {
        if (!Validate::is_unsigned_id($id_tax_rules_group)) {
            throw new Presta_Shop_Exception('Invalid tax rules group ID');
        }
        if (!$all_shops) {
            $shops = Shop::get_context_list_shop_id();
        } else {
            $shops = Shop::get_shops(true, null, true);
        }
        $this->delete_tax_rules_group($shops);
        $values = [];
        foreach ($shops as $id_shop) {
            $values[] = ['id_carrier' => (int) $this->id, 'id_tax_rules_group' => (int) $id_tax_rules_group, 'id_shop' => (int) $id_shop];
        }
        Cache::clean('carrier_id_tax_rules_group_' . (int) $this->id . '_' . (int) Context::get_context()->shop->id);
        return Db::get_instance()->insert('carrier_tax_rules_group_shop', $values);
    }
    /**
     * @param string $idTaxRulesGroup
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function set_ws_tax_rules_group($id_tax_rules_group)
    {
        return $this->set_tax_rules_group((int) $id_tax_rules_group);
    }
    /**
     * Returns the taxes rate associated to the carrier
     *
     *
     * @return float
     * @throws PrestaShopException
     */
    public function get_taxes_rate(Address $address)
    {
        $tax_calculator = $this->get_tax_calculator($address);
        return $tax_calculator->get_total_rate();
    }
    /**
     * Returns the taxes calculator associated to the carrier
     *
     * @param int|null $idOrder
     * @param bool $useAverageTaxOfProducts
     *
     * @return AverageTaxOfProductsTaxCalculator|TaxCalculator
     * @throws PrestaShopException
     */
    public function get_tax_calculator(Address $address, $id_order = null, $use_average_tax_of_products = false)
    {
        if ($use_average_tax_of_products) {
            return Adapter_service_Locator::get('AverageTaxOfProductsTaxCalculator')->set_id_order($id_order);
        }
        $tax_manager = Tax_Manager_Factory::get_manager($address, $this->get_id_tax_rules_group());
        return $tax_manager->get_tax_calculator();
    }
    /**
     * Moves a carrier
     *
     * @param bool $way Up (1) or Down (0)
     * @param int $position
     *
     * @return bool Update result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_position($way, $position)
    {
        if (!$res = Db::read_only()->get_array((new Db_Query())->select('`id_carrier`, `position`')->from('carrier')->where('`deleted` = 0')->order_by('`position` ASC'))) {
            return false;
        }
        foreach ($res as $carrier) {
            if ((int) $carrier['id_carrier'] == (int) $this->id) {
                $moved_carrier = $carrier;
            }
        }
        if (!isset($moved_carrier) || !isset($position)) {
            return false;
        }
        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $conn = Db::get_instance();
        return $conn->update('carrier', ['position' => ['type' => 'sql', 'value' => '`position` ' . ($way ? '- 1' : '+ 1')]], '`position` ' . ($way ? '> ' . (int) $moved_carrier['position'] . ' AND `position` <= ' . (int) $position : '< ' . (int) $moved_carrier['position'] . ' AND `position` >= ' . (int) $position . ' AND `deleted` = 0')) && $conn->update('carrier', ['position' => (int) $position], '`id_carrier` = ' . (int) $moved_carrier['id_carrier']);
    }
    /**
     * @param array $groups
     * @param bool $delete
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_groups($groups, $delete = true)
    {
        if ($delete) {
            Db::get_instance()->delete('carrier_group', '`id_carrier` = ' . (int) $this->id);
        }
        if (!is_array($groups) || !count($groups)) {
            return true;
        }
        $insert = [];
        foreach ($groups as $id_group) {
            $insert[] = ['id_carrier' => (int) $this->id, 'id_group' => (int) $id_group];
        }
        return Db::get_instance()->insert('carrier_group', $insert);
    }
    /**
     * @param TableSchema $table
     */
    public static function process_table_schema($table): void
    {
        if ($table->get_name_without_prefix() === 'carrier_lang') {
            $table->reorder_columns(['id_carrier', 'id_shop', 'id_lang']);
        }
    }
    /**
     * Helper method to resolve $carrier->name based on displayable name. Property $carrier->name
     * exists for backwards compatibility only, should not be used directly
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    protected function fix_names()
    {
        // if display_name is not assigned, but name is, we use it for initialization
        if (is_array($this->display_name)) {
            $this->display_name = array_filter($this->display_name);
        }
        if (!$this->display_name && is_string($this->name) && strlen($this->name) > 0) {
            if ($this->id_lang) {
                $this->display_name = static::expand_name($this->name);
            } else {
                $this->display_name = [];
                foreach (Language::get_languages(false, false, true) as $lang) {
                    $this->display_name[$lang] = static::expand_name($this->name);
                }
            }
        }
        $this->name = $this->get_name();
    }
    /**
     * Resolves carrier name
     *
     * @return string
     * @throws PrestaShopException
     */
    public function get_name()
    {
        if (is_string($this->display_name) && strlen($this->display_name) > 0) {
            return static::expand_name($this->display_name);
        }
        if (is_array($this->display_name)) {
            $languages = array_unique(array_merge(array_filter([(int) $this->id_lang, (int) Context::get_context()->language->id, (int) Configuration::get('PS_LANG_DEFAULT')]), Language::get_languages(true, null, true)));
            foreach ($languages as $lang) {
                if (array_key_exists($lang, $this->display_name)) {
                    $name = $this->display_name[$lang];
                    if (strlen($name) > 0) {
                        return static::expand_name($name);
                    }
                }
            }
        }
        return static::expand_name($this->name);
    }
    /**
     * Helper method that expands placeholder carrier name '0' to Shop Name
     *
     * @param string $name
     * @return string
     * @throws PrestaShopException
     */
    public static function expand_name($name)
    {
        if ($name) {
            return $name;
        }
        $carrier_name = static::get_carrier_name_from_shop_name();
        return $carrier_name ?: '0';
    }
    /**
     * @throws PrestaShopException
     */
    public static function initialization_callback(Db $conn): void
    {
        $conn->execute('UPDATE ' . _DB_PREFIX_ . 'carrier_lang l ' . 'INNER JOIN ' . _DB_PREFIX_ . 'carrier c ON (c.id_carrier = l.id_carrier) ' . 'SET l.display_name = c.name ' . 'WHERE l.display_name = "" AND c.name != ""');
    }
}