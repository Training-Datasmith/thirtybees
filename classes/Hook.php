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
 * Class HookCore
 */
class Hook_Core extends Object_Model
{
    /**
     * @var array List of executed hooks on this page
     */
    public static $executed_hooks = [];
    /**
     * @var array
     */
    public static $native_module;
    /**
     * @var string Hook name identifier
     */
    public $name;
    /**
     * @var string Hook title (displayed in BO)
     */
    public $title;
    /**
     * @var string Hook description
     */
    public $description;
    /**
     * @var bool
     */
    public $position = false;
    /**
     * @var bool Is this hook usable with live edit ?
     */
    public $live_edit = false;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'hook', 'primary' => 'id_hook', 'fields' => ['name' => ['type' => self::TYPE_STRING, 'validate' => 'isHookName', 'required' => true, 'size' => 64, 'unique' => 'hook_name'], 'title' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64, 'dbNullable' => false], 'description' => ['type' => self::TYPE_HTML, 'validate' => 'isCleanHtml', 'size' => Object_Model::SIZE_TEXT], 'position' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'], 'live_edit' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0']]];
    /**
     * Return Hooks List
     *
     * @param bool $position
     *
     * @return array Hooks List
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_hooks($position = false)
    {
        return Db::read_only()->get_array('
			SELECT * FROM `' . _DB_PREFIX_ . 'hook` h
			' . ($position ? 'WHERE h.`position` = 1' : '') . '
			ORDER BY `name`');
    }
    /**
     * Return hook ID from name
     *
     * @throws PrestaShopException
     */
    public static function get_name_by_id($hook_id)
    {
        $cache_id = 'hook_namebyid_' . $hook_id;
        if (!Cache::is_stored($cache_id)) {
            $result = Db::read_only()->get_value('
							SELECT `name`
							FROM `' . _DB_PREFIX_ . 'hook`
							WHERE `id_hook` = ' . (int) $hook_id);
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Return hook live edit bool from ID
     *
     * @throws PrestaShopException
     */
    public static function get_live_edit_by_id($hook_id)
    {
        $cache_id = 'hook_live_editbyid_' . $hook_id;
        if (!Cache::is_stored($cache_id)) {
            $result = Db::read_only()->get_value('
							SELECT `live_edit`
							FROM `' . _DB_PREFIX_ . 'hook`
							WHERE `id_hook` = ' . (int) $hook_id);
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Return Hooks List
     *
     * @param int $idHook
     * @param int $idModule
     *
     * @return array Modules List
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_modules_from_hook($id_hook, $id_module = null)
    {
        $hm_list = static::get_hook_module_list();
        $module_list = $hm_list[$id_hook] ?? [];
        if ($id_module) {
            return isset($module_list[$id_module]) ? [$module_list[$id_module]] : [];
        }
        return $module_list;
    }
    /**
     * Get list of all registered hooks with modules
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_hook_module_list()
    {
        $cache_id = 'hook_module_list';
        if (!Cache::is_stored($cache_id)) {
            $results = Db::read_only()->get_array('
                SELECT h.id_hook, h.name AS h_name, h.title, h.description, h.position, h.live_edit, hm.position AS hm_position, m.id_module, m.name, m.active
                FROM `' . _DB_PREFIX_ . 'hook_module` hm
                STRAIGHT_JOIN `' . _DB_PREFIX_ . 'hook` h ON (h.id_hook = hm.id_hook AND hm.id_shop = ' . (int) Context::get_context()->shop->id . ')
                STRAIGHT_JOIN `' . _DB_PREFIX_ . 'module` AS m ON (m.id_module = hm.id_module)
                ORDER BY hm.position');
            $list = [];
            foreach ($results as $result) {
                if (!isset($list[$result['id_hook']])) {
                    $list[$result['id_hook']] = [];
                }
                $list[$result['id_hook']][$result['id_module']] = ['id_hook' => $result['id_hook'], 'title' => $result['title'], 'description' => $result['description'], 'hm.position' => $result['position'], 'live_edit' => $result['live_edit'], 'm.position' => $result['hm_position'], 'id_module' => $result['id_module'], 'name' => $result['name'], 'active' => $result['active']];
            }
            Cache::store($cache_id, $list);
            return $list;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Executes displayable hook and returns HTML string
     *
     * If $moduleId parameter is provided, only hook from single module will be executed. Otherwise, all modules
     * will be invoked, and their HTML results will be concatenated.
     *
     * Module hook response is expected to be HTML code. Responses can be extended with additional HTML elements
     * to support Live Edit functionality, FPC hole punching, etc. Do not use for hooks that return different type
     * of data (for example javascript, json, or xml), as this post-processing would break it.
     *
     * @param string $hookName Hook Name
     * @param array $hookArgs Parameters for the functions
     * @param int|null $moduleId Execute hook for this module only
     *
     *
     * @throws PrestaShopException
     * @since 1.5.0
     */
    public static function display_hook(string $hook_name, array $hook_args = [], ?int $module_id = null): string
    {
        return (string) static::exec($hook_name, $hook_args, $module_id);
    }
    /**
     * Triggers event
     *
     * Used to notify modules about some events
     *
     * @param string $hookName Hook Name
     * @param array $hookArgs Parameters for the functions
     * @param int|null $idShop If specified, hook will be executed for shop with this ID
     * @param int|null $idModule Execute hook for this module only
     *
     *
     * @throws PrestaShopException
     * @since 1.5.0
     */
    public static function trigger_event(string $hook_name, array $hook_args = [], ?int $id_shop = null, ?int $id_module = null): void
    {
        static::exec_without_cache($hook_name, $hook_args, $id_module, true, false, false, $id_shop);
    }
    /**
     * Query modules for response
     *
     * This hook is used to query modules for some kind of informations. Returns array - map from module name to result
     *
     * Modules can return arbitrary data
     *
     * @param string $hookName Hook Name
     * @param array $hookArgs Parameters for the functions
     * @param int|null $idModule Execute hook for this module only
     *
     *
     * @throws PrestaShopException
     * @since 1.5.0
     */
    public static function get_responses(string $hook_name, array $hook_args = [], ?int $id_module = null): array
    {
        $ret = static::exec_without_cache($hook_name, $hook_args, $id_module, true, false);
        if (is_array($ret)) {
            return $ret;
        }
        trigger_error('Hook::execWithoutCache did not return array. Ignoring result');
        return [];
    }
    /**
     * Returns hook response from specific module
     *
     * @param string $hookName Hook Name
     * @param int $idModule Execute hook for this module only
     * @param array $hookArgs Parameters for the functions
     *
     * @return mixed|null
     *
     * @throws PrestaShopException
     *
     * @since 1.5.0
     */
    public static function get_response(string $hook_name, int $id_module, array $hook_args = [])
    {
        $responses = static::get_responses($hook_name, $hook_args, $id_module);
        return $responses ? array_shift($responses) : null;
    }
    /**
     * Returns response from first module that implements this hook, according to hook positions
     *
     * If parameter $raiseWarning is set to true, and more than one module implements hook, warning will be raised.
     *
     * @param string $hookName Hook Name
     * @param array $hookArgs Parameters for the functions
     *
     * @return mixed|null
     * @throws PrestaShopException
     * @since 1.5.0
     */
    public static function get_first_response(string $hook_name, array $hook_args = [], bool $raise_warning = true)
    {
        $responses = static::get_responses($hook_name, $hook_args);
        $count = count($responses);
        switch ($count) {
            case 0:
                return null;
            case 1:
                return array_shift($responses);
            default:
                $modules = implode(', ', array_keys($responses));
                trigger_error(sprintf('Multiple modules [%1$s] are attached to \'%2$s\' hook. Only the first response will be used!', $modules, $hook_name), E_USER_WARNING);
                return array_shift($responses);
        }
    }
    /**
     * Execute modules for specified hook
     *
     * @param string $hookName Hook Name
     * @param array $hookArgs Parameters for the functions
     * @param int $idModule Execute hook for this module only
     * @param bool $arrayReturn If specified, module output will be set by name in an array
     * @param bool $checkExceptions Check permission exceptions
     * @param bool $usePush @deprecated since thirty bees 1.5
     * @param int $idShop If specified, hook will be executed for shop with this ID
     *
     * @return string|array modules output
     *
     * @throws PrestaShopException
     */
    public static function exec($hook_name, $hook_args = [], $id_module = null, $array_return = false, $check_exceptions = true, $use_push = false, $id_shop = null)
    {
        if ($use_push !== false) {
            Tools::display_parameter_as_deprecated('usePush');
        }
        if ($array_return || !Page_Cache::is_enabled() || Page_Cache_Key::get() === false) {
            return static::exec_without_cache($hook_name, $hook_args, $id_module, $array_return, $check_exceptions, false, $id_shop);
        }
        if (!$module_list = static::get_hook_module_exec_list($hook_name)) {
            return '';
        }
        $return = '';
        if (!$id_module) {
            $cache_entry = Page_Cache::get();
            $cached_hooks = Page_Cache::get_cached_hooks();
            foreach ($module_list as $m) {
                $id_module = (int) $m['id_module'];
                $data = static::exec_without_cache($hook_name, $hook_args, $id_module, false, $check_exceptions, false, $id_shop);
                $id_hook = (int) static::get_id_by_name($hook_name);
                if (isset($cached_hooks[$id_module][$id_hook])) {
                    $return .= $data;
                } else {
                    // wrap dynamic hooks
                    $key = $cache_entry->set_hook($id_module, $id_hook, $hook_name, $hook_args);
                    $delimiter = "<!--[{$key}]-->";
                    $return .= $delimiter . $data . $delimiter;
                }
            }
        } else {
            $return = static::exec_without_cache($hook_name, $hook_args, $id_module, false, $check_exceptions, false, $id_shop);
        }
        return $return;
    }
    /**
     * Execute modules for specified hook
     *
     * @param string $hookName Hook Name
     * @param array $hookArgs Parameters for the functions
     * @param int $idModule Execute hook for this module only
     * @param bool $arrayReturn If specified, module output will be set by name in an array
     * @param bool $checkExceptions Check permission exceptions
     * @param bool $usePush @deprecated since thirty bees 1.5
     * @param int $idShop If specified, hook will be executed for shop with this ID
     *
     * @throws PrestaShopException
     *
     * @return string|array modules output
     */
    public static function exec_without_cache($hook_name, $hook_args = [], $id_module = null, $array_return = false, $check_exceptions = true, $use_push = false, $id_shop = null)
    {
        $output = $array_return ? [] : '';
        if (defined('TB_INSTALLATION_IN_PROGRESS')) {
            return $output;
        }
        if ($use_push !== false) {
            Tools::display_parameter_as_deprecated('usePush');
        }
        static $disable_non_native_modules = null;
        if ($disable_non_native_modules === null) {
            $disable_non_native_modules = (bool) Configuration::get('PS_DISABLE_NON_NATIVE_MODULE');
        }
        // Check arguments validity
        if ($id_module && !is_numeric($id_module) || !Validate::is_hook_name($hook_name)) {
            throw new Presta_Shop_Exception('Invalid id_module or hook_name');
        }
        // If no modules associated to hook_name or recompatible hook name, we stop the function
        if (!$module_list = Hook::get_hook_module_exec_list($hook_name)) {
            return $output;
        }
        // Check if hook exists
        if (!$id_hook = Hook::get_id_by_name($hook_name)) {
            return $output;
        }
        // Store list of executed hooks on this page
        Hook::$executed_hooks[$id_hook] = $hook_name;
        $live_edit = false;
        $context = Context::get_context();
        if (!isset($hook_args['cookie']) || !$hook_args['cookie']) {
            $hook_args['cookie'] = $context->cookie;
        }
        if (!isset($hook_args['cart']) || !$hook_args['cart']) {
            $hook_args['cart'] = $context->cart;
        }
        $retro_hook_name = Hook::get_retro_hook_name($hook_name);
        // Look on modules list
        $altern = 0;
        if ($disable_non_native_modules && !isset(Hook::$native_module)) {
            Hook::$native_module = Module::get_native_module_list();
        }
        if ($id_shop !== null && Validate::is_unsigned_id($id_shop) && $id_shop != $context->shop->get_context_shop_id()) {
            $old_context = $context->shop->get_context();
            $old_shop = clone $context->shop;
            $shop = new Shop((int) $id_shop);
            if (Validate::is_loaded_object($shop)) {
                $context->shop = $shop;
                $context->shop->set_context(Shop::CONTEXT_SHOP, $shop->id);
            }
        }
        foreach ($module_list as $array) {
            // Check errors
            if ($id_module && $id_module != $array['id_module']) {
                continue;
            }
            if ($disable_non_native_modules && Hook::$native_module && count(Hook::$native_module) && !in_array($array['module'], Hook::$native_module)) {
                continue;
            }
            // Check permissions
            if ($check_exceptions) {
                $exceptions = Module::get_exceptions_static($array['id_module'], $array['id_hook']);
                $controller = Dispatcher::get_instance()->get_controller();
                $controller_obj = Context::get_context()->controller;
                //check if current controller is a module controller
                if (isset($controller_obj->module) && Validate::is_loaded_object($controller_obj->module)) {
                    $controller = 'module-' . $controller_obj->module->name . '-' . $controller;
                }
                if (in_array($controller, $exceptions)) {
                    continue;
                }
                //Backward compatibility of controller names
                $matching_name = ['authentication' => 'auth', 'productscomparison' => 'compare'];
                if (isset($matching_name[$controller]) && in_array($matching_name[$controller], $exceptions)) {
                    continue;
                }
                if (Validate::is_loaded_object($context->employee) && !Module::get_permission_static($array['id_module'], 'view', $context->employee)) {
                    continue;
                }
            }
            if (!$module_instance = Module::get_instance_by_name($array['module'])) {
                continue;
            }
            // Check which / if method is callable
            $hook_method = 'hook' . ucfirst($hook_name);
            $hook_callable = is_callable([$module_instance, $hook_method]);
            $hook_retro_callable = is_callable([$module_instance, 'hook' . $retro_hook_name]);
            if ($hook_callable || $hook_retro_callable) {
                if (Module::pre_call($module_instance->name)) {
                    $hook_args['altern'] = ++$altern;
                    // Call hook method
                    if ($hook_callable) {
                        $display = Hook::core_call_hook($module_instance, 'hook' . $hook_name, $hook_args);
                    } else {
                        $display = Hook::core_call_hook($module_instance, 'hook' . $retro_hook_name, $hook_args);
                    }
                    // Live edit
                    if (!$array_return && $array['live_edit'] && Tools::is_submit('live_edit') && Tools::get_value('ad') && Tools::get_value('liveToken') == Tools::get_admin_token('AdminModulesPositions' . (int) Tab::get_id_from_class_name('AdminModulesPositions') . Tools::get_int_value('id_employee'))) {
                        $live_edit = true;
                        $output .= static::wrap_live_edit($display, $module_instance, $array['id_hook']);
                    } elseif ($array_return) {
                        $output[$module_instance->name] = $display;
                    } else {
                        $output .= $display;
                    }
                }
            } else {
                Logger::add_log("Module '{$array['module']}' doesn't implement public hook handler method '{$hook_method}()'. This hook will be unregistred.", 2, 0, 'Module', $module_instance->id, true);
                $module_instance->unregister_hook((int) $id_hook);
            }
        }
        if (isset($old_shop) && isset($old_context) && isset($shop)) {
            $context->shop = $old_shop;
            $context->shop->set_context($old_context, $shop->id);
        }
        if ($array_return) {
            return $output;
        }
        if ($live_edit) {
            return '<script type="text/javascript">hooks_list.push(\'' . $hook_name . '\');</script>' . '<div id="' . $hook_name . '" class="dndHook" style="min-height:50px">' . $output . '</div>';
        }
        return $output;
    }
    /**
     * Get list of modules we can execute per hook
     *
     * @param string $hookName Get list of modules for this hook if given
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_hook_module_exec_list($hook_name = null)
    {
        $context = Context::get_context();
        $cache_id = 'hook_module_exec_list_' . (isset($context->shop->id) ? '_' . $context->shop->id : '') . (isset($context->customer) ? '_' . $context->customer->id : '');
        if (!Cache::is_stored($cache_id) || $hook_name == 'displayPayment' || $hook_name == 'displayPaymentEU') {
            $frontend = true;
            $groups = [];
            $use_groups = Group::is_feature_active();
            if (isset($context->employee)) {
                $frontend = false;
            } else if ($use_groups) {
                if (isset($context->customer) && $context->customer->is_logged()) {
                    $groups = $context->customer->get_groups();
                } elseif (isset($context->customer) && $context->customer->is_logged(true)) {
                    $groups = [(int) Configuration::get('PS_GUEST_GROUP')];
                } else {
                    $groups = [(int) Configuration::get('PS_UNIDENTIFIED_GROUP')];
                }
            }
            // SQL Request
            $sql = new Db_Query();
            $sql->select('h.`name` as hook, m.`id_module`, h.`id_hook`, m.`name` as module, h.`live_edit`');
            $sql->from('module', 'm');
            $sql->join(Shop::add_sql_association('module', 'm', true, 'module_shop.enable_device & ' . (int) Context::get_context()->get_device()));
            $sql->inner_join('module_shop', 'ms', 'ms.`id_module` = m.`id_module`');
            $sql->inner_join('hook_module', 'hm', 'hm.`id_module` = m.`id_module`');
            $sql->inner_join('hook', 'h', 'hm.`id_hook` = h.`id_hook`');
            if ($hook_name != 'displayPayment' && $hook_name != 'displayPaymentEU') {
                $sql->where('h.`name` != "displayPayment" AND h.`name` != "displayPaymentEU"');
            } elseif ($frontend) {
                if (Validate::is_loaded_object($context->country)) {
                    $sql->where('((h.`name` = "displayPayment" OR h.`name` = "displayPaymentEU") AND (SELECT `id_country` FROM `' . _DB_PREFIX_ . 'module_country` mc WHERE mc.`id_module` = m.`id_module` AND `id_country` = ' . (int) $context->country->id . ' AND `id_shop` = ' . (int) $context->shop->id . ' LIMIT 1) = ' . (int) $context->country->id . ')');
                }
                if (Validate::is_loaded_object($context->currency)) {
                    $sql->where('((h.`name` = "displayPayment" OR h.`name` = "displayPaymentEU") AND (SELECT `id_currency` FROM `' . _DB_PREFIX_ . 'module_currency` mcr WHERE mcr.`id_module` = m.`id_module` AND `id_currency` IN (' . (int) $context->currency->id . ', -1, -2) LIMIT 1) IN (' . (int) $context->currency->id . ', -1, -2))');
                }
                if (Validate::is_loaded_object($context->cart)) {
                    $carrier = new Carrier($context->cart->id_carrier);
                    if (Validate::is_loaded_object($carrier)) {
                        $sql->where('((h.`name` = "displayPayment" OR h.`name` = "displayPaymentEU") AND (SELECT `id_reference` FROM `' . _DB_PREFIX_ . 'module_carrier` mcar WHERE mcar.`id_module` = m.`id_module` AND `id_reference` = ' . (int) $carrier->id_reference . ' AND `id_shop` = ' . (int) $context->shop->id . ' LIMIT 1) = ' . (int) $carrier->id_reference . ')');
                    }
                }
            }
            if (Validate::is_loaded_object($context->shop)) {
                $sql->where('hm.`id_shop` = ' . (int) $context->shop->id);
            }
            if ($frontend) {
                if ($use_groups) {
                    $sql->left_join('module_group', 'mg', 'mg.`id_module` = m.`id_module`');
                    if (Validate::is_loaded_object($context->shop)) {
                        $sql->where('mg.`id_shop` = ' . (int) $context->shop->id . (count($groups) ? ' AND  mg.`id_group` IN (' . implode(', ', $groups) . ')' : ''));
                    } elseif (count($groups)) {
                        $sql->where('mg.`id_group` IN (' . implode(', ', $groups) . ')');
                    }
                }
            }
            $sql->group_by('hm.id_hook, hm.id_module');
            $sql->order_by('hm.`position`');
            $list = [];
            if ($result = Db::read_only()->get_array($sql)) {
                foreach ($result as $row) {
                    $row['hook'] = strtolower((string) $row['hook']);
                    if (!isset($list[$row['hook']])) {
                        $list[$row['hook']] = [];
                    }
                    $list[$row['hook']][] = ['id_hook' => $row['id_hook'], 'module' => $row['module'], 'id_module' => $row['id_module'], 'live_edit' => $row['live_edit']];
                }
            }
            if ($hook_name != 'displayPayment' && $hook_name != 'displayPaymentEU') {
                Cache::store($cache_id, $list);
            }
        } else {
            $list = Cache::retrieve($cache_id);
        }
        // If hook_name is given, just get list of modules for this hook
        if ($hook_name) {
            $retro_hook_name = strtolower(Hook::get_retro_hook_name($hook_name));
            $hook_name = strtolower($hook_name);
            $return = [];
            $inserted_modules = [];
            if (isset($list[$hook_name])) {
                $return = $list[$hook_name];
            }
            foreach ($return as $module) {
                $inserted_modules[] = $module['id_module'];
            }
            if (isset($list[$retro_hook_name])) {
                foreach ($list[$retro_hook_name] as $retro_module_call) {
                    if (!in_array($retro_module_call['id_module'], $inserted_modules)) {
                        $return[] = $retro_module_call;
                    }
                }
            }
            return count($return) > 0 ? $return : false;
        }
        return $list;
    }
    /**
     * Return backward compatibility hook name
     *
     * @param string $hookName Hook name
     *
     * @return string alternate hook name
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_retro_hook_name($hook_name)
    {
        $hook_name = strtolower($hook_name);
        $alias_list = Hook::get_hook_alias_list();
        if (isset($alias_list[$hook_name])) {
            return strtolower($alias_list[$hook_name]);
        }
        foreach ($alias_list as $alias => $original) {
            if (strtolower($original) === $hook_name) {
                return strtolower($alias);
            }
        }
        return '';
    }
    /**
     * Get list of hook alias
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_hook_alias_list()
    {
        $cache_id = 'hook_alias';
        if (!Cache::is_stored($cache_id)) {
            $hook_alias_list = Db::read_only()->get_array('SELECT * FROM `' . _DB_PREFIX_ . 'hook_alias`');
            $hook_alias = [];
            if ($hook_alias_list) {
                foreach ($hook_alias_list as $ha) {
                    $hook_alias[strtolower((string) $ha['alias'])] = $ha['name'];
                }
            }
            Cache::store($cache_id, $hook_alias);
            return $hook_alias;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Return hook ID from name
     *
     * @param string $hookName Hook name
     *
     * @return int Hook ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_id_by_name($hook_name)
    {
        $hook_name = strtolower($hook_name);
        if (!Validate::is_hook_name($hook_name)) {
            return false;
        }
        $cache_id = 'hook_idsbyname';
        if (!Cache::is_stored($cache_id)) {
            // Get all hook ID by name and alias
            $hook_ids = [];
            $db = Db::read_only();
            $result = $db->get_array('
			SELECT `id_hook`, `name`
			FROM `' . _DB_PREFIX_ . 'hook`
			UNION
			SELECT `id_hook`, ha.`alias` AS name
			FROM `' . _DB_PREFIX_ . 'hook_alias` ha
			INNER JOIN `' . _DB_PREFIX_ . 'hook` h ON ha.name = h.name');
            foreach ($result as $row) {
                $hook_ids[strtolower((string) $row['name'])] = $row['id_hook'];
            }
            Cache::store($cache_id, $hook_ids);
        } else {
            $hook_ids = Cache::retrieve($cache_id);
        }
        return $hook_ids[$hook_name] ?? false;
    }
    /**
     * @param Module $module
     * @param string $method
     * @param array $params
     *
     * @return string|array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function core_call_hook($module, $method, $params)
    {
        // Define if we will log modules performances for this session
        if (Module::$_log_modules_perfs === null) {
            $modulo = _PS_DEBUG_PROFILING_ ? 1 : Configuration::get('PS_log_modules_perfs_MODULO');
            Module::$_log_modules_perfs = $modulo && mt_rand(0, $modulo - 1) == 0;
            if (Module::$_log_modules_perfs) {
                Module::$_log_modules_perfs_session = mt_rand();
            }
        }
        // Immediately return the result if we do not log performances
        if (!Module::$_log_modules_perfs) {
            return $module->{$method}($params);
        }
        // Store time and memory before and after hook call and save the result in the database
        $time_start = microtime(true);
        $memory_start = memory_get_usage(true);
        // Call hook
        $r = $module->{$method}($params);
        $time_end = microtime(true);
        $memory_end = memory_get_usage(true);
        Db::get_instance()->insert('modules_perfs', ['session' => (int) Module::$_log_modules_perfs_session, 'module' => p_sql($module->name), 'method' => p_sql($method), 'time_start' => p_sql($time_start), 'time_end' => p_sql($time_end), 'memory_start' => p_sql($memory_start), 'memory_end' => p_sql($memory_end)]);
        return $r;
    }
    /**
     * @param string|null $display
     * @param Module $moduleInstance
     * @param int $idHook
     *
     * @return string
     */
    public static function wrap_live_edit($display, $module_instance, $id_hook)
    {
        $display = (string) $display;
        return '<script type="text/javascript"> modules_list.push(\'' . Tools::safe_output($module_instance->name) . '\');</script>
				<div id="hook_' . (int) $id_hook . '_module_' . (int) $module_instance->id . '_moduleName_' . str_replace('_', '-', Tools::safe_output($module_instance->name)) . '"
				class="dndModule" style="border: 1px dotted red;' . (!strlen($display) ? 'height:50px;' : '') . '">
					<span style="font-family: Georgia;font-size:13px;font-style:italic;">
						<img style="padding-right:5px;" src="' . _MODULE_DIR_ . Tools::safe_output($module_instance->name) . '/logo.gif">' . Tools::safe_output($module_instance->display_name) . '<span style="float:right">
				<a href="#" id="' . (int) $id_hook . '_' . (int) $module_instance->id . '" class="moveModule">
					<img src="' . _PS_ADMIN_IMG_ . 'arrow_out.png"></a>
				<a href="#" id="' . (int) $id_hook . '_' . (int) $module_instance->id . '" class="unregisterHook">
					<img src="' . _PS_ADMIN_IMG_ . 'delete.gif"></a></span>
				</span>' . $display . '</div>';
    }
    /**
     * Return hook ID from name
     *
     * @param string $hookName Hook name
     *
     * @return int Hook ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @deprecated 1.0.0 use Hook::getIdByName() instead
     */
    public static function get($hook_name)
    {
        Tools::display_as_deprecated('Use Hook::getIdByName() instead');
        if (!Validate::is_hook_name($hook_name)) {
            throw new Presta_Shop_Exception('Invalid hook name: ' . $hook_name);
        }
        $result = Db::read_only()->get_row((new Db_Query())->select('`id_hook`, `name`')->from('hook')->where('`name` = \'' . p_sql($hook_name) . '\''));
        return $result ? $result['id_hook'] : false;
    }
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
        Cache::clean('hook_idsbyname');
        return parent::add($auto_date, $null_values);
    }
    /**
     * Returns true if $hookName is a displayable hook
     *
     * At the moment, crude check for hook name is used -- every hook starting with display
     * is considered displayable hook, with exception of displayAdmin*
     *
     * @param string $hookName
     * @param bool $includeBackOfficeHooks if true, then check for back office displayable hooks as well
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function is_displayable_hook($hook_name, $include_back_office_hooks = false)
    {
        $variants = [$hook_name, static::get_retro_hook_name($hook_name)];
        foreach ($variants as $hook) {
            $hook = strtolower($hook);
            if (str_starts_with($hook, 'display')) {
                if ($include_back_office_hooks) {
                    return true;
                }
                return !str_starts_with($hook, 'displayadmin') && !str_starts_with($hook, 'displaybackoffice');
            }
        }
        return false;
    }
}