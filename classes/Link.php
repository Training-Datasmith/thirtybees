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
use Thirtybees\Core\View\Model\Product_View_Model;
/**
 * Class LinkCore
 *
 *
 * Backwards compatible properties and methods (accessed via magic methods):
 * @property array|null $category_disable_rewrite
 */
class Link_Core
{
    /**
     * @var array[]
     */
    public static $cache = ['page' => []];
    /**
     * @var array|null $categoryDisableRewrite
     */
    protected static $category_disable_rewrite;
    /**
     * @var bool Rewriting activation
     */
    protected int $allow;
    /**
     * @var string
     */
    protected $url;
    protected bool $ssl_enable;
    /**
     * Constructor (initialization only)
     *
     * @param string|null $protocol_link
     * @param string|null $protocol_content
     *
     * @throws PrestaShopException
     */
    public function __construct(public $protocol_link = null, public $protocol_content = null)
    {
        $this->allow = (int) Configuration::get('PS_REWRITING_SETTINGS');
        $this->url = $_SERVER['SCRIPT_NAME'];
        if (!defined('_PS_BASE_URL_')) {
            define('_PS_BASE_URL_', Tools::get_shop_domain(true));
        }
        if (!defined('_PS_BASE_URL_SSL_')) {
            define('_PS_BASE_URL_SSL_', Tools::get_shop_domain_ssl(true));
        }
        if (static::$category_disable_rewrite === null) {
            static::$category_disable_rewrite = [Configuration::get('PS_HOME_CATEGORY'), Configuration::get('PS_ROOT_CATEGORY')];
        }
        $this->ssl_enable = (bool) Configuration::get('PS_SSL_ENABLED');
    }
    /**
     * thirty bees' new coding style dictates that camelCase should be used
     * rather than snake_case
     * These magic methods provide backwards compatibility for modules/themes/whatevers
     * that still access properties via their snake_case names
     *
     * @param string $property Property name
     */
    public function &__get(string $property): mixed
    {
        // Property to camelCase for backwards compatibility
        $camel_case_property = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));
        if (property_exists($this, $camel_case_property) && in_array($camel_case_property, ['categoryDisableRewrite'])) {
            return $this->{$camel_case_property};
        }
        return $this->{$property};
    }
    /**
     * Create a link to delete a product
     *
     * @param int|Product $product ID of the product OR a Product object
     * @param int $idPicture ID of the picture to delete
     *
     *
     * @throws PrestaShopException
     */
    public function get_product_delete_picture_link($product, $id_picture): string
    {
        $url = $this->get_product_link($product);
        return $url . (strpos($url, '?') ? '&' : '?') . 'deletePicture=' . $id_picture;
    }
    /**
     * @param int|array|ProductCore $productIdentifier
     * @param string|null $alias
     * @param int|null $category
     * @param string|null $ean13
     * @param int|null $idLang
     * @param int|null $idShop
     * @param int $ipa
     * @param bool $forceRoutes
     * @param bool $relativeProtocol
     * @param bool|string $addAnchor
     * @param array $extraParams
     *
     * @throws PrestaShopException
     */
    public function get_product_link($product_identifier, $alias = null, $category = null, $ean13 = null, $id_lang = null, $id_shop = null, $ipa = 0, $force_routes = false, $relative_protocol = false, $add_anchor = false, $extra_params = []): string
    {
        $dispatcher = Dispatcher::get_instance();
        if (!$id_lang) {
            $id_lang = Context::get_context()->language->id;
        }
        $url = $this->get_base_link($id_shop, null, $relative_protocol) . $this->get_lang_link($id_lang, null, $id_shop);
        $product = $this->get_product_object($product_identifier, $id_lang, $id_shop);
        // Set available keywords
        $params = [];
        $params['id'] = $product->id;
        $params['rewrite'] = !$alias ? $product->get_field_by_lang('link_rewrite') : $alias;
        $params['ean13'] = !$ean13 ? $product->ean13 : $ean13;
        $params['meta_keywords'] = Tools::str2url($product->get_field_by_lang('meta_keywords'));
        $params['meta_title'] = Tools::str2url($product->get_field_by_lang('meta_title'));
        if ($dispatcher->has_keyword('product_rule', $id_lang, 'manufacturer', $id_shop)) {
            $params['manufacturer'] = Tools::str2url($product->is_fully_loaded ? $product->manufacturer_name : Manufacturer::get_name_by_id($product->id_manufacturer));
        }
        if ($dispatcher->has_keyword('product_rule', $id_lang, 'supplier', $id_shop)) {
            $params['supplier'] = Tools::str2url($product->is_fully_loaded ? $product->supplier_name : Supplier::get_name_by_id($product->id_supplier));
        }
        if ($dispatcher->has_keyword('product_rule', $id_lang, 'price', $id_shop)) {
            $params['price'] = $product->is_fully_loaded ? $product->price : Product::get_price_static($product->id, false, null, _TB_PRICE_DATABASE_PRECISION_, null, false, true, 1, false, null, null, null, $product->specific_price);
        }
        if ($dispatcher->has_keyword('product_rule', $id_lang, 'tags', $id_shop)) {
            $params['tags'] = Tools::str2url($product->get_tags($id_lang));
        }
        if ($dispatcher->has_keyword('product_rule', $id_lang, 'category', $id_shop)) {
            $params['category'] = !empty($product->category) ? Tools::str2url($product->category) : Tools::str2url($category);
        }
        if ($dispatcher->has_keyword('product_rule', $id_lang, 'reference', $id_shop)) {
            $params['reference'] = Tools::str2url($product->reference);
        }
        if ($dispatcher->has_keyword('product_rule', $id_lang, 'categories', $id_shop)) {
            $params['category'] = !$category ? $product->category : $category;
            $cats = [];
            $category_disable_rewrite = static::$category_disable_rewrite;
            foreach ($product->get_parent_categories($id_lang) as $cat) {
                if (!in_array($cat['id_category'], $category_disable_rewrite)) {
                    //remove root and home category from the URL
                    $cats[] = $cat['link_rewrite'];
                }
            }
            $params['categories'] = implode('/', $cats);
        }
        $anchor = is_string($add_anchor) ? $add_anchor : '';
        if (!$ipa && $product instanceof Product_View_Model) {
            $ipa = (int) $product->get_selected_combination_id();
        }
        if ($ipa && (int) $ipa !== (int) $product->get_default_id_product_attribute()) {
            $params['combination'] = (int) $ipa;
        }
        return $url . $dispatcher->create_url('product_rule', $id_lang, array_merge($params, $extra_params), $force_routes, $anchor, $id_shop);
    }
    /**
     * @param int|null $idShop
     * @param bool|null $ssl
     * @param bool $relativeProtocol
     *
     *
     * @throws PrestaShopException
     */
    public function get_base_link($id_shop = null, $ssl = null, $relative_protocol = false): string
    {
        static $force_ssl = null;
        if ($ssl === null) {
            if ($force_ssl === null) {
                $force_ssl = Configuration::get('PS_SSL_ENABLED');
            }
            $ssl = $force_ssl;
        }
        if (Configuration::get('PS_MULTISHOP_FEATURE_ACTIVE') && $id_shop !== null) {
            $shop = new Shop($id_shop);
        } else {
            $shop = Context::get_context()->shop;
        }
        if ($relative_protocol) {
            $base = '//' . ($ssl && $this->ssl_enable ? $shop->domain_ssl : $shop->domain);
        } else {
            $base = $ssl && $this->ssl_enable ? 'https://' . $shop->domain_ssl : 'http://' . $shop->domain;
        }
        return $base . $shop->get_base_uri();
    }
    /**
     * @param int|null $idLang
     * @param int|null $idShop
     *
     *
     * @throws PrestaShopException
     */
    public function get_lang_link($id_lang = null, ?Context $context = null, $id_shop = null): string
    {
        if (!$context) {
            $context = Context::get_context();
        }
        if (!$id_lang) {
            $id_lang = $context->language->id;
        }
        $id_lang = (int) $id_lang;
        // friendly urls must be enabled
        $friendly_url_enabled = (bool) Configuration::get('PS_REWRITING_SETTINGS', null, null, $id_shop);
        if (!$friendly_url_enabled) {
            return '';
        }
        // language code can be hidden, depending on settings
        $lang_in_url_settings = (int) Configuration::get(Configuration::LANGUAGE_CODE_IN_URL, null, null, $id_shop);
        if ($lang_in_url_settings === Language::LANG_CODE_IN_URL_WHEN_MULTI_LANGUAGES && !Language::is_multi_language_activated($id_shop)) {
            return '';
        }
        $default_language_id = (int) Configuration::get('PS_LANG_DEFAULT', null, null, $id_shop);
        if ($lang_in_url_settings === Language::LANG_CODE_IN_URL_FOR_NON_DEFAULT_LANGUAGES && $id_lang === $default_language_id) {
            return '';
        }
        // Return the language friendly url code
        return Language::get_url_code_by_id($id_lang) . '/';
    }
    /**
     * Use controller name to create a link
     *
     * @param string $controller
     * @param bool $withToken include or not the token in the url
     * @param array $params optional parameters to be passed to controller
     * @param array $filters admin controller list filter values
     *
     * @return string url
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_admin_link($controller, $with_token = true, $params = [], $filters = [])
    {
        $id_lang = Context::get_context()->language->id;
        if (!is_array($params)) {
            $call_point = Tools::get_call_point([Link::class]);
            $error_message = 'Link::getAdminLink(): parameter $params has invalid type. ';
            $error_message .= 'Expected array, got ' . gettype($params) . '. ';
            $error_message .= 'This will raise error in future version of thirty bees. ';
            $error_message .= 'Called from: ' . $call_point['description'];
            trigger_error($error_message, E_USER_WARNING);
            $params = [];
        }
        if ($with_token) {
            $params['token'] = Tools::get_admin_token_lite($controller);
        }
        if (is_array($filters) && $filters) {
            $params['submitFilterForced'] = true;
            foreach ($filters as $column => $value) {
                $params['list_idFilter_' . $column] = $value;
            }
        }
        return Dispatcher::get_instance()->create_url($controller, $id_lang, $params, false);
    }
    /**
     * Returns a link to a product image for display
     *
     * @param string $name Rewrite link of the image
     * @param string|int|null $ids ID part of the image filename - can be "id_product-id_image" (legacy support, recommended) or "id_image" (new)
     * @param string $imageType Image type
     * @param string $imageExtension Image format (jpg/png/webp). Auto-detected by default
     * @param bool $highDpi Higher resolution
     *
     *
     * @throws PrestaShopException
     */
    public function get_image_link($name, $ids, $image_type = null, $image_extension = null, bool $high_dpi = false): string
    {
        $ids = (string) $ids;
        $context = Context::get_context();
        if (is_null($name)) {
            $name = $ids;
        }
        if (!is_string($name)) {
            $call_point = Tools::get_call_point([Link::class]);
            $error_message = 'Link::getImageLink(): parameter $name has invalid type. ';
            $error_message .= 'Expected string, got ' . gettype($name) . '. ';
            $error_message .= 'This will raise error in future version of thirty bees. ';
            $error_message .= 'Called from: ' . $call_point['description'];
            trigger_error($error_message, E_USER_WARNING);
            $name = static::resolve_name($name, $ids);
        }
        if (!$image_extension) {
            $image_extension = Image_Manager::get_default_image_extension();
        }
        $formatted_type = Image_Type::get_formated_name($image_type) ?? '';
        // Check if module is installed, enabled, customer is logged in and watermark logged option is on
        // TODO: this functionality should be extracted to post-processing hook
        if ($formatted_type && isset($context->customer->id) && Configuration::get('WATERMARK_LOGGED') && Module::is_installed('watermark') && Module::is_enabled('watermark')) {
            $watermark_types = static::get_watermark_image_types();
            if (isset($watermark_types[$formatted_type])) {
                $formatted_type = $watermark_types[$formatted_type];
            }
        }
        $uri_path = false;
        if (preg_match('/^([a-zA-Z]{2,3})-default-?([a-zA-Z_]*)$/', $ids, $matches)) {
            // $ids contains string like 'en-default' or 'es-default-Niara_cart', not actual product image ID
            $iso = $matches[1];
            if (isset($matches[2])) {
                // if $ids contains image type, use it
                $override_type = Image_Type::get_formated_name($matches[2]) ?? '';
                $uri_path = $this->get_product_default_image_uri($iso, $override_type, $high_dpi, $image_extension);
            }
            if (!$uri_path) {
                $uri_path = $this->get_product_default_image_uri($iso, $formatted_type, $high_dpi, $image_extension);
            }
        } else {
            // ids can either be single number, or in format id_product-id_image
            $split_ids = explode('-', $ids);
            $id_image = (int) ($split_ids[1] ?? $split_ids[0]);
            if ($id_image) {
                $uri_path = $this->get_product_image_uri($id_image, $formatted_type, $high_dpi, $image_extension, $name);
            }
        }
        // fallback to default image uri
        if (!$uri_path) {
            $uri_path = $this->get_product_default_image_uri($context->language->iso_code, $formatted_type, $high_dpi, $image_extension);
        }
        // image file not found
        if (!$uri_path) {
            $uri_path = _PS_IMG_ . '404.gif';
        }
        return $this->protocol_content . Tools::get_media_server($uri_path) . $uri_path;
    }
    /**
     *
     *
     * @throws PrestaShopException
     */
    public function get_media_link(string $filepath): string
    {
        return $this->protocol_content . Tools::get_media_server($filepath) . $filepath;
    }
    /**
     * @param string $name
     * @param int $idCategory
     * @param string|null $imageType
     * @param string $imageExtension Deprecated, since Auto-detected
     * @param boolean $highDpi
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function get_cat_image_link($name, $id_category, $image_type = null, $image_extension = null, $high_dpi = false)
    {
        return static::get_generic_image_link('categories', $id_category, $image_type, $high_dpi ? '2x' : '', null, $name);
    }
    /**
     * Get an image link to anything but products.
     *
     * @param string $imageEntityName name of the imageEntity. 'categories', 'manufacturers', ...
     * @param int $id ID of the image.
     * @param string $imageType Image type name, like 'home', 'home_small', ...
     * @param bool $highDpi Higher resolution
     * @param bool $webp Deprecated, since Auto-detected
     * @param string $link_rewrite An image name for pretty/SEO-friendly URLs.
     *                            Currently, only (products and) categories
     *                            support such names.
     *
     * @return string Full URL to the image.
     *
     * @throws PrestaShopException
     */
    public static function get_generic_image_link(string $image_entity_name, $id, $image_type = null, $high_dpi = false, $webp = null, $link_rewrite = ''): string
    {
        // Format imageType
        $image_type = Image_Type::get_formated_name($image_type);
        $image_type = $image_type ? '-' . $image_type : '';
        // Format link rewrite
        $link_rewrite = Configuration::get('PS_REWRITING_SETTINGS') && $link_rewrite ? $link_rewrite : $id;
        $high_dpi = $high_dpi ? '2x' : '';
        // Get default image extension
        $image_extension = Image_Manager::get_default_image_extension();
        if ((int) Configuration::get('PS_REWRITING_SETTINGS') || !isset(_TB_IMAGE_MAP_[$image_entity_name])) {
            $uri_path = __PS_BASE_URI__ . $image_entity_name . '/' . $id . $image_type . '/' . $link_rewrite . $high_dpi . '.' . $image_extension;
        } else {
            $uri_path = _PS_IMG_ . _TB_IMAGE_MAP_[$image_entity_name] . $id . $image_type . $high_dpi . '.' . $image_extension;
        }
        return Tools::get_shop_protocol() . Tools::get_media_server($uri_path) . $uri_path;
    }
    /**
     * Create link after language change, for the change language block
     *
     * @param int $idLang Language ID
     *
     * @return string link
     * @throws PrestaShopException
     */
    public function get_language_link($id_lang, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $params = $_GET;
        unset($params['isolang'], $params['controller']);
        if (!$this->allow) {
            $params['id_lang'] = $id_lang;
        } else {
            unset($params['id_lang']);
        }
        if (!empty($context->controller->php_self)) {
            $controller = $context->controller->php_self;
        } else {
            $controller = Dispatcher::get_instance()->get_controller();
        }
        if ($controller == 'product' && isset($params['id_product'])) {
            return $this->get_product_link((int) $params['id_product'], null, null, null, (int) $id_lang);
        }
        if ($controller == 'category' && isset($params['id_category'])) {
            return $this->get_category_link((int) $params['id_category'], null, (int) $id_lang);
        }
        if ($controller == 'supplier' && isset($params['id_supplier'])) {
            return $this->get_supplier_link((int) $params['id_supplier'], null, (int) $id_lang);
        }
        if ($controller == 'manufacturer' && isset($params['id_manufacturer'])) {
            return $this->get_manufacturer_link((int) $params['id_manufacturer'], null, (int) $id_lang);
        }
        if ($controller == 'cms' && isset($params['id_cms'])) {
            return $this->get_cms_link((int) $params['id_cms'], null, null, (int) $id_lang);
        }
        if ($controller == 'cms' && isset($params['id_cms_category'])) {
            return $this->get_cms_category_link((int) $params['id_cms_category'], null, (int) $id_lang);
        }
        if (isset($params['fc']) && $params['fc'] == 'module') {
            $module = Validate::is_module_name(Tools::get_value('module')) ? Tools::get_value('module') : '';
            if (!empty($module)) {
                unset($params['fc'], $params['module']);
                return $this->get_module_link($module, $controller, $params, null, (int) $id_lang);
            }
        }
        return $this->get_page_link($controller, null, $id_lang, $params);
    }
    /**
     * @param int|CategoryCore $category
     * @param string|null $alias
     * @param int|null $idLang
     * @param string|null $selectedFilters
     * @param int|null $idShop
     * @param bool $relativeProtocol
     *
     * @throws PrestaShopException
     */
    public function get_category_link($category, $alias = null, $id_lang = null, $selected_filters = null, $id_shop = null, $relative_protocol = false): string
    {
        if (!$id_lang) {
            $id_lang = Context::get_context()->language->id;
        }
        $url = $this->get_base_link($id_shop, null, $relative_protocol) . $this->get_lang_link($id_lang, null, $id_shop);
        if (!is_object($category)) {
            $category = new Category($category, $id_lang, $id_shop);
        }
        // Set available keywords
        $params = [];
        $params['id'] = $category->id;
        $params['rewrite'] = !$alias ? $category->link_rewrite : $alias;
        $params['meta_keywords'] = Tools::str2url($category->get_field_by_lang('meta_keywords'));
        $params['meta_title'] = Tools::str2url($category->get_field_by_lang('meta_title'));
        $cats = [];
        $category_disable_rewrite = static::$category_disable_rewrite;
        foreach ($category->get_parents_categories($id_lang) as $cat) {
            if (!in_array($cat['id_category'], $category_disable_rewrite)) {
                //remove root and home category from the URL
                $cats[] = $cat['link_rewrite'];
            }
        }
        array_shift($cats);
        $cats = array_reverse($cats);
        $params['categories'] = trim(implode('/', $cats), '/');
        // Selected filters are used by layered navigation modules
        $selected_filters = is_null($selected_filters) ? '' : $selected_filters;
        if (empty($selected_filters)) {
            $rule = 'category_rule';
        } else {
            $rule = 'layered_rule';
            $params['selected_filters'] = $selected_filters;
        }
        return $url . Dispatcher::get_instance()->create_url($rule, $id_lang, $params, $this->allow, '', $id_shop);
    }
    /**
     * Create a link to a supplier
     *
     * @param int|Supplier $supplier Supplier object
     * @param string $alias
     * @param int $idLang
     * @param int|null $idShop
     * @param bool $relativeProtocol
     *
     * @throws PrestaShopException
     */
    public function get_supplier_link($supplier, $alias = null, $id_lang = null, $id_shop = null, $relative_protocol = false): string
    {
        if (!$id_lang) {
            $id_lang = Context::get_context()->language->id;
        }
        $url = $this->get_base_link($id_shop, null, $relative_protocol) . $this->get_lang_link($id_lang, null, $id_shop);
        $dispatcher = Dispatcher::get_instance();
        if (!is_object($supplier)) {
            if ($alias !== null && !$dispatcher->has_keyword('supplier_rule', $id_lang, 'meta_keywords', $id_shop) && !$dispatcher->has_keyword('supplier_rule', $id_lang, 'meta_title', $id_shop)) {
                return $url . $dispatcher->create_url('supplier_rule', $id_lang, ['id' => (int) $supplier, 'rewrite' => (string) $alias], $this->allow, '', $id_shop);
            }
            $supplier = new Supplier($supplier, $id_lang);
        }
        // Set available keywords
        $params = [];
        $params['id'] = $supplier->id;
        $params['rewrite'] = !$alias ? $supplier->link_rewrite : $alias;
        $params['meta_keywords'] = Tools::str2url($supplier->meta_keywords);
        $params['meta_title'] = Tools::str2url($supplier->meta_title);
        return $url . $dispatcher->create_url('supplier_rule', $id_lang, $params, $this->allow, '', $id_shop);
    }
    /**
     * Create a link to a manufacturer
     *
     * @param Manufacturer|int $manufacturer Manufacturer object
     * @param string $alias
     * @param int $idLang
     * @param int|null $idShop
     * @param bool $relativeProtocol
     *
     * @throws PrestaShopException
     */
    public function get_manufacturer_link($manufacturer, $alias = null, $id_lang = null, $id_shop = null, $relative_protocol = false): string
    {
        if (!$id_lang) {
            $id_lang = Context::get_context()->language->id;
        }
        $url = $this->get_base_link($id_shop, null, $relative_protocol) . $this->get_lang_link($id_lang, null, $id_shop);
        $dispatcher = Dispatcher::get_instance();
        if (!is_object($manufacturer)) {
            if ($alias !== null && !$dispatcher->has_keyword('manufacturer_rule', $id_lang, 'meta_keywords', $id_shop) && !$dispatcher->has_keyword('manufacturer_rule', $id_lang, 'meta_title', $id_shop)) {
                return $url . $dispatcher->create_url('manufacturer_rule', $id_lang, ['id' => (int) $manufacturer, 'rewrite' => (string) $alias], $this->allow, '', $id_shop);
            }
            $manufacturer = new Manufacturer($manufacturer, $id_lang);
        }
        // Set available keywords
        $params = [];
        $params['id'] = $manufacturer->id;
        $params['rewrite'] = !$alias ? $manufacturer->link_rewrite : $alias;
        $params['meta_keywords'] = Tools::str2url($manufacturer->meta_keywords);
        $params['meta_title'] = Tools::str2url($manufacturer->meta_title);
        return $url . $dispatcher->create_url('manufacturer_rule', $id_lang, $params, $this->allow, '', $id_shop);
    }
    /**
     * @param int|CMS $cms
     * @param string|null $alias
     * @param bool|null $ssl
     * @param int|null $idLang
     * @param int|null $idShop
     * @param bool $relativeProtocol
     *
     * @throws PrestaShopException
     */
    public function get_cms_link($cms, $alias = null, $ssl = null, $id_lang = null, $id_shop = null, $relative_protocol = false): string
    {
        if (!$id_lang) {
            $id_lang = Context::get_context()->language->id;
        }
        if (!$id_shop) {
            $id_shop = Context::get_context()->shop->id;
        }
        $url = $this->get_base_link($id_shop, $ssl, $relative_protocol) . $this->get_lang_link($id_lang, null, $id_shop);
        $dispatcher = Dispatcher::get_instance();
        if (!is_object($cms)) {
            $cms = new CMS($cms, $id_lang);
        }
        // Set available keywords
        $params = [];
        $params['id'] = $cms->id;
        $params['rewrite'] = !$alias ? is_array($cms->link_rewrite) ? $cms->link_rewrite[(int) $id_lang] : $cms->link_rewrite : $alias;
        $params['meta_keywords'] = '';
        $params['categories'] = $this->find_cms_subcategories($cms->id, $id_lang);
        if (!empty($cms->meta_keywords)) {
            $params['meta_keywords'] = is_array($cms->meta_keywords) ? Tools::str2url($cms->meta_keywords[(int) $id_lang]) : Tools::str2url($cms->meta_keywords);
        }
        $params['meta_title'] = '';
        if (!empty($cms->meta_title)) {
            $params['meta_title'] = is_array($cms->meta_title) ? Tools::str2url($cms->meta_title[(int) $id_lang]) : Tools::str2url($cms->meta_title);
        }
        return $url . $dispatcher->create_url('cms_rule', $id_lang, $params, $this->allow, '', $id_shop);
    }
    /**
     * @param int $idCms
     * @param int $idLang
     *
     * @throws PrestaShopException
     */
    protected function find_cms_subcategories($id_cms, $id_lang): string
    {
        $sql = new Db_Query();
        $sql->select('`' . bq_sql(Cms_Category::$definition['primary']) . '`');
        $sql->from(bq_sql(CMS::$definition['table']));
        $sql->where('`' . bq_sql(CMS::$definition['primary']) . '` = ' . (int) $id_cms);
        $id_cms_category = Db::read_only()->get_value($sql);
        if (empty($id_cms_category)) {
            return '';
        }
        $subcategories = $this->find_cms_category_subcategories($id_cms_category, $id_lang);
        return trim($subcategories, '/');
    }
    /**
     * @param int $idCmsCategory
     * @param int $idLang
     *
     * @throws PrestaShopException
     */
    protected function find_cms_category_subcategories($id_cms_category, $id_lang): string
    {
        if (empty($id_cms_category) || $id_cms_category === 1) {
            return '';
        }
        $subcategories = '';
        while ($id_cms_category > 1) {
            $subcategory = new Cms_Category($id_cms_category);
            $subcategories = $subcategory->link_rewrite[$id_lang] . '/' . $subcategories;
            $id_cms_category = $subcategory->id_parent;
        }
        return trim($subcategories, '/');
    }
    /**
     * @param int|CMSCategory $cmsCategory
     * @param string|null $alias
     * @param int|null $idLang
     * @param int|null $idShop
     * @param bool $relativeProtocol
     *
     * @throws PrestaShopException
     */
    public function get_cms_category_link($cms_category, $alias = null, $id_lang = null, $id_shop = null, $relative_protocol = false): string
    {
        if (empty($id_lang)) {
            $id_lang = Context::get_context()->language->id;
        }
        if (empty($id_shop)) {
            $id_shop = Context::get_context()->shop->id;
        }
        $url = $this->get_base_link($id_shop, null, $relative_protocol) . $this->get_lang_link($id_lang, null, $id_shop);
        $dispatcher = Dispatcher::get_instance();
        if (!is_object($cms_category)) {
            $cms_category = new Cms_Category($cms_category, $id_lang);
        }
        if (is_array($cms_category->link_rewrite) && isset($cms_category->link_rewrite[(int) $id_lang])) {
            $cms_category->link_rewrite = $cms_category->link_rewrite[(int) $id_lang];
        }
        if (is_array($cms_category->meta_keywords) && isset($cms_category->meta_keywords[(int) $id_lang])) {
            $cms_category->meta_keywords = $cms_category->meta_keywords[(int) $id_lang];
        }
        if (is_array($cms_category->meta_title) && isset($cms_category->meta_title[(int) $id_lang])) {
            $cms_category->meta_title = $cms_category->meta_title[(int) $id_lang];
        }
        // Set available keywords
        $params = [];
        $params['id'] = $cms_category->id;
        $params['rewrite'] = !$alias ? $cms_category->link_rewrite : $alias;
        $params['meta_keywords'] = Tools::str2url($cms_category->meta_keywords);
        $params['meta_title'] = Tools::str2url($cms_category->meta_title);
        $id_parent = $this->find_cms_category_parent($cms_category->id_cms_category);
        if (empty($id_parent)) {
            $params['categories'] = '';
        } else {
            $params['categories'] = $this->find_cms_category_subcategories($id_parent, $id_lang);
        }
        return $url . $dispatcher->create_url('cms_category_rule', $id_lang, $params, $this->allow, '', $id_shop);
    }
    /**
     * @param int $idCmsCategory
     *
     * @throws PrestaShopException
     */
    protected function find_cms_category_parent($id_cms_category): int
    {
        $sql = new Db_Query();
        $sql->select('`id_parent`');
        $sql->from(bq_sql(Cms_Category::$definition['table']));
        $sql->where('`' . bq_sql(Cms_Category::$definition['primary']) . '` = ' . (int) $id_cms_category);
        $id_parent = Db::read_only()->get_value($sql);
        if (empty($id_parent)) {
            return 0;
        }
        return (int) $id_parent;
    }
    /**
     * Create a link to a module
     *
     * @param string $module Module name
     * @param string $controller
     * @param bool|null $ssl
     * @param int $idLang
     * @param int|null $idShop
     * @param bool $relativeProtocol
     *
     * @return string
     * @throws PrestaShopException
     */
    public function get_module_link(string $module, $controller = 'default', array $params = [], $ssl = null, $id_lang = null, $id_shop = null, $relative_protocol = false)
    {
        if (!$id_lang) {
            $id_lang = Context::get_context()->language->id;
        }
        $url = $this->get_base_link($id_shop, $ssl, $relative_protocol) . $this->get_lang_link($id_lang, null, $id_shop);
        $controller = $controller ?: 'default';
        $dispatcher = Dispatcher::get_instance();
        // allow passing full module routeId instead of a controller
        if ($info = $dispatcher->is_module_controller_route($controller)) {
            if ($module === $info['module'] && $dispatcher->has_route($controller)) {
                $controller = $info['controller'];
            }
        }
        // Set available keywords
        $params['module'] = $module;
        $params['controller'] = $controller;
        // If the module has its own route ... just use it !
        if ($dispatcher->has_route('module-' . $module . '-' . $controller, $id_lang, $id_shop)) {
            return $this->get_page_link('module-' . $module . '-' . $controller, $ssl, $id_lang, $params);
        }
        return $url . $dispatcher->create_url('module', $id_lang, $params, $this->allow, '', $id_shop);
    }
    /**
     * Create a simple link
     *
     * @param string $controller
     * @param bool $ssl
     * @param int $idLang
     * @param string|array|null $request
     * @param bool $requestUrlEncode Use URL encode
     * @param int|null $idShop
     * @param bool $relativeProtocol
     *
     * @return string Page link
     *
     * @throws PrestaShopException
     */
    public function get_page_link($controller, $ssl = null, $id_lang = null, $request = null, $request_url_encode = false, $id_shop = null, $relative_protocol = false): string
    {
        //If $controller contains '&' char, it means that $controller contains request data and must be parsed first
        $p = strpos($controller, '&');
        if ($p !== false) {
            $request = substr($controller, $p + 1);
            $request_url_encode = false;
            $controller = substr($controller, 0, $p);
        }
        $controller = Tools::str_replace_first('.php', '', $controller);
        if (!$id_lang) {
            $id_lang = (int) Context::get_context()->language->id;
        }
        //need to be unset because getModuleLink need those params when rewrite is enable
        if (is_array($request)) {
            if (isset($request['module'])) {
                unset($request['module']);
            }
            if (isset($request['controller'])) {
                unset($request['controller']);
            }
        } else if ($request) {
            $request = html_entity_decode($request);
            if ($request_url_encode) {
                $request = urlencode($request);
            }
            parse_str($request, $request);
        } else {
            $request = [];
        }
        $uri_path = Dispatcher::get_instance()->create_url($controller, $id_lang, $request, false, '', $id_shop);
        return $this->get_base_link($id_shop, $ssl, $relative_protocol) . $this->get_lang_link($id_lang, null, $id_shop) . ltrim((string) $uri_path, '/');
    }
    /**
     * @param string $url
     * @param int $p
     */
    public function go_page($url, $p): string
    {
        $url = rtrim(str_replace('?&', '?', $url), '?');
        return $url . ($p == 1 ? '' : (!strstr($url, '?') ? '?' : '&') . 'p=' . (int) $p);
    }
    /**
     * Get pagination link
     *
     * @param string $type Controller name
     * @param object|int $idObject
     * @param bool $nb Show nb element per page attribute
     * @param bool $sort Show sort attribute
     * @param bool $pagination Show page number attribute
     * @param bool $array If false return an url, if true return an array
     *
     * @return array|string
     *
     * @throws PrestaShopException
     */
    public function get_pagination_link($type, $id_object, $nb = false, $sort = false, $pagination = false, $array = false)
    {
        // If no parameter $type, try to get it by using the controller name
        if (!$type && !$id_object) {
            $method_name = 'get' . Dispatcher::get_instance()->get_controller() . 'Link';
            if (method_exists($this, $method_name) && isset($_GET['id_' . Dispatcher::get_instance()->get_controller()])) {
                $type = Dispatcher::get_instance()->get_controller();
                $id_object = $_GET['id_' . $type];
            }
        }
        if ($type && $id_object) {
            $url = $this->{'get' . $type . 'Link'}($id_object, null);
        } else {
            if (isset(Context::get_context()->controller->php_self)) {
                $name = Context::get_context()->controller->php_self;
            } else {
                $name = Dispatcher::get_instance()->get_controller();
            }
            $url = $this->get_page_link($name);
        }
        $vars = [];
        $vars_nb = ['n'];
        $vars_sort = ['orderby', 'orderway'];
        $vars_pagination = ['p'];
        foreach ($_GET as $k => $value) {
            if ($k != 'id_' . $type && $k != 'controller') {
                if (Configuration::get('PS_REWRITING_SETTINGS') && ($k == 'isolang' || $k == 'id_lang')) {
                    continue;
                }
                $if_nb = !$nb || !in_array($k, $vars_nb);
                $if_sort = !$sort || !in_array($k, $vars_sort);
                $if_pagination = !$pagination || !in_array($k, $vars_pagination);
                if ($if_nb && $if_sort && $if_pagination) {
                    if (!is_array($value)) {
                        $vars[urlencode((string) $k)] = $value;
                    } else {
                        foreach (explode('&', http_build_query([$k => $value], '', '&')) as $val) {
                            $data = explode('=', $val);
                            $vars[urldecode($data[0])] = $data[1];
                        }
                    }
                }
            }
        }
        if (!$array) {
            if (count($vars)) {
                return $url . (!strstr((string) $url, '?') && ($this->allow == 1 || $url == $this->url) ? '?' : '&') . http_build_query($vars, '', '&');
            }
            return $url;
        }
        $vars['requestUrl'] = $url;
        if ($type && $id_object) {
            $vars['id_' . $type] = is_object($id_object) ? (int) $id_object->id : (int) $id_object;
        }
        if (!$this->allow == 1) {
            $vars['controller'] = Dispatcher::get_instance()->get_controller();
        }
        return $vars;
    }
    /**
     * @param string $orderby
     * @param string $orderway
     *
     */
    public function add_sort_details(string $url, $orderby, $orderway): string
    {
        return $url . (!strstr($url, '?') ? '?' : '&') . 'orderby=' . urlencode($orderby) . '&orderway=' . urlencode($orderway);
    }
    /**
     * @param string $url
     */
    public function match_quick_link($url): bool
    {
        $quicklink = static::get_quick_link($url);
        if (isset($quicklink) && $quicklink === static::get_quick_link($_SERVER['REQUEST_URI'])) {
            return true;
        }
        return false;
    }
    /**
     * @param string $url
     */
    public static function get_quick_link($url): string
    {
        $parsed_url = parse_url($url);
        $output = [];
        if (is_array($parsed_url) && isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $output);
            unset($output['token'], $output['conf'], $output['id_quick_access']);
        }
        return http_build_query($output);
    }
    /**
     * Returns product image types that are protected using watermark functionality
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private static function get_watermark_image_types()
    {
        static $watermark_types = null;
        if (is_null($watermark_types)) {
            $watermark_types = [];
            $selected_types = Configuration::get('WATERMARK_TYPES');
            if ($selected_types) {
                $selected_types = array_map(intval(...), explode(',', $selected_types));
                if ($selected_types) {
                    $hash = Configuration::get('WATERMARK_HASH');
                    foreach (Image_Type::get_images_types(Image_Entity::ENTITY_TYPE_PRODUCTS) as $image_type) {
                        if (in_array((int) $image_type['id_image_type'], $selected_types)) {
                            $image_type_name = $image_type['name'];
                            $watermark_types[$image_type_name] = $image_type_name . '-' . $hash;
                        }
                    }
                }
            }
        }
        return $watermark_types;
    }
    /**
     * This method returns uri to default product image, for example /img/p/en-default-Niara_home.jpg
     *
     * @param string $iso language iso code for which to display image
     * @param string $formattedType formatted image type, ie. 'Niara_home'
     * @param bool $highDpi true, if high resolution image should be displayed
     * @param string $preferredExtension preferred image extension ['jpg', 'png', 'gif', webp']
     * @param bool $returnFullUri basically adds _PS_CORE_DIR_ if true
     *
     * @return string | false
     * @throws PrestaShopException
     */
    public function get_default_image_uri(string $iso, string $formatted_type, bool $high_dpi, string $preferred_extension = '', bool $return_full_uri = false)
    {
        $type_dimension = $formatted_type ? '-' . $formatted_type : '';
        $high_dpi_dimension = $high_dpi ? '2x' : '';
        $iso_candidates = array_unique(array_filter([$iso, Context::get_context()->language->iso_code, Language::get_iso_by_id(Configuration::get('PS_LANG_DEFAULT')), 'en'], ['Validate', 'isLangIsoCode']));
        // build list of candidate image files
        $file_name_candidates = [];
        foreach ($iso_candidates as $iso_candidate) {
            $file_name_candidates[] = $iso_candidate . '-default' . $type_dimension . $high_dpi_dimension;
            $file_name_candidates[] = $iso_candidate . '-default' . $type_dimension;
            $file_name_candidates[] = $iso_candidate . '-default' . $high_dpi_dimension;
            $file_name_candidates[] = $iso_candidate . '-default';
        }
        $file_name_candidates = array_unique($file_name_candidates);
        foreach ($file_name_candidates as $candidate) {
            if ($source_image = Image_Manager::get_source_image(_PS_LANG_IMG_DIR_, $candidate, $preferred_extension)) {
                return $return_full_uri ? $source_image : str_replace(_PS_LANG_IMG_DIR_, _THEME_LANG_DIR_, $source_image);
            }
        }
        // Default image was not found
        return false;
    }
    /**
     * This method returns uri to default product image, for example /img/p/en-default-Niara_home.jpg
     *
     * @param string $iso language iso code for which to display image
     * @param string $formattedType formatted image type, ie. 'Niara_home'
     * @param bool $highDpi true, if high resolution image should be displayed
     * @param string $preferredExtension preferred image extension ['jpg', 'webp']
     *
     * @return string|false
     *
     * @throws PrestaShopException
     */
    public function get_product_default_image_uri(string $iso, string $formatted_type, bool $high_dpi, string $preferred_extension)
    {
        return $this->get_default_image_uri($iso, $formatted_type, $high_dpi, $preferred_extension);
    }
    /**
     * This method returns uri to product image, if it exists
     *
     *
     * @return string|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function get_product_image_uri(int $image_id, string $formatted_type, bool $high_dpi, string $preferred_extension, string $name): string|false
    {
        // ids can either be single number, or in format id_product-id_image
        $type_dimension = $formatted_type ? '-' . $formatted_type : '';
        $high_dpi_dimension = $high_dpi ? '2x' : '';
        // resolve image dir
        $img_dir = Image::get_img_folder_static($image_id);
        // build list of candidate image files
        $candidates = [];
        $candidates[$image_id . $type_dimension . $high_dpi_dimension] = $image_id . $type_dimension . '/' . $name . $high_dpi_dimension;
        $candidates[$image_id . $type_dimension] = $image_id . $type_dimension . '/' . $name;
        $candidates[$image_id . $high_dpi_dimension] = $image_id . '/' . $name . $high_dpi_dimension;
        $candidates[$image_id] = $image_id . '/' . $name;
        // find first existing file
        foreach ($candidates as $file_name => $friendly_uri) {
            if ($source_image = Image_Manager::get_source_image(_PS_PROD_IMG_DIR_ . $img_dir, $file_name, $preferred_extension)) {
                if ($this->allow) {
                    $source_image_extension = substr(strrchr($source_image, '.'), 1);
                    return __PS_BASE_URI__ . 'products/' . $friendly_uri . '.' . $source_image_extension;
                }
                $relative_path = str_replace(_PS_PROD_IMG_DIR_, '', $source_image);
                return _THEME_PROD_DIR_ . $relative_path;
            }
        }
        return false;
    }
    /**
     * @param mixed $name
     * @param string $default
     *
     * @return string
     */
    protected static function resolve_name($name, $default)
    {
        if (is_array($name)) {
            $language_id = Context::get_context()->language->id;
            if (isset($name[$language_id])) {
                return (string) $name[$language_id];
            }
            foreach ($name as $value) {
                if (is_string($value)) {
                    return $value;
                }
            }
        }
        return $default;
    }
    /**
     * Returns Product object from identifier. Object might not exists
     *
     * @param int|array|Product $identifier
     * @param int|null $idLang
     * @param int|null $idShop
     *
     *
     * @throws PrestaShopException
     */
    protected function get_product_object($identifier, $id_lang, $id_shop): Product
    {
        if ($identifier instanceof Product) {
            return $identifier;
        }
        if (is_int($identifier)) {
            return new Product($identifier, false, $id_lang, $id_shop);
        }
        if (is_array($identifier) && isset($identifier['id_product'])) {
            return new Product((int) $identifier['id_product'], false, $id_lang, $id_shop);
        }
        if (is_object($identifier) && property_exists($identifier, 'id')) {
            return new Product((int) $identifier->id, false, $id_lang, $id_shop);
        }
        return new Product((int) $identifier, false, $id_lang, $id_shop);
    }
    /**
     *
     * @throws PrestaShopException
     */
    public function get_combination_hash_url(int $product_id, int $combination_id): string
    {
        $attributes = Product::get_attributes_params($product_id, $combination_id);
        $anchor = '#';
        $sep = Configuration::get('PS_ATTRIBUTE_ANCHOR_SEPARATOR');
        foreach ($attributes as $attribute) {
            $attribute_id = (int) $attribute['id_attribute'];
            $attribute_group_name = str_replace($sep, '_', Tools::str2url($attribute['group']));
            $attribute_name = str_replace($sep, '_', Tools::str2url($attribute['name']));
            $anchor .= '/' . $attribute_id . $sep . $attribute_group_name . $sep . $attribute_name;
        }
        return $anchor;
    }
}