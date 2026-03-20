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
 * Class ProductSaleCore
 */
class Product_Sale_Core
{
    /**
     * Fill the `product_sale` SQL table with data from `order_detail`
     *
     * @return bool True on success
     *
     * @throws PrestaShopException
     */
    public static function fill_product_sales()
    {
        $sql = 'REPLACE INTO ' . _DB_PREFIX_ . 'product_sale
				(`id_product`, `quantity`, `sale_nbr`, `date_upd`)
				SELECT od.product_id, SUM(od.product_quantity), COUNT(od.product_id), NOW()
							FROM ' . _DB_PREFIX_ . 'order_detail od GROUP BY od.product_id';
        return Db::get_instance()->execute($sql);
    }
    /**
     * Get number of actives products sold
     *
     * @return int number of actives products listed in product_sales
     *
     * @throws PrestaShopException
     */
    public static function get_nb_sales(): int
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('COUNT(ps.`id_product`) AS `nb`')->from('product_sale', 'ps')->left_join('product', 'p', 'p.`id_product` = ps.`id_product`')->join(Shop::add_sql_association('product', 'p'))->where('product_shop.`active` = 1'));
    }
    /**
     * Get required informations on best sales products
     *
     * @param int $idLang Language id
     * @param int $pageNumber Start from (optional)
     * @param int $nbProducts Number of products to return (optional)
     * @param string|null $orderBy
     * @param string|null $orderWay
     *
     * @return false| array from Product::getProductProperties
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_best_sales($id_lang, $page_number = 0, $nb_products = 10, $order_by = null, $order_way = null)
    {
        $context = Context::get_context();
        if ($page_number < 0) {
            $page_number = 0;
        }
        if ($nb_products < 1) {
            $nb_products = 10;
        }
        $order_table = '';
        if (is_null($order_by)) {
            $order_by = 'quantity';
            $order_table = 'ps';
        }
        if ($order_by == 'date_add' || $order_by == 'date_upd') {
            $order_table = 'product_shop';
        }
        if (is_null($order_way) || $order_by == 'sales') {
            $order_way = 'DESC';
        }
        $interval = Validate::is_unsigned_int(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20;
        // no group by needed : there's only one attribute with default_on=1 for a given id_product + shop
        // same for image with cover=1
        $sql = (new Db_Query())->select('p.*, product_shop.*, stock.`out_of_stock`, IFNULL(stock.quantity, 0) as quantity')->select(Combination::is_feature_active() ? 'product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute' : '')->select('pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`')->select('pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`')->select('m.`name` AS manufacturer_name, p.`id_manufacturer` as id_manufacturer')->select('image_shop.`id_image` id_image, il.`legend`')->select('ps.`quantity` AS sales, t.`rate`, pl.`meta_keywords`, pl.`meta_title`, pl.`meta_description`')->select('DATEDIFF(p.`date_add`, DATE_SUB("' . date('Y-m-d') . ' 00:00:00"')->select('INTERVAL ' . (int) $interval . ' DAY)) > 0 AS new')->from('product_sale', 'ps')->left_join('product', 'p', 'ps.`id_product` = p.`id_product`')->join(Shop::add_sql_association('product', 'p', false))->join(Combination::is_feature_active() ? 'LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) $context->shop->id . ')' : '')->left_join('product_lang', 'pl', 'p.`id_product` = pl.`id_product`')->left_join('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.`cover` = 1 AND image_shop.`id_shop` = ' . (int) $context->shop->id)->left_join('image_lang', 'il', 'image_shop.`id_image` = il.`id_image`')->left_join('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`')->left_join('tax_rule', 'tr', 'product_shop.`id_tax_rules_group` = tr.`id_tax_rules_group` AND tr.`id_country` = ' . (int) $context->country->id . ' AND tr.`id_state` = 0')->left_join('tax', 't', 't.`id_tax` = tr.`id_tax` ' . Product::sql_stock('p', 0))->where('pl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl'))->where('il.`id_lang` = ' . (int) $id_lang)->where('product_shop.`active` = 1')->where('p.`visibility` != \'none\'')->where('EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` ' . (count(Front_Controller::get_current_customer_groups()) ? 'IN (' . implode(',', Front_Controller::get_current_customer_groups()) . ')' : '= 1') . ') WHERE cp.`id_product` = p.`id_product`)');
        if ($order_by !== 'price' && $order_by !== 'position') {
            $sql->order_by((!empty($order_table) ? '`' . p_sql($order_table) . '`.' : '') . '`' . p_sql($order_by) . '` ' . p_sql($order_way));
            $sql->limit((int) $nb_products, (int) ($page_number * $nb_products));
        }
        $result = Db::read_only()->get_array($sql);
        if ($order_by === 'price') {
            Tools::orderby_price($result, $order_way);
        }
        if (!$result) {
            return false;
        }
        return Product::get_products_properties($id_lang, $result);
    }
    /**
     * Get required informations on best sales products
     *
     * @param int $idLang Language id
     * @param int $pageNumber Start from (optional)
     * @param int $nbProducts Number of products to return (optional)
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_best_sales_light($id_lang, $page_number = 0, $nb_products = 10, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        if ($page_number < 0) {
            $page_number = 0;
        }
        if ($nb_products < 1) {
            $nb_products = 10;
        }
        // no group by needed : there's only one attribute with default_on=1 for a given id_product + shop
        // same for image with cover=1
        $sql = '
		SELECT
			p.id_product, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute, pl.`link_rewrite`, pl.`name`, pl.`description_short`, product_shop.`id_category_default`,
			image_shop.`id_image` id_image, il.`legend`,
			ps.`quantity` AS sales, p.`ean13`, p.`upc`, cl.`link_rewrite` AS category, p.show_price, p.available_for_order, IFNULL(stock.quantity, 0) as quantity, p.customizable,
			IFNULL(pa.minimal_quantity, p.minimal_quantity) as minimal_quantity, stock.out_of_stock,
			product_shop.`date_add` > "' . date('Y-m-d', strtotime('-' . (Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY')) . '" as new,
			product_shop.`on_sale`, product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity
		FROM `' . _DB_PREFIX_ . 'product_sale` ps
		LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON ps.`id_product` = p.`id_product`
		' . Shop::add_sql_association('product', 'p') . '
		LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop
			ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) $context->shop->id . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON (product_attribute_shop.id_product_attribute=pa.id_product_attribute)
		LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
			ON p.`id_product` = pl.`id_product`
			AND pl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl') . '
		LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
			ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $id_lang . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
			ON cl.`id_category` = product_shop.`id_category_default`
			AND cl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('cl') . Product::sql_stock('p', 0);
        $sql .= '
		WHERE product_shop.`active` = 1
		AND p.`visibility` != \'none\'';
        if (Group::is_feature_active()) {
            $groups = Front_Controller::get_current_customer_groups();
            $sql .= ' AND EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp
				JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1') . ')
				WHERE cp.`id_product` = p.`id_product`)';
        }
        $sql .= '
		ORDER BY ps.quantity DESC
		LIMIT ' . (int) ($page_number * $nb_products) . ', ' . (int) $nb_products;
        if (!$result = Db::read_only()->get_array($sql)) {
            return false;
        }
        return Product::get_products_properties($id_lang, $result);
    }
    /**
     * @param int $idProduct
     * @param int $qty
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function add_product_sale($id_product, $qty = 1)
    {
        return Db::get_instance()->execute('
			INSERT INTO ' . _DB_PREFIX_ . 'product_sale
			(`id_product`, `quantity`, `sale_nbr`, `date_upd`)
			VALUES (' . (int) $id_product . ', ' . (int) $qty . ', 1, NOW())
			ON DUPLICATE KEY UPDATE `quantity` = `quantity` + ' . (int) $qty . ', `sale_nbr` = `sale_nbr` + 1, `date_upd` = NOW()');
    }
    /**
     * @param int $idProduct
     * @param int $qty
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function remove_product_sale($id_product, $qty = 1)
    {
        $total_sales = Product_Sale::get_nbr_sales($id_product);
        $conn = Db::get_instance();
        if ($total_sales > 1) {
            return $conn->execute('
				UPDATE ' . _DB_PREFIX_ . 'product_sale
				SET `quantity` = CAST(`quantity` AS SIGNED) - ' . (int) $qty . ', `sale_nbr` = CAST(`sale_nbr` AS SIGNED) - 1, `date_upd` = NOW()
				WHERE `id_product` = ' . (int) $id_product);
        }
        if ($total_sales == 1) {
            return $conn->delete('product_sale', 'id_product = ' . (int) $id_product);
        }
        return true;
    }
    /**
     * @param int $idProduct
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_nbr_sales($id_product): int
    {
        $result = Db::read_only()->get_row('SELECT `sale_nbr` FROM ' . _DB_PREFIX_ . 'product_sale WHERE `id_product` = ' . (int) $id_product);
        if (empty($result) || !array_key_exists('sale_nbr', $result)) {
            return -1;
        }
        return (int) $result['sale_nbr'];
    }
}