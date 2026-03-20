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
 * Class FileUploaderCore
 */
class File_Uploader_Core
{
    /**
     * @var string[]
     */
    protected array $allowed_extensions;
    /**
     * @var QqUploadedFileXhr|QqUploadedFileForm|false
     */
    protected $file;
    /**
     * FileUploaderCore constructor.
     *
     * @param string[] $allowedExtensions
     * @param int $sizeLimit
     */
    public function __construct(array $allowed_extensions = [], protected $size_limit = 10485760)
    {
        $allowed_extensions = array_map(strtolower(...), $allowed_extensions);
        $this->allowed_extensions = $allowed_extensions;
        if (isset($_GET['qqfile'])) {
            $this->file = new Qq_Uploaded_File_Xhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new Qq_Uploaded_File_Form();
        } else {
            $this->file = false;
        }
    }
    /**
     * @param string $str
     * @return int|string
     */
    protected function to_bytes($str)
    {
        $val = trim($str);
        $last = strtolower($str[strlen($str) - 1]);
        switch ($last) {
            case 'g':
                $val *= 1024;
            // Fall though allowed
            // no break
            case 'm':
                $val *= 1024;
            // Fall through allowed
            // no break
            case 'k':
                $val *= 1024;
        }
        return $val;
    }
    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     *
     * @throws PrestaShopException
     */
    public function handle_upload()
    {
        if (!$this->file) {
            return ['error' => Tools::display_error('No files were uploaded.')];
        }
        $size = $this->file->get_size();
        if ($size == 0) {
            return ['error' => Tools::display_error('File is empty')];
        }
        if ($size > $this->size_limit) {
            return ['error' => Tools::display_error('File is too large')];
        }
        $pathinfo = pathinfo($this->file->get_name());
        $these = implode(', ', $this->allowed_extensions);
        if (!isset($pathinfo['extension'])) {
            return ['error' => sprintf(Tools::display_error('File has an invalid extension, it should be one of these: %s.'), $these)];
        }
        $ext = $pathinfo['extension'];
        if ($this->allowed_extensions && !in_array(strtolower($ext), $this->allowed_extensions)) {
            return ['error' => sprintf(Tools::display_error('File has an invalid extension, it should be one of these: %s.'), $these)];
        }
        return $this->file->save();
    }
}
/**
 * Class QqUploadedFileForm
 */
class Qq_Uploaded_File_Form
{
    /**
     * Save the file to the specified path
     *
     * @return array
     * @throws PrestaShopException
     */
    public function save()
    {
        $product = new Product($_GET['id_product']);
        if (!Validate::is_loaded_object($product)) {
            return ['error' => Tools::display_error('Cannot add image because product creation failed.')];
        }
        $image = new Image();
        $image->id_product = (int) $product->id;
        $image->position = Image::get_highest_position($product->id) + 1;
        $legends = Tools::get_value('legend');
        if (is_array($legends)) {
            foreach ($legends as $key => $legend) {
                if (Validate::is_generic_name($legend)) {
                    $image->legend[(int) $key] = $legend;
                } else {
                    return ['error' => sprintf(Tools::display_error('Error on image caption: "%1s" is not a valid caption.'), Tools::safe_output($legend))];
                }
            }
        }
        if (!Image::get_cover($image->id_product)) {
            $image->cover = 1;
        } else {
            $image->cover = 0;
        }
        if (($validate = $image->validate_fields_lang(false, true)) !== true) {
            return ['error' => Tools::display_error($validate)];
        }
        if (!$image->add()) {
            return ['error' => Tools::display_error('Error while creating additional image')];
        }
        return $this->copy_image($product->id, $image->id);
    }
    /**
     * @param int $idProduct
     * @param int $idImage
     * @param string $method
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function copy_image($id_product, $id_image, $method = 'auto'): array
    {
        $image = new Image($id_image);
        if (!$new_path = $image->get_path_for_creation()) {
            return ['error' => Tools::display_error('An error occurred during new folder creation')];
        }
        if (!($tmp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES['qqfile']['tmp_name'], $tmp_name)) {
            return ['error' => Tools::display_error('An error occurred during the image upload')];
        }
        if (!Image_Manager::resize($tmp_name, $new_path . '.' . $image->image_format)) {
            return ['error' => Tools::display_error('An error occurred while copying image.')];
        }
        if ($method == 'auto') {
            $images_types = Image_Type::get_images_types(Image_Entity::ENTITY_TYPE_PRODUCTS);
            foreach ($images_types as $image_type) {
                if (!Image_Manager::resize($tmp_name, $new_path . '-' . stripslashes((string) $image_type['name']) . '.' . $image->image_format, $image_type['width'], $image_type['height'], $image->image_format)) {
                    return ['error' => Tools::display_error('An error occurred while copying image:') . ' ' . stripslashes((string) $image_type['name'])];
                }
            }
        }
        unlink($tmp_name);
        Hook::trigger_event('actionWatermark', ['id_image' => $id_image, 'id_product' => $id_product]);
        if (!$image->update()) {
            return ['error' => Tools::display_error('Error while updating status')];
        }
        $img = ['id_image' => $image->id, 'position' => $image->position, 'cover' => $image->cover, 'name' => $this->get_name(), 'legend' => $image->legend];
        return ['success' => $img];
    }
    /**
     * @return string
     */
    public function get_name()
    {
        return $_FILES['qqfile']['name'];
    }
    /**
     * @return int
     */
    public function get_size()
    {
        return $_FILES['qqfile']['size'];
    }
}
/**
 * Handle file uploads via XMLHttpRequest
 */
class Qq_Uploaded_File_Xhr
{
    /**
     * Save the file to the specified path
     *
     * @param string $path
     *
     * @return bool TRUE on success
     */
    public function upload($path): bool
    {
        $input = fopen('php://input', 'r');
        $target = fopen($path, 'w');
        $real_size = stream_copy_to_stream($input, $target);
        if ($real_size != $this->get_size()) {
            return false;
        }
        fclose($input);
        fclose($target);
        return true;
    }
    /**
     * @return array
     *
     * @throws PrestaShopException
     */
    public function save()
    {
        $product = new Product($_GET['id_product']);
        if (!Validate::is_loaded_object($product)) {
            return ['error' => Tools::display_error('Cannot add image because product creation failed.')];
        }
        $image = new Image();
        $image->id_product = (int) $product->id;
        $image->position = Image::get_highest_position($product->id) + 1;
        $legends = Tools::get_value('legend');
        if (is_array($legends)) {
            foreach ($legends as $key => $legend) {
                if (Validate::is_generic_name($legend)) {
                    $image->legend[(int) $key] = $legend;
                } else {
                    return ['error' => sprintf(Tools::display_error('Error on image caption: "%1s" is not a valid caption.'), Tools::safe_output($legend))];
                }
            }
        }
        if (!Image::get_cover($image->id_product)) {
            $image->cover = 1;
        } else {
            $image->cover = 0;
        }
        if (($validate = $image->validate_fields_lang(false, true)) !== true) {
            return ['error' => Tools::display_error($validate)];
        }
        if (!$image->add()) {
            return ['error' => Tools::display_error('Error while creating additional image')];
        }
        return $this->copy_image($product->id, $image->id);
    }
    /**
     * @param int $idProduct
     * @param int $idImage
     * @param string $method
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function copy_image($id_product, $id_image, $method = 'auto'): array
    {
        $image = new Image($id_image);
        if (!$new_path = $image->get_path_for_creation()) {
            return ['error' => Tools::display_error('An error occurred during new folder creation')];
        }
        if (!($tmp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !$this->upload($tmp_name)) {
            return ['error' => Tools::display_error('An error occurred during the image upload')];
        }
        if (!Image_Manager::resize($tmp_name, $new_path . '.' . $image->image_format)) {
            return ['error' => Tools::display_error('An error occurred while copying image.')];
        }
        if ($method == 'auto') {
            $images_types = Image_Type::get_images_types(Image_Entity::ENTITY_TYPE_PRODUCTS);
            foreach ($images_types as $image_type) {
                /*
                    $theme = (Shop::isFeatureActive() ? '-'.$imageType['id_theme'] : '');
                    if (!ImageManager::resize($tmpName, $new_path.'-'.stripslashes($imageType['name']).$theme.'.'.$image->image_format, $imageType['width'], $imageType['height'], $image->image_format))
                        return array('error' => Tools::displayError('An error occurred while copying image:').' '.stripslashes($imageType['name']));
                */
                if (!Image_Manager::resize($tmp_name, $new_path . '-' . stripslashes((string) $image_type['name']) . '.' . $image->image_format, $image_type['width'], $image_type['height'], $image->image_format)) {
                    return ['error' => Tools::display_error('An error occurred while copying image:') . ' ' . stripslashes((string) $image_type['name'])];
                }
            }
        }
        unlink($tmp_name);
        Hook::trigger_event('actionWatermark', ['id_image' => $id_image, 'id_product' => $id_product]);
        if (!$image->update()) {
            return ['error' => Tools::display_error('Error while updating status')];
        }
        $img = ['id_image' => $image->id, 'position' => $image->position, 'cover' => $image->cover, 'name' => $this->get_name(), 'legend' => $image->legend];
        return ['success' => $img];
    }
    /**
     * @return string
     */
    public function get_name()
    {
        return $_GET['qqfile'];
    }
    public function get_size(): int|false
    {
        if (!(isset($_SERVER['CONTENT_LENGTH']) || isset($_SERVER['HTTP_CONTENT_LENGTH']))) {
            return false;
        }
        if (isset($_SERVER['HTTP_CONTENT_LENGTH'])) {
            return (int) $_SERVER['HTTP_CONTENT_LENGTH'];
        }
        return (int) $_SERVER['CONTENT_LENGTH'];
    }
}