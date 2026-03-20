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
use Guzzle_Http\Client;
use Thirtybees\Core\Dependency_Injection\Service_Locator;
use Thirtybees\Core\Error\Error_Utils;
use Thirtybees\Core\Error\Response\J_Send_Error_Response;
/**
 * Class AdminControllerCore
 */
class Admin_Controller_Core extends Controller
{
    public const LEVEL_VIEW = 1;
    public const LEVEL_EDIT = 2;
    public const LEVEL_ADD = 3;
    public const LEVEL_DELETE = 4;
    public const DEFAULT_VIEW_TEMPLATE = 'content.tpl';
    // Cache file to make errors/warnings/informations/confirmations
    // survive redirects.
    public const MESSAGE_CACHE_PATH = 'AdminControllerMessages.php';
    /** @var string */
    public static $current_index;
    /** @var array Cache for translations */
    public static $cache_lang = [];
    /** @var string */
    public $path;
    /** @var string|string[] */
    public $content;
    /** @var array */
    public $warnings = [];
    /** @var array */
    public $informations = [];
    /** @var array */
    public $confirmations = [];
    /** @var string|false */
    public $shop_share_datas = false;
    /** @var array */
    public $_languages = [];
    /** @var int */
    public $default_form_language;
    /** @var int */
    public $allow_employee_form_lang;
    /** @var string */
    public $layout = 'layout.tpl';
    /** @var bool */
    public $bootstrap = false;
    /** @var string */
    public $template = 'content.tpl';
    /** @var string Associated table name */
    public $table = 'configuration';
    /** @var string */
    public $list_id;
    /** @var string Associated object class name */
    public $class_name;
    /** @var array */
    public $tab_access;
    /** @var int Tab id */
    public $id = -1;
    /** @var bool */
    public $required_database = false;
    /** @var string Security token */
    public $token;
    /** @var string "shop" or "group_shop" */
    public $shop_link_type;
    /** @var array */
    public $tpl_form_vars = [];
    /** @var array */
    public $tpl_list_vars = [];
    /** @var array */
    public $tpl_delete_link_vars = [];
    /** @var array */
    public $tpl_option_vars = [];
    /** @var array */
    public $tpl_view_vars = [];
    /** @var array */
    public $tpl_required_fields_vars = [];
    /** @var string|null */
    public $base_tpl_view;
    /** @var string|null */
    public $base_tpl_form;
    /** @var bool If you want more fieldsets in the form */
    public $multiple_fieldsets = false;
    /** @var array */
    public $fields_value = [];
    /** @var bool Automatically join language table if true */
    public $lang = false;
    /** @var array Required_fields to display in the Required Fields form */
    public $required_fields = [];
    /** @var string */
    public $tpl_folder;
    /** @var string  */
    public $override_folder;
    /** @var array Name and directory where class image are located */
    public $field_image_settings = [];
    /** @var string Image type */
    public $image_type;
    /** @var string Current controller name without suffix */
    public $controller_name;
    /** @var int */
    public $multishop_context = -1;
    /** @var false */
    public $multishop_context_group = true;
    /** @var bool Bootstrap variable */
    public $show_page_header_toolbar = false;
    /** @var string Bootstrap variable */
    public $page_header_toolbar_title;
    /** @var array|Traversable Bootstrap variable */
    public $page_header_toolbar_btn = [];
    /** @var bool Bootstrap variable */
    public $show_form_cancel_button;
    /** @var string */
    public $admin_webpath;
    /** @var array */
    public $modals = [];
    /** @var array */
    public $ajax_params = [];
    /** @var string|array */
    protected $meta_title = [];
    /** @var string|false Object identifier inside the associated table */
    protected $identifier = false;
    /** @var string */
    protected $identifier_name = 'name';
    /** @var string Default ORDER BY clause when $_orderBy is not defined */
    protected $_default_order_by = false;
    /** @var string */
    protected $_default_order_way = 'ASC';
    /** @var bool Define if the header of the list contains filter and sorting links or not */
    protected $list_simple_header;
    /** @var array List to be generated */
    protected $fields_list;
    /** @var array Modules list filters */
    protected $filter_modules_list;
    /** @var array Modules list filters */
    protected $modules_list = [];
    /** @var array Edit form to be generated */
    protected $fields_form;
    /** @var array Override of $fields_form */
    protected $fields_form_override;
    /** @var string Override form action */
    protected $submit_action;
    /** @var array List of option forms to be generated */
    protected $fields_options = [];
    /** @var string */
    protected $shop_link;
    /** @var string SQL query */
    protected $_listsql = '';
    /** @var array Cache for query results */
    protected $_list = [];
    /** @var string|null */
    protected $_list_error;
    /** @var string|array Toolbar title */
    protected $toolbar_title;
    /** @var array List of toolbar buttons */
    protected $toolbar_btn;
    /** @var bool Scrolling toolbar */
    protected $toolbar_scroll = true;
    /** @var bool Set to false to hide toolbar and page title */
    protected $show_toolbar = true;
    /** @var bool Set to true to show toolbar and page title for options */
    protected $show_toolbar_options = false;
    /** @var int Number of results in list */
    protected $_list_total = 0;
    /** @var string|false WHERE clause determined by filter fields */
    protected $_filter;
    /** @var string */
    protected $_filter_having;
    /** @var array Temporary SQL table WHERE clause determined by filter fields */
    protected $_tmp_table_filter = '';
    /** @var array Number of results in list per page (used in select field) */
    protected $_pagination = [20, 50, 100, 300, 1000];
    /** @var int Default number of results in list per page */
    protected $_default_pagination = 50;
    /** @var string ORDER BY clause determined by field/arrows in list header */
    protected $_order_by;
    /** @var string Order way (ASC, DESC) determined by arrows in list header */
    protected $_order_way;
    /** @var array List of available actions for each list row - default actions are view, edit, delete, duplicate */
    protected $actions_available = ['view', 'edit', 'duplicate', 'delete'];
    /** @var array List of required actions for each list row */
    protected $actions = [];
    /** @var array List of row ids associated with a given action for witch this action have to not be available */
    protected $list_skip_actions = [];
    /* @var bool Don't show header & footer */
    protected $lite_display = false;
    /** @var bool List content lines are clickable if true */
    protected $list_no_link = false;
    /** @var bool */
    protected $allow_export = false;
    /** @var HelperList */
    protected $helper;
    /**
     * Actions to execute on multiple selections.
     *
     * Usage:
     *
     * [
     *      'actionName'    => [
     *      'text'          => $this->l('Message displayed on the submit button (mandatory)'),
     *      'confirm'       => $this->l('If set, this confirmation message will pop-up (optional)')),
     *      'anotherAction' => [...]
     * ];
     *
     * If your action is named 'actionName', you need to have a method named bulkactionName() that will be executed when the button is clicked.
     *
     * @var array
     */
    protected $bulk_actions;
    /* @var array Ids of the rows selected */
    protected $boxes;
    /** @var string Do not automatically select * anymore but select only what is necessary */
    protected $explicit_select = false;
    /** @var string Add fields into data query to display list */
    protected $_select;
    /** @var string Join tables into data query to display list */
    protected $_join;
    /** @var string Add conditions into data query to display list */
    protected $_where;
    /** @var string Group rows into data query to display list */
    protected $_group;
    /** @var string Having rows into data query to display list */
    protected $_having;
    /** @var string Use SQL_CALC_FOUND_ROWS / FOUND_ROWS to count the number of records */
    protected $_use_found_rows = true;
    /** @var bool */
    protected $is_cms = false;
    /** @var string Identifier to use for changing positions in lists (can be omitted if positions cannot be changed) */
    protected $position_identifier;
    /** @var string|int */
    protected $position_group_identifier;
    /** @var bool Table records are not deleted but marked as deleted if set to true */
    protected $deleted = false;
    /**  @var bool Is a list filter set */
    protected $filter;
    /** @var bool */
    protected $no_link;
    /** @var bool|string|null */
    protected $specific_confirm_delete;
    /** @var bool */
    protected $color_on_background;
    /** @var bool If true, activates color on hover */
    protected $row_hover = true;
    /** @var string Action to perform : 'edit', 'view', 'add', ... */
    protected $action;
    /** @var string */
    protected $display;
    /** @var bool */
    protected $_include_container = true;
    /** @var array */
    protected $tab_modules_list = ['default_list' => [], 'slider_list' => []];
    /** @var string */
    protected $bo_theme;
    /** @var bool Redirect or not after a creation */
    protected $_redirect = true;
    /** @var ObjectModel|null Instantiation of the class associated with the AdminController */
    protected $object;
    /** @var int Current object ID */
    protected $id_object;
    /** @var array Current breadcrumb position as an array of tab names */
    protected $breadcrumbs;
    /** @var array */
    protected $list_natives_modules = [];
    /** @var array */
    protected $list_partners_modules = [];
    /** @var bool if logged employee has access to AdminImport */
    protected $can_import = false;
    /** @var array */
    protected $translations_tab = [];
    /** @var bool $isThirtybeesUp */
    public static $is_thirtybees_up = true;
    /** @var float */
    protected $timer_start;
    /** @var string */
    protected $bo_css;
    protected array $_conf = [];
    /**
     * @var int|null
     */
    protected $max_image_size;
    /**
     * If set to true, any exception throws in postProcess() phase will be converted to error message. Otherwise,
     * exceptions will cause error page response
     *
     * @var bool
     */
    protected $post_process_handle_exceptions = true;
    /**
     * @var boolean
     */
    protected $list_fields_extended = false;
    /**
     * AdminControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        global $timer_start;
        global $token;
        $this->timer_start = $timer_start;
        $message_cache_path = _PS_CACHE_DIR_ . '/' . static::MESSAGE_CACHE_PATH . '-' . Tools::get_value('token');
        if (is_readable($message_cache_path)) {
            include $message_cache_path;
            unlink($message_cache_path);
        }
        $this->controller_type = 'admin';
        $this->controller_name = static::class;
        if (strpos($this->controller_name, 'Controller')) {
            $this->controller_name = substr($this->controller_name, 0, -10);
        }
        parent::__construct();
        if ($this->multishop_context == -1) {
            $this->multishop_context = Shop::CONTEXT_ALL | Shop::CONTEXT_GROUP | Shop::CONTEXT_SHOP;
        }
        $default_theme_name = 'default';
        if (defined('_PS_BO_DEFAULT_THEME_') && _PS_BO_DEFAULT_THEME_ && @filemtime(_PS_BO_ALL_THEMES_DIR_ . _PS_BO_DEFAULT_THEME_ . DIRECTORY_SEPARATOR . 'template')) {
            $default_theme_name = _PS_BO_DEFAULT_THEME_;
        }
        $this->bo_theme = Validate::is_loaded_object($this->context->employee) && $this->context->employee->bo_theme ? $this->context->employee->bo_theme : $default_theme_name;
        if (!@filemtime(_PS_BO_ALL_THEMES_DIR_ . $this->bo_theme . DIRECTORY_SEPARATOR . 'template')) {
            $this->bo_theme = $default_theme_name;
        }
        $this->bo_css = Validate::is_loaded_object($this->context->employee) && $this->context->employee->bo_css ? $this->context->employee->bo_css : 'admin-theme.css';
        if (!@filemtime(_PS_BO_ALL_THEMES_DIR_ . $this->bo_theme . DIRECTORY_SEPARATOR . 'css' . DIRECTORY_SEPARATOR . $this->bo_css)) {
            $this->bo_css = 'admin-theme.css';
        }
        $this->context->smarty->set_template_dir([_PS_BO_ALL_THEMES_DIR_ . $this->bo_theme . DIRECTORY_SEPARATOR . 'template', _PS_OVERRIDE_DIR_ . 'controllers' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'templates']);
        $this->id = Tab::get_id_from_class_name($this->controller_name);
        $this->token = Tools::get_admin_token($this->controller_name . (int) $this->id . (int) $this->context->employee->id);
        $token = $this->token;
        $this->_conf = [1 => $this->l('Successful deletion'), 2 => $this->l('The selection has been successfully deleted.'), 3 => $this->l('Successful creation'), 4 => $this->l('Successful update'), 5 => $this->l('The status has been successfully updated.'), 6 => $this->l('The settings have been successfully updated.'), 7 => $this->l('The image was successfully deleted.'), 8 => $this->l('The module was successfully downloaded.'), 9 => $this->l('The thumbnails were successfully regenerated.'), 10 => $this->l('The message was successfully sent to the customer.'), 11 => $this->l('Comment successfully added'), 12 => $this->l('Module(s) installed successfully.'), 13 => $this->l('Module(s) uninstalled successfully.'), 14 => $this->l('The translation was successfully copied.'), 15 => $this->l('The translations have been successfully added.'), 16 => $this->l('The module transplanted successfully to the hook.'), 17 => $this->l('The module was successfully removed from the hook.'), 18 => $this->l('Successful upload'), 19 => $this->l('Duplication was completed successfully.'), 20 => $this->l('The translation was added successfully, but the language has not been created.'), 21 => $this->l('Module reset successfully.'), 22 => $this->l('Module deleted successfully.'), 23 => $this->l('Localization pack imported successfully.'), 24 => $this->l('Localization pack imported successfully.'), 25 => $this->l('The selected images have successfully been moved.'), 26 => $this->l('Your cover image selection has been saved.'), 27 => $this->l('The image\'s shop association has been modified.'), 28 => $this->l('A zone has been assigned to the selection successfully.'), 29 => $this->l('Successful upgrade'), 30 => $this->l('A partial refund was successfully created.'), 31 => $this->l('The discount was successfully generated.'), 32 => $this->l('Successfully signed in')];
        if (!$this->identifier) {
            $this->identifier = 'id_' . $this->table;
        }
        if (!$this->_default_order_by) {
            $this->_default_order_by = $this->identifier;
        }
        $this->tab_access = Profile::get_profile_access($this->context->employee->id_profile, $this->id);
        if (!Shop::is_feature_active()) {
            $this->shop_link_type = '';
        }
        //$this->base_template_folder = _PS_BO_ALL_THEMES_DIR_.$this->bo_theme.'/template';
        $this->override_folder = Tools::to_underscore_case(substr($this->controller_name, 5)) . '/';
        // Get the name of the folder containing the custom tpl files
        $this->tpl_folder = Tools::to_underscore_case(substr($this->controller_name, 5)) . '/';
        $this->init_shop_context();
        $this->context->currency = new Currency(Configuration::get('PS_CURRENCY_DEFAULT'));
        $this->image_type = Image_Manager::get_default_image_extension();
        $this->clean_field_image_settings();
        $this->admin_webpath = str_ireplace(_PS_CORE_DIR_, '', _PS_ADMIN_DIR_);
        $this->admin_webpath = preg_replace('/^' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', '', $this->admin_webpath);
        $this->can_import = $this->context->employee->has_access(Admin_Import_Controller::class, Profile::PERMISSION_VIEW);
        $this->context->smarty->assign('can_import', $this->can_import);
    }
    /**
     * Non-static method which uses AdminController::translate()
     *
     * @param string $string Term or expression in english
     * @param string|null $class Name of the class
     * @param bool $addslashes If set to true, the return value will pass through addslashes(). Otherwise, stripslashes().
     * @param bool $htmlentities If set to true(default), the return value will pass through htmlentities($string, ENT_QUOTES, 'utf-8')
     *
     * @return string The translation if available, or the english default text.
     */
    protected function l($string, $class = null, $addslashes = false, $htmlentities = true)
    {
        if ($class === null || $class == 'AdminTab') {
            $class = substr(static::class, 0, -10);
        } elseif (strtolower(substr($class, -10)) == 'controller') {
            /* classname has changed, from AdminXXX to AdminXXXController, so we remove 10 characters and we keep same keys */
            $class = substr($class, 0, -10);
        }
        return Translate::get_admin_translation($string, $class, $addslashes, $htmlentities);
    }
    /**
     * @throws PrestaShopException
     */
    public function init_shop_context(): void
    {
        if (!$this->context->employee->is_logged_back()) {
            return;
        }
        // Change shop context ?
        if (Shop::is_feature_active() && Tools::get_value('setShopContext') !== false) {
            $this->context->cookie->shop_context = Tools::get_value('setShopContext');
            $url = parse_url((string) $_SERVER['REQUEST_URI']);
            $query = $url['query'] ?? '';
            parse_str($query, $parse_query);
            unset($parse_query['setShopContext'], $parse_query['conf']);
            $this->redirect_after = $url['path'] . '?' . http_build_query($parse_query, '', '&');
        } elseif (!Shop::is_feature_active()) {
            $this->context->cookie->shop_context = 's-' . (int) Configuration::get('PS_SHOP_DEFAULT');
        } elseif (Shop::get_total_shops(false, null) < 2) {
            $this->context->cookie->shop_context = 's-' . (int) $this->context->employee->get_default_shop_id();
        }
        $id_shop = '';
        Shop::set_context(Shop::CONTEXT_ALL);
        if ($this->context->cookie->shop_context) {
            $split = explode('-', (string) $this->context->cookie->shop_context);
            if (count($split) == 2) {
                if ($split[0] == 'g') {
                    if ($this->context->employee->has_auth_on_shop_group((int) $split[1])) {
                        Shop::set_context(Shop::CONTEXT_GROUP, (int) $split[1]);
                    } else {
                        $id_shop = (int) $this->context->employee->get_default_shop_id();
                        Shop::set_context(Shop::CONTEXT_SHOP, $id_shop);
                    }
                } elseif (Shop::get_shop($split[1]) && $this->context->employee->has_auth_on_shop($split[1])) {
                    $id_shop = (int) $split[1];
                    Shop::set_context(Shop::CONTEXT_SHOP, $id_shop);
                } else {
                    $id_shop = (int) $this->context->employee->get_default_shop_id();
                    Shop::set_context(Shop::CONTEXT_SHOP, $id_shop);
                }
            }
        }
        // Check multishop context and set right context if need
        if (!($this->multishop_context & Shop::get_context())) {
            if (Shop::get_context() == Shop::CONTEXT_SHOP && !($this->multishop_context & Shop::CONTEXT_SHOP)) {
                Shop::set_context(Shop::CONTEXT_GROUP, Shop::get_context_shop_group_id());
            }
            if (Shop::get_context() == Shop::CONTEXT_GROUP && !($this->multishop_context & Shop::CONTEXT_GROUP)) {
                Shop::set_context(Shop::CONTEXT_ALL);
            }
        }
        // Replace existing shop if necessary
        if (!$id_shop) {
            $this->context->shop = new Shop((int) Configuration::get('PS_SHOP_DEFAULT'));
        } elseif ($this->context->shop->id != $id_shop) {
            $this->context->shop = new Shop((int) $id_shop);
        }
        if ($this->context->shop->id_theme != $this->context->theme->id) {
            $this->context->theme = new Theme((int) $this->context->shop->id_theme);
        }
        // Replace current default country
        $this->context->country = new Country((int) Configuration::get('PS_COUNTRY_DEFAULT'));
    }
    /**
     * @return false|mixed
     *
     * @throws PrestaShopException
     */
    public function post_process()
    {
        try {
            if ($this->ajax) {
                // from ajax-tab.php
                $action = Tools::get_value('action');
                // no need to use displayConf() here
                if (!empty($action) && method_exists($this, 'ajaxProcess' . Tools::to_camel_case($action))) {
                    Hook::trigger_event('actionAdmin' . ucfirst($action) . 'Before', ['controller' => $this]);
                    Hook::trigger_event('action' . $this::class . ucfirst($action) . 'Before', ['controller' => $this]);
                    $return = $this->{'ajaxProcess' . Tools::to_camel_case($action)}();
                    Hook::trigger_event('actionAdmin' . ucfirst($action) . 'After', ['controller' => $this, 'return' => $return]);
                    Hook::trigger_event('action' . $this::class . ucfirst($action) . 'After', ['controller' => $this, 'return' => $return]);
                    return $return;
                }
                // no need to use displayConf() here
                if (!empty($action) && $this->controller_name == 'AdminModules' && Tools::get_isset('configure')) {
                    $module_obj = Module::get_instance_by_name(Tools::get_value('configure'));
                    if (Validate::is_loaded_object($module_obj) && method_exists($module_obj, 'ajaxProcess' . $action)) {
                        return $module_obj->{'ajaxProcess' . $action}();
                    }
                } elseif (method_exists($this, 'ajaxProcess')) {
                    return $this->ajax_process();
                }
            } else {
                // Process list filtering
                if ($this->filter && $this->action != 'reset_filters') {
                    $this->process_filter();
                }
                if (isset($_POST) && count($_POST) && Tools::get_int_value('submitFilter' . $this->list_id) || Tools::is_submit('submitReset' . $this->list_id)) {
                    $this->set_redirect_after(static::$current_index . '&token=' . $this->token . (Tools::is_submit('submitFilter' . $this->list_id) ? '&submitFilter' . $this->list_id . '=' . Tools::get_int_value('submitFilter' . $this->list_id) : '') . (isset($_GET['id_' . $this->list_id]) ? '&id_' . $this->list_id . '=' . (int) $_GET['id_' . $this->list_id] : ''));
                    if (!empty(Tools::get_value('id_' . $this->list_id . '_category'))) {
                        $this->set_redirect_after($this->redirect_after . '&id_' . $this->list_id . '_category=' . Tools::get_value('id_' . $this->list_id . '_category'));
                    }
                }
                // If the method named after the action exists, call "before" hooks, then call action method, then call "after" hooks
                if (!empty($this->action) && method_exists($this, 'process' . ucfirst(Tools::to_camel_case($this->action)))) {
                    // Hook before action
                    Hook::trigger_event('actionAdmin' . ucfirst($this->action) . 'Before', ['controller' => $this]);
                    Hook::trigger_event('action' . static::class . ucfirst($this->action) . 'Before', ['controller' => $this]);
                    // Call process
                    $return = $this->{'process' . Tools::to_camel_case($this->action)}();
                    // Hook After Action
                    Hook::trigger_event('actionAdmin' . ucfirst($this->action) . 'After', ['controller' => $this, 'return' => $return]);
                    Hook::trigger_event('action' . static::class . ucfirst($this->action) . 'After', ['controller' => $this, 'return' => $return]);
                    return $return;
                }
            }
        } catch (Throwable $e) {
            if ($this->post_process_handle_exceptions) {
                static::get_error_handler()->log_fatal_error(Error_Utils::describe_exception($e));
                $this->errors[] = $e->get_message();
            } else {
                if ($e instanceof Presta_Shop_Exception) {
                    throw $e;
                }
                throw new Presta_Shop_Exception($e->get_message(), 0, $e);
            }
        }
        return false;
    }
    /**
     * @throws PrestaShopException
     */
    public function process_filter(): void
    {
        Hook::trigger_event('action' . $this->controller_name . 'ListingFieldsModifier', ['fields' => &$this->fields_list]);
        $this->ensure_list_id_definition();
        $prefix = $this->get_cookie_filter_prefix();
        // Reset current filter, if forced filter was applied
        if (Tools::is_submit('submitFilterForced')) {
            $this->process_reset_filters();
            $_POST['submitFilter' . $this->list_id] = true;
        }
        if (isset($this->list_id)) {
            foreach ($_POST as $key => $value) {
                $value = $this->serialize_list_filter_value($value);
                if ($value === '') {
                    unset($this->context->cookie->{$prefix . $key});
                } elseif (stripos((string) $key, $this->list_id . 'Filter_') === 0) {
                    $this->context->cookie->{$prefix . $key} = $value;
                } elseif (stripos((string) $key, 'submitFilter') === 0) {
                    $this->context->cookie->{$key} = $value;
                }
            }
            foreach ($_GET as $key => $value) {
                // Handle forced filtering parameter by url
                if (stripos((string) $key, 'list_idFilter_') === 0) {
                    $key = preg_replace('/list_id/', $this->list_id, (string) $key, 1);
                }
                if (stripos((string) $key, $this->list_id . 'Filter_') === 0) {
                    $value = $this->serialize_list_filter_value($value);
                    if ($value === '') {
                        unset($this->context->cookie->{$prefix . $key});
                    } else {
                        $this->context->cookie->{$prefix . $key} = $value;
                    }
                } elseif (stripos((string) $key, 'submitFilter') === 0) {
                    $this->context->cookie->{$key} = $this->serialize_list_filter_value($value);
                }
                if (stripos((string) $key, $this->list_id . 'Orderby') === 0 && Validate::is_order_by($value)) {
                    if ($value === '' || $value == $this->_default_order_by) {
                        unset($this->context->cookie->{$prefix . $key});
                    } else {
                        $this->context->cookie->{$prefix . $key} = $value;
                    }
                } elseif (stripos((string) $key, $this->list_id . 'Orderway') === 0 && Validate::is_order_way($value)) {
                    if ($value === '' || $value == $this->_default_order_way) {
                        unset($this->context->cookie->{$prefix . $key});
                    } else {
                        $this->context->cookie->{$prefix . $key} = $value;
                    }
                }
            }
        }
        $filters = $this->context->cookie->get_family($prefix . $this->list_id . 'Filter_');
        $definition = false;
        if (isset($this->class_name) && $this->class_name) {
            $definition = Object_Model::get_definition($this->class_name);
        }
        foreach ($filters as $key => $value) {
            /* Extracting filters from $_POST on key filter_ */
            if ($value != null && !strncmp((string) $key, $prefix . $this->list_id . 'Filter_', 7 + mb_strlen($prefix . $this->list_id))) {
                $key = mb_substr((string) $key, 7 + mb_strlen($prefix . $this->list_id));
                /* Table alias could be specified using a ! eg. alias!field */
                $tmp_tab = explode('!', $key);
                $filter = count($tmp_tab) > 1 ? $tmp_tab[1] : $tmp_tab[0];
                if ($field = $this->filter_to_field($key, $filter)) {
                    $type = array_key_exists('filter_type', $field) ? $field['filter_type'] : (array_key_exists('type', $field) ? $field['type'] : false);
                    if (($type == 'date' || $type == 'datetime') && is_string($value)) {
                        $value = json_decode($value, true);
                    }
                    if (array_key_exists('filter_key', $field) && (string) $field['filter_key']) {
                        $tmp_tab = explode('!', (string) $field['filter_key']);
                    }
                    $key = isset($tmp_tab[1]) ? $tmp_tab[0] . '.`' . $tmp_tab[1] . '`' : '`' . $tmp_tab[0] . '`';
                    // Assignment by reference
                    if (array_key_exists('tmpTableFilter', $field)) {
                        $sql_filter =& $this->_tmp_table_filter;
                    } elseif (array_key_exists('havingFilter', $field)) {
                        $sql_filter =& $this->_filter_having;
                    } else {
                        $sql_filter =& $this->_filter;
                    }
                    /* Only for date filtering (from, to) */
                    if (is_array($value)) {
                        if (!empty($value[0])) {
                            if (!Validate::is_date($value[0])) {
                                $this->errors[] = Tools::display_error('The \'From\' date format is invalid (YYYY-MM-DD)');
                            } else {
                                $sql_filter .= ' AND ' . p_sql($key) . ' >= \'' . p_sql(Tools::date_from($value[0])) . '\'';
                            }
                        }
                        if (!empty($value[1])) {
                            if (!Validate::is_date($value[1])) {
                                $this->errors[] = Tools::display_error('The \'To\' date format is invalid (YYYY-MM-DD)');
                            } else {
                                $sql_filter .= ' AND ' . p_sql($key) . ' <= \'' . p_sql(Tools::date_to($value[1])) . '\'';
                            }
                        }
                    } else {
                        $sql_filter .= ' AND ';
                        $check_key = $key == $this->identifier || $key == '`' . $this->identifier . '`';
                        $alias = $definition && !empty($definition['fields'][$filter]['shop']) ? 'sa' : 'a';
                        if ($type == 'int' || $type == 'bool') {
                            $sql_filter .= ($check_key || $key == '`active`' ? $alias . '.' : '') . p_sql($key) . ' = ' . (int) $value . ' ';
                        } elseif ($type == 'decimal' || $type == 'price') {
                            $value = Tools::parse_number($value);
                            $sql_filter .= ($check_key ? $alias . '.' : '') . p_sql($key) . ' = ' . $value . ' ';
                        } elseif ($type == 'select') {
                            $sql_filter .= ($check_key ? $alias . '.' : '') . p_sql($key) . ' = \'' . p_sql($value) . '\' ';
                        } else {
                            $sql_filter .= ($check_key ? $alias . '.' : '') . p_sql($key) . ' LIKE \'%' . p_sql(trim((string) $value)) . '%\' ';
                        }
                    }
                }
            }
        }
    }
    /**
     * @return void
     */
    protected function ensure_list_id_definition()
    {
        if (!isset($this->list_id)) {
            $this->list_id = $this->table;
        }
    }
    /**
     * Return the type of authorization on permissions page and option.
     *
     * @return int(integer)
     */
    public function authorization_level()
    {
        if ($this->has_delete_permission()) {
            return Admin_Controller::LEVEL_DELETE;
        }
        if ($this->has_add_permission()) {
            return Admin_Controller::LEVEL_ADD;
        }
        if ($this->has_edit_permission()) {
            return Admin_Controller::LEVEL_EDIT;
        }
        if ($this->has_view_permission()) {
            return Admin_Controller::LEVEL_VIEW;
        }
        return 0;
    }
    /**
     * Set the filters used for the list display
     *
     * @return string
     */
    protected function get_cookie_filter_prefix()
    {
        return str_replace(['admin', 'controller'], '', mb_strtolower(static::class));
    }
    /**
     * @param string $key
     * @param string $filter
     *
     * @return array|false
     * @throws PrestaShopException
     */
    protected function filter_to_field($key, $filter)
    {
        if (!isset($this->fields_list)) {
            return false;
        }
        if (Shop::is_feature_active() && ($this->shop_link_type === 'shop' || $this->shop_link_type === 'shop_group')) {
            $shop_filter_key = 'shop!id_' . $this->shop_link_type;
            if ($key === $shop_filter_key) {
                return ['filter_type' => 'int'];
            }
        }
        foreach ($this->fields_list as $field) {
            if (array_key_exists('filter_key', $field) && $field['filter_key'] == $key) {
                return $field;
            }
        }
        if (array_key_exists($filter, $this->fields_list)) {
            return $this->fields_list[$filter];
        }
        return false;
    }
    /**
     * Object Delete images
     *
     * @return ObjectModel|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function process_delete_image()
    {
        if (Validate::is_loaded_object($object = $this->load_object())) {
            if (($input_name = Tools::get_value('inputName')) && !empty($this->field_image_settings)) {
                foreach ($this->field_image_settings as $field_image_setting) {
                    if ($field_image_setting['inputName'] == $input_name && !empty($field_image_setting['path'])) {
                        $object->image_dir = $field_image_setting['path'];
                        break;
                    }
                }
            }
            if ($object->delete_image()) {
                $redirect = static::$current_index . '&update' . $this->table . '&' . $this->identifier . '=' . Tools::get_value($this->identifier) . '&conf=7&token=' . $this->token;
                if (!$this->ajax) {
                    $this->redirect_after = $redirect;
                } else {
                    $this->content = 'ok';
                }
            }
        }
        return $object;
    }
    /**
     * Load class object using identifier in $_GET (if possible)
     * otherwise return an empty object, or die
     *
     * @param bool $opt Return an empty object if load fail
     *
     * @return ObjectModel|bool
     */
    protected function load_object($opt = false)
    {
        // return object that was already instantiated
        if ($this->object) {
            return $this->object;
        }
        if (empty($this->class_name)) {
            return true;
        }
        $id = Tools::get_int_value($this->identifier);
        if ($id && Validate::is_unsigned_id($id)) {
            $this->object = new $this->class_name($id);
            if (Validate::is_loaded_object($this->object)) {
                return $this->object;
            }
            // throw exception
            $this->errors[] = Tools::display_error('The object cannot be loaded (or found)');
            return false;
        }
        if ($opt) {
            $this->object = new $this->class_name();
            return $this->object;
        }
        $this->errors[] = Tools::display_error('The object cannot be loaded (the identifier is missing or invalid)');
        return false;
    }
    /**
     * @param string $textDelimiter
     *
     *
     * @throws PrestaShopException
     */
    public function process_export($text_delimiter = '"'): void
    {
        // clean buffer
        if (ob_get_level() && ob_get_length() > 0) {
            ob_clean();
        }
        $this->get_list($this->context->language->id, null, null, 0, false);
        if (!count($this->_list)) {
            return;
        }
        header('Content-type: text/csv');
        header('Content-Type: application/force-download; charset=UTF-8');
        header('Cache-Control: no-store, no-cache');
        header('Content-disposition: attachment; filename="' . $this->get_export_file_name() . '"');
        $headers = [];
        foreach ($this->fields_list as $key => $datas) {
            if ($datas['title'] === 'PDF') {
                unset($this->fields_list[$key]);
            } else if ($datas['title'] === 'ID') {
                $headers[] = strtolower(Tools::htmlentities_decode_utf8($datas['title']));
            } else {
                $headers[] = Tools::htmlentities_decode_utf8($datas['title']);
            }
        }
        $content = [];
        foreach ($this->_list as $i => $row) {
            $content[$i] = [];
            //            $pathToImage = false;
            foreach ($this->fields_list as $key => $params) {
                $field_value = isset($row[$key]) ? Tools::htmlentities_decode_utf8(Tools::nl2br($row[$key])) : '';
                if ($key == 'image') {
                    if ($params['image'] != 'p') {
                        $path_to_image = Tools::get_shop_domain(true) . _PS_IMG_ . $params['image'] . '/' . $row['id_' . $this->table] . (isset($row['id_image']) ? '-' . (int) $row['id_image'] : '') . '.' . $this->image_type;
                    } else {
                        $path_to_image = Tools::get_shop_domain(true) . _PS_IMG_ . $params['image'] . '/' . Image::get_img_folder_static($row['id_image']) . (int) $row['id_image'] . '.' . $this->image_type;
                    }
                    if ($path_to_image) {
                        $field_value = $path_to_image;
                    }
                }
                if (isset($params['callback_export'])) {
                    $callback = $params['callback_export'];
                    if (is_callable($callback)) {
                        $field_value = $callback($field_value, $row);
                    }
                } elseif (isset($params['callback'])) {
                    $callback_method = $params['callback'];
                    $callback_obj = $params['callback_object'] ?? $this;
                    $converted_value = (string) call_user_func_array([$callback_obj, $callback_method], [$field_value, $row]);
                    if (!preg_match('/<([a-z]+)([^<]+)*(?:>(.*)<\/\1>|\s+\/>)/ism', $converted_value)) {
                        $field_value = $converted_value;
                    }
                }
                $content[$i][] = $field_value;
            }
        }
        $field_delimiter = Configuration::get('TB_EXPORT_FIELD_DELIMITER') ?: ',';
        $this->context->smarty->assign(['export_precontent' => '', 'export_headers' => $headers, 'export_content' => $content, 'text_delimiter' => $text_delimiter, 'field_delimiter' => $field_delimiter]);
        $this->layout = 'layout-export.tpl';
    }
    protected function get_export_file_name(): string
    {
        return $this->table . '_' . date('Y-m-d_His') . '.csv';
    }
    /**
     * Get the current objects' list form the database
     *
     * @param int $idLang Language used for display
     * @param string|null $orderBy ORDER BY clause
     * @param string|null $orderWay Order way (ASC, DESC)
     * @param int $start Offset in LIMIT clause
     * @param int|false|null $limit Row count in LIMIT clause
     * @param int|bool $idLangShop
     *
     * @throws PrestaShopException
     */
    public function get_list($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false): void
    {
        $this->dispatch_fields_listing_modifier_event();
        $this->ensure_list_id_definition();
        /* Manage default params values */
        if ($limit === false) {
            $use_limit = false;
            $limit = 0;
        } else {
            $use_limit = true;
            $limit = Helper_List::resolve_pagination($this->list_id, $this->context->cookie, $this->_pagination, $this->_default_pagination);
            if ($limit !== $this->_default_pagination) {
                $this->context->cookie->{$this->list_id . '_pagination'} = $limit;
            } else {
                unset($this->context->cookie->{$this->list_id . '_pagination'});
            }
        }
        if (!Validate::is_table_or_identifier($this->table)) {
            throw new Presta_Shop_Exception(sprintf('Table name %s is invalid:', $this->table));
        }
        $order_by = $this->resolve_order_by($order_by);
        $order_way = $this->resolve_order_way($order_way);
        /* Check params validity */
        if (!Validate::is_order_by($order_by) || !Validate::is_order_way($order_way)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Invalid ordering parameters: orderBy=[%s] orderWay=[%s]'), $order_by, $order_way));
        }
        if (!isset($this->fields_list[$order_by]['order_key']) && isset($this->fields_list[$order_by]['filter_key'])) {
            $this->fields_list[$order_by]['order_key'] = $this->fields_list[$order_by]['filter_key'];
        }
        if (isset($this->fields_list[$order_by]['order_key'])) {
            $order_by = $this->fields_list[$order_by]['order_key'];
        }
        /* Determine offset from current page */
        $start = 0;
        if (Tools::get_int_value('submitFilter' . $this->list_id)) {
            $start = (Tools::get_int_value('submitFilter' . $this->list_id) - 1) * $limit;
        } elseif (isset($this->context->cookie->{$this->list_id . '_start'}) && Tools::is_submit('export' . $this->table)) {
            $start = $this->context->cookie->{$this->list_id . '_start'};
        }
        // Either save or reset the offset in the cookie
        if ($start) {
            $this->context->cookie->{$this->list_id . '_start'} = $start;
        } elseif (isset($this->context->cookie->{$this->list_id . '_start'})) {
            unset($this->context->cookie->{$this->list_id . '_start'});
        }
        $this->_order_by = $order_by;
        if (preg_match('/[.!]/', (string) $order_by)) {
            $order_by_split = preg_split('/[.!]/', (string) $order_by);
            $order_by = bq_sql($order_by_split[0]) . '.`' . bq_sql($order_by_split[1]) . '`';
        } elseif ($order_by) {
            $order_by = '`' . bq_sql($order_by) . '`';
        }
        $this->_order_way = mb_strtoupper((string) $order_way);
        /* SQL table : orders, but class name is Order */
        $sql_table = $this->table == 'order' ? 'orders' : $this->table;
        // Add SQL shop restriction
        $select_shop = $join_shop = $where_shop = '';
        if ($this->shop_link_type) {
            $select_shop = ', shop.name as shop_name ';
            $join_shop = ' LEFT JOIN ' . _DB_PREFIX_ . $this->shop_link_type . ' shop
							ON a.id_' . $this->shop_link_type . ' = shop.id_' . $this->shop_link_type;
            $where_shop = Shop::add_sql_restriction($this->shop_share_datas, 'a');
        }
        if ($this->multishop_context && Shop::is_table_associated($this->table) && !empty($this->class_name)) {
            if (Shop::get_context() != Shop::CONTEXT_ALL || !$this->context->employee->is_super_admin()) {
                $test_join = !preg_match('#`?' . preg_quote(_DB_PREFIX_ . $this->table . '_shop') . '`? *sa#', $this->_join ?? '');
                if (Shop::is_feature_active() && $test_join && Shop::is_table_associated($this->table)) {
                    $this->_where .= ' AND EXISTS (
						SELECT 1
						FROM `' . _DB_PREFIX_ . $this->table . '_shop` sa
						WHERE a.' . $this->identifier . ' = sa.' . $this->identifier . ' AND sa.id_shop IN (' . implode(', ', Shop::get_context_list_shop_id()) . ')
					)';
                }
            }
        }
        /* Query in order to get results with all fields */
        $lang_join = '';
        if ($this->lang) {
            $lang_join = 'LEFT JOIN `' . _DB_PREFIX_ . $this->table . '_lang` b ON (b.`' . $this->identifier . '` = a.`' . $this->identifier . '` AND b.`id_lang` = ' . (int) $id_lang;
            if ($id_lang_shop) {
                if (!Shop::is_feature_active()) {
                    $lang_join .= ' AND b.`id_shop` = ' . (int) Configuration::get('PS_SHOP_DEFAULT');
                } elseif (Shop::get_context() == Shop::CONTEXT_SHOP) {
                    $lang_join .= ' AND b.`id_shop` = ' . (int) $id_lang_shop;
                } else {
                    $lang_join .= ' AND b.`id_shop` = a.id_shop_default';
                }
            }
            $lang_join .= ')';
        }
        $having_clause = '';
        if (isset($this->_filter_having) || isset($this->_having)) {
            $having_clause = ' HAVING ';
            if (isset($this->_filter_having)) {
                $having_clause .= ltrim($this->_filter_having, ' AND ');
            }
            if (isset($this->_having)) {
                $having_clause .= $this->_having . ' ';
            }
        }
        do {
            $this->_listsql = '';
            if ($this->explicit_select) {
                foreach ($this->fields_list as $key => $array_value) {
                    // Add it only if it is not already in $this->_select
                    if (isset($this->_select) && preg_match('/[\s]`?' . preg_quote((string) $key, '/') . '`?\s*,/', $this->_select)) {
                        continue;
                    }
                    if (isset($array_value['filter_key'])) {
                        $this->_listsql .= str_replace('!', '.`', $array_value['filter_key']) . '` AS `' . $key . '`, ';
                    } elseif ($key == 'id_' . $this->table) {
                        $this->_listsql .= 'a.`' . bq_sql($key) . '`, ';
                    } elseif ($key != 'image' && !preg_match('/' . preg_quote((string) $key, '/') . '/i', $this->_select ?? '')) {
                        $this->_listsql .= '`' . bq_sql($key) . '`, ';
                    }
                }
                $this->_listsql = rtrim(trim($this->_listsql), ',');
            } else {
                $this->_listsql .= ($this->lang ? 'b.*,' : '') . ' a.*';
            }
            $this->_listsql .= '
			' . (isset($this->_select) ? ', ' . rtrim($this->_select, ', ') : '') . $select_shop;
            $sql_from = '
			FROM `' . _DB_PREFIX_ . $sql_table . '` a ';
            $sql_join = '
			' . $lang_join . '
			' . (isset($this->_join) ? $this->_join . ' ' : '') . '
			' . $join_shop;
            $sql_where = ' ' . (isset($this->_where) ? $this->_where . ' ' : '') . ($this->deleted ? 'AND a.`deleted` = 0 ' : '') . ($this->_filter ?? '') . $where_shop . '
			' . (isset($this->_group) ? $this->_group . ' ' : '') . '
			' . $having_clause;
            $sql_order_by = ' ORDER BY ' . (str_replace('`', '', $order_by) == $this->identifier ? 'a.' : '') . $order_by . ' ' . p_sql($order_way) . ($this->_tmp_table_filter ? ') tmpTable WHERE 1' . $this->_tmp_table_filter : '');
            $sql_limit = ' ' . ($use_limit === true ? ' LIMIT ' . (int) $start . ', ' . (int) $limit : '');
            if ($this->_use_found_rows || isset($this->_filter_having) || isset($this->_having)) {
                $this->_listsql = 'SELECT SQL_CALC_FOUND_ROWS
								' . ($this->_tmp_table_filter ? ' * FROM (SELECT ' : '') . $this->_listsql . $sql_from . $sql_join . ' WHERE 1 ' . $sql_where . $sql_order_by . $sql_limit;
                $list_count = 'SELECT FOUND_ROWS() AS `' . _DB_PREFIX_ . $this->table . '`';
            } else {
                $this->_listsql = 'SELECT
								' . ($this->_tmp_table_filter ? ' * FROM (SELECT ' : '') . $this->_listsql . $sql_from . $sql_join . ' WHERE 1 ' . $sql_where . $sql_order_by . $sql_limit;
                if ($this->_group) {
                    $list_count = 'SELECT COUNT(*) AS `' . _DB_PREFIX_ . $this->table . '` FROM (SELECT 1 ' . $sql_from . $sql_join . ' WHERE 1 ' . $sql_where . ') AS `inner`';
                } else {
                    $list_count = 'SELECT COUNT(*) AS `' . _DB_PREFIX_ . $this->table . '` ' . $sql_from . $sql_join . ' WHERE 1 ' . $sql_where;
                }
            }
            $conn = Db::read_only();
            try {
                $this->_list = $conn->get_array($this->_listsql);
                $this->_list_total = $conn->get_value($list_count);
            } catch (Presta_Shop_Database_Exception $e) {
                $description = Error_Utils::describe_exception($e);
                $error_handler = Service_Locator::get_instance()->get_error_handler();
                $error_handler->log_fatal_error($description);
                $this->_list_error = Tools::display_error('Invalid list SQL');
                if (_PS_MODE_DEV_) {
                    $this->_list_error .= ': ' . $description->get_message();
                }
                $this->_list = [];
                $this->_list_total = 0;
            }
            if ($use_limit === true) {
                $start = (int) $start - (int) $limit;
                if ($start < 0) {
                    break;
                }
            } else {
                break;
            }
        } while (empty($this->_list));
        Hook::trigger_event('action' . $this->controller_name . 'ListingResultsModifier', ['list' => &$this->_list, 'list_total' => &$this->_list_total]);
    }
    /**
     * @throws PrestaShopException
     */
    protected function dispatch_fields_listing_modifier_event()
    {
        if (!$this->list_fields_extended) {
            $this->list_fields_extended = true;
            Hook::trigger_event('action' . $this->controller_name . 'ListingFieldsModifier', ['select' => &$this->_select, 'join' => &$this->_join, 'where' => &$this->_where, 'group_by' => &$this->_group, 'order_by' => &$this->_order_by, 'order_way' => &$this->_order_way, 'fields' => &$this->fields_list]);
        }
    }
    /**
     * Object Delete
     *
     * @return ObjectModel|false
     * @throws PrestaShopException
     */
    public function process_delete()
    {
        if (Validate::is_loaded_object($object = $this->load_object())) {
            $res = true;
            //check if some ids are in list_skip_actions and forbid deletion
            if (array_key_exists('delete', $this->list_skip_actions) && in_array($object->id, $this->list_skip_actions['delete'])) {
                $this->errors[] = Tools::display_error('You cannot delete this item.');
            } else {
                if ($this->deleted) {
                    foreach ($this->field_image_settings as $field_image_setting) {
                        $object->image_dir = $field_image_setting['path'];
                        $res = $object->delete_image();
                    }
                    if (!$res) {
                        $this->errors[] = Tools::display_error('Unable to delete associated images.');
                    }
                    $object->deleted = 1;
                    if ($res = $object->update()) {
                        $this->redirect_after = static::$current_index . '&conf=1&token=' . $this->token;
                    }
                } elseif ($res = $object->delete()) {
                    $this->redirect_after = static::$current_index . '&conf=1&token=' . $this->token;
                } else {
                    $this->errors[] = Tools::display_error('An error occurred during deletion.');
                }
                if ($res) {
                    Logger::add_log(sprintf($this->l('%s deletion', 'AdminTab', false, false), $this->class_name), 1, null, $this->class_name, (int) $this->object->id, true, (int) $this->context->employee->id);
                }
            }
        } else {
            $this->errors[] = Tools::display_error('An error occurred while deleting the object.') . ' <b>' . $this->table . '</b> ' . Tools::display_error('(cannot load object)');
        }
        return $object;
    }
    /**
     * Call the right method for creating or updating object
     *
     * @return ObjectModel|false
     *
     * @throws PrestaShopException
     */
    public function process_save()
    {
        if ($this->id_object) {
            $this->load_object();
            return $this->process_update();
        }
        return $this->process_add();
    }
    /**
     * Object update
     *
     * @return ObjectModel|false
     * @throws PrestaShopException
     */
    public function process_update()
    {
        /* Checking fields validity */
        $this->validate_rules();
        if (empty($this->errors)) {
            $id = Tools::get_int_value($this->identifier);
            /* Object update */
            if ($id) {
                /** @var ObjectModel $object */
                $object = new $this->class_name($id);
                if (Validate::is_loaded_object($object)) {
                    /* Specific to objects which must not be deleted */
                    if ($this->deleted && $this->before_delete($object)) {
                        // Create new one with old objet values
                        /** @var ObjectModel $objectNew */
                        $object_new = $object->duplicate_object();
                        if (Validate::is_loaded_object($object_new)) {
                            // Update old object to deleted
                            $object->deleted = 1;
                            $object->update();
                            // Update new object with post values
                            $this->copy_from_post($object_new, $this->table);
                            $result = $object_new->update();
                            if (Validate::is_loaded_object($object_new)) {
                                $this->after_delete($object_new, $object->id);
                            }
                        }
                    } else {
                        $this->copy_from_post($object, $this->table);
                        $result = $object->update();
                        $this->after_update($object);
                    }
                    if ($object->id) {
                        $this->update_asso_shop($object->id);
                    }
                    if (!isset($result) || !$result) {
                        $this->errors[] = Tools::display_error('An error occurred while updating an object.') . ' <b>' . $this->table . '</b> (' . Db::get_instance()->get_msg_error() . ')';
                    } elseif ($this->post_image($object->id) && !count($this->errors) && $this->_redirect) {
                        $parent_id = Tools::get_int_value('id_parent', 1);
                        // Specific back redirect
                        if ($back = Tools::get_value('back')) {
                            $this->redirect_after = urldecode($back) . '&conf=4';
                        }
                        // Specific scene feature
                        // @todo change stay_here submit name (not clear for redirect to scene ... )
                        if (Tools::get_value('stay_here') == 'on' || Tools::get_value('stay_here') == 'true' || Tools::get_value('stay_here') == '1') {
                            $this->redirect_after = static::$current_index . '&' . $this->identifier . '=' . $object->id . '&conf=4&updatescene&token=' . $this->token;
                        }
                        // Save and stay on same form
                        // @todo on the to following if, we may prefer to avoid override redirect_after previous value
                        if (Tools::is_submit('submitAdd' . $this->table . 'AndStay')) {
                            $this->redirect_after = static::$current_index . '&' . $this->identifier . '=' . $object->id . '&conf=4&update' . $this->table . '&token=' . $this->token;
                        }
                        // Save and back to parent
                        if (Tools::is_submit('submitAdd' . $this->table . 'AndBackToParent')) {
                            $this->redirect_after = static::$current_index . '&' . $this->identifier . '=' . $parent_id . '&conf=4&token=' . $this->token;
                        }
                        // Default behavior (save and back)
                        if (empty($this->redirect_after) && $this->redirect_after !== false) {
                            $this->redirect_after = static::$current_index . ($parent_id ? '&' . $this->identifier . '=' . $object->id : '') . '&conf=4&token=' . $this->token;
                        }
                    }
                    Logger::add_log(sprintf($this->l('%s modification', 'AdminTab', false, false), $this->class_name), 1, null, $this->class_name, (int) $object->id, true, (int) $this->context->employee->id);
                } else {
                    $this->errors[] = Tools::display_error('An error occurred while updating an object.') . ' <b>' . $this->table . '</b> ' . Tools::display_error('(cannot load object)');
                }
            }
        }
        $this->errors = array_unique($this->errors);
        if (!empty($this->errors)) {
            // if we have errors, we stay on the form instead of going back to the list
            $this->display = 'edit';
            return false;
        }
        return $object ?? false;
    }
    /**
     * Manage page display (form, list...)
     *
     * @param string|bool $className Allow to validate a different class than the current one
     *
     * @throws PrestaShopException
     */
    public function validate_rules($class_name = false): void
    {
        if (!$class_name) {
            $class_name = $this->class_name;
        }
        /** @var ObjectModel $object */
        $object = new $class_name();
        if (method_exists($this, 'getValidationRules')) {
            $definition = $this->get_validation_rules();
        } else {
            $definition = Object_Model::get_definition($class_name);
        }
        $default_language = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $languages = Language::get_languages(false);
        foreach ($definition['fields'] as $field => $def) {
            $skip = [];
            if (in_array($field, ['passwd', 'no-picture'])) {
                $skip = ['required'];
            }
            if (isset($def['lang']) && $def['lang']) {
                if (isset($def['required']) && $def['required']) {
                    $value = Tools::get_value($field . '_' . $default_language->id);
                    if ($value === false || $value === '') {
                        $this->errors[$field . '_' . $default_language->id] = sprintf(Tools::display_error('The field %1$s is required at least in %2$s.'), $object->display_field_name($field, $class_name), $default_language->name);
                    }
                }
                foreach ($languages as $language) {
                    $value = Tools::get_value($field . '_' . $language['id_lang']);
                    if (!empty($value)) {
                        if (($error = $object->validate_field($field, $value, $language['id_lang'], $skip, true)) !== true) {
                            $this->errors[$field . '_' . $language['id_lang']] = $error;
                        }
                    }
                }
            } elseif (($error = $object->validate_field($field, Tools::get_value($field), null, $skip, true)) !== true) {
                $this->errors[$field] = $error;
            }
        }
        /* Overload this method for custom checking */
        $this->_child_validation();
    }
    /**
     * Overload this method for custom checking
     *
     * @return void
     */
    protected function _child_validation()
    {
    }
    /**
     * Called before deletion
     *
     * @param ObjectModel $object Object
     *
     * @return bool
     */
    protected function before_delete($object)
    {
        return false;
    }
    /**
     * Copy data values from $_POST to object
     *
     * @param ObjectModel &$object Object
     * @param string $table Object table
     *
     * @throws PrestaShopException
     */
    protected function copy_from_post(&$object, $table)
    {
        /* Classical fields */
        foreach ($_POST as $key => $value) {
            if (property_exists($object, $key) && $key != 'id_' . $table) {
                /* Do not take care of password field if empty */
                if ($key == 'passwd' && Tools::get_value('id_' . $table) && empty($value)) {
                    continue;
                }
                /* Automatically hash password */
                if ($key == 'passwd' && !empty($value)) {
                    $value = Tools::hash($value);
                }
                if ($key === 'email') {
                    if (mb_detect_encoding($value, 'UTF-8', true) && mb_strpos($value, '@') > -1) {
                        // Convert to IDN
                        [$local, $domain] = explode('@', $value, 2);
                        $domain = Tools::utf8to_idn($domain);
                        $value = "{$local}@{$domain}";
                    }
                }
                $object->{$key} = $value;
            }
        }
        /* Multilingual fields */
        $fields = [];
        if ($object instanceof Object_Model) {
            $definition = Object_Model::get_definition($object);
            if (isset($definition['fields'])) {
                $fields = $definition['fields'];
            }
        }
        foreach ($fields as $field => $params) {
            if (array_key_exists('lang', $params) && $params['lang']) {
                foreach (Language::get_i_ds(false) as $id_lang) {
                    if (Tools::is_submit($field . '_' . (int) $id_lang)) {
                        if (!isset($object->{$field}) || !is_array($object->{$field})) {
                            $object->{$field} = [];
                        }
                        $object->{$field}[(int) $id_lang] = Tools::get_value($field . '_' . (int) $id_lang);
                    }
                }
            }
        }
    }
    /**
     * Called before deletion
     *
     * @param ObjectModel $object Object
     * @param int $oldId
     */
    protected function after_delete($object, $old_id)
    {
    }
    /**
     * @param ObjectModel $object
     */
    protected function after_update($object)
    {
    }
    /**
     * Update the associations of shops
     *
     * @param int $idObject
     *
     * @return bool|void
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    protected function update_asso_shop($id_object)
    {
        if (!Shop::is_feature_active()) {
            return;
        }
        if (!Shop::is_table_associated($this->table)) {
            return;
        }
        $assos_data = $this->get_selected_asso_shop($this->table);
        // Get list of shop id we want to exclude from asso deletion
        $exclude_ids = $assos_data;
        $conn = Db::get_instance();
        foreach ($conn->get_array('SELECT id_shop FROM ' . _DB_PREFIX_ . 'shop') as $row) {
            if (!$this->context->employee->has_auth_on_shop($row['id_shop'])) {
                $exclude_ids[] = $row['id_shop'];
            }
        }
        $conn->delete($this->table . '_shop', '`' . bq_sql($this->identifier) . '` = ' . (int) $id_object . ($exclude_ids ? ' AND id_shop NOT IN (' . implode(', ', array_map(intval(...), $exclude_ids)) . ')' : ''));
        $insert = [];
        foreach ($assos_data as $id_shop) {
            $insert[] = [$this->identifier => (int) $id_object, 'id_shop' => (int) $id_shop];
        }
        return $conn->insert($this->table . '_shop', $insert, false, true, Db::INSERT_IGNORE);
    }
    /**
     * Returns an array with selected shops and type (group or boutique shop)
     *
     * @param string $table
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function get_selected_asso_shop($table)
    {
        if (!Shop::is_feature_active() || !Shop::is_table_associated($table)) {
            return [];
        }
        $shops = Shop::get_shops(true, null, true);
        if (count($shops) == 1 && isset($shops[0])) {
            return [$shops[0], 'shop'];
        }
        $assos = [];
        if (Tools::is_submit('checkBoxShopAsso_' . $table)) {
            foreach (Tools::get_array_value('checkBoxShopAsso_' . $table) as $id_shop => $value) {
                $assos[] = (int) $id_shop;
            }
        } elseif (Shop::get_total_shops(false) == 1) {
            // if we do not have the checkBox multishop, we can have an admin with only one shop and being in multishop
            $assos[] = (int) Shop::get_context_shop_id();
        }
        return $assos;
    }
    /**
     * Gathering ObjectModel data and setting $fieldImageSettings as a multidimensional array
     */
    protected function clean_field_image_settings()
    {
        // Make sure, that fieldImageSettings is a multidimensional array
        if (isset($this->field_image_settings['name']) && isset($this->field_image_settings['dir'])) {
            $this->field_image_settings = [$this->field_image_settings];
        }
        if (empty($this->field_image_settings) && $this->class_name && class_exists($this->class_name)) {
            try {
                $definition = Object_Model::get_definition($this->class_name);
                if (!empty($definition['images'])) {
                    $this->field_image_settings = $definition['images'];
                }
            } catch (Presta_Shop_Exception) {
            }
        }
        foreach ($this->field_image_settings as &$field_image_setting) {
            // Set inputName
            if (!isset($field_image_setting['inputName']) && isset($field_image_setting['name'])) {
                $field_image_setting['inputName'] = $field_image_setting['name'];
            }
            // Set path
            if (!isset($field_image_setting['path']) && isset($field_image_setting['dir'])) {
                $field_image_setting['path'] = $field_image_setting['dir'];
            }
            // Check if there is a full path
            if (!str_contains((string) $field_image_setting['path'], _PS_CORE_DIR_)) {
                $field_image_setting['path'] = _PS_IMG_DIR_ . $field_image_setting['path'];
            }
            $field_image_setting['path'] = rtrim((string) $field_image_setting['path'], '/') . '/';
            // Make sure to end with a /
        }
    }
    /**
     * Overload this method for custom checking
     *
     * @param int $id Object id used for deleting images
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function post_image($id)
    {
        foreach ($this->field_image_settings as $image_entity_name => $field_image_setting) {
            if (isset($field_image_setting['inputName']) && isset($field_image_setting['path'])) {
                $image_extension = $field_image_setting['imageExtension'] ?? false;
                $image_types = $image_entity_name ? Image_Type::get_images_types($image_entity_name) : [];
                $width = $field_image_setting['width'] ?? null;
                $height = $field_image_setting['height'] ?? null;
                $this->upload_image($id, $field_image_setting['inputName'], $field_image_setting['path'], $image_extension, $width, $height, $image_types);
            }
        }
        return !count($this->errors);
    }
    /**
     * @param int $id
     * @param string $name
     * @param string $path
     * @param string|bool $imageExtension
     * @param int|null $width
     * @param int|null $height
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function upload_image($id, $name, $path, $image_extension = false, $width = null, $height = null, $generate_image_types = [])
    {
        if (!empty($_FILES[$name]['tmp_name'])) {
            // Delete old image
            if (Validate::is_loaded_object($object = $this->load_object())) {
                // A few times we use strings for filename instead of int -> we shouldn't delete in this case (needed for AdminLanguagesController)
                if (Validate::is_int($id)) {
                    $object->image_dir = $path;
                    $object->delete_image();
                }
            } else {
                return false;
            }
            // Check image validity
            $max_size = (int) $this->max_image_size;
            if ($error = Image_Manager::validate_upload($_FILES[$name], Tools::get_max_upload_size($max_size))) {
                $this->errors[] = $error;
            }
            $tmp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS');
            if (!$tmp_name) {
                return false;
            }
            if (!move_uploaded_file($_FILES[$name]['tmp_name'], $tmp_name)) {
                return false;
            }
            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            if (!Image_Manager::check_image_memory_limit($tmp_name)) {
                $this->errors[] = Tools::display_error('Due to memory limit restrictions, this image cannot be loaded. Please increase your memory_limit value via your server\'s configuration settings. ');
            }
            // Check if the dir path exits (otherwise create it)
            if (!file_exists($path)) {
                // Apparently sometimes mkdir cannot set the rights, and sometimes chmod can't. Trying both.
                $success = @mkdir($path, 0775, true);
                $chmod = @chmod($path, 0775);
                // Create an index.php file in the new folder
                if (($success || $chmod) && !file_exists($path . 'index.php') && file_exists(_PS_IMG_DIR_ . 'index.php')) {
                    @copy(_PS_IMG_DIR_ . 'index.php', $path . 'index.php');
                }
            }
            // Copy new image
            if (!$image_extension) {
                $image_extension = $this->image_type;
            }
            if (empty($this->errors)) {
                // Some controller (example: AdminGenders) use fixed sizes for uploads
                if ($width && $height) {
                    $success = Image_Manager::resize($tmp_name, $path . $id . '.' . $image_extension, $width, $height, $image_extension);
                } else {
                    $success = Image_Manager::convert_image_to_extension($tmp_name, $image_extension, $path . $id . '.' . $image_extension);
                }
                if (!$success) {
                    $this->errors[] = Tools::display_error('An error occurred while uploading the image.');
                }
            }
            if (empty($this->errors) && !empty($generate_image_types)) {
                foreach ($generate_image_types as $image_type) {
                    Image_Manager::resize($path . $id . '.' . $image_extension, $path . $id . '-' . $image_type['name'] . '.' . $image_extension, $image_type['width'], $image_type['height'], $image_extension);
                    if (Image_Manager::retina_support()) {
                        Image_Manager::resize($path . $id . '.' . $image_extension, $path . $id . '-' . $image_type['name'] . '2x.' . $image_extension, $image_type['width'] * 2, $image_type['height'] * 2, $image_extension);
                    }
                }
            }
            if (count($this->errors)) {
                return false;
            }
            if ($this->after_image_upload()) {
                unlink($tmp_name);
                return true;
            }
            return false;
        }
        if (!empty($_FILES[$name]['name'])) {
            $this->errors[] = $this->l('Image upload failed!');
        }
        return true;
    }
    /**
     * Check rights to view the current tab
     *
     * @return bool
     */
    protected function after_image_upload()
    {
        return true;
    }
    /**
     * Object creation
     *
     * @return ObjectModel|false
     * @throws PrestaShopException
     */
    public function process_add()
    {
        if (empty($this->class_name)) {
            return false;
        }
        $this->validate_rules();
        if (count($this->errors) <= 0) {
            $this->object = new $this->class_name();
            $this->copy_from_post($this->object, $this->table);
            $this->before_add($this->object);
            if (method_exists($this->object, 'add') && !$this->object->add()) {
                $this->errors[] = Tools::display_error('An error occurred while creating an object.') . ' <strong>' . $this->table . ' (' . Db::get_instance()->get_msg_error() . ')</strong>';
            } elseif (($_POST[$this->identifier] = $this->object->id) && $this->post_image($this->object->id) && !count($this->errors) && $this->_redirect) {
                Logger::add_log(sprintf($this->l('%s addition', 'AdminTab', false, false), $this->class_name), 1, null, $this->class_name, (int) $this->object->id, true, (int) $this->context->employee->id);
                $parent_id = Tools::get_int_value('id_parent', 1);
                $this->after_add($this->object);
                $this->update_asso_shop($this->object->id);
                // Save and stay on same form
                if (empty($this->redirect_after) && $this->redirect_after !== false && Tools::is_submit('submitAdd' . $this->table . 'AndStay')) {
                    $this->redirect_after = static::$current_index . '&' . $this->identifier . '=' . $this->object->id . '&conf=3&update' . $this->table . '&token=' . $this->token;
                }
                // Save and back to parent
                if (empty($this->redirect_after) && $this->redirect_after !== false && Tools::is_submit('submitAdd' . $this->table . 'AndBackToParent')) {
                    $this->redirect_after = static::$current_index . '&' . $this->identifier . '=' . $parent_id . '&conf=3&token=' . $this->token;
                }
                // Default behavior (save and back)
                if (empty($this->redirect_after) && $this->redirect_after !== false) {
                    // Specific back redirect
                    if ($back = Tools::get_value('back')) {
                        $this->redirect_after = urldecode($back) . '&conf=3';
                    } else {
                        $this->redirect_after = static::$current_index . ($parent_id ? '&' . $this->identifier . '=' . $this->object->id : '') . '&conf=3&token=' . $this->token;
                    }
                }
            }
        }
        $this->errors = array_unique($this->errors);
        if (!empty($this->errors)) {
            // if we have errors, we stay on the form instead of going back to the list
            $this->display = 'edit';
            return false;
        }
        return $this->object;
    }
    /**
     * Called before Add
     *
     * @param ObjectModel $object Object
     *
     * @return void
     */
    protected function before_add($object)
    {
    }
    /**
     * @param ObjectModel $object
     *
     * @return void
     */
    protected function after_add($object)
    {
    }
    /**
     * Change object required fields
     *
     * @return ObjectModel
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function process_update_fields()
    {
        $fields = Tools::get_array_value('fieldsBox');
        /** @var ObjectModel $object */
        $object = new $this->class_name();
        if (!$object->add_fields_required_database($fields)) {
            $this->errors[] = Tools::display_error('An error occurred when attempting to update the required fields.');
        } else {
            $this->redirect_after = static::$current_index . '&conf=4&token=' . $this->token;
        }
        return $object;
    }
    /**
     * Change object status (active, inactive)
     *
     * @return ObjectModel|false
     * @throws PrestaShopException
     */
    public function process_status()
    {
        /** @var ObjectModel $object */
        if (Validate::is_loaded_object($object = $this->load_object())) {
            if (property_exists($object, 'active') && $object->toggle_status()) {
                Logger::add_log(sprintf($this->l('%s status switched to %s', 'AdminTab', false, false), $this->class_name, $object->active ? 'enable' : 'disable'), 1, null, $this->class_name, (int) $object->id, true, (int) $this->context->employee->id);
                $matches = [];
                $referer = Tools::get_http_referer();
                if (preg_match('/[\?|&]controller=([^&]*)/', $referer, $matches) !== false && strtolower($matches[1]) != strtolower((string) preg_replace('/controller/i', '', static::class))) {
                    $this->redirect_after = preg_replace('/[\?|&]conf=([^&]*)/i', '', $referer);
                } else {
                    $this->redirect_after = static::$current_index . '&token=' . $this->token;
                }
                $id_category = ($id_category = Tools::get_int_value('id_category')) && Tools::get_int_value('id_product') ? '&id_category=' . $id_category : '';
                $page = Tools::get_int_value('page');
                $page = $page > 1 ? '&submitFilter' . $this->table . '=' . (int) $page : '';
                $this->redirect_after .= '&conf=5' . $id_category . $page;
            } else {
                $this->errors[] = Tools::display_error('An error occurred while updating the status.');
            }
        } else {
            $this->errors[] = Tools::display_error('An error occurred while updating the status for an object.') . ' <b>' . $this->table . '</b> ' . Tools::display_error('(cannot load object)');
        }
        return $object;
    }
    /**
     * Change object position
     *
     * @return ObjectModel|false
     */
    public function process_position()
    {
        if (!Validate::is_loaded_object($object = $this->load_object())) {
            $this->errors[] = Tools::display_error('An error occurred while updating the status for an object.') . ' <b>' . $this->table . '</b> ' . Tools::display_error('(cannot load object)');
        } elseif (!$object->update_position(Tools::get_int_value('way'), Tools::get_int_value('position'))) {
            $this->errors[] = Tools::display_error('Failed to update the position.');
        } else {
            $id_identifier_str = ($id_identifier = Tools::get_int_value($this->identifier)) ? '&' . $this->identifier . '=' . $id_identifier : '';
            $redirect = static::$current_index . '&' . $this->table . 'Orderby=position&' . $this->table . 'Orderway=asc&conf=5' . $id_identifier_str . '&token=' . $this->token;
            $this->redirect_after = $redirect;
        }
        return $object;
    }
    /**
     * Cancel all filters for this tab
     *
     * @param int|null $listId
     */
    public function process_reset_filters($list_id = null): void
    {
        if ($list_id === null) {
            $list_id = $this->list_id ?? $this->table;
        }
        $prefix = $this->get_cookie_filter_prefix();
        $filters = $this->context->cookie->get_family($prefix . $list_id . 'Filter_');
        foreach ($filters as $cookie_key => $filter) {
            if (strncmp((string) $cookie_key, $prefix . $list_id . 'Filter_', 7 + mb_strlen($prefix . $list_id)) == 0) {
                $key = substr((string) $cookie_key, 7 + mb_strlen($prefix . $list_id));
                if (is_array($this->fields_list) && array_key_exists($key, $this->fields_list)) {
                    $this->context->cookie->{$cookie_key} = null;
                }
                unset($this->context->cookie->{$cookie_key});
            }
        }
        if (isset($this->context->cookie->{'submitFilter' . $list_id})) {
            unset($this->context->cookie->{'submitFilter' . $list_id});
        }
        if (isset($this->context->cookie->{$prefix . $list_id . 'Orderby'})) {
            unset($this->context->cookie->{$prefix . $list_id . 'Orderby'});
        }
        if (isset($this->context->cookie->{$prefix . $list_id . 'Orderway'})) {
            unset($this->context->cookie->{$prefix . $list_id . 'Orderway'});
        }
        $_POST = [];
        $this->_filter = false;
        unset($this->_filter_having);
        unset($this->_having);
    }
    /**
     * Check if the token is valid, else display a warning page
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function check_access()
    {
        if (!$this->check_token()) {
            // If this is an XSS attempt, then we should only display a simple, secure page
            // ${1} in the replacement string of the regexp is required,
            // because the token may begin with a number and mix up with it (e.g. $17)
            $url = preg_replace('/([&?]token=)[^&]*(&.*)?$/', '${1}' . $this->token . '$2', (string) $_SERVER['REQUEST_URI']);
            if (!str_contains((string) $url, '?token=') && !str_contains((string) $url, '&token=')) {
                $url .= '&token=' . $this->token;
            }
            if (!str_contains($url, '?')) {
                $url = str_replace('&token', '?controller=AdminDashboard&token', $url);
            }
            $this->context->smarty->assign('url', htmlentities($url));
            return false;
        }
        return true;
    }
    /**
     * Check for security token
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function check_token()
    {
        // if token is provided it must match the expected token
        $token = Tools::get_value('token');
        if ($token) {
            return hash_equals((string) $this->token, (string) $token);
        }
        // token was not provided. It is required, if security was explicitly strengthened
        $force_token = (bool) Configuration::get_global_value(Configuration::BO_FORCE_TOKEN);
        if ($force_token) {
            return false;
        }
        // if there are any POST parameters, token is required
        if (count($_POST)) {
            return false;
        }
        // if there are any GET parameters, token is required as well
        foreach ($_GET as $key => $value) {
            if (!in_array($key, ['controller', 'controllerUri'])) {
                return false;
            }
            if ($key === 'controller' && !Validate::is_controller_name($value)) {
                return false;
            }
        }
        // for backwards compatibility reasons
        return true;
    }
    /**
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function display_ajax(): void
    {
        if ($this->json) {
            $this->context->smarty->assign(['json' => true, 'status' => $this->status]);
        }
        $this->layout = 'layout-ajax.tpl';
        $this->display_header = false;
        $this->display_header_javascript = false;
        $this->display_footer = false;
        $this->display();
    }
    /**
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function display(): void
    {
        $supporter_info = Configuration::get_supporter_info();
        $this->context->smarty->assign(['supporterInfo' => $supporter_info, 'campaingClass' => $this->get_campaign_classes($supporter_info), 'display_header' => $this->display_header, 'display_header_javascript' => $this->display_header_javascript, 'display_footer' => $this->display_footer, 'js_def' => Media::get_js_def()]);
        // Use page title from meta_title if it has been set else from the breadcrumbs array
        if (!$this->meta_title) {
            $this->meta_title = $this->toolbar_title;
        }
        if (is_array($this->meta_title)) {
            $this->meta_title = strip_tags(implode(' ' . Configuration::get('PS_NAVIGATION_PIPE') . ' ', $this->meta_title));
        }
        $this->context->smarty->assign('meta_title', $this->meta_title);
        $template_dirs = $this->context->smarty->get_template_dir();
        // Check if header/footer have been overriden
        $dir = $this->context->smarty->get_template_dir(0) . 'controllers' . DIRECTORY_SEPARATOR . trim($this->override_folder, '\/') . DIRECTORY_SEPARATOR;
        $header_tpl = file_exists($dir . 'header.tpl') ? $dir . 'header.tpl' : 'header.tpl';
        $page_header_toolbar = file_exists($dir . 'page_header_toolbar.tpl') ? $dir . 'page_header_toolbar.tpl' : 'page_header_toolbar.tpl';
        $footer_tpl = file_exists($dir . 'footer.tpl') ? $dir . 'footer.tpl' : 'footer.tpl';
        $tpl_action = $this->tpl_folder . $this->display . '.tpl';
        // Check if action template has been overriden
        foreach ($template_dirs as $template_dir) {
            if (file_exists($template_dir . DIRECTORY_SEPARATOR . $tpl_action) && $this->display != 'view' && $this->display != 'options') {
                if (method_exists($this, $this->display . Tools::to_camel_case($this->class_name))) {
                    $this->{$this->display . Tools::to_camel_case($this->class_name)}();
                }
                $this->context->smarty->assign('content', $this->context->smarty->fetch($tpl_action));
                break;
            }
        }
        if (!$this->ajax) {
            $template = $this->create_template($this->template);
            $page = $template->fetch();
        } else {
            $page = $this->content;
        }
        if ($conf = Tools::get_value('conf')) {
            $this->context->smarty->assign('conf', $this->json ? json_encode($this->_conf[(int) $conf]) : $this->_conf[(int) $conf]);
        }
        foreach (['errors', 'warnings', 'informations', 'confirmations'] as $type) {
            if (!is_array($this->{$type})) {
                $this->{$type} = (array) $this->{$type};
            }
            $this->context->smarty->assign($type, $this->json ? json_encode(array_unique($this->{$type})) : array_unique($this->{$type}));
        }
        if ($this->show_page_header_toolbar && !$this->lite_display) {
            $this->context->smarty->assign(['page_header_toolbar' => $this->context->smarty->fetch($page_header_toolbar)]);
        }
        $messages = static::get_error_messages();
        if ($messages) {
            $this->context->smarty->assign('php_errors', $messages);
        }
        $this->context->smarty->assign(['page' => $this->json ? json_encode($page) : $page, 'header' => $this->context->smarty->fetch($header_tpl), 'footer' => $this->context->smarty->fetch($footer_tpl)]);
        $this->smarty_output_content($this->layout);
    }
    /**
     * Create a template from the override file, else from the base file.
     *
     * @param string $tplName filename
     *
     * @return Smarty_Internal_Template
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function create_template($tpl_name)
    {
        $smarty = $this->context->smarty;
        $template_dir = $smarty->get_template_dir(0);
        if ($this->view_access()) {
            if ($this->override_folder) {
                // Use override tpl if it exists
                $override_template_dir = $smarty->get_template_dir(1);
                if (!Configuration::get('PS_DISABLE_OVERRIDES') && file_exists($override_template_dir . DIRECTORY_SEPARATOR . $this->override_folder . $tpl_name)) {
                    return $smarty->create_template($this->override_folder . $tpl_name, $smarty);
                }
                if (file_exists($template_dir . 'controllers' . DIRECTORY_SEPARATOR . $this->override_folder . $tpl_name)) {
                    return $smarty->create_template('controllers' . DIRECTORY_SEPARATOR . $this->override_folder . $tpl_name, $smarty);
                }
            }
            return $smarty->create_template($template_dir . $tpl_name, $smarty);
        }
        // If view access is denied, we want to use the default template that will be used to display an error
        return $smarty->create_template($template_dir . static::DEFAULT_VIEW_TEMPLATE, $smarty);
    }
    /**
     * Check rights to view the current tab
     *
     * @param bool $disable
     *
     * @return bool
     */
    public function view_access($disable = false)
    {
        if ($disable) {
            return true;
        }
        return $this->has_view_permission();
    }
    /**
     * Assign smarty variables for the header
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function init_header(): void
    {
        header('Cache-Control: no-store, no-cache');
        // Multishop
        $is_multishop = Shop::is_feature_active();
        // Quick access
        if ((int) $this->context->employee->id) {
            $quick_access = Quick_Access::get_quick_accesses($this->context->language->id);
            foreach ($quick_access as $index => $quick) {
                if ($quick['link'] == '../' && Shop::get_context() == Shop::CONTEXT_SHOP) {
                    $url = $this->context->shop->get_base_url();
                    if (!$url) {
                        unset($quick_access[$index]);
                        continue;
                    }
                    $quick_access[$index]['link'] = $url;
                } else {
                    preg_match('/controller=(.+)(&.+)?$/', (string) $quick['link'], $admin_tab);
                    if (isset($admin_tab[1])) {
                        if (strpos($admin_tab[1], '&')) {
                            $admin_tab[1] = substr($admin_tab[1], 0, strpos($admin_tab[1], '&'));
                        }
                        $token = Tools::get_admin_token($admin_tab[1] . (int) Tab::get_id_from_class_name($admin_tab[1]) . (int) $this->context->employee->id);
                        $quick_access[$index]['target'] = $admin_tab[1];
                        $quick_access[$index]['link'] .= '&token=' . $token;
                    }
                }
            }
        }
        // Tab list
        $tabs = Tab::get_tabs($this->context->language->id, 0);
        $current_id = Tab::get_current_parent_id();
        foreach ($tabs as $index => $tab) {
            if (!Tab::check_tab_rights($tab['id_tab']) || $tab['class_name'] == 'AdminStock' && Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') == 0 || $tab['class_name'] == 'AdminCarrierWizard') {
                unset($tabs[$index]);
                continue;
            }
            $tabs[$index]['current'] = $tab['class_name'] . 'Controller' == static::class || $current_id == $tab['id_tab'];
            $tabs[$index]['href'] = $this->context->link->get_admin_link($tab['class_name']);
            $sub_tabs = Tab::get_tabs($this->context->language->id, $tab['id_tab']);
            foreach ($sub_tabs as $index2 => $sub_tab) {
                //check if module is enable and
                if (!empty($sub_tab['module'])) {
                    $module_id = Module::get_module_id_by_name($sub_tab['module']);
                    if (!$module_id || !Module::is_enabled_for_shops($module_id, Shop::get_context_list_shop_id())) {
                        unset($sub_tabs[$index2]);
                        continue;
                    }
                }
                // class_name is the name of the class controller
                if (Tab::check_tab_rights($sub_tab['id_tab']) === true && $sub_tab['active'] && $sub_tab['class_name'] != 'AdminCarrierWizard') {
                    $sub_tabs[$index2]['href'] = $this->context->link->get_admin_link($sub_tab['class_name']);
                    $sub_tabs[$index2]['current'] = $sub_tab['class_name'] . 'Controller' == static::class || $sub_tab['class_name'] == Tools::get_value('controller');
                } elseif ($sub_tab['class_name'] == 'AdminCarrierWizard' && $sub_tab['class_name'] . 'Controller' == static::class) {
                    foreach ($sub_tabs as $i => $tab) {
                        if ($tab['class_name'] == 'AdminCarriers') {
                            break;
                        }
                    }
                    $sub_tabs[$i]['current'] = true;
                    unset($sub_tabs[$index2]);
                } else {
                    unset($sub_tabs[$index2]);
                }
            }
            $tabs[$index]['sub_tabs'] = array_values($sub_tabs);
        }
        if (Validate::is_loaded_object($this->context->employee)) {
            $notification = $this->context->employee->get_notification();
            $helper_shop = new Helper_Shop();
            /* Hooks are voluntary out the initialize array (need those variables already assigned) */
            $bo_color = empty($this->context->employee->bo_color) ? '#FFFFFF' : $this->context->employee->bo_color;
            $this->context->smarty->assign(['autorefresh_notifications' => false, 'notificationTypes' => $notification->get_types(), 'help_box' => Configuration::get('PS_HELPBOX'), 'round_mode' => Configuration::get('PS_PRICE_ROUND_MODE'), 'brightness' => Tools::get_brightness($bo_color) < 128 ? 'white' : '#383838', 'bo_width' => (int) $this->context->employee->bo_width, 'bo_color' => isset($this->context->employee->bo_color) ? Tools::htmlentities_utf8($this->context->employee->bo_color) : null, 'employee' => $this->context->employee, 'search_type' => Tools::get_value('bo_search_type'), 'bo_query' => Tools::safe_output(Tools::get_value('bo_query')), 'quick_access' => $quick_access, 'multi_shop' => Shop::is_feature_active(), 'shop_list' => $helper_shop->get_rendered_shop_list(), 'shop' => $this->context->shop, 'shop_group' => new Shop_Group((int) Shop::get_context_shop_group_id()), 'is_multishop' => $is_multishop, 'multishop_context' => $this->multishop_context, 'default_tab_link' => $this->context->link->get_admin_link(Tab::get_class_name_by_id((int) $this->context->employee->default_tab)), 'login_link' => $this->context->link->get_admin_link('AdminLogin'), 'collapse_menu' => isset($this->context->cookie->collapse_menu) ? (int) $this->context->cookie->collapse_menu : 0]);
        } else {
            $this->context->smarty->assign('default_tab_link', $this->context->link->get_admin_link('AdminDashboard'));
        }
        // Shop::initialize() in config.php may empty $this->context->shop->virtual_uri so using a new shop instance for getBaseUrl()
        $this->context->shop = new Shop((int) $this->context->shop->id);
        $shop_context = match (Shop::get_context()) {
            Shop::CONTEXT_ALL => 'all',
            Shop::CONTEXT_GROUP => 'group-' . Shop::get_context_shop_group_id(false),
            default => 'shop-' . Shop::get_context_shop_id(false),
        };
        $this->context->smarty->assign([
            'img_dir' => _PS_IMG_,
            'iso' => $this->context->language->iso_code,
            'class_name' => $this->class_name,
            'iso_user' => $this->context->language->iso_code,
            'country_iso_code' => $this->context->country->iso_code,
            'version' => _TB_VERSION_,
            'lang_iso' => $this->context->language->iso_code,
            'full_language_code' => $this->context->language->language_code,
            'link' => $this->context->link,
            'shop_name' => Configuration::get('PS_SHOP_NAME'),
            'base_url' => $this->context->shop->get_base_url(),
            'tab' => $tab ?? null,
            // Deprecated, this tab is declared in the foreach, so it's the last tab in the foreach
            'current_parent_id' => (int) Tab::get_current_parent_id(),
            'tabs' => $tabs,
            'install_dir_exists' => file_exists(_PS_ADMIN_DIR_ . '/../install'),
            'pic_dir' => _THEME_PROD_PIC_DIR_,
            'controller_name' => htmlentities(Tools::get_value('controller')),
            'currentIndex' => static::$current_index,
            'maintenance_mode' => !Configuration::get('PS_SHOP_ENABLE'),
            'bootstrap' => $this->bootstrap,
            'default_language' => (int) Configuration::get('PS_LANG_DEFAULT'),
            'shopContext' => $shop_context,
        ]);
        /** @var ThemeConfigurator|false $module */
        $module = Module::get_instance_by_name('themeconfigurator');
        if (is_object($module) && $module->active && (int) Configuration::get('PS_TC_ACTIVE') == 1 && $this->context->shop->get_base_url()) {
            $request = 'live_configurator_token=' . $module->get_live_configurator_token() . '&id_employee=' . (int) $this->context->employee->id . '&id_shop=' . (int) $this->context->shop->id . (Configuration::get('PS_TC_THEME') != '' ? '&theme=' . Configuration::get('PS_TC_THEME') : '') . (Configuration::get('PS_TC_FONT') != '' ? '&theme_font=' . Configuration::get('PS_TC_FONT') : '');
            $this->context->smarty->assign('base_url_tc', $this->context->link->get_page_link('index', null, null, $request));
        }
    }
    /**
     * Declare an action to use for each row in the list
     *
     * @param string $action
     */
    public function add_row_action($action): void
    {
        $action = strtolower($action);
        $this->actions[] = $action;
    }
    /**
     * Add an action to use for each row in the list
     *
     * @param string $action
     * @param array $list
     */
    public function add_row_action_skip_list($action, $list): void
    {
        $action = strtolower($action);
        $list = (array) $list;
        if (array_key_exists($action, $this->list_skip_actions)) {
            $this->list_skip_actions[$action] = array_merge($this->list_skip_actions[$action], $list);
        } else {
            $this->list_skip_actions[$action] = $list;
        }
    }
    /**
     * Assign smarty variables for all default views, list and form, then call other init functions
     *
     *
     * @throws PrestaShopException
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function init_content(): void
    {
        if (!$this->view_access()) {
            $this->errors[] = Tools::display_error('You do not have permission to view this.');
            return;
        }
        $this->get_languages();
        $this->init_toolbar();
        $this->init_page_header_toolbar();
        if ($this->display == 'edit' || $this->display == 'add') {
            if ($this->class_name) {
                if (!$this->load_object(true)) {
                    return;
                }
            }
            $this->content .= $this->render_form();
        } elseif ($this->display == 'view') {
            // Some controllers use the view action without an object
            if ($this->class_name) {
                $this->load_object(true);
            }
            $this->content .= $this->render_view();
        } elseif ($this->display == 'details') {
            $this->content .= $this->render_details();
        } elseif (!$this->ajax) {
            $this->content .= $this->render_kpis();
            $this->content .= $this->render_list();
            $this->content .= $this->render_options();
            // if we have to display the required fields form
            if ($this->required_database) {
                $this->content .= $this->display_required_fields();
            }
        }
        $this->context->smarty->assign(['maintenance_mode' => !Configuration::get('PS_SHOP_ENABLE'), 'content' => $this->content, 'lite_display' => $this->lite_display, 'url_post' => static::$current_index . '&token=' . $this->token, 'show_page_header_toolbar' => $this->show_page_header_toolbar, 'page_header_toolbar_title' => $this->page_header_toolbar_title, 'title' => $this->page_header_toolbar_title, 'toolbar_btn' => $this->page_header_toolbar_btn, 'page_header_toolbar_btn' => $this->page_header_toolbar_btn]);
    }
    /**
     * @throws PrestaShopException
     */
    protected static function resolve_form_languages(int $default_form_language): array
    {
        $languages = Language::get_languages(false);
        foreach ($languages as &$language) {
            $is_default = $default_form_language === (int) $language['id_lang'];
            $language['is_default'] = $is_default ? 1 : 0;
        }
        return $languages;
    }
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_languages()
    {
        if (!$this->_languages) {
            $this->_languages = static::resolve_form_languages($this->get_default_form_language());
        }
        return $this->_languages;
    }
    /**
     *
     * @throws PrestaShopException
     */
    protected static function resolve_default_form_language(Cookie $cookie, int $allow_employee_lang): int
    {
        $language_ids = Language::get_languages(false, false, true);
        if ($language_ids) {
            // first check last used employee language
            if ($allow_employee_lang) {
                if (isset($cookie->employee_form_lang)) {
                    $employee_lang = (int) $cookie->employee_form_lang;
                    if (in_array($employee_lang, $language_ids)) {
                        return $employee_lang;
                    }
                }
            }
            if (isset($cookie->employee_form_lang)) {
                unset($cookie->employee_form_lang);
            }
            // try default language
            $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
            if (in_array($default_lang, $language_ids)) {
                return $default_lang;
            }
            // fallback to first language in the list
            return $language_ids[0];
        }
        return 0;
    }
    /**
     * @throws PrestaShopException
     */
    protected function get_default_form_language(): int
    {
        if (is_null($this->default_form_language)) {
            $this->default_form_language = static::resolve_default_form_language($this->context->cookie, $this->get_allow_employee_form_language());
        }
        return (int) $this->default_form_language;
    }
    /**
     * @throws PrestaShopException
     */
    protected function get_allow_employee_form_language(): int
    {
        if (is_null($this->allow_employee_form_lang)) {
            $this->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        }
        return (int) $this->allow_employee_form_lang;
    }
    /**
     * assign default action in toolbar_btn smarty var, if they are not set.
     * uses override to specifically add, modify or remove items
     *
     * @throws PrestaShopException
     */
    public function init_toolbar(): void
    {
        switch ($this->display) {
            case 'add':
            case 'edit':
                // Default save button - action dynamically handled in javascript
                $this->toolbar_btn['save'] = ['href' => '#', 'desc' => $this->l('Save')];
                if (!$this->lite_display) {
                    $this->toolbar_btn['cancel'] = ['href' => $this->get_back_url_parameter(), 'desc' => $this->l('Cancel')];
                }
                break;
            case 'view':
                if (!$this->lite_display) {
                    $this->toolbar_btn['back'] = ['href' => $this->get_back_url_parameter(), 'desc' => $this->l('Back to list')];
                }
                break;
            case 'options':
                $this->toolbar_btn['save'] = ['href' => '#', 'desc' => $this->l('Save')];
                break;
            default:
                // list
                $this->toolbar_btn['new'] = ['href' => static::$current_index . '&add' . $this->table . '&token=' . $this->token, 'desc' => $this->l('Add new')];
                if ($this->allow_export) {
                    $this->toolbar_btn['export'] = ['href' => static::$current_index . '&export' . $this->table . '&token=' . $this->token, 'desc' => $this->l('Export')];
                }
        }
    }
    /**
     * @param array|null $supporterInfo
     */
    public function get_campaign_classes($supporter_info): string
    {
        $campaign_class = 'campaign-bar-on';
        $employee = $this->context->employee;
        if (Validate::is_loaded_object($employee)) {
            $disabled = false;
            if ($employee->campaign_disabled) {
                try {
                    $ts = DateTime::create_from_format('Y-m-d H:i:s', $employee->campaign_disabled);
                    if ($ts) {
                        $months = $supporter_info ? 6 : 1;
                        $disabled_until = $ts->add(new DateInterval('P' . $months . 'M'));
                        $now = new DateTime();
                        if ($disabled_until > $now) {
                            $disabled = true;
                        }
                    }
                } catch (Throwable) {
                }
            }
            if (!$disabled) {
                $campaign_class .= ' show-campaign-bar';
                $campaign_class .= ' show-campaign-slider';
            }
        }
        if ($supporter_info) {
            $campaign_class .= ' ' . $supporter_info['type'];
        }
        return $campaign_class;
    }
    /**
     * @return void
     */
    protected function add_tool_bar_modules_list_button()
    {
    }
    /**
     * @return void
     */
    protected function filter_tab_module_list()
    {
    }
    /**
     * Init tab modules list and add button in toolbar
     *
     * @deprecated 1.5.0
     */
    protected function init_tab_module_list()
    {
    }
    /**
     * @param string $file
     * @param int $timeout
     *
     * @return bool
     */
    public function is_fresh($file, $timeout = 604800)
    {
        $path = _PS_ROOT_DIR_ . $file;
        if (file_exists($path) && filesize($path) > 0) {
            return time() - filemtime($path) < $timeout;
        }
        return false;
    }
    /**
     * @return void
     */
    protected function add_page_header_tool_bar_modules_list_button()
    {
    }
    /**
     * @throws PrestaShopException
     */
    public function init_page_header_toolbar(): void
    {
        if (empty($this->toolbar_title)) {
            $this->init_toolbar_title();
        }
        if (!is_array($this->toolbar_title)) {
            $this->toolbar_title = [$this->toolbar_title];
        }
        switch ($this->display) {
            case 'view':
                // Default cancel button - like old back link
                if (!$this->lite_display) {
                    $this->page_header_toolbar_btn['back'] = ['href' => $this->get_back_url_parameter(), 'desc' => $this->l('Back to list')];
                }
                $obj = $this->load_object(true);
                if (Validate::is_loaded_object($obj) && !empty($obj->{$this->identifier_name})) {
                    array_pop($this->toolbar_title);
                    array_pop($this->meta_title);
                    $this->toolbar_title[] = is_array($obj->{$this->identifier_name}) ? $obj->{$this->identifier_name}[$this->context->employee->id_lang] : $obj->{$this->identifier_name};
                    $this->add_meta_title($this->toolbar_title[count($this->toolbar_title) - 1]);
                }
                break;
            case 'edit':
                $obj = $this->load_object(true);
                if (Validate::is_loaded_object($obj) && !empty($obj->{$this->identifier_name})) {
                    array_pop($this->toolbar_title);
                    array_pop($this->meta_title);
                    $this->toolbar_title[] = sprintf($this->l('Edit: %s'), is_array($obj->{$this->identifier_name}) && isset($obj->{$this->identifier_name}[$this->context->employee->id_lang]) ? $obj->{$this->identifier_name}[$this->context->employee->id_lang] : $obj->{$this->identifier_name});
                    $this->add_meta_title($this->toolbar_title[count($this->toolbar_title) - 1]);
                }
                break;
        }
        if (empty($this->page_header_toolbar_title) && $this->toolbar_title) {
            if (is_array($this->toolbar_title)) {
                $size = count($this->toolbar_title);
                $this->page_header_toolbar_title = $this->toolbar_title[$size - 1];
            } else {
                $this->page_header_toolbar_title = $this->toolbar_title;
            }
        }
        if (is_iterable($this->page_header_toolbar_btn) || $this->page_header_toolbar_title) {
            $this->show_page_header_toolbar = true;
        }
        $this->context->smarty->assign('help_link', '');
    }
    /**
     * Set default toolbar_title to admin breadcrumb
     */
    public function init_toolbar_title(): void
    {
        $this->toolbar_title = is_array($this->breadcrumbs) ? array_unique($this->breadcrumbs) : [$this->breadcrumbs];
        switch ($this->display) {
            case 'edit':
                $this->toolbar_title[] = $this->l('Edit', null, null, false);
                $this->add_meta_title($this->l('Edit', null, null, false));
                break;
            case 'add':
                $this->toolbar_title[] = $this->l('Add new', null, null, false);
                $this->add_meta_title($this->l('Add new', null, null, false));
                break;
            case 'view':
                $this->toolbar_title[] = $this->l('View', null, null, false);
                $this->add_meta_title($this->l('View', null, null, false));
                break;
        }
        if ($filter = $this->add_filters_to_breadcrumbs()) {
            $this->toolbar_title[] = $filter;
        }
    }
    /**
     * Add an entry to the meta title.
     *
     * @param string $entry New entry.
     */
    public function add_meta_title($entry): void
    {
        // Only add entry if the meta title was not forced.
        if (is_array($this->meta_title)) {
            $this->meta_title[] = $entry;
        }
    }
    /**
     * @return string
     */
    public function add_filters_to_breadcrumbs()
    {
        if ($this->filter && is_array($this->fields_list)) {
            $filters = [];
            foreach ($this->fields_list as $field => $t) {
                if (isset($t['filter_key'])) {
                    $field = $t['filter_key'];
                }
                $val = $this->get_list_field_filter_value($field);
                if (!is_null($val)) {
                    $filter_value = '';
                    if (!is_array($val)) {
                        if (isset($t['type']) && $t['type'] == 'bool') {
                            $filter_value = $val ? $this->l('yes') : $this->l('no');
                        } elseif (isset($t['type']) && $t['type'] == 'date' || isset($t['type']) && $t['type'] == 'datetime') {
                            $date = json_decode($val, true);
                            if (isset($date[0]) && $ts = strtotime((string) $date[0])) {
                                $filter_value = date('Y-m-d', $ts);
                                if (!empty($date[1]) && $ts = strtotime((string) $date[1])) {
                                    $filter_value .= ' - ' . date('Y-m-d', $ts);
                                }
                            }
                        } elseif (is_string($val)) {
                            $filter_value = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
                        }
                    } else {
                        foreach ($val as $v) {
                            if (is_string($v)) {
                                $v = trim($v);
                                if ($v !== '') {
                                    $filter_value .= ' - ' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
                                }
                            }
                        }
                        $filter_value = ltrim($filter_value, ' -');
                    }
                    if ($filter_value !== '') {
                        $filters[] = sprintf($this->l('%s: %s'), $t['title'], $filter_value);
                    }
                }
            }
            if (count($filters)) {
                return sprintf($this->l('filter by %s'), implode(', ', $filters));
            }
        }
        return null;
    }
    /**
     * Function used to render the form for this controller
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render_form()
    {
        $this->get_languages();
        if (Tools::get_value('submitFormAjax')) {
            $this->content .= $this->context->smarty->fetch('form_submit_ajax.tpl');
        }
        if ($this->fields_form && is_array($this->fields_form)) {
            if (!$this->multiple_fieldsets) {
                $this->fields_form = [['form' => $this->fields_form]];
            }
            // For add a fields via an override of $fields_form, use $fields_form_override
            if (is_array($this->fields_form_override) && !empty($this->fields_form_override)) {
                $this->fields_form[0]['form']['input'] = array_merge($this->fields_form[0]['form']['input'], $this->fields_form_override);
            }
            $fields_value = $this->get_fields_value($this->object);
            Hook::trigger_event('action' . $this->controller_name . 'FormModifier', ['fields' => &$this->fields_form, 'fields_value' => &$fields_value, 'form_vars' => &$this->tpl_form_vars]);
            $helper = new Helper_Form();
            $this->set_helper_display($helper);
            $helper->fields_value = $fields_value;
            $helper->submit_action = $this->submit_action;
            $helper->tpl_vars = $this->get_template_form_vars();
            $helper->show_cancel_button = $this->show_form_cancel_button ?? $this->display == 'add' || $this->display == 'edit';
            $helper->back_url = $this->get_back_url_parameter();
            if ($this->base_tpl_form) {
                $helper->base_tpl = $this->base_tpl_form;
            }
            if ($this->has_view_permission()) {
                if (Tools::get_value('back')) {
                    $helper->tpl_vars['back'] = Tools::safe_output(Tools::get_value('back'));
                } else {
                    $helper->tpl_vars['back'] = Tools::safe_output(Tools::get_value(static::$current_index . '&token=' . $this->token));
                }
            }
            return $helper->generate_form($this->fields_form);
        }
    }
    /**
     * Return the list of fields value
     *
     * @param ObjectModel $obj Object
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_fields_value($obj)
    {
        foreach ($this->fields_form as $fieldset) {
            if (isset($fieldset['form']['input'])) {
                foreach ($fieldset['form']['input'] as $input) {
                    if (!isset($this->fields_value[$input['name']])) {
                        if (isset($input['type']) && $input['type'] == 'shop') {
                            if ($obj->id) {
                                $result = Shop::get_shop_by_id((int) $obj->id, $this->identifier, $this->table);
                                foreach ($result as $row) {
                                    $this->fields_value['shop'][$row['id_' . $input['type']]][] = $row['id_shop'];
                                }
                            }
                        } elseif (isset($input['lang']) && $input['lang']) {
                            foreach ($this->get_languages() as $language) {
                                $field_value = $this->get_field_value($obj, $input['name'], $language['id_lang']);
                                if (empty($field_value)) {
                                    if (isset($input['default_value'][$language['id_lang']]) && is_array($input['default_value'])) {
                                        $field_value = $input['default_value'][$language['id_lang']];
                                    } elseif (isset($input['default_value'])) {
                                        $field_value = $input['default_value'];
                                    }
                                }
                                $this->fields_value[$input['name']][$language['id_lang']] = $field_value;
                            }
                        } else {
                            $field_value = $this->get_field_value($obj, $input['name']);
                            if ($field_value === false && isset($input['default_value'])) {
                                $field_value = $input['default_value'];
                            }
                            $this->fields_value[$input['name']] = $field_value;
                        }
                    }
                }
            }
        }
        return $this->fields_value;
    }
    /**
     * Return field value if possible (both classical and multilingual fields)
     *
     * Case 1 : Return value if present in $_POST / $_GET
     * Case 2 : Return object value
     *
     * @param ObjectModel|null $obj Object
     * @param string $key Field name
     * @param int|null $idLang Language id (optional)
     *
     * @return array|bool|float|int|string|null
     */
    public function get_field_value($obj, $key, $id_lang = null)
    {
        if (is_object($obj) && property_exists($obj, $key)) {
            if ($id_lang) {
                $default_value = isset($obj->id) && $obj->id && isset($obj->{$key}[$id_lang]) ? $obj->{$key}[$id_lang] : false;
            } else {
                $default_value = $obj->{$key} ?? false;
            }
        } else {
            $default_value = false;
        }
        return Tools::get_value($key . ($id_lang ? '_' . $id_lang : ''), $default_value);
    }
    /**
     * This function sets various display options for helper list
     *
     *
     * @throws PrestaShopException
     */
    public function set_helper_display(Helper $helper): void
    {
        if (empty($this->toolbar_title)) {
            $this->init_toolbar_title();
        }
        if ($helper instanceof Helper_List) {
            $this->set_helper_list_display($helper);
        } elseif ($helper instanceof Helper_View) {
            $this->set_helper_view_display($helper);
        } elseif ($helper instanceof Helper_Form) {
            $this->set_helper_form_display($helper);
        } elseif ($helper instanceof Helper_Options) {
            $this->set_helper_options_display($helper);
        } elseif ($helper instanceof Helper_Kpi) {
            $this->set_helper_kpi_display($helper);
        } elseif ($helper instanceof Helper_Kpi_Row) {
            $this->set_helper_kpi_row_display($helper);
        } elseif ($helper instanceof Helper_Shop) {
            $this->set_helper_shop_display($helper);
        } elseif ($helper instanceof Helper_Calendar) {
            $this->set_helper_calendar_display($helper);
        } else {
            $this->set_helper_common_display($helper);
        }
        $this->helper = $helper;
    }
    /**
     * @throws PrestaShopException
     */
    public function set_helper_common_display(Helper $helper): void
    {
        $helper->title = is_array($this->toolbar_title) ? implode(' ' . Configuration::get('PS_NAVIGATION_PIPE') . ' ', $this->toolbar_title) : $this->toolbar_title;
        $helper->toolbar_btn = $this->toolbar_btn;
        $helper->show_toolbar = $this->show_toolbar;
        $helper->toolbar_scroll = $this->toolbar_scroll;
        $helper->override_folder = $this->tpl_folder;
        $helper->current_index = static::$current_index;
        $helper->table = $this->table;
        $helper->identifier = $this->identifier;
        $helper->token = $this->token;
        $helper->bootstrap = $this->bootstrap;
    }
    /**
     * @throws PrestaShopException
     */
    public function set_helper_list_display(Helper_List $helper): void
    {
        $this->set_helper_common_display($helper);
        $helper->set_list_error($this->_list_error);
        $helper->actions = $this->actions;
        $helper->simple_header = $this->list_simple_header;
        $helper->bulk_actions = $this->bulk_actions;
        $helper->order_by = $this->_order_by;
        $helper->order_way = $this->_order_way;
        $helper->list_total = $this->_list_total;
        $helper->specific_confirm_delete = $this->specific_confirm_delete;
        $helper->no_link = $this->list_no_link;
        $helper->color_on_background = $this->color_on_background;
        $helper->shop_link_type = $this->shop_link_type;
        $helper->image_type = $this->image_type;
        $helper->ajax_params = $this->ajax_params;
        $helper->row_hover = $this->row_hover;
        $helper->position_identifier = $this->position_identifier;
        $helper->position_group_identifier = $this->position_group_identifier;
        $helper->controller_name = $this->controller_name;
        $helper->list_id = $this->list_id ?? $this->table;
        $helper->list_skip_actions = $this->list_skip_actions;
    }
    /**
     * @throws PrestaShopException
     */
    public function set_helper_form_display(Helper_Form $helper): void
    {
        $this->set_helper_common_display($helper);
        if ($this->object && $this->object->id) {
            $helper->id = $this->object->id;
        }
        $helper->name_controller = Tools::get_value('controller');
        $helper->languages = $this->get_languages();
        $helper->default_form_language = $this->get_default_form_language();
        $helper->allow_employee_form_lang = $this->get_allow_employee_form_language();
    }
    /**
     * @throws PrestaShopException
     */
    public function set_helper_view_display(Helper_View $helper): void
    {
        $this->set_helper_common_display($helper);
        if ($this->object && $this->object->id) {
            $helper->id = $this->object->id;
        }
    }
    /**
     * @throws PrestaShopException
     */
    public function set_helper_options_display(Helper_Options $helper): void
    {
        $this->set_helper_common_display($helper);
        if ($this->object && $this->object->id) {
            $helper->id = $this->object->id;
        }
    }
    /**
     * @throws PrestaShopException
     */
    public function set_helper_kpi_display(Helper_Kpi $helper): void
    {
        $this->set_helper_common_display($helper);
        if ($this->object && $this->object->id) {
            if (strlen((string) $helper->id) == 0) {
                $helper->id = (string) $this->object->id;
            } else {
                $helper->id .= '-' . $this->object->id;
            }
        }
    }
    /**
     * @throws PrestaShopException
     */
    public function set_helper_kpi_row_display(Helper_Kpi_Row $helper): void
    {
        $this->set_helper_common_display($helper);
    }
    /**
     * @throws PrestaShopException
     */
    public function set_helper_calendar_display(Helper_Calendar $helper): void
    {
        $this->set_helper_common_display($helper);
    }
    /**
     * @throws PrestaShopException
     */
    public function set_helper_shop_display(Helper_Shop $helper): void
    {
        $this->set_helper_common_display($helper);
    }
    /**
     * @return array
     */
    public function get_template_form_vars()
    {
        return $this->tpl_form_vars;
    }
    /**
     * Override to render the view page
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render_view()
    {
        $helper = new Helper_View();
        $this->set_helper_display($helper);
        $helper->tpl_vars = $this->get_template_view_vars();
        if (!is_null($this->base_tpl_view)) {
            $helper->base_tpl = $this->base_tpl_view;
        }
        return $helper->generate_view();
    }
    /**
     * @return array
     */
    public function get_template_view_vars()
    {
        return $this->tpl_view_vars;
    }
    /**
     * Override to render the view page
     *
     * @return string|false
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render_details()
    {
        return $this->render_list();
    }
    /**
     * Function used to render the list to display for this controller
     *
     * @return string|false
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render_list()
    {
        if (!($this->fields_list && is_array($this->fields_list))) {
            return false;
        }
        $this->get_list($this->context->language->id);
        // If list has 'active' field, we automatically create bulk action
        if (isset($this->fields_list) && is_array($this->fields_list) && array_key_exists('active', $this->fields_list) && !empty($this->fields_list['active'])) {
            if (!is_array($this->bulk_actions)) {
                $this->bulk_actions = [];
            }
            $this->bulk_actions = array_merge(['enableSelection' => ['text' => $this->l('Enable selection'), 'icon' => 'icon-power-off text-success'], 'disableSelection' => ['text' => $this->l('Disable selection'), 'icon' => 'icon-power-off text-danger'], 'divider' => ['text' => 'divider']], $this->bulk_actions);
        }
        $helper = new Helper_List();
        $this->set_helper_display($helper);
        $helper->_default_pagination = $this->_default_pagination;
        $helper->_pagination = $this->_pagination;
        $helper->tpl_vars = $this->get_template_list_vars();
        $helper->tpl_delete_link_vars = $this->tpl_delete_link_vars;
        // For compatibility reasons, we have to check standard actions in class attributes
        foreach ($this->actions_available as $action) {
            if (!in_array($action, $this->actions) && isset($this->{$action}) && $this->{$action}) {
                $this->actions[] = $action;
            }
        }
        $helper->is_cms = $this->is_cms;
        $helper->sql = $this->_listsql;
        return $helper->generate_list($this->_list, $this->fields_list);
    }
    /**
     * Add a warning message to display at the top of the page
     *
     * @param string $msg
     */
    protected function display_warning($msg)
    {
        $this->warnings[] = $msg;
    }
    /**
     * @return array
     */
    public function get_template_list_vars()
    {
        return $this->tpl_list_vars;
    }
    /**
     * @return void
     */
    public function render_modules_list()
    {
    }
    /**
     * @param array|string $filterModulesList
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function get_modules_list($filter_modules_list)
    {
        if (!is_array($filter_modules_list) && !is_null($filter_modules_list)) {
            $filter_modules_list = [$filter_modules_list];
        }
        if (is_null($filter_modules_list) || !count($filter_modules_list)) {
            return false;
        }
        //if there is no modules to display just return false;
        $all_modules = Module::get_modules_on_disk(true);
        $this->modules_list = [];
        foreach ($all_modules as $module) {
            if ($module->id) {
                $perm = Module::get_permission_static($module->id, 'configure');
            } else {
                $perm = $this->context->employee->has_access(Admin_Modules_Controller::class, Profile::PERMISSION_EDIT);
            }
            if (in_array($module->name, $filter_modules_list) && $perm) {
                $this->fill_module_data($module);
                $this->modules_list[array_search($module->name, $filter_modules_list)] = $module;
            }
        }
        ksort($this->modules_list);
        if (count($this->modules_list)) {
            return true;
        }
        return false;
        //no module found on disk just return false;
    }
    /**
     * @param string $fileToRefresh
     * @param string $externalFile
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function refresh($file_to_refresh, $external_file)
    {
        $guzzle = new Client(['timeout' => 5, 'verify' => Configuration::get_ssl_trust_store()]);
        if (static::$is_thirtybees_up) {
            try {
                $content = (string) $guzzle->get($external_file)->get_body();
                return (bool) file_put_contents(_PS_ROOT_DIR_ . $file_to_refresh, $content);
            } catch (Throwable) {
                static::$is_thirtybees_up = false;
                return false;
            }
        }
        return false;
    }
    /**
     * @param stdClass $module
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function fill_module_data(&$module): void
    {
        // Fill module data
        $module->logo = '../../img/questionmark.png';
        if (file_exists(_PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . basename(_PS_MODULE_DIR_) . DIRECTORY_SEPARATOR . $module->name . DIRECTORY_SEPARATOR . 'logo.gif')) {
            $module->logo = 'logo.gif';
        }
        if (file_exists(_PS_ROOT_DIR_ . DIRECTORY_SEPARATOR . basename(_PS_MODULE_DIR_) . DIRECTORY_SEPARATOR . $module->name . DIRECTORY_SEPARATOR . 'logo.png')) {
            $module->logo = 'logo.png';
        }
        $link_admin_modules = $this->context->link->get_admin_link('AdminModules', true);
        $module->options['install_url'] = $link_admin_modules . '&install=' . urlencode((string) $module->name) . '&tab_module=' . $module->tab . '&module_name=' . $module->name . '&anchor=' . ucfirst((string) $module->name);
        $module->options['update_url'] = $link_admin_modules . '&update=' . urlencode((string) $module->name) . '&tab_module=' . $module->tab . '&module_name=' . $module->name . '&anchor=' . ucfirst((string) $module->name);
        $module->options['uninstall_url'] = $link_admin_modules . '&uninstall=' . urlencode((string) $module->name) . '&tab_module=' . $module->tab . '&module_name=' . $module->name . '&anchor=' . ucfirst((string) $module->name);
        $module->options_html = $this->display_module_options($module);
        if ((Tools::get_value('module_name') == $module->name || in_array($module->name, explode('|', Tools::get_value('modules_list')))) && Tools::get_int_value('conf') > 0) {
            $module->message = $this->_conf[Tools::get_int_value('conf')];
        }
    }
    /**
     * Display modules list
     *
     * @param stdClass $module
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function display_module_options($module)
    {
        if (!isset($module->enable_device)) {
            $module->enable_device = Context::DEVICE_COMPUTER | Context::DEVICE_TABLET | Context::DEVICE_MOBILE;
        }
        $this->translations_tab['confirm_uninstall_popup'] = isset($module->confirm_uninstall) && $module->confirm_uninstall ? $module->confirm_uninstall : $this->l('Do you really want to uninstall this module? All its data will be lost!');
        if (!isset($this->translations_tab['Disable this module'])) {
            $this->translations_tab['Disable this module'] = $this->l('Disable this module');
            $this->translations_tab['Enable this module for all shops'] = $this->l('Enable this module for all shops');
            $this->translations_tab['Disable'] = $this->l('Disable');
            $this->translations_tab['Enable'] = $this->l('Enable');
            $this->translations_tab['Disable on mobiles'] = $this->l('Disable on mobiles');
            $this->translations_tab['Disable on tablets'] = $this->l('Disable on tablets');
            $this->translations_tab['Disable on computers'] = $this->l('Disable on computers');
            $this->translations_tab['Display on mobiles'] = $this->l('Display on mobiles');
            $this->translations_tab['Display on tablets'] = $this->l('Display on tablets');
            $this->translations_tab['Display on computers'] = $this->l('Display on computers');
            $this->translations_tab['Reset'] = $this->l('Reset');
            $this->translations_tab['Configure'] = $this->l('Configure');
            $this->translations_tab['Delete'] = $this->l('Delete');
            $this->translations_tab['Install'] = $this->l('Install');
            $this->translations_tab['Uninstall'] = $this->l('Uninstall');
            $this->translations_tab['Would you like to delete the content related to this module ?'] = $this->l('Would you like to delete the content related to this module ?');
            $this->translations_tab['This action will permanently remove the module from the server. Are you sure you want to do this?'] = $this->l('This action will permanently remove the module from the server. Are you sure you want to do this?');
            $this->translations_tab['Remove from Favorites'] = $this->l('Remove from Favorites');
            $this->translations_tab['Mark as Favorite'] = $this->l('Mark as Favorite');
        }
        $link_admin_modules = $this->context->link->get_admin_link('AdminModules', true);
        $modules_options = [];
        $has_reset = false;
        $onclick_options = ['desactive' => '', 'reset' => '', 'configure' => '', 'delete' => 'return confirm(\'' . $this->translations_tab['This action will permanently remove the module from the server. Are you sure you want to do this?'] . '\');', 'uninstall' => 'return confirm(\'' . $this->translations_tab['confirm_uninstall_popup'] . '\');'];
        if (Validate::is_module_name($module->name) && Module::is_enabled($module->name)) {
            $instance = Module::get_instance_by_name($module->name);
            if ($instance) {
                // check if module has reset capability
                if (method_exists($instance, 'reset')) {
                    $has_reset = true;
                }
                // check if module provides custom onclick handlers
                if (method_exists($instance, 'onclickOption')) {
                    $href = Context::get_context()->link->get_admin_link('Module', true) . '&module_name=' . $instance->name . '&tab_module=' . $instance->tab;
                    foreach (array_keys($onclick_options) as $opt) {
                        $on_click = $instance->onclick_option($opt, $href);
                        if ($on_click) {
                            $onclick_options[$opt] = $on_click;
                        }
                    }
                }
            }
        }
        $configure_module = ['href' => $link_admin_modules . '&configure=' . urlencode((string) $module->name) . '&tab_module=' . $module->tab . '&module_name=' . urlencode((string) $module->name), 'onclick' => $onclick_options['configure'], 'title' => '', 'text' => $this->translations_tab['Configure'], 'cond' => $module->id && $module->is_configurable, 'icon' => 'wrench'];
        $deactivate_module = ['href' => $link_admin_modules . '&module_name=' . urlencode((string) $module->name) . '&' . ($module->active ? 'enable=0' : 'enable=1') . '&tab_module=' . $module->tab, 'onclick' => $module->active ? $onclick_options['desactive'] : '', 'title' => Shop::is_feature_active() ? htmlspecialchars($module->active ? $this->translations_tab['Disable this module'] : $this->translations_tab['Enable this module for all shops']) : '', 'text' => $module->active ? $this->translations_tab['Disable'] : $this->translations_tab['Enable'], 'cond' => $module->id, 'icon' => 'off'];
        $link_reset_module = $link_admin_modules . '&module_name=' . urlencode((string) $module->name) . '&reset&tab_module=' . $module->tab;
        $reset_module = ['href' => $link_reset_module, 'onclick' => $onclick_options['reset'], 'title' => '', 'text' => $this->translations_tab['Reset'], 'cond' => $module->id && $module->active, 'icon' => 'undo', 'class' => $has_reset ? 'reset_ready' : ''];
        $delete_module = ['href' => $link_admin_modules . '&delete=' . urlencode((string) $module->name) . '&tab_module=' . $module->tab . '&module_name=' . urlencode((string) $module->name), 'onclick' => $onclick_options['delete'], 'title' => '', 'text' => $this->translations_tab['Delete'], 'cond' => file_exists(_PS_MODULE_DIR_ . $module->name) && is_dir(_PS_MODULE_DIR_ . $module->name), 'icon' => 'trash', 'class' => 'text-danger'];
        $display_mobile = ['href' => $link_admin_modules . '&module_name=' . urlencode((string) $module->name) . '&' . ($module->enable_device & Context::DEVICE_MOBILE ? 'disable_device' : 'enable_device') . '=' . Context::DEVICE_MOBILE . '&tab_module=' . $module->tab, 'onclick' => '', 'title' => htmlspecialchars($module->enable_device & Context::DEVICE_MOBILE ? $this->translations_tab['Disable on mobiles'] : $this->translations_tab['Display on mobiles']), 'text' => $module->enable_device & Context::DEVICE_MOBILE ? $this->translations_tab['Disable on mobiles'] : $this->translations_tab['Display on mobiles'], 'cond' => $module->id, 'icon' => 'mobile'];
        $display_tablet = ['href' => $link_admin_modules . '&module_name=' . urlencode((string) $module->name) . '&' . ($module->enable_device & Context::DEVICE_TABLET ? 'disable_device' : 'enable_device') . '=' . Context::DEVICE_TABLET . '&tab_module=' . $module->tab, 'onclick' => '', 'title' => htmlspecialchars($module->enable_device & Context::DEVICE_TABLET ? $this->translations_tab['Disable on tablets'] : $this->translations_tab['Display on tablets']), 'text' => $module->enable_device & Context::DEVICE_TABLET ? $this->translations_tab['Disable on tablets'] : $this->translations_tab['Display on tablets'], 'cond' => $module->id, 'icon' => 'tablet'];
        $display_computer = ['href' => $link_admin_modules . '&module_name=' . urlencode((string) $module->name) . '&' . ($module->enable_device & Context::DEVICE_COMPUTER ? 'disable_device' : 'enable_device') . '=' . Context::DEVICE_COMPUTER . '&tab_module=' . $module->tab, 'onclick' => '', 'title' => htmlspecialchars($module->enable_device & Context::DEVICE_COMPUTER ? $this->translations_tab['Disable on computers'] : $this->translations_tab['Display on computers']), 'text' => $module->enable_device & Context::DEVICE_COMPUTER ? $this->translations_tab['Disable on computers'] : $this->translations_tab['Display on computers'], 'cond' => $module->id, 'icon' => 'desktop'];
        $install = ['href' => $link_admin_modules . '&install=' . urlencode((string) $module->name) . '&tab_module=' . $module->tab . '&module_name=' . $module->name . '&anchor=' . ucfirst((string) $module->name), 'onclick' => '', 'title' => $this->translations_tab['Install'], 'text' => $this->translations_tab['Install'], 'cond' => $module->id, 'icon' => 'plus-sign-alt'];
        $uninstall = ['href' => $link_admin_modules . '&uninstall=' . urlencode((string) $module->name) . '&tab_module=' . $module->tab . '&module_name=' . $module->name . '&anchor=' . ucfirst((string) $module->name), 'onclick' => $onclick_options['uninstall'], 'title' => $this->translations_tab['Uninstall'], 'text' => $this->translations_tab['Uninstall'], 'cond' => $module->id, 'icon' => 'minus-sign-alt'];
        $remove_from_favorite = ['href' => '#', 'class' => 'action_unfavorite toggle_favorite', 'onclick' => '', 'title' => $this->translations_tab['Remove from Favorites'], 'text' => $this->translations_tab['Remove from Favorites'], 'cond' => $module->id, 'icon' => 'star', 'data-value' => '0', 'data-module' => $module->name];
        $mark_as_favorite = ['href' => '#', 'class' => 'action_favorite toggle_favorite', 'onclick' => '', 'title' => $this->translations_tab['Mark as Favorite'], 'text' => $this->translations_tab['Mark as Favorite'], 'cond' => $module->id, 'icon' => 'star', 'data-value' => '1', 'data-module' => $module->name];
        $update = ['href' => $module->options['update_url'], 'onclick' => '', 'title' => 'Update it!', 'text' => 'Update it!', 'icon' => 'refresh', 'cond' => $module->id];
        $url = ['href' => $module->url ?? '', 'onclick' => '', 'target' => '_blank', 'title' => $this->l('Visit module page'), 'text' => $this->l('Visit module page'), 'cond' => isset($module->url) && $module->url, 'icon' => 'link'];
        $divider = ['href' => '#', 'onclick' => '', 'title' => 'divider', 'text' => 'divider', 'cond' => $module->id];
        if (isset($module->version_addons) && $module->version_addons) {
            $modules_options[] = $update;
        }
        if ($module->active) {
            $modules_options[] = $configure_module;
            $modules_options[] = $deactivate_module;
            $modules_options[] = $display_mobile;
            $modules_options[] = $display_tablet;
            $modules_options[] = $display_computer;
        } else {
            $modules_options[] = $deactivate_module;
            $modules_options[] = $configure_module;
        }
        $modules_options[] = $reset_module;
        if ($module->id) {
            $modules_options[] = $uninstall;
        }
        if (isset($module->preferences['favorite']) && $module->preferences['favorite'] == 1) {
            $remove_from_favorite['style'] = '';
            $mark_as_favorite['style'] = 'display:none;';
            $modules_options[] = $remove_from_favorite;
            $modules_options[] = $mark_as_favorite;
        } else {
            $mark_as_favorite['style'] = '';
            $remove_from_favorite['style'] = 'display:none;';
            $modules_options[] = $remove_from_favorite;
            $modules_options[] = $mark_as_favorite;
        }
        if ($module->id == 0) {
            $install['cond'] = 1;
            $install['flag_install'] = 1;
            $modules_options[] = $install;
        }
        $modules_options[] = $url;
        $modules_options[] = $divider;
        $modules_options[] = $delete_module;
        $return = [];
        foreach ($modules_options as $option) {
            if ($option['cond']) {
                $html = '<a class="';
                $is_install = isset($option['flag_install']);
                if (isset($option['class'])) {
                    $html .= $option['class'];
                }
                if ($is_install) {
                    $html .= ' btn btn-success';
                }
                if (!$is_install && count($return) == 0) {
                    $html .= ' btn btn-default';
                }
                $html .= '"';
                if (isset($option['data-value'])) {
                    $html .= ' data-value="' . $option['data-value'] . '"';
                }
                if (isset($option['data-module'])) {
                    $html .= ' data-module="' . $option['data-module'] . '"';
                }
                if (isset($option['style'])) {
                    $html .= ' style="' . $option['style'] . '"';
                }
                if (isset($option['target'])) {
                    $html .= ' target="' . $option['target'] . '"';
                }
                $html .= ' href="' . htmlentities((string) $option['href']) . '" onclick="' . $option['onclick'] . '"  title="' . $option['title'] . '"><i class="icon-' . (isset($option['icon']) && $option['icon'] ? $option['icon'] : 'cog') . '"></i> ' . $option['text'] . '</a>';
                $return[] = $html;
            }
        }
        return $return;
    }
    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render_kpis()
    {
        $kpis = $this->get_kpis();
        $hook_params = ['object' => $this, 'className' => static::class, 'display' => (string) ($this->display ?? 'list')];
        $responses = Hook::get_responses('actionGetKpis', $hook_params);
        foreach ($responses as $response) {
            if (is_array($response)) {
                $kpis = array_merge($kpis, $response);
            }
        }
        if ($kpis) {
            $rendered = [];
            foreach ($kpis as $kpi) {
                if ($kpi instanceof Helper_Kpi) {
                    if ($this->can_display_kpi((string) $kpi->id)) {
                        $rendered[] = $kpi->generate();
                    }
                } elseif (is_array($kpi) && array_key_exists('content', $kpi) && array_key_exists('id', $kpi)) {
                    if ($this->can_display_kpi((string) $kpi['id'])) {
                        $rendered[] = (string) $kpi['content'];
                    }
                }
            }
            if ($rendered) {
                $helper = new Helper_Kpi_Row();
                $helper->kpis = $rendered;
                return $helper->generate();
            }
        }
        return '';
    }
    /**
     *
     * @return true
     * @throws PrestaShopException
     */
    public function can_display_kpi(string $id): bool
    {
        $hook_params = ['object' => $this, 'className' => static::class, 'display' => (string) ($this->display ?? 'list'), 'kpi' => $id];
        foreach (Hook::get_responses('actionCanDisplayKpi', $hook_params) as $response) {
            if (is_bool($response)) {
                if (!$response) {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * Returns KPIs for this controllers.
     * Allowed returned values are
     *      - HelperKPI object
     *      - Array with keys ['id', 'title', 'content']
     */
    public function get_kpis(): array
    {
        return [];
    }
    /**
     * Function used to render the options for this controller
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render_options()
    {
        Hook::trigger_event('action' . $this->controller_name . 'OptionsModifier', ['options' => &$this->fields_options, 'option_vars' => &$this->tpl_option_vars]);
        if ($this->fields_options && is_array($this->fields_options)) {
            if (isset($this->display) && $this->display != 'options' && $this->display != 'list') {
                $this->show_toolbar = false;
            } else {
                $this->display = 'options';
            }
            unset($this->toolbar_btn);
            $this->init_toolbar();
            $helper = new Helper_Options();
            $this->set_helper_display($helper);
            $helper->id = $this->id;
            $helper->tpl_vars = $this->tpl_option_vars;
            return $helper->generate_options($this->fields_options);
        }
    }
    /**
     * Prepare the view to display the required fields form
     *
     * @return string|void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function display_required_fields()
    {
        if (!$this->has_add_permission() || !$this->has_delete_permission() || !$this->required_database) {
            return;
        }
        $helper = new Helper();
        $helper->current_index = static::$current_index;
        $helper->token = $this->token;
        $helper->override_folder = $this->override_folder;
        return $helper->render_required_fields($this->class_name, $this->identifier, $this->required_fields);
    }
    /**
     * Initialize the invalid doom page of death
     */
    public function init_cursed_page(): void
    {
        $this->layout = 'invalid_token.tpl';
    }
    /**
     * Assign smarty variables for the footer
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function init_footer(): void
    {
        //RTL Support
        //rtl.js overrides inline styles
        //iso_code.css overrides default fonts for every language (optional)
        if ($this->context->language->is_rtl) {
            $this->add_js(_PS_JS_DIR_ . 'rtl.js');
            $this->add_css(__PS_BASE_URI__ . $this->admin_webpath . '/themes/' . $this->bo_theme . '/css/' . $this->context->language->iso_code . '.css', 'all', false);
        }
        // We assign js and css files on the last step before display template, because controller can add many js and css files
        $this->context->smarty->assign('css_files', $this->css_files);
        $this->context->smarty->assign('js_files', array_unique($this->js_files));
        $supporter = Configuration::get_supporter_info();
        $this->context->smarty->assign(['ps_version' => _TB_VERSION_, 'timer_start' => $this->timer_start, 'iso_is_fr' => strtoupper((string) $this->context->language->iso_code) == 'FR', 'modals' => $this->render_modal(), 'showBecomeSupporterButton' => !$supporter, 'becomeSupporterUrl' => Configuration::get_become_supporter_url()]);
    }
    /**
     * @return string
     * @throws SmartyException
     */
    public function render_modal()
    {
        $modal_render = '';
        if (is_array($this->modals) && count($this->modals)) {
            foreach ($this->modals as $modal) {
                $this->context->smarty->assign($modal);
                $modal_render .= $this->context->smarty->fetch('modal.tpl');
            }
        }
        return $modal_render;
    }
    /**
     * @deprecated
     */
    public function set_deprecated_media()
    {
    }
    /**
     * @throws PrestaShopException
     */
    public function set_media(): void
    {
        //Bootstrap
        $this->add_css(__PS_BASE_URI__ . $this->admin_webpath . '/themes/' . $this->bo_theme . '/css/' . $this->bo_css, 'all', 0);
        $this->add_css(__PS_BASE_URI__ . $this->admin_webpath . '/themes/' . $this->bo_theme . '/css/overrides.css', 'all', PHP_INT_MAX);
        $this->add_css(__PS_BASE_URI__ . $this->admin_webpath . '/themes/' . $this->bo_theme . '/css/admin-campaign-bar/admin-campaign-bar.css', 'all', PHP_INT_MAX);
        $this->add_jquery();
        $this->addj_query_plugin(['scrollTo', 'alerts', 'chosen', 'autosize', 'fancybox']);
        $this->addj_query_plugin('growl', null, false);
        $this->add_jquery_ui(['ui.slider', 'ui.datepicker']);
        Media::add_js_def(['currencyFormatters' => Currency::get_javascript_formatters()]);
        $this->add_js([_PS_JS_DIR_ . 'admin.js', _PS_JS_DIR_ . 'tools.js', _PS_JS_DIR_ . 'jquery/plugins/timepicker/jquery-ui-timepicker-addon.js']);
        //loads specific javascripts for the admin theme
        $this->add_js(__PS_BASE_URI__ . $this->admin_webpath . '/themes/' . $this->bo_theme . '/js/vendor/bootstrap.min.js');
        $this->add_js(__PS_BASE_URI__ . $this->admin_webpath . '/themes/' . $this->bo_theme . '/js/vendor/modernizr.min.js');
        $this->add_js(__PS_BASE_URI__ . $this->admin_webpath . '/themes/' . $this->bo_theme . '/js/vendor/enquire.min.js');
        $this->add_js(__PS_BASE_URI__ . $this->admin_webpath . '/themes/' . $this->bo_theme . '/js/vendor/moment-with-langs.min.js');
        $this->add_js(__PS_BASE_URI__ . $this->admin_webpath . '/themes/' . $this->bo_theme . '/js/admin-theme.js');
        $this->add_js(__PS_BASE_URI__ . $this->admin_webpath . '/themes/' . $this->bo_theme . '/js/admin-campaign-bar/admin-campaign-bar.js');
        if (!$this->lite_display) {
            $this->add_js(__PS_BASE_URI__ . $this->admin_webpath . '/themes/' . $this->bo_theme . '/js/help.js');
        }
        if (!Tools::get_value('submitFormAjax')) {
            $this->add_js(_PS_JS_DIR_ . 'admin/notifications.js');
        }
        $this->add_synthetic_scheduler_js();
        // Execute Hook AdminController SetMedia
        Hook::trigger_event('actionAdminControllerSetMedia');
    }
    /**
     * Init context and dependencies, handles POST and GET
     *
     * @throws PrestaShopException
     */
    public function init(): void
    {
        // Has to be removed for the next Prestashop version
        global $current_index;
        parent::init();
        if (Tools::get_value('ajax')) {
            $this->ajax = '1';
        }
        /* Server Params */
        $protocol_link = Tools::using_secure_mode() && Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        $protocol_content = Tools::using_secure_mode() && Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        $this->context->link = new Link($protocol_link, $protocol_content);
        if (isset($_GET['logout'])) {
            $this->context->employee->logout();
        }
        if (isset($this->context->cookie->last_activity)) {
            $short_expire = defined('_TB_COOKIE_SHORT_EXPIRE_') ? _TB_COOKIE_SHORT_EXPIRE_ : 900;
            if ((int) $this->context->cookie->last_activity + (int) $short_expire < time()) {
                $this->context->employee->logout();
            } else {
                $this->context->cookie->last_activity = time();
            }
        }
        if ($this->controller_name != 'AdminLogin' && (!isset($this->context->employee) || !$this->context->employee->is_logged_back())) {
            if (isset($this->context->employee)) {
                $this->context->employee->logout();
            }
            $email = false;
            if (Tools::get_value('email') && Validate::is_email(Tools::get_value('email'))) {
                $email = Tools::get_value('email');
            }
            Tools::redirect_admin($this->context->link->get_admin_link('AdminLogin') . (!isset($_GET['logout']) && $this->controller_name != 'AdminNotFound' && Tools::get_value('controller') ? '&redirect=' . $this->controller_name : '') . ($email ? '&email=' . $email : ''));
        }
        // Set current index
        $current_index = 'index.php' . (($controller = Tools::get_value('controller')) ? '?controller=' . $controller : '');
        if ($back = Tools::get_value('back')) {
            $current_index .= '&back=' . urlencode($back);
        }
        static::$current_index = $current_index;
        $current_index = $current_index;
        if (Tools::get_int_value('liteDisplaying')) {
            $this->display_header = false;
            $this->display_header_javascript = true;
            $this->display_footer = false;
            $this->content_only = false;
            $this->lite_display = true;
        }
        if ($this->ajax && method_exists($this, 'ajaxPreprocess')) {
            $this->ajax_pre_process();
        }
        $this->context->smarty->assign(['table' => $this->table, 'current' => static::$current_index, 'token' => $this->token, 'stock_management' => (int) Configuration::get('PS_STOCK_MANAGEMENT')]);
        if ($this->display_header) {
            $this->context->smarty->assign('displayBackOfficeHeader', Hook::display_hook('displayBackOfficeHeader'));
        }
        $this->context->smarty->assign(['displayBackOfficeTop' => Hook::display_hook('displayBackOfficeTop'), 'submit_form_ajax' => Tools::get_int_value('submitFormAjax')]);
        Employee::set_last_connection_date($this->context->employee->id);
        $this->init_process();
        $this->init_breadcrumbs();
        $this->init_modal();
    }
    /**
     * Retrieve GET and POST value and translate them to actions
     */
    public function init_process(): void
    {
        $this->ensure_list_id_definition();
        // Manage list filtering
        if (Tools::is_submit('submitFilter' . $this->list_id) || $this->context->cookie->{'submitFilter' . $this->list_id} !== false || Tools::get_value($this->list_id . 'Orderby') || Tools::get_value($this->list_id . 'Orderway') || Tools::is_submit('submitFilterForced')) {
            $this->filter = true;
        }
        $this->id_object = Tools::get_int_value($this->identifier);
        /* Delete object image */
        if (isset($_GET['deleteImage'])) {
            if ($this->has_delete_permission()) {
                $this->action = 'delete_image';
            } else {
                $this->errors[] = Tools::display_error('You do not have permission to delete this.');
            }
        } elseif (isset($_GET['delete' . $this->table])) {
            /* Delete object */
            if ($this->has_delete_permission()) {
                $this->action = 'delete';
            } else {
                $this->errors[] = Tools::display_error('You do not have permission to delete this.');
            }
        } elseif ((isset($_GET['status' . $this->table]) || isset($_GET['status'])) && Tools::get_value($this->identifier)) {
            /* Change object statuts (active, inactive) */
            if ($this->has_edit_permission()) {
                $this->action = 'status';
            } else {
                $this->errors[] = Tools::display_error('You do not have permission to edit this.');
            }
        } elseif (isset($_GET['position'])) {
            /* Move an object */
            if ($this->has_edit_permission()) {
                $this->action = 'position';
            } else {
                $this->errors[] = Tools::display_error('You do not have permission to edit this.');
            }
        } elseif (Tools::is_submit('submitAdd' . $this->table) || Tools::is_submit('submitAdd' . $this->table . 'AndStay') || Tools::is_submit('submitAdd' . $this->table . 'AndPreview') || Tools::is_submit('submitAdd' . $this->table . 'AndBackToParent')) {
            // case 1: updating existing entry
            if ($this->id_object) {
                if ($this->has_edit_permission()) {
                    $this->action = 'save';
                    if (Tools::is_submit('submitAdd' . $this->table . 'AndStay')) {
                        $this->display = 'edit';
                    } else {
                        $this->display = 'list';
                    }
                } else {
                    $this->errors[] = Tools::display_error('You do not have permission to edit this.');
                }
            } else if ($this->has_add_permission()) {
                $this->action = 'save';
                if (Tools::is_submit('submitAdd' . $this->table . 'AndStay')) {
                    $this->display = 'edit';
                } else {
                    $this->display = 'list';
                }
            } else {
                $this->errors[] = Tools::display_error('You do not have permission to add this.');
            }
        } elseif (isset($_GET['add' . $this->table])) {
            if ($this->has_add_permission()) {
                $this->action = 'new';
                $this->display = 'add';
            } else {
                $this->errors[] = Tools::display_error('You do not have permission to add this.');
            }
        } elseif (isset($_GET['update' . $this->table]) && isset($_GET[$this->identifier])) {
            $this->display = 'edit';
            if (!$this->has_edit_permission()) {
                $this->errors[] = Tools::display_error('You do not have permission to edit this.');
            }
        } elseif (isset($_GET['view' . $this->table])) {
            if ($this->has_view_permission()) {
                $this->display = 'view';
                $this->action = 'view';
            } else {
                $this->errors[] = Tools::display_error('You do not have permission to view this.');
            }
        } elseif (isset($_GET['details' . $this->table])) {
            if ($this->has_view_permission()) {
                $this->display = 'details';
                $this->action = 'details';
            } else {
                $this->errors[] = Tools::display_error('You do not have permission to view this.');
            }
        } elseif (isset($_GET['export' . $this->table])) {
            if ($this->has_view_permission()) {
                $this->action = 'export';
            }
        } elseif (isset($_POST['submitReset' . $this->list_id])) {
            /* Cancel all filters for this tab */
            $this->action = 'reset_filters';
        } elseif (Tools::is_submit('submitOptions' . $this->table) || Tools::is_submit('submitOptions')) {
            /* Submit options list */
            $this->display = 'options';
            if ($this->has_edit_permission()) {
                $this->action = 'update_options';
            } else {
                $this->errors[] = Tools::display_error('You do not have permission to edit this.');
            }
        } elseif (Tools::get_value('action') && method_exists($this, 'process' . ucfirst(Tools::to_camel_case(Tools::get_value('action'))))) {
            $this->action = Tools::get_value('action');
        } elseif (Tools::is_submit('submitFields') && $this->required_database && $this->has_add_permission() && $this->has_delete_permission()) {
            $this->action = 'update_fields';
        } elseif (is_array($this->bulk_actions)) {
            $submit_bulk_actions = array_merge(['enableSelection' => ['text' => $this->l('Enable selection'), 'icon' => 'icon-power-off text-success'], 'disableSelection' => ['text' => $this->l('Disable selection'), 'icon' => 'icon-power-off text-danger']], $this->bulk_actions);
            foreach ($submit_bulk_actions as $bulk_action => $params) {
                if (Tools::is_submit('submitBulk' . $bulk_action . $this->table) || Tools::is_submit('submitBulk' . $bulk_action)) {
                    if ($bulk_action === 'delete') {
                        if ($this->has_delete_permission()) {
                            $this->action = 'bulk' . $bulk_action;
                            $this->boxes = Tools::get_array_value($this->table . 'Box');
                            if (empty($this->boxes) && $this->table == 'attribute') {
                                $this->boxes = Tools::get_array_value($this->table . '_valuesBox');
                            }
                        } else {
                            $this->errors[] = Tools::display_error('You do not have permission to delete this.');
                        }
                        break;
                    } elseif ($this->has_edit_permission()) {
                        $this->action = 'bulk' . $bulk_action;
                        $this->boxes = Tools::get_array_value($this->table . 'Box');
                    } else {
                        $this->errors[] = Tools::display_error('You do not have permission to edit this.');
                    }
                    break;
                } elseif (Tools::is_submit('submitBulk')) {
                    if ($bulk_action === 'delete') {
                        if ($this->has_delete_permission()) {
                            $this->action = 'bulk' . $bulk_action;
                            $this->boxes = Tools::get_array_value($this->table . 'Box');
                        } else {
                            $this->errors[] = Tools::display_error('You do not have permission to delete this.');
                        }
                        break;
                    } elseif ($this->has_edit_permission()) {
                        $this->action = 'bulk' . Tools::get_value('select_submitBulk');
                        $this->boxes = Tools::get_array_value($this->table . 'Box');
                    } else {
                        $this->errors[] = Tools::display_error('You do not have permission to edit this.');
                    }
                    break;
                }
            }
        } elseif (!empty($this->fields_options) && empty($this->fields_list)) {
            $this->display = 'options';
        }
    }
    /**
     * Set breadcrumbs array for the controller page
     *
     * @param int|null $tabId
     * @param array|null $tabs
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function init_breadcrumbs($tab_id = null, $tabs = null): void
    {
        if (is_null($tab_id)) {
            $tab_id = $this->id;
        }
        $tabs = Tab::recursive_tab($tab_id);
        $dummy = ['name' => '', 'href' => '', 'icon' => ''];
        $breadcrumbs2 = ['container' => $dummy, 'tab' => $dummy, 'action' => $dummy];
        if (isset($tabs[0])) {
            $this->add_meta_title($tabs[0]['name']);
            $breadcrumbs2['tab']['name'] = $tabs[0]['name'];
            $breadcrumbs2['tab']['href'] = __PS_BASE_URI__ . basename(_PS_ADMIN_DIR_) . '/' . $this->context->link->get_admin_link($tabs[0]['class_name']);
            if (!isset($tabs[1])) {
                $breadcrumbs2['tab']['icon'] = 'icon-' . $tabs[0]['class_name'];
            }
        }
        if (isset($tabs[1])) {
            $breadcrumbs2['container']['name'] = $tabs[1]['name'];
            $breadcrumbs2['container']['href'] = __PS_BASE_URI__ . basename(_PS_ADMIN_DIR_) . '/' . $this->context->link->get_admin_link($tabs[1]['class_name']);
            $breadcrumbs2['container']['icon'] = 'icon-' . $tabs[1]['class_name'];
        }
        /* content, edit, list, add, details, options, view */
        switch ($this->display) {
            case 'add':
                $breadcrumbs2['action']['name'] = $this->l('Add', null, null, false);
                $breadcrumbs2['action']['icon'] = 'icon-plus';
                break;
            case 'edit':
                $breadcrumbs2['action']['name'] = $this->l('Edit', null, null, false);
                $breadcrumbs2['action']['icon'] = 'icon-pencil';
                break;
            case '':
            case 'list':
                $breadcrumbs2['action']['name'] = $this->l('List', null, null, false);
                $breadcrumbs2['action']['icon'] = 'icon-th-list';
                break;
            case 'details':
            case 'view':
                $breadcrumbs2['action']['name'] = $this->l('View details', null, null, false);
                $breadcrumbs2['action']['icon'] = 'icon-zoom-in';
                break;
            case 'options':
                $breadcrumbs2['action']['name'] = $this->l('Options', null, null, false);
                $breadcrumbs2['action']['icon'] = 'icon-cogs';
                break;
            case 'generator':
                $breadcrumbs2['action']['name'] = $this->l('Generator', null, null, false);
                $breadcrumbs2['action']['icon'] = 'icon-flask';
                break;
        }
        $this->context->smarty->assign(['breadcrumbs2' => $breadcrumbs2, 'quick_access_current_link_name' => $breadcrumbs2['tab']['name'] . (isset($breadcrumbs2['action']) ? ' - ' . $breadcrumbs2['action']['name'] : ''), 'quick_access_current_link_icon' => $breadcrumbs2['container']['icon']]);
        /* BEGIN - Backward compatibility < 1.6.0.3 */
        if (isset($tabs[0])) {
            $this->breadcrumbs[] = $tabs[0]['name'];
        }
        $navigation_pipe = Configuration::get('PS_NAVIGATION_PIPE') ?: '>';
        $this->context->smarty->assign('navigationPipe', $navigation_pipe);
        /* END - Backward compatibility < 1.6.0.3 */
    }
    public function init_modal(): void
    {
        $this->context->smarty->assign(['check_url_fopen' => ini_get('allow_url_fopen') ? 'ok' : 'ko', 'check_openssl' => extension_loaded('openssl') ? 'ok' : 'ko', 'add_permission' => 1]);
    }
    /**
     * Display object details
     *
     * @return void
     */
    public function view_details()
    {
    }
    /**
     * Shortcut to set up a json success payload
     *
     * @param string $message Success message
     */
    public function json_confirmation($message): void
    {
        $this->json = true;
        $this->confirmations[] = $message;
        if ($this->status === '') {
            $this->status = 'ok';
        }
    }
    /**
     * Shortcut to set up a json error payload
     *
     * @param string $message Error message
     */
    public function json_error($message): void
    {
        $this->json = true;
        $this->errors[] = $message;
        if ($this->status === '') {
            $this->status = 'error';
        }
    }
    /**
     * @deprecated 1.5.0
     */
    public function ajax_process_get_module_quick_view(): void
    {
        Tools::display_as_deprecated();
    }
    /**
     * Update options and preferences
     *
     * @throws PrestaShopException
     */
    protected function process_update_options()
    {
        $this->before_update_options();
        $languages = Language::get_languages(false);
        $hide_multishop_checkbox = Shop::get_total_shops(false, null) < 2;
        foreach ($this->fields_options as $category_data) {
            if (!isset($category_data['fields'])) {
                continue;
            }
            $fields = $category_data['fields'];
            foreach ($fields as $field => $values) {
                if (isset($values['type']) && $values['type'] == 'selectLang') {
                    foreach ($languages as $lang) {
                        if (Tools::get_value($field . '_' . strtoupper((string) $lang['iso_code']))) {
                            $fields[$field . '_' . strtoupper((string) $lang['iso_code'])] = ['type' => 'select', 'cast' => 'strval', 'identifier' => 'mode', 'list' => $values['list']];
                        }
                    }
                }
            }
            // Cast and validate fields.
            foreach ($fields as $field => $values) {
                // ignore doNotProcess fields
                if (isset($options['doNotProcess']) && $options['doNotProcess']) {
                    continue;
                }
                // We don't validate fields with no visibility
                if (!$hide_multishop_checkbox && Shop::is_feature_active() && isset($values['visibility']) && $values['visibility'] > Shop::get_context()) {
                    continue;
                }
                // Apply cast before validating.
                if (array_key_exists('cast', $values)) {
                    $cast = $values['cast'];
                    if (array_key_exists('type', $values) && in_array($values['type'], ['textLang', 'textareaLang'])) {
                        foreach ($languages as $language) {
                            $lang_field = $field . '_' . $language['id_lang'];
                            $_POST[$lang_field] = Tools::cast_input($cast, Tools::get_value($lang_field));
                        }
                    } else {
                        $_POST[$field] = Tools::cast_input($cast, Tools::get_value($field));
                    }
                }
                // Check if field is required
                if (!Shop::is_feature_active() && isset($values['required']) && $values['required'] || Shop::is_feature_active() && isset($_POST['multishopOverrideOption'][$field]) && isset($values['required']) && $values['required']) {
                    if (isset($values['type']) && $values['type'] == 'textLang') {
                        foreach ($languages as $language) {
                            if (($value = Tools::get_value($field . '_' . $language['id_lang'])) == false && (string) $value != '0') {
                                $this->errors[] = sprintf(Tools::display_error('field %s is required.'), $values['title']);
                            }
                        }
                    } elseif (($value = Tools::get_value($field)) == false && (string) $value != '0') {
                        $this->errors[] = sprintf(Tools::display_error('field %s is required.'), $values['title']);
                    }
                }
                // Check field validator
                if (isset($values['type']) && $values['type'] == 'textLang') {
                    foreach ($languages as $language) {
                        if (Tools::get_value($field . '_' . $language['id_lang']) && isset($values['validation'])) {
                            $values_validation = $values['validation'];
                            if (!Validate::$values_validation(Tools::get_value($field . '_' . $language['id_lang']))) {
                                $this->errors[] = sprintf(Tools::display_error('field %s is invalid.'), $values['title']);
                            }
                        }
                    }
                } elseif (Tools::get_value($field) && isset($values['validation'])) {
                    $values_validation = $values['validation'];
                    if (!Validate::$values_validation(Tools::get_value($field))) {
                        $this->errors[] = sprintf(Tools::display_error('field %s is invalid.'), $values['title']);
                    }
                }
                // Set default value
                if (Tools::get_value($field) === false && isset($values['default'])) {
                    $_POST[$field] = $values['default'];
                }
            }
            if (!count($this->errors)) {
                foreach ($fields as $key => $options) {
                    // ignore doNotProcess fields
                    if (isset($options['doNotProcess']) && $options['doNotProcess']) {
                        continue;
                    }
                    if (Shop::is_feature_active() && isset($options['visibility']) && $options['visibility'] > Shop::get_context()) {
                        continue;
                    }
                    if (!$hide_multishop_checkbox && Shop::is_feature_active() && Shop::get_context() != Shop::CONTEXT_ALL && empty($options['no_multishop_checkbox']) && empty($_POST['multishopOverrideOption'][$key])) {
                        Configuration::delete_from_context($key);
                        continue;
                    }
                    // check if a method updateOptionFieldName is available
                    $method_name = 'updateOption' . Tools::to_camel_case($key, true);
                    if (method_exists($this, $method_name)) {
                        $this->{$method_name}(Tools::get_value($key));
                    } elseif (isset($options['type']) && in_array($options['type'], ['textLang', 'textareaLang'])) {
                        $list = [];
                        foreach ($languages as $language) {
                            $val = Tools::get_value($key . '_' . $language['id_lang']);
                            if ($this->validate_field($val, $options)) {
                                if (Validate::is_clean_html($val)) {
                                    $list[$language['id_lang']] = $val;
                                } else {
                                    $this->errors[] = Tools::display_error('Can not add configuration ' . $key . ' for lang ' . Language::get_iso_by_id((int) $language['id_lang']));
                                }
                            }
                        }
                        Configuration::update_value($key, $list, isset($values['validation']) && isset($options['validation']) && $options['validation'] == 'isCleanHtml');
                    } else {
                        $is_code_field = $options['type'] === 'code';
                        $val = $is_code_field ? Tools::get_value_raw($key) : Tools::get_value($key);
                        if ($this->validate_field($val, $options)) {
                            if ($is_code_field) {
                                Configuration::update_value_raw($key, $val);
                            } elseif (Validate::is_clean_html($val)) {
                                Configuration::update_value($key, $val);
                            } else {
                                $this->errors[] = Tools::display_error('Can not add configuration ' . $key);
                            }
                        }
                    }
                }
            }
        }
        $this->display = 'list';
        if (empty($this->errors)) {
            $this->confirmations[] = $this->_conf[6];
        }
    }
    /**
     * Can be overridden
     *
     * @return void
     */
    public function before_update_options()
    {
    }
    /**
     * @param mixed $value
     * @param array $field
     *
     * @return bool
     */
    protected function validate_field($value, $field)
    {
        if (isset($field['validation'])) {
            $valid_method_exists = method_exists('Validate', $field['validation']);
            if ((!isset($field['empty']) || !$field['empty'] || $value) && $valid_method_exists) {
                $field_validation = $field['validation'];
                if (!Validate::$field_validation($value)) {
                    $this->errors[] = Tools::display_error($field['title'] . ' : Incorrect value');
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * @return void
     */
    protected function redirect()
    {
        if ($this->errors || $this->warnings || $this->informations || $this->confirmations) {
            $token = Tools::get_value('token');
            $message_cache_path = _PS_CACHE_DIR_ . '/' . static::MESSAGE_CACHE_PATH . '-' . $token;
            file_put_contents($message_cache_path, '<?php
                $this->errors = ' . var_export($this->errors, true) . ';
                $this->warnings = ' . var_export($this->warnings, true) . ';
                $this->informations = ' . var_export($this->informations, true) . ';
                $this->confirmations = ' . var_export($this->confirmations, true) . ';
            ');
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($message_cache_path);
            }
        }
        Tools::redirect_admin($this->redirect_after);
    }
    /**
     * Add a info message to display at the top of the page
     *
     * @param string $msg
     */
    protected function display_information($msg)
    {
        $this->informations[] = $msg;
    }
    /**
     * Delete multiple items
     *
     * @return bool true if success
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function process_bulk_delete()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            $result = true;
            foreach ($this->boxes as $id) {
                $id = (int) $id;
                /** @var ObjectModel $objectToDelete */
                $object_to_delete = new $this->class_name($id);
                if (Validate::is_loaded_object($object_to_delete)) {
                    if ($this->deleted && property_exists($object_to_delete, 'deleted')) {
                        $object_to_delete->deleted = 1;
                        $deleted = $object_to_delete->update();
                    } else {
                        $deleted = $object_to_delete->delete();
                    }
                } else {
                    $deleted = false;
                }
                if ($deleted) {
                    Logger::add_log(sprintf($this->l('%s deletion', 'AdminTab', false, false), $this->class_name), 1, null, $this->class_name, (int) $object_to_delete->id, true, (int) $this->context->employee->id);
                } else {
                    $result = false;
                    $this->errors[] = sprintf(Tools::display_error('Can\'t delete #%d'), $id);
                }
            }
            if ($result) {
                $this->redirect_after = static::$current_index . '&conf=2&token=' . $this->token;
            } else {
                $this->errors[] = Tools::display_error('An error occurred while deleting this selection.');
            }
        } else {
            $this->errors[] = Tools::display_error('You must select at least one element to delete.');
        }
        return $result ?? false;
    }
    /**
     * @throws PrestaShopException
     */
    protected function ajax_process_open_help()
    {
        $help_class_name = $_GET['controller'];
        $popup_content = "<!doctype html>\n\t\t<html>\n\t\t\t<head>\n\t\t\t\t<meta charset='UTF-8'>\n\t\t\t\t<title>thirty bees Help</title>\n\t\t\t\t<script src='" . _PS_JS_DIR_ . "jquery/jquery-1.11.0.min.js'></script>\n\t\t\t\t<script src='" . _PS_JS_DIR_ . "admin.js'></script>\n\t\t\t\t<script src='" . _PS_JS_DIR_ . "tools.js'></script>\n\t\t\t\t<script>\n\t\t\t\t\thelp_class_name='" . addslashes((string) $help_class_name) . "';\n\t\t\t\t\tiso_user = '" . addslashes((string) $this->context->language->iso_code) . "'\n\t\t\t\t</script>\n\t\t\t\t<script src='themes/default/js/help.js'></script>\n\t\t\t\t<script>\n\t\t\t\t\t\$(function(){\n\t\t\t\t\t\tinitHelp();\n\t\t\t\t\t});\n\t\t\t\t</script>\n\t\t\t</head>\n\t\t\t<body><div id='help-container' class='help-popup'></div></body>\n\t\t</html>";
        $this->ajax_die($popup_content);
    }
    /**
     * Enable multiple items
     *
     * @return bool true if success
     *
     * @throws PrestaShopException
     */
    protected function process_bulk_enable_selection()
    {
        return $this->process_bulk_status_selection(1);
    }
    /**
     * Toggle status of multiple items
     *
     * @param bool $status
     *
     * @return bool true if success
     *
     * @throws PrestaShopException
     */
    protected function process_bulk_status_selection($status)
    {
        $result = true;
        if (is_array($this->boxes) && !empty($this->boxes)) {
            foreach ($this->boxes as $id) {
                /** @var ObjectModel $object */
                $object = new $this->class_name((int) $id);
                if (property_exists($object, 'active')) {
                    $object->set_fields_to_update(['active' => true]);
                    $object->active = (int) $status;
                    $result = $object->update() && $result;
                } else {
                    throw new Presta_Shop_Exception('property "active" is missing in object ' . $this->class_name);
                }
            }
        }
        return $result;
    }
    /**
     * Disable multiple items
     *
     * @return bool true if success
     *
     * @throws PrestaShopException
     */
    protected function process_bulk_disable_selection()
    {
        return $this->process_bulk_status_selection(0);
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function process_bulk_affect_zone()
    {
        $result = false;
        if (is_array($this->boxes) && !empty($this->boxes)) {
            /** @var Country|State $object */
            $object = new $this->class_name();
            $result = $object->affect_zone_to_selection(Tools::get_array_value($this->table . 'Box'), Tools::get_int_value('zone_to_affect'));
            if ($result) {
                $this->redirect_after = static::$current_index . '&conf=28&token=' . $this->token;
            }
            $this->errors[] = Tools::display_error('An error occurred while assigning a zone to the selection.');
        } else {
            $this->errors[] = Tools::display_error('You must select at least one element to assign a new zone.');
        }
        return $result;
    }
    /**
     * Adds javascript URI to list of javascript files included in page header
     *
     * @param string $uri uri to javascript file
     * @param boolean $checkPath if true, system will check if the javascript file exits on filesystem
     */
    public function add_javascript_uri($uri, $check_path): void
    {
        parent::add_javascript_uri(Media::get_uri_with_version($uri), $check_path);
    }
    /**
     * Adds a new stylesheet(s) to the page header.
     *
     * @param string|array $cssUri Path to CSS file, or list of css files like this : array(array(uri => media_type), ...)
     * @param string $cssMediaType
     * @param int|null $offset
     * @param bool $checkPath
     *
     * @return bool
     */
    public function add_css($css_uri, $css_media_type = 'all', $offset = null, $check_path = true)
    {
        if (!is_array($css_uri)) {
            $css_uri = [$css_uri => $css_media_type];
        }
        $converted = [];
        foreach ($css_uri as $css_file => $media) {
            if (is_string($css_file) && strlen($css_file) > 1) {
                $converted[Media::get_uri_with_version($css_file)] = $media;
            } else {
                $converted[Media::get_uri_with_version($media)] = $css_media_type;
            }
        }
        return parent::add_css($converted, $css_media_type, $offset, $check_path);
    }
    /**
     * Method that allows controllers to define their own custom permissions. To be overridden by subclasses
     *
     * Returns array of permission definitions. Example entry:
     *
     *  [
     *       ...
     *      [
     *          "permission" => 'action-buttons",
     *          "name" => "Buttons available to employee"
     *          "description" => "Here you can choose what action buttons can employee use"
     *          "levels" => [
     *              ...
     *              'none' => 'No buttons available',
     *              'invoice' => 'Employee can generate invoice',
     *              'send_email' => 'Employee can send email'
     *              'all' => 'Employee can use all buttons'
     *              ...
     *          ],
     *          "defaultLevel" => 'all'
     *      ]
     *      ...
     *  ]
     *
     * Controllers are responsible for enforcing selected permissions -- permission levels for current employee
     * can be retrieved by calling method getPermLevels
     *
     * @return array
     */
    public function get_perm_definitions()
    {
        return [];
    }
    /**
     * Returns permission levels for current employee. Returns map: permission -> level
     *
     * @return array
     * @throws PrestaShopException
     */
    public function get_perm_levels()
    {
        $perms = $this->get_perm_definitions();
        $levels = [];
        if ($perms) {
            $profile_id = $this->context->employee->id_profile;
            $group = preg_replace('#Controller$#', '', (string) preg_replace('#Core$#', '', static::class));
            foreach ($perms as $def) {
                $permission = $def['permission'];
                $level = Profile::get_profile_permission($profile_id, $group, $permission);
                if ($level === false) {
                    $levels[$permission] = $def['defaultLevel'];
                } else {
                    $levels[$permission] = $level;
                }
            }
        }
        return $levels;
    }
    /**
     * Extracts information about custom permissions from all admin controllers
     *
     * This method iterates over all php files in /controllers/admin directory, and use reflection to checks
     * if controller overrides method AdminControllerCore::getPermissions()
     *
     * For every controller that overrides permission, new instance is created and this method is called to retrieve
     * list of additional permissions
     *
     * @throws PrestaShopException
     */
    public static function get_controllers_permissions()
    {
        $permissions = [];
        $iterator = new Filesystem_Iterator(_PS_ADMIN_CONTROLLER_DIR_);
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ($file->is_file() && preg_match('#(.*)Controller\.php$#', $file->get_filename(), $matches)) {
                $controller_name = $matches[1];
                $class_name = $controller_name . 'Controller';
                try {
                    $reflection = new ReflectionMethod($class_name, 'getPermDefinitions');
                    if ($reflection->get_declaring_class()->get_name() != 'AdminControllerCore') {
                        /** @var AdminControllerCore $instance - subclass of admin controller */
                        $instance = new $class_name();
                        $permissions[$controller_name] = $instance->get_perm_definitions();
                    }
                } catch (Reflection_Exception $e) {
                    throw new Presta_Shop_Exception('Failed to resolve permissions for admin controller ' . $controller_name, 0, $e);
                }
            }
        }
        return $permissions;
    }
    /**
     * @throws PrestaShopException
     */
    protected function get_back_url_parameter(): string
    {
        $back = Tools::safe_output(Tools::get_value('back', ''));
        if (empty($back)) {
            $back = static::$current_index . '&token=' . $this->token;
        }
        if (!Validate::is_clean_html($back)) {
            throw new Presta_Shop_Exception(Tools::display_error('Parameter $back is invalid'));
        }
        return $back;
    }
    /**
     * @return bool
     */
    protected function has_delete_permission()
    {
        return $this->has_permission(Profile::PERMISSION_DELETE);
    }
    /**
     * Returns true, if current employee can create new records
     *
     * @return bool
     */
    protected function has_add_permission()
    {
        return $this->has_permission(Profile::PERMISSION_ADD);
    }
    /**
     * Returns true, if current employee has view permissions
     *
     * @return bool
     */
    protected function has_view_permission()
    {
        return $this->has_permission(Profile::PERMISSION_VIEW);
    }
    /**
     * Returns true, if current employee can edit existing records
     *
     * @return bool
     */
    protected function has_edit_permission()
    {
        return $this->has_permission(Profile::PERMISSION_EDIT);
    }
    /**
     * Returns true, if current employee has permission level
     *
     * @param string $permission
     *
     * @return bool
     */
    protected function has_permission($permission)
    {
        if (!Profile::is_valid_permission($permission)) {
            return false;
        }
        if (!isset($this->tab_access[$permission])) {
            return false;
        }
        return (bool) $this->tab_access[$permission];
    }
    /**
     * @return string|null
     */
    protected function resolve_order_by(?string $order_by)
    {
        if (!empty($order_by)) {
            return $order_by;
        }
        $prefix = $this->get_cookie_filter_prefix();
        if ($this->context->cookie->{$prefix . $this->list_id . 'Orderby'}) {
            return $this->context->cookie->{$prefix . $this->list_id . 'Orderby'};
        }
        if ($this->_order_by) {
            return $this->_order_by;
        }
        return $this->_default_order_by;
    }
    /**
     * @return string|null
     */
    protected function resolve_order_way(?string $order_way)
    {
        if (!empty($order_way)) {
            return $order_way;
        }
        $prefix = $this->get_cookie_filter_prefix();
        if ($this->context->cookie->{$prefix . $this->list_id . 'Orderway'}) {
            return $this->context->cookie->{$prefix . $this->list_id . 'Orderway'};
        }
        if ($this->_order_way) {
            return $this->_order_way;
        }
        return $this->_default_order_way;
    }
    /**
     * @param string $field
     *
     * @return array|bool|float|int|string|null
     */
    protected function get_list_field_filter_value($field)
    {
        $filter_name = $this->table . 'Filter_' . $field;
        if (Tools::get_isset($filter_name)) {
            return Tools::get_value($filter_name);
        }
        $cookie_filter_name = $this->get_cookie_filter_prefix() . $filter_name;
        return $this->context->cookie->{$cookie_filter_name} ?? null;
    }
    /**
     * @return void
     */
    protected function set_j_send_error_handling()
    {
        $this->post_process_handle_exceptions = false;
        static::get_error_handler()->set_error_response_handler(new J_Send_Error_Response(_PS_MODE_DEV_));
    }
    /**
     * Serialize list filter value
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function serialize_list_filter_value($value)
    {
        if (is_array($value)) {
            $filtered = array_filter($value);
            if ($filtered) {
                $json = json_encode($value);
                if ($json !== false) {
                    return $json;
                }
            }
            return '';
        }
        return (string) $value;
    }
    /**
     *
     *
     * @throws PrestaShopException
     */
    protected function get_field_image_settings(string $name): array
    {
        foreach ($this->field_image_settings as $field_image_setting) {
            if ((string) $field_image_setting['name'] === $name) {
                return $field_image_setting;
            }
        }
        throw new Presta_Shop_Exception("Image settings for field '{$name}' not found");
    }
}