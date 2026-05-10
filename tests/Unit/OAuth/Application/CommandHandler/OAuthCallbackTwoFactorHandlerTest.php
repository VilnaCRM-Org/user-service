<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\CommandHandler;

use App\OAuth\Application\Command\HandleOAuthCallbackCommand;
use App\OAuth\Application\CommandHandler\OAuthCallbackTwoFactorHandler;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\PendingTwoFactorFactory;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\PendingTwoFactorRepositoryInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;

final class OAuthCallbackTwoFactorHandlerTest extends UnitTestCase
{
    private PendingTwoFactorRepositoryInterface&MockObject $pendingTwoFactorRepo;
    private IdFactoryInterface&MockObject $idFactory;
    private OAuthCallbackTwoFactorHandler $handler;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->pendingTwoFactorRepo = $this->createMock(
            PendingTwoFactorRepositoryInterface::class
        );
        $this->idFactory = $this->createMock(IdFactoryInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());

        $this->idFactory->method('create')
            ->willReturn($this->faker->uuid());

        $this->handler = new OAuthCallbackTwoFactorHandler(
            $this->pendingTwoFactorRepo,
            new PendingTwoFactorFactory(),
            $this->idFactory,
        );
    }

    public function testHandleSavesPendingAndSetsTwoFactorResponse(): void
    {
        $user = $this->createUser();

        $this->pendingTwoFactorRepo->expects($this->once())
            ->method('save');

        $command = new HandleOAuthCallbackCommand(
            $this->faker->word(),
            $this->faker->sha256(),
            $this->faker->sha256(),
            $this->faker->sha256(),
            $this->faker->ipv4(),
            $this->faker->userAgent(),
        );

        $this->handler->handle($user, $command);

        $response = $command->getResponse();
        $this->assertTrue($response->isTwoFactorEnabled());
        $this->assertNull($response->getAccessToken());
        $this->assertNotEmpty($response->getPendingSessionId());
    }

    public function testConstructorRejectsZeroTtl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OAuthCallbackTwoFactorHandler(
            $this->pendingTwoFactorRepo,
            new PendingTwoFactorFactory(),
            $this->idFactory,
            0,
        );
    }

    public function testConstructorRejectsNegativeTtl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new OAuthCallbackTwoFactorHandler(
            $this->pendingTwoFactorRepo,
            new PendingTwoFactorFactory(),
            $this->idFactory,
            -1,
        );
    }

    private function createUser(): User
    {
        return $this->userFactory->create(
            $this->faker->safeEmail(),
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid()),
        );
    }
}
