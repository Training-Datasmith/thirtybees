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
 * Class Core_Foundation_Database_EntityRepository
 */
class Core_foundation_database_entity_Repository
{
    /**
     * @var Core_Foundation_Database_EntityManager
     */
    protected $entity_manager;
    /**
     * @var Core_Foundation_Database_DatabaseInterface
     */
    protected $db;
    /**
     * @var string
     */
    protected $tables_prefix;
    /**
     * @var Core_Foundation_Database_EntityMetaData
     */
    protected $entity_meta_data;
    /**
     * @var Core_Foundation_Database_EntityManager_QueryBuilder
     */
    protected $query_builder;
    /**
     * Core_Foundation_Database_EntityRepository constructor.
     *
     * @param Core_Foundation_Database_EntityManager $entityManager
     * @param string $tablesPrefix
     * @param Core_Foundation_Database_EntityMetaData $entityMetaData
     */
    public function __construct(Core_foundation_database_entity_Manager $entity_manager, $tables_prefix, Core_foundation_database_entity_Meta_Data $entity_meta_data)
    {
        $this->entity_manager = $entity_manager;
        $this->db = $this->entity_manager->get_database();
        $this->tables_prefix = $tables_prefix;
        $this->entity_meta_data = $entity_meta_data;
        $this->query_builder = new Core_foundation_database_entity_Manager_query_Builder($this->db);
    }
    /**
     * @param string $method
     * @param array $arguments
     *
     * @return array|mixed|null
     * @throws Core_Foundation_Database_Exception
     */
    public function __call($method, $arguments)
    {
        if (0 === strpos($method, 'findOneBy')) {
            $one = true;
            $by = substr($method, 9);
        } elseif (0 === strpos($method, 'findBy')) {
            $one = false;
            $by = substr($method, 6);
        } else {
            throw new Core_Foundation_Database_Exception(sprintf('Undefind method %s.', $method));
        }
        if (count($arguments) !== 1) {
            throw new Core_Foundation_Database_Exception(sprintf('Method %s takes exactly one argument.', $method));
        }
        if (!$by) {
            $where = $arguments[0];
        } else {
            $where = [];
            $by = $this->convert_to_db_field_name($by);
            $where[$by] = $arguments[0];
        }
        return $this->do_find($one, $where);
    }
    /**
     * Convert a camelCase field name to a snakeCase one
     * e.g.: findAllByIdCMS => id_cms
     *
     * @param string $camelCaseFieldName
     *
     * @return string
     */
    protected function convert_to_db_field_name($camel_case_field_name)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $camel_case_field_name));
    }
    /**
     * Return ID field name
     *
     * @return string
     * @throws Core_Foundation_Database_Exception
     */
    protected function get_id_field_name()
    {
        $primary = $this->entity_meta_data->get_primary_key_fieldnames();
        if (count($primary) === 0) {
            throw new Core_Foundation_Database_Exception(sprintf('No primary key defined in entity `%s`.', $this->entity_meta_data->get_entity_class_name()));
        } elseif (count($primary) > 1) {
            throw new Core_Foundation_Database_Exception(sprintf('Entity `%s` has a composite primary key, which is not supported by entity repositories.', $this->entity_meta_data->get_entity_class_name()));
        }
        return $primary[0];
    }
    /**
     * Returns escaped+prefixed current table name
     *
     * @return string
     */
    protected function get_table_name_with_prefix()
    {
        return $this->db->escape($this->tables_prefix . $this->entity_meta_data->get_table_name());
    }
    /**
     * Returns escaped DB table prefix
     *
     * @return string
     */
    protected function get_prefix()
    {
        return $this->db->escape($this->tables_prefix);
    }
    /**
     * Return a new empty Entity depending on current Repository selected
     *
     * @return mixed
     */
    public function get_new_entity()
    {
        $entity_class_name = $this->entity_meta_data->get_entity_class_name();
        return new $entity_class_name();
    }
    /**
     * This function takes an array of database rows as input
     * and returns an hydrated entity if there is one row only.
     *
     * Null is returned when there are no rows, and an exception is thrown
     * if there are too many rows.
     *
     * @param array $rows Database rows
     *
     * @return mixed|null
     * @throws Core_Foundation_Database_Exception
     */
    protected function hydrate_one(array $rows)
    {
        if (count($rows) === 0) {
            return null;
        } elseif (count($rows) > 1) {
            throw new Core_Foundation_Database_Exception('Too many rows returned.');
        } else {
            $data = $rows[0];
            $entity = $this->get_new_entity();
            $entity->hydrate($data);
            return $entity;
        }
    }
    /**
     * @param array $rows
     *
     * @return array
     */
    protected function hydrate_many(array $rows)
    {
        $entities = [];
        foreach ($rows as $row) {
            $entity = $this->get_new_entity();
            $entity->hydrate($row);
            $entities[] = $entity;
        }
        return $entities;
    }
    /**
     * Constructs and performs 'SELECT' in DB
     *
     * @param bool $one
     * @param array $cumulativeConditions
     *
     * @return array|mixed|null
     * @throws Core_Foundation_Database_Exception
     */
    protected function do_find($one, array $cumulative_conditions)
    {
        $where_clause = $this->query_builder->build_where_conditions('AND', $cumulative_conditions);
        $sql = 'SELECT * FROM ' . $this->get_table_name_with_prefix() . ' WHERE ' . $where_clause;
        $rows = $this->db->select($sql);
        if ($one) {
            return $this->hydrate_one($rows);
        } else {
            return $this->hydrate_many($rows);
        }
    }
    /**
     * Find one entity in DB
     *
     * @param int $id
     *
     * @return array|mixed|null
     * @throws Core_Foundation_Database_Exception
     */
    public function find_one($id)
    {
        $conditions = [];
        $conditions[$this->get_id_field_name()] = $id;
        return $this->do_find(true, $conditions);
    }
    /**
     * Find all entities in DB
     *
     * @return array
     */
    public function find_all()
    {
        $sql = 'SELECT * FROM ' . $this->get_table_name_with_prefix();
        return $this->hydrate_many($this->db->select($sql));
    }
}