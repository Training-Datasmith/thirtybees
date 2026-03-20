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
class Tree_Toolbar_Core implements I_Tree_Toolbar_Core
{
    public const DEFAULT_TEMPLATE_DIRECTORY = 'helpers/tree';
    public const DEFAULT_TEMPLATE = 'tree_toolbar.tpl';
    /**
     * @var ITreeToolbarButtonCore[]
     */
    protected $_actions;
    /**
     * @var Context
     */
    protected $_context;
    /**
     * @var array
     */
    protected $_data;
    /**
     * @var string
     */
    protected $_template;
    /**
     * @var string
     */
    protected $_template_directory;
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
     * @param ITreeToolbarButtonCore[] $actions
     *
     *
     * @throws PrestaShopException
     */
    public function set_actions($actions): static
    {
        if (!is_array($actions) && !$actions instanceof Traversable) {
            throw new Presta_Shop_Exception('Action value must be an traversable array');
        }
        foreach ($actions as $action) {
            $this->add_action($action);
        }
        return $this;
    }
    /**
     * @return ITreeToolbarButtonCore[]
     */
    public function get_actions()
    {
        if (!isset($this->_actions)) {
            $this->_actions = [];
        }
        return $this->_actions;
    }
    /**
     * @param Context $value
     */
    public function set_context($value): static
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
     * @param array $value
     *
     * @throws PrestaShopException
     */
    public function set_data($value): static
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            throw new Presta_Shop_Exception('Data value must be an traversable array');
        }
        $this->_data = $value;
        return $this;
    }
    /**
     * @return array
     */
    public function get_data()
    {
        return $this->_data;
    }
    /**
     * @param string $value
     */
    public function set_template($value): static
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
     */
    public function set_template_directory($value): static
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
     *
     * @throws PrestaShopException
     */
    public function get_template_file(string $template): string
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
     * @param ITreeToolbarButtonCore $action
     *
     * @throws PrestaShopException
     */
    public function add_action($action): static
    {
        if (!is_object($action)) {
            throw new Presta_Shop_Exception('Action must be a class object');
        }
        $reflection = new ReflectionClass($action);
        if (!$reflection->implements_interface('ITreeToolbarButtonCore')) {
            throw new Presta_Shop_Exception('Action class must implements ITreeToolbarButtonCore interface');
        }
        if (!isset($this->_actions)) {
            $this->_actions = [];
        }
        if (isset($this->_template_directory)) {
            $action->set_template_directory($this->get_template_directory());
        }
        $this->_actions[] = $action;
        return $this;
    }
    public function remove_actions(): static
    {
        $this->_actions = null;
        return $this;
    }
    /**
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render()
    {
        foreach ($this->get_actions() as $action) {
            $action->set_attribute('data', $this->get_data());
        }
        return $this->get_context()->smarty->create_template($this->get_template_file($this->get_template()), $this->get_context()->smarty)->assign('actions', $this->get_actions())->fetch();
    }
    /**
     *
     *
     * @deprecated 2.0.0
     */
    protected function _normalize_directory(string $directory): string
    {
        $last = $directory[strlen($directory) - 1];
        if (in_array($last, ['/', '\\'])) {
            $directory[strlen($directory) - 1] = DIRECTORY_SEPARATOR;
            return $directory;
        }
        return $directory . DIRECTORY_SEPARATOR;
    }
}