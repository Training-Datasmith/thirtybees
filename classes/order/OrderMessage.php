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
 * Class OrderMessageCore
 */
class Order_Message_Core extends Object_Model
{
    /** @var string|string[] name name */
    public $name;
    /** @var string|string[] message content */
    public $message;
    /** @var string Object creation date */
    public $date_add;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'order_message', 'primary' => 'id_order_message', 'multilang' => true, 'fields' => ['date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128], 'message' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isMessage', 'required' => true, 'size' => 1200]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['fields' => ['id' => ['sqlId' => 'id_discount_type', 'xlink_resource' => 'order_message_lang'], 'date_add' => ['sqlId' => 'date_add']]];
    /**
     * @param int $idLang
     * @param Order|null $order
     * @param Customer|null $customer
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_order_messages($id_lang, $order = null, $customer = null)
    {
        $id_lang = (int) $id_lang;
        $order_messages = Db::read_only()->get_array('
		SELECT om.id_order_message, oml.name, oml.message
		FROM ' . _DB_PREFIX_ . 'order_message om
		LEFT JOIN ' . _DB_PREFIX_ . 'order_message_lang oml ON (oml.id_order_message = om.id_order_message)
		WHERE oml.id_lang = ' . $id_lang . '
		ORDER BY name ASC');
        // Replace Shortcodes
        if ($order_messages) {
            $customer = static::resolve_customer($customer, $order);
            return static::replace_shortcodes_in_order_messages($order_messages, $id_lang, $order, $customer);
        }
        return [];
    }
    /**
     *
     * @throws PrestaShopException
     */
    protected static function resolve_customer(?Customer $customer, ?Order $order): ?Customer
    {
        if (Validate::is_loaded_object($customer)) {
            return $customer;
        }
        if ($order) {
            $customer = new Customer((int) $order->id_customer);
            if (Validate::is_loaded_object($customer)) {
                return $customer;
            }
        }
        return null;
    }
    /**
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    protected static function replace_shortcodes_in_order_messages(array $order_messages, int $id_lang, ?Order $order, ?Customer $customer, bool $return_shortcode_list = false)
    {
        $gender_name = '[customer_gender]';
        $customer_first_name = '[customer_firstname]';
        $customer_last_name = '[customer_lastname]';
        $order_reference = '[order_reference]';
        $order_address_delivery = '[order_address_delivery]';
        $order_address_invoice = '[order_address_invoice]';
        $order_total_paid_tax_incl = '[order_total_paid_tax_incl]';
        $order_total_paid_tax_excl = '[order_total_paid_tax_excl]';
        $order_data = '[order_date]';
        $order_delivery_date = '[order_delivery_date]';
        if (Validate::is_loaded_object($customer)) {
            $gender = new Gender($customer->id_gender, $id_lang, $order->id_shop ?? null);
            $gender_name = $gender->name;
            $customer_first_name = $customer->firstname;
            $customer_last_name = $customer->lastname;
        }
        if (Validate::is_loaded_object($order)) {
            $order_reference = $order->reference;
            $address_delivery = new Address($order->id_address_delivery, $id_lang);
            $address_invoice = new Address($order->id_address_invoice, $id_lang);
            $order_address_delivery = Address_Format::generate_address($address_delivery);
            $order_address_invoice = Address_Format::generate_address($address_invoice);
            $order_total_paid_tax_incl = Tools::display_price($order->total_paid_tax_incl, $order->id_currency);
            $order_total_paid_tax_excl = Tools::display_price($order->total_paid_tax_excl, $order->id_currency);
            $order_data = Tools::display_date($order->date_add);
            $order_delivery_date = Tools::display_date($order->delivery_date);
        }
        $context = Context::get_context();
        $shortcodes_list = ['[customer_firstname]' => $customer_first_name, '[customer_lastname]' => $customer_last_name, '[customer_gender]' => $gender_name, '[order_reference]' => $order_reference, '[order_date]' => $order_data, '[order_delivery_date]' => $order_delivery_date, '[order_total_paid_tax_incl]' => $order_total_paid_tax_incl, '[order_total_paid_tax_excl]' => $order_total_paid_tax_excl, '[order_address_delivery]' => $order_address_delivery, '[order_address_invoice]' => $order_address_invoice, '[employee_firstname]' => $context->employee->firstname, '[employee_lastname]' => $context->employee->lastname];
        if ($return_shortcode_list) {
            return array_keys($shortcodes_list);
        }
        foreach ($order_messages as &$order_message) {
            $order_message['message'] = str_replace(array_keys($shortcodes_list), array_values($shortcodes_list), $order_message['message']);
        }
        return $order_messages;
    }
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function get_shortcode_list()
    {
        return static::replace_shortcodes_in_order_messages([], (int) Configuration::get('PS_LANG_DEFAULT'), null, null, true);
    }
}