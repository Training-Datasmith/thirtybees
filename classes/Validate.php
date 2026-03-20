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
 * Class ValidateCore
 */
class Validate_Core
{
    public const ADMIN_PASSWORD_LENGTH = 8;
    public const PASSWORD_LENGTH = 5;
    public const EMAIL_PATTERN = '/^(?:(?:(?:(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?(?:[a-zA-Z0-9!#\$%&\'\*\+\-\/=\?\^_`\{\}\|~]+(\.[a-zA-Z0-9!#\$%&\'\*\+\-\/=\?\^_`\{\}\|~]+)*)+(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?)|(?:(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?"((?:(?:[ \t]*(?:\r\n))?[ \t])?(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21\x23-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])))*(?:(?:[ \t]*(?:\r\n))?[ \t])?"(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?))@(?:(?:(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?(?:[a-zA-Z0-9!#\$%&\'\*\+\-\/=\?\^_`\{\}\|~]+(\.[a-zA-Z0-9!#\$%&\'\*\+\-\/=\?\^_`\{\}\|~]+)*)+(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?)|(?:(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?\[((?:(?:[ \t]*(?:\r\n))?[ \t])?(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x5A\x5E-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])))*?(?:(?:[ \t]*(?:\r\n))?[ \t])?\](?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))*(?:(?:(?:(?:[ \t]*(?:\r\n))?[ \t])?(\((?:(?:(?:[ \t]*(?:\r\n))?[ \t])|(?:(?:[\x01-\x08\x0B\x0C\x0E-\x19\x7F]|[\x21-\x27\x2A-\x5B\x5D-\x7E])|(?:\\\\[\x00-\x08\x0B\x0C\x0E-\x7F])|(?1)))*(?:(?:[ \t]*(?:\r\n))?[ \t])?\)))|(?:(?:[ \t]*(?:\r\n))?[ \t])))?)))$/D';
    /**
     * @param string $ip
     */
    public static function is_ip2long($ip): bool
    {
        return (bool) preg_match('#^-?[0-9]+$#', (string) $ip);
    }
    /**
     * Validates that input string is valid ipv4 or ipv6 address
     *
     * @param string $ip input string
     *
     * @return bool
     */
    public static function is_ip_address($ip): mixed
    {
        return filter_var($ip, FILTER_VALIDATE_IP);
    }
    public static function is_anything(): bool
    {
        return true;
    }
    /**
     * Check for e-mail validity. See also validate_isEmail() in js/validate.js.
     *
     * @param string $email e-mail address to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_email($email)
    {
        if (!$email) {
            return false;
        }
        // Convert to IDN first if necessary
        if (mb_detect_encoding($email, 'UTF-8', true) && mb_strpos($email, '@') > -1) {
            // Convert to IDN
            [$local, $domain] = explode('@', $email, 2);
            $domain = Tools::utf8to_idn($domain);
            $email = "{$local}@{$domain}";
        }
        return (bool) preg_match(static::EMAIL_PATTERN, $email);
    }
    /**
     * Check for module URL validity
     *
     * @param string $url module URL to validate
     * @param array $errors Reference array for catching errors
     *
     * @return bool Validity is ok or not
     */
    public static function is_module_url($url, &$errors): bool
    {
        if (!$url || $url == 'http://' || $url == 'https://') {
            $errors[] = Tools::display_error('Please specify module URL');
        } elseif (!str_ends_with($url, '.tar') && !str_ends_with($url, '.zip') && !str_ends_with($url, '.tgz') && !str_ends_with($url, '.tar.gz')) {
            $errors[] = Tools::display_error('Unknown archive type');
        } else {
            if (!str_contains($url, 'http')) {
                $url = 'http://' . $url;
            }
            if (!static::is_absolute_url($url)) {
                $errors[] = Tools::display_error('Invalid URL');
            }
        }
        if (!is_array($errors) || !count($errors)) {
            return true;
        }
        return false;
    }
    /**
     * Check for MD5 string validity
     *
     * @param string $md5 MD5 string to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_md5($md5): bool
    {
        return (bool) preg_match('/^[a-f0-9A-F]{32}$/', $md5);
    }
    /**
     * Check for SHA1 string validity
     *
     * @param string $sha1 SHA1 string to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_sha1($sha1): bool
    {
        return (bool) preg_match('/^[a-fA-F0-9]{40}$/', $sha1);
    }
    /**
     * Check for SHA256 string validity
     *
     * @param string $sha265
     */
    public static function is_sha256($sha265): bool
    {
        return (bool) preg_match('/^[a-fA-F0-9]{64}$/', (string) $sha265);
    }
    /**
     * @param float $float
     */
    public static function is_unsigned_float($float): bool
    {
        return strval((float) $float) == strval($float) && $float >= 0;
    }
    /**
     * Check for a float number validity
     *
     * @param float $float Float number to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_opt_float($float): bool
    {
        return empty($float) || static::is_float($float);
    }
    /**
     * Check for a float number validity
     *
     * @param float $float Float number to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_float($float): bool
    {
        return strval((float) $float) == strval($float);
    }
    /**
     * Check for a carrier name validity
     *
     * @param string $name Carrier name to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_carrier_name($name): bool
    {
        return empty($name) || preg_match(Tools::clean_non_unicode_support('/^[^<>;=#{}]*$/u'), $name);
    }
    /**
     * Check for an image size validity
     *
     * @param string $size Image size to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_image_size($size): bool
    {
        return (bool) preg_match('/^[0-9]{1,4}$/', $size);
    }
    /**
     * Check for name validity. This should match validate_isName() in
     * js/validate.js.
     *
     * @param string $name Name to validate
     *
     * @return bool Validity is ok or not
     *
     *                unusual/risky characters.
     */
    public static function is_name($name): bool
    {
        return !preg_match('/www|http/ui', $name) && preg_match(Tools::clean_non_unicode_support('/^[^0-9!\[\]<>;?=+()@#"°{}_$%:\\\\*\^]*$/u'), $name);
    }
    /**
     * Check for hook name validity
     *
     * @param string $hook Hook name to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_hook_name($hook): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9\\\\_-]+$/', $hook);
    }
    /**
     * Check for sender name validity
     *
     * @param string $mailName Sender name to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_mail_name($mail_name): bool
    {
        return is_string($mail_name) && preg_match(Tools::clean_non_unicode_support('/^[^<>;=#{}]*$/u'), $mail_name);
    }
    /**
     * Check for e-mail subject validity
     *
     * @param string $mailSubject e-mail subject to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_mail_subject($mail_subject): bool
    {
        return (bool) preg_match(Tools::clean_non_unicode_support('/^[^<>]*$/u'), $mail_subject);
    }
    /**
     * Check for module name validity
     *
     * @param string $moduleName Module name to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_module_name($module_name): bool
    {
        return is_string($module_name) && preg_match('/^[a-zA-Z0-9_-]+$/', $module_name);
    }
    /**
     * Check for template name validity
     *
     * @param string $tplName Template name to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_tpl_name($tpl_name): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $tpl_name);
    }
    /**
     * Check for image type name validity
     *
     * @param string $type Image type name to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_image_type_name($type): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_ -]+$/', $type);
    }
    /**
     * Check for price validity
     *
     * @param string $price Price to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_price($price): bool
    {
        return (bool) preg_match('/^[0-9]{1,10}(\.[0-9]{1,9})?$/', $price);
    }
    /**
     * Check for price validity (including negative price)
     *
     * @param string $price Price to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_negative_price($price): bool
    {
        return (bool) preg_match('/^[-]?[0-9]{1,10}(\.[0-9]{1,9})?$/', $price);
    }
    /**
     * Check for language code (ISO) validity
     *
     * @param string $isoCode Language code (ISO) to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_language_iso_code($iso_code): bool
    {
        return (bool) preg_match('/^[a-zA-Z]{2,3}$/', $iso_code);
    }
    /**
     * @param string $s
     */
    public static function is_language_code($s): bool
    {
        return (bool) preg_match('/^[a-zA-Z]{2}(-[a-zA-Z]{2})?$/', $s);
    }
    /**
     * @param string $isoCode
     */
    public static function is_state_iso_code($iso_code): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9]{1,4}((-)[a-zA-Z0-9]{1,4})?$/', $iso_code);
    }
    /**
     * @param string $isoCode
     */
    public static function is_numeric_iso_code($iso_code): bool
    {
        return (bool) preg_match('/^[0-9]{2,3}$/', $iso_code);
    }
    /**
     * Check for voucher name validity
     *
     * @param string $voucher voucher to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_discount_name($voucher): bool
    {
        return (bool) preg_match(Tools::clean_non_unicode_support('/^[^!<>,;?()@"°{}_$%:]{3,32}$/u'), $voucher);
    }
    /**
     * Check for product or category name validity
     *
     * @param string $name Product or category name to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_catalog_name($name): bool
    {
        return (bool) preg_match(Tools::clean_non_unicode_support('/^[^<>;{}]*$/u'), $name);
    }
    /**
     * Check for a message validity. This should match validate_isMessage() in
     * js/validate.js.
     *
     * @param string $message Message to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_message($message): bool
    {
        return (bool) preg_match('/^[^<>{}]+$/', $message);
    }
    /**
     * Check for a country name validity
     *
     * @param string $name Country name to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_country_name($name): bool
    {
        return (bool) preg_match('/^[a-zA-Z -]+$/', $name);
    }
    /**
     * Check for a link (url-rewriting only) validity
     *
     * @param string $link Link to validate
     *
     * @return bool Validity is ok or not
     *
     * @throws PrestaShopException
     */
    public static function is_link_rewrite($link): bool
    {
        if (Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL')) {
            return (bool) preg_match(Tools::clean_non_unicode_support('/^[_a-zA-Z0-9\pL\pS-]+$/u'), $link);
        }
        return (bool) preg_match('/^[_a-zA-Z0-9\-]+$/', $link);
    }
    /**
     * Check for a route pattern validity
     *
     * @param string $pattern to validate
     *
     * @return bool Validity is ok or not
     *
     * @throws PrestaShopException
     */
    public static function is_route_pattern($pattern): bool
    {
        if (Configuration::get('PS_ALLOW_ACCENTED_CHARS_URL')) {
            return (bool) preg_match(Tools::clean_non_unicode_support('/^[_a-zA-Z0-9\(\)\.{}:\/\pL\pS-]+$/u'), $pattern);
        }
        return (bool) preg_match('/^[_a-zA-Z0-9\(\)\.{}:\/\-]+$/', $pattern);
    }
    /**
     * Check for a postal address validity. This should match
     * validate_isAddress() in js/validate.js.
     *
     * @param string $address Address to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_address($address): bool
    {
        return empty($address) || preg_match(Tools::clean_non_unicode_support('/^[^!<>?=+@{}_$%]+$/u'), $address);
    }
    /**
     * Check for city name validity. This should match validate_isCityName() in
     * js/validate.js.
     *
     * @param string $city City name to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_city_name($city): bool
    {
        return (bool) preg_match(Tools::clean_non_unicode_support('/^[^!<>;?=+@#"°{}_$%]+$/u'), $city);
    }
    /**
     * Check for search query validity
     *
     * @param string $search Query to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_valid_search($search): bool
    {
        return (bool) preg_match(Tools::clean_non_unicode_support('/^[^<>;=#{}]{0,64}$/u'), $search);
    }
    /**
     * Check for HTML field validity (no XSS please !)
     *
     * @param string|null $html HTML field to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_clean_html($html, $allow_iframe = false): bool
    {
        if (is_null($html)) {
            return true;
        }
        static $forbidden_events = null;
        if (is_null($forbidden_events)) {
            $forbidden_events = implode('|', ['onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onanimationcancel', 'onanimationend', 'onanimationiteration', 'onanimationstart', 'onauxclick', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onbegin', 'onblur', 'onbounce', 'oncanplay', 'oncanplaythrough', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragdrop', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onend', 'onended', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onformchange', 'onforminput', 'ongotpointercapture', 'onhashchange', 'onhelp', 'oninput', 'oninvalid', 'onkeydown', 'onkeypress', 'onkeyup', 'onload', 'onloadeddata', 'onloadedmetadata', 'onloadend', 'onloadstart', 'onlosecapture', 'onlostpointercapture', 'onmessage', 'onmmouseup', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onoffline', 'ononline', 'onorientationchange', 'onpageshow', 'onpaste', 'onpause', 'onplay', 'onplaying', 'onpointercancel', 'onpointerdown', 'onpointerenter', 'onpointerleave', 'onpointermove', 'onpointerout', 'onpointerover', 'onpointerup', 'onpopstate', 'onpropertychange', 'onreadystatechange', 'onrepeat', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onsearch', 'onseeked', 'onseeking', 'onselect', 'onselectionchange', 'onselectstart', 'onshow', 'onstart', 'onstop', 'onsubmit', 'ontimer', 'ontimeupdate', 'ontoggle', 'ontouchcancel', 'ontouchend', 'ontouchmove', 'ontouchstart', 'ontransitioncancel', 'ontransitionend', 'ontransitionrun', 'ontransitionstart', 'ontype', 'onunhandledrejection', 'onunload', 'onvolumechange', 'onwaiting', 'onwheel']);
        }
        if (preg_match('/<[\s]*script/ims', $html) || preg_match('/(' . $forbidden_events . ')[\s]*=/ims', $html) || preg_match('/.*script\:/ims', $html)) {
            return false;
        }
        if (!$allow_iframe && preg_match('/<[\s]*(i?frame|form|input|embed|object)/ims', $html)) {
            return false;
        }
        return true;
    }
    /**
     * Check for product reference validity
     *
     * @param string $reference Product reference to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_reference($reference): bool
    {
        $reference = (string) $reference;
        return mb_strlen($reference) <= Object_Model::SIZE_REFERENCE && preg_match(Tools::clean_non_unicode_support('/^[^<>;={}]*$/u'), $reference);
    }
    /**
     * @param string $plainTextPassword
     *
     * @return bool
     */
    public static function is_passwd_admin($plain_text_password)
    {
        return static::is_passwd($plain_text_password, static::ADMIN_PASSWORD_LENGTH);
    }
    /**
     * Check for password validity. See also validate_isPasswd() in
     * js/validate.js.
     *
     * @param string $plainTextPassword Password to validate
     * @param int $size
     *
     * @return bool Validity is ok or not
     */
    public static function is_passwd($plain_text_password, $size = self::PASSWORD_LENGTH): bool
    {
        return mb_strlen($plain_text_password) >= $size && mb_strlen($plain_text_password) < 255;
    }
    /**
     * Check for configuration key validity
     *
     * @param string $configName Configuration key to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_config_name($config_name): bool
    {
        return (bool) preg_match('/^[a-zA-Z_0-9-]+$/', $config_name);
    }
    /**
     * Check date formats like http://php.net/manual/en/function.date.php
     *
     * @param string $dateFormat date format to check
     *
     * @return bool Validity is ok or not
     */
    public static function is_php_date_format($date_format): bool
    {
        // We can't really check if this is valid or not, because this is a string and you can write whatever you want in it.
        // That's why only < et > are forbidden (HTML)
        return (bool) preg_match('/^[^<>]+$/', $date_format);
    }
    /**
     * Check for date format
     *
     * @param string $date Date to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_date_format($date): bool
    {
        return (bool) preg_match('/^([0-9]{4})-((0?[0-9])|(1[0-2]))-((0?[0-9])|([1-2][0-9])|(3[01]))( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/', $date);
    }
    /**
     * Check for date validity
     *
     * @param string $date Date to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_date($date)
    {
        if (!preg_match('/^([0-9]{4})-((?:0?[0-9])|(?:1[0-2]))-((?:0?[0-9])|(?:[1-2][0-9])|(?:3[01]))( [0-9]{2}:[0-9]{2}:[0-9]{2})?$/', $date, $matches)) {
            return false;
        }
        foreach ([1, 2, 3] as $i) {
            $matches[$i] = (int) $matches[$i];
        }
        return $matches[1] === 0 && $matches[2] === 0 && $matches[3] === 0 || checkdate($matches[2], $matches[3], $matches[1]);
    }
    /**
     * Check for birthDate validity
     *
     * @param string $date birthdate to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_birth_date($date): bool
    {
        if (empty($date) || $date == '0000-00-00') {
            return true;
        }
        if (preg_match('/^([0-9]{4})-((?:0?[1-9])|(?:1[0-2]))-((?:0?[1-9])|(?:[1-2][0-9])|(?:3[01]))([0-9]{2}:[0-9]{2}:[0-9]{2})?$/', $date)) {
            if (date('Y-m-d', strtotime($date)) > date('Y-m-d')) {
                // Reject dates in the future
                return false;
            }
            return true;
        }
        return false;
    }
    /**
     * Check for boolean validity
     *
     * @param bool $bool Boolean to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_bool($bool): bool
    {
        return $bool === null || is_bool($bool) || preg_match('/^(0|1)$/', $bool);
    }
    /**
     * Check for phone number validity
     *
     * @param string $number Phone number to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_phone_number($number): bool
    {
        return (bool) preg_match('/^[+0-9. ()-]+$/', $number);
    }
    /**
     * Check for barcode validity (EAN-13)
     *
     * @param string $ean13 Barcode to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_ean13($ean13): bool
    {
        return !$ean13 || preg_match('/^[0-9]{0,13}$/', $ean13);
    }
    /**
     * Check for barcode validity (UPC)
     *
     * @param string $upc Barcode to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_upc($upc): bool
    {
        return !$upc || preg_match('/^[0-9]{0,12}$/', $upc);
    }
    /**
     * Check for postal code validity. See also validate_isPostCode() in
     * js/validate.js.
     *
     * @param string $postcode Postal code to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_post_code($postcode): bool
    {
        return empty($postcode) || preg_match('/^[a-zA-Z 0-9-]+$/', $postcode);
    }
    /**
     * Check for zip code format validity
     *
     * @param string $zipCode zip code format to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_zip_code_format($zip_code)
    {
        if (!empty($zip_code)) {
            return (bool) preg_match('/^[NLCnlc 0-9-]+$/', $zip_code);
        }
        return true;
    }
    /**
     * Check for table or identifier validity
     * Mostly used in database for ordering : ASC / DESC
     *
     * @param string $way Keyword to validate
     *
     * @return int Validity is ok or not
     */
    public static function is_order_way($way): int
    {
        return $way === 'ASC' | $way === 'DESC' | $way === 'asc' | $way === 'desc';
    }
    /**
     * Check for table or identifier validity
     * Mostly used in database for ordering : ORDER BY field
     *
     * @param string $order Field to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_order_by($order): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9.!_-]+$/', (string) $order);
    }
    /**
     * Check for table or identifier validity
     * Mostly used in database for table names and id_table
     *
     * @param string $table Table/identifier to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_table_or_identifier($table): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_-]+$/', $table);
    }
    /**
     * @deprecated 1.0.0 You should not use list like this, please use an array when you build a SQL query
     */
    public static function is_values_list(): bool
    {
        Tools::display_as_deprecated();
        return true;
        /* For history reason, we keep this line */
        // return preg_match('/^[0-9,\'(). NULL]+$/', $list);
    }
    /**
     * Check for tags list validity
     *
     * @param string $list List to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_tags_list($list): bool
    {
        return (bool) preg_match(Tools::clean_non_unicode_support('/^[^!<>;?=+#"°{}_$%]*$/u'), $list);
    }
    /**
     * Check for product visibility
     *
     * @param string $s visibility to check
     *
     * @return bool Validity is ok or not
     */
    public static function is_product_visibility($s): bool
    {
        return (bool) preg_match('/^both|catalog|search|none$/i', $s);
    }
    /**
     * Check for an integer validity
     *
     * @param int $value Integer to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_int($value): bool
    {
        return (string) (int) $value === (string) $value || $value === false;
    }
    /**
     * Check for an percentage validity (between 0 and 100)
     *
     * @param float $value Float to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_percentage($value): bool
    {
        return static::is_float($value) && $value >= 0 && $value <= 100;
    }
    /**
     * @param int|null $id
     */
    public static function is_null_or_unsigned_id($id): bool
    {
        return $id === null || static::is_unsigned_id($id);
    }
    /**
     * Check for an integer validity (unsigned)
     * Mostly used in database for auto-increment
     *
     * @param int $id Integer to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_unsigned_id($id)
    {
        return static::is_unsigned_int($id);
        /* Because an id could be equal to zero when there is no association */
    }
    /**
     * Check for an integer validity (unsigned)
     *
     * @param int $value Integer to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_unsigned_int($value): bool
    {
        return (string) (int) $value === (string) $value && $value < 4294967296 && $value >= 0;
    }
    /**
     * Check object validity
     *
     * @param ObjectModel|mixed $object Object to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_loaded_object($object): bool
    {
        return is_object($object) && $object->id;
    }
    /**
     * Check object validity
     *
     * @param string $color
     * @return bool Validity is ok or not
     */
    public static function is_color($color): bool
    {
        return (bool) preg_match('/^(#[0-9a-fA-F]{6}|[a-zA-Z0-9-]*)$/', $color);
    }
    /**
     * Check tracking number validity (disallowed empty string)
     *
     * @param string $trackingNumber Tracking number to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_tracking_number($tracking_number): bool
    {
        $tracking_number = (string) $tracking_number;
        return mb_strlen($tracking_number) <= 64 && preg_match('/^[~:#,%&_=\(\)\[\]\.\? \+\-@\/a-zA-Z0-9]+$/', $tracking_number);
    }
    /**
     * Check url validity (allowed empty string)
     *
     * @param string $url Url to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_url_or_empty($url): bool
    {
        return empty($url) || static::is_url($url);
    }
    /**
     * Check url validity (disallowed empty string)
     *
     * @param string $url Url to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_url($url)
    {
        if (!is_string($url)) {
            return false;
        }
        if (!static::is_absolute_url($url)) {
            return (bool) preg_match(Tools::clean_non_unicode_support('/^[~:#,$%&_=\(\)\.\? \+\-@\/a-zA-Z0-9\pL\pS-]+$/u'), $url);
        }
        // Reject dangerous URL schemes (e.g. javascript:, data:, vbscript:)
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https', 'ftp', 'ftps'], true)) {
            return false;
        }
        return true;
    }
    /**
     * Check if URL is absolute
     *
     * @param string $url URL to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_absolute_url($url): bool
    {
        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }
    /**
     * @param string $engine
     */
    public static function is_my_sql_engine($engine): bool
    {
        return in_array($engine, ['InnoDB', 'MyISAM']);
    }
    /**
     * @param string $data
     */
    public static function is_unix_name($data): bool
    {
        return (bool) preg_match(Tools::clean_non_unicode_support('/^[a-z0-9\._-]+$/ui'), $data);
    }
    /**
     * @param string $data
     */
    public static function is_table_prefix($data): bool
    {
        // Even if "-" is theorically allowed, it will be considered a syntax error if you do not add backquotes (`) around the table name
        return (bool) preg_match(Tools::clean_non_unicode_support('/^[a-z0-9_]+$/ui'), $data);
    }
    /**
     * Check for standard name file validity
     *
     * @param string $name Name to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_file_name($name): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_.-]+$/', $name);
    }
    /**
     * Check for standard name directory validity
     *
     * @param string $dir Directory to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_dir_name($dir): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_.-]*$/', $dir);
    }
    /**
     * Check for standard uri path validity
     *
     * @param string $path path to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_uri_path($path)
    {
        if (is_string($path)) {
            return (bool) preg_match('/^[\/a-zA-Z0-9_.~-]*$/', $path);
        }
        return false;
    }
    /**
     * Check for admin panel tab name validity
     *
     * @param string $name Name to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_tab_name($name): bool
    {
        return (bool) preg_match(Tools::clean_non_unicode_support('/^[^<>]+$/u'), $name);
    }
    /**
     * @param string $unit
     */
    public static function is_weight_unit($unit): int
    {
        return static::is_generic_name($unit) & mb_strlen($unit) < 5;
    }
    /**
     * Check for standard name validity. This should match
     * validate_isGenericName() in js/validate.js.
     *
     * @param string $name Name to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_generic_name($name): bool
    {
        return empty($name) || preg_match(Tools::clean_non_unicode_support('/^[^<>={}]*$/u'), $name);
    }
    /**
     * @param string $unit
     */
    public static function is_distance_unit($unit): int
    {
        return static::is_generic_name($unit) & mb_strlen($unit) < 5;
    }
    /**
     * @param string $domain
     */
    public static function is_sub_domain_name($domain): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9-_]*$/', $domain);
    }
    /**
     * @param string $text
     */
    public static function is_voucher_description($text): bool
    {
        return (bool) preg_match('/^([^<>{}]|<br \/>)*$/i', $text);
    }
    /**
     * Check if the value is a sort direction value (DESC/ASC)
     *
     * @param string $value
     *
     * @return bool Validity is ok or not
     */
    public static function is_sort_direction($value): bool
    {
        return $value === 'ASC' || $value === 'DESC';
    }
    /**
     * Customization fields' label validity
     *
     * @param string $label
     *
     * @return bool Validity is ok or not
     */
    public static function is_label($label): int|false
    {
        return preg_match(Tools::clean_non_unicode_support('/^[^{}<>]*$/u'), $label);
    }
    /**
     * Price display method validity
     *
     * @param int $data Data to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_price_display_method($data): bool
    {
        return $data == PS_TAX_EXC || $data == PS_TAX_INC;
    }
    /**
     * @param string $dni to validate
     */
    public static function is_dni_lite($dni): bool
    {
        return empty($dni) || preg_match('/^[0-9A-Za-z-.]{1,16}$/U', $dni);
    }
    /**
     * Check if $data is a PrestaShop cookie object
     *
     * @param mixed $data to validate
     */
    public static function is_cookie($data): bool
    {
        return is_object($data) && $data::class == 'Cookie';
    }
    /**
     * Price display method validity
     *
     * @param string $data Data to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_string($data): bool
    {
        return is_string($data);
    }
    /**
     * Check if the data is a reduction type (amout or percentage)
     *
     * @param string $data Data to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_reduction_type($data): bool
    {
        return $data === 'amount' || $data === 'percentage';
    }
    /**
     * @deprecated 1.0.0 Use static::isBoolId()
     */
    public static function is_bool_id($ids)
    {
        Tools::display_as_deprecated();
        return static::is_bool_id($ids);
    }
    /**
     * Check for bool_id
     *
     * @param string $ids
     *
     * @return bool Validity is ok or not
     */
    public static function is_bool_id($ids): bool
    {
        return (bool) preg_match('#^[01]_[0-9]+$#', $ids);
    }
    /**
     * Check the localization pack part selected
     *
     * @param string $data Localization pack to check
     *
     * @return bool Validity is ok or not
     */
    public static function is_localization_pack_selection($data): bool
    {
        return in_array((string) $data, ['states', 'taxes', 'currencies', 'languages', 'units', 'groups']);
    }
    /**
     * Check for PHP serialized data
     *
     * @param string $data Serialized data to validate
     *
     * @return bool Validity is ok or not
     *
     * @deprecated Use the generally safer JSON format instead of serialize().
     *
     * @deprecated 1.0.5
     */
    public static function is_serialized_array($data): bool
    {
        return $data === null || is_string($data) && preg_match('/^a:[0-9]+:{.*;}$/s', $data);
    }
    /**
     * Check for JSON encoded data.
     *
     * @param string $data JSON encoded data to validate.
     *
     * @return bool Validity is ok or not
     */
    public static function is_json($data): bool
    {
        json_decode($data);
        return json_last_error() === JSON_ERROR_NONE;
    }
    /**
     * Check for Latitude/Longitude
     *
     * @param string $data Coordinate to validate
     *
     * @return bool Validity is ok or not
     */
    public static function is_coordinate($data): bool
    {
        return $data === null || preg_match('/^\-?[0-9]{1,8}\.[0-9]{1,8}$/s', $data);
    }
    /**
     * Check for Language Iso Code
     *
     * @param string $isoCode
     *
     * @return bool Validity is ok or not
     */
    public static function is_lang_iso_code($iso_code): bool
    {
        return (bool) preg_match('/^[a-zA-Z]{2,3}$/s', $iso_code);
    }
    /**
     * Check for Language File Name
     *
     * @param string $fileName
     *
     * @return bool Validity is ok or not
     */
    public static function is_language_file_name($file_name): bool
    {
        return (bool) preg_match('/^[a-zA-Z]{2,3}\.(?:gzip|tar\.gz)$/s', $file_name);
    }
    /**
     * @param array $ids
     *
     * @return bool return true if the array contain only unsigned int value
     */
    public static function is_array_with_ids($ids): bool
    {
        if (count($ids)) {
            foreach ($ids as $id) {
                if ($id == 0 || !static::is_unsigned_int($id)) {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * @param array $zones
     *
     * @return bool return true if array contain all value required for an image map zone
     */
    public static function is_scene_zones($zones): bool
    {
        foreach ($zones as $zone) {
            if (!isset($zone['x1']) || !static::is_unsigned_int($zone['x1'])) {
                return false;
            }
            if (!isset($zone['y1']) || !static::is_unsigned_int($zone['y1'])) {
                return false;
            }
            if (!isset($zone['width']) || !static::is_unsigned_int($zone['width'])) {
                return false;
            }
            if (!isset($zone['height']) || !static::is_unsigned_int($zone['height'])) {
                return false;
            }
            if (!isset($zone['id_product']) || !static::is_unsigned_int($zone['id_product'])) {
                return false;
            }
        }
        return true;
    }
    /**
     * @param array $stock_management
     *
     * @return bool return true if is a valide stock management
     */
    public static function is_stock_management($stock_management): bool
    {
        if (!in_array($stock_management, ['WA', 'FIFO', 'LIFO'])) {
            return false;
        }
        return true;
    }
    /**
     * Validate SIRET Code
     *
     * @param string $siret SIRET Code
     *
     * @return bool Return true if is valid
     */
    public static function is_siret($siret)
    {
        if (mb_strlen($siret) != 14) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i != 14; $i++) {
            $tmp = (($i + 1) % 2 + 1) * intval($siret[$i]);
            if ($tmp >= 10) {
                $tmp -= 9;
            }
            $sum += $tmp;
        }
        return $sum % 10 === 0;
    }
    /**
     * Validate APE Code
     *
     * @param string $ape APE Code
     *
     * @return bool Return true if is valid
     */
    public static function is_ape($ape): bool
    {
        return (bool) preg_match('/^[0-9]{3,4}[a-zA-Z]{1}$/s', $ape);
    }
    /**
     * @param string $name
     */
    public static function is_controller_name($name): bool
    {
        return is_string($name) && preg_match(Tools::clean_non_unicode_support('/^[0-9a-zA-Z-_]*$/u'), $name);
    }
    /**
     * @param string $version
     */
    public static function is_presta_shop_version($version): bool
    {
        return preg_match('/^[0-1]\.[0-9]{1,2}(\.[0-9]{1,2}){0,2}$/', $version) && ip2long($version);
    }
    /**
     * @param int $id
     *
     *
     * @throws PrestaShopException
     */
    public static function is_order_invoice_number($id): bool
    {
        return (bool) preg_match('/^(?:' . Configuration::get('PS_INVOICE_PREFIX', Context::get_context()->language->id) . ')\s*([0-9]+)$/i', $id);
    }
}