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
 * Class SupplyOrderCore
 */
class Supply_Order_Core extends Object_Model
{
    /**
     * @var int Supplier
     */
    public $id_supplier;
    /**
     * @var string Supplier Name
     */
    public $supplier_name;
    /**
     * @var int The language id used on the delivery note
     */
    public $id_lang;
    /**
     * @var int Warehouse where products will be delivered
     */
    public $id_warehouse;
    /**
     * @var int Current state of the order
     */
    public $id_supply_order_state;
    /**
     * @var int Currency used for the order
     */
    public $id_currency;
    /**
     * @var int Currency used by default in main global configuration (i.e. by default for all shops)
     */
    public $id_ref_currency;
    /**
     * @var string Reference of the order
     */
    public $reference;
    /**
     * @var string Date when added
     */
    public $date_add;
    /**
     * @var string Date when updated
     */
    public $date_upd;
    /**
     * @var string Expected delivery date
     */
    public $date_delivery_expected;
    /**
     * @var float Total price without tax
     */
    public $total_te = 0;
    /**
     * @var float Total price after discount, without tax
     */
    public $total_with_discount_te = 0;
    /**
     * @var float Total price with tax
     */
    public $total_ti = 0;
    /**
     * @var float Total tax value
     */
    public $total_tax = 0;
    /**
     * @var float Supplier discount rate (for the whole order)
     */
    public $discount_rate = 0;
    /**
     * @var float Supplier discount value without tax (for the whole order)
     */
    public $discount_value_te = 0;
    /**
     * @var bool Tells if this order is a template
     */
    public $is_template = 0;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'supply_order', 'primary' => 'id_supply_order', 'fields' => ['id_supplier' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'supplier_name' => ['type' => self::TYPE_STRING, 'validate' => 'isCatalogName', 'size' => 64, 'dbNullable' => false], 'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_warehouse' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_supply_order_state' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_currency' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_ref_currency' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'date_delivery_expected' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true, 'dbNullable' => true], 'total_te' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000', 'dbNullable' => true], 'total_with_discount_te' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000', 'dbNullable' => true], 'total_tax' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000', 'dbNullable' => true], 'total_ti' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000', 'dbNullable' => true], 'discount_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isFloat', 'required' => false, 'dbDefault' => '0.000000', 'dbNullable' => true], 'discount_value_te' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000', 'dbNullable' => true], 'is_template' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0', 'dbNullable' => true]], 'keys' => ['supply_order' => ['id_supplier' => ['type' => Object_Model::KEY, 'columns' => ['id_supplier']], 'id_warehouse' => ['type' => Object_Model::KEY, 'columns' => ['id_warehouse']], 'reference' => ['type' => Object_Model::KEY, 'columns' => ['reference']]]]];
    /**
     * @var array Webservice Parameters
     */
    protected $webservice_parameters = ['fields' => ['id_supplier' => ['xlink_resource' => 'suppliers'], 'id_lang' => ['xlink_resource' => 'languages'], 'id_warehouse' => ['xlink_resource' => 'warehouses'], 'id_supply_order_state' => ['xlink_resource' => 'supply_order_states'], 'id_currency' => ['xlink_resource' => 'currencies']], 'hidden_fields' => ['id_ref_currency'], 'associations' => ['supply_order_details' => ['resource' => 'supply_order_detail', 'fields' => ['id' => [], 'id_product' => [], 'id_product_attribute' => [], 'supplier_reference' => [], 'product_name' => []]]]];
    /**
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        $this->calculate_prices();
        $res = parent::update($null_values);
        if ($res && !$this->is_template) {
            $this->add_history();
        }
        return $res;
    }
    /**
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        $this->calculate_prices();
        $res = parent::add($auto_date, $null_values);
        if ($res && !$this->is_template) {
            $this->add_history();
        }
        return $res;
    }
    /**
     * Checks all products in this order and calculate prices
     * Applies the global discount if necessary
     *
     * @throws PrestaShopException
     */
    protected function calculate_prices()
    {
        $this->total_te = 0;
        $this->total_with_discount_te = 0;
        $this->total_tax = 0;
        $this->total_ti = 0;
        $is_discount = false;
        if (is_numeric($this->discount_rate) && $this->discount_rate >= 0) {
            $is_discount = true;
        }
        // gets all product entries in this order
        $entries = $this->get_entries_collection();
        foreach ($entries as $entry) {
            /** @var SupplyOrderDetail $entry */
            // applys global discount rate on each product if possible
            if ($is_discount) {
                $entry->apply_global_discount((float) $this->discount_rate);
            }
            // adds new prices to the total
            $this->total_te += $entry->price_with_discount_te;
            $this->total_with_discount_te += $entry->price_with_order_discount_te;
            $this->total_tax += $entry->tax_value_with_order_discount;
            $this->total_ti = $this->total_tax + $this->total_with_discount_te;
        }
        // applies global discount rate if possible
        if ($is_discount) {
            $this->discount_value_te = $this->total_te - $this->total_with_discount_te;
        }
    }
    /**
     * Retrieves the product entries for the current order
     *
     * @param int $idLang Optional Id Lang - Uses Context::language::id by default
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_entries($id_lang = null)
    {
        if ($id_lang == null) {
            $id_lang = Context::get_context()->language->id;
        }
        // build query
        $query = new Db_Query();
        $query->select('
			s.*,
			IFNULL(CONCAT(pl.name, \' : \', GROUP_CONCAT(agl.name, \' - \', al.name SEPARATOR \', \')), pl.name) as name_displayed');
        $query->from('supply_order_detail', 's');
        $query->innerjoin('product_lang', 'pl', 'pl.id_product = s.id_product AND pl.id_lang = ' . $id_lang);
        $query->leftjoin('product', 'p', 'p.id_product = s.id_product');
        $query->leftjoin('product_attribute_combination', 'pac', 'pac.id_product_attribute = s.id_product_attribute');
        $query->leftjoin('attribute', 'atr', 'atr.id_attribute = pac.id_attribute');
        $query->leftjoin('attribute_lang', 'al', 'al.id_attribute = atr.id_attribute AND al.id_lang = ' . $id_lang);
        $query->leftjoin('attribute_group_lang', 'agl', 'agl.id_attribute_group = atr.id_attribute_group AND agl.id_lang = ' . $id_lang);
        $query->where('s.id_supply_order = ' . (int) $this->id);
        $query->group_by('s.id_supply_order_detail');
        return Db::read_only()->get_array($query);
    }
    /**
     * Retrieves the details entries (i.e. products) collection for the current order
     *
     * @return PrestaShopCollection Collection of SupplyOrderDetail
     *
     * @throws PrestaShopException
     */
    public function get_entries_collection()
    {
        $details = new Presta_Shop_Collection('SupplyOrderDetail');
        $details->where('id_supply_order', '=', $this->id);
        return $details;
    }
    /**
     * Check if the order has entries
     *
     * @return bool Has/Has not
     *
     * @throws PrestaShopException
     */
    public function has_entries()
    {
        $query = new Db_Query();
        $query->select('COUNT(*)');
        $query->from('supply_order_detail', 's');
        $query->where('s.id_supply_order = ' . (int) $this->id);
        return Db::read_only()->get_value($query) > 0;
    }
    /**
     * Check if the current state allows to edit the current order
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_editable()
    {
        $query = new Db_Query();
        $query->select('s.editable');
        $query->from('supply_order_state', 's');
        $query->where('s.id_supply_order_state = ' . (int) $this->id_supply_order_state);
        return Db::read_only()->get_value($query) == 1;
    }
    /**
     * Checks if the current state allows to generate a delivery note for this order
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_delivery_note_available()
    {
        $query = new Db_Query();
        $query->select('s.delivery_note');
        $query->from('supply_order_state', 's');
        $query->where('s.id_supply_order_state = ' . (int) $this->id_supply_order_state);
        return Db::read_only()->get_value($query) == 1;
    }
    /**
     * Checks if the current state allows to add products in stock
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_in_receipt_state()
    {
        $query = new Db_Query();
        $query->select('s.receipt_state');
        $query->from('supply_order_state', 's');
        $query->where('s.id_supply_order_state = ' . (int) $this->id_supply_order_state);
        return Db::read_only()->get_value($query) == 1;
    }
    /**
     * Historizes the order : its id, its state, and the employee responsible for the current action
     *
     * @throws PrestaShopException
     */
    protected function add_history()
    {
        $context = Context::get_context();
        $history = new Supply_Order_History();
        $history->id_supply_order = $this->id;
        $history->id_state = $this->id_supply_order_state;
        $history->id_employee = (int) $context->employee->id;
        $history->employee_firstname = p_sql($context->employee->firstname);
        $history->employee_lastname = p_sql($context->employee->lastname);
        $history->save();
    }
    /**
     * Removes all products from the order
     *
     * @throws PrestaShopException
     */
    public function reset_products(): void
    {
        $products = $this->get_entries_collection();
        foreach ($products as $p) {
            $p->delete();
        }
    }
    /**
     * For a given $id_warehouse, tells if it has pending supply orders
     *
     * @param int $idWarehouse
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function warehouse_has_pending_orders($id_warehouse)
    {
        if (!$id_warehouse) {
            return false;
        }
        $query = new Db_Query();
        $query->select('COUNT(so.id_supply_order) as supply_orders');
        $query->from('supply_order', 'so');
        $query->left_join('supply_order_state', 'sos', 'so.id_supply_order_state = sos.id_supply_order_state');
        $query->where('sos.enclosed != 1');
        $query->where('so.id_warehouse = ' . (int) $id_warehouse);
        $res = Db::read_only()->get_value($query);
        return $res > 0;
    }
    /**
     * For a given $id_supplier, tells if it has pending supply orders
     *
     * @param int $idSupplier Id Supplier
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function supplier_has_pending_orders($id_supplier)
    {
        if (!$id_supplier) {
            return false;
        }
        $query = new Db_Query();
        $query->select('COUNT(so.id_supply_order) as supply_orders');
        $query->from('supply_order', 'so');
        $query->left_join('supply_order_state', 'sos', 'so.id_supply_order_state = sos.id_supply_order_state');
        $query->where('sos.enclosed != 1');
        $query->where('so.id_supplier = ' . (int) $id_supplier);
        $res = Db::read_only()->get_value($query);
        return $res > 0;
    }
    /**
     * For a given id or reference, tells if the supply order exists
     *
     * @param int|string $match Either the reference of the order, or the Id of the order
     *
     * @return int SupplyOrder Id
     *
     * @throws PrestaShopException
     */
    public static function exists($match)
    {
        if (!$match) {
            return false;
        }
        $query = new Db_Query();
        $query->select('id_supply_order');
        $query->from('supply_order', 'so');
        $query->where('so.id_supply_order = ' . (int) $match . ' OR so.reference = "' . p_sql($match) . '"');
        $res = Db::read_only()->get_value($query);
        return (int) $res;
    }
    /**
     * For a given reference, returns the corresponding supply order
     *
     * @param string $reference Reference of the order
     *
     * @return bool|SupplyOrder
     *
     * @throws PrestaShopException
     */
    public static function get_supply_order_by_reference($reference)
    {
        if (!$reference) {
            return false;
        }
        $query = new Db_Query();
        $query->select('id_supply_order');
        $query->from('supply_order', 'so');
        $query->where('so.reference = "' . p_sql($reference) . '"');
        $id_supply_order = (int) Db::read_only()->get_value($query);
        if (!$id_supply_order) {
            return false;
        }
        return new Supply_Order($id_supply_order);
    }
    /**
     * @param int|null $idLang
     *
     */
    public function hydrate(array $data, $id_lang = null): void
    {
        $this->id_lang = $id_lang;
        if (isset($data[$this->def['primary']])) {
            $this->id = $data[$this->def['primary']];
        }
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                // formats prices and floats
                if ($this->def['fields'][$key]['validate'] == 'isPrice') {
                    $value = round($value, _TB_PRICE_DATABASE_PRECISION_);
                }
                $this->{$key} = $value;
            }
        }
    }
    /**
     * Gets the reference of a given order
     *
     * @param int $idSupplyOrder
     *
     * @return bool|string
     *
     * @throws PrestaShopException
     */
    public static function get_reference_by_id($id_supply_order)
    {
        if (!$id_supply_order) {
            return false;
        }
        $query = new Db_Query();
        $query->select('so.reference');
        $query->from('supply_order', 'so');
        $query->where('so.id_supply_order = ' . (int) $id_supply_order);
        $ref = Db::read_only()->get_value($query);
        return p_sql($ref);
    }
    /**
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function get_all_expected_quantity()
    {
        return Db::read_only()->get_value('
			SELECT SUM(`quantity_expected`)
			FROM `' . _DB_PREFIX_ . 'supply_order_detail`
			WHERE `id_supply_order` = ' . (int) $this->id);
    }
    /**
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function get_all_received_quantity()
    {
        return Db::read_only()->get_value('
			SELECT SUM(`quantity_received`)
			FROM `' . _DB_PREFIX_ . 'supply_order_detail`
			WHERE `id_supply_order` = ' . (int) $this->id);
    }
    /**
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function get_all_pending_quantity()
    {
        return Db::read_only()->get_value('
			SELECT (SUM(`quantity_expected`) - SUM(`quantity_received`))
			FROM `' . _DB_PREFIX_ . 'supply_order_detail`
			WHERE `id_supply_order` = ' . (int) $this->id);
    }
    /**
     * Webservice : gets the ids supply_order_detail associated to this order
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_supply_order_details()
    {
        $query = new Db_Query();
        $query->select('sod.id_supply_order_detail as id, sod.id_product,
						sod.id_product_attribute,
					    sod.name as product_name, supplier_reference');
        $query->from('supply_order_detail', 'sod');
        $query->where('id_supply_order = ' . (int) $this->id);
        return Db::read_only()->get_array($query);
    }
}