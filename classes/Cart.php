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
 * Class CartCore
 */
class Cart_Core extends Object_Model
{
    public const ONLY_PRODUCTS = 1;
    public const ONLY_DISCOUNTS = 2;
    public const BOTH = 3;
    public const BOTH_WITHOUT_SHIPPING = 4;
    public const ONLY_SHIPPING = 5;
    public const ONLY_WRAPPING = 6;
    public const ONLY_PRODUCTS_WITHOUT_SHIPPING = 7;
    public const ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING = 8;
    public const NO_CARRIER_FOUND_PLACEHOLDER = 0;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'cart', 'primary' => 'id_cart', 'fields' => ['id_shop_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbDefault' => '1'], 'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbDefault' => '1'], 'id_carrier' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false], 'delivery_option' => ['type' => self::TYPE_STRING, 'size' => Object_Model::SIZE_TEXT, 'dbNullable' => false], 'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_address_delivery' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false], 'id_address_invoice' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false], 'id_currency' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false], 'id_guest' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false], 'secure_key' => ['type' => self::TYPE_STRING, 'size' => 32, 'dbDefault' => '-1'], 'recyclable' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '1'], 'gift' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'], 'gift_message' => ['type' => self::TYPE_STRING, 'validate' => 'isMessage', 'size' => Object_Model::SIZE_TEXT], 'mobile_theme' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'], 'allow_seperated_package' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false]], 'keys' => ['cart' => ['cart_customer' => ['type' => Object_Model::KEY, 'columns' => ['id_customer']], 'id_address_delivery' => ['type' => Object_Model::KEY, 'columns' => ['id_address_delivery']], 'id_address_invoice' => ['type' => Object_Model::KEY, 'columns' => ['id_address_invoice']], 'id_carrier' => ['type' => Object_Model::KEY, 'columns' => ['id_carrier']], 'id_currency' => ['type' => Object_Model::KEY, 'columns' => ['id_currency']], 'id_guest' => ['type' => Object_Model::KEY, 'columns' => ['id_guest']], 'id_lang' => ['type' => Object_Model::KEY, 'columns' => ['id_lang']], 'id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop', 'date_add']], 'id_shop_2' => ['type' => Object_Model::KEY, 'columns' => ['id_shop', 'date_upd']], 'id_shop_group' => ['type' => Object_Model::KEY, 'columns' => ['id_shop_group']]]]];
    /**
     * @var int[] $_nbProducts
     */
    protected static $_nb_products = [];
    /**
     * @var array $_isVirtualCart
     */
    protected static $_is_virtual_cart = [];
    /**
     * @var float[] $_totalWeight
     */
    protected static $_total_weight = [];
    /**
     * @var Carrier[]
     */
    protected static $_carriers = [];
    /**
     * @var array
     */
    protected static $_attributes_lists = [];
    /** @var Customer|null */
    protected static $_customer;
    /**
     * @var int
     */
    public $id_shop_group;
    /**
     * @var int
     */
    public $id_shop;
    /**
     * @var int Customer delivery address ID
     */
    public $id_address_delivery;
    /**
     * @var int Customer invoicing address ID
     */
    public $id_address_invoice;
    /**
     * @var int Customer currency ID
     */
    public $id_currency;
    /**
     * @var int Customer ID
     */
    public $id_customer;
    /**
     * @var int Guest ID
     */
    public $id_guest;
    /**
     * @var int Language ID
     */
    public $id_lang;
    /**
     * @var bool True if the customer wants a recycled package
     */
    public $recyclable = 0;
    /**
     * @var bool True if the customer wants a gift wrapping
     */
    public $gift = 0;
    /**
     * @var string Gift message if specified
     */
    public $gift_message;
    /**
     * @var bool Mobile Theme
     */
    public $mobile_theme;
    /**
     * @var string Object creation date
     */
    public $date_add;
    /**
     * @var string secure_key
     */
    public $secure_key;
    /**
     * @var int Carrier ID
     */
    public $id_carrier = 0;
    /**
     * @var string Object last modification date
     */
    public $date_upd;
    /**
     * @var bool $checkedTos
     */
    public $checked_tos = false;
    /**
     * @var string
     */
    public $delivery_option;
    /**
     * @var bool Allow to seperate order in multiple package in order to recieve as soon as possible the available products
     */
    public $allow_seperated_package = false;
    /**
     * @var array[] | null
     */
    protected $_products;
    /**
     * @var int
     */
    protected $_tax_calculation_method = PS_TAX_EXC;
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['fields' => ['id_address_delivery' => ['xlink_resource' => 'addresses'], 'id_address_invoice' => ['xlink_resource' => 'addresses'], 'id_currency' => ['xlink_resource' => 'currencies'], 'id_customer' => ['xlink_resource' => 'customers'], 'id_guest' => ['xlink_resource' => 'guests'], 'id_lang' => ['xlink_resource' => 'languages']], 'associations' => ['cart_rows' => ['resource' => 'cart_row', 'virtual_entity' => true, 'fields' => ['id_product' => ['required' => true, 'xlink_resource' => 'products'], 'id_product_attribute' => ['required' => true, 'xlink_resource' => 'combinations'], 'id_address_delivery' => ['required' => true, 'xlink_resource' => 'addresses'], 'quantity' => ['required' => true]]]]];
    /**
     * CartCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     *
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id);
        if (!is_null($id_lang)) {
            $this->id_lang = (int) (Language::get_language($id_lang) !== false) ? $id_lang : Configuration::get('PS_LANG_DEFAULT');
        }
        if ($this->id_customer) {
            if (isset(Context::get_context()->customer) && Context::get_context()->customer->id == $this->id_customer) {
                $customer = Context::get_context()->customer;
            } else {
                $customer = new Customer((int) $this->id_customer);
            }
            static::$_customer = $customer;
            if ((!$this->secure_key || $this->secure_key == '-1') && $customer->secure_key) {
                $this->secure_key = $customer->secure_key;
                $this->save();
            }
        }
        $this->set_tax_calculation_method();
    }
    /**
     * @throws PrestaShopException
     */
    public function set_tax_calculation_method(): void
    {
        $this->_tax_calculation_method = (int) Group::get_price_display_method(Group::get_current()->id);
    }
    /**
     * Get the average tax used in the Cart
     *
     * @param int $idCart
     *
     * @return float|int
     * @throws PrestaShopException
     */
    public static function get_taxes_average_used($id_cart)
    {
        $cart = new Cart((int) $id_cart);
        if (!Validate::is_loaded_object($cart)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Cart with ID %s not found'), (int) $id_cart));
        }
        if (!Configuration::get('PS_TAX')) {
            return 0;
        }
        $products = $cart->get_products();
        $total_products_moy = 0;
        $ratio_tax = 0;
        if (!count($products)) {
            return 0;
        }
        foreach ($products as $product) {
            // products refer to the cart details
            if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
                $address_id = (int) $cart->id_address_invoice;
            } else {
                $address_id = (int) $product['id_address_delivery'];
            }
            // Get delivery address of the product from the cart
            if (!Address::address_exists($address_id)) {
                $address_id = null;
            }
            $total_products_moy += $product['total_wt'];
            $ratio_tax += $product['total_wt'] * Tax::get_product_tax_rate((int) $product['id_product'], (int) $address_id);
        }
        if ($total_products_moy > 0) {
            return $ratio_tax / $total_products_moy;
        }
        return 0;
    }
    /**
     * Return cart products
     *
     * @param bool $refresh
     * @param int|false $idProduct
     * @param int|null $idCountry
     *
     * @return array[]
     * @throws PrestaShopException
     */
    public function get_products($refresh = false, $id_product = false, $id_country = null)
    {
        if (!$this->id) {
            return [];
        }
        // Product cache must be strictly compared to NULL, or else an empty cart will add dozens of queries
        if ($this->_products !== null && !$refresh) {
            // Return product row with specified ID if it exists
            if (is_int($id_product)) {
                foreach ($this->_products as $product) {
                    if ($product['id_product'] == $id_product) {
                        return [$product];
                    }
                }
                return [];
            }
            return $this->_products;
        }
        // Build query
        $sql = new Db_Query();
        // Build SELECT
        $sql->select('cp.`id_product_attribute`');
        $sql->select('cp.`id_product`');
        $sql->select('cp.`quantity` AS `cart_quantity`');
        $sql->select('cp.`id_shop`');
        $sql->select('pl.`name`');
        $sql->select('p.`is_virtual`');
        $sql->select('pl.`description_short`');
        $sql->select('pl.`available_now`');
        $sql->select('pl.`available_later`');
        $sql->select('product_shop.`id_category_default`');
        $sql->select('p.`id_supplier`');
        $sql->select('p.`id_manufacturer`');
        $sql->select('product_shop.`on_sale`');
        $sql->select('product_shop.`ecotax`');
        $sql->select('product_shop.`additional_shipping_cost`');
        $sql->select('product_shop.`available_for_order`');
        $sql->select('product_shop.`price`');
        $sql->select('product_shop.`active`');
        $sql->select('product_shop.`unity`');
        $sql->select('product_shop.`unit_price_ratio`');
        $sql->select('stock.`quantity` AS `quantity_available`');
        $sql->select('p.`width`');
        $sql->select('p.`height`');
        $sql->select('p.`depth`');
        $sql->select('p.`weight`');
        $sql->select('stock.`out_of_stock`');
        $sql->select('p.`date_add`');
        $sql->select('p.`date_upd`');
        $sql->select('IFNULL(stock.`quantity`, 0) AS `quantity`');
        $sql->select('pl.`link_rewrite`');
        $sql->select('cl.`link_rewrite` AS `category`');
        $sql->select('CONCAT(LPAD(cp.`id_product`, 10, 0), LPAD(IFNULL(cp.`id_product_attribute`, 0), 10, 0), IFNULL(cp.`id_address_delivery`, 0)) AS unique_id');
        $sql->select('cp.`id_address_delivery`');
        $sql->select('product_shop.`advanced_stock_management`');
        $sql->select('ps.`product_supplier_reference` AS `supplier_reference`');
        // Build FROM
        $sql->from('cart_product', 'cp');
        // Build JOIN
        $sql->left_join('product', 'p', 'p.`id_product` = cp.`id_product`');
        $sql->inner_join('product_shop', 'product_shop', '(product_shop.`id_shop` = cp.`id_shop` AND product_shop.`id_product` = p.`id_product`)');
        $sql->left_join('product_lang', 'pl', 'p.`id_product` = pl.`id_product` AND pl.`id_lang` = ' . (int) $this->id_lang . Shop::add_sql_restriction_on_lang('pl', 'cp.id_shop'));
        $sql->left_join('category_lang', 'cl', 'product_shop.`id_category_default` = cl.`id_category` AND cl.`id_lang` = ' . (int) $this->id_lang . Shop::add_sql_restriction_on_lang('cl', 'cp.id_shop'));
        $sql->left_join('product_supplier', 'ps', 'ps.`id_product` = cp.`id_product` AND ps.`id_product_attribute` = cp.`id_product_attribute` AND ps.`id_supplier` = p.`id_supplier`');
        // @todo test if everything is ok, then refactorise call of this method
        $sql->join(Product::sql_stock('cp', 'cp'));
        // Build WHERE clauses
        $sql->where('cp.`id_cart` = ' . (int) $this->id);
        if ($id_product) {
            $sql->where('cp.`id_product` = ' . (int) $id_product);
        }
        $sql->where('p.`id_product` IS NOT NULL');
        // Build ORDER BY
        $sql->order_by('cp.`date_add`, cp.`id_product`, cp.`id_product_attribute` ASC');
        if (Customization::is_feature_active()) {
            $sql->select('cu.`id_customization`, cu.`quantity` AS customization_quantity');
            $sql->left_join('customization', 'cu', 'p.`id_product` = cu.`id_product` AND cp.`id_product_attribute` = cu.`id_product_attribute` AND cu.`id_cart` = ' . (int) $this->id);
            $sql->group_by('cp.`id_product_attribute`, cp.`id_product`, cp.`id_shop`');
        } else {
            $sql->select('NULL AS customization_quantity, NULL AS id_customization');
        }
        if (Combination::is_feature_active()) {
            $sql->select('product_attribute_shop.`price` AS price_attribute, product_attribute_shop.`ecotax` AS ecotax_attr');
            $sql->select('IF (IFNULL(pa.`reference`, \'\') = \'\', p.`reference`, pa.`reference`) AS reference');
            $sql->select('(p.`weight`+ pa.`weight`) weight_attribute');
            $sql->select('IF (IFNULL(pa.`ean13`, \'\') = \'\', p.`ean13`, pa.`ean13`) AS ean13');
            $sql->select('IF (IFNULL(pa.`upc`, \'\') = \'\', p.`upc`, pa.`upc`) AS upc');
            $sql->select('IFNULL(product_attribute_shop.`minimal_quantity`, product_shop.`minimal_quantity`) as minimal_quantity');
            $sql->select('IF(product_attribute_shop.wholesale_price > 0,  product_attribute_shop.wholesale_price, product_shop.`wholesale_price`) wholesale_price');
            $sql->left_join('product_attribute', 'pa', 'pa.`id_product_attribute` = cp.`id_product_attribute`');
            $sql->left_join('product_attribute_shop', 'product_attribute_shop', '(product_attribute_shop.`id_shop` = cp.`id_shop` AND product_attribute_shop.`id_product_attribute` = pa.`id_product_attribute`)');
        } else {
            $sql->select('p.`reference` AS `reference`');
            $sql->select('p.`ean13`');
            $sql->select('p.`upc` AS `upc`');
            $sql->select('product_shop.`minimal_quantity` AS `minimal_quantity`');
            $sql->select('product_shop.`wholesale_price` AS `wholesale_price`');
        }
        $sql->select('image_shop.`id_image` id_image, il.`legend`');
        $sql->left_join('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $this->id_shop);
        $sql->left_join('image_lang', 'il', 'il.`id_image` = image_shop.`id_image` AND il.`id_lang` = ' . (int) $this->id_lang);
        $result = Db::read_only()->get_array($sql);
        // Reset the cache before the following return, or else an empty cart will add dozens of queries
        $products_ids = [];
        $pa_ids = [];
        if ($result) {
            $id_group = null;
            if ($this->id_customer) {
                $id_group = (int) Customer::get_default_group_id((int) $this->id_customer);
            }
            if (!$id_group) {
                $id_group = (int) Group::get_current()->id;
            }
            foreach ($result as $key => $row) {
                $products_ids[] = $row['id_product'];
                $pa_ids[] = $row['id_product_attribute'];
                $specific_price = Specific_Price::get_specific_price($row['id_product'], $this->id_shop, $this->id_currency, $id_country, $id_group, (int) $row['cart_quantity'], $row['id_product_attribute'], $this->id_customer, $this->id);
                $reduction_rows = ['reduction_type' => 0, 'reduction_amount' => 0, 'reduction_from_quantity' => 0];
                if ($specific_price) {
                    $reduction_rows['reduction_type'] = $specific_price['reduction_type'];
                    if ($specific_price['reduction_type'] == 'amount') {
                        $specific_price_reduction_amount = Tools::convert_price($specific_price['reduction'], $this->id_currency);
                    } elseif ($specific_price['reduction_type'] == 'percentage') {
                        $specific_price_reduction_amount = round((float) $specific_price['reduction'] * 100);
                    }
                    $reduction_rows['reduction_amount'] = $specific_price_reduction_amount ?? 0;
                    $reduction_rows['reduction_from_quantity'] = (int) $specific_price['from_quantity'];
                }
                $result[$key] = array_merge($row, $reduction_rows);
            }
        }
        // Thus you can avoid one query per product, because there will be only one query for all the products of the cart
        Product::cache_products_features($products_ids);
        static::cache_some_attributes_lists($pa_ids, $this->id_lang);
        $this->_products = [];
        if (empty($result)) {
            return [];
        }
        Tax::get_product_ecotax_rate($this->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        Product::$_tax_calculation_method == PS_TAX_INC && (int) Configuration::get('PS_TAX');
        $cart_shop_context = Context::get_context()->clone_context();
        foreach ($result as &$row) {
            if (isset($row['ecotax_attr']) && $row['ecotax_attr'] > 0) {
                $row['ecotax'] = (float) $row['ecotax_attr'];
            }
            $quantity = (int) $row['cart_quantity'];
            $row['stock_quantity'] = (int) $row['quantity'];
            $row['quantity'] = $quantity;
            if (isset($row['id_product_attribute']) && (int) $row['id_product_attribute'] && isset($row['weight_attribute'])) {
                $row['weight'] = (float) $row['weight_attribute'];
            }
            if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
                $address_id = (int) $this->id_address_invoice;
            } else {
                $address_id = (int) $row['id_address_delivery'];
            }
            if (!Address::address_exists($address_id)) {
                $address_id = null;
            }
            if ($cart_shop_context->shop->id != $row['id_shop']) {
                $cart_shop_context->shop = new Shop((int) $row['id_shop']);
            }
            $address = Address::initialize($address_id, true);
            $id_tax_rules_group = Product::get_id_tax_rules_group_by_id_product((int) $row['id_product'], $cart_shop_context);
            $tax_calculator = Tax_Manager_Factory::get_manager($address, $id_tax_rules_group)->get_tax_calculator();
            // Prices params array
            $prices_params = ['price_without_reduction' => ['usetax' => true, 'usereduc' => false], 'price_without_reduction_without_tax' => ['usetax' => false, 'usereduc' => false], 'price_with_reduction' => ['usetax' => true, 'usereduc' => true], 'price_with_reduction_without_tax' => ['alias' => 'price', 'usetax' => false, 'usereduc' => true]];
            foreach ($prices_params as $param_key => $param_settings) {
                $row[$param_key] = Product::get_price_static((int) $row['id_product'], $param_settings['usetax'], isset($row['id_product_attribute']) ? (int) $row['id_product_attribute'] : null, _TB_PRICE_DATABASE_PRECISION_, null, false, $param_settings['usereduc'], $quantity, false, (int) $this->id_customer ?: null, (int) $this->id, $address_id, $specific_price_output, true, true, $cart_shop_context);
                if (!empty($param_settings['alias'])) {
                    $row[$param_settings['alias']] = $row[$param_key];
                }
            }
            $row['total'] = $this->round_price($row['price_with_reduction_without_tax'], $row['price_with_reduction'], $quantity, false);
            $row['total_wt'] = $this->round_price($row['price_with_reduction_without_tax'], $row['price_with_reduction'], $quantity, true);
            // Recalculate prices after rounding, these go into an order.
            if ($quantity !== 0) {
                $row['price'] = round($row['total'] / $quantity, _TB_PRICE_DATABASE_PRECISION_);
                $row['price_wt'] = round($row['total_wt'] / $quantity, _TB_PRICE_DATABASE_PRECISION_);
            } else {
                $row['price'] = 0.0;
                $row['price_wt'] = 0.0;
            }
            $row['description_short'] = Tools::nl2br($row['description_short']);
            // check if a image associated with the attribute exists
            if ($row['id_product_attribute']) {
                $row2 = Image::get_best_image_attribute($row['id_shop'], $this->id_lang, $row['id_product'], $row['id_product_attribute']);
                if ($row2) {
                    $row = array_merge($row, $row2);
                }
            }
            $row['reduction_applies'] = $specific_price_output && (float) $specific_price_output['reduction'];
            $row['quantity_discount_applies'] = $specific_price_output && $quantity >= (int) $specific_price_output['from_quantity'];
            $row['allow_oosp'] = Product::is_available_when_out_of_stock($row['out_of_stock']);
            $row['features'] = Product::get_features_static((int) $row['id_product']);
            if (array_key_exists($row['id_product_attribute'] . '-' . $this->id_lang, static::$_attributes_lists)) {
                $row = array_merge($row, static::$_attributes_lists[$row['id_product_attribute'] . '-' . $this->id_lang]);
            }
            $row = Product::get_taxes_informations($row, $cart_shop_context);
            $this->_products[] = $row;
        }
        return $this->_products;
    }
    /**
     * Round a quantity of a price for display. This is non-trivial, because
     * thirty bees features multiple rounding strategies.
     *
     * @param float $priceWithoutTax Single price of the product, without tax.
     * @param float $priceWithTax Single price of the product, with tax.
     * @param int $quantity Quantity of the product.
     * @param bool $withTax Whether the price with or without tax
     *                                should get returned. Rounding gets
     *                                applied to the displayed price, so
     *                                rounding the other variant requires to
     *                                recalculate taxes.
     *
     * return float Rounded and multiplied price.
     *
     * @return float|int
     * @throws PrestaShopException
     */
    protected function round_price($price_without_tax, $price_with_tax, $quantity, $with_tax)
    {
        $display_precision = Currency::get_currency_instance($this->id_currency)->get_display_precision();
        $round_type = (int) Configuration::get('PS_ROUND_TYPE');
        $price = $price_without_tax;
        if ($this->_tax_calculation_method === PS_TAX_INC) {
            $price = $price_with_tax;
        }
        $price = Tools::round_price($price);
        if ($round_type === Order::ROUND_ITEM) {
            $price = Tools::ps_round($price, $display_precision);
        }
        $total = $price * (int) $quantity;
        // Add/remove taxes as appropriate. Ignore the obvious calculation
        // precision limitation, please, it should be negligible.
        if ($price_with_tax && $this->_tax_calculation_method === PS_TAX_INC && !$with_tax) {
            // Remove taxes.
            $total = Tools::round_price($total / $price_with_tax * $price_without_tax);
        } elseif ($price_without_tax && $this->_tax_calculation_method === PS_TAX_EXC && $with_tax) {
            // Add taxes.
            $total = Tools::round_price($total * $price_with_tax / $price_without_tax);
        }
        // else nothing to change.
        if ($round_type === Order::ROUND_LINE) {
            return Tools::ps_round($total, $display_precision);
        }
        return $total;
    }
    /**
     * @param array $ipaList
     * @param int $idLang
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function cache_some_attributes_lists($ipa_list, $id_lang): void
    {
        if (!Combination::is_feature_active()) {
            return;
        }
        $pa_implode = [];
        foreach ($ipa_list as $id_product_attribute) {
            if ((int) $id_product_attribute && !array_key_exists($id_product_attribute . '-' . $id_lang, static::$_attributes_lists)) {
                $pa_implode[] = (int) $id_product_attribute;
                static::$_attributes_lists[(int) $id_product_attribute . '-' . $id_lang] = ['attributes' => '', 'attributes_small' => ''];
            }
        }
        if (!count($pa_implode)) {
            return;
        }
        $result = Db::read_only()->get_array((new Db_Query())->select('pac.`id_product_attribute`, agl.`public_name` AS `public_group_name`, al.`name` AS `attribute_name`')->from('product_attribute_combination', 'pac')->left_join('attribute', 'a', 'a.`id_attribute` = pac.`id_attribute`')->left_join('attribute_group', 'ag', 'ag.`id_attribute_group` = a.`id_attribute_group`')->left_join('attribute_lang', 'al', 'a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $id_lang)->left_join('attribute_group_lang', 'agl', 'ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $id_lang)->where('pac.`id_product_attribute` IN (' . implode(',', $pa_implode) . ')')->order_by('ag.`position` ASC, a.`position` ASC'));
        foreach ($result as $row) {
            static::$_attributes_lists[$row['id_product_attribute'] . '-' . $id_lang]['attributes'] .= $row['public_group_name'] . ' : ' . $row['attribute_name'] . ', ';
            static::$_attributes_lists[$row['id_product_attribute'] . '-' . $id_lang]['attributes_small'] .= $row['attribute_name'] . ', ';
        }
        foreach ($pa_implode as $id_product_attribute) {
            static::$_attributes_lists[$id_product_attribute . '-' . $id_lang]['attributes'] = rtrim((string) static::$_attributes_lists[$id_product_attribute . '-' . $id_lang]['attributes'], ', ');
            static::$_attributes_lists[$id_product_attribute . '-' . $id_lang]['attributes_small'] = rtrim((string) static::$_attributes_lists[$id_product_attribute . '-' . $id_lang]['attributes_small'], ', ');
        }
    }
    /**
     * @param int $idCart
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function get_order_total_using_tax_calculation_method($id_cart)
    {
        return static::get_total_cart($id_cart, true);
    }
    /**
     * @param int $idCart
     * @param bool $useTaxDisplay
     * @param int $type
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function get_total_cart($id_cart, $use_tax_display = false, $type = self::BOTH)
    {
        $cart = new Cart($id_cart);
        if (!Validate::is_loaded_object($cart)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Cart with ID %s not found'), (int) $id_cart));
        }
        $with_taxes = !$use_tax_display || $cart->_tax_calculation_method !== PS_TAX_EXC;
        return Tools::display_price($cart->get_order_total($with_taxes, $type), Currency::get_currency_instance((int) $cart->id_currency), false);
    }
    /**
     * This function returns the total cart amount
     *
     * Possible values for $type:
     * static::ONLY_PRODUCTS
     * static::ONLY_DISCOUNTS
     * static::BOTH
     * static::BOTH_WITHOUT_SHIPPING
     * static::ONLY_SHIPPING
     * static::ONLY_WRAPPING
     * static::ONLY_PRODUCTS_WITHOUT_SHIPPING
     * static::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING
     *
     * @param bool $withTaxes With or without taxes
     * @param int $type Total type
     * @param array|null $products
     * @param int|null $idCarrier
     * @param bool $useCache Allow using cache of the method CartRule::getContextualValue
     *
     * @return float Order total
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_order_total($with_taxes = true, $type = self::BOTH, $products = null, $id_carrier = null, $use_cache = true)
    {
        $display_precision = Currency::get_currency_instance($this->id_currency)->get_display_precision();
        // Dependencies
        /** @var Adapter_AddressFactory $addressFactory */
        $address_factory = Adapter_service_Locator::get('Adapter_AddressFactory');
        /** @var Adapter_ProductPriceCalculator $priceCalculator */
        $price_calculator = Adapter_service_Locator::get('Adapter_ProductPriceCalculator');
        /** @var Core_Business_ConfigurationInterface $configuration */
        $configuration = Adapter_service_Locator::get('Core_Business_ConfigurationInterface');
        $ps_tax_address_type = $configuration->get('PS_TAX_ADDRESS_TYPE');
        $ps_use_ecotax = $configuration->get('PS_USE_ECOTAX');
        if (!$this->id) {
            return 0;
        }
        $type = (int) $type;
        $array_type = [static::ONLY_PRODUCTS, static::ONLY_DISCOUNTS, static::BOTH, static::BOTH_WITHOUT_SHIPPING, static::ONLY_SHIPPING, static::ONLY_WRAPPING, static::ONLY_PRODUCTS_WITHOUT_SHIPPING, static::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING];
        // Define virtual context to prevent case where the cart is not the in the global context
        $virtual_context = Context::get_context()->clone_context();
        $virtual_context->cart = $this;
        if (!in_array($type, $array_type)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('getOrderTotal: Invalid value of $type parameter: %s'), $type));
        }
        $with_shipping = in_array($type, [static::BOTH, static::ONLY_SHIPPING]);
        // if cart rules are not used
        if ($type == static::ONLY_DISCOUNTS && !Cart_Rule::is_feature_active()) {
            return 0;
        }
        // no shipping cost if is a cart with only virtuals products
        $virtual = $this->is_virtual_cart();
        if ($virtual && $type == static::ONLY_SHIPPING) {
            return 0;
        }
        if ($virtual && $type == static::BOTH) {
            $type = static::BOTH_WITHOUT_SHIPPING;
        }
        if ($with_shipping || $type == static::ONLY_DISCOUNTS) {
            if (is_null($products) && is_null($id_carrier)) {
                $shipping_fees = $this->get_total_shipping_cost(null, (bool) $with_taxes);
            } else {
                $shipping_fees = $this->get_package_shipping_cost((int) $id_carrier, (bool) $with_taxes, null, $products);
            }
        } else {
            $shipping_fees = 0;
        }
        if ($type == static::ONLY_SHIPPING) {
            return $shipping_fees;
        }
        if ($type == static::ONLY_PRODUCTS_WITHOUT_SHIPPING) {
            $type = static::ONLY_PRODUCTS;
        }
        $param_product = true;
        if (is_null($products)) {
            $param_product = false;
            $products = $this->get_products();
        }
        if ($type == static::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING) {
            foreach ($products as $key => $product) {
                if ($product['is_virtual']) {
                    unset($products[$key]);
                }
            }
            $type = static::ONLY_PRODUCTS;
        }
        $order_total = 0;
        if (Tax::exclude_taxe_option()) {
            $with_taxes = false;
        }
        $products_total = [];
        foreach ($products as $product) {
            // products refer to the cart details
            if ($virtual_context->shop->id != $product['id_shop']) {
                $virtual_context->shop = new Shop((int) $product['id_shop']);
            }
            if ($ps_tax_address_type == 'id_address_invoice') {
                $id_address = (int) $this->id_address_invoice;
            } else {
                $id_address = (int) $product['id_address_delivery'];
            }
            // Get delivery address of the product from the cart
            if (!$address_factory->address_exists($id_address)) {
                $id_address = null;
            }
            // The $null variable below is not used,
            // but it is necessary to pass it to getProductPrice because
            // it expects a reference.
            $null = null;
            $price_without_tax = $price_calculator->get_product_price((int) $product['id_product'], false, (int) $product['id_product_attribute'], _TB_PRICE_DATABASE_PRECISION_, null, false, true, $product['cart_quantity'], false, (int) $this->id_customer ?: null, (int) $this->id, $id_address, $null, $ps_use_ecotax, true, $virtual_context);
            $price_with_tax = $price_calculator->get_product_price((int) $product['id_product'], true, (int) $product['id_product_attribute'], _TB_PRICE_DATABASE_PRECISION_, null, false, true, $product['cart_quantity'], false, (int) $this->id_customer ?: null, (int) $this->id, $id_address, $null, $ps_use_ecotax, true, $virtual_context);
            if ($with_taxes) {
                $id_tax_rules_group = Product::get_id_tax_rules_group_by_id_product((int) $product['id_product'], $virtual_context);
            } else {
                $id_tax_rules_group = 0;
            }
            $index = $id_tax_rules_group;
            if (Configuration::get('PS_ROUND_TYPE') == Order::ROUND_TOTAL) {
                $index = $id_tax_rules_group . '_' . $id_address;
            }
            if (!isset($products_total[$index])) {
                $products_total[$index] = 0;
            }
            $products_total[$index] += $this->round_price($price_without_tax, $price_with_tax, $product['cart_quantity'], $with_taxes);
        }
        foreach ($products_total as $price) {
            $order_total += $price;
        }
        $order_total_products = $order_total;
        if ($type == static::ONLY_DISCOUNTS) {
            $order_total = 0;
        }
        // Wrapping Fees
        $wrapping_fees = 0;
        // With useProportionateTax on the gift wrapping cost computation calls getOrderTotal with $type === static::ONLY_PRODUCTS, so the flag below prevents an infinite recursion.
        $include_gift_wrapping = !Carrier::use_proportionate_tax() || $type !== static::ONLY_PRODUCTS;
        if ($this->gift && $include_gift_wrapping) {
            $wrapping_fees = Tools::ps_round(Tools::convert_price($this->get_gift_wrapping_price($with_taxes), Currency::get_currency_instance((int) $this->id_currency)), $display_precision);
        }
        if ($type == static::ONLY_WRAPPING) {
            return $wrapping_fees;
        }
        $order_total_discount = 0;
        $order_shipping_discount = 0;
        if (!in_array($type, [static::ONLY_SHIPPING, static::ONLY_PRODUCTS]) && Cart_Rule::is_feature_active()) {
            // First, retrieve the cart rules associated to this "getOrderTotal"
            if ($with_shipping || $type == static::ONLY_DISCOUNTS) {
                $cart_rules = $this->get_cart_rules(Cart_Rule::FILTER_ACTION_ALL);
            } else {
                $cart_rules = $this->get_cart_rules(Cart_Rule::FILTER_ACTION_REDUCTION);
                // Cart Rules array are merged manually in order to avoid doubles
                foreach ($this->get_cart_rules(Cart_Rule::FILTER_ACTION_GIFT) as $tmp_cart_rule) {
                    $flag = false;
                    foreach ($cart_rules as $cart_rule) {
                        if ($tmp_cart_rule['id_cart_rule'] == $cart_rule['id_cart_rule']) {
                            $flag = true;
                        }
                    }
                    if (!$flag) {
                        $cart_rules[] = $tmp_cart_rule;
                    }
                }
            }
            $id_address_delivery = 0;
            if (isset($products[0])) {
                $id_address_delivery = is_null($products) ? $this->id_address_delivery : $products[0]['id_address_delivery'];
            }
            $package = ['id_carrier' => $id_carrier, 'id_address' => $id_address_delivery, 'products' => $products];
            // Then, calculate the contextual value for each one
            $flag = false;
            foreach ($cart_rules as $cart_rule) {
                /** @var CartRule $cartRuleObject */
                $cart_rule_object = $cart_rule['obj'];
                // If the cart rule offers free shipping, add the shipping cost
                if (($with_shipping || $type == static::ONLY_DISCOUNTS) && $cart_rule_object->free_shipping && !$flag) {
                    $order_shipping_discount = (float) $cart_rule_object->get_contextual_value($with_taxes, $virtual_context, Cart_Rule::FILTER_ACTION_SHIPPING, $param_product ? $package : null, $use_cache);
                    $flag = true;
                }
                // If the cart rule is a free gift, then add the free gift value only if the gift is in this package
                if ((int) $cart_rule_object->gift_product) {
                    $in_order = false;
                    if (is_null($products)) {
                        $in_order = true;
                    } else {
                        foreach ($products as $product) {
                            if ($cart_rule_object->gift_product == $product['id_product'] && $cart_rule_object->gift_product_attribute == $product['id_product_attribute']) {
                                $in_order = true;
                            }
                        }
                    }
                    if ($in_order) {
                        $order_total_discount += $cart_rule_object->get_contextual_value($with_taxes, $virtual_context, Cart_Rule::FILTER_ACTION_GIFT, $package, $use_cache);
                    }
                }
                // If the cart rule offers a reduction, the amount is prorated (with the products in the package)
                if ($cart_rule_object->reduction_percent > 0 || $cart_rule_object->reduction_amount > 0) {
                    $order_total_discount += $cart_rule_object->get_contextual_value($with_taxes, $virtual_context, Cart_Rule::FILTER_ACTION_REDUCTION, $package, $use_cache);
                }
            }
            $order_total_discount = min($order_total_discount, (float) $order_total_products) + (float) $order_shipping_discount;
            $order_total -= $order_total_discount;
        }
        if ($type == static::BOTH) {
            $order_total += $shipping_fees + $wrapping_fees;
        }
        if ($order_total < 0 && $type != static::ONLY_DISCOUNTS) {
            return 0;
        }
        if ($type == static::ONLY_DISCOUNTS) {
            return $order_total_discount;
        }
        return Tools::ps_round((float) $order_total, $display_precision);
    }
    /**
     * Check if cart contains only virtual products
     *
     * @return bool true if is a virtual cart or false
     *
     * @throws PrestaShopException
     */
    public function is_virtual_cart()
    {
        if (!Product_Download::is_feature_active()) {
            return false;
        }
        if (!isset(static::$_is_virtual_cart[$this->id])) {
            $products = $this->get_products();
            if (!count($products)) {
                return false;
            }
            $is_virtual = 1;
            foreach ($products as $product) {
                if (empty($product['is_virtual'])) {
                    $is_virtual = 0;
                }
            }
            static::$_is_virtual_cart[$this->id] = (int) $is_virtual;
        }
        return static::$_is_virtual_cart[$this->id];
    }
    /**
     * Return shipping total for the cart
     *
     * @param array|null $deliveryOption Array of the delivery option for each address
     * @param bool $useTax
     *
     * @return float Shipping total
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_total_shipping_cost($delivery_option = null, $use_tax = true, ?Country $default_country = null)
    {
        if (isset(Context::get_context()->cookie->id_country)) {
            $default_country = new Country(Context::get_context()->cookie->id_country);
        }
        if (is_null($delivery_option)) {
            $delivery_option = $this->get_delivery_option($default_country, false, false);
        }
        $total_shipping = 0;
        $delivery_option_list = $this->get_delivery_option_list($default_country);
        foreach ($delivery_option as $id_address => $key) {
            if (!isset($delivery_option_list[$id_address])) {
                continue;
            }
            if (!isset($delivery_option_list[$id_address][$key])) {
                continue;
            }
            if ($use_tax) {
                $total_shipping += $delivery_option_list[$id_address][$key]['total_price_with_tax'];
            } else {
                $total_shipping += $delivery_option_list[$id_address][$key]['total_price_without_tax'];
            }
        }
        return $total_shipping;
    }
    /**
     * Get the delivery option selected, or if no delivery option was selected,
     * the cheapest option for each address
     *
     * @param Country|null $defaultCountry
     * @param bool $dontAutoSelectOptions
     * @param bool $useCache
     *
     * @return array|bool|mixed Delivery option
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_delivery_option($default_country = null, $dont_auto_select_options = false, $use_cache = true)
    {
        static $cache = [];
        $cache_id = (int) (is_object($default_country) ? $default_country->id : 0) . '-' . (int) $dont_auto_select_options;
        if (isset($cache[$cache_id]) && $use_cache) {
            return $cache[$cache_id];
        }
        $delivery_option_list = $this->get_delivery_option_list($default_country);
        // The delivery option was selected
        if (isset($this->delivery_option) && $this->delivery_option != '') {
            $delivery_option = json_decode($this->delivery_option, true);
            $validated = true;
            if (is_array($delivery_option)) {
                foreach ($delivery_option as $id_address => $key) {
                    if (!isset($delivery_option_list[$id_address][$key])) {
                        $validated = false;
                        break;
                    }
                }
                if ($validated) {
                    $cache[$cache_id] = $delivery_option;
                    return $delivery_option;
                }
            }
        }
        if ($dont_auto_select_options) {
            return false;
        }
        // No delivery option selected or delivery option selected is not valid, get the better for all options
        $delivery_option = [];
        foreach ($delivery_option_list as $id_address => $options) {
            foreach ($options as $key => $option) {
                if (Configuration::get('PS_CARRIER_DEFAULT') == -1 && $option['is_best_price']) {
                    $delivery_option[$id_address] = $key;
                    break;
                } elseif (Configuration::get('PS_CARRIER_DEFAULT') == -2 && $option['is_best_grade']) {
                    $delivery_option[$id_address] = $key;
                    break;
                } elseif ($option['unique_carrier'] && in_array(Configuration::get('PS_CARRIER_DEFAULT'), array_keys($option['carrier_list']))) {
                    $delivery_option[$id_address] = $key;
                    break;
                }
            }
            if (!isset($delivery_option[$id_address])) {
                $delivery_option[$id_address] = array_key_first($options);
            }
        }
        $cache[$cache_id] = $delivery_option;
        return $delivery_option;
    }
    /**
     * Set the delivery option and id_carrier, if there is only one carrier
     *
     * @param array|null $deliveryOption
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_delivery_option($delivery_option = null): void
    {
        if (empty($delivery_option) || count($delivery_option) == 0) {
            $this->delivery_option = '';
            $this->id_carrier = 0;
            return;
        }
        Cache::clean('getContextualValue_*');
        $delivery_option_list = $this->get_delivery_option_list(null, true);
        foreach ($delivery_option_list as $id_address => $options) {
            if (!isset($delivery_option[$id_address])) {
                foreach ($options as $key => $option) {
                    if ($option['is_best_price']) {
                        $delivery_option[$id_address] = $key;
                        break;
                    }
                }
            }
        }
        if (count($delivery_option) == 1) {
            $this->id_carrier = $this->get_id_carrier_from_delivery_option($delivery_option);
        }
        $this->delivery_option = json_encode($delivery_option);
    }
    /**
     * Get all deliveries options available for the current cart
     *
     * @param bool $flush Force flushing cache
     *
     * @return array array(
     *                   0 => array( // First address
     *                       '12,' => array(  // First delivery option available for this address
     *                           carrier_list => array(
     *                               12 => array( // First carrier for this option
     *                                   'instance' => Carrier Object,
     *                                   'logo' => <url to the carriers logo>,
     *                                   'price_with_tax' => 12.4,
     *                                   'price_without_tax' => 12.4,
     *                                   'package_list' => array(
     *                                       1,
     *                                       3,
     *                                   ),
     *                               ),
     *                           ),
     *                           is_best_grade => true, // Does this option have the biggest grade (quick shipping) for this shipping address
     *                           is_best_price => true, // Does this option have the lower price for this shipping address
     *                           unique_carrier => true, // Does this option use a unique carrier
     *                           total_price_with_tax => 12.5,
     *                           total_price_without_tax => 12.5,
     *                           position => 5, // Average of the carrier position
     *                       ),
     *                   ),
     *               );
     *               If there are no carriers available for an address, return an empty  array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_delivery_option_list(?Country $default_country = null, $flush = false)
    {
        $country_id = $default_country ? $default_country->id : 0;
        $cache_key = 'Cart::getDeliveryOptionList_' . $this->id . '_' . $country_id;
        if ($flush || !Cache::is_stored($cache_key)) {
            Cache::store($cache_key, $this->calculate_delivery_option_list($default_country));
        }
        return Cache::retrieve($cache_key);
    }
    /**
     * Calculate all delivery options available for the current cart
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function calculate_delivery_option_list(?Country $default_country = null)
    {
        $delivery_option_list = [];
        $carriers_price = [];
        $carrier_collection = [];
        $package_list = $this->get_package_list(true);
        // Foreach addresses
        foreach ($package_list as $id_address => $packages) {
            // Initialize vars
            $delivery_option_list[$id_address] = [];
            $carriers_price[$id_address] = [];
            $common_carriers = null;
            $best_price_carriers = [];
            $best_grade_carriers = [];
            $carriers_instance = [];
            // Get country
            if ($id_address) {
                $address = new Address($id_address);
                $country = new Country($address->id_country);
            } else {
                $country = $default_country;
            }
            // Foreach packages, get the carriers with best price, best position and best grade
            foreach ($packages as $id_package => $package) {
                // fail if package have no carrier associated
                if (!$package['carrier_list'] || in_array(static::NO_CARRIER_FOUND_PLACEHOLDER, $package['carrier_list'])) {
                    return [];
                }
                $carriers_price[$id_address][$id_package] = [];
                // Get all common carriers for each packages to the same address
                if (is_null($common_carriers)) {
                    $common_carriers = $package['carrier_list'];
                } else {
                    $common_carriers = array_intersect($common_carriers, $package['carrier_list']);
                }
                $best_price = null;
                $best_price_carrier = null;
                $best_grade = null;
                $best_grade_carrier = null;
                // Foreach carriers of the package, calculate his price, check if it the best price, position and grade
                foreach ($package['carrier_list'] as $id_carrier) {
                    if (!isset($carriers_instance[$id_carrier])) {
                        $carriers_instance[$id_carrier] = new Carrier($id_carrier);
                    }
                    $price_with_tax = $this->get_package_shipping_cost((int) $id_carrier, true, $country, $package['product_list']);
                    $price_without_tax = $this->get_package_shipping_cost((int) $id_carrier, false, $country, $package['product_list']);
                    if (is_null($best_price) || $price_with_tax < $best_price) {
                        $best_price = $price_with_tax;
                        $best_price_carrier = $id_carrier;
                    }
                    $carriers_price[$id_address][$id_package][$id_carrier] = ['without_tax' => $price_without_tax, 'with_tax' => $price_with_tax];
                    $grade = $carriers_instance[$id_carrier]->grade;
                    if (is_null($best_grade) || $grade > $best_grade) {
                        $best_grade = $grade;
                        $best_grade_carrier = $id_carrier;
                    }
                }
                $best_price_carriers[$id_package] = $best_price_carrier;
                $best_grade_carriers[$id_package] = $best_grade_carrier;
            }
            // Reset $best_price_carrier, it's now an array
            $best_price_carrier = [];
            $key = '';
            // Get the delivery option with the lower price
            foreach ($best_price_carriers as $id_package => $id_carrier) {
                $key .= $id_carrier . ',';
                if (!isset($best_price_carrier[$id_carrier])) {
                    $best_price_carrier[$id_carrier] = ['price_with_tax' => 0, 'price_without_tax' => 0, 'package_list' => [], 'product_list' => []];
                }
                $best_price_carrier[$id_carrier]['price_with_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'];
                $best_price_carrier[$id_carrier]['price_without_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'];
                $best_price_carrier[$id_carrier]['package_list'][] = $id_package;
                $best_price_carrier[$id_carrier]['product_list'] = array_merge($best_price_carrier[$id_carrier]['product_list'], $packages[$id_package]['product_list']);
                $best_price_carrier[$id_carrier]['instance'] = $carriers_instance[$id_carrier];
                $real_best_price = !isset($real_best_price) || $real_best_price > $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'] ? $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'] : $real_best_price;
                $real_best_price_wt = !isset($real_best_price_wt) || $real_best_price_wt > $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'] ? $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'] : $real_best_price_wt;
            }
            // Add the delivery option with best price as best price
            $delivery_option_list[$id_address][$key] = ['carrier_list' => $best_price_carrier, 'is_best_price' => true, 'is_best_grade' => false, 'unique_carrier' => count($best_price_carrier) <= 1];
            // Reset $best_grade_carrier, it's now an array
            $best_grade_carrier = [];
            $key = '';
            // Get the delivery option with the best grade
            foreach ($best_grade_carriers as $id_package => $id_carrier) {
                $key .= $id_carrier . ',';
                if (!isset($best_grade_carrier[$id_carrier])) {
                    $best_grade_carrier[$id_carrier] = ['price_with_tax' => 0, 'price_without_tax' => 0, 'package_list' => [], 'product_list' => []];
                }
                $best_grade_carrier[$id_carrier]['price_with_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'];
                $best_grade_carrier[$id_carrier]['price_without_tax'] += $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'];
                $best_grade_carrier[$id_carrier]['package_list'][] = $id_package;
                $best_grade_carrier[$id_carrier]['product_list'] = array_merge($best_grade_carrier[$id_carrier]['product_list'], $packages[$id_package]['product_list']);
                $best_grade_carrier[$id_carrier]['instance'] = $carriers_instance[$id_carrier];
            }
            // Add the delivery option with best grade as best grade
            if (!isset($delivery_option_list[$id_address][$key])) {
                $delivery_option_list[$id_address][$key] = ['carrier_list' => $best_grade_carrier, 'is_best_price' => false, 'unique_carrier' => count($best_grade_carrier) <= 1];
            }
            $delivery_option_list[$id_address][$key]['is_best_grade'] = true;
            // Get all delivery options with a unique carrier
            foreach ($common_carriers as $id_carrier) {
                $key = '';
                $package_list = [];
                $product_list = [];
                $price_with_tax = 0;
                $price_without_tax = 0;
                foreach ($packages as $id_package => $package) {
                    $key .= $id_carrier . ',';
                    $price_with_tax += $carriers_price[$id_address][$id_package][$id_carrier]['with_tax'];
                    $price_without_tax += $carriers_price[$id_address][$id_package][$id_carrier]['without_tax'];
                    $package_list[] = $id_package;
                    $product_list = array_merge($product_list, $package['product_list']);
                }
                if (!isset($delivery_option_list[$id_address][$key])) {
                    $delivery_option_list[$id_address][$key] = ['is_best_price' => false, 'is_best_grade' => false, 'unique_carrier' => true, 'carrier_list' => [$id_carrier => ['price_with_tax' => $price_with_tax, 'price_without_tax' => $price_without_tax, 'instance' => $carriers_instance[$id_carrier], 'package_list' => $package_list, 'product_list' => $product_list]]];
                } else {
                    $delivery_option_list[$id_address][$key]['unique_carrier'] = count($delivery_option_list[$id_address][$key]['carrier_list']) <= 1;
                }
            }
        }
        /** @var Cart $this */
        $cart_rules = Cart_Rule::get_customer_cart_rules(Context::get_context()->cookie->id_lang, Context::get_context()->cookie->id_customer, true, true, false, $this, true);
        $cart_rules_in_cart = [];
        if ($this->id) {
            $result = Db::read_only()->get_array((new Db_Query())->select('*')->from('cart_cart_rule')->where('`id_cart` = ' . (int) $this->id));
            foreach ($result as $row) {
                $cart_rules_in_cart[] = $row['id_cart_rule'];
            }
        }
        $total_products_tax_included = $this->get_order_total(true, static::ONLY_PRODUCTS);
        $total_products = $this->get_order_total(false, static::ONLY_PRODUCTS);
        $free_carriers_rules = [];
        $context = Context::get_context();
        foreach ($cart_rules as $cart_rule) {
            $total_price = $cart_rule['minimum_amount_tax'] ? $total_products_tax_included : $total_products;
            $total_price += isset($real_best_price) && $cart_rule['minimum_amount_tax'] && $cart_rule['minimum_amount_shipping'] ? $real_best_price : 0;
            $total_price += isset($real_best_price_wt) && !$cart_rule['minimum_amount_tax'] && $cart_rule['minimum_amount_shipping'] ? $real_best_price_wt : 0;
            $condition = $cart_rule['free_shipping'] && $cart_rule['carrier_restriction'] && $cart_rule['minimum_amount'] <= $total_price ? 1 : 0;
            if (!empty($cart_rule['code'])) {
                $condition = $cart_rule['free_shipping'] && $cart_rule['carrier_restriction'] && in_array($cart_rule['id_cart_rule'], $cart_rules_in_cart) && $cart_rule['minimum_amount'] <= $total_price ? 1 : 0;
            }
            if ($condition) {
                $cr = new Cart_Rule((int) $cart_rule['id_cart_rule']);
                if (Validate::is_loaded_object($cr) && $cr->check_validity($context, in_array((int) $cart_rule['id_cart_rule'], $cart_rules_in_cart), false, false)) {
                    $carriers = $cr->get_associated_restrictions('carrier', true, false);
                    if (is_array($carriers) && count($carriers) && isset($carriers['selected'])) {
                        foreach ($carriers['selected'] as $carrier) {
                            if (isset($carrier['id_carrier']) && $carrier['id_carrier']) {
                                $free_carriers_rules[] = (int) $carrier['id_carrier'];
                            }
                        }
                    }
                }
            }
        }
        // For each delivery options :
        //    - Set the carrier list
        //    - Calculate the price
        //    - Calculate the average position
        foreach ($delivery_option_list as $id_address => $delivery_option) {
            foreach ($delivery_option as $key => $value) {
                $total_price_with_tax = 0;
                $total_price_without_tax = 0;
                $position = 0;
                foreach ($value['carrier_list'] as $id_carrier => $data) {
                    $total_price_with_tax += $data['price_with_tax'];
                    $total_price_without_tax += $data['price_without_tax'];
                    $total_price_without_tax_with_rules = in_array($id_carrier, $free_carriers_rules) ? 0 : $total_price_without_tax;
                    if (!isset($carrier_collection[$id_carrier])) {
                        $carrier_collection[$id_carrier] = new Carrier($id_carrier);
                    }
                    $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['instance'] = $carrier_collection[$id_carrier];
                    if ($source_image = Image_Manager::get_source_image(_PS_SHIP_IMG_DIR_, $id_carrier)) {
                        $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['logo'] = str_replace(_PS_SHIP_IMG_DIR_, _THEME_SHIP_DIR_, $source_image);
                    } else {
                        $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['logo'] = false;
                    }
                    $position += $carrier_collection[$id_carrier]->position;
                }
                if (!isset($total_price_without_tax_with_rules)) {
                    $total_price_without_tax_with_rules = false;
                }
                $delivery_option_list[$id_address][$key]['total_price_with_tax'] = $total_price_with_tax;
                $delivery_option_list[$id_address][$key]['total_price_without_tax'] = $total_price_without_tax;
                $delivery_option_list[$id_address][$key]['is_free'] = !$total_price_without_tax_with_rules;
                $delivery_option_list[$id_address][$key]['position'] = $position / count($value['carrier_list']);
            }
        }
        // Sort delivery option list
        foreach ($delivery_option_list as &$array) {
            uasort($array, ['Cart', 'sortDeliveryOptionList']);
        }
        return $delivery_option_list;
    }
    /**
     * Get products grouped by package and by addresses to be sent individualy (one package = one shipping cost).
     *
     * @param bool $flush
     *
     * @return array array(
     *                   0 => array( // First address
     *                       0 => array(  // First package
     *                           'product_list' => array(...),
     *                           'carrier_list' => array(...),
     *                           'id_warehouse' => array(...),
     *                       ),
     *                   ),
     *               );
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @todo Add availability check
     */
    public function get_package_list($flush = false)
    {
        static $cache = [];
        $cache_key = (int) $this->id . '_' . (int) $this->id_address_delivery;
        if (isset($cache[$cache_key]) && $cache[$cache_key] !== false && !$flush) {
            return $cache[$cache_key];
        }
        $product_list = $this->get_products($flush);
        // Step 1 : Get product informations (warehouse_list and carrier_list), count warehouse
        // Determine the best warehouse to determine the packages
        // For that we count the number of time we can use a warehouse for a specific delivery address
        $warehouse_count_by_address = [];
        $stock_management_active = Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT');
        foreach ($product_list as &$product) {
            if ((int) $product['id_address_delivery'] == 0) {
                $product['id_address_delivery'] = (int) $this->id_address_delivery;
            }
            if (!isset($warehouse_count_by_address[$product['id_address_delivery']])) {
                $warehouse_count_by_address[$product['id_address_delivery']] = [];
            }
            $product['warehouse_list'] = [];
            if ($stock_management_active && (int) $product['advanced_stock_management'] == 1) {
                $warehouse_list = Warehouse::get_product_warehouse_list($product['id_product'], $product['id_product_attribute'], $this->id_shop);
                if (!$warehouse_list) {
                    $warehouse_list = Warehouse::get_product_warehouse_list($product['id_product'], $product['id_product_attribute']);
                }
                if (!$warehouse_list) {
                    $warehouse_list = [['id_warehouse' => 0]];
                }
                // Does the product is in stock ?
                // If yes, get only warehouse where the product is in stock
                $warehouse_in_stock = [];
                $manager = Stock_Manager_Factory::get_manager();
                foreach ($warehouse_list as $warehouse) {
                    $product_real_quantities = $manager->get_product_real_quantities($product['id_product'], $product['id_product_attribute'], [$warehouse['id_warehouse']], true);
                    if ($product_real_quantities > 0 || Pack::is_pack((int) $product['id_product'])) {
                        $warehouse_in_stock[] = $warehouse;
                    }
                }
                if (!empty($warehouse_in_stock)) {
                    $warehouse_list = $warehouse_in_stock;
                    $product['in_stock'] = true;
                } else {
                    $product['in_stock'] = false;
                }
            } else {
                //simulate default warehouse
                $warehouse_list = [['id_warehouse' => 0]];
                $product['in_stock'] = Stock_Available::get_quantity_available_by_product($product['id_product'], $product['id_product_attribute']) > 0;
            }
            foreach ($warehouse_list as $warehouse) {
                $product['warehouse_list'][$warehouse['id_warehouse']] = $warehouse['id_warehouse'];
                if (!isset($warehouse_count_by_address[$product['id_address_delivery']][$warehouse['id_warehouse']])) {
                    $warehouse_count_by_address[$product['id_address_delivery']][$warehouse['id_warehouse']] = 0;
                }
                $warehouse_count_by_address[$product['id_address_delivery']][$warehouse['id_warehouse']]++;
            }
        }
        unset($product);
        arsort($warehouse_count_by_address);
        // Step 2 : Group product by warehouse
        $grouped_by_warehouse = [];
        foreach ($product_list as &$product) {
            if (!isset($grouped_by_warehouse[$product['id_address_delivery']])) {
                $grouped_by_warehouse[$product['id_address_delivery']] = ['in_stock' => [], 'out_of_stock' => []];
            }
            $product['carrier_list'] = [];
            $id_warehouse = 0;
            foreach ($warehouse_count_by_address[$product['id_address_delivery']] as $id_war => $val) {
                if (array_key_exists((int) $id_war, $product['warehouse_list'])) {
                    $carrier_list = Carrier::get_available_carrier_list(new Product((int) $product['id_product']), (int) $id_war, (int) $product['id_address_delivery'], null, $this, $error, (int) $product['id_product_attribute']);
                    $product['carrier_list'] = array_replace($product['carrier_list'], $carrier_list);
                    if (!$id_warehouse) {
                        $id_warehouse = (int) $id_war;
                    }
                }
            }
            if (!isset($grouped_by_warehouse[$product['id_address_delivery']]['in_stock'][$id_warehouse])) {
                $grouped_by_warehouse[$product['id_address_delivery']]['in_stock'][$id_warehouse] = [];
                $grouped_by_warehouse[$product['id_address_delivery']]['out_of_stock'][$id_warehouse] = [];
            }
            if (!$this->allow_seperated_package) {
                $key = 'in_stock';
            } else {
                $key = $product['in_stock'] ? 'in_stock' : 'out_of_stock';
                $product_quantity_in_stock = Stock_Available::get_quantity_available_by_product($product['id_product'], $product['id_product_attribute']);
                if ($product['in_stock'] && $product['cart_quantity'] > $product_quantity_in_stock) {
                    $out_stock_part = $product['cart_quantity'] - $product_quantity_in_stock;
                    $product_bis = $product;
                    $product_bis['cart_quantity'] = $out_stock_part;
                    $product_bis['in_stock'] = 0;
                    $product['cart_quantity'] -= $out_stock_part;
                    $grouped_by_warehouse[$product['id_address_delivery']]['out_of_stock'][$id_warehouse][] = $product_bis;
                }
            }
            if (empty($product['carrier_list'])) {
                $product['carrier_list'] = [static::NO_CARRIER_FOUND_PLACEHOLDER];
            }
            $grouped_by_warehouse[$product['id_address_delivery']][$key][$id_warehouse][] = $product;
        }
        unset($product);
        // Step 3 : grouped product from grouped_by_warehouse by available carriers
        $grouped_by_carriers = [];
        foreach ($grouped_by_warehouse as $id_address_delivery => $products_in_stock_list) {
            if (!isset($grouped_by_carriers[$id_address_delivery])) {
                $grouped_by_carriers[$id_address_delivery] = ['in_stock' => [], 'out_of_stock' => []];
            }
            foreach ($products_in_stock_list as $key => $warehouse_list) {
                if (!isset($grouped_by_carriers[$id_address_delivery][$key])) {
                    $grouped_by_carriers[$id_address_delivery][$key] = [];
                }
                foreach ($warehouse_list as $id_warehouse => $product_list) {
                    if (!isset($grouped_by_carriers[$id_address_delivery][$key][$id_warehouse])) {
                        $grouped_by_carriers[$id_address_delivery][$key][$id_warehouse] = [];
                    }
                    foreach ($product_list as $product) {
                        $package_carriers_key = implode(',', $product['carrier_list']);
                        if (!isset($grouped_by_carriers[$id_address_delivery][$key][$id_warehouse][$package_carriers_key])) {
                            $grouped_by_carriers[$id_address_delivery][$key][$id_warehouse][$package_carriers_key] = ['product_list' => [], 'carrier_list' => $product['carrier_list'], 'warehouse_list' => $product['warehouse_list']];
                        }
                        $grouped_by_carriers[$id_address_delivery][$key][$id_warehouse][$package_carriers_key]['product_list'][] = $product;
                    }
                }
            }
        }
        $package_list = [];
        // Step 4 : merge product from grouped_by_carriers into $package to minimize the number of package
        foreach ($grouped_by_carriers as $id_address_delivery => $products_in_stock_list) {
            if (!isset($package_list[$id_address_delivery])) {
                $package_list[$id_address_delivery] = ['in_stock' => [], 'out_of_stock' => []];
            }
            foreach ($products_in_stock_list as $key => $warehouse_list) {
                if (!isset($package_list[$id_address_delivery][$key])) {
                    $package_list[$id_address_delivery][$key] = [];
                }
                // Count occurance of each carriers to minimize the number of packages
                $carrier_count = [];
                foreach ($warehouse_list as $products_grouped_by_carriers) {
                    foreach ($products_grouped_by_carriers as $data) {
                        foreach ($data['carrier_list'] as $id_carrier) {
                            if (!isset($carrier_count[$id_carrier])) {
                                $carrier_count[$id_carrier] = 0;
                            }
                            $carrier_count[$id_carrier]++;
                        }
                    }
                }
                arsort($carrier_count);
                foreach ($warehouse_list as $id_warehouse => $products_grouped_by_carriers) {
                    if (!isset($package_list[$id_address_delivery][$key][$id_warehouse])) {
                        $package_list[$id_address_delivery][$key][$id_warehouse] = [];
                    }
                    foreach ($products_grouped_by_carriers as $data) {
                        foreach ($carrier_count as $id_carrier => $rate) {
                            if (array_key_exists($id_carrier, $data['carrier_list'])) {
                                if (!isset($package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier])) {
                                    $package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier] = ['carrier_list' => $data['carrier_list'], 'warehouse_list' => $data['warehouse_list'], 'product_list' => []];
                                }
                                $package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['carrier_list'] = array_intersect($package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['carrier_list'], $data['carrier_list']);
                                $package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['product_list'] = array_merge($package_list[$id_address_delivery][$key][$id_warehouse][$id_carrier]['product_list'], $data['product_list']);
                                break;
                            }
                        }
                    }
                }
            }
        }
        // Step 5 : Reduce depth of $package_list
        $final_package_list = [];
        foreach ($package_list as $id_address_delivery => $products_in_stock_list) {
            if (!isset($final_package_list[$id_address_delivery])) {
                $final_package_list[$id_address_delivery] = [];
            }
            foreach ($products_in_stock_list as $warehouse_list) {
                foreach ($warehouse_list as $id_warehouse => $products_grouped_by_carriers) {
                    foreach ($products_grouped_by_carriers as $data) {
                        $final_package_list[$id_address_delivery][] = ['product_list' => $data['product_list'], 'carrier_list' => $data['carrier_list'], 'warehouse_list' => $data['warehouse_list'], 'id_warehouse' => $id_warehouse];
                    }
                }
            }
        }
        $cache[$cache_key] = $final_package_list;
        return $final_package_list;
    }
    /**
     * Return package shipping cost
     *
     * @param int $idCarrier Carrier ID (default: current carrier)
     * @param bool $useTax
     * @param array|null $productList List of product concerned by the
     *                                     shipping. If null, all the product
     *                                     of the cart are used to calculate
     *                                     the shipping cost.
     * @param int|null $idZone
     *
     * @return bool|float Shipping total, rounded to
     *                    _TB_PRICE_DATABASE_PRECISION_, or false on failure.
     * @throws PrestaShopException
     */
    public function get_package_shipping_cost($id_carrier = null, $use_tax = true, ?Country $default_country = null, $product_list = null, $id_zone = null)
    {
        if ($this->is_virtual_cart()) {
            return 0.0;
        }
        if (!$default_country) {
            $default_country = Context::get_context()->country;
        }
        if (!is_null($product_list)) {
            foreach ($product_list as $key => $value) {
                if ($value['is_virtual'] == 1) {
                    unset($product_list[$key]);
                }
            }
        }
        if (is_null($product_list)) {
            $products = $this->get_products();
        } else {
            $products = $product_list;
        }
        if (Configuration::get('PS_TAX_ADDRESS_TYPE') == 'id_address_invoice') {
            $address_id = (int) $this->id_address_invoice;
        } elseif (is_array($product_list) && count($product_list)) {
            $prod = current($product_list);
            $address_id = (int) $prod['id_address_delivery'];
        } else {
            $address_id = null;
        }
        if (!Address::address_exists($address_id)) {
            $address_id = null;
        }
        if (is_null($id_carrier) && !empty($this->id_carrier)) {
            $id_carrier = (int) $this->id_carrier;
        }
        $cache_id = 'getPackageShippingCost_' . (int) $this->id . '_' . (int) $address_id . '_' . (int) $id_carrier . '_' . (int) $use_tax . '_' . (int) $default_country->id . '_' . (int) $id_zone;
        if ($products) {
            foreach ($products as $product) {
                $cache_id .= '_' . (int) $product['id_product'] . '_' . (int) $product['id_product_attribute'];
            }
        }
        if (Cache::is_stored($cache_id)) {
            return (float) Cache::retrieve($cache_id);
        }
        // Order total in default currency without fees
        $order_total = $this->get_order_total(true, static::ONLY_PHYSICAL_PRODUCTS_WITHOUT_SHIPPING, $product_list);
        // Start with shipping cost at 0
        $shipping_cost = 0.0;
        // If no product added, return 0
        if (!count($products)) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }
        if (!isset($id_zone)) {
            // Get id zone
            if (!$this->is_multi_address_delivery() && isset($this->id_address_delivery) && $this->id_address_delivery && Customer::customer_has_address($this->id_customer, $this->id_address_delivery)) {
                $id_zone = Address::get_zone_by_id((int) $this->id_address_delivery);
            } else {
                if (!Validate::is_loaded_object($default_country)) {
                    $default_country = new Country(Configuration::get('PS_COUNTRY_DEFAULT'), Configuration::get('PS_LANG_DEFAULT'));
                }
                $id_zone = (int) $default_country->id_zone;
            }
        }
        if ($id_carrier && !$this->is_carrier_in_range((int) $id_carrier, (int) $id_zone)) {
            $id_carrier = '';
        }
        if (empty($id_carrier) && $this->is_carrier_in_range((int) Configuration::get('PS_CARRIER_DEFAULT'), (int) $id_zone)) {
            $id_carrier = (int) Configuration::get('PS_CARRIER_DEFAULT');
        }
        $total_package_without_shipping_tax_inc = $this->get_order_total(true, static::BOTH_WITHOUT_SHIPPING, $product_list);
        if (empty($id_carrier)) {
            if ((int) $this->id_customer) {
                $customer = new Customer((int) $this->id_customer);
                $result = Carrier::get_carriers((int) Configuration::get('PS_LANG_DEFAULT'), true, false, (int) $id_zone, $customer->get_groups());
                unset($customer);
            } else {
                $result = Carrier::get_carriers((int) Configuration::get('PS_LANG_DEFAULT'), true, false, (int) $id_zone);
            }
            foreach ($result as $k => $row) {
                if ($row['id_carrier'] == Configuration::get('PS_CARRIER_DEFAULT')) {
                    continue;
                }
                if (!isset(static::$_carriers[$row['id_carrier']])) {
                    static::$_carriers[$row['id_carrier']] = new Carrier((int) $row['id_carrier']);
                }
                $carrier = static::$_carriers[$row['id_carrier']];
                $shipping_method = $carrier->get_shipping_method();
                // Get only carriers that are compliant with shipping method
                if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT && $carrier->get_max_delivery_price_by_weight((int) $id_zone) === false || $shipping_method == Carrier::SHIPPING_METHOD_PRICE && $carrier->get_max_delivery_price_by_price((int) $id_zone) === false) {
                    unset($result[$k]);
                    continue;
                }
                // If out-of-range behavior carrier is set to "Deactivate carrier"
                if ($row['range_behavior']) {
                    $check_delivery_price_by_weight = Carrier::check_delivery_price_by_weight($row['id_carrier'], $this->get_total_weight(), (int) $id_zone);
                    $total_order = $total_package_without_shipping_tax_inc;
                    $check_delivery_price_by_price = Carrier::check_delivery_price_by_price($row['id_carrier'], $total_order, (int) $id_zone, (int) $this->id_currency);
                    // Get only carriers that have a range compatible with cart
                    if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT && !$check_delivery_price_by_weight || $shipping_method == Carrier::SHIPPING_METHOD_PRICE && !$check_delivery_price_by_price) {
                        unset($result[$k]);
                        continue;
                    }
                }
                if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT) {
                    $shipping = $carrier->get_delivery_price_by_weight($this->get_total_weight($product_list), (int) $id_zone);
                } else {
                    $shipping = $carrier->get_delivery_price_by_price($order_total, (int) $id_zone, (int) $this->id_currency);
                }
                if (!isset($min_shipping_price)) {
                    $min_shipping_price = $shipping;
                }
                if ($shipping <= $min_shipping_price) {
                    $id_carrier = (int) $row['id_carrier'];
                    $min_shipping_price = $shipping;
                }
            }
        }
        if (empty($id_carrier)) {
            $id_carrier = Configuration::get('PS_CARRIER_DEFAULT');
        }
        if (!isset(static::$_carriers[$id_carrier])) {
            static::$_carriers[$id_carrier] = new Carrier((int) $id_carrier, Configuration::get('PS_LANG_DEFAULT'));
        }
        $carrier = static::$_carriers[$id_carrier];
        // No valid Carrier or $id_carrier <= 0 ?
        if (!Validate::is_loaded_object($carrier)) {
            Cache::store($cache_id, 0.0);
            return 0.0;
        }
        $shipping_method = $carrier->get_shipping_method();
        if (!$carrier->active) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }
        // Free fees if free carrier
        if ($carrier->is_free == 1) {
            Cache::store($cache_id, 0.0);
            return 0.0;
        }
        // calculate carrier tax
        if (Tax::exclude_taxe_option()) {
            $carrier_tax = 0.0;
        } else if (Carrier::use_proportionate_tax()) {
            $carrier_tax = 100.0 * $this->get_average_products_tax_rate();
        } else {
            $address = Address::initialize((int) $address_id);
            $carrier_tax = $carrier->get_taxes_rate($address);
        }
        $configuration = Configuration::get_multiple(['PS_SHIPPING_FREE_PRICE', 'PS_SHIPPING_HANDLING', 'PS_SHIPPING_METHOD', 'PS_SHIPPING_FREE_WEIGHT']);
        // Free fees
        $free_fees_price = 0;
        if (isset($configuration['PS_SHIPPING_FREE_PRICE'])) {
            $free_fees_price = Tools::convert_price((float) $configuration['PS_SHIPPING_FREE_PRICE'], Currency::get_currency_instance((int) $this->id_currency));
        }
        $order_total_with_discounts = $this->get_order_total(true, static::BOTH_WITHOUT_SHIPPING, null, null, false);
        if ($order_total_with_discounts >= (float) $free_fees_price && (float) $free_fees_price > 0) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }
        if (isset($configuration['PS_SHIPPING_FREE_WEIGHT']) && $this->get_total_weight() >= (float) $configuration['PS_SHIPPING_FREE_WEIGHT'] && (float) $configuration['PS_SHIPPING_FREE_WEIGHT'] > 0) {
            Cache::store($cache_id, $shipping_cost);
            return $shipping_cost;
        }
        // Get shipping cost using correct method
        if ($carrier->range_behavior) {
            if (!isset($id_zone)) {
                // Get id zone
                if (isset($this->id_address_delivery) && $this->id_address_delivery && Customer::customer_has_address($this->id_customer, $this->id_address_delivery)) {
                    $id_zone = Address::get_zone_by_id((int) $this->id_address_delivery);
                } else {
                    $id_zone = (int) $default_country->id_zone;
                }
            }
            if (!($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT && !Carrier::check_delivery_price_by_weight($carrier->id, $this->get_total_weight(), (int) $id_zone) || $shipping_method == Carrier::SHIPPING_METHOD_PRICE && !Carrier::check_delivery_price_by_price($carrier->id, $total_package_without_shipping_tax_inc, $id_zone, (int) $this->id_currency))) {
                if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT) {
                    $shipping_cost += $carrier->get_delivery_price_by_weight($this->get_total_weight($product_list), $id_zone);
                } else {
                    // by price
                    $shipping_cost += $carrier->get_delivery_price_by_price($order_total, $id_zone, (int) $this->id_currency);
                }
            }
        } else if ($shipping_method == Carrier::SHIPPING_METHOD_WEIGHT) {
            $shipping_cost += $carrier->get_delivery_price_by_weight($this->get_total_weight($product_list), $id_zone);
        } else {
            $shipping_cost += $carrier->get_delivery_price_by_price($order_total, $id_zone, (int) $this->id_currency);
        }
        // Adding handling charges
        if (isset($configuration['PS_SHIPPING_HANDLING']) && $carrier->shipping_handling) {
            $shipping_cost += (float) $configuration['PS_SHIPPING_HANDLING'];
        }
        // Additional Shipping Cost per product
        foreach ($products as $product) {
            if (!$product['is_virtual']) {
                $shipping_cost += $product['additional_shipping_cost'] * $product['cart_quantity'];
            }
        }
        $shipping_cost = Tools::convert_price($shipping_cost, Currency::get_currency_instance((int) $this->id_currency));
        //get external shipping cost from module
        if ($carrier->shipping_external) {
            $module_name = $carrier->external_module_name;
            /** @var CarrierModule $module */
            $module = Module::get_instance_by_name($module_name);
            if (Validate::is_loaded_object($module)) {
                if (property_exists($module, 'id_carrier')) {
                    $module->id_carrier = $carrier->id;
                }
                if ($carrier->need_range) {
                    if (method_exists($module, 'getPackageShippingCost')) {
                        $shipping_cost = $module->get_package_shipping_cost($this, $shipping_cost, $products);
                    } else {
                        $shipping_cost = $module->get_order_shipping_cost($this, $shipping_cost);
                    }
                } else {
                    $shipping_cost = $module->get_order_shipping_cost_external($this);
                }
                // Check if carrier is available
                if ($shipping_cost === false) {
                    Cache::store($cache_id, false);
                    return false;
                }
            } else {
                Cache::store($cache_id, false);
                return false;
            }
        }
        $shipping_cost = $this->adjust_shipping_cost_with_tax($use_tax, $carrier->prices_with_tax, $carrier_tax, $shipping_cost);
        $shipping_cost = round($shipping_cost, _TB_PRICE_DATABASE_PRECISION_);
        Cache::store($cache_id, $shipping_cost);
        return $shipping_cost;
    }
    /**
     * Helper method to adds or subtract tax from shipping cost
     *
     * @param boolean $returnPriceWithTax
     *                  - if true, shipping cost including tax will be returned
     *                  - if false, shipping cost excluding tax will be returned
     * @param boolean $priceIncludesTax if true, then $value already includes tax
     * @param float $taxRate tax rate, ie. 21
     * @param float $value input shipping cost
     */
    private function adjust_shipping_cost_with_tax($return_price_with_tax, $price_includes_tax, $tax_rate, $value): float
    {
        $value = (float) $value;
        if ($return_price_with_tax) {
            if ($price_includes_tax) {
                // price already contains tax
                return $value;
            }
            // we want price with tax, but value is without tax. Let's add tax amount
            return $value * (1.0 + $tax_rate / 100.0);
        }
        if ($price_includes_tax) {
            // price includes tax but we want price without tax. Let's remove tax amount
            return $value / (1.0 + $tax_rate / 100.0);
        }
        // price is already without tax
        return $value;
    }
    /**
     * Does the cart use multiple address
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_multi_address_delivery()
    {
        static $cache = [];
        if (!isset($cache[$this->id])) {
            $sql = new Db_Query();
            $sql->select('count(distinct id_address_delivery)');
            $sql->from('cart_product', 'cp');
            $sql->where('id_cart = ' . (int) $this->id);
            $cache[$this->id] = Db::read_only()->get_value($sql) > 1;
        }
        return $cache[$this->id];
    }
    /**
     * isCarrierInRange
     *
     * Check if the specified carrier is in range
     *
     * @param int $idCarrier
     * @param int $idZone
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function is_carrier_in_range($id_carrier, $id_zone)
    {
        $carrier = new Carrier((int) $id_carrier, Configuration::get('PS_LANG_DEFAULT'));
        if (!$carrier->range_behavior) {
            return true;
        }
        return match ($carrier->get_shipping_method()) {
            Carrier::SHIPPING_METHOD_FREE => true,
            Carrier::SHIPPING_METHOD_WEIGHT => Carrier::check_delivery_price_by_weight((int) $id_carrier, $this->get_total_weight(), $id_zone),
            Carrier::SHIPPING_METHOD_PRICE => Carrier::check_delivery_price_by_price((int) $id_carrier, $this->get_order_total(true, static::BOTH_WITHOUT_SHIPPING), $id_zone, (int) $this->id_currency),
            default => false,
        };
    }
    /**
     * Return cart weight
     *
     * @param array|null $products
     *
     * @return float Cart weight
     *
     * @throws PrestaShopException
     */
    public function get_total_weight($products = null)
    {
        if (!is_null($products)) {
            $total_weight = 0;
            foreach ($products as $product) {
                if (!isset($product['weight_attribute'])) {
                    $total_weight += $product['weight'] * $product['cart_quantity'];
                } else {
                    $total_weight += $product['weight_attribute'] * $product['cart_quantity'];
                }
            }
            return $total_weight;
        }
        if (!isset(static::$_total_weight[$this->id])) {
            $connection = Db::read_only();
            if (Combination::is_feature_active()) {
                $weight_product_with_attribute = $connection->get_value((new Db_Query())->select('SUM((p.`weight` + pa.`weight`) * cp.`quantity`) AS `nb`')->from('cart_product', 'cp')->left_join('product', 'p', 'cp.`id_product` = p.`id_product`')->left_join('product_attribute', 'pa', 'cp.`id_product_attribute` = pa.`id_product_attribute`')->where('cp.`id_product_attribute` IS NOT NULL')->where('cp.`id_product_attribute` != 0')->where('cp.`id_cart` = ' . (int) $this->id));
            } else {
                $weight_product_with_attribute = 0;
            }
            $weight_product_without_attribute = $connection->get_value((new Db_Query())->select('SUM(p.`weight` * cp.`quantity`) AS `nb`')->from('cart_product', 'cp')->left_join('product', 'p', 'cp.`id_product` = p.`id_product`')->where('cp.`id_product_attribute` IS NULL OR cp.`id_product_attribute` = 0')->where('cp.`id_cart` = ' . (int) $this->id));
            static::$_total_weight[$this->id] = round((float) $weight_product_with_attribute + (float) $weight_product_without_attribute, 6);
        }
        return static::$_total_weight[$this->id];
    }
    /**
     * The arguments are optional and only serve as return values in case
     * caller needs the details.
     *
     * @param float|null $amountTaxExcluded
     * @param float|null $amountTaxIncluded
     *
     * @return float
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_average_products_tax_rate(&$amount_tax_excluded = null, &$amount_tax_included = null)
    {
        $amount_tax_included = $this->get_order_total(true, static::ONLY_PRODUCTS);
        $amount_tax_excluded = $this->get_order_total(false, static::ONLY_PRODUCTS);
        $tax = $amount_tax_included - $amount_tax_excluded;
        if ($tax == 0 || $amount_tax_excluded == 0) {
            return 0.0;
        }
        return $tax / $amount_tax_excluded;
    }
    /**
     * Get the gift wrapping price
     *
     * @param bool $withTaxes With or without taxes
     * @param int|null $idAddress Address ID
     *
     * @return float wrapping price
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_gift_wrapping_price($with_taxes = true, $id_address = null)
    {
        static $address = [];
        $wrapping_fees = (float) Configuration::get('PS_GIFT_WRAPPING_PRICE');
        if ($wrapping_fees <= 0) {
            return $wrapping_fees;
        }
        if ($with_taxes) {
            if (Carrier::use_proportionate_tax()) {
                $wrapping_fees = round($wrapping_fees * (1 + $this->get_average_products_tax_rate()), _TB_PRICE_DATABASE_PRECISION_);
            } else {
                if (!isset($address[$this->id])) {
                    if ($id_address === null) {
                        $id_address = (int) $this->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
                    }
                    try {
                        $address[$this->id] = Address::initialize($id_address);
                    } catch (Exception) {
                        $address[$this->id] = new Address();
                        $address[$this->id]->id_country = Configuration::get('PS_COUNTRY_DEFAULT');
                    }
                }
                $tax_manager = Tax_Manager_Factory::get_manager($address[$this->id], (int) Configuration::get('PS_GIFT_WRAPPING_TAX_RULES_GROUP'));
                $tax_calculator = $tax_manager->get_tax_calculator();
                $wrapping_fees = $tax_calculator->add_taxes($wrapping_fees);
            }
        }
        return $wrapping_fees;
    }
    /**
     * @param int $filter
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_cart_rules($filter = Cart_Rule::FILTER_ACTION_ALL)
    {
        // If the cart has not been saved, then there can't be any cart rule applied
        if (!Cart_Rule::is_feature_active() || !$this->id) {
            return [];
        }
        $cache_key = 'static::getCartRules_' . $this->id . '-' . $filter;
        if (!Cache::is_stored($cache_key)) {
            $result = Db::read_only()->get_array((new Db_Query())->select('cr.*, crl.`id_lang`, crl.`name`, cd.`id_cart`')->from('cart_cart_rule', 'cd')->left_join('cart_rule', 'cr', 'cd.`id_cart_rule` = cr.`id_cart_rule`')->left_join('cart_rule_lang', 'crl', 'cd.`id_cart_rule` = crl.`id_cart_rule` AND crl.`id_lang` = ' . (int) $this->id_lang)->where('`id_cart` = ' . (int) $this->id)->where((int) $filter === Cart_Rule::FILTER_ACTION_SHIPPING ? '`free_shipping` = 1' : '')->where((int) $filter === Cart_Rule::FILTER_ACTION_GIFT ? '`gift_product` = 1' : '')->where((int) $filter === Cart_Rule::FILTER_ACTION_REDUCTION ? '`reduction_percent` != 0 OR `reduction_amount` != 0' : '')->order_by('cr.`priority` ASC'));
            Cache::store($cache_key, $result);
        } else {
            /** @var array $result */
            $result = Cache::retrieve($cache_key);
        }
        // Define virtual context to prevent case where the cart is not the in the global context
        $virtual_context = Context::get_context()->clone_context();
        $virtual_context->cart = $this;
        foreach ($result as &$row) {
            $cart_rule = new Cart_Rule();
            $cart_rule->hydrate($row);
            $row['obj'] = $cart_rule;
            $row['value_real'] = $cart_rule->get_contextual_value(true, $virtual_context, $filter);
            $row['value_tax_exc'] = $cart_rule->get_contextual_value(false, $virtual_context, $filter);
            // Retro compatibility < 1.5.0.2
            $row['id_discount'] = $row['id_cart_rule'];
            $row['description'] = $row['name'];
        }
        return $result;
    }
    /*
     ** Customization management
     */
    /**
     * @param array $deliveryOption
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function get_id_carrier_from_delivery_option($delivery_option)
    {
        $delivery_option_list = $this->get_delivery_option_list();
        foreach ($delivery_option as $key => $value) {
            if (isset($delivery_option_list[$key][$value])) {
                if (count($delivery_option_list[$key][$value]['carrier_list']) == 1) {
                    return current(array_keys($delivery_option_list[$key][$value]['carrier_list']));
                }
            }
        }
        return 0;
    }
    /**
     * Sort list of option delivery by parameters define in the BO
     *
     * @param array $option1
     * @param array $option2
     *
     * @return int -1 if $option 1 must be placed before and 1 if the $option1 must be placed after the $option2
     *
     * @throws PrestaShopException
     */
    public static function sort_delivery_option_list($option1, $option2)
    {
        static $order_by_price = null;
        static $order_way = null;
        if (is_null($order_by_price)) {
            $order_by_price = !Configuration::get('PS_CARRIER_DEFAULT_SORT');
        }
        if (is_null($order_way)) {
            $order_way = Configuration::get('PS_CARRIER_DEFAULT_ORDER');
        }
        if ($order_by_price) {
            if ($order_way) {
                return ($option1['total_price_with_tax'] < $option2['total_price_with_tax']) * 2 - 1;
            }
            return ($option1['total_price_with_tax'] >= $option2['total_price_with_tax']) * 2 - 1;
        }
        // return -1 or 1
        if ($order_way) {
            return ($option1['position'] < $option2['position']) * 2 - 1;
        }
        return ($option1['position'] >= $option2['position']) * 2 - 1;
        // return -1 or 1
    }
    /**
     * Translate a int option_delivery identifier (3240002000) in a string ('24,3,')
     *
     * @param int $int
     * @param string $delimiter
     *
     * @return string
     */
    public static function desintifier($int, $delimiter = ',')
    {
        $int = (string) $int;
        if (strlen($int) > 0) {
            $delimiter_len = (int) $int[0];
            $int = strrev(substr($int, 1));
            $elm = explode(str_repeat('0', $delimiter_len + 1), $int);
            return strrev(implode($delimiter, $elm));
        }
        return '';
    }
    /**
     * @param int $idCustomer
     *
     * @return bool|int
     *
     * @throws PrestaShopException
     */
    public static function last_none_ordered_cart($id_customer)
    {
        if (!$id_cart = Db::read_only()->get_value((new Db_Query())->select('c.`id_cart`')->from('cart', 'c')->where('NOT EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'orders o WHERE o.`id_cart` = c.`id_cart`AND o.`id_customer` = ' . (int) $id_customer . ')')->where('c.`id_customer` = ' . (int) $id_customer . ' ' . Shop::add_sql_restriction(Shop::SHARE_ORDER, 'c'))->order_by('c.`date_upd` DESC'))) {
            return false;
        }
        return (int) $id_cart;
    }
    /**
     * Build cart object from provided id_order
     *
     * @param int $idOrder
     *
     * @return Cart|bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_cart_by_order_id($id_order)
    {
        if ($id_cart = static::get_cart_id_by_order_id($id_order)) {
            return new Cart((int) $id_cart);
        }
        return false;
    }
    /**
     * @param int $idOrder
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_cart_id_by_order_id($id_order)
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('`id_cart`')->from('orders')->where('`id_order` = ' . (int) $id_order));
    }
    /**
     * @param int $idCustomer
     * @param bool $dontRejectOrdered if true, all carts will be returned, otherwise
     *             already ordered carts will be filtered out
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_customer_carts($id_customer, $dont_reject_ordered = true)
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('cart', 'c')->where('c.`id_customer` = ' . (int) $id_customer)->where($dont_reject_ordered ? '' : 'NOT EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'orders o WHERE o.`id_cart` = c.`id_cart`)')->order_by('c.`date_add` DESC'));
    }
    /**
     * @param string $echo
     * @param array $tr
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function replace_zero_by_shop_name($echo, $tr)
    {
        return $echo == '0' ? Carrier::get_carrier_name_from_shop_name() : $echo;
    }
    /**
     * isGuestCartByCartId
     *
     * @param int $idCart
     *
     * @return bool true if cart has been made by a guest customer
     *
     * @throws PrestaShopException
     */
    public static function is_guest_cart_by_cart_id($id_cart)
    {
        if (!(int) $id_cart) {
            return false;
        }
        return (bool) Db::read_only()->get_value((new Db_Query())->select('`is_guest`')->from('customer', 'cu')->left_join('cart', 'ca', 'ca.`id_customer` = cu.`id_customer`')->where('ca.`id_cart` = ' . (int) $id_cart));
    }
    /**
     * Execute hook displayCarrierList (extraCarrier) and merge theme to the $array
     *
     * @param array $array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function add_extra_carriers(&$array): void
    {
        $first = true;
        $hook_extracarrier_addr = [];
        foreach (Context::get_context()->cart->get_address_collection() as $address) {
            $hook = Hook::display_hook('displayCarrierList', ['address' => $address]);
            $hook_extracarrier_addr[$address->id] = $hook;
            if ($first) {
                $array = array_merge($array, ['HOOK_EXTRACARRIER' => $hook]);
                $first = false;
            }
            $array = array_merge($array, ['HOOK_EXTRACARRIER_ADDR' => $hook_extracarrier_addr]);
        }
    }
    /**
     * Get all delivery addresses object for the current cart
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_address_collection()
    {
        $collection = [];
        $cache_id = 'static::getAddressCollection' . (int) $this->id;
        if (!Cache::is_stored($cache_id)) {
            $result = Db::read_only()->get_array((new Db_Query())->select('DISTINCT `id_address_delivery`')->from('cart_product')->where('`id_cart` = ' . (int) $this->id));
            Cache::store($cache_id, $result);
        } else {
            $result = Cache::retrieve($cache_id);
        }
        $result[] = ['id_address_delivery' => (int) $this->id_address_delivery];
        foreach ($result as $row) {
            if ((int) $row['id_address_delivery'] != 0) {
                $collection[(int) $row['id_address_delivery']] = new Address((int) $row['id_address_delivery']);
            }
        }
        return $collection;
    }
    /**
     * Update the address id of the cart
     *
     * @param int $idAddress Current address id to change
     * @param int $idAddressNew New address id
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_address_id($id_address, $id_address_new): void
    {
        if (Validate::is_loaded_object($this)) {
            $to_update = false;
            if (!isset($this->id_address_invoice) || $this->id_address_invoice == $id_address) {
                $to_update = true;
                $this->id_address_invoice = $id_address_new;
            }
            if (!isset($this->id_address_delivery) || $this->id_address_delivery == $id_address) {
                $to_update = true;
                $this->id_address_delivery = $id_address_new;
            }
            if ($to_update) {
                $this->update();
            }
            $conn = Db::get_instance();
            $cart_id = (int) $this->id;
            $conn->update('cart_product', ['id_address_delivery' => (int) $id_address_new], '`id_cart` = ' . $cart_id . ' AND `id_address_delivery` = ' . (int) $id_address);
            $conn->update('customization', ['id_address_delivery' => (int) $id_address_new], '`id_cart` = ' . $cart_id . ' AND `id_address_delivery` = ' . (int) $id_address);
        }
    }
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
        if (isset(static::$_nb_products[$this->id])) {
            unset(static::$_nb_products[$this->id]);
        }
        if (isset(static::$_total_weight[$this->id])) {
            unset(static::$_total_weight[$this->id]);
        }
        $this->_products = null;
        $return = parent::update($null_values);
        Hook::trigger_event('actionCartSave', ['cart' => $this]);
        return $return;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if ($this->order_exists()) {
            //NOT delete a cart which is associated with an order
            return false;
        }
        $uploaded_files = Db::read_only()->get_array((new Db_Query())->select('cd.`value`, cd.`type`')->from('customized_data', 'cd')->inner_join('customization', 'c', 'cd.`id_customization` = c.`id_customization`')->where('cd.`type` = 0')->where('c.`id_cart` = ' . (int) $this->id));
        foreach ($uploaded_files as $must_unlink) {
            $this->delete_customization_file($must_unlink);
        }
        $conn = Db::get_instance();
        $conn->delete('customized_data', '`id_customization` IN (SELECT `id_customization` FROM `' . _DB_PREFIX_ . 'customization` WHERE `id_cart`=' . (int) $this->id . ')');
        $conn->delete('customization', '`id_cart` = ' . (int) $this->id);
        if (!$conn->delete('cart_cart_rule', '`id_cart` = ' . (int) $this->id) || !$conn->delete('cart_product', '`id_cart` = ' . (int) $this->id)) {
            return false;
        }
        return parent::delete();
    }
    /**
     * Check if order has already been placed
     *
     * @return bool result
     *
     * @throws PrestaShopException
     */
    public function order_exists()
    {
        $cache_id = 'static::orderExists_' . (int) $this->id;
        if (!Cache::is_stored($cache_id)) {
            $result = (bool) Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from('orders')->where('`id_cart` = ' . (int) $this->id));
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * @deprecated 1.0.0, use Cart->getCartRules()
     *
     * @param bool $lite
     * @param bool $refresh
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_discounts($lite = false, $refresh = false)
    {
        Tools::display_as_deprecated();
        return $this->get_cart_rules();
    }
    /**
     * Return the cart rules Ids on the cart.
     *
     * @param int $filter
     *
     * @return array
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public function get_ordered_cart_rules_ids($filter = Cart_Rule::FILTER_ACTION_ALL)
    {
        $cache_key = 'static::getOrderedCartRulesIds_' . $this->id . '-' . $filter . '-ids';
        if (!Cache::is_stored($cache_key)) {
            $result = Db::read_only()->get_array((new Db_Query())->select('cr.`id_cart_rule`')->from('cart_cart_rule', 'cd')->left_join('cart_rule', 'cr', 'cd.`id_cart_rule` = cr.`id_cart_rule`')->left_join('cart_rule_lang', 'crl', 'cd.`id_cart_rule` = crl.`id_cart_rule` AND crl.`id_lang` = ' . (int) $this->id_lang)->where('cd.`id_cart` = ' . (int) $this->id)->where($filter === Cart_Rule::FILTER_ACTION_SHIPPING ? 'cr.`free_shipping` = 1' : '')->where($filter === Cart_Rule::FILTER_ACTION_GIFT ? 'cr.`gift_product` = 1' : '')->where($filter === Cart_Rule::FILTER_ACTION_REDUCTION ? 'cr.`reduction_percent` != 0 OR cr.`reduction_amount` != 0' : '')->order_by('cr.`priority` ASC'));
            Cache::store($cache_key, $result);
        } else {
            $result = Cache::retrieve($cache_key);
        }
        return $result;
    }
    /**
     * @param int $idCartRule
     *
     * @return int|null
     *
     * @throws PrestaShopException
     */
    public function get_discounts_customer($id_cart_rule)
    {
        if (!Cart_Rule::is_feature_active()) {
            return 0;
        }
        $cache_id = 'static::getDiscountsCustomer_' . (int) $this->id . '-' . (int) $id_cart_rule;
        if (!Cache::is_stored($cache_id)) {
            $result = (int) Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from('cart_cart_rule')->where('`id_cart_rule` = ' . (int) $id_cart_rule)->where('`id_cart` = ' . (int) $this->id));
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_last_product()
    {
        $result = Db::read_only()->get_row((new Db_Query())->select('`id_product`, `id_product_attribute`, `id_shop`')->from('cart_product', 'cp')->where('`id_cart` = ' . (int) $this->id)->order_by('`date_upd` DESC, `date_add` DESC'));
        if ($result && isset($result['id_product']) && $result['id_product']) {
            foreach ($this->get_products() as $product) {
                if ($result['id_product'] == $product['id_product'] && (!$result['id_product_attribute'] || $result['id_product_attribute'] == $product['id_product_attribute'])) {
                    return $product;
                }
            }
        }
        return false;
    }
    /**
     * Return cart products quantity
     *
     * @return int Products quantity
     *
     * @throws PrestaShopException
     */
    public function nb_products()
    {
        if (!$this->id) {
            return 0;
        }
        return static::get_nb_products($this->id);
    }
    /**
     * @param int $id
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_nb_products($id)
    {
        // Must be strictly compared to NULL, or else an empty cart will bypass the cache and add dozens of queries
        if (isset(static::$_nb_products[$id]) && static::$_nb_products[$id] !== null) {
            return static::$_nb_products[$id];
        }
        static::$_nb_products[$id] = (int) Db::read_only()->get_value((new Db_Query())->select('SUM(`quantity`)')->from('cart_product')->where('`id_cart` = ' . (int) $id));
        return static::$_nb_products[$id];
    }
    /**
     * @deprecated 1.0.0, use Cart->addCartRule()
     *
     * @param int $idCartRule
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function add_discount($id_cart_rule)
    {
        Tools::display_as_deprecated();
        return $this->add_cart_rule($id_cart_rule);
    }
    /**
     * @param int $idCartRule
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add_cart_rule($id_cart_rule)
    {
        // You can't add a cart rule that does not exist
        $cart_rule = new Cart_Rule($id_cart_rule, Context::get_context()->language->id);
        if (!Validate::is_loaded_object($cart_rule)) {
            return false;
        }
        if (Db::read_only()->get_value((new Db_Query())->select('`id_cart_rule`')->from('cart_cart_rule')->where('`id_cart_rule` = ' . (int) $id_cart_rule)->where('`id_cart` = ' . (int) $this->id))) {
            return false;
        }
        // Add the cart rule to the cart
        if (!Db::get_instance()->insert('cart_cart_rule', ['id_cart_rule' => (int) $id_cart_rule, 'id_cart' => (int) $this->id])) {
            return false;
        }
        Cache::clean('static::getCartRules_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_ALL);
        Cache::clean('static::getCartRules_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_SHIPPING);
        Cache::clean('static::getCartRules_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_REDUCTION);
        Cache::clean('static::getCartRules_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_GIFT);
        Cache::clean('static::getOrderedCartRulesIds_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_ALL . '-ids');
        Cache::clean('static::getOrderedCartRulesIds_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_SHIPPING . '-ids');
        Cache::clean('static::getOrderedCartRulesIds_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_REDUCTION . '-ids');
        Cache::clean('static::getOrderedCartRulesIds_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_GIFT . '-ids');
        if ((int) $cart_rule->gift_product) {
            $this->update_qty(1, $cart_rule->gift_product, $cart_rule->gift_product_attribute, false, 'up', 0, null, false);
        }
        return true;
    }
    /**
     * Update product quantity
     *
     * @param int $quantity Quantity to add (or substract)
     * @param int $idProduct Product ID
     * @param int|null $idProductAttribute Attribute ID if needed
     * @param int|bool $idCustomization
     * @param string $operator Indicate if quantity must be increased or decreased
     * @param int $idAddressDelivery
     * @param bool $autoAddCartRule
     *
     * @return bool|int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_qty($quantity, $id_product, $id_product_attribute = null, $id_customization = false, $operator = 'up', $id_address_delivery = 0, ?Shop $shop = null, $auto_add_cart_rule = true)
    {
        if (!$shop) {
            $shop = Context::get_context()->shop;
        }
        if (Context::get_context()->customer->id) {
            if ($id_address_delivery == 0 && (int) $this->id_address_delivery) {
                // The $id_address_delivery is null, use the cart delivery address
                $id_address_delivery = $this->id_address_delivery;
            } elseif ($id_address_delivery == 0) {
                // The $id_address_delivery is null, get the default customer address
                $id_address_delivery = (int) Address::get_first_customer_address_id((int) Context::get_context()->customer->id);
            } elseif (!Customer::customer_has_address(Context::get_context()->customer->id, $id_address_delivery)) {
                // The $id_address_delivery must be linked with customer
                $id_address_delivery = 0;
            }
        }
        $quantity = (int) $quantity;
        $id_product = (int) $id_product;
        $id_product_attribute = (int) $id_product_attribute;
        $product = new Product($id_product, false, Configuration::get('PS_LANG_DEFAULT'), $shop->id);
        if (!Validate::is_loaded_object($product)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Product with id %s not found'), $id_product));
        }
        if ($id_product_attribute) {
            $combination = new Combination((int) $id_product_attribute);
            if ($combination->id_product != $id_product) {
                return false;
            }
        }
        /* If we have a product combination, the minimal quantity is set with the one of this combination */
        if (!empty($id_product_attribute)) {
            $minimal_quantity = (int) Product_Attribute::get_attribute_minimal_qty($id_product_attribute);
        } else {
            $minimal_quantity = (int) $product->minimal_quantity;
        }
        if (isset(static::$_nb_products[$this->id])) {
            unset(static::$_nb_products[$this->id]);
        }
        if (isset(static::$_total_weight[$this->id])) {
            unset(static::$_total_weight[$this->id]);
        }
        Hook::trigger_event('actionBeforeCartUpdateQty', ['cart' => $this, 'product' => $product, 'id_product_attribute' => $id_product_attribute, 'id_customization' => $id_customization, 'quantity' => $quantity, 'operator' => $operator, 'id_address_delivery' => $id_address_delivery, 'shop' => $shop, 'auto_add_cart_rule' => $auto_add_cart_rule]);
        if ($quantity <= 0) {
            return $this->delete_product($id_product, $id_product_attribute, (int) $id_customization, 0, $auto_add_cart_rule);
        }
        if (!$product->available_for_order || Configuration::get('PS_CATALOG_MODE') && !defined('_PS_ADMIN_DIR_')) {
            return false;
        }
        /* Check if the product is already in the cart */
        $result = $this->contains_product($id_product, $id_product_attribute, (int) $id_customization, (int) $id_address_delivery);
        /* Update quantity if product already exist */
        $conn = Db::get_instance();
        if ($result) {
            if ($operator == 'up') {
                $result2 = $conn->get_row((new Db_Query())->select('stock.`out_of_stock`, IFNULL(stock.`quantity`, 0) AS `quantity`')->from('product', 'p')->join(Product::sql_stock('p', $id_product_attribute, true, $shop))->where('p.`id_product` = ' . $id_product));
                $product_qty = (int) $result2['quantity'];
                // Quantity for product pack
                if (Pack::is_pack($id_product)) {
                    $product_qty = Pack::get_quantity($id_product, $id_product_attribute);
                }
                $new_qty = (int) $result['quantity'] + $quantity;
                $qty = '+ ' . $quantity;
                if (!Product::is_available_when_out_of_stock((int) $result2['out_of_stock'])) {
                    if ($new_qty > $product_qty) {
                        return false;
                    }
                }
            } elseif ($operator == 'down') {
                $qty = '- ' . $quantity;
                $new_qty = (int) $result['quantity'] - $quantity;
                if ($new_qty < $minimal_quantity && $minimal_quantity > 1) {
                    return -1;
                }
            } else {
                return false;
            }
            /* Delete product from cart */
            if ($new_qty <= 0) {
                return $this->delete_product($id_product, $id_product_attribute, (int) $id_customization, 0, $auto_add_cart_rule);
            }
            /* Delete product from cart */
            if ($new_qty < $minimal_quantity) {
                return -1;
            }
            $conn->update('cart_product', ['quantity' => ['type' => 'sql', 'value' => '`quantity` ' . $qty], 'date_upd' => ['type' => 'sql', 'value' => 'NOW()']], '`id_product` = ' . $id_product . (!empty($id_product_attribute) ? ' AND `id_product_attribute` = ' . (int) $id_product_attribute : '') . ' AND `id_cart` = ' . (int) $this->id . (Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->is_multi_address_delivery() ? ' AND `id_address_delivery` = ' . (int) $id_address_delivery : ''), 1);
        } elseif ($operator == 'up') {
            /* Add product to the cart */
            $result2 = $conn->get_row((new Db_Query())->select('stock.`out_of_stock`, IFNULL(stock.`quantity`, 0) AS `quantity`')->from('product', 'p')->join(Product::sql_stock('p', $id_product_attribute, true, $shop))->where('p.`id_product` = ' . $id_product));
            // Quantity for product pack
            if (Pack::is_pack($id_product)) {
                $result2['quantity'] = Pack::get_quantity($id_product, $id_product_attribute);
            }
            if (!Product::is_available_when_out_of_stock((int) $result2['out_of_stock'])) {
                if ($quantity > $result2['quantity']) {
                    return false;
                }
            }
            if ($quantity < $minimal_quantity) {
                return -1;
            }
            $result_add = $conn->insert('cart_product', ['id_product' => $id_product, 'id_product_attribute' => $id_product_attribute, 'id_cart' => (int) $this->id, 'id_address_delivery' => (int) $id_address_delivery, 'id_shop' => $shop->id, 'quantity' => $quantity, 'date_add' => date('Y-m-d H:i:s'), 'date_upd' => date('Y-m-d H:i:s')]);
            if (!$result_add) {
                return false;
            }
        }
        // refresh cache of static::_products
        $this->_products = $this->get_products(true);
        $this->update();
        $context = Context::get_context()->clone_context();
        $context->cart = $this;
        Cache::clean('getContextualValue_*');
        if ($auto_add_cart_rule) {
            Cart_Rule::auto_add_to_cart($context);
        }
        if ($product->customizable) {
            return $this->_update_customization_quantity($quantity, (int) $id_customization, $id_product, $id_product_attribute, (int) $id_address_delivery, $operator);
        }
        return true;
    }
    /**
     * Delete a product from the cart
     *
     * @param int $idProduct Product ID
     * @param int $idProductAttribute Attribute ID if needed
     * @param int $idCustomization Customization id
     * @param int $idAddressDelivery
     * @param bool $autoAddCartRule
     *
     * @return bool result
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function delete_product($id_product, $id_product_attribute = null, $id_customization = null, $id_address_delivery = 0, $auto_add_cart_rule = true)
    {
        if (isset(static::$_nb_products[$this->id])) {
            unset(static::$_nb_products[$this->id]);
        }
        if (isset(static::$_total_weight[$this->id])) {
            unset(static::$_total_weight[$this->id]);
        }
        $read_conn = Db::read_only();
        if ((int) $id_customization) {
            $product_total_quantity = (int) $read_conn->get_value((new Db_Query())->select('`quantity`')->from('cart_product')->where('`id_cart` = ' . (int) $this->id)->where('`id_product` = ' . (int) $id_product)->where('`id_product_attribute` = ' . (int) $id_product_attribute));
            $customization_quantity = (int) $read_conn->get_value((new Db_Query())->select('`quantity`')->from('customization')->where('`id_cart` = ' . (int) $this->id)->where('`id_product` = ' . (int) $id_product)->where('`id_product_attribute` = ' . (int) $id_product_attribute)->where($id_address_delivery ? '`id_address_delivery` = ' . (int) $id_address_delivery : ''));
            if (!$this->_delete_customization((int) $id_customization, (int) $id_product, (int) $id_product_attribute, (int) $id_address_delivery)) {
                return false;
            }
            // refresh cache of static::_products
            $this->_products = $this->get_products(true);
            return $customization_quantity == $product_total_quantity && $this->delete_product((int) $id_product, (int) $id_product_attribute, null, (int) $id_address_delivery);
        }
        /* Get customization quantity */
        $quantity = (int) $read_conn->get_value((new Db_Query())->select('SUM(`quantity`) AS quantity')->from('customization')->where('`id_cart` = ' . (int) $this->id)->where('`id_product` = ' . (int) $id_product)->where('`id_product_attribute` = ' . (int) $id_product_attribute));
        $conditions = ['id_product = ' . (int) $id_product, 'id_cart = ' . (int) $this->id];
        if (!is_null($id_product_attribute)) {
            $conditions[] = 'id_product_attribute = ' . (int) $id_product_attribute;
        }
        if ((int) $id_address_delivery) {
            $conditions[] = 'id_address_delivery = ' . (int) $id_address_delivery;
        }
        /* If the product still possesses customization it does not have to be deleted */
        $write_con = Db::get_instance();
        if ($quantity) {
            return $write_con->update('cart_product', ['quantity' => $quantity], $conditions);
        }
        /* Product deletion */
        $result = $write_con->delete('cart_product', $conditions);
        // Remove any specific price for this cart/product combination
        Specific_Price::delete_by_id_cart((int) $this->id, (int) $id_product, (int) $id_product_attribute);
        if ($result) {
            $return = $this->update();
            // refresh cache of static::_products
            $this->_products = $this->get_products(true);
            Cart_Rule::auto_remove_from_cart();
            if ($auto_add_cart_rule) {
                Cart_Rule::auto_add_to_cart();
            }
            return $return;
        }
        return false;
    }
    /**
     * Delete a customization from the cart. If customization is a Picture,
     * then the image is also deleted
     *
     * @param int $idCustomization
     *
     * @return bool result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function _delete_customization($id_customization, $id_product, $id_product_attribute, $id_address_delivery = 0)
    {
        $result = true;
        $conn = Db::get_instance();
        $customization = $conn->get_row((new Db_Query())->select('*')->from('customization')->where('`id_customization` = ' . (int) $id_customization));
        if ($customization) {
            $cust_data = $conn->get_row((new Db_Query())->select('*')->from('customized_data')->where('`id_customization` = ' . (int) $id_customization));
            // Delete customization picture if necessary
            if ($cust_data) {
                $result = $this->delete_customization_file($cust_data);
            }
            $result = $conn->delete('customized_data', '`id_customization` = ' . (int) $id_customization) && $result;
            $result = $conn->update('cart_product', ['quantity' => ['type' => 'sql', 'value' => '`quantity` - ' . (int) $customization['quantity']]], '`id_cart` = ' . (int) $this->id . ' AND `id_product` = ' . (int) $id_product . ((int) $id_product_attribute ? ' AND `id_product_attribute` = ' . (int) $id_product_attribute : '') . ' AND `id_address_delivery` = ' . (int) $id_address_delivery) && $result;
            $result = $conn->delete('customization', '`id_customization` = ' . (int) $id_customization) && $result;
        }
        return $result;
    }
    /**
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $idCustomization
     * @param int $idAddressDelivery
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function contains_product($id_product, $id_product_attribute = 0, $id_customization = 0, $id_address_delivery = 0)
    {
        $sql = (new Db_Query())->select('cp.`quantity`')->from('cart_product', 'cp');
        if ($id_customization) {
            $sql->left_join('customization', 'c', 'c.`id_product` = cp.`id_product`');
            $sql->where('c.`id_product_attribute` = cp.`id_product_attribute`');
        }
        $sql->where('cp.`id_product` = ' . (int) $id_product);
        $sql->where('cp.`id_product_attribute` = ' . (int) $id_product_attribute);
        $sql->where('cp.`id_cart` = ' . (int) $this->id);
        if (Configuration::get('PS_ALLOW_MULTISHIPPING') && $this->is_multi_address_delivery()) {
            $sql->where('cp.`id_address_delivery` = ' . (int) $id_address_delivery);
        }
        if ($id_customization) {
            $sql->where('c.`id_customization` = ' . (int) $id_customization);
        }
        return Db::read_only()->get_row($sql);
    }
    /**
     * @param int $quantityChange Quantity change
     * @param int $idCustomization Customization ID
     * @param int $idProduct Product ID
     * @param int $idProductAttribute Product Attribute ID
     * @param int $idAddressDelivery Address ID
     * @param string $operator `up` or `down`
     *
     * @return bool
     *
     * @deprecated 2.0.0
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    protected function _update_customization_quantity($quantity_change, $id_customization, $id_product, $id_product_attribute, $id_address_delivery, $operator = 'up')
    {
        // Link customization to product combination when it is first added to cart
        $conn = Db::get_instance();
        if (empty($id_customization) && $operator === 'up') {
            $customization = $this->get_product_customization($id_product, null, true);
            foreach ($customization as $field) {
                if ((int) $field['quantity'] === 0) {
                    $conn->update('customization', ['quantity' => (int) $quantity_change, 'id_product' => (int) $id_product, 'id_product_attribute' => (int) $id_product_attribute, 'id_address_delivery' => (int) $id_address_delivery, 'in_cart' => true], '`id_customization` = ' . (int) $field['id_customization']);
                }
            }
        }
        /* Quantity update */
        if (!empty($id_customization)) {
            $result = (int) Db::read_only()->get_value((new Db_Query())->select('`quantity`')->from('customization')->where('`id_customization` = ' . (int) $id_customization));
            if ($operator === 'down' && $result - (int) $quantity_change < 1) {
                return $conn->delete('customization', '`id_customization` = ' . (int) $id_customization);
            }
            return $conn->update('customization', ['quantity' => ['type' => 'sql', 'value' => '`quantity` ' . ($operator === 'up' ? '+' : '-') . (int) $quantity_change], 'id_address_delivery' => (int) $id_address_delivery, 'in_cart' => true], '`id_customization` = ' . (int) $id_customization);
        }
        // refresh cache of static::_products
        $this->_products = $this->get_products(true);
        $this->update();
        return true;
    }
    /**
     * Return custom pictures in this cart for a specified product
     *
     * @param int $idProduct
     * @param int $type only return customization of this type
     * @param bool $notInCart only return customizations that are not in cart already
     *
     * @return array result rows
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_product_customization($id_product, $type = null, $not_in_cart = false)
    {
        if (!Customization::is_feature_active()) {
            return [];
        }
        return Db::read_only()->get_array((new Db_Query())->select('cu.`id_customization`, cd.`index`, cd.`value`, cd.`type`, cu.`in_cart`, cu.`quantity`')->from('customization', 'cu')->left_join('customized_data', 'cd', 'cu.`id_customization` = cd.`id_customization`')->where('cu.`id_cart` = ' . (int) $this->id)->where('cu.`id_product` = ' . (int) $id_product)->where($type === Product::CUSTOMIZE_FILE ? 'cd.`type` = ' . (int) Product::CUSTOMIZE_FILE : '')->where($type === Product::CUSTOMIZE_TEXTFIELD ? 'cd.`type` = ' . (int) Product::CUSTOMIZE_TEXTFIELD : '')->where($not_in_cart ? 'cu.`in_cart` = 0' : ''));
    }
    /**
     * @deprecated 1.0.0, use Cart->removeCartRule()
     *
     * @param int $idCartRule
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_discount($id_cart_rule)
    {
        Tools::display_as_deprecated();
        return $this->remove_cart_rule($id_cart_rule);
    }
    /**
     * @param int $idCartRule
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function remove_cart_rule($id_cart_rule)
    {
        Cache::clean('static::getCartRules_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_ALL);
        Cache::clean('static::getCartRules_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_SHIPPING);
        Cache::clean('static::getCartRules_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_REDUCTION);
        Cache::clean('static::getCartRules_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_GIFT);
        Cache::clean('static::getCartRules_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_ALL . '-ids');
        Cache::clean('static::getCartRules_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_SHIPPING . '-ids');
        Cache::clean('static::getCartRules_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_REDUCTION . '-ids');
        Cache::clean('static::getCartRules_' . $this->id . '-' . Cart_Rule::FILTER_ACTION_GIFT . '-ids');
        $result = Db::get_instance()->delete('cart_cart_rule', '`id_cart_rule` = ' . (int) $id_cart_rule . ' AND `id_cart` = ' . (int) $this->id, 1);
        $cart_rule = new Cart_Rule($id_cart_rule, Configuration::get('PS_LANG_DEFAULT'));
        if ((int) $cart_rule->gift_product) {
            $this->update_qty(1, $cart_rule->gift_product, $cart_rule->gift_product_attribute, null, 'down', 0, null, false);
        }
        return $result;
    }
    /**
     * Get the number of packages
     *
     * @return int number of packages
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_nb_of_packages()
    {
        static $nb_packages = [];
        if (!isset($nb_packages[$this->id])) {
            $nb_packages[$this->id] = 0;
            foreach ($this->get_package_list() as $by_address) {
                $nb_packages[$this->id] += count($by_address);
            }
        }
        return $nb_packages[$this->id];
    }
    /**
     * @param array $package
     * @param int|null $idCarrier
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_package_id_warehouse($package, $id_carrier = null)
    {
        if ($id_carrier === null) {
            if (isset($package['id_carrier'])) {
                $id_carrier = (int) $package['id_carrier'];
            }
        }
        if ($id_carrier == null) {
            return $package['id_warehouse'];
        }
        foreach ($package['warehouse_list'] as $id_warehouse) {
            $warehouse = new Warehouse((int) $id_warehouse);
            $available_warehouse_carriers = $warehouse->get_carriers();
            if (in_array($id_carrier, $available_warehouse_carriers)) {
                return (int) $id_warehouse;
            }
        }
        return 0;
    }
    /**
     * @param int $idCarrier
     * @param int $idAddress
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function carrier_is_selected($id_carrier, $id_address)
    {
        $delivery_option = $this->get_delivery_option();
        $delivery_option_list = $this->get_delivery_option_list();
        if (!isset($delivery_option[$id_address])) {
            return false;
        }
        if (!isset($delivery_option_list[$id_address][$delivery_option[$id_address]])) {
            return false;
        }
        if (!in_array($id_carrier, array_keys($delivery_option_list[$id_address][$delivery_option[$id_address]]['carrier_list']))) {
            return false;
        }
        return true;
    }
    /**
     * Get all deliveries options available for the current cart formated like Carriers::getCarriersForOrder
     * This method was wrote for retrocompatibility with 1.4 theme
     * New theme need to use static::getDeliveryOptionList() to generate carriers option in the checkout process
     *
     * @param bool $flush Force flushing cache
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function simulate_carriers_output(?Country $default_country = null, $flush = false)
    {
        $delivery_option_list = $this->get_delivery_option_list($default_country, $flush);
        // This method cannot work if there is multiple address delivery
        if (count($delivery_option_list) > 1 || empty($delivery_option_list)) {
            return [];
        }
        $carriers = [];
        foreach (reset($delivery_option_list) as $key => $option) {
            $price = $option['total_price_with_tax'];
            $price_tax_excluded = $option['total_price_without_tax'];
            $name = '';
            $img = '';
            $delay = '';
            if ($option['unique_carrier']) {
                $carrier = reset($option['carrier_list']);
                if (isset($carrier['instance'])) {
                    $name = $carrier['instance']->name;
                    $delay = $carrier['instance']->delay;
                    $delay = $delay[Context::get_context()->language->id] ?? $delay[(int) Configuration::get('PS_LANG_DEFAULT')];
                }
                if (isset($carrier['logo'])) {
                    $img = $carrier['logo'];
                }
            } else {
                $name_list = [];
                foreach ($option['carrier_list'] as $carrier) {
                    $name_list[] = $carrier['instance']->name;
                }
                $name = implode(' -', $name_list);
            }
            $carriers[] = [
                'name' => $name,
                'img' => $img,
                'delay' => $delay,
                'price' => $price,
                'price_tax_exc' => $price_tax_excluded,
                'id_carrier' => static::intifier($key),
                // Need to translate to an integer for retrocompatibility reason, in 1.4 template we used intval
                'is_module' => false,
            ];
        }
        return $carriers;
    }
    /**
     * Translate a string option_delivery identifier ('24,3,') in a int (3240002000)
     *
     * The  option_delivery identifier is a list of integers separated by a ','.
     * This method replace the delimiter by a sequence of '0'.
     * The size of this sequence is fixed by the first digit of the return
     *
     * @param string $string
     * @param string $delimiter
     *
     * @return string
     */
    public static function intifier($string, $delimiter = ',')
    {
        $elm = explode($delimiter, $string);
        $max = max($elm);
        return strlen($max) . implode(str_repeat('0', strlen($max) + 1), $elm);
    }
    /**
     * @param bool $useCache
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function simulate_carrier_selected_output($use_cache = true)
    {
        $delivery_option = $this->get_delivery_option(null, false, $use_cache);
        if (count($delivery_option) > 1 || empty($delivery_option)) {
            return 0;
        }
        return static::intifier(reset($delivery_option));
    }
    /**
     * Return shipping total of a specific carriers for the cart
     *
     * @param int $idCarrier
     * @param bool $useTax
     * @param array|null $deliveryOption Array of the delivery option for each address
     *
     * @return float Shipping total
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_carrier_cost($id_carrier, $use_tax = true, ?Country $default_country = null, $delivery_option = null)
    {
        if (is_null($delivery_option)) {
            $delivery_option = $this->get_delivery_option($default_country);
        }
        $total_shipping = 0;
        $delivery_option_list = $this->get_delivery_option_list();
        foreach ($delivery_option as $id_address => $key) {
            if (!isset($delivery_option_list[$id_address])) {
                continue;
            }
            if (!isset($delivery_option_list[$id_address][$key])) {
                continue;
            }
            if (isset($delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier])) {
                if ($use_tax) {
                    $total_shipping += $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['price_with_tax'];
                } else {
                    $total_shipping += $delivery_option_list[$id_address][$key]['carrier_list'][$id_carrier]['price_without_tax'];
                }
            }
        }
        return $total_shipping;
    }
    /**
     * @deprecated 1.0.0, use static::getPackageShippingCost
     *
     * @param int|null $idCarrier
     * @param bool $useTax
     * @param array|null $productList
     *
     * @return bool|float
     * @throws PrestaShopException
     */
    public function get_order_shipping_cost($id_carrier = null, $use_tax = true, ?Country $default_country = null, $product_list = null)
    {
        Tools::display_as_deprecated();
        return $this->get_package_shipping_cost((int) $id_carrier, $use_tax, $default_country, $product_list);
    }
    /**
     * @deprecated 1.0.0
     *
     * @param CartRule $obj
     * @param mixed $discounts
     * @param mixed $orderTotal
     * @param mixed $products
     * @param bool $checkCartDiscount
     *
     * @return bool|string
     * @throws PrestaShopException
     */
    public function check_discount_validity($obj, $discounts, $order_total, $products, $check_cart_discount = false)
    {
        Tools::display_as_deprecated();
        $context = Context::get_context()->clone_context();
        $context->cart = $this;
        return $obj->check_validity($context);
    }
    /**
     * Return useful informations for cart
     *
     * @param int|null $idLang
     * @param bool $refresh
     *
     * @return array Cart details
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_summary_details($id_lang = null, $refresh = false)
    {
        $context = Context::get_context();
        if (!$id_lang) {
            $id_lang = $context->language->id;
        }
        $delivery = new Address((int) $this->id_address_delivery);
        $invoice = new Address((int) $this->id_address_invoice);
        // New layout system with personalization fields
        $formatted_addresses = ['delivery' => Address_Format::get_formatted_layout_data($delivery), 'invoice' => Address_Format::get_formatted_layout_data($invoice)];
        $base_total_tax_inc = $this->get_order_total(true);
        $base_total_tax_exc = $this->get_order_total(false);
        $total_tax = $base_total_tax_inc - $base_total_tax_exc;
        if ($total_tax < 0) {
            $total_tax = 0;
        }
        $products = $this->get_products($refresh);
        foreach ($products as &$product) {
            $product['price_without_quantity_discount'] = Product::get_price_static($product['id_product'], !Product::get_tax_calculation_method(), $product['id_product_attribute'], _TB_PRICE_DATABASE_PRECISION_, null, false, false);
            if ($product['reduction_type'] == 'amount') {
                $reduction = (!Product::get_tax_calculation_method() ? (float) $product['price_wt'] : (float) $product['price']) - (float) $product['price_without_quantity_discount'];
                $product['reduction_formatted'] = Tools::display_price($reduction);
            }
        }
        $gift_products = [];
        $cart_rules = $this->get_cart_rules();
        $total_shipping = $this->get_total_shipping_cost();
        $total_shipping_tax_exc = $this->get_total_shipping_cost(null, false);
        $total_products_wt = $this->get_order_total(true, static::ONLY_PRODUCTS);
        $total_products = $this->get_order_total(false, static::ONLY_PRODUCTS);
        $total_discounts = $this->get_order_total(true, static::ONLY_DISCOUNTS);
        $total_discounts_tax_exc = $this->get_order_total(false, static::ONLY_DISCOUNTS);
        // The cart content is altered for display
        $decimals = Currency::get_currency_instance($this->id_currency)->get_display_precision();
        foreach ($cart_rules as &$cart_rule) {
            // If the cart rule is automatic (wihtout any code) and include free shipping, it should not be displayed as a cart rule but only set the shipping cost to 0
            if ($cart_rule['free_shipping'] && (empty($cart_rule['code']) || preg_match('/^' . Cart_Rule::BO_ORDER_CODE_PREFIX . '[0-9]+/', (string) $cart_rule['code']))) {
                $cart_rule['value_real'] -= $total_shipping;
                $cart_rule['value_tax_exc'] -= $total_shipping_tax_exc;
                $cart_rule['value_real'] = Tools::ps_round($cart_rule['value_real'], $decimals);
                $cart_rule['value_tax_exc'] = Tools::ps_round($cart_rule['value_tax_exc'], $decimals);
                if ($total_discounts > $cart_rule['value_real']) {
                    $total_discounts -= $total_shipping;
                }
                if ($total_discounts_tax_exc > $cart_rule['value_tax_exc']) {
                    $total_discounts_tax_exc -= $total_shipping_tax_exc;
                }
                // Update total shipping
                $total_shipping = 0;
                $total_shipping_tax_exc = 0;
            }
            if ($cart_rule['gift_product']) {
                foreach ($products as $key => &$product) {
                    if (empty($product['gift']) && $product['id_product'] == $cart_rule['gift_product'] && $product['id_product_attribute'] == $cart_rule['gift_product_attribute']) {
                        // Update total products
                        $total_products_wt = Tools::ps_round($total_products_wt - $product['price_wt'], $decimals);
                        $total_products = Tools::ps_round($total_products - $product['price'], $decimals);
                        // Update total discounts
                        $total_discounts = $total_discounts - $product['price_wt'];
                        $total_discounts_tax_exc = $total_discounts_tax_exc - $product['price'];
                        // Update cart rule value
                        $cart_rule['value_real'] = Tools::ps_round($cart_rule['value_real'] - $product['price_wt'], $decimals);
                        $cart_rule['value_tax_exc'] = Tools::ps_round($cart_rule['value_tax_exc'] - $product['price'], $decimals);
                        // Update product quantity
                        $product['total_wt'] = Tools::ps_round($product['total_wt'] - $product['price_wt'], $decimals);
                        $product['total'] = Tools::ps_round($product['total'] - $product['price'], $decimals);
                        $product['cart_quantity']--;
                        if (!$product['cart_quantity']) {
                            unset($products[$key]);
                        }
                        // Add a new product line
                        $gift_product = $product;
                        $gift_product['cart_quantity'] = 1;
                        $gift_product['price'] = 0;
                        $gift_product['price_wt'] = 0;
                        $gift_product['total_wt'] = 0;
                        $gift_product['total'] = 0;
                        $gift_product['gift'] = true;
                        $gift_products[] = $gift_product;
                        break;
                        // One gift product per cart rule
                    }
                }
            }
        }
        foreach ($cart_rules as $key => &$cart_rule) {
            if ((float) $cart_rule['value_real'] == 0 && (int) $cart_rule['free_shipping'] == 0) {
                unset($cart_rules[$key]);
            }
        }
        $errors = $this->get_delivery_error_reasons();
        $summary = ['delivery' => $delivery, 'delivery_state' => State::get_name_by_id($delivery->id_state), 'invoice' => $invoice, 'invoice_state' => State::get_name_by_id($invoice->id_state), 'formattedAddresses' => $formatted_addresses, 'products' => array_values($products), 'gift_products' => $gift_products, 'discounts' => array_values($cart_rules), 'is_virtual_cart' => (int) $this->is_virtual_cart(), 'total_discounts' => $total_discounts, 'total_discounts_tax_exc' => $total_discounts_tax_exc, 'total_wrapping' => $this->get_order_total(true, static::ONLY_WRAPPING), 'total_wrapping_tax_exc' => $this->get_order_total(false, static::ONLY_WRAPPING), 'total_shipping' => $total_shipping, 'total_shipping_tax_exc' => $total_shipping_tax_exc, 'total_products_wt' => $total_products_wt, 'total_products' => $total_products, 'total_price' => $base_total_tax_inc, 'total_tax' => $total_tax, 'total_price_without_tax' => $base_total_tax_exc, 'is_multi_address_delivery' => $this->is_multi_address_delivery() || Tools::get_int_value('multi-shipping') == 1, 'free_ship' => !$total_shipping && !$errors, 'carrier' => new Carrier($this->id_carrier, $id_lang), 'errors' => $errors];
        foreach (Hook::get_responses('actionCartSummary', $summary) as $hook_response) {
            if (is_array($hook_response)) {
                $summary = array_merge($summary, $hook_response);
            }
        }
        return $summary;
    }
    /**
     * Returns array of reasons why this cart can't be delivered
     *
     * @return string[]
     *
     * @throws PrestaShopException
     */
    public function get_delivery_error_reasons()
    {
        if ($this->is_virtual_cart()) {
            return [];
        }
        $reasons = [];
        $errors = [];
        $addresses_without_carriers = $this->get_delivery_addresses_without_carriers(false, $errors);
        if ($addresses_without_carriers) {
            if ($errors) {
                $unique_errors = array_unique(array_values($errors));
                foreach ($unique_errors as $error) {
                    switch ((int) $error) {
                        case Carrier::SHIPPING_WEIGHT_EXCEPTION:
                            $reasons[] = Tools::display_error('The product selection cannot be delivered by the available carrier(s): it is too heavy. Please amend your cart to lower its weight.', !Tools::get_value('ajax'));
                            break;
                        case Carrier::SHIPPING_PRICE_EXCEPTION:
                            $reasons[] = Tools::display_error('The product selection cannot be delivered by the available carrier(s). Please amend your cart.', !Tools::get_value('ajax'));
                            break;
                        case Carrier::SHIPPING_SIZE_EXCEPTION:
                            $reasons[] = Tools::display_error('The product selection cannot be delivered by the available carrier(s): its size does not fit. Please amend your cart to reduce its size.', !Tools::get_value('ajax'));
                            break;
                        default:
                            trigger_error("Unknown carrier error reason: {$error}", E_USER_WARNING);
                            $reasons[] = Tools::display_error('The product selection cannot be delivered by the available carrier(s): unknown reason', !Tools::get_value('ajax'));
                            break;
                    }
                }
            } else {
                $reasons[] = Tools::display_error('There are no carriers that deliver to the address you selected.', !Tools::get_value('ajax'));
            }
        }
        return $reasons;
    }
    /**
     * Get all the ids of the delivery addresses without carriers
     *
     * @param bool $returnCollection Return a collection
     * @param array $error contains an error message if an error occurs
     *
     * @return array Array of address id or of address object
     * @throws PrestaShopException
     */
    public function get_delivery_addresses_without_carriers($return_collection = false, &$error = [])
    {
        $addresses_without_carriers = [];
        foreach ($this->get_products() as $product) {
            if (!in_array($product['id_address_delivery'], $addresses_without_carriers)) {
                $carrier_list = Carrier::get_available_carrier_list(new Product((int) $product['id_product']), 0, (int) $product['id_address_delivery'], null, $this, $error, (int) $product['id_product_attribute']);
                if (!$carrier_list) {
                    $addresses_without_carriers[] = $product['id_address_delivery'];
                }
            }
        }
        if (!$return_collection) {
            return $addresses_without_carriers;
        }
        $addresses_instance_without_carriers = [];
        foreach ($addresses_without_carriers as $id_address) {
            $addresses_instance_without_carriers[] = new Address($id_address);
        }
        return $addresses_instance_without_carriers;
    }
    /**
     * @param bool $returnProduct
     *
     * @return bool|mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function check_quantities($return_product = false)
    {
        if (Configuration::get('PS_CATALOG_MODE') && !defined('_PS_ADMIN_DIR_')) {
            return false;
        }
        foreach ($this->get_products() as $product) {
            if (!$this->allow_seperated_package && !$product['allow_oosp'] && Stock_Available::depends_on_stock($product['id_product']) && $product['advanced_stock_management'] && Context::get_context()->customer->is_logged() && ($delivery = $this->get_delivery_option()) && !empty($delivery)) {
                $product['stock_quantity'] = Stock_Manager::get_stock_by_carrier((int) $product['id_product'], (int) $product['id_product_attribute'], $delivery);
            }
            if (!$product['active'] || !$product['available_for_order'] || !$product['allow_oosp'] && $product['stock_quantity'] < $product['cart_quantity']) {
                return $return_product ? $product : false;
            }
        }
        return true;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function check_products_access()
    {
        if (Configuration::get('PS_CATALOG_MODE')) {
            return true;
        }
        foreach ($this->get_products() as $product) {
            if (!Product::check_access_static($product['id_product'], $this->id_customer)) {
                return $product['id_product'];
            }
        }
        return false;
    }
    /**
     * Add customer's text
     *
     * @param int $idProduct
     * @param int $index
     * @param int $type
     * @param string $textValue
     *
     * @return bool Always true
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_text_field_to_product($id_product, $index, $type, $text_value)
    {
        return $this->_add_customization($id_product, 0, $index, $type, $text_value, 0);
    }
    /**
     * Add customization item to database
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $index
     * @param int $type
     * @param string $field
     * @param int $quantity
     *
     * @return bool success
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function _add_customization($id_product, $id_product_attribute, $index, $type, $field, $quantity)
    {
        $exising_customization = Db::read_only()->get_array((new Db_Query())->select('cu.`id_customization`, cd.`index`, cd.`value`, cd.`type`')->from('customization', 'cu')->left_join('customized_data', 'cd', 'cu.`id_customization` = cd.`id_customization`')->where('cu.`id_cart` = ' . (int) $this->id)->where('cu.`id_product` = ' . (int) $id_product)->where('`in_cart` = 0'));
        $conn = Db::get_instance();
        if ($exising_customization) {
            // If the customization field is already filled, delete it
            foreach ($exising_customization as $customization) {
                if ($customization['type'] == $type && $customization['index'] == $index) {
                    $conn->delete('customized_data', 'id_customization = ' . (int) $customization['id_customization'] . ' AND type = ' . (int) $customization['type'] . ' AND `index` = ' . (int) $customization['index']);
                    $this->delete_customization_file($customization);
                    break;
                }
            }
            $id_customization = $exising_customization[0]['id_customization'];
        } else {
            $conn->insert('customization', ['id_cart' => (int) $this->id, 'id_product' => (int) $id_product, 'id_product_attribute' => (int) $id_product_attribute, 'quantity' => (int) $quantity]);
            $id_customization = $conn->Insert_ID();
        }
        if (!$conn->insert('customized_data', ['id_customization' => (int) $id_customization, 'type' => (int) $type, 'index' => (int) $index, 'value' => p_sql($field)])) {
            return false;
        }
        return true;
    }
    /**
     * Add customer's pictures
     *
     * @param int $idProduct
     * @param int $index
     * @param int $type
     * @param string $file
     *
     * @return bool Always true
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_picture_to_product($id_product, $index, $type, $file)
    {
        return $this->_add_customization($id_product, 0, $index, $type, $file, 0);
    }
    /**
     * @param int $idProduct
     * @param int $index
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function delete_picture_to_product($id_product, $index)
    {
        Tools::display_as_deprecated();
        return $this->delete_customization_to_product($id_product, 0);
    }
    /**
     * Remove a customer's customization
     *
     * @param int $idProduct
     * @param int $index
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete_customization_to_product($id_product, $index)
    {
        $cust_data = Db::read_only()->get_row((new Db_Query())->select('cu.`id_customization`, cd.`index`, cd.`value`, cd.`type`')->from('customization', 'cu')->left_join('customized_data', 'cd', 'cu.`id_customization` = cd.`id_customization`')->where('cu.`id_cart` = ' . (int) $this->id)->where('cu.`id_product` = ' . (int) $id_product)->where('`index` = ' . (int) $index)->where('`in_cart` = 0'));
        if ($cust_data) {
            // Delete customization picture if necessary
            $result = $this->delete_customization_file($cust_data);
            return Db::get_instance()->delete('customized_data', '`id_customization` = ' . (int) $cust_data['id_customization'] . ' AND `index` = ' . (int) $index) && $result;
        }
        return true;
    }
    /**
     * @return false|array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function duplicate()
    {
        if (!Validate::is_loaded_object($this)) {
            return false;
        }
        $cart = new Cart($this->id);
        $cart->id = null;
        $cart->id_shop = $this->id_shop;
        $cart->id_shop_group = $this->id_shop_group;
        if (!Customer::customer_has_address((int) $cart->id_customer, (int) $cart->id_address_delivery)) {
            $cart->id_address_delivery = (int) Address::get_first_customer_address_id((int) $cart->id_customer);
        }
        if (!Customer::customer_has_address((int) $cart->id_customer, (int) $cart->id_address_invoice)) {
            $cart->id_address_invoice = (int) Address::get_first_customer_address_id((int) $cart->id_customer);
        }
        if ($cart->id_customer) {
            $cart->secure_key = static::$_customer->secure_key;
        }
        $cart->add();
        if (!Validate::is_loaded_object($cart)) {
            return false;
        }
        $conn = Db::get_instance();
        $success = true;
        $products = $conn->get_array((new Db_Query())->select('*')->from('cart_product')->where('`id_cart` = ' . (int) $this->id));
        $product_gift = $conn->get_array((new Db_Query())->select('cr.`gift_product`, cr.`gift_product_attribute`')->from('cart_rule', 'cr')->left_join('order_cart_rule', 'ocr', 'ocr.`id_cart_rule` = cr.`id_cart_rule`')->where('ocr.`id_order` = ' . (int) $this->id));
        $id_address_delivery = Configuration::get('PS_ALLOW_MULTISHIPPING') ? $cart->id_address_delivery : 0;
        foreach ($products as $product) {
            if ($id_address_delivery) {
                if (Customer::customer_has_address((int) $cart->id_customer, $product['id_address_delivery'])) {
                    $id_address_delivery = $product['id_address_delivery'];
                }
            }
            foreach ($product_gift as $gift) {
                if (isset($gift['gift_product']) && isset($gift['gift_product_attribute']) && (int) $gift['gift_product'] == (int) $product['id_product'] && (int) $gift['gift_product_attribute'] == (int) $product['id_product_attribute']) {
                    $product['quantity'] = (int) $product['quantity'] - 1;
                }
            }
            $success = $cart->update_qty((int) $product['quantity'], (int) $product['id_product'], (int) $product['id_product_attribute'], null, 'up', (int) $id_address_delivery, new Shop((int) $cart->id_shop), false) && $success;
        }
        // Customized products
        $customs = $conn->get_array((new Db_Query())->select('*')->from('customization', 'c')->left_join('customized_data', 'cd', 'cd.`id_customization` = c.`id_customization`')->where('c.`id_cart` = ' . (int) $this->id));
        // Get datas from customization table
        $customs_by_id = [];
        foreach ($customs as $custom) {
            if (!isset($customs_by_id[$custom['id_customization']])) {
                $customs_by_id[$custom['id_customization']] = ['id_product_attribute' => $custom['id_product_attribute'], 'id_product' => $custom['id_product'], 'quantity' => $custom['quantity']];
            }
        }
        // Insert new customizations
        $custom_ids = [];
        foreach ($customs_by_id as $customization_id => $val) {
            $conn->insert('customization', ['id_cart' => (int) $cart->id, 'id_product_attribute' => (int) $val['id_product_attribute'], 'id_product' => (int) $val['id_product'], 'id_address_delivery' => (int) $id_address_delivery, 'quantity' => (int) $val['quantity'], 'quantity_refunded' => 0, 'quantity_returned' => 0, 'in_cart' => 1]);
            $custom_ids[$customization_id] = $conn->Insert_ID();
        }
        // Insert customized_data
        if (count($customs)) {
            foreach ($customs as $custom) {
                $customized_value = $custom['value'];
                if ((int) $custom['type'] === Product::CUSTOMIZE_FILE) {
                    $customized_value = md5(uniqid(random_int(0, mt_getrandmax()), true));
                    copy(_PS_UPLOAD_DIR_ . $custom['value'], _PS_UPLOAD_DIR_ . $customized_value);
                    copy(_PS_UPLOAD_DIR_ . $custom['value'] . '_small', _PS_UPLOAD_DIR_ . $customized_value . '_small');
                }
                $conn->insert('customized_data', ['id_customization' => (int) $custom_ids[$custom['id_customization']], 'type' => (int) $custom['type'], 'index' => (int) $custom['index'], 'value' => p_sql($customized_value)]);
            }
        }
        return ['cart' => $cart, 'success' => $success];
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if (!$this->id_lang) {
            $this->id_lang = Configuration::get('PS_LANG_DEFAULT');
        }
        if (!$this->id_shop) {
            $this->id_shop = Context::get_context()->shop->id;
        }
        $return = parent::add($auto_date, $null_values);
        Hook::trigger_event('actionCartSave', ['cart' => $this]);
        return $return;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_cart_rows()
    {
        return Db::read_only()->get_array((new Db_Query())->select('`id_product`, `id_product_attribute`, `quantity`, `id_address_delivery`')->from('cart_product')->where('`id_cart` = ' . (int) $this->id)->where('`id_shop` = ' . (int) Context::get_context()->shop->id));
    }
    /**
     * @param array $values
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_ws_cart_rows($values)
    {
        if ($this->delete_associations()) {
            $insert = [];
            foreach ($values as $value) {
                $insert[] = ['id_cart' => (int) $this->id, 'id_product' => (int) $value['id_product'], 'id_product_attribute' => isset($value['id_product_attribute']) ? (int) $value['id_product_attribute'] : null, 'id_address_delivery' => isset($value['id_address_delivery']) ? (int) $value['id_address_delivery'] : 0, 'quantity' => (int) $value['quantity'], 'date_add' => ['type' => 'sql', 'value' => 'NOW()'], 'date_upd' => ['type' => 'sql', 'value' => 'NOW()'], 'id_shop' => (int) Context::get_context()->shop->id];
            }
            Db::get_instance()->insert('cart_product', $insert);
        }
        return true;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_associations()
    {
        return (bool) Db::get_instance()->delete('cart_product', '`id_cart` = ' . (int) $this->id);
    }
    /**
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $oldIdAddressDelivery
     * @param int $newIdAddressDelivery
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_product_address_delivery($id_product, $id_product_attribute, $old_id_address_delivery, $new_id_address_delivery)
    {
        // Check address is linked with the customer
        if (!Customer::customer_has_address(Context::get_context()->customer->id, $new_id_address_delivery)) {
            return false;
        }
        if ($new_id_address_delivery == $old_id_address_delivery) {
            return false;
        }
        $conn = Db::get_instance();
        // Checking if the product with the old address delivery exists
        $sql = new Db_Query();
        $sql->select('count(*)');
        $sql->from('cart_product', 'cp');
        $sql->where('id_product = ' . (int) $id_product);
        $sql->where('id_product_attribute = ' . (int) $id_product_attribute);
        $sql->where('id_address_delivery = ' . (int) $old_id_address_delivery);
        $sql->where('id_cart = ' . (int) $this->id);
        $result = $conn->get_value($sql);
        if ($result == 0) {
            return false;
        }
        // Checking if there is no others similar products with this new address delivery
        $sql = new Db_Query();
        $sql->select('sum(quantity) as qty');
        $sql->from('cart_product', 'cp');
        $sql->where('id_product = ' . (int) $id_product);
        $sql->where('id_product_attribute = ' . (int) $id_product_attribute);
        $sql->where('id_address_delivery = ' . (int) $new_id_address_delivery);
        $sql->where('id_cart = ' . (int) $this->id);
        $result = $conn->get_value($sql);
        // Removing similar products with this new address delivery
        $conn->delete('cart_product', 'id_product = ' . (int) $id_product . ' AND id_product_attribute = ' . (int) $id_product_attribute . ' AND id_address_delivery = ' . (int) $new_id_address_delivery . ' AND id_cart = ' . (int) $this->id, 1);
        // Changing the address
        $conn->update('cart_product', ['id_address_delivery' => (int) $new_id_address_delivery, 'quantity' => ['type' => 'sql', 'value' => '`quantity` + ' . (int) $result]], '`id_product` = ' . (int) $id_product . ' AND `id_product_attribute` = ' . (int) $id_product_attribute . ' AND `id_address_delivery` = ' . (int) $old_id_address_delivery . ' AND `id_cart` = ' . (int) $this->id, 1);
        return true;
    }
    /**
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $idAddressDelivery
     * @param int $newIdAddressDelivery
     * @param int $quantity
     * @param bool $keepQuantity
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function duplicate_product($id_product, $id_product_attribute, $id_address_delivery, $new_id_address_delivery, $quantity = 1, $keep_quantity = false)
    {
        // Check address is linked with the customer
        if (!Customer::customer_has_address(Context::get_context()->customer->id, $new_id_address_delivery)) {
            return false;
        }
        $conn = Db::get_instance();
        // Checking the product do not exist with the new address
        $result = $conn->get_value((new Db_Query())->select('COUNT(*)')->from('cart_product', 'c')->where('id_product = ' . (int) $id_product)->where('`id_product_attribute` = ' . (int) $id_product_attribute)->where('`id_address_delivery` = ' . (int) $new_id_address_delivery)->where('`id_cart` = ' . (int) $this->id));
        if ($result > 0) {
            return false;
        }
        $conn->insert('cart_product', ['id_cart' => (int) $this->id, 'id_product' => (int) $id_product, 'id_shop' => (int) $this->id_shop, 'id_product_attribute' => (int) $id_product_attribute, 'quantity' => (int) $quantity, 'date_add' => ['type' => 'sql', 'value' => 'NOW()'], 'date_upd' => ['type' => 'sql', 'value' => 'NOW()'], 'id_address_delivery' => (int) $new_id_address_delivery]);
        if (!$keep_quantity) {
            $duplicated_quantity = $conn->get_value((new Db_Query())->select('quantity')->from('cart_product', 'c')->where('id_product = ' . (int) $id_product)->where('id_product_attribute = ' . (int) $id_product_attribute)->where('id_address_delivery = ' . (int) $id_address_delivery)->where('id_cart = ' . (int) $this->id));
            if ($duplicated_quantity > $quantity) {
                $conn->update('cart_product', ['quantity' => ['type' => 'sql', 'value' => '`quantity - `' . (int) $quantity], 'id_product' => (int) $id_product, 'id_shop' => (int) $this->id_shop, 'id_product_attribute' => (int) $id_product_attribute, 'id_address_delivery' => (int) $id_address_delivery], '`id_cart` =' . (int) $this->id);
            }
        }
        // Checking if there is customizations
        $results = $conn->get_array((new Db_Query())->select('*')->from('customization', 'c')->where('id_product = ' . (int) $id_product)->where('id_product_attribute = ' . (int) $id_product_attribute)->where('id_address_delivery = ' . (int) $id_address_delivery)->where('id_cart = ' . (int) $this->id));
        foreach ($results as $customization) {
            // Duplicate customization
            $conn->insert('customization', ['id_product_attribute' => (int) $customization['id_product_attribute'], 'id_address_delivery' => (int) $new_id_address_delivery, 'id_cart' => (int) $customization['id_cart'], 'id_product' => (int) $customization['id_product'], 'quantity' => (int) $quantity, 'in_cart' => $customization['in_cart']]);
            // Save last insert ID before doing another query
            $last_id = (int) $conn->Insert_ID();
            // Get data from duplicated customizations
            $last_row = $conn->get_row((new Db_Query())->select('`type`, `index`, `value`')->from('customized_data')->where('id_customization = ' . $customization['id_customization']));
            // Insert new copied data with new customization ID into customized_data table
            $last_row['id_customization'] = $last_id;
            $conn->insert('customized_data', $last_row);
        }
        $customization_count = count($results);
        if ($customization_count > 0) {
            $conn->update('cart_product', ['quantity' => ['type' => 'sql', 'value' => '`quantity` + ' . $customization_count * $quantity]], 'id_cart = ' . (int) $this->id . ' AND id_product = ' . (int) $id_product . ' AND id_shop = ' . (int) $this->id_shop . ' AND id_product_attribute = ' . (int) $id_product_attribute . ' AND id_address_delivery = ' . (int) $new_id_address_delivery);
        }
        return true;
    }
    /**
     * Update products cart address delivery with the address delivery of the cart
     *
     * @throws PrestaShopException
     */
    public function set_no_multishipping(): void
    {
        $empty_cache = false;
        $conn = Db::get_instance();
        if (Configuration::get('PS_ALLOW_MULTISHIPPING')) {
            // Upgrading quantities
            $products = Db::read_only()->get_array((new Db_Query())->select('SUM(`quantity`) AS `quantity`, `id_product`, `id_product_attribute`, COUNT(*) AS `count`')->from('cart_product')->where('`id_cart` = ' . (int) $this->id)->where('`id_shop` = ' . (int) $this->id_shop)->group_by('`id_product`, `id_product_attribute`')->having('`count` > 1'));
            foreach ($products as $product) {
                if ($conn->update('cart_product', ['quantity' => (int) $product['quantity']], '`id_cart` = ' . (int) $this->id . ' AND `id_shop` = ' . (int) $this->id_shop . ' AND id_product = ' . $product['id_product'] . ' AND id_product_attribute = ' . $product['id_product_attribute'])) {
                    $empty_cache = true;
                }
            }
            // Merging multiple lines
            $sql = 'DELETE cp1
				FROM `' . _DB_PREFIX_ . 'cart_product` cp1
					INNER JOIN `' . _DB_PREFIX_ . 'cart_product` cp2
					ON (
						(cp1.id_cart = cp2.id_cart)
						AND (cp1.id_product = cp2.id_product)
						AND (cp1.id_product_attribute = cp2.id_product_attribute)
						AND (cp1.id_address_delivery <> cp2.id_address_delivery)
						AND ((cp1.date_upd > cp2.date_upd) OR (cp1.date_upd=cp2.date_upd AND cp1.date_add > cp2.date_add))
					)';
            $conn->execute($sql);
        }
        // Update delivery address for each product line
        $cache_id = 'static::setNoMultishipping' . (int) $this->id . '-' . (int) $this->id_shop . (isset($this->id_address_delivery) && $this->id_address_delivery ? '-' . (int) $this->id_address_delivery : '');
        if (!Cache::is_stored($cache_id)) {
            if ($result = (bool) $conn->update('cart_product', ['id_address_delivery' => ['type' => 'sql', 'value' => '(SELECT `id_address_delivery` FROM `' . _DB_PREFIX_ . 'cart` WHERE `id_cart` = ' . (int) $this->id . ' AND `id_shop` = ' . (int) $this->id_shop . ' LIMIT 1)']], '`id_cart` = ' . (int) $this->id . ' ' . (Configuration::get('PS_ALLOW_MULTISHIPPING') ? ' AND `id_shop` = ' . (int) $this->id_shop : ''))) {
                $empty_cache = true;
            }
            Cache::store($cache_id, $result);
        }
        if (Customization::is_feature_active()) {
            $conn->update('customization', ['id_address_delivery' => ['type' => 'sql', 'value' => '(SELECT `id_address_delivery` FROM `' . _DB_PREFIX_ . 'cart` WHERE `id_cart` = ' . (int) $this->id . ' LIMIT 1)']], '`id_cart` = ' . (int) $this->id);
        }
        if ($empty_cache) {
            $this->_products = null;
        }
    }
    /**
     * Set an address to all products on the cart without address delivery
     *
     * @throws PrestaShopException
     */
    public function autoset_product_address(): void
    {
        if (Validate::is_loaded_object($this)) {
            // Get the main address of the customer
            if ((int) $this->id_address_delivery > 0) {
                $id_address_delivery = (int) $this->id_address_delivery;
            } else {
                $id_address_delivery = (int) Address::get_first_customer_address_id(Context::get_context()->customer->id);
            }
            if (!$id_address_delivery) {
                return;
            }
            // Update
            $conn = Db::get_instance();
            $cart_id = (int) $this->id;
            $conn->update('cart_product', ['id_address_delivery' => (int) $id_address_delivery], '`id_cart` = ' . $cart_id . ' AND (`id_address_delivery` = 0 OR `id_address_delivery` IS NULL) AND `id_shop` = ' . (int) $this->id_shop);
            $conn->update('customization', ['id_address_delivery' => (int) $id_address_delivery], '`id_cart` = ' . $cart_id . ' AND (`id_address_delivery` = 0 OR `id_address_delivery` IS NULL)');
        }
    }
    /**
     * @param bool $ignoreVirtual Ignore virtual product
     * @param bool $exclusive If true, the validation is exclusive : it must be present product in stock and out of stock
     *
     * @return bool false is some products from the cart are out of stock
     *
     * @throws PrestaShopException
     */
    public function is_all_products_in_stock($ignore_virtual = false, $exclusive = false)
    {
        $product_out_of_stock = 0;
        $product_in_stock = 0;
        foreach ($this->get_products() as $product) {
            if (!$exclusive) {
                if ((int) $product['quantity_available'] - (int) $product['cart_quantity'] < 0 && (!$ignore_virtual || !$product['is_virtual'])) {
                    return false;
                }
            } else {
                if ((int) $product['quantity_available'] <= 0 && (!$ignore_virtual || !$product['is_virtual'])) {
                    $product_out_of_stock++;
                }
                if ((int) $product['quantity_available'] > 0 && (!$ignore_virtual || !$product['is_virtual'])) {
                    $product_in_stock++;
                }
                if ($product_in_stock > 0 && $product_out_of_stock > 0) {
                    return false;
                }
            }
        }
        return true;
    }
    protected function delete_customization_file(array $cust_data): bool
    {
        $result = true;
        if ((int) $cust_data['type'] === Product::CUSTOMIZE_FILE) {
            if (file_exists(_PS_UPLOAD_DIR_ . $cust_data['value'])) {
                $result = unlink(_PS_UPLOAD_DIR_ . $cust_data['value']);
            }
            if (file_exists(_PS_UPLOAD_DIR_ . $cust_data['value'] . '_small')) {
                $result = unlink(_PS_UPLOAD_DIR_ . $cust_data['value'] . '_small') && $result;
            }
        }
        return $result;
    }
}