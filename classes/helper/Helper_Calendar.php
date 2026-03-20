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
 * Class HelperCalendarCore
 */
class Helper_Calendar_Core extends Helper
{
    public const DEFAULT_DATE_FORMAT = 'Y-mm-dd';
    public const DEFAULT_COMPARE_OPTION = 1;
    /**
     * @var array[]
     */
    private $_actions = [];
    /**
     * @var array[]
     */
    private $_compare_actions = [];
    /**
     * @var string | null
     */
    private $_compare_date_from;
    /**
     * @var string | null
     */
    private $_compare_date_to;
    private int $_compare_date_option = self::DEFAULT_COMPARE_OPTION;
    private string $_date_format = self::DEFAULT_DATE_FORMAT;
    /**
     * @var string
     */
    private $_date_from;
    /**
     * @var string
     */
    private $_date_to;
    private bool $_rtl;
    /**
     * HelperCalendarCore constructor.
     */
    public function __construct()
    {
        $this->base_folder = 'helpers/calendar/';
        $this->base_tpl = 'calendar.tpl';
        parent::__construct();
        $this->_rtl = (bool) $this->context->language->is_rtl;
    }
    /**
     * @param array[] $value
     *
     * @return static
     * @throws PrestaShopException
     */
    public function set_actions($value)
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            throw new Presta_Shop_Exception('Actions value must be an traversable array');
        }
        $this->_actions = (array) $value;
        return $this;
    }
    /**
     * @return array[]
     */
    public function get_actions()
    {
        return $this->_actions;
    }
    /**
     * @param array[] $value
     *
     * @return static
     * @throws PrestaShopException
     */
    public function set_compare_actions($value)
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            throw new Presta_Shop_Exception('Actions value must be an traversable array');
        }
        $this->_compare_actions = (array) $value;
        return $this;
    }
    /**
     * @return array[]
     */
    public function get_compare_actions()
    {
        return $this->_compare_actions;
    }
    /**
     * @param string $value
     *
     * @return static
     */
    public function set_compare_date_from($value)
    {
        $this->_compare_date_from = $this->convert_to_date($value);
        return $this;
    }
    /**
     * @return string
     */
    public function get_compare_date_from()
    {
        return $this->_compare_date_from;
    }
    /**
     * @param string $value
     *
     * @return static
     */
    public function set_compare_date_to($value)
    {
        $this->_compare_date_to = $this->convert_to_date($value);
        return $this;
    }
    /**
     * @return string
     */
    public function get_compare_date_to()
    {
        return $this->_compare_date_to;
    }
    /**
     * @param int $value
     *
     * @return static
     */
    public function set_compare_option($value)
    {
        $this->_compare_date_option = (int) $value;
        return $this;
    }
    /**
     * @return int
     */
    public function get_compare_option()
    {
        return $this->_compare_date_option;
    }
    /**
     * @param string $value
     *
     * @return static
     * @throws PrestaShopException
     */
    public function set_date_format($value)
    {
        if (!is_string($value)) {
            throw new Presta_Shop_Exception('Date format must be a string');
        }
        $this->_date_format = $value;
        return $this;
    }
    /**
     * @return string
     */
    public function get_date_format()
    {
        return $this->_date_format;
    }
    /**
     * @param string $value
     *
     * @return static
     */
    public function set_date_from($value)
    {
        $this->_date_from = $this->convert_to_date($value);
        return $this;
    }
    /**
     * @return string
     */
    public function get_date_from()
    {
        return $this->_date_from ?? date('Y-m-d', strtotime('-31 days'));
    }
    /**
     * @param string $value
     *
     * @return static
     */
    public function set_date_to($value)
    {
        $this->_date_to = $this->convert_to_date($value);
        return $this;
    }
    /**
     * @return false|string
     */
    public function get_date_to()
    {
        return $this->_date_to ?? date('Y-m-d');
    }
    /**
     * @param bool $value
     *
     * @return static
     */
    public function set_rtl($value)
    {
        $this->_rtl = (bool) $value;
        return $this;
    }
    /**
     * @param array $action
     *
     * @return static
     */
    public function add_action($action)
    {
        $this->_actions[] = $action;
        return $this;
    }
    /**
     * @param array $action
     *
     * @return static
     */
    public function add_compare_action($action)
    {
        $this->_compare_actions[] = $action;
        return $this;
    }
    /**
     * @return string
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function generate()
    {
        $context = $this->context;
        $controller = $this->get_controller();
        $admin_webpath = str_ireplace(_PS_CORE_DIR_, '', _PS_ADMIN_DIR_);
        $admin_webpath = preg_replace('/^' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', '', $admin_webpath);
        $bo_theme = Validate::is_loaded_object($context->employee) && $context->employee->bo_theme ? $context->employee->bo_theme : 'default';
        if (!file_exists(_PS_BO_ALL_THEMES_DIR_ . $bo_theme . DIRECTORY_SEPARATOR . 'template')) {
            $bo_theme = 'default';
        }
        if ($controller->ajax) {
            $html = '<script type="text/javascript" src="' . __PS_BASE_URI__ . $admin_webpath . '/themes/' . $bo_theme . '/js/date-range-picker.js"></script>';
            $html .= '<script type="text/javascript" src="' . __PS_BASE_URI__ . $admin_webpath . '/themes/' . $bo_theme . '/js/calendar.js"></script>';
        } else {
            $html = '';
            $controller->add_js(__PS_BASE_URI__ . $admin_webpath . '/themes/' . $bo_theme . '/js/date-range-picker.js');
            $controller->add_js(__PS_BASE_URI__ . $admin_webpath . '/themes/' . $bo_theme . '/js/calendar.js');
        }
        $this->tpl = $this->create_template($this->base_tpl);
        $this->tpl->assign(['date_format' => $this->get_date_format(), 'date_from' => $this->get_date_from(), 'date_to' => $this->get_date_to(), 'compare_date_from' => $this->get_compare_date_from(), 'compare_date_to' => $this->get_compare_date_to(), 'actions' => $this->get_actions(), 'compare_actions' => $this->get_compare_actions(), 'compare_option' => $this->get_compare_option(), 'is_rtl' => $this->is_rtl()]);
        return $html . parent::generate();
    }
    /**
     * @return bool
     */
    public function is_rtl()
    {
        return $this->_rtl;
    }
    /**
     * Converts and validates input value to date
     *
     * @param mixed $value
     * @return string | null
     */
    public function convert_to_date($value)
    {
        if (is_string($value) && !empty($value)) {
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('Y-m-d', $timestamp);
            }
        }
        return null;
    }
}