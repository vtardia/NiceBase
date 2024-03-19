<?php

/** @noinspection PhpUndefinedFieldInspection */

declare(strict_types=1);

namespace NiceBase\Tests;

use TypeError;
use PHPUnit\Framework\TestCase;
use stdClass;
use NiceBase\Collection;

/**
 * @psalm-suppress UnusedClass,PropertyNotSetInConstructor
 */
class CollectionTest extends TestCase
{
    private array $ringo = [
        'id' => 2,
        'full_name' => 'Ringo Starr',
        'email' => 'ringo@thebeatles.com',
        'phone' => '0980980970897'
    ];
    private array $john = [
        'id' => 1,
        'full_name' => 'John Lennon',
        'email' => 'john@thebeatles.com',
        'phone' => '0980980970000'
    ];

    public function testEmptyCollection(): void
    {
        // Create a new empty collection
        $c = new Collection(Model\User::class);
        $this->assertInstanceOf(Collection::class, $c);
        $this->assertCount(0, $c);

        // Add data...
        $c[] = Model\User::create($this->ringo);
        $this->assertCount(1, $c);
    }

    public function testCustomItemType(): void
    {
        $c = new Collection(Model\User::class, [
            $this->john,
            Model\User::create($this->ringo)
        ]);
        $this->assertInstanceOf(Collection::class, $c);
        $this->assertCount(2, $c);
        $this->assertInstanceOf(Model\User::class, $c[0]);
    }

    public function testErrorOnInvalidInputData(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Invalid item data: Array or 'NiceBase\Tests\Model\User' expected");
        new Collection(Model\User::class, ['foo', 'bar']);
    }

    public function testErrorOnInvalidArrayFormat(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Invalid item data: Array or 'NiceBase\Tests\Model\User' expected");
        new Collection(Model\User::class, ['id' => 1, 'name' => 'John']);
    }

    public function testErrorOnMixedObjectTypes(): void
    {
        // It should throw when the data array is mixed classes
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Invalid item data: Array or 'NiceBase\Tests\Model\User' expected");
        new Collection(Model\User::class, [
            Model\User::create($this->ringo),
            new stdClass()
        ]);
    }

    public function testErrorWhenAppendingDifferentObjectType(): void
    {
        // It should throw when adding item of different class
        $c = new Collection(Model\User::class, [
            Model\User::create($this->ringo)
        ]);
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Invalid item data: Array or 'NiceBase\Tests\Model\User' expected");
        $c[] = new stdClass();
    }

    public function testErrorOnInvalidItemClass(): void
    {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage("Invalid item type: 'stdClass' doesn't implement Creatable");
        new Collection(stdClass::class, [
            new stdClass()
        ]);
    }

    public function testFilter(): void
    {
        $c = new Collection(Model\User::class, [
            $this->john,
            Model\User::create($this->ringo)
        ]);
        $f = $c->filter(function (Model\User $item) {
            return $item->id > 1;
        });
        $this->assertCount(1, $f);
        $this->assertEquals(2, $f[0]->id);
        $this->assertEquals(Model\User::class, $c->getType());
    }
}
