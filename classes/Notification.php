<?php

declare (strict_types=1);
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.thirtybees.com for more information.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */
use Thirtybees\Core\Notification\System_Notification;
/**
 * Class NotificationCore
 */
class Notification_Core
{
    protected array $types;
    protected int $employee_id;
    /**
     * @var array
     */
    protected $last_seen_ids;
    /**
     * @var array
     */
    protected $permissions;
    /**
     * NotificationCore constructor.
     *
     * @param EmployeeCore|null $employee
     * @throws PrestaShopException
     */
    public function __construct($employee = null)
    {
        if (!$employee) {
            $employee = Context::get_context()->employee;
        }
        $this->employee_id = (int) $employee->id;
        $this->permissions = [];
        foreach (Profile::get_profile_accesses($employee->id_profile, 'class_name') as $tab => $access) {
            $this->permissions[$tab] = (bool) $access['view'];
        }
        $this->types = [];
        // register build in notification types
        if (Configuration::get('PS_SHOW_NEW_ORDERS')) {
            $this->register_type('order', ['getNotifications' => $this->get_new_orders(...), 'renderer' => 'renderOrderNotification', 'rendererData' => ['orderNumber' => $this->l('Order number:'), 'total' => $this->l('Total:'), 'from' => $this->l('From:')], 'controller' => 'AdminOrders', 'icon' => 'icon-shopping-cart', 'header' => $this->l('Latest Orders'), 'emptyMessage' => $this->l('No new orders have been placed on your shop.'), 'showAll' => $this->l('Show all orders')]);
        }
        if (Configuration::get('PS_SHOW_NEW_CUSTOMERS')) {
            $this->register_type('customer', ['getNotifications' => $this->get_new_customers(...), 'renderer' => 'renderCustomerNotification', 'rendererData' => ['customerName' => $this->l('Customer name:')], 'controller' => 'AdminCustomers', 'icon' => 'icon-user', 'header' => $this->l('Latest Registrations'), 'emptyMessage' => $this->l('No new customers have registered on your shop.'), 'showAll' => $this->l('Show all customers')]);
        }
        if (Configuration::get('PS_SHOW_NEW_MESSAGES')) {
            $this->register_type('customer_message', ['getNotifications' => $this->get_new_customer_messages(...), 'renderer' => 'renderCustomerMessageNotification', 'rendererData' => ['from' => $this->l('From:')], 'controller' => 'AdminCustomerThreads', 'icon' => 'icon-envelope', 'header' => $this->l('Latest Messages'), 'emptyMessage' => $this->l('No new messages have been posted on your shop.'), 'showAll' => $this->l('Show all messages')]);
        }
        if (Configuration::get(Configuration::SHOW_NEW_SYSTEM_NOTIFICATIONS)) {
            $this->register_type('system_notification', ['getNotifications' => $this->get_new_system_notifications(...), 'renderer' => 'renderSystemNotification', 'rendererData' => [System_Notification::IMPORTANCE_LOW => $this->l('Low'), System_Notification::IMPORTANCE_MEDIUM => $this->l('Medium'), System_Notification::IMPORTANCE_HIGH => $this->l('High'), System_Notification::IMPORTANCE_URGENT => $this->l('Urgent')], 'controller' => 'AdminSystemNotification', 'icon' => 'icon-globe', 'header' => $this->l('System Notifications'), 'emptyMessage' => $this->l('No new notifications have been posted.'), 'showAll' => $this->l('Show all notifications')]);
        }
        // Register modules notification types
        foreach (static::get_module_notification_types() as $type => $definition) {
            $this->register_type($type, $definition);
        }
    }
    /**
     * Returns notification types defined by modules
     *
     * @throws PrestaShopException
     */
    protected static function get_module_notification_types()
    {
        static $module_types = null;
        if (is_null($module_types)) {
            $module_types = static::resolve_module_notification_types();
        }
        return $module_types;
    }
    /**
     * Returns notification types defined by modules
     *
     * @throws PrestaShopException
     */
    protected static function resolve_module_notification_types(): array
    {
        $module_types = [];
        $result = Hook::get_responses('actionGetNotificationType');
        foreach ($result as $module_name => $definitions) {
            if (is_array($definitions)) {
                foreach ($definitions as $type => $definition) {
                    $full_type = $module_name . '_' . $type;
                    $full_type = strtolower((string) preg_replace('/(?<!^)[A-Z]/', '_$0', $full_type));
                    $module_types[$full_type] = $definition;
                }
            }
        }
        return $module_types;
    }
    /**
     * Returns enabled notification types
     *
     * @throws PrestaShopException
     */
    public function get_types(): array
    {
        $ret = [];
        $link = Context::get_context()->link;
        foreach ($this->types as $type => $description) {
            $ret[] = ['type' => $type, 'icon' => $description['icon'], 'header' => $description['header'], 'emptyMessage' => $description['emptyMessage'], 'showAll' => $description['showAll'], 'showAllLink' => $description['showAllLink'] ?? $link->get_admin_link($description['controller'])];
        }
        return $ret;
    }
    /**
     * Initialize notifications to match current last id.
     * Used when new employee is created, to prevent too many unread notifications
     *
     * @throws PrestaShopException
     */
    public function initialize(): void
    {
        foreach ($this->get_notifications() as $notification) {
            $type = $notification['type'];
            $last_id = (int) $notification['lastId'];
            $this->mark_as_read($type, $last_id);
        }
    }
    /**
     * Returns true, if $type is supported
     *
     * @param string $type
     */
    public function has_type($type): bool
    {
        return isset($this->types[$type]);
    }
    /**
     * Returns last seen notification id for given type
     *
     * @param string $type
     * @return int
     * @throws PrestaShopException
     */
    public function get_last_seen_id($type)
    {
        if (is_null($this->last_seen_ids)) {
            $this->last_seen_ids = [];
            $types = "'" . implode("', '", array_map(p_sql(...), array_keys($this->types))) . "'";
            $sql = (new Db_Query())->select('type, last_id')->from('employee_notification')->where('id_employee = ' . $this->employee_id)->where('type IN (' . $types . ')');
            $employee_infos = Db::read_only()->get_array($sql);
            foreach ($employee_infos as $row) {
                $this->last_seen_ids[$row['type']] = (int) $row['last_id'];
            }
        }
        return $this->last_seen_ids[$type] ?? 0;
    }
    /**
     * Get all unread the notifications
     *
     * @param array $typeFilter list of types to return. Pass null to return all supported types
     *
     * @return array containing the notifications
     *
     * @throws PrestaShopException
     */
    public function get_notifications($type_filter = null): array
    {
        $notifications = [];
        foreach ($this->types as $type => $description) {
            if (!is_null($type_filter)) {
                if (!in_array($type, $type_filter)) {
                    continue;
                }
            }
            $callable = $description['getNotifications'];
            if (is_callable($callable)) {
                $notifications[] = array_merge(['type' => $type, 'renderer' => $description['renderer'], 'rendererData' => $description['rendererData']], $callable($this->get_last_seen_id($type), 5));
            }
        }
        return $notifications;
    }
    /**
     * Marks notification of given types as read
     *
     * @param string $type - notification type, must be allowed in $this->types
     * @param int $lastId - last notification id that employee seen
     * @return bool
     * @throws PrestaShopException
     */
    public function mark_as_read($type, $last_id)
    {
        if (!isset($this->types[$type])) {
            return false;
        }
        $last_id = (int) $last_id;
        if ($last_id <= 0) {
            return false;
        }
        return Db::get_instance()->insert('employee_notification', ['id_employee' => $this->employee_id, 'type' => p_sql($type), 'last_id' => $last_id], false, false, Db::REPLACE);
    }
    /**
     * Returns information about orders created after $lastId
     *
     * @param int $lastId order id
     * @param int $limit number of detail rows to return
     *
     * @throws PrestaShopException
     */
    protected function get_new_orders($last_id, $limit): array
    {
        $link = Context::get_context()->link;
        $base_sql = (new Db_Query())->from('orders', 'o')->left_join('customer', 'c', 'c.`id_customer` = o.`id_customer`')->where('`id_order` > ' . $last_id . ' ' . Shop::add_sql_restriction(false, 'o'));
        $total_sql = clone $base_sql;
        $total_sql->select('COUNT(1)');
        $detail_sql = clone $base_sql;
        $detail_sql->select('o.`id_order`, o.`total_paid`, o.`id_currency`, o.`date_add`, c.`id_customer`, CONCAT(c.`firstname`, " ", c.`lastname`) as name')->order_by('`id_order` DESC')->limit($limit);
        $connection = Db::read_only();
        $result = $connection->get_array($detail_sql);
        $total = (int) $connection->get_value($total_sql);
        $results = [];
        foreach ($result as $row) {
            $id = (int) $row['id_order'];
            $last_id = max($id, $last_id);
            $results[] = ['link' => $link->get_admin_link('AdminOrders', true, ['vieworder' => 1, 'id_order' => $id]), 'id' => $id, 'total' => Tools::display_price((float) $row['total_paid'], (int) $row['id_currency']), 'customerName' => $row['name'], 'ts' => (int) strtotime((string) $row['date_add'])];
        }
        return ['total' => $total, 'lastId' => $last_id, 'results' => $results];
    }
    /**
     * Returns information about new customers created after $lastId
     *
     * @param int $lastId order id
     * @param int $limit number of detail rows to return
     *
     * @throws PrestaShopException
     */
    protected function get_new_customers($last_id, $limit): array
    {
        $last_id = (int) $last_id;
        $link = Context::get_context()->link;
        $base_sql = (new Db_Query())->from('customer', 'c')->where('c.`deleted` = 0')->where('c.`id_customer` > ' . $last_id . ' ' . Shop::add_sql_restriction(false, 'c'));
        $total_sql = clone $base_sql;
        $total_sql->select('COUNT(1)');
        $detail_sql = clone $base_sql;
        $detail_sql->select('c.`id_customer`, c.`date_add`, CONCAT(c.`firstname`, " ", c.`lastname`) as name')->order_by('`id_customer` DESC')->limit($limit);
        $connection = Db::read_only();
        $result = $connection->get_array($detail_sql);
        $total = (int) $connection->get_value($total_sql);
        $results = [];
        foreach ($result as $row) {
            $id = (int) $row['id_customer'];
            $last_id = max($id, $last_id);
            $results[] = ['link' => $link->get_admin_link('AdminCustomers', true, ['viewcustomer' => 1, 'id_customer' => $id]), 'id' => $id, 'customerName' => $row['name'], 'ts' => (int) strtotime((string) $row['date_add'])];
        }
        return ['total' => $total, 'lastId' => $last_id, 'results' => $results];
    }
    /**
     * Returns information about customer messages created after $lastId
     *
     * @param int $lastId order id
     * @param int $limit number of detail rows to return
     *
     * @throws PrestaShopException
     */
    protected function get_new_customer_messages($last_id, $limit): array
    {
        $last_id = (int) $last_id;
        $link = Context::get_context()->link;
        $base_sql = (new Db_Query())->from('customer_message', 'c')->left_join('customer_thread', 'ct', 'c.`id_customer_thread` = ct.`id_customer_thread`')->left_join('customer', 'customer', 'ct.`id_customer` = customer.`id_customer`')->where('c.`id_customer_message` > ' . $last_id)->where('c.`id_employee` = 0')->where('ct.`id_shop` IN (' . implode(', ', Shop::get_context_list_shop_id()) . ')');
        $total_sql = clone $base_sql;
        $total_sql->select('COUNT(1)');
        $detail_sql = clone $base_sql;
        $detail_sql->select('c.`id_customer_message`, ct.`id_customer_thread`')->select('ct.`email`, c.`date_add`, customer.id_customer, customer.firstname, customer.lastname, customer.email as customerEmail')->order_by('c.`id_customer_message` DESC')->limit($limit);
        $connection = Db::read_only();
        $result = $connection->get_array($detail_sql);
        $total = (int) $connection->get_value($total_sql);
        $results = [];
        foreach ($result as $row) {
            $id = (int) $row['id_customer_message'];
            $thread_id = (int) $row['id_customer_thread'];
            $last_id = max($id, $last_id);
            $customer_id = (int) $row['id_customer'];
            if ($customer_id) {
                $email = $row['customerEmail'] ?: $row['email'];
                $from = $row['firstname'] . ' ' . $row['lastname'] . ' - ' . $email;
            } else {
                $from = $row['email'];
            }
            $results[] = ['link' => $link->get_admin_link('AdminCustomerThreads', true, ['viewcustomer_thread' => 1, 'id_customer_thread' => $thread_id]), 'id' => $id, 'from' => $from, 'ts' => (int) strtotime((string) $row['date_add'])];
        }
        return ['total' => $total, 'lastId' => $last_id, 'results' => $results];
    }
    /**
     * Returns information about system notifications created after $lastId
     *
     * @param int $lastId order id
     * @param int $limit number of detail rows to return
     *
     *
     * @throws PrestaShopException
     */
    protected function get_new_system_notifications($last_id, $limit): array
    {
        $last_id = (int) $last_id;
        $link = Context::get_context()->link;
        $detail_sql = (new Db_Query())->select('*')->from('system_notification', 'sn')->where('sn.`id_system_notification` > ' . $last_id)->order_by('sn.`id_system_notification` DESC')->limit($limit);
        $total_sql = (new Db_Query())->select('COUNT(1)')->from('system_notification', 'sn')->where('sn.`id_system_notification` > ' . $last_id);
        $connection = Db::read_only();
        $result = $connection->get_array($detail_sql);
        $total = (int) $connection->get_value($total_sql);
        $results = [];
        foreach ($result as $row) {
            $id = (int) $row['id_system_notification'];
            $last_id = max($id, $last_id);
            $results[] = ['link' => $link->get_admin_link('AdminSystemNotification', true, ['viewsystem_notification' => 1, 'id_system_notification' => $id]), 'id' => $id, 'importance' => $row['importance'], 'badgeClass' => System_Notification::get_badge_class($row['importance']), 'title' => $row['title'], 'ts' => (int) strtotime((string) $row['date_add'])];
        }
        return ['total' => $total, 'lastId' => $last_id, 'results' => $results];
    }
    /**
     * Registers new notification type
     *
     * @throws PrestaShopException
     */
    protected function register_type(string $type, array $definition): bool
    {
        // validate $definition
        $required = ['getNotifications', 'renderer', 'icon', 'header', 'emptyMessage', 'showAll'];
        foreach ($required as $item) {
            if (!isset($definition[$item])) {
                throw new Presta_Shop_Exception('Invalid notification definition "' . $type . '": missing field "' . $item . '"');
            }
        }
        if (!is_callable($definition['getNotifications'])) {
            throw new Presta_Shop_Exception('Invalid notification definition "' . $type . '": "getNotification" is not callable');
        }
        if (!isset($definition['controller']) && !isset($definition['showAllLink'])) {
            throw new Presta_Shop_Exception('Invalid notification definition "' . $type . '": "either "showAllLink" or "controller" must be specified');
        }
        if (isset($definition['controller'])) {
            $controller = $definition['controller'];
            if (!isset($this->permissions[$controller])) {
                return false;
            }
            if (!$this->permissions[$controller]) {
                return false;
            }
        }
        if (!isset($definition['rendererData'])) {
            $definition['rendererData'] = [];
        }
        $this->types[$type] = $definition;
        return true;
    }
    /**
     * Translate method
     *
     * @param string $str
     * @return string
     */
    protected function l($str)
    {
        return Translate::get_admin_translation($str, 'AdminController');
    }
}