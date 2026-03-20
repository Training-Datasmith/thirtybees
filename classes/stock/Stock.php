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
 * Represents the products kept in warehouses
 */
class Stock_Core extends Object_Model
{
    /** @var int identifier of the warehouse */
    public $id_warehouse;
    /** @var int identifier of the product */
    public $id_product;
    /** @var int identifier of the product attribute if necessary */
    public $id_product_attribute;
    /** @var string Product reference */
    public $reference;
    /** @var string Product EAN13 */
    public $ean13;
    /** @var string UPC */
    public $upc;
    /** @var int the physical quantity in stock for the current product in the current warehouse */
    public $physical_quantity;
    /** @var int the usable quantity (for sale) of the current physical quantity */
    public $usable_quantity;
    /** @var float the unit price without tax forthe current product */
    public $price_te;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'stock', 'primary' => 'id_stock', 'fields' => ['id_warehouse' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => self::SIZE_REFERENCE, 'dbNullable' => false], 'ean13' => ['type' => self::TYPE_STRING, 'validate' => 'isEan13', 'size' => 13], 'upc' => ['type' => self::TYPE_STRING, 'validate' => 'isUpc', 'size' => 12], 'physical_quantity' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true], 'usable_quantity' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true], 'price_te' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbDefault' => '0.000000', 'dbNullable' => true]], 'keys' => ['stock' => ['id_product' => ['type' => Object_Model::KEY, 'columns' => ['id_product']], 'id_product_attribute' => ['type' => Object_Model::KEY, 'columns' => ['id_product_attribute']], 'id_warehouse' => ['type' => Object_Model::KEY, 'columns' => ['id_warehouse']]]]];
    /**
     * @var array Webservice Parameters
     */
    protected $webservice_parameters = ['fields' => ['id_warehouse' => ['xlink_resource' => 'warehouses'], 'id_product' => ['xlink_resource' => 'products'], 'id_product_attribute' => ['xlink_resource' => 'combinations'], 'real_quantity' => ['getter' => 'getWsRealQuantity', 'setter' => false]], 'hidden_fields' => []];
    /**
     * @param bool $nullValues
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        $this->get_product_informations();
        return parent::update($null_values);
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
        $this->get_product_informations();
        return parent::add($auto_date, $null_values);
    }
    /**
     * Webservice : used to get the real quantity of a product
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_real_quantity()
    {
        $manager = Stock_Manager_Factory::get_manager();
        return $manager->get_product_real_quantities($this->id_product, $this->id_product_attribute, $this->id_warehouse, true);
    }
    /**
     * @param int|null $idProduct
     * @param int|null $idProductAttribute
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function delete_stock_by_ids($id_product = null, $id_product_attribute = null)
    {
        if (!$id_product || !$id_product_attribute) {
            return false;
        }
        return Db::get_instance()->delete('stock', '`id_product` = ' . (int) $id_product . ' AND `id_product_attribute` = ' . (int) $id_product_attribute);
    }
    /**
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $idWarehouse
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function product_is_present_in_stock($id_product = 0, $id_product_attribute = 0, $id_warehouse = 0)
    {
        if (!(int) $id_product && !is_int($id_product_attribute) && !(int) $id_warehouse) {
            return false;
        }
        return (bool) Db::read_only()->get_value((new Db_Query())->select('`id_stock`')->from('stock')->where('`id_warehouse` = ' . (int) $id_warehouse)->where('`id_product` = ' . (int) $id_product)->where((int) $id_product_attribute ? '`id_product_attribute` = ' . $id_product_attribute : ''));
    }
    /**
     * Gets reference, ean13 and upc of the current product
     * Stores it in stock for stock_mvt integrity and history purposes
     *
     * @throws PrestaShopException
     */
    protected function get_product_informations()
    {
        // if combinations
        if ((int) $this->id_product_attribute > 0) {
            $rows = Db::read_only()->get_array((new Db_Query())->select('reference, ean13, upc')->from('product_attribute')->where('id_product = ' . (int) $this->id_product)->where('id_product_attribute = ' . (int) $this->id_product_attribute));
            foreach ($rows as $row) {
                $this->reference = $row['reference'];
                $this->ean13 = $row['ean13'];
                $this->upc = $row['upc'];
            }
        } else {
            // else, simple product
            $product = new Product((int) $this->id_product);
            if (Validate::is_loaded_object($product)) {
                $this->reference = $product->reference;
                $this->ean13 = $product->ean13;
                $this->upc = $product->upc;
            }
        }
    }
}