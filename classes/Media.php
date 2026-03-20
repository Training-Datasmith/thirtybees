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
 * Class MediaCore
 */
class Media_Core
{
    public const FAVICON = 1;
    public const FAVICON_57 = 2;
    public const FAVICON_72 = 3;
    public const FAVICON_114 = 4;
    public const FAVICON_144 = 5;
    public const FAVICON_192 = 7;
    public const FAVICON_STORE_ICON = 6;
    /**
     * @var array[]
     */
    public static $jquery_ui_dependencies = ['ui.core' => ['fileName' => 'jquery.ui.core.min.js', 'dependencies' => [], 'theme' => true], 'ui.widget' => ['fileName' => 'jquery.ui.widget.min.js', 'dependencies' => [], 'theme' => false], 'ui.mouse' => ['fileName' => 'jquery.ui.mouse.min.js', 'dependencies' => ['ui.core', 'ui.widget'], 'theme' => false], 'ui.position' => ['fileName' => 'jquery.ui.position.min.js', 'dependencies' => [], 'theme' => false], 'ui.draggable' => ['fileName' => 'jquery.ui.draggable.min.js', 'dependencies' => ['ui.core', 'ui.widget', 'ui.mouse'], 'theme' => false], 'ui.droppable' => ['fileName' => 'jquery.ui.droppable.min.js', 'dependencies' => ['ui.core', 'ui.widget', 'ui.mouse', 'ui.draggable'], 'theme' => false], 'ui.resizable' => ['fileName' => 'jquery.ui.resizable.min.js', 'dependencies' => ['ui.core', 'ui.widget', 'ui.mouse'], 'theme' => true], 'ui.selectable' => ['fileName' => 'jquery.ui.selectable.min.js', 'dependencies' => ['ui.core', 'ui.widget', 'ui.mouse'], 'theme' => true], 'ui.sortable' => ['fileName' => 'jquery.ui.sortable.min.js', 'dependencies' => ['ui.core', 'ui.widget', 'ui.mouse'], 'theme' => true], 'ui.autocomplete' => ['fileName' => 'jquery.ui.autocomplete.min.js', 'dependencies' => ['ui.core', 'ui.widget', 'ui.position', 'ui.menu'], 'theme' => true], 'ui.button' => ['fileName' => 'jquery.ui.button.min.js', 'dependencies' => ['ui.core', 'ui.widget'], 'theme' => true], 'ui.dialog' => ['fileName' => 'jquery.ui.dialog.min.js', 'dependencies' => ['ui.core', 'ui.widget', 'ui.position', 'ui.button'], 'theme' => true], 'ui.menu' => ['fileName' => 'jquery.ui.menu.min.js', 'dependencies' => ['ui.core', 'ui.widget', 'ui.position'], 'theme' => true], 'ui.slider' => ['fileName' => 'jquery.ui.slider.min.js', 'dependencies' => ['ui.core', 'ui.widget', 'ui.mouse'], 'theme' => true], 'ui.spinner' => ['fileName' => 'jquery.ui.spinner.min.js', 'dependencies' => ['ui.core', 'ui.widget', 'ui.button'], 'theme' => true], 'ui.tabs' => ['fileName' => 'jquery.ui.tabs.min.js', 'dependencies' => ['ui.core', 'ui.widget'], 'theme' => true], 'ui.datepicker' => ['fileName' => 'jquery.ui.datepicker.min.js', 'dependencies' => ['ui.core'], 'theme' => true], 'ui.progressbar' => ['fileName' => 'jquery.ui.progressbar.min.js', 'dependencies' => ['ui.core', 'ui.widget'], 'theme' => true], 'ui.tooltip' => ['fileName' => 'jquery.ui.tooltip.min.js', 'dependencies' => ['ui.core', 'ui.widget', 'ui.position', 'effects.core'], 'theme' => true], 'ui.accordion' => ['fileName' => 'jquery.ui.accordion.min.js', 'dependencies' => ['ui.core', 'ui.widget', 'effects.core'], 'theme' => true], 'effects.core' => ['fileName' => 'jquery.effects.core.min.js', 'dependencies' => [], 'theme' => false], 'effects.blind' => ['fileName' => 'jquery.effects.blind.min.js', 'dependencies' => ['effects.core'], 'theme' => false], 'effects.bounce' => ['fileName' => 'jquery.effects.bounce.min.js', 'dependencies' => ['effects.core'], 'theme' => false], 'effects.clip' => ['fileName' => 'jquery.effects.clip.min.js', 'dependencies' => ['effects.core'], 'theme' => false], 'effects.drop' => ['fileName' => 'jquery.effects.drop.min.js', 'dependencies' => ['effects.core'], 'theme' => false], 'effects.explode' => ['fileName' => 'jquery.effects.explode.min.js', 'dependencies' => ['effects.core'], 'theme' => false], 'effects.fade' => ['fileName' => 'jquery.effects.fade.min.js', 'dependencies' => ['effects.core'], 'theme' => false], 'effects.fold' => ['fileName' => 'jquery.effects.fold.min.js', 'dependencies' => ['effects.core'], 'theme' => false], 'effects.highlight' => ['fileName' => 'jquery.effects.highlight.min.js', 'dependencies' => ['effects.core'], 'theme' => false], 'effects.pulsate' => ['fileName' => 'jquery.effects.pulsate.min.js', 'dependencies' => ['effects.core'], 'theme' => false], 'effects.scale' => ['fileName' => 'jquery.effects.scale.min.js', 'dependencies' => ['effects.core'], 'theme' => false], 'effects.shake' => ['fileName' => 'jquery.effects.shake.min.js', 'dependencies' => ['effects.core'], 'theme' => false], 'effects.slide' => ['fileName' => 'jquery.effects.slide.min.js', 'dependencies' => ['effects.core'], 'theme' => false], 'effects.transfer' => ['fileName' => 'jquery.effects.transfer.min.js', 'dependencies' => ['effects.core'], 'theme' => false]];
    /**
     * @var string pattern used in replaceByAbsoluteURL
     */
    public static $pattern_callback = '#(url\((?![\'"]?(?:data:|//|https?:))(?:\'|")?)([^\)\'"]*)(?=[\'"]?\))#s';
    /**
     * @var string pattern used in packJSinHTML
     */
    public static $pattern_js = '/(<\s*script(?:\s+[^>]*(?:javascript|src)[^>]*)?\s*>)(.*)(<\s*\/script\s*[^>]*>)/Uims';
    /**
     * @var array list of javascript definitions
     */
    protected static $js_def = [];
    /**
     * @var array list of javascript inline scripts
     */
    protected static $inline_script = [];
    /**
     * @var array list of javascript external scripts
     */
    protected static $inline_script_src = [];
    /**
     * @var string used for preg_replace_callback parameter (avoid global)
     */
    protected static $current_css_file;
    /**
     * @var string
     */
    protected static $pattern_keepinline = 'data-keepinline';
    /**
     * @param string $htmlContent
     *
     * @return false|string
     *
     * @throws PrestaShopException
     */
    public static function minify_html($html_content): string|array|false
    {
        if (strlen($html_content) > 0) {
            // replace UTF-8 encoding of a NO-BREAK SPACE  with html entity &nbsp;
            $html_content = str_replace(chr(194) . chr(160), '&nbsp;', $html_content);
            // invoke minifier module
            $minified_content = (string) Hook::get_first_response('actionMinifyHtml', ['html' => $html_content]);
            $minified_content = trim($minified_content);
            return $minified_content ?: $html_content;
        }
        return false;
    }
    public static function minify_htm_lpreg_callback(array $preg_matches): string
    {
        $args = [];
        preg_match_all('/[a-zA-Z0-9]+=[\"\\\'][^\"\\\']*[\"\\\']/is', (string) $preg_matches[2], $args);
        $args = $args[0];
        sort($args);
        // if there is no args in the balise, we don't write a space (avoid previous : <title >, now : <title>)
        if (empty($args)) {
            return $preg_matches[1] . '>';
        }
        return $preg_matches[1] . ' ' . implode(' ', $args) . '>';
    }
    /**
     * @param string $htmlContent
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function pack_j_sin_html($html_content)
    {
        // continue only if some javascript minification module is installed
        static $enabled = null;
        if (is_null($enabled)) {
            $enabled = !!Hook::get_hook_module_exec_list('actionMinifyJs');
        }
        if (!$enabled) {
            return $html_content;
        }
        if (strlen($html_content) > 0) {
            $html_content_copy = $html_content;
            if (!preg_match('/' . Media::$pattern_keepinline . '/', $html_content)) {
                $html_content = preg_replace_callback(Media::$pattern_js, ['Media', 'packJSinHTMLpregCallback'], $html_content, Media::get_back_track_limit());
                // If the string is too big preg_replace return an error
                // In this case, we don't compress the content
                if (preg_last_error() == PREG_BACKTRACK_LIMIT_ERROR) {
                    trigger_error('ERROR: PREG_BACKTRACK_LIMIT_ERROR in function packJSinHTML', E_USER_NOTICE);
                    return $html_content_copy;
                }
            }
            return $html_content;
        }
        return '';
    }
    /**
     * @return int|null|string
     */
    public static function get_back_track_limit()
    {
        static $limit = null;
        if ($limit === null) {
            $limit = @ini_get('pcre.backtrack_limit');
            if (!$limit) {
                $limit = -1;
            }
        }
        return $limit;
    }
    /**
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function pack_j_sin_htm_lpreg_callback(array $preg_matches)
    {
        if (!trim((string) $preg_matches[2])) {
            return $preg_matches[0];
        }
        $preg_matches[1] = $preg_matches[1] . '/* <![CDATA[ */';
        $preg_matches[2] = Media::pack_js($preg_matches[2]);
        $preg_matches[count($preg_matches) - 1] = '/* ]]> */' . $preg_matches[count($preg_matches) - 1];
        unset($preg_matches[0]);
        return implode('', $preg_matches);
    }
    /**
     * @param string $jsContent
     * @param bool $addSeparators
     *
     *
     * @throws PrestaShopException
     */
    public static function pack_js($js_content, $add_separators = true): string
    {
        if ($js_content) {
            // invoke minifier module
            $minified_content = Hook::get_first_response('actionMinifyJs', ['js' => $js_content]);
            if ($minified_content !== null) {
                $js_content = (string) $minified_content;
            }
        }
        $js_content = trim((string) $js_content, "; \t\n\r\x00\v");
        if ($js_content && $add_separators) {
            return ';' . $js_content . ';';
        }
        return $js_content;
    }
    /**
     *
     * @return string|false
     * @throws PrestaShopException
     */
    public static function replace_by_absolute_url(array $matches)
    {
        if (array_key_exists(1, $matches) && array_key_exists(2, $matches)) {
            if (!preg_match('/^(?:https?:)?\/\//iUs', (string) $matches[2])) {
                $protocol_link = Tools::get_current_url_protocol_prefix();
                $sep = '/';
                $tmp = $matches[2][0] == $sep ? $matches[2] : dirname(Media::$current_css_file) . $sep . ltrim((string) $matches[2], $sep);
                $server = Tools::get_media_server($tmp);
                return $matches[1] . $protocol_link . $server . $tmp;
            }
            return $matches[0];
        }
        return false;
    }
    /**
     * return jquery path.
     *
     * @param string|null $version
     * @param string|null $folder
     * @param bool $minifier
     *
     * @return false|array
     */
    public static function get_jquery_path($version = null, $folder = null, $minifier = true): false|array
    {
        $add_no_conflict = false;
        if ($version === null) {
            $version = _PS_JQUERY_VERSION_;
        } elseif (preg_match('/^([0-9\.]+)$/Ui', $version)) {
            $add_no_conflict = true;
        } else {
            return false;
        }
        if ($folder === null) {
            $folder = _PS_JS_DIR_ . 'jquery/';
        }
        //set default folder
        //check if file exists
        $file = $folder . 'jquery-' . $version . ($minifier ? '.min.js' : '.js');
        $file_path = static::get_local_media_file_path($file);
        $return = [];
        if ($file_path) {
            $return[] = Media::get_js_path($file);
        } else {
            $return[] = Media::get_js_path(Tools::get_current_url_protocol_prefix() . 'ajax.googleapis.com/ajax/libs/jquery/' . $version . '/jquery' . ($minifier ? '.min.js' : '.js'));
        }
        if ($add_no_conflict) {
            $return[] = Media::get_js_path(Context::get_context()->shop->get_base_url(true, false) . _PS_JS_DIR_ . 'jquery/jquery.noConflict.php?version=' . $version);
        }
        //added query migrate for compatibility with new version of jquery will be removed in ps 1.6
        $return[] = Media::get_js_path(_PS_JS_DIR_ . 'jquery/jquery-migrate-1.2.1.min.js');
        return $return;
    }
    /**
     * addJS return javascript path
     *
     * @param string $jsUri
     *
     * @return string
     */
    public static function get_js_path($js_uri)
    {
        return Media::get_media_path($js_uri);
    }
    /**
     * @param int $type
     * @param int|null $idShop
     *
     *
     * @throws PrestaShopException
     */
    public static function get_favicon_path($type = self::FAVICON, $id_shop = null): string
    {
        if (!$id_shop) {
            $id_shop = (int) Context::get_context()->shop->id;
        }
        $store_path = Shop::is_feature_active() ? '-' . (int) $id_shop : '';
        switch ($type) {
            case static::FAVICON_57:
                $path = 'favicon_57';
                $ext = 'png';
                break;
            case static::FAVICON_72:
                $path = 'favicon_72';
                $ext = 'png';
                break;
            case static::FAVICON_114:
                $path = 'favicon_114';
                $ext = 'png';
                break;
            case static::FAVICON_144:
                $path = 'favicon_144';
                $ext = 'png';
                break;
            case static::FAVICON_192:
                $path = 'favicon_192';
                $ext = 'png';
                break;
            default:
                // Default favicon
                $path = 'favicon';
                $ext = 'ico';
                break;
        }
        // Copy shop favicon if it does not exist
        if (Shop::is_feature_active() && !file_exists(_PS_IMG_DIR_ . "{$path}{$store_path}.{$ext}")) {
            @copy(_PS_IMG_DIR_ . "{$path}.{$ext}", _PS_IMG_DIR_ . "{$path}{$store_path}.{$ext}");
        }
        return (string) Media::get_media_path(_PS_IMG_DIR_ . "{$path}.{$ext}");
    }
    /**
     * @param string $mediaUri
     * @param string|null $cssMediaType
     *
     * @return false|string|array
     */
    public static function get_media_path($media_uri, $css_media_type = null)
    {
        if (is_array($media_uri) || empty($media_uri)) {
            return false;
        }
        $url_data = parse_url($media_uri);
        if (!is_array($url_data)) {
            return false;
        }
        if (!array_key_exists('host', $url_data)) {
            $file_path = static::get_local_media_file_path($media_uri);
            if (!$file_path) {
                return false;
            }
            $media_uri = '/' . ltrim(str_replace(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, _PS_ROOT_DIR_), __PS_BASE_URI__, $media_uri), '/\\');
            $media_uri = str_replace('//', '/', $media_uri);
        }
        if ($css_media_type) {
            return [$media_uri => $css_media_type];
        }
        return $media_uri;
    }
    /**
     * return jqueryUI component path.
     *
     * @param bool $checkDependencies
     *
     */
    public static function get_jquery_ui_path(string $component, string $theme, $check_dependencies): array
    {
        $ui_path = ['js' => [], 'css' => []];
        $folder = _PS_JS_DIR_ . 'jquery/ui/';
        $file = 'jquery.' . $component . '.min.js';
        $ui_tmp = [];
        if (isset(Media::$jquery_ui_dependencies[$component]) && Media::$jquery_ui_dependencies[$component]['theme'] && $check_dependencies) {
            $theme_css = Media::get_css_path($folder . 'themes/' . $theme . '/jquery.ui.theme.css');
            $comp_css = Media::get_css_path($folder . 'themes/' . $theme . '/jquery.' . $component . '.css');
            if ($theme_css) {
                $ui_path['css'] = array_merge($ui_path['css'], $theme_css);
            }
            if ($comp_css) {
                $ui_path['css'] = array_merge($ui_path['css'], $comp_css);
            }
        }
        if ($check_dependencies && array_key_exists($component, static::$jquery_ui_dependencies)) {
            foreach (static::$jquery_ui_dependencies[$component]['dependencies'] as $dependency) {
                $ui_tmp[] = Media::get_jquery_ui_path($dependency, $theme, false);
                if (static::$jquery_ui_dependencies[$dependency]['theme']) {
                    $dep_css = Media::get_css_path($folder . 'themes/' . $theme . '/jquery.' . $dependency . '.css');
                }
                if (isset($dep_css) && $dep_css) {
                    $ui_path['css'] = array_merge($ui_path['css'], $dep_css);
                }
            }
        }
        $file_path = static::get_local_media_file_path($folder . $file);
        if ($file_path) {
            if (!empty($ui_tmp)) {
                foreach ($ui_tmp as $ui) {
                    if (!empty($ui['js'])) {
                        $ui_path['js'][] = $ui['js'];
                    }
                    if (!empty($ui['css'])) {
                        $ui_path['css'][] = $ui['css'];
                    }
                }
                $ui_path['js'][] = Media::get_js_path($folder . $file);
            } else {
                $ui_path['js'] = Media::get_js_path($folder . $file);
            }
        }
        //add i18n file for datepicker
        if ($component == 'ui.datepicker') {
            if (!is_array($ui_path['js'])) {
                $ui_path['js'] = [$ui_path['js']];
            }
            $ui_path['js'][] = Media::get_js_path($folder . 'i18n/jquery.ui.datepicker-' . Context::get_context()->language->iso_code . '.js');
        }
        return $ui_path;
    }
    /**
     * addCSS return stylesheet path.
     *
     * @param string $cssUri
     * @param string $cssMediaType
     * @param bool $needRtl
     *
     * @return array
     */
    public static function get_css_path($css_uri, $css_media_type = 'all', $need_rtl = true)
    {
        // Search and load rtl css file if it's not originally rtl
        if ($need_rtl && Context::get_context()->language->is_rtl) {
            $css_uri_rtl = preg_replace('/(^[^.].*)(\.css)$/', '$1_rtl.css', $css_uri);
            $rtl_media = Media::get_media_path($css_uri_rtl, $css_media_type);
            if ($rtl_media != false) {
                return $rtl_media;
            }
        }
        return Media::get_media_path($css_uri, $css_media_type);
    }
    /**
     * return jquery plugin path.
     *
     * @param string|null $folder
     * @return array|false
     */
    public static function get_jquery_plugin_path(string $name, $folder = null): false|array
    {
        $plugin_path = ['js' => [], 'css' => []];
        if ($folder === null) {
            $folder = _PS_JS_DIR_ . 'jquery/plugins/';
        }
        $file = 'jquery.' . $name . '.js';
        if (static::get_local_media_file_path($folder . $file)) {
            $plugin_path['js'] = Media::get_js_path($folder . $file);
        } elseif (static::get_local_media_file_path($folder . $name . '/' . $file)) {
            $plugin_path['js'] = Media::get_js_path($folder . $name . '/' . $file);
        } else {
            return false;
        }
        $plugin_path['css'] = Media::get_jquery_plugin_css_path($name, $folder);
        return $plugin_path;
    }
    /**
     * return jquery plugin css path if exist.
     *
     * @param string|null $folder
     * @return array|false
     */
    public static function get_jquery_plugin_css_path(string $name, $folder = null)
    {
        if ($folder === null) {
            $folder = _PS_JS_DIR_ . 'jquery/plugins/';
        }
        //set default folder
        $file = 'jquery.' . $name . '.css';
        if (static::get_local_media_file_path($folder . $file)) {
            return Media::get_css_path($folder . $file);
        }
        if (static::get_local_media_file_path($folder . $name . '/' . $file)) {
            return Media::get_css_path($folder . $name . '/' . $file);
        }
        return false;
    }
    /**
     * Combine Compress and Cache CSS (ccc) calls
     *
     * @param array $cachePath
     *
     * @return array processed css_files
     * @throws PrestaShopException
     */
    public static function ccc_css(array $css_files, $cache_path = null): array
    {
        //inits
        $css_files_by_media = [];
        $external_css_files = [];
        $compressed_css_files = [];
        $compressed_css_files_not_found = [];
        $compressed_css_files_infos = [];
        $protocol_link = Tools::get_current_url_protocol_prefix();
        //if cache_path not specified, set curent theme cache folder
        $cache_path = $cache_path ?: _PS_THEME_DIR_ . 'cache/';
        // group css files by media
        foreach ($css_files as $filename => $media) {
            if (!array_key_exists($media, $css_files_by_media)) {
                $css_files_by_media[$media] = [];
            }
            $infos = [];
            $infos['uri'] = $filename;
            $url_data = parse_url((string) $filename);
            if (array_key_exists('host', $url_data)) {
                $external_css_files[$filename] = $media;
                continue;
            }
            $infos['path'] = _PS_ROOT_DIR_ . Tools::str_replace_once(__PS_BASE_URI__, '/', $url_data['path']);
            if (!file_exists($infos['path'])) {
                $infos['path'] = _PS_CORE_DIR_ . Tools::str_replace_once(__PS_BASE_URI__, '/', $url_data['path']);
            }
            $css_files_by_media[$media]['files'][] = $infos;
            if (!array_key_exists('date', $css_files_by_media[$media])) {
                $css_files_by_media[$media]['date'] = 0;
            }
            if (file_exists($infos['path'])) {
                $css_files_by_media[$media]['date'] = max((int) @filemtime($infos['path']), $css_files_by_media[$media]['date']);
            }
            if (!array_key_exists($media, $compressed_css_files_infos)) {
                $compressed_css_files_infos[$media] = ['key' => ''];
            }
            $compressed_css_files_infos[$media]['key'] .= $filename;
        }
        // get compressed css file infos
        $version = (int) Configuration::get('PS_CCCCSS_VERSION');
        foreach ($compressed_css_files_infos as $media => &$info) {
            $key = md5($info['key'] . $protocol_link);
            $filename = $cache_path . 'v_' . $version . '_' . $key . '_' . $media . '.css';
            $mtime = file_exists($filename) ? (int) @filemtime($filename) : 0;
            $info = ['key' => $key, 'date' => $mtime];
        }
        foreach ($css_files_by_media as $media => $media_infos) {
            if ($media_infos['date'] <= $compressed_css_files_infos[$media]['date']) {
                continue;
            }
            if (!$compressed_css_files_infos[$media]['date']) {
                continue;
            }
            Configuration::update_value('PS_CCCCSS_VERSION', ++$version);
            break;
        }
        // aggregate and compress css files content, write new caches files
        $import_url = [];
        foreach ($css_files_by_media as $media => $media_infos) {
            $cache_filename = $cache_path . 'v_' . $version . '_' . $compressed_css_files_infos[$media]['key'] . '_' . $media . '.css';
            if ($media_infos['date'] > $compressed_css_files_infos[$media]['date']) {
                $compressed_css_files[$media] = '';
                foreach ($media_infos['files'] as $file_infos) {
                    if (file_exists($file_infos['path'])) {
                        $compressed_css_files[$media] .= Media::minify_css(file_get_contents($file_infos['path']), $file_infos['uri'], $import_url);
                    } else {
                        $compressed_css_files_not_found[] = $file_infos['path'];
                    }
                }
                if (!empty($compressed_css_files_not_found)) {
                    $content = '/* WARNING ! file(s) not found : "' . implode(',', $compressed_css_files_not_found) . '" */' . "\n" . $compressed_css_files[$media];
                } else {
                    $content = $compressed_css_files[$media];
                }
                $content = '@charset "UTF-8";' . "\n" . $content;
                $content = implode('', $import_url) . $content;
                file_put_contents($cache_filename, $content);
                chmod($cache_filename, 0777);
            }
            $compressed_css_files[$media] = $cache_filename;
        }
        // rebuild the original css_files array
        $css_files = [];
        foreach ($compressed_css_files as $media => $filename) {
            $url = str_replace(_PS_THEME_DIR_, _THEMES_DIR_ . _THEME_NAME_ . '/', $filename);
            $css_files[$protocol_link . Tools::get_media_server($url) . $url] = $media;
        }
        return array_merge($external_css_files, $css_files);
    }
    /**
     * @param string $cssContent
     * @param bool $fileuri
     * @param array $importUrl
     *
     * @return string|false
     *
     * @throws PrestaShopException
     */
    public static function minify_css($css_content, $fileuri = false, &$import_url = []): string|false
    {
        Media::$current_css_file = $fileuri;
        if (strlen($css_content) > 0) {
            $minified_content = (string) Hook::get_first_response('actionMinifyCss', ['css' => $css_content, 'fileuri' => $fileuri, 'importUrl' => &$import_url]);
            if ($minified_content) {
                $css_content = $minified_content;
            }
            $limit = Media::get_back_track_limit();
            $css_content = preg_replace_callback(Media::$pattern_callback, ['Media', 'replaceByAbsoluteURL'], $css_content, $limit);
            $css_content = str_replace('\'images_ie/', '\'images/', $css_content);
            $css_content = preg_replace_callback('#(AlphaImageLoader\(src=\')([^\']*\',)#s', ['Media', 'replaceByAbsoluteURL'], $css_content);
            // Store all import url
            preg_match_all('#@(import|charset) .*?;#i', (string) $css_content, $m);
            for ($i = 0, $total = count($m[0]); $i < $total; $i++) {
                if (isset($m[1][$i]) && $m[1][$i] == 'import') {
                    $import_url[] = $m[0][$i];
                }
                $css_content = str_replace($m[0][$i], '', $css_content);
            }
            return trim((string) $css_content);
        }
        return false;
    }
    /**
     * Splits stylesheets that go beyond the IE limit of 4096 selectors
     *
     * @param array $compiledCss
     * @param string $cachePath
     * @param bool $refresh
     *
     * @return array processed css_files
     */
    public static function ie_css_splitter($compiled_css, $cache_path, $refresh = false): array
    {
        Tools::display_as_deprecated();
        return [];
    }
    /**
     * Combine Compress and Cache (ccc) JS calls
     *
     * @param array $jsFiles
     *
     * @return array processed js_files
     *
     * @throws PrestaShopException
     */
    public static function ccc_js($js_files): array
    {
        //inits
        $compressed_js_files_not_found = [];
        $js_files_infos = [];
        $js_files_date = 0;
        $compressed_js_filename = '';
        $js_external_files = [];
        $protocol_link = Tools::get_current_url_protocol_prefix();
        $cache_path = _PS_THEME_DIR_ . 'cache/';
        // get js files infos
        foreach ($js_files as $filename) {
            if (Validate::is_absolute_url($filename)) {
                $js_external_files[] = $filename;
            } else {
                $infos = [];
                $infos['uri'] = $filename;
                $url_data = parse_url((string) $filename);
                $infos['path'] = _PS_ROOT_DIR_ . Tools::str_replace_once(__PS_BASE_URI__, '/', $url_data['path']);
                if (!@filemtime($infos['path'])) {
                    $infos['path'] = _PS_CORE_DIR_ . Tools::str_replace_once(__PS_BASE_URI__, '/', $url_data['path']);
                }
                $js_files_infos[] = $infos;
                $js_files_date = max((int) @filemtime($infos['path']), $js_files_date);
                $compressed_js_filename .= $filename;
            }
        }
        // get compressed js file infos
        $compressed_js_filename = md5($compressed_js_filename);
        $version = (int) Configuration::get('PS_CCCJS_VERSION');
        $compressed_js_path = $cache_path . 'v_' . $version . '_' . $compressed_js_filename . '.js';
        $compressed_js_file_date = file_exists($compressed_js_path) ? (int) @filemtime($compressed_js_path) : 0;
        // aggregate and compress js files content, write new caches files
        if ($js_files_date > $compressed_js_file_date) {
            if ($compressed_js_file_date) {
                Configuration::update_value('PS_CCCJS_VERSION', ++$version);
            }
            $compressed_js_path = $cache_path . 'v_' . $version . '_' . $compressed_js_filename . '.js';
            $content = '';
            foreach ($js_files_infos as $file_infos) {
                $file_path = $file_infos['path'];
                if (file_exists($file_path)) {
                    $tmp_content = file_get_contents($file_infos['path']);
                    if (preg_match('@\.(min|pack)\.[^/]+$@', $file_infos['path'], $matches)) {
                        $content_to_add = preg_replace('/\/\/@\ssourceMappingURL\=[_a-zA-Z0-9-.]+\.' . $matches[1] . '\.map\s+/', '', $tmp_content);
                        $content_to_add = trim((string) $content_to_add, "; \t\n\r\x00\v");
                    } else {
                        $content_to_add = Media::pack_js($tmp_content, false);
                    }
                    if ($content_to_add) {
                        if ($content) {
                            $content .= ";\n";
                        }
                        $content .= $content_to_add;
                    }
                } else {
                    $compressed_js_files_not_found[] = $file_infos['path'];
                }
            }
            if (!empty($compressed_js_files_not_found)) {
                $content = '/* WARNING ! file(s) not found : "' . implode(',', $compressed_js_files_not_found) . '" */' . "\n" . $content;
            }
            file_put_contents($compressed_js_path, $content);
            chmod($compressed_js_path, 0777);
        }
        // rebuild the original js_files array
        $url = '';
        if (str_contains($compressed_js_path, _PS_ROOT_DIR_)) {
            $url = str_replace(_PS_ROOT_DIR_ . '/', __PS_BASE_URI__, $compressed_js_path);
        }
        if (str_contains($compressed_js_path, _PS_CORE_DIR_)) {
            $url = str_replace(_PS_CORE_DIR_ . '/', __PS_BASE_URI__, $compressed_js_path);
        }
        return array_merge([$protocol_link . Tools::get_media_server($url) . $url], $js_external_files);
    }
    /**
     * Clear theme cache
     *
     *
     * @throws PrestaShopException
     */
    public static function clear_cache(): void
    {
        Shop_Maintenance::clean_old_theme_cache_files();
        $version = (int) Configuration::get('PS_CCCJS_VERSION');
        Configuration::update_value('PS_CCCJS_VERSION', ++$version);
        $version = (int) Configuration::get('PS_CCCCSS_VERSION');
        Configuration::update_value('PS_CCCCSS_VERSION', ++$version);
    }
    /**
     * Get JS definitions
     *
     * @return array JS definitions
     */
    public static function get_js_def()
    {
        ksort(Media::$js_def);
        return Media::$js_def;
    }
    /**
     * Get JS inline script
     *
     * @return array inline script
     */
    public static function get_inline_script()
    {
        return Media::$inline_script;
    }
    /**
     * Add a new javascript definition at bottom of page
     *
     * @param string|int|bool|float|array $jsDef
     */
    public static function add_js_def($js_def): void
    {
        if (is_array($js_def)) {
            foreach ($js_def as $key => $js) {
                Media::$js_def[$key] = $js;
            }
        } elseif ($js_def) {
            Media::$js_def[] = $js_def;
        }
    }
    /**
     * Add a new javascript definition from a capture at bottom of page
     *
     * @param string|string[] $params
     * @param string $content
     * @param Smarty $smarty
     * @param bool $repeat
     */
    public static function add_js_def_l($params, $content, $smarty = null, &$repeat = false): void
    {
        if (!$repeat && isset($params) && mb_strlen($content)) {
            if (!is_array($params)) {
                $params = (array) $params;
            }
            foreach ($params as $param) {
                Media::$js_def[$param] = $content;
            }
        }
    }
    /**
     * @param string $output
     *
     * @return string
     */
    public static function defer_inline_scripts($output): ?string
    {
        /* Try to enqueue in js_files inline scripts with src but without conditionnal comments */
        $dom = new Dom_Document();
        libxml_use_internal_errors(true);
        @$dom->load_html($output);
        libxml_use_internal_errors(false);
        $scripts = $dom->get_elements_by_tag_name('script');
        if (is_object($scripts) && $scripts->length) {
            foreach ($scripts as $script) {
                /** @var DOMElement $script */
                if ($src = $script->get_attribute('src')) {
                    if (str_starts_with($src, '//')) {
                        $src = Tools::get_current_url_protocol_prefix() . substr($src, 2);
                    }
                    $patterns = ['#code\.jquery\.com/jquery-([0-9\.]+)(\.min)*\.js$#Ui', '#ajax\.googleapis\.com/ajax/libs/jquery/([0-9\.]+)/jquery(\.min)*\.js$#Ui', '#ajax\.aspnetcdn\.com/ajax/jquery/jquery-([0-9\.]+)(\.min)*\.js$#Ui', '#cdnjs\.cloudflare\.com/ajax/libs/jquery/([0-9\.]+)/jquery(\.min)*\.js$#Ui', '#/jquery-([0-9\.]+)(\.min)*\.js$#Ui'];
                    foreach ($patterns as $pattern) {
                        $matches = [];
                        if (preg_match($pattern, $src, $matches)) {
                            $minifier = $version = false;
                            if (isset($matches[2]) && $matches[2]) {
                                $minifier = (bool) $matches[2];
                            }
                            if (isset($matches[1]) && $matches[1]) {
                                $version = $matches[1];
                            }
                            if ($version) {
                                if ($version != _PS_JQUERY_VERSION_) {
                                    Context::get_context()->controller->add_jquery($version, null, $minifier);
                                }
                                Media::$inline_script_src[] = $src;
                            }
                        }
                    }
                    if (!in_array($src, Media::$inline_script_src) && !$script->get_attribute(Media::$pattern_keepinline)) {
                        Context::get_context()->controller->add_js($src);
                    }
                }
            }
        }
        return preg_replace_callback(Media::$pattern_js, ['Media', 'deferScript'], $output);
    }
    /**
     * Get all JS scripts and place it to bottom
     * To be used in callback with deferInlineScripts
     *
     * @param array $matches
     *
     * @return bool|string Empty string or original script lines
     */
    public static function defer_script($matches): false|string
    {
        if (!is_array($matches)) {
            return false;
        }
        $inline = '';
        if (isset($matches[0])) {
            $original = trim((string) $matches[0]);
        } else {
            $original = '';
        }
        if (isset($matches[2])) {
            $inline = trim($matches[2]);
        }
        /* This is an inline script, add its content to inline scripts stack then remove it from content */
        if (!empty($inline) && preg_match(Media::$pattern_js, $original) !== false && !preg_match('/' . Media::$pattern_keepinline . '/', $original) && Media::$inline_script[] = $inline) {
            return '';
        }
        /* This is an external script, if it already belongs to js_files then remove it from content */
        preg_match('/src\s*=\s*["\']?([^"\']*)[^>]/ims', $original, $results);
        if (array_key_exists(1, $results)) {
            if (str_starts_with($results[1], '//')) {
                $protocol_link = Tools::get_current_url_protocol_prefix();
                $results[1] = $protocol_link . ltrim($results[1], '/');
            }
            if (in_array($results[1], Context::get_context()->controller->js_files) || in_array($results[1], Media::$inline_script_src)) {
                return '';
            }
        }
        /* return original string because no match was found */
        return "\n" . $original;
    }
    /**
     * Returns full path to local file for $uri, or false if file does not exists
     * with Linux and Windows compatibility
     *
     * @param string $uri
     *
     * @return string|false
     */
    public static function get_local_media_file_path($uri): false|string
    {
        if (!$uri) {
            return false;
        }
        $uri = (string) $uri;
        // if file exists locally, include its modification timestamp into uri as a version parameter
        $parsed = parse_url($uri);
        if (!array_key_exists('host', $parsed) && isset($parsed['path'])) {
            $path = $parsed['path'];
            $root_dir = rtrim(str_replace('\\', '/', _PS_ROOT_DIR_), '/');
            $file_path = $root_dir . $path;
            // deleted slash separator as unnecessary in both environments
            if (file_exists($file_path) && is_file($file_path)) {
                return $file_path;
            }
            $media_uri = '/' . ltrim(str_replace($root_dir, __PS_BASE_URI__, $path), '/\\');
            if (isset($parsed['scheme'])) {
                // windows environment
                $file_path = $parsed['scheme'] . ':' . $media_uri;
            } else {
                // linux environment
                $file_path = _PS_ROOT_DIR_ . Tools::str_replace_once(__PS_BASE_URI__, '/', $media_uri);
            }
            if (file_exists($file_path) && is_file($file_path)) {
                return $file_path;
            }
        }
        return false;
    }
    /**
     * If $uri parameter refers to local asset, return uri with cache control parameter
     *
     * @param string|null $parameter
     *
     */
    public static function get_uri_with_version(string $uri, $parameter = 'v'): string
    {
        $file_path = static::get_local_media_file_path($uri);
        if ($file_path) {
            $parsed = parse_url($uri);
            $ts = filemtime($file_path);
            $cache_control = $parameter . '=' . $ts;
            if (isset($parsed['query'])) {
                return $uri . '&' . $cache_control;
            }
            return $uri . '?' . $cache_control;
        }
        return $uri;
    }
    /**
     * Get information for supported files
     *
     * @param string $type (Atm: 'images') Todo: also use this array for other types like 'documents' in future
     */
    public static function get_file_informations($type = null, $main_extension = null): false|array
    {
        $allowed_extensions = ['images' => ['jpg' => ['mimeType' => 'image/jpeg', 'extensions' => ['jpg', 'jpeg', 'jpe', 'pjpeg'], 'imageSupport' => true, 'uploadFrontOffice' => true, 'uploadBackOffice' => true], 'png' => ['mimeType' => 'image/png', 'extensions' => ['png', 'x-png'], 'imageSupport' => true, 'uploadFrontOffice' => true, 'uploadBackOffice' => true], 'gif' => ['mimeType' => 'image/gif', 'extensions' => ['gif'], 'imageSupport' => true, 'uploadFrontOffice' => true, 'uploadBackOffice' => true], 'ico' => ['mimeType' => 'image/x-icon', 'extensions' => ['ico'], 'imageSupport' => false, 'uploadFrontOffice' => false, 'uploadBackOffice' => true], 'bmp' => ['mimeType' => 'image/bmp', 'extensions' => ['bmp'], 'imageSupport' => false, 'uploadFrontOffice' => false, 'uploadBackOffice' => true], 'tiff' => ['mimeType' => 'image/tiff', 'extensions' => ['tiff'], 'imageSupport' => false, 'uploadFrontOffice' => false, 'uploadBackOffice' => true], 'svg' => ['mimeType' => 'image/svg+xml', 'extensions' => ['svg'], 'imageSupport' => false, 'uploadFrontOffice' => false, 'uploadBackOffice' => true]]];
        if (Image_Manager::server_supports_webp()) {
            $allowed_extensions['images']['webp'] = ['mimeType' => 'image/webp', 'extensions' => ['webp'], 'imageSupport' => true, 'uploadFrontOffice' => true, 'uploadBackOffice' => true];
        }
        if (Image_Manager::server_supports_avif()) {
            $allowed_extensions['images']['avif'] = ['mimeType' => 'image/avif', 'extensions' => ['avif'], 'imageSupport' => true, 'uploadFrontOffice' => true, 'uploadBackOffice' => true];
        }
        if ($type) {
            // Check if the type is defined
            if (!isset($allowed_extensions[$type])) {
                return false;
            }
            // Check if the mainExtension is defined
            if ($main_extension) {
                return $allowed_extensions[$type][$main_extension] ?? false;
            }
            return $allowed_extensions[$type];
        }
        // Strange case, where $mainExtension has been submitted but not $type
        if ($main_extension) {
            foreach ($allowed_extensions as $allowed_extension) {
                foreach ($allowed_extension as $main_extension_key => $extension_info) {
                    if ($main_extension == $main_extension_key) {
                        return $extension_info;
                    }
                }
            }
            return false;
        }
        return $allowed_extensions;
    }
}