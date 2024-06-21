<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Collection;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\User;

final class UserCollectionTest extends UnitTestCase
{
    public function testAddUser(): void
    {
        $user = $this->createMock(User::class);
        $collection = new UserCollection();

        $collection->add($user);

        $this->assertCount(1, $collection);
        $this->assertSame($user, $collection[0]);
    }

    public function testRemoveUser(): void
    {
        $user = $this->createMock(User::class);
        $collection = new UserCollection([$user]);

        $collection->remove($user);

        $this->assertCount(0, $collection);
    }

    public function testGetIterator(): void
    {
        $user1 = $this->createMock(User::class);
        $user2 = $this->createMock(User::class);
        $collection = new UserCollection([$user1, $user2]);

        $iterator = $collection->getIterator();

        $this->assertInstanceOf(\ArrayIterator::class, $iterator);
        $this->assertCount(2, $iterator);
    }

    public function testCount(): void
    {
        $user1 = $this->createMock(User::class);
        $user2 = $this->createMock(User::class);
        $collection = new UserCollection([$user1, $user2]);

        $this->assertCount(2, $collection);
    }

    public function testOffsetExists(): void
    {
        $user = $this->createMock(User::class);
        $collection = new UserCollection([$user]);

        $this->assertTrue(isset($collection[0]));
        $this->assertFalse(isset($collection[1]));
    }

    public function testOffsetGet(): void
    {
        $user = $this->createMock(User::class);
        $collection = new UserCollection([$user]);

        $this->assertSame($user, $collection[0]);
        $this->assertNull($collection[1]);
    }

    public function testOffsetSet(): void
    {
        $user1 = $this->createMock(User::class);
        $user2 = $this->createMock(User::class);
        $user3 = $this->createMock(User::class);
        $collection = new UserCollection([$user1]);

        $collection[1] = $user2;
        $this->assertCount(2, $collection);
        $this->assertSame($user2, $collection[1]);

        $collection[] = $user3;
        $this->assertCount(3, $collection);
        $this->assertSame($user3, $collection[2]);

        $collection[1] = $user3;
        $this->assertCount(3, $collection);
        $this->assertSame($user3, $collection[1]);
    }

    public function testOffsetUnset(): void
    {
        $user1 = $this->createMock(User::class);
        $user2 = $this->createMock(User::class);
        $collection = new UserCollection([$user1, $user2]);

        unset($collection[0]);

        $this->assertCount(1, $collection);
        $this->assertSame($user2, $collection[1]);
    }
}
