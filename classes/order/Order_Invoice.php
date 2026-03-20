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
 * Class OrderInvoiceCore
 */
class Order_Invoice_Core extends Object_Model
{
    public const TAX_EXCL = 0;
    public const TAX_INCL = 1;
    public const DETAIL = 2;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'order_invoice', 'primary' => 'id_order_invoice', 'fields' => ['id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'dbType' => 'int(11)'], 'number' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'dbType' => 'int(11)'], 'delivery_number' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'int(11)', 'dbNullable' => false], 'delivery_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'], 'total_discount_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_discount_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_paid_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_paid_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_products' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_products_wt' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_shipping_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_shipping_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'shipping_tax_computation_method' => ['type' => self::TYPE_INT, 'dbNullable' => false], 'total_wrapping_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_wrapping_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'shop_address' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'size' => 1000], 'invoice_address' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'size' => 1000], 'delivery_address' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'size' => 1000], 'note' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => Object_Model::SIZE_TEXT], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false]], 'keys' => ['order_invoice' => ['id_order' => ['type' => Object_Model::KEY, 'columns' => ['id_order']]]]];
    /** @var array Total paid cache */
    protected static $_total_paid_cache = [];
    /** @var int */
    public $id_order;
    /** @var int */
    public $number;
    /** @var int */
    public $delivery_number;
    /** @var string */
    public $delivery_date = '0000-00-00 00:00:00';
    /** @var float */
    public $total_discount_tax_excl;
    /** @var float */
    public $total_discount_tax_incl;
    /** @var float */
    public $total_paid_tax_excl;
    /** @var float */
    public $total_paid_tax_incl;
    /** @var float */
    public $total_products;
    /** @var float */
    public $total_products_wt;
    /** @var float */
    public $total_shipping_tax_excl;
    /** @var float */
    public $total_shipping_tax_incl;
    /** @var int */
    public $shipping_tax_computation_method;
    /** @var float */
    public $total_wrapping_tax_excl;
    /** @var float */
    public $total_wrapping_tax_incl;
    /** @var string shop address */
    public $shop_address;
    /** @var string invoice address */
    public $invoice_address;
    /** @var string delivery address */
    public $delivery_address;
    /** @var string note */
    public $note;
    /** @var string */
    public $date_add;
    /** @var Order */
    private $order;
    /**
     * @param int $idInvoice
     *
     * @return bool|OrderInvoice
     *
     * @throws PrestaShopException
     */
    public static function get_invoice_by_number($id_invoice)
    {
        if (is_numeric($id_invoice)) {
            $id_invoice = (int) $id_invoice;
        } elseif (is_string($id_invoice)) {
            $matches = [];
            if (preg_match('/^(?:' . Configuration::get('PS_INVOICE_PREFIX', Context::get_context()->language->id) . ')\s*([0-9]+)$/i', $id_invoice, $matches)) {
                $id_invoice = $matches[1];
            }
        }
        if (!$id_invoice) {
            return false;
        }
        $id_order_invoice = Db::read_only()->get_value((new Db_Query())->select('`id_order_invoice`')->from('order_invoice')->where('`number` = ' . (int) $id_invoice));
        return $id_order_invoice ? new Order_Invoice($id_order_invoice) : false;
    }
    /**
     * Returns all the order invoice that match the date interval
     *
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return OrderInvoice[] collection of OrderInvoice
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_by_date_interval($date_from, $date_to)
    {
        $order_invoice_list = Db::read_only()->get_array((new Db_Query())->select('oi.*')->from('order_invoice', 'oi')->left_join('orders', 'o', 'o.`id_order` = oi.`id_order`')->where('DATE_ADD(oi.`date_add`, INTERVAL -1 DAY) <= \'' . p_sql($date_to) . '\'')->where('oi.`date_add` >= \'' . p_sql($date_from) . '\' ' . Shop::add_sql_restriction(Shop::SHARE_ORDER, 'o'))->where('oi.`number` > 0')->order_by('oi.`date_add` ASC'));
        return Object_Model::hydrate_collection(Order_Invoice::class, $order_invoice_list);
    }
    /**
     * @param int $idOrderState
     *
     * @return OrderInvoice[] collection of OrderInvoice
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_by_status($id_order_state)
    {
        $order_invoice_list = Db::read_only()->get_array((new Db_Query())->select('oi.*')->from('order_invoice', 'oi')->left_join('orders', 'o', 'o.`id_order` = oi.`id_order`')->where('o.`current_state` = ' . (int) $id_order_state . ' ' . Shop::add_sql_restriction(Shop::SHARE_ORDER, 'o'))->where('oi.`number` > 0')->order_by('oi.`date_add` ASC'));
        return Object_Model::hydrate_collection(Order_Invoice::class, $order_invoice_list);
    }
    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return OrderInvoice[] collection of invoice
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_by_delivery_date_interval($date_from, $date_to)
    {
        $order_invoice_list = Db::read_only()->get_array((new Db_Query())->select('oi.*')->from('order_invoice', 'oi')->left_join('orders', 'o', 'o.`id_order` = oi.`id_order`')->where('DATE_ADD(oi.`delivery_date`, INTERVAL -1 DAY) <= \'' . p_sql($date_to) . '\'')->where('oi.`delivery_date` >= \'' . p_sql($date_from) . '\' ' . Shop::add_sql_restriction(Shop::SHARE_ORDER, 'o'))->order_by('oi.`delivery_date` ASC'));
        return Object_Model::hydrate_collection(Order_Invoice::class, $order_invoice_list);
    }
    /**
     * @param int $idOrderInvoice
     *
     * @return Carrier
     *
     * @throws PrestaShopException
     */
    public static function get_carrier($id_order_invoice)
    {
        if ($id_carrier = static::get_carrier_id($id_order_invoice)) {
            return new Carrier((int) $id_carrier);
        }
        return false;
    }
    /**
     * @param int $idOrderInvoice
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_carrier_id($id_order_invoice)
    {
        return Db::read_only()->get_value((new Db_Query())->select('`id_carrier`')->from('order_carrier')->where('`id_order_invoice` = ' . (int) $id_order_invoice));
    }
    /**
     * @param int $id
     *
     * @return OrderInvoice
     * @throws PrestaShopException
     */
    public static function retrieve_one_by_id($id)
    {
        $order_invoice = new Order_Invoice($id);
        if (!Validate::is_loaded_object($order_invoice)) {
            throw new Presta_Shop_Exception('Can\'t load Order Invoice object for id: ' . $id);
        }
        return $order_invoice;
    }
    /**
     * This method is used to fix shop addresses that cannot be fixed during upgrade process
     * (because uses the whole environnement of PS classes that is not available during upgrade).
     * This method should execute once on an upgraded PrestaShop to fix all OrderInvoices in one shot.
     * This method is triggered once during a (non bulk) creation of a PDF from an OrderInvoice that is not fixed yet.
     *
     * @throws PrestaShopException
     */
    public static function fix_all_shop_addresses(): void
    {
        $shop_ids = Shop::get_shops(false, null, true);
        $db = Db::get_instance();
        foreach ($shop_ids as $id_shop) {
            $address = static::get_current_formatted_shop_address($id_shop);
            $escaped_address = $db->escape($address, true, true);
            $db->execute('UPDATE `' . _DB_PREFIX_ . 'order_invoice` INNER JOIN `' . _DB_PREFIX_ . 'orders` USING (`id_order`)
                SET `shop_address` = \'' . $escaped_address . '\' WHERE `shop_address` IS NULL AND `id_shop` = ' . $id_shop);
        }
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        $order = new Order($this->id_order);
        $this->shop_address = static::get_current_formatted_shop_address($order->id_shop);
        return parent::add();
    }
    /**
     * @param int|null $idShop
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function get_current_formatted_shop_address($id_shop = null)
    {
        $address = new Address();
        $address->company = Configuration::get('PS_SHOP_NAME', null, null, $id_shop);
        $address->address1 = Configuration::get('PS_SHOP_ADDR1', null, null, $id_shop);
        $address->address2 = Configuration::get('PS_SHOP_ADDR2', null, null, $id_shop);
        $address->postcode = Configuration::get('PS_SHOP_CODE', null, null, $id_shop);
        $address->city = Configuration::get('PS_SHOP_CITY', null, null, $id_shop);
        $address->phone = Configuration::get('PS_SHOP_PHONE', null, null, $id_shop);
        $address->id_country = Configuration::get('PS_SHOP_COUNTRY_ID', null, null, $id_shop);
        $address->id_state = Configuration::get('PS_SHOP_STATE_ID', null, null, $id_shop);
        return Address_Format::generate_address($address, [], '<br />', ' ');
    }
    /**
     * Get order products
     *
     * @param array|bool $products
     * @param array|bool $selectedProducts
     * @param array|bool $selectedQty
     *
     * @return array Products with price, quantity (with taxe and without)
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_products($products = false, $selected_products = false, $selected_qty = false)
    {
        if (!$products) {
            $products = $this->get_products_detail();
        }
        $order = new Order($this->id_order);
        $customized_data = Product::get_all_customized_datas($order->id_cart);
        $result_array = [];
        foreach ($products as $row) {
            // Change qty if selected
            if ($selected_qty && $selected_products) {
                $row['product_quantity'] = 0;
                foreach ($selected_products as $key => $id_product) {
                    if ($row['id_order_detail'] == $id_product) {
                        $row['product_quantity'] = (int) $selected_qty[$key] ?? 0;
                    }
                }
                if (!$row['product_quantity']) {
                    continue;
                }
            }
            $this->set_product_image_informations($row);
            $this->set_product_current_stock($row);
            $this->set_product_customized_datas($row, $customized_data);
            // Add information for virtual product
            if (!empty($row['download_hash'])) {
                $row['filename'] = Product_Download::get_filename_from_id_product((int) $row['product_id']);
                // Get the display filename
                $row['display_filename'] = Product_Download::get_filename_from_filename($row['filename']);
            }
            $row['id_address_delivery'] = $order->id_address_delivery;
            /* Ecotax */
            $ecotax = (float) $row['ecotax'];
            $ecotax_rate = (float) $row['ecotax_tax_rate'];
            $row['ecotax_tax_excl'] = Tools::round_price($ecotax);
            $row['ecotax_tax_incl'] = Tools::round_price($ecotax * (1 + $ecotax_rate / 100));
            $row['ecotax_tax'] = $row['ecotax_tax_incl'] - $row['ecotax_tax_excl'];
            $row['total_ecotax_tax_excl'] = $row['ecotax_tax_excl'] * $row['product_quantity'];
            $row['total_ecotax_tax_incl'] = $row['ecotax_tax_incl'] * $row['product_quantity'];
            $row['total_ecotax_tax'] = $row['total_ecotax_tax_incl'] - $row['total_ecotax_tax_excl'];
            // Aliases
            $row['unit_price_tax_excl_including_ecotax'] = $row['unit_price_tax_excl'];
            $row['unit_price_tax_incl_including_ecotax'] = $row['unit_price_tax_incl'];
            $row['total_price_tax_excl_including_ecotax'] = $row['total_price_tax_excl'];
            $row['total_price_tax_incl_including_ecotax'] = $row['total_price_tax_incl'];
            /* Stock product */
            $result_array[(int) $row['id_order_detail']] = $row;
        }
        if ($customized_data) {
            Product::add_customization_price($result_array, $customized_data);
        }
        return $result_array;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_products_detail()
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('order_detail', 'od')->left_join('product', 'p', 'p.`id_product` = od.`product_id`')->left_join('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = od.id_shop')->where('od.`id_order` = ' . (int) $this->id_order)->where($this->id && $this->number ? 'od.`id_order_invoice` = ' . (int) $this->id : '')->order_by('od.`product_name`'));
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function display_tax_bases_in_product_taxes_breakdown()
    {
        return !$this->use_one_after_another_tax_computation_method();
    }
    /**
     * This method returns true if at least one order details uses the
     * One After Another tax computation method.
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function use_one_after_another_tax_computation_method()
    {
        // if one of the order details use the tax computation method the display will be different
        if (Db::read_only()->get_value((new Db_Query())->select('od.`tax_computation_method`')->from('order_detail_tax', 'odt')->left_join('order_detail', 'od', 'od.`id_order_detail` = odt.`id_order_detail`')->where('od.`id_order` = ' . (int) $this->id_order)->where('od.`id_order_invoice` = ' . (int) $this->id)->where('od.`tax_computation_method` = ' . (int) Tax_Calculator::ONE_AFTER_ANOTHER_METHOD))) {
            return true;
        }
        return (bool) Configuration::get('PS_INVOICE_TAXES_BREAKDOWN');
    }
    /**
     * @param Order|null $order
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_product_taxes_breakdown($order = null)
    {
        if (!$order) {
            $order = $this->get_order();
        }
        $sum_composite_taxes = !$this->use_one_after_another_tax_computation_method();
        // $breakdown will be an array with tax rates as keys and at least the columns:
        // 	- 'total_price_tax_excl'
        // 	- 'total_amount'
        $breakdown = [];
        $details = $order->get_product_taxes_details();
        if ($sum_composite_taxes) {
            $grouped_details = [];
            foreach ($details as $row) {
                $order_detail_id = $row['id_order_detail'];
                if (!isset($grouped_details[$order_detail_id])) {
                    $grouped_details[$order_detail_id] = ['tax_rate' => 0.0, 'total_tax_base' => 0.0, 'total_amount' => 0.0, 'id_tax' => (int) $row['id_tax']];
                }
                $grouped_details[$order_detail_id]['tax_rate'] += (float) $row['tax_rate'];
                $grouped_details[$order_detail_id]['total_tax_base'] += (float) $row['total_tax_base'];
                $grouped_details[$order_detail_id]['total_amount'] += (float) $row['total_amount'];
            }
            $details = $grouped_details;
        }
        foreach ($details as $row) {
            $rate = round((float) $row['tax_rate'], 3);
            $key = (string) $rate;
            if (!isset($breakdown[$key])) {
                $breakdown[$key] = ['total_price_tax_excl' => 0.0, 'total_amount' => 0.0, 'id_tax' => (int) $row['id_tax'], 'rate' => $rate];
            }
            $breakdown[$key]['total_price_tax_excl'] += (float) $row['total_tax_base'];
            $breakdown[$key]['total_amount'] += (float) $row['total_amount'];
        }
        ksort($breakdown, SORT_NUMERIC);
        return $breakdown;
    }
    /**
     * @return Order
     *
     * @throws PrestaShopException
     */
    public function get_order()
    {
        if (!$this->order) {
            $this->order = new Order($this->id_order);
        }
        return $this->order;
    }
    /**
     * Returns the shipping taxes breakdown
     *
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_shipping_taxes_breakdown(Order $order)
    {
        // No shipping breakdown if no shipping!
        if ($this->total_shipping_tax_excl == 0) {
            return [];
        }
        // No shipping breakdown if it's free!
        foreach ($order->get_cart_rules() as $cart_rule) {
            if ($cart_rule['free_shipping']) {
                return [];
            }
        }
        $shipping_tax_amount = $this->total_shipping_tax_incl - $this->total_shipping_tax_excl;
        $shipping_breakdown = [];
        if (Configuration::get('PS_INVOICE_TAXES_BREAKDOWN') || Carrier::use_proportionate_tax()) {
            $shipping_breakdown = Db::read_only()->get_array((new Db_Query())->select('t.`id_tax`, t.`rate`, oit.`amount` AS `total_amount`')->from('tax', 't')->inner_join('order_invoice_tax', 'oit', 'oit.`id_tax` = t.`id_tax`')->where('oit.`type` = "shipping"')->where('oit.`id_order_invoice` = ' . (int) $this->id));
            if ($shipping_breakdown) {
                $sum_of_split_taxes = 0;
                $sum_of_tax_bases = 0;
                foreach ($shipping_breakdown as &$row) {
                    if (Carrier::use_proportionate_tax()) {
                        $rate = (float) $row['rate'];
                        $row['total_tax_excl'] = $rate !== 0.0 ? round($row['total_amount'] / $rate * 100, _TB_PRICE_DATABASE_PRECISION_) : round($row['total_amount'], _TB_PRICE_DATABASE_PRECISION_);
                        $sum_of_tax_bases += $row['total_tax_excl'];
                    } else {
                        $row['total_tax_excl'] = $this->total_shipping_tax_excl;
                    }
                    $row['total_amount'] = round($row['total_amount'], _TB_PRICE_DATABASE_PRECISION_);
                    $sum_of_split_taxes += $row['total_amount'];
                }
                unset($row);
                $delta_amount = $shipping_tax_amount - $sum_of_split_taxes;
                if ($delta_amount != 0) {
                    Tools::spread_amount($delta_amount, _TB_PRICE_DATABASE_PRECISION_, $shipping_breakdown, 'total_amount');
                }
                $delta_base = $this->total_shipping_tax_excl - $sum_of_tax_bases;
                if ($delta_base != 0) {
                    Tools::spread_amount($delta_base, _TB_PRICE_DATABASE_PRECISION_, $shipping_breakdown, 'total_tax_excl');
                }
            }
        }
        if (!$shipping_breakdown) {
            return [['total_tax_excl' => $this->total_shipping_tax_excl, 'rate' => $order->carrier_tax_rate, 'total_amount' => $shipping_tax_amount, 'id_tax' => null]];
        }
        return $shipping_breakdown;
    }
    /**
     * Returns the wrapping taxes breakdown
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_wrapping_taxes_breakdown()
    {
        if ($this->total_wrapping_tax_excl == 0) {
            return [];
        }
        $wrapping_tax_amount = $this->total_wrapping_tax_incl - $this->total_wrapping_tax_excl;
        $wrapping_breakdown = Db::read_only()->get_array((new Db_Query())->select('t.`id_tax`, t.`rate`, oit.`amount` AS `total_amount`')->from('tax', 't')->inner_join('order_invoice_tax', 'oit', 'oit.`id_tax` = t.`id_tax`')->where('oit.`type` = "wrapping"')->where('oit.`id_order_invoice` = ' . (int) $this->id));
        $sum_of_split_taxes = 0;
        $sum_of_tax_bases = 0;
        $total_tax_rate = 0;
        foreach ($wrapping_breakdown as &$row) {
            if (Carrier::use_proportionate_tax()) {
                $row['total_tax_excl'] = round($row['total_amount'] / $row['rate'] * 100, _TB_PRICE_DATABASE_PRECISION_);
                $sum_of_tax_bases += $row['total_tax_excl'];
            } else {
                $row['total_tax_excl'] = $this->total_wrapping_tax_excl;
            }
            $row['total_amount'] = round($row['total_amount'], _TB_PRICE_DATABASE_PRECISION_);
            $sum_of_split_taxes += $row['total_amount'];
            $total_tax_rate += (float) $row['rate'];
        }
        unset($row);
        $delta_amount = $wrapping_tax_amount - $sum_of_split_taxes;
        if ($delta_amount != 0) {
            Tools::spread_amount($delta_amount, _TB_PRICE_DATABASE_PRECISION_, $wrapping_breakdown, 'total_amount');
        }
        $delta_base = $this->total_wrapping_tax_excl - $sum_of_tax_bases;
        if ($delta_base != 0) {
            Tools::spread_amount($delta_base, _TB_PRICE_DATABASE_PRECISION_, $wrapping_breakdown, 'total_tax_excl');
        }
        if (!Configuration::get('PS_INVOICE_TAXES_BREAKDOWN') && !Carrier::use_proportionate_tax()) {
            return [['total_tax_excl' => $this->total_wrapping_tax_excl, 'rate' => $total_tax_rate, 'total_amount' => $wrapping_tax_amount]];
        }
        return $wrapping_breakdown;
    }
    /**
     * Returns the ecotax taxes breakdown
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_eco_tax_taxes_breakdown()
    {
        $result = Db::read_only()->get_array((new Db_Query())->select('`ecotax_tax_rate` AS `rate`, `ecotax` AS `ecotax_tax_excl`, `product_quantity`')->from('order_detail')->where('`id_order` = ' . (int) $this->id_order)->where('`id_order_invoice` = ' . (int) $this->id));
        $taxes = [];
        foreach ($result as $row) {
            if ($row['ecotax_tax_excl'] > 0) {
                $row['ecotax_tax_incl'] = round($row['ecotax_tax_excl'] * (1 + $row['rate'] / 100), _TB_PRICE_DATABASE_PRECISION_);
                $row['ecotax_tax_excl'] *= $row['product_quantity'];
                $row['ecotax_tax_incl'] *= $row['product_quantity'];
                if (isset($taxes[$row['rate']])) {
                    $old_row = $taxes[$row['rate']];
                    $old_row['ecotax_tax_excl'] += $row['ecotax_tax_excl'];
                    $old_row['ecotax_tax_incl'] += $row['ecotax_tax_incl'];
                } else {
                    $taxes[$row['rate']] = $row;
                }
            }
        }
        return array_values($taxes);
    }
    /**
     * Rest Paid
     *
     * @return float Rest Paid
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_rest_paid()
    {
        return Tools::round_price($this->total_paid_tax_incl + $this->get_sibling_total() - $this->get_total_paid());
    }
    /**
     * Return total to paid of sibling invoices
     *
     * @param int $mod TAX_EXCL, TAX_INCL, DETAIL
     *
     * @return array|float
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_sibling_total($mod = self::TAX_INCL)
    {
        $query = new Db_Query();
        $query->select('SUM(oi.total_paid_tax_incl) as total_paid_tax_incl, SUM(oi.total_paid_tax_excl) as total_paid_tax_excl');
        $query->from('order_invoice_payment', 'oip1');
        $query->inner_join('order_invoice_payment', 'oip2', 'oip2.id_order_payment = oip1.id_order_payment AND oip2.id_order_invoice <> oip1.id_order_invoice AND oip1.id_order = oip2.id_order');
        $query->left_join('order_invoice', 'oi', 'oi.id_order_invoice = oip2.id_order_invoice');
        $query->where('oip1.id_order_invoice = ' . $this->id);
        $row = Db::read_only()->get_row($query);
        return match ($mod) {
            static::TAX_EXCL => (float) $row['total_paid_tax_excl'],
            static::TAX_INCL => (float) $row['total_paid_tax_incl'],
            default => $row,
        };
    }
    /**
     * Amounts of payments
     *
     * @return float Total paid
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_total_paid()
    {
        $cache_id = 'order_invoice_paid_' . (int) $this->id;
        if (!Cache::is_stored($cache_id)) {
            $amount = 0;
            $payments = Order_Payment::get_by_invoice_id($this->id);
            foreach ($payments as $payment) {
                /** @var OrderPayment $payment */
                $amount += $payment->amount;
            }
            Cache::store($cache_id, $amount);
            return $amount;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Return collection of order invoice object linked to the payments of the current order invoice object
     *
     * @return PrestaShopCollection|array Collection of OrderInvoice or empty array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_sibling()
    {
        $query = new Db_Query();
        $query->select('oip2.id_order_invoice');
        $query->from('order_invoice_payment', 'oip1');
        $query->inner_join('order_invoice_payment', 'oip2', 'oip2.id_order_payment = oip1.id_order_payment AND oip2.id_order_invoice <> oip1.id_order_invoice AND oip1.id_order = oip2.id_order');
        $query->where('oip1.id_order_invoice = ' . $this->id);
        $invoices = Db::read_only()->get_array($query);
        if (!$invoices) {
            return [];
        }
        $invoice_list = [];
        foreach ($invoices as $invoice) {
            $invoice_list[] = $invoice['id_order_invoice'];
        }
        $payments = new Presta_Shop_Collection('OrderInvoice');
        $payments->where('id_order_invoice', 'IN', $invoice_list);
        return $payments;
    }
    /**
     * Get global rest to paid
     *    This method will return something different of the method getRestPaid if
     *    there is an other invoice linked to the payments of the current invoice
     *
     * @return float
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_global_rest_paid()
    {
        static $cache;
        if (!isset($cache[$this->id])) {
            $res = Db::read_only()->get_row('SELECT SUM(sub.paid) paid, SUM(sub.to_paid) to_paid
			FROM (
				SELECT
					op.amount AS paid, SUM(oi.total_paid_tax_incl) to_paid
				FROM `' . _DB_PREFIX_ . 'order_invoice_payment` oip1
				INNER JOIN `' . _DB_PREFIX_ . 'order_invoice_payment` oip2
					ON oip2.id_order_payment = oip1.id_order_payment
				INNER JOIN `' . _DB_PREFIX_ . 'order_invoice` oi
					ON oi.id_order_invoice = oip2.id_order_invoice
				INNER JOIN `' . _DB_PREFIX_ . 'order_payment` op
					ON op.id_order_payment = oip2.id_order_payment
				WHERE oip1.id_order_invoice = ' . (int) $this->id . '
				GROUP BY op.id_order_payment
			) sub');
            $cache[$this->id] = $res['to_paid'] - $res['paid'];
        }
        return $cache[$this->id];
    }
    /**
     * @return bool Is paid ?
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function is_paid()
    {
        return (string) round($this->get_total_paid(), _TB_PRICE_DATABASE_PRECISION_) === (string) round($this->total_paid_tax_incl, _TB_PRICE_DATABASE_PRECISION_);
    }
    /**
     * @return PrestaShopCollection Collection of Order payment
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_order_payment_collection()
    {
        return Order_Payment::get_by_invoice_id($this->id);
    }
    /**
     * Get the formatted number of invoice
     *
     * @param int $idLang for invoice_prefix
     * @param int|null $idShop
     *
     * @return string
     * @throws PrestaShopException
     */
    public function get_invoice_number_formatted($id_lang, $id_shop = null)
    {
        $invoice_formatted_number = Hook::get_first_response('actionInvoiceNumberFormatted', [static::class => $this, 'id_lang' => (int) $id_lang, 'id_shop' => (int) $id_shop, 'number' => (int) $this->number]);
        if (!empty($invoice_formatted_number)) {
            return $invoice_formatted_number;
        }
        $format = '%1$s%2$06d';
        if (Configuration::get('PS_INVOICE_USE_YEAR')) {
            $format = Configuration::get('PS_INVOICE_YEAR_POS') ? '%1$s%3$s/%2$06d' : '%1$s%2$06d/%3$s';
        }
        return sprintf($format, Configuration::get('PS_INVOICE_PREFIX', (int) $id_lang, null, (int) $id_shop), $this->number, date('Y', strtotime($this->date_add)));
    }
    /**
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function save_carrier_tax_calculator(array $taxes_amount)
    {
        $is_correct = true;
        foreach ($taxes_amount as $id_tax => $amount) {
            $is_correct = Db::get_instance()->insert('order_invoice_tax', ['id_order_invoice' => (int) $this->id, 'type' => 'shipping', 'id_tax' => (int) $id_tax, 'amount' => round($amount, _TB_PRICE_DATABASE_PRECISION_)]) && $is_correct;
        }
        return $is_correct;
    }
    /**
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function save_wrapping_tax_calculator(array $taxes_amount)
    {
        $is_correct = true;
        foreach ($taxes_amount as $id_tax => $amount) {
            $is_correct = Db::get_instance()->insert('order_invoice_tax', ['id_order_invoice' => (int) $this->id, 'type' => 'wrapping', 'id_tax' => (int) $id_tax, 'amount' => (float) $amount]) && $is_correct;
        }
        return $is_correct;
    }
    /**
     * This method allow to add image information on a product detail
     *
     * @param array &$product
     *
     * @throws PrestaShopException
     */
    protected function set_product_image_informations(&$product)
    {
        $connection = Db::read_only();
        if (isset($product['product_attribute_id']) && $product['product_attribute_id']) {
            $id_image = $connection->get_value((new Db_Query())->select('image_shop.`id_image`')->from('product_attribute_image', 'pai')->join(Shop::add_sql_association('image', 'pai', true))->where('`id_product_attribute` = ' . (int) $product['product_attribute_id']));
        }
        if (!isset($id_image) || !$id_image) {
            $id_image = $connection->get_value((new Db_Query())->select('image_shop.`id_image`')->from('image', 'i')->join(Shop::add_sql_association('image', 'i', true, 'image_shop.`cover` = 1'))->where('i.`id_product` = ' . (int) $product['product_id']));
        }
        $product['image'] = null;
        $product['image_size'] = null;
        if ($id_image) {
            $product['image'] = new Image($id_image);
        }
    }
    /**
     * This method allow to add stock information on a product detail
     *
     * @param array &$product
     *
     * @throws PrestaShopException
     */
    protected function set_product_current_stock(&$product)
    {
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && (int) $product['advanced_stock_management'] == 1 && (int) $product['id_warehouse'] > 0) {
            $product['current_stock'] = Stock_Manager_Factory::get_manager()->get_product_physical_quantities($product['product_id'], $product['product_attribute_id'], null, true);
        } else {
            $product['current_stock'] = '--';
        }
    }
    /**
     * @param array $product
     * @param array $customizedData
     */
    protected function set_product_customized_datas(&$product, $customized_data)
    {
        $product['customizedDatas'] = null;
        if (isset($customized_data[$product['product_id']][$product['product_attribute_id']])) {
            $product['customizedDatas'] = $customized_data[$product['product_id']][$product['product_attribute_id']];
        } else {
            $product['customizationQuantityTotal'] = 0;
        }
    }
}