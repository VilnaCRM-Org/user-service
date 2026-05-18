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
    /**
     * @var array{
     *     email: string,
     *     challengeId: string,
     *     userId: string,
     *     accessToken: string,
     *     refreshToken: string,
     *     sessionId: string,
     *     passkeyId: string,
     *     credentialId: string,
     *     challenge: string,
     *     ipAddress: string,
     *     userAgent: string,
     *     initials: string,
     *     displayName: string,
     *     signupLabel: string,
     *     registrationLabel: string,
     *     rpId: string,
     *     passwordHash: string,
     *     credential: array<string, string>
     * }
     */
    private array $fixtures;

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
        $this->fixtures = $this->createFixtures();
    }

    public function testPasskeySignUpOptionsDispatchesCommand(): void
    {
        $this->expectValidation(PasskeySignUpOptionsDto::class);
        $this->expectDispatch(
            StartPasskeySignUpCommand::class,
            $this->createOptionsResult(),
            function (StartPasskeySignUpCommand $command): void {
                self::assertSame($this->fixtures['email'], $command->email);
                self::assertSame($this->fixtures['initials'], $command->initials);
                self::assertSame($this->fixtures['displayName'], $command->displayName);
            }
        );

        $payload = $this->createSignUpOptionsResolver()->__invoke(
            null,
            $this->signUpOptionsContext()
        );

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
                self::assertSame($this->fixtures['signupLabel'], $command->label);
                self::assertTrue($command->rememberMe);
            }
        );

        $payload = $this->createSignUpCompleteResolver()->__invoke(null, $this->completeContext([
            'label' => $this->fixtures['signupLabel'],
            'rememberMe' => true,
        ]));

        $this->assertTokenPayload($payload, 'auth-passkey-signup-complete');
    }

    public function testPasskeySignInOptionsDispatchesCommand(): void
    {
        $this->expectValidation(PasskeySignInOptionsDto::class);
        $this->expectDispatch(
            StartPasskeySignInCommand::class,
            $this->createOptionsResult(),
            function (StartPasskeySignInCommand $command): void {
                self::assertSame($this->fixtures['email'], $command->email);
                self::assertTrue($command->rememberMe);
            }
        );

        $payload = (new PasskeySignInOptionsAuthMutationResolver(
            $this->validator,
            $this->commandBus,
            $this->payloadFactory
        ))->__invoke(null, [
            'args' => [
                'input' => ['email' => $this->fixtures['email'], 'rememberMe' => true],
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
            new PasskeyAuthenticationResult('', '', true, '', $this->fixtures['challengeId']),
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
        self::assertSame($this->fixtures['challengeId'], $payload->getPendingSessionId());
        self::assertNull($payload->getAccessToken());
    }

    public function testPasskeyRegistrationOptionsUsesCurrentIdentity(): void
    {
        $this->expectDispatch(
            StartPasskeyRegistrationCommand::class,
            $this->createOptionsResult(),
            function (StartPasskeyRegistrationCommand $command): void {
                self::assertSame($this->fixtures['userId'], $command->userId);
                self::assertSame($this->fixtures['email'], $command->email);
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
                self::assertSame($this->fixtures['challengeId'], $command->challengeId);
                self::assertSame($this->fixtures['credential'], $command->credential);
                self::assertSame($this->fixtures['registrationLabel'], $command->label);
                self::assertSame($this->fixtures['userId'], $command->currentUserId);
            }
        );

        $context = $this->completeContext(['label' => $this->fixtures['registrationLabel']]);
        $payload = $this->registrationCompleteResolver()->__invoke(null, $context);

        self::assertInstanceOf(AuthPayload::class, $payload);
        self::assertSame('auth-passkey-registration-complete', $payload->getId());
        self::assertSame($credential->getCredentialId(), $payload->getCredentialId());
    }

    /**
     * @return array{
     *     email: string,
     *     challengeId: string,
     *     userId: string,
     *     accessToken: string,
     *     refreshToken: string,
     *     sessionId: string,
     *     passkeyId: string,
     *     credentialId: string,
     *     challenge: string,
     *     ipAddress: string,
     *     userAgent: string,
     *     initials: string,
     *     displayName: string,
     *     signupLabel: string,
     *     registrationLabel: string,
     *     rpId: string,
     *     passwordHash: string,
     *     credential: array<string, string>
     * }
     */
    private function createFixtures(): array
    {
        return [
            'email' => $this->faker->safeEmail(),
            'challengeId' => $this->faker->uuid(),
            'userId' => $this->faker->uuid(),
            'accessToken' => $this->faker->sha256(),
            'refreshToken' => $this->faker->sha256(),
            'sessionId' => $this->faker->uuid(),
            'passkeyId' => $this->faker->uuid(),
            'credentialId' => $this->faker->sha256(),
            'challenge' => $this->faker->sha256(),
            'ipAddress' => $this->faker->ipv4(),
            'userAgent' => $this->faker->userAgent(),
            'initials' => strtoupper($this->faker->lexify('??')),
            'displayName' => $this->faker->name(),
            'signupLabel' => $this->faker->words(2, true),
            'registrationLabel' => $this->faker->words(2, true),
            'rpId' => $this->faker->domainName(),
            'passwordHash' => $this->faker->password(),
            'credential' => ['id' => $this->faker->sha256()],
        ];
    }

    private function createSignUpOptionsResolver(): PasskeySignUpOptionsAuthMutationResolver
    {
        return new PasskeySignUpOptionsAuthMutationResolver(
            $this->validator,
            $this->commandBus,
            $this->payloadFactory
        );
    }

    private function createSignUpCompleteResolver(): PasskeySignUpCompleteAuthMutationResolver
    {
        return new PasskeySignUpCompleteAuthMutationResolver(
            $this->validator,
            $this->commandBus,
            $this->payloadFactory,
            $this->requestContextResolver
        );
    }

    private function registrationCompleteResolver(): PasskeyRegistrationCompleteAuthMutationResolver
    {
        return new PasskeyRegistrationCompleteAuthMutationResolver(
            $this->validator,
            $this->commandBus,
            $this->payloadFactory,
            $this->identityResolver()
        );
    }

    /**
     * @return array{args: array{input: array<string, string>}}
     */
    private function signUpOptionsContext(): array
    {
        return [
            'args' => [
                'input' => [
                    'email' => $this->fixtures['email'],
                    'initials' => $this->fixtures['initials'],
                    'displayName' => $this->fixtures['displayName'],
                ],
            ],
        ];
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
            ->willReturn($this->fixtures['ipAddress']);
        $this->requestContextResolver->expects($this->once())
            ->method('resolveUserAgent')
            ->with($this->request)
            ->willReturn($this->fixtures['userAgent']);
    }

    private function assertCompleteCommand(
        CompletePasskeySignUpCommand|CompletePasskeySignInCommand $command
    ): void {
        self::assertSame($this->fixtures['challengeId'], $command->challengeId);
        self::assertSame($this->fixtures['credential'], $command->credential);
        self::assertSame($this->fixtures['ipAddress'], $command->ipAddress);
        self::assertSame($this->fixtures['userAgent'], $command->userAgent);
    }

    /**
     * @param array<string, bool|string> $extraInput
     *
     * @return array{
     *     args: array{input: array<string, array<string, string>|bool|string>},
     *     request: Request
     * }
     */
    private function completeContext(array $extraInput = []): array
    {
        return [
            'args' => [
                'input' => array_merge([
                    'challengeId' => $this->fixtures['challengeId'],
                    'credential' => $this->fixtures['credential'],
                ], $extraInput),
            ],
            'request' => $this->request,
        ];
    }

    private function assertOptionsPayload(object $payload, string $id): void
    {
        self::assertInstanceOf(AuthPayload::class, $payload);
        self::assertSame($id, $payload->getId());
        self::assertSame($this->fixtures['challengeId'], $payload->getChallengeId());
        self::assertSame(['rpId' => $this->fixtures['rpId']], $payload->getPublicKey());
    }

    private function assertTokenPayload(object $payload, string $id): void
    {
        self::assertInstanceOf(AuthPayload::class, $payload);
        self::assertSame($id, $payload->getId());
        self::assertFalse($payload->isTwoFactorEnabled());
        self::assertSame($this->fixtures['accessToken'], $payload->getAccessToken());
        self::assertSame($this->fixtures['refreshToken'], $payload->getRefreshToken());
    }

    private function createOptionsResult(): PasskeyOptionsResult
    {
        $now = new DateTimeImmutable();

        return new PasskeyOptionsResult(
            new PasskeyChallenge(
                $this->fixtures['challengeId'],
                PasskeyChallenge::PURPOSE_AUTHENTICATION,
                $this->fixtures['challenge'],
                '{}',
                $now,
                $now->modify('+5 minutes'),
                new PasskeyChallengeContext(
                    $this->fixtures['email'],
                    userId: $this->fixtures['userId']
                )
            ),
            ['rpId' => $this->fixtures['rpId']]
        );
    }

    private function createAuthenticationResult(): PasskeyAuthenticationResult
    {
        return new PasskeyAuthenticationResult(
            $this->fixtures['accessToken'],
            $this->fixtures['refreshToken'],
            true,
            $this->fixtures['sessionId']
        );
    }

    private function createCredential(): PasskeyCredential
    {
        return new PasskeyCredential(
            $this->fixtures['passkeyId'],
            $this->fixtures['userId'],
            $this->fixtures['credentialId'],
            '{}',
            $this->fixtures['registrationLabel'],
            new DateTimeImmutable()
        );
    }

    private function identityResolver(): \App\User\Application\Resolver\CurrentUserIdentityResolver
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')
            ->willReturn(new \App\User\Application\DTO\AuthorizationUserDto(
                $this->fixtures['email'],
                $this->fixtures['initials'],
                $this->fixtures['passwordHash'],
                new \App\Shared\Domain\ValueObject\Uuid($this->fixtures['userId']),
                true
            ));
        $token = $this->createMock(TokenInterface::class);
        $token->method('getAttribute')->with('sid')->willReturn($this->fixtures['sessionId']);
        $security->method('getToken')->willReturn($token);

        return new \App\User\Application\Resolver\CurrentUserIdentityResolver($security);
    }
}
