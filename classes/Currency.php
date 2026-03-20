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
 * Class CurrencyCore
 */
class Currency_Core extends Object_Model
{
    /** @var array Currency cache */
    protected static $currencies = [];
    /**
     * @var array
     */
    protected static $count_active_currencies = [];
    /**
     * @var array Currency formatters
     */
    protected static $currency_formatters;
    /**
     * @var int|null Object ID
     */
    public $id;
    /**
     * @var string Name
     */
    public $name;
    /**
     * @var string Iso code
     */
    public $iso_code;
    /**
     * @var string Iso code numeric
     */
    public $iso_code_num;
    /**
     * @var string Symbol for short display
     */
    public $sign;
    /**
     * @var bool used for displaying blank between sign and price
     */
    public $blank;
    /**
     * @var float exchange rate from euros
     */
    public $conversion_rate;
    /**
     * @var bool True if currency has been deleted (staying in database as deleted)
     */
    public $deleted = 0;
    /**
     * @var int ID used for displaying prices
     */
    public $format;
    /**
     * @var bool Display decimals on prices
     */
    public $decimals;
    /**
     * @var int Display precision
     */
    public $decimal_places;
    /**
     * @var bool active
     */
    public $active;
    /**
     * contains the sign to display before price, according to its format
     *
     * @var string
     */
    public $prefix;
    /**
     * contains the sign to display after price, according to its format
     *
     * @var string
     */
    public $suffix;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'currency', 'primary' => 'id_currency', 'multilang_shop' => true, 'fields' => ['name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 32], 'iso_code' => ['type' => self::TYPE_STRING, 'validate' => 'isLanguageIsoCode', 'required' => true, 'size' => 3, 'dbDefault' => '0'], 'iso_code_num' => ['type' => self::TYPE_STRING, 'validate' => 'isNumericIsoCode', 'size' => 3, 'dbDefault' => '0'], 'sign' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 8], 'blank' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'], 'format' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'size' => 1, 'dbDefault' => '0'], 'decimals' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbDefault' => '1'], 'decimal_places' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true, 'dbDefault' => '2'], 'conversion_rate' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'required' => true, 'shop' => true, 'size' => 13], 'deleted' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'], 'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '1']], 'keys' => ['currency_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectsNodeName' => 'currencies'];
    /**
     * CurrencyCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
        // prefix and suffix are convenient shortcut for displaying
        // price sign before or after the price number
        $this->prefix = $this->format % 2 != 0 ? $this->sign . ' ' : '';
        $this->suffix = $this->format % 2 == 0 ? ' ' . $this->sign : '';
        if (!$this->conversion_rate) {
            $this->conversion_rate = 1;
        }
    }
    /**
     * @param int $idShop
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_currencies_by_id_shop($id_shop = 0)
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('currency', 'c')->left_join('currency_shop', 'cs', 'cs.`id_currency` = c.`id_currency`')->where($id_shop ? 'cs.`id_shop` = ' . (int) $id_shop : '')->where('c.`deleted` = 0')->order_by('`name` ASC'));
    }
    /**
     * @param int $idModule
     * @param int|null $idShop
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_payment_currencies_special($id_module, $id_shop = null)
    {
        if (is_null($id_shop)) {
            $id_shop = Context::get_context()->shop->id;
        }
        return Db::read_only()->get_row((new Db_Query())->select('*')->from('module_currency')->where('`id_module` = ' . (int) $id_module)->where('`id_shop` = ' . (int) $id_shop));
    }
    /**
     * @param int $idModule
     * @param int|null $idShop
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_payment_currencies($id_module, $id_shop = null)
    {
        if (is_null($id_shop)) {
            $id_shop = Context::get_context()->shop->id;
        }
        return Db::read_only()->get_array((new Db_Query())->select('c.*')->from('module_currency', 'mc')->left_join('currency', 'c', 'c.`id_currency` = mc.`id_currency`')->where('c.`deleted` = 0')->where('mc.`id_module` = ' . (int) $id_module)->where('c.`active` = 1')->where('mc.`id_shop` = ' . (int) $id_shop)->order_by('c.`name` ASC'));
    }
    /**
     * @param int $idModule
     * @param int|null $idShop
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function check_payment_currencies($id_module, $id_shop = null)
    {
        if (empty($id_module)) {
            return [];
        }
        if (is_null($id_shop)) {
            $id_shop = Context::get_context()->shop->id;
        }
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('module_currency')->where('`id_module` = ' . (int) $id_module)->where('`id_shop` = ' . (int) $id_shop));
    }
    /**
     * @param int $idCurrency
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_currency($id_currency)
    {
        return Db::read_only()->get_row((new Db_Query())->select('*')->from('currency')->where('`deleted` = 0')->where('`id_currency` = ' . (int) $id_currency));
    }
    /**
     * @return string|null
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function refresh_currencies()
    {
        if (!$default_currency = Currency::get_default_currency()) {
            return Tools::display_error('No default currency');
        }
        $currency_rates = Currency_Rate_Module::get_currency_rate_info();
        if (!is_array($currency_rates)) {
            return null;
        }
        $currency_rates = array_filter($currency_rates);
        $module_rates = [];
        foreach ($currency_rates as $currency => $module) {
            if (mb_strtoupper((string) $currency) === mb_strtoupper((string) $default_currency->iso_code)) {
                continue;
            }
            if (!isset($module_rates[$module->id])) {
                $module_rates[$module->id] = [mb_strtoupper((string) $currency)];
            } else {
                $module_rates[$module->id][] = mb_strtoupper((string) $currency);
            }
        }
        foreach ($module_rates as $id_module => $currencies) {
            $rates = Hook::get_response('actionRetrieveCurrencyRates', $id_module, ['currencies' => $currencies, 'baseCurrency' => mb_strtoupper((string) $default_currency->iso_code)]);
            if (is_array($rates)) {
                foreach ($rates as $iso_code => $rate) {
                    $currency = Currency::get_currency_instance(Currency::get_id_by_iso_code($iso_code));
                    if (Validate::is_loaded_object($currency)) {
                        $currency->conversion_rate = (float) $rate;
                        $currency->save();
                    }
                }
            }
        }
        return null;
    }
    /**
     * @return bool|Currency
     *
     * @throws PrestaShopException
     */
    public static function get_default_currency()
    {
        $id_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        if ($id_currency == 0) {
            return false;
        }
        return new Currency($id_currency);
    }
    /**
     * Return available currencies
     *
     * @param bool $object
     * @param bool $active
     * @param bool $groupBy
     *
     * @return array Currencies
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_currencies($object = false, $active = true, $group_by = false)
    {
        $tab = Db::read_only()->get_array((new Db_Query())->select('*')->from('currency', 'c')->join(Shop::add_sql_association('currency', 'c'))->where('`deleted` = 0')->where($active ? 'c.`active` = 1' : '')->group_by($group_by ? 'c.`id_currency`' : '')->order_by('`name` ASC'));
        if ($object) {
            foreach ($tab as $key => $currency) {
                $tab[$key] = Currency::get_currency_instance($currency['id_currency']);
            }
        }
        return $tab;
    }
    /**
     * @param int $id
     *
     * @return Currency
     *
     * @throws PrestaShopException
     */
    public static function get_currency_instance($id)
    {
        $id = (int) $id;
        if (!isset(static::$currencies[$id])) {
            static::$currencies[$id] = new Currency($id);
        }
        return static::$currencies[$id];
    }
    /**
     * Refresh the currency exchange rate
     * The XML file define exchange rate for each from a default currency ($isoCodeSource).
     *
     * @param SimpleXMLElement $data XML content which contains all the exchange rates
     * @param string $isoCodeSource The default currency used in the XML file
     * @param Currency $defaultCurrency The default currency object
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @deprecated 1.0.0
     */
    public function refresh_currency($data, $iso_code_source, $default_currency): void
    {
        // fetch the exchange rate of the default currency
        $exchange_rate = 1;
        $tmp = $this->conversion_rate;
        if ($default_currency->iso_code != $iso_code_source) {
            foreach ($data->currency as $currency) {
                if ($currency['iso_code'] == $default_currency->iso_code) {
                    $exchange_rate = round((float) $currency['rate'], 6);
                    break;
                }
            }
        }
        if ($default_currency->iso_code == $this->iso_code) {
            $this->conversion_rate = 1;
        } else {
            if ($this->iso_code == $iso_code_source) {
                $rate = 1;
            } else {
                foreach ($data->currency as $obj) {
                    if ($this->iso_code == strval($obj['iso_code'])) {
                        $rate = (float) $obj['rate'];
                        break;
                    }
                }
            }
            if (isset($rate)) {
                $this->conversion_rate = round($rate / $exchange_rate, 6);
            }
        }
        if ($tmp != $this->conversion_rate) {
            $this->update();
        }
    }
    /**
     * Get current currency
     *
     * @deprecated 1.0.0 use $context->currency instead
     * @return Currency
     */
    public static function get_current()
    {
        Tools::display_as_deprecated();
        return Context::get_context()->currency;
    }
    /**
     * @param int|null $idShop
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_multi_currency_activated($id_shop = null)
    {
        return Currency::count_active_currencies($id_shop) > 1;
    }
    /**
     * @param int|null $idShop
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function count_active_currencies($id_shop = null)
    {
        if ($id_shop === null) {
            $id_shop = (int) Context::get_context()->shop->id;
        }
        if (!isset(static::$count_active_currencies[$id_shop])) {
            static::$count_active_currencies[$id_shop] = Db::read_only()->get_value((new Db_Query())->select('COUNT(DISTINCT c.`id_currency`)')->from('currency', 'c')->left_join('currency_shop', 'cs', 'cs.`id_currency` = c.`id_currency`')->where('cs.`id_shop` = ' . (int) $id_shop)->where('c.`deleted` = 0')->where('c.`active` = 1'));
        }
        return static::$count_active_currencies[$id_shop];
    }
    /**
     * Overriding check if currency rate is not empty and if currency with the same iso code already exists.
     * If it's true, currency is not added.
     *
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if ((float) $this->conversion_rate <= 0) {
            return false;
        }
        if (static::exists($this->iso_code, $this->iso_code_num)) {
            return false;
        }
        parent::add($auto_date, $null_values);
        Currency_Rate_Module::scan_missing_currency_rate_modules($this->iso_code);
        return true;
    }
    /**
     * Check if a curency already exists.
     *
     * @param int|string $isoCode int for iso code number string for iso code
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function exists($iso_code, $iso_code_num, $id_shop = 0)
    {
        if (is_int($iso_code)) {
            $id_currency_exists = Currency::get_id_by_iso_code_num((int) $iso_code_num, (int) $id_shop);
        } else {
            $id_currency_exists = Currency::get_id_by_iso_code($iso_code, (int) $id_shop);
        }
        if ($id_currency_exists) {
            return true;
        }
        return false;
    }
    /**
     * @param string $isoCodeNum
     * @param int $idShop
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_iso_code_num($iso_code_num, $id_shop = 0)
    {
        $query = Currency::get_id_by_query($id_shop);
        $query->where('iso_code_num = \'' . p_sql($iso_code_num) . '\'');
        return (int) Db::read_only()->get_value($query->build());
    }
    /**
     * @param int $idShop
     *
     * @return DbQuery
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_query($id_shop = 0)
    {
        $query = new Db_Query();
        $query->select('c.id_currency');
        $query->from('currency', 'c');
        $query->where('deleted = 0');
        if (Shop::is_feature_active() && $id_shop > 0) {
            $query->left_join('currency_shop', 'cs', 'cs.id_currency = c.id_currency');
            $query->where('id_shop = ' . (int) $id_shop);
        }
        return $query;
    }
    /**
     * @param string $isoCode
     * @param int $idShop
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_iso_code($iso_code, $id_shop = 0)
    {
        $cache_id = 'Currency::getIdByIsoCode_' . p_sql($iso_code) . '-' . (int) $id_shop;
        if (!Cache::is_stored($cache_id)) {
            $query = Currency::get_id_by_query($id_shop);
            $query->where('iso_code = \'' . p_sql($iso_code) . '\'');
            $result = (int) Db::read_only()->get_value($query->build());
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * @param array $selection
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_selection($selection)
    {
        if (!is_array($selection)) {
            return false;
        }
        $res = [];
        foreach ($selection as $id) {
            $obj = new Currency((int) $id);
            $res[$id] = $obj->delete();
        }
        foreach ($res as $value) {
            if (!$value) {
                return false;
            }
        }
        return true;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if ($this->id == Configuration::get('PS_CURRENCY_DEFAULT')) {
            $result = Db::read_only()->get_row((new Db_Query())->select('`id_currency`')->from('currency')->where('`id_currency` != ' . (int) $this->id)->where('`deleted` = 0'));
            if (!$result['id_currency']) {
                return false;
            }
            Configuration::update_value('PS_CURRENCY_DEFAULT', $result['id_currency']);
        }
        $this->deleted = 1;
        $conn = Db::get_instance();
        $res = (bool) $conn->delete('module_currency', '`id_currency` = ' . (int) $this->id);
        $conn->delete('currency_module', '`id_currency` = ' . (int) $this->id);
        return $res && $this->update();
    }
    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($autodate = true, $null_values = false)
    {
        if ((float) $this->conversion_rate <= 0) {
            return false;
        }
        return parent::update($null_values);
    }
    /**
     * Return formated sign
     *
     * @param string $side left or right
     *
     * @return string formated sign
     */
    public function get_sign($side = null)
    {
        if (!$side) {
            return $this->sign;
        }
        $formatted_strings = ['left' => $this->sign . ' ', 'right' => ' ' . $this->sign];
        $formats = [1 => ['left' => &$formatted_strings['left'], 'right' => ''], 2 => ['left' => '', 'right' => &$formatted_strings['right']], 3 => ['left' => &$formatted_strings['left'], 'right' => ''], 4 => ['left' => '', 'right' => &$formatted_strings['right']], 5 => ['left' => '', 'right' => &$formatted_strings['right']]];
        return $formats[$this->format][$side] ?? $this->sign;
    }
    /**
     * @return float
     *
     * @throws PrestaShopException
     */
    public function get_conversation_rate()
    {
        return $this->id != (int) Configuration::get('PS_CURRENCY_DEFAULT') ? (float) $this->conversion_rate : 1.0;
    }
    /**
     * Should the currency be automatically formatted?
     *
     * @return bool
     */
    public function get_mode()
    {
        Tools::display_as_deprecated();
        return false;
    }
    /**
     * Get the modes for all currencies
     * NOTE: the keys in this array are the upper cased ISO codes
     *
     * @return array
     */
    public static function get_modes()
    {
        Tools::display_as_deprecated();
        return [];
    }
    /**
     * Get map from currencies to javascript function used for formatting
     * The keys in this array are the upper cased ISO codes
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function get_javascript_formatters()
    {
        $formatters = [];
        foreach (static::get_formatters() as $id => $formatter) {
            $currency = Currency::get_currency_instance((int) $id);
            if (isset($formatter['js']) && $formatter['js']) {
                $formatters[strtoupper((string) $currency->iso_code)] = $formatter['js'];
            }
        }
        return $formatters;
    }
    /**
     * Returns currency formatter associated with this currency, if exists
     *
     * @return callable|null
     *
     * @throws PrestaShopException
     */
    public function get_formatter()
    {
        $id = (int) $this->id;
        if ($id) {
            $formatters = static::get_formatters();
            if (isset($formatters[$id])) {
                return $formatters[$id]['php'];
            }
        }
        return null;
    }
    /**
     * Returns currency formatters
     *
     * @throws PrestaShopException
     */
    protected static function get_formatters()
    {
        if (is_null(static::$currency_formatters)) {
            static::$currency_formatters = static::resolve_formatters();
        }
        return static::$currency_formatters;
    }
    /**
     * Resolves currency formatters
     *
     * Method calls hook actionGetCurrencyFormatters and return list of all formatters
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function resolve_formatters()
    {
        $currencies = static::get_currencies(false, false);
        $results = Hook::get_responses('actionGetCurrencyFormatters', ['currencies' => $currencies]);
        $formatters = [];
        foreach ($results as $module_formatters) {
            foreach ($module_formatters as $currency_id => $definition) {
                $currency_id = (int) $currency_id;
                if (isset($formatters[$currency_id])) {
                    trigger_error('Multiple modules provided formatter for currency ' . $currency_id, E_USER_WARNING);
                }
                $formatters[$currency_id] = $definition;
            }
        }
        return $formatters;
    }
    /**
     * Returns currency display precision
     *
     * @return int
     */
    public function get_display_precision()
    {
        if ($this->decimals) {
            return $this->decimal_places;
        }
        return 0;
    }
}