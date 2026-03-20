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
 * Class TaxRulesGroupCore
 */
class Tax_Rules_Group_Core extends Object_Model
{
    /**
     * @var string
     */
    public $name;
    /** @var bool active state */
    public $active;
    /**
     * @var bool
     */
    public $deleted = 0;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'tax_rules_group', 'primary' => 'id_tax_rules_group', 'primaryKeyDbType' => 'int(11)', 'fields' => ['name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 50], 'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'int(11)', 'dbNullable' => false], 'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbNullable' => false], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false]], 'keys' => ['tax_rules_group_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectsNodeName' => 'tax_rule_groups', 'objectNodeName' => 'tax_rule_group', 'fields' => []];
    /**
     * @var array
     */
    protected static $_taxes = [];
    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        if (!$this->deleted && $this->is_used()) {
            $current_tax_rules_group = new Tax_Rules_Group((int) $this->id);
            /** @var TaxRulesGroup $newTaxRulesGroup */
            if (!($new_tax_rules_group = $current_tax_rules_group->duplicate_object()) || !$current_tax_rules_group->historize($new_tax_rules_group)) {
                return false;
            }
            $this->id = (int) $new_tax_rules_group->id;
        }
        return parent::update($null_values);
    }
    /**
     * Save the object with the field deleted to true
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function historize(Tax_Rules_Group $tax_rules_group)
    {
        $this->deleted = true;
        $conn = Db::get_instance();
        return parent::update() && $conn->execute('
		INSERT INTO ' . _DB_PREFIX_ . 'tax_rule
		(id_tax_rules_group, id_country, id_state, zipcode_from, zipcode_to, id_tax, behavior, description)
		(
			SELECT ' . (int) $tax_rules_group->id . ', id_country, id_state, zipcode_from, zipcode_to, id_tax, behavior, description
			FROM ' . _DB_PREFIX_ . 'tax_rule
			WHERE id_tax_rules_group=' . (int) $this->id . '
		)') && $conn->execute('
		UPDATE ' . _DB_PREFIX_ . 'product
		SET id_tax_rules_group=' . (int) $tax_rules_group->id . '
		WHERE id_tax_rules_group=' . (int) $this->id) && $conn->execute('
		UPDATE ' . _DB_PREFIX_ . 'product_shop
		SET id_tax_rules_group=' . (int) $tax_rules_group->id . '
		WHERE id_tax_rules_group=' . (int) $this->id) && $conn->execute('
		UPDATE ' . _DB_PREFIX_ . 'carrier
		SET id_tax_rules_group=' . (int) $tax_rules_group->id . '
		WHERE id_tax_rules_group=' . (int) $this->id) && $conn->execute('
		UPDATE ' . _DB_PREFIX_ . 'carrier_tax_rules_group_shop
		SET id_tax_rules_group=' . (int) $tax_rules_group->id . '
		WHERE id_tax_rules_group=' . (int) $this->id);
    }
    /**
     * @param int $idTaxRule
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_id_tax_rule_group_from_historized_id($id_tax_rule)
    {
        $id_tax_rule = (int) $id_tax_rule;
        if ($id_tax_rule) {
            $connection = Db::read_only();
            $params = $connection->get_row((new Db_Query())->select('t.id_country')->select('t.id_state')->select('t.zipcode_from')->select('t.zipcode_to')->select('t.id_tax')->select('t.behavior')->from('tax_rule', 't')->where('t.id_tax_rule = ' . $id_tax_rule));
            if ($params) {
                return (int) $connection->get_value((new Db_Query())->select('t.id_tax_rule')->from('tax_rule', 't')->where('t.id_tax_rules_group = ' . (int) $this->id)->where('t.id_country = ' . (int) $params['id_country'])->where('t.id_state = ' . (int) $params['id_state'])->where('t.id_tax = ' . (int) $params['id_tax'])->where('t.zipcode_from = "' . p_sql($params['zipcode_from']) . '"')->where('t.zipcode_to = "' . p_sql($params['zipcode_to']) . '"')->where('t.behavior = ' . (int) $params['behavior']));
            }
        }
        return 0;
    }
    /**
     * @param bool $onlyActive
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_tax_rules_groups($only_active = true)
    {
        return Db::read_only()->get_array('
			SELECT DISTINCT g.id_tax_rules_group, g.name, g.active
			FROM `' . _DB_PREFIX_ . 'tax_rules_group` g' . Shop::add_sql_association('tax_rules_group', 'g') . ' WHERE deleted = 0' . ($only_active ? ' AND g.`active` = 1' : '') . '
			ORDER BY name ASC');
    }
    /**
     * @return array an array of tax rules group formatted as $id => $name
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_tax_rules_groups_for_options()
    {
        $tax_rules[] = ['id_tax_rules_group' => 0, 'name' => Tools::display_error('No tax')];
        return array_merge($tax_rules, Tax_Rules_Group::get_tax_rules_groups());
    }
    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function delete()
    {
        $res = Db::get_instance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'tax_rule` WHERE `id_tax_rules_group`=' . (int) $this->id);
        return parent::delete() && $res;
    }
    /**
     * @param int $idCountry
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_associated_tax_rates_by_id_country($id_country)
    {
        $rows = Db::read_only()->get_array('
			SELECT rg.`id_tax_rules_group`, t.`rate`
			FROM `' . _DB_PREFIX_ . 'tax_rules_group` rg
			LEFT JOIN `' . _DB_PREFIX_ . 'tax_rule` tr ON (tr.`id_tax_rules_group` = rg.`id_tax_rules_group`)
			LEFT JOIN `' . _DB_PREFIX_ . 'tax` t ON (t.`id_tax` = tr.`id_tax`)
			WHERE tr.`id_country` = ' . (int) $id_country . '
			AND tr.`id_state` = 0
			AND 0 between `zipcode_from` AND `zipcode_to`');
        $res = [];
        foreach ($rows as $row) {
            $res[$row['id_tax_rules_group']] = $row['rate'];
        }
        return $res;
    }
    /**
     * Returns the tax rules group id corresponding to the name
     *
     * @param string $name
     *
     * @return int id of the tax rules
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_name($name)
    {
        return Db::read_only()->get_value('SELECT `id_tax_rules_group`
			FROM `' . _DB_PREFIX_ . 'tax_rules_group` rg
			WHERE `name` = \'' . p_sql($name) . '\'');
    }
    /**
     * @param int $idCountry
     * @param int $idState
     * @param bool $idTaxRule
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function has_unique_tax_rule_for_country($id_country, $id_state, $id_tax_rule = false)
    {
        $rules = Tax_Rule::get_tax_rules_by_group_id((int) Context::get_context()->language->id, (int) $this->id);
        foreach ($rules as $rule) {
            if ($rule['id_country'] == $id_country && $id_state == $rule['id_state'] && !$rule['behavior'] && (int) $id_tax_rule != $rule['id_tax_rule']) {
                return true;
            }
        }
        return false;
    }
    /**
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function is_used()
    {
        return Db::read_only()->get_value('
		SELECT `id_tax_rules_group`
		FROM `' . _DB_PREFIX_ . 'order_detail`
		WHERE `id_tax_rules_group` = ' . (int) $this->id);
    }
}