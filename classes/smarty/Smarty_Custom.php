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
use Thirtybees\Core\Smarty\Cache\Cache_Resource_Mysql;
use Thirtybees\Core\Smarty\Cache\Cache_Resource_Server_Side_Cache;
/**
 * Class SmartyCustomCore
 */
class Smarty_Custom_Core extends Smarty
{
    public const CACHING_TYPE_FILESYSTEM = 'filesystem';
    public const CACHING_TYPE_MYSQL = 'mysql';
    public const CACHING_TYPE_SSC = 'ssc';
    /**
     * @var array stack trace for currently rendering templates
     */
    public static $trace = [];
    /**
     * SmartyCustomCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();
        $this->template_class = 'Smarty_Custom_Template';
        $this->resolve_caching_type();
    }
    /**
     * @throws PrestaShopException
     */
    protected function resolve_caching_type()
    {
        $caching_type = Configuration::get(Configuration::SMARTY_CACHING_TYPE);
        if ($caching_type === static::CACHING_TYPE_MYSQL) {
            $this->register_cache_resource('mysql', new Cache_Resource_Mysql(Encryptor::get_instance()));
            $this->caching_type = 'mysql';
        } elseif ($caching_type === static::CACHING_TYPE_SSC && Cache::is_enabled()) {
            $cache = Cache::get_instance();
            if ($cache->is_available()) {
                $this->register_cache_resource('ssc', new Cache_Resource_Server_Side_Cache($cache));
                $this->caching_type = 'ssc';
            } else {
                $this->caching_type = 'file';
            }
        } else {
            // fallback to built-in cache resource
            $this->caching_type = 'file';
        }
    }
    /**
     * Delete compiled template file (lazy delete if resource_name is not specified)
     *
     * @param string $resourceName template name
     * @param string $compileId compile id
     * @param int $expTime expiration time
     *
     * @return int number of template files deleted
     *
     * @throws PrestaShopException
     */
    public function clear_compiled_template($resource_name = null, $compile_id = null, $exp_time = null)
    {
        if ($resource_name == null) {
            Db::get_instance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'smarty_last_flush` (`type`, `last_flush`) VALUES (\'compile\', FROM_UNIXTIME(' . time() . '))');
            return 0;
        }
        return parent::clear_compiled_template($resource_name, $compile_id, $exp_time);
    }
    /**
     * Mark all template files to be regenerated
     *
     * @param int $expTime expiration time
     * @param string $type resource type
     *
     * @return bool number of cache files which needs to be updated
     *
     * @throws PrestaShopException
     */
    public function clear_all_cache($exp_time = null, $type = null)
    {
        Db::get_instance()->execute('REPLACE INTO `' . _DB_PREFIX_ . 'smarty_last_flush` (`type`, `last_flush`) VALUES (\'template\', FROM_UNIXTIME(' . time() . '))');
        return $this->delete_from_lazy_cache(null, null, null);
    }
    /**
     * Delete the current template from the lazy cache or the whole cache if no template name is given
     *
     * @param string $template template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     *
     * @return bool|int
     *
     * @throws PrestaShopException
     */
    public function delete_from_lazy_cache($template, $cache_id, $compile_id)
    {
        $conn = Db::get_instance();
        if (!$template) {
            return $conn->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'smarty_lazy_cache`', false);
        }
        $template_md5 = md5($template);
        $sql = 'DELETE FROM `' . _DB_PREFIX_ . 'smarty_lazy_cache`
							WHERE template_hash=\'' . p_sql($template_md5) . '\'';
        if ($cache_id != null) {
            $sql .= ' AND cache_id LIKE "' . p_sql((string) $cache_id) . '%"';
        }
        if ($compile_id != null) {
            if (strlen($compile_id) > 32) {
                $compile_id = md5($compile_id);
            }
            $sql .= ' AND compile_id="' . p_sql((string) $compile_id) . '"';
        }
        $conn->execute($sql, false);
        return $conn->Affected_Rows();
    }
    /**
     * Mark file to be regenerated for a specific template
     *
     * @param string $templateName template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     * @param int $expTime expiration time
     * @param string $type resource type
     *
     * @return bool|int number of cache files which needs to be updated
     *
     * @throws PrestaShopException
     */
    public function clear_cache($template_name, $cache_id = null, $compile_id = null, $exp_time = null, $type = null)
    {
        return $this->delete_from_lazy_cache($template_name, $cache_id, $compile_id);
    }
    /**
     * @param string|null $template
     * @param string|null $cacheId
     * @param string|null $compileId
     * @param object|null $parent
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        $this->check_compile_cache_invalidation();
        return parent::fetch($template, $cache_id, $compile_id, $parent);
    }
    /**
     * Check the compile cache needs to be invalidated (multi front + local cache compatible)
     *
     * @throws PrestaShopException
     */
    public function check_compile_cache_invalidation(): void
    {
        static $checked = false;
        if (!$checked) {
            $filename = $this->get_compile_dir() . 'last_flush';
            if (!@file_exists($filename)) {
                Tools::change_file_m_time($filename);
                parent::clear_compiled_template();
            } else {
                $sql = 'SELECT UNIX_TIMESTAMP(last_flush) AS last_flush FROM `' . _DB_PREFIX_ . 'smarty_last_flush` WHERE type=\'compile\'';
                $last_flush = (int) Db::read_only()->get_value($sql);
                if ($last_flush && @filemtime($filename) < $last_flush) {
                    Tools::change_file_m_time($filename);
                    parent::clear_compiled_template();
                }
            }
            $checked = true;
        }
    }
    /**
     * @param string $template
     * @param string $cacheId
     * @param string $compileId
     * @param object $parent
     * @param bool $doClone
     *
     * @return object
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function create_template($template, $cache_id = null, $compile_id = null, $parent = null, $do_clone = true)
    {
        $this->check_compile_cache_invalidation();
        if ($this->caching) {
            $this->check_template_invalidation($template, $cache_id, $compile_id);
            $tpl = parent::create_template($template, $cache_id, $compile_id, $parent, $do_clone);
        } else {
            $tpl = parent::create_template($template, $cache_id, $compile_id, $parent, $do_clone);
        }
        $tpl->start_render_callbacks[] = ['SmartyCustom', 'beforeFetch'];
        $tpl->end_render_callbacks[] = ['SmartyCustom', 'afterFetch'];
        return $tpl;
    }
    /**
     * Handle the lazy template cache invalidation
     *
     * @param string $template template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function check_template_invalidation($template, $cache_id, $compile_id): void
    {
        static $last_flush = null;
        $filename = $this->get_cache_dir() . 'last_template_flush';
        if (!@file_exists($filename)) {
            Tools::change_file_m_time($filename);
            parent::clear_all_cache();
        } else {
            if ($last_flush === null) {
                $sql = 'SELECT UNIX_TIMESTAMP(last_flush) AS last_flush FROM `' . _DB_PREFIX_ . 'smarty_last_flush` WHERE type=\'template\'';
                $last_flush = Db::read_only()->get_value($sql);
            }
            if ((int) $last_flush && @filemtime($filename) < $last_flush) {
                Tools::change_file_m_time($filename);
                parent::clear_all_cache();
            } else {
                if (is_object($cache_id) || is_array($cache_id)) {
                    $cache_id = null;
                }
                if ($this->is_in_lazy_cache($template, $cache_id, $compile_id) === false) {
                    // insert in cache before the effective cache creation to avoid nasty race condition
                    $this->insert_in_lazy_cache($template, $cache_id, $compile_id);
                    parent::clear_cache($template, $cache_id, $compile_id);
                }
            }
        }
    }
    /**
     * Check if the current template is stored in the lazy cache
     * Entry in the lazy cache = no need to regenerate the template
     *
     * @param string $template template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     *
     * @return bool|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function is_in_lazy_cache($template, $cache_id, $compile_id)
    {
        $template_md5 = md5($template);
        if (!is_null($compile_id) && strlen($compile_id) > 32) {
            $compile_id = md5($compile_id);
        }
        $key = 'SmartyCustom::lazy_cache_' . md5($template_md5 . $cache_id . $compile_id);
        if (!Cache::is_stored($key)) {
            Cache::store($key, $this->fetch_is_in_lazy_cache($template_md5, $cache_id, $compile_id, $template));
        }
        return Cache::retrieve($key);
    }
    /**
     * Insert the current template in the lazy cache
     *
     * @param string $template template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function insert_in_lazy_cache($template, $cache_id, $compile_id)
    {
        $template_md5 = md5($template);
        $sql = 'INSERT IGNORE INTO `' . _DB_PREFIX_ . 'smarty_lazy_cache`
							(`template_hash`, `cache_id`, `compile_id`, `last_update`)
							VALUES (\'' . p_sql($template_md5) . '\'';
        $sql .= ',"' . p_sql((string) $cache_id) . '"';
        if (!is_null($compile_id) && strlen($compile_id) > 32) {
            $compile_id = md5($compile_id);
        }
        $sql .= ',"' . p_sql((string) $compile_id) . '"';
        $sql .= ', FROM_UNIXTIME(' . time() . '))';
        return Db::get_instance()->execute($sql);
    }
    /**
     * Store the cache file path
     *
     * @param string $filepath cache file path
     * @param string $template template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     *
     * @throws PrestaShopException
     */
    public function update_filepath($filepath, $template, $cache_id, $compile_id): void
    {
        $template_md5 = md5($template);
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'smarty_lazy_cache`
							SET filepath=\'' . p_sql($filepath) . '\'
							WHERE `template_hash`=\'' . p_sql($template_md5) . '\'';
        $sql .= ' AND cache_id="' . p_sql((string) $cache_id) . '"';
        if (!is_null($compile_id) && strlen($compile_id) > 32) {
            $compile_id = md5($compile_id);
        }
        $sql .= ' AND compile_id="' . p_sql((string) $compile_id) . '"';
        Db::get_instance()->execute($sql);
    }
    /**
     * Callback called before template rendering. It is used to track
     * current template stack
     *
     * @param Smarty_Internal_Template $template
     * @throws SmartyException
     */
    public static function before_fetch($template): void
    {
        static::$trace[] = static::get_template_source($template);
    }
    /**
     * Callback called after template rendering
     */
    public static function after_fetch(): void
    {
        array_pop(static::$trace);
    }
    /**
     * Helper method to returns file path to current template
     *
     * @param Smarty_Internal_Template $template
     * @return string
     * @throws SmartyException
     */
    private static function get_template_source($template)
    {
        // first check whether resource descriptor points directly to template file
        if (@file_exists($template->template_resource)) {
            return $template->template_resource;
        }
        // we need to parse resource
        $file_path = Smarty_Resource::source($template)->filepath;
        if ($file_path) {
            return $file_path;
        }
        // return resource descriptor if it does not refers to physical file
        return $template->template_resource;
    }
    /**
     * Method returns true, if $file is compiled template
     *
     * @param string $file filepath
     * @return bool
     */
    public static function is_compiled_template($file)
    {
        // dynamically evaluated templates -- path from stack contains eval()'d
        if (strpos($file, 'eval()') > -1 && strpos($file, 'smarty_internal_templatebase.php') > -1) {
            return true;
        }
        // compiled templates are found in compile directory
        if (strpos($file, 'cache/smarty/compile/') > -1) {
            return true;
        }
        return false;
    }
    /**
     * Returns currently rendering template, if any
     *
     * @return string | null
     */
    public static function get_current_template()
    {
        if (static::$trace) {
            $length = count(static::$trace);
            return static::$trace[$length - 1];
        }
        return null;
    }
    /**
     * @param string $templateMd5
     * @param string $cacheId
     * @param string $compileId
     * @param string $template
     *
     * @return bool | string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function fetch_is_in_lazy_cache($template_md5, $cache_id, $compile_id, $template)
    {
        $sql = (new Db_Query())->select('UNIX_TIMESTAMP(last_update) AS last_update')->select('filepath')->from('smarty_lazy_cache')->where('template_hash="' . p_sql((string) $template_md5) . '"')->where('cache_id="' . p_sql((string) $cache_id) . '"')->where('compile_id="' . p_sql((string) $compile_id) . '"');
        $result = Db::read_only()->get_row($sql);
        if ($result === false) {
            return false;
        }
        $filepath = trim((string) $result['filepath']);
        $last_update = (int) $result['last_update'];
        if ($filepath === '') {
            // If the cache update is stalled for more than 1min, something should be wrong,
            // remove the entry from the lazy cache
            if ($last_update < time() - 60) {
                $this->delete_from_lazy_cache($template, $cache_id, $compile_id);
            }
            return true;
        }
        if ($this->caching_type === 'file') {
            $fullpath = $this->get_cache_dir() . $filepath;
            if (!file_exists($fullpath)) {
                return false;
            }
            if (filemtime($fullpath) < $last_update) {
                return false;
            }
        }
        return $filepath;
    }
}
/**
 * Class Smarty_Custom_Template
 */
class Smarty_Custom_Template extends Smarty_Internal_Template
{
    /** @var SmartyCustom|null */
    public $smarty;
    /**
     * @param string|null $template
     * @param string|null $cacheId
     * @param string|null $compileId
     * @param object|null $parent
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function fetch($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        if ($this->smarty->caching) {
            $tpl = $this->fetch_with_retries($template, $cache_id, $compile_id, $parent);
            if (property_exists($this, 'cached')) {
                $filepath = str_replace($this->smarty->get_cache_dir(), '', $this->cached->filepath);
                if ($this->smarty->is_in_lazy_cache($this->template_resource, $this->cache_id, $this->compile_id) != $filepath) {
                    $this->smarty->update_filepath($filepath, $this->template_resource, $this->cache_id, $this->compile_id);
                }
            }
            return $tpl;
        }
        return $this->fetch_with_retries($template, $cache_id, $compile_id, $parent);
    }
    /**
     * Helper method to render template
     *
     * @param string $template
     * @param string|null $cacheId
     * @param string|null $compileId
     * @param object|null $parent
     * @return string
     * @throws SmartyException
     * @throws Exception
     */
    public function fetch_with_retries($template, $cache_id, $compile_id, $parent)
    {
        $count = 0;
        $max_tries = 3;
        while (true) {
            try {
                $tpl = parent::fetch($template, $cache_id, $compile_id, $parent);
                return $tpl ?? '';
            } catch (Smarty_Exception $e) {
                // handle exception
                if (++$count === $max_tries) {
                    throw $e;
                }
                usleep(1);
            }
        }
    }
}