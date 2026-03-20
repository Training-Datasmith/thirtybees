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
use Thirtybees\Core\Dependency_Injection\Service_Locator;
/**
 * Class WebserviceRequestCore
 */
class Webservice_Request_Core
{
    public const HTTP_GET = 1;
    public const HTTP_POST = 2;
    public const HTTP_PUT = 4;
    public const HEADER_IO_FORMAT = 'Io-Format';
    public const HEADER_OUTPUT_FORMAT = 'Output-Format';
    /**
     * @var array
     */
    protected $_available_languages;
    /**
     * Errors triggered at execution
     *
     * @var array
     */
    public $errors = [];
    /**
     * Set if return should display content or not
     *
     * @var bool
     */
    protected $_output_enabled = true;
    /**
     * Set if the management is specific or if it is classic (entity management)
     *
     * @var WebserviceSpecificManagementInterface|false
     */
    protected $object_specific_management = false;
    /**
     * Base PrestaShop webservice URL
     *
     * @var string
     */
    public $ws_url;
    /**
     * @var array
     */
    protected $params;
    /**
     * PrestaShop Webservice Documentation URL
     *
     * @var string
     */
    protected $_doc_url = 'http://example.com';
    /**
     * Set if the authentication key was checked
     *
     * @var bool
     */
    protected $_authenticated = false;
    /**
     * HTTP Method to support
     *
     * @var string
     */
    public $method;
    /**
     * The segment of the URL
     *
     * @var array
     */
    public $url_segment = [];
    /**
     * The segment list of the URL after the "api" segment
     *
     * @var array
     */
    public $url_fragments = [];
    /**
     * The time in microseconds of the start of the execution of the web service request
     *
     * @var int
     */
    protected $_start_time = 0;
    /**
     * The list of each resources manageable via web service
     *
     * @var array
     */
    public $resource_list;
    /**
     * The configuration parameters of the current resource
     *
     * @var array
     */
    public $resource_configuration;
    /**
     * The permissions for the current key
     *
     * @var array
     */
    public $key_permissions;
    /**
     * The XML string to display if web service call succeed
     *
     * @var string
     */
    protected $specific_output = '';
    /**
     * The list of objects to display
     *
     * @var array
     */
    public $objects;
    /**
     * The current object to support, it extends the PrestaShop ObjectModel
     *
     * @var ObjectModel
     */
    protected $_object;
    /**
     * The schema to display.
     * If null, no schema have to be displayed and normal management has to be performed
     *
     * @var string
     */
    public $schema_to_display;
    /**
     * The fields to display. These fields will be displayed when retrieving objects
     *
     * @var string
     */
    public $fields_to_display = 'minimum';
    /**
     * If we are in PUT or POST case, we use this attribute to store the xml string value during process
     *
     * @var string
     */
    protected $_input_xml;
    /**
     * Object instance for singleton
     *
     * @var WebserviceRequest
     */
    protected static $_instance;
    /**
     * Key used for authentication
     *
     * @var string
     */
    protected $_key;
    /**
     * This is used to have a deeper tree diagram.
     *
     * @var int
     */
    public $depth = 0;
    /**
     * Name of the output format
     *
     * @var string
     */
    protected $output_format = 'xml';
    /**
     * The object to build the output.
     *
     * @var WebserviceOutputBuilder
     */
    protected $obj_output;
    /**
     * Save the class name for override used in getInstance()
     *
     * @var string
     */
    public static $ws_current_classname;
    /**
     * @var int[]
     */
    public static $shop_i_ds = [];
    /**
     * @var WebserviceLogger
     */
    protected $logger;
    /**
     * @return bool
     */
    public function get_output_enabled()
    {
        return $this->_output_enabled;
    }
    /**
     * @param bool $bool
     */
    public function set_output_enabled($bool): static
    {
        if (Validate::is_bool($bool)) {
            $this->_output_enabled = $bool;
        }
        return $this;
    }
    /**
     * Get WebserviceRequest object instance (Singleton)
     *
     * @return static WebserviceRequest instance
     */
    public static function get_instance()
    {
        if (!isset(static::$_instance)) {
            static::$_instance = new Webservice_Request::$ws_current_classname();
        }
        return static::$_instance;
    }
    /**
     * @param string $type
     */
    protected function get_output_object($type): \Webservice_Output_Xml|\Webservice_Output_Json
    {
        // set header param in header or as get param
        $headers = static::get_webservice_headers();
        if (isset($headers[static::HEADER_IO_FORMAT])) {
            $type = $headers[static::HEADER_IO_FORMAT];
        } elseif (isset($headers[static::HEADER_OUTPUT_FORMAT])) {
            $type = $headers[static::HEADER_OUTPUT_FORMAT];
        } elseif (isset($_GET['output_format'])) {
            $type = $_GET['output_format'];
        } elseif (isset($_GET['io_format'])) {
            $type = $_GET['io_format'];
        }
        $this->output_format = $type;
        switch ($type) {
            case 'JSON':
                require_once __DIR__ . '/WebserviceOutputJSON.php';
                $obj_render = new Webservice_Output_Json();
                break;
            case 'XML':
            default:
                $obj_render = new Webservice_Output_Xml();
                break;
        }
        return $obj_render;
    }
    public static function get_resources(): array
    {
        $resources = ['addresses' => ['description' => 'The Customer, Manufacturer and Customer addresses', 'class' => 'Address'], 'carriers' => ['description' => 'The Carriers', 'class' => 'Carrier'], 'carts' => ['description' => 'Customer\'s carts', 'class' => 'Cart'], 'cart_rules' => ['description' => 'Cart rules management', 'class' => 'CartRule'], 'categories' => ['description' => 'The product categories', 'class' => 'Category'], 'combinations' => ['description' => 'The product combinations', 'class' => 'Combination'], 'configurations' => ['description' => 'Shop configuration', 'class' => 'Configuration'], 'contacts' => ['description' => 'Shop contacts', 'class' => 'Contact'], 'countries' => ['description' => 'The countries', 'class' => 'Country'], 'currencies' => ['description' => 'The currencies', 'class' => 'Currency'], 'customers' => ['description' => 'The e-shop\'s customers', 'class' => 'Customer'], 'customer_threads' => ['description' => 'Customer services threads', 'class' => 'CustomerThread'], 'customer_messages' => ['description' => 'Customer services messages', 'class' => 'CustomerMessage'], 'deliveries' => ['description' => 'Product delivery', 'class' => 'Delivery'], 'groups' => ['description' => 'The customer\'s groups', 'class' => 'Group'], 'guests' => ['description' => 'The guests', 'class' => 'Guest'], 'images' => ['description' => 'The images', 'specific_management' => true], 'image_entities' => ['description' => 'Image entities', 'class' => 'ImageEntity'], 'image_types' => ['description' => 'The image types', 'class' => 'ImageType'], 'languages' => ['description' => 'Shop languages', 'class' => 'Language'], 'manufacturers' => ['description' => 'The product manufacturers', 'class' => 'Manufacturer'], 'order_carriers' => ['description' => 'The Order carriers', 'class' => 'OrderCarrier'], 'order_cart_rules' => ['description' => 'The Order cart rules', 'class' => 'OrderCartRule'], 'order_details' => ['description' => 'Details of an order', 'class' => 'OrderDetail'], 'order_histories' => ['description' => 'The Order histories', 'class' => 'OrderHistory'], 'order_invoices' => ['description' => 'The Order invoices', 'class' => 'OrderInvoice'], 'orders' => ['description' => 'The Customers orders', 'class' => 'Order'], 'order_payments' => ['description' => 'The Order payments', 'class' => 'OrderPayment'], 'order_states' => ['description' => 'The Order statuses', 'class' => 'OrderState'], 'order_slip' => ['description' => 'The Order slips', 'class' => 'OrderSlip'], 'price_ranges' => ['description' => 'Price ranges', 'class' => 'RangePrice'], 'product_features' => ['description' => 'The product features', 'class' => 'Feature'], 'product_feature_values' => ['description' => 'The product feature values', 'class' => 'FeatureValue'], 'product_options' => ['description' => 'The product options', 'class' => 'AttributeGroup'], 'product_option_values' => ['description' => 'The product options value', 'class' => 'ProductAttribute'], 'products' => ['description' => 'The products', 'class' => 'Product'], 'states' => ['description' => 'The available states of countries', 'class' => 'State'], 'stores' => ['description' => 'The stores', 'class' => 'Store'], 'suppliers' => ['description' => 'The product suppliers', 'class' => 'Supplier'], 'tags' => ['description' => 'The Products tags', 'class' => 'Tag'], 'translated_configurations' => ['description' => 'Shop configuration', 'class' => 'TranslatedConfiguration'], 'weight_ranges' => ['description' => 'Weight ranges', 'class' => 'RangeWeight'], 'zones' => ['description' => 'The Countries zones', 'class' => 'Zone'], 'employees' => ['description' => 'The Employees', 'class' => 'Employee'], 'search' => ['description' => 'Search', 'specific_management' => true, 'forbidden_method' => ['PUT', 'POST', 'DELETE']], 'content_management_system' => ['description' => 'Content management system', 'class' => 'CMS'], 'cms_categories' => ['description' => 'CMS Category', 'class' => 'CMSCategory'], 'shops' => ['description' => 'Shops from multi-shop feature', 'class' => 'Shop'], 'shop_groups' => ['description' => 'Shop groups from multi-shop feature', 'class' => 'ShopGroup'], 'taxes' => ['description' => 'The tax rate', 'class' => 'Tax'], 'stock_movements' => ['description' => 'Stock movements', 'class' => 'StockMvtWS', 'forbidden_method' => ['PUT', 'POST', 'DELETE']], 'stock_movement_reasons' => ['description' => 'Stock movement reason', 'class' => 'StockMvtReason'], 'warehouses' => ['description' => 'Warehouses', 'class' => 'Warehouse', 'forbidden_method' => ['DELETE']], 'stocks' => ['description' => 'Stocks', 'class' => 'Stock', 'forbidden_method' => ['PUT', 'POST', 'DELETE']], 'stock_availables' => ['description' => 'Available quantities', 'class' => 'StockAvailable', 'forbidden_method' => ['POST', 'DELETE']], 'warehouse_product_locations' => ['description' => 'Location of products in warehouses', 'class' => 'WarehouseProductLocation', 'forbidden_method' => ['PUT', 'POST', 'DELETE']], 'supply_orders' => ['description' => 'Supply Orders', 'class' => 'SupplyOrder', 'forbidden_method' => ['PUT', 'POST', 'DELETE']], 'supply_order_details' => ['description' => 'Supply Order Details', 'class' => 'SupplyOrderDetail', 'forbidden_method' => ['PUT', 'POST', 'DELETE']], 'supply_order_states' => ['description' => 'Supply Order Statuses', 'class' => 'SupplyOrderState', 'forbidden_method' => ['PUT', 'POST', 'DELETE']], 'supply_order_histories' => ['description' => 'Supply Order Histories', 'class' => 'SupplyOrderHistory', 'forbidden_method' => ['PUT', 'POST', 'DELETE']], 'supply_order_receipt_histories' => ['description' => 'Supply Order Receipt Histories', 'class' => 'SupplyOrderReceiptHistory', 'forbidden_method' => ['PUT', 'POST', 'DELETE']], 'product_suppliers' => ['description' => 'Product Suppliers', 'class' => 'ProductSupplier'], 'tax_rules' => ['description' => 'Tax rules entity', 'class' => 'TaxRule'], 'tax_rule_groups' => ['description' => 'Tax rule groups', 'class' => 'TaxRulesGroup'], 'specific_prices' => ['description' => 'Specific price management', 'class' => 'SpecificPrice'], 'specific_price_rules' => ['description' => 'Specific price management', 'class' => 'SpecificPriceRule'], 'shop_urls' => ['description' => 'Shop URLs from multi-shop feature', 'class' => 'ShopUrl'], 'product_customization_fields' => ['description' => 'Customization Field', 'class' => 'CustomizationField'], 'customizations' => ['description' => 'Customization values', 'class' => 'Customization']];
        ksort($resources);
        return $resources;
    }
    /* @todo Check how get parameters */
    /* @todo : set this method out */
    /**
     * This method is used for calculate the price for products on the output details
     *
     * @param ObjectModel $entityObject
     * @param array $wsParams
     *
     * @return array field parameters.
     * @throws PrestaShopException
     */
    public function get_price_for_product(array $field, $entity_object, $ws_params): array
    {
        if (is_int($entity_object->id)) {
            $arr_return = $this->specific_price_for_product($entity_object, ['default_price' => '']);
            $field['value'] = $arr_return['default_price']['value'];
        }
        return $field;
    }
    /* @todo : set this method out */
    /**
     * This method is used for calculate the price for products on a virtual fields
     *
     * @param ObjectModel $entityObject
     *
     * @return array
     * @throws PrestaShopException
     */
    public function specific_price_for_product($entity_object, array $parameters)
    {
        foreach (array_keys($parameters) as $name) {
            $parameters[$name]['object_id'] = $entity_object->id;
        }
        return $this->specific_price_calculation($parameters);
    }
    /**
     * @param array $parameters
     *
     *
     * @throws PrestaShopException
     */
    public function specific_price_calculation($parameters): array
    {
        $arr_return = [];
        foreach ($parameters as $name => $value) {
            $id_shop = (int) Context::get_context()->shop->id;
            $id_country = (int) ($value['country'] ?? Configuration::get('PS_COUNTRY_DEFAULT'));
            $id_state = (int) ($value['state'] ?? 0);
            $id_currency = (int) ($value['currency'] ?? Configuration::get('PS_CURRENCY_DEFAULT'));
            $id_group = (int) ($value['group'] ?? (int) Configuration::get('PS_CUSTOMER_GROUP'));
            $quantity = (int) ($value['quantity'] ?? 1);
            $use_tax = (int) ($value['use_tax'] ?? Configuration::get('PS_TAX'));
            $decimals = (int) ($value['decimals'] ?? _TB_PRICE_DATABASE_PRECISION_);
            $id_product_attribute = (int) ($value['product_attribute'] ?? null);
            $only_reduc = (int) ($value['only_reduction'] ?? false);
            $use_reduc = (int) ($value['use_reduction'] ?? true);
            $use_ecotax = (int) ($value['use_ecotax'] ?? Configuration::get('PS_USE_ECOTAX'));
            $specific_price_output = null;
            $zipcode = $value['zipcode'] ?? '';
            $return_value = Product::price_calculation($id_shop, $value['object_id'], $id_product_attribute, $id_country, $id_state, $zipcode, $id_currency, $id_group, $quantity, $use_tax, $decimals, $only_reduc, $use_reduc, $use_ecotax, $specific_price_output, null);
            $arr_return[$name] = ['sqlId' => strtolower((string) $name), 'value' => sprintf('%f', $return_value)];
        }
        return $arr_return;
    }
    /**
     * This method is used for calculate the price for products on a virtual fields
     *
     * @param Combination $entityObject
     *
     * @return array
     * @throws PrestaShopException
     */
    public function specific_price_for_combination($entity_object, array $parameters)
    {
        foreach (array_keys($parameters) as $name) {
            $parameters[$name]['object_id'] = $entity_object->id_product;
            $parameters[$name]['product_attribute'] = $entity_object->id;
        }
        return $this->specific_price_calculation($parameters);
    }
    /**
     * Start Webservice request
     *    Check webservice activation
     *    Check autentication
     *    Check resource
     *    Check HTTP Method
     *    Execute the action
     *    Display the result
     *
     * @param string $key
     * @param string $method
     * @param string $url
     * @param array $params GET parameters
     * @param string $badClassName
     * @param string $inputXml
     *
     * @return array Returns an array of results (headers, content, type of resource...)
     *
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    public function fetch($key, $method, $url, array $params, $bad_class_name, $input_xml = null)
    {
        $error_handler = Service_Locator::get_instance()->get_error_handler();
        $logger = $this->get_logger();
        // Time logger
        $this->_start_time = microtime(true);
        $this->objects = [];
        if (!$params) {
            $params = [];
        }
        $this->params = $params;
        // __PS_BASE_URI__ is from Shop::$current_base_uri
        $this->ws_url = Tools::get_http_host(true) . __PS_BASE_URI__ . 'api/';
        // set the output object which manage the content and header structure and informations
        $this->obj_output = new Webservice_Output_Builder($this->ws_url);
        $this->_key = trim($key);
        $this->output_format = $params['output_format'] ?? $this->output_format;
        // Set the render object to build the output on the asked format (XML, JSON, CSV, ...)
        $this->obj_output->set_object_render($this->get_output_object($this->output_format));
        // set fatal error handler
        $error_handler->set_error_response_handler(new Webservice_Fatal_Error_Response($this->obj_output, $logger, _PS_MODE_DEV_, $this->_start_time));
        // Check webservice activation and request authentication
        if ($this->webservice_checks()) {
            $logger->set_key($this->get_webservice_key());
            $headers = static::get_webservice_headers();
            $logger->log_request($method, $_SERVER['REQUEST_URI'], $headers, $input_xml);
            if ($bad_class_name) {
                $this->set_error(500, 'Class "' . htmlspecialchars($bad_class_name) . '" not found. Please update the class_name field in the webservice_account table.', 126);
            }
            // parse request url
            $this->method = $method;
            $this->url_segment = explode('/', $url);
            $this->url_fragments = $params;
            $this->_input_xml = $input_xml;
            $this->depth = isset($this->url_fragments['depth']) ? (int) $this->url_fragments['depth'] : $this->depth;
            if (isset($this->url_fragments['price'])) {
                $this->obj_output->set_virtual_field($this, 'specificPriceForCombination', 'combinations', $this->url_fragments['price']);
                $this->obj_output->set_virtual_field($this, 'specificPriceForProduct', 'products', $this->url_fragments['price']);
            }
            if (isset($this->url_fragments['language'])) {
                $this->_available_languages = $this->filter_language();
            } else {
                $this->_available_languages = Language::get_i_ds();
            }
            if (empty($this->_available_languages)) {
                $this->set_error(400, 'language is not available', 81);
            }
            // Need to set available languages for the render object.
            // Thus we can filter i18n field for the output
            // @see WebserviceOutputXML::renderField() method for example
            $this->obj_output->object_render->set_languages($this->_available_languages);
            // check method and resource
            if (empty($this->errors) && $this->check_resource() && $this->check_http_method()) {
                // The resource list is necessary for build the output
                $this->obj_output->set_ws_resources($this->resource_list);
                // if the resource is a core entity...
                if (!isset($this->resource_list[$this->url_segment[0]]['specific_management']) || !$this->resource_list[$this->url_segment[0]]['specific_management']) {
                    // load resource configuration
                    if ($this->url_segment[0] != '') {
                        /** @var ObjectModel $object */
                        $url_resource = $this->resource_list[$this->url_segment[0]];
                        $object = new $url_resource['class']();
                        if (isset($this->resource_list[$this->url_segment[0]]['parameters_attribute'])) {
                            $this->resource_configuration = $object->get_webservice_parameters($this->resource_list[$this->url_segment[0]]['parameters_attribute']);
                        } else {
                            $this->resource_configuration = $object->get_webservice_parameters();
                        }
                    }
                    // execute the action
                    switch ($this->method) {
                        case 'GET':
                        case 'HEAD':
                            $this->execute_entity_get_and_head();
                            break;
                        case 'POST':
                            $this->execute_entity_post();
                            break;
                        case 'PUT':
                            $this->execute_entity_put();
                            break;
                        case 'DELETE':
                            $this->execute_entity_delete();
                            break;
                    }
                    // Need to set an object for the WebserviceOutputBuilder object in any case
                    // because schema need to get webserviceParameters of this object
                    if (isset($object)) {
                        $this->objects['empty'] = $object;
                    }
                } else {
                    $specific_object_name = 'WebserviceSpecificManagement' . ucfirst(Tools::to_camel_case($this->url_segment[0]));
                    if (!class_exists($specific_object_name)) {
                        $this->set_error(501, sprintf('The specific management class is not implemented for the "%s" entity.', $this->url_segment[0]), 124);
                    } else {
                        $this->object_specific_management = new $specific_object_name();
                        $this->object_specific_management->set_object_output($this->obj_output)->set_ws_object($this);
                        try {
                            $this->object_specific_management->manage();
                        } catch (Webservice_Exception $e) {
                            if ($e->get_type() == Webservice_Exception::DID_YOU_MEAN) {
                                $this->set_error_did_you_mean($e->get_status(), $e->get_message(), $e->get_wrong_value(), $e->get_available_values(), $e->get_code());
                            } elseif ($e->get_type() == Webservice_Exception::SIMPLE) {
                                $this->set_error($e->get_status(), $e->get_message(), $e->get_code());
                            }
                        }
                    }
                }
            }
        }
        return $this->return_output();
    }
    /**
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function webservice_checks(): bool
    {
        return $this->is_activated() && $this->authenticate() && $this->group_shop_exists($this->params) && $this->shop_exists($this->params) && $this->shop_has_right($this->_key);
    }
    /**
     * Set a webservice error
     *
     * @param int $status
     * @param string $label
     * @param int $code
     */
    public function set_error($status, $label, $code): void
    {
        if (isset($this->obj_output)) {
            $this->obj_output->set_status($status);
        }
        $this->errors[] = [$code, $label];
    }
    /**
     * Set a webservice error and propose a new value near from the available values
     *
     * @param int $num
     * @param string $value
     * @param array $availableValues
     * @param int $code
     *
     */
    public function set_error_did_you_mean($num, string $label, $value, $available_values, $code): void
    {
        $this->set_error($num, $label . '. Did you mean: "' . $this->get_closest($value, $available_values) . '"?' . (count($available_values) > 1 ? ' The full list is: "' . implode('", "', $available_values) . '"' : ''), $code);
    }
    /**
     * Return the nearest value picked in the values list
     *
     * @param string $input
     * @param array $words
     *
     * @return string
     */
    protected function get_closest($input, $words)
    {
        $shortest = -1;
        foreach ($words as $word) {
            $lev = levenshtein($input, $word);
            if ($lev == 0) {
                $closest = $word;
                $shortest = 0;
                break;
            }
            if ($lev <= $shortest || $shortest < 0) {
                $closest = $word;
                $shortest = $lev;
            }
        }
        return $closest;
    }
    /**
     * Check if there is one or more error
     */
    protected function has_errors(): bool
    {
        return (bool) $this->errors;
    }
    /**
     * Check request authentication
     *
     *
     * @throws PrestaShopException
     */
    protected function authenticate(): bool
    {
        if (!$this->has_errors()) {
            if (is_null($this->_key)) {
                $this->set_error(401, 'Please enter the authentication key as the login. No password required', 16);
            } else if (empty($this->_key)) {
                $this->set_error(401, 'Authentication key is empty', 17);
            } elseif (strlen($this->_key) != '32') {
                $this->set_error(401, 'Invalid authentication key format', 18);
            } else {
                if (Webservice_Key::is_key_active($this->_key)) {
                    $this->key_permissions = Webservice_Key::get_permission_for_account($this->_key);
                } else {
                    $this->set_error(401, 'Authentification key is not active', 20);
                }
                if (!$this->key_permissions) {
                    $this->set_error(401, 'No permission for this authentication key', 21);
                }
            }
            if ($this->has_errors()) {
                header('WWW-Authenticate: Basic realm="Welcome to PrestaShop Webservice, please enter the authentication key as the login. No password required."');
                $this->obj_output->set_status(401);
                return false;
            }
            // only now we can say the access is authenticated
            $this->_authenticated = true;
            return true;
        }
        return false;
    }
    /**
     * Check webservice activation
     *
     *
     * @throws PrestaShopException
     */
    protected function is_activated(): bool
    {
        if (!Configuration::get('PS_WEBSERVICE')) {
            $this->set_error(503, 'The thirty bees webservice is disabled. Please activate it in the thirty bees Back Office', 22);
            return false;
        }
        return true;
    }
    /**
     * @param string $key
     *
     *
     * @throws PrestaShopException
     */
    protected function shop_has_right($key): bool
    {
        $sql = 'SELECT 1
				FROM ' . _DB_PREFIX_ . 'webservice_account wsa LEFT JOIN ' . _DB_PREFIX_ . 'webservice_account_shop wsas ON (wsa.id_webservice_account = wsas.id_webservice_account)
				WHERE wsa.key = \'' . p_sql($key) . '\'';
        $OR = [];
        foreach ($this->get_shop_ids() as $shop_id) {
            $OR[] = ' wsas.id_shop = ' . (int) $shop_id . ' ';
        }
        if ($OR) {
            $sql .= ' AND (' . implode('OR', $OR) . ') ';
        }
        if (!Db::read_only()->get_value($sql)) {
            $this->set_error(403, 'No permission for this key on this shop', 132);
            return false;
        }
        return true;
    }
    /**
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function shop_exists(array $params): bool
    {
        if (static::$shop_i_ds) {
            return true;
        }
        if (isset($params['id_shop'])) {
            if ($params['id_shop'] != 'all' && is_numeric($params['id_shop'])) {
                Shop::set_context(Shop::CONTEXT_SHOP, (int) $params['id_shop']);
                static::$shop_i_ds[] = (int) $params['id_shop'];
                return true;
            }
            if ($params['id_shop'] == 'all') {
                Shop::set_context(Shop::CONTEXT_ALL);
                static::$shop_i_ds = Shop::get_shops(true, null, true);
                return true;
            }
        } else {
            static::$shop_i_ds[] = (int) Context::get_context()->shop->id;
            return true;
        }
        $this->set_error(404, 'This shop id does not exist', 999);
        return false;
    }
    /**
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function group_shop_exists(array $params): bool
    {
        if (isset($params['id_group_shop']) && is_numeric($params['id_group_shop'])) {
            Shop::set_context(Shop::CONTEXT_GROUP, (int) $params['id_group_shop']);
            static::$shop_i_ds = Shop::get_shops(true, (int) $params['id_group_shop'], true);
            if (!static::$shop_i_ds) {
                $this->set_error(500, 'This group shop doesn\'t have shops', 999);
                return false;
            }
        }
        // id_group_shop isn't mandatory
        return true;
    }
    /**
     * Check HTTP method
     */
    protected function check_http_method(): bool
    {
        if (!in_array($this->method, ['GET', 'POST', 'PUT', 'DELETE', 'HEAD'])) {
            $this->set_error(405, 'Method ' . $this->method . ' is not valid', 23);
        } elseif (isset($this->url_segment[0]) && isset($this->resource_list[$this->url_segment[0]]['forbidden_method']) && in_array($this->method, $this->resource_list[$this->url_segment[0]]['forbidden_method'])) {
            $this->set_error(405, 'Method ' . $this->method . ' is not allowed for the resource ' . $this->url_segment[0], 101);
        } elseif ($this->url_segment[0] && !in_array($this->method, $this->key_permissions[$this->url_segment[0]])) {
            $this->set_error(405, 'Method ' . $this->method . ' is not allowed for the resource ' . $this->url_segment[0] . ' with this authentication key', 25);
        } else {
            return true;
        }
        return false;
    }
    /**
     * Check resource validity
     */
    protected function check_resource(): bool
    {
        $this->resource_list = static::get_resources();
        $resource_names = array_keys($this->resource_list);
        if ($this->url_segment[0] == '') {
            $this->resource_configuration['objectsNodeName'] = 'resources';
        } elseif (in_array($this->url_segment[0], $resource_names)) {
            if (!in_array($this->url_segment[0], array_keys($this->key_permissions))) {
                $this->set_error(401, 'Resource of type "' . $this->url_segment[0] . '" is not allowed with this authentication key', 26);
                return false;
            }
        } else {
            $this->set_error_did_you_mean(400, 'Resource of type "' . $this->url_segment[0] . '" does not exists', $this->url_segment[0], $resource_names, 27);
            return false;
        }
        return true;
    }
    /**
     * @return void
     */
    protected function set_objects()
    {
        $arr_avoid_id = [];
        $ids = [];
        if (isset($this->url_fragments['id'])) {
            preg_match('#^\[(.*)\]$#Ui', $this->url_fragments['id'], $matches);
            if (count($matches) > 1) {
                $ids = explode(',', $matches[1]);
            }
        } else {
            $ids[] = (int) $this->url_segment[1];
        }
        foreach ($ids as $id) {
            $retrieve_data = $this->resource_configuration['retrieveData'];
            $object = new $retrieve_data['className']((int) $id);
            if (!$object->id) {
                $arr_avoid_id[] = $id;
            }
        }
        if (!empty($arr_avoid_id) || empty($ids)) {
            $this->set_error(404, 'Id(s) not exists: ' . implode(', ', $arr_avoid_id), 87);
            $this->_output_enabled = true;
        }
    }
    /**
     * @param string $str
     */
    protected function parse_display_fields($str): array
    {
        $bracket_level = 0;
        $part = [];
        $tmp = '';
        $str_len = strlen($str);
        for ($i = 0; $i < $str_len; $i++) {
            if ($str[$i] == ',' && $bracket_level == 0) {
                $part[] = $tmp;
                $tmp = '';
            } else {
                $tmp .= $str[$i];
            }
            if ($str[$i] == '[') {
                $bracket_level++;
            }
            if ($str[$i] == ']') {
                $bracket_level--;
            }
        }
        if ($tmp != '') {
            $part[] = $tmp;
        }
        $fields = [];
        foreach ($part as $str) {
            $field_name = trim(substr($str, 0, !str_contains($str, '[') ? strlen($str) : strpos($str, '[')));
            if (!isset($fields[$field_name])) {
                $fields[$field_name] = null;
            }
            if (str_contains($str, '[')) {
                $sub_fields = substr($str, strpos($str, '[') + 1, strlen($str) - strpos($str, '[') - 2);
                if (str_contains($sub_fields, ',')) {
                    $tmp_array = explode(',', $sub_fields);
                } else {
                    $tmp_array = [$sub_fields];
                }
                $fields[$field_name] = is_array($fields[$field_name]) ? array_merge($fields[$field_name], $tmp_array) : $tmp_array;
            }
        }
        return $fields;
    }
    public function set_fields_to_display(): bool
    {
        // set the fields to display in the list : "full", "minimum", "field_1", "field_1,field_2,field_3"
        if (isset($this->url_fragments['display'])) {
            $this->fields_to_display = $this->url_fragments['display'];
            if ($this->fields_to_display != 'full' && $this->fields_to_display != 'minimum') {
                preg_match('#^\[(.*)\]$#Ui', $this->fields_to_display, $matches);
                if (count($matches)) {
                    $error = false;
                    $fields_to_test = $this->parse_display_fields($matches[1]);
                    foreach ($fields_to_test as $field_name => $part) {
                        // in case it is not an association
                        if (!is_array($part)) {
                            // We have to allow new specific field for price calculation too
                            $error = !isset($this->resource_configuration['fields'][$field_name]) && !isset($this->url_fragments['price'][$field_name]);
                        } else {
                            // if this association does not exists
                            if (!array_key_exists($field_name, $this->resource_configuration['associations'])) {
                                $error = true;
                            }
                            foreach ($part as $field) {
                                if ($field != 'id' && !array_key_exists($field, $this->resource_configuration['associations'][$field_name]['fields'])) {
                                    $error = true;
                                    break;
                                }
                            }
                        }
                        if ($error) {
                            $this->set_error(400, 'Unable to display this field "' . $field_name . (is_array($part) ? ' (details : ' . var_export($part, true) . ')' : '') . '". However, these are available: ' . implode(', ', array_keys($this->resource_configuration['fields'])), 35);
                            return false;
                        }
                    }
                    $this->fields_to_display = $fields_to_test;
                } else {
                    $this->set_error(400, 'The \'display\' syntax is wrong. You can set \'full\' or \'[field_1,field_2,field_3,...]\'. These are available: ' . implode(', ', array_keys($this->resource_configuration['fields'])), 36);
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * @return array | false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function manage_filters(): array|false
    {
        // filtered fields which can not use filters : hidden_fields
        $available_filters = [];
        // filtered i18n fields which can use filters
        $i18n_available_filters = [];
        foreach ($this->resource_configuration['fields'] as $field_name => $field) {
            if (!isset($this->resource_configuration['hidden_fields']) || isset($this->resource_configuration['hidden_fields']) && !in_array($field_name, $this->resource_configuration['hidden_fields'])) {
                if (!isset($field['i18n']) || !$field['i18n']) {
                    $available_filters[] = $field_name;
                } else {
                    $i18n_available_filters[] = $field_name;
                }
            }
        }
        // Date feature : date=1
        if (!empty($this->url_fragments['date']) && $this->url_fragments['date']) {
            if (!in_array('date_add', $available_filters)) {
                $available_filters[] = 'date_add';
            }
            if (!in_array('date_upd', $available_filters)) {
                $available_filters[] = 'date_upd';
            }
            if (!array_key_exists('date_add', $this->resource_configuration['fields'])) {
                $this->resource_configuration['fields']['date_add'] = ['sqlId' => 'date_add'];
            }
            if (!array_key_exists('date_upd', $this->resource_configuration['fields'])) {
                $this->resource_configuration['fields']['date_upd'] = ['sqlId' => 'date_upd'];
            }
        } else {
            foreach ($available_filters as $key => $value) {
                if ($value == 'date_add' || $value == 'date_upd') {
                    unset($available_filters[$key]);
                }
            }
        }
        //construct SQL filter
        $sql_filter = '';
        $sql_join = '';
        if ($this->url_fragments) {
            $schema = 'schema';
            // if we have to display the schema
            if (isset($this->url_fragments[$schema])) {
                if ($this->url_fragments[$schema] == 'blank' || $this->url_fragments[$schema] == 'synopsis') {
                    $this->schema_to_display = $this->url_fragments[$schema];
                    return ['sql_join' => '', 'sql_filter' => '', 'sql_sort' => '', 'sql_limit' => ' LIMIT 1'];
                }
                $this->set_error(400, 'Please select a schema of type \'synopsis\' to get the whole schema informations (which fields are required, which kind of content...) or \'blank\' to get an empty schema to fill before using POST request', 28);
                return false;
            }
            // if there are filters
            if (isset($this->url_fragments['filter']) && is_array($this->url_fragments['filter'])) {
                foreach ($this->url_fragments['filter'] as $field => $url_param) {
                    if ($field != 'sort' && $field != 'limit') {
                        if (!in_array($field, $available_filters)) {
                            // if there are linked tables
                            if (isset($this->resource_configuration['linked_tables'][$field])) {
                                // contruct SQL join for linked tables
                                $sql_join .= 'LEFT JOIN `' . bq_sql(_DB_PREFIX_ . $this->resource_configuration['linked_tables'][$field]['table']) . '` ' . bq_sql($field) . ' ON (main.`' . bq_sql($this->resource_configuration['fields']['id']['sqlId']) . '` = ' . bq_sql($field) . '.`' . bq_sql($this->resource_configuration['fields']['id']['sqlId']) . '`)' . "\n";
                                // construct SQL filter for linked tables
                                foreach ($url_param as $field2 => $value) {
                                    if (isset($this->resource_configuration['linked_tables'][$field]['fields'][$field2])) {
                                        $linked_field = $this->resource_configuration['linked_tables'][$field]['fields'][$field2];
                                        $sql_filter .= $this->get_sql_retrieve_filter($linked_field['sqlId'], $value, $field . '.');
                                    } else {
                                        $list = array_keys($this->resource_configuration['linked_tables'][$field]['fields']);
                                        $this->set_error_did_you_mean(400, 'This filter does not exist for this linked table', $field2, $list, 29);
                                        return false;
                                    }
                                }
                            } elseif ($url_param != '' && in_array($field, $i18n_available_filters)) {
                                if (!is_array($url_param)) {
                                    $url_param = [$url_param];
                                }
                                $sql_join .= 'LEFT JOIN `' . bq_sql(_DB_PREFIX_ . $this->resource_configuration['retrieveData']['table']) . '_lang` AS main_i18n ON (main.`' . p_sql($this->resource_configuration['fields']['id']['sqlId']) . '` = main_i18n.`' . bq_sql($this->resource_configuration['fields']['id']['sqlId']) . '`)' . "\n";
                                foreach ($url_param as $value) {
                                    $linked_field = $this->resource_configuration['fields'][$field];
                                    $sql_filter .= $this->get_sql_retrieve_filter($linked_field['sqlId'], $value, 'main_i18n.');
                                    $language_filter = '[' . implode('|', $this->_available_languages) . ']';
                                    $sql_filter .= $this->get_sql_retrieve_filter('id_lang', $language_filter, 'main_i18n.');
                                }
                            } elseif (is_array($url_param)) {
                                if (isset($this->resource_configuration['linked_tables'])) {
                                    $this->set_error_did_you_mean(400, 'This linked table does not exist', $field, array_keys($this->resource_configuration['linked_tables']), 30);
                                } else {
                                    $this->set_error(400, 'There is no existing linked table for this resource', 31);
                                }
                                return false;
                            } else {
                                $this->set_error_did_you_mean(400, 'This filter does not exist', $field, $available_filters, 32);
                                return false;
                            }
                        } elseif ($url_param == '') {
                            $this->set_error(400, 'The filter "' . $field . '" is malformed.', 33);
                            return false;
                        } else {
                            if (isset($this->resource_configuration['fields'][$field]['getter'])) {
                                $this->set_error(400, 'The field "' . $field . '" is dynamic. It is not possible to filter GET query with this field.', 34);
                                return false;
                            }
                            if (isset($this->resource_configuration['retrieveData']['tableAlias'])) {
                                $sql_filter .= $this->get_sql_retrieve_filter($this->resource_configuration['fields'][$field]['sqlId'], $url_param, $this->resource_configuration['retrieveData']['tableAlias'] . '.');
                            } else {
                                $sql_filter .= $this->get_sql_retrieve_filter($this->resource_configuration['fields'][$field]['sqlId'], $url_param);
                            }
                        }
                    }
                }
            }
        }
        if (!$this->set_fields_to_display()) {
            return false;
        }
        // construct SQL Sort
        $sql_sort = '';
        if (isset($this->url_fragments['sort'])) {
            preg_match('#^\[(.*)\]$#Ui', $this->url_fragments['sort'], $matches);
            if (count($matches) > 1) {
                $sorts = explode(',', $matches[1]);
            } else {
                $sorts = [$this->url_fragments['sort']];
            }
            $sql_sort .= ' ORDER BY ';
            foreach ($sorts as $sort) {
                $delimiter_position = strrpos($sort, '_');
                if ($delimiter_position !== false) {
                    $field_name = substr($sort, 0, $delimiter_position);
                    $direction = strtoupper(substr($sort, $delimiter_position + 1));
                }
                if ($delimiter_position === false || !in_array($direction, ['ASC', 'DESC'])) {
                    $this->set_error(400, 'The "sort" value has to be formed as this example: "field_ASC" or \'[field_1_DESC,field_2_ASC,field_3_ASC,...]\' ("field" has to be an available field)', 37);
                    return false;
                }
                if (!in_array($field_name, $available_filters) && !in_array($field_name, $i18n_available_filters)) {
                    $this->set_error(400, 'Unable to filter by this field. However, these are available: ' . implode(', ', $available_filters) . ', for i18n fields:' . implode(', ', $i18n_available_filters), 38);
                    return false;
                }
                // for sort on i18n field
                if (in_array($field_name, $i18n_available_filters)) {
                    if (!preg_match('#main_i18n#', $sql_join)) {
                        $sql_join .= 'LEFT JOIN `' . _DB_PREFIX_ . p_sql($this->resource_configuration['retrieveData']['table']) . '_lang` AS main_i18n ON (main.`' . p_sql($this->resource_configuration['fields']['id']['sqlId']) . '` = main_i18n.`' . p_sql($this->resource_configuration['fields']['id']['sqlId']) . '`)' . "\n";
                    }
                    $sql_sort .= 'main_i18n.`' . p_sql($this->resource_configuration['fields'][$field_name]['sqlId']) . '` ' . $direction . ', ';
                    // ORDER BY main_i18n.`field` ASC|DESC
                } else {
                    /** @var ObjectModel $object */
                    $retrieve_data = $this->resource_configuration['retrieveData'];
                    $object = new $retrieve_data['className']();
                    $assoc = Shop::get_asso_table($this->resource_configuration['retrieveData']['table']);
                    if ($assoc !== false && $assoc['type'] == 'shop' && ($object->is_multi_shop_field($this->resource_configuration['fields'][$field_name]['sqlId']) || $field_name == 'id')) {
                        $table_alias = 'multi_shop_' . $this->resource_configuration['retrieveData']['table'];
                    } else {
                        $table_alias = '';
                    }
                    $sql_sort .= (isset($this->resource_configuration['retrieveData']['tableAlias']) ? '`' . bq_sql($this->resource_configuration['retrieveData']['tableAlias']) . '`.' : '`' . bq_sql($table_alias) . '`.') . '`' . p_sql($this->resource_configuration['fields'][$field_name]['sqlId']) . '` ' . $direction . ', ';
                    // ORDER BY `field` ASC|DESC
                }
            }
            $sql_sort = rtrim($sql_sort, ', ') . "\n";
        }
        //construct SQL Limit
        $sql_limit = '';
        if (isset($this->url_fragments['limit'])) {
            $limit_args = explode(',', $this->url_fragments['limit']);
            if (count($limit_args) > 2) {
                $this->set_error(400, 'The "limit" value has to be formed as this example: "5,25" or "10"', 39);
                return false;
            }
            $sql_limit .= ' LIMIT ' . (int) $limit_args[0] . (isset($limit_args[1]) ? ', ' . (int) $limit_args[1] : '') . "\n";
            // LIMIT X|X, Y
        }
        return ['sql_join' => $sql_join, 'sql_filter' => $sql_filter, 'sql_sort' => $sql_sort, 'sql_limit' => $sql_limit];
    }
    /**
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_filtered_object_list(): array
    {
        $objects = [];
        $filters = $this->manage_filters();
        if (is_array($filters)) {
            $this->resource_configuration['retrieveData']['params'][] = $filters['sql_join'];
            $this->resource_configuration['retrieveData']['params'][] = $filters['sql_filter'];
            $this->resource_configuration['retrieveData']['params'][] = $filters['sql_sort'];
            $this->resource_configuration['retrieveData']['params'][] = $filters['sql_limit'];
            //list entities
            $retrieve_data = $this->resource_configuration['retrieveData'];
            $tmp = new $retrieve_data['className']();
            $sql_objects = call_user_func_array([$tmp, $this->resource_configuration['retrieveData']['retrieveMethod']], $this->resource_configuration['retrieveData']['params']);
            if ($sql_objects) {
                foreach ($sql_objects as $sql_object) {
                    if ($this->fields_to_display == 'minimum') {
                        $obj = new $retrieve_data['className']();
                        $obj->id = (int) $sql_object[$this->resource_configuration['fields']['id']['sqlId']];
                        $objects[] = $obj;
                    } else {
                        $objects[] = new $retrieve_data['className']((int) $sql_object[$this->resource_configuration['fields']['id']['sqlId']]);
                    }
                }
            }
        }
        return $objects;
    }
    /**
     * @throws PrestaShopException
     */
    public function get_filtered_object_details(): array
    {
        $objects = [];
        if (!isset($this->url_fragments['display'])) {
            $this->fields_to_display = 'full';
        }
        //get entity details
        $retrieve_data = $this->resource_configuration['retrieveData'];
        $object = new $retrieve_data['className']((int) $this->url_segment[1]);
        if ($object->id) {
            $objects[] = $object;
            // Check if Object is accessible for this/those id_shop
            $assoc = Shop::get_asso_table($this->resource_configuration['retrieveData']['table']);
            if ($assoc !== false) {
                $check_shop_group = false;
                $sql = 'SELECT 1
	 						FROM `' . bq_sql(_DB_PREFIX_ . $retrieve_data['table']);
                if ($assoc['type'] != 'fk_shop') {
                    $sql .= '_' . $assoc['type'];
                } else {
                    $def = Object_Model::get_definition($this->resource_configuration['retrieveData']['className']);
                    if (isset($def['fields']['id_shop_group'])) {
                        $check_shop_group = true;
                    }
                }
                $sql .= '`';
                $OR = [];
                foreach ($this->get_shop_ids() as $id_shop) {
                    $OR[] = ' (id_shop = ' . (int) $id_shop . ($check_shop_group ? ' OR (id_shop = 0 AND id_shop_group=' . (int) Shop::get_group_from_shop((int) $id_shop) . ')' : '') . ') ';
                }
                $shop_cond = $OR ? '(' . implode('OR', $OR) . ')' : '1';
                $check = ' WHERE ' . $shop_cond . ' AND `' . bq_sql($this->resource_configuration['fields']['id']['sqlId']) . '` = ' . (int) $this->url_segment[1];
                if (!Db::read_only()->get_value($sql . $check)) {
                    $this->set_error(404, 'This ' . $this->resource_configuration['retrieveData']['className'] . ' (' . (int) $this->url_segment[1] . ') does not exists on this shop', 131);
                }
            }
            return $objects;
        }
        if (!count($this->errors)) {
            $this->obj_output->set_status(404);
            $this->_output_enabled = false;
        }
        return [];
    }
    /**
     * Execute GET and HEAD requests
     *
     * Build filter
     * Build fields display
     * Build sort
     * Build limit
     *
     *
     * @throws PrestaShopException
     */
    public function execute_entity_get_and_head(): bool
    {
        if ($this->resource_configuration['objectsNodeName'] != 'resources') {
            if (!isset($this->url_segment[1]) || !strlen($this->url_segment[1])) {
                $return = $this->get_filtered_object_list();
            } else {
                $return = $this->get_filtered_object_details();
            }
            if (!$return) {
                return false;
            }
            $this->objects = $return;
        }
        return true;
    }
    /**
     * Execute POST method on a PrestaShop entity
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function execute_entity_post()
    {
        return $this->save_entity_from_xml(201);
    }
    /**
     * Execute PUT method on a PrestaShop entity
     *
     * @return bool
     *
     * @throws PrestaShopException
     */
    protected function execute_entity_put()
    {
        return $this->save_entity_from_xml(200);
    }
    /**
     * Execute DELETE method on a PrestaShop entity
     *
     * @throws PrestaShopException
     */
    protected function execute_entity_delete()
    {
        $objects = [];
        $arr_avoid_id = [];
        $ids = [];
        if (isset($this->url_fragments['id'])) {
            preg_match('#^\[(.*)\]$#Ui', $this->url_fragments['id'], $matches);
            if (count($matches) > 1) {
                $ids = explode(',', $matches[1]);
            }
        } else {
            $ids[] = (int) $this->url_segment[1];
        }
        foreach ($ids as $id) {
            $retrieve_data = $this->resource_configuration['retrieveData'];
            $object = new $retrieve_data['className']((int) $id);
            if (!$object->id) {
                $arr_avoid_id[] = $id;
            } else {
                $objects[] = $object;
            }
        }
        if (!empty($arr_avoid_id) || empty($ids)) {
            $this->set_error(404, 'Id(s) not exists: ' . implode(', ', $arr_avoid_id), 87);
            $this->_output_enabled = true;
        } else {
            foreach ($objects as $object) {
                /** @var ObjectModel $object */
                if (isset($this->resource_configuration['objectMethods']['delete'])) {
                    $resource_config = $this->resource_configuration['objectMethods']['delete'];
                    $result = $object->{$resource_config}();
                } else {
                    $result = $object->delete();
                }
                if (!$result) {
                    $arr_avoid_id[] = $object->id;
                }
            }
            if (!empty($arr_avoid_id)) {
                $this->set_error(500, 'Id(s) wasn\'t deleted: ' . implode(', ', $arr_avoid_id), 88);
                $this->_output_enabled = true;
            } else {
                $this->_output_enabled = false;
            }
        }
    }
    /**
     * save Entity Object from XML
     *
     * @param int $successReturnCode
     *
     *
     * @throws PrestaShopException
     */
    protected function save_entity_from_xml($success_return_code): bool
    {
        try {
            $xml = @new Simple_Xml_Element($this->_input_xml);
        } catch (Exception $error) {
            $this->set_error(500, 'XML error : ' . $error->get_message() . "\n" . 'XML length : ' . strlen($this->_input_xml) . "\n" . 'Original XML : ' . $this->_input_xml, 127);
            return false;
        }
        /** @var SimpleXMLElement|Countable $xmlEntities */
        $xml_entities = $xml->children();
        $object = null;
        $ids = [];
        foreach ($xml_entities as $entity) {
            // To cast in string allow to check null values
            if ((string) $entity->id != '') {
                $ids[] = (int) $entity->id;
            }
        }
        if ($this->method == 'PUT') {
            $ids2 = array_unique($ids);
            if (count($ids2) != count($ids)) {
                $this->set_error(400, 'id is duplicate in request', 89);
                return false;
            }
            if (count($xml_entities) != count($ids)) {
                $this->set_error(400, 'id is required when modifying a resource', 90);
                return false;
            }
        } elseif ($this->method == 'POST' && count($ids) > 0) {
            $this->set_error(400, 'id is forbidden when adding a new resource', 91);
            return false;
        }
        foreach ($xml_entities as $xml_entity) {
            /** @var SimpleXMLElement $xmlEntity */
            $attributes = $xml_entity->children();
            /** @var ObjectModel $object */
            $retrieve_data = $this->resource_configuration['retrieveData'];
            if ($this->method == 'POST') {
                $object = new $retrieve_data['className']();
            } elseif ($this->method == 'PUT') {
                $object = new $retrieve_data['className']((int) $attributes->id);
                if (!$object->id) {
                    $this->set_error(404, 'Invalid ID', 92);
                    return false;
                }
            }
            $this->objects[] = $object;
            $i18n = false;
            // attributes
            foreach ($this->resource_configuration['fields'] as $field_name => $field_properties) {
                $sql_id = $field_properties['sqlId'];
                if ($field_name == 'id') {
                    $sql_id = $field_name;
                }
                if (isset($attributes->{$field_name}) && isset($field_properties['sqlId']) && (!isset($field_properties['i18n']) || !$field_properties['i18n'])) {
                    if (isset($field_properties['setter'])) {
                        // if we have to use a specific setter
                        if (!$field_properties['setter']) {
                            // if it's forbidden to set this field
                            $this->set_error(400, 'parameter "' . $field_name . '" not writable. Please remove this attribute of this XML', 93);
                            return false;
                        }
                        $setter = $field_properties['setter'];
                        $object->{$setter}((string) $attributes->{$field_name});
                    } elseif (property_exists($object, $sql_id)) {
                        $object->{$sql_id} = (string) $attributes->{$field_name};
                    } else {
                        $this->set_error(400, 'Parameter "' . $field_name . '" can\'t be set to the object "' . $this->resource_configuration['retrieveData']['className'] . '"', 123);
                    }
                } elseif (isset($field_properties['required']) && $field_properties['required'] && !$field_properties['i18n']) {
                    $this->set_error(400, 'parameter "' . $field_name . '" required', 41);
                    return false;
                } elseif ((!isset($field_properties['required']) || !$field_properties['required']) && property_exists($object, $sql_id)) {
                    $object->{$sql_id} = null;
                }
                if (isset($field_properties['i18n']) && $field_properties['i18n']) {
                    $i18n = true;
                    if (isset($attributes->{$field_name}, $attributes->{$field_name}->language)) {
                        foreach ($attributes->{$field_name}->language as $lang) {
                            /** @var SimpleXMLElement $lang */
                            $object->{$field_name}[(int) $lang->attributes()->id] = (string) $lang;
                        }
                    } else {
                        $object->{$field_name} = (string) $attributes->{$field_name};
                    }
                }
            }
            // Apply the modifiers if they exist
            foreach ($this->resource_configuration['fields'] as $field_properties) {
                if (isset($field_properties['modifier']) && isset($field_properties['modifier']['modifier']) && $field_properties['modifier']['http_method'] & constant('WebserviceRequest::HTTP_' . $this->method)) {
                    $object->{$field_properties['modifier']['modifier']}();
                }
            }
            if (!$this->has_errors()) {
                if ($i18n && ($ret_validate_fields_lang = $object->validate_fields_lang(false, true)) !== true) {
                    $this->set_error(400, 'Validation error: "' . $ret_validate_fields_lang . '"', 84);
                    return false;
                }
                if (($ret_validate_fields = $object->validate_fields(false, true)) !== true) {
                    $this->set_error(400, 'Validation error: "' . $ret_validate_fields . '"', 85);
                    return false;
                }
                // Call alternative method for add/update
                $object_method = $this->method == 'POST' ? 'add' : 'update';
                if (isset($this->resource_configuration['objectMethods']) && array_key_exists($object_method, $this->resource_configuration['objectMethods'])) {
                    $object_method = $this->resource_configuration['objectMethods'][$object_method];
                }
                $result = $object->{$object_method}();
                if ($result) {
                    if (isset($attributes->associations)) {
                        foreach ($attributes->associations->children() as $association) {
                            /** @var SimpleXMLElement $association */
                            // associations
                            if (isset($this->resource_configuration['associations'][$association->get_name()])) {
                                $assoc_items = $association->children();
                                $values = [];
                                foreach ($assoc_items as $assoc_item) {
                                    /** @var SimpleXMLElement $assocItem */
                                    $fields = $assoc_item->children();
                                    $entry = [];
                                    foreach ($fields as $field_name => $field_value) {
                                        $entry[$field_name] = (string) $field_value;
                                    }
                                    $values[] = $entry;
                                }
                                $setter = $this->resource_configuration['associations'][$association->get_name()]['setter'];
                                if ($setter && method_exists($object, $setter) && !$object->{$setter}($values)) {
                                    $this->set_error(500, 'Error occurred while setting the ' . $association->get_name() . ' value', 85);
                                    return false;
                                }
                            } elseif ($association->get_name() != 'i18n') {
                                $this->set_error(400, 'The association "' . $association->get_name() . '" does not exists', 86);
                                return false;
                            }
                        }
                    }
                    $assoc = Shop::get_asso_table($this->resource_configuration['retrieveData']['table']);
                    if ($assoc !== false && $assoc['type'] != 'fk_shop') {
                        // PUT nor POST is destructive, no deletion
                        $shop_ids = $this->get_shop_ids();
                        if ($shop_ids) {
                            $sql = 'INSERT IGNORE INTO `' . bq_sql(_DB_PREFIX_ . $this->resource_configuration['retrieveData']['table'] . '_' . $assoc['type']) . '` (id_shop, ' . p_sql($this->resource_configuration['fields']['id']['sqlId']) . ') VALUES ';
                            $data = [];
                            foreach ($shop_ids as $id) {
                                $data[] = '(' . (int) $id . ',' . (int) $object->id . ')';
                            }
                            Db::get_instance()->execute($sql . implode(', ', $data));
                        }
                    }
                } else {
                    $this->set_error(500, 'Unable to save resource', 46);
                }
            }
        }
        if (!$this->has_errors()) {
            $this->obj_output->set_status($success_return_code);
            return true;
        }
        return false;
    }
    /**
     * get SQL retrieve Filter
     *
     * @param string $sqlId
     * @param string $filterValue
     * @param string $tableAlias = 'main.'
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function get_sql_retrieve_filter($sql_id, $filter_value, string $table_alias = 'main.'): string
    {
        $ret = '';
        preg_match('/^(.*)\[(.*)\](.*)$/', $filter_value, $matches);
        if (count($matches) > 1) {
            if ($matches[1] == '%' || $matches[3] == '%') {
                $ret .= ' AND ' . bq_sql($table_alias) . '`' . bq_sql($sql_id) . '` LIKE "' . p_sql($matches[1] . $matches[2] . $matches[3]) . "\"\n";
            } elseif ($matches[1] == '' && $matches[3] == '') {
                if (strpos($matches[2], '|') > 0) {
                    $values = explode('|', $matches[2]);
                    $ret .= ' AND (';
                    $temp = '';
                    foreach ($values as $value) {
                        $temp .= bq_sql($table_alias) . '`' . bq_sql($sql_id) . '` = "' . bq_sql($value) . '" OR ';
                    }
                    $ret .= rtrim($temp, 'OR ') . ')' . "\n";
                } elseif (preg_match('/^([\d\.:\-\s]+),([\d\.:\-\s]+)$/', $matches[2], $matches3)) {
                    unset($matches3[0]);
                    if (count($matches3) > 0) {
                        sort($matches3);
                        $ret .= ' AND ' . $table_alias . '`' . bq_sql($sql_id) . '` BETWEEN "' . p_sql($matches3[0]) . '" AND "' . p_sql($matches3[1]) . "\"\n";
                    }
                } else {
                    $ret .= ' AND ' . $table_alias . '`' . bq_sql($sql_id) . '`="' . p_sql($matches[2]) . '"' . "\n";
                }
            } elseif ($matches[1] == '>') {
                $ret .= ' AND ' . $table_alias . '`' . bq_sql($sql_id) . '` > "' . p_sql($matches[2]) . "\"\n";
            } elseif ($matches[1] == '<') {
                $ret .= ' AND ' . $table_alias . '`' . bq_sql($sql_id) . '` < "' . p_sql($matches[2]) . "\"\n";
            } elseif ($matches[1] == '!') {
                $multiple_values = explode('|', $matches[2]);
                foreach ($multiple_values as $value) {
                    $ret .= ' AND ' . $table_alias . '`' . bq_sql($sql_id) . '` != "' . p_sql($value) . "\"\n";
                }
            }
        } else {
            $ret .= ' AND ' . $table_alias . '`' . bq_sql($sql_id) . '` ' . (Validate::is_float(p_sql($filter_value)) ? 'LIKE' : '=') . ' "' . p_sql($filter_value) . "\"\n";
        }
        return $ret;
    }
    /**
     * @return array|false
     */
    public function filter_language(): false|array
    {
        $arr_languages = [];
        $length_values = strlen((string) $this->url_fragments['language']);
        // if just one language is asked
        if (is_numeric($this->url_fragments['language'])) {
            $arr_languages[] = (int) $this->url_fragments['language'];
        } elseif (str_starts_with((string) $this->url_fragments['language'], '[') && strpos((string) $this->url_fragments['language'], ']') === $length_values - 1) {
            if (str_contains((string) $this->url_fragments['language'], '|') xor str_contains((string) $this->url_fragments['language'], ',')) {
                $params_values = str_replace([']', '['], '', $this->url_fragments['language']);
                // it's a list
                if (str_contains($params_values, '|')) {
                    $list_enabled_lang = explode('|', $params_values);
                    $arr_languages = $list_enabled_lang;
                } elseif (str_contains($params_values, ',')) {
                    $range_enabled_lang = explode(',', $params_values);
                    if (count($range_enabled_lang) != 2) {
                        $this->set_error(400, 'A range value for a language must contains only 2 values', 78);
                        return false;
                    }
                    for ($i = $range_enabled_lang[0]; $i <= $range_enabled_lang[1]; $i++) {
                        $arr_languages[] = $i;
                    }
                }
            } elseif (preg_match('#\[(\d)+\]#Ui', (string) $this->url_fragments['language'], $match_lang)) {
                $arr_languages[] = $match_lang[1];
            }
        } else {
            $this->set_error(400, 'language value is wrong', 79);
            return false;
        }
        $result = array_map(is_numeric(...), $arr_languages);
        if (array_search(false, $result, true)) {
            $this->set_error(400, 'Language ID must be numeric', 80);
            return false;
        }
        foreach ($arr_languages as $key => $id_lang) {
            if (!Language::get_language($id_lang)) {
                unset($arr_languages[$key]);
            }
        }
        return $arr_languages;
    }
    /**
     * Thanks to the (WebserviceOutputBuilder) WebserviceKey::objOutput
     * Method build the output depend on the WebserviceRequest::outputFormat
     * and set HTTP header parameters.
     *
     * @return array with displaying informations (used in the dispatcher).
     *
     * @throws PrestaShopException
     * @throws WebserviceException
     */
    protected function return_output(): array
    {
        $return = [];
        // write headers
        $time = round(microtime(true) - $this->_start_time, 3);
        $this->obj_output->set_header_params('Execution-Time', $time);
        $return['type'] = strtolower($this->output_format);
        // write this header only now (avoid hackers happiness...)
        if ($this->_authenticated) {
            $this->obj_output->set_header_params('PSWS-Version', _PS_VERSION_);
            $this->obj_output->set_header_params('TBWS-Version', _TB_VERSION_);
        }
        // If Specific Management is asked
        if ($this->object_specific_management instanceof Webservice_Specific_Management_Interface) {
            try {
                $return['content'] = $this->object_specific_management->get_content();
            } catch (Webservice_Exception $e) {
                if ($e->get_type() == Webservice_Exception::DID_YOU_MEAN) {
                    $this->set_error_did_you_mean($e->get_status(), $e->get_message(), $e->get_wrong_value(), $e->get_available_values(), $e->get_code());
                } elseif ($e->get_type() == Webservice_Exception::SIMPLE) {
                    $this->set_error($e->get_status(), $e->get_message(), $e->get_code());
                }
            }
        }
        // for use a general output
        if (!$this->has_errors() && $this->object_specific_management == null) {
            if (empty($this->objects)) {
                try {
                    $return['content'] = $this->obj_output->get_resources_list($this->key_permissions);
                } catch (Webservice_Exception $e) {
                    if ($e->get_type() == Webservice_Exception::DID_YOU_MEAN) {
                        $this->set_error_did_you_mean($e->get_status(), $e->get_message(), $e->get_wrong_value(), $e->get_available_values(), $e->get_code());
                    } elseif ($e->get_type() == Webservice_Exception::SIMPLE) {
                        $this->set_error($e->get_status(), $e->get_message(), $e->get_code());
                    }
                }
            } else {
                try {
                    if (isset($this->url_segment[1]) && !empty($this->url_segment[1])) {
                        $type_of_view = Webservice_Output_Builder::VIEW_DETAILS;
                    } else {
                        $type_of_view = Webservice_Output_Builder::VIEW_LIST;
                    }
                    if (in_array($this->method, ['PUT', 'POST'])) {
                        $type_of_view = Webservice_Output_Builder::VIEW_DETAILS;
                        $this->fields_to_display = 'full';
                    }
                    $return['content'] = $this->obj_output->get_content($this->objects, $this->schema_to_display, $this->fields_to_display, $this->depth, $type_of_view);
                } catch (Webservice_Exception $e) {
                    if ($e->get_type() == Webservice_Exception::DID_YOU_MEAN) {
                        $this->set_error_did_you_mean($e->get_status(), $e->get_message(), $e->get_wrong_value(), $e->get_available_values(), $e->get_code());
                    } elseif ($e->get_type() == Webservice_Exception::SIMPLE) {
                        $this->set_error($e->get_status(), $e->get_message(), $e->get_code());
                    }
                } catch (Exception $e) {
                    $this->set_error(500, $e->get_message(), $e->get_code());
                }
            }
        }
        // if the output is not enable, delete the content
        // the type content too
        if (!$this->_output_enabled) {
            unset($return['type']);
            if (isset($return['content'])) {
                unset($return['content']);
            }
        } elseif (isset($return['content'])) {
            $this->obj_output->set_header_params('Content-Sha1', sha1($return['content']));
        }
        // if errors happens when creating returned xml,
        // the usual xml content is replaced by the nice error handler content
        if ($this->has_errors()) {
            $this->_output_enabled = true;
            $return['content'] = $this->obj_output->get_errors($this->errors);
        }
        if (!isset($return['content']) || strlen($return['content']) <= 0) {
            $this->obj_output->set_header_params('Content-Type', '');
        }
        $return['headers'] = $this->obj_output->build_header();
        $logger = $this->get_logger();
        $response = $return['content'] ?? null;
        $logger->log_response($response, $this->errors, $time);
        return $return;
    }
    public static function get_all_headers(): array
    {
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
        } else {
            $headers = array_merge($_ENV, $_SERVER);
            foreach ($headers as $key => $val) {
                //we need this header
                if (str_contains(strtolower((string) $key), 'content-type')) {
                    continue;
                }
                if (strtoupper(substr((string) $key, 0, 5)) != 'HTTP_') {
                    unset($headers[$key]);
                }
            }
        }
        //Normalize this array to Cased-Like-This structure.
        $retarr = [];
        foreach ($headers as $key => $value) {
            $key = preg_replace('/^HTTP_/i', '', (string) $key);
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace(['-', '_'], ' ', $key))));
            $retarr[$key] = $value;
        }
        ksort($retarr);
        return $retarr;
    }
    /**
     * @return array
     */
    private static function get_webservice_headers()
    {
        static $headers = null;
        if (is_null($headers)) {
            $interesting = [static::HEADER_OUTPUT_FORMAT, static::HEADER_IO_FORMAT];
            $headers = array_intersect_key(static::get_all_headers(), array_flip($interesting));
        }
        return $headers;
    }
    /**
     * @return WebserviceLogger
     */
    protected function get_logger()
    {
        if (is_null($this->logger)) {
            $this->logger = new Webservice_Logger();
        }
        return $this->logger;
    }
    public function get_shop_ids(): array
    {
        if (is_array(static::$shop_i_ds)) {
            return static::$shop_i_ds;
        }
        return [];
    }
    /**
     * @throws PrestaShopException
     */
    public function get_webservice_key(): Webservice_Key
    {
        $key = Webservice_Key::get_instance_by_key($this->_key);
        if (!$key) {
            throw new Presta_Shop_Exception('Webservice key not assigned');
        }
        return $key;
    }
}