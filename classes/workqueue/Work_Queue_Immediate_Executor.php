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

use Presta_Shop_Exception;
/**
 * Class WorkQueueImmediateExecutorCore
 */
class Work_Queue_Immediate_Executor_Core implements Work_Queue_Executor
{
    /**
     * Executor identifier
     */
    public const INSTANT_EXECUTOR = 'instant';
    /**
     * @var static
     */
    protected static $instance;
    /**
     * Immediately runs work queue task
     *
     * @return WorkQueueFuture work queue future descriptor
     * @throws PrestaShopException
     */
    public function enqueue(Work_Queue_Task $task)
    {
        return $this->run($task);
    }
    /**
     * Immediately runs work queue task
     *
     * @return WorkQueueFuture work queue future descriptor
     * @throws PrestaShopException
     */
    public function run(Work_Queue_Task $task)
    {
        return new Work_Queue_Future($this, $this->get_id($task), $task->run());
    }
    /**
     * @return string
     */
    public function get_executor_identifier()
    {
        return static::INSTANT_EXECUTOR;
    }
    public function supports_immediate_execution(): bool
    {
        return true;
    }
    /**
     * Generates id for task
     */
    protected function get_id(Work_Queue_Task $task): string
    {
        if ($task->id) {
            return Work_Queue_Task::class . '::' . $task->id;
        }
        return $task->task . '::' . microtime(true);
    }
    /**
     * @return static
     */
    public static function get_instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }
}