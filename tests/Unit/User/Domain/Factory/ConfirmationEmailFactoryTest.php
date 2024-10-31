<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Factory;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Aggregate\ConfirmationEmailInterface;
use App\User\Domain\Factory\ConfirmationEmailFactory;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class ConfirmationEmailFactoryTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private UuidTransformer $uuidTransformer;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
    }

    public function testCreate(): void
    {
        $userId = $this->faker->uuid();
        $user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($userId)
        );
        $token = $this->confirmationTokenFactory->create($userId);

        $factory = new ConfirmationEmailFactory();

        $confirmationEmail = $factory->create($token, $user);

        $this->assertInstanceOf(
            ConfirmationEmailInterface::class,
            $confirmationEmail
        );
    }
}
