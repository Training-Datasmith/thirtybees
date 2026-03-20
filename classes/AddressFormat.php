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
 * Class AddressFormatCore
 */
class Address_Format_Core extends Object_Model
{
    /** @var int */
    public $id_address_format;
    /** @var int */
    public $id_country;
    /** @var string */
    public $format;
    /** @var array $_errorFormatList */
    protected $_error_format_list = [];
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'address_format', 'primary' => 'id_country', 'autoIncrement' => false, 'fields' => ['format' => ['type' => self::TYPE_HTML, 'validate' => 'isGenericName', 'required' => true, 'dbDefault' => ''], 'id_country' => ['type' => self::TYPE_INT]]];
    /**
     * @var string[]
     */
    public static $require_form_fields_list = ['firstname', 'lastname', 'address1', 'city', 'Country:name'];
    /**
     * @var string[]
     */
    public static $forbidden_property_list = ['deleted', 'date_add', 'alias', 'secure_key', 'note', 'newsletter', 'ip_registration_newsletter', 'newsletter_date_add', 'optin', 'passwd', 'last_passwd_gen', 'active', 'is_guest', 'date_upd', 'country', 'years', 'days', 'months', 'description', 'meta_description', 'short_description', 'link_rewrite', 'meta_title', 'meta_keywords', 'display_tax_label', 'need_zip_code', 'contains_states', 'call_prefixes', 'show_public_prices', 'max_payment', 'max_payment_days', 'logged', 'account_number', 'groupBox', 'ape', 'max_payment', 'outstanding_allow_amount', 'call_prefix', 'definition', 'debug_list'];
    /**
     * @var string[]
     */
    public static $forbidden_class_list = ['Manufacturer', 'Supplier'];
    public const _CLEANING_REGEX_ = '#([^\w:_]+)#i';
    /**
     * Check if the the association of the field name and a class name
     * is valide
     *
     * @param string $className is the name class
     * @param string $fieldName is a property name
     * @param bool $isIdField to know if we have to allowed a property name started by 'id_'
     *
     * @return bool
     */
    protected function _check_validate_class_field($class_name, $field_name, $is_id_field)
    {
        $is_valide = false;
        if (!class_exists($class_name)) {
            $this->_error_format_list[] = Tools::display_error('This class name does not exist.') . ': ' . $class_name;
        } else {
            $obj = new $class_name();
            $reflect = new Reflection_Object($obj);
            // Check if the property is accessible
            $public_properties = $reflect->get_properties(ReflectionProperty::IS_PUBLIC);
            foreach ($public_properties as $property) {
                $property_name = $property->get_name();
                if ($property_name == $field_name && ($is_id_field || !preg_match('/\bid\b|id_\w+|\bid[A-Z]\w+/', $property_name))) {
                    $is_valide = true;
                }
            }
            if (!$is_valide) {
                $this->_error_format_list[] = Tools::display_error('This property does not exist in the class or is forbidden.') . ': ' . $class_name . ': ' . $field_name;
            }
            unset($obj);
            unset($reflect);
        }
        return $is_valide;
    }
    /**
     * Verify the existence of a field name and check the availability
     * of an association between a field name and a class (ClassName:fieldName)
     * if the separator is overview
     *
     * @param string $patternName is the composition of the class and field name
     */
    protected function _check_liable_association($pattern_name)
    {
        $pattern_name = trim($pattern_name);
        if ($association_name = explode(':', $pattern_name)) {
            $total_name_used = count($association_name);
            if ($total_name_used > 2) {
                $this->_error_format_list[] = Tools::display_error('This association has too many elements.');
            } elseif ($total_name_used == 1) {
                $association_name[0] = strtolower($association_name[0]);
                if (in_array($association_name[0], static::$forbidden_property_list) || !$this->_check_validate_class_field('Address', $association_name[0], false)) {
                    $this->_error_format_list[] = Tools::display_error('This name is not allowed.') . ': ' . $association_name[0];
                }
            } elseif ($total_name_used == 2) {
                if (empty($association_name[0]) || empty($association_name[1])) {
                    $this->_error_format_list[] = Tools::display_error('Syntax error with this pattern.') . ': ' . $pattern_name;
                } else {
                    $association_name[0] = ucfirst($association_name[0]);
                    $association_name[1] = strtolower($association_name[1]);
                    if (in_array($association_name[0], static::$forbidden_class_list)) {
                        $this->_error_format_list[] = Tools::display_error('This name is not allowed.') . ': ' . $association_name[0];
                    } else {
                        // Check if the id field name exist in the Address class
                        // Don't check this attribute on Address (no sense)
                        if ($association_name[0] != 'Address') {
                            $this->_check_validate_class_field('Address', 'id_' . strtolower($association_name[0]), true);
                        }
                        // Check if the field name exist in the class write by the user
                        $this->_check_validate_class_field($association_name[0], $association_name[1], false);
                    }
                }
            }
        }
    }
    /**
     * Check if the set fields are valid
     *
     * @return bool
     */
    public function check_format_fields()
    {
        $this->_error_format_list = [];
        $used_key_list = [];
        $multiple_line_fields = explode("\n", $this->format);
        if ($multiple_line_fields && is_array($multiple_line_fields)) {
            foreach ($multiple_line_fields as $line_field) {
                if (!$patterns_name = preg_split(static::_CLEANING_REGEX_, $line_field, -1, PREG_SPLIT_NO_EMPTY)) {
                    continue;
                }
                if (!is_array($patterns_name)) {
                    continue;
                }
                foreach ($patterns_name as $pattern_name) {
                    if (!in_array($pattern_name, $used_key_list)) {
                        $this->_check_liable_association($pattern_name);
                        $used_key_list[] = $pattern_name;
                    } else {
                        $this->_error_format_list[] = Tools::display_error('This key has already been used.') . ': ' . $pattern_name;
                    }
                }
            }
        }
        return !count($this->_error_format_list);
    }
    /**
     * @return array
     */
    public function get_error_list()
    {
        return $this->_error_format_list;
    }
    /**
     * Set the layout key with the liable value
     *  example : (firstname) => 'Presta' will result (Presta)
     *         : (firstname-lastname) => 'Presta' and 'Shop' result '(Presta-Shop)'
     *
     * @param array $formattedValueList
     * @param string $currentLine
     * @param string[] $currentKeyList
     */
    protected static function _set_original_display_format(&$formatted_value_list, $current_line, $current_key_list)
    {
        if ($current_key_list && is_array($current_key_list)) {
            if ($original_formatted_pattern_list = explode(' ', $current_line)) {
                // Foreach the available pattern
                foreach ($original_formatted_pattern_list as $pattern_num => $pattern) {
                    // Var allows to modify the good formatted key value when multiple key exist into the same pattern
                    $main_formatted_key = '';
                    // Multiple key can be found in the same pattern
                    foreach ($current_key_list as $key) {
                        // Check if we need to use an older modified pattern if a key has already be matched before
                        $replaced_value = empty($main_formatted_key) ? $pattern : $formatted_value_list[$main_formatted_key];
                        $chars = $start = $end = str_replace($key, '', $replaced_value);
                        if (preg_match(static::_CLEANING_REGEX_, $chars)) {
                            if (mb_substr((string) $replaced_value, 0, mb_strlen($chars)) == $chars) {
                                $end = '';
                            } else {
                                $start = '';
                            }
                            if ($chars) {
                                $replaced_value = str_replace($chars, '', $replaced_value);
                            }
                        }
                        if ($formatted_value = preg_replace('/^' . $key . '$/', $formatted_value_list[$key] ?? '', (string) $replaced_value, -1, $count)) {
                            if ($count) {
                                // Allow to check multiple key in the same pattern,
                                if (empty($main_formatted_key)) {
                                    $main_formatted_key = $key;
                                }
                                // Set the pattern value to an empty string if an older key has already been matched before
                                if ($main_formatted_key != $key) {
                                    $formatted_value_list[$key] = '';
                                }
                                // Store the new pattern value
                                $formatted_value_list[$main_formatted_key] = $start . $formatted_value . $end;
                                unset($original_formatted_pattern_list[$pattern_num]);
                            }
                        }
                    }
                }
            }
        }
    }
    /**
     * @param array $orderedAddressField
     */
    public static function clean_ordered_address(&$ordered_address_field): void
    {
        foreach ($ordered_address_field as &$line) {
            $cleaned_line = '';
            if ($key_list = preg_split(static::_CLEANING_REGEX_, (string) $line, -1, PREG_SPLIT_NO_EMPTY)) {
                foreach ($key_list as $key) {
                    $cleaned_line .= $key . ' ';
                }
                $cleaned_line = trim($cleaned_line);
                $line = $cleaned_line;
            }
        }
    }
    /**
     * Returns the formatted fields with associated values
     *
     * @param Address $address
     * @param array $addressFormat
     * @param int|null $idLang
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function get_formatted_address_fields_values($address, $address_format, $id_lang = null)
    {
        if (!$id_lang) {
            $id_lang = Context::get_context()->language->id;
        }
        $tab = [];
        $tempory_object = [];
        // Check if $address exist and it's an instanciate object of Address
        if ($address instanceof Address) {
            if (Module::is_installed('vatnumber') && Module::is_enabled('vatnumber') && file_exists(_PS_MODULE_DIR_ . 'vatnumber/vatnumber.php')) {
                include_once _PS_MODULE_DIR_ . 'vatnumber/vatnumber.php';
                if (method_exists('VatNumber', 'adjustAddressForLayout')) {
                    Vat_Number::adjust_address_for_layout($address);
                }
            }
            foreach ($address_format as $line) {
                if (($key_list = preg_split(static::_CLEANING_REGEX_, (string) $line, -1, PREG_SPLIT_NO_EMPTY)) && is_array($key_list)) {
                    foreach ($key_list as $pattern) {
                        if ($associate_name = explode(':', $pattern)) {
                            $total_name = count($associate_name);
                            if ($total_name == 1 && isset($address->{$associate_name[0]})) {
                                $tab[$associate_name[0]] = $address->{$associate_name[0]};
                            } else {
                                $tab[$pattern] = '';
                                // Check if the property exist in both classes
                                if ($total_name == 2 && class_exists($associate_name[0]) && property_exists($associate_name[0], $associate_name[1]) && property_exists($address, 'id_' . strtolower($associate_name[0]))) {
                                    $id_field_name = 'id_' . strtolower($associate_name[0]);
                                    if (!isset($tempory_object[$associate_name[0]])) {
                                        $tempory_object[$associate_name[0]] = new $associate_name[0]($address->{$id_field_name});
                                    }
                                    if ($tempory_object[$associate_name[0]]) {
                                        $tab[$pattern] = is_array($tempory_object[$associate_name[0]]->{$associate_name[1]}) ? $tempory_object[$associate_name[0]]->{$associate_name[1]}[$id_lang] ?? '' : $tempory_object[$associate_name[0]]->{$associate_name[1]};
                                    }
                                }
                            }
                        }
                    }
                    Address_Format::_set_original_display_format($tab, $line, $key_list);
                }
            }
        }
        Address_Format::clean_ordered_address($address_format);
        return $tab;
    }
    /**
     * Generates the full address text
     *
     * @param array $patternRules A defined rules array to avoid some pattern
     * @param string $newLine A string containing the newLine format
     * @param string $separator A string containing the separator format
     * @param array $style
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function generate_address(Address $address, $pattern_rules = [], $new_line = "\r\n", $separator = ' ', $style = [])
    {
        $address_fields = Address_Format::get_ordered_address_fields($address->id_country);
        $address_formated_values = Address_Format::get_formatted_address_fields_values($address, $address_fields);
        $address_text = '';
        foreach ($address_fields as $line) {
            if ($patterns_list = preg_split(static::_CLEANING_REGEX_, $line, -1, PREG_SPLIT_NO_EMPTY)) {
                $tmp_text = '';
                foreach ($patterns_list as $pattern) {
                    if (!array_key_exists('avoid', $pattern_rules) || is_array($pattern_rules) && !in_array($pattern, $pattern_rules['avoid'])) {
                        $tmp_text .= !empty($address_formated_values[$pattern]) ? (isset($style[$pattern]) ? sprintf($style[$pattern], $address_formated_values[$pattern]) : $address_formated_values[$pattern]) . $separator : '';
                    }
                }
                $tmp_text = trim($tmp_text);
                $address_text .= !empty($tmp_text) ? $tmp_text . $new_line : '';
            }
        }
        $address_text = preg_replace('/' . preg_quote($new_line, '/') . '$/i', '', $address_text);
        return rtrim((string) $address_text, $separator);
    }
    /**
     * @param array $params
     * @param Smarty_Internal_Template $smarty
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function generate_address_smarty($params, $smarty)
    {
        return Address_Format::generate_address($params['address'], $params['patternRules'] ?? [], $params['newLine'] ?? "\r\n", $params['separator'] ?? ' ', $params['style'] ?? []);
    }
    /**
     * Returns selected fields required for an address in an array according to a selection hash
     *
     * @return array String values
     */
    public static function get_validate_fields($class_name)
    {
        $property_list = [];
        if (class_exists($class_name)) {
            $object = new $class_name();
            $reflect = new Reflection_Object($object);
            // Check if the property is accessible
            $public_properties = $reflect->get_properties(ReflectionProperty::IS_PUBLIC);
            foreach ($public_properties as $property) {
                $property_name = $property->get_name();
                if (!in_array($property_name, Address_Format::$forbidden_property_list) && !preg_match('#id|id_\w#', $property_name)) {
                    $property_list[] = $property_name;
                }
            }
            unset($object);
            unset($reflect);
        }
        return $property_list;
    }
    /**
     * @param string $className
     *
     * @return array
     */
    public static function get_liable_class($class_name)
    {
        $object_list = [];
        if (class_exists($class_name)) {
            $object = new $class_name();
            $reflect = new Reflection_Object($object);
            // Get all the name object liable to the Address class
            $public_properties = $reflect->get_properties(ReflectionProperty::IS_PUBLIC);
            foreach ($public_properties as $property) {
                $property_name = $property->get_name();
                if (preg_match('#id_\w#', $property_name) && strlen($property_name) > 3) {
                    $name_object = ucfirst(substr($property_name, 3));
                    if (!in_array($name_object, static::$forbidden_class_list) && class_exists($name_object)) {
                        $object_list[$name_object] = new $name_object();
                    }
                }
            }
            unset($object);
            unset($reflect);
        }
        return $object_list;
    }
    /**
     * Returns address format fields in array by country
     *
     * @param int $idCountry If null using PS_COUNTRY_DEFAULT
     * @param bool $splitAll
     * @param bool $cleaned
     *
     * @return array String field address format
     *
     * @throws PrestaShopException
     */
    public static function get_ordered_address_fields($id_country = 0, $split_all = false, $cleaned = false)
    {
        $out = [];
        $field_set = explode("\n", Address_Format::get_address_country_format($id_country));
        foreach ($field_set as $field_item) {
            if ($split_all) {
                if ($cleaned) {
                    $key_list = preg_split(static::_CLEANING_REGEX_, $field_item, -1, PREG_SPLIT_NO_EMPTY);
                }
                if (isset($key_list)) {
                    foreach ($key_list as $word_item) {
                        $out[] = trim($word_item);
                    }
                }
            } else {
                $out[] = $cleaned ? implode(' ', preg_split(static::_CLEANING_REGEX_, trim($field_item), -1, PREG_SPLIT_NO_EMPTY)) : trim($field_item);
            }
        }
        return $out;
    }
    /**
     * @param Address $address
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function get_formatted_layout_data($address)
    {
        $layout_data = [];
        if ($address instanceof Address) {
            if (Module::is_installed('vatnumber') && Module::is_enabled('vatnumber') && file_exists(_PS_MODULE_DIR_ . 'vatnumber/vatnumber.php')) {
                include_once _PS_MODULE_DIR_ . 'vatnumber/vatnumber.php';
                if (method_exists('VatNumber', 'adjustAddressForLayout')) {
                    Vat_Number::adjust_address_for_layout($address);
                }
            }
            $layout_data['ordered'] = Address_Format::get_ordered_address_fields((int) $address->id_country);
            $layout_data['formated'] = Address_Format::get_formatted_address_fields_values($address, $layout_data['ordered']);
            $layout_data['object'] = [];
            $reflect = new Reflection_Object($address);
            $public_properties = $reflect->get_properties(ReflectionProperty::IS_PUBLIC);
            foreach ($public_properties as $property) {
                if (isset($address->{$property->get_name()})) {
                    $layout_data['object'][$property->get_name()] = $address->{$property->get_name()};
                }
            }
        }
        return $layout_data;
    }
    /**
     * Returns address format by country if not defined using default country
     *
     * @param int $idCountry
     *
     * @return String field address format
     *
     * @throws PrestaShopException
     */
    public static function get_address_country_format($id_country = 0)
    {
        $id_country = (int) $id_country;
        $tmp_obj = new Address_Format();
        $tmp_obj->id_country = $id_country;
        $out = $tmp_obj->get_format($tmp_obj->id_country);
        unset($tmp_obj);
        return $out;
    }
    /**
     * Returns address format by country
     *
     * @param int $idCountry
     *
     * @return String field address format
     *
     * @throws PrestaShopException
     */
    public function get_format($id_country)
    {
        $out = $this->_get_format_db($id_country);
        if (empty($out)) {
            return $this->_get_format_db(Configuration::get('PS_COUNTRY_DEFAULT'));
        }
        return $out;
    }
    /**
     * @param int $idCountry
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    protected function _get_format_db($id_country)
    {
        if (!Cache::is_stored('AddressFormat::_getFormatDB' . $id_country)) {
            $format = Db::read_only()->get_value((new Db_Query())->select('`format`')->from(bq_sql(static::$definition['table']))->where('`id_country` = ' . (int) $id_country));
            $format = trim((string) $format);
            Cache::store('AddressFormat::_getFormatDB' . $id_country, $format);
            return $format;
        }
        return Cache::retrieve('AddressFormat::_getFormatDB' . $id_country);
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_fields_required()
    {
        $address = new Address();
        $required_fields = static::$require_form_fields_list;
        if (Configuration::get('PS_ONE_PHONE_AT_LEAST')) {
            $required_fields[] = 'phone';
            $required_fields[] = 'phone_mobile';
        }
        return array_unique(array_merge($address->get_fields_required_db(), $required_fields));
    }
}