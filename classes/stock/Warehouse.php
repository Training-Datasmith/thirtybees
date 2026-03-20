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
/**
 * Class WarehouseCore
 */
class Warehouse_Core extends Object_Model
{
    /** @var int identifier of the warehouse */
    public $id;
    /** @var int Id of the address associated to the warehouse */
    public $id_address;
    /** @var string Reference of the warehouse */
    public $reference;
    /** @var string Name of the warehouse */
    public $name;
    /** @var int Id of the employee who manages the warehouse */
    public $id_employee;
    /** @var int Id of the valuation currency of the warehouse */
    public $id_currency;
    /** @var bool True if warehouse has been deleted (hence, no deletion in DB) */
    public $deleted = 0;
    /**
     * Describes the way a Warehouse is managed
     *
     * @var string enum WA|LIFO|FIFO
     */
    public $management_type;
    /**
     * @see ObjectModel::$definition
     */
    public static $definition = ['table' => 'warehouse', 'primary' => 'id_warehouse', 'fields' => ['id_currency' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_address' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 32, 'dbDefault' => Object_Model::DEFAULT_NULL, 'dbNullable' => true], 'name' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 45], 'management_type' => ['type' => self::TYPE_STRING, 'validate' => 'isStockManagement', 'required' => true, 'values' => ['WA', 'FIFO', 'LIFO'], 'dbDefault' => 'WA'], 'deleted' => ['type' => self::TYPE_BOOL, 'dbDefault' => '0']], 'keys' => ['warehouse_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']], 'id_warehouse' => ['type' => Object_Model::KEY, 'columns' => ['id_warehouse']]]]];
    /**
     * @var array Webservice Parameters
     */
    protected $webservice_parameters = ['fields' => ['id_address' => ['xlink_resource' => 'addresses'], 'id_employee' => ['xlink_resource' => 'employees'], 'id_currency' => ['xlink_resource' => 'currencies'], 'valuation' => ['getter' => 'getWsStockValue', 'setter' => false], 'deleted' => []], 'associations' => ['stocks' => ['resource' => 'stock', 'fields' => ['id' => []]], 'carriers' => ['resource' => 'carrier', 'fields' => ['id' => []]], 'shops' => ['resource' => 'shop', 'fields' => ['id' => [], 'name' => []]]]];
    /**
     * Gets the shops associated to the current warehouse
     *
     * @return array Shops (id, name)
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_shops()
    {
        $query = new Db_Query();
        $query->select('ws.id_shop, s.name');
        $query->from('warehouse_shop', 'ws');
        $query->left_join('shop', 's', 's.id_shop = ws.id_shop');
        $query->where($this->def['primary'] . ' = ' . (int) $this->id);
        return Db::read_only()->get_array($query);
    }
    /**
     * Gets the carriers associated to the current warehouse
     *
     * @param bool $returnReference
     * @return array Ids of the associated carriers
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_carriers($return_reference = false)
    {
        $ids_carrier = [];
        $query = new Db_Query();
        if ($return_reference) {
            $query->select('wc.id_carrier');
        } else {
            $query->select('c.id_carrier');
        }
        $query->from('warehouse_carrier', 'wc');
        $query->inner_join('carrier', 'c', 'c.id_reference = wc.id_carrier');
        $query->where($this->def['primary'] . ' = ' . (int) $this->id);
        $query->where('c.deleted = 0');
        $res = Db::read_only()->get_array($query);
        foreach ($res as $carriers) {
            foreach ($carriers as $carrier) {
                $ids_carrier[$carrier] = $carrier;
            }
        }
        return $ids_carrier;
    }
    /**
     * Sets the carriers associated to the current warehouse
     *
     * @param array $idsCarriers
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_carriers($ids_carriers): void
    {
        if (!is_array($ids_carriers)) {
            $ids_carriers = [];
        }
        $row_to_insert = [];
        foreach ($ids_carriers as $id_carrier) {
            $row_to_insert[] = [$this->def['primary'] => $this->id, 'id_carrier' => (int) $id_carrier];
        }
        $conn = Db::get_instance();
        $conn->execute('
			DELETE FROM ' . _DB_PREFIX_ . 'warehouse_carrier
			WHERE ' . $this->def['primary'] . ' = ' . (int) $this->id);
        if ($row_to_insert) {
            $conn->insert('warehouse_carrier', $row_to_insert);
        }
    }
    /**
     * For a given carrier, removes it from the warehouse/carrier association
     * If $id_warehouse is set, it only removes the carrier for this warehouse
     *
     * @param int $idCarrier Id of the carrier to remove
     * @param int $idWarehouse optional Id of the warehouse to filter
     *
     * @throws PrestaShopException
     */
    public static function remove_carrier($id_carrier, $id_warehouse = null): void
    {
        Db::get_instance()->execute('
			DELETE FROM ' . _DB_PREFIX_ . 'warehouse_carrier
			WHERE id_carrier = ' . (int) $id_carrier . ($id_warehouse ? ' AND id_warehouse = ' . (int) $id_warehouse : ''));
    }
    /**
     * Checks if a warehouse is empty - i.e. has no stock
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_empty()
    {
        $query = new Db_Query();
        $query->select('SUM(s.physical_quantity)');
        $query->from('stock', 's');
        $query->where($this->def['primary'] . ' = ' . (int) $this->id);
        return Db::read_only()->get_value($query) == 0;
    }
    /**
     * Checks if the given warehouse exists
     *
     * @param int $idWarehouse
     *
     * @return bool Exists/Does not exist
     *
     * @throws PrestaShopException
     */
    public static function exists($id_warehouse)
    {
        $query = new Db_Query();
        $query->select('id_warehouse');
        $query->from('warehouse');
        $query->where('id_warehouse = ' . (int) $id_warehouse);
        $query->where('deleted = 0');
        return Db::read_only()->get_value($query);
    }
    /**
     * For a given {product, product attribute} sets its location in the given warehouse
     * First, for the given parameters, it cleans the database before updating
     *
     * @param int $idProduct ID of the product
     * @param int $idProductAttribute Use 0 if this product does not have attributes
     * @param int $idWarehouse ID of the warehouse
     * @param string $location Describes the location (no lang id required)
     *
     * @return bool Success/Failure
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function set_product_location($id_product, $id_product_attribute, $id_warehouse, $location)
    {
        $conn = Db::get_instance();
        $conn->execute('
			DELETE FROM `' . _DB_PREFIX_ . 'warehouse_product_location`
			WHERE `id_product` = ' . (int) $id_product . '
			AND `id_product_attribute` = ' . (int) $id_product_attribute . '
			AND `id_warehouse` = ' . (int) $id_warehouse);
        $row_to_insert = ['id_product' => (int) $id_product, 'id_product_attribute' => (int) $id_product_attribute, 'id_warehouse' => (int) $id_warehouse, 'location' => p_sql($location)];
        return $conn->insert('warehouse_product_location', $row_to_insert);
    }
    /**
     * Resets all product locations for this warehouse
     *
     * @throws PrestaShopException
     */
    public function reset_products_locations(): void
    {
        Db::get_instance()->execute('
			DELETE FROM `' . _DB_PREFIX_ . 'warehouse_product_location`
			WHERE `id_warehouse` = ' . (int) $this->id);
    }
    /**
     * For a given {product, product attribute} gets its location in the given warehouse
     *
     * @param int $idProduct ID of the product
     * @param int $idProductAttribute Use 0 if this product does not have attributes
     * @param int $idWarehouse ID of the warehouse
     *
     * @return string Location of the product
     *
     * @throws PrestaShopException
     */
    public static function get_product_location($id_product, $id_product_attribute, $id_warehouse)
    {
        $query = new Db_Query();
        $query->select('location');
        $query->from('warehouse_product_location');
        $query->where('id_warehouse = ' . (int) $id_warehouse);
        $query->where('id_product = ' . (int) $id_product);
        $query->where('id_product_attribute = ' . (int) $id_product_attribute);
        return Db::read_only()->get_value($query);
    }
    /**
     * For a given {product, product attribute} gets warehouse list
     *
     * @param int $idProduct ID of the product
     * @param int $idProductAttribute Optional, uses 0 if this product does not have attributes
     * @param int $idShop Optional, ID of the shop. Uses the context shop id
     *
     * @return array Warehouses (ID, reference/name concatenated)
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_product_warehouse_list($id_product, $id_product_attribute = 0, $id_shop = null)
    {
        // if it's a pack, returns warehouses if and only if some products use the advanced stock management
        if ($id_shop === null) {
            if (Shop::get_context() == Shop::CONTEXT_GROUP) {
                $shop_group = Shop::get_context_shop_group();
            } else {
                $shop_group = Context::get_context()->shop->get_group();
            }
            $id_shop = (int) Context::get_context()->shop->id;
            $share_stock = $shop_group->share_stock;
            $shop_group_id = (int) $shop_group->id;
        } else {
            $shop_group = Shop::get_group_from_shop($id_shop, false);
            $shop_group_id = (int) $shop_group['id'];
            $share_stock = (bool) $shop_group['share_stock'];
        }
        if ($share_stock) {
            $ids_shop = Shop::get_shops(true, $shop_group_id, true);
        } else {
            $ids_shop = [(int) $id_shop];
        }
        $query = new Db_Query();
        $query->select('wpl.id_warehouse, CONCAT(w.reference, " - ", w.name) as name');
        $query->from('warehouse_product_location', 'wpl');
        $query->inner_join('warehouse_shop', 'ws', 'ws.id_warehouse = wpl.id_warehouse AND id_shop IN (' . implode(',', array_map(intval(...), $ids_shop)) . ')');
        $query->inner_join('warehouse', 'w', 'ws.id_warehouse = w.id_warehouse');
        $query->where('id_product = ' . (int) $id_product);
        $query->where('id_product_attribute = ' . (int) $id_product_attribute);
        $query->where('w.deleted = 0');
        $query->group_by('wpl.id_warehouse');
        return Db::read_only()->get_array($query);
    }
    /**
     * Gets available warehouses
     * It is possible via ignore_shop and id_shop to filter the list with shop id
     *
     * @param bool $ignoreShop Optional, false by default - Allows to get only the warehouses that are associated to one/some shops
     * @param int $idShop Optional, Context::shop::Id by default - Allows to define a specific shop to filter.
     *
     * @return array Warehouses (ID, reference/name concatenated)
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_warehouses($ignore_shop = false, $id_shop = null)
    {
        if (!$ignore_shop) {
            if (is_null($id_shop)) {
                $id_shop = Context::get_context()->shop->id;
            }
        }
        $query = new Db_Query();
        $query->select('w.id_warehouse, CONCAT(reference, \' - \', name) as name');
        $query->from('warehouse', 'w');
        $query->where('deleted = 0');
        $query->order_by('reference ASC');
        if (!$ignore_shop) {
            $query->inner_join('warehouse_shop', 'ws', 'ws.id_warehouse = w.id_warehouse AND ws.id_shop = ' . (int) $id_shop);
        }
        return Db::read_only()->get_array($query);
    }
    /**
     * Gets warehouses grouped by shops
     *
     * @return array (of array) Warehouses ID are grouped by shops ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_warehouses_grouped_by_shops()
    {
        $ids_warehouse = [];
        $query = new Db_Query();
        $query->select('id_warehouse, id_shop');
        $query->from('warehouse_shop');
        $query->order_by('id_shop');
        // queries to get warehouse ids grouped by shops
        foreach (Db::read_only()->get_array($query) as $row) {
            $ids_warehouse[$row['id_shop']][] = $row['id_warehouse'];
        }
        return $ids_warehouse;
    }
    /**
     * Gets the number of products in the current warehouse
     *
     * @return int Number of different id_stock
     *
     * @throws PrestaShopException
     */
    public function get_number_of_products()
    {
        $query = '
			SELECT COUNT(t.id_stock)
			FROM
				(
					SELECT s.id_stock
				 	FROM ' . _DB_PREFIX_ . 'stock s
				 	WHERE s.id_warehouse = ' . (int) $this->id . '
				 	GROUP BY s.id_product, s.id_product_attribute
				 ) as t';
        return Db::read_only()->get_value($query);
    }
    /**
     * Gets the number of quantities - for all products - in the current warehouse
     *
     * @return int Total Quantity
     *
     * @throws PrestaShopException
     */
    public function get_quantities_of_products()
    {
        $query = '
			SELECT SUM(s.physical_quantity)
			FROM ' . _DB_PREFIX_ . 'stock s
			WHERE s.id_warehouse = ' . (int) $this->id;
        $res = Db::read_only()->get_value($query);
        return $res ?: 0;
    }
    /**
     * Gets the value of the stock in the current warehouse
     *
     * @return int Value of the stock
     *
     * @throws PrestaShopException
     */
    public function get_stock_value()
    {
        $query = new Db_Query();
        $query->select('SUM(s.`price_te` * s.`physical_quantity`)');
        $query->from('stock', 's');
        $query->where('s.`id_warehouse` = ' . (int) $this->id);
        return Db::read_only()->get_value($query);
    }
    /**
     * For a given employee, gets the warehouse(s) he/she manages
     *
     * @param int $idEmployee Manager ID
     *
     * @return array ids_warehouse Ids of the warehouses
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_warehouses_by_employee($id_employee)
    {
        $query = new Db_Query();
        $query->select('w.id_warehouse');
        $query->from('warehouse', 'w');
        $query->where('w.id_employee = ' . (int) $id_employee);
        return Db::read_only()->get_array($query);
    }
    /**
     * For a given product, returns the warehouses it is stored in
     *
     * @param int $idProduct Product Id
     * @param int $idProductAttribute Optional, Product Attribute Id - 0 by default (no attribues)
     *
     * @return array Warehouses Ids and names
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_warehouses_by_product_id($id_product, $id_product_attribute = 0)
    {
        if (!$id_product && !$id_product_attribute) {
            return [];
        }
        $query = new Db_Query();
        $query->select('DISTINCT w.id_warehouse, CONCAT(w.reference, " - ", w.name) as name');
        $query->from('warehouse', 'w');
        $query->left_join('warehouse_product_location', 'wpl', 'wpl.id_warehouse = w.id_warehouse');
        if ($id_product) {
            $query->where('wpl.id_product = ' . (int) $id_product);
        }
        if ($id_product_attribute) {
            $query->where('wpl.id_product_attribute = ' . (int) $id_product_attribute);
        }
        $query->order_by('w.reference ASC');
        return Db::read_only()->get_array($query);
    }
    /**
     * For a given $id_warehouse, returns its name
     *
     * @param int $idWarehouse Warehouse Id
     *
     * @return string Name
     *
     * @throws PrestaShopException
     */
    public static function get_warehouse_name_by_id($id_warehouse)
    {
        $query = new Db_Query();
        $query->select('name');
        $query->from('warehouse');
        $query->where('id_warehouse = ' . (int) $id_warehouse);
        return Db::read_only()->get_value($query);
    }
    /**
     * For a given pack, returns the warehouse it can be shipped from
     *
     * @param int $idProduct
     *
     * @param int|null $idShop
     *
     * @return array id_warehouse
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_pack_warehouses($id_product, $id_shop = null)
    {
        if (!Pack::is_pack($id_product)) {
            return [];
        }
        if (is_null($id_shop)) {
            $id_shop = Context::get_context()->shop->id;
        }
        // warehouses of the pack
        $pack_warehouses = Warehouse_Product_Location::get_collection((int) $id_product);
        // products in the pack
        $products = Pack::get_items((int) $id_product, Configuration::get('PS_LANG_DEFAULT'));
        // array with all warehouses id to check
        $list = ['pack_warehouses' => []];
        // fills $list
        foreach ($pack_warehouses as $pack_warehouse) {
            /** @var WarehouseProductLocation $pack_warehouse */
            $list['pack_warehouses'][] = (int) $pack_warehouse->id_warehouse;
        }
        // for each products in the pack
        foreach ($products as $product) {
            if ($product->advanced_stock_management) {
                // gets the warehouses of one product
                $product_warehouses = Warehouse::get_product_warehouse_list((int) $product->id, (int) $product->cache_default_attribute, (int) $id_shop);
                $list[(int) $product->id] = [];
                // fills array with warehouses for this product
                foreach ($product_warehouses as $product_warehouse) {
                    $list[(int) $product->id][] = $product_warehouse['id_warehouse'];
                }
            }
        }
        // returns final list
        if (count($list) > 1) {
            return call_user_func_array(array_intersect(...), array_values($list));
        }
        return [];
    }
    /**
     * @throws PrestaShopException
     */
    public function reset_stock_available(): void
    {
        $products = Warehouse_Product_Location::get_products((int) $this->id);
        foreach ($products as $product) {
            Stock_Available::synchronize((int) $product['id_product']);
        }
    }
    /**
     * Webservice : gets the value of the warehouse
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function get_ws_stock_value()
    {
        return $this->get_stock_value();
    }
    /**
     * Webservice : gets the ids stock associated to this warehouse
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_stocks()
    {
        $query = new Db_Query();
        $query->select('s.id_stock as id');
        $query->from('stock', 's');
        $query->where('s.id_warehouse =' . (int) $this->id);
        return Db::read_only()->get_array($query);
    }
    /**
     * Webservice : gets the ids shops associated to this warehouse
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_shops()
    {
        $query = new Db_Query();
        $query->select('ws.id_shop as id, s.name');
        $query->from('warehouse_shop', 'ws');
        $query->left_join('shop', 's', 's.id_shop = ws.id_shop');
        $query->where($this->def['primary'] . ' = ' . (int) $this->id);
        return Db::read_only()->get_array($query);
    }
    /**
     * Webservice : gets the ids carriers associated to this warehouse
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_carriers()
    {
        $ids_carrier = [];
        $query = new Db_Query();
        $query->select('wc.id_carrier as id');
        $query->from('warehouse_carrier', 'wc');
        $query->where($this->def['primary'] . ' = ' . (int) $this->id);
        $res = Db::read_only()->get_array($query);
        foreach ($res as $carriers) {
            foreach ($carriers as $carrier) {
                $ids_carrier[] = $carrier;
            }
        }
        return $ids_carrier;
    }
    /**
     * @param TableSchema $table
     */
    public static function process_table_schema($table): void
    {
        if ($table->get_name_without_prefix() === 'warehouse_shop') {
            $table->reorder_columns(['id_shop', 'id_warehouse']);
        }
    }
}