<?php

declare(strict_types=1);

namespace NiceBase\Tests\Model;

use NiceBase\Tests\TestCase;

/**
 * @psalm-suppress UnusedClass,PropertyNotSetInConstructor
 */
class TicketMapperTest extends TestCase
{
    protected TicketMapper $ticketMapper;
    protected UserMapper $userMapper;
    protected WorkshopMapper $workshopMapper;

    public function setUp(): void
    {
        parent::setUp();
        $this->ticketMapper = new TicketMapper();
        $this->userMapper = new UserMapper();
        $this->workshopMapper = new WorkshopMapper();
    }

    public function testFind(): void
    {
        $ticket = $this->ticketMapper->find(1);
        $this->assertNotNull($ticket);
        $this->assertEquals('192.168.1.2', $ticket->ipAddress->value);
        $this->assertEquals('ABC123456', $ticket->code);
        $this->assertInstanceOf(User::class, $ticket->user);
        $this->assertEquals('paul@thebeatles.com', $ticket->user->email->value);
        $this->assertCount(2, $ticket->workshops);
        $this->assertEquals('Workshop Two', $ticket->workshops[0]->title);
        $this->assertEquals('Workshop Three', $ticket->workshops[1]->title);

        $this->assertNull($this->ticketMapper->find(666));
        $this->assertNull($this->ticketMapper->findByCode('YYZ'));
    }

    public function testSaveCreate(): void
    {
        // Create ticket for existing user
        $user = $this->userMapper->findByEmail('george@thebeatles.com');
        $this->assertNotNull($user);
        $workshops = $this->workshopMapper->findAll();
        $this->assertNotEmpty($workshops);
        $ticket = Ticket::create(['user' => $user, 'workshops' => $workshops]);
        $this->assertNotEmpty($ticket->workshops);
        $this->assertNull($ticket->id);
        $ticket = $this->ticketMapper->save($ticket);
        $this->assertNotNull($ticket->id);

        // Create ticket for a new user
        $user = User::create([
            'full_name' => 'Fox Mulder',
            'email' => 'spooky@gmail.com',
            'phone' => '55512346',
        ]);
        $user->setPassword('Dana');
        $ticket = Ticket::create(['user' => $user, 'workshops' => $workshops]);
        $this->assertNotEmpty($ticket->workshops);
        $this->assertNull($ticket->id);
        $ticket = $this->ticketMapper->save($ticket);
        $this->assertNotNull($ticket->id);
    }

    public function testSaveUpdate(): void
    {
        $ticket = $this->ticketMapper->findByCode('ABC654321');
        $this->assertNotNull($ticket);
        $this->assertCount(2, $ticket->workshops);
        $this->assertEquals('Workshop One', $ticket->workshops[0]->title);
        $this->assertEquals('Workshop Three', $ticket->workshops[1]->title);
        $this->assertEquals('john@thebeatles.com', $ticket->user->email->value);

        // Update a ticket with a different existing user
        $george = $this->userMapper->findByEmail('george@thebeatles.com');
        $this->assertNotNull($george);
        $ticket->user = $george;
        $ticket = $this->ticketMapper->save($ticket);
        $this->assertEquals($george->email->value, $ticket->user->email->value);

        // Update a ticket for a new user
        $fox = User::create([
            'full_name' => 'Fox Mulder',
            'email' => 'spooky@gmail.com',
            'phone' => '55512346',
        ]);
        $fox->setPassword('Dana');
        $ticket->user = $fox;
        $ticket = $this->ticketMapper->save($ticket);
        $this->assertEquals($fox->email->value, $ticket->user->email->value);

        // Design decision: workshops are readonly, so it's not possible
        // to change the workshop list for a ticket
    }

    public function testFindBy(): void
    {
        $this->assertCount(1, $this->ticketMapper->findBy(['user_full_name' => 'John Lennon']));

        $this->assertEmpty($this->ticketMapper->findBy(['user_full_name' => 'Ozzy Osbourne']));
    }

    public function testFindAll(): void
    {
        $this->assertCount(2, $this->ticketMapper->findAll());
    }
}
