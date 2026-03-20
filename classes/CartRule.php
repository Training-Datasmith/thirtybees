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
 * Class CartRuleCore
 */
class Cart_Rule_Core extends Object_Model
{
    /* Filters used when retrieving the cart rules applied to a cart of when calculating the value of a reduction */
    public const FILTER_ACTION_ALL = 1;
    public const FILTER_ACTION_SHIPPING = 2;
    public const FILTER_ACTION_REDUCTION = 3;
    public const FILTER_ACTION_GIFT = 4;
    public const FILTER_ACTION_ALL_NOCAP = 5;
    public const BO_ORDER_CODE_PREFIX = 'BO_ORDER_';
    public const APPLY_DISCOUNT_TO_ORDER_WITHOUT_SHIPPING = 0;
    public const APPLY_DISCOUNT_TO_CHEAPEST_PRODUCT_FROM_SELECTION = -1;
    public const APPLY_DISCOUNT_TO_SELECTED_PRODUCTS = -2;
    public const SYSTEM_RULE_CHEAPEST_PRODUCT = 'cheapest_product';
    /**
     * This variable controls that a free gift is offered only once, even when multi-shipping is activated and the same product is delivered in both addresses
     *
     * @var array
     */
    protected static $only_one_gift = [];
    /**
     * @var int $id
     */
    public $id;
    /**
     * @var string|string[] $name
     */
    public $name;
    /**
     * @var int $id_customer
     */
    public $id_customer;
    /**
     * @var string $date_from
     */
    public $date_from;
    /**
     * @var string $date_to
     */
    public $date_to;
    /**
     * @var string $description
     */
    public $description;
    /**
     * @var int $quantity
     */
    public $quantity = 1;
    /**
     * @var int $quantity_per_user
     */
    public $quantity_per_user = 1;
    /**
     * @var int $priority
     */
    public $priority = 1;
    /**
     * @var bool $partial_use
     */
    public $partial_use = true;
    /**
     * @var string $code
     */
    public $code;
    /**
     * @var float $minimum_amount
     */
    public $minimum_amount;
    /**
     * @var bool $minimum_amount_tax
     */
    public $minimum_amount_tax;
    /**
     * @var int $minimum_amount_currency
     */
    public $minimum_amount_currency;
    /**
     * @var bool $minimum_amount_shipping
     */
    public $minimum_amount_shipping;
    /**
     * @var bool $country_restriction
     */
    public $country_restriction;
    /**
     * @var bool $carrier_restriction
     */
    public $carrier_restriction;
    /**
     * @var bool $group_restriction
     */
    public $group_restriction;
    /**
     * @var bool $cart_rule_restriction
     */
    public $cart_rule_restriction;
    /**
     * @var bool $product_restriction
     */
    public $product_restriction;
    /**
     * @var bool minimum_amount_product_restriction
     */
    public $minimum_amount_product_restriction;
    /**
     * @var bool $shop_restriction
     */
    public $shop_restriction;
    /**
     * @var bool $free_shipping
     */
    public $free_shipping;
    /**
     * @var float $reduction_percent
     */
    public $reduction_percent;
    /**
     * @var float
     */
    public $reduction_max;
    /**
     * @var bool
     */
    public $reduction_max_tax;
    /**
     * @var int
     */
    public $reduction_max_currency;
    /**
     * @var float $reduction_amount
     */
    public $reduction_amount;
    /**
     * @var bool $reduction_tax
     */
    public $reduction_tax;
    /**
     * @var int $reduction_currency
     */
    public $reduction_currency;
    /**
     * @var int $reduction_product
     */
    public $reduction_product;
    /**
     * @var int $gift_product
     */
    public $gift_product;
    /**
     * @var int $gift_product_attribute
     */
    public $gift_product_attribute;
    /**
     * @var bool $highlight
     */
    public $highlight;
    /**
     * @var bool $active
     */
    public $active = true;
    /**
     * @var string $date_add
     */
    public $date_add;
    /**
     * @var string $date_upd
     */
    public $date_upd;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'cart_rule', 'primary' => 'id_cart_rule', 'multilang' => true, 'fields' => [
        'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'size' => 10, 'dbDefault' => '0'],
        'date_from' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        'date_to' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true],
        'description' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => Object_Model::SIZE_TEXT],
        'quantity' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbDefault' => '0'],
        'quantity_per_user' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbDefault' => '0'],
        'priority' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbDefault' => '1'],
        'partial_use' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'code' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 254, 'dbNullable' => false],
        'minimum_amount' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
        'minimum_amount_tax' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'minimum_amount_currency' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'size' => 10, 'dbDefault' => '0'],
        'minimum_amount_shipping' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'country_restriction' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'carrier_restriction' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'group_restriction' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'cart_rule_restriction' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'product_restriction' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'minimum_amount_product_restriction' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'shop_restriction' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'free_shipping' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'reduction_percent' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPercentage', 'size' => 5, 'decimals' => 2, 'dbDefault' => '0.00'],
        'reduction_max' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
        'reduction_max_tax' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'reduction_max_currency' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'size' => 10, 'dbDefault' => '0'],
        'reduction_amount' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
        'reduction_tax' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'reduction_currency' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbDefault' => '0'],
        'reduction_product' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'size' => 10, 'signed' => true, 'dbDefault' => '0'],
        'gift_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbDefault' => '0'],
        'gift_product_attribute' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbDefault' => '0'],
        'highlight' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 254],
    ], 'keys' => ['cart_rule' => ['group_restriction' => ['type' => Object_Model::KEY, 'columns' => ['group_restriction', 'active', 'date_to']], 'group_restriction_2' => ['type' => Object_Model::KEY, 'columns' => ['group_restriction', 'active', 'highlight', 'date_to']], 'id_customer' => ['type' => Object_Model::KEY, 'columns' => ['id_customer', 'active', 'date_to']], 'id_customer_2' => ['type' => Object_Model::KEY, 'columns' => ['id_customer', 'active', 'highlight', 'date_to']]], 'cart_rule_shop' => ['primary' => ['type' => Object_Model::PRIMARY_KEY, 'columns' => ['id_cart_rule', 'id_shop']]]]];
    /**
     * Copy conditions from one cart rule to an other
     *
     * @param int $idCartRuleSource
     * @param int $idCartRuleDestination
     *
     * @throws PrestaShopException
     */
    public static function copy_conditions($id_cart_rule_source, $id_cart_rule_destination): void
    {
        $conn = Db::get_instance();
        $conn->execute('
		INSERT INTO `' . _DB_PREFIX_ . 'cart_rule_shop` (`id_cart_rule`, `id_shop`)
		(SELECT ' . (int) $id_cart_rule_destination . ', id_shop FROM `' . _DB_PREFIX_ . 'cart_rule_shop` WHERE `id_cart_rule` = ' . (int) $id_cart_rule_source . ')');
        $conn->execute('
		INSERT INTO `' . _DB_PREFIX_ . 'cart_rule_carrier` (`id_cart_rule`, `id_carrier`)
		(SELECT ' . (int) $id_cart_rule_destination . ', id_carrier FROM `' . _DB_PREFIX_ . 'cart_rule_carrier` WHERE `id_cart_rule` = ' . (int) $id_cart_rule_source . ')');
        $conn->execute('
		INSERT INTO `' . _DB_PREFIX_ . 'cart_rule_group` (`id_cart_rule`, `id_group`)
		(SELECT ' . (int) $id_cart_rule_destination . ', id_group FROM `' . _DB_PREFIX_ . 'cart_rule_group` WHERE `id_cart_rule` = ' . (int) $id_cart_rule_source . ')');
        $conn->execute('
		INSERT INTO `' . _DB_PREFIX_ . 'cart_rule_country` (`id_cart_rule`, `id_country`)
		(SELECT ' . (int) $id_cart_rule_destination . ', id_country FROM `' . _DB_PREFIX_ . 'cart_rule_country` WHERE `id_cart_rule` = ' . (int) $id_cart_rule_source . ')');
        $conn->execute('
		INSERT INTO `' . _DB_PREFIX_ . 'cart_rule_combination` (`id_cart_rule_1`, `id_cart_rule_2`)
		(SELECT DISTINCT ' . (int) $id_cart_rule_destination . ', IF(id_cart_rule_1 != ' . (int) $id_cart_rule_source . ', id_cart_rule_1, id_cart_rule_2) FROM `' . _DB_PREFIX_ . 'cart_rule_combination`
		WHERE `id_cart_rule_1` = ' . (int) $id_cart_rule_source . ' OR `id_cart_rule_2` = ' . (int) $id_cart_rule_source . ')');
        // Todo : should be changed soon, be must be copied too
        // Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'cart_rule_product_rule` WHERE `id_cart_rule` = '.(int)$this->id);
        // Db::getInstance()->execute('DELETE FROM `'._DB_PREFIX_.'cart_rule_product_rule_value` WHERE `id_product_rule` NOT IN (SELECT `id_product_rule` FROM `'._DB_PREFIX_.'cart_rule_product_rule`)');
        // Copy products/category filters
        $sql = new Db_Query();
        $sql->select('`id_product_rule_group`, `quantity`');
        $sql->from('cart_rule_product_rule_group');
        $sql->where('`id_cart_rule` = ' . (int) $id_cart_rule_source);
        $products_rules_group_source = $conn->get_array($sql);
        foreach ($products_rules_group_source as $product_rule_group_source) {
            $conn->insert('cart_rule_product_rule_group', ['id_cart_rule' => (int) $id_cart_rule_destination, 'quantity' => (int) $product_rule_group_source['quantity']]);
            $id_product_rule_group_destination = $conn->Insert_ID();
            $products_rules_source = $conn->get_array((new Db_Query())->select('`id_product_rule`, `type`')->from('cart_rule_product_rule')->where('`id_product_rule_group` = ' . (int) $products_rules_group_source['id_product_rule_group']));
            foreach ($products_rules_source as $product_rule_source) {
                $conn->insert('cart_rule_product_rule', ['id_product_rule_group' => (int) $id_product_rule_group_destination, 'type' => p_sql($product_rule_source['type'])]);
                $id_product_rule_destination = $conn->Insert_ID();
                $products_rules_values_source = $conn->get_array((new Db_Query())->select('`id_item`')->from('cart_rule_product_rule_value')->where('`id_product_rule` = ' . (int) $products_rules_source['id_product_rule']));
                foreach ($products_rules_values_source as $product_rule_value_source) {
                    $conn->insert('cart_rule_product_rule_value', ['id_product_rule' => (int) $id_product_rule_destination, 'id_item' => (int) $product_rule_value_source['id_item']]);
                }
            }
        }
    }
    /**
     * Retrieves the id associated to the given code
     *
     * @param string $code
     *
     * @return int|bool
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_code($code)
    {
        if (!Validate::is_clean_html($code)) {
            return false;
        }
        return Db::read_only()->get_value((new Db_Query())->select('`id_cart_rule`')->from('cart_rule')->where('`code` = \'' . p_sql($code) . '\''));
    }
    /**
     * @param int $idLang
     * @param int $idCustomer
     * @param bool $active
     * @param bool $includeGeneric
     * @param bool $inStock
     * @param bool $freeShippingOnly
     * @param bool $highlightOnly
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_customer_cart_rules($id_lang, $id_customer, $active = false, $include_generic = true, $in_stock = false, ?Cart $cart = null, $free_shipping_only = false, $highlight_only = false)
    {
        if (!static::is_feature_active()) {
            return [];
        }
        $sql = (new Db_Query())->select('*')->from('cart_rule', 'cr')->left_join('cart_rule_lang', 'crl', 'cr.`id_cart_rule` = crl.`id_cart_rule` AND crl.`id_lang` = ' . (int) $id_lang)->where('cr.`date_from` < \'' . date('Y-m-d H:i:s') . '\'')->where('cr.`date_to` > \'' . date('Y-m-d H:i:s') . '\'');
        if ($active) {
            $sql->where('cr.`active` = 1');
        }
        if ($in_stock) {
            $sql->where('cr.`quantity` > 0');
        }
        if ($free_shipping_only) {
            $sql->where('`free_shipping` = 1');
            $sql->where('`carrier_restriction` = 1');
        }
        if ($highlight_only) {
            $sql->where('`highlight` = 1');
            $sql->where('`code` NOT LIKE \'' . p_sql(static::BO_ORDER_CODE_PREFIX) . '%\'');
        }
        $sql->where('cr.`id_customer` = ' . (int) $id_customer . ' OR (cr.`group_restriction` = 1 AND cr.`id_customer` = 0)' . ($include_generic && (int) $id_customer !== 0 ? ' OR cr.`id_customer` = 0' : ''));
        $conn = Db::read_only();
        $result = $conn->get_array($sql);
        if (empty($result)) {
            return [];
        }
        // Remove cart rule that does not match the customer groups
        $customer_groups = Customer::get_groups_static($id_customer);
        foreach ($result as $key => $cart_rule) {
            if ($cart_rule['group_restriction']) {
                $cart_rule_groups = $conn->get_array((new Db_Query())->select('`id_group`')->from('cart_rule_group')->where('id_cart_rule = ' . (int) $cart_rule['id_cart_rule']));
                foreach ($cart_rule_groups as $cart_rule_group) {
                    if (in_array($cart_rule_group['id_group'], $customer_groups)) {
                        continue 2;
                    }
                }
                unset($result[$key]);
            }
        }
        foreach ($result as &$cart_rule) {
            if ($cart_rule['quantity_per_user']) {
                $quantity_used = Order::get_discounts_customer((int) $id_customer, (int) $cart_rule['id_cart_rule']);
                if (isset($cart->id)) {
                    $quantity_used += $cart->get_discounts_customer((int) $cart_rule['id_cart_rule']);
                }
                $cart_rule['quantity_for_user'] = $cart_rule['quantity_per_user'] - $quantity_used;
            } else {
                $cart_rule['quantity_for_user'] = 0;
            }
            // Backwards compatibility
            $cart_rule['id_group'] = 0;
            if ($cart_rule['free_shipping']) {
                $cart_rule['id_discount_type'] = 3;
            } elseif ($cart_rule['reduction_percent'] > 0) {
                $cart_rule['id_discount_type'] = 1;
            } elseif ($cart_rule['reduction_amount'] > 0) {
                $cart_rule['id_discount_type'] = 2;
            }
            if ($cart_rule['reduction_percent'] > 0) {
                $cart_rule['value'] = $cart_rule['reduction_percent'];
            } elseif ($cart_rule['reduction_amount'] > 0) {
                $cart_rule['value'] = $cart_rule['reduction_amount'];
            }
            $cart_rule['cumulable'] = $cart_rule['cart_rule_restriction'];
            $cart_rule['cumulable_reduction'] = false;
            $cart_rule['minimal'] = $cart_rule['minimum_amount'];
            $cart_rule['include_tax'] = $cart_rule['reduction_tax'];
            $cart_rule['behavior_not_exhausted'] = $cart_rule['partial_use'];
            $cart_rule['cart_display'] = true;
        }
        unset($cart_rule);
        foreach ($result as $key => $cart_rule) {
            if ($cart_rule['shop_restriction']) {
                $cart_rule_shops = $conn->get_array((new Db_Query())->select('`id_shop`')->from('cart_rule_shop')->where('`id_cart_rule` = ' . (int) $cart_rule['id_cart_rule']));
                foreach ($cart_rule_shops as $cart_rule_shop) {
                    if (Shop::is_feature_active() && $cart_rule_shop['id_shop'] == Context::get_context()->shop->id) {
                        continue 2;
                    }
                }
                unset($result[$key]);
            }
        }
        if (isset($cart->id)) {
            foreach ($result as $key => $cart_rule) {
                if ($cart_rule['product_restriction']) {
                    $cr = new Cart_Rule((int) $cart_rule['id_cart_rule']);
                    $r = $cr->check_product_restrictions(Context::get_context(), false, false);
                    if ($r !== false) {
                        continue;
                    }
                    unset($result[$key]);
                }
            }
        }
        $result_bak = $result;
        $result = [];
        $country_restriction = false;
        foreach ($result_bak as $cart_rule) {
            if ($cart_rule['country_restriction']) {
                $country_restriction = true;
                $countries = $conn->get_array((new Db_Query())->select('`id_country`')->from('address')->where('`id_customer` = ' . (int) $id_customer)->where('`deleted` = 0'));
                foreach ($countries as $country) {
                    $id_cart_rule = (bool) $conn->get_value((new Db_Query())->select('crc.`id_cart_rule`')->from('cart_rule_country', 'crc')->where('crc.`id_cart_rule` = ' . (int) $cart_rule['id_cart_rule'])->where('crc.`id_country` = ' . (int) $country['id_country']));
                    if ($id_cart_rule) {
                        $result[] = $cart_rule;
                        break;
                    }
                }
            } else {
                $result[] = $cart_rule;
            }
        }
        if (!$country_restriction) {
            return $result_bak;
        }
        return $result;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_feature_active()
    {
        static $is_feature_active = null;
        if ($is_feature_active === null) {
            $is_feature_active = (bool) Configuration::get('PS_CART_RULE_FEATURE_ACTIVE');
        }
        return $is_feature_active;
    }
    /**
     * @param bool $returnProducts
     * @param bool $displayError
     * @param bool $alreadyInCart
     *
     * @return array|bool|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function check_product_restrictions(Context $context, $return_products = false, $display_error = true, $already_in_cart = false)
    {
        $selected_products = [];
        // Check if the products chosen by the customer are usable with the cart rule
        if ($this->product_restriction) {
            $conn = Db::read_only();
            $product_rule_groups = $this->get_product_rule_groups();
            foreach ($product_rule_groups as $id_product_rule_group => $product_rule_group) {
                $eligible_products_list = [];
                if (isset($context->cart) && is_object($context->cart) && is_array($products = $context->cart->get_products())) {
                    foreach ($products as $product) {
                        $eligible_products_list[] = (int) $product['id_product'] . '-' . (int) $product['id_product_attribute'];
                    }
                }
                if (!count($eligible_products_list)) {
                    return !$display_error ? false : Tools::display_error('You cannot use this voucher in an empty cart');
                }
                $product_rules = $this->get_product_rules($id_product_rule_group);
                foreach ($product_rules as $product_rule) {
                    switch ($product_rule['type']) {
                        case 'attributes':
                            $cart_attributes = $conn->get_array((new Db_Query())->select('cp.`quantity`, cp.`id_product`, pac.`id_attribute`, cp.`id_product_attribute`')->from('cart_product', 'cp')->left_join('product_attribute_combination', 'pac', 'cp.`id_product_attribute` = pac.`id_product_attribute`')->where('cp.`id_cart` = ' . (int) $context->cart->id)->where('cp.`id_product` IN (' . implode(',', array_map(intval(...), $eligible_products_list)) . ')')->where('cp.`id_product_attribute` > 0'));
                            $count_matching_products = 0;
                            $matching_products_list = [];
                            foreach ($cart_attributes as $cart_attribute) {
                                if (in_array($cart_attribute['id_attribute'], $product_rule['values'])) {
                                    $count_matching_products += $cart_attribute['quantity'];
                                    if ($already_in_cart && $this->gift_product == $cart_attribute['id_product'] && $this->gift_product_attribute == $cart_attribute['id_product_attribute']) {
                                        --$count_matching_products;
                                    }
                                    $matching_products_list[] = $cart_attribute['id_product'] . '-' . $cart_attribute['id_product_attribute'];
                                }
                            }
                            if ($count_matching_products < $product_rule_group['quantity']) {
                                return !$display_error ? false : Tools::display_error('You cannot use this voucher with these products');
                            }
                            $eligible_products_list = static::array_uintersect($eligible_products_list, $matching_products_list);
                            break;
                        case 'products':
                            $cart_products = $conn->get_array((new Db_Query())->select('cp.`quantity`, cp.`id_product`')->from('cart_product', 'cp')->where('cp.`id_cart` = ' . (int) $context->cart->id)->where('cp.`id_product` IN (' . implode(',', array_map(intval(...), $eligible_products_list)) . ')'));
                            $count_matching_products = 0;
                            $matching_products_list = [];
                            foreach ($cart_products as $cart_product) {
                                if (in_array($cart_product['id_product'], $product_rule['values'])) {
                                    $count_matching_products += $cart_product['quantity'];
                                    if ($already_in_cart && $this->gift_product == $cart_product['id_product']) {
                                        --$count_matching_products;
                                    }
                                    $matching_products_list[] = $cart_product['id_product'] . '-0';
                                }
                            }
                            if ($count_matching_products < $product_rule_group['quantity']) {
                                return !$display_error ? false : Tools::display_error('You cannot use this voucher with these products');
                            }
                            $eligible_products_list = static::array_uintersect($eligible_products_list, $matching_products_list);
                            break;
                        case 'categories':
                            $cart_categories = $conn->get_array((new Db_Query())->select('cp.quantity, cp.`id_product`, cp.`id_product_attribute`, catp.`id_category`')->from('cart_product', 'cp')->left_join('category_product', 'catp', 'cp.`id_product` = catp.`id_product`')->where('cp.`id_cart` = ' . (int) $context->cart->id)->where('cp.`id_product` IN (' . implode(',', array_map(intval(...), $eligible_products_list)) . ')')->where('cp.`id_product` <> ' . (int) $this->gift_product));
                            $count_matching_products = 0;
                            $matching_products_list = [];
                            foreach ($cart_categories as $cart_category) {
                                if (in_array($cart_category['id_category'], $product_rule['values']) && !in_array($cart_category['id_product'] . '-' . $cart_category['id_product_attribute'], $matching_products_list)) {
                                    $count_matching_products += $cart_category['quantity'];
                                    $matching_products_list[] = $cart_category['id_product'] . '-' . $cart_category['id_product_attribute'];
                                }
                            }
                            if ($count_matching_products < $product_rule_group['quantity']) {
                                return !$display_error ? false : Tools::display_error('You cannot use this voucher with these products');
                            }
                            // Attribute id is not important for this filter in the global list, so the ids are replaced by 0
                            foreach ($matching_products_list as &$matching_product) {
                                $matching_product = preg_replace('/^([0-9]+)-[0-9]+$/', '$1-0', $matching_product);
                            }
                            $eligible_products_list = static::array_uintersect($eligible_products_list, $matching_products_list);
                            break;
                        case 'manufacturers':
                            $cart_manufacturers = $conn->get_array((new Db_Query())->select('cp.quantity, cp.`id_product`, p.`id_manufacturer`')->from('cart_product', 'cp')->left_join('product', 'p', 'cp.`id_product` = p.`id_product`')->where('cp.`id_cart` = ' . (int) $context->cart->id)->where('cp.`id_product` IN (' . implode(',', array_map(intval(...), $eligible_products_list)) . ')'));
                            $count_matching_products = 0;
                            $matching_products_list = [];
                            foreach ($cart_manufacturers as $cart_manufacturer) {
                                if (in_array($cart_manufacturer['id_manufacturer'], $product_rule['values'])) {
                                    $count_matching_products += $cart_manufacturer['quantity'];
                                    $matching_products_list[] = $cart_manufacturer['id_product'] . '-0';
                                }
                            }
                            if ($count_matching_products < $product_rule_group['quantity']) {
                                return !$display_error ? false : Tools::display_error('You cannot use this voucher with these products');
                            }
                            $eligible_products_list = static::array_uintersect($eligible_products_list, $matching_products_list);
                            break;
                        case 'suppliers':
                            $cart_suppliers = $conn->get_array((new Db_Query())->select('cp.`quantity`, cp.`id_product`, p.`id_supplier`')->from('cart_product', 'cp')->left_join('product', 'p', 'cp.`id_product` = p.`id_product`')->where('cp.`id_cart` = ' . (int) $context->cart->id)->where('cp.`id_product` IN (' . implode(',', array_map(intval(...), $eligible_products_list)) . ')'));
                            $count_matching_products = 0;
                            $matching_products_list = [];
                            foreach ($cart_suppliers as $cart_supplier) {
                                if (in_array($cart_supplier['id_supplier'], $product_rule['values'])) {
                                    $count_matching_products += $cart_supplier['quantity'];
                                    $matching_products_list[] = $cart_supplier['id_product'] . '-0';
                                }
                            }
                            if ($count_matching_products < $product_rule_group['quantity']) {
                                return !$display_error ? false : Tools::display_error('You cannot use this voucher with these products');
                            }
                            $eligible_products_list = static::array_uintersect($eligible_products_list, $matching_products_list);
                            break;
                    }
                    if (!count($eligible_products_list)) {
                        return !$display_error ? false : Tools::display_error('You cannot use this voucher with these products');
                    }
                }
                $selected_products = array_merge($selected_products, $eligible_products_list);
            }
        }
        if ($return_products) {
            return $selected_products;
        }
        return !$display_error;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_product_rule_groups()
    {
        if (!Validate::is_loaded_object($this) || $this->product_restriction == 0) {
            return [];
        }
        $product_rule_groups = [];
        $result = Db::read_only()->get_array((new Db_Query())->select('*')->from('cart_rule_product_rule_group')->where('`id_cart_rule` = ' . (int) $this->id));
        foreach ($result as $row) {
            if (!isset($product_rule_groups[$row['id_product_rule_group']])) {
                $product_rule_groups[$row['id_product_rule_group']] = ['id_product_rule_group' => $row['id_product_rule_group'], 'quantity' => $row['quantity']];
            }
            $product_rule_groups[$row['id_product_rule_group']]['product_rules'] = $this->get_product_rules($row['id_product_rule_group']);
        }
        return $product_rule_groups;
    }
    /**
     * @param int $idProductRuleGroup
     *
     * @return array ('type' => ? , 'values' => ?)
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_product_rules($id_product_rule_group)
    {
        if (!Validate::is_loaded_object($this) || $this->product_restriction == 0) {
            return [];
        }
        $product_rules = [];
        $results = Db::read_only()->get_array((new Db_Query())->select('*')->from('cart_rule_product_rule', 'pr')->left_join('cart_rule_product_rule_value', 'prv', 'pr.`id_product_rule` = prv.`id_product_rule`')->where('pr.`id_product_rule_group` = ' . (int) $id_product_rule_group));
        foreach ($results as $row) {
            if (!isset($product_rules[$row['id_product_rule']])) {
                $product_rules[$row['id_product_rule']] = ['type' => $row['type'], 'values' => []];
            }
            $product_rules[$row['id_product_rule']]['values'][] = $row['id_item'];
        }
        return $product_rules;
    }
    /**
     * @param array $array1
     * @param array $array2
     *
     * @return array
     */
    protected static function array_uintersect($array1, $array2)
    {
        $intersection = [];
        foreach ($array1 as $value1) {
            foreach ($array2 as $value2) {
                if (static::array_uintersect_compare($value1, $value2) == 0) {
                    $intersection[] = $value1;
                    break;
                }
            }
        }
        return $intersection;
    }
    /**
     * @param string $a
     * @param string $b
     *
     * @return int
     */
    protected static function array_uintersect_compare($a, $b)
    {
        if ($a == $b) {
            return 0;
        }
        $asplit = explode('-', $a);
        $bsplit = explode('-', $b);
        if ($asplit[0] == $bsplit[0] && (!(int) $asplit[1] || !(int) $bsplit[1])) {
            return 0;
        }
        return 1;
    }
    /**
     * @param string $name
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function cart_rule_exists($name)
    {
        if (!static::is_feature_active()) {
            return false;
        }
        return (bool) Db::read_only()->get_value((new Db_Query())->select('`id_cart_rule`')->from('cart_rule')->where('`code` = \'' . p_sql($name) . '\''));
    }
    /**
     * @param int $idCustomer
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function delete_by_id_customer($id_customer)
    {
        $return = true;
        $cart_rules = new Presta_Shop_Collection('CartRule');
        $cart_rules->where('id_customer', '=', $id_customer);
        foreach ($cart_rules as $cart_rule) {
            $return = $cart_rule->delete() && $return;
        }
        return $return;
    }
    /**
     * Make sure caches are empty
     * Must be called before calling multiple time getContextualValue()
     */
    public static function clean_cache(): void
    {
        static::$only_one_gift = [];
    }
    /**
     * @param Context|null $context
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function auto_remove_from_cart($context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        if (!static::is_feature_active() || !Validate::is_loaded_object($context->cart)) {
            return [];
        }
        static $errors = [];
        foreach ($context->cart->get_cart_rules() as $cart_rule) {
            /** @var CartRule $cartRuleObject */
            $cart_rule_object = $cart_rule['obj'];
            if ($error = $cart_rule_object->check_validity($context, true)) {
                $context->cart->remove_cart_rule($cart_rule_object->id);
                $context->cart->update();
                $errors[] = $error;
            }
        }
        return $errors;
    }
    /**
     * @throws PrestaShopException
     */
    public static function auto_add_to_cart(?Context $context = null): void
    {
        if ($context === null) {
            $context = Context::get_context();
        }
        if (!static::is_feature_active() || !Validate::is_loaded_object($context->cart)) {
            return;
        }
        $sql = '
		SELECT SQL_NO_CACHE cr.*
		FROM ' . _DB_PREFIX_ . 'cart_rule cr
		LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_shop crs ON cr.id_cart_rule = crs.id_cart_rule
		' . (!$context->customer->id && Group::is_feature_active() ? ' LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_group crg ON cr.id_cart_rule = crg.id_cart_rule' : '') . '
		LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_carrier crca ON cr.id_cart_rule = crca.id_cart_rule
		' . ($context->cart->id_carrier ? 'LEFT JOIN ' . _DB_PREFIX_ . 'carrier c ON (c.id_reference = crca.id_carrier AND c.deleted = 0)' : '') . '
		LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_country crco ON cr.id_cart_rule = crco.id_cart_rule
		WHERE cr.active = 1
		AND cr.code = ""
		AND cr.quantity > 0
		AND cr.date_from < "' . date('Y-m-d H:i:s') . '"
		AND cr.date_to > "' . date('Y-m-d H:i:s') . '"
		AND (
			cr.id_customer = 0
			' . ($context->customer->id ? 'OR cr.id_customer = ' . (int) $context->cart->id_customer : '') . '
		)
		AND (
			cr.`carrier_restriction` = 0
			' . ($context->cart->id_carrier ? 'OR c.id_carrier = ' . (int) $context->cart->id_carrier : '') . '
		)
		AND (
			cr.`shop_restriction` = 0
			' . (Shop::is_feature_active() && $context->shop->id ? 'OR crs.id_shop = ' . (int) $context->shop->id : '') . '
		)
		AND (
			cr.`group_restriction` = 0
			' . ($context->customer->id ? 'OR EXISTS (
				SELECT 1
				FROM `' . _DB_PREFIX_ . 'customer_group` cg
				INNER JOIN `' . _DB_PREFIX_ . 'cart_rule_group` crg ON cg.id_group = crg.id_group
				WHERE cr.`id_cart_rule` = crg.`id_cart_rule`
				AND cg.`id_customer` = ' . (int) $context->customer->id . '
				LIMIT 1
			)' : (Group::is_feature_active() ? 'OR crg.`id_group` = ' . (int) Configuration::get('PS_UNIDENTIFIED_GROUP') : '')) . '
		)
		AND (
			cr.`reduction_product` <= 0
			OR EXISTS (
				SELECT 1
				FROM `' . _DB_PREFIX_ . 'cart_product`
				WHERE `' . _DB_PREFIX_ . 'cart_product`.`id_product` = cr.`reduction_product` AND `id_cart` = ' . (int) $context->cart->id . '
			)
		)
		AND NOT EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'cart_cart_rule WHERE cr.id_cart_rule = ' . _DB_PREFIX_ . 'cart_cart_rule.id_cart_rule
																			AND id_cart = ' . (int) $context->cart->id . ')
		ORDER BY priority';
        $result = Db::read_only()->get_array($sql);
        if ($result) {
            $cart_rules = Object_Model::hydrate_collection('CartRule', $result);
            if ($cart_rules) {
                foreach ($cart_rules as $cart_rule) {
                    /** @var CartRule $cartRule */
                    if ($cart_rule->check_validity($context, false, false)) {
                        $context->cart->add_cart_rule($cart_rule->id);
                    }
                }
            }
        }
    }
    /**
     * Check if this cart rule can be applied
     *
     * @param bool $alreadyInCart Check if the voucher is already on the cart
     * @param bool $displayError Display error
     * @param bool $checkCarrier
     *
     * @return array|bool|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function check_validity(Context $context, $already_in_cart = false, $display_error = true, $check_carrier = true)
    {
        if (!static::is_feature_active()) {
            return false;
        }
        if (!$this->active) {
            return !$display_error ? false : Tools::display_error('This voucher is disabled');
        }
        if (!$this->quantity) {
            return !$display_error ? false : Tools::display_error('This voucher has already been used');
        }
        if (strtotime($this->date_from) > time()) {
            return !$display_error ? false : Tools::display_error('This voucher is not valid yet');
        }
        if (strtotime($this->date_to) < time()) {
            return !$display_error ? false : Tools::display_error('This voucher has expired');
        }
        $conn = Db::read_only();
        if ($context->cart->id_customer) {
            $quantity_used = $conn->get_value((new Db_Query())->select('COUNT(*)')->from('orders', 'o')->left_join('order_cart_rule', 'od', 'od.`id_order` = o.`id_order`')->where('o.`id_customer` = ' . (int) $context->cart->id_customer)->where('od.`id_cart_rule` = ' . (int) $this->id)->where('o.`current_state` != ' . (int) Configuration::get('PS_OS_ERROR')));
            if ($quantity_used + 1 > $this->quantity_per_user) {
                return !$display_error ? false : Tools::display_error('You cannot use this voucher anymore (usage limit reached)');
            }
        }
        // Get an intersection of the customer groups and the cart rule groups (if the customer is not logged in, the default group is Visitors)
        if ($this->group_restriction) {
            $id_cart_rule = (int) $conn->get_value((new Db_Query())->select('crg.`id_cart_rule`')->from('cart_rule_group', 'crg')->where('crg.`id_cart_rule` = ' . (int) $this->id)->where('crg.`id_group` ' . ($context->cart->id_customer ? 'IN (SELECT cg.id_group FROM ' . _DB_PREFIX_ . 'customer_group cg WHERE cg.id_customer = ' . (int) $context->cart->id_customer . ')' : '= ' . (int) Configuration::get('PS_UNIDENTIFIED_GROUP'))));
            if (!$id_cart_rule) {
                return !$display_error ? false : Tools::display_error('You cannot use this voucher');
            }
        }
        // Check if the customer delivery address is usable with the cart rule
        if ($this->country_restriction) {
            if (!$context->cart->id_address_delivery) {
                return !$display_error ? false : Tools::display_error('You must choose a delivery address before applying this voucher to your order');
            }
            $id_cart_rule = (int) $conn->get_value((new Db_Query())->select('crc.`id_cart_rule`')->from('cart_rule_country', 'crc')->where('crc.`id_cart_rule` = ' . (int) $this->id)->where('crc.`id_country`  = (SELECT a.id_country FROM ' . _DB_PREFIX_ . 'address a WHERE a.id_address = ' . (int) $context->cart->id_address_delivery . ' LIMIT 1)'));
            if (!$id_cart_rule) {
                return !$display_error ? false : Tools::display_error('You cannot use this voucher in your country of delivery');
            }
        }
        // Check if the carrier chosen by the customer is usable with the cart rule
        if ($this->carrier_restriction && $check_carrier) {
            if (!$context->cart->id_carrier) {
                return !$display_error ? false : Tools::display_error('You must choose a carrier before applying this voucher to your order');
            }
            $id_cart_rule = (int) $conn->get_value((new Db_Query())->select('crc.`id_cart_rule`')->from('cart_rule_carrier', 'crc')->inner_join('carrier', 'c', 'c.`id_reference` = crc.`id_carrier` AND c.`deleted` = 0')->where('crc.`id_cart_rule` = ' . (int) $this->id)->where('c.`id_carrier` = ' . (int) $context->cart->id_carrier));
            if (!$id_cart_rule) {
                return !$display_error ? false : Tools::display_error('You cannot use this voucher with this carrier');
            }
        }
        // Check if the cart rules appliy to the shop browsed by the customer
        if ($this->shop_restriction && $context->shop->id && Shop::is_feature_active()) {
            $id_cart_rule = (int) $conn->get_value((new Db_Query())->select('crs.`id_cart_rule`')->from('cart_rule_shop', 'crs')->where('crs.`id_cart_rule` = ' . (int) $this->id)->where('crs.`id_shop` = ' . (int) $context->shop->id));
            if (!$id_cart_rule) {
                return !$display_error ? false : Tools::display_error('You cannot use this voucher');
            }
        }
        // Check if the products chosen by the customer are usable with the cart rule
        if ($this->product_restriction) {
            $r = $this->check_product_restrictions($context, false, $display_error, $already_in_cart);
            if ($r !== false && $display_error) {
                return $r;
            }
            if (!$r && !$display_error) {
                return false;
            }
        }
        // Check if the cart rule is only usable by a specific customer, and if the current customer is the right one
        if ($this->id_customer && $context->cart->id_customer != $this->id_customer) {
            if (!Context::get_context()->customer->is_logged()) {
                return !$display_error ? false : Tools::display_error('You cannot use this voucher') . ' - ' . Tools::display_error('Please log in first');
            }
            return !$display_error ? false : Tools::display_error('You cannot use this voucher');
        }
        if ($this->minimum_amount && $check_carrier) {
            // Minimum amount is converted to the contextual currency
            $minimum_amount = $this->minimum_amount;
            if ($this->minimum_amount_currency != Context::get_context()->currency->id) {
                $minimum_amount = Tools::convert_price_full($minimum_amount, new Currency($this->minimum_amount_currency), Context::get_context()->currency);
            }
            $cart_total = $context->cart->get_order_total($this->minimum_amount_tax, Cart::ONLY_PRODUCTS);
            if ($this->minimum_amount_shipping) {
                $cart_total += $context->cart->get_order_total($this->minimum_amount_tax, Cart::ONLY_SHIPPING);
            }
            $products = $context->cart->get_products();
            $cart_rules = $context->cart->get_cart_rules();
            // Check if the products chosen by the customer are usable with the cart rule to calculate if minimum amount is reached.
            if ($this->product_restriction && $this->minimum_amount_product_restriction) {
                $cart_total = 0;
                $selected_products = $this->check_product_restrictions($context, true);
                foreach ($products as $product) {
                    if (in_array($product['id_product'] . '-' . $product['id_product_attribute'], $selected_products)) {
                        if ($this->minimum_amount_tax) {
                            $cart_total = $cart_total + $product['price_with_reduction_without_tax'] * $product['cart_quantity'];
                        } else {
                            $cart_total = $cart_total + $product['price'] * $product['cart_quantity'];
                        }
                    }
                }
            }
            foreach ($cart_rules as $cart_rule) {
                if ($cart_rule['gift_product']) {
                    foreach ($products as &$product) {
                        if (empty($product['gift']) && $product['id_product'] == $cart_rule['gift_product'] && $product['id_product_attribute'] == $cart_rule['gift_product_attribute']) {
                            if ($this->minimum_amount_tax) {
                                $cart_total = $cart_total - $product['price_wt'];
                            } else {
                                $cart_total = $cart_total - $product['price'];
                            }
                        }
                    }
                }
            }
            if ($cart_total < $minimum_amount) {
                return !$display_error ? false : Tools::display_error('You have not reached the minimum amount required to use this voucher');
            }
        }
        /* This loop checks:
               - if the voucher is already in the cart
               - if a non compatible voucher is in the cart
               - if there are products in the cart (gifts excluded)
               Important note: this MUST be the last check, because if the tested cart rule has priority over a non combinable one in the cart, we will switch them
           */
        $nb_products = Cart::get_nb_products($context->cart->id);
        if ($check_carrier) {
            foreach ($context->cart->get_cart_rules() as $other_cart_rule) {
                if ($other_cart_rule['id_cart_rule'] == $this->id && !$already_in_cart) {
                    return !$display_error ? false : Tools::display_error('This voucher is already in your cart');
                }
                if ($other_cart_rule['gift_product']) {
                    --$nb_products;
                }
                if ($this->cart_rule_restriction && $other_cart_rule['cart_rule_restriction'] && $other_cart_rule['id_cart_rule'] != $this->id) {
                    $combinable = $conn->get_value('
					SELECT id_cart_rule_1
					FROM ' . _DB_PREFIX_ . 'cart_rule_combination
					WHERE (id_cart_rule_1 = ' . (int) $this->id . ' AND id_cart_rule_2 = ' . (int) $other_cart_rule['id_cart_rule'] . ')
					OR (id_cart_rule_2 = ' . (int) $this->id . ' AND id_cart_rule_1 = ' . (int) $other_cart_rule['id_cart_rule'] . ')');
                    if (!$combinable) {
                        $cart_rule = new Cart_Rule((int) $other_cart_rule['id_cart_rule'], $context->cart->id_lang);
                        // The cart rules are not combinable and the cart rule currently in the cart has priority over the one tested
                        if ($cart_rule->priority <= $this->priority) {
                            return !$display_error ? false : Tools::display_error('This voucher is not combinable with an other voucher already in your cart:') . ' ' . $cart_rule->name;
                        }
                        $context->cart->remove_cart_rule($cart_rule->id);
                    }
                }
            }
        }
        if (!$nb_products) {
            return !$display_error ? false : Tools::display_error('Cart is empty');
        }
        if (!$display_error) {
            return true;
        }
        return false;
    }
    /**
     * @param string $type
     * @param int|int[] $list
     *
     * @return bool
     *
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public static function clean_product_rule_integrity($type, $list)
    {
        // Type must be available in the 'type' enum of the table cart_rule_product_rule
        if (!in_array($type, ['products', 'categories', 'attributes', 'manufacturers', 'suppliers'])) {
            return false;
        }
        // This check must not be removed because this var is used a few lines below
        $list = is_array($list) ? implode(',', array_map(intval(...), $list)) : (int) $list;
        if (!preg_match('/^[0-9,]+$/', (string) $list)) {
            return false;
        }
        // Delete associated restrictions on cart rules
        $conn = Db::get_instance();
        $conn->execute('
		DELETE crprv
		FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule` crpr
		LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule_product_rule_value` crprv ON crpr.`id_product_rule` = crprv.`id_product_rule`
		WHERE crpr.`type` = "' . p_sql($type) . '"
		AND crprv.`id_item` IN (' . $list . ')');
        // $list is checked a few lines above
        // Delete the product rules that does not have any values
        if ($conn->Affected_Rows() > 0) {
            $conn->delete('cart_rule_product_rule', 'NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule_value`
																							WHERE `' . _DB_PREFIX_ . 'cart_rule_product_rule`.`id_product_rule` = `' . _DB_PREFIX_ . 'cart_rule_product_rule_value`.`id_product_rule`)');
        }
        // If the product rules were the only conditions of a product rule group, delete the product rule group
        if ($conn->Affected_Rows() > 0) {
            $conn->delete('cart_rule_product_rule_group', 'NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule`
																						WHERE `' . _DB_PREFIX_ . 'cart_rule_product_rule`.`id_product_rule_group` = `' . _DB_PREFIX_ . 'cart_rule_product_rule_group`.`id_product_rule_group`)');
        }
        // If the product rule group were the only restrictions of a cart rule, update de cart rule restriction cache
        if ($conn->Affected_Rows() > 0) {
            $conn->execute('
				UPDATE `' . _DB_PREFIX_ . 'cart_rule` cr
				LEFT JOIN `' . _DB_PREFIX_ . 'cart_rule_product_rule_group` crprg ON cr.id_cart_rule = crprg.id_cart_rule
				SET product_restriction = IF(crprg.id_product_rule_group IS NULL, 0, 1)');
        }
        return true;
    }
    /**
     * @param string $name
     * @param int $idLang
     *
     * @param bool $extended
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_carts_rule_by_code($name, $id_lang, $extended = false)
    {
        $sql_base = 'SELECT cr.*, crl.*
						FROM ' . _DB_PREFIX_ . 'cart_rule cr
						LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_lang crl ON (cr.id_cart_rule = crl.id_cart_rule AND crl.id_lang = ' . (int) $id_lang . ')';
        $conn = Db::read_only();
        if ($extended) {
            return $conn->get_array('(' . $sql_base . ' WHERE code LIKE \'%' . p_sql($name) . '%\') UNION (' . $sql_base . ' WHERE name LIKE \'%' . p_sql($name) . '%\')');
        }
        return $conn->get_array($sql_base . ' WHERE code LIKE \'%' . p_sql($name) . '%\'');
    }
    /**
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if (!$this->reduction_currency) {
            $this->reduction_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        }
        if (!parent::add($auto_date, $null_values)) {
            return false;
        }
        Configuration::update_global_value('PS_CART_RULE_FEATURE_ACTIVE', '1');
        return true;
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
        Cache::clean('getContextualValue_' . $this->id . '_*');
        if (!$this->reduction_currency) {
            $this->reduction_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        }
        return parent::update($null_values);
    }
    /**
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }
        Configuration::update_global_value('PS_CART_RULE_FEATURE_ACTIVE', static::is_currently_used($this->def['table'], true));
        $conn = Db::get_instance();
        $r = $conn->delete('cart_cart_rule', '`id_cart_rule` = ' . (int) $this->id);
        $r = $conn->delete('cart_rule_carrier', '`id_cart_rule` = ' . (int) $this->id) && $r;
        $r = $conn->delete('cart_rule_shop', '`id_cart_rule` = ' . (int) $this->id) && $r;
        $r = $conn->delete('cart_rule_group', '`id_cart_rule` = ' . (int) $this->id) && $r;
        $r = $conn->delete('cart_rule_country', '`id_cart_rule` = ' . (int) $this->id) && $r;
        $r = $conn->delete('cart_rule_combination', '`id_cart_rule_1` = ' . (int) $this->id . ' OR `id_cart_rule_2` = ' . (int) $this->id) && $r;
        $r = $conn->delete('cart_rule_product_rule_group', '`id_cart_rule` = ' . (int) $this->id) && $r;
        $r = $conn->delete('cart_rule_product_rule', 'NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule_group`
			WHERE `' . _DB_PREFIX_ . 'cart_rule_product_rule`.`id_product_rule_group` = `' . _DB_PREFIX_ . 'cart_rule_product_rule_group`.`id_product_rule_group`)') && $r;
        return $conn->delete('cart_rule_product_rule_value', 'NOT EXISTS (SELECT 1 FROM `' . _DB_PREFIX_ . 'cart_rule_product_rule`
			WHERE `' . _DB_PREFIX_ . 'cart_rule_product_rule_value`.`id_product_rule` = `' . _DB_PREFIX_ . 'cart_rule_product_rule`.`id_product_rule`)') && $r;
    }
    /**
     * @param int $idCustomer
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function used_by_customer($id_customer)
    {
        return (bool) Db::read_only()->get_value((new Db_Query())->select('`id_cart_rule`')->from('order_cart_rule', 'ocr')->left_join('orders', 'o', 'ocr.`id_order` = o.`id_order`')->where('ocr.`id_cart_rule` = ' . (int) $this->id)->where('o.`id_customer` = ' . (int) $id_customer));
    }
    /**
     * The reduction value is POSITIVE
     *
     * @param bool $useTax
     * @param int|null $filter
     * @param array|null $package
     * @param bool $useCache Allow using cache to avoid multiple free gift using multishipping
     *
     * @return float|int|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_contextual_value($use_tax, ?Context $context = null, $filter = null, $package = null, $use_cache = true)
    {
        if (!static::is_feature_active()) {
            return 0;
        }
        if (!$context) {
            $context = Context::get_context();
        }
        if (!$filter) {
            $filter = static::FILTER_ACTION_ALL;
        }
        $round_type = (int) Configuration::get('PS_ROUND_TYPE');
        $display_decimals = $context->currency->get_display_precision();
        $all_products = $context->cart->get_products();
        $package_products = is_null($package) ? $all_products : $package['products'];
        $reduction_value = 0;
        $cache_id = 'getContextualValue_' . (int) $this->id . '_' . (int) $use_tax . '_' . (int) $context->cart->id . '_' . (int) $filter;
        foreach ($package_products as $product) {
            $cache_id .= '_' . (int) $product['id_product'] . '_' . (int) $product['id_product_attribute'] . (isset($product['in_stock']) ? '_' . (int) $product['in_stock'] : '');
        }
        if (Cache::is_stored($cache_id)) {
            return Cache::retrieve($cache_id);
        }
        $all_cart_rules_ids = $context->cart->get_ordered_cart_rules_ids();
        $cart_amount_tax_included = $context->cart->get_order_total(true, Cart::ONLY_PRODUCTS);
        $cart_amount_tax_excluded = $context->cart->get_order_total(false, Cart::ONLY_PRODUCTS);
        // Free shipping on selected carriers
        if ($this->free_shipping && in_array($filter, [static::FILTER_ACTION_ALL, static::FILTER_ACTION_ALL_NOCAP, static::FILTER_ACTION_SHIPPING])) {
            if (!$this->carrier_restriction) {
                $reduction_value += $context->cart->get_order_total($use_tax, Cart::ONLY_SHIPPING, is_null($package) ? null : $package['products'], is_null($package) ? null : $package['id_carrier']);
            } else {
                $data = Db::read_only()->get_array((new Db_Query())->select('crc.`id_cart_rule`, c.`id_carrier`')->from('cart_rule_carrier', 'crc')->inner_join('carrier', 'c', 'c.`id_reference` = crc.`id_carrier` AND c.`deleted` = 0')->where('crc.`id_cart_rule` = ' . (int) $this->id)->where('c.`id_carrier` = ' . (int) $context->cart->id_carrier));
                if ($data) {
                    foreach ($data as $cart_rule) {
                        $reduction_value += $context->cart->get_carrier_cost((int) $cart_rule['id_carrier'], $use_tax, $context->country);
                    }
                }
            }
        }
        if (in_array($filter, [static::FILTER_ACTION_ALL, static::FILTER_ACTION_ALL_NOCAP, static::FILTER_ACTION_REDUCTION])) {
            // Discount (%) on the whole order
            if ($this->reduction_percent && $this->apply_discount_to_order_without_shipping()) {
                // Do not give a reduction on free products!
                $order_total = $context->cart->get_order_total($use_tax, Cart::ONLY_PRODUCTS, $package_products);
                foreach ($context->cart->get_cart_rules(static::FILTER_ACTION_GIFT) as $cart_rule) {
                    $reduction = $cart_rule['obj']->get_contextual_value($use_tax, $context, static::FILTER_ACTION_GIFT, $package);
                    if ($round_type === Order::ROUND_ITEM) {
                        $reduction = round($reduction, $display_decimals);
                    }
                    $order_total -= $reduction;
                }
                $reduction_value += Tools::round_price($order_total * $this->reduction_percent / 100);
            }
            // Discount (%) on a specific product
            if ($this->reduction_percent && $this->apply_discount_to_specific_product()) {
                foreach ($package_products as $product) {
                    if ((int) $product['id_product'] === $this->get_specific_product_id()) {
                        $reduction = $use_tax ? (float) $product['total_wt'] : (float) $product['total'];
                        $reduction_value += Tools::round_price($reduction * $this->reduction_percent / 100);
                    }
                }
            }
            // Discount (%) on the cheapest product
            if ($this->reduction_percent && $this->apply_discount_to_cheapest_product_from_selection()) {
                $min_price = false;
                $cheapest_product = null;
                $selected_products = $this->check_product_restrictions($context, true);
                if (is_array($selected_products)) {
                    // find the cheapest product price
                    foreach ($all_products as $product) {
                        $product_key = (int) $product['id_product'] . '-' . (int) $product['id_product_attribute'];
                        if (in_array($product_key, $selected_products)) {
                            $price = $use_tax ? (float) $product['price_wt'] : (float) $product['price'];
                            if ($price > 0 && ($min_price === false || $min_price > $price)) {
                                $min_price = $price;
                                $cheapest_product = $product_key;
                            }
                        }
                    }
                    // Check if the cheapest product is in the package
                    if ($cheapest_product) {
                        foreach ($package_products as $product) {
                            $product_key = (int) $product['id_product'] . '-' . (int) $product['id_product_attribute'];
                            if ($product_key === $cheapest_product) {
                                $reduction_value += Tools::round_price($min_price * $this->reduction_percent / 100);
                                break;
                            }
                        }
                    }
                }
            }
            // Discount (%) on the selection of products
            if ($this->reduction_percent && $this->apply_discount_to_selected_products()) {
                $selected_products_reduction = 0;
                $selected_products = $this->check_product_restrictions($context, true);
                if (is_array($selected_products)) {
                    foreach ($package_products as $product) {
                        $product_key = (int) $product['id_product'] . '-' . (int) $product['id_product_attribute'];
                        if (in_array($product_key, $selected_products)) {
                            $price = $use_tax ? (float) $product['total_wt'] : (float) $product['total'];
                            $selected_products_reduction += $price;
                        }
                    }
                    $reduction_value += Tools::round_price($selected_products_reduction * $this->reduction_percent / 100);
                }
            }
            // Discount (¤)
            if ($this->reduction_amount > 0) {
                $prorata = 1;
                if (!is_null($package) && count($all_products)) {
                    $total_products = $context->cart->get_order_total($use_tax, Cart::ONLY_PRODUCTS);
                    if ($total_products) {
                        $prorata = $context->cart->get_order_total($use_tax, Cart::ONLY_PRODUCTS, $package['products']) / $total_products;
                    }
                }
                $reduction_amount = $this->reduction_amount;
                $voucher_currency = new Currency($this->reduction_currency);
                // First we convert the voucher value to the default currency.
                $reduction_amount = Tools::convert_price($reduction_amount, $voucher_currency, false);
                // Then we convert the voucher value to the cart currency.
                $reduction_amount = Tools::convert_price($reduction_amount, $context->currency, true);
                // If it has the same tax application that you need, then it's the right value, whatever the product!
                if ($this->reduction_tax == $use_tax) {
                    // The reduction cannot exceed the products total, except when we do not want it to be limited (for the partial use calculation)
                    if ($filter != static::FILTER_ACTION_ALL_NOCAP) {
                        $cart_amount = $context->cart->get_order_total($use_tax, Cart::ONLY_PRODUCTS);
                        $reduction_amount = min($reduction_amount, $cart_amount);
                    }
                    $reduction_value += $prorata * $reduction_amount;
                } else {
                    if ($this->apply_discount_to_specific_product()) {
                        foreach ($context->cart->get_products() as $product) {
                            if ((int) $product['id_product'] === $this->get_specific_product_id()) {
                                $product_price_tax_included = $product['price_wt'];
                                $product_price_tax_excluded = $product['price'];
                                $product_vat_amount = $product_price_tax_included - $product_price_tax_excluded;
                                if ($product_vat_amount == 0 || $product_price_tax_excluded == 0) {
                                    $product_vat_rate = 0;
                                } else {
                                    $product_vat_rate = $product_vat_amount / $product_price_tax_excluded;
                                }
                                if ($this->reduction_tax && !$use_tax) {
                                    $reduction_value += round($prorata * $reduction_amount / (1 + $product_vat_rate), _TB_PRICE_DATABASE_PRECISION_);
                                } elseif (!$this->reduction_tax && $use_tax) {
                                    $reduction_value += round($prorata * $reduction_amount * (1 + $product_vat_rate), _TB_PRICE_DATABASE_PRECISION_);
                                }
                            }
                        }
                    } elseif ($this->apply_discount_to_order_without_shipping()) {
                        $cart_amount_tax_excluded = null;
                        $cart_amount_tax_included = null;
                        $cart_average_vat_rate = $context->cart->get_average_products_tax_rate($cart_amount_tax_excluded, $cart_amount_tax_included);
                        // The reduction cannot exceed the products total, except when we do not want it to be limited (for the partial use calculation)
                        if ($filter != static::FILTER_ACTION_ALL_NOCAP) {
                            $reduction_amount = min($reduction_amount, $this->reduction_tax ? $cart_amount_tax_included : $cart_amount_tax_excluded);
                        }
                        if ($this->reduction_tax && !$use_tax) {
                            $reduction_value += round($prorata * $reduction_amount / (1 + $cart_average_vat_rate), _TB_PRICE_DATABASE_PRECISION_);
                        } elseif (!$this->reduction_tax && $use_tax) {
                            $reduction_value += round($prorata * $reduction_amount * (1 + $cart_average_vat_rate), _TB_PRICE_DATABASE_PRECISION_);
                        }
                    }
                    /*
                     * Reduction on the cheapest or on the selection is not really meaningful and has been disabled in the backend
                     * Please keep this code, so it won't be considered as a bug
                     * elseif ($this->reduction_product == -1)
                     * elseif ($this->reduction_product == -2)
                     */
                }
                // Take care of the other cart rules values if the filter allow it
                if ($filter != static::FILTER_ACTION_ALL_NOCAP) {
                    // Cart values
                    $cart = Context::get_context()->cart;
                    if (!Validate::is_loaded_object($cart)) {
                        $cart = new Cart();
                    }
                    $cart_average_vat_rate = $cart->get_average_products_tax_rate();
                    $current_cart_amount = $use_tax ? $cart_amount_tax_included : $cart_amount_tax_excluded;
                    foreach ($all_cart_rules_ids as $current_cart_rule_id) {
                        if ((int) $current_cart_rule_id['id_cart_rule'] == (int) $this->id) {
                            break;
                        }
                        $previous_cart_rule = new Cart_Rule((int) $current_cart_rule_id['id_cart_rule']);
                        $previous_reduction_amount = $previous_cart_rule->reduction_amount;
                        if ($previous_cart_rule->reduction_tax && !$use_tax) {
                            $previous_reduction_amount = round($previous_reduction_amount * $prorata / (1 + $cart_average_vat_rate), _TB_PRICE_DATABASE_PRECISION_);
                        } elseif (!$previous_cart_rule->reduction_tax && $use_tax) {
                            $previous_reduction_amount = round($previous_reduction_amount * $prorata * (1 + $cart_average_vat_rate), _TB_PRICE_DATABASE_PRECISION_);
                        }
                        $current_cart_amount = max($current_cart_amount - (float) $previous_reduction_amount, 0);
                    }
                    $reduction_value = min($reduction_value, $current_cart_amount);
                }
            }
            $max_reduction = (float) $this->reduction_max;
            if ($max_reduction > 0) {
                // convert max reduction amount to cart currency.
                $max_reduciton_currency = new Currency($this->reduction_max_currency);
                // First we convert the voucher value to the default currency.
                $max_reduction = Tools::convert_price($max_reduction, $max_reduciton_currency, false);
                // Then we convert the voucher value to the cart currency.
                $max_reduction = Tools::convert_price($max_reduction, $context->currency, true);
                if ($this->reduction_max_tax && !$use_tax) {
                    // max reduction amount includes tax, but need to calculate amount without tax
                    $cart_average_vat_rate = $context->cart->get_average_products_tax_rate();
                    $max_reduction = Tools::round_price($max_reduction / (1 + $cart_average_vat_rate));
                } elseif (!$this->reduction_max_tax && $use_tax) {
                    // max reduction amount is without tax tax, but need to calculate amount with tax
                    $cart_average_vat_rate = $context->cart->get_average_products_tax_rate();
                    $max_reduction = Tools::round_price($max_reduction * (1 + $cart_average_vat_rate));
                }
                $reduction_value = min($reduction_value, $max_reduction);
            }
            if ($round_type === Order::ROUND_LINE) {
                $reduction_value = Tools::ps_round($reduction_value, $display_decimals);
            }
        }
        // Free gift
        if ((int) $this->gift_product && in_array($filter, [static::FILTER_ACTION_ALL, static::FILTER_ACTION_ALL_NOCAP, static::FILTER_ACTION_GIFT])) {
            $id_address = is_null($package) ? 0 : $package['id_address'];
            foreach ($package_products as $product) {
                if (!($product['id_product'] == $this->gift_product)) {
                    continue;
                }
                if (!($product['id_product_attribute'] == $this->gift_product_attribute || !(int) $this->gift_product_attribute)) {
                    continue;
                }
                // The free gift coupon must be applied to one product only (needed for multi-shipping which manage multiple product lists)
                if (!(!isset(static::$only_one_gift[$this->id . '-' . $this->gift_product]) || static::$only_one_gift[$this->id . '-' . $this->gift_product] == $id_address || static::$only_one_gift[$this->id . '-' . $this->gift_product] == 0 || $id_address == 0 || !$use_cache)) {
                    continue;
                }
                $reduction_value += $use_tax ? $product['price_wt'] : $product['price'];
                if ($use_cache && (!isset(static::$only_one_gift[$this->id . '-' . $this->gift_product]) || static::$only_one_gift[$this->id . '-' . $this->gift_product] == 0)) {
                    static::$only_one_gift[$this->id . '-' . $this->gift_product] = $id_address;
                }
                break;
            }
        }
        Cache::store($cache_id, $reduction_value);
        return $reduction_value;
    }
    /* When an entity associated to a product rule (product, category, attribute, supplier, manufacturer...) is deleted, the product rules must be updated */
    /**
     * @param string $type
     * @param bool $activeOnly
     * @param bool $i18n
     * @param int $offset
     * @param int $limit
     * @param string $searchCartRuleName
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public function get_associated_restrictions($type, $active_only, $i18n, $offset = null, $limit = null, $search_cart_rule_name = '')
    {
        $array = ['selected' => [], 'unselected' => []];
        if (!in_array($type, ['country', 'carrier', 'group', 'cart_rule', 'shop'])) {
            return false;
        }
        $shop_list = '';
        if ($type == 'shop') {
            $shops = Context::get_context()->employee->get_associated_shops();
            if (count($shops)) {
                $shop_list = ' AND t.id_shop IN (' . implode(',', array_map(intval(...), $shops)) . ') ';
            }
        }
        if ($offset !== null && $limit !== null) {
            $sql_limit = ' LIMIT ' . (int) $offset . ', ' . (int) ($limit + 1);
        } else {
            $sql_limit = '';
        }
        if (!Validate::is_loaded_object($this) || $this->{$type . '_restriction'} == 0) {
            $array['selected'] = Db::read_only()->get_array('
			SELECT t.*' . ($i18n ? ', tl.*' : '') . ', 1 as selected
			FROM `' . _DB_PREFIX_ . $type . '` t
			' . ($i18n ? 'LEFT JOIN `' . _DB_PREFIX_ . $type . '_lang` tl ON (t.id_' . $type . ' = tl.id_' . $type . ' AND tl.id_lang = ' . (int) Context::get_context()->language->id . ')' : '') . '
			WHERE 1
			' . ($active_only ? 'AND t.active = 1' : '') . '
			' . (in_array($type, ['carrier', 'shop']) ? ' AND t.deleted = 0' : '') . '
			' . ($type == 'cart_rule' ? 'AND t.id_cart_rule != ' . (int) $this->id : '') . $shop_list . (in_array($type, ['carrier', 'shop']) ? ' ORDER BY t.name ASC ' : '') . (in_array($type, ['country', 'group', 'cart_rule']) && $i18n ? ' ORDER BY tl.name ASC ' : '') . $sql_limit);
        } else if ($type == 'cart_rule') {
            $array = $this->get_cart_rule_combinations($offset, $limit, $search_cart_rule_name);
        } else {
            $resource = Db::read_only()->get_array('
				SELECT t.*' . ($i18n ? ', tl.*' : '') . ', IF(crt.id_' . $type . ' IS NULL, 0, 1) as selected
				FROM `' . _DB_PREFIX_ . $type . '` t
				' . ($i18n ? 'LEFT JOIN `' . _DB_PREFIX_ . $type . '_lang` tl ON (t.id_' . $type . ' = tl.id_' . $type . ' AND tl.id_lang = ' . (int) Context::get_context()->language->id . ')' : '') . '
				LEFT JOIN (SELECT id_' . $type . ' FROM `' . _DB_PREFIX_ . 'cart_rule_' . $type . '` WHERE id_cart_rule = ' . (int) $this->id . ') crt ON t.id_' . ($type == 'carrier' ? 'reference' : $type) . ' = crt.id_' . $type . '
				WHERE 1 ' . ($active_only ? ' AND t.active = 1' : '') . $shop_list . (in_array($type, ['carrier', 'shop']) ? ' AND t.deleted = 0' : '') . (in_array($type, ['carrier', 'shop']) ? ' ORDER BY t.name ASC ' : '') . (in_array($type, ['country', 'group', 'cart_rule']) && $i18n ? ' ORDER BY tl.name ASC ' : '') . $sql_limit);
            foreach ($resource as $row) {
                $array[$row['selected'] || $this->{$type . '_restriction'} == 0 ? 'selected' : 'unselected'][] = $row;
            }
        }
        return $array;
    }
    /**
     * Find the cheapest product
     *
     * @param array $package
     *
     * @return string|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function find_cheapest_product($package)
    {
        $context = Context::get_context();
        $cheapest_product = null;
        $all_products = $package['products'];
        if ($this->reduction_percent && $this->apply_discount_to_cheapest_product_from_selection()) {
            $min_price = false;
            $selected_products = $this->check_product_restrictions($context, true);
            foreach ($all_products as $product) {
                if (!is_array($selected_products)) {
                    continue;
                }
                if (!in_array($product['id_product'] . '-' . $product['id_product_attribute'], $selected_products) && !in_array($product['id_product'] . '-0', $selected_products)) {
                    continue;
                }
                $price = $product['price'];
                if ($price > 0 && ($min_price === false || $min_price > $price)) {
                    $min_price = $price;
                    $cheapest_product = $product['id_product'] . '-' . $product['id_product_attribute'];
                }
            }
        }
        return $cheapest_product;
    }
    /**
     * @param int $offset
     * @param int $limit
     * @param string $search
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function get_cart_rule_combinations($offset = null, $limit = null, $search = '')
    {
        $array = [];
        if ($offset !== null && $limit !== null) {
            $sql_limit = ' LIMIT ' . (int) $offset . ', ' . (int) ($limit + 1);
        } else {
            $sql_limit = '';
        }
        $conn = Db::read_only();
        $array['selected'] = $conn->get_array('
		SELECT cr.*, crl.*, 1 AS selected
		FROM ' . _DB_PREFIX_ . 'cart_rule cr
		LEFT JOIN ' . _DB_PREFIX_ . 'cart_rule_lang crl ON (cr.id_cart_rule = crl.id_cart_rule AND crl.id_lang = ' . (int) Context::get_context()->language->id . ')
		WHERE cr.id_cart_rule != ' . (int) $this->id . ($search ? ' AND crl.name LIKE "%' . p_sql($search) . '%"' : '') . '
		AND (
			cr.cart_rule_restriction = 0
			OR EXISTS (
				SELECT 1
				FROM ' . _DB_PREFIX_ . 'cart_rule_combination
				WHERE cr.id_cart_rule = ' . _DB_PREFIX_ . 'cart_rule_combination.id_cart_rule_1 AND ' . (int) $this->id . ' = id_cart_rule_2
			)
			OR EXISTS (
				SELECT 1
				FROM ' . _DB_PREFIX_ . 'cart_rule_combination
				WHERE cr.id_cart_rule = ' . _DB_PREFIX_ . 'cart_rule_combination.id_cart_rule_2 AND ' . (int) $this->id . ' = id_cart_rule_1
			)
		) ORDER BY cr.id_cart_rule' . $sql_limit);
        $array['unselected'] = $conn->get_array((new Db_Query())->select('cr.*, crl.*, 1 AS `selected`')->from('cart_rule', 'cr')->inner_join('cart_rule_lang', 'crl', 'cr.`id_cart_rule` = crl.`id_cart_rule` AND crl.`id_lang` = ' . (int) Context::get_context()->language->id)->left_join('cart_rule_combination', 'crc1', 'cr.`id_cart_rule` = crc1.`id_cart_rule_1` AND crc1.`id_cart_rule_2` = ' . (int) $this->id)->left_join('cart_rule_combination', 'crc2', 'cr.`id_cart_rule` = crc2.`id_cart_rule_2` AND crc2.`id_cart_rule_1` = ' . (int) $this->id)->where('cr.`cart_rule_restriction` = 1')->where('cr.`id_cart_rule` != ' . (int) $this->id)->where($search ? 'crl.`name` LIKE "%' . p_sql($search) . '%"' : '')->where('crc1.`id_cart_rule_1` IS NULL')->where('crc2.`id_cart_rule_1` IS NULL')->order_by('cr.`id_cart_rule`')->limit($limit, $offset));
        return $array;
    }
    public function apply_discount_to_order_without_shipping(): bool
    {
        return (int) $this->reduction_product === static::APPLY_DISCOUNT_TO_ORDER_WITHOUT_SHIPPING;
    }
    public function apply_discount_to_cheapest_product_from_selection(): bool
    {
        return (int) $this->reduction_product === static::APPLY_DISCOUNT_TO_CHEAPEST_PRODUCT_FROM_SELECTION;
    }
    public function apply_discount_to_selected_products(): bool
    {
        return (int) $this->reduction_product === static::APPLY_DISCOUNT_TO_SELECTED_PRODUCTS;
    }
    public function apply_discount_to_specific_product(): bool
    {
        return (int) $this->reduction_product > 0;
    }
    public function get_specific_product_id(): int
    {
        if ($this->apply_discount_to_specific_product()) {
            return (int) $this->reduction_product;
        }
        return 0;
    }
    /**
     * Returns true, if this cart rule is a special system cart rule generated for selected cheapest
     * product during cart-to-order conversion
     */
    public function is_cheapest_product_system_rule(): bool
    {
        $object = json_decode((string) $this->description);
        return is_object($object) && isset($object->type) && $object->type === static::SYSTEM_RULE_CHEAPEST_PRODUCT && isset($object->id_product);
    }
    /**
     * Mark this cart rule as a special system rule for cheapest product from selection
     */
    public function set_cheapest_product_system_rule(int $product_id, int $combination_id): void
    {
        $this->description = json_encode(['id_product' => $product_id, 'id_product_attribute' => $combination_id, 'type' => static::SYSTEM_RULE_CHEAPEST_PRODUCT]);
    }
    /**
     * Returns product id of the selected cheapest product
     */
    public function get_cheapest_product_id(): int
    {
        if ($this->is_cheapest_product_system_rule()) {
            $object = json_decode((string) $this->description);
            return $object->id_product ?? 0;
        }
        return 0;
    }
}