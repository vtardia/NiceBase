<?php

declare(strict_types=1);

namespace NiceBase\Tests\Model;

use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress UnusedClass,PropertyNotSetInConstructor
 */
class WorkshopTest extends TestCase
{
    public function testCreate(): void
    {
        $workshop = Workshop::create([
            'title' => 'Some Workshop',
        ]);
        $this->assertInstanceOf(Workshop::class, $workshop);
        $this->assertEquals('Some Workshop', $workshop->title);
        $this->assertNull($workshop->id);
    }

    public function testErrorOnMissingRequiredArguments(): void
    {
        $this->expectExceptionMessageMatches(
            "/^Missing required argument 'title' for/"
        );
        Workshop::create([]);
    }
}
