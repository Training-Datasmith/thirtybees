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
 * Class HelperCore
 */
class Helper_Core
{
    /**
     * @var string
     */
    public $current_index;
    /**
     * @var string $table
     */
    public $table = 'configuration';
    /**
     * @var string $identifier
     */
    public $identifier;
    /**
     * @var string $token
     */
    public $token;
    /**
     * @var array $toolbar_btn
     */
    public $toolbar_btn = [];
    /**
     * @var string $ps_help_context
     */
    public $ps_help_context;
    /**
     * @var string $title
     */
    public $title;
    /**
     * @var bool $show_toolbar
     */
    public $show_toolbar = true;
    /**
     * @var Context $context
     */
    public $context;
    /**
     * @var bool $toolbar_scroll
     */
    public $toolbar_scroll = false;
    /**
     * @var bool $bootstrap
     */
    public $bootstrap = false;
    /**
     * @var Module $module
     */
    public $module;
    /**
     * @var string Helper tpl folder
     */
    public $base_folder;
    /**
     * @var string Controller tpl folder
     */
    public $override_folder;
    /**
     * @var string base template name
     */
    public $base_tpl = 'content.tpl';
    /**
     * @var array $tpl_vars
     */
    public $tpl_vars = [];
    /**
     * @var Smarty_Internal_Template base template object
     */
    protected $tpl;
    /**
     * HelperCore constructor.
     */
    public function __construct()
    {
        $this->context = Context::get_context();
    }
    /**
     * @deprecated 2.0.0
     *
     * @param array $selectedCat
     * @param string $inputName
     * @param bool $useRadio
     * @param bool $useSearch
     * @param array $disabledCategories
     * @param bool $useInPopup
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function render_admin_categorie_tree(array $translations, $selected_cat = [], $input_name = 'categoryBox', $use_radio = false, $use_search = false, $disabled_categories = [], $use_in_popup = false)
    {
        Tools::display_as_deprecated();
        $helper = new Helper();
        if (isset($translations['Root'])) {
            $root = $translations['Root'];
        } elseif (isset($translations['Home'])) {
            $root = ['name' => $translations['Home'], 'id_category' => 1];
        } else {
            throw new Presta_Shop_Exception('Missing root category parameter.');
        }
        return $helper->render_category_tree($root, $selected_cat, $input_name, $use_radio, $use_search, $disabled_categories);
    }
    /**
     * @param array $root array with the name and ID of the tree root category, if null the Shop's root category will be used
     * @param array $selectedCat array of selected categories
     * @param string $inputName name of input
     * @param bool $useRadio use radio tree or checkbox tree
     * @param bool $useSearch display a find category search box
     * @param array $disabledCategories
     *
     *
     * @throws PrestaShopException
     */
    public function render_category_tree($root = null, $selected_cat = [], $input_name = 'categoryBox', $use_radio = false, $use_search = false, $disabled_categories = []): string
    {
        $translations = ['selected' => $this->l('Selected'), 'Collapse All' => $this->l('Collapse All'), 'Expand All' => $this->l('Expand All'), 'Check All' => $this->l('Check All'), 'Uncheck All' => $this->l('Uncheck All'), 'search' => $this->l('Find a category')];
        if (Tools::is_submit('id_shop')) {
            $id_shop = Tools::get_int_value('id_shop');
        } elseif ($this->context->shop->id) {
            $id_shop = $this->context->shop->id;
        } elseif (!Shop::is_feature_active()) {
            $id_shop = Configuration::get('PS_SHOP_DEFAULT');
        } else {
            $id_shop = 0;
        }
        $shop = new Shop($id_shop);
        $root_category = Category::get_root_category(null, $shop);
        $disabled_categories[] = (int) Configuration::get('PS_ROOT_CATEGORY');
        if (!$root) {
            $root = ['name' => $root_category->name, 'id_category' => $root_category->id];
        }
        if (!$use_radio) {
            $input_name = $input_name . '[]';
        }
        if ($use_search) {
            $this->get_controller()->add_js(_PS_JS_DIR_ . 'jquery/plugins/autocomplete/jquery.autocomplete.js');
        }
        $html = '
		<script type="text/javascript">
			var inputName = \'' . addcslashes($input_name, '\'') . '\';' . "\n";
        if (count($selected_cat) > 0) {
            if (isset($selected_cat[0])) {
                $html .= '			var selectedCat = "' . implode(',', array_map(intval(...), $selected_cat)) . '";' . "\n";
            } else {
                $html .= '			var selectedCat = "' . implode(',', array_map(intval(...), array_keys($selected_cat))) . '";' . "\n";
            }
        } else {
            $html .= '			var selectedCat = \'\';' . "\n";
        }
        $html .= '			var selectedLabel = \'' . $translations['selected'] . '\';
			var home = \'' . addcslashes((string) $root['name'], '\'') . '\';
			var use_radio = ' . (int) $use_radio . ';';
        $html .= '</script>';
        $html .= '
		<div class="category-filter">
			<a class="btn btn-link" href="#" id="collapse_all"><i class="icon-collapse"></i> ' . $translations['Collapse All'] . '</a>
			<a class="btn btn-link" href="#" id="expand_all"><i class="icon-expand"></i> ' . $translations['Expand All'] . '</a>
			' . (!$use_radio ? '
				<a class="btn btn-link" href="#" id="check_all"><i class="icon-check"></i> ' . $translations['Check All'] . '</a>
				<a class="btn btn-link" href="#" id="uncheck_all"><i class="icon-check-empty"></i> ' . $translations['Uncheck All'] . '</a>' : '') . ($use_search ? '
				<div class="row">
					<label class="control-label col-lg-6" for="search_cat">' . $translations['search'] . ' :</label>
					<div class="col-lg-6">
						<input type="text" name="search_cat" id="search_cat"/>
					</div>
				</div>' : '') . '</div>';
        $home_is_selected = false;
        if (is_array($selected_cat)) {
            foreach ($selected_cat as $cat) {
                if (is_array($cat)) {
                    $disabled = in_array($cat['id_category'], $disabled_categories);
                    if ($cat['id_category'] != $root['id_category']) {
                        $html .= '<input ' . ($disabled ? 'disabled="disabled"' : '') . ' type="hidden" name="' . $input_name . '" value="' . $cat['id_category'] . '" >';
                    } else {
                        $home_is_selected = true;
                    }
                } else {
                    $disabled = in_array($cat, $disabled_categories);
                    if ($cat != $root['id_category']) {
                        $html .= '<input ' . ($disabled ? 'disabled="disabled"' : '') . ' type="hidden" name="' . $input_name . '" value="' . $cat . '" >';
                    } else {
                        $home_is_selected = true;
                    }
                }
            }
        }
        $root_input = '';
        if ($root['id_category'] != (int) Configuration::get('PS_ROOT_CATEGORY') || Tools::is_submit('ajax') && Tools::get_value('action') == 'getCategoriesFromRootCategory') {
            $root_input = '
				<p class="checkbox"><i class="icon-folder-open"></i><label>
					<input type="' . (!$use_radio ? 'checkbox' : 'radio') . '" name="' . $input_name . '" value="' . $root['id_category'] . '" ' . ($home_is_selected ? 'checked' : '') . ' onclick="clickOnCategoryBox($(this));" />' . $root['name'] . '</label></p>';
        }
        $html .= '
			<div class="container">
				<div class="well">
					<ul id="categories-treeview">
						<li id="' . $root['id_category'] . '" class="hasChildren">
							<span class="folder">' . $root_input . ' </span>
							<ul>
								<li><span class="placeholder">&nbsp;</span></li>
						  	</ul>
						</li>
					</ul>
				</div>
			</div>';
        if ($use_search) {
            $html .= '<script type="text/javascript">searchCategory();</script>';
        }
        return $html;
    }
    /**
     * Render shop list
     *
     * @return string
     *
     * @deprecated deprecated since 1.0.0 use HelperShop->getRenderedShopList
     * @throws PrestaShopException
     */
    public static function render_shop_list(): ?string
    {
        Tools::display_as_deprecated();
        if (!Shop::is_feature_active() || Shop::get_total_shops(false) < 2) {
            return null;
        }
        $tree = Shop::get_tree();
        $context = Context::get_context();
        // Get default value
        $shop_context = Shop::get_context();
        /** @var AdminController $controller */
        $controller = $context->controller;
        if ($shop_context == Shop::CONTEXT_ALL || $controller->multishop_context_group == false && $shop_context == Shop::CONTEXT_GROUP) {
            $value = '';
        } elseif ($shop_context == Shop::CONTEXT_GROUP) {
            $value = 'g-' . Shop::get_context_shop_group_id();
        } else {
            $value = 's-' . Shop::get_context_shop_id();
        }
        // Generate HTML
        $url = $_SERVER['REQUEST_URI'] . ($_SERVER['QUERY_STRING'] ? '&' : '?') . 'setShopContext=';
        // $html = '<a href="#"><i class="icon-home"></i> '.$shop->name.'</a>';
        $html = '<select class="shopList" onchange="location.href = \'' . htmlspecialchars($url) . '\'+$(this).val();">';
        $html .= '<option value="" class="first">' . Translate::get_admin_translation('All shops') . '</option>';
        foreach ($tree as $group_id => $group_data) {
            if (!isset($controller->multishop_context) || $controller->multishop_context & Shop::CONTEXT_GROUP) {
                $html .= '<option class="group" value="g-' . $group_id . '"' . (empty($value) && $shop_context == Shop::CONTEXT_GROUP || $value == 'g-' . $group_id ? ' selected="selected"' : '') . ($controller->multishop_context_group == false ? ' disabled="disabled"' : '') . '>' . Translate::get_admin_translation('Group:') . ' ' . htmlspecialchars((string) $group_data['name']) . '</option>';
            } else {
                $html .= '<optgroup class="group" label="' . Translate::get_admin_translation('Group:') . ' ' . htmlspecialchars((string) $group_data['name']) . '"' . ($controller->multishop_context_group == false ? ' disabled="disabled"' : '') . '>';
            }
            if (!isset($controller->multishop_context) || $controller->multishop_context & Shop::CONTEXT_SHOP) {
                foreach ($group_data['shops'] as $shop_id => $shop_data) {
                    if ($shop_data['active']) {
                        $html .= '<option value="s-' . $shop_id . '" class="shop"' . ($value == 's-' . $shop_id ? ' selected="selected"' : '') . '>' . ($controller->multishop_context_group == false ? htmlspecialchars((string) $group_data['name']) . ' - ' : '') . $shop_data['name'] . '</option>';
                    }
                }
            }
            if (!(!isset($controller->multishop_context) || $controller->multishop_context & Shop::CONTEXT_GROUP)) {
                $html .= '</optgroup>';
            }
        }
        return $html . '</select>';
    }
    /**
     * @param string $tpl
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function set_tpl($tpl): void
    {
        $this->tpl = $this->create_template($tpl);
    }
    /**
     * Create a template from the override file, else from the base file.
     *
     * @param string $tplName filename
     *
     * @return Smarty_Internal_Template|object
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function create_template(string $tpl_name)
    {
        $override_tpl_path = $this->get_override_template_path($tpl_name);
        if ($override_tpl_path) {
            return $this->context->smarty->create_template($override_tpl_path, $this->context->smarty);
        }
        return $this->context->smarty->create_template($this->base_folder . $tpl_name, $this->context->smarty);
    }
    /**
     * default behaviour for helper is to return a tpl fetched
     *
     * @return string
     *
     * @throws SmartyException
     */
    public function generate()
    {
        $this->tpl->assign($this->tpl_vars);
        return $this->tpl->fetch();
    }
    /**
     * Render a form with potentials required fields
     *
     * @param string $className
     * @param string $identifier
     * @param array $tableFields
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render_required_fields($class_name, $identifier, $table_fields)
    {
        $rules = call_user_func_array([$class_name, 'getValidationRules'], [$class_name]);
        $required_class_fields = [$identifier];
        foreach ($rules['required'] as $required) {
            $required_class_fields[] = $required;
        }
        /** @var ObjectModel $object */
        $object = new $class_name();
        $res = $object->get_fields_required_database();
        $required_fields = [];
        foreach ($res as $row) {
            $required_fields[(int) $row['id_required_field']] = $row['field_name'];
        }
        $this->tpl_vars = ['table_fields' => $table_fields, 'irow' => 0, 'required_class_fields' => $required_class_fields, 'required_fields' => $required_fields, 'current' => $this->current_index, 'token' => $this->token];
        $tpl = $this->create_template('helpers/required_fields.tpl');
        $tpl->assign($this->tpl_vars);
        return $tpl->fetch();
    }
    /**
     * @param array $modulesList
     */
    public function render_modules_list($modules_list): string
    {
        Tools::display_as_deprecated();
        return '';
    }
    /**
     * use translations files to replace english expression.
     *
     * @param string $string term or expression in english
     * @param string $class deprecated
     * @param bool $addslashes if set to true, the return value will pass through addslashes(). Otherwise, stripslashes().
     * @param bool $htmlentities if set to true(default), the return value will pass through htmlentities($string, ENT_QUOTES, 'utf-8')
     *
     * @return string the translation if available, or the english default text.
     */
    protected function l($string, $class = 'Helper', $addslashes = false, $htmlentities = true)
    {
        return Translate::get_admin_translation($string, $class, $addslashes, $htmlentities);
    }
    /**
     * Returns path to override template file, if it exists
     *
     * @return string | false
     * @throws PrestaShopException
     */
    protected function get_override_template_path(string $tpl_name): string|false
    {
        if ($this->override_folder) {
            $controller = $this->get_controller();
            if ($controller instanceof Module_Admin_Controller) {
                $path = $controller->get_template_path() . $this->override_folder . $this->base_folder . $tpl_name;
                if (file_exists($path)) {
                    return $path;
                }
            } elseif ($this->module) {
                $path = _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/_configure/' . $this->override_folder . $this->base_folder . $tpl_name;
                if (file_exists($path)) {
                    return $path;
                }
            } else {
                if (!Configuration::get('PS_DISABLE_OVERRIDES')) {
                    // check override file in /override/ directory
                    $path = $this->context->smarty->get_template_dir(1) . $this->override_folder . $this->base_folder . $tpl_name;
                    if (file_exists($path)) {
                        return $path;
                    }
                }
                $path = $this->context->smarty->get_template_dir(0) . 'controllers' . DIRECTORY_SEPARATOR . $this->override_folder . $this->base_folder . $tpl_name;
                if (file_exists($path)) {
                    return $path;
                }
            }
        } elseif ($this->module) {
            $path = _PS_MODULE_DIR_ . $this->module->name . '/views/templates/admin/_configure/' . $this->base_folder . $tpl_name;
            if (file_exists($path)) {
                return $path;
            }
        }
        return false;
    }
    /**
     * @return AdminController
     */
    protected function get_controller()
    {
        /** @var AdminController $controller */
        $controller = $this->context->controller;
        if (!$controller instanceof Admin_Controller) {
            trigger_error('Helper class used outside AdminController context', E_USER_WARNING);
        }
        return $controller;
    }
}