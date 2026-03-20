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
use Thirtybees\Core\Initialization_Callback;
/**
 * Class ConfigurationKPICore
 */
class Configuration_Kpi_Core extends Configuration implements Initialization_Callback
{
    /**
     * @var array
     */
    public static $definition_backup;
    public static function set_kpi_definition(): void
    {
        Configuration_Kpi::$definition_backup = Configuration::$definition;
        Configuration::$definition['table'] = 'configuration_kpi';
        Configuration::$definition['primary'] = 'id_configuration_kpi';
    }
    public static function unset_kpi_definition(): void
    {
        Configuration::$definition = Configuration_Kpi::$definition_backup;
    }
    /**
     * @param string $key
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_name($key, $id_shop_group = null, $id_shop = null)
    {
        Configuration_Kpi::set_kpi_definition();
        $r = parent::get_id_by_name($key, $id_shop_group, $id_shop);
        Configuration_Kpi::unset_kpi_definition();
        return $r;
    }
    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function load_configuration(): void
    {
        Configuration_Kpi::set_kpi_definition();
        parent::load_configuration();
        Configuration_Kpi::unset_kpi_definition();
    }
    /**
     * @param string $key
     * @param int|null $idLang
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public static function get($key, $id_lang = null, $id_shop_group = null, $id_shop = null)
    {
        Configuration_Kpi::set_kpi_definition();
        $r = parent::get($key, $id_lang, $id_shop_group, $id_shop);
        Configuration_Kpi::unset_kpi_definition();
        return $r;
    }
    /**
     * @param string $key
     * @param int|null $idLang
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public static function get_global_value($key, $id_lang = null)
    {
        Configuration_Kpi::set_kpi_definition();
        $r = parent::get_global_value($key, $id_lang);
        Configuration_Kpi::unset_kpi_definition();
        return $r;
    }
    /**
     * @param string $key
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function get_int($key, $id_shop_group = null, $id_shop = null)
    {
        Configuration_Kpi::set_kpi_definition();
        $r = parent::get_int($key, $id_shop_group, $id_shop);
        Configuration_Kpi::unset_kpi_definition();
        return $r;
    }
    /**
     * @param array $keys
     * @param int|null $idLang
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function get_multiple($keys, $id_lang = null, $id_shop_group = null, $id_shop = null)
    {
        Configuration_Kpi::set_kpi_definition();
        $r = parent::get_multiple($keys, $id_lang, $id_shop_group, $id_shop);
        Configuration_Kpi::unset_kpi_definition();
        return $r;
    }
    /**
     * @param string $key
     * @param int|null $idLang
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function has_key($key, $id_lang = null, $id_shop_group = null, $id_shop = null)
    {
        Configuration_Kpi::set_kpi_definition();
        $r = parent::has_key($key, $id_lang, $id_shop_group, $id_shop);
        Configuration_Kpi::unset_kpi_definition();
        return $r;
    }
    /**
     * @param string $key
     * @param mixed $values
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @throws PrestaShopException
     */
    public static function set($key, $values, $id_shop_group = null, $id_shop = null): void
    {
        Configuration_Kpi::set_kpi_definition();
        parent::set($key, $values, $id_shop_group, $id_shop);
        Configuration_Kpi::unset_kpi_definition();
    }
    /**
     * @param string $key
     * @param mixed $values
     * @param bool $html
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function update_global_value($key, $values, $html = false)
    {
        Configuration_Kpi::set_kpi_definition();
        $r = parent::update_global_value($key, $values, $html);
        Configuration_Kpi::unset_kpi_definition();
        return $r;
    }
    /**
     * @param string $key
     * @param mixed $values
     * @param bool $html
     * @param int|null $idShopGroup
     * @param int|null $idShop
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function update_value($key, $values, $html = false, $id_shop_group = null, $id_shop = null)
    {
        Configuration_Kpi::set_kpi_definition();
        $r = parent::update_value($key, $values, $html, $id_shop_group, $id_shop);
        Configuration_Kpi::unset_kpi_definition();
        return $r;
    }
    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function delete_by_name($key)
    {
        Configuration_Kpi::set_kpi_definition();
        $r = parent::delete_by_name($key);
        Configuration_Kpi::unset_kpi_definition();
        return $r;
    }
    /**
     * @param string $key
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function delete_from_context($key): void
    {
        Configuration_Kpi::set_kpi_definition();
        parent::delete_from_context($key);
        Configuration_Kpi::unset_kpi_definition();
    }
    /**
     * @param string $key
     * @param int $idLang
     * @param int $context
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function has_context($key, $id_lang, $context)
    {
        Configuration_Kpi::set_kpi_definition();
        $r = parent::has_context($key, $id_lang, $context);
        Configuration_Kpi::unset_kpi_definition();
        return $r;
    }
    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_overriden_by_current_context($key)
    {
        Configuration_Kpi::set_kpi_definition();
        $r = parent::is_overriden_by_current_context($key);
        Configuration_Kpi::unset_kpi_definition();
        return $r;
    }
    /**
     * @param string $key
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_lang_key($key)
    {
        Configuration_Kpi::set_kpi_definition();
        $r = parent::is_lang_key($key);
        Configuration_Kpi::unset_kpi_definition();
        return $r;
    }
    /**
     * @param int $idShopGroup
     * @param int $idShop
     *
     * @return string
     */
    protected static function sql_restriction($id_shop_group, $id_shop)
    {
        Configuration_Kpi::set_kpi_definition();
        $r = parent::sql_restriction($id_shop_group, $id_shop);
        Configuration_Kpi::unset_kpi_definition();
        return $r;
    }
    /**
     *
     * @throws PrestaShopException
     */
    public static function initialization_callback(Db $conn): void
    {
        $conn->delete('configuration_kpi_lang', 'IFNULL(value, "") = ""');
    }
}