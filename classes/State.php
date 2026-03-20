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
 * Class StateCore
 */
class State_Core extends Object_Model
{
    /**
     * @var int Country id which state belongs
     */
    public $id_country;
    /**
     * @var int Zone id which state belongs
     */
    public $id_zone;
    /**
     * @var string 2 letters iso code
     */
    public $iso_code;
    /**
     * @var string Name
     */
    public $name;
    /**
     * @var bool Status for delivery
     */
    public $active = true;
    /**
     * @var int
     */
    public $tax_behavior;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'state', 'primary' => 'id_state', 'fields' => ['id_country' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'id_zone' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true], 'name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'required' => true, 'size' => 64], 'iso_code' => ['type' => self::TYPE_STRING, 'validate' => 'isStateIsoCode', 'required' => true, 'size' => 7], 'tax_behavior' => ['type' => self::TYPE_INT, 'dbType' => 'smallint(1)', 'dbDefault' => '0'], 'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0']], 'keys' => ['state' => ['id_country' => ['type' => Object_Model::KEY, 'columns' => ['id_country']], 'id_zone' => ['type' => Object_Model::KEY, 'columns' => ['id_zone']], 'name' => ['type' => Object_Model::KEY, 'columns' => ['name']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['fields' => ['id_zone' => ['xlink_resource' => 'zones'], 'id_country' => ['xlink_resource' => 'countries']]];
    /**
     * @param bool $idLang
     * @param bool $active
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_states($id_lang = false, $active = false)
    {
        return Db::read_only()->get_array((new Db_Query())->select('`id_state`, `id_country`, `id_zone`, `iso_code`, `name`, `active`')->from('state', 's')->where($active ? '`active` = 1' : '')->order_by('`name` ASC'));
    }
    /**
     * Get a state name with its ID
     *
     * @param int $idState Country ID
     *
     * @return string State name
     *
     * @throws PrestaShopException
     */
    public static function get_name_by_id($id_state)
    {
        if (!$id_state) {
            return false;
        }
        $cache_id = 'State::getNameById_' . (int) $id_state;
        if (!Cache::is_stored($cache_id)) {
            $result = Db::read_only()->get_value((new Db_Query())->select('`name`')->from('state')->where('`id_state` = ' . (int) $id_state));
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Get a state id with its name
     *
     * @param string $state State name
     *
     * @return int State ID
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_name($state)
    {
        if (empty($state)) {
            return false;
        }
        $cache_id = 'State::getIdByName_' . p_sql($state);
        if (!Cache::is_stored($cache_id)) {
            $result = (int) Db::read_only()->get_value((new Db_Query())->select('`id_state`')->from('state')->where('`name` = \'' . p_sql($state) . '\''));
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Get a state id with its iso code
     *
     * @param string $isoCode Iso code
     * @param int|null $idCountry
     *
     * @return int state id
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_iso($iso_code, $id_country = null)
    {
        return Db::read_only()->get_value((new Db_Query())->select('`id_state`')->from('state')->where('`iso_code` = \'' . p_sql($iso_code) . '\'')->where($id_country ? '`id_country` = ' . (int) $id_country : ''));
    }
    /**
     * @param int $idCountry
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_states_by_id_country($id_country)
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('state', 's')->where('s.`id_country` = ' . (int) $id_country));
    }
    /**
     * @param int $idState
     *
     * @return int
     *
     * @deprecated 1.1.0 counties not supported anymore
     */
    public static function has_counties($id_state)
    {
        Tools::display_as_deprecated();
        return 0;
    }
    /**
     * @param int $idState
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_id_zone($id_state)
    {
        return (int) Db::read_only()->get_value((new Db_Query())->select('`id_zone`')->from('state')->where('`id_state` = ' . (int) $id_state));
    }
    /**
     * Delete a state only if is not in use
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (!$this->is_used()) {
            // Database deletion
            $conn = Db::get_instance();
            $result = $conn->delete($this->def['table'], '`' . $this->def['primary'] . '` = ' . (int) $this->id);
            if (!$result) {
                return false;
            }
            // Database deletion for multilingual fields related to the object
            if (!empty($this->def['multilang'])) {
                $conn->delete(bq_sql($this->def['table']) . '_lang', '`' . $this->def['primary'] . '` = ' . (int) $this->id);
            }
            return $result;
        }
        return false;
    }
    /**
     * Check if a state is used
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_used()
    {
        return $this->count_used() > 0;
    }
    /**
     * Returns the number of utilisation of a state
     *
     * @return int count for this state
     *
     * @throws PrestaShopException
     */
    public function count_used()
    {
        return Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from('address')->where('`' . bq_sql(static::$definition['primary']) . '` = ' . (int) $this->id));
    }
    /**
     * @param array $idsStates
     * @param int $idZone
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function affect_zone_to_selection($ids_states, $id_zone)
    {
        // cast every array values to int (security)
        $ids_states = array_map(intval(...), $ids_states);
        return Db::get_instance()->update('state', ['id_zone' => (int) $id_zone], '`id_state` IN (' . implode(',', $ids_states) . ')');
    }
}