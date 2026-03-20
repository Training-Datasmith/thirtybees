<?php

declare (strict_types=1);
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
namespace Thirtybees\Core\Work_Queue;

use Context;
use Customer;
use Employee;
use Language;
use Presta_Shop_Exception;
use Shop;
/**
 * Class WorkQueueTaskCore
 */
class Work_Queue_Context_Core
{
    /**
     * @var int | null
     */
    protected $shop_id;
    /**
     * @var Shop
     */
    protected $shop;
    /**
     * @var int | null
     */
    protected $employee_id;
    /**
     * @var Employee
     */
    protected $employee;
    /**
     * @var int | null
     */
    protected $customer_id;
    /**
     * @var Customer
     */
    protected $customer;
    /**
     * @var int | null
     */
    protected $language_id;
    /**
     * @var Language
     */
    protected $language;
    /**
     * WorkQueueContextCore constructor.
     * @param int $shopId
     * @param int $employeeId
     * @param int $customerId
     * @param int $languageId
     */
    public function __construct($shop_id, $employee_id, $customer_id, $language_id)
    {
        $this->shop_id = static::id_or_null($shop_id);
        $this->employee_id = static::id_or_null($employee_id);
        $this->customer_id = static::id_or_null($customer_id);
        $this->language_id = static::id_or_null($language_id);
    }
    /**
     * Creates workqueue context from shop context
     */
    public static function from_context(Context $context): static
    {
        $shop = $context->shop;
        $employee = $context->employee;
        $customer = $context->customer;
        $language = $context->language;
        $work_queue_context = new static(is_null($shop) ? 0 : $shop->id, is_null($employee) ? 0 : $employee->id, is_null($customer) ? 0 : $customer->id, is_null($language) ? 0 : $language->id);
        $work_queue_context->shop = $shop;
        $work_queue_context->employee = $employee;
        $work_queue_context->customer = $customer;
        $work_queue_context->language = $language;
        return $work_queue_context;
    }
    /**
     * @return int
     */
    public function get_shop_id()
    {
        return $this->shop_id;
    }
    /**
     * @return int
     */
    public function get_employee_id()
    {
        return $this->employee_id;
    }
    /**
     * @return int
     */
    public function get_customer_id()
    {
        return $this->customer_id;
    }
    /**
     * @return int
     */
    public function get_language_id()
    {
        return $this->language_id;
    }
    /**
     * @return Shop
     * @throws PrestaShopException
     */
    public function get_shop()
    {
        if ($this->shop_id && is_null($this->shop)) {
            $this->shop = new Shop($this->shop_id, $this->language_id);
        }
        return $this->shop;
    }
    /**
     * @return Employee
     * @throws PrestaShopException
     */
    public function get_employee()
    {
        if ($this->employee_id && is_null($this->employee)) {
            $this->shop = new Employee($this->employee_id);
        }
        return $this->employee;
    }
    /**
     * @return Customer
     * @throws PrestaShopException
     */
    public function get_customer()
    {
        if ($this->customer_id && is_null($this->customer)) {
            $this->customer = new Customer($this->customer_id);
        }
        return $this->customer;
    }
    /**
     * @return Language
     * @throws PrestaShopException
     */
    public function get_language()
    {
        if ($this->language_id && is_null($this->language)) {
            $this->language = new Language($this->language_id);
        }
        return $this->language;
    }
    /**
     * If input is positive integer (valid ID), then return it, otherwise returns null
     *
     * @param mixed $input
     */
    protected static function id_or_null($input): ?int
    {
        $value = (int) $input;
        if ($value) {
            return $value;
        }
        return null;
    }
}