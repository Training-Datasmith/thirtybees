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
 * Class ProductDownloadCore
 */
class Product_Download_Core extends Object_Model
{
    /** @deprecated 1.0.2 This cache is no longer used. */
    protected static $_product_ids = [];
    /** @var int Product id which download belongs */
    public $id_product = 0;
    /** @var string DisplayFilename the name which appear */
    public $display_filename = '';
    /** @var string PhysicallyFilename the name of the file on hard disk */
    public $filename = '';
    /** @var string DateDeposit when the file is upload */
    public $date_add = '0000-00-00 00:00:00';
    /** @var string DateExpiration deadline of the file */
    public $date_expiration = '0000-00-00 00:00:00';
    /** @var int NbDaysAccessible how many days the customer can access to file */
    public $nb_days_accessible = 0;
    /** @var int NbDownloadable how many time the customer can download the file */
    public $nb_downloadable = 0;
    /** @var bool Active if file is accessible or not */
    public $active = 1;
    /** @var bool is_shareable indicates whether the product can be shared */
    public $is_shareable = 0;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'product_download', 'primary' => 'id_product_download', 'fields' => ['id_product' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true, 'unique' => true], 'display_filename' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 255], 'filename' => ['type' => self::TYPE_STRING, 'validate' => 'isSha1', 'size' => 255], 'date_add' => ['type' => self::TYPE_DATE, 'validate' => 'isDate', 'dbNullable' => false], 'date_expiration' => ['type' => self::TYPE_DATE, 'validate' => 'isDate'], 'nb_days_accessible' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'], 'nb_downloadable' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt', 'dbDefault' => '1', 'dbNullable' => true], 'active' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '1'], 'is_shareable' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbDefault' => '0']], 'keys' => ['product_download' => ['product_active' => ['type' => Object_Model::KEY, 'columns' => ['id_product', 'active']]]]];
    /**
     * Build a virtual product
     *
     * @param int|null $idProductDownload Existing productDownload id in order to load object (optional)
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct($id_product_download = null)
    {
        parent::__construct($id_product_download);
        // @TODO check if the file is present on hard drive
    }
    /**
     * Check if download repository is writable
     *
     * @return bool
     */
    public static function check_writable_dir()
    {
        return is_writable(_PS_DOWNLOAD_DIR_);
    }
    /**
     * Find a product's download. As class Product doesn't maintain it's
     * download, that's the way to find out wether there's a download and
     * which one it is.
     *
     * @param int $idProduct Product ID.
     * @param bool $active Wether only an active download or any download.
     *
     * @return int ID of the product download or 0 if there's none.
     *
     * @throws PrestaShopException
     */
    public static function get_id_from_id_product($id_product, $active = true)
    {
        $id = (int) Db::read_only()->get_value((new Db_Query())->select('`id_product_download`')->from('product_download')->where('`id_product` = ' . (int) $id_product)->where($active ? '`active` = 1' : '')->order_by('`id_product_download` DESC'));
        // @deprecated 1.0.2
        static::$_product_ids[$id_product] = $id;
        return $id;
    }
    /**
     * This method is allow to know if a feature is used or active
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function is_feature_active()
    {
        return Configuration::get('PS_VIRTUAL_PROD_FEATURE_ACTIVE');
    }
    /**
     * Return the display filename from a physical filename
     *
     * @param string $filename Filename physically
     *
     * @return int Product the id for this virtual product
     *
     * @throws PrestaShopException
     */
    public static function get_id_from_filename($filename)
    {
        return Db::read_only()->get_value((new Db_Query())->select('`id_product_download`')->from('product_download')->where('`filename` = \'' . p_sql($filename) . '\''));
    }
    /**
     * Return the filename from an id_product
     *
     * @param int $idProduct Product the id
     *
     * @return string Filename the filename for this virtual product
     *
     * @throws PrestaShopException
     */
    public static function get_filename_from_id_product($id_product)
    {
        return Db::read_only()->get_value((new Db_Query())->select('`filename`')->from('product_download')->where('`id_product` = ' . (int) $id_product)->where('`active` = 1'));
    }
    /**
     * Return the display filename from a physical filename
     *
     * @param string $filename Filename physically
     *
     * @return string Filename the display filename for this virtual product
     *
     * @throws PrestaShopException
     */
    public static function get_filename_from_filename($filename)
    {
        return Db::read_only()->get_value((new Db_Query())->select('`display_filename`')->from('product_download')->where('`filename` = \'' . p_sql($filename) . '\''));
    }
    /**
     * Return a sha1 filename
     *
     * @return string Sha1 unique filename
     */
    public static function get_new_filename()
    {
        do {
            $filename = sha1(microtime());
        } while (file_exists(_PS_DOWNLOAD_DIR_ . $filename));
        return $filename;
    }
    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        if (parent::update($null_values)) {
            // Refresh cache of feature detachable because the row can be deactive
            Configuration::update_global_value('PS_VIRTUAL_PROD_FEATURE_ACTIVE', Product_Download::is_currently_used($this->def['table'], true));
            return true;
        }
        return false;
    }
    /**
     * @param bool $deleteFile Deprecated. File gets always deleted.
     *
     * @return bool True on successful deletion of file and DB entry.
     *
     *                file without matching DB entry means just a leaked file.
     * @throws PrestaShopException
     */
    public function delete($delete_file = 999)
    {
        if ($delete_file !== 999) {
            Tools::display_parameter_as_deprecated('deleteFile');
        }
        return $this->delete_file() && parent::delete();
    }
    /**
     * Delete the file
     *
     * @param int $idProductDownload : if we need to delete a specific product attribute file
     *
     * @return bool True if file didn't exist or was deleted successfully.
     *              False if the existing file couldn't get deleted.
     *
     *                wanting to also delete the DB entry should use delete().
     * @throws PrestaShopException
     */
    public function delete_file($id_product_download = 999)
    {
        if ($id_product_download !== 999) {
            Tools::display_parameter_as_deprecated('idProductDownload');
            // Retrocompatibility.
            if ($id_product_download) {
                $download = new Product_Download($id_product_download);
                return $download->delete();
            }
        }
        $result = !$this->check_file();
        if (!$result) {
            $result = @unlink(_PS_DOWNLOAD_DIR_ . $this->filename);
            if ($result) {
                $this->filename = '';
                $this->display_filename = '';
                $this->update();
            }
        }
        return $result;
    }
    /**
     * Check if file exists
     *
     * @return bool
     */
    public function check_file()
    {
        if (!$this->filename) {
            return false;
        }
        return file_exists(_PS_DOWNLOAD_DIR_ . $this->filename);
    }
    /**
     * Return html link
     *
     * @param bool|string $class CSS selector
     * @param bool $admin specific to backend
     * @param bool $hash hash code in table order detail
     *
     * @return string Html all the code for print a link to the file
     * @throws PrestaShopException
     * @deprecated 1.6.0
     */
    public function get_html_link($class = false, $admin = true, $hash = false)
    {
        Tools::display_as_deprecated();
        $link = $this->get_text_link($admin, $hash);
        $html = '<a href="' . $link . '" title=""';
        if ($class) {
            $html .= ' class="' . $class . '"';
        }
        return $html . ('>' . $this->display_filename . '</a>');
    }
    /**
     * Return html link
     *
     * @param bool $admin specific to backend (optional)
     * @param bool|string $hash hash code in table order detail (optional)
     * @param array $params
     *
     * @return string Html all the code for print a link to the file
     * @throws PrestaShopException
     */
    public function get_text_link($admin = true, $hash = false, $params = [])
    {
        if ($admin) {
            return 'get-file-admin.php?file=' . $this->filename;
        }
        $params['key'] = $this->filename . '-' . ($hash ?: 'orderdetail');
        return Context::get_context()->link->get_page_link('get-file', null, null, $params);
    }
    /**
     * Return a deadline
     *
     * @return string Datetime in SQL format
     */
    public function get_deadline()
    {
        if (!(int) $this->nb_days_accessible) {
            return '0000-00-00 00:00:00';
        }
        $timestamp = strtotime('+' . (int) $this->nb_days_accessible . ' day');
        return date('Y-m-d H:i:s', $timestamp);
    }
    /**
     * Return a hash for control download access
     *
     * @return string Hash ready to insert in database
     */
    public function get_hash()
    {
        // TODO check if this hash not already in database
        return sha1(microtime() . $this->id);
    }
}