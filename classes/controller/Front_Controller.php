<?php

declare (strict_types=1);
/**
 * 2007-2016 PrestaShop.
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
 * Class FrontControllerCore.
 */
class Front_Controller_Core extends Controller
{
    public const JS_DEF_PLACEHOLDER = 'js_def';
    /**
     * True if controller has already been initialized.
     * Prevents initializing controller more than once.
     *
     * @var bool
     */
    public static $initialized = false;
    /**
     * @deprecated Deprecated shortcuts as of 1.1.0 - Use $context->smarty instead
     *
     * @var Smarty $smarty
     */
    protected static $smarty;
    /**
     * @deprecated Deprecated shortcuts as of 1.1.0 - Use $context->cookie instead
     *
     * @var Cookie $cookie
     */
    protected static $cookie;
    /**
     * @deprecated Deprecated shortcuts as of 1.1.0 - Use $context->link instead
     *
     * @var Link $link
     */
    protected static $link;
    /**
     * @deprecated Deprecated shortcuts as of 1.1.0 - Use $context->cart instead
     *
     * @var Cart $cart
     */
    protected static $cart;
    /**
     * @var int[] Holds current customer's groups.
     */
    protected static $current_customer_groups;
    /** @var string Language ISO code */
    public $iso;
    /** @var string ORDER BY field */
    public $order_by;
    /** @var string Order way string ('ASC', 'DESC') */
    public $order_way;
    /** @var int Current page number */
    public $p;
    /** @var int Items (products) per page */
    public $n;
    /** @var bool If set to true, will redirected user to login page during init function. */
    public $auth = false;
    /**
     * If set to true, user can be logged in as guest when checking if logged in.
     *
     * @see $auth
     *
     * @var bool
     */
    public $guest_allowed = false;
    /**
     * Route of PrestaShop page to redirect to after forced login.
     *
     * @see $auth
     *
     * @var bool
     */
    public $auth_redirection = false;
    /** @var bool SSL connection flag */
    public $ssl = false;
    /** @var bool If false, does not build left page column content and hides it. */
    public $display_column_left = true;
    /** @var bool If false, does not build right page column content and hides it. */
    public $display_column_right = true;
    /** @var int */
    public $nb_items_per_page;
    /**
     * @var string|null Controller rewrite name
     */
    public $page_name;
    /** @var bool If true, switches display to restricted country page during init. */
    protected $restricted_country = false;
    /** @var bool If true, forces display to maintenance page. */
    protected $maintenance = false;
    /**
     * Controller constructor.
     *
     * @throws PrestaShopException
     *
     * @global bool $useSSL SSL connection flag
     */
    public function __construct()
    {
        $this->controller_type = 'front';
        global $use_ssl;
        parent::__construct();
        if (Configuration::get('PS_SSL_ENABLED')) {
            $this->ssl = true;
        }
        if (isset($use_ssl)) {
            $this->ssl = $use_ssl;
        } else {
            $use_ssl = $this->ssl;
        }
        if (isset($this->php_self) && is_object($this->context->theme)) {
            $columns = $this->context->theme->has_columns($this->php_self);
            // Don't use theme tables if not configured in DB
            if ($columns) {
                $this->display_column_left = $columns['left_column'];
                $this->display_column_right = $columns['right_column'];
            }
        }
    }
    /**
     * Sets and returns customer groups that the current customer(visitor) belongs to.
     *
     * @return int[]
     *
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public static function get_current_customer_groups()
    {
        if (!Group::is_feature_active()) {
            return [];
        }
        $context = Context::get_context();
        if (!isset($context->customer) || !$context->customer->id) {
            return [];
        }
        if (!is_array(static::$current_customer_groups)) {
            static::$current_customer_groups = [];
            $result = Db::read_only()->get_array((new Db_Query())->select('`id_group`')->from('customer_group')->where('`id_customer` = ' . (int) $context->customer->id));
            if ($result) {
                foreach ($result as $row) {
                    static::$current_customer_groups[] = (int) $row['id_group'];
                }
            }
        }
        return static::$current_customer_groups;
    }
    /**
     * Check if the controller is available for the current user/visitor.
     *
     * @see Controller::checkAccess()
     *
     * @return bool
     */
    public function check_access()
    {
        return true;
    }
    /**
     * Check if the current user/visitor has valid view permissions.
     *
     * @see Controller::viewAccess
     *
     * @return bool
     */
    public function view_access()
    {
        return true;
    }
    /**
     * Method that is executed after init() and checkAccess().
     * Used to process user input.
     *
     * @see Controller::run()
     */
    public function post_process()
    {
    }
    /**
     * Starts the controller process
     *
     * Overrides Controller::run() to allow full page cache
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function run(): void
    {
        if (Page_Cache::is_enabled()) {
            $debug = Configuration::get('TB_PAGE_CACHE_DEBUG');
            $cache_entry = Page_Cache::get();
            if ($cache_entry->exists()) {
                if ($debug) {
                    header('X-thirtybees-PageCache: HIT');
                }
                $this->init();
                $this->context->cookie->write();
                $content = $cache_entry->get_fresh_content();
                echo $content;
                return;
            }
            if ($debug) {
                header('X-thirtybees-PageCache: MISS');
            }
        }
        parent::run();
    }
    /**
     * Initializes common front page content: header, footer and side columns.
     *
     * @throws PrestaShopException
     */
    public function init_content(): void
    {
        $this->process();
        if (!isset($this->context->cart)) {
            $this->context->cart = new Cart();
        }
        if (!$this->use_mobile_theme()) {
            $hook_header = '';
            $favicon_template = Configuration::get('TB_SOURCE_FAVICON_CODE') ?? '';
            if (!empty(trim($favicon_template))) {
                $favicon_template = preg_replace('/\<br(\s*)?\/?\>/i', "\n", $favicon_template);
                $dom = new Dom_Document();
                $dom->load_html($favicon_template);
                $links = [];
                foreach ($dom->get_elements_by_tag_name('link') as $elem) {
                    $links[] = $elem;
                }
                foreach ($dom->get_elements_by_tag_name('meta') as $elem) {
                    $links[] = $elem;
                }
                $favicon_html = '';
                foreach ($links as $link) {
                    foreach ($link->attributes as $attribute) {
                        if ($favicon = Tools::parse_favicon_size_tag(urldecode($attribute->value))) {
                            $attribute->value = Media::get_media_path(_PS_IMG_DIR_ . "favicon/favicon_{$this->context->shop->id}_{$favicon['width']}_{$favicon['height']}.{$favicon['type']}");
                        }
                    }
                    $favicon_html .= $dom->save_html($link);
                }
                if ($favicon_html) {
                    $hook_header .= $favicon_html;
                }
                $hook_header .= '<meta name="msapplication-config" content="' . Media::get_media_path(_PS_IMG_DIR_ . "favicon/browserconfig_{$this->context->shop->id}.xml") . '">';
                $hook_header .= '<link rel="manifest" href="' . Media::get_media_path(_PS_IMG_DIR_ . "favicon/manifest_{$this->context->shop->id}.json") . '">';
            }
            if (Configuration::get('TB_EMIT_SEO_FIELDS')) {
                // append some seo fields, canonical, hrefLang, rel prev/next
                $hook_header .= $this->get_seo_fields();
            }
            $hook_header .= Hook::display_hook('displayHeader');
            // To be removed: append extra css and metas to the header hook
            $extra_code = Configuration::get_multiple([Configuration::CUSTOMCODE_METAS, Configuration::CUSTOMCODE_CSS]);
            $extra_css = $extra_code[Configuration::CUSTOMCODE_CSS] ? '<style>' . $extra_code[Configuration::CUSTOMCODE_CSS] . '</style>' : '';
            $hook_header .= $extra_code[Configuration::CUSTOMCODE_METAS] . $extra_css;
            $this->context->smarty->assign(['HOOK_HEADER' => $hook_header, 'HOOK_TOP' => Hook::display_hook('displayTop'), 'HOOK_LEFT_COLUMN' => $this->display_column_left ? Hook::display_hook('displayLeftColumn') : '', 'HOOK_RIGHT_COLUMN' => $this->display_column_right ? Hook::display_hook('displayRightColumn', ['cart' => $this->context->cart]) : '']);
        } else {
            $this->context->smarty->assign('HOOK_MOBILE_HEADER', Hook::display_hook('displayMobileHeader'));
        }
    }
    /**
     * Called before compiling common page sections (header, footer, columns).
     * Good place to modify smarty variables.
     *
     * @see FrontController::initContent()
     */
    public function process()
    {
    }
    /**
     * Checks if mobile theme is active and in use.
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function use_mobile_theme()
    {
        static $use_mobile_template = null;
        // The mobile theme must have a layout to be used
        if ($use_mobile_template === null) {
            $use_mobile_template = $this->context->get_mobile_device() && file_exists(_PS_THEME_MOBILE_DIR_ . 'layout.tpl');
        }
        return $use_mobile_template;
    }
    /**
     * Generates html for additional seo tags.
     *
     * @return string html code for the new tags
     *
     * @throws PrestaShopException
     */
    public function get_seo_fields()
    {
        $content = "\n";
        $canonical_url = $this->get_current_page_canonical_url();
        if ($canonical_url) {
            $content .= '<link rel="canonical" href="' . $canonical_url . '">' . "\n";
        }
        foreach ($this->get_current_page_href_lang_tags() as $lang) {
            $content .= $lang . "\n";
        }
        $relprev_next = $this->get_current_page_prev_next_rel_tags();
        if ($relprev_next) {
            $content .= $relprev_next . "\n";
        }
        return rtrim($content);
    }
    /**
     * creates hrefLang links for various entities.
     *
     * @param string $entity name of the object/page to get the link for
     * @param int $idItem eventual id of the object (if any)
     * @param array $languages list of languages
     * @param int $idLangDefault id of the default language
     *
     * @return string[] HTML of the hreflang tags
     *
     * @throws PrestaShopException
     */
    public function get_href_lang($entity, $id_item, $languages, $id_lang_default)
    {
        Tools::display_as_deprecated();
        return $this->get_current_page_href_lang_tags();
    }
    /**
     * Get rel prev/next tags for paginated pages.
     *
     * @param string $entity type of object
     * @param int $idItem id of he object
     *
     * @return string string containing the new tags
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_rel_prev_next($entity, $id_item)
    {
        switch ($entity) {
            case 'category':
                $category = new Category((int) $id_item);
                $nb_products = $category->get_products(null, null, null, null, null, true);
                break;
            case 'manufacturer':
                $manufacturer = new Manufacturer($id_item);
                $nb_products = $manufacturer->get_products($manufacturer->id, null, null, null, null, null, true);
                break;
            case 'supplier':
                $supplier = new Supplier($id_item);
                $nb_products = $supplier->get_products($supplier->id, null, null, null, null, null, true);
                break;
            default:
                return '';
        }
        $p = Tools::get_int_value('p');
        $n = (int) Configuration::get('PS_PRODUCTS_PER_PAGE');
        $total_pages = ceil($nb_products / $n);
        $linkprev = '';
        $linknext = '';
        $request_page = $this->context->link->get_pagination_link($entity, $id_item, $n, false, 1, false);
        if (!$p) {
            $p = 1;
        }
        if ($p > 1) {
            // we need prev
            $linkprev = $this->context->link->go_page($request_page, $p - 1);
        }
        if ($total_pages > 1 && $p + 1 <= $total_pages) {
            $linknext = $this->context->link->go_page($request_page, $p + 1);
        }
        $return = '';
        if ($linkprev) {
            $return .= '<link rel="prev" href="' . $linkprev . '">';
        }
        if ($linknext) {
            $return .= '<link rel="next" href="' . $linknext . '">';
        }
        return $return;
    }
    /**
     * Compiles and outputs page header section (including HTML <head>).
     *
     * @param bool $display If true, renders visual page header section
     *
     * @throws PrestaShopException
     * @throws SmartyException
     * @deprecated 2.0.0
     */
    public function display_header($display = true): void
    {
        Tools::display_as_deprecated();
        $this->init_header();
        $hook_header = Hook::display_hook('displayHeader');
        if ((Configuration::get('PS_CSS_THEME_CACHE') || Configuration::get('PS_JS_THEME_CACHE')) && is_writable(_PS_THEME_DIR_ . 'cache')) {
            // CSS compressor management
            if (Configuration::get('PS_CSS_THEME_CACHE')) {
                $this->css_files = Media::ccc_css($this->css_files);
            }
            //JS compressor management
            if (Configuration::get('PS_JS_THEME_CACHE')) {
                $this->js_files = Media::ccc_js($this->js_files);
            }
        }
        // Call hook before assign of css_files and js_files in order to include correctly all css and javascript files
        $this->context->smarty->assign(['HOOK_HEADER' => $hook_header, 'HOOK_TOP' => Hook::display_hook('displayTop'), 'HOOK_LEFT_COLUMN' => $this->display_column_left ? Hook::display_hook('displayLeftColumn') : '', 'HOOK_RIGHT_COLUMN' => $this->display_column_right ? Hook::display_hook('displayRightColumn', ['cart' => $this->context->cart]) : '', 'HOOK_FOOTER' => Hook::display_hook('displayFooter')]);
        $this->context->smarty->assign(['css_files' => $this->css_files, 'js_files' => $this->get_layout() && Configuration::get('PS_JS_DEFER') ? [] : $this->js_files]);
        $this->display_header = $display;
        $this->smarty_output_content(_PS_THEME_DIR_ . 'header.tpl');
    }
    /**
     * Initializes page header variables.
     *
     * @throws PrestaShopException
     */
    public function init_header(): void
    {
        // Added powered by for builtwith.com
        header('Powered-By: thirty bees');
        // Hooks are voluntary out the initialize array (need those variables already assigned)
        $this->context->smarty->assign(['time' => time(), 'img_update_time' => Configuration::get('PS_IMG_UPDATE_TIME'), 'static_token' => Tools::get_token(false), 'token' => Tools::get_token(), 'priceDisplayPrecision' => Context::get_context()->currency->get_display_precision(), 'content_only' => Tools::get_int_value('content_only')]);
        $this->context->smarty->assign($this->init_logo_and_favicon());
    }
    /**
     * Returns logo and favicon variables, depending
     * on active theme type (regular or mobile).
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public function init_logo_and_favicon()
    {
        $mobile_device = $this->context->get_mobile_device();
        if ($mobile_device && Configuration::get('PS_LOGO_MOBILE')) {
            $logo = $this->context->link->get_media_link(_PS_IMG_ . Configuration::get('PS_LOGO_MOBILE') . '?' . Configuration::get('PS_IMG_UPDATE_TIME'));
        } else {
            $logo = $this->context->link->get_media_link(_PS_IMG_ . Configuration::get('PS_LOGO'));
        }
        return ['favicon_url' => _PS_IMG_ . Configuration::get('PS_FAVICON'), 'logo_image_width' => $mobile_device == false ? Configuration::get('SHOP_LOGO_WIDTH') : Configuration::get('SHOP_LOGO_MOBILE_WIDTH'), 'logo_image_height' => $mobile_device == false ? Configuration::get('SHOP_LOGO_HEIGHT') : Configuration::get('SHOP_LOGO_MOBILE_HEIGHT'), 'logo_url' => $logo];
    }
    /**
     * Returns the layout corresponding to the current page by using the override system
     * Ex:
     * On the url: http://localhost/index.php?id_product=1&controller=product, this method will
     * check if the layout exists in the following files (in that order), and return the first found:
     * - /themes/default/override/layout-product-1.tpl
     * - /themes/default/override/layout-product.tpl
     * - /themes/default/layout.tpl.
     *
     * @return bool|string
     *
     * @throws PrestaShopException
     */
    public function get_layout()
    {
        $entity = $this->php_self;
        $id_item = Tools::get_int_value('id_' . $entity);
        $layout_dir = $this->get_theme_dir();
        $layout_override_dir = $this->get_override_theme_dir();
        $layout = false;
        if ($entity) {
            if ($id_item > 0 && file_exists($layout_override_dir . 'layout-' . $entity . '-' . $id_item . '.tpl')) {
                $layout = $layout_override_dir . 'layout-' . $entity . '-' . $id_item . '.tpl';
            } elseif (file_exists($layout_override_dir . 'layout-' . $entity . '.tpl')) {
                $layout = $layout_override_dir . 'layout-' . $entity . '.tpl';
            }
        }
        if (!$layout && file_exists($layout_dir . 'layout.tpl')) {
            return $layout_dir . 'layout.tpl';
        }
        return $layout;
    }
    /**
     * Returns theme directory (regular or mobile).
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected function get_theme_dir()
    {
        return $this->use_mobile_theme() ? _PS_THEME_MOBILE_DIR_ : _PS_THEME_DIR_;
    }
    /**
     * Returns theme override directory (regular or mobile).
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected function get_override_theme_dir()
    {
        return $this->use_mobile_theme() ? _PS_THEME_MOBILE_OVERRIDE_DIR_ : _PS_THEME_OVERRIDE_DIR_;
    }
    /**
     * Renders controller templates and generates page content.
     *
     * @param array|string $content Template file(s) to be rendered
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    protected function smarty_output_content($content)
    {
        if (Page_Cache::is_enabled()) {
            $html = $this->get_smarty_output_content($content);
            Page_Cache::set($html);
            echo $html;
        } else {
            parent::smarty_output_content($content);
        }
    }
    /**
     * Generates page content for controller templates
     *
     * @param array|string $content
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function get_smarty_output_content($content): string
    {
        $this->context->smarty->assign('js_def', static::JS_DEF_PLACEHOLDER);
        $html = parent::get_smarty_output_content($content);
        if ($html && $this->get_layout()) {
            $live_edit_content = '';
            if (!$this->use_mobile_theme() && $this->check_live_edit_access()) {
                $live_edit_content = $this->get_live_edit_footer();
            }
            $dom_available = extension_loaded('dom');
            $defer = (bool) Configuration::get('PS_JS_DEFER');
            if ($defer && $dom_available) {
                $html = Media::defer_inline_scripts($html);
            }
            $html = trim(str_replace(['</body>', '</html>'], '', $html)) . "\n";
            $this->context->smarty->assign(['js_def' => Media::get_js_def(), 'js_files' => $defer ? array_unique($this->js_files) : [], 'js_inline' => $defer && $dom_available ? Media::get_inline_script() : []]);
            $javascript = $this->context->smarty->fetch(_PS_ALL_THEMES_DIR_ . 'javascript.tpl');
            if ($defer && (!isset($this->ajax) || !$this->ajax)) {
                $html .= $javascript;
            } else {
                $html = preg_replace('/(?<!\$)' . static::JS_DEF_PLACEHOLDER . '/', (string) $javascript, $html);
            }
            $html .= $live_edit_content . (!isset($this->ajax) || !$this->ajax ? '</body></html>' : '');
        }
        return $html;
    }
    /**
     * Compiles and outputs page footer section.
     *
     * @param bool $display
     *
     * @throws SmartyException
     * @throws PrestaShopException
     * @deprecated 2.0.0
     */
    public function display_footer($display = true): void
    {
        Tools::display_as_deprecated();
        $this->smarty_output_content(_PS_THEME_DIR_ . 'footer.tpl');
    }
    /**
     * Renders and outputs maintenance page and ends controller process.
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function init_cursed_page(): void
    {
        $this->display_maintenance_page();
    }
    /**
     * Displays maintenance page if shop is closed.
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function display_maintenance_page()
    {
        if ($this->maintenance == true || !(int) Configuration::get('PS_SHOP_ENABLE')) {
            $this->maintenance = true;
            if (Tools::is_phpcli()) {
                // don't show mantenance page in CLI mode
                return;
            }
            $allowed_ip = in_array(Tools::get_remote_addr(), Tools::get_maintenance_ip_addresses());
            if ($allowed_ip) {
                // don't show mantenance page for maintenance IP addresses
                return;
            }
            header('HTTP/1.1 503 temporarily overloaded');
            $this->context->smarty->assign($this->init_logo_and_favicon());
            $this->context->smarty->assign('HOOK_MAINTENANCE', Hook::display_hook('displayMaintenance'));
            // If the controller is a module, then getTemplatePath will try to find the template in the modules, so we need to instanciate a real frontcontroller
            $front_controller = preg_match('/ModuleFrontController$/', static::class) ? new Front_Controller() : $this;
            $this->smarty_output_content($front_controller->get_template_path($this->get_theme_dir() . 'maintenance.tpl'));
            exit;
        }
    }
    /**
     * Returns template path.
     *
     * @param string $template
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function get_template_path($template)
    {
        if (!$this->use_mobile_theme()) {
            return $template;
        }
        $tpl_file = basename($template);
        $dirname = dirname($template) . (str_ends_with(dirname($template), '/') ? '' : '/');
        if ($dirname == _PS_THEME_DIR_) {
            if (file_exists(_PS_THEME_MOBILE_DIR_ . $tpl_file)) {
                $template = _PS_THEME_MOBILE_DIR_ . $tpl_file;
            }
        } elseif ($dirname == _PS_THEME_MOBILE_DIR_) {
            if (!file_exists(_PS_THEME_MOBILE_DIR_ . $tpl_file) && file_exists(_PS_THEME_DIR_ . $tpl_file)) {
                $template = _PS_THEME_DIR_ . $tpl_file;
            }
        }
        return $template;
    }
    /**
     * Compiles and outputs full page content.
     *
     * @return bool
     *
     * @throws SmartyException
     * @throws PrestaShopException
     */
    public function display()
    {
        Tools::safe_post_vars();
        // assign css_files and js_files at the very last time
        if ((Configuration::get('PS_CSS_THEME_CACHE') || Configuration::get('PS_JS_THEME_CACHE')) && is_writable(_PS_THEME_DIR_ . 'cache')) {
            // CSS compressor management
            if (Configuration::get('PS_CSS_THEME_CACHE')) {
                $this->css_files = Media::ccc_css($this->css_files);
            }
            //JS compressor management
            if (Configuration::get('PS_JS_THEME_CACHE') && !$this->use_mobile_theme()) {
                $this->js_files = Media::ccc_js($this->js_files);
            }
        }
        // Get img_formats dynamically
        $supported_main_image_extensions = Image_Manager::get_allowed_image_extensions(true, true);
        $img_formats = [];
        foreach ($supported_main_image_extensions as $main_image_extension) {
            if ($mime_type = Media::get_file_informations('images', $main_image_extension)['mimeType']) {
                $img_formats[$main_image_extension] = $mime_type;
            }
        }
        $this->context->smarty->assign(['css_files' => $this->css_files, 'js_files' => $this->get_layout() && Configuration::get('PS_JS_DEFER') ? [] : $this->js_files, 'js_defer' => (bool) Configuration::get('PS_JS_DEFER'), 'errors' => $this->errors, 'display_header' => $this->display_header, 'display_footer' => $this->display_footer, 'img_formats' => $img_formats]);
        $layout = $this->get_layout();
        if ($layout) {
            if ($this->template) {
                $template = $this->context->smarty->fetch($this->template);
            } else {
                // For retrocompatibility with 1.4 controller
                ob_start();
                $this->display_content();
                $template = ob_get_contents();
                ob_clean();
            }
            $this->context->smarty->assign('template', $template);
            $this->smarty_output_content($layout);
        } else {
            Tools::display_as_deprecated('layout.tpl is missing in your theme directory');
            if ($this->display_header) {
                $this->smarty_output_content(_PS_THEME_DIR_ . 'header.tpl');
            }
            if ($this->template) {
                $this->smarty_output_content($this->template);
            } else {
                // For retrocompatibility with 1.4 controller
                $this->display_content();
            }
            if ($this->display_footer) {
                $this->smarty_output_content(_PS_THEME_DIR_ . 'footer.tpl');
            }
        }
        return true;
    }
    /**
     * Renders page content.
     * Used for retrocompatibility with PS 1.4.
     *
     * @return void
     */
    public function display_content()
    {
    }
    /**
     * Sets controller CSS and JS files.
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_media(): void
    {
        /*
         * If website is accessed by mobile device
         * @see FrontControllerCore::setMobileMedia()
         */
        if ($this->use_mobile_theme()) {
            $this->set_mobile_media();
            return;
        }
        $this->add_css(_THEME_CSS_DIR_ . 'grid_prestashop.css', 'all');
        // retro compat themes 1.5.0.1
        $this->add_css(_THEME_CSS_DIR_ . 'global.css', 'all');
        $this->add_jquery();
        $this->add_jquery_plugin('easing');
        $this->add_js(_PS_JS_DIR_ . 'tools.js');
        $this->add_js(_THEME_JS_DIR_ . 'global.js');
        Media::add_js_def(['currencyFormatters' => Currency::get_javascript_formatters()]);
        // @since 1.0.4
        Media::add_js_def(['useLazyLoad' => (bool) Configuration::get('TB_LAZY_LOAD'), 'useWebp' => Image_Manager::webp_support()]);
        // Automatically add js files from js/autoload directory in the template
        $autoload_dir_js = $this->get_theme_dir() . 'js/autoload/';
        if (file_exists($autoload_dir_js) && is_dir($autoload_dir_js)) {
            foreach (scandir($autoload_dir_js) as $file) {
                if (preg_match('/^[^.].*\.js$/', $file)) {
                    $this->add_js($autoload_dir_js . $file);
                }
            }
        }
        // Automatically add css files from css/autoload directory in the template
        $autoload_dir_css = $this->get_theme_dir() . 'css/autoload/';
        if (file_exists($autoload_dir_css) && is_dir($autoload_dir_css)) {
            foreach (scandir($autoload_dir_css) as $file) {
                if (preg_match('/^[^.].*\.css$/', $file)) {
                    $this->add_css($autoload_dir_css . $file);
                }
            }
        }
        if (Tools::is_submit('live_edit') && Tools::get_value('ad') && Tools::get_admin_token('AdminModulesPositions' . (int) Tab::get_id_from_class_name('AdminModulesPositions') . Tools::get_int_value('id_employee'))) {
            $this->add_jquery_ui('ui.sortable');
            $this->addjquery_plugin('fancybox');
            $this->add_js(_PS_JS_DIR_ . 'hookLiveEdit.js');
        }
        if (Configuration::get('PS_QUICK_VIEW')) {
            $this->addjquery_plugin('fancybox');
        }
        if (Configuration::get('PS_COMPARATOR_MAX_ITEM') > 0) {
            $this->add_js(_THEME_JS_DIR_ . 'products-comparison.js');
        }
        // Execute Hook FrontController SetMedia
        Hook::trigger_event('actionFrontControllerSetMedia', []);
        $this->add_synthetic_scheduler_js();
    }
    /**
     * Specific medias for mobile device.
     * If autoload directory is present in the mobile theme, these files will not be loaded.
     *
     * @throws PrestaShopException
     */
    public function set_mobile_media(): void
    {
        $this->add_jquery();
        if (!file_exists($this->get_theme_dir() . 'js/autoload/')) {
            $this->add_js(_THEME_MOBILE_JS_DIR_ . 'jquery.mobile-1.3.0.min.js');
            $this->add_js(_THEME_MOBILE_JS_DIR_ . 'jqm-docs.js');
            $this->add_js(_PS_JS_DIR_ . 'tools.js');
            $this->add_js(_THEME_MOBILE_JS_DIR_ . 'global.js');
            $this->add_jquery_plugin('fancybox');
        }
        if (!file_exists($this->get_theme_dir() . 'css/autoload/')) {
            $this->add_css(_THEME_MOBILE_CSS_DIR_ . 'jquery.mobile-1.3.0.min.css', 'all');
            $this->add_css(_THEME_MOBILE_CSS_DIR_ . 'jqm-docs.css', 'all');
            $this->add_css(_THEME_MOBILE_CSS_DIR_ . 'global.css', 'all');
        }
    }
    /**
     * Add one or several JS files for front, checking if js files are overridden in theme/js/modules/ directory.
     *
     * @see Controller::addJS()
     *
     * @param array|string $jsUri Path to file, or an array of paths
     * @param bool $checkPath If true, checks if files exists
     *
     * @return bool
     */
    public function add_js($js_uri, $check_path = true)
    {
        return $this->add_media($js_uri, null, null, false, $check_path);
    }
    /**
     * Adds a media file(s) (CSS, JS) to page header.
     *
     * @param string|array $mediaUri Path to file, or an array of paths like: array(array(uri => media_type), ...)
     * @param string|null $cssMediaType CSS media type
     * @param int|null $offset
     * @param bool $remove If True, removes media files
     * @param bool $checkPath If true, checks if files exists
     *
     * @return bool
     */
    public function add_media($media_uri, $css_media_type = null, $offset = null, $remove = false, $check_path = true)
    {
        if (!is_array($media_uri)) {
            if ($css_media_type) {
                $media_uri = [$media_uri => $css_media_type];
            } else {
                $media_uri = [$media_uri];
            }
        }
        $list_uri = [];
        foreach ($media_uri as $file => $media) {
            if (!Validate::is_absolute_url($media)) {
                $different = 0;
                $different_css = 0;
                $type = 'css';
                if (!$css_media_type) {
                    $type = 'js';
                    $file = $media;
                }
                if (str_starts_with((string) $file, __PS_BASE_URI__ . 'modules/')) {
                    $override_path = str_replace(__PS_BASE_URI__ . 'modules/', _PS_ROOT_DIR_ . '/themes/' . _THEME_NAME_ . '/' . $type . '/modules/', $file, $different);
                    if (strrpos($override_path, $type . '/' . basename((string) $file)) !== false) {
                        $override_path_css = str_replace($type . '/' . basename((string) $file), basename((string) $file), $override_path, $different_css);
                    }
                    if ($different && file_exists($override_path)) {
                        $file = str_replace(__PS_BASE_URI__ . 'modules/', __PS_BASE_URI__ . 'themes/' . _THEME_NAME_ . '/' . $type . '/modules/', $file, $different);
                    } elseif ($different_css && isset($override_path_css) && file_exists($override_path_css)) {
                        $file = $override_path_css;
                    }
                    if ($css_media_type) {
                        $list_uri[$file] = $media;
                    } else {
                        $list_uri[] = $file;
                    }
                } else {
                    $list_uri[$file] = $media;
                }
            } else {
                $list_uri[$file] = $media;
            }
        }
        if ($remove) {
            if ($css_media_type) {
                parent::remove_css($list_uri, $css_media_type);
                return true;
            }
            parent::remove_js($list_uri);
            return true;
        }
        if ($css_media_type) {
            parent::add_css($list_uri, $css_media_type, $offset, $check_path);
            return true;
        }
        parent::add_js($list_uri, $check_path);
        return true;
    }
    /**
     * Add one or several CSS for front, checking if css files are overridden in theme/css/modules/ directory.
     *
     * @see Controller::addCSS()
     *
     * @param array|string $cssUri Path to file, or an array of paths like: array(array(uri => media_type), ...)
     * @param string $cssMediaType CSS media type
     * @param int|null $offset
     * @param bool $checkPath If true, checks if files exists
     *
     * @return bool
     */
    public function add_css($css_uri, $css_media_type = 'all', $offset = null, $check_path = true)
    {
        return $this->add_media($css_uri, $css_media_type, null, false, $check_path);
    }
    /**
     * Initializes page footer variables.
     *
     * @throws PrestaShopException
     */
    public function init_footer(): void
    {
        $hook_footer = Hook::display_hook('displayFooter');
        $extra_js = Configuration::get(Configuration::CUSTOMCODE_JS);
        $extra_js_conf = '';
        if (isset($this->php_self) && $this->php_self == 'order-confirmation') {
            $extra_js_conf = Configuration::get(Configuration::CUSTOMCODE_ORDERCONF_JS);
        }
        if ($extra_js) {
            $hook_footer .= '<script type="text/javascript">' . $extra_js . '</script>';
        }
        if ($extra_js_conf) {
            $hook_footer .= '<script type="text/javascript">' . $extra_js_conf . '</script>';
        }
        $this->context->smarty->assign(['HOOK_FOOTER' => $hook_footer, 'conditions' => Configuration::get(Configuration::CONDITIONS), 'id_cgv' => Configuration::get(Configuration::CONDITIONS_CMS_ID), 'PS_SHOP_NAME' => Configuration::get(Configuration::SHOP_NAME), 'PS_ALLOW_MOBILE_DEVICE' => Context::get_context()->theme->supports_mobile_variant()]);
        /*
         * RTL support
         * rtl.css overrides theme css files for RTL
         * iso_code.css overrides default font for every language (optional)
         */
        if ($this->context->language->is_rtl) {
            $this->add_css(_THEME_CSS_DIR_ . 'rtl.css');
            $this->add_css(_THEME_CSS_DIR_ . $this->context->language->iso_code . '.css');
        }
    }
    /**
     * Renders Live Edit widget.
     *
     * @return string HTML
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function get_live_edit_footer()
    {
        if ($this->check_live_edit_access()) {
            $data = $this->context->smarty->create_data();
            $data->assign(['ad' => Tools::get_value('ad'), 'live_edit' => true, 'hook_list' => Hook::$executed_hooks, 'id_shop' => $this->context->shop->id]);
            return $this->context->smarty->create_template(_PS_ALL_THEMES_DIR_ . 'live_edit.tpl', $data)->fetch();
        }
        return '';
    }
    /**
     * Checks if the user can use Live Edit feature.
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function check_live_edit_access()
    {
        if (!Tools::is_submit('live_edit') || !Tools::get_value('ad') || !Tools::get_value('liveToken')) {
            return false;
        }
        if (Tools::get_value('liveToken') != Tools::get_admin_token('AdminModulesPositions' . (int) Tab::get_id_from_class_name('AdminModulesPositions') . Tools::get_int_value('id_employee'))) {
            return false;
        }
        return is_dir(_PS_CORE_DIR_ . DIRECTORY_SEPARATOR . Tools::get_value('ad'));
    }
    /**
     * Assigns product list page sorting variables.
     *
     * @throws PrestaShopException
     */
    public function product_sort(): void
    {
        // $this->orderBy = Tools::getProductsOrder('by', Tools::getValue('orderby'));
        // $this->orderWay = Tools::getProductsOrder('way', Tools::getValue('orderway'));
        // 'orderbydefault' => Tools::getProductsOrder('by'),
        // 'orderwayposition' => Tools::getProductsOrder('way'), // Deprecated: orderwayposition
        // 'orderwaydefault' => Tools::getProductsOrder('way'),
        $stock_management = (bool) Configuration::get('PS_STOCK_MANAGEMENT');
        // no display quantity order if stock management disabled
        $order_by_values = [0 => 'name', 1 => 'price', 2 => 'date_add', 3 => 'date_upd', 4 => 'position', 5 => 'manufacturer_name', 6 => 'quantity', 7 => 'reference'];
        $order_way_values = [0 => 'asc', 1 => 'desc'];
        $this->order_by = mb_strtolower(Tools::get_value('orderby', $order_by_values[(int) Configuration::get('PS_PRODUCTS_ORDER_BY')]));
        $this->order_way = mb_strtolower(Tools::get_value('orderway', $order_way_values[(int) Configuration::get('PS_PRODUCTS_ORDER_WAY')]));
        if (!in_array($this->order_by, $order_by_values)) {
            $this->order_by = $order_by_values[0];
        }
        if (!in_array($this->order_way, $order_way_values)) {
            $this->order_way = $order_way_values[0];
        }
        $this->context->smarty->assign([
            'orderby' => $this->order_by,
            'orderway' => $this->order_way,
            'orderbydefault' => $order_by_values[(int) Configuration::get('PS_PRODUCTS_ORDER_BY')],
            'orderwayposition' => $order_way_values[(int) Configuration::get('PS_PRODUCTS_ORDER_WAY')],
            // Deprecated: orderwayposition
            'orderwaydefault' => $order_way_values[(int) Configuration::get('PS_PRODUCTS_ORDER_WAY')],
            'stock_management' => (int) $stock_management,
        ]);
    }
    /**
     * Assigns product list page pagination variables.
     *
     * @param int|null $totalProducts
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function pagination($total_products = null): void
    {
        if (!static::$initialized) {
            $this->init();
        }
        // Retrieve the default number of products per page and the other available selections
        $default_products_per_page = max(1, (int) Configuration::get('PS_PRODUCTS_PER_PAGE'));
        $n_array = [$default_products_per_page, $default_products_per_page * 2, $default_products_per_page * 5];
        if (Tools::get_int_value('n') && (int) $total_products > 0) {
            $n_array[] = $total_products;
        }
        // Retrieve the current number of products per page (either the default, the GET parameter or the one in the cookie)
        $this->n = $default_products_per_page;
        if (isset($this->context->cookie->nb_item_per_page) && in_array($this->context->cookie->nb_item_per_page, $n_array)) {
            $this->n = (int) $this->context->cookie->nb_item_per_page;
        }
        if (Tools::get_int_value('n') && in_array(Tools::get_int_value('n'), $n_array)) {
            $this->n = Tools::get_int_value('n');
        }
        // Retrieve the page number (either the GET parameter or the first page)
        $this->p = Tools::get_int_value('p', 1);
        // If the parameter is not correct then redirect (do not merge with the previous line, the redirect is required in order to avoid duplicate content)
        if (!is_numeric($this->p) || $this->p < 1) {
            Tools::redirect($this->context->link->get_pagination_link(false, false, $this->n, false, 1, false));
        }
        // Remove the page parameter in order to get a clean URL for the pagination template
        $current_url = preg_replace('/(?:(\?)|&amp;)p=\d+/', '$1', Tools::htmlentities_utf8($_SERVER['REQUEST_URI']));
        if ($this->n != $default_products_per_page || isset($this->context->cookie->nb_item_per_page)) {
            $this->context->cookie->nb_item_per_page = $this->n;
        }
        $pages_nb = ceil($total_products / (int) $this->n);
        if ($this->p > $pages_nb && $total_products != 0) {
            Tools::redirect($this->context->link->get_pagination_link(false, false, $this->n, false, $pages_nb, false));
        }
        $range = 2;
        /* how many pages around page selected */
        $start = (int) ($this->p - $range);
        if ($start < 1) {
            $start = 1;
        }
        $stop = (int) ($this->p + $range);
        if ($stop > $pages_nb) {
            $stop = (int) $pages_nb;
        }
        $this->context->smarty->assign(['nb_products' => $total_products, 'products_per_page' => $this->n, 'pages_nb' => $pages_nb, 'p' => $this->p, 'n' => $this->n, 'nArray' => $n_array, 'range' => $range, 'start' => $start, 'stop' => $stop, 'current_url' => $current_url]);
    }
    /**
     * Initializes front controller: sets smarty variables,
     * class properties, redirects depending on context, etc.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     * @global bool $useSSL SSL connection flag
     * @global Cookie $cookie Visitor's cookie
     * @global Smarty $smarty
     * @global Cart $cart Visitor's cart
     * @global string $iso Language ISO
     * @global Country $defaultCountry Visitor's country object
     * @global string $protocol_link
     * @global string $protocol_content
     * @global Link $link
     * @global array $css_files
     * @global array $js_files
     * @global Currency $currency Visitor's selected currency
     */
    public function init(): void
    {
        /*
         * Globals are DEPRECATED as of version 1.5.0.1
         * Use the Context object to access objects instead.
         * Example: $this->context->cart
         */
        global $use_ssl, $cookie, $smarty, $cart, $iso, $default_country, $protocol_link, $protocol_content, $link, $css_files, $js_files, $currency;
        if (static::$initialized) {
            return;
        }
        static::$initialized = true;
        parent::init();
        // If current URL use SSL, set it true (used a lot for module redirect)
        if (Tools::using_secure_mode()) {
            $use_ssl = true;
        }
        // For compatibility with globals, DEPRECATED as of version 1.5.0.1
        $css_files = $this->css_files;
        $js_files = $this->js_files;
        $this->ssl_redirection();
        if ($this->ajax) {
            $this->display_header = false;
            $this->display_footer = false;
        }
        // If account created with the 2 steps register process, remove 'account_created' from cookie
        if (isset($this->context->cookie->account_created)) {
            $this->context->smarty->assign('account_created', 1);
            unset($this->context->cookie->account_created);
        }
        ob_start();
        // Init cookie language
        // @TODO This method must be moved into switchLanguage
        Tools::set_cookie_language($this->context->cookie);
        $protocol_link = Configuration::get('PS_SSL_ENABLED') || Tools::using_secure_mode() ? 'https://' : 'http://';
        $use_ssl = isset($this->ssl) && $this->ssl && Configuration::get('PS_SSL_ENABLED') || Tools::using_secure_mode();
        $protocol_content = $use_ssl ? 'https://' : 'http://';
        $link = new Link($protocol_link, $protocol_content);
        $this->context->link = $link;
        if ($id_cart = (int) $this->recover_cart()) {
            $this->context->cookie->id_cart = (int) $id_cart;
        }
        if ($this->auth && !$this->context->customer->is_logged($this->guest_allowed)) {
            Tools::redirect('index.php?controller=authentication' . ($this->auth_redirection ? '&back=' . $this->auth_redirection : ''));
        }
        /* Theme is missing */
        if (!is_dir(_PS_THEME_DIR_)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Current theme unavailable "%s". Please check your theme directory name and permissions.'), basename(rtrim(_PS_THEME_DIR_, '/\\'))));
        }
        if (Configuration::get('PS_GEOLOCATION_ENABLED')) {
            if (($new_default = $this->geolocation_management($this->context->country)) && Validate::is_loaded_object($new_default)) {
                $this->context->country = $new_default;
            }
        } elseif (Configuration::get('PS_DETECT_COUNTRY')) {
            $has_currency = isset($this->context->cookie->id_currency) && (int) $this->context->cookie->id_currency;
            $has_country = isset($this->context->cookie->iso_code_country) && $this->context->cookie->iso_code_country;
            $has_address_type = false;
            if ((int) $this->context->cookie->id_cart && ($cart = new Cart($this->context->cookie->id_cart)) && Validate::is_loaded_object($cart)) {
                $has_address_type = isset($cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}) && $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
            }
            if ((!$has_currency || $has_country) && !$has_address_type) {
                $id_country = $has_country && !Validate::is_language_iso_code($this->context->cookie->iso_code_country) ? (int) Country::get_by_iso(strtoupper((string) $this->context->cookie->iso_code_country)) : (int) Tools::get_country();
                $country = new Country($id_country, (int) $this->context->cookie->id_lang);
                if (!$has_currency && Validate::is_loaded_object($country) && $this->context->country->id !== $country->id) {
                    $this->context->country = $country;
                    $this->context->cookie->id_currency = (int) Currency::get_currency_instance($country->id_currency ? (int) $country->id_currency : (int) Configuration::get('PS_CURRENCY_DEFAULT'))->id;
                    $this->context->cookie->iso_code_country = strtoupper($country->iso_code);
                }
            }
        }
        // save user preference about using mobile theme, if submitted
        if (Tools::is_submit('no_mobile_theme')) {
            $this->set_mobile_theme_allowed($this->context->cookie, false);
        } elseif (Tools::is_submit('mobile_theme_ok')) {
            $this->set_mobile_theme_allowed($this->context->cookie, true);
        }
        $currency = Tools::set_currency($this->context->cookie);
        if (isset($_GET['logout']) || $this->context->customer->logged && Customer::is_banned($this->context->customer->id)) {
            $this->context->customer->logout();
            Tools::redirect(Tools::secure_referrer(Tools::get_http_referer()));
        } elseif (isset($_GET['mylogout'])) {
            $this->context->customer->mylogout();
            Tools::redirect(Tools::secure_referrer(Tools::get_http_referer()));
        }
        /* Cart already exists */
        if ((int) $this->context->cookie->id_cart) {
            if (!isset($cart)) {
                $cart = new Cart($this->context->cookie->id_cart);
            }
            if (Validate::is_loaded_object($cart) && $cart->order_exists()) {
                unset($this->context->cookie->id_cart, $cart, $this->context->cookie->checked_tos);
                $this->context->cookie->check_cgv = false;
            } elseif (intval(Configuration::get('PS_GEOLOCATION_ENABLED')) && !in_array(strtoupper((string) $this->context->cookie->iso_code_country), explode(';', (string) Configuration::get('PS_ALLOWED_COUNTRIES'))) && $cart->nb_products() && intval(Configuration::get('PS_GEOLOCATION_NA_BEHAVIOR')) != -1 && !Front_Controller::is_in_whitelist_for_geolocation() && !in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1'])) {
                Logger::add_log('Frontcontroller::init - GEOLOCATION is deleting a cart', 1, null, 'Cart', (int) $this->context->cookie->id_cart, true);
                unset($this->context->cookie->id_cart, $cart);
            } elseif ($this->context->cookie->id_customer != $cart->id_customer || $this->context->cookie->id_lang != $cart->id_lang || $currency->id != $cart->id_currency) {
                if ($this->context->cookie->id_customer) {
                    $cart->id_customer = (int) $this->context->cookie->id_customer;
                }
                $cart->id_lang = (int) $this->context->cookie->id_lang;
                $cart->id_currency = (int) $currency->id;
                $cart->update();
            }
            /* Select an address if not set */
            if (isset($cart) && $this->context->cookie->id_customer && (!$cart->id_address_delivery || !$cart->id_address_invoice)) {
                $id_first_address = (int) Address::get_first_customer_address_id($cart->id_customer);
                if ($id_first_address) {
                    $to_update = false;
                    if (!$cart->id_address_delivery) {
                        $to_update = true;
                        $cart->id_address_delivery = $id_first_address;
                    }
                    if (!$cart->id_address_invoice) {
                        $to_update = true;
                        $cart->id_address_invoice = $id_first_address;
                    }
                    if ($to_update) {
                        $cart->update();
                    }
                }
            }
        }
        if (!isset($cart) || !$cart->id) {
            $cart = new Cart();
            $cart->id_lang = (int) $this->context->cookie->id_lang;
            $cart->id_currency = (int) $this->context->cookie->id_currency;
            $cart->id_guest = (int) $this->context->cookie->id_guest;
            $cart->id_shop_group = (int) $this->context->shop->id_shop_group;
            $cart->id_shop = $this->context->shop->id;
            if ($this->context->cookie->id_customer) {
                $cart->id_customer = (int) $this->context->cookie->id_customer;
                $cart->id_address_delivery = (int) Address::get_first_customer_address_id($cart->id_customer);
                $cart->id_address_invoice = (int) $cart->id_address_delivery;
            } else {
                $cart->id_address_delivery = 0;
                $cart->id_address_invoice = 0;
            }
            // Needed if the merchant want to give a free product to every visitors
            $this->context->cart = $cart;
            Cart_Rule::auto_add_to_cart($this->context);
        } else {
            $this->context->cart = $cart;
        }
        /* get page name to display it in body id */
        // Are we in a payment module
        $module_name = '';
        if (Validate::is_module_name(Tools::get_value('module'))) {
            $module_name = Tools::get_value('module');
        }
        if (!empty($this->page_name)) {
            $page_name = $this->page_name;
        } elseif (!empty($this->php_self)) {
            $page_name = $this->php_self;
        } elseif (Tools::get_value('fc') == 'module' && $module_name != '' && Module::get_instance_by_name($module_name) instanceof Payment_Module) {
            $page_name = 'module-payment-submit';
        } elseif (preg_match('#^' . preg_quote((string) $this->context->shop->physical_uri, '#') . 'modules/([a-zA-Z0-9_-]+?)/(.*)$#', (string) $_SERVER['REQUEST_URI'], $m)) {
            $page_name = 'module-' . $m[1] . '-' . str_replace(['.php', '/'], ['', '-'], $m[2]);
        } else {
            $page_name = Dispatcher::get_instance()->get_controller();
            $page_name = preg_match('/^[0-9]/', (string) $page_name) ? 'page_' . $page_name : $page_name;
        }
        $this->context->smarty->assign(Meta::get_meta_tags($this->context->language->id, $page_name));
        $this->context->smarty->assign('request_uri', Tools::safe_output(urldecode((string) $_SERVER['REQUEST_URI'])));
        /* Breadcrumb */
        $navigation_pipe = Configuration::get('PS_NAVIGATION_PIPE') ?: '>';
        $this->context->smarty->assign('navigationPipe', $navigation_pipe);
        // Automatically redirect to the canonical URL if needed
        if (!empty($this->php_self) && !Tools::get_value('ajax')) {
            $this->canonical_redirection($this->context->link->get_page_link($this->php_self, $this->ssl, $this->context->language->id));
        }
        Product::init_prices_computation();
        $display_tax_label = $this->context->country->display_tax_label;
        if (isset($cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}) && $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}) {
            $infos = Address::get_country_and_state((int) $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
            if (isset($infos['id_country']) && $infos['id_country']) {
                $country = new Country((int) $infos['id_country']);
                $this->context->country = $country;
                if (Validate::is_loaded_object($country)) {
                    $display_tax_label = $country->display_tax_label;
                }
            }
        }
        $languages = Language::get_languages(true, $this->context->shop->id);
        $meta_language = [];
        foreach ($languages as $lang) {
            $meta_language[] = $lang['iso_code'];
        }
        $compared_products = [];
        if (Configuration::get('PS_COMPARATOR_MAX_ITEM') && isset($this->context->cookie->id_compare)) {
            $compared_products = Compare_Product::get_compare_products($this->context->cookie->id_compare);
        }
        $this->context->smarty->assign([
            // Useful for layout.tpl
            'mobile_device' => $this->context->get_mobile_device(),
            'link' => $link,
            'cart' => $cart,
            'currency' => $currency,
            'currencyRate' => $currency->get_conversation_rate(),
            'cookie' => $this->context->cookie,
            'page_name' => $page_name,
            'hide_left_column' => !$this->display_column_left,
            'hide_right_column' => !$this->display_column_right,
            'base_dir' => _PS_BASE_URL_ . __PS_BASE_URI__,
            'base_dir_ssl' => $protocol_link . Tools::get_shop_domain_ssl() . __PS_BASE_URI__,
            'force_ssl' => Configuration::get('PS_SSL_ENABLED'),
            'content_dir' => $protocol_content . Tools::get_http_host() . __PS_BASE_URI__,
            'base_uri' => $protocol_content . Tools::get_http_host() . __PS_BASE_URI__ . (!Configuration::get('PS_REWRITING_SETTINGS') ? 'index.php' : ''),
            'tpl_dir' => _PS_THEME_DIR_,
            'tpl_uri' => _THEME_DIR_,
            'root_dir' => _PS_ROOT_DIR_,
            'modules_dir' => _MODULE_DIR_,
            'mail_dir' => _MAIL_DIR_,
            'lang_iso' => $this->context->language->iso_code,
            'lang_id' => (int) $this->context->language->id,
            'isRtl' => $this->context->language->is_rtl,
            'language_code' => $this->context->language->language_code ?: $this->context->language->iso_code,
            'come_from' => Tools::get_http_host(true, true) . Tools::htmlentities_utf8(str_replace(['\'', '\\'], '', urldecode((string) $_SERVER['REQUEST_URI']))),
            'cart_qties' => (int) $cart->nb_products(),
            'currencies' => Currency::get_currencies(),
            'languages' => $languages,
            'meta_language' => implode(',', $meta_language),
            'priceDisplay' => Product::get_tax_calculation_method((int) $this->context->cookie->id_customer),
            'is_logged' => (bool) $this->context->customer->is_logged(),
            'is_guest' => (bool) $this->context->customer->is_guest(),
            'add_prod_display' => (int) Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY'),
            'shop_name' => Configuration::get('PS_SHOP_NAME'),
            'roundMode' => (int) Configuration::get('PS_PRICE_ROUND_MODE'),
            'use_taxes' => (int) Configuration::get('PS_TAX'),
            'show_taxes' => (int) (Configuration::get('PS_TAX_DISPLAY') == 1 && (int) Configuration::get('PS_TAX')),
            'display_tax_label' => (bool) $display_tax_label,
            'vat_management' => (int) Configuration::get('VATNUMBER_MANAGEMENT'),
            'opc' => (bool) Configuration::get('PS_ORDER_PROCESS_TYPE'),
            'PS_CATALOG_MODE' => Configuration::get('PS_CATALOG_MODE') || Group::is_feature_active() && !Group::get_current()->show_prices,
            'b2b_enable' => (bool) Configuration::get('PS_B2B_ENABLE'),
            'request' => $link->get_pagination_link(false, false, false, true),
            'PS_STOCK_MANAGEMENT' => Configuration::get('PS_STOCK_MANAGEMENT'),
            'quick_view' => (bool) Configuration::get('PS_QUICK_VIEW'),
            'shop_phone' => Configuration::get('PS_SHOP_PHONE'),
            'compared_products' => $compared_products,
            'comparator_max_item' => (int) Configuration::get('PS_COMPARATOR_MAX_ITEM'),
            'currencySign' => $currency->sign,
            // backward compat, see global.tpl
            'currencyFormat' => $currency->format,
            // backward compat
            'currencyBlank' => $currency->blank,
            // backward compat
            'high_dpi' => Image_Manager::retina_support(),
            'lazy_load' => (bool) Configuration::get('TB_LAZY_LOAD'),
            'webp' => Image_Manager::webp_support(),
        ]);
        // Add the tpl files directory for mobile
        if ($this->use_mobile_theme()) {
            $this->context->smarty->assign(['tpl_mobile_uri' => _PS_THEME_MOBILE_DIR_]);
        }
        // Deprecated
        $this->context->smarty->assign(['id_currency_cookie' => (int) $currency->id, 'logged' => $this->context->customer->is_logged(), 'customerName' => $this->context->customer->logged ? $this->context->cookie->customer_firstname . ' ' . $this->context->cookie->customer_lastname : false]);
        $assign_array = ['img_ps_dir' => _PS_IMG_, 'img_cat_dir' => _THEME_CAT_DIR_, 'img_lang_dir' => _THEME_LANG_DIR_, 'img_prod_dir' => _THEME_PROD_DIR_, 'img_manu_dir' => _THEME_MANU_DIR_, 'img_sup_dir' => _THEME_SUP_DIR_, 'img_ship_dir' => _THEME_SHIP_DIR_, 'img_store_dir' => _THEME_STORE_DIR_, 'img_col_dir' => _THEME_COL_DIR_, 'img_dir' => _THEME_IMG_DIR_, 'css_dir' => _THEME_CSS_DIR_, 'js_dir' => _THEME_JS_DIR_, 'pic_dir' => _THEME_PROD_PIC_DIR_];
        // Add the images directory for mobile
        if ($this->use_mobile_theme()) {
            $assign_array['img_mobile_dir'] = _THEME_MOBILE_IMG_DIR_;
        }
        // Add the CSS directory for mobile
        if ($this->use_mobile_theme()) {
            $assign_array['css_mobile_dir'] = _THEME_MOBILE_CSS_DIR_;
        }
        foreach ($assign_array as $assign_key => $assign_value) {
            if (str_starts_with($assign_value, '/') || $protocol_content == 'https://') {
                $this->context->smarty->assign($assign_key, $protocol_content . Tools::get_media_server($assign_value) . $assign_value);
            } else {
                $this->context->smarty->assign($assign_key, $assign_value);
            }
        }
        /*
         * These shortcuts are DEPRECATED as of version 1.5.0.1
         * Use the Context to access objects instead.
         * Example: $this->context->cart
         */
        static::$cookie = $this->context->cookie;
        static::$cart = $cart;
        static::$smarty = $this->context->smarty;
        static::$link = $link;
        $default_country = $this->context->country;
        $this->display_maintenance_page();
        if ($this->restricted_country) {
            $this->display_restricted_country_page();
        }
        if (Tools::is_submit('live_edit') && !$this->check_live_edit_access()) {
            Tools::redirect('index.php?controller=404');
        }
        $this->iso = $iso;
        $this->context->cart = $cart;
        $this->context->currency = $currency;
    }
    /**
     * Redirects to correct protocol if settings and request methods don't match.
     *
     * @throws PrestaShopException
     */
    protected function ssl_redirection()
    {
        // If we call a SSL controller without SSL or a non SSL controller with SSL, we redirect with the right protocol
        if (!Tools::is_phpcli() && Configuration::get('PS_SSL_ENABLED') && Tools::get_request_method() !== 'POST' && $this->ssl != Tools::using_secure_mode()) {
            $this->context->cookie->disallow_writing();
            header('HTTP/1.1 301 Moved Permanently');
            header('Cache-Control: no-cache');
            if ($this->ssl) {
                header('Location: ' . Tools::get_shop_domain_ssl(true) . $_SERVER['REQUEST_URI']);
            } else {
                header('Location: ' . Tools::get_shop_domain(true) . $_SERVER['REQUEST_URI']);
            }
            exit;
        }
    }
    /**
     * Recovers cart information.
     *
     * @return int|false
     *
     * @throws PrestaShopException
     */
    protected function recover_cart()
    {
        if (($id_cart = Tools::get_int_value('recover_cart')) && Tools::get_value('token_cart') == md5(_COOKIE_KEY_ . 'recover_cart_' . $id_cart)) {
            $cart = new Cart((int) $id_cart);
            if (Validate::is_loaded_object($cart)) {
                $customer = new Customer((int) $cart->id_customer);
                if (Validate::is_loaded_object($customer)) {
                    $customer->logged = 1;
                    $this->context->customer = $customer;
                    $this->context->cookie->id_customer = (int) $customer->id;
                    $this->context->cookie->customer_lastname = $customer->lastname;
                    $this->context->cookie->customer_firstname = $customer->firstname;
                    $this->context->cookie->logged = 1;
                    $this->context->cookie->check_cgv = 1;
                    $this->context->cookie->is_guest = $customer->is_guest();
                    $this->context->cookie->passwd = $customer->passwd;
                    $this->context->cookie->email = $customer->email;
                    return $id_cart;
                }
            }
        } else {
            return false;
        }
        return false;
    }
    /**
     * Geolocation management.
     *
     * @param Country $defaultCountry
     *
     * @return Country|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function geolocation_management($default_country)
    {
        $ip = Tools::get_remote_addr();
        if ($ip && !in_array($ip, ['127.0.0.1', '::1'])) {
            // determine GeoLocation service module
            $geolocation_module = Configuration::get_global_value('PS_GEOLOCATION_SERVICE');
            $geolocation_module_id = Module::get_module_id_by_name($geolocation_module);
            if ($geolocation_module_id) {
                $allowed_countries = explode(';', (string) Configuration::get('PS_ALLOWED_COUNTRIES'));
                if (!isset($this->context->cookie->iso_code_country) || isset($this->context->cookie->iso_code_country) && !in_array(strtoupper($this->context->cookie->iso_code_country), $allowed_countries)) {
                    // Invoke geolocation module service
                    $res = Hook::get_response('actionGeoLocation', $geolocation_module_id, ['ip' => $ip]);
                    if ($res) {
                        $country_code = strtoupper((string) $res);
                        if (!in_array($country_code, $allowed_countries) && !static::is_in_whitelist_for_geolocation()) {
                            if (Configuration::get('PS_GEOLOCATION_BEHAVIOR') == _PS_GEOLOCATION_NO_CATALOG_) {
                                $this->restricted_country = true;
                            } elseif (Configuration::get('PS_GEOLOCATION_BEHAVIOR') == _PS_GEOLOCATION_NO_ORDER_) {
                                $country_name = $country_code;
                                $country = new Country(Country::get_by_iso($country_code), $this->context->language->id);
                                if (Validate::is_loaded_object($country)) {
                                    $country_name = $country->name;
                                }
                                $this->context->smarty->assign(['restricted_country_mode' => true, 'geolocation_country' => $country_name]);
                            }
                        } else {
                            $has_been_set = !isset($this->context->cookie->iso_code_country);
                            $this->context->cookie->iso_code_country = $country_code;
                        }
                    }
                }
                if (isset($this->context->cookie->iso_code_country) && $this->context->cookie->iso_code_country && !Validate::is_language_iso_code($this->context->cookie->iso_code_country)) {
                    $this->context->cookie->iso_code_country = Country::get_iso_by_id(Configuration::get('PS_COUNTRY_DEFAULT'));
                }
                if (isset($this->context->cookie->iso_code_country) && $id_country = (int) Country::get_by_iso(strtoupper($this->context->cookie->iso_code_country))) {
                    /* Update defaultCountry */
                    if ($default_country->iso_code != $this->context->cookie->iso_code_country) {
                        $default_country = new Country($id_country);
                    }
                    if (isset($has_been_set) && $has_been_set) {
                        $this->context->cookie->id_currency = $default_country->id_currency ? (int) $default_country->id_currency : (int) Configuration::get('PS_CURRENCY_DEFAULT');
                    }
                    return $default_country;
                }
                if (Configuration::get('PS_GEOLOCATION_NA_BEHAVIOR') == _PS_GEOLOCATION_NO_CATALOG_ && !Front_Controller::is_in_whitelist_for_geolocation()) {
                    $this->restricted_country = true;
                } elseif (Configuration::get('PS_GEOLOCATION_NA_BEHAVIOR') == _PS_GEOLOCATION_NO_ORDER_ && !Front_Controller::is_in_whitelist_for_geolocation()) {
                    $this->context->smarty->assign(['restricted_country_mode' => true, 'geolocation_country' => '']);
                }
            }
        }
        return false;
    }
    /**
     * Checks if user's location is whitelisted.
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected static function is_in_whitelist_for_geolocation()
    {
        static $allowed = null;
        if ($allowed !== null) {
            return $allowed;
        }
        $allowed = false;
        $user_ip = Tools::get_remote_addr();
        $ips = [];
        // retrocompatibility
        $ips_old = explode(';', (string) Configuration::get('PS_GEOLOCATION_WHITELIST'));
        if (count($ips_old)) {
            foreach ($ips_old as $ip) {
                $ips = array_merge($ips, explode("\n", $ip));
            }
        }
        foreach ($ips as $ip) {
            $ip = trim((string) $ip);
            if ($ip && preg_match('/^' . $ip . '.*/', $user_ip)) {
                $allowed = true;
            }
        }
        return $allowed;
    }
    /**
     * Redirects to canonical URL.
     *
     * @param string $canonicalUrl
     *
     * @throws PrestaShopException
     */
    protected function canonical_redirection($canonical_url = '')
    {
        if (!$canonical_url || !Configuration::get('PS_CANONICAL_REDIRECT') || Tools::get_request_method() !== 'GET' || Tools::get_value('live_edit')) {
            return;
        }
        $match_url = rawurldecode(Tools::get_current_url_protocol_prefix() . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        if (Tools::using_secure_mode()) {
            // Do not redirect to the same page on HTTP
            if (substr_replace($canonical_url, 'https', 0, 4) === $match_url) {
                return;
            }
        }
        if (!preg_match('/^' . Tools::p_regexp(rawurldecode($canonical_url), '/') . '([&?].*)?$/', $match_url)) {
            $params = [];
            $url_details = parse_url($canonical_url);
            if (!empty($url_details['query'])) {
                parse_str($url_details['query'], $query);
                foreach ($query as $key => $value) {
                    $params[Tools::safe_output($key)] = Tools::safe_output($value);
                }
            }
            $excluded_key = ['isolang', 'id_lang', 'controller', 'fc', 'id_product', 'id_category', 'id_manufacturer', 'id_supplier', 'id_cms'];
            foreach ($_GET as $key => $value) {
                if (!in_array($key, $excluded_key) && Validate::is_url($key)) {
                    if (is_array($value)) {
                        $array_params = [];
                        foreach ($value as $param_key => $array_param) {
                            if (Validate::is_url($array_param)) {
                                $array_params[$param_key] = Tools::safe_output($array_param);
                            }
                        }
                        $params[Tools::safe_output($key)] = $array_params;
                    } else if (Validate::is_url($value)) {
                        $params[Tools::safe_output($key)] = Tools::safe_output($value);
                    }
                }
            }
            $str_params = http_build_query($params, '', '&');
            if (!empty($str_params)) {
                $final_url = preg_replace('/^([^?]*)?.*$/', '$1', $canonical_url) . '?' . $str_params;
            } else {
                $final_url = preg_replace('/^([^?]*)?.*$/', '$1', $canonical_url);
            }
            // Don't send any cookie
            $this->context->cookie->disallow_writing();
            $redirect_type = Configuration::get('PS_CANONICAL_REDIRECT') == 2 ? '301' : '302';
            header('HTTP/1.0 ' . $redirect_type . ' Moved');
            header('Cache-Control: no-cache');
            Tools::redirect_link($final_url);
        }
    }
    /**
     * Displays 'country restricted' page if user's country is not allowed.
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function display_restricted_country_page()
    {
        header('HTTP/1.1 503 temporarily overloaded');
        $this->context->smarty->assign(['shop_name' => $this->context->shop->name, 'favicon_url' => _PS_IMG_ . Configuration::get('PS_FAVICON'), 'logo_url' => $this->context->link->get_media_link(_PS_IMG_ . Configuration::get('PS_LOGO'))]);
        $this->smarty_output_content($this->get_template_path($this->get_theme_dir() . 'restricted-country.tpl'));
        exit;
    }
    /**
     * Checks if token is valid.
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_token_valid()
    {
        if (!Configuration::get('PS_TOKEN_ENABLE')) {
            return true;
        }
        return strcasecmp(Tools::get_token(false), Tools::get_value('token')) == 0;
    }
    /**
     * Removes CSS file(s) from page header.
     *
     * @param array|string $cssUri Path to file, or an array of paths like: array(array(uri => media_type), ...)
     * @param string $cssMediaType CSS media type
     * @param bool $checkPath If true, checks if files exists
     */
    public function remove_css($css_uri, $css_media_type = 'all', $check_path = true)
    {
        return $this->remove_media($css_uri, $css_media_type, $check_path);
    }
    /**
     * Removes media file(s) from page header.
     *
     * @param string|array $mediaUri Path to file, or an array paths of like: array(array(uri => media_type), ...)
     * @param string|null $cssMediaType CSS media type
     * @param bool $checkPath If true, checks if files exists
     */
    public function remove_media($media_uri, $css_media_type = null, $check_path = true): void
    {
        $this->add_media($media_uri, $css_media_type, null, true, $check_path);
    }
    /**
     * Removes JS file(s) from page header.
     *
     * @param array|string $jsUri Path to file, or an array of paths
     * @param bool $checkPath If true, checks if files exists
     */
    public function remove_js($js_uri, $check_path = true)
    {
        return $this->remove_media($js_uri, null, $check_path);
    }
    /**
     * Sets template file for page content output.
     *
     * @param string $defaultTemplate
     *
     * @throws PrestaShopException
     */
    public function set_template($default_template): void
    {
        if ($this->use_mobile_theme()) {
            $this->set_mobile_template($default_template);
        } else {
            $template = $this->get_override_template();
            if (!$template) {
                $template = $default_template;
            }
            $theme = Context::get_context()->theme;
            $theme->ensure_template($template);
            parent::set_template($template);
        }
    }
    /**
     * Checks if the template set is available for mobile themes,
     * otherwise front template is chosen.
     *
     * @param string $template
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_mobile_template($template): void
    {
        // Needed for site map
        $blockmanufacturer = Module::get_instance_by_name('blockmanufacturer');
        $blocksupplier = Module::get_instance_by_name('blocksupplier');
        $this->context->smarty->assign(['categoriesTree' => Category::get_root_category()->recurse_lite_categ_tree(0), 'categoriescmsTree' => Cms_Category::get_recurse_category($this->context->language->id, 1, 1, 1), 'voucherAllowed' => (int) Cart_Rule::is_feature_active(), 'display_manufacturer_link' => (bool) $blockmanufacturer->active, 'display_supplier_link' => (bool) $blocksupplier->active, 'PS_DISPLAY_SUPPLIERS' => Configuration::get('PS_DISPLAY_SUPPLIERS'), 'PS_DISPLAY_BEST_SELLERS' => Configuration::get('PS_DISPLAY_BEST_SELLERS'), 'display_store' => Configuration::get('PS_STORES_DISPLAY_SITEMAP'), 'conditions' => Configuration::get('PS_CONDITIONS'), 'id_cgv' => Configuration::get('PS_CONDITIONS_CMS_ID'), 'PS_SHOP_NAME' => Configuration::get('PS_SHOP_NAME')]);
        $template = $this->get_template_path($template);
        $assign = [];
        $assign['tpl_file'] = basename($template, '.tpl');
        if (isset($this->php_self)) {
            $assign['controller_name'] = $this->php_self;
        }
        $this->context->smarty->assign($assign);
        $this->template = $template;
    }
    /**
     * Returns an overridden template path (if any) for this controller.
     * If not overridden, will return false. This method can be easily overriden in a
     * specific controller.
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function get_override_template()
    {
        return (string) Hook::get_first_response('DisplayOverrideTemplate', ['controller' => $this]);
    }
    /**
     * Renders and adds color list HTML for each product in a list.
     *
     * @param array $products
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function add_colors_to_product_list(&$products): void
    {
        if (!is_array($products) || !count($products) || !file_exists(_PS_THEME_DIR_ . 'product-list-colors.tpl')) {
            return;
        }
        $products_need_cache = [];
        foreach ($products as &$product) {
            if (!$this->is_cached(_PS_THEME_DIR_ . 'product-list-colors.tpl', $this->get_colors_list_cache_id($product['id_product']))) {
                $products_need_cache[] = (int) $product['id_product'];
            }
        }
        unset($product);
        $colors = false;
        if ($products_need_cache) {
            $colors = Product::get_attributes_color_list($products_need_cache);
        }
        Tools::enable_cache();
        foreach ($products as &$product) {
            $cache_id = $this->get_colors_list_cache_id($product['id_product']);
            $tpl = $this->context->smarty->create_template(_PS_THEME_DIR_ . 'product-list-colors.tpl', $cache_id);
            if (isset($colors[$product['id_product']])) {
                $tpl->assign(['id_product' => $product['id_product'], 'colors_list' => $colors[$product['id_product']], 'link' => Context::get_context()->link, 'img_col_dir' => _THEME_COL_DIR_, 'col_img_dir' => _PS_COL_IMG_DIR_]);
            }
            if (!in_array($product['id_product'], $products_need_cache) || isset($colors[$product['id_product']])) {
                $product['color_list'] = $tpl->fetch(_PS_THEME_DIR_ . 'product-list-colors.tpl', $cache_id);
            } else {
                $product['color_list'] = '';
            }
        }
        Tools::restore_cache_settings();
    }
    /**
     * Returns cache ID for product color list.
     *
     * @param int $idProduct
     *
     * @return string
     */
    protected function get_colors_list_cache_id($id_product)
    {
        return Product::get_colors_list_cache_id($id_product);
    }
    /**
     * Redirects to redirect_after link.
     *
     * @throws PrestaShopException
     */
    protected function redirect()
    {
        Tools::redirect_link($this->redirect_after);
    }
    /**
     * Saves user preference about 'Mobile Theme' into cookie. This allows
     * visitors to opt out from using mobile theme variant
     *
     * @param bool $allowed
     * @return void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function set_mobile_theme_allowed(Cookie $cookie, $allowed)
    {
        $allowed = (bool) $allowed;
        $cookie->no_mobile = !$allowed;
        if ($cookie->id_guest) {
            $guest = new Guest($cookie->id_guest);
            if (Validate::is_loaded_object($guest) && (bool) $guest->mobile_theme !== $allowed) {
                $guest->mobile_theme = $allowed;
                $guest->update();
            }
        }
    }
    /**
     *
     * @throws PrestaShopException
     */
    protected function get_href_lang_mapping(array $languages, int $default_lang_id): array
    {
        $target_shop_id = (int) $this->context->shop->id;
        // allow modules to override hreng mappings
        $response = Hook::get_first_response('actionHrefLangMapping');
        if (is_array($response)) {
            return $response;
        }
        $mapping = [];
        foreach ($languages as $lang) {
            $code = $lang['language_code'];
            $target_lang_id = (int) $lang['id_lang'];
            $is_default = $target_lang_id === $default_lang_id;
            $mapping[$code] = ['targetShopId' => $target_shop_id, 'targetLangId' => $target_lang_id, 'isDefault' => $is_default];
        }
        return $mapping;
    }
    /**
     * Returns canonical url to current page, if known
     *
     * @return string|null
     * @throws PrestaShopException
     */
    protected function get_current_page_canonical_url()
    {
        return $this->add_current_pagination_parameters_to_url($this->get_current_page_alternate_url((int) $this->context->shop->id, (int) $this->context->language->id));
    }
    /**
     * Returns current page next/prev link tags, if they exits
     *
     * @return string|null
     */
    protected function get_current_page_prev_next_rel_tags()
    {
        return null;
    }
    /**
     * Returns alternate url for current page
     *
     *
     * @return string|null
     * @throws PrestaShopException
     */
    protected function get_current_page_alternate_url(int $shop_id, int $language_id)
    {
        $route_id = (string) $this->php_self;
        if ($route_id) {
            $dispatcher = Dispatcher::get_instance();
            if ($info = $dispatcher->is_module_controller_route($route_id)) {
                // include only required $_GET parameters and ignore others
                $params = array_intersect_key($_GET, $dispatcher->get_route_required_params($route_id, $language_id));
                return $this->context->link->get_module_link($info['module'], $info['controller'], $params, null, $language_id, $shop_id);
            }
            return $this->context->link->get_page_link($route_id, null, $language_id, null, false, $shop_id);
        }
        return null;
    }
    /**
     * Returns alternate hreflang link tags
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function get_current_page_href_lang_tags()
    {
        $languages = Language::get_languages(true, $this->context->shop->id);
        $id_lang_default = (int) Configuration::get('PS_LANG_DEFAULT');
        $mapping = $this->get_href_lang_mapping($languages, $id_lang_default);
        $default = null;
        $links = [];
        foreach ($mapping as $language_code => $target) {
            $shop_id = (int) $target['targetShopId'];
            $language_id = (int) $target['targetLangId'];
            $is_default = (bool) $target['isDefault'];
            $lnk = $this->add_current_pagination_parameters_to_url($this->get_current_page_alternate_url($shop_id, $language_id));
            if ($lnk) {
                $links[] = '<link rel="alternate" hreflang="' . $language_code . '" href="' . $lnk . '">';
                if ($is_default) {
                    $default = '<link rel="alternate" hreflang="x-default" href="' . $lnk . '">';
                }
            }
        }
        if ($default) {
            $links[] = $default;
        }
        return $links;
    }
    /**
     * Helper method that adds pagination parameters 'p' and 'n' to url
     *
     * @param string|null $url
     * @return string|null
     *
     * @throws PrestaShopException
     */
    protected function add_current_pagination_parameters_to_url($url)
    {
        if ($url) {
            // add page number, unless it's first page
            $p = Tools::get_int_value('p');
            if ($p > 1) {
                $url = Tools::url($url, "p={$p}");
            }
            // add page size, unless it's default page size
            $default_products_per_page = max(1, (int) Configuration::get('PS_PRODUCTS_PER_PAGE'));
            $n = Tools::get_int_value('n');
            if ($n >= 1 && $n !== $default_products_per_page) {
                $url = Tools::url($url, "n={$n}");
            }
        }
        return $url;
    }
}