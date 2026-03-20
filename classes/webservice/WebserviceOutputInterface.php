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
 * Interface WebserviceOutputInterface
 */
interface Webservice_Output_Interface
{
    /**
     * @param array $languages
     */
    public function __construct($languages = []);
    /**
     * @param string $url
     * @return static
     */
    public function set_ws_url($url);
    /**
     * @return string
     */
    public function get_ws_url();
    /**
     * @return string
     */
    public function get_content_type();
    /**
     * @param string $schema
     * @return static
     */
    public function set_schema_to_display($schema);
    /**
     * @return string
     */
    public function get_schema_to_display();
    /**
     * @param array $field
     * @return string
     */
    public function render_field($field);
    /**
     * @param string $obj
     * @param array $params
     * @param array|null $moreAttr
     * @param bool $hasChild
     * @return string
     */
    public function render_node_header($obj, $params, $more_attr = null, $has_child = true);
    /**
     * @param string $obj
     * @param array $params
     * @return string
     */
    public function render_node_footer($obj, $params);
    /**
     * @param ObjectModel $obj
     * @param array $params
     * @param string $assocName
     * @param bool $closedTags
     * @return string
     */
    public function render_association_header($obj, $params, $assoc_name, $closed_tags = false);
    /**
     * @param ObjectModel $obj
     * @param array $params
     * @param string $assocName
     * @return string
     */
    public function render_association_footer($obj, $params, $assoc_name);
    /**
     * @param string $content
     * @return string
     */
    public function override_content($content);
    /**
     * @return string
     */
    public function render_errors_header();
    /**
     * @return string
     */
    public function render_errors_footer();
    /**
     * @param string $message
     * @param int|null $code
     * @param array $extra
     * @return string
     */
    public function render_errors($message, $code = null, $extra = []);
}