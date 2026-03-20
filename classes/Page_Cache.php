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
 * Class PageCache
 */
class Page_Cache_Core
{
    /**
     * How many seconds should the page remain in cache
     */
    public const CACHE_ENTRY_TTL = 86400;
    /**
     * @var PageCacheEntry|null holds current page cache entry
     */
    protected static $entry;
    /**
     * @return bool true if full page cache is enabled and user user is not
     *              logged in, else false.
     *
     * @throws PrestaShopException
     */
    public static function is_enabled(): bool
    {
        $page_cache_enabled = Cache::is_enabled() && Configuration::get('TB_PAGE_CACHE_ENABLED');
        $user_logged_in = !is_null(Context::get_context()->customer) && Context::get_context()->customer->is_logged();
        return $page_cache_enabled && !$user_logged_in;
    }
    /**
     * Insert new entry for current request into full page cache
     *
     * @param string $template
     * @throws PrestaShopException
     */
    public static function set($template): void
    {
        if (static::is_enabled()) {
            $key = Page_Cache_Key::get();
            if ($key) {
                $cache_entry = static::get();
                $cache_entry->set_content($template);
                if ($cache_entry->is_valid()) {
                    $hash = $key->get_hash();
                    $cache = Cache::get_instance();
                    $cache->set($hash, $cache_entry->serialize(), static::CACHE_ENTRY_TTL);
                    static::cache_key($hash, $key->id_currency, $key->id_language, $key->id_country, $key->id_shop, $key->entity_type, $key->entity_id);
                }
            }
        }
    }
    /**
     * Returns full page cache entry for current request
     *
     * @return PageCacheEntry
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get()
    {
        if (is_null(static::$entry)) {
            static::$entry = new Page_Cache_Entry();
            if (static::is_enabled()) {
                // check that there were no changes to hook list
                $hook_list_hash = static::get_hook_list_fingerprint();
                if ($hook_list_hash != Configuration::get('TB_HOOK_LIST_HASH')) {
                    // drain the cache if the hook list changed
                    Configuration::update_value('TB_HOOK_LIST_HASH', $hook_list_hash);
                    static::flush();
                } else {
                    $key = Page_Cache_Key::get();
                    if ($key) {
                        $cache = Cache::get_instance();
                        $serialized = $cache->get($key->get_hash());
                        if ($serialized) {
                            static::$entry->set_from_cache($serialized);
                        }
                    }
                }
            }
        }
        return static::$entry;
    }
    /**
     * Register cache key and set its metadata
     *
     * @param string $key
     * @param int $idCurrency
     * @param int $idLanguage
     * @param int $idCountry
     * @param int $idShop
     * @param string $entityType
     * @param int $idEntity
     */
    public static function cache_key($key, $id_currency, $id_language, $id_country, $id_shop, $entity_type, $id_entity): void
    {
        try {
            Db::get_instance()->insert('page_cache', ['cache_hash' => p_sql($key), 'id_currency' => (int) $id_currency, 'id_language' => (int) $id_language, 'id_country' => (int) $id_country, 'id_shop' => (int) $id_shop, 'entity_type' => p_sql($entity_type), 'id_entity' => (int) $id_entity], false, true, Db::ON_DUPLICATE_KEY);
        } catch (Exception) {
            // Hash already inserted
        }
    }
    /**
     * Invalidate an entity from the cache
     *
     * @param string $entityType
     * @param int|null $idEntity
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function invalidate_entity($entity_type, $id_entity = null): void
    {
        $keys_to_invalidate = [];
        $conn = Db::get_instance();
        if ($entity_type === 'product') {
            // Refresh the homepage
            $keys_to_invalidate = array_merge($keys_to_invalidate, static::get_keys_to_invalidate('index'));
            $conn->delete('page_cache', '`entity_type` = \'index\'');
            if ($id_entity) {
                // Invalidate product's categories only
                $product = new Product((int) $id_entity);
                if (Validate::is_loaded_object($product)) {
                    $categories = $product->get_categories();
                    foreach ($categories as $id_category) {
                        $keys_to_invalidate = array_merge($keys_to_invalidate, static::get_keys_to_invalidate('category', $id_category));
                        $conn->delete('page_cache', '`entity_type` = \'category\' AND `id_entity` = ' . (int) $id_category);
                    }
                }
            } else {
                // Invalidate all parent categories
                $keys_to_invalidate = array_merge($keys_to_invalidate, static::get_keys_to_invalidate('category'));
                $conn->delete('page_cache', '`entity_type` = \'category\'');
            }
        }
        $keys_to_invalidate = array_merge($keys_to_invalidate, static::get_keys_to_invalidate($entity_type, $id_entity));
        $conn->delete('page_cache', '`entity_type` = \'' . p_sql($entity_type) . '\'' . ($id_entity ? ' AND `id_entity` = ' . (int) $id_entity : ''));
        $cache = Cache::get_instance();
        foreach ($keys_to_invalidate as $item) {
            $cache->delete($item);
        }
    }
    /**
     * Flush all data
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function flush(): void
    {
        if (static::is_enabled()) {
            Cache::get_instance()->flush();
        }
        Db::get_instance()->delete('page_cache');
    }
    /**
     * Get keys to invalidate
     *
     * @param string $entityType
     * @param int|null $idEntity
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function get_keys_to_invalidate($entity_type, $id_entity = null): array
    {
        $sql = new Db_Query();
        $sql->select('`cache_hash`');
        $sql->from('page_cache');
        $sql->where('`entity_type` = \'' . p_sql($entity_type) . '\'');
        if ($id_entity) {
            $sql->where('`id_entity` = ' . (int) $id_entity);
        }
        $results = Db::read_only()->get_array($sql);
        return array_column($results, 'cache_hash');
    }
    /**
     * Return normalized list of all hooks that should be cached
     * @throws PrestaShopException
     * @return mixed[]|array<int, non-empty-array<(int<min, -1> | int<1, max>), 1>>
     */
    public static function get_cached_hooks(): array
    {
        $hook_settings = json_decode(Configuration::get('TB_PAGE_CACHE_HOOKS'), true);
        if (!is_array($hook_settings)) {
            return [];
        }
        $cached_hooks = [];
        foreach ($hook_settings as $id_module => $hook_arr) {
            $id_module = (int) $id_module;
            if ($id_module) {
                $module_hooks = [];
                foreach ($hook_arr as $id_hook => $bool) {
                    $id_hook = (int) $id_hook;
                    if ($id_hook && $bool) {
                        $module_hooks[$id_hook] = 1;
                    }
                }
                if ($module_hooks) {
                    $cached_hooks[$id_module] = $module_hooks;
                }
            }
        }
        return $cached_hooks;
    }
    /**
     * Modify hook cached status
     *
     * If $status is true, hook output will be cached. Otherwise content of
     * this hook will be refreshed with every page load
     *
     * @param int $idModule
     * @param int $idHook
     * @param bool $status
     *
     * @throws PrestaShopException
     */
    public static function set_hook_cache_status($id_module, $id_hook, $status): bool
    {
        $hook_settings = static::get_cached_hooks();
        $id_module = (int) $id_module;
        $id_hook = (int) $id_hook;
        if (!isset($hook_settings[$id_module])) {
            $hook_settings[$id_module] = [];
        }
        if ($status) {
            $hook_settings[$id_module][$id_hook] = 1;
        } else {
            unset($hook_settings[$id_module][$id_hook]);
            if (empty($hook_settings[$id_module])) {
                unset($hook_settings[$id_module]);
            }
        }
        if (Configuration::update_global_value('TB_PAGE_CACHE_HOOKS', json_encode($hook_settings))) {
            static::flush();
            return true;
        }
        return false;
    }
    /**
     * Calculates md5 hash of hook list
     *
     * This has is used to detect any changes to hook execution list, including
     * hook position, new modules, enabled/disabled modules, etc...
     *
     * In multistore environment, every shop will have different hash. That's fine,
     * because PageCacheKey has id_shop as a dimension
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_hook_list_fingerprint(): string
    {
        $hook_list = Hook::get_hook_module_list();
        $ctx = hash_init('md5');
        foreach ($hook_list as $id_hook => $module_list) {
            hash_update($ctx, $id_hook);
            foreach ($module_list as $id_module => $module_info) {
                hash_update($ctx, $id_module);
                hash_update($ctx, (string) $module_info['active']);
            }
        }
        return hash_final($ctx);
    }
}