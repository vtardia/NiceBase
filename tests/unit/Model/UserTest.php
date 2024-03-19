<?php

declare(strict_types=1);

namespace NiceBase\Tests\Model;

use DateTimeZone;
use Exception;
use NiceBase\DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * @psalm-suppress UnusedClass,PropertyNotSetInConstructor
 */
class UserTest extends TestCase
{
    /**
     * Tests both manual and form creation, which are pretty much the same
     */
    public function testCreateFromFormData(): void
    {
        $user = User::create([
            'full_name' => 'John Osbourne',
            'email' => 'ozzy@blacksabbath.com',
            'phone' => '12341234',
            'password' => '666'
        ]);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('ozzy@blacksabbath.com', $user->email->value);
        $this->assertEquals('ozzy@blacksabbath.com', $user->email);
        $this->assertEquals('John Osbourne', $user->fullName->value);
        $this->assertEquals('John Osbourne', $user->fullName);
        $this->assertEquals('John', $user->fullName->firstName);
        $this->assertEquals('Osbourne', $user->fullName->lastName);
        $this->assertNull($user->id);
    }

    /**
     * Tests creation from a database row, which includes ID and timestamp fields
     * @throws Exception
     */
    public function testCreateFromDatabase(): void
    {
        $user = User::create([
            'id' => 666,
            'full_name' => 'John Osbourne',
            'email' => 'ozzy@blacksabbath.com',
            'phone' => '12341234',
            'password' => '666',
            'created_at' => '2010-10-31 23:59:00',
            'updated_at' => '2010-11-03 15:20:00',
        ]);
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(666, $user->id);
        $this->assertEquals(
            '2010-10-31 23:59:00',
            $user->created()->format(DateTimeImmutable::SQL)
        );
        $this->assertEquals(
            '2010-11-01 00:59:00',
            $user->created(new DateTimeZone('Europe/Rome'))->format(DateTimeImmutable::SQL)
        );
        $this->assertEquals(
            '2010-11-03 15:20:00',
            $user->updated()->format(DateTimeImmutable::SQL)
        );
        $this->assertEquals(
            '2010-11-03 16:20:00',
            $user->updated(new DateTimeZone('Europe/Rome'))->format(DateTimeImmutable::SQL)
        );
    }

    public function testErrorOnMissingRequiredArguments(): void
    {
        $this->expectExceptionMessageMatches(
            "/^Missing required argument 'phone' for/"
        );
        User::create([
            'full_name' => 'John Osbourne',
            'email' => 'ozzy@blacksabbath.com',
            'password' => '666'
        ]);
    }
}
