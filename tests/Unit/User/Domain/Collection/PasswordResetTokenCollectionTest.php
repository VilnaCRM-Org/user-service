<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Collection;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Collection\PasswordResetTokenCollection;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use ArrayIterator;

final class PasswordResetTokenCollectionTest extends UnitTestCase
{
    public function testCount(): void
    {
        $token1 = $this->createMock(
            PasswordResetTokenInterface::class
        );
        $token2 = $this->createMock(
            PasswordResetTokenInterface::class
        );
        $collection = new PasswordResetTokenCollection(
            $token1,
            $token2
        );

        $this->assertCount(2, $collection);
    }

    public function testGetIterator(): void
    {
        $token = $this->createMock(
            PasswordResetTokenInterface::class
        );
        $collection = new PasswordResetTokenCollection($token);

        $iterator = $collection->getIterator();

        $this->assertInstanceOf(
            ArrayIterator::class,
            $iterator
        );
        $this->assertCount(1, $iterator);
    }

    public function testEmptyCollection(): void
    {
        $collection = new PasswordResetTokenCollection();

        $this->assertCount(0, $collection);
    }
}
