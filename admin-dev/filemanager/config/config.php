<?php

declare (strict_types=1);
/** @noinspection PhpUnhandledExceptionInspection */
if (!defined('_PS_ADMIN_DIR_')) {
    define('_PS_ADMIN_DIR_', dirname(__FILE__) . '/../../');
}
require_once _PS_ADMIN_DIR_ . '/../config/config.inc.php';
require_once _PS_ADMIN_DIR_ . '/init.php';
require_once __DIR__ . '/../include/utils.php';
// load custom configuration from /config/filemanager.inc.php
if (file_exists(_PS_CONFIG_DIR_ . '/filemanager.inc.php')) {
    require_once _PS_CONFIG_DIR_ . '/filemanager.inc.php';
}
// check access
$employee = Context::get_context()->employee;
if (!$employee->has_access(Admin_Products_Controller::class, Profile::PERMISSION_EDIT)) {
    throw new Presta_Shop_Exception(Tools::display_error('Access denied'));
}
if (!$employee->has_access(Admin_Cms_Content_Controller::class, Profile::PERMISSION_EDIT)) {
    throw new Presta_Shop_Exception(Tools::display_error('Access denied'));
}
// set language
set_language();
if (!defined('FILE_MANAGER_FILE_NUMBER_LIMIT_JS')) {
    define('FILE_MANAGER_FILE_NUMBER_LIMIT_JS', 500);
}
if (!defined('FILE_MANAGER_BASE_URL')) {
    define('FILE_MANAGER_BASE_URL', Tools::get_http_host(true));
}
if (!defined('FILE_MANAGER_UPLOAD_DIR')) {
    define('FILE_MANAGER_UPLOAD_DIR', Context::get_context()->shop->get_base_uri() . 'img/cms/');
}
if (!defined('FILE_MANAGER_ICON_THEME')) {
    $icon_theme = Configuration::get('FILE_MANAGER_ICON_THEME');
    if ($icon_theme !== 'ico' && $icon_theme !== 'ico_dark') {
        $icon_theme = 'ico';
    }
    define('FILE_MANAGER_ICON_THEME', $icon_theme);
}
if (!defined('FILE_MANAGER_BASE_DIR')) {
    define('FILE_MANAGER_BASE_DIR', _PS_ROOT_DIR_ . '/img/cms/');
}
if (!defined('FILE_MANAGER_THUMB_BASE_DIR')) {
    define('FILE_MANAGER_THUMB_BASE_DIR', _PS_ROOT_DIR_ . '/img/tmp/cms/');
}
/**
 * Allowed mime types
 *
 * it's possible to extend list of default mime types using FILE_MANAGER_EXTRA_MIME_TYPES constant,
 * or override all mime types by defining custom FILE_MANAGER_ALLOWED_MIME_TYPES constant
 *
 * All file extensions should match .htaccess rule in /img/cms/.htaccess
 */
if (!defined('FILE_MANAGER_ALLOWED_MIME_TYPES')) {
    $types = ['image/jpeg' => ['extensions' => ['jpg', 'jpeg'], 'category' => 'image'], 'image/png' => ['extensions' => ['png'], 'category' => 'image'], 'image/gif' => ['extensions' => ['gif'], 'category' => 'image'], 'image/bmp' => ['extensions' => ['bmp'], 'category' => 'image'], 'image/tiff' => ['extensions' => ['tiff'], 'category' => 'image'], 'image/svg' => ['extensions' => ['svg'], 'category' => 'image'], 'image/webp' => ['extensions' => ['webp'], 'category' => 'image'], 'image/avif' => ['extensions' => ['avif'], 'category' => 'image'], 'application/pdf' => ['extensions' => ['pdf'], 'category' => 'file'], 'video/mpeg' => ['extensions' => ['mpeg', 'mpg', 'mov'], 'category' => 'video'], 'video/mp4' => ['extensions' => ['mp4'], 'category' => 'video'], 'video/x-msvideo' => ['extensions' => ['avi'], 'category' => 'video'], 'audio/x-ms-wma' => ['extensions' => ['wma'], 'category' => 'video'], 'video/x-flv' => ['extensions' => ['flv'], 'category' => 'video'], 'video/webm' => ['extensions' => ['webm'], 'category' => 'video']];
    if (defined('FILE_MANAGER_EXTRA_MIME_TYPES')) {
        $types = array_merge($types, FILE_MANAGER_EXTRA_MIME_TYPES);
    }
    define('FILE_MANAGER_ALLOWED_MIME_TYPES', $types);
}