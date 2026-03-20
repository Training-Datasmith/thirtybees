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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */
/** @noinspection PhpUnhandledExceptionInspection */
use Thirtybees\Core\Dependency_Injection\Service_Locator;
use Thirtybees\Core\Error\Response\J_Send_Error_Response;
if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}
include _PS_ADMIN_DIR_ . '/../config/config.inc.php';
/* Getting cookie or logout */
require_once _PS_ADMIN_DIR_ . '/init.php';
Service_Locator::get_instance()->get_error_handler()->set_error_response_handler(new J_Send_Error_Response(_PS_MODE_DEV_));
$query = Tools::get_value('q', false);
if (!$query or $query == '' or strlen($query) < 1) {
    exit;
}
/*
 * In the SQL request the "q" param is used entirely to match result in database.
 * In this way if string:"(ref : #ref_pattern#)" is displayed on the return list,
 * they are no return values just because string:"(ref : #ref_pattern#)"
 * is not write in the name field of the product.
 * So the ref pattern will be cut for the search request.
 */
if ($pos = strpos($query, ' (ref:')) {
    $query = substr($query, 0, $pos);
}
$exclude_ids = Tools::get_value('excludeIds', false);
$return_type = Tools::get_value('returnType');
if ($exclude_ids && $exclude_ids != 'NaN') {
    $exclude_ids = implode(',', array_map('intval', explode(',', $exclude_ids)));
} else {
    $exclude_ids = '';
    $exclude_pack_itself = Tools::get_value('packItself', false);
}
// Excluding downloadable products from packs because download from pack is not supported
$exclude_virtuals = Tools::get_bool_value('excludeVirtuals', true);
$exclude_packs = Tools::get_bool_value('exclude_packs', true);
$context = Context::get_context();
$sql = 'SELECT p.`id_product`, pl.`link_rewrite`, p.`reference`, pl.`name`, image_shop.`id_image` id_image, il.`legend`, p.`cache_default_attribute`
		FROM `' . _DB_PREFIX_ . 'product` p
		' . Shop::add_sql_association('product', 'p') . '
		LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (pl.id_product = p.id_product AND pl.id_lang = ' . (int) $context->language->id . Shop::add_sql_restriction_on_lang('pl') . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
			ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $context->language->id . ')
		WHERE (pl.name LIKE \'%' . p_sql($query) . '%\' OR p.reference LIKE \'%' . p_sql($query) . '%\')' . (!empty($exclude_ids) ? ' AND p.id_product NOT IN (' . $exclude_ids . ') ' : ' ') . (!empty($exclude_pack_itself) ? ' AND p.id_product <> ' . $exclude_pack_itself . ' ' : ' ') . ($exclude_virtuals ? 'AND NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'product_download` pd WHERE (pd.id_product = p.id_product))' : '') . ($exclude_packs ? 'AND (p.cache_is_pack IS NULL OR p.cache_is_pack = 0)' : '') . ' GROUP BY p.id_product';
$conn = Db::read_only();
$items = $conn->get_array($sql);
if ($return_type !== 'ajax' && ($return_type === 'list' || $items && ($exclude_ids || strpos(Tools::get_http_referer(), 'AdminScenes') !== false))) {
    foreach ($items as $item) {
        echo trim($item['name']) . (!empty($item['reference']) ? ' (ref: ' . $item['reference'] . ')' : '') . '|' . (int) $item['id_product'] . "\n";
    }
} elseif ($items) {
    // packs
    $results = [];
    foreach ($items as $item) {
        // check if product have combination
        if (Combination::is_feature_active() && $item['cache_default_attribute']) {
            $sql = 'SELECT pa.`id_product_attribute`, pa.`reference`, ag.`id_attribute_group`, pai.`id_image`, agl.`name` AS group_name, al.`name` AS attribute_name,
						a.`id_attribute`
					FROM `' . _DB_PREFIX_ . 'product_attribute` pa
					' . Shop::add_sql_association('product_attribute', 'pa') . '
					LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
					LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
					LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
					LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $context->language->id . ')
					LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $context->language->id . ')
					LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_image` pai ON pai.`id_product_attribute` = pa.`id_product_attribute`
					WHERE pa.`id_product` = ' . (int) $item['id_product'] . '
					GROUP BY pa.`id_product_attribute`, ag.`id_attribute_group`
					ORDER BY pa.`id_product_attribute`';
            $combinations = $conn->get_array($sql);
            if (!empty($combinations)) {
                foreach ($combinations as $k => $combination) {
                    $results[$combination['id_product_attribute']]['id'] = $item['id_product'];
                    $results[$combination['id_product_attribute']]['id_product_attribute'] = $combination['id_product_attribute'];
                    !empty($results[$combination['id_product_attribute']]['name']) ? $results[$combination['id_product_attribute']]['name'] .= ' ' . $combination['group_name'] . '-' . $combination['attribute_name'] : $results[$combination['id_product_attribute']]['name'] = $item['name'] . ' ' . $combination['group_name'] . '-' . $combination['attribute_name'];
                    if (!empty($combination['reference'])) {
                        $results[$combination['id_product_attribute']]['ref'] = $combination['reference'];
                    } else {
                        $results[$combination['id_product_attribute']]['ref'] = !empty($item['reference']) ? $item['reference'] : '';
                    }
                    if (empty($results[$combination['id_product_attribute']]['image'])) {
                        $results[$combination['id_product_attribute']]['image'] = str_replace('http://', Tools::get_shop_protocol(), $context->link->get_image_link($item['link_rewrite'], $combination['id_image'], 'home_default'));
                    }
                }
            } else {
                $product = ['id' => (int) $item['id_product'], 'name' => $item['name'], 'ref' => !empty($item['reference']) ? $item['reference'] : '', 'image' => str_replace('http://', Tools::get_shop_protocol(), $context->link->get_image_link($item['link_rewrite'], $item['id_image'], 'home_default'))];
                $results[] = $product;
            }
        } else {
            $product = ['id' => (int) $item['id_product'], 'name' => $item['name'], 'ref' => !empty($item['reference']) ? $item['reference'] : '', 'image' => str_replace('http://', Tools::get_shop_protocol(), $context->link->get_image_link($item['link_rewrite'], $item['id_image'], 'home_default'))];
            $results[] = $product;
        }
    }
    $results = array_values($results);
    echo json_encode($results);
} else {
    json_encode(new stdClass());
}