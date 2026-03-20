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
 * Class OrderSlipCore
 */
class Order_Slip_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'order_slip', 'primary' => 'id_order_slip', 'fields' => ['conversion_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true, 'size' => 13, 'decimals' => 6, 'dbDefault' => '1.000000'], 'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'total_products_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbNullable' => true], 'total_products_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbNullable' => true], 'total_shipping_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbNullable' => true], 'total_shipping_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbNullable' => true], 'shipping_cost' => ['type' => self::TYPE_INT, 'dbType' => 'tinyint(3) unsigned', 'dbDefault' => '0'], 'amount' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbNullable' => false], 'shipping_cost_amount' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbNullable' => false], 'partial' => ['type' => self::TYPE_INT, 'dbType' => 'tinyint(1)', 'dbNullable' => false], 'order_slip_type' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'size' => 1, 'dbDefault' => '0'], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false]], 'keys' => ['order_slip' => ['id_order' => ['type' => Object_Model::KEY, 'columns' => ['id_order']], 'order_slip_customer' => ['type' => Object_Model::KEY, 'columns' => ['id_customer']]]]];
    /** @var int */
    public $id;
    /** @var int */
    public $id_customer;
    /** @var int */
    public $id_order;
    /** @var float */
    public $conversion_rate;
    /** @var float */
    public $total_products_tax_excl;
    /** @var float */
    public $total_products_tax_incl;
    /** @var float */
    public $total_shipping_tax_excl;
    /** @var float */
    public $total_shipping_tax_incl;
    /** @var float */
    public $amount;
    /** @var int */
    public $shipping_cost;
    /** @var float */
    public $shipping_cost_amount;
    /** @var int */
    public $partial;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var int */
    public $order_slip_type = 0;
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectNodeName' => 'order_slip', 'objectsNodeName' => 'order_slips', 'fields' => ['id_customer' => ['xlink_resource' => 'customers'], 'id_order' => ['xlink_resource' => 'orders']], 'associations' => ['order_slip_details' => ['resource' => 'order_slip_detail', 'setter' => false, 'virtual_entity' => true, 'fields' => ['id' => [], 'id_order_detail' => ['required' => true], 'product_quantity' => ['required' => true], 'amount_tax_excl' => ['required' => true], 'amount_tax_incl' => ['required' => true]]]]];
    /**
     * @param int $customerId
     * @param bool $orderId
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_orders_slip($customer_id, $order_id = false)
    {
        return Db::read_only()->get_array((new Db_Query())->select(' *')->from('order_slip')->where('`id_customer` = ' . (int) $customer_id)->where($order_id ? '`id_order` = ' . (int) $order_id : '')->order_by('`date_add` DESC'));
    }
    /**
     * @param int $orderSlipId
     * @param Order $order
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_orders_slip_products($order_slip_id, $order)
    {
        $products_ret = static::get_orders_slip_detail($order_slip_id);
        $order_details = $order->get_products_detail();
        $slip_quantity = [];
        foreach ($products_ret as $slip_detail) {
            $slip_quantity[$slip_detail['id_order_detail']] = $slip_detail;
        }
        $products = [];
        foreach ($order_details as $key => $product) {
            if (isset($slip_quantity[$product['id_order_detail']]) && $slip_quantity[$product['id_order_detail']]['product_quantity']) {
                $products[$key] = $product;
                $products[$key] = array_merge($products[$key], $slip_quantity[$product['id_order_detail']]);
            }
        }
        return $order->get_products($products);
    }
    /**
     * @param bool $idOrderSlip
     * @param bool $idOrderDetail
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_orders_slip_detail($id_order_slip = false, $id_order_detail = false)
    {
        return Db::read_only()->get_array((new Db_Query())->select($id_order_detail ? 'SUM(`product_quantity`) AS `total`' : '*')->from('order_slip_detail')->where($id_order_slip ? '`id_order_slip` = ' . (int) $id_order_slip : '')->where($id_order_detail ? '`id_order_detail` = ' . (int) $id_order_detail : ''));
    }
    /**
     * Get refund details for one product line
     *
     * @param int $idOrderDetail
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_product_slip_detail($id_order_detail)
    {
        return Db::read_only()->get_array((new Db_Query())->select('`product_quantity`, `amount_tax_excl`, `amount_tax_incl`, `date_add`')->from('order_slip_detail', 'osd')->left_join('order_slip', 'os', 'os.`id_order_slip` = osd.`id_order_slip`')->where('osd.`id_order_detail` = ' . (int) $id_order_detail));
    }
    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_slips_id_by_date($date_from, $date_to)
    {
        $result = Db::read_only()->get_array((new Db_Query())->select('`id_order_slip`')->from('order_slip', 'os')->left_join('orders', 'o', 'o.`id_order` = os.`id_order`')->where('os.`date_add` BETWEEN \'' . p_sql($date_from) . ' 00:00:00\' AND \'' . p_sql($date_to) . ' 23:59:59\' ' . Shop::add_sql_restriction(Shop::SHARE_ORDER, 'o'))->order_by('os.`date_add` ASC'));
        $slips = [];
        foreach ($result as $slip) {
            $slips[] = (int) $slip['id_order_slip'];
        }
        return $slips;
    }
    /**
     * @deprecated 1.0.0 use OrderSlip::create() instead
     *
     * @param Order $order
     * @param int[] $selectedOrderLines list of order details line IDs selected for refund
     * @param int[] $qtyList refund quantities associative array
     * @param float|bool $shippingCost Shipping costs to be refunded. Explicit shipping costs amount can be passed,
     *                                 or boolean value to indicate if total shipping costs should be refunded
     *
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function create_order_slip($order, $selected_order_lines, $qty_list, $shipping_cost = false)
    {
        Tools::display_as_deprecated();
        $new_product_list = [];
        foreach ($selected_order_lines as $id_order_detail) {
            $order_detail = new Order_Detail((int) $id_order_detail);
            $new_product_list[$id_order_detail] = ['id_order_detail' => $id_order_detail, 'quantity' => $qty_list[$id_order_detail], 'unit_price' => $order_detail->unit_price_tax_excl, 'amount' => $order_detail->unit_price_tax_incl * $qty_list[$id_order_detail]];
        }
        return static::create($order, $new_product_list, $shipping_cost);
    }
    /**
     * @param Order $order The order this refunding is related to.
     * @param array $productList List of arrays with product descriptions.
     * @param float|bool $shippingCost Shipping costs to be refunded. Explicit shipping costs amount can be passed,
     *                                 or boolean value to indicate if total shipping costs should be refunded
     * @param int $amount
     * @param bool $amountChoosen
     * @param bool $addTax True if prices are without tax, else false.
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function create(Order $order, $product_list, $shipping_cost = false, $amount = 0, $amount_choosen = false, $add_tax = true)
    {
        $currency = new Currency((int) $order->id_currency);
        $order_slip = new Order_Slip();
        $order_slip->id_customer = (int) $order->id_customer;
        $order_slip->id_order = (int) $order->id;
        $order_slip->conversion_rate = $currency->conversion_rate;
        $order_slip->total_shipping_tax_excl = 0;
        $order_slip->total_shipping_tax_incl = 0;
        // TODO: deprecate this, nowhere in use.
        $order_slip->partial = 0;
        $shipping_cost = static::resolve_shipping_cost($shipping_cost, $order, $add_tax);
        if ($shipping_cost > 0.0) {
            $order_slip->shipping_cost = true;
            $order_slip->shipping_cost_amount = $shipping_cost;
            // Use taxes from the given order.
            $tax = new Tax();
            $tax->rate = $order->carrier_tax_rate;
            $tax_calculator = new Tax_Calculator([$tax]);
            if ($add_tax == true) {
                $order_slip->total_shipping_tax_excl = $shipping_cost;
                $order_slip->total_shipping_tax_incl = $tax_calculator->add_taxes($shipping_cost);
            } else {
                $order_slip->total_shipping_tax_incl = $shipping_cost;
                $order_slip->total_shipping_tax_excl = $tax_calculator->remove_taxes($shipping_cost);
            }
        } else {
            $order_slip->shipping_cost = false;
            $order_slip->shipping_cost_amount = 0;
        }
        $order_slip->total_products_tax_excl = 0;
        $order_slip->total_products_tax_incl = 0;
        foreach ($product_list as &$product) {
            $order_detail = new Order_Detail((int) $product['id_order_detail']);
            $quantity = (int) $product['quantity'];
            $order_slip_resume = static::get_product_slip_resume((int) $order_detail->id);
            if ($quantity + $order_slip_resume['product_quantity'] > $order_detail->product_quantity) {
                $quantity = (int) ($order_detail->product_quantity - $order_slip_resume['product_quantity']);
            }
            if (!Tools::is_submit('cancelProduct') && $quantity !== 0) {
                $order_detail->product_quantity_refunded += $quantity;
                $order_detail->save();
            }
            // Use taxes from the given order detail.
            $tax = new Tax();
            $tax->rate = $order_detail->tax_rate;
            $tax_calculator = new Tax_Calculator([$tax]);
            // In case of a distinction between product value in the order and
            // product value in the refund (choosen by the merchant on refund
            // creation), these prices are reduced already.
            $unit_price = (float) $product['unit_price'];
            if ($add_tax == true) {
                $product['unit_price_tax_excl'] = Tools::round_price($unit_price);
                $product['unit_price_tax_incl'] = $tax_calculator->add_taxes($unit_price);
            } else {
                $product['unit_price_tax_incl'] = Tools::round_price($unit_price);
                $product['unit_price_tax_excl'] = $tax_calculator->remove_taxes($unit_price);
            }
            $product['total_price_tax_excl'] = Tools::round_price($product['unit_price_tax_excl'] * $quantity);
            $product['total_price_tax_incl'] = Tools::round_price($product['unit_price_tax_incl'] * $quantity);
            $order_slip->total_products_tax_excl += $product['total_price_tax_excl'];
            $order_slip->total_products_tax_incl += $product['total_price_tax_incl'];
        }
        unset($product);
        if ($add_tax == true) {
            $order_slip->amount = $order_slip->total_products_tax_excl;
        } else {
            $order_slip->amount = $order_slip->total_products_tax_incl;
        }
        if ((float) $amount && !$amount_choosen) {
            $order_slip->order_slip_type = 1;
        }
        if ((float) $amount && $amount_choosen || $order_slip->shipping_cost_amount > 0) {
            $order_slip->order_slip_type = 2;
        }
        if (!$order_slip->add()) {
            return false;
        }
        $res = true;
        foreach ($product_list as $product) {
            $res = $order_slip->add_product_order_slip($product) && $res;
        }
        return $res;
    }
    /**
     * Get resume of all refund for one product line
     *
     * @param int $idOrderDetail
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function get_product_slip_resume($id_order_detail)
    {
        return Db::read_only()->get_row((new Db_Query())->select('COALESCE(SUM(`product_quantity`), 0) AS `product_quantity`')->select('COALESCE(SUM(`amount_tax_excl`), 0) AS `amount_tax_excl`')->select('COALESCE(SUM(`amount_tax_incl`), 0) AS `amount_tax_incl`')->from('order_slip_detail')->where('`id_order_detail` = ' . (int) $id_order_detail));
    }
    /**
     * @param Order $order
     * @param float $amount
     * @param float $shippingCostAmount
     * @param array $orderDetailList
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function create_partial_order_slip($order, $amount, $shipping_cost_amount, $order_detail_list)
    {
        Tools::display_as_deprecated();
        $currency = new Currency($order->id_currency);
        $order_slip = new Order_Slip();
        $order_slip->id_customer = (int) $order->id_customer;
        $order_slip->id_order = (int) $order->id;
        $order_slip->amount = Tools::round_price((float) $amount);
        $order_slip->shipping_cost = false;
        $order_slip->shipping_cost_amount = Tools::round_price((float) $shipping_cost_amount);
        $order_slip->conversion_rate = $currency->conversion_rate;
        $order_slip->partial = 1;
        if (!$order_slip->add()) {
            return false;
        }
        $order_slip->add_partial_slip_detail($order_detail_list);
        return true;
    }
    /**
     * @param array $orderDetailList
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_partial_slip_detail($order_detail_list): void
    {
        Tools::display_as_deprecated();
        foreach ($order_detail_list as $id_order_detail => $tab) {
            $order_detail = new Order_Detail($id_order_detail);
            $order_slip_resume = static::get_product_slip_resume($id_order_detail);
            if ($tab['amount'] + $order_slip_resume['amount_tax_incl'] > $order_detail->total_price_tax_incl) {
                $tab['amount'] = $order_detail->total_price_tax_incl - $order_slip_resume['amount_tax_incl'];
            }
            if ($tab['amount'] == 0) {
                continue;
            }
            if ($tab['quantity'] + $order_slip_resume['product_quantity'] > $order_detail->product_quantity) {
                $tab['quantity'] = $order_detail->product_quantity - $order_slip_resume['product_quantity'];
            }
            $tab['amount_tax_excl'] = $tab['amount_tax_incl'] = $tab['amount'];
            $connection = Db::read_only();
            $id_tax = (int) $connection->get_value((new Db_Query())->select('`id_tax`')->from('order_detail_tax')->where('`id_order_detail` = ' . (int) $id_order_detail));
            if ($id_tax > 0) {
                $rate = (float) $connection->get_value((new Db_Query())->select('`rate`')->from('tax')->where('`id_tax` = ' . $id_tax));
                if ($rate > 0) {
                    $rate = 1 + $rate / 100;
                    $tab['amount_tax_excl'] = Tools::round_price($tab['amount_tax_excl'] / $rate);
                }
            }
            if ($tab['quantity'] > 0 && $tab['quantity'] > $order_detail->product_quantity_refunded) {
                $order_detail->product_quantity_refunded = $tab['quantity'];
                $order_detail->save();
            }
            $insert_order_slip = ['id_order_slip' => (int) $this->id, 'id_order_detail' => (int) $id_order_detail, 'product_quantity' => (int) $tab['quantity'], 'amount_tax_excl' => (float) $tab['amount_tax_excl'], 'amount_tax_incl' => (float) $tab['amount_tax_incl']];
            Db::get_instance()->insert('order_slip_detail', $insert_order_slip);
        }
    }
    /**
     * @param array $orderDetailList
     * @param array $productQtyList
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_slip_detail($order_detail_list, $product_qty_list): void
    {
        Tools::display_as_deprecated();
        foreach ($order_detail_list as $key => $id_order_detail) {
            if ($qty = (int) $product_qty_list[$key]) {
                $order_detail = new Order_Detail((int) $id_order_detail);
                if (Validate::is_loaded_object($order_detail)) {
                    Db::get_instance()->insert('order_slip_detail', ['id_order_slip' => (int) $this->id, 'id_order_detail' => (int) $id_order_detail, 'product_quantity' => $qty, 'amount_tax_excl' => Tools::round_price($order_detail->unit_price_tax_excl * $qty), 'amount_tax_incl' => Tools::round_price($order_detail->unit_price_tax_incl * $qty)]);
                }
            }
        }
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_products()
    {
        $result = Db::read_only()->get_array((new Db_Query())->select('*')->from('order_slip_detail', 'osd')->inner_join('order_detail', 'od', 'osd.`id_order_detail` = od.`id_order_detail`')->where('osd.`id_order_slip` = ' . (int) $this->id));
        $order = new Order($this->id_order);
        $products = [];
        foreach ($result as $row) {
            $order->set_product_prices($row);
            $products[] = $row;
        }
        return $products;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_eco_tax_taxes_breakdown()
    {
        $ecotax_detail = [];
        foreach (static::get_orders_slip_detail((int) $this->id) as $order_slip_details) {
            $row = Db::read_only()->get_row((new Db_Query())->select('`ecotax_tax_rate` AS `rate`, `ecotax` AS `ecotax_tax_excl`, `ecotax` AS `ecotax_tax_incl`, `product_quantity`')->from('order_detail')->where('`id_order_detail` = ' . (int) $order_slip_details['id_order_detail']));
            if (!isset($ecotax_detail[$row['rate']])) {
                $ecotax_detail[$row['rate']] = ['ecotax_tax_incl' => 0, 'ecotax_tax_excl' => 0, 'rate' => $row['rate']];
            }
            $quantity = (int) $order_slip_details['product_quantity'];
            $ecotax_detail[$row['rate']]['ecotax_tax_incl'] += Tools::round_price($row['ecotax_tax_excl'] * $quantity * (1 + $row['rate'] / 100));
            $ecotax_detail[$row['rate']]['ecotax_tax_excl'] += Tools::round_price($row['ecotax_tax_excl'] * $quantity);
        }
        return $ecotax_detail;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_order_slip_details()
    {
        Tools::display_as_deprecated();
        return Db::read_only()->get_array((new Db_Query())->select('`id_order_slip` AS `id`, `id_order_detail`, `product_quantity`, `amount_tax_excl`, `amount_tax_incl`')->from('order_slip_detail')->where('`id_order_slip` = ' . (int) $this->id));
    }
    /**
     * @param array $values
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_ws_order_slip_details($values)
    {
        Tools::display_as_deprecated();
        $conn = Db::get_instance();
        if ($conn->delete('order_slip_detail', '`id_order_slip` = ' . (int) $this->id)) {
            $insert = [];
            foreach ($values as $value) {
                $insert[] = ['id_order_slip' => (int) $this->id, 'id_order_detail' => (int) $value['id_order_detail'], 'product_quantity' => (int) $value['product_quantity'], 'amount_tax_excl' => ['type' => 'sql', 'value' => isset($value['amount_tax_excl']) ? (float) $value['amount_tax_excl'] : 'NULL'], 'amount_tax_incl' => ['type' => 'sql', 'value' => isset($value['amount_tax_incl']) ? (float) $value['amount_tax_incl'] : 'NULL']];
            }
            $conn->insert('order_slip_detail', $insert);
        }
        return true;
    }
    /**
     * @param array $product
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function add_product_order_slip($product)
    {
        return Db::get_instance()->insert('order_slip_detail', ['id_order_slip' => (int) $this->id, 'id_order_detail' => (int) $product['id_order_detail'], 'product_quantity' => (int) $product['quantity'], 'unit_price_tax_excl' => Tools::round_price((float) $product['unit_price_tax_excl']), 'unit_price_tax_incl' => Tools::round_price((float) $product['unit_price_tax_incl']), 'total_price_tax_excl' => Tools::round_price((float) $product['total_price_tax_excl']), 'total_price_tax_incl' => Tools::round_price((float) $product['total_price_tax_incl']), 'amount_tax_excl' => Tools::round_price((float) $product['total_price_tax_excl']), 'amount_tax_incl' => Tools::round_price((float) $product['total_price_tax_incl'])]);
    }
    /**
     * Returns shipping costs for refund
     *
     * The shipping cost can either be explicitly specified by $shippingCost parameter. Alternatively, boolean
     * value can be provided to specify if total order shipping cost should be returned
     *
     * @param float|bool $shippingCost Explicit value, or boolean to indicate if order total shipping cost should be used
     * @param Order $order associated order
     * @param bool $withoutTax
     *
     * @return float
     */
    protected static function resolve_shipping_cost($shipping_cost, Order $order, $without_tax)
    {
        if ($shipping_cost === false) {
            return 0.0;
        }
        if (!is_numeric($shipping_cost)) {
            if ($without_tax) {
                $shipping_cost = $order->total_shipping_tax_excl;
            } else {
                $shipping_cost = $order->total_shipping_tax_incl;
            }
        }
        return Tools::round_price((float) $shipping_cost);
    }
}