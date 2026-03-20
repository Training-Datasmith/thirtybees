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
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
namespace Thirtybees\Core\Smarty\Cache;

use Cache;
use Smarty_cache_Resource_custom;
/**
 * Class CacheResourceMysqlCore
 */
class Cache_Resource_Server_Side_Cache_Core extends Smarty_cache_Resource_custom
{
    public function __construct(protected \Cache $cache)
    {
    }
    /**
     * fetch cached content and its modification time from server side cache
     *
     * @param string $id unique cache content identifier
     * @param string $name template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     * @param string $content cached content
     * @param int $mtime cache modification timestamp (epoch)
     *
     * @return void
     *
     */
    protected function fetch($id, $name, $cache_id, $compile_id, &$content, &$mtime)
    {
        $cache_key = $this->get_cache_key($name, $cache_id, $compile_id);
        $value = $this->cache->get($cache_key);
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (is_array($value) && isset($value['mtime'])) {
            $mtime = (int) $value['mtime'];
            $content = $value['content'];
        } else {
            $content = null;
            $mtime = null;
        }
    }
    /**
     * Fetch cached content's modification timestamp from server side cache
     *
     * @param string $id unique cache content identifier
     * @param string $name template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     *
     * @return int|boolean timestamp (epoch) the template was modified, or false if not found
     */
    protected function fetch_timestamp($id, $name, $cache_id, $compile_id)
    {
        $value = $this->cache->get($this->get_cache_key($name, $cache_id, $compile_id));
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (is_array($value) && isset($value['mtime'])) {
            return (int) $value['mtime'];
        }
        return false;
    }
    /**
     * Save content to server side cache
     *
     * @param string $id unique cache content identifier
     * @param string $name template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     * @param int|null $expTime seconds till expiration time in seconds or null
     * @param string $content content to cache
     *
     * @return bool success
     */
    protected function save($id, $name, $cache_id, $compile_id, $exp_time, $content)
    {
        $value = ['mtime' => time(), 'content' => $content];
        return $this->cache->set($this->get_cache_key($name, $cache_id, $compile_id), $value, $exp_time);
    }
    /**
     * Delete content from cache
     *
     * @param string $name template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     * @param int|null $expTime seconds till expiration or null
     *
     * @return int number of deleted caches
     */
    protected function delete($name, $cache_id, $compile_id, $exp_time)
    {
        if ($name === null && $cache_id === null && $compile_id === null) {
            $this->cache->flush();
            return -1;
        }
        $key = $this->get_cache_key($name, $cache_id, $compile_id);
        if ($name && !$cache_id) {
            $key = $key . '*';
        }
        $deleted = $this->cache->delete($key);
        if (is_array($deleted)) {
            return count($deleted);
        }
        return 1;
    }
    /**
     * @param string $name
     * @param string $cacheId
     * @param string $compileId
     *
     * @return string
     */
    protected function get_cache_key($name, $cache_id, $compile_id)
    {
        $parts = ['smarty'];
        if ($name) {
            $name = trim(str_replace(_PS_ROOT_DIR_, '', $name), '/');
            $parts[] = $name;
        }
        if ($cache_id) {
            $cache_id = str_replace('*', '_', $cache_id);
            $parts[] = $cache_id;
        }
        if ($compile_id) {
            $compile_id = str_replace('*', '_', $compile_id);
            $parts[] = $compile_id;
        }
        return implode('~', $parts);
    }
}