<?php

declare(strict_types=1);

namespace NiceBase\Tests\Model;

use NiceBase\Tests\TestCase;

/**
 * @psalm-suppress UnusedClass,PropertyNotSetInConstructor
 */
class WorkshopMapperTest extends TestCase
{
    protected WorkshopMapper $workshopMapper;

    public function setUp(): void
    {
        parent::setUp();
        $this->workshopMapper = new WorkshopMapper();
    }

    public function testSave(): void
    {
        // Creation
        $four = Workshop::create(['title' => 'Workshop Four']);
        $this->assertNull($four->id);
        $four = $this->workshopMapper->save($four);
        $this->assertNotNull($four->id);

        // Update: workshops are readonly, so in order to update
        // we need to create a new one with an existing ID and override
        $newFour = Workshop::create([
            'id' => $four->id,
            'title' => 'Updated Workshop Four'
        ]);
        /** @var Workshop $newFour */
        $newFour = $this->workshopMapper->save($newFour);
        $this->assertEquals($four->id, $newFour->id);
        $this->assertEquals('Updated Workshop Four', $newFour->title);
    }

    public function testFind(): void
    {
        /** @var ?Workshop $workshop */
        $workshop = $this->workshopMapper->find(2);
        $this->assertNotNull($workshop);
        $this->assertEquals('Workshop Two', $workshop->title);
    }

    public function testDelete(): void
    {
        $this->assertNotNull($this->workshopMapper->find(1));
        $this->workshopMapper->delete(1);
        $this->assertNull($this->workshopMapper->find(1));
    }

    public function testFindAll(): void
    {
        $this->assertCount(3, $this->workshopMapper->findAll());
    }

    public function testFindBy(): void
    {
        $this->assertCount(1, $this->workshopMapper->findBy(['title' => 'Workshop One']));
        $this->assertEmpty($this->workshopMapper->findBy(['title' => 'Workshop Five']));
    }

    public function testFindPerUser(): void
    {
        $this->assertEmpty($this->workshopMapper->findPerUser(4));
        $this->assertCount(2, $this->workshopMapper->findPerUser(2));
    }
}
