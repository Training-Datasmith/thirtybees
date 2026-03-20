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
 * Class QuickAccessCore
 */
class Quick_Access_Core extends Object_Model
{
    /** @var string|string[] Name */
    public $name;
    /** @var string Link */
    public $link;
    /** @var bool New windows or not */
    public $new_window;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'quick_access', 'primary' => 'id_quick_access', 'multilang' => true, 'fields' => [
        'new_window' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'required' => true, 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'link' => ['type' => self::TYPE_STRING, 'validate' => 'isUrl', 'required' => true, 'size' => 255],
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCleanHtml', 'required' => true, 'size' => 32],
    ]];
    /**
     * Get all available quick_accesses
     *
     * @param int $idLang
     *
     * @return array QuickAccesses
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_quick_accesses($id_lang)
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from(bq_sql(static::$definition['table']), 'qa')->left_join(bq_sql(static::$definition['table']) . '_lang', 'qal', 'qa.`' . bq_sql(static::$definition['primary']) . '` = qal.`' . bq_sql(static::$definition['primary']) . '`')->order_by('`name` ASC')->where('qal.`id_lang` = ' . (int) $id_lang));
    }
    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function toggle_new_window()
    {
        $this->set_fields_to_update(['new_window' => true]);
        $this->new_window = !(int) $this->new_window;
        return $this->update(false);
    }
}