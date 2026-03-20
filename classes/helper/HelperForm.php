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
 * Class HelperFormCore
 */
class Helper_Form_Core extends Helper
{
    /**
     * @var int $id
     */
    public $id;
    /**
     * @var bool $first_call
     */
    public $first_call = true;
    /**
     * @var array of forms fields
     */
    protected $fields_form = [];
    /**
     * @var array values of form fields
     */
    public $fields_value = [];
    /**
     * @var string $name_controller
     */
    public $name_controller = '';
    /**
     * @var string if not null, a title will be added on that list
     */
    public $title;
    /**
     * @var string Used to override default 'submitAdd' parameter in form action attribute
     */
    public $submit_action;
    /**
     * @var string
     */
    public $token;
    /**
     * @var array $languages
     */
    public $languages;
    /**
     * @var int
     */
    public $default_form_language;
    /**
     * @var bool
     */
    public $allow_employee_form_lang;
    /**
     * @var bool $show_cancel_button
     */
    public $show_cancel_button = false;
    /**
     * @var string $back_url
     */
    public $back_url = '#';
    /**
     * HelperFormCore constructor.
     */
    public function __construct()
    {
        $this->base_folder = 'helpers/form/';
        $this->base_tpl = 'form.tpl';
        parent::__construct();
    }
    /**
     * @param array $fieldsForm
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function generate_form($fields_form)
    {
        $this->fields_form = $fields_form;
        return $this->generate();
    }
    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function generate()
    {
        $this->tpl = $this->create_template($this->base_tpl);
        if (is_null($this->submit_action)) {
            $this->submit_action = 'submitAdd' . $this->table;
        }
        $categories = true;
        $color = true;
        $date = true;
        $tinymce = true;
        $textarea_autosize = true;
        foreach ($this->fields_form as $fieldset_key => &$fieldset) {
            if (isset($fieldset['form']['tabs'])) {
                $tabs[] = $fieldset['form']['tabs'];
            }
            if (isset($fieldset['form']['input'])) {
                foreach ($fieldset['form']['input'] as $key => &$params) {
                    // If the condition is not met, the field will not be displayed
                    if (isset($params['condition']) && !$params['condition']) {
                        unset($this->fields_form[$fieldset_key]['form']['input'][$key]);
                    }
                    $controller = $this->get_controller();
                    switch ($params['type']) {
                        case 'select':
                            $field_name = (string) $params['name'];
                            // If multiple select check that 'name' field is suffixed with '[]'
                            if (isset($params['multiple']) && $params['multiple'] && stripos($field_name, '[]') === false) {
                                $params['name'] .= '[]';
                            }
                            break;
                        case 'categories':
                            if ($categories) {
                                if (!isset($params['tree']['id'])) {
                                    throw new Presta_Shop_Exception('Id must be filled for categories tree');
                                }
                                $tree = new Helper_Tree_Categories($params['tree']['id'], $params['tree']['title'] ?? null);
                                if (isset($params['name'])) {
                                    $tree->set_input_name($params['name']);
                                }
                                if (isset($params['tree']['selected_categories'])) {
                                    $tree->set_selected_categories($params['tree']['selected_categories']);
                                }
                                if (isset($params['tree']['disabled_categories'])) {
                                    $tree->set_disabled_categories($params['tree']['disabled_categories']);
                                }
                                if (isset($params['tree']['root_category'])) {
                                    $tree->set_root_category($params['tree']['root_category']);
                                }
                                if (isset($params['tree']['use_search'])) {
                                    $tree->set_use_search($params['tree']['use_search']);
                                }
                                if (isset($params['tree']['use_checkbox'])) {
                                    $tree->set_use_check_box($params['tree']['use_checkbox']);
                                }
                                if (isset($params['tree']['set_data'])) {
                                    $tree->set_data($params['tree']['set_data']);
                                }
                                $this->context->smarty->assign('categories_tree', $tree->render());
                                $categories = false;
                            }
                            break;
                        case 'file':
                            $uploader = new Helper_Uploader();
                            $uploader->set_id($params['id'] ?? null);
                            $uploader->set_name($params['name']);
                            $uploader->set_url($params['url'] ?? null);
                            $uploader->set_multiple($params['multiple'] ?? false);
                            $uploader->set_use_ajax($params['ajax'] ?? false);
                            $uploader->set_max_files($params['max_files'] ?? null);
                            // Generate dynamic delete_url
                            if (isset($params['delete_url']) && $params['delete_url'] === true) {
                                $params['delete_url'] = $this->current_index . '&' . $this->identifier . '=' . $this->id . '&token=' . $this->token . '&action=deleteImage&inputName=' . $params['name'];
                            }
                            if (isset($params['files']) && $params['files']) {
                                $uploader->set_files($params['files']);
                            } elseif (isset($params['image']) && $params['image']) {
                                // Use for retrocompatibility
                                $uploader->set_files([0 => ['type' => Helper_Uploader::TYPE_IMAGE, 'image' => $params['image'], 'size' => $params['size'] ?? null, 'delete_url' => $params['delete_url'] ?? null]]);
                            }
                            if (isset($params['file']) && $params['file']) {
                                // Use for retrocompatibility
                                $uploader->set_files([0 => ['type' => Helper_Uploader::TYPE_FILE, 'size' => $params['size'] ?? null, 'delete_url' => $params['delete_url'] ?? null, 'download_url' => $params['file']]]);
                            }
                            if (isset($params['thumb']) && $params['thumb']) {
                                // Use for retrocompatibility
                                $uploader->set_files([0 => ['type' => Helper_Uploader::TYPE_IMAGE, 'image' => '<img src="' . $params['thumb'] . '" alt="' . ($params['title'] ?? '') . '" title="' . ($params['title'] ?? '') . '" />']]);
                            }
                            $uploader->set_title($params['title'] ?? null);
                            $params['file'] = $uploader->render();
                            break;
                        case 'color':
                            if ($color) {
                                // Added JS file
                                $controller->add_jquery_plugin('colorpicker');
                                $color = false;
                            }
                            break;
                        case 'date':
                            if ($date) {
                                $controller->add_jquery_ui('ui.datepicker');
                                $date = false;
                            }
                            break;
                        case 'textarea':
                            if ($tinymce) {
                                $iso = $this->context->language->iso_code;
                                $this->tpl_vars['iso'] = file_exists(_PS_ROOT_DIR_ . '/js/tiny_mce/langs/' . $iso . '.js') ? $iso : 'en';
                                $this->tpl_vars['path_css'] = _THEME_CSS_DIR_;
                                $this->tpl_vars['ad'] = __PS_BASE_URI__ . basename(_PS_ADMIN_DIR_);
                                $this->tpl_vars['tinymce'] = true;
                                $controller->add_js(_PS_JS_DIR_ . 'tiny_mce/tiny_mce.js');
                                $controller->add_js(_PS_JS_DIR_ . 'admin/tinymce.inc.js');
                                $tinymce = false;
                            }
                            if ($textarea_autosize) {
                                $controller->add_jquery_plugin('autosize');
                                $textarea_autosize = false;
                            }
                            break;
                        case 'tags':
                            $controller->add_jquery_plugin('tagify');
                            break;
                        case 'code':
                            $controller->add_js(_PS_JS_DIR_ . 'ace/ace.js');
                            $controller->add_css(_PS_JS_DIR_ . 'ace/aceinput.css');
                            break;
                        case 'shop':
                            $disable_shops = $params['disable_shared'] ?? false;
                            $params['html'] = $this->render_asso_shop($disable_shops);
                            if (Shop::get_total_shops(false) == 1) {
                                if (isset($this->fields_form[$fieldset_key]['form']['force']) && !$this->fields_form[$fieldset_key]['form']['force'] || !isset($this->fields_form[$fieldset_key]['form']['force'])) {
                                    unset($this->fields_form[$fieldset_key]['form']['input'][$key]);
                                }
                            }
                            break;
                    }
                }
            }
        }
        $this->tpl->assign(['title' => $this->title, 'toolbar_btn' => $this->toolbar_btn, 'show_toolbar' => $this->show_toolbar, 'toolbar_scroll' => $this->toolbar_scroll, 'submit_action' => $this->submit_action, 'firstCall' => $this->first_call, 'current' => $this->current_index, 'token' => $this->token, 'table' => $this->table, 'identifier' => $this->identifier, 'name_controller' => $this->name_controller, 'languages' => $this->languages, 'currency_left_sign' => $this->context->currency->get_sign('left'), 'currency_right_sign' => $this->context->currency->get_sign('right'), 'current_id_lang' => $this->context->language->id, 'defaultFormLanguage' => $this->default_form_language, 'allowEmployeeFormLang' => $this->allow_employee_form_lang, 'form_id' => $this->id, 'tabs' => $tabs ?? null, 'fields' => $this->fields_form, 'fields_value' => $this->fields_value, 'required_fields' => $this->get_fields_required(), 'vat_number' => Module::is_installed('vatnumber') && file_exists(_PS_MODULE_DIR_ . 'vatnumber/ajax.php'), 'module_dir' => _MODULE_DIR_, 'base_url' => $this->context->shop->get_base_url(), 'contains_states' => isset($this->fields_value['id_country']) && isset($this->fields_value['id_state']) ? Country::contains_states($this->fields_value['id_country']) : null, 'show_cancel_button' => $this->show_cancel_button, 'back_url' => $this->back_url]);
        return parent::generate();
    }
    /**
     * Return true if there are required fields
     *
     * @return bool
     */
    public function get_fields_required()
    {
        foreach ($this->fields_form as $fieldset) {
            if (isset($fieldset['form']['input'])) {
                foreach ($fieldset['form']['input'] as $input) {
                    if (!empty($input['required']) && $input['type'] != 'radio') {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    /**
     * Render an area to determinate shop association
     *
     * @param bool $disableShared
     * @param string|null $templateDirectory
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render_asso_shop($disable_shared = false, $template_directory = null)
    {
        if (!Shop::is_feature_active()) {
            return '';
        }
        $assos = [];
        if ((int) $this->id) {
            foreach (Db::read_only()->get_array((new Db_Query())->select('`id_shop`, `' . bq_sql($this->identifier) . '`')->from(bq_sql($this->table) . '_shop')->where('`' . bq_sql($this->identifier) . '` = ' . (int) $this->id)) as $row) {
                $assos[$row['id_shop']] = $row['id_shop'];
            }
        } else {
            switch (Shop::get_context()) {
                case Shop::CONTEXT_SHOP:
                    $assos[Shop::get_context_shop_id()] = Shop::get_context_shop_id();
                    break;
                case Shop::CONTEXT_GROUP:
                    foreach (Shop::get_shops(false, Shop::get_context_shop_group_id(), true) as $id_shop) {
                        $assos[$id_shop] = $id_shop;
                    }
                    break;
                default:
                    foreach (Shop::get_shops(false, null, true) as $id_shop) {
                        $assos[$id_shop] = $id_shop;
                    }
                    break;
            }
        }
        return $this->render_shop_association($assos, $template_directory);
    }
    /**
     * @param string $templateDirectory
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render_shop_association(array $selected, $template_directory = null)
    {
        $tree = new Helper_Tree_Shops('shop-tree', 'Shops');
        if (isset($template_directory)) {
            $tree->set_template_directory($template_directory);
        }
        $tree->set_selected_shops($selected);
        $tree->set_attribute('table', $this->table);
        return $tree->render();
    }
}