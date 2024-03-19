<?php

declare(strict_types=1);

namespace NiceBase;

interface Createable
{
    public static function create(array $data): static;
}
