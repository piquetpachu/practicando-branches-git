<?php

declare(strict_types=1);

namespace flight\database;

use flight\core\EventDispatcher;
use flight\util\Collection;
use PDO;
use PDOStatement;

class PdoWrapper extends PDO
{
    /** @var bool $trackApmQueries Whether to track application performance metrics (APM) for queries. */
    protected bool $trackApmQueries = false;

    /** @var array<int,array<string,mixed>> $queryMetrics Metrics related to the database connection. */
    protected array $queryMetrics = [];

    /** @var array<string,string> $connectionMetrics Metrics related to the database connection. */
    protected array $connectionMetrics = [];

    /**
     * Initializes a new PdoWrapper instance with optional application performance metrics tracking.
     *
     * Establishes a PDO database connection using the provided DSN, credentials, and options. If APM tracking is enabled, extracts and stores connection metadata for performance monitoring.
     */
    public function __construct(?string $dsn = null, ?string $username = '', ?string $password = '', ?array $options = null, bool $trackApmQueries = false)
    {
        parent::__construct($dsn, $username, $password, $options);
        $this->trackApmQueries = $trackApmQueries;
        if ($this->trackApmQueries === true) {
            $this->connectionMetrics = $this->pullDataFromDsn($dsn);
        }
    }

    /**
     * Executes a SQL statement with optional parameters and returns the resulting PDOStatement.
     *
     * Supports INSERT, UPDATE, and SELECT queries, including those with dynamic IN clauses. If application performance monitoring (APM) is enabled, records execution metrics for the query.
     *
     * @param string $sql The SQL statement to execute, which may include placeholders for parameters.
     * @param array<int|string, mixed> $params Parameters to bind to the SQL statement placeholders.
     * @return PDOStatement The executed PDOStatement object.
     */
    public function runQuery(string $sql, array $params = []): PDOStatement
    {
        $processed_sql_data = $this->processInStatementSql($sql, $params);
        $sql = $processed_sql_data['sql'];
        $params = $processed_sql_data['params'];
        $start = $this->trackApmQueries === true ? microtime(true) : 0;
        $memory_start = $this->trackApmQueries === true ? memory_get_usage() : 0;
        $statement = $this->prepare($sql);
        $statement->execute($params);
        if ($this->trackApmQueries === true) {
            $this->queryMetrics[] = [
                'sql' => $sql,
                'params' => $params,
                'execution_time' => microtime(true) - $start,
                'row_count' => $statement->rowCount(),
                'memory_usage' => memory_get_usage() - $memory_start
            ];
        }
        return $statement;
    }

    /**
     * Retrieves the value of the first field from the first row of a query result.
     *
     * Executes the provided SQL query with optional parameters and returns the value of the first column from the first row, or `false` if no results are found.
     *
     * @param string $sql SQL query to execute.
     * @param array<int|string,mixed> $params Parameters to bind to the SQL query.
     * @return mixed The value of the first field in the first row, or `false` if no rows are returned.
     */
    public function fetchField(string $sql, array $params = [])
    {
        $result = $this->fetchRow($sql, $params);
        $data = $result->getData();
        return reset($data);
    }

    /**
     * Retrieves the first row from the result set of a SQL query as a Collection.
     *
     * Appends `LIMIT 1` to the query if not already present. Returns an empty Collection if no rows are found.
     *
     * @return Collection The first row of the result set, or an empty Collection if there are no results.
     */
    public function fetchRow(string $sql, array $params = []): Collection
    {
        $sql .= stripos($sql, 'LIMIT') === false ? ' LIMIT 1' : '';
        $result = $this->fetchAll($sql, $params);
        return count($result) > 0 ? $result[0] : new Collection();
    }

    /**
     * Executes a SQL query and returns all result rows as an array of Collection objects.
     *
     * Handles parameterized queries, including dynamic expansion of `IN` clauses. Each row in the result set is wrapped in a Collection for convenient data access. Returns an empty array if no results are found.
     *
     * @param string $sql SQL query to execute.
     * @param array<int|string,mixed> $params Parameters to bind to the query.
     * @return array<int,Collection> Array of Collection objects representing each result row.
     */
    public function fetchAll(string $sql, array $params = [])
    {
        $processed_sql_data = $this->processInStatementSql($sql, $params);
        $sql = $processed_sql_data['sql'];
        $params = $processed_sql_data['params'];
        $start = $this->trackApmQueries === true ? microtime(true) : 0;
        $memory_start = $this->trackApmQueries === true ? memory_get_usage() : 0;
        $statement = $this->prepare($sql);
        $statement->execute($params);
        $results = $statement->fetchAll();
        if ($this->trackApmQueries === true) {
            $this->queryMetrics[] = [
                'sql' => $sql,
                'params' => $params,
                'execution_time' => microtime(true) - $start,
                'row_count' => $statement->rowCount(),
                'memory_usage' => memory_get_usage() - $memory_start
            ];
        }
        if (is_array($results) === true && count($results) > 0) {
            foreach ($results as &$result) {
                $result = new Collection($result);
            }
        } else {
            $results = [];
        }
        return $results;
    }

    /**
     * Extracts the database engine, database name, and host from a DSN string.
     *
     * Supports parsing for SQLite and other common database engines. For SQLite, the database name is the file name and host is set to 'localhost'. For other engines, extracts the `dbname` and `host` parameters from the DSN.
     *
     * @param string $dsn The Data Source Name (DSN) string.
     * @return array<string,string> Associative array with keys: 'engine', 'database', and 'host'.
     */
    protected function pullDataFromDsn(string $dsn): array
    {
        // pull the engine from the dsn (sqlite, mysql, pgsql, etc)
        preg_match('/^([a-zA-Z]+):/', $dsn, $matches);
        $engine = $matches[1] ?? 'unknown';

        if ($engine === 'sqlite') {
            // pull the path from the dsn
            preg_match('/sqlite:(.*)/', $dsn, $matches);
            $dbname = basename($matches[1] ?? 'unknown');
            $host = 'localhost';
        } else {
            // pull the database from the dsn
            preg_match('/dbname=([^;]+)/', $dsn, $matches);
            $dbname = $matches[1] ?? 'unknown';
            // pull the host from the dsn
            preg_match('/host=([^;]+)/', $dsn, $matches);
            $host = $matches[1] ?? 'unknown';
        }

        return [
            'engine' => $engine,
            'database' => $dbname,
            'host' => $host
        ];
    }

    /**
     * Triggers an event to log collected query and connection metrics if APM tracking is enabled.
     *
     * If application performance monitoring is active and metrics are available, this method dispatches an event containing all recorded query and connection data, then resets the query metrics.
     */
    public function logQueries(): void
    {
        if ($this->trackApmQueries === true && $this->connectionMetrics !== [] && $this->queryMetrics !== []) {
            EventDispatcher::getInstance()->trigger('flight.db.queries', $this->connectionMetrics, $this->queryMetrics);
            $this->queryMetrics = []; // Reset after logging
        }
    }

    /**
     * Expands SQL `IN(?)` placeholders to match the number of parameters provided.
     *
     * Converts SQL statements containing `IN(?)` to use the correct number of placeholders based on the corresponding parameter, supporting both arrays and comma-separated strings. Adjusts the SQL and parameters array accordingly for safe execution with PDO.
     *
     * @param string $sql The SQL statement, potentially containing `IN(?)` placeholders.
     * @param array<int|string,mixed> $params The parameters for the SQL statement, where values for `IN(?)` can be arrays or comma-separated strings.
     * @return array<string,string|array<int|string,mixed>> An array with updated 'sql' and 'params' keys reflecting the expanded placeholders and parameters.
     */
    protected function processInStatementSql(string $sql, array $params = []): array
    {
        // Replace "IN(?)" with "IN(?,?,?)"
        $sql = preg_replace('/IN\s*\(\s*\?\s*\)/i', 'IN(?)', $sql);

        $current_index = 0;
        while (($current_index = strpos($sql, 'IN(?)', $current_index)) !== false) {
            $preceeding_count = substr_count($sql, '?', 0, $current_index - 1);

            $param = $params[$preceeding_count];
            $question_marks = '?';

            if (is_string($param) || is_array($param)) {
                $params_to_use = $param;
                if (is_string($param)) {
                    $params_to_use = explode(',', $param);
                }

                foreach ($params_to_use as $key => $value) {
                    if (is_string($value)) {
                        $params_to_use[$key] = trim($value);
                    }
                }

                $question_marks = join(',', array_fill(0, count($params_to_use), '?'));
                $sql = substr_replace($sql, $question_marks, $current_index + 3, 1);

                array_splice($params, $preceeding_count, 1, $params_to_use);
            }

            $current_index += strlen($question_marks) + 4;
        }

        return ['sql' => $sql, 'params' => $params];
    }
}
