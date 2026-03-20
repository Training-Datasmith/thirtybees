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
 * Class Core_Foundation_IoC_Container
 */
class Core_foundation_io_C_container
{
    /**
     * List of services and instruction about their creation
     *
     * @var array
     */
    protected $bindings = [];
    /**
     * List of service instances
     *
     * @var array
     */
    protected $instances = [];
    /**
     * List of namespace aliases, currently unused by core
     *
     * @var array
     */
    protected $namespace_aliases = [];
    /**
     * @param string $serviceName
     *
     * @return bool
     */
    public function knows($service_name)
    {
        return array_key_exists($service_name, $this->bindings);
    }
    /**
     * @param string $alias
     *
     * @return bool
     */
    protected function knows_namespace_alias($alias)
    {
        return array_key_exists($alias, $this->namespace_aliases);
    }
    /**
     * @param string $serviceName
     * @param string|callable|object $constructor
     * @param bool $shared
     *
     * @return static
     */
    public function bind($service_name, $constructor, $shared = false)
    {
        if (!$this->knows($service_name)) {
            $this->bindings[$service_name] = ['constructor' => $constructor, 'shared' => $shared];
        }
        return $this;
    }
    /**
     * @param string $alias
     * @param string $namespacePrefix
     *
     * @return static
     * @throws Core_Foundation_IoC_Exception
     */
    public function alias_namespace($alias, $namespace_prefix)
    {
        if ($this->knows_namespace_alias($alias)) {
            throw new Core_foundation_io_C_exception(sprintf('Namespace alias `%1$s` already exists and points to `%2$s`', $alias, $this->namespace_aliases[$alias]));
        }
        $this->namespace_aliases[$alias] = $namespace_prefix;
        return $this;
    }
    /**
     * @param string $className
     *
     * @return string
     */
    public function resolve_class_name($class_name)
    {
        $colon_pos = strpos($class_name, ':');
        if (0 !== $colon_pos) {
            $alias = substr($class_name, 0, $colon_pos);
            if ($this->knows_namespace_alias($alias)) {
                $class = ltrim(substr($class_name, $colon_pos + 1), '\\');
                return $this->namespace_aliases[$alias] . '\\' . $class;
            }
        }
        return $class_name;
    }
    /**
     * @param string $className
     * @param array $alreadySeen
     *
     * @return object
     * @throws Core_Foundation_IoC_Exception
     */
    protected function make_instance_from_class_name($class_name, array $already_seen)
    {
        $class_name = $this->resolve_class_name($class_name);
        try {
            $refl = new ReflectionClass($class_name);
            $args = [];
            if ($refl->is_abstract()) {
                throw new Core_foundation_io_C_exception(sprintf('Cannot build abstract class: `%s`.', $class_name));
            }
            $class_constructor = $refl->get_constructor();
            if ($class_constructor) {
                foreach ($class_constructor->get_parameters() as $param) {
                    $param_class = $this->get_parameter_class_name($param);
                    if ($param_class) {
                        $args[] = $this->do_make($param_class, $already_seen);
                    } elseif ($param->is_default_value_available()) {
                        try {
                            $args[] = $param->get_default_value();
                        } catch (Exception $e) {
                            throw new Core_foundation_io_C_exception('Failed to resolve default parameter', 0, $e);
                        }
                    } else {
                        throw new Core_foundation_io_C_exception(sprintf('Cannot build a `%s`.', $class_name));
                    }
                }
            }
            if (count($args) > 0) {
                return $refl->new_instance_args($args);
            } else {
                // newInstanceArgs with empty array fails in PHP 5.3 when the class
                // doesn't have an explicitly defined constructor
                return $refl->new_instance();
            }
        } catch (Reflection_Exception $re) {
            throw new Core_foundation_io_C_exception(sprintf('This doesn\'t seem to be a class name: `%s`.', $class_name), 0, $re);
        }
    }
    /**
     * @param string $serviceName
     * @param array $alreadySeen
     *
     * @return mixed|object
     * @throws Core_Foundation_IoC_Exception
     */
    protected function do_make($service_name, array $already_seen = [])
    {
        if (array_key_exists($service_name, $already_seen)) {
            throw new Core_foundation_io_C_exception(sprintf('Cyclic dependency detected while building `%s`.', $service_name));
        }
        $already_seen[$service_name] = true;
        if (!$this->knows($service_name)) {
            $this->bind($service_name, $service_name);
        }
        $binding = $this->bindings[$service_name];
        if ($binding['shared'] && array_key_exists($service_name, $this->instances)) {
            return $this->instances[$service_name];
        } else {
            $constructor = $binding['constructor'];
            if (is_callable($constructor)) {
                $service = call_user_func($constructor);
            } elseif (!is_string($constructor)) {
                // user already provided the value, no need to construct it.
                $service = $constructor;
            } else {
                // assume the $constructor is a class name
                $service = $this->make_instance_from_class_name($constructor, $already_seen);
            }
            if ($binding['shared']) {
                $this->instances[$service_name] = $service;
            }
            return $service;
        }
    }
    /**
     * @param string $serviceName
     *
     * @return mixed|object
     *
     * @throws Core_Foundation_IoC_Exception
     */
    public function make($service_name)
    {
        return $this->do_make($service_name, []);
    }
    /**
     * Returns parameter class name, or null
     *
     * @param ReflectionParameter $param
     * @return string|null
     */
    protected function get_parameter_class_name(ReflectionParameter $param)
    {
        if (PHP_VERSION_ID > 80000) {
            $type = $param->get_type();
            if ($type instanceof ReflectionNamedType) {
                return $type->get_name();
            }
        } else {
            $param_class = $param->get_class();
            if ($param_class) {
                return $param_class->get_name();
            }
        }
        return null;
    }
}