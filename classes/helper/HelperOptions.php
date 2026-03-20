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
 * Use this helper to generate preferences forms, with values stored in the configuration table
 */
class Helper_Options_Core extends Helper
{
    /**
     * @var bool $required
     */
    public $required = false;
    /**
     * @var int $id
     */
    public $id;
    /**
     * HelperOptionsCore constructor.
     */
    public function __construct()
    {
        $this->base_folder = 'helpers/options/';
        $this->base_tpl = 'options.tpl';
        parent::__construct();
    }
    /**
     * Generate a form for options
     *
     * @param array $optionList
     *
     * @return string html
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function generate_options($option_list)
    {
        $this->tpl = $this->create_template($this->base_tpl);
        $tab = Tab::get_tab($this->context->language->id, $this->id);
        $languages = Language::get_languages(false);
        $use_multishop = false;
        $hide_multishop_checkbox = Shop::get_total_shops(false, null) < 2;
        foreach ($option_list as $category => $category_data) {
            if (!is_array($category_data)) {
                continue;
            }
            if (!isset($category_data['image']) && $tab) {
                $category_data['image'] = (!empty($tab['module']) && file_exists($_SERVER['DOCUMENT_ROOT'] . _MODULE_DIR_ . $tab['module'] . '/' . $tab['class_name'] . '.gif') ? _MODULE_DIR_ . $tab['module'] . '/' : '../img/t/') . $tab['class_name'] . '.gif';
            }
            if (!isset($category_data['fields'])) {
                $category_data['fields'] = [];
            }
            $category_data['hide_multishop_checkbox'] = true;
            if (isset($category_data['tabs'])) {
                $tabs[$category] = $category_data['tabs'];
                $tabs[$category]['misc'] = $this->l('Miscellaneous');
            }
            foreach ($category_data['fields'] as $key => $field) {
                if (empty($field['no_multishop_checkbox']) && !$hide_multishop_checkbox) {
                    $category_data['hide_multishop_checkbox'] = false;
                }
                // Set field value unless explicitly denied
                if (!isset($field['auto_value']) || $field['auto_value']) {
                    $field['value'] = $this->get_option_value($key, $field);
                }
                // Check if var is invisible (can't edit it in current shop context), or disable (use default value for multishop)
                $is_disabled = $is_invisible = false;
                if (Shop::is_feature_active()) {
                    if (isset($field['visibility']) && $field['visibility'] > Shop::get_context()) {
                        $is_disabled = true;
                        $is_invisible = true;
                    } elseif (Shop::get_context() != Shop::CONTEXT_ALL && !Configuration::is_overriden_by_current_context($key)) {
                        $is_disabled = true;
                    }
                }
                $field['is_disabled'] = $is_disabled;
                $field['is_invisible'] = $is_invisible;
                $field['required'] ??= $this->required;
                $controller = $this->get_controller();
                if ($field['type'] === 'color') {
                    $controller->add_jquery_plugin('colorpicker');
                }
                if ($field['type'] === 'textarea' || $field['type'] === 'textareaLang') {
                    $controller->add_jquery_plugin('autosize');
                }
                if ($field['type'] === 'code') {
                    $controller->add_js(_PS_JS_DIR_ . 'ace/ace.js');
                    $controller->add_js(_PS_JS_DIR_ . 'ace/ext-language_tools.js');
                    $controller->add_js(_PS_JS_DIR_ . 'ace/snippets/' . $field['mode'] . '.js');
                    $controller->add_css(_PS_JS_DIR_ . 'ace/aceinput.css');
                }
                if ($field['type'] == 'tags') {
                    $controller->add_jquery_plugin('tagify');
                }
                if ($field['type'] == 'file') {
                    $uploader = new Helper_Uploader();
                    $uploader->set_id($field['id'] ?? null);
                    $uploader->set_name($field['name']);
                    $uploader->set_url($field['url'] ?? null);
                    $uploader->set_multiple($field['multiple'] ?? false);
                    $uploader->set_use_ajax($field['ajax'] ?? false);
                    $uploader->set_max_files($field['max_files'] ?? null);
                    if (isset($field['files']) && $field['files']) {
                        $uploader->set_files($field['files']);
                    } elseif (isset($field['image']) && $field['image']) {
                        // Use for retrocompatibility
                        $uploader->set_files([0 => ['type' => Helper_Uploader::TYPE_IMAGE, 'image' => $field['image'], 'size' => $field['size'] ?? null, 'delete_url' => $field['delete_url'] ?? null]]);
                    }
                    if (isset($field['file']) && $field['file']) {
                        // Use for retrocompatibility
                        $uploader->set_files([0 => ['type' => Helper_Uploader::TYPE_FILE, 'size' => $field['size'] ?? null, 'delete_url' => $field['delete_url'] ?? null, 'download_url' => $field['file']]]);
                    }
                    if (isset($field['thumb']) && $field['thumb']) {
                        // Use for retrocompatibility
                        $uploader->set_files([0 => ['type' => Helper_Uploader::TYPE_IMAGE, 'image' => '<img src="' . $field['thumb'] . '" alt="' . $field['title'] . '" title="' . $field['title'] . '" />']]);
                    }
                    $uploader->set_title($field['title'] ?? null);
                    $field['file'] = $uploader->render();
                }
                // Cast options values if specified
                if ($field['type'] == 'select' && isset($field['cast'])) {
                    foreach ($field['list'] as $option_key => $option) {
                        $field['list'][$option_key][$field['identifier']] = Tools::cast_input($field['cast'], $option[$field['identifier']]);
                    }
                }
                // Fill values for all languages for all lang fields
                if (str_ends_with((string) $field['type'], 'Lang')) {
                    foreach ($languages as $language) {
                        if ($field['type'] == 'textLang') {
                            $value = Tools::get_value($key . '_' . $language['id_lang'], Configuration::get($key, $language['id_lang']));
                        } elseif ($field['type'] == 'textareaLang') {
                            $value = Configuration::get($key, $language['id_lang']);
                        } elseif ($field['type'] == 'selectLang') {
                            $value = Configuration::get($key, $language['id_lang']);
                        }
                        $field['languages'][$language['id_lang']] = $value ?? '';
                        if (!is_array($field['value'])) {
                            $field['value'] = [];
                        }
                        $field['value'][$language['id_lang']] = $this->get_option_value($key . '_' . strtoupper((string) $language['iso_code']), $field);
                    }
                }
                // Multishop default value
                $field['multishop_default'] = false;
                if (Shop::is_feature_active() && Shop::get_context() != Shop::CONTEXT_ALL && !$is_invisible) {
                    $field['multishop_default'] = true;
                    $use_multishop = true;
                }
                // Assign the modifications back to parent array
                $category_data['fields'][$key] = $field;
                // Is at least one required field present?
                if (isset($field['required']) && $field['required']) {
                    $category_data['required_fields'] = true;
                }
            }
            // Assign the modifications back to parent array
            $option_list[$category] = $category_data;
        }
        $this->tpl->assign(['title' => $this->title, 'toolbar_btn' => $this->toolbar_btn, 'show_toolbar' => $this->show_toolbar, 'toolbar_scroll' => $this->toolbar_scroll, 'current' => $this->current_index, 'table' => $this->table, 'token' => $this->token, 'tabs' => $tabs ?? null, 'option_list' => $option_list, 'current_id_lang' => $this->context->language->id, 'languages' => $languages ?? null, 'currency_left_sign' => $this->context->currency->get_sign('left'), 'currency_right_sign' => $this->context->currency->get_sign('right'), 'use_multishop' => $use_multishop]);
        return parent::generate();
    }
    /**
     * @param string $key
     * @param array $field
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function get_option_value($key, $field)
    {
        if ($field['type'] === 'code') {
            // don't perform any value sanitization and preprocessing for code fields
            $value = Tools::get_value_raw($key, Configuration::get($key));
            if (isset($field['defaultValue']) && !$value) {
                return $field['defaultValue'];
            }
            return $value;
        }
        $value = Tools::get_value($key, Configuration::get($key));
        if (!Validate::is_clean_html($value)) {
            $value = Configuration::get($key);
        }
        if (isset($field['defaultValue']) && !$value) {
            $value = $field['defaultValue'];
        }
        return Tools::purify_html($value);
    }
}