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
/**
 * Class TabCore
 */
class Tab_Core extends Object_Model
{
    /**
     * @var array|null
     */
    protected static $_get_id_from_class_name;
    /**
     * Get tabs
     *
     * @return array tabs
     */
    protected static $_cache_tabs = [];
    /**
     * Displayed name
     *
     * Multilang property
     *
     * @var string|string[]
     */
    public $name;
    /**
     * @var string Class and file name
     */
    public $class_name;
    /**
     * @var string
     */
    public $module;
    /**
     * @var int parent ID
     */
    public $id_parent;
    /**
     * @var int position
     */
    public $position;
    /**
     * @var bool active
     */
    public $active = true;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'tab', 'primary' => 'id_tab', 'multilang' => true, 'fields' => [
        'id_parent' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbType' => 'int(11)', 'dbNullable' => false],
        'class_name' => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 64],
        'module' => ['type' => self::TYPE_STRING, 'validate' => 'isTabName', 'size' => 64],
        'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbNullable' => false],
        'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isTabName', 'size' => 64],
    ], 'keys' => ['tab' => ['class_name' => ['type' => Object_Model::KEY, 'columns' => ['class_name']], 'id_parent' => ['type' => Object_Model::KEY, 'columns' => ['id_parent']]]]];
    /**
     * Get tab id
     *
     * @return int tab id
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_current_tab_id()
    {
        $id_tab = Tab::get_id_from_class_name(Tools::get_value('controller'));
        // retro-compatibility 1.4/1.5
        if (empty($id_tab)) {
            return Tab::get_id_from_class_name(Tools::get_value('tab'));
        }
        return $id_tab;
    }
    /**
     * Get tab id from name
     *
     * @param string $className
     *
     * @return int|false Tab ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_id_from_class_name($class_name)
    {
        if (!is_string($class_name)) {
            return false;
        }
        $class_name = strtolower($class_name);
        if (str_ends_with($class_name, 'core')) {
            $class_name = substr($class_name, 0, -4);
        }
        if (str_ends_with($class_name, 'controller')) {
            $class_name = substr($class_name, 0, -10);
        }
        if (static::$_get_id_from_class_name === null) {
            static::$_get_id_from_class_name = [];
            $result = Db::read_only()->get_array((new Db_Query())->select('`id_tab`, `class_name`')->from('tab'));
            foreach ($result as $row) {
                static::$_get_id_from_class_name[strtolower((string) $row['class_name'])] = (int) $row['id_tab'];
            }
        }
        return isset(static::$_get_id_from_class_name[$class_name]) ? (int) static::$_get_id_from_class_name[$class_name] : false;
    }
    /**
     * Get tab parent id
     *
     * @return int tab parent id
     * @throws PrestaShopException
     */
    public static function get_current_parent_id()
    {
        $cache_id = 'getCurrentParentId_' . mb_strtolower(Tools::get_value('controller'));
        if (!Cache::is_stored($cache_id)) {
            $value = Db::read_only()->get_value((new Db_Query())->select('`id_parent`')->from('tab')->where('LOWER(`class_name`) = \'' . p_sql(mb_strtolower(Tools::get_value('controller'))) . '\''));
            if (!$value) {
                $value = -1;
            }
            Cache::store($cache_id, $value);
            return $value;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Return the list of tab used by a module
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_module_tab_list()
    {
        $list = [];
        $result = Db::read_only()->get_array((new Db_Query())->select('t.`class_name`, t.`module`')->from('tab', 't')->where('t.`module` IS NOT NULL')->where('t.`module` != ""'));
        foreach ($result as $detail) {
            $list[strtolower((string) $detail['class_name'])] = $detail;
        }
        return $list;
    }
    /**
     * @param int $idLang
     * @param int|null $idParent
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_tabs($id_lang, $id_parent = null)
    {
        if (!isset(static::$_cache_tabs[$id_lang])) {
            static::$_cache_tabs[$id_lang] = [];
            // Keep t.*, tl.name instead of only * because if translations are missing, the join on tab_lang will overwrite the id_tab in the results
            $result = Db::read_only()->get_array((new Db_Query())->select('t.*, COALESCE(NULLIF(tl.`name`, ""), tl_def.`name`) AS `name`')->from('tab', 't')->left_join('tab_lang', 'tl', 't.`id_tab` = tl.`id_tab` AND tl.`id_lang` = ' . (int) $id_lang)->left_join('tab_lang', 'tl_def', 't.`id_tab` = tl_def.`id_tab` AND tl_def.`id_lang` = ' . (int) Configuration::get('PS_LANG_DEFAULT'))->order_by('t.`position` ASC'));
            foreach ($result as $row) {
                $name = (string) $row['name'];
                if (!$name) {
                    $class = (string) $row['class_name'];
                    $name = preg_replace('/^Admin/', '', $class);
                    $name = preg_replace('/(?<!^)[A-Z]/', ' $0', (string) $name);
                    $row['name'] = $name;
                }
                if (!isset(static::$_cache_tabs[$id_lang][$row['id_parent']])) {
                    static::$_cache_tabs[$id_lang][$row['id_parent']] = [];
                }
                static::$_cache_tabs[$id_lang][$row['id_parent']][] = $row;
            }
        }
        if ($id_parent === null) {
            $array_all = [];
            foreach (static::$_cache_tabs[$id_lang] as $array_parent) {
                $array_all = array_merge($array_all, $array_parent);
            }
            return $array_all;
        }
        return static::$_cache_tabs[$id_lang][$id_parent] ?? [];
    }
    /**
     * Enabling tabs for module
     *
     * @param string $module Module Name
     *
     * @return bool Status
     *
     * @throws PrestaShopException
     */
    public static function enabling_for_module($module)
    {
        $tabs = Tab::get_collection_from_module($module);
        if (!empty($tabs)) {
            foreach ($tabs as $tab) {
                /** @var Tab $tab */
                $tab->active = 1;
                $tab->save();
            }
            return true;
        }
        return false;
    }
    /**
     * Get collection from module name
     *
     * @param string $module Module name
     * @param int|null $idLang Language ID
     *
     * @return array|PrestaShopCollection Collection of tabs (or empty array)
     *
     * @throws PrestaShopException
     */
    public static function get_collection_from_module($module, $id_lang = null)
    {
        if (is_null($id_lang)) {
            $id_lang = Context::get_context()->language->id;
        }
        if (!Validate::is_module_name($module)) {
            return [];
        }
        $tabs = new Presta_Shop_Collection('Tab', (int) $id_lang);
        $tabs->where('module', '=', $module);
        return $tabs;
    }
    /**
     * Disabling tabs for module
     *
     * @param string $module Module name
     *
     * @return bool Status
     *
     * @throws PrestaShopException
     */
    public static function disabling_for_module($module)
    {
        $tabs = Tab::get_collection_from_module($module);
        if (!empty($tabs)) {
            foreach ($tabs as $tab) {
                /** @var Tab $tab */
                $tab->active = 0;
                $tab->save();
            }
            return true;
        }
        return false;
    }
    /**
     * Get Instance from tab class name
     *
     * @param string $className Name of tab class
     * @param int|null $idLang id_lang
     *
     * @return Tab Tab object (empty if bad id or class name)
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_instance_from_class_name($class_name, $id_lang = null)
    {
        $id_tab = (int) Tab::get_id_from_class_name($class_name);
        return new Tab($id_tab, $id_lang);
    }
    /**
     * @param int $idTab
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function check_tab_rights($id_tab)
    {
        return Context::get_context()->employee->has_access($id_tab, Profile::PERMISSION_VIEW);
    }
    /**
     * @param int $idTab
     * @param array $tabs
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function recursive_tab($id_tab, $tabs = [])
    {
        $id_tab = (int) $id_tab;
        while ($id_tab) {
            $admin_tab = Tab::get_tab((int) Context::get_context()->language->id, $id_tab);
            if ($admin_tab) {
                $tabs[] = $admin_tab;
                $id_tab = (int) $admin_tab['id_parent'];
            } else {
                $id_tab = 0;
            }
        }
        return $tabs;
    }
    /**
     * Get tab
     *
     * @param int $idLang
     * @param int $idTab
     *
     * @return array tab
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_tab($id_lang, $id_tab)
    {
        $cache_id = 'Tab::getTab_' . (int) $id_lang . '-' . (int) $id_tab;
        if (!Cache::is_stored($cache_id)) {
            /* Tabs selection */
            $result = Db::read_only()->get_row((new Db_Query())->select('*')->from('tab', 't')->left_join('tab_lang', 'tl', 't.`id_tab` = tl.`id_tab` AND tl.`id_lang` = ' . (int) $id_lang)->where('t.`id_tab` = ' . (int) $id_tab));
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * @param int $idParent
     * @param int $idProfile
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_tab_by_id_profile($id_parent, $id_profile)
    {
        return Db::read_only()->get_array((new Db_Query())->select('t.`id_tab`, t.`id_parent`, tl.`name`, a.`id_profile`')->from('tab', 't')->left_join('access', 'a', 'a.`id_tab` = t.`id_tab`')->left_join('tab_lang', 'tl', 't.`id_tab` = tl.`id_tab` AND tl.`id_lang` = ' . (int) Context::get_context()->language->id)->where('a.`id_profile` = ' . (int) $id_profile)->where('t.`id_parent` = ' . (int) $id_parent)->where('a.`view` = 1')->where('a.`edit` = 1')->where('a.`delete` = 1')->where('a.`add` = 1')->where('t.`id_parent` != 0')->where('t.`id_parent` != -1')->order_by('t.`id_parent` ASC'));
    }
    /**
     * @param int $idTab
     *
     * @return array
     */
    public static function get_tab_modules_list($id_tab)
    {
        return ['default_list' => [], 'slider_list' => []];
    }
    /**
     * additionnal treatments for Tab when creating new one :
     * - generate a new position
     * - add access for admin profile
     *
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
        // @retrocompatibility with old menu (before 1.5.0.9)
        $retro = ['AdminPayment' => 'AdminParentModules', 'AdminOrders' => 'AdminParentOrders', 'AdminCustomers' => 'AdminParentCustomer', 'AdminShipping' => 'AdminParentShipping', 'AdminPreferences' => 'AdminParentPreferences', 'AdminStats' => 'AdminParentStats', 'AdminEmployees' => 'AdminAdmin'];
        $class_name = Tab::get_class_name_by_id($this->id_parent);
        if (isset($retro[$class_name])) {
            $this->id_parent = Tab::get_id_from_class_name($retro[$class_name]);
        }
        static::$_cache_tabs = [];
        // Set good position for new tab
        $this->position = Tab::get_new_last_position($this->id_parent);
        $this->module = mb_strtolower($this->module ?? '');
        // Add tab
        if (parent::add($auto_date, $null_values)) {
            //forces cache to be reloaded
            static::$_get_id_from_class_name = null;
            return Tab::init_access($this->id);
        }
        return false;
    }
    /**
     * @param int $idTab
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public static function get_class_name_by_id($id_tab)
    {
        return Db::read_only()->get_value((new Db_Query())->select('`class_name`')->from('tab')->where('`id_tab` = ' . (int) $id_tab));
    }
    /**
     * return an available position in subtab for parent $id_parent
     *
     * @param int $idParent
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_new_last_position($id_parent)
    {
        return Db::read_only()->get_value((new Db_Query())->select('IFNULL(MAX(`position`), 0) + 1')->from('tab')->where('`id_parent` = ' . (int) $id_parent));
    }
    /** When creating a new tab $id_tab, this add default rights to the table access
     *
     * @param int $idTab
     *
     * @return bool true if succeed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @todo    this should not be public static but protected
     */
    public static function init_access($id_tab, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        if (!$context->employee || !$context->employee->id_profile) {
            return false;
        }
        /* Profile selection */
        $profiles = Db::read_only()->get_array((new Db_Query())->select('`id_profile`')->from('profile')->where('`id_profile` != 1'));
        /* Query definition */
        $replace = [];
        $replace[] = ['id_profile' => 1, 'id_tab' => (int) $id_tab, 'view' => 1, 'add' => 1, 'edit' => 1, 'delete' => 1];
        foreach ($profiles as $profile) {
            $rights = $profile['id_profile'] == $context->employee->id_profile ? 1 : 0;
            $replace[] = ['id_profile' => (int) $profile['id_profile'], 'id_tab' => (int) $id_tab, 'view' => (int) $rights, 'add' => (int) $rights, 'edit' => (int) $rights, 'delete' => (int) $rights];
        }
        return Db::get_instance()->insert('access', $replace, false, true, Db::REPLACE);
    }
    /**
     * @param bool $nullValues
     * @param bool $autodate
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function save($null_values = false, $autodate = true)
    {
        static::$_get_id_from_class_name = null;
        return parent::save();
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (Db::get_instance()->delete('access', '`id_tab` = ' . (int) $this->id) && parent::delete()) {
            if (is_array(static::$_get_id_from_class_name) && isset(static::$_get_id_from_class_name[strtolower($this->class_name)])) {
                static::$_get_id_from_class_name = null;
            }
            return $this->clean_positions($this->id_parent);
        }
        return false;
    }
    /**
     * @param int $idParent
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function clean_positions($id_parent)
    {
        $result = Db::read_only()->get_array((new Db_Query())->select('`id_tab`')->from('tab')->where('`id_parent` = ' . (int) $id_parent)->order_by('position'));
        $sizeof = count($result);
        for ($i = 0; $i < $sizeof; ++$i) {
            Db::get_instance()->update('tab', ['position' => $i], '`id_tab` = ' . (int) $result[$i]['id_tab']);
        }
        return true;
    }
    /**
     * @param string $direction
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function move($direction)
    {
        $nb_tabs = Tab::get_nb_tabs($this->id_parent);
        if ($direction != 'l' && $direction != 'r') {
            return false;
        }
        if ($nb_tabs <= 1) {
            return false;
        }
        if ($direction == 'l' && $this->position <= 1) {
            return false;
        }
        if ($direction == 'r' && $this->position >= $nb_tabs) {
            return false;
        }
        $new_position = $direction == 'l' ? $this->position - 1 : $this->position + 1;
        Db::get_instance()->execute('
			UPDATE `' . _DB_PREFIX_ . 'tab` t
			SET position = ' . (int) $this->position . '
			WHERE id_parent = ' . (int) $this->id_parent . '
				AND position = ' . (int) $new_position);
        $this->position = $new_position;
        return $this->update();
    }
    /**
     * @param int|null $idParent
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_nb_tabs($id_parent = null)
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from('tab', 't')->where(!is_null($id_parent) ? 't.`id_parent` = ' . (int) $id_parent : ''));
    }
    /**
     * Overrides update to set position to last when changing parent tab
     *
     * @see ObjectModel::update
     *
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        $current_tab = new Tab($this->id);
        if ($current_tab->id_parent != $this->id_parent) {
            $this->position = Tab::get_new_last_position($this->id_parent);
        }
        static::$_cache_tabs = [];
        return parent::update($null_values);
    }
    /**
     * @param string $way
     * @param int $position
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_position($way, $position)
    {
        if (!$res = Db::read_only()->get_array((new Db_Query())->select('t.`id_tab`, t.`position`, t.`id_parent`')->from('tab', 't')->where('t.`id_parent` = ' . (int) $this->id_parent)->order_by('t.`position` ASC'))) {
            return false;
        }
        foreach ($res as $tab) {
            if ((int) $tab['id_tab'] == (int) $this->id) {
                $moved_tab = $tab;
            }
        }
        if (!isset($moved_tab) || !isset($position)) {
            return false;
        }
        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $conn = Db::get_instance();
        return $conn->update('tab', ['position' => ['type' => 'sql', 'value' => '`position` ' . ($way ? '- 1' : '+ 1')]], '`position` ' . ($way ? '> ' . (int) $moved_tab['position'] . ' AND `position` <= ' . (int) $position : '< ' . (int) $moved_tab['position'] . ' AND `position` >= ' . (int) $position) . ' AND `id_parent`=' . (int) $moved_tab['id_parent']) && $conn->update('tab', ['position' => (int) $position], '`id_parent` = ' . (int) $moved_tab['id_parent'] . ' AND `id_tab`=' . (int) $moved_tab['id_tab']);
    }
}