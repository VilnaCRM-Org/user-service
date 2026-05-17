<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Resolver;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompletePasskeyRegistrationCommand;
use App\User\Application\Command\CompletePasskeySignInCommand;
use App\User\Application\Command\CompletePasskeySignUpCommand;
use App\User\Application\Command\StartPasskeyRegistrationCommand;
use App\User\Application\Command\StartPasskeySignInCommand;
use App\User\Application\Command\StartPasskeySignUpCommand;
use App\User\Application\DTO\AuthPayload;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Application\DTO\PasskeyRegistrationCompleteDto;
use App\User\Application\DTO\PasskeySignInCompleteDto;
use App\User\Application\DTO\PasskeySignInOptionsDto;
use App\User\Application\DTO\PasskeySignUpCompleteDto;
use App\User\Application\DTO\PasskeySignUpOptionsDto;
use App\User\Application\Factory\AuthPayloadFactory;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use App\User\Application\Resolver\PasskeyRegistrationCompleteAuthMutationResolver;
use App\User\Application\Resolver\PasskeyRegistrationOptionsAuthMutationResolver;
use App\User\Application\Resolver\PasskeySignInCompleteAuthMutationResolver;
use App\User\Application\Resolver\PasskeySignInOptionsAuthMutationResolver;
use App\User\Application\Resolver\PasskeySignUpCompleteAuthMutationResolver;
use App\User\Application\Resolver\PasskeySignUpOptionsAuthMutationResolver;
use App\User\Application\Validator\MutationInputValidator;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class PasskeyAuthMutationResolverTest extends UnitTestCase
{
    private MutationInputValidator $validator;
    private CommandBusInterface $commandBus;
    private HttpRequestContextResolverInterface $requestContextResolver;
    private AuthPayloadFactory $payloadFactory;
    private Request $request;
    private string $email;
    private string $challengeId;
    /** @var array<string, string> */
    private array $credential;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = $this->createMock(MutationInputValidator::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->requestContextResolver = $this->createMock(
            HttpRequestContextResolverInterface::class
        );
        $this->payloadFactory = new AuthPayloadFactory();
        $this->request = Request::create('/api/graphql');
        $this->email = $this->faker->safeEmail();
        $this->challengeId = $this->faker->uuid();
        $this->credential = ['id' => $this->faker->sha256()];
    }

    public function testPasskeySignUpOptionsDispatchesCommand(): void
    {
        $this->expectValidation(PasskeySignUpOptionsDto::class);
        $this->expectDispatch(
            StartPasskeySignUpCommand::class,
            $this->createOptionsResult(),
            function (StartPasskeySignUpCommand $command): void {
                self::assertSame($this->email, $command->email);
                self::assertSame('AB', $command->initials);
                self::assertSame('Passkey User', $command->displayName);
            }
        );

        $payload = (new PasskeySignUpOptionsAuthMutationResolver(
            $this->validator,
            $this->commandBus,
            $this->payloadFactory
        ))->__invoke(null, [
            'args' => [
                'input' => [
                    'email' => $this->email,
                    'initials' => 'AB',
                    'displayName' => 'Passkey User',
                ],
            ],
        ]);

        $this->assertOptionsPayload($payload, 'auth-passkey-signup-options');
    }

    public function testPasskeySignUpCompleteDispatchesCommand(): void
    {
        $this->expectValidation(PasskeySignUpCompleteDto::class);
        $this->expectRequestContext();
        $this->expectDispatch(
            CompletePasskeySignUpCommand::class,
            $this->createAuthenticationResult(),
            function (CompletePasskeySignUpCommand $command): void {
                $this->assertCompleteCommand($command);
                self::assertSame('MacBook', $command->label);
                self::assertTrue($command->rememberMe);
            }
        );

        $payload = (new PasskeySignUpCompleteAuthMutationResolver(
            $this->validator,
            $this->commandBus,
            $this->payloadFactory,
            $this->requestContextResolver
        ))->__invoke(null, $this->completeContext(['label' => 'MacBook', 'rememberMe' => true]));

        $this->assertTokenPayload($payload, 'auth-passkey-signup-complete');
    }

    public function testPasskeySignInOptionsDispatchesCommand(): void
    {
        $this->expectValidation(PasskeySignInOptionsDto::class);
        $this->expectDispatch(
            StartPasskeySignInCommand::class,
            $this->createOptionsResult(),
            function (StartPasskeySignInCommand $command): void {
                self::assertSame($this->email, $command->email);
                self::assertTrue($command->rememberMe);
            }
        );

        $payload = (new PasskeySignInOptionsAuthMutationResolver(
            $this->validator,
            $this->commandBus,
            $this->payloadFactory
        ))->__invoke(null, [
            'args' => [
                'input' => ['email' => $this->email, 'rememberMe' => true],
            ],
        ]);

        $this->assertOptionsPayload($payload, 'auth-passkey-signin-options');
    }

    public function testPasskeySignInCompleteCanReturnPendingTwoFactor(): void
    {
        $this->expectValidation(PasskeySignInCompleteDto::class);
        $this->expectRequestContext();
        $this->expectDispatch(
            CompletePasskeySignInCommand::class,
            new PasskeyAuthenticationResult('', '', true, '', $this->challengeId),
            function (CompletePasskeySignInCommand $command): void {
                $this->assertCompleteCommand($command);
            }
        );

        $payload = (new PasskeySignInCompleteAuthMutationResolver(
            $this->validator,
            $this->commandBus,
            $this->payloadFactory,
            $this->requestContextResolver
        ))->__invoke(null, $this->completeContext());

        self::assertInstanceOf(AuthPayload::class, $payload);
        self::assertSame('auth-passkey-signin-complete', $payload->getId());
        self::assertTrue($payload->isTwoFactorEnabled());
        self::assertSame($this->challengeId, $payload->getPendingSessionId());
        self::assertNull($payload->getAccessToken());
    }

    public function testPasskeyRegistrationOptionsUsesCurrentIdentity(): void
    {
        $this->expectDispatch(
            StartPasskeyRegistrationCommand::class,
            $this->createOptionsResult(),
            function (StartPasskeyRegistrationCommand $command): void {
                self::assertSame('user-id', $command->userId);
                self::assertSame($this->email, $command->email);
            }
        );

        $payload = (new PasskeyRegistrationOptionsAuthMutationResolver(
            $this->commandBus,
            $this->payloadFactory,
            $this->identityResolver()
        ))->__invoke(null, []);

        $this->assertOptionsPayload($payload, 'auth-passkey-registration-options');
    }

    public function testPasskeyRegistrationCompleteReturnsCredentialId(): void
    {
        $this->expectValidation(PasskeyRegistrationCompleteDto::class);
        $credential = $this->createCredential();
        $this->expectDispatch(
            CompletePasskeyRegistrationCommand::class,
            $credential,
            function (CompletePasskeyRegistrationCommand $command): void {
                self::assertSame($this->challengeId, $command->challengeId);
                self::assertSame($this->credential, $command->credential);
                self::assertSame('Security key', $command->label);
                self::assertSame('user-id', $command->currentUserId);
            }
        );

        $payload = (new PasskeyRegistrationCompleteAuthMutationResolver(
            $this->validator,
            $this->commandBus,
            $this->payloadFactory,
            $this->identityResolver()
        ))->__invoke(null, $this->completeContext(['label' => 'Security key']));

        self::assertInstanceOf(AuthPayload::class, $payload);
        self::assertSame('auth-passkey-registration-complete', $payload->getId());
        self::assertSame($credential->getCredentialId(), $payload->getCredentialId());
    }

    /**
     * @param class-string $expectedDto
     */
    private function expectValidation(string $expectedDto): void
    {
        $this->validator->expects($this->once())
            ->method('validate')
            ->with(self::isInstanceOf($expectedDto));
    }

    /**
     * @param class-string<CommandInterface> $commandClass
     */
    private function expectDispatch(
        string $commandClass,
        object $response,
        callable $assertCommand
    ): void {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(self::callback(function (CommandInterface $command) use (
                $commandClass,
                $response,
                $assertCommand
            ): bool {
                self::assertInstanceOf($commandClass, $command);
                $assertCommand($command);
                $this->setCommandResponse($command, $response);

                return true;
            }));
    }

    private function setCommandResponse(CommandInterface $command, object $response): void
    {
        if ($command instanceof StartPasskeySignUpCommand
            || $command instanceof StartPasskeySignInCommand
            || $command instanceof StartPasskeyRegistrationCommand
        ) {
            self::assertInstanceOf(PasskeyOptionsResult::class, $response);
            $command->setResponse($response);

            return;
        }

        if ($command instanceof CompletePasskeySignUpCommand
            || $command instanceof CompletePasskeySignInCommand
        ) {
            self::assertInstanceOf(PasskeyAuthenticationResult::class, $response);
            $command->setResponse($response);

            return;
        }

        self::assertInstanceOf(CompletePasskeyRegistrationCommand::class, $command);
        self::assertInstanceOf(PasskeyCredential::class, $response);
        $command->setResponse($response);
    }

    private function expectRequestContext(): void
    {
        $this->requestContextResolver->expects($this->once())
            ->method('resolveRequest')
            ->with($this->request)
            ->willReturn($this->request);
        $this->requestContextResolver->expects($this->once())
            ->method('resolveIpAddress')
            ->with($this->request)
            ->willReturn('127.0.0.1');
        $this->requestContextResolver->expects($this->once())
            ->method('resolveUserAgent')
            ->with($this->request)
            ->willReturn('GraphQL Passkey Test');
    }

    private function assertCompleteCommand(
        CompletePasskeySignUpCommand|CompletePasskeySignInCommand $command
    ): void {
        self::assertSame($this->challengeId, $command->challengeId);
        self::assertSame($this->credential, $command->credential);
        self::assertSame('127.0.0.1', $command->ipAddress);
        self::assertSame('GraphQL Passkey Test', $command->userAgent);
    }

    /**
     * @param array<string, bool|string> $extraInput
     *
     * @return array<string, mixed>
     */
    private function completeContext(array $extraInput = []): array
    {
        return [
            'args' => [
                'input' => array_merge([
                    'challengeId' => $this->challengeId,
                    'credential' => $this->credential,
                ], $extraInput),
            ],
            'request' => $this->request,
        ];
    }

    private function assertOptionsPayload(object $payload, string $id): void
    {
        self::assertInstanceOf(AuthPayload::class, $payload);
        self::assertSame($id, $payload->getId());
        self::assertSame($this->challengeId, $payload->getChallengeId());
        self::assertSame(['rpId' => 'example.com'], $payload->getPublicKey());
    }

    private function assertTokenPayload(object $payload, string $id): void
    {
        self::assertInstanceOf(AuthPayload::class, $payload);
        self::assertSame($id, $payload->getId());
        self::assertFalse($payload->isTwoFactorEnabled());
        self::assertSame('access-token', $payload->getAccessToken());
        self::assertSame('refresh-token', $payload->getRefreshToken());
    }

    private function createOptionsResult(): PasskeyOptionsResult
    {
        $now = new DateTimeImmutable();

        return new PasskeyOptionsResult(
            new PasskeyChallenge(
                $this->challengeId,
                PasskeyChallenge::PURPOSE_AUTHENTICATION,
                'challenge',
                '{}',
                $now,
                $now->modify('+5 minutes'),
                new PasskeyChallengeContext($this->email, userId: 'user-id')
            ),
            ['rpId' => 'example.com']
        );
    }

    private function createAuthenticationResult(): PasskeyAuthenticationResult
    {
        return new PasskeyAuthenticationResult(
            'access-token',
            'refresh-token',
            true,
            'session-id'
        );
    }

    private function createCredential(): PasskeyCredential
    {
        return new PasskeyCredential(
            'passkey-id',
            'user-id',
            'credential-id',
            '{}',
            'Security key',
            new DateTimeImmutable()
        );
    }

    private function identityResolver(): \App\User\Application\Resolver\CurrentUserIdentityResolver
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')
            ->willReturn(new \App\User\Application\DTO\AuthorizationUserDto(
                $this->email,
                'AB',
                'hashed',
                new \App\Shared\Domain\ValueObject\Uuid('user-id'),
                true
            ));
        $token = $this->createMock(TokenInterface::class);
        $token->method('getAttribute')->with('sid')->willReturn('session-id');
        $security->method('getToken')->willReturn($token);

        return new \App\User\Application\Resolver\CurrentUserIdentityResolver($security);
    }
}
