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
 * Class CompareProductCore
 */
class Compare_Product_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'compare', 'primary' => 'id_compare', 'fields' => ['id_compare' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true], 'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true]], 'keys' => ['compare_product' => ['primary' => ['type' => Object_Model::PRIMARY_KEY, 'columns' => ['id_compare', 'id_product']]]]];
    /** @var int $id_compare */
    public $id_compare;
    /** @var int $id_customer */
    public $id_customer;
    /** @var string $date_add */
    public $date_add;
    /** @var string $date_upd */
    public $date_upd;
    /**
     * Get all compare products of the customer
     *
     * @param int $idCompare
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_compare_products($id_compare)
    {
        $results = Db::read_only()->get_array((new Db_Query())->select('DISTINCT `id_product`')->from('compare', 'c')->left_join('compare_product', 'cp', 'cp.`id_compare` = c.`id_compare`')->where('cp.`id_compare` = ' . (int) $id_compare));
        $compare_products = [];
        if ($results) {
            foreach ($results as $result) {
                $compare_products[] = (int) $result['id_product'];
            }
        }
        return $compare_products;
    }
    /**
     * Add a compare product for the customer
     *
     * @param int $idCompare
     * @param int $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function add_compare_product($id_compare, $id_product)
    {
        $id_compare = (int) $id_compare;
        // Check if compare row exists
        if ($id_compare) {
            $id_compare = (int) Db::read_only()->get_value((new Db_Query())->select('`id_compare`')->from('compare')->where('`id_compare` = ' . (int) $id_compare));
        }
        // Create new compare record if it does not exists yet
        $conn = Db::get_instance();
        if (!$id_compare) {
            $context = Context::get_context();
            $customer = $context->customer;
            $id_customer = Validate::is_loaded_object($customer) ? (int) $customer->id : 0;
            if (!$conn->insert('compare', ['id_customer' => $id_customer])) {
                return false;
            }
            $id_compare = (int) $conn->Insert_ID();
            $context->cookie->id_compare = $id_compare;
        }
        if ($id_compare && $id_product) {
            return $conn->insert('compare_product', ['id_compare' => (int) $id_compare, 'id_product' => (int) $id_product, 'date_add' => ['type' => 'sql', 'value' => 'NOW()'], 'date_upd' => ['type' => 'sql', 'value' => 'NOW()']], false, true, Db::INSERT_IGNORE);
        }
        return false;
    }
    /**
     * Remove a compare product for the customer
     *
     * @param int $idCompare
     * @param int $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function remove_compare_product($id_compare, $id_product)
    {
        return Db::get_instance()->execute('
            DELETE cp FROM `' . _DB_PREFIX_ . 'compare_product` cp, `' . _DB_PREFIX_ . 'compare` c
            WHERE cp.`id_compare`=c.`id_compare`
            AND cp.`id_product` = ' . (int) $id_product . '
            AND c.`id_compare` = ' . (int) $id_compare);
    }
    /**
     * Get the number of compare products of the customer
     *
     * @param int $idCompare
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_number_products($id_compare)
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('COUNT(`id_compare`)')->from('compare_product')->where('`id_compare` = ' . (int) $id_compare));
    }
    /**
     * Clean entries which are older than the period
     *
     * @param string $period
     *
     *
     * @throws PrestaShopException
     */
    public static function clean_compare_products($period = null): void
    {
        if ($period !== null) {
            Tools::display_parameter_as_deprecated('period');
        }
        Db::get_instance()->execute('
        DELETE cp, c FROM `' . _DB_PREFIX_ . 'compare_product` cp, `' . _DB_PREFIX_ . 'compare` c
        WHERE cp.date_upd < DATE_SUB(NOW(), INTERVAL 1 WEEK) AND c.`id_compare`=cp.`id_compare`');
    }
    /**
     * Get the id_compare by id_customer
     *
     * @param int $idCustomer
     *
     * @return int $id_compare
     *
     * @throws PrestaShopException
     */
    public static function get_id_compare_by_id_customer($id_customer)
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('`id_compare`')->from('compare')->where('`id_customer` = ' . (int) $id_customer));
    }
}