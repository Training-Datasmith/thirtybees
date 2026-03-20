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
 * Class CacheFsCore
 */
class Cache_Fs_Core extends Cache
{
    /**
     * @var int Number of subfolders to dispatch cached filenames
     */
    protected int $depth;
    /**
     * CacheFsCore constructor.
     *
     * @throws PrestaShopException
     */
    protected function __construct()
    {
        $this->depth = (int) Db::read_only()->get_value('SELECT value FROM ' . _DB_PREFIX_ . 'configuration WHERE name= \'PS_CACHEFS_DIRECTORY_DEPTH\'');
        $keys_filename = $this->get_filename(static::KEYS_NAME);
        if (file_exists($keys_filename)) {
            $this->keys = json_decode(file_get_contents($keys_filename), true);
            if (!is_array($this->keys)) {
                $this->keys = [];
            }
        }
    }
    /**
     * @return bool
     */
    public static function check_environment()
    {
        if (!is_dir(_PS_CACHEFS_DIRECTORY_)) {
            @mkdir(_PS_CACHEFS_DIRECTORY_, 0777, true);
        }
        return is_writable(_PS_CACHEFS_DIRECTORY_);
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
        $defined_umask = defined('_TB_UMASK_') ? _TB_UMASK_ : 00;
        $previous_umask = @umask($defined_umask);
        $result = @file_put_contents($this->get_filename($key), json_encode($value));
        @umask($previous_umask);
        return $result;
    }
    /**
     * Retrieve a cached data by key
     *
     * @param string $key
     *
     * @return mixed|false
     */
    protected function _get($key)
    {
        if (isset($this->keys[$key]) && $this->keys[$key] > 0 && $this->keys[$key] < time()) {
            $this->delete($key);
            return false;
        }
        $filename = $this->get_filename($key);
        if (!file_exists($filename)) {
            $this->delete($key);
            return false;
        }
        $file = file_get_contents($filename);
        if (!$file) {
            $this->delete($key);
            return false;
        }
        return json_decode($file, true);
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
        if (isset($this->keys[$key]) && $this->keys[$key] > 0 && $this->keys[$key] < time()) {
            $this->delete($key);
            return false;
        }
        return isset($this->keys[$key]) && file_exists($this->get_filename($key));
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
        $filename = $this->get_filename($key);
        if (!file_exists($filename)) {
            return true;
        }
        return unlink($filename);
    }
    /**
     * Write keys index
     */
    protected function _write_keys()
    {
        $defined_umask = defined('_TB_UMASK_') ? _TB_UMASK_ : 00;
        $previous_umask = @umask($defined_umask);
        @file_put_contents($this->get_filename(static::KEYS_NAME), json_encode($this->keys));
        @umask($previous_umask);
    }
    /**
     * Clean all cached data
     *
     * @return bool
     */
    public function flush()
    {
        $this->delete('*');
        return true;
    }
    /**
     * Delete cache directory
     */
    public static function delete_cache_directory(): void
    {
        Tools::delete_directory(_PS_CACHEFS_DIRECTORY_, false);
    }
    /**
     * Create cache directory
     *
     * @param int $levelDepth
     * @param string $directory
     */
    public static function create_cache_directories($level_depth, $directory = false): void
    {
        if (!$directory) {
            $directory = _PS_CACHEFS_DIRECTORY_;
        }
        $chars = '0123456789abcdef';
        for ($i = 0, $length = strlen($chars); $i < $length; $i++) {
            $new_dir = $directory . $chars[$i] . '/';
            if (mkdir($new_dir)) {
                if (chmod($new_dir, 0777)) {
                    if ($level_depth - 1 > 0) {
                        Cache_Fs::create_cache_directories($level_depth - 1, $new_dir);
                    }
                }
            }
        }
    }
    /**
     * Transform a key into its absolute path
     *
     * @param string $key
     * @return string
     */
    protected function get_filename($key)
    {
        $key = md5($key);
        $path = _PS_CACHEFS_DIRECTORY_;
        for ($i = 0; $i < $this->depth; $i++) {
            $path .= $key[$i] . '/';
        }
        if (!is_dir($path)) {
            $defined_umask = defined('_TB_UMASK_') ? _TB_UMASK_ : 00;
            $previous_umask = @umask($defined_umask);
            @mkdir($path, 0777, true);
            @umask($previous_umask);
        }
        return $path . $key;
    }
}