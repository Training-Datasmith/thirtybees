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
 * Class PageCore
 */
class Page_Core extends Object_Model
{
    /**
     * @var int
     */
    public $id_page_type;
    /**
     * @var int
     */
    public $id_object;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'page', 'primary' => 'id_page', 'fields' => ['id_page_type' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_object' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId']], 'keys' => ['page' => ['id_object' => ['type' => Object_Model::KEY, 'columns' => ['id_object']], 'id_page_type' => ['type' => Object_Model::KEY, 'columns' => ['id_page_type']]]]];
    /**
     * @return int Current page ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_current_id()
    {
        $controller = Dispatcher::get_instance()->get_controller();
        $page_type_id = Page::get_page_type_by_name($controller);
        // Some pages must be distinguished in order to record exactly what is being seen
        // @todo dispatcher module
        $special_array = ['product' => 'id_product', 'category' => 'id_category', 'order' => 'step', 'manufacturer' => 'id_manufacturer'];
        $where = '';
        $insert_data = ['id_page_type' => $page_type_id];
        if (array_key_exists($controller, $special_array)) {
            $object_id = Tools::get_value($special_array[$controller], null);
            $where = ' AND `id_object` = ' . (int) $object_id;
            $insert_data['id_object'] = (int) $object_id;
        }
        $result = Db::read_only()->get_row((new Db_Query())->select('`id_page`')->from('page')->where('`id_page_type` = ' . (int) $page_type_id . $where));
        if ($result && $result['id_page']) {
            return (int) $result['id_page'];
        }
        $conn = Db::get_instance();
        $conn->insert('page', $insert_data, true);
        return $conn->Insert_ID();
    }
    /**
     * Return page type ID from page name
     *
     * @param string $name Page name (E.g. product.php)
     *
     * @return false|int|null|string
     * @throws PrestaShopException
     */
    public static function get_page_type_by_name($name)
    {
        if ($value = Db::read_only()->get_value((new Db_Query())->select('`id_page_type`')->from('page_type')->where('`name` = \'' . p_sql($name) . '\''))) {
            return $value;
        }
        $conn = Db::get_instance();
        $conn->insert('page_type', ['name' => p_sql($name)]);
        return $conn->Insert_ID();
    }
    /**
     * @param int $idPage
     *
     * @throws PrestaShopException
     */
    public static function set_page_viewed($id_page): void
    {
        $id_date_range = Date_Range::get_current_range();
        $context = Context::get_context();
        // Try to increment the visits counter
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'page_viewed`
				SET `counter` = `counter` + 1
				WHERE `id_date_range` = ' . (int) $id_date_range . '
					AND `id_page` = ' . (int) $id_page . '
					AND `id_shop` = ' . (int) $context->shop->id;
        $conn = Db::get_instance();
        $conn->execute($sql);
        // If no one has seen the page in this date range, it is added
        if ($conn->Affected_Rows() == 0) {
            $conn->insert('page_viewed', ['id_date_range' => (int) $id_date_range, 'id_page' => (int) $id_page, 'counter' => 1, 'id_shop' => (int) $context->shop->id, 'id_shop_group' => (int) $context->shop->id_shop_group], false, true, Db::INSERT_IGNORE);
        }
    }
}