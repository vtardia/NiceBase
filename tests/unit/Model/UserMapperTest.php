<?php

declare(strict_types=1);

namespace NiceBase\Tests\Model;

use NiceBase\Tests\Exception\AuthenticationFailedException;
use NiceBase\Tests\TestCase;
use Simphle\Value\EmailAddress;
use Simphle\Value\PersonName;

/**
 * @psalm-suppress UnusedClass,PropertyNotSetInConstructor
 */
class UserMapperTest extends TestCase
{
    protected UserMapper $userMapper;

    public function setUp(): void
    {
        parent::setUp();
        $this->userMapper = new UserMapper();
    }

    public function testSave(): void
    {
        // Creation
        $user = User::create([
            'full_name' => 'John Osbourne',
            'email' => 'ozzy@blacksabbath.com',
            'phone' => '12341234',
        ]);
        $user->setPassword('666');
        $this->assertNull($user->id);

        /** @var User $newUser */
        $newUser = $this->userMapper->save($user);
        $this->assertNotNull($newUser->id);
        $this->assertEquals('ozzy@blacksabbath.com', $user->email->value);

        // Update
        $newUser->fullName = new PersonName('Ozzy Osbourne');
        /** @var User $updatedUser */
        $updatedUser = $this->userMapper->save($newUser);
        $this->assertEquals('Ozzy', $updatedUser->fullName->firstName);

        $this->assertInstanceOf(
            User::class,
            $this->userMapper->authenticate($newUser->email, '666')
        );

        $this->expectException(AuthenticationFailedException::class);
        $this->userMapper->authenticate($newUser->email, 'foobar');
    }

    public function testFind(): void
    {
        /** @var ?User $paul */
        $paul = $this->userMapper->find(2);
        $this->assertNotNull($paul);
        $this->assertEquals('paul@thebeatles.com', $paul->email->value);
    }

    public function testFindOrCreate(): void
    {
        $tony = $this->userMapper->findByEmail('tony@blacksabbath.com');
        $this->assertNull($tony);
        $tony = User::create([
            'full_name' => 'Toni Iommi',
            'email' => 'tony@blacksabbath.com',
            'phone' => '12341234',
        ]);
        $tony->setPassword('666');
        $tony = $this->userMapper->findOrCreate($tony);
        $this->assertNotNull($tony->id);
        $id = $tony->id;
        $tony = $this->userMapper->findOrCreate($tony);
        $this->assertEquals($id, $tony->id);
    }

    public function testDelete(): void
    {
        $paul = $this->userMapper->find(2);
        $this->assertNotNull($paul);

        // Delete by ID and check that it has been deleted
        /** @psalm-suppress PossiblyNullArgument */
        $this->userMapper->delete($paul->id);
        $this->assertNull($this->userMapper->find($paul->id));

        // Delete by object
        $ringo = $this->userMapper->find(4);
        $this->assertNotNull($ringo);
        /** @psalm-suppress PossiblyNullArgument */
        $this->userMapper->delete($ringo);
        /** @psalm-suppress PossiblyNullArgument */
        $this->assertNull($this->userMapper->find($ringo->id));

        // Test idempotence
        $this->userMapper->delete($ringo);
    }

    public function testFindAll(): void
    {
        $users = $this->userMapper->findAll();
        $this->assertCount(4, $users);
        $users = $this->userMapper->findAll(['limit' => 2, 'offset' => 2]);
        $this->assertCount(2, $users);
        $this->assertEquals(3, $users[0]->id);
    }

    public function testFindByEmail(): void
    {
        $george = $this->userMapper->findByEmail('george@thebeatles.com');
        $this->assertNotNull($george);

        $ozzy = $this->userMapper->findByEmail(new EmailAddress('john@thebeatles.com'));
        $this->assertNotNull($ozzy);

        $paul = $this->userMapper->findByEmail('ozzy@blacksabbath.com');
        $this->assertNull($paul);
    }

    public function testFindBy(): void
    {
        $this->assertCount(1, $this->userMapper->findBy(['full_name' => 'John Lennon']));
        $this->assertEmpty($this->userMapper->findBy(['full_name' => 'Ozzy Osbourne']));
    }

    public function testFindPerWorkshop(): void
    {
        $this->assertCount(1, $this->userMapper->findPerWorkshop(1));
        $this->assertCount(1, $this->userMapper->findPerWorkshop(2));
        $this->assertCount(2, $this->userMapper->findPerWorkshop(3));
    }
}
