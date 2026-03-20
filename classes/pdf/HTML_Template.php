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
 * Class HTMLTemplateCore
 */
abstract class Html_Template_Core
{
    /** @var string $title */
    public $title;
    /** @var string $date */
    public $date;
    /** @var bool $available_in_your_account */
    public $available_in_your_account = true;
    /** @var Smarty */
    public $smarty;
    /** @var Shop */
    public $shop;
    /**
     * Returns the template's HTML header
     *
     * @return string HTML header
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function get_header()
    {
        $this->assign_common_header_data();
        return $this->smarty->fetch($this->get_template('header'));
    }
    /**
     * Returns the template's HTML footer
     *
     * @return string HTML footer
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function get_footer()
    {
        $shop_address = $this->get_shop_address();
        $id_shop = (int) $this->shop->id;
        $this->smarty->assign(['available_in_your_account' => $this->available_in_your_account, 'shop_address' => $shop_address, 'shop_fax' => Configuration::get('PS_SHOP_FAX', null, null, $id_shop), 'shop_phone' => Configuration::get('PS_SHOP_PHONE', null, null, $id_shop), 'shop_email' => Configuration::get('PS_SHOP_EMAIL', null, null, $id_shop), 'free_text' => Configuration::get('PS_INVOICE_FREE_TEXT', (int) Context::get_context()->language->id, null, $id_shop)]);
        return $this->smarty->fetch($this->get_template('footer'));
    }
    /**
     * Returns the shop address
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected function get_shop_address()
    {
        return Address_Format::generate_address($this->shop->get_address(), [], ' - ', ' ');
    }
    /**
     * Returns the invoice logo
     *
     * @throws PrestaShopException
     */
    protected function get_logo()
    {
        $logo = '';
        $id_shop = (int) $this->shop->id;
        if (Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop) != false && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop))) {
            $logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE', null, null, $id_shop);
        } elseif (Configuration::get('PS_LOGO', null, null, $id_shop) != false && file_exists(_PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $id_shop))) {
            $logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, $id_shop);
        }
        return $logo;
    }
    /**
     * Assign common header data to smarty variables
     *
     * @throws PrestaShopException
     */
    public function assign_common_header_data(): void
    {
        $this->set_shop_id();
        $id_shop = (int) $this->shop->id;
        $shop_name = Configuration::get('PS_SHOP_NAME', null, null, $id_shop);
        $path_logo = $this->get_logo();
        $width = 0;
        $height = 0;
        if (!empty($path_logo)) {
            [$width, $height] = getimagesize($path_logo);
        }
        // Limit the height of the logo for the PDF render
        $maximum_height = 100;
        if ($height > $maximum_height) {
            $ratio = $maximum_height / $height;
            $height *= $ratio;
            $width *= $ratio;
        }
        $this->smarty->assign(['logo_path' => $path_logo, 'img_ps_dir' => Tools::get_shop_protocol() . Tools::get_media_server(_PS_IMG_) . _PS_IMG_, 'img_update_time' => Configuration::get('PS_IMG_UPDATE_TIME'), 'date' => $this->date, 'title' => $this->title, 'shop_name' => $shop_name, 'shop_details' => Configuration::get('PS_SHOP_DETAILS', null, null, $id_shop), 'width_logo' => $width, 'height_logo' => $height]);
    }
    /**
     * Assign hook data
     *
     * @param ObjectModel $object generally the object used in the constructor
     * @throws PrestaShopException
     */
    public function assign_hook_data($object): void
    {
        $template = ucfirst(str_replace('HTMLTemplate', '', static::class));
        $hook_name = 'displayPDF' . $template;
        $this->smarty->assign(['HOOK_DISPLAY_PDF' => Hook::display_hook($hook_name, ['object' => $object])]);
    }
    /**
     * Returns the template's HTML content
     *
     * @return string HTML content
     */
    abstract public function get_content();
    /**
     * Returns the template filename
     *
     * @return string filename
     */
    abstract public function get_filename();
    /**
     * Returns the template filename when using bulk rendering
     *
     * @return string filename
     */
    abstract public function get_bulk_filename();
    /**
     * If the template is not present in the theme directory, it will return the default template
     * in _PS_PDF_DIR_ directory
     *
     *
     * @return string
     */
    protected function get_template(string $template_name)
    {
        $template = false;
        $default_template = rtrim(_PS_PDF_DIR_, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $template_name . '.tpl';
        $overridden_template = _PS_ALL_THEMES_DIR_ . $this->shop->get_theme() . DIRECTORY_SEPARATOR . 'pdf' . DIRECTORY_SEPARATOR . $template_name . '.tpl';
        if (file_exists($overridden_template)) {
            $template = $overridden_template;
        } elseif (file_exists($default_template)) {
            $template = $default_template;
        }
        return $template;
    }
    /**
     * Translation method
     *
     * @param string $string
     *
     * @return string translated text
     */
    protected static function l($string)
    {
        return Translate::get_pdf_translation($string);
    }
    /**
     * @throws PrestaShopException
     */
    protected function set_shop_id()
    {
        if (isset($this->order) && Validate::is_loaded_object($this->order)) {
            $id_shop = (int) $this->order->id_shop;
        } else {
            $id_shop = (int) Context::get_context()->shop->id;
        }
        $this->shop = new Shop($id_shop);
        if (Validate::is_loaded_object($this->shop)) {
            Shop::set_context(Shop::CONTEXT_SHOP, (int) $this->shop->id);
        }
    }
    /**
     * Returns the template's HTML pagination block
     *
     * @return string HTML pagination block
     *
     * @throws SmartyException
     */
    public function get_pagination()
    {
        return $this->smarty->fetch($this->get_template('pagination'));
    }
}