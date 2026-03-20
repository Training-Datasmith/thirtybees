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
 * Class HTMLTemplateOrderReturnCore
 */
class Html_Template_Order_Return_Core extends Html_Template
{
    /**
     * @var OrderReturn $order_return
     */
    public $order_return;
    /**
     * @var Order $order
     */
    public $order;
    /**
     *
     * @throws PrestaShopException
     */
    public function __construct(Order_Return $order_return, Smarty $smarty)
    {
        $this->order_return = $order_return;
        $this->smarty = $smarty;
        $this->order = new Order($order_return->id_order);
        // header informations
        $this->date = Tools::display_date($this->order->invoice_date);
        $prefix = Configuration::get('PS_RETURN_PREFIX', Context::get_context()->language->id);
        $this->title = sprintf(Html_Template_Order_Return::l('%1$s%2$06d'), $prefix, $this->order_return->id);
        $this->shop = new Shop((int) $this->order->id_shop);
    }
    /**
     * Returns the template's HTML content
     *
     * @return string HTML content
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function get_content()
    {
        $delivery_address = new Address((int) $this->order->id_address_delivery);
        $formatted_delivery_address = Address_Format::generate_address($delivery_address, [], '<br />', ' ');
        $formatted_invoice_address = '';
        if ($this->order->id_address_delivery != $this->order->id_address_invoice) {
            $invoice_address = new Address((int) $this->order->id_address_invoice);
            $formatted_invoice_address = Address_Format::generate_address($invoice_address, [], '<br />', ' ');
        }
        $this->smarty->assign(['order_return' => $this->order_return, 'return_nb_days' => (int) Configuration::get('PS_ORDER_RETURN_NB_DAYS'), 'products' => Order_Return::get_orders_return_products((int) $this->order_return->id, $this->order), 'delivery_address' => $formatted_delivery_address, 'invoice_address' => $formatted_invoice_address, 'shop_address' => Address_Format::generate_address($this->shop->get_address(), [], '<br />', ' ')]);
        $tpls = ['style_tab' => $this->smarty->fetch($this->get_template('invoice.style-tab')), 'addresses_tab' => $this->smarty->fetch($this->get_template('order-return.addresses-tab')), 'summary_tab' => $this->smarty->fetch($this->get_template('order-return.summary-tab')), 'product_tab' => $this->smarty->fetch($this->get_template('order-return.product-tab')), 'conditions_tab' => $this->smarty->fetch($this->get_template('order-return.conditions-tab'))];
        $this->smarty->assign($tpls);
        return $this->smarty->fetch($this->get_template('order-return'));
    }
    /**
     * Returns the template filename
     *
     * @return string filename
     *
     * @throws PrestaShopException
     */
    public function get_filename()
    {
        return Configuration::get('PS_RETURN_PREFIX', Context::get_context()->language->id) . sprintf('%06d', $this->order_return->id) . '.pdf';
    }
    /**
     * Returns the template filename when using bulk rendering
     *
     * @return string filename
     */
    public function get_bulk_filename()
    {
        return 'invoices.pdf';
    }
    /**
     * Returns the template's HTML header
     *
     * @return string HTML header
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function get_header()
    {
        $this->assign_common_header_data();
        $this->smarty->assign(['header' => Html_Template_Order_Return::l('Order return')]);
        return $this->smarty->fetch($this->get_template('header'));
    }
}