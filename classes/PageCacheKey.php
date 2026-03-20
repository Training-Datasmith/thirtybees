<?php

declare (strict_types=1);
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
/**
 * Class PageCacheKey - composite key for full page cache
 */
class Page_Cache_Key_Core
{
    /**
     * @var PageCacheKey|false|null
     */
    protected static $instance;
    /**
     * Creates new cache key and set its metadata
     *
     * @param string $entityType -- controller name
     * @param int $entityId - specific entity, for example product id
     * @param string $url
     * @param int $idCurrency
     * @param int $idLanguage
     * @param int $idCountry
     * @param int $idShop
     * @param int $idGroup
     */
    protected function __construct(public $entity_type, public $entity_id, public $url, public $id_currency, public $id_language, public $id_country, public $id_shop, public $id_group)
    {
    }
    /**
     * Returns unique hash for this key
     *
     * @return string
     */
    public function get_hash()
    {
        return Tools::encrypt('pagecache_public_' . $this->url . $this->id_currency . $this->id_language . $this->id_country . $this->id_shop . $this->id_group);
    }
    /**
     * Returns full page cache key for current request
     *
     * @return PageCacheKey | false
     * @throws PrestaShopException
     */
    public static function get()
    {
        if (is_null(static::$instance)) {
            static::$instance = static::resolve_page_key();
        }
        return static::$instance;
    }
    /**
     * Returns full page cache key for current request
     *
     * @return PageCacheKey|false
     * @throws PrestaShopException
     */
    protected static function resolve_page_key(): false|\Page_Cache_Key
    {
        // don't cache in back office
        if (defined('_PS_ADMIN_DIR_')) {
            return false;
        }
        // we can cache only GET request
        if (Tools::get_request_method() !== 'GET') {
            return false;
        }
        // don't cache when request contains 'no_cache=1'
        if (Tools::get_value('no_cache')) {
            return false;
        }
        // don't cache pages when live edit mode is enabled
        if (Tools::is_submit('live_edit') || Tools::is_submit('live_configurator_token')) {
            return false;
        }
        // ajax calls are not cached
        $ajax_calling = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && mb_strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($ajax_calling) {
            return false;
        }
        $context = Context::get_context();
        if (!$context->currency) {
            $currency = Tools::set_currency($context->cookie);
        } else {
            $currency = $context->currency;
        }
        // check that current controller can be cached
        $entity_type = Dispatcher::get_instance()->get_controller();
        $cacheable_controllers = json_decode(Configuration::get('TB_PAGE_CACHE_CONTROLLERS'), true);
        if (!in_array($entity_type, $cacheable_controllers)) {
            return false;
        }
        // this page can be cached -- let's compute cache key
        $protocol = Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://';
        $url = explode('?', (string) $_SERVER['REQUEST_URI']);
        $uri = $url[0];
        $query_string = $url[1] ?? '';
        if ($query_string === '') {
            $new_url = $protocol . $_SERVER['HTTP_HOST'] . $uri;
        } else {
            parse_str($query_string, $query_string_params);
            $params_to_ignore_str = Configuration::get('TB_PAGE_CACHE_IGNOREPARAMS');
            if ($params_to_ignore_str) {
                $params_to_ignore = explode(',', $params_to_ignore_str);
                if (is_array($params_to_ignore)) {
                    foreach ($params_to_ignore as $param) {
                        if (isset($query_string_params[$param])) {
                            unset($query_string_params[$param]);
                        }
                    }
                }
            }
            ksort($query_string_params);
            $new_query_string = http_build_query($query_string_params);
            $new_url = $protocol . $_SERVER['HTTP_HOST'] . $uri . '?' . $new_query_string;
        }
        $entity_id = Tools::get_int_value('id_' . $entity_type);
        return new Page_Cache_Key($entity_type, $entity_id, $new_url, (int) $currency->id, (int) $context->language->id, (int) $context->country->id, (int) $context->shop->id, (int) Group::get_current()->id);
    }
}