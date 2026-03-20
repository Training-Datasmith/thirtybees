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
 * Class CacheMemcacheCore
 */
class Cache_Memcache_Core extends Cache
{
    /**
     * @var Memcache
     */
    protected $memcache;
    /**
     * @var bool Connection status
     */
    protected $is_connected = false;
    /**
     * CacheMemcacheCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->is_connected = $this->connect();
        if (!$this->is_connected) {
            trigger_error('Failed to connect to memcache', E_USER_WARNING);
        }
    }
    /**
     * CacheMemcacheCore destructor.
     */
    public function __destruct()
    {
        $this->close();
    }
    /**
     * Connect to memcache server
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function connect()
    {
        if (!static::check_environment()) {
            return false;
        }
        $servers = static::get_memcached_servers();
        if (!$servers) {
            return false;
        }
        try {
            $this->memcache = new Memcache();
            foreach ($servers as $server) {
                $this->memcache->add_server($server['ip'], $server['port'], true, (int) $server['weight']);
            }
            return (bool) @$this->memcache->get_version();
        } catch (Throwable) {
            return false;
        }
    }
    /***
     * Returns true, if we are connected to memcache server
     *
     * @return bool
     */
    public function is_available()
    {
        return $this->is_connected;
    }
    /**
     * @return bool
     */
    public static function check_environment()
    {
        return class_exists('Memcache') && extension_loaded('memcache');
    }
    /**
     * Cache a data
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     *
     * @return bool
     */
    protected function _set($key, $value, $ttl = 0)
    {
        if (!$this->is_connected) {
            return false;
        }
        $expires = $ttl ? time() + $ttl : 0;
        return $this->memcache->set(static::map_key($key), $value, 0, $expires);
    }
    /**
     * Retrieve a cached data by key
     *
     * @param string $key
     *
     * @return array|false|string
     */
    protected function _get($key)
    {
        if (!$this->is_connected) {
            return false;
        }
        return $this->memcache->get(static::map_key($key));
    }
    /**
     * Check if a data is cached by key
     *
     * @param string $key
     *
     * @return bool
     */
    protected function _exists($key)
    {
        if (!$this->is_connected) {
            return false;
        }
        return $this->memcache->get(static::map_key($key)) !== false;
    }
    /**
     * Delete a data from the cache by key
     *
     * @param string $key
     *
     * @return bool
     */
    protected function _delete($key)
    {
        if (!$this->is_connected) {
            return false;
        }
        return $this->memcache->delete(static::map_key($key));
    }
    /**
     * Write keys index
     */
    protected function _write_keys()
    {
        // this implementation do not use keys
    }
    /**
     * Clean all cached data
     *
     * @return bool
     */
    public function flush()
    {
        if (!$this->is_connected) {
            return false;
        }
        return $this->memcache->flush();
    }
    /**
     * Store a data in cache
     *
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     *
     * @return bool
     */
    public function set($key, $value, $ttl = 0)
    {
        return $this->_set($key, $value, $ttl);
    }
    /**
     * Retrieve a data from cache
     *
     * @param string $key
     *
     * @return array|false|string
     */
    public function get($key)
    {
        return $this->_get($key);
    }
    /**
     * Check if a data is cached
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->_exists($key);
    }
    /**
     * Delete one or several data from cache (* joker can be used, but avoid it !)
     *    E.g.: delete('*'); delete('my_prefix_*'); delete('my_key_name');
     *
     * @param string $key
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete($key)
    {
        if (!$this->is_connected) {
            return false;
        }
        if ($key == '*') {
            $this->flush();
        } elseif (!str_contains($key, '*')) {
            $this->_delete($key);
        } else {
            // Get keys (this code comes from Doctrine 2 project)
            $pattern = str_replace('\*', '.*', preg_quote($key));
            $servers = static::get_memcached_servers();
            if (is_array($servers) && count($servers) > 0 && method_exists('Memcache', 'getStats')) {
                $all_slabs = $this->memcache->get_stats('slabs');
            }
            if (isset($all_slabs) && is_array($all_slabs)) {
                foreach ($all_slabs as $slabs) {
                    if (is_array($slabs)) {
                        foreach (array_keys($slabs) as $i => $slab_id) {
                            if (is_int($i)) {
                                $dump = $this->memcache->get_stats('cachedump', $i);
                                if ($dump) {
                                    foreach ($dump as $entries) {
                                        if ($entries) {
                                            foreach ($entries as $key => $data) {
                                                if (preg_match('#^' . $pattern . '$#', (string) $key)) {
                                                    $this->_delete($key);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return true;
    }
    /**
     * Close connection to memcache server
     *
     * @return bool
     */
    protected function close()
    {
        if (!$this->is_connected) {
            return false;
        }
        return $this->memcache->close();
    }
    /**
     * Add a memcache server
     *
     * @param string $ip
     * @param int $port
     * @param int $weight
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function add_server($ip, $port, $weight)
    {
        return Db::get_instance()->execute('INSERT INTO ' . _DB_PREFIX_ . 'memcached_servers (ip, port, weight) VALUES(\'' . p_sql($ip) . '\', ' . (int) $port . ', ' . (int) $weight . ')', false);
    }
    /**
     * Get list of memcached servers
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_memcached_servers()
    {
        return Db::read_only()->get_array('SELECT * FROM ' . _DB_PREFIX_ . 'memcached_servers');
    }
    /**
     * Delete a memcache server
     *
     * @param int $idServer
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function delete_server($id_server)
    {
        return Db::get_instance()->execute('DELETE FROM ' . _DB_PREFIX_ . 'memcached_servers WHERE id_memcached_server=' . (int) $id_server);
    }
    /**
     * @return string
     */
    protected static function map_key($key)
    {
        if (strlen((string) $key) > 250) {
            return Tools::encrypt($key);
        }
        return $key;
    }
}