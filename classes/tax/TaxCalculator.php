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
 * Class TaxCalculatorCore
 */
class Tax_Calculator_Core
{
    /**
     * COMBINE_METHOD sum taxes
     * eg: 100€ * (10% + 15%)
     */
    public const COMBINE_METHOD = 1;
    /**
     * ONE_AFTER_ANOTHER_METHOD apply taxes one after another
     * eg: (100€ * 10%) * 15%
     */
    public const ONE_AFTER_ANOTHER_METHOD = 2;
    /**
     * @var Tax[] $taxes
     */
    public $taxes;
    /**
     * @var int $computation_method (COMBINE_METHOD | ONE_AFTER_ANOTHER_METHOD)
     */
    public $computation_method;
    /**
     * @param Tax[] $taxes
     * @param int $computationMethod (COMBINE_METHOD | ONE_AFTER_ANOTHER_METHOD)
     */
    public function __construct(array $taxes = [], $computation_method = self::COMBINE_METHOD)
    {
        // sanity check
        foreach ($taxes as $tax) {
            if (!$tax instanceof Tax) {
                throw new RuntimeException('Invalid Tax Object');
            }
        }
        $this->taxes = $taxes;
        $this->computation_method = (int) $computation_method;
    }
    /**
     * Compute and add the taxes to the specified price.
     *
     * @param float $priceTaxExcluded price tax excluded
     *
     * @return float Price with taxes
     */
    public function add_taxes($price_tax_excluded)
    {
        return Tools::round_price($price_tax_excluded * (1 + $this->get_total_rate() / 100));
    }
    /**
     * Compute and remove the taxes to the specified price.
     *
     * @param float $priceTaxIncluded price tax inclusive
     *
     * @return float Price without taxes
     */
    public function remove_taxes($price_tax_included)
    {
        return Tools::round_price($price_tax_included / (1 + $this->get_total_rate() / 100));
    }
    /**
     * @return float total taxes rate
     */
    public function get_total_rate(): float
    {
        $taxes = 0;
        if ($this->computation_method == static::ONE_AFTER_ANOTHER_METHOD) {
            $taxes = 1;
            foreach ($this->taxes as $tax) {
                $taxes *= 1 + $this->get_tax_rate($tax) / 100;
            }
            $taxes = $taxes - 1;
            $taxes = $taxes * 100;
        } else {
            foreach ($this->taxes as $tax) {
                $taxes += $this->get_tax_rate($tax);
            }
        }
        return (float) $taxes;
    }
    /**
     * @throws PrestaShopException
     */
    public function get_taxes_name(): string
    {
        $names = [];
        $language_id = (int) Context::get_context()->language->id;
        foreach ($this->taxes as $tax) {
            $names[] = $tax->get_name($language_id);
        }
        return implode(' - ', $names);
    }
    /**
     * Return the tax amount associated to each taxes of the TaxCalculator.
     *
     * @param float $priceTaxExcluded
     *
     * @return array Array with one amount per tax
     */
    public function get_taxes_amount($price_tax_excluded): array
    {
        $taxes_amounts = [];
        foreach ($this->taxes as $tax) {
            if ($this->computation_method == static::ONE_AFTER_ANOTHER_METHOD) {
                $taxes_amounts[$tax->id] = Tools::round_price($price_tax_excluded * ($this->get_tax_rate($tax) / 100));
                $price_tax_excluded = $price_tax_excluded + $taxes_amounts[$tax->id];
            } else {
                $taxes_amounts[$tax->id] = Tools::round_price($price_tax_excluded * ($this->get_tax_rate($tax) / 100));
            }
        }
        return $taxes_amounts;
    }
    /**
     * Return the total taxes amount.
     *
     * @param float $priceTaxExcluded
     *
     * @return float Amount
     */
    public function get_taxes_total_amount($price_tax_excluded): int|float
    {
        $amount = 0;
        $taxes = $this->get_taxes_amount($price_tax_excluded);
        foreach ($taxes as $tax) {
            $amount += $tax;
        }
        return $amount;
    }
    /**
     * Returns tax rate
     *
     * @return float
     */
    protected function get_tax_rate(Tax $tax): float|int
    {
        if (is_null($tax->rate)) {
            return 0.0;
        }
        return abs($tax->rate);
    }
}