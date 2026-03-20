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
class Order_History_Core extends Object_Model
{
    /** @var int Order id */
    public $id_order;
    /** @var int Order status id */
    public $id_order_state;
    /** @var int Employee id for this history entry */
    public $id_employee;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'order_history', 'primary' => 'id_order_history', 'fields' => ['id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false], 'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_order_state' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false]], 'keys' => ['order_history' => ['id_employee' => ['type' => Object_Model::KEY, 'columns' => ['id_employee']], 'id_order_state' => ['type' => Object_Model::KEY, 'columns' => ['id_order_state']], 'order_history_order' => ['type' => Object_Model::KEY, 'columns' => ['id_order']]]]];
    /**
     * @see ObjectModel::$webserviceParameters
     */
    protected $webservice_parameters = ['objectsNodeName' => 'order_histories', 'fields' => ['id_employee' => ['xlink_resource' => 'employees'], 'id_order_state' => ['required' => true, 'xlink_resource' => 'order_states'], 'id_order' => ['xlink_resource' => 'orders']], 'objectMethods' => ['add' => 'addWs']];
    /**
     * Sets the new state of the given order
     *
     * @param int $newOrderState
     * @param int|Order|OrderCore $idOrder
     * @param bool $useExistingPayment
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function change_id_order_state($new_order_state, $id_order, $use_existing_payment = false)
    {
        if (!$new_order_state || !$id_order) {
            return false;
        }
        $order = $this->resolve_order($id_order);
        if (!Validate::is_loaded_object($order)) {
            return false;
        }
        $new_os = new Order_State((int) $new_order_state, $order->id_lang);
        $old_os = $order->get_current_order_state();
        // executes hook
        if (in_array($new_os->id, [Configuration::get('PS_OS_PAYMENT'), Configuration::get('PS_OS_WS_PAYMENT')])) {
            Hook::trigger_event('actionPaymentConfirmation', ['id_order' => (int) $order->id], $order->id_shop);
        }
        Hook::trigger_event('actionOrderStatusUpdate', ['newOrderStatus' => $new_os, 'id_order' => (int) $order->id, 'order' => $order], $order->id_shop);
        // An email is sent the first time a virtual item is validated
        $virtual_products = $order->get_virtual_products();
        if (is_array($virtual_products) && !empty($virtual_products) && (!$old_os || !$old_os->logable) && $new_os->logable) {
            $assign = [];
            foreach ($virtual_products as $key => $virtual_product) {
                $id_product_download = Product_Download::get_id_from_id_product($virtual_product['product_id']);
                $product_download = new Product_Download($id_product_download);
                // If this virtual item has an associated file, we'll provide the link to download the file in the email
                if ($product_download->display_filename != '') {
                    $assign[$key]['name'] = $product_download->display_filename;
                    $download_link = $product_download->get_text_link(false, $virtual_product['download_hash'], ['id_order' => (int) $order->id, 'secure_key' => $order->secure_key]);
                    $assign[$key]['link'] = $download_link;
                    if (isset($virtual_product['download_deadline']) && $virtual_product['download_deadline'] != '0000-00-00 00:00:00') {
                        $assign[$key]['deadline'] = Tools::display_date($virtual_product['download_deadline']);
                    }
                    if ($product_download->nb_downloadable != 0) {
                        $assign[$key]['downloadable'] = (int) $product_download->nb_downloadable;
                    }
                }
            }
            $customer = new Customer((int) $order->id_customer);
            $links = '<ul>';
            foreach ($assign as $product) {
                $links .= '<li>';
                $links .= '<a href="' . $product['link'] . '">' . Tools::htmlentities_utf8($product['name']) . '</a>';
                if (isset($product['deadline'])) {
                    $links .= '&nbsp;' . Tools::htmlentities_utf8(Tools::display_error('expires on', false)) . '&nbsp;' . $product['deadline'];
                }
                if (isset($product['downloadable'])) {
                    $links .= '&nbsp;' . Tools::htmlentities_utf8(sprintf(Tools::display_error('downloadable %d time(s)', false), $product['downloadable']));
                }
                $links .= '</li>';
            }
            $links .= '</ul>';
            $data = ['{lastname}' => $customer->lastname, '{firstname}' => $customer->firstname, '{id_order}' => (int) $order->id, '{order_name}' => $order->get_uniq_reference(), '{nbProducts}' => count($virtual_products), '{virtualProducts}' => $links];
            // If there is at least one downloadable file
            if (!empty($assign)) {
                Mail::Send((int) $order->id_lang, 'download_product', Mail::l('The virtual product that you bought is available for download', $order->id_lang), $data, $customer->email, $customer->firstname . ' ' . $customer->lastname, null, null, null, null, _PS_MAIL_DIR_, false, (int) $order->id_shop);
            }
        }
        // @since 1.5.0 : gets the stock manager
        $manager = null;
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
            $manager = Stock_Manager_Factory::get_manager();
        }
        $error_or_canceled_statuses = [Configuration::get('PS_OS_ERROR'), Configuration::get('PS_OS_CANCELED')];
        if (!(int) $this->id_employee || !Validate::is_loaded_object($employee = new Employee((int) $this->id_employee))) {
            if (!Validate::is_loaded_object($old_os)) {
                // First OrderHistory, there is no $old_os, so $employee is null before here
                $employee = Context::get_context()->employee;
                // filled if from BO and order created (because no old_os)
                if ($employee) {
                    $this->id_employee = $employee->id;
                }
            } else {
                $employee = null;
            }
        }
        // foreach products of the order
        foreach ($order->get_products_detail() as $product) {
            if (Validate::is_loaded_object($old_os)) {
                // if becoming logable => adds sale
                if ($new_os->logable && !$old_os->logable) {
                    Product_Sale::add_product_sale($product['product_id'], $product['product_quantity']);
                    // @since 1.5.0 - Stock Management
                    if (!Pack::is_pack($product['product_id']) && in_array($old_os->id, $error_or_canceled_statuses) && !Stock_Available::depends_on_stock($product['id_product'], (int) $order->id_shop)) {
                        Stock_Available::update_quantity($product['product_id'], $product['product_attribute_id'], -(int) $product['product_quantity'], $order->id_shop);
                    }
                } elseif (!$new_os->logable && $old_os->logable) {
                    // if becoming unlogable => removes sale
                    Product_Sale::remove_product_sale($product['product_id'], $product['product_quantity']);
                    // @since 1.5.0 - Stock Management
                    if (!Pack::is_pack($product['product_id']) && in_array($new_os->id, $error_or_canceled_statuses) && !Stock_Available::depends_on_stock($product['id_product'])) {
                        Stock_Available::update_quantity($product['product_id'], $product['product_attribute_id'], (int) $product['product_quantity'], $order->id_shop);
                    }
                } elseif (!$new_os->logable && !$old_os->logable && in_array($new_os->id, $error_or_canceled_statuses) && !in_array($old_os->id, $error_or_canceled_statuses) && !Stock_Available::depends_on_stock($product['id_product'])) {
                    // if waiting for payment => payment error/canceled
                    Stock_Available::update_quantity($product['product_id'], $product['product_attribute_id'], (int) $product['product_quantity'], $order->id_shop);
                }
            }
            // From here, there is 2 cases : $old_os exists, and we can test shipped state evolution,
            // Or old_os does not exists, and we should consider that initial shipped state is 0 (to allow decrease of stocks)
            // @since 1.5.0 : if the order is being shipped and this products uses the advanced stock management :
            // decrements the physical stock using $id_warehouse
            if ($new_os->shipped == 1 && (!Validate::is_loaded_object($old_os) || $old_os->shipped == 0) && Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && Warehouse::exists($product['id_warehouse']) && $manager != null && (int) $product['advanced_stock_management'] == 1) {
                // gets the warehouse
                $warehouse = new Warehouse($product['id_warehouse']);
                // decrements the stock (if it's a pack, the StockManager does what is needed)
                $manager->remove_product($product['product_id'], $product['product_attribute_id'], $warehouse, $product['product_quantity'] - $product['product_quantity_refunded'] - $product['product_quantity_return'], Configuration::get('PS_STOCK_CUSTOMER_ORDER_REASON'), true, (int) $order->id, 0, $employee);
            } elseif ($new_os->shipped == 0 && Validate::is_loaded_object($old_os) && $old_os->shipped == 1 && Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && Warehouse::exists($product['id_warehouse']) && $manager != null && (int) $product['advanced_stock_management'] == 1) {
                // @since.1.5.0 : if the order was shipped, and is not anymore, we need to restock products
                // if the product is a pack, we restock every products in the pack using the last negative stock mvts
                if (Pack::is_pack($product['product_id'])) {
                    $pack_products = Pack::get_items($product['product_id'], Configuration::get('PS_LANG_DEFAULT', null, null, $order->id_shop));
                    if ($pack_products) {
                        foreach ($pack_products as $pack_product) {
                            if ($pack_product->advanced_stock_management == 1) {
                                $mvts = Stock_Mvt::get_negative_stock_mvts($order->id, $pack_product->id, 0, $pack_product->pack_quantity * $product['product_quantity']);
                                foreach ($mvts as $mvt) {
                                    $manager->add_product($pack_product->id, 0, new Warehouse($mvt['id_warehouse']), $mvt['physical_quantity'], null, $mvt['price_te'], true, null);
                                }
                                if (!Stock_Available::depends_on_stock($product['id_product'])) {
                                    Stock_Available::update_quantity($pack_product->id, 0, (int) $pack_product->pack_quantity * $product['product_quantity'], $order->id_shop);
                                }
                            }
                        }
                    }
                } else {
                    // else, it's not a pack, re-stock using the last negative stock mvts
                    $mvts = Stock_Mvt::get_negative_stock_mvts($order->id, $product['product_id'], $product['product_attribute_id'], $product['product_quantity'] - $product['product_quantity_refunded'] - $product['product_quantity_return']);
                    foreach ($mvts as $mvt) {
                        $manager->add_product($product['product_id'], $product['product_attribute_id'], new Warehouse($mvt['id_warehouse']), $mvt['physical_quantity'], null, $mvt['price_te'], true);
                    }
                }
            }
        }
        $this->id_order_state = (int) $new_order_state;
        // changes invoice number of order ?
        if (!Validate::is_loaded_object($new_os)) {
            throw new Presta_Shop_Exception(Tools::display_error('Invalid new order status') . ' ' . (int) $new_order_state);
        }
        if (!Validate::is_loaded_object($order)) {
            throw new Presta_Shop_Exception(Tools::display_error('Order does not exists'));
        }
        // the order is valid if and only if the invoice is available and the order is not cancelled
        $order->current_state = $this->id_order_state;
        $order->valid = $new_os->logable;
        $order->update();
        if ($new_os->invoice && !$order->invoice_number) {
            $order->set_invoice($use_existing_payment);
        } elseif ($new_os->delivery && !$order->delivery_number) {
            $order->set_delivery_slip();
        }
        // set orders as paid
        if ($new_os->paid == 1) {
            $invoices = $order->get_invoices_collection();
            if ($order->total_paid != 0) {
                $payment_method = Module::get_instance_by_name($order->module);
            }
            foreach ($invoices as $invoice) {
                /** @var OrderInvoice $invoice */
                $rest_paid = $invoice->get_rest_paid();
                if ($rest_paid > 0) {
                    $payment = new Order_Payment();
                    $payment->order_reference = mb_substr($order->reference, 0, 9);
                    $payment->id_currency = $order->id_currency;
                    $payment->amount = $rest_paid;
                    if (isset($payment_method) && $order->total_paid != 0) {
                        $payment->payment_method = $payment_method->display_name;
                    } else {
                        $payment->payment_method = null;
                    }
                    $order->adjust_total_paid_amount($payment->amount, $payment->id_currency);
                    $order->save();
                    $payment->conversion_rate = 1;
                    $payment->save();
                    Db::get_instance()->insert('order_invoice_payment', ['id_order_invoice' => (int) $invoice->id, 'id_order_payment' => (int) $payment->id, 'id_order' => (int) $order->id]);
                }
            }
        }
        // updates delivery date even if it was already set by another state change
        if ($new_os->delivery) {
            $order->set_delivery();
        }
        // executes hook
        Hook::trigger_event('actionOrderStatusPostUpdate', ['newOrderStatus' => $new_os, 'id_order' => (int) $order->id, 'order' => $order], $order->id_shop);
        Shop_Url::reset_main_domain_cache();
        return true;
    }
    /**
     * Returns the last order status
     *
     * @param int $idOrder
     *
     * @return OrderState|false
     *
     * @deprecated 2.0.0
     * @see Order->current_state
     * @throws PrestaShopException
     */
    public static function get_last_order_state($id_order)
    {
        Tools::display_as_deprecated();
        $id_order_state = (int) Db::read_only()->get_value((new Db_Query())->select('`id_order_state`')->from('order_history')->where('`id_order` = ' . (int) $id_order)->order_by('`date_add` DESC, `id_order_history` DESC'));
        // returns false if there is no state
        if (!$id_order_state) {
            return false;
        }
        // else, returns an OrderState object if it can be loaded
        $order_state = new Order_State($id_order_state, Configuration::get('PS_LANG_DEFAULT'));
        if (Validate::is_loaded_object($order_state)) {
            return $order_state;
        }
        return false;
    }
    /**
     * @param bool $autodate Optional
     * @param bool|array $templateVars Optional
     * @param Context|null $context Deprecated
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function add_withemail($autodate = true, $template_vars = false, ?Context $context = null)
    {
        $order = new Order($this->id_order);
        if (!$this->add($autodate)) {
            return false;
        }
        if (!$this->send_email($order, $template_vars)) {
            return false;
        }
        return true;
    }
    /**
     * @param Order $order
     * @param array|bool $templateVars
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function send_email($order, $template_vars = false)
    {
        $sql = (new Db_Query())->select('osl.template')->select('osl.email_subject')->select('c.lastname')->select('c.firstname')->select('osl.name AS osname')->select('c.email')->select('os.module_name')->select('os.id_order_state')->select('os.pdf_invoice')->select('os.pdf_delivery')->from('order_history', 'oh')->inner_join('orders', 'o', '(oh.id_order = o.id_order)')->inner_join('customer', 'c', '(o.id_customer = c.id_customer)')->inner_join('order_state', 'os', '(oh.id_order_state = os.id_order_state)')->inner_join('order_state_lang', 'osl', '(os.id_order_state = osl.id_order_state AND osl.id_lang = o.id_lang)')->where('os.send_email = 1')->where('oh.id_order_history = ' . (int) $this->id);
        $result = Db::read_only()->get_row($sql);
        if ($result && $result['template'] && Validate::is_email($result['email'])) {
            $subject = trim((string) $result['email_subject']);
            if (!$subject) {
                $subject = trim((string) $result['osname']);
            }
            $carrier_url = '';
            if (Validate::is_loaded_object($carrier = new Carrier((int) $order->id_carrier, $order->id_lang))) {
                $carrier_url = (string) $carrier->url;
            }
            $data = ['{lastname}' => $result['lastname'], '{firstname}' => $result['firstname'], '{id_order}' => (int) $this->id_order, '{order_name}' => $order->get_uniq_reference(), '{followup}' => str_replace('@', $order->get_ws_shipping_number(), $carrier_url), '{shipping_number}' => $order->get_ws_shipping_number(), '{invoice_number}' => $order->invoice_number];
            if ($result['module_name']) {
                $module = Module::get_instance_by_name($result['module_name']);
                if (Validate::is_loaded_object($module) && isset($module->extra_mail_vars) && is_array($module->extra_mail_vars)) {
                    $data = array_merge($data, $module->extra_mail_vars);
                }
            }
            if ($template_vars) {
                $data = array_merge($data, $template_vars);
            }
            $data['{total_paid}'] = Tools::display_price((float) $order->total_paid, new Currency((int) $order->id_currency), false);
            if (Validate::is_loaded_object($order)) {
                // Attach invoice and / or delivery-slip if they exists and status is set to attach them
                if ($result['pdf_invoice'] || $result['pdf_delivery']) {
                    $context = Context::get_context();
                    $invoice = $order->get_invoices_collection();
                    $file_attachement = [];
                    if ($result['pdf_invoice'] && (int) Configuration::get('PS_INVOICE') && $order->invoice_number) {
                        Hook::trigger_event('actionPDFInvoiceRender', ['order_invoice_list' => $invoice]);
                        $pdf = new PDF($invoice, PDF::TEMPLATE_INVOICE, $context->smarty);
                        $file_attachement['invoice']['content'] = $pdf->render(false);
                        $file_attachement['invoice']['name'] = Configuration::get('PS_INVOICE_PREFIX', (int) $order->id_lang, null, $order->id_shop) . sprintf('%06d', $order->invoice_number) . '.pdf';
                        $file_attachement['invoice']['mime'] = 'application/pdf';
                    }
                    if ($result['pdf_delivery'] && $order->delivery_number) {
                        $pdf = new PDF($invoice, PDF::TEMPLATE_DELIVERY_SLIP, $context->smarty);
                        $file_attachement['delivery']['content'] = $pdf->render(false);
                        $file_attachement['delivery']['name'] = Configuration::get('PS_DELIVERY_PREFIX', Context::get_context()->language->id, null, $order->id_shop) . sprintf('%06d', $order->delivery_number) . '.pdf';
                        $file_attachement['delivery']['mime'] = 'application/pdf';
                    }
                } else {
                    $file_attachement = null;
                }
                if (!Mail::Send((int) $order->id_lang, $result['template'], $subject, $data, $result['email'], $result['firstname'] . ' ' . $result['lastname'], null, null, $file_attachement, null, _PS_MAIL_DIR_, false, (int) $order->id_shop)) {
                    return false;
                }
            }
            Shop_Url::reset_main_domain_cache();
        }
        return true;
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if (!parent::add($auto_date)) {
            return false;
        }
        $order = new Order((int) $this->id_order);
        // Update id_order_state attribute in Order
        $order->current_state = $this->id_order_state;
        $order->update();
        Hook::trigger_event('actionOrderHistoryAddAfter', ['order_history' => $this], $order->id_shop);
        return true;
    }
    /**
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function is_validated()
    {
        return Db::read_only()->get_value((new Db_Query())->select('COUNT(oh.`id_order_history` AS `nb`')->from('order_state', 'os')->left_join('order_history', 'oh', 'os.`id_order_state` = oh.`id_order_state`')->where('oh.`id_order` = ' . (int) $this->id_order)->where('od.`logable` = 1'));
    }
    /**
     * Add method for webservice create resource Order History
     * If sendemail=1 GET parameter is present sends email to customer otherwise does not
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function add_ws()
    {
        $sendemail = (bool) Tools::get_value('sendemail', false);
        $this->change_id_order_state($this->id_order_state, $this->id_order);
        if ($sendemail) {
            //Mail::Send requires link object on context and is not set when getting here
            $context = Context::get_context();
            if ($context->link == null) {
                $protocol_link = Tools::using_secure_mode() && Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://';
                $protocol_content = Tools::using_secure_mode() && Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://';
                $context->link = new Link($protocol_link, $protocol_content);
            }
            return $this->add_withemail();
        }
        return $this->add();
    }
    /**
     * @param int|Order|OrderCore $identifier
     *
     * @throws PrestaShopException
     */
    protected function resolve_order($identifier): ?Order
    {
        if ($identifier instanceof Order) {
            return $identifier;
        }
        if (is_numeric($identifier)) {
            return new Order((int) $identifier);
        }
        if ($identifier instanceof Order_Core) {
            return new Order((int) $identifier->id);
        }
        return null;
    }
}