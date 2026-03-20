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
use Thirtybees\Core\Error\Error_Utils;
/**
 * Class HelperListCore
 */
class Helper_List_Core extends Helper
{
    public const COLUMN_TYPE_TEXT = 'text';
    public const COLUMN_TYPE_BOOL = 'bool';
    public const COLUMN_TYPE_DATE = 'date';
    public const COLUMN_TYPE_DATETIME = 'datetime';
    public const COLUMN_TYPE_SELECT = 'select';
    public const COLUMN_TYPE_FLOAT = 'float';
    public const COLUMN_TYPE_PRICE = 'price';
    public const COLUMN_TYPE_DECIMAL = 'decimal';
    public const COLUMN_TYPE_PERCENT = 'percent';
    public const COLUMNT_TYPE_EDITABLE = 'editable';
    public const COLUMN_TYPE_INT = 'int';
    /**
     * @var array $cache_lang use to cache texts in current language
     */
    public static $cache_lang = [];
    /**
     * @var int Number of results in list
     */
    public $list_total = 0;
    /**
     * @var array Number of results in list per page (used in select field)
     */
    public $_pagination = [20, 50, 100, 300, 1000];
    /**
     * @var int Default number of results in list per page
     */
    public $_default_pagination = 50;
    /**
     * @var string ORDER BY clause determined by field/arrows in list header
     */
    public $order_by;
    /**
     * @var string Default ORDER BY clause when $orderBy is not defined
     */
    public $_default_order_by = false;
    /**
     * @var array : list of vars for button delete
     */
    public $tpl_delete_link_vars = [];
    /**
     * @var string Order way (ASC, DESC) determined by arrows in list header
     */
    public $order_way;
    /**
     * @var string
     */
    public $identifier;
    /**
     * @var bool $is_cms
     */
    public $is_cms = false;
    /**
     * @var string
     */
    public $position_identifier;
    /**
     * @var string | int
     */
    public $position_group_identifier;
    /**
     * @var string
     */
    public $table_id;
    /**
     * @var bool Content line is clickable if true
     */
    public $no_link = false;
    /**
     * @var string
     */
    public $list_id;
    /**
     * @var string
     */
    public $controller_name;
    /**
     * @var string
     */
    public $image_type;
    /**
     * @var array list of required actions for each list row
     */
    public $actions = [];
    /**
     * @var array list of row ids associated with a given action for witch this action have to not be available
     */
    public $list_skip_actions = [];
    /**
     * @var array
     */
    public $bulk_actions = [];
    /**
     * @var bool
     */
    public $force_show_bulk_actions = false;
    /**
     * @var string
     */
    public $specific_confirm_delete;
    /**
     * @var bool
     */
    public $color_on_background;
    /**
     * @var bool If true, activates color on hover
     */
    public $row_hover = true;
    /**
     * @var string|null If not null, a title will be added on that list
     */
    public $title;
    /**
     * @var bool ask for simple header : no filters, no paginations and no sorting
     */
    public $simple_header = false;
    /**
     * @var array
     */
    public $ajax_params = [];
    /**
     * @var int
     */
    public $page;
    /**
     * @var string
     */
    public $sql;
    /**
     * @var array Cache for query results
     */
    protected $_list = [];
    /**
     * @var array WHERE clause determined by filter fields
     */
    protected $_filter;
    /**
     * @var int $deleted
     */
    protected $deleted = 0;
    /**
     * @var array Customize list display
     *
     * align  : determine value alignment
     * prefix : displayed before value
     * suffix : displayed after value
     * image  : object image
     * icon   : icon determined by values
     * active : allow to toggle status
     */
    protected $fields_list;
    /**
     * @var Smarty_Internal_Template|string
     */
    protected $header_tpl = 'list_header.tpl';
    /**
     * @var Smarty_Internal_Template|string
     */
    protected $content_tpl = 'list_content.tpl';
    /**
     * @var Smarty_Internal_Template|string
     */
    protected $footer_tpl = 'list_footer.tpl';
    /**
     * @var string|false $shopLinkType
     */
    public $shop_link_type;
    /**
     * @var callable method used to generate link
     */
    public $link_url_callback;
    /**
     * @var string target window for drilldown link
     */
    public $link_url_target = '_self';
    /**
     * @var string|null
     */
    protected $list_error;
    /**
     * HelperListCore constructor.
     */
    public function __construct()
    {
        $this->base_folder = 'helpers/list/';
        $this->base_tpl = 'list.tpl';
        parent::__construct();
    }
    /**
     * Return an html list given the data to fill it up
     *
     * @param array $list entries to display (rows)
     * @param array $fieldsDisplay fields (cols)
     *
     * @return string html
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function generate_list($list, $fields_display)
    {
        // Append when we get a syntax error in SQL query
        if ($list === false) {
            $this->get_controller()->warnings[] = $this->l('Bad SQL query');
            return false;
        }
        $this->tpl = $this->create_template($this->base_tpl);
        $this->header_tpl = $this->create_template($this->header_tpl);
        $this->content_tpl = $this->create_template($this->content_tpl);
        $this->footer_tpl = $this->create_template($this->footer_tpl);
        $this->_list = $list;
        $this->fields_list = $this->prepare_fields($fields_display);
        $this->order_by = preg_replace('/^([a-z _]*!)/Ui', '', $this->order_by ?? '');
        $this->order_way = preg_replace('/^([a-z _]*!)/Ui', '', $this->order_way ?? '');
        $this->tpl->assign([
            'header' => $this->display_list_header(),
            // Display list header (filtering, pagination and column names)
            'content' => $this->display_list_content(),
            // Show the content of the table
            'footer' => $this->display_list_footer(),
        ]);
        return parent::generate();
    }
    /**
     * Display list header (filtering, pagination and column names)
     *
     * @return string
     *
     * @throws SmartyException
     */
    public function display_list_header()
    {
        if (is_null($this->list_id)) {
            $this->list_id = $this->table;
        }
        $id_cat = Tools::get_int_value('id_' . ($this->is_cms ? 'cms_' : '') . 'category');
        if (empty($token)) {
            $token = $this->token;
        }
        /* Determine total page number */
        $pagination = $this->get_selected_pagination();
        $total_pages = max(1, ceil($this->list_total / $pagination));
        $identifier = Tools::get_isset($this->identifier) ? '&' . $this->identifier . '=' . Tools::get_int_value($this->identifier) : '';
        $action = $this->current_index . $identifier . '&token=' . $token . '#' . $this->list_id;
        /* Determine current page number */
        $page = Tools::get_int_value('submitFilter' . $this->list_id);
        if ($page <= 0) {
            $page = 1;
        }
        if ($page > $total_pages) {
            $page = $total_pages;
        }
        $this->page = (int) $page;
        if (is_null($this->table_id) && $this->position_identifier && Tools::get_int_value($this->position_identifier, 1)) {
            $this->table_id = substr($this->identifier, 3, strlen($this->identifier));
        }
        if ($this->position_identifier && ($this->order_by == 'position' && $this->order_way != 'DESC')) {
            $table_dnd = true;
        }
        $prefix = str_replace(['admin', 'controller'], '', mb_strtolower((string) $this->controller_name));
        $ajax = false;
        $controller = $this->get_controller();
        $cookie = $this->context->cookie;
        foreach ($this->fields_list as $key => $params) {
            if (!isset($params['type'])) {
                $params['type'] = static::COLUMN_TYPE_TEXT;
            }
            $value_key = $prefix . $this->list_id . 'Filter_' . (array_key_exists('filter_key', $params) ? $params['filter_key'] : $key);
            if ($key == 'active' && str_contains((string) $key, '!')) {
                $keys = explode('!', (string) $params['filter_key']);
                $value_key = $keys[1];
            }
            $value = $cookie->{$value_key};
            if (!$value && Tools::get_isset($value_key)) {
                $value = Tools::get_value($value_key);
            }
            switch ($params['type']) {
                case static::COLUMN_TYPE_BOOL:
                    if (isset($params['ajax']) && $params['ajax']) {
                        $ajax = true;
                    }
                    break;
                case static::COLUMN_TYPE_DATE:
                case static::COLUMN_TYPE_DATETIME:
                    if ($value) {
                        if (is_string($value)) {
                            $value = json_decode($value, true);
                        }
                        if (!Validate::is_clean_html($value[0]) || !Validate::is_clean_html($value[1])) {
                            $value = '';
                        }
                    }
                    $name = $this->list_id . 'Filter_' . ($params['filter_key'] ?? $key);
                    $name_id = str_replace('!', '__', $name);
                    $params['id_date'] = $name_id;
                    $params['name_date'] = $name;
                    $controller->add_jquery_ui('ui.datepicker');
                    break;
                case static::COLUMN_TYPE_SELECT:
                    foreach ($params['list'] as $option_value => $option_display) {
                        if (isset($cookie->{$prefix . $this->list_id . 'Filter_' . $params['filter_key']}) && $cookie->{$prefix . $this->list_id . 'Filter_' . $params['filter_key']} == $option_value && $cookie->{$prefix . $this->list_id . 'Filter_' . $params['filter_key']} != '') {
                            $this->fields_list[$key]['select'][$option_value]['selected'] = 'selected';
                        }
                    }
                    break;
                case static::COLUMN_TYPE_TEXT:
                    if (!Validate::is_clean_html($value)) {
                        $value = '';
                    }
            }
            $params['value'] = $value;
            $this->fields_list[$key] = $params;
        }
        $has_value = false;
        $has_search_field = false;
        foreach ($this->fields_list as $field) {
            if (isset($field['value']) && $field['value'] !== false && $field['value'] !== '') {
                if (is_array($field['value']) && trim(implode('', $field['value'])) == '') {
                    continue;
                }
                $has_value = true;
                break;
            }
            if (!(isset($field['search']) && $field['search'] === false)) {
                $has_search_field = true;
            }
        }
        $this->context->smarty->assign(['page' => $page, 'simple_header' => $this->simple_header, 'total_pages' => $total_pages, 'selected_pagination' => $this->get_selected_pagination(), 'pagination' => $this->_pagination, 'list_total' => $this->list_total, 'sql' => str_replace('\n', ' ', str_replace('\r', '', (string) $this->sql)), 'table' => $this->table, 'bulk_actions' => $this->bulk_actions, 'show_toolbar' => $this->show_toolbar, 'toolbar_scroll' => $this->toolbar_scroll, 'toolbar_btn' => $this->toolbar_btn, 'has_bulk_actions' => $this->has_bulk_actions($has_value), 'filters_has_value' => $has_value]);
        // Include dnd javascript if list contains position update functionality
        if ($this->position_identifier && $this->order_by === 'position') {
            $controller->add_jquery_plugin('tablednd');
            $controller->add_js(_PS_JS_DIR_ . 'admin/dnd.js');
            Media::add_js_def(['come_from' => $this->list_id ?? $this->table, 'alternate' => $this->order_way === 'DESC']);
        }
        $this->header_tpl->assign(array_merge(['ajax' => $ajax, 'title' => array_key_exists('title', $this->tpl_vars) ? $this->tpl_vars['title'] : $this->title, 'show_filters' => count($this->_list) > 1 && $has_search_field || $has_value, 'currentIndex' => $this->current_index, 'action' => $action, 'order_way' => $this->order_way, 'order_by' => $this->order_by, 'fields_display' => $this->fields_list, 'delete' => in_array('delete', $this->actions), 'identifier' => $this->identifier, 'id_cat' => $id_cat, 'shop_link_type' => false, 'has_actions' => !empty($this->actions), 'table_id' => $this->table_id ?? null, 'table_dnd' => $table_dnd ?? null, 'name' => $name ?? null, 'name_id' => $name_id ?? null, 'row_hover' => $this->row_hover, 'list_id' => $this->list_id ?? $this->table, 'token' => $this->token], $this->tpl_vars));
        return $this->header_tpl->fetch();
    }
    /**
     * @param bool $hasValue
     *
     * @return bool
     */
    public function has_bulk_actions($has_value = false)
    {
        if ($this->force_show_bulk_actions) {
            return true;
        }
        if (count($this->_list) === 0 && !$has_value) {
            return false;
        }
        if (is_array($this->list_skip_actions) && count($this->list_skip_actions) && is_array($this->bulk_actions) && count($this->bulk_actions)) {
            foreach ($this->bulk_actions as $action => $data) {
                if (array_key_exists($action, $this->list_skip_actions)) {
                    foreach ($this->_list as $row) {
                        if (!in_array($row[$this->identifier], $this->list_skip_actions[$action])) {
                            return true;
                        }
                    }
                    return false;
                }
            }
        }
        return !empty($this->bulk_actions);
    }
    /**
     * @return false|string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function display_list_content()
    {
        $position_group_identifier = 0;
        if (isset($this->fields_list['position'])) {
            if ($this->position_identifier) {
                if (!is_null($this->position_group_identifier)) {
                    $position_group_identifier = Tools::get_isset($this->position_group_identifier) ? Tools::get_value($this->position_group_identifier) : $this->position_group_identifier;
                } else {
                    if ($this->is_cms) {
                        $parameter = 'id_cms_category';
                        $default_id = 1;
                    } else {
                        $parameter = 'id_category';
                        $default_id = (int) Category::get_root_category()->id;
                    }
                    $position_group_identifier = Tools::get_int_value($parameter, $default_id);
                }
            } else {
                $position_group_identifier = Category::get_root_category()->id;
            }
            $positions = array_map(fn(array $elem) => (int) $elem['position'], $this->_list);
            sort($positions);
        }
        // key_to_get is used to display the correct product category or cms category after a position change
        $identifier = in_array($this->identifier, ['id_category', 'id_cms_category']) ? '_parent' : '';
        if ($identifier) {
            $key_to_get = 'id_' . ($this->is_cms ? 'cms_' : '') . 'category' . $identifier;
        }
        foreach ($this->_list as $index => $tr) {
            $id = null;
            if (isset($tr[$this->identifier])) {
                $id = $tr[$this->identifier];
            }
            $name = $tr['name'] ?? null;
            if ($this->shop_link_type) {
                $this->_list[$index]['short_shop_name'] = mb_strlen((string) $tr['shop_name']) > 15 ? mb_substr((string) $tr['shop_name'], 0, 15) . '...' : $tr['shop_name'];
            }
            $is_first = true;
            // Check all available actions to add to the current list row
            $controller = $this->get_controller();
            foreach ($this->actions as $action) {
                //Check if the action is available for the current row
                if (!array_key_exists($action, $this->list_skip_actions) || !in_array($id, $this->list_skip_actions[$action])) {
                    $method_name = 'display' . ucfirst((string) $action) . 'Link';
                    if (method_exists($controller, $method_name)) {
                        $this->_list[$index][$action] = $controller->{$method_name}($this->token, $id, $name);
                    } elseif ($this->module instanceof Module && method_exists($this->module, $method_name)) {
                        $this->_list[$index][$action] = $this->module->{$method_name}($this->token, $id, $name);
                    } elseif (method_exists($this, $method_name)) {
                        $this->_list[$index][$action] = $this->{$method_name}($this->token, $id, $name);
                    }
                }
                if ($is_first && isset($this->_list[$index][$action])) {
                    $is_first = false;
                    if (!preg_match('/a\s*.*class/', $this->_list[$index][$action])) {
                        $this->_list[$index][$action] = preg_replace('/href\s*=\s*\"([^\"]*)\"/', 'href="$1" class="btn btn-default"', $this->_list[$index][$action]);
                    } elseif (!preg_match('/a\s*.*class\s*=\s*\".*btn.*\"/', $this->_list[$index][$action])) {
                        $this->_list[$index][$action] = preg_replace('/a(\s*.*)class\s*=\s*\"(.*)\"/', 'a $1 class="$2 btn btn-default"', $this->_list[$index][$action]);
                    }
                }
            }
            // @todo skip action for bulk actions
            // $this->_list[$index]['has_bulk_actions'] = true;
            foreach ($this->fields_list as $key => $params) {
                $tmp = explode('!', (string) $key);
                $key = $tmp[1] ?? $tmp[0];
                $data_value = $tr[$key] ?? null;
                if (isset($params['active'])) {
                    // If method is defined in calling controller, use it instead of the Helper method
                    if (method_exists($controller, 'displayEnableLink')) {
                        $calling_obj = $controller;
                    } elseif ($this->module && method_exists($this->module, 'displayEnableLink')) {
                        $calling_obj = $this->module;
                    } else {
                        $calling_obj = $this;
                    }
                    if (!isset($params['ajax'])) {
                        $params['ajax'] = false;
                    }
                    $this->_list[$index][$key] = $calling_obj->display_enable_link($this->token, $id, $data_value, $params['active'], Tools::get_int_value('id_category'), Tools::get_int_value('id_product'), $params['ajax']);
                } elseif (isset($params['activeVisu'])) {
                    $this->_list[$index][$key] = (bool) $data_value;
                } elseif (isset($params['position'])) {
                    $this->_list[$index][$key] = ['position' => $data_value, 'position_url_down' => $this->current_index . (isset($key_to_get) ? '&' . $key_to_get . '=' . (int) $position_group_identifier : '') . '&' . $this->position_identifier . '=' . $id . '&way=1&position=' . ((int) $tr['position'] + 1) . '&token=' . $this->token, 'position_url_up' => $this->current_index . (isset($key_to_get) ? '&' . $key_to_get . '=' . (int) $position_group_identifier : '') . '&' . $this->position_identifier . '=' . $id . '&way=0&position=' . ((int) $tr['position'] - 1) . '&token=' . $this->token];
                } elseif (isset($params['image'])) {
                    // item_id is the product id in a product image context, else it is the image id.
                    $item_id = isset($params['image_id']) ? $tr[$params['image_id']] : $id;
                    if ($params['image'] != 'p') {
                        $path_to_image = _PS_IMG_DIR_ . $params['image'] . '/' . $item_id . (isset($tr['id_image']) ? '-' . (int) $tr['id_image'] : '') . '.' . $this->image_type;
                        $this->_list[$index][$key] = Image_Manager::thumbnail($path_to_image, $this->table . '_mini_' . $item_id . '_' . $this->context->shop->id . '.' . $this->image_type, 45, $this->image_type);
                    } else {
                        $this->_list[$index][$key] = Image_Manager::get_product_image_thumbnail_tag($tr['id_image']);
                    }
                } elseif (isset($params['icon']) && (isset($params['icon'][$data_value]) || isset($params['icon']['default']))) {
                    $default_icon = 'unknown.gif';
                    if (isset($params['icon']['default'])) {
                        if (is_array($params['icon']['default'])) {
                            $default_icon = $params['icon']['default']['src'];
                        } else {
                            $default_icon = $params['icon']['default'];
                        }
                    }
                    $icon_value = $params['icon'][$data_value] ?? $default_icon;
                    if (is_array($icon_value)) {
                        $this->_list[$index][$key] = $icon_value;
                    } else {
                        $this->_list[$index][$key] = ['src' => $icon_value, 'alt' => sprintf($this->l('Value: %s'), $data_value)];
                    }
                    // backwards compatibility for build-in icon files stored in img/admin directory
                    if (isset($this->_list[$index][$key]['src'])) {
                        $icon_file = $this->_list[$index][$key]['src'];
                        if (file_exists(_PS_IMG_DIR_ . 'admin/' . $icon_file)) {
                            $this->_list[$index][$key]['src'] = _PS_ADMIN_IMG_ . $icon_file;
                        }
                    }
                } elseif (isset($params['type']) && $params['type'] == static::COLUMN_TYPE_FLOAT) {
                    $this->_list[$index][$key] = rtrim(rtrim((string) $data_value, '0'), '.');
                } elseif (isset($data_value)) {
                    $converted_value = $data_value;
                    if (isset($params['callback'])) {
                        try {
                            $callback_obj = $params['callback_object'] ?? $controller;
                            $converted_value = call_user_func_array([$callback_obj, $params['callback']], [$data_value, $tr]);
                        } catch (Throwable $e) {
                            $error_handler = Service_Locator::get_instance()->get_error_handler();
                            $error_handler->log_fatal_error(Error_Utils::describe_exception($e));
                        }
                    }
                    $this->_list[$index][$key] = $converted_value;
                }
            }
        }
        $this->content_tpl->assign(array_merge($this->tpl_vars, ['shop_link_type' => false, 'name' => $name ?? null, 'position_identifier' => $this->position_identifier, 'identifier' => $this->identifier, 'table' => $this->table, 'token' => $this->token, 'color_on_bg' => $this->color_on_background, 'position_group_identifier' => $position_group_identifier ?? false, 'bulk_actions' => $this->bulk_actions, 'positions' => $positions ?? null, 'order_by' => $this->order_by, 'order_way' => $this->order_way, 'is_cms' => $this->is_cms, 'fields_display' => $this->fields_list, 'list' => $this->_list, 'actions' => $this->actions, 'no_link' => $this->no_link, 'current_index' => $this->current_index, 'linkUrlCallback' => is_callable($this->link_url_callback) ? $this->link_url_callback : null, 'linkUrlTarget' => $this->link_url_target, 'view' => in_array('view', $this->actions), 'edit' => in_array('edit', $this->actions), 'has_actions' => !empty($this->actions), 'list_skip_actions' => $this->list_skip_actions, 'row_hover' => $this->row_hover, 'list_id' => $this->list_id ?? $this->table, 'checked_boxes' => Tools::get_array_value(($this->list_id ?? $this->table) . 'Box'), 'list_error' => $this->list_error]));
        return $this->content_tpl->fetch();
    }
    /**
     * Fetch the template for action enable
     *
     * @param string $token
     * @param string $id
     * @param bool $value state enabled or not
     * @param string $active status
     * @param int|null $idCategory
     * @param int|null $idProduct
     * @param bool $ajax
     *
     * @return string
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function display_enable_link($token, $id, $value, $active, $id_category = null, $id_product = null, $ajax = false)
    {
        $tpl_enable = $this->create_template('list_action_enable.tpl');
        $tpl_enable->assign(['ajax' => $ajax, 'enabled' => (bool) $value, 'url_enable' => $this->current_index . '&' . $this->identifier . '=' . $id . '&' . $active . $this->table . ($ajax ? '&action=' . $active . $this->table . '&ajax=' . (int) $ajax : '') . ((int) $id_category && (int) $id_product ? '&id_category=' . (int) $id_category : '') . ($this->page && $this->page > 1 ? '&page=' . (int) $this->page : '') . '&token=' . ($token != null ? $token : $this->token)]);
        return $tpl_enable->fetch();
    }
    /**
     * Close list table and submit button
     *
     * @throws SmartyException
     */
    public function display_list_footer()
    {
        if (is_null($this->list_id)) {
            $this->list_id = $this->table;
        }
        $this->footer_tpl->assign(array_merge($this->tpl_vars, ['current' => $this->current_index, 'list_id' => $this->list_id, 'token' => $this->token]));
        return $this->footer_tpl->fetch();
    }
    /**
     * Display duplicate action link
     *
     * @param string|null $token
     * @param int $id
     * @param string|null $name
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function display_duplicate_link($token, $id, $name = null)
    {
        $tpl = $this->create_template('list_action_duplicate.tpl');
        if (!array_key_exists('Duplicate', static::$cache_lang)) {
            static::$cache_lang['Duplicate'] = $this->l('Duplicate');
        }
        if (!array_key_exists('Copy images too?', static::$cache_lang)) {
            static::$cache_lang['Copy images too?'] = $this->l('This will copy the images too. If you wish to proceed, click "Yes". If not, click "No".');
        }
        $duplicate = $this->current_index . '&' . $this->identifier . '=' . $id . '&duplicate' . $this->table;
        $confirm = static::$cache_lang['Copy images too?'];
        if ($this->table == 'product' && !Image::has_images($this->context->language->id, (int) $id)) {
            $confirm = '';
        }
        $tpl->assign(['href' => $this->current_index . '&' . $this->identifier . '=' . $id . '&view' . $this->table . '&token=' . ($token != null ? $token : $this->token), 'action' => static::$cache_lang['Duplicate'], 'confirm' => $confirm, 'location_ok' => $duplicate . '&token=' . ($token != null ? $token : $this->token), 'location_ko' => $duplicate . '&noimage=1&token=' . ($token ?: $this->token)]);
        return $tpl->fetch();
    }
    /**
     * Display action show details of a table row
     * This action need an ajax request with a return like this:
     *   {
     *     use_parent_structure: true // If false, data need to be an html
     *     data:
     *       [
     *         {field_name: 'value'}
     *       ],
     *     fields_display: // attribute $fields_list of the admin controller
     *   }
     * or somethins like this:
     *   {
     *     use_parent_structure: false // If false, data need to be an html
     *     data:
     *       '<p>My html content</p>',
     *     fields_display: // attribute $fields_list of the admin controller
     *   }
     *
     * @param string|null $token
     * @param int $id
     * @param string|null $name
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function display_details_link($token, $id, $name = null)
    {
        $tpl = $this->create_template('list_action_details.tpl');
        if (!array_key_exists('Details', static::$cache_lang)) {
            static::$cache_lang['Details'] = $this->l('Details');
        }
        $ajax_params = $this->ajax_params;
        if (!is_array($ajax_params) || !isset($ajax_params['action'])) {
            $ajax_params['action'] = 'details';
        }
        $tpl->assign(['id' => $id, 'href' => $this->current_index . '&' . $this->identifier . '=' . $id . '&details' . $this->table . '&token=' . ($token != null ? $token : $this->token), 'controller' => str_replace('Controller', '', $this->get_controller()::class), 'token' => $token != null ? $token : $this->token, 'action' => static::$cache_lang['Details'], 'params' => $ajax_params, 'json_params' => json_encode($ajax_params)]);
        return $tpl->fetch();
    }
    /**
     * Display view action link
     *
     * @param string|null $token
     * @param int $id
     * @param string|null $name
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function display_view_link($token, $id, $name = null)
    {
        $tpl = $this->create_template('list_action_view.tpl');
        if (!array_key_exists('View', static::$cache_lang)) {
            static::$cache_lang['View'] = $this->l('View');
        }
        $tpl->assign(['href' => $this->current_index . '&' . $this->identifier . '=' . $id . '&view' . $this->table . '&token=' . ($token != null ? $token : $this->token), 'action' => static::$cache_lang['View']]);
        return $tpl->fetch();
    }
    /**
     * Display edit action link
     *
     * @param string|null $token
     * @param int $id
     * @param string|null $name
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function display_edit_link($token, $id, $name = null)
    {
        $tpl = $this->create_template('list_action_edit.tpl');
        if (!array_key_exists('Edit', static::$cache_lang)) {
            static::$cache_lang['Edit'] = $this->l('Edit');
        }
        $tpl->assign(['href' => $this->current_index . '&' . $this->identifier . '=' . $id . '&update' . $this->table . ($this->page && $this->page > 1 ? '&page=' . (int) $this->page : '') . '&token=' . ($token != null ? $token : $this->token), 'action' => static::$cache_lang['Edit'], 'id' => $id]);
        return $tpl->fetch();
    }
    /**
     * Display delete action link
     *
     * @param string|null $token
     * @param int $id
     * @param string|null $name
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function display_delete_link($token, $id, $name = null)
    {
        $tpl = $this->create_template('list_action_delete.tpl');
        if (!array_key_exists('Delete', static::$cache_lang)) {
            static::$cache_lang['Delete'] = $this->l('Delete');
        }
        if (!array_key_exists('DeleteItem', static::$cache_lang)) {
            static::$cache_lang['DeleteItem'] = $this->l('Delete selected item?', 'Helper', true, false);
        }
        if (!array_key_exists('Name', static::$cache_lang)) {
            static::$cache_lang['Name'] = $this->l('Name:', 'Helper', true, false);
        }
        if (!is_null($name)) {
            $name = addcslashes('\n\n' . static::$cache_lang['Name'] . ' ' . $name, '\'');
        }
        $data = [$this->identifier => $id, 'href' => $this->current_index . '&' . $this->identifier . '=' . $id . '&delete' . $this->table . '&token=' . ($token != null ? $token : $this->token), 'action' => static::$cache_lang['Delete']];
        if ($this->specific_confirm_delete !== false) {
            $data['confirm'] = !is_null($this->specific_confirm_delete) ? '\r' . $this->specific_confirm_delete : Tools::safe_output(static::$cache_lang['DeleteItem'] . $name);
        }
        $tpl->assign(array_merge($this->tpl_delete_link_vars, $data));
        return $tpl->fetch();
    }
    /**
     * Display default action link
     *
     * @param string|null $token
     * @param int $id
     * @param string|null $name
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function display_default_link($token, $id, $name = null)
    {
        $tpl = $this->create_template('list_action_default.tpl');
        if (!array_key_exists('Default', static::$cache_lang)) {
            static::$cache_lang['Default'] = $this->l('Default');
        }
        $tpl->assign(['href' => $this->current_index . '&' . $this->identifier . '=' . (int) $id . '&default' . $this->table . '&token=' . ($token != null ? $token : $this->token), 'action' => static::$cache_lang['Default'], 'name' => $name]);
        return $tpl->fetch();
    }
    /**
     * @return int
     */
    protected function get_selected_pagination()
    {
        return static::resolve_pagination($this->list_id, $this->context->cookie, $this->_pagination, $this->_default_pagination);
    }
    /**
     *
     * @return int
     */
    public static function resolve_pagination(string $list_id, Cookie $cookie, array $pagination, int $default_pagination)
    {
        if ($pagination) {
            $value = static::resolve_pagination_value($list_id, $cookie, $default_pagination);
            if (in_array($value, $pagination)) {
                return $value;
            }
            if (in_array($default_pagination, $pagination)) {
                return $default_pagination;
            }
            return $pagination[0];
        }
        trigger_error("Pagination not set for list {$list_id}", E_USER_WARNING);
        return 20;
    }
    /**
     *
     * @return int
     */
    protected static function resolve_pagination_value(string $list_id, Cookie $cookie, int $default_pagination)
    {
        $pagination_key = $list_id . '_pagination';
        $pagination = Tools::get_int_value($pagination_key);
        if ($pagination > 0) {
            return $pagination;
        }
        if (isset($cookie->{$pagination_key})) {
            $pagination = (int) $cookie->{$pagination_key};
            if ($pagination > 0) {
                return $pagination;
            }
        }
        return $default_pagination;
    }
    /**
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function prepare_fields(array $fields): array
    {
        if (Shop::is_feature_active() && ($this->shop_link_type === 'shop' || $this->shop_link_type === 'shop_group')) {
            if (!isset($fields['shop_name'])) {
                $shops = [];
                foreach (Shop::get_shops(false) as $shop) {
                    $shops[(int) $shop['id_shop']] = (string) $shop['name'];
                }
                $fields['shop_name'] = ['title' => $this->shop_link_type === 'shop' ? $this->l('Shop') : $this->l('Shop group'), 'filter_type' => 'int', 'filter_key' => 'shop!id_' . $this->shop_link_type, 'orderby' => true, 'type' => static::COLUMN_TYPE_SELECT, 'list' => $shops];
            }
        }
        return $fields;
    }
    /**
     * @param string|null $listError
     *
     * @return $this
     */
    public function set_list_error($list_error)
    {
        $this->list_error = $list_error;
        return $this;
    }
}