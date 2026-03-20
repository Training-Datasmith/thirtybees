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
 * Class TreeToolbarButtonCore
 */
abstract class Tree_Toolbar_Button_Core implements \Stringable
{
    public const DEFAULT_TEMPLATE_DIRECTORY = 'helpers/tree';
    /**
     * @var array
     */
    protected $_attributes;
    /**
     * @var Context
     */
    private $_context;
    /**
     * @var string
     */
    protected $_template;
    /**
     * @var string
     */
    protected $_template_directory;
    /**
     * TreeToolbarButtonCore constructor.
     *
     * @param string $label
     * @param int|null $id
     * @param string|null $name
     * @param string|null $class
     */
    public function __construct($label, $id = null, $name = null, $class = null)
    {
        $this->set_label($label);
        $this->set_id($id);
        $this->set_name($name);
        $this->set_class($class);
    }
    /**
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function __toString(): string
    {
        return $this->render();
    }
    /**
     * @param string $name
     * @param mixed $value
     *
     * @return static
     */
    public function set_attribute($name, $value)
    {
        if (!isset($this->_attributes)) {
            $this->_attributes = [];
        }
        $this->_attributes[$name] = $value;
        return $this;
    }
    /**
     * @param string $name
     *
     * @return mixed|null
     */
    public function get_attribute($name)
    {
        return $this->has_attribute($name) ? $this->_attributes[$name] : null;
    }
    /**
     * @param array $value
     *
     * @return static
     * @throws PrestaShopException
     */
    public function set_attributes($value)
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            throw new Presta_Shop_Exception('Data value must be an traversable array');
        }
        $this->_attributes = $value;
        return $this;
    }
    /**
     * @return array
     */
    public function get_attributes()
    {
        if (!isset($this->_attributes)) {
            $this->_attributes = [];
        }
        return $this->_attributes;
    }
    /**
     * @param string $value
     *
     * @return TreeToolbarButtonCore
     */
    public function set_class($value)
    {
        return $this->set_attribute('class', $value);
    }
    /**
     * @return string|null
     */
    public function get_class()
    {
        return $this->get_attribute('class');
    }
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
     * @param string|int $value
     *
     * @return TreeToolbarButtonCore
     */
    public function set_id($value)
    {
        return $this->set_attribute('id', $value);
    }
    /**
     * @return string|int|null
     */
    public function get_id()
    {
        return $this->get_attribute('id');
    }
    /**
     * @param string $value
     *
     * @return TreeToolbarButtonCore
     */
    public function set_label($value)
    {
        return $this->set_attribute('label', $value);
    }
    /**
     * @return string|null
     */
    public function get_label()
    {
        return $this->get_attribute('label');
    }
    /**
     * @param string $value
     *
     * @return TreeToolbarButtonCore
     */
    public function set_name($value)
    {
        return $this->set_attribute('name', $value);
    }
    /**
     * @return string|null
     */
    public function get_name()
    {
        return $this->get_attribute('name');
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
        return $this->_template;
    }
    /**
     * @param string $value
     *
     * @return static
     */
    public function set_template_directory($value)
    {
        $this->_template_directory = $this->_normalize_directory($value);
        return $this;
    }
    /**
     * @return string
     */
    public function get_template_directory()
    {
        if (!isset($this->_template_directory)) {
            $this->_template_directory = $this->_normalize_directory(static::DEFAULT_TEMPLATE_DIRECTORY);
        }
        return $this->_template_directory;
    }
    /**
     *
     * @return string
     * @throws PrestaShopException
     */
    public function get_template_file(string $template)
    {
        if (preg_match_all('/((?:^|[A-Z])[a-z]+)/', $this->get_context()->controller::class, $matches) !== false) {
            $controller_name = strtolower($matches[0][1]);
        }
        if ($this->get_context()->controller instanceof Module_Admin_Controller && file_exists($this->_normalize_directory($this->get_context()->controller->get_template_path()) . $this->get_template_directory() . $template)) {
            return $this->_normalize_directory($this->get_context()->controller->get_template_path()) . $this->get_template_directory() . $template;
        }
        if ($this->get_context()->controller instanceof Admin_Controller && isset($controller_name) && file_exists($this->_normalize_directory($this->get_context()->smarty->get_template_dir(0)) . 'controllers' . DIRECTORY_SEPARATOR . $controller_name . DIRECTORY_SEPARATOR . $this->get_template_directory() . $template)) {
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
     * @param string $name
     *
     * @return bool
     */
    public function has_attribute($name)
    {
        return isset($this->_attributes) && array_key_exists($name, $this->_attributes);
    }
    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render()
    {
        return $this->get_context()->smarty->create_template($this->get_template_file($this->get_template()), $this->get_context()->smarty)->assign($this->get_attributes())->fetch();
    }
    /**
     *
     * @return string
     * @deprecated 2.0.0
     */
    protected function _normalize_directory(string $directory)
    {
        $last = $directory[strlen($directory) - 1];
        if (in_array($last, ['/', '\\'])) {
            $directory[strlen($directory) - 1] = DIRECTORY_SEPARATOR;
            return $directory;
        }
        return $directory . DIRECTORY_SEPARATOR;
    }
}