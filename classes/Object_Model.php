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
use Thirtybees\Core\Error\Error_Utils;
/**
 * Class ObjectModelCore
 */
abstract class Object_Model_Core implements Core_foundation_database_entity_Interface
{
    /**
     * List of field types
     */
    public const TYPE_INT = 1;
    public const TYPE_BOOL = 2;
    public const TYPE_STRING = 3;
    public const TYPE_FLOAT = 4;
    public const TYPE_DATE = 5;
    public const TYPE_HTML = 6;
    public const TYPE_NOTHING = 7;
    public const TYPE_SQL = 8;
    public const TYPE_PRICE = 9;
    /**
     * List of data to format
     */
    public const FORMAT_COMMON = 1;
    public const FORMAT_LANG = 2;
    public const FORMAT_SHOP = 3;
    /**
     * List of association types
     */
    public const HAS_ONE = 1;
    public const HAS_MANY = 2;
    public const BELONGS_TO_MANY = 3;
    /**
     * List of common database default values
     */
    public const DEFAULT_NULL = '@@NULL';
    public const DEFAULT_CURRENT_TIMESTAMP = 'CURRENT_TIMESTAMP';
    /**
     * List of database column sizes
     */
    public const SIZE_MAX_VARCHAR = 255;
    public const SIZE_MEDIUM_TEXT = 16777215;
    public const SIZE_TEXT = 65535;
    public const SIZE_LONG_TEXT = 4294967295;
    public const SIZE_REFERENCE = 64;
    /**
     * List of different database key types
     */
    public const PRIMARY_KEY = 1;
    public const UNIQUE_KEY = 2;
    public const FOREIGN_KEY = 3;
    public const KEY = 4;
    /** @var int|null Object ID */
    public $id;
    /** @var int|null Language ID */
    public $id_lang;
    /** @var int|null Shop ID */
    public $id_shop;
    /** @var array|null List of shop IDs */
    public $id_shop_list;
    /** @var bool */
    protected $get_shop_from_context = true;
    /** @var array|null Holds required fields for each ObjectModel class */
    protected static $fields_required_database;
    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var string
     */
    protected $table;
    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var string
     */
    protected $identifier;
    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var array
     */
    protected $fields_required = [];
    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var array
     */
    protected $fields_size = [];
    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var array
     */
    protected $fields_validate = [];
    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var array
     */
    protected $fields_required_lang = [];
    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var array
     */
    protected $fields_size_lang = [];
    /**
     * @deprecated 1.0.0 Define property using $definition['table'] property instead.
     * @var array
     */
    protected $fields_validate_lang = [];
    /**
     * @deprecated 1.0.0
     * @var array
     */
    protected $tables = [];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = [];
    /** @var string|null Path to image directory. Used for image deletion. */
    protected $image_dir;
    /** @var String file type of image files. */
    protected $image_format;
    /**
     * @var array Contains object definition
     */
    public static $definition = [];
    /**
     * Holds compiled definitions of each ObjectModel class.
     * Values are assigned during object initialization.
     *
     * @var array
     */
    protected static $loaded_classes = [];
    /** @var array Contains current object definition. */
    protected $def;
    /** @var array|null List of specific fields to update (all fields if null). */
    protected $update_fields;
    /** @var Db An instance of the db in order to avoid calling Db::getInstance() thousands of times. */
    protected static $db = false;
    /** @var bool Enables to define an ID before adding object. */
    public $force_id = false;
    /**
     * @var bool If true, objects are cached in memory.
     */
    protected static $cache_objects = true;
    /**
     * @return string|null
     */
    public static function get_repository_class_name()
    {
        return null;
    }
    /**
     * Returns object validation rules (fields validity)
     *
     * @param string $class Child class name for static use (optional)
     *
     * @return array Validation rules (fields validity)
     */
    public static function get_validation_rules($class = self::class)
    {
        $object = new $class();
        return ['required' => $object->fields_required, 'size' => $object->fields_size, 'validate' => $object->fields_validate, 'requiredLang' => $object->fields_required_lang, 'sizeLang' => $object->fields_size_lang, 'validateLang' => $object->fields_validate_lang];
    }
    /**
     * Builds the object
     *
     * @param int|null $id If specified, loads and existing object from DB (optional).
     * @param int|null $idLang Required if object is multilingual (optional).
     * @param int|null $idShop ID shop for objects with multishop tables.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        $class_name = static::class;
        if (!isset(Object_Model::$loaded_classes[$class_name])) {
            $this->def = Object_Model::get_definition($class_name);
            $this->set_definition_retrocompatibility();
            if (!Validate::is_table_or_identifier($this->def['primary']) || !Validate::is_table_or_identifier($this->def['table'])) {
                throw new Presta_Shop_Exception('Identifier or table format not valid for class ' . $class_name);
            }
            Object_Model::$loaded_classes[$class_name] = get_object_vars($this);
        } else {
            foreach (Object_Model::$loaded_classes[$class_name] as $key => $value) {
                $this->{$key} = $value;
            }
        }
        if ($id_lang !== null) {
            $this->id_lang = Language::get_language($id_lang) !== false ? $id_lang : Configuration::get('PS_LANG_DEFAULT');
        }
        if ($id_shop && $this->is_multishop()) {
            $this->id_shop = (int) $id_shop;
            $this->get_shop_from_context = false;
        }
        if ($this->is_multishop() && !$this->id_shop) {
            $this->id_shop = Context::get_context()->shop->id;
        }
        if ($id) {
            /** @var Adapter_EntityMapper $entityMapper */
            $entity_mapper = Adapter_service_Locator::get('Adapter_EntityMapper');
            $entity_mapper->load($id, $id_lang, $this, $this->def, $this->id_shop, static::$cache_objects);
        }
        $this->image_format = Image_Manager::get_default_image_extension();
    }
    /**
     * thirty bees' new coding style dictates that camelCase should be used
     * rather than snake_case
     * These magic methods provide backwards compatibility for modules/themes/whatevers
     * that still access properties via their snake_case names
     *
     * @param string $property Property name
     */
    public function &__get(string $property): mixed
    {
        // Property to camelCase for backwards compatibility
        $camel_case_property = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));
        if (property_exists($this, $camel_case_property)) {
            return $this->{$camel_case_property};
        }
        return $this->{$property};
    }
    /**
     * thirty bees' new coding style dictates that camelCase should be used
     * rather than snake_case
     * These magic methods provide backwards compatibility for modules/themes/whatevers
     * that still access properties via their snake_case names
     *
     *
     * @return void
     */
    public function __set(string $property, mixed $value)
    {
        // Property to camelCase for backwards compatibility
        $snake_case_property = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));
        if (property_exists($this, $snake_case_property)) {
            $this->{$snake_case_property} = $value;
        } else {
            $this->{$property} = $value;
        }
    }
    /**
     * Prepare fields for ObjectModel class (add, update)
     * All fields are verified (pSQL, intval, ...)
     *
     * @return array All object fields
     * @throws PrestaShopException
     */
    public function get_fields()
    {
        $this->validate_fields();
        $fields = $this->format_fields(static::FORMAT_COMMON);
        // For retro compatibility
        if (Shop::is_table_associated($this->def['table'])) {
            $fields = array_merge($fields, $this->get_fields_shop());
        }
        // Ensure that we get something to insert
        if (!$fields && isset($this->id) && Validate::is_unsigned_id($this->id)) {
            $fields[$this->def['primary']] = $this->id;
        }
        return $fields;
    }
    /**
     * Return fields that are stored in object model primary table
     * Fields are sanitized (pSQL, intval, ...)
     *
     * @return array primary table fields
     * @throws PrestaShopException
     */
    protected function get_fields_primary()
    {
        // although it would be better from performance point of view to build list of primary table fields directly
        // by calling formatFields method, we can't do that.
        // The reason is that some subclasses overridden getFields() method to include additional fields
        $fields = $this->get_fields();
        $definitions = $this->def['fields'];
        foreach ($fields as $field => $value) {
            $shop_only_field = isset($definitions[$field]['shopOnly']) && $definitions[$field]['shopOnly'];
            if ($shop_only_field) {
                unset($fields[$field]);
            }
        }
        return $fields;
    }
    /**
     * Prepare fields for multishop
     * Fields are not validated here, we consider they are already validated in getFields() method,
     * this is not the best solution but this is the only one possible for retro compatibility.
     *
     * @return array All object fields
     *
     * @throws PrestaShopException
     */
    public function get_fields_shop()
    {
        $fields = $this->format_fields(static::FORMAT_SHOP);
        if (!$fields && isset($this->id) && Validate::is_unsigned_id($this->id)) {
            $fields[$this->def['primary']] = $this->id;
        }
        return $fields;
    }
    /**
     * Prepare multilang fields
     *
     * @return array
     * @throws PrestaShopException
     */
    public function get_fields_lang()
    {
        // Backward compatibility
        if (method_exists($this, 'getTranslationsFieldsChild')) {
            return $this->get_translations_fields_child();
        }
        $this->validate_fields_lang();
        $is_lang_multishop = $this->is_lang_multishop();
        $fields = [];
        if ($this->id_lang === null) {
            foreach (Language::get_i_ds(false) as $id_lang) {
                $fields[$id_lang] = $this->format_fields(static::FORMAT_LANG, $id_lang);
                $fields[$id_lang]['id_lang'] = $id_lang;
                if ($this->id_shop && $is_lang_multishop) {
                    $fields[$id_lang]['id_shop'] = (int) $this->id_shop;
                }
            }
        } else {
            $fields = [$this->id_lang => $this->format_fields(static::FORMAT_LANG, $this->id_lang)];
            $fields[$this->id_lang]['id_lang'] = $this->id_lang;
            if ($this->id_shop && $is_lang_multishop) {
                $fields[$this->id_lang]['id_shop'] = (int) $this->id_shop;
            }
        }
        return $fields;
    }
    /**
     * Formats values of each fields.
     *
     * @param int $type FORMAT_COMMON or FORMAT_LANG or FORMAT_SHOP
     * @param int $idLang If this parameter is given, only take lang fields
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    protected function format_fields($type, $id_lang = null)
    {
        $fields = [];
        // Set primary key in fields
        if (isset($this->id)) {
            $fields[$this->def['primary']] = $this->id;
        }
        foreach ($this->def['fields'] as $field => $data) {
            $lang_field = isset($data['lang']) && $data['lang'];
            if ($type == static::FORMAT_LANG && !$lang_field) {
                continue;
            }
            $shop_only_field = isset($data['shopOnly']) && $data['shopOnly'];
            $shop_field = $shop_only_field || isset($data['shop']) && $data['shop'];
            if ($type == static::FORMAT_SHOP && !$shop_field) {
                continue;
            }
            if ($type == static::FORMAT_COMMON && ($shop_only_field || $lang_field)) {
                continue;
            }
            if (is_array($this->update_fields)) {
                if (($lang_field || $shop_field) && (empty($this->update_fields[$field]) || $type == static::FORMAT_LANG && empty($this->update_fields[$field][$id_lang]))) {
                    continue;
                }
            }
            // Get field value, if value is multilang and field is empty, use value from default lang
            $value = $this->{$field};
            if ($type == static::FORMAT_LANG && $id_lang && is_array($value)) {
                if (!empty($value[$id_lang])) {
                    $value = $value[$id_lang];
                } elseif (!empty($data['required'])) {
                    $value = $value[Configuration::get('PS_LANG_DEFAULT')];
                } else {
                    $value = '';
                }
            }
            $purify = isset($data['validate']) && mb_strtolower($data['validate']) == 'iscleanhtml';
            // Format field value
            $fields[$field] = Object_Model::format_value($value, $data['type'], false, $purify, !empty($data['allow_null']));
        }
        return $fields;
    }
    /**
     * Formats a value
     *
     * @param mixed $value
     * @param int $type
     * @param bool $withQuotes
     * @param bool $purify
     * @param bool $allowNull
     *
     * @return int|float|bool|string|array
     *
     * @throws PrestaShopException
     */
    public static function format_value($value, $type, $with_quotes = false, $purify = true, $allow_null = false)
    {
        if ($allow_null && $value === null) {
            return ['type' => 'sql', 'value' => 'NULL'];
        }
        switch ($type) {
            case self::TYPE_INT:
            case self::TYPE_BOOL:
                return (int) $value;
            case self::TYPE_FLOAT:
            case self::TYPE_PRICE:
                return Tools::parse_number($value);
            case self::TYPE_DATE:
                if (!$value) {
                    return '0000-00-00';
                }
                if ($with_quotes) {
                    return '\'' . p_sql($value) . '\'';
                }
                return p_sql($value);
            case self::TYPE_HTML:
                if ($purify) {
                    $value = Tools::purify_html($value);
                }
                if ($with_quotes) {
                    return '\'' . p_sql($value, true) . '\'';
                }
                return p_sql($value, true);
            case self::TYPE_SQL:
                if ($with_quotes) {
                    return '\'' . p_sql($value, true) . '\'';
                }
                return p_sql($value, true);
            case self::TYPE_NOTHING:
                return $value;
            case self::TYPE_STRING:
            default:
                if ($with_quotes) {
                    return '\'' . p_sql($value) . '\'';
                }
                return p_sql($value);
        }
    }
    /**
     * Saves current object to database (add or update)
     *
     * @param bool $nullValues
     * @param bool $autoDate
     *
     * @return bool Insertion result
     * @throws PrestaShopException
     */
    public function save($null_values = false, $auto_date = true)
    {
        return (int) $this->id > 0 ? $this->update($null_values) : $this->add($auto_date, $null_values);
    }
    /**
     * Adds current object to the database
     *
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool Insertion result
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if (isset($this->id) && !$this->force_id) {
            unset($this->id);
        }
        // @hook actionObject*AddBefore
        Hook::trigger_event('actionObjectAddBefore', ['object' => $this]);
        Hook::trigger_event('actionObject' . static::class . 'AddBefore', ['object' => $this]);
        // Automatically fill dates
        if ($auto_date && property_exists($this, 'date_add')) {
            $this->date_add = date('Y-m-d H:i:s');
        }
        if ($auto_date && property_exists($this, 'date_upd')) {
            $this->date_upd = date('Y-m-d H:i:s');
        }
        if (Shop::is_table_associated($this->def['table'])) {
            if (is_array($this->id_shop_list) && count($this->id_shop_list)) {
                $id_shop_list = $this->id_shop_list;
            } else {
                $id_shop_list = Shop::get_context_list_shop_id();
            }
            if (Shop::check_id_shop_default($this->def['table']) && property_exists($this, 'id_shop_default')) {
                $default_shop_id = (int) Configuration::get('PS_SHOP_DEFAULT');
                $this->id_shop_default = in_array($default_shop_id, $id_shop_list) ? $default_shop_id : min($id_shop_list);
            }
        }
        // Database insertion
        $fields = $this->get_fields_primary();
        $conn = Db::get_instance();
        if (!$conn->insert($this->def['table'], $fields, $null_values)) {
            return false;
        }
        // Get object id in database
        $this->id = $conn->Insert_ID();
        $result = true;
        // Database insertion for multishop fields related to the object
        if (Shop::is_table_associated($this->def['table'])) {
            $fields = $this->get_fields_shop();
            $fields[$this->def['primary']] = (int) $this->id;
            foreach ($id_shop_list as $id_shop) {
                $fields['id_shop'] = (int) $id_shop;
                $result = $conn->insert($this->def['table'] . '_shop', $fields, $null_values) && $result;
            }
        }
        if (!$result) {
            return false;
        }
        // Database insertion for multilingual fields related to the object
        if (!empty($this->def['multilang'])) {
            $fields = $this->get_fields_lang();
            if ($fields && is_array($fields)) {
                $shops = Shop::get_complete_list_of_shops_id();
                $asso = Shop::get_asso_table($this->def['table'] . '_lang');
                foreach ($fields as $field) {
                    foreach (array_keys($field) as $key) {
                        if (!Validate::is_table_or_identifier($key)) {
                            throw new Presta_Shop_Exception('key ' . $key . ' is not table or identifier');
                        }
                    }
                    $field[$this->def['primary']] = (int) $this->id;
                    if ($asso !== false && $asso['type'] == 'fk_shop') {
                        foreach ($shops as $id_shop) {
                            $field['id_shop'] = (int) $id_shop;
                            $result = $conn->insert($this->def['table'] . '_lang', $field) && $result;
                        }
                    } else {
                        $result = $conn->insert($this->def['table'] . '_lang', $field) && $result;
                    }
                }
            }
        }
        // @hook actionObject*AddAfter
        Hook::trigger_event('actionObjectAddAfter', ['object' => $this]);
        Hook::trigger_event('actionObject' . static::class . 'AddAfter', ['object' => $this]);
        return $result;
    }
    /**
     * Takes current object ID, gets its values from database,
     * saves them in a new row and loads newly saved values as a new object.
     *
     * @return ObjectModel|false
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public function duplicate_object()
    {
        $definition = Object_Model::get_definition($this);
        $conn = Db::get_instance();
        $res = $conn->get_row('
					SELECT *
					FROM `' . _DB_PREFIX_ . bq_sql($definition['table']) . '`
					WHERE `' . bq_sql($definition['primary']) . '` = ' . (int) $this->id);
        if (!$res) {
            return false;
        }
        unset($res[$definition['primary']]);
        foreach ($res as $field => &$value) {
            if (isset($definition['fields'][$field])) {
                $value = Object_Model::format_value($value, $definition['fields'][$field]['type'], false, true, !empty($definition['fields'][$field]['allow_null']));
            }
        }
        if (!$conn->insert($definition['table'], $res)) {
            return false;
        }
        $object_id = $conn->Insert_ID();
        if (isset($definition['multilang']) && $definition['multilang']) {
            $result = $conn->get_array('
			SELECT *
			FROM `' . _DB_PREFIX_ . bq_sql($definition['table']) . '_lang`
			WHERE `' . bq_sql($definition['primary']) . '` = ' . (int) $this->id);
            if (!$result) {
                return false;
            }
            foreach ($result as &$row) {
                foreach ($row as $field => &$value) {
                    if (isset($definition['fields'][$field])) {
                        $value = Object_Model::format_value($value, $definition['fields'][$field]['type'], false, true, !empty($definition['fields'][$field]['allow_null']));
                    }
                }
            }
            // Keep $row2, you cannot use $row because there is an unexplicated conflict with the previous usage of this variable
            foreach ($result as $row2) {
                $row2[$definition['primary']] = (int) $object_id;
                if (!$conn->insert($definition['table'] . '_lang', $row2)) {
                    return false;
                }
            }
        }
        $class_name = $definition['classname'];
        /** @var ObjectModel $objectDuplicated */
        $object_duplicated = new $class_name((int) $object_id);
        $object_duplicated->duplicate_shops((int) $this->id);
        return $object_duplicated;
    }
    /**
     * Updates the current object in the database
     *
     * @param bool $nullValues
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        $id = (int) $this->id;
        if (!$id) {
            trigger_error('Attempt to update unsaved object ' . static::class, E_USER_WARNING);
            return false;
        }
        // @hook actionObject*UpdateBefore
        Hook::trigger_event('actionObjectUpdateBefore', ['object' => $this]);
        Hook::trigger_event('actionObject' . static::class . 'UpdateBefore', ['object' => $this]);
        $this->clear_cache();
        // Automatically fill dates
        if (property_exists($this, 'date_upd')) {
            $this->date_upd = date('Y-m-d H:i:s');
            if (isset($this->update_fields) && is_array($this->update_fields) && count($this->update_fields)) {
                $this->update_fields['date_upd'] = true;
            }
        }
        // Automatically fill dates
        if (property_exists($this, 'date_add') && $this->date_add == null) {
            $this->date_add = date('Y-m-d H:i:s');
            if (isset($this->update_fields) && is_array($this->update_fields) && count($this->update_fields)) {
                $this->update_fields['date_add'] = true;
            }
        }
        if (is_array($this->id_shop_list) && count($this->id_shop_list)) {
            $id_shop_list = $this->id_shop_list;
        } else {
            $id_shop_list = Shop::get_context_list_shop_id();
        }
        if (Shop::check_id_shop_default($this->def['table']) && property_exists($this, 'id_shop_default') && !$this->id_shop_default) {
            $default_shop_id = (int) Configuration::get('PS_SHOP_DEFAULT');
            $this->id_shop_default = in_array($default_shop_id, $id_shop_list) ? $default_shop_id : min($id_shop_list);
        }
        // Database update
        $primary_fields = $this->get_fields_primary();
        $conn = Db::get_instance();
        if (!$result = $conn->update($this->def['table'], $primary_fields, '`' . p_sql($this->def['primary']) . '` = ' . $id, 0, $null_values)) {
            return false;
        }
        // Database insertion for multishop fields related to the object
        if (Shop::is_table_associated($this->def['table'])) {
            // for insert operation we need all multishop fields
            $insert_fields = $this->get_fields_shop();
            $insert_fields[$this->def['primary']] = $id;
            // by default update all fields except primary key
            $update_fields = $insert_fields;
            unset($update_fields[$this->def['primary']]);
            unset($update_fields['id_shop']);
            // if property $update_fields exists, we have to use it to restrict update fields
            if (is_array($this->update_fields)) {
                foreach ($update_fields as $key => $val) {
                    if (!array_key_exists($key, $this->update_fields)) {
                        unset($update_fields[$key]);
                    }
                }
            }
            // update or create multishop entries
            foreach ($id_shop_list as $id_shop) {
                $where = $this->def['primary'] . ' = ' . $id . ' AND id_shop = ' . (int) $id_shop;
                $shop_entry_exists = $conn->get_value('SELECT ' . $this->def['primary'] . ' FROM ' . _DB_PREFIX_ . $this->def['table'] . '_shop WHERE ' . $where);
                if ($shop_entry_exists) {
                    // if multishop db entry exists, we use $updateFields array to update it
                    $result = $conn->update($this->def['table'] . '_shop', $update_fields, $where, 0, $null_values) && $result;
                } elseif (Shop::get_context() == Shop::CONTEXT_SHOP) {
                    // if multishop db entry doesnt exist yet, we use $insertFields array to create it
                    $insert_fields['id_shop'] = (int) $id_shop;
                    $result = $conn->insert($this->def['table'] . '_shop', $insert_fields, $null_values) && $result;
                }
            }
        }
        // Database update for multilingual fields related to the object
        if (isset($this->def['multilang']) && $this->def['multilang']) {
            $fields = $this->get_fields_lang();
            if (is_array($fields)) {
                foreach ($fields as $field) {
                    foreach (array_keys($field) as $key) {
                        if (!Validate::is_table_or_identifier($key)) {
                            throw new Presta_Shop_Exception('key ' . $key . ' is not a valid table or identifier');
                        }
                    }
                    // If this table is linked to multishop system, update / insert for all shops from context
                    if ($this->is_lang_multishop()) {
                        foreach ($id_shop_list as $id_shop) {
                            $field['id_shop'] = (int) $id_shop;
                            $where = p_sql($this->def['primary']) . ' = ' . $id . ' AND id_lang = ' . (int) $field['id_lang'] . ' AND id_shop = ' . (int) $id_shop;
                            if ($conn->get_value('SELECT COUNT(*) FROM ' . p_sql(_DB_PREFIX_ . $this->def['table']) . '_lang WHERE ' . $where)) {
                                $result = $conn->update($this->def['table'] . '_lang', $field, $where) && $result;
                            } else {
                                $result = $conn->insert($this->def['table'] . '_lang', $field) && $result;
                            }
                        }
                    } else {
                        // If this table is not linked to multishop system ...
                        $where = p_sql($this->def['primary']) . ' = ' . $id . ' AND id_lang = ' . (int) $field['id_lang'];
                        if ($conn->get_value('SELECT COUNT(*) FROM ' . p_sql(_DB_PREFIX_ . $this->def['table']) . '_lang WHERE ' . $where)) {
                            $result = $conn->update($this->def['table'] . '_lang', $field, $where) && $result;
                        } else {
                            $result = $conn->insert($this->def['table'] . '_lang', $field, $null_values) && $result;
                        }
                    }
                }
            }
        }
        // @hook actionObject*UpdateAfter
        Hook::trigger_event('actionObjectUpdateAfter', ['object' => $this]);
        Hook::trigger_event('actionObject' . static::class . 'UpdateAfter', ['object' => $this]);
        return $result;
    }
    /**
     * Deletes current object from database
     *
     * @return bool True if delete was successful
     * @throws PrestaShopException
     */
    public function delete()
    {
        // @hook actionObject*DeleteBefore
        Hook::trigger_event('actionObjectDeleteBefore', ['object' => $this]);
        Hook::trigger_event('actionObject' . static::class . 'DeleteBefore', ['object' => $this]);
        $this->clear_cache();
        $result = true;
        // Remove association to multishop table
        $conn = Db::get_instance();
        if (Shop::is_table_associated($this->def['table'])) {
            if (is_array($this->id_shop_list) && count($this->id_shop_list)) {
                $id_shop_list = $this->id_shop_list;
            } else {
                $id_shop_list = Shop::get_context_list_shop_id();
            }
            $result = $conn->delete($this->def['table'] . '_shop', '`' . $this->def['primary'] . '`=' . (int) $this->id . ' AND id_shop IN (' . implode(', ', $id_shop_list) . ')');
        }
        // Database deletion
        $has_multishop_entries = $this->has_multishop_entries();
        if ($result && !$has_multishop_entries) {
            $result = $conn->delete($this->def['table'], '`' . bq_sql($this->def['primary']) . '` = ' . (int) $this->id);
        }
        if (!$result) {
            return false;
        }
        // Database deletion for multilingual fields related to the object
        if (!empty($this->def['multilang']) && !$has_multishop_entries) {
            $result = $conn->delete($this->def['table'] . '_lang', '`' . bq_sql($this->def['primary']) . '` = ' . (int) $this->id);
        }
        // @hook actionObject*DeleteAfter
        Hook::trigger_event('actionObjectDeleteAfter', ['object' => $this]);
        Hook::trigger_event('actionObject' . static::class . 'DeleteAfter', ['object' => $this]);
        return $result;
    }
    /**
     * Deletes multiple objects from the database at once
     *
     * @param array $ids Array of objects IDs.
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete_selection($ids)
    {
        $result = true;
        foreach ($ids as $id) {
            $this->id = (int) $id;
            $result = $result && $this->delete();
        }
        return $result;
    }
    /**
     * Toggles object status in database
     *
     * @return bool Update result
     * @throws PrestaShopException
     */
    public function toggle_status()
    {
        // Object must have a variable called 'active'
        if (!property_exists($this, 'active')) {
            throw new Presta_Shop_Exception('property "active" is missing in object ' . static::class);
        }
        // Update only active field
        $this->set_fields_to_update(['active' => true]);
        // Update active status on object
        $this->active = !(int) $this->active;
        // Change status to active/inactive
        return $this->update(false);
    }
    /**
     * @deprecated 1.0.0 (use getFieldsLang())
     *
     * @param array $fieldsArray
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function get_translations_fields($fields_array)
    {
        $fields = [];
        if ($this->id_lang == null) {
            foreach (Language::get_i_ds(false) as $id_lang) {
                $this->make_translation_fields($fields, $fields_array, $id_lang);
            }
        } else {
            $this->make_translation_fields($fields, $fields_array, $this->id_lang);
        }
        return $fields;
    }
    /**
     * @deprecated 1.0.0
     *
     * @param array $fieldsArray
     * @param int $idLanguage
     * @throws PrestaShopException
     */
    protected function make_translation_fields(array &$fields, &$fields_array, $id_language)
    {
        $fields[$id_language]['id_lang'] = $id_language;
        $fields[$id_language][$this->def['primary']] = (int) $this->id;
        if ($this->id_shop && $this->is_lang_multishop()) {
            $fields[$id_language]['id_shop'] = (int) $this->id_shop;
        }
        foreach ($fields_array as $k => $field) {
            $html = false;
            $field_name = $field;
            if (is_array($field)) {
                $field_name = $k;
                $html = $field['html'] ?? false;
            }
            /* Check fields validity */
            if (!Validate::is_table_or_identifier($field_name)) {
                throw new Presta_Shop_Exception('identifier is not table or identifier : ' . $field_name);
            }
            // Copy the field, or the default language field if it's both required and empty
            if (!$this->id_lang && isset($this->{$field_name}[$id_language]) && !empty($this->{$field_name}[$id_language]) || $this->id_lang && !empty($this->{$field_name})) {
                $fields[$id_language][$field_name] = $this->id_lang ? p_sql($this->{$field_name}, $html) : p_sql($this->{$field_name}[$id_language], $html);
            } elseif (in_array($field_name, $this->fields_required_lang)) {
                $fields[$id_language][$field_name] = p_sql($this->id_lang ? $this->{$field_name} : $this->{$field_name}[Configuration::get('PS_LANG_DEFAULT')], $html);
            } else {
                $fields[$id_language][$field_name] = '';
            }
        }
    }
    /**
     * Checks if object field values are valid before database interaction
     *
     * @param bool $die
     * @param bool $errorReturn
     *
     * @return bool|string True, false or error message.
     * @throws PrestaShopException
     */
    public function validate_fields($die = true, $error_return = false)
    {
        foreach ($this->def['fields'] as $field => $data) {
            if (!empty($data['lang'])) {
                continue;
            }
            if (is_array($this->update_fields) && empty($this->update_fields[$field]) && isset($this->def['fields'][$field]['shop']) && $this->def['fields'][$field]['shop']) {
                continue;
            }
            $message = $this->validate_field($field, $this->{$field});
            if ($message !== true) {
                if ($die) {
                    throw new Presta_Shop_Exception($message);
                }
                return $error_return ? $message : false;
            }
        }
        return true;
    }
    /**
     * Checks if multilingual object field values are valid before database interaction.
     *
     * @param bool $die
     * @param bool $errorReturn
     *
     * @return bool|string True, false or error message.
     * @throws PrestaShopException
     */
    public function validate_fields_lang($die = true, $error_return = false)
    {
        $id_lang_default = Configuration::get('PS_LANG_DEFAULT');
        foreach ($this->def['fields'] as $field => $data) {
            if (empty($data['lang'])) {
                continue;
            }
            $values = $this->{$field};
            // If the object has not been loaded in multilanguage, then the value is the one for the current language of the object
            if (!is_array($values)) {
                $values = [$this->id_lang => $values];
            }
            // The value for the default must always be set, so we put an empty string if it does not exists
            if (!isset($values[$id_lang_default])) {
                $values[$id_lang_default] = '';
            }
            foreach ($values as $id_lang => $value) {
                if (is_array($this->update_fields) && empty($this->update_fields[$field][$id_lang])) {
                    continue;
                }
                $message = $this->validate_field($field, $value, $id_lang);
                if ($message !== true) {
                    if ($die) {
                        throw new Presta_Shop_Exception($message);
                    }
                    return $error_return ? $message : false;
                }
            }
        }
        return true;
    }
    /**
     * Validate a single field
     *
     * @param string $field Field name
     * @param array|bool|float|int|string|null $value Field value
     * @param int|null $idLang Language ID
     * @param array $skip Array of fields to skip.
     * @param bool $humanErrors If true, uses more descriptive, translatable error strings.
     *
     * @return true|string True or error message string.
     * @throws PrestaShopException
     */
    public function validate_field(string $field, $value, $id_lang = null, $skip = [], $human_errors = false)
    {
        static $ps_lang_default = null;
        static $ps_allow_html_iframe = null;
        if ($ps_lang_default === null) {
            $ps_lang_default = Configuration::get('PS_LANG_DEFAULT');
        }
        if ($ps_allow_html_iframe === null) {
            $ps_allow_html_iframe = (int) Configuration::get('PS_ALLOW_HTML_IFRAME');
        }
        $this->cache_fields_required_database();
        $data = $this->def['fields'][$field];
        // Check if field is required
        $required_fields = static::$fields_required_database[$this::class] ?? [];
        if (!$id_lang || $id_lang == $ps_lang_default) {
            if (!in_array('required', $skip) && (!empty($data['required']) || in_array($field, $required_fields))) {
                if (Tools::is_empty($value)) {
                    if ($human_errors) {
                        return sprintf(Tools::display_error('The %s field is required.'), static::display_field_name($field, $this::class));
                    }
                    return 'Property ' . $this::class . '->' . $field . ' is empty';
                }
            }
        }
        // Default value
        if (!$value && !empty($data['default'])) {
            $value = $data['default'];
            $this->{$field} = $value;
        }
        // Check field values
        if (!in_array('values', $skip) && !empty($data['values']) && is_array($data['values']) && !in_array($value, $data['values'])) {
            if ($human_errors) {
                return sprintf(Tools::display_error('The %s field is invalid.'), static::display_field_name($field, $this::class));
            }
            return 'Property ' . $this::class . '->' . $field . ' has invalid value [' . Error_Utils::display_argument($value) . ']. Allowed values are: ' . implode(', ', $data['values']) . ')';
        }
        // Check field size
        if (!in_array('size', $skip) && !empty($data['size']) && in_array($data['type'], [static::TYPE_STRING, static::TYPE_HTML])) {
            $size = $data['size'];
            if (!is_array($data['size'])) {
                $size = ['min' => 0, 'max' => $data['size']];
            }
            $length = is_null($value) ? 0 : mb_strlen($value);
            if ($length < $size['min'] || $length > $size['max']) {
                if ($human_errors) {
                    if (isset($data['lang']) && $data['lang']) {
                        $language = new Language((int) $id_lang);
                        return sprintf(Tools::display_error('The field %1$s (%2$s) is too long (%3$d chars max, html chars including).'), static::display_field_name($field, $this::class), $language->name, $size['max']);
                    }
                    return sprintf(Tools::display_error('The %1$s field is too long (%2$d chars max).'), static::display_field_name($field, $this::class), $size['max']);
                }
                return 'Property ' . $this::class . '->' . $field . ' length (' . $length . ') must be between ' . $size['min'] . ' and ' . $size['max'];
            }
        }
        // Check field validator
        if (!in_array('validate', $skip) && !empty($data['validate'])) {
            if (!empty($value)) {
                $validate = $data['validate'];
                if (is_string($validate)) {
                    if (mb_strtolower($validate) === 'iscleanhtml') {
                        $res = Validate::is_clean_html($value, $ps_allow_html_iframe);
                    } elseif (method_exists(Validate::class, $validate)) {
                        $res = (bool) Validate::$validate($value);
                    } else {
                        throw new Presta_Shop_Exception('Property ' . static::class . '->' . $field . ': Validation function not found: ' . $validate);
                    }
                } elseif (is_callable($validate)) {
                    $res = $validate($value);
                } else {
                    throw new Presta_Shop_Exception('Property ' . static::class . '->' . $field . ': invalid validation callback');
                }
                if (!$res) {
                    if ($human_errors) {
                        return sprintf(Tools::display_error('The %s field is invalid.'), static::display_field_name($field, $this::class));
                    }
                    return 'Property ' . $this::class . '->' . $field . ' has invalid value [' . Error_Utils::display_argument($value) . ']';
                }
            }
        }
        return true;
    }
    /**
     * Returns field name translation
     *
     * @param string $field Field name
     * @param string $class ObjectModel class name
     * @param bool $htmlentities If true, applies htmlentities() to result string
     * @param Context|null $context Context object
     *
     * @return string
     */
    public static function display_field_name($field, string $class = self::class, $htmlentities = true, ?Context $context = null)
    {
        global $_FIELDS;
        if (!isset($context)) {
            $context = Context::get_context();
        }
        if ($_FIELDS === null && file_exists(_PS_TRANSLATIONS_DIR_ . $context->language->iso_code . '/fields.php')) {
            include_once _PS_TRANSLATIONS_DIR_ . $context->language->iso_code . '/fields.php';
        }
        $key = $class . '_' . md5($field);
        if (is_array($_FIELDS) && array_key_exists($key, $_FIELDS) && $_FIELDS[$key] !== '') {
            $str = $_FIELDS[$key];
            return $htmlentities ? htmlentities((string) $str, ENT_QUOTES, 'utf-8') : $str;
        }
        return $field;
    }
    /**
     * @param bool $htmlentities
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.0.0 Use validateController() instead
     */
    public function validate_controler($htmlentities = true)
    {
        Tools::display_as_deprecated();
        return $this->validate_controller($htmlentities);
    }
    /**
     * Validates submitted values and returns an array of errors, if any.
     *
     * @param bool $htmlentities If true, uses htmlentities() for field name translations in errors.
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function validate_controller($htmlentities = true)
    {
        $this->cache_fields_required_database();
        $errors = [];
        $class_name = static::class;
        $required_fields_database = static::$fields_required_database[$class_name] ?? [];
        foreach ($this->def['fields'] as $field => $data) {
            $value = Tools::get_value($field, $this->{$field});
            // Check if field is required by user
            if (in_array($field, $required_fields_database)) {
                $data['required'] = true;
            }
            $is_empty = empty($value) && $value !== '0' && $value !== 0 && $value !== 0.0 && $value !== false;
            // Checking for required fields
            if (isset($data['required']) && $data['required'] && $is_empty) {
                if (!$this->id || $field != 'passwd') {
                    $errors[$field] = '<b>' . static::display_field_name($field, $class_name, $htmlentities) . '</b> ' . Tools::display_error('is required.');
                }
            }
            // Checking for maximum fields sizes
            if (isset($data['size']) && !$is_empty && in_array($data['type'], [static::TYPE_STRING, static::TYPE_HTML]) && mb_strlen($value) > $data['size']) {
                $errors[$field] = sprintf(Tools::display_error('%1$s is too long. Maximum length: %2$d'), static::display_field_name($field, $class_name, $htmlentities), $data['size']);
            }
            // Checking for fields validity
            // Hack for postcode required for country which does not have postcodes
            if (!$is_empty || $field == 'postcode' && $value == '0') {
                $validation_error = false;
                if (isset($data['validate'])) {
                    $data_validate = $data['validate'];
                    if (!Validate::$data_validate($value) && (!$is_empty || $data['required'])) {
                        $errors[$field] = '<b>' . static::display_field_name($field, $class_name, $htmlentities) . '</b> ' . Tools::display_error('is invalid.');
                        $validation_error = true;
                    }
                }
                if (!$validation_error) {
                    if (isset($data['copy_post']) && !$data['copy_post']) {
                        continue;
                    }
                    if ($field == 'passwd') {
                        if ($value = Tools::get_value($field)) {
                            $this->{$field} = Tools::hash($value);
                        }
                    } else {
                        $this->{$field} = $value;
                    }
                }
            }
        }
        // call modules hook to validate controller
        foreach (['actionObjectValidateController', 'actionObject' . $class_name . 'ValidateController'] as $hook_name) {
            $modules_errors = Hook::get_responses($hook_name, ['object' => $this, 'className' => $class_name]);
            foreach ($modules_errors as $module_errors) {
                if (is_array($module_errors)) {
                    foreach ($module_errors as $error) {
                        if (is_string($error)) {
                            $errors[] = $error;
                        }
                    }
                }
            }
        }
        return $errors;
    }
    /**
     * Returns webservice parameters of this object.
     *
     * @param string|null $wsParamsAttributeName
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_webservice_parameters($ws_params_attribute_name = null)
    {
        $this->cache_fields_required_database();
        $default_resource_parameters = ['objectSqlId' => $this->def['primary'], 'retrieveData' => ['className' => static::class, 'retrieveMethod' => 'getWebserviceObjectList', 'params' => [], 'table' => $this->def['table']], 'fields' => ['id' => ['sqlId' => $this->def['primary'], 'i18n' => false]]];
        if ($ws_params_attribute_name === null) {
            $ws_params_attribute_name = 'webserviceParameters';
        }
        if (!isset($this->{$ws_params_attribute_name}['objectNodeName'])) {
            $default_resource_parameters['objectNodeName'] = $this->def['table'];
        }
        if (!isset($this->{$ws_params_attribute_name}['objectsNodeName'])) {
            $default_resource_parameters['objectsNodeName'] = $this->def['table'] . 's';
        }
        if (isset($this->{$ws_params_attribute_name}['associations'])) {
            foreach ($this->{$ws_params_attribute_name}['associations'] as $assoc_name => &$association) {
                if (!array_key_exists('setter', $association) || isset($association['setter']) && !$association['setter']) {
                    $association['setter'] = Tools::to_camel_case('set_ws_' . $assoc_name);
                }
                if (!array_key_exists('getter', $association)) {
                    $association['getter'] = Tools::to_camel_case('get_ws_' . $assoc_name);
                }
            }
        }
        if (isset($this->{$ws_params_attribute_name}['retrieveData']['retrieveMethod'])) {
            unset($default_resource_parameters['retrieveData']['retrieveMethod']);
        }
        $resource_parameters = array_merge_recursive($default_resource_parameters, $this->{$ws_params_attribute_name});
        $required_fields = static::$fields_required_database[static::class] ?? [];
        foreach ($this->def['fields'] as $field_name => $details) {
            if (!isset($resource_parameters['fields'][$field_name])) {
                $resource_parameters['fields'][$field_name] = [];
            }
            $current_field = [];
            $current_field['sqlId'] = $field_name;
            if (isset($details['size'])) {
                $current_field['maxSize'] = $details['size'];
            }
            if (isset($details['lang'])) {
                $current_field['i18n'] = $details['lang'];
            } else {
                $current_field['i18n'] = false;
            }
            if (isset($details['required']) && $details['required'] === true || in_array($field_name, $required_fields)) {
                $current_field['required'] = true;
            } else {
                $current_field['required'] = false;
            }
            if (isset($details['validate'])) {
                $current_field['validateMethod'] = array_key_exists('validateMethod', $resource_parameters['fields'][$field_name]) ? array_merge($resource_parameters['fields'][$field_name]['validateMethod'], [$details['validate']]) : [$details['validate']];
            }
            $resource_parameters['fields'][$field_name] = array_merge($resource_parameters['fields'][$field_name], $current_field);
            if (isset($details['ws_modifier'])) {
                $resource_parameters['fields'][$field_name]['modifier'] = $details['ws_modifier'];
            }
        }
        if (isset($this->date_add)) {
            $resource_parameters['fields']['date_add']['setter'] = false;
        }
        if (isset($this->date_upd)) {
            $resource_parameters['fields']['date_upd']['setter'] = false;
        }
        foreach ($resource_parameters['fields'] as $key => $resource_parameters_field) {
            if (!isset($resource_parameters_field['sqlId'])) {
                $resource_parameters['fields'][$key]['sqlId'] = $key;
            }
        }
        return $resource_parameters;
    }
    /**
     * Returns webservice object list.
     *
     * @param string $sqlJoin
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array|null
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public function get_webservice_object_list($sql_join, $sql_filter, $sql_sort, $sql_limit)
    {
        $assoc = Shop::get_asso_table($this->def['table']);
        if ($assoc !== false) {
            if ($assoc['type'] !== 'fk_shop') {
                $multi_shop_join = ' LEFT JOIN `' . _DB_PREFIX_ . bq_sql($this->def['table']) . '_' . bq_sql($assoc['type']) . '`
										AS `multi_shop_' . bq_sql($this->def['table']) . '`
										ON (main.`' . bq_sql($this->def['primary']) . '` = `multi_shop_' . bq_sql($this->def['table']) . '`.`' . bq_sql($this->def['primary']) . '`)';
                $sql_filter = 'AND `multi_shop_' . bq_sql($this->def['table']) . '`.id_shop = ' . Context::get_context()->shop->id . ' ' . $sql_filter;
                $sql_join = $multi_shop_join . ' ' . $sql_join;
            } else {
                $or = [];
                foreach (Webservice_Request::get_instance()->get_shop_ids() as $id_shop) {
                    $or[] = '(main.id_shop = ' . (int) $id_shop . (isset($this->def['fields']['id_shop_group']) ? ' OR (id_shop = 0 AND id_shop_group=' . (int) Shop::get_group_from_shop((int) $id_shop) . ')' : '') . ')';
                }
                $prepend = '';
                if ($or) {
                    $prepend = 'AND (' . implode('OR', $or) . ')';
                }
                $sql_filter = $prepend . ' ' . $sql_filter;
            }
        }
        $query = '
		SELECT DISTINCT main.`' . bq_sql($this->def['primary']) . '` FROM `' . _DB_PREFIX_ . bq_sql($this->def['table']) . '` AS main
		' . $sql_join . '
		WHERE 1 ' . $sql_filter . '
		' . ($sql_sort != '' ? $sql_sort : '') . '
		' . ($sql_limit != '' ? $sql_limit : '');
        return Db::read_only()->get_array($query);
    }
    /**
     * Validate required fields.
     *
     * @param bool $htmlentities
     *
     * @return array
     * @throws PrestaShopException
     */
    public function validate_fields_required_database($htmlentities = true)
    {
        $this->cache_fields_required_database();
        $errors = [];
        $required_fields = static::$fields_required_database[$this::class] ?? [];
        foreach ($this->def['fields'] as $field => $data) {
            if (!in_array($field, $required_fields)) {
                continue;
            }
            if (!method_exists('Validate', $data['validate'])) {
                throw new Presta_Shop_Exception('Validation function not found. ' . $data['validate']);
            }
            $value = Tools::get_value($field);
            if (empty($value)) {
                $errors[$field] = sprintf(Tools::display_error('The field %s is required.'), static::display_field_name($field, static::class, $htmlentities));
            }
        }
        return $errors;
    }
    /**
     * Returns an array of required fields
     *
     * @param bool $all If true, returns required fields of all object classes.
     *
     * @return array|null
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public function get_fields_required_database($all = false)
    {
        return Db::read_only()->get_array('
		SELECT id_required_field, object_name, field_name
		FROM ' . _DB_PREFIX_ . 'required_field
		' . (!$all ? 'WHERE object_name = \'' . p_sql(static::class) . '\'' : ''));
    }
    /**
     * Caches data about required objects fields in memory
     *
     * @param bool $all If true, caches required fields of all object classes.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function cache_fields_required_database($all = true): void
    {
        if (!is_array(static::$fields_required_database)) {
            $fields = $this->getfields_required_database((bool) $all);
            if ($fields) {
                foreach ($fields as $row) {
                    static::$fields_required_database[$row['object_name']][(int) $row['id_required_field']] = p_sql($row['field_name']);
                }
            } else {
                static::$fields_required_database = [];
            }
        }
    }
    /**
     * Sets required field for this class in the database.
     *
     * @param array $fields
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public function add_fields_required_database($fields)
    {
        if (!is_array($fields)) {
            return false;
        }
        $conn = Db::get_instance();
        if (!$conn->execute('DELETE FROM ' . _DB_PREFIX_ . 'required_field WHERE object_name = \'' . static::class . '\'')) {
            return false;
        }
        foreach ($fields as $field) {
            if (!$conn->insert('required_field', ['object_name' => static::class, 'field_name' => p_sql($field)])) {
                return false;
            }
        }
        return true;
    }
    /**
     * Clears cache entries that have this object's ID.
     *
     * @param bool $all If true, clears cache for all objects
     */
    public function clear_cache($all = false): void
    {
        if ($all) {
            Cache::clean('objectmodel_' . $this->def['classname'] . '_*');
        } elseif ($this->id) {
            Cache::clean('objectmodel_' . $this->def['classname'] . '_' . (int) $this->id . '_*');
        }
    }
    /**
     * Checks if current object is associated to a shop.
     *
     * @param int|null $idShop
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_associated_to_shop($id_shop = null)
    {
        if ($id_shop === null) {
            $id_shop = Context::get_context()->shop->id;
        }
        $cache_id = 'objectmodel_shop_' . $this->def['classname'] . '_' . (int) $this->id . '-' . (int) $id_shop;
        if (!Object_Model::$cache_objects || !Cache::is_stored($cache_id)) {
            $associated = (bool) Db::read_only()->get_value('
				SELECT id_shop
				FROM `' . p_sql(_DB_PREFIX_ . $this->def['table']) . '_shop`
				WHERE `' . $this->def['primary'] . '` = ' . (int) $this->id . '
				AND id_shop = ' . (int) $id_shop);
            if (!Object_Model::$cache_objects) {
                return $associated;
            }
            Cache::store($cache_id, $associated);
            return $associated;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * This function associate an item to its context
     *
     * @param int|array $idShops
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public function associate_to($id_shops)
    {
        if (!$this->id) {
            return false;
        }
        if (!is_array($id_shops)) {
            $id_shops = [$id_shops];
        }
        $data = [];
        foreach ($id_shops as $id_shop) {
            if (!$this->is_associated_to_shop($id_shop)) {
                $data[] = [$this->def['primary'] => (int) $this->id, 'id_shop' => (int) $id_shop];
            }
        }
        if ($data) {
            return Db::get_instance()->insert($this->def['table'] . '_shop', $data);
        }
        return true;
    }
    /**
     * Gets the list of associated shop IDs
     *
     * @return array
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public function get_associated_shops()
    {
        if (!Shop::is_table_associated($this->def['table'])) {
            return [];
        }
        $list = [];
        $sql = 'SELECT id_shop FROM `' . _DB_PREFIX_ . $this->def['table'] . '_shop` WHERE `' . $this->def['primary'] . '` = ' . (int) $this->id;
        foreach (Db::read_only()->get_array($sql) as $row) {
            $list[] = $row['id_shop'];
        }
        return $list;
    }
    /**
     * Copies shop association data from object with specified ID.
     *
     * @param int $id
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public function duplicate_shops($id)
    {
        if (!Shop::is_table_associated($this->def['table'])) {
            return false;
        }
        $sql = 'SELECT id_shop
				FROM ' . _DB_PREFIX_ . $this->def['table'] . '_shop
				WHERE ' . $this->def['primary'] . ' = ' . (int) $id;
        if ($results = Db::read_only()->get_array($sql)) {
            $ids = [];
            foreach ($results as $row) {
                $ids[] = $row['id_shop'];
            }
            return $this->associate_to($ids);
        }
        return false;
    }
    /**
     * Checks if there is more than one entry in associated shop table for current object.
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function has_multishop_entries()
    {
        if (!Shop::is_table_associated($this->def['table']) || !Shop::is_feature_active()) {
            return false;
        }
        return (bool) Db::read_only()->get_value('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . $this->def['table'] . '_shop` WHERE `' . $this->def['primary'] . '` = ' . (int) $this->id);
    }
    /**
     * Checks if object is multi-shop object.
     *
     * @return bool
     */
    public function is_multishop()
    {
        if (Shop::is_table_associated($this->def['table'])) {
            return true;
        }
        return !empty($this->def['multilang_shop']);
    }
    /**
     * Checks if a field is a multi-shop field.
     *
     * @param string $field
     *
     * @return bool
     */
    public function is_multi_shop_field($field)
    {
        return isset($this->def['fields'][$field]['shop']) && $this->def['fields'][$field]['shop'];
    }
    /**
     * Checks if the object is both multi-language and multi-shop.
     *
     * @return bool
     */
    public function is_lang_multishop()
    {
        return !empty($this->def['multilang']) && !empty($this->def['multilang_shop']);
    }
    /**
     * Updates a table and splits the common datas and the shop datas.
     *
     * @param string $className
     * @param array $data
     * @param string $where
     * @param string $specificWhere Only executed for common table
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function update_multishop_table($class_name, $data, ?string $where = '', $specific_where = '')
    {
        $def = Object_Model::get_definition($class_name);
        $update_data = [];
        foreach ($data as $field => $value) {
            if (!isset($def['fields'][$field])) {
                continue;
            }
            if (!empty($def['fields'][$field]['shop'])) {
                if ($value === null && !empty($def['fields'][$field]['allow_null'])) {
                    $update_data[] = "a.{$field} = NULL";
                    $update_data[] = "{$def['table']}_shop.{$field} = NULL";
                } else {
                    $update_data[] = "a.{$field} = '{$value}'";
                    $update_data[] = "{$def['table']}_shop.{$field} = '{$value}'";
                }
            } else if ($value === null && !empty($def['fields'][$field]['allow_null'])) {
                $update_data[] = "a.{$field} = NULL";
            } else {
                $update_data[] = "a.{$field} = '{$value}'";
            }
        }
        $sql = 'UPDATE ' . _DB_PREFIX_ . $def['table'] . ' a
				' . Shop::add_sql_association($def['table'], 'a', true, null, true) . '
				SET ' . implode(', ', $update_data) . (!empty($where) ? ' WHERE ' . $where : '');
        return Db::get_instance()->execute($sql);
    }
    /**
     * Delete images associated with the object
     *
     * @param bool $forceDelete @deprecated
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_image($force_delete = false)
    {
        if (!$this->id) {
            return false;
        }
        $candidates = [];
        $types = $this->image_dir ? Image_Type::get_images_types() : [];
        // To make sure we get all relevant image files, we need to loop through all supported image extensions
        foreach (Image_Manager::get_allowed_image_extensions(true, true) as $image_extension) {
            // Deleting tmp images
            $ids_shop = Shop::get_complete_list_of_shops_id();
            $ids_shop[] = 0;
            // Making sure that none shop related image are deleted too
            foreach ($ids_shop as $id_shop) {
                $shop_key = $id_shop ? '_' . $id_shop : '';
                $candidates[] = _PS_TMP_IMG_DIR_ . $this->def['table'] . '_' . $this->id . $shop_key . '.' . $image_extension;
                $candidates[] = _PS_TMP_IMG_DIR_ . $this->def['table'] . '_mini_' . $this->id . $shop_key . '.' . $image_extension;
                $candidates[] = _PS_TMP_IMG_DIR_ . $this->def['table'] . '_' . $this->id . $shop_key . '_thumb.' . $image_extension;
            }
            /* Deleting object images and thumbnails (cache) */
            if ($this->image_dir) {
                $candidates[] = $this->image_dir . $this->id . '.' . $image_extension;
                foreach ($types as $image_type) {
                    $candidates[] = $this->image_dir . $this->id . '-' . stripslashes((string) $image_type['name']) . '.' . $image_extension;
                    $candidates[] = $this->image_dir . $this->id . '-' . stripslashes((string) $image_type['name']) . '2x.' . $image_extension;
                }
            }
        }
        $result = true;
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                $result = unlink($candidate) && $result;
            }
        }
        return $result;
    }
    /**
     * Checks if an object exists in database.
     *
     * @param int $idEntity
     * @param string $table
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function exists_in_database($id_entity, $table)
    {
        $row = Db::read_only()->get_row('
			SELECT `id_' . bq_sql($table) . '` as id
			FROM `' . _DB_PREFIX_ . bq_sql($table) . '` e
			WHERE e.`id_' . bq_sql($table) . '` = ' . (int) $id_entity);
        return isset($row['id']);
    }
    /**
     * Checks if an object type exists in the database.
     *
     * @param string|null $table Name of table linked to entity
     * @param bool $hasActiveColumn True if the table has an active column
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_currently_used($table = null, $has_active_column = false)
    {
        if ($table === null) {
            $table = static::$definition['table'];
        }
        $query = new Db_Query();
        $query->select('`id_' . bq_sql($table) . '`');
        $query->from($table);
        if ($has_active_column) {
            $query->where('`active` = 1');
        }
        return (bool) Db::read_only()->get_value($query);
    }
    /**
     * Fill an object with given data. Data must be an array with this syntax:
     * array(objProperty => value, objProperty2 => value, etc.)
     *
     * @param int|null $idLang
     */
    public function hydrate(array $data, $id_lang = null): void
    {
        $this->id_lang = $id_lang;
        if (isset($data[$this->def['primary']])) {
            $this->id = $data[$this->def['primary']];
        }
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }
    /**
     * Fill an object with given data. Data must be an array with this syntax:
     * array(
     *   array(id_lang => 1, objProperty => value, objProperty2 => value, etc.),
     *   array(id_lang => 2, objProperty => value, objProperty2 => value, etc.),
     * );
     */
    public function hydrate_multilang(array $data): void
    {
        foreach ($data as $row) {
            if (isset($row[$this->def['primary']])) {
                $this->id = $row[$this->def['primary']];
            }
            foreach ($row as $key => $value) {
                if (property_exists($this, $key)) {
                    if (!empty($this->def['fields'][$key]['lang']) && !empty($row['id_lang'])) {
                        // Multilang
                        if (!is_array($this->{$key})) {
                            $this->{$key} = [];
                        }
                        $this->{$key}[(int) $row['id_lang']] = $value;
                    } else {
                        // Normal
                        $this->{$key} = $value;
                    }
                }
            }
        }
    }
    /**
     * Fill (hydrate) a list of objects in order to get a collection of these objects
     *
     * @param string $class Class of objects to hydrate
     * @param array $datas List of data (multi-dimensional array)
     * @param int|null $idLang
     *
     * @return array
     * @throws PrestaShopException
     */
    public static function hydrate_collection($class, array $datas, $id_lang = null)
    {
        if (!class_exists($class)) {
            throw new Presta_Shop_Exception("Class '{$class}' not found");
        }
        $collection = [];
        $rows = [];
        if ($datas) {
            $definition = Object_Model::get_definition($class);
            if (!array_key_exists($definition['primary'], $datas[0])) {
                throw new Presta_Shop_Exception("Identifier '{$definition['primary']}' not found for class '{$class}'");
            }
            foreach ($datas as $row) {
                // Get object common properties
                $id = $row[$definition['primary']];
                if (!isset($rows[$id])) {
                    $rows[$id] = $row;
                }
                // Get object lang properties
                if (isset($row['id_lang']) && !$id_lang) {
                    foreach ($definition['fields'] as $field => $data) {
                        if (!empty($data['lang'])) {
                            if (!is_array($rows[$id][$field])) {
                                $rows[$id][$field] = [];
                            }
                            $rows[$id][$field][$row['id_lang']] = $row[$field];
                        }
                    }
                }
            }
        }
        // Hydrate objects
        foreach ($rows as $row) {
            /** @var ObjectModel $obj */
            $obj = new $class();
            $obj->hydrate($row, $id_lang);
            $collection[] = $obj;
        }
        return $collection;
    }
    /**
     * Returns object definition
     *
     * @param string|ObjectModelCore $class Name of object or object model instance
     * @param string|null $field Name of field if we want the definition of one field only
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function get_definition($class, $field = null)
    {
        if (is_object($class)) {
            $class = $class::class;
        }
        if ($field === null) {
            $cache_id = 'objectmodel_def_' . $class;
        }
        if ($field !== null || !Cache::is_stored($cache_id)) {
            try {
                $reflection = new ReflectionClass($class);
                if (!$reflection->has_property('definition')) {
                    throw new Presta_Shop_Exception("Class '{$class}' does not contain object model definition");
                }
                $definition = $reflection->get_static_property_value('definition');
            } catch (Reflection_Exception $e) {
                throw new Presta_Shop_Exception("Failed to resolve object model definition for '{$class}'", 0, $e);
            }
            $definition['classname'] = $class;
            if (!empty($definition['multilang'])) {
                $definition['associations'][Presta_Shop_Collection::LANG_ALIAS] = ['type' => static::HAS_MANY, 'field' => $definition['primary'], 'foreign_field' => $definition['primary']];
            }
            if ($field) {
                return $definition['fields'][$field] ?? null;
            }
            Cache::store($cache_id, $definition);
            return $definition;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Retrocompatibility for classes without $definition static
     *
     * @deprecated 2.0.0
     */
    protected function set_definition_retrocompatibility()
    {
        // Retrocompatibility with $table property ($definition['table'])
        if (isset($this->def['table'])) {
            $this->table = $this->def['table'];
        } else {
            $this->def['table'] = $this->table;
        }
        // Retrocompatibility with $identifier property ($definition['primary'])
        if (isset($this->def['primary'])) {
            $this->identifier = $this->def['primary'];
        } else {
            $this->def['primary'] = $this->identifier;
        }
        // Check multilang retrocompatibility
        if (method_exists($this, 'getTranslationsFieldsChild')) {
            $this->def['multilang'] = true;
        }
        // Retrocompatibility with $fieldsValidate, $fieldsRequired and $fieldsSize properties ($definition['fields'])
        if (isset($this->def['fields'])) {
            foreach ($this->def['fields'] as $field => $data) {
                $is_lang = isset($data['lang']) && $data['lang'];
                if (isset($data['validate'])) {
                    if ($is_lang) {
                        $this->fields_validate_lang[$field] = $data['validate'];
                    } else {
                        $this->fields_validate[$field] = $data['validate'];
                    }
                }
                if (isset($data['required']) && $data['required']) {
                    if ($is_lang) {
                        $this->fields_required_lang[] = $field;
                    } else {
                        $this->fields_required[] = $field;
                    }
                }
                if (isset($data['size'])) {
                    if ($is_lang) {
                        $this->fields_size_lang[$field] = $data['size'];
                    } else {
                        $this->fields_size[$field] = $data['size'];
                    }
                }
            }
        } else {
            $this->def['fields'] = [];
            foreach ($this->fields_validate as $field => $validate) {
                $this->def['fields'][$field]['validate'] = $validate;
            }
            foreach ($this->fields_required as $field) {
                $this->def['fields'][$field]['required'] = true;
            }
            foreach ($this->fields_size as $field => $size) {
                $this->def['fields'][$field]['size'] = $size;
            }
            foreach ($this->fields_validate_lang as $field => $validate) {
                $this->def['fields'][$field]['validate'] = $validate;
                $this->def['fields'][$field]['lang'] = true;
            }
            foreach ($this->fields_required_lang as $field) {
                $this->def['fields'][$field]['required'] = true;
                $this->def['fields'][$field]['lang'] = true;
            }
            foreach ($this->fields_size_lang as $field => $size) {
                $this->def['fields'][$field]['size'] = $size;
                $this->def['fields'][$field]['lang'] = true;
            }
        }
    }
    /**
     * Return the field value for the specified language if the field is multilang,
     * else the field value.
     *
     * @param string $fieldName
     * @param int|null $idLang
     *
     * @return mixed
     * @throws PrestaShopException
     */
    public function get_field_by_lang($field_name, $id_lang = null)
    {
        $definition = Object_Model::get_definition($this);
        // Is field in definition?
        if ($definition && isset($definition['fields'][$field_name])) {
            $field = $definition['fields'][$field_name];
            if (!isset($field['lang'])) {
                return $this->{$field_name};
            }
            if (!$field['lang']) {
                return $this->{$field_name};
            }
            if (is_array($this->{$field_name})) {
                return $this->{$field_name}[$id_lang ?: Context::get_context()->language->id];
            }
            return $this->{$field_name};
        }
        throw new Presta_Shop_Exception('Could not load field from definition.');
    }
    /**
     * Set a list of specific fields to update
     * array(field1 => true, field2 => false,
     * langfield1 => array(1 => true, 2 => false))
     */
    public function set_fields_to_update(array $fields): void
    {
        $this->update_fields = $fields;
    }
    /**
     * Enables object caching
     */
    public static function enable_cache(): void
    {
        Object_Model::$cache_objects = true;
    }
    /**
     * Disables object caching
     */
    public static function disable_cache(): void
    {
        Object_Model::$cache_objects = false;
    }
    /**
     *  Create the database table with its columns. Similar to the createColumn() method.
     *
     * @param string|null $className Class name
     *
     * @return bool Indicates whether the database was successfully added
     *
     * @throws PrestaShopException
     */
    public static function create_database($class_name = null)
    {
        if (empty($class_name)) {
            $class_name = static::class;
        }
        $definition = static::get_definition($class_name);
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . bq_sql($definition['table']) . '` (';
        $sql .= '`' . $definition['primary'] . '` INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT,';
        foreach ($definition['fields'] as $field_name => $field) {
            if ($field_name === $definition['primary']) {
                continue;
            }
            if (isset($field['lang']) && $field['lang']) {
                continue;
            }
            if (isset($field['shop']) && $field['shop']) {
                continue;
            }
            if (empty($field['db_type'])) {
                switch ($field['type']) {
                    case '1':
                        $field['db_type'] = 'INT(11) UNSIGNED';
                        break;
                    case '2':
                        $field['db_type'] .= 'TINYINT(1)';
                        break;
                    case '3':
                        isset($field['size']) && $field['size'] > 256 ? $field['db_type'] = 'VARCHAR(256)' : $field['db_type'] = 'VARCHAR(512)';
                        break;
                    case '4':
                        $field['db_type'] = 'DECIMAL(20,6)';
                        break;
                    case '5':
                        $field['db_type'] = 'DATETIME';
                        break;
                    case '6':
                        $field['db_type'] = 'TEXT';
                        break;
                }
            }
            $sql .= '`' . $field_name . '` ' . $field['db_type'];
            if (isset($field['required'])) {
                $sql .= ' NOT NULL';
            }
            if (isset($field['default'])) {
                $sql .= ' DEFAULT \'' . $field['default'] . '\'';
            }
            $sql .= ',';
        }
        $sql = trim($sql, ',');
        $sql .= ')';
        $conn = Db::get_instance();
        try {
            $success = $conn->execute($sql);
        } catch (Presta_Shop_Database_Exception) {
            static::drop_database($class_name);
            return false;
        }
        if (isset($definition['multilang']) && $definition['multilang'] || isset($definition['multilang_shop']) && $definition['multilang_shop']) {
            $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . bq_sql($definition['table']) . '_lang` (';
            $sql .= '`' . $definition['primary'] . '` INT(11) UNSIGNED NOT NULL,';
            foreach ($definition['fields'] as $field_name => $field) {
                if ($field_name === $definition['primary']) {
                    continue;
                }
                if (!(isset($field['lang']) && $field['lang'])) {
                    continue;
                }
                $sql .= '`' . $field_name . '` ' . $field['db_type'];
                if (isset($field['required'])) {
                    $sql .= ' NOT NULL';
                }
                if (isset($field['default'])) {
                    $sql .= ' DEFAULT \'' . $field['default'] . '\'';
                }
                $sql .= ',';
            }
            // Lang field
            $sql .= '`id_lang` INT(11) NOT NULL,';
            if (isset($definition['multilang_shop']) && $definition['multilang_shop']) {
                $sql .= '`id_shop` INT(11) NOT NULL,';
            }
            // Primary key
            $sql .= 'PRIMARY KEY (`' . bq_sql($definition['primary']) . '`, `id_lang`)';
            $sql .= ')';
            try {
                $success = $conn->execute($sql) && $success;
            } catch (Presta_Shop_Database_Exception) {
                static::drop_database($class_name);
                return false;
            }
        }
        if (isset($definition['multishop']) && $definition['multishop'] || isset($definition['multilang_shop']) && $definition['multilang_shop']) {
            $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . bq_sql($definition['table']) . '_shop` (';
            $sql .= '`' . $definition['primary'] . '` INT(11) UNSIGNED NOT NULL,';
            foreach ($definition['fields'] as $field_name => $field) {
                if ($field_name === $definition['primary']) {
                    continue;
                }
                if (!(isset($field['shop']) && $field['shop'])) {
                    continue;
                }
                $sql .= '`' . $field_name . '` ' . $field['db_type'];
                if (isset($field['required'])) {
                    $sql .= ' NOT NULL';
                }
                if (isset($field['default'])) {
                    $sql .= ' DEFAULT \'' . $field['default'] . '\'';
                }
                $sql .= ',';
            }
            // Shop field
            $sql .= '`id_shop` INT(11) NOT NULL,';
            // Primary key
            $sql .= 'PRIMARY KEY (`' . bq_sql($definition['primary']) . '`, `id_shop`)';
            $sql .= ')';
            try {
                $success = $conn->execute($sql) && $success;
            } catch (Presta_Shop_Database_Exception) {
                static::drop_database($class_name);
                return false;
            }
        }
        return $success;
    }
    /**
     * Drop the database for this ObjectModel
     *
     * @param string|null $className Class name
     *
     * @return bool Indicates whether the database was successfully dropped
     *
     * @throws PrestaShopException
     */
    public static function drop_database($class_name = null)
    {
        if (empty($class_name)) {
            $class_name = static::class;
        }
        $definition = Object_Model::get_definition($class_name);
        $conn = Db::get_instance();
        $success = $conn->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . bq_sql($definition['table']) . '`');
        if (isset($definition['multilang']) && $definition['multilang'] || isset($definition['multilang_shop']) && $definition['multilang_shop']) {
            $success = $conn->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . bq_sql($definition['table']) . '_lang`') && $success;
        }
        if (isset($definition['multishop']) && $definition['multishop'] || isset($definition['multilang_shop']) && $definition['multilang_shop']) {
            return $conn->execute('DROP TABLE IF EXISTS `' . _DB_PREFIX_ . bq_sql($definition['table']) . '_shop`') && $success;
        }
        return $success;
    }
    /**
     * Get columns in database
     *
     * @param string|null $className Class name
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_database_columns($class_name = null)
    {
        if (empty($class_name)) {
            $class_name = static::class;
        }
        $definition = Object_Model::get_definition($class_name);
        $sql = 'SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=\'' . _DB_NAME_ . '\' AND TABLE_NAME=\'' . _DB_PREFIX_ . p_sql($definition['table']) . '\'';
        return Db::read_only()->get_array($sql);
    }
    /**
     * Add a column in the table relative to the ObjectModel.
     * This method uses the $definition property of the ObjectModel,
     * with some extra properties.
     *
     * Example:
     * 'table'        => 'tablename',
     * 'primary'      => 'id',
     * 'fields'       => array(
     *     'id'     => array('type' => self::TYPE_INT, 'validate' => 'isInt'),
     *     'number' => array(
     *         'type'     => self::TYPE_STRING,
     *         'db_type'  => 'varchar(20)',
     *         'required' => true,
     *         'default'  => '25'
     *     ),
     * ),
     *
     * The primary column is created automatically as INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT. The other columns
     * require an extra parameter, with the type of the column in the database.
     *
     * @param string $name Column name
     * @param array $columnDefinition Column type definition
     * @param string|null $className Class name
     *
     * @return bool Indicates whether the column was successfully created
     *
     * @throws PrestaShopException
     */
    public static function create_column($name, array $column_definition, $class_name = null)
    {
        if (empty($class_name)) {
            $class_name = static::class;
        }
        $definition = static::get_definition($class_name);
        $sql = 'ALTER TABLE `' . _DB_PREFIX_ . bq_sql($definition['table']) . '`';
        $sql .= ' ADD COLUMN `' . bq_sql($name) . '` ' . bq_sql($column_definition['db_type']);
        if ($name === $definition['primary']) {
            $sql .= ' INTEGER NOT NULL PRIMARY KEY AUTO_INCREMENT';
        } else {
            if (isset($column_definition['required']) && $column_definition['required']) {
                $sql .= ' NOT NULL';
            }
            if (isset($column_definition['default'])) {
                $sql .= ' DEFAULT "' . p_sql($column_definition['default']) . '"';
            }
        }
        return (bool) Db::get_instance()->execute($sql);
    }
    /**
     *  Create in the database every column detailed in the $definition property that are
     *  missing in the database.
     *
     * @param string|null $className Class name
     *
     * @return bool Indicates whether the missing columns were successfully created
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @todo    : Support multishop and multilang
     */
    public static function create_missing_columns($class_name = null)
    {
        if (empty($class_name)) {
            $class_name = static::class;
        }
        $success = true;
        $definition = static::get_definition($class_name);
        $columns = static::get_database_columns();
        foreach ($definition['fields'] as $column_name => $column_definition) {
            //column exists in database
            $exists = false;
            foreach ($columns as $column) {
                if ($column['COLUMN_NAME'] === $column_name) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $success = static::create_column($column_name, $column_definition) && $success;
            }
        }
        return $success;
    }
}