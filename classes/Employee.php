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
use Guzzle_Http\Client;
use Thirtybees\Core\Initialization_Callback;
/**
 * Class EmployeeCore
 */
class Employee_Core extends Object_Model implements Initialization_Callback
{
    /**
     * @var int Determine employee profile
     */
    public $id_profile;
    /**
     * @var int employee language
     */
    public $id_lang;
    /**
     * @var string Lastname
     */
    public $lastname;
    /**
     * @var string Firstname
     */
    public $firstname;
    /**
     * @var string e-mail
     */
    public $email;
    /**
     * @var string Password
     */
    public $passwd;
    /**
     * @var string Password
     */
    public $last_passwd_gen;
    /**
     * @var string $stats_date_from
     */
    public $stats_date_from;
    /**
     * @var string $stats_date_to
     */
    public $stats_date_to;
    /**
     * @var string $stats_compare_from
     */
    public $stats_compare_from;
    /**
     * @var string $stats_compare_to
     */
    public $stats_compare_to;
    /**
     * @var int $stats_compare_option
     */
    public $stats_compare_option = 1;
    /**
     * @var string $preselect_date_range
     */
    public $preselect_date_range;
    /**
     * @var string Display back office background in the specified color
     */
    public $bo_color;
    /**
     * @var int
     */
    public $default_tab;
    /**
     * @var string employee's chosen theme
     */
    public $bo_theme;
    /**
     * @var string employee's chosen css file
     */
    public $bo_css = 'admin-theme.css';
    /**
     * @var int employee desired screen width
     */
    public $bo_width;
    /**
     * @var bool, false
     */
    public $bo_menu = 1;
    /**
     * @var bool
     */
    public $bo_show_screencast = false;
    /**
     * @var bool Status
     */
    public $active = 1;
    /**
     * @var bool Optin status
     */
    public $optin = 1;
    /**
     * @var int[]
     */
    protected $associated_shops = [];
    /**
     * @var string
     */
    public $last_connection_date;
    /**
     * @var string stored HMAC-SHA256 signature of security-critical fields
     */
    public $signature;
    /**
     * @var string
     */
    public $campaign_disabled;
    /**
     * @var Notification|null
     */
    protected $notification;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'employee', 'primary' => 'id_employee', 'fields' => ['id_profile' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'required' => true], 'id_lang' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'required' => true, 'dbDefault' => '0'], 'lastname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32], 'firstname' => ['type' => self::TYPE_STRING, 'validate' => 'isName', 'required' => true, 'size' => 32], 'email' => ['type' => self::TYPE_STRING, 'validate' => 'isEmail', 'required' => true, 'size' => 128], 'passwd' => ['type' => self::TYPE_STRING, 'validate' => 'isPasswdAdmin', 'required' => true, 'size' => 60], 'last_passwd_gen' => ['type' => self::TYPE_DATE, 'dbType' => 'timestamp', 'dbDefault' => Object_Model::DEFAULT_CURRENT_TIMESTAMP], 'stats_date_from' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbType' => 'date'], 'stats_date_to' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbType' => 'date'], 'stats_compare_from' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbType' => 'date'], 'stats_compare_to' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbType' => 'date'], 'stats_compare_option' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'size' => 1, 'dbType' => 'int(1) unsigned', 'dbDefault' => '1'], 'preselect_date_range' => ['type' => self::TYPE_STRING, 'size' => 32], 'bo_color' => ['type' => self::TYPE_STRING, 'validate' => 'isColor', 'size' => 32], 'bo_theme' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 32], 'bo_css' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64], 'default_tab' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbDefault' => '0'], 'bo_width' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbDefault' => '0'], 'bo_menu' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'], 'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0'], 'optin' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '1'], 'last_connection_date' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => true], 'signature' => ['type' => self::TYPE_STRING, 'validate' => 'isSha256', 'size' => 64, 'copy_post' => false], 'campaign_disabled' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => false, 'dbNullable' => true]], 'keys' => ['employee' => ['employee_login' => ['type' => Object_Model::KEY, 'columns' => ['email', 'passwd']], 'id_employee_passwd' => ['type' => Object_Model::KEY, 'columns' => ['id_employee', 'passwd']], 'id_profile' => ['type' => Object_Model::KEY, 'columns' => ['id_profile']]], 'employee_shop' => ['id_shop' => ['type' => Object_Model::KEY, 'columns' => ['id_shop']]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['fields' => ['id_lang' => ['xlink_resource' => 'languages'], 'last_passwd_gen' => ['setter' => null], 'stats_date_from' => ['setter' => null], 'stats_date_to' => ['setter' => null], 'stats_compare_from' => ['setter' => null], 'stats_compare_to' => ['setter' => null], 'passwd' => ['setter' => 'setWsPasswd']]];
    /**
     * EmployeeCore constructor.
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
        parent::__construct($id, null, $id_shop);
        if (!is_null($id_lang)) {
            $this->id_lang = (int) (Language::get_language($id_lang) !== false) ? $id_lang : Configuration::get('PS_LANG_DEFAULT');
        }
        if ($this->id) {
            $this->associated_shops = $this->get_associated_shops();
        }
        $this->image_dir = _PS_EMPLOYEE_IMG_DIR_;
    }
    /**
     * Return list of employees
     *
     * @param bool $activeOnly Filter employee by active status
     *
     * @return array Employees
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_employees($active_only = true)
    {
        $sql = new Db_Query();
        $sql->select('`id_employee`, `firstname`, `lastname`');
        $sql->from(bq_sql(static::$definition['table']));
        if ($active_only) {
            $sql->where('`active` = 1');
        }
        $sql->order_by('`lastname` ASC');
        return Db::read_only()->get_array($sql);
    }
    /**
     * @param string $email
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function employee_exists($email)
    {
        return (bool) Db::read_only()->get_value((new Db_Query())->select('`id_employee`')->from('employee')->where('`email` = \'' . p_sql($email) . '\''));
    }
    /**
     * @param int $idProfile
     * @param bool $activeOnly
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_employees_by_profile($id_profile, $active_only = false)
    {
        $sql = new Db_Query();
        $sql->select('*');
        $sql->from(bq_sql(static::$definition['table']));
        $sql->where('`id_profile` = ' . (int) $id_profile);
        if ($active_only) {
            $sql->where('`active` = 1');
        }
        return Db::read_only()->get_array($sql);
    }
    /**
     * @param int $idEmployee
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function set_last_connection_date($id_employee)
    {
        $id_employee = (int) $id_employee;
        if ($id_employee) {
            return Db::get_instance()->update(bq_sql(static::$definition['table']), ['last_connection_date' => date('Y-m-d H:i:s')], '`id_employee` = ' . (int) $id_employee);
        }
        return false;
    }
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_fields()
    {
        if (empty($this->stats_date_from) || $this->stats_date_from == '0000-00-00') {
            $this->stats_date_from = date('Y-m-d', strtotime('-1 month'));
        }
        if (empty($this->stats_compare_from) || $this->stats_compare_from == '0000-00-00') {
            $this->stats_compare_from = null;
        }
        if (empty($this->stats_date_to) || $this->stats_date_to == '0000-00-00') {
            $this->stats_date_to = date('Y-m-d');
        }
        if (empty($this->stats_compare_to) || $this->stats_compare_to == '0000-00-00') {
            $this->stats_compare_to = null;
        }
        return parent::get_fields();
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = true)
    {
        $this->last_passwd_gen = date('Y-m-d H:i:s', strtotime('-' . Configuration::get('PS_PASSWD_TIME_BACK') . 'minutes'));
        $this->save_optin();
        $this->update_text_direction();
        $result = parent::add($auto_date, $null_values);
        return $this->update_signature() && $result;
    }
    /**
     * Subscribe to the thirty bees newsletter. Also resets $this->optin on
     * failure.
     *
     * @return bool Wether un/registration was successful.
     *
     * @throws PrestaShopException
     */
    protected function save_optin()
    {
        $success = true;
        if (!defined('TB_INSTALLATION_IN_PROGRESS')) {
            if ($this->optin && $this->email) {
                $context = Context::get_context();
                $guzzle = new Client(['base_uri' => Configuration::get_api_server(), 'timeout' => 20, 'verify' => Configuration::get_ssl_trust_store()]);
                try {
                    $body = $guzzle->post('/newsletter/', ['json' => ['email' => $this->email, 'fname' => $this->firstname, 'lname' => $this->lastname, 'activity' => Configuration::get('PS_SHOP_ACTIVITY'), 'country' => $context->country->iso_code, 'language' => $context->language->iso_code, 'URL' => $context->shop->get_base_url()], 'headers' => ['X-SID' => Configuration::get_server_tracking_id()]])->get_body();
                    if ((string) $body) {
                        // Service itself wasn't successful.
                        $success = false;
                        $this->optin = false;
                    }
                } catch (Throwable) {
                    $success = false;
                    $this->optin = false;
                }
            }
        }
        return $success;
    }
    /**
     * Deletes this employee
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function delete()
    {
        $id = (int) $this->id;
        if ($id) {
            Db::get_instance()->delete('employee_notification', 'id_employee = ' . $id);
        }
        return parent::delete();
    }
    /**
     * @throws PrestaShopException
     */
    protected function update_text_direction()
    {
        if (defined('_PS_ADMIN_DIR_')) {
            $path = _PS_ADMIN_DIR_ . '/themes/' . $this->bo_theme . '/css/';
        } else {
            // Probably installation in progress.
            $path = _PS_ROOT_DIR_ . '/admin/themes/' . $this->bo_theme . '/css/';
            if (!is_dir($path)) {
                $path = _PS_ROOT_DIR_ . '/admin-dev/themes/' . $this->bo_theme . '/css/';
                if (!is_dir($path)) {
                    // Give up.
                    return;
                }
            }
        }
        $language = new Language($this->id_lang);
        if ($language->is_rtl && !strpos($this->bo_css, '_rtl')) {
            $bo_css = preg_replace('/^(.*)\.css$/', '$1_rtl.css', $this->bo_css);
            $bo_css = str_replace('schemes/', 'schemes_rtl/', $bo_css);
            if (file_exists($path . $bo_css)) {
                $this->bo_css = $bo_css;
            }
        } elseif (!$language->is_rtl && strpos($this->bo_css, '_rtl')) {
            $bo_css = str_replace('_rtl', '', $this->bo_css);
            if (file_exists($path . $bo_css)) {
                $this->bo_css = $bo_css;
            }
        }
    }
    /**
     * Update the database record. Also used by AdminDashboardController for
     * newsletter registration.
     *
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        $success = true;
        if (empty($this->stats_date_from) || $this->stats_date_from == '0000-00-00') {
            $this->stats_date_from = date('Y-m-d');
        }
        if (empty($this->stats_date_to) || $this->stats_date_to == '0000-00-00') {
            $this->stats_date_to = date('Y-m-d');
        }
        $current_employee = new Employee((int) $this->id);
        if ($current_employee->optin != $this->optin || $current_employee->email != $this->email || !Configuration::get('TB_STORE_REGISTERED')) {
            $success = $this->save_optin();
        }
        $this->update_text_direction();
        $success = parent::update($null_values) && $success;
        return $this->update_signature() && $success;
    }
    /**
     * Return employee instance from its e-mail (optionally check password)
     *
     * @param string $email E-mail
     * @param string $plainTextPassword Password is also checked if specified
     * @param bool $activeOnly Filter employee by active status
     *
     * @return static|bool Employee instance
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_by_email($email, $plain_text_password = null, $active_only = true)
    {
        if (!Validate::is_email($email) || $plain_text_password && !Validate::is_passwd_admin($plain_text_password)) {
            return false;
        }
        $sql = new Db_Query();
        $sql->select('*');
        $sql->from('employee');
        $sql->where('`email` = \'' . p_sql($email) . '\'');
        if ($active_only) {
            $sql->where('`active` = 1');
        }
        $result = Db::read_only()->get_row($sql);
        if (!$result) {
            return false;
        }
        // verify that stored password/email/profile/signature was not tampered with
        $employee_id = (int) $result['id_employee'];
        $profile_id = (int) $result['id_profile'];
        $stored_password = $result['passwd'];
        $stored_email = $result['email'];
        $stored_signature = $result['signature'];
        $calculated_signature = static::calculate_signature($employee_id, $profile_id, $stored_email, $stored_password);
        if ($stored_signature !== $calculated_signature) {
            return false;
        }
        if ($plain_text_password && !password_verify($plain_text_password, (string) $stored_password)) {
            // Check if it matches the legacy md5 hashing and, if it does, rehash it.
            if (Validate::is_md5($stored_password) && $stored_password === md5(_COOKIE_KEY_ . $plain_text_password)) {
                $new_password = Tools::hash($plain_text_password);
                $new_signature = static::calculate_signature($employee_id, $profile_id, $stored_email, $new_password);
                Db::get_instance()->update(bq_sql(static::$definition['table']), ['passwd' => p_sql($new_password), 'signature' => p_sql($new_signature)], 'id_employee = ' . (int) $result['id_employee']);
                $result['passwd'] = $new_password;
                $result['signature'] = $new_signature;
            } else {
                return false;
            }
        }
        $this->id = $employee_id;
        $this->id_profile = $result['id_profile'];
        foreach ($result as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
        return $this;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_last_admin()
    {
        return $this->is_super_admin() && Employee::count_profile($this->id_profile, true) == 1 && $this->active;
    }
    /**
     * Check if current employee is super administrator
     *
     * @return bool
     */
    public function is_super_admin()
    {
        return $this->id_profile == _PS_ADMIN_PROFILE_;
    }
    /**
     * @param int $idProfile
     * @param bool $activeOnly
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public static function count_profile($id_profile, $active_only = false)
    {
        $sql = new Db_Query();
        $sql->select('COUNT(*)');
        $sql->from(bq_sql(static::$definition['table']));
        $sql->where('`id_profile` = ' . (int) $id_profile);
        if ($active_only) {
            $sql->where('`active` = 1');
        }
        return Db::read_only()->get_value($sql);
    }
    /**
     * @param string $plainTextPassword
     *
     * @return bool
     */
    public function set_ws_passwd($plain_text_password)
    {
        if ($this->id != 0) {
            if ($this->passwd != $plain_text_password) {
                $this->passwd = Tools::hash($plain_text_password);
            }
        } else {
            $this->passwd = Tools::hash($plain_text_password);
        }
        return true;
    }
    /**
     * Check employee informations saved into cookie and return employee validity
     *
     * @return bool employee validity
     *
     * @throws PrestaShopException
     */
    public function is_logged_back()
    {
        if (!Cache::is_stored('isLoggedBack' . $this->id)) {
            /* Employee is valid only if it can be load and if cookie password is the same as database one */
            $result = $this->id && Validate::is_unsigned_id($this->id) && Employee::check_password($this->id, Context::get_context()->cookie->passwd) && (!isset(Context::get_context()->cookie->remote_addr) || Context::get_context()->cookie->remote_addr == ip2long(Tools::get_remote_addr()) || !Configuration::get('PS_COOKIE_CHECKIP'));
            Cache::store('isLoggedBack' . $this->id, $result);
            return $result;
        }
        return Cache::retrieve('isLoggedBack' . $this->id);
    }
    /**
     * Check if employee password is the right one
     *
     * @param int $idEmployee
     * @param string $hashedPassword Password
     *
     * @return bool result
     *
     * @throws PrestaShopException
     */
    public static function check_password($id_employee, $hashed_password)
    {
        $sql = new Db_Query();
        $sql->select('`id_employee`');
        $sql->from('employee');
        $sql->where('`id_employee` = ' . (int) $id_employee);
        $sql->where('`active` = 1');
        $sql->where('`passwd` = \'' . p_sql($hashed_password) . '\'');
        return (bool) Db::read_only()->get_value($sql);
    }
    /**
     * Logout
     *
     * @throws PrestaShopException
     */
    public function logout(): void
    {
        if (isset(Context::get_context()->cookie)) {
            Context::get_context()->cookie->delete();
            Context::get_context()->cookie->write();
        }
        $this->id = null;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function favorite_modules_list()
    {
        return Db::read_only()->get_array((new Db_Query())->select('module')->from('module_preference')->where('`id_employee` = ' . (int) $this->id)->where('`favorite` = 1')->where('`interest` = 1 OR `interest` IS NULL'));
    }
    /**
     * Check if the employee is associated to a specific shop
     *
     * @param int $idShop
     *
     * @return bool
     */
    public function has_auth_on_shop($id_shop)
    {
        if ($this->is_super_admin()) {
            return true;
        }
        return in_array($id_shop, $this->associated_shops);
    }
    /**
     * Check if the employee is associated to a specific shop group
     *
     * @param int $idShopGroup
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function has_auth_on_shop_group($id_shop_group)
    {
        if ($this->is_super_admin()) {
            return true;
        }
        foreach ($this->associated_shops as $id_shop) {
            if ($id_shop_group == Shop::get_group_from_shop($id_shop, true)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Get default id_shop with auth for current employee
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function get_default_shop_id()
    {
        if ($this->is_super_admin() || in_array(Configuration::get('PS_SHOP_DEFAULT'), $this->associated_shops)) {
            return Configuration::get('PS_SHOP_DEFAULT');
        }
        return $this->associated_shops[0];
    }
    /**
     * @return string
     *
     * @throws PrestaShopException
     */
    public function get_image()
    {
        if ($source_image = Image_Manager::get_source_image(_PS_EMPLOYEE_IMG_DIR_, $this->id)) {
            return str_replace(_PS_CORE_DIR_, '', $source_image);
        }
        return Context::get_context()->link->get_media_link(_PS_IMG_ . 'admin/employees_xl.png');
    }
    /**
     * @param string $type
     *
     * @return int
     *
     * @throws PrestaShopException
     * @deprecated since 1.4.0
     */
    public function get_last_elements_for_notify($type)
    {
        Tools::display_as_deprecated();
        return $this->get_notification()->get_last_seen_id($type);
    }
    /**
     * Returns Notification object associated with this employee
     *
     * @return Notification
     * @throws PrestaShopException
     */
    public function get_notification()
    {
        if (is_null($this->notification)) {
            $this->notification = new Notification($this);
        }
        return $this->notification;
    }
    /**
     * Returns true, if this employee has access to $tabId with $permission level
     *
     * @param int|string $tab either tab ID or controller name
     * @param string $permission permission level
     * @return bool
     * @throws PrestaShopException
     */
    public function has_access($tab, $permission)
    {
        if (!Profile::is_valid_permission($permission)) {
            throw new Presta_Shop_Exception('Invalid permission type');
        }
        $tab_id = (int) $tab;
        if (!$tab_id && is_string($tab)) {
            $tab_id = (int) Tab::get_id_from_class_name($tab);
        }
        $tab_access = Profile::get_profile_access($this->id_profile, $tab_id);
        return (bool) $tab_access[$permission];
    }
    /**
     * Calculates HMAC-SHA256 signature
     *
     * @param int $profileId,
     *
     * @return string
     */
    protected static function calculate_signature(int $employee_id, int $profile_id, string $email, string $password)
    {
        return Tools::signature($employee_id . $email . $profile_id . $password);
    }
    /**
     * Updates HMAC-SHA256 signature stored inside database
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function update_signature()
    {
        $id = (int) $this->id;
        if ($id) {
            $signature = static::calculate_signature($id, (int) $this->id_profile, $this->email, $this->passwd);
            if ($signature !== $this->signature) {
                return Db::get_instance()->update(bq_sql(static::$definition['table']), ['signature' => p_sql($signature)], 'id_employee = ' . (int) $id);
            }
            return true;
        }
        return false;
    }
    /**
     * @throws PrestaShopException
     */
    public static function initialization_callback(Db $conn): void
    {
        // if signature is missing/empty, calculate and save it
        $employees = new Presta_Shop_Collection('Employee');
        $employees->sql_where('COALESCE(`signature`, "") = ""');
        /** @var Employee $employee */
        foreach ($employees as $employee) {
            $employee->update_signature();
        }
    }
}