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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */
/**
 * Class CurrencyModuleCore
 */
abstract class Currency_Rate_Module_Core extends Module
{
    /**
     * @param string $baseCurrency Uppercase base currency code
     *                             Only codes that have been added to the
     *                             `supportedCurrencies` array will be called.
     *                             The module will have to accept all currencies
     *                             from that array as a base.
     *
     * @return false Associate array with all supported currency codes as key (uppercase) and the actual
     *                     amounts as values (floats - be as accurate as you like), e.g.:
     *                     ```php
     *                     [
     *                         'EUR' => 1.233434,
     *                         'USD' => 1.343,
     *                     ]
     *                     ```
     *                     Returns `false`  if there were problems with retrieving the exchange rates
     *
     * @deprecated 1.0.1 Sorry, it doesn't work as it should :(
     *             Please avoid!
     */
    public function hook_currency_rates($base_currency)
    {
        return false;
    }
    /**
     * @param array $params It contains the following values:
     *                      - `currencies`: `array` of `string`s
     *                        Uppercase currency codes
     *                        Only codes that have been added to the
     *                        `currencies` array should be filled.
     *                        The module will have to accept all the currencies it provides
     *                        as a base currency, too. So if it provides `EUR` and `USD`, it should be able to calculate
     *                        with both `EUR` or `USD` as a base currency and find the exchange rate for the other.
     *                      - `baseCurrency`: `string`
     *                        Uppercase base currency code
     *
     * @return false|array Associate array with all supported and requested currency codes as key (uppercase) and the actual
     *                     amounts as values (floats - be as accurate as you like), e.g.:
     *                     ```php
     *                     [
     *                         'EUR' => 1.233434,
     *                         'USD' => 1.343,
     *                     ]
     *                     ```
     *                     Sets a currency as `false` if there were problems with retrieving the exchange rates.
     *                     This will cause thirty bees to not further process the currency. As of 1.0.x thirty bees will not request
     *                     other modules to provide the missing rates. This might change in the future.
     */
    abstract public function hook_action_retrieve_currency_rates($params);
    /**
     * @param string $fromCurrency From currency code
     * @param string $toCurrency To currency code
     *
     * @return false
     *
     * @deprecated 1.0.1 Sorry, it doesn't work as it should :(
     *             Please avoid!
     */
    public function hook_rate($from_currency, $to_currency)
    {
        return false;
    }
    /**
     * @return array Supported currencies
     *               An array with uppercase currency codes (ISO 4217)
     */
    abstract public function get_supported_currencies();
    /**
     * Install this module and scan currencies
     *
     * @return bool Indicates whether the module was successfully installed
     * @throws PrestaShopException
     */
    public function install()
    {
        if (!parent::install()) {
            return false;
        }
        static::scan_missing_currency_rate_modules(false, $this->name);
        return true;
    }
    /**
     * Retrieve all currencies that have exchange rate modules available
     *
     * @param bool $registeredOnly Show currencies with registered services only
     * @param bool $codesOnly Return codes only
     *
     * @return array|false Array with currency iso code as key and module instance as value
     * @throws PrestaShopException
     */
    public static function get_currency_rate_info($registered_only = false, $codes_only = false)
    {
        if ($registered_only) {
            $sql = new Db_Query();
            $sql->select('`id_currency`, `id_module`');
            $sql->from('currency_module');
        } else {
            $sql = new Db_Query();
            $sql->select('c.`id_currency`, cm.`id_module`');
            $sql->from('currency', 'c');
            $sql->left_join('currency_module', 'cm', 'cm.`id_currency` = c.`id_currency`');
            $sql->where('c.`deleted` = 0');
        }
        $results = Db::read_only()->get_array($sql);
        if (!$results) {
            return false;
        }
        $default_currency = Currency::get_default_currency();
        if (!$default_currency) {
            return false;
        }
        $return = [];
        foreach ($results as $result) {
            $currency = Currency::get_currency_instance($result['id_currency']);
            $module = Module::get_instance_by_id($result['id_module']);
            if (Validate::is_loaded_object($currency) && Validate::is_loaded_object($module)) {
                if ($codes_only) {
                    $return[mb_strtoupper((string) $currency->iso_code)] = null;
                } else {
                    $return[mb_strtoupper((string) $currency->iso_code)] = $module;
                }
            } elseif (!$registered_only && Validate::is_loaded_object($currency)) {
                $return[mb_strtoupper((string) $currency->iso_code)] = null;
            }
        }
        return $return;
    }
    /**
     * @param bool|string $baseCurrency
     *
     * @return false|array Result
     * @throws PrestaShopException
     */
    public static function scan_missing_currency_rate_modules($base_currency = false, $extra_module = null)
    {
        if (!$base_currency) {
            $default_currency = Currency::get_default_currency();
            if (!Validate::is_loaded_object($default_currency)) {
                return false;
            }
            $base_currency = $default_currency->iso_code;
        }
        if ($extra_module) {
            $extra_module = Module::get_instance_by_name($extra_module);
        }
        $registered_modules = static::get_currency_rate_info();
        foreach ($registered_modules as $currency_code => &$module) {
            if (!Validate::is_loaded_object($module)) {
                $id_currency = Currency::get_id_by_iso_code($currency_code);
                $currency = Currency::get_currency_instance($id_currency);
                if (!Validate::is_loaded_object($currency)) {
                    continue;
                }
                $available_module_name = static::provides_exchange_rate($currency->iso_code, $base_currency, true);
                if (!$available_module_name && Validate::is_loaded_object($extra_module)) {
                    /** @var CurrencyRateModule $extraModule */
                    $provided_currencies = $extra_module->get_supported_currencies();
                    if (in_array($base_currency, $provided_currencies) && in_array($currency_code, $provided_currencies)) {
                        $available_module_name = $extra_module->name;
                    }
                }
                if ($available_module_name) {
                    $available_module = Module::get_instance_by_name($available_module_name);
                    if (Validate::is_loaded_object($available_module)) {
                        $module['id_module'] = $available_module->id;
                        static::set_module($currency->id, $available_module->id);
                    }
                }
            }
        }
        return $registered_modules;
    }
    /**
     * List all installed and active currency rate modules
     *
     * @return array Available modules
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_installed_currency_rate_modules()
    {
        $sql = new Db_Query();
        $sql->select('m.`id_module`, m.`name`');
        $sql->from('module', 'm');
        $sql->left_join('hook_module', 'hm', 'hm.`id_module` = m.`id_module` ' . Shop::add_sql_restriction(false, 'hm'));
        $sql->left_join('hook', 'h', 'hm.`id_hook` = h.`id_hook`');
        $sql->inner_join('module_shop', 'ms', 'm.`id_module` = ms.`id_module`');
        $sql->where('ms.`id_shop` = ' . (int) Context::get_context()->shop->id);
        $sql->where('h.`name` = \'actionRetrieveCurrencyRates\'');
        return Db::read_only()->get_array($sql);
    }
    /**
     * Same as `CurrencyRateModule::getInstalledCurrencyRateModules`
     * but also returns the list of supported currencies by every module
     *
     * @return array Available modules
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_currency_rate_modules()
    {
        $modules = [];
        $installed_modules = static::get_installed_currency_rate_modules();
        foreach ($installed_modules as $module_info) {
            /** @var CurrencyRateModule $module */
            $module = Module::get_instance_by_id($module_info['id_module']);
            if (Validate::is_loaded_object($module)) {
                $modules[$module->name] = $module->get_supported_currencies();
            }
        }
        return $modules;
    }
    /**
     * Get providing modules
     *
     * @param string $to To currency code
     * @param string|null $from From given base currency code
     * @param bool $justOne Search for just one module
     *
     * @return array|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function provides_exchange_rate($to, $from = null, $just_one = false)
    {
        if (!$from) {
            $from_currency = Currency::get_default_currency();
            $from = mb_strtoupper((string) $from_currency->iso_code);
        }
        $modules = static::get_currency_rate_modules();
        if ($just_one) {
            $providing_modules = '';
        } else {
            $providing_modules = [];
        }
        foreach ($modules as $module_name => $supported_currencies) {
            if (in_array(mb_strtoupper($to), $supported_currencies) && in_array($from, $supported_currencies)) {
                if ($just_one) {
                    return $module_name;
                }
                $providing_modules[] = $module_name;
            }
        }
        return $providing_modules;
    }
    /**
     * Get providing modules
     *
     * @param int $idCurrency To currency code
     * @param string $selected Selected module
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_services($id_currency, $selected)
    {
        $currency = new Currency($id_currency);
        $default_currency = Currency::get_default_currency();
        if (!Validate::is_loaded_object($default_currency)) {
            return false;
        }
        if ($currency->iso_code == $default_currency->iso_code) {
            return false;
        }
        $available_services = static::provides_exchange_rate($currency->iso_code, $default_currency->iso_code, false);
        $service_modules = [];
        foreach ($available_services as $service) {
            $module = Module::get_instance_by_name($service);
            if (!Validate::is_loaded_object($module)) {
                continue;
            }
            $service_modules[] = ['id_module' => $module->id, 'name' => $module->name, 'display_name' => $module->display_name, 'selected' => $module->name === $selected];
        }
        return $service_modules;
    }
    /**
     * @param int $idCurrency
     *
     * @return false|null|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function get_module_for_currency($id_currency)
    {
        $sql = new Db_Query();
        $sql->select('`id_module`');
        $sql->from('currency_module');
        $sql->where('`id_currency` = ' . (int) $id_currency);
        return Db::read_only()->get_value($sql);
    }
    /**
     * Set module
     *
     * @param int $idCurrency
     * @param int $idModule
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function set_module($id_currency, $id_module): void
    {
        $conn = Db::get_instance();
        $conn->delete('currency_module', '`id_currency` = ' . (int) $id_currency, 1, false);
        $conn->insert('currency_module', ['id_currency' => (int) $id_currency, 'id_module' => (int) $id_module]);
    }
}