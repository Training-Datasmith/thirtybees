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
 * Class ProductSupplierCore
 */
class Product_Supplier_Core extends Object_Model
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
     * @var int the supplier ID
     */
    public $id_supplier;
    /**
     * @var string The supplier name of the product
     */
    public $product_supplier_name;
    /**
     * @var string The supplier reference of the product
     */
    public $product_supplier_reference;
    /**
     * @var int the currency ID for unit price tax excluded
     */
    public $id_currency;
    /**
     * @var float The unit price tax excluded of the product
     */
    public $product_supplier_price_te;
    /**
     * @var string Additional information for this product
     */
    public $product_supplier_comment;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'product_supplier', 'primary' => 'id_product_supplier', 'fields' => ['id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'dbDefault' => '0'], 'id_supplier' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'product_supplier_name' => ['type' => self::TYPE_STRING, 'validate' => 'isCatalogName', 'size' => 128], 'product_supplier_reference' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => self::SIZE_REFERENCE], 'product_supplier_price_te' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'product_supplier_comment' => ['type' => self::TYPE_STRING, 'size' => 250], 'id_currency' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false]], 'keys' => ['product_supplier' => ['id_product' => ['type' => Object_Model::UNIQUE_KEY, 'columns' => ['id_product', 'id_product_attribute', 'id_supplier']], 'id_supplier' => ['type' => Object_Model::KEY, 'columns' => ['id_supplier', 'id_product']]]]];
    /**
     * @see ObjectModel::$webserviceParameters
     */
    protected $webservice_parameters = ['objectsNodeName' => 'product_suppliers', 'objectNodeName' => 'product_supplier', 'fields' => ['id_product' => ['xlink_resource' => 'products'], 'id_product_attribute' => ['xlink_resource' => 'combinations'], 'id_supplier' => ['xlink_resource' => 'suppliers'], 'id_currency' => ['xlink_resource' => 'currencies']]];
    /**
     * For a given product and supplier, gets the product supplier reference
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $idSupplier
     *
     * @return string|false
     *
     * @throws PrestaShopException
     */
    public static function get_product_supplier_reference($id_product, $id_product_attribute, $id_supplier)
    {
        return Db::read_only()->get_value((new Db_Query())->select('ps.`product_supplier_reference`')->from('product_supplier', 'ps')->where('ps.`id_product` = ' . (int) $id_product)->where('ps.`id_product_attribute` = ' . (int) $id_product_attribute)->where('ps.`id_supplier` = ' . (int) $id_supplier));
    }
    /**
     * For a given product and supplier, gets the product supplier unit price
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $idSupplier
     * @param bool $withCurrency Optional
     *
     * @return int|array
     *
     * @throws PrestaShopException
     */
    public static function get_product_supplier_price($id_product, $id_product_attribute, $id_supplier, $with_currency = false)
    {
        // build query
        $query = new Db_Query();
        $query->select('ps.product_supplier_price_te');
        if ($with_currency) {
            $query->select('ps.id_currency');
        }
        $query->from('product_supplier', 'ps');
        $query->where('ps.id_product = ' . (int) $id_product . '
			AND ps.id_product_attribute = ' . (int) $id_product_attribute . '
			AND ps.id_supplier = ' . (int) $id_supplier);
        if (!$with_currency) {
            return (int) Db::read_only()->get_value($query);
        }
        $res = Db::read_only()->get_array($query);
        return $res[0] ?? $res;
    }
    /**
     * For a given product and supplier, gets corresponding ProductSupplier ID
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $idSupplier
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_product_and_supplier($id_product, $id_product_attribute, $id_supplier)
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('ps.id_product_supplier')->from('product_supplier', 'ps')->where('ps.id_product = ' . (int) $id_product)->where('ps.id_product_attribute = ' . (int) $id_product_attribute)->where('ps.id_supplier = ' . (int) $id_supplier));
    }
    /**
     * For a given Supplier, Product, returns the purchased price
     *
     * @param int $idSupplier
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param bool $convertedPrice Whether price should be converted to the current currency.
     *
     * @return float|false Price, rounded to _TB_PRICE_DATABASE_PRECISION_, or false on failure.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_product_price($id_supplier, $id_product, $id_product_attribute = 0, $converted_price = false)
    {
        if (is_null($id_supplier) || is_null($id_product)) {
            return false;
        }
        $row = Db::read_only()->get_row((new Db_Query())->select('product_supplier_price_te as price_te, id_currency')->from('product_supplier')->where('id_product = ' . (int) $id_product . ' AND id_product_attribute = ' . (int) $id_product_attribute)->where('id_supplier = ' . (int) $id_supplier));
        if ($row && isset($row['price_te'])) {
            if ($converted_price) {
                return Tools::convert_price($row['price_te'], $row['id_currency'], false);
            }
            return $row['price_te'];
        }
        return false;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete()
    {
        $res = parent::delete();
        if ($res && $this->id_product_attribute == 0) {
            $items = Product_Supplier::get_supplier_collection($this->id_product, false);
            foreach ($items as $item) {
                /** @var ProductSupplier $item */
                if ($item->id_product_attribute > 0) {
                    $item->delete();
                }
            }
        }
        return $res;
    }
    /**
     * For a given product, retrieves its suppliers
     *
     * @param int $idProduct
     * @param bool $groupBySupplier
     *
     * @return PrestaShopCollection Collection of ProductSupplier
     *
     * @throws PrestaShopException
     */
    public static function get_supplier_collection($id_product, $group_by_supplier = true)
    {
        $suppliers = new Presta_Shop_Collection('ProductSupplier');
        $suppliers->where('id_product', '=', (int) $id_product);
        if ($group_by_supplier) {
            $suppliers->group_by('id_supplier');
        }
        return $suppliers;
    }
}