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
 * Class LocalizationPackCore
 */
class Localization_Pack_Core
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $version;
    /**
     * @var string
     */
    protected $iso_code_lang;
    /**
     * @var string
     */
    protected $iso_currency;
    /**
     * @var string[]
     */
    protected $_errors = [];
    /**
     * @param string $file
     * @param array $selection
     * @param bool $installMode
     * @param string|null $isoLocalizationPack
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function load_localisation_pack($file, $selection, $install_mode = false, $iso_localization_pack = null)
    {
        if (!$xml = @simplexml_load_string($file)) {
            return false;
        }
        libxml_clear_errors();
        $main_attributes = $xml->attributes();
        $this->name = (string) $main_attributes['name'];
        $this->version = (string) $main_attributes['version'];
        if ($iso_localization_pack) {
            $id_country = (int) Country::get_by_iso($iso_localization_pack);
            if ($id_country) {
                $country = new Country($id_country);
            }
            if (!$id_country || !isset($country) || !Validate::is_loaded_object($country)) {
                $this->_errors[] = Tools::display_error(sprintf('Cannot load country : %1d', $id_country));
                return false;
            }
            if (!$country->active) {
                $country->active = 1;
                if (!$country->update()) {
                    $this->_errors[] = Tools::display_error(sprintf('Cannot enable the associated country: %1s', $country->name));
                }
            }
        }
        $res = true;
        if (empty($selection)) {
            $res = $this->_install_states($xml);
            $res = $this->_install_taxes($xml) && $res;
            $res = $this->_install_currencies($xml, $install_mode) && $res;
            $res = $this->install_configuration($xml) && $res;
            $res = $this->install_modules($xml) && $res;
            $res = $this->update_default_group_display_method($xml) && $res;
            if (($res || $install_mode) && isset($this->iso_code_lang)) {
                if (!$id_lang = (int) Language::get_id_by_iso($this->iso_code_lang, true)) {
                    $id_lang = 1;
                }
                if (!$install_mode) {
                    Configuration::update_value('PS_LANG_DEFAULT', $id_lang);
                }
            } elseif (!isset($this->iso_code_lang) && $install_mode) {
                $id_lang = 1;
            }
            if (!Language::is_installed(Language::get_iso_by_id($id_lang))) {
                $res = $this->_install_languages($xml, $install_mode) && $res;
                $res = $this->_install_units($xml) && $res;
            }
            if ($install_mode && $res && isset($this->iso_currency)) {
                Cache::clean('Currency::getIdByIsoCode_*');
                $res = Configuration::update_value('PS_CURRENCY_DEFAULT', (int) Currency::get_id_by_iso_code($this->iso_currency));
                Currency::refresh_currencies();
            }
        } else {
            foreach ($selection as $selected) {
                // No need to specify the install_mode because if the selection mode is used, then it's not the install
                $res = Validate::is_localization_pack_selection($selected) && $this->{'_install' . $selected}($xml) && $res;
            }
        }
        return $res;
    }
    /**
     * @param SimpleXMLElement $xml
     *
     * @throws PrestaShopException
     */
    protected function _install_states($xml): bool
    {
        if (isset($xml->states->state)) {
            foreach ($xml->states->state as $data) {
                /** @var SimpleXMLElement $data */
                $attributes = $data->attributes();
                $id_country = $attributes['country'] ? (int) Country::get_by_iso(strval($attributes['country'])) : false;
                $id_state = $id_country ? State::get_id_by_iso($attributes['iso_code'], $id_country) : State::get_id_by_name($attributes['name']);
                if (!$id_state) {
                    $state = new State();
                    $state->name = strval($attributes['name']);
                    $state->iso_code = strval($attributes['iso_code']);
                    $state->id_country = $id_country;
                    $id_zone = (int) Zone::get_id_by_name(strval($attributes['zone']));
                    if (!$id_zone) {
                        $zone = new Zone();
                        $zone->name = (string) $attributes['zone'];
                        $zone->active = true;
                        if (!$zone->add()) {
                            $this->_errors[] = Tools::display_error('Invalid Zone name.');
                            return false;
                        }
                        $id_zone = $zone->id;
                    }
                    $state->id_zone = $id_zone;
                    if (!$state->validate_fields()) {
                        $this->_errors[] = Tools::display_error('Invalid state properties.');
                        return false;
                    }
                    $country = new Country($state->id_country);
                    if (!$country->contains_states) {
                        $country->contains_states = 1;
                        if (!$country->update()) {
                            $this->_errors[] = Tools::display_error('Cannot update the associated country: ') . $country->name;
                        }
                    }
                    if (!$state->add()) {
                        $this->_errors[] = Tools::display_error('An error occurred while adding the state.');
                        return false;
                    }
                } else {
                    $state = new State($id_state);
                    if (!Validate::is_loaded_object($state)) {
                        $this->_errors[] = Tools::display_error('An error occurred while fetching the state.');
                        return false;
                    }
                }
            }
        }
        return true;
    }
    /**
     * @param SimpleXMLElement $xml
     *
     * @throws PrestaShopException
     */
    protected function _install_taxes($xml): bool
    {
        if (isset($xml->taxes->tax)) {
            $assoc_taxes = [];
            foreach ($xml->taxes->tax as $tax_data) {
                /** @var SimpleXMLElement $taxData */
                $attributes = $tax_data->attributes();
                if ($id_tax = Tax::get_tax_id_by_name($attributes['name'])) {
                    $assoc_taxes[(int) $attributes['id']] = $id_tax;
                    continue;
                }
                $tax = new Tax();
                $tax->name[(int) Configuration::get('PS_LANG_DEFAULT')] = (string) $attributes['name'];
                $tax->rate = (float) $attributes['rate'];
                $tax->active = 1;
                if (($error = $tax->validate_fields(false, true)) !== true || ($error = $tax->validate_fields_lang(false, true)) !== true) {
                    $this->_errors[] = Tools::display_error('Invalid tax properties.') . ' ' . $error;
                    return false;
                }
                if (!$tax->add()) {
                    $this->_errors[] = Tools::display_error('An error occurred while importing the tax: ') . $attributes['name'];
                    return false;
                }
                $assoc_taxes[(int) $attributes['id']] = $tax->id;
            }
            foreach ($xml->taxes->tax_rules_group as $group) {
                /** @var SimpleXMLElement $group */
                $group_attributes = $group->attributes();
                if (!Validate::is_generic_name($group_attributes['name'])) {
                    continue;
                }
                if (Tax_Rules_Group::get_id_by_name($group['name'])) {
                    continue;
                }
                $trg = new Tax_Rules_Group();
                $trg->name = $group['name'];
                $trg->active = 1;
                if (!$trg->save()) {
                    $this->_errors[] = Tools::display_error('This tax rule cannot be saved.');
                    return false;
                }
                foreach ($group->tax_rule as $rule) {
                    /** @var SimpleXMLElement $rule */
                    $rule_attributes = $rule->attributes();
                    // Validation
                    if (!isset($rule_attributes['iso_code_country'])) {
                        continue;
                    }
                    $id_country = (int) Country::get_by_iso(strtoupper($rule_attributes['iso_code_country']));
                    if (!$id_country) {
                        continue;
                    }
                    if (!isset($rule_attributes['id_tax'])) {
                        continue;
                    }
                    if (!array_key_exists(strval($rule_attributes['id_tax']), $assoc_taxes)) {
                        continue;
                    }
                    // Default values
                    $id_state = (int) isset($rule_attributes['iso_code_state']) ? State::get_id_by_iso(strtoupper($rule_attributes['iso_code_state'])) : 0;
                    $zipcode_from = 0;
                    $zipcode_to = 0;
                    $behavior = $rule_attributes['behavior'];
                    if (isset($rule_attributes['zipcode_from'])) {
                        $zipcode_from = $rule_attributes['zipcode_from'];
                        if (isset($rule_attributes['zipcode_to'])) {
                            $zipcode_to = $rule_attributes['zipcode_to'];
                        }
                    }
                    // Creation
                    $tr = new Tax_Rule();
                    $tr->id_tax_rules_group = $trg->id;
                    $tr->id_country = $id_country;
                    $tr->id_state = $id_state;
                    $tr->zipcode_from = $zipcode_from;
                    $tr->zipcode_to = $zipcode_to;
                    $tr->behavior = $behavior;
                    $tr->description = '';
                    $tr->id_tax = $assoc_taxes[strval($rule_attributes['id_tax'])];
                    $tr->save();
                }
            }
        }
        return true;
    }
    /**
     * @param SimpleXMLElement $xml
     * @param bool $installMode
     *
     * @throws PrestaShopException
     */
    protected function _install_currencies($xml, $install_mode = false): bool
    {
        if (isset($xml->currencies->currency)) {
            foreach ($xml->currencies->currency as $data) {
                /** @var SimpleXMLElement $data */
                $attributes = $data->attributes();
                if (Currency::exists($attributes['iso_code'], (int) $attributes['iso_code_num'])) {
                    continue;
                }
                $currency = new Currency();
                $currency->name = (string) $attributes['name'];
                $currency->iso_code = (string) $attributes['iso_code'];
                $currency->iso_code_num = (int) $attributes['iso_code_num'];
                $currency->sign = (string) $attributes['sign'];
                $currency->blank = (int) $attributes['blank'];
                $currency->conversion_rate = 1;
                // This value will be updated if the store is online
                $currency->format = (int) $attributes['format'];
                $currency->decimals = (int) $attributes['decimals'];
                $currency->decimal_places = (int) $attributes['decimal_places'];
                $currency->active = true;
                if (!$currency->validate_fields(false)) {
                    $this->_errors[] = Tools::display_error('Invalid currency properties.');
                    return false;
                }
                if (!Currency::exists($currency->iso_code, $currency->iso_code_num)) {
                    if (!$currency->add()) {
                        $this->_errors[] = Tools::display_error('An error occurred while importing the currency: ') . $attributes['name'];
                        return false;
                    }
                    Payment_Module::add_currency_permissions($currency->id);
                }
            }
            if (($error = Currency::refresh_currencies()) !== null) {
                $this->_errors[] = $error;
            }
            if (!count($this->_errors) && $install_mode && isset($attributes['iso_code']) && count($xml->currencies->currency) == 1) {
                $this->iso_currency = $attributes['iso_code'];
            }
        }
        return true;
    }
    /**
     * Update a configuration variable from a localization file
     * <configuration>
     * <configuration name="variable_name" value="variable_value" />
     *
     * @param SimpleXMLElement $xml
     *
     *
     * @throws PrestaShopException
     */
    protected function install_configuration($xml): bool
    {
        if (isset($xml->configurations)) {
            foreach ($xml->configurations->configuration as $data) {
                /** @var SimpleXMLElement $data */
                $attributes = $data->attributes();
                $name = (string) $attributes['name'];
                if (isset($attributes['value']) && Configuration::get($name) !== false) {
                    if (!Configuration::update_value($name, (string) $attributes['value'])) {
                        $this->_errors[] = Tools::display_error('An error occurred during the configuration setup: ' . $name);
                    }
                }
            }
        }
        return true;
    }
    /**
     * Install/Uninstall a module from a localization file
     * <modules>
     *    <module name="module_name" [install="0|1"] />
     *
     * @param SimpleXMLElement $xml
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function install_modules($xml): bool
    {
        if (isset($xml->modules)) {
            foreach ($xml->modules->module as $data) {
                /** @var SimpleXMLElement $data */
                $attributes = $data->attributes();
                $name = (string) $attributes['name'];
                if ($module = Module::get_instance_by_name($name)) {
                    $install = $attributes['install'] == 1;
                    if ($install) {
                        if (!Module::is_installed($name)) {
                            if (!$module->install()) {
                                $this->_errors[] = Tools::display_error('An error occurred while installing the module:') . $name;
                            }
                        }
                    } elseif (Module::is_installed($name)) {
                        if (!$module->uninstall()) {
                            $this->_errors[] = Tools::display_error('An error occurred while uninstalling the module:') . $name;
                        }
                    }
                    unset($module);
                } else {
                    $this->_errors[] = Tools::display_error('An error has occurred, this module does not exist:') . $name;
                }
            }
        }
        return true;
    }
    /**
     * @param SimpleXMLElement $xml
     *
     *
     * @throws PrestaShopException
     */
    protected function update_default_group_display_method($xml): bool
    {
        if (isset($xml->group_default)) {
            $attributes = $xml->group_default->attributes();
            if (isset($attributes['price_display_method']) && in_array((int) $attributes['price_display_method'], [0, 1])) {
                Configuration::update_value('PRICE_DISPLAY_METHOD', (int) $attributes['price_display_method']);
                foreach ([(int) Configuration::get('PS_CUSTOMER_GROUP'), (int) Configuration::get('PS_GUEST_GROUP'), (int) Configuration::get('PS_UNIDENTIFIED_GROUP')] as $id_group) {
                    $group = new Group($id_group);
                    $group->price_display_method = (int) $attributes['price_display_method'];
                    if (!$group->save()) {
                        $this->_errors[] = Tools::display_error('An error occurred during the default group update');
                    }
                }
            } else {
                $this->_errors[] = Tools::display_error('An error has occurred during the default group update');
            }
        }
        return true;
    }
    /**
     * @param SimpleXMLElement $xml
     * @param bool $installMode
     *
     *
     * @throws PrestaShopException
     */
    protected function _install_languages($xml, $install_mode = false): bool
    {
        $attributes = [];
        if (isset($xml->languages->language)) {
            foreach ($xml->languages->language as $data) {
                $attributes = $data->attributes();
                // if we are not in an installation context or if the pack is not available in the local directory
                if (Language::get_id_by_iso($attributes['iso_code']) && !$install_mode) {
                    continue;
                }
                $errors = Language::download_and_install_language_pack($attributes['iso_code'], $attributes['version'], $attributes);
                if ($errors !== true && is_array($errors)) {
                    $this->_errors = array_merge($this->_errors, $errors);
                }
            }
        }
        // change the default language if there is only one language in the localization pack
        if (!count($this->_errors) && $install_mode && isset($attributes['iso_code']) && count($xml->languages->language) == 1) {
            $this->iso_code_lang = $attributes['iso_code'];
        }
        return !count($this->_errors);
    }
    /**
     * @param SimpleXMLElement $xml
     *
     *
     * @throws PrestaShopException
     */
    protected function _install_units($xml): bool
    {
        $var_names = ['weight' => 'PS_WEIGHT_UNIT', 'volume' => 'PS_VOLUME_UNIT', 'short_distance' => 'PS_DIMENSION_UNIT', 'base_distance' => 'PS_BASE_DISTANCE_UNIT', 'long_distance' => 'PS_DISTANCE_UNIT'];
        if (isset($xml->units->unit)) {
            foreach ($xml->units->unit as $data) {
                /** @var SimpleXMLElement $data */
                $attributes = $data->attributes();
                if (!isset($var_names[strval($attributes['type'])])) {
                    $this->_errors[] = Tools::display_error('Localization pack corrupted: wrong unit type.');
                    return false;
                }
                if (!Configuration::update_value($var_names[strval($attributes['type'])], strval($attributes['value']))) {
                    $this->_errors[] = Tools::display_error('An error occurred while setting the units.');
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * @return array
     */
    public function get_errors()
    {
        return $this->_errors;
    }
    /**
     * @param SimpleXMLElement $xml
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function _install_groups($xml)
    {
        return $this->update_default_group_display_method($xml);
    }
}