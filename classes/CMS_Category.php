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
 * Class CMSCategoryCore
 */
class Cms_Category_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'cms_category', 'primary' => 'id_cms_category', 'multilang' => true, 'multilang_shop' => true, 'fields' => [
        'id_parent' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true],
        'level_depth' => ['type' => self::TYPE_INT, 'dbType' => 'tinyint(3) unsigned', 'dbDefault' => '0'],
        'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbDefault' => '0'],
        'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        'position' => ['type' => self::TYPE_INT, 'dbDefault' => '0'],
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => true, 'size' => 128],
        'description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => Object_Model::SIZE_TEXT],
        'link_rewrite' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'required' => true, 'size' => 128],
        'meta_title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
        'meta_keywords' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
    ], 'keys' => ['cms_category' => ['category_parent' => ['type' => Object_Model::KEY, 'columns' => ['id_parent']]], 'cms_category_lang' => ['primary' => ['type' => Object_Model::PRIMARY_KEY, 'columns' => ['id_cms_category', 'id_shop', 'id_lang']], 'category_name' => ['type' => Object_Model::KEY, 'columns' => ['name']]], 'cms_category_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectNodeName' => 'cms_category', 'objectsNodeName' => 'cms_categories', 'fields' => ['id_parent' => ['xlink_resource' => 'cms_categories']], 'associations' => ['cms_categories' => ['getter' => 'getChildrenWs', 'resource' => 'cms_categories'], 'content_management_system' => ['getter' => 'getCmsWs', 'resource' => 'content_management_system']]];
    /**
     * @var array
     */
    protected static $_links = [];
    /** @var int CMSCategory ID */
    public $id_cms_category;
    /** @var string|string[] Name */
    public $name;
    /** @var bool Status for display */
    public $active = 1;
    /** @var string|string[] Description */
    public $description;
    /** @var int Parent CMSCategory ID */
    public $id_parent;
    /** @var int category position */
    public $position;
    /** @var int Parents number */
    public $level_depth;
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
    /**
     * @param int|null $idLang
     * @param int $current
     * @param int $active
     * @param int $links
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_recurse_category($id_lang = null, $current = 1, $active = 1, $links = 0, ?Link $link = null)
    {
        if (!$link) {
            $link = Context::get_context()->link;
        }
        if (is_null($id_lang)) {
            $id_lang = Context::get_context()->language->id;
        }
        $connection = Db::read_only();
        $category = $connection->get_row((new Db_Query())->select('c.`id_cms_category`, c.`id_parent`, c.`level_depth`, cl.`name`, cl.`link_rewrite`')->from('cms_category', 'c')->inner_join('cms_category_lang', 'cl', 'c.`id_cms_category` = cl.`id_cms_category`')->where('c.`id_cms_category` = ' . (int) $current)->where('`id_lang` = ' . (int) $id_lang));
        if (!$category) {
            return [];
        }
        $sql = 'SELECT c.`id_cms_category`
				FROM `' . _DB_PREFIX_ . 'cms_category` c
				WHERE c.`id_parent` = ' . (int) $current . ($active ? ' AND c.`active` = 1' : '');
        $result = $connection->get_array($sql);
        $children = [];
        if ($result) {
            foreach ($result as $row) {
                $children_tree = static::get_recurse_category($id_lang, $row['id_cms_category'], $active, $links);
                if ($children_tree) {
                    $children[] = $children_tree;
                }
            }
        }
        $category['children'] = $children;
        $sql = 'SELECT c.`id_cms`, cl.`meta_title`, cl.`link_rewrite`
				FROM `' . _DB_PREFIX_ . 'cms` c
				' . Shop::add_sql_association('cms', 'c') . '
				JOIN `' . _DB_PREFIX_ . 'cms_lang` cl ON c.`id_cms` = cl.`id_cms`
				WHERE `id_cms_category` = ' . (int) $current . '
				AND cl.`id_lang` = ' . (int) $id_lang . ($active ? ' AND c.`active` = 1' : '') . '
				GROUP BY c.id_cms
				ORDER BY c.`position`';
        $category['cms'] = $connection->get_array($sql);
        if ($links == 1) {
            $category['link'] = $link->get_cms_category_link($current, $category['link_rewrite']);
            foreach ($category['cms'] as $key => $cms) {
                $category['cms'][$key]['link'] = $link->get_cms_link($cms['id_cms'], $cms['link_rewrite']);
            }
        }
        return $category;
    }
    /**
     * @param array $categories
     * @param array $current
     * @param int $idCmsCategory
     * @param int $idSelected
     * @param bool $isHtml
     *
     * @return string
     */
    public static function recurse_cms_category($categories, $current, $id_cms_category = 1, $id_selected = 1, $is_html = false)
    {
        $html = '<option value="' . $id_cms_category . '"' . ($id_selected == $id_cms_category ? ' selected="selected"' : '') . '>' . str_repeat('&nbsp;', $current['infos']['level_depth'] * 5) . Cms_Category::hide_cms_category_position(stripslashes((string) $current['infos']['name'])) . '</option>';
        if (!$is_html) {
            echo $html;
        }
        if (isset($categories[$id_cms_category])) {
            foreach (array_keys($categories[$id_cms_category]) as $key) {
                $html .= Cms_Category::recurse_cms_category($categories, $categories[$id_cms_category][$key], $key, $id_selected, $is_html);
            }
        }
        return $html;
    }
    /**
     * Return available categories
     *
     * @param int $idLang Language ID
     * @param bool $active return only active categories
     * @param bool $order
     *
     * @return array Categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_categories($id_lang, $active = true, $order = true)
    {
        $result = Db::read_only()->get_array('
		SELECT *
		FROM `' . _DB_PREFIX_ . 'cms_category` c
		LEFT JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl ON c.`id_cms_category` = cl.`id_cms_category`
		WHERE `id_lang` = ' . (int) $id_lang . '
		' . ($active ? 'AND `active` = 1' : '') . '
		ORDER BY `name` ASC');
        if (!$order) {
            return $result;
        }
        $categories = [];
        foreach ($result as $row) {
            $categories[$row['id_parent']][$row['id_cms_category']]['infos'] = $row;
        }
        return $categories;
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
        return Db::read_only()->get_array('
		SELECT c.`id_cms_category`, cl.`name`
		FROM `' . _DB_PREFIX_ . 'cms_category` c
		LEFT JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl ON (c.`id_cms_category` = cl.`id_cms_category`)
		WHERE cl.`id_lang` = ' . (int) $id_lang . '
		ORDER BY cl.`name`');
    }
    /**
     * Return main categories
     *
     * @param int $idLang Language ID
     * @param bool $active return only active categories
     *
     * @return array categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_home_categories($id_lang, $active = true)
    {
        return Cms_Category::get_children(1, $id_lang, $active);
    }
    /**
     * @param int $idParent
     * @param int $idLang
     * @param bool $active
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_children($id_parent, $id_lang, $active = true)
    {
        $result = Db::read_only()->get_array('
		SELECT c.`id_cms_category`, cl.`name`, cl.`link_rewrite`
		FROM `' . _DB_PREFIX_ . 'cms_category` c
		LEFT JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl ON c.`id_cms_category` = cl.`id_cms_category`
		WHERE `id_lang` = ' . (int) $id_lang . '
		AND c.`id_parent` = ' . (int) $id_parent . '
		' . ($active ? 'AND `active` = 1' : '') . '
		ORDER BY `name` ASC');
        // Modify SQL result
        $results_array = [];
        foreach ($result as $row) {
            $row['name'] = Cms_Category::hide_cms_category_position($row['name']);
            $results_array[] = $row;
        }
        return $results_array;
    }
    /**
     * Check if CMSCategory can be moved in another one
     *
     * @param int $idCmsCategory
     * @param int $idParent Parent candidate
     *
     * @return bool Parent validity
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function check_before_move($id_cms_category, $id_parent)
    {
        if ($id_cms_category == $id_parent) {
            return false;
        }
        if ($id_parent == 1) {
            return true;
        }
        $i = (int) $id_parent;
        while (42) {
            $result = Db::read_only()->get_row('SELECT `id_parent` FROM `' . _DB_PREFIX_ . 'cms_category` WHERE `id_cms_category` = ' . (int) $i);
            if (!isset($result['id_parent'])) {
                return false;
            }
            if ($result['id_parent'] == $id_cms_category) {
                return false;
            }
            if ($result['id_parent'] == 1) {
                return true;
            }
            $i = $result['id_parent'];
        }
    }
    /**
     * @param int $idCmsCategory
     * @param int $idLang
     *
     * @return bool|mixed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_link_rewrite($id_cms_category, $id_lang)
    {
        if (!Validate::is_unsigned_id($id_cms_category) || !Validate::is_unsigned_id($id_lang)) {
            return false;
        }
        if (isset(static::$_links[$id_cms_category . '-' . $id_lang])) {
            return static::$_links[$id_cms_category . '-' . $id_lang];
        }
        $result = Db::read_only()->get_row('
		SELECT cl.`link_rewrite`
		FROM `' . _DB_PREFIX_ . 'cms_category` c
		LEFT JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl ON c.`id_cms_category` = cl.`id_cms_category`
		WHERE `id_lang` = ' . (int) $id_lang . '
		AND c.`id_cms_category` = ' . (int) $id_cms_category);
        static::$_links[$id_cms_category . '-' . $id_lang] = $result['link_rewrite'];
        return $result['link_rewrite'];
    }
    /**
     * Light back office search for categories
     *
     * @param int $idLang Language ID
     * @param string $query Searched string
     * @param bool $unrestricted allows search without lang and includes first CMSCategory and exact match
     *
     * @return array|false Corresponding categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function search_by_name($id_lang, $query, $unrestricted = false)
    {
        $connection = Db::read_only();
        if ($unrestricted === true) {
            return $connection->get_row('
			SELECT c.*, cl.*
			FROM `' . _DB_PREFIX_ . 'cms_category` c
			LEFT JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl ON (c.`id_cms_category` = cl.`id_cms_category`)
			WHERE `name` = \'' . p_sql($query) . '\'');
        }
        return $connection->get_array('
			SELECT c.*, cl.*
			FROM `' . _DB_PREFIX_ . 'cms_category` c
			LEFT JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl ON (c.`id_cms_category` = cl.`id_cms_category` AND `id_lang` = ' . (int) $id_lang . ')
			WHERE `name` LIKE \'%' . p_sql($query) . '%\' AND c.`id_cms_category` != 1');
    }
    /**
     * Retrieve CMSCategory by name and parent CMSCategory id
     *
     * @param int $idLang Language ID
     * @param string $cmsCategoryName Searched CMSCategory name
     * @param int $idParentCmsCategory parent CMSCategory ID
     *
     * @return array|false Corresponding CMSCategory
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public static function search_by_name_and_parent_cms_category_id($id_lang, $cms_category_name, $id_parent_cms_category)
    {
        Tools::display_as_deprecated();
        return Db::read_only()->get_row('
		SELECT c.*, cl.*
	    FROM `' . _DB_PREFIX_ . 'cms_category` c
	    LEFT JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl ON (c.`id_cms_category` = cl.`id_cms_category` AND `id_lang` = ' . (int) $id_lang . ')
	    WHERE `name` = \'' . p_sql($cms_category_name) . '\'
		AND c.`id_cms_category` != 1
		AND c.`id_parent` = ' . (int) $id_parent_cms_category);
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
        return Db::read_only()->get_array((new Db_Query())->select('l.`id_lang`, c.`link_rewrite`')->from('cms_category_lang', 'c')->left_join('lang', 'l', 'c.`id_lang` = l.`id_lang`')->where('c.`id_cms_category` = ' . (int) $id_category)->where('l.`active` = 1')->add_current_shop_restriction('c'));
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
        $this->position = Cms_Category::get_last_position((int) $this->id_parent);
        $this->level_depth = $this->calc_level_depth();
        foreach ($this->name as $k => $value) {
            if (preg_match('/^[1-9]\./', $value)) {
                $this->name[$k] = '0' . $value;
            }
        }
        $ret = parent::add($auto_date, $null_values);
        static::clean_positions($this->id_parent);
        return $ret;
    }
    /**
     * @param int $idCategoryParent
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public static function get_last_position($id_category_parent)
    {
        return Db::read_only()->get_value((new Db_Query())->select('MAX(`position`)')->from('cms_category')->where('`id_parent` = ' . (int) $id_category_parent));
    }
    /**
     * Get the number of parent categories
     *
     * @return int Level depth
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function calc_level_depth()
    {
        $parent_cms_category = new Cms_Category($this->id_parent);
        return $parent_cms_category->level_depth + 1;
    }
    /**
     * @param int $idCategoryParent
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function clean_positions($id_category_parent)
    {
        $result = Db::read_only()->get_array((new Db_Query())->select('`id_cms_category`')->from('cms_category')->where('`id_parent` = ' . (int) $id_category_parent)->order_by('`position`'));
        $sizeof = count($result);
        for ($i = 0; $i < $sizeof; ++$i) {
            $sql = '
			UPDATE `' . _DB_PREFIX_ . 'cms_category`
			SET `position` = ' . $i . '
			WHERE `id_parent` = ' . (int) $id_category_parent . '
			AND `id_cms_category` = ' . (int) $result[$i]['id_cms_category'];
            Db::get_instance()->execute($sql);
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
        if (Page_Cache::is_enabled()) {
            Page_Cache::invalidate_entity('cms_category', $this->id);
        }
        $this->level_depth = $this->calc_level_depth();
        foreach ($this->name as $k => $value) {
            if (preg_match('/^[1-9]\./', $value)) {
                $this->name[$k] = '0' . $value;
            }
        }
        return parent::update($null_values);
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
    public function recurse_lite_categ_tree($max_depth = 3, $current_depth = 0, $id_lang = null, $excluded_ids_array = null, ?Link $link = null)
    {
        if (!$link) {
            $link = Context::get_context()->link;
        }
        if (is_null($id_lang)) {
            $id_lang = Context::get_context()->language->id;
        }
        // recursivity for subcategories
        $children = [];
        $subcats = $this->get_sub_categories($id_lang, true);
        if (($max_depth == 0 || $current_depth < $max_depth) && $subcats && count($subcats)) {
            foreach ($subcats as &$subcat) {
                if (!$subcat['id_cms_category']) {
                    break;
                } elseif (!is_array($excluded_ids_array) || !in_array($subcat['id_cms_category'], $excluded_ids_array)) {
                    $categ = new Cms_Category($subcat['id_cms_category'], $id_lang);
                    $categ->name = Cms_Category::hide_cms_category_position($categ->name);
                    $children[] = $categ->recurse_lite_categ_tree($max_depth, $current_depth + 1, $id_lang, $excluded_ids_array);
                }
            }
        }
        return ['id' => $this->id_cms_category, 'link' => $link->get_cms_category_link($this->id, $this->link_rewrite), 'name' => $this->name, 'desc' => $this->description, 'children' => $children];
    }
    /**
     * Return current CMSCategory childs
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
        $result = Db::read_only()->get_array('
		SELECT c.*, cl.id_lang, cl.name, cl.description, cl.link_rewrite, cl.meta_title, cl.meta_keywords, cl.meta_description
		FROM `' . _DB_PREFIX_ . 'cms_category` c
		LEFT JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl ON (c.`id_cms_category` = cl.`id_cms_category` AND `id_lang` = ' . (int) $id_lang . ')
		WHERE `id_parent` = ' . (int) $this->id . '
		' . ($active ? 'AND `active` = 1' : '') . '
		GROUP BY c.`id_cms_category`
		ORDER BY `name` ASC');
        // Modify SQL result
        foreach ($result as &$row) {
            $row['name'] = Cms_Category::hide_cms_category_position($row['name']);
        }
        return $result;
    }
    /**
     * Hide CMSCategory prefix used for position
     *
     * @param string $name CMSCategory name
     *
     * @return string Name without position
     */
    public static function hide_cms_category_position($name)
    {
        return preg_replace('/^[0-9]+\./', '', $name);
    }
    /**
     * Delete several categories from database
     *
     * @param array $categories
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_selection($categories)
    {
        $return = true;
        foreach ($categories as $id_category_cms) {
            $category_cms = new Cms_Category($id_category_cms);
            $return = $category_cms->delete() && $return;
        }
        return $return;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if ($this->id == 1) {
            return false;
        }
        if (Page_Cache::is_enabled()) {
            Page_Cache::invalidate_entity('cms', $this->id);
        }
        $this->clear_cache();
        // Get children categories
        $to_delete = [(int) $this->id];
        $this->recursive_delete($to_delete, (int) $this->id);
        $to_delete = array_unique($to_delete);
        // Delete CMS Category and its child from database
        $list = count($to_delete) > 1 ? implode(',', $to_delete) : (int) $this->id;
        $id_shop_list = $this->id_shop_list ?: Shop::get_context_list_shop_id();
        $conn = Db::get_instance();
        $conn->delete($this->def['table'] . '_shop', '`' . $this->def['primary'] . '` IN (' . $list . ') AND id_shop IN (' . implode(', ', $id_shop_list) . ')');
        $has_multishop_entries = $this->has_multishop_entries();
        if (!$has_multishop_entries) {
            $conn->execute('DELETE FROM `' . _DB_PREFIX_ . 'cms_category` WHERE `id_cms_category` IN (' . $list . ')');
            $conn->execute('DELETE FROM `' . _DB_PREFIX_ . 'cms_category_lang` WHERE `id_cms_category` IN (' . $list . ')');
        }
        Cms_Category::clean_positions($this->id_parent);
        // Delete pages which are in categories to delete
        $result = Db::read_only()->get_array('
		SELECT `id_cms`
		FROM `' . _DB_PREFIX_ . 'cms`
		WHERE `id_cms_category` IN (' . $list . ')');
        foreach ($result as $c) {
            $cms = new CMS((int) $c['id_cms']);
            if (Validate::is_loaded_object($cms)) {
                $cms->delete();
            }
        }
        return true;
    }
    /**
     * Recursively add specified CMSCategory childs to $toDelete array
     *
     * @param array &$toDelete Array reference where categories ID will be saved
     * @param array|int $idCmsCategory Parent CMSCategory ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function recursive_delete(&$to_delete, $id_cms_category)
    {
        if (!is_array($to_delete) || !$id_cms_category) {
            throw new Presta_Shop_Exception('Invalid input parameters');
        }
        $result = Db::read_only()->get_array('
		SELECT `id_cms_category`
		FROM `' . _DB_PREFIX_ . 'cms_category`
		WHERE `id_parent` = ' . (int) $id_cms_category);
        foreach ($result as $row) {
            $to_delete[] = (int) $row['id_cms_category'];
            $this->recursive_delete($to_delete, (int) $row['id_cms_category']);
        }
    }
    /**
     *
     * @return string
     * @throws PrestaShopException
     */
    public function get_link(?Link $link = null)
    {
        if (!$link) {
            $link = Context::get_context()->link;
        }
        return $link->get_cms_category_link($this->id, $this->link_rewrite);
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
        $context = Context::get_context();
        if (!$id_lang) {
            if (isset($this->name[$context->language->id])) {
                $id_lang = $context->language->id;
            } else {
                $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
            }
        }
        return $this->name[$id_lang] ?? '';
    }
    /**
     * Get Each parent CMSCategory of this CMSCategory until the root CMSCategory
     *
     * @param int $idLang Language ID
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_parents_categories($id_lang = null)
    {
        if (is_null($id_lang)) {
            $id_lang = Context::get_context()->language->id;
        }
        $categories = null;
        $id_current = $this->id;
        while (true) {
            $query = '
				SELECT c.*, cl.*
				FROM `' . _DB_PREFIX_ . 'cms_category` c
				LEFT JOIN `' . _DB_PREFIX_ . 'cms_category_lang` cl ON (c.`id_cms_category` = cl.`id_cms_category` AND `id_lang` = ' . (int) $id_lang . ')
				WHERE c.`id_cms_category` = ' . (int) $id_current . ' AND c.`id_parent` != 0
			';
            $result = Db::read_only()->get_array($query);
            $categories[] = $result[0];
            if (!$result || $result[0]['id_parent'] == 1) {
                return $categories;
            }
            $id_current = $result[0]['id_parent'];
        }
    }
    /**
     * @param bool $way
     * @param int $position
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_position($way, $position)
    {
        if (!$res = Db::read_only()->get_array('
			SELECT cp.`id_cms_category`, cp.`position`, cp.`id_parent`
			FROM `' . _DB_PREFIX_ . 'cms_category` cp
			WHERE cp.`id_parent` = ' . (int) $this->id_parent . '
			ORDER BY cp.`position` ASC')) {
            return false;
        }
        foreach ($res as $category) {
            if ((int) $category['id_cms_category'] == (int) $this->id) {
                $moved_category = $category;
            }
        }
        if (!isset($moved_category) || !isset($position)) {
            return false;
        }
        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $conn = Db::get_instance();
        return $conn->execute('
			UPDATE `' . _DB_PREFIX_ . 'cms_category`
			SET `position`= `position` ' . ($way ? '- 1' : '+ 1') . '
			WHERE `position`
			' . ($way ? '> ' . (int) $moved_category['position'] . ' AND `position` <= ' . (int) $position : '< ' . (int) $moved_category['position'] . ' AND `position` >= ' . (int) $position) . '
			AND `id_parent`=' . (int) $moved_category['id_parent']) && $conn->execute('
			UPDATE `' . _DB_PREFIX_ . 'cms_category`
			SET `position` = ' . (int) $position . '
			WHERE `id_parent` = ' . (int) $moved_category['id_parent'] . '
			AND `id_cms_category`=' . (int) $moved_category['id_cms_category']);
    }
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_children_ws()
    {
        $result = [];
        $children = $this->get_sub_categories(Configuration::get('PS_LANG_DEFAULT'), false);
        foreach ($children as $category) {
            $result[] = ['id' => $category['id_cms_category']];
        }
        return $result;
    }
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_cms_ws()
    {
        $result = [];
        $pages = Cms::get_cms_pages((int) Configuration::get('PS_LANG_DEFAULT'), (int) $this->id, false);
        foreach ($pages as $cms) {
            $result[] = ['id' => $cms['id_cms']];
        }
        return $result;
    }
}