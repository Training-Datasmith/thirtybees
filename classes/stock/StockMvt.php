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
 * Class StockMvtCore
 */
class Stock_Mvt_Core extends Object_Model
{
    /**
     * @var int|null Object ID
     */
    public $id;
    /**
     * @var string The creation date of the movement
     */
    public $date_add;
    /**
     * @var int The employee id, responsible of the movement
     */
    public $id_employee;
    /**
     * @var string The first name of the employee responsible of the movement
     */
    public $employee_firstname;
    /**
     * @var string The last name of the employee responsible of the movement
     */
    public $employee_lastname;
    /**
     * @var int The stock id on wtich the movement is applied
     */
    public $id_stock;
    /**
     * @var int the quantity of product with is moved
     */
    public $physical_quantity;
    /**
     * @var int id of the movement reason assoiated to the movement
     */
    public $id_stock_mvt_reason;
    /**
     * @var int Used when the movement is due to a customer order
     */
    public $id_order;
    /**
     * @var int detrmine if the movement is a positive or negative operation
     */
    public $sign;
    /**
     * @var int Used when the movement is due to a supplier order
     */
    public $id_supply_order;
    /**
     * @var float Last value of the weighted-average method
     */
    public $last_wa;
    /**
     * @var float Current value of the weighted-average method
     */
    public $current_wa;
    /**
     * @var float The unit price without tax of the product associated to the movement
     */
    public $price_te;
    /**
     * @var int Refers to an other id_stock_mvt : used for LIFO/FIFO implementation in StockManager
     */
    public $referer;
    /**
     * @deprecated since 1.5.0
     * @deprecated stock movement will not be updated anymore
     */
    public $date_upd;
    /**
     * @var int
     *
     * @deprecated since 1.5.0
     */
    public $quantity;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'stock_mvt', 'primary' => 'id_stock_mvt', 'primaryKeyDbType' => 'bigint(20) unsigned', 'fields' => ['id_stock' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'], 'id_supply_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'], 'id_stock_mvt_reason' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'employee_lastname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 32, 'dbDefault' => '', 'dbNullable' => true], 'employee_firstname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'size' => 32, 'dbDefault' => '', 'dbNullable' => true], 'physical_quantity' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true], 'sign' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true, 'dbType' => 'tinyint(1)', 'dbDefault' => '1'], 'price_te' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbDefault' => '0.000000', 'dbNullable' => true], 'last_wa' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000', 'dbNullable' => true], 'current_wa' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000', 'dbNullable' => true], 'referer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'bigint(20) unsigned']], 'keys' => ['stock_mvt' => ['id_stock' => ['type' => Object_Model::KEY, 'columns' => ['id_stock']], 'id_stock_mvt_reason' => ['type' => Object_Model::KEY, 'columns' => ['id_stock_mvt_reason']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectsNodeName' => 'stock_movements', 'objectNodeName' => 'stock_movement', 'fields' => ['id_employee' => ['xlink_resource' => 'employees'], 'id_stock' => ['xlink_resource' => 'stock'], 'id_stock_mvt_reason' => ['xlink_resource' => 'stock_movement_reasons'], 'id_order' => ['xlink_resource' => 'orders'], 'id_supply_order' => ['xlink_resource' => 'supply_order']]];
    /**
     * @deprecated 1.0.0
     *
     * This method no longer exists.
     * There is no equivalent or replacement, considering that this should be handled by inventories.
     */
    public static function add_missing_mvt($id_employee): void
    {
        // display that this method is deprecated
        Tools::display_as_deprecated();
    }
    /**
     * Gets the negative (decrements the stock) stock mvts that correspond to the given order, for :
     * the given product, in the given quantity.
     *
     * @param int $idOrder
     * @param int $idProduct
     * @param int $idProductAttribute Use 0 if the product does not have attributes
     * @param int $quantity
     * @param int|null $idWarehouse Optional
     *
     * @return array mvts
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_negative_stock_mvts($id_order, $id_product, $id_product_attribute, $quantity, $id_warehouse = null)
    {
        $movements = [];
        $quantity_total = 0;
        // preps query
        $query = new Db_Query();
        $query->select('sm.*, s.id_warehouse');
        $query->from('stock_mvt', 'sm');
        $query->inner_join('stock', 's', 's.id_stock = sm.id_stock');
        $query->where('sm.sign = -1');
        $query->where('sm.id_order = ' . (int) $id_order);
        $query->where('s.id_product = ' . (int) $id_product . ' AND s.id_product_attribute = ' . (int) $id_product_attribute);
        // if filer by warehouse
        if (!is_null($id_warehouse)) {
            $query->where('s.id_warehouse = ' . (int) $id_warehouse);
        }
        // orders the movements by date
        $query->order_by('date_add DESC');
        // gets the result
        $res = Db::read_only()->get_array($query);
        // fills the movements array
        foreach ($res as $row) {
            if ($quantity_total >= $quantity) {
                break;
            }
            $quantity_total += (int) $row['physical_quantity'];
            $movements[] = $row;
        }
        return $movements;
    }
    /**
     * For a given product, gets the last positive stock mvt
     *
     * @param int $idProduct
     * @param int $idProductAttribute Use 0 if the product does not have attributes
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_last_positive_stock_mvt($id_product, $id_product_attribute)
    {
        $query = new Db_Query();
        $query->select('sm.*, w.id_currency, (s.usable_quantity = sm.physical_quantity) as is_usable');
        $query->from('stock_mvt', 'sm');
        $query->inner_join('stock', 's', 's.id_stock = sm.id_stock');
        $query->inner_join('warehouse', 'w', 'w.id_warehouse = s.id_warehouse');
        $query->where('sm.sign = 1');
        if ($id_product_attribute) {
            $query->where('s.id_product = ' . (int) $id_product . ' AND s.id_product_attribute = ' . (int) $id_product_attribute);
        } else {
            $query->where('s.id_product = ' . (int) $id_product);
        }
        $query->order_by('date_add DESC');
        return Db::read_only()->get_row($query);
    }
}