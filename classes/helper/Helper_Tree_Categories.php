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
 * Class HelperTreeCategoriesCore
 */
class Helper_Tree_Categories_Core extends Tree_Core
{
    public const DEFAULT_TEMPLATE = 'tree_categories.tpl';
    public const DEFAULT_NODE_FOLDER_TEMPLATE = 'tree_node_folder_radio.tpl';
    public const DEFAULT_NODE_ITEM_TEMPLATE = 'tree_node_item_radio.tpl';
    /**
     * @var array|null
     */
    protected $_disabled_categories;
    /**
     * @var string
     */
    protected $_input_name;
    /**
     * @var int $_lang
     */
    protected $_lang;
    /**
     * @var int
     */
    protected $_root_category;
    /**
     * @var array
     */
    protected $_selected_categories;
    /**
     * @var bool
     */
    protected $_full_tree = false;
    /**
     * @var Shop
     */
    protected $_shop;
    /**
     * @var bool
     */
    protected $_use_checkbox;
    /**
     * @var bool
     */
    protected $_use_search;
    /**
     * @var bool
     */
    protected $_use_shop_restriction;
    /**
     * @var bool
     */
    protected $_children_only = false;
    /**
     * HelperTreeCategoriesCore constructor.
     *
     * @param string|int $id
     * @param string|null $title
     * @param int|null $rootCategory
     * @param int|null $lang
     * @param bool $useShopRestriction
     *
     * @throws PrestaShopException
     */
    public function __construct($id, $title = null, $root_category = null, $lang = null, $use_shop_restriction = true)
    {
        parent::__construct($id);
        $this->set_title($title);
        if (isset($root_category)) {
            $this->set_root_category($root_category);
        }
        $this->set_lang($lang);
        $this->set_use_shop_restriction($use_shop_restriction);
    }
    /**
     * @param int $idCategory
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function fill_tree(array &$categories, $id_category): array
    {
        $tree = [];
        foreach ($categories[$id_category] as $category) {
            $tree[$category['id_category']] = $category;
            if (!empty($categories[$category['id_category']])) {
                $tree[$category['id_category']]['children'] = $this->fill_tree($categories, $category['id_category']);
            } elseif ($result = Category::has_children($category['id_category'], $this->get_lang(), false, $this->get_shop()->id)) {
                $tree[$category['id_category']]['children'] = [$result[0]['id_category'] => $result[0]];
            }
        }
        return $tree;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_data()
    {
        if (!isset($this->_data)) {
            $shop = $this->get_shop();
            $lang = $this->get_lang();
            $root_category = (int) $this->get_root_category();
            if ($this->_full_tree) {
                $this->set_data(Category::get_nested_categories($root_category, $lang, false, null, $this->use_shop_restriction()));
                $this->set_data_search(Category::get_all_categories_name($root_category, $lang, false, null, $this->use_shop_restriction()));
            } elseif ($this->_children_only) {
                if (empty($root_category)) {
                    $root_category = Category::get_root_category()->id;
                }
                $categories[$root_category] = Category::get_children($root_category, $lang, false, $shop->id);
                $children = $this->fill_tree($categories, $root_category);
                $this->set_data($children);
            } else {
                if (empty($root_category)) {
                    $root_category = Category::get_root_category()->id;
                }
                $new_selected_categories = [];
                $selected_categories = $this->get_selected_categories();
                $categories[$root_category] = Category::get_children($root_category, $lang, false, $shop->id);
                foreach ($selected_categories as $selected_category) {
                    $category = new Category($selected_category, $lang, $shop->id);
                    $new_selected_categories[] = $selected_category;
                    $parents = $category->get_parents_categories($lang);
                    foreach ($parents as $value) {
                        $new_selected_categories[] = $value['id_category'];
                    }
                }
                $new_selected_categories = array_unique($new_selected_categories);
                foreach ($new_selected_categories as $selected_category) {
                    $current_category = Category::get_children($selected_category, $lang, false, $shop->id);
                    if (!empty($current_category)) {
                        $categories[$selected_category] = $current_category;
                    }
                }
                $tree = Category::get_category_informations([$root_category], $lang);
                $children = $this->fill_tree($categories, $root_category);
                if (!empty($children)) {
                    $tree[$root_category]['children'] = $children;
                }
                $this->set_data($tree);
                $this->set_data_search(Category::get_all_categories_name($root_category, $lang, false, null, $this->use_shop_restriction()));
            }
        }
        return $this->_data;
    }
    /**
     * @param bool $value
     */
    public function set_children_only($value): static
    {
        $this->_children_only = (bool) $value;
        return $this;
    }
    /**
     * @param bool $value
     */
    public function set_full_tree($value): static
    {
        $this->_full_tree = (bool) $value;
        return $this;
    }
    /**
     * @return bool
     */
    public function get_full_tree()
    {
        return $this->_full_tree;
    }
    /**
     * @param array|null $value
     */
    public function set_disabled_categories($value): static
    {
        $this->_disabled_categories = $value;
        return $this;
    }
    /**
     * @return array|null
     */
    public function get_disabled_categories()
    {
        return $this->_disabled_categories;
    }
    /**
     * @param string $value
     */
    public function set_input_name($value): static
    {
        $this->_input_name = $value;
        return $this;
    }
    /**
     * @return string
     */
    public function get_input_name()
    {
        if (!isset($this->_input_name)) {
            $this->set_input_name('categoryBox');
        }
        return $this->_input_name;
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
     * @param int $value
     *
     * @throws PrestaShopException
     */
    public function set_root_category($value): static
    {
        if (!Validate::is_int($value)) {
            throw new Presta_Shop_Exception('Root category must be an integer value');
        }
        $this->_root_category = $value;
        return $this;
    }
    /**
     * @return int
     */
    public function get_root_category()
    {
        return $this->_root_category;
    }
    /**
     * @param array $value
     *
     * @throws PrestaShopException
     */
    public function set_selected_categories($value): static
    {
        if (!is_array($value)) {
            throw new Presta_Shop_Exception('Selected categories value must be an array');
        }
        $this->_selected_categories = $value;
        return $this;
    }
    /**
     * @return array
     */
    public function get_selected_categories()
    {
        if (!isset($this->_selected_categories)) {
            $this->_selected_categories = [];
        }
        return $this->_selected_categories;
    }
    /**
     * @param Shop $value
     */
    public function set_shop($value): static
    {
        $this->_shop = $value;
        return $this;
    }
    /**
     * @return Shop
     *
     * @throws PrestaShopException
     */
    public function get_shop()
    {
        if (!isset($this->_shop)) {
            if (Tools::is_submit('id_shop')) {
                $this->set_shop(new Shop(Tools::get_int_value('id_shop')));
            } elseif ($this->get_context()->shop->id) {
                $this->set_shop(new Shop($this->get_context()->shop->id));
            } elseif (!Shop::is_feature_active()) {
                $this->set_shop(new Shop(Configuration::get('PS_SHOP_DEFAULT')));
            } else {
                $this->set_shop(new Shop(0));
            }
        }
        return $this->_shop;
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
     * @param bool $value
     */
    public function set_use_check_box($value): static
    {
        $this->_use_checkbox = (bool) $value;
        return $this;
    }
    /**
     * @param bool $value
     */
    public function set_use_search($value): static
    {
        $this->_use_search = (bool) $value;
        return $this;
    }
    /**
     * @param bool $value
     */
    public function set_use_shop_restriction($value): static
    {
        $this->_use_shop_restriction = (bool) $value;
        return $this;
    }
    public function use_check_box(): bool
    {
        return isset($this->_use_checkbox) && $this->_use_checkbox;
    }
    public function use_search(): bool
    {
        return isset($this->_use_search) && $this->_use_search;
    }
    public function use_shop_restriction(): bool
    {
        return isset($this->_use_shop_restriction) && $this->_use_shop_restriction;
    }
    /**
     * @param array|null $data
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render($data = null)
    {
        if (!isset($data)) {
            $data = $this->get_data();
        }
        if (!empty($this->_disabled_categories)) {
            $this->_disable_categories($data, $this->get_disabled_categories());
        }
        if (!empty($this->_selected_categories)) {
            $this->_get_selected_child_numbers($data, $this->get_selected_categories());
        }
        $collapse_all = new Tree_Toolbar_Link('Collapse All', '#', '$(\'#' . $this->get_id() . '\').tree(\'collapseAll\');$(\'#collapse-all-' . $this->get_id() . '\').hide();$(\'#expand-all-' . $this->get_id() . '\').show(); return false;', 'icon-collapse-alt');
        $collapse_all->set_attribute('id', 'collapse-all-' . $this->get_id());
        $expand_all = new Tree_Toolbar_Link('Expand All', '#', '$(\'#' . $this->get_id() . '\').tree(\'expandAll\');$(\'#collapse-all-' . $this->get_id() . '\').show();$(\'#expand-all-' . $this->get_id() . '\').hide(); return false;', 'icon-expand-alt');
        $expand_all->set_attribute('id', 'expand-all-' . $this->get_id());
        $this->add_action($collapse_all);
        $this->add_action($expand_all);
        if ($this->use_check_box()) {
            $check_all = new Tree_Toolbar_Link('Check All', '#', 'checkAllAssociatedCategories($(\'#' . $this->get_id() . '\')); return false;', 'icon-check-sign');
            $check_all->set_attribute('id', 'check-all-' . $this->get_id());
            $uncheck_all = new Tree_Toolbar_Link('Uncheck All', '#', 'uncheckAllAssociatedCategories($(\'#' . $this->get_id() . '\')); return false;', 'icon-check-empty');
            $uncheck_all->set_attribute('id', 'uncheck-all-' . $this->get_id());
            $this->add_action($check_all);
            $this->add_action($uncheck_all);
            $this->set_node_folder_template('tree_node_folder_checkbox.tpl');
            $this->set_node_item_template('tree_node_item_checkbox.tpl');
            $this->set_attribute('use_checkbox', $this->use_check_box());
        }
        $this->set_attribute('selected_categories', $this->get_selected_categories());
        $this->get_context()->smarty->assign('root_category', Configuration::get('PS_ROOT_CATEGORY'));
        $this->get_context()->smarty->assign('token', Tools::get_admin_token_lite('AdminProducts'));
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
            if (array_key_exists('children', $item) && !empty($item['children'])) {
                $html .= $this->get_context()->smarty->create_template($this->get_template_file($this->get_node_folder_template()), $this->get_context()->smarty)->assign(['input_name' => $this->get_input_name(), 'children' => $this->render_nodes($item['children']), 'node' => $item])->fetch();
            } else {
                $html .= $this->get_context()->smarty->create_template($this->get_template_file($this->get_node_item_template()), $this->get_context()->smarty)->assign(['input_name' => $this->get_input_name(), 'node' => $item])->fetch();
            }
        }
        return $html;
    }
    /**
     * @param array[] $categories
     * @param array|null $disabledCategories
     */
    protected function _disable_categories(&$categories, $disabled_categories = null)
    {
        foreach ($categories as &$category) {
            if (!isset($disabled_categories) || in_array($category['id_category'], $disabled_categories)) {
                $category['disabled'] = true;
                if (array_key_exists('children', $category) && is_array($category['children'])) {
                    static::_disable_categories($category['children']);
                }
            } elseif (array_key_exists('children', $category) && is_array($category['children'])) {
                static::_disable_categories($category['children'], $disabled_categories);
            }
        }
    }
    /**
     * @param array[] $categories
     * @param array $selected
     * @param array $parent
     *
     * @return int
     */
    protected function _get_selected_child_numbers(&$categories, $selected, &$parent = null): float|int
    {
        $selected_childs = 0;
        foreach ($categories as &$category) {
            if (isset($parent) && in_array($category['id_category'], $selected)) {
                $selected_childs++;
            }
            if (!empty($category['children'])) {
                $selected_childs += $this->_get_selected_child_numbers($category['children'], $selected, $category);
            }
        }
        if (!isset($parent['selected_childs'])) {
            $parent['selected_childs'] = 0;
        }
        $parent['selected_childs'] = $selected_childs;
        return $selected_childs;
    }
}