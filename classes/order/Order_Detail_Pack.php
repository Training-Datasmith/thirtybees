<?php

declare (strict_types=1);
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
/**
 * Class OrderDetailPackCore
 */
class Order_Detail_Pack_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'order_detail_pack', 'primary' => 'id_order_detail_pack', 'fields' => ['id_order_detail' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'default' => '0'], 'quantity' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true]], 'keys' => ['order_detail_pack' => ['detail' => ['type' => Object_Model::KEY, 'columns' => ['id_order_detail']], 'product' => ['type' => Object_Model::KEY, 'columns' => ['id_product', 'id_product_attribute']]]]];
    /**
     * @var int $id_order_detail
     */
    public $id_order_detail;
    /**
     * @var int $id_product
     */
    public $id_product;
    /**
     * @var int $id_product_attribute
     */
    public $id_product_attribute;
    /**
     * @var int $quantity
     */
    public $quantity;
    /**
     * Is product a pack?
     *
     * @throws PrestaShopException
     */
    public static function is_pack(int $id_order_detail): bool
    {
        return (bool) static::get_pack_content($id_order_detail);
    }
    /**
     * @param int $idOrderDetail
     * @param int $idLang
     * @return Product[]
     * @throws PrestaShopException
     */
    public static function get_items($id_order_detail, $id_lang): array
    {
        if (!static::is_feature_active()) {
            return [];
        }
        $id_order_detail = (int) $id_order_detail;
        $id_lang = (int) $id_lang;
        $cache_key = "OrderDetailPack::getItems({$id_order_detail},{$id_lang})";
        if (!Cache::is_stored($cache_key)) {
            Cache::store($cache_key, static::retrieve_items($id_order_detail, $id_lang));
        }
        return Cache::retrieve($cache_key);
    }
    /**
     * @return Product[]
     * @throws PrestaShopException
     */
    protected static function retrieve_items(int $id_order_detail, int $id_lang): array
    {
        $array_result = [];
        foreach (static::get_pack_content($id_order_detail) as $row) {
            $p = new Product($row['id_product'], false, $id_lang);
            $p->load_stock_data();
            $p->pack_quantity = $row['quantity'];
            $p->id_pack_product_attribute = $row['id_product_attribute'];
            if ($p->id_pack_product_attribute) {
                $sql = 'SELECT agl.`name` AS group_name, al.`name` AS attribute_name, pa.`reference` AS attribute_reference
                    FROM `' . _DB_PREFIX_ . 'product_attribute` pa
                    ' . Shop::add_sql_association('product_attribute', 'pa') . '
                    LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . $id_lang . ')
                    LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . $id_lang . ')
                    WHERE pa.`id_product_attribute` = ' . $p->id_pack_product_attribute . '
                    GROUP BY pa.`id_product_attribute`, ag.`id_attribute_group`
                    ORDER BY pa.`id_product_attribute`';
                $combinations = Db::read_only()->get_array($sql);
                foreach ($combinations as $combination) {
                    $p->name .= ' ' . $combination['group_name'] . '-' . $combination['attribute_name'];
                    $reference = (string) $combination['attribute_reference'];
                    if ($reference) {
                        $p->reference = $combination['attribute_reference'];
                    }
                }
            }
            $array_result[] = $p;
        }
        return $array_result;
    }
    /**
     * Returns information about pack items.
     *
     * @throws PrestaShopException
     */
    public static function get_pack_content(int $id_order_detail): array
    {
        if (!$id_order_detail || !static::is_feature_active()) {
            return [];
        }
        $cache_key = "OrderDetailPack::getPackContent({$id_order_detail})";
        if (!Cache::is_stored($cache_key)) {
            Cache::store($cache_key, static::retrieve_pack_content($id_order_detail));
        }
        return Cache::retrieve($cache_key);
    }
    /**
     * Retrieves information about pack items from database
     *
     * @throws PrestaShopException
     */
    protected static function retrieve_pack_content(int $id_order_detail): array
    {
        $content = [];
        $sql = (new Db_Query())->select('id_product')->select('id_product_attribute')->select('quantity')->from('order_detail_pack')->where('id_order_detail = ' . $id_order_detail)->order_by('id_product, id_product_attribute');
        $result = Db::read_only()->get_array($sql);
        foreach ($result as $row) {
            $content[] = ['id_product' => (int) $row['id_product'], 'id_product_attribute' => (int) $row['id_product_attribute'], 'quantity' => (int) $row['quantity']];
        }
        return $content;
    }
    /**
     * This method is allow to know if a feature is used or active
     *
     * @throws PrestaShopException
     */
    public static function is_feature_active(): bool
    {
        return (bool) Configuration::get('PS_PACK_FEATURE_ACTIVE');
    }
}