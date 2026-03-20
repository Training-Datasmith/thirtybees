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
 * Class TaxCore
 */
class Tax_Core extends Object_Model
{
    /** @var string|string[] Name */
    public $name;
    /** @var float Rate (%) */
    public $rate;
    /** @var bool active state */
    public $active;
    /** @var bool true if the tax has been historized */
    public $deleted = 0;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'tax', 'primary' => 'id_tax', 'multilang' => true, 'fields' => [
        'rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => true, 'size' => 10, 'decimals' => 3],
        'active' => ['type' => self::TYPE_BOOL, 'dbDefault' => '1'],
        'deleted' => ['type' => self::TYPE_BOOL, 'dbDefault' => '0'],
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 32],
    ]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectsNodeName' => 'taxes'];
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete()
    {
        /* Clean associations */
        Tax_Rule::delete_tax_rule_by_id_tax((int) $this->id);
        if ($this->is_used()) {
            return $this->historize();
        }
        return parent::delete();
    }
    /**
     * Save the object with the field deleted to true
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function historize()
    {
        $this->deleted = true;
        return parent::update();
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function toggle_status()
    {
        if (parent::toggle_status()) {
            return $this->_on_status_change();
        }
        return false;
    }
    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        if (!$this->deleted && $this->is_used()) {
            $historized_tax = new Tax($this->id);
            $historized_tax->historize();
            // remove the id in order to create a new object
            $this->id = 0;
            $res = $this->add();
            // change tax id in the tax rule table
            $res = Tax_Rule::swap_tax_id($historized_tax->id, $this->id) && $res;
            return $res;
        }
        if (parent::update($null_values)) {
            return $this->_on_status_change();
        }
        return false;
    }
    /**
     * @return bool
     *
     * @deprecated 2.0.0
     * @throws PrestaShopException
     */
    protected function _on_status_change()
    {
        if (!$this->active) {
            return Tax_Rule::delete_tax_rule_by_id_tax($this->id);
        }
        return true;
    }
    /**
     * Returns true if the tax is used in an order details
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_used()
    {
        return Db::read_only()->get_value('
		SELECT `id_tax`
		FROM `' . _DB_PREFIX_ . 'order_detail_tax`
		WHERE `id_tax` = ' . (int) $this->id);
    }
    /**
     * Get all available taxes
     *
     * @param bool $idLang
     * @param bool $activeOnly
     *
     * @return array Taxes
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_taxes($id_lang = false, $active_only = true)
    {
        $sql = new Db_Query();
        $sql->select('t.id_tax, t.rate');
        $sql->from('tax', 't');
        $sql->where('t.`deleted` != 1');
        if ($id_lang) {
            $sql->select('tl.name, tl.id_lang');
            $sql->left_join('tax_lang', 'tl', 't.`id_tax` = tl.`id_tax` AND tl.`id_lang` = ' . (int) $id_lang);
            $sql->order_by('`name` ASC');
        }
        if ($active_only) {
            $sql->where('t.`active` = 1');
        }
        return Db::read_only()->get_array($sql);
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function exclude_taxe_option()
    {
        return !Configuration::get('PS_TAX');
    }
    /**
     * Return the tax id associated to the specified name
     *
     * @param string $taxName
     * @param int $active (true by default)
     *
     * @return bool|int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_tax_id_by_name($tax_name, $active = 1)
    {
        $tax = Db::read_only()->get_row('
			SELECT t.`id_tax`
			FROM `' . _DB_PREFIX_ . 'tax` t
			LEFT JOIN `' . _DB_PREFIX_ . 'tax_lang` tl ON (tl.id_tax = t.id_tax)
			WHERE tl.`name` = \'' . p_sql($tax_name) . '\' ' . ($active == 1 ? ' AND t.`active` = 1' : ''));
        return $tax ? (int) $tax['id_tax'] : false;
    }
    /**
     * Returns the ecotax tax rate
     *
     * @param int $idAddress
     *
     * @return float $tax_rate
     *
     * @throws PrestaShopException
     */
    public static function get_product_ecotax_rate($id_address = null)
    {
        $address = Address::initialize($id_address);
        $tax_manager = Tax_Manager_Factory::get_manager($address, (int) Configuration::get('PS_ECOTAX_TAX_RULES_GROUP_ID'));
        $tax_calculator = $tax_manager->get_tax_calculator();
        return $tax_calculator->get_total_rate();
    }
    /**
     * Returns the carrier tax rate
     *
     * @param int $idCarrier
     * @param int|null $idAddress
     *
     * @return float $tax_rate
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_carrier_tax_rate($id_carrier, $id_address = null)
    {
        $address = Address::initialize($id_address);
        $id_tax_rules = (int) Carrier::get_id_tax_rules_group_by_id_carrier((int) $id_carrier);
        $tax_manager = Tax_Manager_Factory::get_manager($address, $id_tax_rules);
        $tax_calculator = $tax_manager->get_tax_calculator();
        return $tax_calculator->get_total_rate();
    }
    /**
     * Returns the product tax
     *
     * @param int $idProduct
     * @param int|null $idAddress
     *
     * @return float
     * @throws PrestaShopException
     */
    public static function get_product_tax_rate($id_product, $id_address = null, ?Context $context = null)
    {
        if ($context == null) {
            $context = Context::get_context();
        }
        $address = Address::initialize($id_address);
        $id_tax_rules = (int) Product::get_id_tax_rules_group_by_id_product($id_product, $context);
        $tax_manager = Tax_Manager_Factory::get_manager($address, $id_tax_rules);
        $tax_calculator = $tax_manager->get_tax_calculator();
        return $tax_calculator->get_total_rate();
    }
    /**
     * Returns tax name
     *
     *
     * @return string
     * @throws PrestaShopException
     */
    public function get_name(int $language_id = 0)
    {
        if (is_array($this->name)) {
            if (isset($this->name[$language_id])) {
                return (string) $this->name[$language_id];
            }
            $default_lang_id = (int) Configuration::get('PS_LANG_DEFAULT');
            if (isset($this->name[$default_lang_id])) {
                return (string) $this->name[$language_id];
            }
            foreach ($this->name as $name) {
                return $name;
            }
            return '';
        }
        return (string) $this->name;
    }
}