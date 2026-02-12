<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Post;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SetupTwoFactorCommand;
use App\User\Application\Command\SetupTwoFactorCommandResponse;
use App\User\Application\DTO\SetupTwoFactorDto;
use App\User\Application\Processor\SetupTwoFactorProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

final class SetupTwoFactorProcessorTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private Security&MockObject $security;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->security = $this->createMock(Security::class);
    }

    public function testProcessDispatchesSetupCommandForAuthenticatedUser(): void
    {
        $email = $this->faker->email();
        $securityUser = $this->createSecurityUser($email);

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($securityUser);

        $uri = 'otpauth://totp/VilnaCRM:test@example.com?secret=ABC123&issuer=VilnaCRM';
        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static function (SetupTwoFactorCommand $cmd) use ($email, $uri): bool {
                    $cmd->setResponse(
                        new SetupTwoFactorCommandResponse($uri, 'ABC123')
                    );

                    return $cmd->userEmail === $email;
                }
            ));

        $processor = $this->createProcessor();
        $response = $processor->process(new SetupTwoFactorDto(), new Post());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'otpauth_uri' => $uri,
                'secret' => 'ABC123',
            ], JSON_THROW_ON_ERROR),
            (string) $response->getContent()
        );
    }

    public function testProcessThrowsUnauthorizedWhenNoUserExists(): void
    {
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');

        $this->createProcessor()
            ->process(new SetupTwoFactorDto(), new Post());
    }

    public function testProcessThrowsUnauthorizedWhenIdentifierIsEmpty(): void
    {
        $securityUser = $this->createSecurityUser('');

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($securityUser);

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');

        $this->createProcessor()
            ->process(new SetupTwoFactorDto(), new Post());
    }

    private function createProcessor(): SetupTwoFactorProcessor
    {
        return new SetupTwoFactorProcessor(
            $this->commandBus,
            $this->security
        );
    }

    private function createSecurityUser(string $identifier): object
    {
        return new class($identifier) implements UserInterface {
            public function __construct(
                private readonly string $identifier,
            ) {
            }

            /**
             * @return string[]
             *
             * @psalm-return list{'ROLE_USER'}
             */
            #[\Override]
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            #[\Override]
            public function eraseCredentials(): void
            {
            }

            #[\Override]
            public function getUserIdentifier(): string
            {
                return $this->identifier;
            }
        };
    }
}
