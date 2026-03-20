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
 * Class WorkQueueExecutor
 */
interface Work_Queue_Executor
{
    /**
     * Returns unique identifier of executor
     *
     * @return string
     */
    public function get_executor_identifier();
    /**
     * Enqueues work queue task
     *
     * @return WorkQueueFuture
     */
    public function enqueue(Work_Queue_Task $task);
    /**
     * Immediately runs work queue task, if supported
     *
     * This method must be implemented if supportsImmediateExecution()
     * method returns true
     *
     * @return WorkQueueFuture
     */
    public function run(Work_Queue_Task $task);
    /**
     * Returns true, if immediate execution is supported by this work queue
     * implementation.
     *
     * If this method returns true, then method run() must be implemented
     *
     * @return boolean
     */
    public function supports_immediate_execution();
}