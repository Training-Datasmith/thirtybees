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
 * Class ImageCore
 */
class Image_Core extends Object_Model
{
    /** @var int access rights of created folders (octal) */
    protected static $access_rights = 0775;
    /** @var array $_cacheGetSize */
    protected static $_cache_get_size = [];
    /** @var int Image ID */
    public $id_image;
    /** @var int Product ID */
    public $id_product;
    /** @var int Position used to order images of the same product */
    public $position;
    /** @var bool Image is cover */
    public $cover;
    /** @var string|string[] Legend */
    public $legend;
    /** @var string image extension */
    public $image_format;
    /** @var string path to index.php file to be copied to new image folders */
    public $source_index;
    /** @var string image folder */
    protected $folder;
    /** @var string image path without extension */
    protected $existing_path;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'image', 'primary' => 'id_image', 'multilang' => true, 'fields' => ['id_product' => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId', 'required' => true], 'position' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbType' => 'smallint(2) unsigned', 'dbDefault' => '0'], 'cover' => ['type' => self::TYPE_BOOL, 'allow_null' => true, 'validate' => 'isBool', 'shop' => true], 'legend' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128]], 'keys' => ['image' => ['idx_product_image' => ['type' => Object_Model::UNIQUE_KEY, 'columns' => ['id_image', 'id_product', 'cover']], 'id_product_cover' => ['type' => Object_Model::UNIQUE_KEY, 'columns' => ['id_product', 'cover']], 'image_product' => ['type' => Object_Model::KEY, 'columns' => ['id_product']]], 'image_lang' => ['id_image' => ['type' => Object_Model::KEY, 'columns' => ['id_image']]], 'image_shop' => ['id_product' => ['type' => Object_Model::UNIQUE_KEY, 'columns' => ['id_product', 'id_shop', 'cover']], 'id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]]];
    /**
     * ImageCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null)
    {
        parent::__construct($id, $id_lang);
        $this->image_dir = _PS_PROD_IMG_DIR_;
        $this->source_index = _PS_PROD_IMG_DIR_ . 'index.php';
        $this->image_format = Image_Manager::get_default_image_extension();
    }
    /**
     * Return first image (by position) associated with a product attribute
     *
     * @param int $idShop Shop ID
     * @param int $idLang Language ID
     * @param int $idProduct Product ID
     * @param int $idProductAttribute Product Attribute ID
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_best_image_attribute($id_shop, $id_lang, $id_product, $id_product_attribute)
    {
        $cache_id = 'Image::getBestImageAttribute' . '-' . (int) $id_product . '-' . (int) $id_product_attribute . '-' . (int) $id_lang . '-' . (int) $id_shop;
        if (!Cache::is_stored($cache_id)) {
            $row = Db::read_only()->get_row((new Db_Query())->select('image_shop.`id_image` id_image, il.`legend`')->from('image', 'i')->inner_join('image_shop', 'image_shop', 'i.`id_image` = image_shop.`id_image` AND image_shop.`id_shop` = ' . (int) $id_shop)->inner_join('product_attribute_image', 'pai', 'pai.`id_image` = i.`id_image` AND pai.`id_product_attribute` = ' . (int) $id_product_attribute)->left_join('image_lang', 'il', 'image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $id_lang)->where('i.`id_product` = ' . (int) $id_product)->order_by('i.`position` ASC'));
            Cache::store($cache_id, $row);
        } else {
            $row = Cache::retrieve($cache_id);
        }
        return $row;
    }
    /**
     * Return available images for a product
     *
     * @param int|null $idLang Language ID. Null/0/false = all languages.
     * @param int $idProduct Product ID
     * @param int $idProductAttribute Product Attribute ID
     *
     * @return array Images
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_images($id_lang, $id_product, $id_product_attribute = null)
    {
        $sql = new Db_Query();
        $sql->select('*');
        $sql->from('image', 'i');
        $sql->where('i.`id_product` = ' . (int) $id_product);
        if ($id_lang) {
            $sql->left_join('image_lang', 'il', 'i.`id_image` = il.`id_image`');
            $sql->where('il.`id_lang` = ' . (int) $id_lang);
        }
        if ($id_product_attribute) {
            $sql->left_join('product_attribute_image', 'ai', 'i.`id_image` = ai.`id_image`');
            $sql->where('ai.`id_product_attribute` = ' . (int) $id_product_attribute);
        }
        $sql->order_by('i.`position` ASC');
        return Db::read_only()->get_array($sql);
    }
    /**
     * Check if a product has an image available
     *
     * @param int $idLang Language ID. Null/0/false = all languages.
     * @param int $idProduct Product ID
     * @param int $idProductAttribute Product Attribute ID
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function has_images($id_lang, $id_product, $id_product_attribute = null)
    {
        $sql = new Db_Query();
        $sql->select('1');
        $sql->from('image', 'i');
        $sql->where('i.`id_product` = ' . (int) $id_product);
        if ($id_lang) {
            $sql->left_join('image_lang', 'il', 'i.`id_image` = il.`id_image`');
            $sql->where('il.`id_lang` = ' . (int) $id_lang);
        }
        if ($id_product_attribute) {
            $sql->left_join('product_attribute_image', 'ai', 'i.`id_image` = ai.`id_image`');
            $sql->where('ai.`id_product_attribute` = ' . (int) $id_product_attribute);
        }
        return (bool) Db::read_only()->get_value($sql);
    }
    /**
     * Return Images
     *
     * @return array Images
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_all_images()
    {
        return Db::read_only()->get_array((new Db_Query())->select('`id_image`, `id_product`')->from('image')->order_by('`id_image` ASC'));
    }
    /**
     * Return number of images for a product
     *
     * @param int $idProduct Product ID
     *
     * @return int number of images
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_images_total($id_product)
    {
        $result = Db::read_only()->get_row((new Db_Query())->select('COUNT(`id_image`) AS `total`')->from('image')->where('`id_product` = ' . (int) $id_product));
        return $result['total'];
    }
    /**
     * Delete product cover
     *
     * @param int $idProduct Product ID
     *
     * @return bool result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function delete_cover($id_product)
    {
        $conn = Db::get_instance();
        return $conn->update('image', ['cover' => ['type' => 'sql', 'value' => 'NULL']], '`id_product` = ' . (int) $id_product, 0, true) && $conn->update('image_shop', ['cover' => ['type' => 'sql', 'value' => 'NULL']], '`id_shop` IN (' . implode(',', array_map(intval(...), Shop::get_context_list_shop_id())) . ') AND `id_product` = ' . (int) $id_product);
    }
    /**
     *Get product cover
     *
     * @param int $idProduct Product ID
     *
     * @return array|false result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_cover($id_product)
    {
        return Db::read_only()->get_row((new Db_Query())->select('*')->from('image_shop')->where('`id_product` = ' . (int) $id_product)->where('`cover` = 1'));
    }
    /**
     * Get global product cover
     *
     * @param int $idProduct Product ID
     *
     * @return array|false result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_global_cover($id_product)
    {
        return Db::read_only()->get_row((new Db_Query())->select('*')->from('image', 'i')->where('i.`id_product` = ' . (int) $id_product)->where('i.`cover` = 1'));
    }
    /**
     * Copy images from a product to another
     *
     * @param int $idProductOld Source product ID
     * @param bool $idProductNew Destination product ID
     * @param array $combinationImages
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function duplicate_product_images($id_product_old, $id_product_new, $combination_images)
    {
        $image_types = Image_Type::get_images_types(Image_Entity::ENTITY_TYPE_PRODUCTS);
        $image_extension = Image_Manager::get_default_image_extension();
        $result = Db::read_only()->get_array((new Db_Query())->select('`id_image`')->from('image')->where('`id_product` = ' . (int) $id_product_old));
        foreach ($result as $row) {
            $image_old = new Image($row['id_image']);
            $image_new = clone $image_old;
            unset($image_new->id);
            $image_new->id_product = (int) $id_product_new;
            // A new id is generated for the cloned image when calling add()
            if ($image_new->add()) {
                $new_path = $image_new->get_path_for_creation();
                foreach ($image_types as $image_type) {
                    if (file_exists(_PS_PROD_IMG_DIR_ . $image_old->get_existing_img_path() . '-' . $image_type['name'] . '.' . $image_extension)) {
                        $image_new->create_img_folder();
                        copy(_PS_PROD_IMG_DIR_ . $image_old->get_existing_img_path() . '-' . $image_type['name'] . '.' . $image_extension, $new_path . '-' . $image_type['name'] . '.' . $image_extension);
                        if (Configuration::get('WATERMARK_HASH')) {
                            $old_image_path = _PS_PROD_IMG_DIR_ . $image_old->get_existing_img_path() . '-' . $image_type['name'] . '-' . Configuration::get('WATERMARK_HASH') . '.' . $image_extension;
                            if (file_exists($old_image_path)) {
                                copy($old_image_path, $new_path . '-' . $image_type['name'] . '-' . Configuration::get('WATERMARK_HASH') . '.' . $image_extension);
                            }
                        }
                    }
                }
                if ($source_file = Image_Manager::get_source_image(_PS_PROD_IMG_DIR_ . $image_old->get_img_folder(), $image_old->id)) {
                    copy($source_file, $new_path . '.' . $image_extension);
                }
                static::replace_attribute_image_association_id($combination_images, (int) $image_old->id, (int) $image_new->id);
                // Duplicate shop associations for images
                $image_new->duplicate_shops($id_product_old);
            } else {
                return false;
            }
        }
        return Image::duplicate_attribute_image_associations($combination_images);
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
        if ($this->position <= 0) {
            $this->position = Image::get_highest_position($this->id_product) + 1;
        }
        if ($this->cover) {
            $this->cover = 1;
        } else {
            $this->cover = null;
        }
        return parent::add($auto_date, $null_values);
    }
    /**
     * Return highest position of images for a product
     *
     * @param int $idProduct Product ID
     *
     * @return int highest position of images
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_highest_position($id_product)
    {
        $result = Db::read_only()->get_row((new Db_Query())->select('MAX(`position`) AS `max`')->from('image')->where('`id_product` = ' . (int) $id_product));
        return $result['max'];
    }
    /**
     * Returns the path where a product image should be created (without file format)
     *
     * @return string path
     */
    public function get_path_for_creation()
    {
        if (!$this->id) {
            return false;
        }
        $path = $this->get_img_path();
        $this->create_img_folder();
        return _PS_PROD_IMG_DIR_ . $path;
    }
    /**
     * Create parent folders for the image in the new filesystem
     *
     * @return bool success
     */
    public function create_img_folder()
    {
        if (!$this->id) {
            return false;
        }
        if (!file_exists(_PS_PROD_IMG_DIR_ . $this->get_img_folder())) {
            // Apparently sometimes mkdir cannot set the rights, and sometimes chmod can't. Trying both.
            $success = @mkdir(_PS_PROD_IMG_DIR_ . $this->get_img_folder(), static::$access_rights, true);
            $chmod = @chmod(_PS_PROD_IMG_DIR_ . $this->get_img_folder(), static::$access_rights);
            // Create an index.php file in the new folder
            if (($success || $chmod) && !file_exists(_PS_PROD_IMG_DIR_ . $this->get_img_folder() . 'index.php') && file_exists($this->source_index)) {
                return @copy($this->source_index, _PS_PROD_IMG_DIR_ . $this->get_img_folder() . 'index.php');
            }
        }
        return true;
    }
    /**
     * @param array $combinationImages
     * @param int $savedId
     * @param int $idImage
     */
    protected static function replace_attribute_image_association_id(&$combination_images, $saved_id, $id_image)
    {
        if (!isset($combination_images['new']) || !is_array($combination_images['new'])) {
            return;
        }
        foreach ($combination_images['new'] as $id_product_attribute => $image_ids) {
            foreach ($image_ids as $key => $image_id) {
                if ((int) $image_id == (int) $saved_id) {
                    $combination_images['new'][$id_product_attribute][$key] = (int) $id_image;
                }
            }
        }
    }
    /**
     * Duplicate product attribute image associations
     *
     * @param array $combinationImages
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function duplicate_attribute_image_associations($combination_images)
    {
        if (!isset($combination_images['new']) || !is_array($combination_images['new'])) {
            return true;
        }
        $insert = [];
        foreach ($combination_images['new'] as $id_product_attribute => $image_ids) {
            foreach ($image_ids as $image_id) {
                $insert[] = ['id_product_attribute' => (int) $id_product_attribute, 'id_image' => (int) $image_id];
            }
        }
        return Db::get_instance()->insert('product_attribute_image', $insert);
    }
    /**
     * @param array $params
     * @param Smarty_Internal_Template $smarty
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_width($params, $smarty)
    {
        $result = static::get_size($params['type']);
        return $result['width'];
    }
    /**
     * @param string $type
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_size($type)
    {
        $type = Image_Type::get_formated_name($type);
        if (!isset(static::$_cache_get_size[$type]) || static::$_cache_get_size[$type] === null) {
            static::$_cache_get_size[$type] = Db::read_only()->get_row((new Db_Query())->select('`width`, `height`')->from('image_type')->where('`name` = \'' . p_sql($type) . '\''));
        }
        return static::$_cache_get_size[$type];
    }
    /**
     * @param array $params
     * @param Smarty_Internal_Template $smarty
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_height($params, $smarty)
    {
        $result = static::get_size($params['type']);
        return $result['height'];
    }
    /**
     * Clear all images in tmp dir
     */
    public static function clear_tmp_dir(): void
    {
        $image_formats = implode('|', Image_Manager::get_allowed_image_extensions(false, true));
        foreach (scandir(_PS_TMP_IMG_DIR_) as $d) {
            if (preg_match('/(.*)\.(' . $image_formats . ')$/', $d)) {
                unlink(_PS_TMP_IMG_DIR_ . $d);
            }
        }
    }
    /**
     * Recursively deletes all product images in the given folder tree and removes empty folders.
     *
     * @param string $path folder containing the product images to delete
     * @param array|string $formats image formats to delete
     *
     * @return bool success
     *
     * @throws PrestaShopException
     */
    public static function delete_all_images($path = _PS_PROD_IMG_DIR_, $formats = null)
    {
        if (!$path || !is_dir($path)) {
            return false;
        }
        // normalize input variable $formats. It can either be null, string, or array
        if (is_null($formats)) {
            // all possible formats
            $formats = Image_Manager::get_allowed_image_extensions(false, true);
        } elseif (is_string($formats)) {
            // single format provided
            $formats = [$formats];
        } elseif (!is_array($formats)) {
            return false;
        }
        // recursively delete files
        foreach (@scandir($path) as $file) {
            if (is_dir($path . $file)) {
                if (preg_match('/^[0-9]$/', $file)) {
                    Image::delete_all_images($path . $file . '/', $formats);
                }
            } else {
                foreach ($formats as $format) {
                    if (preg_match('/^[0-9]+(\-(.*))?\.' . $format . '$/', $file)) {
                        @unlink($path . $file);
                    }
                }
            }
        }
        // Can we remove the image folder?
        if (is_numeric(basename($path))) {
            // delete image directory, if it's empty
            if (Tools::is_directory_empty($path, ['index.php'])) {
                Tools::delete_directory($path);
            }
        }
        return true;
    }
    /**
     * Move all legacy product image files from the image folder root to their subfolder in the new filesystem.
     * If max_execution_time is provided, stops before timeout and returns string "timeout".
     * If any image cannot be moved, stops and returns "false"
     *
     * @param int $maxExecutionTime
     *
     * @return bool|string success or timeout
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function move_to_new_file_system($max_execution_time = 0)
    {
        $start_time = time();
        $image = null;
        $tmp_folder = 'duplicates/';
        $image_formats = implode('|', Image_Manager::get_allowed_image_extensions(false, true));
        foreach (scandir(_PS_PROD_IMG_DIR_) as $file) {
            // matches the base product image or the thumbnails
            if (preg_match('/^([0-9]+\-)([0-9]+)(\-(.*))?\.(' . $image_formats . ')$/', $file, $matches)) {
                // don't recreate an image object for each image type
                if (!$image || $image->id !== (int) $matches[2]) {
                    $image = new Image((int) $matches[2]);
                }
                // image exists in DB and with the correct product?
                if (Validate::is_loaded_object($image) && $image->id_product == (int) rtrim($matches[1], '-')) {
                    // create the new folder if it does not exist
                    if (!$image->create_img_folder()) {
                        return false;
                    }
                    // if there's already a file at the new image path, move it to a dump folder
                    // most likely the preexisting image is a demo image not linked to a product and it's ok to replace it
                    if ($new_path = Image_Manager::get_source_image(_PS_PROD_IMG_DIR_ . $image->get_img_folder(), $image->id . (isset($matches[3]) ?? ''))) {
                        if (!file_exists(_PS_PROD_IMG_DIR_ . $tmp_folder)) {
                            @mkdir(_PS_PROD_IMG_DIR_ . $tmp_folder, static::$access_rights);
                            @chmod(_PS_PROD_IMG_DIR_ . $tmp_folder, static::$access_rights);
                        }
                        $tmp_path = _PS_PROD_IMG_DIR_ . $tmp_folder . basename($file);
                        if (!@rename($new_path, $tmp_path) || !file_exists($tmp_path)) {
                            return false;
                        }
                    }
                    // move the image
                    if (!@rename(_PS_PROD_IMG_DIR_ . $file, $new_path) || !file_exists($new_path)) {
                        return false;
                    }
                }
            }
            if ((int) $max_execution_time != 0 && time() - $start_time > (int) $max_execution_time - 4) {
                return 'timeout';
            }
        }
        return true;
    }
    /**
     * Try to create and delete some folders to check if moving images to new file system will be possible
     *
     * @return bool success
     */
    public static function test_file_system()
    {
        $folder1 = _PS_PROD_IMG_DIR_ . 'testfilesystem/';
        $test_folder = $folder1 . 'testsubfolder/';
        // check if folders are already existing from previous failed test
        if (file_exists($test_folder)) {
            @rmdir($test_folder);
            @rmdir($folder1);
        }
        if (file_exists($test_folder)) {
            return false;
        }
        @mkdir($test_folder, static::$access_rights, true);
        @chmod($test_folder, static::$access_rights);
        if (!is_writable($test_folder)) {
            return false;
        }
        @rmdir($test_folder);
        @rmdir($folder1);
        if (file_exists($folder1)) {
            return false;
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
        if ($this->cover) {
            $this->cover = 1;
        } else {
            $this->cover = null;
        }
        return parent::update($null_values);
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }
        if ($this->has_multishop_entries()) {
            return true;
        }
        if (!$this->delete_product_attribute_image() || !$this->delete_image()) {
            return false;
        }
        // update positions
        $conn = Db::get_instance();
        $conn->execute('SET @position:=0', false);
        $conn->execute('UPDATE `' . _DB_PREFIX_ . 'image` SET position=(@position:=@position+1)
									WHERE `id_product` = ' . (int) $this->id_product . ' ORDER BY position ASC');
        return true;
    }
    /**
     * Delete Image - Product attribute associations for this image
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_product_attribute_image()
    {
        return Db::get_instance()->delete('product_attribute_image', '`id_image` = ' . (int) $this->id);
    }
    /**
     * Delete the product image from disk and remove the containing folder if empty
     * Handles both legacy and new image filesystems
     *
     * @param bool $forceDelete
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_image($force_delete = false, $path = '')
    {
        if (!$this->id) {
            return false;
        }
        $directory = $this->image_dir . $this->get_img_folder();
        if (!is_dir($directory)) {
            return true;
        }
        // delete all possible image formats
        $image_extensions = Image_Manager::get_allowed_image_extensions(false, true);
        $result = true;
        foreach ($image_extensions as $image_extension) {
            if (!$this->delete_image_format($image_extension)) {
                $result = false;
            }
        }
        // delete image directory, if it's empty
        if (Tools::is_directory_empty($directory, ['index.php'])) {
            Tools::delete_directory($directory);
        }
        Image_Manager::delete_product_image_thumbnail($this->id);
        return $result;
    }
    /**
     * Delete the product images of given format from disk
     *
     * @param string $imageExtension
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function delete_image_format($image_extension)
    {
        // Delete base image
        if (file_exists($this->image_dir . $this->get_existing_img_path() . '.' . $image_extension)) {
            if (!@unlink($this->image_dir . $this->get_existing_img_path() . '.' . $image_extension)) {
                return false;
            }
        }
        $files_to_delete = [];
        // Delete auto-generated images
        $image_types = Image_Type::get_images_types();
        foreach ($image_types as $image_type) {
            $files_to_delete[] = $this->image_dir . $this->get_existing_img_path() . '-' . $image_type['name'] . '.' . $image_extension;
            if (Configuration::get('WATERMARK_HASH')) {
                $files_to_delete[] = $this->image_dir . $this->get_existing_img_path() . '-' . $image_type['name'] . '-' . Configuration::get('WATERMARK_HASH') . '.' . $image_extension;
            }
        }
        // Delete watermark image
        $files_to_delete[] = $this->image_dir . $this->get_existing_img_path() . '-watermark.' . $image_extension;
        // perform delete
        $result = true;
        foreach ($files_to_delete as $file) {
            if (file_exists($file)) {
                $result = @unlink($file) && $result;
            }
        }
        return $result;
    }
    /**
     * Returns image path in the old or in the new filesystem
     *
     * @return string image path
     */
    public function get_existing_img_path()
    {
        if (!$this->id) {
            return false;
        }
        if (!$this->existing_path) {
            $this->existing_path = $this->get_img_path();
        }
        return $this->existing_path;
    }
    /**
     * Returns the path to the image without file extension
     *
     * @return string path
     */
    public function get_img_path()
    {
        if (!$this->id) {
            return false;
        }
        return $this->get_img_folder() . $this->id;
    }
    /**
     * @param int $imageId
     * @param string $extension jpg | webp | png
     * @return false | string
     */
    public static function resolve_file_path($image_id, $extension)
    {
        $image_id = (int) $image_id;
        if ($image_id) {
            return static::get_img_folder_static($image_id) . $image_id . '.' . $extension;
        }
        return false;
    }
    /**
     * Returns the path to the folder containing the image in the new filesystem
     *
     * @return string path to folder
     */
    public function get_img_folder()
    {
        if (!$this->id) {
            return false;
        }
        if (!$this->folder) {
            $this->folder = Image::get_img_folder_static($this->id);
        }
        return $this->folder;
    }
    /**
     * Returns the path to the folder containing the image in the new filesystem
     *
     * @param int $idImage
     *
     * @return string path to folder
     */
    public static function get_img_folder_static($id_image)
    {
        if (!is_numeric($id_image)) {
            return false;
        }
        $folders = str_split((string) $id_image);
        return implode('/', $folders) . '/';
    }
    /**
     * Reposition image
     *
     * @param int $position Position
     * @param bool $direction Direction
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated since version 1.0.0 use Image::updatePosition() instead
     */
    public function position_image($position, $direction): void
    {
        Tools::display_as_deprecated();
        $position = (int) $position;
        $direction = (int) $direction;
        // temporary position
        $high_position = Image::get_highest_position($this->id_product) + 1;
        $conn = Db::get_instance();
        $conn->update('image', ['position' => (int) $high_position], '`id_product` = ' . (int) $this->id_product . ' AND `position` = ' . ($direction ? $position - 1 : $position + 1));
        $conn->update('image', ['position' => ['type' => 'sql', 'value' => '`position`' . ($direction ? '-1' : '+1')]], '`id_image` = ' . (int) $this->id);
        $conn->update('image', ['position' => (int) $this->position], '`id_product` = ' . (int) $this->id_product . ' AND `position` = ' . (int) $high_position);
    }
    /**
     * Change an image position and update relative positions
     *
     * @param int $way position is moved up if 0, moved down if 1
     * @param int $position new position of the moved image
     *
     * @return bool success
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_position($way, $position)
    {
        if (!isset($this->id) || !$position) {
            return false;
        }
        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $conn = Db::get_instance();
        return $conn->update('image', ['position' => ['type' => 'sql', 'value' => '`position` ' . ($way ? '- 1' : '+ 1')]], '`position` ' . ($way ? '> ' . (int) $this->position . ' AND `position` <= ' . (int) $position : '< ' . (int) $this->position . ' AND `position` >= ' . (int) $position) . ' AND `id_product`=' . (int) $this->id_product) && $conn->update('image', ['position' => (int) $position], '`id_image` = ' . (int) $this->id_image);
    }
    /**
     * @param TableSchema $table
     */
    public static function process_table_schema($table): void
    {
        if ($table->get_name_without_prefix() === 'image_shop') {
            $table->reorder_columns(['id_product', 'id_image', 'id_shop']);
        }
    }
}