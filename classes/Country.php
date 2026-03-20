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
 * Class CountryCore
 */
class Country_Core extends Object_Model
{
    /** @var int|null Object ID */
    public $id;
    /** @var int Zone id which country belongs */
    public $id_zone;
    /** @var int Currency id which country belongs */
    public $id_currency;
    /** @var string 2 letters iso code */
    public $iso_code;
    /** @var int international call prefix */
    public $call_prefix;
    /** @var string|string[] Name */
    public $name;
    /** @var bool Contain states */
    public $contains_states;
    /** @var bool Need identification number dni/nif/nie */
    public $need_identification_number;
    /** @var bool Need Zip Code */
    public $need_zip_code;
    /** @var string Zip Code Format */
    public $zip_code_format;
    /** @var bool Display or not the tax incl./tax excl. mention in the front office */
    public $display_tax_label = true;
    /** @var bool Status for delivery */
    public $active = true;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'country', 'primary' => 'id_country', 'multilang' => true, 'fields' => [
        'id_zone' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true],
        'id_currency' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbDefault' => '0'],
        'iso_code' => ['type' => self::TYPE_STRING, 'validate' => 'isLanguageIsoCode', 'required' => true, 'size' => 3],
        'call_prefix' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbType' => 'int(10)', 'dbDefault' => '0'],
        'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'],
        'contains_states' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'need_identification_number' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'need_zip_code' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
        'zip_code_format' => ['type' => self::TYPE_STRING, 'validate' => 'isZipCodeFormat', 'size' => 12, 'dbDefault' => ''],
        'display_tax_label' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbType' => 'tinyint(1)'],
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 64],
    ], 'associations' => ['zone' => ['type' => self::HAS_ONE], 'currency' => ['type' => self::HAS_ONE]], 'keys' => ['country' => ['country_' => ['type' => Object_Model::KEY, 'columns' => ['id_zone']], 'country_iso_code' => ['type' => Object_Model::KEY, 'columns' => ['iso_code']]], 'country_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectsNodeName' => 'countries', 'fields' => ['id_zone' => ['xlink_resource' => 'zones'], 'id_currency' => ['xlink_resource' => 'currencies']]];
    /**
     * @param int $idShop
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_countries_by_id_shop($id_shop, $id_lang)
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('country', 'c')->left_join('country_shop', 'cs', 'cs.`id_country` = c.`id_country` AND cs.`id_shop` = ' . (int) $id_shop)->left_join('country_lang', 'cl', 'cl.`id_country` = c.`id_country` AND cl.`id_lang` = ' . (int) $id_lang));
    }
    /**
     * Get a country ID by its iso code
     *
     * @param string $isoCode Country iso code
     * @param bool $active return only active countries
     *
     * @return false|int Country ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_by_iso($iso_code, $active = false)
    {
        if (Validate::is_language_iso_code($iso_code)) {
            $result = Db::read_only()->get_row((new Db_Query())->select('`id_country`')->from('country')->where('`iso_code` = \'' . p_sql(strtoupper($iso_code)) . '\'')->where($active ? '`active` = 1' : ''));
            if (isset($result['id_country'])) {
                return (int) $result['id_country'];
            }
        }
        return false;
    }
    /**
     * @param int $idCountry
     *
     * @return int | false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_id_zone($id_country)
    {
        $id_country = (int) $id_country;
        if (!$id_country) {
            return false;
        }
        $key = 'country_getIdZone_' . $id_country;
        if (!Cache::is_stored($key)) {
            $result = Db::read_only()->get_row((new Db_Query())->select('`id_zone`')->from('country')->where('`id_country` = ' . $id_country));
            $zone_id = isset($result['id_zone']) && $result['id_zone'] ? (int) $result['id_zone'] : false;
            Cache::store($key, $zone_id);
            return $zone_id;
        }
        return Cache::retrieve($key);
    }
    /**
     * Get a country name with its ID
     *
     * @param int $idLang Language ID
     * @param int $idCountry Country ID
     *
     * @return string | false Country name
     *
     * @throws PrestaShopException
     */
    public static function get_name_by_id($id_lang, $id_country)
    {
        $id_lang = (int) $id_lang;
        $id_country = (int) $id_country;
        $key = 'country_getNameById_' . $id_country . '_' . $id_lang;
        if (!Cache::is_stored($key)) {
            $result = Db::read_only()->get_value((new Db_Query())->select('`name`')->from('country_lang')->where('`id_lang` = ' . $id_lang)->where('`id_country` = ' . $id_country));
            Cache::store($key, $result);
            return $result;
        }
        return Cache::retrieve($key);
    }
    /**
     * Get a country iso with its ID
     *
     * @param int $idCountry Country ID
     *
     * @return string | false Country iso
     *
     * @throws PrestaShopException
     */
    public static function get_iso_by_id($id_country)
    {
        $id_country = (int) $id_country;
        $key = 'country_getIsoById_' . $id_country;
        if (!Cache::is_stored($key)) {
            $result = Db::read_only()->get_value((new Db_Query())->select('`iso_code`')->from('country')->where('`id_country` = ' . $id_country));
            Cache::store($key, $result);
            return $result;
        }
        return Cache::retrieve($key);
    }
    /**
     * Get a country id with its name
     *
     * @param int|null $idLang Language ID
     * @param string $countryName Country Name
     *
     * @return int | false Country ID
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_id_by_name($id_lang, $country_name)
    {
        $id_lang = (int) $id_lang;
        $result = Db::read_only()->get_row((new Db_Query())->select('`id_country`')->from('country_lang')->where('`name` = \'' . p_sql($country_name) . '\'')->where($id_lang ? '`id_lang` = ' . $id_lang : ''));
        if (isset($result['id_country'])) {
            return (int) $result['id_country'];
        }
        return false;
    }
    /**
     * @param int $idCountry
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function get_need_zip_code($id_country)
    {
        if (!(int) $id_country) {
            return false;
        }
        return (bool) Db::read_only()->get_value((new Db_Query())->select('`need_zip_code`')->from('country')->where('`id_country` = ' . (int) $id_country));
    }
    /**
     * @param int $idCountry
     *
     * @return string | false
     *
     * @throws PrestaShopException
     */
    public static function get_zip_code_format($id_country)
    {
        $id_country = (int) $id_country;
        if (!$id_country) {
            return false;
        }
        $zip_code_format = Db::read_only()->get_value((new Db_Query())->select('`zip_code_format`')->from('country')->where('`id_country` = ' . $id_country));
        if (isset($zip_code_format) && $zip_code_format) {
            return $zip_code_format;
        }
        return false;
    }
    /**
     * Returns the default country ID
     *
     * @deprecated 1.0.0 use $context->country->id instead
     * @return int default country id
     */
    public static function get_default_country_id()
    {
        Tools::display_as_deprecated();
        return (int) Context::get_context()->country->id;
    }
    /**
     * @param int $idZone
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_countries_by_zone_id($id_zone, $id_lang)
    {
        $id_zone = (int) $id_zone;
        $id_lang = (int) $id_lang;
        return Db::read_only()->get_array((new Db_Query())->select('c.*, cl.*')->from('country', 'c')->join(Shop::add_sql_association('country', 'c', false))->left_join('state', 's', 's.`id_country` = c.`id_country`')->left_join('country_lang', 'cl', 'c.`id_country` = cl.`id_country` AND cl.`id_lang` = ' . $id_lang)->where('c.`id_zone` = ' . $id_zone . ' OR s.`id_zone` = ' . $id_zone));
    }
    /**
     * @param int $idCountry
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function contains_states($id_country)
    {
        return (bool) Db::read_only()->get_value((new Db_Query())->select('`contains_states`')->from('country')->where('`id_country` = ' . (int) $id_country));
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (!parent::delete()) {
            return false;
        }
        return Db::get_instance()->delete('cart_rule_country', '`id_country` = ' . (int) $this->id);
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_need_dni()
    {
        return Country::is_need_dni_by_country_id($this->id);
    }
    /**
     * @param int $idCountry
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_need_dni_by_country_id($id_country)
    {
        return (bool) Db::read_only()->get_value((new Db_Query())->select('`need_identification_number`')->from('country')->where('`id_country` = ' . (int) $id_country));
    }
    /**
     * @param array $idsCountries
     * @param int $idZone
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function affect_zone_to_selection($ids_countries, $id_zone)
    {
        // cast every array values to int (security)
        $ids_countries = array_map(intval(...), $ids_countries);
        return Db::get_instance()->update('country', ['id_zone' => (int) $id_zone], '`id_country` IN (' . implode(',', $ids_countries) . ')');
    }
    /**
     * Replace letters of zip code format And check this format on the zip code
     *
     * @param string $zipCode
     *
     * @return bool
     */
    public function check_zip_code($zip_code)
    {
        $zip_regexp = '/^' . $this->zip_code_format . '$/ui';
        $zip_regexp = str_replace(' ', '( |)', $zip_regexp);
        $zip_regexp = str_replace('-', '(-|)', $zip_regexp);
        $zip_regexp = str_replace('N', '[0-9]', $zip_regexp);
        $zip_regexp = str_replace('L', '[a-zA-Z]', $zip_regexp);
        $zip_regexp = str_replace('C', $this->iso_code, $zip_regexp);
        return (bool) preg_match($zip_regexp, (string) $zip_code);
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
        return parent::add($auto_date, $null_values) && static::add_module_restrictions([], [['id_country' => $this->id]], []);
    }
    /**
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function add_module_restrictions(array $shops = [], array $countries = [], array $modules = [])
    {
        if (!count($shops)) {
            $shops = Shop::get_shops(true, null, true);
        }
        if (!count($countries)) {
            $countries = Country::get_countries((int) Context::get_context()->cookie->id_lang);
        }
        if (!count($modules)) {
            $modules = Module::get_payment_modules();
        }
        $insert = [];
        foreach ($shops as $id_shop) {
            foreach ($countries as $country) {
                foreach ($modules as $module) {
                    $insert[] = ['id_module' => (int) $module['id_module'], 'id_shop' => (int) $id_shop, 'id_country' => (int) $country['id_country']];
                }
            }
        }
        if (!empty($insert)) {
            return Db::get_instance()->insert('module_country', $insert, false, true, Db::INSERT_IGNORE);
        }
        return true;
    }
    /**
     * Return available countries
     *
     * @param int $idLang Language ID
     * @param bool $active return only active countries
     * @param bool $containStates return only country with states
     * @param bool $listStates Include the states list with the returned list
     *
     * @return array Countries and corresponding zones
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_countries($id_lang, $active = false, $contain_states = false, $list_states = true)
    {
        $countries = [];
        $connection = Db::read_only();
        $result = $connection->get_array((new Db_Query())->select('cl.*, c.*, cl.`name` AS `country`, z.`name` AS `zone`')->from('country', 'c')->join(Shop::add_sql_association('country', 'c'))->left_join('country_lang', 'cl', 'c.`id_country` = cl.`id_country` AND cl.`id_lang` = ' . (int) $id_lang)->left_join('zone', 'z', 'z.`id_zone` = c.`id_zone`')->where($active ? 'c.`active` = 1' : '')->where($contain_states ? 'c.`contains_states` = ' . (int) $contain_states : '')->order_by('cl.`name` ASC'));
        foreach ($result as $row) {
            $countries[$row['id_country']] = $row;
        }
        if ($list_states) {
            $result = $connection->get_array((new Db_Query())->select('*')->from('state')->order_by('`name` ASC'));
            foreach ($result as $row) {
                if (isset($countries[$row['id_country']]) && $row['active'] == 1) {
                    /* Does not keep the state if its country has been disabled and not selected */
                    $countries[$row['id_country']]['states'][] = $row;
                }
            }
        }
        return $countries;
    }
}