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
class Html_Template_Invoice_Core extends Html_Template
{
    /**
     * @var Order $order
     */
    public $order;
    /**
     * @var OrderInvoice $order_invoice
     */
    public $order_invoice;
    /**
     * @var bool $available_in_your_account
     */
    public $available_in_your_account = false;
    /**
     * @param bool $bulkMode
     *
     * @throws PrestaShopException
     */
    public function __construct(Order_Invoice $order_invoice, Smarty $smarty, $bulk_mode = false)
    {
        $this->order_invoice = $order_invoice;
        $this->order = new Order((int) $this->order_invoice->id_order);
        $this->smarty = $smarty;
        // If shop_address is null, then update it with current one.
        // But no DB save required here to avoid massive updates for bulk PDF generation case.
        // (DB: bug fixed in 1.6.1.1 with upgrade SQL script to avoid null shop_address in old orderInvoices)
        if (!isset($this->order_invoice->shop_address) || !$this->order_invoice->shop_address) {
            $this->order_invoice->shop_address = Order_Invoice::get_current_formatted_shop_address((int) $this->order->id_shop);
            if (!$bulk_mode) {
                Order_Invoice::fix_all_shop_addresses();
            }
        }
        // header informations
        $this->date = Tools::display_date($order_invoice->date_add);
        $id_lang = Context::get_context()->language->id;
        $this->title = $order_invoice->get_invoice_number_formatted($id_lang, (int) $this->order->id_shop);
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
        $this->smarty->assign(['header' => static::l('Invoice')]);
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
        $invoice_address_pattern_rules = json_decode(Configuration::get('PS_INVCE_INVOICE_ADDR_RULES'), true);
        $delivery_address_pattern_rules = json_decode(Configuration::get('PS_INVCE_DELIVERY_ADDR_RULES'), true);
        $invoice_address = new Address((int) $this->order->id_address_invoice);
        $country = new Country((int) $invoice_address->id_country);
        $formatted_invoice_address = Address_Format::generate_address($invoice_address, $invoice_address_pattern_rules, '<br />', ' ');
        $delivery_address = null;
        $formatted_delivery_address = '';
        if (isset($this->order->id_address_delivery) && $this->order->id_address_delivery) {
            $delivery_address = new Address((int) $this->order->id_address_delivery);
            $formatted_delivery_address = Address_Format::generate_address($delivery_address, $delivery_address_pattern_rules, '<br />', ' ');
        }
        $customer = new Customer((int) $this->order->id_customer);
        $carrier = new Carrier((int) $this->order->id_carrier);
        $order_details = $this->order_invoice->get_products();
        $has_discount = false;
        foreach ($order_details as $id => &$order_detail) {
            // Find out if column 'price before discount' is required
            if ($order_detail['reduction_amount_tax_excl'] > 0) {
                $has_discount = true;
                $order_detail['unit_price_tax_excl_before_specific_price'] = $order_detail['unit_price_tax_excl_including_ecotax'] + $order_detail['reduction_amount_tax_excl'];
            } elseif ($order_detail['reduction_percent'] > 0) {
                $has_discount = true;
                $order_detail['unit_price_tax_excl_before_specific_price'] = 100 * $order_detail['unit_price_tax_excl_including_ecotax'] / (100 - $order_detail['reduction_percent']);
            }
            // Set tax_code
            $taxes = Order_Detail::get_tax_list_static($id);
            $tax_temp = [];
            foreach ($taxes as $tax) {
                $obj = new Tax($tax['id_tax']);
                $tax_rate = Validate::is_loaded_object($obj) ? round($obj->rate, 3) : 0.0;
                $tax_temp[] = sprintf($this->l('%1$s%2$s%%'), $tax_rate, '&nbsp;');
            }
            $order_detail['order_detail_tax'] = $taxes;
            $order_detail['order_detail_tax_label'] = implode(', ', $tax_temp);
        }
        unset($tax_temp);
        unset($order_detail);
        if (Configuration::get('PS_PDF_IMG_INVOICE')) {
            foreach ($order_details as &$order_detail) {
                if ($order_detail['image'] instanceof Image) {
                    $image_id = (int) $order_detail['image']->id;
                    $order_detail['image_tag'] = preg_replace('/\.*' . preg_quote(__PS_BASE_URI__, '/') . '/', _PS_ROOT_DIR_ . DIRECTORY_SEPARATOR, Image_Manager::get_product_image_thumbnail_tag($image_id, false), 1);
                    $image_path = Image_Manager::get_product_image_thumbnail_file_path($image_id);
                    if (file_exists($image_path)) {
                        $order_detail['image_size'] = getimagesize($image_path);
                    } else {
                        $order_detail['image_size'] = false;
                    }
                }
            }
            unset($order_detail);
            // don't overwrite the last order_detail later
        }
        $cart_rules = $this->order->get_cart_rules();
        $free_shipping = false;
        foreach ($cart_rules as $key => $cart_rule) {
            if ($cart_rule['free_shipping']) {
                $free_shipping = true;
                /**
                 * Adjust cart rule value to remove the amount of the shipping.
                 * We're not interested in displaying the shipping discount as it is already shown as "Free Shipping".
                 */
                $cart_rules[$key]['value_tax_excl'] -= $this->order_invoice->total_shipping_tax_excl;
                $cart_rules[$key]['value'] -= $this->order_invoice->total_shipping_tax_incl;
                /**
                 * Don't display cart rules that are only about free shipping and don't create
                 * a discount on products.
                 */
                if ($cart_rules[$key]['value'] == 0) {
                    unset($cart_rules[$key]);
                }
            }
        }
        $product_taxes = 0;
        foreach ($this->order_invoice->get_product_taxes_breakdown($this->order) as $details) {
            $product_taxes += $details['total_amount'];
        }
        $product_discounts_tax_excl = $this->order_invoice->total_discount_tax_excl;
        $product_discounts_tax_incl = $this->order_invoice->total_discount_tax_incl;
        if ($free_shipping) {
            $product_discounts_tax_excl -= $this->order_invoice->total_shipping_tax_excl;
            $product_discounts_tax_incl -= $this->order_invoice->total_shipping_tax_incl;
        }
        $products_after_discounts_tax_excl = $this->order_invoice->total_products - $product_discounts_tax_excl;
        $products_after_discounts_tax_incl = $this->order_invoice->total_products_wt - $product_discounts_tax_incl;
        $shipping_tax_excl = $free_shipping ? 0 : $this->order_invoice->total_shipping_tax_excl;
        $shipping_tax_incl = $free_shipping ? 0 : $this->order_invoice->total_shipping_tax_incl;
        $shipping_taxes = $shipping_tax_incl - $shipping_tax_excl;
        $wrapping_taxes = $this->order_invoice->total_wrapping_tax_incl - $this->order_invoice->total_wrapping_tax_excl;
        $total_taxes = $this->order_invoice->total_paid_tax_incl - $this->order_invoice->total_paid_tax_excl;
        $footer = ['products_before_discounts_tax_excl' => $this->order_invoice->total_products, 'product_discounts_tax_excl' => $product_discounts_tax_excl, 'products_after_discounts_tax_excl' => $products_after_discounts_tax_excl, 'products_before_discounts_tax_incl' => $this->order_invoice->total_products_wt, 'product_discounts_tax_incl' => $product_discounts_tax_incl, 'products_after_discounts_tax_incl' => $products_after_discounts_tax_incl, 'product_taxes' => $product_taxes, 'shipping_tax_excl' => $shipping_tax_excl, 'shipping_taxes' => $shipping_taxes, 'shipping_tax_incl' => $shipping_tax_incl, 'wrapping_tax_excl' => $this->order_invoice->total_wrapping_tax_excl, 'wrapping_taxes' => $wrapping_taxes, 'wrapping_tax_incl' => $this->order_invoice->total_wrapping_tax_incl, 'ecotax_taxes' => $total_taxes - $product_taxes - $wrapping_taxes - $shipping_taxes, 'total_taxes' => $total_taxes, 'total_paid_tax_excl' => $this->order_invoice->total_paid_tax_excl, 'total_paid_tax_incl' => $this->order_invoice->total_paid_tax_incl];
        $decimals = Currency::get_currency_instance($this->order->id_currency)->get_display_precision();
        foreach ($footer as $key => $value) {
            $footer[$key] = Tools::ps_round($value, $decimals, $this->order->round_mode);
        }
        $display_product_images = Configuration::get('PS_PDF_IMG_INVOICE');
        $tax_excluded_display = Group::get_price_display_method($customer->id_default_group);
        $layout = $this->compute_layout(['has_discount' => $has_discount]);
        $legal_free_text = Hook::display_hook('displayInvoiceLegalFreeText', ['order' => $this->order]);
        if (!$legal_free_text) {
            $legal_free_text = Configuration::get('PS_INVOICE_LEGAL_FREE_TEXT', (int) Context::get_context()->language->id, null, (int) $this->order->id_shop);
        }
        $data = ['order' => $this->order, 'order_invoice' => $this->order_invoice, 'order_details' => $order_details, 'carrier' => $carrier, 'cart_rules' => $cart_rules, 'delivery_address' => $formatted_delivery_address, 'invoice_address' => $formatted_invoice_address, 'addresses' => ['invoice' => $invoice_address, 'delivery' => $delivery_address], 'tax_excluded_display' => $tax_excluded_display, 'display_product_images' => $display_product_images, 'layout' => $layout, 'customer' => $customer, 'footer' => $footer, 'legal_free_text' => $legal_free_text];
        $this->smarty->assign($data);
        $tpls = ['style_tab' => $this->smarty->fetch($this->get_template('invoice.style-tab')), 'addresses_tab' => $this->smarty->fetch($this->get_template('invoice.addresses-tab')), 'summary_tab' => $this->smarty->fetch($this->get_template('invoice.summary-tab')), 'product_tab' => $this->smarty->fetch($this->get_template('invoice.product-tab')), 'tax_tab' => $this->get_tax_tab_content(), 'payment_tab' => $this->smarty->fetch($this->get_template('invoice.payment-tab')), 'note_tab' => $this->smarty->fetch($this->get_template('invoice.note-tab')), 'total_tab' => $this->smarty->fetch($this->get_template('invoice.total-tab')), 'shipping_tab' => $this->smarty->fetch($this->get_template('invoice.shipping-tab'))];
        $this->smarty->assign($tpls);
        return $this->smarty->fetch($this->get_template_by_country($country->iso_code));
    }
    /**
     * Returns the tax tab content
     *
     * @return false|string Tax tab html content
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
        $tax_breakdowns = $this->get_tax_breakdown();
        $shipping_tax_breakdowns = $this->order_invoice->get_shipping_taxes_breakdown($this->order);
        $eco_tax_breakdowns = $this->order_invoice->get_eco_tax_taxes_breakdown();
        $wrapping_tax_breakdowns = $this->order_invoice->get_wrapping_taxes_breakdown();
        foreach (array_merge($shipping_tax_breakdowns, $eco_tax_breakdowns, $wrapping_tax_breakdowns) as &$breakdown) {
            $breakdown['rate'] = round($breakdown['rate'], 3);
        }
        $data = ['tax_exempt' => $tax_exempt, 'use_one_after_another_method' => $this->order_invoice->use_one_after_another_tax_computation_method(), 'display_tax_bases_in_breakdowns' => $this->order_invoice->display_tax_bases_in_product_taxes_breakdown(), 'product_tax_breakdown' => $this->order_invoice->get_product_taxes_breakdown($this->order), 'shipping_tax_breakdown' => $shipping_tax_breakdowns, 'ecotax_tax_breakdown' => $eco_tax_breakdowns, 'wrapping_tax_breakdown' => $wrapping_tax_breakdowns, 'tax_breakdowns' => $tax_breakdowns];
        $this->smarty->assign($data);
        return $this->smarty->fetch($this->get_template('invoice.tax-tab'));
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
     * Returns the template filename
     *
     * @return string filename
     *
     * @throws PrestaShopException
     */
    public function get_filename()
    {
        $id_lang = Context::get_context()->language->id;
        $id_shop = (int) $this->order->id_shop;
        $format = '%1$s%2$06d';
        if (Configuration::get('PS_INVOICE_USE_YEAR')) {
            $format = Configuration::get('PS_INVOICE_YEAR_POS') ? '%1$s%3$s-%2$06d' : '%1$s%2$06d-%3$s';
        }
        return sprintf($format, Configuration::get('PS_INVOICE_PREFIX', $id_lang, null, $id_shop), $this->order_invoice->number, date('Y', strtotime((string) $this->order_invoice->date_add))) . '.pdf';
    }
    /**
     * Compute layout elements size
     *
     * @param array $params Layout elements
     *
     * @return array Layout elements columns size
     */
    protected function compute_layout($params)
    {
        $layout = ['reference' => ['width' => 15], 'product' => ['width' => 40], 'quantity' => ['width' => 8], 'tax_code' => ['width' => 12], 'unit_price_tax_excl' => ['width' => 0], 'total_tax_excl' => ['width' => 0]];
        if (isset($params['has_discount']) && $params['has_discount']) {
            $layout['before_discount'] = ['width' => 0];
            $layout['product']['width'] -= 7;
            $layout['reference']['width'] -= 3;
        }
        $total_width = 0;
        $free_columns_count = 0;
        foreach ($layout as $data) {
            if ($data['width'] === 0) {
                ++$free_columns_count;
            }
            $total_width += $data['width'];
        }
        $delta = 100 - $total_width;
        foreach ($layout as $row => $data) {
            if ($data['width'] === 0) {
                $layout[$row]['width'] = $delta / $free_columns_count;
            }
        }
        $layout['_colCount'] = count($layout);
        return $layout;
    }
    /**
     * Returns the invoice template associated to the country iso_code
     *
     * @param string $isoCountry
     *
     * @return string
     * @throws PrestaShopException
     */
    protected function get_template_by_country($iso_country)
    {
        $file = Configuration::get('PS_INVOICE_MODEL');
        // try to fetch the iso template
        $template = $this->get_template($file . '.' . $iso_country);
        // else use the default one
        if (!$template) {
            return $this->get_template($file);
        }
        return $template;
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
        $breakdowns = ['product_tax' => $this->order_invoice->get_product_taxes_breakdown($this->order), 'shipping_tax' => $this->order_invoice->get_shipping_taxes_breakdown($this->order), 'ecotax_tax' => $this->order_invoice->get_eco_tax_taxes_breakdown(), 'wrapping_tax' => $this->order_invoice->get_wrapping_taxes_breakdown()];
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