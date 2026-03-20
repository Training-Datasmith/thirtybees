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
 * Class OrderReturnCore
 */
class Order_Return_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'order_return', 'primary' => 'id_order_return', 'fields' => ['id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'state' => ['type' => self::TYPE_INT, 'dbType' => 'tinyint(1) unsigned', 'dbDefault' => '1'], 'question' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'size' => Object_Model::SIZE_TEXT, 'dbNullable' => false], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false]], 'keys' => ['order_return' => ['id_order' => ['type' => Object_Model::KEY, 'columns' => ['id_order']], 'order_return_customer' => ['type' => Object_Model::KEY, 'columns' => ['id_customer']]]]];
    /** @var int */
    public $id;
    /** @var int */
    public $id_customer;
    /** @var int */
    public $id_order;
    /** @var int */
    public $state;
    /** @var string message content */
    public $question;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /**
     * @param int $idOrder
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_returned_customized_products($id_order)
    {
        $id_order = (int) $id_order;
        $returns = Customization::get_returned_customizations($id_order);
        $order = new Order($id_order);
        if (!Validate::is_loaded_object($order)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Order %s not found'), $id_order));
        }
        $products = $order->get_products();
        foreach ($returns as &$return) {
            $return['product_id'] = (int) $products[(int) $return['id_order_detail']]['product_id'];
            $return['product_attribute_id'] = (int) $products[(int) $return['id_order_detail']]['product_attribute_id'];
            $return['name'] = $products[(int) $return['id_order_detail']]['product_name'];
            $return['reference'] = $products[(int) $return['id_order_detail']]['product_reference'];
            $return['id_address_delivery'] = $products[(int) $return['id_order_detail']]['id_address_delivery'];
        }
        return $returns;
    }
    /**
     * @param int $idOrderReturn
     * @param int $idOrderDetail
     * @param int $idCustomization
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function delete_order_return_detail($id_order_return, $id_order_detail, $id_customization = 0)
    {
        return Db::get_instance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'order_return_detail` WHERE `id_order_detail` = ' . (int) $id_order_detail . ' AND `id_order_return` = ' . (int) $id_order_return . ' AND `id_customization` = ' . (int) $id_customization);
    }
    /**
     * Get return details for one product line
     *
     * @param int $idOrderDetail
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_product_return_detail($id_order_detail)
    {
        return Db::read_only()->get_array((new Db_Query())->select('`product_quantity`, `date_add`, orsl.`name` AS `state`')->from('order_return_detail', 'ord')->left_join('order_return', 'o', 'o.`id_order_return` = ord.`id_order_return`')->left_join('order_return_state_lang', 'orsl', 'orsl.`id_order_return_state` = o.`state` AND orsl.`id_lang` = ' . (int) Context::get_context()->language->id)->where('ord.`id_order_detail` = ' . (int) $id_order_detail));
    }
    /**
     * Add returned quantity to products list
     *
     * @param array $products
     * @param int $idOrder
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function add_returned_quantity(&$products, $id_order): void
    {
        $details = Db::read_only()->get_array((new Db_Query())->select('od.`id_order_detail`, GREATEST(od.`product_quantity_return`, IFNULL(SUM(ord.`product_quantity`),0)) AS `qty_returned`')->from('order_detail', 'od')->left_join('order_return_detail', 'ord', 'ord.`id_order_detail` = od.`id_order_detail`')->where('od.`id_order` = ' . (int) $id_order)->group_by('od.`id_order_detail`'));
        if (!$details) {
            return;
        }
        $detail_list = [];
        foreach ($details as $detail) {
            $detail_list[$detail['id_order_detail']] = $detail;
        }
        foreach ($products as &$product) {
            if (isset($detail_list[$product['id_order_detail']]['qty_returned'])) {
                $product['qty_returned'] = $detail_list[$product['id_order_detail']]['qty_returned'];
            }
        }
    }
    /**
     * @param array $orderDetailList
     * @param array $productQtyList
     * @param array $customizationIds
     * @param array $customizationQtyInput
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_return_detail($order_detail_list, $product_qty_list, $customization_ids, $customization_qty_input): void
    {
        /* Classic product return */
        $conn = Db::get_instance();
        if ($order_detail_list) {
            foreach ($order_detail_list as $key => $order_detail) {
                if ($qty = (int) $product_qty_list[$key]) {
                    $conn->insert('order_return_detail', ['id_order_return' => (int) $this->id, 'id_order_detail' => (int) $order_detail, 'product_quantity' => $qty, 'id_customization' => 0]);
                }
            }
        }
        /* Customized product return */
        if ($customization_ids) {
            foreach ($customization_ids as $order_detail_id => $customizations) {
                foreach ($customizations as $customization_id) {
                    if ($quantity = (int) $customization_qty_input[(int) $customization_id]) {
                        $conn->insert('order_return_detail', ['id_order_return' => (int) $this->id, 'id_order_detail' => (int) $order_detail_id, 'product_quantity' => $quantity, 'id_customization' => (int) $customization_id]);
                    }
                }
            }
        }
    }
    /**
     * @param array $orderDetailList
     * @param array $productQtyList
     * @param array $customizationIds
     * @param array $customizationQtyInput
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function check_enough_product($order_detail_list, $product_qty_list, $customization_ids, $customization_qty_input)
    {
        $id_order = (int) $this->id_order;
        $order = new Order($id_order);
        if (!Validate::is_loaded_object($order)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Order %s not found'), $id_order));
        }
        $products = $order->get_products();
        /* Products already returned */
        $order_return = Order_Return::get_orders_return($order->id_customer, $order->id, true);
        foreach ($order_return as $or) {
            $order_return_products = Order_Return::get_orders_return_products($or['id_order_return'], $order);
            foreach ($order_return_products as $key => $orp) {
                $products[$key]['product_quantity'] -= (int) $orp['product_quantity'];
            }
        }
        /* Quantity check */
        if ($order_detail_list) {
            foreach (array_keys($order_detail_list) as $key) {
                if ($qty = (int) $product_qty_list[$key]) {
                    if ($products[$key]['product_quantity'] - $qty < 0) {
                        return false;
                    }
                }
            }
        }
        /* Customization quantity check */
        if ($customization_ids) {
            $ordered_customizations = Customization::get_ordered_customizations((int) $order->id_cart);
            foreach ($customization_ids as $customizations) {
                foreach ($customizations as $customization_id) {
                    $customization_id = (int) $customization_id;
                    if (!isset($ordered_customizations[$customization_id])) {
                        return false;
                    }
                    $quantity = isset($customization_qty_input[$customization_id]) ? (int) $customization_qty_input[$customization_id] : 0;
                    if ((int) $ordered_customizations[$customization_id]['quantity'] - $quantity < 0) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
    /**
     * @param int $customerId
     * @param int|bool $orderId
     * @param bool $noDenied
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_orders_return($customer_id, $order_id = false, $no_denied = false, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $data = Db::read_only()->get_array((new Db_Query())->select('*')->from('order_return')->where('`id_customer` = ' . (int) $customer_id)->where($order_id ? '`id_order` = ' . (int) $order_id : '')->where($no_denied ? '`state` != 4' : '')->order_by('`date_add` DESC'));
        foreach ($data as $k => $or) {
            $state = new Order_Return_State($or['state']);
            $data[$k]['state_name'] = $state->name[$context->language->id];
            $data[$k]['type'] = 'Return';
            $data[$k]['tracking_number'] = $or['id_order_return'];
            $data[$k]['can_edit'] = false;
            $data[$k]['reference'] = Order::get_uniq_reference_of($or['id_order']);
        }
        return $data;
    }
    /**
     * @param int $orderReturnId
     * @param Order $order
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_orders_return_products($order_return_id, $order)
    {
        $products_ret = Order_Return::get_orders_return_detail($order_return_id);
        $products = $order->get_products();
        $tmp = [];
        foreach ($products_ret as $return_detail) {
            $tmp[$return_detail['id_order_detail']]['quantity'] = isset($tmp[$return_detail['id_order_detail']]['quantity']) ? $tmp[$return_detail['id_order_detail']]['quantity'] + (int) $return_detail['product_quantity'] : (int) $return_detail['product_quantity'];
            $tmp[$return_detail['id_order_detail']]['customizations'] = (int) $return_detail['id_customization'];
        }
        $res_tab = [];
        foreach ($products as $key => $product) {
            if (isset($tmp[$product['id_order_detail']])) {
                $res_tab[$key] = $product;
                $res_tab[$key]['product_quantity'] = $tmp[$product['id_order_detail']]['quantity'];
                $res_tab[$key]['customizations'] = $tmp[$product['id_order_detail']]['customizations'];
            }
        }
        return $res_tab;
    }
    /**
     * @param int $idOrderReturn
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_orders_return_detail($id_order_return)
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('order_return_detail')->where('`id_order_return` = ' . (int) $id_order_return));
    }
    /**
     * @return bool|int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function count_product()
    {
        if (!$data = Db::read_only()->get_row((new Db_Query())->select('COUNT(`id_order_return`) AS `total`')->from('order_return_detail')->where('`id_order_return` = ' . (int) $this->id))) {
            return false;
        }
        return (int) $data['total'];
    }
}