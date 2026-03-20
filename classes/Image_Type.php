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
 * Class ImageTypeCore
 */
class Image_Type_Core extends Object_Model
{
    /**
     * @var string Name
     */
    public $name;
    /**
     * @var int Width
     */
    public $width;
    /**
     * @var int Height
     */
    public $height;
    /**
     * @var int $id_image_type_parent if set, the imageType acts like an alias
     */
    public $id_image_type_parent;
    /**
     * @var bool Apply to products
     *
     * @deprecated since 1.5 -> imageEntities are handled by table image_entity
     */
    public $products;
    /**
     * @var bool Apply to categories
     *
     * @deprecated since 1.5 -> imageEntities are handled by table image_entity
     */
    public $categories;
    /**
     * @var bool Apply to manufacturers
     *
     * @deprecated since 1.5 -> imageEntities are handled by table image_entity
     */
    public $manufacturers;
    /**
     * @var bool Apply to suppliers
     *
     * @deprecated since 1.5 -> imageEntities are handled by table image_entity
     */
    public $suppliers;
    /**
     * @var bool Apply to scenes
     *
     * @deprecated since 1.5 -> imageEntities are handled by table image_entity
     */
    public $scenes;
    /**
     * @var bool Apply to store
     *
     * @deprecated since 1.5 -> imageEntities are handled by table image_entity
     */
    public $stores;
    /**
     * @var string[]
     */
    protected static $type_name_cache;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'image_type', 'primary' => 'id_image_type', 'fields' => ['name' => ['type' => self::TYPE_STRING, 'validate' => 'isImageTypeName', 'required' => true, 'size' => 64], 'width' => ['type' => self::TYPE_INT, 'validate' => 'isImageSize', 'required' => true], 'height' => ['type' => self::TYPE_INT, 'validate' => 'isImageSize', 'required' => true], 'id_image_type_parent' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'], 'products' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'], 'categories' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'], 'manufacturers' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'], 'suppliers' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'], 'scenes' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'], 'stores' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1']], 'keys' => ['image_type' => ['image_type_name' => ['type' => Object_Model::KEY, 'columns' => ['name']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectsNodeName' => 'image_types', 'objectNodeName' => 'image_type', 'fields' => [], 'associations' => ['image_entities' => ['resource' => 'image_entities', 'fields' => ['id' => []]]]];
    /**
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
        // BC: populate values of legacy properties based on entity association
        if ($id) {
            foreach (Image_Entity::get_legacy_image_entities() as $entity_type) {
                $this->{$entity_type} = 0;
                $info = Image_Entity::get_image_entity_info($entity_type);
                if ($info) {
                    foreach ($info['imageTypes'] as $type) {
                        if ((int) $type['id_image_type'] === $id) {
                            $this->{$entity_type} = 1;
                            break;
                        }
                    }
                }
            }
        }
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        $db = Db::get_instance();
        // Delete image entity types
        $db->delete('image_entity_type', 'id_image_type=' . $this->id);
        // Unhook aliases
        $db->update('image_type', ['id_image_type_parent' => 0], 'id_image_type_parent=' . $this->id);
        return parent::delete();
    }
    /**
     * Return an instance for the named image type. If no such image type
     * exists yet, return an empty instance with just the name set.
     *
     * @param string $typeName Name of the image type.
     * @param string $themeName Name of the theme this image type belongs to.
     *                          Defaults to the name of the current theme.
     *
     * @return ImageType
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_instance_by_name($type_name, $theme_name = null)
    {
        $name = $theme_name ? $theme_name . '_' . $type_name : $type_name;
        if (!static::type_already_exists($name)) {
            $type = new Image_Type();
            $type->name = $name;
            return $type;
        }
        $result = Db::read_only()->get_value((new Db_Query())->select('`id_image_type`')->from('image_type')->where('`name` = \'' . p_sql($name) . '\''));
        return new Image_Type($result);
    }
    /**
     * Returns image type definitions
     *
     * @param string|null $imageEntityName Name of imageEntity
     * @param bool $orderBySize
     *
     * @return array[] Image type definitions
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public static function get_images_types($image_entity_name = null, $order_by_size = false)
    {
        $cache_key = $image_entity_name ? 'ImageType::getImagesTypes_entity:' . $image_entity_name : 'ImageType::getImagesTypes_all';
        if (!Cache::is_stored($cache_key)) {
            if ($image_entity_name) {
                $image_entity = Image_Entity::get_image_entity_info($image_entity_name);
                $image_types = $image_entity['imageTypes'] ?? [];
            } else {
                $query = new Db_Query();
                $query->select('*');
                $query->from(self::$definition['table']);
                $query->order_by('`name` ASC');
                $image_types = Db::read_only()->get_array($query);
            }
            Cache::store($cache_key, $image_types);
        } else {
            $image_types = Cache::retrieve($cache_key);
        }
        if ($order_by_size) {
            usort($image_types, function (array $a, array $b): float|int {
                $ret = $a['width'] - $b['width'];
                if (!$ret) {
                    return $a['height'] - $b['height'];
                }
                return $ret;
            });
        }
        return $image_types;
    }
    /**
     * Check if type is already registered in database.
     *
     * @param string $typeName Name
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function type_already_exists($type_name)
    {
        $type_name_cache = static::get_indexed_image_type_names();
        return isset($type_name_cache[$type_name]);
    }
    /**
     * Return indexed list of image type names
     *
     * @return string[]
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function get_indexed_image_type_names()
    {
        if (is_null(static::$type_name_cache)) {
            $image_types = static::get_images_types();
            // index image types by name and ids
            $by_name = [];
            $by_id = [];
            foreach ($image_types as $type) {
                $name = (string) $type['name'];
                $id = (int) $type['id_image_type'];
                $by_name[$name] = $type;
                $by_id[$id] = $type;
            }
            static::$type_name_cache = array_map(function (array $type) use ($by_id) {
                for ($i = 0; $i < 20; $i++) {
                    $parent_id = (int) $type['id_image_type_parent'];
                    if ($parent_id && array_key_exists($parent_id, $by_id)) {
                        $type = $by_id[$parent_id];
                    } else {
                        break;
                    }
                }
                return $type['name'];
            }, $by_name);
        }
        return static::$type_name_cache;
    }
    /**
     * Find an existing variant of a specific image type.
     *
     * @param string $name image type name
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_formated_name($name)
    {
        if (!$name) {
            return $name;
        }
        $theme_name = '';
        $theme_dir = '';
        $theme = Context::get_context()->theme;
        if (Validate::is_loaded_object($theme)) {
            $theme_name = $theme->name;
            $theme_dir = $theme->directory;
        }
        return static::resolve_image_type_name($name, $theme_name, $theme_dir, static::get_indexed_image_type_names());
    }
    /**
     * Helper method to resolve image type name to canonical version. If this method fails to
     * resolve image type, input $name value is returned
     *
     * For example:
     *     Niara_cart -> Niara_cart
     *     Niara_cart_default -> Niara_cart
     *     cart -> Niara_cart
     *     cart_default -> Niara_cart
     *     non-existing -> non-existing
     *
     * @param string $name image type name
     * @param string $themeName theme name
     * @param string $themeDirectory theme directory name
     * @param array $imageTypes indexed map of all image types
     * @return string
     */
    protected static function resolve_image_type_name($name, $theme_name, $theme_directory, $image_types)
    {
        static $cache = [];
        $cache_key = $name . '|' . $theme_name . '|' . $theme_directory;
        if (!array_key_exists($cache_key, $cache)) {
            $cache[$cache_key] = static::resolve_image_type_name_without_cache($name, $theme_name, $theme_directory, $image_types);
        }
        return $cache[$cache_key];
    }
    /**
     * Helper method to resolve image type name to canonical version. If this method fails to
     * resolve image type, input $name value is returned
     *
     * @param string $name image type name
     * @param string $themeName theme name
     * @param string $themeDirectory theme directory name
     * @param array $imageTypes indexed map of all image types
     * @return string
     */
    protected static function resolve_image_type_name_without_cache($name, $theme_name, $theme_directory, $image_types)
    {
        // normalize input $name -- remove all theme prefixes/suffixes.
        $theme_names = array_unique([$theme_name, $theme_directory, 'default']);
        $regexps = [];
        foreach ($theme_names as $item) {
            $regexps[] = '/^' . preg_quote($item) . '_/i';
            $regexps[] = '/_' . preg_quote($item) . '$/i';
        }
        $name_without_theme = $name;
        do {
            $name_without_theme = preg_replace($regexps, '', (string) $name_without_theme, -1, $count);
        } while ($count > 0);
        // possible variants of the input image type name that we accept, ordered by priority
        $variants = [$theme_name . '_' . $name_without_theme, $theme_directory . '_' . $name_without_theme, $name_without_theme . '_' . $theme_name, $name_without_theme . '_' . $theme_directory, $theme_name . '_' . $name_without_theme . '_default', $theme_directory . '_' . $name_without_theme . '_default', $name_without_theme . '_' . $theme_name . '_default', $name_without_theme . '_' . $theme_directory . '_default', $name_without_theme, $name_without_theme . '_default', $theme_name . '_' . $name_without_theme . '_' . $theme_name, $theme_directory . '_' . $name_without_theme . '_' . $theme_directory];
        // image type is not case sensitive
        foreach ($image_types as $key => $value) {
            $lower = strtolower((string) $key);
            if ($lower != $key && !in_array($lower, $image_types)) {
                $image_types[$lower] = $value;
            }
        }
        // try to find variant for input name, and map it to actual name
        foreach ($variants as $variant) {
            if (array_key_exists($variant, $image_types)) {
                return $image_types[$variant];
            }
            $lower = strtolower($variant);
            if (array_key_exists($lower, $image_types)) {
                return $image_types[$lower];
            }
        }
        // Give up searching.
        return $name;
    }
    /**
     * @param int $imageTypeId ID (not name!) of imageType
     *
     * @return array of imageTypes
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_image_type_aliases(int $image_type_id): array
    {
        if ($image_type_id) {
            $query = new Db_Query();
            $query->select('*');
            $query->from(self::$definition['table']);
            $query->where('id_image_type_parent = ' . (int) $image_type_id);
            return Db::get_instance()->get_array($query);
        }
        return [];
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
        static::clean_cache();
        return $res;
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
        $res = parent::update($null_values);
        static::clean_cache();
        return $res;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_image_entities()
    {
        $result = [];
        foreach (Image_Entity::get_image_entities() as $image_entity) {
            foreach ($image_entity['imageTypes'] as $type) {
                if ((int) $type['id_image_type'] === (int) $this->id) {
                    $result[] = ['id' => (int) $image_entity['id_image_entity']];
                    break;
                }
            }
        }
        return $result;
    }
    public static function clean_cache(): void
    {
        static::$type_name_cache = null;
        Cache::clean('ImageType::*');
        Cache::clean('ImageEntity::*');
    }
}