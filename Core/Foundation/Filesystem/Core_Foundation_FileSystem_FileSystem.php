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
 * Class Core_Foundation_FileSystem_FileSystem
 */
class Core_foundation_file_System_file_System
{
    /**
     * Replaces directory separators with the system's native one
     * and trims the trailing separator.
     *
     * @param string $path
     *
     * @return string
     */
    public function normalize_path($path)
    {
        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }
    /**
     * @param string $a
     * @param string $b
     *
     * @return string
     */
    protected function join_two_paths($a, $b)
    {
        return $this->normalize_path($a) . DIRECTORY_SEPARATOR . $this->normalize_path($b);
    }
    /**
     * Joins an arbitrary number of paths, normalizing them along the way.
     *
     * @return string|null
     * @throws Core_Foundation_FileSystem_Exception
     */
    public function join_paths()
    {
        if (func_num_args() < 2) {
            throw new Core_foundation_file_System_exception('joinPaths requires at least 2 arguments.');
        } elseif (func_num_args() === 2) {
            $arg0 = func_get_arg(0);
            $arg1 = func_get_arg(1);
            return $this->join_two_paths($arg0, $arg1);
        } elseif (func_num_args() > 2) {
            $func_args = func_get_args();
            $arg0 = func_get_arg(0);
            return $this->join_paths($arg0, call_user_func_array([$this, 'joinPaths'], array_slice($func_args, 1)));
        }
        return null;
    }
    /**
     * Performs a depth first listing of directory entries.
     * Throws exception if $path is not a file.
     * If $path is a file and not a directory, just gets the file info for it
     * and return it in an array.
     *
     * @param string $path
     *
     * @return array of SplFileInfo object indexed by file path
     * @throws Core_Foundation_FileSystem_Exception
     */
    public function list_entries_recursively($path)
    {
        if (!file_exists($path)) {
            throw new Core_foundation_file_System_exception(sprintf('No such file or directory: %s', $path));
        }
        if (!is_dir($path)) {
            throw new Core_foundation_file_System_exception(sprintf('%s is not a directory', $path));
        }
        $entries = [];
        foreach (scandir($path) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $new_path = $this->join_paths($path, $entry);
            $info = new Spl_File_Info($new_path);
            $entries[$new_path] = $info;
            if ($info->is_dir()) {
                $entries = array_merge($entries, $this->list_entries_recursively($new_path));
            }
        }
        return $entries;
    }
    /**
     * Filter used by listFilesRecursively.
     *
     * @param SplFileInfo $info
     *
     * @return bool
     */
    protected function match_only_files(Spl_File_Info $info)
    {
        return $info->is_file();
    }
    /**
     * Same as listEntriesRecursively but returns only files.
     *
     * @return array
     *
     * @throws Core_Foundation_FileSystem_Exception
     * @throws Core_Foundation_FileSystem_Exception
     */
    public function list_files_recursively($path)
    {
        return array_filter($this->list_entries_recursively($path), [$this, 'matchOnlyFiles']);
    }
}