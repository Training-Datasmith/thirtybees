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
namespace Thirtybees\Core\Work_Queue;

/**
 * Class WorkQueueClientCore
 */
class Work_Queue_Client_Core
{
    /**
     * Enqueues new work queue task
     *
     * @return WorkQueueFuture work queue future descriptor
     */
    public function enqueue(Work_Queue_Task $task)
    {
        return $this->get_executor()->enqueue($task);
    }
    /**
     * Immediately executes work queue task and waits for its completion.
     *
     * If executor implementation does not support immediate execution,
     * WorkQueueImmediateExecutor will be used as a fallback
     *
     * @return WorkQueueFuture
     */
    public function run_immediately(Work_Queue_Task $task)
    {
        $executor = $this->get_executor();
        if ($executor->supports_immediate_execution()) {
            return $executor->run($task);
        }
        return $this->get_immediate_executor()->run($task);
    }
    /**
     * Returns immediate work queue executor
     *
     * @return WorkQueueExecutor
     */
    public function get_immediate_executor()
    {
        return Work_Queue_Immediate_Executor::get_instance();
    }
    /**
     * Returns work queue executor
     *
     * @return WorkQueueExecutor
     */
    public function get_executor()
    {
        return $this->get_immediate_executor();
    }
}