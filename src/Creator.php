<?php

declare(strict_types=1);

namespace NiceBase;

use InvalidArgumentException;

trait Creator
{
   /**
    * Creates an entity from the data provided
    * An entity can be created from
    *  - database reading or result
    *  - form data
    *  - manually programming and setting attributes
    * @return static new entity with the data provided and sensible defaults
    * @throws InvalidArgumentException
    */
    public static function create(array $data): static
    {
        return new static($data);
    }
}
