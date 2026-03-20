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
 * Class WebserviceOutputXMLCore
 */
class Webservice_Output_Xml_Core implements Webservice_Output_Interface
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
        return 'text/xml';
    }
    /**
     * WebserviceOutputXMLCore constructor.
     *
     * @param array $languages
     */
    public function __construct(public $languages = [])
    {
    }
    /**
     * @param array $languages
     */
    public function set_languages($languages): static
    {
        $this->languages = $languages;
        return $this;
    }
    public function render_errors_header(): string
    {
        return '<errors>' . "\n";
    }
    public function render_errors_footer(): string
    {
        return '</errors>' . "\n";
    }
    /**
     * @param string $message
     * @param int|null $code
     * @param array $extra
     */
    public function render_errors($message, $code = null, $extra = []): string
    {
        $str_output = '<error>' . "\n";
        if ($code !== null) {
            $str_output .= '<code><![CDATA[' . $code . ']]></code>' . "\n";
        }
        $str_output .= '<message><![CDATA[' . $message . ']]></message>' . "\n";
        if (!is_null($extra)) {
            $str_output .= "<additional_info>\n";
            foreach ($extra as $name => $value) {
                $str_output .= '<' . $name . '><![CDATA[' . $value . ']]></' . $name . '>' . "\n";
            }
            $str_output .= "</additional_info>\n";
        }
        return $str_output . ('</error>' . "\n");
    }
    /**
     * @param array $field
     */
    public function render_field($field): string
    {
        $ret = '';
        $node_content = '';
        $value = $field['value'] ?? null;
        $ret .= '<' . $field['sqlId'];
        // display i18n fields
        if (isset($field['i18n']) && $field['i18n']) {
            foreach ($this->languages as $language) {
                $more_attr = '';
                if (isset($field['synopsis_details']) || is_array($value)) {
                    $more_attr .= ' xlink:href="' . $this->get_ws_url() . 'languages/' . $language . '"';
                    if (isset($field['synopsis_details']) && $this->schema_to_display != 'blank') {
                        $more_attr .= ' format="isUnsignedId" ';
                    }
                }
                $node_content .= '<language id="' . $language . '"' . $more_attr . '>';
                if (is_array($value) && isset($value[$language])) {
                    $node_content .= '<![CDATA[' . $value[$language] . ']]>';
                }
                $node_content .= '</language>';
            }
        } else {
            if (array_key_exists('xlink_resource', $field) && $this->schema_to_display != 'blank') {
                if (!is_array($field['xlink_resource'])) {
                    $ret .= ' xlink:href="' . $this->get_ws_url() . $field['xlink_resource'] . '/' . $value . '"';
                } else {
                    $ret .= ' xlink:href="' . $this->get_ws_url() . $field['xlink_resource']['resourceName'] . '/' . (isset($field['xlink_resource']['subResourceName']) ? $field['xlink_resource']['subResourceName'] . '/' . $field['object_id'] . '/' : '') . $value . '"';
                }
            }
            if (isset($field['getter']) && $this->schema_to_display != 'blank') {
                $ret .= ' notFilterable="true"';
            }
            if (isset($field['setter']) && $field['setter'] == false && $this->schema_to_display == 'synopsis') {
                $ret .= ' read_only="true"';
            }
            if ($value != '') {
                $node_content .= '<![CDATA[' . $value . ']]>';
            }
        }
        if (isset($field['encode'])) {
            $ret .= ' encode="' . $field['encode'] . '"';
        }
        if (!empty($field['synopsis_details']) && $this->schema_to_display !== 'blank') {
            foreach ($field['synopsis_details'] as $name => $detail) {
                $ret .= ' ' . $name . '="' . (is_array($detail) ? implode(' ', $detail) : $detail) . '"';
            }
        }
        $ret .= '>';
        $ret .= $node_content;
        return $ret . ('</' . $field['sqlId'] . '>' . "\n");
    }
    /**
     * @param string $nodeName
     * @param array $params
     * @param array|null $moreAttr
     * @param bool $hasChild
     */
    public function render_node_header($node_name, $params, $more_attr = null, $has_child = true): string
    {
        $string_attr = '';
        if (is_array($more_attr)) {
            foreach ($more_attr as $key => $attr) {
                if ($key === 'xlink_resource') {
                    $string_attr .= ' xlink:href="' . $attr . '"';
                } else {
                    $string_attr .= ' ' . $key . '="' . $attr . '"';
                }
            }
        }
        $end_tag = !$has_child ? '/>' : '>';
        return '<' . $node_name . $string_attr . $end_tag . "\n";
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
        return '</' . $node_name . '>' . "\n";
    }
    /**
     * @param string $content
     */
    public function override_content($content): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">' . "\n";
        $xml .= $content;
        return $xml . ('</prestashop>' . "\n");
    }
    public function render_association_wrapper_header(): string
    {
        return '<associations>' . "\n";
    }
    public function render_association_wrapper_footer(): string
    {
        return '</associations>' . "\n";
    }
    /**
     * @param ObjectModel $obj
     * @param array $params
     * @param string $assocName
     * @param bool $closedTags
     */
    public function render_association_header($obj, $params, $assoc_name, $closed_tags = false): string
    {
        $end_tag = $closed_tags ? '/>' : '>';
        $more = '';
        if ($this->schema_to_display != 'blank') {
            if (array_key_exists('setter', $params['associations'][$assoc_name]) && !$params['associations'][$assoc_name]['setter']) {
                $more .= ' readOnly="true"';
            }
            $more .= ' nodeType="' . $params['associations'][$assoc_name]['resource'] . '"';
            if (isset($params['associations'][$assoc_name]['virtual_entity']) && $params['associations'][$assoc_name]['virtual_entity']) {
                $more .= ' virtualEntity="true"';
            } else if (isset($params['associations'][$assoc_name]['api'])) {
                $more .= ' api="' . $params['associations'][$assoc_name]['api'] . '"';
            } else {
                $more .= ' api="' . $assoc_name . '"';
            }
        }
        return '<' . $assoc_name . $more . $end_tag . "\n";
    }
    /**
     * @param ObjectModel $obj
     * @param array $params
     * @param string $assocName
     */
    public function render_association_footer($obj, $params, $assoc_name): string
    {
        return '</' . $assoc_name . '>' . "\n";
    }
}