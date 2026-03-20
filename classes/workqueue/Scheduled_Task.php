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

use DateInterval;
use DateTime;
use Db;
use Exception;
use Object_Model;
use Presta_Shop_Collection;
use Presta_Shop_Database_Exception;
use Presta_Shop_Exception;
/**
 * Class ScheduledTaskCore
 */
class Scheduled_Task_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'scheduled_task', 'primary' => 'id_scheduled_task', 'multishop' => false, 'fields' => ['frequency' => ['type' => self::TYPE_STRING, 'size' => 40, 'required' => true], 'name' => ['type' => self::TYPE_STRING, 'size' => 200, 'required' => true], 'description' => ['type' => self::TYPE_STRING, 'size' => self::SIZE_TEXT], 'task' => ['type' => self::TYPE_STRING, 'size' => 200, 'required' => true], 'payload' => ['type' => self::TYPE_STRING, 'size' => self::SIZE_MEDIUM_TEXT], 'active' => ['type' => self::TYPE_BOOL, 'required' => true, 'default' => true], 'last_execution' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'], 'last_checked' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'], 'id_employee_context' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false], 'id_shop_context' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false], 'id_customer_context' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false], 'id_language_context' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false]]];
    /**
     * @var string Cron expression
     */
    public $frequency;
    /**
     * @var string Task name
     */
    public $name;
    /**
     * @var string Task description
     */
    public $description;
    /**
     * @var string Task type
     */
    public $task;
    /**
     * @var string Json payload
     */
    public $payload;
    /**
     * @var bool Determine if task is active or not
     */
    public $active;
    /**
     * @var int|null last execution unix timestamp. Integer is used instead of date to mitigate timezones issues
     */
    public $last_execution;
    /**
     * @var int|null last checked unix timestamp. Integer is used instead of date to mitigate timezones issues
     */
    public $last_checked;
    /**
     * @var int id employee
     */
    public $id_employee_context;
    /**
     * @var int id shop
     */
    public $id_shop_context;
    /**
     * @var int id customer
     */
    public $id_customer_context;
    /**
     * @var int id language
     */
    public $id_language_context;
    /**
     * @var string Object creation date
     */
    public $date_add;
    /**
     * @var string Object update date
     */
    public $date_upd;
    /**
     * Returns all active scheduled tasks
     *
     * @return ScheduledTask[]
     * @throws PrestaShopException
     */
    public static function get_active_tasks()
    {
        $list = new Presta_Shop_Collection(static::class);
        $list->where('active', '=', 1);
        return $list->get_results();
    }
    /**
     * Returns all scheduled tasks for given callable
     *
     * @return ScheduledTask[]
     * @throws PrestaShopException
     */
    public static function get_tasks_for_callable($callable)
    {
        $list = new Presta_Shop_Collection(static::class);
        $list->where('task', '=', $callable);
        return $list->get_results();
    }
    /**
     * Mark scheduled tasks $taskIds as checked at timestamp $ts
     *
     * @param int[] $taskIds
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function mark_tasks_checked(array $task_ids, DateTime $ts): void
    {
        $task_ids = array_filter(array_map(intval(...), $task_ids));
        if ($task_ids) {
            Db::get_instance()->update(static::$definition['table'], ['last_checked' => $ts->get_timestamp()], static::$definition['primary'] . ' IN (' . implode(',', $task_ids) . ')');
        }
    }
    /**
     * Runs scheduled task
     *
     * Actual task execution is deferred to work queue. This method only creates new work queue task,
     * and mark scheduled task as executed
     *
     * @return WorkQueueFuture
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function run(Work_Queue_Client $work_queue_client)
    {
        // load context from scheduled task definition
        $context = new Work_Queue_Context($this->id_shop_context, $this->id_employee_context, $this->id_customer_context, $this->id_language_context);
        $parameters = $this->payload ? json_decode($this->payload, true) : [];
        // create and persist work queue task
        $task = Work_Queue_Task::create_task($this->task, $parameters, $context);
        $task->add();
        // create execution record
        $execution = new Scheduled_Task_Execution();
        $execution->id_scheduled_task = $this->id;
        $execution->id_workqueue_task = $task->id;
        $execution->add();
        // enqueue work queue task
        $future = $work_queue_client->enqueue($task);
        // mark task as executed
        $this->last_execution = time();
        $this->update();
        return $future;
    }
    /**
     * Returns true, if the task should be executed
     * This is determined by checking if cron expressions matched any time in range $last_checked ... $asOf
     *
     * @param DateTime $asOf
     * @return bool
     */
    public function should_run($as_of)
    {
        try {
            $from = $this->get_start_of_check_interval();
            $threshold = static::from_timestamp($as_of->get_timestamp());
            $threshold = $threshold->sub(new DateInterval('P1M'));
            if ($from < $threshold) {
                $from = $threshold;
            }
            if ($from < $as_of) {
                return static::event_occurred_in_range($this->frequency, $from, $as_of);
            }
            return false;
        } catch (Exception) {
            return false;
        }
    }
    /**
     * Returns true, if the cron $expression occurred in date range $from ... $to
     *
     * @param string $expression cron expression, such as '5 * * * *'
     * @param DateTime $from
     * @param DateTime $to
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function event_occurred_in_range($expression, $from, $to)
    {
        $matchers = static::parse_cron_expression($expression);
        try {
            $ts = $from->get_timestamp();
            $to_ts = $to->get_timestamp();
            while ($ts <= $to_ts) {
                if (static::cron_expression_matches($matchers, static::from_timestamp($ts))) {
                    return true;
                }
                $ts += 60;
            }
        } catch (Exception $e) {
            throw new Presta_Shop_Exception('Error occurred when checking cron range', 0, $e);
        }
        return false;
    }
    /**
     * Returns DateTime from which the cron expression will be check
     *
     * @return DateTime
     */
    protected function get_start_of_check_interval()
    {
        $ts = max((int) $this->last_checked, (int) $this->last_execution);
        if ($ts > 0) {
            $from = static::from_timestamp($ts);
            $from->add(new DateInterval('PT1M'));
            return $from;
        }
        return static::from_timestamp(strtotime($this->date_add));
    }
    /**
     * Returns true, if DateTime $ts matches expression
     *
     * @param array $matchers Cron expression matchers returned by method parseCronExpression
     * @param DateTime $ts timestamp to check
     * @return boolean
     */
    protected static function cron_expression_matches($matchers, $ts)
    {
        return static::cron_expression_part_matches($matchers['minute'], static::get_minute($ts)) && static::cron_expression_part_matches($matchers['hour'], static::get_hour($ts)) && static::cron_expression_part_matches($matchers['day_of_month'], static::get_day_of_month($ts)) && static::cron_expression_part_matches($matchers['month'], static::get_month($ts)) && static::cron_expression_part_matches($matchers['day_of_week'], static::get_day_of_week($ts));
    }
    /**
     * Parses cron expression, and return array of matchers.
     *
     * @param string $expression
     * @return callable[]
     * @throws PrestaShopException
     */
    protected static function parse_cron_expression($expression)
    {
        $parts = array_map(trim(...), explode(' ', $expression));
        if (count($parts) != 5) {
            throw new Presta_Shop_Exception("Invalid cron expression: '" . $expression . "'");
        }
        return ['minute' => static::get_matcher($parts[0]), 'hour' => static::get_matcher($parts[1]), 'day_of_month' => static::get_matcher($parts[2]), 'month' => static::get_matcher($parts[3]), 'day_of_week' => static::get_matcher($parts[4])];
    }
    /**
     * Parses sub cron expression, such as '*' or '1-7' and returns matcher for it
     *
     * @param string $expression
     * @return callable
     * @throws PrestaShopException
     */
    protected static function get_matcher($expression)
    {
        // any character
        if ($expression === '*') {
            return fn() => true;
        }
        // specific number, ie: '3'
        $int_value = (int) $expression;
        if ($int_value >= 0 && "{$int_value}" === $expression) {
            return fn($input) => (int) $input === $int_value;
        }
        // numeric interval, ie: '2-5'
        if (preg_match('/^([0-9]+)-([0-9]+)/', $expression, $matches)) {
            $min = (int) $matches[1];
            $max = (int) $matches[2];
            return function ($input) use ($min, $max): bool {
                $input = (int) $input;
                return $input >= $min && $input <= $max;
            };
        }
        // n-th value, ie: '*/5'
        if (preg_match("/^\\*\\/([0-9]+)\$/", $expression, $matches)) {
            $modulo = (int) $matches[1];
            if ($modulo === 0) {
                throw new Presta_Shop_Exception("Invalid expression: {$expression}': division by zero");
            }
            return fn($input) => $input % $modulo === 0;
        }
        throw new Presta_Shop_Exception("Invalid expression: {$expression}'");
    }
    /**
     * Return true, if $matcher matches $value
     *
     * @param callable $matcher callable that expects int, and return boolean
     * @param int $value input value
     *
     * @return boolean
     */
    protected static function cron_expression_part_matches($matcher, $value)
    {
        return !!$matcher((int) $value);
    }
    /**
     * Helper method to return minute part of the datetime object
     *
     * @return int minute of the hour, in rage 0-59
     */
    protected static function get_minute(DateTime $ts)
    {
        return (int) $ts->format('i');
    }
    /**
     * Helper method to return hour part of the datetime object
     *
     * @return int hour of the day, in range 0-23
     */
    protected static function get_hour(DateTime $ts)
    {
        return (int) $ts->format('G');
    }
    /**
     * Helper method to return day of month part of the datetime object
     *
     * @return int day of month, in range 1-31
     */
    protected static function get_day_of_month(DateTime $ts)
    {
        return (int) $ts->format('j');
    }
    /**
     * Helper method to return month the year part of the datetime object
     *
     * @return int month index, in range 1-12
     */
    protected static function get_month(DateTime $ts)
    {
        return (int) $ts->format('n');
    }
    /**
     * Helper method to return day of the week part of the datetime object
     *
     * @return int day of the week, in range 0-6. 0 represents Sunday, 6 represents Saturday
     */
    protected static function get_day_of_week(DateTime $ts)
    {
        return (int) $ts->format('w');
    }
    /**
     * Helper method to create DateTime object from unit timestamp
     *
     * @param int $ts unit timestamp
     * @return DateTime
     */
    protected static function from_timestamp($ts)
    {
        $date = new DateTime();
        $date->set_timestamp($ts);
        return $date;
    }
}