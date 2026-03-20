<?php

declare (strict_types=1);
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
namespace Thirtybees\Core\Tracking;

use Presta_Shop_Exception;
use RuntimeException;
use Translate;
/**
 * Class DataExtractor
 */
abstract class Data_Extractor_Core
{
    public const GROUP_ENVIRONMENT = 'environment';
    /**
     * Return extractor id
     * @return string
     */
    public function get_id()
    {
        $class = static::class;
        if (preg_match('#^.*\\\\([a-zA-Z]+)Extractor(Core)*$#', $this::class, $matches)) {
            return lcfirst($matches[1]);
        }
        throw new RuntimeException('Invariant: failed to resolve extractor ID for class ' . $class);
    }
    /**
     * Return list of extractor groups
     *
     * @return array
     */
    public static function get_groups()
    {
        return [static::GROUP_ENVIRONMENT => ['name' => Translate::get_admin_translation('Environment', 'AdminDataCollection'), 'extractors' => ['system', 'phpVersion', 'phpExtensions', 'serverSettings', 'db']]];
    }
    /**
     * Instantiates extractor
     *
     * @param int $id
     * @return DataExtractor
     * @throws PrestaShopException
     */
    public static function get_extractor($id)
    {
        $clazz = '\Thirtybees\Core\Tracking\Extractor\\' . ucfirst($id) . 'Extractor';
        if (!class_exists($clazz)) {
            throw new Presta_Shop_Exception("Extractor class for '{$id}' not found");
        }
        return new $clazz();
    }
    /**
     * Translates string
     *
     * @param string $str
     * @return string
     */
    protected function l($str)
    {
        return Translate::get_admin_translation($str, 'AdminDataCollection');
    }
    /**
     * Returns data name
     *
     * @return string
     */
    abstract public function get_name();
    /**
     * Returns detailed information about this data
     *
     * @return string
     */
    abstract public function get_description();
    /**
     * Extracts value
     *
     * @return mixed
     */
    abstract public function extract_value();
}