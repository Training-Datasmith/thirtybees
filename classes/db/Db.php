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
use Thirtybees\Core\Database\Read_Only_Connection;
if (file_exists(_PS_ROOT_DIR_ . '/config/settings.inc.php')) {
    include_once _PS_ROOT_DIR_ . '/config/settings.inc.php';
}
/**
 * Class DbCore
 */
class Db_Core implements Read_Only_Connection
{
    public const MYSQL_ERROR_SERVER_GONE = 2006;
    public const MYSQL_ERROR_LOCK_WAIT_TIMEOUT = 1205;
    public const MYSQL_ERROR_LOCK_DEADLOCK = 1213;
    public const MAX_ATTEMPTS = 3;
    /**
     * @var int Constant used by insert() method
     */
    public const INSERT = 1;
    /**
     * @var int Constant used by insert() method
     */
    public const INSERT_IGNORE = 2;
    /**
     * @var int Constant used by insert() method
     */
    public const REPLACE = 3;
    /**
     * @var int Constant used by insert() method
     */
    public const ON_DUPLICATE_KEY = 4;
    /**
     * @var array List of DB instances
     */
    public static $instance = [];
    /**
     * @var array List of server settings
     */
    public static $_servers = [];
    /**
     * @var bool|null Flag used to load slave servers only once
     */
    public static $_slave_servers_loaded;
    protected bool $throw_on_error;
    /**
     * @var PDO Resource link
     */
    protected $link;
    /**
     * @var PDOStatement|false SQL cached result
     */
    protected $result;
    /**
     * Store last executed query
     *
     * @var string
     */
    protected $last_query;
    /**
     * Store hash of the last executed query
     *
     * @var string
     *
     * @deprecated 1.0.4 For backwards compatibility only
     */
    protected $last_query_hash;
    /**
     * Last cached query
     *
     * @var string
     *
     * @deprecated 1.0.4 For backwards compatibility only
     */
    protected $last_cached = false;
    /**
     * @var bool
     *
     * @deprecated 1.0.4 For backwards compatibility only
     */
    protected $is_cache_enabled = false;
    /**
     * Instantiates a database connection
     *
     * @param string $server Server address
     * @param string $user User login
     * @param string $password User password
     * @param string $database Database name
     * @param bool $connect If false, don't connect in constructor (since 1.5.0.1)
     */
    public function __construct(protected $server, protected $user, protected $password, protected $database, $connect = true)
    {
        $this->throw_on_error = defined('_PS_DEBUG_SQL_') && _PS_DEBUG_SQL_;
        if ($connect) {
            $this->connect();
        }
    }
    /**
     * Opens a database connection
     *
     * @return PDO
     */
    public function connect()
    {
        try {
            $this->link = static::_get_pdo($this->server, $this->user, $this->password, $this->database, 5);
        } catch (PDOException $e) {
            die(sprintf(Tools::display_error('Link to database cannot be established: %s'), $e->get_message()));
        }
        // UTF-8 support
        if ($this->link->exec('SET NAMES \'utf8mb4\'') === false) {
            die(Tools::display_error('thirty bees Fatal error: no UTF-8 support. Please check your server configuration.'));
        }
        $this->link->exec('SET SESSION sql_mode = \'\'');
        return $this->link;
    }
    /**
     * Returns a new PDO object (database link)
     *
     * @param string $user
     * @param string $password
     * @param string $dbname
     * @param int $timeout
     *
     */
    protected static function _get_pdo(string $host, $user, $password, ?string $dbname, $timeout = 5): \PDO
    {
        $dsn = 'mysql:';
        if ($dbname) {
            $dsn .= 'dbname=' . $dbname . ';';
        }
        if (preg_match('/^(.*):([0-9]+)$/', $host, $matches)) {
            $dsn .= 'host=' . $matches[1] . ';port=' . $matches[2];
        } elseif (preg_match('#^.*:(/.*)$#', $host, $matches)) {
            $dsn .= 'unix_socket=' . $matches[1];
        } else {
            $dsn .= 'host=' . $host;
        }
        return new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT, PDO::ATTR_TIMEOUT => $timeout, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, PDO::ATTR_STRINGIFY_FETCHES => _TB_DB_STRINGIFY_FETCHES_, PDO::MYSQL_ATTR_MULTI_STATEMENTS => _TB_DB_ALLOW_MULTI_STATEMENTS_QUERIES_]);
    }
    /**
     * Displays last SQL error
     *
     * @param string|bool $sql
     *
     * @throws PrestaShopDatabaseException
     */
    public function display_error($sql = false): void
    {
        $errno = $this->get_number_error();
        if ($errno) {
            throw new Presta_Shop_Database_Exception($this->get_msg_error(), $sql);
        }
    }
    /**
     * Returns the number of the error from previous database operation
     */
    public function get_number_error(): int
    {
        $error = $this->link->error_info();
        return isset($error[1]) ? (int) $error[1] : 0;
    }
    /**
     * Returns the text of the error message from previous database operation
     *
     * @return string
     */
    public function get_msg_error()
    {
        $error = $this->link->error_info();
        return $error[0] == '00000' ? '' : $error[2];
    }
    /**
     * Try a connection to the database
     *
     * @param string $server Server address
     * @param string $user Login for database connection
     * @param string $pwd Password for database connection
     * @param string $db Database name
     * @param bool $newDbLink
     * @param string|bool $engine
     * @param int $timeout
     *
     * @return int Error code or 0 if connection was successful
     *
     */
    public static function try_to_connect($server, $user, $pwd, $db, $new_db_link = true, $engine = null, $timeout = 5): int
    {
        try {
            $link = static::_get_pdo($server, $user, $pwd, $db, $timeout);
        } catch (PDOException $e) {
            // hhvm wrongly reports error status 42000 when the database does not exist - might change in the future
            return $e->get_code() == 1049 || defined('HHVM_VERSION') && $e->get_code() == 42000 ? 2 : 1;
        }
        unset($link);
        return 0;
    }
    /**
     * Tries to connect and create a new database
     *
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $dbname
     * @param bool $dropAfter If true, drops the created database.
     *
     * @return bool
     */
    public static function create_database($host, $user, $password, $dbname, $drop_after = false)
    {
        try {
            $link = static::_get_pdo($host, $user, $password, false);
            $escaped_name = str_replace('`', '\`', $dbname);
            $create_db_ddl = 'CREATE DATABASE `' . $escaped_name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
            $success = $link->exec($create_db_ddl);
            if ($drop_after && $link->exec('DROP DATABASE `' . $escaped_name . '`') !== false) {
                return true;
            }
        } catch (PDOException) {
            return false;
        }
        return $success;
    }
    /**
     * Try a connection to the database and set names to UTF-8
     *
     * @param string $server Server address
     * @param string $user Login for database connection
     * @param string $pwd Password for database connection
     *
     * @return bool
     */
    public static function try_utf8($server, $user, $pwd)
    {
        try {
            $link = static::_get_pdo($server, $user, $pwd, false, 5);
        } catch (PDOException) {
            return false;
        }
        $result = $link->exec('SET NAMES \'utf8mb4\'');
        unset($link);
        return $result !== false;
    }
    /**
     * @param Db $testDb
     * Unit testing purpose only
     */
    public static function set_instance_for_testing($test_db): void
    {
        static::$instance[0] = $test_db;
    }
    /**
     * Unit testing purpose only
     */
    public static function delete_testing_instance(): void
    {
        static::$instance = [];
    }
    /**
     * Try a connection to the database
     *
     * @param string $server Server address
     * @param string $user Login for database connection
     * @param string $pwd Password for database connection
     * @param string $db Database name
     * @param bool $newDbLink
     * @param string|bool $engine
     * @param int $timeout
     *
     * @return int Error code or 0 if connection was successful
     */
    public static function check_connection($server, $user, $pwd, $db, $new_db_link = true, $engine = null, $timeout = 5)
    {
        return static::try_to_connect($server, $user, $pwd, $db, $new_db_link, $engine, $timeout);
    }
    /**
     * Try a connection to the database and set names to UTF-8
     *
     * @param string $server Server address
     * @param string $user Login for database connection
     * @param string $pwd Password for database connection
     *
     * @return bool
     */
    public static function check_encoding($server, $user, $pwd)
    {
        return static::try_utf8($server, $user, $pwd);
    }
    /**
     * Try a connection to the database and check if at least one table with same prefix exists
     *
     * @param string $server Server address
     * @param string $user Login for database connection
     * @param string $pwd Password for database connection
     * @param string $db Database name
     * @param string $prefix Tables prefix
     *
     * @return bool
     */
    public static function has_table_with_same_prefix($server, $user, $pwd, $db, string $prefix)
    {
        try {
            $link = static::_get_pdo($server, $user, $pwd, $db, 5);
        } catch (PDOException) {
            return false;
        }
        $sql = 'SHOW TABLES LIKE \'' . $prefix . '%\'';
        $result = $link->query($sql);
        return (bool) $result->fetch();
    }
    /**
     * Execute a query and get result resource
     *
     * @param string|DbQuery $sql
     *
     * @return PDOStatement|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function query($sql)
    {
        $sql_string = $this->get_sql_string($sql);
        $this->last_query = $sql_string;
        $this->result = $this->_query($sql_string);
        if ($this->result === false && $this->throw_on_error) {
            $this->display_error($sql_string);
        }
        return $this->result;
    }
    /**
     * Tries to connect to the database and create a table (checking creation privileges)
     *
     * @param string $server
     * @param string $user
     * @param string $pwd
     * @param string $db
     * @param string|null $engine Table engine
     * @return bool|string True, false or error
     */
    public static function check_create_privilege($server, $user, $pwd, $db, string $prefix, $engine = null)
    {
        try {
            $link = static::_get_pdo($server, $user, $pwd, $db, 5);
        } catch (PDOException) {
            return false;
        }
        if ($engine === null) {
            $engine = 'InnoDB';
        }
        $result = $link->query('
		CREATE TABLE `' . $prefix . 'test` (
			`test` tinyint(1) unsigned NOT NULL
		) ENGINE=' . $engine);
        if (!$result) {
            $error = $link->error_info();
            return $error[2];
        }
        $link->query('DROP TABLE `' . $prefix . 'test`');
        return true;
    }
    /**
     * Checks if auto increment value and offset is 1
     *
     * @param string $server
     * @param string $user
     * @param string $pwd
     *
     * @return bool
     */
    public static function check_auto_increment($server, $user, $pwd)
    {
        try {
            $link = static::_get_pdo($server, $user, $pwd, false, 5);
        } catch (PDOException) {
            return false;
        }
        $ret = ($result = $link->query('SELECT @@auto_increment_increment as aii')) && ($row = $result->fetch()) && $row['aii'] == 1;
        $ret = ($result = $link->query('SELECT @@auto_increment_offset as aio')) && ($row = $result->fetch()) && $row['aio'] == 1 && $ret;
        unset($link);
        return $ret;
    }
    /**
     * Executes return the result of $sql as array
     *
     * @param string|DbQuery $sql Query to execute
     * @param bool $array Return an array instead of a result object (deprecated since 1.5.0.1, use query method instead)
     * @param bool $useCache Deprecated, the internal query cache is no longer used
     *
     * @return array|bool|PDOStatement
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function execute_s($sql, $array = true, $use_cache = true)
    {
        $sql_string = $this->get_sql_string($sql);
        // This method must be used only with queries which display results
        if (!preg_match('#^\s*\(?\s*(select|show|explain|describe|desc)\s#i', $sql_string)) {
            if ($this->throw_on_error) {
                $this->last_query = $sql_string;
                $this->result = false;
                throw new Presta_Shop_Database_Exception('Db::executeS method should be used for SELECT queries only.', $sql_string);
            }
            $call_point = Tools::get_call_point([Db::class]);
            $error = 'Db::executeS method should be used for SELECT queries only. ';
            $error .= 'Calling this method with other SQL statements will raise exception in the future. ';
            $error .= 'Called from: ' . $call_point['description'] . '. ';
            $error .= 'Illegal SQL: [' . $sql_string . ']';
            trigger_error($error, E_USER_DEPRECATED);
            return $this->execute($sql_string, $use_cache);
        }
        $this->result = $this->query($sql_string);
        if (!$this->result) {
            return false;
        }
        if (!$array) {
            return $this->result;
        }
        return $this->result->fetch_all(PDO::FETCH_ASSOC);
    }
    /**
     * Executes a query
     *
     * @param string|DbQuery $sql
     * @param bool $useCache
     *
     * @throws PrestaShopException
     */
    public function execute($sql, $use_cache = true): bool
    {
        $this->result = $this->query($sql);
        return (bool) $this->result;
    }
    /**
     * Returns all rows from the result set.
     *
     * @param bool $result
     */
    protected function get_all($result = false): false|array
    {
        if (!$result) {
            $result = $this->result;
        }
        if (!is_object($result)) {
            return false;
        }
        return $result->fetch_all(PDO::FETCH_ASSOC);
    }
    /**
     * Returns read-only dataase connection
     *
     * If only single database server exists, the same connection is used for read and write access.
     *
     * If multiple database server exists (MASTER -> SLAVE replication), then this method returns connection
     * to SLAVE server
     *
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function read_only(): Read_Only_Connection
    {
        return static::get_instance(false);
    }
    /**
     * Returns database object instance.
     *
     * @param bool $master Decides whether the connection to be returned by the master server or the slave server
     *
     * @return Db Singleton instance of Db object
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public static function get_instance($master = true)
    {
        static $id = 0;
        // This MUST not be declared with the class members because some defines (like _DB_SERVER_) may not exist yet (the constructor can be called directly with params)
        if (!static::$_servers) {
            static::$_servers = [['server' => _DB_SERVER_, 'user' => _DB_USER_, 'password' => _DB_PASSWD_, 'database' => _DB_NAME_]];
        }
        if (!$master) {
            static::load_slave_servers();
        }
        $total_servers = count(static::$_servers);
        if ($master || $total_servers == 1) {
            $id_server = 0;
        } else {
            $id++;
            $id_server = $total_servers > 2 && $id % $total_servers != 0 ? $id % $total_servers : 1;
        }
        if (!isset(static::$instance[$id_server]) || !static::$instance[$id_server]->link) {
            static::$instance[$id_server] = static::create_instance(static::$_servers[$id_server]['server'], static::$_servers[$id_server]['user'], static::$_servers[$id_server]['password'], static::$_servers[$id_server]['database']);
            $connection = static::$instance[$id_server];
            if (!Configuration::configuration_is_loaded()) {
                Configuration::load_configuration_from_db($connection);
            }
            $connection->set_time_zone(Tools::get_time_zone());
        }
        return static::$instance[$id_server];
    }
    /**
     * Loads configuration settings for slave servers if needed.
     *
     * @return void
     */
    protected static function load_slave_servers()
    {
        if (static::$_slave_servers_loaded !== null) {
            return;
        }
        // Add here your slave(s) server(s) in this file
        if (file_exists(_PS_ROOT_DIR_ . '/config/db_slave_server.inc.php')) {
            static::$_servers = array_merge(static::$_servers, require _PS_ROOT_DIR_ . '/config/db_slave_server.inc.php');
        }
        static::$_slave_servers_loaded = true;
    }
    /**
     * Creates new database object instance.
     *
     * @param string $server
     * @param string $user
     * @param string $password
     * @param string $database
     *
     * @return Db
     */
    public static function create_instance($server, $user, $password, $database)
    {
        return new Db($server, $user, $password, $database);
    }
    /**
     * Set timezone on current connection.
     *
     * @param string $timezone
     */
    public function set_time_zone($timezone): void
    {
        try {
            $now = new DateTime('now', new DateTimeZone($timezone));
            $minutes = $now->get_offset() / 60;
            $sign = $minutes < 0 ? -1 : 1;
            $minutes = abs($minutes);
            $hours = floor($minutes / 60);
            $minutes -= $hours * 60;
            $offset = sprintf('%+d:%02d', $hours * $sign, $minutes);
            $this->link->exec("SET time_zone='{$offset}'");
        } catch (Exception $e) {
            throw new RuntimeException('Failed to set timezone', 0, $e);
        }
    }
    /**
     * Returns ID of the last inserted row.
     *
     * @return string|false
     */
    public function Insert_ID(): string|false
    {
        return $this->link->last_insert_id();
    }
    /**
     * Return the number of rows affected by the last SQL query.
     *
     * @return int
     */
    public function Affected_Rows()
    {
        return $this->result->row_count();
    }
    /**
     * Returns database server version.
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    public function get_version()
    {
        return $this->get_value('SELECT VERSION()');
    }
    /**
     * Returns a value from the first row, first column of a SELECT query
     *
     * @param string|DbQuery $sql
     *
     * @return mixed|false
     * @throws PrestaShopException
     */
    public function get_value($sql)
    {
        if (!$result = $this->get_row($sql)) {
            return false;
        }
        return array_shift($result);
    }
    /**
     * Returns an associative array containing the first row of the query
     * This function automatically adds "LIMIT 1" to the query
     *
     * @param string|DbQuery $sql the select query (without "LIMIT 1")
     *
     * @return array|false
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_row($sql): array|false
    {
        $sql_string = $this->get_sql_string($sql);
        $sql_string = rtrim($sql_string, " \t\n\r\x00\v;") . ' LIMIT 1';
        $this->result = $this->query($sql_string);
        if (!$this->result) {
            $result = false;
        } else {
            $result = $this->next_row($this->result);
        }
        return is_array($result) ? $result : false;
    }
    /**
     * Returns the next row from the result set.
     *
     * @param PDOStatement|false $result
     *
     * @return array|false|null
     */
    public function next_row($result = false)
    {
        if (!$result) {
            $result = $this->result;
        }
        if (!is_object($result)) {
            return false;
        }
        return $result->fetch(PDO::FETCH_ASSOC);
    }
    /**
     * Sets the current active database on the server that's associated with the specified link identifier.
     * Do not remove, useful for some modules.
     *
     * @param string $dbName
     *
     * @return int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function set_db($db_name): int|false
    {
        return $this->link->exec('USE ' . p_sql($db_name));
    }
    /**
     * Selects best table engine.
     */
    public function get_best_engine(): string
    {
        return 'InnoDB';
    }
    /**
     * Closes connection to database
     */
    public function __destruct()
    {
        if ($this->link) {
            $this->disconnect();
        }
    }
    /**
     * Destroys the database connection link
     */
    public function disconnect(): void
    {
        unset($this->link);
    }
    /**
     * Filter SQL query within a blacklist
     *
     * @param string $table Table where insert/update data
     * @param array $values Data to insert/update
     * @param string $type INSERT or UPDATE
     * @param string $where WHERE clause, only for UPDATE (optional)
     * @param int $limit LIMIT clause (optional)
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public function auto_execute_with_null_values($table, $values, $type, $where = '', $limit = 0)
    {
        return $this->auto_execute($table, $values, $type, $where, $limit, 0, true);
    }
    /**
     * Executes SQL query based on selected type
     *
     * @param string $table
     * @param array $data
     * @param string $type (INSERT, INSERT IGNORE, REPLACE, UPDATE).
     * @param string $where
     * @param int $limit
     * @param bool $useCache
     * @param bool $useNull
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function auto_execute($table, $data, $type, $where = '', $limit = 0, $use_cache = true, $use_null = false)
    {
        $type = strtoupper($type);
        return match ($type) {
            'INSERT' => $this->insert($table, $data, $use_null, $use_cache, static::INSERT, false),
            'INSERT IGNORE' => $this->insert($table, $data, $use_null, $use_cache, static::INSERT_IGNORE, false),
            'REPLACE' => $this->insert($table, $data, $use_null, $use_cache, static::REPLACE, false),
            'UPDATE' => $this->update($table, $data, $where, $limit, $use_null, $use_cache, false),
            default => throw new Presta_Shop_Database_Exception('Wrong argument (miss type) in static::autoExecute()'),
        };
    }
    /**
     * Executes an INSERT query
     *
     * @param string $table Table name without prefix
     * @param array $data Data to insert as associative array. If $data is a list of arrays, multiple insert will be done
     * @param bool $nullValues If we want to use NULL values instead of empty quotes
     * @param bool $useCache
     * @param int $type Must be static::INSERT or static::INSERT_IGNORE or static::REPLACE
     * @param bool $addPrefix Add or not _DB_PREFIX_ before table name
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     */
    public function insert($table, $data, $null_values = false, $use_cache = true, $type = self::INSERT, $add_prefix = true)
    {
        if (!$data && !$null_values) {
            return true;
        }
        if ($add_prefix && _DB_PREFIX_ && strncmp(_DB_PREFIX_, $table, strlen(_DB_PREFIX_)) !== 0) {
            $table = _DB_PREFIX_ . $table;
        }
        if ($type == static::INSERT) {
            $insert_keyword = 'INSERT';
        } elseif ($type == static::INSERT_IGNORE) {
            $insert_keyword = 'INSERT IGNORE';
        } elseif ($type == static::REPLACE) {
            $insert_keyword = 'REPLACE';
        } elseif ($type == static::ON_DUPLICATE_KEY) {
            $insert_keyword = 'INSERT';
        } else {
            throw new Presta_Shop_Database_Exception('Bad keyword, must be static::INSERT or static::INSERT_IGNORE or static::REPLACE');
        }
        // Check if $data is a list of row
        $current = current($data);
        if (!is_array($current) || isset($current['type'])) {
            $data = [$data];
        }
        $keys = [];
        $values_stringified = [];
        $first_loop = true;
        $duplicate_key_stringified = '';
        foreach ($data as $row_data) {
            $values = [];
            foreach ($row_data as $key => $value) {
                if (!$first_loop) {
                    // Check if row array mapping are the same
                    if (!in_array("`{$key}`", $keys)) {
                        throw new Presta_Shop_Database_Exception('Keys form $data subarray don\'t match');
                    }
                    if ($duplicate_key_stringified != '') {
                        throw new Presta_Shop_Database_Exception('On duplicate key cannot be used on insert with more than 1 VALUE group');
                    }
                } else {
                    $keys[] = '`' . bq_sql($key) . '`';
                }
                if (!is_array($value)) {
                    $value = ['type' => 'text', 'value' => $value];
                }
                if ($value['type'] == 'sql') {
                    $values[] = $string_value = $value['value'];
                } else {
                    $values[] = $string_value = $null_values && ($value['value'] === '' || is_null($value['value'])) ? 'NULL' : "'{$value['value']}'";
                }
                if ($type == static::ON_DUPLICATE_KEY) {
                    $duplicate_key_stringified .= '`' . bq_sql($key) . '` = ' . $string_value . ',';
                }
            }
            $first_loop = false;
            $values_stringified[] = '(' . implode(', ', $values) . ')';
        }
        $keys_stringified = implode(', ', $keys);
        $sql = $insert_keyword . ' INTO `' . $table . '` (' . $keys_stringified . ') VALUES ' . implode(', ', $values_stringified);
        if ($type == static::ON_DUPLICATE_KEY) {
            $sql .= ' ON DUPLICATE KEY UPDATE ' . substr($duplicate_key_stringified, 0, -1);
        }
        return (bool) $this->query($sql);
    }
    /**
     * Executes an UPDATE query
     *
     * @param string $table Table name without prefix
     * @param array $data Data to insert as associative array. If $data is a list of arrays, multiple insert will be done
     * @param string|array $where WHERE condition
     * @param int $limit
     * @param bool $nullValues If we want to use NULL values instead of empty quotes
     * @param bool $useCache
     * @param bool $addPrefix Add or not _DB_PREFIX_ before table name
     *
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function update($table, $data, $where = '', $limit = 0, $null_values = false, $use_cache = true, $add_prefix = true)
    {
        if (!$data) {
            return true;
        }
        if ($add_prefix && strncmp(_DB_PREFIX_, $table, strlen(_DB_PREFIX_)) !== 0) {
            $table = _DB_PREFIX_ . $table;
        }
        if (is_array($where)) {
            $where = implode(' AND ', array_filter($where));
        }
        $sql = 'UPDATE `' . bq_sql($table) . '` SET ';
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $value = ['type' => 'text', 'value' => $value];
            }
            if ($value['type'] == 'sql') {
                $sql .= '`' . bq_sql($key) . "` = {$value['value']},";
            } else {
                $sql .= $null_values && ($value['value'] === '' || is_null($value['value'])) ? '`' . bq_sql($key) . '` = NULL,' : '`' . bq_sql($key) . "` = '{$value['value']}',";
            }
        }
        $sql = rtrim($sql, ',');
        if ($where) {
            $sql .= ' WHERE ' . $where;
        }
        if ($limit) {
            $sql .= ' LIMIT ' . (int) $limit;
        }
        return (bool) $this->query($sql);
    }
    /**
     * Executes a DELETE query
     *
     * @param string $table Name of the table to delete
     * @param string|array $where WHERE clause on query
     * @param int $limit Number max of rows to delete
     * @param bool $useCache Use cache or not
     * @param bool $addPrefix Add or not _DB_PREFIX_ before table name
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function delete($table, $where = '', $limit = 0, $use_cache = true, $add_prefix = true): bool
    {
        if ($add_prefix && strncmp(_DB_PREFIX_, $table, strlen(_DB_PREFIX_)) !== 0) {
            $table = _DB_PREFIX_ . $table;
        }
        if (is_array($where)) {
            $where = implode(' AND ', array_filter($where));
        }
        $this->result = false;
        $sql = 'DELETE FROM `' . bq_sql($table) . '`' . ($where ? ' WHERE ' . $where : '') . ($limit ? ' LIMIT ' . (int) $limit : '');
        $res = $this->query($sql);
        return (bool) $res;
    }
    /**
     * Executes sql and returns the result of $sql as an array
     *
     * @param string|DbQuery $sql the select query
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function get_array($sql): array
    {
        $sql_string = $this->get_sql_string($sql);
        // This method must be used only with queries which display results
        if (!preg_match('#^\s*\(?\s*(select|show|explain|describe|desc)\s#i', $sql_string)) {
            $this->result = false;
            $this->last_query = $sql_string;
            throw new Presta_Shop_Database_Exception('Db::getArrray method can be used for SELECT queries only.', $sql_string);
        }
        $this->result = $this->query($sql_string);
        return $this->result ? $this->result->fetch_all(PDO::FETCH_ASSOC) : [];
    }
    /**
     * Get number of rows for last result
     */
    public function num_rows(): int
    {
        if ($this->result) {
            return $this->result->row_count();
        }
        return 0;
    }
    /**
     * Sanitize data which will be injected into SQL query
     *
     * @param string $string SQL data which will be injected into SQL query
     * @param bool $htmlOk Does data contain HTML code ? (optional)
     * @param bool $bqSql Escape backquotes
     *
     * @return string Sanitized data
     */
    public function escape($string, $html_ok = false, $bq_sql = false)
    {
        if (!is_numeric($string)) {
            $string = $this->_escape($string);
            if (!$html_ok) {
                $string = strip_tags(Tools::nl2br($string));
            }
            if ($bq_sql === true) {
                $string = str_replace('`', '\`', $string);
            }
        }
        return $string;
    }
    /**
     * Escapes illegal characters in a string. Protect string against SQL injections
     *
     * @param string $str
     */
    public function _escape($str): string
    {
        if (is_null($str)) {
            return '';
        }
        $search = ['\\', "\x00", "\n", "\r", "\x1a", "'", '"'];
        $replace = ['\\\\', '\0', '\n', '\r', "\\Z", "\\'", '\"'];
        return str_replace($search, $replace, $str);
    }
    /**
     * Get used link instance
     *
     * @return PDO Resource
     */
    public function get_link()
    {
        return $this->link;
    }
    /**
     * Disable the use of the cache
     *
     * @deprecated 1.0.4 For backwards compatibility only
     */
    public function disable_cache()
    {
    }
    /**
     * Enable & flush the cache
     *
     * @deprecated 1.0.4 For backwards compatibility only
     */
    public function enable_cache()
    {
    }
    /**
     * Returns database class
     *
     *
     * @deprecated 1.5.0
     */
    public static function get_class(): string
    {
        return 'Db';
    }
    /**
     * Get number of rows in a result
     *
     * @param PDOStatement $result
     *
     *
     * @deprecated 1.5.0
     */
    protected function _num_rows($result): int
    {
        return $result->row_count();
    }
    /**
     * Executes an SQL statement, returning a result set as a PDOStatement object or true/false.
     *
     * @param string $sql
     *
     * @return PDOStatement|false
     */
    protected function _query($sql)
    {
        for ($attempt = 0; $attempt < static::MAX_ATTEMPTS; $attempt++) {
            $result = $this->link->query($sql);
            if ($result !== false) {
                return $result;
            }
            if ($attempt === static::MAX_ATTEMPTS - 1) {
                return false;
            }
            // handle errors
            $error = $this->get_number_error();
            if ($error === static::MYSQL_ERROR_SERVER_GONE) {
                if (!$this->connect()) {
                    return false;
                }
            } elseif ($error === static::MYSQL_ERROR_LOCK_WAIT_TIMEOUT || $error === static::MYSQL_ERROR_LOCK_DEADLOCK) {
                $wait = (1 << $attempt) * 1000;
                try {
                    $rand = floor(random_int(0, $wait));
                    $wait = $rand;
                } catch (Exception) {
                }
                if ($wait > 0) {
                    usleep($wait);
                }
            } else {
                return false;
            }
        }
        return false;
    }
    /**
     * Executes a query
     *
     * @param string|DbQuery $sql
     * @param bool $useCache Deprecated, the internal query cache is no longer used
     *
     * @return bool|PDOStatement
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @deprecated 1.1.1
     */
    protected function q($sql, $use_cache = true)
    {
        Tools::display_as_deprecated();
        return $this->query($sql);
    }
    /**
     * Executes a query
     *
     * @param string $sql
     * @param int $useCache
     *
     *
     * @throws PrestaShopException
     * @deprecated 2.0.0
     */
    public static function ps($sql, $use_cache = 1): array
    {
        Tools::display_as_deprecated();
        return static::read_only()->get_array($sql);
    }
    /**
     * Executes a query
     *
     * @param string|DbQuery $sql
     * @param bool $useCache
     *
     * @throws PrestaShopDatabaseException
     *
     * @throws PrestaShopException
     * @deprecated 2.0.0
     */
    public static function s($sql, $use_cache = true): array
    {
        Tools::display_as_deprecated();
        return static::read_only()->get_array($sql);
    }
    /**
     * Executes a query and kills process (dies)
     *
     * @param string $sql
     * @param int $useCache
     *
     * @throws PrestaShopException
     * @deprecated 2.0.0
     *
     */
    public static function ds($sql, $use_cache = 1): never
    {
        Tools::display_as_deprecated();
        static::get_instance()->execute($sql);
        exit;
    }
    /**
     * @param string|DbQuery $sql
     *
     * @throws PrestaShopException
     */
    protected function get_sql_string($sql): string
    {
        if ($sql instanceof Db_Query) {
            return $sql->build();
        }
        return (string) $sql;
    }
}