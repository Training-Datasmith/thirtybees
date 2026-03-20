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
use Thirtybees\Core\Dependency_Injection\Service_Locator;
use Thirtybees\Core\Stock\Synchronization\Dynamic_Packs_Synchronization_Task;
/**
 * Represents quantities available
 * It is either synchronized with Stock or manualy set by the seller
 */
class Stock_Available_Core extends Object_Model
{
    public const OUT_OF_STOCK_DENY = 0;
    public const OUT_OF_STOCK_ALLOW = 1;
    public const OUT_OF_STOCK_SYSTEM_DEFAULT = 2;
    /** @var int identifier of the current product */
    public $id_product;
    /** @var int identifier of product attribute if necessary */
    public $id_product_attribute;
    /** @var int the shop associated to the current product and corresponding quantity */
    public $id_shop;
    /** @var int the group shop associated to the current product and corresponding quantity */
    public $id_shop_group;
    /** @var int the quantity available for sale */
    public $quantity = 0;
    /** @var bool determine if the available stock value depends on physical stock */
    public $depends_on_stock = false;
    /** @var int determine if a product is out of stock - it was previously in Product class */
    public $out_of_stock = self::OUT_OF_STOCK_DENY;
    protected int $original_quantity;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'stock_available', 'primary' => 'id_stock_available', 'fields' => ['id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false], 'id_shop_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false], 'quantity' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true, 'signed' => 1, 'size' => 10, 'dbDefault' => '0'], 'depends_on_stock' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbDefault' => '0'], 'out_of_stock' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true, 'size' => 1, 'dbDefault' => '0']], 'keys' => ['stock_available' => ['product_sqlstock' => ['type' => Object_Model::UNIQUE_KEY, 'columns' => ['id_product', 'id_product_attribute', 'id_shop', 'id_shop_group']], 'id_product' => ['type' => Object_Model::KEY, 'columns' => ['id_product']], 'id_product_attribute' => ['type' => Object_Model::KEY, 'columns' => ['id_product_attribute']], 'id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']], 'id_shop_group' => ['type' => Object_Model::KEY, 'columns' => ['id_shop_group']]]]];
    /**
     * @var array Webservice Parameters
     */
    protected $webservice_parameters = ['fields' => ['id_product' => ['xlink_resource' => 'products'], 'id_product_attribute' => ['xlink_resource' => 'combinations'], 'id_shop' => ['xlink_resource' => 'shops'], 'id_shop_group' => ['xlink_resource' => 'shop_groups']], 'hidden_fields' => [], 'objectMethods' => ['add' => 'addWs', 'update' => 'updateWs']];
    /**
     * Constructor
     *
     * @param int $id
     * @param int $idLang
     * @param int $idShop
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
        $this->original_quantity = (int) $this->quantity;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_ws()
    {
        if ($this->depends_on_stock) {
            Webservice_Request::get_instance()->set_error(500, 'You cannot update the available stock when it depends on stock.', 133);
            return false;
        }
        return $this->update();
    }
    /**
     * @param int $idProduct
     * @param int|null $idProductAttribute
     * @param int|null $idShop
     *
     * @return bool|int
     *
     * @throws PrestaShopException
     */
    public static function get_stock_available_id_by_product_id($id_product, $id_product_attribute = null, $id_shop = null)
    {
        if (!Validate::is_unsigned_id($id_product)) {
            return false;
        }
        $id_product = (int) $id_product;
        $cache_key = "StockAvailable::getStockAvailableIdByProductId_{$id_product}-";
        $cache_key .= (is_null($id_product_attribute) ? 'NULL' : (int) $id_product_attribute) . '-';
        $cache_key .= is_null($id_shop) ? 'NULL' : (int) $id_shop;
        if (!Cache::is_stored($cache_key)) {
            $query = new Db_Query();
            $query->select('id_stock_available');
            $query->from('stock_available');
            $query->where("id_product = {$id_product}");
            if ($id_product_attribute !== null) {
                $query->where('id_product_attribute = ' . (int) $id_product_attribute);
            }
            $query = static::add_sql_shop_restriction($query, $id_shop);
            $conn = Db::read_only();
            $id = (int) $conn->get_value($query);
            if ($id) {
                Cache::store($cache_key, $id);
                return $id;
            }
        }
        return (int) Cache::retrieve($cache_key);
    }
    /**
     * For a given id_product, synchronizes StockAvailable::quantity with Stock::usable_quantity
     *
     * @param int $idProduct
     * @param int|null $orderIdShop
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function synchronize($id_product, $order_id_shop = null)
    {
        if (!Validate::is_unsigned_id($id_product)) {
            return false;
        }
        //if product is pack sync recursivly product in pack
        if (Pack::is_pack($id_product)) {
            if (Validate::is_loaded_object($product = new Product((int) $id_product))) {
                if ($product->should_adjust_pack_items_quantities()) {
                    $products_pack = Pack::get_items($id_product, (int) Configuration::get('PS_LANG_DEFAULT'));
                    foreach ($products_pack as $product_pack) {
                        static::synchronize($product_pack->id, $order_id_shop);
                    }
                }
            } else {
                return false;
            }
        }
        // gets warehouse ids grouped by shops
        $ids_warehouse = Warehouse::get_warehouses_grouped_by_shops();
        $order_warehouses = [];
        if ($order_id_shop !== null) {
            $wh = Warehouse::get_warehouses(false, (int) $order_id_shop);
            foreach ($wh as $warehouse) {
                $order_warehouses[] = $warehouse['id_warehouse'];
            }
        }
        // gets all product attributes ids
        $ids_product_attribute = [];
        foreach (Product::get_product_attributes_ids($id_product) as $id_product_attribute) {
            $ids_product_attribute[] = $id_product_attribute['id_product_attribute'];
        }
        // Allow to order the product when out of stock?
        $out_of_stock = static::out_of_stock($id_product);
        $manager = Stock_Manager_Factory::get_manager();
        // loops on $ids_warehouse to synchronize quantities
        $write_conn = Db::get_instance();
        foreach ($ids_warehouse as $id_shop => $warehouses) {
            // first, checks if the product depends on stock for the given shop $id_shop
            if (static::depends_on_stock($id_product, $id_shop)) {
                // init quantity
                $product_quantity = 0;
                // if it's a simple product
                if (empty($ids_product_attribute)) {
                    $allowed_warehouse_for_product = Warehouse::get_product_warehouse_list((int) $id_product, 0, (int) $id_shop);
                    $allowed_warehouse_for_product_clean = [];
                    foreach ($allowed_warehouse_for_product as $warehouse) {
                        $allowed_warehouse_for_product_clean[] = (int) $warehouse['id_warehouse'];
                    }
                    $allowed_warehouse_for_product_clean = array_intersect($allowed_warehouse_for_product_clean, $warehouses);
                    if ($order_id_shop != null && !count(array_intersect($allowed_warehouse_for_product_clean, $order_warehouses))) {
                        continue;
                    }
                    $product_quantity = $manager->get_product_real_quantities($id_product, null, $allowed_warehouse_for_product_clean, true);
                    Hook::trigger_event('actionUpdateQuantity', ['id_product' => $id_product, 'id_product_attribute' => 0, 'quantity' => $product_quantity, 'id_shop' => $id_shop]);
                } else {
                    foreach ($ids_product_attribute as $id_product_attribute) {
                        $allowed_warehouse_for_combination = Warehouse::get_product_warehouse_list((int) $id_product, (int) $id_product_attribute, (int) $id_shop);
                        $allowed_warehouse_for_combination_clean = [];
                        foreach ($allowed_warehouse_for_combination as $warehouse) {
                            $allowed_warehouse_for_combination_clean[] = (int) $warehouse['id_warehouse'];
                        }
                        $allowed_warehouse_for_combination_clean = array_intersect($allowed_warehouse_for_combination_clean, $warehouses);
                        if ($order_id_shop != null && !count(array_intersect($allowed_warehouse_for_combination_clean, $order_warehouses))) {
                            continue;
                        }
                        $quantity = $manager->get_product_real_quantities($id_product, $id_product_attribute, $allowed_warehouse_for_combination_clean, true);
                        $query = new Db_Query();
                        $query->select('COUNT(*)');
                        $query->from('stock_available');
                        $query->where('id_product = ' . (int) $id_product . ' AND id_product_attribute = ' . (int) $id_product_attribute . static::add_sql_shop_restriction(null, $id_shop));
                        if ((int) Db::read_only()->get_value($query)) {
                            $query = ['table' => 'stock_available', 'data' => ['quantity' => $quantity], 'where' => 'id_product = ' . (int) $id_product . ' AND id_product_attribute = ' . (int) $id_product_attribute . static::add_sql_shop_restriction(null, $id_shop)];
                            $write_conn->update($query['table'], $query['data'], $query['where']);
                        } else {
                            $query = ['table' => 'stock_available', 'data' => ['quantity' => $quantity, 'depends_on_stock' => 1, 'out_of_stock' => $out_of_stock, 'id_product' => (int) $id_product, 'id_product_attribute' => (int) $id_product_attribute]];
                            static::add_sql_shop_params($query['data'], $id_shop);
                            $write_conn->insert($query['table'], $query['data']);
                        }
                        $product_quantity += $quantity;
                        Hook::trigger_event('actionUpdateQuantity', ['id_product' => $id_product, 'id_product_attribute' => $id_product_attribute, 'quantity' => $quantity, 'id_shop' => $id_shop]);
                    }
                }
                // updates
                // if $id_product has attributes, it also updates the sum for all attributes
                if ($order_id_shop != null && array_intersect($warehouses, $order_warehouses) || $order_id_shop == null) {
                    $query = ['table' => 'stock_available', 'data' => ['quantity' => $product_quantity], 'where' => 'id_product = ' . (int) $id_product . ' AND id_product_attribute = 0' . static::add_sql_shop_restriction(null, $id_shop)];
                    $write_conn->update($query['table'], $query['data'], $query['where']);
                }
            }
        }
        // In case there are no warehouses, removes product from StockAvailable
        if (count($ids_warehouse) == 0 && static::depends_on_stock((int) $id_product)) {
            $write_conn->update('stock_available', ['quantity' => 0], 'id_product = ' . (int) $id_product);
        }
        static::clean_quantity_cache($id_product);
        return true;
    }
    /**
     * For a given id_product, sets if stock available depends on stock
     *
     * @param int $idProduct
     * @param int|bool $dependsOnStock true by default
     * @param int|null $idShop gets context by default
     * @param int $idProductAttribute
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function set_product_depends_on_stock($id_product, $depends_on_stock = true, $id_shop = null, $id_product_attribute = 0)
    {
        if (!Validate::is_unsigned_id($id_product)) {
            return false;
        }
        $existing_id = static::get_stock_available_id_by_product_id((int) $id_product, (int) $id_product_attribute, $id_shop);
        $conn = Db::get_instance();
        if ($existing_id > 0) {
            $conn->update('stock_available', ['depends_on_stock' => (int) $depends_on_stock], 'id_stock_available = ' . (int) $existing_id);
        } else {
            $params = ['depends_on_stock' => (int) $depends_on_stock, 'id_product' => (int) $id_product, 'id_product_attribute' => (int) $id_product_attribute];
            static::add_sql_shop_params($params, $id_shop);
            $conn->insert('stock_available', $params);
        }
        // depends on stock.. hence synchronizes
        if ($depends_on_stock) {
            static::synchronize($id_product);
        }
        return true;
    }
    /**
     * For a given id_product, sets if product is available out of stocks
     *
     * @param int $idProduct
     * @param int $outOfStock Optional false by default
     * @param int $idShop Optional gets context by default
     * @param int $idProductAttribute
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function set_product_out_of_stock($id_product, $out_of_stock = self::OUT_OF_STOCK_DENY, $id_shop = null, $id_product_attribute = 0)
    {
        if (!Validate::is_unsigned_id($id_product)) {
            return false;
        }
        $out_of_stock = (int) $out_of_stock;
        if (!static::is_valid_out_of_stock_value($out_of_stock)) {
            $out_of_stock = static::OUT_OF_STOCK_DENY;
        }
        $existing_id = (int) static::get_stock_available_id_by_product_id((int) $id_product, (int) $id_product_attribute, $id_shop);
        $conn = Db::get_instance();
        if ($existing_id > 0) {
            $conn->update('stock_available', ['out_of_stock' => (int) $out_of_stock], 'id_product = ' . (int) $id_product . ($id_product_attribute ? ' AND id_product_attribute = ' . (int) $id_product_attribute : '') . static::add_sql_shop_restriction(null, $id_shop));
        } else {
            $params = ['out_of_stock' => (int) $out_of_stock, 'id_product' => (int) $id_product, 'id_product_attribute' => (int) $id_product_attribute];
            static::add_sql_shop_params($params, $id_shop);
            $conn->insert('stock_available', $params, false, true, Db::ON_DUPLICATE_KEY);
        }
        return true;
    }
    /**
     * For a given id_product and id_product_attribute, gets its stock available
     *
     * @param int $idProduct
     * @param int $idProductAttribute Optional
     * @param int $idShop Optional : gets context by default
     *
     * @return int Quantity
     *
     * @throws PrestaShopException
     */
    public static function get_quantity_available_by_product($id_product = null, $id_product_attribute = null, $id_shop = null)
    {
        $id_product = (int) $id_product;
        $id_product_attribute = (int) $id_product_attribute;
        $key = 'StockAvailable::getQuantityAvailableByProduct_' . $id_product . '-' . $id_product_attribute . '-' . (int) $id_shop;
        if (!Cache::is_stored($key)) {
            $query = new Db_Query();
            $query->select('SUM(quantity)');
            $query->from('stock_available');
            if ($id_product) {
                $query->where("id_product = {$id_product}");
            }
            $query->where("id_product_attribute = {$id_product_attribute}");
            $query = static::add_sql_shop_restriction($query, $id_shop);
            $result = (int) Db::read_only()->get_value($query);
            Cache::store($key, $result);
            return $result;
        }
        return Cache::retrieve($key);
    }
    /**
     * Returns information about combination quantities
     *
     *
     * @return array<int, int> Map from combinationId -> quantity
     *
     * @throws PrestaShopException
     */
    public static function get_combination_quantities(int $id_product, ?int $id_shop = null): array
    {
        $key = 'StockAvailable::getCombinationQuantities' . $id_product . '-' . (int) $id_shop;
        if (!Cache::is_stored($key)) {
            $result = [];
            $query = (new Db_Query())->select('id_product_attribute, quantity')->from('stock_available')->where('`id_product` = ' . $id_product)->where('`id_product_attribute` != 0')->order_by('id_product_attribute');
            static::add_sql_shop_restriction($query, $id_shop);
            foreach (Db::read_only()->get_array($query) as $row) {
                $combination_id = (int) $row['id_product_attribute'];
                $quantity = (int) $row['quantity'];
                $result[$combination_id] = $quantity;
            }
            Cache::store($key, $result);
            return $result;
        }
        return Cache::retrieve($key);
    }
    /**
     * Upgrades total_quantity_available after having saved
     *
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
        if (!parent::add($auto_date, $null_values)) {
            return false;
        }
        $result = $this->post_save();
        $this->trigger_update_hook();
        return $result;
    }
    /**
     * Upgrades total_quantity_available after having update
     *
     *
     *
     * @param bool $nullValues
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        if (!parent::update($null_values)) {
            return false;
        }
        $result = $this->post_save();
        $this->trigger_update_hook();
        return $result;
    }
    /**
     * Upgrades total_quantity_available after having saved
     *
     * @throws PrestaShopException
     */
    public function post_save()
    {
        if ($this->id_product_attribute == 0) {
            return true;
        }
        $id_shop = Shop::get_context() != Shop::CONTEXT_GROUP && $this->id_shop ? $this->id_shop : null;
        if (!Configuration::get('PS_DISP_UNAVAILABLE_ATTR')) {
            $combination = new Combination((int) $this->id_product_attribute);
            if ($colors = $combination->get_colors_attributes()) {
                $product = new Product((int) $this->id_product);
                foreach ($colors as $color) {
                    if ($product->is_color_unavailable((int) $color['id_attribute'], (int) $this->id_shop)) {
                        Tools::clear_color_list_cache($product->id);
                        break;
                    }
                }
            }
        }
        $total_quantity = (int) Db::read_only()->get_value((new Db_Query())->select('SUM(`quantity`) AS `quantity`')->from(bq_sql(static::$definition['table']))->where('`id_product` = ' . (int) $this->id_product)->where('`id_product_attribute` <> 0 ' . static::add_sql_shop_restriction(null, $id_shop)));
        static::set_quantity($this->id_product, 0, $total_quantity, $id_shop);
        return true;
    }
    /**
     * For a given id_product and id_product_attribute updates the quantity available
     * If $avoid_parent_pack_update is true, then packs containing the given product won't be updated
     *
     * @param int $idProduct
     * @param int $idProductAttribute Optional
     * @param int $deltaQuantity The delta quantity to update
     * @param int $idShop Optional
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function update_quantity($id_product, $id_product_attribute, $delta_quantity, $id_shop = null)
    {
        if (!Validate::is_unsigned_id($id_product)) {
            return false;
        }
        $product = new Product((int) $id_product);
        if (!Validate::is_loaded_object($product)) {
            return false;
        }
        /** @var Core_Business_Stock_StockManager $stockManager */
        $stock_manager = Adapter_service_Locator::get('Core_Business_Stock_StockManager');
        $stock_manager->update_quantity($product, $id_product_attribute, $delta_quantity);
        return true;
    }
    /**
     * For a given id_product and id_product_attribute sets the quantity available
     *
     * @param int $idProduct
     * @param int $idProductAttribute Optional
     * @param int $quantity
     * @param int $idShop Optional
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function set_quantity($id_product, $id_product_attribute, $quantity, $id_shop = null)
    {
        if (!Validate::is_unsigned_id($id_product)) {
            return false;
        }
        // Try to set available quantity if product does not depend on physical stock
        if (static::depends_on_stock($id_product)) {
            return false;
        }
        $quantity = (int) $quantity;
        $context = Context::get_context();
        // if there is no $id_shop, gets the context one
        if ($id_shop === null && Shop::get_context() != Shop::CONTEXT_GROUP) {
            $id_shop = (int) $context->shop->id;
        }
        $id_stock_available = (int) static::get_stock_available_id_by_product_id($id_product, $id_product_attribute, $id_shop);
        if ($id_stock_available) {
            $stock_available = new Stock_Available($id_stock_available);
            if ((int) $stock_available->quantity !== $quantity) {
                $stock_available->quantity = $quantity;
                $stock_available->update();
                // adjust packs this item might be in
                $packs = Pack::get_packs_containing_item($id_product, $id_product_attribute, Configuration::get('PS_LANG_DEFAULT'));
                $dynamic_packs = [];
                foreach ($packs as $pack) {
                    if ($pack->pack_dynamic) {
                        $dynamic_packs[] = $pack->id;
                    }
                }
                if ($dynamic_packs) {
                    Stock_Available::synchronize_dynamic_packs($dynamic_packs);
                }
            }
        } else {
            $out_of_stock = static::out_of_stock($id_product, $id_shop);
            $stock_available = new Stock_Available();
            $stock_available->out_of_stock = (int) $out_of_stock;
            $stock_available->id_product = (int) $id_product;
            $stock_available->id_product_attribute = (int) $id_product_attribute;
            $stock_available->quantity = $quantity;
            if ($id_shop === null) {
                $shop_group = Shop::get_context_shop_group();
            } else {
                $shop_group = new Shop_Group((int) Shop::get_group_from_shop((int) $id_shop));
            }
            // if quantities are shared between shops of the group
            if ($shop_group->share_stock) {
                $stock_available->id_shop = 0;
                $stock_available->id_shop_group = (int) $shop_group->id;
            } else {
                $stock_available->id_shop = (int) $id_shop;
                $stock_available->id_shop_group = 0;
            }
            $stock_available->add();
        }
        return true;
    }
    /**
     * Removes a given product from the stock available
     *
     * @param int $idProduct
     * @param int|null $idProductAttribute Optional
     * @param Shop|null $shop Shop id or shop object Optional
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function remove_product_from_stock_available($id_product, $id_product_attribute = null, $shop = null)
    {
        if (!Validate::is_unsigned_id($id_product)) {
            return false;
        }
        if (Shop::get_context() == Shop::CONTEXT_SHOP) {
            if (Shop::get_context_shop_group()->share_stock == 1) {
                $pa_sql = '';
                if ($id_product_attribute !== null) {
                    $pa_sql = '_attribute';
                    $id_product_attribute_sql = $id_product_attribute;
                } else {
                    $id_product_attribute_sql = $id_product;
                }
                if ((int) Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from('product' . bq_sql($pa_sql) . '_shop')->where('`id_product' . bq_sql($pa_sql) . '` = ' . (int) $id_product_attribute_sql)->where('`id_shop` IN (' . implode(',', array_map(intval(...), Shop::get_context_list_shop_id(Shop::SHARE_STOCK))) . ')'))) {
                    return true;
                }
            }
        }
        $res = Db::get_instance()->delete('stock_available', '`id_product` = ' . (int) $id_product . ($id_product_attribute ? ' AND `id_product_attribute` = ' . (int) $id_product_attribute : '') . static::add_sql_shop_restriction(null, $shop));
        if ($id_product_attribute) {
            if ($shop === null || !Validate::is_loaded_object($shop)) {
                $shop_datas = [];
                static::add_sql_shop_params($shop_datas);
                $id_shop = (int) $shop_datas['id_shop'];
            } else {
                $id_shop = (int) $shop->id;
            }
            $stock_available = new Stock_Available();
            $stock_available->id_product = (int) $id_product;
            $stock_available->id_product_attribute = (int) $id_product_attribute;
            $stock_available->id_shop = $id_shop;
            $stock_available->post_save();
        }
        static::clean_quantity_cache($id_product);
        return $res;
    }
    /**
     * Removes all product quantities from all a group of shops
     * If stocks are shared, remove all old available quantities for all shops of the group
     * Else remove all available quantities for the current group
     *
     * @param ShopGroup $shopGroup the ShopGroup object
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function reset_product_from_stock_available_by_shop_group(Shop_Group $shop_group)
    {
        if ($shop_group->share_stock) {
            $shop_list = Shop::get_shops(false, $shop_group->id, true);
        }
        $conn = Db::get_instance();
        if (isset($shop_list) && count($shop_list) > 0) {
            $id_shops_list = implode(', ', $shop_list);
            return $conn->update('stock_available', ['quantity' => 0], 'id_shop IN (' . $id_shops_list . ')');
        }
        return $conn->update('stock_available', ['quantity' => 0], 'id_shop_group = ' . $shop_group->id);
    }
    /**
     * For a given product, tells if it depends on the physical (usable) stock
     *
     * @param int $idProduct
     * @param int|null $idShop Optional : gets context if null
     * @param int $combinationId Optional
     *
     * @return bool : depends on stock
     *
     * @throws PrestaShopException
     */
    public static function depends_on_stock($id_product, $id_shop = null, $combination_id = 0)
    {
        if (!Validate::is_unsigned_id($id_product)) {
            return false;
        }
        $query = new Db_Query();
        $query->select('depends_on_stock');
        $query->from('stock_available');
        $query->where('id_product = ' . (int) $id_product);
        $query->where('id_product_attribute = ' . (int) $combination_id);
        $query = static::add_sql_shop_restriction($query, $id_shop);
        return (bool) Db::read_only()->get_value($query);
    }
    /**
     * For a given product, get its "out of stock" flag
     *
     * @param int $idProduct
     * @param int|null $idShop Optional : gets context if null
     * @param int $combinationId Optional
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function out_of_stock($id_product, $id_shop = null, $combination_id = 0)
    {
        if (!Validate::is_unsigned_id($id_product)) {
            return static::OUT_OF_STOCK_DENY;
        }
        $query = new Db_Query();
        $query->select('out_of_stock');
        $query->from('stock_available');
        $query->where('id_product = ' . (int) $id_product);
        $query->where('id_product_attribute = ' . (int) $combination_id);
        $query = static::add_sql_shop_restriction($query, $id_shop);
        $value = (int) Db::read_only()->get_value($query);
        if (static::is_valid_out_of_stock_value($value)) {
            return $value;
        }
        return static::OUT_OF_STOCK_DENY;
    }
    /**
     * Add an sql restriction for shops fields - specific to StockAvailable
     *
     * @param DbQuery|string|null $sql Reference to the query object
     * @param Shop|int|null $shop Optional : The shop ID
     * @param string|null $alias Optional : The current table alias
     *
     * @return string|DbQuery DbQuery object or the sql restriction string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function add_sql_shop_restriction($sql = null, $shop = null, $alias = null)
    {
        $context = Context::get_context();
        if (!empty($alias)) {
            $alias .= '.';
        }
        // if there is no $id_shop, gets the context one
        // get shop group too
        if ($shop === null || $shop === $context->shop->id) {
            if (Shop::get_context() == Shop::CONTEXT_GROUP) {
                $shop_group = Shop::get_context_shop_group();
            } else {
                $shop_group = $context->shop->get_group();
            }
            $shop = $context->shop;
        } elseif (is_object($shop)) {
            $shop_group = $shop->get_group();
        } else {
            $shop = new Shop($shop);
            $shop_group = $shop->get_group();
        }
        // if quantities are shared between shops of the group
        if ($shop_group->share_stock) {
            if (is_object($sql)) {
                $sql->where(p_sql($alias) . 'id_shop_group = ' . (int) $shop_group->id);
                $sql->where(p_sql($alias) . 'id_shop = 0');
            } else {
                $sql = ' AND ' . p_sql($alias) . 'id_shop_group = ' . (int) $shop_group->id . ' ';
                $sql .= ' AND ' . p_sql($alias) . 'id_shop = 0 ';
            }
        } else if (is_object($sql)) {
            $sql->where(p_sql($alias) . 'id_shop = ' . (int) $shop->id);
            $sql->where(p_sql($alias) . 'id_shop_group = 0');
        } else {
            $sql = ' AND ' . p_sql($alias) . 'id_shop = ' . (int) $shop->id . ' ';
            $sql .= ' AND ' . p_sql($alias) . 'id_shop_group = 0 ';
        }
        return $sql;
    }
    /**
     * Add sql params for shops fields - specific to StockAvailable
     *
     * @param array $params Reference to the params array
     * @param int $idShop Optional : The shop ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function add_sql_shop_params(&$params, $id_shop = null): void
    {
        $context = Context::get_context();
        $group_ok = false;
        // if there is no $id_shop, gets the context one
        // get shop group too
        if ($id_shop === null) {
            if (Shop::get_context() == Shop::CONTEXT_GROUP) {
                $shop_group = Shop::get_context_shop_group();
            } else {
                $shop_group = $context->shop->get_group();
                $id_shop = $context->shop->id;
            }
        } else {
            $shop = new Shop($id_shop);
            $shop_group = $shop->get_group();
        }
        // if quantities are shared between shops of the group
        if ($shop_group->share_stock) {
            $params['id_shop_group'] = (int) $shop_group->id;
            $params['id_shop'] = 0;
            $group_ok = true;
        } else {
            $params['id_shop_group'] = 0;
        }
        // if no group specific restriction, set simple shop restriction
        if (!$group_ok) {
            $params['id_shop'] = (int) $id_shop;
        }
    }
    /**
     * Copies stock available content table
     *
     * @param int $srcShopId
     * @param int $dstShopId
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function copy_stock_available_from_shop_to_shop($src_shop_id, $dst_shop_id)
    {
        if (!$src_shop_id || !$dst_shop_id) {
            return false;
        }
        $query = '
			INSERT INTO ' . _DB_PREFIX_ . 'stock_available
			(
				id_product,
				id_product_attribute,
				id_shop,
				id_shop_group,
				quantity,
				depends_on_stock,
				out_of_stock
			)
			(
				SELECT id_product, id_product_attribute, ' . (int) $dst_shop_id . ', 0, quantity, depends_on_stock, out_of_stock
				FROM ' . _DB_PREFIX_ . 'stock_available
				WHERE id_shop = ' . (int) $src_shop_id . ')';
        return Db::get_instance()->execute($query);
    }
    /**
     * @param int $productId
     * @throws PrestaShopException
     */
    public static function synchronize_dynamic_pack($product_id): void
    {
        static::synchronize_dynamic_packs([$product_id], true);
    }
    /**
     * @param int[] $productIds
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function synchronize_dynamic_packs($product_ids, $immediate_execution = false): void
    {
        $task = Dynamic_Packs_Synchronization_Task::create_task($product_ids);
        $work_queue_client = Service_Locator::get_instance()->get_work_queue_client();
        if ($immediate_execution) {
            $work_queue_client->run_immediately($task);
        } else {
            $work_queue_client->enqueue($task);
        }
    }
    /**
     * @param int $productId
     */
    public static function clean_quantity_cache($product_id): void
    {
        $product_id = (int) $product_id;
        Cache::clean('StockAvailable::getQuantityAvailableByProduct_' . $product_id . '-*');
        Cache::clean('StockAvailable::getCombinationQuantities' . $product_id . '-*');
        Cache::clean('StockAvailable::getStockAvailableIdByProductId_' . $product_id . '-*');
    }
    /**
     * @throws PrestaShopException
     */
    protected function trigger_update_hook()
    {
        $new_quantity = (int) $this->quantity;
        $old_quantity = (int) $this->original_quantity;
        if ($new_quantity !== $old_quantity) {
            $this->original_quantity = $new_quantity;
            // first, clear cache
            static::clean_quantity_cache($this->id_product);
            // and trigger hook
            Hook::trigger_event('actionUpdateQuantity', ['id_product' => (int) $this->id_product, 'id_product_attribute' => (int) $this->id_product_attribute, 'id_shop' => (int) $this->id_shop, 'id_shop_group' => (int) $this->id_shop_group, 'quantity' => $new_quantity, 'old_quantity' => $old_quantity]);
        }
    }
    protected static function is_valid_out_of_stock_value(int $value): bool
    {
        return in_array($value, [static::OUT_OF_STOCK_DENY, static::OUT_OF_STOCK_ALLOW, static::OUT_OF_STOCK_SYSTEM_DEFAULT]);
    }
}