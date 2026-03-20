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
namespace Thirtybees\Core\Notification;

use Configuration;
use Context;
use Db;
use Guzzle_Http\Client;
use Guzzle_Http\Exception\Guzzle_Exception;
use Module;
use Presta_Shop_Exception;
use Thirtybees\Core\Initialization_Callback;
use Thirtybees\Core\Work_Queue\Scheduled_Task;
use Thirtybees\Core\Work_Queue\Work_Queue_Context;
use Thirtybees\Core\Work_Queue\Work_Queue_Task;
use Thirtybees\Core\Work_Queue\Work_Queue_Task_Callable;
use Validate;
/**
 * Class FetchNotificationsTaskCore
 *
 * Work queue task that collects information and sends them to thirty bees api server
 */
class Fetch_Notifications_Task_Core implements Work_Queue_Task_Callable, Initialization_Callback
{
    /**
     * Returns work queue task for this callable
     *
     * @return WorkQueueTask
     */
    public static function create_task()
    {
        return Work_Queue_Task::create_task(static::class, [], Work_Queue_Context::from_context(Context::get_context()));
    }
    /**
     * Task execution method
     *
     * Collect data using all extractors that store owner gave consent. If any data are available,
     * send them to thirty bees api server
     *
     *
     *
     * @throws PrestaShopException
     */
    public function execute(Work_Queue_Context $context, array $parameters): string
    {
        $last_uuid = $this->get_last_seen_notification_uuid();
        $data = $this->fetch($last_uuid);
        $cnt = 0;
        if ($data) {
            $config = static::get_property('config', $data);
            $installation_info = static::get_property('installationInfo', $data);
            $notifications = static::get_property('notifications', $data);
            foreach ($notifications as $entry) {
                $cnt++;
                $uuid = static::get_property('uuid', $entry);
                $conditions = static::get_property('conditions', $entry, []);
                $last_uuid = $uuid;
                if ($this->accept_notification($conditions)) {
                    $notification = System_Notification::get_by_uuid($uuid);
                    if (!Validate::is_loaded_object($notification)) {
                        $notification = new System_Notification();
                    }
                    $notification->uuid = $uuid;
                    $notification->importance = static::get_property('importance', $entry);
                    $notification->title = static::get_property('title', $entry);
                    $notification->message = static::get_property('message', $entry);
                    $notification->date_created = date('Y-m-d', strtotime((string) static::get_property('date', $entry)));
                    $notification->save();
                }
            }
            $this->set_last_seen_notification_uuid($last_uuid);
            // update configurations
            Configuration::update_global_value(Configuration::BECOME_SUPPORTER_URL, $config['supporterUrl']);
            // update installation info
            if (isset($installation_info['supporter']['type']) && $installation_info['supporter']['type']) {
                $supporter = $installation_info['supporter'];
                Configuration::update_global_value(Configuration::SUPPORTER_TYPE, $supporter['type']);
                Configuration::update_global_value(Configuration::SUPPORTER_TYPE_NAME, $supporter['name']);
            } else {
                Configuration::delete_by_name(Configuration::SUPPORTER_TYPE);
                Configuration::delete_by_name(Configuration::SUPPORTER_TYPE_NAME);
            }
            Configuration::update_global_value(Configuration::CONNECTED, $installation_info['connected'] ? 1 : 0);
            if ($installation_info['sid'] !== Configuration::get_server_tracking_id()) {
                Configuration::update_global_value(Configuration::TRACKING_ID, $installation_info['sid']);
            }
            Module::process_premium_modules();
        }
        return "Retrieved {$cnt} notifications";
    }
    /**
     * Retrieves notifications from thirty bees api server
     *
     * @throws PrestaShopException
     */
    protected function fetch($last_uuid)
    {
        $guzzle = new Client(['base_uri' => Configuration::get_api_server(), 'timeout' => 15, 'verify' => Configuration::get_ssl_trust_store()]);
        try {
            $response = $guzzle->post('/notification/v1.php', ['json' => ['ts' => time(), 'lastSeen' => $last_uuid], 'headers' => ['X-SID' => Configuration::get_server_tracking_id()]]);
        } catch (Guzzle_Exception $e) {
            throw new Presta_Shop_Exception('Transport exception: ' . $e->get_message(), 0, $e);
        }
        if ($response->get_status_code() >= 300) {
            throw new Presta_Shop_Exception('Invalid response status code: ' . $response->get_status_code() . ' ' . $response->get_reason_phrase());
        }
        $body = (string) $response->get_body();
        if (!$body) {
            throw new Presta_Shop_Exception('Empty response');
        }
        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new Presta_Shop_Exception('Failed to parse response: ' . $body);
        }
        if (!isset($json['success'])) {
            throw new Presta_Shop_Exception('Invalid response payload: ' . $body);
        }
        if (!$json['success']) {
            if (isset($json['error'])) {
                throw new Presta_Shop_Exception($json['error']);
            }
            throw new Presta_Shop_Exception('Failure response: ' . $body);
        }
        return static::get_property('data', $json);
    }
    /**
     * Returns true, if this store accepts notification conditions
     *
     * @param array $conditionGroups array of arrays
     */
    protected function accept_notification($condition_groups): bool
    {
        if ($condition_groups) {
            // at least one condition group must be satisfied
            foreach ($condition_groups as $conditions) {
                if ($this->all_conditions_satisfied($conditions)) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }
    /**
     * Return true, if all conditions are satisfied
     *
     * @param array $conditions
     */
    protected function all_conditions_satisfied($conditions): bool
    {
        foreach ($conditions as $condition) {
            if (!$this->condition_satisfied($condition)) {
                return false;
            }
        }
        return true;
    }
    /**
     * Return true, if condition is satisfied
     */
    protected function condition_satisfied(array $condition)
    {
        if (isset($condition['value']) && isset($condition['compare'])) {
            $value_func = $condition['value'];
            $function = 'conditionFunc' . ucfirst($value_func);
            $compare = $condition['compare'];
            if (method_exists($this, $function)) {
                $value = $this->{$function}($condition);
                switch ($compare) {
                    case '<':
                    case 'lt':
                        return $value < $condition['argument'];
                    case '<=':
                    case 'lte':
                        return $value <= $condition['argument'];
                    case '>':
                    case 'gt':
                        return $value > $condition['argument'];
                    case '>=':
                    case 'gte':
                        return $value >= $condition['argument'];
                    case 'eq':
                    case '=':
                    case '==':
                        return $value == $condition['argument'];
                    case 'between':
                        return $value >= $condition['from'] && $value <= $condition['to'];
                    case 'version_compare':
                        return version_compare($value, $condition['version'], $condition['operator']);
                    default:
                        trigger_error("Unknown condition compare '{$compare}'", E_USER_NOTICE);
                        return false;
                }
            } else {
                trigger_error("Unknown condition value function '{$value_func}'", E_USER_NOTICE);
            }
        }
        return true;
    }
    /**
     * Returns last seen notification UUID
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected function get_last_seen_notification_uuid()
    {
        $value = Configuration::get_global_value(Configuration::LAST_SEEN_NOTIFICATION_UUID);
        if ($value) {
            return $value;
        }
        return null;
    }
    /**
     * Updates last seen notification UUID
     *
     * @param string $uuid Last seen notification UUID
     * @throws PrestaShopException
     */
    protected function set_last_seen_notification_uuid($uuid)
    {
        Configuration::update_global_value(Configuration::LAST_SEEN_NOTIFICATION_UUID, $uuid);
    }
    /**
     * Extracts value of $entry[$key], if exists
     *
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     * @throws PrestaShopException
     */
    protected static function get_property($key, array $entry, $default_value = null)
    {
        if (array_key_exists($key, $entry)) {
            return $entry[$key];
        }
        if (!is_null($default_value)) {
            return $default_value;
        }
        throw new Presta_Shop_Exception("Property '{$key}' not found");
    }
    /**
     * Callback method to initialize class
     *
     * @throws PrestaShopException
     */
    public static function initialization_callback(Db $conn): void
    {
        $task = str_replace('FetchNotificationTaskCore', 'FetchNotificationTask', static::class);
        $tracking_tasks = Scheduled_Task::get_tasks_for_callable($task);
        if (!$tracking_tasks) {
            $scheduled_task = new Scheduled_Task();
            $scheduled_task->frequency = random_int(0, 59) . ' */6 * * *';
            $scheduled_task->name = 'Thirty bees notification task';
            $scheduled_task->description = 'Retrieve thirty bees notifications from api server';
            $scheduled_task->task = $task;
            $scheduled_task->active = true;
            $scheduled_task->add();
        }
    }
    protected function condition_func_php_version(): string
    {
        return phpversion();
    }
    /**
     * @return string
     */
    protected function condition_func_tb_version()
    {
        return _TB_VERSION_;
    }
    /**
     * @return string
     */
    protected function condition_func_tb_build_php_version()
    {
        return _TB_BUILD_PHP_;
    }
    /**
     * @return string
     */
    protected function condition_func_tb_revision()
    {
        return _TB_REVISION_;
    }
    /**
     * @throws PrestaShopException
     */
    protected function condition_func_sid(): string
    {
        return (string) Configuration::get_server_tracking_id();
    }
}