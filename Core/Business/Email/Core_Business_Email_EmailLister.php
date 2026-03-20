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
/**
 * Class Core_Business_Email_EmailLister
 */
class Core_business_email_email_Lister
{
    /**
     * @var Core_Foundation_FileSystem_FileSystem
     */
    protected $filesystem;
    /**
     * Core_Business_Email_EmailLister constructor.
     *
     * @param Core_Foundation_FileSystem_FileSystem $fs
     */
    public function __construct(Core_foundation_file_System_file_System $fs)
    {
        // Register dependencies
        $this->filesystem = $fs;
    }
    /**
     * Return the list of available mails
     *
     * @param string $dir
     *
     * @return array|null
     *
     * @throws Core_Foundation_FileSystem_Exception
     */
    public function get_available_mails($dir)
    {
        if (!is_dir($dir)) {
            return null;
        }
        $mail_directory = $this->filesystem->list_entries_recursively($dir);
        $mail_list = [];
        // Remove unwanted .html / .txt / .tpl / .php / . / ..
        foreach ($mail_directory as $mail) {
            if (strpos($mail->get_filename(), '.') !== false) {
                $tmp = explode('.', $mail->get_filename());
                // Check for filename existence (left part) and if extension is html (right part)
                if ($tmp === false || !isset($tmp[0]) || isset($tmp[1]) && $tmp[1] !== 'html') {
                    continue;
                }
                $mail_name_no_ext = $tmp[0];
                if (!in_array($mail_name_no_ext, $mail_list)) {
                    $mail_list[] = $mail_name_no_ext;
                }
            }
        }
        return $mail_list;
    }
    /**
     * Give in input getAvailableMails(), will output a human readable and proper string name
     *
     * @param string $mailName
     *
     * @return string
     */
    public function get_cleaned_mail_name($mail_name)
    {
        if (strpos($mail_name, '.') !== false) {
            $tmp = explode('.', $mail_name);
            if ($tmp === false || !isset($tmp[0])) {
                return $mail_name;
            }
            $mail_name = $tmp[0];
        }
        return ucfirst(str_replace(['_', '-'], ' ', $mail_name));
    }
}