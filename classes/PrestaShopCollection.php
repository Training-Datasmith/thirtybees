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
 * Class PrestaShopCollectionCore
 */
class Presta_Shop_Collection_Core implements Iterator, ArrayAccess, Countable
{
    public const LEFT_JOIN = 1;
    public const INNER_JOIN = 2;
    public const LEFT_OUTER_JOIN = 3;
    public const LANG_ALIAS = 'l';
    /**
     * @var array Object definition
     */
    protected $definition = [];
    /**
     * @var DbQuery
     */
    protected $query;
    /**
     * @var array Collection of objects in an array
     */
    protected $results = [];
    /**
     * @var bool Is current collection already hydrated
     */
    protected $is_hydrated = false;
    /**
     * @var int Collection iterator
     */
    protected $iterator = 0;
    /**
     * @var int Total of elements for iteration
     */
    protected $total;
    /**
     * @var int Page number
     */
    protected $page_number = 0;
    /**
     * @var int Size of a page
     */
    protected $page_size = 0;
    /**
     * @var array[]
     */
    protected $fields = [];
    /**
     * @var string[]
     */
    protected $alias = [];
    /**
     * @var int
     */
    protected $alias_iterator = 0;
    /**
     * @var array[]
     */
    protected $join_list = [];
    /**
     * @var array[]
     */
    protected $association_definition = [];
    /**
     * @param string $classname
     * @param int $id_lang
     *
     * @throws PrestaShopException
     */
    public function __construct(protected $classname, protected $id_lang = null)
    {
        $this->definition = Object_Model::get_definition($this->classname);
        if (!isset($this->definition['table'])) {
            throw new Presta_Shop_Exception('Miss table in definition for class ' . $this->classname);
        }
        if (!isset($this->definition['primary'])) {
            throw new Presta_Shop_Exception('Miss primary in definition for class ' . $this->classname);
        }
        $this->query = new Db_Query();
    }
    /**
     * Add WHERE restriction on query using real SQL syntax
     *
     * @param string $sql
     *
     *
     * @throws PrestaShopException
     */
    public function sql_where($sql): static
    {
        $this->query->where($this->parse_fields($sql));
        return $this;
    }
    /**
     * Parse all fields with {field} syntax in a string
     *
     * @param string $str
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected function parse_fields($str)
    {
        preg_match_all('#\{(([a-z0-9_]+\.)*[a-z0-9_]+)\}#i', $str, $m);
        for ($i = 0, $total = count($m[0]); $i < $total; $i++) {
            $str = str_replace($m[0][$i], $this->parse_field($m[1][$i]), $str);
        }
        return $str;
    }
    /**
     * Replace a field with its SQL version (E.g. manufacturer.name with a2.name)
     *
     * @param string $field Field name
     *
     *
     * @throws PrestaShopException
     */
    protected function parse_field($field): string
    {
        $info = $this->get_field_info($field);
        return $info['alias'] . '.`' . $info['name'] . '`';
    }
    /**
     * Obtain some information on a field (alias, name, type, etc.)
     *
     * @param string $field Field name
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function get_field_info($field)
    {
        if (!isset($this->fields[$field])) {
            $split = explode('.', $field);
            $total = count($split);
            if ($total > 1) {
                $fieldname = $split[$total - 1];
                unset($split[$total - 1]);
                $association = implode('.', $split);
            } else {
                $fieldname = $field;
                $association = '';
            }
            $definition = $this->get_definition($association);
            if ($association && !isset($this->join_list[$association])) {
                $this->join($association);
            }
            if ($fieldname == $definition['primary'] || !empty($definition['is_lang']) && $fieldname == 'id_lang') {
                $type = Object_Model::TYPE_INT;
            } else {
                // Test if field exists
                if (!isset($definition['fields'][$fieldname])) {
                    throw new Presta_Shop_Exception('Field ' . $fieldname . ' not found in class ' . $definition['classname']);
                }
                // Test field validity for language fields
                if (empty($definition['is_lang']) && !empty($definition['fields'][$fieldname]['lang'])) {
                    throw new Presta_Shop_Exception('Field ' . $fieldname . ' is declared as lang field but is used in non multilang context');
                }
                // Test field validity for language fields
                if (!empty($definition['is_lang']) && empty($definition['fields'][$fieldname]['lang'])) {
                    throw new Presta_Shop_Exception('Field ' . $fieldname . ' is not declared as lang field but is used in multilang context');
                }
                $type = $definition['fields'][$fieldname]['type'];
            }
            $this->fields[$field] = ['name' => $fieldname, 'association' => $association, 'alias' => $this->generate_alias($association), 'type' => $type];
        }
        return $this->fields[$field];
    }
    /**
     * Get definition of an association
     *
     * @param string $association
     *
     * @return array
     * @throws PrestaShopException
     */
    protected function get_definition(?string $association)
    {
        if (!$association) {
            return $this->definition;
        }
        if (!isset($this->association_definition[$association])) {
            $definition = $this->definition;
            $split = explode('.', $association);
            $is_lang = false;
            for ($i = 0, $total_association = count($split); $i < $total_association; $i++) {
                $asso = $split[$i];
                // Check is current association exists in current definition
                if (!isset($definition['associations'][$asso])) {
                    throw new Presta_Shop_Exception('Association ' . $asso . ' not found for class ' . $this->definition['classname']);
                }
                $current_def = $definition['associations'][$asso];
                // Special case for lang alias
                if ($asso == static::LANG_ALIAS) {
                    $is_lang = true;
                    break;
                }
                $classname = $current_def['object'] ?? Tools::to_camel_case($asso, true);
                $definition = Object_Model::get_definition($classname);
            }
            $type = $current_def['type'];
            // Get definition of associated entity and add information on current association
            $current_def['name'] = $asso;
            if (!isset($current_def['object'])) {
                $current_def['object'] = Tools::to_camel_case($asso, true);
            }
            if (!isset($current_def['field'])) {
                $current_def['field'] = $type === Object_Model::BELONGS_TO_MANY ? $this->definition['primary'] : 'id_' . $asso;
            }
            if (!isset($current_def['foreign_field'])) {
                $current_def['foreign_field'] = $definition['primary'];
            }
            if ($type === Object_Model::BELONGS_TO_MANY) {
                if (!isset($current_def['joinTable'])) {
                    throw new Presta_Shop_Exception('Association ' . $this->definition['classname'] . ':' . $asso . ' is missing joinTable');
                }
                if (!isset($current_def['joinSourceField'])) {
                    $current_def['joinSourceField'] = $this->definition['primary'];
                }
                if (!isset($current_def['joinTargetField'])) {
                    $current_def['joinTargetField'] = $definition['primary'];
                }
            }
            if ($total_association > 1) {
                unset($split[$total_association - 1]);
                $current_def['complete_field'] = implode('.', $split) . '.' . $current_def['field'];
            } else {
                $current_def['complete_field'] = $current_def['field'];
            }
            $current_def['complete_foreign_field'] = $association . '.' . $current_def['foreign_field'];
            $definition['is_lang'] = $is_lang;
            $definition['asso'] = $current_def;
            $this->association_definition[$association] = $definition;
        } else {
            $definition = $this->association_definition[$association];
        }
        return $definition;
    }
    /**
     * Join current entity to an associated entity
     *
     * @param string $association Association name
     * @param string $on
     * @param int $type
     *
     * @return false|static
     *
     * @throws PrestaShopException
     */
    public function join(?string $association, $on = '', $type = null): false|self
    {
        if (!$association) {
            return false;
        }
        if (!isset($this->join_list[$association])) {
            if (!$type) {
                $type = static::LEFT_JOIN;
            }
            $definition = $this->get_definition($association);
            $assoc_definition = $definition['asso'];
            if (isset($assoc_definition['joinTable'])) {
                $join_alias = $this->generate_alias($association . '_' . $assoc_definition['joinTable']);
                $target_alias = $this->generate_alias($association);
                if (!$on) {
                    $on = $join_alias . '.`' . $assoc_definition['joinTargetField'] . '` = {' . $assoc_definition['complete_foreign_field'] . '}';
                }
                $this->join_list[$association] = ['joinTable' => $assoc_definition['joinTable'], 'joinAlias' => $join_alias, 'joinTableJoin' => $this->parse_fields('{' . $assoc_definition['complete_field'] . '} = ' . $join_alias . '.`' . $assoc_definition['joinSourceField'] . '`'), 'table' => $definition['table'], 'alias' => $target_alias, 'on' => [], 'type' => $type];
            } else {
                if (!$on) {
                    $on = '{' . $assoc_definition['complete_field'] . '} = {' . $assoc_definition['complete_foreign_field'] . '}';
                }
                $this->join_list[$association] = ['table' => $definition['is_lang'] ? $definition['table'] . '_lang' : $definition['table'], 'alias' => $this->generate_alias($association), 'on' => [], 'type' => $type];
            }
        }
        if ($on) {
            $this->join_list[$association]['on'][] = $this->parse_fields($on);
        }
        if ($type) {
            $this->join_list[$association]['type'] = $type;
        }
        return $this;
    }
    /**
     * Generate uniq alias from association name
     *
     * @param string $association Use empty association for alias on current table
     *
     * @return string
     */
    protected function generate_alias($association = '')
    {
        if (!isset($this->alias[$association])) {
            $this->alias[$association] = 'a' . $this->alias_iterator++;
        }
        return $this->alias[$association];
    }
    /**
     * Add HAVING restriction on query
     *
     * @param string $field Field name
     * @param string $operator List of operators : =, !=, <>, <, <=, >, >=, like, notlike, regexp, notregexp
     * @param array|bool|float|int|string $value
     *
     * @return static
     *
     * @throws PrestaShopException
     */
    public function having($field, $operator, $value)
    {
        return $this->where($field, $operator, $value, 'having');
    }
    /**
     * Add WHERE restriction on query
     *
     * @param string $field Field name
     * @param string $operator List of operators : =, !=, <>, <, <=, >, >=, like, notlike, regexp, notregexp
     * @param array|bool|float|int|string $value
     * @param string $method
     *
     * @throws PrestaShopException
     */
    public function where($field, $operator, $value, $method = 'where'): static
    {
        if ($method != 'where' && $method != 'having') {
            throw new Presta_Shop_Exception('Bad method argument for where() method (should be "where" or "having")');
        }
        // Create WHERE clause with an array value (IN, NOT IN)
        if (is_array($value)) {
            match (strtolower($operator)) {
                '=', 'in' => $this->query->{$method}($this->parse_field($field) . ' IN(' . implode(', ', $this->format_value($value, $field)) . ')'),
                '!=', '<>', 'notin' => $this->query->{$method}($this->parse_field($field) . ' NOT IN(' . implode(', ', $this->format_value($value, $field)) . ')'),
                default => throw new Presta_Shop_Exception('Operator not supported for array value'),
            };
        } else {
            match (strtolower($operator)) {
                '=', '!=', '<>', '>', '>=', '<', '<=', 'like', 'regexp' => $this->query->{$method}($this->parse_field($field) . ' ' . $operator . ' ' . $this->format_value($value, $field)),
                'notlike' => $this->query->{$method}($this->parse_field($field) . ' NOT LIKE ' . $this->format_value($value, $field)),
                'notregexp' => $this->query->{$method}($this->parse_field($field) . ' NOT REGEXP ' . $this->format_value($value, $field)),
                default => throw new Presta_Shop_Exception('Operator not supported'),
            };
        }
        return $this;
    }
    /**
     * Format a value with the type of the given field
     *
     * @param array|bool|float|int|string $value
     * @param string $field Field name
     *
     * @return array|bool|float|int|string|string[]|null
     *
     * @throws PrestaShopException
     */
    protected function format_value($value, $field)
    {
        $info = $this->get_field_info($field);
        if (is_array($value)) {
            $results = [];
            foreach ($value as $item) {
                $results[] = Object_Model::format_value($item, $info['type'], true);
            }
            return $results;
        }
        return Object_Model::format_value($value, $info['type'], true);
    }
    /**
     * Add HAVING restriction on query using real SQL syntax
     *
     * @param string $sql
     *
     *
     * @throws PrestaShopException
     */
    public function sql_having($sql): static
    {
        $this->query->having($this->parse_fields($sql));
        return $this;
    }
    /**
     * Add ORDER BY restriction on query
     *
     * @param string $field Field name
     * @param string $order asc|desc
     *
     * @throws PrestaShopException
     */
    public function order_by($field, $order = 'asc'): static
    {
        $order = strtolower($order);
        if ($order != 'asc' && $order != 'desc') {
            throw new Presta_Shop_Exception('Order must be asc or desc');
        }
        $this->query->order_by($this->parse_field($field) . ' ' . $order);
        return $this;
    }
    /**
     * Add ORDER BY restriction on query using real SQL syntax
     *
     * @param string $sql
     *
     *
     * @throws PrestaShopException
     */
    public function sql_order_by($sql): static
    {
        $this->query->order_by($this->parse_fields($sql));
        return $this;
    }
    /**
     * Add GROUP BY restriction on query
     *
     * @param string $field Field name
     *
     *
     * @throws PrestaShopException
     */
    public function group_by($field): static
    {
        $this->query->group_by($this->parse_field($field));
        return $this;
    }
    /**
     * Add GROUP BY restriction on query using real SQL syntax
     *
     * @param string $sql
     *
     *
     * @throws PrestaShopException
     */
    public function sql_group_by($sql): static
    {
        $this->query->group_by($this->parse_fields($sql));
        return $this;
    }
    /**
     * Retrieve the first result
     *
     * @return false|ObjectModel
     *
     * @throws PrestaShopException
     */
    public function get_first(): false|\Object_Model
    {
        $this->get_all();
        if (!count($this)) {
            return false;
        }
        return $this[0];
    }
    /**
     * Launch sql query to create collection of objects
     *
     * @param bool $displayQuery If true, query will be displayed (for debug purpose)
     *
     *
     * @throws PrestaShopException
     */
    public function get_all($display_query = false): static
    {
        if ($this->is_hydrated) {
            return $this;
        }
        $this->is_hydrated = true;
        $alias = $this->generate_alias();
        //$this->query->select($alias.'.*');
        $this->query->from($this->definition['table'], $alias);
        // If multilang, create association to lang table
        if (!empty($this->definition['multilang'])) {
            $this->join(static::LANG_ALIAS);
            if ($this->id_lang) {
                $this->where(static::LANG_ALIAS . '.id_lang', '=', $this->id_lang);
            }
        }
        // Add join clause
        foreach ($this->join_list as $data) {
            if (isset($data['joinTable'])) {
                $this->join_table($data['joinTable'], $data['joinAlias'], $data['joinTableJoin'], $data['type']);
            }
            $on = '(' . implode(') AND (', $data['on']) . ')';
            $this->join_table($data['table'], $data['alias'], $on, $data['type']);
        }
        // All limit clause
        if ($this->page_size) {
            $this->query->limit($this->page_size, $this->page_number * $this->page_size);
        }
        // Shall we display query for debug ?
        if ($display_query) {
            echo $this->query . '<br />';
        }
        $this->results = Db::read_only()->get_array($this->query);
        if ($this->results) {
            $this->results = Object_Model::hydrate_collection($this->classname, $this->results, $this->id_lang);
        }
        return $this;
    }
    /**
     * Marks collection as empty. SQL query will not be executed, and empty results array will
     * always be returned
     */
    public function empty(): static
    {
        $this->is_hydrated = true;
        $this->results = [];
        $this->query->where('0 = 1');
        return $this;
    }
    /**
     * @param string $table
     * @param string $alias
     * @param string $on
     * @param int $joinType
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function join_table($table, $alias, $on, $join_type): void
    {
        switch ($join_type) {
            case static::LEFT_JOIN:
                $this->query->left_join($table, $alias, $on);
                break;
            case static::INNER_JOIN:
                $this->query->inner_join($table, $alias, $on);
                break;
            case static::LEFT_OUTER_JOIN:
                $this->query->left_outer_join($table, $alias, $on);
                break;
        }
    }
    /**
     * Get results array
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public function get_results()
    {
        $this->get_all();
        return $this->results;
    }
    /**
     * This method is called when a foreach begin
     *
     * @throws PrestaShopException
     */
    public function rewind(): void
    {
        $this->get_all();
        $this->results = array_merge($this->results);
        $this->iterator = 0;
        $this->total = count($this->results);
    }
    /**
     * Get current result
     *
     * @return ObjectModel
     */
    #[Return_Type_Will_Change]
    public function current()
    {
        return $this->results[$this->iterator] ?? null;
    }
    /**
     * Check if there is a current result
     */
    public function valid(): bool
    {
        return $this->iterator < $this->total;
    }
    /**
     * Get current result index
     */
    public function key(): int
    {
        return $this->iterator;
    }
    /**
     * Go to next result
     */
    public function next(): void
    {
        $this->iterator++;
    }
    /**
     * Get total of results
     *
     *
     * @throws PrestaShopException
     */
    public function count(): int
    {
        $this->get_all();
        return count($this->results);
    }
    /**
     * Check if a result exist
     *
     * @param int $offset
     *
     *
     * @throws PrestaShopException
     */
    public function offsetExists($offset): bool
    {
        $this->get_all();
        return isset($this->results[$offset]);
    }
    /**
     * Get a result by offset
     *
     * @param mixed $offset
     *
     * @return ObjectModel
     * @throws PrestaShopException
     */
    #[Return_Type_Will_Change]
    public function offsetGet($offset)
    {
        $this->get_all();
        if (!isset($this->results[$offset])) {
            throw new Presta_Shop_Exception('Unknown offset ' . $offset . ' for collection ' . $this->classname);
        }
        return $this->results[$offset];
    }
    /**
     * Add an element in the collection
     *
     * @param int $offset
     * @param mixed $value
     *
     * @throws PrestaShopException
     */
    public function offsetSet($offset, $value): void
    {
        if (!$value instanceof $this->classname) {
            throw new Presta_Shop_Exception('You cannot add an element which is not an instance of ' . $this->classname);
        }
        $this->get_all();
        if (is_null($offset)) {
            $this->results[] = $value;
        } else {
            $this->results[$offset] = $value;
        }
    }
    /**
     * Delete an element from the collection
     *
     * @param mixed $offset
     *
     * @throws PrestaShopException
     */
    public function offsetUnset($offset): void
    {
        $this->get_all();
        unset($this->results[$offset]);
    }
    /**
     * Set the page number
     *
     * @param int $pageNumber
     */
    public function set_page_number($page_number): static
    {
        $page_number = (int) $page_number;
        if ($page_number > 0) {
            $page_number--;
        }
        $this->page_number = $page_number;
        return $this;
    }
    /**
     * Set the nuber of item per page
     *
     * @param int $pageSize
     */
    public function set_page_size($page_size): static
    {
        $this->page_size = (int) $page_size;
        return $this;
    }
}