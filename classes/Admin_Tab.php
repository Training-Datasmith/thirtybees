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
use Thirtybees\Core\Dependency_Injection\Service_Locator;
/**
 * @deprecated 1.0.0
 */
abstract class Admin_Tab_Core
{
    /**
     * @var string
     */
    public static $current_index;
    /**
     * @var string[]
     */
    public static $tab_parenting = ['AdminCms' => 'AdminCmsContent', 'AdminCmsCategories' => 'AdminCmsContent', 'AdminOrdersStates' => 'AdminStatuses', 'AdminAttributeGenerator' => 'AdminProducts', 'AdminAttributes' => 'AdminAttributesGroups', 'AdminFeaturesValues' => 'AdminFeatures', 'AdminReturnStates' => 'AdminStatuses', 'AdminStatsTab' => 'AdminStats'];
    /** @var int Tab id */
    public $id = -1;
    /** @var string Associated table name */
    public $table;
    /** @var string Tab name */
    public $class_name;
    /** @var string Security token */
    public $token;
    /** @var bool Automatically join language table if true */
    public $lang = false;
    /** @var bool Tab Automatically displays edit/delete icons if true */
    public $edit = false;
    /** @var string|bool Tab Automatically displays view icon if true */
    public $view = false;
    /** @var bool Tab Automatically displays delete icon if true */
    public $delete = false;
    /** @var bool Table records are not deleted but marked as deleted */
    public $deleted = false;
    /** @var bool Tab Automatically displays duplicate icon if true */
    public $duplicate = false;
    /** @var bool Content line is clickable if true */
    public $no_link = false;
    /** @var bool select other required fields */
    public $required_database = false;
    /** @var bool Tab Automatically displays '$color' as background color on listing if true */
    public $color_on_background = false;
    /** @var array Name and directory where class image are located */
    public $field_image_settings = [];
    /** @var string Image type */
    public $image_type;
    /** @var array Fields to display in list */
    public $fields_display = [];
    /**
     * @var string|null
     */
    public $option_title;
    /** @var string shop */
    public $shop_link_type;
    /** @var bool */
    public $shop_share_datas = false;
    /** @var array Errors displayed after post processing */
    public $_errors = [];
    /** @var array tabAccess */
    public $tab_access;
    /** @var string specificConfirmDelete */
    public $specific_confirm_delete;
    /**
     * @var Smarty
     */
    public $smarty;
    /**
     * @var array
     */
    public $_fields_options = [];
    /**
     * @var array
     */
    public $options_list = [];
    /**
     * @var Context
     */
    public $context;
    /**
     * @var bool
     */
    public $ajax = false;
    /**
     * if true, ajax-tab will not wait 1 sec
     *
     * @var bool
     */
    public $ignore_sleep = false;
    /** @var string Object identifier inside the associated table */
    protected $identifier = false;
    /** @var string Add fields into data query to display list */
    protected $_select;
    /** @var string Join tables into data query to display list */
    protected $_join;
    /** @var string Add conditions into data query to display list */
    protected $_where;
    /** @var string Group rows into data query to display list */
    protected $_group;
    /** @var string Having rows into data query to display list */
    protected $_having;
    /** @var array|false Cache for query results */
    protected $_list = [];
    /** @var int Number of results in list */
    protected $_list_total = 0;
    /** @var string WHERE clause determined by filter fields */
    protected $_filter;
    /** @var string HAVING clause */
    protected $_filter_having;
    /** @var array Temporary SQL table WHERE clause determinated by filter fields */
    protected $_tmp_table_filter = '';
    /** @var array Number of results in list per page (used in select field) */
    protected $_pagination = [20, 50, 100, 300, 1000];
    /** @var string ORDER BY clause determined by field/arrows in list header */
    protected $_order_by;
    /** @var string Default ORDER BY clause when $_orderBy is not defined */
    protected $_default_order_by = false;
    /** @var string Order way (ASC, DESC) determined by arrows in list header */
    protected $_order_way;
    /** @var int Max image size for upload
     * As of 1.5 it is recommended to not set a limit to max image size
     **/
    protected $max_image_size;
    /** @var array Confirmations displayed after post processing */
    protected array $_conf;
    /** @var object Object corresponding to the tab */
    protected $_object = false;
    /**
     * @var string[]
     */
    protected $identifiers_dnd = ['id_product' => 'id_product', 'id_category' => 'id_category_to_move', 'id_cms_category' => 'id_cms_category_to_move', 'id_cms' => 'id_cms', 'id_attribute' => 'id_attribute', 'id_attribute_group' => 'id_attribute_group', 'id_feature' => 'id_feature', 'id_carrier' => 'id_carrier'];
    /** @var bool Redirect or not ater a creation */
    protected $_redirect = true;
    /** @var bool If false, don't add form tags in options forms */
    protected $form_options = true;
    /**
     * @var array
     */
    protected $_languages;
    /**
     * @var int
     */
    protected $_default_form_language;
    /**
     * @var array
     */
    protected $_include_obj = [];
    /**
     * @var array
     */
    protected $_include_vars = [];
    /**
     * @var bool
     */
    protected $_include_container = true;
    /**
     * AdminTabCore constructor.
     *
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function __construct()
    {
        Tools::display_as_deprecated('AdminTab will be removed in thirty bees 1.6.0. Please update module');
        $this->context = Context::get_context();
        $this->id = Tab::get_id_from_class_name(static::class);
        $this->_conf = [1 => $this->l('Deletion successful'), 2 => $this->l('Selection successfully deleted'), 3 => $this->l('Creation successful'), 4 => $this->l('Update successful'), 5 => $this->l('Status update successful'), 6 => $this->l('Settings update successful'), 7 => $this->l('Image successfully deleted'), 8 => $this->l('Module downloaded successfully'), 9 => $this->l('Thumbnails successfully regenerated'), 10 => $this->l('Message sent to the customer'), 11 => $this->l('Comment added'), 12 => $this->l('Module installed successfully'), 13 => $this->l('Module uninstalled successfully'), 14 => $this->l('Language successfully copied'), 15 => $this->l('Translations successfully added'), 16 => $this->l('Module transplanted successfully to hook'), 17 => $this->l('Module removed successfully from hook'), 18 => $this->l('Upload successful'), 19 => $this->l('Duplication completed successfully'), 20 => $this->l('Translation added successfully but the language has not been created'), 21 => $this->l('Module reset successfully'), 22 => $this->l('Module deleted successfully'), 23 => $this->l('Localization pack imported successfully'), 24 => $this->l('Refund Successful'), 25 => $this->l('Images successfully moved')];
        if (!$this->identifier) {
            $this->identifier = 'id_' . $this->table;
        }
        if (!$this->_default_order_by) {
            $this->_default_order_by = $this->identifier;
        }
        $class_name = static::class;
        $this->token = Tools::get_admin_token($class_name . (int) $this->id . (int) $this->context->employee->id);
        if (!Shop::is_feature_active()) {
            $this->shop_link_type = '';
        }
        $this->image_type = Image_Manager::get_default_image_extension();
    }
    /**
     * Uses translations files to find a translation for a given string (string should be in english).
     *
     * @param string $string term or expression in english
     * @param string $class
     * @param bool $addslashes if set to true, the return value will pass through addslashes(). Otherwise, stripslashes().
     * @param bool $htmlentities if set to true(default), the return value will pass through htmlentities($string, ENT_QUOTES, 'utf-8')
     *
     * @return string The translation if available, or the english default text.
     *
     * @deprecated 1.0.0
     */
    protected function l($string, $class = 'AdminTab', $addslashes = false, $htmlentities = true)
    {
        // if the class is extended by a module, use modules/[module_name]/xx.php lang file
        $current_class = static::class;
        if (Module::get_module_name_from_class($current_class)) {
            $string = str_replace('\'', '\\\'', $string);
            return Translate::get_module_translation(Module::$class_in_module[$current_class], $string, $current_class);
        }
        global $_LANGADM;
        if ($class == self::class) {
            $class = 'AdminTab';
        }
        $md5Key = md5(str_replace('\'', '\\\'', $string));
        $this_key = static::class . $md5Key;
        $class_key = $class . $md5Key;
        $str = $string;
        if (array_key_exists($this_key, $_LANGADM) && $_LANGADM[$this_key] !== '') {
            $str = $_LANGADM[$this_key];
        } else if (array_key_exists($class_key, $_LANGADM) && $_LANGADM[$class_key] !== '') {
            $str = $_LANGADM[$class_key];
        }
        $str = $htmlentities ? htmlentities((string) $str, ENT_QUOTES, 'utf-8') : $str;
        return str_replace('"', '&quot;', $addslashes ? addslashes((string) $str) : stripslashes((string) $str));
    }
    /**
     * @param bool $idObject
     *
     * @return array|void
     * @deprecated 1.0.0
     */
    protected static function get_asso_shop(string $table, $id_object = false)
    {
        if (Shop::is_table_associated($table)) {
            $type = 'shop';
        } else {
            return;
        }
        $assos = [];
        foreach ($_POST as $k => $row) {
            if (!preg_match('/^checkBox' . Tools::to_camel_case($type, true) . 'Asso_' . $table . '_([0-9]+)?_([0-9]+)$/Ui', (string) $k, $res)) {
                continue;
            }
            $id_asso_object = !empty($res[1]) ? $res[1] : $id_object;
            $assos[] = ['id_object' => (int) $id_asso_object, 'id_' . $type => (int) $res[2]];
        }
        return [$assos, $type];
    }
    /**
     * ajaxDisplay is the default ajax return sytem
     *
     * @return void
     *
     * @deprecated 1.0.0
     */
    public function display_ajax()
    {
    }
    /**
     * Manage page display (form, list...)
     *
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function display(): void
    {
        // Include other tab in current tab
        if ($this->include_sub_tab('display', ['submitAdd2', 'add', 'update', 'view'])) {
        } elseif (Tools::get_value('submitAdd' . $this->table) && count($this->_errors) || isset($_GET['add' . $this->table])) {
            if ($this->tab_access[Profile::PERMISSION_ADD]) {
                $this->display_form();
                if ($this->tab_access[Profile::PERMISSION_VIEW]) {
                    echo '<br /><br /><a href="' . (Tools::get_value('back') ?: static::$current_index . '&token=' . $this->token) . '"><img src="../img/admin/arrow2.gif" /> ' . (Tools::get_value('back') ? $this->l('Back') : $this->l('Back to list')) . '</a><br />';
                }
            } else {
                echo $this->l('You do not have permission to add here');
            }
        } elseif (isset($_GET['update' . $this->table])) {
            if ($this->tab_access[Profile::PERMISSION_EDIT] || $this->table == 'employee' && $this->context->employee->id == Tools::get_int_value('id_employee')) {
                $this->display_form();
                if ($this->tab_access[Profile::PERMISSION_VIEW]) {
                    echo '<br /><br /><a href="' . (Tools::get_value('back') ?: static::$current_index . '&token=' . $this->token) . '"><img src="../img/admin/arrow2.gif" /> ' . (Tools::get_value('back') ? $this->l('Back') : $this->l('Back to list')) . '</a><br />';
                }
            } else {
                echo $this->l('You do not have permission to edit here');
            }
        } elseif (isset($_GET['view' . $this->table])) {
            $this->{'view' . $this->table}();
        } else {
            $this->get_list($this->context->language->id);
            $this->display_list();
            echo '<br />';
            $this->display_options_list();
            $this->display_required_fields();
            $this->include_sub_tab('display');
        }
    }
    /**
     * @param string $methodname
     * @param array $actions
     *
     * @deprecated 1.0.0
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function include_sub_tab($methodname, $actions = [])
    {
        if (!isset($this->_include_tab) || !is_array($this->_include_tab)) {
            return false;
        }
        $key = 0;
        $inc = false;
        foreach ($this->_include_tab as $subtab => $extra_vars) {
            /* New tab loading */
            $classname = 'Admin' . $subtab;
            if (($module = Db::read_only()->get_value('SELECT `module` FROM `' . _DB_PREFIX_ . 'tab` WHERE `class_name` = \'' . p_sql($classname) . '\'')) && file_exists(_PS_MODULE_DIR_ . '/' . $module . '/' . $classname . '.php')) {
                include_once _PS_MODULE_DIR_ . '/' . $module . '/' . $classname . '.php';
            } elseif (file_exists(_PS_ADMIN_DIR_ . '/tabs/' . $classname . '.php')) {
                include_once 'tabs/' . $classname . '.php';
            }
            if (!isset($this->_include_obj[$key])) {
                $this->_include_obj[$key] = new $classname();
            }
            /** @var static $adminTab */
            $admin_tab = $this->_include_obj[$key];
            $admin_tab->token = $this->token;
            /* Extra variables addition */
            if (!empty($extra_vars) && is_array($extra_vars)) {
                foreach ($extra_vars as $var_key => $var_value) {
                    $admin_tab->{$var_key} = $var_value;
                }
            }
            /* Actions management */
            foreach ($actions as $action) {
                switch ($action) {
                    case 'submitAdd1':
                        if (Tools::get_value('submitAdd' . $admin_tab->table)) {
                            $ok_inc = true;
                        }
                        break;
                    case 'submitAdd2':
                        if (Tools::get_value('submitAdd' . $admin_tab->table) && count($admin_tab->_errors)) {
                            $ok_inc = true;
                        }
                        break;
                    case 'submitDel':
                        if (Tools::get_value('submitDel' . $admin_tab->table)) {
                            $ok_inc = true;
                        }
                        break;
                    case 'submitFilter':
                        if (Tools::is_submit('submitFilter' . $admin_tab->table)) {
                            $ok_inc = true;
                        }
                    // no break
                    case 'submitReset':
                        if (Tools::is_submit('submitReset' . $admin_tab->table)) {
                            $ok_inc = true;
                        }
                    // no break
                    default:
                        if (isset($_GET[$action . $admin_tab->table])) {
                            $ok_inc = true;
                        }
                }
            }
            $inc = false;
            if (isset($ok_inc) && $ok_inc || !count($actions)) {
                if (!$admin_tab->view_access()) {
                    echo Tools::display_error('Access denied.');
                    return false;
                }
                if (!count($actions)) {
                    if ($methodname == 'displayErrors' && count($admin_tab->_errors) || $methodname != 'displayErrors') {
                        echo isset($this->_include_tab_title[$key]) ? '<h2>' . $this->_include_tab_title[$key] . '</h2>' : '';
                    }
                }
                if ($admin_tab->_include_vars) {
                    foreach ($admin_tab->_include_vars as $var => $value) {
                        $admin_tab->{$var} = $this->{$value};
                    }
                }
                $admin_tab->{$methodname}();
                $inc = true;
            }
            $key++;
        }
        return $inc;
    }
    /**
     * Display form
     *
     * @param bool $firstCall
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    public function display_form($first_call = true): void
    {
        $allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        if ($allow_employee_form_lang && !$this->context->cookie->employee_form_lang) {
            $this->context->cookie->employee_form_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        }
        $use_lang_from_cookie = false;
        $this->_languages = Language::get_languages(false);
        if ($allow_employee_form_lang) {
            foreach ($this->_languages as $lang) {
                if ($this->context->cookie->employee_form_lang == $lang['id_lang']) {
                    $use_lang_from_cookie = true;
                }
            }
        }
        if (!$use_lang_from_cookie) {
            $this->_default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        } else {
            $this->_default_form_language = (int) $this->context->cookie->employee_form_lang;
        }
        // Only if it is the first call to displayForm, otherwise it has already been defined
        if ($first_call) {
            echo '
			<script type="text/javascript">
				$(document).ready(function() {
					id_language = ' . $this->_default_form_language . ';
					languages = new Array();';
            foreach ($this->_languages as $k => $language) {
                echo '
					languages[' . $k . '] = {
						id_lang: ' . (int) $language['id_lang'] . ',
						iso_code: \'' . $language['iso_code'] . '\',
						name: \'' . htmlentities((string) $language['name'], ENT_COMPAT, 'UTF-8') . '\'
					};';
            }
            echo '
					displayFlags(languages, id_language, ' . $allow_employee_form_lang . ');
				});
			</script>';
        }
    }
    /**
     * Get the current objects' list form the database
     *
     * @param int $idLang Language used for display
     * @param string|null $orderBy ORDER BY clause
     * @param string|null $orderWay
     * @param int $start Offset in LIMIT clause
     * @param int|null $limit Row count in LIMIT clause
     * @param bool $idLangShop
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function get_list($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false): void
    {
        /* Manage default params values */
        if (empty($limit)) {
            $limit = !isset($this->context->cookie->{$this->table . '_pagination'}) ? $this->_pagination[1] : $this->context->cookie->{$this->table . '_pagination'};
        }
        if (!Validate::is_table_or_identifier($this->table)) {
            $this->_errors[] = Tools::display_error('Table name is invalid:') . ' "' . $this->table . '"';
        }
        if (empty($order_by)) {
            $order_by = $this->context->cookie->__get($this->table . 'Orderby') ?: $this->_default_order_by;
        }
        if (empty($order_way)) {
            $order_way = $this->context->cookie->__get($this->table . 'Orderway') ?: 'ASC';
        }
        $limit = Tools::get_int_value('pagination', $limit);
        $this->context->cookie->{$this->table . '_pagination'} = $limit;
        /* Check params validity */
        if (!Validate::is_order_by($order_by) || !Validate::is_order_way($order_way)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Invalid ordering parameters: orderBy=[%s] orderWay=[%s]'), $order_by, $order_way));
        }
        if (!is_numeric($start) || !is_numeric($limit) || !Validate::is_unsigned_id($id_lang)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('getList params is not valid: start=[%s] limit=[%s] idLang=[%s]'), $start, $limit, $id_lang));
        }
        /* Determine offset from current page */
        if ((isset($_POST['submitFilter' . $this->table]) || isset($_POST['submitFilter' . $this->table . '_x']) || isset($_POST['submitFilter' . $this->table . '_y'])) && !empty($_POST['submitFilter' . $this->table]) && is_numeric($_POST['submitFilter' . $this->table])) {
            $start = (int) ($_POST['submitFilter' . $this->table] - 1) * $limit;
        }
        /* Cache */
        $this->_order_by = $order_by;
        $this->_order_way = mb_strtoupper((string) $order_way);
        /* SQL table : orders, but class name is Order */
        $sql_table = $this->table == 'order' ? 'orders' : $this->table;
        // Add SQL shop restriction
        $select_shop = $join_shop = $where_shop = '';
        if ($this->shop_link_type) {
            $select_shop = ', shop.name as shop_name ';
            $join_shop = ' LEFT JOIN ' . _DB_PREFIX_ . $this->shop_link_type . ' shop
							ON a.id_' . $this->shop_link_type . ' = shop.id_' . $this->shop_link_type;
            $where_shop = Shop::add_sql_restriction($this->shop_share_datas, 'a');
        }
        $asso = Shop::get_asso_table($this->table);
        if ($asso !== false && $asso['type'] == 'shop') {
            $filter_key = $asso['type'];
            $idenfier_shop = Shop::get_context_list_shop_id();
        }
        $filter_shop = '';
        if (isset($filter_key)) {
            if (!$this->_group) {
                $this->_group = 'GROUP BY a.' . p_sql($this->identifier);
            } elseif (!preg_match('#(\s|,)\s*a\.`?' . p_sql($this->identifier) . '`?(\s|,|$)#', $this->_group)) {
                $this->_group .= ', a.' . p_sql($this->identifier);
            }
            if (Shop::is_feature_active() && Shop::get_context() != Shop::CONTEXT_ALL && !preg_match('#`?' . preg_quote(_DB_PREFIX_ . $this->table . '_' . $filter_key) . '`? *sa#', $this->_join)) {
                $filter_shop = 'JOIN `' . _DB_PREFIX_ . $this->table . '_' . $filter_key . '` sa ON (sa.' . $this->identifier . ' = a.' . $this->identifier . ' AND sa.id_' . $filter_key . ' IN (' . implode(', ', $idenfier_shop) . '))';
            }
        }
        ///////////////////////
        /* Query in order to get results with all fields */
        $sql = 'SELECT SQL_CALC_FOUND_ROWS
			' . ($this->_tmp_table_filter ? ' * FROM (SELECT ' : '') . '
			' . ($this->lang ? 'b.*, ' : '') . 'a.*' . (isset($this->_select) ? ', ' . $this->_select . ' ' : '') . $select_shop . '
			FROM `' . _DB_PREFIX_ . $sql_table . '` a
			' . $filter_shop . '
			' . ($this->lang ? 'LEFT JOIN `' . _DB_PREFIX_ . $this->table . '_lang` b ON (b.`' . $this->identifier . '` = a.`' . $this->identifier . '` AND b.`id_lang` = ' . (int) $id_lang . ($id_lang_shop ? ' AND b.`id_shop`=' . (int) $id_lang_shop : '') . ')' : '') . '
			' . (isset($this->_join) ? $this->_join . ' ' : '') . '
			' . $join_shop . '
			WHERE 1 ' . (isset($this->_where) ? $this->_where . ' ' : '') . ($this->deleted ? 'AND a.`deleted` = 0 ' : '') . ($this->_filter ?? '') . $where_shop . '
			' . (isset($this->_group) ? $this->_group . ' ' : '') . '
			' . (isset($this->_filter_having) || isset($this->_having) ? 'HAVING ' : '') . (isset($this->_filter_having) ? ltrim($this->_filter_having, ' AND ') : '') . (isset($this->_having) ? $this->_having . ' ' : '') . '
			ORDER BY ' . ($order_by == $this->identifier ? 'a.' : '') . '`' . p_sql($order_by) . '` ' . p_sql($order_way) . ($this->_tmp_table_filter ? ') tmpTable WHERE 1' . $this->_tmp_table_filter : '') . '
			LIMIT ' . (int) $start . ',' . (int) $limit;
        $connection = Db::read_only();
        $this->_list = $connection->get_array($sql);
        $this->_list_total = $connection->get_value('SELECT FOUND_ROWS() as `' . _DB_PREFIX_ . $this->table . '`');
    }
    /**
     * Display list
     *
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function display_list()
    {
        $this->display_top();
        if ($this->edit && (!isset($this->no_add) || !$this->no_add)) {
            $this->display_add_button();
        }
        /* Append when we get a syntax error in SQL query */
        if ($this->_list === false) {
            $this->display_warning($this->l('Bad SQL query'));
            return false;
        }
        /* Display list header (filtering, pagination and column names) */
        $this->display_list_header();
        if (!count($this->_list)) {
            echo '<tr><td class="center" colspan="' . (count($this->fields_display) + 2) . '">' . $this->l('No items found') . '</td></tr>';
        }
        /* Show the content of the table */
        $this->display_list_content();
        /* Close list table and submit button */
        $this->display_list_footer();
    }
    /**
     * @return void
     */
    public function display_top()
    {
    }
    /**
     * @return void
     */
    protected function display_add_button()
    {
        echo '<br /><a href="' . static::$current_index . '&add' . $this->table . '&token=' . $this->token . '"><img src="../img/admin/add.gif" border="0" /> ' . $this->l('Add new') . '</a><br /><br />';
    }
    /**
     * Display a warning message
     *
     * @param string $warn Warning message to display
     *
     * @deprecated 1.0.0
     */
    public function display_warning(?string $warn): void
    {
        $str_output = '';
        if (!empty($warn)) {
            $str_output .= '<script type="text/javascript">
					$(document).ready(function() {
						$(\'#linkSeeMore\').unbind(\'click\').click(function(){
							$(\'#seeMore\').show(\'slow\');
							$(this).hide();
							$(\'#linkHide\').show();
							return false;
						});
						$(\'#linkHide\').unbind(\'click\').click(function(){
							$(\'#seeMore\').hide(\'slow\');
							$(this).hide();
							$(\'#linkSeeMore\').show();
							return false;
						});
						$(\'#hideWarn\').unbind(\'click\').click(function(){
							$(\'.warn\').hide(\'slow\', function (){
								$(\'.warn\').remove();
							});
							return false;
						});
					});
				  </script>
			<div class="warn">';
            if (!is_array($warn)) {
                $str_output .= '<img src="../img/admin/warn2.png" />' . $warn;
            } else {
                $str_output .= '<span style="float:right"><a id="hideWarn" href=""><img alt="X" src="../img/admin/close.png" /></a></span><img src="../img/admin/warn2.png" />' . (count($warn) > 1 ? sprintf($this->l('There are %s warnings'), count($warn)) : $this->l('There is 1 warning')) . '<span style="margin-left:20px;" id="labelSeeMore">
				<a id="linkSeeMore" href="#" style="text-decoration:underline">' . $this->l('Click here to see more') . '</a>
				<a id="linkHide" href="#" style="text-decoration:underline;display:none">' . $this->l('Hide warning') . '</a></span><ul style="display:none;" id="seeMore">';
                foreach ($warn as $val) {
                    $str_output .= '<li>' . $val . '</li>';
                }
                $str_output .= '</ul>';
            }
            $str_output .= '</div>';
        }
        echo $str_output;
    }
    /**
     * Display list header (filtering, pagination and column names)
     *
     * @param string|null $token
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function display_list_header($token = null): void
    {
        $is_cms = false;
        if (preg_match('/cms/Ui', $this->identifier)) {
            $is_cms = true;
        }
        $id_cat = Tools::get_value('id_' . ($is_cms ? 'cms_' : '') . 'category');
        if (empty($token)) {
            $token = $this->token;
        }
        /* Determine total page number */
        $total_pages = ceil($this->_list_total / Tools::get_value('pagination', $this->context->cookie->{$this->table . '_pagination'} ?? $this->_pagination[0]));
        if (!$total_pages) {
            $total_pages = 1;
        }
        echo '<a name="' . $this->table . '">&nbsp;</a>';
        echo '<form method="post" action="' . static::$current_index;
        if (Tools::get_isset($this->identifier)) {
            echo '&' . $this->identifier . '=' . Tools::get_int_value($this->identifier);
        }
        echo '&token=' . $token;
        if (Tools::get_isset($this->table . 'Orderby')) {
            echo '&' . $this->table . 'Orderby=' . urlencode($this->_order_by) . '&' . $this->table . 'Orderway=' . urlencode(strtolower($this->_order_way));
        }
        echo '#' . $this->table . '" class="form">
		<input type="hidden" id="submitFilter' . $this->table . '" name="submitFilter' . $this->table . '" value="0">
		<table>
			<tr>
				<td style="vertical-align: bottom;">
					<span style="float: left;">';
        /* Determine current page number */
        $page = Tools::get_int_value('submitFilter' . $this->table);
        if (!$page) {
            $page = 1;
        }
        if ($page > 1) {
            echo '
						<input type="image" src="../img/admin/list-prev2.gif" onclick="getE(\'submitFilter' . $this->table . '\').value=1"/>
						&nbsp; <input type="image" src="../img/admin/list-prev.gif" onclick="getE(\'submitFilter' . $this->table . '\').value=' . ($page - 1) . '"/> ';
        }
        echo $this->l('Page') . ' <b>' . $page . '</b> / ' . $total_pages;
        if ($page < $total_pages) {
            echo '
						<input type="image" src="../img/admin/list-next.gif" onclick="getE(\'submitFilter' . $this->table . '\').value=' . ($page + 1) . '"/>
						 &nbsp;<input type="image" src="../img/admin/list-next2.gif" onclick="getE(\'submitFilter' . $this->table . '\').value=' . $total_pages . '"/>';
        }
        echo '			| ' . $this->l('Display') . '
						<select name="pagination">';
        /* Choose number of results per page */
        $selected_pagination = Tools::get_value('pagination', $this->context->cookie->{$this->table . '_pagination'} ?? null);
        foreach ($this->_pagination as $value) {
            echo '<option value="' . (int) $value . '"' . ($selected_pagination == $value ? ' selected="selected"' : ($selected_pagination == null && $value == $this->_pagination[1] ? ' selected="selected2"' : '')) . '>' . (int) $value . '</option>';
        }
        echo '
						</select>
						/ ' . (int) $this->_list_total . ' ' . $this->l('result(s)') . '
					</span>
					<span style="float: right;">
						<input type="submit" name="submitReset' . $this->table . '" value="' . $this->l('Reset') . '" class="button" />
						<input type="submit" id="submitFilterButton_' . $this->table . '" name="submitFilter" value="' . $this->l('Filter') . '" class="button" />
					</span>
					<span class="clear"></span>
				</td>
			</tr>
			<tr>
				<td>';
        /* Display column names and arrows for ordering (ASC, DESC) */
        if (array_key_exists($this->identifier, $this->identifiers_dnd) && $this->_order_by == 'position') {
            echo '
			<script type="text/javascript" src="../js/jquery/jquery.tablednd_0_5.js"></script>
			<script type="text/javascript">
				var token = \'' . ($token != null ? $token : $this->token) . '\';
				var come_from = \'' . $this->table . '\';
				var alternate = \'' . ($this->_order_way == 'DESC' ? '1' : '0') . '\';
			</script>
			<script type="text/javascript" src="../js/admin/dnd.js"></script>
			';
        }
        echo '<table' . (array_key_exists($this->identifier, $this->identifiers_dnd) ? ' id="' . (Tools::get_int_value($this->identifiers_dnd[$this->identifier], 1) ? mb_substr($this->identifier, 3, mb_strlen($this->identifier)) : '') . '"' : '') . ' class="table' . (array_key_exists($this->identifier, $this->identifiers_dnd) && ($this->_order_by != 'position' && $this->_order_way != 'DESC') ? ' tableDnD' : '') . '" cellpadding="0" cellspacing="0">
			<thead>
				<tr class="nodrag nodrop">
					<th>';
        if ($this->delete) {
            echo '		<input type="checkbox" name="checkme" class="noborder" onclick="checkDelBoxes(this.form, \'' . $this->table . 'Box[]\', this.checked)" />';
        }
        echo '		</th>';
        foreach ($this->fields_display as $key => $params) {
            echo '	<th ' . (isset($params['widthColumn']) ? 'style="width: ' . $params['widthColumn'] . 'px"' : '') . '>' . $params['title'];
            if (!isset($params['orderby']) || $params['orderby']) {
                // Cleaning links
                if (Tools::get_value($this->table . 'Orderby') && Tools::get_value($this->table . 'Orderway')) {
                    static::$current_index = preg_replace('/&' . $this->table . 'Orderby=([a-z _]*)&' . $this->table . 'Orderway=([a-z]*)/i', '', static::$current_index);
                }
                echo '	<br />
						<a href="' . static::$current_index . '&' . $this->identifier . '=' . $id_cat . '&' . $this->table . 'Orderby=' . urlencode((string) $key) . '&' . $this->table . 'Orderway=desc&token=' . $token . '"><img border="0" src="../img/admin/down' . (isset($this->_order_by) && $key == $this->_order_by && $this->_order_way == 'DESC' ? '_d' : '') . '.gif" /></a>
						<a href="' . static::$current_index . '&' . $this->identifier . '=' . $id_cat . '&' . $this->table . 'Orderby=' . urlencode((string) $key) . '&' . $this->table . 'Orderway=asc&token=' . $token . '"><img border="0" src="../img/admin/up' . (isset($this->_order_by) && $key == $this->_order_by && $this->_order_way == 'ASC' ? '_d' : '') . '.gif" /></a>';
            }
            echo '	</th>';
        }
        if ($this->shop_link_type) {
            echo '<th style="width: 80px">' . $this->l($this->shop_link_type == 'shop' ? 'Shop' : 'Shop group') . '</th>';
        }
        /* Check if object can be modified, deleted or detailed */
        if ($this->edit || $this->delete || $this->view && $this->view !== 'noActionColumn') {
            echo '	<th style="width: 52px">' . $this->l('Actions') . '</th>';
        }
        echo '	</tr>
				<tr class="nodrag nodrop" style="height: 35px;">
					<td class="center">';
        if ($this->delete) {
            echo '		--';
        }
        echo '		</td>';
        /* Javascript hack in order to catch ENTER keypress event */
        $key_press = 'onkeypress="formSubmit(event, \'submitFilterButton_' . $this->table . '\');"';
        /* Filters (input, select, date or bool) */
        foreach ($this->fields_display as $key => $params) {
            $width = isset($params['width']) ? ' style="width: ' . (int) $params['width'] . 'px;"' : '';
            echo '<td' . (isset($params['align']) ? ' class="' . $params['align'] . '"' : '') . '>';
            if (!isset($params['type'])) {
                $params['type'] = 'text';
            }
            $value = Tools::get_value($this->table . 'Filter_' . (array_key_exists('filter_key', $params) ? $params['filter_key'] : $key));
            if (isset($params['search']) && !$params['search']) {
                echo '--</td>';
                continue;
            }
            switch ($params['type']) {
                case 'bool':
                    echo '
					<select name="' . $this->table . 'Filter_' . $key . '">
						<option value="">-</option>
						<option value="1"' . ($value == 1 ? ' selected="selected"' : '') . '>' . $this->l('Yes') . '</option>
						<option value="0"' . ($value == 0 && $value != '' ? ' selected="selected"' : '') . '>' . $this->l('No') . '</option>
					</select>';
                    break;
                case 'date':
                case 'datetime':
                    if (!Validate::is_clean_html($value[0]) || !Validate::is_clean_html($value[1])) {
                        $value = '';
                    }
                    $name = $this->table . 'Filter_' . ($params['filter_key'] ?? $key);
                    $name_id = str_replace('!', '__', $name);
                    include_datepicker([$name_id . '_0', $name_id . '_1']);
                    echo $this->l('From') . ' <input type="text" id="' . $name_id . '_0" name="' . $name . '[0]" value="' . ($value[0] ?? '') . '"' . $width . ' ' . $key_press . ' /><br />
					' . $this->l('To') . ' <input type="text" id="' . $name_id . '_1" name="' . $name . '[1]" value="' . ($value[1] ?? '') . '"' . $width . ' ' . $key_press . ' />';
                    break;
                case 'select':
                    if (isset($params['filter_key'])) {
                        echo '<select onchange="$(\'#submitFilter' . $this->table . '\').focus();$(\'#submitFilter' . $this->table . '\').click();" name="' . $this->table . 'Filter_' . $params['filter_key'] . '" ' . (isset($params['width']) ? 'style="width: ' . $params['width'] . 'px"' : '') . '>
								<option value=""' . ($value == 0 && $value != '' ? ' selected="selected"' : '') . '>-</option>';
                        if (isset($params['select']) && is_array($params['select'])) {
                            foreach ($params['select'] as $option_value => $option_display) {
                                echo '<option value="' . $option_value . '"' . (isset($_POST[$this->table . 'Filter_' . $params['filter_key']]) && Tools::get_value($this->table . 'Filter_' . $params['filter_key']) == $option_value && Tools::get_value($this->table . 'Filter_' . $params['filter_key']) != '' ? ' selected="selected"' : '') . '>' . $option_display . '</option>';
                            }
                        }
                        echo '</select>';
                        break;
                    }
                // no break
                case 'text':
                default:
                    if (!Validate::is_clean_html($value)) {
                        $value = '';
                    }
                    echo '<input type="text" name="' . $this->table . 'Filter_' . ($params['filter_key'] ?? $key) . '" value="' . htmlentities($value, ENT_COMPAT, 'UTF-8') . '"' . $width . ' ' . $key_press . ' />';
            }
            echo '</td>';
        }
        if ($this->shop_link_type) {
            echo '<td>--</td>';
        }
        if ($this->edit || $this->delete || $this->view && $this->view !== 'noActionColumn') {
            echo '<td class="center">--</td>';
        }
        echo '</tr>
			</thead>';
    }
    /**
     * @param string|null $token
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    public function display_list_content($token = null): void
    {
        /* Display results in a table
         *
         * align  : determine value alignment
         * prefix : displayed before value
         * suffix : displayed after value
         * image  : object image
         * icon   : icon determined by values
         * active : allow to toggle status
         */
        $id_category = 1;
        // default categ
        $irow = 0;
        if ($this->_list && isset($this->fields_display['position'])) {
            $positions = array_map(fn(array $elem) => (int) $elem['position'], $this->_list);
            sort($positions);
        }
        if ($this->_list) {
            $is_cms = false;
            if (preg_match('/cms/Ui', $this->identifier)) {
                $is_cms = true;
            }
            $key_to_get = 'id_' . ($is_cms ? 'cms_' : '') . 'category' . (in_array($this->identifier, ['id_category', 'id_cms_category']) ? '_parent' : '');
            foreach ($this->_list as $tr) {
                $id = $tr[$this->identifier];
                echo '<tr' . (array_key_exists($this->identifier, $this->identifiers_dnd) ? ' id="tr_' . (($id_category = Tools::get_int_value('id_' . ($is_cms ? 'cms_' : '') . 'category', 1)) ? $id_category : '') . '_' . $id . '_' . $tr['position'] . '"' : '') . ($irow++ % 2 ? ' class="alt_row"' : '') . ' ' . (isset($tr['color']) && $this->color_on_background ? 'style="background-color: ' . $tr['color'] . '"' : '') . '>
							<td class="center">';
                if ($this->delete && (!isset($this->_list_skip_delete) || !in_array($id, $this->_list_skip_delete))) {
                    echo '<input type="checkbox" name="' . $this->table . 'Box[]" value="' . $id . '" class="noborder" />';
                }
                echo '</td>';
                foreach ($this->fields_display as $key => $params) {
                    $tmp = explode('!', (string) $key);
                    $key = $tmp[1] ?? $tmp[0];
                    echo '
					<td ' . (isset($params['position']) ? ' id="td_' . $id_category . '_' . $id . '"' : '') . ' class="' . (!isset($this->no_link) || !$this->no_link ? 'pointer' : '') . (isset($params['position']) && $this->_order_by == 'position' ? ' dragHandle' : '') . (isset($params['align']) ? ' ' . $params['align'] : '') . '" ';
                    if (!isset($params['position']) && (!isset($this->no_link) || !$this->no_link)) {
                        echo ' onclick="document.location = \'' . static::$current_index . '&' . $this->identifier . '=' . $id . ($this->view ? '&view' : '&update') . $this->table . '&token=' . ($token != null ? $token : $this->token) . '\'">' . ($params['prefix'] ?? '');
                    } else {
                        echo '>';
                    }
                    if (isset($params['active']) && isset($tr[$key])) {
                        $this->_display_enable_link($token, $id, $tr[$key], $params['active'], Tools::get_int_value('id_category'), Tools::get_int_value('id_product'));
                    } elseif (isset($params['activeVisu']) && isset($tr[$key])) {
                        echo '<img src="../img/admin/' . ($tr[$key] ? 'enabled.gif' : 'disabled.gif') . '"
						alt="' . ($tr[$key] ? $this->l('Enabled') : $this->l('Disabled')) . '" title="' . ($tr[$key] ? $this->l('Enabled') : $this->l('Disabled')) . '" />';
                    } elseif (isset($params['position'])) {
                        if ($this->_order_by == 'position' && $this->_order_way != 'DESC') {
                            echo '<a' . (!($tr[$key] != $positions[count($positions) - 1]) ? ' style="display: none;"' : '') . ' href="' . static::$current_index . '&' . $key_to_get . '=' . (int) $id_category . '&' . $this->identifiers_dnd[$this->identifier] . '=' . $id . '
									&way=1&position=' . (int) ($tr['position'] + 1) . '&token=' . ($token != null ? $token : $this->token) . '">
									<img src="../img/admin/' . ($this->_order_way == 'ASC' ? 'down' : 'up') . '.gif"
									alt="' . $this->l('Down') . '" title="' . $this->l('Down') . '" /></a>';
                            echo '<a' . (!($tr[$key] != $positions[0]) ? ' style="display: none;"' : '') . ' href="' . static::$current_index . '&' . $key_to_get . '=' . (int) $id_category . '&' . $this->identifiers_dnd[$this->identifier] . '=' . $id . '
									&way=0&position=' . (int) ($tr['position'] - 1) . '&token=' . ($token != null ? $token : $this->token) . '">
									<img src="../img/admin/' . ($this->_order_way == 'ASC' ? 'up' : 'down') . '.gif"
									alt="' . $this->l('Up') . '" title="' . $this->l('Up') . '" /></a>';
                        } else {
                            echo (int) ($tr[$key] + 1);
                        }
                    } elseif (isset($params['image'])) {
                        // item_id is the product id in a product image context, else it is the image id.
                        $item_id = isset($params['image_id']) ? $tr[$params['image_id']] : $id;
                        // If it's a product image
                        if (isset($tr['id_image'])) {
                            $image = new Image((int) $tr['id_image']);
                            $path_to_image = _PS_IMG_DIR_ . $params['image'] . '/' . $image->get_existing_img_path() . '.' . $this->image_type;
                        } else {
                            $path_to_image = _PS_IMG_DIR_ . $params['image'] . '/' . $item_id . '.' . $this->image_type;
                        }
                        echo Image_Manager::thumbnail($path_to_image, $this->table . '_mini_' . $item_id . '.' . $this->image_type, 45, $this->image_type);
                    } elseif (isset($params['icon']) && (isset($params['icon'][$tr[$key]]) || isset($params['icon']['default']))) {
                        echo '<img src="../img/admin/' . ($params['icon'][$tr[$key]] ?? $params['icon']['default'] . '" alt="' . $tr[$key]) . '" title="' . $tr[$key] . '" />';
                    } elseif (isset($params['price'])) {
                        echo Tools::display_price($tr[$key], isset($params['currency']) ? Currency::get_currency_instance($tr['id_currency']) : $this->context->currency, false);
                    } elseif (isset($params['float'])) {
                        echo rtrim(rtrim((string) $tr[$key], '0'), '.');
                    } elseif (isset($params['type']) && $params['type'] == 'date') {
                        echo Tools::display_date($tr[$key]);
                    } elseif (isset($params['type']) && $params['type'] == 'datetime') {
                        echo Tools::display_date($tr[$key], null, true);
                    } elseif (isset($tr[$key])) {
                        if ($key == 'price') {
                            $currency = $this->context->currency;
                            if (isset($params['currency'])) {
                                $currency = Currency::get_currency_instance($tr['id_currency']);
                            }
                            $echo = Tools::ps_round($tr[$key], $currency->get_display_precision());
                        } elseif (isset($params['maxlength']) && mb_strlen($tr[$key]) > $params['maxlength']) {
                            $echo = '<span title="' . $tr[$key] . '">' . mb_substr($tr[$key], 0, $params['maxlength']) . '...</span>';
                        } else {
                            $echo = $tr[$key];
                        }
                        echo isset($params['callback']) ? call_user_func_array([$params['callback_object'] ?? $this->class_name, $params['callback']], [$echo, $tr]) : $echo;
                    } else {
                        echo '--';
                    }
                    echo ($params['suffix'] ?? '') . '</td>';
                }
                if ($this->shop_link_type) {
                    $name = mb_strlen((string) $tr['shop_name']) > 15 ? mb_substr((string) $tr['shop_name'], 0, 15) . '...' : $tr['shop_name'];
                    echo '<td class="center" ' . ($name != $tr['shop_name'] ? 'title="' . $tr['shop_name'] . '"' : '') . '>' . $name . '</td>';
                }
                if ($this->edit || $this->delete || $this->view && $this->view !== 'noActionColumn') {
                    echo '<td class="center" style="white-space: nowrap;">';
                    if ($this->view) {
                        $this->_display_view_link($token, $id);
                    }
                    if ($this->edit) {
                        $this->_display_edit_link($token, $id);
                    }
                    if ($this->delete && (!isset($this->_list_skip_delete) || !in_array($id, $this->_list_skip_delete))) {
                        $this->_display_delete_link($token, $id);
                    }
                    if ($this->duplicate) {
                        $this->_display_duplicate($token, $id);
                    }
                    echo '</td>';
                }
                echo '</tr>';
            }
        }
    }
    /**
     * @param string $token
     * @param int $id
     * @param bool $value
     * @param int|null $idCategory
     * @param int|null $idProduct
     * @deprecated 1.0.0
     */
    protected function _display_enable_link($token, $id, $value, string $active, $id_category = null, $id_product = null)
    {
        $href = Tools::safe_output(static::$current_index . '&' . $this->identifier . '=' . (int) $id . '&' . $active . $this->table . ((int) $id_category && (int) $id_product ? '&id_category=' . (int) $id_category : '') . '&token=' . ($token != null ? $token : $this->token));
        echo '<a href="' . $href . '">
	        <img src="../img/admin/' . ($value ? 'enabled.gif' : 'disabled.gif') . '"
	        alt="' . ($value ? $this->l('Enabled') : $this->l('Disabled')) . '" title="' . ($value ? $this->l('Enabled') : $this->l('Disabled')) . '" /></a>';
    }
    /**
     * @param string|null $token
     * @param int $id
     *
     * @deprecated 1.0.0
     */
    protected function _display_view_link($token, $id)
    {
        $_cache_lang['View'] = $this->l('View');
        $href = Tools::safe_output(static::$current_index . '&' . $this->identifier . '=' . (int) $id . '&view' . $this->table . '&token=' . ($token != null ? $token : $this->token));
        echo '<a href="' . $href . '">
			<img src="../img/admin/details.gif" alt="' . $_cache_lang['View'] . '" title="' . $_cache_lang['View'] . '" /></a>';
    }
    /**
     * @param string|null $token
     * @param int $id
     *
     * @deprecated 1.0.0
     */
    protected function _display_edit_link($token, $id)
    {
        $_cache_lang['Edit'] = $this->l('Edit');
        $href = Tools::safe_output(static::$current_index . '&' . $this->identifier . '=' . (int) $id . '&update' . $this->table . '&token=' . ($token != null ? $token : $this->token));
        echo '<a href="' . $href . '">
    		<img src="../img/admin/edit.gif" alt="" title="' . $_cache_lang['Edit'] . '" /></a>';
    }
    /**
     * @param string|null $token
     * @param int $id
     *
     * @deprecated 1.0.0
     */
    protected function _display_delete_link($token, $id)
    {
        $_cache_lang['Delete'] = $this->l('Delete');
        $_cache_lang['DeleteItem'] = $this->l('Delete item #', self::class, true, false);
        $href = Tools::safe_output(static::$current_index . '&' . $this->identifier . '=' . (int) $id . '&delete' . $this->table . '&token=' . ($token != null ? $token : $this->token));
        echo '<a href="' . $href . '" onclick="return confirm(\'' . $_cache_lang['DeleteItem'] . (int) $id . ' ?' . (!is_null($this->specific_confirm_delete) ? '\r' . $this->specific_confirm_delete : '') . '\');">
			<img src="../img/admin/delete.gif" alt="' . $_cache_lang['Delete'] . '" title="' . $_cache_lang['Delete'] . '" /></a>';
    }
    /**
     * @param string|null $token
     * @param int $id
     *
     * @deprecated 1.0.0
     */
    protected function _display_duplicate($token, $id)
    {
        $_cache_lang['Duplicate'] = $this->l('Duplicate');
        $_cache_lang['Copy images too?'] = $this->l('This will copy the images too. If you wish to proceed, click "OK". If not, click "Cancel".', self::class, true, false);
        $duplicate = Tools::safe_output(static::$current_index . '&' . $this->identifier . '=' . $id . '&duplicate' . $this->table . '&token=' . ($token != null ? $token : $this->token));
        echo '<a class="pointer" onclick="if (confirm(\'' . $_cache_lang['Copy images too?'] . '\')) document.location = \'' . $duplicate . '\'; else document.location = \'' . $duplicate . '&noimage=1\';">
    		<img src="../img/admin/duplicate.png" alt="' . $_cache_lang['Duplicate'] . '" title="' . $_cache_lang['Duplicate'] . '" /></a>';
    }
    /**
     * Close list table and submit button
     *
     * @param string|null $token
     * @deprecated 1.0.0
     */
    public function display_list_footer($token = null): void
    {
        echo '</table>';
        if ($this->delete) {
            echo '<p><input type="submit" class="button" name="submitDel' . $this->table . '" value="' . $this->l('Delete selection') . '" onclick="return confirm(\'' . $this->l('Delete selected items?', self::class, true, false) . '\');" /></p>';
        }
        echo '
				</td>
			</tr>
		</table>
		<input type="hidden" name="token" value="' . ($token ?: $this->token) . '" />
		</form>';
        if (isset($this->_include_tab) && count($this->_include_tab)) {
            echo '<br /><br />';
        }
    }
    /**
     * Options lists
     *
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function display_options_list(): void
    {
        $tab = Tab::get_tab($this->context->language->id, $this->id);
        // Retrocompatibility < 1.5.0
        if (!$this->options_list && $this->_fields_options) {
            $this->options_list = ['options' => ['title' => $this->option_title ?: $this->l('Options'), 'fields' => $this->_fields_options]];
        }
        if (!$this->options_list) {
            return;
        }
        echo '<br />';
        echo '<script type="text/javascript">
			id_language = Number(' . $this->context->language->id . ');
		</script>';
        $action = Tools::safe_output(static::$current_index . '&submitOptions' . $this->table . '=1&token=' . $this->token);
        echo '<form action="' . $action . '" method="post" enctype="multipart/form-data">';
        foreach ($this->options_list as $category => $category_data) {
            $required = false;
            $this->display_top_option_category($category, $category_data);
            echo '<fieldset>';
            // Options category title
            $legend = '<img src="' . (!empty($tab['module']) && file_exists($_SERVER['DOCUMENT_ROOT'] . _MODULE_DIR_ . $tab['module'] . '/' . $tab['class_name'] . '.gif') ? _MODULE_DIR_ . $tab['module'] . '/' : '../img/t/') . $tab['class_name'] . '.gif" /> ';
            $legend .= $category_data['title'] ?? $this->l('Options');
            echo '<legend>' . $legend . '</legend>';
            // Category fields
            if (!isset($category_data['fields'])) {
                continue;
            }
            // Category description
            if (isset($category_data['description']) && $category_data['description']) {
                echo '<p class="optionsDescription">' . $category_data['description'] . '</p>';
            }
            foreach ($category_data['fields'] as $key => $field) {
                // Field value
                $value = Tools::get_value($key, Configuration::get($key));
                if (!Validate::is_clean_html($value)) {
                    $value = Configuration::get($key);
                }
                if (isset($field['defaultValue']) && !$value) {
                    $value = $field['defaultValue'];
                }
                // Check if var is invisible (can't edit it in current shop context), or disable (use default value for multishop)
                $is_disabled = $is_invisible = false;
                if (Shop::is_feature_active()) {
                    if (isset($field['visibility']) && $field['visibility'] > Shop::get_context()) {
                        $is_disabled = true;
                        $is_invisible = true;
                    } elseif (Shop::get_context() != Shop::CONTEXT_ALL && !Configuration::is_overriden_by_current_context($key)) {
                        $is_disabled = true;
                    }
                }
                // Display title
                echo '<div style="clear: both; padding-top:15px;" id="conf_id_' . $key . '" ' . ($is_invisible ? 'class="isInvisible"' : '') . '>';
                if ($field['title']) {
                    echo '<label class="conf_title">';
                    // Is this field required ?
                    if (isset($field['required']) && $field['required']) {
                        $required = true;
                        echo '<sup>*</sup> ';
                    }
                    echo $field['title'] . '</label>';
                }
                echo '<div class="margin-form" style="padding-top:5px;">';
                // Display option inputs
                $method = 'displayOptionType' . Tools::to_camel_case($field['type'], true);
                if (!method_exists($this, $method)) {
                    $this->display_option_type_text($key, $field, $value);
                } else {
                    $this->{$method}($key, $field, $value);
                }
                // Multishop default value
                if (Shop::is_feature_active() && Shop::get_context() != Shop::CONTEXT_ALL && !$is_invisible) {
                    echo '<div class="preference_default_multishop">
							<label>
								<input type="checkbox" name="multishopOverrideOption[' . $key . ']" value="1" ' . ($is_disabled ? 'checked="checked"' : '') . ' onclick="checkMultishopDefaultValue(this, \'' . $key . '\')" /> ' . $this->l('Use default value') . '
							</label>
						</div>';
                }
                // Field description
                //echo (isset($field['desc']) ? '<p class="preference_description">'.((isset($field['thumb']) AND $field['thumb'] AND $field['thumb']['pos'] == 'after') ? '<img src="'.$field['thumb']['file'].'" alt="'.$field['title'].'" title="'.$field['title'].'" style="float:left;" />' : '' ).$field['desc'].'</p>' : '');
                echo isset($field['desc']) ? '<p class="preference_description">' . $field['desc'] . '</p>' : '';
                // Is this field invisible in current shop context ?
                echo $is_invisible ? '<p class="multishop_warning">' . $this->l('You cannot change the value of this configuration field in this shop context') . '</p>' : '';
                echo '</div></div>';
            }
            echo '<div align="center" style="margin-top: 20px;">';
            echo '<input type="submit" value="' . $this->l('   Save   ') . '" name="submit' . ucfirst((string) $category) . $this->table . '" class="button" />';
            echo '</div>';
            if ($required) {
                echo '<div class="small"><sup>*</sup> ' . $this->l('Required field') . '</div>';
            }
            echo '</fieldset><br />';
            $this->display_bottom_option_category($category, $category_data);
        }
        echo '</form>';
    }
    /**
     * Can be overriden
     *
     * @deprecated 1.0.0
     */
    public function display_top_option_category($category, $data)
    {
    }
    /**
     * Type = text
     *
     * @deprecated 1.0.0
     */
    public function display_option_type_text(string $key, array $field, $value): void
    {
        echo '<input type="' . $field['type'] . '"' . (isset($field['id']) ? ' id="' . $field['id'] . '"' : '') . ' size="' . (isset($field['size']) ? (int) $field['size'] : 5) . '" name="' . $key . '" value="' . htmlentities((string) $value, ENT_COMPAT, 'UTF-8') . '" />' . (isset($field['next']) ? '&nbsp;' . $field['next'] : '');
    }
    /**
     * Can be overriden
     *
     * @deprecated 1.0.0
     */
    public function display_bottom_option_category($category, $data)
    {
    }
    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function display_required_fields(): void
    {
        if (!$this->tab_access[Profile::PERMISSION_ADD] || !$this->tab_access[Profile::PERMISSION_DELETE] || !$this->required_database) {
            return;
        }
        $rules = call_user_func_array([$this->class_name, 'getValidationRules'], [$this->class_name]);
        $required_class_fields = [$this->identifier];
        foreach ($rules['required'] as $required) {
            $required_class_fields[] = $required;
        }
        echo '<br />
		<p><a href="#" onclick="if ($(\'.requiredFieldsParameters:visible\').length == 0) $(\'.requiredFieldsParameters\').slideDown(\'slow\'); else $(\'.requiredFieldsParameters\').slideUp(\'slow\'); return false;"><img src="../img/admin/duplicate.gif" alt="" /> ' . $this->l('Set required fields for this section') . '</a></p>
		<fieldset style="display:none" class="width1 requiredFieldsParameters">
		<legend>' . $this->l('Required Fields') . '</legend>
		<form name="updateFields" action="' . static::$current_index . '&submitFields' . $this->table . '=1&token=' . $this->token . '" method="post">
		<p><b>' . $this->l('Select the fields you would like to be required for this section.') . '<br />
		<table cellspacing="0" cellpadding="0" class="table width1 clear">
		<tr>
			<th><input type="checkbox" onclick="checkDelBoxes(this.form, \'fieldsBox[]\', this.checked)" class="noborder" name="checkme"></th>
			<th>' . $this->l('Field Name') . '</th>
		</tr>';
        /** @var ObjectModel $object */
        $object = new $this->class_name();
        $res = $object->get_fields_required_database();
        $required_fields = [];
        foreach ($res as $row) {
            $required_fields[(int) $row['id_required_field']] = $row['field_name'];
        }
        $table_fields = Db::read_only()->get_array('SHOW COLUMNS FROM ' . p_sql(_DB_PREFIX_ . $this->table));
        $irow = 0;
        foreach ($table_fields as $field) {
            if (in_array($field['Field'], $required_class_fields)) {
                continue;
            }
            echo '<tr class="' . ($irow++ % 2 ? 'alt_row' : '') . '">
						<td class="noborder"><input type="checkbox" name="fieldsBox[]" value="' . $field['Field'] . '" ' . (in_array($field['Field'], $required_fields) ? 'checked="checked"' : '') . ' /></td>
						<td>' . $field['Field'] . '</td>
					</tr>';
        }
        echo '</table><br />
				<center><input style="margin-left:15px;" class="button" type="submit" value="' . $this->l('   Save   ') . '" name="submitFields" /></center>
		</fieldset>';
    }
    /**
     * Overload this method for custom checking
     *
     * @param int $id Object id used for deleting images
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function delete_image($id)
    {
        Tools::display_as_deprecated();
        $dir = null;
        /* Deleting object images and thumbnails (cache) */
        if (array_key_exists('dir', $this->field_image_settings)) {
            $dir = $this->field_image_settings['dir'] . '/';
            if (file_exists(_PS_IMG_DIR_ . $dir . $id . '.' . $this->image_type) && !unlink(_PS_IMG_DIR_ . $dir . $id . '.' . $this->image_type)) {
                return false;
            }
        }
        if (file_exists(_PS_TMP_IMG_DIR_ . $this->table . '_' . $id . '.' . $this->image_type) && !unlink(_PS_TMP_IMG_DIR_ . $this->table . '_' . $id . '.' . $this->image_type)) {
            return false;
        }
        if (file_exists(_PS_TMP_IMG_DIR_ . $this->table . '_mini_' . $id . '.' . $this->image_type) && !unlink(_PS_TMP_IMG_DIR_ . $this->table . '_mini_' . $id . '.' . $this->image_type)) {
            return false;
        }
        $types = Image_Type::get_images_types();
        foreach ($types as $image_type) {
            if (file_exists(_PS_IMG_DIR_ . $dir . $id . '-' . stripslashes((string) $image_type['name']) . '.' . $this->image_type) && !unlink(_PS_IMG_DIR_ . $dir . $id . '-' . stripslashes((string) $image_type['name']) . '.' . $this->image_type)) {
                return false;
            }
        }
        return true;
    }
    /**
     * ajaxPreProcess is a method called in ajax-tab.php before displayConf().
     *
     * @return void
     *
     * @deprecated 1.0.0
     */
    public function ajax_pre_process()
    {
    }
    /**
     * ajaxProcess is the default handle method for request with ajax-tab.php
     *
     * @return void
     *
     * @deprecated 1.0.0
     */
    public function ajax_process()
    {
    }
    /**
     * Manage page processing
     *
     * @return false|void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     *
     * @deprecated 1.0.0
     */
    public function post_process()
    {
        if (!isset($this->table)) {
            return false;
        }
        // set token
        $token = Tools::get_value('token') ?: $this->token;
        // Sub included tab postProcessing
        $this->include_sub_tab('postProcess', ['status', 'submitAdd1', 'submitDel', 'delete', 'submitFilter', 'submitReset']);
        /* Delete object image */
        if (isset($_GET['deleteImage'])) {
            if (Validate::is_loaded_object($object = $this->load_object())) {
                /** @var ObjectModel $object */
                if ($object->delete_image()) {
                    Tools::redirect_admin(static::$current_index . '&add' . $this->table . '&' . $this->identifier . '=' . Tools::get_value($this->identifier) . '&conf=7&token=' . $token);
                }
            }
            $this->_errors[] = Tools::display_error('An error occurred during image deletion (cannot load object).');
        } elseif (isset($_GET['delete' . $this->table])) {
            if ($this->tab_access[Profile::PERMISSION_DELETE]) {
                if (Validate::is_loaded_object($object = $this->load_object()) && isset($this->field_image_settings)) {
                    /** @var ObjectModel $object */
                    if ($this->deleted) {
                        $object->delete_image();
                        $object->deleted = 1;
                        if (method_exists($object, 'cleanPositions')) {
                            $object->clean_positions();
                        }
                        if ($object->update()) {
                            Tools::redirect_admin(static::$current_index . '&conf=1&token=' . $token);
                        }
                    } elseif ($object->delete()) {
                        if (method_exists($object, 'cleanPositions')) {
                            $object->clean_positions();
                        }
                        Tools::redirect_admin(static::$current_index . '&conf=1&token=' . $token);
                    }
                    $this->_errors[] = Tools::display_error('An error occurred during deletion.');
                } else {
                    $this->_errors[] = Tools::display_error('An error occurred while deleting object.') . ' <b>' . $this->table . '</b> ' . Tools::display_error('(cannot load object)');
                }
            } else {
                $this->_errors[] = Tools::display_error('You do not have permission to delete here.');
            }
        } elseif ((isset($_GET['status' . $this->table]) || isset($_GET['status'])) && Tools::get_value($this->identifier)) {
            if ($this->tab_access[Profile::PERMISSION_EDIT]) {
                if (Validate::is_loaded_object($object = $this->load_object())) {
                    /** @var ObjectModel $object */
                    if ($object->toggle_status()) {
                        Tools::redirect_admin(static::$current_index . '&conf=5' . (($id_category = Tools::get_int_value('id_category')) && Tools::get_int_value('id_product') ? '&id_category=' . $id_category : '') . '&token=' . $token);
                    } else {
                        $this->_errors[] = Tools::display_error('An error occurred while updating status.');
                    }
                } else {
                    $this->_errors[] = Tools::display_error('An error occurred while updating status for object.') . ' <b>' . $this->table . '</b> ' . Tools::display_error('(cannot load object)');
                }
            } else {
                $this->_errors[] = Tools::display_error('You do not have permission to edit here.');
            }
        } elseif (isset($_GET['position'])) {
            /** @var ObjectModel $object */
            if (!$this->tab_access[Profile::PERMISSION_EDIT]) {
                $this->_errors[] = Tools::display_error('You do not have permission to edit here.');
            } elseif (!Validate::is_loaded_object($object = $this->load_object())) {
                $this->_errors[] = Tools::display_error('An error occurred while updating status for object.') . ' <b>' . $this->table . '</b> ' . Tools::display_error('(cannot load object)');
            } elseif (!$object->update_position(Tools::get_int_value('way'), Tools::get_int_value('position'))) {
                $this->_errors[] = Tools::display_error('Failed to update the position.');
            } else {
                Tools::redirect_admin(static::$current_index . '&' . $this->table . 'Orderby=position&' . $this->table . 'Orderway=asc&conf=5' . (($id_identifier = Tools::get_int_value($this->identifier)) ? '&' . $this->identifier . '=' . $id_identifier : '') . '&token=' . $token);
            }
        } elseif (Tools::get_value('submitDel' . $this->table)) {
            if ($this->tab_access[Profile::PERMISSION_DELETE]) {
                if (isset($_POST[$this->table . 'Box'])) {
                    $result = true;
                    if ($this->deleted) {
                        foreach (Tools::get_array_value($this->table . 'Box') as $id) {
                            /** @var ObjectModel $toDelete */
                            $to_delete = new $this->class_name($id);
                            $to_delete->deleted = 1;
                            $result = $result && $to_delete->update();
                        }
                    } else {
                        /** @var ObjectModel $object */
                        $object = new $this->class_name();
                        $result = $object->delete_selection(Tools::get_array_value($this->table . 'Box'));
                    }
                    if ($result) {
                        Tools::redirect_admin(static::$current_index . '&conf=2&token=' . $token);
                    }
                    $this->_errors[] = Tools::display_error('An error occurred while deleting selection.');
                    // clean carriers positions
                    Carrier::clean_positions();
                } else {
                    $this->_errors[] = Tools::display_error('You must select at least one element to delete.');
                }
            } else {
                $this->_errors[] = Tools::display_error('You do not have permission to delete here.');
            }
        } elseif (Tools::get_value('submitAdd' . $this->table)) {
            /* Checking fields validity */
            $this->validate_rules();
            if (!count($this->_errors)) {
                $id = Tools::get_int_value($this->identifier);
                /* Object update */
                if ($id) {
                    if ($this->tab_access[Profile::PERMISSION_EDIT] || $this->table == 'employee' && $this->context->employee->id == Tools::get_int_value('id_employee') && Tools::is_submit('updateemployee')) {
                        /** @var ObjectModel $object */
                        $object = new $this->class_name($id);
                        if (Validate::is_loaded_object($object)) {
                            /* Specific to objects which must not be deleted */
                            if ($this->deleted && $this->before_delete($object)) {
                                /** @var ObjectModel $objectNew */
                                // Create new one with old objet values
                                $object_new = new $this->class_name($object->id);
                                $object_new->id = null;
                                if (property_exists($object_new, 'date_add')) {
                                    $object_new->date_add = '';
                                }
                                if (property_exists($object_new, 'date_upd')) {
                                    $object_new->date_upd = '';
                                }
                                // Update old object to deleted
                                $object->deleted = 1;
                                $object->update();
                                // Update new object with post values
                                $this->copy_from_post($object_new, $this->table);
                                $result = $object_new->add();
                                if (Validate::is_loaded_object($object_new)) {
                                    $this->after_delete($object_new, $object->id);
                                }
                            } else {
                                $this->copy_from_post($object, $this->table);
                                $result = $object->update();
                                $this->after_update($object);
                            }
                            if ($object->id) {
                                $this->update_asso_shop($object->id);
                            }
                            if (!$result) {
                                $this->_errors[] = Tools::display_error('An error occurred while updating object.') . ' <b>' . $this->table . '</b> (' . Db::get_instance()->get_msg_error() . ')';
                            } elseif ($this->post_image($object->id) && !count($this->_errors)) {
                                if ($this->table == 'group' && method_exists($this, 'updateRestrictions')) {
                                    $this->update_restrictions($object->id);
                                }
                                $parent_id = Tools::get_int_value('id_parent', 1);
                                // Specific back redirect
                                if ($back = Tools::get_value('back')) {
                                    Tools::redirect_admin(urldecode($back) . '&conf=4');
                                }
                                // Specific scene feature
                                if (Tools::get_value('stay_here') == 'on' || Tools::get_value('stay_here') == 'true' || Tools::get_value('stay_here') == '1') {
                                    Tools::redirect_admin(static::$current_index . '&' . $this->identifier . '=' . $object->id . '&conf=4&updatescene&token=' . $token);
                                }
                                // Save and stay on same form
                                if (Tools::is_submit('submitAdd' . $this->table . 'AndStay')) {
                                    Tools::redirect_admin(static::$current_index . '&' . $this->identifier . '=' . $object->id . '&conf=4&update' . $this->table . '&token=' . $token);
                                }
                                // Save and back to parent
                                if (Tools::is_submit('submitAdd' . $this->table . 'AndBackToParent')) {
                                    Tools::redirect_admin(static::$current_index . '&' . $this->identifier . '=' . $parent_id . '&conf=4&token=' . $token);
                                }
                                // Default behavior (save and back)
                                Tools::redirect_admin(static::$current_index . ($parent_id ? '&' . $this->identifier . '=' . $object->id : '') . '&conf=4&token=' . $token);
                            }
                        } else {
                            $this->_errors[] = Tools::display_error('An error occurred while updating object.') . ' <b>' . $this->table . '</b> ' . Tools::display_error('(cannot load object)');
                        }
                    } else {
                        $this->_errors[] = Tools::display_error('You do not have permission to edit here.');
                    }
                } else if ($this->tab_access[Profile::PERMISSION_ADD]) {
                    /** @var ObjectModel $object */
                    $object = new $this->class_name();
                    $this->copy_from_post($object, $this->table);
                    if (!$object->add()) {
                        $this->_errors[] = Tools::display_error('An error occurred while creating object.') . ' <b>' . $this->table . ' (' . Db::get_instance()->get_msg_error() . ')</b>';
                    } elseif (($_POST[$this->identifier] = $object->id) && $this->post_image($object->id) && !count($this->_errors) && $this->_redirect) {
                        $parent_id = Tools::get_int_value('id_parent', 1);
                        $this->after_add($object);
                        $this->update_asso_shop($object->id);
                        if ($this->table == 'group' && method_exists($this, 'updateRestrictions')) {
                            $this->update_restrictions($object->id);
                            // assign group access to every categories
                            $categories = Category::get_categories($this->context->language->id, true);
                            $row_list = [];
                            foreach ($categories as $category) {
                                foreach ($category as $categ_id => $categ) {
                                    if ($categ_id != 1) {
                                        $row_list[] = ['id_category' => $categ_id, 'id_group' => $object->id];
                                    }
                                }
                            }
                            Db::get_instance()->insert('category_group', $row_list);
                        }
                        // Save and stay on same form
                        if (Tools::is_submit('submitAdd' . $this->table . 'AndStay')) {
                            Tools::redirect_admin(static::$current_index . '&' . $this->identifier . '=' . $object->id . '&conf=3&update' . $this->table . '&token=' . $token);
                        }
                        // Save and back to parent
                        if (Tools::is_submit('submitAdd' . $this->table . 'AndBackToParent')) {
                            Tools::redirect_admin(static::$current_index . '&' . $this->identifier . '=' . $parent_id . '&conf=3&token=' . $token);
                        }
                        // Default behavior (save and back)
                        Tools::redirect_admin(static::$current_index . ($parent_id ? '&' . $this->identifier . '=' . $object->id : '') . '&conf=3&token=' . $token);
                    }
                } else {
                    $this->_errors[] = Tools::display_error('You do not have permission to add here.');
                }
            }
            $this->_errors = array_unique($this->_errors);
        } elseif (isset($_POST['submitReset' . $this->table])) {
            $filters = $this->context->cookie->get_family($this->table . 'Filter_');
            foreach ($filters as $cookie_key => $filter) {
                if (strncmp((string) $cookie_key, $this->table . 'Filter_', 7 + mb_strlen($this->table)) == 0) {
                    $key = mb_substr((string) $cookie_key, 7 + mb_strlen($this->table));
                    /* Table alias could be specified using a ! eg. alias!field */
                    $tmp_tab = explode('!', $key);
                    $key = count($tmp_tab) > 1 ? $tmp_tab[1] : $tmp_tab[0];
                    if (array_key_exists($key, $this->fields_display)) {
                        unset($this->context->cookie->{$cookie_key});
                    }
                }
            }
            if (isset($this->context->cookie->{'submitFilter' . $this->table})) {
                unset($this->context->cookie->{'submitFilter' . $this->table});
            }
            if (isset($this->context->cookie->{$this->table . 'Orderby'})) {
                unset($this->context->cookie->{$this->table . 'Orderby'});
            }
            if (isset($this->context->cookie->{$this->table . 'Orderway'})) {
                unset($this->context->cookie->{$this->table . 'Orderway'});
            }
            unset($_POST);
        } elseif (Tools::get_value('submitOptions' . $this->table)) {
            $this->update_options($token);
        } elseif (Tools::is_submit('submitFilter' . $this->table) || $this->context->cookie->{'submitFilter' . $this->table} !== false) {
            $_POST = array_merge($this->context->cookie->get_family($this->table . 'Filter_'), $_POST ?? []);
            foreach ($_POST as $key => $value) {
                /* Extracting filters from $_POST on key filter_ */
                if ($value != null && !strncmp((string) $key, $this->table . 'Filter_', 7 + mb_strlen($this->table))) {
                    $key = mb_substr((string) $key, 7 + mb_strlen($this->table));
                    /* Table alias could be specified using a ! eg. alias!field */
                    $tmp_tab = explode('!', $key);
                    $filter = count($tmp_tab) > 1 ? $tmp_tab[1] : $tmp_tab[0];
                    if ($field = $this->filter_to_field($key, $filter)) {
                        $type = array_key_exists('filter_type', $field) ? $field['filter_type'] : (array_key_exists('type', $field) ? $field['type'] : false);
                        $key = isset($tmp_tab[1]) ? $tmp_tab[0] . '.`' . bq_sql($tmp_tab[1]) . '`' : '`' . bq_sql($tmp_tab[0]) . '`';
                        if (array_key_exists('tmpTableFilter', $field)) {
                            $sql_filter =& $this->_tmp_table_filter;
                        } elseif (array_key_exists('havingFilter', $field)) {
                            $sql_filter =& $this->_filter_having;
                        } else {
                            $sql_filter =& $this->_filter;
                        }
                        /* Only for date filtering (from, to) */
                        if (is_array($value)) {
                            if (!empty($value[0])) {
                                if (!Validate::is_date($value[0])) {
                                    $this->_errors[] = Tools::display_error('\'From:\' date format is invalid (YYYY-MM-DD)');
                                } else {
                                    $sql_filter .= ' AND ' . $key . ' >= \'' . p_sql(Tools::date_from($value[0])) . '\'';
                                }
                            }
                            if (!empty($value[1])) {
                                if (!Validate::is_date($value[1])) {
                                    $this->_errors[] = Tools::display_error('\'To:\' date format is invalid (YYYY-MM-DD)');
                                } else {
                                    $sql_filter .= ' AND ' . $key . ' <= \'' . p_sql(Tools::date_to($value[1])) . '\'';
                                }
                            }
                        } else {
                            $sql_filter .= ' AND ';
                            if ($type == 'int' || $type == 'bool') {
                                $sql_filter .= ($key == $this->identifier || $key == '`' . $this->identifier . '`' || $key == '`active`' ? 'a.' : '') . p_sql($key) . ' = ' . (int) $value . ' ';
                            } elseif ($type == 'decimal') {
                                $sql_filter .= ($key == $this->identifier || $key == '`' . $this->identifier . '`' ? 'a.' : '') . p_sql($key) . ' = ' . (float) $value . ' ';
                            } elseif ($type == 'select') {
                                $sql_filter .= ($key == $this->identifier || $key == '`' . $this->identifier . '`' ? 'a.' : '') . p_sql($key) . ' = \'' . p_sql($value) . '\' ';
                            } else {
                                $sql_filter .= ($key == $this->identifier || $key == '`' . $this->identifier . '`' ? 'a.' : '') . p_sql($key) . ' LIKE \'%' . p_sql($value) . '%\' ';
                            }
                        }
                    }
                }
            }
        } elseif (Tools::is_submit('submitFields') && $this->required_database && $this->tab_access[Profile::PERMISSION_ADD] && $this->tab_access[Profile::PERMISSION_DELETE]) {
            $fields = Tools::get_array_value('fieldsBox');
            /** @var ObjectModel $object */
            $object = new $this->class_name();
            if (!$object->add_fields_required_database($fields)) {
                $this->_errors[] = Tools::display_error('Error in updating required fields');
            } else {
                Tools::redirect_admin(static::$current_index . '&conf=4&token=' . $token);
            }
        }
    }
    /**
     * Load class object using identifier in $_GET (if possible)
     * otherwise return an empty object, or die
     *
     * @param bool $opt Return an empty object if load fail
     *
     * @return object
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function load_object($opt = false)
    {
        $id = Tools::get_int_value($this->identifier);
        if ($id && Validate::is_unsigned_id($id)) {
            if (!$this->_object) {
                $this->_object = new $this->class_name($id);
            }
            if (Validate::is_loaded_object($this->_object)) {
                return $this->_object;
            }
            $this->_errors[] = Tools::display_error('Object cannot be loaded (not found)');
        } elseif ($opt) {
            $this->_object = new $this->class_name();
            return $this->_object;
        } else {
            $this->_errors[] = Tools::display_error('Object cannot be loaded (identifier missing or invalid)');
        }
        $this->display_errors();
    }
    /**
     * Display errors
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function display_errors(): void
    {
        if (($nb_errors = count($this->_errors)) && $this->_include_container) {
            echo '<script type="text/javascript">
				$(document).ready(function() {
					$(\'#hideError\').unbind(\'click\').click(function(){
						$(\'.error\').hide(\'slow\', function (){
							$(\'.error\').remove();
						});
						return false;
					});
				});
			  </script>
			<div class="error"><span style="float:right"><a id="hideError" href=""><img alt="X" src="../img/admin/close.png" /></a></span><img src="../img/admin/error2.png" />';
            if (count($this->_errors) == 1) {
                echo $this->_errors[0];
            } else {
                echo sprintf($this->l('%d errors'), $nb_errors) . '<br /><ol>';
                foreach ($this->_errors as $error) {
                    echo '<li>' . $error . '</li>';
                }
                echo '</ol>';
            }
            echo '</div>';
        }
        if ($this->_include_container) {
            $error_handler = Service_Locator::get_instance()->get_error_handler();
            $error_messages = $error_handler->get_error_messages(false, E_ALL);
            $smarty = Context::get_context()->smarty;
            $smarty->assign('php_errors', $error_messages);
            echo $smarty->fetch('error.tpl');
        }
        $this->include_sub_tab('displayErrors');
    }
    /**
     * Manage page display (form, list...)
     *
     * @param string $className Allow to validate a different class than the current one
     *
     * @throws PrestaShopException
     */
    public function validate_rules($class_name = false): void
    {
        if (!$class_name) {
            $class_name = $this->class_name;
        }
        /* Class specific validation rules */
        $rules = call_user_func([$class_name, 'getValidationRules'], $class_name);
        if (count($rules['requiredLang']) || count($rules['sizeLang']) || count($rules['validateLang'])) {
            /* Language() instance determined by default language */
            $default_language = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
            /* All availables languages */
            $languages = Language::get_languages(false);
        }
        /* Checking for required fields */
        foreach ($rules['required'] as $field) {
            if (!$value = Tools::get_value($field) == false) {
                continue;
            }
            if (!((string) $value != '0')) {
                continue;
            }
            if (!(!Tools::get_value($this->identifier) || $field != 'passwd' && $field != 'no-picture')) {
                continue;
            }
            $this->_errors[] = sprintf(Tools::display_error('The field %s is required.'), call_user_func([$class_name, 'displayFieldName'], $field, $class_name));
        }
        /* Checking for multilingual required fields */
        foreach ($rules['requiredLang'] as $field_lang) {
            if (($empty = Tools::get_value($field_lang . '_' . $default_language->id)) === false || $empty !== '0' && empty($empty)) {
                $this->_errors[] = sprintf(Tools::display_error('The field %1$s is required at least in %2$s.'), call_user_func([$class_name, 'displayFieldName'], $field_lang, $class_name), $default_language->name);
            }
        }
        /* Checking for maximum fields sizes */
        foreach ($rules['size'] as $field => $max_length) {
            if (Tools::get_value($field) !== false && mb_strlen(Tools::get_value($field)) > $max_length) {
                $this->_errors[] = sprintf(Tools::display_error('field %1$s is too long. (%2$d chars max)'), call_user_func([$class_name, 'displayFieldName'], $field, $class_name), $max_length);
            }
        }
        /* Checking for maximum multilingual fields size */
        foreach ($rules['sizeLang'] as $field_lang => $max_length) {
            foreach ($languages as $language) {
                if (Tools::get_value($field_lang . '_' . $language['id_lang']) !== false && mb_strlen(Tools::get_value($field_lang . '_' . $language['id_lang'])) > $max_length) {
                    $this->_errors[] = sprintf(Tools::display_error('field %1$s is too long. (%2$d chars max, html chars including)'), call_user_func([$class_name, 'displayFieldName'], $field_lang, $class_name), $max_length);
                }
            }
        }
        /* Overload this method for custom checking */
        $this->_child_validation();
        /* Checking for fields validity */
        foreach ($rules['validate'] as $field => $function) {
            if (!$value = Tools::get_value($field) !== false && !empty($value)) {
                continue;
            }
            if (!($field != 'passwd')) {
                continue;
            }
            if (Validate::$function($value)) {
                continue;
            }
            $this->_errors[] = sprintf(Tools::display_error('The field %1$s (%2$s) is invalid.'), call_user_func([$class_name, 'displayFieldName'], $field, $class_name));
        }
        /* Checking for passwd_old validity */
        if (($value = Tools::get_value('passwd')) != false) {
            if ($class_name == 'Employee' && !Validate::is_passwd_admin($value)) {
                $this->_errors[] = sprintf(Tools::display_error('The field %1$s (%2$s) is invalid.'), call_user_func([$class_name, 'displayFieldName'], 'passwd', $class_name));
            } elseif ($class_name == 'Customer' && !Validate::is_passwd($value)) {
                $this->_errors[] = sprintf(Tools::display_error('The field %1$s (%2$s) is invalid.'), call_user_func([$class_name, 'displayFieldName'], 'passwd', $class_name));
            }
        }
        /* Checking for multilingual fields validity */
        foreach ($rules['validateLang'] as $field_lang => $function) {
            foreach ($languages as $language) {
                if (!$value = Tools::get_value($field_lang . '_' . $language['id_lang']) !== false) {
                    continue;
                }
                if (empty($value)) {
                    continue;
                }
                if (Validate::$function($value)) {
                    continue;
                }
                $this->_errors[] = sprintf(Tools::display_error('The field %1$s (%2$s) is invalid.'), call_user_func([$class_name, 'displayFieldName'], $field_lang, $class_name), $language['name']);
            }
        }
    }
    /**
     * Overload this method for custom checking
     *
     * @deprecated 1.0.0
     */
    protected function _child_validation()
    {
    }
    /**
     * Called before deletion
     *
     * @param object $object Object
     *
     * @return bool
     *
     * @deprecated 1.0.0
     */
    protected function before_delete($object)
    {
        return true;
    }
    /**
     * Copy datas from $_POST to object
     *
     * @param ObjectModel &$object Object model
     * @param string $table Object table
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    protected function copy_from_post(&$object, string $table)
    {
        /* Classical fields */
        foreach ($_POST as $key => $value) {
            if (property_exists($object, $key) && $key != 'id_' . $table) {
                /* Do not take care of password field if empty */
                if ($key == 'passwd' && Tools::get_value('id_' . $table) && empty($value)) {
                    continue;
                }
                /* Automatically encrypt password in MD5 */
                if ($key == 'passwd' && !empty($value)) {
                    $value = Tools::encrypt($value);
                }
                $object->{$key} = $value;
            }
        }
        /* Multilingual fields */
        $rules = call_user_func([$object::class, 'getValidationRules'], $object::class);
        if (count($rules['validateLang'])) {
            $language_ids = Language::get_i_ds(false);
            foreach ($language_ids as $id_lang) {
                foreach (array_keys($rules['validateLang']) as $field) {
                    if (Tools::is_submit($field . '_' . (int) $id_lang)) {
                        $object->{$field}[(int) $id_lang] = Tools::get_value($field . '_' . (int) $id_lang);
                    }
                }
            }
        }
    }
    /**
     * Called before deletion
     *
     * @param object $object Object
     *
     * @return bool
     *
     * @deprecated 1.0.0
     */
    protected function after_delete($object, $old_id)
    {
        return true;
    }
    /**
     * @param ObjectModel $object
     *
     * @return bool
     *
     * @deprecated 1.0.0
     */
    protected function after_update($object)
    {
        return true;
    }
    /**
     * @param bool $idObject
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    protected function update_asso_shop($id_object = false)
    {
        if (!Shop::is_feature_active()) {
            return;
        }
        if (!$assos = static::get_asso_shop($this->table, $id_object)) {
            return;
        }
        Db::get_instance()->execute('DELETE FROM ' . _DB_PREFIX_ . $this->table . '_' . $assos[1] . ($id_object ? ' WHERE `' . $this->identifier . '`=' . (int) $id_object : ''));
        foreach ($assos[0] as $asso) {
            Db::get_instance()->execute('INSERT INTO ' . _DB_PREFIX_ . $this->table . '_' . $assos[1] . ' (`' . p_sql($this->identifier) . '`, id_' . $assos[1] . ')
											VALUES(' . (int) $asso['id_object'] . ', ' . (int) $asso['id_' . $assos[1]] . ')');
        }
    }
    /**
     * Overload this method for custom checking
     *
     * @param int $id Object id used for deleting images
     *
     * @return bool
     *
     * @throws PrestaShopException
     * @throws SmartyException
     *
     * @deprecated 1.0.0
     */
    protected function post_image($id)
    {
        if (isset($this->field_image_settings['name']) && isset($this->field_image_settings['dir'])) {
            return $this->upload_image($id, $this->field_image_settings['name'], $this->field_image_settings['dir'] . '/');
        }
        foreach ($this->field_image_settings as $image) {
            if (isset($image['name']) && isset($image['dir'])) {
                $this->upload_image($id, $image['name'], $image['dir'] . '/');
            }
        }
        return !count($this->_errors);
    }
    /**
     * @param int $id
     * @param string $name
     * @param bool $ext
     * @param int|null $width
     * @param int|null $height
     *
     * @return bool
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function upload_image($id, $name, string $dir, $ext = false, $width = null, $height = null)
    {
        if (!empty($_FILES[$name]['tmp_name'])) {
            // Delete old image
            if (Validate::is_loaded_object($object = $this->load_object())) {
                $object->delete_image();
            } else {
                return false;
            }
            // Check image validity
            $max_size = $this->max_image_size ?? 0;
            if ($error = Image_Manager::validate_upload($_FILES[$name], Tools::get_max_upload_size($max_size))) {
                $this->_errors[] = $error;
            } elseif (!($tmp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES[$name]['tmp_name'], $tmp_name)) {
                return false;
            } else {
                $_FILES[$name]['tmp_name'] = $tmp_name;
                // Copy new image
                if (!Image_Manager::resize($tmp_name, _PS_IMG_DIR_ . $dir . $id . '.' . $this->image_type, (int) $width, (int) $height, $ext ?: $this->image_type)) {
                    $this->_errors[] = Tools::display_error('An error occurred while uploading image.');
                }
                if (count($this->_errors)) {
                    return false;
                }
                if ($this->after_image_upload()) {
                    unlink($tmp_name);
                    return true;
                }
                return false;
            }
        }
        return true;
    }
    /**
     * Check rights to view the current tab
     *
     * @return bool
     *
     * @deprecated 1.0.0
     */
    protected function after_image_upload()
    {
        return true;
    }
    /**
     * @param ObjectModel $object
     *
     * @return bool
     *
     * @deprecated 1.0.0
     */
    protected function after_add($object)
    {
        return true;
    }
    /**
     * Update options and preferences
     *
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    protected function update_options(string $token)
    {
        if ($this->tab_access[Profile::PERMISSION_EDIT]) {
            $this->before_update_options();
            $language_ids = Language::get_i_ds(false);
            foreach ($this->options_list as $category_data) {
                $fields = $category_data['fields'];
                /* Check required fields */
                foreach ($fields as $field => $values) {
                    if (isset($values['required']) && $values['required'] && !empty($_POST['multishopOverrideOption'][$field])) {
                        if (isset($values['type']) && $values['type'] == 'textLang') {
                            foreach ($language_ids as $id_lang) {
                                if (($value = Tools::get_value($field . '_' . $id_lang)) == false && (string) $value != '0') {
                                    $this->_errors[] = sprintf(Tools::display_error('field %s is required.'), $values['title']);
                                }
                            }
                        } elseif (($value = Tools::get_value($field)) == false && (string) $value != '0') {
                            $this->_errors[] = sprintf(Tools::display_error('field %s is required.'), $values['title']);
                        }
                    }
                }
                /* Check fields validity */
                foreach ($fields as $field => $values) {
                    if (isset($values['type']) && $values['type'] == 'textLang') {
                        foreach ($language_ids as $id_lang) {
                            if (Tools::get_value($field . '_' . $id_lang) && isset($values['validation'])) {
                                $values_validation = $values['validation'];
                                if (!Validate::$values_validation(Tools::get_value($field . '_' . $id_lang))) {
                                    $this->_errors[] = sprintf(Tools::display_error('field %s is invalid.'), $values['title']);
                                }
                            }
                        }
                    } elseif (Tools::get_value($field) && isset($values['validation'])) {
                        $values_validation = $values['validation'];
                        if (!Validate::$values_validation(Tools::get_value($field))) {
                            $this->_errors[] = sprintf(Tools::display_error('field %s is invalid.'), $values['title']);
                        }
                    }
                }
                /* Default value if null */
                foreach ($fields as $field => $values) {
                    if (!Tools::get_value($field) && isset($values['default'])) {
                        $_POST[$field] = $values['default'];
                    }
                }
                if (!count($this->_errors)) {
                    foreach ($fields as $key => $options) {
                        if (isset($options['visibility']) && $options['visibility'] > Shop::get_context()) {
                            continue;
                        }
                        if (Shop::is_feature_active() && empty($_POST['multishopOverrideOption'][$key])) {
                            Configuration::delete_from_context($key);
                            continue;
                        }
                        // check if a method updateOptionFieldName is available
                        $method_name = 'updateOption' . Tools::to_camel_case($key, true);
                        if (method_exists($this, $method_name)) {
                            $this->{$method_name}(Tools::get_value($key));
                        } elseif (isset($options['type']) && in_array($options['type'], ['textLang', 'textareaLang'])) {
                            $list = [];
                            foreach ($language_ids as $id_lang) {
                                $val = isset($options['cast']) ? $options['cast'](Tools::get_value($key . '_' . $id_lang)) : Tools::get_value($key . '_' . $id_lang);
                                if ($this->validate_field($val, $options)) {
                                    if (Validate::is_clean_html($val)) {
                                        $list[$id_lang] = $val;
                                    } else {
                                        $this->_errors[] = Tools::display_error('Can not add configuration ' . $key . ' for lang ' . Language::get_iso_by_id((int) $id_lang));
                                    }
                                }
                            }
                            Configuration::update_value($key, $list);
                        } else {
                            $val = isset($options['cast']) ? $options['cast'](Tools::get_value($key)) : Tools::get_value($key);
                            if ($this->validate_field($val, $options)) {
                                if (Validate::is_clean_html($val)) {
                                    Configuration::update_value($key, $val);
                                } else {
                                    $this->_errors[] = Tools::display_error('Can not add configuration ' . $key);
                                }
                            }
                        }
                    }
                }
            }
            if (count($this->_errors) <= 0) {
                Tools::redirect_admin(static::$current_index . '&conf=6&token=' . $token);
            }
        } else {
            $this->_errors[] = Tools::display_error('You do not have permission to edit here.');
        }
    }
    /**
     * Can be overriden
     *
     * @deprecated 1.0.0
     */
    public function before_update_options()
    {
    }
    /**
     * @param mixed $value
     *
     * @return bool
     * @deprecated 1.0.0
     */
    protected function validate_field($value, array $field)
    {
        if (isset($field['validation'])) {
            $field_validation = $field['validation'];
            if ((!isset($field['empty']) || !$field['empty'] || $value) && method_exists('Validate', $field['validation'])) {
                if (!Validate::$field_validation($value)) {
                    $this->_errors[] = Tools::display_error($field['title'] . ' : Incorrect value');
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * @param string $key
     * @param string $filter
     *
     * @return array|false
     *
     * @deprecated 1.0.0
     */
    protected function filter_to_field($key, $filter)
    {
        foreach ($this->fields_display as $field) {
            if (array_key_exists('filter_key', $field) && $field['filter_key'] == $key) {
                return $field;
            }
        }
        if (array_key_exists($filter, $this->fields_display)) {
            return $this->fields_display[$filter];
        }
        return false;
    }
    /**
     * Display confirmations
     *
     * @deprecated 1.0.0
     */
    public function display_conf(): void
    {
        if ($conf = Tools::get_value('conf')) {
            echo '
			<div class="conf">
				' . $this->_conf[(int) $conf] . '
			</div>';
        }
    }
    /**
     * Display image aside object form
     *
     * @param int $id Object id
     * @param string $image Local image filepath
     * @param int $size Image width
     * @param int|null $idImage Image id (for products with several images)
     * @param string|null $token Employee token used in the image deletion link
     * @param bool $disableCache When turned on a timestamp will be added to the image URI to disable the HTTP cache
     *
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function display_image($id, $image, $size, $id_image = null, $token = null, $disable_cache = false): void
    {
        if (empty($token)) {
            $token = $this->token;
        }
        if ($id && file_exists($image)) {
            echo '
			<div id="image" >
				' . Image_Manager::thumbnail($image, $this->table . '_' . (int) $id . '.' . $this->image_type, $size, $this->image_type, $disable_cache) . '
				<p align="center">' . $this->l('File size') . ' ' . filesize($image) / 1000 . 'kb</p>
				<a href="' . static::$current_index . '&' . $this->identifier . '=' . (int) $id . '&token=' . $token . ($id_image ? '&id_image=' . (int) $id_image : '') . '&deleteImage=1">
				<img src="../img/admin/delete.gif" alt="' . $this->l('Delete') . '" /> ' . $this->l('Delete') . '</a>
			</div>';
        }
    }
    /**
     * Type = select
     *
     * @deprecated 1.0.0
     *
     * @param string $value
     */
    public function display_option_type_select(string $key, array $field, $value): void
    {
        echo '<select name="' . $key . '"' . (isset($field['js']) === true ? ' onchange="' . $field['js'] . '"' : '') . ' id="' . $key . '">';
        foreach ($field['list'] as $option) {
            echo '<option value="' . (isset($option['cast']) ? $option['cast']($option[$field['identifier']]) : $option[$field['identifier']]) . '"' . ($value == $option[$field['identifier']] ? ' selected="selected"' : '') . '>' . $option['name'] . '</option>';
        }
        echo '</select>';
    }
    /**
     * Type = bool
     *
     * @deprecated 1.0.0
     *
     * @param bool $value
     */
    public function display_option_type_bool(string $key, array $field, $value): void
    {
        echo '<label class="t" for="' . $key . '_on"><img src="../img/admin/enabled.gif" alt="' . $this->l('Yes') . '" title="' . $this->l('Yes') . '" /></label>';
        echo '<input type="radio" name="' . $key . '" id="' . $key . '_on" value="1" ' . ($value ? ' checked="checked" ' : '') . ($field['js']['on'] ?? '') . ' />';
        echo '<label class="t" for="' . $key . '_on"> ' . $this->l('Yes') . '</label>';
        echo '<label class="t" for="' . $key . '_off"><img src="../img/admin/disabled.gif" alt="' . $this->l('No') . '" title="' . $this->l('No') . '" style="margin-left: 10px;" /></label>';
        echo '<input type="radio" name="' . $key . '" id="' . $key . '_off" value="0" ' . (!$value ? ' checked="checked" ' : '') . ($field['js']['off'] ?? '') . ' />';
        echo '<label class="t" for="' . $key . '_off"> ' . $this->l('No') . '</label>';
    }
    /**
     * Type = radio
     *
     * @deprecated 1.0.0
     *
     * @param string $value
     */
    public function display_option_type_radio(string $key, array $field, $value): void
    {
        foreach ($field['choices'] as $k => $v) {
            echo '<input type="radio" name="' . $key . '" id="' . $key . $k . '_on" value="' . (int) $k . '"' . ($k == $value ? ' checked="checked"' : '') . (isset($field['js'][$k]) ? ' ' . $field['js'][$k] : '') . ' /><label class="t" for="' . $key . $k . '_on"> ' . $v . '</label><br />';
        }
        echo '<br />';
    }
    /**
     * Type = password
     *
     * @deprecated 1.0.0
     *
     * @param string $key
     * @param array $field
     * @param string $value
     */
    public function display_option_type_password($key, $field, $value): void
    {
        $this->display_option_type_text($key, $field, '');
    }
    /**
     * Type = textarea
     *
     * @param string $value
     *
     * @deprecated 1.0.0
     */
    public function display_option_type_textarea(string $key, array $field, $value): void
    {
        echo '<textarea name=' . $key . ' cols="' . $field['cols'] . '" rows="' . $field['rows'] . '">' . htmlentities($value, ENT_COMPAT, 'UTF-8') . '</textarea>';
    }
    /**
     * Type = file
     *
     * @param string $value
     *
     * @deprecated 1.0.0
     */
    public function display_option_type_file(string $key, array $field, $value): void
    {
        if (isset($field['thumb']) && $field['thumb'] && $field['thumb']['pos'] == 'before') {
            echo '<img src="' . $field['thumb']['file'] . '" alt="' . $field['title'] . '" title="' . $field['title'] . '" /><br />';
        }
        echo '<input type="file" name="' . $key . '" />';
    }
    /**
     * Type = image
     *
     * @param string $value
     *
     * @deprecated 1.0.0
     */
    public function display_option_type_image(string $key, array $field, $value): void
    {
        echo '<table cellspacing="0" cellpadding="0">';
        echo '<tr>';
        /*if ($name == 'themes')
          echo '
          <td colspan="'.sizeof($field['list']).'">
              <b>'.$this->l('In order to use a new theme, please follow these steps:', get_class()).'</b>
              <ul>
                  <li>'.$this->l('Import your theme using this module:', get_class()).' <a href="index.php?tab=AdminModules&token='.Tools::getAdminTokenLite('AdminModules').'&filtername=themeinstallator" style="text-decoration: underline;">'.$this->l('Theme installer', get_class()).'</a></li>
                  <li>'.$this->l('When your theme is imported, please select the theme in this page', get_class()).'</li>
              </ul>
          </td>
          </tr>
          <tr>
          ';*/
        $i = 0;
        foreach ($field['list'] as $theme) {
            echo '<td class="center" style="width: 180px; padding:0px 20px 20px 0px;">';
            echo '<input type="radio" name="' . $key . '" id="' . $key . '_' . $theme['name'] . '_on" style="vertical-align: text-bottom;" value="' . $theme['name'] . '"' . (_THEME_NAME_ == $theme['name'] ? 'checked="checked"' : '') . ' />';
            echo '<label class="t" for="' . $key . '_' . $theme['name'] . '_on"> ' . mb_strtolower((string) $theme['name']) . '</label>';
            echo '<br />';
            echo '<label class="t" for="' . $key . '_' . $theme['name'] . '_on">';
            echo '<img src="../themes/' . $theme['name'] . '/preview.jpg" alt="' . mb_strtolower((string) $theme['name']) . '">';
            echo '</label>';
            echo '</td>';
            if (isset($field['max']) && ($i + 1) % $field['max'] == 0) {
                echo '</tr><tr>';
            }
            $i++;
        }
        echo '</tr>';
        echo '</table>';
    }
    /**
     * Type = textLang
     *
     * @param string $value
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    public function display_option_type_text_lang(string $key, array $field, $value): void
    {
        $languages = Language::get_languages(false);
        foreach ($languages as $language) {
            $value = Tools::get_value($key . '_' . $language['id_lang'], Configuration::get($key, $language['id_lang']));
            echo '<div id="' . $key . '_' . $language['id_lang'] . '" style="margin-bottom:8px; display: ' . ($language['id_lang'] == $this->context->language->id ? 'block' : 'none') . '; float: left; vertical-align: top;">';
            echo '<input type="text" size="' . (isset($field['size']) ? (int) $field['size'] : 5) . '" name="' . $key . '_' . $language['id_lang'] . '" value="' . htmlentities($value, ENT_COMPAT, 'UTF-8') . '" />';
            echo '</div>';
        }
        $this->display_flags($languages, $this->context->language->id, $key, $key);
    }
    /**
     * Display flags in forms for translations
     *
     * @param array $languages All languages available
     * @param int $default_language Default language id
     * @param string $ids Multilingual div ids in form
     * @param string $id Current div id]
     * @param bool $return define the return way : false for a display, true for a return
     * @param bool $use_vars_instead_of_ids use an js vars instead of ids seperate by "¤"
     *
     * @return false|string|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function display_flags($languages, $default_language, string $ids, string $id, $return = false, $use_vars_instead_of_ids = false)
    {
        if (count($languages) == 1) {
            return false;
        }
        $image_extension = Image_Manager::get_default_image_extension();
        $output = '
		<div class="displayed_flag">
			<img src="../img/l/' . $default_language . '.' . $image_extension . '" class="pointer" id="language_current_' . $id . '" onclick="toggleLanguageFlags(this);" alt="" />
		</div>
		<div id="languages_' . $id . '" class="language_flags">
			' . $this->l('Choose language:') . '<br /><br />';
        foreach ($languages as $language) {
            if ($use_vars_instead_of_ids) {
                $output .= '<img src="../img/l/' . (int) $language['id_lang'] . '.' . $image_extension . '" class="pointer" alt="' . $language['name'] . '" title="' . $language['name'] . '" onclick="changeLanguage(\'' . $id . '\', ' . $ids . ', ' . $language['id_lang'] . ', \'' . $language['iso_code'] . '\');" /> ';
            } else {
                $output .= '<img src="../img/l/' . (int) $language['id_lang'] . '.' . $image_extension . '" class="pointer" alt="' . $language['name'] . '" title="' . $language['name'] . '" onclick="changeLanguage(\'' . $id . '\', \'' . $ids . '\', ' . $language['id_lang'] . ', \'' . $language['iso_code'] . '\');" /> ';
            }
        }
        $output .= '</div>';
        if ($return) {
            return $output;
        }
        echo $output;
    }
    /**
     * Type = TextareaLang
     *
     * @param string $value
     *
     * @throws PrestaShopException
     */
    public function display_option_type_textarea_lang(string $key, array $field, $value): void
    {
        $languages = Language::get_languages(false);
        foreach ($languages as $language) {
            $value = Configuration::get($key, $language['id_lang']);
            echo '<div id="' . $key . '_' . $language['id_lang'] . '" style="display: ' . ($language['id_lang'] == $this->context->language->id ? 'block' : 'none') . '; float: left;">';
            echo '<textarea rows="' . (int) $field['rows'] . '" cols="' . (int) $field['cols'] . '"  name="' . $key . '_' . $language['id_lang'] . '">' . str_replace('\r\n', "\n", $value) . '</textarea>';
            echo '</div>';
        }
        $this->display_flags($languages, $this->context->language->id, $key, $key);
        echo '<br style="clear:both">';
    }
    /**
     * Type = selectLang
     *
     * @param string $value
     *
     * @throws PrestaShopException
     */
    public function display_option_type_select_lang(string $key, array $field, $value): void
    {
        $languages = Language::get_languages(false);
        foreach ($languages as $language) {
            echo '<div id="' . $key . '_' . $language['id_lang'] . '" style="margin-bottom:8px; display: ' . ($language['id_lang'] == $this->context->language->id ? 'block' : 'none') . '; float: left; vertical-align: top;">';
            echo '<select name="' . $key . '_' . mb_strtoupper((string) $language['iso_code']) . '">';
            foreach ($field['list'] as $v) {
                echo '<option value="' . (isset($v['cast']) ? $v['cast']($v[$field['identifier']]) : $v[$field['identifier']]) . '"' . (htmlentities(Tools::get_value($key . '_' . mb_strtoupper((string) $language['iso_code']), Configuration::get($key . '_' . mb_strtoupper((string) $language['iso_code'])) ?: ''), ENT_COMPAT, 'UTF-8') == $v[$field['identifier']] ? ' selected="selected"' : '') . '>' . $v['name'] . '</option>';
            }
            echo '</select>';
            echo '</div>';
        }
        $this->display_flags($languages, $this->context->language->id, $key, $key);
    }
    /**
     * Type = price
     *
     * @param string $key
     * @param array $field
     * @param string $value
     */
    public function display_option_type_price($key, $field, $value): void
    {
        echo $this->context->currency->get_sign('left');
        $this->display_option_type_text($key, $field, $value);
        echo $this->context->currency->get_sign('right') . ' ' . $this->l('(tax excl.)');
    }
    /**
     * Type = disabled
     *
     * @param string $key
     * @param string $value
     */
    public function display_option_type_disabled($key, array $field, $value): void
    {
        echo $field['disabled'];
    }
    /**
     * Return field value if possible (both classical and multilingual fields)
     *
     * Case 1 : Return value if present in $_POST / $_GET
     * Case 2 : Return object value
     *
     * @param object $obj Object
     * @param string $key Field name
     * @param int $id_lang Language id (optional)
     * @param int|null $idShop
     *
     * @return string
     */
    public function get_field_value($obj, string $key, $id_lang = null, $id_shop = null)
    {
        if (!$id_shop && $obj->is_lang_multishop()) {
            $id_shop = Context::get_context()->shop->id;
        }
        if ($id_lang) {
            $default_value = $obj->id && isset($obj->{$key}[$id_lang]) ? $obj->{$key}[$id_lang] : '';
        } else {
            $default_value = $obj->{$key} ?? '';
        }
        return Tools::get_value($key . ($id_lang ? '_' . $id_shop . '_' . $id_lang : ''), $default_value);
    }
    /**
     * Display object details
     *
     * @deprecated 1.0.0
     */
    public function view_details()
    {
    }
    /**
     * Check rights to view the current tab
     *
     * @param bool $disable
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function view_access($disable = false)
    {
        if ($disable) {
            return true;
        }
        return $this->context->employee->has_access($this->id, Profile::PERMISSION_VIEW);
    }
    /**
     * Check for security token
     *
     * @deprecated 1.0.0
     */
    public function check_token()
    {
        $token = Tools::get_value('token');
        return !empty($token) && $token === $this->token;
    }
    /**
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    protected function warn_domain_name()
    {
        if ($_SERVER['HTTP_HOST'] != Configuration::get('PS_SHOP_DOMAIN') && $_SERVER['HTTP_HOST'] != Configuration::get('PS_SHOP_DOMAIN_SSL')) {
            $this->display_warning($this->l('You are currently connected with the following domain name:') . ' <span style="color: #CC0000;">' . $_SERVER['HTTP_HOST'] . '</span><br />' . $this->l('This one is different from the main shop\'s domain name set in "Preferences > SEO & URLs":') . ' <span style="color: #CC0000;">' . Configuration::get('PS_SHOP_DOMAIN') . '</span><br />
			<a href="index.php?tab=AdminMeta&token=' . Tools::get_admin_token_lite('AdminMeta') . '#SEO%20%26%20URLs">' . $this->l('Click here if you want to modify the main shop\'s domain name') . '</a>');
        }
    }
    /**
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    protected function display_asso_shop()
    {
        if (!Shop::is_feature_active() || !$this->_object && Shop::get_context() != Shop::CONTEXT_ALL) {
            return;
        }
        $assos = [];
        $sql = 'SELECT id_shop, `' . bq_sql($this->identifier) . '`
				FROM `' . _DB_PREFIX_ . bq_sql($this->table) . '_shop`';
        foreach (Db::read_only()->get_array($sql) as $row) {
            $assos[$row['id_shop']][] = $row[$this->identifier];
        }
        $html = <<<EOF
        \t\t\t<script type="text/javascript">
        \t\t\t\$().ready(function()
        \t\t\t{
        \t\t\t\t// Click on "all shop"
        \t\t\t\t\$('.input_all_shop').click(function()
        \t\t\t\t{
        \t\t\t\t\tvar checked = \$(this).prop('checked');
        \t\t\t\t\t\$('.input_shop_group').attr('checked', checked);
        \t\t\t\t\t\$('.input_shop').attr('checked', checked);
        \t\t\t\t});
        
        \t\t\t\t// Click on a group shop
        \t\t\t\t\$('.input_shop_group').click(function()
        \t\t\t\t{
        \t\t\t\t\t\$('.input_shop[value='+\$(this).val()+']').attr('checked', \$(this).prop('checked'));
        \t\t\t\t\tcheck_all_shop();
        \t\t\t\t});
        
        \t\t\t\t// Click on a shop
        \t\t\t\t\$('.input_shop').click(function()
        \t\t\t\t{
        \t\t\t\t\tcheck_shop_group_status(\$(this).val());
        \t\t\t\t\tcheck_all_shop();
        \t\t\t\t});
        
        \t\t\t\t// Initialize checkbox
        \t\t\t\t\$('.input_shop').each(function(k, v)
        \t\t\t\t{
        \t\t\t\t\tcheck_shop_group_status(\$(v).val());
        \t\t\t\t\tcheck_all_shop();
        \t\t\t\t});
        \t\t\t});
        
        \t\t\tfunction check_shop_group_status(id_group)
        \t\t\t{
        \t\t\t\tvar groupChecked = true;
        \t\t\t\t\$('.input_shop[value='+id_group+']').each(function(k, v)
        \t\t\t\t{
        \t\t\t\t\tif (!\$(v).prop('checked'))
        \t\t\t\t\t\tgroupChecked = false;
        \t\t\t\t});
        \t\t\t\t\$('.input_shop_group[value='+id_group+']').attr('checked', groupChecked);
        \t\t\t}
        
        \t\t\tfunction check_all_shop()
        \t\t\t{
        \t\t\t\tvar allChecked = true;
        \t\t\t\t\$('.input_shop_group').each(function(k, v)
        \t\t\t\t{
        \t\t\t\t\tif (!\$(v).prop('checked'))
        \t\t\t\t\t\tallChecked = false;
        \t\t\t\t});
        \t\t\t\t\$('.input_all_shop').attr('checked', allChecked);
        \t\t\t}
        \t\t\t</script>
        EOF;
        $html .= '<div class="assoShop">';
        $html .= '<table class="table" cellpadding="0" cellspacing="0" width="100%">
					<tr><th>' . $this->l('Shop') . '</th></tr>';
        $html .= '<tr><td><label class="t"><input class="input_all_shop" type="checkbox" /> ' . $this->l('All shops') . '</label></td></tr>';
        foreach (Shop::get_tree() as $group_id => $group_data) {
            $html .= '<tr class="alt_row">';
            $html .= '<td><img style="vertical-align: middle;" alt="" src="../img/admin/lv2_b.gif" /><label class="t"><input class="input_shop_group" type="checkbox" name="checkBoxShopGroupAsso_' . $this->table . '_' . $this->_object->id . '_' . $group_id . '" value="' . $group_id . '" /> ' . $group_data['name'] . '</label></td>';
            $html .= '</tr>';
            $total = count($group_data['shops']);
            $j = 0;
            foreach ($group_data['shops'] as $shop_id => $shop_data) {
                $checked = isset($assos[$shop_id]) && in_array($this->_object->id, $assos[$shop_id]) || !$this->_object->id;
                $html .= '<tr>';
                $html .= '<td><img style="vertical-align: middle;" alt="" src="../img/admin/lv3_' . ($j < $total - 1 ? 'b' : 'f') . '.png" /><label class="child">';
                $html .= '<input class="input_shop" type="checkbox" value="' . $group_id . '" name="checkBoxShopAsso_' . $this->table . '_' . $this->_object->id . '_' . $shop_id . '" id="checkedBox_' . $shop_id . '" ' . ($checked ? 'checked="checked"' : '') . ' /> ';
                $html .= $shop_data['name'] . '</label></td>';
                $html .= '</tr>';
                $j++;
            }
        }
        $html .= '</table></div>';
        echo $html;
    }
    /**
     * Get current URL
     *
     * @param array $remove List of keys to remove from URL
     *
     * @return string
     *
     * @deprecated 1.0.0
     */
    protected function get_current_url($remove = [])
    {
        $url = $_SERVER['REQUEST_URI'];
        if (!$remove) {
            return $url;
        }
        if (!is_array($remove)) {
            $remove = [$remove];
        }
        $url = preg_replace('#(?<=&|\?)(' . implode('|', $remove) . ')=.*?(&|$)#i', '', (string) $url);
        $len = mb_strlen((string) $url);
        if ($url[$len - 1] == '&') {
            return mb_substr((string) $url, 0, $len - 1);
        }
        return $url;
    }
}