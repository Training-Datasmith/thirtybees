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
/**
 * Class HelperShopCore
 */
class Helper_Shop_Core extends Helper
{
    /**
     * Render shop list
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function get_rendered_shop_list()
    {
        if (!Shop::is_feature_active() || Shop::get_total_shops(false) < 2) {
            return '';
        }
        $shop_context = Shop::get_context();
        $tree = Shop::get_tree();
        $controller = $this->get_controller();
        if ($shop_context == Shop::CONTEXT_ALL || $controller->multishop_context_group == false && $shop_context == Shop::CONTEXT_GROUP) {
            $current_shop_value = '';
            $current_shop_name = Translate::get_admin_translation('All shops');
        } elseif ($shop_context == Shop::CONTEXT_GROUP) {
            $current_shop_value = 'g-' . Shop::get_context_shop_group_id();
            $current_shop_name = sprintf(Translate::get_admin_translation('%s group'), $tree[Shop::get_context_shop_group_id()]['name']);
        } else {
            $current_shop_value = 's-' . Shop::get_context_shop_id();
            $current_shop_name = '';
            foreach ($tree as $group_data) {
                foreach ($group_data['shops'] as $shop_id => $shop_data) {
                    if ($shop_id == Shop::get_context_shop_id()) {
                        $current_shop_name = $shop_data['name'];
                        break;
                    }
                }
            }
        }
        $tpl = $this->create_template('helpers/shops_list/list.tpl');
        $tpl->assign(['tree' => $tree, 'current_shop_name' => $current_shop_name, 'current_shop_value' => $current_shop_value, 'multishop_context' => $controller->multishop_context, 'multishop_context_group' => $controller->multishop_context_group, 'is_shop_context' => $controller->multishop_context & Shop::CONTEXT_SHOP, 'is_group_context' => $controller->multishop_context & Shop::CONTEXT_GROUP, 'shop_context' => $shop_context, 'url' => $_SERVER['REQUEST_URI'] . ($_SERVER['QUERY_STRING'] ? '&' : '?') . 'setShopContext=']);
        return $tpl->fetch();
    }
}