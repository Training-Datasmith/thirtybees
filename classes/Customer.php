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
 * Class CustomerCore
 */
class Customer_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'customer', 'primary' => 'id_customer', 'fields' => ['id_shop_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false, 'dbDefault' => '1'], 'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false, 'dbDefault' => '1'], 'id_gender' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false], 'id_default_group' => ['type' => self::TYPE_INT, 'copy_post' => false, 'dbDefault' => '1'], 'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'copy_post' => false], 'id_risk' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'copy_post' => false, 'dbDefault' => '1'], 'company' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64], 'siret' => ['type' => self::TYPE_STRING, 'validate' => 'isSiret', 'size' => 14], 'ape' => ['type' => self::TYPE_STRING, 'validate' => 'isApe', 'size' => 5], 'firstname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32], 'lastname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32], 'email' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 128], 'passwd' => ['type' => self::TYPE_STRING, 'validate' => 'isPasswd', 'required' => true, 'size' => 60], 'last_passwd_gen' => ['type' => self::TYPE_DATE, 'copy_post' => false, 'dbType' => 'timestamp', 'dbDefault' => Object_Model::DEFAULT_CURRENT_TIMESTAMP], 'birthday' => ['type' => self::TYPE_DATE, 'validate' => 'isBirthDate', 'dbType' => 'date'], 'newsletter' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'], 'ip_registration_newsletter' => ['type' => self::TYPE_STRING, 'copy_post' => false, 'size' => 45], 'newsletter_date_add' => ['type' => self::TYPE_DATE, 'copy_post' => false], 'optin' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'], 'website' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'size' => 128], 'outstanding_allow_amount' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'copy_post' => false, 'dbDefault' => '0.000000'], 'show_public_prices' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false, 'dbDefault' => '0'], 'max_payment_days' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'copy_post' => false, 'dbDefault' => '60'], 'secure_key' => ['type' => self::TYPE_STRING, 'validate' => 'isMd5', 'copy_post' => false, 'size' => 32, 'dbDefault' => '-1'], 'note' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'copy_post' => false, 'size' => Object_Model::SIZE_TEXT], 'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false, 'dbDefault' => '0'], 'is_guest' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false, 'dbType' => 'tinyint(1)', 'dbDefault' => '0'], 'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'copy_post' => false, 'dbType' => 'tinyint(1)', 'dbDefault' => '0'], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false, 'dbNullable' => false], 'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'copy_post' => false, 'dbNullable' => false]], 'keys' => ['customer' => ['customer_email' => ['type' => Object_Model::KEY, 'columns' => ['email']], 'customer_login' => ['type' => Object_Model::KEY, 'columns' => ['email', 'passwd']], 'id_customer_passwd' => ['type' => Object_Model::KEY, 'columns' => ['id_customer', 'passwd']], 'id_gender' => ['type' => Object_Model::KEY, 'columns' => ['id_gender']], 'id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop', 'date_add']], 'id_shop_group' => ['type' => Object_Model::KEY, 'columns' => ['id_shop_group']]]]];
    public const DEFAULT_MERGE_OPERATIONS = ['customer' => 'delete', 'address' => 'update', 'cart' => 'update', 'customer_thread' => 'update', 'guest' => 'update', 'message' => 'update', 'order_return' => 'update', 'order_slip' => 'update', 'orders' => 'update', 'specific_price' => 'update', 'cart_rule' => 'delete', 'compare' => 'delete', 'customer_group' => 'delete'];
    /**
     * @var array
     */
    protected static $_default_group_id = [];
    /**
     * @var array
     */
    protected static $_customer_has_address = [];
    /**
     * @var array
     */
    protected static $_customer_groups = [];
    /**
     * @var int
     */
    public $id_shop_group;
    /** @var string Secure key */
    public $secure_key;
    /** @var string protected note */
    public $note;
    /** @var int Gender ID */
    public $id_gender = 0;
    /** @var int Default group ID */
    public $id_default_group;
    /** @var int Current language used by the customer */
    public $id_lang;
    /** @var string Lastname */
    public $lastname;
    /** @var string Firstname */
    public $firstname;
    /** @var string Birthday (yyyy-mm-dd) */
    public $birthday;
    /** @var string e-mail */
    public $email;
    /** @var bool Newsletter subscription */
    public $newsletter;
    /** @var string Newsletter ip registration */
    public $ip_registration_newsletter;
    /** @var string Newsletter ip registration */
    public $newsletter_date_add;
    /** @var bool Opt-in subscription */
    public $optin;
    /** @var string WebSite */
    public $website;
    /** @var string Company */
    public $company;
    /** @var string SIRET */
    public $siret;
    /** @var string APE */
    public $ape;
    /** @var float Outstanding allow amount (B2B opt) */
    public $outstanding_allow_amount = 0;
    /** @var bool Show public prices (B2B opt) */
    public $show_public_prices = 0;
    /** @var int Risk ID (B2B opt) */
    public $id_risk;
    /** @var int Max payment day */
    public $max_payment_days = 0;
    /** @var string Password */
    public $passwd;
    /** @var string Datetime Password */
    public $last_passwd_gen;
    /** @var bool Status */
    public $active = true;
    /** @var bool Status */
    public $is_guest = 0;
    /** @var bool True if carrier has been deleted (staying in database as deleted) */
    public $deleted = 0;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /**
     * @var int
     */
    public $years;
    /**
     * @var int
     */
    public $days;
    /**
     * @var int
     */
    public $months;
    /**
     * @var bool is the customer logged in
     */
    public $logged = 0;
    /**
     * @var int id_guest meaning the guest table, not the guest customer
     */
    public $id_guest;
    /**
     * @var array
     */
    public $group_box;
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['fields' => ['id_default_group' => ['xlink_resource' => 'groups'], 'id_lang' => ['xlink_resource' => 'languages'], 'newsletter_date_add' => [], 'ip_registration_newsletter' => [], 'last_passwd_gen' => ['setter' => null], 'secure_key' => ['setter' => null], 'deleted' => [], 'passwd' => ['setter' => 'setWsPasswd']], 'associations' => ['groups' => ['resource' => 'group']]];
    /**
     * CustomerCore constructor.
     *
     * @param int|null $id
     *
     * @throws PrestaShopException
     */
    public function __construct($id = null)
    {
        $this->id_default_group = (int) Configuration::get('PS_CUSTOMER_GROUP');
        parent::__construct($id);
    }
    /**
     * Return customers list
     *
     * @param bool|null $onlyActive Returns only active customers when true
     *
     * @return array Customers
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_customers($only_active = null)
    {
        $sql = new Db_Query();
        $sql->select('`id_customer`, `email`, `firstname`, `lastname`');
        $sql->from(bq_sql(static::$definition['table']));
        $sql->where('1 ' . Shop::add_sql_restriction(Shop::SHARE_CUSTOMER));
        if ($only_active) {
            $sql->where('`active` = 1');
        }
        $sql->order_by('`id_customer` ASC');
        return Db::read_only()->get_array($sql);
    }
    /**
     * Retrieve customers by email address
     *
     * @param string $email
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_customers_by_email($email)
    {
        $sql = new Db_Query();
        $sql->select('*');
        $sql->from(bq_sql(static::$definition['table']));
        $sql->where('`email` = \'' . p_sql($email) . '\' ' . Shop::add_sql_restriction(Shop::SHARE_CUSTOMER));
        return Db::read_only()->get_array($sql);
    }
    /**
     * Check id the customer is active or not
     *
     * @param int $idCustomer
     *
     * @return bool customer validity
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function is_banned($id_customer)
    {
        if (!Validate::is_unsigned_id($id_customer)) {
            return true;
        }
        $cache_id = 'Customer::isBanned_' . (int) $id_customer;
        if (!Cache::is_stored($cache_id)) {
            $sql = new Db_Query();
            $sql->select('`id_customer`');
            $sql->from(bq_sql(static::$definition['table']));
            $sql->where('`id_customer` = ' . (int) $id_customer);
            $sql->where('`active` = 1');
            $sql->where('`deleted` = 0');
            $result = !Db::read_only()->get_row($sql);
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Check if e-mail is already registered in database
     *
     * @param string $email e-mail
     * @param bool $returnId boolean
     * @param bool $ignoreGuest boolean, to exclude guest customer
     *
     * @return int|bool if found, false otherwise
     *
     * @throws PrestaShopException
     */
    public static function customer_exists($email, $return_id = false, $ignore_guest = true)
    {
        if (!Validate::is_email($email)) {
            return false;
        }
        $sql = new Db_Query();
        $sql->select('`id_customer`');
        $sql->from(bq_sql(static::$definition['table']));
        $sql->where('`email` = \'' . p_sql($email) . '\' ' . Shop::add_sql_restriction(Shop::SHARE_CUSTOMER));
        if ($ignore_guest) {
            $sql->where('`is_guest` = 0');
        }
        $result = Db::read_only()->get_value($sql);
        return $return_id ? (int) $result : (bool) $result;
    }
    /**
     * Check if an address is owned by a customer
     *
     * @param int $idCustomer Customer ID
     * @param int $idAddress Address ID
     *
     * @return bool result
     * @throws PrestaShopException
     */
    public static function customer_has_address($id_customer, $id_address)
    {
        $key = (int) $id_customer . '-' . (int) $id_address;
        if (!array_key_exists($key, static::$_customer_has_address)) {
            static::$_customer_has_address[$key] = (bool) Db::read_only()->get_value('
			SELECT `id_address`
			FROM `' . _DB_PREFIX_ . 'address`
			WHERE `id_customer` = ' . (int) $id_customer . '
			AND `id_address` = ' . (int) $id_address . '
			AND `deleted` = 0');
        }
        return static::$_customer_has_address[$key];
    }
    /**
     * @param int $idCustomer
     * @param int $idAddress
     */
    public static function reset_address_cache($id_customer, $id_address): void
    {
        $key = (int) $id_customer . '-' . (int) $id_address;
        if (array_key_exists($key, static::$_customer_has_address)) {
            unset(static::$_customer_has_address[$key]);
        }
    }
    /**
     * Count the number of addresses for a customer
     *
     * @param int $idCustomer Customer ID
     *
     * @return int Number of addresses
     *
     * @throws PrestaShopException
     */
    public static function get_addresses_total_by_id($id_customer)
    {
        return Db::read_only()->get_value('
			SELECT COUNT(`id_address`)
			FROM `' . _DB_PREFIX_ . 'address`
			WHERE `id_customer` = ' . (int) $id_customer . '
			AND `deleted` = 0');
    }
    /**
     * Light back office search for customers
     *
     * @param string $query Searched string
     * @param int|null $limit Limit query results
     *
     * @return array Corresponding customers
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public static function search_by_name($query, $limit = null)
    {
        $sql_base = 'SELECT *
				FROM `' . _DB_PREFIX_ . 'customer`';
        $sql = '(' . $sql_base . ' WHERE `email` LIKE \'%' . p_sql($query) . '%\' ' . Shop::add_sql_restriction(Shop::SHARE_CUSTOMER) . ')';
        $sql .= ' UNION (' . $sql_base . ' WHERE `id_customer` = ' . (int) $query . ' ' . Shop::add_sql_restriction(Shop::SHARE_CUSTOMER) . ')';
        $sql .= ' UNION (' . $sql_base . ' WHERE `lastname` LIKE \'%' . p_sql($query) . '%\' ' . Shop::add_sql_restriction(Shop::SHARE_CUSTOMER) . ')';
        $sql .= ' UNION (' . $sql_base . ' WHERE `firstname` LIKE \'%' . p_sql($query) . '%\' ' . Shop::add_sql_restriction(Shop::SHARE_CUSTOMER) . ')';
        if ($limit) {
            $sql .= ' LIMIT 0, ' . (int) $limit;
        }
        return Db::read_only()->get_array($sql);
    }
    /**
     * Search for customers by ip address
     *
     * @param string $ip Searched string
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function search_by_ip($ip)
    {
        return Db::read_only()->get_array('
		SELECT DISTINCT c.*
		FROM `' . _DB_PREFIX_ . 'customer` c
		LEFT JOIN `' . _DB_PREFIX_ . 'guest` g ON g.id_customer = c.id_customer
		LEFT JOIN `' . _DB_PREFIX_ . 'connections` co ON g.id_guest = co.id_guest
		WHERE co.`ip_address` = \'' . (int) ip2long(trim($ip)) . '\'');
    }
    /**
     * @param int $idCustomer
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_default_group_id($id_customer)
    {
        if (!Group::is_feature_active()) {
            static $ps_customer_group = null;
            if ($ps_customer_group === null) {
                $ps_customer_group = Configuration::get('PS_CUSTOMER_GROUP');
            }
            return $ps_customer_group;
        }
        if (!isset(static::$_default_group_id[(int) $id_customer])) {
            static::$_default_group_id[(int) $id_customer] = Db::read_only()->get_value('
				SELECT `id_default_group`
				FROM `' . _DB_PREFIX_ . 'customer`
				WHERE `id_customer` = ' . (int) $id_customer);
        }
        return static::$_default_group_id[(int) $id_customer];
    }
    /**
     * @param int $idCustomer
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_current_country($id_customer, ?Cart $cart = null)
    {
        if (!$cart) {
            $cart = Context::get_context()->cart;
        }
        if (!$cart || !$cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}) {
            $id_address = (int) Db::read_only()->get_value('
				SELECT `id_address`
				FROM `' . _DB_PREFIX_ . 'address`
				WHERE `id_customer` = ' . (int) $id_customer . '
				AND `deleted` = 0 ORDER BY `id_address`');
        } else {
            $id_address = $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
        }
        if ($id_address) {
            $ids = Address::get_country_and_state($id_address);
            if (is_array($ids) && (int) $ids['id_country']) {
                return (int) $ids['id_country'];
            }
        }
        return (int) Configuration::get('PS_COUNTRY_DEFAULT');
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = true)
    {
        $this->id_shop = $this->id_shop ?: Context::get_context()->shop->id;
        $this->id_shop_group = $this->id_shop_group ?: Context::get_context()->shop->id_shop_group;
        $this->id_lang = $this->id_lang ?: Context::get_context()->language->id;
        $this->birthday = empty($this->years) ? $this->birthday : (int) $this->years . '-' . (int) $this->months . '-' . (int) $this->days;
        $this->secure_key = md5(uniqid(random_int(0, mt_getrandmax()), true));
        $this->last_passwd_gen = date('Y-m-d H:i:s', strtotime('-' . Configuration::get('PS_PASSWD_TIME_FRONT') . 'minutes'));
        if ($this->newsletter && !Validate::is_date($this->newsletter_date_add)) {
            $this->newsletter_date_add = date('Y-m-d H:i:s');
        }
        if ($this->id_default_group == Configuration::get('PS_CUSTOMER_GROUP')) {
            if ($this->is_guest) {
                $this->id_default_group = (int) Configuration::get('PS_GUEST_GROUP');
            } else {
                $this->id_default_group = (int) Configuration::get('PS_CUSTOMER_GROUP');
            }
        }
        /* Can't create a guest customer, if this feature is disabled */
        if ($this->is_guest && !Configuration::get('PS_GUEST_CHECKOUT_ENABLED')) {
            return false;
        }
        $success = parent::add($auto_date, $null_values);
        $this->update_group($this->group_box);
        return $success;
    }
    /**
     * Update customer groups associated to the object
     *
     * @param array $list groups
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_group($list): void
    {
        if (!empty($list)) {
            $this->clean_groups();
            $this->add_groups($list);
        } else {
            $this->add_groups([$this->id_default_group]);
        }
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function clean_groups()
    {
        return Db::get_instance()->delete('customer_group', 'id_customer = ' . (int) $this->id);
    }
    /**
     * @param int[] $groups
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_groups($groups): void
    {
        foreach ($groups as $group) {
            $row = ['id_customer' => (int) $this->id, 'id_group' => (int) $group];
            Db::get_instance()->insert('customer_group', $row, false, true, Db::INSERT_IGNORE);
        }
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (Validate::is_loaded_object($this)) {
            $customer_id = (int) $this->id;
            if (!Order::get_customer_orders($customer_id)) {
                $addresses = $this->get_addresses((int) Configuration::get('PS_LANG_DEFAULT'));
                foreach ($addresses as $address) {
                    $obj = new Address((int) $address['id_address']);
                    $obj->delete();
                }
            }
            $conn = Db::get_instance();
            $conn->delete('customer_group', 'id_customer = ' . $customer_id);
            $conn->delete('message', 'id_customer = ' . $customer_id);
            $conn->delete('specific_price', 'id_customer = ' . $customer_id);
            $conn->delete('compare', 'id_customer = ' . $customer_id);
            $carts = $conn->get_array('SELECT id_cart FROM ' . _DB_PREFIX_ . 'cart WHERE id_customer=' . $customer_id);
            foreach ($carts as $cart) {
                $cart_id = (int) $cart['id_cart'];
                $conn->delete('cart', 'id_cart = ' . $cart_id);
                $conn->delete('cart_product', 'id_cart = ' . $cart_id);
            }
            $cts = $conn->get_array('SELECT id_customer_thread FROM ' . _DB_PREFIX_ . 'customer_thread WHERE id_customer=' . $customer_id);
            foreach ($cts as $ct) {
                $customer_thread_id = (int) $ct['id_customer_thread'];
                $conn->delete('customer_thread', 'id_customer_thread = ' . $customer_thread_id);
                $conn->delete('customer_message', 'id_customer_thread = ' . $customer_thread_id);
            }
            Cart_Rule::delete_by_id_customer($customer_id);
        }
        return parent::delete();
    }
    /**
     * Return customer addresses
     *
     * @param int $idLang Language ID
     *
     * @return array Addresses
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_addresses($id_lang)
    {
        $share_order = (bool) Context::get_context()->shop->get_group()->share_order;
        $cache_id = 'Customer::getAddresses' . (int) $this->id . '-' . (int) $id_lang . '-' . $share_order;
        if (!Cache::is_stored($cache_id)) {
            $result = Db::read_only()->get_array((new Db_Query())->select('DISTINCT a.*, cl.`name` AS `country`, s.`name` AS `state`, s.`iso_code` AS `state_iso`')->from('address', 'a')->left_join('country', 'c', 'a.`id_country` = c.`id_country`')->left_join('country_lang', 'cl', 'c.`id_country` = cl.`id_country` AND cl.`id_lang` = ' . (int) $id_lang)->left_join('state', 's', 's.`id_state` = a.`id_state`')->join($share_order ? '' : Shop::add_sql_association('country', 'c'))->where('a.`id_customer` = ' . (int) $this->id)->where('a.`deleted` = 0'));
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Return customer instance from its e-mail (optionally check password)
     *
     * @param string $email E-mail
     * @param string $plainTextPassword Password is also checked if specified
     * @param bool $ignoreGuest
     *
     * @return self | false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_by_email($email, $plain_text_password = null, $ignore_guest = true)
    {
        if (!Validate::is_email($email)) {
            throw new Presta_Shop_Exception(Tools::display_error('Invalid email address'));
        }
        $sql = new Db_Query();
        $sql->select('*');
        $sql->from(bq_sql(static::$definition['table']));
        $sql->where('`email` = \'' . p_sql($email) . '\' ' . Shop::add_sql_restriction(Shop::SHARE_CUSTOMER));
        $sql->where('`deleted` = 0');
        if ($ignore_guest) {
            $sql->where('`is_guest` = 0');
        }
        $result = Db::read_only()->get_row($sql);
        if (!$result) {
            return false;
        }
        // If password is provided but doesn't match.
        if ($plain_text_password && !password_verify($plain_text_password, (string) $result['passwd'])) {
            // Check if it matches the legacy md5 hashing and, if it does, rehash it.
            if (Validate::is_md5($result['passwd']) && $result['passwd'] === md5(_COOKIE_KEY_ . $plain_text_password)) {
                $new_hash = Tools::hash($plain_text_password);
                Db::get_instance()->update(bq_sql(static::$definition['table']), ['passwd' => p_sql($new_hash)], '`id_customer` = ' . (int) $result['id_customer']);
                $result['passwd'] = $new_hash;
            } else {
                return false;
            }
        }
        $this->id = $result['id_customer'];
        foreach ($result as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        return $this;
    }
    /**
     * Return several useful statistics about customer
     *
     * @return array Stats
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_stats()
    {
        $id = (int) $this->id;
        $result = ['nb_orders' => 0, 'total_orders' => 0, 'last_visit' => null, 'age' => '--'];
        if ($id) {
            $conn = Db::read_only();
            $res = $conn->get_row((new Db_Query())->select('COUNT(`id_order`) AS `nb_orders`, SUM(`total_paid` / o.`conversion_rate`) AS `total_orders`')->from('orders', 'o')->where('o.`id_customer` = ' . $id)->where('o.`valid` = 1'));
            if (is_array($res)) {
                $result['nb_orders'] = (int) $res['nb_orders'];
                $result['total_orders'] = (int) $res['total_orders'];
            }
            $result['last_visit'] = $conn->get_value((new Db_Query())->select('MAX(c.`date_add`) AS `last_visit`')->from('guest', 'g')->left_join('connections', 'c', 'c.`id_guest` = g.`id_guest`')->where('g.`id_customer` = ' . $id));
            $age = $conn->get_value((new Db_Query())->select('(YEAR(CURRENT_DATE)-YEAR(c.`birthday`)) - (RIGHT(CURRENT_DATE, 5) < RIGHT(c.`birthday`, 5)) AS `age`')->from('customer', 'c')->where('c.`id_customer` = ' . $id));
            $result['age'] = $age != date('Y') ? $age : '--';
        }
        return $result;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_last_emails()
    {
        if (!$this->id) {
            return [];
        }
        return Db::read_only()->get_array((new Db_Query())->select('m.*, l.`name` as `language`')->from('mail', 'm')->left_join('lang', 'l', 'm.`id_lang` = l.`id_lang`')->where('`recipient` = \'' . p_sql($this->email) . '\'')->order_by('m.`date_add` DESC')->limit(10));
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_last_connections()
    {
        if (!$this->id) {
            return [];
        }
        return Db::read_only()->get_array((new Db_Query())->select('c.`id_connections`, c.`date_add`, COUNT(cp.`id_page`) AS `pages`')->select('TIMEDIFF(MAX(cp.time_end), c.date_add) AS time, http_referer,INET_NTOA(ip_address) AS ipaddress')->from('guest', 'g')->left_join('connections', 'c', 'c.`id_guest` = g.`id_guest`')->left_join('connections_page', 'cp', 'c.`id_connections` = cp.`id_connections`')->where('g.`id_customer` = ' . (int) $this->id)->group_by('c.`id_connections`')->order_by('c.`date_add` DESC')->limit(10));
    }
    /**
     * @param int $idCustomer
     *
     * @return int|null
     *
     * @throws PrestaShopException
     */
    public function customer_id_exists($id_customer)
    {
        return Customer::customer_id_exists_static((int) $id_customer);
    }
    /**
     * @param int $idCustomer
     *
     * @return int|null
     *
     * @throws PrestaShopException
     */
    public static function customer_id_exists_static($id_customer)
    {
        $cache_id = 'Customer::customerIdExistsStatic' . (int) $id_customer;
        if (!Cache::is_stored($cache_id)) {
            $result = (int) Db::read_only()->get_value((new Db_Query())->select('`id_customer`')->from('customer', 'c')->where('c.`id_customer` = ' . (int) $id_customer));
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * @return int[]
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_groups()
    {
        return Customer::get_groups_static((int) $this->id);
    }
    /**
     * @param int $idCustomer
     *
     * @return int[]
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_groups_static($id_customer)
    {
        if (!Group::is_feature_active()) {
            return [Configuration::get('PS_CUSTOMER_GROUP')];
        }
        if ($id_customer == 0) {
            static::$_customer_groups[$id_customer] = [(int) Configuration::get('PS_UNIDENTIFIED_GROUP')];
        }
        if (!isset(static::$_customer_groups[$id_customer])) {
            static::$_customer_groups[$id_customer] = [];
            $result = Db::read_only()->get_array((new Db_Query())->select('cg.`id_group`')->from('customer_group', 'cg')->where('cg.`id_customer` = ' . (int) $id_customer));
            foreach ($result as $group) {
                static::$_customer_groups[$id_customer][] = (int) $group['id_group'];
            }
        }
        return static::$_customer_groups[$id_customer];
    }
    /**
     * @deprecated since 1.0.0
     *
     * @return false
     */
    public function is_used()
    {
        Tools::display_as_deprecated();
        return false;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_bought_products()
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('orders', 'o')->left_join('order_detail', 'od', 'o.`id_order` = od.`id_order`')->where('o.`valid` = 1')->where('o.`id_customer` = ' . (int) $this->id));
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function toggle_status()
    {
        parent::toggle_status();
        /* Change status to active/inactive */
        return Db::get_instance()->update(bq_sql(static::$definition['table']), ['date_upd' => ['type' => 'sql', 'value' => 'NOW()']], '`' . bq_sql(static::$definition['primary']) . '` = ' . (int) $this->id);
    }
    /**
     * @param int $idLang
     * @param string|null $password
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function transform_to_customer($id_lang, $password = null)
    {
        if (!$this->is_guest()) {
            return false;
        }
        if (empty($password)) {
            $password = Tools::passwd_gen(8, 'RANDOM');
        }
        if (!Validate::is_passwd($password)) {
            return false;
        }
        $this->is_guest = 0;
        $this->id_default_group = (int) Configuration::get('PS_CUSTOMER_GROUP');
        // adds Customer group as default
        $this->passwd = Tools::hash($password);
        $this->clean_groups();
        $this->add_groups([Configuration::get('PS_CUSTOMER_GROUP')]);
        //associate to Customer group
        if ($this->update()) {
            $vars = ['{firstname}' => $this->firstname, '{lastname}' => $this->lastname, '{email}' => $this->email, '{passwd}' => '*******'];
            Mail::Send((int) $id_lang, 'guest_to_customer', Mail::l('Your guest account has been transformed into a customer account', (int) $id_lang), $vars, $this->email, $this->firstname . ' ' . $this->lastname, null, null, null, null, _PS_MAIL_DIR_, false, (int) $this->id_shop);
            return true;
        }
        return false;
    }
    /**
     * @return bool
     */
    public function is_guest()
    {
        return (bool) $this->is_guest;
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
        $this->birthday = empty($this->years) ? $this->birthday : (int) $this->years . '-' . (int) $this->months . '-' . (int) $this->days;
        if ($this->newsletter && !Validate::is_date($this->newsletter_date_add)) {
            $this->newsletter_date_add = date('Y-m-d H:i:s');
        }
        if (isset(Context::get_context()->controller) && Context::get_context()->controller->controller_type == 'admin') {
            $this->update_group($this->group_box);
        }
        if ($this->deleted) {
            $addresses = $this->get_addresses((int) Configuration::get('PS_LANG_DEFAULT'));
            foreach ($addresses as $address) {
                $obj = new Address((int) $address['id_address']);
                $obj->delete();
            }
        }
        return parent::update(true);
    }
    /**
     * @param string $passwd
     *
     * @return bool
     */
    public function set_ws_passwd($passwd)
    {
        if ($this->id == 0 || $this->passwd != $passwd) {
            $this->passwd = Tools::hash($passwd);
        }
        return true;
    }
    /**
     * Check customer informations and return customer validity
     *
     * @param bool $withGuest
     *
     * @return bool customer validity
     *
     * @throws PrestaShopException
     */
    public function is_logged($with_guest = false)
    {
        if (!$with_guest && $this->is_guest == 1) {
            return false;
        }
        /* Customer is valid only if it can be load and if object password is the same as database one */
        return $this->logged == 1 && $this->id && Validate::is_unsigned_id($this->id) && Customer::check_password($this->id, $this->passwd);
    }
    /**
     * Check if customer password is the right one
     *
     * @param int $idCustomer
     * @param string $plaintextOrHashedPassword Password
     *
     * @return bool result
     *
     * @todo    : adapt validation for hashed password
     * @todo    : find out why both hashed and plaintext password are passed
     * @throws PrestaShopException
     */
    public static function check_password($id_customer, $plaintext_or_hashed_password)
    {
        if (!Validate::is_unsigned_id($id_customer)) {
            return false;
        }
        if (Validate::is_md5($plaintext_or_hashed_password) || mb_substr($plaintext_or_hashed_password, 0, 4) === '$2y$') {
            $hashed_password = $plaintext_or_hashed_password;
            return static::check_password_in_database($id_customer, $hashed_password);
        }
        $hashed_password = Tools::encrypt($plaintext_or_hashed_password);
        if (static::check_password_in_database($id_customer, $hashed_password)) {
            return true;
        }
        $sql = new Db_Query();
        $sql->select('`passwd`');
        $sql->from(bq_sql(static::$definition['table']));
        $sql->where('`id_customer` = ' . (int) $id_customer);
        $hashed_password = Db::read_only()->get_value($sql);
        return password_verify($plaintext_or_hashed_password, $hashed_password);
    }
    /**
     * Check password validity via DB
     *
     * @param int $idCustomer
     * @param string $hashedPassword
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected static function check_password_in_database($id_customer, $hashed_password)
    {
        $cache_id = 'Customer::checkPassword' . (int) $id_customer . '-' . $hashed_password;
        if (!Cache::is_stored($cache_id)) {
            $sql = new Db_Query();
            $sql->select('`id_customer`');
            $sql->from(bq_sql(static::$definition['table']));
            $sql->where('`id_customer` = ' . (int) $id_customer);
            $sql->where('`passwd` = \'' . p_sql($hashed_password) . '\'');
            $result = (bool) Db::read_only()->get_value($sql);
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Logout
     *
     * @throws PrestaShopException
     */
    public function logout(): void
    {
        Hook::trigger_event('actionCustomerLogoutBefore', ['customer' => $this]);
        if (isset(Context::get_context()->cookie)) {
            Context::get_context()->cookie->delete();
        }
        $this->logged = 0;
        Hook::trigger_event('actionCustomerLogoutAfter', ['customer' => $this]);
    }
    /**
     * Soft logout, delete everything links to the customer
     * but leave there affiliate's informations
     *
     * @throws PrestaShopException
     */
    public function mylogout(): void
    {
        Hook::trigger_event('actionCustomerLogoutBefore', ['customer' => $this]);
        if (isset(Context::get_context()->cookie)) {
            Context::get_context()->cookie->mylogout();
        }
        $this->logged = 0;
        Hook::trigger_event('actionCustomerLogoutAfter', ['customer' => $this]);
    }
    /**
     * @param bool $withOrder
     *
     * @return bool|int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_last_cart($with_order = true)
    {
        $carts = Cart::get_customer_carts((int) $this->id, $with_order);
        if (!count($carts)) {
            return false;
        }
        $cart = array_shift($carts);
        $cart = new Cart((int) $cart['id_cart']);
        return $cart->nb_products() === 0 ? (int) $cart->id : false;
    }
    /**
     * @return float
     *
     * @throws PrestaShopException
     */
    public function get_outstanding()
    {
        $conn = Db::read_only();
        $total_paid = (float) $conn->get_value((new Db_Query())->select('SUM(oi.`total_paid_tax_incl`)')->from('order_invoice', 'oi')->left_join('orders', 'o', 'oi.`id_order` = o.`id_order`')->group_by('o.`id_customer`')->where('o.`id_customer` = ' . (int) $this->id));
        $total_rest = (float) $conn->get_value((new Db_Query())->select('SUM(op.`amount`)')->from('order_payment', 'op')->left_join('order_invoice_payment', 'oip', 'op.`id_order_payment` = oip.`id_order_payment`')->left_join('orders', 'o', 'oip.`id_order` = o.`id_order`')->group_by('o.`id_customer`')->where('o.`id_customer` = ' . (int) $this->id));
        return $total_paid - $total_rest;
    }
    /**
     * Return customer rank
     *
     * Return rank of customer among customers with at least one valid order.
     * If customer haven't place any order yet, this method returns null.
     *
     * @return int|null
     * @throws PrestaShopException
     */
    public function get_best_customer_rank()
    {
        $conn = Db::read_only();
        $total_paid = $conn->get_value((new Db_Query())->select('SUM(`total_paid` / `conversion_rate`)')->from('orders')->where('`id_customer` = ' . (int) $this->id)->where('`valid` = 1'));
        if ($total_paid) {
            $conn->get_value((new Db_Query())->select('SQL_CALC_FOUND_ROWS COUNT(*)')->from('orders')->where('`valid` = 1')->where('`id_customer` != ' . (int) $this->id)->group_by('id_customer')->having('SUM(`total_paid` / `conversion_rate`) > ' . $total_paid));
            return (int) $conn->get_value('SELECT FOUND_ROWS()') + 1;
        }
        return null;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @todo    Double-check the query, doesn't look right ^MD
     */
    public function get_ws_groups()
    {
        return Db::read_only()->get_array((new Db_Query())->select('cg.`id_group` AS `id`')->from('customer_group', 'cg')->join(Shop::add_sql_association('group', 'cg'))->where('cg.`id_customer` = ' . (int) $this->id));
    }
    /**
     * @param array $result
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_ws_groups($result)
    {
        $groups = [];
        foreach ($result as $row) {
            $groups[] = $row['id'];
        }
        $this->clean_groups();
        $this->add_groups($groups);
        return true;
    }
    /**
     * @param string $sqlJoin
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_webservice_object_list($sql_join, $sql_filter, $sql_sort, $sql_limit)
    {
        $sql_filter .= Shop::add_sql_restriction(Shop::SHARE_CUSTOMER, 'main');
        return parent::get_webservice_object_list($sql_join, $sql_filter, $sql_sort, $sql_limit);
    }
    /**
     * Methods merges two customer accounts, and deletes the $other account from the database
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function merge_accounts(Customer $target, Customer $source, array $tables_operations): void
    {
        $target_id = (int) $target->id;
        $source_id = (int) $source->id;
        if (!$target_id) {
            throw new Presta_Shop_Exception('Merge failed: invalid target customer id');
        }
        if (!$source_id) {
            throw new Presta_Shop_Exception('Merge failed: invalid source customer id');
        }
        $tables = array_merge(static::DEFAULT_MERGE_OPERATIONS, $tables_operations);
        $conn = Db::get_instance();
        // re-associate data
        unset($tables['customer']);
        foreach ($tables as $table => $operation) {
            if ($operation === 'update') {
                $conn->update($table, ['id_customer' => $target_id], 'id_customer = ' . $source_id);
            } elseif ($operation === 'delete') {
                $conn->delete($table, 'id_customer = ' . $source_id);
            } elseif (is_callable($operation)) {
                call_user_func($operation, $source_id, $target_id);
            }
        }
        // delete source customer
        $source->delete();
    }
}