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
if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', getcwd());
}
require_once _PS_ADMIN_DIR_ . '/../images.inc.php';
/**
 * @param string $id
 * @param bool $time
 * @return void
 */
function bind_datepicker($id, $time)
{
    Tools::display_as_deprecated(sprintf("Function '%s' will be removed in thirty bees 1.6.0", __FUNCTION__));
    if ($time) {
        echo '
		var dateObj = new Date();
		var hours = dateObj.getHours();
		var mins = dateObj.getMinutes();
		var secs = dateObj.getSeconds();
		if (hours < 10) { hours = "0" + hours; }
		if (mins < 10) { mins = "0" + mins; }
		if (secs < 10) { secs = "0" + secs; }
		var time = " "+hours+":"+mins+":"+secs;';
    }
    echo '
	$(function() {
		$("#' . Tools::htmlentities_utf8($id) . '").datepicker({
			prevText:"",
			nextText:"",
			dateFormat:"yy-mm-dd"' . ($time ? '+time' : '') . '});
	});';
}
/**
 * @param int|array $id ID can be a identifier or an array of identifiers
 * @param bool $time
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 * @deprecated 1.5.3.0 Use Controller::addJqueryUi('ui.datepicker') instead
 */
function include_datepicker($id, $time = false)
{
    Tools::display_as_deprecated(sprintf("Function '%s' will be removed in thirty bees 1.6.0", __FUNCTION__));
    echo '<script type="text/javascript" src="' . __PS_BASE_URI__ . 'js/jquery/ui/jquery.ui.core.min.js"></script>';
    echo '<link type="text/css" rel="stylesheet" href="' . __PS_BASE_URI__ . 'js/jquery/ui/themes/ui-lightness/jquery.ui.theme.css" />';
    echo '<link type="text/css" rel="stylesheet" href="' . __PS_BASE_URI__ . 'js/jquery/ui/themes/ui-lightness/jquery.ui.datepicker.css" />';
    $iso = Db::read_only()->get_value('SELECT iso_code FROM ' . _DB_PREFIX_ . 'lang WHERE `id_lang` = ' . (int) Context::get_context()->language->id);
    if ($iso != 'en') {
        echo '<script type="text/javascript" src="' . __PS_BASE_URI__ . 'js/jquery/ui/i18n/jquery.ui.datepicker-' . Tools::htmlentities_utf8($iso) . '.js"></script>';
    }
    echo '<script type="text/javascript">';
    if (is_array($id)) {
        foreach ($id as $id2) {
            bind_datepicker($id2, $time);
        }
    } else {
        bind_datepicker($id, $time);
    }
    echo '</script>';
}
/**
 * Cast a number to a price.
 *
 * @param float|int|string $price The price to cast.
 *
 * @return float Casted price, rounded to _TB_PRICE_DATABASE_PRECISION_.
 */
function priceval($price)
{
    Tools::display_as_deprecated(sprintf("Function '%s' will be removed in thirty bees 1.6.0", __FUNCTION__));
    return Tools::parse_number($price);
}
/**
 * Generate a new settings file, only transmitted parameters are updated
 *
 * @param string $base_urls Base URI
 * @param string $theme Theme name (eg. default)
 * @param array $array_db Parameters in order to connect to database
 * @deprecated 1.4.0
 * @return bool
 */
function rewrite_settings_file($base_urls = null, $theme = null, $array_db = null)
{
    Tools::display_as_deprecated(sprintf("Function '%s' will be removed in thirty bees 1.6.0", __FUNCTION__));
    return false;
}
/**
 * Display SQL date in friendly format
 *
 * @param string $sql_date Date in SQL format (YYYY-MM-DD HH:mm:ss)
 * @param bool $with_time Display both date and time
 * @deprecated 1.4.0 Use Tools::displayDate instead
 */
function display_date($sql_date, $with_time = false)
{
    Tools::display_as_deprecated(sprintf("Function '%s' will be removed in thirty bees 1.6.0", __FUNCTION__));
    return date('Y-m-d' . ($with_time ? ' H:i:s' : ''), strtotime($sql_date));
}
/**
 * Return path to a product category
 *
 * @param string $url_base Start URL
 * @param int $id_category Start category
 * @param string $path Current path
 * @param string $highlight String to highlight (in XHTML/CSS)
 * @param string $category_type
 * @param bool $home
 * @return false|string|void
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function get_path($url_base, $id_category, $path = '', $highlight = '', $category_type = 'catalog', $home = false)
{
    Tools::display_as_deprecated(sprintf("Function '%s' will be removed in thirty bees 1.6.0", __FUNCTION__));
    $context = Context::get_context();
    if ($category_type == 'catalog') {
        $conn = Db::read_only();
        $category = $conn->get_row('
		SELECT id_category, level_depth, nleft, nright
		FROM ' . _DB_PREFIX_ . 'category
		WHERE id_category = ' . (int) $id_category);
        if (isset($category['id_category'])) {
            $sql = 'SELECT c.id_category, cl.name, cl.link_rewrite
					FROM ' . _DB_PREFIX_ . 'category c
					LEFT JOIN ' . _DB_PREFIX_ . 'category_lang cl ON (cl.id_category = c.id_category' . Shop::add_sql_restriction_on_lang('cl') . ')
					WHERE c.nleft <= ' . (int) $category['nleft'] . '
						AND c.nright >= ' . (int) $category['nright'] . '
						AND cl.id_lang = ' . (int) $context->language->id . ($home ? ' AND c.id_category=' . (int) $id_category : '') . '
						AND c.id_category != ' . (int) Category::get_top_category()->id . '
					GROUP BY c.id_category
					ORDER BY c.level_depth ASC
					LIMIT ' . (!$home ? (int) $category['level_depth'] + 1 : 1);
            $categories = $conn->get_array($sql);
            $full_path = '';
            $n = 1;
            $n_categories = (int) count($categories);
            foreach ($categories as $category) {
                $link = Context::get_context()->link->get_admin_link('AdminCategories');
                $edit = '<a href="' . Tools::safe_output($link . '&id_category=' . (int) $category['id_category'] . '&' . ($category['id_category'] == 1 || $home ? 'viewcategory' : 'updatecategory')) . '" title="' . ($category['id_category'] == Category::get_root_category()->id_category ? 'Home' : 'Modify') . '"><i class="icon-' . ($category['id_category'] == Category::get_root_category()->id_category || $home ? 'home' : 'pencil') . '"></i></a> ';
                $full_path .= $edit . ($n < $n_categories ? '<a href="' . Tools::safe_output($url_base . '&id_category=' . (int) $category['id_category'] . '&viewcategory&token=' . Tools::get_admin_token('AdminCategories' . (int) Tab::get_id_from_class_name('AdminCategories') . (int) $context->employee->id)) . '" title="' . htmlentities($category['name'], ENT_NOQUOTES, 'UTF-8') . '">' : '') . (!empty($highlight) ? str_ireplace($highlight, '<span class="highlight">' . htmlentities($highlight, ENT_NOQUOTES, 'UTF-8') . '</span>', $category['name']) : $category['name']) . ($n < $n_categories ? '</a>' : '') . ($n++ != $n_categories || !empty($path) ? ' > ' : '');
            }
            return $full_path . $path;
        }
    } elseif ($category_type == 'cms') {
        $category = new Cms_Category($id_category, $context->language->id);
        if (!$category->id) {
            return $path;
        }
        $name = $highlight != null ? str_ireplace($highlight, '<span class="highlight">' . $highlight . '</span>', Cms_Category::hide_cms_category_position($category->name)) : Cms_Category::hide_cms_category_position($category->name);
        $edit = '<a href="' . Tools::safe_output($url_base . '&id_cms_category=' . $category->id . '&addcategory&token=' . Tools::get_admin_token('AdminCmsContent' . (int) Tab::get_id_from_class_name('AdminCmsContent') . (int) $context->employee->id)) . '">
				<i class="icon-pencil"></i></a> ';
        if ($category->id == 1) {
            $edit = '<li><a href="' . Tools::safe_output($url_base . '&id_cms_category=' . $category->id . '&viewcategory&token=' . Tools::get_admin_token('AdminCmsContent' . (int) Tab::get_id_from_class_name('AdminCmsContent') . (int) $context->employee->id)) . '">
					<i class="icon-home"></i></a></li> ';
        }
        $path = $edit . '<li><a href="' . Tools::safe_output($url_base . '&id_cms_category=' . $category->id . '&viewcategory&token=' . Tools::get_admin_token('AdminCmsContent' . (int) Tab::get_id_from_class_name('AdminCmsContent') . (int) $context->employee->id)) . '">
		' . $name . '</a></li> > ' . $path;
        if ($category->id == 1) {
            return substr($path, 0, strlen($path) - 3);
        }
        return get_path($url_base, $category->id_parent, $path, '', 'cms');
    }
}
/**
 * @param string $path
 * @return array
 */
function get_dir_content($path)
{
    Tools::display_as_deprecated(sprintf("Function '%s' will be removed in thirty bees 1.6.0", __FUNCTION__));
    $content = [];
    if (is_dir($path)) {
        $d = dir($path);
        while (false !== $entry = $d->read()) {
            if ($entry[0] != '.') {
                $content[] = $entry;
            }
        }
        $d->close();
    }
    return $content;
}
/**
 * @param string $path
 * @param int $rights
 * @return bool
 */
function create_dir($path, $rights)
{
    Tools::display_as_deprecated(sprintf("Function '%s' will be removed in thirty bees 1.6.0", __FUNCTION__));
    if (file_exists($path)) {
        return true;
    }
    return @mkdir($path, $rights);
}
/**
 * @deprecated 1.5.4.1 Use Translate::getAdminTranslation($string) instead
 * @param string $string
 * @return string
 */
function translate($string)
{
    Tools::display_as_deprecated(sprintf("Function '%s' will be removed in thirty bees 1.6.0", __FUNCTION__));
    global $_LANGADM;
    if (!is_array($_LANGADM)) {
        return str_replace('"', '&quot;', $string);
    }
    $key = 'index' . md5(str_replace('\'', '\\\'', $string));
    $str = array_key_exists($key, $_LANGADM) && $_LANGADM[$key] !== '' ? $_LANGADM[$key] : $string;
    return str_replace('"', '&quot;', stripslashes($str));
}
/**
 * Returns a new Tab object
 *
 * @param string $tab class name
 * @return AdminTab|bool tab object or false if failed
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function checking_tab($tab)
{
    Tools::display_as_deprecated(sprintf("Function '%s' will be removed in thirty bees 1.6.0", __FUNCTION__));
    if (!Validate::is_tab_name($tab)) {
        return false;
    }
    $row = Db::read_only()->get_row('SELECT id_tab, module, class_name FROM `' . _DB_PREFIX_ . 'tab` WHERE LOWER(class_name) = \'' . p_sql($tab) . '\'');
    if (!$row['id_tab']) {
        if (isset(Admin_Tab::$tab_parenting[$tab])) {
            Tools::redirect_admin('?tab=' . Admin_Tab::$tab_parenting[$tab] . '&token=' . Tools::get_admin_token_lite(Admin_Tab::$tab_parenting[$tab]));
        }
        echo sprintf(Tools::display_error('Page %s cannot be found.'), $tab);
        return false;
    }
    // Class file is included in Dispatcher::dispatch() function
    if (!class_exists($tab, false)) {
        echo sprintf(Tools::display_error('The class %s cannot be found.'), $tab);
        return false;
    }
    $admin_obj = new $tab();
    if (!$admin_obj->view_access() && ($admin_obj->table != 'employee' || Context::get_context()->employee->id != Tools::get_int_value('id_employee') || !Tools::is_submit('updateemployee'))) {
        $admin_obj->_errors = [Tools::display_error('Access denied.')];
        echo $admin_obj->display_errors();
        return false;
    }
    return $admin_obj;
}
/**
 * @param int $id_tab
 * @return bool
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 */
function check_tab_rights($id_tab)
{
    Tools::display_as_deprecated(sprintf("Function '%s' will be removed in thirty bees 1.6.0", __FUNCTION__));
    return Context::get_context()->employee->has_access($id_tab, Profile::PERMISSION_VIEW);
}
/**
 * Converts a simpleXML element into an array. Preserves attributes and everything.
 * You can choose to get your elements either flattened, or stored in a custom index that
 * you define.
 * For example, for a given element
 * <field name="someName" type="someType"/>
 * if you choose to flatten attributes, you would get:
 * $array['field']['name'] = 'someName';
 * $array['field']['type'] = 'someType';
 * If you choose not to flatten, you get:
 * $array['field']['@attributes']['name'] = 'someName';
 * _____________________________________
 * Repeating fields are stored in indexed arrays. so for a markup such as:
 * <parent>
 * <child>a</child>
 * <child>b</child>
 * <child>c</child>
 * </parent>
 * you array would be:
 * $array['parent']['child'][0] = 'a';
 * $array['parent']['child'][1] = 'b';
 * ...And so on.
 * _____________________________________
 * @param simpleXMLElement $xml the XML to convert
 * @param bool $flatten_values Choose wether to flatten values
 *                                    or to set them under a particular index.
 *                                    defaults to true;
 * @param bool $flatten_attributes Choose wether to flatten attributes
 *                                    or to set them under a particular index.
 *                                    Defaults to true;
 * @param bool $flatten_children Choose wether to flatten children
 *                                    or to set them under a particular index.
 *                                    Defaults to true;
 * @param string $value_key index for values, in case $flatten_values was set to false. Defaults to "@value"
 * @param string $attributes_key index for attributes, in case $flatten_attributes was set to false. Defaults to "@attributes"
 * @param string $children_key index for children, in case $flatten_children was set to false. Defaults to "@children"
 * @return array the resulting array.
 */
function simple_xml_to_array($xml, $flatten_values = true, $flatten_attributes = true, $flatten_children = true, $value_key = '@value', $attributes_key = '@attributes', $children_key = '@children')
{
    Tools::display_as_deprecated(sprintf("Function '%s' will be removed in thirty bees 1.6.0", __FUNCTION__));
    $return = [];
    if (!$xml instanceof Simple_Xml_Element) {
        return $return;
    }
    $name = $xml->get_name();
    $value = trim((string) $xml);
    if (strlen($value) == 0) {
        $value = null;
    }
    if ($value !== null) {
        if (!$flatten_values) {
            $return[$value_key] = $value;
        } else {
            $return = $value;
        }
    }
    $children = [];
    $first = true;
    foreach ($xml->children() as $element_name => $child) {
        $value = simple_xml_to_array($child, $flatten_values, $flatten_attributes, $flatten_children, $value_key, $attributes_key, $children_key);
        if (isset($children[$element_name])) {
            if ($first) {
                $temp = $children[$element_name];
                unset($children[$element_name]);
                $children[$element_name][] = $temp;
                $first = false;
            }
            $children[$element_name][] = $value;
        } else {
            $children[$element_name] = $value;
        }
    }
    if (count($children) > 0) {
        if (!$flatten_children) {
            $return[$children_key] = $children;
        } else {
            $return = array_merge($return, $children);
        }
    }
    $attributes = [];
    foreach ($xml->attributes() as $name => $value) {
        $attributes[$name] = trim($value);
    }
    if (count($attributes) > 0) {
        if (!$flatten_attributes) {
            $return[$attributes_key] = $attributes;
        } else {
            $return = array_merge($return, $attributes);
        }
    }
    return $return;
}
/**
 * for retrocompatibility with old AdminTab, old index.php
 *
 * @param string $tab
 * @param bool $ajax_mode
 * @return void
 * @throws PrestaShopDatabaseException
 * @throws PrestaShopException
 * @throws SmartyException
 */
function run_admin_tab($tab, $ajax_mode = false)
{
    Tools::display_as_deprecated(sprintf("Function '%s' will be removed in thirty bees 1.6.0", __FUNCTION__));
    $ajax_mode = (bool) $ajax_mode;
    require_once _PS_ADMIN_DIR_ . '/init.php';
    $cookie = Context::get_context()->cookie;
    if (empty($tab) && !count($_POST)) {
        $tab = 'AdminDashboard';
        $_POST['tab'] = $tab;
        $_POST['token'] = Tools::get_admin_token_lite($tab);
    }
    // $tab = $_REQUEST['tab'];
    if ($admin_obj = checking_tab($tab)) {
        Context::get_context()->controller = $admin_obj;
        // init is different for new tabs (AdminController) and old tabs (AdminTab)
        if ($admin_obj instanceof Admin_Controller) {
            if ($ajax_mode) {
                $admin_obj->ajax = true;
            }
            $admin_obj->path = dirname($_SERVER['PHP_SELF']);
            $admin_obj->run();
        } else {
            if (!$ajax_mode) {
                require_once _PS_ADMIN_DIR_ . '/header.inc.php';
            }
            $iso_user = Context::get_context()->language->id;
            $tabs = Tab::recursive_tab($admin_obj->id);
            $tabs = array_reverse($tabs);
            $bread = '';
            foreach ($tabs as $key => $item) {
                $bread .= ' <img src="../img/admin/separator_breadcrumb.png" style="margin-right:5px" alt="&gt;" />';
                if (count($tabs) - 1 > $key) {
                    $bread .= '<a href="?tab=' . $item['class_name'] . '&token=' . Tools::get_admin_token($item['class_name'] . (int) $item['id_tab'] . (int) Context::get_context()->employee->id) . '">';
                }
                $bread .= $item['name'];
                if (count($tabs) - 1 > $key) {
                    $bread .= '</a>';
                }
            }
            if (!$ajax_mode && Shop::is_feature_active() && Shop::get_context() != Shop::CONTEXT_ALL && Context::get_context()->controller->multishop_context != Shop::CONTEXT_ALL) {
                echo '<div class="multishop_info">';
                if (Shop::get_context() == Shop::CONTEXT_GROUP) {
                    $shop_group = new Shop_Group((int) Shop::get_context_shop_group_id());
                    printf(Translate::get_admin_translation('You are configuring your store for group shop %s'), '<b>' . $shop_group->name . '</b>');
                } elseif (Shop::get_context() == Shop::CONTEXT_SHOP) {
                    printf(Translate::get_admin_translation('You are configuring your store for shop %s'), '<b>' . Context::get_context()->shop->name . '</b>');
                }
                echo '</div>';
            }
            if (Validate::is_loaded_object($admin_obj)) {
                if ($admin_obj->check_token()) {
                    if ($ajax_mode) {
                        // the differences with index.php is here
                        $admin_obj->ajax_pre_process();
                        $action = Tools::get_value('action');
                        // no need to use displayConf() here
                        if (!empty($action) && method_exists($admin_obj, 'ajaxProcess' . Tools::to_camel_case($action))) {
                            $admin_obj->{'ajaxProcess' . Tools::to_camel_case($action)}();
                        } else {
                            $admin_obj->ajax_process();
                        }
                        // @TODO We should use a displayAjaxError
                        $admin_obj->display_errors();
                        if (!empty($action) && method_exists($admin_obj, 'displayAjax' . Tools::to_camel_case($action))) {
                            $admin_obj->{'displayAjax' . $action}();
                        } else {
                            $admin_obj->display_ajax();
                        }
                    } else {
                        /* Filter memorization */
                        if (!empty($_POST) && isset($admin_obj->table)) {
                            foreach ($_POST as $key => $value) {
                                if (is_array($admin_obj->table)) {
                                    foreach ($admin_obj->table as $table) {
                                        if (strncmp($key, $table . 'Filter_', 7) === 0 || strncmp($key, 'submitFilter', 12) === 0) {
                                            $cookie->{$key} = !is_array($value) ? $value : json_encode($value);
                                        }
                                    }
                                } elseif (strncmp($key, $admin_obj->table . 'Filter_', 7) === 0 || strncmp($key, 'submitFilter', 12) === 0) {
                                    $cookie->{$key} = !is_array($value) ? $value : json_encode($value);
                                }
                            }
                        }
                        if (!empty($_GET) && isset($admin_obj->table)) {
                            foreach ($_GET as $key => $value) {
                                if (is_array($admin_obj->table)) {
                                    foreach ($admin_obj->table as $table) {
                                        if (strncmp($key, $table . 'OrderBy', 7) === 0 || strncmp($key, $table . 'Orderway', 8) === 0) {
                                            $cookie->{$key} = $value;
                                        }
                                    }
                                } elseif (strncmp($key, $admin_obj->table . 'OrderBy', 7) === 0 || strncmp($key, $admin_obj->table . 'Orderway', 12) === 0) {
                                    $cookie->{$key} = $value;
                                }
                            }
                        }
                        $admin_obj->display_conf();
                        $admin_obj->post_process();
                        $admin_obj->display_errors();
                        $admin_obj->display();
                        include _PS_ADMIN_DIR_ . '/footer.inc.php';
                    }
                } else if ($ajax_mode) {
                    // If this is an XSS attempt, then we should only display a simple, secure page
                    if (ob_get_level() && ob_get_length() > 0) {
                        ob_clean();
                    }
                    die(json_encode(Translate::get_admin_translation('Invalid security token')));
                } else {
                    // If this is an XSS attempt, then we should only display a simple, secure page
                    if (ob_get_level() && ob_get_length() > 0) {
                        ob_clean();
                    }
                    // ${1} in the replacement string of the regexp is required, because the token may begin with a number and mix up with it (e.g. $17)
                    $url = preg_replace('/([&?]token=)[^&]*(&.*)?$/', '${1}' . $admin_obj->token . '$2', $_SERVER['REQUEST_URI']);
                    if (false === strpos($url, '?token=') && false === strpos($url, '&token=')) {
                        $url .= '&token=' . $admin_obj->token;
                    }
                    $message = Translate::get_admin_translation('Invalid security token');
                    echo '<html><head><title>' . $message . '</title></head><body style="font-family:Arial,Verdana,Helvetica,sans-serif;background-color:#EC8686">
							<div style="background-color:#FAE2E3;border:1px solid #000000;color:#383838;font-weight:700;line-height:20px;margin:0 0 10px;padding:10px 15px;width:500px">
								<img src="../img/admin/error2.png" style="margin:-4px 5px 0 0;vertical-align:middle">
								' . $message . '
							</div>';
                    echo '<a href="' . htmlentities($url) . '" method="get" style="float:left;margin:10px">
								<input type="button" value="' . Tools::htmlentities_utf8(Translate::get_admin_translation('I understand the risks and I really want to display this page')) . '" style="height:30px;margin-top:5px" />
							</a>
							<a href="index.php" method="get" style="float:left;margin:10px">
								<input type="button" value="' . Tools::htmlentities_utf8(Translate::get_admin_translation('Take me out of here!')) . '" style="height:40px" />
							</a>
						</body></html>';
                    die;
                }
            }
        }
    }
}