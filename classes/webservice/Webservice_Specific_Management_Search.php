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
 * Class WebserviceSpecificManagementSearchCore
 */
class Webservice_Specific_Management_Search_Core implements Webservice_Specific_Management_Interface
{
    /**
     * @var WebserviceOutputBuilder
     */
    protected $obj_output;
    /**
     * @var string
     */
    protected $output;
    /**
     * @var WebserviceRequest
     */
    protected $ws_object;
    /* ------------------------------------------------
     * GETTERS & SETTERS
     * ------------------------------------------------ */
    public function set_object_output(Webservice_Output_Builder_Core $obj): static
    {
        $this->obj_output = $obj;
        return $this;
    }
    public function set_ws_object(Webservice_Request_Core $obj): static
    {
        $this->ws_object = $obj;
        return $this;
    }
    /**
     * @return WebserviceRequest
     */
    public function get_ws_object()
    {
        return $this->ws_object;
    }
    /**
     * @return WebserviceOutputBuilder
     */
    public function get_object_output()
    {
        return $this->obj_output;
    }
    /**
     * WebserviceRequestCore
     *
     * @throws WebserviceException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function manage()
    {
        if (!isset($this->ws_object->url_fragments['query']) || !isset($this->ws_object->url_fragments['language'])) {
            throw new Webservice_Exception('You have to set both the \'language\' and \'query\' parameters to get a result', [100, 400]);
        }
        $objects_products = [];
        $objects_categories = [];
        $objects_products['empty'] = new Product();
        $objects_categories['empty'] = new Category();
        if (!$this->ws_object->set_fields_to_display()) {
            return false;
        }
        $results = Search::find($this->ws_object->url_fragments['language'], $this->ws_object->url_fragments['query'], 1, 1, 'position', 'desc', true, false);
        $categories = [];
        foreach ($results as $result) {
            $current = new Product($result['id_product']);
            $objects_products[] = $current;
            $categories_result = $current->get_ws_categories();
            foreach ($categories_result as $category_result) {
                foreach ($category_result as $id) {
                    $categories[] = $id;
                }
            }
        }
        $categories = array_unique($categories);
        foreach ($categories as $id) {
            $objects_categories[] = new Category($id);
        }
        $this->output .= $this->obj_output->get_content($objects_products, null, $this->ws_object->fields_to_display, $this->ws_object->depth, Webservice_Output_Builder::VIEW_LIST, false);
        $this->output .= $this->obj_output->get_content($objects_categories, null, $this->ws_object->fields_to_display, $this->ws_object->depth, Webservice_Output_Builder::VIEW_LIST, false);
    }
    /**
     * This must be return a string with specific values as WebserviceRequest expects.
     *
     * @return string
     */
    public function get_content()
    {
        return $this->obj_output->get_object_render()->override_content($this->output);
    }
}