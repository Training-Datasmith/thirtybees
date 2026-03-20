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
 * Class AddressCore
 */
class Address_Core extends Object_Model
{
    /** @var int Customer id which address belongs to */
    public $id_customer;
    /** @var int Manufacturer id which address belongs to */
    public $id_manufacturer;
    /** @var int Supplier id which address belongs to */
    public $id_supplier;
    /**
     * @var int Warehouse id which address belongs to
     */
    public $id_warehouse;
    /** @var int Country id */
    public $id_country;
    /** @var int State id */
    public $id_state;
    /** @var string Country name */
    public $country;
    /** @var string Alias (eg. Home, Work...) */
    public $alias;
    /** @var string Company (optional) */
    public $company;
    /** @var string Lastname */
    public $lastname;
    /** @var string Firstname */
    public $firstname;
    /** @var string Address first line */
    public $address1;
    /** @var string Address second line (optional) */
    public $address2;
    /** @var string Postal code */
    public $postcode;
    /** @var string City */
    public $city;
    /** @var string Any other useful information */
    public $other;
    /** @var string Phone number */
    public $phone;
    /** @var string Mobile phone number */
    public $phone_mobile;
    /** @var string VAT number */
    public $vat_number;
    /** @var string DNI number */
    public $dni;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var bool True if address has been deleted (staying in database as deleted) */
    public $deleted = 0;
    /** @var bool True if address is active */
    public $active = 1;
    /**
     * @var array
     */
    protected static $_id_zones = [];
    /**
     * @var array
     */
    protected static $_id_countries = [];
    /**
     * @var bool
     */
    protected $_include_container = false;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'address', 'primary' => 'id_address', 'fields' => ['id_country' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_state' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId'], 'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false, 'dbDefault' => '0'], 'id_manufacturer' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false, 'dbDefault' => '0'], 'id_supplier' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false, 'dbDefault' => '0'], 'id_warehouse' => ['type' => self::TYPE_INT, 'validate' => 'isNullOrUnsignedId', 'copy_post' => false, 'dbDefault' => '0'], 'alias' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32], 'company' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64], 'lastname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32], 'firstname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32], 'address1' => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'required' => true, 'size' => 128], 'address2' => ['type' => self::TYPE_STRING, 'validate' => 'isAddress', 'size' => 128], 'postcode' => ['type' => self::TYPE_STRING, 'validate' => 'isPostCode', 'size' => 12], 'city' => ['type' => self::TYPE_STRING, 'validate' => 'isCityName', 'required' => true, 'size' => 64], 'other' => ['type' => self::TYPE_STRING, 'validate' => 'isMessage', 'size' => 300], 'phone' => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 32], 'phone_mobile' => ['type' => self::TYPE_STRING, 'validate' => 'isPhoneNumber', 'size' => 32], 'vat_number' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32], 'dni' => ['type' => self::TYPE_STRING, 'validate' => 'isDniLite', 'size' => 16], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false, 'dbNullable' => false], 'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false, 'dbNullable' => false], 'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false, 'dbDefault' => '1'], 'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false, 'dbDefault' => '0']], 'keys' => ['address' => ['address_customer' => ['type' => Object_Model::KEY, 'columns' => ['id_customer']], 'id_country' => ['type' => Object_Model::KEY, 'columns' => ['id_country']], 'id_manufacturer' => ['type' => Object_Model::KEY, 'columns' => ['id_manufacturer']], 'id_state' => ['type' => Object_Model::KEY, 'columns' => ['id_state']], 'id_supplier' => ['type' => Object_Model::KEY, 'columns' => ['id_supplier']], 'id_warehouse' => ['type' => Object_Model::KEY, 'columns' => ['id_warehouse']]]]];
    /**
     * @var array
     */
    protected $webservice_parameters = ['objectsNodeName' => 'addresses', 'fields' => ['id_customer' => ['xlink_resource' => 'customers'], 'id_manufacturer' => ['xlink_resource' => 'manufacturers'], 'id_supplier' => ['xlink_resource' => 'suppliers'], 'id_warehouse' => ['xlink_resource' => 'warehouse'], 'id_country' => ['xlink_resource' => 'countries'], 'id_state' => ['xlink_resource' => 'states']]];
    /**
     * Build an address
     *
     * @param int $idAddress Existing address id in order to load object (optional)
     * @param int|null $idLang
     *
     * @throws PrestaShopException
     */
    public function __construct($id_address = null, $id_lang = null)
    {
        parent::__construct($id_address);
        /* Get and cache address country name */
        if ($this->id) {
            $this->country = Country::get_name_by_id($id_lang ?: Configuration::get('PS_LANG_DEFAULT'), $this->id_country);
        }
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if (!parent::add($auto_date, $null_values)) {
            return false;
        }
        if (Validate::is_unsigned_id($this->id_customer)) {
            Customer::reset_address_cache($this->id_customer, $this->id);
        }
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
        // Empty related caches
        if (isset(static::$_id_countries[$this->id])) {
            unset(static::$_id_countries[$this->id]);
        }
        if (isset(static::$_id_zones[$this->id])) {
            unset(static::$_id_zones[$this->id]);
        }
        if (Validate::is_unsigned_id($this->id_customer)) {
            Customer::reset_address_cache($this->id_customer, $this->id);
        }
        return parent::update($null_values);
    }
    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (Validate::is_unsigned_id($this->id_customer)) {
            Customer::reset_address_cache($this->id_customer, $this->id);
        }
        if (!$this->is_used()) {
            return parent::delete();
        }
        $this->deleted = true;
        return $this->update();
    }
    /**
     * Returns fields required for an address in an array hash
     *
     * @return array
     */
    public static function get_fields_validate()
    {
        return array_filter(array_map(fn(array $field) => $field['validate'] ?? null, static::$definition['fields']));
    }
    /**
     * Get zone id for a given address
     *
     * @param int $idAddress Address id for which we want to get zone id
     *
     * @return int|false Zone id
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_zone_by_id($id_address)
    {
        if (empty($id_address)) {
            return false;
        }
        $id_address = (int) $id_address;
        if (isset(static::$_id_zones[$id_address])) {
            return static::$_id_zones[$id_address];
        }
        $id_zone = Hook::get_first_response('actionGetIDZoneByAddressID', ['id_address' => $id_address]);
        if (is_numeric($id_zone)) {
            static::$_id_zones[$id_address] = (int) $id_zone;
            return static::$_id_zones[$id_address];
        }
        $result = Db::read_only()->get_row((new Db_Query())->select('s.`id_zone` AS `id_zone_state`, c.`id_zone`')->from('address', 'a')->left_join('country', 'c', 'c.`id_country` = a.`id_country`')->left_join('state', 's', 's.`id_state` = a.`id_state` AND c.`contains_states` = 1')->where('a.`id_address` = ' . $id_address));
        $zone_id = false;
        if ($result) {
            $zone_id = (int) $result['id_zone_state'];
            if (!$zone_id) {
                $zone_id = (int) $result['id_zone'];
            }
        }
        static::$_id_zones[$id_address] = $zone_id;
        return $zone_id;
    }
    /**
     * Check if zone, country, and state of an address are active
     *
     * @param int $idAddress Address id for which we want to get active status
     *
     * @return int address active status
     *
     * @throws PrestaShopException
     */
    public static function is_country_active_by_id($id_address)
    {
        if (empty($id_address)) {
            return false;
        }
        $cache_id = 'Address::isCountryActiveById_' . (int) $id_address;
        if (!Cache::is_stored($cache_id)) {
            $result = (bool) Db::read_only()->getvalue((new Db_Query())->select('(IFNULL(c.`active`, 0) AND IFNULL(z.`active`, 0) AND IFNULL(s.`active`, 1)) AS `active`')->from('address', 'a')->left_join('country', 'c', 'c.`id_country` = a.`id_country`')->left_join('state', 's', 's.id_country = c.id_country AND s.id_state = a.id_state')->left_join('zone', 'z', 'z.id_zone = c.id_zone')->where('a.`id_address` = ' . (int) $id_address));
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Check if the address is deleted in the database
     *
     * @param int $idAddress
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function is_deleted($id_address)
    {
        $row = Db::read_only()->get_row((new Db_Query())->select('a.`deleted`')->from(bq_sql(Address::$definition['table']), 'a')->where('`id_address` = ' . (int) $id_address));
        if (!is_array($row) || !isset($row['deleted'])) {
            return true;
        }
        return (bool) $row['deleted'];
    }
    /**
     * Check if address is used (at least one order placed)
     *
     * @return int Order count for this address
     *
     * @throws PrestaShopException
     */
    public function is_used()
    {
        $result = (int) Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from('orders')->where('`id_address_delivery` = ' . (int) $this->id . ' OR `id_address_invoice` = ' . (int) $this->id));
        return $result > 0 ? $result : false;
    }
    /**
     * @param int $idAddress
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_country_and_state($id_address)
    {
        $id_address = (int) $id_address;
        if (isset(static::$_id_countries[$id_address])) {
            return static::$_id_countries[$id_address];
        }
        if ($id_address) {
            $result = Db::read_only()->get_row((new Db_Query())->select('`id_country`, `id_state`, `vat_number`, `postcode`')->from('address')->where('`id_address` = ' . $id_address));
        } else {
            $result = false;
        }
        static::$_id_countries[$id_address] = $result;
        return $result;
    }
    /**
     * Specify if an address is already in base
     *
     * @param int $idAddress Address id
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function address_exists($id_address)
    {
        $key = 'address_exists_' . (int) $id_address;
        if (!Cache::is_stored($key)) {
            $id_address = Db::read_only()->get_value((new Db_Query())->select('a.`id_address`')->from('address', 'a')->where('a.`id_address` = ' . (int) $id_address));
            Cache::store($key, (bool) $id_address);
            return (bool) $id_address;
        }
        return Cache::retrieve($key);
    }
    /**
     * @param int $idCustomer
     * @param bool $active
     *
     * @return bool|int|null
     *
     * @throws PrestaShopException
     */
    public static function get_first_customer_address_id($id_customer, $active = true)
    {
        if (!$id_customer) {
            return false;
        }
        $cache_id = 'Address::getFirstCustomerAddressId_' . (int) $id_customer . '-' . (bool) $active;
        if (!Cache::is_stored($cache_id)) {
            $result = (int) Db::read_only()->get_value((new Db_Query())->select('`id_address`')->from('address')->where('`id_customer` = ' . (int) $id_customer)->where('`deleted` = 0')->where($active ? '`active` = 1' : ''));
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Initiliaze an address corresponding to the specified id address or if empty to the
     * default shop configuration
     *
     * @param int $idAddress
     * @param bool $withGeoLocation Unused
     *
     * @return Address address
     *
     * @throws PrestaShopException
     */
    public static function initialize($id_address = null, $with_geo_location = false)
    {
        $context = Context::get_context();
        $exists = (int) $id_address && Address::address_exists($id_address);
        if ($exists) {
            $context_hash = (int) $id_address;
        } else {
            $context_hash = md5((int) $context->country->id);
        }
        $cache_id = 'Address::initialize_' . $context_hash;
        if (!Cache::is_stored($cache_id)) {
            // if an id_address has been specified retrieve the address
            if ($exists) {
                $address = new Address((int) $id_address);
                if (!Validate::is_loaded_object($address)) {
                    throw new Presta_Shop_Exception('Invalid address #' . (int) $id_address);
                }
            } else {
                // set the default address
                $address = new Address();
                $address->id_country = (int) $context->country->id;
                $address->id_state = 0;
                $address->postcode = 0;
            }
            Cache::store($cache_id, $address);
            return $address;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Returns id_address for a given id_supplier
     *
     * @param int $idSupplier
     *
     * @return int $id_address
     *
     * @throws PrestaShopException
     */
    public static function get_address_id_by_supplier_id($id_supplier)
    {
        return Db::read_only()->get_value((new Db_Query())->select('id_address')->from('address')->where('id_supplier = ' . (int) $id_supplier)->where('deleted = 0')->where('id_customer = 0')->where('id_manufacturer = 0')->where('id_warehouse = 0'));
    }
    /**
     * @param string $alias
     * @param int $idAddress
     * @param int $idCustomer
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public static function alias_exist($alias, $id_address, $id_customer)
    {
        return Db::read_only()->get_value((new Db_Query())->select('count(*)')->from('address')->where('alias = \'' . p_sql($alias) . '\'')->where('id_address != ' . (int) $id_address)->where('id_customer = ' . (int) $id_customer)->where('deleted = 0'));
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_fields_required_db()
    {
        $this->cache_fields_required_database(false);
        return static::$fields_required_database['Address'] ?? [];
    }
}