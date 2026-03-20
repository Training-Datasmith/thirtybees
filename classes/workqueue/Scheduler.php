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

use Configuration;
use DateTime;
use Db;
use Exception;
use Presta_Shop_Exception;
use Tools;
/**
 * Class SchedulerCore
 */
class Scheduler_Core
{
    /**
     * Database lock name
     */
    public const LOCK_NAME = 'THIRTY_BEES_SCHEDULER';
    /**
     * Configuration key: Last cron event timestamp
     */
    public const CRON_EVENT_TS = 'SCHEDULER_LAST_CRON_EVENT_TS';
    /**
     * Configuration key: Synthetic cron secret
     */
    public const SYNTHETIC_CRON_SECRET = 'SCHEDULER_SYNTHETIC_CRON_SECRET';
    /**
     * Configuration key: Minimal cron interval in seconds
     */
    public const MINIMAL_CRON_INTERVAL = 'SCHEDULER_MINIMAL_CRON_INTERVAL';
    /**
     * Default value of cron interval, used if not found in configuration table
     */
    public const MINIMAL_CRON_INTERVAL_DEFAULT_VALUE = 600;
    /**
     * Hard limit for minimal cron interval. Used if configuration table contains lower value
     */
    public const MINIMAL_CRON_INTERVAL_HARD_LIMIT = 60;
    /**
     * @var WorkQueueClient work queue client
     */
    protected $work_queue_client;
    /**
     * SchedulerCore constructor.
     */
    public function __construct(
        /**
         * @var Db Database connection
         */
        protected \Db $connection,
        Work_Queue_Client $work_queue_client
    )
    {
        $this->work_queue_client = $work_queue_client;
    }
    /**
     * Returns true, if synthetic cron event is required. This happens when no other cron mechanism are installed,
     * or didn't generated event recently
     *
     * @throws PrestaShopException
     */
    public function synthetic_event_required(): bool
    {
        $now = time();
        $last_cron_event = (int) Configuration::get_global_value(static::CRON_EVENT_TS);
        $min_interval = (int) Configuration::get_global_value(static::MINIMAL_CRON_INTERVAL);
        if (!$min_interval) {
            $min_interval = static::MINIMAL_CRON_INTERVAL_DEFAULT_VALUE;
        }
        $min_interval = max($min_interval, static::MINIMAL_CRON_INTERVAL_HARD_LIMIT);
        $threshold = $last_cron_event + $min_interval;
        if ($now > $threshold) {
            return true;
        }
        return false;
    }
    /**
     * @return string
     * @throws PrestaShopException
     */
    public function get_synthetic_event_secret()
    {
        $secret = Configuration::get_global_value(static::SYNTHETIC_CRON_SECRET);
        if (!$secret) {
            $secret = Tools::passwd_gen(20);
            Configuration::update_global_value(static::SYNTHETIC_CRON_SECRET, $secret);
        }
        return $secret;
    }
    /**
     * @throws PrestaShopException
     */
    public function delete_synthetic_event_secret(): void
    {
        Configuration::update_global_value(static::SYNTHETIC_CRON_SECRET, '');
    }
    /**
     * Executes all scheduled tasks
     * @throws PrestaShopException
     */
    public function run(): void
    {
        // update last cron event timestamp
        Configuration::update_global_value(static::CRON_EVENT_TS, time());
        // disable timout limit
        @set_time_limit(0);
        if ($this->lock()) {
            try {
                $this->run_tasks();
            } finally {
                $this->release_lock();
            }
        }
    }
    /**
     * Executes all active tasks that should be executed
     *
     * @throws PrestaShopException
     */
    protected function run_tasks()
    {
        // figure out what tasks should be run
        $as_of = new DateTime();
        $checked_task_ids = [];
        $task_to_run = [];
        foreach (Scheduled_Task::get_active_tasks() as $task) {
            $checked_task_ids[] = $task->id;
            if ($task->should_run($as_of)) {
                $task_to_run[] = $task;
            }
        }
        // mark all checked tasks as checked, just in case something wrong happens later
        Scheduled_Task::mark_tasks_checked($checked_task_ids, $as_of);
        // execute all tasks
        foreach ($task_to_run as $task) {
            /** @var ScheduledTask $task  */
            $task->run($this->work_queue_client);
        }
    }
    /**
     * Tries to acquire lock
     */
    protected function lock(): bool
    {
        try {
            return (bool) (int) $this->connection->get_value("SELECT GET_LOCK('" . static::LOCK_NAME . "', 3)");
        } catch (Exception) {
            return false;
        }
    }
    /**
     * Releases lock
     *
     * @return void
     */
    protected function release_lock()
    {
        try {
            $this->connection->execute("SELECT RELEASE_LOCK('" . static::LOCK_NAME . "')");
        } catch (Exception) {
        }
    }
}