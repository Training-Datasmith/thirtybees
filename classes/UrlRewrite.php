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
 * Class UrlRewrite
 */
class Url_Rewrite_Core extends Object_Model
{
    public const CANONICAL = 1;
    public const DIRECT_SERVE = 2;
    public const REDIRECT_301 = 3;
    public const REDIRECT_302 = 4;
    public const ENTITY_PRODUCT = 1;
    public const ENTITY_CATEGORY = 2;
    public const ENTITY_SUPPLIER = 3;
    public const ENTITY_MANUFACTURER = 4;
    public const ENTITY_CMS = 5;
    public const ENTITY_CMS_CATEGORY = 6;
    public const ENTITY_PAGE = 7;
    public const MAX_CATEGORY_DEPTH = 10;
    /**
     * @var array Object model definition
     */
    public static $definition = [];
    /** @var int $entity */
    public $entity;
    /** @var int $id_entity */
    public $id_entity;
    /** @var string $rewrite */
    public $rewrite;
    /** @var int $redirect */
    public $redirect;
    /**
     * UrlRewriteCore constructor.
     *
     *
     * @deprecated 1.0.1
     */
    public function __construct()
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @deprecated 1.0.1
     */
    public static function regenerate_url_rewrites($id_lang = null, $id_shop = null, array $entities = [self::ENTITY_PRODUCT, self::ENTITY_CATEGORY, self::ENTITY_SUPPLIER, self::ENTITY_MANUFACTURER, self::ENTITY_CMS, self::ENTITY_CMS_CATEGORY, self::ENTITY_PAGE]): void
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param int $entityType
     * @param int|null $idEntity
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @deprecated 1.0.1
     */
    public static function regenerate_url_rewrite($entity_type, $id_entity = null, $id_lang = null, $id_shop = null): void
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param string $route
     * @param array $params
     *
     * @deprecated 1.0.1
     */
    public static function create_base_url($route, $params): void
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * Delete URL rewrites
     *
     * @param int $entityType
     * @param int $idLang
     * @param int $idShop
     * @param int|null $idEntity
     *
     * @deprecated 1.0.1
     */
    public static function delete_url_rewrites($entity_type, $id_lang, $id_shop, $id_entity = null): void
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param int $entityType
     * @param int $idEntity
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @deprecated 1.0.1
     */
    public static function delete_url_rewrite($entity_type, $id_entity, $id_lang = null, $id_shop = null): void
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param string $rewrite
     * @param int $idLang
     * @param int $idShop
     * @param int|null $redirect
     * @param int|null $entityType
     */
    public static function lookup($rewrite, $id_lang, $id_shop, $redirect = null, $entity_type = null): void
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param int $idEntity
     * @param int $entityType
     * @param int $idLang
     * @param int $idShop
     * @param int|null $redirect
     */
    public static function reverse_lookup($id_entity, $entity_type, $id_lang, $id_shop, $redirect = null): void
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function get_category_info($id_lang, $id_shop)
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param int $idLang
     * @param int $idShop
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function get_cms_category_info($id_lang, $id_shop)
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $categories
     * @param array $newRoutes
     * @param int|null $idEntity
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function generate_product_url_rewrites($id_lang, $id_shop, $categories, $new_routes, $id_entity = null)
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $categories
     * @param array $newRoutes
     * @param int|null $idEntity
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function generate_category_url_rewrites($id_lang, $id_shop, $categories, &$new_routes, $id_entity = null)
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $newRoutes
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function generate_supplier_url_rewrites($id_lang, $id_shop, $new_routes, $id_entity = null)
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $newRoutes
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function generate_manufacturer_url_rewrites($id_lang, $id_shop, &$new_routes, $id_entity = null)
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $categories
     * @param array $newRoutes
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function generate_cms_url_rewrites($id_lang, $id_shop, $categories, &$new_routes, $id_entity = null)
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param int $idLang
     * @param int $idShop
     * @param array $categories
     * @param array $newRoutes
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function generate_cms_category_url_rewrites($id_lang, $id_shop, $categories, &$new_routes, $id_entity = null)
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * Fills the routes array with the current available routes
     *
     * @param int $idLang
     * @param int $idShop
     * @param array $routes
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function get_routes($id_lang, $id_shop, &$routes)
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * Check if a keyword is written in a route rule
     *
     * @param array $rule
     * @param string $keyword
     *
     * @return void
     * @deprecated 1.0.1
     */
    protected static function has_keyword($rule, $keyword)
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param int $idLang
     * @param int $idShop
     * @return void
     */
    protected static function generate_page_url_rewrites($id_lang, $id_shop)
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
    /**
     * @param int $idLang
     * @param int $idShop
     * @param int|null $idProduct
     *
     * @deprecated 1.0.1
     */
    public static function update_product_rewrite($id_lang, $id_shop, $id_product = null): void
    {
        Tools::display_as_deprecated('UrlRewrite class has been removed');
    }
}