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
 * Class TaxRulesTaxManagerCore
 */
class Tax_Rules_Tax_Manager_Core implements Tax_Manager_Interface
{
    /**
     * @var Address
     */
    public $address;
    /**
     * @var TaxCalculator
     */
    public $tax_calculator;
    /**
     * @var Core_Business_ConfigurationInterface
     */
    private $configuration_manager;
    /**
     * @param int $type TaxRulesGroup id
     * @throws PrestaShopException
     */
    public function __construct(Address $address, public $type, ?Core_business_configuration_Interface $configuration_manager = null)
    {
        if ($configuration_manager === null) {
            $this->configuration_manager = Adapter_service_Locator::get('Core_Business_ConfigurationInterface');
        } else {
            $this->configuration_manager = $configuration_manager;
        }
        $this->address = $address;
    }
    /**
     * Returns true if this tax manager is available for this address
     */
    public static function is_available_for_this_address(Address $address): bool
    {
        return true;
        // default manager, available for all addresses
    }
    /**
     * Return the tax calculator associated to this address
     *
     * @return TaxCalculator
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_tax_calculator()
    {
        static $tax_enabled = null;
        if (isset($this->tax_calculator)) {
            return $this->tax_calculator;
        }
        if ($tax_enabled === null) {
            $tax_enabled = $this->configuration_manager->get('PS_TAX');
        }
        if (!$tax_enabled) {
            return new Tax_Calculator([]);
        }
        $taxes = [];
        $postcode = 0;
        if (!empty($this->address->postcode)) {
            $postcode = $this->address->postcode;
        }
        $cache_id = (int) $this->address->id_country . '-' . (int) $this->address->id_state . '-' . $postcode . '-' . (int) $this->type;
        if (!Cache::is_stored($cache_id)) {
            $rows = Db::read_only()->get_array('
				SELECT tr.*
				FROM `' . _DB_PREFIX_ . 'tax_rule` tr
				JOIN `' . _DB_PREFIX_ . 'tax_rules_group` trg ON (tr.`id_tax_rules_group` = trg.`id_tax_rules_group`)
				WHERE trg.`active` = 1
				AND tr.`id_country` = ' . (int) $this->address->id_country . '
				AND tr.`id_tax_rules_group` = ' . (int) $this->type . '
				AND tr.`id_state` IN (0, ' . (int) $this->address->id_state . ')
				AND (\'' . p_sql($postcode) . '\' BETWEEN tr.`zipcode_from` AND tr.`zipcode_to`
					OR (tr.`zipcode_to` = 0 AND tr.`zipcode_from` IN(0, \'' . p_sql($postcode) . '\')))
				ORDER BY tr.`zipcode_from` DESC, tr.`zipcode_to` DESC, tr.`id_state` DESC, tr.`id_country` DESC');
            $behavior = 0;
            $first_row = true;
            foreach ($rows as $row) {
                $tax = new Tax((int) $row['id_tax']);
                $taxes[] = $tax;
                // the applied behavior correspond to the most specific rules
                if ($first_row) {
                    $behavior = $row['behavior'];
                    $first_row = false;
                }
                if ($row['behavior'] == 0) {
                    break;
                }
            }
            $result = new Tax_Calculator($taxes, $behavior);
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
}