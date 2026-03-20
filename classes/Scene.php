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
use Thirtybees\Core\Initialization_Callback;
/**
 * Class SceneCore
 */
class Scene_Core extends Object_Model implements Initialization_Callback
{
    /** @var string|string[] Name */
    public $name;
    /** @var bool Active Scene */
    public $active = true;
    /** @var array Zone for image map */
    public $zones = [];
    /** @var array list of category where this scene is available */
    public $categories = [];
    /** @var array Products */
    public $products;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'scene', 'primary' => 'id_scene', 'multilang' => true, 'fields' => [
        'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 100],
    ], 'keys' => ['scene_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]], 'images' => [Image_Entity::ENTITY_TYPE_SCENES => ['inputName' => 'image', 'path' => _PS_SCENE_IMG_DIR_, 'imageTypes' => [['name' => 'scene_default', 'width' => 870, 'height' => 270]]], Image_Entity::ENTITY_TYPE_SCENES_THUMB => ['inputName' => 'thumb', 'path' => _PS_SCENE_IMG_DIR_ . 'thumbs/', 'displayName' => 'Scenes Thumbnails', 'imageTypes' => [['name' => 'm_scene_default', 'width' => 161, 'height' => 58]]]]];
    /**
     * SceneCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param bool $liteResult
     * @param bool $hideScenePosition
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null, $lite_result = true, $hide_scene_position = false)
    {
        parent::__construct($id, $id_lang);
        if (!$lite_result) {
            $this->products = $this->get_products(true, (int) $id_lang, false);
        }
        if ($hide_scene_position) {
            $this->name = Scene::hide_scene_position($this->name);
        }
        $this->image_dir = _PS_SCENE_IMG_DIR_;
    }
    /**
     * Get all products of this scene
     *
     * @param bool $onlyActive
     * @param int|null $idLang
     * @param bool $liteResult
     * @return array Products
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_products($only_active = true, $id_lang = null, $lite_result = true, ?Context $context = null)
    {
        if (!Scene::is_feature_active()) {
            return [];
        }
        if (!$context) {
            $context = Context::get_context();
        }
        $id_lang = is_null($id_lang) ? $context->language->id : $id_lang;
        $products = Db::read_only()->get_array('
		SELECT s.*
		FROM `' . _DB_PREFIX_ . 'scene_products` s
		LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (p.id_product = s.id_product)
		' . Shop::add_sql_association('product', 'p') . '
		WHERE s.id_scene = ' . (int) $this->id . ($only_active ? ' AND product_shop.active = 1' : ''));
        if (!$lite_result && $products) {
            foreach ($products as &$product) {
                $product['details'] = new Product($product['id_product'], !$lite_result, $id_lang);
                if (Validate::is_loaded_object($product['details'])) {
                    $product['link'] = $context->link->get_product_link($product['details']->id, $product['details']->link_rewrite, $product['details']->category, $product['details']->ean13);
                    $cover = Product::get_cover($product['details']->id);
                    if (is_array($cover)) {
                        $product = array_merge($cover, $product);
                    }
                }
            }
        }
        return $products;
    }
    /**
     * This method is allow to know if a feature is used or active
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_feature_active()
    {
        return Configuration::get('PS_SCENE_FEATURE_ACTIVE');
    }
    /**
     * Hide scene prefix used for position
     *
     * @param string $name Scene name
     *
     * @return string Name without position
     */
    public static function hide_scene_position($name)
    {
        return preg_replace('/^[0-9]+\./', '', $name);
    }
    /**
     * Get all scenes of a category
     *
     * @param int $idCategory
     * @param int|null $idLang
     * @param bool $onlyActive
     * @param bool $liteResult
     * @param bool $hideScenePosition
     * @return array Products
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_scenes($id_category, $id_lang = null, $only_active = true, $lite_result = true, $hide_scene_position = true, ?Context $context = null)
    {
        if (!Scene::is_feature_active()) {
            return [];
        }
        $cache_key = 'Scene::getScenes' . $id_category . (int) $lite_result;
        if (!Cache::is_stored($cache_key)) {
            if (!$context) {
                $context = Context::get_context();
            }
            $id_lang = is_null($id_lang) ? $context->language->id : $id_lang;
            $sql = 'SELECT s.*
					FROM `' . _DB_PREFIX_ . 'scene_category` sc
					LEFT JOIN `' . _DB_PREFIX_ . 'scene` s ON (sc.id_scene = s.id_scene)
					' . Shop::add_sql_association('scene', 's') . '
					LEFT JOIN `' . _DB_PREFIX_ . 'scene_lang` sl ON (sl.id_scene = s.id_scene)
					WHERE sc.id_category = ' . (int) $id_category . '
						AND sl.id_lang = ' . (int) $id_lang . ($only_active ? ' AND s.active = 1' : '') . '
					ORDER BY sl.name ASC';
            $scenes = Db::read_only()->get_array($sql);
            if (!$lite_result && $scenes) {
                foreach ($scenes as &$scene) {
                    $scene = new Scene($scene['id_scene'], $id_lang, false, $hide_scene_position);
                }
            }
            Cache::store($cache_key, $scenes);
        } else {
            $scenes = Cache::retrieve($cache_key);
        }
        return $scenes;
    }
    /**
     * Get categories where scene is indexed
     *
     * @param int $idScene Scene id
     *
     * @return array Categories where scene is indexed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_indexed_categories($id_scene)
    {
        return Db::read_only()->get_array('
		SELECT `id_category`
		FROM `' . _DB_PREFIX_ . 'scene_category`
		WHERE `id_scene` = ' . (int) $id_scene);
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
        if (!$this->update_zone_products()) {
            return false;
        }
        if (!$this->update_categories()) {
            return false;
        }
        if (parent::update($null_values)) {
            // Refresh cache of feature detachable
            Configuration::update_global_value('PS_SCENE_FEATURE_ACTIVE', Scene::is_currently_used($this->def['table'], true));
            return true;
        }
        return false;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function update_zone_products()
    {
        if (!$this->delete_zone_products()) {
            return false;
        }
        if ($this->zones && !$this->add_zone_products($this->zones)) {
            return false;
        }
        return true;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete_zone_products()
    {
        return Db::get_instance()->execute('
		DELETE FROM `' . _DB_PREFIX_ . 'scene_products`
		WHERE `id_scene` = ' . (int) $this->id);
    }
    /**
     * @param array $zones
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_zone_products($zones)
    {
        $data = [];
        foreach ($zones as $zone) {
            $data[] = ['id_scene' => (int) $this->id, 'id_product' => (int) $zone['id_product'], 'x_axis' => (int) $zone['x1'], 'y_axis' => (int) $zone['y1'], 'zone_width' => (int) $zone['width'], 'zone_height' => (int) $zone['height']];
        }
        return Db::get_instance()->insert('scene_products', $data);
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function update_categories()
    {
        if (!$this->delete_categories()) {
            return false;
        }
        if (!empty($this->categories) && !$this->add_categories($this->categories)) {
            return false;
        }
        return true;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete_categories()
    {
        return Db::get_instance()->execute('
		DELETE FROM `' . _DB_PREFIX_ . 'scene_category`
		WHERE `id_scene` = ' . (int) $this->id);
    }
    /**
     * @param int[] $categories
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_categories($categories)
    {
        $data = [];
        foreach ($categories as $category) {
            $data[] = ['id_scene' => (int) $this->id, 'id_category' => (int) $category];
        }
        return Db::get_instance()->insert('scene_category', $data);
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
        if (!empty($this->zones)) {
            $this->add_zone_products($this->zones);
        }
        if (!empty($this->categories)) {
            $this->add_categories($this->categories);
        }
        if (parent::add($auto_date, $null_values)) {
            // Put cache of feature detachable only if this new scene is active else we keep the old value
            if ($this->active) {
                Configuration::update_global_value('PS_SCENE_FEATURE_ACTIVE', '1');
            }
            return true;
        }
        return false;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete()
    {
        $this->delete_zone_products();
        $this->delete_categories();
        if (parent::delete()) {
            return $this->delete_image() && Configuration::update_global_value('PS_SCENE_FEATURE_ACTIVE', Scene::is_currently_used($this->def['table'], true));
        }
        return false;
    }
    /**
     * @param bool $forceDelete
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_image($force_delete = false, $path = '')
    {
        if (file_exists($this->image_dir . 'thumbs/' . $this->id . '-m_scene_default.' . $this->image_format) && !unlink($this->image_dir . 'thumbs/' . $this->id . '-m_scene_default.' . $this->image_format)) {
            return false;
        }
        if (!$_FILES) {
            return parent::delete_image();
        }
        return true;
    }
    /**
     * Database initialization callback
     *
     * @throws PrestaShopException
     */
    public static function initialization_callback(Db $conn): void
    {
        Image_Entity::rebuild_image_entities(static::class, self::$definition['images']);
    }
}