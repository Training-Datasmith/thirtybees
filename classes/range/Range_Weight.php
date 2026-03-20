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
 * Class RangeWeightCore
 */
class Range_Weight_Core extends Object_Model
{
    /** @var int $id_carrier */
    public $id_carrier;
    /** @var float $delimiter1 */
    public $delimiter1;
    /** @var float $delimiter2 */
    public $delimiter2;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'range_weight', 'primary' => 'id_range_weight', 'fields' => ['id_carrier' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true], 'delimiter1' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'required' => true], 'delimiter2' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'required' => true]], 'keys' => ['range_weight' => ['id_carrier' => ['type' => Object_Model::UNIQUE_KEY, 'columns' => ['id_carrier', 'delimiter1', 'delimiter2']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectNodeName' => 'weight_range', 'objectsNodeName' => 'weight_ranges', 'fields' => ['id_carrier' => ['xlink_resource' => 'carriers']]];
    /**
     * Get all available price ranges
     *
     * @param int $idCarrier
     *
     * @return array Ranges
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_ranges($id_carrier)
    {
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('range_weight')->where('`id_carrier` = ' . (int) $id_carrier)->order_by('`delimiter1` ASC'));
    }
    /**
     * @param int $idCarrier
     * @param float $delimiter1
     * @param float $delimiter2
     * @param int|null $idReference
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public static function range_exist($id_carrier, $delimiter1, $delimiter2, $id_reference = null)
    {
        return Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from('range_weight', 'rw')->join(is_null($id_carrier) && $id_reference ? ' INNER JOIN `' . _DB_PREFIX_ . 'carrier` c on (rw.`id_carrier` = c.`id_carrier`)' : '')->where($id_carrier ? '`id_carrier` = ' . (int) $id_carrier : '')->where(is_null($id_carrier) && $id_reference ? 'c.`id_reference` = ' . (int) $id_reference : '')->where(is_null($id_carrier) && $id_reference ? 'c.`id_reference` = ' . (int) $id_reference : '')->where('`delimiter1` = ' . (float) $delimiter1)->where('`delimiter2` = ' . (float) $delimiter2));
    }
    /**
     * @param int $idCarrier
     * @param float $delimiter1
     * @param float $delimiter2
     * @param int|null $idRang
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public static function is_overlapping($id_carrier, $delimiter1, $delimiter2, $id_rang = null)
    {
        return Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from('range_weight')->where('`id_carrier` = ' . (int) $id_carrier)->where('(`delimiter1` >= ' . (float) $delimiter1 . ' AND `delimiter1` < ' . (float) $delimiter2 . ') OR (`delimiter2` > ' . (float) $delimiter1 . ' AND `delimiter2` < ' . (float) $delimiter2 . ') OR (' . (float) $delimiter1 . ' > `delimiter1` AND ' . (float) $delimiter1 . ' < `delimiter2`) OR (' . (float) $delimiter2 . ' < `delimiter1` AND ' . (float) $delimiter2 . ' > `delimiter2`)')->where(!is_null($id_rang) ? '`id_range_weight` != ' . (int) $id_rang : ''));
    }
}