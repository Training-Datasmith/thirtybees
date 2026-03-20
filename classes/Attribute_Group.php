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
 * Class AttributeGroupCore
 */
class Attribute_Group_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'attribute_group', 'primary' => 'id_attribute_group', 'multilang' => true, 'fields' => [
        'is_color_group' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'group_type' => ['type' => self::TYPE_STRING, 'required' => true, 'values' => ['select', 'radio', 'color'], 'dbDefault' => 'select'],
        'position' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbDefault' => '0'],
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
        'public_name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
    ], 'keys' => ['attribute_group_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]]];
    /** @var string|string[] Name */
    public $name;
    /** @var bool $is_color_group */
    public $is_color_group;
    /** @var int $position */
    public $position;
    /** @var string $group_type */
    public $group_type;
    /** @var string|string[] Public Name */
    public $public_name;
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectsNodeName' => 'product_options', 'objectNodeName' => 'product_option', 'fields' => [], 'associations' => ['product_option_values' => ['resource' => 'product_option_value', 'fields' => ['id' => []]]]];
    /**
     * Get all attributes for a given language / group
     *
     * @param int $idLang Language id
     * @param bool $idAttributeGroup Attribute group id
     *
     * @return array Attributes
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_attributes($id_lang, $id_attribute_group)
    {
        if (!Combination::is_feature_active()) {
            return [];
        }
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('attribute', 'a')->join(Shop::add_sql_association('attribute', 'a'))->left_join('attribute_lang', 'al', 'a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $id_lang)->where('a.`id_attribute_group` = ' . (int) $id_attribute_group)->order_by('`position` ASC'));
    }
    /**
     * Get all attributes groups for a given language
     *
     * @param int $idLang Language id
     *
     * @return array Attributes groups
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_attributes_groups($id_lang)
    {
        if (!Combination::is_feature_active()) {
            return [];
        }
        return Db::read_only()->get_array((new Db_Query())->select('DISTINCT agl.`name`, ag.*, agl.*')->from('attribute_group', 'ag')->join(Shop::add_sql_association('attribute_group', 'ag'))->left_join('attribute_group_lang', 'agl', 'ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $id_lang)->order_by('agl.`name` ASC'));
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
        if ($this->group_type == 'color') {
            $this->is_color_group = 1;
        } else {
            $this->is_color_group = 0;
        }
        if ($this->position <= 0) {
            $this->position = Attribute_Group::get_higher_position() + 1;
        }
        $return = parent::add($auto_date, true);
        Hook::trigger_event('actionAttributeGroupSave', ['id_attribute_group' => $this->id]);
        return $return;
    }
    /**
     * getHigherPosition
     *
     * Get the higher group attribute position
     *
     * @return int $position
     * @throws PrestaShopException
     */
    public static function get_higher_position()
    {
        $position = (int) Db::read_only()->get_value((new Db_Query())->select('MAX(`position`)')->from('attribute_group'));
        if (!$position) {
            return -1;
        }
        return $position;
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
        if ($this->group_type == 'color') {
            $this->is_color_group = 1;
        } else {
            $this->is_color_group = 0;
        }
        $return = parent::update($null_values);
        Hook::trigger_event('actionAttributeGroupSave', ['id_attribute_group' => $this->id]);
        return $return;
    }
    /**
     * Delete several objects from database
     *
     * return boolean Deletion result
     *
     * @param array $selection
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_selection($selection)
    {
        /* Also delete Attributes */
        foreach ($selection as $value) {
            $obj = new Attribute_Group($value);
            if (!$obj->delete()) {
                return false;
            }
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
        if (!$this->has_multishop_entries() || Shop::get_context() == Shop::CONTEXT_ALL) {
            /* Select children in order to find linked combinations */
            $attribute_ids = Db::read_only()->get_array((new Db_Query())->select('`id_attribute`')->from('attribute')->where('`id_attribute_group` = ' . (int) $this->id));
            /* Removing attributes to the found combinations */
            $to_remove = [];
            foreach ($attribute_ids as $attribute) {
                $to_remove[] = (int) $attribute['id_attribute'];
            }
            $conn = Db::get_instance();
            if (!empty($to_remove) && $conn->delete('product_attribute_combination', '`id_attribute` IN (' . implode(',', $to_remove) . ')') === false) {
                return false;
            }
            /* Remove combinations if they do not possess attributes anymore */
            if (!Attribute_Group::clean_dead_combinations()) {
                return false;
            }
            /* Also delete related attributes */
            if (count($to_remove)) {
                if (!$conn->delete('attribute_lang', '`id_attribute` IN (' . implode(',', $to_remove) . ')') || !$conn->delete('attribute_shop', '`id_attribute` IN (' . implode(',', $to_remove) . ')') || !$conn->delete('attribute', '`id_attribute_group` = ' . (int) $this->id)) {
                    return false;
                }
            }
            static::clean_positions();
        }
        $return = parent::delete();
        if ($return) {
            Hook::trigger_event('actionAttributeGroupDelete', ['id_attribute_group' => $this->id]);
        }
        return $return;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function clean_dead_combinations()
    {
        $attribute_combinations = Db::read_only()->get_array((new Db_Query())->select('pac.`id_attribute`, pa.`id_product_attribute`')->from('product_attribute', 'pa')->left_join('product_attribute_combination', 'pac', 'pa.`id_product_attribute` = pac.`id_product_attribute`'));
        $to_remove = [];
        foreach ($attribute_combinations as $attribute_combination) {
            if ((int) $attribute_combination['id_attribute'] == 0) {
                $to_remove[] = (int) $attribute_combination['id_product_attribute'];
            }
        }
        $return = true;
        foreach ($to_remove as $remove) {
            $combination = new Combination($remove);
            $return = $combination->delete() && $return;
        }
        return $return;
    }
    /**
     * Reorder group attribute position
     * Call it after deleting a group attribute.
     *
     * @return bool $return
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function clean_positions()
    {
        $return = true;
        $result = Db::read_only()->get_array((new Db_Query())->select('`id_attribute_group`')->from('attribute_group')->order_by('`position`'));
        $i = 0;
        foreach ($result as $value) {
            $return = Db::get_instance()->update('attribute_group', ['position' => $i++], '`id_attribute_group` = ' . (int) $value['id_attribute_group']);
        }
        return $return;
    }
    /**
     * @param array $values
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_ws_product_option_values($values)
    {
        $ids = [];
        foreach ($values as $value) {
            $ids[] = intval($value['id']);
        }
        $conn = Db::get_instance();
        $conn->delete('attribute', '`id_attribute_group` = ' . (int) $this->id . ' AND `id_attribute` NOT IN (' . implode(',', $ids) . ')');
        $ok = true;
        foreach ($values as $value) {
            $result = $conn->update('attribute', ['id_attribute_group' => (int) $this->id], '`id_attribute` = ' . (int) $value['id']);
            if ($result === false) {
                $ok = false;
            }
        }
        return $ok;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_product_option_values()
    {
        return Db::read_only()->get_array((new Db_Query())->select('a.`id_attribute` AS `id`')->from('attribute', 'a')->join(Shop::add_sql_association('attribute', 'a'))->where('a.`id_attribute_group` = ' . (int) $this->id));
    }
    /**
     * Move a group attribute
     *
     * @param bool $way Up (1) or Down (0)
     * @param int $position
     *
     * @return bool Update result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_position($way, $position)
    {
        if (!$res = Db::read_only()->get_array((new Db_Query())->select('ag.`position`, ag.`id_attribute_group`')->from('attribute_group', 'ag')->where('ag.`id_attribute_group` = ' . Tools::get_int_value('id_attribute_group', 1))->order_by('ag.`position` ASC'))) {
            return false;
        }
        foreach ($res as $group_attribute) {
            if ((int) $group_attribute['id_attribute_group'] == (int) $this->id) {
                $moved_group_attribute = $group_attribute;
            }
        }
        if (!isset($moved_group_attribute) || !isset($position)) {
            return false;
        }
        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $conn = Db::get_instance();
        return $conn->update('attribute_group', ['position' => ['type' => 'sql', 'value' => '`position` ' . ($way ? '- 1' : '+ 1')]], '`position` ' . ($way ? '> ' . (int) $moved_group_attribute['position'] . ' AND `position` <= ' . (int) $position : '< ' . (int) $moved_group_attribute['position'] . ' AND `position` >= ' . (int) $position)) && $conn->update('attribute_group', ['position' => (int) $position], '`id_attribute_group` = ' . (int) $moved_group_attribute['id_attribute_group']);
    }
}