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
use Thirtybees\Core\Initialization_Callback;
/**
 * Class CategoryCore
 */
class Category_Core extends Object_Model implements Initialization_Callback
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'category', 'primary' => 'id_category', 'multilang' => true, 'multilang_shop' => true, 'fields' => [
        'id_parent' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbNullable' => false],
        'id_shop_default' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbDefault' => '1'],
        'level_depth' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbType' => 'tinyint(3) unsigned', 'dbDefault' => '0'],
        'nleft' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbDefault' => '0'],
        'nright' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbDefault' => '0'],
        'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbDefault' => '0', 'shop' => true],
        'display_from_sub' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0', 'shop' => true],
        'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false, 'shop' => true],
        'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false, 'shop' => true],
        'position' => ['type' => self::TYPE_INT, 'dbDefault' => '0', 'shop' => true],
        'is_root_category' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => true, 'size' => 128],
        'description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => Object_Model::SIZE_TEXT],
        'additional_description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => Object_Model::SIZE_TEXT],
        'link_rewrite' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'required' => true, 'size' => 128],
        'meta_title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
        'meta_keywords' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
    ], 'keys' => ['category' => ['activenleft' => ['type' => Object_Model::KEY, 'columns' => ['active', 'nleft']], 'activenright' => ['type' => Object_Model::KEY, 'columns' => ['active', 'nright']], 'category_parent' => ['type' => Object_Model::KEY, 'columns' => ['id_parent']], 'level_depth' => ['type' => Object_Model::KEY, 'columns' => ['level_depth']], 'nleftrightactive' => ['type' => Object_Model::KEY, 'columns' => ['nleft', 'nright', 'active']], 'nright' => ['type' => Object_Model::KEY, 'columns' => ['nright']]], 'category_lang' => ['primary' => ['type' => Object_Model::PRIMARY_KEY, 'columns' => ['id_category', 'id_shop', 'id_lang']], 'category_name' => ['type' => Object_Model::KEY, 'columns' => ['name']]]], 'images' => [Image_Entity::ENTITY_TYPE_CATEGORIES => ['inputName' => 'image', 'path' => _PS_CAT_IMG_DIR_], Image_Entity::ENTITY_TYPE_CATEGORIES_THUMB => ['inputName' => 'thumb', 'displayName' => 'Categories Thumbnails', 'path' => _PS_CAT_IMG_DIR_ . 'thumb/']]];
    /**
     * @var array
     */
    protected static $_links = [];
    /** @var int category ID */
    public $id_category;
    /** @var string|string[] Name */
    public $name;
    /** @var bool Status for display */
    public $active = 1;
    /** @var bool Status for displaying subcategory products */
    public $display_from_sub = 1;
    /** @var int category position */
    public $position;
    /** @var string|string[] Description */
    public $description;
    /** @var string|string[] Additional description */
    public $additional_description;
    /** @var int Parent category ID */
    public $id_parent;
    /** @var int default Category id */
    public $id_category_default;
    /** @var int Parents number */
    public $level_depth;
    /** @var int Nested tree model "left" value */
    public $nleft;
    /** @var int Nested tree model "right" value */
    public $nright;
    /** @var string|string[] string used in rewrited URL */
    public $link_rewrite;
    /** @var string|string[] Meta title */
    public $meta_title;
    /** @var string|string[] Meta keywords */
    public $meta_keywords;
    /** @var string|string[] Meta description */
    public $meta_description;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var bool is Category Root */
    public $is_root_category;
    /** @var int */
    public $id_shop_default;
    /**
     * @var array
     */
    public $group_box;
    /** @var string id_image is the category ID when an image exists and false otherwise */
    public $id_image = false;
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectsNodeName' => 'categories', 'hidden_fields' => ['nleft', 'nright', 'groupBox'], 'fields' => ['id_parent' => ['xlink_resource' => 'categories'], 'level_depth' => ['setter' => false], 'nb_products_recursive' => ['getter' => 'getWsNbProductsRecursive', 'setter' => false]], 'associations' => ['categories' => ['getter' => 'getChildrenWs', 'resource' => 'category'], 'products' => ['getter' => 'getProductsWs', 'resource' => 'product']]];
    /**
     * CategoryCore constructor.
     *
     * @param int|null $idCategory
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @throws PrestaShopException
     */
    public function __construct($id_category = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id_category, $id_lang, $id_shop);
        if ($this->id && Image_Manager::get_source_image(_PS_CAT_IMG_DIR_, $this->id)) {
            $this->id_image = $this->id;
        }
        $this->image_dir = _PS_CAT_IMG_DIR_;
    }
    /**
     * @param array[] $categories
     * @param array $current
     * @param int|null $idCategory
     * @param int $idSelected
     *
     * @throws PrestaShopException
     */
    public static function recurse_category($categories, $current, $id_category = null, $id_selected = 1): void
    {
        if (!$id_category) {
            $id_category = (int) Configuration::get('PS_ROOT_CATEGORY');
        }
        echo '<option value="' . $id_category . '"' . ($id_selected == $id_category ? ' selected="selected"' : '') . '>' . str_repeat('&nbsp;', $current['infos']['level_depth'] * 5) . stripslashes((string) $current['infos']['name']) . '</option>';
        if (isset($categories[$id_category])) {
            foreach (array_keys($categories[$id_category]) as $key) {
                Category::recurse_category($categories, $categories[$id_category][$key], $key, $id_selected);
            }
        }
    }
    /**
     * Return available categories
     *
     * @param bool $idLang Language ID
     * @param bool $active return only active categories
     *
     * @param bool $order
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array Categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_categories($id_lang = false, $active = true, $order = true, $sql_filter = '', $sql_sort = '', $sql_limit = '')
    {
        $result = Db::read_only()->get_array('
			SELECT *
			FROM `' . _DB_PREFIX_ . 'category` c
			' . Shop::add_sql_association('category', 'c') . '
			LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON c.`id_category` = cl.`id_category`' . Shop::add_sql_restriction_on_lang('cl') . '
			WHERE 1 ' . $sql_filter . ' ' . ($id_lang ? 'AND `id_lang` = ' . (int) $id_lang : '') . '
			' . static::get_active_column_condition($active, true) . '
			' . (!$id_lang ? 'GROUP BY c.id_category' : '') . '
			' . ($sql_sort != '' ? $sql_sort : 'ORDER BY c.`level_depth` ASC, category_shop.`position` ASC') . '
			' . ($sql_limit != '' ? $sql_limit : ''));
        if (!$order) {
            return $result;
        }
        $categories = [];
        foreach ($result as $row) {
            $categories[$row['id_parent']][$row['id_category']]['infos'] = $row;
        }
        return $categories;
    }
    /**
     * Helper method to return active column condition
     *
     * @param bool $active
     * @param bool $useShopRestriction
     * @return string
     */
    protected static function get_active_column_condition($active, $use_shop_restriction)
    {
        if (!$active) {
            return '';
        }
        if ($use_shop_restriction) {
            return 'AND category_shop.`active` = 1';
        }
        return 'AND EXISTS(SELECT 1 FROM ' . _DB_PREFIX_ . 'category_shop cs WHERE cs.id_category = c.id_category AND cs.active = 1)';
    }
    /**
     * @param int|null $rootCategory
     * @param bool $idLang
     * @param bool $active
     * @param array|null $groups
     * @param bool $useShopRestriction
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_all_categories_name($root_category = null, $id_lang = false, $active = true, $groups = null, $use_shop_restriction = true, $sql_filter = '', $sql_sort = '', $sql_limit = '')
    {
        if (isset($groups) && Group::is_feature_active() && !is_array($groups)) {
            $groups = (array) $groups;
        }
        $cache_id = 'Category::getAllCategoriesName_' . md5((int) $root_category . (int) $id_lang . (int) $active . (int) $use_shop_restriction . (isset($groups) && Group::is_feature_active() ? implode('', $groups) : ''));
        if (!Cache::is_stored($cache_id)) {
            $result = Db::read_only()->get_array('
				SELECT c.id_category, cl.name
				FROM `' . _DB_PREFIX_ . 'category` c
				' . ($use_shop_restriction ? Shop::add_sql_association('category', 'c') : '') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON c.`id_category` = cl.`id_category`' . Shop::add_sql_restriction_on_lang('cl') . '
				' . (isset($groups) && Group::is_feature_active() ? 'LEFT JOIN `' . _DB_PREFIX_ . 'category_group` cg ON c.`id_category` = cg.`id_category`' : '') . '
				' . (isset($root_category) ? 'RIGHT JOIN `' . _DB_PREFIX_ . 'category` c2 ON c2.`id_category` = ' . (int) $root_category . ' AND c.`nleft` >= c2.`nleft` AND c.`nright` <= c2.`nright`' : '') . '
				WHERE ' . ($sql_filter ?: '1') . ' ' . ($id_lang ? 'AND `id_lang` = ' . (int) $id_lang : '') . '
				' . static::get_active_column_condition($active, $use_shop_restriction) . '
				' . (isset($groups) && Group::is_feature_active() ? ' AND cg.`id_group` IN (' . implode(',', $groups) . ')' : '') . '
				' . (!$id_lang || isset($groups) && Group::is_feature_active() ? ' GROUP BY c.`id_category`' : '') . '
				' . ($sql_sort != '' ? $sql_sort : ' ORDER BY c.`level_depth` ASC') . '
				' . ($sql_sort == '' && $use_shop_restriction ? ', category_shop.`position` ASC' : '') . '
				' . ($sql_limit != '' ? $sql_limit : ''));
            Cache::store($cache_id, $result);
        } else {
            $result = Cache::retrieve($cache_id);
        }
        return $result;
    }
    /**
     * @param int|null $rootCategory
     * @param bool $idLang
     * @param bool $active
     * @param array|null $groups
     * @param bool $useShopRestriction
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_nested_categories($root_category = null, $id_lang = false, $active = true, $groups = null, $use_shop_restriction = true, $sql_filter = '', $sql_sort = '', $sql_limit = '')
    {
        if (isset($groups) && Group::is_feature_active() && !is_array($groups)) {
            $groups = (array) $groups;
        }
        $cache_id = 'Category::getNestedCategories_' . md5((int) $root_category . (int) $id_lang . (int) $active . (int) $use_shop_restriction . (isset($groups) && Group::is_feature_active() ? implode('', $groups) : ''));
        if (!Cache::is_stored($cache_id)) {
            $result = Db::read_only()->get_array('
				SELECT c.*, cl.*
				FROM `' . _DB_PREFIX_ . 'category` c
				' . ($use_shop_restriction ? Shop::add_sql_association('category', 'c') : '') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON c.`id_category` = cl.`id_category`' . Shop::add_sql_restriction_on_lang('cl') . '
				' . (isset($groups) && Group::is_feature_active() ? 'LEFT JOIN `' . _DB_PREFIX_ . 'category_group` cg ON c.`id_category` = cg.`id_category`' : '') . '
				' . (isset($root_category) ? 'RIGHT JOIN `' . _DB_PREFIX_ . 'category` c2 ON c2.`id_category` = ' . (int) $root_category . ' AND c.`nleft` >= c2.`nleft` AND c.`nright` <= c2.`nright`' : '') . '
				WHERE 1 ' . $sql_filter . ' ' . ($id_lang ? 'AND `id_lang` = ' . (int) $id_lang : '') . '
				' . static::get_active_column_condition($active, $use_shop_restriction) . '
				' . (isset($groups) && Group::is_feature_active() ? ' AND cg.`id_group` IN (' . implode(',', $groups) . ')' : '') . '
				' . (!$id_lang || isset($groups) && Group::is_feature_active() ? ' GROUP BY c.`id_category`' : '') . '
				' . ($sql_sort != '' ? $sql_sort : ' ORDER BY c.`level_depth` ASC') . '
				' . ($sql_sort == '' && $use_shop_restriction ? ', category_shop.`position` ASC' : '') . '
				' . ($sql_limit != '' ? $sql_limit : ''));
            $categories = [];
            $buff = [];
            if (!isset($root_category)) {
                $root_category = Category::get_root_category()->id;
            }
            foreach ($result as $row) {
                $current =& $buff[$row['id_category']];
                $current = $row;
                if ($row['id_category'] == $root_category) {
                    $categories[$row['id_category']] =& $current;
                } else {
                    $buff[$row['id_parent']]['children'][$row['id_category']] =& $current;
                }
            }
            Cache::store($cache_id, $categories);
        } else {
            $categories = Cache::retrieve($cache_id);
        }
        return $categories;
    }
    /**
     * @param int|null $idLang
     *
     * @return Category
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_root_category($id_lang = null, ?Shop $shop = null)
    {
        $context = Context::get_context();
        if (is_null($id_lang)) {
            $id_lang = $context->language->id;
        }
        if (!$shop) {
            if (Shop::is_feature_active() && Shop::get_context() != Shop::CONTEXT_SHOP) {
                $shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
            } else {
                $shop = $context->shop;
            }
        } else {
            return new Category($shop->get_category(), $id_lang);
        }
        $is_more_than_one_root_category = count(Category::get_categories_without_parent()) > 1;
        if (Shop::is_feature_active() && $is_more_than_one_root_category && Shop::get_context() != Shop::CONTEXT_SHOP) {
            return Category::get_top_category($id_lang);
        }
        return new Category($shop->get_category(), $id_lang);
    }
    /**
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_categories_without_parent()
    {
        $cache_id = 'Category::getCategoriesWithoutParent_' . (int) Context::get_context()->language->id;
        if (!Cache::is_stored($cache_id)) {
            $result = Db::read_only()->get_array('
			SELECT DISTINCT c.*
			FROM `' . _DB_PREFIX_ . 'category` c
			LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (c.`id_category` = cl.`id_category` AND cl.`id_lang` = ' . (int) Context::get_context()->language->id . ')
			WHERE `level_depth` = 1');
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * @param int|null $idLang
     *
     * @return Category
     *
     * @throws PrestaShopException
     */
    public static function get_top_category($id_lang = null)
    {
        if (is_null($id_lang)) {
            $id_lang = (int) Context::get_context()->language->id;
        }
        $cache_id = 'Category::getTopCategory_' . (int) $id_lang;
        if (!Cache::is_stored($cache_id)) {
            $id_category = (int) Db::read_only()->get_value((new Db_Query())->select('`id_category`')->from('category')->where('`id_parent` = 0'));
            $category = new Category($id_category, $id_lang);
            Cache::store($cache_id, $category);
            return $category;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_simple_categories($id_lang)
    {
        return Db::read_only()->get_array((new Db_Query())->select('c.`id_category`, cl.`name`')->from('category', 'c')->left_join('category_lang', 'cl', 'c.`id_category` = cl.`id_category` ' . Shop::add_sql_restriction_on_lang('cl'))->join(Shop::add_sql_association('category', 'c'))->where('cl.`id_lang` = ' . (int) $id_lang)->where('c.`id_category` != ' . Configuration::get('PS_ROOT_CATEGORY'))->group_by('c.`id_category`')->order_by('c.`id_category`, category_shop.`position`'));
    }
    /**
     * Return main categories
     *
     * @param int $idLang Language ID
     * @param bool $active return only active categories
     *
     * @param bool $idShop
     *
     * @return array categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_home_categories($id_lang, $active = true, $id_shop = false)
    {
        return static::get_children(Configuration::get('PS_HOME_CATEGORY'), $id_lang, $active, $id_shop);
    }
    /**
     * @param int $idParent
     * @param int $idLang
     * @param bool $active
     * @param bool $idShop
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_children($id_parent, $id_lang, $active = true, $id_shop = false)
    {
        $cache_id = 'Category::getChildren_' . (int) $id_parent . '-' . (int) $id_lang . '-' . (bool) $active . '-' . (int) $id_shop;
        if (!Cache::is_stored($cache_id)) {
            $query = 'SELECT c.`id_category`, cl.`name`, cl.`link_rewrite`, category_shop.`id_shop`
			FROM `' . _DB_PREFIX_ . 'category` c
			LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (c.`id_category` = cl.`id_category`' . Shop::add_sql_restriction_on_lang('cl') . ')
			' . Shop::add_sql_association('category', 'c') . '
			WHERE `id_lang` = ' . (int) $id_lang . '
			AND c.`id_parent` = ' . (int) $id_parent . '
			' . static::get_active_column_condition($active, true) . '
			GROUP BY c.`id_category`
			ORDER BY category_shop.`position` ASC';
            $result = Db::read_only()->get_array($query);
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * @param int $idParent
     * @param int $idLang
     * @param bool $active
     * @param bool $idShop
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function has_children($id_parent, $id_lang, $active = true, $id_shop = false)
    {
        $cache_id = 'Category::hasChildren_' . (int) $id_parent . '-' . (int) $id_lang . '-' . (bool) $active . '-' . (int) $id_shop;
        if (!Cache::is_stored($cache_id)) {
            $query = 'SELECT c.id_category, "" AS name
			FROM `' . _DB_PREFIX_ . 'category` c
			LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (c.`id_category` = cl.`id_category`' . Shop::add_sql_restriction_on_lang('cl') . ')
			' . Shop::add_sql_association('category', 'c') . '
			WHERE `id_lang` = ' . (int) $id_lang . '
			AND c.`id_parent` = ' . (int) $id_parent . '
			' . static::get_active_column_condition($active, true) . '
			LIMIT 1';
            $result = Db::read_only()->get_array($query);
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * This method allow to return children categories with the number of sub children selected for a product
     *
     * @param int $idParent
     * @param array $selectedCat
     * @param int $idLang
     * @param bool $useShopContext
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_children_with_nb_selected_sub_cat($id_parent, $selected_cat, $id_lang, ?Shop $shop = null, $use_shop_context = true)
    {
        if (!$shop) {
            $shop = Context::get_context()->shop;
        }
        $id_shop = $shop->id ?: Configuration::get('PS_SHOP_DEFAULT');
        $selected_cat = explode(',', str_replace(' ', '', $selected_cat));
        $sql = '
		SELECT c.`id_category`, c.`level_depth`, cl.`name`,
		IF((
			SELECT COUNT(*)
			FROM `' . _DB_PREFIX_ . 'category` c2
			WHERE c2.`id_parent` = c.`id_category`
		) > 0, 1, 0) AS has_children,
		' . ($selected_cat ? '(
			SELECT count(c3.`id_category`)
			FROM `' . _DB_PREFIX_ . 'category` c3
			WHERE c3.`nleft` > c.`nleft`
			AND c3.`nright` < c.`nright`
			AND c3.`id_category`  IN (' . implode(',', array_map(intval(...), $selected_cat)) . ')
		)' : '0') . ' AS nbSelectedSubCat
		FROM `' . _DB_PREFIX_ . 'category` c
		LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (c.`id_category` = cl.`id_category` ' . Shop::add_sql_restriction_on_lang('cl', $id_shop) . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'category_shop` cs ON (c.`id_category` = cs.`id_category` AND cs.`id_shop` = ' . (int) $id_shop . ')
		WHERE `id_lang` = ' . (int) $id_lang . '
		AND c.`id_parent` = ' . (int) $id_parent;
        if (Shop::get_context() == Shop::CONTEXT_SHOP && $use_shop_context) {
            $sql .= ' AND cs.`id_shop` = ' . (int) $shop->id;
        }
        if (!Shop::is_feature_active() || Shop::get_context() == Shop::CONTEXT_SHOP && $use_shop_context) {
            $sql .= ' ORDER BY cs.`position` ASC';
        }
        return Db::read_only()->get_array($sql);
    }
    /**
     * Copy products from a category to another
     *
     * @param int $idOld Source category ID
     * @param bool $idNew Destination category ID
     *
     * @return bool Duplication result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function duplicate_product_categories($id_old, $id_new)
    {
        $sql = 'SELECT `id_category`
				FROM `' . _DB_PREFIX_ . 'category_product`
				WHERE `id_product` = ' . (int) $id_old;
        $result = Db::read_only()->get_array($sql);
        if ($result) {
            $row = [];
            foreach ($result as $i) {
                $row[] = '(' . implode(', ', [(int) $id_new, $i['id_category'], '(SELECT tmp.max + 1 FROM (
					SELECT MAX(cp.`position`) AS max
					FROM `' . _DB_PREFIX_ . 'category_product` cp
					WHERE cp.`id_category`=' . (int) $i['id_category'] . ') AS tmp)']) . ')';
            }
            return Db::get_instance()->execute('
                INSERT IGNORE INTO `' . _DB_PREFIX_ . 'category_product` (`id_product`, `id_category`, `position`)
                VALUES ' . implode(',', $row));
        }
        return true;
    }
    /**
     * Check if category can be moved in another one.
     * The category cannot be moved in a child category.
     *
     * @param int $idCategory current category
     * @param int $idParent Parent candidate
     *
     * @return bool Parent validity
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function check_before_move($id_category, $id_parent)
    {
        if ($id_category == $id_parent) {
            return false;
        }
        if ($id_parent == Configuration::get('PS_HOME_CATEGORY')) {
            return true;
        }
        $i = (int) $id_parent;
        while (42) {
            $result = Db::read_only()->get_row('SELECT `id_parent` FROM `' . _DB_PREFIX_ . 'category` WHERE `id_category` = ' . (int) $i);
            if (!isset($result['id_parent'])) {
                return false;
            }
            if ($result['id_parent'] == $id_category) {
                return false;
            }
            if ($result['id_parent'] == Configuration::get('PS_HOME_CATEGORY')) {
                return true;
            }
            $i = $result['id_parent'];
        }
    }
    /**
     * @param int $idCategory
     * @param int $idLang
     *
     * @return bool|mixed
     *
     * @throws PrestaShopException
     */
    public static function get_link_rewrite($id_category, $id_lang)
    {
        if (!Validate::is_unsigned_id($id_category) || !Validate::is_unsigned_id($id_lang)) {
            return false;
        }
        if (!isset(static::$_links[$id_category . '-' . $id_lang])) {
            static::$_links[$id_category . '-' . $id_lang] = Db::read_only()->get_value('
			SELECT cl.`link_rewrite`
			FROM `' . _DB_PREFIX_ . 'category_lang` cl
			WHERE `id_lang` = ' . (int) $id_lang . '
			' . Shop::add_sql_restriction_on_lang('cl') . '
			AND cl.`id_category` = ' . (int) $id_category);
        }
        return static::$_links[$id_category . '-' . $id_lang];
    }
    /**
     * Search with Pathes for categories
     *
     * @param int $idLang Language ID
     * @param string $path of category
     * @param bool $objectToCreate a category
     *                                 * @param bool $methodToCreate a category
     *
     * @return array Corresponding categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function search_by_path($id_lang, $path, $object_to_create = false, $method_to_create = false)
    {
        $categories = explode('/', trim($path));
        $category = $id_parent_category = false;
        if (count($categories)) {
            foreach ($categories as $category_name) {
                if ($id_parent_category) {
                    $category = Category::search_by_name_and_parent_category_id($id_lang, $category_name, $id_parent_category);
                } else {
                    $category = Category::search_by_name($id_lang, $category_name, true, true);
                }
                if (!$category && $object_to_create && $method_to_create) {
                    call_user_func_array([$object_to_create, $method_to_create], [$id_lang, $category_name, $id_parent_category]);
                    $category = Category::search_by_path($id_lang, $category_name);
                }
                if (isset($category['id_category']) && $category['id_category']) {
                    $id_parent_category = (int) $category['id_category'];
                }
            }
        }
        return $category;
    }
    /**
     * Retrieve category by name and parent category id
     *
     * @param int $idLang Language ID
     * @param string $categoryName Searched category name
     * @param int $idParentCategory parent category ID
     *
     * @return array|false Corresponding category
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function search_by_name_and_parent_category_id($id_lang, $category_name, $id_parent_category)
    {
        return Db::read_only()->get_row('
		SELECT c.*, cl.*
		FROM `' . _DB_PREFIX_ . 'category` c
		LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
			ON (c.`id_category` = cl.`id_category`
			AND `id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('cl') . ')
		WHERE `name` = \'' . p_sql($category_name) . '\'
			AND c.`id_category` != ' . (int) Configuration::get('PS_HOME_CATEGORY') . '
			AND c.`id_parent` = ' . (int) $id_parent_category);
    }
    /**
     * Light back office search for categories
     *
     * @param int $idLang Language ID
     * @param string $query Searched string
     * @param bool $unrestricted allows search without lang and includes first category and exact match
     * @param bool $skipCache
     *
     * @return array Corresponding categories
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public static function search_by_name($id_lang, $query, $unrestricted = false, $skip_cache = false)
    {
        if ($unrestricted === true) {
            $key = 'Category::searchByName_' . $query;
            if ($skip_cache || !Cache::is_stored($key)) {
                $categories = Db::read_only()->get_row((new Db_Query())->select('c.*, cl.*')->from('category', 'c')->left_join('category_lang', 'cl', 'c.`id_category` = cl.`id_category` ' . Shop::add_sql_restriction_on_lang('cl'))->where('`name` = \'' . p_sql($query) . '\''));
                if (!$skip_cache) {
                    Cache::store($key, $categories);
                }
                return $categories;
            }
            return Cache::retrieve($key);
        }
        return Db::read_only()->get_array((new Db_Query())->select('c.*, cl.*')->from('category', 'c')->left_join('category_lang', 'cl', 'c.`id_category` = cl.`id_category` AND `id_lang` = ' . (int) $id_lang . ' ' . Shop::add_sql_restriction_on_lang('cl'))->where('`name` LIKE \'%' . p_sql($query) . '%\'')->where('c.`id_category` != ' . (int) Configuration::get('PS_HOME_CATEGORY')));
    }
    /**
     * Specify if a category already in base
     *
     * @param int $idCategory Category id
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function category_exists($id_category)
    {
        $row = Db::read_only()->get_row((new Db_Query())->select('`id_category`')->from('category', 'c')->where('c.`id_category` = ' . (int) $id_category));
        return isset($row['id_category']);
    }
    /**
     * @param int $idGroup
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function set_new_group_for_home($id_group)
    {
        if (!(int) $id_group) {
            return false;
        }
        try {
            return Db::get_instance()->insert('category_group', ['id_category' => (int) Context::get_context()->shop->get_category(), 'id_group' => (int) $id_group]);
        } catch (Presta_Shop_Database_Exception) {
            return false;
        }
    }
    /**
     * @param int $idCategory
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_url_rewrite_informations($id_category)
    {
        return Db::read_only()->get_array((new Db_Query())->select('l.`id_lang`, c.`link_rewrite`')->from('category_lang', 'c')->left_join('lang', 'l', 'c.`id_lang` = l.`id_lang`')->where('c.`id_category` = ' . (int) $id_category)->where('l.`active` = 1')->add_current_shop_restriction('c'));
    }
    /**
     * @param int $idCategory
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function in_shop_static($id_category, ?Shop $shop = null)
    {
        if (!$shop || !is_object($shop)) {
            $shop = Context::get_context()->shop;
        }
        if (!$interval = Category::get_interval($shop->get_category())) {
            return false;
        }
        $row = Db::read_only()->get_row((new Db_Query())->select('`nleft`, `nright`')->from('category')->where('`id_category` = ' . (int) $id_category));
        if (!$row) {
            return false;
        }
        return $row['nleft'] >= $interval['nleft'] && $row['nright'] <= $interval['nright'];
    }
    /**
     * Return nleft and nright fields for a given category
     *
     * @param int $id
     *
     * @return array | false
     *
     * @throws PrestaShopException
     */
    public static function get_interval($id)
    {
        $cache_id = 'Category::getInterval_' . (int) $id;
        if (!Cache::is_stored($cache_id)) {
            $result = Db::read_only()->get_row((new Db_Query())->select('`nleft`, `nright`, `level_depth`')->from('category')->where('`id_category` = ' . (int) $id));
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * @param array $idsCategory
     * @param int $idLang
     *
     * @return array|false
     *
     * @throws PrestaShopException
     */
    public static function get_category_informations($ids_category, $id_lang = null)
    {
        if ($id_lang === null) {
            $id_lang = Context::get_context()->language->id;
        }
        if (!is_array($ids_category) || !count($ids_category)) {
            return false;
        }
        $categories = [];
        $results = Db::read_only()->get_array((new Db_Query())->select('c.`id_category`, cl.`name`, cl.`link_rewrite`, cl.`id_lang`')->from('category', 'c')->left_join('category_lang', 'cl', 'c.`id_category` = cl.`id_category` ' . Shop::add_sql_restriction_on_lang('cl'))->where('cl.`id_lang` = ' . (int) $id_lang)->where('c.`id_category` IN (' . implode(',', array_map(intval(...), $ids_category)) . ')'));
        foreach ($results as $category) {
            $categories[$category['id_category']] = $category;
        }
        return $categories;
    }
    /**
     * @param int|null $idLang
     * @param bool $active
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function get_root_categories($id_lang = null, $active = true)
    {
        if (!$id_lang) {
            $id_lang = Context::get_context()->language->id;
        }
        return Db::read_only()->get_array((new Db_Query())->select('DISTINCT(c.`id_category`), cl.`name`')->from('category', 'c')->left_join('category_lang', 'cl', 'cl.`id_category` = c.`id_category` AND cl.`id_lang`=' . (int) $id_lang)->where('`is_root_category` = 1 ' . static::get_active_column_condition($active, false)));
    }
    /**
     * @param int $idCategory
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function get_shops_by_category($id_category)
    {
        return Db::read_only()->get_array((new Db_Query())->select('`id_shop`')->from('category_shop')->where('`id_category` = ' . (int) $id_category));
    }
    /**
     * Update categories for a shop
     *
     * @param array $categories Categories list to associate a shop
     * @param int $idShop Categories list to associate a shop
     *
     * @return bool|false Update/insertion result
     *
     * @throws PrestaShopException
     */
    public static function update_from_shop($categories, $id_shop)
    {
        $shop = new Shop($id_shop);
        // if array is empty or if the default category is not selected, return false
        if (!is_array($categories) || !count($categories) || !in_array($shop->id_category, $categories)) {
            return false;
        }
        // delete categories for this shop
        Category::delete_categories_from_shop($id_shop);
        // and add $categories to this shop
        return Category::add_to_shop($categories, $id_shop);
    }
    /**
     * Delete every categories
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function delete_categories_from_shop($id_shop)
    {
        return Db::get_instance()->delete('category_shop', 'id_shop = ' . (int) $id_shop);
    }
    /**
     * Add some categories to a shop
     *
     * @param int $idShop
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function add_to_shop(array $categories, $id_shop)
    {
        $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'category_shop` (`id_category`, `id_shop`) VALUES';
        $tab_categories = [];
        foreach ($categories as $id_category) {
            $tab_categories[] = new Category($id_category);
            $sql .= '("' . (int) $id_category . '", "' . (int) $id_shop . '"),';
        }
        // removing last comma to avoid SQL error
        $sql = substr($sql, 0, strlen($sql) - 1);
        $return = Db::get_instance()->execute($sql);
        // we have to update position for every new entries
        foreach ($tab_categories as $category) {
            /** @var Category $category */
            $category->add_position(Category::get_last_position($category->id_parent, $id_shop), $id_shop);
        }
        return $return;
    }
    /**
     * @param int $position
     * @param int|null $idShop
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add_position($position, $id_shop = null)
    {
        $return = true;
        $conn = Db::get_instance();
        if (is_null($id_shop)) {
            if (Shop::get_context() != Shop::CONTEXT_SHOP) {
                foreach (Shop::get_context_list_shop_id() as $id_shop) {
                    $return = $conn->execute('
						INSERT INTO `' . _DB_PREFIX_ . 'category_shop` (`id_category`, `id_shop`, `position`) VALUES
						(' . (int) $this->id . ', ' . (int) $id_shop . ', ' . (int) $position . ')
						ON DUPLICATE KEY UPDATE `position` = ' . (int) $position) && $return;
                }
            } else {
                $id = Context::get_context()->shop->id;
                $id_shop = $id ?: Configuration::get('PS_SHOP_DEFAULT');
                $return = $conn->execute('
					INSERT INTO `' . _DB_PREFIX_ . 'category_shop` (`id_category`, `id_shop`, `position`) VALUES
					(' . (int) $this->id . ', ' . (int) $id_shop . ', ' . (int) $position . ')
					ON DUPLICATE KEY UPDATE `position` = ' . (int) $position);
            }
        } else {
            $return = $conn->execute('
			INSERT INTO `' . _DB_PREFIX_ . 'category_shop` (`id_category`, `id_shop`, `position`) VALUES
			(' . (int) $this->id . ', ' . (int) $id_shop . ', ' . (int) $position . ')
			ON DUPLICATE KEY UPDATE `position` = ' . (int) $position);
        }
        return $return;
    }
    /** this function return the number of category + 1 having $id_category_parent as parent.
     *
     * @todo    rename that function to make it understandable (getNewLastPosition for example)
     *
     * @param int $idCategoryParent the parent category
     * @param int $idShop
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_last_position($id_category_parent, $id_shop)
    {
        if ((int) Db::read_only()->get_value('
				SELECT COUNT(c.`id_category`)
				FROM `' . _DB_PREFIX_ . 'category` c
				LEFT JOIN `' . _DB_PREFIX_ . 'category_shop` cs
				ON (c.`id_category` = cs.`id_category` AND cs.`id_shop` = ' . (int) $id_shop . ')
				WHERE c.`id_parent` = ' . (int) $id_category_parent) === 1) {
            return 0;
        }
        return 1 + (int) Db::read_only()->get_value('
				SELECT MAX(cs.`position`)
				FROM `' . _DB_PREFIX_ . 'category` c
				LEFT JOIN `' . _DB_PREFIX_ . 'category_shop` cs
				ON (c.`id_category` = cs.`id_category` AND cs.`id_shop` = ' . (int) $id_shop . ')
				WHERE c.`id_parent` = ' . (int) $id_category_parent);
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
        if (!isset($this->level_depth)) {
            $this->level_depth = $this->calc_level_depth();
        }
        if ($this->is_root_category && $id_root_category = (int) Configuration::get('PS_ROOT_CATEGORY')) {
            $this->id_parent = $id_root_category;
        }
        $ret = parent::add($auto_date, $null_values);
        if (Tools::is_submit('checkBoxShopAsso_category')) {
            foreach (Tools::get_array_value('checkBoxShopAsso_category') as $id_shop => $value) {
                $position = (int) Category::get_last_position((int) $this->id_parent, $id_shop);
                $this->add_position($position, $id_shop);
            }
        } else {
            foreach (Shop::get_shops(true) as $shop) {
                $position = (int) Category::get_last_position((int) $this->id_parent, $shop['id_shop']);
                $this->add_position($position, $shop['id_shop']);
            }
        }
        if (!isset($this->do_not_regenerate_n_tree) || !$this->do_not_regenerate_n_tree) {
            Category::regenerate_entire_ntree();
        }
        // Update group selection, if provided
        if (is_array($this->group_box)) {
            $this->update_group($this->group_box);
        }
        Hook::trigger_event('actionCategoryAdd', ['category' => $this]);
        return $ret;
    }
    /**
     * Get the depth level for the category
     *
     * @return int Depth level
     *
     * @throws PrestaShopException
     */
    public function calc_level_depth()
    {
        /* Root category */
        if (!$this->id_parent) {
            return 0;
        }
        $parent_category = new Category((int) $this->id_parent);
        if (!Validate::is_loaded_object($parent_category)) {
            throw new Presta_Shop_Exception('Parent category does not exist');
        }
        return $parent_category->level_depth + 1;
    }
    /**
     * Re-calculate the values of all branches of the nested tree
     *
     * @throws PrestaShopException
     */
    public static function regenerate_entire_ntree(): void
    {
        $id = Context::get_context()->shop->id;
        $id_shop = $id ?: Configuration::get('PS_SHOP_DEFAULT');
        $categories = Db::read_only()->get_array((new Db_Query())->select('c.`id_category`, c.`id_parent`')->from('category', 'c')->left_join('category_shop', 'cs', 'c.`id_category` = cs.`id_category` AND cs.`id_shop` = ' . (int) $id_shop)->order_by('c.`id_parent`, cs.`position` ASC'));
        $categories_array = [];
        foreach ($categories as $category) {
            $categories_array[$category['id_parent']]['subcategories'][] = $category['id_category'];
        }
        $n = 1;
        if (isset($categories_array[0]) && $categories_array[0]['subcategories']) {
            Category::_sub_tree($categories_array, $categories_array[0]['subcategories'][0], $n);
        }
    }
    /**
     * @param array $categories
     * @param int $idCategory
     * @param int $n
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    protected static function _sub_tree(&$categories, $id_category, &$n)
    {
        return static::sub_tree($categories, $id_category, $n);
    }
    /**
     * @param array $categories
     * @param int $idCategory
     * @param int $n
     *
     * @throws PrestaShopException
     */
    protected static function sub_tree(&$categories, $id_category, &$n)
    {
        $left = $n++;
        if (isset($categories[(int) $id_category]['subcategories'])) {
            foreach ($categories[(int) $id_category]['subcategories'] as $id_subcategory) {
                Category::_sub_tree($categories, (int) $id_subcategory, $n);
            }
        }
        $right = (int) $n++;
        Db::get_instance()->execute('
		UPDATE ' . _DB_PREFIX_ . 'category
		SET nleft = ' . (int) $left . ', nright = ' . $right . '
		WHERE id_category = ' . (int) $id_category . ' LIMIT 1');
    }
    /**
     * Update customer groups associated to the object
     *
     * @param int[] $groupIds List of group IDs
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_group($group_ids): void
    {
        $this->clean_groups();
        if (is_array($group_ids)) {
            $this->add_groups($group_ids);
        }
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function clean_groups()
    {
        $category_id = (int) $this->id;
        Cache::clean('Category::getGroups_' . $category_id);
        return Db::get_instance()->delete('category_group', 'id_category = ' . $category_id);
    }
    /**
     * @param array $groups
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_groups($groups)
    {
        $category_id = (int) $this->id;
        $result = true;
        foreach ($groups as $group_id) {
            $group_id = (int) $group_id;
            if ($group_id) {
                $result = Db::get_instance()->insert('category_group', ['id_category' => $category_id, 'id_group' => $group_id]) && $result;
            }
        }
        Cache::clean('Category::getGroups_' . $category_id);
        return $result;
    }
    /**
     * update category positions in parent
     *
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        if ($this->id_parent == $this->id) {
            throw new Presta_Shop_Exception('a category cannot be its own parent');
        }
        if (Page_Cache::is_enabled()) {
            Page_Cache::invalidate_entity('category', $this->id);
        }
        if ($this->is_root_category && $this->id_parent != (int) Configuration::get('PS_ROOT_CATEGORY')) {
            $this->is_root_category = 0;
        }
        // Update group selection, if provided
        if (is_array($this->group_box)) {
            $this->update_group($this->group_box);
        }
        if ($this->level_depth != $this->calc_level_depth()) {
            $this->level_depth = $this->calc_level_depth();
            $changed = true;
        }
        // If the parent category was changed, we don't want to have 2 categories with the same position
        if (!isset($changed)) {
            $changed = $this->get_duplicate_position();
        }
        if ($changed) {
            if (Tools::is_submit('checkBoxShopAsso_category')) {
                foreach (Tools::get_array_value('checkBoxShopAsso_category') as $id_shop => $value) {
                    $this->add_position((int) Category::get_last_position((int) $this->id_parent, (int) $id_shop), (int) $id_shop);
                }
            } else {
                foreach (Shop::get_shops(true) as $shop) {
                    $this->add_position((int) Category::get_last_position((int) $this->id_parent, $shop['id_shop']), $shop['id_shop']);
                }
            }
        }
        $ret = parent::update($null_values);
        if ($changed && (!isset($this->do_not_regenerate_n_tree) || !$this->do_not_regenerate_n_tree)) {
            static::clean_positions((int) $this->id_parent);
            Category::regenerate_entire_ntree();
            $this->recalculate_level_depth($this->id);
        }
        Hook::trigger_event('actionCategoryUpdate', ['category' => $this]);
        return $ret;
    }
    /**
     * Search for another category with the same parent and the same position
     *
     * @return false|null|string first category found
     *
     * @throws PrestaShopException
     */
    public function get_duplicate_position()
    {
        return Db::read_only()->get_value('
		SELECT c.`id_category`
		FROM `' . _DB_PREFIX_ . 'category` c
		' . Shop::add_sql_association('category', 'c') . '
		WHERE c.`id_parent` = ' . (int) $this->id_parent . '
		AND category_shop.`position` = ' . (int) $this->position . '
		AND c.`id_category` != ' . (int) $this->id);
    }
    /**
     * cleanPositions keep order of category in $id_category_parent,
     * but remove duplicate position. Should not be used if positions
     * are clean at the beginning !
     *
     * @param int|null $idCategoryParent
     *
     * @return bool true if succeed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function clean_positions($id_category_parent = null)
    {
        if ($id_category_parent === null) {
            return false;
        }
        $return = true;
        $result = Db::read_only()->get_array((new Db_Query())->select('c.`id_category`')->from('category', 'c')->join(Shop::add_sql_association('category', 'c'))->where('c.`id_parent` = ' . (int) $id_category_parent)->order_by('category_shop.`position`'));
        $count = count($result);
        for ($i = 0; $i < $count; $i++) {
            $return = Db::get_instance()->execute('
            UPDATE `' . _DB_PREFIX_ . 'category` c ' . Shop::add_sql_association('category', 'c') . '
            SET c.`position` = ' . $i . ',
            category_shop.`position` = ' . $i . ',
            c.`date_upd` = "' . date('Y-m-d H:i:s') . '"
            WHERE c.`id_parent` = ' . (int) $id_category_parent . ' AND c.`id_category` = ' . (int) $result[$i]['id_category']) && $return;
        }
        return $return;
    }
    /**
     * Updates level_depth for all children of the given id_category
     *
     * @param int $idCategory parent category
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function recalculate_level_depth($id_category): void
    {
        if (!is_numeric($id_category)) {
            throw new Presta_Shop_Exception('id category is not numeric');
        }
        /* Gets all children */
        $conn = Db::get_instance();
        $categories = $conn->get_array((new Db_Query())->select('`id_category`, `id_parent`, `level_depth`')->from('category')->where('`id_parent` = ' . (int) $id_category));
        /* Gets level_depth */
        $level = $conn->get_row((new Db_Query())->select('level_depth')->from('category')->where('`id_category` = ' . (int) $id_category));
        /* Updates level_depth for all children */
        foreach ($categories as $sub_category) {
            $conn->execute('
				UPDATE ' . _DB_PREFIX_ . 'category
				SET level_depth = ' . (int) ($level['level_depth'] + 1) . '
				WHERE id_category = ' . (int) $sub_category['id_category']);
            /* Recursive call */
            $this->recalculate_level_depth($sub_category['id_category']);
        }
    }
    /**
     * @throws PrestaShopException
     */
    public function toggle_status()
    {
        $result = parent::toggle_status();
        Hook::trigger_event('actionCategoryUpdate', ['category' => $this]);
        return $result;
    }
    /**
     * Recursive scan of subcategories
     *
     * @param int $maxDepth Maximum depth of the tree (i.e. 2 => 3 levels depth)
     * @param int $currentDepth specify the current depth in the tree (don't use it, only for rucursivity!)
     * @param int $idLang Specify the id of the language used
     * @param array $excludedIdsArray specify a list of ids to exclude of results
     *
     * @return array Subcategories lite tree
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function recurse_lite_categ_tree($max_depth = 3, $current_depth = 0, $id_lang = null, $excluded_ids_array = null)
    {
        $id_lang = is_null($id_lang) ? Context::get_context()->language->id : (int) $id_lang;
        $children = [];
        $subcats = $this->get_sub_categories($id_lang, true);
        if (($max_depth == 0 || $current_depth < $max_depth) && $subcats && count($subcats)) {
            foreach ($subcats as &$subcat) {
                if (!$subcat['id_category']) {
                    break;
                } elseif (!is_array($excluded_ids_array) || !in_array($subcat['id_category'], $excluded_ids_array)) {
                    $categ = new Category($subcat['id_category'], $id_lang);
                    $children[] = $categ->recurse_lite_categ_tree($max_depth, $current_depth + 1, $id_lang, $excluded_ids_array);
                }
            }
        }
        if (is_array($this->description)) {
            foreach ($this->description as $lang => $description) {
                $this->description[$lang] = Category::get_description_clean($description);
            }
        } else {
            $this->description = Category::get_description_clean($this->description);
        }
        return ['id' => (int) $this->id, 'link' => Context::get_context()->link->get_category_link($this->id, $this->link_rewrite), 'name' => $this->name, 'desc' => $this->description, 'children' => $children];
    }
    /**
     * Return current category childs
     *
     * @param int $idLang Language ID
     * @param bool $active return only active categories
     *
     * @return array Categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_sub_categories($id_lang, $active = true)
    {
        $sql_groups_where = '';
        $sql_groups_join = '';
        if (Group::is_feature_active()) {
            $sql_groups_join = 'LEFT JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cg.`id_category` = c.`id_category`)';
            $groups = Front_Controller::get_current_customer_groups();
            $sql_groups_where = 'AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '=' . (int) Group::get_current()->id);
        }
        $result = Db::read_only()->get_array('
		SELECT c.*, cl.id_lang, cl.name, cl.description, cl.link_rewrite, cl.meta_title, cl.meta_keywords, cl.meta_description
		FROM `' . _DB_PREFIX_ . 'category` c
		' . Shop::add_sql_association('category', 'c') . '
		LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (c.`id_category` = cl.`id_category` AND `id_lang` = ' . (int) $id_lang . ' ' . Shop::add_sql_restriction_on_lang('cl') . ')
		' . $sql_groups_join . '
		WHERE `id_parent` = ' . (int) $this->id . '
		' . static::get_active_column_condition($active, true) . '
		' . $sql_groups_where . '
		GROUP BY c.`id_category`
		ORDER BY `level_depth` ASC, category_shop.`position` ASC');
        foreach ($result as &$row) {
            $row['id_image'] = $row['id_category'];
            $row['legend'] = 'no picture';
        }
        return $result;
    }
    /**
     * @param string $description
     *
     * @return string
     */
    public static function get_description_clean($description)
    {
        return Tools::get_description_clean($description);
    }
    /**
     * Delete several categories from database
     *
     * return boolean Deletion result
     *
     * @throws PrestaShopException
     */
    public function delete_selection($categories)
    {
        $return = true;
        foreach ($categories as $id_category) {
            $category = new Category($id_category);
            if ($category->is_root_category_for_a_shop()) {
                $return = false;
            } else {
                $return = $category->delete() && $return;
            }
        }
        return $return;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_root_category_for_a_shop()
    {
        return (bool) Db::read_only()->get_value((new Db_Query())->select('`id_shop`')->from('shop')->where('`id_category` = ' . (int) $this->id));
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if ((int) $this->id === 0 || (int) $this->id === (int) Configuration::get('PS_ROOT_CATEGORY')) {
            return false;
        }
        if (Page_Cache::is_enabled()) {
            Page_Cache::invalidate_entity('category', $this->id);
        }
        $this->clear_cache();
        $deleted_children = $all_cat = $this->get_all_children();
        $all_cat[] = $this;
        foreach ($all_cat as $cat) {
            /** @var Category $cat */
            $cat->delete_lite();
            if (!$this->has_multishop_entries()) {
                $cat->delete_image();
                $cat->clean_groups();
                $cat->clean_asso_products();
                // Delete associated restrictions on cart rules
                Cart_Rule::clean_product_rule_integrity('categories', [$cat->id]);
                Category::clean_positions($cat->id_parent);
                /* Delete Categories in GroupReduction */
                if (Group_Reduction::get_groups_reduction_by_category_id((int) $cat->id)) {
                    Group_Reduction::delete_category($cat->id);
                }
            }
        }
        /* Rebuild the nested tree */
        if (!$this->has_multishop_entries() && (!isset($this->do_not_regenerate_n_tree) || !$this->do_not_regenerate_n_tree)) {
            Category::regenerate_entire_ntree();
        }
        Hook::trigger_event('actionCategoryDelete', ['category' => $this, 'deleted_children' => $deleted_children]);
        return true;
    }
    /**
     * Return an array of all children of the current category
     *
     * @param int $idLang
     *
     * @return PrestaShopCollection Collection of Category
     *
     * @throws PrestaShopException
     */
    public function get_all_children($id_lang = null)
    {
        if (is_null($id_lang)) {
            $id_lang = Context::get_context()->language->id;
        }
        $categories = new Presta_Shop_Collection('Category', $id_lang);
        $categories->where('nleft', '>', (int) $this->nleft);
        $categories->where('nright', '<', (int) $this->nright);
        return $categories;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete_lite()
    {
        // Directly call the parent of delete, in order to avoid recursion
        return parent::delete();
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function clean_asso_products()
    {
        return Db::get_instance()->delete('category_product', 'id_category = ' . (int) $this->id);
    }
    /**
     * @return int
     */
    public function get_shop_id()
    {
        return $this->id_shop;
    }
    /**
     * Returns category products
     *
     * @param int|null $idLang Language ID
     * @param int|null $p Page number
     * @param int|null $n Number of products per page
     * @param string|null $orderBy ORDER BY column
     * @param string|null $orderWay Order way
     * @param bool $getTotal If set to true, returns the total number of results only
     * @param bool $active If set to true, finds only active products
     * @param bool $random If true, sets a random filter for returned products
     * @param int $randomNumberProducts Number of products to return if random is activated
     * @param bool $checkAccess If set tot rue, check if the current customer
     *                                             can see products from this category
     *
     * @return array|int|false Products, number of products or false (no access)
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_products($id_lang, $p, $n, $order_by = null, $order_way = null, $get_total = false, $active = true, $random = false, $random_number_products = 1, $check_access = true, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        if ($check_access && !$this->check_access($context->customer->id)) {
            return false;
        }
        $front = in_array($context->controller->controller_type, ['front', 'modulefront']);
        $id_supplier = Tools::get_int_value('id_supplier');
        $subcats = $this->get_all_subcategories();
        $cats_to_search_in = [$this->id];
        if ($subcats && $this->display_from_sub) {
            foreach ($subcats as $scat) {
                $cats_to_search_in[] = $scat['id_category'];
            }
        }
        /** Return only the number of products */
        if ($get_total) {
            $sql = 'SELECT COUNT(DISTINCT(cp.`id_product`)) AS total
					FROM `' . _DB_PREFIX_ . 'product` p
					' . Shop::add_sql_association('product', 'p') . '
					LEFT JOIN `' . _DB_PREFIX_ . 'category_product` cp ON p.`id_product` = cp.`id_product`
					WHERE cp.`id_category` IN (' . implode(',', $cats_to_search_in) . ')' . ($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '') . ($active ? ' AND product_shop.`active` = 1' : '') . ($id_supplier ? ' AND p.id_supplier = ' . (int) $id_supplier : '');
            return (int) Db::read_only()->get_value($sql);
        }
        if ($p < 1) {
            $p = 1;
        }
        /** Tools::strtolower is a fix for all modules which are now using lowercase values for 'orderBy' parameter */
        $order_by = Validate::is_order_by($order_by) ? mb_strtolower((string) $order_by) : 'position';
        $order_way = Validate::is_order_way($order_way) ? mb_strtoupper((string) $order_way) : 'ASC';
        $order_by_prefix = false;
        if ($order_by == 'id_product' || $order_by == 'date_add' || $order_by == 'date_upd') {
            $order_by_prefix = 'p';
        } elseif ($order_by == 'name') {
            $order_by_prefix = 'pl';
        } elseif ($order_by == 'manufacturer' || $order_by == 'manufacturer_name') {
            $order_by_prefix = 'm';
            $order_by = 'name';
        } elseif ($order_by == 'position') {
            $order_by_prefix = 'cp';
        }
        if ($order_by == 'price') {
            $order_by = 'orderprice';
        }
        $nb_days_new_product = Configuration::get('PS_NB_DAYS_NEW_PRODUCT');
        if (!Validate::is_unsigned_int($nb_days_new_product)) {
            $nb_days_new_product = 20;
        }
        $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) AS quantity' . (Combination::is_feature_active() ? ', IFNULL(product_attribute_shop.id_product_attribute, 0) AS id_product_attribute,
					product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity' : '') . ', pl.`description`, pl.`description_short`, pl.`available_now`,
					pl.`available_later`, pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, image_shop.`id_image` id_image,
					il.`legend` as legend, m.`name` AS manufacturer_name, cl.`name` AS category_default,
					DATEDIFF(product_shop.`date_add`, DATE_SUB("' . date('Y-m-d') . ' 00:00:00",
					INTERVAL ' . (int) $nb_days_new_product . ' DAY)) > 0 AS new, product_shop.price AS orderprice
				FROM `' . _DB_PREFIX_ . 'category_product` cp
				LEFT JOIN `' . _DB_PREFIX_ . 'product` p
					ON p.`id_product` = cp.`id_product`
				' . Shop::add_sql_association('product', 'p') . (Combination::is_feature_active() ? ' LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop
				ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) $context->shop->id . ')' : '') . '
				' . Product::sql_stock('p', 0) . '
				LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl
					ON (product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('cl') . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
					ON (p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl') . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il
					ON (image_shop.`id_image` = il.`id_image`
					AND il.`id_lang` = ' . (int) $id_lang . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m
					ON m.`id_manufacturer` = p.`id_manufacturer`
				WHERE product_shop.`id_shop` = ' . (int) $context->shop->id . '
					AND cp.`id_category` IN (' . implode(',', $cats_to_search_in) . ')' . ($active ? ' AND product_shop.`active` = 1' : '') . ($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '') . ($id_supplier ? ' AND p.id_supplier = ' . (int) $id_supplier : '') . ' GROUP BY cp.id_product';
        if ($random === true) {
            $sql .= ' ORDER BY RAND() LIMIT ' . (int) $random_number_products;
        } else {
            $sql .= ' ORDER BY ' . (!empty($order_by_prefix) ? $order_by_prefix . '.' : '') . '`' . bq_sql($order_by) . '` ' . p_sql($order_way) . '
			LIMIT ' . ((int) $p - 1) * (int) $n . ',' . (int) $n;
        }
        $result = Db::read_only()->get_array($sql);
        if (!$result) {
            return [];
        }
        if ($order_by == 'orderprice') {
            Tools::orderby_price($result, $order_way);
        }
        /** Modify SQL result */
        return Product::get_products_properties($id_lang, $result);
    }
    /**
     * checkAccess return true if id_customer is in a group allowed to see this category.
     *
     * @param int|null $idCustomer
     *
     * @access  public
     * @return bool true if access allowed for customer $id_customer
     *
     * @throws PrestaShopException
     */
    public function check_access($id_customer)
    {
        $cache_id = 'Category::checkAccess_' . (int) $this->id . '-' . $id_customer . (!$id_customer ? '-' . (int) Group::get_current()->id : '');
        if (!Cache::is_stored($cache_id)) {
            $connection = Db::read_only();
            if (!$id_customer) {
                $result = (bool) $connection->get_value('
				SELECT ctg.`id_group`
				FROM ' . _DB_PREFIX_ . 'category_group ctg
				WHERE ctg.`id_category` = ' . (int) $this->id . ' AND ctg.`id_group` = ' . (int) Group::get_current()->id);
            } else {
                $result = (bool) $connection->get_value('
				SELECT ctg.`id_group`
				FROM ' . _DB_PREFIX_ . 'category_group ctg
				INNER JOIN ' . _DB_PREFIX_ . 'customer_group cg ON (cg.`id_group` = ctg.`id_group` AND cg.`id_customer` = ' . (int) $id_customer . ')
				WHERE ctg.`id_category` = ' . (int) $this->id);
            }
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Return an array of all parents of the current category
     *
     * @param int $idLang
     *
     * @return PrestaShopCollection Collection of Category
     *
     * @throws PrestaShopException
     */
    public function get_all_parents($id_lang = null)
    {
        if (is_null($id_lang)) {
            $id_lang = Context::get_context()->language->id;
        }
        $categories = new Presta_Shop_Collection('Category', $id_lang);
        $categories->where('nleft', '<', (int) $this->nleft);
        $categories->where('nright', '>', (int) $this->nright);
        $categories->order_by('nleft');
        return $categories;
    }
    /**
     * Returns path to category.
     *
     * @param int $categoryId leaf category ID
     * @param int|null $idLang language context
     * @param bool $includeRoot if true, Root pseudo-category will be included in the result
     *
     * @return Category[]
     * @throws PrestaShopException
     */
    public static function get_category_path(int $category_id, $id_lang = null, $include_root = false)
    {
        if (is_null($id_lang)) {
            $id_lang = (int) Context::get_context()->language->id;
        }
        $path = [];
        $leaf = new Category($category_id, $id_lang);
        if (Validate::is_loaded_object($leaf)) {
            $parents = $leaf->get_all_parents($id_lang);
            /** @var Category $parent */
            foreach ($parents as $parent) {
                if ($include_root || $parent->id_parent) {
                    $path[] = $parent;
                }
            }
            $path[] = $leaf;
        }
        return $path;
    }
    /**
     * @param int|null $idLang
     *
     * @return string
     * @throws PrestaShopException
     */
    public function get_link(?Link $link = null, $id_lang = null)
    {
        if (!$link) {
            $link = Context::get_context()->link;
        }
        if (!$id_lang && is_array($this->link_rewrite)) {
            $id_lang = Context::get_context()->language->id;
        }
        return $link->get_category_link($this, is_array($this->link_rewrite) ? $this->link_rewrite[$id_lang] : $this->link_rewrite, $id_lang);
    }
    /**
     * @param int|null $idLang
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function get_name($id_lang = null)
    {
        if (is_array($this->name)) {
            // object was loaded with all language context
            $name_array = $this->name;
        } else {
            // object was loaded in single language context
            // if object was loaded for requested language, we can return name directly
            if ($id_lang && $this->id_lang == $id_lang) {
                return $this->name;
            }
            if (!$id_lang && $this->id_lang == Context::get_context()->language->id) {
                return $this->name;
            }
            if (!$id_lang && $this->id_lang == Configuration::get('PS_LANG_DEFAULT')) {
                return $this->name;
            }
            // object was loaded in different language context than requested, we need to load names from db
            $connection = Db::read_only();
            $name_array = [];
            $rows = $connection->get_array((new Db_Query())->select('id_lang, name')->from('category_lang')->where('id_category = ' . (int) $this->id)->where(Shop::get_sql_restriction()));
            foreach ($rows as $row) {
                $name_array[(int) $row['id_lang']] = $row['name'];
            }
        }
        if (!$id_lang) {
            if (isset($name_array[Context::get_context()->language->id])) {
                $id_lang = Context::get_context()->language->id;
            } else {
                $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
            }
        }
        return $name_array[$id_lang] ?? '';
    }
    /**
     * Get Each parent category of this category until the root category
     *
     * @param int $idLang Language ID
     *
     * @return array Corresponding categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_parents_categories($id_lang = null)
    {
        static $parent_category_cache = [];
        $context = Context::get_context()->clone_context();
        $context->shop = clone $context->shop;
        if (is_null($id_lang)) {
            $id_lang = $context->language->id;
        }
        $categories = null;
        $id_current = $this->id;
        if (count(Category::get_categories_without_parent()) > 1 && Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && count(Shop::get_shops(true, null, true)) != 1) {
            $context->shop->id_category = (int) Configuration::get('PS_ROOT_CATEGORY');
        } elseif (!$context->shop->id) {
            $context->shop = new Shop(Configuration::get('PS_SHOP_DEFAULT'));
        }
        $id_shop = $context->shop->id;
        if (!isset($parent_category_cache[$id_shop])) {
            $parent_category_cache[$id_shop] = [];
        }
        if (!isset($parent_category_cache[$id_shop][$id_lang])) {
            $parent_category_cache[$id_shop][$id_lang] = [];
        }
        while (true) {
            if (!empty($parent_category_cache[$id_shop][$id_lang][$id_current])) {
                $result = $parent_category_cache[$id_shop][$id_lang][$id_current];
            } else {
                $sql = (new Db_Query())->select('c.*, cl.*')->from('category', 'c')->left_join('category_lang', 'cl', 'c.`id_category` = cl.`id_category`')->where('`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('cl'));
                if (Shop::is_feature_active() && Shop::get_context() == Shop::CONTEXT_SHOP) {
                    $sql->left_join('category_shop', 'cs', 'c.`id_category` = cs.`id_category` AND cs.`id_shop` = ' . (int) $id_shop);
                }
                $sql->where('c.`id_category` = ' . (int) $id_current);
                if (Shop::is_feature_active() && Shop::get_context() == Shop::CONTEXT_SHOP) {
                    $sql->where('cs.`id_shop` = ' . (int) $context->shop->id);
                }
                $root_category = Category::get_root_category();
                if (Shop::is_feature_active() && Shop::get_context() == Shop::CONTEXT_SHOP && (!Tools::is_submit('id_category') || Tools::get_int_value('id_category') === (int) $root_category->id || (int) $root_category->id === (int) $context->shop->id_category)) {
                    $sql->where('c.`id_parent` != 0');
                }
                $result = Db::read_only()->get_row($sql);
                $parent_category_cache[$id_shop][$id_lang][$id_current] = $result;
            }
            if ($result) {
                $categories[] = $result;
            } elseif (!$categories) {
                $categories = [];
            }
            if (!$result || $result['id_category'] == $context->shop->id_category) {
                return $categories;
            }
            $id_current = $result['id_parent'];
        }
    }
    /**
     * @param int $idGroup
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_groups_if_no_exist($id_group)
    {
        $id_group = (int) $id_group;
        $groups = $this->get_groups();
        if (!in_array($id_group, $groups)) {
            return $this->add_groups([$id_group]);
        }
        return false;
    }
    /**
     * @return int[]
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_groups()
    {
        $category_id = (int) $this->id;
        if ($category_id) {
            $cache_id = 'Category::getGroups_' . $category_id;
            if (!Cache::is_stored($cache_id)) {
                $result = Db::read_only()->get_array((new Db_Query())->select('cg.`id_group`')->from('category_group', 'cg')->where('cg.`id_category` = ' . $category_id));
                $groups = [];
                foreach ($result as $group) {
                    $groups[] = (int) $group['id_group'];
                }
                Cache::store($cache_id, $groups);
                return $groups;
            }
            return Cache::retrieve($cache_id);
        }
        return [];
    }
    /**
     * @param int $way
     * @param int $position
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_position($way, $position)
    {
        if (!$res = Db::read_only()->get_array((new Db_Query())->select('cp.`id_category`, category_shop.`position`, cp.`id_parent`')->from('category', 'cp')->join(Shop::add_sql_association('category', 'cp'))->where('cp.`id_parent` = ' . (int) $this->id_parent)->order_by('category_shop.`position` ASC'))) {
            return false;
        }
        $moved_category = false;
        foreach ($res as $category) {
            if ((int) $category['id_category'] == (int) $this->id) {
                $moved_category = $category;
            }
        }
        if ($moved_category === false) {
            return false;
        }
        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $conn = Db::get_instance();
        $result = $conn->execute('
            UPDATE `' . _DB_PREFIX_ . 'category` c ' . Shop::add_sql_association('category', 'c') . '
            SET c.`position`= c.`position` ' . ($way ? '- 1' : '+ 1') . ',
            category_shop.`position`= category_shop.`position` ' . ($way ? '- 1' : '+ 1') . ',
            c.`date_upd` = "' . date('Y-m-d H:i:s') . '"
            WHERE category_shop.`position`
            ' . ($way ? '> ' . (int) $moved_category['position'] . ' AND category_shop.`position` <= ' . (int) $position : '< ' . (int) $moved_category['position'] . ' AND category_shop.`position` >= ' . (int) $position) . '
            AND c.`id_parent`=' . (int) $moved_category['id_parent']) && $conn->execute('
            UPDATE `' . _DB_PREFIX_ . 'category` c ' . Shop::add_sql_association('category', 'c') . '
            SET c.`position` = ' . (int) $position . ',
            category_shop.`position` = ' . (int) $position . ',
            c.`date_upd` = "' . date('Y-m-d H:i:s') . '"
            WHERE c.`id_parent` = ' . (int) $moved_category['id_parent'] . '
            AND c.`id_category`=' . (int) $moved_category['id_category']);
        Hook::trigger_event('actionCategoryUpdate', ['category' => new Category($moved_category['id_category'])]);
        return $result;
    }
    /**
     * Check if current category is a child of shop root category
     *
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function in_shop(?Shop $shop = null)
    {
        if (!Validate::is_loaded_object($this)) {
            return false;
        }
        if (!$shop) {
            $shop = Context::get_context()->shop;
        }
        if (!$interval = Category::get_interval($shop->get_category())) {
            return false;
        }
        return $this->nleft >= $interval['nleft'] && $this->nright <= $interval['nright'];
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_children_ws()
    {
        return Db::read_only()->get_array('
		SELECT c.`id_category` AS id
		FROM `' . _DB_PREFIX_ . 'category` c
		' . Shop::add_sql_association('category', 'c') . '
		WHERE c.`id_parent` = ' . (int) $this->id . '
		AND category_shop.`active` = 1
		ORDER BY category_shop.`position` ASC');
    }
    /**
     * Returns products associated with this category
     *
     * @return int[]
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_associated_products()
    {
        $connection = Db::read_only();
        $result = $connection->get_array((new Db_Query())->select('id_product')->from('category_product')->where('id_category = ' . (int) $this->id)->order_by('`position` ASC, `id_product`'));
        return array_map(intval(...), array_column($result, 'id_product'));
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_products_ws()
    {
        return Db::read_only()->get_array('
		SELECT cp.`id_product` AS id
		FROM `' . _DB_PREFIX_ . 'category_product` cp
		WHERE cp.`id_category` = ' . (int) $this->id . '
		ORDER BY `position` ASC');
    }
    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    public function get_ws_nb_products_recursive()
    {
        if (!Validate::is_loaded_object($this)) {
            return -1;
        }
        $nb_product_recursive = (int) Db::read_only()->get_value('
			SELECT COUNT(DISTINCT(id_product))
			FROM  `' . _DB_PREFIX_ . 'category_product`
			WHERE id_category = ' . (int) $this->id . ' OR
			EXISTS (
				SELECT 1
				FROM `' . _DB_PREFIX_ . 'category` c2
				' . Shop::add_sql_association('category', 'c2') . '
				WHERE `' . _DB_PREFIX_ . 'category_product`.id_category = c2.id_category
					AND c2.nleft > ' . (int) $this->nleft . '
					AND c2.nright < ' . (int) $this->nright . '
					AND category_shop.active = 1
			)
		');
        if (!$nb_product_recursive) {
            return -1;
        }
        return $nb_product_recursive;
    }
    /**
     * @param int $idShop
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_parent_category_available($id_shop)
    {
        $id = Context::get_context()->shop->id;
        $id_shop = $id ?: Configuration::get('PS_SHOP_DEFAULT');
        return (bool) Db::read_only()->get_value((new Db_Query())->select('c.`id_category`')->from('category', 'c')->join(Shop::add_sql_association('category', 'c', true, null, true))->where('category_shop.`id_shop` = ' . (int) $id_shop)->where('c.`id_parent` = ' . (int) $this->id_parent));
    }
    /**
     * Add association between shop and categories
     *
     * @param int $idShop
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_shop($id_shop)
    {
        $data = [];
        if (!$id_shop) {
            foreach (Shop::get_shops(false) as $shop) {
                if (!$this->exists_in_shop($shop['id_shop'])) {
                    $data[] = ['id_category' => (int) $this->id, 'id_shop' => (int) $shop['id_shop']];
                }
            }
        } elseif (!$this->exists_in_shop($id_shop)) {
            $data[] = ['id_category' => (int) $this->id, 'id_shop' => (int) $id_shop];
        }
        return Db::get_instance()->insert('category_shop', $data);
    }
    /**
     * @param int $id_shop
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function exists_in_shop($id_shop)
    {
        return (bool) Db::read_only()->get_value((new Db_Query())->select('`id_category`')->from('category_shop')->where('`id_category` = ' . (int) $this->id)->where('`id_shop` = ' . (int) $id_shop));
    }
    /**
     * Delete category from shop $id_shop
     *
     * @param int $idShop
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete_from_shop($id_shop)
    {
        return Db::get_instance()->delete('category_shop', '`id_shop` = ' . (int) $id_shop . ' AND id_category = ' . (int) $this->id);
    }
    /**
     * Recursively add specified category childs to $to_delete array
     *
     * @param array &$toDelete Array reference where categories ID will be saved
     * @param int $idCategory Parent category ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.4.0 -- not used by core
     */
    protected function recursive_delete(&$to_delete, $id_category)
    {
        Tools::display_as_deprecated();
        if (Page_Cache::is_enabled()) {
            Page_Cache::invalidate_entity('category', $this->id);
        }
        $result = Db::read_only()->get_array((new Db_Query())->select('`id_category`')->from('category')->where('`id_parent` = ' . (int) $id_category));
        foreach ($result as $row) {
            $to_delete[] = (int) $row['id_category'];
            $this->recursive_delete($to_delete, (int) $row['id_category']);
        }
    }
    /**
     * Get all ids of all subcategories of the current category
     *
     * @return array list of ids of the subcategories
     *
     * @throws PrestaShopException
     */
    public function get_all_subcategories()
    {
        if (!Validate::is_loaded_object($this)) {
            return [];
        }
        return Db::read_only()->get_array((new Db_Query())->select('`id_category`')->from('category')->where('`nleft` > ' . (int) $this->nleft . ' AND `nright` < ' . (int) $this->nright));
    }
    /**
     * @param TableSchema $table
     */
    public static function process_table_schema($table): void
    {
        if ($table->get_name_without_prefix() === 'category_lang') {
            $table->reorder_columns(['id_category', 'id_shop', 'id_lang']);
        }
    }
    /**
     * Database initialization callback
     *
     * @throws PrestaShopException
     */
    public static function initialization_callback(Db $conn): void
    {
        // in 1.4.0 columns 'active', 'display_from_sub', 'date_add', and 'date_upd' were moved to
        // shop table. We need to initialize them properly
        $conn->execute('
            UPDATE ' . _DB_PREFIX_ . 'category_shop cs
            INNER JOIN ' . _DB_PREFIX_ . 'category c ON (cs.id_category = c.id_category)
            SET cs.active = c.active,
                cs.display_from_sub = c.display_from_sub,
                cs.date_add = c.date_add,
                cs.date_upd = c.date_upd
            WHERE IFNULL(cs.date_add, \'1970-01-01\') < \'1971-01-01\'
        ');
        Image_Entity::rebuild_image_entities(static::class, self::$definition['images']);
    }
}