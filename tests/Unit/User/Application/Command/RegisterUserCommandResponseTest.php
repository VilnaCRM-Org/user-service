<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Command;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserBatchCommandResponse;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;

final class RegisterUserCommandResponseTest extends UnitTestCase
{
    private const BATCH_SIZE = 2;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer(new UuidFactory());
    }

    public function testConstructor(): void
    {
        $users = [];
        for ($i = 0; $i < self::BATCH_SIZE; $i++) {
            $email = $this->faker->email();
            $initials = $this->faker->name();
            $password = $this->faker->password();

            $users[] = $this->userFactory->create(
                $email,
                $initials,
                $password,
                $this->transformer->transformFromString($this->faker->uuid())
            );
        }

        $response =
            new RegisterUserBatchCommandResponse(new UserCollection($users));

        $this->assertEquals(new UserCollection($users), $response->users);
    }
}
