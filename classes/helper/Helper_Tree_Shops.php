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
 * Class HelperTreeShopsCore
 */
class Helper_Tree_Shops_Core extends Tree_Core
{
    public const DEFAULT_TEMPLATE = 'tree_shops.tpl';
    public const DEFAULT_NODE_FOLDER_TEMPLATE = 'tree_node_folder_checkbox_shops.tpl';
    public const DEFAULT_NODE_ITEM_TEMPLATE = 'tree_node_item_checkbox_shops.tpl';
    /**
     * @var int|null
     */
    protected $_lang;
    /**
     * @var array
     */
    protected $_selected_shops;
    /**
     * HelperTreeShopsCore constructor.
     *
     * @param string|int $id
     * @param string|null $title
     * @param int|null $lang
     *
     * @throws PrestaShopException
     */
    public function __construct($id, $title = null, $lang = null)
    {
        parent::__construct($id);
        $this->set_title($title);
        $this->set_lang($lang);
    }
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_data()
    {
        if (!isset($this->_data)) {
            $this->set_data(Shop::get_tree());
        }
        return $this->_data;
    }
    /**
     * @param int $value
     */
    public function set_lang($value): static
    {
        $this->_lang = $value;
        return $this;
    }
    /**
     * @return int
     */
    public function get_lang()
    {
        if (!isset($this->_lang)) {
            $this->set_lang($this->get_context()->employee->id_lang);
        }
        return $this->_lang;
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
     * @param int[] $value
     *
     * @throws PrestaShopException
     */
    public function set_selected_shops($value): static
    {
        if (!is_array($value)) {
            throw new Presta_Shop_Exception('Selected shops value must be an array');
        }
        $this->_selected_shops = $value;
        return $this;
    }
    /**
     * @return int[]
     */
    public function get_selected_shops()
    {
        if (!isset($this->_selected_shops)) {
            $this->_selected_shops = [];
        }
        return $this->_selected_shops;
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
     * @param array|null $data
     * @param bool $useDefaultActions
     * @param bool $useSelectedShop
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render($data = null, $use_default_actions = true, $use_selected_shop = true)
    {
        if (!isset($data)) {
            $data = $this->get_data();
        }
        if ($use_default_actions) {
            $this->set_actions([new Tree_Toolbar_Link('Collapse All', '#', '$(\'#' . $this->get_id() . '\').tree(\'collapseAll\'); return false;', 'icon-collapse-alt'), new Tree_Toolbar_Link('Expand All', '#', '$(\'#' . $this->get_id() . '\').tree(\'expandAll\'); return false;', 'icon-expand-alt'), new Tree_Toolbar_Link('Check All', '#', 'checkAllAssociatedShops($(\'#' . $this->get_id() . '\')); return false;', 'icon-check-sign'), new Tree_Toolbar_Link('Uncheck All', '#', 'uncheckAllAssociatedShops($(\'#' . $this->get_id() . '\')); return false;', 'icon-check-empty')]);
        }
        if ($use_selected_shop) {
            $this->set_attribute('selected_shops', $this->get_selected_shops());
        }
        return parent::render($data);
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
            if (array_key_exists('shops', $item) && !empty($item['shops'])) {
                $html .= $this->get_context()->smarty->create_template($this->get_template_file($this->get_node_folder_template()), $this->get_context()->smarty)->assign($this->get_attributes())->assign(['children' => $this->render_nodes($item['shops']), 'node' => $item])->fetch();
            } else {
                $html .= $this->get_context()->smarty->create_template($this->get_template_file($this->get_node_item_template()), $this->get_context()->smarty)->assign($this->get_attributes())->assign(['node' => $item])->fetch();
            }
        }
        return $html;
    }
}