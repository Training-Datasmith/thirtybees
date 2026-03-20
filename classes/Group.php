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
 * Class GroupCore
 */
class Group_Core extends Object_Model
{
    /**
     * @var array
     */
    protected static $cache_reduction = [];
    /**
     * @var array
     */
    protected static $group_price_display_method = [];
    /**
     * @var string|string[] Lastname
     */
    public $name;
    /**
     * @var float Reduction
     */
    public $reduction;
    /**
     * @var int Price display method (tax inc/tax exc)
     */
    public $price_display_method;
    /**
     * @var bool Show prices
     */
    public $show_prices = 1;
    /**
     * @var string Object creation date
     */
    public $date_add;
    /**
     * @var string Object last modification date
     */
    public $date_upd;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'group', 'primary' => 'id_group', 'multilang' => true, 'fields' => [
        'reduction' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPercentage', 'size' => 17, 'decimals' => 2, 'dbDefault' => '0.00'],
        'price_display_method' => ['type' => self::TYPE_INT, 'validate' => 'isPriceDisplayMethod', 'required' => true, 'dbType' => 'tinyint(4)', 'dbDefault' => '0'],
        'show_prices' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '1'],
        'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
    ], 'keys' => ['group_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = [];
    /**
     * GroupCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
        if ($this->id && !isset(static::$group_price_display_method[$this->id])) {
            static::$group_price_display_method[$this->id] = $this->price_display_method;
        }
    }
    /**
     * @param int $idLang
     * @param int|bool $idShop: false  --> Return all groups.
     *                          true   --> Return groups associated with
     *                                     current shop (from context).
     *                          number --> Return groups associated with the
     *                                     specific shop with this ID.
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_groups($id_lang, $id_shop = false)
    {
        $shop_criteria = '';
        if (is_int($id_shop)) {
            $shop_criteria = ' INNER JOIN `' . _DB_PREFIX_ . 'group_shop` gs ON (gs.`id_group` = g.`id_group` AND gs.`id_shop` = ' . $id_shop . ')';
        } elseif ($id_shop) {
            $shop_criteria = Shop::add_sql_association('group', 'g');
        }
        return Db::read_only()->get_array((new Db_Query())->select('DISTINCT g.`id_group`, g.`reduction`, g.`price_display_method`, gl.`name`')->from('group', 'g')->left_join('group_lang', 'gl', 'g.`id_group` = gl.`id_group` AND gl.`id_lang` = ' . (int) $id_lang . $shop_criteria)->order_by('g.`id_group` ASC'));
    }
    /**
     * @param int|null $idCustomer
     *
     * @return float
     *
     * @throws PrestaShopException
     */
    public static function get_reduction($id_customer = null)
    {
        if (!isset(static::$cache_reduction['customer'][(int) $id_customer])) {
            $id_group = $id_customer ? Customer::get_default_group_id((int) $id_customer) : (int) static::get_current()->id;
            static::$cache_reduction['customer'][(int) $id_customer] = static::get_reduction_by_id_group($id_group);
        }
        return static::$cache_reduction['customer'][(int) $id_customer];
    }
    /**
     * Return current group object
     * Use context
     *
     * @return Group Group object
     *
     * @throws PrestaShopException
     */
    public static function get_current()
    {
        static $groups = [];
        static $ps_unidentified_group = null;
        static $ps_customer_group = null;
        if ($ps_unidentified_group === null) {
            $ps_unidentified_group = Configuration::get('PS_UNIDENTIFIED_GROUP');
        }
        if ($ps_customer_group === null) {
            $ps_customer_group = Configuration::get('PS_CUSTOMER_GROUP');
        }
        $customer = Context::get_context()->customer;
        if (Validate::is_loaded_object($customer)) {
            $id_group = (int) $customer->id_default_group;
        } else {
            $id_group = (int) $ps_unidentified_group;
        }
        if (!isset($groups[$id_group])) {
            $groups[$id_group] = new Group($id_group);
        }
        if (!$groups[$id_group]->is_associated_to_shop(Context::get_context()->shop->id)) {
            $id_group = (int) $ps_customer_group;
            if (!isset($groups[$id_group])) {
                $groups[$id_group] = new Group($id_group);
            }
        }
        return $groups[$id_group];
    }
    /**
     * Get reduction for a group, which happens to be a percentage.
     *
     * @param int $idGroup
     *
     * @return float
     *
     * @throws PrestaShopException
     */
    public static function get_reduction_by_id_group($id_group)
    {
        if (!isset(static::$cache_reduction['group'][$id_group])) {
            static::$cache_reduction['group'][$id_group] = Db::read_only()->get_value((new Db_Query())->select('`reduction`')->from('group')->where('`id_group` = ' . (int) $id_group));
        }
        return static::$cache_reduction['group'][$id_group];
    }
    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_default_price_display_method()
    {
        return static::get_price_display_method((int) Configuration::get('PS_CUSTOMER_GROUP'));
    }
    /**
     * @param int $idGroup
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_price_display_method($id_group)
    {
        if (!isset(static::$group_price_display_method[$id_group])) {
            static::$group_price_display_method[$id_group] = (int) Db::read_only()->get_value((new Db_Query())->select('`price_display_method`')->from('group')->where('`id_group` = ' . (int) $id_group));
        }
        return (int) static::$group_price_display_method[$id_group];
    }
    /**
     * This method is allow to know if a feature is used or active
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_feature_active()
    {
        static $ps_group_feature_active = null;
        if ($ps_group_feature_active === null) {
            $ps_group_feature_active = (bool) Configuration::get('PS_GROUP_FEATURE_ACTIVE');
        }
        return (bool) $ps_group_feature_active;
    }
    /**
     * This method is allow to know if there are other groups than the default ones
     *
     * @param string $table
     * @param bool $hasActiveColumn
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_currently_used($table = null, $has_active_column = false)
    {
        return Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from('group')) > 3;
    }
    /**
     * Truncate all restrictions by module
     *
     * @param int $idModule
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function truncate_restrictions_by_module($id_module)
    {
        return Db::get_instance()->delete('module_group', '`id_module` = ' . (int) $id_module);
    }
    /**
     * Adding restrictions modules to the group with id $id_group
     *
     * @param int $idGroup
     * @param array $modules
     * @param array $shops
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function add_modules_restrictions($id_group, $modules, $shops = [1])
    {
        if (!is_array($modules) || !count($modules) || !is_array($shops) || !count($shops)) {
            return false;
        }
        // Delete all record for this group
        $conn = Db::get_instance();
        $conn->delete('module_group', '`id_group` = ' . (int) $id_group);
        $insert = [];
        foreach ($modules as $module) {
            foreach ($shops as $shop) {
                $insert[] = ['id_module' => (int) $module, 'id_shop' => (int) $shop, 'id_group' => (int) $id_group];
            }
        }
        return (bool) $conn->insert('module_group', $insert);
    }
    /**
     * Add restrictions for a new module.
     * We authorize every groups to the new module
     *
     * @param int $idModule
     * @param array $shops
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function add_restrictions_for_module($id_module, $shops = [1])
    {
        if (!is_array($shops) || !count($shops)) {
            return false;
        }
        $res = true;
        foreach ($shops as $shop) {
            $res = Db::get_instance()->execute('
			INSERT INTO `' . _DB_PREFIX_ . 'module_group` (`id_module`, `id_shop`, `id_group`)
			(SELECT ' . (int) $id_module . ', ' . (int) $shop . ', id_group FROM `' . _DB_PREFIX_ . 'group`)') && $res;
        }
        return $res;
    }
    /**
     * Light back office search for Group
     *
     * @param string $query Searched string
     *
     * @return array|false Corresponding groups
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function search_by_name($query)
    {
        return Db::read_only()->get_row((new Db_Query())->select('g.*, gl.*')->from('group', 'g')->left_join('group_lang', 'gl', 'g.`id_group` = gl.`id_group`')->where('`name` = \'' . p_sql($query) . '\''));
    }
    /**
     * @param bool $count
     * @param int $start
     * @param int $limit
     * @param bool $shopFiltering
     *
     * @return array|int
     *
     * @throws PrestaShopException
     */
    public function get_customers($count = false, $start = 0, $limit = 0, $shop_filtering = false)
    {
        $connection = Db::read_only();
        if ($count) {
            return (int) $connection->get_value((new Db_Query())->select('COUNT(1)')->from('customer_group', 'cg')->left_join('customer', 'c', 'cg.`id_customer` = c.`id_customer`')->where('cg.`id_group` = ' . (int) $this->id . ' ' . ($shop_filtering ? Shop::add_sql_restriction(Shop::SHARE_CUSTOMER) : ''))->where('c.`deleted` != 1'));
        }
        return $connection->get_array((new Db_Query())->select('cg.`id_customer`, c.*')->from('customer_group', 'cg')->left_join('customer', 'c', 'cg.`id_customer` = c.`id_customer`')->where('cg.`id_group` = ' . (int) $this->id)->where('c.`deleted` != 1' . ($shop_filtering ? Shop::add_sql_restriction(Shop::SHARE_CUSTOMER) : ''))->order_by('cg.`id_customer` ASC')->limit($limit > 0 ? (int) $limit : 0, $limit ? (int) $start : 0));
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        Configuration::update_global_value('PS_GROUP_FEATURE_ACTIVE', '1');
        if (parent::add($auto_date, $null_values)) {
            Category::set_new_group_for_home((int) $this->id);
            Carrier::assign_group_to_all_carriers((int) $this->id);
            return true;
        }
        return false;
    }
    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function update($autodate = true, $null_values = false)
    {
        if (!Configuration::get_global_value('PS_GROUP_FEATURE_ACTIVE') && $this->reduction > 0) {
            Configuration::update_global_value('PS_GROUP_FEATURE_ACTIVE', 1);
        }
        return parent::update($autodate);
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function delete()
    {
        if ($this->id == (int) Configuration::get('PS_CUSTOMER_GROUP')) {
            return false;
        }
        if (parent::delete()) {
            $conn = Db::get_instance();
            $conn->delete('cart_rule_group', '`id_group` = ' . (int) $this->id);
            $conn->delete('customer_group', '`id_group` = ' . (int) $this->id);
            $conn->delete('category_group', '`id_group` = ' . (int) $this->id);
            $conn->delete('group_reduction', '`id_group` = ' . (int) $this->id);
            $conn->delete('product_group_reduction_cache', '`id_group` = ' . (int) $this->id);
            static::truncate_modules_restrictions($this->id);
            // Add default group (id 3) to customers without groups
            $conn->execute('INSERT INTO `' . _DB_PREFIX_ . 'customer_group` (
				SELECT c.id_customer, ' . (int) Configuration::get('PS_CUSTOMER_GROUP') . ' FROM `' . _DB_PREFIX_ . 'customer` c
				LEFT JOIN `' . _DB_PREFIX_ . 'customer_group` cg
				ON cg.id_customer = c.id_customer
				WHERE cg.id_customer IS NULL)');
            // Set to the customer the default group
            // Select the minimal id from customer_group
            $conn->execute('UPDATE `' . _DB_PREFIX_ . 'customer` cg
				SET id_default_group =
					IFNULL((
						SELECT min(id_group) FROM `' . _DB_PREFIX_ . 'customer_group`
						WHERE id_customer = cg.id_customer),
						' . (int) Configuration::get('PS_CUSTOMER_GROUP') . ')
				WHERE `id_default_group` = ' . (int) $this->id);
            return $conn->delete('module_group', '`id_group` = ' . (int) $this->id);
        }
        return false;
    }
    /**
     * Truncate all modules restrictions for the group
     *
     * @param int $idGroup
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function truncate_modules_restrictions($id_group)
    {
        return Db::get_instance()->delete('module_group', '`id_group` = ' . (int) $id_group);
    }
}