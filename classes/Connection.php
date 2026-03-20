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
 * Class ConnectionCore
 */
class Connection_Core extends Object_Model
{
    /** @var int */
    public $id_guest;
    /** @var int */
    public $id_page;
    /** @var int */
    public $ip_address;
    /** @var string */
    public $http_referer;
    /** @var int */
    public $id_shop;
    /** @var int */
    public $id_shop_group;
    /** @var string */
    public $date_add;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'connections', 'primary' => 'id_connections', 'fields' => ['id_shop_group' => ['type' => self::TYPE_INT, 'required' => true, 'dbDefault' => '1'], 'id_shop' => ['type' => self::TYPE_INT, 'required' => true, 'dbDefault' => '1'], 'id_guest' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_page' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'ip_address' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbType' => 'bigint(20)'], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'http_referer' => ['type' => self::TYPE_STRING, 'validate' => 'isAbsoluteUrl']], 'keys' => ['connections' => ['date_add' => ['type' => Object_Model::KEY, 'columns' => ['date_add']], 'id_guest' => ['type' => Object_Model::KEY, 'columns' => ['id_guest']], 'id_page' => ['type' => Object_Model::KEY, 'columns' => ['id_page']]]]];
    /**
     * @param bool $full
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function set_page_connection(Cookie $cookie, $full = true)
    {
        $id_page = false;
        // The connection is created if it does not exist yet and we get the current page id
        if (!isset($cookie->id_connections) || !strstr(Tools::get_http_referer(), (string) Tools::get_http_host(false, false))) {
            $id_page = Connection::set_new_connection($cookie);
        }
        // If we do not track the pages, no need to get the page id
        if (!Configuration::get('PS_STATSDATA_PAGESVIEWS') && !Configuration::get('PS_STATSDATA_CUSTOMER_PAGESVIEWS')) {
            return [];
        }
        if (!$id_page) {
            $id_page = Page::get_current_id();
        }
        // If we do not track the page views by customer, the id_page is the only information needed
        if (!Configuration::get('PS_STATSDATA_CUSTOMER_PAGESVIEWS')) {
            return ['id_page' => $id_page];
        }
        // The ending time will be updated by an ajax request when the guest will close the page
        $time_start = date('Y-m-d H:i:s');
        Db::get_instance()->insert('connections_page', ['id_connections' => (int) $cookie->id_connections, 'id_page' => (int) $id_page, 'time_start' => $time_start], false, true, Db::INSERT_IGNORE);
        // This array is serialized and used by the ajax request to identify the page
        return ['id_connections' => (int) $cookie->id_connections, 'id_page' => (int) $id_page, 'time_start' => $time_start];
    }
    /**
     * @param Cookie $cookie
     *
     * @return int|false returns page id if connection has been set up, or false otherwise
     *
     * @throws PrestaShopException
     */
    public static function set_new_connection($cookie)
    {
        if (Tools::is_crawler()) {
            return false;
        }
        $guest_id = (int) $cookie->id_guest;
        if ($guest_id) {
            $sql = (new Db_Query())->select('1')->from('connections', 'c')->add_current_shop_restriction('c')->where('`c`.`id_guest` = ' . $guest_id)->where('`c`.`date_add` > \'' . p_sql(date('Y-m-d H:i:00', time() - 1800)) . '\'');
            $exists = Db::read_only()->get_row($sql);
            if (!$exists) {
                // The old connections details are removed from the database in order to spare some memory
                Connection::clean_connections_pages();
                $referer = Tools::get_http_referer();
                $array_url = parse_url($referer);
                if (!isset($array_url['host']) || preg_replace('/^www./', '', $array_url['host']) == preg_replace('/^www./', '', Tools::get_http_host(false, false))) {
                    $referer = '';
                }
                $connection = new Connection();
                $connection->id_guest = $guest_id;
                $connection->id_page = Page::get_current_id();
                $connection->ip_address = Tools::get_remote_addr() ? (int) ip2long(Tools::get_remote_addr()) : '';
                $connection->id_shop = Context::get_context()->shop->id;
                $connection->id_shop_group = Context::get_context()->shop->id_shop_group;
                $connection->date_add = $cookie->date_add;
                if (Validate::is_absolute_url($referer)) {
                    $connection->http_referer = substr($referer, 0, 254);
                }
                $connection->add();
                $cookie->id_connections = $connection->id;
                return (int) $connection->id_page;
            }
        }
        return false;
    }
    /**
     * @throws PrestaShopException
     */
    public static function clean_connections_pages(): void
    {
        $period = Configuration::get('PS_STATS_OLD_CONNECT_AUTO_CLEAN');
        if ($period === 'week') {
            $interval = '1 WEEK';
        } elseif ($period === 'month') {
            $interval = '1 MONTH';
        } elseif ($period === 'year') {
            $interval = '1 YEAR';
        } else {
            return;
        }
        // Records of connections details older than the beginning of the  specified interval are deleted
        Db::get_instance()->execute('
        DELETE FROM `' . _DB_PREFIX_ . 'connections_page`
        WHERE time_start < LAST_DAY(DATE_SUB(NOW(), INTERVAL ' . $interval . '))');
    }
    /**
     * @param int $idConnections
     * @param int $idPage
     * @param string $timeStart
     * @param int $time
     *
     * @throws PrestaShopException
     */
    public static function set_page_time($id_connections, $id_page, $time_start, $time): void
    {
        if (!Validate::is_unsigned_id($id_connections) || !Validate::is_unsigned_id($id_page) || !Validate::is_date($time_start)) {
            return;
        }
        // Limited to 5 minutes because more than 5 minutes is considered as an error
        if ($time > 300000) {
            $time = 300000;
        }
        Db::get_instance()->execute('
		UPDATE `' . _DB_PREFIX_ . 'connections_page`
		SET `time_end` = `time_start` + INTERVAL ' . (int) ($time / 1000) . ' SECOND
		WHERE `id_connections` = ' . (int) $id_connections . '
		AND `id_page` = ' . (int) $id_page . '
		AND `time_start` = \'' . p_sql($time_start) . '\'');
    }
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_fields()
    {
        if (!$this->id_shop_group) {
            $this->id_shop_group = Context::get_context()->shop->id_shop_group;
        }
        return parent::get_fields();
    }
}