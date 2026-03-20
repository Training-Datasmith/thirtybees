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
 * Class WarehouseProductLocationCore
 */
class Warehouse_Product_Location_Core extends Object_Model
{
    /**
     * @var int product ID
     */
    public $id_product;
    /**
     * @var int product attribute ID
     */
    public $id_product_attribute;
    /**
     * @var int warehouse ID
     */
    public $id_warehouse;
    /**
     * @var string location of the product
     */
    public $location;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'warehouse_product_location', 'primary' => 'id_warehouse_product_location', 'fields' => ['id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_warehouse' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'location' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => 64]], 'keys' => ['warehouse_product_location' => ['id_product' => ['type' => Object_Model::UNIQUE_KEY, 'columns' => ['id_product', 'id_product_attribute', 'id_warehouse']]]]];
    /**
     * @var array Webservice Parameters
     */
    protected $webservice_parameters = ['fields' => ['id_product' => ['xlink_resource' => 'products'], 'id_product_attribute' => ['xlink_resource' => 'combinations'], 'id_warehouse' => ['xlink_resource' => 'warehouses']], 'hidden_fields' => []];
    /**
     * For a given product and warehouse, gets the location
     *
     * @param int $idProduct product ID
     * @param int $idProductAttribute product attribute ID
     * @param int $idWarehouse warehouse ID
     *
     * @return string|false $location Location of the product
     *
     * @throws PrestaShopException
     */
    public static function get_product_location($id_product, $id_product_attribute, $id_warehouse)
    {
        // build query
        $query = new Db_Query();
        $query->select('wpl.location');
        $query->from('warehouse_product_location', 'wpl');
        $query->where('wpl.id_product = ' . (int) $id_product . '
			AND wpl.id_product_attribute = ' . (int) $id_product_attribute . '
			AND wpl.id_warehouse = ' . (int) $id_warehouse);
        return Db::read_only()->get_value($query);
    }
    /**
     * For a given product and warehouse, gets the WarehouseProductLocation corresponding ID
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $idWarehouse
     *
     * @return int $id_warehouse_product_location ID of the WarehouseProductLocation
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_product_and_warehouse($id_product, $id_product_attribute, $id_warehouse)
    {
        // build query
        $query = new Db_Query();
        $query->select('wpl.id_warehouse_product_location');
        $query->from('warehouse_product_location', 'wpl');
        $query->where('wpl.id_product = ' . (int) $id_product . '
			AND wpl.id_product_attribute = ' . (int) $id_product_attribute . '
			AND wpl.id_warehouse = ' . (int) $id_warehouse);
        return Db::read_only()->get_value($query);
    }
    /**
     * For a given product, gets its warehouses
     *
     * @param int $idProduct
     *
     * @return PrestaShopCollection The type of the collection is WarehouseProductLocation
     *
     * @throws PrestaShopException
     */
    public static function get_collection($id_product)
    {
        $collection = new Presta_Shop_Collection('WarehouseProductLocation');
        $collection->where('id_product', '=', (int) $id_product);
        return $collection;
    }
    /**
     * @param int $idWarehouse
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_products($id_warehouse)
    {
        return Db::read_only()->get_array('SELECT DISTINCT id_product FROM ' . _DB_PREFIX_ . 'warehouse_product_location WHERE id_warehouse=' . (int) $id_warehouse);
    }
}