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
 * Class WebserviceOutputBuilderCore
 */
class Webservice_Output_Builder_Core
{
    /**
     * @var int constant
     */
    public const VIEW_LIST = 1;
    public const VIEW_DETAILS = 2;
    /**
     * @var string
     */
    protected $output;
    /**
     * @var WebserviceOutputInterface
     */
    public $object_render;
    /**
     * @var array
     */
    protected $ws_resource;
    /**
     * @var int
     */
    protected $depth = 0;
    /**
     * @var string
     */
    protected $schema_to_display;
    /**
     * @var array|string
     */
    protected $fields_to_display;
    /**
     * @var array
     */
    protected $specific_fields = [];
    /**
     * @var array
     */
    protected $virtual_fields = [];
    protected int $status_int;
    /**
     * @var array
     */
    protected $ws_param_overrides;
    /**
     * @var array
     */
    protected static $_cache_ws_parameters = [];
    /* Header properties */
    /**
     * @var array
     */
    protected $header_params = [];
    /**
     * @var string Status header sent at return
     */
    protected string $status;
    /**
     * WebserviceOutputBuilderCore constructor.
     *
     * @param string $wsUrl
     */
    public function __construct(protected $ws_url)
    {
        $this->status_int = 200;
        $this->status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
        $this->ws_param_overrides = [];
        $this->header_params['Access-Time'] = time();
    }
    /**
     * Set the render object for set the output format.
     * Set the Content-type for the http header.
     *
     *
     * @throws WebserviceException if the object render is not an instance of WebserviceOutputInterface
     * @throws WebserviceException
     */
    public function set_object_render(Webservice_Output_Interface $obj_render): static
    {
        $this->object_render = $obj_render;
        $this->object_render->set_ws_url($this->ws_url);
        if ($this->object_render->get_content_type()) {
            $this->set_header_params('Content-Type', $this->object_render->get_content_type());
        }
        return $this;
    }
    /**
     * getter
     *
     * @return WebserviceOutputInterface
     */
    public function get_object_render()
    {
        return $this->object_render;
    }
    /**
     * Need to have the resource list to get the class name for an entity,
     * To build
     *
     * @param array $resources
     */
    public function set_ws_resources($resources): static
    {
        $this->ws_resource = $resources;
        return $this;
    }
    /**
     * This method return an array with each http header params for a content.
     * This check each required params.
     *
     * If this method is overrided don't forget to check required specific params (for xml etc...)
     */
    public function build_header(): array
    {
        $return = [];
        $return[] = $this->status;
        foreach ($this->header_params as $key => $param) {
            $return[] = trim((string) $key) . ': ' . $param;
        }
        return $return;
    }
    /**
     * @param string $key The normalized key expected for an http response
     * @param string $value
     *
     * @throws WebserviceException If the key or the value are corrupted (use Validate::isCleanHtml method)
     */
    public function set_header_params($key, $value): static
    {
        if (!Validate::is_clean_html($key) || !Validate::is_clean_html($value)) {
            throw new Webservice_Exception('the key or your value is corrupted.', [94, 500]);
        }
        $this->header_params[$key] = $value;
        return $this;
    }
    /**
     * @param string|null $key if null get all header params otherwise the params specified by the key
     *
     * @throws WebserviceException if the key is corrupted (use Validate::isCleanHtml method)
     * @throws WebserviceException if the asked key does'nt exists.
     * @return array|string
     *
     * @throws WebserviceException
     */
    public function get_header_params($key = null)
    {
        if (!is_null($key)) {
            if (!Validate::is_clean_html($key)) {
                throw new Webservice_Exception('the key you write is a corrupted text.', [95, 500]);
            }
            if (!array_key_exists($key, $this->header_params)) {
                throw new Webservice_Exception(sprintf('The key %s does\'nt exist', $key), [96, 500]);
            }
            return $this->header_params[$key];
        }
        return $this->header_params;
    }
    /**
     * Delete all Header parameters previously set.
     */
    public function reset_header_params(): static
    {
        $this->header_params = [];
        return $this;
    }
    /**
     * @return string the normalized status for http request
     */
    public function get_status()
    {
        return $this->status;
    }
    /**
     * @return int
     */
    public function get_status_int()
    {
        return $this->status_int;
    }
    /**
     * Set the return header status
     *
     * @param int $num the Http status code
     */
    public function set_status($num): void
    {
        $this->status_int = (int) $num;
        switch ($num) {
            case 200:
                $this->status = $_SERVER['SERVER_PROTOCOL'] . ' 200 OK';
                break;
            case 201:
                $this->status = $_SERVER['SERVER_PROTOCOL'] . ' 201 Created';
                break;
            case 204:
                $this->status = $_SERVER['SERVER_PROTOCOL'] . ' 204 No Content';
                break;
            case 304:
                $this->status = $_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified';
                break;
            case 400:
                $this->status = $_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request';
                break;
            case 401:
                $this->status = $_SERVER['SERVER_PROTOCOL'] . ' 401 Unauthorized';
                break;
            case 403:
                $this->status = $_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden';
                break;
            case 404:
                $this->status = $_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found';
                break;
            case 405:
                $this->status = $_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed';
                break;
            case 500:
                $this->status = $_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error';
                break;
            case 501:
                $this->status = $_SERVER['SERVER_PROTOCOL'] . ' 501 Not Implemented';
                break;
            case 503:
                $this->status = $_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable';
                break;
        }
    }
    /**
     * Build errors output using an error array
     *
     * @param array $errors
     *
     * @return string output in the format specified by WebserviceOutputBuilder::objectRender
     */
    public function get_errors($errors)
    {
        if (!empty($errors)) {
            if (isset($this->object_render)) {
                $str_output = $this->object_render->render_errors_header();
                foreach ($errors as $error) {
                    if (is_array($error)) {
                        $code = $error[0];
                        $message = $error[1];
                        $extra = $error[2] ?? [];
                        $str_output .= $this->object_render->render_errors($message, $code, $extra);
                    } else {
                        $str_output .= $this->object_render->render_errors($error);
                    }
                }
                $str_output .= $this->object_render->render_errors_footer();
                $str_output = $this->object_render->override_content($str_output);
            } else {
                $str_output = '<pre>' . print_r($errors, true) . '</pre>';
            }
        }
        return $str_output;
    }
    /**
     * Build the resource list in the output format specified by WebserviceOutputBuilder::objectRender
     *
     *
     * @return string
     *
     * @throws WebserviceException
     * @throws PrestaShopException
     */
    public function get_resources_list(array $key_permissions)
    {
        if (is_null($this->ws_resource)) {
            throw new Webservice_Exception('You must set web service resource for get the resources list.', [82, 500]);
        }
        $output = '';
        $more_attr = ['shopName' => htmlspecialchars(Configuration::get('PS_SHOP_NAME'))];
        $output .= $this->object_render->render_node_header('api', [], $more_attr);
        foreach ($this->ws_resource as $resource_name => $resource) {
            if (in_array($resource_name, array_keys($key_permissions))) {
                $more_attr = ['xlink_resource' => $this->ws_url . $resource_name, 'get' => in_array('GET', $key_permissions[$resource_name]) ? 'true' : 'false', 'put' => in_array('PUT', $key_permissions[$resource_name]) ? 'true' : 'false', 'post' => in_array('POST', $key_permissions[$resource_name]) ? 'true' : 'false', 'delete' => in_array('DELETE', $key_permissions[$resource_name]) ? 'true' : 'false', 'head' => in_array('HEAD', $key_permissions[$resource_name]) ? 'true' : 'false'];
                $output .= $this->object_render->render_node_header($resource_name, [], $more_attr);
                $output .= $this->object_render->render_node_header('description', [], $more_attr);
                $output .= $resource['description'];
                $output .= $this->object_render->render_node_footer('description', []);
                if (!isset($resource['specific_management']) || !$resource['specific_management']) {
                    $more_attr_schema = ['xlink_resource' => $this->ws_url . $resource_name . '?schema=blank', 'type' => 'blank'];
                    $output .= $this->object_render->render_node_header('schema', [], $more_attr_schema, false);
                    $more_attr_schema = ['xlink_resource' => $this->ws_url . $resource_name . '?schema=synopsis', 'type' => 'synopsis'];
                    $output .= $this->object_render->render_node_header('schema', [], $more_attr_schema, false);
                }
                $output .= $this->object_render->render_node_footer($resource_name, []);
            }
        }
        $output .= $this->object_render->render_node_footer('api', []);
        return $this->object_render->override_content($output);
    }
    /**
     * @param object $wsrObject
     * @param string $method
     */
    public function register_override_ws_parameters($wsr_object, $method): void
    {
        $this->ws_param_overrides[] = ['object' => $wsr_object, 'method' => $method];
    }
    /**
     * Method is used for each content type
     * Different content types are :
     *        - list of entities,
     *        - tree diagram of entity details (full or minimum),
     *        - schema (synopsis & blank),
     *
     * @param array $objects each object created by entity asked
     *
     * @param string|null $schemaToDisplay if null display the entities list or entity details.
     * @param string|array $fieldsToDisplay the fields allow for the output
     * @param int $depth depth for the tree diagram output.
     * @param int $typeOfView use the 2 constants WebserviceOutputBuilder::VIEW_LIST WebserviceOutputBuilder::VIEW_DETAILS
     * @param bool $override
     * @return string in the output format specified by WebserviceOutputBuilder::objectRender
     *
     * @throws PrestaShopDatabaseException
     * @throws WebserviceException
     * @throws PrestaShopException
     * @see WebserviceOutputBuilder::executeEntityGetAndHead
     */
    public function get_content(array $objects, $schema_to_display = null, $fields_to_display = 'minimum', $depth = 0, $type_of_view = self::VIEW_LIST, $override = true)
    {
        $this->fields_to_display = $fields_to_display;
        $this->depth = $depth;
        $output = '';
        if ($schema_to_display != null) {
            $this->schema_to_display = $schema_to_display;
            $this->object_render->set_schema_to_display($this->schema_to_display);
            // If a shema is asked the view must be an details type
            $type_of_view = static::VIEW_DETAILS;
        }
        $class = $objects['empty']::class;
        if (!isset(Webservice_Output_Builder::$_cache_ws_parameters[$class])) {
            Webservice_Output_Builder::$_cache_ws_parameters[$class] = $objects['empty']->get_webservice_parameters();
        }
        $ws_params = Webservice_Output_Builder::$_cache_ws_parameters[$class];
        foreach ($this->ws_param_overrides as $p) {
            $object = $p['object'];
            $method = $p['method'];
            $ws_params = $object->{$method}($ws_params);
        }
        // If a list is asked, need to wrap with a plural node
        if ($type_of_view === static::VIEW_LIST) {
            $output .= $this->set_indent($depth) . $this->object_render->render_node_header($ws_params['objectsNodeName'], $ws_params);
        }
        if (is_null($this->schema_to_display)) {
            foreach ($objects as $key => $object) {
                if ($key !== 'empty') {
                    if ($this->fields_to_display === 'minimum') {
                        $output .= $this->render_entity_minimum($object, $depth);
                    } else {
                        $output .= $this->render_entity($object, $depth);
                    }
                }
            }
        } else {
            $output .= $this->render_schema($objects['empty'], $ws_params);
        }
        // If a list is asked, need to wrap with a plural node
        if ($type_of_view === static::VIEW_LIST) {
            $output .= $this->set_indent($depth) . $this->object_render->render_node_footer($ws_params['objectsNodeName'], $ws_params);
        }
        if ($override) {
            return $this->object_render->override_content($output);
        }
        return $output;
    }
    /**
     * Create the tree diagram with no details
     *
     * @param ObjectModel $object create by the entity
     * @param int $depth the depth for the tree diagram
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function render_entity_minimum($object, $depth): string
    {
        $class = $object::class;
        if (!isset(Webservice_Output_Builder::$_cache_ws_parameters[$class])) {
            Webservice_Output_Builder::$_cache_ws_parameters[$class] = $object->get_webservice_parameters();
        }
        $ws_params = Webservice_Output_Builder::$_cache_ws_parameters[$class];
        $more_attr['id'] = $object->id;
        $more_attr['xlink_resource'] = $this->ws_url . $ws_params['objectsNodeName'] . '/' . $object->id;
        return $this->set_indent($depth) . $this->object_render->render_node_header($ws_params['objectNodeName'], $ws_params, $more_attr, false);
    }
    /**
     * Build a schema blank or synopsis
     *
     * @param ObjectModel $object create by the entity
     * @param array $wsParams webserviceParams from the entity
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws WebserviceException
     * @throws PrestaShopException
     */
    protected function render_schema($object, array $ws_params): string
    {
        $output = $this->object_render->render_node_header($ws_params['objectNodeName'], $ws_params);
        foreach ($ws_params['fields'] as $field_name => $field) {
            $output .= $this->render_field($object, $ws_params, $field_name, $field, 0);
        }
        if (isset($ws_params['associations']) && count($ws_params['associations']) > 0) {
            $this->fields_to_display = 'full';
            $output .= $this->render_associations($object, 0, $ws_params['associations'], $ws_params);
        }
        return $output . $this->object_render->render_node_footer($ws_params['objectNodeName'], $ws_params);
    }
    /**
     * Build the entity detail.
     *
     * @param ObjectModel $object create by the entity
     * @param int $depth the depth for the tree diagram
     *
     *
     * @throws WebserviceException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function render_entity($object, $depth): string
    {
        $output = '';
        $class = $object::class;
        if (!isset(Webservice_Output_Builder::$_cache_ws_parameters[$class])) {
            Webservice_Output_Builder::$_cache_ws_parameters[$class] = $object->get_webservice_parameters();
        }
        $ws_params = Webservice_Output_Builder::$_cache_ws_parameters[$class];
        foreach ($this->ws_param_overrides as $p) {
            $o = $p['object'];
            $method = $p['method'];
            $ws_params = $o->{$method}($ws_params);
        }
        $output .= $this->set_indent($depth) . $this->object_render->render_node_header($ws_params['objectNodeName'], $ws_params);
        if ($object->id != 0) {
            // This to add virtual Fields for a particular entity.
            $virtual_fields = $this->add_virtual_fields($ws_params['objectsNodeName'], $object);
            if (!empty($virtual_fields)) {
                $ws_params['fields'] = array_merge($ws_params['fields'], $virtual_fields);
            }
            foreach ($ws_params['fields'] as $field_name => $field) {
                if ($this->fields_to_display === 'full' || array_key_exists($field_name, $this->fields_to_display)) {
                    $field['object_id'] = $object->id;
                    $field['entity_name'] = $ws_params['objectNodeName'];
                    $field['entities_name'] = $ws_params['objectsNodeName'];
                    $output .= $this->render_field($object, $ws_params, $field_name, $field, $depth);
                }
            }
        }
        $subexists = false;
        if (is_array($this->fields_to_display)) {
            foreach ($this->fields_to_display as $fields) {
                if (is_array($fields)) {
                    $subexists = true;
                }
            }
        }
        if (isset($ws_params['associations']) && ($this->fields_to_display == 'full' || $subexists)) {
            $output .= $this->render_associations($object, $depth, $ws_params['associations'], $ws_params);
        }
        return $output . ($this->set_indent($depth) . $this->object_render->render_node_footer($ws_params['objectNodeName'], $ws_params));
    }
    /**
     * Build a field and use recursivity depend on the depth parameter.
     *
     * @param ObjectModel $object create by the entity
     * @param array $wsParams webserviceParams from the entity
     * @param string $fieldName
     * @param array $field
     * @param int $depth
     *
     * @return string
     */
    protected function render_field($object, array $ws_params, $field_name, $field, $depth)
    {
        $output = '';
        $show_field = true;
        if (isset($ws_params['hidden_fields']) && in_array($field_name, $ws_params['hidden_fields'])) {
            return;
        }
        if ($this->schema_to_display === 'synopsis') {
            $field['synopsis_details'] = $this->get_synopsis_details($field);
            if ($field_name === 'id') {
                $show_field = false;
            }
        }
        if ($this->schema_to_display === 'blank') {
            if (isset($field['setter']) && !$field['setter']) {
                $show_field = false;
            }
        }
        // don't set any value for a schema
        if (isset($field['synopsis_details']) || $this->schema_to_display === 'blank') {
            $field['value'] = '';
            if (isset($field['xlink_resource'])) {
                unset($field['xlink_resource']);
            }
        } elseif (isset($field['getter']) && $object != null && method_exists($object, $field['getter'])) {
            $field_getter = $field['getter'];
            $field['value'] = $object->{$field_getter}();
        } elseif (!isset($field['value'])) {
            $field['value'] = $object->{$field_name};
        }
        // this apply specific function for a particular field on a choosen entity
        $field = $this->override_specific_field($ws_params['objectsNodeName'], $field_name, $field, $object, $ws_params);
        // don't display informations for a not existant id
        if (str_starts_with((string) $field['sqlId'], 'id_') && !$field['value']) {
            if ($field['value'] === null) {
                $field['value'] = '';
            }
            // delete the xlink except for schemas
            if (isset($field['xlink_resource']) && is_null($this->schema_to_display)) {
                unset($field['xlink_resource']);
            }
        }
        // set "id" for each node name which display the id of the entity
        if ($field_name === 'id') {
            $field['sqlId'] = 'id';
        }
        // don't display the node id for a synopsis schema
        if ($show_field) {
            $output .= $this->set_indent($depth - 1) . $this->object_render->render_field($field);
        }
        return $output;
    }
    /**
     * @param ObjectModel $object
     * @param int $depth
     * @param array $associations
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws WebserviceException
     * @throws PrestaShopException
     */
    protected function render_associations($object, $depth, $associations, array $ws_params): string
    {
        $output = $this->object_render->render_association_wrapper_header();
        foreach ($associations as $assoc_name => $association) {
            if ($this->fields_to_display == 'full' || is_array($this->fields_to_display) && array_key_exists($assoc_name, $this->fields_to_display)) {
                $getter = $association['getter'];
                $objects_assoc = [];
                if (isset($association['fields']) && is_array($association['fields']) && $association['fields']) {
                    $fields_assoc = $association['fields'];
                } else {
                    $fields_assoc = ['id' => []];
                }
                $parent_details = ['object_id' => $object->id, 'entity_name' => $ws_params['objectNodeName'], 'entities_name' => $ws_params['objectsNodeName']];
                if (is_array($getter)) {
                    $association_resources = call_user_func($getter, $object);
                    if (is_array($association_resources) && !empty($association_resources)) {
                        foreach ($association_resources as $association_resource) {
                            $objects_assoc[] = $association_resource;
                        }
                    }
                } else if (method_exists($object, $getter) && is_null($this->schema_to_display)) {
                    $association_resources = $object->{$getter}();
                    if (is_array($association_resources) && !empty($association_resources)) {
                        foreach ($association_resources as $association_resource) {
                            $objects_assoc[] = $association_resource;
                        }
                    }
                } else {
                    $objects_assoc[] = '';
                }
                $class_name = null;
                if (isset($this->ws_resource[$assoc_name]['class']) && class_exists($this->ws_resource[$assoc_name]['class'], true)) {
                    $class_name = $this->ws_resource[$assoc_name]['class'];
                }
                $output_details = '';
                foreach ($objects_assoc as $object_assoc) {
                    if ($depth == 0 || $class_name === null) {
                        $value = null;
                        if (!empty($object_assoc)) {
                            if (is_array($object_assoc)) {
                                $value = $object_assoc;
                            } else {
                                $value = ['id' => $object_assoc];
                            }
                        }
                        $output_details .= $this->render_flat_association($object, $depth, $assoc_name, $association['resource'], $fields_assoc, $value, $parent_details);
                    } else {
                        foreach ($object_assoc as $id) {
                            $child_object = new $class_name($id);
                            $output_details .= $this->render_entity($child_object, $depth - 2 ? 0 : $depth - 2);
                        }
                    }
                }
                if ($output_details != '') {
                    $output .= $this->set_indent($depth) . $this->object_render->render_association_header($object, $ws_params, $assoc_name);
                    $output .= $output_details;
                    $output .= $this->set_indent($depth) . $this->object_render->render_association_footer($object, $ws_params, $assoc_name);
                } else {
                    $output .= $this->set_indent($depth) . $this->object_render->render_association_header($object, $ws_params, $assoc_name, true);
                }
            }
        }
        return $output . $this->object_render->render_association_wrapper_footer();
    }
    /**
     * @param ObjectModel $object
     * @param int $depth
     * @param string $assocName
     * @param string $resourceName
     * @param array $fieldsAssoc
     * @param array $objectAssoc
     *
     */
    protected function render_flat_association($object, $depth, $assoc_name, $resource_name, $fields_assoc, $object_assoc, array $parent_details): string
    {
        $output = '';
        $more_attr = [];
        if (isset($this->ws_resource[$assoc_name]) && is_null($this->schema_to_display)) {
            if ($assoc_name == 'images') {
                if ($parent_details['entities_name'] == 'combinations') {
                    /** @var Combination $object */
                    $more_attr['xlink_resource'] = $this->ws_url . $assoc_name . '/products/' . $object->id_product . '/' . $object_assoc['id'];
                } else {
                    $more_attr['xlink_resource'] = $this->ws_url . $assoc_name . '/' . $parent_details['entities_name'] . '/' . $parent_details['object_id'] . '/' . $object_assoc['id'];
                }
            } else {
                $more_attr['xlink_resource'] = $this->ws_url . $assoc_name . '/' . $object_assoc['id'];
            }
        }
        $output .= $this->set_indent($depth - 1) . $this->object_render->render_node_header($resource_name, [], $more_attr);
        foreach ($fields_assoc as $field_name => $field) {
            if (!is_array($this->fields_to_display) || in_array($field_name, $this->fields_to_display[$assoc_name])) {
                if (!isset($field['sqlId'])) {
                    $field['sqlId'] = $field_name;
                }
                if (!isset($field['value']) && is_array($object_assoc) && array_key_exists($field_name, $object_assoc)) {
                    $field['value'] = $object_assoc[$field_name];
                }
                $field['entities_name'] = $assoc_name;
                $field['entity_name'] = $resource_name;
                if (!is_null($this->schema_to_display)) {
                    $field['synopsis_details'] = $this->get_synopsis_details($field);
                }
                $field['is_association'] = true;
                $output .= $this->set_indent($depth - 1) . $this->object_render->render_field($field);
            }
        }
        return $output . ($this->set_indent($depth - 1) . $this->object_render->render_node_footer($resource_name, []));
    }
    /**
     * @param int $depth
     */
    public function set_indent($depth): string
    {
        $number_of_tabs = max($this->depth - $depth, 0);
        return str_repeat("\t", $number_of_tabs);
    }
    public function get_synopsis_details(array $field): array
    {
        $arr_details = [];
        if (array_key_exists('required', $field) && $field['required']) {
            $arr_details['required'] = 'true';
        }
        if (array_key_exists('maxSize', $field) && $field['maxSize']) {
            $arr_details['maxSize'] = $field['maxSize'];
        }
        if (array_key_exists('validateMethod', $field) && $field['validateMethod']) {
            $arr_details['format'] = $field['validateMethod'];
        }
        if (array_key_exists('setter', $field) && !$field['setter']) {
            $arr_details['readOnly'] = 'true';
        }
        return $arr_details;
    }
    /**
     * @param string|object $object
     * @param string $method
     * @param string $fieldName
     * @param string $entityName
     *
     * @throws WebserviceException
     */
    public function set_specific_field($object, $method, $field_name, $entity_name): static
    {
        $this->validate_object_and_method($object, $method);
        $this->specific_fields[$field_name] = ['entity' => $entity_name, 'object' => $object, 'method' => $method, 'type' => gettype($object)];
        return $this;
    }
    /**
     * @param object|string $object
     * @param string $method
     *
     * @throws WebserviceException
     */
    protected function validate_object_and_method($object, $method)
    {
        if (is_string($object) && !class_exists($object)) {
            throw new Webservice_Exception('The object you want to set in ' . __METHOD__ . ' is not allowed.', [98, 500]);
        }
        if (!method_exists($object, $method)) {
            throw new Webservice_Exception('The method you want to set in ' . __METHOD__ . ' is not allowed.', [99, 500]);
        }
    }
    /**
     * @return array
     */
    public function get_specific_field()
    {
        return $this->specific_fields;
    }
    /**
     * @param string $entityName
     * @param string $fieldName
     * @param array $field
     * @param ObjectModel $entityObject
     * @param array $wsParams
     *
     * @return array
     */
    protected function override_specific_field($entity_name, $field_name, $field, $entity_object, $ws_params)
    {
        if (array_key_exists($field_name, $this->specific_fields)) {
            $specific_fields_field_name = $this->specific_fields[$field_name];
            if ($specific_fields_field_name['entity'] == $entity_name) {
                if ($specific_fields_field_name['type'] == 'string') {
                    $object = new $specific_fields_field_name['object']();
                } elseif ($specific_fields_field_name['type'] == 'object') {
                    $object = $specific_fields_field_name['object'];
                }
                if (isset($object)) {
                    $method = $specific_fields_field_name['method'];
                    $field = $object->{$method}($field, $entity_object, $ws_params);
                }
            }
        }
        return $field;
    }
    /**
     * @param object|string $object
     * @param string $method
     * @param string $entityName
     * @param string $parameters
     *
     * @throws WebserviceException
     */
    public function set_virtual_field($object, $method, $entity_name, $parameters): void
    {
        $this->validate_object_and_method($object, $method);
        $this->virtual_fields[$entity_name][] = ['parameters' => $parameters, 'object' => $object, 'method' => $method, 'type' => gettype($object)];
    }
    /**
     * @return array
     */
    public function get_virtual_fields()
    {
        return $this->virtual_fields;
    }
    /**
     * @param string $entityName
     * @param ObjectModel $entityObject
     *
     * @throws WebserviceException
     */
    public function add_virtual_fields($entity_name, $entity_object): array
    {
        $arr_return = [];
        $virtual_fields = $this->get_virtual_fields();
        if (array_key_exists($entity_name, $virtual_fields)) {
            foreach ($virtual_fields[$entity_name] as $function_infos) {
                if ($function_infos['type'] == 'string') {
                    $object = new $function_infos['object']();
                } elseif ($function_infos['type'] == 'object') {
                    $object = $function_infos['object'];
                }
                $method = $function_infos['method'];
                $return_fields = $object->{$method}($entity_object, $function_infos['parameters']);
                foreach ($return_fields as $field_name => $value) {
                    if (Validate::is_config_name($field_name)) {
                        $arr_return[$field_name] = $value;
                    } else {
                        throw new Webservice_Exception('Name for the virtual field is not allow', [128, 400]);
                    }
                }
            }
        }
        return $arr_return;
    }
    /**
     * @param array|string $fields
     */
    public function set_fields_to_display($fields): void
    {
        $this->fields_to_display = $fields;
    }
}