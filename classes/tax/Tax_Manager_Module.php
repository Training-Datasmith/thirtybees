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
 * Class TaxManagerModuleCore
 */
abstract class Tax_Manager_Module_Core extends Module
{
    /**
     * @var string
     */
    public $tax_manager_class;
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        return parent::install() && $this->register_hook('taxManager');
    }
    /**
     * @param array $args
     *
     * @return TaxManagerInterface|false
     *
     * @throws PrestaShopException
     */
    public function hook_tax_manager($args)
    {
        $class_file = _PS_MODULE_DIR_ . '/' . $this->name . '/' . $this->tax_manager_class . '.php';
        if (!isset($this->tax_manager_class) || !file_exists($class_file)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Incorrect Tax Manager class [%s]'), $this->tax_manager_class));
        }
        require_once $class_file;
        if (!class_exists($this->tax_manager_class)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Tax Manager class not found [%s]'), $this->tax_manager_class));
        }
        $class = $this->tax_manager_class;
        if (call_user_func([$class, 'isAvailableForThisAddress'], $args['address'])) {
            return new $class();
        }
        return false;
    }
}