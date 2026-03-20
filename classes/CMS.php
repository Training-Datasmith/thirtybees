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
 * Class CMSCore
 */
class Cms_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'cms', 'primary' => 'id_cms', 'multilang' => true, 'multilang_shop' => true, 'fields' => [
        'id_cms_category' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbNullable' => false],
        'position' => ['type' => self::TYPE_INT, 'dbDefault' => '0'],
        'active' => ['type' => self::TYPE_BOOL, 'dbDefault' => '0'],
        'indexation' => ['type' => self::TYPE_BOOL, 'dbDefault' => '1'],
        /* Lang fields */
        'meta_title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128],
        'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        'meta_keywords' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        'content' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => Object_Model::SIZE_LONG_TEXT],
        'link_rewrite' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'required' => true, 'size' => 128],
    ], 'keys' => ['cms_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]], 'cms_lang' => ['primary' => ['type' => Object_Model::PRIMARY_KEY, 'columns' => ['id_cms', 'id_shop', 'id_lang']]]]];
    /**
     * @var string|string[] Name
     */
    public $meta_title;
    /**
     * @var string|string[]
     */
    public $meta_description;
    /**
     * @var string|string[]
     */
    public $meta_keywords;
    /**
     * @var string|string[]
     */
    public $content;
    /**
     * @var string|string[]
     */
    public $link_rewrite;
    /**
     * @var int
     */
    public $id_cms_category;
    /**
     * @var int
     */
    public $position;
    /**
     * @var bool
     */
    public $indexation;
    /**
     * @var bool
     */
    public $active;
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectNodeName' => 'content', 'objectsNodeName' => 'content_management_system', 'fields' => ['id_cms_category' => ['xlink_resource' => 'cms_categories']]];
    /**
     * @param int $idLang
     * @param array|null $selection
     * @param bool $active
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_links($id_lang, $selection = null, $active = true, ?Link $link = null)
    {
        if (!$link) {
            $link = Context::get_context()->link;
        }
        $result = Db::read_only()->get_array((new Db_Query())->select('c.`id_cms`, cl.`link_rewrite`, cl.`meta_title`')->from('cms', 'c')->left_join('cms_lang', 'cl', 'c.`id_cms` = cl.`id_cms` AND cl.`id_lang` = ' . (int) $id_lang)->join(Shop::add_sql_association('cms', 'c'))->where($selection !== null ? 'c.`id_cms` IN (' . implode(',', array_map(intval(...), $selection)) . ')' : '')->where($active ? 'c.`active` = 1 ' : '')->group_by('c.`id_cms`')->order_by('c.`position`'));
        $links = [];
        if ($result) {
            foreach ($result as $row) {
                $row['link'] = $link->get_cms_link((int) $row['id_cms'], $row['link_rewrite']);
                $links[] = $row;
            }
        }
        return $links;
    }
    /**
     * @param int|null $idLang
     * @param bool $idBlock
     * @param bool $active
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function list_cms($id_lang = null, $id_block = false, $active = true)
    {
        if (empty($id_lang)) {
            $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        }
        return Db::read_only()->get_array((new Db_Query())->select('c.`id_cms`, l.`meta_title`')->from('cms', 'c')->inner_join('cms_lang', 'l', 'c.`id_cms` = l.`id_cms`')->join($id_block ? 'JOIN `' . _DB_PREFIX_ . 'block_cms` b ON (c.`id_cms` = b.`id_cms`)' : '')->where('l.`id_lang` = ' . (int) $id_lang)->where($id_block ? 'b.`id_block` = ' . (int) $id_block : '')->where($active ? 'c.`active` = 1' : '')->group_by('c.`id_cms`')->order_by('c.`position`'));
    }
    /**
     * @param int|null $idLang
     * @param int|null $idCmsCategory
     * @param bool $active
     * @param int|null $idShop
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_cms_pages($id_lang = null, $id_cms_category = null, $active = true, $id_shop = null)
    {
        $sql = new Db_Query();
        $sql->select('*');
        $sql->from('cms', 'c');
        if ($id_lang) {
            if ($id_shop) {
                $sql->inner_join('cms_lang', 'l', 'c.`id_cms` = l.`id_cms` AND l.`id_lang` = ' . (int) $id_lang . ' AND l.`id_shop` = ' . (int) $id_shop);
            } else {
                $sql->inner_join('cms_lang', 'l', 'c.`id_cms` = l.`id_cms` AND l.`id_lang` = ' . (int) $id_lang);
            }
        }
        if ($id_shop) {
            $sql->inner_join('cms_shop', 'cs', 'c.`id_cms` = cs.`id_cms` AND cs.`id_shop` = ' . (int) $id_shop);
        }
        if ($active) {
            $sql->where('c.`active` = 1');
        }
        if ($id_cms_category) {
            $sql->where('c.`id_cms_category` = ' . (int) $id_cms_category);
        }
        $sql->order_by('position');
        return Db::read_only()->get_array($sql);
    }
    /**
     * @param int $idCms
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_url_rewrite_informations($id_cms)
    {
        return Db::read_only()->get_array((new Db_Query())->select('l.`id_lang`, c.`link_rewrite`')->from('cms_lang', 'c')->left_join('lang', 'l', 'c.`id_lang` = l.`id_lang`')->where('c.`id_cms` = ' . (int) $id_cms)->where('l.`active` = 1')->add_current_shop_restriction('c'));
    }
    /**
     * @param int $idCms
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_cms_content($id_cms, $id_lang = null, $id_shop = null)
    {
        if (is_null($id_lang)) {
            $id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        }
        if (is_null($id_shop)) {
            $id_shop = (int) Configuration::get('PS_SHOP_DEFAULT');
        }
        return Db::read_only()->get_row((new Db_Query())->select('`content`')->from('cms_lang')->where('`id_cms` = ' . (int) $id_cms)->where('`id_lang` = ' . (int) $id_lang)->where('`id_shop` = ' . (int) $id_shop));
    }
    /**
     * @return string
     */
    public static function get_repository_class_name()
    {
        return 'Core_Business_CMS_CMSRepository';
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
        $this->position = CMS::get_last_position((int) $this->id_cms_category);
        return parent::add($auto_date, true);
    }
    /**
     * @param int $idCategory
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public static function get_last_position($id_category)
    {
        return Db::read_only()->get_value((new Db_Query())->select('MAX(`position`) + 1')->from('cms')->where('`id_cms_category` = ' . (int) $id_category));
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
            Page_Cache::invalidate_entity('cms', $this->id);
        }
        if (parent::update($null_values)) {
            return static::clean_positions($this->id_cms_category);
        }
        return false;
    }
    /**
     * @param int $idCategory
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function clean_positions($id_category)
    {
        $result = Db::read_only()->get_array((new Db_Query())->select('`id_cms`')->from('cms')->where('`id_cms_category` = ' . (int) $id_category)->order_by('`position`'));
        for ($i = 0, $total = count($result); $i < $total; ++$i) {
            Db::get_instance()->update('cms', ['position' => $i], '`id_cms_category` = ' . (int) $id_category . ' AND `id_cms` = ' . (int) $result[$i]['id_cms']);
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
        if (Page_Cache::is_enabled()) {
            Page_Cache::invalidate_entity('cms', $this->id);
        }
        if (parent::delete()) {
            return static::clean_positions($this->id_cms_category);
        }
        return false;
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
        if (!$res = Db::read_only()->get_array((new Db_Query())->select('cp.`id_cms`, cp.`position`, cp.`id_cms_category`')->from('cms', 'cp')->where('cp.`id_cms_category` = ' . (int) $this->id_cms_category)->order_by('cp.`position` ASC'))) {
            return false;
        }
        foreach ($res as $cms) {
            if ((int) $cms['id_cms'] == (int) $this->id) {
                $moved_cms = $cms;
            }
        }
        if (!isset($moved_cms) || !isset($position)) {
            return false;
        }
        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $conn = Db::get_instance();
        return $conn->update('cms', ['position' => ['type' => 'sql', 'value' => '`position` ' . ($way ? '- 1' : '+ 1')]], '`position` ' . ($way ? '> ' . (int) $moved_cms['position'] . ' AND `position` <= ' . (int) $position : '< ' . (int) $moved_cms['position'] . ' AND `position` >= ' . (int) $position) . ' AND `id_cms_category`=' . (int) $moved_cms['id_cms_category']) && $conn->update('cms', ['position' => (int) $position], '`id_cms` = ' . (int) $moved_cms['id_cms'] . ' AND `id_cms_category`=' . (int) $moved_cms['id_cms_category']);
    }
}