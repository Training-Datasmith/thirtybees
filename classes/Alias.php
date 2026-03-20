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
 * Class AliasCore
 */
class Alias_Core extends Object_Model
{
    /** @var string $alias */
    public $alias;
    /** @var string $search */
    public $search;
    /** @var bool $active */
    public $active = true;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'alias', 'primary' => 'id_alias', 'fields' => ['alias' => ['type' => self::TYPE_STRING, 'validate' => 'isValidSearch', 'required' => true, 'unique' => true, 'size' => 64], 'search' => ['type' => self::TYPE_STRING, 'validate' => 'isValidSearch', 'required' => true, 'size' => 255], 'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1']]];
    /**
     * AliasCore constructor.
     *
     * @param int|null $id
     * @param string|null $alias
     * @param string|null $search
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $alias = null, $search = null)
    {
        $this->def = static::get_definition($this);
        $this->set_definition_retrocompatibility();
        if ($id) {
            parent::__construct($id);
        } elseif ($alias && Validate::is_valid_search($alias)) {
            $alias = trim($alias);
            $search = trim($search ?? '');
            if (!Alias::is_feature_active()) {
                $this->alias = $alias;
                $this->search = $search;
            } else {
                $row = Db::read_only()->get_row((new Db_Query())->select('a.`id_alias`, a.`search`, a.`alias`')->from('alias', 'a')->where('`alias` = \'' . p_sql($alias) . '\'')->where('`active` = 1'));
                if ($row) {
                    $this->id = (int) $row['id_alias'];
                    $this->search = $search ?: $row['search'];
                    $this->alias = $row['alias'];
                } else {
                    $this->alias = $alias;
                    $this->search = $search;
                }
            }
        }
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
        $this->alias = Tools::replace_accented_chars($this->alias);
        $this->search = Tools::replace_accented_chars($this->search);
        if (parent::add($auto_date, $null_values)) {
            // Set cache of feature detachable to true
            Configuration::update_global_value('PS_ALIAS_FEATURE_ACTIVE', '1');
            return true;
        }
        return false;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete()
    {
        if (parent::delete()) {
            // Refresh cache of feature detachable
            Configuration::update_global_value('PS_ALIAS_FEATURE_ACTIVE', Alias::is_currently_used($this->def['table'], true));
            return true;
        }
        return false;
    }
    /**
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_aliases()
    {
        if (!Alias::is_feature_active()) {
            return '';
        }
        $aliases = Db::read_only()->get_array((new Db_Query())->select('a.`alias`')->from('alias', 'a')->where('a.`search` = \'' . p_sql($this->search) . '\''));
        $aliases = array_map(implode(...), $aliases);
        return implode(', ', $aliases);
    }
    /**
     * This method is allow to know if a feature is used or active
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_feature_active()
    {
        return Configuration::get('PS_ALIAS_FEATURE_ACTIVE');
    }
    /**
     * This method is allow to know if a alias exist for AdminImportController
     *
     * @param int $idAlias
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function alias_exists($id_alias)
    {
        if (!Alias::is_feature_active()) {
            return false;
        }
        $row = Db::read_only()->get_row((new Db_Query())->select('`id_alias`')->from('alias', 'a')->where('a.`id_alias` = ' . (int) $id_alias));
        return isset($row['id_alias']);
    }
}