<?php

declare (strict_types=1);
/**
 * Copyright (C) 2017-2025 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <contact@thirtybees.com>
 * @copyright 2017-2025 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
/**
 * Class ShopMaintenance
 *
 * This class implements tasks for maintaining the shop installation, to be
 * run on a regular schedule. It gets called by an asynchronous Ajax request
 * in DashboardController.
 */
class Shop_Maintenance_Core
{
    /**
     * Database lock name.
     */
    public const BACKUP_LOCK_NAME = 'TB_AUTO_BACKUP_LOCK';
    /**
     * Run tasks as needed. Should take care of running tasks not more often
     * than needed and that one run takes not longer than a few seconds.
     *
     * This method gets triggered by the 'getNotifications' Ajax request, so
     * every two minutes while somebody has back office open.
     *
     * @throws PrestaShopException
     */
    public static function run(): void
    {
        $now = time();
        $last_run = Configuration::get_global_value('SHOP_MAINTENANCE_LAST_RUN');
        if ($now - $last_run > 86400) {
            // Run daily tasks.
            static::adjust_theme_headers();
            static::optin_shop();
            static::clean_admin_controller_messages();
            static::clean_old_log_files();
            static::clean_old_theme_cache_files();
            static::auto_db_backup();
            static::delete_old_db_backup_files();
            Configuration::update_global_value('SHOP_MAINTENANCE_LAST_RUN', $now);
        }
    }
    /**
     * Correct the "generator" meta tag in templates. Technology detection
     * sites like builtwith.com don't recognize thirty bees technology if the
     * theme template inserts a meta tag "generator" for PrestaShop.
     */
    public static function adjust_theme_headers(): void
    {
        foreach (scandir(_PS_ALL_THEMES_DIR_) as $theme_dir) {
            if (!is_dir(_PS_ALL_THEMES_DIR_ . $theme_dir)) {
                continue;
            }
            if (in_array($theme_dir, ['.', '..'])) {
                continue;
            }
            $header_path = _PS_ALL_THEMES_DIR_ . $theme_dir . '/header.tpl';
            if (is_writable($header_path)) {
                $header = file_get_contents($header_path);
                $new_header = preg_replace('/<\s*meta\s*name\s*=\s*["\']generator["\']\s*content\s*=\s*["\'].*["\']\s*>/i', '<meta name="generator" content="thirty bees">', $header);
                if ($new_header !== $header) {
                    file_put_contents($header_path, $new_header);
                    Tools::clear_smarty_cache();
                }
            }
        }
    }
    /**
     * Handle shop optin.
     *
     * @throws PrestaShopException
     */
    public static function optin_shop(): void
    {
        $name = Configuration::STORE_REGISTERED;
        if (!Configuration::get($name)) {
            $employees = Employee::get_employees_by_profile(_PS_ADMIN_PROFILE_);
            // Usually there's only one employee when we run this code.
            foreach ($employees as $employee) {
                $employee = new Employee($employee['id_employee']);
                $employee->optin = true;
                if ($employee->update()) {
                    Configuration::update_value($name, 1);
                }
            }
        }
    }
    /**
     * Delete lost AdminController messages.
     */
    public static function clean_admin_controller_messages(): void
    {
        $name = Admin_Controller::MESSAGE_CACHE_PATH;
        $name_length = strlen($name);
        foreach (scandir(_PS_CACHE_DIR_) as $candidate) {
            if (substr($candidate, 0, $name_length) === $name) {
                $path = _PS_CACHE_DIR_ . '/' . $candidate;
                if (time() - filemtime($path) > 3600) {
                    unlink($path);
                }
            }
        }
    }
    /**
     * Delete all .log files in the /log/ directory older than 6 months.
     *
     * @throws PrestaShopException
     */
    public static function clean_old_log_files(): void
    {
        $now = time();
        $days = Configuration::get_logs_retention_period();
        $oldlogdeleteperiod = $days * 86400;
        $log_dir = _PS_ROOT_DIR_ . '/log/';
        $iterator = new Recursive_Iterator_Iterator(new Recursive_Directory_Iterator($log_dir));
        foreach ($iterator as $item) {
            $file_path = $item->get_pathname();
            if (is_file($file_path) && pathinfo((string) $file_path, PATHINFO_EXTENSION) === 'log' && is_writable($file_path)) {
                if ($now - filemtime($file_path) > $oldlogdeleteperiod) {
                    unlink($file_path);
                }
            }
        }
    }
    /**
     * Delete all .js and .css files in /themes/../cache/ directories older than 30 days.
     *
     * @throws PrestaShopException
     */
    public static function clean_old_theme_cache_files(): void
    {
        $days = Configuration::get_ccc_assets_retention_period();
        $themes_dir = _PS_ROOT_DIR_ . '/themes/';
        $now = time();
        $themecachedeleteperiod = $days * 86400;
        foreach (scandir($themes_dir) as $theme_name) {
            $theme_dir = $themes_dir . $theme_name;
            $cache_dir = $theme_dir . '/cache/';
            if (!in_array($theme_name, ['.', '..']) && is_dir($theme_dir) && is_dir($cache_dir)) {
                foreach (scandir($cache_dir) as $file) {
                    $file_path = $cache_dir . $file;
                    $extension = pathinfo($file_path, PATHINFO_EXTENSION);
                    if (is_file($file_path) && ($extension === 'js' || $extension === 'css')) {
                        if ($now - filemtime($file_path) > $themecachedeleteperiod) {
                            unlink($file_path);
                        }
                    }
                }
            }
        }
    }
    /**
     * Automatically create a database backup if the automatic backup feature is enabled.
     *
     * @throws PrestaShopException
     */
    public static function auto_db_backup(): void
    {
        if (!Configuration::get('TB_DB_AUTO_BACKUP')) {
            return;
        }
        if (!self::lock()) {
            throw new Presta_Shop_Exception('Automatic backup skipped - lock not acquired');
        }
        try {
            $backup = new Presta_Shop_Backup();
            if (!$backup->add()) {
                throw new Presta_Shop_Exception('Automatic backup failed: backup->add() returned false');
            }
        } finally {
            self::release_lock();
        }
    }
    /**
     * Attempts to acquire the MySQL lock defined by static::LOCK_NAME.
     *
     * @return bool True if the lock was acquired, false otherwise.
     */
    protected static function lock(): bool
    {
        try {
            $connection = Db::get_instance();
            return (bool) (int) $connection->get_value("SELECT GET_LOCK('" . static::BACKUP_LOCK_NAME . "', 3)");
        } catch (Throwable) {
            return false;
        }
    }
    /**
     * Releases the MySQL lock identified by static::LOCK_NAME.
     *
     * @return void
     */
    protected static function release_lock()
    {
        try {
            $connection = Db::get_instance();
            $connection->execute("SELECT RELEASE_LOCK('" . static::BACKUP_LOCK_NAME . "')");
        } catch (Throwable) {
        }
    }
    /**
     * Delete backup files older than the configured retention period.
     *
     * @throws PrestaShopException
     */
    public static function delete_old_db_backup_files(): void
    {
        $retention_days = (int) Configuration::get('TB_DB_BACKUP_RETENTION_PERIOD');
        if ($retention_days <= 0) {
            return;
        }
        $backup_dir = realpath(_PS_ADMIN_DIR_ . Presta_Shop_Backup::$backup_dir);
        if ($backup_dir === false) {
            Presta_Shop_Logger::add_log('Backup directory not found.', 3, null, 'ShopMaintenance', null, true);
            return;
        }
        $now = time();
        $files = glob($backup_dir . DIRECTORY_SEPARATOR . '*');
        // Only process files that match the expected backup filename pattern:
        // e.g. 1618821234-abc123.sql, 1618821234-abc123.sql.gz or 1618821234-abc123.sql.bz2
        $pattern = '/^\d+\-[a-f0-9]+\.sql(\.gz|\.bz2)?$/i';
        foreach ($files as $file) {
            if (is_file($file) && preg_match($pattern, basename($file))) {
                $age_days = ($now - filemtime($file)) / 86400;
                if ($age_days > $retention_days) {
                    if (unlink($file)) {
                        Presta_Shop_Logger::add_log('Deleted old backup file: ' . basename($file), 1, null, 'ShopMaintenance', null, true);
                    } else {
                        Presta_Shop_Logger::add_log('Error deleting backup file: ' . basename($file), 3, null, 'ShopMaintenance', null, true);
                    }
                }
            }
        }
    }
}