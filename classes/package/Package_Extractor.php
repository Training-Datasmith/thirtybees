<?php

declare (strict_types=1);
/**
 * Copyright (C) 2017-2024 thirty bees
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
 * @copyright 2017-2024 thirty bees
 * @license   Open Software License (OSL 3.0)
 */
namespace Thirtybees\Core\Package;

use Archive_Tar;
use Presta_Shop_Exception;
use Recursive_Directory_Iterator;
use Recursive_Iterator_Iterator;
use Spl_File_Info;
use Throwable;
use Tools;
use Zip_Archive;
/**
 * Class PackageExtractorCore
 *
 * This class can be used to extract zip and tgz packages. Expected usage is to extract
 * module packages into /modules/ directory, but it can be used elsewhere as well.
 */
class Package_Extractor_Core
{
    /**
     * Merge mode - when target directory exists, package content will be merged into it
     */
    public const MODE_MERGE = 'MERGE';
    /**
     * Replace mode - when target directory exists, it will be replaced with package content
     */
    public const MODE_REPLACE = 'REPLACE';
    /**
     * @var string target directory, into which package will be installed
     */
    protected $target_directory;
    /**
     * @var string temp directory, used to temporary store unzipped downloaded content
     */
    protected string $temp_directory;
    /**
     * @var int permissions for newly created directories
     */
    protected $directory_perms = 0755;
    /**
     * @var int permissions for newly created files
     */
    protected $file_perms = 0644;
    /**
     * @var string extract mode - one of MERGE | REPLACE
     */
    protected $mode = self::MODE_MERGE;
    /**
     * @var callable validator for package files. It receives list of package files as an argument, and
     *      returns list of errors if validation fails, or null/empty list on validation success
     */
    protected $package_validator;
    /**
     * @var array[] array containing all error messages generated during package extraction
     */
    private array $errors = [];
    /**
     * @var string[] array containing all warning messages collected during package extractions
     */
    private array $warnings = [];
    /**
     * PackageInstallerCore constructor.
     *
     * @param string $targetDirectory directory where to extract packages
     *
     * @throws PrestaShopException
     */
    public function __construct($target_directory)
    {
        // resolve target directory
        $target_directory = $this->normalize_path($target_directory, true);
        if (!is_dir($target_directory)) {
            throw new Presta_Shop_Exception("Target directory does not exists: {$target_directory}");
        }
        $this->target_directory = $target_directory;
        // set default temp directory
        $this->temp_directory = $this->normalize_path(_PS_CACHE_DIR_, true) . 'tmp/';
    }
    /**
     * Main method that extracts package into target directory.
     *
     * $source parameter can be either file path or url address. Required $name parameter must contain
     * name of top_level folder in zip file that we want to extract from archive
     *
     * @param string $source filepath / url to package
     * @param string $name name of the top-level directory from zip to extract
     *
     * @return bool
     */
    public function extract_package($source, $name)
    {
        $temp_file = null;
        try {
            $this->errors = [];
            $this->warnings = [];
            // if source is url, fetch it
            if (preg_match('/^https?:\/\//', $source)) {
                $temp_file = $this->fetch_remote_package($source, $name);
                if (!$temp_file) {
                    return false;
                }
                $source = $temp_file;
            }
            return $this->extract_local_package($source, $name);
        } catch (Throwable $e) {
            return $this->error('Fatal error: ' . $e->get_message(), $e);
        } finally {
            if ($temp_file && @is_file($temp_file)) {
                @unlink($temp_file);
            }
        }
    }
    /**
     * This method returns list of top-level directories in package
     *
     * @param string $source filepath / url to package
     *
     * @return string[]
     *
     * @throws PrestaShopException
     */
    public function get_package_top_level_directories($source)
    {
        $temp_file = null;
        try {
            $this->errors = [];
            $this->warnings = [];
            // if source is url, fetch it
            if (preg_match('/^https?:\/\//', $source)) {
                $temp_file = $this->fetch_remote_package($source, 'unknown');
                if (!$temp_file) {
                    return [];
                }
                $source = $temp_file;
            }
            // check that input file exists
            if (!@is_file($source)) {
                $this->error(sprintf(Tools::display_error('File not found: %s'), $source));
                return null;
            }
            return strtolower(substr($source, -4)) === '.zip' ? $this->zip_top_level_directories($source) : $this->tar_top_level_directories($source);
        } finally {
            if ($temp_file && @is_file($temp_file)) {
                @unlink($temp_file);
            }
        }
    }
    /**
     * This method returns list of errors that occurred during last extract package call
     *
     * @return array
     */
    public function get_errors()
    {
        return $this->errors;
    }
    /**
     * This method returns list of warnings that occurred during last extract package call
     *
     * @return array
     */
    public function get_warnings()
    {
        return $this->warnings;
    }
    /**
     * This method can be used to attach external validator. This validator will be called before package content
     * is copied to the destination. It can perform additional checks on files, and prevent installing invalid package.
     *
     * Example usage: check that <module_name>/<module_name>.php file exists
     *
     * @param callable $validator validator for package files.
     *
     * @return $this
     */
    public function set_package_validator($validator): static
    {
        $this->package_validator = $validator;
        return $this;
    }
    /**
     * Sets merge algorithm to be used in case target directory already exists
     *
     *   MERGE - package content will be merged into existing directory, overwriting same files
     *   REPLACE - existing directory will be replaced by fresh copy from package
     *
     * @param string $mode merge mode
     *
     * @return $this
     */
    public function set_mode($mode): static
    {
        if ($mode === static::MODE_MERGE || $mode === static::MODE_REPLACE) {
            $this->mode = $mode;
        }
        return $this;
    }
    /**
     * Sets chmod permissions to be used for newly created directories
     *
     * @param int $directoryPerms
     *
     * @return $this
     */
    public function set_directory_perms($directory_perms): static
    {
        $this->directory_perms = $directory_perms;
        return $this;
    }
    /**
     * Sets chmod permissions to be used for newly created files
     *
     * @param int $filePerms
     *
     * @return $this
     */
    public function set_file_perms($file_perms): static
    {
        $this->file_perms = $file_perms;
        return $this;
    }
    /**
     * Extracts package that already exists on filesystem
     *
     * @param string $filepath file path to zip file
     * @param string $name name of the top-level directory from zip to extract
     *
     * @return bool
     *
     * @throws Throwable
     */
    protected function extract_local_package($filepath, $name)
    {
        $dir = null;
        try {
            // unpack package
            $dir = $this->unpack($filepath, $name);
            if (!$dir) {
                return false;
            }
            return $this->validate_package_content($dir, $name) && $this->copy_package_content($dir, $name);
        } finally {
            if ($dir) {
                Tools::delete_directory($dir);
                Tools::clear_op_cache();
            }
        }
    }
    /**
     * Validates content of extracted package. If packageValidator has been provided,
     * it will be called as well
     *
     * @param string $dir directory
     * @param string $name name of the top-level directory from zip to extract
     *
     * @return bool
     */
    protected function validate_package_content($dir, $name)
    {
        $iterator = new Recursive_Iterator_Iterator(new Recursive_Directory_Iterator($dir));
        $files = [];
        $relative_start = strlen($dir) + 1;
        foreach ($iterator as $item) {
            if ($this->should_copy_file($item)) {
                $path = $this->normalize_path($item->get_pathname());
                $relative_path = substr($path, $relative_start);
                $filename = $item->get_filename();
                $files[$relative_path] = ['path' => $path, 'filename' => $filename];
            }
        }
        if (!$files) {
            return $this->error(Tools::display_error('Package is empty'));
        }
        if ($this->package_validator) {
            $errors = call_user_func($this->package_validator, $files, $name);
            if ($errors && is_array($errors)) {
                foreach ($errors as $error) {
                    $this->error($error);
                }
                return false;
            }
        }
        return true;
    }
    /**
     * Copies package content from staging area to real destination
     *
     * @param string $dir path to source directory
     * @param string $name name of directory to copy
     *
     * @return bool
     */
    protected function copy_package_content($dir, string $name)
    {
        if ($this->mode === static::MODE_REPLACE) {
            $target_dir = $this->target_directory . $name;
            if (@is_dir($target_dir)) {
                Tools::delete_directory($target_dir);
            }
        }
        // Copy content
        $iterator = new Recursive_Iterator_Iterator(new Recursive_Directory_Iterator($dir));
        $relative_start = strlen($dir) + 1;
        $valid = true;
        foreach ($iterator as $item) {
            if ($this->should_copy_file($item)) {
                $source_path = $this->normalize_path($item->get_pathname());
                $relative_path = substr($source_path, $relative_start);
                $valid = $this->move_file($source_path, $relative_path) && $valid;
            }
        }
        return $valid;
    }
    /**
     * Returns true, if file should be copied from package.
     *
     *
     */
    protected function should_copy_file(Spl_File_Info $file): bool
    {
        // check file name
        $name = $file->get_filename();
        if (in_array($name, ['.', '..'])) {
            return false;
        }
        // check parent directories
        $path = explode('/', $this->normalize_path($file->get_path()));
        foreach ($path as $dir) {
            if (in_array($dir, ['.svn', '.git', '__MACOSX'])) {
                return false;
            }
        }
        return true;
    }
    /**
     * Moves $source file to destination.
     *
     * @param string $source path to file to copy
     * @param string $relativeTarget relative path from $this->targetDirectory
     *
     * @return bool
     */
    protected function move_file($source, string $relative_target)
    {
        // create directory
        $path = array_filter(explode('/', $relative_target));
        array_pop($path);
        $dir = $this->target_directory . implode('/', $path);
        if (!@is_dir($dir) && !@mkdir($dir, $this->directory_perms, true)) {
            return $this->error(sprintf(Tools::display_error('Failed to create directory %s'), $dir));
        }
        // move file
        $target = $this->target_directory . $relative_target;
        if (!@rename($source, $target)) {
            return $this->error(sprintf(Tools::display_error('Failed to create file %s'), $target));
        }
        if (!@chmod($target, $this->file_perms)) {
            $this->warning(sprintf(Tools::display_error('Failed to change file permissions for file %s'), $target));
        }
        return true;
    }
    /**
     * This method unpacks package $filepath to temporary directory.
     *
     * @param string $filepath file path to zip file
     * @param string $name name of the top-level directory from zip to extract
     *
     * @return string file path to temp directory containing $name subdirectory, or null
     *
     * @throws Throwable
     */
    protected function unpack($filepath, string $name): ?string
    {
        // check that input file exists
        if (!@is_file($filepath)) {
            $this->error(sprintf(Tools::display_error('File not found: %s'), $filepath));
            return null;
        }
        // check that temp directory exists
        if (!@is_dir($this->temp_directory) && !@mkdir($this->temp_directory, 0777, true)) {
            $this->error(sprintf(Tools::display_error("Temp directory not exists and can't be created: %s"), $this->temp_directory));
            return null;
        }
        // create temporary directory for extraction
        $temp_dir = tempnam($this->temp_directory, $name . '-');
        @unlink($temp_dir);
        if (!@mkdir($temp_dir)) {
            $this->error(sprintf(Tools::display_error('Failed to create temporary directory: %s'), $temp_dir));
            return null;
        }
        try {
            // unpack using correct algorithm
            $res = strtolower(substr($filepath, -4)) === '.zip' ? $this->unzip($filepath, $temp_dir) : $this->untar($filepath, $temp_dir);
            if (!$res) {
                Tools::delete_directory($temp_dir);
                return null;
            }
            // clean up directory content -- we want to keep only $name directory
            $found = false;
            foreach (@scandir($temp_dir) as $subdir) {
                if ($subdir === '.') {
                    continue;
                }
                if ($subdir === '..') {
                    continue;
                }
                $path = $temp_dir . '/' . $subdir;
                // we don't want any files in top level directory
                if (is_file($path)) {
                    @unlink($path);
                }
                // if the entry is dir, check if it the wanted one
                if (is_dir($path)) {
                    if ($subdir === $name) {
                        $found = true;
                    } else {
                        Tools::delete_directory($path);
                    }
                }
            }
            if (!$found) {
                $this->error(sprintf(Tools::display_error('Archive does not contain top-level directory %s'), $name));
                Tools::delete_directory($temp_dir);
                return null;
            }
            return $temp_dir;
        } catch (Throwable $e) {
            // delete temp directory on any exception
            Tools::delete_directory($temp_dir);
            throw $e;
        }
    }
    /**
     * Unzips file to $tempDir
     *
     * @param string $filepath
     * @param string $tempDir
     *
     * @return bool
     */
    protected function unzip($filepath, $temp_dir)
    {
        // extract zip file
        $zip = new Zip_Archive();
        // open zip archive
        if ($zip->open($filepath) !== true) {
            return $this->error(sprintf(Tools::display_error('Failed to open zip archive: %s'), $filepath));
        }
        // extract content
        if (!$zip->extract_to($temp_dir)) {
            $zip->close();
            return $this->error(sprintf(Tools::display_error('Failed to extract zip archive: %s'), $filepath));
        }
        // close zip archive
        $zip->close();
        return true;
    }
    /**
     * Unpacks file to $tempDir
     *
     * @param string $filepath
     * @param string $tempDir
     *
     * @return bool
     */
    protected function untar($filepath, $temp_dir)
    {
        $archive = new Archive_Tar($filepath);
        if (!$archive->extract($temp_dir)) {
            return $this->error(sprintf(Tools::display_error('Failed to extract tgz archive: %s'), $filepath));
        }
        return true;
    }
    /**
     * Returns list of top-level directories from zip file
     *
     * @param string $filepath
     * @return string[]
     */
    protected function zip_top_level_directories(array $filepath): array
    {
        $zip = new Zip_Archive();
        $zip->open($filepath);
        $dirs = [];
        for ($i = 0; $i < $zip->num_files; $i++) {
            $file_path = array_filter(explode('/', $zip->get_name_index($i)));
            if ($file_path) {
                $dirs[] = $file_path[0];
            }
        }
        return array_values(array_unique($dirs));
    }
    /**
     * Returns list of top-level directories from tgz file
     *
     * @param string $filepath
     * @return string[]
     */
    protected function tar_top_level_directories(array $filepath): array
    {
        $archive = new Archive_Tar($filepath);
        $dirs = [];
        foreach ($archive->list_content() as $entry) {
            $file_path = array_filter(explode('/', (string) $entry['filename']));
            if ($file_path) {
                $dirs[] = $file_path[0];
            }
        }
        return array_values(array_unique($dirs));
    }
    /**
     * Method to retrieve package from url
     *
     * @param string $url url address
     * @param string $name name of directory we want to extract from the package.
     *
     * @return string
     * @throws PrestaShopException
     */
    protected function fetch_remote_package($url, string $name)
    {
        // check that temp directory exists
        if (!@is_dir($this->temp_directory) && !@mkdir($this->temp_directory, 0777, true)) {
            $this->error(sprintf(Tools::display_error("Temp directory not exists and can't be created: %s"), $this->temp_directory));
            return null;
        }
        // download file
        $suffix = '.zip';
        if (str_ends_with($url, '.tar.gz') || str_ends_with($url, '.gz') || str_ends_with($url, '.tar') || str_ends_with($url, '.tgz')) {
            $suffix = '.tgz';
        }
        $filename = $this->temp_directory . $name . '-' . md5(Tools::passwd_gen() . time()) . $suffix;
        if (!Tools::copy($url, $filename)) {
            $this->error(sprintf(Tools::display_error('Failed to download file %s'), $url));
            @unlink($filename);
            return null;
        }
        return is_file($filename) ? $filename : null;
    }
    /**
     * Helper method to save error message
     *
     * @param string $message
     * @param Throwable|null $exception
     *
     * @return false
     */
    protected function error($message, $exception = null): bool
    {
        $entry = ['message' => $message];
        if ($exception) {
            $entry['exception'] = $exception;
        }
        $this->errors[] = $entry;
        return false;
    }
    /**
     * Helper method to save warning message
     *
     * @param string $message
     */
    protected function warning($message)
    {
        $this->warnings[] = ['message' => $message];
    }
    /**
     * Helper method to covert part to normalized (linux-based) version
     *
     * @param string $path file path
     * @param bool $addTrailingSlash if true, / will be added at the end
     *
     * @return string
     */
    protected function normalize_path($path, $add_trailing_slash = false): string|array
    {
        $path = str_replace('\\', '/', $path);
        if ($add_trailing_slash) {
            return rtrim($path, '/') . '/';
        }
        return $path;
    }
}