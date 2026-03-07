<?php

declare(strict_types=1);
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
 * Class UploaderCore
 */
class UploaderCore
{
    public const DEFAULT_MAX_SIZE = 10485760;

    private bool $checkFileSize = true;

    /**
     * @var string[]|null
     */
    private ?array $acceptTypes = null;

    private array $files = [];

    private int $maxSize = self::DEFAULT_MAX_SIZE;

    /**
     * @var string|null
     */
    private $name;

    /**
     * @var string
     */
    private $savePath = _PS_UPLOAD_DIR_;

    /**
     * UploaderCore constructor.
     *
     * @param string|null $name
     */
    public function __construct($name = null)
    {
        $this->setName($name);
    }

    /**
     * @param bool $value
     */
    public function setCheckFileSize($value): static
    {
        $this->checkFileSize = (bool)$value;

        return $this;
    }

    /**
     * @return array[]
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param string|null $dest
     *
     * @return array
     */
    public function process($dest = null)
    {
        $upload = $_FILES[$this->getName()] ?? null;

        if ($upload && is_array($upload['tmp_name'])) {
            $tmp = [];
            foreach ($upload['tmp_name'] as $index => $value) {
                $tmp[$index] = [
                    'tmp_name' => $upload['tmp_name'][$index],
                    'name'     => $upload['name'][$index],
                    'size'     => $upload['size'][$index],
                    'type'     => $upload['type'][$index],
                    'error'    => $upload['error'][$index],
                ];

                $this->files[] = $this->upload($tmp[$index], $dest);
            }
        } elseif ($upload) {
            $this->files[] = $this->upload($upload, $dest);
        }

        return $this->files;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $value
     */
    public function setName($value): static
    {
        $this->name = $value;

        return $this;
    }

    /**
     * @param string|null $dest
     *
     */
    public function upload(array $file, $dest = null): array
    {
        if ($this->validate($file)) {
            if (isset($dest) && is_dir($dest)) {
                $filePath = $dest;
            } else {
                $filePath = $this->getFilePath($dest ?? $file['name']);
            }

            if ($file['tmp_name'] && is_uploaded_file($file['tmp_name'])) {
                move_uploaded_file($file['tmp_name'], $filePath);
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents($filePath, fopen('php://input', 'r'));
            }

            $fileSize = $this->_getFileSize($filePath, true);

            if ($fileSize === $file['size']) {
                $file['save_path'] = $filePath;
            } else {
                $file['size'] = $fileSize;
                unlink($filePath);
                $file['error'] = Tools::displayError('Server file size is different from local file size');
            }
        }

        return $file;
    }

    protected function validate(array &$file): bool
    {
        $file['error'] = $this->checkUploadError($file['error']);

        if ($file['error']) {
            return false;
        }

        $postMaxSize = $this->getPostMaxSizeBytes();

        if ($postMaxSize && ($this->_getServerVars('CONTENT_LENGTH') > $postMaxSize)) {
            $file['error'] = Tools::displayError('The uploaded file exceeds the post_max_size directive in php.ini');

            return false;
        }

        if (preg_match('/%00/', (string) $file['name'])) {
            $file['error'] = Tools::displayError('Invalid file name');

            return false;
        }

        $types = $this->getAcceptTypes();

        if (isset($types) && !in_array(mb_strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION)), $types)) {
            $file['error'] = Tools::displayError('Filetype not allowed');

            return false;
        }

        if ($this->checkFileSize() && $file['size'] > $this->getMaxSize()) {
            $file['error'] = sprintf(Tools::displayError('File (size : %1s) is too big (max : %2s)'), $file['size'], $this->getMaxSize());

            return false;
        }

        return true;
    }

    /**
     * @param int $errorCode
     *
     * @return int|string
     */
    protected function checkUploadError($errorCode)
    {
        $error = 0;
        switch ($errorCode) {
            case 1:
                $error = sprintf(Tools::displayError('The uploaded file exceeds %s'), ini_get('upload_max_filesize'));
                break;
            case 2:
                $error = sprintf(Tools::displayError('The uploaded file exceeds %s'), ini_get('post_max_size'));
                break;
            case 3:
                $error = Tools::displayError('The uploaded file was only partially uploaded');
                break;
            case 4:
                $error = Tools::displayError('No file was uploaded');
                break;
            case 6:
                $error = Tools::displayError('Missing temporary folder');
                break;
            case 7:
                $error = Tools::displayError('Failed to write file to disk');
                break;
            case 8:
                $error = Tools::displayError('A PHP extension stopped the file upload');
                break;
            default:
                break;
        }

        return $error;
    }

    /**
     * @return int PHP setting 'post_max_size', converted to bytes.
     */
    public function getPostMaxSizeBytes(): int
    {
        $postMaxSize = ini_get('post_max_size');
        $bytes = (int) trim($postMaxSize);
        $last = strtolower(substr($postMaxSize, -1));

        return match ($last) {
            'g' => $bytes * 1024 * 1024 * 1024,
            'm' => $bytes * 1024 * 1024,
            'k' => $bytes * 1024,
            default => $bytes,
        };

    }

    /**
     * @param string $var
     *
     * @return string
     */
    protected function _getServerVars($var)
    {
        return $_SERVER[$var] ?? '';
    }

    /**
     * @return string[]|null
     */
    public function getAcceptTypes()
    {
        return $this->acceptTypes;
    }

    /**
     * @param string[] $value
     */
    public function setAcceptTypes($value): static
    {
        if (is_array($value) && count($value)) {
            $value = array_map(['Tools', 'strtolower'], $value);
        }
        $this->acceptTypes = $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function checkFileSize()
    {
        return $this->checkFileSize;
    }

    public function getMaxSize(): int
    {
        return (int)$this->maxSize;
    }

    /**
     * @param int $value
     */
    public function setMaxSize($value): static
    {
        $this->maxSize = (int)$value;

        return $this;
    }

    /**
     * @param string|null $fileName
     *
     * @return string
     */
    public function getFilePath($fileName = null)
    {
        if (!isset($fileName)) {
            return tempnam($this->getSavePath(), $this->getUniqueFileName());
        }

        return $this->getSavePath().$fileName;
    }

    /**
     * @return string
     */
    public function getSavePath()
    {
        return $this->_normalizeDirectory($this->savePath);
    }

    /**
     * @param string $value
     */
    public function setSavePath($value): static
    {
        $this->savePath = $value;
        return $this;
    }

    protected function _normalizeDirectory(string $directory): string
    {
        $last = $directory[strlen($directory) - 1];

        if (in_array($last, ['/', '\\'])) {
            $directory[strlen($directory) - 1] = DIRECTORY_SEPARATOR;

            return $directory;
        }

        return $directory . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $prefix
     */
    public function getUniqueFileName($prefix = 'PS'): string
    {
        return uniqid($prefix, true);
    }

    /**
     * @param string $filePath
     * @param bool $clearStatCache
     *
     * @return int
     */
    protected function _getFileSize($filePath, $clearStatCache = false): int|false
    {
        if ($clearStatCache) {
            clearstatcache(true, $filePath);
        }

        return filesize($filePath);
    }
}
