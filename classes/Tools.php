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
use Guzzle_Http\Client;
use Phpsql_Parser\Phpsql_Parser;
use Thirtybees\Core\Dependency_Injection\Service_Locator;
use Thirtybees\Core\Error\Error_Utils;
/**
 * Class ToolsCore
 */
class Tools_Core
{
    /**
     * Bootstring parameter values
     */
    public const PUNYCODE_BASE = 36;
    public const PUNYCODE_TMIN = 1;
    public const PUNYCODE_TMAX = 26;
    public const PUNYCODE_SKEW = 38;
    public const PUNYCODE_DAMP = 700;
    public const PUNYCODE_INITIAL_BIAS = 72;
    public const PUNYCODE_INITIAL_N = 128;
    public const PUNYCODE_PREFIX = 'xn--';
    public const PUNYCODE_DELIMITER = '-';
    /**
     * @var int|null
     */
    public static $round_mode;
    /**
     * @var bool[]
     */
    protected static $file_exists_cache = [];
    /**
     * @var int
     */
    protected static $_force_compile;
    /**
     * @var int
     */
    protected static $_caching;
    /**
     * @var string
     */
    protected static $_user_plateform;
    /**
     * @var string
     */
    protected static $_user_browser;
    /**
     * @var int|null
     */
    protected static $_cache_nb_media_servers;
    /**
     * Random password generator
     *
     * @param int $length Desired length (optional)
     * @param string $flag Output type (NUMERIC, ALPHANUMERIC, NO_NUMERIC, RANDOM)
     *
     * @return bool|string Password
     */
    public static function passwd_gen($length = 8, $flag = 'ALPHANUMERIC')
    {
        $length = (int) $length;
        if ($length <= 0) {
            return false;
        }
        switch ($flag) {
            case 'NUMERIC':
                $str = '0123456789';
                break;
            case 'NO_NUMERIC':
                $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
            case 'RANDOM':
                $num_bytes = (int) ceil($length * 0.75);
                $bytes = static::get_bytes($num_bytes);
                return substr(rtrim(base64_encode($bytes), '='), 0, $length);
            case 'ALPHANUMERIC':
            default:
                $str = 'abcdefghijkmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                break;
        }
        $bytes = Tools::get_bytes($length);
        $position = 0;
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $position = ($position + ord($bytes[$i])) % strlen($str);
            $result .= $str[$position];
        }
        return $result;
    }
    /**
     * Random bytes generator
     *
     * @param int $length Desired length of random bytes
     *
     * @return string Random bytes
     */
    public static function get_bytes($length): string
    {
        $length = (int) $length;
        if ($length > 0) {
            try {
                return random_bytes($length);
            } catch (Exception) {
            }
        }
        return '';
    }
    /**
     * Redirect user to another page
     *
     * @param string|null $url Desired URL
     * @param false|string $baseUri Base URI (optional)
     * @param string|string[]|null $headers A list of headers to send before redirection
     * @throws PrestaShopException
     */
    public static function redirect($url, $base_uri = __PS_BASE_URI__, ?Link $link = null, $headers = null): void
    {
        $url = (string) $url;
        if (!$link) {
            $link = Context::get_context()->link;
        }
        if (!str_contains($url, 'http://') && !str_contains($url, 'https://') && $link) {
            if (str_starts_with($url, $base_uri)) {
                $url = substr($url, strlen($base_uri));
            }
            if (str_contains($url, 'index.php?controller=') && str_starts_with($url, 'index.php/')) {
                $url = substr($url, strlen('index.php?controller='));
                if (Configuration::get('PS_REWRITING_SETTINGS')) {
                    $url = Tools::str_replace_first('&', '?', $url);
                }
            }
            $explode = explode('?', $url);
            $url = $link->get_page_link($explode[0]);
            if (isset($explode[1])) {
                $url .= '?' . $explode[1];
            }
        }
        // Send additional headers
        if ($headers) {
            if (!is_array($headers)) {
                $headers = [$headers];
            }
            foreach ($headers as $header) {
                header($header);
            }
        }
        header('Location: ' . $url);
        exit;
    }
    /**
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @param int $cur
     *
     * @return string
     */
    public static function str_replace_first($search, $replace, $subject, $cur = 0)
    {
        $pos = strpos($subject, $search, $cur);
        if ($pos !== false) {
            return substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }
    /**
     * Redirect URLs already containing PS_BASE_URI
     *
     * @param string $url Desired URL
     *
     * @throws PrestaShopException
     */
    public static function redirect_link($url): void
    {
        if (!preg_match('@^https?://@i', $url)) {
            if (str_contains($url, __PS_BASE_URI__) && str_starts_with($url, __PS_BASE_URI__)) {
                $url = substr($url, strlen(__PS_BASE_URI__));
            }
            if (str_contains($url, 'index.php?controller=') && str_starts_with($url, 'index.php/')) {
                $url = substr($url, strlen('index.php?controller='));
            }
            $explode = explode('?', $url);
            $url = Context::get_context()->link->get_page_link($explode[0]);
            if (isset($explode[1])) {
                $url .= '?' . $explode[1];
            }
        }
        header('Location: ' . $url);
        exit;
    }
    /**
     * Redirect user to another admin page
     *
     * @param string $url Desired URL
     */
    public static function redirect_admin(string $url): never
    {
        header('Location: ' . $url);
        exit;
    }
    /**
     * getShopProtocol return the available protocol for the current shop in use
     * SSL if Configuration is set on and available for the server
     *
     *
     * @throws PrestaShopException
     */
    public static function get_shop_protocol(): string
    {
        return Configuration::get('PS_SSL_ENABLED') || !empty($_SERVER['HTTPS']) && mb_strtolower((string) $_SERVER['HTTPS']) != 'off' ? 'https://' : 'http://';
    }
    /**
     * @param string $str
     *
     * @return bool|string
     *
     * @deprecated 1.0.4 Use mb_strtolower for UTF-8 or strtolower if guaranteed ASCII
     */
    public static function strtolower($str)
    {
        if (is_array($str)) {
            return false;
        }
        return mb_strtolower((string) $str, 'utf-8');
    }
    /**
     * getProtocol return the set protocol according to configuration (http[s])
     *
     * @param bool $useSsl true if require ssl
     *
     * @return String (http|https)
     */
    public static function get_protocol($use_ssl = null): string
    {
        return $use_ssl ? 'https://' : 'http://';
    }
    /**
     * Get the server variable REMOTE_ADDR, or the first ip of HTTP_X_FORWARDED_FOR (when using proxy)
     *
     * @return string $remote_addr ip of client
     */
    public static function get_remote_addr()
    {
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers = $_SERVER;
        }
        if (array_key_exists('X-Forwarded-For', $headers)) {
            $_SERVER['HTTP_X_FORWARDED_FOR'] = $headers['X-Forwarded-For'];
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] && (!isset($_SERVER['REMOTE_ADDR']) || preg_match('/^127\..*/i', trim($_SERVER['REMOTE_ADDR'])) || preg_match('/^172\.16.*/i', trim($_SERVER['REMOTE_ADDR'])) || preg_match('/^192\.168\.*/i', trim($_SERVER['REMOTE_ADDR'])) || preg_match('/^10\..*/i', trim($_SERVER['REMOTE_ADDR'])))) {
            if (strpos((string) $_SERVER['HTTP_X_FORWARDED_FOR'], ',')) {
                $ips = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
                return $ips[0];
            }
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'];
    }
    /**
     * Get the current url prefix protocol (https/http)
     *
     * @return string protocol
     */
    public static function get_current_url_protocol_prefix(): string
    {
        if (Tools::using_secure_mode()) {
            return 'https://';
        }
        return 'http://';
    }
    /**
     * Check if the current page use SSL connection on not
     *
     * @return bool uses SSL
     */
    public static function using_secure_mode()
    {
        if (isset($_SERVER['HTTPS'])) {
            return in_array(mb_strtolower((string) $_SERVER['HTTPS']), [1, 'on']);
        }
        // $_SERVER['SSL'] exists only in some specific configuration
        if (isset($_SERVER['SSL'])) {
            return in_array(mb_strtolower((string) $_SERVER['SSL']), [1, 'on']);
        }
        // $_SERVER['REDIRECT_HTTPS'] exists only in some specific configuration
        if (isset($_SERVER['REDIRECT_HTTPS'])) {
            return in_array(mb_strtolower((string) $_SERVER['REDIRECT_HTTPS']), [1, 'on']);
        }
        if (isset($_SERVER['HTTP_SSL'])) {
            return in_array(mb_strtolower((string) $_SERVER['HTTP_SSL']), [1, 'on']);
        }
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return mb_strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https';
        }
        return false;
    }
    /**
     * Secure an URL referrer
     *
     * @param string $referrer URL referrer
     *
     * @return string secured referrer
     */
    public static function secure_referrer($referrer)
    {
        if (preg_match('/^http[s]?:\/\/' . Tools::get_server_name() . '(:' . _PS_SSL_PORT_ . ')?\/.*$/Ui', $referrer)) {
            return $referrer;
        }
        return __PS_BASE_URI__;
    }
    /**
     * Get the server variable SERVER_NAME
     *
     * @return string server name
     */
    public static function get_server_name()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_SERVER']) && $_SERVER['HTTP_X_FORWARDED_SERVER']) {
            return $_SERVER['HTTP_X_FORWARDED_SERVER'];
        }
        return $_SERVER['SERVER_NAME'];
    }
    /**
     * Get all values from $_POST/$_GET
     *
     * @return array
     */
    public static function get_all_values()
    {
        return $_POST + $_GET;
    }
    /**
     * @param string $key
     *
     * @return bool
     */
    public static function get_isset($key)
    {
        if (empty($key) || !is_string($key)) {
            return false;
        }
        return isset($_POST[$key]) || (isset($_GET[$key]) ? true : false);
    }
    /**
     * Change language in cookie while clicking on a flag
     *
     *
     * @return string ISO code
     * @throws PrestaShopException
     */
    public static function set_cookie_language(?Cookie $cookie = null)
    {
        if (!$cookie) {
            $cookie = Context::get_context()->cookie;
        }
        /* If language does not exist or is disabled, erase it */
        if ($cookie->id_lang) {
            $lang = new Language((int) $cookie->id_lang);
            if (!Validate::is_loaded_object($lang) || !$lang->active || !$lang->is_associated_to_shop()) {
                $cookie->id_lang = null;
            }
        }
        if (!Configuration::get('PS_DETECT_LANG')) {
            unset($cookie->detect_language);
        }
        /* Automatically detect language if not already defined, detect_language is set in Cookie::update */
        if (!Tools::get_value('isolang') && !Tools::get_int_value('id_lang') && (!$cookie->id_lang || isset($cookie->detect_language)) && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $array = explode(',', mb_strtolower((string) $_SERVER['HTTP_ACCEPT_LANGUAGE']));
            $string = $array[0];
            if (Validate::is_language_code($string)) {
                $lang = Language::get_language_by_ietf_code($string);
                if (Validate::is_loaded_object($lang) && $lang->active && $lang->is_associated_to_shop()) {
                    Context::get_context()->language = $lang;
                    $cookie->id_lang = (int) $lang->id;
                }
            }
        }
        if (isset($cookie->detect_language)) {
            unset($cookie->detect_language);
        }
        /* If language file not present, you must use default language file */
        if (!$cookie->id_lang || !Validate::is_unsigned_id($cookie->id_lang)) {
            $cookie->id_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        }
        $iso = Language::get_iso_by_id((int) $cookie->id_lang);
        $theme_lang_file = _PS_THEME_DIR_ . 'lang/' . $iso . '.php';
        if (file_exists($theme_lang_file)) {
            @include_once $theme_lang_file;
        }
        return $iso;
    }
    /**
     * Get a value from $_POST / $_GET
     * if unavailable, take a default value
     *
     * @param string $key Value key
     * @param array|bool|float|int|string|null $defaultValue (optional)
     *
     * @return array|bool|float|int|string|null Value
     */
    public static function get_value_raw($key, $default_value = false)
    {
        if (empty($key) || !is_string($key)) {
            return false;
        }
        return $_POST[$key] ?? $_GET[$key] ?? $default_value;
    }
    /**
     * Extract price value from $_POST / $_GET
     *
     * @param string $key Value key
     * @param int $precision Precisions
     *
     * @return float parsed price, rounded to $precision
     */
    public static function get_number_value($key, $precision = _TB_PRICE_DATABASE_PRECISION_)
    {
        return static::parse_number(static::get_value_raw($key), $precision);
    }
    /**
     * Get a value from $_POST / $_GET
     * if unavailable, take a default value
     *
     * This method performs basic sanitization of input value
     *
     * @param string $key Value key
     * @param array|bool|float|int|string|null $defaultValue (optional)
     *
     * @return array|bool|float|int|string|null Value
     */
    public static function get_value($key, $default_value = false)
    {
        $ret = static::get_value_raw($key, $default_value);
        if (is_string($ret)) {
            return stripslashes(urldecode((string) preg_replace('/((\%5C0+)|(\%00+))/i', '', urlencode($ret))));
        }
        return $ret;
    }
    /**
     * @param int $defaultValue
     *
     *
     * @since 1.5.0
     */
    public static function get_int_value(string $key, $default_value = 0): int
    {
        if (_PS_MODE_DEV_ && !is_null($default_value)) {
            $type = gettype($default_value);
            if ($type !== 'integer') {
                trigger_error(sprintf('Tools::getIntValue(): Argument #2 ($defaultValue) must be of type int, %s given', $type), E_USER_WARNING);
            }
        }
        return (int) static::get_value_raw($key, (int) $default_value);
    }
    /**
     *
     * @since 1.5.0
     */
    public static function get_bool_value(string $key, bool $default_value = false): bool
    {
        return (bool) static::get_value_raw($key, $default_value);
    }
    /**
     *
     * @since 1.5.0
     */
    public static function get_array_value(string $key, array $default_value = []): array
    {
        $value = static::get_value_raw($key, $default_value);
        if (is_array($value)) {
            return $value;
        }
        if (_PS_MODE_DEV_) {
            $type = gettype($value);
            trigger_error(sprintf('Tools::getArrayValue(): passed value should be array, %s given', $type), E_USER_WARNING);
        }
        return $default_value;
    }
    /**
     * Set cookie id_lang
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function switch_language(?Context $context = null): void
    {
        if (!$context) {
            $context = Context::get_context();
        }
        // Install call the dispatcher and so the switchLanguage
        // Stop this method by checking the cookie
        if (!isset($context->cookie)) {
            return;
        }
        if (($iso = Tools::get_value('isolang')) && Validate::is_language_iso_code($iso) && $id_lang = (int) Language::get_id_by_iso($iso)) {
            $_GET['id_lang'] = $id_lang;
        }
        // update language only if new id is different from old id
        // or if default language changed
        $cookie_id_lang = $context->cookie->id_lang;
        $configuration_id_lang = Configuration::get('PS_LANG_DEFAULT');
        if (($id_lang = Tools::get_int_value('id_lang')) && Validate::is_unsigned_id($id_lang) && $cookie_id_lang != (int) $id_lang || $id_lang == $configuration_id_lang && Validate::is_unsigned_id($id_lang) && $id_lang != $cookie_id_lang) {
            $context->cookie->id_lang = $id_lang;
            $language = new Language($id_lang);
            if (Validate::is_loaded_object($language) && $language->active) {
                $context->language = $language;
            }
            $params = $_GET;
            if (Configuration::get('PS_REWRITING_SETTINGS') || !Language::is_multi_language_activated()) {
                unset($params['id_lang']);
            }
        }
    }
    /**
     * @param AddressCore|null $address
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_country($address = null): int
    {
        $id_country = Tools::get_int_value('id_country');
        if ($id_country && Validate::is_int($id_country)) {
            return (int) $id_country;
        }
        if (isset($address->id_country) && !$id_country && $address->id_country) {
            $id_country = (int) $address->id_country;
        } elseif (Configuration::get('PS_DETECT_COUNTRY') && isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            preg_match('#(?<=-)\w\w|\w\w(?!-)#', (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'], $array);
            if (is_array($array) && isset($array[0]) && Validate::is_language_iso_code($array[0])) {
                $id_country = (int) Country::get_by_iso($array[0], true);
            }
        }
        if (!isset($id_country) || !$id_country) {
            $id_country = (int) Configuration::get('PS_COUNTRY_DEFAULT');
        }
        return (int) $id_country;
    }
    /**
     * Set cookie currency from POST or default currency
     *
     * @param Cookie $cookie
     * @return Currency object
     *
     * @throws PrestaShopException
     */
    public static function set_currency($cookie)
    {
        if (Tools::is_submit('SubmitCurrency') && $id_currency = Tools::get_int_value('id_currency')) {
            $currency = Currency::get_currency_instance($id_currency);
            if (is_object($currency) && $currency->id && !$currency->deleted && $currency->is_associated_to_shop()) {
                $cookie->id_currency = (int) $currency->id;
            }
        }
        $currency = null;
        if ((int) $cookie->id_currency) {
            $currency = Currency::get_currency_instance((int) $cookie->id_currency);
        }
        if (!Validate::is_loaded_object($currency) || $currency->deleted || !$currency->active) {
            $currency = Currency::get_currency_instance(Configuration::get('PS_CURRENCY_DEFAULT'));
        }
        $cookie->id_currency = (int) $currency->id;
        if ($currency->is_associated_to_shop()) {
            return $currency;
        }
        // get currency from context
        $currency_ids = Shop::get_entity_ids('currency', Context::get_context()->shop->id, true, true);
        if (isset($currency_ids[0]) && $currency_ids[0]['id_currency']) {
            $cookie->id_currency = $currency_ids[0]['id_currency'];
            return Currency::get_currency_instance((int) $cookie->id_currency);
        }
        return $currency;
    }
    /**
     * Check if submit has been posted
     *
     * @param string $submit submit name
     */
    public static function is_submit(string $submit): bool
    {
        return isset($_POST[$submit]) || isset($_POST[$submit . '_x']) || isset($_POST[$submit . '_y']) || isset($_GET[$submit]) || isset($_GET[$submit . '_x']) || isset($_GET[$submit . '_y']);
    }
    /**
     * @param float $number
     * @param Currency|array|int|null $currency
     */
    public static function display_number($number, $currency = null): string
    {
        $thousands_separator = ' ';
        if (!is_null($currency)) {
            if (is_array($currency) && array_key_exists('format', $currency)) {
                $format = (int) $currency['format'];
            } elseif (is_object($currency) && property_exists($currency, 'format')) {
                $format = (int) $currency->format;
            } else {
                $format = 0;
            }
            if ($format === 1 || $format === 4) {
                $thousands_separator = ',';
            }
        }
        return number_format($number, 0, '.', $thousands_separator);
    }
    /**
     * @param Smarty_Internal_Template $smarty
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function display_price_smarty(array $params, $smarty)
    {
        if (array_key_exists('currency', $params)) {
            $currency = Currency::get_currency_instance((int) $params['currency']);
            if (Validate::is_loaded_object($currency)) {
                try {
                    return Tools::display_price($params['price'], $currency, false);
                } catch (Presta_Shop_Exception) {
                    return '';
                }
            }
        }
        try {
            return Tools::display_price($params['price']);
        } catch (Presta_Shop_Exception) {
            return '';
        }
    }
    /**
     * Return a formatted price string, with currency sign.
     *
     * Formatting should match JavaScript function displayPrice (in tools.js).
     * Which means: don't forget to transport any changes made here to there.
     *
     * @param float $price Product price
     * @param Currency|array|int|null $tbCurrency
     * @param bool $noUtf8
     * @param bool|null $auto
     *
     * @return string Price correctly formatted (sign, decimal separator...)
     *
     *              For them, the auto option is now available.
     * @throws PrestaShopException
     */
    public static function display_price($price, $tb_currency = null, $no_utf8 = false, ?Context $context = null, $auto = null)
    {
        if (!is_numeric($price)) {
            return $price;
        }
        if (!$context) {
            $context = Context::get_context();
        }
        if (!$tb_currency) {
            $tb_currency = $context->currency;
        }
        if (is_int($tb_currency)) {
            $tb_currency = Currency::get_currency_instance($tb_currency);
        } elseif (is_array($tb_currency)) {
            $currency_array = $tb_currency;
            $tb_currency = new Currency();
            $tb_currency->hydrate($currency_array);
        }
        if (!is_object($tb_currency)) {
            // this should never happen
            return '';
        }
        // if currency has associated formatter, use it. Formatter must return string
        $formatter = $tb_currency->get_formatter();
        if ($formatter && is_callable($formatter)) {
            $result = $formatter($price, $tb_currency, $context->language);
            if (is_string($result)) {
                return $result;
            }
        }
        // fallback to default currency formatting
        $c_char = $tb_currency->sign;
        $c_format = $tb_currency->format;
        $c_decimals = $tb_currency->get_display_precision();
        $c_blank = $tb_currency->blank;
        $blank = $c_blank ? ' ' : '';
        $ret = 0;
        if ($is_negative = $price < 0) {
            $price *= -1;
        }
        $price = Tools::ps_round($price, $c_decimals);
        /*
         * If the language is RTL and the selected currency format contains spaces as thousands separator
         * then the number will be printed in reverse since the space is interpreted as separating words.
         * To avoid this we replace the currency format containing a space with the one containing a comma (,) as thousand
         * separator when the language is RTL.
         */
        if ($c_format == 2 && $context->language->is_rtl == 1) {
            $c_format = 4;
        }
        switch ($c_format) {
            /* X 0,000.00 */
            case 1:
                $ret = $c_char . $blank . number_format($price, $c_decimals, '.', ',');
                break;
            /* 0 000,00 X*/
            case 2:
                $ret = number_format($price, $c_decimals, ',', ' ') . $blank . $c_char;
                break;
            /* X 0.000,00 */
            case 3:
                $ret = $c_char . $blank . number_format($price, $c_decimals, ',', '.');
                break;
            /* 0,000.00 X */
            case 4:
                $ret = number_format($price, $c_decimals, '.', ',') . $blank . $c_char;
                break;
            /* X 0'000.00  Added for the switzerland currency */
            case 5:
                $ret = number_format($price, $c_decimals, '.', "'") . $blank . $c_char;
                break;
            /* 0.000,00 X */
            case 6:
                $ret = number_format($price, $c_decimals, ',', '.') . $blank . $c_char;
                break;
        }
        if ($is_negative) {
            $ret = '-' . $ret;
        }
        if ($no_utf8) {
            return str_replace('€', chr(128), $ret);
        }
        return $ret;
    }
    /**
     * returns the rounded value of $value to specified precision, according to your configuration;
     *
     * @param float $value
     * @param int $precision
     *
     * @return float
     */
    public static function ps_round($value, $precision = 0, $round_mode = null)
    {
        if (is_null($value)) {
            return 0.0;
        }
        if ($round_mode === null) {
            if (Tools::$round_mode == null) {
                try {
                    Tools::$round_mode = (int) Configuration::get('PS_PRICE_ROUND_MODE');
                } catch (Presta_Shop_Exception) {
                    Tools::$round_mode = PS_ROUND_HALF_UP;
                }
            }
            $round_mode = Tools::$round_mode;
        }
        return match ($round_mode) {
            PS_ROUND_UP => Tools::ceilf($value, $precision),
            PS_ROUND_DOWN => Tools::floorf($value, $precision),
            PS_ROUND_HALF_DOWN, PS_ROUND_HALF_EVEN, PS_ROUND_HALF_ODD => Tools::math_round($value, $precision, $round_mode),
            default => Tools::math_round($value, $precision, PS_ROUND_HALF_UP),
        };
    }
    /**
     * returns the rounded value up of $value to specified precision
     *
     * @param float $value
     * @param int $precision
     *
     * @return float
     */
    public static function ceilf($value, $precision = 0)
    {
        $precision_factor = $precision == 0 ? 1 : 10 ** $precision;
        $tmp = $value * $precision_factor;
        $tmp2 = (string) $tmp;
        // If the current value has already the desired precision
        if (!str_contains($tmp2, '.')) {
            return $value;
        }
        if ($tmp2[strlen($tmp2) - 1] == 0) {
            return $value;
        }
        return ceil($tmp) / $precision_factor;
    }
    /**
     * returns the rounded value down of $value to specified precision
     *
     * @param float $value
     * @param int $precision
     *
     * @return float
     */
    public static function floorf($value, $precision = 0)
    {
        $precision_factor = $precision == 0 ? 1 : 10 ** $precision;
        $tmp = $value * $precision_factor;
        $tmp2 = (string) $tmp;
        // If the current value has already the desired precision
        if (!str_contains($tmp2, '.')) {
            return $value;
        }
        if ($tmp2[strlen($tmp2) - 1] == 0) {
            return $value;
        }
        return floor($tmp) / $precision_factor;
    }
    /**
     * @param float $value
     * @param int $places
     * @param int $mode
     */
    public static function math_round($value, $places, $mode = PS_ROUND_HALF_UP): float
    {
        return round($value, $places, $mode - 1);
    }
    /**
     * @param float $value
     * @param int $mode
     *
     *
     * @deprecated 1.1.0
     */
    public static function round_helper($value, $mode): float
    {
        static::display_as_deprecated('This was needed for PHP <= 5.3, only.');
        if ($value >= 0.0) {
            $tmp_value = floor($value + 0.5);
            if ($mode == PS_ROUND_HALF_DOWN && $value == -0.5 + $tmp_value || $mode == PS_ROUND_HALF_EVEN && $value == 0.5 + 2 * floor($tmp_value / 2.0) || $mode == PS_ROUND_HALF_ODD && $value == 0.5 + 2 * floor($tmp_value / 2.0) - 1.0) {
                $tmp_value = $tmp_value - 1.0;
            }
        } else {
            $tmp_value = ceil($value - 0.5);
            if ($mode == PS_ROUND_HALF_DOWN && $value == 0.5 + $tmp_value || $mode == PS_ROUND_HALF_EVEN && $value == -0.5 + 2 * ceil($tmp_value / 2.0) || $mode == PS_ROUND_HALF_ODD && $value == -0.5 + 2 * ceil($tmp_value / 2.0) + 1.0) {
                $tmp_value = $tmp_value + 1.0;
            }
        }
        return $tmp_value;
    }
    /**
     * Convert a price to or from the default currency.
     *
     * @param float $price Price.
     * @param Currency|array|int|null $currency Currency object or describing array to convert this price to/from
     * @param bool $toCurrency Conversion direction.
     * @param Context|null $context Context. Defaults to the global context.
     *
     * @return float Price, rounded to _TB_PRICE_DATABASE_PRECISION_.
     *
     * @throws PrestaShopException
     */
    public static function convert_price($price, $currency = null, $to_currency = true, ?Context $context = null): float
    {
        static $default_currency = null;
        if ($default_currency === null) {
            $default_currency = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        }
        if (!$context) {
            $context = Context::get_context();
        }
        if ($currency === null) {
            $currency = $context->currency;
        } elseif (is_numeric($currency)) {
            $currency = Currency::get_currency_instance($currency);
        }
        $currency_id = is_array($currency) ? $currency['id_currency'] : $currency->id;
        $currency_rate = is_array($currency) ? $currency['conversion_rate'] : $currency->conversion_rate;
        if ($currency_id != $default_currency) {
            if ($to_currency) {
                $price *= $currency_rate;
            } else {
                $price /= $currency_rate;
            }
        }
        return round($price, _TB_PRICE_DATABASE_PRECISION_);
    }
    /**
     * Implement array_replace for PHP <= 5.2
     *
     * @return array|mixed|null
     *
     * @deprecated 1.0.0 Use array_replace instead
     */
    public static function array_replace(): mixed
    {
        Tools::display_as_deprecated('Use PHP\'s array_replace() instead');
        return call_user_func_array(array_replace(...), func_get_args());
    }
    /**
     * Convert amount from a currency to an other currency automatically.
     *
     * @param float $amount
     * @param Currency|null $currencyFrom if null we used the default currency
     * @param Currency|null $currencyTo if null we used the default currency
     * @param bool $round
     * @return float Converted value, rounded to _TB_PRICE_DATABASE_PRECISION_.
     *
     * @throws PrestaShopException
     */
    public static function convert_price_full($amount, ?Currency $currency_from = null, ?Currency $currency_to = null, $round = true): float
    {
        if ($round !== true) {
            static::display_parameter_as_deprecated('round');
        }
        $default_currency_id = (int) Configuration::get('PS_CURRENCY_DEFAULT');
        if ($currency_from === null) {
            $currency_from = Currency::get_currency_instance($default_currency_id);
        }
        if ($currency_to === null) {
            $currency_to = Currency::get_currency_instance($default_currency_id);
        }
        $currency_from_id = (int) $currency_from->id;
        $currency_to_id = (int) $currency_to->id;
        if ($currency_from_id !== $currency_to_id) {
            if ($currency_from_id === $default_currency_id) {
                $amount *= $currency_to->conversion_rate;
            } else {
                $conversion_rate = $currency_from->conversion_rate == 0 ? 1 : $currency_from->conversion_rate;
                // Convert amount to default currency (using the old currency rate)
                $amount = $amount / $conversion_rate;
                // Convert to new currency
                $amount *= $currency_to->conversion_rate;
            }
        }
        return round($amount, _TB_PRICE_DATABASE_PRECISION_);
    }
    /**
     * Display date regarding to language preferences
     *
     * @param array $params Date, format...
     * @param Smarty_Internal_Template $smarty Smarty object for language preferences
     *
     * @return string Date
     *
     * @throws PrestaShopException
     */
    public static function date_format(array $params, $smarty)
    {
        return Tools::display_date($params['date'], null, $params['full'] ?? false);
    }
    /**
     * Display date regarding to language preferences
     *
     * @param string $date Date to display format UNIX
     * @param int $idLang Language id DEPRECATED
     * @param bool $full With time or not (optional)
     * @param string $separator DEPRECATED
     *
     * @return string Date
     *
     * @throws PrestaShopException
     */
    public static function display_date($date, $id_lang = null, $full = false, $separator = null)
    {
        if ($id_lang !== null) {
            Tools::display_parameter_as_deprecated('idLang');
        }
        if ($separator !== null) {
            Tools::display_parameter_as_deprecated('separator');
        }
        if (!$date || !$time = strtotime($date)) {
            return $date;
        }
        if ($date == '0000-00-00 00:00:00' || $date == '0000-00-00') {
            return '';
        }
        if (!Validate::is_date($date) || !Validate::is_bool($full)) {
            throw new Presta_Shop_Exception('Invalid date');
        }
        $context = Context::get_context();
        $date_format = $full ? $context->language->date_format_full : $context->language->date_format_lite;
        return date($date_format, $time);
    }
    /**
     * Display a warning message indicating that the parameter is deprecated
     *
     *
     */
    public static function display_parameter_as_deprecated(string $parameter): void
    {
        $backtrace = debug_backtrace();
        $curr = current($backtrace);
        $callee = next($backtrace);
        $class = $callee['class'] ?? '';
        $file = Error_Utils::get_relative_file($curr['file']);
        $call_file = Error_Utils::get_relative_file($callee['file']);
        if ($class) {
            $prefix = 'method ' . $class . '::';
        } else {
            $prefix = 'function ';
        }
        $error = $file . ': Parameter ' . $parameter . ' in ' . $prefix . $callee['function'] . '() is deprecated. Called from ' . $call_file . ':' . $callee['line'];
        trigger_error($error, E_USER_DEPRECATED);
    }
    /**
     * @param string $name
     * @return string
     */
    protected static function normalize_class_name($name): ?string
    {
        return preg_replace('/core$/', '', strtolower($name));
    }
    /**
     * @param string[] $ignoreClassNames
     */
    public static function get_call_point($ignore_class_names = []): array
    {
        $ignore_class_names = array_unique(array_map(['Tools', 'normalizeClassName'], $ignore_class_names));
        $backtrace = debug_backtrace();
        $prev = next($backtrace);
        while ($trace = next($backtrace)) {
            $class = $trace['class'] ?? '';
            if (!in_array(static::normalize_class_name($class), $ignore_class_names)) {
                $func = $trace['function'];
                $line = (int) $prev['line'];
                $file = Error_Utils::get_relative_file($prev['file']);
                if ($class) {
                    $description = $class . '::' . $func . '() in file \'' . $file . '\' at line ' . $line;
                } else {
                    $description = 'Function ' . $func . '() in file \'' . $file . '\' at line ' . $line;
                }
                return ['class' => $class, 'function' => $func, 'line' => $line, 'file' => $file, 'description' => $description];
            }
            $prev = $trace;
        }
        return ['class' => 'unknown', 'function' => 'unknown', 'line' => 0, 'file' => 'unknown', 'description' => 'unknown'];
    }
    /**
     * @param string $string
     */
    public static function htmlentities_decode_utf8($string): string
    {
        if (is_array($string)) {
            $string = array_map(['Tools', 'htmlentitiesDecodeUTF8'], $string);
            return (string) array_shift($string);
        }
        return html_entity_decode((string) $string, ENT_QUOTES, 'utf-8');
    }
    public static function safe_post_vars(): void
    {
        if (!is_array($_POST)) {
            $_POST = [];
        } else {
            $_POST = array_map(['Tools', 'htmlentitiesUTF8'], $_POST);
        }
    }
    /**
     * Delete directory and subdirectories
     *
     * @param string $dirname Directory name
     * @param bool $deleteSelf
     */
    public static function delete_directory($dirname, $delete_self = true): bool
    {
        $dirname = rtrim($dirname, '/') . '/';
        if (file_exists($dirname)) {
            if ($files = scandir($dirname)) {
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..' && $file != '.svn') {
                        if (is_dir($dirname . $file)) {
                            Tools::delete_directory($dirname . $file, true);
                        } elseif (file_exists($dirname . $file)) {
                            @chmod($dirname . $file, 0777);
                            // NT ?
                            unlink($dirname . $file);
                        }
                    }
                }
                if ($delete_self && file_exists($dirname)) {
                    if (!rmdir($dirname)) {
                        @chmod($dirname, 0777);
                        // NT ?
                        return false;
                    }
                }
                return true;
            }
        }
        return false;
    }
    /**
     * Clear XML cache folder
     *
     * @throws PrestaShopException
     */
    public static function clear_xml_cache(): void
    {
        $themes = [];
        foreach (Theme::get_themes() as $theme) {
            /** @var Theme $theme */
            $themes[] = $theme->directory;
        }
        foreach (scandir(_PS_ROOT_DIR_ . '/config/xml') as $file) {
            $path_info = pathinfo($file, PATHINFO_EXTENSION);
            if ($path_info == 'xml' && $file != 'default.xml' && !in_array(basename($file, '.' . $path_info), $themes)) {
                static::delete_file(_PS_ROOT_DIR_ . '/config/xml/' . $file);
            }
        }
    }
    /**
     * Clears opcache, if enabled
     */
    public static function clear_op_cache(): void
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
    /**
     * Delete file
     *
     * @param string $file File path
     * @param array $excludeFiles Excluded files
     */
    public static function delete_file($file, $exclude_files = []): void
    {
        if (isset($exclude_files) && !is_array($exclude_files)) {
            $exclude_files = [$exclude_files];
        }
        if (file_exists($file) && is_file($file) && !in_array(basename($file), $exclude_files)) {
            @chmod($file, 0777);
            // NT ?
            unlink($file);
        }
    }
    /**
     * Display a var dump in firebug console
     *
     * @param object $object Object to display
     *
     * @param string $type
     */
    public static function fd($object, $type = 'log'): void
    {
        $types = ['log', 'debug', 'info', 'warn', 'error', 'assert'];
        if (!in_array($type, $types)) {
            $type = 'log';
        }
        echo '
			<script type="text/javascript">
				console.' . $type . '(' . json_encode($object) . ');
			</script>
		';
    }
    /**
     * ALIAS OF dieObject() - Display an error with detailed object
     *
     * @param mixed $object Object to display
     *
     * @return mixed
     */
    public static function d($object, $kill = true)
    {
        return Tools::die_object($object, $kill);
    }
    /**
     * Display an error with detailed object
     *
     * @param mixed $object
     * @param bool $kill
     *
     * @return mixed $object if $kill = false;
     */
    public static function die_object($object, $kill = true)
    {
        echo '<xmp style="text-align: left;">';
        print_r($object);
        echo '</xmp><br />';
        if ($kill) {
            die('END');
        }
        return $object;
    }
    /**
     * @param int $start
     * @param int|null $limit
     */
    public static function debug_backtrace($start = 0, $limit = null): void
    {
        $backtrace = debug_backtrace();
        array_shift($backtrace);
        for ($i = 0; $i < $start; ++$i) {
            array_shift($backtrace);
        }
        echo '
		<div style="margin:10px;padding:10px;border:1px solid #666666">
			<ul>';
        $i = 0;
        foreach ($backtrace as $trace) {
            if ((int) $limit && ++$i > $limit) {
                break;
            }
            $relative_file = isset($trace['file']) ? 'in /' . ltrim(str_replace([_PS_ROOT_DIR_, '\\'], ['', '/'], $trace['file']), '/') : '';
            $current_line = isset($trace['line']) ? ':' . $trace['line'] : '';
            echo '<li>
				<b>' . ($trace['class'] ?? '') . ($trace['type'] ?? '') . $trace['function'] . '</b>
				' . $relative_file . $current_line . '
			</li>';
        }
        echo '</ul>
		</div>';
    }
    /**
     * ALIAS OF dieObject() - Display an error with detailed object but don't stop the execution
     *
     * @param object $object Object to display
     */
    public static function p($object)
    {
        return Tools::die_object($object, false);
    }
    /**
     * Prints object information into error log
     *
     * @see error_log()
     *
     * @param mixed $object
     * @param int|null $messageType
     * @param string|null $destination
     * @param string|null $extraHeaders
     */
    public static function error_log($object, $message_type = null, $destination = null, $extra_headers = null): bool
    {
        return error_log(print_r($object, true), $message_type, $destination, $extra_headers);
    }
    /**
     * @param int $idLang
     * @param string $pageName
     * @param string $title
     *
     * @return array
     *
     * @throws PrestaShopException
     *
     * @deprecated 1.0.0
     */
    public static function get_meta_tags($id_lang, $page_name, $title = '')
    {
        Tools::display_as_deprecated();
        return Meta::get_meta_tags($id_lang, $page_name, $title);
    }
    /**
     * Display a warning message indicating that the method is deprecated
     *
     * @param string|null $message
     */
    public static function display_as_deprecated($message = null): void
    {
        $backtrace = debug_backtrace();
        $curr = current($backtrace);
        $callee = next($backtrace);
        $class = $callee['class'] ?? '';
        $file = Error_Utils::get_relative_file($curr['file']);
        $call_file = Error_Utils::get_relative_file($callee['file']);
        if ($class) {
            $prefix = 'Method ' . $class . '::';
        } else {
            $prefix = 'Function ';
        }
        $error = $file . ': ' . $prefix . $callee['function'] . '() is deprecated. Called from ' . $call_file . ':' . $callee['line'];
        if ($message) {
            $error .= '. Reason: ' . $message;
        }
        trigger_error($error, E_USER_DEPRECATED);
    }
    /**
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public static function get_home_meta_tags($id_lang, $page_name)
    {
        Tools::display_as_deprecated();
        return Meta::get_home_metas($id_lang, $page_name);
    }
    /**
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    public static function complete_meta_tags($meta_tags, $default_value, ?Context $context = null)
    {
        Tools::display_as_deprecated();
        return Meta::complete_meta_tags($meta_tags, $default_value, $context);
    }
    /**
     * Hash password with native `password_hash`
     *
     * @param string $password
     */
    public static function hash($password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
    /**
     * Encrypt data string
     *
     * @param string $data String to encrypt
     */
    public static function encrypt_iv(string $data): string
    {
        return md5(_COOKIE_IV_ . $data);
    }
    /**
     * Get token to prevent CSRF
     *
     * @param string|true $page token to encrypt
     *
     * @return string
     */
    public static function get_token($page = true, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        if ($page === true) {
            return Tools::encrypt($context->customer->id . $context->customer->passwd . $_SERVER['SCRIPT_NAME']);
        }
        return Tools::encrypt($context->customer->id . $context->customer->passwd . $page);
    }
    /**
     * Encrypt password
     *
     * @param string $passwd String to encrypt
     */
    public static function encrypt(string $passwd): string
    {
        return md5(_COOKIE_KEY_ . $passwd);
    }
    /**
     * Returns HMAC-SHA256 signature of $data
     *
     * @param string $data
     */
    public static function signature($data): string
    {
        return hash_hmac('sha256', (string) $data, _COOKIE_KEY_);
    }
    /**
     * @return bool|string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_admin_token_lite(string $tab, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        return Tools::get_admin_token($tab . (int) Tab::get_id_from_class_name($tab) . (int) $context->employee->id);
    }
    /**
     * Tokenize a string
     *
     * @param string $string string to encript
     *
     * @return bool|string
     */
    public static function get_admin_token($string)
    {
        return !empty($string) ? Tools::encrypt($string) : false;
    }
    /**
     * @param Smarty_Internal_Template $smarty
     *
     * @return bool|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_admin_token_lite_smarty(array $params, $smarty)
    {
        $context = Context::get_context();
        return Tools::get_admin_token($params['tab'] . (int) Tab::get_id_from_class_name($params['tab']) . (int) $context->employee->id);
    }
    /**
     * Get a valid image URL to use from BackOffice
     *
     * @param string|null $image Image name
     * @param bool $entities
     * @return string
     * @throws PrestaShopException
     */
    public static function get_admin_image_url($image = null, $entities = false)
    {
        return Tools::get_admin_url(basename(_PS_IMG_DIR_) . '/' . $image, $entities);
    }
    /**
     * Get a valid URL to use from BackOffice
     *
     * @param string|null $url An URL to use in BackOffice
     * @param bool $entities
     * @throws PrestaShopException
     */
    public static function get_admin_url($url = null, $entities = false): string
    {
        $link = Tools::get_http_host(true) . __PS_BASE_URI__;
        if (isset($url)) {
            $link .= $entities ? Tools::htmlentities_utf8($url) : $url;
        }
        return $link;
    }
    /**
     * getHttpHost return the <b>current</b> host used, with the protocol (http or https) if $http is true
     * This function should not be used to choose http or https domain name.
     * Use Tools::getShopDomain() or Tools::getShopDomainSsl instead
     *
     * @param bool $http
     * @param bool $entities
     *
     * @param bool $ignore_port
     *
     * @return string host
     *
     * @throws PrestaShopException
     */
    public static function get_http_host($http = false, $entities = false, $ignore_port = false)
    {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'];
        if ($ignore_port && $pos = strpos((string) $host, ':')) {
            $host = substr((string) $host, 0, $pos);
        }
        if ($entities) {
            $host = htmlspecialchars((string) $host, ENT_COMPAT, 'UTF-8');
        }
        if ($http) {
            return (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . $host;
        }
        return $host;
    }
    /**
     * @param array|string $string
     * @param int $type
     */
    public static function htmlentities_utf8($string, $type = ENT_QUOTES): array|string
    {
        if (is_array($string)) {
            return array_map(['Tools', 'htmlentitiesUTF8'], $string);
        }
        return htmlentities((string) $string, $type, 'utf-8');
    }
    /**
     * @param int $idCategory
     * @param string $end
     * @param string $typeCat
     *
     * @throws PrestaShopException
     */
    public static function get_full_path($id_category, $end, $type_cat = 'products', ?Context $context = null): string
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $id_category = (int) $id_category;
        $pipe = Configuration::get('PS_NAVIGATION_PIPE') ?: '>';
        $default_category = 1;
        if ($type_cat === 'products') {
            $default_category = $context->shop->get_category();
            $category = new Category($id_category, $context->language->id);
        } elseif ($type_cat === 'CMS') {
            $category = new Cms_Category($id_category, $context->language->id);
        }
        if (!Validate::is_loaded_object($category)) {
            $id_category = $default_category;
        }
        if ($id_category == $default_category) {
            return htmlentities($end, ENT_NOQUOTES, 'UTF-8');
        }
        return Tools::get_path($id_category, $category->name, true, $type_cat) . '<span class="navigation-pipe">' . $pipe . '</span> <span class="navigation_product">' . htmlentities($end, ENT_NOQUOTES, 'UTF-8') . '</span>';
    }
    /**
     * Get the user's journey
     *
     * @param int $idCategory
     * @param bool $linkOnTheItem
     * @param string $categoryType
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_path($id_category, string $path = '', $link_on_the_item = false, $category_type = 'products', ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $id_category = (int) $id_category;
        if ($id_category == 1) {
            return '<span class="navigation_end">' . $path . '</span>';
        }
        $pipe = Configuration::get('PS_NAVIGATION_PIPE');
        if (empty($pipe)) {
            $pipe = '>';
        }
        $full_path = '';
        if ($category_type === 'products') {
            $interval = Category::get_interval($id_category);
            $id_root_category = $context->shop->get_category();
            $interval_root = Category::get_interval($id_root_category);
            if ($interval) {
                $sql = 'SELECT c.id_category, cl.name, cl.link_rewrite
						FROM ' . _DB_PREFIX_ . 'category c
						LEFT JOIN ' . _DB_PREFIX_ . 'category_lang cl ON (cl.id_category = c.id_category' . Shop::add_sql_restriction_on_lang('cl') . ')
						' . Shop::add_sql_association('category', 'c') . '
						WHERE c.nleft <= ' . $interval['nleft'] . '
							AND c.nright >= ' . $interval['nright'] . '
							AND c.nleft >= ' . $interval_root['nleft'] . '
							AND c.nright <= ' . $interval_root['nright'] . '
							AND cl.id_lang = ' . (int) $context->language->id . '
							AND category_shop.active = 1
							AND c.level_depth > ' . (int) $interval_root['level_depth'] . '
						ORDER BY c.level_depth ASC';
                $categories = Db::read_only()->get_array($sql);
                $n = 1;
                $n_categories = count($categories);
                foreach ($categories as $category) {
                    $full_path .= ($n < $n_categories || $link_on_the_item ? '<a href="' . Tools::safe_output($context->link->get_category_link((int) $category['id_category'], $category['link_rewrite'])) . '" title="' . htmlentities((string) $category['name'], ENT_NOQUOTES, 'UTF-8') . '" data-gg="">' : '') . htmlentities((string) $category['name'], ENT_NOQUOTES, 'UTF-8') . ($n < $n_categories || $link_on_the_item ? '</a>' : '') . ($n++ != $n_categories || !empty($path) ? '<span class="navigation-pipe">' . $pipe . '</span>' : '');
                }
                return $full_path . $path;
            }
            return $path;
        }
        if ($category_type === 'CMS') {
            $category = new Cms_Category($id_category, $context->language->id);
            if (!Validate::is_loaded_object($category)) {
                throw new Presta_Shop_Exception(sprintf(Tools::display_error('CMSCategory [%s] not found'), (int) $id_category));
            }
            $category_link = $context->link->get_cms_category_link($category);
            if ($path != $category->name) {
                $full_path .= '<a href="' . Tools::safe_output($category_link) . '" data-gg="">' . htmlentities($category->name, ENT_NOQUOTES, 'UTF-8') . '</a><span class="navigation-pipe">' . $pipe . '</span>' . $path;
            } else {
                $full_path = ($link_on_the_item ? '<a href="' . Tools::safe_output($category_link) . '" data-gg="">' : '') . htmlentities($path, ENT_NOQUOTES, 'UTF-8') . ($link_on_the_item ? '</a>' : '');
            }
            return Tools::get_path($category->id_parent, $full_path, $link_on_the_item, $category_type);
        }
        trigger_error('Method Tools::getPath called with invalid parameter $categoryType = \'' . $category_type . '\'', E_USER_WARNING);
        return '';
    }
    /**
     * Sanitize a string
     *
     * @param string $string String to sanitize
     * @param bool $html String contains HTML or not (optional)
     *
     * @return string Sanitized string
     */
    public static function safe_output($string, $html = false)
    {
        if (!$html) {
            $string = strip_tags((string) $string);
        }
        return @Tools::htmlentities_utf8($string, ENT_QUOTES);
    }
    /**
     * Display an error according to an error code
     *
     * @param string $string Error message
     * @param bool $htmlentities By default at true for parsing error message with htmlentities
     *
     * @return string
     */
    public static function display_error($string = 'Fatal error', $htmlentities = true, ?Context $context = null)
    {
        global $_ERRORS;
        if (is_null($context)) {
            $context = Context::get_context();
        }
        $iso_code = static::resolve_error_language($context);
        $error_lang_file = _PS_TRANSLATIONS_DIR_ . $iso_code . '/errors.php';
        if (file_exists($error_lang_file)) {
            @include_once $error_lang_file;
        }
        $key = md5(str_replace('\'', '\\\'', $string));
        if (isset($_ERRORS) && is_array($_ERRORS) && array_key_exists($key, $_ERRORS) && $_ERRORS[$key] !== '') {
            $string = $_ERRORS[$key];
        }
        return $htmlentities ? Tools::htmlentities_utf8(stripslashes((string) $string)) : $string;
    }
    /**
     * Return the friendly url from the provided string
     *
     * @param string $str
     * @param bool $utf8Decode (deprecated)
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function link_rewrite($str, $utf8Decode = null)
    {
        if ($utf8Decode !== null) {
            Tools::display_parameter_as_deprecated('utf8Decode');
        }
        return Tools::str2url($str);
    }
    /**
     * Return a friendly url made from the provided string
     * If the mbstring library is available, the output is the same as the js function of the same name
     *
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function str2url(string $str)
    {
        static $allow_accented_chars = null;
        if ($allow_accented_chars === null) {
            $allow_accented_chars = (bool) Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL');
        }
        $cache_key = 'Tools::str2url_' . $str;
        if (Cache::is_stored($cache_key)) {
            return Cache::retrieve($cache_key);
        }
        $link_rewrite = static::generate_link_rewrite($str, $allow_accented_chars);
        Cache::store($cache_key, $link_rewrite);
        return $link_rewrite;
    }
    /**
     * Return a friendly url made from the provided string
     *
     * @param string $str
     * @return string
     */
    public static function generate_link_rewrite($str, $allow_accented_chars): string|array
    {
        if (!is_string($str)) {
            return '';
        }
        $return_str = trim($str);
        if ($return_str === '') {
            return '';
        }
        $return_str = mb_strtolower($return_str, 'utf-8');
        // Remove all non-whitelist chars.
        if ($allow_accented_chars) {
            $return_str = preg_replace('/[^a-zA-Z0-9\s\':\/\[\]\-\p{L}]/u', '', $return_str);
        } else {
            $return_str = Tools::replace_accented_chars($return_str);
            $return_str = preg_replace('/[^a-zA-Z0-9\s\'\:\/\[\]\-]/', '', $return_str);
        }
        $return_str = preg_replace('/[\s\'\:\/\[\]\-]+/', ' ', $return_str);
        return str_replace([' ', '/'], '-', $return_str);
    }
    /**
     * Replace all accented chars by their equivalent non accented chars.
     *
     * @param string $str
     *
     * @return string
     */
    public static function replace_accented_chars($str): ?string
    {
        /* One source among others:
               http://www.tachyonsoft.com/uc0000.htm
               http://www.tachyonsoft.com/uc0001.htm
               http://www.tachyonsoft.com/uc0004.htm
           */
        $patterns = [
            /* Lowercase */
            /* a  */
            '/[\x{00E0}\x{00E1}\x{00E2}\x{00E3}\x{00E4}\x{00E5}\x{0101}\x{0103}\x{0105}\x{0430}\x{00C0}-\x{00C3}\x{1EA0}-\x{1EB7}]/u',
            /* b  */
            '/[\x{0431}]/u',
            /* c  */
            '/[\x{00E7}\x{0107}\x{0109}\x{010D}\x{0446}]/u',
            /* d  */
            '/[\x{010F}\x{0111}\x{0434}\x{0110}\x{00F0}]/u',
            /* e  */
            '/[\x{00E8}\x{00E9}\x{00EA}\x{00EB}\x{0113}\x{0115}\x{0117}\x{0119}\x{011B}\x{0435}\x{044D}\x{00C8}-\x{00CA}\x{1EB8}-\x{1EC7}]/u',
            /* f  */
            '/[\x{0444}]/u',
            /* g  */
            '/[\x{011F}\x{0121}\x{0123}\x{0433}\x{0491}]/u',
            /* h  */
            '/[\x{0125}\x{0127}]/u',
            /* i  */
            '/[\x{00EC}\x{00ED}\x{00EE}\x{00EF}\x{0129}\x{012B}\x{012D}\x{012F}\x{0131}\x{0438}\x{0456}\x{00CC}\x{00CD}\x{1EC8}-\x{1ECB}\x{0128}]/u',
            /* j  */
            '/[\x{0135}\x{0439}]/u',
            /* k  */
            '/[\x{0137}\x{0138}\x{043A}]/u',
            /* l  */
            '/[\x{013A}\x{013C}\x{013E}\x{0140}\x{0142}\x{043B}]/u',
            /* m  */
            '/[\x{043C}]/u',
            /* n  */
            '/[\x{00F1}\x{0144}\x{0146}\x{0148}\x{0149}\x{014B}\x{043D}]/u',
            /* o  */
            '/[\x{00F2}\x{00F3}\x{00F4}\x{00F5}\x{00F6}\x{00F8}\x{014D}\x{014F}\x{0151}\x{043E}\x{00D2}-\x{00D5}\x{01A0}\x{01A1}\x{1ECC}-\x{1EE3}]/u',
            /* p  */
            '/[\x{043F}]/u',
            /* r  */
            '/[\x{0155}\x{0157}\x{0159}\x{0440}]/u',
            /* s  */
            '/[\x{015B}\x{015D}\x{015F}\x{0161}\x{0441}]/u',
            /* ss */
            '/[\x{00DF}]/u',
            /* t  */
            '/[\x{0163}\x{0165}\x{0167}\x{0442}]/u',
            /* u  */
            '/[\x{00F9}\x{00FA}\x{00FB}\x{00FC}\x{0169}\x{016B}\x{016D}\x{016F}\x{0171}\x{0173}\x{0443}\x{00D9}-\x{00DA}\x{0168}\x{01AF}\x{01B0}\x{1EE4}-\x{1EF1}]/u',
            /* v  */
            '/[\x{0432}]/u',
            /* w  */
            '/[\x{0175}]/u',
            /* y  */
            '/[\x{00FF}\x{0177}\x{00FD}\x{044B}\x{1EF2}-\x{1EF9}\x{00DD}]/u',
            /* z  */
            '/[\x{017A}\x{017C}\x{017E}\x{0437}]/u',
            /* ae */
            '/[\x{00E6}]/u',
            /* ch */
            '/[\x{0447}]/u',
            /* kh */
            '/[\x{0445}]/u',
            /* oe */
            '/[\x{0153}]/u',
            /* sh */
            '/[\x{0448}]/u',
            /* shh*/
            '/[\x{0449}]/u',
            /* ya */
            '/[\x{044F}]/u',
            /* ye */
            '/[\x{0454}]/u',
            /* yi */
            '/[\x{0457}]/u',
            /* yo */
            '/[\x{0451}]/u',
            /* yu */
            '/[\x{044E}]/u',
            /* zh */
            '/[\x{0436}]/u',
            /* Uppercase */
            /* A  */
            '/[\x{0100}\x{0102}\x{0104}\x{00C0}\x{00C1}\x{00C2}\x{00C3}\x{00C4}\x{00C5}\x{0410}]/u',
            /* B  */
            '/[\x{0411}]/u',
            /* C  */
            '/[\x{00C7}\x{0106}\x{0108}\x{010A}\x{010C}\x{0426}]/u',
            /* D  */
            '/[\x{010E}\x{0110}\x{0414}\x{00D0}]/u',
            /* E  */
            '/[\x{00C8}\x{00C9}\x{00CA}\x{00CB}\x{0112}\x{0114}\x{0116}\x{0118}\x{011A}\x{0415}\x{042D}]/u',
            /* F  */
            '/[\x{0424}]/u',
            /* G  */
            '/[\x{011C}\x{011E}\x{0120}\x{0122}\x{0413}\x{0490}]/u',
            /* H  */
            '/[\x{0124}\x{0126}]/u',
            /* I  */
            '/[\x{0128}\x{012A}\x{012C}\x{012E}\x{0130}\x{0418}\x{0406}]/u',
            /* J  */
            '/[\x{0134}\x{0419}]/u',
            /* K  */
            '/[\x{0136}\x{041A}]/u',
            /* L  */
            '/[\x{0139}\x{013B}\x{013D}\x{0139}\x{0141}\x{041B}]/u',
            /* M  */
            '/[\x{041C}]/u',
            /* N  */
            '/[\x{00D1}\x{0143}\x{0145}\x{0147}\x{014A}\x{041D}]/u',
            /* O  */
            '/[\x{00D3}\x{014C}\x{014E}\x{0150}\x{041E}]/u',
            /* P  */
            '/[\x{041F}]/u',
            /* R  */
            '/[\x{0154}\x{0156}\x{0158}\x{0420}]/u',
            /* S  */
            '/[\x{015A}\x{015C}\x{015E}\x{0160}\x{0421}]/u',
            /* T  */
            '/[\x{0162}\x{0164}\x{0166}\x{0422}]/u',
            /* U  */
            '/[\x{00D9}\x{00DA}\x{00DB}\x{00DC}\x{0168}\x{016A}\x{016C}\x{016E}\x{0170}\x{0172}\x{0423}]/u',
            /* V  */
            '/[\x{0412}]/u',
            /* W  */
            '/[\x{0174}]/u',
            /* Y  */
            '/[\x{0176}\x{042B}]/u',
            /* Z  */
            '/[\x{0179}\x{017B}\x{017D}\x{0417}]/u',
            /* AE */
            '/[\x{00C6}]/u',
            /* CH */
            '/[\x{0427}]/u',
            /* KH */
            '/[\x{0425}]/u',
            /* OE */
            '/[\x{0152}]/u',
            /* SH */
            '/[\x{0428}]/u',
            /* SHH*/
            '/[\x{0429}]/u',
            /* YA */
            '/[\x{042F}]/u',
            /* YE */
            '/[\x{0404}]/u',
            /* YI */
            '/[\x{0407}]/u',
            /* YO */
            '/[\x{0401}]/u',
            /* YU */
            '/[\x{042E}]/u',
            /* ZH */
            '/[\x{0416}]/u',
        ];
        // ö to oe
        // å to aa
        // ä to ae
        $replacements = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 'ss', 't', 'u', 'v', 'w', 'y', 'z', 'ae', 'ch', 'kh', 'oe', 'sh', 'shh', 'ya', 'ye', 'yi', 'yo', 'yu', 'zh', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'V', 'W', 'Y', 'Z', 'AE', 'CH', 'KH', 'OE', 'SH', 'SHH', 'YA', 'YE', 'YI', 'YO', 'YU', 'ZH'];
        return preg_replace($patterns, $replacements, $str);
    }
    /**
     * @param string $str
     * @param int $maxLength
     *
     * @return string
     */
    public static function truncate($str, $max_length, string $suffix = '...')
    {
        if (mb_strlen($str) <= $max_length) {
            return $str;
        }
        return mb_substr($str, 0, $max_length - mb_strlen($suffix)) . $suffix;
    }
    /**
     * @param string $str
     * @param string $encoding
     *
     *
     * @deprecated 1.0.4 Use mb_strlen for UTF-8 or strlen if guaranteed ASCII
     */
    public static function strlen($str, $encoding = 'UTF-8'): false|int
    {
        if (is_array($str)) {
            return false;
        }
        return mb_strlen((string) $str, $encoding);
    }
    /**
     * @param string $text
     * @param int $length
     *
     */
    public static function truncate_string($text, $length = 120, array $options = []): string
    {
        $text = (string) $text;
        $ellipsis = (string) ($options['ellipsis'] ?? '...');
        $exact = (bool) ($options['exact'] ?? true);
        $html = (bool) ($options['html'] ?? true);
        if ($html) {
            if (mb_strlen((string) preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            $total_length = mb_strlen(strip_tags($ellipsis));
            $open_tags = [];
            $truncate = '';
            preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag) {
                if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
                    if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
                        array_unshift($open_tags, $tag[2]);
                    } elseif (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $close_tag)) {
                        $pos = array_search($close_tag[1], $open_tags);
                        if ($pos !== false) {
                            array_splice($open_tags, $pos, 1);
                        }
                    }
                }
                $truncate .= $tag[1];
                $content_length = mb_strlen((string) preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
                if ($content_length + $total_length > $length) {
                    $left = $length - $total_length;
                    $entities_length = 0;
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entities_length <= $left) {
                                $left--;
                                $entities_length += mb_strlen($entity[0]);
                            } else {
                                break;
                            }
                        }
                    }
                    $truncate .= mb_substr($tag[3], 0, $left + $entities_length);
                    break;
                } else {
                    $truncate .= $tag[3];
                    $total_length += $content_length;
                }
                if ($total_length >= $length) {
                    break;
                }
            }
        } else {
            if (mb_strlen($text) <= $length) {
                return $text;
            }
            $truncate = mb_substr($text, 0, $length - mb_strlen($ellipsis));
        }
        if (!$exact) {
            $spacepos = mb_strrpos($truncate, ' ');
            if ($html) {
                $truncate_check = mb_substr($truncate, 0, $spacepos);
                $last_open_tag = mb_strrpos($truncate_check, '<');
                $last_close_tag = mb_strrpos($truncate_check, '>');
                if ($last_open_tag > $last_close_tag) {
                    preg_match_all('/<[\w]+[^>]*>/s', $truncate, $last_tag_matches);
                    $last_tag = array_pop($last_tag_matches[0]);
                    $spacepos = mb_strrpos($truncate, (string) $last_tag) + mb_strlen((string) $last_tag);
                }
                $bits = mb_substr($truncate, $spacepos);
                preg_match_all('/<\/([a-z]+)>/', $bits, $dropped_tags, PREG_SET_ORDER);
                if (!empty($dropped_tags)) {
                    if (!empty($open_tags)) {
                        foreach ($dropped_tags as $closing_tag) {
                            if (!in_array($closing_tag[1], $open_tags)) {
                                array_unshift($open_tags, $closing_tag[1]);
                            }
                        }
                    } else {
                        foreach ($dropped_tags as $closing_tag) {
                            $open_tags[] = $closing_tag[1];
                        }
                    }
                }
            }
            $truncate = mb_substr($truncate, 0, $spacepos);
        }
        $truncate .= $ellipsis;
        if ($html) {
            foreach ($open_tags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }
        return $truncate;
    }
    /**
     * @param string $str
     * @param int $start
     * @param int|false $length
     * @param string $encoding
     *
     *
     * @deprecated 1.0.4 Use mb_strlen for UTF-8 or strlen if guaranteed ASCII
     */
    public static function substr($str, $start, $length = false, $encoding = 'utf-8'): false|string
    {
        if (is_array($str)) {
            return false;
        }
        return mb_substr($str, (int) $start, $length === false ? mb_strlen($str) : (int) $length, $encoding);
    }
    /**
     * @param string $str
     * @param string $find
     * @param int $offset
     * @param string $encoding
     *
     *
     * @deprecated 1.0.4 Use mb_strlen for UTF-8 or strlen if guaranteed ASCII
     */
    public static function strrpos($str, $find, $offset = 0, $encoding = 'utf-8'): int|false
    {
        return mb_strrpos($str, $find, $offset, $encoding);
    }
    /**
     * @param string $directory
     */
    public static function normalize_directory($directory): string
    {
        return rtrim($directory, '/\\') . DIRECTORY_SEPARATOR;
    }
    /**
     * Generate years
     */
    public static function date_years(): array
    {
        $tab = [];
        for ($i = date('Y'); $i >= 1900; $i--) {
            $tab[] = $i;
        }
        return $tab;
    }
    public static function date_days(): array
    {
        $tab = [];
        for ($i = 1; $i != 32; $i++) {
            $tab[] = $i;
        }
        return $tab;
    }
    public static function date_months(): array
    {
        $tab = [];
        for ($i = 1; $i != 13; $i++) {
            $tab[$i] = date('F', mktime(0, 0, 0, $i, date('m'), date('Y')));
        }
        return $tab;
    }
    public static function date_from(string $date): string
    {
        $tab = explode(' ', $date);
        if (!isset($tab[1])) {
            $date .= ' ' . Tools::hour_generate(0, 0, 0);
        }
        return $date;
    }
    /**
     * @param int $hours
     * @param int $minutes
     * @param int $seconds
     */
    public static function hour_generate($hours, $minutes, $seconds): string
    {
        return implode(':', [$hours, $minutes, $seconds]);
    }
    public static function date_to(string $date): string
    {
        $tab = explode(' ', $date);
        if (!isset($tab[1])) {
            $date .= ' ' . Tools::hour_generate(23, 59, 59);
        }
        return $date;
    }
    /**
     * Despite its name, this method used to strip slashes only when magic_quotes_gpc
     * was enabled. When this functionality was dropped in php 5.4, this method does
     * nothing anymore.
     *
     * @param string $string
     *
     * @return string
     *
     * @deprecated 1.1.1
     */
    public static function stripslashes($string)
    {
        Tools::display_as_deprecated();
        return $string;
    }
    /**
     * @param string $str
     * @param string $find
     * @param int $offset
     * @param string $encoding
     *
     *
     * @deprecated 1.0.4 Use mb_strlen for UTF-8 or strlen if guaranteed ASCII
     */
    public static function strpos($str, $find, $offset = 0, $encoding = 'UTF-8'): int|false
    {
        return mb_strpos($str, $find, $offset, $encoding);
    }
    /**
     * Convert the first character of each word to uppercase, and all other characters to lowercase.
     *
     * Difference between this function and php ucwords function is that this method also converts
     * other characters to lowercase.
     *
     * Example:
     *
     *     ucwords('heLLo thEre');        // HeLLo ThEre
     *     Tools::ucwords('heLLo thEre'); // Hello There
     *
     * @param string $str
     */
    public static function ucwords($str): string
    {
        $str = (string) $str;
        if (function_exists('mb_convert_case')) {
            return mb_convert_case($str, MB_CASE_TITLE);
        }
        return ucwords(mb_strtolower($str));
    }
    /**
     * @param array $array
     * @param string $order_way
     *
     * @throws PrestaShopException
     */
    public static function orderby_price(&$array, $order_way): void
    {
        foreach ($array as &$row) {
            $product_id = (int) $row['id_product'];
            $product_attribute_id = !empty($row['id_product_attribute']) ? (int) $row['id_product_attribute'] : null;
            $row['price_tmp'] = (float) Product::get_price_static($product_id, true, $product_attribute_id);
        }
        unset($row);
        $asc = mb_strtolower($order_way) !== 'desc';
        uasort($array, fn($a, $b) => static::compare_floats($a, $b, 'price_tmp', $asc));
        foreach ($array as &$row) {
            unset($row['price_tmp']);
        }
    }
    /**
     * @param string $key
     * @param bool $asc
     *
     */
    public static function compare_floats(array $array1, array $array2, $key, $asc = true): int
    {
        $value1 = $array1[$key] ?? 0.0;
        $value2 = $array2[$key] ?? 0.0;
        if ($value1 < $value2) {
            return $asc ? -1 : 1;
        }
        if ($value1 > $value2) {
            return $asc ? 1 : -11;
        }
        return 0;
    }
    /**
     * @param string $from
     * @param string $string
     * @return string
     */
    public static function iconv($from, string $to, $string): string|false
    {
        if (function_exists('iconv')) {
            return iconv($from, $to . '//TRANSLIT', str_replace('¥', '&yen;', str_replace('£', '&pound;', str_replace('€', '&euro;', $string))));
        }
        return html_entity_decode(htmlentities($string, ENT_NOQUOTES, $from), ENT_NOQUOTES, $to);
    }
    /**
     * @param string $field
     */
    public static function is_empty($field): bool
    {
        return $field === '' || $field === null;
    }
    /**
     * file_exists() wrapper with a call to clearstatcache prior
     *
     * @param string $filename File name
     *
     * @return bool Cached result of file_exists($filename)
     */
    public static function file_exists_no_cache($filename): bool
    {
        clearstatcache(true, $filename);
        return file_exists($filename);
    }
    /**
     * @param string $url
     * @param bool $useIncludePath
     * @param resource|null $streamContext
     * @param int $curlTimeout
     *
     * @return string|false
     *
     * @deprecated 1.0.0 Use Guzzle for remote URLs and file_get_contents for local files instead
     */
    public static function file_get_contents($url, $use_include_path = false, $stream_context = null, $curl_timeout = 5): string|bool
    {
        if ($stream_context == null && preg_match('/^https?:\/\//', $url)) {
            $stream_context = @stream_context_create(['http' => ['timeout' => $curl_timeout]]);
        }
        if (is_resource($stream_context)) {
            $opts = stream_context_get_options($stream_context);
        }
        // Remove the Content-Length header -- let cURL/fopen handle it
        if (!empty($opts['http']['header'])) {
            $headers = explode("\r\n", (string) $opts['http']['header']);
            foreach ($headers as $index => $header) {
                if (str_starts_with(strtolower($header), 'content-length')) {
                    unset($headers[$index]);
                }
            }
            $opts['http']['header'] = implode("\r\n", $headers);
            stream_context_set_option($stream_context, ['http' => $opts['http']]);
        }
        if (preg_match('/^(file|php|zlib|ftp|data|glob|phar):\/\//', $url)) {
            return file_get_contents($url, $use_include_path, $stream_context);
        }
        if (!preg_match('/^https?:\/\//', $url)) {
            if (file_exists($url)) {
                return @file_get_contents($url, $use_include_path, $stream_context);
            }
            return false;
        }
        if (function_exists('curl_init')) {
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($curl, CURLOPT_TIMEOUT, $curl_timeout);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            if (!empty($opts['http']['header'])) {
                curl_setopt($curl, CURLOPT_HTTPHEADER, explode("\r\n", (string) $opts['http']['header']));
            }
            if ($stream_context != null) {
                if (isset($opts['http']['method']) && mb_strtolower($opts['http']['method']) == 'post') {
                    curl_setopt($curl, CURLOPT_POST, true);
                    if (isset($opts['http']['content'])) {
                        curl_setopt($curl, CURLOPT_POSTFIELDS, $opts['http']['content']);
                    }
                }
            }
            $content = curl_exec($curl);
            curl_close($curl);
            return $content;
        }
        if (ini_get('allow_url_fopen')) {
            return @file_get_contents($url, $use_include_path, $stream_context);
        }
        return false;
    }
    /**
     * @param string|null $class_name
     *
     * @return SimpleXMLElement|null
     * @throws PrestaShopException
     */
    public static function simplexml_load_file(string $url, $class_name = null)
    {
        $cache_id = 'Tools::simplexml_load_file' . $url;
        if (!Cache::is_stored($cache_id)) {
            $guzzle = new Client(['verify' => Configuration::get_ssl_trust_store(), 'timeout' => 20]);
            try {
                $result = @simplexml_load_string((string) $guzzle->get($url)->get_body(), $class_name);
            } catch (Throwable) {
                return null;
            }
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * @param string $source
     * @param string $destination
     * @param resource|null $streamContext
     * @param string $copyError
     *
     * @throws PrestaShopException
     */
    public static function copy($source, $destination, $stream_context = null, &$copy_error = null): bool
    {
        if ($stream_context) {
            Tools::display_parameter_as_deprecated('streamContext');
        }
        if (!preg_match('/^https?:\/\//', $source)) {
            if (copy($source, $destination)) {
                return true;
            }
            $error = error_get_last();
            if (isset($error['message'])) {
                $copy_error = $error['message'];
            }
            return false;
        }
        $timeout = ini_get('max_execution_time');
        if (!$timeout || $timeout > 600 || $timeout <= 0) {
            $timeout = 600;
        }
        $timeout -= 5;
        // Room for other processing.
        $guzzle = new Client(['verify' => Configuration::get_ssl_trust_store(), 'timeout' => $timeout]);
        try {
            $guzzle->get($source, ['sink' => $destination]);
        } catch (Throwable $e) {
            $copy_error = $e->get_message();
            return false;
        }
        return true;
    }
    /**
     * @throws PrestaShopException
     * @deprecated as of 1.0.0 use Media::minifyHTML()
     */
    public static function minify_html($html_content)
    {
        Tools::display_as_deprecated();
        return Media::minify_html($html_content);
    }
    /**
     * Translates a string with underscores into camel case (e.g. first_name -> firstName)
     *
     * @param string $str
     * @param bool $catapitaliseFirstChar
     *
     * @return string
     */
    public static function to_camel_case($str, $catapitalise_first_char = false): ?string
    {
        $str = mb_strtolower((string) $str);
        if ($catapitalise_first_char) {
            $str = ucfirst($str);
        }
        return preg_replace_callback('/_+([a-z])/', fn($c) => strtoupper((string) $c[1]), $str);
    }
    /**
     * @param string $str
     *
     *
     * @deprecated 1.0.0 use ucfirst instead
     */
    public static function ucfirst($str): string
    {
        return ucfirst((string) $str);
    }
    /**
     * @param string $str
     *
     * @return bool|string
     *
     * @deprecated 1.0.4 Use mb_strlen for UTF-8 or strlen if guaranteed ASCII
     */
    public static function strtoupper($str)
    {
        if (is_array($str)) {
            return false;
        }
        return mb_strtoupper((string) $str, 'utf-8');
    }
    /**
     * Transform a CamelCase string to underscore_case string
     *
     * @param string $string
     */
    public static function to_underscore_case($string): string
    {
        // 'CMSCategories' => 'cms_categories'
        // 'RangePrice' => 'range_price'
        return mb_strtolower(trim((string) preg_replace('/([A-Z][a-z])/', '_$1', $string), '_'));
    }
    /**
     * Returns brightness of a color
     *
     * @param string $hex
     *
     * @return int
     */
    public static function get_brightness($hex): float|int
    {
        $hex = mb_strtolower((string) $hex);
        // special cases for known colors
        if ($hex == 'transparent') {
            return 129;
        }
        $basic_colors = ['black' => '#000000', 'white' => '#ffffff', 'red' => '#ff0000', 'lime' => '#00ff00', 'blue' => '#0000ff', 'yellow' => '#ffff00', 'cyan' => '#00ffff', 'aqua' => '#00ffff', 'magenta' => '#ff00ff', 'fuchsia' => '#ff00ff', 'silver' => '#c0c0c0', 'gray' => '#808080', 'maroon' => '#800000', 'olive' => '#808000', 'green' => '#008000', 'purple' => '#800080', 'teal' => '#008080', 'navy' => '#000080'];
        if (isset($basic_colors[$hex])) {
            $hex = $basic_colors[$hex];
        }
        $hex = str_replace('#', '', $hex);
        if (mb_strlen($hex) == 3) {
            $hex .= $hex;
        }
        if (preg_match('/^[0-9a-f]{6}$/', $hex)) {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            return ($r * 299 + $g * 587 + $b * 114) / 1000;
        }
        // this is neither hex input nor known color, lets return 0
        return 0;
    }
    /**
     * @deprecated as of 1.0.0 use Media::minifyHTMLpregCallback()
     */
    public static function minify_htm_lpreg_callback($preg_matches)
    {
        Tools::display_as_deprecated();
        return Media::minify_htm_lpreg_callback($preg_matches);
    }
    /**
     * @param string $html_content
     *
     * @return string
     * @throws PrestaShopException
     *
     * @deprecated as of 1.0.0 use Media::packJSinHTML()
     */
    public static function pack_j_sin_html($html_content)
    {
        Tools::display_as_deprecated();
        return Media::pack_j_sin_html($html_content);
    }
    /**
     * @param array $preg_matches
     *
     * @return string
     * @throws PrestaShopException
     *
     * @deprecated as of 1.0.0 use Media::packJSinHTMLpregCallback()
     */
    public static function pack_j_sin_htm_lpreg_callback($preg_matches)
    {
        Tools::display_as_deprecated();
        return Media::pack_j_sin_htm_lpreg_callback($preg_matches);
    }
    /**
     * @param string $js_content
     *
     * @return string
     *
     * @throws PrestaShopException
     * @deprecated as of 1.0.0 use Media::packJS()
     */
    public static function pack_js($js_content)
    {
        Tools::display_as_deprecated();
        return Media::pack_js($js_content);
    }
    /**
     * Parse SQL query
     *
     * @param string $sql
     *
     * @return array|false
     */
    public static function parser_sql($sql)
    {
        $sql = (string) $sql;
        if ($sql) {
            $parser = new Phpsql_Parser();
            return $parser->parse($sql);
        }
        return false;
    }
    /**
     * @param string $css_content
     * @param bool $fileuri
     *
     * @return string|false
     *
     * @throws PrestaShopException
     *
     * @deprecated 1.0.0 use Media::minifyCSS()
     */
    public static function minify_css($css_content, $fileuri = false)
    {
        Tools::display_as_deprecated();
        return Media::minify_css($css_content, $fileuri);
    }
    /**
     * @param array $matches
     *
     * @return string|false
     *
     * @throws PrestaShopException
     */
    public static function replace_by_absolute_url($matches)
    {
        Tools::display_as_deprecated();
        return Media::replace_by_absolute_url($matches);
    }
    /**
     * addJS load a javascript file in the header
     *
     * @deprecated 1.0.0 use FrontController->addJS()
     *
     * @param string|array $js_uri
     */
    public static function add_js($js_uri): void
    {
        Tools::display_as_deprecated();
        $context = Context::get_context();
        $context->controller->add_js($js_uri);
    }
    /**
     * @param string|array $css_uri
     * @param string $css_media_type
     */
    public static function add_css($css_uri, $css_media_type = 'all'): void
    {
        Tools::display_as_deprecated();
        $context = Context::get_context();
        $context->controller->add_css($css_uri, $css_media_type);
    }
    /**
     * @param array $css_files
     * @return array
     * @throws PrestaShopException
     */
    public static function ccc_css($css_files)
    {
        Tools::display_as_deprecated();
        return Media::ccc_css($css_files);
    }
    /**
     * @param array $js_files
     * @return array
     * @throws PrestaShopException
     * @deprecated 1.0.0 use Media::cccJS()
     */
    public static function ccc_js($js_files)
    {
        Tools::display_as_deprecated();
        return Media::ccc_js($js_files);
    }
    /**
     * @param string|null $filename
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function get_media_server($filename)
    {
        $shop_id = (int) Context::get_context()->shop->id;
        $media_servers = static::get_media_servers($shop_id);
        static::$_cache_nb_media_servers = count($media_servers);
        if ($filename && $media_servers) {
            $index = abs(crc32($filename)) % static::$_cache_nb_media_servers;
            return $media_servers[$index];
        }
        return Tools::using_secure_mode() ? Tools::get_shop_domain_ssl() : Tools::get_shop_domain();
    }
    /**
     *
     * @return array
     * @throws PrestaShopException
     */
    public static function get_media_servers(int $shop_id)
    {
        $cache_id = 'Tools::getMediaServers_' . $shop_id;
        if (!Cache::is_stored($cache_id)) {
            $media_servers = [];
            for ($i = 1; $i <= 3; $i++) {
                $key = 'PS_MEDIA_SERVER_' . $i;
                $media_server = Configuration::get($key, null, null, $shop_id);
                if ($media_server) {
                    $media_servers[] = $media_server;
                }
            }
            Cache::store($cache_id, $media_servers);
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * getShopDomainSsl returns domain name according to configuration and depending on ssl activation
     *
     * @param bool $http if true, return domain name with protocol
     * @param bool $entities if true, convert special chars to HTML entities
     *
     * @return string domain
     *
     * @throws PrestaShopException
     */
    public static function get_shop_domain_ssl($http = false, $entities = false)
    {
        if (!$domain = Shop_Url::get_main_shop_domain_ssl()) {
            $domain = Tools::get_http_host();
        }
        if ($entities) {
            $domain = htmlspecialchars($domain, ENT_COMPAT, 'UTF-8');
        }
        if ($http) {
            return (Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://') . $domain;
        }
        return $domain;
    }
    /**
     * getShopDomain returns domain name according to configuration and ignoring ssl
     *
     * @param bool $http if true, return domain name with protocol
     * @param bool $entities if true, convert special chars to HTML entities
     *
     * @return string domain
     *
     * @throws PrestaShopException
     */
    public static function get_shop_domain($http = false, $entities = false)
    {
        if (!$domain = Shop_Url::get_main_shop_domain()) {
            $domain = Tools::get_http_host();
        }
        if ($entities) {
            $domain = htmlspecialchars($domain, ENT_COMPAT, 'UTF-8');
        }
        if ($http) {
            return 'http://' . $domain;
        }
        return $domain;
    }
    /**
     * @param string|null $path
     * @param bool|null $rewrite_settings
     * @param bool|null $cache_control
     * @param string $specific
     * @param bool|null $disable_multiviews
     * @param bool $medias
     * @param bool|null $disable_modsec
     *
     *
     * @throws PrestaShopException
     */
    public static function generate_htaccess($path = null, $rewrite_settings = null, $cache_control = null, $specific = '', $disable_multiviews = null, $medias = false, $disable_modsec = null): bool
    {
        if (defined('TB_INSTALLATION_IN_PROGRESS') && $rewrite_settings === null) {
            return true;
        }
        // Default values for parameters
        if (is_null($path)) {
            $path = _PS_ROOT_DIR_ . '/.htaccess';
        }
        if (is_null($cache_control)) {
            $cache_control = (int) Configuration::get('PS_HTACCESS_CACHE_CONTROL');
        }
        if (is_null($disable_multiviews)) {
            $disable_multiviews = (int) Configuration::get('PS_HTACCESS_DISABLE_MULTIVIEWS');
        }
        if ($disable_modsec === null) {
            $disable_modsec = (int) Configuration::get('PS_HTACCESS_DISABLE_MODSEC');
        }
        // Check current content of .htaccess and save all code outside of thirty bees comments
        $specific_before = $specific_after = '';
        if (file_exists($path)) {
            if (static::is_submit('htaccess')) {
                $content = $_POST['htaccess'];
            } else {
                $content = file_get_contents($path);
            }
            if (preg_match('#^(.*)\# ~~start~~.*\# ~~end~~[^\n]*(.*)$#s', (string) $content, $m)) {
                $specific_before = $m[1];
                $specific_after = $m[2];
            } else if (preg_match('#\# http://www\.thirtybees\.com - http://www\.thirtybees\.com/forums\s*(.*)<IfModule mod_rewrite\.c>#si', (string) $content, $m)) {
                $specific_before = $m[1];
            } else {
                $specific_before = $content;
            }
        }
        // Write .htaccess data
        if (!$write_fd = @fopen($path, 'w')) {
            return false;
        }
        if ($specific_before) {
            fwrite($write_fd, trim((string) $specific_before) . "\n\n");
        }
        $domains = [];
        foreach (Shop_Url::get_shop_urls() as $shop_url) {
            /** @var ShopUrl $shop_url */
            if (!isset($domains[$shop_url->domain])) {
                $domains[$shop_url->domain] = [];
            }
            $domains[$shop_url->domain][] = ['physical' => $shop_url->physical_uri, 'virtual' => $shop_url->virtual_uri, 'id_shop' => $shop_url->id_shop];
            if ($shop_url->domain == $shop_url->domain_ssl) {
                continue;
            }
            if (!isset($domains[$shop_url->domain_ssl])) {
                $domains[$shop_url->domain_ssl] = [];
            }
            $domains[$shop_url->domain_ssl][] = ['physical' => $shop_url->physical_uri, 'virtual' => $shop_url->virtual_uri, 'id_shop' => $shop_url->id_shop];
        }
        // Write data in .htaccess file
        fwrite($write_fd, "# ~~start~~ Do not remove this comment, thirty bees will keep automatically the code outside this comment when .htaccess will be generated again\n");
        fwrite($write_fd, "# .htaccess automatically generated by thirty bees e-commerce open-source solution\n");
        fwrite($write_fd, "# http://www.thirtybees.com - http://www.thirtybees.com/forums\n\n");
        if ($disable_modsec) {
            fwrite($write_fd, "<IfModule mod_security.c>\nSecFilterEngine Off\nSecFilterScanPOST Off\n</IfModule>\n\n");
        }
        // RewriteEngine
        fwrite($write_fd, "<IfModule mod_rewrite.c>\n");
        // Ensure HTTP_MOD_REWRITE variable is set in environment
        fwrite($write_fd, "<IfModule mod_env.c>\n");
        fwrite($write_fd, "SetEnv HTTP_MOD_REWRITE On\n");
        fwrite($write_fd, "</IfModule>\n\n");
        // Disable multiviews ?
        if ($disable_multiviews) {
            fwrite($write_fd, "\n# Disable Multiviews\nOptions -Multiviews\n\n");
        }
        fwrite($write_fd, "RewriteEngine on\n");
        $media_domains = array_reduce(static::get_media_servers_urls(), fn($acc, string $media_server) => $acc . 'RewriteCond %{HTTP_HOST} ^' . $media_server . '$ [OR]' . "\n", '');
        $supported_main_image_extensions = Image_Manager::get_allowed_image_extensions(true, true);
        $supported_main_image_extensions[] = 'jpeg';
        $supported_main_image_extensions = array_unique($supported_main_image_extensions);
        sort($supported_main_image_extensions);
        $extensions_pattern = implode('|', $supported_main_image_extensions);
        foreach ($domains as $domain => $list_uri) {
            $domain_rewrite_cond = 'RewriteCond %{HTTP_HOST} ^' . $domain . '$' . "\n";
            foreach ($list_uri as $uri) {
                fwrite($write_fd, PHP_EOL . PHP_EOL . '# Domain: ' . $domain . PHP_EOL);
                if (Shop::is_feature_active()) {
                    fwrite($write_fd, 'RewriteCond %{HTTP_HOST} ^' . $domain . '$' . "\n");
                }
                fwrite($write_fd, 'RewriteRule . - [E=REWRITEBASE:' . $uri['physical'] . ']' . "\n\n");
                // Webservice
                fwrite($write_fd, "# Webservice API\n");
                fwrite($write_fd, 'RewriteRule ^api$ api/ [L]' . "\n");
                fwrite($write_fd, 'RewriteRule ^api/(.*)$ %{ENV:REWRITEBASE}webservice/dispatcher.php?url=$1 [QSA,L]' . "\n\n");
                if (!$rewrite_settings) {
                    $rewrite_settings = (int) Configuration::get('PS_REWRITING_SETTINGS', null, null, (int) $uri['id_shop']);
                }
                // Rewrite virtual multishop uri
                if ($uri['virtual']) {
                    fwrite($write_fd, "# Virtual uri\n");
                    if (!$rewrite_settings) {
                        fwrite($write_fd, $media_domains);
                        fwrite($write_fd, $domain_rewrite_cond);
                        fwrite($write_fd, 'RewriteRule ^' . trim((string) $uri['virtual'], '/') . '/?$ ' . $uri['physical'] . $uri['virtual'] . "index.php [L,R]\n");
                    } else {
                        fwrite($write_fd, $media_domains);
                        fwrite($write_fd, $domain_rewrite_cond);
                        fwrite($write_fd, 'RewriteRule ^' . trim((string) $uri['virtual'], '/') . '$ ' . $uri['physical'] . $uri['virtual'] . " [L,R]\n");
                    }
                    fwrite($write_fd, $media_domains);
                    fwrite($write_fd, $domain_rewrite_cond);
                    fwrite($write_fd, 'RewriteRule ^' . ltrim((string) $uri['virtual'], '/') . '(.*) ' . $uri['physical'] . "\$1 [L]\n\n");
                }
                if ($rewrite_settings) {
                    fwrite($write_fd, "# Images\n");
                    foreach (Image_Entity::get_image_entities() as $entity) {
                        $name = $entity['name'];
                        $path = trim(str_replace(_PS_ROOT_DIR_, '', $entity['path']), '/') . '/';
                        fwrite($write_fd, "\n# {$name} images\n");
                        if ($name !== Image_Entity::ENTITY_TYPE_PRODUCTS) {
                            fwrite($write_fd, $media_domains);
                            fwrite($write_fd, $domain_rewrite_cond);
                            fwrite($write_fd, 'RewriteRule ^' . $name . '/([0-9]+)(\-[_a-zA-Z0-9\s-]*)?/.+?([2-4]x)?\.(' . $extensions_pattern . ')$ %{ENV:REWRITEBASE}' . $path . '$1$2$3.$4 [L]' . "\n");
                        } else {
                            for ($i = 1; $i <= 8; $i++) {
                                $img_path = $img_name = '';
                                for ($j = 1; $j <= $i; $j++) {
                                    $img_path .= '$' . $j . '/';
                                    $img_name .= '$' . $j;
                                }
                                $img_name .= '$' . $j;
                                fwrite($write_fd, $media_domains);
                                fwrite($write_fd, $domain_rewrite_cond);
                                fwrite($write_fd, 'RewriteRule ^' . $name . '/' . str_repeat('([0-9])', $i) . '(\-[_a-zA-Z0-9\s-]*)?/.+?([2-4]x)?\.(' . $extensions_pattern . ')$ %{ENV:REWRITEBASE}' . $path . $img_path . $img_name . '$' . ($j + 1) . '.$' . ($j + 2) . " [L]\n");
                            }
                        }
                    }
                }
            }
            // Redirections to dispatcher
            if ($rewrite_settings) {
                fwrite($write_fd, "\n# Dispatcher\n");
                fwrite($write_fd, "RewriteCond %{REQUEST_FILENAME} -s [OR]\n");
                fwrite($write_fd, "RewriteCond %{REQUEST_FILENAME} -l [OR]\n");
                fwrite($write_fd, "RewriteCond %{REQUEST_FILENAME} -d\n");
                if (Shop::is_feature_active()) {
                    fwrite($write_fd, $domain_rewrite_cond);
                }
                fwrite($write_fd, "RewriteRule ^.*\$ - [NC,L]\n");
                if (Shop::is_feature_active()) {
                    fwrite($write_fd, $domain_rewrite_cond);
                }
                fwrite($write_fd, "RewriteRule ^.*\$ %{ENV:REWRITEBASE}index.php [NC,L]\n");
            }
        }
        fwrite($write_fd, "</IfModule>\n\n");
        fwrite($write_fd, "AddType application/vnd.ms-fontobject .eot\n");
        fwrite($write_fd, "AddType font/ttf .ttf\n");
        fwrite($write_fd, "AddType font/otf .otf\n");
        fwrite($write_fd, "AddType font/woff .woff\n");
        fwrite($write_fd, "AddType font/woff2 .woff2\n");
        fwrite($write_fd, "<IfModule mod_headers.c>\n\t<FilesMatch \"\\.(ttf|ttc|otf|eot|woff|woff2|svg)\$\">\n\t\tHeader set Access-Control-Allow-Origin \"*\"\n\t</FilesMatch>\n</IfModule>\n\n");
        // Cache control
        if ($cache_control) {
            $cache_control = "<IfModule mod_expires.c>\n\tExpiresActive On\n\tExpiresByType image/gif \"access plus 1 year\"\n\tExpiresByType image/jpeg \"access plus 1 year\"\n\tExpiresByType image/png \"access plus 1 year\"\n\tExpiresByType image/webp \"access plus 1 year\"\n\tExpiresByType image/avif \"access plus 1 year\"\n\tExpiresByType text/css \"access plus 1 year\"\n\tExpiresByType text/javascript \"access plus 1 year\"\n\tExpiresByType application/javascript \"access plus 1 year\"\n\tExpiresByType application/x-javascript \"access plus 1 year\"\n\tExpiresByType image/x-icon \"access plus 1 year\"\n\tExpiresByType image/svg+xml \"access plus 1 year\"\n\tExpiresByType image/vnd.microsoft.icon \"access plus 1 year\"\n\tExpiresByType application/font-woff \"access plus 1 year\"\n\tExpiresByType application/x-font-woff \"access plus 1 year\"\n\tExpiresByType font/woff \"access plus 1 year\"\n\tExpiresByType application/font-woff2 \"access plus 1 year\"\n\tExpiresByType font/woff2 \"access plus 1 year\"\n\tExpiresByType application/vnd.ms-fontobject \"access plus 1 year\"\n\tExpiresByType font/opentype \"access plus 1 year\"\n\tExpiresByType font/ttf \"access plus 1 year\"\n\tExpiresByType font/otf \"access plus 1 year\"\n\tExpiresByType application/x-font-ttf \"access plus 1 year\"\n\tExpiresByType application/x-font-otf \"access plus 1 year\"\n</IfModule>\n\n<IfModule mod_headers.c>\n\tHeader unset Etag\n</IfModule>\nFileETag none\n<IfModule mod_deflate.c>\n\t<IfModule mod_filter.c>\n\t\tAddOutputFilterByType DEFLATE text/html text/css text/javascript application/javascript application/x-javascript font/ttf application/x-font-ttf font/otf application/x-font-otf font/opentype\n\t</IfModule>\n</IfModule>\n\n";
            fwrite($write_fd, $cache_control);
        }
        // In case the user hasn't rewrite mod enabled
        fwrite($write_fd, "#If rewrite mod isn't enabled\n");
        // Do not remove ($domains is already iterated upper)
        reset($domains);
        $domain = current($domains);
        fwrite($write_fd, 'ErrorDocument 404 ' . $domain[0]['physical'] . "index.php?controller=404\n\n");
        fwrite($write_fd, '# ~~end~~ Do not remove this comment, thirty bees will keep automatically the code outside this comment when .htaccess will be generated again');
        if ($specific_after) {
            fwrite($write_fd, "\n\n" . trim($specific_after));
        }
        fclose($write_fd);
        if (!defined('TB_INSTALLATION_IN_PROGRESS')) {
            Hook::trigger_event('actionHtaccessCreate');
        }
        return true;
    }
    /**
     * Returns list of all defined media servers
     *
     * @return string[]
     * @throws PrestaShopException
     */
    public static function get_media_servers_urls(): array
    {
        $unique_domains = [];
        $media_servers_keys = ['PS_MEDIA_SERVER_1', 'PS_MEDIA_SERVER_2', 'PS_MEDIA_SERVER_3'];
        foreach ($media_servers_keys as $media_server_key) {
            $media_servers = Configuration::get_multi_shop_values($media_server_key);
            if ($media_servers) {
                foreach ($media_servers as $media_server) {
                    if ($media_server && is_string($media_server) && !isset($unique_domains[$media_server])) {
                        $unique_domains[$media_server] = $media_server;
                    }
                }
            }
        }
        return array_values($unique_domains);
    }
    /**
     * @throws PrestaShopException
     */
    public static function generate_index(): void
    {
        if (defined('_DB_PREFIX_') && Configuration::get('PS_DISABLE_OVERRIDES')) {
            Presta_Shop_Autoload::get_instance()->_include_override_path = false;
        }
        Presta_Shop_Autoload::get_instance()->generate_index();
    }
    /**
     * @return string
     */
    public static function get_default_index_content(): ?string
    {
        // Use a random, existing index.php as template.
        $content = file_get_contents(_PS_ROOT_DIR_ . '/classes/index.php');
        // Drop the license section, we can't really claim a license for an
        // auto-generated file.
        $replacement = '/* Auto-generated file, don\'t edit. */';
        return preg_replace('/\/\*.*\*\//s', $replacement, $content);
    }
    /**
     * jsonDecode convert json string to php array / object
     *
     * @param string $json
     * @param bool $assoc (since 1.4.2.4) if true, convert to associativ array
     *
     * @return object|array
     *
     * @deprecated 1.0.0 Use json_decode instead
     */
    public static function json_decode($json, $assoc = false): mixed
    {
        return json_decode($json, $assoc);
    }
    /**
     * Convert an array to json string
     *
     * @param object|array $data
     *
     * @return string json
     *
     * @deprecated 1.0.0 Use json_encode instead
     */
    public static function json_encode($data)
    {
        return json_encode($data);
    }
    public static function display_file_as_deprecated(): void
    {
        $backtrace = debug_backtrace();
        $callee = current($backtrace);
        $error = 'File ' . $callee['file'] . ' is deprecated and will be removed in the next major version.';
        trigger_error($error, E_USER_DEPRECATED);
    }
    /**
     * @param int $level
     *
     * @throws PrestaShopException
     */
    public static function enable_cache($level = 1, ?Context $context = null): void
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $smarty = $context->smarty;
        if (!Configuration::get('PS_SMARTY_CACHE')) {
            return;
        }
        if ($smarty->force_compile == 0 && $smarty->caching == $level) {
            return;
        }
        static::$_force_compile = (int) $smarty->force_compile;
        static::$_caching = (int) $smarty->caching;
        $smarty->force_compile = 0;
        $smarty->caching = (int) $level;
        $smarty->cache_lifetime = 31536000;
        // 1 Year
    }
    public static function restore_cache_settings(?Context $context = null): void
    {
        if (!$context) {
            $context = Context::get_context();
        }
        if (isset(static::$_force_compile)) {
            $context->smarty->force_compile = (int) static::$_force_compile;
        }
        if (isset(static::$_caching)) {
            $context->smarty->caching = (int) static::$_caching;
        }
    }
    /**
     * @param string $function
     */
    public static function is_callable($function): bool
    {
        $disabled = explode(',', ini_get('disable_functions'));
        return !in_array($function, $disabled) && is_callable($function);
    }
    /**
     * @param string $s
     *
     * @return string
     */
    public static function p_regexp($s, string $delim): array|string
    {
        $s = str_replace($delim, '\\' . $delim, $s);
        foreach (['?', '[', ']', '(', ')', '{', '}', '-', '.', '+', '*', '^', '$', '`', '"', '%'] as $char) {
            $s = str_replace($char, '\\' . $char, $s);
        }
        return $s;
    }
    /**
     * @param string $needle
     * @param string $replace
     * @param string $haystack
     *
     * @return string
     */
    public static function str_replace_once($needle, $replace, $haystack)
    {
        $pos = false;
        if ($needle) {
            $pos = strpos($haystack, $needle);
        }
        if ($pos === false) {
            return $haystack;
        }
        return substr_replace($haystack, $replace, $pos, strlen($needle));
    }
    /**
     * Function property_exists does not exist in PHP < 5.1
     *
     * @deprecated since 1.5.0 (PHP 5.1 required, so property_exists() is now natively supported)
     *
     * @param object $class
     * @param string $property
     */
    public static function property_exists($class, $property): bool
    {
        Tools::display_as_deprecated();
        return property_exists($class, $property);
    }
    /**
     * identify the version of php
     *
     * @return string
     */
    public static function check_php_version(): string|false
    {
        if (defined('PHP_VERSION')) {
            $version = PHP_VERSION;
        } else {
            $version = phpversion('');
        }
        //Case management system of ubuntu, php version return 5.2.4-2ubuntu5.2
        if (str_contains($version, '-')) {
            return substr($version, 0, strpos($version, '-'));
        }
        return $version;
    }
    /**
     * try to open a zip file in order to check if it's valid
     *
     * @return bool success
     */
    public static function zip_test($from_file): bool
    {
        $zip = new Zip_Archive();
        return $zip->open($from_file, ZIPARCHIVE::CHECKCONS) === true;
    }
    /**
     * @deprecated 1.0.3 Safe Mode was removed from PHP >= 5.4.
     */
    public static function get_safe_mode_status(): bool
    {
        Tools::display_as_deprecated();
        return false;
    }
    /**
     * extract a zip file to the given directory
     *
     * @return bool success
     */
    public static function zip_extract($from_file, $to_dir): bool
    {
        if (!file_exists($to_dir)) {
            mkdir($to_dir, 0777);
        }
        $zip = new Zip_Archive();
        if ($zip->open($from_file) === true && $zip->extract_to($to_dir) && $zip->close()) {
            return true;
        }
        return false;
    }
    /**
     * @param int $filemode
     * @return bool
     */
    public static function chmodr(string $path, $filemode)
    {
        if (!is_dir($path)) {
            return @chmod($path, $filemode);
        }
        $dh = opendir($path);
        while (($file = readdir($dh)) !== false) {
            if ($file != '.' && $file != '..') {
                $fullpath = $path . '/' . $file;
                if (is_link($fullpath)) {
                    return false;
                }
                if (!is_dir($fullpath) && !@chmod($fullpath, $filemode)) {
                    return false;
                }
                if (!Tools::chmodr($fullpath, $filemode)) {
                    return false;
                }
            }
        }
        closedir($dh);
        if (@chmod($path, $filemode)) {
            return true;
        }
        return false;
    }
    /**
     * Get products order field name for queries.
     *
     * @param string $type by|way
     * @param string|null $value If no index given, use default order from admin -> pref -> products
     * @param bool $prefix
     *
     * @return string Order by sql clause
     *
     * @throws PrestaShopException
     */
    public static function get_products_order($type, $value = null, $prefix = false)
    {
        switch ($type) {
            case 'by':
                $list = [0 => 'name', 1 => 'price', 2 => 'date_add', 3 => 'date_upd', 4 => 'position', 5 => 'manufacturer_name', 6 => 'quantity', 7 => 'reference'];
                $value = is_null($value) || $value === false || $value === '' ? (int) Configuration::get('PS_PRODUCTS_ORDER_BY') : $value;
                $value = $list[$value] ?? (in_array($value, $list) ? $value : 'position');
                $order_by_prefix = '';
                if ($prefix) {
                    if ($value == 'id_product' || $value == 'date_add' || $value == 'date_upd' || $value == 'price') {
                        $order_by_prefix = 'p.';
                    } elseif ($value == 'name') {
                        $order_by_prefix = 'pl.';
                    } elseif ($value == 'manufacturer_name') {
                        $order_by_prefix = 'm.';
                        $value = 'name';
                    } elseif ($value == 'position' || empty($value)) {
                        $order_by_prefix = 'cp.';
                    }
                }
                return $order_by_prefix . $value;
            case 'way':
                $value = is_null($value) || $value === false || $value === '' ? (int) Configuration::get('PS_PRODUCTS_ORDER_WAY') : $value;
                $list = [0 => 'asc', 1 => 'desc'];
                return $list[$value] ?? (in_array($value, $list) ? $value : 'asc');
            default:
                trigger_error('Method Tools::getProductsOrder called with invalid parameter $type = \'' . $type . '\'', E_USER_WARNING);
                return '';
        }
    }
    /**
     * @deprecated 1.0.0 use Controller::getController('PageNotFoundController')->run();
     */
    public static function display404Error(): never
    {
        Tools::display_as_deprecated();
        header('HTTP/1.1 404 Not Found');
        header('Status: 404 Not Found');
        header('Content-Type: text/plain');
        die('Not Found');
    }
    /**
     * Concat $begin and $end, add ? or & between strings
     *
     *
     */
    public static function url(string $begin, string $end): string
    {
        return $begin . (str_contains($begin, '?') ? '&' : '?') . $end;
    }
    /**
     * Display error and dies or silently log the error.
     *
     * @param string $msg
     * @param bool $die
     *
     * @return bool success of logging
     *
     * @throws PrestaShopException
     * @deprecated 1.0.7 For logging, use Logger::addLog() directly
     */
    public static function die_or_log($msg, $die = true)
    {
        Tools::display_as_deprecated();
        if ($die) {
            throw new Presta_Shop_Exception($msg);
        }
        return Logger::add_log($msg);
    }
    /**
     * Convert \n and \r\n and \r to <br />
     *
     * @param string $str String to transform
     *
     * @return string New string
     */
    public static function nl2br($str): string
    {
        if (is_null($str)) {
            return '';
        }
        return str_replace(["\r\n", "\r", "\n"], '<br />', $str);
    }
    /**
     * Clear Smarty cache and compile folders
     */
    public static function clear_smarty_cache(): void
    {
        $smarty = Context::get_context()->smarty;
        Tools::clear_cache($smarty);
        Tools::clear_compile($smarty);
    }
    /**
     * Clear cache for Smarty
     *
     * @param Smarty|null $smarty
     * @param string|false $tpl
     * @param string|null $cacheId
     * @param string|null $compileId
     * @return int
     */
    public static function clear_cache($smarty = null, $tpl = false, $cache_id = null, $compile_id = null)
    {
        if ($smarty === null) {
            $smarty = Context::get_context()->smarty;
        }
        if ($smarty === null) {
            return 0;
        }
        if (!$tpl && $cache_id === null && $compile_id === null) {
            return $smarty->clear_all_cache();
        }
        return $smarty->clear_cache($tpl, $cache_id, $compile_id);
    }
    /**
     * Clear compile for Smarty
     *
     * @param Smarty|null $smarty
     *
     * @return int
     */
    public static function clear_compile($smarty = null)
    {
        if ($smarty === null) {
            $smarty = Context::get_context()->smarty;
        }
        if ($smarty === null) {
            return 0;
        }
        return $smarty->clear_compiled_template();
    }
    /**
     * @param int|false $id_product
     */
    public static function clear_color_list_cache($id_product = false): void
    {
        // Change template dir if called from the BackOffice
        $current_template_dir = Context::get_context()->smarty->get_template_dir();
        Context::get_context()->smarty->set_template_dir(_PS_THEME_DIR_);
        Tools::clear_cache(null, _PS_THEME_DIR_ . 'product-list-colors.tpl', Product::get_colors_list_cache_id((int) $id_product, false));
        Context::get_context()->smarty->set_template_dir($current_template_dir);
    }
    /**
     * getMemoryLimit allow to get the memory limit in octet
     *
     * @return int the memory limit value in octet
     */
    public static function get_memory_limit()
    {
        $memory_limit = @ini_get('memory_limit');
        if ((int) $memory_limit <= 0) {
            return PHP_INT_MAX;
        }
        return Tools::get_octets($memory_limit);
    }
    /**
     * getOctet allow to gets the value of a configuration option in octet
     *
     * @return int the value of a configuration option in octet
     */
    public static function get_octets($option)
    {
        if (preg_match('/[0-9]+k/i', (string) $option)) {
            return 1024 * (int) $option;
        }
        if (preg_match('/[0-9]+m/i', (string) $option)) {
            return 1024 * 1024 * (int) $option;
        }
        if (preg_match('/[0-9]+g/i', (string) $option)) {
            return 1024 * 1024 * 1024 * (int) $option;
        }
        return $option;
    }
    /**
     * @return bool true if the server use 64bit arch
     */
    public static function is_x86_64arch(): bool
    {
        return PHP_INT_MAX == '9223372036854775807';
    }
    /**
     * @return bool true if php-cli is used
     */
    public static function is_phpcli(): bool
    {
        return defined('STDIN') || mb_strtolower(php_sapi_name()) == 'cli' && empty($_SERVER['REMOTE_ADDR']);
    }
    /**
     * @param int $argc
     * @param string[] $argv
     */
    public static function argv_to_get($argc, array $argv): void
    {
        if ($argc <= 1) {
            return;
        }
        // get the first argument and parse it like a query string
        parse_str($argv[1], $args);
        if (!count($args)) {
            return;
        }
        $_GET = array_merge($args, $_GET);
        $_SERVER['QUERY_STRING'] = $argv[1];
    }
    /**
     * Get max file upload size considering server settings and optional max value
     *
     * @param int $max_size optional max file size
     *
     * @return int max file size in bytes
     */
    public static function get_max_upload_size($max_size = 0): mixed
    {
        $post_max_size = Tools::convert_bytes(ini_get('post_max_size'));
        $upload_max_filesize = Tools::convert_bytes(ini_get('upload_max_filesize'));
        if ($max_size > 0) {
            return min($post_max_size, $upload_max_filesize, $max_size);
        }
        return min($post_max_size, $upload_max_filesize);
    }
    /**
     * Convert a shorthand byte value from a PHP configuration directive to an integer value
     *
     * @param string $value value to convert
     *
     * @return int
     */
    public static function convert_bytes($value)
    {
        if (is_numeric($value)) {
            return $value;
        }
        $value_length = strlen($value);
        $qty = (int) substr($value, 0, $value_length - 1);
        $unit = mb_strtolower(substr($value, $value_length - 1));
        match ($unit) {
            'k' => $qty *= 1024,
            'm' => $qty *= 1048576,
            'g' => $qty *= 1073741824,
            default => $qty,
        };
        return $qty;
    }
    /**
     * Copy the folder $src into $dst, $dst is created if it do not exist
     *
     * @param bool $del if true, delete the file after copy
     *
     * @return bool
     */
    public static function recurse_copy(string $src, string $dst, $del = false)
    {
        if (!file_exists($src)) {
            return false;
        }
        $dir = opendir($src);
        if ($dir === false) {
            return false;
        }
        $result = true;
        if (!file_exists($dst)) {
            $result = mkdir($dst);
        }
        while (false !== $file = readdir($dir)) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
                    $result = static::recurse_copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file, $del) && $result;
                } else {
                    $result = copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file) && $result;
                    if ($del && is_writable($src . DIRECTORY_SEPARATOR . $file)) {
                        $result = unlink($src . DIRECTORY_SEPARATOR . $file) && $result;
                    }
                }
            }
        }
        closedir($dir);
        if ($del && is_writable($src)) {
            return rmdir($src) && $result;
        }
        return $result;
    }
    /**
     * file_exists() wrapper with cache to speedup performance
     *
     * @param string $filename File name
     *
     * @return bool Cached result of file_exists($filename)
     *
     * @deprecated 1.0.0 Please do not use this function. PHP already caches this function.
     */
    public static function file_exists_cache($filename)
    {
        if (!isset(static::$file_exists_cache[$filename])) {
            static::$file_exists_cache[$filename] = file_exists($filename);
        }
        return static::$file_exists_cache[$filename];
    }
    /**
     * @param string $path Path to scan
     * @param string $ext Extention to filter files
     * @param string $dir Add this to prefix output for example /path/dir/*
     *
     * @return array List of file found
     */
    public static function scandir($path, ?string $ext = 'php', ?string $dir = '', $recursive = false): array
    {
        $path = rtrim(rtrim($path, '\\'), '/') . '/';
        $real_path = rtrim(rtrim($path . $dir, '\\'), '/') . '/';
        $files = scandir($real_path);
        if (!$files) {
            return [];
        }
        $filtered_files = [];
        $real_ext = false;
        if (!empty($ext)) {
            $real_ext = '.' . $ext;
        }
        $real_ext_length = strlen($real_ext);
        $subdir = $dir ? $dir . '/' : '';
        foreach ($files as $file) {
            if (!$real_ext || strpos($file, $real_ext) && strpos($file, $real_ext) == strlen($file) - $real_ext_length) {
                $filtered_files[] = $subdir . $file;
            }
            if ($recursive && $file[0] != '.' && is_dir($real_path . $file)) {
                foreach (Tools::scandir($path, $ext, $subdir . $file, $recursive) as $subfile) {
                    $filtered_files[] = $subfile;
                }
            }
        }
        return $filtered_files;
    }
    /**
     * Align version sent and use internal function
     *
     * @param string $v1
     * @param string $v2
     * @param string $operator
     */
    public static function version_compare($v1, $v2, $operator = '<'): bool
    {
        Tools::align_version_number($v1, $v2);
        return version_compare($v1, $v2, $operator);
    }
    /**
     * Align 2 version with the same number of sub version
     * version_compare will work better for its comparison :)
     * (Means: '1.8' to '1.9.3' will change '1.8' to '1.8.0')
     *
     * @param string $v1
     * @param string $v2
     */
    public static function align_version_number(&$v1, &$v2): void
    {
        $len1 = count(explode('.', trim($v1, '.')));
        $len2 = count(explode('.', trim($v2, '.')));
        if ($len1 === $len2) {
            return;
        }
        $len = 0;
        $str = '';
        if ($len1 > $len2) {
            $len = $len1 - $len2;
            $str =& $v2;
        } elseif ($len2 > $len1) {
            $len = $len2 - $len1;
            $str =& $v1;
        }
        $str .= str_repeat('.0', $len);
    }
    /**
     * @return true
     *
     * @deprecated 1.0.1 Not everyone uses Apache
     */
    public static function mod_rewrite_active(): bool
    {
        return true;
    }
    /**
     * apacheModExists return true if the apache module $name is loaded
     *
     * @TODO    move this method in class Information (when /it will exist)
     *
     * Notes: This method requires either apache_get_modules or phpinfo()
     * to be available. With CGI mod, we cannot get php modules
     *
     * @param string $name module name
     *
     * @return bool true if exists
     */
    public static function apache_mod_exists($name): bool
    {
        if (function_exists('apache_get_modules')) {
            static $apache_module_list = null;
            if (!is_array($apache_module_list)) {
                $apache_module_list = apache_get_modules();
            }
            // we need strpos (example, evasive can be evasive20)
            foreach ($apache_module_list as $module) {
                if (str_contains((string) $module, $name)) {
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * @param string $serialized
     * @param bool $object
     *
     * @return mixed|false
     *
     * @deprecated Switch to using json_{en|de}code(). Serializing isn't safe
     *             for untrusted data and JSON is more compact anyways.
     *             See http://php.net/manual/en/function.unserialize.php.
     */
    public static function un_serialize($serialized, $object = false)
    {
        if (is_string($serialized) && (!str_contains($serialized, 'O:') || !preg_match('/(^|;|{|})O:[0-9]+:"/', $serialized)) && !$object || $object) {
            return @unserialize($serialized);
        }
        return false;
    }
    /**
     * Reproduce array_unique working before php version 5.2.9
     *
     * @param array $array
     * @deprecated 1.0.0 Use array_unique instead
     */
    public static function array_unique($array): array
    {
        static::display_as_deprecated();
        return array_unique($array, SORT_REGULAR);
    }
    /**
     * Delete unicode class from regular expression patterns
     *
     * @param string $pattern
     *
     * @return string pattern
     */
    public static function clean_non_unicode_support($pattern)
    {
        if (!defined('PREG_BAD_UTF8_OFFSET')) {
            return $pattern;
        }
        return preg_replace('/\\\\[px]\{[a-z]{1,2}\}|(\/[a-z]*)u([a-z]*)$/i', '$1$2', $pattern);
    }
    /**
     * @param string $request
     * @param array $params
     *
     *
     * @deprecated 1.0.0
     */
    public static function addons_request($request, $params = []): bool
    {
        static::display_as_deprecated();
        return false;
    }
    /**
     * Returns an array containing information about
     * HTTP file upload variable ($_FILES)
     *
     * @param string $input File upload field name
     * @param bool $return_content If true, returns uploaded file contents
     */
    public static function file_attachment($input = 'fileUpload', $return_content = true): ?array
    {
        $file_attachment = null;
        if (!empty($_FILES[$input]['name']) && !empty($_FILES[$input]['tmp_name'])) {
            $file_attachment['rename'] = uniqid() . mb_strtolower(substr((string) $_FILES[$input]['name'], -5));
            if ($return_content) {
                $file_attachment['content'] = file_get_contents($_FILES[$input]['tmp_name']);
            }
            $file_attachment['tmp_name'] = $_FILES[$input]['tmp_name'];
            $file_attachment['name'] = $_FILES[$input]['name'];
            $file_attachment['mime'] = $_FILES[$input]['type'];
            $file_attachment['error'] = $_FILES[$input]['error'];
            $file_attachment['size'] = $_FILES[$input]['size'];
        }
        return $file_attachment;
    }
    /**
     * @param string $filename
     *
     * @return bool
     */
    public static function change_file_m_time($filename)
    {
        $dir = dirname($filename);
        if (!@file_exists($dir)) {
            @mkdir($dir, 0777, true);
        }
        return @touch($filename);
    }
    /**
     * @param string $file_name
     * @param int $timeout
     */
    public static function wait_until_file_is_modified($file_name, $timeout = 180): void
    {
        @ini_set('max_execution_time', $timeout);
        if (($time_limit = ini_get('max_execution_time')) === null) {
            $time_limit = 30;
        }
        $time_limit -= 5;
        $start_time = microtime(true);
        $last_modified = @filemtime($file_name);
        while (true) {
            if (microtime(true) - $start_time > $time_limit || @filemtime($file_name) > $last_modified) {
                break;
            }
            clearstatcache();
            usleep(300);
        }
    }
    /**
     * Delete a substring from another one starting from the right
     *
     * @param string $str
     * @param string $str_search
     *
     * @return string
     */
    public static function rtrim_string($str, $str_search)
    {
        $length_str = strlen($str_search);
        if (strlen($str) >= $length_str && substr($str, -$length_str) == $str_search) {
            return substr($str, 0, -$length_str);
        }
        return $str;
    }
    /**
     * Format a number into a human readable format
     * e.g. 24962496 => 23.81M
     *
     * @param int $size
     * @param int $precision
     */
    public static function format_bytes($size, $precision = 2): string
    {
        if (!$size) {
            return '0';
        }
        $base = log($size) / log(1024);
        $suffixes = ['', 'k', 'M', 'G', 'T'];
        return round(1024 ** ($base - floor($base)), $precision) . $suffixes[floor($base)];
    }
    /**
     * @param bool $value
     *
     *
     * @deprecated Use a cast instead
     */
    public static function bool_val($value): bool
    {
        if (empty($value)) {
            $value = false;
        }
        return (bool) $value;
    }
    /**
     * @return string
     */
    public static function get_user_platform()
    {
        if (isset(static::$_user_plateform)) {
            return static::$_user_plateform;
        }
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        static::$_user_plateform = 'unknown';
        if (preg_match('/linux/i', (string) $user_agent)) {
            static::$_user_plateform = 'Linux';
        } elseif (preg_match('/macintosh|mac os x/i', (string) $user_agent)) {
            static::$_user_plateform = 'Mac';
        } elseif (preg_match('/windows|win32/i', (string) $user_agent)) {
            static::$_user_plateform = 'Windows';
        }
        return static::$_user_plateform;
    }
    /**
     * @return string
     */
    public static function get_user_browser()
    {
        if (isset(static::$_user_browser)) {
            return static::$_user_browser;
        }
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        static::$_user_browser = 'unknown';
        if (preg_match('/MSIE/i', (string) $user_agent) && !preg_match('/Opera/i', (string) $user_agent)) {
            static::$_user_browser = 'Internet Explorer';
        } elseif (preg_match('/Firefox/i', (string) $user_agent)) {
            static::$_user_browser = 'Mozilla Firefox';
        } elseif (preg_match('/Chrome/i', (string) $user_agent)) {
            static::$_user_browser = 'Google Chrome';
        } elseif (preg_match('/Safari/i', (string) $user_agent)) {
            static::$_user_browser = 'Apple Safari';
        } elseif (preg_match('/Opera/i', (string) $user_agent)) {
            static::$_user_browser = 'Opera';
        } elseif (preg_match('/Netscape/i', (string) $user_agent)) {
            static::$_user_browser = 'Netscape';
        }
        return static::$_user_browser;
    }
    /**
     * Allows to display the category description without HTML tags and slashes
     */
    public static function get_description_clean($description): string
    {
        return strip_tags(stripslashes((string) $description));
    }
    /**
     * @param string|null $html
     * @param array|null $uriUnescape
     * @param bool $allowStyle
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function purify_html($html, $uri_unescape = null, $allow_style = false)
    {
        static $use_html_purifier = null;
        static $purifier = null;
        if (defined('TB_INSTALLATION_IN_PROGRESS') || !Configuration::configuration_is_loaded()) {
            return $html;
        }
        if ($use_html_purifier === null) {
            $use_html_purifier = (bool) Configuration::get('PS_USE_HTMLPURIFIER');
        }
        if ($use_html_purifier) {
            try {
                if ($purifier === null) {
                    $config = Html_Purifier_config::create_default();
                    $config->set('Attr.EnableID', true);
                    $config->set('HTML.Trusted', true);
                    $config->set('Cache.SerializerPath', _PS_CACHE_DIR_ . 'purifier');
                    $config->set('Attr.AllowedFrameTargets', ['_blank', '_self', '_parent', '_top']);
                    $config->set('Core.NormalizeNewlines', false);
                    if (is_array($uri_unescape)) {
                        $config->set('URI.UnescapeCharacters', implode('', $uri_unescape));
                    }
                    if (Configuration::get('PS_ALLOW_HTML_IFRAME')) {
                        $config->set('HTML.SafeIframe', true);
                        $config->set('HTML.SafeObject', true);
                        $config->set('URI.SafeIframeRegexp', '/.*/');
                    }
                    /** @var HTMLPurifier_HTMLDefinition|HTMLPurifier_HTMLModule $def */
                    // http://developers.whatwg.org/the-video-element.html#the-video-element
                    if ($def = $config->get_html_definition(true)) {
                        $def->add_element('video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', ['src' => 'URI', 'type' => 'Text', 'width' => 'Length', 'height' => 'Length', 'poster' => 'URI', 'preload' => 'Enum#auto,metadata,none', 'controls' => 'Bool']);
                        $def->add_element('source', 'Block', 'Flow', 'Common', ['src' => 'URI', 'type' => 'Text']);
                        $def->add_element('meta', 'Inline', 'Empty', 'Common', ['itemprop' => 'Text', 'itemscope' => 'Bool', 'itemtype' => 'URI', 'name' => 'Text', 'content' => 'Text']);
                        $def->add_element('link', 'Inline', 'Empty', 'Common', ['rel' => 'Text', 'href' => 'Text', 'sizes' => 'Text']);
                        if ($allow_style) {
                            $def->add_element('style', 'Block', 'Flow', 'Common', ['type' => 'Text']);
                        }
                    }
                    $purifier = new Html_Purifier($config);
                }
                if (!is_null($html)) {
                    $html = $purifier->purify($html);
                }
            } catch (Throwable $e) {
                throw new Presta_Shop_Exception('Failed to purify html string', 0, $e);
            }
        }
        return $html;
    }
    /**
     * Check if a constant was already defined
     *
     * @param string $constant Constant name
     * @param mixed $value Default value to set if not defined
     */
    public static function safe_define($constant, $value): void
    {
        if (!defined($constant)) {
            define($constant, $value);
        }
    }
    /**
     * Spread an amount on lines, adjusting the $column field,
     * with the biggest adjustments going to the rows having the
     * highest $sort_column.
     *
     * E.g.:
     *
     * $rows = [['a' => 5.1], ['a' => 8.2]];
     *
     * spreadAmount(0.3, 1, $rows, 'a');
     *
     * => $rows is [['a' => 8.4], ['a' => 5.2]]
     *
     * @param float $amount The amount to spread across the rows
     * @param int $precision Rounding precision
     *                       e.g. if $amount is 1, $precision is 0 and $rows = [['a' => 2], ['a' => 1]]
     *                       then the resulting $rows will be [['a' => 3], ['a' => 1]]
     *                       But if $precision were 1, then the resulting $rows would be [['a' => 2.5], ['a' => 1.5]]
     * @param array &$rows An array, associative or not, containing arrays that have at least $column and $sort_column fields
     * @param string $column The column on which to perform adjustments
     */
    public static function spread_amount($amount, $precision, &$rows, $column): void
    {
        if (!is_array($rows) || empty($rows)) {
            return;
        }
        uasort($rows, fn($a, $b) => $b[$column] > $a[$column] ? 1 : -1);
        $unit = 10 ** $precision;
        $int_amount = (int) round($unit * $amount);
        $remainder = $int_amount % count($rows);
        $amount_to_spread = ($int_amount - $remainder) / count($rows) / $unit;
        $sign = $amount >= 0 ? 1 : -1;
        $position = 0;
        foreach ($rows as &$row) {
            $adjustment_factor = $amount_to_spread;
            if ($position < abs($remainder)) {
                $adjustment_factor += $sign / $unit;
            }
            $row[$column] += $adjustment_factor;
            ++$position;
        }
        unset($row);
    }
    /**
     * Replaces elements from passed arrays into the first array recursively
     *
     * @param array $base The array in which elements are replaced.
     * @param array $replacements The array from which elements will be extracted.
     *
     * @deprecated 1.5.0
     */
    public static function array_replace_recursive($base, $replacements): array
    {
        Tools::display_as_deprecated('Use function array_replace_recursive() instead');
        return array_replace_recursive($base, $replacements);
    }
    /**
     * Smarty {implode} plugin
     *
     * Type:     function<br>
     * Name:     implode<br>
     * Purpose:  implode Array
     * Use: {implode value="" separator=""}
     *
     * @link http://www.smarty.net/manual/en/language.function.fetch.php Smarty online manual
     *
     * @param array $params parameters
     * @param Smarty_Internal_Template $template template object
     * @return string|null if the assign parameter is passed, Smarty assigns the result to a template variable
     */
    public static function smarty_implode(array $params, $template): string
    {
        if (!isset($params['value'])) {
            trigger_error("[plugin] implode parameter 'value' cannot be empty", E_USER_NOTICE);
            return '';
        }
        if (empty($params['separator'])) {
            $params['separator'] = ',';
        }
        return implode($params['separator'], $params['value']);
    }
    /**
     * Encode table
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     *
     * Copyright (c) 2014 TrueServer B.V.
     *
     * Permission is hereby granted, free of charge, to any person obtaining a copy
     * of this software and associated documentation files (the "Software"), to deal
     * in the Software without restriction, including without limitation the rights
     * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
     * copies of the Software, and to permit persons to whom the Software is furnished
     * to do so, subject to the following conditions:
     *
     * The above copyright notice and this permission notice shall be included in all
     * copies or substantial portions of the Software.
     *
     * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
     * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
     * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
     * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
     * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
     * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
     * THE SOFTWARE.
     *
     * @var array
     */
    protected static $encode_table = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    /**
     * Decode table
     *
     * @var array
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    protected static $decode_table = ['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3, 'e' => 4, 'f' => 5, 'g' => 6, 'h' => 7, 'i' => 8, 'j' => 9, 'k' => 10, 'l' => 11, 'm' => 12, 'n' => 13, 'o' => 14, 'p' => 15, 'q' => 16, 'r' => 17, 's' => 18, 't' => 19, 'u' => 20, 'v' => 21, 'w' => 22, 'x' => 23, 'y' => 24, 'z' => 25, '0' => 26, '1' => 27, '2' => 28, '3' => 29, '4' => 30, '5' => 31, '6' => 32, '7' => 33, '8' => 34, '9' => 35];
    /**
     * Convert a UTF-8 email addres to IDN format (domain part only)
     *
     * @param string $email
     *
     * @return string
     */
    public static function convert_email_to_idn($email)
    {
        if (is_string($email) && mb_detect_encoding($email, 'UTF-8', true) && mb_strpos($email, '@') > -1) {
            // Convert to IDN
            [$local, $domain] = explode('@', $email, 2);
            $domain = Tools::utf8to_idn($domain);
            $email = "{$local}@{$domain}";
        }
        return $email;
    }
    /**
     * Convert an IDN email to UTF-8 (domain part only)
     *
     * @param string $email
     *
     * @return string
     */
    public static function convert_email_from_idn($email)
    {
        if (mb_strpos($email, '@') > -1) {
            // Convert from IDN if necessary
            [$local, $domain] = explode('@', $email, 2);
            $domain = Tools::idn_to_utf8($domain);
            $email = "{$local}@{$domain}";
        }
        return $email;
    }
    /**
     * Encode a domain to its Punycode version
     *
     * @param string $input Domain name in Unicode to be encoded
     *
     * @return string Punycode representation in ASCII
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    public static function utf8to_idn($input): false|string
    {
        $input = mb_strtolower($input);
        $parts = explode('.', $input);
        foreach ($parts as &$part) {
            $length = strlen($part);
            if ($length < 1) {
                return false;
            }
            $part = static::encode_part($part);
        }
        $output = implode('.', $parts);
        $length = strlen($output);
        if ($length > 255) {
            return false;
        }
        return $output;
    }
    /**
     * Decode a Punycode domain name to its Unicode counterpart
     *
     * @param string $input Domain name in Punycode
     *
     * @return string Unicode domain name
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    public static function idn_to_utf8($input): false|string
    {
        $input = strtolower($input);
        $parts = explode('.', $input);
        foreach ($parts as &$part) {
            $length = strlen($part);
            if ($length > 63 || $length < 1) {
                return false;
            }
            if (!str_starts_with($part, (string) static::PUNYCODE_PREFIX)) {
                continue;
            }
            $part = substr($part, strlen((string) static::PUNYCODE_PREFIX));
            $part = static::decode_part($part);
        }
        $output = implode('.', $parts);
        $length = strlen($output);
        if ($length > 255) {
            return false;
        }
        return $output;
    }
    /**
     * Encode a part of a domain name, such as tld, to its Punycode version
     *
     * @param string $input Part of a domain name
     *
     * @return string Punycode representation of a domain part
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    protected static function encode_part($input): string|false
    {
        $code_points = static::list_code_points($input);
        $n = static::PUNYCODE_INITIAL_N;
        $bias = static::PUNYCODE_INITIAL_BIAS;
        $delta = 0;
        $h = $b = count($code_points['basic']);
        $output = '';
        foreach ($code_points['basic'] as $code) {
            $output .= static::code_point_to_char($code);
        }
        if ($input === $output) {
            return $output;
        }
        if ($b > 0) {
            $output .= static::PUNYCODE_DELIMITER;
        }
        $code_points['nonBasic'] = array_unique($code_points['nonBasic']);
        sort($code_points['nonBasic']);
        $i = 0;
        $length = static::strlen($input);
        while ($h < $length) {
            $m = $code_points['nonBasic'][$i++];
            $delta = $delta + ($m - $n) * ($h + 1);
            $n = $m;
            foreach ($code_points['all'] as $c) {
                if ($c < $n || $c < static::PUNYCODE_INITIAL_N) {
                    $delta++;
                }
                if ($c === $n) {
                    $q = $delta;
                    for ($k = static::PUNYCODE_BASE;; $k += static::PUNYCODE_BASE) {
                        $t = static::calculate_threshold($k, $bias);
                        if ($q < $t) {
                            break;
                        }
                        $code = $t + ($q - $t) % (static::PUNYCODE_BASE - $t);
                        $output .= static::$encode_table[$code];
                        $q = ($q - $t) / (static::PUNYCODE_BASE - $t);
                    }
                    $output .= static::$encode_table[$q];
                    $bias = static::adapt($delta, $h + 1, $h === $b);
                    $delta = 0;
                    $h++;
                }
            }
            $delta++;
            $n++;
        }
        $out = static::PUNYCODE_PREFIX . $output;
        $length = strlen($out);
        if ($length > 63 || $length < 1) {
            return false;
        }
        return $out;
    }
    /**
     * Decode a part of domain name, such as tld
     *
     * @param string $input Part of a domain name
     *
     * @return string Unicode domain part
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    protected static function decode_part($input): string
    {
        $n = static::PUNYCODE_INITIAL_N;
        $i = 0;
        $bias = static::PUNYCODE_INITIAL_BIAS;
        $output = '';
        $pos = strrpos($input, (string) static::PUNYCODE_DELIMITER);
        if ($pos !== false) {
            $output = substr($input, 0, $pos++);
        } else {
            $pos = 0;
        }
        $output_length = strlen($output);
        $input_length = strlen($input);
        while ($pos < $input_length) {
            $oldi = $i;
            $w = 1;
            for ($k = static::PUNYCODE_BASE;; $k += static::PUNYCODE_BASE) {
                $digit = static::$decode_table[$input[$pos++]];
                $i = $i + $digit * $w;
                $t = static::calculate_threshold($k, $bias);
                if ($digit < $t) {
                    break;
                }
                $w = $w * (static::PUNYCODE_BASE - $t);
            }
            $bias = static::adapt($i - $oldi, ++$output_length, $oldi === 0);
            $n = $n + (int) ($i / $output_length);
            $i = $i % $output_length;
            $output = static::substr($output, 0, $i) . static::code_point_to_char($n) . static::substr($output, $i, $output_length - 1);
            $i++;
        }
        return $output;
    }
    /**
     * Calculate the bias threshold to fall between TMIN and TMAX
     *
     * @param integer $k
     * @param integer $bias
     *
     * @return integer
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    protected static function calculate_threshold($k, $bias)
    {
        if ($k <= $bias + static::PUNYCODE_TMIN) {
            return static::PUNYCODE_TMIN;
        }
        if ($k >= $bias + static::PUNYCODE_TMAX) {
            return static::PUNYCODE_TMAX;
        }
        return $k - $bias;
    }
    /**
     * Bias adaptation
     *
     * @param integer $delta
     * @param integer $numPoints
     * @param boolean $firstTime
     *
     * @return integer
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    protected static function adapt($delta, $num_points, $first_time): float|int
    {
        $delta = (int) ($first_time ? $delta / static::PUNYCODE_DAMP : $delta / 2);
        $delta += (int) ($delta / $num_points);
        $k = 0;
        while ($delta > (static::PUNYCODE_BASE - static::PUNYCODE_TMIN) * static::PUNYCODE_TMAX / 2) {
            $delta = (int) ($delta / (static::PUNYCODE_BASE - static::PUNYCODE_TMIN));
            $k = $k + static::PUNYCODE_BASE;
        }
        return $k + (int) ((static::PUNYCODE_BASE - static::PUNYCODE_TMIN + 1) * $delta / ($delta + static::PUNYCODE_SKEW));
    }
    /**
     * List code points for a given input
     *
     * @param string $input
     *
     * @return array Multi-dimension array with basic, non-basic and aggregated code points
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    protected static function list_code_points($input): array
    {
        $code_points = ['all' => [], 'basic' => [], 'nonBasic' => []];
        $length = static::strlen($input);
        for ($i = 0; $i < $length; $i++) {
            $char = static::substr($input, $i, 1);
            $code = static::char_to_code_point($char);
            if ($code < 128) {
                $code_points['all'][] = $code_points['basic'][] = $code;
            } else {
                $code_points['all'][] = $code_points['nonBasic'][] = $code;
            }
        }
        return $code_points;
    }
    /**
     * Convert a single or multi-byte character to its code point
     *
     * @param string $char
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    protected static function char_to_code_point($char): int
    {
        $code = ord($char[0]);
        if ($code < 128) {
            return $code;
        }
        if ($code < 224) {
            return ($code - 192) * 64 + (ord($char[1]) - 128);
        }
        if ($code < 240) {
            return ($code - 224) * 4096 + (ord($char[1]) - 128) * 64 + (ord($char[2]) - 128);
        }
        return ($code - 240) * 262144 + (ord($char[1]) - 128) * 4096 + (ord($char[2]) - 128) * 64 + (ord($char[3]) - 128);
    }
    /**
     * Convert a code point to its single or multi-byte character
     *
     * @param integer $code
     *
     * @copyright 2014 TrueServer B.V. (https://github.com/true/php-punycode)
     */
    protected static function code_point_to_char($code): string
    {
        if ($code <= 0x7f) {
            return chr($code);
        }
        if ($code <= 0x7ff) {
            return chr(($code >> 6) + 192) . chr(($code & 63) + 128);
        }
        if ($code <= 0xffff) {
            return chr(($code >> 12) + 224) . chr(($code >> 6 & 63) + 128) . chr(($code & 63) + 128);
        }
        return chr(($code >> 18) + 240) . chr(($code >> 12 & 63) + 128) . chr(($code >> 6 & 63) + 128) . chr(($code & 63) + 128);
    }
    /**
     * Base 64 encode that does not require additional URL Encoding for i.e. cookies
     *
     * This greatly reduces the size of a cookie
     *
     * @param string $data
     */
    public static function base64url_encode($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    /**
     * Base 64 decode for base64UrlEncoded data
     *
     * @param string $data
     */
    public static function base64url_decode($data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
    /**
     * Grabs a size tag from a DOMElement (as HTML)
     *
     * @param string $html
     *
     * @return array|false
     */
    public static function parse_favicon_size_tag($html): false|array
    {
        $src_found = false;
        $favicon = [];
        preg_match('/\{(.*)\}/U', $html, $m);
        if (!$m || count($m) < 2) {
            return false;
        }
        $tags = explode(' ', $m[1]);
        foreach ($tags as $tag) {
            $components = explode('=', $tag);
            if (count($components) === 1) {
                if ($components[0] === 'src') {
                    $src_found = true;
                }
                continue;
            }
            switch ($components[0]) {
                case 'type':
                    $favicon['type'] = $components[1];
                    break;
                case 'size':
                    $dimension = explode('x', $components[1]);
                    if (count($dimension) === 2) {
                        $favicon['width'] = $dimension[0];
                        $favicon['height'] = $dimension[1];
                    }
                    break;
            }
        }
        if ($src_found && array_key_exists('width', $favicon) && array_key_exists('height', $favicon)) {
            if (!isset($favicon['type'])) {
                $favicon['type'] = 'png';
            }
            return $favicon;
        }
        return false;
    }
    /**
     * Returns current server timezone setting.
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function get_time_zone()
    {
        $timezone = Configuration::get('PS_TIMEZONE');
        if (!$timezone) {
            // Fallback use php timezone settings.
            return date_default_timezone_get();
        }
        return $timezone;
    }
    /**
     * Converts date from given format to result format.
     *
     * @param string $format Expected format of the date given.
     * @param string $date Date to reformat.
     * @param string $resultFormat Format of the returned date.
     *
     * @return string Reformatted date.
     */
    public static function get_date_from_date_format($format, $date, $result_format = 'Y-m-d H:i:s'): ?string
    {
        $date = (string) $date;
        if ($date) {
            $d = DateTime::create_from_format($format, $date);
            if ($d && $d->format($format) == $date) {
                if ($result_format === 'Y-m-d H:i:s') {
                    $d->set_time(0, 0, 0);
                }
                return $d->format($result_format);
            }
        }
        return null;
    }
    /**
     * Returns true, if directory is empty
     *
     * @param string $directory path to directory to check
     * @param array $ignore list of files/directories that can exists in the directory for it to be considered empty
     */
    public static function is_directory_empty($directory, $ignore = []): bool
    {
        if (file_exists($directory) && is_dir($directory) && is_readable($directory)) {
            $files = scandir($directory);
            if (is_array($files)) {
                $array_ignore = array_merge(['.', '..'], $ignore);
                foreach ($files as $filename) {
                    if (!in_array($filename, $array_ignore)) {
                        return false;
                    }
                }
                return true;
            }
        }
        return false;
    }
    /**
     * Parse input string number value and returns float
     *
     * @param float|string|int $input The input value
     * @return float price, rounded to _TB_PRICE_DATABASE_PRECISION_.
     *
     * @see ToolsTest::parsePriceData() for more information
     */
    public static function parse_number($input, $precision = _TB_PRICE_DATABASE_PRECISION_): float
    {
        $precision = (int) $precision;
        if (is_null($input)) {
            return 0.0;
        }
        if (is_float($input)) {
            return round($input, $precision);
        }
        if (is_int($input)) {
            return (float) $input;
        }
        if (is_numeric($input)) {
            return round((float) $input, $precision);
        }
        if (is_string($input) && $input !== '') {
            // remove everything except numbers and separators
            $s = preg_replace("/[^0-9.,']/", '', $input);
            if ($s !== '') {
                // if the string contains only numbers, it's integer
                if (preg_match('/^[0-9]$/', (string) $s)) {
                    return round((float) $s, $precision);
                }
                // if the number contains one separator, it will be considered decimal point
                if (preg_match("/^([0-9])*([,.'])?([0-9])*\$/", (string) $s)) {
                    $s = preg_replace("/[,']/", '.', (string) $s);
                    return round((float) $s, $precision);
                }
                // find out all separators
                preg_match_all('/[^0-9]/', (string) $s, $matches);
                $separators = $matches[0] ?? [];
                $unique = array_count_values($separators);
                // if there is only unique separator, it s considered thousand separator.
                if (count($unique) == 1) {
                    $s = preg_replace("/[,'.]/", '', (string) $s);
                    return round((float) $s, $precision);
                }
                if (count($unique) == 2) {
                    $decimal_separator = array_pop($separators);
                    if ($unique[$decimal_separator] === 1) {
                        foreach ($unique as $key => $_) {
                            if ($key !== $decimal_separator) {
                                $s = str_replace($key, '', $s);
                            }
                        }
                        if ($decimal_separator !== '.') {
                            $s = str_replace($decimal_separator, '.', $s);
                        }
                        return round((float) $s, $precision);
                    }
                    // the decimal separator is used multiple times, invalid input. ie: 1.100,2000.123
                    return 0.0;
                }
                // there are more than 2 separators, that is not a valid input
                return 0.0;
            }
        }
        return 0.0;
    }
    /**
     * Round input price value
     *
     * This method expects input type to be either float of int. If different input is provided,
     * the function will raise warning notice, and fallback static::parseNumber() implementation
     * In future versions, the notice will not be raised, and this method will throw instead.
     *
     * @param float|int $input Input value
     * @return float
     */
    public static function round_price($input)
    {
        if (is_null($input)) {
            return 0.0;
        }
        if (is_float($input) || is_int($input)) {
            return round((float) $input, _TB_PRICE_DATABASE_PRECISION_);
        }
        trigger_error('Tools::roundPrice was called with invalid input of type ' . gettype($input));
        return static::parse_number($input);
    }
    /**
     * Returns next available reference for a product attribute
     *
     * Uses the following format for the reference: {$base_reference}_{$next_available_number}
     * and checks whether generated reference number is used for any product or product attribute.
     *
     * @param string $baseReference
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function next_available_reference(?string $base_reference): string
    {
        if ($base_reference) {
            return $base_reference . '_' . static::next_available_reference_counter($base_reference);
        }
        return '';
    }
    /**
     * Returns next available reference counter for a product attribute
     *
     * @param string $baseReference
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function next_available_reference_counter($base_reference): int|float
    {
        if (!$base_reference) {
            return 1;
        }
        $product_refs = (new Db_Query())->select('DISTINCT p.reference')->from('product', 'p')->where('p.reference LIKE "' . p_sql($base_reference) . '\_%"');
        $attribute_refs = (new Db_Query())->select('DISTINCT pa.reference')->from('product_attribute', 'pa')->where('pa.reference LIKE "' . p_sql($base_reference) . '\_%"');
        $sql = $product_refs . ' UNION ' . $attribute_refs;
        $max = 0;
        $rows = Db::read_only()->get_array($sql);
        if ($rows) {
            foreach ($rows as $row) {
                if (preg_match('/^' . preg_quote($base_reference) . '_([0-9]+)$/', (string) $row['reference'], $matches)) {
                    $id = (int) $matches[1];
                    $max = max($id, $max);
                }
            }
        }
        return $max + 1;
    }
    /**
     * Helper method that resolves language for error messages
     *
     * @param Context $context
     * @return string
     */
    protected static function resolve_error_language($context)
    {
        // use language from context, if set up
        if (isset($context->language) && $context->language->iso_code) {
            return $context->language->iso_code;
        }
        // use default store language
        if (Configuration::configuration_is_loaded()) {
            try {
                $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
                if ($default_lang) {
                    $default_iso_code = Language::get_iso_by_id($default_lang);
                    if ($default_iso_code) {
                        return $default_iso_code;
                    }
                }
            } catch (Throwable) {
            }
        }
        // fallback to english
        return 'en';
    }
    /**
     * strftime function polyfill
     *
     * in PHP 9 strftime function will be removed. This method exists as a replacement
     *
     * It requires 'intl' php extension
     *
     * @param int|DateTime|null $timestamp
     * @param string|null $locale
     * @throws PrestaShopException
     */
    public static function strftime(string $format, $timestamp = null, $locale = null): string
    {
        if (!extension_loaded('intl')) {
            $error_message = Tools::display_error("PHP extension 'intl' is not loaded. This is needed for strftime polyfill");
            if (function_exists('strftime')) {
                trigger_error($error_message, E_USER_WARNING);
                return strftime($format, $timestamp);
            }
            throw new Presta_Shop_Exception($error_message);
        }
        if (is_null($timestamp)) {
            $timestamp = new DateTime();
        } elseif (is_numeric($timestamp)) {
            $timestamp = date_create('@' . $timestamp);
            if ($timestamp) {
                try {
                    $timestamp->set_timezone(new DateTimeZone(date_default_timezone_get()));
                } catch (Exception $e) {
                    throw new Presta_Shop_Exception('Failed to resolve timezone', 0, $e);
                }
            }
        } elseif (is_string($timestamp)) {
            $timestamp = date_create($timestamp);
        }
        if (!$timestamp instanceof DateTimeInterface) {
            throw new InvalidArgumentException('$timestamp argument is neither a valid UNIX timestamp, a valid date-time string or a DateTime object.');
        }
        if (is_null($locale)) {
            $locale = strtolower(Configuration::get('PS_LOCALE_LANGUAGE')) . '-' . strtoupper(Configuration::get('PS_LOCALE_COUNTRY'));
        }
        $locale = substr((string) $locale, 0, 5);
        $intl_formatter = function (DateTimeInterface $timestamp, string $format) use ($locale): string|false {
            $intl_formats = ['%a' => 'EEE', '%A' => 'EEEE', '%b' => 'MMM', '%B' => 'MMMM', '%h' => 'MMM'];
            $time_zone = $timestamp->get_timezone();
            $date_type = Intl_Date_Formatter::FULL;
            $time_type = Intl_Date_Formatter::FULL;
            $pattern = '';
            if ($format == '%c') {
                $date_type = Intl_Date_Formatter::LONG;
                $time_type = Intl_Date_Formatter::SHORT;
            } elseif ($format == '%x') {
                $date_type = Intl_Date_Formatter::SHORT;
                $time_type = Intl_Date_Formatter::NONE;
            } elseif ($format == '%X') {
                $date_type = Intl_Date_Formatter::NONE;
                $time_type = Intl_Date_Formatter::MEDIUM;
            } elseif (isset($intl_formats[$format])) {
                $pattern = $intl_formats[$format];
            }
            return (new Intl_Date_Formatter($locale, $date_type, $time_type, $time_zone, null, $pattern))->format($timestamp);
        };
        $translation_table = ['%a' => $intl_formatter, '%A' => $intl_formatter, '%d' => 'd', '%e' => fn($timestamp) => sprintf('% 2u', $timestamp->format('j')), '%j' => fn($timestamp) => sprintf('%03d', $timestamp->format('z') + 1), '%u' => 'N', '%w' => 'w', '%U' => function ($timestamp): string {
            $day = new DateTime(sprintf('%d-01 Sunday', $timestamp->format('Y')));
            return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
        }, '%V' => 'W', '%W' => function ($timestamp): string {
            $day = new DateTime(sprintf('%d-01 Monday', $timestamp->format('Y')));
            return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
        }, '%b' => $intl_formatter, '%B' => $intl_formatter, '%h' => $intl_formatter, '%m' => 'm', '%C' => fn($timestamp) => floor($timestamp->format('Y') / 100), '%g' => fn($timestamp) => substr((string) $timestamp->format('o'), -2), '%G' => 'o', '%y' => 'y', '%Y' => 'Y', '%H' => 'H', '%k' => fn($timestamp) => sprintf('% 2u', $timestamp->format('G')), '%I' => 'h', '%l' => fn($timestamp) => sprintf('% 2u', $timestamp->format('g')), '%M' => 'i', '%p' => 'A', '%P' => 'a', '%r' => 'h:i:s A', '%R' => 'H:i', '%S' => 's', '%T' => 'H:i:s', '%X' => $intl_formatter, '%z' => 'O', '%Z' => 'T', '%c' => $intl_formatter, '%D' => 'm/d/Y', '%F' => 'Y-m-d', '%s' => 'U', '%x' => $intl_formatter];
        $out = preg_replace_callback('/(?<!%)(%[a-zA-Z])/', function (array $match) use ($translation_table, $timestamp) {
            if ($match[1] == '%n') {
                return "\n";
            }
            if ($match[1] == '%t') {
                return "\t";
            }
            if (!isset($translation_table[$match[1]])) {
                throw new InvalidArgumentException(sprintf('Format "%s" is unknown in time format', $match[1]));
            }
            $replace = $translation_table[$match[1]];
            if (is_string($replace)) {
                return $timestamp->format($replace);
            }
            return $replace($timestamp, $match[1]);
        }, $format);
        return str_replace('%%', '%', $out);
    }
    /**
     * Method to decode PHP upload file error code to error message
     *
     * @param int $error
     *
     * @return false|string
     */
    public static function decode_upload_error($error)
    {
        $error = (int) $error;
        if (!$error) {
            return false;
        }
        switch ($error) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $limit = floor(static::get_max_upload_size() / (1024 * 1024));
                return sprintf(static::display_error('File is too large. Upload limit is set to %s MB.'), $limit);
            case UPLOAD_ERR_PARTIAL:
                return static::display_error('The uploaded file was only partially uploaded.');
            case UPLOAD_ERR_NO_FILE:
                return static::display_error('No file was uploaded.');
            case UPLOAD_ERR_NO_TMP_DIR:
                return static::display_error('Missing a temporary folder.');
            case UPLOAD_ERR_CANT_WRITE:
                return static::display_error('Failed to write file to disk.');
            case UPLOAD_ERR_EXTENSION:
                return static::display_error('A PHP extension stopped the file upload.');
            default:
                return sprintf(static::display_error('Error while uploading image; please change your server\'s settings. (Error code: %s)'), $error);
        }
    }
    /**
     * Returns HTTP_REFERER server information
     */
    public static function get_http_referer(): string
    {
        if (isset($_SERVER['HTTP_REFERER'])) {
            return (string) $_SERVER['HTTP_REFERER'];
        }
        return '';
    }
    /**
     * Function called by controllers / helpers to parse form input values
     *
     * @param string $cast
     * @param mixed $input
     *
     * @return mixed
     */
    public static function cast_input($cast, $input)
    {
        if ($cast) {
            // this allows us to override build-in casts ('stringval', 'intval')
            // or define new cast types without polluting global namespace 'priceval'
            $method = 'cast' . ucfirst($cast);
            if (method_exists(static::class, $method)) {
                return static::$method($input);
            }
            if (is_callable($cast)) {
                return $cast($input);
            }
            trigger_error(sprintf('Unknown cast type "%s"', $cast), E_USER_NOTICE);
        }
        return $input;
    }
    /**
     * Cast function for prices
     *
     * @param mixed $input
     *
     * @return float
     */
    public static function cast_priceval($input)
    {
        return static::parse_number($input);
    }
    public static function get_request_method(): string
    {
        if (static::is_phpcli()) {
            return 'CLI';
        }
        if (isset($_SERVER['REQUEST_METHOD'])) {
            return strtoupper((string) $_SERVER['REQUEST_METHOD']);
        }
        return 'GET';
    }
    public static function is_crawler(): bool
    {
        static $crawler = null;
        if (is_null($crawler)) {
            $crawler = false;
            try {
                $responses = Hook::get_responses('actionDetectBot');
                foreach ($responses as $response) {
                    if ($response) {
                        $crawler = true;
                    }
                }
            } catch (Throwable $e) {
                $error_handler = Service_Locator::get_instance()->get_error_handler();
                $error_handler->log_fatal_error(Error_Utils::describe_exception($e));
            }
        }
        return $crawler;
    }
    /**
     * @return string[]
     * @throws PrestaShopException
     */
    public static function get_maintenance_ip_addresses(): array
    {
        $ips = explode(',', (string) Configuration::get_global_value(Configuration::MAINTENANCE_IP_ADDRESSES));
        $ips = array_map(trim(...), $ips);
        $ips = array_filter($ips);
        $ips = array_filter($ips, [Validate::class, 'isIPAddress']);
        sort($ips);
        return array_unique($ips);
    }
}
/**
 * Compare 2 prices to sort products
 *
 * @param array $a
 * @param array $b
 *
 * @return int
 *
 * @deprecated 1.5.0
 */
function cmp_price_asc($a, $b)
{
    Tools::display_as_deprecated('Global function cmpPriceAsc will be removed in next version of thirty bees');
    return Tools::compare_floats($a, $b, 'price_tmp', true);
}
/**
 * @param array $a
 * @param array $b
 *
 * @return int
 *
 * @deprecated 1.5.0
 */
function cmp_price_desc($a, $b)
{
    Tools::display_as_deprecated('Global function cmpPriceDesc will be removed in next version of thirty bees');
    return Tools::compare_floats($a, $b, 'price_tmp', false);
}