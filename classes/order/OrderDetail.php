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
 * Class OrderDetailCore
 */
class Order_Detail_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'order_detail', 'primary' => 'id_order_detail', 'fields' => ['id_order' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_order_invoice' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'int(11)'], 'id_warehouse' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'dbDefault' => '0', 'dbNullable' => true], 'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'product_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbNullable' => false], 'product_attribute_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'], 'product_name' => ['type' => self::TYPE_STRING, 'validate' => 'isCatalogName', 'required' => true], 'product_quantity' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true, 'dbDefault' => '0'], 'product_quantity_in_stock' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'size' => 10, 'signed' => true, 'dbDefault' => '0'], 'product_quantity_refunded' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbDefault' => '0'], 'product_quantity_return' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbDefault' => '0'], 'product_quantity_reinjected' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbDefault' => '0'], 'product_price' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'required' => true, 'dbDefault' => '0.000000'], 'reduction_percent' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'size' => 10, 'decimals' => 2, 'dbDefault' => '0.00'], 'reduction_amount' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'reduction_amount_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'reduction_amount_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'group_reduction' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'size' => 10, 'decimals' => 2, 'dbDefault' => '0.00'], 'product_quantity_discount' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'dbDefault' => '0.000000'], 'product_ean13' => ['type' => self::TYPE_STRING, 'validate' => 'isEan13', 'size' => 13], 'product_upc' => ['type' => self::TYPE_STRING, 'validate' => 'isUpc', 'size' => 12], 'product_reference' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => self::SIZE_REFERENCE], 'product_supplier_reference' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => self::SIZE_REFERENCE], 'product_weight' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'dbNullable' => false], 'id_tax_rules_group' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbDefault' => '0', 'dbNullable' => true], 'tax_computation_method' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'size' => 1, 'dbDefault' => '0'], 'tax_name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32, 'dbNullable' => false], 'tax_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'size' => 10, 'decimals' => 3, 'dbDefault' => '0.000'], 'ecotax' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'ecotax_tax_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'size' => 5, 'decimals' => 3, 'dbDefault' => '0.000'], 'discount_quantity_applied' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'], 'download_hash' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName'], 'download_nb' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbDefault' => '0', 'dbNullable' => true], 'download_deadline' => ['type' => self::TYPE_DATE, 'validate' => 'isDateFormat'], 'total_price_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_price_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'unit_price_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'unit_price_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_shipping_price_tax_incl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'total_shipping_price_tax_excl' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'purchase_supplier_price' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'original_product_price' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'original_wholesale_price' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000']], 'keys' => ['order_detail' => ['id_order_id_order_detail' => ['type' => Object_Model::KEY, 'columns' => ['id_order', 'id_order_detail']], 'id_tax_rules_group' => ['type' => Object_Model::KEY, 'columns' => ['id_tax_rules_group']], 'order_detail_order' => ['type' => Object_Model::KEY, 'columns' => ['id_order']], 'product_attribute_id' => ['type' => Object_Model::KEY, 'columns' => ['product_attribute_id']], 'product_id' => ['type' => Object_Model::KEY, 'columns' => ['product_id']]]]];
    /** @var int $id_order_detail */
    public $id_order_detail;
    /** @var int $id_order */
    public $id_order;
    /** @var int $id_order_invoice */
    public $id_order_invoice;
    /** @var int $product_id */
    public $product_id;
    /** @var int $id_shop */
    public $id_shop;
    /** @var int $product_attribute_id */
    public $product_attribute_id;
    /** @var string $product_name */
    public $product_name;
    /** @var int $product_quantity */
    public $product_quantity;
    /** @var int $product_quantity_in_stock */
    public $product_quantity_in_stock;
    /** @var int $product_quantity_return */
    public $product_quantity_return;
    /** @var int $product_quantity_refunded */
    public $product_quantity_refunded;
    /** @var int $product_quantity_reinjected */
    public $product_quantity_reinjected;
    /** @var float $product_price */
    public $product_price;
    /** @var float $original_product_price */
    public $original_product_price;
    /** @var float $unit_price_tax_incl */
    public $unit_price_tax_incl;
    /** @var float $unit_price_tax_excl */
    public $unit_price_tax_excl;
    /** @var float $total_price_tax_incl */
    public $total_price_tax_incl;
    /** @var float $total_price_tax_excl */
    public $total_price_tax_excl;
    /** @var float $reduction_percent */
    public $reduction_percent;
    /** @var float $reduction_amount */
    public $reduction_amount;
    /** @var float $reduction_amount_tax_excl */
    public $reduction_amount_tax_excl;
    /** @var float $reduction_amount_tax_incl */
    public $reduction_amount_tax_incl;
    /** @var float $group_reduction */
    public $group_reduction;
    /** @var float $product_quantity_discount */
    public $product_quantity_discount;
    /** @var string $product_ean13 */
    public $product_ean13;
    /** @var string $product_upc */
    public $product_upc;
    /** @var string $product_reference */
    public $product_reference;
    /** @var string $product_supplier_reference */
    public $product_supplier_reference;
    /** @var float $product_weight */
    public $product_weight;
    /** @var float $ecotax */
    public $ecotax;
    /** @var float $ecotax_tax_rate */
    public $ecotax_tax_rate;
    /** @var int $discount_quantity_applied */
    public $discount_quantity_applied;
    /** @var string $download_hash */
    public $download_hash;
    /** @var int $download_nb */
    public $download_nb;
    /** @var string $download_deadline */
    public $download_deadline;
    /** @var string $tax_name */
    public $tax_name;
    /** @var float $tax_rate */
    public $tax_rate;
    /** @var int $tax_computation_method */
    public $tax_computation_method;
    /** @var int $id_tax_rules_group Id tax rules group */
    public $id_tax_rules_group;
    /** @var int $id_warehouse Id warehouse */
    public $id_warehouse;
    /** @var float $total_shipping_price_tax_excl additional shipping price tax excl */
    public $total_shipping_price_tax_excl;
    /** @var float $total_shipping_price_tax_incl additional shipping price tax incl */
    public $total_shipping_price_tax_incl;
    /** @var float $purchase_supplier_price */
    public $purchase_supplier_price;
    /** @var float $original_wholesale_price */
    public $original_wholesale_price;
    /** @var bool $outOfStock */
    protected $out_of_stock = false;
    /** @var TaxCalculator|null $tax_calculator */
    protected $tax_calculator;
    /** @var Address|null $vat_address */
    protected $vat_address;
    /** @var Address|null $specificPrice */
    protected $specific_price;
    /** @var Customer|null $customer */
    protected $customer;
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['fields' => ['id_order' => ['xlink_resource' => 'orders'], 'product_id' => ['xlink_resource' => 'products'], 'product_attribute_id' => ['xlink_resource' => 'combinations'], 'product_quantity_reinjected' => [], 'group_reduction' => [], 'discount_quantity_applied' => [], 'download_hash' => [], 'download_deadline' => []], 'hidden_fields' => ['tax_rate', 'tax_name'], 'associations' => ['taxes' => ['resource' => 'tax', 'getter' => 'getWsTaxes', 'setter' => false, 'fields' => ['id' => []]], 'pack' => ['resource' => 'product', 'getter' => 'getWsPack', 'api' => 'products', 'fields' => ['id' => ['required' => true, 'xlink_resource' => 'products'], 'combination_id' => ['xlink_resource' => 'combinations'], 'quantity' => []]]]];
    /**
     * OrderDetailCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param Context|null $context
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null, protected $context = null)
    {
        $id_shop = null;
        if ($this->context != null && isset($this->context->shop)) {
            $id_shop = $this->context->shop->id;
        }
        parent::__construct($id, $id_lang, $id_shop);
        if ($this->context == null) {
            $this->context = Context::get_context();
        }
        $this->context = $this->context->clone_context();
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (!$res = parent::delete()) {
            return false;
        }
        Db::get_instance()->delete('order_detail_tax', 'id_order_detail=' . (int) $this->id);
        return $res;
    }
    /**
     * @param string $hash
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_download_from_hash($hash)
    {
        if ($hash == '') {
            return false;
        }
        return Db::read_only()->get_row((new Db_Query())->select('*')->from('order_detail', 'od')->left_join('product_download', 'pd', 'od.`product_id` = pd.`id_product`')->where('od.`download_hash` = \'' . p_sql($hash) . '\'')->where('pd.`active` = 1'));
    }
    /**
     * @param int $idOrderDetail
     * @param int $increment
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function increment_download($id_order_detail, $increment = 1)
    {
        return Db::get_instance()->update('order_detail', ['download_nb' => ['type' => 'sql', 'value' => '`download_nb` + ' . (int) $increment]], '`id_order_detail` = ' . (int) $id_order_detail);
    }
    /**
     * Returns the tax calculator associated to this order detail.
     *
     * @return TaxCalculator
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_tax_calculator()
    {
        return static::get_tax_calculator_static($this->id);
    }
    /**
     * Return the tax calculator associated to this order_detail
     *
     * @param int $idOrderDetail
     *
     * @return TaxCalculator
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_tax_calculator_static($id_order_detail)
    {
        $computation_method = 1;
        $taxes = [];
        if ($results = Db::read_only()->get_array((new Db_Query())->select('t.*, d.`tax_computation_method`')->from('order_detail_tax', 't')->left_join('order_detail', 'd', 'd.`id_order_detail` = t.`id_order_detail`')->where('d.`id_order_detail` = ' . (int) $id_order_detail))) {
            foreach ($results as $result) {
                $taxes[] = new Tax((int) $result['id_tax']);
                $computation_method = $result['tax_computation_method'];
            }
        }
        return new Tax_Calculator($taxes, $computation_method);
    }
    /**
     * Save the tax calculator
     *
     * @param bool $replace
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function save_tax_calculator(Order $order, $replace = false)
    {
        // Nothing to save
        if ($this->tax_calculator == null) {
            return true;
        }
        if (!$this->tax_calculator instanceof Tax_Calculator) {
            return false;
        }
        if (count($this->tax_calculator->taxes) == 0) {
            return true;
        }
        if ($order->total_products <= 0) {
            return true;
        }
        $shipping_tax_amount = 0;
        foreach ($order->get_cart_rules() as $cart_rule) {
            if ($cart_rule['free_shipping']) {
                $shipping_tax_amount = $order->total_shipping_tax_excl;
                break;
            }
        }
        $ratio = $this->unit_price_tax_excl / $order->total_products;
        $order_reduction_amount = round(($order->total_discounts_tax_excl - $shipping_tax_amount) * $ratio, _TB_PRICE_DATABASE_PRECISION_);
        $discounted_price_tax_excl = $this->unit_price_tax_excl - $order_reduction_amount;
        $values = [];
        foreach ($this->tax_calculator->get_taxes_amount($discounted_price_tax_excl) as $id_tax => $amount) {
            $total_amount = $amount * (int) $this->product_quantity;
            $values[] = [static::$definition['primary'] => (int) $this->id, Tax::$definition['primary'] => (int) $id_tax, 'unit_amount' => $amount, 'total_amount' => $total_amount];
        }
        $conn = Db::get_instance();
        if ($replace) {
            $conn->delete('order_detail_tax', '`id_order_detail` = ' . (int) $this->id);
        }
        return $conn->insert('order_detail_tax', $values);
    }
    /**
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_tax_amount(Order $order)
    {
        $this->set_context((int) $this->id_shop);
        $address = new Address((int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        $tax_manager = Tax_Manager_Factory::get_manager($address, (int) Product::get_id_tax_rules_group_by_id_product((int) $this->product_id, $this->context));
        $this->tax_calculator = $tax_manager->get_tax_calculator();
        return $this->save_tax_calculator($order, true);
    }
    /**
     * Get a detailed order list of an id_order
     *
     * @param int $idOrder
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_list($id_order)
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('order_detail')->where('`id_order` = ' . (int) $id_order));
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_tax_list()
    {
        return static::get_tax_list_static($this->id);
    }
    /**
     * @param int $idOrderDetail
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_tax_list_static($id_order_detail)
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('order_detail_tax')->where('`' . bq_sql(static::$definition['primary']) . '` = ' . (int) $id_order_detail));
    }
    /**
     * Create a list of order detail for a specified id_order using cart
     *
     * After method returns, property outOfStock contains information if any product is out of stock.
     * Transient properties $vat_address and $customer are also populated during the method call.
     *
     * This is an excellent example of how NOT TO WRITE a code.
     *
     * @param int $idOrderState
     * @param array[] $productList
     * @param int $idOrderInvoice
     * @param bool $useTaxes set to false if you don't want to use taxes
     * @param int $idWarehouse
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function create_list(Order $order, Cart $cart, $id_order_state, $product_list, $id_order_invoice = 0, $use_taxes = true, $id_warehouse = 0): void
    {
        $this->vat_address = new Address((int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        $this->customer = new Customer((int) $order->id_customer);
        $this->id_order = $order->id;
        $this->out_of_stock = false;
        foreach ($product_list as $product) {
            $this->create($order, $cart, $product, $id_order_state, $id_order_invoice, $use_taxes, $id_warehouse);
        }
        unset($this->vat_address);
        unset($this->customer);
    }
    /**
     * Get the state of the current stock product
     *
     * @return bool
     */
    public function get_stock_state()
    {
        return $this->out_of_stock;
    }
    /**
     * Set the additional shipping information
     *
     * @param array $product
     *
     *
     * @throws PrestaShopException
     */
    public function set_shipping_cost(Order $order, $product): void
    {
        $tax_rate = 0;
        $carrier = Order_Invoice::get_carrier((int) $this->id_order_invoice);
        if (isset($carrier) && Validate::is_loaded_object($carrier)) {
            $tax_rate = $carrier->get_taxes_rate(new Address((int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));
        }
        $this->total_shipping_price_tax_excl = round($product['additional_shipping_cost'], _TB_PRICE_DATABASE_PRECISION_);
        $this->total_shipping_price_tax_incl = round($this->total_shipping_price_tax_excl * (1 + $tax_rate / 100), _TB_PRICE_DATABASE_PRECISION_);
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_taxes()
    {
        return Db::read_only()->get_array((new Db_Query())->select('id_tax as id')->from('order_detail_tax', 'tax')->left_join('order_detail', 'od', 'tax.`id_order_detail` = od.`id_order_detail`')->where('od.`id_order_detail` = ' . (int) $this->id_order_detail));
    }
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_ws_pack()
    {
        $sql = (new Db_Query())->select('odp.id_product AS id, odp.quantity, odp.id_product_attribute AS combination_id')->from('order_detail_pack', 'odp')->where('odp.`id_order_detail` = ' . (int) $this->id_order_detail);
        return Db::read_only()->get_array($sql);
    }
    /**
     * @param int $idProduct
     * @param int $idLang
     * @param int $limit
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_cross_sells($id_product, $id_lang, $limit = 12)
    {
        if (!$id_product || !$id_lang) {
            return [];
        }
        $front = true;
        if (!in_array(Context::get_context()->controller->controller_type, ['front', 'modulefront'])) {
            $front = false;
        }
        $connection = Db::read_only();
        $orders = $connection->get_array((new Db_Query())->select('o.`id_order`')->from('orders', 'o')->left_join('order_detail', 'od', 'od.`id_order` = o.`id_order`')->where('o.`valid` = 1')->where('od.`product_id` = ' . (int) $id_product));
        if (count($orders)) {
            $list = '';
            foreach ($orders as $order) {
                $list .= (int) $order['id_order'] . ',';
            }
            $list = rtrim($list, ',');
            $order_products = $connection->get_array((new Db_Query())->select('DISTINCT od.`product_id`, p.`id_product`, pl.`name`, pl.`link_rewrite`, p.`reference`, i.`id_image`, product_shop.`show_price`')->select('cl.`link_rewrite` AS `category`, p.`ean13`, p.`out_of_stock`, p.`id_category_default`')->select(Combination::is_feature_active() ? 'IFNULL(`product_attribute_shop`.`id_product_attribute`, 0) id_product_attribute' : '')->from('order_detail', 'od')->left_join('product', 'p', 'p.`id_product` = od.`product_id`')->join(Shop::add_sql_association('product', 'p'))->join(Combination::is_feature_active() ? 'LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) Context::get_context()->shop->id . ')' : '')->left_join('product_lang', 'pl', 'pl.`id_product` = od.`product_id` AND pl.`id_lang` = ' . (int) $id_lang . ' ' . Shop::add_sql_restriction_on_lang('pl'))->left_join('category_lang', 'cl', 'cl.`id_category` = product_shop.`id_category_default` AND cl.`id_lang` = ' . (int) $id_lang . ' ' . Shop::add_sql_restriction_on_lang('cl'))->left_join('image', 'i', 'i.`id_product` = od.`product_id` ' . Shop::add_sql_association('image', 'i', true, 'image_shop.cover=1'))->where('od.`id_order` IN (' . $list . ')')->where('od.`product_id` != ' . (int) $id_product)->where($front ? '`product_shop`.`visibility` IN ("both", "catalog")' : '')->where('product_shop.active = 1')->order_by('RAND()')->limit((int) $limit));
            $tax_calc = Product::get_tax_calculation_method();
            foreach ($order_products as &$order_product) {
                $order_product['image'] = Context::get_context()->link->get_image_link($order_product['link_rewrite'], (int) $order_product['product_id'] . '-' . (int) $order_product['id_image'], Image_Type::get_formated_name('medium'));
                $order_product['link'] = Context::get_context()->link->get_product_link((int) $order_product['product_id'], $order_product['link_rewrite'], $order_product['category'], $order_product['ean13']);
                if ($tax_calc == 0 || $tax_calc == 2) {
                    $order_product['displayed_price'] = Product::get_price_static((int) $order_product['product_id'], true, null);
                } elseif ($tax_calc == 1) {
                    $order_product['displayed_price'] = Product::get_price_static((int) $order_product['product_id'], false, null);
                }
            }
            return Product::get_products_properties($id_lang, $order_products);
        }
        return [];
    }
    /**
     * Returns wholesale price of product associated with this OrderDetail line
     *
     * @param int|null $currencyId Currency ID. If not specified, returned value will be in default currency
     *
     * @return float
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_whole_sale_price($currency_id = null)
    {
        $product = new Product($this->product_id);
        $wholesale_price = $product->wholesale_price;
        if ($this->product_attribute_id) {
            $combination = new Combination((int) $this->product_attribute_id);
            if (Validate::is_loaded_object($combination) && $combination->wholesale_price != 0.0) {
                $wholesale_price = $combination->wholesale_price;
            }
        }
        // Convert wholesalePrice to specified currency
        $currency_id = (int) $currency_id;
        if ($currency_id && $currency_id !== (int) Configuration::get('PS_CURRENCY_DEFAULT')) {
            $wholesale_price = Tools::convert_price($wholesale_price, $currency_id);
        }
        return round($wholesale_price, _TB_PRICE_DATABASE_PRECISION_);
    }
    /**
     * @param int $idShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function set_context($id_shop)
    {
        if ($this->context->shop->id != $id_shop) {
            $this->context->shop = new Shop((int) $id_shop);
        }
    }
    /**
     * @param array $product
     *
     * @throws PrestaShopException
     */
    protected function set_virtual_product_information($product)
    {
        // Add some informations for virtual products
        $this->download_deadline = '0000-00-00 00:00:00';
        $this->download_hash = null;
        if ($id_product_download = Product_Download::get_id_from_id_product((int) $product['id_product'])) {
            $product_download = new Product_Download((int) $id_product_download);
            $this->download_deadline = $product_download->get_dead_line();
            $this->download_hash = $product_download->get_hash();
            unset($product_download);
        }
    }
    /**
     * Check the order status
     *
     * @param array $product
     * @param int $idOrderState
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function check_product_stock($product, $id_order_state)
    {
        if ($id_order_state != Configuration::get('PS_OS_CANCELED') && $id_order_state != Configuration::get('PS_OS_ERROR')) {
            $update_quantity = Stock_Available::update_quantity($product['id_product'], $product['id_product_attribute'], -(int) $product['cart_quantity']);
            if ($update_quantity) {
                $product['stock_quantity'] -= $product['cart_quantity'];
            }
            if ($product['stock_quantity'] < 0 && Configuration::get('PS_STOCK_MANAGEMENT')) {
                $this->out_of_stock = true;
            }
            Product::update_default_attribute($product['id_product']);
        }
    }
    /**
     * Apply tax to the product
     *
     * @param array $product
     * @throws PrestaShopException
     */
    protected function set_product_tax(Order $order, $product)
    {
        $this->ecotax = Tools::convert_price(floatval($product['ecotax']), intval($order->id_currency));
        // Exclude VAT
        if (!Tax::exclude_taxe_option()) {
            $this->set_context((int) $product['id_shop']);
            $this->id_tax_rules_group = (int) Product::get_id_tax_rules_group_by_id_product((int) $product['id_product'], $this->context);
            $tax_manager = Tax_Manager_Factory::get_manager($this->vat_address, $this->id_tax_rules_group);
            $this->tax_calculator = $tax_manager->get_tax_calculator();
            $this->tax_computation_method = (int) $this->tax_calculator->computation_method;
        }
        $this->tax_name = $product['tax_name'];
        $this->tax_rate = $product['rate'];
        $this->ecotax_tax_rate = 0;
        if (!empty($product['ecotax'])) {
            $this->ecotax_tax_rate = Tax::get_product_ecotax_rate($order->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        }
    }
    /**
     * Set specific price of the product
     *
     * @param array|null $product
     * @throws PrestaShopException
     */
    protected function set_specific_price(Order $order, $product = null)
    {
        $this->reduction_amount = 0.0;
        $this->reduction_percent = 0.0;
        $this->reduction_amount_tax_incl = 0.0;
        $this->reduction_amount_tax_excl = 0.0;
        if ($this->specific_price) {
            switch ($this->specific_price['reduction_type']) {
                case 'percentage':
                    $this->reduction_percent = (float) $this->specific_price['reduction'] * 100;
                    break;
                case 'amount':
                    $price = Tools::convert_price($this->specific_price['reduction'], $order->id_currency);
                    $this->reduction_amount = !$this->specific_price['id_currency'] ? (float) $price : (float) $this->specific_price['reduction'];
                    if ($product !== null) {
                        $this->set_context((int) $product['id_shop']);
                    }
                    $id_tax_rules = (int) Product::get_id_tax_rules_group_by_id_product((int) $this->specific_price['id_product'], $this->context);
                    $tax_manager = Tax_Manager_Factory::get_manager($this->vat_address, $id_tax_rules);
                    $this->tax_calculator = $tax_manager->get_tax_calculator();
                    if ($this->specific_price['reduction_tax']) {
                        $this->reduction_amount_tax_incl = $this->reduction_amount;
                        $this->reduction_amount_tax_excl = $this->tax_calculator->remove_taxes($this->reduction_amount);
                    } else {
                        $this->reduction_amount_tax_incl = $this->tax_calculator->add_taxes($this->reduction_amount);
                        $this->reduction_amount_tax_excl = $this->reduction_amount;
                    }
                    break;
            }
        }
    }
    /**
     * Set detailed product price to the order detail
     *
     * @param array $product
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function set_detail_product_price(Order $order, Cart $cart, $product)
    {
        $this->set_context((int) $product['id_shop']);
        Product::get_price_static((int) $product['id_product'], true, (int) $product['id_product_attribute'], _TB_PRICE_DATABASE_PRECISION_, null, false, true, $product['cart_quantity'], false, (int) $order->id_customer, (int) $order->id_cart, (int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}, $specific_price, true, true, $this->context);
        $this->specific_price = $specific_price;
        $this->original_product_price = Product::get_price_static($product['id_product'], false, (int) $product['id_product_attribute'], _TB_PRICE_DATABASE_PRECISION_, null, false, false, 1, false, null, null, null, $null, true, true, $this->context);
        $this->product_price = $this->original_product_price;
        $this->unit_price_tax_incl = round($product['price_wt'], _TB_PRICE_DATABASE_PRECISION_);
        $this->unit_price_tax_excl = round($product['price'], _TB_PRICE_DATABASE_PRECISION_);
        $this->total_price_tax_incl = round($product['total_wt'], _TB_PRICE_DATABASE_PRECISION_);
        $this->total_price_tax_excl = round($product['total'], _TB_PRICE_DATABASE_PRECISION_);
        // Supplier price in default currency of the shop
        $purchase_supplier_price_def_cur = $product['wholesale_price'];
        if ($product['id_supplier']) {
            $supplier_price = Product_Supplier::get_product_price((int) $product['id_supplier'], $product['id_product'], $product['id_product_attribute'], true);
            if ($supplier_price !== false) {
                $purchase_supplier_price_def_cur = $supplier_price;
            }
        }
        // Save the purchase_supplier in order currency
        $this->purchase_supplier_price = round(Tools::convert_price($purchase_supplier_price_def_cur, $order->id_currency), _TB_PRICE_DATABASE_PRECISION_);
        $this->set_specific_price($order, $product);
        $this->group_reduction = (float) Group::get_reduction((int) $order->id_customer);
        $shop_id = $this->context->shop->id;
        $quantity_discount = Specific_Price::get_quantity_discount((int) $product['id_product'], $shop_id, (int) $cart->id_currency, (int) $this->vat_address->id_country, (int) $this->customer->id_default_group, (int) $product['cart_quantity'], false, null);
        $unit_price = Product::get_price_static((int) $product['id_product'], true, $product['id_product_attribute'] ? intval($product['id_product_attribute']) : null, _TB_PRICE_DATABASE_PRECISION_, null, false, true, 1, false, (int) $order->id_customer, null, (int) $order->{Configuration::get('PS_TAX_ADDRESS_TYPE')}, $null, true, true, $this->context);
        $this->product_quantity_discount = 0.0;
        if ($quantity_discount) {
            $this->product_quantity_discount = $unit_price;
            if (isset($this->tax_calculator)) {
                $this->product_quantity_discount -= $this->tax_calculator->add_taxes($quantity_discount['price']);
            }
        }
        $this->discount_quantity_applied = $this->specific_price && $this->specific_price['from_quantity'] > 1 ? 1 : 0;
    }
    /**
     * Create an order detail liable to an id_order
     *
     * @param array $product
     * @param int $idOrderState
     * @param int $idOrderInvoice
     * @param bool $useTaxes set to false if you don't want to use taxes
     * @param int $idWarehouse
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function create(Order $order, Cart $cart, $product, $id_order_state, $id_order_invoice, $use_taxes = true, $id_warehouse = 0)
    {
        if ($use_taxes) {
            $this->tax_calculator = new Tax_Calculator();
        }
        $this->id = null;
        $this->product_id = (int) $product['id_product'];
        $this->product_attribute_id = $product['id_product_attribute'] ? (int) $product['id_product_attribute'] : 0;
        $this->product_name = $product['name'] . (isset($product['attributes']) && $product['attributes'] != null ? ' - ' . $product['attributes'] : '');
        $this->product_quantity = (int) $product['cart_quantity'];
        $this->product_ean13 = empty($product['ean13']) ? null : p_sql($product['ean13']);
        $this->product_upc = empty($product['upc']) ? null : p_sql($product['upc']);
        $this->product_reference = empty($product['reference']) ? null : p_sql($product['reference']);
        $this->product_supplier_reference = empty($product['supplier_reference']) ? null : p_sql($product['supplier_reference']);
        $this->product_weight = $product['id_product_attribute'] ? (float) $product['weight_attribute'] : (float) $product['weight'];
        $this->id_warehouse = $id_warehouse;
        $product_quantity = (int) Product::get_quantity($this->product_id, $this->product_attribute_id);
        $this->product_quantity_in_stock = $product_quantity - (int) $product['cart_quantity'] < 0 ? $product_quantity : (int) $product['cart_quantity'];
        $this->set_virtual_product_information($product);
        $this->check_product_stock($product, $id_order_state);
        if ($use_taxes) {
            $this->set_product_tax($order, $product);
        }
        $this->set_shipping_cost($order, $product);
        $this->set_detail_product_price($order, $cart, $product);
        $this->original_wholesale_price = $this->get_whole_sale_price($order->id_currency);
        // Set order invoice id
        $this->id_order_invoice = (int) $id_order_invoice;
        // Set shop id
        $this->id_shop = (int) $product['id_shop'];
        // Add new entry to the table
        $this->add();
        if ($use_taxes) {
            $this->save_tax_calculator($order);
        }
        unset($this->tax_calculator);
        foreach (Pack::get_pack_content($this->product_id) as $entry) {
            $pack_item = new Order_Detail_Pack();
            $pack_item->id_order_detail = (int) $this->id;
            $pack_item->id_product = (int) $entry['id_product'];
            $pack_item->id_product_attribute = (int) $entry['id_product_attribute'];
            $pack_item->quantity = (int) $entry['quantity'];
            $pack_item->add();
        }
    }
}