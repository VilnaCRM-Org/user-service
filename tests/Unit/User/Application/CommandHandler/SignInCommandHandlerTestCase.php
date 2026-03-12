<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\Tests\Unit\User\Application\CommandHandler\Fixture\RecordingPendingTwoFactorRepository;
use App\User\Application\Command\SignInCommand;
use App\User\Application\CommandHandler\SignInCommandHandler;
use App\User\Application\DTO\IssuedSession;
use App\User\Application\Factory\Generator\IdGeneratorInterface;
use App\User\Application\Processor\Authenticator\UserAuthenticatorInterface;
use App\User\Application\Processor\EventPublisher\SignInEventsInterface;
use App\User\Application\Processor\Issuer\SessionIssuerInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\PendingTwoFactorFactory;
use App\User\Domain\Factory\UserFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Uid\Ulid;

abstract class SignInCommandHandlerTestCase extends UnitTestCase
{
    protected UserAuthenticatorInterface&MockObject $userAuthenticator;
    protected SessionIssuerInterface&MockObject $sessionIssuer;
    protected SignInEventsInterface&MockObject $signInEvents;
    protected RecordingPendingTwoFactorRepository $pendingTwoFactorRepository;
    protected PendingTwoFactorFactory $pendingTwoFactorFactory;
    protected IdGeneratorInterface&MockObject $idGenerator;
    protected UserFactory $userFactory;
    protected UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userAuthenticator = $this->createMock(UserAuthenticatorInterface::class);
        $this->sessionIssuer = $this->createMock(SessionIssuerInterface::class);
        $this->signInEvents = $this->createMock(SignInEventsInterface::class);
        $this->pendingTwoFactorRepository = new RecordingPendingTwoFactorRepository();
        $this->pendingTwoFactorFactory = new PendingTwoFactorFactory();
        $this->idGenerator = $this->createMock(IdGeneratorInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());

        $this->configureDefaultSessionIssuer();
    }

    protected function configureDefaultSessionIssuer(): void
    {
        $this->sessionIssuer->method('issue')
            ->willReturn(new IssuedSession(
                (string) new Ulid(),
                'issued-access-token',
                'issued-refresh-token'
            ));
    }

    protected function createHandler(): SignInCommandHandler
    {
        return new SignInCommandHandler(
            $this->userAuthenticator,
            $this->sessionIssuer,
            $this->signInEvents,
            $this->pendingTwoFactorRepository,
            $this->pendingTwoFactorFactory,
            $this->idGenerator,
        );
    }

    /**
     * @return array{User, string, string, string, string}
     */
    protected function arrangeCredentials(): array
    {
        $email = strtolower($this->faker->email());
        $plainPassword = $this->faker->password();
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();

        return [
            $this->createUser($email),
            $email,
            $plainPassword,
            $ipAddress,
            $userAgent,
        ];
    }

    protected function createRandomSignInCommand(): SignInCommand
    {
        return new SignInCommand(
            $this->faker->email(),
            $this->faker->password(),
            false,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );
    }

    protected function createUser(string $email): User
    {
        return $this->userFactory->create(
            $email,
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }
}
