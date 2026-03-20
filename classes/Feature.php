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
use Thirtybees\Core\Initialization_Callback;
/**
 * Class FeatureCore
 */
class Feature_Core extends Object_Model implements Initialization_Callback
{
    public const SORT_VALUE_ASC = 0;
    public const SORT_VALUE_DESC = 1;
    public const SORT_CUSTOM = 2;
    /**
     * @var string|string[] Feature name
     */
    public $name;
    /**
     * @var string|string[] Feature name
     */
    public $public_name;
    /**
     * @var int Position of the feature
     */
    public $position;
    /**
     * @var bool Flag to indicate if feature allows multiple values, or just a single one
     */
    public $allows_multiple_values = false;
    /**
     * @var int Sorting method when multiple values were selected
     */
    public $sorting;
    /**
     * @var bool Deprecated
     */
    public $allows_custom_values = true;
    /**
     * @var string|string[] FO separator, when multiple values were selected
     */
    public $multiple_separator;
    /**
     * @var string|string[] FO display schema, when multiple values were selected
     */
    public $multiple_schema;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'feature', 'primary' => 'id_feature', 'multilang' => true, 'fields' => [
        'position' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbDefault' => '0'],
        'allows_multiple_values' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbDefault' => '0'],
        'allows_custom_values' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbDefault' => '1'],
        'sorting' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbType' => 'tinyint(1)', 'dbDefault' => self::SORT_VALUE_ASC],
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128, 'dbNullable' => true],
        'public_name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'required' => true, 'size' => 128, 'dbNullable' => true],
        'multiple_separator' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => false, 'size' => 128, 'dbNullable' => true],
        'multiple_schema' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => false, 'size' => 128, 'dbNullable' => true],
    ], 'keys' => ['feature_lang' => ['id_lang' => ['type' => Object_Model::KEY, 'columns' => ['id_lang', 'name']], 'id_lang_pub' => ['type' => Object_Model::KEY, 'columns' => ['id_lang', 'public_name']]], 'feature_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectsNodeName' => 'product_features', 'objectNodeName' => 'product_feature', 'fields' => []];
    /**
     * Get a feature data for a given id_feature and id_lang
     *
     * @param int $idLang Language id
     * @param int $idFeature Feature id
     *
     * @return array|false Array with feature's data
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_feature($id_lang, $id_feature)
    {
        return Db::read_only()->get_row((new Db_Query())->select('*')->from('feature', 'f')->left_join('feature_lang', 'fl', 'f.`id_feature` = fl.`id_feature` AND fl.`id_lang` = ' . (int) $id_lang)->where('f.`id_feature` = ' . (int) $id_feature));
    }
    /**
     * Get all features for a given language
     *
     * @param int $idLang Language id
     * @param bool $withShop
     *
     * @return array Multiple arrays with feature's data
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_features($id_lang, $with_shop = true)
    {
        return Db::read_only()->get_array((new Db_Query())->select('DISTINCT f.`id_feature`, f.*, fl.*')->from('feature', 'f')->join($with_shop ? Shop::add_sql_association('feature', 'f') : '')->left_join('feature_lang', 'fl', 'f.`id_feature` = fl.`id_feature` And fl.`id_lang` = ' . (int) $id_lang)->order_by('f.`position` ASC'));
    }
    /**
     * Count number of features for a given language
     *
     * @param int $idLang Language id
     *
     * @return int Number of feature
     *
     * @throws PrestaShopException
     */
    public static function nb_features($id_lang)
    {
        return Db::read_only()->get_value((new Db_Query())->select('COUNT(*) as `nb`')->from('feature', 'ag')->left_join('feature_lang', 'agl', 'ag.`id_feature` = agl.`id_feature` AND `id_lang` = ' . (int) $id_lang));
    }
    /**
     * Create a feature from import
     *
     * @param string $name
     * @param int|false $position
     * @param string|null $publicName
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function add_feature_import($name, $position = false, $public_name = null)
    {
        $name = (string) $name;
        $public_name = $public_name ? (string) $public_name : $name;
        $feature_id = (int) Db::read_only()->get_value((new Db_Query())->select('`id_feature`')->from('feature_lang')->where('`name` = \'' . p_sql($name) . '\''));
        if (!$feature_id) {
            // Feature doesn't exist, create it
            $feature = new Feature();
            $feature->name = array_fill_keys(Language::get_i_ds(), $name);
            $feature->public_name = array_fill_keys(Language::get_i_ds(), $public_name);
            if ($position) {
                $feature->position = (int) $position;
            } else {
                $feature->position = Feature::get_higher_position() + 1;
            }
            $feature->add();
            return $feature->id;
        }
        if (is_numeric($position) && $feature = new Feature($feature_id)) {
            $feature->position = (int) $position;
            if (Validate::is_loaded_object($feature)) {
                $feature->update();
            }
        }
        return $feature_id;
    }
    /**
     * getHigherPosition
     *
     * Get the higher feature position
     *
     * @return int $position
     *
     * @throws PrestaShopException
     */
    public static function get_higher_position()
    {
        $position = Db::read_only()->get_value((new Db_Query())->select('MAX(`position`)')->from('feature'));
        return is_numeric($position) ? $position : -1;
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if ($this->position <= 0) {
            $this->position = Feature::get_higher_position() + 1;
        }
        if ($this->name && !$this->public_name) {
            $this->public_name = $this->name;
        }
        $return = parent::add($auto_date, true);
        Hook::trigger_event('actionFeatureSave', ['id_feature' => $this->id]);
        return $return;
    }
    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        $this->clear_cache();
        $result = true;
        $table_name = $this->def['table'] . '_lang';
        $fields = $this->get_fields_lang();
        $conn = Db::get_instance();
        $feature_id = (int) $this->id;
        foreach ($fields as $field) {
            foreach (array_keys($field) as $key) {
                if (!Validate::is_table_or_identifier($key)) {
                    throw new Presta_Shop_Exception('key ' . $key . ' is not a valid table or identifier');
                }
            }
            $lang_id = (int) $field['id_lang'];
            $exists = (bool) $conn->get_value((new Db_Query())->select('1')->from($table_name)->where("id_feature = {$feature_id}")->where("id_lang = {$lang_id}"));
            if (!$exists) {
                $result = $conn->insert($table_name, $field) && $result;
            } else {
                $where = "id_feature = {$feature_id} AND id_lang = {$lang_id}";
                $result = $conn->update($table_name, $field, $where) && $result;
            }
        }
        if ($result) {
            $result = parent::update($null_values);
            if ($result) {
                Hook::trigger_event('actionFeatureSave', ['id_feature' => $feature_id]);
            }
        }
        return $result;
    }
    /**
     * @param array $listIdsProduct
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_features_for_comparison($list_ids_product, $id_lang)
    {
        if (!Feature::is_feature_active()) {
            return [];
        }
        $ids = '';
        foreach ($list_ids_product as $id) {
            $ids .= (int) $id . ',';
        }
        $ids = rtrim($ids, ',');
        if (empty($ids)) {
            return [];
        }
        return Db::read_only()->get_array((new Db_Query())->select('f.*, fl.*')->from('feature', 'f')->left_join('feature_product', 'fp', 'f.`id_feature` = fp.`id_feature`')->left_join('feature_lang', 'fl', 'f.`id_feature` = fl.`id_feature` AND fl.`id_lang` = ' . (int) $id_lang)->where('fp.`id_product` IN (' . $ids . ')')->group_by('f.`id_feature`')->order_by('f.`position` ASC'));
    }
    /**
     * This metohd is allow to know if a feature is used or active=
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_feature_active()
    {
        return Configuration::get('PS_FEATURE_FEATURE_ACTIVE');
    }
    /**
     * Delete several objects from database
     *
     * @param array $selection Array with items to delete
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_selection($selection)
    {
        /* Also delete Attributes */
        foreach ($selection as $value) {
            $obj = new Feature($value);
            if (!$obj->delete()) {
                return false;
            }
        }
        return true;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    public function delete()
    {
        /* Also delete related attributes */
        $conn = Db::get_instance();
        $conn->execute('
			DELETE
				`' . _DB_PREFIX_ . 'feature_value_lang`
			FROM
				`' . _DB_PREFIX_ . 'feature_value_lang`
				JOIN `' . _DB_PREFIX_ . 'feature_value`
					ON (`' . _DB_PREFIX_ . 'feature_value_lang`.id_feature_value = `' . _DB_PREFIX_ . 'feature_value`.id_feature_value)
			WHERE
				`' . _DB_PREFIX_ . 'feature_value`.`id_feature` = ' . (int) $this->id . '
		');
        $conn->delete('feature_value', '`id_feature` = ' . (int) $this->id);
        /* Also delete related products */
        $conn->delete('feature_product', '`id_feature` = ' . (int) $this->id);
        $return = parent::delete();
        if ($return) {
            Hook::trigger_event('actionFeatureDelete', ['id_feature' => $this->id]);
        }
        /* Reinitializing position */
        static::clean_positions();
        return $return;
    }
    /**
     * Reorder feature position
     * Call it after deleting a feature.
     *
     * @return bool $return
     *
     * @throws PrestaShopException
     */
    public static function clean_positions()
    {
        $conn = Db::get_instance();
        $conn->execute('SET @i = -1', false);
        $sql = 'UPDATE `' . _DB_PREFIX_ . 'feature` SET `position` = @i:=@i+1 ORDER BY `position` ASC';
        return (bool) $conn->execute($sql);
    }
    /**
     * Move a feature
     *
     * @param bool $way Up (1) or Down (0)
     * @param int $position
     * @param int|null $idFeature
     *
     * @return bool Update result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_position($way, $position, $id_feature = null)
    {
        if (!$res = Db::read_only()->get_array((new Db_Query())->select('`position`, `id_feature`')->from('feature')->where('`id_feature` = ' . (int) ($id_feature ?: $this->id))->order_by('`position` ASC'))) {
            return false;
        }
        foreach ($res as $feature) {
            if ((int) $feature['id_feature'] == (int) $this->id) {
                $moved_feature = $feature;
            }
        }
        if (!isset($moved_feature) || !isset($position)) {
            return false;
        }
        // < and > statements rather than BETWEEN operator
        // since BETWEEN is treated differently according to databases
        $conn = Db::get_instance();
        return $conn->update('feature', ['position' => ['type' => 'sql', 'value' => '`position` ' . ($way ? '- 1' : '+ 1')]], '`position`' . ($way ? '> ' . (int) $moved_feature['position'] . ' AND `position` <= ' . (int) $position : '< ' . (int) $moved_feature['position'] . ' AND `position` >= ' . (int) $position)) && $conn->update('feature', ['position' => (int) $position], '`id_feature`=' . (int) $moved_feature['id_feature']);
    }
    /**
     * @return Feature[]
     * @throws PrestaShopException
     */
    public static function get_all()
    {
        $collection = new Presta_Shop_Collection('Feature');
        return $collection->get_results();
    }
    /**
     * Reset feature positions
     *
     * @throws PrestaShopException
     */
    public static function initialization_callback(Db $conn): void
    {
        // add missing public names
        $conn->execute('UPDATE ' . _DB_PREFIX_ . "feature_lang SET public_name = name WHERE COALESCE(public_name, '') = ''");
        // recalculate positions
        $features = static::get_features(Configuration::get('PS_LANG_DEFAULT'));
        foreach ($features as $feature) {
            Feature_Value::clean_positions((int) $feature['id_feature']);
        }
    }
}