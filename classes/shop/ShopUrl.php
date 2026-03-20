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
 * Class ShopUrlCore
 */
class Shop_Url_Core extends Object_Model
{
    /** @var int $id_shop */
    public $id_shop;
    /** @var string $domain */
    public $domain;
    /** @var string $domain_ssl */
    public $domain_ssl;
    /** @var string $physical_uri */
    public $physical_uri;
    /** @var string $virtual_uri */
    public $virtual_uri;
    /** @var bool $main */
    public $main;
    /** @var bool $active */
    public $active;
    /** @var array $main_domain */
    protected static $main_domain = [];
    /** @var array $main_domain_ssl */
    protected static $main_domain_ssl = [];
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'shop_url', 'primary' => 'id_shop_url', 'fields' => ['id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true], 'domain' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 150], 'domain_ssl' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 150, 'dbNullable' => false], 'physical_uri' => ['type' => self::TYPE_STRING, 'validate' => 'isUriPath', 'size' => 64, 'dbNullable' => false], 'virtual_uri' => ['type' => self::TYPE_STRING, 'validate' => 'isUriPath', 'size' => 64, 'dbNullable' => false], 'main' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbNullable' => false], 'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbNullable' => false]], 'keys' => ['shop_url' => ['full_shop_url' => ['type' => Object_Model::UNIQUE_KEY, 'columns' => ['domain', 'physical_uri', 'virtual_uri']], 'full_shop_url_ssl' => ['type' => Object_Model::UNIQUE_KEY, 'columns' => ['domain_ssl', 'physical_uri', 'virtual_uri']], 'id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop', 'main']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['fields' => ['id_shop' => ['xlink_resource' => 'shops']]];
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_fields()
    {
        $this->domain = trim($this->domain);
        $this->domain_ssl = trim($this->domain_ssl);
        if ($this->physical_uri) {
            $this->physical_uri = trim(str_replace(' ', '', $this->physical_uri), '/');
            $this->physical_uri = preg_replace('#/+#', '/', '/' . $this->physical_uri . '/');
        } else {
            $this->physical_uri = '/';
        }
        if ($this->virtual_uri) {
            $this->virtual_uri = trim(str_replace(' ', '', $this->virtual_uri), '/');
            $this->virtual_uri = preg_replace('#/+#', '/', trim($this->virtual_uri, '/')) . '/';
        }
        return parent::get_fields();
    }
    /**
     * @return string
     */
    public function get_base_uri()
    {
        return $this->physical_uri . $this->virtual_uri;
    }
    /**
     * @param bool $ssl
     *
     * @return string|null
     */
    public function get_url($ssl = false)
    {
        if (!$this->id) {
            return null;
        }
        $url = $ssl ? 'https://' . $this->domain_ssl : 'http://' . $this->domain;
        return $url . $this->get_base_uri();
    }
    /**
     * Get list of shop urls
     *
     * @param bool $idShop
     *
     * @return PrestaShopCollection Collection of ShopUrl
     *
     * @throws PrestaShopException
     */
    public static function get_shop_urls($id_shop = false)
    {
        $urls = new Presta_Shop_Collection('ShopUrl');
        if ($id_shop) {
            $urls->where('id_shop', '=', $id_shop);
        }
        return $urls;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_main()
    {
        $conn = Db::get_instance();
        $res = $conn->update('shop_url', ['main' => 0], 'id_shop = ' . (int) $this->id_shop);
        $res = $conn->update('shop_url', ['main' => 1], 'id_shop_url = ' . (int) $this->id) && $res;
        $this->main = true;
        // Reset main URL for all shops to prevent problems
        $sql = 'SELECT s1.id_shop_url FROM ' . _DB_PREFIX_ . 'shop_url s1
				WHERE (
					SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'shop_url s2
					WHERE s2.main = 1
					AND s2.id_shop = s1.id_shop
				) = 0
				GROUP BY s1.id_shop';
        foreach ($conn->get_array($sql) as $row) {
            $conn->update('shop_url', ['main' => 1], 'id_shop_url = ' . $row['id_shop_url']);
        }
        return $res;
    }
    /**
     * @param string $domain
     * @param string $domainSsl
     * @param string $physicalUri
     * @param string $virtualUri
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function can_add_this_url($domain, $domain_ssl, $physical_uri, $virtual_uri)
    {
        $physical_uri = trim($physical_uri, '/');
        if ($physical_uri) {
            $physical_uri = preg_replace('#/+#', '/', '/' . $physical_uri . '/');
        } else {
            $physical_uri = '/';
        }
        $virtual_uri = trim($virtual_uri, '/');
        if ($virtual_uri) {
            $virtual_uri = preg_replace('#/+#', '/', trim($virtual_uri, '/')) . '/';
        }
        return Db::read_only()->get_value((new Db_Query())->select('`id_shop_url`')->from('shop_url')->where('`physical_uri` = \'' . p_sql($physical_uri) . '\'')->where('`virtual_uri` = \'' . p_sql($virtual_uri) . '\'')->where('`domain` = \'' . p_sql($domain) . '\'' . ($domain_ssl ? ' OR domain_ssl = \'' . p_sql($domain_ssl) . '\'' : ''))->where($this->id ? '`id_shop_url` != ' . (int) $this->id : ''));
    }
    /**
     * @param int $idShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function cache_main_domain_for_shop($id_shop): void
    {
        if (!isset(static::$main_domain_ssl[(int) $id_shop]) || !isset(static::$main_domain[(int) $id_shop])) {
            $row = Db::read_only()->get_row((new Db_Query())->select('`domain`, `domain_ssl`')->from('shop_url')->where('`main` = 1')->where('`id_shop` = ' . ($id_shop !== null ? (int) $id_shop : (int) Context::get_context()->shop->id)));
            static::$main_domain[(int) $id_shop] = $row['domain'] ?? '';
            static::$main_domain_ssl[(int) $id_shop] = $row['domain_ssl'] ?? '';
        }
    }
    public static function reset_main_domain_cache(): void
    {
        static::$main_domain = [];
        static::$main_domain_ssl = [];
    }
    /**
     * @param int|null $idShop
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_main_shop_domain($id_shop = null)
    {
        static::cache_main_domain_for_shop($id_shop);
        return static::$main_domain[(int) $id_shop];
    }
    /**
     * @param int|null $idShop
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_main_shop_domain_ssl($id_shop = null)
    {
        static::cache_main_domain_for_shop($id_shop);
        return static::$main_domain_ssl[(int) $id_shop];
    }
}