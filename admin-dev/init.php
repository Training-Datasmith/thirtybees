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
ob_start();
$timer_start = microtime(true);
//	$_GET['tab'] = $_GET['controller'];
//	$_POST['tab'] = $_POST['controller'];
//	$_REQUEST['tab'] = $_REQUEST['controller'];
$context = Context::get_context();
if (isset($_GET['logout'])) {
    $context->employee->logout();
}
if (!isset($context->employee) || !$context->employee->is_logged_back()) {
    Tools::redirect_admin('index.php?controller=AdminLogin&redirect=' . $_SERVER['REQUEST_URI']);
}
// Set current index
// @deprecated global
global $current_index;
// retrocompatibility;
$current_index = $_SERVER['SCRIPT_NAME'] . (($controller = Tools::get_value('controller')) ? '?controller=' . $controller : '');
if ($back = Tools::get_value('back')) {
    $current_index .= '&back=' . urlencode($back);
}
Admin_Tab::$current_index = $current_index;
$iso = $context->language->iso_code;
if (file_exists(_PS_TRANSLATIONS_DIR_ . $iso . '/errors.php')) {
    include _PS_TRANSLATIONS_DIR_ . $iso . '/errors.php';
}
if (file_exists(_PS_TRANSLATIONS_DIR_ . $iso . '/fields.php')) {
    include _PS_TRANSLATIONS_DIR_ . $iso . '/fields.php';
}
if (file_exists(_PS_TRANSLATIONS_DIR_ . $iso . '/admin.php')) {
    include _PS_TRANSLATIONS_DIR_ . $iso . '/admin.php';
}
/* Server Params */
$protocol_link = Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://';
$protocol_content = (isset($use_ssl) and $use_ssl and Configuration::get('PS_SSL_ENABLED')) ? 'https://' : 'http://';
$link = new Link($protocol_link, $protocol_content);
$context->link = $link;
if (!defined('_PS_BASE_URL_')) {
    define('_PS_BASE_URL_', Tools::get_shop_domain(true));
}
if (!defined('_PS_BASE_URL_SSL_')) {
    define('_PS_BASE_URL_SSL_', Tools::get_shop_domain_ssl(true));
}
$path = dirname(__FILE__) . '/themes/';
// if the current employee theme is not valid (check layout.tpl presence),
// reset to default theme
if (empty($context->employee->bo_theme) || !file_exists($path . $context->employee->bo_theme . '/template/layout.tpl')) {
    // default admin theme is "default".
    $context->employee->bo_theme = '';
    if (file_exists($path . 'default/template/layout.tpl')) {
        $context->employee->bo_theme = 'default';
    } else {
        // if default theme doesn't exists, try to find one, otherwise throw exception
        foreach (scandir($path) as $theme) {
            if ($theme[0] != '.' && file_exists($path . $theme . '/template/layout.tpl')) {
                $context->employee->bo_theme = $theme;
                break;
            }
        }
        // if no theme is found, admin can't work.
        if (empty($context->employee->bo_theme)) {
            throw new Presta_Shop_Exception('Unable to load theme for employee, and no valid theme found');
        }
    }
    $context->employee->update();
}
// Change shop context ?
if (Shop::is_feature_active() && Tools::get_value('setShopContext') !== false) {
    $context->cookie->shop_context = Tools::get_value('setShopContext');
    $url = parse_url($_SERVER['REQUEST_URI']);
    $query = isset($url['query']) ? $url['query'] : '';
    parse_str($query, $parse_query);
    unset($parse_query['setShopContext']);
    Tools::redirect_admin($url['path'] . '?' . http_build_query($parse_query, '', '&'));
}
$context->currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
if ($context->employee->is_logged_back()) {
    $shop_id = '';
    Shop::set_context(Shop::CONTEXT_ALL);
    if ($context->cookie->shop_context) {
        $split = explode('-', $context->cookie->shop_context);
        if (count($split) == 2) {
            if ($split[0] == 'g') {
                if ($context->employee->has_auth_on_shop_group($split[1])) {
                    Shop::set_context(Shop::CONTEXT_GROUP, $split[1]);
                } else {
                    $shop_id = $context->employee->get_default_shop_id();
                    Shop::set_context(Shop::CONTEXT_SHOP, $shop_id);
                }
            } elseif ($context->employee->has_auth_on_shop($split[1])) {
                $shop_id = $split[1];
                Shop::set_context(Shop::CONTEXT_SHOP, $shop_id);
            } else {
                $shop_id = $context->employee->get_default_shop_id();
                Shop::set_context(Shop::CONTEXT_SHOP, $shop_id);
            }
        }
    }
    // Replace existing shop if necessary
    if (!$shop_id) {
        $context->shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
    } elseif ($context->shop->id != $shop_id) {
        $context->shop = new Shop($shop_id);
    }
}