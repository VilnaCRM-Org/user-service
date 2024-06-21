<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory;

use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Factory\ConfirmationTokenFactory;

final class ConfirmationTokenFactoryTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $tokenLength = $this->faker->numberBetween(1, 10);
        $factory = new ConfirmationTokenFactory($tokenLength);

        $userID = $this->faker->uuid();
        $confirmationToken = $factory->create($userID);

        $this->assertInstanceOf(
            ConfirmationTokenInterface::class,
            $confirmationToken
        );
    }
}
