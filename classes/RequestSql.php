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
 * Class RequestSqlCore
 */
class Request_Sql_Core extends Object_Model
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $sql;
    /**
     * @var array : List of params to tested
     */
    public $tested = ['required' => ['SELECT', 'FROM'], 'option' => ['WHERE', 'ORDER', 'LIMIT', 'HAVING', 'GROUP', 'UNION'], 'operator' => ['AND', '&&', 'BETWEEN', 'AND', 'BINARY', '&', '~', '|', '^', 'CASE', 'WHEN', 'END', 'DIV', '/', '<=>', '=', '>=', '>', 'IS', 'NOT', 'NULL', '<<', '<=', '<', 'LIKE', '-', '%', '!=', '<>', 'REGEXP', '!', '||', 'OR', '+', '>>', 'RLIKE', 'SOUNDS', '*', '-', 'XOR', 'IN'], 'function' => ['AVG', 'SUM', 'COUNT', 'MIN', 'MAX', 'STDDEV', 'STDDEV_SAMP', 'STDDEV_POP', 'VARIANCE', 'VAR_SAMP', 'VAR_POP', 'GROUP_CONCAT', 'BIT_AND', 'BIT_OR', 'BIT_XOR'], 'unauthorized' => ['DELETE', 'ALTER', 'INSERT', 'REPLACE', 'CREATE', 'TRUNCATE', 'OPTIMIZE', 'GRANT', 'REVOKE', 'SHOW', 'HANDLER', 'LOAD', 'ROLLBACK', 'SAVEPOINT', 'UNLOCK', 'INSTALL', 'UNINSTALL', 'ANALZYE', 'BACKUP', 'CHECK', 'CHECKSUM', 'REPAIR', 'RESTORE', 'CACHE', 'DESCRIBE', 'EXPLAIN', 'USE', 'HELP', 'SET', 'DUPLICATE', 'VALUES', 'INTO', 'RENAME', 'CALL', 'PROCEDURE', 'FUNCTION', 'DATABASE', 'SERVER', 'LOGFILE', 'DEFINER', 'RETURNS', 'EVENT', 'TABLESPACE', 'VIEW', 'TRIGGER', 'DATA', 'DO', 'PASSWORD', 'USER', 'PLUGIN', 'FLUSH', 'KILL', 'RESET', 'START', 'STOP', 'PURGE', 'EXECUTE', 'PREPARE', 'DEALLOCATE', 'LOCK', 'USING', 'DROP', 'FOR', 'UPDATE', 'BEGIN', 'BY', 'ALL', 'SHARE', 'MODE', 'TO', 'KEY', 'DISTINCTROW', 'DISTINCT', 'HIGH_PRIORITY', 'LOW_PRIORITY', 'DELAYED', 'IGNORE', 'FORCE', 'STRAIGHT_JOIN', 'SQL_SMALL_RESULT', 'SQL_BIG_RESULT', 'QUICK', 'SQL_BUFFER_RESULT', 'SQL_CACHE', 'SQL_NO_CACHE', 'SQL_CALC_FOUND_ROWS', 'WITH']];
    /**
     * @var string[]
     */
    public $attributes = ['passwd' => '*******************', 'secure_key' => '*******************'];
    /** @var array : list of errors */
    public $error_sql = [];
    /**
     * @var array Object model definition
     */
    public static $definition = ['table' => 'request_sql', 'primary' => 'id_request_sql', 'primaryKeyDbType' => 'int(11)', 'fields' => ['name' => ['type' => self::TYPE_STRING, 'validate' => 'isString', 'required' => true, 'size' => 200], 'sql' => ['type' => self::TYPE_SQL, 'validate' => 'isString', 'required' => true, 'dbType' => 'text', 'charset' => ['utf8mb4', 'utf8mb4_unicode_ci']]]];
    /**
     * Get list of request SQL
     *
     * @return array|false
     */
    public static function get_request_sql()
    {
        try {
            if (!$result = Db::read_only()->get_array((new Db_Query())->select('*')->from(bq_sql(static::$definition['table']))->order_by('`' . bq_sql(static::$definition['primary']) . '`'))) {
                return false;
            }
        } catch (Presta_Shop_Exception) {
            return false;
        }
        $request_sql = [];
        foreach ($result as $row) {
            $request_sql[] = $row['sql'];
        }
        return $request_sql;
    }
    /**
     * Get request SQL by id request
     *
     * @param int $id
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public static function get_request_sql_by_id($id)
    {
        return (string) Db::read_only()->get_value((new Db_Query())->select('`sql`')->from(bq_sql(static::$definition['table']))->where('`' . bq_sql(static::$definition['primary']) . '` = ' . (int) $id));
    }
    /**
     * Call the parserSQL() method in Tools class
     * Cut the request in table for check it
     *
     * @param string $sql
     *
     * @return false|array
     */
    public function parsing_sql($sql)
    {
        return Tools::parser_sql($sql);
    }
    /**
     * Check if the parsing of the SQL request is good or not
     *
     * @param false|array $tab
     * @param bool $in
     * @param string $sql
     *
     * @return bool
     */
    public function validate_parser($tab, $in, $sql)
    {
        if (!$tab) {
            return false;
        }
        if (isset($tab['UNION'])) {
            $union = $tab['UNION'];
            foreach ($union as $tab) {
                if (!$this->validate_sql($tab, $in, $sql)) {
                    return false;
                }
            }
            return true;
        }
        return $this->validate_sql($tab, $in, $sql);
    }
    /**
     * Cut the request for check each cutting
     *
     * @param array $tab
     * @param bool $in
     * @param string $sql
     *
     * @return bool
     */
    public function validate_sql($tab, $in, $sql)
    {
        if (!$this->tested_required($tab)) {
            return false;
        }
        if (!$this->tested_unauthorized($tab)) {
            return false;
        }
        if (!$this->checked_from($tab['FROM'])) {
            return false;
        }
        if (!$this->checked_select($tab['SELECT'], $tab['FROM'], $in)) {
            return false;
        }
        if (isset($tab['WHERE'])) {
            if (!$this->checked_where($tab['WHERE'], $tab['FROM'], $sql)) {
                return false;
            }
        } elseif (isset($tab['HAVING'])) {
            if (!$this->checked_having($tab['HAVING'], $tab['FROM'])) {
                return false;
            }
        } elseif (isset($tab['ORDER'])) {
            if (!$this->checked_order($tab['ORDER'], $tab['FROM'])) {
                return false;
            }
        } elseif (isset($tab['GROUP'])) {
            if (!$this->checked_group_by($tab['GROUP'], $tab['FROM'])) {
                return false;
            }
        } elseif (isset($tab['LIMIT'])) {
            if (!$this->checked_limit($tab['LIMIT'])) {
                return false;
            }
        }
        try {
            if (empty($this->_errors) && !Db::read_only()->get_array($sql)) {
                return false;
            }
        } catch (Presta_Shop_Exception) {
            return false;
        }
        return true;
    }
    /**
     * Check if all required sentence existing
     *
     * @param array $tab
     *
     * @return bool
     */
    public function tested_required($tab)
    {
        foreach ($this->tested['required'] as $key) {
            if (!array_key_exists($key, $tab)) {
                $this->error_sql['testedRequired'] = $key;
                return false;
            }
        }
        return true;
    }
    /**
     * Check if an unauthorized existing in an array
     *
     * @param array $tab
     *
     * @return bool
     */
    public function tested_unauthorized($tab)
    {
        foreach ($this->tested['unauthorized'] as $key) {
            if (array_key_exists($key, $tab)) {
                $this->error_sql['testedUnauthorized'] = $key;
                return false;
            }
        }
        return true;
    }
    /**
     * Check a "FROM" sentence
     *
     * @param array $from
     *
     * @return bool
     */
    public function checked_from($from)
    {
        if (!is_array($from)) {
            return false;
        }
        $nb = count($from);
        for ($i = 0; $i < $nb; $i++) {
            $table = $from[$i];
            if (isset($table['table']) && !in_array(str_replace('`', '', $table['table']), $this->get_tables())) {
                $this->error_sql['checkedFrom']['table'] = $table['table'];
                return false;
            }
            if ($table['ref_type'] == 'ON' && (trim((string) $table['join_type']) == 'LEFT' || trim((string) $table['join_type']) == 'JOIN')) {
                if ($attrs = $this->cut_join($table['ref_clause'], $from)) {
                    foreach ($attrs as $attr) {
                        if (!$this->attribut_exist_in_table($attr['attribut'], $attr['table'])) {
                            $this->error_sql['checkedFrom']['attribut'] = [$attr['attribut'], implode(', ', $attr['table'])];
                            return false;
                        }
                    }
                } else {
                    if (isset($this->error_sql['returnNameTable'])) {
                        $this->error_sql['checkedFrom'] = $this->error_sql['returnNameTable'];
                        return false;
                    }
                    $this->error_sql['checkedFrom'] = false;
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * Get list of all tables
     *
     * @return array
     */
    public function get_tables()
    {
        $tables = [];
        try {
            $results = Db::read_only()->get_array('SHOW TABLES');
        } catch (Presta_Shop_Exception) {
            return $tables;
        }
        foreach ($results as $result) {
            $key = array_keys($result);
            $tables[] = $result[$key[0]];
        }
        return $tables;
    }
    /**
     * Cut an join sentence
     *
     * @param array $attrs
     * @param array $from
     *
     * @return array
     */
    public function cut_join($attrs, $from)
    {
        $tab = [];
        foreach ($attrs as $attr) {
            if (in_array($attr['expr_type'], ['operator', 'const'])) {
                continue;
            }
            if ($attr['expr_type'] === 'bracket_expression') {
                $tab = array_merge($tab, $this->cut_join($attr['sub_tree'], $from));
            } elseif ($attribut = $this->cut_attribute($attr['base_expr'], $from)) {
                $tab[] = $attribut;
            }
        }
        return $tab;
    }
    /**
     * Cut an attribute with or without the alias
     *
     * @param string $attr
     * @param array $from
     *
     * @return array|false
     */
    public function cut_attribute($attr, $from)
    {
        $matches = [];
        if (preg_match('/((`(\()?([a-z0-9_])+`(\))?)|((\()?([a-z0-9_])+(\))?))\.((`(\()?([a-z0-9_])+`(\))?)|((\()?([a-z0-9_])+(\))?))$/i', $attr, $matches, PREG_OFFSET_CAPTURE)) {
            $tab = explode('.', str_replace(['`', '(', ')'], '', $matches[0][0]));
            if ($table = $this->return_name_table($tab[0], $from)) {
                return ['table' => $table, 'alias' => $tab[0], 'attribut' => $tab[1], 'string' => $attr];
            }
        } elseif (preg_match('/((`(\()?([a-z0-9_])+`(\))?)|((\()?([a-z0-9_])+(\))?))$/i', $attr, $matches, PREG_OFFSET_CAPTURE)) {
            $attribut = str_replace(['`', '(', ')'], '', $matches[0][0]);
            if ($table = $this->return_name_table(false, $from, $attr)) {
                return ['table' => $table, 'attribut' => $attribut, 'string' => $attr];
            }
        }
        return false;
    }
    /**
     * Get name of table by alias
     *
     * @param bool $alias
     * @param array $tables
     * @param string $attr
     *
     * @return array|false
     */
    public function return_name_table($alias, $tables, $attr = null)
    {
        if ($alias) {
            foreach ($tables as $table) {
                if (isset($table['alias']) && isset($table['table']) && $table['alias']['no_quotes']['parts'][0] == $alias) {
                    return [$table['table']];
                }
            }
        } elseif (count($tables) > 1) {
            if ($attr !== null) {
                $tab = [];
                foreach ($tables as $table) {
                    if ($this->attribut_exist_in_table($attr, $table['table'])) {
                        $tab = $table['table'];
                    }
                }
                if (count($tab) == 1) {
                    return $tab;
                }
            }
            $this->error_sql['returnNameTable'] = false;
            return false;
        } else {
            $tab = [];
            foreach ($tables as $table) {
                $tab[] = $table['table'];
            }
            return $tab;
        }
        return false;
    }
    /**
     * Check if an attributes existe in an table
     *
     * @param string $attr
     * @param string $table
     *
     * @return bool
     */
    public function attribut_exist_in_table($attr, $table)
    {
        if (!$attr) {
            return true;
        }
        if (is_array($table) && count($table) == 1) {
            $table = $table[0];
        }
        $attributs = $this->get_attributes_by_table($table);
        foreach ($attributs as $attribut) {
            if ($attribut['Field'] == trim($attr, ' `')) {
                return true;
            }
        }
        return false;
    }
    /**
     * Get list of all attributes by an table
     *
     * @param string $table
     *
     * @return array
     */
    public function get_attributes_by_table($table)
    {
        try {
            return Db::read_only()->get_array('DESCRIBE ' . p_sql($table));
        } catch (Presta_Shop_Exception) {
            return [];
        }
    }
    /**
     * Check a "SELECT" sentence
     *
     * @param string[] $select
     * @param string[] $from
     * @param bool $in
     *
     * @return bool
     */
    public function checked_select($select, $from, $in = false)
    {
        if (!is_array($select)) {
            return false;
        }
        $nb = count($select);
        for ($i = 0; $i < $nb; $i++) {
            /** @var string[] $attribut */
            $attribut = $select[$i];
            if ($attribut['base_expr'] != '*' && !preg_match('/\.*$/', $attribut['base_expr'])) {
                if ($attribut['expr_type'] == 'colref') {
                    if ($attr = $this->cut_attribute(trim($attribut['base_expr']), $from)) {
                        if (!$this->attribut_exist_in_table($attr['attribut'], $attr['table'])) {
                            $this->error_sql['checkedSelect']['attribut'] = [$attr['attribut'], implode(', ', $attr['table'])];
                            return false;
                        }
                    } else {
                        if (isset($this->error_sql['returnNameTable'])) {
                            $this->error_sql['checkedSelect'] = $this->error_sql['returnNameTable'];
                            return false;
                        }
                        $this->error_sql['checkedSelect'] = false;
                        return false;
                    }
                }
            } elseif ($in) {
                $this->error_sql['checkedSelect']['*'] = false;
                return false;
            }
        }
        return true;
    }
    /**
     * Check a "WHERE" sentence
     *
     * @param array $where
     * @param array $from
     * @param string $sql
     *
     * @return bool
     */
    public function checked_where($where, $from, $sql)
    {
        if (!is_array($where)) {
            return false;
        }
        $nb = count($where);
        for ($i = 0; $i < $nb; $i++) {
            $attribut = $where[$i];
            if ($attribut['expr_type'] == 'colref' || $attribut['expr_type'] == 'reserved') {
                if ($attr = $this->cut_attribute(trim((string) $attribut['base_expr']), $from)) {
                    if (!$this->attribut_exist_in_table($attr['attribut'], $attr['table'])) {
                        $this->error_sql['checkedWhere']['attribut'] = [$attr['attribut'], implode(', ', $attr['table'])];
                        return false;
                    }
                } else {
                    if (isset($this->error_sql['returnNameTable'])) {
                        $this->error_sql['checkedWhere'] = $this->error_sql['returnNameTable'];
                        return false;
                    }
                    $this->error_sql['checkedWhere'] = false;
                    return false;
                }
            } elseif ($attribut['expr_type'] == 'operator') {
                if (!in_array(strtoupper((string) $attribut['base_expr']), $this->tested['operator'])) {
                    $this->error_sql['checkedWhere']['operator'] = [$attribut['base_expr']];
                    return false;
                }
            } elseif ($attribut['expr_type'] == 'subquery') {
                $tab = $attribut['sub_tree'];
                return $this->validate_parser($tab, true, $sql);
            }
        }
        return true;
    }
    /**
     * Check a "HAVING" sentence
     *
     * @param array $having
     * @param array $from
     *
     * @return bool
     */
    public function checked_having($having, $from)
    {
        $nb = count($having);
        for ($i = 0; $i < $nb; $i++) {
            $attribut = $having[$i];
            if ($attribut['expr_type'] == 'colref') {
                if ($attr = $this->cut_attribute(trim((string) $attribut['base_expr']), $from)) {
                    if (!$this->attribut_exist_in_table($attr['attribut'], $attr['table'])) {
                        $this->error_sql['checkedHaving']['attribut'] = [$attr['attribut'], implode(', ', $attr['table'])];
                        return false;
                    }
                } else {
                    if (isset($this->error_sql['returnNameTable'])) {
                        $this->error_sql['checkedHaving'] = $this->error_sql['returnNameTable'];
                        return false;
                    }
                    $this->error_sql['checkedHaving'] = false;
                    return false;
                }
            }
            if ($attribut['expr_type'] == 'operator') {
                if (!in_array(strtoupper((string) $attribut['base_expr']), $this->tested['operator'])) {
                    $this->error_sql['checkedHaving']['operator'] = [$attribut['base_expr']];
                    return false;
                }
            }
        }
        return true;
    }
    /**
     * Check a "ORDER" sentence
     *
     * @param array $order
     * @param array $from
     *
     * @return bool
     */
    public function checked_order($order, $from)
    {
        $order = $order[0];
        if ($order['type'] == 'expression') {
            if ($attr = $this->cut_attribute(trim((string) $order['base_expr']), $from)) {
                if (!$this->attribut_exist_in_table($attr['attribut'], $attr['table'])) {
                    $this->error_sql['checkedOrder']['attribut'] = [$attr['attribut'], implode(', ', $attr['table'])];
                    return false;
                }
            } else {
                if (isset($this->error_sql['returnNameTable'])) {
                    $this->error_sql['checkedOrder'] = $this->error_sql['returnNameTable'];
                    return false;
                }
                $this->error_sql['checkedOrder'] = false;
                return false;
            }
        }
        return true;
    }
    /**
     * Check a "GROUP BY" sentence
     *
     * @param array $group
     * @param array $from
     *
     * @return bool
     */
    public function checked_group_by($group, $from)
    {
        $group = $group[0];
        if ($group['type'] == 'expression') {
            if ($attr = $this->cut_attribute(trim((string) $group['base_expr']), $from)) {
                if (!$this->attribut_exist_in_table($attr['attribut'], $attr['table'])) {
                    $this->error_sql['checkedGroupBy']['attribut'] = [$attr['attribut'], implode(', ', $attr['table'])];
                    return false;
                }
            } else {
                if (isset($this->error_sql['returnNameTable'])) {
                    $this->error_sql['checkedGroupBy'] = $this->error_sql['returnNameTable'];
                    return false;
                }
                $this->error_sql['checkedGroupBy'] = false;
                return false;
            }
        }
        return true;
    }
    /**
     * Check a "LIMIT" sentence
     *
     * @param string[] $limit
     *
     * @return bool
     */
    public function checked_limit($limit)
    {
        if (!preg_match('#^[0-9]+$#', trim($limit['start'])) || !preg_match('#^[0-9]+$#', trim($limit['end']))) {
            $this->error_sql['checkedLimit'] = false;
            return false;
        }
        return true;
    }
}