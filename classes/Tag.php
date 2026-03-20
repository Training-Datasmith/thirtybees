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
 * Class TagCore
 */
class Tag_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'tag', 'primary' => 'id_tag', 'fields' => ['id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32]], 'keys' => ['tag' => ['id_lang' => ['type' => Object_Model::KEY, 'columns' => ['id_lang']], 'tag_name' => ['type' => Object_Model::KEY, 'columns' => ['name']]]]];
    /** @var int Language id */
    public $id_lang;
    /** @var string Name */
    public $name;
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['fields' => ['id_lang' => ['xlink_resource' => 'languages']]];
    /**
     * TagCore constructor.
     *
     * @param int|null $id
     * @param string|null $name
     * @param int|null $idLang
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $name = null, $id_lang = null)
    {
        $this->def = Tag::get_definition($this);
        $this->set_definition_retrocompatibility();
        if ($id) {
            parent::__construct($id);
        } elseif ($name && Validate::is_generic_name($name) && $id_lang && Validate::is_unsigned_id($id_lang)) {
            $row = Db::read_only()->get_row((new Db_Query())->select('*')->from('tag', 't')->where('`name` = \'' . p_sql($name) . '\'')->where('`id_lang` = ' . (int) $id_lang));
            if ($row) {
                $this->id = (int) $row['id_tag'];
                $this->id_lang = (int) $row['id_lang'];
                $this->name = $row['name'];
            }
        }
    }
    /**
     * Add several tags in database and link it to a product
     *
     * @param int $idLang Language id
     * @param int $idProduct Product id to link tags with
     * @param string|array $tagList List of tags, as array or as a string with comas
     * @param string $separator Separator to split a given string inot an array.
     *                                Not needed if $tagList is an array already.
     *
     * @return bool Operation success
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function add_tags($id_lang, $id_product, $tag_list, $separator = ',')
    {
        $id_product = (int) $id_product;
        $id_lang = (int) $id_lang;
        if (!is_array($tag_list)) {
            $tag_list = explode($separator, $tag_list);
        }
        if ($tag_list) {
            $list = [];
            $result = true;
            foreach ($tag_list as $tag) {
                if (!Validate::is_generic_name($tag)) {
                    $result = false;
                } else {
                    $tag = trim(mb_substr((string) $tag, 0, static::$definition['fields']['name']['size']));
                    $tag_obj = new Tag(null, $tag, $id_lang);
                    /* Tag does not exist in database */
                    if (!Validate::is_loaded_object($tag_obj)) {
                        $tag_obj->name = $tag;
                        $tag_obj->id_lang = $id_lang;
                        $tag_obj->add();
                    }
                    $tag_id = (int) $tag_obj->id;
                    if (!in_array($tag_id, $list)) {
                        $list[] = $tag_id;
                    }
                }
            }
            if ($list) {
                $insert = [];
                foreach ($list as $tag) {
                    $insert[] = ['id_tag' => $tag, 'id_product' => $id_product, 'id_lang' => $id_lang];
                }
                $result = Db::get_instance()->insert('product_tag', $insert, false, true, Db::INSERT_IGNORE) && $result;
                static::update_tag_count($list);
            }
            return $result;
        }
        return true;
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
        if (!parent::add($auto_date, $null_values)) {
            return false;
        }
        if (isset($_POST['products'])) {
            return $this->set_products(Tools::get_value('products'));
        }
        return true;
    }
    /**
     * @param array $array
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_products($array)
    {
        $conn = Db::get_instance();
        $result = $conn->delete('product_tag', '`id_tag` = ' . (int) $this->id);
        if (is_array($array) && $array) {
            $array = array_map(intval(...), $array);
            $result = Object_Model::update_multishop_table('Product', ['indexed' => 0], 'a.id_product IN (' . implode(',', $array) . ')') && $result;
            $ids = [];
            foreach ($array as $id_product) {
                $ids[] = ['id_product' => (int) $id_product, 'id_tag' => (int) $this->id, 'id_lang' => (int) $this->id_lang];
            }
            if ($result) {
                $result = $conn->insert('product_tag', $ids);
                if (Configuration::get('PS_SEARCH_INDEXATION')) {
                    $result = Search::indexation(false) && $result;
                }
            }
        }
        static::update_tag_count([(int) $this->id]);
        return $result;
    }
    /**
     * @param array|null $tagList
     *
     * @throws PrestaShopException
     */
    public static function update_tag_count($tag_list = null): void
    {
        if (!Module::get_batch_mode()) {
            $conn = Db::get_instance();
            if ($tag_list != null) {
                $tag_list_query = ' AND pt.id_tag IN (' . implode(',', $tag_list) . ')';
                $conn->execute('DELETE pt FROM `' . _DB_PREFIX_ . 'tag_count` pt WHERE 1=1 ' . $tag_list_query);
            } else {
                $tag_list_query = '';
            }
            $conn->execute('REPLACE INTO `' . _DB_PREFIX_ . 'tag_count` (id_group, id_tag, id_lang, id_shop, counter)
			SELECT cg.id_group, pt.id_tag, pt.id_lang, id_shop, COUNT(pt.id_tag) AS times
				FROM `' . _DB_PREFIX_ . 'product_tag` pt
				INNER JOIN `' . _DB_PREFIX_ . 'product_shop` product_shop
					USING (id_product)
				JOIN (SELECT DISTINCT id_group FROM `' . _DB_PREFIX_ . 'category_group`) cg
				WHERE product_shop.`active` = 1
				AND EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp
								LEFT JOIN `' . _DB_PREFIX_ . 'category_group` cgo ON (cp.`id_category` = cgo.`id_category`)
								WHERE cgo.`id_group` = cg.id_group AND product_shop.`id_product` = cp.`id_product`)
				' . $tag_list_query . '
				GROUP BY pt.id_tag, pt.id_lang, cg.id_group, id_shop ORDER BY NULL');
            $conn->execute('REPLACE INTO `' . _DB_PREFIX_ . 'tag_count` (id_group, id_tag, id_lang, id_shop, counter)
			SELECT 0, pt.id_tag, pt.id_lang, id_shop, COUNT(pt.id_tag) AS times
				FROM `' . _DB_PREFIX_ . 'product_tag` pt
				INNER JOIN `' . _DB_PREFIX_ . 'product_shop` product_shop
					USING (id_product)
				WHERE product_shop.`active` = 1
				' . $tag_list_query . '
				GROUP BY pt.id_tag, pt.id_lang, id_shop ORDER BY NULL');
        }
    }
    /**
     * @param int $idLang
     * @param int $nb
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_main_tags($id_lang, $nb = 10)
    {
        $context = Context::get_context();
        if (Group::is_feature_active()) {
            $groups = Front_Controller::get_current_customer_groups();
            $query = (new Db_Query())->select('t.`name`, pt.`counter` AS `times`')->from('tag_count', 'pt')->inner_join('tag', 't', 't.`id_tag` = pt.`id_tag`')->where('pt.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1'))->where('pt.`id_lang` = ' . (int) $id_lang)->where('pt.`id_shop` = ' . (int) $context->shop->id)->order_by('`times` DESC')->limit((int) $nb);
        } else {
            $query = (new Db_Query())->select('t.`name`, pt.`counter` AS `times`')->from('tag_count', 'pt')->inner_join('tag', 't', 't.`id_tag` = pt.`id_tag`')->where('pt.`id_group` = 0')->where('pt.`id_lang` = ' . (int) $id_lang)->where('pt.`id_shop` = ' . (int) $context->shop->id)->order_by('`times` DESC')->limit((int) $nb);
        }
        return Db::read_only()->get_array($query);
    }
    /**
     * @param int $idProduct
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_product_tags($id_product)
    {
        if (!$tmp = Db::read_only()->get_array((new Db_Query())->select('t.`id_lang`, t.`name`')->from('tag', 't')->left_join('product_tag', 'pt', 'pt.`id_tag` = t.`id_tag`')->where('pt.`id_product` = ' . (int) $id_product))) {
            return false;
        }
        $result = [];
        foreach ($tmp as $tag) {
            $result[$tag['id_lang']][] = $tag['name'];
        }
        return $result;
    }
    /**
     * @param int $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function delete_tags_for_product($id_product)
    {
        $tags_removed = Db::read_only()->get_array((new Db_Query())->select('`id_tag`')->from('product_tag')->where('`id_product` = ' . (int) $id_product));
        $conn = Db::get_instance();
        $result = $conn->delete('product_tag', 'id_product = ' . (int) $id_product);
        $conn->delete('tag', 'NOT EXISTS (SELECT 1 FROM ' . _DB_PREFIX_ . 'product_tag WHERE ' . _DB_PREFIX_ . 'product_tag.id_tag = ' . _DB_PREFIX_ . 'tag.id_tag)');
        $tag_list = [];
        foreach ($tags_removed as $tag_removed) {
            $tag_list[] = $tag_removed['id_tag'];
        }
        if ($tag_list != []) {
            static::update_tag_count($tag_list);
        }
        return $result;
    }
    /**
     * @param bool $associated
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_products($associated = true, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $id_lang = $this->id_lang ?: $context->language->id;
        if (!$this->id && $associated) {
            return [];
        }
        $in = $associated ? 'IN' : 'NOT IN';
        return Db::read_only()->get_array((new Db_Query())->select('pl.`name`, pl.`id_product`')->from('product', 'p')->left_join('product_lang', 'pl', 'p.`id_product` = pl.`id_product`' . Shop::add_sql_restriction_on_lang('pl') . Shop::add_sql_association('product', 'p') . ' AND pl.`id_lang` = ' . (int) $id_lang)->where('product_shop.`active` = 1')->where($this->id ? 'p.`id_product` ' . $in . ' (SELECT pt.`id_product` FROM `' . _DB_PREFIX_ . 'product_tag` pt WHERE pt.`id_tag` = ' . (int) $this->id . ')' : '')->order_by('pl.`name`'));
    }
}