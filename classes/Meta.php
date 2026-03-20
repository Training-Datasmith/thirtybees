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
 * Class MetaCore
 */
class Meta_Core extends Object_Model
{
    /**
     * @var string
     */
    public $page;
    /**
     * @var bool $configurable
     *
     * True:  The meta is configurable by AdminMetaController.
     * False: The meta is only a helper for a theme meta.
     */
    public $configurable = 1;
    /**
     * @var bool
     */
    public $nobots = 0;
    /**
     * @var string|string[]
     */
    public $title;
    /**
     * @var string|string[]
     */
    public $description;
    /**
     * @var string|string[]
     */
    public $keywords;
    /**
     * @var string|string[]
     */
    public $url_rewrite;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'meta', 'primary' => 'id_meta', 'multilang' => true, 'multilang_shop' => true, 'fields' => [
        'page' => ['type' => self::TYPE_STRING, 'validate' => 'isFileName', 'required' => true, 'size' => 128, 'unique' => true],
        'configurable' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '1'],
        'nobots' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        /* Lang fields */
        'title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
        'description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        'keywords' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        'url_rewrite' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'size' => 254, 'dbNullable' => false],
    ], 'keys' => ['meta_lang' => ['primary' => ['type' => Object_Model::PRIMARY_KEY, 'columns' => ['id_meta', 'id_shop', 'id_lang']], 'id_lang' => ['type' => Object_Model::KEY, 'columns' => ['id_lang']], 'id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]]];
    /**
     * @param bool $excludeFilled
     * @param bool $addPage
     * @param bool $forTheme If true, return 'forbidden' pages as well.
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ReflectionException
     */
    public static function get_pages($exclude_filled = false, $add_page = false, $for_theme = false)
    {
        $selected_pages = [];
        $files = Tools::scandir(_PS_FRONT_CONTROLLER_DIR_, 'php', '', true);
        if (!$files) {
            throw new Presta_Shop_Exception(Tools::display_error('Cannot scan root directory'));
        }
        $override_files = Tools::scandir(_PS_OVERRIDE_DIR_ . 'controllers/front/', 'php', '', true);
        if (!$override_files) {
            throw new Presta_Shop_Exception(Tools::display_error('Cannot scan override directory'));
        }
        $files = array_values(array_unique(array_merge($files, $override_files)));
        // Exclude pages forbidden.
        $exlude_pages = [];
        if (!$for_theme) {
            $exlude_pages = ['category', 'changecurrency', 'cms', 'footer', 'header', 'pagination', 'product', 'product-sort', 'statistics'];
        }
        foreach ($files as $file) {
            if ($file != 'index.php' && !in_array(strtolower(str_replace('Controller.php', '', $file)), $exlude_pages)) {
                $class_name = str_replace('.php', '', $file);
                $reflection = class_exists($class_name) ? new ReflectionClass(str_replace('.php', '', $file)) : false;
                $properties = $reflection ? $reflection->get_default_properties() : [];
                if (isset($properties['php_self'])) {
                    $selected_pages[$properties['php_self']] = $properties['php_self'];
                } elseif (preg_match('/^[a-z0-9_.-]*\.php$/i', (string) $file)) {
                    $selected_pages[strtolower(str_replace('Controller.php', '', $file))] = strtolower(str_replace('Controller.php', '', $file));
                } elseif (preg_match('/^([a-z0-9_.-]*\/)?[a-z0-9_.-]*\.php$/i', (string) $file)) {
                    $selected_pages[strtolower(sprintf(Tools::display_error('%2$s (in %1$s)'), dirname((string) $file), str_replace('Controller.php', '', basename((string) $file))))] = strtolower(str_replace('Controller.php', '', basename((string) $file)));
                }
            }
        }
        // Add module controllers to list.
        $module_dirs = Module::get_modules_dir_on_disk();
        foreach ($module_dirs as $module) {
            if (Module::is_installed($module)) {
                $path = _PS_MODULE_DIR_ . $module . '/controllers/front/';
                if (!is_dir($path)) {
                    continue;
                }
                foreach (Tools::scandir($path, 'php', '', false) as $file) {
                    if (in_array($file, ['.', '..', 'index.php'])) {
                        continue;
                    }
                    $filename = mb_strtolower(basename($file, '.php'));
                    $selected_pages[$module . ' - ' . $filename] = 'module-' . $module . '-' . $filename;
                }
            }
        }
        // Exclude page already filled
        if ($exclude_filled) {
            $metas = Meta::get_metas();
            foreach ($metas as $meta) {
                if (in_array($meta['page'], $selected_pages)) {
                    unset($selected_pages[array_search($meta['page'], $selected_pages)]);
                }
            }
        }
        // Add selected page
        if ($add_page) {
            $name = $add_page;
            if (preg_match('#module-([a-z0-9_-]+)-([a-z0-9]+)$#i', $add_page, $m)) {
                $add_page = $m[1] . ' - ' . $m[2];
            }
            $selected_pages[$add_page] = $name;
            asort($selected_pages);
        }
        return $selected_pages;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_metas()
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('meta')->order_by('`page` ASC'));
    }
    /**
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_metas_by_id_lang($id_lang)
    {
        return Db::read_only()->get_array((new Db_Query())->select('m.id_meta, m.page, m.configurable, ml.title, ml.description, ml.keywords, ml.url_rewrite')->from('meta', 'm')->left_join('meta_lang', 'ml', 'm.id_meta = ml.id_meta AND ml.id_lang = ' . (int) $id_lang . ' ' . Shop::add_sql_restriction_on_lang('ml'))->order_by('m.page ASC'));
    }
    /**
     * @param int $newIdLang
     * @param int $idLang
     * @param string $urlRewrite
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public static function get_equivalent_url_rewrite($new_id_lang, $id_lang, $url_rewrite)
    {
        $meta_sql = (new Db_Query())->select('`id_meta`')->from('meta_lang')->where('`url_rewrite` = \'' . p_sql($url_rewrite) . '\'')->where('`id_lang` = ' . (int) $id_lang)->where('`id_shop` = ' . (int) Context::get_context()->shop->id);
        return Db::read_only()->get_value((new Db_Query())->select('url_rewrite')->from('meta_lang')->where('id_meta = (' . $meta_sql->build() . ')')->where('`id_lang` = ' . (int) $new_id_lang)->where('`id_shop` = ' . (int) Context::get_context()->shop->id));
    }
    /**
     * @param int $idLang
     * @param string $pageName
     * @param string $title
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_meta_tags($id_lang, $page_name, $title = '')
    {
        if (!Configuration::get('PS_SHOP_ENABLE') && !in_array(Tools::get_remote_addr(), Tools::get_maintenance_ip_addresses())) {
            return Meta::get_home_metas($id_lang, $page_name);
        }
        if ($page_name == 'product' && $id_product = Tools::get_int_value('id_product')) {
            return Meta::get_product_metas($id_product, $id_lang, $page_name);
        }
        if ($page_name == 'category' && $id_category = Tools::get_int_value('id_category')) {
            return Meta::get_category_metas($id_category, $id_lang, $page_name, $title);
        }
        if ($page_name == 'manufacturer' && $id_manufacturer = Tools::get_int_value('id_manufacturer')) {
            return Meta::get_manufacturer_metas($id_manufacturer, $id_lang, $page_name);
        }
        if ($page_name == 'supplier' && $id_supplier = Tools::get_int_value('id_supplier')) {
            return Meta::get_supplier_metas($id_supplier, $id_lang, $page_name);
        }
        if ($page_name == 'cms' && $id_cms = Tools::get_int_value('id_cms')) {
            return Meta::get_cms_metas($id_cms, $id_lang, $page_name);
        }
        if ($page_name == 'cms' && $id_cms_category = Tools::get_int_value('id_cms_category')) {
            return Meta::get_cms_category_metas($id_cms_category, $id_lang, $page_name);
        }
        return Meta::get_home_metas($id_lang, $page_name);
    }
    /**
     * Get product meta tags
     *
     * @param int $idProduct
     * @param int $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_product_metas($id_product, $id_lang, $page_name)
    {
        if ($row = Db::read_only()->get_row((new Db_Query())->select('`name`, `meta_title`, `meta_description`, `meta_keywords`, `description_short`')->from('product', 'p')->join(Shop::add_sql_association('product', 'p'))->left_join('product_lang', 'pl', 'pl.`id_product` = p.`id_product` ' . Shop::add_sql_restriction_on_lang('pl'))->where('pl.`id_lang` = ' . (int) $id_lang)->where('pl.`id_product` = ' . (int) $id_product)->where('product_shop.`active` = 1'))) {
            if (!empty($row['meta_description'])) {
                $row['meta_description'] = strip_tags((string) $row['meta_description']);
            } elseif (!empty($row['description_short'])) {
                $row['meta_description'] = strip_tags((string) $row['description_short']);
            }
            return Meta::complete_meta_tags($row, $row['name']);
        }
        return Meta::get_home_metas($id_lang, $page_name);
    }
    /**
     * @param array $metaTags
     * @param string $defaultValue
     *
     * @return array
     * @throws PrestaShopException
     */
    public static function complete_meta_tags($meta_tags, $default_value, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        if (empty($meta_tags['meta_title'])) {
            $meta_tags['meta_title'] = $default_value . ' - ' . Configuration::get('PS_SHOP_NAME');
        }
        if (empty($meta_tags['meta_description'])) {
            $meta_tags['meta_description'] = (string) Configuration::get('PS_META_DESCRIPTION', $context->language->id);
        }
        if (empty($meta_tags['meta_keywords'])) {
            $meta_tags['meta_keywords'] = (string) Configuration::get('PS_META_KEYWORDS', $context->language->id);
        }
        return $meta_tags;
    }
    /**
     * Get meta tags for a given page
     *
     * @param int $idLang
     * @param string $pageName
     *
     * @return array Meta tags
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_home_metas($id_lang, $page_name)
    {
        $metas = Meta::get_meta_by_page($page_name, $id_lang);
        $shop_name = (string) Configuration::get('PS_SHOP_NAME');
        if ($metas) {
            $title = (string) $metas['title'];
            $ret = ['meta_title' => $title ? $title . ' - ' . $shop_name : $shop_name, 'meta_description' => (string) $metas['description'], 'meta_keywords' => (string) $metas['keywords']];
            if ($metas['nobots']) {
                $ret['nobots'] = true;
                $ret['nofollow'] = true;
            }
            return $ret;
        }
        return ['meta_title' => $shop_name, 'meta_description' => '', 'meta_keywords' => ''];
    }
    /**
     * @param string $page
     * @param int $idLang
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_meta_by_page($page, $id_lang)
    {
        return Db::read_only()->get_row((new Db_Query())->select('*')->from('meta', 'm')->left_join('meta_lang', 'ml', 'm.`id_meta` = ml.`id_meta`')->where('m.`page` = \'' . p_sql($page) . '\' OR m.`page` = \'' . p_sql(str_replace('_', '', strtolower($page))) . '\'')->where('ml.`id_lang` = ' . (int) $id_lang . ' ' . Shop::add_sql_restriction_on_lang('ml')));
    }
    /**
     * Get category meta tags
     *
     * @param int $idCategory
     * @param int $idLang
     * @param string $pageName
     * @param string $title
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_category_metas($id_category, $id_lang, $page_name, $title = '')
    {
        if (!empty($title)) {
            $title = ' - ' . $title;
        }
        $page_number = Tools::get_int_value('p');
        $cache_id = 'Meta::getCategoryMetas' . (int) $id_category . '-' . (int) $id_lang;
        if (!Cache::is_stored($cache_id)) {
            if ($row = Db::read_only()->get_row((new Db_Query())->select('`name`, `meta_title`, `meta_description`, `meta_keywords`, `description`')->from('category_lang', 'cl')->where('cl.`id_lang` = ' . (int) $id_lang)->where('cl.`id_category` = ' . (int) $id_category . ' ' . Shop::add_sql_restriction_on_lang('cl')))) {
                if (!empty($row['meta_description'])) {
                    $row['meta_description'] = strip_tags((string) $row['meta_description']);
                } elseif (!empty($row['description'])) {
                    $row['meta_description'] = strip_tags((string) $row['description']);
                }
                // Paginate title
                if (!empty($row['meta_title'])) {
                    $row['meta_title'] = $title . $row['meta_title'] . (!empty($page_number) ? ' (' . $page_number . ')' : '') . ' - ' . Configuration::get('PS_SHOP_NAME');
                } else {
                    $row['meta_title'] = $row['name'] . (!empty($page_number) ? ' (' . $page_number . ')' : '') . ' - ' . Configuration::get('PS_SHOP_NAME');
                }
                if (!empty($title)) {
                    $row['meta_title'] = $title . (!empty($page_number) ? ' (' . $page_number . ')' : '') . ' - ' . Configuration::get('PS_SHOP_NAME');
                }
                $result = Meta::complete_meta_tags($row, $row['name']);
            } else {
                $result = Meta::get_home_metas($id_lang, $page_name);
            }
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Get manufacturer meta tags
     *
     * @param int $idManufacturer
     * @param int $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_manufacturer_metas($id_manufacturer, $id_lang, $page_name)
    {
        $page_number = Tools::get_int_value('p');
        if ($row = Db::read_only()->get_row((new Db_Query())->select('`name`, `meta_title`, `meta_description`, `meta_keywords`')->from('manufacturer_lang', 'ml')->left_join('manufacturer', 'm', 'ml.`id_manufacturer` = m.`id_manufacturer`')->where('ml.`id_lang` = ' . (int) $id_lang)->where('ml.`id_manufacturer` = ' . (int) $id_manufacturer))) {
            if (!empty($row['meta_description'])) {
                $row['meta_description'] = strip_tags((string) $row['meta_description']);
            }
            $row['meta_title'] = ($row['meta_title'] ?: $row['name']) . (!empty($page_number) ? ' (' . $page_number . ')' : '');
            $row['meta_title'] .= ' - ' . Configuration::get('PS_SHOP_NAME');
            return Meta::complete_meta_tags($row, $row['meta_title']);
        }
        return Meta::get_home_metas($id_lang, $page_name);
    }
    /**
     * Get supplier meta tags
     *
     * @param int $idSupplier
     * @param int $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_supplier_metas($id_supplier, $id_lang, $page_name)
    {
        if ($row = Db::read_only()->get_row((new Db_Query())->select('`name`, `meta_title`, `meta_description`, `meta_keywords`')->from('supplier_lang', 'sl')->left_join('supplier', 's', 'sl.`id_supplier` = s.`id_supplier`')->where('sl.`id_lang` = ' . (int) $id_lang)->where('sl.`id_supplier` = ' . (int) $id_supplier))) {
            if (!empty($row['meta_description'])) {
                $row['meta_description'] = strip_tags((string) $row['meta_description']);
            }
            if (!empty($row['meta_title'])) {
                $row['meta_title'] = $row['meta_title'] . ' - ' . Configuration::get('PS_SHOP_NAME');
            }
            return Meta::complete_meta_tags($row, $row['name']);
        }
        return Meta::get_home_metas($id_lang, $page_name);
    }
    /**
     * Get CMS meta tags
     *
     * @param int $idCms
     * @param int $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_cms_metas($id_cms, $id_lang, $page_name)
    {
        if ($row = Db::read_only()->get_row((new Db_Query())->select('`meta_title`, `meta_description`, `meta_keywords`')->from('cms_lang')->where('`id_lang` = ' . (int) $id_lang)->where('`id_cms` = ' . (int) $id_cms)->where(Context::get_context()->shop->id ? '`id_shop` = ' . (int) Context::get_context()->shop->id : ''))) {
            $row['meta_title'] = $row['meta_title'] . ' - ' . Configuration::get('PS_SHOP_NAME');
            return Meta::complete_meta_tags($row, $row['meta_title']);
        }
        return Meta::get_home_metas($id_lang, $page_name);
    }
    /**
     * Get CMS category meta tags
     *
     * @param int $idCmsCategory
     * @param int $idLang
     * @param string $pageName
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_cms_category_metas($id_cms_category, $id_lang, $page_name)
    {
        if ($row = Db::read_only()->get_row((new Db_Query())->select('`meta_title`, `meta_description`, `meta_keywords`')->from('cms_category_lang')->where('`id_lang` = ' . (int) $id_lang)->where('`id_cms_category` = ' . (int) $id_cms_category)->where(Context::get_context()->shop->id ? '`id_shop` = ' . (int) Context::get_context()->shop->id : ''))) {
            $row['meta_title'] = $row['meta_title'] . ' - ' . Configuration::get('PS_SHOP_NAME');
            return Meta::complete_meta_tags($row, $row['meta_title']);
        }
        return Meta::get_home_metas($id_lang, $page_name);
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
        if (!parent::update($null_values)) {
            return false;
        }
        return Tools::generate_htaccess();
    }
    /**
     * @param array $selection
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete_selection($selection)
    {
        if (!is_array($selection)) {
            return false;
        }
        $result = true;
        foreach ($selection as $id) {
            $this->id = (int) $id;
            $result = $result && $this->delete();
        }
        return $result && Tools::generate_htaccess();
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
        return Tools::generate_htaccess();
    }
    /**
     * @param TableSchema $table
     */
    public static function process_table_schema($table): void
    {
        if ($table->get_name_without_prefix() === 'meta_lang') {
            $table->reorder_columns(['id_meta', 'id_shop', 'id_lang']);
        }
    }
}