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
use Thirtybees\Core\Dependency_Injection\Service_Locator;
/**
 * Class DispatcherCore
 */
class Dispatcher_Core
{
    /**
     * List of available front controllers types
     */
    public const FC_FRONT = 1;
    public const FC_ADMIN = 2;
    public const FC_MODULE = 3;
    /**
     * @var Dispatcher
     */
    public static $instance;
    /**
     * @var array List of default routes
     */
    public $default_routes = ['category_rule' => ['controller' => 'category', 'rule' => '{categories:/}{rewrite}', 'keywords' => ['id' => ['regexp' => '[0-9]+', 'alias' => 'id_category'], 'rewrite' => ['regexp' => '[_a-zA-Z0-9\pL\pS-]*', 'param' => 'rewrite'], 'categories' => ['regexp' => '[/_a-zA-Z0-9-\pL]*'], 'meta_keywords' => ['regexp' => '[_a-zA-Z0-9-\pL]*'], 'meta_title' => ['regexp' => '[_a-zA-Z0-9-\pL]*']]], 'supplier_rule' => ['controller' => 'supplier', 'rule' => '{rewrite}', 'keywords' => ['id' => ['regexp' => '[0-9]+', 'alias' => 'id_supplier'], 'rewrite' => ['regexp' => '[_a-zA-Z0-9\pL\pS-]*', 'param' => 'rewrite'], 'meta_keywords' => ['regexp' => '[_a-zA-Z0-9-\pL]*'], 'meta_title' => ['regexp' => '[_a-zA-Z0-9-\pL]*']]], 'manufacturer_rule' => ['controller' => 'manufacturer', 'rule' => 'manufacturer/{rewrite}', 'keywords' => ['id' => ['regexp' => '[0-9]+', 'alias' => 'id_manufacturer'], 'rewrite' => ['regexp' => '[_a-zA-Z0-9\pL\pS-]*', 'param' => 'rewrite'], 'meta_keywords' => ['regexp' => '[_a-zA-Z0-9-\pL]*'], 'meta_title' => ['regexp' => '[_a-zA-Z0-9-\pL]*']]], 'cms_rule' => ['controller' => 'cms', 'rule' => 'info/{categories:/}{rewrite}', 'keywords' => ['id' => ['regexp' => '[0-9]+', 'alias' => 'id_cms'], 'rewrite' => ['regexp' => '[_a-zA-Z0-9\pL\pS-]*', 'param' => 'cms_rewrite'], 'categories' => ['regexp' => '[/_a-zA-Z0-9-\pL]*'], 'meta_keywords' => ['regexp' => '[_a-zA-Z0-9-\pL]*'], 'meta_title' => ['regexp' => '[_a-zA-Z0-9-\pL]*']]], 'cms_category_rule' => ['controller' => 'cms', 'rule' => 'info/{categories:/}{rewrite}', 'keywords' => ['id' => ['regexp' => '[0-9]+', 'alias' => 'id_cms_category'], 'rewrite' => ['regexp' => '[_a-zA-Z0-9\pL\pS-]*', 'param' => 'cms_cat_rewrite'], 'categories' => ['regexp' => '[/_a-zA-Z0-9-\pL]*'], 'meta_keywords' => ['regexp' => '[_a-zA-Z0-9-\pL]*'], 'meta_title' => ['regexp' => '[_a-zA-Z0-9-\pL]*']]], 'module' => ['controller' => null, 'rule' => 'module/{module}{/:controller}', 'keywords' => ['module' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'module'], 'controller' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'controller']], 'params' => ['fc' => 'module']], 'product_rule' => ['controller' => 'product', 'rule' => '{categories:/}{rewrite}', 'keywords' => ['id' => ['regexp' => '[0-9]+', 'alias' => 'id_product'], 'rewrite' => ['regexp' => '[_a-zA-Z0-9\pL\pS-]*', 'param' => 'rewrite'], 'ean13' => ['regexp' => '[0-9\pL]*'], 'category' => ['regexp' => '[_a-zA-Z0-9-\pL]*'], 'categories' => ['regexp' => '[/_a-zA-Z0-9-\pL]*'], 'reference' => ['regexp' => '[_a-zA-Z0-9-\pL]*'], 'meta_keywords' => ['regexp' => '[_a-zA-Z0-9-\pL]*'], 'meta_title' => ['regexp' => '[_a-zA-Z0-9-\pL]*'], 'manufacturer' => ['regexp' => '[_a-zA-Z0-9-\pL]*'], 'supplier' => ['regexp' => '[_a-zA-Z0-9-\pL]*'], 'price' => ['regexp' => '[0-9\.,]*'], 'tags' => ['regexp' => '[a-zA-Z0-9-\pL]*'], 'any' => ['regexp' => '.*']]], 'layered_rule' => ['controller' => 'category', 'rule' => '{rewrite}{/:selected_filters}', 'keywords' => ['id' => ['regexp' => '[0-9]+', 'alias' => 'id_category'], 'selected_filters' => ['regexp' => '.*', 'param' => 'selected_filters'], 'rewrite' => ['regexp' => '[_a-zA-Z0-9\pL\pS-]*', 'param' => 'rewrite'], 'categories' => ['regexp' => '[/_a-zA-Z0-9-\pL]*'], 'meta_keywords' => ['regexp' => '[_a-zA-0-9-\pL]*'], 'meta_title' => ['regexp' => '[_a-zA-Z0-9-\pL]*']]]];
    /**
     * @var bool If true, use routes to build URL (mod rewrite must be activated)
     */
    protected bool $use_routes;
    /**
     * @var bool
     */
    protected $multilang_activated = false;
    /**
     * @var array List of loaded routes
     */
    public $routes = [];
    /**
     * @var array Map of additional route matchers
     */
    public $matchers = [];
    /**
     * @var string Current controller name
     */
    protected $controller;
    /**
     * @var string Current request uri
     */
    protected $request_uri;
    /**
     * @var array Store empty route (a route with an empty rule)
     */
    protected $empty_route;
    /**
     * @var string Set default controller, which will be used if http parameter 'controller' is empty
     */
    protected $default_controller;
    /**
     * @var bool
     */
    protected $use_default_controller = false;
    /**
     * @var string Controller to use if found controller doesn't exist
     */
    protected string $controller_not_found = 'pagenotfound';
    /**
     * @var string Front controller to use
     */
    protected $front_controller = self::FC_FRONT;
    /**
     * Get current instance of dispatcher (singleton)
     *
     * @return Dispatcher
     */
    public static function get_instance()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    /**
     * @param string $routeId Name of the route (need to be unique, a second route with same name will override the first)
     * @param string $rule Url rule
     * @param string $controller Controller to call if request uri match the rule
     * @param int $idLang
     * @param int $idShop
     */
    public function add_route($route_id, $rule, $controller, $id_lang = null, array $keywords = [], array $params = [], $id_shop = null): void
    {
        if (isset(Context::get_context()->language) && $id_lang === null) {
            $id_lang = (int) Context::get_context()->language->id;
        }
        if (isset(Context::get_context()->shop) && $id_shop === null) {
            $id_shop = (int) Context::get_context()->shop->id;
        }
        if (!$rule && in_array($route_id, array_keys($this->default_routes))) {
            $rule = $this->default_routes[$route_id]['rule'];
        }
        $regexp = preg_quote((string) $rule, '#');
        $aliases = [];
        if ($keywords) {
            $transform_keywords = [];
            preg_match_all('#\\\\{(([^{}]*)\\\\:)?(' . implode('|', array_keys($keywords)) . ')(\\\\:([^{}]*))?\\\\}#', $regexp, $m);
            for ($i = 0, $total = count($m[0]); $i < $total; $i++) {
                $prepend = $m[2][$i];
                $keyword = $m[3][$i];
                $append = $m[5][$i];
                $has_param = isset($keywords[$keyword]['param']);
                $keyword_param = $has_param ? $keywords[$keyword]['param'] : null;
                $keyword_regexp = $keywords[$keyword]['regexp'];
                $transform_keywords[$keyword] = ['required' => isset($keywords[$keyword]['param']), 'prepend' => stripslashes($prepend), 'append' => stripslashes($append)];
                // if keyword 'id' and 'param' are different, let's register param as keyword id alias
                if ($keyword_param && $keyword_param !== $keyword) {
                    $aliases[$keyword_param] = $keyword;
                }
                $prepend_regexp = $append_regexp = '';
                if ($prepend || $append) {
                    $prepend_regexp = '(' . $prepend;
                    $append_regexp = $append . ')?';
                }
                if ($keyword_param) {
                    $regexp = str_replace($m[0][$i], $prepend_regexp . '(?P<' . $keyword_param . '>' . $keyword_regexp . ')' . $append_regexp, $regexp);
                } elseif ($keyword === 'id') {
                    $regexp = str_replace($m[0][$i], $prepend_regexp . '(?P<id>' . $keyword_regexp . ')' . $append_regexp, $regexp);
                } else {
                    $regexp = str_replace($m[0][$i], $prepend_regexp . '(' . $keyword_regexp . ')' . $append_regexp, $regexp);
                }
            }
            $keywords = $transform_keywords;
        }
        $regexp = '#^/' . $regexp . '$#u';
        if (!isset($this->routes[$id_shop])) {
            $this->routes[$id_shop] = [];
        }
        if (!isset($this->routes[$id_shop][$id_lang])) {
            $this->routes[$id_shop][$id_lang] = [];
        }
        $this->routes[$id_shop][$id_lang][$route_id] = ['rule' => $rule, 'regexp' => $regexp, 'controller' => $controller, 'keywords' => $keywords, 'params' => $params, 'aliases' => $aliases];
    }
    /**
     * Get list of all available Module Front controllers
     *
     * @param string $type
     * @param string|string[]|null $module
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_module_controllers($type = 'all', $module = null): array
    {
        $modules_controllers = [];
        if (is_null($module)) {
            $modules = Module::get_modules_on_disk(true);
        } elseif (!is_array($module)) {
            $modules = [Module::get_instance_by_name($module)];
        } else {
            $modules = [];
            foreach ($module as $_mod) {
                $modules[] = Module::get_instance_by_name($_mod);
            }
        }
        foreach ($modules as $mod) {
            foreach (Dispatcher::get_controllers_in_directory(_PS_MODULE_DIR_ . $mod->name . '/controllers/') as $controller) {
                if ($type == 'admin') {
                    if (str_contains($controller, 'Admin')) {
                        $modules_controllers[$mod->name][] = $controller;
                    }
                } elseif ($type == 'front') {
                    if (!str_contains($controller, 'Admin')) {
                        $modules_controllers[$mod->name][] = $controller;
                    }
                } else {
                    $modules_controllers[$mod->name][] = $controller;
                }
            }
        }
        return $modules_controllers;
    }
    /**
     * Needs to be instantiated from getInstance() method
     *
     * @throws PrestaShopException
     */
    protected function __construct()
    {
        $this->use_routes = (bool) Configuration::get('PS_REWRITING_SETTINGS');
        // Select right front controller
        if (defined('_PS_ADMIN_DIR_')) {
            $this->front_controller = static::FC_ADMIN;
            $this->controller_not_found = 'adminnotfound';
        } elseif (Tools::get_value('fc') == 'module') {
            $this->front_controller = static::FC_MODULE;
            $this->controller_not_found = 'pagenotfound';
        } else {
            $this->front_controller = static::FC_FRONT;
            $this->controller_not_found = 'pagenotfound';
        }
        $this->set_request_uri();
        // Switch language if needed (only on front)
        if (in_array($this->front_controller, [static::FC_FRONT, static::FC_MODULE])) {
            Tools::switch_language();
        }
        if (Language::is_multi_language_activated()) {
            $this->multilang_activated = true;
        }
        $this->load_routes();
    }
    /**
     * Set request uri and iso lang
     *
     * @throws PrestaShopException
     */
    protected function set_request_uri()
    {
        $request_uri = static::extract_request_uri();
        // remove shop base uri from requestUri
        if (isset(Context::get_context()->shop) && is_object(Context::get_context()->shop)) {
            $request_uri = preg_replace('#^' . preg_quote(Context::get_context()->shop->get_base_uri(), '#') . '#i', '/', $request_uri);
        }
        // remove language from uri
        if ($this->use_routes) {
            $url_language = $this->get_language_from_uri($request_uri);
            if ($url_language) {
                $request_uri = substr((string) $request_uri, strlen($url_language->get_url_code()) + 1);
                $_GET['isolang'] = $url_language->iso_code;
            } elseif (!Tools::get_value('isolang')) {
                if (!$this->is_php_script_url($request_uri)) {
                    // no iso code in url, we will fallback to default language
                    $_GET['isolang'] = $this->get_default_language_iso_code();
                }
            }
        }
        // fix uri, if it starts with two or more / characters
        if (str_starts_with((string) $request_uri, '//')) {
            $request_uri = '/' . ltrim((string) $request_uri, '/');
        }
        $this->request_uri = $request_uri;
    }
    /**
     * Load default routes group by languages
     *
     * @param int|null $idShop
     *
     * @throws PrestaShopException
     */
    protected function load_routes($id_shop = null)
    {
        // Load custom routes from modules
        $modules_routes = Hook::get_responses('moduleRoutes', ['id_shop' => $id_shop]);
        foreach ($modules_routes as $module_route) {
            if (is_array($module_route)) {
                foreach ($module_route as $route => $route_details) {
                    if (array_key_exists('controller', $route_details) && array_key_exists('rule', $route_details) && array_key_exists('keywords', $route_details) && array_key_exists('params', $route_details)) {
                        if (!isset($this->default_routes[$route])) {
                            $this->default_routes[$route] = [];
                        }
                        $this->default_routes[$route] = array_merge($this->default_routes[$route], $route_details);
                    }
                }
            }
        }
        // Set rules and old keywords
        $prodroutes = 'PS_ROUTE_product_rule';
        $catroutes = 'PS_ROUTE_category_rule';
        $supproutes = 'PS_ROUTE_supplier_rule';
        $manuroutes = 'PS_ROUTE_manufacturer_rule';
        $layeredroutes = 'PS_ROUTE_layered_rule';
        $cmsroutes = 'PS_ROUTE_cms_rule';
        $cmscatroutes = 'PS_ROUTE_cms_category_rule';
        $moduleroutes = 'PS_ROUTE_module';
        $this->set_route_matcher('product_rule', $this->match_rewritable_route(...));
        $this->set_route_matcher('category_rule', $this->match_rewritable_route(...));
        $this->set_route_matcher('supplier_rule', $this->match_rewritable_route(...));
        $this->set_route_matcher('manufacturer_rule', $this->match_rewritable_route(...));
        $this->set_route_matcher('layered_rule', $this->match_rewritable_route(...));
        $this->set_route_matcher('cms_rule', $this->match_rewritable_route(...));
        $this->set_route_matcher('cms_category_rule', $this->match_rewritable_route(...));
        // Set new routes
        foreach (Language::get_languages() as $lang) {
            foreach ($this->default_routes as $id => $route) {
                $rule = match ($id) {
                    'product_rule' => Configuration::get($prodroutes, (int) $lang['id_lang']),
                    'category_rule' => Configuration::get($catroutes, (int) $lang['id_lang']),
                    'supplier_rule' => Configuration::get($supproutes, (int) $lang['id_lang']),
                    'manufacturer_rule' => Configuration::get($manuroutes, (int) $lang['id_lang']),
                    'layered_rule' => Configuration::get($layeredroutes, (int) $lang['id_lang']),
                    'cms_rule' => Configuration::get($cmsroutes, (int) $lang['id_lang']),
                    'cms_category_rule' => Configuration::get($cmscatroutes, (int) $lang['id_lang']),
                    'module' => Configuration::get($moduleroutes, (int) $lang['id_lang']),
                    default => $route['rule'],
                };
                $this->add_route($id, $rule, $route['controller'], $lang['id_lang'], $route['keywords'], $route['params'] ?? [], $id_shop);
            }
        }
        // Load the custom routes prior the defaults to avoid infinite loops
        if ($this->use_routes) {
            /* Load routes from meta table */
            if ($results = Db::read_only()->get_array((new Db_Query())->select('m.`page`, ml.`url_rewrite`, ml.`id_lang`')->from('meta', 'm')->left_join('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta` ' . Shop::add_sql_restriction_on_lang('ml', $id_shop))->order_by('LENGTH(ml.`url_rewrite`) DESC'))) {
                foreach ($results as $row) {
                    if ($row['url_rewrite']) {
                        $this->add_route($row['page'], $row['url_rewrite'], $row['page'], $row['id_lang'], [], [], $id_shop);
                    }
                }
            }
            foreach (Language::get_languages(false, false, true) as $id_lang) {
                // Set favicon.ico route
                $this->add_route('favicon', 'favicon.ico', 'favicon', $id_lang, [], [], $id_shop);
                // Set apple-touch-icon.png route
                $this->add_route('apple-touch-icon', 'apple-touch-icon.png', 'favicon', $id_lang, [], ['icon' => 'apple-touch-icon', 'precomposed' => false], $id_shop);
                // Set apple-touch-icon.png route
                $this->add_route('apple-touch-icon-precomposed', 'apple-touch-icon-precomposed.png', 'favicon', $id_lang, [], ['icon' => 'apple-touch-icon', 'precomposed' => true], $id_shop);
                // Set apple-touch-icon-width-height.png route
                $this->add_route('apple-touch-icon-size', 'apple-touch-icon-{width}x{height}.png', 'favicon', $id_lang, ['width' => ['regexp' => '[0-9]+', 'param' => 'width'], 'height' => ['regexp' => '[0-9]+', 'param' => 'height']], ['icon' => 'apple-touch-icon', 'precomposed' => false], $id_shop);
                // Set apple-touch-icon-width-height-precomposed.png route
                $this->add_route('apple-touch-icon-size-precomposed', 'apple-touch-icon-{width}x{height}-precomposed.png', 'favicon', $id_lang, ['width' => ['regexp' => '[0-9]+', 'param' => 'width'], 'height' => ['regexp' => '[0-9]+', 'param' => 'height']], ['icon' => 'apple-touch-icon', 'precomposed' => true], $id_shop);
            }
            // Set default empty route if no empty route (that's weird I know)
            if (!$this->empty_route) {
                $this->empty_route = ['routeID' => 'index', 'rule' => '', 'controller' => 'index'];
            }
        }
    }
    /**
     * Find the controller and instantiate it
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function dispatch(): void
    {
        $controller_class = '';
        // Get current controller
        $this->get_controller();
        if (!$this->controller) {
            $this->controller = $this->use_default_controller();
        }
        // Dispatch with right front controller
        switch ($this->front_controller) {
            // Dispatch front office controller
            case static::FC_FRONT:
                $controllers = Dispatcher::get_controllers([_PS_FRONT_CONTROLLER_DIR_, _PS_OVERRIDE_DIR_ . 'controllers/front/']);
                $controllers['index'] = 'IndexController';
                if (isset($controllers['auth'])) {
                    $controllers['authentication'] = $controllers['auth'];
                }
                if (isset($controllers['compare'])) {
                    $controllers['productscomparison'] = $controllers['compare'];
                }
                if (isset($controllers['contact'])) {
                    $controllers['contactform'] = $controllers['contact'];
                }
                if (!isset($controllers[strtolower($this->controller)])) {
                    $this->controller = $this->controller_not_found;
                }
                $controller_class = $controllers[strtolower($this->controller)];
                $params_hook_action_dispatcher = ['controller_type' => static::FC_FRONT, 'controller_class' => $controller_class, 'is_module' => 0];
                break;
            // Dispatch module controller for front office and ajax
            case static::FC_MODULE:
                $controller_class = 'PageNotFoundController';
                $module_name = Tools::get_value('module');
                if (Validate::is_module_name($module_name)) {
                    $module = Module::get_instance_by_name($module_name);
                    if (Validate::is_loaded_object($module) && $module->active) {
                        $controllers = Dispatcher::get_controllers(_PS_MODULE_DIR_ . $module_name . '/controllers/front/');
                        if ($controller_file_name = $controllers[strtolower($this->controller)] ?? null) {
                            if (file_exists(_PS_MODULE_DIR_ . $module_name . '/controllers/front/' . $controller_file_name . '.php')) {
                                include_once _PS_MODULE_DIR_ . $module_name . '/controllers/front/' . $controller_file_name . '.php';
                                $controller_class = $module_name . $this->controller . 'ModuleFrontController';
                            }
                        }
                        $ajax_controllers = Dispatcher::get_controllers(_PS_MODULE_DIR_ . $module_name . '/controllers/ajax/');
                        if ($ajax_controller_file_name = $ajax_controllers[strtolower($this->controller)] ?? null) {
                            if (file_exists(_PS_MODULE_DIR_ . $module_name . '/controllers/ajax/' . $ajax_controller_file_name . '.php')) {
                                include_once _PS_MODULE_DIR_ . $module_name . '/controllers/ajax/' . $ajax_controller_file_name . '.php';
                                $controller_class = $module_name . $this->controller . 'ModuleAjaxController';
                            }
                        }
                    }
                }
                $params_hook_action_dispatcher = ['controller_type' => static::FC_FRONT, 'controller_class' => $controller_class, 'is_module' => 1];
                break;
            // Dispatch back office controller + module back office controller
            case static::FC_ADMIN:
                if ($this->use_default_controller && !Tools::get_value('token') && Validate::is_loaded_object(Context::get_context()->employee) && Context::get_context()->employee->is_logged_back()) {
                    Tools::redirect_admin('index.php?controller=' . $this->controller . '&token=' . Tools::get_admin_token_lite($this->controller));
                }
                $tab = Tab::get_instance_from_class_name($this->controller, Configuration::get('PS_LANG_DEFAULT'));
                $retrocompatibility_admin_tab = null;
                if ($tab->module) {
                    if (file_exists(_PS_MODULE_DIR_ . $tab->module . '/' . $tab->class_name . '.php')) {
                        $retrocompatibility_admin_tab = _PS_MODULE_DIR_ . $tab->module . '/' . $tab->class_name . '.php';
                    } else {
                        $controllers = Dispatcher::get_controllers(_PS_MODULE_DIR_ . $tab->module . '/controllers/admin/');
                        if (!isset($controllers[strtolower($this->controller)])) {
                            $this->controller = $this->controller_not_found;
                            $controller_class = 'AdminNotFoundController';
                        } else {
                            // Controllers in modules can be named AdminXXX.php or AdminXXXController.php
                            include_once _PS_MODULE_DIR_ . $tab->module . '/controllers/admin/' . $controllers[strtolower($this->controller)] . '.php';
                            $controller_class = $controllers[strtolower($this->controller)] . (strpos($controllers[strtolower($this->controller)], 'Controller') ? '' : 'Controller');
                        }
                    }
                    $params_hook_action_dispatcher = ['controller_type' => static::FC_ADMIN, 'controller_class' => $controller_class, 'is_module' => 1];
                } else {
                    $controllers = Dispatcher::get_controllers([_PS_ADMIN_DIR_ . '/tabs/', _PS_ADMIN_CONTROLLER_DIR_, _PS_OVERRIDE_DIR_ . 'controllers/admin/']);
                    if (!isset($controllers[strtolower($this->controller)])) {
                        // If this is a parent tab, load the first child
                        if (Validate::is_loaded_object($tab) && $tab->id_parent == 0 && ($tabs = Tab::get_tabs(Context::get_context()->language->id, $tab->id)) && isset($tabs[0])) {
                            Tools::redirect_admin(Context::get_context()->link->get_admin_link($tabs[0]['class_name']));
                        }
                        $this->controller = $this->controller_not_found;
                    }
                    $controller_class = $controllers[strtolower($this->controller)];
                    $params_hook_action_dispatcher = ['controller_type' => static::FC_ADMIN, 'controller_class' => $controller_class, 'is_module' => 0];
                    if (file_exists(_PS_ADMIN_DIR_ . '/tabs/' . $controller_class . '.php')) {
                        $retrocompatibility_admin_tab = _PS_ADMIN_DIR_ . '/tabs/' . $controller_class . '.php';
                    }
                }
                // @retrocompatibility with admin/tabs/ old system
                if ($retrocompatibility_admin_tab) {
                    include_once $retrocompatibility_admin_tab;
                    include_once _PS_ADMIN_DIR_ . '/functions.php';
                    run_admin_tab($this->controller, !empty($_REQUEST['ajaxMode']));
                    return;
                }
                break;
            default:
                throw new Presta_Shop_Exception('Bad front controller chosen');
        }
        // Instantiate controller
        $controller = Service_Locator::get_instance()->get_controller($controller_class);
        // Execute hook dispatcher
        Hook::trigger_event('actionDispatcher', $params_hook_action_dispatcher);
        // Running controller
        $controller->run();
    }
    /**
     * Retrieve the controller from url or request uri if routes are activated
     *
     * @param int|null $idShop
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function get_controller($id_shop = null)
    {
        $context = Context::get_context();
        // Get the controller directly on admin pages
        if (isset($context->employee->id) && $context->employee->id) {
            $_GET['controllerUri'] = Tools::get_value('controller');
        }
        if ($this->controller) {
            $_GET['controller'] = $this->controller;
            return $this->controller;
        }
        if (isset(Context::get_context()->shop) && $id_shop === null) {
            $id_shop = (int) Context::get_context()->shop->id;
        }
        return $this->resolve_controller($id_shop, $this->request_uri);
    }
    /**
     * @return string
     * @throws PrestaShopException
     */
    public function resolve_controller(int $id_shop, string $request_uri): string|array
    {
        [$uri] = explode('?', $request_uri);
        $controller = Tools::get_value('controller');
        if (isset($controller) && is_string($controller)) {
            if (preg_match('/^([0-9a-z_-]+)\?(.*)=(.*)$/Ui', $controller, $m)) {
                $controller = $m[1];
                if (isset($_GET['controller'])) {
                    $_GET[$m[2]] = $m[3];
                } else if (isset($_POST['controller'])) {
                    $_POST[$m[2]] = $m[3];
                }
            } elseif (!$this->use_routes && Validate::is_controller_name($controller) && Tools::is_submit('id_' . $controller)) {
                $id = Tools::get_value('id_' . $controller);
                $_GET['id_' . $controller] = $id;
                $this->controller = $controller;
                return $this->controller;
            }
        }
        if (!Validate::is_controller_name($controller)) {
            $controller = false;
        }
        if ($this->use_routes && !$controller && !defined('_PS_ADMIN_DIR_')) {
            if (!$request_uri) {
                return mb_strtolower($this->controller_not_found);
            }
            // Check basic controllers & params
            $controller = $this->controller_not_found;
            $test_request_uri = preg_replace('/(=http:\/\/)/', '=', $request_uri);
            $url_path = parse_url((string) $test_request_uri, PHP_URL_PATH);
            if ($url_path && !preg_match('/\.(css|js)$/i', $url_path)) {
                // Add empty route as last route to prevent this greedy regexp to match request uri before right time
                if ($this->empty_route) {
                    $this->add_route($this->empty_route['routeID'], $this->empty_route['rule'], $this->empty_route['controller'], Context::get_context()->language->id, [], [], $id_shop);
                }
                [$uri] = explode('?', $request_uri);
                if (isset($this->routes[$id_shop][Context::get_context()->language->id])) {
                    $routes = $this->routes[$id_shop][Context::get_context()->language->id];
                    foreach ($routes as $route_id => $route) {
                        if (preg_match($route['regexp'], $uri, $m)) {
                            // if route has dedicated matcher, use it to determine whether route was found or not
                            if (isset($this->matchers[$route_id])) {
                                $matcher = $this->matchers[$route_id];
                                $params = $matcher($m, $uri, $route);
                                if ($params === false) {
                                    // even though uri matches this route regexp, it does not matches associated mather
                                    continue;
                                }
                                if (is_array($params)) {
                                    foreach ($params as $key => $value) {
                                        $_GET[$key] = $value;
                                    }
                                } else {
                                    $params = [];
                                }
                            }
                            $is_module = isset($route['params']['fc']) && $route['params']['fc'] === 'module';
                            foreach ($m as $k => $v) {
                                // We might have us an external module page here, in that case we set whatever we can
                                if (!is_numeric($k) && !isset($params[$k]) && ($is_module || $k !== 'id' && $k !== 'ipa' && $k !== 'rewrite' && $k !== 'cms_rewrite' && $k !== 'cms_cat_rewrite')) {
                                    $_GET[$k] = $v;
                                    if (isset($route['aliases'][$k])) {
                                        $_GET[$route['aliases'][$k]] = $v;
                                    }
                                }
                            }
                            if (isset($route['controller']) && $route['controller']) {
                                $controller = (string) $route['controller'];
                            } elseif (isset($_GET['controller']) && $_GET['controller']) {
                                $controller = (string) $_GET['controller'];
                            }
                            if (!empty($route['params'])) {
                                foreach ($route['params'] as $k => $v) {
                                    $_GET[$k] = $v;
                                }
                            }
                            // A patch for module friendly urls
                            if ($info = $this->is_module_controller_route($controller)) {
                                $_GET['fc'] = 'module';
                                $_GET['module'] = $info['module'];
                                $controller = $info['controller'];
                            }
                            if (isset($_GET['fc']) && $_GET['fc'] == 'module') {
                                $this->front_controller = self::FC_MODULE;
                            }
                            break;
                        }
                    }
                }
            }
            // Check if index
            if ($controller == 'index' || preg_match('/^\/index.php(?:\?.*)?$/', $request_uri) || $uri == '') {
                $controller = $this->use_default_controller();
            }
        }
        $this->controller = str_replace('-', '', (string) $controller);
        $_GET['controller'] = $this->controller;
        return $this->controller;
    }
    /**
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function use_default_controller()
    {
        $this->use_default_controller = true;
        if ($this->default_controller === null) {
            if (defined('_PS_ADMIN_DIR_')) {
                if (isset(Context::get_context()->employee) && Validate::is_loaded_object(Context::get_context()->employee) && isset(Context::get_context()->employee->default_tab)) {
                    $this->default_controller = Tab::get_class_name_by_id((int) Context::get_context()->employee->default_tab);
                }
                if (empty($this->default_controller)) {
                    $this->default_controller = 'AdminDashboard';
                }
            } elseif (Tools::get_value('fc') == 'module') {
                $this->default_controller = 'default';
            } else {
                $this->default_controller = 'index';
            }
        }
        return $this->default_controller;
    }
    /**
     * Get list of all available FO controllers
     *
     * @param string|string[] $dirs
     */
    public static function get_controllers($dirs): array
    {
        if (!is_array($dirs)) {
            $dirs = [$dirs];
        }
        $controllers = [];
        foreach ($dirs as $dir) {
            $controllers = array_merge($controllers, Dispatcher::get_controllers_in_directory($dir));
        }
        return $controllers;
    }
    /**
     * Get list of available controllers from the specified dir
     *
     * @param string $dir Directory to scan (recursively)
     */
    public static function get_controllers_in_directory(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }
        $controllers = [];
        $controller_files = scandir($dir);
        foreach ($controller_files as $controller_filename) {
            if ($controller_filename[0] != '.') {
                if (!strpos($controller_filename, '.php') && is_dir($dir . $controller_filename)) {
                    $controllers += Dispatcher::get_controllers_in_directory($dir . $controller_filename . DIRECTORY_SEPARATOR);
                } elseif ($controller_filename != 'index.php') {
                    $key = str_replace(['controller.php', '.php'], '', strtolower($controller_filename));
                    $controllers[$key] = basename($controller_filename, '.php');
                }
            }
        }
        return $controllers;
    }
    /**
     * Check if a route exists
     *
     * @param string $routeId
     * @param int $idLang
     * @param int $idShop
     */
    public function has_route($route_id, $id_lang = null, $id_shop = null): bool
    {
        return !!$this->get_route($route_id, $id_lang, $id_shop);
    }
    /**
     * Returns route by its routeId
     *
     * @param string $routeId
     * @param int $idLang
     * @param int $idShop
     *
     * @return array | null
     */
    public function get_route($route_id, $id_lang = null, $id_shop = null)
    {
        if (isset(Context::get_context()->language) && $id_lang === null) {
            $id_lang = (int) Context::get_context()->language->id;
        }
        if (isset(Context::get_context()->shop) && $id_shop === null) {
            $id_shop = (int) Context::get_context()->shop->id;
        }
        return $this->routes[$id_shop][$id_lang][$route_id] ?? null;
    }
    /**
     * Check if a keyword is written in a route rule
     *
     * @param string $routeId
     * @param int $idLang
     * @param string $keyword
     * @param int $idShop
     *
     *
     * @throws PrestaShopException
     */
    public function has_keyword($route_id, $id_lang, $keyword, $id_shop = null): false|int
    {
        if ($id_shop === null) {
            $id_shop = (int) Context::get_context()->shop->id;
        }
        if (!isset($this->routes[$id_shop])) {
            $this->load_routes($id_shop);
        }
        if (!isset($this->routes[$id_shop]) || !isset($this->routes[$id_shop][$id_lang]) || !isset($this->routes[$id_shop][$id_lang][$route_id])) {
            return false;
        }
        return preg_match('#\{([^{}]*:)?' . preg_quote($keyword, '#') . '(:[^{}]*)?\}#', (string) $this->routes[$id_shop][$id_lang][$route_id]['rule']);
    }
    /**
     * Check if a route rule contain all required keywords of default route definition
     *
     * @param string $routeId
     * @param string $rule Rule to verify
     * @param array $errors List of missing keywords
     *
     * @return bool
     */
    public function validate_route($route_id, $rule, &$errors = [])
    {
        $errors = [];
        if (!isset($this->default_routes[$route_id])) {
            return false;
        }
        foreach ($this->default_routes[$route_id]['keywords'] as $keyword => $data) {
            if ($this->use_routes && $keyword === 'id') {
                continue;
            }
            if ($this->use_routes && $keyword === 'rewrite') {
                $data['param'] = true;
            }
            if (isset($data['param']) && !preg_match('#\{([^{}]*:)?' . $keyword . '(:[^{}]*)?\}#', $rule)) {
                $errors[] = $keyword;
            }
        }
        return !count($errors);
    }
    /**
     * Create an url from
     *
     * @param string $routeId Name of the route
     * @param int $idLang
     * @param bool $forceRoutes
     * @param string $anchor Optional anchor to add at the end of this url
     * @param int|null $idShop
     *
     *
     * @throws PrestaShopException
     */
    public function create_url($route_id, $id_lang = null, array $params = [], $force_routes = false, string $anchor = '', $id_shop = null): string
    {
        if ($id_lang === null) {
            $id_lang = (int) Context::get_context()->language->id;
        }
        if ($id_shop === null) {
            $id_shop = (int) Context::get_context()->shop->id;
        }
        if (!isset($this->routes[$id_shop])) {
            $this->load_routes($id_shop);
        }
        if (!isset($this->routes[$id_shop][$id_lang][$route_id])) {
            $route_id = trim($route_id ?? '');
            switch ($route_id) {
                case '':
                    return '';
                case 'index':
                    $query = http_build_query($params, '', '&');
                    $index_link = $this->use_routes ? '' : 'index.php';
                    return $index_link . ($query ? '?' . $query : '');
                default:
                    $query = http_build_query($params, '', '&');
                    return 'index.php?controller=' . $route_id . ($query ? '&' . $query : '') . $anchor;
            }
        }
        $route = $this->routes[$id_shop][$id_lang][$route_id];
        // Check required fields
        $query_params = $route['params'] ?? [];
        // Skip if we are not using routes
        // Build an url which match a route
        if ($this->use_routes || $force_routes) {
            $aliases = array_flip($route['aliases']);
            foreach ($route['keywords'] as $key => $data) {
                if (!$data['required']) {
                    continue;
                }
                $alias = $aliases[$key] ?? null;
                if ($alias && array_key_exists($alias, $params)) {
                    $params[$key] = $params[$alias];
                    unset($params[$alias]);
                }
                if (!array_key_exists($key, $params)) {
                    if ($alias) {
                        throw new Presta_Shop_Exception('Dispatcher::createUrl() miss required parameter "' . $alias . '" or it\'s alias "' . $key . '"for route "' . $route_id . '"');
                    }
                    throw new Presta_Shop_Exception('Dispatcher::createUrl() miss required parameter "' . $key . '" for route "' . $route_id . '"');
                }
                if (isset($this->default_routes[$route_id])) {
                    $query_params[$this->default_routes[$route_id]['keywords'][$key]['param']] = $params[$key];
                }
            }
            $url = $route['rule'];
            $add_param = [];
            foreach ($params as $key => $value) {
                if (!isset($route['keywords'][$key])) {
                    if (!isset($this->default_routes[$route_id]['keywords'][$key])) {
                        $add_param[$key] = $value;
                    }
                } else {
                    if ($value) {
                        $replace = $route['keywords'][$key]['prepend'] . $value . $route['keywords'][$key]['append'];
                    } else {
                        $replace = '';
                    }
                    $url = preg_replace('#\{([^{}]*:)?' . $key . '(:[^{}]*)?\}#', $replace, (string) $url);
                }
            }
            $url = preg_replace('#\{([^{}]*:)?[a-z0-9_]+?(:[^{}]*)?\}#', '', (string) $url);
            if (count($add_param)) {
                $url .= '?' . http_build_query($add_param, '', '&');
            }
        } else {
            $add_params = [];
            foreach ($route['keywords'] as $key => $data) {
                if (!$data['required']) {
                    continue;
                }
                if (!array_key_exists($key, $params)) {
                    continue;
                }
                if ($key === 'rewrite' && in_array($route['controller'], ['product', 'category', 'supplier', 'manufacturer', 'cms', 'cms_category'])) {
                    continue;
                }
                if (isset($this->default_routes[$route_id])) {
                    $query_params[$this->default_routes[$route_id]['keywords'][$key]['param']] = $params[$key];
                }
            }
            foreach ($params as $key => $value) {
                if (!isset($route['keywords'][$key]) && !isset($this->default_routes[$route_id]['keywords'][$key])) {
                    $add_params[$key] = $value;
                }
            }
            if (isset($this->default_routes[$route_id])) {
                foreach ($this->default_routes[$route_id]['keywords'] as $key => $keyword) {
                    if (isset($keyword['alias']) && $keyword['alias']) {
                        $add_params[$keyword['alias']] = $params[$key];
                    }
                }
            }
            if (!empty($route['controller'])) {
                $query_params['controller'] = $route['controller'];
            }
            $query = http_build_query(array_merge($add_params, $query_params), '', '&');
            if ($this->multilang_activated) {
                $query .= (!empty($query) ? '&' : '') . 'id_lang=' . (int) $id_lang;
            }
            $url = 'index.php?' . $query;
        }
        return $url . $anchor;
    }
    /**
     * This method tries to match core rewritable controllers
     *
     * @param array $parts parsed uri according to $route rule definition
     * @param string $uri
     * @param array $route route definition
     *
     * @return array | false
     */
    protected function match_rewritable_route(array $parts, $uri, array $route): array|false
    {
        $type = $route['controller'];
        if ($type === 'cms') {
            if (isset($parts['cms_rewrite'])) {
                $key = 'id_cms';
                $func = 'cmsID';
                $rewrite = 'cms_rewrite';
            } else {
                $key = 'id_cms_category';
                $func = 'cmsCategoryID';
                $rewrite = 'cms_cat_rewrite';
            }
        } else {
            $key = 'id_' . $type;
            $func = $type . 'ID';
            $rewrite = 'rewrite';
        }
        // Look if record ID is part of the parsed uri. If exists, use it directly
        if (isset($parts['id']) && $parts['id']) {
            $id = (int) $parts['id'];
            if ($id) {
                return [$key => $id];
            }
            return false;
        }
        // try to resolve by rewrite / full uri
        if (isset($parts[$rewrite])) {
            $id = $this->{$func}($parts[$rewrite], $uri);
            if ($id) {
                return [$key => (int) $id];
            }
            return false;
        }
        return false;
    }
    /**
     * @param string $rewrite
     * @param string $url
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function product_id($rewrite, $url = ''): int
    {
        // Rewrite and url cannot both be empty
        if (empty($rewrite)) {
            return 0;
        }
        // Remove leading slash from URL
        $url = ltrim($url, '/');
        $context = Context::get_context();
        $link = $context->link;
        $id_lang = $context->language->id;
        $id_shop = $context->shop->id;
        // Context sometimes contains no link in older versions of PS
        if (empty($link)) {
            $link = new Link();
        }
        $sql = new Db_Query();
        $sql->select('`id_product`');
        $sql->from('product_lang');
        $sql->where('`link_rewrite` = \'' . p_sql($rewrite) . '\'');
        $sql->where('`id_lang` = ' . (int) $id_lang);
        $sql->where('`id_shop` = ' . (int) $id_shop);
        $results = Db::read_only()->get_array($sql);
        if (!empty($results)) {
            $base_link = $link->get_base_link() . $link->get_lang_link();
            if (count($results) > 1 && !empty($url)) {
                // Multiple rewrites available, full URL needs to be checked
                foreach ($results as $result) {
                    $product_link = $link->get_product_link($result['id_product']);
                    if ($url === str_replace($base_link, '', $product_link)) {
                        return (int) $result['id_product'];
                    }
                }
            }
            return (int) $results[0]['id_product'];
        }
        return 0;
    }
    /**
     * @param string $rewrite
     * @param string $url
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function category_id($rewrite, $url = ''): int
    {
        // Rewrite cannot be empty
        if (empty($rewrite)) {
            return 0;
        }
        // Remove leading slash from URL
        $url = ltrim($url, '/');
        $context = Context::get_context();
        $link = $context->link;
        $id_lang = $context->language->id;
        $id_shop = $context->shop->id;
        // Context sometimes contains no link in older versions of PS
        if (empty($link)) {
            $link = new Link();
        }
        $sql = new Db_Query();
        $sql->select('`id_category`');
        $sql->from('category_lang');
        $sql->where('`link_rewrite` = \'' . p_sql($rewrite) . '\'');
        $sql->where('`id_lang` = ' . (int) $id_lang);
        $sql->where('`id_shop` = ' . (int) $id_shop);
        $results = Db::read_only()->get_array($sql);
        if (!empty($results)) {
            $base_link = $link->get_base_link() . $link->get_lang_link();
            if (count($results) > 1 && !empty($url)) {
                // Multiple rewrites available, full URL needs to be checked
                foreach ($results as $result) {
                    $category_link = $link->get_category_link($result['id_category']);
                    if ($url === str_replace($base_link, '', $category_link)) {
                        return (int) $result['id_category'];
                    }
                }
            } else {
                $category_link = $link->get_category_link((int) $results[0]['id_category']);
                if ($url === str_replace($base_link, '', $category_link)) {
                    return (int) $results[0]['id_category'];
                }
            }
        }
        return 0;
    }
    /**
     * @param string $rewrite
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function supplier_id($rewrite): int
    {
        // Rewrite cannot be empty
        if (empty($rewrite)) {
            return 0;
        }
        $context = Context::get_context();
        $suppliers = Supplier::get_suppliers(false, $context->language->id, true);
        foreach ($suppliers as $supplier) {
            if (Tools::link_rewrite($supplier['name']) === $rewrite) {
                return (int) $supplier['id_supplier'];
            }
        }
        return 0;
    }
    /**
     * @param string $rewrite
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function manufacturer_id($rewrite): int
    {
        // Rewrite cannot be empty
        if (empty($rewrite)) {
            return 0;
        }
        $context = Context::get_context();
        $manufacturers = Manufacturer::get_manufacturers(false, $context->language->id, true);
        foreach ($manufacturers as $manufacturer) {
            if (Tools::link_rewrite($manufacturer['name']) === $rewrite) {
                return (int) $manufacturer['id_manufacturer'];
            }
        }
        return 0;
    }
    /**
     * @param string $rewrite
     * @param string $url
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function cms_id($rewrite, $url = ''): int
    {
        // Rewrite cannot be empty
        if (empty($rewrite)) {
            return 0;
        }
        // Remove leading slash from URL
        $url = ltrim($url, '/');
        $context = Context::get_context();
        $link = $context->link;
        $id_lang = $context->language->id;
        $id_shop = $context->shop->id;
        // Context sometimes contains no link in older versions of PS
        if (empty($link)) {
            $link = new Link();
        }
        $sql = new Db_Query();
        $sql->select('`cl`.`id_cms`');
        $sql->from('cms_lang', 'cl');
        $sql->inner_join('cms_shop', 'cs', '`cl`.`id_cms` = `cs`.`id_cms`');
        $sql->where('`link_rewrite` = \'' . p_sql($rewrite) . '\'');
        $sql->where('`cl`.`id_lang` = ' . (int) $id_lang);
        $sql->where('`cs`.`id_shop` = ' . (int) $id_shop);
        $results = Db::read_only()->get_array($sql);
        if (!empty($results)) {
            $base_link = $link->get_base_link() . $link->get_lang_link();
            if (count($results) > 1 && !empty($url)) {
                // Multiple rewrites available, full URL needs to be checked
                foreach ($results as $result) {
                    $cms_link = $link->get_cms_link($result['id_cms']);
                    if ($url === str_replace($base_link, '', $cms_link)) {
                        return (int) $result['id_cms'];
                    }
                }
            } else {
                $cms_link = $link->get_cms_link((int) $results[0]['id_cms']);
                if ($url === str_replace($base_link, '', $cms_link)) {
                    return (int) $results[0]['id_cms'];
                }
            }
        }
        return 0;
    }
    /**
     * @param string $rewrite
     * @param string $url
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function cms_category_id($rewrite, $url = ''): int
    {
        // Rewrite cannot be empty
        if (empty($rewrite)) {
            return 0;
        }
        // Remove leading slash from URL
        $url = ltrim($url, '/');
        $context = Context::get_context();
        $link = $context->link;
        $id_lang = $context->language->id;
        $id_shop = $context->shop->id;
        // Context sometimes contains no link in older versions of PS
        if (empty($link)) {
            $link = new Link();
        }
        $sql = new Db_Query();
        $sql->select('`cl`.`id_cms_category`');
        $sql->from('cms_category_lang', 'cl');
        $sql->inner_join('cms_category_shop', 'cs', '`cl`.`id_cms_category` = `cs`.`id_cms_category`');
        $sql->where('`link_rewrite` = \'' . p_sql($rewrite) . '\'');
        $sql->where('`cl`.`id_lang` = ' . (int) $id_lang);
        $sql->where('`cs`.`id_shop` = ' . (int) $id_shop);
        $results = Db::read_only()->get_array($sql);
        if (!empty($results)) {
            $base_link = $link->get_base_link() . $link->get_lang_link();
            if (count($results) > 1 && !empty($url)) {
                // Multiple rewrites available, full URL needs to be checked
                foreach ($results as $result) {
                    $cms_link = $link->get_cms_category_link($result['id_cms_category']);
                    if ($url === str_replace($base_link, '', $cms_link)) {
                        return (int) $result['id_cms_category'];
                    }
                }
            } else {
                $cms_link = $link->get_cms_category_link((int) $results[0]['id_cms_category']);
                if ($url === str_replace($base_link, '', $cms_link)) {
                    return (int) $results[0]['id_cms_category'];
                }
            }
        }
        return 0;
    }
    /**
     * @param string $rule
     *
     */
    protected function create_reg_exp($rule, array $keywords): string
    {
        $regexp = preg_quote($rule, '#');
        if ($keywords) {
            preg_match_all('#\\\\{(([^{}]*)\\\\:)?(' . implode('|', array_keys($keywords)) . ')(\\\\:([^{}]*))?\\\\}#', $regexp, $m);
            for ($i = 0, $total = count($m[0]); $i < $total; $i++) {
                $prepend = $m[2][$i];
                $keyword = $m[3][$i];
                $append = $m[5][$i];
                $prepend_regexp = $append_regexp = '';
                if ($prepend || $append) {
                    $prepend_regexp = '(' . preg_quote($prepend);
                    $append_regexp = preg_quote($append) . ')?';
                }
                if (isset($keywords[$keyword]['param'])) {
                    $regexp = str_replace($m[0][$i], $prepend_regexp . '(?P<' . $keywords[$keyword]['param'] . '>' . $keywords[$keyword]['regexp'] . ')' . $append_regexp, $regexp);
                } else {
                    $regexp = str_replace($m[0][$i], $prepend_regexp . '(' . $keywords[$keyword]['regexp'] . ')' . $append_regexp, $regexp);
                }
            }
        }
        return '#^/' . $regexp . '$#u';
    }
    /**
     * Registers matcher function for given route
     *
     * Matcher function is called with following parameters:
     *    - $parts - array parsed when $route['rule'] regexp is matched against $uri
     *    - $uri - matched uri
     *    - $route - current route object
     * and it must return either
     *    - false - this means that $uri does NOT represent current route, even though it matched regexp
     *    - array - associative array of parameters that will be passed to route Controller
     *              This array usually contains resolved primary key, but it can contain additional
     *              information as well
     *
     * @param string $routeId unique route ID
     * @param callable $matcher matche function
     * @throws PrestaShopException
     */
    public function set_route_matcher($route_id, $matcher): void
    {
        if (is_callable($matcher)) {
            $this->matchers[$route_id] = $matcher;
        } else {
            throw new Presta_Shop_Exception("Can't register matcher for route {$route_id}");
        }
    }
    /**
     * Returns matcher function associated with given route
     *
     * @param string $routeId
     * @return callable | null
     */
    public function get_route_matcher($route_id)
    {
        return $this->matchers[$route_id] ?? null;
    }
    /**
     * Returns array with information about module/controller, if $routeId matches module controller route
     *
     * @param string $routeId
     * @return false| array
     */
    public function is_module_controller_route($route_id): array|false
    {
        if (preg_match('#module-([a-z0-9_-]+)-([a-z0-9_]+)$#i', (string) $route_id, $m)) {
            return ['module' => $m[1], 'controller' => $m[2]];
        }
        return false;
    }
    /**
     * Returns parameters names required by route with id $routeId
     *
     * @param int|null $langId
     *
     */
    public function get_route_required_params(string $route_id, int $lang_id): array
    {
        $params = [];
        $route = $this->get_route($route_id, $lang_id);
        if ($route) {
            $aliases = array_flip($route['aliases']);
            foreach ($route['keywords'] as $keyword => $info) {
                if ($info['required']) {
                    if (isset($aliases[$keyword]) && $aliases[$keyword]) {
                        $alias = $aliases[$keyword];
                        $params[$alias] = $alias;
                    }
                    $params[$keyword] = $keyword;
                }
            }
        }
        return $params;
    }
    /**
     * Extracts request_uri from request
     */
    protected static function extract_request_uri(): string
    {
        // Get request uri (HTTP_X_REWRITE_URL is used by IIS)
        if (isset($_SERVER['REQUEST_URI'])) {
            return rawurldecode((string) $_SERVER['REQUEST_URI']);
        }
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            return rawurldecode((string) $_SERVER['HTTP_X_REWRITE_URL']);
        }
        return '';
    }
    /**
     *
     *
     * @throws PrestaShopException
     */
    protected function get_language_from_uri(string $request_uri): ?Language
    {
        $languages = Language::get_languages(true, Context::get_context()->shop->id);
        if (!$languages) {
            return null;
        }
        $codes = [];
        foreach ($languages as $data) {
            $lang = new Language();
            $lang->hydrate($data);
            $url_code = $lang->get_url_code();
            $codes[$url_code] = $lang;
        }
        $regexp_codes = implode('|', array_map(preg_quote(...), array_keys($codes)));
        if (preg_match('#^/(' . $regexp_codes . ')(?:/.*)?$#', $request_uri, $m)) {
            $url_code = strtolower($m[1]);
            return $codes[$url_code];
        }
        return null;
    }
    /**
     * @throws PrestaShopException
     */
    protected function get_default_language_iso_code(): string
    {
        return (string) Language::get_iso_by_id((int) Configuration::get('PS_LANG_DEFAULT'));
    }
    /**
     * Returns true, if $requestUri points to PHP script file
     *
     * This means that php script included thirty bees core and triggered dispatcher
     */
    protected function is_php_script_url(string $request_uri): bool
    {
        $path = (string) parse_url($request_uri, PHP_URL_PATH);
        $path = '/' . ltrim($path, '/');
        if (str_ends_with($path, '/')) {
            $path .= 'index.php';
        }
        // remove extra path that after actual php script file, for example /index.php/extra/path => /index.php
        $path = preg_replace("#\\.php\\/.*\$#", '.php', $path);
        // special handling for root index.php, we will consider this to be
        // php script file only for non GET requests
        if ($path === '/index.php') {
            return Tools::get_request_method() !== 'GET';
        }
        return file_exists(_PS_ROOT_DIR_ . $path);
    }
}