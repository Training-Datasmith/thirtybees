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
 * Class HelperImageUploaderCore
 */
class Helper_Image_Uploader_Core extends Helper_Uploader
{
    /**
     * @return int
     */
    public function get_max_size()
    {
        return (int) Tools::get_max_upload_size();
    }
    /**
     * @return string
     */
    public function get_save_path()
    {
        return $this->_normalize_directory(_PS_TMP_IMG_DIR_);
    }
    /**
     * @param string|null $fileName
     *
     * @return string
     */
    public function get_file_path($file_name = null)
    {
        //Force file path
        return tempnam($this->get_save_path(), $this->get_unique_file_name());
    }
    /**
     * @param array $file
     *
     * @return bool
     */
    protected function validate(&$file)
    {
        $file['error'] = $this->check_upload_error($file['error']);
        if ($file['error']) {
            return false;
        }
        $post_max_size = Tools::convert_bytes(ini_get('post_max_size'));
        $upload_max_filesize = Tools::convert_bytes(ini_get('upload_max_filesize'));
        if ($post_max_size && $this->_get_server_vars('CONTENT_LENGTH') > $post_max_size) {
            $file['error'] = Tools::display_error('The uploaded file exceeds the post_max_size directive in php.ini');
            return false;
        }
        if ($upload_max_filesize && $this->_get_server_vars('CONTENT_LENGTH') > $upload_max_filesize) {
            $file['error'] = Tools::display_error('The uploaded file exceeds the upload_max_filesize directive in php.ini');
            return false;
        }
        if ($error = Image_Manager::validate_upload($file, Tools::get_max_upload_size($this->get_max_size()), $this->get_accept_types())) {
            $file['error'] = $error;
            return false;
        }
        if ($file['size'] > $this->get_max_size()) {
            $file['error'] = sprintf(Tools::display_error('File (size : %1s) is too big (max : %2s)'), $file['size'], $this->get_max_size());
            return false;
        }
        return true;
    }
}