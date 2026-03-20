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
use Guzzle_Http\Client;
/**
 * Class ThemeCore
 */
class Theme_Core extends Object_Model
{
    public const CACHE_FILE_CUSTOMER_THEMES_LIST = '/config/xml/customer_themes_list.xml';
    public const CACHE_FILE_MUST_HAVE_THEMES_LIST = '/config/xml/must_have_themes_list.xml';
    public const UPLOADED_THEME_DIR_NAME = 'uploaded';
    /** @var int access rights of created folders (octal) */
    public static $access_rights = 0775;
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'theme', 'primary' => 'id_theme', 'primaryKeyDbType' => 'int(11)', 'fields' => ['name' => ['type' => self::TYPE_STRING, 'validate' => 'isGenericName', 'size' => 64, 'required' => true], 'directory' => ['type' => self::TYPE_STRING, 'validate' => 'isDirName', 'size' => 64, 'required' => true], 'responsive' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'], 'default_left_column' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'], 'default_right_column' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'], 'product_per_page' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbNullable' => false]]];
    /** @var string $name */
    public $name;
    /** @var string $directory */
    public $directory;
    /** @var bool $responsive */
    public $responsive;
    /** @var bool $default_left_column */
    public $default_left_column;
    /** @var bool $default_right_column */
    public $default_right_column;
    /** @var int $product_per_page */
    public $product_per_page;
    /**
     * @param bool $excludedIds
     *
     * @return PrestaShopCollection
     *
     * @throws PrestaShopException
     */
    public static function get_all_themes($excluded_ids = false)
    {
        $themes = new Presta_Shop_Collection('Theme');
        if (is_array($excluded_ids) && !empty($excluded_ids)) {
            $themes->where('id_theme', 'notin', $excluded_ids);
        }
        $themes->order_by('name');
        return $themes;
    }
    /**
     * return an array of all available theme (installed or not)
     *
     * @param bool $installedOnly
     *
     * @return array string (directory)
     * @throws PrestaShopException
     */
    public static function get_available($installed_only = true)
    {
        static $dirlist = [];
        $available_theme = [];
        if (empty($dirlist)) {
            $themes = scandir(_PS_ALL_THEMES_DIR_);
            foreach ($themes as $theme) {
                if (is_dir(_PS_ALL_THEMES_DIR_ . DIRECTORY_SEPARATOR . $theme) && $theme[0] != '.') {
                    $dirlist[] = $theme;
                }
            }
        }
        $themes_dir = [];
        if ($installed_only) {
            $themes = Theme::get_themes();
            foreach ($themes as $theme_obj) {
                /** @var Theme $themeObj */
                $themes_dir[] = $theme_obj->directory;
            }
            foreach ($dirlist as $theme) {
                if (in_array($theme, $themes_dir)) {
                    $available_theme[] = $theme;
                }
            }
        } else {
            $available_theme = $dirlist;
        }
        return $available_theme;
    }
    /**
     * Returns all installed themes
     *
     * @return PrestaShopCollection
     *
     * @throws PrestaShopException
     */
    public static function get_themes()
    {
        $themes = new Presta_Shop_Collection('Theme');
        $themes->order_by('name');
        return $themes;
    }
    /**
     * Returns all installed themes that are actually used by some shop
     *
     * @return Theme[]
     *
     * @throws PrestaShopException
     */
    public static function get_used_themes()
    {
        $used_themes = [];
        /** @var Theme $theme */
        foreach (static::get_themes() as $theme) {
            if ($theme->is_used()) {
                $used_themes[] = $theme;
            }
        }
        return $used_themes;
    }
    /**
     * Find a theme by name.
     *
     * @param string $name
     *
     * @return bool|Theme Theme instance on success, false if theme not found.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_by_name($name)
    {
        $id_theme = (int) Db::read_only()->get_value((new Db_Query())->select('`id_theme`')->from('theme')->where('`name` = \'' . p_sql($name) . '\''));
        if ($id_theme) {
            return new Theme($id_theme);
        }
        return false;
    }
    /**
     * Checks if theme exists (by folder) and returns Theme object.
     *
     * @param string $directory
     *
     * @return bool|Theme
     *
     * @throws PrestaShopException
     */
    public static function get_by_directory($directory)
    {
        if (is_string($directory) && strlen($directory) > 0 && file_exists(_PS_ALL_THEMES_DIR_ . $directory) && is_dir(_PS_ALL_THEMES_DIR_ . $directory)) {
            $id_theme = (int) Db::read_only()->get_value((new Db_Query())->select('`id_theme`')->from('theme')->where('`directory` = \'' . p_sql($directory) . '\''));
            return $id_theme ? new Theme($id_theme) : false;
        }
        return false;
    }
    /**
     * @param int $idTheme
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function get_theme_info($id_theme)
    {
        $theme = new Theme((int) $id_theme);
        $theme_arr = [];
        $xml_theme = $theme->load_config_file();
        if ($xml_theme) {
            $theme_arr['theme_id'] = (int) $theme->id;
            foreach ($xml_theme->attributes() as $key => $value) {
                $theme_arr['theme_' . $key] = (string) $value;
            }
            foreach ($xml_theme->author->attributes() as $key => $value) {
                $theme_arr['author_' . $key] = (string) $value;
            }
            if ($theme_arr['theme_name'] == 'community-theme-default') {
                $theme_arr['tc'] = Module::is_enabled('themeconfigurator');
            }
        } else {
            // If no xml we use data from database
            $theme_arr['theme_id'] = (int) $theme->id;
            $theme_arr['theme_name'] = $theme->name;
            $theme_arr['theme_directory'] = $theme->directory;
        }
        return $theme_arr;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_non_installed_theme()
    {
        $installed_theme_directories = Theme::get_installed_theme_directories();
        $not_installed_theme = [];
        foreach (scandir(_PS_ALL_THEMES_DIR_) as $dir) {
            if (is_dir(_PS_ALL_THEMES_DIR_ . $dir) && !in_array($dir, ['.', '..']) && !in_array($dir, $installed_theme_directories)) {
                $xml_theme = static::load_default_config(_PS_ALL_THEMES_DIR_ . $dir);
                if ($xml_theme) {
                    $theme = [];
                    foreach ($xml_theme->attributes() as $key => $value) {
                        $theme[$key] = (string) $value;
                    }
                    if (!empty($theme)) {
                        $not_installed_theme[] = $theme;
                    }
                }
            }
        }
        return $not_installed_theme;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_installed_theme_directories()
    {
        $list = [];
        $tmp = Db::read_only()->get_array((new Db_Query())->select('`directory`')->from('theme'));
        foreach ($tmp as $t) {
            $list[] = $t['directory'];
        }
        return $list;
    }
    /**
     * check if a theme is used by a shop
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function is_used()
    {
        return Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from('shop')->where('`id_theme` = ' . (int) $this->id));
    }
    /**
     * add only theme if the directory exists
     *
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool Insertion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if (!is_dir(_PS_ALL_THEMES_DIR_ . $this->directory)) {
            return false;
        }
        return parent::add($auto_date, $null_values);
    }
    /**
     * update the table PREFIX_theme_meta for the current theme
     *
     * @param array $metas
     * @param bool $fullUpdate If true, all the meta of the theme will be deleted prior the insert, otherwise only the current $metas will be deleted
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_metas($metas, $full_update = false): void
    {
        $conn = Db::get_instance();
        if ($full_update) {
            $conn->delete('theme_meta', 'id_theme=' . (int) $this->id);
        }
        $values = [];
        if ($this->id > 0) {
            foreach ($metas as $meta) {
                if (!$full_update) {
                    $conn->delete('theme_meta', 'id_theme=' . (int) $this->id . ' AND id_meta=' . (int) $meta['id_meta']);
                }
                $values[] = ['id_theme' => (int) $this->id, 'id_meta' => (int) $meta['id_meta'], 'left_column' => (int) $meta['left'], 'right_column' => (int) $meta['right']];
            }
            $conn->insert('theme_meta', $values);
        }
    }
    /**
     * @param string $page
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function has_columns($page)
    {
        return Db::read_only()->get_row((new Db_Query())->select('IFNULL(`left_column`, `default_left_column`) AS `left_column`, IFNULL(`right_column`, `default_right_column`) AS `right_column`')->from('theme', 't')->left_join('theme_meta', 'tm', 't.`id_theme` = tm.`id_theme`')->left_join('meta', 'm', 'm.`id_meta` = tm.`id_meta`')->where('t.`id_theme` = ' . (int) $this->id)->where('m.`page` = \'' . p_sql($page) . '\''));
    }
    /**
     * @param string $page
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function has_columns_settings($page)
    {
        return (bool) Db::read_only()->get_value((new Db_Query())->select('m.`id_meta`')->from('theme', 't')->left_join('theme_meta', 'tm', 't.`id_theme` = tm.`id_theme`')->left_join('meta', 'm', 'm.`id_meta` = tm.`id_meta`')->where('t.`id_theme` = ' . (int) $this->id)->where('m.`page` = \'' . p_sql($page) . '\''));
    }
    /**
     * @param string|null $page
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function has_left_column($page = null)
    {
        return (bool) Db::read_only()->get_value((new Db_Query())->select('IFNULL(`left_column`, `default_left_column`)')->from('theme', 't')->left_join('theme_meta', 'tm', 't.`id_theme` = tm.`id_theme`')->left_join('meta', 'm', 'm.`id_meta` = tm.`id_meta`')->where('t.`id_theme` = ' . (int) $this->id)->where('m.`page` = \'' . p_sql($page) . '\''));
    }
    /**
     * @param string|null $page
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function has_right_column($page = null)
    {
        return (bool) Db::read_only()->get_value((new Db_Query())->select('IFNULL(`right_column`, `default_right_column`)')->from('theme', 't')->left_join('theme_meta', 'tm', 't.`id_theme` = tm.`id_theme`')->left_join('meta', 'm', 'm.`id_meta` = tm.`id_meta`')->where('t.`id_theme` = ' . (int) $this->id)->where('m.`page` = \'' . p_sql($page) . '\''));
    }
    /**
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_metas()
    {
        if (!Validate::is_unsigned_id($this->id) || $this->id == 0) {
            return false;
        }
        return Db::read_only()->get_array((new Db_Query())->select('*')->from('theme_meta')->where('`id_theme` = ' . (int) $this->id));
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function remove_metas()
    {
        if (!Validate::is_unsigned_id($this->id) || $this->id == 0) {
            return false;
        }
        return Db::get_instance()->delete('theme_meta', 'id_theme = ' . (int) $this->id);
    }
    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function toggle_responsive()
    {
        // Object must have a variable called 'responsive'
        if (!method_exists($this, 'responsive')) {
            throw new Presta_Shop_Exception('property "responsive" is missing in object ' . static::class);
        }
        // Update only responsive field
        $this->set_fields_to_update(['responsive' => true]);
        // Update active responsive on object
        $this->responsive = !(int) $this->responsive;
        // Change responsive to active/inactive
        return $this->update(false);
    }
    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function toggle_default_left_column()
    {
        if (!method_exists($this, 'default_left_column')) {
            throw new Presta_Shop_Exception('property "default_left_column" is missing in object ' . static::class);
        }
        $this->set_fields_to_update(['default_left_column' => true]);
        $this->default_left_column = !(int) $this->default_left_column;
        return $this->update(false);
    }
    /**
     * @return bool
     * @throws PrestaShopException
     */
    public function toggle_default_right_column()
    {
        if (!method_exists($this, 'default_right_column')) {
            throw new Presta_Shop_Exception('property "default_right_column" is missing in object ' . static::class);
        }
        $this->set_fields_to_update(['default_right_column' => true]);
        $this->default_right_column = !(int) $this->default_right_column;
        return $this->update(false);
    }
    /**
     * Get the configuration file as an array
     *
     * @return array
     */
    public function get_configuration()
    {
        $ob = $this->load_config_file();
        if ($ob) {
            // convert SimpleXMLElement to array
            return json_decode(json_encode($ob), true);
        }
        return [];
    }
    /**
     * Install a theme with just the directory given.
     *
     * Note that files of the theme might be located not yet in
     * _PS_ALL_THEMES_DIR_ (themes/). If not, the theme gets moved into the
     * right place.
     *
     * @param string $themeDir Theme directory inside _PS_ALL_THEMES_DIR_.
     *
     * @return Theme|string Error message or Theme instance on success.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function install_from_dir($theme_dir)
    {
        if (!file_exists($theme_dir)) {
            return sprintf(Tools::display_error('Theme directory not found: "%s"'), $theme_dir);
        }
        $xml = static::load_default_config($theme_dir);
        if (!$xml) {
            return sprintf(Tools::display_error('Bad or missing config.xml in theme in %s.'), $theme_dir);
        }
        $xml_attributes = $xml->attributes();
        if (static::get_by_name((string) $xml_attributes['name']) !== false) {
            return sprintf(Tools::display_error('A theme with the same name as the theme in %s is already installed.'), $theme_dir);
        }
        $theme = new Theme();
        $theme->name = (string) $xml_attributes['name'];
        $theme->directory = (string) $xml_attributes['directory'];
        // These are defaults, likely overwritten by the variation.
        $theme->product_per_page = Configuration::get('PS_PRODUCTS_PER_PAGE');
        $theme->responsive = false;
        $theme->default_left_column = true;
        $theme->default_right_column = true;
        /**
         * This is an intentional deviation from PrestaShop: only the first
         * variation gets installed, 'name' and 'directory' of the variation
         * gets ignored. Having theme distributions with multiple variations is
         * considered to be overengineering.
         */
        if (isset($xml->variations)) {
            if (count($xml->variations) > 1) {
                return sprintf(Tools::display_error('thirty bees supports only themes with at most one variation, the theme in %s has multiple ones.'), $theme_dir);
            }
            $variation = $xml->variations->variation[0];
            if (isset($variation['product_per_page'])) {
                $theme->product_per_page = (int) $variation['product_per_page'];
            }
            if (isset($variation['responsive'])) {
                $theme->responsive = (bool) (string) $variation['responsive'];
            }
            if (isset($variation['default_left_column'])) {
                $theme->default_left_column = (bool) (string) $variation['default_left_column'];
            }
            if (isset($variation['default_right_column'])) {
                $theme->default_right_column = (bool) (string) $variation['default_right_column'];
            }
        }
        $theme->add();
        if (!Validate::is_loaded_object($theme)) {
            return sprintf(Tools::display_error('Error while installing theme in %s'), $theme_dir);
        }
        return $theme;
    }
    /**
     * Install this theme in the current shop context.
     *
     * @return array Array with the following entries:
     *               'imageTypes':   List of image types updated or created for
     *                               this theme. Each item an array with 'name',
     *                               'width' and 'height'.
     *               'moduleErrors': List of module installation errors. Each
     *                               item with 'module_name' and an 'errors',
     *                               an array with error messages.
     *               'documents':    Array with documentation links.
     *               'warnings' :    List of warnings
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install_into_shop_context()
    {
        $return = ['imageTypes' => [], 'moduleErrors' => [], 'documents' => [], 'warnings' => ['ignoredHooks' => [], 'ignoredModules' => [], 'unmanagedModules' => []]];
        $xml = $this->load_config_file();
        if ($xml) {
            /**
             * Create/update image types.
             */
            if (isset($xml->images->image)) {
                $image_entities = Image_Entity::get_all();
                foreach ($xml->images->image as $image_type) {
                    // It's installation time, name variants can get ignored.
                    // create/update ImageType
                    $image_type_obj = Image_Type::get_instance_by_name((string) $image_type['name'], $this->name);
                    $image_type_obj->width = (int) $image_type['width'];
                    $image_type_obj->height = (int) $image_type['height'];
                    $image_type_obj->save();
                    // associate ImageType with ImageEnity
                    foreach ($image_entities as $image_entity) {
                        if ((string) $image_type[$image_entity->name] === 'true') {
                            $image_entity->associate_image_type((int) $image_type_obj->id);
                        }
                    }
                    $return['imageTypes'][] = ['name' => $image_type_obj->name, 'width' => $image_type_obj->width, 'height' => $image_type_obj->height];
                }
            }
            /**
             * Install/enable/disable theme related modules. All Module methods
             * used work on the current shop context.
             */
            $unrelated_modules = Module::get_not_theme_related_modules();
            $hooks = static::get_hooks_from_config_file($xml, $return['warnings']['ignoredHooks']);
            foreach ($xml->modules->module as $module_row) {
                $module_name = (string) $module_row['name'];
                $module_action = strtolower((string) $module_row['action']);
                $module = Module::get_instance_by_name($module_name);
                if (!$module) {
                    continue;
                }
                if (in_array($module_name, $unrelated_modules)) {
                    $return['warnings']['ignoredModules'][] = ['module' => $module_name, 'action' => $module_action];
                    continue;
                }
                switch ($module_action) {
                    case 'install':
                    case 'enable':
                        $manage_hooks = true;
                        if (isset($module_row['manageHooks'])) {
                            $value = strtolower((string) $module_row['manageHooks']);
                            $manage_hooks = $value === 'true';
                        }
                        $module_hooks = $hooks[$module_name] ?? [];
                        $result = $this->install_module($module, $manage_hooks, $module_hooks, $return['warnings']['unmanagedModules']);
                        if ($result !== true) {
                            $return['moduleErrors'][] = ['module_name' => $module_name, 'errors' => $result];
                        }
                        break;
                    case 'disable':
                        $module->disable();
                        break;
                }
            }
            /**
             * Create/update theme metas.
             */
            $metas_xml = [];
            // Collect defined metas.
            if ($xml->metas->meta) {
                foreach ($xml->metas->meta as $meta) {
                    $meta_id = Db::read_only()->get_value((new Db_Query())->select('`id_meta`')->from('meta')->where('`page` = \'' . p_sql($meta['meta_page']) . '\''));
                    $meta_id = (int) $meta_id;
                    if ($meta_id) {
                        $metas_xml[$meta_id] = ['id_meta' => $meta_id, 'left' => (bool) (int) $meta['left'], 'right' => (bool) (int) $meta['right']];
                    }
                }
            }
            // Fill all other metas with default values.
            foreach (Meta::get_metas() as $meta) {
                $meta_id = (int) $meta['id_meta'];
                if (!array_key_exists($meta_id, $metas_xml)) {
                    $metas_xml[$meta_id] = ['id_meta' => $meta_id, 'left' => $this->default_left_column, 'right' => $this->default_right_column];
                }
            }
            $this->update_metas($metas_xml, true);
            /**
             * Install the theme into all shops of the current context.
             */
            $shops = Shop::get_context_list_shop_id();
            foreach ($shops as $id_shop) {
                $shop = new Shop((int) $id_shop);
                $shop->id_theme = $this->id;
                $shop->save();
                if (Shop::is_feature_active()) {
                    Configuration::update_value('PS_PRODUCTS_PER_PAGE', (int) $this->product_per_page, false, null, (int) $id_shop);
                } else {
                    Configuration::update_value('PS_PRODUCTS_PER_PAGE', (int) $this->product_per_page);
                }
            }
            $context = Context::get_context();
            $context->shop->id_theme = $this->id;
            $context->shop->update();
            /**
             * Create documentation link.
             */
            foreach ($xml->docs->doc as $row) {
                $return['documents'][(string) $row['name']] = preg_replace('#^' . _PS_ROOT_DIR_ . '#', __PS_BASE_URI__, _PS_ALL_THEMES_DIR_) . $this->directory . '/' . $row['path'];
            }
        } else {
            // Invalid themes shouldn't get offered for installation.
            throw new Presta_Shop_Exception('Attempt to install theme ' . $this->name . ' despite its invalid config.xml.');
        }
        return $return;
    }
    /**
     * Installs module required by theme
     *
     * Module is installed (or enabled). If theme manages module hooks, then all module's displayable hooks
     * will be unhooked and replaced with the hook list from theme configuration file.
     * Returns true, if module is installed/enabled successfully. Otherwise, array of errors are returned
     *
     * @param Module $module module instance
     * @param bool $manageHooks if true, module displayable hooks are fully managed by theme
     * @param array $hooks list of module hooks from config.xml file
     * @param array $warnings array to collect warnings
     *
     * @return true | array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function install_module(Module $module, $manage_hooks, array $hooks, array &$warnings)
    {
        $module_name = $module->name;
        // install module if it's not installed yet
        if (!Module::is_installed($module_name)) {
            if (!$module->install()) {
                return $module->get_errors();
            }
        }
        $module->enable();
        // theme can mark some modules as un-managed - module hooks will not be modified during theme installation
        if (!$manage_hooks) {
            return true;
        }
        // get list of displayable hooks this module supports
        $displayable_hooks = $module->get_displayable_hook_list();
        // if module has some displayable hooks, but theme does not specify any, it's most
        // likely a bug in theme config.xml
        if ($displayable_hooks && !$hooks) {
            $warnings[] = $module_name;
            return true;
        }
        // replace module default displayable hooks and hook exceptions with those from the theme configuration.
        foreach ($displayable_hooks as $hook) {
            $module->unregister_exceptions($hook['id_hook']);
            $module->unregister_hook($hook['id_hook']);
        }
        foreach ($hooks as $hook) {
            $module->register_hook($hook['name']);
            $id_hook = Hook::get_id_by_name($hook['name']);
            if ($hook['position']) {
                $module->update_position($id_hook, false, $hook['position']);
            }
            if ($hook['exceptions']) {
                $module->register_exceptions($id_hook, $hook['exceptions']);
            }
        }
        return true;
    }
    /**
     * Get the configuration file as SimpleXMLElement
     *
     * @param boolean $validate - if true, configuration file will be validated
     *
     * @return SimpleXMLElement | false
     *
     *                inside the theme directory.
     */
    public function load_config_file($validate = false)
    {
        $this->collect_config_files_for_retrocompatibility();
        $xml = static::load_default_config(_PS_ALL_THEMES_DIR_ . $this->directory);
        if (!$xml || (string) $xml->attributes()->name !== $this->name) {
            return false;
        }
        return $xml;
    }
    /**
     * Retrocompatibility with < 1.1.0: collect old configuration files.
     *
     * Where did these files come from?
     *
     *  - PrestaShop didn't have them in the themes folder, but installed
     *    them in config/xml/themes/. It was a copy of Config.xml in the top
     *    level of a theme package, outside of the actual theme.
     *
     *  - thirty bees < 1.1.0 put a copy of the config.xml in the theme
     *    folder there.
     *
     * TODO: move this into Core Updater.
     */
    private function collect_config_files_for_retrocompatibility(): void
    {
        $old_config_found = false;
        $old_configs = [_PS_CONFIG_DIR_ . 'xml/themes/' . $this->directory . '.xml', _PS_CONFIG_DIR_ . 'xml/themes/' . $this->name . '.xml'];
        if ($this->name === 'community-theme-default') {
            $old_configs[] = _PS_CONFIG_DIR_ . 'xml/themes/default.xml';
        }
        foreach ($old_configs as $old_config) {
            if ($old_config_found) {
                @unlink($old_config);
            } elseif (file_exists($old_config)) {
                rename($old_config, _PS_ALL_THEMES_DIR_ . $this->directory . '/config.xml');
                $old_config_found = true;
            }
        }
    }
    /**
     * Return full path of theme's configuration file.
     *
     * @return string Path of the config file or false if none found.
     *
     *                inside the theme directory.
     * @deprecated 1.1.0 Use loadConfigFile() or loadDefaultConfig() directly.
     */
    public function get_config_file_path()
    {
        $this->collect_config_files_for_retrocompatibility();
        return _PS_ALL_THEMES_DIR_ . $this->directory . '/config.xml';
    }
    /**
     * Get the configuration file as SimpleXMLElement
     *
     * @param string $filePath - path to xml config file to load
     * @param boolean $validate - if true, configuration file will be validated
     *
     * @return SimpleXMLElement | false
     */
    public static function load_config_from_file($file_path, $validate)
    {
        if (file_exists($file_path)) {
            $content = @simplexml_load_file($file_path);
            if ($content && $validate && !static::validate_config_file($content)) {
                return false;
            }
            return $content;
        }
        return false;
    }
    /**
     * Validate xml fields in config file
     *
     * @param SimpleXMLElement $xml
     *
     * @return boolean
     */
    public static function validate_config_file($xml)
    {
        if (!$xml) {
            return false;
        }
        if (!$xml['version'] || !$xml['name']) {
            return false;
        }
        foreach ($xml->variations->variation as $val) {
            if (!$val['name'] || !$val['directory'] || !$val['from']) {
                return false;
            }
        }
        foreach ($xml->modules->module as $val) {
            if (!$val['action'] || !$val['name']) {
                return false;
            }
        }
        foreach ($xml->modules->hooks->hook as $val) {
            if (!$val['module'] || !$val['hook'] || !$val['position']) {
                return false;
            }
        }
        return true;
    }
    /**
     * Get the default configuration file of a theme as SimpleXMLElement. This
     * works for installed themes and for theme packages before import.
     *
     * @param string $themePath Directory of the theme. For installed themes
     *                          that's _PS_ALL_THEMES_DIR_.$theme->directory.
     *
     * @return SimpleXMLElement | false
     */
    public static function load_default_config($theme_path)
    {
        $theme_path = rtrim($theme_path, '/');
        $path = $theme_path . '/config.xml';
        // Preferred name: all lowercase.
        if (!file_exists($path)) {
            // Try to find differently cased variants.
            foreach (scandir($theme_path) as $variant) {
                if (strcasecmp($variant, 'config.xml') === 0) {
                    $path = $theme_path . '/' . $variant;
                    break;
                }
            }
        }
        return static::load_config_from_file($path, true);
    }
    /**
     * Return list of displayable hooks from config.xml indexed by module key
     *
     * @param array $ignored/
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function get_hooks_from_config_file(Simple_Xml_Element $xml, array &$ignored)
    {
        $hooks = [];
        foreach ($xml->modules->hooks->hook as $entry) {
            $module = (string) $entry['module'];
            $hook = (string) $entry['hook'];
            $position = (int) $entry['position'];
            $exceptions = isset($entry['exceptions']) ? explode(',', $entry['exceptions']) : [];
            // we will load only displayable hooks, and ignore others.
            if (Hook::is_displayable_hook($hook)) {
                if (!isset($hooks[$module])) {
                    $hooks[$module] = [];
                }
                $hooks[$module][] = ['name' => $hook, 'position' => $position, 'exceptions' => $exceptions];
            } else {
                $ignored[] = ['module' => $module, 'hook' => $hook];
            }
        }
        return $hooks;
    }
    /**
     * Helper method to ensure that template exists. If front office template does not exists, it will be downloaded
     * from thirty bees api server
     *
     * @param string $template
     * @throws PrestaShopException
     */
    public function ensure_template($template): void
    {
        // template variable usually represents file, simply check if it exists
        if (!@file_exists($template)) {
            // first, resolve relative path
            $directory_path = $this->get_directory_path();
            $template = str_replace('\\', '/', $template);
            if (str_starts_with($template, $directory_path)) {
                $relative_template = substr($template, strlen($directory_path));
            } else {
                $relative_template = $template;
            }
            $this->download_template($directory_path, $relative_template);
            if (!@file_exists($template)) {
                throw new Presta_Shop_Exception('Template ' . $template . ' does not exists');
            }
        }
    }
    /**
     * Downloads missing template from thirty bees api server
     *
     * @param string $directoryPath
     * @param string $relativeTemplate
     * @throws PrestaShopException
     */
    protected function download_template($directory_path, $relative_template)
    {
        // throttle api requests - allow one request per hour per resource
        $cache_key = 'TB_TEMPLATE_' . md5($relative_template);
        $last_attempt = (int) Configuration::get_global_value($cache_key);
        $now = time();
        if ($last_attempt > $now - 3600) {
            return;
        }
        Configuration::update_global_value($cache_key, $now);
        $request = ['action' => 'download-template', 'php' => phpversion(), 'templates' => [$relative_template]];
        $archive_file = tempnam(_PS_CACHE_DIR_, 'theme-templates');
        try {
            $guzzle = new Client(['base_uri' => Configuration::get_api_server(), 'verify' => Configuration::get_ssl_trust_store(), 'timeout' => 20]);
            $guzzle->post('/coreupdater/v2.php', ['form_params' => $request, 'http_errors' => false, 'sink' => $archive_file, 'headers' => ['X-SID' => Configuration::get_server_tracking_id()]]);
            if (!is_file($archive_file)) {
                throw new Presta_Shop_Exception('Failed to download file from thirty bees api server');
            }
            $magic_number = file_get_contents($archive_file, false, null, 0, 2);
            if (@filesize($archive_file) < 100 || strcmp($magic_number, "\x1f\x8b")) {
                // It's an error message response.
                throw new Presta_Shop_Exception('Api error: ' . file_get_contents($archive_file));
            }
            $archive = new Archive_Tar($archive_file, 'gz');
            $archive_paths = $archive->list_content();
            if ($archive->error_object) {
                throw new Presta_Shop_Exception('Failed to open archive: ' . $archive->error_object->message);
            }
            if (count($archive_paths) !== 1 || $archive_paths[0]['filename'] !== $relative_template) {
                throw new Presta_Shop_Exception('Archive contains invalid content: ' . print_r($archive_paths, true));
            }
            $archive->extract($directory_path);
        } catch (Throwable $e) {
            throw new Presta_Shop_Exception('Failed to download file from thirty bees api server', 0, $e);
        } finally {
            @unlink($archive_file);
        }
    }
    /**
     * Returns full path to theme directory
     *
     * @return string
     */
    public function get_directory_path()
    {
        return rtrim(str_replace('\\', '/', _PS_ALL_THEMES_DIR_), '/') . '/' . $this->directory . '/';
    }
    /**
     * Returns true, if current theme supports mobile theme variant
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function supports_mobile_variant()
    {
        return isset($_SERVER['HTTP_USER_AGENT']) && Configuration::get('PS_ALLOW_MOBILE_DEVICE') && file_exists(_PS_THEME_MOBILE_DIR_) && is_dir(_PS_THEME_MOBILE_DIR_);
    }
}