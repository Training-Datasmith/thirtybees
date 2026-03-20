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
 * Class TreeToolbarLinkCore
 */
class Tree_Toolbar_Link_Core extends Tree_Toolbar_Button_Core implements I_Tree_Toolbar_Button_Core
{
    /**
     * @var string
     */
    protected $_template = 'tree_toolbar_link.tpl';
    /**
     * TreeToolbarLinkCore constructor.
     *
     * @param string $label
     * @param string $link
     * @param string|null $action
     * @param string|null $iconClass
     */
    public function __construct($label, $link, $action = null, $icon_class = null)
    {
        parent::__construct($label);
        $this->set_link($link);
        $this->set_action($action);
        $this->set_icon_class($icon_class);
    }
    /**
     * @param string $value
     *
     * @return static
     */
    public function set_action($value)
    {
        return $this->set_attribute('action', $value);
    }
    /**
     * @return string|null
     */
    public function get_action()
    {
        return $this->get_attribute('action');
    }
    /**
     * @param string $value
     *
     * @return static
     */
    public function set_icon_class($value)
    {
        return $this->set_attribute('icon_class', $value);
    }
    /**
     * @return string|null
     */
    public function get_icon_class()
    {
        return $this->get_attribute('icon_class');
    }
    /**
     * @param string $value
     *
     * @return static
     */
    public function set_link($value)
    {
        return $this->set_attribute('link', $value);
    }
    /**
     * @return string|null
     */
    public function get_link()
    {
        return $this->get_attribute('link');
    }
}