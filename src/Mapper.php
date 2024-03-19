<?php

declare(strict_types=1);

namespace NiceBase;

use Closure;
use InvalidArgumentException;
use NiceBase\Database\Connection;
use NiceBase\Database\Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

abstract class Mapper
{
    protected static Connection $db;
    protected static LoggerInterface $logger;
    protected string $table;
    protected string $class;

    /**
     * Initialises the Mapper engine with dependencies
     */
    public static function init(Connection $db, ?LoggerInterface $logger = null): void
    {
        self::$db = $db;
        self::$logger = $logger ?? new NullLogger();
    }

    public function find(int $id, ?array $fields = null): ?Model
    {
        $fields ??= forward_static_call([$this->class, 'getAttributes']) ?? ['*'];
        if (false === $fields) {
            throw new RuntimeException('Unable to get attributes for ' . $this->class);
        }
        if ($rows = self::$db->select($this->table, ['id' => $id], $fields)) {
            $model = forward_static_call([$this->class, 'create'], $rows[0]);
            if (false === $model) {
                throw new RuntimeException('Unable to create item for ' . $this->class);
            }
            return $model;
        }
        return null;
    }

    /**
     * @param array<string,mixed> $conditions
     * @param string[] $fields
     * @param array $options
     * @return Collection
     */
    public function findBy(array $conditions, ?array $fields = null, array $options = []): Collection
    {
        $fields ??= forward_static_call([$this->class, 'getAttributes']) ?? ['*'];
        if (false === $fields) {
            throw new RuntimeException('Unable to get attributes for ' . $this->class);
        }
        try {
            if ($rows = self::$db->select($this->table, $conditions, $fields, $options)) {
                return new Collection($this->class, $rows);
            }
        } catch (Exception $e) {
            self::$logger->error(get_class($e) . ' [' . $e->getCode() . ']' . $e->getMessage());
        }
        return new Collection($this->class);
    }

    public function findAll(array $options = []): Collection
    {
        return $this->findBy([], options: $options);
    }

    public function save(Model $object): Model
    {
        if ($this->class !== get_class($object)) {
            throw new InvalidArgumentException('Invalid model');
        }
        if ($object->id === null) {
            // Insert
            $row = self::$db->insert($this->table, $this->getModelData($object));
        } else {
            // Update
            $row = self::$db->update(
                $this->table,
                $this->getModelData($object),
                ['id' => $object->id]
            );
        }
        $model = forward_static_call([$this->class, 'create'], $row);
        if (false === $model) {
            throw new RuntimeException('Unable to create item for ' . $this->class);
        }
        return $model;
    }

    public function delete(Model|int $value): void
    {
        $id = is_a($value, Model::class) ? $value->id : $value;
        self::$db->delete($this->table, ['id' => $id]);
    }

    /**
     * Extracts public and private attributes from a model
     * @see http://ocramius.github.io/blog/accessing-private-php-class-members-without-reflection/
     * @return array<string,mixed>
     */
    protected function getModelData(Model $object): array
    {
        $getData = Closure::bind(function (): array {
            /** @psalm-suppress UndefinedMethod $this is in the Model scope */
            return $this->getData();
        }, $object, $object);
        return $getData ? $getData($object) : [];
    }
}
