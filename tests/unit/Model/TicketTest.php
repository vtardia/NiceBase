<?php

declare(strict_types=1);

namespace NiceBase\Tests\Model;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Simphle\Value\IPAddress;

/**
 * @psalm-suppress UnusedClass,PropertyNotSetInConstructor
 */
class TicketTest extends TestCase
{
    public function testCreateWithUser(): void
    {
        $user = User::create([
            'full_name' => 'John Osbourne',
            'email' => 'ozzy@blacksabbath.com',
            'phone' => '12341234',
            'password' => '666'
        ]);
        $ticket = Ticket::create(['user' => $user]);
        $this->assertInstanceOf(Ticket::class, $ticket);
        $this->assertNull($ticket->id);
        $this->assertEquals(IPAddress::LOCALHOST, $ticket->ipAddress);
        $this->assertEquals($user, $ticket->user);
    }

    public function testErrorOnMissingRequiredArguments(): void
    {
        $this->expectExceptionMessageMatches(
            "/^Missing required argument 'user' for/"
        );
        Ticket::create([]);
    }

    public function testErrorOnInvalidUserType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid user');
        Ticket::create(['user' => 'Foo']);
    }

    public function testGetAttributes(): void
    {
        $this->assertEquals(
            ['id', 'created_at', 'updated_at', 'ip_address', 'user_id', 'code'],
            Ticket::getAttributes()
        );
    }
}
