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
 * Class StockManagerCore
 */
class Stock_Manager_Core implements Stock_Manager_Interface
{
    public static function is_available(): bool
    {
        // Default Manager : always available
        return true;
    }
    /**
     * @param string|null $date
     */
    protected static function converts_date_to_timestamp($date): int
    {
        if ($date) {
            try {
                $date = new DateTime($date);
                return $date->get_timestamp();
            } catch (Exception) {
            }
        }
        return 0;
    }
    /**
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $quantity
     * @param int|null $idStockMvtReason
     * @param float $priceTe
     * @param bool $isUsable
     * @param int|null $idSupplyOrder
     * @param Employee|null $employee
     * @throws PrestaShopException
     */
    public function add_product($id_product, $id_product_attribute, Warehouse $warehouse, $quantity, $id_stock_mvt_reason, $price_te, $is_usable = true, $id_supply_order = null, $employee = null): bool
    {
        if ($this->should_prevent_stock_operation($warehouse, $id_product, $quantity)) {
            return false;
        }
        $price_te = round($price_te, _TB_PRICE_DATABASE_PRECISION_);
        if ($price_te < 0.0) {
            // why <= ?
            return false;
        }
        if (!Stock_Mvt_Reason::exists($id_stock_mvt_reason)) {
            $id_stock_mvt_reason = Configuration::get('PS_STOCK_MVT_INC_REASON_DEFAULT');
        }
        $context = Context::get_context();
        $mvt_params = ['id_stock' => null, 'physical_quantity' => $quantity, 'id_stock_mvt_reason' => $id_stock_mvt_reason, 'id_supply_order' => $id_supply_order, 'price_te' => $price_te, 'last_wa' => null, 'current_wa' => null, 'id_employee' => (int) $context->employee->id ?: $employee->id, 'employee_firstname' => $context->employee->firstname ?: $employee->firstname, 'employee_lastname' => $context->employee->lastname ?: $employee->lastname, 'sign' => 1];
        $stock_exists = false;
        // switch on MANAGEMENT_TYPE
        switch ($warehouse->management_type) {
            // case CUMP mode
            case 'WA':
                $stock_collection = $this->get_stock_collection($id_product, $id_product_attribute, $warehouse->id);
                // if this product is already in stock
                if (count($stock_collection) > 0) {
                    $stock_exists = true;
                    /** @var Stock $stock */
                    // for a warehouse using WA, there is one and only one stock for a given product
                    $stock = $stock_collection->current();
                    // calculates WA price
                    $last_wa = $stock->price_te;
                    $current_wa = $this->calculate_wa($stock, $quantity, $price_te);
                    $mvt_params['id_stock'] = $stock->id;
                    $mvt_params['last_wa'] = $last_wa;
                    $mvt_params['current_wa'] = $current_wa;
                    $stock_params = ['physical_quantity' => $stock->physical_quantity + $quantity, 'price_te' => $current_wa, 'usable_quantity' => $is_usable ? $stock->usable_quantity + $quantity : $stock->usable_quantity, 'id_warehouse' => $warehouse->id];
                    // saves stock in warehouse
                    $stock->hydrate($stock_params);
                    $stock->update();
                } else {
                    // else, the product is not in sock
                    $mvt_params['last_wa'] = 0;
                    $mvt_params['current_wa'] = $price_te;
                }
                break;
            // case FIFO / LIFO mode
            case 'FIFO':
            case 'LIFO':
                $stock_collection = $this->get_stock_collection($id_product, $id_product_attribute, $warehouse->id, $price_te);
                // if this product is already in stock
                if (count($stock_collection) > 0) {
                    $stock_exists = true;
                    /** @var Stock $stock */
                    // there is one and only one stock for a given product in a warehouse and at the current unit price
                    $stock = $stock_collection->current();
                    $stock_params = ['physical_quantity' => $stock->physical_quantity + $quantity, 'usable_quantity' => $is_usable ? $stock->usable_quantity + $quantity : $stock->usable_quantity];
                    // updates stock in warehouse
                    $stock->hydrate($stock_params);
                    $stock->update();
                    // sets mvt_params
                    $mvt_params['id_stock'] = $stock->id;
                }
                break;
            default:
                return false;
        }
        if (!$stock_exists) {
            $stock = new Stock();
            $stock_params = ['id_product_attribute' => $id_product_attribute, 'id_product' => $id_product, 'physical_quantity' => $quantity, 'price_te' => $price_te, 'usable_quantity' => $is_usable ? $quantity : 0, 'id_warehouse' => $warehouse->id];
            // saves stock in warehouse
            $stock->hydrate($stock_params);
            $stock->add();
            $mvt_params['id_stock'] = $stock->id;
        }
        // saves stock mvt
        $stock_mvt = new Stock_Mvt();
        $stock_mvt->hydrate($mvt_params);
        $stock_mvt->add();
        return true;
    }
    /**
     * @param int $idProduct
     * @param int|null $idProductAttribute
     * @param int $quantity
     * @param int $idStockMvtReason
     * @param bool $isUsable
     * @param int|null $idOrder
     * @param int $ignorePack
     * @param Employee|null $employee
     *
     * @return array|false
     * @throws PrestaShopException
     */
    public function remove_product($id_product, $id_product_attribute, Warehouse $warehouse, $quantity, $id_stock_mvt_reason, $is_usable = true, $id_order = null, $ignore_pack = 0, $employee = null, ?Stock $stock = null): array|false
    {
        $removed_products = [];
        if ($this->should_prevent_stock_operation($warehouse, $id_product, $quantity)) {
            return $removed_products;
        }
        $id_stock_mvt_reason = $this->ensure_stock_movement_reason_is_valid($id_stock_mvt_reason);
        if ($this->should_handle_stock_operation_for_products_pack($id_product, $ignore_pack)) {
            if (Validate::is_loaded_object($product = new Product((int) $id_product))) {
                // Gets items
                if ($product->should_adjust_pack_items_quantities()) {
                    $products_pack = Pack::get_items((int) $id_product, (int) Configuration::get('PS_LANG_DEFAULT'));
                    // Foreach item
                    foreach ($products_pack as $product_pack) {
                        if ($product_pack->advanced_stock_management == 1) {
                            $product_warehouses = Warehouse::get_product_warehouse_list($product_pack->id, $product_pack->id_pack_product_attribute);
                            $warehouse_stock_found = false;
                            foreach ($product_warehouses as $product_warehouse) {
                                if ($warehouse_stock_found) {
                                    continue;
                                }
                                if (!Warehouse::exists($product_warehouse['id_warehouse'])) {
                                    continue;
                                }
                                $current_warehouse = new Warehouse($product_warehouse['id_warehouse']);
                                $removed_products[] = $this->remove_product($product_pack->id, $product_pack->id_pack_product_attribute, $current_warehouse, $product_pack->pack_quantity * $quantity, $id_stock_mvt_reason, $is_usable, $id_order);
                                // The product was found on this warehouse. Stop the stock searching.
                                $warehouse_stock_found = !empty($removed_products[count($removed_products) - 1]);
                            }
                        }
                    }
                }
                if ($product->should_adjust_pack_quantity()) {
                    $removed_products = array_merge($removed_products, $this->remove_product($id_product, $id_product_attribute, $warehouse, $quantity, $id_stock_mvt_reason, $is_usable, $id_order, 1));
                }
            } else {
                return false;
            }
        } else {
            $quantity_in_stock = $this->compute_product_quantity_in_stock($warehouse, $id_product, $id_product_attribute, $is_usable, $stock);
            if ($this->ensure_product_quantity_requested_for_removal_is_valid($quantity, $quantity_in_stock)) {
                return $removed_products;
            }
            $stock_collection = $this->get_product_stock_lines_in_warehouse($id_product, $id_product_attribute, $warehouse, $stock);
            /** @var Countable $stockCollection */
            if (count($stock_collection) <= 0) {
                return $removed_products;
            }
            // switch on MANAGEMENT_TYPE
            switch ($warehouse->management_type) {
                // case CUMP mode
                case 'WA':
                    /** @var Stock $stock */
                    // There is one and only one stock for a given product in a warehouse in this mode
                    $stock = $stock_collection->current();
                    $this->remove_product_quantity_applying_cump($quantity, $id_stock_mvt_reason, $is_usable, $id_order, $employee, $stock);
                    $removed_products[$stock->id]['quantity'] = $quantity;
                    $removed_products[$stock->id]['price_te'] = $stock->price_te;
                    break;
                case 'LIFO':
                case 'FIFO':
                    $stock_history_qty_available = [];
                    $quantity_to_decrement_by_stock = [];
                    $global_quantity_to_decrement = $quantity;
                    // for each stock, parse its mvts history to calculate the quantities left for each positive mvt,
                    // according to the instant available quantities for this stock
                    foreach ($stock_collection as $stock) {
                        /** @var Stock $stock */
                        $left_quantity_to_check = $stock->physical_quantity;
                        if ($left_quantity_to_check <= 0) {
                            continue;
                        }
                        $conn = Db::get_instance();
                        $resource = $conn->query('
							SELECT sm.`id_stock_mvt`, sm.`date_add`, sm.`physical_quantity`,
								IF ((sm2.`physical_quantity` is null), sm.`physical_quantity`, (sm.`physical_quantity` - SUM(sm2.`physical_quantity`))) as qty
							FROM `' . _DB_PREFIX_ . 'stock_mvt` sm
							LEFT JOIN `' . _DB_PREFIX_ . 'stock_mvt` sm2 ON sm2.`referer` = sm.`id_stock_mvt`
							WHERE sm.`sign` = 1
							AND sm.`id_stock` = ' . (int) $stock->id . '
							GROUP BY sm.`id_stock_mvt`
							ORDER BY sm.`date_add` DESC');
                        while ($row = $conn->next_row($resource)) {
                            // continue - in FIFO mode, we have to retreive the oldest positive mvts for which there are left quantities
                            if ($warehouse->management_type == 'FIFO') {
                                if ($row['qty'] == 0) {
                                    continue;
                                }
                            }
                            $timestamp = static::converts_date_to_timestamp($row['date_add']);
                            // history of the mvt
                            $stock_history_qty_available[$timestamp] = ['id_stock' => $stock->id, 'id_stock_mvt' => (int) $row['id_stock_mvt'], 'qty' => (int) $row['qty']];
                            // break - in LIFO mode, checks only the necessary history to handle the global quantity for the current stock
                            if ($warehouse->management_type == 'LIFO') {
                                $left_quantity_to_check -= (int) $row['qty'];
                                if ($left_quantity_to_check <= 0) {
                                    break;
                                }
                            }
                        }
                    }
                    if ($warehouse->management_type == 'LIFO') {
                        // orders stock history by timestamp to get newest history first
                        krsort($stock_history_qty_available);
                    } else {
                        // orders stock history by timestamp to get oldest history first
                        ksort($stock_history_qty_available);
                    }
                    // checks each stock to manage the real quantity to decrement for each of them
                    foreach ($stock_history_qty_available as $entry) {
                        if ($entry['qty'] >= $global_quantity_to_decrement) {
                            $quantity_to_decrement_by_stock[$entry['id_stock']][$entry['id_stock_mvt']] = $global_quantity_to_decrement;
                            $global_quantity_to_decrement = 0;
                        } else {
                            $quantity_to_decrement_by_stock[$entry['id_stock']][$entry['id_stock_mvt']] = $entry['qty'];
                            $global_quantity_to_decrement -= $entry['qty'];
                        }
                        if ($global_quantity_to_decrement <= 0) {
                            break;
                        }
                    }
                    $employee_attributes = $this->get_attributes_of_employee_requesting_stock_movement($employee);
                    // for each stock, decrements it and logs the mvts
                    foreach ($stock_collection as $stock) {
                        if (array_key_exists($stock->id, $quantity_to_decrement_by_stock) && is_array($quantity_to_decrement_by_stock[$stock->id])) {
                            $total_quantity_for_current_stock = 0;
                            foreach ($quantity_to_decrement_by_stock[$stock->id] as $id_mvt_referrer => $qte) {
                                $mvt_params = ['id_stock' => $stock->id, 'physical_quantity' => $qte, 'id_stock_mvt_reason' => $id_stock_mvt_reason, 'id_order' => $id_order, 'price_te' => $stock->price_te, 'sign' => -1, 'referer' => $id_mvt_referrer, 'id_employee' => $employee_attributes['employee_id']];
                                // saves stock mvt
                                $stock_mvt = new Stock_Mvt();
                                $stock_mvt->hydrate($mvt_params);
                                $stock_mvt->save();
                                $total_quantity_for_current_stock += $qte;
                            }
                            if ($is_usable) {
                                $usable_product_quantity = $stock->usable_quantity - $total_quantity_for_current_stock;
                            } else {
                                $usable_product_quantity = $stock->usable_quantity;
                            }
                            $stock_params = ['physical_quantity' => $stock->physical_quantity - $total_quantity_for_current_stock, 'usable_quantity' => $usable_product_quantity];
                            $removed_products[$stock->id]['quantity'] = $total_quantity_for_current_stock;
                            $removed_products[$stock->id]['price_te'] = $stock->price_te;
                            // saves stock in warehouse
                            $stock->hydrate($stock_params);
                            $stock->update();
                        }
                    }
                    break;
            }
            if (Pack::is_packed($id_product, $id_product_attribute)) {
                $packs = Pack::get_packs_containing_item($id_product, $id_product_attribute, (int) Configuration::get('PS_LANG_DEFAULT'));
                foreach ($packs as $pack) {
                    // Decrease stocks of the pack only if pack is in linked stock mode (option called 'Decrement both')
                    if ($pack->get_pack_stock_type() !== Pack::STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS) {
                        continue;
                    }
                    // Decrease stocks of the pack only if there is not enough items to constitute the actual pack stocks.
                    // How many packs can be constituted with the remaining product stocks
                    $quantity_by_pack = $pack->pack_item_quantity;
                    $stock_available_quantity = $quantity_in_stock - $quantity;
                    $max_pack_quantity = max([0, floor($stock_available_quantity / $quantity_by_pack)]);
                    $quantity_delta = Pack::get_quantity($pack->id) - $max_pack_quantity;
                    if ($pack->advanced_stock_management == 1 && $quantity_delta > 0) {
                        $product_warehouses = Warehouse::get_pack_warehouses($pack->id);
                        $warehouse_stock_found = false;
                        foreach ($product_warehouses as $product_warehouse) {
                            if ($warehouse_stock_found) {
                                continue;
                            }
                            if (!Warehouse::exists($product_warehouse)) {
                                continue;
                            }
                            $current_warehouse = new Warehouse($product_warehouse);
                            $removed_products[] = $this->remove_product($pack->id, null, $current_warehouse, $quantity_delta, $id_stock_mvt_reason, $is_usable, $id_order, 1);
                            // The product was found on this warehouse. Stop the stock searching.
                            $warehouse_stock_found = !empty($removed_products[count($removed_products) - 1]);
                        }
                    }
                }
            }
        }
        $this->hook_coverage_on_product_removal($warehouse, $id_product, $id_product_attribute, $is_usable);
        return $removed_products;
    }
    /**
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int|null $idsWarehouse
     * @param bool $usable
     *
     * @throws PrestaShopException
     * @deprecated
     */
    public function get_product_physical_quantities($id_product, $id_product_attribute, $ids_warehouse = null, $usable = false): int
    {
        $ids_warehouse = $this->normalize_warehouse_ids($ids_warehouse);
        $query = new Db_Query();
        $query->select('SUM(' . ($usable ? 's.usable_quantity' : 's.physical_quantity') . ')');
        $query->from('stock', 's');
        $query->where('s.id_product = ' . (int) $id_product);
        if (0 != $id_product_attribute) {
            $query->where('s.id_product_attribute = ' . (int) $id_product_attribute);
        }
        if (count($ids_warehouse)) {
            $query->where('s.id_warehouse IN(' . implode(', ', $ids_warehouse) . ')');
        }
        return (int) Db::read_only()->get_value($query);
    }
    /**
     * @param array $productStockCriteria
     *
     *
     * @throws PrestaShopException
     */
    public function get_physical_product_quantities($product_stock_criteria): int
    {
        $product_stock_criteria = $this->validate_product_stock_criteria($product_stock_criteria);
        return (int) $this->get_product_physical_quantities($product_stock_criteria['product_id'], $product_stock_criteria['product_attribute_id'], $product_stock_criteria['warehouse_id'], $product_stock_criteria['usable'] ?? false);
    }
    /**
     * @param array $productStockCriteria
     *
     *
     * @throws PrestaShopException
     */
    public function get_usable_product_quantities($product_stock_criteria): int
    {
        $product_stock_criteria = $this->validate_product_stock_criteria($product_stock_criteria);
        return (int) $this->get_product_physical_quantities($product_stock_criteria['product_id'], $product_stock_criteria['product_attribute_id'], $product_stock_criteria['warehouse_id'], true);
    }
    protected function validate_product_stock_criteria(array $criteria): array
    {
        if (!array_key_exists('product_id', $criteria)) {
            throw new InvalidArgumentException('Missing product id');
        }
        if (!array_key_exists('product_attribute_id', $criteria)) {
            throw new InvalidArgumentException('Missing product combination id');
        }
        if (!array_key_exists('warehouse_id', $criteria)) {
            throw new InvalidArgumentException('Missing warehouse id');
        }
        return $criteria;
    }
    /**
     * @param int|int[]|null $idsWarehouse
     *
     * @return int[]
     */
    public function normalize_warehouse_ids($ids_warehouse): array
    {
        $normalized_warehouse_ids = [];
        if (!is_null($ids_warehouse)) {
            if (!is_array($ids_warehouse)) {
                $ids_warehouse = [$ids_warehouse];
            }
            $normalized_warehouse_ids = array_map(intval(...), $ids_warehouse);
        }
        return $normalized_warehouse_ids;
    }
    /**
     * @param int $idProduct
     * @param int|null $idProductAttribute
     * @param array|int $idsWarehouse
     * @param bool $usable
     *
     * @return float|int|mixed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_product_real_quantities($id_product, $id_product_attribute, $ids_warehouse = null, $usable = false): int|float
    {
        $ids_warehouse = $this->normalize_warehouse_ids($ids_warehouse);
        $client_orders_qty = 0;
        // check if product is present in a pack
        $conn = Db::read_only();
        if (!Pack::is_pack($id_product) && $in_pack = $conn->get_array('SELECT id_product_pack, quantity FROM ' . _DB_PREFIX_ . 'pack
			WHERE id_product_item = ' . (int) $id_product . '
			AND id_product_attribute_item = ' . ($id_product_attribute ? (int) $id_product_attribute : '0'))) {
            foreach ($in_pack as $value) {
                $product = new Product((int) $value['id_product_pack']);
                if (Validate::is_loaded_object($product) && $product->should_adjust_pack_items_quantities()) {
                    $query = new Db_Query();
                    $query->select('od.product_quantity, od.product_quantity_refunded, pk.quantity');
                    $query->from('order_detail', 'od');
                    $query->leftjoin('orders', 'o', 'o.id_order = od.id_order');
                    $query->where('od.product_id = ' . (int) $value['id_product_pack']);
                    $query->left_join('order_history', 'oh', 'oh.id_order = o.id_order AND oh.id_order_state = o.current_state');
                    $query->left_join('order_state', 'os', 'os.id_order_state = oh.id_order_state');
                    $query->left_join('pack', 'pk', 'pk.id_product_item = ' . (int) $id_product . ' AND pk.id_product_attribute_item = ' . ($id_product_attribute ? (int) $id_product_attribute : '0') . ' AND id_product_pack = od.product_id');
                    $query->where('os.shipped != 1');
                    $query->where('o.valid = 1 OR (os.id_order_state != ' . (int) Configuration::get('PS_OS_ERROR') . '
								   AND os.id_order_state != ' . (int) Configuration::get('PS_OS_CANCELED') . ')');
                    $query->group_by('od.id_order_detail');
                    if ($ids_warehouse) {
                        $query->where('od.id_warehouse IN(' . implode(', ', $ids_warehouse) . ')');
                    }
                    $res = $conn->get_array($query);
                    if (count($res)) {
                        foreach ($res as $row) {
                            $client_orders_qty += ($row['product_quantity'] - $row['product_quantity_refunded']) * $row['quantity'];
                        }
                    }
                }
            }
        }
        $tracking_product_quantity = true;
        if (Pack::is_pack($id_product)) {
            $product = new Product((int) $id_product);
            $tracking_product_quantity = $product->should_adjust_pack_quantity();
        }
        // skip if product is a pack without
        if ($tracking_product_quantity) {
            // Gets client_orders_qty
            $query = new Db_Query();
            $query->select('od.product_quantity, od.product_quantity_refunded');
            $query->from('order_detail', 'od');
            $query->leftjoin('orders', 'o', 'o.id_order = od.id_order');
            $query->where('od.product_id = ' . (int) $id_product);
            if (0 != $id_product_attribute) {
                $query->where('od.product_attribute_id = ' . (int) $id_product_attribute);
            }
            $query->left_join('order_history', 'oh', 'oh.id_order = o.id_order AND oh.id_order_state = o.current_state');
            $query->left_join('order_state', 'os', 'os.id_order_state = oh.id_order_state');
            $query->where('os.shipped != 1');
            $query->where('o.valid = 1 OR (os.id_order_state != ' . (int) Configuration::get('PS_OS_ERROR') . '
						   AND os.id_order_state != ' . (int) Configuration::get('PS_OS_CANCELED') . ')');
            $query->group_by('od.id_order_detail');
            if ($ids_warehouse) {
                $query->where('od.id_warehouse IN(' . implode(', ', $ids_warehouse) . ')');
            }
            $res = $conn->get_array($query);
            if (count($res)) {
                foreach ($res as $row) {
                    $client_orders_qty += $row['product_quantity'] - $row['product_quantity_refunded'];
                }
            }
        }
        // Gets supply_orders_qty
        $query = new Db_Query();
        $query->select('sod.quantity_expected, sod.quantity_received');
        $query->from('supply_order', 'so');
        $query->leftjoin('supply_order_detail', 'sod', 'sod.id_supply_order = so.id_supply_order');
        $query->leftjoin('supply_order_state', 'sos', 'sos.id_supply_order_state = so.id_supply_order_state');
        $query->where('sos.pending_receipt = 1');
        $query->where('sod.id_product = ' . (int) $id_product . ' AND sod.id_product_attribute = ' . (int) $id_product_attribute);
        if ($ids_warehouse) {
            $query->where('so.id_warehouse IN(' . implode(', ', $ids_warehouse) . ')');
        }
        $supply_orders_qties = $conn->get_array($query);
        $supply_orders_qty = 0;
        foreach ($supply_orders_qties as $qty) {
            if ($qty['quantity_expected'] > $qty['quantity_received']) {
                $supply_orders_qty += $qty['quantity_expected'] - $qty['quantity_received'];
            }
        }
        // Gets {physical OR usable}_qty
        $qty = $this->get_physical_product_quantities(['product_id' => $id_product, 'product_attribute_id' => $id_product_attribute, 'warehouse_id' => $ids_warehouse, 'usable' => $usable]);
        //real qty = actual qty in stock - current client orders + current supply orders
        return $qty - $client_orders_qty + $supply_orders_qty;
    }
    /**
     * @throws PrestaShopException
     */
    public function transfer_between_warehouses($id_product, $id_product_attribute, $quantity, $id_warehouse_from, $id_warehouse_to, $usable_from = true, $usable_to = true): bool
    {
        // Checks if this transfer is possible
        if ($this->get_physical_product_quantities(['product_id' => $id_product, 'product_attribute_id' => $id_product_attribute, 'warehouse_id' => [$id_warehouse_from], 'usable' => $usable_from]) < $quantity) {
            return false;
        }
        if ($id_warehouse_from == $id_warehouse_to && $usable_from == $usable_to) {
            return false;
        }
        // Checks if the given warehouses are available
        $warehouse_from = new Warehouse($id_warehouse_from);
        $warehouse_to = new Warehouse($id_warehouse_to);
        if (!Validate::is_loaded_object($warehouse_from) || !Validate::is_loaded_object($warehouse_to)) {
            return false;
        }
        // Removes from warehouse_from
        $stocks = $this->remove_product($id_product, $id_product_attribute, $warehouse_from, $quantity, Configuration::get('PS_STOCK_MVT_TRANSFER_FROM'), $usable_from);
        if (!count($stocks)) {
            return false;
        }
        // Adds in warehouse_to
        foreach ($stocks as $stock) {
            $price = $stock['price_te'];
            // convert product price to destination warehouse currency if needed
            if ($warehouse_from->id_currency != $warehouse_to->id_currency) {
                // First convert price to the default currency
                $price_converted_to_default_currency = Tools::convert_price($price, $warehouse_from->id_currency, false);
                // Convert the new price from default currency to needed currency
                $price = Tools::convert_price($price_converted_to_default_currency, $warehouse_to->id_currency, true);
            }
            if (!$this->add_product($id_product, $id_product_attribute, $warehouse_to, $stock['quantity'], Configuration::get('PS_STOCK_MVT_TRANSFER_TO'), $price, $usable_to)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Here, $coverage is a number of days
     *
     * @return int number of days left (-1 if infinite)
     *
     * @throws PrestaShopException
     */
    public function get_product_coverage($id_product, $id_product_attribute, $coverage, $id_warehouse = null)
    {
        if (!$id_product_attribute) {
            $id_product_attribute = 0;
        }
        if ($coverage == 0 || !$coverage) {
            $coverage = 7;
        }
        // Week by default
        // gets all stock_mvt for the given coverage period
        $query = '
			SELECT SUM(sm.`physical_quantity`) as quantity
				FROM `' . _DB_PREFIX_ . 'stock_mvt` sm
				LEFT JOIN `' . _DB_PREFIX_ . 'stock` s ON (sm.`id_stock` = s.`id_stock`)
				LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (p.`id_product` = s.`id_product`)
				' . Shop::add_sql_association('product', 'p') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON (p.`id_product` = pa.`id_product`)
				' . Shop::add_sql_association('product_attribute', 'pa', false) . '
				WHERE sm.`sign` = -1
				AND sm.`id_stock_mvt_reason` != ' . Configuration::get('PS_STOCK_MVT_TRANSFER_FROM') . '
				AND TO_DAYS("' . date('Y-m-d') . ' 00:00:00") - TO_DAYS(sm.`date_add`) <= ' . (int) $coverage . '
				AND s.`id_product` = ' . (int) $id_product . '
				AND s.`id_product_attribute` = ' . (int) $id_product_attribute . ($id_warehouse ? ' AND s.`id_warehouse` = ' . (int) $id_warehouse : '');
        $quantity_out = (int) Db::read_only()->get_value($query);
        if (!$quantity_out) {
            return -1;
        }
        $quantity_per_day = $quantity_out / $coverage;
        $physical_quantity = $this->get_product_physical_quantities($id_product, $id_product_attribute, $id_warehouse ? [$id_warehouse] : null, true);
        return Tools::ps_round($physical_quantity / $quantity_per_day);
    }
    /**
     * For a given stock, calculates its new WA(Weighted Average) price based on the new quantities and price
     * Formula : (physicalStock * lastCump + quantityToAdd * unitPrice) / (physicalStock + quantityToAdd)
     *
     * @param int $quantity
     * @param float $priceTe
     * @return float Weight Average, rounded to _TB_PRICE_DATABASE_PRECISION_.
     */
    protected function calculate_wa(Stock $stock, $quantity, $price_te): float
    {
        return round(($stock->physical_quantity * $stock->price_te + $quantity * $price_te) / ($stock->physical_quantity + $quantity), _TB_PRICE_DATABASE_PRECISION_);
    }
    /**
     * For a given product, retrieves the stock collection
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int|null $idWarehouse Optional
     * @param float|null $priceTaxExcluded Optional
     * @param Stock|null $stock Optional
     *
     * @return PrestaShopCollection Collection of Stock
     *
     * @throws PrestaShopException
     */
    protected function get_stock_collection($id_product, $id_product_attribute, $id_warehouse = null, $price_tax_excluded = null, ?Stock $stock = null): \Presta_Shop_Collection
    {
        $stocks = new Presta_Shop_Collection('Stock');
        $stocks->where('id_product', '=', $id_product);
        $stocks->where('id_product_attribute', '=', $id_product_attribute);
        if ($stock) {
            $stocks->where('id_stock', '=', $stock->id);
        }
        if ($id_warehouse) {
            $stocks->where('id_warehouse', '=', $id_warehouse);
        }
        if ($price_tax_excluded) {
            $stocks->where('price_te', '=', $price_tax_excluded);
        }
        return $stocks;
    }
    /**
     * For a given product, retrieves the stock in function of the delivery option
     *
     * @param int $idProduct
     * @param int $idProductAttribute optional
     * @param array $deliveryOption
     *
     * @return int quantity
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_stock_by_carrier($id_product = 0, $id_product_attribute = 0, $delivery_option = null): false|float|int
    {
        if (!(int) $id_product || !is_array($delivery_option) || !is_int($id_product_attribute)) {
            return false;
        }
        $delivery_address_id = (int) Context::get_context()->cart->id_address_delivery;
        $carrier_list = array_filter(array_map(intval(...), explode(',', (string) $delivery_option[$delivery_address_id])));
        $results = Warehouse::get_warehouses_by_product_id($id_product, $id_product_attribute);
        $stock_quantity = 0;
        $connection = Db::read_only();
        foreach ($results as $result) {
            if (isset($result['id_warehouse']) && (int) $result['id_warehouse']) {
                $warehouse_id = (int) $result['id_warehouse'];
                $ws = new Warehouse($warehouse_id);
                $carriers = $ws->get_ws_carriers();
                if (is_array($carriers) && !empty($carriers)) {
                    if ($carrier_list) {
                        $stock_quantity += $connection->get_value((new Db_Query())->select('SUM(s.`usable_quantity`) as quantity')->from('stock', 's')->left_join('warehouse_carrier', 'wc', '(wc.`id_warehouse` = s.`id_warehouse`)')->left_join('carrier', 'c', '(wc.`id_carrier` = c.`id_reference`)')->where('s.`id_product` = ' . (int) $id_product)->where('s.`id_product_attribute` = ' . $id_product_attribute)->where('s.`id_warehouse` = ' . $warehouse_id)->where('c.`id_carrier` IN (' . implode(',', $carrier_list) . ')')->group_by('s.`id_product`'));
                    }
                } else {
                    $stock_quantity += $connection->get_value((new Db_Query())->select('SUM(s.`usable_quantity`) as quantity')->from('stock', 's')->where('s.`id_product` = ' . (int) $id_product)->where('s.`id_product_attribute` = ' . $id_product_attribute)->where('s.`id_warehouse` = ' . $warehouse_id)->group_by('s.`id_product`'));
                }
            }
        }
        return $stock_quantity;
    }
    /**
     * Prevent stock operation whenever product, quantity or warehouse are invalid
     *
     * @param int $productId
     * @param int $quantity
     *
     */
    protected function should_prevent_stock_operation(Warehouse $warehouse, $product_id, $quantity): bool
    {
        if (!Validate::is_loaded_object($warehouse)) {
            return true;
        }
        if (!$quantity) {
            return true;
        }
        return !$product_id;
    }
    /**
     * @param int $stockMovementReasonId
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    protected function ensure_stock_movement_reason_is_valid($stock_movement_reason_id)
    {
        if (!Stock_Mvt_Reason::exists($stock_movement_reason_id)) {
            return Configuration::get('PS_STOCK_MVT_DEC_REASON_DEFAULT');
        }
        return $stock_movement_reason_id;
    }
    /**
     * @param int $productId
     * @param bool $shouldIgnorePack
     *
     *
     * @throws PrestaShopException
     */
    protected function should_handle_stock_operation_for_products_pack($product_id, $should_ignore_pack): bool
    {
        return Pack::is_pack((int) $product_id) && !$should_ignore_pack;
    }
    /**
     * @param int $productId
     * @param int $productAttributeId
     * @param bool $isUsable
     * @throws PrestaShopException
     */
    protected function hook_coverage_on_product_removal(Warehouse $warehouse, $product_id, $product_attribute_id, $is_usable)
    {
        if ($is_usable) {
            Hook::trigger_event('actionProductCoverage', ['id_product' => $product_id, 'id_product_attribute' => $product_attribute_id, 'warehouse' => $warehouse]);
        }
    }
    /**
     * @param int $productId
     * @param int $productAttributeId
     * @param bool $shouldHandleUsableQuantity
     *
     *
     * @throws PrestaShopException
     */
    protected function compute_product_quantity_in_stock(Warehouse $warehouse, $product_id, $product_attribute_id, $should_handle_usable_quantity, ?Stock $stock = null): int
    {
        $product_stock_criteria = ['product_id' => $product_id, 'product_attribute_id' => $product_attribute_id, 'warehouse_id' => $warehouse->id];
        $physical_product_quantity_in_stock = $this->get_physical_product_quantities($product_stock_criteria);
        $usable_product_quantity_in_stock = $this->get_usable_product_quantities($product_stock_criteria);
        if ($stock) {
            $physical_product_quantity_in_stock = $stock->physical_quantity;
            $usable_product_quantity_in_stock = $stock->usable_quantity;
        }
        $product_quantity_in_stock = $physical_product_quantity_in_stock;
        if ($should_handle_usable_quantity) {
            $product_quantity_in_stock = $usable_product_quantity_in_stock;
        }
        return (int) $product_quantity_in_stock;
    }
    /**
     * @param int $quantity
     * @param int $quantityInStock
     */
    protected function ensure_product_quantity_requested_for_removal_is_valid($quantity, $quantity_in_stock): bool
    {
        return $quantity_in_stock < $quantity;
    }
    /**
     * @param int $idProduct
     * @param int $idProductAttribute
     *
     * @return PrestaShopCollection
     *
     * @throws PrestaShopException
     */
    protected function get_product_stock_lines_in_warehouse($id_product, $id_product_attribute, Warehouse $warehouse, ?Stock $stock = null)
    {
        $stock_lines = $this->get_stock_collection($id_product, $id_product_attribute, $warehouse->id, null, $stock);
        $stock_lines->get_all();
        return $stock_lines;
    }
    /**
     * @param Employee|null $employee
     */
    protected function get_attributes_of_employee_requesting_stock_movement($employee): array
    {
        $context = Context::get_context();
        if (Validate::is_loaded_object($context->employee)) {
            return ['employee_id' => (int) $context->employee->id, 'first_name' => $context->employee->firstname, 'last_name' => $context->employee->lastname];
        }
        if (Validate::is_loaded_object($employee)) {
            return ['employee_id' => (int) $employee->id, 'first_name' => $employee->firstname, 'last_name' => $employee->lastname];
        }
        // fallback - we are in front-office context, no employee available
        return ['employee_id' => 0, 'first_name' => '', 'last_name' => ''];
    }
    /**
     * @param int $quantity
     * @param int $idStockMvtReason
     * @param bool $isUsable
     * @param int $idOrder
     * @param Employee|null $employee
     * @param Stock $stock
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function remove_product_quantity_applying_cump($quantity, $id_stock_mvt_reason, $is_usable, $id_order, $employee, $stock): void
    {
        $employee_attributes = $this->get_attributes_of_employee_requesting_stock_movement($employee);
        $movement_params = ['id_stock' => $stock->id, 'physical_quantity' => $quantity, 'id_stock_mvt_reason' => $id_stock_mvt_reason, 'id_order' => $id_order, 'price_te' => $stock->price_te, 'last_wa' => $stock->price_te, 'current_wa' => $stock->price_te, 'id_employee' => $employee_attributes['employee_id'], 'employee_firstname' => $employee_attributes['first_name'], 'employee_lastname' => $employee_attributes['last_name'], 'sign' => -1];
        if ($is_usable) {
            $usable_product_quantity = $stock->usable_quantity - $quantity;
        } else {
            $usable_product_quantity = $stock->usable_quantity;
        }
        $physical_product_quantity = $stock->physical_quantity - $quantity;
        $stock_params = ['physical_quantity' => $physical_product_quantity, 'usable_quantity' => $usable_product_quantity];
        $stock->hydrate($stock_params);
        $stock->update();
        $stock_movement = new Stock_Mvt();
        $stock_movement->hydrate($movement_params);
        $stock_movement->save();
    }
}