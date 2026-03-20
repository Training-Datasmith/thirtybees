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
 * Class TaxManagerFactoryCore
 */
class Tax_Manager_Factory_Core
{
    /**
     * @var TaxManagerInterface[]
     */
    protected static $cache_tax_manager;
    /**
     * Returns a tax manager able to handle this address
     *
     * @param int $taxRuleGroupId TaxRulesGroup id
     *
     * @return TaxManagerInterface
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_manager(Address $address, $tax_rule_group_id)
    {
        $cache_id = static::get_cache_key($address) . '-' . $tax_rule_group_id;
        if (!isset(static::$cache_tax_manager[$cache_id])) {
            $tax_manager = static::exec_hook_tax_manager_factory($address, $tax_rule_group_id);
            if ($tax_manager) {
                static::$cache_tax_manager[$cache_id] = $tax_manager;
            } else {
                static::$cache_tax_manager[$cache_id] = new Tax_Rules_Tax_Manager($address, $tax_rule_group_id);
            }
        }
        return static::$cache_tax_manager[$cache_id];
    }
    /**
     * Check for a tax manager able to handle this type of address in the module list
     *
     * @param int $type TaxRulesGroup id
     *
     * @return TaxManagerInterface|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function exec_hook_tax_manager_factory(Address $address, $type): \Tax_Manager_Interface|false
    {
        $hook_name = 'taxManager';
        $modules = Hook::get_modules_from_hook(Hook::get_id_by_name($hook_name));
        foreach ($modules as $module) {
            $module_id = (int) $module['id_module'];
            /** @var TaxManagerInterface|false|null $taxManager */
            $tax_manager = Hook::get_response($hook_name, $module_id, ['address' => $address, 'params' => $type]);
            if ($tax_manager instanceof Tax_Manager_Interface) {
                return $tax_manager;
            }
        }
        return false;
    }
    /**
     * Create a unique identifier for the address
     */
    protected static function get_cache_key(Address $address): string
    {
        return $address->id_country . '-' . (int) $address->id_state . '-' . $address->postcode . '-' . $address->vat_number . '-' . $address->dni;
    }
}