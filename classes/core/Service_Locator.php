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
namespace Thirtybees\Core\Dependency_Injection;

use Controller;
use Core_foundation_io_C_container;
use Db;
use Exception;
use Presta_Shop_Exception;
use Thirtybees\Core\Error\Error_Handler;
use Thirtybees\Core\Error\Response\Cli_Error_Response;
use Thirtybees\Core\Error\Response\Debug_Error_Page;
use Thirtybees\Core\Error\Response\Error_Response_Interface;
use Thirtybees\Core\Error\Response\Production_Error_Page;
use Thirtybees\Core\Work_Queue\Scheduler;
use Thirtybees\Core\Work_Queue\Work_Queue_Client;
use Throwable;
/**
 * Class ServiceLocatorCore
 */
class Service_Locator_Core
{
    // services
    public const SERVICE_SERVICE_LOCATOR = 'Thirtybees\Core\DependencyInjection\ServiceLocator';
    public const SERVICE_SCHEDULER = 'Thirtybees\Core\WorkQueue\Scheduler';
    public const SERVICE_WORK_QUEUE_CLIENT = 'Thirtybees\Core\WorkQueue\WorkQueueClient';
    public const SERVICE_READ_WRITE_CONNECTION = 'Db';
    public const SERVICE_ERROR_HANDLER = 'Thirtybees\Core\Error\ErrorHandler';
    public const SERVICE_ERROR_RESPONSE = \Thirtybees\Core\Error\Response\Error_Response_Interface::class;
    // Legacy services
    public const SERVICE_ADAPTER_CONFIGURATION = 'Core_Business_ConfigurationInterface';
    public const SERVICE_ADAPTER_DATABASE = 'Core_Foundation_Database_DatabaseInterface';
    /**
     * @var ServiceLocator singleton instance
     */
    protected static $instance;
    /**
     * @var Core_Foundation_IoC_Container container
     */
    protected \Core_foundation_io_C_container $container;
    /**
     * ServiceLocatorCore constructor
     * @throws PrestaShopException
     */
    protected function __construct(?Core_foundation_io_C_container $container = null)
    {
        $this->container = is_null($container) ? new Core_foundation_io_C_container() : $container;
        // initialize error page
        $this->container->bind(static::SERVICE_ERROR_RESPONSE, $this->get_error_response(), true);
        // initialize error handler
        if (!$this->container->knows(static::SERVICE_ERROR_HANDLER)) {
            $error_handler = new Error_Handler($this->get_by_service_name(static::SERVICE_ERROR_RESPONSE));
            $this->container->bind(static::SERVICE_ERROR_HANDLER, $error_handler, true);
        }
        // services
        $this->container->bind(static::SERVICE_SERVICE_LOCATOR, $this, true);
        $this->container->bind(static::SERVICE_WORK_QUEUE_CLIENT, static::SERVICE_WORK_QUEUE_CLIENT, true);
        $this->container->bind(static::SERVICE_SCHEDULER, static::SERVICE_SCHEDULER, true);
        $this->container->bind(static::SERVICE_READ_WRITE_CONNECTION, [Db::class, 'getInstance'], true);
        // legacy services
        $this->container->bind(static::SERVICE_ADAPTER_CONFIGURATION, 'Adapter_Configuration', true);
        $this->container->bind(static::SERVICE_ADAPTER_DATABASE, 'Adapter_Database', true);
    }
    public function get_service_locator(): static
    {
        return $this;
    }
    /**
     * Instantiates controller class
     *
     * @param string $controllerClass
     * @return Controller
     * @throws PrestaShopException
     */
    public function get_controller($controller_class)
    {
        $controller = $this->get_by_service_name($controller_class);
        if (!$controller instanceof Controller) {
            throw new Presta_Shop_Exception("Failed to construct controller, class '{$controller_class}' does not extend Controller");
        }
        return $controller;
    }
    /**
     * @return Scheduler
     * @throws PrestaShopException
     */
    public function get_scheduler()
    {
        return $this->get_by_service_name(static::SERVICE_SCHEDULER);
    }
    /**
     * @return WorkQueueClient
     * @throws PrestaShopException
     */
    public function get_work_queue_client()
    {
        return $this->get_by_service_name(static::SERVICE_WORK_QUEUE_CLIENT);
    }
    /**
     * Returns read/write connection
     *
     * @return Db
     * @throws PrestaShopException
     */
    public function get_connection()
    {
        return $this->get_by_service_name(static::SERVICE_READ_WRITE_CONNECTION);
    }
    /**
     * @return ErrorHandler
     */
    public function get_error_handler()
    {
        try {
            return $this->get_by_service_name(static::SERVICE_ERROR_HANDLER);
        } catch (Presta_Shop_Exception) {
            die('Invariant: error handler must always be known to service locator');
        }
    }
    /**
     * @param string $serviceName
     * @return mixed|object
     * @throws PrestaShopException
     */
    public function get_by_service_name($service_name)
    {
        try {
            return $this->container->make($service_name);
        } catch (Exception $e) {
            throw new Presta_Shop_Exception("Failed to construct service '{$service_name}': " . $e->get_message(), 0, $e);
        }
    }
    /**
     * @return ServiceLocator singleton instance
     */
    public static function get_instance()
    {
        if (is_null(static::$instance)) {
            die('Service locator has not been initialized yet');
        }
        return static::$instance;
    }
    /**
     * Method to initialize service locator
     */
    public static function initialize(?Core_foundation_io_C_container $container = null): void
    {
        if (!is_null(static::$instance)) {
            die('Service locator is already initialized');
        }
        try {
            static::$instance = new static($container);
        } catch (Throwable $e) {
            die('Failed to initialize service locator: ' . $e);
        }
    }
    /**
     * @return ErrorResponseInterface
     */
    protected function get_error_response(): \Thirtybees\Core\Error\Response\Cli_Error_Response|\Thirtybees\Core\Error\Response\Debug_Error_Page|\Thirtybees\Core\Error\Response\Production_Error_Page
    {
        if (php_sapi_name() === 'cli') {
            return new Cli_Error_Response();
        }
        if (_PS_MODE_DEV_) {
            return new Debug_Error_Page();
        }
        if (defined('TB_INSTALLATION_IN_PROGRESS') && TB_INSTALLATION_IN_PROGRESS) {
            return new Debug_Error_Page();
        }
        return new Production_Error_Page();
    }
}