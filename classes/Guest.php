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
 * Class GuestCore
 */
class Guest_Core extends Object_Model
{
    /**
     * @var int
     */
    public $id_operating_system;
    /**
     * @var int
     */
    public $id_web_browser;
    /**
     * @var int
     */
    public $id_customer;
    /**
     * @var bool
     */
    public $javascript;
    /**
     * @var int
     */
    public $screen_resolution_x;
    /**
     * @var int
     */
    public $screen_resolution_y;
    /**
     * @var int
     */
    public $screen_color;
    /**
     * @var bool
     */
    public $sun_java;
    /**
     * @var bool
     */
    public $adobe_flash;
    /**
     * @var bool
     */
    public $adobe_director;
    /**
     * @var bool
     */
    public $apple_quicktime;
    /**
     * @var bool
     */
    public $real_player;
    /**
     * @var bool
     */
    public $windows_media;
    /**
     * @var string
     */
    public $accept_language;
    /**
     * @var bool
     */
    public $mobile_theme;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'guest', 'primary' => 'id_guest', 'fields' => ['id_operating_system' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'], 'id_web_browser' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'], 'id_customer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'], 'javascript' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0', 'dbNullable' => true], 'screen_resolution_x' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbType' => 'smallint(5) unsigned'], 'screen_resolution_y' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbType' => 'smallint(5) unsigned'], 'screen_color' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbType' => 'tinyint(3) unsigned'], 'sun_java' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)'], 'adobe_flash' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)'], 'adobe_director' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)'], 'apple_quicktime' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)'], 'real_player' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)'], 'windows_media' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)'], 'accept_language' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 8], 'mobile_theme' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0']], 'keys' => ['guest' => ['id_customer' => ['type' => Object_Model::KEY, 'columns' => ['id_customer']], 'id_operating_system' => ['type' => Object_Model::KEY, 'columns' => ['id_operating_system']], 'id_web_browser' => ['type' => Object_Model::KEY, 'columns' => ['id_web_browser']]]]];
    /**
     * @var string[][][]
     */
    protected $webservice_parameters = ['fields' => ['id_customer' => ['xlink_resource' => 'customers']]];
    /**
     * @param CookieCore $cookie
     *
     * @throws PrestaShopException
     */
    public static function set_new_guest($cookie): void
    {
        if (!Tools::is_crawler()) {
            $guest = new Guest(static::get_from_customer($cookie->id_customer));
            $guest->user_agent();
            $guest->save();
            $cookie->id_guest = (int) $guest->id;
        }
    }
    /**
     * @param int $idCustomer
     *
     * @return integer | null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_from_customer($id_customer)
    {
        if (!Validate::is_unsigned_id($id_customer)) {
            return null;
        }
        $id_customer = (int) $id_customer;
        if ($id_customer === 0) {
            return null;
        }
        $result = Db::read_only()->get_row((new Db_Query())->select('`id_guest`')->from('guest')->where('`id_customer` = ' . (int) $id_customer));
        if (is_array($result) && isset($result['id_guest'])) {
            return (int) $result['id_guest'];
        }
        return null;
    }
    /**
     * @throws PrestaShopException
     */
    public function user_agent(): void
    {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $this->accept_language = $this->get_language($accept_language);
        $this->id_operating_system = $this->get_os($user_agent);
        $this->id_web_browser = $this->get_browser($user_agent);
        $this->mobile_theme = Context::get_context()->get_mobile_device();
    }
    /**
     * @param string $acceptLanguage
     *
     * @return string
     */
    protected function get_language($accept_language)
    {
        // $langsArray is filled with all the languages accepted, ordered by priority
        $langs_array = [];
        preg_match_all('/([a-z]{2}(-[a-z]{2})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/', $accept_language, $array);
        if (count($array[1])) {
            $langs_array = array_combine($array[1], $array[4]);
            foreach ($langs_array as $lang => $val) {
                if ($val === '') {
                    $langs_array[$lang] = 1;
                }
            }
            arsort($langs_array, SORT_NUMERIC);
        }
        // Only the first language is returned
        return count($langs_array) ? key($langs_array) : '';
    }
    /**
     * @param string $userAgent
     *
     * @return int|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function get_os($user_agent)
    {
        $os_array = ['Windows 10' => 'Windows NT 10', 'Windows 8.1' => 'Windows NT 6.3', 'Windows 8' => 'Windows NT 6.2', 'Windows 7' => 'Windows NT 6.1', 'Windows Vista' => 'Windows NT 6.0', 'Windows XP' => 'Windows NT 5', 'MacOsX' => 'Mac OS X', 'Android' => 'Android', 'Linux' => 'X11'];
        foreach ($os_array as $os_name => $value) {
            if (strstr($user_agent, $value)) {
                $id = $this->get_operating_system_id($os_name);
                if (!$id) {
                    Db::get_instance()->insert('operating_system', ['name' => p_sql($os_name)], false, false, Db::INSERT_IGNORE);
                    $id = $this->get_operating_system_id($os_name);
                }
                return $id;
            }
        }
        return null;
    }
    /**
     *
     * @return int|null
     * @throws PrestaShopException
     */
    protected function get_operating_system_id(string $name)
    {
        $id = (int) Db::read_only()->get_value((new Db_Query())->select('`id_operating_system`')->from('operating_system', 'os')->where('os.`name` = \'' . p_sql($name) . '\''));
        return $id ?: null;
    }
    /**
     * @param string $userAgent
     *
     * @return int|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function get_browser($user_agent)
    {
        $browser_array = ['Chrome' => 'Chrome/', 'Safari' => 'Safari', 'Safari iPad' => 'iPad', 'Firefox' => 'Firefox/', 'Opera' => 'Opera', 'IE 11' => 'Trident', 'IE 10' => 'MSIE 10', 'IE 9' => 'MSIE 9', 'IE 8' => 'MSIE 8', 'IE 7' => 'MSIE 7', 'IE 6' => 'MSIE 6'];
        foreach ($browser_array as $browser_name => $value) {
            if (strstr($user_agent, $value)) {
                $id = $this->get_browser_id($browser_name);
                if (!$id) {
                    Db::get_instance()->insert('web_browser', ['name' => p_sql($browser_name)], false, false, Db::INSERT_IGNORE);
                    $id = $this->get_browser_id($browser_name);
                }
                return $id;
            }
        }
        return null;
    }
    /**
     *
     * @return int|null
     * @throws PrestaShopException
     */
    protected function get_browser_id(string $name)
    {
        $id = (int) Db::read_only()->get_value((new Db_Query())->select('`id_web_browser`')->from('web_browser', 'wb')->where('wb.`name` = \'' . p_sql($name) . '\''));
        return $id ?: null;
    }
    /**
     * @param int $idGuest
     * @param int $idCustomer
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function merge_with_customer($id_guest, $id_customer): void
    {
        // Since the guests are merged, the guest id in the connections table must be changed too
        Db::get_instance()->update('connections', ['id_guest' => (int) $id_guest], '`id_guest` = ' . (int) $this->id);
        // The current guest is removed from the database
        $this->delete();
        // $this is still filled with values, so it's id is changed for the old guest
        $this->id = (int) $id_guest;
        $this->id_customer = (int) $id_customer;
        // $this is now the old guest but filled with the most up to date values
        $this->update();
    }
}