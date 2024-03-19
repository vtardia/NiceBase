<?php

declare(strict_types=1);

namespace NiceBase;

use DateTimeZone;
use Exception;
use InvalidArgumentException;

abstract class Model implements Createable
{
    use Creator;

    public readonly ?int $id;

    /**
     * UTC Creation timestamp
     */
    protected readonly int $created;

    /**
     * UTC Last update timestamp
     */
    protected readonly int $updated;

    /**
     * List of mandatory constructor attributes in database snake_case
     *
     * Nothing prevents us to have optional attributes that will be
     * managed by the build() method of a specific model.
     *
     * @var string[]
     */
    protected array $attributes = [];

    abstract protected function build(array $row): void;

    final protected function __construct(array $params)
    {
        $this->id = $params['id'] ?? null;
        // Check mandatory fields
        foreach ($this->attributes as $attribute) {
            if (!array_key_exists($attribute, $params)) {
                throw new InvalidArgumentException(
                    "Missing required argument '$attribute' for " . static::class
                );
            }
        }
        // Update timestamps
        $this->created = DateTimeImmutable::timestampFrom($params['created_at'] ?? time());
        $this->updated = DateTimeImmutable::timestampFrom($params['updated_at'] ?? time());
        $this->build($params);
    }

    /**
     * @throws Exception
     */
    public function created(?DateTimeZone $timeZone = null): DateTimeImmutable
    {
        return DateTimeImmutable::dateFrom($this->created, $timeZone);
    }

    /**
     * @throws Exception
     */
    public function updated(?DateTimeZone $timeZone = null): DateTimeImmutable
    {
        return DateTimeImmutable::dateFrom($this->updated, $timeZone);
    }

    /**
     * Used by mappers to retrieve a model's properties
     * Must be overridden
     * @return array<string,mixed>
     */
    protected function getData(): array
    {
        $data = [
            'created_at' => date(DateTimeImmutable::SQL, $this->created),
            'updated_at' => date(DateTimeImmutable::SQL, $this->updated),
        ];
        if ($this->id !== null) {
            $data['id'] = $this->id;
        }
        return $data;
    }

    /**
     * @return string[]
     */
    public static function getAttributes(): array
    {
        return ['id', 'created_at', 'updated_at'];
    }
}
