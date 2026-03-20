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
$context = Context::get_context();
Service_Locator::get_instance()->get_error_handler()->set_error_response_handler(new J_Send_Error_Response(_PS_MODE_DEV_));
$conn = Db::read_only();
if (Tools::is_submit('ajaxReferrers')) {
    if (Tools::is_submit('ajaxProductFilter')) {
        Referrer::get_ajax_product(Tools::get_int_value('id_referrer'), Tools::get_int_value('id_product'), new Employee(Tools::get_int_value('id_employee')));
    } else if (Tools::is_submit('ajaxFillProducts')) {
        $json_array = [];
        $result = $conn->get_array('
			SELECT p.id_product, pl.name
			FROM ' . _DB_PREFIX_ . 'product p
			LEFT JOIN ' . _DB_PREFIX_ . 'product_lang pl
				ON (p.id_product = pl.id_product AND pl.id_lang = ' . Tools::get_int_value('id_lang') . ')
			' . (Tools::get_value('filter') != 'undefined' ? 'WHERE name LIKE "%' . p_sql(Tools::get_value('filter')) . '%"' : ''));
        foreach ($result as $row) {
            $json_array[] = '{id_product:' . (int) $row['id_product'] . ',name:\'' . addslashes($row['name']) . '\'}';
        }
        die('[' . implode(',', $json_array) . ']');
    }
}
if (Tools::is_submit('getAvailableFields') and Tools::is_submit('entity')) {
    $json_array = [];
    $import = new Admin_Import_Controller();
    $fields = $import->get_available_fields(true);
    foreach ($fields as $field) {
        $json_array[] = '{"field":"' . addslashes($field) . '"}';
    }
    die('[' . implode(',', $json_array) . ']');
}
if (Tools::is_submit('ajaxProductPackItems')) {
    $json_array = [];
    $products = $conn->get_array('
	SELECT p.`id_product`, pl.`name`
	FROM `' . _DB_PREFIX_ . 'product` p
	NATURAL LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
	WHERE pl.`id_lang` = ' . Tools::get_int_value('id_lang') . '
	' . Shop::add_sql_restriction_on_lang('pl') . '
	AND NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'pack` WHERE `id_product_pack` = p.`id_product`)
	AND p.`id_product` != ' . Tools::get_int_value('id_product'));
    foreach ($products as $pack_item) {
        $json_array[] = '{"value": "' . (int) $pack_item['id_product'] . '-' . addslashes($pack_item['name']) . '", "text":"' . (int) $pack_item['id_product'] . ' - ' . addslashes($pack_item['name']) . '"}';
    }
    die('[' . implode(',', $json_array) . ']');
}
if (Tools::is_submit('getChildrenCategories') && Tools::is_submit('id_category_parent')) {
    $children_categories = Category::get_children_with_nb_selected_sub_cat(Tools::get_int_value('id_category_parent'), Tools::get_value('selectedCat'), Context::get_context()->language->id, null, Tools::get_value('use_shop_context'));
    die(json_encode($children_categories));
}
if (Tools::is_submit('getNotifications')) {
    Shop_Maintenance::run();
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    $notification = $context->employee->get_notification();
    die(json_encode($notification->get_notifications()));
}
if (Tools::is_submit('markNotificationsRead')) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    $notification = $context->employee->get_notification();
    $type = Tools::get_value('type');
    $last_id = Tools::get_int_value('lastId');
    die(json_encode(['success' => $notification->mark_as_read($type, $last_id)]));
}
if (Tools::is_submit('searchCategory')) {
    $q = Tools::get_value('q');
    $limit = Tools::get_value('limit');
    $results = $conn->get_array('SELECT c.`id_category`, cl.`name`
		FROM `' . _DB_PREFIX_ . 'category` c
		LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (c.`id_category` = cl.`id_category`' . Shop::add_sql_restriction_on_lang('cl') . ')
		WHERE cl.`id_lang` = ' . (int) $context->language->id . ' AND c.`level_depth` <> 0
		AND cl.`name` LIKE \'%' . p_sql($q) . '%\'
		GROUP BY c.id_category
		ORDER BY c.`position`
		LIMIT ' . (int) $limit);
    if ($results) {
        foreach ($results as $result) {
            echo trim($result['name']) . '|' . (int) $result['id_category'] . "\n";
        }
    }
}
if (Tools::is_submit('getParentCategoriesId') && $id_category = Tools::get_int_value('id_category')) {
    $category = new Category((int) $id_category);
    $results = $conn->get_array('SELECT `id_category` FROM `' . _DB_PREFIX_ . 'category` c WHERE c.`nleft` < ' . (int) $category->nleft . ' AND c.`nright` > ' . (int) $category->nright);
    $output = [];
    foreach ($results as $result) {
        $output[] = $result;
    }
    die(json_encode($output));
}
if (Tools::is_submit('getZones')) {
    $html = '<select id="zone_to_affect" name="zone_to_affect">';
    foreach (Zone::get_zones() as $z) {
        $html .= '<option value="' . $z['id_zone'] . '">' . $z['name'] . '</option>';
    }
    $html .= '</select>';
    $array = ['hasError' => false, 'errors' => '', 'data' => $html];
    die(json_encode($array));
}
if (Tools::is_submit('getEmailHTML') && $email = Tools::get_value('email')) {
    $email_html = Admin_Translations_Controller::get_email_html($email);
    die($email_html);
}