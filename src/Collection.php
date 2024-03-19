<?php

declare(strict_types=1);

namespace NiceBase;

use ArrayIterator;
use ArrayObject;
use TypeError;

/**
 * @template T
 * @extends ArrayObject<int, T>
 */
class Collection extends ArrayObject
{
    protected string $itemType;

    public function __construct(string $type, array $data = [])
    {
        /** @psalm-suppress PossiblyFalseArgument Let it throw on error */
        if (!in_array(Createable::class, class_implements($type))) {
            throw new TypeError("Invalid item type: '$type' doesn't implement Creatable");
        }
        parent::__construct([], 0, ArrayIterator::class);
        $this->itemType = $type;
        foreach ($data as $item) {
            $this->append($item);
        }
    }

    /**
     * Checks that the item has the requirements to be appended
     * @return T
     */
    private function validate(mixed $item): object
    {
        if (is_array($item)) {
            return call_user_func([$this->itemType, 'create'], $item);
        }
        if ($item instanceof $this->itemType) {
            return $item;
        }
        throw new TypeError(
            "Invalid item data: Array or '$this->itemType' expected"
        );
    }

    public function append(mixed $value): void
    {
        parent::append($this->validate($value));
    }

    /**
     * Deals with $arr[] = something
     * @psalm-suppress ParamNameMismatch
     */
    public function offsetSet(mixed $key, mixed $value): void
    {
        parent::offsetSet($key, $this->validate($value));
    }

    /**
     * Filters elements of an array using a callback function
     */
    public function filter(callable $callback): Collection
    {
        return new self($this->itemType, array_filter($this->getArrayCopy(), $callback));
    }

    public function getType(): string
    {
        return $this->itemType;
    }
}
