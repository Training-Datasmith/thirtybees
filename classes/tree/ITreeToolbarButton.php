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
 * Interface ITreeToolbarButtonCore
 */
interface I_Tree_Toolbar_Button_Core
{
    /**
     * @return string
     */
    public function __toString();
    /**
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function set_attribute($name, $value);
    /**
     * @param string $name
     * @return mixed
     */
    public function get_attribute($name);
    /**
     * @param array $value
     * @return mixed
     */
    public function set_attributes($value);
    /**
     * @return array
     */
    public function get_attributes();
    /**
     * @param string $value
     * @return static
     */
    public function set_class($value);
    /**
     * @return string
     */
    public function get_class();
    /**
     * @param string $value
     * @return static
     */
    public function set_context($value);
    /**
     * @return string
     */
    public function get_context();
    /**
     * @param string $value
     * @return static
     */
    public function set_id($value);
    /**
     * @return string
     */
    public function get_id();
    /**
     * @param string $value
     * @return static
     */
    public function set_label($value);
    /**
     * @return string
     */
    public function get_label();
    /**
     * @param string $value
     * @return static
     */
    public function set_name($value);
    /**
     * @return string
     */
    public function get_name();
    /**
     * @param string $value
     * @return static
     */
    public function set_template($value);
    /**
     * @return string
     */
    public function get_template();
    /**
     * @param string $value
     * @return static
     */
    public function set_template_directory($value);
    /**
     * @return string
     */
    public function get_template_directory();
    /**
     * @param string $name
     * @return bool
     */
    public function has_attribute($name);
    /**
     * @return string
     */
    public function render();
}