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
 * Class SupplierCore
 */
class Supplier_Core extends Object_Model implements Initialization_Callback
{
    /**
     * Return name from id
     *
     * @param int $id_supplier Supplier ID
     *
     * @return string name
     */
    protected static $cache_name = [];
    /** @var int supplier ID */
    public $id_supplier;
    /** @var string Name */
    public $name;
    /** @var string|string[] A short description for the discount */
    public $description;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var string Friendly URL */
    public $link_rewrite;
    /** @var string|string[] Meta title */
    public $meta_title;
    /** @var string|string[] Meta keywords */
    public $meta_keywords;
    /** @var string|string[] Meta description */
    public $meta_description;
    /** @var bool active */
    public $active;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'supplier', 'primary' => 'id_supplier', 'multilang' => true, 'fields' => [
        'name' => ['type' => self::TYPE_STRING, 'validate' => 'isCatalogName', 'required' => true, 'size' => 64],
        'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        'active' => ['type' => self::TYPE_BOOL, 'dbType' => 'tinyint(1)', 'dbNullable' => false, 'dbDefault' => '0'],
        /* Lang fields */
        'description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => Object_Model::SIZE_TEXT],
        'meta_title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
        'meta_keywords' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
    ], 'keys' => ['supplier_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]], 'images' => [Image_Entity::ENTITY_TYPE_SUPPLIERS => ['inputName' => 'logo', 'path' => _PS_SUPP_IMG_DIR_]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['fields' => ['link_rewrite' => ['sqlId' => 'link_rewrite']]];
    /**
     * SupplierCore constructor.
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
        $this->image_dir = _PS_SUPP_IMG_DIR_;
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
     * Return suppliers
     *
     * @param bool $getNbProducts
     * @param int $idLang
     * @param bool $active
     * @param bool $p
     * @param bool $n
     * @param bool $allGroups
     *
     * @return array Suppliers
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_suppliers($get_nb_products = false, $id_lang = 0, $active = true, $p = false, $n = false, $all_groups = false)
    {
        if (!$id_lang) {
            $id_lang = Configuration::get('PS_LANG_DEFAULT');
        }
        if (!Group::is_feature_active()) {
            $all_groups = true;
        }
        $query = new Db_Query();
        $query->select('s.*, sl.`description`');
        $query->from('supplier', 's');
        $query->left_join('supplier_lang', 'sl', 's.`id_supplier` = sl.`id_supplier` AND sl.`id_lang` = ' . (int) $id_lang);
        $query->join(Shop::add_sql_association('supplier', 's'));
        if ($active) {
            $query->where('s.`active` = 1');
        }
        $query->order_by(' s.`name` ASC');
        $query->limit($n, ($p - 1) * $n);
        $query->group_by('s.id_supplier');
        $conn = Db::read_only();
        $suppliers = $conn->get_array($query);
        if ($get_nb_products) {
            $sql_groups = '';
            if (!$all_groups) {
                $groups = Front_Controller::get_current_customer_groups();
                $sql_groups = count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1';
            }
            $results = $conn->get_array((new Db_Query())->select('ps.`id_supplier`, COUNT(DISTINCT ps.`id_product`) AS nb_products')->from('product_supplier', 'ps')->inner_join('product', 'p', 'ps.`id_product` = p.`id_product`')->join(Shop::add_sql_association('product', 'p'))->left_join('supplier', 'm', 'm.`id_supplier` = p.`id_supplier`')->where('ps.`id_product_attribute` = 0')->where($active ? 'product_shop.`active` = 1' : '')->where('product_shop.`visibility` NOT IN ("none")')->where($all_groups ? 'ps.`id_product` IN (SELECT cp.`id_product` FROM `' . _DB_PREFIX_ . 'category_group` cg LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON (cp.`id_category` = cg.`id_category`) WHERE cg.`id_group` ' . $sql_groups . ')' : '')->group_by('ps.`id_supplier`'));
            $counts = [];
            foreach ($results as $result) {
                $counts[(int) $result['id_supplier']] = (int) $result['nb_products'];
            }
            if (count($counts)) {
                foreach ($suppliers as $key => $supplier) {
                    if (isset($counts[(int) $supplier['id_supplier']])) {
                        $suppliers[$key]['nb_products'] = $counts[(int) $supplier['id_supplier']];
                    } else {
                        $suppliers[$key]['nb_products'] = 0;
                    }
                }
            }
        }
        $nb_suppliers = count($suppliers);
        $rewrite_settings = (int) Configuration::get('PS_REWRITING_SETTINGS');
        for ($i = 0; $i < $nb_suppliers; $i++) {
            $suppliers[$i]['link_rewrite'] = $rewrite_settings ? Tools::link_rewrite($suppliers[$i]['name']) : 0;
        }
        return $suppliers;
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
        if (Page_Cache::is_enabled()) {
            Page_Cache::invalidate_entity('supplier', $this->id);
        }
        return parent::update($null_values);
    }
    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        $supplier_id = (int) $this->id;
        if (Page_Cache::is_enabled()) {
            Page_Cache::invalidate_entity('supplier', $supplier_id);
        }
        $res = parent::delete();
        if ($res) {
            // delete product supplier references
            $res = Db::get_instance()->delete('product_supplier', 'id_supplier=' . $supplier_id);
            // mark supplier address as deleted
            $id_address = Address::get_address_id_by_supplier_id($supplier_id);
            $address = new Address($id_address);
            if (Validate::is_loaded_object($address)) {
                $address->deleted = 1;
                $address->update();
            }
        }
        return $res;
    }
    /**
     * @param int $idSupplier
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function get_name_by_id($id_supplier)
    {
        if (!isset(static::$cache_name[$id_supplier])) {
            static::$cache_name[$id_supplier] = Db::read_only()->get_value((new Db_Query())->select('`name`')->from('supplier')->where('`id_supplier` = ' . (int) $id_supplier));
        }
        return static::$cache_name[$id_supplier];
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
        $result = Db::read_only()->get_row((new Db_Query())->select('`id_supplier`')->from('supplier')->where('`name` = \'' . p_sql($name) . '\''));
        if (isset($result['id_supplier'])) {
            return (int) $result['id_supplier'];
        }
        return false;
    }
    /**
     * @param int $idSupplier
     * @param int|null $idLang
     * @param int|null $p
     * @param int|null $n
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param bool $getTotal
     * @param bool $active
     * @param bool $activeCategory
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_products($id_supplier, $id_lang, $p, $n, $order_by = null, $order_way = null, $get_total = false, $active = true, $active_category = true)
    {
        $context = Context::get_context();
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
            throw new Presta_Shop_Exception(Tools::display_error('Invalid orderBy parameters'));
        }
        $sql_groups = '';
        if (Group::is_feature_active()) {
            $groups = Front_Controller::get_current_customer_groups();
            $sql_groups = 'cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1');
        }
        /* Return only the number of products */
        $conn = Db::read_only();
        if ($get_total) {
            $sql = new Db_Query();
            $sql->select('cp.`id_product`');
            $sql->from('category_product', 'cp');
            if (Group::is_feature_active()) {
                $sql->left_join('category_group', 'cg', 'cp.`id_category` = cg.`id_category`');
            }
            if ($active_category) {
                $sql->inner_join('category', 'ca', 'cp.`id_category` = ca.`id_category` AND ca.`active` = 1');
            }
            $sql->where($sql_groups);
            return (int) $conn->get_value((new Db_Query())->select('COUNT(DISTINCT ps.`id_product`)')->from('product_supplier', 'ps')->inner_join('product', 'p', 'ps.`id_product` = p.`id_product`')->join(Shop::add_sql_association('product', 'p'))->where('ps.`id_supplier` = ' . (int) $id_supplier)->where('ps.`id_product_attribute` = 0')->where($active ? 'product_shop.`active` = 1' : '')->where($front ? 'product_shop.`visibility` IN ("both", "catalog")' : '')->where('p.`id_product` IN (' . $sql->build() . ')'));
        }
        $nb_days_new_product = Validate::is_unsigned_int(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20;
        if (strpos('.', $order_by) > 0) {
            $order_by = explode('.', $order_by);
            $order_by = p_sql($order_by[0]) . '.`' . p_sql($order_by[1]) . '`';
        }
        $alias = '';
        if (in_array($order_by, ['price', 'date_add', 'date_upd'])) {
            $alias = 'product_shop.';
        } elseif ($order_by == 'id_product') {
            $alias = 'p.';
        } elseif ($order_by == 'manufacturer_name') {
            $order_by = 'name';
            $alias = 'm.';
        }
        $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock,
					IFNULL(stock.quantity, 0) as quantity,
					pl.`description`,
					pl.`description_short`,
					pl.`link_rewrite`,
					pl.`meta_description`,
					pl.`meta_keywords`,
					pl.`meta_title`,
					pl.`name`,
					image_shop.`id_image` id_image,
					il.`legend`,
					s.`name` AS supplier_name,
					DATEDIFF(p.`date_add`, DATE_SUB("' . date('Y-m-d') . ' 00:00:00", INTERVAL ' . $nb_days_new_product . ' DAY)) > 0 AS new,
					m.`name` AS manufacturer_name' . (Combination::is_feature_active() ? ', product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute' : '') . '
				 FROM `' . _DB_PREFIX_ . 'product` p
				' . Shop::add_sql_association('product', 'p') . '
				JOIN `' . _DB_PREFIX_ . 'product_supplier` ps ON (ps.id_product = p.id_product
					AND ps.id_product_attribute = 0) ' . (Combination::is_feature_active() ? 'LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop
				ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) $context->shop->id . ')' : '') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl') . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image`
					AND il.`id_lang` = ' . (int) $id_lang . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'supplier` s ON s.`id_supplier` = p.`id_supplier`
				LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON m.`id_manufacturer` = p.`id_manufacturer`
				' . Product::sql_stock('p', 0);
        if (Group::is_feature_active() || $active_category) {
            $sql .= 'JOIN `' . _DB_PREFIX_ . 'category_product` cp ON (p.id_product = cp.id_product)';
            if (Group::is_feature_active()) {
                $sql .= 'JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cp.`id_category` = cg.`id_category` AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1') . ')';
            }
            if ($active_category) {
                $sql .= 'JOIN `' . _DB_PREFIX_ . 'category` ca ON cp.`id_category` = ca.`id_category` AND ca.`active` = 1';
            }
        }
        $sql .= '
				WHERE ps.`id_supplier` = ' . (int) $id_supplier . '
					' . ($active ? ' AND product_shop.`active` = 1' : '') . '
					' . ($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '') . '
				GROUP BY ps.id_product
				ORDER BY ' . $alias . p_sql($order_by) . ' ' . p_sql($order_way) . '
				LIMIT ' . ((int) $p - 1) * (int) $n . ',' . (int) $n;
        $result = $conn->get_array($sql);
        if (!$result) {
            return false;
        }
        if ($order_by == 'price') {
            Tools::orderby_price($result, $order_way);
        }
        return Product::get_products_properties($id_lang, $result);
    }
    /**
     * @param int $idSupplier
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function supplier_exists($id_supplier)
    {
        $res = Db::read_only()->get_value((new Db_Query())->select('id_supplier')->from('supplier')->where('id_supplier = ' . (int) $id_supplier));
        return $res > 0;
    }
    /**
     * Gets product informations
     *
     * @param int $idSupplier
     * @param int $idProduct
     * @param int $idProductAttribute
     *
     * @return false|array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_product_informations_by_supplier($id_supplier, $id_product, $id_product_attribute = 0)
    {
        $res = Db::read_only()->get_array((new Db_Query())->select('product_supplier_reference, product_supplier_price_te, id_currency')->from('product_supplier')->where('id_supplier = ' . (int) $id_supplier)->where('id_product = ' . (int) $id_product)->where('id_product_attribute = ' . (int) $id_product_attribute));
        if (count($res)) {
            return $res[0];
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
        return Db::read_only()->get_array((new Db_Query())->select('p.`id_product`, pl.`name`')->from('product', 'p')->join(Shop::add_sql_association('product', 'p'))->left_join('product_lang', 'pl', 'p.`id_product` = pl.`id_product` AND pl.`id_lang` = ' . (int) $id_lang)->inner_join('product_supplier', 'ps', 'p.`id_product` = ps.`id_product`')->where('ps.`id_supplier` = ' . (int) $this->id)->where($front ? 'product_shop.`visibility` IN ("both", "catalog")' : '')->group_by('p.`id_product`'));
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