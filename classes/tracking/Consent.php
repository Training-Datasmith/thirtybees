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

use Context;
use Db;
use Db_Query;
use Object_Model;
use Presta_Shop_Database_Exception;
use Presta_Shop_Exception;
use Thirtybees\Core\Database\Read_Only_Connection;
use Thirtybees\Core\Initialization_Callback;
/**
 * Class ConsentCore
 */
class Consent_Core extends Object_Model implements Initialization_Callback
{
    public const CONSENT_ALL = 'all';
    public const PREFIX_GROUP = 'group_';
    public const PREFIX_EXTRACTOR = 'extractor_';
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'tracking_consent', 'primary' => 'id_tracking_consent', 'multishop' => false, 'fields' => ['id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true], 'identifier' => ['type' => self::TYPE_STRING, 'size' => 80, 'required' => true], 'consent' => ['type' => self::TYPE_BOOL, 'required' => true], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false]], 'keys' => ['tracking_consent' => ['identifier' => ['type' => Object_Model::UNIQUE_KEY, 'columns' => ['identifier']]]]];
    /**
     * @var string Employee that decided
     */
    public $id_employee;
    /**
     * @var string Information identifier
     */
    public $identifier;
    /**
     * @var bool Flat indicating if information can be send or not
     */
    public $consent;
    /**
     * @var string Object creation date
     */
    public $date_add;
    /**
     * @var string Object update date
     */
    public $date_upd;
    /**
     * Returns list of allowed extractors
     *
     * @return string[]
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_allowed_extractors()
    {
        $consents = static::get_consents(Db::read_only());
        $groups = Data_Extractor::get_groups();
        $allowed = [];
        foreach ($groups as $group_id => $group) {
            foreach ($group['extractors'] as $extractor_id) {
                if (static::extractor_allowed($group_id, $extractor_id, $consents)) {
                    $allowed[] = $extractor_id;
                }
            }
        }
        return $allowed;
    }
    /**
     * Return all consents
     *
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_consents(Read_Only_Connection $conn)
    {
        $consents = [];
        $result = $conn->get_array((new Db_Query())->select('identifier, consent')->from(static::$definition['table']));
        foreach ($result as $row) {
            $consents[$row['identifier']] = (bool) $row['consent'];
        }
        return $consents;
    }
    /**
     * Returns true, if extractor $extractorId from group $groupId is allowed
     *
     * Extractor is allowed to run, if
     *  - all data are allowed to be sent (consents contains 'all' key)
     *  - entire group is allowed to be send (consents contains 'group_<name>' key
     *  - extractor is specifically allowed (consents contains 'extractor_<name>' key
     *
     * @param int $groupId
     * @param int $extractorId
     * @param array $consents
     * @return bool
     */
    protected static function extractor_allowed($group_id, $extractor_id, $consents)
    {
        if (static::has_consent(static::CONSENT_ALL, $consents)) {
            return true;
        }
        if (static::has_consent(static::PREFIX_GROUP . $group_id, $consents)) {
            return true;
        }
        return static::has_consent(static::PREFIX_EXTRACTOR . $extractor_id, $consents);
    }
    /**
     * Returns true, if $key exists in $consents and is set to true
     *
     * @param string $key
     * @param array $consents
     * @return bool
     */
    protected static function has_consent($key, $consents)
    {
        return array_key_exists($key, $consents) && !!$consents[$key];
    }
    /**
     * Callback method to initialize class
     *
     * @throws PrestaShopException
     */
    public static function initialization_callback(Db $conn): void
    {
        $consents = static::get_consents($conn);
        $groups = Data_Extractor::get_groups();
        static::ensure_consent_exists(static::CONSENT_ALL, $consents);
        foreach ($groups as $group_id => $group) {
            static::ensure_consent_exists(static::PREFIX_GROUP . $group_id, $consents);
            foreach ($group['extractors'] as $extractor_id) {
                static::ensure_consent_exists(static::PREFIX_EXTRACTOR . $extractor_id, $consents);
            }
        }
    }
    /**
     * Ensures that consent with identifier $key exists in database
     *
     * @param string $key
     * @param array $consents
     *
     * @throws PrestaShopException
     */
    protected static function ensure_consent_exists($key, $consents)
    {
        if (!array_key_exists($key, $consents)) {
            $consent = new static();
            $consent->id_employee = Context::get_context()->employee->id;
            $consent->identifier = $key;
            $consent->consent = true;
            $consent->add();
        }
    }
}