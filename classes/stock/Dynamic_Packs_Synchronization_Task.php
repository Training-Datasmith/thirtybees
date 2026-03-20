<?php

declare (strict_types=1);
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
namespace Thirtybees\Core\Stock\Synchronization;

use Context;
use Db;
use Db_Query;
use Pack;
use Presta_Shop_Database_Exception;
use Presta_Shop_Exception;
use Stock_Available;
use Thirtybees\Core\Initialization_Callback;
use Thirtybees\Core\Work_Queue\Scheduled_Task;
use Thirtybees\Core\Work_Queue\Work_Queue_Context;
use Thirtybees\Core\Work_Queue\Work_Queue_Task;
use Thirtybees\Core\Work_Queue\Work_Queue_Task_Callable;
/**
 * Class DynamicPacksSynchronizationTaskCore
 *
 * Work queue task to synchronize dynamic packs quantities
 */
class Dynamic_Packs_Synchronization_Task_Core implements Work_Queue_Task_Callable, Initialization_Callback
{
    /**
     * Creates work queue task to synchronize packs
     *
     * @param int[] $productIds
     * @return WorkQueueTask
     */
    public static function create_task($product_ids = null)
    {
        $parameters = [];
        if (!is_null($product_ids)) {
            $parameters['productIds'] = array_filter(array_map(intval(...), $product_ids));
        }
        return Work_Queue_Task::create_task(static::get_task_name(), $parameters, Work_Queue_Context::from_context(Context::get_context()));
    }
    /**
     * Task execution method
     *
     * Synchronizes all dynamic packs
     *
     *
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function execute(Work_Queue_Context $context, array $parameters): int
    {
        $conn = Db::get_instance();
        if (isset($parameters['productIds'])) {
            $product_ids = array_filter(array_map(intval(...), $parameters['productIds']));
            $product_ids_sql = (new Db_Query())->select('DISTINCT id_product')->from('product_shop')->where('pack_dynamic')->where('id_product IN (' . implode(',', $product_ids) . ')');
            $product_ids = array_map(intval(...), array_column($conn->get_array($product_ids_sql), 'id_product'));
        } else {
            $product_ids = Pack::get_dynamic_packs();
        }
        if (!$product_ids) {
            return 0;
        }
        $product_ids = implode(',', $product_ids);
        // figure out current stocks
        $current_stock_sql = (new Db_Query())->select('s.*')->from('stock_available', 's')->where("s.id_product IN ({$product_ids})");
        $current_quantities = [];
        foreach ($conn->get_array($current_stock_sql) as $row) {
            $product_id = (int) $row['id_product'];
            $product_attribute_id = (int) $row['id_product_attribute'];
            $shop_id = (int) $row['id_shop'];
            $shop_group_id = (int) $row['id_shop_group'];
            $key = "{$shop_id}|{$shop_group_id}|{$product_id}|{$product_attribute_id}";
            $current_quantities[$key] = ['id' => (int) $row['id_stock_available'], 'quantity' => (int) $row['quantity']];
        }
        // calculate dynamic stocks
        $dynamic_stock_sql = (new Db_Query())->select('sa.id_shop')->select('sa.id_shop_group')->select('p.id_product_pack AS id_product')->select('0 AS id_product_attribute')->select('MIN(FLOOR(sa.quantity / p.quantity)) AS quantity')->from('pack', 'p')->inner_join('stock_available', 'sa', '(sa.id_product = p.id_product_item AND sa.id_product_attribute = p.id_product_attribute_item)')->where("p.id_product_pack IN ({$product_ids})")->group_by('sa.id_shop')->group_by('sa.id_shop_group')->group_by('p.id_product_pack');
        $cnt = 0;
        // update stock
        foreach ($conn->get_array($dynamic_stock_sql) as $row) {
            $product_id = (int) $row['id_product'];
            $product_attribute_id = (int) $row['id_product_attribute'];
            $shop_id = (int) $row['id_shop'];
            $shop_group_id = (int) $row['id_shop_group'];
            $key = "{$shop_id}|{$shop_group_id}|{$product_id}|{$product_attribute_id}";
            $quantity = (int) $row['quantity'];
            if (isset($current_quantities[$key])) {
                if ($current_quantities[$key]['quantity'] !== $quantity) {
                    $stock_available = new Stock_Available($current_quantities[$key]['id']);
                    $stock_available->quantity = $quantity;
                    $stock_available->update();
                    $cnt++;
                }
                unset($current_quantities[$key]);
            } else {
                $stock_available = new Stock_Available();
                $stock_available->out_of_stock = Stock_Available::out_of_stock($product_id, $shop_id);
                $stock_available->id_product = $product_id;
                $stock_available->id_product_attribute = $product_attribute_id;
                $stock_available->quantity = $quantity;
                $stock_available->id_shop = $shop_id;
                $stock_available->id_shop_group = $shop_group_id;
                $stock_available->add();
                $cnt++;
            }
        }
        // delete all residual stock
        if ($current_quantities) {
            $ids = implode(',', array_column($current_quantities, 'id'));
            $conn->delete('stock_available', "id_stock_available IN ({$ids})");
        }
        return $cnt;
    }
    /**
     * Callback method to initialize class
     *
     * @throws PrestaShopException
     */
    public static function initialization_callback(Db $conn): void
    {
        $task = static::get_task_name();
        $tracking_tasks = Scheduled_Task::get_tasks_for_callable($task);
        if (!$tracking_tasks) {
            $scheduled_task = new Scheduled_Task();
            $scheduled_task->frequency = '0 */8 * * *';
            $scheduled_task->name = 'Dynamic packs synchronization task';
            $scheduled_task->description = 'Synchronizes dynamic packs quantities';
            $scheduled_task->task = $task;
            $scheduled_task->active = true;
            $scheduled_task->add();
        }
    }
    /**
     * @return string
     */
    public static function get_task_name(): ?string
    {
        return preg_replace('/Core$/', '', static::class);
    }
}