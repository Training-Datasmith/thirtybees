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
 * Class WebserviceExceptionCore
 */
class Webservice_Exception_Core extends Exception
{
    /**
     * @var int
     */
    protected $status;
    /**
     * @var string
     */
    protected $wrong_value;
    /**
     * @var array
     */
    protected $available_values;
    /**
     * @var int
     */
    protected $type;
    public const SIMPLE = 0;
    public const DID_YOU_MEAN = 1;
    /**
     * WebserviceExceptionCore constructor.
     *
     * @param string $message
     * @param int|int[] $code
     */
    public function __construct($message, $code)
    {
        $exception_code = $code;
        if (is_array($code)) {
            $exception_code = $code[0];
            $this->set_status($code[1]);
        }
        parent::__construct($message, $exception_code);
        $this->type = static::SIMPLE;
    }
    /**
     * @return int
     */
    public function get_type()
    {
        return $this->type;
    }
    /**
     * @param int $type
     */
    public function set_type($type): static
    {
        $this->type = $type;
        return $this;
    }
    /**
     * @param int $status
     */
    public function set_status($status): static
    {
        if (Validate::is_int($status)) {
            $this->status = $status;
        }
        return $this;
    }
    /**
     * @return int
     */
    public function get_status()
    {
        return $this->status;
    }
    /**
     * @return string
     */
    public function get_wrong_value()
    {
        return $this->wrong_value;
    }
    /**
     * @param string $wrongValue
     * @param array $availableValues
     */
    public function set_did_you_mean($wrong_value, $available_values): static
    {
        $this->type = static::DID_YOU_MEAN;
        $this->wrong_value = $wrong_value;
        $this->available_values = $available_values;
        return $this;
    }
    /**
     * @return array
     */
    public function get_available_values()
    {
        return $this->available_values;
    }
}