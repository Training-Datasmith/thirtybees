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
 * Class HTMLTemplateSupplyOrderFormCore
 */
class Html_Template_Supply_Order_Form_Core extends Html_Template
{
    /**
     * @var SupplyOrder $supply_order
     */
    public $supply_order;
    /**
     * @var Warehouse $warehouse
     */
    public $warehouse;
    /**
     * @var Address $address_warehouse
     */
    public $address_warehouse;
    /**
     * @var Address $address_supplier
     */
    public $address_supplier;
    /**
     * @var Context $context
     */
    public $context;
    /**
     * @var Currency $currency
     */
    protected $currency;
    /**
     *
     * @throws PrestaShopException
     */
    public function __construct(Supply_Order $supply_order, Smarty $smarty)
    {
        $this->supply_order = $supply_order;
        $this->smarty = $smarty;
        $this->context = Context::get_context();
        $this->warehouse = new Warehouse((int) $supply_order->id_warehouse);
        $this->address_warehouse = new Address((int) $this->warehouse->id_address);
        $this->address_supplier = new Address(Address::get_address_id_by_supplier_id((int) $supply_order->id_supplier));
        $this->currency = Currency::get_currency_instance((int) $this->supply_order->id_currency);
        // Header informations
        $this->date = Tools::display_date($supply_order->date_add);
        $this->title = static::l('Supply order form');
        $this->shop = new Shop((int) $this->supply_order->id_shop);
    }
    /**
     * @see HTMLTemplate::getContent()
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function get_content()
    {
        $supply_order_details = $this->supply_order->get_entries_collection();
        $this->round_supply_order_details($supply_order_details);
        $this->round_supply_order($this->supply_order);
        $tax_order_summary = $this->get_tax_order_summary();
        $this->smarty->assign(['warehouse' => $this->warehouse, 'address_warehouse' => $this->address_warehouse, 'address_supplier' => $this->address_supplier, 'supply_order' => $this->supply_order, 'supply_order_details' => $supply_order_details, 'tax_order_summary' => $tax_order_summary, 'currency' => $this->currency]);
        $tpls = ['style_tab' => $this->smarty->fetch($this->get_template('invoice.style-tab')), 'addresses_tab' => $this->smarty->fetch($this->get_template('supply-order.addresses-tab')), 'product_tab' => $this->smarty->fetch($this->get_template('supply-order.product-tab')), 'tax_tab' => $this->smarty->fetch($this->get_template('supply-order.tax-tab')), 'total_tab' => $this->smarty->fetch($this->get_template('supply-order.total-tab'))];
        $this->smarty->assign($tpls);
        return $this->smarty->fetch($this->get_template('supply-order'));
    }
    /**
     * Returns the invoice logo
     *
     * @return String Logo path
     *
     * @throws PrestaShopException
     */
    protected function get_logo()
    {
        $logo = '';
        if (Configuration::get('PS_LOGO_INVOICE', null, null, (int) Shop::get_context_shop_id()) != false && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE', null, null, (int) Shop::get_context_shop_id()))) {
            $logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE', null, null, (int) Shop::get_context_shop_id());
        } elseif (Configuration::get('PS_LOGO', null, null, (int) Shop::get_context_shop_id()) != false && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, (int) Shop::get_context_shop_id()))) {
            $logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, (int) Shop::get_context_shop_id());
        }
        return $logo;
    }
    /**
     * @see HTMLTemplate::getBulkFilename()
     */
    public function get_bulk_filename()
    {
        return 'supply_order.pdf';
    }
    /**
     * @see HTMLTemplate::getFileName()
     */
    public function get_filename()
    {
        return static::l('SupplyOrderForm') . sprintf('_%s', $this->supply_order->reference) . '.pdf';
    }
    /**
     * Get order taxes summary
     *
     * @return array
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    protected function get_tax_order_summary()
    {
        $query = new Db_Query();
        $query->select('SUM(`price_with_order_discount_te`) AS `base_te`');
        $query->select('`tax_rate`');
        $query->select('SUM(`tax_value_with_order_discount`) AS `total_tax_value`');
        $query->from('supply_order_detail');
        $query->where('`id_supply_order` = ' . (int) $this->supply_order->id);
        $query->group_by('`tax_rate`');
        $results = Db::read_only()->get_array($query);
        $decimals = $this->currency->get_display_precision();
        foreach ($results as &$result) {
            $result['base_te'] = Tools::ps_round($result['base_te'], $decimals);
            $result['tax_rate'] = Tools::ps_round($result['tax_rate'], $decimals);
            $result['total_tax_value'] = Tools::ps_round($result['total_tax_value'], $decimals);
        }
        unset($result);
        // remove reference
        return $results;
    }
    /**
     * @see HTMLTemplate::getHeader()
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function get_header()
    {
        $shop_name = Configuration::get('PS_SHOP_NAME');
        $path_logo = $this->get_logo();
        $width = $height = 0;
        if (!empty($path_logo)) {
            [$width, $height] = getimagesize($path_logo);
        }
        $this->smarty->assign(['logo_path' => $path_logo, 'img_ps_dir' => Tools::get_shop_protocol() . Tools::get_media_server(_PS_IMG_) . _PS_IMG_, 'img_update_time' => Configuration::get('PS_IMG_UPDATE_TIME'), 'title' => $this->title, 'reference' => $this->supply_order->reference, 'date' => $this->date, 'shop_name' => $shop_name, 'width_logo' => $width, 'height_logo' => $height]);
        return $this->smarty->fetch($this->get_template('supply-order-header'));
    }
    /**
     * @return false|string
     * @throws PrestaShopException
     * @throws SmartyException
     * @see HTMLTemplate::getFooter()
     */
    public function get_footer()
    {
        $free_text = [];
        $free_text[] = Html_Template_Supply_Order_Form::l('TE: Tax excluded');
        $free_text[] = Html_Template_Supply_Order_Form::l('TI: Tax included');
        $this->smarty->assign(['shop_address' => $this->get_shop_address(), 'shop_fax' => Configuration::get('PS_SHOP_FAX'), 'shop_phone' => Configuration::get('PS_SHOP_PHONE'), 'shop_details' => Configuration::get('PS_SHOP_DETAILS'), 'free_text' => $free_text]);
        return $this->smarty->fetch($this->get_template('supply-order-footer'));
    }
    /**
     * Rounds values of a SupplyOrderDetail object
     *
     * @param array|PrestaShopCollection $collection
     */
    protected function round_supply_order_details(&$collection)
    {
        $decimals = $this->currency->get_display_precision();
        foreach ($collection as $supply_order_detail) {
            /** @var SupplyOrderDetail $supplyOrderDetail */
            $supply_order_detail->unit_price_te = Tools::ps_round($supply_order_detail->unit_price_te, $decimals);
            $supply_order_detail->price_te = Tools::ps_round($supply_order_detail->price_te, $decimals);
            $supply_order_detail->discount_rate = Tools::ps_round($supply_order_detail->discount_rate, $decimals);
            $supply_order_detail->price_with_discount_te = Tools::ps_round($supply_order_detail->price_with_discount_te, $decimals);
            $supply_order_detail->tax_rate = Tools::ps_round($supply_order_detail->tax_rate, $decimals);
            $supply_order_detail->price_ti = Tools::ps_round($supply_order_detail->price_ti, $decimals);
        }
    }
    /**
     * Rounds values of a SupplyOrder object
     */
    protected function round_supply_order(Supply_Order &$supply_order)
    {
        $decimals = $this->currency->get_display_precision();
        $supply_order->total_te = Tools::ps_round($supply_order->total_te, $decimals);
        $supply_order->discount_value_te = Tools::ps_round($supply_order->discount_value_te, $decimals);
        $supply_order->total_with_discount_te = Tools::ps_round($supply_order->total_with_discount_te, $decimals);
        $supply_order->total_tax = Tools::ps_round($supply_order->total_tax, $decimals);
        $supply_order->total_ti = Tools::ps_round($supply_order->total_ti, $decimals);
    }
}