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
 * Class SpecificPriceRuleCore
 */
class Specific_Price_Rule_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'specific_price_rule', 'primary' => 'id_specific_price_rule', 'fields' => ['name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true], 'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'dbDefault' => '1'], 'id_currency' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_country' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'from_quantity' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true, 'dbType' => 'mediumint(8) unsigned'], 'price' => ['type' => self::TYPE_PRICE, 'validate' => 'isNegativePrice', 'required' => true, 'dbNullable' => true], 'reduction' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true], 'reduction_tax' => ['type' => self::TYPE_INT, 'validate' => 'isBool', 'required' => true, 'dbType' => 'tinyint(1)', 'dbDefault' => '1'], 'reduction_type' => ['type' => self::TYPE_STRING, 'validate' => 'isReductionType', 'required' => true, 'values' => ['amount', 'percentage']], 'from' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => true], 'to' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat', 'required' => true]], 'keys' => ['specific_price_rule' => ['id_product' => ['type' => Object_Model::KEY, 'columns' => ['id_shop', 'id_currency', 'id_country', 'id_group', 'from_quantity', 'from', 'to']]]]];
    /** @var bool $rules_application_enable */
    protected static $rules_application_enable = true;
    /** @var string $name */
    public $name;
    /** @var int $id_shop */
    public $id_shop;
    /** @var int $id_currency */
    public $id_currency;
    /** @var int $id_country */
    public $id_country;
    /** @var int $id_group */
    public $id_group;
    /** @var int $from_quantity */
    public $from_quantity;
    /** @var float $price */
    public $price;
    /** @var float $reduction */
    public $reduction;
    /** @var int $reduction_tax */
    public $reduction_tax;
    /** @var string $reduction_type */
    public $reduction_type;
    /** @var string $from */
    public $from;
    /** @var string $to */
    public $to;
    /** @var array $webserviceParameters */
    protected $webservice_parameters = ['objectsNodeName' => 'specific_price_rules', 'objectNodeName' => 'specific_price_rule', 'fields' => ['id_shop' => ['xlink_resource' => 'shops', 'required' => true], 'id_country' => ['xlink_resource' => 'countries', 'required' => true], 'id_currency' => ['xlink_resource' => 'currencies', 'required' => true], 'id_group' => ['xlink_resource' => 'groups', 'required' => true]]];
    public static function disable_any_application(): void
    {
        static::$rules_application_enable = false;
    }
    public static function enable_any_application(): void
    {
        static::$rules_application_enable = true;
    }
    /**
     * @param array|bool $products
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function apply_all_rules($products = false): void
    {
        if (!static::$rules_application_enable) {
            return;
        }
        $rules = new Presta_Shop_Collection('SpecificPriceRule');
        foreach ($rules as $rule) {
            /** @var SpecificPriceRule $rule */
            $rule->apply($products);
        }
    }
    /**
     * @param bool $products
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function apply($products = false): void
    {
        if (!static::$rules_application_enable) {
            return;
        }
        $this->reset_application($products);
        $products = $this->get_affected_products($products);
        foreach ($products as $product) {
            static::apply_rule_to_product((int) $this->id, (int) $product['id_product'], (int) $product['id_product_attribute']);
        }
    }
    /**
     * @param bool $products
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function reset_application($products = false)
    {
        $where = '';
        if ($products && is_array($products) && count($products)) {
            $where .= ' AND id_product IN (' . implode(', ', array_map(intval(...), $products)) . ')';
        }
        return Db::get_instance()->delete('specific_price', '`id_specific_price_rule` = ' . (int) $this->id . $where);
    }
    /**
     * Return the product list affected by this specific rule.
     *
     * @param bool|array $products Products list limitation.
     *
     * @return array Affected products list IDs.
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_affected_products($products = false)
    {
        $conditions_group = $this->get_conditions();
        $current_shop_id = Context::get_context()->shop->id;
        $conn = Db::read_only();
        if ($conditions_group) {
            $result = [];
            foreach ($conditions_group as $condition_group) {
                // Base request
                $query = (new Db_Query())->select('DISTINCT p.`id_product`')->from('product', 'p')->left_join('product_shop', 'ps', 'p.`id_product` = ps.`id_product`')->where('ps.id_shop = ' . (int) $current_shop_id);
                $attributes_join_added = false;
                // Add the conditions
                foreach ($condition_group as $id_condition => $condition) {
                    if ($condition['type'] == 'attribute') {
                        if (!$attributes_join_added) {
                            $query->select('pa.`id_product_attribute`')->left_join('product_attribute', 'pa', 'p.`id_product` = pa.`id_product`')->join(Shop::add_sql_association('product_attribute', 'pa', false));
                            $attributes_join_added = true;
                        }
                        $query->left_join('product_attribute_combination', 'pac' . (int) $id_condition, 'pa.`id_product_attribute` = pac' . (int) $id_condition . '.`id_product_attribute`')->where('pac' . (int) $id_condition . '.`id_attribute` = ' . (int) $condition['value']);
                    } elseif ($condition['type'] == 'manufacturer') {
                        $query->where('p.id_manufacturer = ' . (int) $condition['value']);
                    } elseif ($condition['type'] == 'category') {
                        $query->left_join('category_product', 'cp' . (int) $id_condition, 'p.`id_product` = cp' . (int) $id_condition . '.`id_product`')->where('cp' . (int) $id_condition . '.id_category = ' . (int) $condition['value']);
                    } elseif ($condition['type'] == 'supplier') {
                        $query->where('EXISTS(
							SELECT
								`ps' . (int) $id_condition . '`.`id_product`
							FROM
								`' . _DB_PREFIX_ . 'product_supplier` `ps' . (int) $id_condition . '`
							WHERE
								`p`.`id_product` = `ps' . (int) $id_condition . '`.`id_product`
								AND `ps' . (int) $id_condition . '`.`id_supplier` = ' . (int) $condition['value'] . '
						)');
                    } elseif ($condition['type'] == 'feature') {
                        $query->left_join('feature_product', 'fp' . (int) $id_condition, 'p.`id_product` = fp' . (int) $id_condition . '.`id_product`')->where('fp' . (int) $id_condition . '.`id_feature_value` = ' . (int) $condition['value']);
                    }
                }
                // Products limitation
                if ($products && count($products)) {
                    $query->where('p.`id_product` IN (' . implode(', ', array_map(intval(...), $products)) . ')');
                }
                // Force the column id_product_attribute if not requested
                if (!$attributes_join_added) {
                    $query->select('NULL as `id_product_attribute`');
                }
                $condition_group_results = $conn->get_array($query);
                foreach ($condition_group_results as $row) {
                    $key = $row['id_product'] . '|' . $row['id_product_attribute'];
                    $result[$key] = $row;
                }
            }
            return array_values($result);
        }
        // All products without conditions
        $query = new Db_Query();
        $query->select('p.`id_product`')->select('NULL as `id_product_attribute`')->from('product', 'p')->left_join('product_shop', 'ps', 'p.`id_product` = ps.`id_product`')->where('ps.id_shop = ' . (int) $current_shop_id);
        if ($products && count($products)) {
            $query->where('p.`id_product` IN (' . implode(', ', array_map(intval(...), $products)) . ')');
        }
        return $conn->get_array($query);
    }
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_conditions()
    {
        $conn = Db::read_only();
        $conditions = $conn->get_array('
			SELECT g.*, c.*
			FROM ' . _DB_PREFIX_ . 'specific_price_rule_condition_group g
			LEFT JOIN ' . _DB_PREFIX_ . 'specific_price_rule_condition c
				ON (c.id_specific_price_rule_condition_group = g.id_specific_price_rule_condition_group)
			WHERE g.id_specific_price_rule=' . (int) $this->id);
        $conditions_group = [];
        if ($conditions) {
            foreach ($conditions as &$condition) {
                if ($condition['type'] == 'attribute') {
                    $condition['id_attribute_group'] = $conn->get_value('SELECT id_attribute_group
							 FROM ' . _DB_PREFIX_ . 'attribute
							 WHERE id_attribute=' . (int) $condition['value']);
                } elseif ($condition['type'] == 'feature') {
                    $condition['id_feature'] = $conn->get_value('SELECT id_feature
							 FROM ' . _DB_PREFIX_ . 'feature_value
							 WHERE id_feature_value=' . (int) $condition['value']);
                }
                $conditions_group[(int) $condition['id_specific_price_rule_condition_group']][] = $condition;
            }
        }
        return $conditions_group;
    }
    /**
     * @param int $idRule
     * @param int $idProduct
     * @param int|null $idProductAttribute
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function apply_rule_to_product($id_rule, $id_product, $id_product_attribute = null)
    {
        $rule = new static((int) $id_rule);
        if (!Validate::is_loaded_object($rule) || !Validate::is_unsigned_int($id_product)) {
            return false;
        }
        $specific_price = new Specific_Price();
        $specific_price->id_specific_price_rule = (int) $rule->id;
        $specific_price->id_product = (int) $id_product;
        $specific_price->id_product_attribute = (int) $id_product_attribute;
        $specific_price->id_customer = 0;
        $specific_price->id_shop = (int) $rule->id_shop;
        $specific_price->id_country = (int) $rule->id_country;
        $specific_price->id_currency = (int) $rule->id_currency;
        $specific_price->id_group = (int) $rule->id_group;
        $specific_price->from_quantity = (int) $rule->from_quantity;
        $specific_price->price = round($rule->price, _TB_PRICE_DATABASE_PRECISION_);
        $specific_price->reduction_type = $rule->reduction_type;
        $specific_price->reduction_tax = $rule->reduction_tax;
        $specific_price->reduction = $rule->reduction_type === 'percentage' ? round($rule->reduction / 100, _TB_PRICE_DATABASE_PRECISION_) : (float) $rule->reduction;
        $specific_price->from = $rule->from;
        $specific_price->to = $rule->to;
        return $specific_price->add();
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        $this->delete_conditions();
        Db::get_instance()->delete('specific_price', '`id_specific_price_rule` = ' . (int) $this->id);
        return parent::delete();
    }
    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_conditions(): void
    {
        $ids_condition_group = Db::read_only()->get_array((new Db_Query())->select('`id_specific_price_rule_condition_group`')->from('specific_price_rule_condition_group')->where('`id_specific_price_rule` = ' . (int) $this->id));
        if ($ids_condition_group) {
            $conn = Db::get_instance();
            foreach ($ids_condition_group as $row) {
                $conn->delete('specific_price_rule_condition_group', '`id_specific_price_rule_condition_group` = ' . (int) $row['id_specific_price_rule_condition_group']);
                $conn->delete('specific_price_rule_condition', '`id_specific_price_rule_condition_group` = ' . (int) $row['id_specific_price_rule_condition_group']);
            }
        }
    }
    /**
     * @param array $conditions
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_conditions($conditions)
    {
        if (!is_array($conditions)) {
            return false;
        }
        $conn = Db::get_instance();
        $result = $conn->insert('specific_price_rule_condition_group', ['id_specific_price_rule' => (int) $this->id]);
        if (!$result) {
            return false;
        }
        $id_specific_price_rule_condition_group = (int) $conn->Insert_ID();
        foreach ($conditions as $condition) {
            $result = $conn->insert('specific_price_rule_condition', ['id_specific_price_rule_condition_group' => $id_specific_price_rule_condition_group, 'type' => p_sql($condition['type']), 'value' => round($condition['value'], _TB_PRICE_DATABASE_PRECISION_)]);
            if (!$result) {
                return false;
            }
        }
        return true;
    }
}