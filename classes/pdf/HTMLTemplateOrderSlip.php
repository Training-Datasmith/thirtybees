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
 * Class HTMLTemplateOrderSlipCore
 */
class Html_Template_Order_Slip_Core extends Html_Template
{
    /**
     * @var Order $order
     */
    public $order;
    /**
     * @var array[]
     */
    public $products;
    /**
     * @var OrderSlip $order_slip
     */
    public $order_slip;
    /**
     *
     * @throws PrestaShopException
     */
    public function __construct(Order_Slip $order_slip, Smarty $smarty)
    {
        $this->order_slip = $order_slip;
        $this->order = new Order((int) $order_slip->id_order);
        $products = Order_Slip::get_orders_slip_products($this->order_slip->id, $this->order);
        $customized_datas = Product::get_all_customized_datas((int) $this->order->id_cart);
        Product::add_customization_price($products, $customized_datas);
        $this->products = $products;
        $this->smarty = $smarty;
        // header informations
        $this->date = Tools::display_date($this->order_slip->date_add);
        $prefix = Configuration::get('PS_CREDIT_SLIP_PREFIX', Context::get_context()->language->id);
        $this->title = sprintf(static::l('%1$s%2$06d'), $prefix, (int) $this->order_slip->id);
        $this->shop = new Shop((int) $this->order->id_shop);
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
        $this->smarty->assign(['header' => static::l('Credit slip')]);
        return $this->smarty->fetch($this->get_template('header'));
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
        $delivery_address = $invoice_address = new Address((int) $this->order->id_address_invoice);
        $formatted_invoice_address = Address_Format::generate_address($invoice_address, [], '<br />', ' ');
        $formatted_delivery_address = '';
        if ($this->order->id_address_delivery != $this->order->id_address_invoice) {
            $delivery_address = new Address((int) $this->order->id_address_delivery);
            $formatted_delivery_address = Address_Format::generate_address($delivery_address, [], '<br />', ' ');
        }
        $customer = new Customer((int) $this->order->id_customer);
        $this->order->total_paid_tax_excl = $this->order->total_paid_tax_incl = $this->order->total_products = $this->order->total_products_wt = 0;
        if ($this->order_slip->amount > 0) {
            foreach ($this->products as &$product) {
                $product['total_price_tax_excl'] = $product['unit_price_tax_excl'] * $product['product_quantity'];
                $product['total_price_tax_incl'] = $product['unit_price_tax_incl'] * $product['product_quantity'];
                if ($this->order_slip->partial == 1) {
                    $order_slip_detail = Db::read_only()->get_row((new Db_Query())->select('*')->from('order_slip_detail')->where('`id_order_slip` = ' . (int) $this->order_slip->id)->where('`id_order_detail` = ' . (int) $product['id_order_detail']));
                    $product['total_price_tax_excl'] = $order_slip_detail['amount_tax_excl'];
                    $product['total_price_tax_incl'] = $order_slip_detail['amount_tax_incl'];
                }
                $this->order->total_products += $product['total_price_tax_excl'];
                $this->order->total_products_wt += $product['total_price_tax_incl'];
                $this->order->total_paid_tax_excl = $this->order->total_products;
                $this->order->total_paid_tax_incl = $this->order->total_products_wt;
            }
        } else {
            $this->products = [];
        }
        unset($product);
        // remove reference
        if ($this->order_slip->shipping_cost == 0) {
            $this->order->total_shipping_tax_incl = $this->order->total_shipping_tax_excl = 0;
        }
        $tax = new Tax();
        $tax->rate = $this->order->carrier_tax_rate;
        $tax_excluded_display = Group::get_price_display_method((int) $customer->id_default_group);
        $this->order->total_shipping_tax_incl = $this->order_slip->total_shipping_tax_incl;
        $this->order->total_shipping_tax_excl = $this->order_slip->total_shipping_tax_excl;
        $this->order_slip->shipping_cost_amount = $tax_excluded_display ? $this->order_slip->total_shipping_tax_excl : $this->order_slip->total_shipping_tax_incl;
        $this->order->total_paid_tax_incl += $this->order->total_shipping_tax_incl;
        $this->order->total_paid_tax_excl += $this->order->total_shipping_tax_excl;
        $total_cart_rule = 0;
        if ($this->order_slip->order_slip_type == 1 && is_array($cart_rules = $this->order->get_cart_rules())) {
            foreach ($cart_rules as $cart_rule) {
                if ($tax_excluded_display) {
                    $total_cart_rule += $cart_rule['value_tax_excl'];
                } else {
                    $total_cart_rule += $cart_rule['value'];
                }
            }
        }
        $this->smarty->assign(['order' => $this->order, 'order_slip' => $this->order_slip, 'order_details' => $this->products, 'cart_rules' => $this->order_slip->order_slip_type == 1 ? $this->order->get_cart_rules() : false, 'amount_choosen' => $this->order_slip->order_slip_type == 2, 'delivery_address' => $formatted_delivery_address, 'invoice_address' => $formatted_invoice_address, 'addresses' => ['invoice' => $invoice_address, 'delivery' => $delivery_address], 'tax_excluded_display' => $tax_excluded_display, 'total_cart_rule' => $total_cart_rule]);
        $tpls = ['style_tab' => $this->smarty->fetch($this->get_template('invoice.style-tab')), 'addresses_tab' => $this->smarty->fetch($this->get_template('invoice.addresses-tab')), 'summary_tab' => $this->smarty->fetch($this->get_template('order-slip.summary-tab')), 'product_tab' => $this->smarty->fetch($this->get_template('order-slip.product-tab')), 'total_tab' => $this->smarty->fetch($this->get_template('order-slip.total-tab')), 'payment_tab' => $this->smarty->fetch($this->get_template('order-slip.payment-tab')), 'tax_tab' => $this->get_tax_tab_content()];
        $this->smarty->assign($tpls);
        return $this->smarty->fetch($this->get_template('order-slip'));
    }
    /**
     * Returns the template filename when using bulk rendering
     *
     * @return string filename
     */
    public function get_bulk_filename()
    {
        return 'order-slips.pdf';
    }
    /**
     * Returns the template filename
     *
     * @return string filename
     */
    public function get_filename()
    {
        return 'order-slip-' . sprintf('%06d', $this->order_slip->id) . '.pdf';
    }
    /**
     * Returns the tax tab content
     *
     * @return String Tax tab html content
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function get_tax_tab_content()
    {
        $address = new Address((int) $this->order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        $tax_exempt = false;
        // @TODO: Use a hook for this
        if (Module::is_enabled('vatnumber')) {
            require_once _PS_MODULE_DIR_ . '/vatnumber/VATNumberTaxManager.php';
            $tax_exempt = Vat_Number_Tax_Manager::is_available_for_this_address($address);
        }
        $this->smarty->assign(['tax_exempt' => $tax_exempt, 'product_tax_breakdown' => $this->get_product_taxes_breakdown(), 'shipping_tax_breakdown' => $this->get_shipping_taxes_breakdown(), 'order' => $this->order, 'ecotax_tax_breakdown' => $this->order_slip->get_eco_tax_taxes_breakdown(), 'is_order_slip' => true, 'tax_breakdowns' => $this->get_tax_breakdown(), 'display_tax_bases_in_breakdowns' => false]);
        return $this->smarty->fetch($this->get_template('invoice.tax-tab'));
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_product_taxes_breakdown()
    {
        if (!$this->products) {
            return [];
        }
        // $breakdown will be an array with tax rates as keys and at least the columns:
        // 	- 'total_price_tax_excl'
        // 	- 'total_amount'
        $breakdown = [];
        $details = $this->order->get_product_taxes_details($this->products);
        foreach ($details as $row) {
            $rate = sprintf('%.3f', $row['tax_rate']);
            if (!isset($breakdown[$rate])) {
                $breakdown[$rate] = ['total_price_tax_excl' => 0, 'total_amount' => 0, 'id_tax' => $row['id_tax'], 'rate' => $rate];
            }
            $breakdown[$rate]['total_price_tax_excl'] += $row['total_tax_base'];
            $breakdown[$rate]['total_amount'] += $row['total_amount'];
        }
        $decimals = Currency::get_currency_instance($this->order->id_currency)->get_display_precision();
        foreach ($breakdown as $rate => $data) {
            $breakdown[$rate]['total_price_tax_excl'] = Tools::ps_round($data['total_price_tax_excl'], $decimals, $this->order->round_mode);
            $breakdown[$rate]['total_amount'] = Tools::ps_round($data['total_amount'], $decimals, $this->order->round_mode);
        }
        ksort($breakdown);
        return $breakdown;
    }
    /**
     * Returns Shipping tax breakdown elements
     *
     * @return array Shipping tax breakdown elements
     *
     * @throws PrestaShopException
     */
    public function get_shipping_taxes_breakdown()
    {
        $taxes_breakdown = [];
        $tax = new Tax();
        $tax->rate = $this->order->carrier_tax_rate;
        $tax_calculator = new Tax_Calculator([$tax]);
        $customer = new Customer((int) $this->order->id_customer);
        $tax_excluded_display = Group::get_price_display_method((int) $customer->id_default_group);
        if ($tax_excluded_display) {
            $total_tax_excl = $this->order_slip->shipping_cost_amount;
            $shipping_tax_amount = $tax_calculator->add_taxes($this->order_slip->shipping_cost_amount) - $total_tax_excl;
        } else {
            $total_tax_excl = $tax_calculator->remove_taxes($this->order_slip->shipping_cost_amount);
            $shipping_tax_amount = $this->order_slip->shipping_cost_amount - $total_tax_excl;
        }
        if ($shipping_tax_amount > 0) {
            $taxes_breakdown[] = ['rate' => $this->order->carrier_tax_rate, 'total_amount' => $shipping_tax_amount, 'total_tax_excl' => $total_tax_excl];
        }
        return $taxes_breakdown;
    }
    /**
     * Returns different tax breakdown elements
     *
     * @return array Different tax breakdown elements
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function get_tax_breakdown()
    {
        $breakdowns = ['product_tax' => $this->get_product_taxes_breakdown(), 'shipping_tax' => $this->get_shipping_taxes_breakdown(), 'ecotax_tax' => $this->order_slip->get_eco_tax_taxes_breakdown()];
        foreach ($breakdowns as $type => $bd) {
            if (empty($bd)) {
                unset($breakdowns[$type]);
            }
        }
        if (empty($breakdowns)) {
            $breakdowns = false;
        }
        if (isset($breakdowns['product_tax'])) {
            foreach ($breakdowns['product_tax'] as &$bd) {
                $bd['total_tax_excl'] = $bd['total_price_tax_excl'];
            }
        }
        if (isset($breakdowns['ecotax_tax'])) {
            foreach ($breakdowns['ecotax_tax'] as &$bd) {
                $bd['total_tax_excl'] = $bd['ecotax_tax_excl'];
                $bd['total_amount'] = $bd['ecotax_tax_incl'] - $bd['ecotax_tax_excl'];
            }
        }
        return $breakdowns;
    }
}