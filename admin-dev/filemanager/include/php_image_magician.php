<?php

declare (strict_types=1);
# ======================================================================== #
#
#  This work is licensed under the Creative Commons Attribution 3.0 Unported
#  License. To view a copy of this license,
#  visit http://creativecommons.org/licenses/by/3.0/ or send a letter to
#  Creative Commons, 444 Castro Street, Suite 900, Mountain View, California,
#  94041, USA.
#
#  All rights reserved.
#
#  Author:    Jarrod Oberto
#  Version:   1.5.1
#  Date:      10-05-11
#  Purpose:   Provide tools for image manipulation using GD
#  Param In:  See functions.
#  Param Out: Produces a resized image
#  Requires:  Requires PHP GD library.
#  Usage Example:
#    include("lib/php_image_magician.php");
#    $magicianObj = new resize('images/car.jpg');
#    $magicianObj -> resizeImage(150, 100, 0);
#    $magicianObj -> saveImage('images/car_small.jpg', 100);
#
#  - See end of doc for more examples -
#
#  Supported file types include: webp, jpg, png, gif, bmp, psd (read)
#
#  The following functions are taken from phpThumb() [available from
#    http://phpthumb.sourceforge.net], and are used with written permission
#  from James Heinrich.
#    - GD2BMPstring
#    - GetPixelColor
#    - LittleEndian2String
#
#  The following functions are from Marc Hibbins and are used with written
#  permission (are also under the Attribution-ShareAlike
#  [http://creativecommons.org/licenses/by-sa/3.0/] license.
#
#  PhpPsdReader is used with written permission from Tim de Koning.
#  [http://www.kingsquare.nl/phppsdreader]
#
#  Modificatoin history
#  Date      Initials  Ver Description
#  10-05-11  J.C.O   0.0 Initial build
#  01-06-11  J.C.O   0.1.1   * Added reflections
#              * Added Rounded corners
#              * You can now use PNG interlacing
#              * Added shadow
#              * Added caption box
#              * Added vintage filter
#              * Added dynamic image resizing (resize on the fly)
#              * minor bug fixes
#  05-06-11  J.C.O   0.1.1.1 * Fixed undefined variables
#  17-06-11  J.C.O   0.1.2   * Added image_batch_class.php class
#              * Minor bug fixes
#  26-07-11  J.C.O   0.1.4 * Added support for external images
#              * Can now set the crop poisition
#  03-08-11  J.C.O   0.1.5 * Added reset() method to reset resource to
#                original input file.
#              * Added method addTextToCaptionBox() to
#                simplify adding text to a caption box.
#              * Added experimental writeIPTC. (not finished)
#              * Added experimental readIPTC. (not finished)
#  11-08-11  J.C.O     * Added initial border presets.
#  30-08-11  J.C.O     * Added 'auto' crop option to crop portrait
#                images near the top.
#  08-09-11  J.C.O     * Added cropImage() method to allow standalone
#                cropping.
#  17-09-11  J.C.O     * Added setCropFromTop() set method - set the
#                percentage to crop from the top when using
#                crop 'auto' option.
#              * Added setTransparency() set method - allows you
#                to turn transparency off (like when saving
#                as a jpg).
#              * Added setFillColor() set method - set the
#                background color to use instead of transparency.
#  05-11-11  J.C.O   0.1.5.1 * Fixed interlacing option
#  0-07-12  J.C.O   1.0
#
#  Known issues & Limitations:
# -------------------------------
#  Not so much an issue, the image is destroyed on the deconstruct rather than
#  when we have finished with it. The reason for this is that we don't know
#  when we're finished with it as you can both save the image and display
#  it directly to the screen (imagedestroy($this->imageResized))
#
#  Opening BMP files is slow. A test with 884 bmp files processed in a loop
#  takes forever - over 5 min. This test inlcuded opening the file, then
#  getting and displaying its width and height.
#
#  $forceStretch:
# -------------------------------
#  On by default.
#  $forceStretch can be disabled by calling method setForceStretch with false
#  parameter. If disabled, if an images original size is smaller than the size
#  specified by the user, the original size will be used. This is useful when
#  dealing with small images.
#
#  If enabled, images smaller than the size specified will be stretched to
#  that size.
#
#  Tips:
# -------------------------------
#  * If you're resizing a transparent png and saving it as a jpg, set
#  $keepTransparency to false with: $magicianObj->setTransparency(false);
#
#  FEATURES:
#    * EASY TO USE
#    * BMP SUPPORT (read & write)
#    * PSD (photoshop) support (read)
#    * RESIZE IMAGES
#      - Preserve transparency (png, gif)
#      - Apply sharpening (jpg) (requires PHP >= 5.1.0)
#      - Set image quality (jpg, png)
#      - Resize modes:
#        - exact size
#        - resize by width (auto height)
#        - resize by height (auto width)
#        - auto (automatically determine the best of the above modes to use)
#        - crop - resize as best as it can then crop the rest
#      - Force stretching of smaller images (upscale)
#    * APPLY FILTERS
#      - Convert to grey scale
#      - Convert to black and white
#      - Convert to sepia
#      - Convert to negative
#    * ROTATE IMAGES
#      - Rotate using predefined "left", "right", or "180"; or any custom degree amount
#    * EXTRACT EXIF DATA (requires exif module)
#      - make
#      - model
#      - date
#      - exposure
#      - aperture
#      - f-stop
#      - iso
#      - focal length
#      - exposure program
#      - metering mode
#      - flash status
#      - creator
#      - copyright
#    * ADD WATERMARK
#      - Specify exact x, y placement
#      - Or, specify using one of the 9 pre-defined placements such as "tl"
#        (for top left), "m" (for middle), "br" (for bottom right)
#        - also specify padding from edge amount (optional).
#      - Set opacity of watermark (png).
#    * ADD BORDER
#    * USE HEX WHEN SPECIFYING COLORS (eg: #ffffff)
#    * SAVE IMAGE OR OUTPUT TO SCREEN
#
# ======================================================================== #
class Image_Lib
{
    /**
     * @var string
     */
    private $file_name;
    /**
     * @var false|GdImage|resource
     */
    private $image;
    /**
     * @var false|GdImage|resource
     */
    protected $image_resized;
    /**
     * @var false|int
     */
    private $width_original;
    /**
     * @var false|int
     */
    private $height_original;
    /**
     * @var false|int
     */
    private $width;
    /**
     * @var false|int
     */
    private $height;
    /**
     * @var string
     */
    private $file_extension;
    /**
     * @var array
     */
    private $error_array = [];
    /**
     * @var bool
     */
    private $force_stretch = true;
    /**
     * @var bool
     */
    private $aggresive_sharpening = false;
    /**
     * @var string[]
     */
    private $transparent_array = ['.png', '.gif', '.webp'];
    /**
     * @var bool
     */
    private $keep_transparency = true;
    /**
     * @var int[]
     */
    private $fill_color_array = ['r' => 255, 'g' => 255, 'b' => 255];
    /**
     * @var string[]
     */
    private $sharpen_array = ['.jpg'];
    /**
     * @var string
     */
    private $filter_overlay_path;
    /**
     * @var bool
     */
    private $is_interlace;
    /**
     * @var array
     */
    private $caption_box_position_array = [];
    /**
     * @var string
     */
    private $font_dir = 'fonts';
    /**
     * @var int
     */
    private $crop_from_top_percent = 10;
    /**
     * @param string $fileName
     * @throws PrestaShopException
     */
    public function __construct($file_name)
    {
        if (!$this->test_gd_installed()) {
            throw new Presta_Shop_Exception('The GD Library is not installed.');
        }
        $this->initialise();
        $this->file_name = $file_name;
        $this->file_extension = fix_strtolower(strrchr($file_name, '.'));
        $this->image = $this->open_image($file_name);
        $this->image_resized = $this->image;
        if ($this->test_is_image($this->image)) {
            $this->width = imagesx($this->image);
            $this->width_original = imagesx($this->image);
            $this->height = imagesy($this->image);
            $this->height_original = imagesy($this->image);
        } else {
            $this->error_array[] = 'File is not an image';
        }
    }
    /**
     * @return void
     */
    private function initialise()
    {
        $this->filter_overlay_path = dirname(__FILE__) . '/filters';
        $this->is_interlace = false;
    }
    /**
     * @param int $newWidth
     * @param int $newHeight
     * @param int $option
     * @param bool $sharpen
     * @param bool $autoRotate
     * @return void
     * @throws PrestaShopException
     */
    public function resize_image($new_width, $new_height, $option = 0, $sharpen = false, $auto_rotate = false)
    {
        $crop_pos = 'm';
        if (is_array($option) && fix_strtolower($option[0]) == 'crop') {
            $crop_pos = $option[1];
        } else if (strpos($option, '-') !== false) {
            $option_pieces_array = explode('-', $option);
            $crop_pos = end($option_pieces_array);
        }
        $option = $this->prep_option($option);
        if (!$this->image) {
            throw new Presta_Shop_Exception('file ' . $this->get_file_name() . ' is missing or invalid');
        }
        $dimensions_array = $this->get_dimensions($new_width, $new_height, $option);
        $optimal_width = $dimensions_array['optimalWidth'];
        $optimal_height = $dimensions_array['optimalHeight'];
        $this->image_resized = imagecreatetruecolor($optimal_width, $optimal_height);
        $this->keep_transparancy($optimal_width, $optimal_height, $this->image_resized);
        imagecopyresampled($this->image_resized, $this->image, 0, 0, 0, 0, $optimal_width, $optimal_height, $this->width, $this->height);
        if ($option == 4 || $option == 'crop') {
            if ($optimal_width >= $new_width && $optimal_height >= $new_height) {
                $this->crop($optimal_width, $optimal_height, $new_width, $new_height, $crop_pos);
            }
        }
        if ($auto_rotate) {
            $exif_data = $this->get_exif();
            if (count($exif_data) > 0) {
                switch ($exif_data['orientation']) {
                    case 8:
                        $this->image_resized = imagerotate($this->image_resized, 90, 0);
                        break;
                    case 3:
                        $this->image_resized = imagerotate($this->image_resized, 180, 0);
                        break;
                    case 6:
                        $this->image_resized = imagerotate($this->image_resized, -90, 0);
                        break;
                }
            }
        }
        if ($sharpen && in_array($this->file_extension, $this->sharpen_array)) {
            $this->sharpen();
        }
    }
    /**
     * @param int $newWidth
     * @param int $newHeight
     * @param string $cropPos
     * @return void
     * @throws PrestaShopException
     */
    public function crop_image($new_width, $new_height, $crop_pos = 'm')
    {
        if (!$this->image) {
            throw new Presta_Shop_Exception('file ' . $this->get_file_name() . ' is missing or invalid');
        }
        $this->image_resized = $this->image;
        $this->crop($this->width, $this->height, $new_width, $new_height, $crop_pos);
    }
    /**
     * @param int $width
     * @param int $height
     * @param GdImage $im
     * @return void
     */
    private function keep_transparancy($width, $height, $im)
    {
        if (in_array($this->file_extension, $this->transparent_array) && $this->keep_transparency) {
            imagealphablending($im, false);
            imagesavealpha($im, true);
            $transparent = imagecolorallocatealpha($im, 255, 255, 255, 127);
            imagefilledrectangle($im, 0, 0, $width, $height, $transparent);
        } else {
            $color = imagecolorallocate($im, $this->fill_color_array['r'], $this->fill_color_array['g'], $this->fill_color_array['b']);
            imagefilledrectangle($im, 0, 0, $width, $height, $color);
        }
    }
    /**
     * @param int $optimalWidth
     * @param int $optimalHeight
     * @param int $newWidth
     * @param int $newHeight
     * @param string $cropPos
     * @return void
     */
    private function crop($optimal_width, $optimal_height, $new_width, $new_height, $crop_pos)
    {
        $crop_array = $this->get_crop_placing($optimal_width, $optimal_height, $new_width, $new_height, $crop_pos);
        $crop_start_x = (int) $crop_array['x'];
        $crop_start_y = (int) $crop_array['y'];
        $crop = imagecreatetruecolor($new_width, $new_height);
        $this->keep_transparancy($optimal_width, $optimal_height, $crop);
        imagecopyresampled($crop, $this->image_resized, 0, 0, $crop_start_x, $crop_start_y, $new_width, $new_height, $new_width, $new_height);
        $this->image_resized = $crop;
        $this->width = $new_width;
        $this->height = $new_height;
    }
    /**
     * @param int $optimalWidth
     * @param int $optimalHeight
     * @param int $newWidth
     * @param int $newHeight
     * @param string $pos
     * @return array
     */
    private function get_crop_placing($optimal_width, $optimal_height, $new_width, $new_height, $pos = 'm')
    {
        $pos = fix_strtolower($pos);
        if (strstr($pos, 'x')) {
            $pos = str_replace(' ', '', $pos);
            $xy_array = explode('x', $pos);
            list($crop_start_x, $crop_start_y) = $xy_array;
        } else {
            switch ($pos) {
                case 'tl':
                    $crop_start_x = 0;
                    $crop_start_y = 0;
                    break;
                case 't':
                    $crop_start_x = $optimal_width / 2 - $new_width / 2;
                    $crop_start_y = 0;
                    break;
                case 'tr':
                    $crop_start_x = $optimal_width - $new_width;
                    $crop_start_y = 0;
                    break;
                case 'l':
                    $crop_start_x = 0;
                    $crop_start_y = $optimal_height / 2 - $new_height / 2;
                    break;
                case 'm':
                    $crop_start_x = $optimal_width / 2 - $new_width / 2;
                    $crop_start_y = $optimal_height / 2 - $new_height / 2;
                    break;
                case 'r':
                    $crop_start_x = $optimal_width - $new_width;
                    $crop_start_y = $optimal_height / 2 - $new_height / 2;
                    break;
                case 'bl':
                    $crop_start_x = 0;
                    $crop_start_y = $optimal_height - $new_height;
                    break;
                case 'b':
                    $crop_start_x = $optimal_width / 2 - $new_width / 2;
                    $crop_start_y = $optimal_height - $new_height;
                    break;
                case 'br':
                    $crop_start_x = $optimal_width - $new_width;
                    $crop_start_y = $optimal_height - $new_height;
                    break;
                case 'auto':
                    if ($optimal_height > $optimal_width) {
                        $crop_start_x = $optimal_width / 2 - $new_width / 2;
                        $crop_start_y = $this->crop_from_top_percent / 100 * $optimal_height;
                    } else {
                        $crop_start_x = $optimal_width / 2 - $new_width / 2;
                        $crop_start_y = $optimal_height / 2 - $new_height / 2;
                    }
                    break;
                default:
                    $crop_start_x = $optimal_width / 2 - $new_width / 2;
                    $crop_start_y = $optimal_height / 2 - $new_height / 2;
                    break;
            }
        }
        return ['x' => $crop_start_x, 'y' => $crop_start_y];
    }
    /**
     * @param int $newWidth
     * @param int $newHeight
     * @param string $option
     * @return array
     */
    private function get_dimensions($new_width, $new_height, $option)
    {
        switch (strval($option)) {
            case '0':
            case 'exact':
                $optimal_width = $new_width;
                $optimal_height = $new_height;
                break;
            case '1':
            case 'portrait':
                $dimensions_array = $this->get_size_by_fixed_height($new_width, $new_height);
                $optimal_width = $dimensions_array['optimalWidth'];
                $optimal_height = $dimensions_array['optimalHeight'];
                break;
            case '2':
            case 'landscape':
                $dimensions_array = $this->get_size_by_fixed_width($new_width, $new_height);
                $optimal_width = $dimensions_array['optimalWidth'];
                $optimal_height = $dimensions_array['optimalHeight'];
                break;
            case '3':
            case 'auto':
                $dimensions_array = $this->get_size_by_auto($new_width, $new_height);
                $optimal_width = $dimensions_array['optimalWidth'];
                $optimal_height = $dimensions_array['optimalHeight'];
                break;
            case '4':
            case 'crop':
                $dimensions_array = $this->get_optimal_crop($new_width, $new_height);
                $optimal_width = $dimensions_array['optimalWidth'];
                $optimal_height = $dimensions_array['optimalHeight'];
                break;
            default:
                $optimal_width = $new_width;
                $optimal_height = $new_height;
                break;
        }
        return ['optimalWidth' => $optimal_width, 'optimalHeight' => $optimal_height];
    }
    /**
     * @param int $newWidth
     * @param int $newHeight
     * @return array
     */
    private function get_size_by_fixed_height($new_width, $new_height)
    {
        if (!$this->force_stretch) {
            if ($this->height < $new_height) {
                return ['optimalWidth' => $this->width, 'optimalHeight' => $this->height];
            }
        }
        $ratio = $this->width / $this->height;
        $new_width = $new_height * $ratio;
        return ['optimalWidth' => $new_width, 'optimalHeight' => $new_height];
    }
    /**
     * @param int $newWidth
     * @param int $newHeight
     * @return array
     */
    private function get_size_by_fixed_width($new_width, $new_height)
    {
        if (!$this->force_stretch) {
            if ($this->width < $new_width) {
                return ['optimalWidth' => $this->width, 'optimalHeight' => $this->height];
            }
        }
        $ratio = $this->height / $this->width;
        $new_height = $new_width * $ratio;
        return ['optimalWidth' => $new_width, 'optimalHeight' => $new_height];
    }
    /**
     * @param int $newWidth
     * @param int $newHeight
     * @return array
     */
    private function get_size_by_auto($new_width, $new_height)
    {
        if (!$this->force_stretch) {
            if ($this->width < $new_width && $this->height < $new_height) {
                return ['optimalWidth' => $this->width, 'optimalHeight' => $this->height];
            }
        }
        if ($this->height < $this->width) {
            $dimensions_array = $this->get_size_by_fixed_width($new_width, $new_height);
            $optimal_width = $dimensions_array['optimalWidth'];
            $optimal_height = $dimensions_array['optimalHeight'];
        } elseif ($this->height > $this->width) {
            $dimensions_array = $this->get_size_by_fixed_height($new_width, $new_height);
            $optimal_width = $dimensions_array['optimalWidth'];
            $optimal_height = $dimensions_array['optimalHeight'];
        } else if ($new_height < $new_width) {
            $dimensions_array = $this->get_size_by_fixed_width($new_width, $new_height);
            $optimal_width = $dimensions_array['optimalWidth'];
            $optimal_height = $dimensions_array['optimalHeight'];
        } else if ($new_height > $new_width) {
            $dimensions_array = $this->get_size_by_fixed_height($new_width, $new_height);
            $optimal_width = $dimensions_array['optimalWidth'];
            $optimal_height = $dimensions_array['optimalHeight'];
        } else {
            $optimal_width = $new_width;
            $optimal_height = $new_height;
        }
        return ['optimalWidth' => $optimal_width, 'optimalHeight' => $optimal_height];
    }
    /**
     * @param int $newWidth
     * @param int $newHeight
     * @return array
     */
    private function get_optimal_crop($new_width, $new_height)
    {
        if (!$this->force_stretch) {
            if ($this->width < $new_width && $this->height < $new_height) {
                return ['optimalWidth' => $this->width, 'optimalHeight' => $this->height];
            }
        }
        $height_ratio = $this->height / $new_height;
        $width_ratio = $this->width / $new_width;
        $optimal_ratio = min($height_ratio, $width_ratio);
        $optimal_height = round($this->height / $optimal_ratio);
        $optimal_width = round($this->width / $optimal_ratio);
        return ['optimalWidth' => $optimal_width, 'optimalHeight' => $optimal_height];
    }
    /**
     * @return void
     */
    private function sharpen()
    {
        if ($this->aggresive_sharpening) {
            $sharpen_matrix = [[-1, -1, -1], [-1, 16, -1], [-1, -1, -1]];
            $divisor = 8;
            $offset = 0;
            imageconvolution($this->image_resized, $sharpen_matrix, $divisor, $offset);
        } else {
            $sharpness = $this->find_sharp($this->width_original, $this->width);
            $sharpen_matrix = [[-1, -2, -1], [-2, $sharpness + 12, -2], [-1, -2, -1]];
            $divisor = $sharpness;
            $offset = 0;
            imageconvolution($this->image_resized, $sharpen_matrix, $divisor, $offset);
        }
    }
    /**
     * @param float $orig
     * @param float $final
     * @return float
     */
    private function find_sharp($orig, $final)
    {
        $final = $final * (750.0 / $orig);
        $a = 52;
        $b = -0.27810650887573124;
        $c = 0.00047337278106508946;
        $result = $a + $b * $final + $c * $final * $final;
        return max(round($result), 0);
    }
    /**
     * @param array|string $option
     * @return string
     * @throws PrestaShopException
     */
    private function prep_option($option)
    {
        if (is_array($option)) {
            if (fix_strtolower($option[0]) == 'crop' && count($option) == 2) {
                return 'crop';
            } else {
                throw new Presta_Shop_Exception('Crop resize option array is badly formatted.');
            }
        } else if (strpos($option, 'crop') !== false) {
            return 'crop';
        }
        if (is_string($option)) {
            return fix_strtolower($option);
        }
        /** @var string $option */
        return $option;
    }
    /**
     * @param string $preset
     * @return void
     */
    public function border_preset($preset)
    {
        switch ($preset) {
            case 'simple':
                $this->add_border(7, '#fff');
                $this->add_border(6, '#f2f1f0');
                $this->add_border(2, '#fff');
                $this->add_border(1, '#ccc');
                break;
            default:
                break;
        }
    }
    /**
     * @param int $thickness
     * @param array $rgbArray
     * @return void
     */
    public function add_border($thickness = 1, $rgb_array = [255, 255, 255])
    {
        if ($this->image_resized) {
            $rgb_array = $this->format_color($rgb_array);
            $r = $rgb_array['r'];
            $g = $rgb_array['g'];
            $b = $rgb_array['b'];
            $x1 = 0;
            $y1 = 0;
            $x2 = image_sx($this->image_resized) - 1;
            $y2 = image_sy($this->image_resized) - 1;
            $rgb_array = image_color_allocate($this->image_resized, $r, $g, $b);
            for ($i = 0; $i < $thickness; $i++) {
                image_rectangle($this->image_resized, $x1++, $y1++, $x2--, $y2--, $rgb_array);
            }
        }
    }
    /**
     * @return void
     */
    public function grey_scale()
    {
        if ($this->image_resized) {
            imagefilter($this->image_resized, IMG_FILTER_GRAYSCALE);
        }
    }
    /**
     * @return void
     */
    public function grey_scale_enhanced()
    {
        if ($this->image_resized) {
            imagefilter($this->image_resized, IMG_FILTER_GRAYSCALE);
            imagefilter($this->image_resized, IMG_FILTER_CONTRAST, -15);
            imagefilter($this->image_resized, IMG_FILTER_BRIGHTNESS, 2);
            $this->sharpen();
        }
    }
    /**
     * @return void
     */
    public function grey_scale_dramatic()
    {
        $this->gd_filter_monopin();
    }
    /**
     * @return void
     */
    public function black_and_white()
    {
        if ($this->image_resized) {
            imagefilter($this->image_resized, IMG_FILTER_GRAYSCALE);
            imagefilter($this->image_resized, IMG_FILTER_CONTRAST, -1000);
        }
    }
    /**
     * @return void
     */
    public function negative()
    {
        if ($this->image_resized) {
            imagefilter($this->image_resized, IMG_FILTER_NEGATE);
        }
    }
    /**
     * @return void
     */
    public function sepia()
    {
        if ($this->image_resized) {
            imagefilter($this->image_resized, IMG_FILTER_GRAYSCALE);
            imagefilter($this->image_resized, IMG_FILTER_BRIGHTNESS, -10);
            imagefilter($this->image_resized, IMG_FILTER_CONTRAST, -20);
            imagefilter($this->image_resized, IMG_FILTER_COLORIZE, 60, 30, -15);
        }
    }
    /**
     * @return void
     */
    public function sepia2()
    {
        if ($this->image_resized) {
            $total = imagecolorstotal($this->image_resized);
            for ($i = 0; $i < $total; $i++) {
                $index = imagecolorsforindex($this->image_resized, $i);
                $red = ($index['red'] * 0.393 + $index['green'] * 0.769 + $index['blue'] * 0.189) / 1.351;
                $green = ($index['red'] * 0.349 + $index['green'] * 0.6860000000000001 + $index['blue'] * 0.168) / 1.203;
                $blue = ($index['red'] * 0.272 + $index['green'] * 0.534 + $index['blue'] * 0.131) / 2.14;
                imagecolorset($this->image_resized, $i, $red, $green, $blue);
            }
        }
    }
    /**
     * @return void
     */
    public function vintage()
    {
        $this->gd_filter_vintage();
    }
    /**
     * @return void
     */
    public function gd_filter_monopin()
    {
        if ($this->image_resized) {
            imagefilter($this->image_resized, IMG_FILTER_GRAYSCALE);
            imagefilter($this->image_resized, IMG_FILTER_BRIGHTNESS, -15);
            imagefilter($this->image_resized, IMG_FILTER_CONTRAST, -15);
            $this->image_resized = $this->gd_apply_overlay($this->image_resized, 'vignette', 100);
        }
    }
    /**
     * @return void
     */
    public function gd_filter_vintage()
    {
        if ($this->image_resized) {
            $this->image_resized = $this->gd_apply_overlay($this->image_resized, 'vignette', 45);
            imagefilter($this->image_resized, IMG_FILTER_BRIGHTNESS, 20);
            imagefilter($this->image_resized, IMG_FILTER_CONTRAST, -35);
            imagefilter($this->image_resized, IMG_FILTER_COLORIZE, 60, -10, 35);
            imagefilter($this->image_resized, IMG_FILTER_SMOOTH, 7);
            $this->image_resized = $this->gd_apply_overlay($this->image_resized, 'scratch', 10);
        }
    }
    /**
     * @param GdImage $im
     * @param string $type
     * @param int $amount
     * @return GdImage
     */
    private function gd_apply_overlay($im, $type, $amount)
    {
        $width = imagesx($im);
        $height = imagesy($im);
        $filter = imagecreatetruecolor($width, $height);
        imagealphablending($filter, false);
        imagesavealpha($filter, true);
        $transparent = imagecolorallocatealpha($filter, 255, 255, 255, 127);
        imagefilledrectangle($filter, 0, 0, $width, $height, $transparent);
        $overlay = $this->filter_overlay_path . '/' . $type . '.png';
        $png = imagecreatefrompng($overlay);
        imagecopyresampled($filter, $png, 0, 0, 0, 0, $width, $height, imagesx($png), imagesy($png));
        $comp = imagecreatetruecolor($width, $height);
        imagecopy($comp, $im, 0, 0, 0, 0, $width, $height);
        imagecopy($comp, $filter, 0, 0, 0, 0, $width, $height);
        imagecopymerge($im, $comp, 0, 0, 0, 0, $width, $height, $amount);
        imagedestroy($comp);
        return $im;
    }
    /**
     * @param array $rgb
     * @return bool
     */
    public function image_colorize($rgb)
    {
        image_true_color_to_palette($this->image_resized, true, 256);
        $num_colors = image_colors_total($this->image_resized);
        for ($x = 0; $x < $num_colors; $x++) {
            list($r, $g, $b) = array_values(image_colors_for_index($this->image_resized, $x));
            $grayscale = ($r + $g + $b) / 3 / 0xff;
            image_color_set($this->image_resized, $x, $grayscale * $rgb[0], $grayscale * $rgb[1], $grayscale * $rgb[2]);
        }
        return true;
    }
    /**
     * @param int $reflectionHeight
     * @param int $startingTransparency
     * @param false $inside
     * @param string $bgColor
     * @param bool $stretch
     * @param int $divider
     * @return void
     */
    public function add_reflection($reflection_height = 50, $starting_transparency = 30, $inside = false, $bg_color = '#fff', $stretch = false, $divider = 0)
    {
        $rgb_array = $this->format_color($bg_color);
        $r = $rgb_array['r'];
        $g = $rgb_array['g'];
        $b = $rgb_array['b'];
        $im = $this->image_resized;
        $li = imagecreatetruecolor($this->width, 1);
        $bgc = imagecolorallocate($li, $r, $g, $b);
        imagefilledrectangle($li, 0, 0, $this->width, 1, $bgc);
        $bg = imagecreatetruecolor($this->width, $reflection_height);
        $wh = imagecolorallocate($im, 255, 255, 255);
        $im = imagerotate($im, -180, $wh);
        imagecopyresampled($bg, $im, 0, 0, 0, 0, $this->width, $this->height, $this->width, $this->height);
        $im = $bg;
        $bg = imagecreatetruecolor($this->width, $reflection_height);
        for ($x = 0; $x < $this->width; $x++) {
            imagecopy($bg, $im, $x, 0, $this->width - $x - 1, 0, 1, $reflection_height);
        }
        $im = $bg;
        if ($stretch) {
            $step = 100 / ($reflection_height + $starting_transparency);
        } else {
            $step = 100 / $reflection_height;
        }
        for ($i = 0; $i <= $reflection_height; $i++) {
            if ($starting_transparency > 100) {
                $starting_transparency = 100;
            }
            if ($starting_transparency < 1) {
                $starting_transparency = 1;
            }
            imagecopymerge($bg, $li, 0, $i, 0, 0, $this->width, 1, $starting_transparency);
            $starting_transparency += $step;
        }
        imagecopymerge($im, $li, 0, 0, 0, 0, $this->width, $divider, 100);
        $x = imagesx($im);
        $y = imagesy($im);
        if ($inside) {
            $final = imagecreatetruecolor($this->width, $this->height);
            imagecopymerge($final, $this->image_resized, 0, 0, 0, $reflection_height, $this->width, $this->height - $reflection_height, 100);
            imagecopymerge($final, $im, 0, $this->height - $reflection_height, 0, 0, $x, $y, 100);
        } else {
            $final = imagecreatetruecolor($this->width, $this->height + $y);
            imagecopymerge($final, $this->image_resized, 0, 0, 0, 0, $this->width, $this->height, 100);
            imagecopymerge($final, $im, 0, $this->height, 0, 0, $x, $y, 100);
        }
        $this->image_resized = $final;
        imagedestroy($li);
        imagedestroy($im);
    }
    /**
     * @param int $value
     * @param string $bgColor
     * @return void
     */
    public function rotate($value = 90, $bg_color = 'transparent')
    {
        if ($this->image_resized) {
            $degrees = (int) $value;
            $rgb_array = $this->format_color($bg_color);
            $r = $rgb_array['r'];
            $g = $rgb_array['g'];
            $b = $rgb_array['b'];
            $a = $rgb_array['a'] ?? 0;
            if (is_string($value)) {
                $value = fix_strtolower($value);
                switch ($value) {
                    case 'left':
                        $degrees = 90;
                        break;
                    case 'right':
                        $degrees = 270;
                        break;
                    case 'upside':
                        $degrees = 180;
                        break;
                    default:
                        break;
                }
            }
            $degrees = 360 - $degrees;
            $bg = image_color_allocate_alpha($this->image_resized, $r, $g, $b, $a);
            image_fill($this->image_resized, 0, 0, $bg);
            $this->image_resized = imagerotate($this->image_resized, $degrees, $bg);
            image_save_alpha($this->image_resized, true);
        }
    }
    /**
     * @param int $radius
     * @param string|array $bgColor
     * @return void
     */
    public function round_corners($radius = 5, $bg_color = 'transparent')
    {
        $is_transparent = false;
        if (!is_array($bg_color)) {
            if (fix_strtolower($bg_color) == 'transparent') {
                $is_transparent = true;
            }
        }
        if ($is_transparent) {
            $bg_color = $this->find_unused_green();
        }
        $rgb_array = $this->format_color($bg_color);
        $r = $rgb_array['r'];
        $g = $rgb_array['g'];
        $b = $rgb_array['b'];
        $corner_img = imagecreatetruecolor($radius, $radius);
        $mask_color = imagecolorallocate($corner_img, 0, 0, 0);
        imagecolortransparent($corner_img, $mask_color);
        $imagebg_color = imagecolorallocate($corner_img, $r, $g, $b);
        imagefill($corner_img, 0, 0, $imagebg_color);
        imagefilledellipse($corner_img, $radius, $radius, $radius * 2, $radius * 2, $mask_color);
        imagecopymerge($this->image_resized, $corner_img, 0, 0, 0, 0, $radius, $radius, 100);
        $corner_img = imagerotate($corner_img, 90, 0);
        imagecopymerge($this->image_resized, $corner_img, 0, $this->height - $radius, 0, 0, $radius, $radius, 100);
        $corner_img = imagerotate($corner_img, 90, 0);
        imagecopymerge($this->image_resized, $corner_img, $this->width - $radius, $this->height - $radius, 0, 0, $radius, $radius, 100);
        $corner_img = imagerotate($corner_img, 90, 0);
        imagecopymerge($this->image_resized, $corner_img, $this->width - $radius, 0, 0, 0, $radius, $radius, 100);
        if ($is_transparent) {
            $this->image_resized = $this->transparent_image($this->image_resized);
            imagesavealpha($this->image_resized, true);
        }
    }
    /**
     * @param int $shadowAngle
     * @param int $blur
     * @param string|array $bgColor
     * @return void
     */
    public function add_shadow($shadow_angle = 45, $blur = 15, $bg_color = 'transparent')
    {
        define('STEPS', $blur * 2);
        $shadow_distance = $blur * 0.25;
        $blur_width = $blur_height = $blur;
        if ($shadow_angle == 0) {
            $dist_width = 0;
            $dist_height = 0;
        } else {
            $dist_width = $shadow_distance * cos(deg2rad($shadow_angle));
            $dist_height = $shadow_distance * sin(deg2rad($shadow_angle));
        }
        if (fix_strtolower($bg_color) != 'transparent') {
            $rgb_array = $this->format_color($bg_color);
            $r0 = $rgb_array['r'];
            $g0 = $rgb_array['g'];
            $b0 = $rgb_array['b'];
        } else {
            $r0 = 0;
            $g0 = 0;
            $b0 = 0;
        }
        $image = $this->image_resized;
        $width = $this->width;
        $height = $this->height;
        $new_image = imagecreatetruecolor($width, $height);
        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $width, $height, $width, $height);
        $rgb = imagecreatetruecolor($width + $blur_width, $height + $blur_height);
        $colour = imagecolorallocate($rgb, 0, 0, 0);
        imagefilledrectangle($rgb, 0, 0, $width + $blur_width, $height + $blur_height, $colour);
        $colour = imagecolorallocate($rgb, 255, 255, 255);
        imagefilledrectangle($rgb, $blur_width * 0.5 - $dist_width, $blur_height * 0.5 - $dist_height, $width + $blur_width * 0.5 - $dist_width, $height + $blur_width * 0.5 - $dist_height, $colour);
        imagecopymerge($rgb, $new_image, $blur_width * 0.5 - $dist_width, $blur_height * 0.5 - $dist_height, 0, 0, $width + $blur_width, $height + $blur_height, 100);
        $shadow = imagecreatetruecolor($width + $blur_width, $height + $blur_height);
        imagealphablending($shadow, false);
        $colour = imagecolorallocate($shadow, 0, 0, 0);
        imagefilledrectangle($shadow, 0, 0, $width + $blur_width, $height + $blur_height, $colour);
        for ($i = 0; $i <= STEPS; $i++) {
            $t = 1.0 * $i / STEPS;
            $intensity = 255 * $t * $t;
            $colour = imagecolorallocate($shadow, $intensity, $intensity, $intensity);
            $points = [$blur_width * $t, $blur_height, $blur_width, $blur_height * $t, $width, $blur_height * $t, $width + $blur_width * (1 - $t), $blur_height, $width + $blur_width * (1 - $t), $height, $width, $height + $blur_height * (1 - $t), $blur_width, $height + $blur_height * (1 - $t), $blur_width * $t, $height];
            imagepolygon($shadow, $points, 8, $colour);
        }
        for ($i = 0; $i <= STEPS; $i++) {
            $t = 1.0 * $i / STEPS;
            $intensity = 255 * $t * $t;
            $colour = imagecolorallocate($shadow, $intensity, $intensity, $intensity);
            imagefilledarc($shadow, $blur_width - 1, $blur_height - 1, 2 * (1 - $t) * $blur_width, 2 * (1 - $t) * $blur_height, 180, 268, $colour, IMG_ARC_PIE);
            imagefilledarc($shadow, $width, $blur_height - 1, 2 * (1 - $t) * $blur_width, 2 * (1 - $t) * $blur_height, 270, 358, $colour, IMG_ARC_PIE);
            imagefilledarc($shadow, $width, $height, 2 * (1 - $t) * $blur_width, 2 * (1 - $t) * $blur_height, 0, 90, $colour, IMG_ARC_PIE);
            imagefilledarc($shadow, $blur_width - 1, $height, 2 * (1 - $t) * $blur_width, 2 * (1 - $t) * $blur_height, 90, 180, $colour, IMG_ARC_PIE);
        }
        $colour = imagecolorallocate($shadow, 255, 255, 255);
        imagefilledrectangle($shadow, $blur_width, $blur_height, $width, $height, $colour);
        imagefilledrectangle($shadow, $blur_width * 0.5 - $dist_width, $blur_height * 0.5 - $dist_height, $width + $blur_width * 0.5 - 1 - $dist_width, $height + $blur_height * 0.5 - 1 - $dist_height, $colour);
        imagealphablending($rgb, false);
        for ($the_x = 0; $the_x < imagesx($rgb); $the_x++) {
            for ($the_y = 0; $the_y < imagesy($rgb); $the_y++) {
                $col_array = imagecolorat($rgb, $the_x, $the_y);
                $r = $col_array >> 16 & 0xff;
                $g = $col_array >> 8 & 0xff;
                $b = $col_array & 0xff;
                $col_array = imagecolorat($shadow, $the_x, $the_y);
                $a = $col_array & 0xff;
                $a = 127 - floor($a / 2);
                $t = $a / 128.0;
                if (fix_strtolower($bg_color) == 'transparent') {
                    $my_colour = imagecolorallocatealpha($rgb, $r, $g, $b, $a);
                } else {
                    $my_colour = imagecolorallocate($rgb, $r * (1.0 - $t) + $r0 * $t, $g * (1.0 - $t) + $g0 * $t, $b * (1.0 - $t) + $b0 * $t);
                }
                imagesetpixel($rgb, $the_x, $the_y, $my_colour);
            }
        }
        imagealphablending($rgb, true);
        imagesavealpha($rgb, true);
        $this->image_resized = $rgb;
        imagedestroy($image);
        imagedestroy($new_image);
        imagedestroy($shadow);
    }
    /**
     * @param string $side
     * @param int $thickness
     * @param int $padding
     * @param string|array $bgColor
     * @param int $transaprencyAmount
     * @return void
     */
    public function add_caption_box($side = 'b', $thickness = 50, $padding = 0, $bg_color = '#000', $transaprency_amount = 30)
    {
        $side = fix_strtolower($side);
        $rgb_array = $this->format_color($bg_color);
        $r = $rgb_array['r'];
        $g = $rgb_array['g'];
        $b = $rgb_array['b'];
        $position_array = $this->calculate_caption_box_position($side, $thickness, $padding);
        $this->caption_box_position_array = $position_array;
        $transaprency_amount = $this->invert_transparency($transaprency_amount, 127, false);
        $transparent = imagecolorallocatealpha($this->image_resized, $r, $g, $b, $transaprency_amount);
        imagefilledrectangle($this->image_resized, $position_array['x1'], $position_array['y1'], $position_array['x2'], $position_array['y2'], $transparent);
    }
    /**
     * @param string $text
     * @param string $fontColor
     * @param int $fontSize
     * @param int $angle
     * @param string|null $font
     * @return void
     * @throws PrestaShopException
     */
    public function add_text_to_caption_box($text, $font_color = '#fff', $font_size = 12, $angle = 0, $font = null)
    {
        if (count($this->caption_box_position_array) == 4) {
            $x1 = $this->caption_box_position_array['x1'];
            $x2 = $this->caption_box_position_array['x2'];
            $y1 = $this->caption_box_position_array['y1'];
            $y2 = $this->caption_box_position_array['y2'];
        } else {
            throw new Presta_Shop_Exception('No caption box found.');
        }
        $font = $this->get_text_font($font);
        $text_size_array = $this->get_text_size($font_size, $angle, $font, $text);
        $text_width = $text_size_array['width'];
        $text_height = $text_size_array['height'];
        $box_x_middle = ($x2 - $x1) / 2;
        $box_y_middle = ($y2 - $y1) / 2;
        $x_pos = $x1 + $box_x_middle - $text_width / 2;
        $y_pos = $y1 + $box_y_middle - $text_height / 2;
        $pos = $x_pos . 'x' . $y_pos;
        $this->add_text($text, $pos, 0, $font_color, $font_size, $angle, $font);
    }
    /**
     * @param string $side
     * @param int $thickness
     * @param int $padding
     * @return array
     */
    private function calculate_caption_box_position($side, $thickness, $padding)
    {
        $position_array = [];
        switch ($side) {
            case 't':
                $position_array['x1'] = 0;
                $position_array['y1'] = $padding;
                $position_array['x2'] = $this->width;
                $position_array['y2'] = $thickness + $padding;
                break;
            case 'r':
                $position_array['x1'] = $this->width - $thickness - $padding;
                $position_array['y1'] = 0;
                $position_array['x2'] = $this->width - $padding;
                $position_array['y2'] = $this->height;
                break;
            case 'b':
                $position_array['x1'] = 0;
                $position_array['y1'] = $this->height - $thickness - $padding;
                $position_array['x2'] = $this->width;
                $position_array['y2'] = $this->height - $padding;
                break;
            case 'l':
                $position_array['x1'] = $padding;
                $position_array['y1'] = 0;
                $position_array['x2'] = $thickness + $padding;
                $position_array['y2'] = $this->height;
                break;
            default:
                break;
        }
        return $position_array;
    }
    /**
     * @param bool $debug
     * @return array
     */
    public function get_exif($debug = false)
    {
        if (!$this->test_exif_installed()) {
            return [];
        }
        if (!file_exists($this->file_name)) {
            return [];
        }
        if ($this->file_extension != '.jpg') {
            return [];
        }
        $exif_data = exif_read_data($this->file_name, 'IFD0');
        $ev = $exif_data['ApertureValue'];
        $ap_peices_array = explode('/', $ev);
        if (count($ap_peices_array) == 2) {
            $aperture_value = round($ap_peices_array[0] / $ap_peices_array[1], 2, PHP_ROUND_HALF_DOWN) . ' EV';
        } else {
            $aperture_value = '';
        }
        $focal_length = $exif_data['FocalLength'];
        $fl_peices_array = explode('/', $focal_length);
        if (count($fl_peices_array) == 2) {
            $focal_length = $fl_peices_array[0] / $fl_peices_array[1] . '.0 mm';
        } else {
            $focal_length = '';
        }
        $f_number = $exif_data['FNumber'];
        $fn_peices_array = explode('/', $f_number);
        if (count($fn_peices_array) == 2) {
            $f_number = $fn_peices_array[0] / $fn_peices_array[1];
        } else {
            $f_number = '';
        }
        if (isset($exif_data['ExposureProgram'])) {
            $ep = $exif_data['ExposureProgram'];
        }
        if (isset($ep)) {
            $ep = $this->resolve_exposure_program($ep);
        }
        $mm = $exif_data['MeteringMode'];
        $mm = $this->resolve_metering_mode($mm);
        $flash = $exif_data['Flash'];
        $flash = $this->resolve_flash($flash);
        if (isset($exif_data['Make'])) {
            $exif_data_array['make'] = $exif_data['Make'];
        } else {
            $exif_data_array['make'] = '';
        }
        if (isset($exif_data['Model'])) {
            $exif_data_array['model'] = $exif_data['Model'];
        } else {
            $exif_data_array['model'] = '';
        }
        if (isset($exif_data['DateTime'])) {
            $exif_data_array['date'] = $exif_data['DateTime'];
        } else {
            $exif_data_array['date'] = '';
        }
        if (isset($exif_data['ExposureTime'])) {
            $exif_data_array['exposure time'] = $exif_data['ExposureTime'] . ' sec.';
        } else {
            $exif_data_array['exposure time'] = '';
        }
        if ($aperture_value != '') {
            $exif_data_array['aperture value'] = $aperture_value;
        } else {
            $exif_data_array['aperture value'] = '';
        }
        if (isset($exif_data['COMPUTED']['ApertureFNumber'])) {
            $exif_data_array['f-stop'] = $exif_data['COMPUTED']['ApertureFNumber'];
        } else {
            $exif_data_array['f-stop'] = '';
        }
        if (isset($exif_data['FNumber'])) {
            $exif_data_array['fnumber'] = $exif_data['FNumber'];
        } else {
            $exif_data_array['fnumber'] = '';
        }
        if ($f_number != '') {
            $exif_data_array['fnumber value'] = $f_number;
        } else {
            $exif_data_array['fnumber value'] = '';
        }
        if (isset($exif_data['ISOSpeedRatings'])) {
            $exif_data_array['iso'] = $exif_data['ISOSpeedRatings'];
        } else {
            $exif_data_array['iso'] = '';
        }
        if ($focal_length != '') {
            $exif_data_array['focal length'] = $focal_length;
        } else {
            $exif_data_array['focal length'] = '';
        }
        if (isset($ep)) {
            $exif_data_array['exposure program'] = $ep;
        } else {
            $exif_data_array['exposure program'] = '';
        }
        if ($mm != '') {
            $exif_data_array['metering mode'] = $mm;
        } else {
            $exif_data_array['metering mode'] = '';
        }
        if ($flash != '') {
            $exif_data_array['flash status'] = $flash;
        } else {
            $exif_data_array['flash status'] = '';
        }
        if (isset($exif_data['Artist'])) {
            $exif_data_array['creator'] = $exif_data['Artist'];
        } else {
            $exif_data_array['creator'] = '';
        }
        if (isset($exif_data['Copyright'])) {
            $exif_data_array['copyright'] = $exif_data['Copyright'];
        } else {
            $exif_data_array['copyright'] = '';
        }
        if (isset($exif_data['Orientation'])) {
            $exif_data_array['orientation'] = $exif_data['Orientation'];
        } else {
            $exif_data_array['orientation'] = '';
        }
        return $exif_data_array;
    }
    /**
     * @param int $ep
     * @return int|string
     */
    private function resolve_exposure_program($ep)
    {
        switch ($ep) {
            case 0:
                $ep = '';
                break;
            case 1:
                $ep = 'manual';
                break;
            case 2:
                $ep = 'normal program';
                break;
            case 3:
                $ep = 'aperture priority';
                break;
            case 4:
                $ep = 'shutter priority';
                break;
            case 5:
                $ep = 'creative program';
                break;
            case 6:
                $ep = 'action program';
                break;
            case 7:
                $ep = 'portrait mode';
                break;
            case 8:
                $ep = 'landscape mode';
                break;
            default:
                break;
        }
        return $ep;
    }
    /**
     * @param int $mm
     * @return int|string
     */
    private function resolve_metering_mode($mm)
    {
        switch ($mm) {
            case 0:
                $mm = 'unknown';
                break;
            case 1:
                $mm = 'average';
                break;
            case 2:
                $mm = 'center weighted average';
                break;
            case 3:
                $mm = 'spot';
                break;
            case 4:
                $mm = 'multi spot';
                break;
            case 5:
                $mm = 'pattern';
                break;
            case 6:
                $mm = 'partial';
                break;
            case 255:
                $mm = 'other';
                break;
            default:
                break;
        }
        return $mm;
    }
    /**
     * @param int $flash
     * @return int|string
     */
    private function resolve_flash($flash)
    {
        switch ($flash) {
            case 0:
                $flash = 'flash did not fire';
                break;
            case 1:
                $flash = 'flash fired';
                break;
            case 5:
                $flash = 'strobe return light not detected';
                break;
            case 7:
                $flash = 'strobe return light detected';
                break;
            case 9:
                $flash = 'flash fired, compulsory flash mode';
                break;
            case 13:
                $flash = 'flash fired, compulsory flash mode, return light not detected';
                break;
            case 15:
                $flash = 'flash fired, compulsory flash mode, return light detected';
                break;
            case 16:
                $flash = 'flash did not fire, compulsory flash mode';
                break;
            case 24:
                $flash = 'flash did not fire, auto mode';
                break;
            case 25:
                $flash = 'flash fired, auto mode';
                break;
            case 29:
                $flash = 'flash fired, auto mode, return light not detected';
                break;
            case 31:
                $flash = 'flash fired, auto mode, return light detected';
                break;
            case 32:
                $flash = 'no flash function';
                break;
            case 65:
                $flash = 'flash fired, red-eye reduction mode';
                break;
            case 69:
                $flash = 'flash fired, red-eye reduction mode, return light not detected';
                break;
            case 71:
                $flash = 'flash fired, red-eye reduction mode, return light detected';
                break;
            case 73:
                $flash = 'flash fired, compulsory flash mode, red-eye reduction mode';
                break;
            case 77:
                $flash = 'flash fired, compulsory flash mode, red-eye reduction mode, return light not detected';
                break;
            case 79:
                $flash = 'flash fired, compulsory flash mode, red-eye reduction mode, return light detected';
                break;
            case 89:
                $flash = 'flash fired, auto mode, red-eye reduction mode';
                break;
            case 93:
                $flash = 'flash fired, auto mode, return light not detected, red-eye reduction mode';
                break;
            case 95:
                $flash = 'flash fired, auto mode, return light detected, red-eye reduction mode';
                break;
            default:
                break;
        }
        return $flash;
    }
    /**
     * @param string $value
     * @return void
     */
    public function write_ipt_ccaption($value)
    {
        $this->write_iptc(120, $value);
    }
    /**
     * @param string $value
     * @return void
     */
    public function write_ipt_cwriter($value)
    {
    }
    /**
     * @param int $dat
     * @param string $value
     * @return void
     */
    private function write_iptc($dat, $value)
    {
        $caption_block = $this->iptc_maketag(2, $dat, $value);
        $image_string = iptcembed($caption_block, $this->file_name);
        file_put_contents('iptc.jpg', $image_string);
    }
    /**
     * @param int $rec
     * @param int $dat
     * @param string $val
     * @return string
     */
    private function iptc_maketag($rec, $dat, $val)
    {
        $len = strlen($val);
        if ($len < 0x8000) {
            return chr(0x1c) . chr($rec) . chr($dat) . chr($len >> 8) . chr($len & 0xff) . $val;
        } else {
            return chr(0x1c) . chr($rec) . chr($dat) . chr(0x80) . chr(0x4) . chr($len >> 24 & 0xff) . chr($len >> 16 & 0xff) . chr($len >> 8 & 0xff) . chr($len & 0xff) . $val;
        }
    }
    /**
     * @param string $text
     * @param string $pos
     * @param int $padding
     * @param string $fontColor
     * @param int $fontSize
     * @param int $angle
     * @param string|null $font
     * @return void
     * @throws PrestaShopException
     */
    public function add_text($text, $pos = '20x20', $padding = 0, $font_color = '#fff', $font_size = 12, $angle = 0, $font = null)
    {
        $rgb_array = $this->format_color($font_color);
        $r = $rgb_array['r'];
        $g = $rgb_array['g'];
        $b = $rgb_array['b'];
        $font = $this->get_text_font($font);
        $text_size_array = $this->get_text_size($font_size, $angle, $font, $text);
        $text_width = $text_size_array['width'];
        $text_height = $text_size_array['height'];
        $pos_array = $this->calculate_position($pos, $padding, $text_width, $text_height, false);
        $x = $pos_array['width'];
        $y = $pos_array['height'];
        $font_color = imagecolorallocate($this->image_resized, $r, $g, $b);
        imagettftext($this->image_resized, $font_size, $angle, $x, $y, $font_color, $font, $text);
    }
    /**
     * @param string $font
     * @return string
     * @throws PrestaShopException
     */
    private function get_text_font($font)
    {
        $font_path = dirname(__FILE__) . '/' . $this->font_dir;
        putenv('GDFONTPATH=' . realpath('.'));
        if ($font == null || !file_exists($font)) {
            $font = $font_path . '/arimo.ttf';
            if (!file_exists($font)) {
                throw new Presta_Shop_Exception('Font not found');
            }
        }
        return $font;
    }
    /**
     * @param int $fontSize
     * @param int $angle
     * @param string $font
     * @param string $text
     * @return array
     */
    private function get_text_size($font_size, $angle, $font, $text)
    {
        $box = @image_ttf_bbox($font_size, $angle, $font, $text);
        $text_width = abs($box[4] - $box[0]);
        $text_height = abs($box[5] - $box[1]);
        return ['height' => $text_height, 'width' => $text_width];
    }
    /**
     * @param string $watermarkImage
     * @param string $pos
     * @param int $padding
     * @param int $opacity
     * @return void
     * @throws PrestaShopException
     */
    public function add_watermark($watermark_image, $pos, $padding = 0, $opacity = 0)
    {
        $stamp = $this->open_image($watermark_image);
        $im = $this->image_resized;
        $sx = imagesx($stamp);
        $sy = imagesy($stamp);
        $pos_array = $this->calculate_position($pos, $padding, $sx, $sy);
        $x = $pos_array['width'];
        $y = $pos_array['height'];
        if (fix_strtolower(strrchr($watermark_image, '.')) == '.png') {
            $opacity = $this->invert_transparency($opacity, 100);
            $this->filter_opacity($stamp, $opacity);
        }
        imagecopy($im, $stamp, $x, $y, 0, 0, imagesx($stamp), imagesy($stamp));
    }
    /**
     * @param string $pos
     * @param int $padding
     * @param int $assetWidth
     * @param int $assetHeight
     * @param bool $upperLeft
     * @return array
     */
    private function calculate_position($pos, $padding, $asset_width, $asset_height, $upper_left = true)
    {
        $pos = fix_strtolower($pos);
        if (strstr($pos, 'x')) {
            $pos = str_replace(' ', '', $pos);
            $xy_array = explode('x', $pos);
            list($width, $height) = $xy_array;
        } else {
            switch ($pos) {
                case 'tl':
                    $width = 0 + $padding;
                    $height = 0 + $padding;
                    break;
                case 't':
                    $width = $this->width / 2 - $asset_width / 2;
                    $height = 0 + $padding;
                    break;
                case 'tr':
                    $width = $this->width - $asset_width - $padding;
                    $height = 0 + $padding;
                    break;
                case 'l':
                    $width = 0 + $padding;
                    $height = $this->height / 2 - $asset_height / 2;
                    break;
                case 'm':
                    $width = $this->width / 2 - $asset_width / 2;
                    $height = $this->height / 2 - $asset_height / 2;
                    break;
                case 'r':
                    $width = $this->width - $asset_width - $padding;
                    $height = $this->height / 2 - $asset_height / 2;
                    break;
                case 'bl':
                    $width = 0 + $padding;
                    $height = $this->height - $asset_height - $padding;
                    break;
                case 'b':
                    $width = $this->width / 2 - $asset_width / 2;
                    $height = $this->height - $asset_height - $padding;
                    break;
                case 'br':
                    $width = $this->width - $asset_width - $padding;
                    $height = $this->height - $asset_height - $padding;
                    break;
                default:
                    $width = 0;
                    $height = 0;
                    break;
            }
        }
        if (!$upper_left) {
            $height = $height + $asset_height;
        }
        return ['width' => $width, 'height' => $height];
    }
    /**
     * @param GdImage $img
     * @param int $opacity
     */
    private function filter_opacity($img, $opacity = 75)
    {
        if (!isset($opacity)) {
            return;
        }
        if ($opacity == 100) {
            return;
        }
        $opacity /= 100;
        $w = imagesx($img);
        $h = imagesy($img);
        imagealphablending($img, false);
        $minalpha = 127;
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $alpha = imagecolorat($img, $x, $y) >> 24 & 0xff;
                if ($alpha < $minalpha) {
                    $minalpha = $alpha;
                }
            }
        }
        for ($x = 0; $x < $w; $x++) {
            for ($y = 0; $y < $h; $y++) {
                $colorxy = imagecolorat($img, $x, $y);
                $alpha = $colorxy >> 24 & 0xff;
                if ($minalpha !== 127) {
                    $alpha = 127 + 127 * $opacity * ($alpha - 127) / (127 - $minalpha);
                } else {
                    $alpha += 127 * $opacity;
                }
                $alphacolorxy = imagecolorallocatealpha($img, $colorxy >> 16 & 0xff, $colorxy >> 8 & 0xff, $colorxy & 0xff, $alpha);
                imagesetpixel($img, $x, $y, $alphacolorxy);
            }
        }
    }
    /**
     * @param string $file
     * @return false|GdImage|resource
     * @throws PrestaShopException
     */
    private function open_image($file)
    {
        if (!file_exists($file) && !$this->check_string_starts_with('http://', $file) && !$this->check_string_starts_with('https://', $file)) {
            throw new Presta_Shop_Exception('Image not found.');
        }
        $extension = mime_content_type($file);
        $extension = fix_strtolower($extension);
        $extension = str_replace('image/', '', $extension);
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $img = @imagecreatefromjpeg($file);
                break;
            case 'webp':
                $img = @imagecreatefromwebp($file);
                break;
            case 'avif':
                $img = function_exists('imagecreatefromavif') ? imagecreatefromavif($file) : false;
                break;
            case 'gif':
                $img = @imagecreatefromgif($file);
                break;
            case 'png':
                $img = @imagecreatefrompng($file);
                break;
            case 'bmp':
            case 'x-ms-bmp':
                $img = @$this->imagecreatefrombmp($file);
                break;
            default:
                $img = false;
                break;
        }
        return $img;
    }
    /**
     * @return void
     * @throws PrestaShopException
     */
    public function reset()
    {
        $this->__construct($this->file_name);
    }
    /**
     * @param string $savePath
     * @param int $imageQuality
     * @return void
     * @throws PrestaShopException
     */
    public function save_image($save_path, $image_quality = 100)
    {
        if (!static::is_image_resource($this->image_resized)) {
            throw new Presta_Shop_Exception('saveImage: This is not a resource.');
        }
        $file_info_array = path_info($save_path);
        clearstatcache();
        if (!is_writable($file_info_array['dirname'])) {
            throw new Presta_Shop_Exception('The path is not writable. Please check your permissions.');
        }
        $extension = strrchr($save_path, '.');
        $extension = fix_strtolower($extension);
        $error = '';
        switch ($extension) {
            case '.jpg':
            case '.jpeg':
                $this->check_interlace_image($this->is_interlace);
                if (imagetypes() & IMG_JPG) {
                    imagejpeg($this->image_resized, $save_path, $image_quality);
                } else {
                    $error = 'jpg';
                }
                break;
            case '.webp':
                if (imagetypes() & IMG_WEBP) {
                    imagewebp($this->image_resized, $save_path, $image_quality);
                } else {
                    $error = 'webp';
                }
                break;
            case '.avif':
                if (defined('IMG_AVIF') && imagetypes() & IMG_AVIF && function_exists('imageavif')) {
                    imageavif($this->image_resized, $save_path, $image_quality);
                } else {
                    $error = 'avif';
                }
                break;
            case '.gif':
                $this->check_interlace_image($this->is_interlace);
                if (imagetypes() & IMG_GIF) {
                    imagegif($this->image_resized, $save_path);
                } else {
                    $error = 'gif';
                }
                break;
            case '.png':
                $scale_quality = round($image_quality / 100 * 9);
                $invert_scale_quality = 9 - $scale_quality;
                $this->check_interlace_image($this->is_interlace);
                if (imagetypes() & IMG_PNG) {
                    imagepng($this->image_resized, $save_path, $invert_scale_quality);
                } else {
                    $error = 'png';
                }
                break;
            case '.bmp':
                file_put_contents($save_path, $this->gd2bm_pstring($this->image_resized));
                break;
            default:
                $this->error_array[] = 'This file type (' . $extension . ') is not supported. File not saved.';
                break;
        }
        if ($error != '') {
            $this->error_array[] = $error . ' support is NOT enabled. File not saved.';
        }
    }
    /**
     * @param string $fileType
     * @param int $imageQuality
     * @return void
     * @throws PrestaShopException
     */
    public function display_image($file_type = 'jpg', $image_quality = 100)
    {
        if (!static::is_image_resource($this->image_resized)) {
            throw new Presta_Shop_Exception('saveImage: This is not a resource.');
        }
        switch ($file_type) {
            case 'jpg':
            case 'jpeg':
                header('Content-type: image/jpeg');
                imagejpeg($this->image_resized, '', $image_quality);
                break;
            case 'webp':
                header('Content-type: image/webp');
                imagewebp($this->image_resized, '', $image_quality);
                break;
            case 'avif':
                if (function_exists('imageavif')) {
                    header('Content-type: image/avif');
                    imageavif($this->image_resized, '', $image_quality);
                }
                break;
            case 'gif':
                header('Content-type: image/gif');
                imagegif($this->image_resized);
                break;
            case 'png':
                header('Content-type: image/png');
                $scale_quality = round($image_quality / 100 * 9);
                $invert_scale_quality = 9 - $scale_quality;
                imagepng($this->image_resized, '', $invert_scale_quality);
                break;
            case 'bmp':
                echo 'bmp file format is not supported.';
                break;
            default:
                break;
        }
    }
    /**
     * @param bool $bool
     * @return void
     */
    public function set_transparency($bool)
    {
        $this->keep_transparency = $bool;
    }
    /**
     * @param string|array $value
     * @return void
     */
    public function set_fill_color($value)
    {
        $color_array = $this->format_color($value);
        $this->fill_color_array = $color_array;
    }
    /**
     * @param int $value
     * @return void
     */
    public function set_crop_from_top($value)
    {
        $this->crop_from_top_percent = $value;
    }
    /**
     * @return bool
     */
    public function test_gd_installed()
    {
        if (extension_loaded('gd') && function_exists('gd_info')) {
            $gd_installed = true;
        } else {
            $gd_installed = false;
        }
        return $gd_installed;
    }
    /**
     * @return bool
     */
    public function test_exif_installed()
    {
        if (extension_loaded('exif')) {
            $exif_installed = true;
        } else {
            $exif_installed = false;
        }
        return $exif_installed;
    }
    /**
     * @param GdImage $image
     * @return bool
     */
    public function test_is_image($image)
    {
        if ($image) {
            $file_is_image = true;
        } else {
            $file_is_image = false;
        }
        return $file_is_image;
    }
    /**
     * @return void
     */
    public function test_funct()
    {
        echo $this->height;
    }
    /**
     * @param bool $value
     * @return void
     */
    public function set_force_stretch($value)
    {
        $this->force_stretch = $value;
    }
    /**
     * @param string $fileName
     * @return void
     * @throws PrestaShopException
     */
    public function set_file($file_name)
    {
        self::__construct($file_name);
    }
    /**
     * @return string
     */
    public function get_file_name()
    {
        return $this->file_name;
    }
    /**
     * @return false|int
     */
    public function get_height()
    {
        return $this->height;
    }
    /**
     * @return false|int
     */
    public function get_width()
    {
        return $this->width;
    }
    /**
     * @return false|int
     */
    public function get_original_height()
    {
        return $this->height_original;
    }
    /**
     * @return false|int
     */
    public function get_original_width()
    {
        return $this->width_original;
    }
    /**
     * @return array
     */
    public function get_errors()
    {
        return $this->error_array;
    }
    /**
     * @param bool $isEnabled
     * @return void
     */
    private function check_interlace_image($is_enabled)
    {
        if ($is_enabled) {
            imageinterlace($this->image_resized, $is_enabled);
        }
    }
    /**
     * @param string|array $value
     * @return array
     */
    protected function format_color($value)
    {
        $rgb_array = [];
        if (is_array($value)) {
            if (key($value) == 0 && count($value) == 3) {
                $rgb_array['r'] = $value[0];
                $rgb_array['g'] = $value[1];
                $rgb_array['b'] = $value[2];
            } else {
                $rgb_array = $value;
            }
        } else if (fix_strtolower($value) == 'transparent') {
            $rgb_array = ['r' => 255, 'g' => 255, 'b' => 255, 'a' => 127];
        } else {
            $rgb_array = $this->hex2dec($value);
        }
        return $rgb_array;
    }
    /**
     * @param string $hex
     * @return array
     */
    public function hex2dec($hex)
    {
        $color = str_replace('#', '', $hex);
        if (strlen($color) == 3) {
            $color = $color . $color;
        }
        $rgb = ['r' => hexdec(substr($color, 0, 2)), 'g' => hexdec(substr($color, 2, 2)), 'b' => hexdec(substr($color, 4, 2)), 'a' => 0];
        return $rgb;
    }
    /**
     * @param array $colorArray
     * @return bool
     */
    private function test_color_exists($color_array)
    {
        $r = $color_array['r'];
        $g = $color_array['g'];
        $b = $color_array['b'];
        if (imagecolorexact($this->image_resized, $r, $g, $b) == -1) {
            return false;
        } else {
            return true;
        }
    }
    /**
     * @return int[]
     */
    private function find_unused_green()
    {
        $green = 255;
        do {
            $green_chroma = [0, $green, 0];
            $color_array = $this->format_color($green_chroma);
            $match = $this->test_color_exists($color_array);
            $green--;
        } while ($match == false && $green > 0);
        if (!$match) {
            $green_chroma = [0, $green, 0];
        }
        return $green_chroma;
    }
    /**
     * @param int $value
     * @param int $originalMax
     * @param bool $invert
     * @return float|int
     */
    private function invert_transparency($value, $original_max, $invert = true)
    {
        if ($value > $original_max) {
            $value = $original_max;
        }
        if ($value < 0) {
            $value = 0;
        }
        if ($invert) {
            return $original_max - $value / 100 * $original_max;
        } else {
            return $value / 100 * $original_max;
        }
    }
    /**
     * @param GdImage $src
     * @return GdImage
     */
    private function transparent_image($src)
    {
        for ($x = 0; $x < imagesx($src); ++$x) {
            for ($y = 0; $y < imagesy($src); ++$y) {
                $color = imagecolorat($src, $x, $y);
                $r = $color >> 16 & 0xff;
                $g = $color >> 8 & 0xff;
                $b = $color & 0xff;
                for ($i = 0; $i < 270; $i++) {
                    if ($r == 0 && $g == 255 && $b == 0) {
                        $trans_colour = imagecolorallocatealpha($src, 0, 0, 0, 127);
                        imagefill($src, $x, $y, $trans_colour);
                    }
                }
            }
        }
        return $src;
    }
    /**
     * @param string $needle
     * @param string $haystack
     * @return bool
     */
    public function check_string_starts_with($needle, $haystack)
    {
        return substr($haystack, 0, strlen($needle)) == $needle;
    }
    /**
     * @param GdImage $gd_image
     * @return string
     */
    private function gd2bm_pstring($gd_image)
    {
        $image_x = image_sx($gd_image);
        $image_y = image_sy($gd_image);
        $BMP = '';
        for ($y = $image_y - 1; $y >= 0; $y--) {
            $thisline = '';
            for ($x = 0; $x < $image_x; $x++) {
                $argb = $this->get_pixel_color($gd_image, $x, $y);
                $thisline .= chr($argb['blue']) . chr($argb['green']) . chr($argb['red']);
            }
            while (strlen($thisline) % 4) {
                $thisline .= "\x00";
            }
            $BMP .= $thisline;
        }
        $bmp_size = strlen($BMP) + 14 + 40;
        $BITMAPFILEHEADER = 'BM';
        $BITMAPFILEHEADER .= $this->little_endian2string($bmp_size, 4);
        $BITMAPFILEHEADER .= $this->little_endian2string(0, 2);
        $BITMAPFILEHEADER .= $this->little_endian2string(0, 2);
        $BITMAPFILEHEADER .= $this->little_endian2string(54, 4);
        $BITMAPINFOHEADER = $this->little_endian2string(40, 4);
        $BITMAPINFOHEADER .= $this->little_endian2string($image_x, 4);
        $BITMAPINFOHEADER .= $this->little_endian2string($image_y, 4);
        $BITMAPINFOHEADER .= $this->little_endian2string(1, 2);
        $BITMAPINFOHEADER .= $this->little_endian2string(24, 2);
        $BITMAPINFOHEADER .= $this->little_endian2string(0, 4);
        $BITMAPINFOHEADER .= $this->little_endian2string(0, 4);
        $BITMAPINFOHEADER .= $this->little_endian2string(2835, 4);
        $BITMAPINFOHEADER .= $this->little_endian2string(2835, 4);
        $BITMAPINFOHEADER .= $this->little_endian2string(0, 4);
        $BITMAPINFOHEADER .= $this->little_endian2string(0, 4);
        return $BITMAPFILEHEADER . $BITMAPINFOHEADER . $BMP;
    }
    /**
     * @param GdImage $img
     * @param int $x
     * @param int $y
     * @return array|false
     */
    private function get_pixel_color($img, $x, $y)
    {
        if (!static::is_image_resource($img)) {
            return false;
        }
        return @image_colors_for_index($img, @image_color_at($img, $x, $y));
    }
    /**
     * @param int $number
     * @param int $minbytes
     * @return string
     */
    private function little_endian2string($number, $minbytes = 1)
    {
        $intstring = '';
        while ($number > 0) {
            $intstring = $intstring . chr($number & 255);
            $number >>= 8;
        }
        return str_pad($intstring, $minbytes, "\x00", STR_PAD_RIGHT);
    }
    /**
     * @param string $filename
     * @return false|GdImage|resource
     */
    private function image_create_from_bmp($filename)
    {
        if (!$f1 = fopen($filename, 'rb')) {
            return false;
        }
        $FILE = unpack('vfile_type/Vfile_size/Vreserved/Vbitmap_offset', fread($f1, 14));
        if ($FILE['file_type'] != 19778) {
            return false;
        }
        $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' . '/Vcompression/Vsize_bitmap/Vhoriz_resolution' . '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));
        $BMP['colors'] = pow(2, $BMP['bits_per_pixel']);
        if ($BMP['size_bitmap'] == 0) {
            $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
        }
        $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
        $BMP['decal'] = $BMP['width'] * $BMP['bytes_per_pixel'] / 4;
        $BMP['decal'] -= floor($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] = 4 - 4 * $BMP['decal'];
        if ($BMP['decal'] == 4) {
            $BMP['decal'] = 0;
        }
        $PALETTE = [];
        if ($BMP['colors'] < 16777216) {
            $PALETTE = unpack('V' . $BMP['colors'], fread($f1, $BMP['colors'] * 4));
        }
        $IMG = fread($f1, $BMP['size_bitmap']);
        $VIDE = chr(0);
        $res = imagecreatetruecolor($BMP['width'], $BMP['height']);
        $P = 0;
        $Y = $BMP['height'] - 1;
        while ($Y >= 0) {
            $X = 0;
            while ($X < $BMP['width']) {
                if ($BMP['bits_per_pixel'] == 24) {
                    $COLOR = unpack('V', substr($IMG, $P, 3) . $VIDE);
                } elseif ($BMP['bits_per_pixel'] == 16) {
                    $COLOR = unpack('v', substr($IMG, $P, 2));
                    $blue = ($COLOR[1] & 0x1f) << 3;
                    $green = ($COLOR[1] & 0x7e0) >> 3;
                    $red = ($COLOR[1] & 0xf800) >> 8;
                    $COLOR[1] = $red * 65536 + $green * 256 + $blue;
                } elseif ($BMP['bits_per_pixel'] == 8) {
                    $COLOR = unpack('n', $VIDE . substr($IMG, $P, 1));
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 4) {
                    $COLOR = unpack('n', $VIDE . substr($IMG, floor($P), 1));
                    if ($P * 2 % 2 == 0) {
                        $COLOR[1] = $COLOR[1] >> 4;
                    } else {
                        $COLOR[1] = $COLOR[1] & 0xf;
                    }
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif ($BMP['bits_per_pixel'] == 1) {
                    $COLOR = unpack('n', $VIDE . substr($IMG, floor($P), 1));
                    if ($P * 8 % 8 == 0) {
                        $COLOR[1] = $COLOR[1] >> 7;
                    } elseif ($P * 8 % 8 == 1) {
                        $COLOR[1] = ($COLOR[1] & 0x40) >> 6;
                    } elseif ($P * 8 % 8 == 2) {
                        $COLOR[1] = ($COLOR[1] & 0x20) >> 5;
                    } elseif ($P * 8 % 8 == 3) {
                        $COLOR[1] = ($COLOR[1] & 0x10) >> 4;
                    } elseif ($P * 8 % 8 == 4) {
                        $COLOR[1] = ($COLOR[1] & 0x8) >> 3;
                    } elseif ($P * 8 % 8 == 5) {
                        $COLOR[1] = ($COLOR[1] & 0x4) >> 2;
                    } elseif ($P * 8 % 8 == 6) {
                        $COLOR[1] = ($COLOR[1] & 0x2) >> 1;
                    } elseif ($P * 8 % 8 == 7) {
                        $COLOR[1] = $COLOR[1] & 0x1;
                    }
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } else {
                    return false;
                }
                imagesetpixel($res, $X, $Y, $COLOR[1]);
                $X++;
                $P += $BMP['bytes_per_pixel'];
            }
            $Y--;
            $P += $BMP['decal'];
        }
        fclose($f1);
        return $res;
    }
    /**
     * @return void
     */
    public function __destruct()
    {
        if (static::is_image_resource($this->image_resized)) {
            imagedestroy($this->image_resized);
        }
    }
    /**
     * Returns true, if $image is either resource, or GdImage
     * @param resource|GdImage|mixed $image
     * @return bool
     */
    private static function is_image_resource($image)
    {
        if (is_null($image)) {
            return false;
        }
        if (is_resource($image)) {
            return true;
        }
        /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
        if (class_exists('GdImage') && $image instanceof Gd_Image) {
            return true;
        }
        return false;
    }
}