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
 * Class TranslatedConfigurationCore
 */
class Translated_Configuration_Core extends Configuration
{
    /**
     * @var string|string[]
     */
    public $value = [];
    /**
     * @var string|string[]
     */
    public $date_upd;
    /**
     * @var array
     */
    public static $definition = ['table' => 'configuration', 'primary' => 'id_configuration', 'multilang' => true, 'fields' => ['id_shop_group' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'int(11) unsigned'], 'id_shop' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbType' => 'int(11) unsigned'], 'name' => ['type' => self::TYPE_STRING, 'validate' => 'isConfigName', 'required' => true, 'size' => 254], 'value' => ['type' => self::TYPE_STRING, 'lang' => true, 'size' => Object_Model::SIZE_TEXT], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'date_upd' => ['type' => self::TYPE_DATE, 'lang' => true, 'validate' => 'isDate']], 'keys' => ['configuration' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']], 'id_shop_group' => ['type' => Object_Model::KEY, 'columns' => ['id_shop_group']]], 'configuration_kpi' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']], 'id_shop_group' => ['type' => Object_Model::KEY, 'columns' => ['id_shop_group']], 'name' => ['type' => Object_Model::KEY, 'columns' => ['name']]], 'configuration_kpi_lang' => ['primary' => ['type' => Object_Model::PRIMARY_KEY, 'columns' => ['id_configuration_kpi', 'id_lang']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectNodeName' => 'translated_configuration', 'objectsNodeName' => 'translated_configurations', 'fields' => ['value' => [], 'date_add' => [], 'date_upd' => []]];
    /**
     * TranslatedConfigurationCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null)
    {
        $this->def = Object_Model::get_definition($this);
        // Check if the id configuration is set in the configuration_lang table.
        // Otherwise configuration is not set as translated configuration.
        if ($id !== null) {
            $id_translated = Db::read_only()->get_array((new Db_Query())->select(bq_sql(static::$definition['primary']))->from(bq_sql(static::$definition['table']) . '_lang')->where('`' . bq_sql(static::$definition['primary']) . '` = ' . (int) $id)->limit(1, 0));
            if (empty($id_translated)) {
                $id = null;
            }
        }
        parent::__construct($id, $id_lang);
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
        return $this->update($null_values);
    }
    /**
     * @param bool $nullValues
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        $ishtml = false;
        foreach ($this->value as $i18n_value) {
            if (Validate::is_clean_html($i18n_value)) {
                $ishtml = true;
                break;
            }
        }
        Configuration::update_value($this->name, $this->value, $ishtml);
        $last_insert = Db::read_only()->get_row((new Db_Query())->select('`id_configuration` AS `id`')->from('configuration')->where('`name` = \'' . p_sql($this->name) . '\''));
        if ($last_insert) {
            $this->id = $last_insert['id'];
        }
        return true;
    }
    /**
     * @param string $sqlJoin
     * @param string $sqlFilter
     * @param string $sqlSort
     * @param string $sqlLimit
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_webservice_object_list($sql_join, $sql_filter, $sql_sort, $sql_limit)
    {
        $query = '
		SELECT DISTINCT main.`' . $this->def['primary'] . '` FROM `' . _DB_PREFIX_ . $this->def['table'] . '` main
		' . $sql_join . '
		WHERE id_configuration IN
		(	SELECT id_configuration
			FROM ' . _DB_PREFIX_ . $this->def['table'] . '_lang
		) ' . $sql_filter . '
		' . ($sql_sort != '' ? $sql_sort : '') . '
		' . ($sql_limit != '' ? $sql_limit : '') . '
		';
        return Db::read_only()->get_array($query);
    }
}