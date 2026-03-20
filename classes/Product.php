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
use Core_Updater\Table_Schema;
use Thirtybees\Core\Initialization_Callback;
/**
 * @deprecated 1.5.0.1
 */
define('_CUSTOMIZE_FILE_', 0);
/**
 * @deprecated 1.5.0.1
 */
define('_CUSTOMIZE_TEXTFIELD_', 1);
/**
 * Class ProductCore
 */
class Product_Core extends Object_Model implements Initialization_Callback
{
    public const CUSTOMIZE_FILE = 0;
    public const CUSTOMIZE_TEXTFIELD = 1;
    /**
     * Note:  prefix is "PTYPE" because TYPE_ is used in ObjectModel (definition)
     */
    public const PTYPE_SIMPLE = 0;
    public const PTYPE_PACK = 1;
    public const PTYPE_VIRTUAL = 2;
    /**
     * @var int|null
     */
    public static $_tax_calculation_method;
    /**
     * @var float
     */
    protected static $_prices = [];
    /**
     * @var array
     */
    protected static $_prices_level2 = [];
    /**
     * @var bool[]
     */
    protected static $_incat = [];
    /**
     * @var array
     * @deprecated 1.0.0 Not used anymore
     */
    protected static $_cart_quantity = [];
    /**
     * @var array
     * @deprecated 1.5.0 Not used anymore
     */
    protected static $_tax_rules_group = [];
    /**
     * @var array
     */
    protected static $_cache_features = [];
    /**
     * @var array
     */
    protected static $_front_features_cache = [];
    /**
     * @var array
     */
    protected static $produc_properties_cache = [];
    /**
     * @var array cache stock data in getStock() method
     * @deprecated 1.5.0 Not used anymore
     */
    protected static $cache_stock = [];
    /**
     * @var array
     */
    protected $_cache_available_quantity = [];
    /**
     * @var string Tax name
     */
    public $tax_name;
    /**
     * @var string Tax rate
     */
    public $tax_rate;
    /**
     * @var int Manufacturer id
     */
    public $id_manufacturer;
    /**
     * @var int Supplier id
     */
    public $id_supplier;
    /**
     * @var int default Category id
     */
    public $id_category_default;
    /**
     * @var int default Shop id
     */
    public $id_shop_default;
    /**
     * @var string Manufacturer name
     */
    public $manufacturer_name;
    /**
     * @var string Supplier name
     */
    public $supplier_name;
    /**
     * @var string|string[] Name
     */
    public $name;
    /**
     * @var string|string[] Long description
     */
    public $description;
    /**
     * @var string|string[] Short description
     */
    public $description_short;
    /**
     * @var int Quantity available
     */
    public $quantity = 0;
    /**
     * @var int Minimal quantity for add to cart
     */
    public $minimal_quantity = 1;
    /**
     * @var string|string[] available_now
     */
    public $available_now;
    /**
     * @var string|string[] available_later
     */
    public $available_later;
    /**
     * @var float Price in euros
     */
    public $price = 0;
    /**
     * @var array
     */
    public $specific_price;
    /**
     * @var float Additional shipping cost
     */
    public $additional_shipping_cost = 0;
    /**
     * @var float Wholesale Price in euros
     */
    public $wholesale_price = 0;
    /**
     * @var bool on_sale
     */
    public $on_sale = false;
    /**
     * @var bool online_only
     */
    public $online_only = false;
    /**
     * @var string unity
     */
    public $unity;
    /**
     * @var float price for product's unity
     */
    public $unit_price;
    /**
     * @var float price for product's unity ratio
     */
    public $unit_price_ratio = 0;
    /**
     * @var float Ecotax
     */
    public $ecotax = 0;
    /**
     * @var string Reference
     */
    public $reference;
    /**
     * @var string Supplier Reference
     */
    public $supplier_reference;
    /**
     * @var string Location
     */
    public $location;
    /**
     * @var float Width in default width unit
     */
    public $width = 0;
    /**
     * @var float Height in default height unit
     */
    public $height = 0;
    /**
     * @var float Depth in default depth unit
     */
    public $depth = 0;
    /**
     * @var float Weight in default weight unit
     */
    public $weight = 0;
    /**
     * @var string Ean-13 barcode
     */
    public $ean13;
    /**
     * @var string Upc barcode
     */
    public $upc;
    /**
     * @var string|string[] Friendly URL
     */
    public $link_rewrite;
    /**
     * @var string|string[] Meta tag description
     */
    public $meta_description;
    /**
     * @var string|string[] Meta tag keywords
     */
    public $meta_keywords;
    /**
     * @var string|string[] Meta tag title
     */
    public $meta_title;
    /**
     * @var bool Product statuts
     */
    public $quantity_discount = 0;
    /**
     * @var int Product customization
     */
    public $customizable;
    /**
     * @var bool Product is new
     */
    public $new;
    /**
     * @var int Number of uploadable files (concerning customizable products)
     */
    public $uploadable_files;
    /**
     * @var int Number of text fields
     */
    public $text_fields;
    /**
     * @var bool Product statuts
     */
    public $active = true;
    /**
     * @var string
     */
    public $redirect_type = '';
    /**
     * @var int
     */
    public $id_product_redirected = 0;
    /**
     * @var bool Product available for order
     */
    public $available_for_order = true;
    /**
     * @var string Object available order date
     */
    public $available_date = '0000-00-00';
    /**
     * @var string Enumerated (enum) product condition (new, used, refurbished)
     */
    public $condition;
    /**
     * @var bool Show price of Product
     */
    public $show_price = true;
    /**
     * @var bool is the product indexed in the search index?
     */
    public $indexed = 0;
    /**
     * @var string ENUM('both', 'catalog', 'search', 'none') front office visibility
     */
    public $visibility;
    /**
     * @var string Object creation date
     */
    public $date_add;
    /**
     * @var string Object last modification date
     */
    public $date_upd;
    /***
     * @var array Tags
     */
    public $tags;
    /**
     * @var float Base price of the product
     * @deprecated 1.6.0.13
     */
    public $base_price;
    /**
     * @var int
     */
    public $id_tax_rules_group = 1;
    /**
     * @var int
     *
     * @deprecated 1.5.0 for retrocompatibility for themes
     */
    public $id_color_default = 0;
    /**
     * @var bool Tells if the product uses the advanced stock management
     */
    public $advanced_stock_management = 0;
    /**
     * @var int
     */
    public $out_of_stock;
    /**
     * @var bool
     */
    public $depends_on_stock;
    /**
     * @var bool
     */
    public $is_fully_loaded = false;
    /**
     * @var bool|null
     */
    public $cache_is_pack;
    /**
     * @var bool|null
     */
    public $cache_has_attachments;
    /**
     * @var bool
     */
    public $is_virtual;
    /**
     * @var int|null
     */
    public $id_pack_product_attribute;
    /**
     * @var int
     */
    public $cache_default_attribute;
    /**
     * @var string If product is populated, this property contain the rewrite link of the default category
     */
    public $category;
    /**
     * @var int tell the type of stock management to apply on the pack
     */
    public $pack_stock_type = Pack::STOCK_TYPE_DECREMENT_GLOBAL_SETTINGS;
    /**
     * @var bool
     */
    public $pack_dynamic = 0;
    /**
     * @var Product[]|null
     */
    public $pack_items;
    /**
     * @var int|null
     */
    public $pack_quantity;
    /**
     * @var array
     */
    public static $definition = ['table' => 'product', 'primary' => 'id_product', 'multilang' => true, 'multilang_shop' => true, 'fields' => [
        /* Classic fields */
        'id_supplier' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
        'id_manufacturer' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId'],
        'id_category_default' => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId'],
        'id_shop_default' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'dbDefault' => '1'],
        'id_tax_rules_group' => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId', 'dbNullable' => false],
        'on_sale' => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool', 'dbDefault' => '0'],
        'online_only' => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool', 'dbDefault' => '0'],
        'ean13' => ['type' => self::TYPE_STRING, 'validate' => 'isEan13', 'size' => 13],
        'upc' => ['type' => self::TYPE_STRING, 'validate' => 'isUpc', 'size' => 12],
        'ecotax' => ['type' => self::TYPE_PRICE, 'shop' => true, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
        'quantity' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbDefault' => '0', 'dbType' => 'int(10)'],
        'minimal_quantity' => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt', 'dbDefault' => '1'],
        'price' => ['type' => self::TYPE_PRICE, 'shop' => true, 'validate' => 'isPrice', 'required' => true, 'dbDefault' => '0.000000'],
        'wholesale_price' => ['type' => self::TYPE_PRICE, 'shop' => true, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
        'unity' => ['type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isString'],
        'unit_price_ratio' => ['type' => self::TYPE_FLOAT, 'shop' => true, 'dbDefault' => '0.000000'],
        'additional_shipping_cost' => ['type' => self::TYPE_PRICE, 'shop' => true, 'validate' => 'isPrice', 'dbDefault' => '0.000000'],
        'reference' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => self::SIZE_REFERENCE],
        'supplier_reference' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => self::SIZE_REFERENCE],
        'location' => ['type' => self::TYPE_STRING, 'validate' => 'isReference', 'size' => self::SIZE_REFERENCE],
        'width' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'dbDefault' => '0.000000'],
        'height' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'dbDefault' => '0.000000'],
        'depth' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'dbDefault' => '0.000000'],
        'weight' => ['type' => self::TYPE_FLOAT, 'validate' => 'isUnsignedFloat', 'dbDefault' => '0.000000'],
        'out_of_stock' => ['type' => self::TYPE_INT, 'validate' => 'isInt', 'dbDefault' => '2'],
        'quantity_discount' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0', 'dbNullable' => true],
        'customizable' => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt', 'dbType' => 'tinyint(2)', 'dbDefault' => '0'],
        'uploadable_files' => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt', 'dbType' => 'tinyint(4)', 'dbDefault' => '0'],
        'text_fields' => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt', 'dbType' => 'tinyint(4)', 'dbDefault' => '0'],
        'active' => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool', 'dbDefault' => '0'],
        'redirect_type' => ['type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isString', 'values' => ['', '404', '301', '302'], 'dbDefault' => ''],
        'id_product_redirected' => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedId', 'dbDefault' => '0'],
        'available_for_order' => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
        'available_date' => ['type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDateFormat', 'dbDefault' => '1970-01-01', 'dbType' => 'date'],
        'condition' => ['type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isGenericName', 'values' => ['new', 'used', 'refurbished'], 'default' => 'new'],
        'show_price' => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '1'],
        'indexed' => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'visibility' => ['type' => self::TYPE_STRING, 'shop' => true, 'validate' => 'isProductVisibility', 'values' => ['both', 'catalog', 'search', 'none'], 'default' => 'both'],
        'cache_is_pack' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'cache_has_attachments' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'is_virtual' => ['type' => self::TYPE_BOOL, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'cache_default_attribute' => ['type' => self::TYPE_INT, 'shop' => true],
        'date_add' => ['type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate', 'dbNullable' => false],
        'date_upd' => ['type' => self::TYPE_DATE, 'shop' => true, 'validate' => 'isDate', 'dbNullable' => false],
        'advanced_stock_management' => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isBool', 'dbType' => 'tinyint(1)', 'dbDefault' => '0'],
        'pack_stock_type' => ['type' => self::TYPE_INT, 'shop' => true, 'validate' => 'isUnsignedInt', 'dbDefault' => '3'],
        'pack_dynamic' => ['type' => self::TYPE_BOOL, 'shop' => true, 'validate' => 'isUnsignedInt', 'dbDefault' => '0'],
        /* Lang fields */
        'description' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => Object_Model::SIZE_LONG_TEXT],
        'description_short' => ['type' => self::TYPE_HTML, 'lang' => true, 'validate' => 'isCleanHtml', 'size' => Object_Model::SIZE_TEXT],
        'link_rewrite' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isLinkRewrite', 'required' => true, 'size' => 128, 'ws_modifier' => ['http_method' => Webservice_Request::HTTP_POST, 'modifier' => 'modifierWsLinkRewrite']],
        'meta_description' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        'meta_keywords' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        'meta_title' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 128],
        'name' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isCatalogName', 'required' => true, 'size' => 128],
        'available_now' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'isGenericName', 'size' => 255],
        'available_later' => ['type' => self::TYPE_STRING, 'lang' => true, 'validate' => 'IsGenericName', 'size' => 255],
    ], 'associations' => ['manufacturer' => ['type' => self::HAS_ONE], 'supplier' => ['type' => self::HAS_ONE], 'default_category' => ['type' => self::HAS_ONE, 'field' => 'id_category_default', 'object' => 'Category'], 'tax_rules_group' => ['type' => self::HAS_ONE], 'categories' => ['type' => self::BELONGS_TO_MANY, 'object' => 'Category', 'joinTable' => 'category_product'], 'stock_availables' => ['type' => self::HAS_MANY, 'field' => 'id_product', 'foreignField' => 'id_product', 'object' => 'StockAvailable'], 'accessories' => ['type' => self::BELONGS_TO_MANY, 'object' => 'Product', 'joinTable' => 'accessory', 'joinSourceField' => 'id_product_1', 'joinTargetField' => 'id_product_2']], 'keys' => ['product' => ['date_add' => ['type' => Object_Model::KEY, 'columns' => ['date_add']], 'id_category_default' => ['type' => Object_Model::KEY, 'columns' => ['id_category_default']], 'indexed' => ['type' => Object_Model::KEY, 'columns' => ['indexed']], 'product_manufacturer' => ['type' => Object_Model::KEY, 'columns' => ['id_manufacturer', 'id_product']], 'product_supplier' => ['type' => Object_Model::KEY, 'columns' => ['id_supplier']]], 'product_lang' => ['primary' => ['type' => Object_Model::PRIMARY_KEY, 'columns' => ['id_product', 'id_shop', 'id_lang']], 'id_lang' => ['type' => Object_Model::KEY, 'columns' => ['id_lang']], 'name' => ['type' => Object_Model::KEY, 'columns' => ['name']]], 'product_shop' => ['date_add' => ['type' => Object_Model::KEY, 'columns' => ['date_add', 'active', 'visibility']], 'id_category_default' => ['type' => Object_Model::KEY, 'columns' => ['id_category_default']], 'indexed' => ['type' => Object_Model::KEY, 'columns' => ['indexed', 'active', 'id_product']]]], 'images' => [Image_Entity::ENTITY_TYPE_PRODUCTS => ['path' => _PS_PROD_IMG_DIR_, 'imageTypes' => [['name' => 'backoffice_product_medium', 'width' => 150, 'height' => 150]]]]];
    /**
     * @var array Webservice parameters
     */
    protected $webservice_parameters = ['objectMethods' => ['add' => 'addWs', 'update' => 'updateWs'], 'objectNodeNames' => 'products', 'fields' => ['id_manufacturer' => ['xlink_resource' => 'manufacturers'], 'id_supplier' => ['xlink_resource' => 'suppliers'], 'id_category_default' => ['xlink_resource' => 'categories'], 'new' => [], 'cache_default_attribute' => [], 'id_default_image' => ['getter' => 'getCoverWs', 'setter' => 'setCoverWs', 'xlink_resource' => ['resourceName' => 'images', 'subResourceName' => 'products']], 'id_default_combination' => ['getter' => 'getWsDefaultCombination', 'setter' => 'setWsDefaultCombination', 'xlink_resource' => ['resourceName' => 'combinations']], 'id_tax_rules_group' => ['xlink_resource' => ['resourceName' => 'tax_rule_groups']], 'position_in_category' => ['getter' => 'getWsPositionInCategory', 'setter' => 'setWsPositionInCategory'], 'manufacturer_name' => ['getter' => 'getWsManufacturerName', 'setter' => false], 'quantity' => ['getter' => false, 'setter' => false], 'type' => ['getter' => 'getWsType', 'setter' => 'setWsType']], 'associations' => ['categories' => ['resource' => 'category', 'fields' => ['id' => ['required' => true]]], 'images' => ['resource' => 'image', 'fields' => ['id' => []]], 'combinations' => ['resource' => 'combination', 'fields' => ['id' => ['required' => true]]], 'product_option_values' => ['resource' => 'product_option_value', 'fields' => ['id' => ['required' => true]]], 'product_features' => ['resource' => 'product_feature', 'fields' => ['id' => ['required' => true], 'id_feature_value' => ['required' => true, 'xlink_resource' => 'product_feature_values']]], 'tags' => ['resource' => 'tag', 'fields' => ['id' => ['required' => true]]], 'stock_availables' => ['resource' => 'stock_available', 'fields' => ['id' => ['required' => true], 'id_product_attribute' => ['required' => true]], 'setter' => false], 'accessories' => ['resource' => 'product', 'api' => 'products', 'fields' => ['id' => ['required' => true, 'xlink_resource' => 'product']]], 'product_bundle' => ['resource' => 'product', 'api' => 'products', 'fields' => ['id' => ['required' => true], 'quantity' => [], 'combination_id' => ['xlink_resource' => 'combinations']]]]];
    /**
     * ProductCore constructor.
     *
     * @param int|null $idProduct
     * @param bool $full
     * @param int|null $idLang
     * @param int|null $idShop
     *
     * @throws PrestaShopException
     */
    public function __construct($id_product = null, $full = false, $id_lang = null, $id_shop = null, ?Context $context = null)
    {
        parent::__construct($id_product, $id_lang, $id_shop);
        if ($full && $this->id) {
            if (!$context) {
                $context = Context::get_context();
            }
            $this->is_fully_loaded = $full;
            $this->tax_name = 'deprecated';
            // The applicable tax may be BOTH the product one AND the state one (moreover this variable is some deadcode)
            $this->manufacturer_name = Manufacturer::get_name_by_id((int) $this->id_manufacturer);
            $this->supplier_name = Supplier::get_name_by_id((int) $this->id_supplier);
            $address = null;
            if (is_object($context->cart) && $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')} != null) {
                $address = $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
            }
            $this->tax_rate = $this->get_taxes_rate(new Address($address));
            $this->new = $this->is_new();
            // Keep base price
            $this->base_price = $this->price;
            $this->price = static::get_price_static((int) $this->id, false, null, _TB_PRICE_DATABASE_PRECISION_, null, false, true, 1, false, null, null, null, $this->specific_price);
            $this->unit_price = $this->unit_price_ratio != 0 ? round($this->price / $this->unit_price_ratio, _TB_PRICE_DATABASE_PRECISION_) : 0;
            $this->tags = Tag::get_product_tags((int) $this->id);
            $this->load_stock_data();
        }
        if ($this->id_category_default) {
            $this->category = Category::get_link_rewrite((int) $this->id_category_default, (int) $id_lang);
        }
    }
    /**
     * Returns tax rate.
     *
     *
     * @return float The total taxes rate applied to the product
     * @throws PrestaShopException
     */
    public function get_taxes_rate(?Address $address = null)
    {
        if (!$address || !$address->id_country) {
            $address = Address::initialize();
        }
        $tax_manager = Tax_Manager_Factory::get_manager($address, $this->id_tax_rules_group);
        $tax_calculator = $tax_manager->get_tax_calculator();
        return $tax_calculator->get_total_rate();
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function is_new()
    {
        $result = Db::read_only()->get_array('
			SELECT p.id_product
			FROM `' . _DB_PREFIX_ . 'product` p
			' . Shop::add_sql_association('product', 'p') . '
			WHERE p.id_product = ' . (int) $this->id . '
			AND DATEDIFF(
				product_shop.`date_add`,
				DATE_SUB(
					"' . date('Y-m-d') . ' 00:00:00",
					INTERVAL ' . (Validate::is_unsigned_int(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY
				)
			) > 0
		');
        return count($result) > 0;
    }
    /**
     * Returns product price
     *
     * @param int $idProduct Product id
     * @param bool $usetax With taxes or not (optional)
     * @param int|false|null $idProductAttribute Product attribute id (optional).
     *                                     If set to false, do not apply the combination price impact.
     *                                     NULL does apply the default combination price impact.
     * @param int $decimals Number of decimals (optional)
     * @param int|null $divisor Useful when paying many time without fees (optional)
     * @param bool $onlyReduc Returns only the reduction amount
     * @param bool $usereduc Set if the returned amount will include reduction
     * @param int $quantity Required for quantity discount application (default value: 1)
     * @param bool $forceAssociatedTax DEPRECATED - NOT USED Force to apply the associated tax.
     *                                 Only works when the parameter $usetax is true
     * @param int|null $idCustomer Customer ID (for customer group reduction)
     * @param int|null $idCart Cart ID. Required when the cookie is not accessible
     *                                      (e.g., inside a payment module, a cron task...)
     * @param int|null $idAddress Customer address ID. Required for price (tax included)
     *                                      calculation regarding the guest localization
     * @param array|null $specificPriceOutput If a specific price applies regarding the previous parameters,
     *                                      this variable is filled with the corresponding SpecificPrice object
     * @param bool $withEcotax Insert ecotax in price output.
     * @param bool $useGroupReduction
     * @param bool $useCustomerPrice
     *
     * @return float Product price
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_price_static($id_product, $usetax = true, $id_product_attribute = null, $decimals = _TB_PRICE_DATABASE_PRECISION_, $divisor = null, $only_reduc = false, $usereduc = true, $quantity = 1, $force_associated_tax = false, $id_customer = null, $id_cart = null, $id_address = null, &$specific_price_output = null, $with_ecotax = true, $use_group_reduction = true, ?Context $context = null, $use_customer_price = true)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $cur_cart = $context->cart;
        if ($divisor !== null) {
            Tools::display_parameter_as_deprecated('divisor');
        }
        if (!Validate::is_bool($usetax)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Invalid value for parameter [%s]'), 'usetax'));
        }
        if (!Validate::is_unsigned_id($id_product)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Invalid value for parameter [%s]'), 'idProduct'));
        }
        // Initializations
        $id_group = null;
        if ($id_customer) {
            $id_group = Customer::get_default_group_id((int) $id_customer);
        }
        if (!$id_group) {
            $id_group = (int) Group::get_current()->id;
        }
        // If there is cart in context or if the specified id_cart is different from the context cart id
        if (!is_object($cur_cart) || Validate::is_unsigned_int($id_cart) && $id_cart && $cur_cart->id != $id_cart) {
            /*
             * When a user (e.g., guest, customer, Google...) is on PrestaShop, he has already its cart as the global (see /init.php)
             * When a non-user calls directly this method (e.g., payment module...) is on PrestaShop, he does not have already it BUT knows the cart ID
             * When called from the back office, cart ID can be inexistant
             */
            if (!$id_cart && !isset($context->employee)) {
                throw new Presta_Shop_Exception('ID cart not provided in front office context');
            }
            $cur_cart = new Cart($id_cart);
            // Store cart in context to avoid multiple instantiations in BO
            if (!Validate::is_loaded_object($context->cart)) {
                $context->cart = $cur_cart;
            }
        }
        $cart_quantity = 0;
        if ((int) $id_cart) {
            $cache_id = 'Product::getPriceStatic_' . (int) $id_product . '-' . (int) $id_cart;
            if (!Cache::is_stored($cache_id) || $cart_quantity = Cache::retrieve($cache_id) != (int) $quantity) {
                $sql = 'SELECT SUM(`quantity`)
				FROM `' . _DB_PREFIX_ . 'cart_product`
				WHERE `id_product` = ' . (int) $id_product . '
				AND `id_cart` = ' . (int) $id_cart;
                $cart_quantity = (int) Db::read_only()->get_value($sql);
                Cache::store($cache_id, $cart_quantity);
            } else {
                $cart_quantity = Cache::retrieve($cache_id);
            }
        }
        $id_currency = Validate::is_loaded_object($context->currency) ? (int) $context->currency->id : (int) Configuration::get('PS_CURRENCY_DEFAULT');
        // retrieve address informations
        $id_country = (int) $context->country->id;
        $id_state = 0;
        $zipcode = 0;
        if (!$id_address && Validate::is_loaded_object($cur_cart)) {
            $id_address = $cur_cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
        }
        if ($id_address) {
            $address_infos = Address::get_country_and_state($id_address);
            if ($address_infos && $address_infos['id_country']) {
                $id_country = (int) $address_infos['id_country'];
                $id_state = (int) $address_infos['id_state'];
                $zipcode = $address_infos['postcode'];
            }
        }
        if (Tax::exclude_taxe_option()) {
            $usetax = false;
        }
        // @TODO: Use a hook for this
        if (Module::is_enabled('vatnumber') && $id_address) {
            require_once _PS_MODULE_DIR_ . '/vatnumber/VATNumberTaxManager.php';
            $address = new Address($id_address);
            $usetax = $usetax && !Vat_Number_Tax_Manager::is_available_for_this_address($address);
        }
        if (is_null($id_customer) && Validate::is_loaded_object($context->customer)) {
            $id_customer = $context->customer->id;
        }
        return static::price_calculation($context->shop->id, $id_product, $id_product_attribute, $id_country, $id_state, $zipcode, $id_currency, $id_group, $quantity, $usetax, $decimals, $only_reduc, $usereduc, $with_ecotax, $specific_price_output, $use_group_reduction, $id_customer, $use_customer_price, $id_cart, $cart_quantity);
    }
    /**
     * Price calculation / Get product price
     *
     * @param int|null $idShop Shop id
     * @param int $idProduct Product id
     * @param int|false|null $idProductAttribute Product attribute id
     * @param int $idCountry Country id
     * @param int $idState State id
     * @param string $zipcode
     * @param int $idCurrency Currency id
     * @param int $idGroup Group id
     * @param int $quantity Quantity Required for Specific prices : quantity discount application
     * @param bool $useTax with (1) or without (0) tax
     * @param int $decimals Number of decimals returned
     * @param bool $onlyReduc Returns only the reduction amount
     * @param bool $useReduc Set if the returned amount will include reduction
     * @param bool $withEcotax insert ecotax in price output.
     * @param array|null $specificPrice If a specific price applies regarding the previous parameters,
     *                                   this variable is filled with the corresponding SpecificPrice object
     * @param bool|null $useGroupReduction
     * @param int $idCustomer
     * @param bool $useCustomerPrice
     * @param int $idCart
     * @param int $realQuantity
     *
     * @return float Product price
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function price_calculation($id_shop, $id_product, $id_product_attribute, $id_country, $id_state, $zipcode, $id_currency, $id_group, $quantity, $use_tax, $decimals, $only_reduc, $use_reduc, $with_ecotax, &$specific_price, $use_group_reduction, $id_customer = 0, $use_customer_price = true, $id_cart = 0, $real_quantity = 0)
    {
        static $address = null;
        static $context = null;
        if ($address === null) {
            $address = new Address();
        }
        if ($context == null) {
            $context = Context::get_context()->clone_context();
        }
        if ($id_shop !== null && $context->shop->id != (int) $id_shop) {
            $context->shop = new Shop((int) $id_shop);
        }
        if (!$use_customer_price) {
            $id_customer = 0;
        }
        if ($id_product_attribute === null) {
            $id_product_attribute = static::get_default_attribute($id_product);
        }
        $cache_id = (int) $id_product . '-' . (int) $id_shop . '-' . (int) $id_currency . '-' . (int) $id_country . '-' . $id_state . '-' . $zipcode . '-' . (int) $id_group . '-' . (int) $quantity . '-' . (int) $id_product_attribute . '-' . (int) $with_ecotax . '-' . (int) $id_customer . '-' . (int) $use_group_reduction . '-' . (int) $id_cart . '-' . (int) $real_quantity . '-' . ($only_reduc ? '1' : '0') . '-' . ($use_reduc ? '1' : '0') . '-' . ($use_tax ? '1' : '0') . '-' . (int) $decimals;
        // reference parameter is filled before any returns
        $specific_price = Specific_Price::get_specific_price((int) $id_product, $id_shop, $id_currency, $id_country, $id_group, $quantity, $id_product_attribute, $id_customer, $id_cart, $real_quantity);
        if (isset(static::$_prices[$cache_id])) {
            /* Affect reference before returning cache */
            if (isset($specific_price['price']) && $specific_price['price'] > 0) {
                $specific_price['price'] = static::$_prices[$cache_id];
            }
            return static::$_prices[$cache_id];
        }
        // fetch price & attribute price
        $cache_id2 = $id_product . '-' . $id_shop;
        if (!isset(static::$_prices_level2[$cache_id2])) {
            $sql = new Db_Query();
            $sql->select('product_shop.`price`');
            $sql->select('product_shop.`ecotax`');
            $sql->from('product', 'p');
            $sql->inner_join('product_shop', 'product_shop', '(product_shop.id_product=p.id_product AND product_shop.id_shop = ' . (int) $id_shop . ')');
            $sql->where('p.`id_product` = ' . (int) $id_product);
            if (Combination::is_feature_active()) {
                $sql->select('IFNULL(product_attribute_shop.id_product_attribute,0) AS id_product_attribute')->select('product_attribute_shop.`price` AS attribute_price')->select('product_attribute_shop.default_on')->select('product_attribute_shop.`ecotax` AS attribute_ecotax');
                $sql->left_join('product_attribute_shop', 'product_attribute_shop', '(product_attribute_shop.id_product = p.id_product AND product_attribute_shop.id_shop = ' . (int) $id_shop . ')');
            } else {
                $sql->select('0 as id_product_attribute');
            }
            $res = Db::read_only()->get_array($sql);
            foreach ($res as $row) {
                $array_tmp = ['price' => $row['price'], 'ecotax' => $row['ecotax'], 'attribute_price' => $row['attribute_price'] ?? null, 'attribute_ecotax' => $row['attribute_ecotax'] ?? null];
                static::$_prices_level2[$cache_id2][(int) $row['id_product_attribute']] = $array_tmp;
                if (isset($row['default_on']) && $row['default_on'] == 1) {
                    static::$_prices_level2[$cache_id2][0] = $array_tmp;
                }
            }
        }
        if (!isset(static::$_prices_level2[$cache_id2][(int) $id_product_attribute])) {
            return 0.0;
        }
        $result = static::$_prices_level2[$cache_id2][(int) $id_product_attribute];
        if (!$specific_price || $specific_price['price'] < 0) {
            $price = (float) $result['price'];
        } else {
            $price = (float) $specific_price['price'];
        }
        // convert only if the specific price is in the default currency (id_currency = 0)
        if (!$specific_price || !($specific_price['price'] >= 0 && $specific_price['id_currency'])) {
            $price = Tools::convert_price($price, $id_currency);
            if (isset($specific_price['price']) && $specific_price['price'] >= 0) {
                $specific_price['price'] = $price;
            }
        }
        // Attribute price
        if (is_array($result) && (!$specific_price || !$specific_price['id_product_attribute'] || $specific_price['price'] < 0)) {
            $attribute_price = Tools::convert_price($result['attribute_price'] !== null ? (float) $result['attribute_price'] : 0, $id_currency);
            // If you want the default combination, please use NULL value instead
            if ($id_product_attribute !== false) {
                $price += $attribute_price;
            }
        }
        // Tax
        $address->id_country = $id_country;
        $address->id_state = $id_state;
        $address->postcode = $zipcode;
        $tax_manager = Tax_Manager_Factory::get_manager($address, static::get_id_tax_rules_group_by_id_product((int) $id_product, $context));
        $product_tax_calculator = $tax_manager->get_tax_calculator();
        // Add Tax
        if ($use_tax) {
            $price = $product_tax_calculator->add_taxes($price);
        }
        // Reduction
        $specific_price_reduction = 0;
        if (($only_reduc || $use_reduc) && $specific_price) {
            if ($specific_price['reduction_type'] == 'amount') {
                $reduction_amount = $specific_price['reduction'];
                if (!$specific_price['id_currency']) {
                    $reduction_amount = Tools::convert_price($reduction_amount, $id_currency);
                }
                $specific_price_reduction = $reduction_amount;
                // Adjust taxes if required
                if (!$use_tax && $specific_price['reduction_tax']) {
                    if (!$product_tax_calculator->get_total_rate()) {
                        $tax = new Tax(Configuration::get('TB_DEFAULT_SPECIFIC_PRICE_RULE_TAX'));
                        if (Validate::is_loaded_object($tax)) {
                            $specific_price_reduction = round($specific_price_reduction / (1 + $tax->rate / 100), _TB_PRICE_DATABASE_PRECISION_);
                        }
                    } else {
                        $specific_price_reduction = $product_tax_calculator->remove_taxes($specific_price_reduction);
                    }
                }
                if ($use_tax && !$specific_price['reduction_tax']) {
                    $specific_price_reduction = $product_tax_calculator->add_taxes($specific_price_reduction);
                }
            } else {
                $specific_price_reduction = round($price * $specific_price['reduction'], _TB_PRICE_DATABASE_PRECISION_);
            }
        }
        if ($use_reduc) {
            $price -= $specific_price_reduction;
        }
        // Group reduction
        if ($use_group_reduction) {
            $reduction_from_category = Group_Reduction::get_value_for_product($id_product, $id_group);
            if ($reduction_from_category !== false) {
                $group_reduction = Tools::round_price($price * $reduction_from_category);
            } else {
                // Apply group reduction if there is no group reduction for
                // this category.
                $reduc = Group::get_reduction_by_id_group($id_group);
                $group_reduction = $reduc ? Tools::round_price($price * $reduc / 100) : 0.0;
            }
            $price -= $group_reduction;
        }
        if ($only_reduc) {
            if ($decimals >= _TB_PRICE_DATABASE_PRECISION_) {
                return round($specific_price_reduction, _TB_PRICE_DATABASE_PRECISION_);
            }
            return Tools::ps_round($specific_price_reduction, $decimals);
        }
        // Eco Tax
        if (($result['ecotax'] || isset($result['attribute_ecotax'])) && $with_ecotax) {
            $ecotax = $result['ecotax'];
            if (isset($result['attribute_ecotax']) && $result['attribute_ecotax'] > 0) {
                $ecotax = $result['attribute_ecotax'];
            }
            if ($id_currency) {
                $ecotax = Tools::convert_price($ecotax, $id_currency);
            }
            if ($use_tax) {
                // reinit the tax manager for ecotax handling
                $tax_manager = Tax_Manager_Factory::get_manager($address, (int) Configuration::get('PS_ECOTAX_TAX_RULES_GROUP_ID'));
                $ecotax_tax_calculator = $tax_manager->get_tax_calculator();
                $price += $ecotax_tax_calculator->add_taxes($ecotax);
            } else {
                $price += $ecotax;
            }
        }
        if ($decimals >= _TB_PRICE_DATABASE_PRECISION_) {
            $price = round($price, _TB_PRICE_DATABASE_PRECISION_);
        } else {
            $price = Tools::ps_round($price, $decimals);
        }
        if ($price < 0) {
            $price = 0;
        }
        static::$_prices[$cache_id] = $price;
        return static::$_prices[$cache_id];
    }
    /**
     * Get the default attribute for a product
     *
     * @param int $idProduct
     * @param int $minimumQuantity
     * @param bool $reset
     *
     * @return int Attributes list
     *
     * @throws PrestaShopException
     */
    public static function get_default_attribute($id_product, $minimum_quantity = 0, $reset = false)
    {
        static $combinations = [];
        if (!Combination::is_feature_active()) {
            return 0;
        }
        if ($reset && isset($combinations[$id_product])) {
            unset($combinations[$id_product]);
        }
        if (!isset($combinations[$id_product])) {
            $combinations[$id_product] = [];
        }
        if (isset($combinations[$id_product][$minimum_quantity])) {
            return $combinations[$id_product][$minimum_quantity];
        }
        $sql = 'SELECT product_attribute_shop.id_product_attribute
				FROM ' . _DB_PREFIX_ . 'product_attribute pa
				' . Shop::add_sql_association('product_attribute', 'pa') . '
				WHERE pa.id_product = ' . (int) $id_product;
        $conn = Db::read_only();
        $result_no_filter = $conn->get_value($sql);
        if (!$result_no_filter) {
            $combinations[$id_product][$minimum_quantity] = 0;
            return 0;
        }
        $sql = 'SELECT product_attribute_shop.id_product_attribute
				FROM ' . _DB_PREFIX_ . 'product_attribute pa
				' . Shop::add_sql_association('product_attribute', 'pa') . '
				' . ($minimum_quantity > 0 ? static::sql_stock('pa', 'pa') : '') . ' WHERE product_attribute_shop.default_on = 1 ' . ($minimum_quantity > 0 ? ' AND IFNULL(stock.quantity, 0) >= ' . (int) $minimum_quantity : '') . ' AND pa.id_product = ' . (int) $id_product;
        $result = $conn->get_value($sql);
        if (!$result) {
            $sql = 'SELECT product_attribute_shop.id_product_attribute
					FROM ' . _DB_PREFIX_ . 'product_attribute pa
					' . Shop::add_sql_association('product_attribute', 'pa') . '
					' . ($minimum_quantity > 0 ? static::sql_stock('pa', 'pa') : '') . ' WHERE pa.id_product = ' . (int) $id_product . ($minimum_quantity > 0 ? ' AND IFNULL(stock.quantity, 0) >= ' . (int) $minimum_quantity : '');
            $result = $conn->get_value($sql);
        }
        if (!$result) {
            $sql = 'SELECT product_attribute_shop.id_product_attribute
					FROM ' . _DB_PREFIX_ . 'product_attribute pa
					' . Shop::add_sql_association('product_attribute', 'pa') . '
					WHERE product_attribute_shop.`default_on` = 1
					AND pa.id_product = ' . (int) $id_product;
            $result = $conn->get_value($sql);
        }
        if (!$result) {
            $result = $result_no_filter;
        }
        $combinations[$id_product][$minimum_quantity] = $result;
        return $result;
    }
    /**
     * Create JOIN query with 'stock_available' table
     *
     * @param string $productAlias Alias of product table
     * @param string|int|null $productAttribute If string : alias of PA table ; if int : value of PA ; if null : nothing about PA
     * @param bool $innerJoin LEFT JOIN or INNER JOIN
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function sql_stock($product_alias, $product_attribute = null, $inner_join = false, ?Shop $shop = null)
    {
        $id_shop = $shop !== null ? (int) $shop->id : null;
        $sql = ($inner_join ? ' INNER ' : ' LEFT ') . 'JOIN ' . _DB_PREFIX_ . 'stock_available stock
			ON (stock.id_product = ' . p_sql($product_alias) . '.id_product';
        if (!is_null($product_attribute)) {
            if (!Combination::is_feature_active()) {
                $sql .= ' AND stock.id_product_attribute = 0';
            } elseif (is_numeric($product_attribute)) {
                $sql .= ' AND stock.id_product_attribute = ' . $product_attribute;
            } elseif (is_string($product_attribute)) {
                $sql .= ' AND stock.id_product_attribute = IFNULL(`' . bq_sql($product_attribute) . '`.id_product_attribute, 0)';
            }
        }
        return $sql . (Stock_Available::add_sql_shop_restriction(null, $id_shop, 'stock') . ' )');
    }
    /**
     * @param int $idProduct
     *
     * @return int
     * @throws PrestaShopException
     */
    public static function get_id_tax_rules_group_by_id_product($id_product, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $key = 'product_id_tax_rules_group_' . (int) $id_product . '_' . (int) $context->shop->id;
        if (!Cache::is_stored($key)) {
            $result = Db::read_only()->get_value('
							SELECT `id_tax_rules_group`
							FROM `' . _DB_PREFIX_ . 'product_shop`
							WHERE `id_product` = ' . (int) $id_product . ' AND id_shop=' . (int) $context->shop->id);
            Cache::store($key, (int) $result);
            return (int) $result;
        }
        return Cache::retrieve($key);
    }
    /**
     * Fill the variables used for stock management
     *
     * @throws PrestaShopException
     */
    public function load_stock_data(): void
    {
        if (Validate::is_loaded_object($this)) {
            // By default, the product quantity correspond to the available quantity to sell in the current shop
            $this->quantity = Stock_Available::get_quantity_available_by_product($this->id, 0);
            $this->out_of_stock = Stock_Available::out_of_stock($this->id);
            $this->depends_on_stock = Stock_Available::depends_on_stock($this->id);
            if (Context::get_context()->shop->get_context() == Shop::CONTEXT_GROUP && Context::get_context()->shop->get_context_shop_group()->share_stock == 1) {
                $this->advanced_stock_management = $this->use_advanced_stock_management();
            }
        }
    }
    /**
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function use_advanced_stock_management()
    {
        return Db::read_only()->get_value('
					SELECT `advanced_stock_management`
					FROM ' . _DB_PREFIX_ . 'product_shop
					WHERE id_product=' . (int) $this->id . Shop::add_sql_restriction());
    }
    /**
     * @param int|null $idCustomer
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_tax_calculation_method($id_customer = null)
    {
        if (static::$_tax_calculation_method === null || $id_customer !== null) {
            static::init_prices_computation($id_customer);
        }
        return (int) static::$_tax_calculation_method;
    }
    /**
     * @param int|null $idCustomer
     *
     * @throws PrestaShopException
     */
    public static function init_prices_computation($id_customer = null): void
    {
        if ($id_customer) {
            $id_customer = (int) $id_customer;
            $customer = new Customer($id_customer);
            if (!Validate::is_loaded_object($customer)) {
                throw new Presta_Shop_Exception(sprintf(Tools::display_error('Customer [%s] not found'), $id_customer));
            }
            static::$_tax_calculation_method = Group::get_price_display_method((int) $customer->id_default_group);
            $cur_cart = Context::get_context()->cart;
            $id_address = 0;
            if (Validate::is_loaded_object($cur_cart)) {
                $id_address = (int) $cur_cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
            }
            // @TODO: Use a hook for this
            if (Module::is_enabled('vatnumber') && static::$_tax_calculation_method != PS_TAX_EXC) {
                require_once _PS_MODULE_DIR_ . '/vatnumber/VATNumberTaxManager.php';
                $address = new Address($id_address);
                if (Vat_Number_Tax_Manager::is_available_for_this_address($address)) {
                    static::$_tax_calculation_method = PS_TAX_EXC;
                }
            }
        } else {
            static::$_tax_calculation_method = Group::get_price_display_method(Group::get_current()->id);
        }
    }
    /**
     * For a given id_product and id_product_attribute, return available date
     *
     * @param int $idProduct
     * @param int $idProductAttribute Optional
     *
     * @return string/null
     *
     * @throws PrestaShopException
     */
    public static function get_available_date($id_product, $id_product_attribute = null)
    {
        $sql = 'SELECT';
        if ($id_product_attribute === null) {
            $sql .= ' p.`available_date`';
        } else {
            $sql .= ' IF(pa.`available_date` = "0000-00-00", p.`available_date`, pa.`available_date`) AS available_date';
        }
        $sql .= ' FROM `' . _DB_PREFIX_ . 'product` p';
        if ($id_product_attribute !== null) {
            $sql .= ' LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON (pa.`id_product` = p.`id_product`)';
        }
        $sql .= Shop::add_sql_association('product', 'p');
        if ($id_product_attribute !== null) {
            $sql .= Shop::add_sql_association('product_attribute', 'pa');
        }
        $sql .= ' WHERE p.`id_product` = ' . (int) $id_product;
        if ($id_product_attribute !== null) {
            $sql .= ' AND pa.`id_product` = ' . (int) $id_product . ' AND pa.`id_product_attribute` = ' . (int) $id_product_attribute;
        }
        $result = Db::read_only()->get_value($sql);
        if ($result == '0000-00-00') {
            return null;
        }
        return $result;
    }
    /**
     * @param int $idProduct
     * @param bool $isVirtual
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function update_is_virtual($id_product, $is_virtual = true): void
    {
        Db::get_instance()->update('product', ['is_virtual' => (bool) $is_virtual], 'id_product = ' . (int) $id_product);
    }
    /**
     * Get all available products
     *
     * @param int $idLang Language id
     * @param int $start Start number
     * @param int $limit Number of products to return
     * @param string $orderBy Field for ordering
     * @param string $orderWay Way for ordering (ASC or DESC)
     *
     * @param bool $idCategory
     * @param bool $onlyActive
     *
     * @return array Products details
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_products($id_lang, $start, $limit, $order_by, $order_way, $id_category = false, $only_active = false, ?Context $context = null)
    {
        if (!Validate::is_order_by($order_by) || !Validate::is_order_way($order_way)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Invalid ordering parameters: orderBy=[%s] orderWay=[%s]'), $order_by, $order_way));
        }
        if ($order_by == 'id_product' || $order_by == 'price' || $order_by == 'date_add' || $order_by == 'date_upd') {
            $order_by_prefix = 'p';
        } elseif ($order_by == 'name') {
            $order_by_prefix = 'pl';
        } elseif ($order_by == 'position') {
            $order_by_prefix = 'c';
        }
        if (strpos($order_by, '.') > 0) {
            $order_by = explode('.', $order_by);
            $order_by_prefix = $order_by[0];
            $order_by = $order_by[1];
        }
        $sql = 'SELECT p.*, product_shop.*, pl.* , m.`name` AS manufacturer_name, s.`name` AS supplier_name
				FROM `' . _DB_PREFIX_ . 'product` p
				' . Shop::add_sql_association('product', 'p') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.`id_product` = pl.`id_product` ' . Shop::add_sql_restriction_on_lang('pl') . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
				LEFT JOIN `' . _DB_PREFIX_ . 'supplier` s ON (s.`id_supplier` = p.`id_supplier`)' . ($id_category ? 'LEFT JOIN `' . _DB_PREFIX_ . 'category_product` c ON (c.`id_product` = p.`id_product`)' : '') . '
				WHERE pl.`id_lang` = ' . (int) $id_lang . ($id_category ? ' AND c.`id_category` = ' . (int) $id_category : '') . (static::is_front_office_context($context) ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '') . ($only_active ? ' AND product_shop.`active` = 1' : '') . '
				ORDER BY ' . (isset($order_by_prefix) ? p_sql($order_by_prefix) . '.' : '') . '`' . p_sql($order_by) . '` ' . p_sql($order_way) . ($limit > 0 ? ' LIMIT ' . (int) $start . ',' . (int) $limit : '');
        $rq = Db::read_only()->get_array($sql);
        if ($order_by == 'price') {
            Tools::orderby_price($rq, $order_way);
        }
        foreach ($rq as &$row) {
            $row = static::get_taxes_informations($row);
        }
        return $rq;
    }
    /**
     * @param array $row
     *
     * @return array
     * @throws PrestaShopException
     */
    public static function get_taxes_informations($row, ?Context $context = null)
    {
        static $address = null;
        if ($context === null) {
            $context = Context::get_context();
        }
        if ($address === null) {
            $address = new Address();
        }
        $address->id_country = (int) $context->country->id;
        $address->id_state = 0;
        $address->postcode = 0;
        $tax_manager = Tax_Manager_Factory::get_manager($address, static::get_id_tax_rules_group_by_id_product((int) $row['id_product'], $context));
        $row['rate'] = $tax_manager->get_tax_calculator()->get_total_rate();
        $row['tax_name'] = $tax_manager->get_tax_calculator()->get_taxes_name();
        return $row;
    }
    /**
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_simple_products($id_lang, ?Context $context = null)
    {
        $sql = 'SELECT p.`id_product`, pl.`name`
				FROM `' . _DB_PREFIX_ . 'product` p
				' . Shop::add_sql_association('product', 'p') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.`id_product` = pl.`id_product` ' . Shop::add_sql_restriction_on_lang('pl') . ')
				WHERE pl.`id_lang` = ' . (int) $id_lang . '
				' . (static::is_front_office_context($context) ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '') . '
				ORDER BY pl.`name`';
        return Db::read_only()->get_array($sql);
    }
    /**
     * @param int $idProductAttribute
     * @param int $idLang
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_combination_image_by_id($id_product_attribute, $id_lang)
    {
        if (!Combination::is_feature_active() || !$id_product_attribute) {
            return false;
        }
        return Db::read_only()->get_row('
			SELECT pai.`id_image`, pai.`id_product_attribute`, il.`legend`
			FROM `' . _DB_PREFIX_ . 'product_attribute_image` pai
			LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (il.`id_image` = pai.`id_image`)
			LEFT JOIN `' . _DB_PREFIX_ . 'image` i ON (i.`id_image` = pai.`id_image`)
			WHERE pai.`id_product_attribute` = ' . (int) $id_product_attribute . ' AND il.`id_lang` = ' . (int) $id_lang . ' ORDER BY i.`position`');
    }
    /**
     * Get new products
     *
     * @param int $idLang Language id
     * @param int $pageNumber Start from (optional)
     * @param int $nbProducts Number of products to return (optional)
     * @param bool $count
     * @param string|null $orderBy
     * @param string|null $orderWay
     *
     * @return array|false New products
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_new_products($id_lang, $page_number = 0, $nb_products = 10, $count = false, $order_by = null, $order_way = null, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $front = static::is_front_office_context($context);
        if ($page_number < 0) {
            $page_number = 0;
        }
        if ($nb_products < 1) {
            $nb_products = 10;
        }
        if (empty($order_by) || $order_by == 'position') {
            $order_by = 'date_add';
        }
        if (empty($order_way)) {
            $order_way = 'DESC';
        }
        if ($order_by == 'id_product' || $order_by == 'price' || $order_by == 'date_add' || $order_by == 'date_upd') {
            $order_by_prefix = 'product_shop';
        } elseif ($order_by == 'name') {
            $order_by_prefix = 'pl';
        }
        if (!Validate::is_order_by($order_by) || !Validate::is_order_way($order_way)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Invalid ordering parameters: orderBy=[%s] orderWay=[%s]'), $order_by, $order_way));
        }
        $sql_groups = '';
        if (Group::is_feature_active()) {
            $groups = Front_Controller::get_current_customer_groups();
            $sql_groups = ' AND EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp
				JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1') . ')
				WHERE cp.`id_product` = p.`id_product`)';
        }
        if (strpos($order_by, '.') > 0) {
            $order_by = explode('.', $order_by);
            $order_by_prefix = $order_by[0];
            $order_by = $order_by[1];
        }
        $conn = Db::read_only();
        if ($count) {
            $sql = 'SELECT COUNT(p.`id_product`) AS nb
					FROM `' . _DB_PREFIX_ . 'product` p
					' . Shop::add_sql_association('product', 'p') . '
					WHERE product_shop.`active` = 1
					AND product_shop.`date_add` > "' . date('Y-m-d', strtotime('-' . (Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY')) . '"
					' . ($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '') . '
					' . $sql_groups;
            return (int) $conn->get_value($sql);
        }
        $sql = new Db_Query();
        $sql->select('p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`, pl.`description_short`, pl.`link_rewrite`, pl.`meta_description`,
			pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`, image_shop.`id_image` id_image, il.`legend`, m.`name` AS manufacturer_name,
			product_shop.`date_add` > "' . date('Y-m-d', strtotime('-' . (Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY')) . '" as new');
        $sql->from('product', 'p');
        $sql->join(Shop::add_sql_association('product', 'p'));
        $sql->left_join('product_lang', 'pl', 'p.`id_product` = pl.`id_product`
			AND pl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl'));
        $sql->left_join('image_shop', 'image_shop', 'image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id);
        $sql->left_join('image_lang', 'il', 'image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $id_lang);
        $sql->left_join('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');
        $sql->where('product_shop.`active` = 1');
        if ($front) {
            $sql->where('product_shop.`visibility` IN ("both", "catalog")');
        }
        $sql->where('product_shop.`date_add` > "' . date('Y-m-d', strtotime('-' . (Configuration::get('PS_NB_DAYS_NEW_PRODUCT') ? (int) Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY')) . '"');
        if (Group::is_feature_active()) {
            $groups = Front_Controller::get_current_customer_groups();
            $sql->where('EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp
				JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1') . ')
				WHERE cp.`id_product` = p.`id_product`)');
        }
        $sql->order_by((isset($order_by_prefix) ? p_sql($order_by_prefix) . '.' : '') . '`' . p_sql($order_by) . '` ' . p_sql($order_way));
        $sql->limit($nb_products, $page_number * $nb_products);
        if (Combination::is_feature_active()) {
            $sql->select('product_attribute_shop.minimal_quantity AS product_attribute_minimal_quantity, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute');
            $sql->left_join('product_attribute_shop', 'product_attribute_shop', 'p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) $context->shop->id);
        }
        $sql->join(static::sql_stock('p', 0));
        $result = $conn->get_array($sql);
        if (!$result) {
            return false;
        }
        if ($order_by == 'price') {
            Tools::orderby_price($result, $order_way);
        }
        $products_ids = [];
        foreach ($result as $row) {
            $products_ids[] = $row['id_product'];
        }
        // Thus you can avoid one query per product, because there will be only one query for all the products of the cart
        static::cache_front_features($products_ids, $id_lang);
        return static::get_products_properties((int) $id_lang, $result);
    }
    /**
     * @param array $productIds
     * @param int $idLang
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function cache_front_features($product_ids, $id_lang): void
    {
        if (!Feature::is_feature_active()) {
            return;
        }
        $product_implode = [];
        foreach ($product_ids as $id_product) {
            if ((int) $id_product && !array_key_exists($id_product . '-' . $id_lang, static::$_cache_features)) {
                $product_implode[] = (int) $id_product;
            }
        }
        if (!count($product_implode)) {
            return;
        }
        $result = Db::read_only()->get_array('
		SELECT id_product, name, value, pf.id_feature
		FROM ' . _DB_PREFIX_ . 'feature_product pf
		LEFT JOIN ' . _DB_PREFIX_ . 'feature_lang fl ON (fl.id_feature = pf.id_feature AND fl.id_lang = ' . (int) $id_lang . ')
		LEFT JOIN ' . _DB_PREFIX_ . 'feature_value fv ON (fv.id_feature_value = pf.id_feature_value)
		LEFT JOIN ' . _DB_PREFIX_ . 'feature_value_lang fvl ON (fvl.id_feature_value = pf.id_feature_value AND fvl.id_lang = ' . (int) $id_lang . ')
		LEFT JOIN ' . _DB_PREFIX_ . 'feature f ON (f.id_feature = pf.id_feature)
		' . Shop::add_sql_association('feature', 'f') . '
		WHERE `id_product` IN (' . implode(',', $product_implode) . ')
		ORDER BY f.position ASC, fv.position ASC');
        foreach ($result as $row) {
            if (!array_key_exists($row['id_product'] . '-' . $id_lang, static::$_front_features_cache)) {
                static::$_front_features_cache[$row['id_product'] . '-' . $id_lang] = [];
            }
            if (!isset(static::$_front_features_cache[$row['id_product'] . '-' . $id_lang][$row['id_feature']])) {
                static::$_front_features_cache[$row['id_product'] . '-' . $id_lang][$row['id_feature']] = $row;
            }
        }
    }
    /**
     * @param int $idLang
     * @param array $queryResult
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public static function get_products_properties($id_lang, $query_result)
    {
        $results_array = [];
        if (is_array($query_result)) {
            foreach ($query_result as $row) {
                if ($row2 = static::get_product_properties($id_lang, $row)) {
                    $results_array[] = $row2;
                }
            }
        }
        return $results_array;
    }
    /**
     * @param int $idLang
     * @param array $row
     *
     * @return array|false
     * @throws PrestaShopException
     */
    public static function get_product_properties($id_lang, $row, ?Context $context = null)
    {
        if (!$row['id_product']) {
            return false;
        }
        if ($context == null) {
            $context = Context::get_context();
        }
        $id_product_attribute = $row['id_product_attribute'] = !empty($row['id_product_attribute']) ? (int) $row['id_product_attribute'] : null;
        // Product::getDefaultAttribute is only called if id_product_attribute is missing from the SQL query at the origin of it:
        // consider adding it in order to avoid unnecessary queries
        $row['allow_oosp'] = static::is_available_when_out_of_stock($row['out_of_stock']);
        if (Combination::is_feature_active() && $id_product_attribute === null && (isset($row['cache_default_attribute']) && ($ipa_default = $row['cache_default_attribute']) !== null || $ipa_default = static::get_default_attribute($row['id_product'], !$row['allow_oosp']))) {
            $id_product_attribute = $row['id_product_attribute'] = $ipa_default;
        }
        if (!Combination::is_feature_active() || !isset($row['id_product_attribute'])) {
            $id_product_attribute = $row['id_product_attribute'] = 0;
        }
        // Tax
        $usetax = Tax::exclude_taxe_option();
        $cache_key = $row['id_product'] . '-' . $id_product_attribute . '-' . $id_lang . '-' . (int) $usetax;
        if (isset($row['id_product_pack'])) {
            $cache_key .= '-pack' . $row['id_product_pack'];
        }
        if (isset(static::$produc_properties_cache[$cache_key])) {
            return array_merge($row, static::$produc_properties_cache[$cache_key]);
        }
        // Datas
        if (!isset($row['id_category_default']) && $row['id_category_default']) {
            $row['id_category_default'] = (int) Db::read_only()->get_value((new Db_Query())->select('product_shop.`id_category_default`')->from('product', 'p')->join(Shop::add_sql_association('product', 'p'))->where('p.`id_product` = ' . (int) $row['id_product']));
            if (!$row['id_category_default']) {
                $row['id_category_default'] = Context::get_context()->shop->id_category;
            }
        }
        $row['category'] = Category::get_link_rewrite((int) $row['id_category_default'], (int) $id_lang);
        $row['link'] = $context->link->get_product_link((int) $row['id_product'], $row['link_rewrite'], $row['category'], $row['ean13']);
        $row['attribute_price'] = 0;
        if ($id_product_attribute) {
            $row['attribute_price'] = Combination::get_price($id_product_attribute);
        }
        $row['price_tax_exc'] = static::get_price_static((int) $row['id_product'], false, $id_product_attribute);
        $row['price'] = static::get_price_static((int) $row['id_product'], true, $id_product_attribute);
        $row['price_without_reduction'] = static::get_price_static((int) $row['id_product'], static::$_tax_calculation_method != PS_TAX_EXC, $id_product_attribute, _TB_PRICE_DATABASE_PRECISION_, null, false, false);
        $row['reduction'] = static::get_price_static((int) $row['id_product'], static::$_tax_calculation_method != PS_TAX_EXC, $id_product_attribute, _TB_PRICE_DATABASE_PRECISION_, null, true, true, 1, true, null, null, null, $specific_prices);
        $row['specific_prices'] = $specific_prices;
        $row['quantity'] = (int) static::get_quantity((int) $row['id_product'], 0, $row['cache_is_pack'] ?? null);
        $row['quantity_all_versions'] = $row['quantity'];
        if ($id_product_attribute) {
            $row['quantity'] = (int) static::get_quantity((int) $row['id_product'], $id_product_attribute, $row['cache_is_pack'] ?? null);
            // $quantity_all_versions is a sum of quantities of all combinations. It is possible that
            // the value is zero or negative even when some combination is in stock.
            // Example: product has 3 combinations with quantities (-12, 10, 1), sum is -1
            if ($row['quantity_all_versions'] <= 0) {
                $total_positive_quantity = 0;
                $combination_quantities = Stock_Available::get_combination_quantities((int) $row['id_product']);
                foreach ($combination_quantities as $quantity) {
                    if ($quantity > 0) {
                        $total_positive_quantity += $quantity;
                    }
                }
                $row['quantity_all_versions'] = $total_positive_quantity;
            }
        }
        $row['features'] = static::get_front_features_static((int) $id_lang, $row['id_product']);
        $row['attachments'] = [];
        if (!isset($row['cache_has_attachments']) || $row['cache_has_attachments']) {
            $row['attachments'] = static::get_attachments_static((int) $id_lang, $row['id_product']);
        }
        $row['virtual'] = !isset($row['is_virtual']) || $row['is_virtual'] ? 1 : 0;
        // Pack management
        $row['pack'] = !isset($row['cache_is_pack']) ? Pack::is_pack($row['id_product']) : (int) $row['cache_is_pack'];
        $row['packItems'] = $row['pack'] ? Pack::get_item_table($row['id_product'], $id_lang) : [];
        $row['nopackprice'] = $row['pack'] ? Pack::no_pack_price($row['id_product']) : 0;
        if ($row['pack'] && !Pack::is_in_stock($row['id_product'])) {
            $row['quantity'] = 0;
        }
        $row['customization_required'] = false;
        if (isset($row['customizable']) && $row['customizable'] && Customization::is_feature_active()) {
            if (count(static::get_required_customizable_fields_static((int) $row['id_product']))) {
                $row['customization_required'] = true;
            }
        }
        $row = static::get_taxes_informations($row, $context);
        static::$produc_properties_cache[$cache_key] = $row;
        return static::$produc_properties_cache[$cache_key];
    }
    /**
     * @param int $outOfStock
     *
     * @return bool|int
     *
     * @throws PrestaShopException
     */
    public static function is_available_when_out_of_stock($out_of_stock)
    {
        // @TODO 1.5.0 Update of STOCK_MANAGEMENT & ORDER_OUT_OF_STOCK
        static $ps_stock_management = null;
        if ($ps_stock_management === null) {
            $ps_stock_management = Configuration::get('PS_STOCK_MANAGEMENT');
        }
        if (!$ps_stock_management) {
            return true;
        }
        static $ps_order_out_of_stock = null;
        if ($ps_order_out_of_stock === null) {
            $ps_order_out_of_stock = Configuration::get('PS_ORDER_OUT_OF_STOCK');
        }
        return (int) $out_of_stock == 2 ? (int) $ps_order_out_of_stock : (int) $out_of_stock;
    }
    /**
     * @deprecated 1.0.0 Use Combination::getPrice
     *
     * @param int $idProductAttribute
     *
     * @return float
     * @throws PrestaShopException
     */
    public static function get_product_attribute_price($id_product_attribute)
    {
        return Combination::get_price($id_product_attribute);
    }
    /**
     * Get available product quantities
     *
     * @param int $idProduct Product id
     * @param int $idProductAttribute Product attribute id (optional)
     *
     * @param bool|null $cacheIsPack
     *
     * @return int Available quantities
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_quantity($id_product, $id_product_attribute = null, $cache_is_pack = null)
    {
        if (!((int) $cache_is_pack || $cache_is_pack === null && Pack::is_pack((int) $id_product))) {
            return Stock_Available::get_quantity_available_by_product($id_product, $id_product_attribute);
        }
        if (!Pack::is_in_stock((int) $id_product)) {
            return 0;
        }
        return Stock_Available::get_quantity_available_by_product($id_product, $id_product_attribute);
    }
    /**
     * @param array $row
     * @param int $idLang
     *
     * @return int
     */
    public static function define_product_image($row, $id_lang)
    {
        return (int) ($row['id_image'] ?? 0);
    }
    /**
     * @param int $idLang
     * @param int $idProduct
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_front_features_static($id_lang, $id_product)
    {
        if (!Feature::is_feature_active()) {
            return [];
        }
        if (!array_key_exists($id_product . '-' . $id_lang, static::$_front_features_cache)) {
            $feature_values = Db::read_only()->get_array('
				SELECT COALESCE(NULLIF(fl.public_name, \'\'), fl.name) AS name, fvl.value, IFNULL(pfl.displayable, fvl.displayable) AS displayable, fl.multiple_schema, fl.multiple_separator, pf.id_feature, f.allows_multiple_values
				FROM ' . _DB_PREFIX_ . 'feature_product pf
				LEFT JOIN ' . _DB_PREFIX_ . 'feature_product_lang pfl ON (pfl.id_feature_value = pf.id_feature_value AND pfl.id_lang = ' . (int) $id_lang . ' AND pfl.id_product = ' . (int) $id_product . ')
				LEFT JOIN ' . _DB_PREFIX_ . 'feature_lang fl ON (fl.id_feature = pf.id_feature AND fl.id_lang = ' . (int) $id_lang . ')
				LEFT JOIN ' . _DB_PREFIX_ . 'feature_value fv ON (fv.id_feature_value = pf.id_feature_value)
				LEFT JOIN ' . _DB_PREFIX_ . 'feature_value_lang fvl ON (fvl.id_feature_value = pf.id_feature_value AND fvl.id_lang = ' . (int) $id_lang . ')
				LEFT JOIN ' . _DB_PREFIX_ . 'feature f ON (f.id_feature = pf.id_feature AND fl.id_lang = ' . (int) $id_lang . ')
				' . Shop::add_sql_association('feature', 'f') . '
				WHERE pf.id_product = ' . (int) $id_product . '
				ORDER BY f.position ASC,
				    (CASE WHEN f.sorting=' . Feature::SORT_VALUE_ASC . ' THEN fvl.value END) ASC,
				    (CASE WHEN f.sorting=' . Feature::SORT_VALUE_DESC . ' THEN fvl.value END) DESC,
				    (CASE WHEN f.sorting=' . Feature::SORT_CUSTOM . ' THEN fv.position END) ASC
				    ');
            $feature_values_helper = [];
            // Get concatenated values, min_value and max_value per id_feature
            foreach ($feature_values as $feature_value) {
                $id_feature = (int) $feature_value['id_feature'];
                $display_value = $feature_value['displayable'] ?: $feature_value['value'];
                if (!isset($feature_values_helper[$id_feature])) {
                    $feature_values_helper[$id_feature]['id_feature'] = $id_feature;
                    // Helpful in cases the keys got lost due to sorting
                    $feature_values_helper[$id_feature]['name'] = $feature_value['name'];
                    $feature_values_helper[$id_feature]['values'][] = $display_value;
                    $feature_values_helper[$id_feature]['values_string'] = $display_value;
                    $feature_values_helper[$id_feature]['min_value'] = $feature_value;
                    $feature_values_helper[$id_feature]['max_value'] = $feature_value;
                } else {
                    $feature_values_helper[$id_feature]['multiple_schema'] = $feature_value['multiple_schema'];
                    // Multiple Schema should only apply, if really multiple values were selected
                    $feature_values_helper[$id_feature]['values'][] = $display_value;
                    // Concatenate values
                    $display_separator = $feature_value['multiple_separator'] ?: ', ';
                    $feature_values_helper[$id_feature]['values_string'] .= $display_separator . $display_value;
                    // Update min and max value
                    if ($feature_values_helper[$id_feature]['min_value']['value'] > $feature_value['value']) {
                        $feature_values_helper[$id_feature]['min_value'] = $feature_value;
                    }
                    if ($feature_values_helper[$id_feature]['max_value']['value'] < $feature_value['value']) {
                        $feature_values_helper[$id_feature]['max_value'] = $feature_value;
                    }
                }
            }
            // Now create the 'value' based on the multiple_schema
            foreach ($feature_values_helper as &$feature_value_helper) {
                if (isset($feature_value_helper['multiple_schema']) && $multiple_schema = $feature_value_helper['multiple_schema']) {
                    $value = str_replace('{values}', $feature_value_helper['values_string'], $multiple_schema);
                    $value = str_replace('{count_values}', count($feature_value_helper['values']), $value);
                    $value = str_replace('{min_value}', $feature_value_helper['min_value']['value'], $value);
                    $value = str_replace('{max_value}', $feature_value_helper['max_value']['value'], $value);
                    $value = str_replace('{first_value}', $feature_value_helper['values'][0], $value);
                    $value = str_replace('{last_value}', $feature_value_helper['values'][array_key_last($feature_value_helper['values'])], $value);
                    $display_value_min = $feature_value_helper['min_value']['displayable'] ?: $feature_value_helper['min_value']['value'];
                    $display_value_max = $feature_value_helper['max_value']['displayable'] ?: $feature_value_helper['max_value']['value'];
                    $value = str_replace('{min_displayable}', $display_value_min, $value);
                    $value = str_replace('{max_displayable}', $display_value_max, $value);
                    $feature_value_helper['value'] = $value;
                } else {
                    $feature_value_helper['value'] = $feature_value_helper['values_string'];
                }
            }
            static::$_front_features_cache[$id_product . '-' . $id_lang] = $feature_values_helper;
        }
        return static::$_front_features_cache[$id_product . '-' . $id_lang];
    }
    /**
     * @param int $idLang
     * @param int $idProduct
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_attachments_static($id_lang, $id_product)
    {
        return Db::read_only()->get_array('
		SELECT *
		FROM ' . _DB_PREFIX_ . 'product_attachment pa
		LEFT JOIN ' . _DB_PREFIX_ . 'attachment a ON a.id_attachment = pa.id_attachment
		LEFT JOIN ' . _DB_PREFIX_ . 'attachment_lang al ON (a.id_attachment = al.id_attachment AND al.id_lang = ' . (int) $id_lang . ')
		WHERE pa.id_product = ' . (int) $id_product);
    }
    /**
     * @param int $id
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_required_customizable_fields_static($id)
    {
        if (!$id || !Customization::is_feature_active()) {
            return [];
        }
        return Db::read_only()->get_array('
			SELECT `id_customization_field`, `type`
			FROM `' . _DB_PREFIX_ . 'customization_field`
			WHERE `id_product` = ' . (int) $id . '
			AND `required` = 1');
    }
    /**
     * Get a random special
     *
     * @param int $idLang Language id
     * @param bool $beginning
     * @param bool $ending
     *
     * @return array|false Special
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_random_special($id_lang, $beginning = false, $ending = false, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $current_date = date('Y-m-d H:i:00');
        $product_reductions = static::_get_product_id_by_date(!$beginning ? $current_date : $beginning, !$ending ? $current_date : $ending, $context, true);
        if ($product_reductions) {
            $ids_products = '';
            foreach ($product_reductions as $product_reduction) {
                $ids_products .= '(' . (int) $product_reduction['id_product'] . ',' . ($product_reduction['id_product_attribute'] ? (int) $product_reduction['id_product_attribute'] : '0') . '),';
            }
            $ids_products = rtrim($ids_products, ',');
            $conn = Db::get_instance();
            $conn->execute('CREATE TEMPORARY TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'product_reductions` (id_product INT UNSIGNED NOT NULL DEFAULT 0, id_product_attribute INT UNSIGNED NOT NULL DEFAULT 0) ENGINE=MEMORY', false);
            if ($ids_products) {
                $conn->execute('INSERT INTO `' . _DB_PREFIX_ . 'product_reductions` VALUES ' . $ids_products, false);
            }
            $groups = Front_Controller::get_current_customer_groups();
            $sql_groups = ' AND EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp
				JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1') . ')
				WHERE cp.`id_product` = p.`id_product`)';
            // Please keep 2 distinct queries because RAND() is an awful way to achieve this result
            $sql = 'SELECT product_shop.id_product, IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute
					FROM
					`' . _DB_PREFIX_ . 'product_reductions` pr,
					`' . _DB_PREFIX_ . 'product` p
					' . Shop::add_sql_association('product', 'p') . '
					LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop
				   		ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) $context->shop->id . ')
					WHERE p.id_product=pr.id_product AND (pr.id_product_attribute = 0 OR product_attribute_shop.id_product_attribute = pr.id_product_attribute) AND product_shop.`active` = 1
						' . $sql_groups . '
					' . (static::is_front_office_context($context) ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '') . '
					ORDER BY RAND()';
            $result = $conn->get_row($sql);
            $conn->execute('TRUNCATE TABLE `' . _DB_PREFIX_ . 'product_reductions`', false);
            if (!$id_product = $result['id_product']) {
                return false;
            }
            // no group by needed : there's only one attribute with cover=1 for a given id_product + shop
            $sql = 'SELECT p.*, product_shop.*, stock.`out_of_stock` out_of_stock, pl.`description`, pl.`description_short`,
						pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`,
						p.`ean13`, p.`upc`, image_shop.`id_image` id_image, il.`legend`,
						DATEDIFF(product_shop.`date_add`, DATE_SUB("' . date('Y-m-d') . ' 00:00:00",
						INTERVAL ' . (Validate::is_unsigned_int(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . '
							DAY)) > 0 AS new
					FROM `' . _DB_PREFIX_ . 'product` p
					LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (
						p.`id_product` = pl.`id_product`
						AND pl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl') . '
					)
					' . Shop::add_sql_association('product', 'p') . '
					LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
						ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id . ')
					LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $id_lang . ')
					' . static::sql_stock('p', 0) . '
					WHERE p.id_product = ' . (int) $id_product;
            $row = $conn->get_row($sql);
            if (!$row) {
                return false;
            }
            $row['id_product_attribute'] = (int) $result['id_product_attribute'];
            return static::get_product_properties($id_lang, $row);
        }
        return false;
    }
    /**
     * @param string $beginning
     * @param string $ending
     * @param bool $withCombination
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function _get_product_id_by_date($beginning, $ending, ?Context $context = null, $with_combination = false)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $id_address = $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
        $ids = Address::get_country_and_state($id_address);
        $id_country = $ids && $ids['id_country'] ? (int) $ids['id_country'] : (int) Configuration::get('PS_COUNTRY_DEFAULT');
        return Specific_Price::get_product_id_by_date($context->shop->id, $context->currency->id, $id_country, $context->customer->id_default_group, $beginning, $ending, 0, $with_combination);
    }
    /**
     * Get prices drop
     *
     * @param int $idLang Language id
     * @param int $pageNumber Start from (optional)
     * @param int $nbProducts Number of products to return (optional)
     * @param bool $count Only in order to get total number (optional)
     * @param string|null $orderBy
     * @param string|null $orderWay
     * @param bool $beginning
     * @param bool $ending
     *
     * @return array|false Prices drop
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_prices_drop($id_lang, $page_number = 0, $nb_products = 10, $count = false, $order_by = null, $order_way = null, $beginning = false, $ending = false, ?Context $context = null)
    {
        if (!Validate::is_bool($count)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Invalid value for parameter [%s]'), 'count'));
        }
        if (!$context) {
            $context = Context::get_context();
        }
        if ($page_number < 0) {
            $page_number = 0;
        }
        if ($nb_products < 1) {
            $nb_products = 10;
        }
        if (empty($order_by) || $order_by == 'position') {
            $order_by = 'price';
        }
        if (empty($order_way)) {
            $order_way = 'DESC';
        }
        if ($order_by == 'id_product' || $order_by == 'price' || $order_by == 'date_add' || $order_by == 'date_upd') {
            $order_by_prefix = 'product_shop';
        } elseif ($order_by == 'name') {
            $order_by_prefix = 'pl';
        }
        if (!Validate::is_order_by($order_by) || !Validate::is_order_way($order_way)) {
            throw new Presta_Shop_Exception(sprintf(Tools::display_error('Invalid ordering parameters: orderBy=[%s] orderWay=[%s]'), $order_by, $order_way));
        }
        $current_date = date('Y-m-d H:i:00');
        $ids_product = static::_get_product_id_by_date(!$beginning ? $current_date : $beginning, !$ending ? $current_date : $ending, $context);
        $tab_id_product = [];
        foreach ($ids_product as $product) {
            if (is_array($product)) {
                $tab_id_product[] = (int) $product['id_product'];
            } else {
                $tab_id_product[] = (int) $product;
            }
        }
        $front = static::is_front_office_context($context);
        $sql_groups = '';
        if (Group::is_feature_active()) {
            $groups = Front_Controller::get_current_customer_groups();
            $sql_groups = ' AND EXISTS(SELECT 1 FROM `' . _DB_PREFIX_ . 'category_product` cp
				JOIN `' . _DB_PREFIX_ . 'category_group` cg ON (cp.id_category = cg.id_category AND cg.`id_group` ' . (count($groups) ? 'IN (' . implode(',', $groups) . ')' : '= 1') . ')
				WHERE cp.`id_product` = p.`id_product`)';
        }
        $conn = Db::read_only();
        if ($count) {
            return $conn->get_value('
			SELECT COUNT(DISTINCT p.`id_product`)
			FROM `' . _DB_PREFIX_ . 'product` p
			' . Shop::add_sql_association('product', 'p') . '
			WHERE product_shop.`active` = 1
			AND product_shop.`show_price` = 1
			' . ($front ? ' AND product_shop.`visibility` IN ("both", "catalog")' : '') . '
			' . (!$beginning && !$ending ? 'AND p.`id_product` IN(' . (is_array($tab_id_product) && count($tab_id_product) ? implode(', ', $tab_id_product) : 0) . ')' : '') . '
			' . $sql_groups);
        }
        if (strpos($order_by, '.') > 0) {
            $order_by = explode('.', $order_by);
            $order_by = p_sql($order_by[0]) . '.`' . p_sql($order_by[1]) . '`';
        }
        $sql = '
		SELECT
			p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`, pl.`description_short`, pl.`available_now`, pl.`available_later`,
			IFNULL(product_attribute_shop.id_product_attribute, 0) id_product_attribute,
			pl.`link_rewrite`, pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`,
			pl.`name`, image_shop.`id_image` id_image, il.`legend`, m.`name` AS manufacturer_name,
			DATEDIFF(
				p.`date_add`,
				DATE_SUB(
					"' . date('Y-m-d') . ' 00:00:00",
					INTERVAL ' . (Validate::is_unsigned_int(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY
				)
			) > 0 AS new
		FROM `' . _DB_PREFIX_ . 'product` p
		' . Shop::add_sql_association('product', 'p') . '
		LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop
			ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) $context->shop->id . ')
		' . static::sql_stock('p', 0, false, $context->shop) . '
		LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (
			p.`id_product` = pl.`id_product`
			AND pl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl') . '
		)
		LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
			ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $context->shop->id . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $id_lang . ')
		LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (m.`id_manufacturer` = p.`id_manufacturer`)
		WHERE product_shop.`active` = 1
		AND product_shop.`show_price` = 1
		' . ($front ? ' AND p.`visibility` IN ("both", "catalog")' : '') . '
		' . (!$beginning && !$ending ? ' AND p.`id_product` IN (' . (is_array($tab_id_product) && count($tab_id_product) ? implode(', ', $tab_id_product) : 0) . ')' : '') . '
		' . $sql_groups . '
		ORDER BY ' . (isset($order_by_prefix) ? p_sql($order_by_prefix) . '.' : '') . p_sql($order_by) . ' ' . p_sql($order_way) . '
		LIMIT ' . (int) ($page_number * $nb_products) . ', ' . (int) $nb_products;
        $result = $conn->get_array($sql);
        if (!$result) {
            return false;
        }
        if ($order_by == 'price') {
            Tools::orderby_price($result, $order_way);
        }
        return static::get_products_properties($id_lang, $result);
    }
    /**
     * @param string $idProduct
     * @param int|null $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_product_categories_full($id_product = '', $id_lang = null)
    {
        if (!$id_lang) {
            $id_lang = Context::get_context()->language->id;
        }
        $ret = [];
        $row = Db::read_only()->get_array('
			SELECT cp.`id_category`, cl.`name`, cl.`link_rewrite` FROM `' . _DB_PREFIX_ . 'category_product` cp
			LEFT JOIN `' . _DB_PREFIX_ . 'category` c ON (c.id_category = cp.id_category)
			LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (cp.`id_category` = cl.`id_category`' . Shop::add_sql_restriction_on_lang('cl') . ')
			' . Shop::add_sql_association('category', 'c') . '
			WHERE cp.`id_product` = ' . (int) $id_product . '
				AND cl.`id_lang` = ' . (int) $id_lang);
        foreach ($row as $val) {
            $ret[$val['id_category']] = $val;
        }
        return $ret;
    }
    /**
     * @param float $price
     * @param bool $currency
     *
     * @return string
     * @throws PrestaShopException
     */
    public static function convert_and_format_price($price, $currency = false, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        if (!$currency) {
            $currency = $context->currency;
        }
        return Tools::display_price(Tools::convert_price($price, $currency), $currency);
    }
    /**
     * @param int $idProduct
     * @param int $quantity
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function is_discounted($id_product, $quantity = 1, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $id_group = $context->customer->id_default_group;
        $cart_quantity = !$context->cart ? 0 : Db::read_only()->get_value('
			SELECT SUM(`quantity`)
			FROM `' . _DB_PREFIX_ . 'cart_product`
			WHERE `id_product` = ' . (int) $id_product . ' AND `id_cart` = ' . (int) $context->cart->id);
        $quantity = $cart_quantity ?: $quantity;
        $id_currency = (int) $context->currency->id;
        $ids = Address::get_country_and_state((int) $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')});
        $id_country = isset($ids['id_country']) && $ids['id_country'] ? (int) $ids['id_country'] : (int) Configuration::get('PS_COUNTRY_DEFAULT');
        return (bool) Specific_Price::get_specific_price((int) $id_product, $context->shop->id, $id_currency, $id_country, $id_group, $quantity, null, 0, 0, $quantity);
    }
    /**
     * Display price with right format and currency
     *
     * @param array $params Params
     * @param Smarty_Internal_Template $smarty Smarty object
     *
     * @return string Price with right format and currency
     *
     * @throws PrestaShopException
     */
    public static function convert_price($params, $smarty)
    {
        return Tools::display_price($params['price'], Context::get_context()->currency);
    }
    /**
     * Convert price with currency
     *
     * @param array $params
     * @param Smarty_Internal_Template $smarty
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function convert_price_with_currency($params, $smarty)
    {
        return Tools::display_price($params['price'], $params['currency'], false);
    }
    /**
     * @param array $params
     * @param Smarty_Internal_Template $smarty
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function display_wt_price($params, $smarty)
    {
        return Tools::display_price($params['p'], Context::get_context()->currency);
    }
    /**
     * Display WT price with currency
     *
     * @param array $params
     * @param Smarty_Internal_Template $smarty
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function display_wt_price_with_currency($params, $smarty)
    {
        return Tools::display_price($params['price'], $params['currency'], false);
    }
    /**
     * It's not possible to use this method with new stockManager and stockAvailable features
     * Now this method do nothing
     *
     * @see StockManager if you want to manage real stock
     * @see StockAvailable if you want to manage available quantities for sale on your shop(s)
     *
     * @deprecated 1.0.0
     * @return false
     */
    public static function update_quantity()
    {
        Tools::display_as_deprecated();
        return false;
    }
    /**
     * It's not possible to use this method with new stockManager and stockAvailable features
     * Now this method do nothing
     *
     * @deprecated 1.0.0
     * @see StockManager if you want to manage real stock
     * @see StockAvailable if you want to manage available quantities for sale on your shop(s)
     * @return false
     */
    public static function reinject_quantities()
    {
        Tools::display_as_deprecated();
        return false;
    }
    /**
     * @param bool $haveStock
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_attributes_color_list(array $products, $have_stock = true)
    {
        if (!count($products)) {
            return [];
        }
        $id_lang = Context::get_context()->language->id;
        $check_stock = !Configuration::get('PS_DISP_UNAVAILABLE_ATTR');
        if (!$res = Db::read_only()->get_array('
			SELECT pa.`id_product`, a.`color`, pac.`id_product_attribute`, ' . ($check_stock ? 'SUM(IF(stock.`quantity` > 0, 1, 0))' : '0') . ' qty, a.`id_attribute`, al.`name`, IF(color = "", a.id_attribute, color) group_by
			FROM `' . _DB_PREFIX_ . 'product_attribute` pa
			' . Shop::add_sql_association('product_attribute', 'pa') . ($check_stock ? static::sql_stock('pa', 'pa') : '') . '
			JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON (pac.`id_product_attribute` = product_attribute_shop.`id_product_attribute`)
			JOIN `' . _DB_PREFIX_ . 'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
			JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $id_lang . ')
			JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON (a.id_attribute_group = ag.`id_attribute_group`)
			WHERE pa.`id_product` IN (' . implode(',', array_map(intval(...), $products)) . ') AND ag.`is_color_group` = 1
			GROUP BY pa.`id_product`, a.`id_attribute`, `group_by`
			' . ($check_stock ? 'HAVING qty > 0' : '') . '
			ORDER BY a.`position` ASC;')) {
            return false;
        }
        $colors = [];
        foreach ($res as $row) {
            $image_extension = Image_Manager::get_default_image_extension();
            $color = (string) $row['color'];
            $attribute_id = (int) $row['id_attribute'];
            $texture_file = _PS_COL_IMG_DIR_ . $attribute_id . '.' . $image_extension;
            if (!$color && !file_exists($texture_file)) {
                continue;
            }
            $product_id = (int) $row['id_product'];
            $colors[$product_id][] = ['id_attribute' => $attribute_id, 'id_product' => $product_id, 'id_product_attribute' => (int) $row['id_product_attribute'], 'color' => $color, 'name' => (string) $row['name']];
        }
        return $colors;
    }
    /**
     * Get product accessories (only names)
     *
     * @param int $idLang Language id
     * @param int $idProduct Product id
     *
     * @return array Product accessories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_accessories_light($id_lang, $id_product)
    {
        return Db::read_only()->get_array('
			SELECT p.`id_product`, p.`reference`, pl.`name`
			FROM `' . _DB_PREFIX_ . 'accessory`
			LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON (p.`id_product`= `id_product_2`)
			' . Shop::add_sql_association('product', 'p') . '
			LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (
				p.`id_product` = pl.`id_product`
				AND pl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl') . '
			)
			WHERE `id_product_1` = ' . (int) $id_product);
    }
    /**
     * @param int $idProduct
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_accessory_by_id($id_product)
    {
        return Db::read_only()->get_row('SELECT `id_product`, `name` FROM `' . _DB_PREFIX_ . 'product_lang` WHERE `id_product` = ' . (int) $id_product);
    }
    /**
     * @param int $idProduct
     * @param int $idFeature
     * @param int $idFeatureValue
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function add_feature_product_import($id_product, $id_feature, $id_feature_value)
    {
        return Db::get_instance()->execute('
			INSERT INTO `' . _DB_PREFIX_ . 'feature_product` (`id_feature`, `id_product`, `id_feature_value`)
			VALUES (' . (int) $id_feature . ', ' . (int) $id_product . ', ' . (int) $id_feature_value . ')
			ON DUPLICATE KEY UPDATE `id_feature_value` = ' . (int) $id_feature_value);
    }
    /**
     * @param array $productIds
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function cache_products_features($product_ids): void
    {
        if (!Feature::is_feature_active()) {
            return;
        }
        $product_implode = [];
        foreach ($product_ids as $id_product) {
            if ((int) $id_product && !array_key_exists($id_product, static::$_cache_features)) {
                $product_implode[] = (int) $id_product;
            }
        }
        if (!count($product_implode)) {
            return;
        }
        $result = Db::read_only()->get_array('
		SELECT id_feature, id_product, id_feature_value
		FROM `' . _DB_PREFIX_ . 'feature_product`
		WHERE `id_product` IN (' . implode(',', $product_implode) . ')');
        foreach ($result as $row) {
            if (!array_key_exists($row['id_product'], static::$_cache_features)) {
                static::$_cache_features[$row['id_product']] = [];
            }
            static::$_cache_features[$row['id_product']][] = $row;
        }
    }
    /**
     * Admin panel product search
     *
     * @param int $idLang Language id
     * @param string $query Search query
     *
     * @return array Matching products
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function search_by_name($id_lang, $query, ?Context $context = null)
    {
        $sql = new Db_Query();
        $sql->select('p.id_product');
        $sql->select('pl.name');
        $sql->select('p.ean13');
        $sql->select('p.upc');
        $sql->select('product_shop.active');
        $sql->select('p.reference');
        $sql->select('m.name AS manufacturer_name');
        $sql->select('stock.`quantity`');
        $sql->select('product_shop.advanced_stock_management');
        $sql->select('product_shop.customizable');
        $sql->from('product', 'p');
        $sql->join(Shop::add_sql_association('product', 'p'));
        $sql->left_join('product_lang', 'pl', 'p.`id_product` = pl.`id_product`
			AND pl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl'));
        $sql->left_join('manufacturer', 'm', 'm.`id_manufacturer` = p.`id_manufacturer`');
        $where = 'pl.`name` LIKE \'%' . p_sql($query) . '%\'
		OR p.`ean13` LIKE \'%' . p_sql($query) . '%\'
		OR p.`upc` LIKE \'%' . p_sql($query) . '%\'
		OR p.`reference` LIKE \'%' . p_sql($query) . '%\'
		OR p.`supplier_reference` LIKE \'%' . p_sql($query) . '%\'
		OR EXISTS(SELECT * FROM `' . _DB_PREFIX_ . 'product_supplier` sp WHERE sp.`id_product` = p.`id_product` AND `product_supplier_reference` LIKE \'%' . p_sql($query) . '%\')';
        $sql->order_by('pl.`name` ASC');
        if (Combination::is_feature_active()) {
            $where .= ' OR EXISTS(SELECT * FROM `' . _DB_PREFIX_ . 'product_attribute` `pa` WHERE pa.`id_product` = p.`id_product` AND (pa.`reference` LIKE \'%' . p_sql($query) . '%\'
			OR pa.`supplier_reference` LIKE \'%' . p_sql($query) . '%\'
			OR pa.`ean13` LIKE \'%' . p_sql($query) . '%\'
			OR pa.`upc` LIKE \'%' . p_sql($query) . '%\'))';
        }
        $sql->where($where);
        $sql->join(static::sql_stock('p', 0));
        $result = Db::read_only()->get_array($sql);
        if (!$result) {
            return [];
        }
        $results_array = [];
        foreach ($result as $row) {
            $row['price_tax_incl'] = static::get_price_static($row['id_product'], true);
            $row['price_tax_excl'] = static::get_price_static($row['id_product'], false);
            $results_array[] = $row;
        }
        return $results_array;
    }
    /**
     * Duplicate attributes when duplicating a product
     *
     * @param int $idProductOld Old product ID
     * @param int $idProductNew New product ID
     *
     * @return array|bool
     *
     * @throws PrestaShopException
     */
    public static function duplicate_attributes($id_product_old, $id_product_new)
    {
        $return = true;
        $combination_images = [];
        $conn = Db::get_instance();
        $result = $conn->get_array('
		SELECT pa.*, product_attribute_shop.*
			FROM `' . _DB_PREFIX_ . 'product_attribute` pa
			' . Shop::add_sql_association('product_attribute', 'pa') . '
			WHERE pa.`id_product` = ' . (int) $id_product_old);
        $combinations = [];
        foreach ($result as $row) {
            $id_product_attribute_old = (int) $row['id_product_attribute'];
            $quantity_attribute_old = $conn->get_value((new Db_Query())->select('`quantity`')->from('stock_available')->where('`id_product` = ' . (int) $id_product_old)->where('`id_product_attribute` = ' . (int) $row['id_product_attribute']));
            if (!isset($combinations[$id_product_attribute_old])) {
                $id_combination = null;
                $id_shop = null;
                $result2 = $conn->get_array('
				SELECT *
				FROM `' . _DB_PREFIX_ . 'product_attribute_combination`
					WHERE `id_product_attribute` = ' . $id_product_attribute_old);
            } else {
                $id_combination = $combinations[$id_product_attribute_old];
                $id_shop = (int) $row['id_shop'];
                $context_old = Shop::get_context();
                $context_shop_id_old = Shop::get_context_shop_id();
                Shop::set_context(Shop::CONTEXT_SHOP, $id_shop);
            }
            $row['id_product'] = $id_product_new;
            unset($row['id_product_attribute']);
            $combination = new Combination($id_combination, null, $id_shop);
            foreach ($row as $k => $v) {
                $combination->{$k} = $v;
            }
            $return = $combination->save() && $return;
            $id_product_attribute_new = (int) $combination->id;
            // Set stock quantity
            Stock_Available::set_quantity((int) $id_product_new, $id_product_attribute_new, (int) $quantity_attribute_old, $id_shop);
            Stock_Available::set_product_out_of_stock((int) $id_product_new, Stock_Available::out_of_stock($id_product_old), $id_shop, $id_product_attribute_new);
            if ($result_images = static::_get_attribute_image_associations($id_product_attribute_old)) {
                $combination_images['old'][$id_product_attribute_old] = $result_images;
                $combination_images['new'][$id_product_attribute_new] = $result_images;
            }
            if (!isset($combinations[$id_product_attribute_old])) {
                $combinations[$id_product_attribute_old] = $id_product_attribute_new;
                foreach ($result2 as $row2) {
                    $row2['id_product_attribute'] = $id_product_attribute_new;
                    $return = $conn->insert('product_attribute_combination', $row2) && $return;
                }
            } else {
                Shop::set_context($context_old, $context_shop_id_old);
            }
            //Copy suppliers
            $result3 = $conn->get_array('
			SELECT *
			FROM `' . _DB_PREFIX_ . 'product_supplier`
			WHERE `id_product_attribute` = ' . $id_product_attribute_old . '
			AND `id_product` = ' . (int) $id_product_old);
            foreach ($result3 as $row3) {
                unset($row3['id_product_supplier']);
                $row3['id_product'] = $id_product_new;
                $row3['id_product_attribute'] = $id_product_attribute_new;
                $return = $conn->insert('product_supplier', $row3, false, true, Db::INSERT_IGNORE) && $return;
            }
        }
        // duplicate attribute impacts
        $impacts = static::get_attributes_impacts($id_product_old);
        foreach ($impacts as $id_attribute => $impact) {
            $conn->insert('attribute_impact', ['id_product' => (int) $id_product_new, 'id_attribute' => (int) $id_attribute, 'weight' => (float) $impact['weight'], 'price' => (float) $impact['price'], 'width' => (float) $impact['width'], 'height' => (float) $impact['height'], 'depth' => (float) $impact['depth']]);
        }
        return !$return ? false : $combination_images;
    }
    /**
     * Get product attribute image associations
     *
     * @param int $idProductAttribute
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function _get_attribute_image_associations($id_product_attribute)
    {
        $combination_images = [];
        $data = Db::read_only()->get_array('
			SELECT `id_image`
			FROM `' . _DB_PREFIX_ . 'product_attribute_image`
			WHERE `id_product_attribute` = ' . (int) $id_product_attribute);
        foreach ($data as $row) {
            $combination_images[] = (int) $row['id_image'];
        }
        return $combination_images;
    }
    /**
     * @param int $idProduct
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_attributes_impacts($id_product)
    {
        $return = [];
        $result = Db::read_only()->get_array((new Db_Query())->select('ai.id_attribute')->select('ai.price')->select('ai.weight')->select('ai.width')->select('ai.height')->select('ai.depth')->from('attribute_impact', 'ai')->where('ai.id_product = ' . (int) $id_product));
        foreach ($result as $impact) {
            $attribute_id = (int) $impact['id_attribute'];
            $return[$attribute_id] = ['price' => (float) $impact['price'], 'weight' => (float) $impact['weight'], 'width' => (float) $impact['width'], 'height' => (float) $impact['height'], 'depth' => (float) $impact['depth']];
        }
        return $return;
    }
    /**
     * @param int $idProductOld
     * @param int $idProductNew
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function duplicate_accessories($id_product_old, $id_product_new)
    {
        $return = true;
        $result = Db::read_only()->get_array('
		SELECT *
		FROM `' . _DB_PREFIX_ . 'accessory`
		WHERE `id_product_1` = ' . (int) $id_product_old);
        foreach ($result as $row) {
            $data = ['id_product_1' => (int) $id_product_new, 'id_product_2' => (int) $row['id_product_2']];
            $return = Db::get_instance()->insert('accessory', $data) && $return;
        }
        return $return;
    }
    /**
     * @param int $idProductOld
     * @param int $idProductNew
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function duplicate_tags($id_product_old, $id_product_new)
    {
        $tags = Db::read_only()->get_array('SELECT `id_tag`, `id_lang` FROM `' . _DB_PREFIX_ . 'product_tag` WHERE `id_product` = ' . (int) $id_product_old);
        if (!$tags) {
            return true;
        }
        $data = [];
        foreach ($tags as $tag) {
            $data[] = ['id_product' => (int) $id_product_new, 'id_tag' => (int) $tag['id_tag'], 'id_lang' => (int) $tag['id_lang']];
        }
        return Db::get_instance()->insert('product_tag', $data);
    }
    /**
     * @param int $idProductOld
     * @param int $idProductNew
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function duplicate_download($id_product_old, $id_product_new)
    {
        $sql = 'SELECT `display_filename`, `filename`, `date_add`, `date_expiration`, `nb_days_accessible`, `nb_downloadable`, `active`, `is_shareable`
				FROM `' . _DB_PREFIX_ . 'product_download`
				WHERE `id_product` = ' . (int) $id_product_old;
        $results = Db::read_only()->get_array($sql);
        if (!$results) {
            return true;
        }
        $data = [];
        foreach ($results as $row) {
            $new_filename = Product_Download::get_new_filename();
            copy(_PS_DOWNLOAD_DIR_ . $row['filename'], _PS_DOWNLOAD_DIR_ . $new_filename);
            $data[] = ['id_product' => (int) $id_product_new, 'display_filename' => p_sql($row['display_filename']), 'filename' => p_sql($new_filename), 'date_expiration' => p_sql($row['date_expiration']), 'nb_days_accessible' => (int) $row['nb_days_accessible'], 'nb_downloadable' => (int) $row['nb_downloadable'], 'active' => (int) $row['active'], 'is_shareable' => (int) $row['is_shareable'], 'date_add' => date('Y-m-d H:i:s')];
        }
        return Db::get_instance()->insert('product_download', $data);
    }
    /**
     * @param int $idProductOld
     * @param int $idProductNew
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function duplicate_attachments($id_product_old, $id_product_new)
    {
        // Get all ids attachments of the old product
        $sql = 'SELECT `id_attachment` FROM `' . _DB_PREFIX_ . 'product_attachment` WHERE `id_product` = ' . (int) $id_product_old;
        $results = Db::read_only()->get_array($sql);
        if (!$results) {
            return true;
        }
        $data = [];
        // Prepare data of table product_attachment
        foreach ($results as $row) {
            $data[] = ['id_product' => (int) $id_product_new, 'id_attachment' => (int) $row['id_attachment']];
        }
        // Duplicate product attachement
        $res = Db::get_instance()->insert('product_attachment', $data);
        static::update_cache_attachment((int) $id_product_new);
        return $res;
    }
    /**
     * Duplicate features when duplicating a product
     *
     * @param int $idProductOld Old product id
     * @param int $idProductNew New product id
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function duplicate_features($id_product_old, $id_product_new)
    {
        $return = true;
        $conn = Db::get_instance();
        $result = $conn->get_array('
		SELECT *
		FROM `' . _DB_PREFIX_ . 'feature_product`
		WHERE `id_product` = ' . (int) $id_product_old);
        foreach ($result as $row) {
            $result2 = $conn->get_row('
			SELECT *
			FROM `' . _DB_PREFIX_ . 'feature_value`
			WHERE `id_feature_value` = ' . (int) $row['id_feature_value']);
            // Custom feature value, need to duplicate it
            if ($result2['custom']) {
                $old_id_feature_value = $result2['id_feature_value'];
                unset($result2['id_feature_value']);
                $return = $conn->insert('feature_value', $result2) && $return;
                $max_fv = $conn->get_row('
					SELECT MAX(`id_feature_value`) AS nb
					FROM `' . _DB_PREFIX_ . 'feature_value`');
                $new_id_feature_value = $max_fv['nb'];
                foreach (Language::get_i_ds(false) as $id_lang) {
                    $result3 = $conn->get_row('
					SELECT *
					FROM `' . _DB_PREFIX_ . 'feature_value_lang`
					WHERE `id_feature_value` = ' . (int) $old_id_feature_value . '
					AND `id_lang` = ' . (int) $id_lang);
                    if ($result3) {
                        $result3['id_feature_value'] = (int) $new_id_feature_value;
                        $result3['value'] = p_sql($result3['value']);
                        $return = $conn->insert('feature_value_lang', $result3) && $return;
                    }
                }
                $row['id_feature_value'] = $new_id_feature_value;
            }
            $row['id_product'] = (int) $id_product_new;
            $return = $conn->insert('feature_product', $row) && $return;
        }
        return $return;
    }
    /**
     * @param int $oldProductId
     * @param int $productId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function duplicate_specific_prices($old_product_id, $product_id)
    {
        // remove all existing specific prices that might exists for target product
        if (!Specific_Price::delete_by_product_id($product_id)) {
            return false;
        }
        // duplicate specific prices from source product
        foreach (Specific_Price::get_by_product_id((int) $old_product_id) as $data) {
            $specific_price = new Specific_Price((int) $data['id_specific_price']);
            if (!$specific_price->duplicate((int) $product_id)) {
                return false;
            }
        }
        return true;
    }
    /**
     * @param int $oldProductId
     * @param int $productId
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function duplicate_customization_fields($old_product_id, $product_id)
    {
        // If customization is not activated, return success
        if (!Customization::is_feature_active()) {
            return true;
        }
        if (($customizations = static::_get_customization_fields_n_labels($old_product_id)) === false) {
            return false;
        }
        if (empty($customizations)) {
            return true;
        }
        $conn = Db::get_instance();
        foreach ($customizations['fields'] as $customization_field) {
            /* The new datas concern the new product */
            $customization_field['id_product'] = (int) $product_id;
            $old_customization_field_id = (int) $customization_field['id_customization_field'];
            unset($customization_field['id_customization_field']);
            if (!$conn->insert('customization_field', $customization_field) || !$customization_field_id = $conn->Insert_ID()) {
                return false;
            }
            if (isset($customizations['labels'])) {
                foreach ($customizations['labels'][$old_customization_field_id] as $customization_label) {
                    $data = ['id_customization_field' => (int) $customization_field_id, 'id_lang' => (int) $customization_label['id_lang'], 'id_shop' => (int) $customization_label['id_shop'], 'name' => p_sql($customization_label['name'])];
                    if (!$conn->insert('customization_field_lang', $data)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
    /**
     * @param int $productId
     * @param int|null $idShop
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function _get_customization_fields_n_labels($product_id, $id_shop = null)
    {
        if (!Customization::is_feature_active()) {
            return false;
        }
        if (Shop::is_feature_active() && !$id_shop) {
            $id_shop = (int) Context::get_context()->shop->id;
        }
        $customizations = [];
        $conn = Db::read_only();
        $customizations['fields'] = $conn->get_array('
			SELECT `id_customization_field`, `type`, `required`
			FROM `' . _DB_PREFIX_ . 'customization_field`
			WHERE `id_product` = ' . (int) $product_id . '
			ORDER BY `id_customization_field`');
        if (empty($customizations['fields'])) {
            return [];
        }
        $customization_field_ids = [];
        foreach ($customizations['fields'] as $customization_field) {
            $customization_field_ids[] = (int) $customization_field['id_customization_field'];
        }
        $customization_labels = $conn->get_array('
			SELECT `id_customization_field`, `id_lang`, `id_shop`, `name`
			FROM `' . _DB_PREFIX_ . 'customization_field_lang`
			WHERE `id_customization_field` IN (' . implode(', ', $customization_field_ids) . ')' . ($id_shop ? ' AND `id_shop` = ' . $id_shop : '') . '
			ORDER BY `id_customization_field`');
        foreach ($customization_labels as $customization_label) {
            $customizations['labels'][$customization_label['id_customization_field']][] = $customization_label;
        }
        return $customizations;
    }
    /**
     * Adds suppliers from old product onto a newly duplicated product
     *
     * @param int $idProductOld
     * @param int $idProductNew
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function duplicate_suppliers($id_product_old, $id_product_new)
    {
        $result = Db::read_only()->get_array('
		SELECT *
		FROM `' . _DB_PREFIX_ . 'product_supplier`
		WHERE `id_product` = ' . (int) $id_product_old . ' AND `id_product_attribute` = 0');
        foreach ($result as $row) {
            unset($row['id_product_supplier']);
            $row['id_product'] = $id_product_new;
            if (!Db::get_instance()->insert('product_supplier', $row)) {
                return false;
            }
        }
        return true;
    }
    /**
     * @param int $idCart
     * @param int|null $idLang
     * @param int|bool $onlyInCart
     * @param int|null $idShop
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_all_customized_datas($id_cart, $id_lang = null, $only_in_cart = true, $id_shop = null)
    {
        if (!Customization::is_feature_active()) {
            return false;
        }
        // No need to query if there isn't any real cart!
        if (!$id_cart) {
            return false;
        }
        if (!$id_lang) {
            $id_lang = Context::get_context()->language->id;
        }
        if (Shop::is_feature_active() && !$id_shop) {
            $id_shop = (int) Context::get_context()->shop->id;
        }
        $connection = Db::read_only();
        if (!$result = $connection->get_array('
			SELECT cd.`id_customization`, c.`id_address_delivery`, c.`id_product`, cfl.`id_customization_field`, c.`id_product_attribute`,
				cd.`type`, cd.`index`, cd.`value`, cfl.`name`
			FROM `' . _DB_PREFIX_ . 'customized_data` cd
			NATURAL JOIN `' . _DB_PREFIX_ . 'customization` c
			LEFT JOIN `' . _DB_PREFIX_ . 'customization_field_lang` cfl ON (cfl.id_customization_field = cd.`index` AND id_lang = ' . (int) $id_lang . ($id_shop ? ' AND cfl.`id_shop` = ' . $id_shop : '') . ')
			WHERE c.`id_cart` = ' . (int) $id_cart . ($only_in_cart ? ' AND c.`in_cart` = 1' : '') . '
			ORDER BY `id_product`, `id_product_attribute`, `type`, `index`')) {
            return false;
        }
        $customized_datas = [];
        foreach ($result as $row) {
            $customized_datas[(int) $row['id_product']][(int) $row['id_product_attribute']][(int) $row['id_address_delivery']][(int) $row['id_customization']]['datas'][(int) $row['type']][] = $row;
        }
        if (!$result = $connection->get_array('SELECT `id_product`, `id_product_attribute`, `id_customization`, `id_address_delivery`, `quantity`, `quantity_refunded`, `quantity_returned`
			FROM `' . _DB_PREFIX_ . 'customization`
			WHERE `id_cart` = ' . (int) $id_cart . ($only_in_cart ? '
			AND `in_cart` = 1' : ''))) {
            return false;
        }
        foreach ($result as $row) {
            $customized_datas[(int) $row['id_product']][(int) $row['id_product_attribute']][(int) $row['id_address_delivery']][(int) $row['id_customization']]['quantity'] = (int) $row['quantity'];
            $customized_datas[(int) $row['id_product']][(int) $row['id_product_attribute']][(int) $row['id_address_delivery']][(int) $row['id_customization']]['quantity_refunded'] = (int) $row['quantity_refunded'];
            $customized_datas[(int) $row['id_product']][(int) $row['id_product_attribute']][(int) $row['id_address_delivery']][(int) $row['id_customization']]['quantity_returned'] = (int) $row['quantity_returned'];
        }
        return $customized_datas;
    }
    /**
     * @param array $products
     * @param array $customizedDatas
     *
     * @throws PrestaShopException
     */
    public static function add_customization_price(&$products, &$customized_datas): void
    {
        if (!$customized_datas) {
            return;
        }
        foreach ($products as &$product_update) {
            if (!Customization::is_feature_active()) {
                $product_update['customizationQuantityTotal'] = 0;
                $product_update['customizationQuantityRefunded'] = 0;
                $product_update['customizationQuantityReturned'] = 0;
            } else {
                $customization_quantity = 0;
                $customization_quantity_refunded = 0;
                $customization_quantity_returned = 0;
                /* Compatibility */
                $id_product = isset($product_update['id_product']) ? (int) $product_update['id_product'] : (int) $product_update['product_id'];
                $id_product_attribute = isset($product_update['id_product_attribute']) ? (int) $product_update['id_product_attribute'] : (int) $product_update['product_attribute_id'];
                $id_address_delivery = (int) $product_update['id_address_delivery'];
                $product_quantity = isset($product_update['cart_quantity']) ? (int) $product_update['cart_quantity'] : (int) $product_update['product_quantity'];
                $price = $product_update['price'] ?? $product_update['product_price'];
                if (isset($product_update['price_wt']) && $product_update['price_wt']) {
                    $price_wt = $product_update['price_wt'];
                } else {
                    $tax_rate = $product_update['tax_rate'] ?? $product_update['rate'];
                    $price_wt = round($price * (1 + $tax_rate / 100), _TB_PRICE_DATABASE_PRECISION_);
                }
                if (!isset($customized_datas[$id_product][$id_product_attribute][$id_address_delivery])) {
                    $id_address_delivery = 0;
                }
                if (isset($customized_datas[$id_product][$id_product_attribute][$id_address_delivery])) {
                    foreach ($customized_datas[$id_product][$id_product_attribute][$id_address_delivery] as $customization) {
                        $customization_quantity += (int) $customization['quantity'];
                        $customization_quantity_refunded += (int) $customization['quantity_refunded'];
                        $customization_quantity_returned += (int) $customization['quantity_returned'];
                    }
                }
                $product_update['customizationQuantityTotal'] = $customization_quantity;
                $product_update['customizationQuantityRefunded'] = $customization_quantity_refunded;
                $product_update['customizationQuantityReturned'] = $customization_quantity_returned;
                if ($customization_quantity) {
                    $product_update['total_wt'] = $price_wt * ($product_quantity - $customization_quantity);
                    $product_update['total_customization_wt'] = $price_wt * $customization_quantity;
                    $product_update['total'] = $price * ($product_quantity - $customization_quantity);
                    $product_update['total_customization'] = $price * $customization_quantity;
                }
            }
        }
    }
    /**
     * Checks if the product is in at least one of the submited categories
     *
     * @param int $idProduct
     * @param array $categories array of category arrays
     *
     * @return bool is the product in at least one category
     *
     * @throws PrestaShopException
     */
    public static function id_is_on_category_id($id_product, $categories)
    {
        if (!((int) $id_product > 0) || !is_array($categories) || empty($categories)) {
            return false;
        }
        $sql = 'SELECT id_product FROM `' . _DB_PREFIX_ . 'category_product` WHERE `id_product` = ' . (int) $id_product . ' AND `id_category` IN (';
        foreach ($categories as $category) {
            $sql .= (int) $category['id_category'] . ',';
        }
        $sql = rtrim($sql, ',') . ')';
        $hash = md5($sql);
        if (!isset(static::$_incat[$hash])) {
            static::$_incat[$hash] = (bool) Db::read_only()->get_value($sql);
        }
        return static::$_incat[$hash];
    }
    /**
     * @param int $idProduct
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_url_rewrite_informations($id_product)
    {
        return Db::read_only()->get_array('
			SELECT pl.`id_lang`, pl.`link_rewrite`, p.`ean13`, cl.`link_rewrite` AS category_rewrite
			FROM `' . _DB_PREFIX_ . 'product` p
			LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (p.`id_product` = pl.`id_product`' . Shop::add_sql_restriction_on_lang('pl') . ')
			' . Shop::add_sql_association('product', 'p') . '
			LEFT JOIN `' . _DB_PREFIX_ . 'lang` l ON (pl.`id_lang` = l.`id_lang`)
			LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (cl.`id_category` = product_shop.`id_category_default`  AND cl.`id_lang` = pl.`id_lang`' . Shop::add_sql_restriction_on_lang('cl') . ')
			WHERE p.`id_product` = ' . (int) $id_product . '
			AND l.`active` = 1
		');
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function reset_eco_tax()
    {
        return Object_Model::update_multishop_table('product', ['ecotax' => 0]);
    }
    /**
     * Get all product attributes ids
     *
     * @param int $idProduct the id of the product
     *
     * @param bool $shopOnly
     *
     * @return array product attribute id list
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_product_attributes_ids($id_product, $shop_only = false)
    {
        return Db::read_only()->get_array('
		SELECT pa.id_product_attribute
		FROM `' . _DB_PREFIX_ . 'product_attribute` pa' . ($shop_only ? Shop::add_sql_association('product_attribute', 'pa') : '') . '
		WHERE pa.`id_product` = ' . (int) $id_product);
    }
    /**
     * @param int $idProduct
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @todo    Remove existing module condition
     */
    public static function get_attributes_informations_by_product($id_product)
    {
        // if blocklayered module is installed we check if user has set custom attribute name
        $conn = Db::read_only();
        if (Module::is_installed('blocklayered') && Module::is_enabled('blocklayered')) {
            $nb_custom_values = $conn->get_array('
			SELECT DISTINCT la.`id_attribute`, la.`url_name` AS `attribute`
			FROM `' . _DB_PREFIX_ . 'attribute` a
			LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
				ON (a.`id_attribute` = pac.`id_attribute`)
			LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
				ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
			' . Shop::add_sql_association('product_attribute', 'pa') . '
			LEFT JOIN `' . _DB_PREFIX_ . 'layered_indexable_attribute_lang_value` la
				ON (la.`id_attribute` = a.`id_attribute` AND la.`id_lang` = ' . (int) Context::get_context()->language->id . ')
			WHERE la.`url_name` IS NOT NULL AND la.`url_name` != \'\'
			AND pa.`id_product` = ' . (int) $id_product);
            if (!empty($nb_custom_values)) {
                $tab_id_attribute = [];
                foreach ($nb_custom_values as $attribute) {
                    $tab_id_attribute[] = $attribute['id_attribute'];
                    $group = $conn->get_array('
					SELECT g.`id_attribute_group`, g.`url_name` AS `group`
					FROM `' . _DB_PREFIX_ . 'layered_indexable_attribute_group_lang_value` g
					LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a
						ON (a.`id_attribute_group` = g.`id_attribute_group`)
					WHERE a.`id_attribute` = ' . (int) $attribute['id_attribute'] . '
					AND g.`id_lang` = ' . (int) Context::get_context()->language->id . '
					AND g.`url_name` IS NOT NULL AND g.`url_name` != \'\'');
                    if (empty($group)) {
                        $group = $conn->get_array('
						SELECT g.`id_attribute_group`, g.`name` AS `group`
						FROM `' . _DB_PREFIX_ . 'attribute_group_lang` g
						LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a
							ON (a.`id_attribute_group` = g.`id_attribute_group`)
						WHERE a.`id_attribute` = ' . (int) $attribute['id_attribute'] . '
						AND g.`id_lang` = ' . (int) Context::get_context()->language->id . '
						AND g.`name` IS NOT NULL');
                    }
                    $result[] = array_merge($attribute, $group[0]);
                }
                $values_not_custom = $conn->get_array('
				SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` AS `attribute`, agl.`name` AS `group`
				FROM `' . _DB_PREFIX_ . 'attribute` a
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
					ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) Context::get_context()->language->id . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
					ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) Context::get_context()->language->id . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
					ON (a.`id_attribute` = pac.`id_attribute`)
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
					ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
				' . Shop::add_sql_association('product_attribute', 'pa') . '
				' . Shop::add_sql_association('attribute', 'pac') . '
				WHERE pa.`id_product` = ' . (int) $id_product . '
				AND a.`id_attribute` NOT IN(' . implode(', ', $tab_id_attribute) . ')');
                $result = array_merge($values_not_custom, $result);
            } else {
                $result = $conn->get_array('
				SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` AS `attribute`, agl.`name` AS `group`
				FROM `' . _DB_PREFIX_ . 'attribute` a
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
					ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) Context::get_context()->language->id . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
					ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) Context::get_context()->language->id . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
					ON (a.`id_attribute` = pac.`id_attribute`)
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
					ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
				' . Shop::add_sql_association('product_attribute', 'pa') . '
				' . Shop::add_sql_association('attribute', 'pac') . '
				WHERE pa.`id_product` = ' . (int) $id_product);
            }
        } else {
            $result = $conn->get_array('
			SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name` AS `attribute`, agl.`name` AS `group`
			FROM `' . _DB_PREFIX_ . 'attribute` a
			LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
				ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) Context::get_context()->language->id . ')
			LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
				ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) Context::get_context()->language->id . ')
			LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
				ON (a.`id_attribute` = pac.`id_attribute`)
			LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
				ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
			' . Shop::add_sql_association('product_attribute', 'pa') . '
			' . Shop::add_sql_association('attribute', 'pac') . '
			WHERE pa.`id_product` = ' . (int) $id_product);
        }
        return $result;
    }
    /**
     * Gets the name of a given product, in the given lang
     *
     * @param int $idProduct
     * @param int $idProductAttribute Optional
     * @param int $idLang Optional
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function get_product_name($id_product, $id_product_attribute = null, $id_lang = null)
    {
        // use the lang in the context if $id_lang is not defined
        if (!$id_lang) {
            $id_lang = (int) Context::get_context()->language->id;
        }
        // creates the query object
        $query = new Db_Query();
        // selects different names, if it is a combination
        if ($id_product_attribute) {
            $query->select('IFNULL(CONCAT(pl.name, \' : \', GROUP_CONCAT(DISTINCT agl.`name`, \' - \', al.name SEPARATOR \', \')),pl.name) as name');
        } else {
            $query->select('DISTINCT pl.name as name');
        }
        // adds joins & where clauses for combinations
        if ($id_product_attribute) {
            $query->from('product_attribute', 'pa');
            $query->join(Shop::add_sql_association('product_attribute', 'pa'));
            $query->inner_join('product_lang', 'pl', 'pl.id_product = pa.id_product AND pl.id_lang = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl'));
            $query->left_join('product_attribute_combination', 'pac', 'pac.id_product_attribute = pa.id_product_attribute');
            $query->left_join('attribute', 'atr', 'atr.id_attribute = pac.id_attribute');
            $query->left_join('attribute_lang', 'al', 'al.id_attribute = atr.id_attribute AND al.id_lang = ' . (int) $id_lang);
            $query->left_join('attribute_group_lang', 'agl', 'agl.id_attribute_group = atr.id_attribute_group AND agl.id_lang = ' . (int) $id_lang);
            $query->where('pa.id_product = ' . (int) $id_product . ' AND pa.id_product_attribute = ' . (int) $id_product_attribute);
        } else {
            // or just adds a 'where' clause for a simple product
            $query->from('product_lang', 'pl');
            $query->where('pl.id_product = ' . (int) $id_product);
            $query->where('pl.id_lang = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl'));
        }
        return Db::read_only()->get_value($query);
    }
    /**
     * For a given product, returns its real quantity
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     * @param int $idWarehouse
     * @param int $idShop
     *
     * @return int real_quantity
     *
     * @throws PrestaShopException
     */
    public static function get_real_quantity($id_product, $id_product_attribute = 0, $id_warehouse = 0, $id_shop = null)
    {
        static $manager = null;
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && is_null($manager)) {
            $manager = Stock_Manager_Factory::get_manager();
        }
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && static::uses_advanced_stock_management($id_product) && Stock_Available::depends_on_stock($id_product, $id_shop)) {
            return $manager->get_product_real_quantities($id_product, $id_product_attribute, $id_warehouse, true);
        }
        return Stock_Available::get_quantity_available_by_product($id_product, $id_product_attribute, $id_shop);
    }
    /**
     * For a given product, tells if it uses the advanced stock management
     *
     * @param int $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function uses_advanced_stock_management($id_product)
    {
        $query = new Db_Query();
        $query->select('product_shop.advanced_stock_management');
        $query->from('product', 'p');
        $query->join(Shop::add_sql_association('product', 'p'));
        $query->where('p.id_product = ' . (int) $id_product);
        return (bool) Db::read_only()->get_value($query);
    }
    /**
     * This method allows to flush price cache
     */
    public static function flush_price_cache(): void
    {
        static::$_prices = [];
        static::$_prices_level2 = [];
    }
    /**
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public static function get_id_tax_rules_group_most_used()
    {
        return Db::read_only()->get_value('
					SELECT id_tax_rules_group
					FROM (
						SELECT COUNT(*) n, product_shop.id_tax_rules_group
						FROM ' . _DB_PREFIX_ . 'product p
						' . Shop::add_sql_association('product', 'p') . '
						JOIN ' . _DB_PREFIX_ . 'tax_rules_group trg ON (product_shop.id_tax_rules_group = trg.id_tax_rules_group)
						WHERE trg.active = 1 AND trg.deleted = 0
						GROUP BY product_shop.id_tax_rules_group
						ORDER BY n DESC
						LIMIT 1
					) most_used');
    }
    /**
     * For a given ean13 reference, returns the corresponding id
     *
     * @param string $ean13
     *
     * @return int id
     *
     * @throws PrestaShopException
     */
    public static function get_id_by_ean13($ean13)
    {
        if (empty($ean13)) {
            return 0;
        }
        if (!Validate::is_ean13($ean13)) {
            return 0;
        }
        $query = new Db_Query();
        $query->select('p.id_product');
        $query->from('product', 'p');
        $query->where('p.ean13 = \'' . p_sql($ean13) . '\'');
        return Db::read_only()->get_value($query);
    }
    /**
     * @param int $idProduct
     * @param bool $full
     *
     * @return string
     */
    public static function get_colors_list_cache_id($id_product, $full = true)
    {
        $cache_id = 'productlist_colors';
        if ($id_product) {
            $cache_id .= '|' . (int) $id_product;
        }
        if ($full) {
            $cache_id .= '|' . (int) Context::get_context()->shop->id . '|' . (int) Context::get_context()->cookie->id_lang;
        }
        return $cache_id;
    }
    /**
     * @param int $idProduct
     * @param int $packStockType
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function set_pack_stock_type($id_product, $pack_stock_type)
    {
        return Db::get_instance()->execute('UPDATE ' . _DB_PREFIX_ . 'product p
		' . Shop::add_sql_association('product', 'p') . ' SET product_shop.pack_stock_type = ' . (int) $pack_stock_type . ' WHERE p.`id_product` = ' . (int) $id_product);
    }
    /**
     * @param int $idProduct
     * @param bool $isDynamic
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function set_dynamic_pack($id_product, $is_dynamic)
    {
        $is_dynamic = (int) $is_dynamic;
        $id_product = (int) $id_product;
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'product p ' . Shop::add_sql_association('product', 'p') . " SET product_shop.pack_dynamic = {$is_dynamic}," . "     p.pack_dynamic = {$is_dynamic}" . " WHERE p.id_product = {$id_product}";
        $ret = Db::get_instance()->execute($sql);
        if ($ret && $is_dynamic) {
            Stock_Available::synchronize_dynamic_pack($id_product);
        }
        return $ret;
    }
    /**
     * @see ObjectModel::getFieldsShop()
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_fields_shop()
    {
        $fields = parent::get_fields_shop();
        if (is_null($this->update_fields) || !empty($this->update_fields['price']) && !empty($this->update_fields['unit_price'])) {
            $fields['unit_price_ratio'] = (float) $this->unit_price > 0 ? $this->price / $this->unit_price : 0;
        }
        $fields['unity'] = p_sql($this->unity);
        return $fields;
    }
    /**
     * Move a product inside its category
     *
     * @param bool $way Up (1) or Down (0)
     * @param int $position
     *
     * @return bool Update result
     *
     * @throws PrestaShopException
     */
    public function update_position($way, $position)
    {
        if (!isset($position)) {
            return false;
        }
        $conn = Db::get_instance();
        $category_id = Tools::get_int_value('id_category', 1);
        $product_id = (int) $this->id;
        $new_position = (int) $position;
        $current_position = (int) $conn->get_value((new Db_Query())->select('position')->from('category_product')->where('id_category = ' . $category_id)->where('id_product = ' . $product_id));
        $result = $conn->execute('
            UPDATE `' . _DB_PREFIX_ . 'category_product`
            SET `position`= `position` ' . ($way ? '-1' : '+1') . '
            WHERE `position` ' . ($way ? '>' : '<') . $current_position . '
              AND `position` ' . ($way ? '<=' : '>=') . $new_position . '
              AND `id_category` =' . $category_id);
        $result = $conn->execute('
            UPDATE `' . _DB_PREFIX_ . 'category_product`
            SET `position` = ' . $new_position . '
            WHERE `id_product` = ' . $product_id . '
              AND `id_category` =' . $category_id) && $result;
        static::clean_positions($category_id);
        Hook::trigger_event('actionProductUpdate', ['id_product' => (int) $this->id, 'product' => $this]);
        return $result;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function toggle_status()
    {
        //test if the product is active and if redirect_type is empty string and set default value to id_product_redirected & redirect_type
        //  /!\ after parent::toggleStatus() active will be false, that why we set 404 by default :p
        if ($this->active) {
            //case where active will be false after parent::toggleStatus()
            $this->id_product_redirected = 0;
            $this->redirect_type = '404';
        } else {
            //case where active will be true after parent::toggleStatus()
            $this->id_product_redirected = 0;
            $this->redirect_type = '';
        }
        return parent::toggle_status();
    }
    /**
     * @param array $products
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_selection($products)
    {
        $return = true;
        if (is_array($products) && $count = count($products)) {
            // Deleting products can be quite long on a cheap server. Let's say 1.5 seconds by product (I've seen it!).
            if (intval(ini_get('max_execution_time')) < round($count * 1.5)) {
                ini_set('max_execution_time', round($count * 1.5));
            }
            foreach ($products as $id_product) {
                $product = new Product((int) $id_product);
                $return = $product->delete() && $return;
            }
        }
        return $return;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete()
    {
        /*
         * It is NOT possible to delete a product if there are currently:
         * - physical stock for this product
         * - supply order(s) for this product
         */
        if (Page_Cache::is_enabled()) {
            Page_Cache::invalidate_entity('product', $this->id);
        }
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && $this->advanced_stock_management) {
            $stock_manager = Stock_Manager_Factory::get_manager();
            $physical_quantity = $stock_manager->get_product_physical_quantities($this->id, 0);
            $real_quantity = $stock_manager->get_product_real_quantities($this->id, 0);
            if ($physical_quantity > 0) {
                return false;
            }
            if ($real_quantity > $physical_quantity) {
                return false;
            }
            $warehouse_product_locations = Adapter_service_Locator::get('Core_Foundation_Database_EntityManager')->get_repository('WarehouseProductLocation')->find_by_id_product($this->id);
            foreach ($warehouse_product_locations as $warehouse_product_location) {
                $warehouse_product_location->delete();
            }
            $stocks = Adapter_service_Locator::get('Core_Foundation_Database_EntityManager')->get_repository('Stock')->find_by_id_product($this->id);
            foreach ($stocks as $stock) {
                $stock->delete();
            }
        }
        $result = parent::delete();
        // Removes the product from StockAvailable, for the current shop
        Stock_Available::remove_product_from_stock_available($this->id);
        $result = $this->delete_product_attributes() && $this->delete_images() && $this->delete_scene_products() && $result;
        // If there are still entries in product_shop, don't remove completely the product
        if ($this->has_multishop_entries()) {
            return true;
        }
        Hook::trigger_event('actionProductDelete', ['id_product' => (int) $this->id, 'product' => $this]);
        if (!$result || !Group_Reduction::delete_product_reduction($this->id) || !$this->delete_categories(true) || !$this->delete_product_features() || !$this->delete_tags() || !$this->delete_cart_products() || !$this->delete_attributes_impacts() || !$this->delete_attachments(false) || !$this->delete_customization() || !Specific_Price::delete_by_product_id((int) $this->id) || !$this->delete_pack() || !$this->delete_product_sale() || !$this->delete_search_indexes() || !$this->delete_accessories() || !$this->delete_from_accessories() || !$this->delete_from_supplier() || !$this->delete_download() || !$this->delete_from_cart_rules()) {
            return false;
        }
        return true;
    }
    /**
     * Delete product attributes
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete_product_attributes()
    {
        Hook::trigger_event('actionProductAttributeDelete', ['id_product_attribute' => 0, 'id_product' => (int) $this->id, 'deleteAllAttributes' => true]);
        $result = true;
        $combinations = new Presta_Shop_Collection('Combination');
        $combinations->where('id_product', '=', $this->id);
        foreach ($combinations as $combination) {
            $result = $combination->delete() && $result;
        }
        Specific_Price_Rule::apply_all_rules([(int) $this->id]);
        Tools::clear_color_list_cache($this->id);
        return $result;
    }
    /**
     * Delete product images from database
     *
     * @return bool success
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_images()
    {
        $result = Db::read_only()->get_array('
			SELECT `id_image`
			FROM `' . _DB_PREFIX_ . 'image`
			WHERE `id_product` = ' . (int) $this->id);
        $status = true;
        if ($result) {
            foreach ($result as $row) {
                $image = new Image($row['id_image']);
                $status = $image->delete() && $status;
            }
        }
        return $status;
    }
    /**
     * Delete product in its scenes
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopException
     */
    public function delete_scene_products()
    {
        return Db::get_instance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'scene_products`
			WHERE `id_product` = ' . (int) $this->id);
    }
    /**
     * Delete all association to category where product is indexed
     *
     * @param bool $cleanPositions clean category positions after deletion
     *
     * @return boolean Deletion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_categories($clean_positions = false)
    {
        $product_id = (int) $this->id;
        $categories = [];
        if ($clean_positions) {
            $categories = Db::read_only()->get_array((new Db_Query())->select('id_category')->from('category_product')->where('id_product = ' . $product_id));
        }
        $return = Db::get_instance()->delete('category_product', 'id_product = ' . $product_id);
        if ($clean_positions && $categories) {
            foreach ($categories as $row) {
                $return = static::clean_positions((int) $row['id_category']) && $return;
            }
        }
        return $return;
    }
    /**
     * Reorder product position in category $id_category.
     * Call it after deleting a product from a category.
     *
     * @param int $idCategory
     * @param int $position Deprecated, no longer in use
     *
     * @return bool
     * @throws PrestaShopException
     */
    public static function clean_positions($id_category, $position = 0)
    {
        $id_category = (int) $id_category;
        $now = date('Y-m-d H:i:s');
        // reset positions of all products within category
        $conn = Db::get_instance();
        $return = $conn->execute('SET @rank:=-1');
        $return = $conn->execute('
            UPDATE `' . _DB_PREFIX_ . 'category_product`
            SET position = @rank:=@rank+1
            WHERE `id_category` = ' . $id_category . '
            ORDER BY `position`, `id_product`
        ') && $return;
        // mark all products whose position within category (might) have changed as modified
        $return = $conn->execute('
            UPDATE `' . _DB_PREFIX_ . 'product` p' . Shop::add_sql_association('product', 'p') . '
            INNER JOIN `' . _DB_PREFIX_ . 'category_product` cp ON (cp.`id_category` = ' . $id_category . ' AND cp.`id_product` = p.`id_product` AND cp.`position` >= ' . $position . ')
            SET p.`date_upd` = "' . $now . '", product_shop.`date_upd` = "' . $now . '"
        ') && $return;
        return $return;
    }
    /**
     * Delete product features
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopException
     */
    public function delete_product_features()
    {
        Specific_Price_Rule::apply_all_rules([(int) $this->id]);
        return $this->delete_features();
    }
    /**
     * Delete features
     *
     * @throws PrestaShopException
     */
    public function delete_features()
    {
        // List products features
        $features = Db::read_only()->get_array('
		SELECT p.*, f.*
		FROM `' . _DB_PREFIX_ . 'feature_product` AS p
		LEFT JOIN `' . _DB_PREFIX_ . 'feature_value` AS f ON (f.`id_feature_value` = p.`id_feature_value`)
		WHERE `id_product` = ' . (int) $this->id);
        $conn = Db::get_instance();
        foreach ($features as $tab) {
            // Delete product custom features
            if ($tab['custom']) {
                $conn->execute('
				DELETE FROM `' . _DB_PREFIX_ . 'feature_value`
				WHERE `id_feature_value` = ' . (int) $tab['id_feature_value']);
                $conn->execute('
				DELETE FROM `' . _DB_PREFIX_ . 'feature_value_lang`
				WHERE `id_feature_value` = ' . (int) $tab['id_feature_value']);
            }
        }
        // Delete product features
        $result = $conn->execute('
		DELETE FROM `' . _DB_PREFIX_ . 'feature_product`
		WHERE `id_product` = ' . (int) $this->id);
        // Delete product features lang
        $result_lang = $conn->execute('
		DELETE FROM `' . _DB_PREFIX_ . 'feature_product_lang`
		WHERE `id_product` = ' . (int) $this->id);
        Specific_Price_Rule::apply_all_rules([(int) $this->id]);
        return $result && $result_lang;
    }
    /**
     * Deletes all feature value of feature with id $featureId
     *
     * @param int $featureId
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_feature_values($feature_id)
    {
        $product_id = (int) $this->id;
        if ($product_id) {
            $feature_id = (int) $feature_id;
            $conn = Db::get_instance();
            $result = $conn->delete('feature_product', "id_product = {$product_id} AND id_feature = {$feature_id}");
            return $conn->delete('feature_product_lang', "id_product = {$product_id} AND id_feature = {$feature_id}") && $result;
        }
        return false;
    }
    /**
     * Delete products tags entries
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopException
     */
    public function delete_tags()
    {
        return Tag::delete_tags_for_product((int) $this->id);
    }
    /**
     * Delete product from cart
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopException
     */
    public function delete_cart_products()
    {
        return Db::get_instance()->delete('cart_product', 'id_product = ' . (int) $this->id);
    }
    /**
     * Delete product attributes impacts
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete_attributes_impacts()
    {
        return Db::get_instance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'attribute_impact`
			WHERE `id_product` = ' . (int) $this->id);
    }
    /**
     * Delete product attachments
     *
     * @param bool $updateAttachmentCache
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopException
     */
    public function delete_attachments($update_attachment_cache = true)
    {
        $res = Db::get_instance()->execute('
			DELETE FROM `' . _DB_PREFIX_ . 'product_attachment`
			WHERE `id_product` = ' . (int) $this->id);
        if (isset($update_attachment_cache) && (bool) $update_attachment_cache === true) {
            static::update_cache_attachment((int) $this->id);
        }
        return $res;
    }
    /**
     * @param int $idProduct
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function update_cache_attachment($id_product)
    {
        $value = (bool) Db::read_only()->get_value('
								SELECT id_attachment
								FROM ' . _DB_PREFIX_ . 'product_attachment
								WHERE id_product=' . (int) $id_product);
        return Db::get_instance()->update('product', ['cache_has_attachments' => (int) $value], 'id_product = ' . (int) $id_product);
    }
    /**
     * Delete product customizations
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopException
     */
    public function delete_customization()
    {
        $conn = Db::get_instance();
        return $conn->execute('DELETE FROM `' . _DB_PREFIX_ . 'customization_field`
				WHERE `id_product` = ' . (int) $this->id) && $conn->execute('DELETE `' . _DB_PREFIX_ . 'customization_field_lang` FROM `' . _DB_PREFIX_ . 'customization_field_lang` LEFT JOIN `' . _DB_PREFIX_ . 'customization_field`
				ON (' . _DB_PREFIX_ . 'customization_field.id_customization_field = ' . _DB_PREFIX_ . 'customization_field_lang.id_customization_field)
				WHERE ' . _DB_PREFIX_ . 'customization_field.id_customization_field IS NULL');
    }
    /**
     * Delete product pack details
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopException
     */
    public function delete_pack()
    {
        return Db::get_instance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'pack`
			WHERE `id_product_pack` = ' . (int) $this->id . '
			OR `id_product_item` = ' . (int) $this->id);
    }
    /**
     * Delete product sales
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopException
     */
    public function delete_product_sale()
    {
        return Db::get_instance()->execute('DELETE FROM `' . _DB_PREFIX_ . 'product_sale`
			WHERE `id_product` = ' . (int) $this->id);
    }
    /**
     * Delete product indexed words
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopException
     */
    public function delete_search_indexes()
    {
        $conn = Db::get_instance();
        return $conn->execute('DELETE FROM `' . _DB_PREFIX_ . 'search_index`
                    WHERE `id_product` = ' . (int) $this->id) && $conn->execute('DELETE FROM `' . _DB_PREFIX_ . 'search_word`
                    WHERE `id_word` NOT IN (
                        SELECT id_word
                        FROM `' . _DB_PREFIX_ . 'search_index`
                    )');
    }
    /**
     * Delete product accessories
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_accessories()
    {
        return Db::get_instance()->delete('accessory', 'id_product_1 = ' . (int) $this->id);
    }
    /**
     * Delete product from other products accessories
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_from_accessories()
    {
        return Db::get_instance()->delete('accessory', 'id_product_2 = ' . (int) $this->id);
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_from_supplier()
    {
        return Db::get_instance()->delete('product_supplier', 'id_product = ' . (int) $this->id);
    }
    /**
     * Remove all downloadable files for product and its attributes
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete_download()
    {
        $result = true;
        $collection_download = new Presta_Shop_Collection('ProductDownload');
        $collection_download->where('id_product', '=', $this->id);
        foreach ($collection_download as $product_download) {
            /** @var ProductDownload $productDownload */
            $result = $product_download->delete() && $result;
        }
        return $result;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_from_cart_rules()
    {
        Cart_Rule::clean_product_rule_integrity('products', $this->id);
        return true;
    }
    /**
     * Update the categories this product belongs to
     *
     * @param array $categories
     * @param bool $keepCurrentPosition Someone thought it would be a good idea
     *                                  to add this parameter, but it has never actually
     *                                  done anything, so you can ignore it. Maybe we'll
     *                                  do something with it in thirty bees 1.1, maybe
     *                                  we don't. As for tb 1.0 we can't change its behavior
     *                                  due to backwards compatibility.
     *
     * @return bool Update/insertion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update_categories($categories, $keep_current_position = false)
    {
        if (empty($categories)) {
            return false;
        }
        $sql = new Db_Query();
        $sql->select('c.`id_category`');
        $sql->from('category_product', 'cp');
        $sql->left_join('category', 'c', 'c.`id_category` = cp.`id_category`');
        $sql->join(Shop::add_sql_association('category', 'c', true));
        $sql->where('cp.`id_category` NOT IN (' . implode(',', array_map(intval(...), $categories)) . ')');
        $sql->where('cp.`id_product` = ' . (int) $this->id);
        $result = Db::read_only()->get_array($sql);
        foreach ($result as $category_to_delete) {
            $this->delete_category($category_to_delete['id_category']);
        }
        if (!$this->add_to_categories($categories)) {
            return false;
        }
        Specific_Price_Rule::apply_all_rules([(int) $this->id]);
        return true;
    }
    /**
     * deleteCategory delete this product from the category $id_category
     *
     * @param int $idCategory
     * @param bool $cleanPositions
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_category($id_category, $clean_positions = true)
    {
        $id_category = (int) $id_category;
        $return = Db::get_instance()->delete('category_product', 'id_product = ' . (int) $this->id . ' AND id_category = ' . $id_category);
        if ($clean_positions) {
            static::clean_positions($id_category);
        }
        Specific_Price_Rule::apply_all_rules([(int) $this->id]);
        return $return;
    }
    /**
     * addToCategories add this product to the category/ies if not exists.
     *
     * @param int[] $categories id_category or array of id_category
     *
     * @return bool true if succeed
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_to_categories($categories = [])
    {
        if (empty($categories)) {
            return false;
        }
        if (!is_array($categories)) {
            $categories = [$categories];
        }
        if (!count($categories)) {
            return false;
        }
        $categories = array_map(intval(...), $categories);
        $current_categories = $this->get_categories();
        $current_categories = array_map(intval(...), $current_categories);
        // for new categ, put product at last position
        $res_categ_new_pos = Db::read_only()->get_array('
			SELECT id_category, MAX(position)+1 newPos
			FROM `' . _DB_PREFIX_ . 'category_product`
			WHERE `id_category` IN(' . implode(',', $categories) . ')
			GROUP BY id_category');
        foreach ($res_categ_new_pos as $array) {
            $new_categories[(int) $array['id_category']] = (int) $array['newPos'];
        }
        $new_category_pos = [];
        foreach ($categories as $id_category) {
            $new_category_pos[$id_category] = $new_categories[$id_category] ?? 0;
        }
        foreach ($categories as $new_id_categ) {
            if (!in_array($new_id_categ, $current_categories)) {
                Db::get_instance()->insert('category_product', ['id_category' => (int) $new_id_categ, 'id_product' => (int) $this->id, 'position' => $new_category_pos[$new_id_categ]], false, true, Db::INSERT_IGNORE);
            }
        }
        return true;
    }
    /**
     * getCategories return an array of categories which this product belongs to
     *
     * @return array of categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_categories()
    {
        return static::get_product_categories($this->id);
    }
    /**
     * getProductCategories return an array of categories which this product belongs to
     *
     * @param string $idProduct
     *
     * @return array of categories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_product_categories($id_product = '')
    {
        $cache_id = 'Product::getProductCategories_' . (int) $id_product;
        if (!Cache::is_stored($cache_id)) {
            $ret = [];
            $row = Db::read_only()->get_array('
				SELECT `id_category` FROM `' . _DB_PREFIX_ . 'category_product`
				WHERE `id_product` = ' . (int) $id_product);
            if ($row) {
                foreach ($row as $val) {
                    $ret[] = (int) $val['id_category'];
                }
            }
            Cache::store($cache_id, $ret);
            return $ret;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * addProductAttribute is deprecated
     *
     * The quantity params now set StockAvailable for the current shop with the specified quantity
     * The supplier_reference params now set the supplier reference of the default supplier of the product if possible
     *
     * @param float $price
     * @param float $weight
     * @param float $unitImpact
     * @param float $ecotax
     * @param int $quantity
     * @param array $idImages
     * @param string $reference
     * @param int|null $idSupplier
     * @param string $ean13
     * @param bool $default
     * @param string|null $location
     * @param string|null $upc
     * @param int $minimalQuantity
     *
     * @return false|int
     * @throws PrestaShopException
     * @deprecated since 1.5.0
     */
    public function add_product_attribute($price, $weight, $unit_impact, $ecotax, $quantity, $id_images, $reference, $id_supplier, $ean13, $default, $location = null, $upc = null, $minimal_quantity = 1)
    {
        Tools::display_as_deprecated();
        $id_product_attribute = $this->add_attribute($price, $weight, $unit_impact, $ecotax, $id_images, $reference, $ean13, $default, $location, $upc, $minimal_quantity);
        if (!$id_product_attribute) {
            return false;
        }
        Stock_Available::set_quantity($this->id, $id_product_attribute, $quantity);
        //Try to set the default supplier reference
        $this->add_supplier_reference($id_supplier, $id_product_attribute);
        return $id_product_attribute;
    }
    /**
     * Add a product attribute
     *
     * @param float $price Additional price
     * @param float $weight Additional weight
     * @param float $unitImpact
     * @param float $ecotax Additional ecotax
     * @param array $idImages Image ids
     * @param string $reference Reference
     * @param string $ean13 Ean-13 barcode
     * @param bool $default Is default attribute for product
     * @param string $location Location
     * @param string|null $upc
     * @param int $minimalQuantity Minimal quantity to add to cart
     * @param string|null $availableDate
     *
     * @return false|int $id_product_attribute or false
     * @throws PrestaShopException
     */
    public function add_attribute($price, $weight, $unit_impact, $ecotax, $id_images, $reference, $ean13, $default, $location = null, $upc = null, $minimal_quantity = 1, array $id_shop_list = [], $available_date = null)
    {
        if (!$this->id) {
            return false;
        }
        $combination = new Combination();
        $combination->id_product = (int) $this->id;
        $combination->price = Tools::parse_number($price);
        $combination->ecotax = Tools::parse_number($ecotax);
        $combination->quantity = 0;
        $combination->weight = Tools::parse_number($weight);
        $combination->unit_price_impact = Tools::parse_number($unit_impact);
        $combination->reference = p_sql($reference);
        $combination->location = p_sql($location);
        $combination->ean13 = p_sql($ean13);
        $combination->upc = p_sql($upc);
        $combination->default_on = (int) $default;
        $combination->minimal_quantity = (int) $minimal_quantity;
        $combination->available_date = $available_date;
        if (count($id_shop_list)) {
            $combination->id_shop_list = array_unique($id_shop_list);
        }
        $combination->add();
        if (!$combination->id) {
            return false;
        }
        $total_quantity = (int) Db::read_only()->get_value('
			SELECT SUM(quantity) AS quantity
			FROM ' . _DB_PREFIX_ . 'stock_available
			WHERE id_product = ' . (int) $this->id . '
			AND id_product_attribute <> 0 ');
        if (!$total_quantity) {
            Db::get_instance()->update('stock_available', ['quantity' => 0], '`id_product` = ' . $this->id);
        }
        $id_default_attribute = static::update_default_attribute($this->id);
        if ($id_default_attribute) {
            $this->cache_default_attribute = $id_default_attribute;
            if (!$combination->available_date) {
                $this->set_available_date();
            }
        }
        if (!empty($id_images)) {
            $combination->set_images($id_images);
        }
        Tools::clear_color_list_cache($this->id);
        if (Configuration::get('PS_DEFAULT_WAREHOUSE_NEW_PRODUCT') != 0 && Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
            $warehouse_location_entity = new Warehouse_Product_Location();
            $warehouse_location_entity->id_product = $this->id;
            $warehouse_location_entity->id_product_attribute = (int) $combination->id;
            $warehouse_location_entity->id_warehouse = Configuration::get('PS_DEFAULT_WAREHOUSE_NEW_PRODUCT');
            $warehouse_location_entity->location = p_sql('');
            $warehouse_location_entity->save();
        }
        return (int) $combination->id;
    }
    /**
     * @param int $idProduct
     *
     * @return bool|int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function update_default_attribute($id_product)
    {
        $id_default_attribute = (int) static::get_default_attribute($id_product, 0, true);
        $conn = Db::get_instance();
        $result = $conn->update('product_shop', ['cache_default_attribute' => $id_default_attribute], 'id_product = ' . (int) $id_product . Shop::add_sql_restriction());
        $result = $conn->update('product', ['cache_default_attribute' => $id_default_attribute], 'id_product = ' . (int) $id_product) && $result;
        if ($result && $id_default_attribute) {
            return $id_default_attribute;
        }
        return $result;
    }
    /**
     * @param int $idProduct
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_shops_by_product($id_product)
    {
        return Db::read_only()->get_array('
			SELECT `id_shop`
			FROM `' . _DB_PREFIX_ . 'product_shop`
			WHERE `id_product` = ' . (int) $id_product);
    }
    /**
     * @param array $combinations
     * @param array $attributes
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function generate_multiple_combinations($combinations, $attributes)
    {
        $res = true;
        foreach ($combinations as $key => $combination) {
            $id_combination = (int) $this->product_attribute_exists($attributes[$key], false, null, true, true);
            $obj = new Combination($id_combination);
            if ($id_combination) {
                $obj->minimal_quantity = 1;
                $obj->available_date = '0000-00-00';
            }
            foreach ($combination as $field => $value) {
                $obj->{$field} = $value;
            }
            $this->set_available_date();
            $obj->save();
            if (!$id_combination) {
                $attribute_list = [];
                foreach ($attributes[$key] as $id_attribute) {
                    $attribute_list[] = ['id_product_attribute' => (int) $obj->id, 'id_attribute' => (int) $id_attribute];
                }
                $res = Db::get_instance()->insert('product_attribute_combination', $attribute_list) && $res;
            }
        }
        return $this->check_default_attributes() && $res;
    }
    /**
     * @param array $attributesList
     * @param bool $currentProductAttribute
     * @param bool $allShops
     * @param bool $returnId
     *
     * @return bool|int|string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function product_attribute_exists($attributes_list, $current_product_attribute = false, ?Context $context = null, $all_shops = false, $return_id = false)
    {
        if (!Combination::is_feature_active()) {
            return false;
        }
        if ($context === null) {
            $context = Context::get_context();
        }
        $result = Db::read_only()->get_array('SELECT pac.`id_attribute`, pac.`id_product_attribute`
			FROM `' . _DB_PREFIX_ . 'product_attribute` pa
			JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` pas ON (pas.id_product_attribute = pa.id_product_attribute)
			LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
			WHERE 1 ' . (!$all_shops ? ' AND pas.id_shop =' . (int) $context->shop->id : '') . ' AND pa.`id_product` = ' . (int) $this->id . ($all_shops ? ' GROUP BY pac.id_attribute, pac.id_product_attribute ' : ''));
        /* If something's wrong */
        if (empty($result)) {
            return false;
        }
        /* Product attributes simulation */
        $product_attributes = [];
        foreach ($result as $product_attribute) {
            $product_attributes[$product_attribute['id_product_attribute']][] = $product_attribute['id_attribute'];
        }
        /* Checking product's attribute existence */
        foreach ($product_attributes as $key => $product_attribute) {
            if (count($product_attribute) == count($attributes_list)) {
                $diff = false;
                for ($i = 0; $diff == false && isset($product_attribute[$i]); $i++) {
                    if (!in_array($product_attribute[$i], $attributes_list) || $key == $current_product_attribute) {
                        $diff = true;
                    }
                }
                if (!$diff) {
                    if ($return_id) {
                        return $key;
                    }
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * @param string $availableDate
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_available_date($available_date = '0000-00-00')
    {
        if (Validate::is_date_format($available_date) && $this->available_date != $available_date) {
            $this->available_date = $available_date;
            if ($this->id) {
                $fields_to_update = $this->update_fields;
                try {
                    $this->set_fields_to_update(['available_date' => true]);
                    return $this->update();
                } finally {
                    if (!is_null($fields_to_update)) {
                        $this->set_fields_to_update($fields_to_update);
                    }
                }
            } else {
                return true;
            }
        }
        return false;
    }
    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($null_values = false)
    {
        if (Page_Cache::is_enabled()) {
            Page_Cache::invalidate_entity('product', $this->id);
        }
        $return = parent::update($null_values);
        $this->set_group_reduction();
        // Sync stock Reference, EAN13 and UPC
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && Stock_Available::depends_on_stock($this->id, Context::get_context()->shop->id)) {
            Db::get_instance()->update('stock', ['reference' => p_sql($this->reference), 'ean13' => p_sql($this->ean13), 'upc' => p_sql($this->upc)], 'id_product = ' . (int) $this->id . ' AND id_product_attribute = 0');
        }
        Hook::trigger_event('actionProductSave', ['id_product' => (int) $this->id, 'product' => $this]);
        Hook::trigger_event('actionProductUpdate', ['id_product' => (int) $this->id, 'product' => $this]);
        if ($this->get_type() == static::PTYPE_VIRTUAL && $this->active && !Configuration::get('PS_VIRTUAL_PROD_FEATURE_ACTIVE')) {
            Configuration::update_global_value('PS_VIRTUAL_PROD_FEATURE_ACTIVE', '1');
        }
        return $return;
    }
    /**
     * Set Group reduction if needed
     *
     * @throws PrestaShopException
     */
    public function set_group_reduction()
    {
        return Group_Reduction::set_product_reduction($this->id);
    }
    /**
     * Get the product type (simple, virtual, pack)
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function get_type()
    {
        if (!$this->id) {
            return static::PTYPE_SIMPLE;
        }
        if (Pack::is_pack($this->id)) {
            return static::PTYPE_PACK;
        }
        if ($this->is_virtual) {
            return static::PTYPE_VIRTUAL;
        }
        return static::PTYPE_SIMPLE;
    }
    /**
     * @param float $wholesalePrice
     * @param float $price
     * @param float $weight
     * @param float $unitImpact
     * @param float $ecotax
     * @param int $quantity DEPRECATED
     * @param array $idImages
     * @param string $reference
     * @param int|null $idSupplier
     * @param string $ean13
     * @param string $default
     * @param string|null $location
     * @param string|null $upc
     * @param int $minimalQuantity
     * @param string|null $availableDate
     *
     * @return false|int
     * @throws PrestaShopException
     */
    public function add_combination_entity($wholesale_price, $price, $weight, $unit_impact, $ecotax, $quantity, $id_images, $reference, $id_supplier, $ean13, $default, $location = null, $upc = null, $minimal_quantity = 1, array $id_shop_list = [], $available_date = null)
    {
        $id_product_attribute = $this->add_attribute($price, $weight, $unit_impact, $ecotax, $id_images, $reference, $ean13, $default, $location, $upc, $minimal_quantity, $id_shop_list, $available_date);
        $this->add_supplier_reference($id_supplier, $id_product_attribute);
        $result = Object_Model::update_multishop_table('Combination', ['wholesale_price' => round($wholesale_price, _TB_PRICE_DATABASE_PRECISION_)], 'a.id_product_attribute = ' . (int) $id_product_attribute);
        if (!$id_product_attribute || !$result) {
            return false;
        }
        return $id_product_attribute;
    }
    /**
     * Sets or updates Supplier Reference
     *
     * @param int|null $idSupplier
     * @param int $idProductAttribute
     * @param string|null $supplierReference
     * @param float|null $price
     * @param int|null $idCurrency
     * @param string|null $supplierProductName
     * @param string|null $comment
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_supplier_reference($id_supplier, $id_product_attribute, $supplier_reference = null, $price = null, $id_currency = null, $supplier_product_name = null, $comment = null): void
    {
        //Try to set the default supplier reference
        if ($id_supplier > 0 && $this->id > 0) {
            $id_product_supplier = (int) Product_Supplier::get_id_by_product_and_supplier($this->id, $id_product_attribute, $id_supplier);
            $product_supplier = new Product_Supplier($id_product_supplier);
            if (!$id_product_supplier) {
                $product_supplier->id_product = (int) $this->id;
                $product_supplier->id_product_attribute = (int) $id_product_attribute;
                $product_supplier->id_supplier = (int) $id_supplier;
            }
            if (!is_null($supplier_reference)) {
                $product_supplier->product_supplier_reference = (string) $supplier_reference;
            }
            if (!is_null($supplier_product_name)) {
                $product_supplier->product_supplier_name = (string) $supplier_product_name;
            }
            if (!is_null($comment)) {
                $product_supplier->product_supplier_comment = (string) $comment;
            }
            if (!is_null($price)) {
                $product_supplier->product_supplier_price_te = (float) $price;
            }
            if (!is_null($id_currency)) {
                $product_supplier->id_currency = (int) $id_currency;
            }
            $product_supplier->save();
        }
    }
    /**
     * @param array $attributes
     * @param bool $setDefault
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_product_attribute_multiple($attributes, $set_default = true)
    {
        Tools::display_as_deprecated();
        $return = [];
        $default_value = 1;
        foreach ($attributes as &$attribute) {
            $obj = new Combination();
            foreach ($attribute as $key => $value) {
                $obj->{$key} = $value;
            }
            if ($set_default) {
                $obj->default_on = $default_value;
                $default_value = 0;
                // if we add a combination for this shop and this product does not use the combination feature in other shop,
                // we clone the default combination in every shop linked to this product
                if (!$this->has_attributes_in_other_shops()) {
                    $id_shop_list_array = static::get_shops_by_product($this->id);
                    $id_shop_list = [];
                    foreach ($id_shop_list_array as $array_shop) {
                        $id_shop_list[] = $array_shop['id_shop'];
                    }
                    $obj->id_shop_list = $id_shop_list;
                }
            }
            $obj->add();
            $return[] = $obj->id;
        }
        return $return;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function has_attributes_in_other_shops()
    {
        return (bool) Db::read_only()->get_value('
			SELECT pa.id_product_attribute
			FROM `' . _DB_PREFIX_ . 'product_attribute` pa
			LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` pas ON (pa.`id_product_attribute` = pas.`id_product_attribute`)
			WHERE pa.`id_product` = ' . (int) $this->id);
    }
    /**
     * Update a product attribute
     *
     * @param int $idProductAttribute
     * @param float $wholesalePrice
     * @param float $price
     * @param float $weight
     * @param float $unit
     * @param float $ecotax
     * @param array $idImages
     * @param string $reference
     * @param int|null $idSupplier
     * @param string $ean13
     * @param bool $default
     * @param string|null $location
     * @param string|null $upc
     * @param int $minimalQuantity
     * @param string $availableDate
     *
     * @return bool
     * @throws PrestaShopException
     * @see updateAttribute() to use instead
     * @see ProductSupplier for manage supplier reference(s)
     * @deprecated 1.0.0
     */
    public function update_product_attribute($id_product_attribute, $wholesale_price, $price, $weight, $unit, $ecotax, $id_images, $reference, $id_supplier, $ean13, $default, $location, $upc, $minimal_quantity, $available_date)
    {
        Tools::display_as_deprecated();
        $return = $this->update_attribute($id_product_attribute, $wholesale_price, $price, $weight, $unit, $ecotax, $id_images, $reference, $ean13, $default, null, null, $minimal_quantity, $available_date);
        $this->add_supplier_reference($id_supplier, $id_product_attribute);
        return $return;
    }
    /**
     * Update a product attribute
     *
     * @param int $idProductAttribute Product attribute id
     * @param float $wholesalePrice Wholesale price
     * @param float $price Additional price
     * @param float $weight Additional weight
     * @param float $unit
     * @param float $ecotax Additional ecotax
     * @param array $idImages Image id
     * @param string $reference Reference
     * @param string $ean13 Ean-13 barcode
     * @param int $default Default On
     * @param string|null $location
     * @param string $upc Upc barcode
     * @param string $minimalQuantity Minimal quantity
     * @param string|null $availableDate
     * @param bool $updateAllFields
     * @param float|null $widthImpact
     * @param float|null $heightImpact
     * @param float|null $depthImpact
     *
     * @return bool Update result
     * @throws PrestaShopException
     */
    public function update_attribute($id_product_attribute, $wholesale_price, $price, $weight, $unit, $ecotax, $id_images, $reference, $ean13, $default, $location = null, $upc = null, $minimal_quantity = null, $available_date = null, $update_all_fields = true, array $id_shop_list = [], $width_impact = null, $height_impact = null, $depth_impact = null)
    {
        $combination = new Combination($id_product_attribute);
        if (!$update_all_fields) {
            $combination->set_fields_to_update(['price' => !is_null($price), 'wholesale_price' => !is_null($wholesale_price), 'ecotax' => !is_null($ecotax), 'weight' => !is_null($weight), 'width' => !is_null($width_impact), 'height' => !is_null($height_impact), 'depth' => !is_null($depth_impact), 'unit_price_impact' => !is_null($unit), 'default_on' => !is_null($default), 'minimal_quantity' => !is_null($minimal_quantity), 'available_date' => !is_null($available_date)]);
        }
        $combination->price = Tools::parse_number($price);
        $combination->wholesale_price = Tools::parse_number($wholesale_price);
        $combination->ecotax = Tools::parse_number($ecotax);
        $combination->weight = Tools::parse_number($weight);
        $combination->width = Tools::parse_number($width_impact);
        $combination->height = Tools::parse_number($height_impact);
        $combination->depth = Tools::parse_number($depth_impact);
        $combination->unit_price_impact = Tools::parse_number($unit);
        $combination->reference = p_sql($reference);
        $combination->location = p_sql($location);
        $combination->ean13 = p_sql($ean13);
        $combination->upc = p_sql($upc);
        $combination->default_on = (int) $default;
        $combination->minimal_quantity = (int) $minimal_quantity;
        $combination->available_date = $available_date ? p_sql($available_date) : '0000-00-00';
        if (count($id_shop_list)) {
            $combination->id_shop_list = $id_shop_list;
        }
        $combination->save();
        if (is_array($id_images) && count($id_images)) {
            $combination->set_images($id_images);
        }
        $id_default_attribute = (int) static::update_default_attribute($this->id);
        if ($id_default_attribute) {
            $this->cache_default_attribute = $id_default_attribute;
        }
        // Sync stock Reference, EAN13 and UPC for this attribute
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT') && Stock_Available::depends_on_stock($this->id, Context::get_context()->shop->id)) {
            Db::get_instance()->update('stock', ['reference' => p_sql($reference), 'ean13' => p_sql($ean13), 'upc' => p_sql($upc)], 'id_product = ' . $this->id . ' AND id_product_attribute = ' . (int) $id_product_attribute);
        }
        Hook::trigger_event('actionProductAttributeUpdate', ['id_product_attribute' => (int) $id_product_attribute]);
        Tools::clear_color_list_cache($this->id);
        return true;
    }
    /**
     * @return bool
     *
     * @deprecated 1.0.0
     * @throws PrestaShopException
     */
    public function update_quantity_product_with_attribute_quantity()
    {
        Tools::display_as_deprecated();
        return Db::get_instance()->execute('
		UPDATE `' . _DB_PREFIX_ . 'product`
		SET `quantity` = IFNULL(
		(
			SELECT SUM(`quantity`)
			FROM `' . _DB_PREFIX_ . 'product_attribute`
			WHERE `id_product` = ' . (int) $this->id . '
		), \'0\')
		WHERE `id_product` = ' . (int) $this->id);
    }
    /**
     * Add a product attributes combinaison
     *
     * @param int $idProductAttribute Product attribute id
     * @param array $attributes Attributes to forge combinaison
     *
     * @return bool Insertion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function add_attribute_combinaison($id_product_attribute, $attributes)
    {
        Tools::display_as_deprecated();
        if (!is_array($attributes)) {
            return false;
        }
        if (!count($attributes)) {
            return false;
        }
        $combination = new Combination((int) $id_product_attribute);
        return $combination->set_attributes($attributes);
    }
    /**
     * @deprecated 1.0.0
     *
     * @param array $idAttributes
     * @param array $combinations
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_attribute_combination_multiple($id_attributes, $combinations)
    {
        Tools::display_as_deprecated();
        $attributes_list = [];
        foreach ($id_attributes as $nb => $id_product_attribute) {
            if (isset($combinations[$nb])) {
                foreach ($combinations[$nb] as $id_attribute) {
                    $attributes_list[] = ['id_product_attribute' => (int) $id_product_attribute, 'id_attribute' => (int) $id_attribute];
                }
            }
        }
        return Db::get_instance()->insert('product_attribute_combination', $attributes_list);
    }
    /**
     * Get all available product attributes resume
     *
     * @param int $idLang Language id
     * @param string $attributeValueSeparator
     * @param string $attributeSeparator
     *
     * @return array Product attributes combinations
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_attributes_resume($id_lang, $attribute_value_separator = ' - ', $attribute_separator = ', ')
    {
        if (!Combination::is_feature_active()) {
            return [];
        }
        $combinations = Db::read_only()->get_array('SELECT
                    pa.*,
                    product_attribute_shop.*,
                    COALESCE((
                        SELECT GROUP_CONCAT(agl.`name`, \'' . p_sql($attribute_value_separator) . '\',al.`name` ORDER BY agl.`id_attribute_group` SEPARATOR \'' . p_sql($attribute_separator) . '\')
                         FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                         LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
                         LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
                         LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $id_lang . ')
                         LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $id_lang . ')
                         WHERE pac.id_product_attribute  = pa.id_product_attribute
                         GROUP BY pac.id_product_attribute
                   ), \'-\') as attribute_designation
				FROM `' . _DB_PREFIX_ . 'product_attribute` pa
				' . Shop::add_sql_association('product_attribute', 'pa') . '
				WHERE pa.`id_product` = ' . (int) $this->id . '
				GROUP BY pa.`id_product_attribute`
				ORDER BY pa.`id_product_attribute`');
        if (!$combinations) {
            return [];
        }
        $quantities = Stock_Available::get_combination_quantities((int) $this->id);
        foreach ($combinations as &$combination) {
            $product_attribute_id = (int) $combination['id_product_attribute'];
            $combination['quantity'] = $quantities[$product_attribute_id] ?? 0;
        }
        return $combinations;
    }
    /**
     * Get product attribute combination by id_product_attribute
     *
     * @param int $idProductAttribute
     * @param int $idLang Language id
     *
     * @return array Product attribute combination by id_product_attribute
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_attribute_combinations_by_id($id_product_attribute, $id_lang)
    {
        if (!Combination::is_feature_active()) {
            return [];
        }
        $sql = 'SELECT pa.*, product_attribute_shop.*, ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, al.`name` AS attribute_name,
					a.`id_attribute`
				FROM `' . _DB_PREFIX_ . 'product_attribute` pa
				' . Shop::add_sql_association('product_attribute', 'pa') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $id_lang . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $id_lang . ')
				WHERE pa.`id_product` = ' . (int) $this->id . '
				AND pa.`id_product_attribute` = ' . (int) $id_product_attribute . '
				GROUP BY pa.`id_product_attribute`, ag.`id_attribute_group`
				ORDER BY pa.`id_product_attribute`';
        $res = Db::read_only()->get_array($sql);
        //Get quantity of each variations
        foreach ($res as $key => $row) {
            $cache_key = $row['id_product'] . '_' . $row['id_product_attribute'] . '_quantity';
            if (!Cache::is_stored($cache_key)) {
                $result = Stock_Available::get_quantity_available_by_product($row['id_product'], $row['id_product_attribute']);
                Cache::store($cache_key, $result);
                $res[$key]['quantity'] = $result;
            } else {
                $res[$key]['quantity'] = Cache::retrieve($cache_key);
            }
        }
        return $res;
    }
    /**
     * @param int $idLang
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_combination_images($id_lang)
    {
        if (!Combination::is_feature_active()) {
            return false;
        }
        $conn = Db::read_only();
        $product_attributes = $conn->get_array('SELECT `id_product_attribute`
			FROM `' . _DB_PREFIX_ . 'product_attribute`
			WHERE `id_product` = ' . (int) $this->id);
        if (!$product_attributes) {
            return false;
        }
        $ids = [];
        foreach ($product_attributes as $product_attribute) {
            $ids[] = (int) $product_attribute['id_product_attribute'];
        }
        $result = $conn->get_array('
			SELECT pai.`id_image`, pai.`id_product_attribute`, il.`legend`
			FROM `' . _DB_PREFIX_ . 'product_attribute_image` pai
			LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (il.`id_image` = pai.`id_image`)
			LEFT JOIN `' . _DB_PREFIX_ . 'image` i ON (i.`id_image` = pai.`id_image`)
			WHERE pai.`id_product_attribute` IN (' . implode(', ', $ids) . ') AND il.`id_lang` = ' . (int) $id_lang . ' ORDER BY i.`position`');
        if (!$result) {
            return false;
        }
        $images = [];
        foreach ($result as $row) {
            $images[$row['id_product_attribute']][] = $row;
        }
        return $images;
    }
    /**
     * Check if product has attributes combinations
     *
     * @return int Attributes combinations number
     *
     * @throws PrestaShopException
     */
    public function has_attributes()
    {
        if (!Combination::is_feature_active()) {
            return 0;
        }
        $cache_id = 'Product::hasAttributes_' . (int) $this->id;
        if (!Cache::is_stored($cache_id)) {
            $result = (int) Db::read_only()->get_value((new Db_Query())->select('COUNT(*)')->from('product_attribute', 'pa')->join(Shop::add_sql_association('product_attribute', 'pa'))->where('pa.`id_product` = ' . (int) $this->id));
            Cache::store($cache_id, $result);
        }
        return (int) Cache::retrieve($cache_id);
    }
    /**
     * Gets carriers assigned to the product
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_carriers()
    {
        return Db::read_only()->get_array('
			SELECT c.*
			FROM `' . _DB_PREFIX_ . 'product_carrier` pc
			INNER JOIN `' . _DB_PREFIX_ . 'carrier` c
				ON (c.`id_reference` = pc.`id_carrier_reference` AND c.`deleted` = 0)
			WHERE pc.`id_product` = ' . (int) $this->id . '
				AND pc.`id_shop` = ' . (int) $this->id_shop);
    }
    /**
     * Sets carriers assigned to the product
     *
     * @param array $carrierList
     *
     * @throws PrestaShopException
     */
    public function set_carriers($carrier_list): void
    {
        static::associate_product_with_carriers($this->id, $carrier_list, [$this->id_shop]);
    }
    /**
     * Associate product with list of carriers
     *
     * @param int $productId product id
     * @param int[] $carrierIds carrier reference ids
     * @param int[] $shopIds shop id
     *
     * @throws PrestaShopException
     */
    public static function associate_product_with_carriers($product_id, array $carrier_ids, array $shop_ids): void
    {
        $product_id = (int) $product_id;
        $conn = Db::get_instance();
        /** @var int[] $carrierIds */
        $carrier_ids = array_unique(array_filter(array_map(intval(...), $carrier_ids)));
        /** @var int[] $shopIds */
        $shop_ids = array_unique(array_filter(array_map(intval(...), $shop_ids)));
        $data = [];
        foreach ($shop_ids as $shop_id) {
            foreach ($carrier_ids as $carrier_id) {
                $data[] = ['id_product' => $product_id, 'id_carrier_reference' => $carrier_id, 'id_shop' => $shop_id];
            }
            $conn->delete('product_carrier', "id_product = {$product_id} AND id_shop = {$shop_id}");
        }
        if ($data) {
            $conn->insert('product_carrier', $data, false, true, Db::INSERT_IGNORE);
        }
    }
    /**
     * Get product images and legends
     *
     * @param int $idLang Language id for multilingual legends
     *
     * @return array Product images and legends
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_images($id_lang, ?Context $context = null)
    {
        return Db::read_only()->get_array('
			SELECT image_shop.`cover`, i.`id_image`, il.`legend`, i.`position`
			FROM `' . _DB_PREFIX_ . 'image` i
			' . Shop::add_sql_association('image', 'i') . '
			LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (i.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $id_lang . ')
			WHERE i.`id_product` = ' . (int) $this->id . '
			ORDER BY `position`');
    }
    /**
     * Get product price
     * Same as static function getPriceStatic, no need to specify product id
     *
     * @param bool $tax With taxes or not (optional)
     * @param int $idProductAttribute Product attribute id (optional)
     * @param int $decimals Number of decimals (optional)
     * @param int $divisor Util when paying many time without fees (optional)
     *
     * @return float Product price in euros
     *
     * @throws PrestaShopException
     */
    public function get_price($tax = true, $id_product_attribute = null, $decimals = _TB_PRICE_DATABASE_PRECISION_, $divisor = null, $only_reduc = false, $usereduc = true, $quantity = 1)
    {
        return static::get_price_static((int) $this->id, $tax, $id_product_attribute, $decimals, $divisor, $only_reduc, $usereduc, $quantity);
    }
    /*
     ** Customization fields' label management
     */
    /**
     * @param bool $tax
     * @param int|null $idProductAttribute
     * @param int $decimals
     * @param int|null $divisor
     * @param bool $onlyReduc
     * @param bool $usereduc
     * @param int $quantity
     *
     * @return float
     *
     * @throws PrestaShopException
     */
    public function get_public_price($tax = true, $id_product_attribute = null, $decimals = _TB_PRICE_DATABASE_PRECISION_, $divisor = null, $only_reduc = false, $usereduc = true, $quantity = 1)
    {
        $specific_price_output = null;
        return static::get_price_static((int) $this->id, $tax, $id_product_attribute, $decimals, $divisor, $only_reduc, $usereduc, $quantity, false, null, null, null, $specific_price_output, true, true, null, false);
    }
    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    public function get_id_product_attribute_most_expensive()
    {
        if (!Combination::is_feature_active()) {
            return 0;
        }
        return (int) Db::read_only()->get_value('
		SELECT pa.`id_product_attribute`
		FROM `' . _DB_PREFIX_ . 'product_attribute` pa
		' . Shop::add_sql_association('product_attribute', 'pa') . '
		WHERE pa.`id_product` = ' . (int) $this->id . '
		ORDER BY product_attribute_shop.`price` DESC');
    }
    /**
     * @return int
     *
     * @throws PrestaShopException
     */
    public function get_default_id_product_attribute()
    {
        return static::get_product_default_combination_id((int) $this->id);
    }
    /**
     *
     *
     * @throws PrestaShopException
     */
    public static function get_product_default_combination_id(int $product_id): int
    {
        if (!Combination::is_feature_active()) {
            return 0;
        }
        $cache_id = 'Product::getProductDefaultCombinationId_' . $product_id;
        if (!Cache::is_stored($cache_id)) {
            $result = (int) Db::read_only()->get_value((new Db_Query())->select('pa.`id_product_attribute`')->from('product_attribute', 'pa')->join(Shop::add_sql_association('product_attribute', 'pa'))->where('pa.`id_product` = ' . $product_id)->where('product_attribute_shop.default_on = 1'));
            Cache::store($cache_id, $result);
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * @param bool $notax
     * @param bool $idProductAttribute
     * @param int $decimals
     *
     * @return float
     *
     * @throws PrestaShopException
     */
    public function get_price_without_reduct($notax = false, $id_product_attribute = false, $decimals = _TB_PRICE_DATABASE_PRECISION_)
    {
        return static::get_price_static((int) $this->id, !$notax, $id_product_attribute, $decimals, null, false, false);
    }
    /**
     * Check product availability
     *
     * @param int $qty Quantity desired
     *
     * @return bool True if product is available with this quantity
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function check_qty($qty)
    {
        if (Pack::is_pack((int) $this->id) && !Pack::is_in_stock((int) $this->id)) {
            return false;
        }
        if (static::is_available_when_out_of_stock(Stock_Available::out_of_stock($this->id))) {
            return true;
        }
        if (isset($this->id_product_attribute)) {
            $id_product_attribute = $this->id_product_attribute;
        } else {
            $id_product_attribute = 0;
        }
        return $qty <= Stock_Available::get_quantity_available_by_product($this->id, $id_product_attribute);
    }
    /**
     * Check if there is no default attribute and create it if not
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function check_default_attributes()
    {
        if (!$this->id) {
            return false;
        }
        $product_id = (int) $this->id;
        $conn = Db::get_instance();
        $result = true;
        // update shop data
        $data = $conn->get_array((new Db_Query())->select('pas.id_shop')->select('SUM(CASE WHEN pas.default_on THEN 1 ELSE 0 END) AS has_default_on')->select('MIN(pas.id_product_attribute) AS min_id_product_attribute')->from('product_attribute_shop', 'pas')->where('pas.id_product = ' . $product_id)->group_by('pas.id_shop'));
        foreach ($data as $row) {
            if (!$row['has_default_on']) {
                $shop_id = (int) $row['id_shop'];
                $min = (int) $row['min_id_product_attribute'];
                if ($min) {
                    $result = $conn->update('product_attribute_shop', ['default_on' => 1], 'id_shop = ' . $shop_id . ' AND id_product_attribute = ' . $min) && $result;
                }
            }
        }
        // update base table entry
        $row = $conn->get_row((new Db_Query())->select('SUM(CASE WHEN pa.default_on THEN 1 ELSE 0 END) AS has_default_on')->select('MIN(pa.id_product_attribute) AS min_id_product_attribute')->from('product_attribute', 'pa')->where('pa.id_product = ' . $product_id));
        if ($row && !$row['has_default_on']) {
            $min = (int) $row['min_id_product_attribute'];
            if ($min) {
                $result = $conn->update('product_attribute', ['default_on' => 1], 'id_product_attribute = ' . $min) && $result;
            }
        }
        return $result;
    }
    /**
     * Get all available attribute groups
     *
     * @param int $idLang Language id
     *
     * @return array Attribute groups
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_attributes_groups($id_lang)
    {
        if (!Combination::is_feature_active()) {
            return [];
        }
        $sql = 'SELECT ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, agl.`public_name` AS public_group_name,
					a.`id_attribute`, al.`name` AS attribute_name, a.`color` AS attribute_color, product_attribute_shop.`id_product_attribute`,
					IFNULL(stock.quantity, 0) AS quantity, product_attribute_shop.`price`, product_attribute_shop.`ecotax`, product_attribute_shop.`weight`,
					product_attribute_shop.`default_on`, pa.`reference`, product_attribute_shop.`unit_price_impact`,
					product_attribute_shop.`minimal_quantity`, product_attribute_shop.`available_date`, ag.`group_type`,
					product_attribute_shop.`width`, product_attribute_shop.`height`, product_attribute_shop.`depth`
				FROM `' . _DB_PREFIX_ . 'product_attribute` pa
				' . Shop::add_sql_association('product_attribute', 'pa') . '
				' . static::sql_stock('pa', 'pa') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON (a.`id_attribute` = pac.`id_attribute`)
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON (ag.`id_attribute_group` = a.`id_attribute_group`)
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute`)
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group`)
				' . Shop::add_sql_association('attribute', 'a') . '
				WHERE pa.`id_product` = ' . (int) $this->id . '
					AND al.`id_lang` = ' . (int) $id_lang . '
					AND agl.`id_lang` = ' . (int) $id_lang . '
				GROUP BY id_attribute_group, id_product_attribute
				ORDER BY ag.`position` ASC, a.`position` ASC, agl.`name` ASC';
        return Db::read_only()->get_array($sql);
    }
    /**
     * Get product accessories
     *
     * @param int $idLang Language id
     * @param bool $active
     *
     * @return array|false Product accessories
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_accessories($id_lang, $active = true)
    {
        $sql = 'SELECT p.*, product_shop.*, stock.out_of_stock, IFNULL(stock.quantity, 0) as quantity, pl.`description`, pl.`description_short`, pl.`link_rewrite`,
					pl.`meta_description`, pl.`meta_keywords`, pl.`meta_title`, pl.`name`, pl.`available_now`, pl.`available_later`,
					image_shop.`id_image` id_image, il.`legend`, m.`name` as manufacturer_name, cl.`name` AS category_default, IFNULL(product_attribute_shop.id_product_attribute, 0) id_product_attribute,
					DATEDIFF(
						p.`date_add`,
						DATE_SUB(
							"' . date('Y-m-d') . ' 00:00:00",
							INTERVAL ' . (Validate::is_unsigned_int(Configuration::get('PS_NB_DAYS_NEW_PRODUCT')) ? Configuration::get('PS_NB_DAYS_NEW_PRODUCT') : 20) . ' DAY
						)
					) > 0 AS new
				FROM `' . _DB_PREFIX_ . 'accessory`
				LEFT JOIN `' . _DB_PREFIX_ . 'product` p ON p.`id_product` = `id_product_2`
				' . Shop::add_sql_association('product', 'p') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` product_attribute_shop
					ON (p.`id_product` = product_attribute_shop.`id_product` AND product_attribute_shop.`default_on` = 1 AND product_attribute_shop.id_shop=' . (int) $this->id_shop . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (
					p.`id_product` = pl.`id_product`
					AND pl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl') . '
				)
				LEFT JOIN `' . _DB_PREFIX_ . 'category_lang` cl ON (
					product_shop.`id_category_default` = cl.`id_category`
					AND cl.`id_lang` = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('cl') . '
				)
				LEFT JOIN `' . _DB_PREFIX_ . 'image_shop` image_shop
					ON (image_shop.`id_product` = p.`id_product` AND image_shop.cover=1 AND image_shop.id_shop=' . (int) $this->id_shop . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (image_shop.`id_image` = il.`id_image` AND il.`id_lang` = ' . (int) $id_lang . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'manufacturer` m ON (p.`id_manufacturer`= m.`id_manufacturer`)
				' . static::sql_stock('p', 0) . '
				WHERE `id_product_1` = ' . (int) $this->id . ($active ? ' AND product_shop.`active` = 1 AND product_shop.`visibility` != \'none\'' : '') . '
				GROUP BY product_shop.id_product';
        if (!$result = Db::read_only()->get_array($sql)) {
            return false;
        }
        foreach ($result as &$row) {
            $row['id_product_attribute'] = static::get_default_attribute((int) $row['id_product']);
        }
        return static::get_products_properties($id_lang, $result);
    }
    /**
     * Link accessories with product
     *
     * @param array $accessoriesId Accessories ids
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function change_accessories($accessories_id): void
    {
        foreach ($accessories_id as $id_product2) {
            Db::get_instance()->insert('accessory', ['id_product_1' => (int) $this->id, 'id_product_2' => (int) $id_product2]);
        }
    }
    /**
     * Add new feature to product
     *
     * @param int $idValue
     * @param int $idLang
     * @param string $cust
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_features_custom_to_db($id_value, $id_lang, $cust)
    {
        $row = ['id_feature_value' => (int) $id_value, 'id_lang' => (int) $id_lang, 'value' => p_sql($cust)];
        return Db::get_instance()->insert('feature_value_lang', $row);
    }
    /**
     * @param int $featureValueId
     * @param int $langId
     * @param string $displayable
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_features_displayable_to_db($feature_value_id, $lang_id, $displayable)
    {
        if (!$displayable = p_sql($displayable)) {
            return false;
        }
        return Db::get_instance()->insert('feature_product_lang', ['id_product' => (int) $this->id, 'id_feature_value' => (int) $feature_value_id, 'id_lang' => (int) $lang_id, 'displayable' => $displayable]);
    }
    /**
     * Get the link of the product page of this product
     *
     *
     * @return string
     * @throws PrestaShopException
     */
    public function get_link(?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        return $context->link->get_product_link($this);
    }
    /**
     * @param int $idLang
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_tags($id_lang)
    {
        if (!$this->is_fully_loaded && is_null($this->tags)) {
            $this->tags = Tag::get_product_tags($this->id);
        }
        if (!($this->tags && array_key_exists($id_lang, $this->tags))) {
            return '';
        }
        $result = '';
        foreach ($this->tags[$id_lang] as $tag_name) {
            $result .= $tag_name . ', ';
        }
        return rtrim($result, ', ');
    }
    /**
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_front_features($id_lang)
    {
        return static::get_front_features_static($id_lang, $this->id);
    }
    /**
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_attachments($id_lang)
    {
        return static::get_attachments_static($id_lang, $this->id);
    }
    /**
     * @param int $uploadableFiles
     * @param int $textFields
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function create_labels($uploadable_files, $text_fields)
    {
        $languages = Language::get_languages();
        if ((int) $uploadable_files > 0) {
            for ($i = 0; $i < (int) $uploadable_files; $i++) {
                if (!$this->_create_label($languages, static::CUSTOMIZE_FILE)) {
                    return false;
                }
            }
        }
        if ((int) $text_fields > 0) {
            for ($i = 0; $i < (int) $text_fields; $i++) {
                if (!$this->_create_label($languages, static::CUSTOMIZE_TEXTFIELD)) {
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * @param array $languages
     * @param int $type
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function _create_label($languages, $type)
    {
        // Label insertion
        $conn = Db::get_instance();
        if (!$conn->execute('
			INSERT INTO `' . _DB_PREFIX_ . 'customization_field` (`id_product`, `type`, `required`)
			VALUES (' . (int) $this->id . ', ' . (int) $type . ', 0)') || !$id_customization_field = (int) $conn->Insert_ID()) {
            return false;
        }
        // Multilingual label name creation
        $values = '';
        foreach ($languages as $language) {
            foreach (Shop::get_context_list_shop_id() as $id_shop) {
                $values .= '(' . (int) $id_customization_field . ', ' . (int) $language['id_lang'] . ', ' . $id_shop . ',\'\'), ';
            }
        }
        $values = rtrim($values, ', ');
        if (!$conn->execute('
			INSERT INTO `' . _DB_PREFIX_ . 'customization_field_lang` (`id_customization_field`, `id_lang`, `id_shop`, `name`)
			VALUES ' . $values)) {
            return false;
        }
        // Set cache of feature detachable to true
        Configuration::update_global_value('PS_CUSTOMIZATION_FEATURE_ACTIVE', '1');
        return true;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function update_labels()
    {
        $has_required_fields = 0;
        $conn = Db::get_instance();
        foreach ($_POST as $field => $value) {
            /* Label update */
            if (str_starts_with((string) $field, 'label_')) {
                if (!$tmp = $this->_check_label_field($field, $value)) {
                    return false;
                }
                /* Multilingual label name update */
                foreach (Shop::get_context_list_shop_id() as $id_shop) {
                    if (!$conn->execute('INSERT INTO `' . _DB_PREFIX_ . 'customization_field_lang`
                    (`id_customization_field`, `id_lang`, `id_shop`, `name`) VALUES (' . (int) $tmp[2] . ', ' . (int) $tmp[3] . ', ' . $id_shop . ', \'' . p_sql($value) . '\')
                    ON DUPLICATE KEY UPDATE `name` = \'' . p_sql($value) . '\'')) {
                        return false;
                    }
                }
                $is_required = isset($_POST['require_' . (int) $tmp[1] . '_' . (int) $tmp[2]]) ? 1 : 0;
                $has_required_fields |= $is_required;
                /* Require option update */
                if (!$conn->execute('UPDATE `' . _DB_PREFIX_ . 'customization_field`
					SET `required` = ' . (int) $is_required . '
					WHERE `id_customization_field` = ' . (int) $tmp[2])) {
                    return false;
                }
            }
        }
        if ($has_required_fields && !Object_Model::update_multishop_table('product', ['customizable' => 2], 'a.id_product = ' . (int) $this->id)) {
            return false;
        }
        if (!$this->_delete_old_labels()) {
            return false;
        }
        return true;
    }
    /**
     * @param string $field
     * @param string $value
     *
     * @return array|false
     */
    protected function _check_label_field($field, $value)
    {
        if (!Validate::is_label($value)) {
            return false;
        }
        $tmp = explode('_', $field);
        if (count($tmp) < 4) {
            return false;
        }
        return $tmp;
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function _delete_old_labels()
    {
        $max = [static::CUSTOMIZE_FILE => (int) $this->uploadable_files, static::CUSTOMIZE_TEXTFIELD => (int) $this->text_fields];
        /* Get customization field ids */
        $result = Db::read_only()->get_array('SELECT `id_customization_field`, `type`
			FROM `' . _DB_PREFIX_ . 'customization_field`
			WHERE `id_product` = ' . (int) $this->id . '
			ORDER BY `id_customization_field`');
        if (empty($result)) {
            return true;
        }
        $customization_fields = [static::CUSTOMIZE_FILE => [], static::CUSTOMIZE_TEXTFIELD => []];
        foreach ($result as $row) {
            $customization_fields[(int) $row['type']][] = (int) $row['id_customization_field'];
        }
        $extra_file = count($customization_fields[static::CUSTOMIZE_FILE]) - $max[static::CUSTOMIZE_FILE];
        $extra_text = count($customization_fields[static::CUSTOMIZE_TEXTFIELD]) - $max[static::CUSTOMIZE_TEXTFIELD];
        /* If too much inside the database, deletion */
        $conn = Db::get_instance();
        if ($extra_file > 0 && count($customization_fields[static::CUSTOMIZE_FILE]) - $extra_file >= 0 && !$conn->execute('DELETE `' . _DB_PREFIX_ . 'customization_field`,`' . _DB_PREFIX_ . 'customization_field_lang`
			FROM `' . _DB_PREFIX_ . 'customization_field` JOIN `' . _DB_PREFIX_ . 'customization_field_lang`
			WHERE `' . _DB_PREFIX_ . 'customization_field`.`id_product` = ' . (int) $this->id . '
			AND `' . _DB_PREFIX_ . 'customization_field`.`type` = ' . static::CUSTOMIZE_FILE . '
			AND `' . _DB_PREFIX_ . 'customization_field_lang`.`id_customization_field` = `' . _DB_PREFIX_ . 'customization_field`.`id_customization_field`
			AND `' . _DB_PREFIX_ . 'customization_field`.`id_customization_field` >= ' . $customization_fields[static::CUSTOMIZE_FILE][count($customization_fields[static::CUSTOMIZE_FILE]) - $extra_file])) {
            return false;
        }
        if ($extra_text > 0 && count($customization_fields[static::CUSTOMIZE_TEXTFIELD]) - $extra_text >= 0 && !$conn->execute('DELETE `' . _DB_PREFIX_ . 'customization_field`,`' . _DB_PREFIX_ . 'customization_field_lang`
			FROM `' . _DB_PREFIX_ . 'customization_field` JOIN `' . _DB_PREFIX_ . 'customization_field_lang`
			WHERE `' . _DB_PREFIX_ . 'customization_field`.`id_product` = ' . (int) $this->id . '
			AND `' . _DB_PREFIX_ . 'customization_field`.`type` = ' . static::CUSTOMIZE_TEXTFIELD . '
			AND `' . _DB_PREFIX_ . 'customization_field_lang`.`id_customization_field` = `' . _DB_PREFIX_ . 'customization_field`.`id_customization_field`
			AND `' . _DB_PREFIX_ . 'customization_field`.`id_customization_field` >= ' . $customization_fields[static::CUSTOMIZE_TEXTFIELD][count($customization_fields[static::CUSTOMIZE_TEXTFIELD]) - $extra_text])) {
            return false;
        }
        // Refresh cache of feature detachable
        Configuration::update_global_value('PS_CUSTOMIZATION_FEATURE_ACTIVE', Customization::is_currently_used());
        return true;
    }
    /**
     * @param int|bool $idLang
     * @param int|null $idShop
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_customization_fields($id_lang = false, $id_shop = null)
    {
        if (!Customization::is_feature_active()) {
            return false;
        }
        if (Shop::is_feature_active() && !$id_shop) {
            $id_shop = (int) Context::get_context()->shop->id;
        }
        if (!$result = Db::read_only()->get_array('
			SELECT cf.`id_customization_field`, cf.`type`, cf.`required`, cfl.`name`, cfl.`id_lang`
			FROM `' . _DB_PREFIX_ . 'customization_field` cf
			NATURAL JOIN `' . _DB_PREFIX_ . 'customization_field_lang` cfl
			WHERE cf.`id_product` = ' . (int) $this->id . ($id_lang ? ' AND cfl.`id_lang` = ' . (int) $id_lang : '') . ($id_shop ? ' AND cfl.`id_shop` = ' . $id_shop : '') . '
			ORDER BY cf.`id_customization_field`')) {
            return false;
        }
        if ($id_lang) {
            return $result;
        }
        $customization_fields = [];
        foreach ($result as $row) {
            $customization_fields[(int) $row['type']][(int) $row['id_customization_field']][(int) $row['id_lang']] = $row;
        }
        return $customization_fields;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_customization_field_ids()
    {
        if (!Customization::is_feature_active()) {
            return [];
        }
        return Db::read_only()->get_array('
			SELECT `id_customization_field`, `type`, `required`
			FROM `' . _DB_PREFIX_ . 'customization_field`
			WHERE `id_product` = ' . (int) $this->id);
    }
    /**
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function has_all_required_customizable_fields(?Context $context = null)
    {
        if (!Customization::is_feature_active()) {
            return true;
        }
        if (!$context) {
            $context = Context::get_context();
        }
        $fields = $context->cart->get_product_customization($this->id, null, true);
        $required_fields = $this->get_required_customizable_fields();
        $fields_present = [];
        foreach ($fields as $field) {
            $fields_present[] = ['id_customization_field' => $field['index'], 'type' => $field['type']];
        }
        foreach ($required_fields as $required_field) {
            if (!in_array($required_field, $fields_present)) {
                return false;
            }
        }
        return true;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_required_customizable_fields()
    {
        if (!Customization::is_feature_active()) {
            return [];
        }
        return static::get_required_customizable_fields_static($this->id);
    }
    /**
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_no_pack_price()
    {
        return Pack::no_pack_price((int) $this->id);
    }
    /**
     * @param int $idCustomer
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function check_access($id_customer)
    {
        return static::check_access_static((int) $this->id, (int) $id_customer);
    }
    /**
     * @param int $idProduct
     * @param int $idCustomer
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public static function check_access_static($id_product, $id_customer)
    {
        if (!Group::is_feature_active()) {
            return true;
        }
        $cache_id = 'Product::checkAccess_' . (int) $id_product . '-' . (int) $id_customer . (!$id_customer ? '-' . (int) Group::get_current()->id : '');
        if (!Cache::is_stored($cache_id)) {
            $connection = Db::read_only();
            if (!$id_customer) {
                $result = (bool) $connection->get_value('
				SELECT ctg.`id_group`
				FROM `' . _DB_PREFIX_ . 'category_product` cp
				INNER JOIN `' . _DB_PREFIX_ . 'category_group` ctg ON (ctg.`id_category` = cp.`id_category`)
				WHERE cp.`id_product` = ' . (int) $id_product . ' AND ctg.`id_group` = ' . (int) Group::get_current()->id);
            } else {
                $result = (bool) $connection->get_value('
				SELECT cg.`id_group`
				FROM `' . _DB_PREFIX_ . 'category_product` cp
				INNER JOIN `' . _DB_PREFIX_ . 'category_group` ctg ON (ctg.`id_category` = cp.`id_category`)
				INNER JOIN `' . _DB_PREFIX_ . 'customer_group` cg ON (cg.`id_group` = ctg.`id_group`)
				WHERE cp.`id_product` = ' . (int) $id_product . ' AND cg.`id_customer` = ' . (int) $id_customer);
            }
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Add a stock movement for current product
     *
     * Since 1.5, this method only permit to add/remove available quantities of the current product in the current shop
     *
     * @param int $quantity
     * @param int $idReason - useless
     * @param int|null $idProductAttribute
     * @param int|null $idOrder - DEPRECATED
     * @param int|null $idEmployee - DEPRECATED
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @see StockManager if you want to manage real stock
     * @see StockAvailable if you want to manage available quantities for sale on your shop(s)
     *
     * @deprecated since 1.5.0
     */
    public function add_stock_mvt($quantity, $id_reason, $id_product_attribute = null, $id_order = null, $id_employee = null)
    {
        if (!$this->id || !$id_reason) {
            return false;
        }
        if ($id_product_attribute == null) {
            $id_product_attribute = 0;
        }
        $reason = new Stock_Mvt_Reason((int) $id_reason);
        if (!Validate::is_loaded_object($reason)) {
            return false;
        }
        $quantity = abs((int) $quantity) * $reason->sign;
        return Stock_Available::update_quantity($this->id, $id_product_attribute, $quantity);
    }
    /**
     * @param int $idLang
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.0.0
     */
    public function get_stock_mvts($id_lang)
    {
        Tools::display_as_deprecated();
        return Db::read_only()->get_array('
			SELECT sm.id_stock_mvt, sm.date_add, sm.quantity, sm.id_order,
			CONCAT(pl.name, \' \', GROUP_CONCAT(IFNULL(al.name, \'\'), \'\')) product_name, CONCAT(e.lastname, \' \', e.firstname) employee, mrl.name reason
			FROM `' . _DB_PREFIX_ . 'stock_mvt` sm
			LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl ON (
				sm.id_product = pl.id_product
				AND pl.id_lang = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('pl') . '
			)
			LEFT JOIN `' . _DB_PREFIX_ . 'stock_mvt_reason_lang` mrl ON (
				sm.id_stock_mvt_reason = mrl.id_stock_mvt_reason
				AND mrl.id_lang = ' . (int) $id_lang . '
			)
			LEFT JOIN `' . _DB_PREFIX_ . 'employee` e ON (
				e.id_employee = sm.id_employee
			)
			LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON (
				pac.id_product_attribute = sm.id_product_attribute
			)
			LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (
				al.id_attribute = pac.id_attribute
				AND al.id_lang = ' . (int) $id_lang . '
			)
			WHERE sm.id_product=' . (int) $this->id . '
			GROUP BY sm.id_stock_mvt
		');
    }
    /**
     * @return int
     */
    public function get_id_tax_rules_group()
    {
        return $this->id_tax_rules_group;
    }
    /**
     * Webservice getter : get product features association
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_product_features()
    {
        $rows = $this->get_features();
        foreach ($rows as $keyrow => $row) {
            foreach ($row as $keyfeature => $feature) {
                if ($keyfeature == 'id_feature') {
                    $rows[$keyrow]['id'] = $feature;
                    unset($rows[$keyrow]['id_feature']);
                }
                unset($rows[$keyrow]['id_product']);
                unset($rows[$keyrow]['custom']);
            }
            asort($rows[$keyrow]);
        }
        return $rows;
    }
    /**
     * Select all features for the object
     *
     * @return array Array with feature product's data
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_features()
    {
        return static::get_features_static((int) $this->id);
    }
    /**
     * @param int $idProduct
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_features_static($id_product)
    {
        if (!Feature::is_feature_active()) {
            return [];
        }
        if (!array_key_exists($id_product, static::$_cache_features)) {
            static::$_cache_features[$id_product] = Db::read_only()->get_array('
				SELECT fp.id_feature, fp.id_product, fp.id_feature_value, custom
				FROM `' . _DB_PREFIX_ . 'feature_product` fp
				LEFT JOIN `' . _DB_PREFIX_ . 'feature_value` fv ON (fp.id_feature_value = fv.id_feature_value)
				WHERE `id_product` = ' . (int) $id_product);
        }
        return static::$_cache_features[$id_product];
    }
    /**
     * Webservice setter : set product features association
     *
     * @param array $productFeatures Product Feature ids
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function set_ws_product_features($product_features)
    {
        Db::get_instance()->delete('feature_product', 'id_product = ' . (int) $this->id);
        foreach ($product_features as $product_feature) {
            if (isset($product_feature['id']) && (int) $product_feature['id'] && isset($product_feature['id_feature_value']) && (int) $product_feature['id_feature_value']) {
                $this->add_features_to_db((int) $product_feature['id'], (int) $product_feature['id_feature_value']);
            }
        }
        return true;
    }
    /**
     * @param int $id_feature
     * @param int $id_feature_value
     * @param bool $createCustomValue Deprecated
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_features_to_db($id_feature, $id_feature_value, $create_custom_value = null)
    {
        $id_feature = (int) $id_feature;
        if (!is_null($create_custom_value)) {
            Tools::display_parameter_as_deprecated('createCustomValue');
        }
        $id_feature_value = $create_custom_value ? 0 : (int) $id_feature_value;
        // Just to be 100% backward compatible
        $conn = Db::get_instance();
        if (!$id_feature_value) {
            $row = ['id_feature' => $id_feature, 'custom' => 0, 'position' => (int) Feature_Value::get_highest_position($id_feature) + 1];
            $conn->insert('feature_value', $row);
            $id_feature_value = (int) $conn->Insert_ID();
        }
        if ($id_feature && $id_feature_value) {
            $row = ['id_feature' => $id_feature, 'id_product' => (int) $this->id, 'id_feature_value' => $id_feature_value];
            $conn->insert('feature_product', $row);
            Specific_Price_Rule::apply_all_rules([(int) $this->id]);
        }
        return $id_feature_value;
    }
    /**
     * Webservice getter : get virtual field default combination
     *
     * @return int
     *
     * @throws PrestaShopException
     */
    public function get_ws_default_combination()
    {
        return static::get_default_attribute($this->id);
    }
    /**
     * Webservice setter : set virtual field default combination
     *
     * @param string $idCombination id default combination
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function set_ws_default_combination($id_combination)
    {
        $this->delete_default_attributes();
        return $this->set_default_attribute((int) $id_combination);
    }
    /**
     * Del all default attributes for product
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function delete_default_attributes()
    {
        $id = (int) $this->id;
        if ($id) {
            $conn = Db::get_instance();
            $res = Object_Model::update_multishop_table('Combination', ['default_on' => null], 'a.`id_product` = ' . $id);
            return $conn->update('product_attribute', ['default_on' => null], "id_product = {$id}", 0, true) && $res;
        }
        return false;
    }
    /**
     * @param int $idProductAttribute
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function set_default_attribute($id_product_attribute)
    {
        $result = Object_Model::update_multishop_table('Combination', ['default_on' => 1], 'a.`id_product` = ' . (int) $this->id . ' AND a.`id_product_attribute` = ' . (int) $id_product_attribute);
        $result = Object_Model::update_multishop_table('product', ['cache_default_attribute' => (int) $id_product_attribute], 'a.`id_product` = ' . (int) $this->id) && $result;
        $this->cache_default_attribute = (int) $id_product_attribute;
        return $result;
    }
    /**
     * Webservice getter : get category ids of current product for association
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_categories()
    {
        return Db::read_only()->get_array((new Db_Query())->select('cp.`id_category` AS `id`')->from('category_product', 'cp')->left_join('category', 'c', 'c.`id_category` = cp.`id_category`')->join(Shop::add_sql_association('category', 'c'))->where('cp.`id_product` = ' . (int) $this->id));
    }
    /**
     * Webservice setter : set category ids of current product for association
     *
     * @param array $categories category description arrays
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function set_ws_categories($categories)
    {
        $ids = array_filter(array_map(intval(...), array_column($categories, 'id')));
        if ($ids) {
            $result = $this->update_categories($ids);
        } else {
            $result = $this->delete_categories(true);
        }
        Hook::trigger_event('updateProduct', ['id_product' => (int) $this->id]);
        return $result;
    }
    /**
     * Webservice getter : get product accessories ids of current product for association
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_accessories()
    {
        return Db::read_only()->get_array((new Db_Query())->select('p.`id_product` AS `id`')->from('accessory', 'a')->left_join('product', 'p', 'p.`id_product` = a.`id_product_2`')->join(Shop::add_sql_association('product', 'p'))->where('a.`id_product_1` = ' . (int) $this->id));
    }
    /**
     * Webservice setter : set product accessories ids of current product for association
     *
     * @param array $accessories product ids
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function set_ws_accessories($accessories)
    {
        $this->delete_accessories();
        $id = (int) $this->id;
        foreach ($accessories as $accessory) {
            if (isset($accessory['id']) && (int) $accessory['id']) {
                $accessory_id = (int) $accessory['id'];
                Db::get_instance()->insert('accessory', ['id_product_1' => $id, 'id_product_2' => $accessory_id]);
            }
        }
        return true;
    }
    /**
     * Webservice getter : get combination ids of current product for association
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_combinations()
    {
        return Db::read_only()->get_array('SELECT pa.`id_product_attribute` AS id
			FROM `' . _DB_PREFIX_ . 'product_attribute` pa
			' . Shop::add_sql_association('product_attribute', 'pa') . '
			WHERE pa.`id_product` = ' . (int) $this->id);
    }
    /**
     * Webservice setter : set combination ids of current product for association
     *
     * @param array $combinations combination ids
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function set_ws_combinations($combinations)
    {
        // No hook exec
        $ids_new = [];
        foreach ($combinations as $combination) {
            if (isset($combination['id']) && (int) $combination['id']) {
                $ids_new[] = (int) $combination['id'];
            }
        }
        $conn = Db::get_instance();
        $ids_orig = [];
        $original = $conn->get_array('SELECT pa.`id_product_attribute` AS id
			FROM `' . _DB_PREFIX_ . 'product_attribute` pa
			' . Shop::add_sql_association('product_attribute', 'pa') . '
			WHERE pa.`id_product` = ' . (int) $this->id);
        foreach ($original as $id) {
            $ids_orig[] = $id['id'];
        }
        $all_ids = [];
        $all = $conn->get_array('SELECT pa.`id_product_attribute` AS id FROM `' . _DB_PREFIX_ . 'product_attribute` pa ' . Shop::add_sql_association('product_attribute', 'pa'));
        foreach ($all as $id) {
            $all_ids[] = $id['id'];
        }
        $to_add = [];
        foreach ($ids_new as $id) {
            if (!in_array($id, $ids_orig)) {
                $to_add[] = $id;
            }
        }
        $to_delete = [];
        foreach ($ids_orig as $id) {
            if (!in_array($id, $ids_new)) {
                $to_delete[] = $id;
            }
        }
        // Delete rows
        foreach ($to_delete as $id) {
            $combination = new Combination($id);
            $combination->delete();
        }
        foreach ($to_add as $id) {
            // Update id_product if exists else create
            if (in_array($id, $all_ids)) {
                $conn->execute('UPDATE `' . _DB_PREFIX_ . 'product_attribute` SET id_product = ' . (int) $this->id . ' WHERE id_product_attribute=' . $id);
            } else {
                $conn->execute('INSERT INTO `' . _DB_PREFIX_ . 'product_attribute` (`id_product`) VALUES (' . $this->id . ')');
            }
        }
        return true;
    }
    /**
     * Webservice getter : get product option ids of current product for association
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_product_option_values()
    {
        return Db::read_only()->get_array('SELECT DISTINCT pac.id_attribute AS id
			FROM `' . _DB_PREFIX_ . 'product_attribute` pa
			' . Shop::add_sql_association('product_attribute', 'pa') . '
			LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON (pac.id_product_attribute = pa.id_product_attribute)
			WHERE pa.id_product = ' . (int) $this->id);
    }
    /**
     * Webservice setter : set virtual field position in category
     *
     * @param string $position
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_ws_position_in_category($position)
    {
        $position = (int) $position;
        if ($position < 0) {
            Webservice_Request::get_instance()->set_error(500, 'You cannot set a negative position, the minimum for a position is 0.', 134);
        }
        $conn = Db::get_instance();
        $result = $conn->get_array('
			SELECT `id_product`
			FROM `' . _DB_PREFIX_ . 'category_product`
			WHERE `id_category` = ' . (int) $this->id_category_default . '
			ORDER BY `position`
		');
        if ($position > 0 && $position + 1 > count($result)) {
            Webservice_Request::get_instance()->set_error(500, 'You cannot set a position greater than the total number of products in the category, minus 1 (position numbering starts at 0).', 135);
        }
        foreach ($result as &$value) {
            $value = $value['id_product'];
        }
        $current_position = $this->get_ws_position_in_category();
        if ($current_position && isset($result[$current_position])) {
            $save = $result[$current_position];
            unset($result[$current_position]);
            array_splice($result, $position, 0, $save);
        }
        foreach ($result as $position => $id_product) {
            $conn->update('category_product', ['position' => $position], '`id_category` = ' . (int) $this->id_category_default . ' AND `id_product` = ' . (int) $id_product);
        }
        return true;
    }
    /**
     * Webservice getter : get virtual field position in category
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_position_in_category()
    {
        $result = Db::read_only()->get_array('SELECT position
			FROM `' . _DB_PREFIX_ . 'category_product`
			WHERE id_category = ' . (int) $this->id_category_default . '
			AND id_product = ' . (int) $this->id);
        if (count($result) > 0) {
            return (int) $result[0]['position'];
        }
        return 0;
    }
    /**
     * Webservice getter : get virtual field id_default_image in category
     *
     * @return int
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_cover_ws()
    {
        $result = static::get_cover($this->id);
        return $result['id_image'] ?? null;
    }
    /**
     * Get product cover image
     *
     * @param int $idProduct
     *
     * @return array|false Product cover image
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_cover($id_product, ?Context $context = null)
    {
        if (!$context) {
            $context = Context::get_context();
        }
        $cache_id = 'Product::getCover_' . (int) $id_product . '-' . (int) $context->shop->id;
        if (!Cache::is_stored($cache_id)) {
            $sql = 'SELECT image_shop.`id_image`
					FROM `' . _DB_PREFIX_ . 'image` i
					' . Shop::add_sql_association('image', 'i') . '
					WHERE i.`id_product` = ' . (int) $id_product . '
					AND image_shop.`cover` = 1';
            $result = Db::read_only()->get_row($sql);
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
    /**
     * Webservice setter : set virtual field id_default_image in category
     *
     * @param string $idImage
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function set_cover_ws($id_image)
    {
        $id_image = (int) $id_image;
        $conn = Db::get_instance();
        $conn->execute('UPDATE `' . _DB_PREFIX_ . 'image_shop` image_shop, `' . _DB_PREFIX_ . 'image` i
			SET image_shop.`cover` = NULL
			WHERE i.`id_product` = ' . (int) $this->id . ' AND i.id_image = image_shop.id_image
			AND image_shop.id_shop=' . (int) Context::get_context()->shop->id);
        $conn->execute('UPDATE `' . _DB_PREFIX_ . 'image_shop`
			SET `cover` = 1 WHERE `id_image` = ' . $id_image);
        return true;
    }
    /**
     * Webservice getter : get image ids of current product for association
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_images()
    {
        return Db::read_only()->get_array('
		SELECT i.`id_image` AS id
		FROM `' . _DB_PREFIX_ . 'image` i
		' . Shop::add_sql_association('image', 'i') . '
		WHERE i.`id_product` = ' . (int) $this->id . '
		ORDER BY i.`position`');
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_stock_availables()
    {
        return Db::read_only()->get_array('SELECT `id_stock_available` id, `id_product_attribute`
														FROM `' . _DB_PREFIX_ . 'stock_available`
														WHERE `id_product`=' . $this->id . Stock_Available::add_sql_shop_restriction());
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_tags()
    {
        return Db::read_only()->get_array('
		SELECT `id_tag` AS id
		FROM `' . _DB_PREFIX_ . 'product_tag`
		WHERE `id_product` = ' . (int) $this->id);
    }
    /**
     * Webservice setter : set tag ids of current product for association
     *
     * @param array $tagIds tag ids
     *
     * @return bool
     * @throws PrestaShopException
     */
    public function set_ws_tags($tag_ids)
    {
        $ids = [];
        foreach ($tag_ids as $value) {
            if (isset($value['id']) && (int) $value['id']) {
                $ids[] = (int) $value['id'];
            }
        }
        if ($this->delete_ws_tags()) {
            if ($ids) {
                $conn = Db::get_instance();
                $sql_values = [];
                foreach ($ids as $id) {
                    $id_lang = (int) $conn->get_value('SELECT `id_lang` FROM `' . _DB_PREFIX_ . 'tag` WHERE `id_tag`=' . $id);
                    if ($id_lang) {
                        $sql_values[] = '(' . (int) $this->id . ', ' . $id . ', ' . (int) $id_lang . ')';
                    }
                }
                if ($sql_values) {
                    return $conn->execute('
                        INSERT INTO `' . _DB_PREFIX_ . 'product_tag` (`id_product`, `id_tag`, `id_lang`)
                        VALUES ' . implode(',', $sql_values));
                }
            }
        }
        return true;
    }
    /**
     * Delete products tags entries without delete tags for webservice usage
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_ws_tags()
    {
        return Db::get_instance()->delete('product_tag', 'id_product = ' . (int) $this->id);
    }
    /**
     * @return string
     *
     * @throws PrestaShopException
     */
    public function get_ws_manufacturer_name()
    {
        return Manufacturer::get_name_by_id((int) $this->id_manufacturer);
    }
    /**
     * Checks if reference exists
     *
     * @param string $reference
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function exists_ref_in_database($reference)
    {
        $row = Db::read_only()->get_row('
		SELECT `reference`
		FROM `' . _DB_PREFIX_ . 'product` p
		WHERE p.reference = "' . p_sql($reference) . '"');
        return isset($row['reference']);
    }
    /**
     * Get the combination url anchor of the product
     *
     * @param int $idProductAttribute
     *
     * @param bool $withId Deprecated
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_anchor($id_product_attribute, $with_id = false)
    {
        return Context::get_context()->link->get_combination_hash_url($this->id, $id_product_attribute);
    }
    /**
     * Get label by lang and value by lang too
     *
     * @todo    Remove existing module condition
     *
     * @param int $idProduct
     * @param int $idProductAttribute
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_attributes_params($id_product, $id_product_attribute)
    {
        $id_lang = (int) Context::get_context()->language->id;
        $id_shop = (int) Context::get_context()->shop->id;
        $cache_id = 'Product::getAttributesParams_' . (int) $id_product . '-' . (int) $id_product_attribute . '-' . $id_lang . '-' . $id_shop;
        // if blocklayered module is installed we check if user has set custom attribute name
        $conn = Db::read_only();
        if (Module::is_installed('blocklayered') && Module::is_enabled('blocklayered')) {
            $nb_custom_values = $conn->get_array('
			SELECT DISTINCT la.`id_attribute`, la.`url_name` AS `name`
			FROM `' . _DB_PREFIX_ . 'attribute` a
			LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
				ON (a.`id_attribute` = pac.`id_attribute`)
			LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
				ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
			' . Shop::add_sql_association('product_attribute', 'pa') . '
			LEFT JOIN `' . _DB_PREFIX_ . 'layered_indexable_attribute_lang_value` la
				ON (la.`id_attribute` = a.`id_attribute` AND la.`id_lang` = ' . $id_lang . ')
			WHERE la.`url_name` IS NOT NULL AND la.`url_name` != \'\'
			AND pa.`id_product` = ' . (int) $id_product . '
			AND pac.`id_product_attribute` = ' . (int) $id_product_attribute);
            if (!empty($nb_custom_values)) {
                $tab_id_attribute = [];
                foreach ($nb_custom_values as $attribute) {
                    $tab_id_attribute[] = $attribute['id_attribute'];
                    $group = $conn->get_array('
					SELECT a.`id_attribute`, g.`id_attribute_group`, g.`url_name` AS `group`
					FROM `' . _DB_PREFIX_ . 'layered_indexable_attribute_group_lang_value` g
					LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a
						ON (a.`id_attribute_group` = g.`id_attribute_group`)
					WHERE a.`id_attribute` = ' . (int) $attribute['id_attribute'] . '
					AND g.`id_lang` = ' . $id_lang . '
					AND g.`url_name` IS NOT NULL AND g.`url_name` != \'\'');
                    if (empty($group)) {
                        $group = $conn->get_array('
						SELECT g.`id_attribute_group`, g.`name` AS `group`
						FROM `' . _DB_PREFIX_ . 'attribute_group_lang` g
						LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a
							ON (a.`id_attribute_group` = g.`id_attribute_group`)
						WHERE a.`id_attribute` = ' . (int) $attribute['id_attribute'] . '
						AND g.`id_lang` = ' . $id_lang . '
						AND g.`name` IS NOT NULL');
                    }
                    $result[] = array_merge($attribute, $group[0]);
                }
                $values_not_custom = $conn->get_array('
				SELECT DISTINCT a.`id_attribute`, a.`id_attribute_group`, al.`name`, agl.`name` AS `group`
				FROM `' . _DB_PREFIX_ . 'attribute` a
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
					ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . $id_lang . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
					ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . $id_lang . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
					ON (a.`id_attribute` = pac.`id_attribute`)
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
					ON (pac.`id_product_attribute` = pa.`id_product_attribute`)
				' . Shop::add_sql_association('product_attribute', 'pa') . '
				WHERE pa.`id_product` = ' . (int) $id_product . '
				AND pac.id_product_attribute = ' . (int) $id_product_attribute . '
				AND a.`id_attribute` NOT IN(' . implode(', ', $tab_id_attribute) . ')');
                return array_merge($values_not_custom, $result);
            }
        }
        if (!Cache::is_stored($cache_id)) {
            $result = $conn->get_array('
			SELECT a.`id_attribute`, a.`id_attribute_group`, al.`name`, agl.`name` AS `group`
			FROM `' . _DB_PREFIX_ . 'attribute` a
			LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al
				ON (al.`id_attribute` = a.`id_attribute` AND al.`id_lang` = ' . $id_lang . ')
			LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac
				ON (pac.`id_attribute` = a.`id_attribute`)
			LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute` pa
				ON (pa.`id_product_attribute` = pac.`id_product_attribute`)
			' . Shop::add_sql_association('product_attribute', 'pa') . '
			LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl
				ON (a.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . $id_lang . ')
			WHERE pa.`id_product` = ' . (int) $id_product . '
				AND pac.`id_product_attribute` = ' . (int) $id_product_attribute . '
				AND agl.`id_lang` = ' . $id_lang);
            Cache::store($cache_id, $result);
        } else {
            $result = Cache::retrieve($cache_id);
        }
        return $result;
    }
    /**
     * @param bool $autodate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function add_ws($autodate = true, $null_values = false)
    {
        $success = $this->add($autodate, $null_values);
        if ($success && Configuration::get('PS_SEARCH_INDEXATION')) {
            Search::indexation(false, $this->id);
        }
        return $success;
    }
    /**
     * @param bool $autoDate
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add($auto_date = true, $null_values = false)
    {
        if (!parent::add($auto_date, $null_values)) {
            return false;
        }
        $id_shop_list = Shop::get_context_list_shop_id();
        if ($this->get_type() == static::PTYPE_VIRTUAL) {
            foreach ($id_shop_list as $value) {
                Stock_Available::set_product_out_of_stock((int) $this->id, Stock_Available::OUT_OF_STOCK_ALLOW, $value);
            }
            if ($this->active && !Configuration::get('PS_VIRTUAL_PROD_FEATURE_ACTIVE')) {
                Configuration::update_global_value('PS_VIRTUAL_PROD_FEATURE_ACTIVE', '1');
            }
        } else {
            foreach ($id_shop_list as $value) {
                Stock_Available::set_product_out_of_stock((int) $this->id, Stock_Available::OUT_OF_STOCK_SYSTEM_DEFAULT, $value);
            }
        }
        $this->set_group_reduction();
        Hook::trigger_event('actionProductSave', ['id_product' => (int) $this->id, 'product' => $this]);
        return true;
    }
    /**
     * @param bool $nullValues
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function update_ws($null_values = false)
    {
        $success = parent::update($null_values);
        if ($success && Configuration::get('PS_SEARCH_INDEXATION')) {
            Search::indexation(false, $this->id);
        }
        Hook::trigger_event('updateProduct', ['id_product' => (int) $this->id]);
        return $success;
    }
    /**
     * Get list of parent categories
     *
     * @param int $idLang
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_parent_categories($id_lang = null)
    {
        if (!$id_lang) {
            $id_lang = Context::get_context()->language->id;
        }
        $interval = Category::get_interval($this->id_category_default);
        if (is_array($interval)) {
            $sql = new Db_Query();
            $sql->from('category', 'c');
            $sql->left_join('category_lang', 'cl', 'c.id_category = cl.id_category AND id_lang = ' . (int) $id_lang . Shop::add_sql_restriction_on_lang('cl'));
            $sql->where('c.nleft <= ' . (int) $interval['nleft'] . ' AND c.nright >= ' . (int) $interval['nright']);
            $sql->order_by('c.nleft');
            return Db::read_only()->get_array($sql);
        }
        return [];
    }
    /**
     * @param bool $value
     *
     * @throws PrestaShopException
     */
    public function set_advanced_stock_management($value): void
    {
        $value = (bool) $value;
        $this->advanced_stock_management = $value;
        if (Context::get_context()->shop->get_context() == Shop::CONTEXT_GROUP && Context::get_context()->shop->get_context_shop_group()->share_stock == 1) {
            Db::get_instance()->execute('
				UPDATE `' . _DB_PREFIX_ . 'product_shop`
				SET `advanced_stock_management`=' . (int) $value . '
				WHERE id_product=' . (int) $this->id . Shop::add_sql_restriction());
        } else {
            $this->set_fields_to_update(['advanced_stock_management' => true]);
            $this->save();
        }
    }
    /**
     * get the default category according to the shop
     *
     * @throws PrestaShopException
     */
    public function get_default_category()
    {
        $default_category = Db::read_only()->get_value('
			SELECT product_shop.`id_category_default`
			FROM `' . _DB_PREFIX_ . 'product` p
			' . Shop::add_sql_association('product', 'p') . '
			WHERE p.`id_product` = ' . (int) $this->id);
        if (!$default_category) {
            return ['id_category_default' => Context::get_context()->shop->id_category];
        }
        return $default_category;
    }
    /**
     * @deprecated 1.0.0
     * @see Product::getAttributeCombinations()
     *
     * @param int $idLang
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_attribute_combinaisons($id_lang)
    {
        Tools::display_as_deprecated('Use Product::getAttributeCombinations($id_lang)');
        return $this->get_attribute_combinations($id_lang);
    }
    /**
     * Get all available product attributes combinations
     *
     * @param int $idLang Language id
     *
     * @return array Product attributes combinations
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_attribute_combinations($id_lang)
    {
        if (!Combination::is_feature_active()) {
            return [];
        }
        $sql = 'SELECT pa.*, product_attribute_shop.*, ag.`id_attribute_group`, ag.`is_color_group`, agl.`name` AS group_name, al.`name` AS attribute_name,
					a.`id_attribute`
				FROM `' . _DB_PREFIX_ . 'product_attribute` pa
				' . Shop::add_sql_association('product_attribute', 'pa') . '
				LEFT JOIN `' . _DB_PREFIX_ . 'product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute` a ON a.`id_attribute` = pac.`id_attribute`
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = ' . (int) $id_lang . ')
				LEFT JOIN `' . _DB_PREFIX_ . 'attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = ' . (int) $id_lang . ')
				WHERE pa.`id_product` = ' . (int) $this->id . '
				GROUP BY pa.`id_product_attribute`, ag.`id_attribute_group`
				ORDER BY pa.`id_product_attribute`';
        $res = Db::read_only()->get_array($sql);
        //Get quantity of each variations
        foreach ($res as $key => $row) {
            $cache_key = $row['id_product'] . '_' . $row['id_product_attribute'] . '_quantity';
            if (!Cache::is_stored($cache_key)) {
                Cache::store($cache_key, Stock_Available::get_quantity_available_by_product($row['id_product'], $row['id_product_attribute']));
            }
            $res[$key]['quantity'] = Cache::retrieve($cache_key);
        }
        return $res;
    }
    /**
     * @param int $idProductAttribute
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @deprecated 1.0.0
     * @see Product::deleteAttributeCombination()
     */
    public function delete_attribute_combinaison($id_product_attribute)
    {
        Tools::display_as_deprecated('Use Product::deleteAttributeCombination($id_product_attribute)');
        return $this->delete_attribute_combination($id_product_attribute);
    }
    /*
        Create the link rewrite if not exists or invalid on product creation
    */
    /**
     * Delete a product attributes combination
     *
     * @param int $idProductAttribute Product attribute id
     *
     * @return bool Deletion result
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete_attribute_combination($id_product_attribute)
    {
        if (!$this->id || !$id_product_attribute || !is_numeric($id_product_attribute)) {
            return false;
        }
        Hook::trigger_event('deleteProductAttribute', ['id_product_attribute' => $id_product_attribute, 'id_product' => $this->id, 'deleteAllAttributes' => false]);
        $combination = new Combination($id_product_attribute);
        $res = $combination->delete();
        Specific_Price_Rule::apply_all_rules([(int) $this->id]);
        return $res;
    }
    /**
     * @return string
     *
     * @throws PrestaShopException
     */
    public function get_ws_type()
    {
        $type_information = [static::PTYPE_SIMPLE => 'simple', static::PTYPE_PACK => 'pack', static::PTYPE_VIRTUAL => 'virtual'];
        return $type_information[$this->get_type()];
    }
    /**
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function modifier_ws_link_rewrite()
    {
        if (!$this->link_rewrite) {
            $this->link_rewrite = [];
        }
        foreach ($this->name as $id_lang => $name) {
            if (empty($this->link_rewrite[$id_lang])) {
                $this->link_rewrite[$id_lang] = Tools::link_rewrite($name);
            } elseif (!Validate::is_link_rewrite($this->link_rewrite[$id_lang])) {
                $this->link_rewrite[$id_lang] = Tools::link_rewrite($this->link_rewrite[$id_lang]);
            }
        }
        return true;
    }
    /**
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_ws_product_bundle()
    {
        $sql = (new Db_Query())->select('id_product_item AS id, quantity, NULLIF(id_product_attribute_item, 0) AS combination_id')->from('pack')->where('id_product_pack = ' . (int) $this->id);
        return Db::read_only()->get_array($sql);
    }
    /**
     * @param string $typeStr
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    public function set_ws_type($type_str)
    {
        $reverse_type_information = ['simple' => static::PTYPE_SIMPLE, 'pack' => static::PTYPE_PACK, 'virtual' => static::PTYPE_VIRTUAL];
        if (!isset($reverse_type_information[$type_str])) {
            return false;
        }
        $type = $reverse_type_information[$type_str];
        if (Pack::is_pack((int) $this->id) && $type != static::PTYPE_PACK) {
            Pack::delete_items($this->id);
        }
        $this->cache_is_pack = $type == static::PTYPE_PACK;
        $this->is_virtual = $type == static::PTYPE_VIRTUAL;
        return true;
    }
    /**
     * @param array $items
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_ws_product_bundle($items)
    {
        if ($this->is_virtual) {
            return false;
        }
        Pack::delete_items($this->id);
        foreach ($items as $item) {
            if (isset($item['id']) && (int) $item['id']) {
                Pack::add_item((int) $this->id, (int) $item['id'], isset($item['quantity']) ? (int) $item['quantity'] : 1, isset($item['combination_id']) ? (int) $item['combination_id'] : 0);
            }
        }
        return true;
    }
    /**
     * @param int $idAttribute
     * @param int $idShop
     *
     * @return false|null|string
     *
     * @throws PrestaShopException
     */
    public function is_color_unavailable($id_attribute, $id_shop)
    {
        return Db::read_only()->get_value('
			SELECT sa.id_product_attribute
			FROM ' . _DB_PREFIX_ . 'stock_available sa
			WHERE id_product=' . (int) $this->id . ' AND quantity <= 0
			' . Stock_Available::add_sql_shop_restriction(null, $id_shop, 'sa') . '
			AND EXISTS (
				SELECT 1
				FROM ' . _DB_PREFIX_ . 'product_attribute pa
				JOIN ' . _DB_PREFIX_ . 'product_attribute_shop product_attribute_shop
					ON (product_attribute_shop.id_product_attribute = pa.id_product_attribute AND product_attribute_shop.id_shop=' . (int) $id_shop . ')
				JOIN ' . _DB_PREFIX_ . 'product_attribute_combination pac
					ON (pac.id_product_attribute AND product_attribute_shop.id_product_attribute)
				WHERE sa.id_product_attribute = pa.id_product_attribute AND pa.id_product=' . (int) $this->id . ' AND pac.id_attribute=' . (int) $id_attribute . '
			)');
    }
    /**
     * @param TableSchema $table
     */
    public static function process_table_schema($table): void
    {
        if ($table->get_name_without_prefix() === 'product_lang') {
            $table->reorder_columns(['id_product', 'id_shop', 'id_lang']);
        }
        if ($table->get_name_without_prefix() === 'product_shop') {
            $table->reorder_columns(['id_product', 'id_shop', 'id_category_default', 'id_tax_rules_group', 'on_sale', 'online_only', 'ecotax', 'minimal_quantity', 'price', 'wholesale_price', 'unity', 'unit_price_ratio', 'additional_shipping_cost', 'customizable', 'uploadable_files', 'text_fields', 'active', 'redirect_type', 'id_product_redirected', 'available_for_order', 'available_date', 'condition', 'show_price', 'indexed', 'visibility', 'cache_default_attribute', 'advanced_stock_management', 'date_add', 'date_upd', 'pack_stock_type']);
        }
    }
    /**
     * Returns pack stock type management type, one of
     *   - Pack::STOCK_TYPE_DECREMENT_PACK,
     *   - Pack::STOCK_TYPE_DECREMENT_PRODUCTS
     *   - Pack::STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS
     *
     * @return int
     */
    public function get_pack_stock_type()
    {
        $stock_type = (int) $this->pack_stock_type;
        if (Pack::is_valid_stock_type($stock_type)) {
            return $stock_type;
        }
        if ($stock_type === Pack::STOCK_TYPE_DECREMENT_GLOBAL_SETTINGS) {
            return Pack::get_global_stock_type_settings();
        }
        // should never happen
        return Pack::STOCK_TYPE_DECREMENT_PACK;
    }
    /**
     * Returns true, if quantities of pack items should be adjusted with sale of pack
     *
     * @return bool
     */
    public function should_adjust_pack_items_quantities()
    {
        return match ($this->get_pack_stock_type()) {
            Pack::STOCK_TYPE_DECREMENT_PACK => false,
            Pack::STOCK_TYPE_DECREMENT_PRODUCTS => true,
            Pack::STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS => true,
            default => throw new RuntimeException('Invariant: getPackStockType returned invalid value'),
        };
    }
    /**
     * Returns true, if quantity of pack itself should be adjusted with sale of pack
     *
     * @return bool
     */
    public function should_adjust_pack_quantity()
    {
        return match ($this->get_pack_stock_type()) {
            Pack::STOCK_TYPE_DECREMENT_PACK => true,
            Pack::STOCK_TYPE_DECREMENT_PRODUCTS => false,
            Pack::STOCK_TYPE_DECREMENT_PACK_AND_PRODUCTS => true,
            default => throw new RuntimeException('Invariant: getPackStockType returned invalid value'),
        };
    }
    /**
     * Returns default shop ID associated with product.
     *
     * @return int
     * @throws PrestaShopException
     */
    public function get_default_shop_id()
    {
        $shop_id = (int) $this->id_shop_default;
        if (!$this->is_associated_to_shop($shop_id)) {
            $conn = Db::get_instance();
            $cond = 'id_product = ' . (int) $this->id;
            $shop_id = (int) $conn->get_value((new Db_Query())->select('MIN(id_shop)')->from('product_shop')->where($cond));
            if ($shop_id) {
                $conn->update('product', ['id_shop_default' => $shop_id], $cond);
            }
        }
        return $shop_id;
    }
    /**
     * Returns true, if customization is required for a product
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function is_customization_required()
    {
        if ($this->customizable) {
            return (bool) Db::read_only()->get_value((new Db_Query())->select('1')->from('customization_field', 'cf')->where('cf.id_product = ' . (int) $this->id)->where('cf.`required`'));
        }
        return false;
    }
    /**
     * Returns true, if Context represents front office context
     *
     * @param Context|null $context
     */
    protected static function is_front_office_context($context): bool
    {
        if (!$context) {
            $context = Context::get_context();
        }
        // this is not front office context if if controller is not set
        if (!isset($context->controller)) {
            return false;
        }
        // check controller type
        return in_array($context->controller->controller_type, ['front', 'modulefront']);
    }
    /**
     * Database initialization callback
     *
     * @throws PrestaShopException
     */
    public static function initialization_callback(Db $conn): void
    {
        Image_Entity::rebuild_image_entities('Product', self::$definition['images']);
    }
    /**
     * Returns weight of product, including combination impact
     *
     *
     * @throws PrestaShopException
     */
    public function get_weight(int $combination_id = 0): float
    {
        $weight = (float) $this->weight;
        if ($this->has_attributes()) {
            if (!$combination_id) {
                $combination_id = $this->get_default_id_product_attribute();
            }
            $combination = new Combination($combination_id);
            if (Validate::is_loaded_object($combination)) {
                $weight += (float) $combination->weight;
            }
        }
        return $weight;
    }
    /**
     * Returns width of product, including combination impact
     *
     *
     * @throws PrestaShopException
     */
    public function get_width(int $combination_id = 0): float
    {
        $width = (float) $this->width;
        if ($this->has_attributes()) {
            if (!$combination_id) {
                $combination_id = $this->get_default_id_product_attribute();
            }
            $combination = new Combination($combination_id);
            if (Validate::is_loaded_object($combination)) {
                $width += (float) $combination->width;
            }
        }
        return $width;
    }
    /**
     * Returns height of product, including combination impact
     *
     *
     * @throws PrestaShopException
     */
    public function get_height(int $combination_id = 0): float
    {
        $height = (float) $this->height;
        if ($this->has_attributes()) {
            if (!$combination_id) {
                $combination_id = $this->get_default_id_product_attribute();
            }
            $combination = new Combination($combination_id);
            if (Validate::is_loaded_object($combination)) {
                $height += (float) $combination->height;
            }
        }
        return $height;
    }
    /**
     * Returns depth of product, including combination impact
     *
     *
     * @throws PrestaShopException
     */
    public function get_depth(int $combination_id = 0): float
    {
        $depth = (float) $this->depth;
        if ($this->has_attributes()) {
            if (!$combination_id) {
                $combination_id = $this->get_default_id_product_attribute();
            }
            $combination = new Combination($combination_id);
            if (Validate::is_loaded_object($combination)) {
                $depth += (float) $combination->depth;
            }
        }
        return $depth;
    }
    /**
     * Returns the available product quantity.
     * If combinations exist, sums only positive stocks (if $ignoreNegativeStocks is true).
     * Falls back to main product stock if no combinations are found.
     * Uses static cache unless $fresh is true.
     *
     * @param $ignoreNegativeStocks bool If should ignore negative stocks
     * @param $fresh bool If true, then cache is refreshed
     * @param $idShop int|null ID shop for which to take stocks from
     *
     *
     * @throws PrestaShopException
     * @since thirty bees 1.7.0
     */
    public function get_available_quantity(bool $ignore_negative_stocks = true, bool $fresh = false, ?int $id_shop = null): int
    {
        $product_id = (int) $this->id;
        if (!$product_id) {
            return 0;
        }
        $cache_key = ($ignore_negative_stocks ? 'p' : 'n') . '_' . $id_shop;
        if ($fresh) {
            unset($this->_cache_available_quantity[$cache_key]);
        }
        if (isset($this->_cache_available_quantity[$cache_key])) {
            return $this->_cache_available_quantity[$cache_key];
        }
        $this->_cache_available_quantity[$cache_key] = 0;
        if (Combination::is_feature_active()) {
            $combinations = static::get_product_attributes_ids($product_id);
            if ($combinations) {
                foreach ($combinations as $row) {
                    $combination_id = (int) $row['id_product_attribute'];
                    $stock = Stock_Available_Core::get_quantity_available_by_product($product_id, $combination_id, $id_shop);
                    if ($stock > 0 || !$ignore_negative_stocks) {
                        $this->_cache_available_quantity[$cache_key] += $stock;
                    }
                }
                return $this->_cache_available_quantity[$cache_key];
            }
        }
        $stock = Stock_Available_Core::get_quantity_available_by_product($product_id, null, $id_shop);
        if ($stock > 0 || !$ignore_negative_stocks) {
            $this->_cache_available_quantity[$cache_key] = $stock;
        }
        return $this->_cache_available_quantity[$cache_key];
    }
}