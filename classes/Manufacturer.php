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
use Thirtybees\Core\Initialization_Callback;
/**
 * Class ManufacturerCore
 */
class Manufacturer_Core extends Object_Model implements Initialization_Callback
{
    /**
     * @var string[]
     */
    protected static $cache_name = [];
    /**
     * @var int|null Object ID
     */
    public $id;
    /**
     * @var int manufacturer ID
     */
    public $id_manufacturer;
    /**
     * @var string Name
     */
    public $name;
    /**
     * @var string|string[] A description
     */
    public $description;
    /**
     * @var string|string[] A short description
     */
    public $short_description;
    /**
     * @var int Address
     */
    public $id_address;
    /**
     * @var string Object creation date
     */
    public $date_add;
    /**
     * @var string Object last modification date
     */
    public $date_upd;
    /**
     * @var string Friendly URL
     */
    public $link_rewrite;
    /**
     * @var string|string[] Meta title
     */
    public $meta_title;
    /**
     * @var string|string[] Meta keywords
     */
    public $meta_keywords;
    /**
     * @var string|string[] Meta description
     */
    public $meta_description;
    /**
     * @var bool active
     */
    public $active;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'manufacturer', 'primary' => 'id_manufacturer', 'multilang' => true, 'fields' => [
        'name' => ['type' => self::TYPE_STRING, 'validate' => 'isCatalogName', 'required' => true, 'size' => 64],
        'date_add' => ['type' => self::TYPE_DATE, 'dbNullable' => false],
        'date_upd' => ['type' => self::TYPE_DATE, 'dbNullable' => false],
        'active' => ['type' => self::TYPE_BOOL, 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        /* Lang fields */
        'description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => Object_Model::SIZE_TEXT],
        'short_description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => Object_Model::SIZE_TEXT],
        'meta_title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
        'meta_keywords' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName'],
        'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
    ], 'keys' => ['manufacturer_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]], 'images' => [Image_Entity::ENTITY_TYPE_MANUFACTURERS => ['inputName' => 'logo', 'path' => _PS_MANU_IMG_DIR_]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['fields' => ['active' => [], 'link_rewrite' => ['getter' => 'getLink', 'setter' => false]], 'associations' => ['addresses' => ['resource' => 'address', 'setter' => false, 'fields' => ['id' => ['xlink_resource' => 'addresses']]]]];
    /**
     * ManufacturerCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);
        $this->link_rewrite = $this->get_link();
        $this->image_dir = _PS_MANU_IMG_DIR_;
    }
    /**
     * @return string
     *
     * @throws PrestaShopException
     */
    public function get_link()
    {
        return Tools::link_rewrite($this->name);
    }
    /**
     * Return manufacturers
     *
     * @param bool $getNbProducts [optional] return products numbers for each
     * @param int $idLang
     * @param bool $active
     * @param bool|int $p
     * @param bool|int $n
     * @param bool $allGroup
     *
     * @param bool $groupBy
     *
     * @return array Manufacturers
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_manufacturers($get_nb_products = false, $id_lang = 0, $active = true, $p = false, $n = false, $all_group = false, $group_by = true)
    {
        if (!$group_by) {
            Tools::display_parameter_as_deprecated('$groupBy');
        }
        if (!$id_lang) {
            $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        }
        if (!Group::is_feature_active()) {
            $all_group = true;
        }
        $conn = Db::read_only();
        $manufacturers = $conn->get_array((new Db_Query())->select('m.*, ml.`description`, ml.`short_description`')->from('manufacturer', 'm')->join(Shop::add_sql_association('manufacturer', 'm'))->inner_join('manufacturer_lang', 'ml', 'm.`id_manufacturer` = ml.`id_manufacturer`')->where('ml.`id_lang` = ' . (int) $id_lang)->where($active ? 'm.`active` = 1' : '')->group_by($group_by ? 'm.`id_manufacturer`' : '')->order_by('m.`name` ASC')->limit($p ? (int) $n : 0, $p ? ((int) $p - 1) * (int) $n : 0));
        if ($get_nb_products) {
            $sql_groups = '';
            if (!$all_group) {
                $groups = Front_Controller::get_current_customer_groups();
                $sql_groups = count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1';
            }
            $category_group_sql = (new Db_Query())->select('1')->from('category_group', 'cg')->left_join('category_product', 'cp', 'cp.`id_category` = cg.`id_category`')->where('p.`id_product` = cp.`id_product`')->where('cg.`id_group` ' . $sql_groups);
            $results = $conn->get_array((new Db_Query())->select('p.`id_manufacturer`, COUNT(DISTINCT p.`id_product`) AS `nb_products`')->from('product', 'p')->join(Shop::add_sql_association('product', 'p'))->left_join('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`')->where('p.`id_manufacturer` != 0')->where('product_shop.`visibility` NOT IN ("none")')->where($active ? 'product_shop.`active` = 1' : '')->where(Group::is_feature_active() && $all_group ? '' : 'EXISTS (' . $category_group_sql->build() . ')')->group_by('p.`id_manufacturer`'));
            $counts = [];
            if (!empty($results)) {
                foreach ($results as $result) {
                    $counts[(int) $result['id_manufacturer']] = (int) $result['nb_products'];
                }
            }
            if (count($counts)) {
                foreach ($manufacturers as $key => $manufacturer) {
                    if (array_key_exists((int) $manufacturer['id_manufacturer'], $counts)) {
                        $manufacturers[$key]['nb_products'] = $counts[(int) $manufacturer['id_manufacturer']];
                    } else {
                        $manufacturers[$key]['nb_products'] = 0;
                    }
                }
            }
        }
        $total_manufacturers = count($manufacturers);
        $rewrite_settings = (int) Configuration::get('PS_REWRITING_SETTINGS');
        for ($i = 0; $i < $total_manufacturers; $i++) {
            $manufacturers[$i]['link_rewrite'] = $rewrite_settings ? Tools::link_rewrite($manufacturers[$i]['name']) : 0;
        }
        return $manufacturers;
    }
    /**
     * @param int $idManufacturer
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function get_name_by_id($id_manufacturer)
    {
        if (!isset(static::$cache_name[$id_manufacturer])) {
            static::$cache_name[$id_manufacturer] = Db::read_only()->get_value((new Db_Query())->select('name')->from('manufacturer')->where('`id_manufacturer` = ' . (int) $id_manufacturer)->where('`active` = 1'));
        }
        return static::$cache_name[$id_manufacturer];
    }
    /**
     * @param string $name
     *
     * @return bool|int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_id_by_name($name)
    {
        $result = Db::read_only()->get_row((new Db_Query())->select('`id_manufacturer`')->from('manufacturer')->where('`name` = \'' . p_sql($name) . '\''));
        if (isset($result['id_manufacturer'])) {
            return (int) $result['id_manufacturer'];
        }
        return false;
    }
    /**
     * @param int $idManufacturer
     * @param int|null $idLang
     * @param int|null $p
     * @param int|null $n
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param bool $getTotal
     * @param bool $active
     * @param bool $activeCategory
     *
     * @return array|false|int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_products($id_manufacturer, $id_lang, $p, $n, $order_by = null, $order_way = null, $get_total = false, $active = true, $active_category = true, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $front = true;
        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }
        if ($p < 1) {
            $p = 1;
        }
        if (empty($order_by) || $order_by == 'position') {
            $order_by = 'name';
        }
        if (empty($order_way)) {
            $order_way = 'ASC';
        }
        if (!Validate::is_order_by($order_by) || !Validate::is_order_way($order_way)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Invalid ordering parameters: orderBy=[%s] orderWay=[%s]'), $order_by, $order_way));
        }
        $groups = Front_Controller::get_current_customer_groups();
        $sql_groups = count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1';
        /* Return only the number of products */
        $connection = Db::read_only();
        if ($get_total) {
            $category_group_sql = (new Db_Query())->select('1')->from('category_group', 'cg')->left_join('category_product', 'cp', 'cp.`id_category` = cg.`id_category`')->join($active_category ? 'INNER JOIN `' . _DB_PREFIX_ . 'category` ca ON (cp.`id_category` = ca.`id_category` AND ca.`active` = 1)' : '')->where('p.`id_product` = cp.`id_product`')->where('cg.`id_group` ' . $sql_groups);
            $result = $connection->get_array((new Db_Query())->select('p.`id_product`')->from('product', 'p')->join(Shop::add_sql_association('product', 'p'))->where('p.`id_manufacturer` = ' . (int) $id_manufacturer)->where($active ? 'product_shop.`active` = 1' : '')->where($front ? 'product_shop.`visibility` IN ("both", "catalog")' : '')->where('EXISTS (' . $category_group_sql->build() . ')'));
            return count($result);
        }
        if (strpos($order_by, '.') > 0) {
            $order_by = explode('.', $order_by);
            $order_by = p_sql($order_by[0]) . '.`' . p_sql($order_by[1]) . '`';
        }
        if ($order_by == 'price') {
            $alias_with_dot = 'product_shop.';
        } elseif ($order_by == 'name') {
            $alias_with_dot = 'pl.';
        } elseif ($order_by == 'manufacturer_name') {
            $order_by = 'name';
            $alias_with_dot = 'm.';
        } elseif ($order_by == 'quantity') {
            $alias_with_dot = 'stock.';
        } else {
            $alias_with_dot = 'p.';
        }
        $sql = (new Db_Query())->select('p.*, product_shop.*, stock.`out_of_stock`, IFNULL(stock.`quantity`, 0) AS `quantity`')->select(Combination::is_feature_active() ? 'product_attribute_shop.`minimal_quantity` AS `product_attribute_minimal_quantity`, IFNULL(product_attribute_shop.`id_product_attribute`,0) AS `id_product_attribute`' : '')->select('pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`')->select('pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`, image_shop.`id_image` AS `id_image`, il.`legend`, m.`name` AS `manufacturer_name`')->select('DATEDIFF(product_shop.`date_add`, DATE_SUB("' . date('Y-m-d') . ' 00:00:00", INTERVAL ' . (Validate::is_unsigned_int(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY)) > 0 AS `new`')->from('product', 'p')->join(Shop::add_sql_association('product', 'p'))->join(Combination::is_feature_active() ? 'LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.`id_shop` = ' . (int) $context->shop->id . ')' : '')->left_join('product_lang', 'pl', 'p.`id_product` = pl.`id_product`')->left_join('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id)->left_join('image_lang', 'il', 'image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $id_lang)->left_join('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`')->join(Product::sql_stock('p', 0))->where('pl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl'));
        if (Group::is_feature_active() || $active_category) {
            $sql->inner_join('category_product', 'cp', 'p.`id_product` = cp.`id_product`');
            if (Group::is_feature_active()) {
                $sql->inner_join('category_group', 'cg', 'cp.`id_category` = cg.`id_category`');
                $sql->where('cg.`id_group` ' . $sql_groups);
            }
            if ($active_category) {
                $sql->inner_join('category', 'ca', 'cp.`id_category` = ca.`id_category`');
                $sql->where('ca.`active` = 1');
            }
        }
        $sql->where('p.`id_manufacturer` = ' . (int) $id_manufacturer);
        $sql->where($active ? '`product_shop`.`active` = 1' : '');
        $sql->where($front ? '`product_shop`.`visibility` IN ("both", "catalog")' : '');
        $sql->group_by('p.`id_product`');
        $sql->order_by($alias_with_dot . '`' . bq_sql($order_by) . '` ' . p_sql($order_way));
        $sql->limit((int) $n, ((int) $p - 1) * (int) $n);
        $result = $connection->get_array($sql);
        if (!$result) {
            return false;
        }
        if ($order_by == 'price') {
            Tools::orderby_price($result, $order_way);
        }
        return Product::get_products_properties($id_lang, $result);
    }
    /**
     * Specify if a manufacturer already in base
     *
     * @param int $idManufacturer Manufacturer id
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function manufacturer_exists($id_manufacturer)
    {
        $row = Db::read_only()->get_row((new Db_Query())->select('`id_manufacturer`')->from('manufacturer', 'm')->where('m.`id_manufacturer` = ' . (int) $id_manufacturer));
        return isset($row['id_manufacturer']);
    }
    /**
     * Delete several objects from database
     *
     * return boolean Deletion result
     *
     * @param array $selection
     *
     * @return bool
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
            $this->id = (int) $id;
            $this->id_address = $this->get_manufacturer_address();
            $result = $this->delete() && $result;
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
        $address = new Address($this->id_address);
        if (Validate::is_loaded_object($address) && !$address->delete()) {
            return false;
        }
        if (Page_Cache::is_enabled()) {
            Page_Cache::invalidate_entity('manufacturer', $this->id);
        }
        if (parent::delete()) {
            Cart_Rule::clean_product_rule_integrity('manufacturers', $this->id);
            return $this->delete_image();
        }
        return false;
    }
    /**
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_products_lite($id_lang)
    {
        $context = Context::get_context();
        $front = true;
        if (!in_array($context->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }
        return Db::read_only()->get_array((new Db_Query())->select('p.`id_product`, pl.`name`')->from('product', 'p')->join(Shop::add_sql_association('product', 'p'))->left_join('product_lang', 'pl', 'p.`id_product` = pl.`id_product`')->where('pl.`id_lang` = ' . (int) $id_lang . $context->shop->add_sql_restriction_on_lang('pl'))->where('p.`id_manufacturer` = ' . (int) $this->id)->where($front ? 'product_shop.`visibility` IN ("both", "catalog")' : ''));
    }
    /**
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_addresses($id_lang)
    {
        return Db::read_only()->get_array((new Db_Query())->select('a.*, cl.`name` AS `country`, s.`name` AS `state`')->from('address', 'a')->left_join('country_lang', 'cl', 'cl.`id_country` = a.`id_country`')->left_join('state', 's', 's.`id_state` = a.`id_state`')->where('cl.`id_lang` = ' . (int) $id_lang)->where('`id_manufacturer` = ' . (int) $this->id)->where('a.`deleted` = 0'));
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_addresses()
    {
        return Db::read_only()->get_array((new Db_Query())->select('a.`id_address` AS `id`')->from('address', 'a')->join(Shop::add_sql_association('manufacturer', 'a'))->where('a.`id_manufacturer` = ' . (int) $this->id)->where('a.`deleted` = 0'));
    }
    /**
     * @param array $idAddresses
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_ws_addresses($id_addresses)
    {
        $ids = [];
        foreach ($id_addresses as $id) {
            $ids[] = (int) $id['id'];
        }
        $conn = Db::get_instance();
        $result1 = $conn->update('address', ['id_manufacturer' => 0], '`id_manufacturer` = ' . (int) $this->id . ' AND `deleted` = 0');
        $result2 = true;
        if (count($ids)) {
            $result2 = $conn->update('address', ['id_customer' => 0, 'id_supplier' => 0, 'id_manufacturer' => (int) $this->id], '`id_address` IN(' . implode(',', $ids) . ') AND `deleted` = 0');
        }
        return $result1 && $result2;
    }
    /**
     * @param bool|null $nullValues
     *
     * @return bool Indicates whether updating succeeded
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($null_values = null)
    {
        if ('TB_PAGE_CACHE_ENABLED') {
            Page_Cache::invalidate_entity('manufacturer', $this->id);
        }
        return parent::update($null_values);
    }
    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    protected function get_manufacturer_address()
    {
        if (!(int) $this->id) {
            return 0;
        }
        return (int) Db::read_only()->get_value((new Db_Query())->select('`id_address`')->from('address')->where('`id_manufacturer` = ' . (int) $this->id));
    }
    /**
     * Database initialization callback
     *
     * @throws PrestaShopException
     */
    public static function initialization_callback(Db $conn): void
    {
        Image_Entity::rebuild_image_entities(static::class, self::$definition['images']);
    }
}