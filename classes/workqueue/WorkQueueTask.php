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

use Object_Model;
use Presta_Shop_Exception;
use ReflectionClass;
use Reflection_Exception;
use Thirtybees\Core\Dependency_Injection\Service_Locator;
use Throwable;
/**
 * Class WorkQueueTaskCore
 */
class Work_Queue_Task_Core extends Object_Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_FAILURE = 'failure';
    public const STATUS_SUCCESS = 'success';
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'workqueue_task', 'primary' => 'id_workqueue_task', 'multishop' => false, 'fields' => [
        // task definition
        'task' => ['type' => self::TYPE_STRING, 'size' => 200, 'required' => true],
        'payload' => ['type' => self::TYPE_STRING, 'size' => self::SIZE_MEDIUM_TEXT],
        // information about running
        'status' => ['type' => self::TYPE_STRING, 'required' => true, 'values' => [self::STATUS_PENDING, self::STATUS_RUNNING, self::STATUS_SUCCESS, self::STATUS_FAILURE]],
        'date_start' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => false],
        'duration' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'required' => false],
        'result' => ['type' => self::TYPE_STRING, 'size' => self::SIZE_MEDIUM_TEXT],
        'error' => ['type' => self::TYPE_STRING, 'size' => self::SIZE_MEDIUM_TEXT],
        // context fields
        'id_employee_context' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],
        'id_shop_context' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],
        'id_customer_context' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],
        'id_language_context' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => false],
        // record information
        'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
    ]];
    /**
     * @var string Work queue task identifier, matches classname of WorkQueueTaskCallable
     */
    public $task;
    /**
     * @var string json serialized task parameters
     */
    public $payload;
    /**
     * @var string current task status
     */
    public $status;
    /**
     * @var string datetime of task execution start
     */
    public $date_start;
    /**
     * @var float task duration in seconds
     */
    public $duration;
    /**
     * @var string result
     */
    public $result;
    /**
     * @var string error description
     */
    public $error;
    /**
     * @var int Context value: employee id
     */
    public $id_employee_context;
    /**
     * @var int Context value: shop id
     */
    public $id_shop_context;
    /**
     * @var int Context value: customer id
     */
    public $id_customer_context;
    /**
     * @var int Context value: language id
     */
    public $id_language_context;
    /**
     * @var string datetime when record has been created
     */
    public $date_add;
    /**
     * @var string datetime when record has been updated
     */
    public $date_upd;
    /**
     * @var WorkQueueContext transient object containing execution context
     */
    protected $context;
    /**
     * @var array transient object containing deserialized parameters
     */
    protected $parameters;
    /**
     * @var float transient object, containing timestamp of execution start
     */
    protected $start;
    /**
     * @var array WorkQueueTaskCallable cache map
     */
    protected static $callable_map = [];
    /**
     * Creates new task
     *
     * @param string $task
     *
     * @return static
     */
    public static function create_task($task, array $parameters, Work_Queue_Context $context)
    {
        $instance = new static();
        $instance->task = $task;
        $instance->payload = json_encode($parameters);
        $instance->parameters = $parameters;
        $instance->status = self::STATUS_PENDING;
        $instance->context = $context;
        $instance->id_employee_context = $context->get_employee_id();
        $instance->id_shop_context = $context->get_shop_id();
        $instance->id_customer_context = $context->get_customer_id();
        $instance->id_language_context = $context->get_language_id();
        return $instance;
    }
    /**
     * WorkQueueTaskCore constructor.
     *
     * @param int|null $id
     * @throws PrestaShopException
     */
    public function __construct($id = null)
    {
        parent::__construct($id);
        if ($this->id) {
            $this->context = new Work_Queue_Context($this->id_shop_context, $this->id_employee_context, $this->id_customer_context, $this->id_language_context);
            $this->parameters = $this->payload ? json_decode($this->payload, true) : [];
        }
    }
    /**
     * Runs task
     *
     * This method executes task, handles all exceptions and errors
     *
     * @return string
     */
    public function run()
    {
        $error_handler = Service_Locator::get_instance()->get_error_handler();
        $previous_fatal_error_handler = $error_handler->set_fatal_error_handler($this->fatal_error_handler(...));
        $this->start = microtime(true);
        $this->status = static::STATUS_RUNNING;
        $this->date_start = date('Y-m-d H:i:s');
        $this->save_record(false);
        try {
            $this->result = $this->execute();
            $this->status = static::STATUS_SUCCESS;
            $this->error = null;
            $this->duration = microtime(true) - $this->start;
            $this->save_record(false);
        } catch (Throwable $e) {
            $this->status = static::STATUS_FAILURE;
            $this->duration = date('Y-m-d H:i:s');
            $this->result = null;
            $this->error = $e->__toString();
            $this->duration = microtime(true) - $this->start;
            $this->save_record(true);
        } finally {
            $error_handler->set_fatal_error_handler($previous_fatal_error_handler);
        }
        return $this->status;
    }
    /**
     * Executes task, does not handle and task persistence
     * @throws Throwable
     */
    public function execute()
    {
        $callable = static::get_task_callable($this->task);
        return $callable->execute($this->context, $this->parameters);
    }
    /**
     * Called when unrecoverable error during execution has been encountered
     *
     * @param array $error
     */
    public function fatal_error_handler($error): void
    {
        $this->status = static::STATUS_FAILURE;
        $this->result = null;
        $this->error = 'Error: ';
        if (isset($error['message'])) {
            $this->error .= $error['message'];
        } else {
            $this->error .= 'Unknown error';
        }
        if (isset($error['file'])) {
            $this->error .= ' in file ' . $error['file'];
        }
        if (isset($error['line'])) {
            $this->error .= ' at line ' . $error['line'];
        }
        $this->duration = microtime(true) - $this->start;
        $this->save_record(true);
    }
    /**
     * Saves this record to the database, if
     *  - it already exists ($this->id is set)
     *  - or if $force parameter is true
     *
     * @param bool $force if true, then record will be saved even if not exists yet
     */
    protected function save_record($force)
    {
        if ($force || $this->id) {
            try {
                $this->save();
            } catch (Throwable $e) {
                $this->error = $e->__toString();
            }
        }
    }
    /**
     * Resolves callable to handle the task execution
     *
     * @param string $task
     * @return WorkQueueTaskCallable
     *
     * @throws PrestaShopException
     */
    protected static function get_task_callable($task)
    {
        if (!isset(static::$callable_map[$task])) {
            if (class_exists($task)) {
                try {
                    $reflection = new ReflectionClass($task);
                    if (!$reflection->is_instantiable()) {
                        throw new Presta_Shop_Exception("Can't instantiate class {$task}");
                    }
                    if (!$reflection->implements_interface(Work_Queue_Task_Callable::class)) {
                        throw new Presta_Shop_Exception("Class {$task} does not implements WorkQueueTaskCallable interface");
                    }
                    $instance = $reflection->new_instance();
                    static::$callable_map[$task] = $instance;
                } catch (Reflection_Exception $e) {
                    throw new Presta_Shop_Exception('Failed to instantiate WorkQueueTaskCallable class ' . $task, 0, $e);
                }
            } else {
                throw new Presta_Shop_Exception('Failed to resolve WorkQueueTaskCallable class ' . $task);
            }
        }
        return static::$callable_map[$task];
    }
}