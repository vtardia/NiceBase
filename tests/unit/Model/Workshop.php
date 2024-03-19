<?php

declare(strict_types=1);

namespace NiceBase\Tests\Model;

use NiceBase\Model;

class Workshop extends Model
{
    /** @var string[] */
    protected array $attributes = ['title'];

    public readonly string $title;

    protected function build(array $row): void
    {
        /**
         * @psalm-suppress  InaccessibleProperty
         * Because build() is called within the constructor
         */
        $this->title = $row['title'];
    }

    protected function getData(): array
    {
        return array_merge(parent::getData(), [
            'title' => $this->title
        ]);
    }

    /**
     * @return string[]
     */
    public static function getAttributes(): array
    {
        return array_merge(parent::getAttributes(), ['title']);
    }
}
