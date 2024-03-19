<?php

namespace NiceBase\Database;

use Closure;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

class Connection
{
    private PDO $db;
    private array $errors = [];
    protected LoggerInterface $logger;

    public function __construct(
        string $dsn,
        ?string $username = null,
        ?string $password = null,
        ?LoggerInterface $logger = null
    ) {
        try {
            $this->logger = $logger ?? new NullLogger();
            $this->db = new PDO($dsn, $username, $password);
            $this->db->setAttribute(
                PDO::ATTR_ERRMODE,
                PDO::ERRMODE_EXCEPTION
            );
            $this->db->setAttribute(
                PDO::ATTR_DEFAULT_FETCH_MODE,
                PDO::FETCH_ASSOC
            );
        } catch (PDOException $e) {
            throw new Exception(
                'Unable to create database connection - ' . $e->getMessage()
            );
        }
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @throws Exception
     */
    public function execute(string $sql, ?array $params = null): bool | array
    {
        $this->logger->debug('Db::execute', ['sql' => $sql]);
        $this->errors = [];
        if (is_null($params) || [] === $params) {
            // Execute a raw SQL statement (beware of escaping everything)
            try {
                return $this->db->exec($sql) !== false;
            } catch (PDOException) {
                $this->errors = $this->db->errorInfo();
                throw new Exception(
                    "[DB Execute Error] - {$this->errors[2]}",
                    $this->errors[1] ?? 0
                );
            }
        }
        // Execute a query with parameters
        try {
            $stmt = $this->db->prepare($sql);
        } catch (PDOException) {
            $this->errors = $this->db->errorInfo();
            throw new Exception(
                "[DB Prepare Error] - {$this->errors[2]}",
                $this->errors[1] ?? 0
            );
        }
        try {
            $success = $stmt->execute($params);
            if ($success && (0 === stripos($sql, "insert") || 0 === stripos($sql, "update"))) {
                return $stmt->fetch();
            }
            if ($success && (0 === stripos($sql, "select"))) {
                return $stmt->fetchAll();
            }
        } catch (PDOException) {
            $this->errors = $stmt->errorInfo();
            throw new Exception(
                "[DB Execute Error] - {$this->errors[2]}",
                $this->errors[1] ?? 0
            );
        }
        return $success;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @throws Exception
     */
    public function insert(string $table, array $params): ?array
    {
        $columns = array_keys($params);
        $placeholders = array_map(static fn ($column) => ":$column", $columns);
        $sql = "insert into $table "
            . '(' . implode(', ', $columns) . ') '
            . 'values (' . implode(', ', $placeholders) . ') returning *;';
        $row = $this->execute($sql, $params);
        if (is_array($row)) {
            return $row;
        }
        return null;
    }

    /**
     * @throws Exception
     */
    public function update(string $table, array $params, array $conditions): ?array
    {
        $keys = [];
        $ands = [];
        unset($params['id']);
        foreach (array_keys($params) as $key) {
            $keys[] = "$key = :$key";
        }
        foreach (array_keys($conditions) as $key) {
            $ands[] = "$key = :$key";
        }
        $sql = "update $table set "
            . implode(', ', $keys)
            . " where " . implode(' and ', $ands) . " returning *";
        $row = $this->execute($sql, array_merge($params, $conditions));
        if (is_array($row)) {
            return $row;
        }
        return null;
    }

    /**
     * @throws Exception
     */
    public function select(
        string $table,
        array $params = [],
        array $fields = ['*'],
        array $options = []
    ): array {
        $keys = [];
        $sql = 'select ' . implode(', ', $fields)
            . ' from ' . $table;

        $limit = '';
        if (isset($options['limit'])) {
            $limit = ' limit ' . $options['limit'];
        }

        $offset = '';
        if (isset($options['offset'])) {
            $offset = ' offset ' . $options['offset'];
        }

        $order = '';
        if (isset($options['order'])) {
            $order = ' order by ' . $options['order'];
        }

        if (!empty($params)) {
            // Filtered query
            foreach (array_keys($params) as $key) {
                $keys[] = "$key = :$key";
            }
            $sql .=  ' where ' . implode(' and ', $keys);
            $rows = $this->execute($sql . $order . $limit . $offset, $params);
        } else {
            // Non filtered query (select all)
            $this->logger->debug('Db::select', ['sql' => $sql . $order . $limit . $offset]);
            $rows = $this->db->query($sql . $order . $limit . $offset)->fetchAll();
        }
        if (is_array($rows)) {
            return $rows;
        }
        return [];
    }

    public function delete(string $table, array $conditions): void
    {
        $ands = [];
        foreach (array_keys($conditions) as $key) {
            $ands[] = "$key = :$key";
        }
        $sql = "delete from $table where " . implode(' and ', $ands) . " returning *";
        $this->execute($sql, $conditions);
    }

    /** @psalm-suppress PossiblyUnusedReturnValue */
    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    /** @psalm-suppress PossiblyUnusedReturnValue */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /** @psalm-suppress PossiblyUnusedReturnValue */
    public function rollback(): bool
    {
        return $this->db->rollback();
    }

    /**
     * Executes a function in a transaction.
     *
     * The function gets passed this Connection instance as an (optional) parameter.
     *
     * If an exception occurs during execution of the function or transaction commit,
     * the transaction is rolled back and the exception re-thrown.
     *
     * Copied as-is from Doctrine\DBAL\Connection
     *
     * @param Closure(self):T $func The function to execute.
     * @return T The value returned by $func
     * @throws Throwable
     * @template T
     */
    public function transactional(Closure $func)
    {
        $this->beginTransaction();
        try {
            $res = $func($this);
            $this->commit();
            return $res;
        } catch (Throwable $e) {
            $this->rollBack();
            throw $e;
        }
    }
}
