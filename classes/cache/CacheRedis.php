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
use Thirtybees\Core\Dependency_Injection\Service_Locator;
use Thirtybees\Core\Error\Error_Utils;
/**
 * This class require Redis server to be installed
 */
class Cache_Redis_Core extends Cache
{
    public const KEYS_PREFIX_CONFIG_KEY = 'TB_REDIS_KEYS_PREFIX';
    /**
     * @var bool Connection status
     */
    public $is_connected = false;
    /**
     * @var Redis|RedisArray $redis
     */
    protected $redis;
    /**
     * @var string
     */
    protected $keys_prefix;
    /**
     * CacheRedisCore constructor.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($keys_prefix = null)
    {
        $this->is_connected = $this->connect();
        $this->keys_prefix = $keys_prefix ?? static::resolve_keys_prefix();
        if (!$this->is_connected) {
            trigger_error('Failed to connect to redis', E_USER_WARNING);
        }
    }
    /**
     * @return bool
     */
    public static function check_environment()
    {
        return extension_loaded('redis');
    }
    /**
     * Connect to redis server or cluster
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function connect()
    {
        if (!static::check_environment()) {
            return false;
        }
        try {
            $servers = static::get_redis_servers();
            // no servers defined
            if (!$servers) {
                return false;
            }
            return count($servers) === 1 ? $this->connect_single_server($servers[0]) : $this->connect_cluster($servers);
        } catch (Redis_Exception) {
            return false;
        }
    }
    /**
     * Connect to single redis server
     *
     * @param array $serverConfig
     *
     * @return bool
     * @throws RedisException
     */
    protected function connect_single_server($server_config)
    {
        $this->redis = new Redis();
        if ($this->redis->pconnect($server_config['ip'], $server_config['port'])) {
            $this->redis->set_option(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
            return $this->auth_connection($server_config);
        }
        return false;
    }
    /**
     * Connects to redis cluster
     *
     * @param array[] $servers
     *
     * @return bool
     * @throws RedisException
     */
    protected function connect_cluster($servers)
    {
        $hosts = [];
        foreach ($servers as $server) {
            $hosts[] = $server['ip'] . ':' . $server['port'];
        }
        $this->redis = new Redis_Array($hosts, ['pconnect' => true]);
        $this->redis->set_option(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        $connected = true;
        foreach ($servers as $server_config) {
            $connected = $connected && $this->auth_connection($server_config);
        }
        return $connected;
    }
    /**
     * Authenticate redis connection. Returns true, if connection to redis server(s) is established
     *
     * @param array $serverConfig
     *
     * @return bool
     * @throws RedisException
     */
    protected function auth_connection($server_config)
    {
        if ($server_config['auth']) {
            return $this->redis->auth($server_config['auth']) === true;
        }
        $this->redis->select($server_config['db']);
        return (bool) $this->redis->ping();
    }
    /***
     * Returns true, if we are connected to redis cluster
     *
     * @return bool
     */
    public function is_available()
    {
        return $this->is_connected;
    }
    /**
     *Add a redis server
     *
     * @param string $ip IP address or hostname
     * @param int $port Port number
     * @param string $auth Authentication key
     * @param int $db Redis database ID
     *
     * @return bool Whether the server was successfully added
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function add_server($ip, $port, $auth, $db)
    {
        $sql = new Db_Query();
        $sql->select('count(*)');
        $sql->from('redis_servers');
        $sql->where('`ip` = \'' . p_sql($ip) . '\'');
        $sql->where('`port` = ' . (int) $port);
        $sql->where('`auth` = \'' . p_sql($auth) . '\'');
        $sql->where('`db` = ' . (int) $db);
        if (Db::read_only()->get_value($sql)) {
            return false;
        }
        return Db::get_instance()->insert('redis_servers', ['ip' => p_sql($ip), 'port' => (int) $port, 'auth' => p_sql($auth), 'db' => (int) $db], false, false);
    }
    /**
     * Get list of redis server information
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_redis_servers()
    {
        $sql = new Db_Query();
        $sql->select('*');
        $sql->from('redis_servers');
        return Db::read_only()->get_array($sql);
    }
    /**
     * Delete a redis server
     *
     * @param int $idServer Server ID
     *
     * @return bool Whether the server was successfully deleted
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function delete_server($id_server)
    {
        return Db::get_instance()->delete('redis_servers', '`id_redis_server` = ' . (int) $id_server, 0, false);
    }
    /**
     * Returns redis key to store all existing keys
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected static function resolve_keys_prefix()
    {
        if (defined(static::KEYS_PREFIX_CONFIG_KEY)) {
            return constant(static::KEYS_PREFIX_CONFIG_KEY);
        }
        $value = Configuration::get_global_value(static::KEYS_PREFIX_CONFIG_KEY);
        if (!$value) {
            $value = Tools::passwd_gen(6);
            Configuration::update_global_value(static::KEYS_PREFIX_CONFIG_KEY, $value);
        }
        return $value;
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
        try {
            return (bool) $this->redis->flush_db();
        } catch (Redis_Exception $e) {
            $this->log_exception($e);
            return false;
        }
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
     * @return mixed
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
     */
    public function delete($key)
    {
        if (!$this->is_connected) {
            return false;
        }
        if ($key == '*') {
            return $this->flush();
        }
        if (!str_contains($key, '*')) {
            return $this->_delete($key);
        }
        try {
            $keys = $this->redis->keys($this->map_key($key));
            $res = true;
            if (is_array($keys) && $keys) {
                return $this->redis->del($keys) && $res;
            }
            return $res;
        } catch (Redis_Exception $e) {
            $this->log_exception($e);
            return false;
        }
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
        $timeout = $ttl > 0 ? $ttl : null;
        $mapped_key = $this->map_key($key);
        try {
            return $this->redis->set($mapped_key, $value, $timeout);
        } catch (Redis_Exception $e) {
            $this->log_exception($e);
            return false;
        }
    }
    /**
     * @param string $key
     *
     * @return bool
     */
    protected function _exists($key)
    {
        if (!$this->is_connected) {
            return false;
        }
        return (bool) $this->_get($key);
    }
    /**
     * @param string $key
     *
     * @return mixed
     */
    protected function _get($key)
    {
        if (!$this->is_connected) {
            return false;
        }
        $mapped_key = $this->map_key($key);
        try {
            return $this->redis->get($mapped_key);
        } catch (Redis_Exception $e) {
            $this->log_exception($e);
            return false;
        }
    }
    /**
     * @param string $key
     *
     * @return bool
     */
    protected function _delete($key)
    {
        if (!$this->is_connected) {
            return false;
        }
        $mapped_key = $this->map_key($key);
        try {
            return $this->redis->del($mapped_key);
        } catch (Redis_Exception $e) {
            $this->log_exception($e);
            return false;
        }
    }
    /**
     * Write keys index
     */
    protected function _write_keys()
    {
        // this implementation do not use keys
    }
    /**
     * @param string $key
     *
     * @return string
     */
    protected function map_key($key)
    {
        return $this->keys_prefix . ':' . $key;
    }
    /**
     * @return void
     */
    protected function log_exception(Redis_Exception $e)
    {
        $error_handler = Service_Locator::get_instance()->get_error_handler();
        $description = Error_Utils::describe_exception($e);
        $error_handler->log_fatal_error($description);
    }
}