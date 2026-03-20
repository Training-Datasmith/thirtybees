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
/** @noinspection PhpUnhandledExceptionInspection */
if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}
include_once _PS_ADMIN_DIR_ . '/../config/config.inc.php';
$module = Tools::get_value('module');
$type = Tools::get_value('type');
$option = Tools::get_value('option');
$width = Tools::get_int_value('width', 600);
$height = Tools::get_int_value('height', 920);
$start = Tools::get_int_value('start', 0);
$limit = Tools::get_int_value('limit', 40);
$sort = Tools::get_value('sort', 0);
// Should be a String. Default value is an Integer because we don't know what can be the name of the column to sort.
$dir = Tools::get_value('dir', 0);
// Should be a String : Either ASC or DESC
$id_employee = Tools::get_int_value('id_employee');
$id_lang = Tools::get_int_value('id_lang');
if (!isset($cookie->id_employee) || !$cookie->id_employee || $cookie->id_employee != $id_employee) {
    throw new Presta_Shop_Exception(Tools::display_error('Employee not validated'));
}
if (!Validate::is_module_name($module)) {
    throw new Presta_Shop_Exception(sprintf(Tools::display_error('Invalid module name [%s]'), Tools::safe_output($module)));
}
/** @var StatsModule $statsModuleInstance */
$stats_module_instance = Module::get_instance_by_name('statsmodule');
if ($stats_module_instance->active && in_array($module, $stats_module_instance->modules)) {
    $module_path = _PS_ROOT_DIR_ . '/modules/statsmodule/stats/' . $module . '.php';
} else if (!file_exists($module_path = _PS_ROOT_DIR_ . '/modules/' . $module . '/' . $module . '.php')) {
    throw new Presta_Shop_Exception(sprintf(Tools::display_error('Module [%s] not found'), Tools::safe_output($module)));
}
$shop_id = '';
Shop::set_context(Shop::CONTEXT_ALL);
if (Context::get_context()->cookie->shop_context) {
    $split = explode('-', Context::get_context()->cookie->shop_context);
    if (count($split) == 2) {
        if ($split[0] == 'g') {
            if (Context::get_context()->employee->has_auth_on_shop_group($split[1])) {
                Shop::set_context(Shop::CONTEXT_GROUP, $split[1]);
            } else {
                $shop_id = Context::get_context()->employee->get_default_shop_id();
                Shop::set_context(Shop::CONTEXT_SHOP, $shop_id);
            }
        } elseif (Shop::get_shop($split[1]) && Context::get_context()->employee->has_auth_on_shop($split[1])) {
            $shop_id = $split[1];
            Shop::set_context(Shop::CONTEXT_SHOP, $shop_id);
        } else {
            $shop_id = Context::get_context()->employee->get_default_shop_id();
            Shop::set_context(Shop::CONTEXT_SHOP, $shop_id);
        }
    }
}
// Check multishop context and set right context if need
if (Shop::get_context()) {
    if (Shop::get_context() == Shop::CONTEXT_SHOP && !Shop::CONTEXT_SHOP) {
        Shop::set_context(Shop::CONTEXT_GROUP, Shop::get_context_shop_group_id());
    }
    if (Shop::get_context() == Shop::CONTEXT_GROUP && !Shop::CONTEXT_GROUP) {
        Shop::set_context(Shop::CONTEXT_ALL);
    }
}
// Replace existing shop if necessary
if (!$shop_id) {
    Context::get_context()->shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
} elseif (Context::get_context()->shop->id != $shop_id) {
    Context::get_context()->shop = new Shop($shop_id);
}
require_once $module_path;
/** @var StatsModule $grid */
$grid = new $module();
$grid->set_employee($id_employee);
$grid->set_lang($id_lang);
if ($option) {
    $grid->set_option($option);
}
$grid->create_grid(null, $type, $width, $height, $start, $limit, $sort, $dir);
$grid->render();