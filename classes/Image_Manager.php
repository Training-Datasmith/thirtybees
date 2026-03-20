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
use Thirtybees\Core\Error\Error_Utils;
/**
 * Class ImageManagerCore
 */
class Image_Manager_Core
{
    public const ERROR_FILE_NOT_EXIST = 1;
    public const ERROR_FILE_WIDTH = 2;
    public const ERROR_MEMORY_LIMIT = 3;
    public const ERROR_FORBIDDEN_IMAGE_EXTENSION = 4;
    public const DO_NOT_USE_WEBP = 0;
    public const USE_WEBP = 1;
    /**
     * Generate a cached thumbnail for object lists (eg. carrier, order statuses...etc)
     *
     * @param string $image Real image filename
     * @param string $cacheImage Cached filename
     * @param int $size Desired size
     * @param string $imageExtension Image type
     * @param bool $disableCache When turned on a timestamp will be added to the image URI to disable the HTTP cache
     * @param bool $regenerate When turned on and the file already exist, the file will be regenerated
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function thumbnail($image, $cache_image, $size, $image_extension = null, $disable_cache = true, $regenerate = false): string|array|int|float|false|null
    {
        $image_path = static::get_thumbnail_url($image, $cache_image, $size, $image_extension, $disable_cache, $regenerate);
        if ($image_path) {
            return '<img src="' . $image_path . '" alt="" class="imgm img-thumbnail" />';
        }
        return $image_path;
    }
    /**
     * Generate a cached thumbnail for image file and returns url to it
     *
     * @param string $image Real image filename
     * @param string $cacheImage Cached filename
     * @param int $size Desired size
     * @param string $imageExtension Image type
     * @param bool $disableCache When turned on a timestamp will be added to the image URI to disable the HTTP cache
     * @param bool $regenerate When turned on and the file already exist, the file will be regenerated
     *
     *
     * @throws PrestaShopException
     */
    public static function get_thumbnail_url($image, string $cache_image, $size, $image_extension = null, $disable_cache = true, $regenerate = false): string
    {
        if (!file_exists($image) && !$image = static::try_restore_image($image)) {
            return '';
        }
        $target_file = _PS_TMP_IMG_DIR_ . $cache_image;
        // delete existing thumbnail file if we are instructed to regenerate
        if (file_exists($target_file) && $regenerate) {
            @unlink($target_file);
        }
        // generate thumbnail file if it not exists yet
        if (!file_exists($target_file)) {
            $infos = getimagesize($image);
            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            if (!Image_Manager::check_image_memory_limit($image)) {
                return '';
            }
            $x = $infos[0];
            $y = $infos[1];
            $max_x = $size * 3;
            // Size is already ok
            if ($y < $size && $x <= $max_x) {
                copy($image, $target_file);
            } else {
                $ratio_x = $x / ($y / $size);
                if ($ratio_x > $max_x) {
                    $ratio_x = $max_x;
                    $size = $y / ($x / $max_x);
                }
                Image_Manager::resize($image, $target_file, (int) $ratio_x, (int) $size, $image_extension);
            }
        }
        if ($disable_cache) {
            $ts = file_exists($target_file) ? filemtime($target_file) : time();
            $suffix = '?v=' . $ts;
        } else {
            $suffix = '';
        }
        // Relative link will always work, whatever the base uri set in the admin
        if (Context::get_context()->controller->controller_type == 'admin') {
            return '../img/tmp/' . $cache_image . $suffix;
        }
        return _PS_TMP_IMG_ . $cache_image . $suffix;
    }
    /**
     * Returns file name of product image thumbnail
     *
     * @param int $imageId
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_product_image_thumbnail_file_name($image_id): string
    {
        return 'image_mini_' . (int) $image_id . '.' . Image_Manager::get_default_image_extension();
    }
    /**
     * Return path to product image thumbnail
     *
     * @param int $imageId
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_product_image_thumbnail_file_path($image_id): string
    {
        return _PS_TMP_IMG_DIR_ . static::get_product_image_thumbnail_file_name($image_id);
    }
    /**
     * Deletes product image thumbnail, if exists
     *
     * @param int $imageId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function delete_product_image_thumbnail($image_id)
    {
        $path = static::get_product_image_thumbnail_file_path($image_id);
        if ($path && file_exists($path)) {
            return @unlink($path);
        }
        return false;
    }
    /**
     * @param int $imageId
     * @param bool $disableCache
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function get_product_image_thumbnail_tag($image_id, $disable_cache = true)
    {
        $image_id = (int) $image_id;
        if ($image_id) {
            $source_file = '';
            foreach (Image_Manager::get_allowed_image_extensions(false, true) as $image_extension) {
                if (file_exists($source_file = _PS_PROD_IMG_DIR_ . Image::resolve_file_path($image_id, $image_extension))) {
                    break;
                }
            }
            $name = static::get_product_image_thumbnail_file_name($image_id);
            return static::thumbnail($source_file, $name, 45, null, $disable_cache);
        }
        return '';
    }
    /**
     * Check if memory limit is too long or not
     *
     * @param string $image
     */
    public static function check_image_memory_limit($image): bool
    {
        $infos = @getimagesize($image);
        if (!is_array($infos) || !isset($infos['bits'])) {
            return true;
        }
        $memory_limit = Tools::get_memory_limit();
        // memory_limit == -1 => unlimited memory
        if ((int) $memory_limit != -1) {
            $current_memory = memory_get_usage();
            $bits = $infos['bits'] / 8;
            $channel = $infos['channels'] ?? 1;
            // Evaluate the memory required to resize the image: if it's too much, you can't resize it.
            // For perfs, avoid computing static maths formulas in the code. pow(2, 16) = 65536 ; 1024 * 1024 = 1048576
            if (($infos[0] * $infos[1] * $bits * $channel + 65536) * 1.8 + $current_memory > $memory_limit - 1048576) {
                return false;
            }
        }
        return true;
    }
    /**
     * Resize, cut and optimize image
     *
     * @param string $srcFile Image object from $_FILE
     * @param string $dstFile Destination filename
     * @param int $dstWidth Desired width (optional)
     * @param int $dstHeight Desired height (optional)
     * @param string $imageExtension
     * @param bool $forceType
     * @param int $error
     * @param int $tgtWidth
     * @param int $tgtHeight
     * @param int $quality
     * @param int $srcWidth
     * @param int $srcHeight
     *
     * @return bool Operation result
     *
     * @throws PrestaShopException
     */
    public static function resize($src_file, string $dst_file, $dst_width = null, $dst_height = null, $image_extension = null, $force_type = false, &$error = 0, &$tgt_width = null, &$tgt_height = null, $quality = 5, &$src_width = null, &$src_height = null)
    {
        clearstatcache(true, $src_file);
        if (!file_exists($src_file) || !filesize($src_file)) {
            return !$error = static::ERROR_FILE_NOT_EXIST;
        }
        if (is_null($image_extension)) {
            // try to detect extension from target file name
            $image_extension = static::get_image_extension_from_filename($dst_file);
            if (!$image_extension) {
                // fallback to system default extension
                $image_extension = static::get_default_image_extension();
            }
        }
        [$tmp_width, $tmp_height, $type] = getimagesize($src_file);
        $src_width = $tmp_width;
        $src_height = $tmp_height;
        if (!$src_width) {
            return !$error = static::ERROR_FILE_WIDTH;
        }
        $dst_width = (int) $dst_width;
        if (!$dst_width) {
            $dst_width = $src_width;
        }
        $dst_height = (int) $dst_height;
        if (!$dst_height) {
            $dst_height = $src_height;
        }
        $width_diff = $dst_width / $src_width;
        $height_diff = $dst_height / $src_height;
        $ps_image_generation_method = Configuration::get('PS_IMAGE_GENERATION_METHOD');
        if ($width_diff > 1 && $height_diff > 1) {
            $next_width = $src_width;
            $next_height = $src_height;
        } else if ($ps_image_generation_method == 2 || !$ps_image_generation_method && $width_diff > $height_diff) {
            $next_height = $dst_height;
            $next_width = (int) round($src_width * $next_height / $src_height);
            $dst_width = !$ps_image_generation_method ? $dst_width : $next_width;
        } else {
            $next_width = (int) $dst_width;
            $next_height = (int) round($src_height * $dst_width / $src_width);
            $dst_height = !$ps_image_generation_method ? $dst_height : $next_height;
        }
        if (!Image_Manager::check_image_memory_limit($src_file)) {
            return !$error = static::ERROR_MEMORY_LIMIT;
        }
        $tgt_width = $dst_width;
        $tgt_height = $dst_height;
        $dest_image = imagecreatetruecolor($dst_width, $dst_height);
        // If image is a PNG or WEBP and the output is PNG/WEBP, fill with transparency. Else fill with white background.
        if ($image_extension == 'png' || $image_extension === 'webp' || $image_extension === 'avif') {
            imagealphablending($dest_image, false);
            imagesavealpha($dest_image, true);
            $transparent = imagecolorallocatealpha($dest_image, 255, 255, 255, 127);
            imagefilledrectangle($dest_image, 0, 0, $dst_width, $dst_height, $transparent);
        } else {
            $white = imagecolorallocate($dest_image, 255, 255, 255);
            imagefilledrectangle($dest_image, 0, 0, $dst_width, $dst_height, $white);
        }
        $src_image = Image_Manager::create($type, $src_file);
        if (!$src_image) {
            return false;
        }
        if ($dst_width >= $src_width && $dst_height >= $src_height) {
            imagecopyresized($dest_image, $src_image, (int) (($dst_width - $next_width) / 2), (int) (($dst_height - $next_height) / 2), 0, 0, $next_width, $next_height, $src_width, $src_height);
        } else {
            imagecopyresampled($dest_image, $src_image, (int) (($dst_width - $next_width) / 2), (int) (($dst_height - $next_height) / 2), 0, 0, $next_width, $next_height, $src_width, $src_height);
        }
        $write_file = Image_Manager::write($image_extension, $dest_image, $dst_file);
        @imagedestroy($src_image);
        return $write_file;
    }
    /**
     * Create an image with GD extension from a given type
     *
     * @param string $type
     * @param string $filename
     *
     * @return false|GdImage|resource
     */
    public static function create($type, $filename): \Gd_Image|false
    {
        // avif is supported from PHP8.1 only
        if (!defined('IMAGETYPE_AVIF')) {
            define('IMAGETYPE_AVIF', 19);
        }
        switch ($type) {
            case IMAGETYPE_GIF:
                $resource = imagecreatefromgif($filename);
                imagepalettetotruecolor($resource);
                // Otherwise gif to webp can lead in fatal error
                return $resource;
            case IMAGETYPE_PNG:
                return imagecreatefrompng($filename);
            case IMAGETYPE_WEBP:
                return imagecreatefromwebp($filename);
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($filename);
            case IMAGETYPE_AVIF:
                return function_exists('imagecreatefromavif') ? imagecreatefromavif($filename) : false;
            default:
                return false;
        }
    }
    /**
     * @param GdImage $dstImage
     * @param GdImage $srcImage
     * @param int $dstX
     * @param int $dstY
     * @param int $srcX
     * @param int $srcY
     * @param int $dstW
     * @param int $dstH
     * @param int $srcW
     * @param int $srcH
     * @param int $quality
     *
     *
     * @deprecated 1.4.0
     */
    public static function imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3): bool
    {
        Tools::display_as_deprecated();
        return imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
    }
    /**
     * Generate and write image
     *
     * @param string $imageExtension
     * @param GdImage|resource $resource
     * @param string $filename
     * @param int $quality
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function write($image_extension, $resource, $filename, $quality = null)
    {
        if (is_null($quality)) {
            $quality = Configuration::get('TB_IMAGE_QUALITY') ?: 90;
        }
        if (!Validate::is_int($quality) || $quality <= 0 || $quality > 100) {
            throw new Presta_Shop_Exception('Image quality value needs to be between 0 and 100!');
        }
        if (!in_array($image_extension, static::get_allowed_image_extensions(false, true))) {
            throw new Presta_Shop_Exception("The image extensions {$image_extension} is not supported!");
        }
        switch ($image_extension) {
            case 'gif':
                $success = imagegif($resource, $filename);
                break;
            case 'png':
                // PNG compression (0 => biggest file, 9 => smallest file)
                // This little mechanism transforms 0-100 range to a sensible compression value
                $quality *= -1;
                $quality += 100;
                $quality /= 10;
                $success = imagepng($resource, $filename, (int) $quality);
                break;
            case 'webp':
                $success = imagewebp($resource, $filename, (int) $quality);
                break;
            case 'avif':
                $success = function_exists('imageavif') && imageavif($resource, $filename, $quality);
                break;
            case 'jpg':
            case 'jpeg':
            default:
                imageinterlace($resource, 1);
                /// make it PROGRESSIVE
                $success = imagejpeg($resource, $filename, (int) $quality);
                break;
        }
        imagedestroy($resource);
        if (@file_exists(@$filename)) {
            @chmod($filename, 0664);
        }
        return $success;
    }
    /**
     * Copy and convert an image file
     *
     * @param string $sourceImage
     * @param string $newImageDest
     * @param bool   $unlinkOldImage
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function convert_image_to_extension($source_image, string $new_image_extension, $new_image_dest = '', $unlink_old_image = false, &$error = 0): bool
    {
        if (empty($info = getimagesize($source_image)) || empty($info[2])) {
            $error = Image_Manager::ERROR_FILE_NOT_EXIST;
            return false;
        }
        if (!Image_Manager::check_image_memory_limit($source_image)) {
            $error = static::ERROR_MEMORY_LIMIT;
            return false;
        }
        if (!in_array($new_image_extension, static::get_allowed_image_extensions(false, true))) {
            $error = static::ERROR_FORBIDDEN_IMAGE_EXTENSION;
            return false;
        }
        if ($resource = static::create($info[2], $source_image)) {
            if (!$new_image_dest) {
                $old_image_extension = pathinfo($source_image, PATHINFO_EXTENSION);
                $new_image_dest = str_replace('.' . $old_image_extension, '.' . $new_image_extension, $source_image);
            }
            // Note: when we copy/convert an image, we don't want to lose quality
            if (static::write($new_image_extension, $resource, $new_image_dest, 100)) {
                if ($unlink_old_image) {
                    unlink($source_image);
                }
                @imagedestroy($resource);
                return true;
            }
        }
        return false;
    }
    /**
     * Regenerate images for one entity
     *
     * @param string $entityType
     * @param int $idEntity
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function generate_image_types_by_entity($entity_type, $id_entity, $ids_image = [])
    {
        $image_entity = Image_Entity::get_image_entity_info($entity_type);
        if (!$image_entity) {
            return false;
        }
        $image_types = $image_entity['imageTypes'];
        if (!$image_types) {
            return false;
        }
        // Get all source image paths, that are related to this entity
        $possible_source_images = [];
        if ($entity_type == Image_Entity::ENTITY_TYPE_PRODUCTS) {
            if (empty($ids_image)) {
                $ids_image = array_column(Image::get_images(null, $id_entity), 'id_image');
            }
            foreach ($ids_image as $id_image) {
                $possible_source_images[] = ['description' => 'product ' . $id_entity . ', image ' . $id_image, 'path' => $image_entity['path'] . Image::get_img_folder_static($id_image), 'filename' => $id_image];
            }
        } else {
            $possible_source_images[] = ['description' => lcfirst((string) $image_entity['classname']) . ' ' . $id_entity, 'path' => $image_entity['path'], 'filename' => $id_entity];
        }
        $watermark_modules = Db::get_instance()->get_array((new Db_Query())->select('m.`name`')->from('module', 'm')->left_join('hook_module', 'hm', 'hm.`id_module` = m.`id_module`')->left_join('hook', 'h', 'hm.`id_hook` = h.`id_hook`')->where('h.`name` = \'actionWatermark\'')->where('m.`active` = 1'));
        // Loop through all possible source image paths
        $success = true;
        foreach ($possible_source_images as $possible_source_image) {
            Image_Manager::clean_source_image($possible_source_image['path'], $possible_source_image['filename']);
            // Check if the image does really exist
            if ($source_image = Image_Manager::get_source_image($possible_source_image['path'], $possible_source_image['filename'])) {
                [$source_width, $source_height] = getimagesize($source_image);
                $base_name = pathinfo($source_image, PATHINFO_DIRNAME) . '/' . pathinfo($source_image, PATHINFO_FILENAME);
                $default_image_extension = Image_Manager::get_default_image_extension();
                foreach ($image_types as $image_type) {
                    // Check if imageType is alias
                    if ($image_type['id_image_type_parent']) {
                        continue;
                    }
                    $dst_file = $base_name . '-' . stripslashes((string) $image_type['name']) . '.' . $default_image_extension;
                    $success = static::resize($source_image, $dst_file, $image_type['width'], $image_type['height'], $default_image_extension) && $success;
                    // Only generate if size of sourceImage is big enough
                    if (static::retina_support() && ($source_width >= $image_type['width'] * 2 || $source_height >= $image_type['height'] * 2)) {
                        $dst_file_retina = $base_name . '-' . stripslashes((string) $image_type['name']) . '2x.' . $default_image_extension;
                        $success = static::resize($source_image, $dst_file_retina, $image_type['width'] * 2, $image_type['height'] * 2, $default_image_extension) && $success;
                    }
                }
                // Call actionWatermark hook
                if (is_array($watermark_modules) && count($watermark_modules) && $entity_type == Image_Entity::ENTITY_TYPE_PRODUCTS) {
                    foreach ($watermark_modules as $module) {
                        $module_instance = Module::get_instance_by_name($module['name']);
                        if ($module_instance && is_callable([$module_instance, 'hookActionWatermark'])) {
                            call_user_func([$module_instance, 'hookActionWatermark'], ['id_image' => $possible_source_image['filename'], 'id_product' => $id_entity, 'image_type' => $image_types]);
                        }
                    }
                }
            } else if ($entity_type === Image_Entity::ENTITY_TYPE_PRODUCTS) {
                $description = $possible_source_image['description'];
                $path = $possible_source_image['path'] . $possible_source_image['filename'] . '.' . static::get_default_image_extension();
                $path = Error_Utils::get_relative_file($path);
                throw new Presta_Shop_Exception("Source image file for {$description} not found ({$path})");
            }
        }
        return $success;
    }
    /**
     * Validate image upload (check image type and weight)
     *
     * @param array $file Upload $_FILE value
     * @param int $maxFileSize Maximum upload size
     * @param string[] $allowedExtensions allowed image extensions
     *
     * @return bool|string Return false if no error encountered
     */
    public static function validate_upload(array $file, $max_file_size = 0, $allowed_extensions = null)
    {
        if ((int) $max_file_size > 0 && $file['size'] > (int) $max_file_size) {
            return sprintf(Tools::display_error('Image is too large (%1$d kB). Maximum allowed: %2$d kB'), $file['size'] / 1024, $max_file_size / 1024);
        }
        if ($file['error']) {
            return Tools::decode_upload_error($file['error']);
        }
        if (!Image_Manager::is_real_image($file['tmp_name'], $file['type']) || !Image_Manager::is_correct_image_file_ext($file['name'], $allowed_extensions) || preg_match('/%00/', (string) $file['name'])) {
            return Tools::display_error('Image format not recognized, allowed formats are: ') . implode(', ', static::get_allowed_image_extensions());
        }
        return false;
    }
    /**
     * Check if file is a real image
     *
     * @param string $filename File path to check
     * @param string $fileMimeType File known mime type (generally from $_FILES)
     * @param array $mimeTypeList Allowed MIME types
     */
    public static function is_real_image($filename, $file_mime_type = null, $mime_type_list = null): bool
    {
        // Detect mime content type
        $mime_type = false;
        if (!$mime_type_list) {
            foreach (Media::get_file_informations('images') as $image_file_info) {
                $mime_type_list[] = $image_file_info['mimeType'];
            }
        }
        // Try 4 different methods to determine the mime type
        if (function_exists('getimagesize')) {
            $image_info = @getimagesize($filename);
            if ($image_info) {
                $mime_type = $image_info['mime'];
            } else {
                $file_mime_type = false;
            }
        } elseif (function_exists('finfo_open')) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            $finfo = finfo_open($const);
            $mime_type = finfo_file($finfo, $filename);
            finfo_close($finfo);
        } elseif (function_exists('mime_content_type')) {
            $mime_type = mime_content_type($filename);
        } elseif (function_exists('exec')) {
            $mime_type = trim(exec('file -b --mime-type ' . escapeshellarg($filename)));
            if (!$mime_type) {
                $mime_type = trim(exec('file --mime ' . escapeshellarg($filename)));
            }
            if (!$mime_type) {
                $mime_type = trim(exec('file -bi ' . escapeshellarg($filename)));
            }
        }
        if ($file_mime_type && (empty($mime_type) || $mime_type == 'regular file' || $mime_type == 'text/plain')) {
            $mime_type = $file_mime_type;
        }
        // For each allowed MIME type, we are looking for it inside the current MIME type
        foreach ($mime_type_list as $type) {
            if (strstr($mime_type, (string) $type)) {
                return true;
            }
        }
        return false;
    }
    /**
     * Check if image file extension is correct
     *
     * @param string $filename Real filename
     * @param array|null $allowedExtensions
     *
     * @return bool True if it's correct
     */
    public static function is_correct_image_file_ext($filename, $allowed_extensions = null): bool
    {
        // Filter on file extension
        if ($allowed_extensions === null) {
            $allowed_extensions = static::get_allowed_image_extensions();
        }
        $extension = strtolower(pathinfo((string) $filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowed_extensions);
    }
    /**
     * Validate icon upload
     *
     * @param array $file Upload $_FILE value
     * @param int $maxFileSize Maximum upload size
     *
     * @return bool|string Return false if no error encountered
     */
    public static function validate_icon_upload(array $file, $max_file_size = 0)
    {
        if ((int) $max_file_size > 0 && $file['size'] > $max_file_size) {
            return sprintf(Tools::display_error('Image is too large (%1$d kB). Maximum allowed: %2$d kB'), $file['size'] / 1000, $max_file_size / 1000);
        }
        if (!str_ends_with((string) $file['name'], '.ico') && !str_ends_with((string) $file['name'], '.png')) {
            return Tools::display_error('Image format not recognized, allowed formats are: .ico, .png');
        }
        if ($file['error']) {
            return Tools::display_error('Error while uploading image; please change your server\'s settings.');
        }
        return false;
    }
    /**
     * Cut image
     *
     * @param string $srcFile Origin filename
     * @param string $dstFile Destination filename
     * @param int $dstWidth Desired width
     * @param int $dstHeight Desired height
     * @param string $imageExtension
     * @param int $dstX
     * @param int $dstY
     *
     * @return bool Operation result
     *
     * @throws PrestaShopException
     */
    public static function cut($src_file, $dst_file, $dst_width = null, $dst_height = null, $image_extension = null, $dst_x = 0, $dst_y = 0)
    {
        if (!file_exists($src_file)) {
            return false;
        }
        if (is_null($image_extension)) {
            $image_extension = Image_Manager::get_default_image_extension();
        }
        // Source information
        $src_info = getimagesize($src_file);
        $src = ['width' => $src_info[0], 'height' => $src_info[1], 'ressource' => Image_Manager::create($src_info[2], $src_file)];
        // Destination information
        $dest = [];
        $dest['x'] = $dst_x;
        $dest['y'] = $dst_y;
        $dest['width'] = !is_null($dst_width) ? $dst_width : $src['width'];
        $dest['height'] = !is_null($dst_height) ? $dst_height : $src['height'];
        $dest['ressource'] = Image_Manager::create_white_image($dest['width'], $dest['height']);
        $white = imagecolorallocate($dest['ressource'], 255, 255, 255);
        imagecopyresampled($dest['ressource'], $src['ressource'], 0, 0, $dest['x'], $dest['y'], $dest['width'], $dest['height'], $dest['width'], $dest['height']);
        imagecolortransparent($dest['ressource'], $white);
        $return = Image_Manager::write($image_extension, $dest['ressource'], $dst_file);
        @imagedestroy($src['ressource']);
        return $return;
    }
    /**
     * Create an empty image with white background
     *
     * @param int $width
     * @param int $height
     *
     * @return resource
     */
    public static function create_white_image($width, $height): \Gd_Image|false
    {
        $image = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
        return $image;
    }
    /**
     * Return the mime type by the file extension
     *
     * @param string $fileName
     *
     * @return string
     */
    public static function get_mime_type_by_extension($file_name)
    {
        $image_extension_infos = Media::get_file_informations('images');
        $image_extension = substr($file_name, strrpos($file_name, '.') + 1);
        $mime_type = null;
        foreach ($image_extension_infos as $image_extension_info) {
            if (in_array($image_extension, $image_extension_info)) {
                $mime_type = $image_extension_info['mimeType'];
                break;
            }
        }
        if ($mime_type === null) {
            return 'image/jpeg';
        }
        return $mime_type;
    }
    /**
     * Returns an array of image extensions depending on filters
     *
     * @param bool $returnMainExtensions Main extensions are for example, jpg, png, bmp, but NOT png-X or jpeg
     * @param null|bool $imageSupport Only returns image extensions, that can be generated by thirty bees (jpg, png, gif, webp)
     * @param null|bool $uploadFrontOffice Are customers allowed to upload this extension in FO?
     * @param null|bool $uploadBackOffice Are merchants allowed to upload this extension in BO?
     */
    public static function get_allowed_image_extensions($return_main_extensions = false, $image_support = null, $upload_front_office = null, $upload_back_office = null): array
    {
        $image_extensions = Media::get_file_informations('images');
        $return_helper = [];
        foreach ($image_extensions as $main_extension => $image_extension) {
            if ((is_null($image_support) || $image_support == $image_extension['imageSupport']) && (is_null($upload_front_office) || $upload_front_office == $image_extension['uploadFrontOffice']) && (is_null($upload_back_office) || $upload_back_office == $image_extension['uploadBackOffice'])) {
                if ($return_main_extensions) {
                    $return_helper[] = $main_extension;
                } else {
                    $return_helper = array_merge($return_helper, $image_extension['extensions']);
                }
            }
        }
        return $return_helper;
    }
    /**
     *
     * @return string returns either jpg|png|gif|webp
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_default_image_extension()
    {
        $image_extension = Configuration::get('TB_IMAGE_EXTENSION');
        if (!$image_extension) {
            return 'jpg';
        }
        return $image_extension;
    }
    /**
     * Add an image to the generator.
     *
     * This function adds a source image to the generator. It serves two main purposes: add a source image if one was
     * not supplied to the constructor and to add additional source images so that different images can be supplied for
     * different sized images in the resulting ICO file. For instance, a small source image can be used for the small
     * resolutions while a larger source image can be used for large resolutions.
     *
     * @param string $source Path to the source image file.
     * @param array $sizes Optional. An array of sizes (each size is an array with a width and height) that the source image should be rendered at in the generated ICO file. If sizes are not supplied, the size of the source image will be used.
     *
     * @return boolean true on success and false on failure.
     *
     * @copyright 2011-2016  Chris Jean
     * @author Chris Jean
     * @license GNU General Public License v2.0
     * @source https://github.com/chrisbliss18/php-ico
     */
    public static function generate_favicon($source, $sizes = [['16', '16'], ['24', '24'], ['32', '32'], ['48', '48'], ['64', '64']])
    {
        $images = [];
        if (!getimagesize($source)) {
            return false;
        }
        if (!$file_data = file_get_contents($source)) {
            return false;
        }
        if (!$im = imagecreatefromstring($file_data)) {
            return false;
        }
        unset($file_data);
        if (empty($sizes)) {
            $sizes = [imagesx($im), imagesy($im)];
        }
        // If just a single size was passed, put it in array.
        if (!is_array($sizes[0])) {
            $sizes = [$sizes];
        }
        foreach ((array) $sizes as $size) {
            [$width, $height] = $size;
            $width = (int) $width;
            $height = (int) $height;
            $new_im = imagecreatetruecolor($width, $height);
            imagecolortransparent($new_im, imagecolorallocatealpha($new_im, 0, 0, 0, 127));
            imagealphablending($new_im, false);
            imagesavealpha($new_im, true);
            $source_width = imagesx($im);
            $source_height = imagesy($im);
            if (false === imagecopyresampled($new_im, $im, 0, 0, 0, 0, $width, $height, $source_width, $source_height)) {
                continue;
            }
            static::add_favicon_image_data($new_im, $images);
        }
        return static::get_ico_data($images);
    }
    /**
     * Generate the final ICO data by creating a file header and adding the image data.
     *
     * @copyright 2011-2016  Chris Jean
     * @author Chris Jean
     * @license GNU General Public License v2.0
     * @source https://github.com/chrisbliss18/php-ico
     */
    protected static function get_ico_data($images): false|string
    {
        if (!is_array($images) || empty($images)) {
            return false;
        }
        $data = pack('vvv', 0, 1, count($images));
        $pixel_data = '';
        $icon_dir_entry_size = 16;
        $offset = 6 + $icon_dir_entry_size * count($images);
        foreach ($images as $image) {
            $data .= pack('CCCCvvVV', $image['width'], $image['height'], $image['color_palette_colors'], 0, 1, $image['bits_per_pixel'], $image['size'], $offset);
            $pixel_data .= $image['data'];
            $offset += $image['size'];
        }
        $data .= $pixel_data;
        unset($pixel_data);
        return $data;
    }
    /**
     * Take a GD image resource and change it into a raw BMP format.
     *
     * @copyright 2011-2016  Chris Jean
     * @author Chris Jean
     * @license GNU General Public License v2.0
     * @source https://github.com/chrisbliss18/php-ico
     */
    protected static function add_favicon_image_data($im, array &$images)
    {
        $width = imagesx($im);
        $height = imagesy($im);
        $pixel_data = [];
        $opacity_data = [];
        $current_opacity_val = 0;
        for ($y = $height - 1; $y >= 0; $y--) {
            for ($x = 0; $x < $width; $x++) {
                // get the raw color & alpha
                $color = imagecolorat($im, $x, $y);
                $raw_a = ($color & 0x7f000000) >> 24;
                // 0–127
                // compute fully‑scaled alpha and cast to int before shifting
                $alpha = (int) round((1 - $raw_a / 127) * 255);
                // 0–255
                // clear old alpha bits, then re‑insert ours
                $color &= 0xffffff;
                $color |= $alpha << 24;
                $pixel_data[] = $color;
                // build the 1‑bit opacity mask
                $opacity = $alpha <= 127 ? 1 : 0;
                $current_opacity_val = $current_opacity_val << 1 | $opacity;
                if (($x + 1) % 32 === 0) {
                    $opacity_data[] = $current_opacity_val;
                    $current_opacity_val = 0;
                }
            }
            // pad remaining bits in row to a multiple of 32
            if ($x % 32 > 0) {
                while ($x++ % 32 > 0) {
                    $current_opacity_val <<= 1;
                }
                $opacity_data[] = $current_opacity_val;
                $current_opacity_val = 0;
            }
        }
        // header sizes
        $image_header_size = 40;
        $color_mask_size = $width * $height * 4;
        $opacity_mask_size = ceil($width / 32) * 4 * $height;
        // build ICO directory header
        $data = pack(
            'VVVvvVVVVVV',
            40,
            // header size
            $width,
            $height * 2,
            // BMP stores height*2
            1,
            // planes
            32,
            // bits per pixel
            0,
            0,
            0,
            0,
            0,
            0
        );
        // append pixel data
        foreach ($pixel_data as $col) {
            $data .= pack('V', $col);
        }
        // append opacity mask
        foreach ($opacity_data as $mask) {
            $data .= pack('N', $mask);
        }
        $images[] = ['width' => $width, 'height' => $height, 'color_palette_colors' => 0, 'bits_per_pixel' => 32, 'size' => $image_header_size + $color_mask_size + $opacity_mask_size, 'data' => $data];
    }
    /**
     * Returns true, if webp images can be used for current request
     *
     * @return bool
     */
    public static function webp_support()
    {
        static $supported = null;
        if ($supported === null) {
            $supported = static::get_webp_preference() === static::USE_WEBP && static::server_supports_webp();
        }
        return $supported;
    }
    /**
     * Returns true, if browser that initiated request supports webp images
     *
     * @return bool
     */
    public static function browser_supports_webp()
    {
        if (array_key_exists('HTTP_ACCEPT', $_SERVER)) {
            return str_contains((string) $_SERVER['HTTP_ACCEPT'], 'image/webp');
        }
        return false;
    }
    /**
     * Returns true, if server supports webp images
     */
    public static function server_supports_webp(): bool
    {
        return function_exists('imagewebp');
    }
    /**
     * Returns true, if server supports avif images
     */
    public static function server_supports_avif(): bool
    {
        return function_exists('imageavif');
    }
    /**
     * Returns true, if webp images should be generated. That does not necessary mean that webp images
     * will be used by store
     *
     *
     * @deprecated 1.5.0 This specific webp function is obsolete, since we support webp consistently
     */
    public static function generate_webp_images(): bool
    {
        $preference = static::get_webp_preference();
        return $preference === static::USE_WEBP;
    }
    /**
     * Returns current webp settings preference
     *
     * @return int
     */
    protected static function get_webp_preference()
    {
        try {
            return (int) (Configuration::get('TB_IMAGE_EXTENSION') == 'webp');
        } catch (Presta_Shop_Exception) {
            return static::DO_NOT_USE_WEBP;
        }
    }
    /**
     * @return bool
     */
    public static function retina_support()
    {
        static $supported = null;
        if ($supported === null) {
            try {
                $supported = (bool) Configuration::get('PS_HIGHT_DPI');
            } catch (Presta_Shop_Exception) {
                $supported = false;
            }
        }
        return $supported;
    }
    /**
     * Important function to convert core image files in "wrong" format. Also needed when merchant switches image extension.
     * Typical example: orderStatus icons. Originally they are stored in gif, but in lists they are needed in configured image extension.
     *
     * @param string $image
     *
     * @return string|null Returns full path to source image
     *
     */
    public static function try_restore_image($image)
    {
        if (!$image) {
            return null;
        }
        if (@file_exists($image)) {
            return $image;
        }
        $base_source_path = pathinfo($image, PATHINFO_DIRNAME) . '/' . pathinfo($image, PATHINFO_FILENAME);
        foreach (Image_Manager::get_allowed_image_extensions(false, true) as $image_extension) {
            $source_path = $base_source_path . '.' . $image_extension;
            if (@file_exists($source_path)) {
                return $source_path;
            }
        }
        return null;
    }
    /**
     * Get a source file in any extension
     *
     * @param string $path // full folder path
     * @param string $filename // expected filename without any image extension (often this is just the $idEntity)
     * @param null $expectedImageExtension // Only set this, if you have clear idea in which extension the source file is available
     * @param bool $convertImage // If an image is not existing in the configured image extension, should we convert it?
     *
     * @return string|false Returns full path to source image
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_source_image($path, string $filename, $expected_image_extension = null, $convert_image = true)
    {
        $default_image_extension = Configuration::get('TB_IMAGE_EXTENSION');
        // First check default imageExtension (saving time)
        if (!$expected_image_extension) {
            $expected_image_extension = $default_image_extension;
        }
        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }
        // Check if image in expected extension is available (ideal situation)
        if (file_exists($path . $filename . '.' . $expected_image_extension)) {
            return $path . $filename . '.' . $expected_image_extension;
        }
        // Image is not available in the expected extension
        $source_image = false;
        $found_extension = false;
        foreach (Image_Manager::get_allowed_image_extensions(false, true) as $image_extension) {
            if ($image_extension != $expected_image_extension && file_exists($path . $filename . '.' . $image_extension)) {
                $source_image = $path . $filename . '.' . $image_extension;
                $found_extension = $image_extension;
                break;
            }
        }
        // Check if we should convert the found sourceImage
        if ($convert_image) {
            $image_conversion = Configuration::get('TB_IMAGE_CONVERSION');
            if ($found_extension && $found_extension != $default_image_extension && ($image_conversion == 'converted' || $image_conversion == 'both')) {
                $unlink_old_image = $image_conversion == 'converted' && $default_image_extension != Configuration::get('TB_IMAGE_EXTENSION');
                Image_Manager::convert_image_to_extension($source_image, $default_image_extension, '', $unlink_old_image);
            }
        }
        return $source_image;
    }
    /**
     * Removing unnecessary source image extensions
     *
     * @param string $path // full folder path
     * @param string $filename // expected filename without any image extension (often this is just the $idEntity)
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function clean_source_image($path, string $filename): void
    {
        if (!str_ends_with($path, '/')) {
            $path .= '/';
        }
        $image_conversion = Configuration::get('TB_IMAGE_CONVERSION');
        if ($image_conversion == 'converted') {
            // Making 100% sure, that source file is available in correct extension
            $source_file_to_hold = Image_Manager::get_source_image($path, $filename);
            foreach (Image_Manager::get_allowed_image_extensions(false, true) as $image_extension) {
                $source_file_to_check = $path . $filename . '.' . $image_extension;
                if ($source_file_to_check != $source_file_to_hold && $image_extension != Image_Manager::get_default_image_extension()) {
                    if (file_exists($source_file_to_check)) {
                        unlink($source_file_to_check);
                    }
                }
            }
        }
    }
    /**
     * Resolves valid image extension from filepath. File does not need to exits -- extension is extracted from name
     * only
     *
     *
     */
    protected static function get_image_extension_from_filename(string $filepath): ?string
    {
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        if ($extension) {
            $allowed_extensions = static::get_allowed_image_extensions(true, true);
            if (in_array($extension, $allowed_extensions)) {
                return $extension;
            }
        }
        return null;
    }
    /**
     * Resolves valid image extension from filepath. File have to exists - image extension is resolved from file content
     *
     * @return string|null
     */
    public static function get_image_extension(string $filepath)
    {
        $image_info = @getimagesize($filepath);
        if (!$image_info) {
            return null;
        }
        $mime_type = $image_info['mime'] ?? null;
        if (!$mime_type) {
            return null;
        }
        // Detect mime content type
        foreach (Media::get_file_informations('images') as $ext => $image_file_info) {
            if (strstr($mime_type, (string) $image_file_info['mimeType'])) {
                return $ext;
            }
        }
        return null;
    }
}