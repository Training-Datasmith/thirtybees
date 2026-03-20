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
 * Class PDFCore
 */
class Pdf_Core
{
    /** @var string $filename */
    public $filename;
    /** @var PDFGenerator */
    public $pdf_renderer;
    /** @var bool */
    public $send_bulk_flag = false;
    /** @var Smarty */
    public $smarty;
    public const TEMPLATE_INVOICE = 'Invoice';
    public const TEMPLATE_ORDER_RETURN = 'OrderReturn';
    public const TEMPLATE_ORDER_SLIP = 'OrderSlip';
    public const TEMPLATE_DELIVERY_SLIP = 'DeliverySlip';
    public const TEMPLATE_SUPPLY_ORDER_FORM = 'SupplyOrderForm';
    /**
     * @param ObjectModel[]|Iterator|ObjectModel $objects
     * @param string $template
     * @param Smarty $smarty
     * @param string $orientation
     */
    public function __construct(public $objects, public $template, $smarty, $orientation = 'P')
    {
        $this->pdf_renderer = new Pdf_Generator(false, $orientation);
        $this->smarty = $smarty;
        if (!$this->objects instanceof Iterator && !is_array($this->objects)) {
            $this->objects = [$this->objects];
        }
        if (count($this->objects) > 1) {
            // when bulk mode only
            $this->send_bulk_flag = true;
        }
    }
    /**
     * Render PDF
     *
     * @param bool $display
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function render($display = true)
    {
        $render = false;
        $this->pdf_renderer->set_font_for_lang(Context::get_context()->language->iso_code);
        foreach ($this->objects as $object) {
            $this->pdf_renderer->start_page_group();
            $template = $this->get_template_object($object);
            if (empty($this->filename)) {
                $this->filename = $template->get_filename();
                if (count($this->objects) > 1) {
                    $this->filename = $template->get_bulk_filename();
                }
            }
            $template->assign_hook_data($object);
            $this->pdf_renderer->create_header($template->get_header());
            $this->pdf_renderer->create_footer($template->get_footer());
            $this->pdf_renderer->create_pagination($template->get_pagination());
            $this->pdf_renderer->create_content($template->get_content());
            $this->pdf_renderer->write_page();
            $render = true;
            unset($template);
        }
        if ($render) {
            // clean the output buffer
            if (ob_get_level() && ob_get_length() > 0) {
                ob_clean();
            }
            return $this->pdf_renderer->render($this->filename, $display);
        }
        return '';
    }
    /**
     * Get correct PDF template classes
     *
     * @param OrderInvoice|OrderReturn|OrderSlip|SupplyOrder $object
     *
     * @return HTMLTemplate
     * @throws PrestaShopException
     */
    public function get_template_object($object): \Html_Template_Invoice|\Html_Template_Order_Return|\Html_Template_Order_Slip|\Html_Template_Delivery_Slip|\Html_Template_Supply_Order_Form|\Html_Template
    {
        switch ($this->template) {
            case static::TEMPLATE_INVOICE:
                return new Html_Template_Invoice($object, $this->smarty, $this->send_bulk_flag);
            case static::TEMPLATE_ORDER_RETURN:
                return new Html_Template_Order_Return($object, $this->smarty);
            case static::TEMPLATE_ORDER_SLIP:
                return new Html_Template_Order_Slip($object, $this->smarty);
            case static::TEMPLATE_DELIVERY_SLIP:
                return new Html_Template_Delivery_Slip($object, $this->smarty, $this->send_bulk_flag);
            case static::TEMPLATE_SUPPLY_ORDER_FORM:
                return new Html_Template_Supply_Order_Form($object, $this->smarty);
            default:
                $class_name = 'HTMLTemplate' . $this->template;
                if (class_exists($class_name)) {
                    $instance = new $class_name($object, $this->smarty, $this->send_bulk_flag);
                    if ($instance instanceof Html_Template) {
                        return $instance;
                    }
                }
                throw new Presta_Shop_Exception('Unknown template: ' . $this->template);
        }
    }
}