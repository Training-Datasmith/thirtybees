<?php

declare (strict_types=1);
namespace Thirtybees\Core\View\Model;

use Address;
use Carrier;
use Currency;
use Order;
use State;
class Guest_Tracking_Order_Model_Core extends Order
{
    /**
     * @var int
     */
    public $id_order_state;
    /**
     * @var bool
     */
    public $invoice;
    /**
     * @var array[]
     */
    public $order_history;
    /**
     * @var Carrier
     */
    public $carrier;
    /**
     * @var Address
     */
    public $address_invoice;
    /**
     * @var Address
     */
    public $address_delivery;
    /**
     * @var array
     */
    public $inv_adr_fields;
    /**
     * @var array
     */
    public $dlv_adr_fields;
    /**
     * @var array
     */
    public $invoice_address_formated_values;
    /**
     * @var array
     */
    public $delivery_address_formated_values;
    /**
     * @var Currency
     */
    public $currency;
    /**
     * @var array
     */
    public $discounts;
    /**
     * @var State|false
     */
    public $invoice_state;
    /**
     * @var State|false
     */
    public $delivery_state;
    /**
     * @var array
     */
    public $products;
    /**
     * @var array|false
     */
    public $customized_datas;
    /**
     * @var false|float
     */
    public $total_old;
    /**
     * @var string|null
     */
    public $followup;
    /**
     * @var string
     */
    public $hook_orderdetaildisplayed;
}