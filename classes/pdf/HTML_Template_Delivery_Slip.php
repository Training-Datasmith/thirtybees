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
class Html_Template_Delivery_Slip_Core extends Html_Template
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
     * @param bool $bulkMode
     *
     * @throws PrestaShopException
     */
    public function __construct(Order_Invoice $order_invoice, Smarty $smarty, $bulk_mode = false)
    {
        $this->order_invoice = $order_invoice;
        $this->order = new Order($this->order_invoice->id_order);
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
        $prefix = Configuration::get('PS_DELIVERY_PREFIX', Context::get_context()->language->id);
        $this->title = sprintf(static::l('%1$s%2$06d'), $prefix, $this->order_invoice->delivery_number);
        // footer informations
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
        $this->smarty->assign(['header' => static::l('Delivery')]);
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
        $delivery_address = new Address((int) $this->order->id_address_delivery);
        $formatted_delivery_address = Address_Format::generate_address($delivery_address, [], '<br />', ' ');
        $formatted_invoice_address = '';
        if ($this->order->id_address_delivery != $this->order->id_address_invoice) {
            $invoice_address = new Address((int) $this->order->id_address_invoice);
            $formatted_invoice_address = Address_Format::generate_address($invoice_address, [], '<br />', ' ');
        }
        $carrier = new Carrier($this->order->id_carrier);
        $order_details = $this->order_invoice->get_products();
        foreach ($order_details as &$order_detail) {
            if (Order_Detail_Pack::is_pack((int) $order_detail['id_order_detail'])) {
                $pack_items = Order_Detail_Pack::get_items((int) $order_detail['id_order_detail'], Context::get_context()->language->id);
                $name_pack_items = '';
                foreach ($pack_items as $pack_item) {
                    $name_pack_items .= $pack_item->pack_quantity . ' x <b>' . $pack_item->reference . '</b> ' . $pack_item->name . ', ';
                }
                $order_detail['pack_items'] = $name_pack_items;
            }
        }
        if (Configuration::get('PS_PDF_IMG_DELIVERY')) {
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
        $this->smarty->assign(['order' => $this->order, 'order_details' => $order_details, 'delivery_address' => $formatted_delivery_address, 'invoice_address' => $formatted_invoice_address, 'order_invoice' => $this->order_invoice, 'carrier' => $carrier, 'display_product_images' => Configuration::get('PS_PDF_IMG_DELIVERY')]);
        $tpls = ['style_tab' => $this->smarty->fetch($this->get_template('delivery-slip.style-tab')), 'addresses_tab' => $this->smarty->fetch($this->get_template('delivery-slip.addresses-tab')), 'summary_tab' => $this->smarty->fetch($this->get_template('delivery-slip.summary-tab')), 'product_tab' => $this->smarty->fetch($this->get_template('delivery-slip.product-tab')), 'payment_tab' => $this->smarty->fetch($this->get_template('delivery-slip.payment-tab'))];
        $this->smarty->assign($tpls);
        return $this->smarty->fetch($this->get_template('delivery-slip'));
    }
    /**
     * Returns the template filename when using bulk rendering
     *
     * @return string filename
     */
    public function get_bulk_filename()
    {
        return 'deliveries.pdf';
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
        return Configuration::get('PS_DELIVERY_PREFIX', Context::get_context()->language->id, null, $this->order->id_shop) . sprintf('%06d', $this->order->delivery_number) . '.pdf';
    }
}