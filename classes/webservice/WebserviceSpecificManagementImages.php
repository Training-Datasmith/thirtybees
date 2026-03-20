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
 * Class WebserviceSpecificManagementImagesCore
 */
class Webservice_Specific_Management_Images_Core implements Webservice_Specific_Management_Interface
{
    /**
     * @var WebserviceOutputBuilder
     */
    protected $obj_output;
    /**
     * @var string
     */
    protected $output;
    /**
     * @var WebserviceRequest
     */
    protected $ws_object;
    /**
     * @var string The extension of the image to display
     */
    protected $img_extension;
    /**
     * @var array The type of images (general, categories, manufacturers, suppliers, stores...)
     */
    protected $image_types = ['general' => ['header' => [], 'mail' => [], 'invoice' => [], 'store_icon' => []], 'products' => [], 'categories' => [], 'manufacturers' => [], 'suppliers' => [], 'stores' => [], 'customizations' => []];
    /**
     * @var string The image type (product, category, general,...)
     */
    protected $image_type;
    /**
     * @var array The list of supported mime types
     */
    protected $accepted_img_mime_types = [];
    /**
     * @var string The product image declination id
     */
    protected $product_image_declination_id;
    /**
     * @var bool If the current image management has to manage a "default" image (i.e. "No product available")
     */
    protected $default_image = false;
    /**
     * @var string The file path of the image to display. If not null, the image will be displayed, even if the XML output was not empty
     */
    public $img_to_display;
    public function __construct()
    {
        foreach (Media::get_file_informations('images') as $type) {
            if ($type['imageSupport']) {
                $this->accepted_img_mime_types[] = $type['mimeType'];
            }
        }
    }
    /* ------------------------------------------------
     * GETTERS & SETTERS
     * ------------------------------------------------ */
    public function set_object_output(Webservice_Output_Builder_Core $obj): static
    {
        $this->obj_output = $obj;
        return $this;
    }
    /**
     * @return WebserviceOutputBuilder
     */
    public function get_object_output()
    {
        return $this->obj_output;
    }
    public function set_ws_object(Webservice_Request_Core $obj): static
    {
        $this->ws_object = $obj;
        return $this;
    }
    /**
     * @return WebserviceRequest
     */
    public function get_ws_object()
    {
        return $this->ws_object;
    }
    /**
     * @return string
     * @throws WebserviceException
     */
    public function get_content()
    {
        if ($this->output != '') {
            return $this->obj_output->get_object_render()->override_content($this->output);
        }
        // display image content if needed
        if ($this->img_to_display) {
            if (empty($this->img_extension)) {
                $imginfo = getimagesize($this->img_to_display);
                $this->img_extension = image_type_to_extension($imginfo[2], false);
            }
            $image_resource = false;
            $types = ['jpg' => ['function' => 'imagecreatefromjpeg', 'Content-Type' => 'image/jpeg'], 'jpeg' => ['function' => 'imagecreatefromjpeg', 'Content-Type' => 'image/jpeg'], 'png' => ['function' => 'imagecreatefrompng', 'Content-Type' => 'image/png'], 'gif' => ['function' => 'imagecreatefromgif', 'Content-Type' => 'image/gif']];
            if (Image_Manager::server_supports_webp()) {
                $types['webp'] = ['function' => 'imagecreatefromwebp', 'Content-Type' => 'image/webp'];
            }
            if (Image_Manager::server_supports_avif()) {
                $types['avif'] = ['function' => 'imagecreatefromavif', 'Content-Type' => 'image/avif'];
            }
            if (array_key_exists($this->img_extension, $types)) {
                $image_resource = @$types[$this->img_extension]['function']($this->img_to_display);
            }
            if (!$image_resource) {
                throw new Webservice_Exception(sprintf('Unable to load the image "%s"', str_replace(_PS_ROOT_DIR_, '[SHOP_ROOT_DIR]', $this->img_to_display)), [47, 500]);
            }
            if (array_key_exists($this->img_extension, $types)) {
                $this->obj_output->set_header_params('Content-Type', $types[$this->img_extension]['Content-Type']);
            }
            return file_get_contents($this->img_to_display);
        }
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    public function manage()
    {
        $this->manage_images();
        return $this->ws_object->get_output_enabled();
    }
    /**
     * Management of images URL segment
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    protected function manage_images()
    {
        /*
         * available cases api/... :
         *
         *   images ("types_list") (N-1)
         *   	GET    (xml)
         *   images/general ("general_list") (N-2)
         *   	GET    (xml)
         *   images/general/[header,+] ("general") (N-3)
         *   	GET    (bin)
         *   	PUT    (bin)
         *
         *
         *   images/[categories,+] ("normal_list") (N-2) ([categories,+] = categories, manufacturers, ...)
         *   	GET    (xml)
         *   images/[categories,+]/[1,+] ("normal") (N-3)
         *   	GET    (bin)
         *   	PUT    (bin)
         *   	DELETE
         *   	POST   (bin) (if image does not exists)
         *   images/[categories,+]/[1,+]/[small,+] ("normal_resized") (N-4)
         *   	GET    (bin)
         *   images/[categories,+]/default ("display_list_of_langs") (N-3)
         *   	GET    (xml)
         *   images/[categories,+]/default/[en,+] ("normal_default_i18n")  (N-4)
         *   	GET    (bin)
         *   	POST   (bin) (if image does not exists)
         *      PUT    (bin)
         *      DELETE
         *   images/[categories,+]/default/[en,+]/[small,+] ("normal_default_i18n_resized")  (N-5)
         *   	GET    (bin)
         *
         *   images/product ("product_list")  (N-2)
         *   	GET    (xml) (list of image)
         *   images/product/[1,+] ("product_description")  (N-3)
         *   	GET    (xml) (legend, declinations, xlink to images/product/[1,+]/bin)
         *   images/product/[1,+]/bin ("product_bin")  (N-4)
         *   	GET    (bin)
         *      POST   (bin) (if image does not exists)
         *   images/product/[1,+]/[1,+] ("product_declination")  (N-4)
         *   	GET    (bin)
         *   	POST   (xml) (legend)
         *   	PUT    (xml) (legend)
         *      DELETE
         *   images/product/[1,+]/[1,+]/bin ("product_declination_bin") (N-5)
         *   	POST   (bin) (if image does not exists)
         *   	GET    (bin)
         *   	PUT    (bin)
         *   images/product/[1,+]/[1,+]/[small,+] ("product_declination_resized") (N-5)
         *   	GET    (bin)
         *   images/product/default ("product_default") (N-3)
         *   	GET    (bin)
         *   images/product/default/[en,+] ("product_default_i18n") (N-4)
         *   	GET    (bin)
         *      POST   (bin)
         *      PUT   (bin)
         *      DELETE
         *   images/product/default/[en,+]/[small,+] ("product_default_i18n_resized") (N-5)
         * 		GET    (bin)
         *
         */
        /* Declinated
         *ok    GET    (bin)
         *ok images/product ("product_list")  (N-2)
         *ok	GET    (xml) (list of image)
         *ok images/product/[1,+] ("product_description")  (N-3)
         *   	GET    (xml) (legend, declinations, xlink to images/product/[1,+]/bin)
         *ok images/product/[1,+]/bin ("product_bin")  (N-4)
         *ok 	GET    (bin)
         *      POST   (bin) (if image does not exists)
         *ok images/product/[1,+]/[1,+] ("product_declination")  (N-4)
         *ok 	GET    (bin)
         *   	POST   (xml) (legend)
         *   	PUT    (xml) (legend)
         *      DELETE
         *ok images/product/[1,+]/[1,+]/bin ("product_declination_bin") (N-5)
         *   	POST   (bin) (if image does not exists)
         *ok 	GET    (bin)
         *   	PUT    (bin)
         *   images/product/[1,+]/[1,+]/[small,+] ("product_declination_resized") (N-5)
         *ok 	GET    (bin)
         *ok images/product/default ("product_default") (N-3)
         *ok 	GET    (bin)
         *ok images/product/default/[en,+] ("product_default_i18n") (N-4)
         *ok 	GET    (bin)
         *      POST   (bin)
         *      PUT   (bin)
         *      DELETE
         *ok images/product/default/[en,+]/[small,+] ("product_default_i18n_resized") (N-5)
         *ok	GET    (bin)
         *
         */
        // Pre configuration...
        if (isset($this->ws_object->url_segment)) {
            for ($i = 1; $i < 6; $i++) {
                if (count($this->ws_object->url_segment) == $i) {
                    $this->ws_object->url_segment[$i] = '';
                }
            }
        }
        $this->image_type = $this->ws_object->url_segment[1];
        switch ($this->image_type) {
            // general images management : like header's logo, invoice logo, etc...
            case 'general':
                return $this->manage_general_images();
            // normal images management : like the most entity images (categories, manufacturers..)...
            case 'categories':
                return $this->manage_declinated_images(_PS_CAT_IMG_DIR_);
            case 'manufacturers':
                return $this->manage_declinated_images(_PS_MANU_IMG_DIR_);
            case 'suppliers':
                return $this->manage_declinated_images(_PS_SUPP_IMG_DIR_);
            case 'stores':
                return $this->manage_declinated_images(_PS_STORE_IMG_DIR_);
            // product image management : many image for one entity (product)
            case 'products':
                return $this->manage_product_images();
            case 'customizations':
                return $this->manage_customization_images();
            // images root node management : many image for one entity (product)
            case '':
                $this->output .= $this->obj_output->get_object_render()->render_node_header('image_types', []);
                foreach (array_keys($this->image_types) as $image_type_name) {
                    $more_attr = ['xlink_resource' => $this->ws_object->ws_url . $this->ws_object->url_segment[0] . '/' . $image_type_name, 'get' => 'true', 'put' => 'false', 'post' => 'false', 'delete' => 'false', 'head' => 'true', 'upload_allowed_mimetypes' => implode(', ', $this->accepted_img_mime_types)];
                    $this->output .= $this->obj_output->get_object_render()->render_node_header($image_type_name, [], $more_attr, false);
                }
                $this->output .= $this->obj_output->get_object_render()->render_node_footer('image_types', []);
                return true;
            default:
                $exception = new Webservice_Exception(sprintf('Image of type "%s" does not exist', $this->ws_object->url_segment[1]), [48, 400]);
                throw $exception->set_did_you_mean($this->ws_object->url_segment[1], array_keys($this->image_types));
        }
    }
    /**
     * Management of general images
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    protected function manage_general_images()
    {
        $alternative_path = '';
        switch ($this->ws_object->url_segment[2]) {
            // Set the image path on display in relation to the header image
            case 'header':
                if (in_array($this->ws_object->method, ['GET', 'HEAD', 'PUT'])) {
                    $path = _PS_IMG_DIR_ . Configuration::get('PS_LOGO');
                } else {
                    throw new Webservice_Exception('This method is not allowed with general image resources.', [49, 405]);
                }
                break;
            // Set the image path on display in relation to the mail image
            case 'mail':
                if (in_array($this->ws_object->method, ['GET', 'HEAD', 'PUT'])) {
                    $path = _PS_IMG_DIR_ . Configuration::get('PS_LOGO_MAIL');
                    $alternative_path = _PS_IMG_DIR_ . Configuration::get('PS_LOGO');
                } else {
                    throw new Webservice_Exception('This method is not allowed with general image resources.', [50, 405]);
                }
                break;
            // Set the image path on display in relation to the invoice image
            case 'invoice':
                if (in_array($this->ws_object->method, ['GET', 'HEAD', 'PUT'])) {
                    $path = _PS_IMG_DIR_ . Configuration::get('PS_LOGO_INVOICE');
                    $alternative_path = _PS_IMG_DIR_ . Configuration::get('PS_LOGO');
                } else {
                    throw new Webservice_Exception('This method is not allowed with general image resources.', [51, 405]);
                }
                break;
            // Set the image path on display in relation to the icon store image
            case 'store_icon':
                if (in_array($this->ws_object->method, ['GET', 'HEAD', 'PUT'])) {
                    $path = _PS_IMG_DIR_ . Configuration::get('PS_STORES_ICON');
                    $this->img_extension = 'gif';
                } else {
                    throw new Webservice_Exception('This method is not allowed with general image resources.', [52, 405]);
                }
                break;
            // List the general image types
            case '':
                $this->output .= $this->obj_output->get_object_render()->render_node_header('general_image_types', []);
                foreach (array_keys($this->image_types['general']) as $general_image_type_name) {
                    $more_attr = ['xlink_resource' => $this->ws_object->ws_url . $this->ws_object->url_segment[0] . '/' . $this->ws_object->url_segment[1] . '/' . $general_image_type_name, 'get' => 'true', 'put' => 'true', 'post' => 'false', 'delete' => 'false', 'head' => 'true', 'upload_allowed_mimetypes' => implode(', ', $this->accepted_img_mime_types)];
                    $this->output .= $this->obj_output->get_object_render()->render_node_header($general_image_type_name, [], $more_attr, false);
                }
                $this->output .= $this->obj_output->get_object_render()->render_node_footer('general_image_types', []);
                return true;
            // If the image type does not exist...
            default:
                $exception = new Webservice_Exception(sprintf('General image of type "%s" does not exist', $this->ws_object->url_segment[2]), [53, 400]);
                throw $exception->set_did_you_mean($this->ws_object->url_segment[2], array_keys($this->image_types['general']));
        }
        // The general image type is valid, now we try to do action in relation to the method
        switch ($this->ws_object->method) {
            case 'GET':
            case 'HEAD':
                $this->img_to_display = $path != '' && file_exists($path) && is_file($path) ? $path : $alternative_path;
                return true;
            case 'PUT':
                if ($this->write_posted_image_on_disk($path)) {
                    if ($this->ws_object->url_segment[2] == 'header') {
                        $logo_name = Configuration::get('PS_LOGO') ?: 'logo.jpg';
                        [$width, $height] = getimagesize(_PS_IMG_DIR_ . $logo_name);
                        Configuration::update_value('SHOP_LOGO_WIDTH', (int) round($width));
                        Configuration::update_value('SHOP_LOGO_HEIGHT', (int) round($height));
                    }
                    $this->img_to_display = $path;
                    return true;
                }
                throw new Webservice_Exception('Error while copying image to the directory', [54, 400]);
        }
    }
    /**
     * @param array[] $normalImageSizes
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    protected function manage_default_declinated_images(string $directory, $normal_image_sizes)
    {
        $this->default_image = true;
        $lang_list = Language::get_iso_ids(true);
        // Display list of languages
        if ($this->ws_object->url_segment[3] == '' && $this->ws_object->method == 'GET') {
            $this->output .= $this->obj_output->get_object_render()->render_node_header('languages', []);
            foreach ($lang_list as $lang) {
                $more_attr = ['xlink_resource' => $this->ws_object->ws_url . 'images/' . $this->image_type . '/default/' . $lang['iso_code'], 'get' => 'true', 'put' => 'true', 'post' => 'true', 'delete' => 'true', 'head' => 'true', 'upload_allowed_mimetypes' => implode(', ', $this->accepted_img_mime_types), 'iso' => $lang['iso_code']];
                $this->output .= $this->obj_output->get_object_render()->render_node_header('language', [], $more_attr, false);
            }
            $this->output .= $this->obj_output->get_object_render()->render_node_footer('languages', []);
            return true;
        }
        $lang_iso = $this->ws_object->url_segment[3];
        $image_size = $this->ws_object->url_segment[4];
        $image_extension = $this->get_image_extension();
        if ($image_size != '') {
            $this->check_image_size_exits($normal_image_sizes, $image_size);
            $filename = $directory . $lang_iso . '-default-' . $image_size . '.' . $image_extension;
        } else {
            $filename = $directory . $lang_iso . '-default.' . $image_extension;
        }
        $filename_exists = file_exists($filename);
        if (!$filename_exists && $source = Image_Manager::get_source_image($directory, $lang_iso . '-default')) {
            if ($image_size) {
                Language::regenerate_default_images($lang_iso, $image_extension);
            } else {
                Image_Manager::convert_image_to_extension($source, $image_extension, $filename);
            }
            $filename_exists = file_exists($filename);
        }
        return $this->manage_declinated_images_crud($filename_exists, $filename, $normal_image_sizes, $directory);
    }
    /**
     * @param string $directory
     * @param array $normalImageSizes
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    protected function manage_list_declinated_images($directory, $normal_image_sizes): bool
    {
        // Check if method is allowed
        if ($this->ws_object->method != 'GET') {
            throw new Webservice_Exception('This method is not allowed for listing category images.', [55, 405]);
        }
        $this->output .= $this->obj_output->get_object_render()->render_node_header('image_types', []);
        foreach ($normal_image_sizes as $image_size) {
            $this->output .= $this->obj_output->get_object_render()->render_node_header('image_type', [], ['id' => $image_size['id_image_type'], 'name' => $image_size['name'], 'xlink_resource' => $this->ws_object->ws_url . 'image_types/' . $image_size['id_image_type']], false);
        }
        $this->output .= $this->obj_output->get_object_render()->render_node_footer('image_types', []);
        $this->output .= $this->obj_output->get_object_render()->render_node_header('images', []);
        if ($this->image_type == Image_Entity::ENTITY_TYPE_PRODUCTS) {
            $ids = [];
            $images = Image::get_all_images();
            foreach ($images as $image) {
                $ids[] = $image['id_product'];
            }
            $ids = array_unique($ids, SORT_NUMERIC);
            asort($ids);
            foreach ($ids as $id) {
                $this->output .= $this->obj_output->get_object_render()->render_node_header('image', [], ['id' => $id, 'xlink_resource' => $this->ws_object->ws_url . 'images/' . $this->image_type . '/' . $id], false);
            }
        } else {
            $nodes = scandir($directory);
            foreach ($nodes as $node) {
                // avoid too much preg_match...
                if ($node != '.' && $node != '..' && $node != '.svn') {
                    preg_match('/^(\d+)\.jpg*$/Ui', $node, $matches);
                    if (isset($matches[1])) {
                        $id = $matches[1];
                        $this->output .= $this->obj_output->get_object_render()->render_node_header('image', [], ['id' => $id, 'xlink_resource' => $this->ws_object->ws_url . 'images/' . $this->image_type . '/' . $id], false);
                    }
                }
            }
        }
        $this->output .= $this->obj_output->get_object_render()->render_node_footer('images', []);
        return true;
    }
    /**
     * @param array[] $normalImageSizes
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    protected function manage_entity_declinated_images(string $directory, $normal_image_sizes)
    {
        // If id is detected
        $object_id = $this->ws_object->url_segment[2];
        if (!Validate::is_unsigned_id($object_id)) {
            throw new Webservice_Exception('The image id is invalid. Please set a valid id or the "default" value', [60, 400]);
        }
        $ext = $this->get_image_extension();
        // For the product case
        if ($this->image_type == Image_Entity::ENTITY_TYPE_PRODUCTS) {
            $image_id = $this->ws_object->url_segment[3];
            $image_size = $this->ws_object->url_segment[4];
            $product = new Product($object_id);
            if (!Validate::is_loaded_object($product)) {
                throw new Webservice_Exception('This product id does not exist', [57, 400]);
            }
            // Get available image ids
            $available_image_ids = array_column($product->get_ws_images(), 'id');
            // If an image id is specified
            if ($image_id != '') {
                if ($image_id == 'bin') {
                    $current_product = new Product($object_id);
                    $image_id = (int) $current_product->get_cover_ws();
                }
                if (!in_array($image_id, $available_image_ids)) {
                    throw new Webservice_Exception('This image id does not exist', [57, 400]);
                }
                $path = implode('/', str_split((string) $image_id));
                $image_type_suffix = $image_size ? '-' . $image_size : '';
                $filename = $directory . $path . '/' . $image_id . $image_type_suffix . '.' . $ext;
                $orig_filename = Image_Manager::get_source_image($directory . $path, $image_id);
            } elseif ($this->ws_object->method == 'GET' || $this->ws_object->method == 'HEAD') {
                // display the list of declinated images
                if ($available_image_ids) {
                    $this->output .= $this->obj_output->get_object_render()->render_node_header('image', [], ['id' => $object_id]);
                    foreach ($available_image_ids as $available_image_id) {
                        $this->output .= $this->obj_output->get_object_render()->render_node_header('declination', [], ['id' => $available_image_id, 'xlink_resource' => $this->ws_object->ws_url . 'images/' . $this->image_type . '/' . $object_id . '/' . $available_image_id], false);
                    }
                    $this->output .= $this->obj_output->get_object_render()->render_node_footer('image', []);
                } else {
                    $this->obj_output->set_status(404);
                    $this->ws_object->set_output_enabled(false);
                }
                return true;
            } else {
                return $this->manage_declinated_images_crud(false, '', $normal_image_sizes, $directory);
            }
        } else {
            // for all other cases
            $image_size = $this->ws_object->url_segment[3];
            $image_type_suffix = $image_size ? '-' . $image_size : '';
            $orig_filename = Image_Manager::get_source_image($directory, $object_id);
            $filename = $directory . $object_id . $image_type_suffix . '.' . $ext;
        }
        // request for specific image type
        if ($image_size) {
            $this->check_image_size_exits($normal_image_sizes, $image_size);
            if (!file_exists($filename) && $orig_filename) {
                $formatted_name = Image_Type::get_formated_name($image_size);
                $image_type = Image_Type::get_instance_by_name($formatted_name);
                Image_Manager::resize($orig_filename, $filename, $image_type->width, $image_type->height, $ext);
            }
            return $this->set_img_to_display($filename);
        }
        // request for source image
        if ($orig_filename) {
            if (!file_exists($filename)) {
                // convert source image
                Image_Manager::convert_image_to_extension($orig_filename, $ext, $filename);
            }
            return $this->set_img_to_display($filename);
        }
        return $this->manage_declinated_images_crud(false, '', $normal_image_sizes, $directory);
    }
    /**
     * @throws WebserviceException
     */
    protected function set_img_to_display(string $filename): bool
    {
        if (!file_exists($filename)) {
            throw new Webservice_Exception('This image does not exist on disk', [59, 500]);
        }
        $this->img_to_display = $filename;
        return true;
    }
    /**
     * Management of normal images (as categories, suppliers, manufacturers and stores)
     *
     * @param string $directory the file path of the root of the images folder type
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    protected function manage_declinated_images($directory)
    {
        // Get available image sizes for the current image type
        $normal_image_sizes = Image_Type::get_images_types($this->image_type);
        return match ($this->ws_object->url_segment[2]) {
            'default' => $this->manage_default_declinated_images(_PS_LANG_IMG_DIR_, $normal_image_sizes),
            '' => $this->manage_list_declinated_images($directory, $normal_image_sizes),
            default => $this->manage_entity_declinated_images($directory, $normal_image_sizes),
        };
    }
    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    protected function manage_product_images()
    {
        return $this->manage_declinated_images(_PS_PROD_IMG_DIR_);
    }
    /**
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function get_customizations(): array
    {
        $customizations = [];
        if (!$results = Db::read_only()->get_array('
			SELECT DISTINCT c.`id_customization`
			FROM `' . _DB_PREFIX_ . 'customization` c
			NATURAL JOIN `' . _DB_PREFIX_ . 'customization_field` cf
			WHERE c.`id_cart` = ' . (int) $this->ws_object->url_segment[2] . '
			AND type = 0')) {
            return [];
        }
        foreach ($results as $result) {
            $customizations[] = $result['id_customization'];
        }
        return $customizations;
    }
    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    protected function manage_customization_images()
    {
        $normal_image_sizes = Image_Type::get_images_types($this->image_type);
        $connection = Db::read_only();
        if (empty($this->ws_object->url_segment[2])) {
            $results = $connection->get_array('SELECT DISTINCT `id_cart` FROM `' . _DB_PREFIX_ . 'customization`');
            $ids = [];
            foreach ($results as $result) {
                $ids[] = $result['id_cart'];
            }
            asort($ids);
            $this->output .= $this->obj_output->get_object_render()->render_node_header('carts', []);
            foreach ($ids as $id) {
                $this->output .= $this->obj_output->get_object_render()->render_node_header('cart', [], ['id' => $id, 'xlink_resource' => $this->ws_object->ws_url . 'images/' . $this->image_type . '/' . $id], false);
            }
            $this->output .= $this->obj_output->get_object_render()->render_node_footer('carts', []);
            return true;
        }
        if (empty($this->ws_object->url_segment[3])) {
            $this->output .= $this->obj_output->get_object_render()->render_node_header('customizations', []);
            $customizations = $this->get_customizations();
            foreach ($customizations as $id) {
                $this->output .= $this->obj_output->get_object_render()->render_node_header('customization', [], ['id' => $id, 'xlink_resource' => $this->ws_object->ws_url . 'images/' . $this->image_type . '/' . $id], false);
            }
            $this->output .= $this->obj_output->get_object_render()->render_node_footer('customizations', []);
            return true;
        }
        if (empty($this->ws_object->url_segment[4])) {
            if ($this->ws_object->method == 'GET') {
                $results = $connection->get_array((new Db_Query())->select('*')->from('customized_data')->where('`id_customization` = ' . (int) $this->ws_object->url_segment[3])->where('`type` = 0'));
                $this->output .= $this->obj_output->get_object_render()->render_node_header('images', []);
                foreach ($results as $result) {
                    $this->output .= $this->obj_output->get_object_render()->render_node_header('image', [], ['id' => $result['index'], 'xlink_resource' => $this->ws_object->ws_url . 'images/' . $this->image_type . '/' . $result['index']], false);
                }
                $this->output .= $this->obj_output->get_object_render()->render_node_footer('images', []);
                return true;
            }
        } else {
            if ($this->ws_object->method == 'GET') {
                $results = $connection->get_array((new Db_Query())->select('*')->from('customized_data')->where('`id_customization` = ' . (int) $this->ws_object->url_segment[3])->where('`index` = ' . (int) $this->ws_object->url_segment[4]));
                if (empty($results[0]) || empty($results[0]['value'])) {
                    throw new Webservice_Exception('This image does not exist on disk', [61, 500]);
                }
                $this->img_to_display = _PS_UPLOAD_DIR_ . $results[0]['value'];
                return true;
            }
            if ($this->ws_object->method == 'POST') {
                $customizations = $this->get_customizations();
                if (!in_array((int) $this->ws_object->url_segment[3], $customizations)) {
                    throw new Webservice_Exception('Customization does not exist', [61, 500]);
                }
                $results = $connection->get_array((new Db_Query())->select('`id_customization_field`')->from('customization_field')->where('`id_customization_field` = ' . (int) $this->ws_object->url_segment[4])->where('`type` = 0'));
                if (empty($results)) {
                    throw new Webservice_Exception('Customization field does not exist.', [61, 500]);
                }
                $results = $connection->get_array((new Db_Query())->select('*')->from('customized_data')->where('`id_customization` = ' . (int) $this->ws_object->url_segment[3])->where('`index` = ' . (int) $this->ws_object->url_segment[4])->where('`type` = 0'));
                if (!empty($results)) {
                    // customization field exists and has no value
                    throw new Webservice_Exception('Customization field already have a value, please use PUT method.', [61, 500]);
                }
                return $this->manage_declinated_images_crud(false, '', $normal_image_sizes, _PS_UPLOAD_DIR_);
            }
            $results = $connection->get_array((new Db_Query())->select('*')->from('customized_data')->where('`id_customization` = ' . (int) $this->ws_object->url_segment[3])->where('`index` = ' . (int) $this->ws_object->url_segment[4]));
            if (empty($results[0]) || empty($results[0]['value'])) {
                throw new Webservice_Exception('This image does not exist on disk', [61, 500]);
            }
            $this->img_to_display = _PS_UPLOAD_DIR_ . $results[0]['value'];
            $filename_exists = file_exists($this->img_to_display);
            return $this->manage_declinated_images_crud($filename_exists, $this->img_to_display, $normal_image_sizes, _PS_UPLOAD_DIR_);
        }
    }
    /**
     * Management of normal images CRUD
     *
     * @param bool $filenameExists if the filename exists
     * @param string $filename the image path
     * @param array[] $imageSizes
     * @param string $directory
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    protected function manage_declinated_images_crud($filename_exists, $filename, $image_sizes, $directory)
    {
        switch ($this->ws_object->method) {
            // Display the image
            case 'GET':
            case 'HEAD':
                if ($filename_exists) {
                    $this->img_to_display = $filename;
                } else {
                    throw new Webservice_Exception('This image does not exist on disk', [61, 500]);
                }
                break;
            // Modify the image
            case 'PUT':
                if ($filename_exists) {
                    if ($this->write_posted_image_on_disk($filename, null, null, $image_sizes, $directory)) {
                        $this->img_to_display = $filename;
                        return true;
                    }
                    throw new Webservice_Exception('Unable to save this image.', [62, 500]);
                }
                throw new Webservice_Exception('This image does not exist on disk', [63, 500]);
            // Delete the image
            case 'DELETE':
                // Delete products image in DB
                if ($this->image_type == Image_Entity::ENTITY_TYPE_PRODUCTS) {
                    $image = new Image((int) $this->ws_object->url_segment[3]);
                    return $image->delete();
                }
                // Delete products image in DB
                if ($filename_exists) {
                    if (in_array($this->image_type, ['categories', 'manufacturers', 'suppliers', 'stores'])) {
                        /** @var ObjectModel $object */
                        $image_class = $this->ws_object->resource_list[$this->image_type]['class'];
                        $object = new $image_class((int) $this->ws_object->url_segment[2]);
                        return $object->delete_image();
                    }
                    return $this->delete_image_on_disk($filename, $image_sizes, $directory);
                }
                throw new Webservice_Exception('This image does not exist on disk', [64, 500]);
            // Add the image
            case 'POST':
                if ($filename_exists) {
                    throw new Webservice_Exception('This image already exists. To modify it, please use the PUT method', [65, 400]);
                }
                if ($this->write_posted_image_on_disk($filename, null, null, $image_sizes, $directory)) {
                    return true;
                }
                throw new Webservice_Exception('Unable to save this image', [66, 500]);
            default:
                throw new Webservice_Exception('This method is not allowed', [67, 405]);
        }
    }
    /**
     *    Delete the image on disk
     *
     * @param string $filePath the image file path
     * @param array $imageTypes The different sizes
     * @param string $parentPath The parent path
     */
    protected function delete_image_on_disk($file_path, $image_types = null, $parent_path = null): bool
    {
        $this->ws_object->set_output_enabled(false);
        if (file_exists($file_path)) {
            // delete image on disk
            @unlink($file_path);
            // Delete declinated image if needed
            $image_extension = explode('.', $file_path)[1];
            if ($image_types) {
                foreach ($image_types as $image_type) {
                    if ($this->default_image) {
                        // @todo products images too !!
                        $declination_path = $parent_path . $this->ws_object->url_segment[3] . '-default-' . $image_type['name'] . '.' . $image_extension;
                    } else {
                        $declination_path = $parent_path . $this->ws_object->url_segment[2] . '-' . $image_type['name'] . '.' . $image_extension;
                    }
                    if (!@unlink($declination_path)) {
                        $this->obj_output->set_status(204);
                        return false;
                    }
                }
            }
            return true;
        }
        $this->obj_output->set_status(204);
        return false;
    }
    /**
     * Write the image on disk
     *
     * @param string $basePath
     * @param int|null $destWidth
     * @param int|null $destHeight
     * @param array[]|null $imageTypes
     * @param string|null $parentPath
     *
     *
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    protected function write_image_on_disk($base_path, string $new_path, $dest_width = null, $dest_height = null, $image_types = null, $parent_path = null): string
    {
        [$source_width, $source_height, $type] = getimagesize($base_path);
        if (!$source_width) {
            throw new Webservice_Exception('Image width was null', [68, 400]);
        }
        if ($dest_width == null) {
            $dest_width = $source_width;
        }
        if ($dest_height == null) {
            $dest_height = $source_height;
        }
        $source_image = Image_Manager::create($type, $base_path);
        if (!$source_image) {
            throw new Webservice_Exception('Failed to create image', [69, 500]);
        }
        $width_diff = $dest_width / $source_width;
        $height_diff = $dest_height / $source_height;
        if ($width_diff > 1 && $height_diff > 1) {
            $next_width = $source_width;
            $next_height = $source_height;
        } else if ((int) Configuration::get('PS_IMAGE_GENERATION_METHOD') == 2 || (int) Configuration::get('PS_IMAGE_GENERATION_METHOD') == 0 && $width_diff > $height_diff) {
            $next_height = $dest_height;
            $next_width = (int) ($source_width * $next_height / $source_height);
            $dest_width = (int) Configuration::get('PS_IMAGE_GENERATION_METHOD') == 0 ? $dest_width : $next_width;
        } else {
            $next_width = $dest_width;
            $next_height = (int) ($source_height * $dest_width / $source_width);
            $dest_height = (int) Configuration::get('PS_IMAGE_GENERATION_METHOD') == 0 ? $dest_height : $next_height;
        }
        $border_width = (int) (($dest_width - $next_width) / 2);
        $border_height = (int) (($dest_height - $next_height) / 2);
        // Build the image
        if (!($dest_image = imagecreatetruecolor($dest_width, $dest_height)) || !($white = imagecolorallocate($dest_image, 255, 255, 255)) || !imagefill($dest_image, 0, 0, $white) || !imagecopyresampled($dest_image, $source_image, $border_width, $border_height, 0, 0, $next_width, $next_height, $source_width, $source_height) || !imagecolortransparent($dest_image, $white)) {
            throw new Webservice_Exception(sprintf('Unable to build the image "%s".', str_replace(_PS_ROOT_DIR_, '[SHOP_ROOT_DIR]', $new_path)), [69, 500]);
        }
        // Write it on disk
        $imaged = Image_Manager::write($this->img_extension, $dest_image, $new_path);
        if ($this->ws_object->url_segment[1] == 'customizations') {
            // write smaller image in case of customization image
            $product_picture_width = (int) Configuration::get('PS_PRODUCT_PICTURE_WIDTH');
            $product_picture_height = (int) Configuration::get('PS_PRODUCT_PICTURE_HEIGHT');
            if (!Image_Manager::resize($new_path, $new_path . '_small', $product_picture_width, $product_picture_height)) {
                throw new Webservice_Exception(Tools::display_error('An error occurred during the image upload process.'), [70, 500]);
            }
        }
        imagedestroy($dest_image);
        if (!$imaged) {
            throw new Webservice_Exception(sprintf('Unable to write the image "%s".', str_replace(_PS_ROOT_DIR_, '[SHOP_ROOT_DIR]', $new_path)), [70, 500]);
        }
        // Write image declinations if present
        if ($image_types && is_string($image_types)) {
            $image_types = [$image_types];
        }
        if (is_array($image_types)) {
            $image_extension = $this->get_image_extension();
            foreach ($image_types as $image_type) {
                if ($this->default_image) {
                    $declination_path = $parent_path . $this->ws_object->url_segment[3] . '-default-' . $image_type['name'] . '.' . $image_extension;
                } else if ($this->image_type == Image_Entity::ENTITY_TYPE_PRODUCTS) {
                    $declination_path = $parent_path . chunk_split((string) $this->ws_object->url_segment[3], 1, '/') . $this->ws_object->url_segment[3] . '-' . $image_type['name'] . '.' . $image_extension;
                } else {
                    $declination_path = $parent_path . $this->ws_object->url_segment[2] . '-' . $image_type['name'] . '.' . $image_extension;
                }
                if (!$this->write_image_on_disk($base_path, $declination_path, $image_type['width'], $image_type['height'])) {
                    throw new Webservice_Exception(sprintf('Unable to save the declination "%s" of this image.', $image_type['name']), [71, 500]);
                }
            }
        }
        Hook::trigger_event('actionWatermark', ['id_image' => $this->ws_object->url_segment[3], 'id_product' => $this->ws_object->url_segment[2]]);
        return $new_path;
    }
    /**
     * Write the posted image on disk
     *
     * @param string $receptionPath
     * @param int|null $destWidth
     * @param int|null $destHeight
     * @param array[]|null $imageTypes
     * @param string|null $parentPath
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    protected function write_posted_image_on_disk($reception_path, $dest_width = null, $dest_height = null, $image_types = null, $parent_path = null)
    {
        $img_max_upload_size = Tools::get_max_upload_size();
        if ($this->ws_object->method == 'PUT') {
            if (isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name']) {
                $file = $_FILES['image'];
                if ($file['size'] > $img_max_upload_size) {
                    throw new Webservice_Exception(sprintf('The image size is too large (maximum allowed is %d KB)', $img_max_upload_size / 1000), [72, 400]);
                }
                // Get mime content type
                $mime_type = false;
                if (Tools::is_callable('finfo_open')) {
                    $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
                    $finfo = finfo_open($const);
                    $mime_type = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                } elseif (Tools::is_callable('mime_content_type')) {
                    $mime_type = mime_content_type($file['tmp_name']);
                } elseif (Tools::is_callable('exec')) {
                    $mime_type = trim(exec('file -b --mime-type ' . escapeshellarg((string) $file['tmp_name'])));
                }
                if (empty($mime_type) || $mime_type == 'regular file') {
                    $mime_type = $file['type'];
                }
                if (($pos = strpos((string) $mime_type, ';')) !== false) {
                    $mime_type = substr((string) $mime_type, 0, $pos);
                }
                // Check mime content type
                if (!$mime_type || !in_array($mime_type, $this->accepted_img_mime_types)) {
                    throw new Webservice_Exception('This type of image format is not recognized, allowed formats are: ' . implode('", "', $this->accepted_img_mime_types), [73, 400]);
                }
                // Check mime content type
                if ($file['error']) {
                    throw new Webservice_Exception('Error while uploading image. Please change your server\'s settings', [74, 400]);
                }
                // Try to copy image file to a temporary file
                if (!($tmp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES['image']['tmp_name'], $tmp_name)) {
                    throw new Webservice_Exception('Error while copying image to the temporary directory', [75, 400]);
                }
                $result = $this->write_image_on_disk($tmp_name, $reception_path, $dest_width, $dest_height, $image_types, $parent_path);
                @unlink($tmp_name);
                return $result;
            }
            throw new Webservice_Exception('Please set an "image" parameter with image data for value', [76, 400]);
        }
        if ($this->ws_object->method == 'POST') {
            if (isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name']) {
                $file = $_FILES['image'];
                if ($file['size'] > $img_max_upload_size) {
                    throw new Webservice_Exception(sprintf('The image size is too large (maximum allowed is %d KB)', $img_max_upload_size / 1000), [72, 400]);
                }
                require_once _PS_CORE_DIR_ . '/images.inc.php';
                if ($error = Image_Manager::validate_upload($file)) {
                    throw new Webservice_Exception('Image upload error : ' . $error, [76, 400]);
                }
                if (isset($file['tmp_name']) && $file['tmp_name'] != null) {
                    if ($this->image_type == Image_Entity::ENTITY_TYPE_PRODUCTS) {
                        $product = new Product((int) $this->ws_object->url_segment[2]);
                        if (!Validate::is_loaded_object($product)) {
                            throw new Webservice_Exception('Product ' . (int) $this->ws_object->url_segment[2] . ' does not exist', [76, 400]);
                        }
                        $image = new Image();
                        $image->id_product = (int) $product->id;
                        $image->position = Image::get_highest_position($product->id) + 1;
                        if (!Image::get_cover((int) $product->id)) {
                            $image->cover = 1;
                        } else {
                            $image->cover = 0;
                        }
                        if (!$image->add()) {
                            throw new Webservice_Exception('Error while creating image', [76, 400]);
                        }
                        if (!Validate::is_loaded_object($product)) {
                            throw new Webservice_Exception('Product ' . (int) $this->ws_object->url_segment[2] . ' does not exist', [76, 400]);
                        }
                        Hook::trigger_event('updateProduct', ['id_product' => (int) $this->ws_object->url_segment[2]]);
                    }
                    // copy image
                    if ($error = Image_Manager::validate_upload($file, $img_max_upload_size)) {
                        throw new Webservice_Exception('Bad image : ' . $error, [76, 400]);
                    }
                    if ($this->image_type == Image_Entity::ENTITY_TYPE_PRODUCTS) {
                        $image = new Image($image->id);
                        if (!(Configuration::get('PS_OLD_FILESYSTEM') && Image_Manager::get_source_image(_PS_PROD_IMG_DIR_, $product->id . '-' . $image->id))) {
                            $image->create_img_folder();
                        }
                        if (!($tmp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($file['tmp_name'], $tmp_name)) {
                            throw new Webservice_Exception('An error occurred during the image upload', [76, 400]);
                        }
                        if (!Image_Manager::resize($tmp_name, _PS_PROD_IMG_DIR_ . $image->get_existing_img_path() . '.' . $image->image_format)) {
                            throw new Webservice_Exception('An error occurred while copying image', [76, 400]);
                        }
                        $images_types = Image_Type::get_images_types(Image_Entity::ENTITY_TYPE_PRODUCTS);
                        foreach ($images_types as $image_type) {
                            if (!Image_Manager::resize($tmp_name, _PS_PROD_IMG_DIR_ . $image->get_existing_img_path() . '-' . stripslashes((string) $image_type['name']) . '.' . $image->image_format, $image_type['width'], $image_type['height'], $image->image_format)) {
                                throw new Webservice_Exception(Tools::display_error('An error occurred while copying image:') . ' ' . stripslashes((string) $image_type['name']), [76, 400]);
                            }
                        }
                        @unlink($tmp_name);
                        $this->img_to_display = _PS_PROD_IMG_DIR_ . $image->get_existing_img_path() . '.' . $image->image_format;
                        $this->obj_output->set_fields_to_display('full');
                        $this->output = $this->obj_output->render_entity($image, 1);
                        $image_content = ['sqlId' => 'content', 'value' => base64_encode(file_get_contents($this->img_to_display)), 'encode' => 'base64'];
                        $this->output .= $this->obj_output->object_render->render_field($image_content);
                    } elseif (in_array($this->image_type, ['categories', 'manufacturers', 'suppliers', 'stores'])) {
                        if (!($tmp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($file['tmp_name'], $tmp_name)) {
                            throw new Webservice_Exception('An error occurred during the image upload', [76, 400]);
                        }
                        if (!Image_Manager::resize($tmp_name, $reception_path)) {
                            throw new Webservice_Exception('An error occurred while copying image', [76, 400]);
                        }
                        $images_types = Image_Type::get_images_types($this->image_type);
                        foreach ($images_types as $image_type) {
                            $image_extension = Image_Manager::get_default_image_extension();
                            if (!Image_Manager::resize($tmp_name, $parent_path . $this->ws_object->url_segment[2] . '-' . stripslashes((string) $image_type['name']) . '.' . $image_extension, $image_type['width'], $image_type['height'], $image_extension)) {
                                throw new Webservice_Exception(Tools::display_error('An error occurred while copying image:') . ' ' . stripslashes((string) $image_type['name']), [76, 400]);
                            }
                        }
                        @unlink(_PS_TMP_IMG_DIR_ . $tmp_name);
                        $this->img_to_display = $reception_path;
                    } elseif ($this->image_type == 'customizations') {
                        $filename = md5(uniqid(random_int(0, mt_getrandmax()), true));
                        $this->img_to_display = _PS_UPLOAD_DIR_ . $filename;
                        if (!($tmp_name = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($file['tmp_name'], $tmp_name)) {
                            throw new Webservice_Exception('An error occurred during the image upload', [76, 400]);
                        }
                        if (!Image_Manager::resize($tmp_name, $this->img_to_display)) {
                            throw new Webservice_Exception('An error occurred while copying image', [76, 400]);
                        }
                        $product_picture_width = (int) Configuration::get('PS_PRODUCT_PICTURE_WIDTH');
                        $product_picture_height = (int) Configuration::get('PS_PRODUCT_PICTURE_HEIGHT');
                        if (!Image_Manager::resize($this->img_to_display, $this->img_to_display . '_small', $product_picture_width, $product_picture_height)) {
                            throw new Webservice_Exception('An error occurred while resizing image', [76, 400]);
                        }
                        @unlink(_PS_TMP_IMG_DIR_ . $tmp_name);
                        if (!Db::get_instance()->insert('customized_data', ['id_customization' => (int) $this->ws_object->url_segment[3], 'type' => 0, 'index' => (int) $this->ws_object->url_segment[4], 'value' => p_sql($filename)])) {
                            return false;
                        }
                    }
                    return true;
                }
            }
        } else {
            throw new Webservice_Exception('Method ' . $this->ws_object->method . ' is not allowed for an image resource', [77, 405]);
        }
    }
    /**
     * @throws PrestaShopException
     */
    protected function get_image_extension(): string
    {
        $key = $this->ws_object->get_webservice_key();
        return $key->get_image_extension();
    }
    /**
     *
     * @throws WebserviceException
     */
    protected function check_image_size_exits(array $normal_image_sizes, string $image_size)
    {
        $normal_image_size_names = [];
        foreach ($normal_image_sizes as $normal_image_size) {
            $normal_image_size_names[] = $normal_image_size['name'];
        }
        // Check the given size
        if (!in_array($image_size, $normal_image_size_names)) {
            $exception = new Webservice_Exception('This image size does not exist', [58, 400]);
            throw $exception->set_did_you_mean($image_size, $normal_image_size_names);
        }
    }
}