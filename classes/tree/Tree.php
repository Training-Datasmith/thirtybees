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
 * Class TreeCore
 */
class Tree_Core implements \Stringable
{
    public const DEFAULT_TEMPLATE_DIRECTORY = 'helpers/tree';
    public const DEFAULT_TEMPLATE = 'tree.tpl';
    public const DEFAULT_HEADER_TEMPLATE = 'tree_header.tpl';
    public const DEFAULT_NODE_FOLDER_TEMPLATE = 'tree_node_folder.tpl';
    public const DEFAULT_NODE_ITEM_TEMPLATE = 'tree_node_item.tpl';
    /**
     * @var array
     */
    protected $_attributes;
    /**
     * @var Context
     */
    private $_context;
    /**
     * @var array
     */
    protected $_data;
    /**
     * @var array
     */
    protected $_data_search;
    /**
     * @var string
     */
    protected $_header_template;
    /**
     * @var string
     */
    protected $_id_tree;
    /**
     * @var int
     */
    private $_id;
    /**
     * @var string
     */
    protected $_node_folder_template;
    /**
     * @var string
     */
    protected $_node_item_template;
    /**
     * @var string
     */
    protected $_template;
    /**
     * @var string
     */
    private $_template_directory;
    private string $_title = '';
    /**
     * @var bool
     */
    private $_no_js;
    private ?\I_Tree_Toolbar_Core $_toolbar = null;
    /**
     * TreeCore constructor.
     *
     * @param string|int $id
     * @param array $data
     *
     * @throws PrestaShopException
     */
    public function __construct($id, $data = null)
    {
        $this->set_id($id);
        if (isset($data)) {
            $this->set_data($data);
        }
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
     * @param ITreeToolbarButtonCore[] $value
     */
    public function set_actions($value): static
    {
        if (!isset($this->_toolbar)) {
            $this->set_toolbar(new Tree_Toolbar());
        }
        $this->get_toolbar()->set_template_directory($this->get_template_directory())->set_actions($value);
        return $this;
    }
    /**
     * @return ITreeToolbarButtonCore[]
     */
    public function get_actions()
    {
        if (!isset($this->_toolbar)) {
            $this->set_toolbar(new Tree_Toolbar());
        }
        return $this->get_toolbar()->set_template_directory($this->get_template_directory())->get_actions();
    }
    /**
     * @param string $name
     * @param mixed $value
     */
    public function set_attribute($name, $value): static
    {
        if (!isset($this->_attributes)) {
            $this->_attributes = [];
        }
        $this->_attributes[$name] = $value;
        return $this;
    }
    /**
     * @param array $value
     *
     * @throws PrestaShopException
     */
    public function set_attributes($value): static
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            throw new Presta_Shop_Exception('Data value must be an traversable array');
        }
        $this->_attributes = $value;
        return $this;
    }
    /**
     * @param string $idTree
     */
    public function set_id_tree($id_tree): static
    {
        $this->_id_tree = $id_tree;
        return $this;
    }
    /**
     * @return string
     */
    public function get_id_tree()
    {
        return $this->_id_tree;
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
    public function set_data_search($value): static
    {
        if (!is_array($value) && !$value instanceof Traversable) {
            throw new Presta_Shop_Exception('Data value must be an traversable array');
        }
        $this->_data_search = $value;
        return $this;
    }
    /**
     * @return array
     */
    public function get_data_search()
    {
        if (!isset($this->_data_search)) {
            $this->_data_search = [];
        }
        return $this->_data_search;
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
        if (!isset($this->_data)) {
            $this->_data = [];
        }
        return $this->_data;
    }
    /**
     * @param string $value
     */
    public function set_header_template($value): static
    {
        $this->_header_template = $value;
        return $this;
    }
    /**
     * @return string
     */
    public function get_header_template()
    {
        if (!isset($this->_header_template)) {
            $this->set_header_template(static::DEFAULT_HEADER_TEMPLATE);
        }
        return $this->_header_template;
    }
    /**
     * @param int $value
     */
    public function set_id($value): static
    {
        $this->_id = $value;
        return $this;
    }
    /**
     * @return int
     */
    public function get_id()
    {
        return $this->_id;
    }
    /**
     * @param string $value
     */
    public function set_node_folder_template($value): static
    {
        $this->_node_folder_template = $value;
        return $this;
    }
    /**
     * @return string
     */
    public function get_node_folder_template()
    {
        if (!isset($this->_node_folder_template)) {
            $this->set_node_folder_template(static::DEFAULT_NODE_FOLDER_TEMPLATE);
        }
        return $this->_node_folder_template;
    }
    /**
     * @param string $value
     */
    public function set_node_item_template($value): static
    {
        $this->_node_item_template = $value;
        return $this;
    }
    /**
     * @return string
     */
    public function get_node_item_template()
    {
        if (!isset($this->_node_item_template)) {
            $this->set_node_item_template(static::DEFAULT_NODE_ITEM_TEMPLATE);
        }
        return $this->_node_item_template;
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
        if ($this->get_context()->controller instanceof Module_Admin_Controller && isset($controller_name) && file_exists($this->_normalize_directory($this->get_context()->controller->get_template_path()) . $controller_name . DIRECTORY_SEPARATOR . $this->get_template_directory() . $template)) {
            return $this->_normalize_directory($this->get_context()->controller->get_template_path()) . $controller_name . DIRECTORY_SEPARATOR . $this->get_template_directory() . $template;
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
     * @param bool $value
     */
    public function set_no_js($value): static
    {
        $this->_no_js = $value;
        return $this;
    }
    /**
     * @param string $value
     */
    public function set_title($value): static
    {
        if ($value) {
            $this->_title = trim($value);
        }
        return $this;
    }
    /**
     * @return string
     */
    public function get_title()
    {
        return $this->_title;
    }
    public function set_toolbar(I_Tree_Toolbar_Core $value): static
    {
        $this->_toolbar = $value;
        return $this;
    }
    /**
     * @return ITreeToolbarCore
     */
    public function get_toolbar()
    {
        if (isset($this->_toolbar)) {
            if ($this->get_data_search()) {
                $this->_toolbar->set_data($this->get_data_search());
            } else {
                $this->_toolbar->set_data($this->get_data());
            }
        }
        return $this->_toolbar;
    }
    /**
     * @param ITreeToolbarButtonCore $action
     */
    public function add_action($action): static
    {
        if (!isset($this->_toolbar)) {
            $this->set_toolbar(new Tree_Toolbar());
        }
        $this->get_toolbar()->set_template_directory($this->get_template_directory())->add_action($action);
        return $this;
    }
    public function remove_actions(): static
    {
        if (!isset($this->_toolbar)) {
            $this->set_toolbar(new Tree_Toolbar());
        }
        $this->get_toolbar()->set_template_directory($this->get_template_directory())->remove_actions();
        return $this;
    }
    /**
     * @param array|null $data
     *
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render($data = null): string
    {
        //Adding tree.js
        $admin_webpath = str_ireplace(_PS_CORE_DIR_, '', _PS_ADMIN_DIR_);
        $admin_webpath = preg_replace('/^' . preg_quote(DIRECTORY_SEPARATOR, '/') . '/', '', $admin_webpath);
        $bo_theme = Validate::is_loaded_object($this->get_context()->employee) && $this->get_context()->employee->bo_theme ? $this->get_context()->employee->bo_theme : 'default';
        if (!file_exists(_PS_BO_ALL_THEMES_DIR_ . $bo_theme . DIRECTORY_SEPARATOR . 'template')) {
            $bo_theme = 'default';
        }
        $js_path = Media::get_uri_with_version(__PS_BASE_URI__ . $admin_webpath . '/themes/' . $bo_theme . '/js/tree.js');
        if ($this->get_context()->controller->ajax) {
            if (!$this->_no_js) {
                $html = '<script type="text/javascript">$(function(){ $.ajax({url: "' . $js_path . '",cache:true,dataType: "script"})});</script>';
            }
        } else {
            $this->get_context()->controller->add_js($js_path);
        }
        //Create Tree Template
        $template = $this->get_context()->smarty->create_template($this->get_template_file($this->get_template()), $this->get_context()->smarty);
        if ($this->get_title() || $this->use_toolbar()) {
            //Create Tree Header Template
            $header_template = $this->get_context()->smarty->create_template($this->get_template_file($this->get_header_template()), $this->get_context()->smarty);
            $header_template->assign($this->get_attributes())->assign(['title' => $this->get_title(), 'toolbar' => $this->use_toolbar() ? $this->render_toolbar() : null]);
            $template->assign('header', $header_template->fetch());
        }
        //Assign Tree nodes
        $template->assign($this->get_attributes())->assign(['id' => $this->get_id(), 'nodes' => $this->render_nodes($data), 'id_tree' => $this->get_id_tree()]);
        return ($html ?? '') . $template->fetch();
    }
    /**
     * @param array|null $data
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render_nodes($data = null): string
    {
        if (!isset($data)) {
            $data = $this->get_data();
        }
        if (!is_array($data) && !$data instanceof Traversable) {
            throw new Presta_Shop_Exception('Data value must be an traversable array');
        }
        $html = '';
        foreach ($data as $item) {
            if (array_key_exists('children', $item) && !empty($item['children'])) {
                $html .= $this->get_context()->smarty->create_template($this->get_template_file($this->get_node_folder_template()), $this->get_context()->smarty)->assign(['children' => $this->render_nodes($item['children']), 'node' => $item])->fetch();
            } else {
                $html .= $this->get_context()->smarty->create_template($this->get_template_file($this->get_node_item_template()), $this->get_context()->smarty)->assign(['node' => $item])->fetch();
            }
        }
        return $html;
    }
    /**
     * @return string
     */
    public function render_toolbar()
    {
        return $this->get_toolbar()->render();
    }
    public function use_input(): bool
    {
        return isset($this->_input_type);
    }
    public function use_toolbar(): bool
    {
        return isset($this->_toolbar);
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