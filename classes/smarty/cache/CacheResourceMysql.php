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
namespace Thirtybees\Core\Smarty\Cache;

use Db;
use Presta_Shop_Database_Exception;
use Presta_Shop_Exception;
use Smarty_cache_Resource_custom;
/**
 * Class CacheResourceMysqlCore
 */
class Cache_Resource_Mysql_Core extends Smarty_cache_Resource_custom
{
    public function __construct(protected \Encryptor $encryptor)
    {
    }
    /**
     * fetch cached content and its modification time from data source
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function fetch($id, $name, $cache_id, $compile_id, &$content, &$mtime)
    {
        $row = Db::read_only()->get_row('SELECT modified, content FROM ' . _DB_PREFIX_ . 'smarty_cache WHERE id_smarty_cache = "' . p_sql($id, true) . '"');
        if ($row) {
            $encoded = $row['content'];
            if ($encoded) {
                $encrypted = base64_decode((string) $encoded);
                if ($encrypted !== false) {
                    $content = $this->encryptor->decrypt($encrypted);
                    $mtime = strtotime((string) $row['modified']);
                    return;
                }
            }
        }
        $content = null;
        $mtime = null;
    }
    /**
     * Fetch cached content's modification timestamp from data source
     *
     * @param string $id unique cache content identifier
     * @param string $name template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     *
     * @return int|boolean timestamp (epoch) the template was modified, or false if not found
     *
     * @throws PrestaShopException
     */
    protected function fetch_timestamp($id, $name, $cache_id, $compile_id)
    {
        $value = Db::read_only()->get_value('SELECT modified FROM ' . _DB_PREFIX_ . 'smarty_cache WHERE id_smarty_cache = "' . p_sql($id, true) . '"');
        return strtotime((string) $value);
    }
    /**
     * Save content to cache
     *
     * @param string $id unique cache content identifier
     * @param string $name template name
     * @param string $cacheId cache id
     * @param string $compileId compile id
     * @param int|null $expTime seconds till expiration time in seconds or null
     * @param string $content content to cache
     *
     * @return bool success
     *
     * @throws PrestaShopException
     */
    protected function save($id, $name, $cache_id, $compile_id, $exp_time, $content)
    {
        $conn = Db::get_instance();
        $conn->execute('
		REPLACE INTO ' . _DB_PREFIX_ . 'smarty_cache (id_smarty_cache, name, cache_id, content)
		VALUES (
			"' . p_sql($id, true) . '",
			"' . p_sql(sha1($name)) . '",
			"' . p_sql($cache_id, true) . '",
			"' . base64_encode((string) $this->encryptor->encrypt($content)) . '"
		)');
        return (bool) $conn->Affected_Rows();
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
     *
     * @throws PrestaShopException
     */
    protected function delete($name, $cache_id, $compile_id, $exp_time)
    {
        $conn = Db::get_instance();
        // delete the whole cache
        if ($name === null && $cache_id === null && $compile_id === null && $exp_time === null) {
            // returning the number of deleted caches would require a second query to count them
            $conn->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . 'smarty_cache');
            return -1;
        }
        $where = [];
        if ($name !== null) {
            $where[] = 'name = "' . p_sql(sha1($name)) . '"';
        }
        if ($exp_time !== null) {
            $where[] = 'modified < DATE_SUB(NOW(), INTERVAL ' . (int) $exp_time . ' SECOND)';
        }
        if ($cache_id !== null) {
            $where[] = '(cache_id  = "' . p_sql($cache_id, true) . '" OR cache_id LIKE "' . p_sql($cache_id . '|%', true) . '")';
        }
        $conn->execute('DELETE FROM ' . _DB_PREFIX_ . 'smarty_cache WHERE ' . implode(' AND ', $where));
        return $conn->Affected_Rows();
    }
}