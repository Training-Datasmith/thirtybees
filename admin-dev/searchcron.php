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
if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}
include _PS_ADMIN_DIR_ . '/../config/config.inc.php';
if (!Tools::get_int_value('id_shop')) {
    Context::get_context()->shop->set_context(Shop::CONTEXT_ALL);
} else {
    Context::get_context()->shop->set_context(Shop::CONTEXT_SHOP, Tools::get_int_value('id_shop'));
}
if (substr(_COOKIE_KEY_, 34, 8) != Tools::get_value('token')) {
    die;
}
ini_set('max_execution_time', 7200);
Search::indexation(Tools::get_bool_value('full'));
if (Tools::get_value('redirect') && Tools::get_http_referer()) {
    Tools::redirect_admin(Tools::get_http_referer() . '&conf=4');
}