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
 * Class StockManagerFactoryCore
 */
class Stock_Manager_Factory_Core
{
    /** @var StockManager instance of the current StockManager. */
    protected static $stock_manager;
    /**
     * Returns a StockManager
     *
     * @return StockManagerInterface
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_manager()
    {
        if (!isset(Stock_Manager_Factory::$stock_manager)) {
            $stock_manager = Stock_Manager_Factory::exec_hook_stock_manager_factory();
            if (!$stock_manager instanceof Stock_Manager_Interface) {
                $stock_manager = new Stock_Manager();
            }
            Stock_Manager_Factory::$stock_manager = $stock_manager;
        }
        return Stock_Manager_Factory::$stock_manager;
    }
    /**
     * Looks for a StockManager in the modules list.
     *
     * @return StockManagerInterface
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function exec_hook_stock_manager_factory()
    {
        $modules_infos = Hook::get_modules_from_hook(Hook::get_id_by_name('stockManager'));
        $stock_manager = false;
        foreach ($modules_infos as $module_infos) {
            $module_instance = Module::get_instance_by_name($module_infos['name']);
            if (is_callable([$module_instance, 'hookStockManager'])) {
                $stock_manager = $module_instance->hook_stock_manager();
            }
            if ($stock_manager) {
                break;
            }
        }
        return $stock_manager;
    }
}