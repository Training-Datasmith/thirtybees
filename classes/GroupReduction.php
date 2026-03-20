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
 * Class GroupReductionCore
 */
class Group_Reduction_Core extends Object_Model
{
    /**
     * @var float[]
     */
    protected static $reduction_cache = [];
    /**
     * @var int
     */
    public $id_group;
    /**
     * @var int
     */
    public $id_category;
    /**
     * @var float
     */
    public $reduction;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'group_reduction', 'primary' => 'id_group_reduction', 'primaryKeyDbType' => 'mediumint(8) unsigned', 'fields' => ['id_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_category' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'reduction' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPercentage', 'required' => true, 'size' => 4, 'decimals' => 3]], 'keys' => ['group_reduction' => ['id_group' => ['type' => Object_Model::UNIQUE_KEY, 'columns' => ['id_group', 'id_category']]]]];
    /**
     * @param int $idGroup
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_group_reductions($id_group, $id_lang)
    {
        return Db::read_only()->get_array((new Db_Query())->select('gr.`id_group_reduction`, gr.`id_group`, gr.`id_category`, gr.`reduction`, cl.`name` AS category_name')->from('group_reduction', 'gr')->left_join('category_lang', 'cl', 'cl.`id_category` = gr.`id_category`')->where('gr.`id_group` = ' . (int) $id_group)->where('cl.`id_lang` = ' . (int) $id_lang));
    }
    /**
     * @param int $idProduct
     * @param int $idGroup
     *
     * @return float|false
     *
     * @throws PrestaShopException
     */
    public static function get_value_for_product($id_product, $id_group)
    {
        if (!Group::is_feature_active()) {
            return false;
        }
        if (!isset(static::$reduction_cache[$id_product . '-' . $id_group])) {
            $value = Db::read_only()->get_value((new Db_Query())->select('`reduction`')->from('product_group_reduction_cache')->where('`id_product` = ' . (int) $id_product)->where('`id_group` = ' . (int) $id_group));
            if ($value !== false) {
                $value = (float) $value;
            }
            static::$reduction_cache[$id_product . '-' . $id_group] = $value;
            return $value;
        }
        return static::$reduction_cache[$id_product . '-' . $id_group];
    }
    /**
     * @param int $idGroup
     * @param int $idCategory
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function does_exist($id_group, $id_category)
    {
        return (bool) Db::read_only()->get_value((new Db_Query())->select('gr.`id_group`')->from('group_reduction', 'gr')->where('gr.`id_group` = ' . (int) $id_group)->where('gr.`id_category` = ' . (int) $id_category));
    }
    /**
     * @deprecated 1.0.0
     *
     * @param int $idCategory
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_group_by_category_id($id_category)
    {
        Tools::display_as_deprecated('Use GroupReduction::getGroupsByCategoryId($id_category)');
        return Db::read_only()->get_row((new Db_Query())->select('gr.`id_group`')->from('group_reduction', 'gr')->where('gr.`id_category` = ' . (int) $id_category));
    }
    /**
     * @param int $idCategory
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_groups_reduction_by_category_id($id_category)
    {
        return Db::read_only()->get_array((new Db_Query())->select('gr.`id_group_reduction` AS `id_group_reduction`, gr.`id_group`')->from('group_reduction', 'gr')->where('`id_category` = ' . (int) $id_category));
    }
    /**
     * @deprecated 1.0.0
     *
     * @param int $idCategory
     *
     * @return array|false
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_group_reduction_by_category_id($id_category)
    {
        Tools::display_as_deprecated('Use GroupReduction::getGroupsByCategoryId($id_category)');
        return Db::read_only()->get_row((new Db_Query())->select('gr.`id_group_reduction`')->from('group_reduction', 'gr')->where('`id_category` = ' . (int) $id_category));
    }
    /**
     * @param int $idProduct
     * @param int|null $idGroup
     * @param int|null $idCategory
     * @param int|null $reduction
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function set_product_reduction($id_product, $id_group = null, $id_category = null, $reduction = null)
    {
        $res = true;
        Group_Reduction::delete_product_reduction((int) $id_product);
        $categories = Product::get_product_categories((int) $id_product);
        if ($categories) {
            foreach ($categories as $category) {
                $reductions = Group_Reduction::get_groups_by_category_id((int) $category);
                if ($reductions) {
                    foreach ($reductions as $reduction) {
                        $current_group_reduction = new Group_Reduction((int) $reduction['id_group_reduction']);
                        $res = $current_group_reduction->_set_cache() && $res;
                    }
                }
            }
        }
        return $res;
    }
    /**
     * @param int $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function delete_product_reduction($id_product)
    {
        return (bool) Db::get_instance()->delete('product_group_reduction_cache', '`id_product` = ' . (int) $id_product);
    }
    /**
     * @param int $idCategory
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_groups_by_category_id($id_category)
    {
        return Db::read_only()->get_array((new Db_Query())->select('gr.`id_group`, gr.`reduction`, gr.`id_group_reduction`')->from('group_reduction', 'gr')->where('`id_category` = ' . (int) $id_category));
    }
    /**
     * @param int $idProductOld
     * @param int $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function duplicate_reduction($id_product_old, $id_product)
    {
        $res = Db::read_only()->get_array((new Db_Query())->select('pgr.`id_product`, pgr.`id_group`, pgr.`reduction`')->from('product_group_reduction_cache', 'pgr')->where('pgr.`id_product` = ' . (int) $id_product_old));
        if (!$res) {
            return true;
        }
        $insert = [];
        foreach ($res as $row) {
            $insert[] = ['id_product' => (int) $id_product, 'id_group' => (int) $row['id_group'], 'reduction' => (float) $row['reduction']];
        }
        if (empty($insert)) {
            return true;
        }
        return Db::get_instance()->insert('product_group_reduction_cache', $insert, false, true, Db::ON_DUPLICATE_KEY);
    }
    /**
     * @param int $idCategory
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function delete_category($id_category)
    {
        return (bool) Db::get_instance()->delete('group_reduction', '`id_category` = ' . (int) $id_category);
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        return parent::add($auto_date, $null_values) && $this->_set_cache();
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function _set_cache()
    {
        $products = Db::read_only()->get_array((new Db_Query())->select('cp.`id_product`')->from('category_product', 'cp')->where('cp.`id_category` = ' . (int) $this->id_category));
        $values = [];
        foreach ($products as $row) {
            $values[] = '(' . (int) $row['id_product'] . ', ' . (int) $this->id_group . ', ' . (float) $this->reduction . ')';
        }
        if (count($values)) {
            $query = 'INSERT INTO `' . _DB_PREFIX_ . 'product_group_reduction_cache` (`id_product`, `id_group`, `reduction`)
			VALUES ' . implode(', ', $values) . ' ON DUPLICATE KEY UPDATE
			`reduction` = IF(VALUES(`reduction`) > `reduction`, VALUES(`reduction`), `reduction`)';
            return Db::get_instance()->execute($query);
        }
        return true;
    }
    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        return parent::update($null_values) && $this->_update_cache();
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function _update_cache()
    {
        $products = Db::read_only()->get_array((new Db_Query())->select('cp.`id_product`')->from('category_product', 'cp')->where('cp.`id_category` = ' . (int) $this->id_category));
        $ids = [];
        foreach ($products as $product) {
            $ids[] = $product['id_product'];
        }
        if ($ids) {
            return Db::get_instance()->update('product_group_reduction_cache', ['reduction' => (float) $this->reduction], '`id_product` IN(' . implode(', ', $ids) . ') AND `id_group` = ' . (int) $this->id_group);
        }
        return true;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        $products = Db::read_only()->get_array((new Db_Query())->select('cp.`id_product`')->from('category_product', 'cp')->where('cp.`id_category` = ' . (int) $this->id_category));
        $ids = [];
        foreach ($products as $row) {
            $ids[] = $row['id_product'];
        }
        if ($ids) {
            Db::get_instance()->delete('product_group_reduction_cache', '`id_product` IN (' . implode(', ', $ids) . ')');
        }
        return parent::delete();
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function _clear_cache()
    {
        return Db::get_instance()->delete('product_group_reduction_cache', '`id_group` = ' . (int) $this->id_group);
    }
}