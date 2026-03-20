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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */
/**
 * Class Core_Business_Stock_StockManager
 */
class Core_business_stock_stock_Manager
{
    /**
     * This will update a Pack quantity and will decrease the quantity of containing Products if needed.
     *
     * @param Product $product A product pack object to update its quantity
     * @param StockAvailable $stockAvailable the stock of the product to fix with correct quantity
     * @param int $deltaQuantity The movement of the stock (negative for a decrease)
     * @param int|null $idShop Opional shop ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_pack_quantity($product, $stock_available, $delta_quantity, $id_shop = null)
    {
        $delta_quantity = (int) $delta_quantity;
        if ($delta_quantity !== 0) {
            // update pack items quantities, if necessary
            if ($product->pack_dynamic || $product->should_adjust_pack_items_quantities()) {
                /** @var Adapter_PackItemsManager $packItemsManager */
                $pack_items_manager = Adapter_service_Locator::get('Adapter_PackItemsManager');
                $products_pack = $pack_items_manager->get_pack_items($product);
                /** @var Adapter_StockManager $stockManager */
                $stock_manager = Adapter_service_Locator::get('Adapter_StockManager');
                foreach ($products_pack as $product_pack) {
                    $product_stock_available = $stock_manager->get_stock_available_by_product($product_pack, $product_pack->id_pack_product_attribute, $id_shop);
                    $product_stock_available->quantity = $product_stock_available->quantity + $delta_quantity * $product_pack->pack_quantity;
                    $product_stock_available->update();
                }
            }
            // update pack quantity
            if ($product->pack_dynamic) {
                Stock_Available::synchronize_dynamic_pack($product->id);
            } else if ($product->should_adjust_pack_quantity()) {
                $stock_available->quantity = $stock_available->quantity + $delta_quantity;
                $stock_available->update();
            }
        }
    }
    /**
     * Will update Product available stock int he given declinaison. If product is a Pack, could decrease the sub products.
     * If Product is contained in a Pack, Pack could be decreased or not (only if sub product stocks become not sufficient).
     *
     * @param Product $product The product to update its stockAvailable
     * @param integer $idProductAttribute The declinaison to update (null if not)
     * @param integer $deltaQuantity The quantity change (positive or negative)
     * @param int|null $idShop Optional
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_quantity($product, $id_product_attribute, $delta_quantity, $id_shop = null)
    {
        $delta_quantity = (int) $delta_quantity;
        if ($delta_quantity !== 0) {
            /** @var Adapter_StockManager $stockManager */
            $stock_manager = Adapter_service_Locator::get('Adapter_StockManager');
            $stock_available = $stock_manager->get_stock_available_by_product($product, $id_product_attribute, $id_shop);
            if (Validate::is_loaded_object($stock_available)) {
                /** @var Adapter_PackItemsManager $packItemsManager */
                $pack_items_manager = Adapter_service_Locator::get('Adapter_PackItemsManager');
                // Update quantity of the pack products
                if ($pack_items_manager->is_pack($product)) {
                    // The product is a pack
                    $this->update_pack_quantity($product, $stock_available, $delta_quantity, $id_shop);
                } else {
                    // The product is not a pack
                    $stock_available->quantity = $stock_available->quantity + $delta_quantity;
                    $stock_available->update();
                    // adjust packs this item might be in
                    $packs = $pack_items_manager->get_packs_containing_item($product, $id_product_attribute);
                    $dynamic_packs = [];
                    foreach ($packs as $pack) {
                        if ($pack->pack_dynamic) {
                            // dynamic pack, synchronize
                            $dynamic_packs[] = $pack->id;
                        } else if ($pack->get_pack_stock_type() === Pack::STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS) {
                            // pack with 'Decrement both' settings, adjust quantity only when item quantity decreased
                            if ($delta_quantity < 0) {
                                $quantity_by_pack = $pack->pack_item_quantity;
                                $max_pack_quantity = max(0, floor($stock_available->quantity / $quantity_by_pack));
                                $stock_available_pack = $stock_manager->get_stock_available_by_product($pack, null, $id_shop);
                                if ($stock_available_pack->quantity > $max_pack_quantity) {
                                    $stock_available_pack->quantity = $max_pack_quantity;
                                    $stock_available_pack->update();
                                }
                            }
                        }
                    }
                    if ($dynamic_packs) {
                        Stock_Available::synchronize_dynamic_packs($dynamic_packs);
                    }
                }
            }
        }
    }
}