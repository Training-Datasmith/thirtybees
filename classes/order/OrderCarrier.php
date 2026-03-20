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
 * Class OrderCarrierCore
 */
class Order_Carrier_Core extends Object_Model
{
    /** @var int */
    public $id_order_carrier;
    /** @var int */
    public $id_order;
    /** @var int */
    public $id_carrier;
    /** @var int */
    public $id_order_invoice;
    /** @var float */
    public $weight;
    /** @var float */
    public $shipping_cost_tax_excl;
    /** @var float */
    public $shipping_cost_tax_incl;
    /** @var string */
    public $tracking_number;
    /** @var string Object creation date */
    public $date_add;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'order_carrier', 'primary' => 'id_order_carrier', 'primaryKeyDbType' => 'int(11)', 'fields' => ['id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_carrier' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_order_invoice' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'], 'weight' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat'], 'shipping_cost_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'], 'shipping_cost_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice'], 'tracking_number' => ['type' => self::TYPE_STRING, 'validate' => 'isTrackingNumber', 'size' => 64], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false]], 'keys' => ['order_carrier' => ['id_carrier' => ['type' => Object_Model::KEY, 'columns' => ['id_carrier']], 'id_order' => ['type' => Object_Model::KEY, 'columns' => ['id_order']], 'id_order_invoice' => ['type' => Object_Model::KEY, 'columns' => ['id_order_invoice']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['fields' => ['id_order' => ['xlink_resource' => 'orders'], 'id_carrier' => ['xlink_resource' => 'carriers']]];
    /**
     * @param string $trackingNumber
     * @param bool $sendMail
     * @param string[] $errors
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function update_tracking_number($tracking_number, $send_mail = true, &$errors = [])
    {
        if (!Validate::is_tracking_number($tracking_number)) {
            $errors[] = Tools::display_error('The tracking number is incorrect.');
            return false;
        }
        // update shipping number
        // Keep these two following lines for backward compatibility
        $order_object = new Order($this->id_order);
        if ($order_object->id) {
            $order_object->shipping_number = $tracking_number;
            $order_object->update();
        }
        // Update order_carrier
        $this->tracking_number = $tracking_number;
        if ($this->update()) {
            // Send mail to customer
            $customer = new Customer((int) $order_object->id_customer);
            if (!Validate::is_loaded_object($customer)) {
                throw new Presta_Shop_Exception('Can\'t load Customer object');
            }
            $carrier = new Carrier((int) $this->id_carrier, $order_object->id_lang);
            if (!Validate::is_loaded_object($carrier)) {
                throw new Presta_Shop_Exception('Can\'t load Carrier object');
            }
            $send_mail_result = false;
            if ($send_mail) {
                $followup_url = $followup = str_replace('@', $this->tracking_number, $carrier->url);
                if (empty($followup)) {
                    $followup = $this->tracking_number;
                }
                if (empty($followup_url)) {
                    $followup_url = '#';
                }
                $template_vars = ['{followup}' => $followup, '{followup_url}' => $followup_url, '{firstname}' => $customer->firstname, '{lastname}' => $customer->lastname, '{id_order}' => $order_object->id, '{tracking_number}' => $this->tracking_number, '{carrier_name}' => $carrier->display_name, '{order_name}' => $order_object->get_uniq_reference(), '{bankwire_owner}' => (string) Configuration::get('BANK_WIRE_OWNER'), '{bankwire_details}' => nl2br((string) Configuration::get('BANK_WIRE_DETAILS')), '{bankwire_address}' => nl2br((string) Configuration::get('BANK_WIRE_ADDRESS'))];
                $send_mail_result = Mail::Send((int) $order_object->id_lang, 'in_transit', Mail::l('Package in transit', (int) $order_object->id_lang), $template_vars, $customer->email, $customer->firstname . ' ' . $customer->lastname, null, null, null, null, _PS_MAIL_DIR_, false, (int) $order_object->id_shop);
                if (!$send_mail_result) {
                    $errors[] = Tools::display_error('An error occurred while sending an email to the customer.');
                }
            }
            Hook::trigger_event('actionOrderCarrierTrackingNumberUpdate', ['orderObject' => $order_object, 'customer' => $customer, 'carrier' => $carrier, 'sendMail' => $send_mail, 'sendMailResult' => $send_mail_result], $order_object->id_shop);
            return true;
        }
        $errors[] = Tools::display_error('The order carrier cannot be updated.');
        return false;
    }
}