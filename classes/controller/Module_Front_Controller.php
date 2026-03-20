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
 * Class ModuleFrontControllerCore
 */
class Module_Front_Controller_Core extends Front_Controller
{
    /**
     * @var Module $module
     */
    public $module;
    /**
     * ModuleFrontControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->module = Module::get_instance_by_name(Tools::get_value('module'));
        if (!$this->module->active) {
            Tools::redirect('index');
        }
        $this->page_name = 'module-' . $this->module->name . '-' . Dispatcher::get_instance()->get_controller();
        parent::__construct();
        $this->controller_type = 'modulefront';
        $theme = $this->context->theme;
        if ($this->page_name && Validate::is_loaded_object($theme) && $theme->has_columns_settings($this->page_name)) {
            $this->display_column_left = $theme->has_left_column($this->page_name);
            $this->display_column_right = $theme->has_right_column($this->page_name);
        } else {
            $this->display_column_left ??= true;
            $this->display_column_right ??= true;
        }
    }
    /**
     * Assigns module template for page content
     *
     * @param string $template Template filename
     *
     * @throws PrestaShopException
     */
    public function set_template($template): void
    {
        if (!$path = $this->get_template_path($template)) {
            throw new Presta_Shop_Exception("Template '{$template}' not found");
        }
        $this->template = $path;
    }
    /**
     * Finds and returns module front template that take the highest precedence
     *
     * @param string $template Template filename
     *
     * @return string|false
     */
    public function get_template_path($template)
    {
        if (file_exists(_PS_THEME_DIR_ . 'modules/' . $this->module->name . '/' . $template)) {
            return _PS_THEME_DIR_ . 'modules/' . $this->module->name . '/' . $template;
        }
        if (file_exists(_PS_THEME_DIR_ . 'modules/' . $this->module->name . '/views/templates/front/' . $template)) {
            return _PS_THEME_DIR_ . 'modules/' . $this->module->name . '/views/templates/front/' . $template;
        }
        if (file_exists(_PS_MODULE_DIR_ . $this->module->name . '/views/templates/front/' . $template)) {
            return _PS_MODULE_DIR_ . $this->module->name . '/views/templates/front/' . $template;
        }
        return false;
    }
    /**
     * @throws PrestaShopException
     */
    public function init_content(): void
    {
        if (Tools::is_submit('module') && Tools::get_value('controller') == 'payment') {
            $currency = Currency::get_currency((int) $this->context->cart->id_currency);
            $minimal_purchase = Tools::convert_price((float) Configuration::get('PS_PURCHASE_MINIMUM'), $currency);
            if ($this->context->cart->get_order_total(false, Cart::ONLY_PRODUCTS) < $minimal_purchase) {
                Tools::redirect('index.php?controller=order&step=1');
            }
        }
        parent::init_content();
    }
}