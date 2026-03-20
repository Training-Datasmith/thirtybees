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
 * Class PrestaShopLoggerCore
 */
class Presta_Shop_Logger_Core extends Object_Model
{
    public const MAIL_ERROR = 'INTERNAL_EMAIL_ERROR';
    /**
     * @var array
     */
    protected static $is_present = [];
    /** @var int Log id */
    public $id_log;
    /** @var int Log severity */
    public $severity;
    /** @var int Error code */
    public $error_code;
    /** @var string Message */
    public $message;
    /** @var string Object type (eg. Order, Customer...) */
    public $object_type;
    /** @var int Object ID */
    public $object_id;
    /** @var int Object ID */
    public $id_employee;
    /** @var string Object creation date */
    public $date_add;
    /** @var string Object last modification date */
    public $date_upd;
    /** @var string hash code for this log object */
    protected $hash;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'log', 'primary' => 'id_log', 'fields' => ['severity' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true, 'size' => 1, 'signed' => true], 'error_code' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbType' => 'int(11)'], 'message' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => Object_Model::SIZE_TEXT], 'object_type' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'size' => 32], 'object_id' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'], 'id_employee' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'date_upd' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false]], 'keys' => ['log' => ['message' => ['type' => Object_Model::KEY, 'columns' => ['message', 'severity', 'error_code', 'object_type', 'object_id'], 'subParts' => [150]]]]];
    /**
     * add a log item to the database and send a mail if configured for this $severity
     *
     * @param string $message the log message
     * @param int $severity
     * @param int $errorCode
     * @param string $objectType
     * @param int $objectId
     * @param bool $allowDuplicate if set to true, can log several time the same information (not recommended)
     * @param int $idEmployee
     *
     * @return bool true if succeed
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function add_log($message, $severity = 1, $error_code = null, $object_type = null, $object_id = null, $allow_duplicate = false, $id_employee = null)
    {
        $log = new static();
        $log->severity = (int) $severity;
        $log->error_code = (int) $error_code;
        $log->message = $message ?: static::get_empty_message_text();
        $log->date_add = date('Y-m-d H:i:s');
        $log->date_upd = date('Y-m-d H:i:s');
        if ($id_employee === null && isset(Context::get_context()->employee) && Validate::is_loaded_object(Context::get_context()->employee)) {
            $id_employee = Context::get_context()->employee->id;
        }
        if ($id_employee !== null) {
            $log->id_employee = (int) $id_employee;
        }
        if (!empty($object_type) && !empty($object_id) && $object_type !== static::MAIL_ERROR) {
            $log->object_type = substr($object_type, 0, 31);
            $log->object_id = (int) $object_id;
        }
        if ($object_type !== static::MAIL_ERROR) {
            static::send_by_mail($log);
        }
        if ($allow_duplicate || !$log->_is_present()) {
            $res = $log->add();
            if ($res) {
                static::$is_present[$log->get_hash()] = isset(static::$is_present[$log->get_hash()]) ? static::$is_present[$log->get_hash()] + 1 : 1;
                return true;
            }
        }
        return false;
    }
    /**
     * Send e-mail to the shop owner only if the minimal severity level has been reached
     *
     * @param PrestaShopLoggerCore $log
     *
     * @throws PrestaShopException
     */
    public static function send_by_mail($log): void
    {
        if ((int) Configuration::get('PS_LOGS_BY_EMAIL') <= (int) $log->severity) {
            Mail::Send((int) Configuration::get('PS_LANG_DEFAULT'), 'log_alert', Mail::l('Log: You have a new alert from your shop', (int) Configuration::get('PS_LANG_DEFAULT')), [], Configuration::get('PS_SHOP_EMAIL'));
        }
    }
    /**
     * check if this log message already exists in database.
     *
     * @return true if exists
     *
     * @throws PrestaShopException
     */
    protected function _is_present()
    {
        $key = $this->get_hash();
        if (!isset(static::$is_present[$key])) {
            static::$is_present[$key] = Db::read_only()->get_value('SELECT COUNT(*)
				FROM `' . _DB_PREFIX_ . 'log`
				WHERE
					`message` = \'' . p_sql($this->message) . '\'
					AND `severity` = \'' . $this->severity . '\'
					AND `error_code` = \'' . $this->error_code . '\'
					AND `object_type` = \'' . p_sql($this->object_type) . '\'
					AND `object_id` = \'' . $this->object_id . '\'
				');
        }
        return static::$is_present[$key];
    }
    /**
     * Calculates hash key for current log entry
     *
     * @return string hash
     */
    public function get_hash()
    {
        if (empty($this->hash)) {
            $this->hash = md5($this->message . $this->severity . $this->error_code . $this->object_type . $this->object_id);
        }
        return $this->hash;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function erase_all_logs()
    {
        return Db::get_instance()->execute('TRUNCATE TABLE ' . _DB_PREFIX_ . 'log');
    }
    /**
     * This function is called when empty message is passed to Logger::addLog(). In that case thirtybees will log
     * information about the caller
     *
     * @return string
     */
    protected static function get_empty_message_text()
    {
        foreach (debug_backtrace() as $trace) {
            if (!str_contains($trace['file'], __FILE__)) {
                $file = str_replace(_PS_ROOT_DIR_, '', $trace['file']);
                $line = $trace['line'];
                return sprintf(Tools::display_error('Logger::addLog called with empty message at %s on line %s', false), $file, $line);
            }
        }
        return Tools::display_error('Logger::addLog called with empty message', false);
    }
}