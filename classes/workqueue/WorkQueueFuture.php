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
 * Class WorkQueueFutureCore
 */
class Work_Queue_Future_Core
{
    /**
     * @var string work queue implementation
     */
    protected $implementation;
    /**
     * @var string task status
     */
    protected string $status;
    /**
     * WorkQueueFutureCore constructor.
     *
     * @param string $id
     * @throws PrestaShopException
     */
    public function __construct(Work_Queue_Executor $executor, protected $id, string $status)
    {
        if (!in_array($status, [Work_Queue_Task::STATUS_PENDING, Work_Queue_Task::STATUS_RUNNING, Work_Queue_Task::STATUS_SUCCESS, Work_Queue_Task::STATUS_FAILURE])) {
            throw new Presta_Shop_Exception('Invalid work queue status: ' . $status);
        }
        $this->implementation = $executor->get_executor_identifier();
        $this->status = $status;
    }
    /**
     * @return string
     */
    public function get_id()
    {
        return $this->id;
    }
    /**
     * @return string
     */
    public function get_implementation()
    {
        return $this->implementation;
    }
    /**
     * @return string
     */
    public function get_status()
    {
        return $this->status;
    }
}