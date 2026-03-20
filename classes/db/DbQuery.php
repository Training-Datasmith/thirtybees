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
 * SQL query builder
 */
class Db_Query_Core implements \Stringable
{
    /**
     * @var string
     */
    protected $db_prefix;
    /**
     * List of data to build the query
     *
     * @var array
     */
    protected $query = ['type' => 'SELECT', 'select' => [], 'from' => [], 'join' => [], 'where' => [], 'group' => [], 'having' => [], 'order' => [], 'limit' => ['offset' => 0, 'limit' => 0]];
    /**
     * @param string|null $dbPrefix
     */
    public function __construct($db_prefix = null)
    {
        $this->db_prefix = $db_prefix ?? _DB_PREFIX_;
    }
    /**
     * Sets type of the query
     *
     * @param string $type SELECT|DELETE
     */
    public function type($type): static
    {
        $types = ['SELECT', 'DELETE'];
        if (!empty($type) && in_array($type, $types)) {
            $this->query['type'] = $type;
        }
        return $this;
    }
    /**
     * Adds fields to SELECT clause
     *
     * @param string $fields List of fields to concat to other fields
     */
    public function select($fields): static
    {
        if (!empty($fields)) {
            $this->query['select'][] = $fields;
        }
        return $this;
    }
    /**
     * Sets table for FROM clause
     *
     * @param string $table Table name
     * @param string|null $alias Table alias
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function from($table, $alias = null): static
    {
        if (!empty($table)) {
            if (strncmp($this->db_prefix, $table, strlen($this->db_prefix)) !== 0) {
                $table = $this->db_prefix . $table;
            }
            if (empty($this->query['from'])) {
                $this->query['from'] = [];
            }
            $this->query['from'][] = '`' . bq_sql($table) . '`' . ($alias ? ' ' . $alias : '');
        }
        return $this;
    }
    /**
     * Adds JOIN clause
     * E.g. $this->join('RIGHT JOIN '.$this->dbPrefix.'product p ON ...');
     *
     * @param string $join Complete string
     */
    public function join($join): static
    {
        if (!empty($join)) {
            $this->query['join'][] = $join;
        }
        return $this;
    }
    /**
     * Adds a LEFT JOIN clause
     *
     * @param string $table Table name (without prefix)
     * @param string|null $alias Table alias
     * @param string|null $on ON clause
     *
     * @return static
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function left_join($table, $alias = null, $on = null)
    {
        if (strncmp($this->db_prefix, $table, strlen($this->db_prefix)) !== 0) {
            $table = $this->db_prefix . $table;
        }
        return $this->join('LEFT JOIN `' . bq_sql($table) . '`' . ($alias ? ' `' . p_sql($alias) . '`' : '') . ($on ? ' ON ' . $on : ''));
    }
    /**
     * Adds an INNER JOIN clause
     * E.g. $this->innerJoin('product p ON ...')
     *
     * @param string $table Table name (without prefix)
     * @param string|null $alias Table alias
     * @param string|null $on ON clause
     *
     * @return static
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function inner_join($table, $alias = null, $on = null)
    {
        if (strncmp($this->db_prefix, $table, strlen($this->db_prefix)) !== 0) {
            $table = $this->db_prefix . $table;
        }
        return $this->join('INNER JOIN `' . bq_sql($table) . '`' . ($alias ? ' ' . p_sql($alias) : '') . ($on ? ' ON ' . $on : ''));
    }
    /**
     * Include primary and shop tables into the query using INNER JOIN
     *
     * Two tables will be added to the sql query
     *   - primary table:  <DB_PREFIX>_table AS alias
     *   - shop table:     <DB_PREFIX>_table_shop AS aliasShop
     * Shop table will be joined using object model metadata
     *
     * For more information, see Shop::addSqlAssociation
     *
     * @param string $table primary table name (without prefix)
     * @param string $alias primary table alias
     * @param string $aliasShop shop table alias
     * @param string $on primary table ON clause
     * @param string|null $shopOnExtra additional conditions to be included within shop ON clause
     *
     * @return static
     *
     * @throws PrestaShopException
     */
    public function inner_join_multishop(string $table, string $alias, string $alias_shop, $on, $shop_on_extra = null)
    {
        if (!Shop::is_table_associated($table)) {
            throw new Presta_Shop_Exception("Table `{$table}` is not multistore enabled`");
        }
        // expose primary table
        $this->inner_join($table, $alias, $on);
        // expose shop table
        $shop_on = '`' . p_sql($alias_shop) . '`.`id_' . $table . '` = `' . p_sql($alias) . '`.`id_' . $table . '`';
        if ((int) Shop::get_context_shop_id()) {
            $shop_on .= ' AND `' . $alias_shop . '`.`id_shop` = ' . (int) Shop::get_context_shop_id();
        } elseif (Shop::check_id_shop_default($table)) {
            $shop_on .= ' AND `' . $alias_shop . '`.`id_shop` = `' . $alias . '`.`id_shop_default`';
        } else {
            $shop_on .= ' AND `' . $alias_shop . '`.`id_shop` IN (' . implode(', ', Shop::get_context_list_shop_id()) . ')';
        }
        if ($shop_on_extra) {
            $shop_on .= ' ' . trim($shop_on_extra);
        }
        return $this->inner_join($table . '_shop', $alias_shop, $shop_on);
    }
    /**
     * Adds a LEFT OUTER JOIN clause
     *
     * @param string $table Table name (without prefix)
     * @param string|null $alias Table alias
     * @param string|null $on ON clause
     *
     * @return static
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function left_outer_join($table, $alias = null, $on = null)
    {
        if (strncmp($this->db_prefix, $table, strlen($this->db_prefix)) !== 0) {
            $table = $this->db_prefix . $table;
        }
        return $this->join('LEFT OUTER JOIN `' . bq_sql($table) . '`' . ($alias ? ' ' . p_sql($alias) : '') . ($on ? ' ON ' . $on : ''));
    }
    /**
     * Adds a NATURAL JOIN clause
     *
     * @param string $table Table name (without prefix)
     * @param string|null $alias Table alias
     *
     * @return static
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function natural_join($table, $alias = null)
    {
        if (strncmp($this->db_prefix, $table, strlen($this->db_prefix)) !== 0) {
            $table = $this->db_prefix . $table;
        }
        return $this->join('NATURAL JOIN `' . bq_sql($table) . '`' . ($alias ? ' ' . p_sql($alias) : ''));
    }
    /**
     * Adds a RIGHT JOIN clause
     *
     * @param string $table Table name (without prefix)
     * @param string|null $alias Table alias
     * @param string|null $on ON clause
     *
     * @return static
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function right_join($table, $alias = null, $on = null)
    {
        if (strncmp($this->db_prefix, $table, strlen($this->db_prefix)) !== 0) {
            $table = $this->db_prefix . $table;
        }
        return $this->join('RIGHT JOIN `' . bq_sql($table) . '`' . ($alias ? ' `' . p_sql($alias) . '`' : '') . ($on ? ' ON ' . $on : ''));
    }
    /**
     * Adds a restriction in WHERE clause (each restriction will be separated by AND statement)
     *
     * @param string $restriction
     */
    public function where($restriction): static
    {
        if (!empty($restriction)) {
            $this->query['where'][] = $restriction;
        }
        return $this;
    }
    /**
     * Adds shop restriction for a specific table alias.
     *
     * @param string|false $share If false, dont check share datas from group. Else can take a Shop::SHARE_* constant value
     *
     * @return static
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function add_current_shop_restriction(string $table_alias, $share = false)
    {
        return $this->where(Shop::get_sql_restriction($share, '`' . $table_alias . '`'));
    }
    /**
     * Adds a restriction in HAVING clause (each restriction will be separated by AND statement)
     *
     * @param string $restriction
     */
    public function having($restriction): static
    {
        if (!empty($restriction)) {
            $this->query['having'][] = $restriction;
        }
        return $this;
    }
    /**
     * Adds an ORDER BY restriction
     *
     * @param string $fields List of fields to sort. E.g. $this->order('myField, b.mySecondField DESC')
     */
    public function order_by($fields): static
    {
        if (!empty($fields)) {
            $this->query['order'][] = $fields;
        }
        return $this;
    }
    /**
     * Adds a GROUP BY restriction
     *
     * @param string $fields List of fields to group. E.g. $this->group('myField1, myField2')
     */
    public function group_by($fields): static
    {
        if (!empty($fields)) {
            $this->query['group'][] = $fields;
        }
        return $this;
    }
    /**
     * Sets query offset and limit
     *
     * @param int $limit
     * @param int $offset
     */
    public function limit($limit, $offset = 0): static
    {
        $offset = (int) $offset;
        if ($offset < 0) {
            $offset = 0;
        }
        $this->query['limit'] = ['offset' => $offset, 'limit' => (int) $limit];
        return $this;
    }
    /**
     * Generates query and return SQL string
     *
     * @return string
     * @throws PrestaShopException
     */
    public function build()
    {
        $this->validate();
        return $this->build_sql();
    }
    /**
     * Validates current DbQuery object, throws exception if it's not valid
     *
     * @throws PrestaShopException
     */
    public function validate(): void
    {
        if (!$this->query['from']) {
            throw new Presta_Shop_Exception('Table name not set in DbQuery object. Cannot build a valid SQL query.');
        }
    }
    /**
     * Generates query and return SQL
     */
    public function build_sql(): string
    {
        if ($this->query['type'] == 'SELECT') {
            $sql = 'SELECT ' . ($this->query['select'] ? implode(",\n", $this->query['select']) : '*') . "\n";
        } else {
            $sql = $this->query['type'] . ' ';
        }
        if ($this->query['from']) {
            $sql .= 'FROM ' . implode(', ', $this->query['from']) . "\n";
        }
        if ($this->query['join']) {
            $sql .= implode("\n", $this->query['join']) . "\n";
        }
        if ($this->query['where']) {
            $sql .= 'WHERE (' . implode(') AND (', $this->query['where']) . ")\n";
        }
        if ($this->query['group']) {
            $sql .= 'GROUP BY ' . implode(', ', $this->query['group']) . "\n";
        }
        if ($this->query['having']) {
            $sql .= 'HAVING (' . implode(') AND (', $this->query['having']) . ")\n";
        }
        if ($this->query['order']) {
            $sql .= 'ORDER BY ' . implode(', ', $this->query['order']) . "\n";
        }
        if ($this->query['limit']['limit']) {
            $limit = $this->query['limit'];
            $sql .= 'LIMIT ' . ($limit['offset'] ? $limit['offset'] . ', ' : '') . $limit['limit'];
        }
        return $sql;
    }
    /**
     * Converts object to string
     */
    public function __toString(): string
    {
        return $this->build_sql();
    }
}