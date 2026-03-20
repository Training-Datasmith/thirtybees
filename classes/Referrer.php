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
 * Class ReferrerCore
 */
class Referrer_Core extends Object_Model
{
    /**
     * @var string
     */
    protected static $_join = '(r.http_referer_like IS NULL OR r.http_referer_like = \'\' OR cs.http_referer LIKE r.http_referer_like)
			AND (r.request_uri_like IS NULL OR r.request_uri_like = \'\' OR cs.request_uri LIKE r.request_uri_like)
			AND (r.http_referer_like_not IS NULL OR r.http_referer_like_not = \'\' OR cs.http_referer NOT LIKE r.http_referer_like_not)
			AND (r.request_uri_like_not IS NULL OR r.request_uri_like_not = \'\' OR cs.request_uri NOT LIKE r.request_uri_like_not)
			AND (r.http_referer_regexp IS NULL OR r.http_referer_regexp = \'\' OR cs.http_referer REGEXP r.http_referer_regexp)
			AND (r.request_uri_regexp IS NULL OR r.request_uri_regexp = \'\' OR cs.request_uri REGEXP r.request_uri_regexp)
			AND (r.http_referer_regexp_not IS NULL OR r.http_referer_regexp_not = \'\' OR cs.http_referer NOT REGEXP r.http_referer_regexp_not)
			AND (r.request_uri_regexp_not IS NULL OR r.request_uri_regexp_not = \'\' OR cs.request_uri NOT REGEXP r.request_uri_regexp_not)';
    /**
     * @var int $id_shop
     */
    public $id_shop;
    /**
     * @var string $name
     */
    public $name;
    /**
     * @var string $passwd
     */
    public $passwd;
    /**
     * @var string
     */
    public $http_referer_regexp;
    /**
     * @var string
     */
    public $http_referer_like;
    /**
     * @var string
     */
    public $request_uri_regexp;
    /**
     * @var string
     */
    public $request_uri_like;
    /**
     * @var string
     */
    public $http_referer_regexp_not;
    /**
     * @var string
     */
    public $http_referer_like_not;
    /**
     * @var string
     */
    public $request_uri_regexp_not;
    /**
     * @var string
     */
    public $request_uri_like_not;
    /**
     * @var float
     */
    public $base_fee;
    /**
     * @var float
     */
    public $percent_fee;
    /**
     * @var float
     */
    public $click_fee;
    /**
     * @var string
     */
    public $date_add;
    /**
     * @var int
     */
    public $cache_visitors;
    /**
     * @var int
     */
    public $cache_visits;
    /**
     * @var int
     */
    public $cache_pages;
    /**
     * @var int
     */
    public $cache_registrations;
    /**
     * @var int
     */
    public $cache_orders;
    /**
     * @var float
     */
    public $cache_sales;
    /**
     * @var float
     */
    public $cache_reg_rate;
    /**
     * @var float
     */
    public $cache_order_rate;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'referrer', 'primary' => 'id_referrer', 'fields' => [
        'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
        'passwd' => ['type' => self::TYPE_STRING, 'validate' => 'isPasswd', 'size' => 60],
        'http_referer_regexp' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
        'http_referer_like' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
        'request_uri_regexp' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
        'request_uri_like' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
        'http_referer_regexp_not' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
        'http_referer_like_not' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
        'request_uri_regexp_not' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
        'request_uri_like_not' => ['type' => self::TYPE_STRING, 'validate' => 'isCleanHtml', 'size' => 64],
        'base_fee' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
        'percent_fee' => ['type' => self::TYPE_FLOAT, 'validate' => 'isPercentage', 'size' => 5, 'decimals' => 2, 'dbDefault' => '0.00'],
        'click_fee' => ['type' => self::TYPE_PRICE, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
        'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false],
        // shop only fields
        'cache_visitors' => ['type' => self::TYPE_INT, 'shop' => true, 'shopOnly' => true, 'dbType' => 'int(11)'],
        'cache_visits' => ['type' => self::TYPE_INT, 'shop' => true, 'shopOnly' => true, 'dbType' => 'int(11)'],
        'cache_pages' => ['type' => self::TYPE_INT, 'shop' => true, 'shopOnly' => true, 'dbType' => 'int(11)'],
        'cache_registrations' => ['type' => self::TYPE_INT, 'shop' => true, 'shopOnly' => true, 'dbType' => 'int(11)'],
        'cache_orders' => ['type' => self::TYPE_INT, 'shop' => true, 'shopOnly' => true, 'dbType' => 'int(11)'],
        'cache_sales' => ['type' => self::TYPE_FLOAT, 'shop' => true, 'shopOnly' => true, 'size' => 17, 'decimals' => 2],
        'cache_reg_rate' => ['type' => self::TYPE_FLOAT, 'shop' => true, 'shopOnly' => true, 'size' => 5, 'decimals' => 4],
        'cache_order_rate' => ['type' => self::TYPE_FLOAT, 'shop' => true, 'shopOnly' => true, 'size' => 5, 'decimals' => 4],
    ]];
    /**
     * @param int $idConnectionsSource
     *
     * @throws PrestaShopException
     */
    public static function cache_new_source($id_connections_source): void
    {
        if (!$id_connections_source) {
            return;
        }
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'referrer_cache (id_referrer, id_connections_source) (
					SELECT id_referrer, id_connections_source
					FROM ' . _DB_PREFIX_ . 'referrer r
					LEFT JOIN ' . _DB_PREFIX_ . 'connections_source cs ON (' . static::$_join . ')
					WHERE id_connections_source = ' . (int) $id_connections_source . '
				)';
        Db::get_instance()->execute($sql);
    }
    /**
     * Get list of referrers connections of a customer
     *
     * @param int $idCustomer
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_referrers($id_customer)
    {
        return Db::read_only()->get_array((new Db_Query())->select('DISTINCT c.`date_add`, r.`name`, s.`name` AS `shop_name`')->from('guest', 'g')->left_join('connections', 'c', 'c.`id_guest` = g.`id_guest`')->left_join('connections_source', 'cs', 'c.`id_connections` = cs.`id_connections`')->left_join('referrer', 'r', static::$_join)->left_join('shop', 's', 's.`id_shop` = c.`id_shop`')->where('g.`id_customer` = ' . (int) $id_customer)->where('r.`name` IS NOT NULL')->order_by('c.`date_add` DESC'));
    }
    /**
     * @param int $idReferrer
     * @param int $idProduct
     * @param Employee|null $employee
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_ajax_product($id_referrer, $id_product, $employee = null): void
    {
        $product = new Product($id_product, false, Configuration::get('PS_LANG_DEFAULT'));
        $currency = Currency::get_currency_instance(Configuration::get('PS_CURRENCY_DEFAULT'));
        $referrer = new Referrer($id_referrer);
        $stats_visits = $referrer->get_stats_visits($id_product, $employee);
        $registrations = $referrer->get_registrations($id_product, $employee);
        $stats_sales = $referrer->get_stats_sales($id_product, $employee);
        // If it's a product and it has no visits nor orders
        if ((int) $id_product && !$stats_visits['visits'] && !$stats_sales['orders']) {
            exit;
        }
        $json_array = ['id_product' => (int) $product->id, 'product_name' => htmlspecialchars($product->name), 'uniqs' => (int) $stats_visits['uniqs'], 'visitors' => (int) $stats_visits['visitors'], 'visits' => (int) $stats_visits['visits'], 'pages' => (int) $stats_visits['pages'], 'registrations' => (int) $registrations, 'orders' => (int) $stats_sales['orders'], 'sales' => Tools::display_price($stats_sales['sales'], $currency), 'cart' => Tools::display_price((int) $stats_sales['orders'] ? $stats_sales['sales'] / (int) $stats_sales['orders'] : 0, $currency), 'reg_rate' => number_format((int) $stats_visits['uniqs'] ? (int) $registrations / (int) $stats_visits['uniqs'] : 0, 4, '.', ''), 'order_rate' => number_format((int) $stats_visits['uniqs'] ? (int) $stats_sales['orders'] / (int) $stats_visits['uniqs'] : 0, 4, '.', ''), 'click_fee' => Tools::display_price((int) $stats_visits['visits'] * $referrer->click_fee, $currency), 'base_fee' => Tools::display_price($stats_sales['orders'] * $referrer->base_fee, $currency), 'percent_fee' => Tools::display_price($stats_sales['sales'] * $referrer->percent_fee / 100, $currency)];
        die('[' . json_encode($json_array) . ']');
    }
    /**
     * Get some statistics on visitors connection for current referrer
     *
     * @param int|null $idProduct
     * @param Employee $employee
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_stats_visits($id_product, $employee)
    {
        return Db::read_only()->get_row((new Db_Query())->select('COUNT(DISTINCT cs.`id_connections_source`) AS `visits`')->select('COUNT(DISTINCT cs.`id_connections`) AS `visitors`')->select('COUNT(DISTINCT c.`id_guest`) AS `uniqs`')->select('COUNT(DISTINCT cp.`time_start`) AS `pages`')->from('referrer_cache', 'rc')->join($id_product ? 'LEFT JOIN `' . _DB_PREFIX_ . 'page` p ON cp.`id_page` = p.`id_page`' : '')->join($id_product ? 'LEFT JOIN `' . _DB_PREFIX_ . 'page_type` pt ON pt.`id_page_type` = p.`id_page_type`' : '')->left_join('referrer', 'r', 'rc.`id_referrer` = r.`id_referrer`' . ($id_product ? 'AND (' . static::$_join . ')' : ''))->left_join('referrer_shop', 'rs', 'r.`id_referrer` = rs.`id_referrer`')->left_join('connections_source', 'cs', 'rc.`id_connections_source` = cs.`id_connections_source`')->left_join('connections', 'c', 'cs.`id_connections` = c.`id_connections`')->left_join('connections_page', 'cp', 'cp.`id_connections` = c.`id_connections`')->where(isset($employee->stats_date_from) && isset($employee->stats_date_to) ? 'cs.`date_add` BETWEEN \'' . p_sql($employee->stats_date_from) . ' 00:00:00\' AND \'' . p_sql($employee->stats_date_to) . ' 23:59:59\'' : '')->where('1 ' . Shop::add_sql_restriction(false, 'rs'))->where('1 ' . Shop::add_sql_restriction(false, 'c'))->where('rc.`id_referrer` = ' . (int) $this->id)->where($id_product ? 'pt.`name` = \'product\'' : '')->where($id_product ? 'p.`id_object` = ' . (int) $id_product : ''));
    }
    /**
     * Get some statistics on customers registrations for current referrer
     *
     * @param int|null $idProduct
     * @param Employee $employee
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_registrations($id_product, $employee)
    {
        $sql = (new Db_Query())->select('COUNT(DISTINCT cu.`id_customer`) AS `registrations`')->from('referrer_cache', 'rc')->left_join('referrer_shop', 'rs', 'rc.`id_referrer` = rs.`id_referrer`')->left_join('connections_source', 'cs', 'rc.`id_connections_source` = cs.`id_connections_source`')->left_join('connections', 'c', 'cs.`id_connections` = c.`id_connections`')->left_join('guest', 'g', 'g.`id_guest` = c.`id_guest`')->left_join('customer', 'cu', 'cu.`id_customer` = g.`id_customer`')->where('cu.`date_add` BETWEEN ' . Module_Graph::get_date_between($employee) . ' ' . Shop::add_sql_restriction(false, 'rs') . ' ' . Shop::add_sql_restriction(false, 'c') . ' ' . Shop::add_sql_restriction(Shop::SHARE_CUSTOMER, 'cu'))->where('cu.`date_add` > cs.`date_add`')->where('rc.`id_referrer` = ' . (int) $this->id);
        if ($id_product) {
            $sql->left_join('connections_page', 'cp', 'cp.`id_connections` = c.`id_connections`');
            $sql->left_join('page', 'p', 'cp.`id_page` = p.`id_page`');
            $sql->left_join('page_type', 'pt', 'pt.`id_paget_type` = p.`id_page_type`');
            $sql->where('pt.`name` = \'product\'');
            $sql->where('p.`id_object` = ' . (int) $id_product);
        }
        $result = Db::read_only()->get_row($sql);
        return (int) $result['registrations'];
    }
    /**
     * Get some statistics on orders for current referrer
     *
     * @param int|null $idProduct
     * @param Employee|null $employee
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_stats_sales($id_product, $employee)
    {
        $sql = (new Db_Query())->select('oo.`id_order`')->from('referrer_cache', 'rc')->left_join('referrer_shop', 'rs', 'rc.`id_referrer` = rs.`id_referrer`')->inner_join('connections_source', 'cs', 'rc.`id_connections_source` = cs.`id_connections_source`')->inner_join('connections', 'c', 'cs.`id_connections` = c.`id_connections`')->inner_join('guest', 'g', 'g.`id_guest` = c.`id_guest`')->inner_join('orders', 'oo', 'oo.`id_customer` = g.`id_customer`')->where('oo.`invoice_date` BETWEEN ' . Module_Graph::get_date_between($employee) . ' ' . Shop::add_sql_restriction(false, 'rs') . ' ' . Shop::add_sql_restriction(false, 'c') . ' ' . Shop::add_sql_restriction(Shop::SHARE_ORDER, 'oo'))->where('oo.`date_add` > cs.`date_add`')->where('rc.`id_referrer` = ' . (int) $this->id)->where('oo.`valid` = 1');
        if ($id_product) {
            $sql->left_join('order_detail', 'od', 'oo.`id_order` = od.`id_order`');
            $sql->where('od.`product_id` = ' . (int) $id_product);
        }
        $conn = Db::read_only();
        $order_ids = array_map(intval(...), array_column($conn->get_array($sql), 'id_order'));
        $orders = 0;
        $sales = 0;
        if ($order_ids) {
            $data = $conn->get_row((new Db_Query())->select('COUNT(`o`.`id_order`) AS `orders`, SUM(`op`.`amount` / `op`.`conversion_rate`) AS `sales`')->from('orders', 'o')->left_join('order_payment', 'op', '`o`.`reference` = `op`.`order_reference`')->where('`o`.`id_order` IN (' . implode(',', $order_ids) . ') ' . Shop::add_sql_restriction(Shop::SHARE_ORDER))->where('`o`.`valid` = 1'));
            if ($data) {
                $orders = $data['orders'];
                $sales = $data['sales'];
            }
        }
        return ['orders' => $orders, 'sales' => $sales];
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if (!$result = parent::add($auto_date, $null_values)) {
            return false;
        }
        Referrer::refresh_cache([['id_referrer' => $this->id]]);
        Referrer::refresh_index([['id_referrer' => $this->id]]);
        return $result;
    }
    /**
     * Refresh cache data of referrer statistics in referrer_shop table
     *
     * @param array $referrers
     * @param Employee|null $employee
     *
     * @return true
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function refresh_cache($referrers = null, $employee = null)
    {
        if (!$referrers || !is_array($referrers)) {
            $referrers = Db::read_only()->get_array((new Db_Query())->select('`id_referrer`')->from('referrer'));
        }
        foreach ($referrers as $row) {
            $referrer = new Referrer($row['id_referrer']);
            foreach (Shop::get_shops(true, null, true) as $id_shop) {
                if (!$referrer->is_associated_to_shop($id_shop)) {
                    continue;
                }
                $stats_visits = $referrer->get_stats_visits(null, $employee);
                $registrations = $referrer->get_registrations(null, $employee);
                $stats_sales = $referrer->get_stats_sales(null, $employee);
                Db::get_instance()->update('referrer_shop', ['cache_visitors' => (int) $stats_visits['uniqs'], 'cache_visits' => (int) $stats_visits['visits'], 'cache_pages' => (int) $stats_visits['pages'], 'cache_registrations' => (int) $registrations, 'cache_orders' => (int) $stats_sales['orders'], 'cache_sales' => number_format($stats_sales['sales'], 2, '.', ''), 'cache_reg_rate' => $stats_visits['uniqs'] ? $registrations / $stats_visits['uniqs'] : 0, 'cache_order_rate' => $stats_visits['uniqs'] ? $stats_sales['orders'] / $stats_visits['uniqs'] : 0], 'id_referrer = ' . (int) $referrer->id . ' AND `id_shop` = ' . (int) $id_shop);
            }
        }
        Configuration::update_value('PS_REFERRERS_CACHE_LIKE', Module_Graph::get_date_between($employee));
        Configuration::update_value('PS_REFERRERS_CACHE_DATE', date('Y-m-d H:i:s'));
        return true;
    }
    /**
     * Cache liaison between connections_source data and referrers data
     *
     * @param array $referrers
     *
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public static function refresh_index($referrers = null): void
    {
        $conn = Db::get_instance();
        if (!$referrers || !is_array($referrers)) {
            $conn->execute('TRUNCATE ' . _DB_PREFIX_ . 'referrer_cache');
            $conn->execute('
			INSERT INTO ' . _DB_PREFIX_ . 'referrer_cache (id_referrer, id_connections_source) (
				SELECT id_referrer, id_connections_source
				FROM ' . _DB_PREFIX_ . 'referrer r
				LEFT JOIN ' . _DB_PREFIX_ . 'connections_source cs ON (' . static::$_join . ')
			)');
        } else {
            foreach ($referrers as $row) {
                $conn->delete('referrer_cache', '`id_referrer` = ' . (int) $row['id_referrer']);
                $conn->execute('
				INSERT INTO ' . _DB_PREFIX_ . 'referrer_cache (id_referrer, id_connections_source) (
					SELECT id_referrer, id_connections_source
					FROM ' . _DB_PREFIX_ . 'referrer r
					LEFT JOIN ' . _DB_PREFIX_ . 'connections_source cs ON (' . static::$_join . ')
					WHERE id_referrer = ' . (int) $row['id_referrer'] . '
					AND id_connections_source IS NOT NULL
				)');
            }
        }
    }
}