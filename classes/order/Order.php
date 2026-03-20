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
 * Class OrderCore
 */
class Order_Core extends Object_Model
{
    public const ROUND_ITEM = 1;
    public const ROUND_LINE = 2;
    public const ROUND_TOTAL = 3;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'orders', 'primary' => 'id_order', 'fields' => ['reference' => ['type' => self::TYPE_STRING, 'size' => 9], 'id_shop_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbDefault' => '1'], 'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbDefault' => '1'], 'id_carrier' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_cart' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_currency' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_address_delivery' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_address_invoice' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'current_state' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false], 'secure_key' => ['type' => self::TYPE_STRING, 'validate' => 'isMd5', 'size' => 32, 'dbDefault' => '-1'], 'payment' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true], 'conversion_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true, 'size' => 13, 'decimals' => 6, 'dbDefault' => '1.000000'], 'module' => ['type' => self::TYPE_STRING, 'validate' => 'isModuleName', 'required' => true, 'size' => 64, 'dbNullable' => true], 'recyclable' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'], 'gift' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'], 'gift_message' => ['type' => self::TYPE_STRING, 'validate' => 'isMessage', 'size' => Object_Model::SIZE_TEXT], 'mobile_theme' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'], 'shipping_number' => ['type' => self::TYPE_STRING, 'validate' => 'isTrackingNumber', 'size' => 64], 'total_discounts' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_discounts_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_discounts_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_paid' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbDefault' => '0.000000'], 'total_paid_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_paid_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_paid_real' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => false, 'dbDefault' => '0.000000', 'dbNullable' => false], 'total_products' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbDefault' => '0.000000'], 'total_products_wt' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbDefault' => '0.000000'], 'total_shipping' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_shipping_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_shipping_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'carrier_tax_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'size' => 10, 'decimals' => 3, 'dbDefault' => '0.000'], 'total_wrapping' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_wrapping_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_wrapping_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'round_mode' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'tinyint(1)', 'dbDefault' => '2'], 'round_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'], 'invoice_number' => ['type' => self::TYPE_INT, 'dbDefault' => '0'], 'delivery_number' => ['type' => self::TYPE_INT, 'dbDefault' => '0'], 'invoice_date' => ['type' => self::TYPE_DATE, 'dbNullable' => false], 'delivery_date' => ['type' => self::TYPE_DATE, 'dbNullable' => false], 'valid' => ['type' => self::TYPE_BOOL, 'dbType' => 'int(1) unsigned', 'dbDefault' => '0'], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false]], 'keys' => ['orders' => ['current_state' => ['type' => Object_Model::KEY, 'columns' => ['current_state']], 'date_add' => ['type' => Object_Model::KEY, 'columns' => ['date_add']], 'id_address_delivery' => ['type' => Object_Model::KEY, 'columns' => ['id_address_delivery']], 'id_address_invoice' => ['type' => Object_Model::KEY, 'columns' => ['id_address_invoice']], 'id_carrier' => ['type' => Object_Model::KEY, 'columns' => ['id_carrier']], 'id_cart' => ['type' => Object_Model::KEY, 'columns' => ['id_cart']], 'id_currency' => ['type' => Object_Model::KEY, 'columns' => ['id_currency']], 'id_customer' => ['type' => Object_Model::KEY, 'columns' => ['id_customer']], 'id_lang' => ['type' => Object_Model::KEY, 'columns' => ['id_lang']], 'id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']], 'id_shop_group' => ['type' => Object_Model::KEY, 'columns' => ['id_shop_group']], 'invoice_number' => ['type' => Object_Model::KEY, 'columns' => ['invoice_number']], 'reference' => ['type' => Object_Model::KEY, 'columns' => ['reference']]]]];
    /** @var int Delivery address id */
    public $id_address_delivery;
    /** @var int Invoice address id */
    public $id_address_invoice;
    /** @var int $id_shop_group */
    public $id_shop_group;
    /** @var int $id_shop */
    public $id_shop;
    /** @var int Cart id */
    public $id_cart;
    /** @var int Currency id */
    public $id_currency;
    /** @var int Language id */
    public $id_lang;
    /** @var int Customer id */
    public $id_customer;
    /** @var int Carrier id */
    public $id_carrier;
    /** @var int Order Status id */
    public $current_state;
    /** @var string Secure key */
    public $secure_key;
    /** @var string Payment method */
    public $payment;
    /** @var string Payment module */
    public $module;
    /** @var float Currency exchange rate */
    public $conversion_rate;
    /** @var bool Customer is ok for a recyclable package */
    public $recyclable = 1;
    /** @var bool True if the customer wants a gift wrapping */
    public $gift = 0;
    /** @var string Gift message if specified */
    public $gift_message;
    /** @var bool Mobile Theme */
    public $mobile_theme;
    /**
     * @var string Shipping number
     * @deprecated 1.5.0.4
     * @see OrderCarrier->tracking_number
     */
    public $shipping_number;
    /** @var float Discounts total */
    public $total_discounts;
    /** @var float $total_discounts_tax_incl */
    public $total_discounts_tax_incl;
    /** @var float $total_discounts_tax_excl */
    public $total_discounts_tax_excl;
    /** @var float Total to pay */
    public $total_paid;
    /** @var float Total to pay tax included */
    public $total_paid_tax_incl;
    /** @var float Total to pay tax excluded */
    public $total_paid_tax_excl;
    /**
     * @var float Total really paid
     * @deprecated 1.5.0.1 use Order::getTotalPaid() method instead
     */
    public $total_paid_real;
    /** @var float Products total */
    public $total_products;
    /** @var float Products total tax included */
    public $total_products_wt;
    /** @var float Shipping total */
    public $total_shipping;
    /** @var float Shipping total tax included */
    public $total_shipping_tax_incl;
    /** @var float Shipping total tax excluded */
    public $total_shipping_tax_excl;
    /** @var float Shipping tax rate */
    public $carrier_tax_rate;
    /** @var float Wrapping total */
    public $total_wrapping;
    /** @var float Wrapping total tax included */
    public $total_wrapping_tax_incl;
    /** @var float Wrapping total tax excluded */
    public $total_wrapping_tax_excl;
    /** @var int Invoice number */
    public $invoice_number;
    /** @var int Delivery number */
    public $delivery_number;
    /** @var string Invoice creation date */
    public $invoice_date;
    /** @var string Delivery creation date */
    public $delivery_date;
    /** @var bool Order validity: current order status is logable (usually paid and not canceled) */
    public $valid;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var string Order reference, this reference is not unique, but unique for a payment */
    public $reference;
    /** @var int Round mode method used for this order */
    public $round_mode;
    /** @var int Round type method used for this order */
    public $round_type;
    /** @var array Webservice parameters */
    protected $webservice_parameters = ['objectMethods' => ['add' => 'addWs'], 'objectNodeName' => 'order', 'objectsNodeName' => 'orders', 'fields' => ['id_address_delivery' => ['xlink_resource' => 'addresses'], 'id_address_invoice' => ['xlink_resource' => 'addresses'], 'id_cart' => ['xlink_resource' => 'carts'], 'id_currency' => ['xlink_resource' => 'currencies'], 'id_lang' => ['xlink_resource' => 'languages'], 'id_customer' => ['xlink_resource' => 'customers'], 'id_carrier' => ['xlink_resource' => 'carriers'], 'current_state' => ['xlink_resource' => 'order_states', 'setter' => 'setWsCurrentState'], 'module' => ['required' => true], 'invoice_number' => [], 'invoice_date' => [], 'delivery_number' => [], 'delivery_date' => [], 'valid' => [], 'date_add' => [], 'date_upd' => [], 'shipping_number' => ['getter' => 'getWsShippingNumber', 'setter' => 'setWsShippingNumber']], 'associations' => ['order_rows' => ['resource' => 'order_row', 'getter' => 'getWsOrderRows', 'setter' => false, 'virtual_entity' => true, 'fields' => ['id' => [], 'product_id' => ['required' => true], 'product_attribute_id' => ['required' => true], 'product_quantity' => ['required' => true], 'product_name' => ['setter' => false], 'product_reference' => ['setter' => false], 'product_ean13' => ['setter' => false], 'product_upc' => ['setter' => false], 'product_price' => ['setter' => false], 'unit_price_tax_incl' => ['setter' => false], 'unit_price_tax_excl' => ['setter' => false], 'is_pack' => ['setter' => false]]]]];
    /**
     * used to cache order customer
     */
    protected $cache_customer;
    /**
     * @var int
     */
    protected $_tax_calculation_method = PS_TAX_EXC;
    /**
     * @var array
     */
    protected static $_history_cache = [];
    protected ?bool $cache_can_edit_products = null;
    /**
     * OrderCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     *
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);
        $is_admin = is_object(Context::get_context()->controller) && Context::get_context()->controller->controller_type == 'admin';
        if ($this->id_customer && !$is_admin) {
            $customer = new Customer((int) $this->id_customer);
            $this->_tax_calculation_method = Group::get_price_display_method((int) $customer->id_default_group);
        } else {
            $this->_tax_calculation_method = Group::get_default_price_display_method();
        }
    }
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_fields()
    {
        if (!$this->id_lang) {
            $this->id_lang = Configuration::get('PS_LANG_DEFAULT', null, null, $this->id_shop);
        }
        return parent::get_fields();
    }
    /**
     * Add this Order
     *
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = true)
    {
        if (parent::add($auto_date, $null_values)) {
            return Specific_Price::delete_by_id_cart($this->id_cart);
        }
        return false;
    }
    /**
     * This function rounds all the decimal properties of this Object
     *
     * @deprecated 1.1.0
     */
    public function round_amounts(): void
    {
        Tools::display_as_deprecated('No longer needed, ObjectModel rounds now its self.');
    }
    /**
     * @return int
     */
    public function get_tax_calculation_method()
    {
        return (int) $this->_tax_calculation_method;
    }
    /**
     * Does NOT delete a product but "cancel" it (which means return/refund/delete it depending of the case)
     *
     * @param int $quantity
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function delete_product(Order $order, Order_Detail $order_detail, $quantity)
    {
        if (!(int) $this->get_current_state() || !Validate::is_loaded_object($order_detail)) {
            return false;
        }
        if ($this->has_been_delivered()) {
            if (!Configuration::get('PS_ORDER_RETURN', null, null, $this->id_shop)) {
                throw new Presta_Shop_Exception('PS_ORDER_RETURN is not defined in table configuration');
            }
            $order_detail->product_quantity_return += (int) $quantity;
            return $order_detail->update();
        }
        if ($this->has_been_paid()) {
            $order_detail->product_quantity_refunded += (int) $quantity;
            return $order_detail->update();
        }
        return $this->_delete_product($order_detail, (int) $quantity);
    }
    /**
     * This function return products of the orders
     * It's similar to Order::getProducts but with similar outputs of Cart::getProducts
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_cart_products()
    {
        $product_id_list = [];
        $products = $this->get_products();
        foreach ($products as &$product) {
            $product['id_product_attribute'] = $product['product_attribute_id'];
            $product['cart_quantity'] = $product['product_quantity'];
            $product_id_list[] = $this->id_address_delivery . '_' . $product['product_id'] . '_' . $product['product_attribute_id'] . '_' . ($product['id_customization'] ?? '0');
        }
        unset($product);
        $product_list = [];
        foreach ($products as $product) {
            $key = $this->id_address_delivery . '_' . $product['id_product'] . '_' . ($product['id_product_attribute'] ?? '0') . '_' . ($product['id_customization'] ?? '0');
            if (in_array($key, $product_id_list)) {
                $product_list[] = $product;
            }
        }
        return $product_list;
    }
    /**
     * DOES delete the product
     *
     * @param OrderDetail $orderDetail
     * @param int $quantity
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function _delete_product($order_detail, $quantity)
    {
        $product_price_tax_excl = round($order_detail->unit_price_tax_excl, _TB_PRICE_DATABASE_PRECISION_) * (int) $quantity;
        $product_price_tax_incl = round($order_detail->unit_price_tax_incl, _TB_PRICE_DATABASE_PRECISION_) * (int) $quantity;
        /* Update cart */
        $cart = new Cart($this->id_cart);
        $cart->update_qty($quantity, $order_detail->product_id, $order_detail->product_attribute_id, false, 'down');
        // customization are deleted in deleteCustomization
        $cart->update();
        /* Update order */
        $shipping_diff_tax_incl = $this->total_shipping_tax_incl - round($cart->get_package_shipping_cost($this->id_carrier, true, null, $this->get_cart_products()), _TB_PRICE_DATABASE_PRECISION_);
        $shipping_diff_tax_excl = $this->total_shipping_tax_excl - round($cart->get_package_shipping_cost($this->id_carrier, false, null, $this->get_cart_products()), _TB_PRICE_DATABASE_PRECISION_);
        $this->total_shipping -= $shipping_diff_tax_incl;
        $this->total_shipping_tax_excl -= $shipping_diff_tax_excl;
        $this->total_shipping_tax_incl -= $shipping_diff_tax_incl;
        $this->total_products -= $product_price_tax_excl;
        $this->total_products_wt -= $product_price_tax_incl;
        $this->total_paid -= $product_price_tax_incl + $shipping_diff_tax_incl;
        $this->total_paid_tax_incl -= $product_price_tax_incl + $shipping_diff_tax_incl;
        $this->total_paid_tax_excl -= $product_price_tax_excl + $shipping_diff_tax_excl;
        $fields = ['total_shipping', 'total_shipping_tax_excl', 'total_shipping_tax_incl', 'total_products', 'total_products_wt', 'total_paid', 'total_paid_tax_incl', 'total_paid_tax_excl'];
        /* Prevent from floating precision issues */
        foreach ($fields as $field) {
            if ($this->{$field} < 0) {
                $this->{$field} = 0;
            }
            $this->{$field} = round($this->{$field}, _TB_PRICE_DATABASE_PRECISION_);
        }
        /* Update order detail */
        $order_detail->product_quantity -= (int) $quantity;
        if ($order_detail->product_quantity == 0) {
            if (!$order_detail->delete()) {
                return false;
            }
            if (count($this->get_products_detail()) == 0) {
                $history = new Order_History();
                $history->id_order = (int) $this->id;
                $history->change_id_order_state(Configuration::get('PS_OS_CANCELED'), $this);
                if (!$history->add_withemail()) {
                    return false;
                }
            }
            return $this->update();
        }
        $order_detail->total_price_tax_incl -= $product_price_tax_incl;
        $order_detail->total_price_tax_excl -= $product_price_tax_excl;
        $order_detail->total_shipping_price_tax_incl -= $shipping_diff_tax_incl;
        $order_detail->total_shipping_price_tax_excl -= $shipping_diff_tax_excl;
        return $order_detail->update() && $this->update();
    }
    /**
     * @param int $idCustomization
     * @param int $quantity
     * @param OrderDetail $orderDetail
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function delete_customization($id_customization, $quantity, $order_detail)
    {
        if (!(int) $this->get_current_state()) {
            return false;
        }
        $conn = Db::get_instance();
        if ($this->has_been_delivered()) {
            return $conn->update('customization', ['quantity_returned' => ['type' => 'sql', 'value' => '`quantity_returned` + ' . (int) $quantity]], '`id_customization` = ' . (int) $id_customization . ' AND `id_cart` = ' . (int) $this->id_cart . ' AND `id_product` = ' . (int) $order_detail->product_id);
        }
        if ($this->has_been_paid()) {
            return $conn->update('customization', ['quantity_refunded' => ['type' => 'sql', 'value' => '`quantity_refunded` + ' . (int) $quantity]], '`id_customization` = ' . (int) $id_customization . ' AND `id_cart` = ' . (int) $this->id_cart . ' AND `id_product` = ' . (int) $order_detail->product_id);
        }
        if (!$conn->update('customization', ['quantity' => ['type' => 'sql', 'value' => '`quantity` - ' . (int) $quantity]], '`id_customization` = ' . (int) $id_customization . ' AND `id_cart` = ' . (int) $this->id_cart . ' AND `id_product` = ' . (int) $order_detail->product_id)) {
            return false;
        }
        if (!$conn->delete('customization', '`quantity` = 0')) {
            return false;
        }
        return $this->_delete_product($order_detail, (int) $quantity);
    }
    /**
     * Get order history
     *
     * @param int $idLang Language id
     * @param bool|int $idOrderState Filter a specific order status
     * @param bool|int $noHidden Filter no hidden status
     * @param int $filters Flag to use specific field filter
     *
     * @return array[] History entries ordered by date DESC
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_history($id_lang, $id_order_state = false, $no_hidden = false, $filters = 0)
    {
        if (!$id_order_state) {
            $id_order_state = 0;
        }
        $logable = false;
        $delivery = false;
        $paid = false;
        $shipped = false;
        if ($filters > 0) {
            if ($filters & Order_State::FLAG_NO_HIDDEN) {
                $no_hidden = true;
            }
            if ($filters & Order_State::FLAG_DELIVERY) {
                $delivery = true;
            }
            if ($filters & Order_State::FLAG_LOGABLE) {
                $logable = true;
            }
            if ($filters & Order_State::FLAG_PAID) {
                $paid = true;
            }
            if ($filters & Order_State::FLAG_SHIPPED) {
                $shipped = true;
            }
        }
        if (!isset(static::$_history_cache[$this->id . '_' . $id_order_state . '_' . $filters]) || $no_hidden) {
            $id_lang = $id_lang ? (int) $id_lang : 'o.`id_lang`';
            $result = Db::read_only()->get_array((new Db_Query())->select('os.*, oh.*, e.`firstname` AS `employee_firstname`, e.`lastname` AS `employee_lastname`, osl.`name` AS `ostate_name`')->from('orders', 'o')->left_join('order_history', 'oh', 'o.`id_order` = oh.`id_order`')->left_join('order_state', 'os', 'os.`id_order_state` = oh.`id_order_state`')->left_join('order_state_lang', 'osl', 'os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = ' . (int) $id_lang)->left_join('employee', 'e', 'e.`id_employee` = oh.`id_employee`')->where('oh.`id_order` = ' . (int) $this->id)->where($no_hidden ? 'os.`hidden` = 0' : '')->where($logable ? 'os.`logable` = 1' : '')->where($delivery ? 'os.`delivery` = 1' : '')->where($paid ? 'os.`paid` = 1' : '')->where($shipped ? 'os.`shipped` = 1' : '')->where((int) $id_order_state ? 'oh.`id_order_state` = ' . (int) $id_order_state : '')->order_by('oh.`date_add` DESC, oh.`id_order_history` DESC'));
            if ($no_hidden) {
                return $result;
            }
            static::$_history_cache[$this->id . '_' . $id_order_state . '_' . $filters] = $result;
        }
        return static::$_history_cache[$this->id . '_' . $id_order_state . '_' . $filters];
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_products_detail()
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('order_detail', 'od')->left_join('product', 'p', 'p.`id_product` = od.`product_id`')->left_join('product_shop', 'ps', 'ps.`id_product` = od.`product_id` AND ps.`id_shop` = od.`id_shop`')->where('od.`id_order` = ' . (int) $this->id));
    }
    /**
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function get_first_message()
    {
        return Db::read_only()->get_value((new Db_Query())->select('`message`')->from('message')->where('`id_order` = ' . (int) $this->id)->order_by('`id_message`'));
    }
    /**
     * Marked as deprecated but should not throw any "deprecated" message
     * This function is used in order to keep front office backward compatibility 14 -> 1.5
     * (Order History)
     *
     * @deprecated 2.0.0
     *
     * @param array $row
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_product_prices(&$row): void
    {
        $tax_calculator = Order_Detail::get_tax_calculator_static((int) $row['id_order_detail']);
        $row['tax_calculator'] = $tax_calculator;
        $row['tax_rate'] = $tax_calculator->get_total_rate();
        $row['product_price'] = Tools::round_price((float) $row['unit_price_tax_excl']);
        $row['product_price_wt'] = Tools::round_price((float) $row['unit_price_tax_incl']);
        $row['ecotax'] = Tools::round_price((float) $row['ecotax']);
        $row['product_price_wt_but_ecotax'] = Tools::round_price($row['product_price_wt'] - $row['ecotax']);
        $row['total_wt'] = Tools::round_price((float) $row['total_price_tax_incl']);
        $row['total_price'] = Tools::round_price((float) $row['total_price_tax_excl']);
    }
    /**
     * Get order products
     *
     * @param array|false $products
     * @param array|false $selectedProducts
     * @param array|false $selectedQty
     *
     * @return array Products with price, quantity (with taxe and without)
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_products($products = false, $selected_products = false, $selected_qty = false)
    {
        if (!$products) {
            $products = $this->get_products_detail();
        }
        $customized_datas = Product::get_all_customized_datas($this->id_cart);
        $result_array = [];
        foreach ($products as $row) {
            // Change qty if selected
            if ($selected_qty) {
                $row['product_quantity'] = 0;
                if (is_array($selected_products) && !empty($selected_products)) {
                    foreach ($selected_products as $key => $id_product) {
                        if ($row['id_order_detail'] == $id_product) {
                            $row['product_quantity'] = (int) $selected_qty[$key];
                        }
                    }
                }
                if (!$row['product_quantity']) {
                    continue;
                }
            }
            $this->set_product_image_informations($row);
            $this->set_product_current_stock($row);
            // Backward compatibility 1.4 -> 1.5
            $this->set_product_prices($row);
            $this->set_product_customized_datas($row, $customized_datas);
            // Add information for virtual product
            if (!empty($row['download_hash'])) {
                $row['filename'] = Product_Download::get_filename_from_id_product((int) $row['product_id']);
                // Get the display filename
                $row['display_filename'] = Product_Download::get_filename_from_filename($row['filename']);
            }
            $row['id_address_delivery'] = $this->id_address_delivery;
            /* Stock product */
            $result_array[(int) $row['id_order_detail']] = $row;
        }
        if ($customized_datas) {
            Product::add_customization_price($result_array, $customized_datas);
        }
        return $result_array;
    }
    /**
     * @param int $idCustomer
     * @param int $idProduct
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_id_order_product($id_customer, $id_product)
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('o.`id_order`')->from('orders', 'o')->left_join('order_detail', 'od', 'o.`id_order` = od.`id_order`')->where('o.`id_customer` = ' . (int) $id_customer)->where('od.`product_id` = ' . (int) $id_product)->order_by('o.`date_add` DESC'));
    }
    /**
     * @param array $product
     * @param array $customizedDatas
     */
    protected function set_product_customized_datas(&$product, $customized_datas)
    {
        $product['customizedDatas'] = null;
        if (isset($customized_datas[$product['product_id']][$product['product_attribute_id']])) {
            $product['customizedDatas'] = $customized_datas[$product['product_id']][$product['product_attribute_id']];
        } else {
            $product['customizationQuantityTotal'] = 0;
        }
    }
    /**
     * This method allow to add stock information on a product detail
     *
     * If advanced stock management is active, get physical stock of this product in the warehouse associated to the ptoduct for the current order
     * Else get the available quantity of the product in fucntion of the shop associated to the order
     *
     * @param array &$product
     *
     * @throws PrestaShopException
     */
    protected function set_product_current_stock(&$product)
    {
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && (int) $product['advanced_stock_management'] == 1 && (int) $product['id_warehouse'] > 0) {
            $product['current_stock'] = Stock_Manager_Factory::get_manager()->get_product_physical_quantities($product['product_id'], $product['product_attribute_id'], (int) $product['id_warehouse'], true);
        } else {
            $product['current_stock'] = Stock_Available::get_quantity_available_by_product($product['product_id'], $product['product_attribute_id'], (int) $this->id_shop);
        }
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
            $id_image = (int) $connection->get_value((new Db_Query())->select('image_shop.`id_image`')->from('product_attribute_image', 'pai')->join(Shop::add_sql_association('image', 'pai', true))->left_join('image', 'i', 'i.`id_image` = pai.`id_image`')->where('`id_product_attribute` = ' . (int) $product['product_attribute_id'])->order_by('i.`position` ASC'));
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
     * @return float|int
     *
     * @throws PrestaShopException
     */
    public function get_taxes_average_used()
    {
        return Cart::get_taxes_average_used((int) $this->id_cart);
    }
    /**
     * Count virtual products in order
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_virtual_products()
    {
        return Db::read_only()->get_array((new Db_Query())->select('`product_id`, `product_attribute_id`, `download_hash`, `download_deadline`')->from('order_detail', 'od')->where('od.`id_order` = ' . (int) $this->id)->where('`download_hash` <> \'\''));
    }
    /**
     * Check if order contains (only) virtual products
     *
     * @param bool $strict If false return true if there are at least one product virtual
     *
     * @return bool true if is a virtual order or false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function is_virtual($strict = true)
    {
        $products = $this->get_products();
        if (count($products) < 1) {
            return false;
        }
        $virtual = true;
        foreach ($products as $product) {
            $is_virtual = (bool) $product['is_virtual'];
            if ($strict === false && $is_virtual) {
                return true;
            }
            $virtual = $virtual && $is_virtual;
        }
        return $virtual;
    }
    /**
     * @param bool $details
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 2.0.0
     */
    public function get_discounts($details = false)
    {
        Tools::display_as_deprecated();
        return $this->get_cart_rules();
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_cart_rules()
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('order_cart_rule', 'ocr')->where('ocr.`id_order` = ' . (int) $this->id));
    }
    /**
     * @param int $idCustomer
     * @param int $idCartRule
     *
     * @return int|null
     *
     * @throws PrestaShopException
     */
    public static function get_discounts_customer($id_customer, $id_cart_rule)
    {
        $cache_id = 'Order::getDiscountsCustomer_' . (int) $id_customer . '-' . (int) $id_cart_rule;
        if (!Cache::is_stored($cache_id)) {
            $result = (int) Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from(bq_sql(static::$definition['table']), 'o')->left_join('order_cart_rule', 'ocr', 'ocr.`id_order` = o.`id_order`')->where('o.`id_customer` = ' . (int) $id_customer)->where('ocr.`id_cart_rule` = ' . (int) $id_cart_rule));
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Get current order status (eg. Awaiting payment, Delivered...)
     *
     * @return int Order status id
     */
    public function get_current_state()
    {
        return $this->current_state;
    }
    /**
     * Get current order status name (eg. Awaiting payment, Delivered...)
     *
     * @param int $idLang
     *
     * @return array|false Order status details
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_current_state_full($id_lang)
    {
        return Db::read_only()->get_row((new Db_Query())->select('os.`id_order_state`, osl.`name`, os.`logable`, os.`shipped`')->from('order_state', 'os')->left_join('order_state_lang', 'osl', 'osl.`id_order_state` = os.`id_order_state` AND osl.`id_lang` = ' . (int) $id_lang)->where('os.`id_order_state` = ' . (int) $this->current_state));
    }
    /**
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function has_been_delivered()
    {
        return count($this->get_history((int) $this->id_lang, false, false, Order_State::FLAG_DELIVERY));
    }
    /**
     * Returns true, if order product list can be modified -- products can be added, deleted, or change quantity
     *
     *
     * @throws PrestaShopException
     */
    public function can_edit_products(): bool
    {
        if (is_null($this->cache_can_edit_products)) {
            $this->cache_can_edit_products = $this->resolve_can_edit_products();
        }
        return $this->cache_can_edit_products;
    }
    /**
     * @throws PrestaShopException
     */
    protected function resolve_can_edit_products(): bool
    {
        if ($this->has_been_delivered()) {
            return false;
        }
        $responses = Hook::get_responses('actionCanEditOrderProducts', ['order' => $this]);
        foreach ($responses as $response) {
            if (!$response) {
                return false;
            }
        }
        return true;
    }
    /**
     * Has products returned by the merchant or by the customer?
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function has_product_returned()
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('IFNULL(SUM(ord.`product_quantity`), SUM(`product_quantity_return`))')->from('orders', 'o')->inner_join('order_detail', 'od', 'od.`id_order` = o.`id_order`')->left_join('order_return_detail', 'ord', 'ord.`id_order_detail` = od.`id_order_detail`')->where('o.`id_order` = ' . (int) $this->id));
    }
    /**
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function has_been_paid()
    {
        return count($this->get_history((int) $this->id_lang, false, false, Order_State::FLAG_PAID));
    }
    /**
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function has_been_shipped()
    {
        return count($this->get_history((int) $this->id_lang, false, false, Order_State::FLAG_SHIPPED));
    }
    /**
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function is_in_preparation()
    {
        return count($this->get_history((int) $this->id_lang, Configuration::get('PS_OS_PREPARATION')));
    }
    /**
     * Checks if the current order status is paid and shipped
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function is_paid_and_shipped()
    {
        $order_state = $this->get_current_order_state();
        if ($order_state && $order_state->paid && $order_state->shipped) {
            return true;
        }
        return false;
    }
    /**
     * Get customer orders
     *
     * @param int $idCustomer Customer id
     * @param bool $showHiddenStatus Display or not hidden order statuses
     *
     * @return array Customer orders
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_customer_orders($id_customer, $show_hidden_status = false, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $connection = Db::read_only();
        $res = $connection->get_array((new Db_Query())->select('o.*, COALESCE((SELECT SUM(od.`product_quantity`) FROM `' . _DB_PREFIX_ . 'order_detail` od WHERE od.`id_order` = o.`id_order`), 0) nb_products')->from('orders', 'o')->where('o.`id_customer` = ' . (int) $id_customer . ' ' . Shop::add_sql_restriction(Shop::SHARE_ORDER))->group_by('o.`id_order`')->order_by('o.`date_add` DESC'));
        if (!$res) {
            return [];
        }
        foreach ($res as $key => $val) {
            $res2 = $connection->get_array((new Db_Query())->select('os.`id_order_state`, osl.`name` AS `order_state`, os.`invoice`, os.`color` AS `order_state_color`')->from('order_history', 'oh')->left_join('order_state', 'os', 'os.`id_order_state` = oh.`id_order_state`')->inner_join('order_state_lang', 'osl', 'os.`id_order_state` = osl.`id_order_state` AND osl.`id_lang` = ' . (int) $context->language->id)->where('oh.`id_order` = ' . (int) $val['id_order'])->where(!$show_hidden_status ? 'os.`hidden` != 1' : '')->order_by('oh.`date_add` DESC, oh.`id_order_history` DESC')->limit(1));
            if ($res2) {
                $res[$key] = array_merge($val, $res2[0]);
            }
        }
        return $res;
    }
    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|null $idCustomer
     * @param string|null $type
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_orders_id_by_date($date_from, $date_to, $id_customer = null, $type = null)
    {
        $result = Db::read_only()->get_array((new Db_Query())->select('`id_order`')->from('orders')->where('DATE_ADD(`date_upd`, INTERVAL -1 DAY) <= \'' . p_sql($date_to) . '\'')->where('`date_upd`>= \'' . p_sql($date_from) . '\' ' . Shop::add_sql_restriction())->where($type ? '`' . bq_sql($type) . '_number` != 0' : '')->where($id_customer ? '`id_customer` = ' . (int) $id_customer : ''));
        $orders = [];
        foreach ($result as $order) {
            $orders[] = (int) $order['id_order'];
        }
        return $orders;
    }
    /**
     * @param int|null $limit
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_orders_with_informations($limit = null, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $state_name_sql = (new Db_Query())->select('osl.`name`')->from('order_state_lang', 'osl')->where('osl.`id_order_state` = o.`current_state`')->where('osl.`id_lang` = ' . (int) $context->language->id)->limit(1);
        return Db::read_only()->get_array((new Db_Query())->select('*, (' . $state_name_sql->build() . ') AS `state_name`, o.`date_add` AS `date_add`, o.`date_upd` AS `date_upd`')->from('orders', 'o')->left_join('customer', 'c', 'c.`id_customer` = o.`id_customer`')->where('1' . ' ' . Shop::add_sql_restriction(false, 'o'))->order_by('o.`date_add` DESC')->limit((int) $limit ?: 0));
    }
    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param int $idCustomer
     * @param string $type
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_orders_id_invoice_by_date($date_from, $date_to, $id_customer = null, $type = null)
    {
        $result = Db::read_only()->get_array((new Db_Query())->select('`id_order`')->from('orders')->where('DATE_ADD(`invoice_date`, INTERVAL -1 DAY) <= \'' . p_sql($date_to) . '\' AND `invoice_date` >= \'' . p_sql($date_from) . '\' ' . Shop::add_sql_restriction())->where($type ? '`' . bq_sql($type) . '_number` != 0' : '')->where($id_customer ? '`id_customer` = ' . (int) $id_customer : '')->order_by('`invoice_date` ASC'));
        $orders = [];
        foreach ($result as $order) {
            $orders[] = (int) $order['id_order'];
        }
        return $orders;
    }
    /**
     * @param int $idOrderState
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_order_ids_by_status($id_order_state)
    {
        $result = Db::read_only()->get_array((new Db_Query())->select('`id_order`')->from('orders', 'o')->where('o.`current_state` = ' . (int) $id_order_state . ' ' . Shop::add_sql_restriction(false, 'o'))->order_by('`invoice_date` ASC'));
        $orders = [];
        foreach ($result as $order) {
            $orders[] = (int) $order['id_order'];
        }
        return $orders;
    }
    /**
     * Get product total without taxes
     *
     * @param false $products Deprecated.
     *
     * @return float Product total without taxes
     */
    public function get_total_products_without_taxes($products = false)
    {
        if ($products !== false) {
            Tools::display_parameter_as_deprecated('products');
        }
        return $this->total_products;
    }
    /**
     * Get product total with taxes
     *
     * @param false|array $products Deprecated.
     *
     * @return float Product total with taxes
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_total_products_with_taxes($products = false)
    {
        if ($products !== false) {
            Tools::display_parameter_as_deprecated('products');
        }
        if ($this->total_products_wt != '0.00' && !$products) {
            return $this->total_products_wt;
        }
        /* Retro-compatibility (now set directly on the validateOrder() method) */
        if (!$products) {
            $products = $this->get_products_detail();
        }
        $return = 0;
        foreach ($products as $row) {
            $return += $row['total_price_tax_incl'];
        }
        if (!$products) {
            $this->total_products_wt = $return;
            $this->update();
        }
        return $return;
    }
    /**
     * Get order customer
     *
     * @return Customer $customer
     *
     * @throws PrestaShopException
     */
    public function get_customer()
    {
        if (is_null($this->cache_customer)) {
            $this->cache_customer = new Customer((int) $this->id_customer);
        }
        return $this->cache_customer;
    }
    /**
     * Get customer orders number
     *
     * @param int $idCustomer Customer id
     *
     * @return int Customer orders number
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_customer_nb_orders($id_customer)
    {
        $result = Db::read_only()->get_row((new Db_Query())->select('COUNT(`id_order`) AS `nb`')->from('orders')->where('`id_customer` = ' . (int) $id_customer . ' ' . Shop::add_sql_restriction()));
        return $result['nb'] ?? 0;
    }
    /**
     * Get an order id by its cart id
     *
     * @param int $idCart Cart id
     *
     * @return int Order id
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_order_by_cart_id($id_cart)
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('`id_order`')->from('orders')->where('`id_cart` = ' . (int) $id_cart)->order_by('`id_order`'));
    }
    /**
     * @see Order::addCartRule()
     *
     * @param int $idCartRule
     * @param string $name
     * @param float $value
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @deprecated
     */
    public function add_discount($id_cart_rule, $name, $value)
    {
        Tools::display_as_deprecated();
        return $this->add_cart_rule($id_cart_rule, $name, ['tax_incl' => $value, 'tax_excl' => '0.00']);
    }
    /**
     * @param int $idCartRule
     * @param string $name
     * @param array $values
     * @param int $idOrderInvoice
     * @param bool|null $freeShipping
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_cart_rule($id_cart_rule, $name, $values, $id_order_invoice = 0, $free_shipping = null)
    {
        $order_cart_rule = new Order_Cart_Rule();
        $order_cart_rule->id_order = $this->id;
        $order_cart_rule->id_cart_rule = $id_cart_rule;
        $order_cart_rule->id_order_invoice = $id_order_invoice;
        $order_cart_rule->name = $name;
        $order_cart_rule->value = $values['tax_incl'];
        $order_cart_rule->value_tax_excl = $values['tax_excl'];
        if ($free_shipping === null) {
            $cart_rule = new Cart_Rule($id_cart_rule);
            $free_shipping = $cart_rule->free_shipping;
        }
        $order_cart_rule->free_shipping = (int) $free_shipping;
        return $order_cart_rule->add();
    }
    /**
     * Returns true, if customer can return items
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function get_number_of_days()
    {
        $nb_return_days = (int) Configuration::get('PS_ORDER_RETURN_NB_DAYS', null, null, $this->id_shop);
        if (!$nb_return_days) {
            return true;
        }
        $delivery_date = $this->get_delivery_date();
        if (!$delivery_date) {
            return false;
        }
        try {
            $threshold = $delivery_date->add(new DateInterval('P' . $nb_return_days . 'D'))->set_time(23, 59, 59);
            $now = new DateTime();
            return $now < $threshold;
        } catch (Throwable) {
            return false;
        }
    }
    /**
     * Can this order be returned by the client?
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function is_returnable()
    {
        if (Configuration::get('PS_ORDER_RETURN', null, null, $this->id_shop) && $this->is_paid_and_shipped()) {
            return $this->get_number_of_days();
        }
        return false;
    }
    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_last_invoice_number()
    {
        $sql = (new Db_Query())->select('MAX(`number`)')->from('order_invoice');
        if (Configuration::get('PS_INVOICE_RESET')) {
            $sql->where('DATE_FORMAT(`date_add`, "%Y") = ' . (int) date('Y'));
        }
        return (int) Db::read_only()->get_value($sql);
    }
    /**
     * @param int $orderInvoiceId
     * @param int $idShop
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function set_last_invoice_number($order_invoice_id, $id_shop)
    {
        if (!$order_invoice_id) {
            return false;
        }
        $number = Configuration::get('PS_INVOICE_START_NUMBER', null, null, $id_shop);
        // If invoice start number has been set, you clean the value of this configuration
        if ($number) {
            Configuration::update_value('PS_INVOICE_START_NUMBER', false, false, null, $id_shop);
        }
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'order_invoice` SET number =';
        if ($number) {
            $sql .= (int) $number;
        } else {
            // Find the next number
            $new_number_sql = 'SELECT (MAX(`number`) + 1) AS new_number
                FROM `' . _DB_PREFIX_ . 'order_invoice`' . (Configuration::get('PS_INVOICE_RESET') ? ' WHERE DATE_FORMAT(`date_add`, "%Y") = ' . (int) date('Y') : '');
            $new_number = Db::read_only()->get_value($new_number_sql);
            $sql .= (int) $new_number;
        }
        $sql .= ' WHERE `id_order_invoice` = ' . (int) $order_invoice_id;
        return Db::get_instance()->execute($sql);
    }
    /**
     * @param int $orderInvoiceId
     *
     * @return false|null|string
     * @throws PrestaShopException
     */
    public function get_invoice_number($order_invoice_id)
    {
        if (!$order_invoice_id) {
            return false;
        }
        return Db::read_only()->get_value((new Db_Query())->select('`number`')->from('order_invoice')->where('`id_order_invoice` = ' . (int) $order_invoice_id));
    }
    /**
     * This method allows to generate first invoice of the current order
     *
     * @param bool $useExistingPayment
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_invoice($use_existing_payment = false): void
    {
        if (!$this->has_invoice()) {
            if ($id = (int) $this->get_order_invoice_id_if_has_delivery()) {
                $order_invoice = new Order_Invoice($id);
            } else {
                $order_invoice = new Order_Invoice();
            }
            $order_invoice->id_order = $this->id;
            if (!$id) {
                $order_invoice->number = 0;
            }
            // Save Order invoice
            $this->set_invoice_details($order_invoice);
            if (Configuration::get('PS_INVOICE')) {
                static::set_last_invoice_number($order_invoice->id, $this->id_shop);
            }
            $conn = Db::get_instance();
            // Update order_carrier
            $id_order_carrier = $conn->get_value((new Db_Query())->Select('`id_order_carrier`')->from('order_carrier')->where('`id_order` = ' . (int) $order_invoice->id_order)->where('`id_order_invoice` IS NULL OR `id_order_invoice` = 0'));
            if ($id_order_carrier) {
                $order_carrier = new Order_Carrier($id_order_carrier);
                $order_carrier->id_order_invoice = (int) $order_invoice->id;
                $order_carrier->update();
            }
            // Update order detail
            $conn->update('order_detail', ['id_order_invoice' => (int) $order_invoice->id], '`id_order` = ' . (int) $order_invoice->id_order);
            Cache::clean('objectmodel_OrderDetail_*');
            $id_order_payments = $conn->get_array((new Db_Query())->select('DISTINCT op.`id_order_payment`')->from('order_payment', 'op')->inner_join('orders', 'o', 'o.`reference` = op.`order_reference` AND o.`id_order` = ' . (int) $order_invoice->id_order)->left_join('order_invoice_payment', 'oip', 'oip.`id_order_payment` = op.`id_order_payment` AND oip.`id_order` = ' . (int) $order_invoice->id_order));
            // Update order payment
            if ($use_existing_payment && !empty($id_order_payments)) {
                foreach ($id_order_payments as $order_payment) {
                    $conn->insert('order_invoice_payment', ['id_order_invoice' => (int) $order_invoice->id, 'id_order_payment' => (int) $order_payment['id_order_payment'], 'id_order' => (int) $order_invoice->id_order]);
                }
            } else {
                // Since an invoice always requires an existing order payment, we are going to add one
                $order_payment = new Order_Payment();
                $order_payment->order_reference = $this->reference;
                $order_payment->id_currency = $this->id_currency;
                $order_payment->amount = $this->total_paid_tax_incl;
                $order_payment->payment_method = $this->payment;
                $order_payment->conversion_rate = $this->conversion_rate;
                $order_payment->add();
                $conn->insert('order_invoice_payment', ['id_order_invoice' => (int) $order_invoice->id, 'id_order_payment' => (int) $order_payment->id, 'id_order' => (int) $order_invoice->id_order]);
                $this->adjust_total_paid_amount($order_payment->amount, $order_payment->id_currency);
            }
            // Clear cache
            Cache::clean('order_invoice_paid_*');
            // Update order cart rule
            $conn->update('order_cart_rule', ['id_order_invoice' => (int) $order_invoice->id], '`id_order` = ' . (int) $order_invoice->id_order);
            // Keep it for retrocompatibility, to remove on 1.6 version
            $this->invoice_date = $order_invoice->date_add;
            if (Configuration::get('PS_INVOICE')) {
                $this->invoice_number = $this->get_invoice_number($order_invoice->id);
                $invoice_number = Hook::get_first_response('actionSetInvoice', [static::class => $this, $order_invoice::class => $order_invoice, 'use_existing_payment' => (bool) $use_existing_payment]);
                if (is_numeric($invoice_number)) {
                    $this->invoice_number = (int) $invoice_number;
                } else {
                    $this->invoice_number = $this->get_invoice_number($order_invoice->id);
                }
            }
            $this->update();
        }
    }
    /**
     * This method allows to fulfill the object order_invoice with sales figures
     *
     * @param OrderInvoice $orderInvoice
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function set_invoice_details($order_invoice)
    {
        if (!$order_invoice || !is_object($order_invoice)) {
            return;
        }
        $address = new Address((int) $this->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        $carrier = new Carrier((int) $this->id_carrier);
        if (Carrier::use_proportionate_tax()) {
            /** @var AverageTaxOfProductsTaxCalculator $wrappingTaxCalculator */
            $wrapping_tax_calculator = Adapter_service_Locator::get('AverageTaxOfProductsTaxCalculator')->set_id_order($this->id);
            /** @var AverageTaxOfProductsTaxCalculator $taxCalculator */
            $tax_calculator = Adapter_service_Locator::get('AverageTaxOfProductsTaxCalculator')->set_id_order($this->id);
        } else {
            $wrapping_tax_manager = Tax_Manager_Factory::get_manager($address, (int) Configuration::get('PS_GIFT_WRAPPING_TAX_RULES_GROUP'));
            $wrapping_tax_calculator = $wrapping_tax_manager->get_tax_calculator();
            $tax_calculator = $carrier->get_tax_calculator($address);
        }
        $order_invoice->total_discount_tax_excl = $this->total_discounts_tax_excl;
        $order_invoice->total_discount_tax_incl = $this->total_discounts_tax_incl;
        $order_invoice->total_paid_tax_excl = $this->total_paid_tax_excl;
        $order_invoice->total_paid_tax_incl = $this->total_paid_tax_incl;
        $order_invoice->total_products = $this->total_products;
        $order_invoice->total_products_wt = $this->total_products_wt;
        $order_invoice->total_shipping_tax_excl = $this->total_shipping_tax_excl;
        $order_invoice->total_shipping_tax_incl = $this->total_shipping_tax_incl;
        $order_invoice->shipping_tax_computation_method = $tax_calculator->computation_method;
        $order_invoice->total_wrapping_tax_excl = $this->total_wrapping_tax_excl;
        $order_invoice->total_wrapping_tax_incl = $this->total_wrapping_tax_incl;
        $order_invoice->save();
        $order_invoice->save_carrier_tax_calculator($tax_calculator->get_taxes_amount($order_invoice->total_shipping_tax_excl));
        $order_invoice->save_wrapping_tax_calculator($wrapping_tax_calculator->get_taxes_amount($order_invoice->total_wrapping_tax_excl));
    }
    /**
     * This method allows to generate first delivery slip of the current order
     *
     * @throws PrestaShopException
     */
    public function set_delivery_slip(): void
    {
        if (!$this->has_invoice()) {
            $order_invoice = new Order_Invoice();
            $order_invoice->id_order = $this->id;
            $order_invoice->number = 0;
            $this->set_invoice_details($order_invoice);
            $this->delivery_date = $order_invoice->date_add;
            $this->delivery_number = $this->get_delivery_number($order_invoice->id);
            $this->update();
        }
    }
    /**
     * @param int $orderInvoiceId
     * @param int $idShop
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function set_delivery_number($order_invoice_id, $id_shop)
    {
        if (!$order_invoice_id) {
            return false;
        }
        $id_shop = Shop::get_total_shops() > 1 ? $id_shop : null;
        $number = Configuration::get('PS_DELIVERY_NUMBER', null, null, $id_shop);
        // If delivery slip start number has been set, you clean the value of this configuration
        if ($number) {
            Configuration::update_value('PS_DELIVERY_NUMBER', false, false, null, $id_shop);
        }
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'order_invoice` SET delivery_number = ';
        if ($number) {
            $sql .= (int) $number;
        } else {
            $sql .= '(SELECT `new_number` FROM (SELECT (MAX(`delivery_number`) + 1) AS `new_number` FROM `' . _DB_PREFIX_ . 'order_invoice`) AS `result`)';
        }
        $sql .= ' WHERE `id_order_invoice` = ' . (int) $order_invoice_id;
        return Db::get_instance()->execute($sql);
    }
    /**
     * @param int $orderInvoiceId
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function get_delivery_number($order_invoice_id)
    {
        if (!$order_invoice_id) {
            return false;
        }
        return Db::read_only()->get_value((new Db_Query())->select('`delivery_number`')->from('order_invoice')->where('`id_order_invoice` = ' . (int) $order_invoice_id));
    }
    /**
     * @throws PrestaShopException
     */
    public function set_delivery(): void
    {
        // Get all invoice
        $order_invoice_collection = $this->get_invoices_collection();
        foreach ($order_invoice_collection as $order_invoice) {
            /** @var OrderInvoice $orderInvoice */
            if ($order_invoice->delivery_number) {
                continue;
            }
            // Set delivery number on invoice
            $order_invoice->delivery_number = 0;
            $order_invoice->delivery_date = date('Y-m-d H:i:s');
            // Update Order Invoice
            $order_invoice->update();
            $this->set_delivery_number($order_invoice->id, $this->id_shop);
            $this->delivery_number = $this->get_delivery_number($order_invoice->id);
        }
        // Keep it for backward compatibility, to remove on 1.6 version
        // Set delivery date
        $this->delivery_date = date('Y-m-d H:i:s');
        // Update object
        $this->update();
    }
    /**
     * @param int $idDelivery
     *
     * @return Order
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_by_delivery($id_delivery)
    {
        $res = Db::read_only()->get_row((new Db_Query())->select('`id_order`')->from('orders')->where('`delivery_number` = ' . (int) $id_delivery . ' ' . Shop::add_sql_restriction()));
        return new Order((int) $res['id_order']);
    }
    /**
     * Get a collection of orders using reference
     *
     * @param string $reference
     *
     * @return PrestaShopCollection Collection of Order
     *
     * @throws PrestaShopException
     */
    public static function get_by_reference($reference)
    {
        $orders = new Presta_Shop_Collection('Order');
        $orders->where('reference', '=', $reference);
        return $orders;
    }
    /**
     * @return float
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_total_weight()
    {
        $result = Db::read_only()->get_value((new Db_Query())->select('SUM(`product_weight` * `product_quantity`)')->from('order_detail')->where('`id_order` = ' . (int) $this->id));
        return (float) $result;
    }
    /**
     * @param int $idInvoice
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_invoice($id_invoice)
    {
        Tools::display_as_deprecated();
        return Db::read_only()->get_row((new Db_Query())->select('`invoice_number`, `id_order`')->from('orders')->where('`invoice_number` = ' . (int) $id_invoice));
    }
    /**
     * @param string $email
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_associated_at_guest($email)
    {
        if (!$email) {
            return false;
        }
        return (bool) Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from('orders', 'o')->left_join('customer', 'c', 'c.`id_customer` = o.`id_customer`')->where('o.`id_order` = ' . (int) $this->id)->where('c.`email` = \'' . p_sql($email) . '\'')->where('c.`is_guest` = 1 ' . Shop::add_sql_restriction(false, 'c')));
    }
    /**
     * @param int $idOrder
     * @param int $idCustomer optionnal
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_cart_id_static($id_order, $id_customer = 0)
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('`id_cart`')->from('orders')->where('`id_order` = ' . (int) $id_order)->where($id_customer ? '`id_customer` = ' . (int) $id_customer : ''));
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_order_rows()
    {
        $sql = (new Db_Query())->select('od.`id_order_detail` AS `id`')->select('od.`product_id`')->select('od.`product_price`')->select('od.`id_order`')->select('od.`product_attribute_id`')->select('od.`product_quantity`')->select('od.`product_name`')->select('od.`product_reference`')->select('od.`product_ean13`')->select('od.`product_upc`')->select('od.`unit_price_tax_incl`')->select('od.`unit_price_tax_excl`')->select('(CASE WHEN COUNT(odp.id_order_detail_pack) > 0 THEN 1 ELSE 0 END) as is_pack')->from('order_detail', 'od')->left_join('order_detail_pack', 'odp', 'od.id_order_detail = odp.id_order_detail')->where('`id_order` = ' . (int) $this->id)->group_by('od.id_order_detail');
        return Db::read_only()->get_array($sql);
    }
    /** Set current order status
     *
     * @param int $idOrderState
     * @param int $idEmployee (/!\ not optional except for Webservice.
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function set_current_state($id_order_state, $id_employee = 0)
    {
        if (empty($id_order_state)) {
            return false;
        }
        $history = new Order_History();
        $history->id_order = (int) $this->id;
        $history->id_employee = (int) $id_employee;
        $history->change_id_order_state((int) $id_order_state, $this);
        $res = Db::read_only()->get_row((new Db_Query())->select('`invoice_number`, `invoice_date`, `delivery_number`, `delivery_date`')->from('orders')->where('`id_order` = ' . (int) $this->id));
        $this->invoice_date = $res['invoice_date'];
        $this->invoice_number = $res['invoice_number'];
        $this->delivery_date = $res['delivery_date'];
        $this->delivery_number = $res['delivery_number'];
        $this->update();
        return $history->add_withemail();
    }
    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function add_ws($autodate = true, $null_values = false)
    {
        if (!$this->module) {
            throw new Presta_Shop_Exception('Payment module not specified');
        }
        $payment_module = Module::get_instance_by_name($this->module);
        if ($payment_module === false) {
            throw new Presta_Shop_Exception(sprintf("Payment module '%s' not found", $this->module));
        }
        if ($payment_module instanceof Payment_Module) {
            $customer = new Customer($this->id_customer);
            $payment_module->validate_order($this->id_cart, Configuration::get('PS_OS_WS_PAYMENT'), $this->total_paid, $this->payment, null, [], null, false, $customer->secure_key);
            $this->id = $payment_module->current_order;
            return true;
        }
        throw new Presta_Shop_Exception(sprintf("Module '%s' is not payment module", $this->module));
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_associations()
    {
        return Db::get_instance()->delete('order_detail', '`id_order` = ' . (int) $this->id) !== false;
    }
    /**
     * This method return the ID of the previous order
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function get_previous_order_id()
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('`id_order`')->from('orders')->where('`id_order` < ' . (int) $this->id . ' ' . Shop::add_sql_restriction())->order_by('`id_order` DESC'));
    }
    /**
     * This method return the ID of the next order
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function get_next_order_id()
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('`id_order`')->from('orders')->where('`id_order` > ' . (int) $this->id . ' ' . Shop::add_sql_restriction())->order_by('`id_order` ASC'));
    }
    /**
     * Get the an order detail list of the current order
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_order_detail_list()
    {
        return Order_Detail::get_list($this->id);
    }
    /**
     * Gennerate a unique reference for orders generated with the same cart id
     * This references, is usefull for check payment
     *
     * @return String
     */
    public static function generate_reference()
    {
        return strtoupper(Tools::passwd_gen(9, 'NO_NUMERIC'));
    }
    /**
     * @param int $idProduct
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function order_contain_product($id_product)
    {
        $product_list = $this->get_order_detail_list();
        foreach ($product_list as $product) {
            if ($product['product_id'] == (int) $id_product) {
                return true;
            }
        }
        return false;
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
        return (bool) Db::read_only()->get_value((new Db_Query())->select('od.`tax_computation_method`')->from('order_detail_tax', 'odt')->left_join('order_detail', 'od', 'od.`id_order_detail` = odt.`id_order_detail`')->where('od.`id_order` = ' . (int) $this->id)->where('od.`tax_computation_method` = ' . (int) Tax_Calculator::ONE_AFTER_ANOTHER_METHOD));
    }
    /**
     * This method allows to get all Order Payment for the current order
     *
     * @return PrestaShopCollection Collection of OrderPayment
     *
     * @throws PrestaShopException
     */
    public function get_order_payment_collection()
    {
        $order_payments = new Presta_Shop_Collection('OrderPayment');
        $order_payments->where('order_reference', '=', $this->reference);
        return $order_payments;
    }
    /**
     * This method allows to add a payment to the current order
     *
     * @param float $amountPaid
     * @param string $paymentMethod
     * @param string $paymentTransactionId
     * @param Currency $currency
     * @param string $date
     * @param OrderInvoice $orderInvoice
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_order_payment($amount_paid, $payment_method = null, $payment_transaction_id = null, $currency = null, $date = null, $order_invoice = null)
    {
        $order_payment = new Order_Payment();
        $order_payment->order_reference = $this->reference;
        $order_payment->id_currency = $currency ? $currency->id : $this->id_currency;
        // we kept the currency rate for historization reasons
        $order_payment->conversion_rate = $currency ? $currency->conversion_rate : 1;
        // if payment_method is define, we used this
        $order_payment->payment_method = $payment_method ?: $this->payment;
        $order_payment->transaction_id = $payment_transaction_id;
        $order_payment->amount = $amount_paid;
        $order_payment->date_add = $date ?: null;
        // Add time to the date if needed
        if ($order_payment->date_add != null && preg_match('/^[0-9]+-[0-9]+-[0-9]+$/', $order_payment->date_add)) {
            $order_payment->date_add .= ' ' . date('H:i:s');
        }
        $this->adjust_total_paid_amount($order_payment->amount, $order_payment->id_currency);
        // We put autodate parameter of add method to true if date_add field is null
        $res = $order_payment->add(is_null($order_payment->date_add)) && $this->update();
        if (!$res) {
            return false;
        }
        if (!is_null($order_invoice)) {
            $res = Db::get_instance()->insert('order_invoice_payment', ['id_order_invoice' => (int) $order_invoice->id, 'id_order_payment' => (int) $order_payment->id, 'id_order' => (int) $this->id]);
            // Clear cache
            Cache::clean('order_invoice_paid_*');
        }
        return $res;
    }
    /**
     * Get all documents linked to the current order
     *
     * @return OrderInvoice[]|OrderSlip[]
     *
     * @throws PrestaShopException
     */
    public function get_documents()
    {
        /** @var OrderInvoice[] $invoices */
        $invoices = $this->get_invoices_collection()->get_results();
        foreach ($invoices as $key => $invoice) {
            if ($invoice->number) {
                $invoice->delivery_number = 0;
            } else {
                unset($invoices[$key]);
            }
        }
        /** @var OrderInvoice[] $delivery_slips */
        $delivery_slips = $this->get_delivery_slips_collection()->get_results();
        foreach ($delivery_slips as $key => $delivery) {
            if ($delivery->delivery_number) {
                $delivery->date_add = $delivery->delivery_date;
                $delivery->number = 0;
            } else {
                unset($delivery_slips[$key]);
            }
        }
        /** @var OrderSlip[] $order_slips */
        $order_slips = $this->get_order_slips_collection()->get_results();
        $documents = array_merge($invoices, $order_slips, $delivery_slips);
        usort($documents, ['Order', 'sortDocuments']);
        return $documents;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_return()
    {
        return Order_Return::get_orders_return($this->id_customer, $this->id);
    }
    /**
     * @return array return all shipping method for the current order
     * state_name sql var is now deprecated - use order_state_name for the state name and carrier_name for the carrier_name
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_shipping()
    {
        return Db::read_only()->get_array((new Db_Query())->select('DISTINCT oc.`id_order_invoice`, oc.`weight`, oc.`shipping_cost_tax_excl`')->select('oc.`shipping_cost_tax_incl`, c.`url`, oc.`id_carrier`, c.`name` AS `carrier_name`')->select('oc.`date_add`, "Delivery" AS `type`, (CASE WHEN oc.id_order_carrier THEN 1 ELSE 0 END) AS `can_edit`, oc.`tracking_number`')->select('oc.`id_order_carrier`, osl.`name` AS order_state_name, c.`name` AS `state_name`')->from('orders', 'o')->left_join('order_history', 'oh', 'o.`id_order` = oh.`id_order`')->left_join('order_carrier', 'oc', 'o.`id_order` = oc.`id_order`')->left_join('carrier', 'c', 'oc.`id_carrier` = c.`id_carrier`')->left_join('order_state_lang', 'osl', 'oh.`id_order_state` = osl.`id_order_state`')->where('o.`id_order` = ' . (int) $this->id)->group_by('c.`id_carrier`'));
    }
    /**
     * Get all order_slips for the current order
     *
     * @return PrestaShopCollection Collection of OrderSlip
     *
     * @throws PrestaShopException
     */
    public function get_order_slips_collection()
    {
        $order_slips = new Presta_Shop_Collection('OrderSlip');
        $order_slips->where('id_order', '=', $this->id);
        return $order_slips;
    }
    /**
     * Get all invoices for the current order
     *
     * @return PrestaShopCollection Collection of OrderInvoice
     *
     * @throws PrestaShopException
     */
    public function get_invoices_collection()
    {
        $order_invoices = new Presta_Shop_Collection('OrderInvoice');
        $order_invoices->where('id_order', '=', $this->id);
        return $order_invoices;
    }
    /**
     * Get all delivery slips for the current order
     *
     * @return PrestaShopCollection Collection of OrderInvoice
     *
     * @throws PrestaShopException
     */
    public function get_delivery_slips_collection()
    {
        $order_invoices = new Presta_Shop_Collection('OrderInvoice');
        $order_invoices->where('id_order', '=', $this->id);
        $order_invoices->where('delivery_number', '!=', '0');
        return $order_invoices;
    }
    /**
     * Get all not paid invoices for the current order
     *
     * @return PrestaShopCollection Collection of Order invoice not paid
     *
     * @throws PrestaShopException
     */
    public function get_not_paid_invoices_collection()
    {
        $invoices = $this->get_invoices_collection();
        foreach ($invoices as $key => $invoice) {
            /** @var OrderInvoice $invoice */
            if ($invoice->is_paid()) {
                unset($invoices[$key]);
            }
        }
        return $invoices;
    }
    /**
     * Get total paid
     *
     * @param Currency $currency currency used for the total paid of the current order
     *
     * @return float amount in the $currency
     *
     * @throws PrestaShopException
     */
    public function get_total_paid($currency = null)
    {
        if (!$currency) {
            $currency = new Currency($this->id_currency);
        }
        $total = 0;
        // Retrieve all payments
        $payments = $this->get_order_payment_collection();
        foreach ($payments as $payment) {
            /** @var OrderPayment $payment */
            if ($payment->id_currency == $currency->id) {
                $total += $payment->amount;
            } else {
                // get amount in shop default currency
                if ($payment->conversion_rate > 0.0) {
                    $amount = $payment->amount / $payment->conversion_rate;
                } else {
                    $amount = Tools::convert_price($payment->amount, $payment->id_currency, false);
                }
                if ($currency->id == Configuration::get('PS_CURRENCY_DEFAULT', null, null, $this->id_shop)) {
                    $total += $amount;
                } else {
                    $total += Tools::convert_price($amount, $currency->id, true);
                }
            }
        }
        return $total;
    }
    /**
     * Get the sum of total_paid_tax_incl of the orders with similar reference
     *
     * @return float
     *
     * @throws PrestaShopException
     */
    public function get_orders_total_paid()
    {
        return Db::read_only()->get_value((new Db_Query())->select('SUM(`total_paid_tax_incl`)')->from('orders')->where('`reference` = \'' . p_sql($this->reference) . '\'')->where('`id_cart` = ' . (int) $this->id_cart));
    }
    /**
     * This method allows to change the shipping cost of the current order
     *
     * @param float $amount
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_shipping_cost($amount)
    {
        $difference = $amount - $this->total_shipping;
        // if the current amount is same as the new, we return true
        if ($difference == 0) {
            return true;
        }
        // update the total_shipping value
        $this->total_shipping = $amount;
        // update the total of this order
        $this->total_paid += $difference;
        // update database
        return $this->update();
    }
    /**
     * Returns the correct product taxes breakdown.
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_product_taxes_breakdown()
    {
        $connection = Db::read_only();
        if ($this->use_one_after_another_tax_computation_method()) {
            // sum by taxes
            $taxes_by_tax = $connection->get_array('
			SELECT odt.`id_order_detail`, t.`name`, t.`rate`, SUM(`total_amount`) AS `total_amount`
			FROM `' . _DB_PREFIX_ . 'order_detail_tax` odt
			LEFT JOIN `' . _DB_PREFIX_ . 'tax` t ON (t.`id_tax` = odt.`id_tax`)
			LEFT JOIN `' . _DB_PREFIX_ . 'order_detail` od ON (od.`id_order_detail` = odt.`id_order_detail`)
			WHERE od.`id_order` = ' . (int) $this->id . '
			GROUP BY odt.`id_tax`
			');
            // format response
            $tmp_tax_infos = [];
            foreach ($taxes_by_tax as $tax_infos) {
                $tmp_tax_infos[$tax_infos['rate']]['total_amount'] = $tax_infos['tax_amount'];
                $tmp_tax_infos[$tax_infos['rate']]['name'] = $tax_infos['name'];
            }
        } else {
            // sum by order details in order to retrieve real taxes rate
            $taxes_infos = $connection->get_array('
			SELECT odt.`id_order_detail`, t.`rate` AS `name`, SUM(od.`total_price_tax_excl`) AS total_price_tax_excl, SUM(t.`rate`) AS rate, SUM(`total_amount`) AS `total_amount`
			FROM `' . _DB_PREFIX_ . 'order_detail_tax` odt
			LEFT JOIN `' . _DB_PREFIX_ . 'tax` t ON (t.`id_tax` = odt.`id_tax`)
			LEFT JOIN `' . _DB_PREFIX_ . 'order_detail` od ON (od.`id_order_detail` = odt.`id_order_detail`)
			WHERE od.`id_order` = ' . (int) $this->id . '
			GROUP BY odt.`id_order_detail`
			');
            // sum by taxes
            $tmp_tax_infos = [];
            foreach ($taxes_infos as $tax_infos) {
                if (!isset($tmp_tax_infos[$tax_infos['rate']])) {
                    $tmp_tax_infos[$tax_infos['rate']] = ['total_amount' => 0, 'name' => 0, 'total_price_tax_excl' => 0];
                }
                $tmp_tax_infos[$tax_infos['rate']]['total_amount'] += $tax_infos['total_amount'];
                $tmp_tax_infos[$tax_infos['rate']]['name'] = $tax_infos['name'];
                $tmp_tax_infos[$tax_infos['rate']]['total_price_tax_excl'] += $tax_infos['total_price_tax_excl'];
            }
        }
        return $tmp_tax_infos;
    }
    /**
     * Returns the shipping taxes breakdown
     *
     * @return array
     */
    public function get_shipping_taxes_breakdown()
    {
        $taxes_breakdown = [];
        $shipping_tax_amount = $this->total_shipping_tax_incl - $this->total_shipping_tax_excl;
        if ($shipping_tax_amount > 0) {
            $taxes_breakdown[] = ['rate' => $this->carrier_tax_rate, 'total_amount' => $shipping_tax_amount];
        }
        return $taxes_breakdown;
    }
    /**
     * Returns the wrapping taxes breakdown
     *
     * @return array
     */
    public function get_wrapping_taxes_breakdown()
    {
        Tools::display_as_deprecated();
        return [];
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
        return Db::read_only()->get_array((new Db_Query())->select('`eco_tax_rate`, SUM(`ecotax`) AS `ecotax_tax_excl`, SUM(`ecotax`) AS `ecotax_tax_incl`')->from('order_detail')->where('`id_order` = ' . (int) $this->id));
    }
    /**
     * Has invoice return true if this order has already an invoice
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function has_invoice()
    {
        return (bool) Db::read_only()->get_value((new Db_Query())->select('`id_order_invoice`')->from('order_invoice')->where('`id_order` = ' . (int) $this->id)->where(Configuration::get('PS_INVOICE') ? '`number` > 0' : ''));
    }
    /**
     * Has Delivery return true if this order has already a delivery slip
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function has_delivery()
    {
        return (bool) $this->get_order_invoice_id_if_has_delivery();
    }
    /**
     * Get order invoice id if has delivery return id_order_invoice if this order has already a delivery slip
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function get_order_invoice_id_if_has_delivery()
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('`id_order_invoice`')->from('order_invoice')->where('`id_order` = ' . (int) $this->id)->where('`delivery_number` > 0'));
    }
    /**
     * Get warehouse associated to the order
     *
     * @return array List of warehouse
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_warehouse_list()
    {
        $results = Db::read_only()->get_array((new Db_Query())->select('`id_warehouse`')->from('order_detail')->where('`id_order` = ' . (int) $this->id)->group_by('`id_warehouse`'));
        if (!$results) {
            return [];
        }
        $warehouse_list = [];
        foreach ($results as $row) {
            $warehouse_list[] = $row['id_warehouse'];
        }
        return $warehouse_list;
    }
    /**
     * @return OrderState|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_current_order_state()
    {
        if ($this->current_state) {
            return new Order_State($this->current_state);
        }
        return null;
    }
    /**
     * @see ObjectModel::getWebserviceObjectList()
     *
     * @param string $sqlJoin
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_webservice_object_list($sql_join, $sql_filter, $sql_sort, $sql_limit)
    {
        $sql_filter .= Shop::add_sql_restriction(Shop::SHARE_ORDER, 'main');
        return parent::get_webservice_object_list($sql_join, $sql_filter, $sql_sort, $sql_limit);
    }
    /**
     * Get all other orders with the same reference
     *
     * @return PrestaShopCollection
     *
     * @throws PrestaShopException
     */
    public function get_brother()
    {
        $collection = new Presta_Shop_Collection('order');
        $collection->where('reference', '=', $this->reference);
        $collection->where('id_order', '<>', $this->id);
        return $collection;
    }
    /**
     * Get a collection of order payments
     *
     * @throws PrestaShopException
     */
    public function get_order_payments()
    {
        return Order_Payment::get_by_order_reference($this->reference);
    }
    /**
     * Return a unique reference like : GWJTHMZUN#2
     *
     * With multishipping, order reference are the same for all orders made with the same cart
     * in this case this method suffix the order reference by a # and the order number
     *
     * @throws PrestaShopException
     */
    public function get_uniq_reference()
    {
        $query = new Db_Query();
        $query->select('MIN(id_order) as min, MAX(id_order) as max');
        $query->from('orders');
        $query->where('id_cart = ' . (int) $this->id_cart);
        $order = Db::read_only()->get_row($query);
        if ($order['min'] == $order['max']) {
            return $this->reference;
        }
        return $this->reference . '#' . ($this->id + 1 - $order['min']);
    }
    /**
     * Return a unique reference like : GWJTHMZUN#2
     *
     * With multishipping, order reference are the same for all orders made with the same cart
     * in this case this method suffix the order reference by a # and the order number
     *
     * @throws PrestaShopException
     */
    public static function get_uniq_reference_of($id_order)
    {
        $order = new Order($id_order);
        return $order->get_uniq_reference();
    }
    /**
     * Return id of carrier
     *
     * Get id of the carrier used in order
     *
     * @throws PrestaShopException
     */
    public function get_id_order_carrier()
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('`id_order_carrier`')->from('order_carrier')->where('`id_order` = ' . (int) $this->id));
    }
    /**
     * @param OrderInvoice|OrderSlip $a
     * @param OrderInvoice|OrderSlip $b
     *
     * @return int
     */
    public static function sort_documents($a, $b)
    {
        return $a->date_add <=> $b->date_add;
    }
    /**
     * @return string|null
     *
     * @throws PrestaShopException
     */
    public function get_ws_shipping_number()
    {
        $id_order_carrier = Db::read_only()->get_value((new Db_Query())->select('`id_order_carrier`')->from('order_carrier')->where('`id_order` = ' . (int) $this->id));
        if ($id_order_carrier) {
            $order_carrier = new Order_Carrier($id_order_carrier);
            return $order_carrier->tracking_number;
        }
        return $this->shipping_number;
    }
    /**
     * @param string $shippingNumber
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_ws_shipping_number($shipping_number)
    {
        $id_order_carrier = Db::read_only()->get_value((new Db_Query())->select('`id_order_carrier`')->from('order_carrier')->where('`id_order` = ' . (int) $this->id));
        if ($id_order_carrier) {
            $order_carrier = new Order_Carrier($id_order_carrier);
            $order_carrier->tracking_number = $shipping_number;
            $order_carrier->update();
        }
        $this->shipping_number = $shipping_number;
        return true;
    }
    /**
     * @return int
     */
    public function get_ws_current_state()
    {
        return $this->get_current_state();
    }
    /**
     * @param string $state
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function set_ws_current_state($state)
    {
        if ($this->id) {
            $this->set_current_state((int) $state);
        }
        return true;
    }
    /**
     * By default this function was made for invoice, to compute tax amounts and balance delta (because of computation made on round values).
     * If you provide $limitToOrderDetails, only these item will be taken into account. This option is useful for order slips for example,
     * where only a sublist of the order is refunded.
     *
     * @param bool|array $limitToOrderDetails Optional array of OrderDetails to take into account. False by default to take all OrderDetails from the current Order.
     *
     * @return array A list of tax rows applied to the given OrderDetails (or all OrderDetails linked to the current Order).
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_product_taxes_details($limit_to_order_details = false)
    {
        // compute products discount
        $order_discount_tax_excl = $this->total_discounts_tax_excl;
        $free_shipping_tax = 0;
        $product_specific_discounts = [];
        $cheapest_product_discounts = [];
        $order_detail_tax_rows = [];
        foreach ($this->get_cart_rules() as $order_cart_rule) {
            if ($order_cart_rule['free_shipping'] && $free_shipping_tax === 0) {
                $free_shipping_tax = $this->total_shipping_tax_incl - $this->total_shipping_tax_excl;
                $order_discount_tax_excl -= $this->total_shipping_tax_excl;
            }
            $cart_rule = new Cart_Rule($order_cart_rule['id_cart_rule']);
            if ($cart_rule->apply_discount_to_specific_product()) {
                $reduction_product = $cart_rule->get_specific_product_id();
                if (array_key_exists($reduction_product, $product_specific_discounts)) {
                    $product_specific_discounts[$reduction_product] = 0;
                }
                $product_specific_discounts[$reduction_product] += $order_cart_rule['value_tax_excl'];
                $order_discount_tax_excl -= $order_cart_rule['value_tax_excl'];
            }
            if ($cart_rule->is_cheapest_product_system_rule()) {
                $cheapest_product_id = $cart_rule->get_cheapest_product_id();
                if (!isset($cheapest_product_discounts[$cheapest_product_id])) {
                    $cheapest_product_discounts[$cheapest_product_id] = ['tax_amount' => 0, 'tax_base' => 0];
                }
                $cheapest_product_discounts[$cheapest_product_id]['tax_amount'] += (float) ($order_cart_rule['value'] - $order_cart_rule['value_tax_excl']);
                $cheapest_product_discounts[$cheapest_product_id]['tax_base'] += (float) $order_cart_rule['value_tax_excl'];
            }
        }
        // Get order_details
        $order_details = $limit_to_order_details ?: $this->get_order_detail_list();
        $tax_rates = [];
        foreach ($order_details as $order_detail) {
            $id_order_detail = $order_detail['id_order_detail'];
            $tax_calculator = Order_Detail::get_tax_calculator_static($id_order_detail);
            $discount_ratio = 0;
            if ($this->total_products > 0) {
                $discount_ratio = ($order_detail['unit_price_tax_excl'] + $order_detail['ecotax']) / $this->total_products;
            }
            // share of global discount
            $discounted_price_tax_excl = $order_detail['unit_price_tax_excl'] - $discount_ratio * $order_discount_tax_excl;
            // specific discount
            if (!empty($product_specific_discounts[$order_detail['product_id']])) {
                $discounted_price_tax_excl -= $product_specific_discounts[$order_detail['product_id']];
            }
            $quantity = $order_detail['product_quantity'];
            foreach ($tax_calculator->taxes as $tax) {
                $tax_rates[$tax->id] = $tax->rate;
            }
            foreach ($tax_calculator->get_taxes_amount($discounted_price_tax_excl) as $id_tax => $unit_amount) {
                $total_tax_base = $quantity * $discounted_price_tax_excl;
                $total_amount = $quantity * $unit_amount;
                if (isset($cheapest_product_discounts[$order_detail['product_id']]['tax_base'])) {
                    $total_tax_base -= $cheapest_product_discounts[$order_detail['product_id']]['tax_base'];
                    $total_amount -= $cheapest_product_discounts[$order_detail['product_id']]['tax_amount'];
                }
                $order_detail_tax_rows[] = ['id_order_detail' => $id_order_detail, 'id_tax' => $id_tax, 'tax_rate' => $tax_rates[$id_tax], 'unit_tax_base' => $discounted_price_tax_excl, 'total_tax_base' => $total_tax_base, 'unit_amount' => $unit_amount, 'total_amount' => $total_amount];
            }
        }
        return $order_detail_tax_rows;
    }
    /**
     * The primary purpose of this method is to be
     * called at the end of the generation of each order
     * in PaymentModule::validateOrder, to fill in
     * the order_detail_tax table with taxes
     * that will add up in such a way that
     * the sum of the tax amounts in the product tax breakdown
     * is equal to the difference between products with tax and
     * products without tax.
     *
     * @throws PrestaShopException
     */
    public function update_order_detail_tax(): void
    {
        $order_detail_tax_rows_to_insert = $this->get_product_taxes_details();
        if (empty($order_detail_tax_rows_to_insert)) {
            return;
        }
        $old_id_order_details = [];
        $values = [];
        foreach ($order_detail_tax_rows_to_insert as $row) {
            $old_id_order_details[] = (int) $row['id_order_detail'];
            $values[] = ['id_order_detail' => (int) $row['id_order_detail'], 'id_tax' => (int) $row['id_tax'], 'unit_amount' => (float) $row['unit_amount'], 'total_amount' => (float) $row['total_amount']];
        }
        $conn = Db::get_instance();
        // Remove current order_detail_tax'es
        $conn->delete('order_detail_tax', '`id_order_detail` IN (' . implode(', ', $old_id_order_details) . ')');
        // Insert the adjusted ones instead
        $conn->insert('order_detail_tax', $values);
    }
    /**
     * Get order detail taxes breakdown
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_order_detail_taxes()
    {
        return Db::read_only()->get_array((new Db_Query())->select('od.`id_tax_rules_group`, od.`product_quantity`, odt.*, t.*')->from('orders', 'o')->inner_join('order_detail', 'od', 'od.`id_order` = o.`id_order`')->inner_join('order_detail_tax', 'odt', 'odt.`id_order_detail` = od.`id_order_detail`')->inner_join('tax', 't', 't.`id_tax` = odt.`id_tax`')->where('o.`id_order` = ' . (int) $this->id));
    }
    /**
     * Adjust property total_paid_real
     *
     * Property total_paid_real is deprecated and should not be relied upon. To get information
     * about amount paid, use Order::getTotalPaid() method instead
     *
     * For backwards compatibility reasons, we try to keep this property in sync with content
     * of tb_order_payment table.
     *
     * @param float $amount
     * @param int $currencyId
     * @throws PrestaShopException
     */
    public function adjust_total_paid_amount($amount, $currency_id): void
    {
        $currency_id = (int) $currency_id;
        $amount = (float) $amount;
        $order_currency = (int) $this->id_currency;
        if ($order_currency == $currency_id) {
            $amount_order_currency = $amount;
        } else {
            // we need to convert $amount from source currency to order currency
            $amount_default_currency = Tools::convert_price($amount, $currency_id, false);
            $default_currency_id = (int) Configuration::get('PS_CURRENCY_DEFAULT');
            if ($order_currency == $default_currency_id) {
                $amount_order_currency = $amount_default_currency;
            } else {
                $amount_order_currency = Tools::convert_price($amount_default_currency, $order_currency, true);
            }
        }
        // this should be the only place in the core that modifies this deprecated property
        /** @noinspection PhpDeprecationInspection */
        $this->total_paid_real += $amount_order_currency;
    }
    public function get_delivery_date(): ?DateTime
    {
        $delivery_date = DateTime::create_from_format('Y-m-d H:i:s', (string) $this->delivery_date);
        if (!$delivery_date) {
            return null;
        }
        // filter out invalid date 0000-00-00
        $threshold = DateTime::create_from_format('Y-m-d', '1980-01-01');
        if ($delivery_date > $threshold) {
            return $delivery_date;
        }
        return null;
    }
}