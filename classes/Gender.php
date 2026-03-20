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
class Gender_Core extends Object_Model
{
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'gender', 'primary' => 'id_gender', 'primaryKeyDbType' => 'int(11)', 'multilang' => true, 'fields' => [
        'type' => ['type' => self::TYPE_INT, 'required' => false, 'dbType' => 'tinyint(1)', 'default' => 0],
        /* Lang fields */
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isString', 'required' => true, 'size' => 20],
    ], 'keys' => ['gender_lang' => ['id_gender' => ['type' => Object_Model::KEY, 'columns' => ['id_gender']]]]];
    /**
     * @var int
     */
    public $id_gender;
    /**
     * @var string|string[]
     */
    public $name;
    /**
     * @var int
     *
     * @deprecated Gender type is not used anymore, exists for BC only
     */
    public $type = 0;
    /**
     * GenderCore constructor.
     *
     * @param int|null $id
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
        $this->image_dir = _PS_GENDERS_DIR_;
    }
    /**
     * @param int|null $idLang
     *
     * @return PrestaShopCollection
     *
     * @throws PrestaShopException
     */
    public static function get_genders($id_lang = null)
    {
        if (is_null($id_lang)) {
            $id_lang = Context::get_context()->language->id;
        }
        return new Presta_Shop_Collection('Gender', $id_lang);
    }
    /**
     * @return array[]
     * @throws PrestaShopException
     */
    public static function get_icon_list(): array
    {
        $genders_icon = ['default' => ['src' => static::get_gender_image(null), 'alt' => 'Unknown']];
        foreach (static::get_genders() as $gender) {
            /** @var Gender $gender */
            $genders_icon[$gender->id] = ['src' => $gender->get_image(), 'alt' => $gender->name];
        }
        return $genders_icon;
    }
    /**
     * @param int|null $id
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function get_gender_image($id)
    {
        $id = (int) $id;
        if ($id && $source_image = Image_Manager::get_source_image(_PS_GENDERS_DIR_, $id)) {
            return str_replace(_PS_GENDERS_DIR_, _THEME_GENDERS_DIR_, $source_image);
        }
        return _THEME_GENDERS_DIR_ . 'Unknown.jpg';
    }
    /**
     * @param bool $useUnknown
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_image($use_unknown = false)
    {
        return static::get_gender_image($this->id);
    }
}