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
 * Class ShopGroupCore
 */
class Shop_Group_Core extends Object_Model
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var bool
     */
    public $active = true;
    /**
     * @var bool
     */
    public $share_customer;
    /**
     * @var bool
     */
    public $share_stock;
    /**
     * @var bool
     */
    public $share_order;
    /**
     * @var bool
     */
    public $deleted;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'shop_group', 'primary' => 'id_shop_group', 'fields' => ['name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64], 'share_customer' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbNullable' => false], 'share_order' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbNullable' => false], 'share_stock' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbNullable' => false], 'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'], 'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0']], 'keys' => ['shop_group' => ['deleted' => ['type' => Object_Model::KEY, 'columns' => ['deleted', 'name']]]]];
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_fields()
    {
        if (!$this->share_customer || !$this->share_stock) {
            $this->share_order = false;
        }
        return parent::get_fields();
    }
    /**
     * @param bool $active
     *
     * @return PrestaShopCollection
     *
     * @throws PrestaShopException
     */
    public static function get_shop_groups($active = true)
    {
        $groups = new Presta_Shop_Collection('ShopGroup');
        $groups->where('deleted', '=', false);
        if ($active) {
            $groups->where('active', '=', true);
        }
        return $groups;
    }
    /**
     * @param bool $active
     *
     * @return int Total of shop groups
     *
     * @throws PrestaShopException
     */
    public static function get_total_shop_group($active = true)
    {
        return count(Shop_Group::get_shop_groups($active));
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function have_shops()
    {
        return (bool) $this->get_total_shops();
    }
    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    public function get_total_shops()
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from('shop', 's')->where('`id_shop_group` = ' . (int) $this->id));
    }
    /**
     * @param int $idGroup
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_shops_from_group($id_group)
    {
        return Db::read_only()->get_array((new Db_Query())->select('s.`id_shop`')->from('shop', 's')->where('`id_shop_group` = ' . (int) $id_group));
    }
    /**
     * Return a group shop ID from group shop name
     *
     * @param string $name
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_name($name)
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('`id_shop_group`')->from('shop_group')->where('`name` = \'' . p_sql($name) . '\''));
    }
    /**
     * Detect dependency with customer or orders
     *
     * @param int $idShopGroup
     * @param string $check all|customer|order
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function has_dependency($id_shop_group, $check = 'all')
    {
        $list_shops = Shop::get_shops(false, $id_shop_group, true);
        if (!$list_shops) {
            return false;
        }
        $connection = Db::read_only();
        if ($check == 'all' || $check == 'customer') {
            $total_customer = (int) $connection->get_value((new Db_Query())->select('COUNT(*)')->from('customer')->where('`id_shop` IN (' . implode(', ', $list_shops) . ')'));
            if ($total_customer) {
                return true;
            }
        }
        if ($check == 'all' || $check == 'order') {
            $total_order = (int) $connection->get_value((new Db_Query())->select('COUNT(*)')->from('orders')->where('`id_shop` IN (' . implode(', ', $list_shops) . ')'));
            if ($total_order) {
                return true;
            }
        }
        return false;
    }
    /**
     * @param string $name
     * @param bool $idShop
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function shop_name_exists($name, $id_shop = false)
    {
        return Db::read_only()->get_value((new Db_Query())->select('`id_shop`')->from('shop')->where('`name` = \'' . p_sql($name) . '\'')->where('`id_shop_group` = ' . (int) $this->id)->where($id_shop ? 'id_shop != ' . (int) $id_shop : ''));
    }
}