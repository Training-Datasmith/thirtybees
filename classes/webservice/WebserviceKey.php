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
use Thirtybees\Core\Initialization_Callback;
/**
 * Class WebserviceKeyCore
 */
class Webservice_Key_Core extends Object_Model implements Initialization_Callback
{
    /**
     * @var string Key
     */
    public $key;
    /**
     * @var bool Webservice Account statuts
     */
    public $active = true;
    /**
     * @var string Webservice Account description
     */
    public $description;
    /**
     * @var string php class to handle web request. Default WebserviceRequest
     */
    public $class_name;
    /**
     * @var bool is this created by external module
     */
    public $is_module;
    /**
     * @var string module name - webservice provider
     */
    public $module_name;
    /**
     * @var int context employee id
     */
    public $context_employee_id;
    /**
     * @var string image format extension to be used to return images (jpg, webp, avif, png).
     *             If null or empty, default image extension set for store will be used
     */
    public $image_extension;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'webservice_account', 'primary' => 'id_webservice_account', 'primaryKeyDbType' => 'int(11)', 'fields' => ['key' => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 32], 'description' => ['type' => self::TYPE_STRING, 'size' => Object_Model::SIZE_TEXT], 'class_name' => ['type' => self::TYPE_STRING, 'size' => 50, 'default' => 'WebserviceRequest'], 'is_module' => ['type' => self::TYPE_BOOL, 'dbType' => 'tinyint(2)', 'dbDefault' => '0'], 'module_name' => ['type' => self::TYPE_STRING, 'size' => 50], 'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(2)', 'dbNullable' => false], 'context_employee_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'], 'image_extension' => ['type' => self::TYPE_STRING, 'size' => 10]], 'keys' => ['webservice_account' => ['key' => ['type' => Object_Model::KEY, 'columns' => ['key']]], 'webservice_account_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]]];
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if (Webservice_Key::key_exists($this->key)) {
            return false;
        }
        return parent::add(true, false);
    }
    /**
     * Returns WebserviceKey instance associated with key $key
     *
     * @param string $key
     * @return static | null
     * @throws PrestaShopException
     */
    public static function get_instance_by_key($key)
    {
        static $cache = [];
        if (!$key) {
            return null;
        }
        if (!array_key_exists($key, $cache)) {
            $query = (new Db_Query())->select('id_webservice_account')->from('webservice_account')->where('`key` = "' . p_sql($key) . '"');
            $connection = Db::read_only();
            $id = (int) $connection->get_value($query);
            if ($id) {
                $cache[$key] = new static($id);
            } else {
                $cache[$key] = null;
            }
        }
        return $cache[$key];
    }
    /**
     * @param string $key
     *
     * @return boolean
     *
     * @throws PrestaShopException
     */
    public static function key_exists($key)
    {
        return Validate::is_loaded_object(static::get_instance_by_key($key));
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete()
    {
        return parent::delete() && $this->delete_associations() !== false;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_associations()
    {
        return Db::get_instance()->delete('webservice_permission', 'id_webservice_account = ' . (int) $this->id);
    }
    /**
     * @param string $authKey
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_permission_for_account($auth_key)
    {
        $result = Db::read_only()->get_array('
			SELECT p.*
			FROM `' . _DB_PREFIX_ . 'webservice_permission` p
			LEFT JOIN `' . _DB_PREFIX_ . 'webservice_account` a ON (a.id_webservice_account = p.id_webservice_account)
			WHERE a.key = \'' . p_sql($auth_key) . '\'
		');
        $permissions = [];
        if ($result) {
            foreach ($result as $row) {
                $permissions[$row['resource']][] = $row['method'];
            }
        }
        return $permissions;
    }
    /**
     * @param string $authKey
     *
     * @return boolean
     *
     * @throws PrestaShopException
     */
    public static function is_key_active($auth_key)
    {
        $instance = static::get_instance_by_key($auth_key);
        return Validate::is_loaded_object($instance) && $instance->active;
    }
    /**
     * Returns class_name associated with webservice key
     *
     * @param string $authKey
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function get_class_from_key($auth_key)
    {
        $instance = static::get_instance_by_key($auth_key);
        return Validate::is_loaded_object($instance) ? $instance->class_name : null;
    }
    /**
     * @param int $idAccount
     * @param array|null $permissionsToSet
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function set_permission_for_account($id_account, $permissions_to_set)
    {
        $ok = true;
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'webservice_permission` WHERE `id_webservice_account` = ' . (int) $id_account;
        $conn = Db::get_instance();
        if (!$conn->execute($sql)) {
            $ok = false;
        }
        if (isset($permissions_to_set)) {
            $permissions = [];
            $resources = Webservice_Request::get_resources();
            $methods = ['GET', 'PUT', 'POST', 'DELETE', 'HEAD'];
            foreach ($permissions_to_set as $resource_name => $resource_methods) {
                if (in_array($resource_name, array_keys($resources))) {
                    foreach (array_keys($resource_methods) as $method_name) {
                        if (in_array($method_name, $methods)) {
                            $permissions[] = [$method_name, $resource_name];
                        }
                    }
                }
            }
            $account = new Webservice_Key($id_account);
            if ($account->delete_associations() && $permissions) {
                $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'webservice_permission` (`id_webservice_permission` ,`resource` ,`method` ,`id_webservice_account`) VALUES ';
                foreach ($permissions as $permission) {
                    $sql .= '(NULL , \'' . p_sql($permission[1]) . '\', \'' . p_sql($permission[0]) . '\', ' . (int) $id_account . '), ';
                }
                $sql = rtrim($sql, ', ');
                if (!$conn->execute($sql)) {
                    $ok = false;
                }
            }
        }
        return $ok;
    }
    /**
     * Callback method to initialize class
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function initialization_callback(Db $conn): void
    {
        $employees = Employee::get_employees_by_profile(_PS_ADMIN_PROFILE_);
        if ($employees && count($employees) > 0) {
            $employee_id = (int) $employees[0]['id_employee'];
            $conn->update(static::$definition['table'], ['context_employee_id' => $employee_id], 'context_employee_id IS NULL OR context_employee_id = 0');
        }
    }
    /**
     * @throws PrestaShopException
     */
    public function get_image_extension(): string
    {
        if (!$this->image_extension) {
            return Image_Manager::get_default_image_extension();
        }
        $supported_extensions = Image_Manager::get_allowed_image_extensions(true, true);
        if (!in_array($this->image_extension, $supported_extensions)) {
            return Image_Manager::get_default_image_extension();
        }
        return $this->image_extension;
    }
}