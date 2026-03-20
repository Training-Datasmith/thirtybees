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
 * Class WebserviceOutputJSON
 */
class Webservice_Output_Json_Core implements Webservice_Output_Interface
{
    /**
     * @var string
     */
    public $doc_url = '';
    /**
     * @var string
     */
    protected $ws_url;
    /**
     * @var string
     */
    protected $schema_to_display;
    /**
     * @var array Current entity
     */
    protected $current_entity;
    /**
     * @var array Current association
     */
    protected $current_associated_entity = [];
    /**
     * @var array Json content
     */
    protected $content = [];
    /**
     * WebserviceOutputJSON constructor.
     *
     * @param array $languages
     */
    public function __construct(public $languages = [])
    {
    }
    /**
     * @param string $schema
     */
    public function set_schema_to_display($schema): static
    {
        if (is_string($schema)) {
            $this->schema_to_display = $schema;
        }
        return $this;
    }
    /**
     * @return string
     */
    public function get_schema_to_display()
    {
        return $this->schema_to_display;
    }
    /**
     * @param string $url
     */
    public function set_ws_url($url): static
    {
        $this->ws_url = $url;
        return $this;
    }
    /**
     * @return string
     */
    public function get_ws_url()
    {
        return $this->ws_url;
    }
    public function get_content_type(): string
    {
        return 'application/json';
    }
    /**
     * @param string $message
     * @param int|null $code
     * @param array $extra
     */
    public function render_errors($message, $code = null, $extra = []): string
    {
        $error = ['message' => $message];
        if (!is_null($code)) {
            $error['code'] = $code;
        }
        if (!is_null($extra)) {
            $error = array_merge($extra, $error);
        }
        $this->content['errors'][] = $error;
        return '';
    }
    /**
     * @param array $field
     */
    public function render_field($field): string
    {
        $is_association = isset($field['is_association']) && $field['is_association'] == true;
        if (!$is_association) {
            // Case 1 : fields of the current entity (not an association)
            $this->current_entity[$field['sqlId']] = $this->get_field_value($field);
        } else {
            // Case 2 : fields of an associated entity to the current one
            $this->current_associated_entity[] = ['name' => $field['entities_name'], 'key' => $field['sqlId'], 'value' => $this->get_field_value($field)];
        }
        return '';
    }
    /**
     * @param string $nodeName
     * @param array $params
     * @param array|null $moreAttr
     * @param bool $hasChild
     */
    public function render_node_header($node_name, $params, $more_attr = null, $has_child = true): string
    {
        // api ?
        static $is_api_call = false;
        if ($node_name == 'api' && $is_api_call == false) {
            $is_api_call = true;
        }
        if ($is_api_call && !in_array($node_name, ['description', 'schema', 'api'])) {
            $this->content[] = $node_name;
        }
        if (isset($more_attr, $more_attr['id'])) {
            $this->content[$params['objectsNodeName']][] = ['id' => $more_attr['id']];
        }
        return '';
    }
    /**
     * @return string
     */
    public function get_node_name(array $params)
    {
        return $params['objectNodeName'] ?? '';
    }
    /**
     * @param string $nodeName
     * @param array $params
     */
    public function render_node_footer($node_name, $params): string
    {
        if (isset($params['objectNodeName']) && $params['objectNodeName'] == $node_name) {
            if (array_key_exists('display', $_GET)) {
                $this->content[$params['objectsNodeName']][] = $this->current_entity;
            } else {
                $this->content[$params['objectNodeName']] = $this->current_entity;
            }
            $this->current_entity = [];
        }
        if (count($this->current_associated_entity) > 0) {
            $current = [];
            $name = $this->current_associated_entity[0]['name'];
            foreach ($this->current_associated_entity as $element) {
                $current[$element['key']] = $element['value'];
            }
            $this->current_entity['associations'][$name][] = $current;
            $this->current_associated_entity = [];
        }
        return '';
    }
    /**
     * @param string $content
     */
    public function override_content($content): string
    {
        $options = 0;
        if (Tools::get_value('unescaped') === 'true') {
            $options |= JSON_UNESCAPED_UNICODE;
        }
        if (Tools::get_value('pretty') === 'true') {
            $options |= JSON_PRETTY_PRINT;
        }
        $content = '';
        return $content . json_encode($this->content, $options);
    }
    /**
     * @param array $languages
     */
    public function set_languages($languages): static
    {
        $this->languages = $languages;
        return $this;
    }
    public function render_association_wrapper_header(): string
    {
        return '';
    }
    public function render_association_wrapper_footer(): string
    {
        return '';
    }
    /**
     * @param ObjectModel$obj
     * @param array $params
     * @param string $assocName
     * @param bool $closedTags
     */
    public function render_association_header($obj, $params, $assoc_name, $closed_tags = false): string
    {
        return '';
    }
    /**
     * @param ObjectModel $obj
     * @param array $params
     * @param string $assocName
     */
    public function render_association_footer($obj, $params, $assoc_name): string
    {
        return '';
    }
    public function render_errors_header(): string
    {
        return '';
    }
    public function render_errors_footer(): string
    {
        return '';
    }
    /**
     * @param array $field
     */
    public function render_association_field($field): string
    {
        return '';
    }
    /**
     * @param array $field
     */
    public function renderi18n_field($field): string
    {
        return '';
    }
    /**
     * Returns field value
     *
     * @return string
     */
    protected function get_field_value(array $field)
    {
        $value = $field['value'] ?? null;
        if (is_array($value)) {
            $tmp = [];
            foreach ($this->languages as $id_lang) {
                $tmp[] = ['id' => $id_lang, 'value' => array_key_exists($id_lang, $value) ? $value[$id_lang] : ''];
            }
            if (count($tmp) == 1) {
                $value = $tmp[0]['value'];
            } else {
                $value = $tmp;
            }
        }
        return $value;
    }
}