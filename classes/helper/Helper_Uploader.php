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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */
/**
 * Class HelperUploaderCore
 */
class Helper_Uploader_Core extends Uploader
{
    public const DEFAULT_TEMPLATE_DIRECTORY = 'helpers/uploader';
    public const DEFAULT_TEMPLATE = 'simple.tpl';
    public const DEFAULT_AJAX_TEMPLATE = 'ajax.tpl';
    public const TYPE_IMAGE = 'image';
    public const TYPE_FILE = 'file';
    /**
     * @var Context
     */
    private $_context;
    /**
     * @var string
     */
    private $_drop_zone;
    /**
     * @var string
     */
    private $_id;
    /**
     * @var array
     */
    private $_files;
    private ?string $_name = null;
    private ?int $_max_files = null;
    private ?bool $_multiple = null;
    /**
     * @var string
     */
    protected $_template;
    /**
     * @var string
     */
    private $_template_directory;
    /**
     * @var string
     */
    private $_title;
    private ?string $_url = null;
    private ?bool $_use_ajax = null;
    /**
     * @param Context $value
     *
     * @return static
     */
    public function set_context($value)
    {
        $this->_context = $value;
        return $this;
    }
    /**
     * @return Context
     */
    public function get_context()
    {
        if (!isset($this->_context)) {
            $this->_context = Context::get_context();
        }
        return $this->_context;
    }
    /**
     * @param string $value
     *
     * @return static
     */
    public function set_drop_zone($value)
    {
        $this->_drop_zone = $value;
        return $this;
    }
    /**
     * @return string
     */
    public function get_drop_zone()
    {
        if (!isset($this->_drop_zone)) {
            $this->set_drop_zone("\$('#" . $this->get_id() . "-add-button')");
        }
        return $this->_drop_zone;
    }
    /**
     * @param int $value
     *
     * @return static
     */
    public function set_id($value)
    {
        $this->_id = (string) $value;
        return $this;
    }
    /**
     * @return string
     */
    public function get_id()
    {
        if (!isset($this->_id) || trim($this->_id) === '') {
            $this->_id = $this->get_name();
        }
        return $this->_id;
    }
    /**
     * @param array[] $value
     *
     * @return static
     */
    public function set_files($value)
    {
        $this->_files = $value;
        return $this;
    }
    /**
     * @return array[]
     */
    public function get_files()
    {
        if (!isset($this->_files)) {
            $this->_files = [];
        }
        return $this->_files;
    }
    /**
     * @param int $value
     *
     * @return static
     */
    public function set_max_files($value)
    {
        $this->_max_files = isset($value) ? intval($value) : $value;
        return $this;
    }
    /**
     * @return int
     */
    public function get_max_files()
    {
        return $this->_max_files;
    }
    /**
     * @param bool $value
     *
     * @return static
     */
    public function set_multiple($value)
    {
        $this->_multiple = (bool) $value;
        return $this;
    }
    /**
     * @param string $value
     *
     * @return static
     */
    public function set_name($value)
    {
        $this->_name = (string) $value;
        return $this;
    }
    /**
     * @return string
     */
    public function get_name()
    {
        return $this->_name;
    }
    /**
     * @param int $value
     *
     * @return static
     */
    public function set_post_max_size($value)
    {
        $this->set_max_size($value);
        return $this;
    }
    /**
     * @return int
     *
     * @deprecated 1.4.0 Not used anymore
     */
    public function get_post_max_size()
    {
        return $this->get_max_size();
    }
    /**
     * @param string $value
     *
     * @return static
     */
    public function set_template($value)
    {
        $this->_template = $value;
        return $this;
    }
    /**
     * @return string
     */
    public function get_template()
    {
        if (!isset($this->_template)) {
            $this->set_template(static::DEFAULT_TEMPLATE);
        }
        return $this->_template;
    }
    /**
     * @param string $value
     *
     * @return static
     */
    public function set_template_directory($value)
    {
        $this->_template_directory = $value;
        return $this;
    }
    /**
     * @return string
     */
    public function get_template_directory()
    {
        if (!isset($this->_template_directory)) {
            $this->_template_directory = static::DEFAULT_TEMPLATE_DIRECTORY;
        }
        return $this->_normalize_directory($this->_template_directory);
    }
    /**
     * @param string $template
     *
     * @return string
     */
    public function get_template_file($template)
    {
        $controller = $this->get_context()->controller;
        if (preg_match_all('/((?:^|[A-Z])[a-z]+)/', $controller::class, $matches) !== false) {
            $controller_name = strtolower($matches[0][1]);
        }
        if ($controller instanceof Module_Admin_Controller && file_exists($this->_normalize_directory($controller->get_template_path()) . $this->get_template_directory() . $template)) {
            return $this->_normalize_directory($controller->get_template_path()) . $this->get_template_directory() . $template;
        }
        if ($controller instanceof Admin_Controller && isset($controller_name) && file_exists($this->_normalize_directory($this->get_context()->smarty->get_template_dir(0)) . 'controllers' . DIRECTORY_SEPARATOR . $controller_name . DIRECTORY_SEPARATOR . $this->get_template_directory() . $template)) {
            return $this->_normalize_directory($this->get_context()->smarty->get_template_dir(0)) . 'controllers' . DIRECTORY_SEPARATOR . $controller_name . DIRECTORY_SEPARATOR . $this->get_template_directory() . $template;
        }
        if (file_exists($this->_normalize_directory($this->get_context()->smarty->get_template_dir(1)) . $this->get_template_directory() . $template)) {
            return $this->_normalize_directory($this->get_context()->smarty->get_template_dir(1)) . $this->get_template_directory() . $template;
        }
        if (file_exists($this->_normalize_directory($this->get_context()->smarty->get_template_dir(0)) . $this->get_template_directory() . $template)) {
            return $this->_normalize_directory($this->get_context()->smarty->get_template_dir(0)) . $this->get_template_directory() . $template;
        }
        return $this->get_template_directory() . $template;
    }
    /**
     * @param string $value
     *
     * @return static
     */
    public function set_title($value)
    {
        $this->_title = $value;
        return $this;
    }
    /**
     * @return string
     */
    public function get_title()
    {
        return $this->_title;
    }
    /**
     * @param string $value
     *
     * @return static
     */
    public function set_url($value)
    {
        $this->_url = (string) $value;
        return $this;
    }
    /**
     * @return string
     */
    public function get_url()
    {
        return $this->_url;
    }
    /**
     * @param bool $value
     *
     * @return static
     */
    public function set_use_ajax($value)
    {
        $this->_use_ajax = (bool) $value;
        return $this;
    }
    /**
     * @return bool
     */
    public function is_multiple()
    {
        return isset($this->_multiple) && $this->_multiple;
    }
    /**
     * @return string
     *
     * @throws SmartyException
     */
    public function render()
    {
        $admin_webpath = str_ireplace(_PS_CORE_DIR_, '', _PS_ADMIN_DIR_);
        $admin_webpath = preg_replace('/^' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', '', $admin_webpath);
        $bo_theme = Validate::is_loaded_object($this->get_context()->employee) && $this->get_context()->employee->bo_theme ? $this->get_context()->employee->bo_theme : 'default';
        if (!file_exists(_PS_BO_ALL_THEMES_DIR_ . $bo_theme . DIRECTORY_SEPARATOR . 'template')) {
            $bo_theme = 'default';
        }
        $controller = $this->get_context()->controller;
        $controller->add_js(__PS_BASE_URI__ . $admin_webpath . '/themes/' . $bo_theme . '/js/jquery.iframe-transport.js');
        $controller->add_js(__PS_BASE_URI__ . $admin_webpath . '/themes/' . $bo_theme . '/js/jquery.fileupload.js');
        $controller->add_js(__PS_BASE_URI__ . $admin_webpath . '/themes/' . $bo_theme . '/js/jquery.fileupload-process.js');
        $controller->add_js(__PS_BASE_URI__ . $admin_webpath . '/themes/' . $bo_theme . '/js/jquery.fileupload-validate.js');
        $controller->add_js(__PS_BASE_URI__ . 'js/vendor/spin.js');
        $controller->add_js(__PS_BASE_URI__ . 'js/vendor/ladda.js');
        if ($this->use_ajax() && !isset($this->_template)) {
            $this->set_template(static::DEFAULT_AJAX_TEMPLATE);
        }
        $template = $this->get_context()->smarty->create_template($this->get_template_file($this->get_template()), $this->get_context()->smarty);
        $template->assign(['id' => $this->get_id(), 'name' => $this->get_name(), 'url' => $this->get_url(), 'multiple' => $this->is_multiple(), 'files' => $this->get_files(), 'title' => $this->get_title(), 'max_files' => $this->get_max_files(), 'post_max_size' => $this->get_post_max_size_bytes(), 'drop_zone' => $this->get_drop_zone()]);
        return $template->fetch();
    }
    /**
     * @return bool
     */
    public function use_ajax()
    {
        return isset($this->_use_ajax) && $this->_use_ajax;
    }
}