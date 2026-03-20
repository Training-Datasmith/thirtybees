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
use Thirtybees\Core\Error\Error_Handler;
use Thirtybees\Core\Error\Error_Utils;
/**
 * Class ControllerCore
 */
abstract class Controller_Core
{
    /** @var array List of CSS files */
    public $css_files = [];
    /** @var array List of JavaScript files */
    public $js_files = [];
    /** @var bool If AJAX parameter is detected in request, set this flag to true */
    public $ajax = false;
    /** @var string Controller type. Possible values: 'front', 'modulefront', 'admin', 'moduleadmin' */
    public $controller_type;
    /** @var string Controller name */
    public $php_self;
    /** @var Context */
    protected $context;
    /** @var bool Set to true to display page header */
    protected bool $display_header;
    /** @var bool Set to true to display page header javascript */
    protected bool $display_header_javascript;
    /** @var string Template filename for the page content */
    protected $template;
    /** @var string Set to true to display page footer */
    protected bool $display_footer;
    /** @var bool Set to true to only render page content (used to get iframe content) */
    protected $content_only = false;
    /** @var bool If set to true, page content and messages will be encoded to JSON before responding to AJAX request */
    protected $json = false;
    /** @var string JSON response status string */
    protected $status = '';
    /**
     * @see Controller::run()
     * @var string|null Redirect link. If not empty, the user will be redirected after initializing and processing input.
     */
    protected $redirect_after;
    /**
     * @var array errors array
     */
    public $errors = [];
    /**
     * ControllerCore constructor.
     */
    public function __construct()
    {
        if (is_null($this->display_header)) {
            $this->display_header = true;
        }
        if (is_null($this->display_header_javascript)) {
            $this->display_header_javascript = true;
        }
        if (is_null($this->display_footer)) {
            $this->display_footer = true;
        }
        $this->context = Context::get_context();
        $this->context->controller = $this;
        // Usage of ajax parameter is deprecated
        $this->ajax = Tools::get_value('ajax') || Tools::is_submit('ajax');
        if (!headers_sent() && isset($_SERVER['HTTP_USER_AGENT']) && (str_contains((string) $_SERVER['HTTP_USER_AGENT'], 'MSIE') || str_contains((string) $_SERVER['HTTP_USER_AGENT'], 'Trident'))) {
            header('X-UA-Compatible: IE=edge,chrome=1');
        }
    }
    /**
     * returns a new instance of this controller
     *
     * @param string $className
     * @param bool $auth
     * @param bool $ssl
     *
     * @return Controller
     *
     * @throws PrestaShopException
     * @deprecated 1.4.0
     */
    public static function get_controller($class_name, $auth = false, $ssl = false)
    {
        Tools::display_as_deprecated();
        return Service_Locator::get_instance()->get_controller($class_name);
    }
    /**
     * thirty bees' new coding style dictates that camelCase should be used
     * rather than snake_case
     * These magic methods provide backwards compatibility for modules/themes/whatevers
     * that still access properties via their snake_case names
     *
     * @param string $property Property name
     */
    public function &__get(string $property): mixed
    {
        if (property_exists($this, $property)) {
            return $this->{$property};
        }
        // Property to camelCase for backwards compatibility
        $camel_case_property = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));
        if (property_exists($this, $camel_case_property)) {
            return $this->{$camel_case_property};
        }
        return $this->{$property};
    }
    /**
     * thirty bees' new coding style dictates that camelCase should be used
     * rather than snake_case
     * These magic methods provide backwards compatibility for modules/themes/whatevers
     * that still access properties via their snake_case names
     *
     *
     * @return void
     */
    public function __set(string $property, mixed $value)
    {
        $blacklist = ['_select', '_join', '_where', '_group', '_having', '_conf', '_lang'];
        // Property to camelCase for backwards compatibility
        $snake_case_property = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));
        if (!in_array($property, $blacklist) && property_exists($this, $snake_case_property)) {
            $this->{$snake_case_property} = $value;
        } else {
            $this->{$property} = $value;
        }
    }
    /**
     * Starts the controller process
     *
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function run(): void
    {
        $this->init();
        if ($this->check_access()) {
            if (!$this->content_only && ($this->display_header || isset($this->class_name) && $this->class_name)) {
                $this->set_media();
            }
            $this->post_process();
            if (!empty($this->redirect_after)) {
                $this->redirect();
            }
            if (!$this->content_only && ($this->display_header || isset($this->class_name) && $this->class_name)) {
                $this->init_header();
            }
            if ($this->view_access()) {
                $this->init_content();
            } else {
                $this->errors[] = Tools::display_error('Access denied.');
            }
            if (!$this->content_only && ($this->display_footer || isset($this->class_name) && $this->class_name)) {
                $this->init_footer();
            }
            if ($this->ajax) {
                $action = Tools::to_camel_case(Tools::get_value('action'), true);
                if (!empty($action) && method_exists($this, 'displayAjax' . $action)) {
                    $this->{'displayAjax' . $action}();
                } elseif (method_exists($this, 'displayAjax')) {
                    $this->display_ajax();
                }
            } else {
                $this->display();
            }
        } else {
            $this->init_cursed_page();
            if (isset($this->layout)) {
                $this->smarty_output_content($this->layout);
            }
        }
    }
    /**
     * Initialize the page
     *
     * @throws PrestaShopException
     */
    public function init(): void
    {
        if (!defined('_PS_BASE_URL_')) {
            define('_PS_BASE_URL_', Tools::get_shop_domain(true));
        }
        if (!defined('_PS_BASE_URL_SSL_')) {
            define('_PS_BASE_URL_SSL_', Tools::get_shop_domain_ssl(true));
        }
    }
    /**
     * Check if the controller is available for the current user/visitor
     */
    abstract public function check_access();
    /**
     * Sets default media list for this controller
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    abstract public function set_media();
    /**
     * Do the page treatment: process input, process AJAX, etc.
     */
    abstract public function post_process();
    /**
     * Redirects to $this->redirect_after after the process if there is no error
     */
    abstract protected function redirect();
    /**
     * Assigns Smarty variables for the page header
     */
    abstract public function init_header();
    /**
     * Check if the current user/visitor has valid view permissions
     */
    abstract public function view_access();
    /**
     * Assigns Smarty variables for the page main content
     */
    abstract public function init_content();
    /**
     * Assigns Smarty variables for the page footer
     */
    abstract public function init_footer();
    /**
     * Displays page view
     */
    abstract public function display();
    /**
     * Assigns Smarty variables when access is forbidden
     */
    abstract public function init_cursed_page();
    /**
     * Renders controller templates and generates page content
     *
     * @param array|string $content Template file(s) to be rendered
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    protected function smarty_output_content($content)
    {
        $this->context->cookie->write();
        echo $this->get_smarty_output_content($content);
    }
    /**
     * Generates page content for controller templates
     *
     * @param string|array $content
     *
     *
     * @throws SmartyException
     */
    protected function get_smarty_output_content($content): string
    {
        $html = '';
        if (is_array($content)) {
            foreach ($content as $tpl) {
                $html .= $this->context->smarty->fetch($tpl);
            }
        } else {
            $html = $this->context->smarty->fetch($content);
        }
        $html = trim((string) $html);
        $debug_script = $this->get_error_messages_script();
        if ($debug_script) {
            return str_replace('</body>', $debug_script . '</body>', $html);
        }
        return $html;
    }
    /**
     * Sets page header display
     *
     * @param bool $display
     */
    public function display_header($display = true): void
    {
        $this->display_header = $display;
    }
    /**
     * Sets page header javascript display
     *
     * @param bool $display
     */
    public function display_header_java_script($display = true): void
    {
        $this->display_header_javascript = $display;
    }
    /**
     * Sets page header display
     *
     * @param bool $display
     */
    public function display_footer($display = true): void
    {
        $this->display_footer = $display;
    }
    /**
     * Sets template file for page content output
     *
     * @param string $template
     */
    public function set_template($template): void
    {
        $this->template = $template;
    }
    /**
     * Set $this->redirect_after that will be used by redirect() after the process
     */
    public function set_redirect_after($url): void
    {
        $this->redirect_after = $url;
    }
    /**
     * Removes CSS stylesheet(s) from the queued stylesheet list
     *
     * @param string|array $cssUri Path to CSS file or an array like: array(array(uri => media_type), ...)
     * @param string $cssMediaType
     * @param bool $checkPath
     */
    public function remove_css($css_uri, $css_media_type = 'all', $check_path = true): void
    {
        if (!is_array($css_uri)) {
            $css_uri = [$css_uri];
        }
        foreach ($css_uri as $css_file => $media) {
            if (is_string($css_file) && strlen($css_file) > 1) {
                if ($check_path) {
                    $css_path = Media::get_css_path($css_file, $media);
                } else {
                    $css_path = [$css_file => $media];
                }
            } else if ($check_path) {
                $css_path = Media::get_css_path($media, $css_media_type);
            } else {
                $css_path = [$media => $css_media_type];
            }
            if ($css_path && isset($this->css_files[key($css_path)]) && $this->css_files[key($css_path)] == reset($css_path)) {
                unset($this->css_files[key($css_path)]);
            }
        }
    }
    /**
     * Removes JS file(s) from the queued JS file list
     *
     * @param string|array $jsUri Path to JS file or an array like: array(uri, ...)
     * @param bool $checkPath
     */
    public function remove_js($js_uri, $check_path = true): void
    {
        if (is_array($js_uri)) {
            foreach ($js_uri as $js_file) {
                $js_path = $js_file;
                if ($check_path) {
                    $js_path = Media::get_js_path($js_file);
                }
                if ($js_path && in_array($js_path, $this->js_files)) {
                    unset($this->js_files[array_search($js_path, $this->js_files)]);
                }
            }
        } else {
            $js_path = $js_uri;
            if ($check_path) {
                $js_path = Media::get_js_path($js_uri);
            }
            if ($js_path) {
                unset($this->js_files[array_search($js_path, $this->js_files)]);
            }
        }
    }
    /**
     * Adds jQuery library file to queued JS file list
     *
     * @param string|null $version jQuery library version
     * @param string|null $folder jQuery file folder
     * @param bool $minifier If set tot true, a minified version will be included.
     */
    public function add_jquery($version = null, $folder = null, $minifier = true): void
    {
        $this->add_js(Media::get_jquery_path($version, $folder, $minifier), false);
    }
    /**
     * Adds a new JavaScript file(s) to the page header.
     *
     * @param string|array $jsUri Path to JS file or an array like: array(uri, ...)
     * @param bool $checkPath
     */
    public function add_js($js_uri, $check_path = true): void
    {
        if (is_array($js_uri)) {
            foreach ($js_uri as $js_file) {
                $this->add_javascript_uri($js_file, $check_path);
            }
        } else {
            $this->add_javascript_uri($js_uri, $check_path);
        }
    }
    /**
     * Adds javascript URI to list of javascript files included in page header
     *
     * @param string $uri uri to javascript file
     * @param boolean $checkPath if true, system will check if the javascript file exits on filesystem
     */
    public function add_javascript_uri($uri, $check_path): void
    {
        if ($check_path) {
            // remove query parameters from uri
            $parts = explode('?', $uri);
            // resolve uri path
            $uri = Media::get_js_path($parts[0]);
            // add back query parameters
            if ($uri && isset($parts[1]) && $parts[1]) {
                $uri .= '?' . $parts[1];
            }
        }
        if ($uri && !in_array($uri, $this->js_files)) {
            $this->js_files[] = $uri;
        }
    }
    /**
     * Adds jQuery UI component(s) to queued JS file list
     *
     * @param string|array $component
     * @param string $theme
     * @param bool $checkDependencies
     */
    public function add_jquery_ui($component, $theme = 'base', $check_dependencies = true): void
    {
        if (!is_array($component)) {
            $component = [$component];
        }
        foreach ($component as $ui) {
            $ui_path = Media::get_jquery_ui_path($ui, $theme, $check_dependencies);
            $this->add_css($ui_path['css'], 'all', false);
            $this->add_js($ui_path['js'], false);
        }
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
            $css_uri = [$css_uri];
        }
        $result = count($css_uri) > 0;
        foreach ($css_uri as $css_file => $media) {
            if (is_string($css_file) && strlen($css_file) > 1) {
                if ($check_path) {
                    $css_path = Media::get_css_path($css_file, $media);
                } else {
                    $css_path = [$css_file => $media];
                }
            } else if ($check_path) {
                $css_path = Media::get_css_path($media, $css_media_type);
            } else {
                $css_path = [$media => is_string($css_media_type) ? $css_media_type : 'all'];
            }
            $key = is_array($css_path) ? key($css_path) : $css_path;
            if ($css_path && (!isset($this->css_files[$key]) || $this->css_files[$key] != reset($css_path))) {
                $size = count($this->css_files);
                if ($offset > $size || $offset < 0 || !is_numeric($offset)) {
                    $offset = $size;
                }
                $this->css_files = array_merge(array_slice($this->css_files, 0, $offset), $css_path, array_slice($this->css_files, $offset));
            } else {
                $result = false;
            }
        }
        return $result;
    }
    /**
     * Adds jQuery plugin(s) to queued JS file list
     *
     * @param string|array $name
     * @param string|null $folder
     * @param bool $css
     */
    public function add_jquery_plugin($name, $folder = null, $css = true): void
    {
        if (!is_array($name)) {
            $name = [$name];
        }
        if (is_array($name)) {
            foreach ($name as $plugin) {
                $plugin_path = Media::get_jquery_plugin_path($plugin, $folder);
                if (!empty($plugin_path['js'])) {
                    $this->add_js($plugin_path['js'], false);
                }
                if ($css && !empty($plugin_path['css'])) {
                    $this->add_css(key($plugin_path['css']), 'all', null, false);
                }
            }
        }
    }
    /**
     * Checks if the controller has been called from XmlHttpRequest (AJAX)
     *
     * @return bool
     */
    public function is_xml_http_request()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    /**
     * Checks if a template is cached
     *
     * @param string $template
     * @param string|null $cacheId Cache item ID
     * @param string|null $compileId
     *
     * @return bool
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function is_cached($template, $cache_id = null, $compile_id = null)
    {
        Tools::enable_cache();
        $res = $this->context->smarty->is_cached($template, $cache_id, $compile_id);
        Tools::restore_cache_settings();
        return $res;
    }
    /**
     * Dies and echoes output value
     *
     * @param string|null $value
     * @param string|null $controller
     * @param string|null $method
     *
     * @throws PrestaShopException
     */
    protected function ajax_die($value = null, $controller = null, $method = null)
    {
        if ($controller === null) {
            $controller = static::class;
        }
        if ($method === null) {
            $bt = debug_backtrace();
            $method = $bt[1]['function'];
        }
        Hook::trigger_event('actionBeforeAjaxDie', ['controller' => $controller, 'method' => $method, 'value' => $value]);
        Hook::trigger_event('actionBeforeAjaxDie' . $controller . $method, ['value' => $value]);
        die($value);
    }
    /**
     * This method returns javascript code that outputs all encountered php errors and warnings
     * to javascript console. This script should be inserted just before </body> tag
     *
     * @return string javascript code, or null if no errors were encountered
     */
    protected function get_error_messages_script()
    {
        $messages = static::get_error_messages();
        if ($messages) {
            $messages_list = [];
            foreach ($messages as $msg) {
                $messages_list[] = ['level' => $msg['level'], 'type' => $msg['type'], 'message' => $msg['errstr'], 'file' => Error_Utils::get_relative_file($msg['errfile']), 'line' => (int) $msg['errline']];
            }
            $messages = '<script type="text/javascript">' . "\n" . 'window.phpMessages=' . json_encode($messages_list, JSON_PRETTY_PRINT) . ";\n</script>\n";
            $debug_js = '<script type="text/javascript" src="' . Media::get_js_path(_PS_JS_DIR_ . 'php-debug.js') . '" async defer></script>' . "\n";
            return $messages . $debug_js;
        }
        return '';
    }
    /**
     * Checks if scheduler synthetic cron even should be triggered. If so, /js/trigger.js
     * script will be added to the page. This script will trigger ajax post request
     * to TriggerController front controller
     *
     * @throws PrestaShopException
     */
    protected function add_synthetic_scheduler_js()
    {
        // check if scheduler event is required
        if (!$this->ajax) {
            $scheduler = Service_Locator::get_instance()->get_scheduler();
            if ($scheduler->synthetic_event_required() && !Tools::is_crawler()) {
                $trigger_url = $this->context->link->get_page_link('trigger', null, null, ['ts' => time()]);
                Media::add_js_def(['triggerUrl' => $trigger_url, 'triggerToken' => $scheduler->get_synthetic_event_secret()]);
                $this->add_js(_PS_JS_DIR_ . 'trigger.js');
            }
        }
    }
    protected static function get_error_handler(): Error_Handler
    {
        return Service_Locator::get_instance()->get_error_handler();
    }
    /**
     * Returns error messages collected by ErrorHandler
     * @return array
     */
    protected static function get_error_messages()
    {
        if (_PS_MODE_DEV_) {
            if (_PS_DISPLAY_COMPATIBILITY_WARNING_) {
                $mask = E_ALL;
            } else {
                $mask = E_ALL & ~(E_DEPRECATED | E_USER_DEPRECATED);
            }
            return static::get_error_handler()->get_error_messages(false, $mask);
        }
        return [];
    }
}