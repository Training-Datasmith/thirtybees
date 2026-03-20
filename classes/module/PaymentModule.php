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
 * Class PaymentModuleCore
 */
abstract class Payment_Module_Core extends Module
{
    public const DEBUG_MODE = false;
    /**
     * @var int Current order's id
     */
    public $current_order;
    /**
     * @var string contains generated reference code of current order
     */
    public $current_order_reference;
    /**
     * @var bool $currencies
     */
    public $currencies = true;
    /**
     * @var string $currencies_mode
     */
    public $currencies_mode = 'checkbox';
    /**
     * Can be used to show that this module is compatible with the
     * Advanced EU Checkout
     *
     * Note that it is an `int`, not a `bool`, so
     * 0 = not supported
     * 1 = supported
     *
     * @var int $is_eu_compatible
     */
    public $is_eu_compatible = 0;
    /**
     * Allows specified payment modules to be used by a specific currency
     *
     * @param int $idCurrency
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function add_currency_permissions($id_currency, array $id_module_list = [])
    {
        $values = [];
        if (count($id_module_list) == 0) {
            // fetch all installed module ids
            $modules = static::get_installed_payment_modules();
            foreach ($modules as $module) {
                $id_module_list[] = $module['id_module'];
            }
        }
        foreach ($id_module_list as $id_module) {
            $values[] = ['id_module' => (int) $id_module, 'id_currency' => (int) $id_currency];
        }
        if (!empty($values)) {
            return Db::get_instance()->insert('module_currency', $values);
        }
        return true;
    }
    /**
     * List all installed and active payment modules
     *
     * @return array module information
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @see Module::getPaymentModules() if you need a list of module related to the user context
     */
    public static function get_installed_payment_modules()
    {
        $hook_payment = 'Payment';
        $connection = Db::read_only();
        if ($connection->get_value((new Db_Query())->select('`id_hook`')->from('hook')->where('`name` = \'displayPayment\''))) {
            $hook_payment = 'displayPayment';
        }
        $sql = (new Db_Query())->select('DISTINCT m.`id_module`, h.`id_hook`, m.`name`, hm.`position`')->from('module', 'm')->inner_join('module_shop', 'ms', 'm.`id_module` = ms.`id_module` AND ms.`id_shop` = ' . (int) Context::get_context()->shop->id)->left_join('hook_module', 'hm', 'hm.`id_module` = m.`id_module` AND hm.`id_shop` = ms.`id_shop`')->left_join('hook', 'h', 'hm.`id_hook` = h.`id_hook`')->where('h.`name` = \'' . p_sql($hook_payment) . '\'');
        return $connection->get_array($sql);
    }
    /**
     * @param string $moduleName
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function pre_call($module_name)
    {
        if (!parent::pre_call($module_name)) {
            return false;
        }
        if ($module_instance = Module::get_instance_by_name($module_name)) {
            /** @var PaymentModule $moduleInstance */
            if (!$module_instance->currencies || count(Currency::check_payment_currencies($module_instance->id))) {
                return true;
            }
        }
        return false;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }
        // Insert currencies availability
        if ($this->currencies_mode == 'checkbox') {
            if (!$this->add_checkbox_currency_restrictions_for_module()) {
                return false;
            }
        } elseif ($this->currencies_mode == 'radio') {
            if (!$this->add_radio_currency_restrictions_for_module()) {
                return false;
            }
        } else {
            Tools::display_error('No currency mode for payment module');
        }
        // Insert countries availability
        $return = $this->add_checkbox_country_restrictions_for_module();
        // Insert carrier availability
        $return = $this->add_checkbox_carrier_restrictions_for_module() && $return;
        if (!Configuration::get('CONF_' . strtoupper($this->name) . '_FIXED')) {
            Configuration::update_value('CONF_' . strtoupper($this->name) . '_FIXED', '0.2');
        }
        if (!Configuration::get('CONF_' . strtoupper($this->name) . '_VAR')) {
            Configuration::update_value('CONF_' . strtoupper($this->name) . '_VAR', '2');
        }
        if (!Configuration::get('CONF_' . strtoupper($this->name) . '_FIXED_FOREIGN')) {
            Configuration::update_value('CONF_' . strtoupper($this->name) . '_FIXED_FOREIGN', '0.2');
        }
        if (!Configuration::get('CONF_' . strtoupper($this->name) . '_VAR_FOREIGN')) {
            Configuration::update_value('CONF_' . strtoupper($this->name) . '_VAR_FOREIGN', '2');
        }
        return $return;
    }
    /**
     * Add checkbox currency restrictions for a new module
     *
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_checkbox_currency_restrictions_for_module(array $shops = [])
    {
        if (!$shops) {
            $shops = Shop::get_shops(true, null, true);
        }
        $currencies = Currency::get_currencies();
        foreach ($shops as $id_shop) {
            foreach ($currencies as $currency) {
                if (!Db::get_instance()->insert('module_currency', ['id_module' => (int) $this->id, 'id_shop' => (int) $id_shop, 'id_currency' => (int) $currency['id_currency']], false, true, Db::INSERT_IGNORE)) {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * Add radio currency restrictions for a new module
     *
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_radio_currency_restrictions_for_module(array $shops = [])
    {
        if (!$shops) {
            $shops = Shop::get_shops(true, null, true);
        }
        foreach ($shops as $s) {
            if (!Db::get_instance()->insert('module_currency', ['id_module' => (int) $this->id, 'id_shop' => (int) $s, 'id_currency' => '-2'])) {
                return false;
            }
        }
        return true;
    }
    /**
     * Add checkbox country restrictions for a new module
     *
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_checkbox_country_restrictions_for_module(array $shops = [])
    {
        $countries = Country::get_countries((int) Context::get_context()->language->id, true);
        //get only active country
        return Country::add_module_restrictions($shops, $countries, [['id_module' => (int) $this->id]]);
    }
    /**
     * Add checkbox carrier restrictions for a new module
     *
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_checkbox_carrier_restrictions_for_module(array $shops = [])
    {
        if (!$shops) {
            $shops = Shop::get_shops(true, null, true);
        }
        $carriers = Carrier::get_carriers((int) Context::get_context()->language->id, false, false, false, null, Carrier::ALL_CARRIERS);
        $carrier_ids = [];
        foreach ($carriers as $carrier) {
            $carrier_ids[] = $carrier['id_reference'];
        }
        foreach ($shops as $id_shop) {
            foreach ($carrier_ids as $id_carrier) {
                if (!Db::get_instance()->insert('module_carrier', ['id_module' => (int) $this->id, 'id_shop' => (int) $id_shop, 'id_reference' => (int) $id_carrier], false, true, Db::INSERT_IGNORE)) {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        $conn = Db::get_instance();
        if (!$conn->delete('module_country', '`id_module` = ' . (int) $this->id) || !$conn->delete('module_currency', '`id_module` = ' . (int) $this->id) || !$conn->delete('module_group', '`id_module` = ' . (int) $this->id) || !$conn->delete('module_carrier', '`id_module` = ' . (int) $this->id)) {
            return false;
        }
        return parent::uninstall();
    }
    /**
     * Validate an order in database
     * Function called from a payment module
     *
     * @param int $idCart
     * @param int $idOrderState
     * @param float $amountPaid Amount really paid by customer (in the default currency)
     * @param string $paymentMethod Payment method (eg. 'Credit card')
     * @param string|null $message Message to attach to order
     * @param array $extraVars
     * @param int|null $currencySpecial
     * @param bool $dontTouchAmount
     * @param bool $secureKey
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function validate_order($id_cart, $id_order_state, $amount_paid, $payment_method = 'Unknown', $message = null, $extra_vars = [], $currency_special = null, $dont_touch_amount = false, $secure_key = false, ?Shop $shop = null)
    {
        $id_cart = (int) $id_cart;
        if (!isset($this->context)) {
            $this->context = Context::get_context();
        }
        $this->context->cart = new Cart($id_cart);
        if (!Validate::is_loaded_object($this->context->cart)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Cart [%s] not found'), $id_cart));
        }
        if (!isset($this->context->customer) || (int) $this->context->customer->id !== (int) $this->context->cart->id_customer) {
            $this->context->customer = new Customer((int) $this->context->cart->id_customer);
        }
        // The tax cart is loaded before the customer so re-cache the tax calculation method
        $this->context->cart->set_tax_calculation_method();
        $this->context->language = new Language((int) $this->context->cart->id_lang);
        $this->context->shop = $shop ?: new Shop((int) $this->context->cart->id_shop);
        Shop_Url::reset_main_domain_cache();
        $id_currency = $currency_special ? (int) $currency_special : (int) $this->context->cart->id_currency;
        $this->context->currency = new Currency($id_currency, null, (int) $this->context->shop->id);
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
            $context_country = $this->context->country;
        }
        $order_status = new Order_State((int) $id_order_state, (int) $this->context->language->id);
        if (!Validate::is_loaded_object($order_status)) {
            Logger::add_log('PaymentModule::validateOrder - Order Status cannot be loaded', 3, null, 'Cart', $id_cart, true);
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Can\'t load Order status [%s]'), (int) $id_order_state));
        }
        if (!$this->active) {
            Logger::add_log('PaymentModule::validateOrder - Module is not active', 3, null, 'Cart', $id_cart, true);
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Module [%s] is not active'), $this->name));
        }
        // Does order already exists ?
        if ($this->context->cart->order_exists() == false) {
            if ($secure_key !== false && $secure_key != $this->context->cart->secure_key) {
                Logger::add_log('PaymentModule::validateOrder - Secure key does not match', 3, null, 'Cart', $id_cart, true);
                throw new Presta_Shop_Exception(Tools::display_error('Secure key does not match'));
            }
            // For each package, generate an order
            $delivery_option_list = $this->context->cart->get_delivery_option_list();
            $package_list = $this->context->cart->get_package_list();
            $cart_delivery_option = $this->context->cart->get_delivery_option();
            // If some delivery options are not defined, or not valid, use the first valid option
            foreach ($delivery_option_list as $id_address => $package) {
                if (!isset($cart_delivery_option[$id_address]) || !array_key_exists($cart_delivery_option[$id_address], $package)) {
                    foreach ($package as $key => $val) {
                        $cart_delivery_option[$id_address] = $key;
                        break;
                    }
                }
            }
            do {
                $reference = Order::generate_reference();
            } while (Order::get_by_reference($reference)->count());
            $this->current_order_reference = $reference;
            $cart_total_paid = $this->context->cart->get_order_total(true, Cart::BOTH);
            foreach ($cart_delivery_option as $id_address => $key_carriers) {
                foreach ($delivery_option_list[$id_address][$key_carriers]['carrier_list'] as $id_carrier => $data) {
                    foreach ($data['package_list'] as $id_package) {
                        // Rewrite the id_warehouse
                        $package_list[$id_address][$id_package]['id_warehouse'] = (int) $this->context->cart->get_package_id_warehouse($package_list[$id_address][$id_package], (int) $id_carrier);
                        $package_list[$id_address][$id_package]['id_carrier'] = $id_carrier;
                    }
                }
            }
            // Make sure CartRule caches are empty
            Cart_Rule::clean_cache();
            $cart_rules = $this->context->cart->get_cart_rules();
            foreach ($cart_rules as $cart_rule_entry) {
                /** @var CartRule $cartRule */
                $cart_rule = $cart_rule_entry['obj'];
                if (($rule = new Cart_Rule((int) $cart_rule->id)) && Validate::is_loaded_object($rule)) {
                    if ($error = $rule->check_validity($this->context, true, true)) {
                        $this->context->cart->remove_cart_rule((int) $rule->id);
                        if (isset($this->context->cookie) && isset($this->context->cookie->id_customer) && $this->context->cookie->id_customer && !empty($rule->code)) {
                            if (Configuration::get('PS_ORDER_PROCESS_TYPE') == 1) {
                                Tools::redirect('index.php?controller=order-opc&submitAddDiscount=1&discount_name=' . urlencode((string) $rule->code));
                            }
                            Tools::redirect('index.php?controller=order&submitAddDiscount=1&discount_name=' . urlencode((string) $rule->code));
                        } else {
                            $rule_name = $rule->name[(int) $this->context->cart->id_lang] ?? $rule->code;
                            $error = sprintf(Tools::display_error('CartRule ID %1s (%2s) used in this cart is not valid and has been withdrawn from cart. Reason: ' . $error), (int) $rule->id, $rule_name);
                            Logger::add_log($error, 3, '0000002', 'Cart', (int) $this->context->cart->id);
                        }
                    }
                }
            }
            $orders = [];
            foreach ($package_list as $id_address => $package_by_address) {
                foreach ($package_by_address as $package) {
                    $order = new Order();
                    $product_list = $package['product_list'];
                    if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
                        $address = new Address((int) $id_address);
                        $this->context->country = new Country((int) $address->id_country, (int) $this->context->cart->id_lang);
                        if (!$this->context->country->active) {
                            throw new Presta_Shop_Exception('The delivery address country is not active.');
                        }
                    }
                    $carrier = null;
                    if (!$this->context->cart->is_virtual_cart() && isset($package['id_carrier'])) {
                        $carrier = new Carrier((int) $package['id_carrier'], (int) $this->context->cart->id_lang);
                        $order->id_carrier = (int) $carrier->id;
                        $id_carrier = (int) $carrier->id;
                    } else {
                        $order->id_carrier = 0;
                        $id_carrier = 0;
                    }
                    $order->id_customer = (int) $this->context->cart->id_customer;
                    $order->id_address_invoice = (int) $this->context->cart->id_address_invoice;
                    $order->id_address_delivery = (int) $id_address;
                    $order->id_currency = $this->context->currency->id;
                    $order->id_lang = (int) $this->context->cart->id_lang;
                    $order->id_cart = (int) $this->context->cart->id;
                    $order->reference = $reference;
                    $order->id_shop = (int) $this->context->shop->id;
                    $order->id_shop_group = (int) $this->context->shop->id_shop_group;
                    $order->secure_key = $secure_key ? p_sql($secure_key) : p_sql($this->context->customer->secure_key);
                    $order->payment = $payment_method;
                    if (isset($this->name)) {
                        $order->module = $this->name;
                    }
                    $order->recyclable = $this->context->cart->recyclable;
                    $order->gift = (int) $this->context->cart->gift;
                    $order->gift_message = $this->context->cart->gift_message;
                    $order->mobile_theme = $this->context->cart->mobile_theme;
                    $order->conversion_rate = $this->context->currency->conversion_rate;
                    $amount_paid = $dont_touch_amount ? $amount_paid : Tools::ps_round($amount_paid, $this->context->currency->get_display_precision());
                    $order->total_products = (float) $this->context->cart->get_order_total(false, Cart::ONLY_PRODUCTS, $product_list, $id_carrier);
                    $order->total_products_wt = (float) $this->context->cart->get_order_total(true, Cart::ONLY_PRODUCTS, $product_list, $id_carrier);
                    $order->total_discounts_tax_excl = (float) abs($this->context->cart->get_order_total(false, Cart::ONLY_DISCOUNTS, $product_list, $id_carrier));
                    $order->total_discounts_tax_incl = (float) abs($this->context->cart->get_order_total(true, Cart::ONLY_DISCOUNTS, $product_list, $id_carrier));
                    $order->total_discounts = $order->total_discounts_tax_incl;
                    $order->total_shipping_tax_excl = (float) $this->context->cart->get_package_shipping_cost($id_carrier, false, null, $product_list);
                    $order->total_shipping_tax_incl = (float) $this->context->cart->get_package_shipping_cost($id_carrier, true, null, $product_list);
                    $order->total_shipping = $order->total_shipping_tax_incl;
                    if (!is_null($carrier) && Validate::is_loaded_object($carrier)) {
                        $order->carrier_tax_rate = $carrier->get_taxes_rate(new Address((int) $this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
                    }
                    $order->total_wrapping_tax_excl = (float) abs($this->context->cart->get_order_total(false, Cart::ONLY_WRAPPING, $product_list, $id_carrier));
                    $order->total_wrapping_tax_incl = (float) abs($this->context->cart->get_order_total(true, Cart::ONLY_WRAPPING, $product_list, $id_carrier));
                    $order->total_wrapping = $order->total_wrapping_tax_incl;
                    $order->total_paid_tax_excl = (float) $this->context->cart->get_order_total(false, Cart::BOTH, $product_list, $id_carrier);
                    $order->total_paid_tax_incl = (float) $this->context->cart->get_order_total(true, Cart::BOTH, $product_list, $id_carrier);
                    $order->total_paid = $order->total_paid_tax_incl;
                    $order->round_mode = Configuration::get('PS_PRICE_ROUND_MODE');
                    $order->round_type = (int) Configuration::get('PS_ROUND_TYPE');
                    $order->invoice_date = '0000-00-00 00:00:00';
                    $order->delivery_date = '0000-00-00 00:00:00';
                    // Creating order
                    $result = $order->add();
                    if (!$result) {
                        Logger::add_log('PaymentModule::validateOrder - Order cannot be created', 3, null, 'Cart', $id_cart, true);
                        throw new Presta_Shop_Exception('Can\'t save Order');
                    }
                    // Amount paid by customer is not the right one -> Status = payment error
                    if ($order_status->logable && (string) $cart_total_paid !== (string) $amount_paid) {
                        $id_order_state = Configuration::get('PS_OS_ERROR');
                    }
                    // Insert new Order detail list using cart for the current order
                    // Method createList uses $orderDetail as a template to create multiple order lines.
                    // Afterwards, property $orderDetail->outOfStock contains information if *any* order line out of stock
                    $order_detail = new Order_Detail(null, null, $this->context);
                    $order_detail->create_list($order, $this->context->cart, $id_order_state, $product_list, 0, true, $package['id_warehouse']);
                    $out_of_stock = $order_detail->get_stock_state();
                    // Adding an entry in order_carrier table
                    if (!is_null($carrier)) {
                        $order_carrier = new Order_Carrier();
                        $order_carrier->id_order = (int) $order->id;
                        $order_carrier->id_carrier = $id_carrier;
                        $order_carrier->weight = (float) $order->get_total_weight();
                        $order_carrier->shipping_cost_tax_excl = (float) $order->total_shipping_tax_excl;
                        $order_carrier->shipping_cost_tax_incl = (float) $order->total_shipping_tax_incl;
                        $order_carrier->add();
                    }
                    // save information about created order for further processing
                    $orders[] = ['order' => $order, 'productList' => $product_list, 'outOfStock' => $out_of_stock, 'carrierName' => $carrier ? $carrier->get_name() : Tools::display_error('No carrier')];
                }
            }
            // The country can only change if the address used for the calculation is the delivery address, and if multi-shipping is activated
            if (isset($context_country) && Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_delivery') {
                $this->context->country = $context_country;
            }
            if (!$this->context->country->active) {
                Logger::add_log('PaymentModule::validateOrder - Country is not active', 3, null, 'Cart', $id_cart, true);
                throw new Presta_Shop_Exception('The order address country is not active.');
            }
            // Register Payment only if the order status validate the order
            if ($order_status->logable) {
                // $order is the last order loop in the foreach
                // The method addOrderPayment of the class Order make a create a paymentOrder
                // linked to the order reference and not to the order id
                $transaction_id = $extra_vars['transaction_id'] ?? null;
                if (!isset($order) || !Validate::is_loaded_object($order) || !$order->add_order_payment($amount_paid, null, $transaction_id)) {
                    Logger::add_log('PaymentModule::validateOrder - Cannot save Order Payment', 3, null, 'Cart', $id_cart, true);
                    throw new Presta_Shop_Exception('Can\'t save Order Payment');
                }
            }
            // Next !
            $cart_rule_used = [];
            // Make sure CartRule caches are empty
            Cart_Rule::clean_cache();
            $single_order = count($orders) === 1;
            foreach ($orders as $entry) {
                $order = $entry['order'];
                /** @var array[] $productList */
                $product_list = $entry['productList'];
                $out_of_stock = (bool) $entry['outOfStock'];
                $carrier_name = $entry['carrierName'];
                if (!$secure_key) {
                    $message .= '<br />' . Tools::display_error('Warning: the secure key is empty, check your payment account before validation');
                }
                // Optional message to attach to this order
                if (isset($message) & !empty($message)) {
                    $msg = new Message();
                    $message = strip_tags((string) $message, '<br>');
                    if (Validate::is_clean_html($message)) {
                        $msg->message = $message;
                        $msg->id_cart = $id_cart;
                        $msg->id_customer = (int) $order->id_customer;
                        $msg->id_order = (int) $order->id;
                        $msg->private = 1;
                        $msg->add();
                    }
                }
                $product_var_tpl_list = [];
                foreach ($product_list as $product) {
                    $price = Product::get_price_static((int) $product['id_product'], false, $product['id_product_attribute'] ? (int) $product['id_product_attribute'] : null, _TB_PRICE_DATABASE_PRECISION_, null, false, true, $product['cart_quantity'], false, (int) $order->id_customer, (int) $order->id_cart, (int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
                    $price_tax_included = Product::get_price_static((int) $product['id_product'], true, $product['id_product_attribute'] ? (int) $product['id_product_attribute'] : null, _TB_PRICE_DATABASE_PRECISION_, null, false, true, $product['cart_quantity'], false, (int) $order->id_customer, (int) $order->id_cart, (int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
                    $product_price = Product::get_tax_calculation_method() == PS_TAX_EXC ? $price : $price_tax_included;
                    $product_var_tpl = ['reference' => $product['reference'], 'name' => $product['name'] . (isset($product['attributes']) ? ' - ' . $product['attributes'] : ''), 'unit_price' => Tools::display_price($product_price, $this->context->currency, false), 'price' => Tools::display_price($product_price * $product['quantity'], $this->context->currency, false), 'quantity' => $product['quantity'], 'customization' => [], 'id_product' => (int) $product['id_product'], 'id_product_attribute' => $product['id_product_attribute'] ? (int) $product['id_product_attribute'] : null, 'id_image' => $product['id_image'], 'link_rewrite' => $product['link_rewrite']];
                    $customized_datas = Product::get_all_customized_datas((int) $order->id_cart);
                    if (isset($customized_datas[$product['id_product']][$product['id_product_attribute']])) {
                        $product_var_tpl['customization'] = [];
                        foreach ($customized_datas[$product['id_product']][$product['id_product_attribute']][$order->id_address_delivery] as $customization) {
                            $customization_text = '';
                            if (isset($customization['datas'][Product::CUSTOMIZE_TEXTFIELD])) {
                                foreach ($customization['datas'][Product::CUSTOMIZE_TEXTFIELD] as $text) {
                                    $customization_text .= $text['name'] . ': ' . $text['value'] . '<br />';
                                }
                            }
                            if (isset($customization['datas'][Product::CUSTOMIZE_FILE])) {
                                $customization_text .= sprintf(Tools::display_error('%d image(s)'), count($customization['datas'][Product::CUSTOMIZE_FILE])) . '<br />';
                            }
                            $customization_quantity = (int) $product['customization_quantity'];
                            $product_var_tpl['customization'][] = ['customization_text' => $customization_text, 'customization_quantity' => $customization_quantity, 'quantity' => Tools::display_price($customization_quantity * $product_price, $this->context->currency, false)];
                        }
                    }
                    $product_var_tpl_list[] = $product_var_tpl;
                }
                $product_list_txt = '';
                $product_list_html = '';
                if (count($product_var_tpl_list) > 0) {
                    $product_list_txt = $this->get_email_template_content('order_conf_product_list.txt', Mail::TYPE_TEXT, $product_var_tpl_list);
                    $product_list_html = $this->get_email_template_content('order_conf_product_list.tpl', Mail::TYPE_HTML, $product_var_tpl_list);
                }
                $cart_rules_list = [];
                $total_reduction_value_tax_included = 0;
                $total_reduction_value_tax_excluded = 0;
                foreach ($cart_rules as $cart_rule_entry) {
                    /** @var CartRule $cartRule */
                    $cart_rule = $cart_rule_entry['obj'];
                    $package = ['id_carrier' => $order->id_carrier, 'id_address' => $order->id_address_delivery, 'products' => $product_list];
                    $values = ['tax_incl' => $cart_rule->get_contextual_value(true, $this->context, Cart_Rule::FILTER_ACTION_ALL_NOCAP, $package), 'tax_excl' => $cart_rule->get_contextual_value(false, $this->context, Cart_Rule::FILTER_ACTION_ALL_NOCAP, $package)];
                    // If the reduction is not applicable to this order, then continue with the next one
                    if (!$values['tax_excl'] && !$values['tax_incl']) {
                        continue;
                    }
                    // IF
                    //	This is not multi-shipping
                    //	The value of the voucher is greater than the total of the order
                    //	Partial use is allowed
                    //	This is an "amount" reduction, not a reduction in % or a gift
                    // THEN
                    //	The voucher is cloned with a new value corresponding to the remainder
                    if ($single_order && $values['tax_incl'] > $order->total_products_wt - $total_reduction_value_tax_included && $cart_rule->partial_use == 1 && $cart_rule->reduction_amount > 0) {
                        // Create a new voucher from the original
                        $voucher = new Cart_Rule((int) $cart_rule->id);
                        // We need to instantiate the CartRule without lang parameter to allow saving it
                        unset($voucher->id);
                        // Set a new voucher code
                        $voucher->code = empty($voucher->code) ? substr(md5($order->id . '-' . $order->id_customer . '-' . $cart_rule->id), 0, 16) : $voucher->code . '-2';
                        if (preg_match('/\-([0-9]{1,2})\-([0-9]{1,2})$/', $voucher->code, $matches) && $matches[1] == $matches[2]) {
                            $voucher->code = preg_replace('/' . $matches[0] . '$/', '-' . (intval($matches[1]) + 1), $voucher->code);
                        }
                        // Set the new voucher value
                        if ($voucher->reduction_tax) {
                            $voucher->reduction_amount = $total_reduction_value_tax_included + $values['tax_incl'] - $order->total_products_wt;
                            // Add total shipping amout only if reduction amount > total shipping
                            if ($voucher->free_shipping == 1 && $voucher->reduction_amount >= $order->total_shipping_tax_incl) {
                                $voucher->reduction_amount -= $order->total_shipping_tax_incl;
                            }
                        } else {
                            $voucher->reduction_amount = $total_reduction_value_tax_excluded + $values['tax_excl'] - $order->total_products;
                            // Add total shipping amout only if reduction amount > total shipping
                            if ($voucher->free_shipping == 1 && $voucher->reduction_amount >= $order->total_shipping_tax_excl) {
                                $voucher->reduction_amount -= $order->total_shipping_tax_excl;
                            }
                        }
                        $voucher->reduction_amount = Tools::round_price((float) $voucher->reduction_amount);
                        if ($voucher->reduction_amount <= 0.0) {
                            continue;
                        }
                        if ($this->context->customer->is_guest()) {
                            $voucher->id_customer = 0;
                        } else {
                            $voucher->id_customer = $order->id_customer;
                        }
                        $voucher->quantity = 1;
                        $voucher->reduction_currency = $order->id_currency;
                        $voucher->quantity_per_user = 1;
                        $voucher->free_shipping = 0;
                        if ($voucher->add()) {
                            // If the voucher has conditions, they are now copied to the new voucher
                            Cart_Rule::copy_conditions($cart_rule->id, $voucher->id);
                            $params = ['{voucher_amount}' => Tools::display_price($voucher->reduction_amount, $this->context->currency, false), '{voucher_num}' => $voucher->code, '{firstname}' => $this->context->customer->firstname, '{lastname}' => $this->context->customer->lastname, '{id_order}' => $order->reference, '{order_name}' => $order->get_uniq_reference()];
                            Mail::Send((int) $order->id_lang, 'voucher', sprintf(Mail::l('New voucher for your order %s', (int) $order->id_lang), $order->reference), $params, $this->context->customer->email, $this->context->customer->firstname . ' ' . $this->context->customer->lastname, null, null, null, null, _PS_MAIL_DIR_, false, (int) $order->id_shop);
                        }
                        $values['tax_incl'] = $order->total_products_wt - $total_reduction_value_tax_included;
                        $values['tax_excl'] = $order->total_products - $total_reduction_value_tax_excluded;
                    }
                    // Copy a cart rule in case the cheapest product that meets the requirements gets a discount
                    // The copied cart rule is converted into a product specific cart rule
                    if ($cart_rule->product_restriction && $cart_rule->reduction_percent && $cart_rule->apply_discount_to_cheapest_product_from_selection()) {
                        // Create a new voucher from the original
                        $voucher = new Cart_Rule((int) $cart_rule->id);
                        // We need to instantiate the CartRule without lang parameter to allow saving it
                        if ($cheapest_product = $voucher->find_cheapest_product($package)) {
                            unset($voucher->id);
                            // Set a new voucher code
                            $voucher->code = empty($voucher->code) ? substr(md5($order->id . '-' . $order->id_customer . '-' . $cart_rule->id), 0, 16) : $voucher->code . '-2';
                            if (preg_match('/\-([0-9]{1,2})\-([0-9]{1,2})$/', $voucher->code, $matches) && $matches[1] == $matches[2]) {
                                $voucher->code = preg_replace('/' . $matches[0] . '$/', '-' . (intval($matches[1]) + 1), $voucher->code);
                            }
                            if ($this->context->customer->is_guest()) {
                                $voucher->id_customer = 0;
                            } else {
                                $voucher->id_customer = $order->id_customer;
                            }
                            $cheapest_product = explode('-', $cheapest_product);
                            $voucher->reduction_currency = $order->id_currency;
                            $voucher->quantity = 0;
                            $voucher->quantity_per_user = 0;
                            $voucher->active = 0;
                            $voucher->product_restriction = 1;
                            $voucher->reduction_product = Cart_Rule::APPLY_DISCOUNT_TO_ORDER_WITHOUT_SHIPPING;
                            $voucher->set_cheapest_product_system_rule((int) $cheapest_product[0], (int) $cheapest_product[1]);
                            $voucher->add();
                            // load new cart rule in single language context
                            $cart_rule = new Cart_Rule($voucher->id, $cart_rule->id_lang);
                        }
                    }
                    $total_reduction_value_tax_included += $values['tax_incl'];
                    $total_reduction_value_tax_excluded += $values['tax_excl'];
                    $order->add_cart_rule($cart_rule->id, $cart_rule->name, $values, 0, $cart_rule->free_shipping);
                    if ($id_order_state != Configuration::get('PS_OS_ERROR') && $id_order_state != Configuration::get('PS_OS_CANCELED') && !in_array($cart_rule->id, $cart_rule_used)) {
                        $cart_rule_used[] = $cart_rule->id;
                        // Create a new instance of Cart Rule without id_lang, in order to update its quantity
                        $cart_rule_to_update = new Cart_Rule((int) $cart_rule->id);
                        $cart_rule_to_update->quantity = max(0, $cart_rule_to_update->quantity - 1);
                        $cart_rule_to_update->update();
                    }
                    $cart_rules_list[] = ['voucher_name' => $cart_rule->name, 'voucher_reduction' => ($values['tax_incl'] != 0.0 ? '-' : '') . Tools::display_price($values['tax_incl'], $this->context->currency, false)];
                }
                $cart_rules_list_txt = '';
                $cart_rules_list_html = '';
                if (count($cart_rules_list) > 0) {
                    $cart_rules_list_txt = $this->get_email_template_content('order_conf_cart_rules.txt', Mail::TYPE_TEXT, $cart_rules_list);
                    $cart_rules_list_html = $this->get_email_template_content('order_conf_cart_rules.tpl', Mail::TYPE_HTML, $cart_rules_list);
                }
                // Specify order id for message
                $old_message = Message::get_message_by_cart_id((int) $this->context->cart->id);
                if ($old_message && !$old_message['private']) {
                    $update_message = new Message((int) $old_message['id_message']);
                    $update_message->id_order = (int) $order->id;
                    $update_message->update();
                    // Add this message in the customer thread
                    $customer_thread = new Customer_Thread();
                    $customer_thread->id_contact = 0;
                    $customer_thread->id_customer = (int) $order->id_customer;
                    $customer_thread->id_shop = (int) $this->context->shop->id;
                    $customer_thread->id_order = (int) $order->id;
                    $customer_thread->id_lang = (int) $this->context->language->id;
                    $customer_thread->email = $this->context->customer->email;
                    $customer_thread->status = 'open';
                    $customer_thread->token = Tools::passwd_gen(12);
                    $customer_thread->add();
                    $customer_message = new Customer_Message();
                    $customer_message->id_customer_thread = $customer_thread->id;
                    $customer_message->id_employee = 0;
                    $customer_message->message = $update_message->message;
                    $customer_message->private = 0;
                    $customer_message->add();
                }
                // Hook validate order
                Hook::trigger_event('actionValidateOrder', ['cart' => $this->context->cart, 'order' => $order, 'customer' => $this->context->customer, 'currency' => $this->context->currency, 'orderStatus' => $order_status]);
                // Log sales statistics
                if ($order_status->logable) {
                    foreach ($product_list as $product) {
                        Product_Sale::add_product_sale((int) $product['id_product'], (int) $product['cart_quantity']);
                    }
                }
                // Set the order status
                $new_history = new Order_History();
                $new_history->id_order = (int) $order->id;
                $new_history->change_id_order_state((int) $id_order_state, $order, true);
                $new_history->add_withemail(true, $extra_vars);
                // Switch to back order if needed
                if (Configuration::get('PS_STOCK_MANAGEMENT') && $out_of_stock) {
                    $history = new Order_History();
                    $history->id_order = (int) $order->id;
                    $history->change_id_order_state(Configuration::get($order->valid ? 'PS_OS_OUTOFSTOCK_PAID' : 'PS_OS_OUTOFSTOCK_UNPAID'), $order, true);
                    $history->add_withemail();
                }
                // Order is reloaded because the status just changed
                $order = new Order((int) $order->id);
                // Send an e-mail to customer (one order = one email)
                if ($id_order_state != Configuration::get('PS_OS_ERROR') && $id_order_state != Configuration::get('PS_OS_CANCELED') && $this->context->customer->id) {
                    $invoice = new Address((int) $order->id_address_invoice);
                    $delivery = new Address((int) $order->id_address_delivery);
                    $delivery_state = $delivery->id_state ? new State((int) $delivery->id_state) : false;
                    $invoice_state = $invoice->id_state ? new State((int) $invoice->id_state) : false;
                    $data = ['{firstname}' => $this->context->customer->firstname, '{lastname}' => $this->context->customer->lastname, '{email}' => $this->context->customer->email, '{delivery_block_txt}' => $this->_get_formated_address($delivery, "\n"), '{invoice_block_txt}' => $this->_get_formated_address($invoice, "\n"), '{delivery_block_html}' => $this->_get_formated_address($delivery, '<br />', ['firstname' => '<span style="font-weight:bold;">%s</span>', 'lastname' => '<span style="font-weight:bold;">%s</span>']), '{invoice_block_html}' => $this->_get_formated_address($invoice, '<br />', ['firstname' => '<span style="font-weight:bold;">%s</span>', 'lastname' => '<span style="font-weight:bold;">%s</span>']), '{delivery_company}' => $delivery->company, '{delivery_firstname}' => $delivery->firstname, '{delivery_lastname}' => $delivery->lastname, '{delivery_address1}' => $delivery->address1, '{delivery_address2}' => $delivery->address2, '{delivery_city}' => $delivery->city, '{delivery_postal_code}' => $delivery->postcode, '{delivery_country}' => $delivery->country, '{delivery_state}' => $delivery->id_state ? $delivery_state->name : '', '{delivery_phone}' => $delivery->phone ?: $delivery->phone_mobile, '{delivery_other}' => $delivery->other, '{invoice_company}' => $invoice->company, '{invoice_vat_number}' => $invoice->vat_number, '{invoice_firstname}' => $invoice->firstname, '{invoice_lastname}' => $invoice->lastname, '{invoice_address2}' => $invoice->address2, '{invoice_address1}' => $invoice->address1, '{invoice_city}' => $invoice->city, '{invoice_postal_code}' => $invoice->postcode, '{invoice_country}' => $invoice->country, '{invoice_state}' => $invoice->id_state ? $invoice_state->name : '', '{invoice_phone}' => $invoice->phone ?: $invoice->phone_mobile, '{invoice_other}' => $invoice->other, '{order_name}' => $order->get_uniq_reference(), '{order_id}' => $order->id, '{date}' => Tools::display_date(date('Y-m-d H:i:s'), null, 1), '{carrier}' => $carrier_name, '{payment}' => mb_substr($order->payment, 0, 32), '{products}' => $product_list_html, '{products_txt}' => $product_list_txt, '{discounts}' => $cart_rules_list_html, '{discounts_txt}' => $cart_rules_list_txt, '{total_paid}' => Tools::display_price($order->total_paid, $this->context->currency, false), '{total_products}' => Tools::display_price(Product::get_tax_calculation_method() == PS_TAX_EXC ? $order->total_products : $order->total_products_wt, $this->context->currency, false), '{total_discounts}' => Tools::display_price($order->total_discounts, $this->context->currency, false), '{total_shipping}' => Tools::display_price($order->total_shipping, $this->context->currency, false), '{total_wrapping}' => Tools::display_price($order->total_wrapping, $this->context->currency, false), '{total_tax_paid}' => Tools::display_price($order->total_products_wt - $order->total_products + ($order->total_shipping_tax_incl - $order->total_shipping_tax_excl), $this->context->currency, false)];
                    if (is_array($extra_vars)) {
                        $data = array_merge($data, $extra_vars);
                    }
                    // Join PDF invoice
                    if ((int) Configuration::get('PS_INVOICE') && $order_status->invoice && $order->invoice_number) {
                        $order_invoice_list = $order->get_invoices_collection();
                        Hook::trigger_event('actionPDFInvoiceRender', ['order_invoice_list' => $order_invoice_list]);
                        $pdf = new PDF($order_invoice_list, PDF::TEMPLATE_INVOICE, $this->context->smarty);
                        $file_attachment['content'] = $pdf->render(false);
                        $file_attachment['name'] = Configuration::get('PS_INVOICE_PREFIX', (int) $order->id_lang, null, $order->id_shop) . sprintf('%06d', $order->invoice_number) . '.pdf';
                        $file_attachment['mime'] = 'application/pdf';
                    } else {
                        $file_attachment = null;
                    }
                    if (Validate::is_email($this->context->customer->email)) {
                        Mail::Send((int) $order->id_lang, 'order_conf', Mail::l('Order confirmation', (int) $order->id_lang), $data, $this->context->customer->email, $this->context->customer->firstname . ' ' . $this->context->customer->lastname, null, null, $file_attachment, null, _PS_MAIL_DIR_, false, (int) $order->id_shop);
                    }
                }
                // updates stock in shops
                if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
                    $product_list = $order->get_products();
                    foreach ($product_list as $product) {
                        // if the available quantities depends on the physical stock
                        if (Stock_Available::depends_on_stock($product['product_id'])) {
                            // synchronizes
                            Stock_Available::synchronize($product['product_id'], $order->id_shop);
                        }
                    }
                }
                $order->update_order_detail_tax();
            }
            // Use the last order as currentOrder
            if (isset($order) && $order->id) {
                $this->current_order = (int) $order->id;
            }
            return true;
        }
        throw new Presta_Shop_Exception(sprintf(Tools::display_error('Order has already been placed using cart [%s]'), $id_cart));
    }
    /**
     * Fetch the content of $template_name inside the folder current_theme/mails/current_iso_lang/ if found, otherwise in mails/current_iso_lang
     *
     * @param string $templateName template name with extension
     * @param int $mailType Mail::TYPE_HTML or Mail::TYPE_TXT
     * @param array $var list send to smarty
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function get_email_template_content($template_name, $mail_type, $var)
    {
        $email_configuration = Configuration::get('PS_MAIL_TYPE');
        if ($email_configuration != $mail_type && $email_configuration != Mail::TYPE_BOTH) {
            return '';
        }
        $theme_template_path = _PS_THEME_DIR_ . 'mails' . DIRECTORY_SEPARATOR . $this->context->language->iso_code . DIRECTORY_SEPARATOR . $template_name;
        $default_mail_template_path = _PS_MAIL_DIR_ . $this->context->language->iso_code . DIRECTORY_SEPARATOR . $template_name;
        if (file_exists($theme_template_path)) {
            $default_mail_template_path = $theme_template_path;
        }
        if (file_exists($default_mail_template_path)) {
            $this->context->smarty->assign('list', $var);
            return $this->context->smarty->fetch($default_mail_template_path);
        }
        return '';
    }
    /**
     * @param string $lineSep Address $the_address that needs to be txt formatted
     * @param array $fieldsStyle
     * @return String the txt formated address block
     * @throws PrestaShopException
     */
    protected function _get_formated_address(Address $the_address, $line_sep, $fields_style = [])
    {
        return Address_Format::generate_address($the_address, ['avoid' => []], $line_sep, ' ', $fields_style);
    }
    /**
     * @param string $content
     *
     * @return string
     *
     * @deprecated 1.0.0
     */
    public function format_product_and_voucher_for_email($content)
    {
        Tools::display_as_deprecated();
        return $content;
    }
    /**
     * @param int|null $currentIdCurrency
     *
     * @return array|Currency|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_currency($current_id_currency = null)
    {
        if (!(int) $current_id_currency) {
            $current_id_currency = Context::get_context()->currency->id;
        }
        if (!$this->currencies) {
            return false;
        }
        if ($this->currencies_mode == 'checkbox') {
            return Currency::get_payment_currencies($this->id);
        }
        if ($this->currencies_mode == 'radio') {
            $currencies = Currency::get_payment_currencies_special($this->id);
            $currency = $currencies['id_currency'];
            if ($currency == -1) {
                $id_currency = (int) $current_id_currency;
            } elseif ($currency == -2) {
                $id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
            } else {
                $id_currency = $currency;
            }
        }
        if (empty($id_currency)) {
            return false;
        }
        return new Currency((int) $id_currency);
    }
    /**
     * @param Object $theAddress Address $the_address that needs to be txt formated
     *
     * @return String the txt formated address block
     *
     * @throws PrestaShopException
     */
    protected function _get_txt_formated_address($the_address)
    {
        $adr_fields = Address_Format::get_ordered_address_fields($the_address->id_country, false, true);
        $r_values = [];
        foreach ($adr_fields as $fields_line) {
            $tmp_values = [];
            foreach (explode(' ', $fields_line) as $field_item) {
                $field_item = trim($field_item);
                $tmp_values[] = $the_address->{$field_item};
            }
            $r_values[] = implode(' ', $tmp_values);
        }
        return implode("\n", $r_values);
    }
}