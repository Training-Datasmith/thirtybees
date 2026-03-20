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
 * Class MessageCore
 */
class Message_Core extends Object_Model
{
    /** @var string message content */
    public $message;
    /** @var int Cart ID (if applicable) */
    public $id_cart;
    /** @var int Order ID (if applicable) */
    public $id_order;
    /** @var int Customer ID (if applicable) */
    public $id_customer;
    /** @var int Employee ID (if applicable) */
    public $id_employee;
    /** @var bool Message is not displayed to the customer */
    public $private;
    /** @var string Object creation date */
    public $date_add;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'message', 'primary' => 'id_message', 'fields' => ['id_cart' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'], 'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false], 'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'], 'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false], 'message' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 1600], 'private' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '1'], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false]], 'keys' => ['message' => ['id_cart' => ['type' => Object_Model::KEY, 'columns' => ['id_cart']], 'id_customer' => ['type' => Object_Model::KEY, 'columns' => ['id_customer']], 'id_employee' => ['type' => Object_Model::KEY, 'columns' => ['id_employee']], 'message_order' => ['type' => Object_Model::KEY, 'columns' => ['id_order']]]]];
    /**
     * Return the last message from cart
     *
     * @param int $idCart Cart ID
     *
     * @return array|false Message
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_message_by_cart_id($id_cart)
    {
        return Db::read_only()->get_row((new Db_Query())->select('*')->from('message')->where('`id_cart` = ' . (int) $id_cart));
    }
    /**
     * Return messages from Order ID
     *
     * @param int $idOrder Order ID
     * @param bool $private return WITH private messages
     *
     * @return array Messages
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_messages_by_order_id($id_order, $private = false, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        return Db::read_only()->get_array((new Db_Query())->select('m.*')->select('c.`firstname` AS `cfirstname`, c.`lastname` AS `clastname`')->select('e.`firstname` AS `efirstname`, e.`lastname` AS `elastname`')->select('(COUNT(mr.`id_message`) = 0 AND m.`id_customer` != 0) AS `is_new_for_me`')->from('message', 'm')->left_join('customer', 'c', 'm.`id_customer` = c.`id_customer`')->left_join('message_readed', 'mr', 'mr.`id_message` = m.`id_message`')->left_outer_join('employee', 'e', 'e.`id_employee` = m.`id_employee` AND mr.`id_employee` = ' . (isset($context->employee) ? (int) $context->employee->id : '\'\''))->where('`id_order` = ' . (int) $id_order)->where($private ? 'm.`private` = 0' : '')->group_by('m.`id_message`')->order_by('m.`date_add` DESC'));
    }
    /**
     * Return messages from Cart ID
     *
     * @param int $idCart
     * @param bool $private return WITH private messages
     *
     * @return array Messages
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_messages_by_cart_id($id_cart, $private = false, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        return Db::read_only()->get_array((new Db_Query())->select('m.*')->select('c.`firstname` AS `cfirstname`, c.`lastname` AS `clastname`')->select('e.`firstname` AS `efirstname`, e.`lastname` AS `elastname`')->from('message', 'm')->left_join('customer', 'c', 'm.`id_customer` = c.`id_customer`')->left_join('message_readed', 'mr', 'mr.`id_message` = m.`id_message`')->left_outer_join('employee', 'e', 'e.`id_employee` = m.`id_employee`')->where('mr.`id_employee` = ' . (int) $context->employee->id)->where('`id_cart` = ' . (int) $id_cart)->where(!$private ? 'm.`private` = 0' : '')->group_by('m.`id_message`')->order_by('m.`date_add` DESC'));
    }
    /**
     * Registered a message 'readed'
     *
     * @param int $idMessage Message ID
     * @param int $idEmployee Employee ID
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function mark_as_readed($id_message, $id_employee)
    {
        if (!Validate::is_unsigned_id($id_message) || !Validate::is_unsigned_id($id_employee)) {
            return false;
        }
        return Db::get_instance()->insert('message_readed', ['id_message' => (int) $id_message, 'id_employee' => (int) $id_employee, 'date_add' => ['type' => 'sql', 'value' => 'NOW()']]);
    }
}