<?php

declare (strict_types=1);
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2018 thirty bees
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
 * @copyright 2017-2018 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */
/**
 * Class ImageEntityCore
 */
class Image_Entity_Core extends Object_Model
{
    public const ENTITY_TYPE_PRODUCTS = 'products';
    public const ENTITY_TYPE_CATEGORIES = 'categories';
    public const ENTITY_TYPE_CATEGORIES_THUMB = 'categoriesthumb';
    public const ENTITY_TYPE_MANUFACTURERS = 'manufacturers';
    public const ENTITY_TYPE_SUPPLIERS = 'suppliers';
    public const ENTITY_TYPE_SCENES = 'scenes';
    public const ENTITY_TYPE_SCENES_THUMB = 'scenesthumb';
    public const ENTITY_TYPE_STORES = 'stores';
    /**
     * @var string Name
     */
    public $id_image_entity;
    /**
     * @var string Name
     */
    public $name;
    /**
     * @var string Classname
     */
    public $classname;
    /**
     * @var string|string[]
     */
    public $display_name;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'image_entity', 'primary' => 'id_image_entity', 'multilang' => true, 'fields' => [
        'name' => ['type' => self::TYPE_STRING, 'validate' => 'isImageTypeName', 'required' => true, 'size' => 64],
        'classname' => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 64],
        /* Lang fields */
        'display_name' => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 128, 'lang' => true],
    ], 'keys' => ['image_entity' => ['image_entity_name' => ['type' => Object_Model::KEY, 'columns' => ['name']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectsNodeName' => 'image_entities', 'objectNodeName' => 'image_entity', 'fields' => [], 'associations' => ['image_types' => ['resource' => 'image_types', 'fields' => ['id' => []]]]];
    /**
     * @param string $classname This needs to be the classname like defined in AdminController (with namespace)
     * @param array $images The structure is defined ObjectModel $definition['images']
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function rebuild_image_entities($classname, $images): void
    {
        // Adding images from themes
        foreach (Theme::get_used_themes() as $theme) {
            $xml = $theme->load_config_file();
            foreach ($xml->images->image as $image_definition) {
                $width = (int) $image_definition['width'];
                $height = (int) $image_definition['height'];
                $name = $theme->name . '_' . $image_definition['name'];
                if ((string) $image_definition[static::ENTITY_TYPE_PRODUCTS] === 'true') {
                    $images[static::ENTITY_TYPE_PRODUCTS]['classname'] = 'Product';
                    $images[static::ENTITY_TYPE_PRODUCTS]['imageTypes'][] = ['name' => $name, 'width' => $width, 'height' => $height];
                }
                if ((string) $image_definition[static::ENTITY_TYPE_CATEGORIES] === 'true') {
                    $images[static::ENTITY_TYPE_CATEGORIES]['classname'] = 'Category';
                    $images[static::ENTITY_TYPE_CATEGORIES]['imageTypes'][] = ['name' => $name, 'width' => $width, 'height' => $height];
                }
                if ((string) $image_definition[static::ENTITY_TYPE_CATEGORIES_THUMB] === 'true') {
                    $images[static::ENTITY_TYPE_CATEGORIES_THUMB]['classname'] = 'Category';
                    $images[static::ENTITY_TYPE_CATEGORIES_THUMB]['imageTypes'][] = ['name' => $name, 'width' => $width, 'height' => $height];
                }
                if ((string) $image_definition[static::ENTITY_TYPE_MANUFACTURERS] === 'true') {
                    $images[static::ENTITY_TYPE_MANUFACTURERS]['classname'] = 'Manufacturer';
                    $images[static::ENTITY_TYPE_MANUFACTURERS]['imageTypes'][] = ['name' => $name, 'width' => $width, 'height' => $height];
                }
                if ((string) $image_definition[static::ENTITY_TYPE_SUPPLIERS] === 'true') {
                    $images[static::ENTITY_TYPE_SUPPLIERS]['classname'] = 'Supplier';
                    $images[static::ENTITY_TYPE_SUPPLIERS]['imageTypes'][] = ['name' => $name, 'width' => $width, 'height' => $height];
                }
                if (Tab::get_id_from_class_name('AdminScenes')) {
                    if ((string) $image_definition[static::ENTITY_TYPE_SCENES] === 'true') {
                        $images[static::ENTITY_TYPE_SCENES]['classname'] = 'Scene';
                        $images[static::ENTITY_TYPE_SCENES]['imageTypes'][] = ['name' => $name, 'width' => $width, 'height' => $height];
                    }
                    if ((string) $image_definition[static::ENTITY_TYPE_SCENES_THUMB] === 'true') {
                        $images[static::ENTITY_TYPE_SCENES_THUMB]['classname'] = 'Scene';
                        $images[static::ENTITY_TYPE_SCENES_THUMB]['imageTypes'][] = ['name' => $name, 'width' => $width, 'height' => $height];
                    }
                }
                if ((string) $image_definition[static::ENTITY_TYPE_STORES] === 'true') {
                    $images[static::ENTITY_TYPE_STORES]['classname'] = 'Store';
                    $images[static::ENTITY_TYPE_STORES]['imageTypes'][] = ['name' => $name, 'width' => $width, 'height' => $height];
                }
            }
        }
        foreach ($images as $image_entity_name => $image_entity) {
            $existing_image_entity = static::get_image_entity_info($image_entity_name);
            $image_entity_id = isset($existing_image_entity['id_image_entity']) ? (int) $existing_image_entity['id_image_entity'] : 0;
            $image_entity_obj = new Image_Entity($image_entity_id);
            $image_entity_obj->name = $image_entity_name;
            $image_entity_obj->classname = $image_entity['classname'] ?? $classname;
            $display_name = [];
            foreach (Language::get_languages(false, false, true) as $lang_id) {
                if (isset($image_entity_obj->display_name[$lang_id]) && $image_entity_obj->display_name[$lang_id]) {
                    $display_name[$lang_id] = $image_entity_obj->display_name[$lang_id];
                } else {
                    $display_name[$lang_id] = $image_entity['displayName'] ?? ucfirst((string) $image_entity_name);
                }
            }
            $image_entity_obj->display_name = $display_name;
            $image_entity_obj->save();
            $image_entity_id = (int) $image_entity_obj->id;
            if ($image_entity_id && !empty($image_entity['imageTypes']) && is_array($image_entity['imageTypes'])) {
                foreach ($image_entity['imageTypes'] as $image_type) {
                    $image_type_name_formated = Image_Type::get_formated_name($image_type['name']);
                    $image_type_obj = Image_Type::get_instance_by_name($image_type_name_formated);
                    // Adding missing image types
                    if (!$image_type_obj->id) {
                        $image_type_obj->name = $image_type['name'];
                        $image_type_obj->width = (int) $image_type['width'];
                        $image_type_obj->height = (int) $image_type['height'];
                        $image_type_obj->add();
                    }
                    // Link imageType to imageEntity
                    if ($image_type_obj->id) {
                        $image_entity_obj->associate_image_type($image_type_obj->id);
                    }
                }
            }
        }
        static::rebuild_based_on_old_types();
        Configuration::update_global_value('TB_IMAGE_ENTITY_REBUILD_LAST', date('Y-m-d H:i:s'));
    }
    /**
     * Function to transform the old entity structure into table image_entity_type
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private static function rebuild_based_on_old_types(): void
    {
        // This function should only be executed once
        if (!Configuration::get('TB_IMAGE_ENTITY_REBUILD_LAST')) {
            $db = Db::get_instance();
            $query = new Db_Query();
            $query->select('*');
            $query->from('image_type');
            $image_types = $db->get_array($query);
            // Get ids_image_entity
            $ids_image_entity = [];
            foreach (static::get_image_entities() as $image_entity) {
                $ids_image_entity[$image_entity['name']] = $image_entity['id_image_entity'];
            }
            $old_entity_types = static::get_legacy_image_entities();
            foreach ($image_types as $image_type) {
                foreach ($old_entity_types as $old_entity_type) {
                    if ($image_type[$old_entity_type] && isset($ids_image_entity[$old_entity_type])) {
                        $data = ['id_image_entity' => $ids_image_entity[$old_entity_type], 'id_image_type' => $image_type['id_image_type']];
                        $db->insert('image_entity_type', $data, false, true, Db::REPLACE);
                    }
                }
            }
        }
    }
    /**
     * @return ImageEntity[]
     *
     * @throws PrestaShopException
     */
    public static function get_all()
    {
        $collection = new Presta_Shop_Collection('ImageEntity');
        return $collection->get_results();
    }
    /**
     *
     * @return array|null
     * @throws PrestaShopException
     */
    public static function get_image_entity_info(string $image_entity_name)
    {
        if ($image_entity_name) {
            $entities = static::get_image_entities();
            if (isset($entities[$image_entity_name])) {
                return $entities[$image_entity_name];
            }
        }
        return null;
    }
    /**
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_image_entities(): array
    {
        $lang_id = (int) Context::get_context()->language->id;
        $cache_key = 'ImageEntity::getImageEntities_' . $lang_id;
        if (!Cache::is_stored($cache_key)) {
            $query = new Db_Query();
            $query->select('ie.*');
            $query->select('l.display_name');
            $query->from(static::$definition['table'], 'ie');
            $query->select('it.id_image_type, it.name AS image_type, it.width, it.height, it.id_image_type_parent');
            $query->left_join('image_entity_type', 'iet', '(iet.id_image_entity = ie.id_image_entity)');
            $query->left_join('image_type', 'it', '(iet.id_image_type = it.id_image_type)');
            $query->left_join('image_entity_lang', 'l', '(l.id_image_entity = ie.id_image_entity AND l.id_lang = ' . $lang_id . ')');
            $query->order_by('ie.name ASC');
            $result = Db::get_instance()->get_array($query);
            $image_entities = [];
            foreach ($result as $res) {
                $name = $res['name'];
                if (!isset($image_entities[$name])) {
                    // Get data from object model $definition
                    $class_name = $res['classname'];
                    $definition = Object_Model::get_definition($class_name);
                    $image_entities[$name] = ['table' => $definition['table'], 'primary' => $definition['primary'], 'path' => $definition['images'][$name]['path'] ?? '', 'name' => $name, 'display_name' => $res['display_name'] ?: ucfirst((string) $name), 'classname' => $class_name, 'id_image_entity' => (int) $res['id_image_entity'], 'imageTypes' => []];
                }
                $image_type_id = (int) $res['id_image_type'];
                if ($image_type_id) {
                    $image_entities[$name]['imageTypes'][] = ['id_image_type' => $image_type_id, 'name' => $res['image_type'], 'width' => (int) $res['width'], 'height' => (int) $res['height'], 'id_image_type_parent' => (int) $res['id_image_type_parent']];
                }
            }
            Cache::store($cache_key, $image_entities);
            return $image_entities;
        }
        return Cache::retrieve($cache_key);
    }
    /**
     * Method associates ImageTypes records with this ImageEntity object
     *
     * @param int[] $imageTypeIds ids of image types
     * @param bool $deleteExisting if true, existing associations will be deleted first
     *
     * @throws PrestaShopException
     */
    public function associate_image_types(array $image_type_ids, $delete_existing = false): void
    {
        $image_entity_id = (int) $this->id;
        if ($image_entity_id) {
            $conn = Db::get_instance();
            if ($delete_existing) {
                $conn->delete('image_entity_type', "id_image_entity = {$image_entity_id}");
                // BC: keep legacy properties in tb_image_type synchronized
                if (in_array($this->name, static::get_legacy_image_entities())) {
                    $conn->update('image_type', [$this->name => 0]);
                }
            }
            foreach ($image_type_ids as $image_type_id) {
                $this->associate_image_type((int) $image_type_id);
            }
        }
    }
    /**
     *
     *
     * @throws PrestaShopException
     */
    public function associate_image_type(int $image_type_id): void
    {
        $image_entity_id = (int) $this->id;
        if ($image_entity_id && $image_type_id) {
            $conn = Db::get_instance();
            $conn->insert('image_entity_type', ['id_image_entity' => $image_entity_id, 'id_image_type' => $image_type_id], false, true, Db::INSERT_IGNORE);
            // BC: keep legacy properties in tb_image_type synchronized
            if (in_array($this->name, static::get_legacy_image_entities())) {
                $conn->update('image_type', [$this->name => 1], 'id_image_type = ' . $image_type_id);
            }
            Image_Type::clean_cache();
        }
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
        $res = parent::add($auto_date, $null_values);
        Image_Type::clean_cache();
        return $res;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_image_types()
    {
        $result = [];
        $info = static::get_image_entity_info($this->name);
        if ($info) {
            foreach ($info['imageTypes'] as $type) {
                $result[] = ['id' => (int) $type['id_image_type']];
            }
        }
        return $result;
    }
    /**
     * @return string[]
     */
    public static function get_legacy_image_entities()
    {
        return [static::ENTITY_TYPE_PRODUCTS, static::ENTITY_TYPE_CATEGORIES, static::ENTITY_TYPE_MANUFACTURERS, static::ENTITY_TYPE_SUPPLIERS, static::ENTITY_TYPE_SCENES, static::ENTITY_TYPE_STORES];
    }
}