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
use Core_Updater\Table_Schema;
/**
 * Class ProfileCore
 */
class Profile_Core extends Object_Model
{
    public const PERMISSION_VIEW = 'view';
    public const PERMISSION_ADD = 'add';
    public const PERMISSION_EDIT = 'edit';
    public const PERMISSION_DELETE = 'delete';
    /**
     * @var array
     */
    protected static $_cache_accesses = [];
    /**
     * @var array
     */
    protected static $_cache_permissions = [];
    /**
     * @var string|string[] Name
     */
    public $name;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'profile', 'primary' => 'id_profile', 'multilang' => true, 'fields' => [
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
    ]];
    /**
     * Get all available profiles
     *
     * @param int $idLang
     *
     * @return array Profiles
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_profiles($id_lang)
    {
        return Db::read_only()->get_array((new Db_Query())->select('p.`id_profile`, `name`')->from('profile', 'p')->left_join('profile_lang', 'pl', 'p.`id_profile` = pl.`id_profile`')->where('`id_lang` = ' . (int) $id_lang)->order_by('`id_profile` ASC'));
    }
    /**
     * Get the current profile name
     *
     * @param int $idProfile
     * @param int|null $idLang
     *
     * @return string|false Profile
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_profile($id_profile, $id_lang = null)
    {
        if (!$id_lang) {
            $id_lang = Configuration::get('PS_LANG_DEFAULT');
        }
        return Db::read_only()->get_value((new Db_Query())->select('`name`')->from('profile', 'p')->left_join('profile_lang', 'pl', 'p.`id_profile` = pl.`id_profile`')->where('p.`id_profile` = ' . (int) $id_profile)->where('pl.`id_lang` = ' . (int) $id_lang));
    }
    /**
     * @param int $idProfile
     * @param int $idTab
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_profile_access($id_profile, $id_tab)
    {
        $id_profile = (int) $id_profile;
        $accesses = Profile::get_profile_accesses($id_profile);
        if (isset($accesses[$id_tab]) && is_array($accesses[$id_tab])) {
            return $accesses[$id_tab];
        }
        $perm = static::format_permission_value($id_profile === _PS_ADMIN_PROFILE_);
        return ['id_profile' => $id_profile, 'id_tab' => $id_tab, 'class_name' => '', 'view' => $perm, 'add' => $perm, 'edit' => $perm, 'delete' => $perm];
    }
    /**
     * Returns permission level
     *
     * @param int $idProfile
     * @param string $group
     * @param string $permission
     *
     * @return string | false
     *
     * @throws PrestaShopException
     */
    public static function get_profile_permission($id_profile, $group, $permission)
    {
        $id_profile = (int) $id_profile;
        if (!isset(static::$_cache_permissions[$id_profile])) {
            static::$_cache_permissions[$id_profile] = static::load_permissions($id_profile);
        }
        return static::$_cache_permissions[$id_profile][$group][$permission] ?? false;
    }
    /**
     * Loads profile permissions from database
     *
     * @param int $idProfile
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function load_permissions($id_profile)
    {
        $data = Db::read_only()->get_array((new Db_Query())->from('profile_permission')->where('id_profile = ' . (int) $id_profile));
        $result = [];
        foreach ($data as $row) {
            $group = $row['perm_group'];
            $permission = $row['permission'];
            $level = $row['level'];
            if (!isset($result[$group])) {
                $result[$group] = [];
            }
            $result[$group][$permission] = $level;
        }
        return $result;
    }
    /**
     * @param int $idProfile
     * @param string $type
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_profile_accesses($id_profile, $type = 'id_tab')
    {
        $id_profile = (int) $id_profile;
        if (!in_array($type, ['id_tab', 'class_name'])) {
            return false;
        }
        if (!isset(static::$_cache_accesses[$id_profile])) {
            static::$_cache_accesses[$id_profile] = [];
        }
        if (!isset(static::$_cache_accesses[$id_profile][$type])) {
            static::$_cache_accesses[$id_profile][$type] = [];
            // Super admin profile has full auth
            if ($id_profile === _PS_ADMIN_PROFILE_) {
                foreach (Tab::get_tabs(Context::get_context()->language->id) as $tab) {
                    static::$_cache_accesses[$id_profile][$type][$tab[$type]] = ['id_profile' => _PS_ADMIN_PROFILE_, 'id_tab' => $tab['id_tab'], 'class_name' => $tab['class_name'], 'view' => static::format_permission_value(true), 'add' => static::format_permission_value(true), 'edit' => static::format_permission_value(true), 'delete' => static::format_permission_value(true)];
                }
            } else {
                $result = Db::read_only()->get_array((new Db_Query())->select('*')->from('access', 'a')->left_join('tab', 't', 't.`id_tab` = a.`id_tab`')->where('`id_profile` = ' . $id_profile));
                foreach ($result as $row) {
                    $row[static::PERMISSION_VIEW] = static::format_permission_value($row['view']);
                    $row[static::PERMISSION_ADD] = static::format_permission_value($row['add']);
                    $row[static::PERMISSION_EDIT] = static::format_permission_value($row['edit']);
                    $row[static::PERMISSION_DELETE] = static::format_permission_value($row['delete']);
                    static::$_cache_accesses[$id_profile][$type][$row[$type]] = $row;
                }
            }
        }
        return static::$_cache_accesses[$id_profile][$type];
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if (parent::add($auto_date, true)) {
            $conn = Db::get_instance();
            $result = $conn->execute('INSERT INTO ' . _DB_PREFIX_ . 'access (SELECT ' . (int) $this->id . ', id_tab, 0, 0, 0, 0 FROM ' . _DB_PREFIX_ . 'tab)');
            return $conn->execute('
				INSERT INTO ' . _DB_PREFIX_ . 'module_access
				(`id_profile`, `id_module`, `configure`, `view`, `uninstall`)
				(SELECT ' . (int) $this->id . ', id_module, 0, 1, 0 FROM ' . _DB_PREFIX_ . 'module)
			') && $result;
        }
        return false;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (parent::delete()) {
            $conn = Db::get_instance();
            return $conn->delete('access', '`id_profile` = ' . (int) $this->id) && $conn->delete('module_access', '`id_profile` = ' . (int) $this->id);
        }
        return false;
    }
    /**
     * @param TableSchema $table
     */
    public static function process_table_schema($table): void
    {
        if ($table->get_name_without_prefix() === 'profile_lang') {
            $table->reorder_columns(['id_lang', 'id_profile']);
        }
    }
    /**
     * Invalidates cache for permissions
     *
     * @param int $profileId
     */
    public static function invalidate_cache($profile_id): void
    {
        if (isset(static::$_cache_permissions[$profile_id])) {
            unset(static::$_cache_permissions[$profile_id]);
        }
        if (isset(static::$_cache_accesses[$profile_id])) {
            unset(static::$_cache_accesses[$profile_id]);
        }
    }
    /**
     * Returns true, if $permission is a valid permission type: view, delete, add, edit
     *
     * @param string $permission
     * @return bool
     */
    public static function is_valid_permission($permission)
    {
        return in_array((string) $permission, [Profile::PERMISSION_VIEW, Profile::PERMISSION_DELETE, Profile::PERMISSION_ADD, Profile::PERMISSION_EDIT]);
    }
    /**
     * Helper method to format permission value. In the future, this will return boolean value.
     * For compatibility reasons we have to use string values now
     *
     * @param bool $hasPermission
     * @return string
     */
    public static function format_permission_value($has_permission)
    {
        return $has_permission ? '1' : '0';
    }
}