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
 * Class PackCore
 */
class Pack_Core extends Product
{
    public const STOCK_TYPE_DECREMENT_PACK = 0;
    public const STOCK_TYPE_DECREMENT_PRODUCTS = 1;
    public const STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS = 2;
    public const STOCK_TYPE_DECREMENT_GLOBAL_SETTINGS = 3;
    public const STOCK_TYPE_ITEMS = 1;
    /**
     * @param int $idProduct
     *
     * @return float|int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function no_pack_price($id_product)
    {
        $sum = 0;
        $price_display_method = !static::$_tax_calculation_method;
        $items = static::get_items($id_product, Configuration::get('PS_LANG_DEFAULT'));
        foreach ($items as $item) {
            /** @var Product $item */
            $sum += $item->get_price($price_display_method, $item->id_pack_product_attribute ?: null) * $item->pack_quantity;
        }
        return $sum;
    }
    /**
     * @param int $idProduct
     * @param int $idLang
     *
     * @return Product[]
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_items($id_product, $id_lang)
    {
        if (!static::is_feature_active()) {
            return [];
        }
        $id_product = (int) $id_product;
        $id_lang = (int) $id_lang;
        $cache_key = "Pack::getItems({$id_product},{$id_lang})";
        if (!Cache::is_stored($cache_key)) {
            Cache::store($cache_key, static::retrieve_items($id_product, $id_lang));
        }
        return Cache::retrieve($cache_key);
    }
    /**
     * @param int $idProduct
     * @param int $idLang
     * @return Product[]
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function retrieve_items($id_product, $id_lang)
    {
        $id_product = (int) $id_product;
        $id_lang = (int) $id_lang;
        $array_result = [];
        foreach (static::get_pack_content($id_product) as $row) {
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
     * @param int $idProduct
     * @return array
     * @throws PrestaShopException
     */
    public static function get_pack_content($id_product)
    {
        $id_product = (int) $id_product;
        if (!$id_product || !static::is_feature_active()) {
            return [];
        }
        $cache_key = "Pack::getPackContent({$id_product})";
        if (!Cache::is_stored($cache_key)) {
            Cache::store($cache_key, static::retrieve_pack_content($id_product));
        }
        return Cache::retrieve($cache_key);
    }
    /**
     * Retrieves information about pack items from database
     *
     * @param int $idProduct
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function retrieve_pack_content($id_product)
    {
        $id_product = (int) $id_product;
        $content = [];
        $sql = (new Db_Query())->select('id_product_item AS id_product')->select('id_product_attribute_item AS id_product_attribute')->select('quantity')->from('pack')->where('id_product_pack = ' . $id_product)->order_by('id_product_item, id_product_attribute_item');
        $result = Db::read_only()->get_array($sql);
        foreach ($result as $row) {
            $content[] = ['id_product' => (int) $row['id_product'], 'id_product_attribute' => (int) $row['id_product_attribute'], 'quantity' => (int) $row['quantity']];
        }
        return $content;
    }
    /**
     * This method is allow to know if a feature is used or active
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function is_feature_active()
    {
        return Configuration::get('PS_PACK_FEATURE_ACTIVE');
    }
    /**
     * @param int $idProduct
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function no_pack_wholesale_price($id_product)
    {
        $sum = 0;
        $items = static::get_items($id_product, Configuration::get('PS_LANG_DEFAULT'));
        foreach ($items as $item) {
            $sum += $item->wholesale_price * $item->pack_quantity;
        }
        return $sum;
    }
    /**
     * @param int $idProduct
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function is_in_stock($id_product)
    {
        $items = static::get_items((int) $id_product, Configuration::get('PS_LANG_DEFAULT'));
        foreach ($items as $item) {
            if (Product::get_quantity($item->id) < $item->pack_quantity && !$item->is_available_when_out_of_stock((int) $item->out_of_stock)) {
                return false;
            }
        }
        return true;
    }
    /**
     * @param int $idProduct
     * @param int $idLang
     * @param bool $full
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_item_table($id_product, $id_lang, $full = false)
    {
        $id_product = (int) $id_product;
        if (!$id_product || !static::is_feature_active()) {
            return [];
        }
        $context = Context::get_context();
        $sql = 'SELECT p.*, product_shop.*, pl.*, image_shop.`id_image` id_image, il.`legend`, cl.`name` AS category_default, a.quantity AS pack_quantity, product_shop.`id_category_default`, a.id_product_pack, a.id_product_attribute_item
				FROM `' . _DB_PREFIX_ . 'pack` a
				LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.id_product = a.id_product_item
				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
					ON p.id_product = pl.id_product
					AND pl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $id_lang . ')
				' . Shop::add_sql_association('product', 'p') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
					ON product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('cl') . '
				WHERE product_shop.`id_shop` = ' . (int) $context->shop->id . '
				AND a.`id_product_pack` = ' . $id_product . '
				AND product_shop.active
				AND product_shop.visibility IN ("both", "catalog")
				GROUP BY a.`id_product_item`, a.`id_product_attribute_item`';
        $connection = Db::read_only();
        $result = $connection->get_array($sql);
        foreach ($result as &$line) {
            if (Combination::is_feature_active() && isset($line['id_product_attribute_item']) && $line['id_product_attribute_item']) {
                $line['cache_default_attribute'] = $line['id_product_attribute'] = $line['id_product_attribute_item'];
                $sql = 'SELECT agl.`name` AS group_name, al.`name` AS attribute_name,  pai.`id_image` AS id_product_attribute_image
				FROM `' . _DB_PREFIX_ . 'product_attribute` pa
				' . Shop::add_sql_association('product_attribute', 'pa') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON pac.`id_product_attribute` = ' . $line['id_product_attribute_item'] . '
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) Context::get_context()->language->id . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) Context::get_context()->language->id . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_image` pai ON (' . $line['id_product_attribute_item'] . ' = pai.`id_product_attribute`)
				WHERE pa.`id_product` = ' . (int) $line['id_product'] . ' AND pa.`id_product_attribute` = ' . $line['id_product_attribute_item'] . '
				GROUP BY pa.`id_product_attribute`, ag.`id_attribute_group`
				ORDER BY pa.`id_product_attribute`';
                $attr_name = $connection->get_array($sql);
                if (isset($attr_name[0]['id_product_attribute_image']) && $attr_name[0]['id_product_attribute_image']) {
                    $line['id_image'] = $attr_name[0]['id_product_attribute_image'];
                }
                $line['name'] .= "\n";
                foreach ($attr_name as $value) {
                    $line['name'] .= ' ' . $value['group_name'] . '-' . $value['attribute_name'];
                }
            }
            $line = Product::get_taxes_informations($line);
        }
        if (!$full) {
            return $result;
        }
        $array_result = [];
        foreach ($result as $prow) {
            if (!static::is_pack($prow['id_product'])) {
                $prow['id_product_attribute'] = (int) $prow['id_product_attribute_item'];
                $array_result[] = Product::get_product_properties($id_lang, $prow);
            }
        }
        return $array_result;
    }
    /**
     * Is product a pack?
     *
     * @param int $idProduct
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function is_pack($id_product)
    {
        return (bool) static::get_pack_content($id_product);
    }
    /**
     * @param int $idProduct
     * @param int $idLang
     * @param bool $full
     * @param int|null $limit
     *
     * @return array
     * @throws PrestaShopException
     */
    public static function get_packs_table($id_product, $id_lang, $full = false, $limit = null)
    {
        if (!static::is_feature_active()) {
            return [];
        }
        $connection = Db::read_only();
        $packs = $connection->get_value('
		SELECT GROUP_CONCAT(a.`id_product_pack`)
		FROM `' . _DB_PREFIX_ . 'pack` a
		WHERE a.`id_product_item` = ' . (int) $id_product);
        if (!(int) $packs) {
            return [];
        }
        $context = Context::get_context();
        $sql = '
		SELECT p.*, product_shop.*, pl.*, image_shop.`id_image` id_image, il.`legend`, IFNULL(product_attribute_shop.id_product_attribute, 0) id_product_attribute
		FROM `' . _DB_PREFIX_ . 'product` p
		NATURAL LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
		' . Shop::add_sql_association('product', 'p') . '
		LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop
	   		ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) $context->shop->id . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
			ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $id_lang . ')
		WHERE pl.`id_lang` = ' . (int) $id_lang . '
			' . Shop::add_sql_restriction_on_lang('pl') . '
			AND p.`id_product` IN (' . $packs . ')
		GROUP BY p.id_product';
        if ($limit) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        $result = $connection->get_array($sql);
        if (!$full) {
            return $result;
        }
        $array_result = [];
        foreach ($result as $row) {
            if (!static::is_packed($row['id_product'])) {
                $array_result[] = Product::get_product_properties($id_lang, $row);
            }
        }
        return $array_result;
    }
    /**
     * Is product in a pack?
     *
     * If $id_product_attribute specified, then will restrict search on the given combination,
     * else this method will match a product if at least one of all its combination is in a pack.
     *
     * @param int $idProduct
     * @param bool|int $idProductAttribute Optional combination of the product
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function is_packed($id_product, $id_product_attribute = false)
    {
        $id_product = (int) $id_product;
        if (!$id_product || !static::is_feature_active()) {
            return false;
        }
        $id_product_attribute = (int) $id_product_attribute;
        if ($id_product_attribute) {
            return (bool) static::get_item_quantities_in_packs($id_product, $id_product_attribute);
        }
        $cache_key = "Pack::isPacked({$id_product})";
        if (!Cache::is_stored($cache_key)) {
            Cache::store($cache_key, static::resolve_is_packed($id_product));
        }
        return (bool) Cache::retrieve($cache_key);
    }
    /**
     * Is product in a pack
     *
     * @param int $idProduct
     * @return boolean
     * @throws PrestaShopException
     */
    protected static function resolve_is_packed($id_product)
    {
        $id_product = (int) $id_product;
        $sql = (new Db_Query())->select('COUNT(1)')->from('pack')->where('id_product_item = ' . $id_product);
        return (bool) Db::read_only()->get_value($sql);
    }
    /**
     * @param int $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function delete_items($id_product)
    {
        $id_product = (int) $id_product;
        $conn = Db::get_instance();
        return $conn->update('product', ['cache_is_pack' => 0], 'id_product = ' . $id_product) && $conn->delete('pack', 'id_product_pack = ' . $id_product) && Configuration::update_global_value('PS_PACK_FEATURE_ACTIVE', static::is_currently_used());
    }
    /**
     * This method returns true, if at least one pack is defined
     *
     * @param string $table
     * @param bool $hasActiveColumn
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function is_currently_used($table = null, $has_active_column = false)
    {
        $sql = (new Db_Query())->select(1)->from('pack');
        return (bool) Db::read_only()->get_value($sql);
    }
    /**
     * Add an item to the pack
     *
     * @param int $idProduct
     * @param int $idItem
     * @param int $qty
     * @param int $idAttributeItem
     *
     * @return bool true if everything was fine
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function add_item($id_product, $id_item, $qty, $id_attribute_item = 0)
    {
        $id_attribute_item = (int) $id_attribute_item ?: Product::get_default_attribute((int) $id_item);
        $conn = Db::get_instance();
        return $conn->update('product', ['cache_is_pack' => 1], 'id_product = ' . (int) $id_product) && $conn->insert('pack', ['id_product_pack' => (int) $id_product, 'id_product_item' => (int) $id_item, 'id_product_attribute_item' => (int) $id_attribute_item, 'quantity' => (int) $qty]) && Configuration::update_global_value('PS_PACK_FEATURE_ACTIVE', '1');
    }
    /**
     * @param int $idProductOld
     * @param int $idProductNew
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function duplicate($id_product_old, $id_product_new)
    {
        Db::get_instance()->execute('INSERT INTO `' . _DB_PREFIX_ . 'pack` (`id_product_pack`, `id_product_item`, `id_product_attribute_item`, `quantity`)
		(SELECT ' . (int) $id_product_new . ', `id_product_item`, `id_product_attribute_item`, `quantity` FROM `' . _DB_PREFIX_ . 'pack` WHERE `id_product_pack` = ' . (int) $id_product_old . ')');
        // If return query result, a non-pack product will return false
        return true;
    }
    /**
     * For a given pack, tells if it has at least one product using the advanced stock management
     *
     * @param int $idProduct id_pack
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function uses_advanced_stock_management($id_product)
    {
        $products = static::get_items($id_product, Configuration::get('PS_LANG_DEFAULT'));
        foreach ($products as $product) {
            // if one product uses the advanced stock management
            if ($product->advanced_stock_management == 1) {
                return true;
            }
        }
        // not used
        return false;
    }
    /**
     * For a given pack, tells if all products using the advanced stock management
     *
     * @param int $idProduct id_pack
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function all_uses_advanced_stock_management($id_product)
    {
        if (!static::is_pack($id_product)) {
            return false;
        }
        $products = static::get_items($id_product, Configuration::get('PS_LANG_DEFAULT'));
        foreach ($products as $product) {
            // if one product uses the advanced stock management
            if ($product->advanced_stock_management == 0) {
                return false;
            }
        }
        // not used
        return true;
    }
    /**
     * Returns Packs that contains the given product in the right declinaison.
     *
     * @param integer $idItem Product item id that could be contained in a|many pack(s)
     * @param integer $idAttributeItem The declinaison of the product
     * @param integer $idLang
     *
     * @return Product[] Packs that contains the given product
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_packs_containing_item($id_item, $id_attribute_item, $id_lang)
    {
        $array_result = [];
        foreach (static::get_item_quantities_in_packs($id_item, $id_attribute_item) as $pack_id => $item_quantity) {
            $pack = new Product($pack_id, true, $id_lang);
            $pack->load_stock_data();
            // Specific need from StockAvailable::updateQuantity()
            $pack->pack_item_quantity = $item_quantity;
            $array_result[] = $pack;
        }
        return $array_result;
    }
    /**
     * Returns information about all packs $idItem is part of, and item quantity
     *
     * @param int $idItem
     * @param int $idAttributeItem
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_item_quantities_in_packs($id_item, $id_attribute_item)
    {
        $id_item = (int) $id_item;
        if (!$id_item || !static::is_feature_active()) {
            return [];
        }
        $id_attribute_item = (int) $id_attribute_item;
        $cache_key = "Pack::getItemQuantitiesInPacks({$id_item},{$id_attribute_item})";
        if (!Cache::is_stored($cache_key)) {
            Cache::store($cache_key, static::resolve_item_quantities_in_packs($id_item, $id_attribute_item));
        }
        return Cache::retrieve($cache_key);
    }
    /**
     * Returns information about all packs $idItem is part of, and item quantity
     *
     * @param int $idItem
     * @param int $idAttributeItem
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function resolve_item_quantities_in_packs($id_item, $id_attribute_item)
    {
        $id_item = (int) $id_item;
        $id_attribute_item = (int) $id_attribute_item;
        $query = (new Db_Query())->select('p.id_product_pack')->select('p.quantity')->from('pack', 'p')->inner_join('product', 'prod', 'prod.id_product = p.id_product_pack')->where("p.id_product_item = {$id_item}")->where("p.id_product_attribute_item = {$id_attribute_item}");
        $result = Db::read_only()->get_array($query);
        $ret = [];
        foreach ($result as $row) {
            $pack_id = (int) $row['id_product_pack'];
            $quantity = (int) $row['quantity'];
            $ret[$pack_id] = $quantity;
        }
        return $ret;
    }
    /**
     * Returns true, if $stockType value is one of the three allowed settings
     *   - STOCK_TYPE_DECREMENT_PACK,
     *   - STOCK_TYPE_DECREMENT_PRODUCTS
     *   - STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS
     * returns false for anything else, even STOCK_TYPE_DECREMENT_GLOBAL_SETTINGS
     *
     * @param int $stockType
     * @return boolean
     */
    public static function is_valid_stock_type($stock_type)
    {
        $stock_type = (int) $stock_type;
        return $stock_type === static::STOCK_TYPE_DECREMENT_PACK || $stock_type === static::STOCK_TYPE_DECREMENT_PRODUCTS || $stock_type === static::STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS;
    }
    /**
     * Returns public configuration for pack quantity adjustment
     *
     * @return int
     */
    public static function get_global_stock_type_settings()
    {
        try {
            $stock_type = (int) Configuration::get(Configuration::PACK_STOCK_TYPE);
            if (static::is_valid_stock_type($stock_type)) {
                return $stock_type;
            }
        } catch (Exception) {
        }
        return static::STOCK_TYPE_DECREMENT_PACK;
    }
    /**
     * Returns ids of dynamic packs products
     *
     * @return int[]
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_dynamic_packs()
    {
        $sql = (new Db_Query())->select('DISTINCT id_product')->from('product_shop')->where('pack_dynamic');
        $conn = Db::read_only();
        $result = $conn->get_array($sql);
        return array_map(intval(...), array_column($result, 'id_product'));
    }
}