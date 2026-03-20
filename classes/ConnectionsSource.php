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
 * Class ConnectionsSourceCore
 */
class Connections_Source_Core extends Object_Model
{
    /**
     * @var int
     */
    public static $uri_max_size = 255;
    /**
     * @var int
     */
    public $id_connections;
    /**
     * @var string
     */
    public $http_referer;
    /**
     * @var string
     */
    public $request_uri;
    /**
     * @var string
     */
    public $keywords;
    /**
     * @var string
     */
    public $date_add;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'connections_source', 'primary' => 'id_connections_source', 'fields' => ['id_connections' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'http_referer' => ['type' => self::TYPE_STRING, 'validate' => 'isAbsoluteUrl'], 'request_uri' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl'], 'keywords' => ['type' => self::TYPE_STRING, 'validate' => 'isMessage'], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true]], 'keys' => ['connections_source' => ['connections' => ['type' => Object_Model::KEY, 'columns' => ['id_connections']], 'orderby' => ['type' => Object_Model::KEY, 'columns' => ['date_add']]]]];
    /**
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function log_http_referer(?Cookie $cookie = null)
    {
        if (!$cookie) {
            $cookie = Context::get_context()->cookie;
        }
        if (!isset($cookie->id_connections) || !Validate::is_unsigned_id($cookie->id_connections)) {
            return false;
        }
        // If the referrer is not correct, we drop the connection
        $referer = Tools::get_http_referer();
        if ($referer && !Validate::is_absolute_url($referer)) {
            return false;
        }
        // If there is no referrer and we do not want to save direct traffic (as opposed to referral traffic), we drop the connection
        if (!$referer && !Configuration::get('TRACKING_DIRECT_TRAFFIC')) {
            return false;
        }
        $source = new Connections_Source();
        // There are a few more operations if there is a referrer
        if ($referer) {
            // If the referrer is internal (i.e. from your own website), then we drop the connection
            $parsed = parse_url($referer);
            $parsed_host = parse_url(Tools::get_protocol() . Tools::get_http_host(false, false) . __PS_BASE_URI__);
            if (!isset($parsed['host']) || (!isset($parsed['path']) || !isset($parsed_host['path']))) {
                return false;
            }
            if (preg_replace('/^www./', '', $parsed['host']) == preg_replace('/^www./', '', Tools::get_http_host(false, false)) && !strncmp($parsed['path'], $parsed_host['path'], strlen(__PS_BASE_URI__))) {
                return false;
            }
            $source->http_referer = substr($referer, 0, Connections_Source::$uri_max_size);
        }
        $source->id_connections = (int) $cookie->id_connections;
        $source->request_uri = Tools::get_http_host(false, false);
        if (isset($_SERVER['REQUEST_URI'])) {
            $source->request_uri .= $_SERVER['REQUEST_URI'];
        }
        if (!Validate::is_url($source->request_uri)) {
            $source->request_uri = '';
        }
        $source->request_uri = substr($source->request_uri, 0, Connections_Source::$uri_max_size);
        return $source->add();
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if ($result = parent::add($auto_date, $null_values)) {
            Referrer::cache_new_source($this->id);
        }
        return $result;
    }
    /**
     * @param int $idOrder
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_order_sources($id_order)
    {
        return Db::read_only()->get_array('
		SELECT cos.http_referer, cos.request_uri, cos.keywords, cos.date_add
		FROM ' . _DB_PREFIX_ . 'orders o
		INNER JOIN ' . _DB_PREFIX_ . 'guest g ON g.id_customer = o.id_customer
		INNER JOIN ' . _DB_PREFIX_ . 'connections co  ON co.id_guest = g.id_guest
		INNER JOIN ' . _DB_PREFIX_ . 'connections_source cos ON cos.id_connections = co.id_connections
		WHERE id_order = ' . (int) $id_order . '
		ORDER BY cos.date_add DESC');
    }
}