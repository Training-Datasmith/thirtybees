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
use Core_Updater\Table_Schema;
/**
 * Class CombinationCore
 */
class Combination_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'product_attribute', 'primary' => 'id_product_attribute', 'fields' => ['id_product' => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId', 'required' => true], 'reference' => ['type' => self::TYPE_STRING, 'size' => 32], 'supplier_reference' => ['type' => self::TYPE_STRING, 'size' => 32], 'location' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64], 'ean13' => ['type' => self::TYPE_STRING, 'validate' => 'isEan13', 'size' => 13], 'upc' => ['type' => self::TYPE_STRING, 'validate' => 'isUpc', 'size' => 12], 'wholesale_price' => ['type' => self::TYPE_PRICE, 'shop' => true, 'validate' => 'isPrice', 'size' => 20, 'dbDefault' => '0.000000'], 'price' => ['type' => self::TYPE_PRICE, 'shop' => true, 'validate' => 'isNegativePrice', 'size' => 20, 'dbDefault' => '0.000000'], 'ecotax' => ['type' => self::TYPE_PRICE, 'shop' => true, 'validate' => 'isPrice', 'dbDefault' => '0.000000'], 'quantity' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'size' => 10, 'signed' => true, 'dbDefault' => '0'], 'weight' => ['type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isFloat', 'dbDefault' => '0.000000'], 'width' => ['type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isUnsignedFloat', 'dbDefault' => '0.000000'], 'height' => ['type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isUnsignedFloat', 'dbDefault' => '0.000000'], 'depth' => ['type' => self::TYPE_FLOAT, 'shop' => true, 'validate' => 'isUnsignedFloat', 'dbDefault' => '0.000000'], 'unit_price_impact' => ['type' => self::TYPE_PRICE, 'shop' => true, 'validate' => 'isNegativePrice', 'size' => 20, 'dbDefault' => '0.000000'], 'default_on' => ['type' => self::TYPE_BOOL, 'allow_null' => true, 'shop' => true, 'validate' => 'isBool'], 'minimal_quantity' => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId', 'required' => true, 'dbDefault' => '1'], 'available_date' => ['type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat', 'dbType' => 'date', 'dbDefault' => '1970-01-01']], 'keys' => ['product_attribute' => ['product_default' => ['type' => Object_Model::UNIQUE_KEY, 'columns' => ['id_product', 'default_on']], 'id_product_id_product_attribute' => ['type' => Object_Model::KEY, 'columns' => ['id_product_attribute', 'id_product']], 'product_attribute_product' => ['type' => Object_Model::KEY, 'columns' => ['id_product']], 'reference' => ['type' => Object_Model::KEY, 'columns' => ['reference']], 'supplier_reference' => ['type' => Object_Model::KEY, 'columns' => ['supplier_reference']]], 'product_attribute_shop' => ['id_product' => ['type' => Object_Model::UNIQUE_KEY, 'columns' => ['id_product', 'id_shop', 'default_on']]]]];
    /**
     * @var int $id_product
     */
    public $id_product;
    /**
     * @var string $location
     */
    public $location;
    /**
     * @var string $ean13
     */
    public $ean13;
    /**
     * @var string $upc
     */
    public $upc;
    /**
     * @var int $quantity
     */
    public $quantity;
    /**
     * @var string $reference
     */
    public $reference;
    /**
     * @var string $supplier_reference
     */
    public $supplier_reference;
    /**
     * @var float $wholesale_price
     */
    public $wholesale_price;
    /**
     * @var float $price
     */
    public $price;
    /**
     * @var float $ecotax
     */
    public $ecotax;
    /**
     * @var float $weight
     */
    public $weight;
    /**
     * @var float $width Impact on width dimension
     */
    public $width;
    /**
     * @var float $height Impact on height dimension
     */
    public $height;
    /**
     * @var float height Impact on depth dimension
     */
    public $depth;
    /**
     * @var float $unit_price_impact
     */
    public $unit_price_impact;
    /**
     * @var int $minimal_quantity
     */
    public $minimal_quantity = 1;
    /**
     * @var bool $default_on
     */
    public $default_on;
    /**
     * @var string $available_date
     */
    public $available_date = '0000-00-00';
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectNodeName' => 'combination', 'objectsNodeName' => 'combinations', 'fields' => ['id_product' => ['required' => true, 'xlink_resource' => 'products']], 'associations' => ['product_option_values' => ['resource' => 'product_option_value', 'fields' => ['id' => []]], 'images' => ['resource' => 'image', 'api' => 'images/products', 'fields' => ['id' => []]]]];
    /**
     * @var array<int, int>]|null
     */
    protected $attributes;
    /**
     * This method is allowed to know if a feature is active
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_feature_active()
    {
        static $feature_active = null;
        if ($feature_active === null) {
            $feature_active = Configuration::get('PS_COMBINATION_FEATURE_ACTIVE');
        }
        return $feature_active;
    }
    /**
     * This method is allowed to know if a Combination entity is currently used
     *
     * @param string|null $table
     * @param bool $hasActiveColumn
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_currently_used($table = null, $has_active_column = false)
    {
        return parent::is_currently_used('product_attribute');
    }
    /**
     * For a given product_attribute reference, returns the corresponding id
     *
     * @param int $idProduct
     * @param string $reference
     *
     * @return int id
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_reference($id_product, $reference)
    {
        if (empty($reference)) {
            return 0;
        }
        return Db::read_only()->get_value((new Db_Query())->select('pa.id_product_attribute')->from('product_attribute', 'pa')->where('pa.reference LIKE \'%' . p_sql($reference) . '%\'')->where('pa.id_product = ' . (int) $id_product));
    }
    /**
     * Retrive the price of combination
     *
     * @param int $idProductAttribute
     *
     * @return float mixed
     *
     * @throws PrestaShopException
     */
    public static function get_price($id_product_attribute)
    {
        return (float) Db::read_only()->get_value((new Db_Query())->select('product_attribute_shop.`price`')->from('product_attribute', 'pa')->join(Shop::add_sql_association('product_attribute', 'pa'))->where('pa.`id_product_attribute` = ' . (int) $id_product_attribute));
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }
        // Removes the product from StockAvailable, for the current shop
        Stock_Available::remove_product_from_stock_available((int) $this->id_product, (int) $this->id);
        if ($specific_prices = Specific_Price::get_by_product_id((int) $this->id_product, (int) $this->id)) {
            foreach ($specific_prices as $specific_price) {
                $price = new Specific_Price((int) $specific_price['id_specific_price']);
                $price->delete();
            }
        }
        if (!$this->has_multishop_entries() && !$this->delete_associations()) {
            return false;
        }
        $this->delete_from_supplier($this->id_product);
        Product::update_default_attribute($this->id_product);
        Tools::clear_color_list_cache((int) $this->id_product);
        return true;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_associations()
    {
        $conn = Db::get_instance();
        $result = $conn->delete('product_attribute_combination', '`id_product_attribute` = ' . (int) $this->id);
        $result = $conn->delete('cart_product', '`id_product_attribute` = ' . (int) $this->id) && $result;
        return $conn->delete('product_attribute_image', '`id_product_attribute` = ' . (int) $this->id) && $result;
    }
    /**
     * @param int $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_from_supplier($id_product)
    {
        return Db::get_instance()->delete('product_supplier', 'id_product = ' . (int) $id_product . ' AND id_product_attribute = ' . (int) $this->id);
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if ($this->default_on) {
            $this->default_on = 1;
        } else {
            $this->default_on = null;
        }
        if (!parent::add($auto_date, $null_values)) {
            return false;
        }
        $product = new Product((int) $this->id_product);
        if ($product->get_type() == Product::PTYPE_VIRTUAL) {
            Stock_Available::set_product_out_of_stock((int) $this->id_product, Stock_Available::OUT_OF_STOCK_ALLOW, null, (int) $this->id);
        } else {
            Stock_Available::set_product_out_of_stock((int) $this->id_product, Stock_Available::out_of_stock((int) $this->id_product), null, $this->id);
        }
        Specific_Price_Rule::apply_all_rules([(int) $this->id_product]);
        Product::update_default_attribute($this->id_product);
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
        if ($this->default_on) {
            $this->default_on = 1;
        } else {
            $this->default_on = null;
        }
        $return = parent::update($null_values);
        Product::update_default_attribute($this->id_product);
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
        $ids_attributes = [];
        foreach ($values as $value) {
            if (isset($value['id']) && (int) $value['id']) {
                $ids_attributes[] = (int) $value['id'];
            }
        }
        return $this->set_attributes($ids_attributes);
    }
    /**
     * @param int[] $idsAttribute
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_attributes($ids_attribute)
    {
        $conn = Db::get_instance();
        $result = $conn->delete('product_attribute_combination', '`id_product_attribute` = ' . (int) $this->id);
        if ($result && is_array($ids_attribute) && $ids_attribute) {
            $sql_values = [];
            foreach ($ids_attribute as $value) {
                $value = (int) $value;
                if ($value) {
                    $sql_values[] = ['id_attribute' => $value, 'id_product_attribute' => (int) $this->id];
                }
            }
            if ($sql_values) {
                $result = $conn->insert('product_attribute_combination', $sql_values);
            }
        }
        return $result;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_product_option_values()
    {
        return Db::read_only()->get_array((new Db_Query())->select('a.`id_attribute` AS `id`')->from('product_attribute_combination', 'a')->join(Shop::add_sql_association('attribute', 'a'))->where('a.`id_product_attribute` = ' . (int) $this->id));
    }
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_ws_images()
    {
        return Db::read_only()->get_array((new Db_Query())->select('a.`id_image` AS `id`')->from('product_attribute_image', 'a')->join(Shop::add_sql_association('product_attribute', 'a'))->where('a.`id_product_attribute` = ' . (int) $this->id));
    }
    /**
     * @param array $values
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function set_ws_images($values)
    {
        $ids_images = [];
        foreach ($values as $value) {
            if (isset($value['id']) && (int) $value['id']) {
                $ids_images[] = (int) $value['id'];
            }
        }
        return $this->set_images($ids_images);
    }
    /**
     * @param array $idsImage
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_images($ids_image)
    {
        $conn = Db::get_instance();
        if ($conn->delete('product_attribute_image', '`id_product_attribute` = ' . (int) $this->id) === false) {
            return false;
        }
        if (is_array($ids_image) && count($ids_image)) {
            $sql_values = [];
            foreach ($ids_image as $value) {
                $value = (int) $value;
                if ($value) {
                    $sql_values[] = ['id_product_attribute' => (int) $this->id, 'id_image' => $value];
                }
            }
            if ($sql_values) {
                $conn->insert('product_attribute_image', $sql_values);
            }
        }
        return true;
    }
    /**
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_attributes_name($id_lang)
    {
        return Db::read_only()->get_array((new Db_Query())->select('al.*')->from('product_attribute_combination', 'pac')->inner_join('attribute_lang', 'al', 'pac.`id_attribute` = al.`id_attribute`')->where('al.`id_lang` = ' . (int) $id_lang)->where('pac.`id_product_attribute` = ' . (int) $this->id));
    }
    /**
     * Returns map of used attributes [attribute group -> attribute id]
     *
     * @return array<int, int>
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_attributes(): array
    {
        if (is_null($this->attributes)) {
            $this->attributes = [];
            $result = Db::read_only()->get_array((new Db_Query())->select('ag.id_attribute_group, a.`id_attribute`')->from('product_attribute_combination', 'pac')->inner_join('attribute', 'a', 'pac.`id_attribute` = a.`id_attribute`')->inner_join('attribute_group', 'ag', 'ag.`id_attribute_group` = a.`id_attribute_group`')->where('pac.`id_product_attribute` = ' . (int) $this->id));
            foreach ($result as $row) {
                $attribute_group_id = (int) $row['id_attribute_group'];
                $attribute_id = (int) $row['id_attribute'];
                $this->attributes[$attribute_group_id] = $attribute_id;
            }
        }
        return $this->attributes;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_colors_attributes()
    {
        return Db::read_only()->get_array((new Db_Query())->select('a.`id_attribute`')->from('product_attribute_combination', 'pac')->inner_join('attribute', 'a', 'pac.`id_attribute` = a.`id_attribute`')->inner_join('attribute_group', 'ag', 'ag.`id_attribute_group` = a.`id_attribute_group`')->where('pac.`id_product_attribute` = ' . (int) $this->id)->where('ag.`is_color_group` = 1'));
    }
    /**
     * @param TableSchema $table
     */
    public static function process_table_schema($table): void
    {
        if ($table->get_name_without_prefix() === 'product_attribute_shop') {
            $table->reorder_columns(['id_product', 'id_product_attribute', 'id_shop']);
        }
    }
}