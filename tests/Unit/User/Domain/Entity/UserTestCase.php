<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Domain\Entity;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\UserConfirmedEventFactoryInterface;
use App\User\Domain\Factory\Event\UserUpdateEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

abstract class UserTestCase extends UnitTestCase
{
    protected UserInterface $user;
    protected UserConfirmedEventFactoryInterface $userConfirmedEventFactory;
    protected UserUpdateEventFactoryInterface $userUpdateEventFactory;
    protected UserFactoryInterface $userFactory;
    protected ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    protected UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userConfirmedEventFactory =
            $this->createMock(UserConfirmedEventFactoryInterface::class);
        $this->userUpdateEventFactory =
            $this->createMock(UserUpdateEventFactoryInterface::class);
        $this->userFactory = new UserFactory();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());

        $this->user = $this->userFactory->create(
            $this->faker->email(),
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }
}
