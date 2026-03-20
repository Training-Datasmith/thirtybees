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
 * Class ProductAttributeCore
 */
class Product_Attribute_Core extends Object_Model
{
    /** @var int Group id which attribute belongs */
    public $id_attribute_group;
    /** @var string|string[] Name */
    public $name;
    /** @var string $color */
    public $color;
    /** @var int $position */
    public $position;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'attribute', 'primary' => 'id_attribute', 'multilang' => true, 'fields' => [
        'id_attribute_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
        'color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 32],
        'position' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbDefault' => '0'],
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
    ], 'keys' => ['attribute' => ['attribute_group' => ['type' => Object_Model::KEY, 'columns' => ['id_attribute_group']]], 'attribute_lang' => ['id_lang' => ['type' => Object_Model::KEY, 'columns' => ['id_lang', 'name']]], 'attribute_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]]];
    /** @var string Path to image directory. Used for image deletion. */
    protected $image_dir = _PS_COL_IMG_DIR_;
    /** @var array WebService parameters */
    protected $webservice_parameters = ['objectsNodeName' => 'product_option_values', 'objectNodeName' => 'product_option_value', 'fields' => ['id_attribute_group' => ['xlink_resource' => 'product_options']]];
    /**
     * ProductAttributeCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        $this->image_dir = _PS_COL_IMG_DIR_;
        parent::__construct($id, $id_lang, $id_shop);
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
            $conn = Db::read_only();
            $result = $conn->get_array((new Db_Query())->select('`id_product_attribute`')->from('product_attribute_combination')->where('`id_attribute` = ' . (int) $this->id));
            $products = [];
            foreach ($result as $row) {
                $combination = new Combination($row['id_product_attribute']);
                $new_request = $conn->get_array((new Db_Query())->select('`id_product`, `default_on`')->from('product_attribute')->where('`id_product_attribute` = ' . (int) $row['id_product_attribute']));
                foreach ($new_request as $value) {
                    if ($value['default_on'] == 1) {
                        $products[] = $value['id_product'];
                    }
                }
                $combination->delete();
            }
            foreach ($products as $product) {
                $id_product_attribute = (int) $conn->get_value((new Db_Query())->select('`id_product_attribute`')->from('product_attribute')->where('`id_product` = ' . (int) $product));
                if (Validate::is_loaded_object($product = new Product((int) $product))) {
                    $product->delete_default_attributes();
                    $product->set_default_attribute($id_product_attribute);
                }
            }
            // Delete associated restrictions on cart rules
            Cart_Rule::clean_product_rule_integrity('attributes', $this->id);
            /* Reinitializing position */
            $this->clean_positions((int) $this->id_attribute_group);
        }
        $return = parent::delete();
        if ($return) {
            Hook::trigger_event('actionAttributeDelete', ['id_attribute' => $this->id]);
        }
        return $return;
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
        $return = parent::update($null_values);
        if ($return) {
            Hook::trigger_event('actionAttributeSave', ['id_attribute' => $this->id]);
        }
        return $return;
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
        if ($this->position <= 0) {
            $this->position = static::get_higher_position($this->id_attribute_group) + 1;
        }
        $return = parent::add($auto_date, $null_values);
        if ($return) {
            Hook::trigger_event('actionAttributeSave', ['id_attribute' => $this->id]);
        }
        return $return;
    }
    /**
     * Get all attributes for a given language
     *
     * @param int $idLang Language id
     * @param bool $notNull Get only not null fields if true
     *
     * @return array Attributes
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_attributes($id_lang, $not_null = false)
    {
        if (!Combination::is_feature_active()) {
            return [];
        }
        return Db::read_only()->get_array((new Db_Query())->select('DISTINCT ag.*, agl.*, a.`id_attribute`, al.`name`, agl.`name` AS `attribute_group`')->from('attribute_group', 'ag')->left_join('attribute_group_lang', 'agl', 'ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $id_lang)->left_join('attribute', 'a', 'a.`id_attribute_group` = ag.`id_attribute_group`')->left_join('attribute_lang', 'al', 'al.`id_attribute` = a.`id_attribute` AND al.`id_lang` = ' . (int) $id_lang)->join(Shop::add_sql_association('attribute_group', 'ag'))->join(Shop::add_sql_association('attribute', 'a'))->where($not_null ? 'a.`id_attribute` IS NOT NULL AND al.`name` IS NOT NULL AND agl.`id_attribute_group` IS NOT NULL' : '')->order_by('agl.`name` ASC, a.`position` ASC'));
    }
    /**
     * @param int $idAttributeGroup
     * @param string $name
     * @param int $idLang
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_attribute($id_attribute_group, $name, $id_lang)
    {
        if (!Combination::is_feature_active()) {
            return false;
        }
        $result = Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from('attribute_group', 'ag')->left_join('attribute_group_lang', 'agl', 'ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $id_lang)->left_join('attribute', 'a', 'a.`id_attribute_group` = ag.`id_attribute_group`')->left_join('attribute_lang', 'al', 'a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $id_lang)->join(Shop::add_sql_association('attribute_group', 'ag'))->join(Shop::add_sql_association('attribute', 'a'))->where('al.`name` = \'' . p_sql($name) . '\'')->where('ag.`id_attribute_group` = ' . (int) $id_attribute_group)->order_by('agl.`name` ASC, a.`position` ASC'));
        return (int) $result > 0;
    }
    /**
     * Get quantity for a given attribute combination
     * Check if quantity is enough to deserve customer
     *
     * @param int $idProductAttribute Product attribute combination id
     * @param int $qty Quantity needed
     *
     *
     * @return bool Quantity is available or not
     * @throws PrestaShopException
     */
    public static function check_attribute_qty($id_product_attribute, $qty, ?Shop $shop = null)
    {
        if (!$shop) {
            $shop = Context::get_context()->shop;
        }
        $result = Stock_Available::get_quantity_available_by_product(null, (int) $id_product_attribute, $shop->id);
        return $result && $qty <= $result;
    }
    /**
     * @param int $idProduct Product ID
     * @return int Quantity
     * @throws PrestaShopException
     *
     * @deprecated 1.0.0, use StockAvailable::getQuantityAvailableByProduct()
     */
    public static function get_attribute_qty($id_product)
    {
        Tools::display_as_deprecated();
        return Stock_Available::get_quantity_available_by_product($id_product);
    }
    /**
     * Update array with veritable quantity
     *
     * @deprecated since 1.0.0
     *
     * @param array $arr
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function update_qty_product(&$arr)
    {
        Tools::display_as_deprecated();
        $id_product = (int) $arr['id_product'];
        $qty = Stock_Available::get_quantity_available_by_product($id_product);
        $arr['quantity'] = (int) $qty;
        return true;
    }
    /**
     * Return true if attribute is color type
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_color_attribute()
    {
        return (bool) Db::read_only()->get_value((new Db_Query())->select('ag.`group_type`')->from('attribute_group', 'ag')->inner_join('attribute', 'a', 'a.`id_attribute_group` = ag.`id_attribute_group`')->where('`group_type` = \'color\''));
    }
    /**
     * Get minimal quantity for product with attributes quantity
     *
     * @param int $idProductAttribute
     *
     * @return false|int Minimal Quantity or false
     *
     * @throws PrestaShopException
     */
    public static function get_attribute_minimal_qty($id_product_attribute)
    {
        $minimal_quantity = Db::read_only()->get_value((new Db_Query())->select('`minimal_quantity`')->from('product_attribute_shop', 'pas')->where('`id_shop` = ' . (int) Context::get_context()->shop->id)->where('`id_product_attribute` = ' . (int) $id_product_attribute));
        if ($minimal_quantity > 1) {
            return (int) $minimal_quantity;
        }
        return false;
    }
    /**
     * Move an attribute inside its group
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
        if (!$id_attribute_group = Tools::get_int_value('id_attribute_group')) {
            $id_attribute_group = (int) $this->id_attribute_group;
        }
        if (!$res = Db::read_only()->get_array((new Db_Query())->select('a.`id_attribute`, a.`position`, a.`id_attribute_group`')->from('attribute', 'a')->where('a.`id_attribute_group` = ' . (int) $id_attribute_group)->order_by('a.`position` ASC'))) {
            return false;
        }
        foreach ($res as $attribute) {
            if ((int) $attribute['id_attribute'] == (int) $this->id) {
                $moved_attribute = $attribute;
            }
        }
        if (!isset($moved_attribute) || !isset($position)) {
            return false;
        }
        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $conn = Db::get_instance();
        $res1 = $conn->update('attribute', ['position' => ['type' => 'sql', 'value' => '`position` ' . ($way ? '- 1' : '+ 1')]], '`position`' . ($way ? '> ' . (int) $moved_attribute['position'] . ' AND `position` <= ' . (int) $position : '< ' . (int) $moved_attribute['position'] . ' AND `position` >= ' . (int) $position) . ' AND `id_attribute_group`=' . (int) $moved_attribute['id_attribute_group']);
        $res2 = $conn->update('attribute', ['position' => (int) $position], '`id_attribute` = ' . (int) $moved_attribute['id_attribute'] . ' AND `id_attribute_group`=' . (int) $moved_attribute['id_attribute_group']);
        return $res1 && $res2;
    }
    /**
     * Reorder attribute position in group $id_attribute_group.
     * Call it after deleting an attribute from a group.
     *
     * @param int $idAttributeGroup
     * @param bool $useLastAttribute
     *
     * @return bool $return
     *
     * @throws PrestaShopException
     */
    public function clean_positions($id_attribute_group, $use_last_attribute = true)
    {
        $conn = Db::get_instance();
        $conn->execute('SET @i = -1', false);
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'attribute` SET `position` = @i:=@i+1 WHERE';
        if ($use_last_attribute) {
            $sql .= ' `id_attribute` != ' . (int) $this->id . ' AND';
        }
        $sql .= ' `id_attribute_group` = ' . (int) $id_attribute_group . ' ORDER BY `position` ASC';
        return $conn->execute($sql);
    }
    /**
     * getHigherPosition
     *
     * Get the higher attribute position from a group attribute
     *
     * @param int $idAttributeGroup
     *
     * @return int $position
     *
     * @throws PrestaShopException
     */
    public static function get_higher_position($id_attribute_group)
    {
        $position = Db::read_only()->get_value((new Db_Query())->select('MAX(`position`)')->from('attribute')->where('`id_attribute_group` = ' . (int) $id_attribute_group));
        return is_numeric($position) ? $position : -1;
    }
    /**
     * Returns file path to attribute texture file, if exists
     *
     *
     * @return string|false
     * @throws PrestaShopException
     */
    public static function get_texture_file_path(int $attribute_id)
    {
        return Image_Manager::get_source_image(_PS_COL_IMG_DIR_, $attribute_id);
    }
}