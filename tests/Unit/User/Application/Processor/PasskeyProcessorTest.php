<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Validator\Http\EmptyJsonObjectRequestValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompletePasskeyRegistrationCommand;
use App\User\Application\Command\CompletePasskeySignInCommand;
use App\User\Application\Command\CompletePasskeySignUpCommand;
use App\User\Application\Command\StartPasskeyRegistrationCommand;
use App\User\Application\Command\StartPasskeySignInCommand;
use App\User\Application\Command\StartPasskeySignUpCommand;
use App\User\Application\DTO\AuthorizationUserDto;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Application\DTO\PasskeyRegistrationCompleteDto;
use App\User\Application\DTO\PasskeyRegistrationOptionsDto;
use App\User\Application\DTO\PasskeySignInCompleteDto;
use App\User\Application\DTO\PasskeySignInOptionsDto;
use App\User\Application\DTO\PasskeySignUpCompleteDto;
use App\User\Application\DTO\PasskeySignUpOptionsDto;
use App\User\Application\Factory\AuthCookieFactoryInterface;
use App\User\Application\Factory\PasskeyResponseFactory;
use App\User\Application\Processor\PasskeyRegistrationCompleteProcessor;
use App\User\Application\Processor\PasskeyRegistrationOptionsProcessor;
use App\User\Application\Processor\PasskeySignInCompleteProcessor;
use App\User\Application\Processor\PasskeySignInOptionsProcessor;
use App\User\Application\Processor\PasskeySignUpCompleteProcessor;
use App\User\Application\Processor\PasskeySignUpOptionsProcessor;
use App\User\Application\Resolver\CurrentUserIdentityResolver;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;
use const JSON_THROW_ON_ERROR;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class PasskeyProcessorTest extends UnitTestCase
{
    private Operation $operation;
    private CommandBusInterface&MockObject $commandBus;
    private HttpRequestContextResolverInterface&MockObject $requestContextResolver;
    private AuthCookieFactoryInterface&MockObject $authCookieFactory;
    /**
     * @var array{
     *     email: string,
     *     initials: string,
     *     displayName: string,
     *     userId: string,
     *     challengeId: string,
     *     credentialId: string,
     *     credentialPayload: array<string, string>,
     *     label: string,
     *     ipAddress: string,
     *     userAgent: string,
     *     accessToken: string,
     *     refreshToken: string,
     *     rpId: string
     * }
     */
    private array $fixture;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->operation = $this->createMock(Operation::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->requestContextResolver = $this->createMock(
            HttpRequestContextResolverInterface::class
        );
        $this->authCookieFactory = $this->createMock(AuthCookieFactoryInterface::class);
        $this->fixture = $this->createFixture();
    }

    public function testSignUpOptionsProcessorReturnsChallengeOptions(): void
    {
        $this->expectCommandDispatch(
            StartPasskeySignUpCommand::class,
            $this->createOptionsResult()
        );

        $responseFactory = new PasskeyResponseFactory();
        $processor = new PasskeySignUpOptionsProcessor($this->commandBus, $responseFactory);

        $this->assertOptionsResponse($processor->process(
            new PasskeySignUpOptionsDto(
                $this->fixture['email'],
                $this->fixture['initials'],
                $this->fixture['displayName']
            ),
            $this->operation
        ));
    }

    public function testSignUpCompleteProcessorIssuesTokensAndCookie(): void
    {
        $request = new Request();
        $dto = new PasskeySignUpCompleteDto(
            $this->fixture['challengeId'],
            $this->fixture['credentialPayload'],
            $this->fixture['label']
        );
        $dto->setRememberMe(true);
        $this->expectSignUpCompletion($request);

        $this->assertTokenResponse($this->createSignUpCompleteProcessor()->process(
            $dto,
            $this->operation,
            [],
            ['request' => $request]
        ));
    }

    public function testRegistrationOptionsProcessorUsesAuthenticatedIdentity(): void
    {
        $request = new Request([], [], [], [], [], [], '{}');
        $this->expectRegistrationOptions($request);

        $this->assertOptionsResponse($this->createRegistrationOptionsProcessor()->process(
            new PasskeyRegistrationOptionsDto(),
            $this->operation,
            [],
            ['request' => $request]
        ));
    }

    public function testRegistrationOptionsProcessorRejectsRequestBodyContent(): void
    {
        $request = new Request([], [], [], [], [], [], '{"unexpected":true}');
        $this->requestContextResolver->expects($this->once())
            ->method('resolveRequest')
            ->with($request)
            ->willReturn($request);
        $this->commandBus->expects($this->never())->method('dispatch');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('This operation does not accept request body content.');

        $this->createRegistrationOptionsProcessor()->process(
            new PasskeyRegistrationOptionsDto(),
            $this->operation,
            [],
            ['request' => $request]
        );
    }

    public function testRegistrationCompleteProcessorReturnsCredentialId(): void
    {
        $this->expectRegistrationComplete();

        $response = $this->createRegistrationCompleteProcessor()->process(
            new PasskeyRegistrationCompleteDto(
                $this->fixture['challengeId'],
                $this->fixture['credentialPayload'],
                $this->fixture['label']
            ),
            $this->operation
        );
        $this->assertCredentialResponse($response);
    }

    public function testSignInOptionsProcessorReturnsAuthenticationOptions(): void
    {
        $dto = new PasskeySignInOptionsDto($this->fixture['email']);
        $dto->setRememberMe(true);
        $this->expectCommandDispatch(
            StartPasskeySignInCommand::class,
            $this->createOptionsResult(),
            function (StartPasskeySignInCommand $command): void {
                self::assertSame($this->fixture['email'], $command->email);
                self::assertTrue($command->rememberMe);
            }
        );

        $processor = new PasskeySignInOptionsProcessor(
            $this->commandBus,
            new PasskeyResponseFactory()
        );

        $this->assertOptionsResponse($processor->process($dto, $this->operation));
    }

    public function testSignInCompleteProcessorIssuesTokensAndCookie(): void
    {
        $request = new Request();
        $this->expectSignInCompletion($request);

        $this->assertTokenResponse($this->createSignInCompleteProcessor()->process(
            new PasskeySignInCompleteDto(
                $this->fixture['challengeId'],
                $this->fixture['credentialPayload']
            ),
            $this->operation,
            [],
            ['request' => $request]
        ));
    }

    /**
     * @return array{
     *     email: string,
     *     initials: string,
     *     displayName: string,
     *     userId: string,
     *     challengeId: string,
     *     credentialId: string,
     *     credentialPayload: array<string, string>,
     *     label: string,
     *     ipAddress: string,
     *     userAgent: string,
     *     accessToken: string,
     *     refreshToken: string,
     *     rpId: string
     * }
     */
    private function createFixture(): array
    {
        $credentialId = $this->faker->uuid();

        return [
            'email' => $this->faker->safeEmail(),
            'initials' => $this->faker->lexify('??'),
            'displayName' => $this->faker->name(),
            'userId' => $this->faker->uuid(),
            'challengeId' => $this->faker->uuid(),
            'credentialId' => $credentialId,
            'credentialPayload' => ['id' => $credentialId],
            'label' => $this->faker->words(2, true),
            'ipAddress' => $this->faker->ipv4(),
            'userAgent' => $this->faker->userAgent(),
            'accessToken' => $this->faker->sha256(),
            'refreshToken' => $this->faker->sha256(),
            'rpId' => $this->faker->domainName(),
        ];
    }

    private function expectRequestContext(Request $request): void
    {
        $this->requestContextResolver->expects($this->once())
            ->method('resolveRequest')
            ->with($request)
            ->willReturn($request);
        $this->requestContextResolver->expects($this->once())
            ->method('resolveIpAddress')
            ->with($request)
            ->willReturn($this->fixture['ipAddress']);
        $this->requestContextResolver->expects($this->once())
            ->method('resolveUserAgent')
            ->with($request)
            ->willReturn($this->fixture['userAgent']);
    }

    private function expectSignUpCompletion(Request $request): void
    {
        $this->expectRequestContext($request);
        $this->expectCommandDispatch(
            CompletePasskeySignUpCommand::class,
            new PasskeyAuthenticationResult(
                $this->fixture['accessToken'],
                $this->fixture['refreshToken'],
                true
            ),
            function (CompletePasskeySignUpCommand $command): void {
                self::assertSame($this->fixture['challengeId'], $command->challengeId);
                self::assertSame($this->fixture['credentialPayload'], $command->credential);
                self::assertSame($this->fixture['label'], $command->label);
                self::assertTrue($command->rememberMe);
                self::assertSame($this->fixture['ipAddress'], $command->ipAddress);
                self::assertSame($this->fixture['userAgent'], $command->userAgent);
            }
        );
        $this->expectAccessTokenCookie(true);
    }

    private function expectRegistrationOptions(Request $request): void
    {
        $this->requestContextResolver->expects($this->once())
            ->method('resolveRequest')
            ->with($request)
            ->willReturn($request);
        $this->expectCommandDispatch(
            StartPasskeyRegistrationCommand::class,
            $this->createOptionsResult(),
            function (StartPasskeyRegistrationCommand $command): void {
                self::assertSame($this->fixture['userId'], $command->userId);
                self::assertSame($this->fixture['email'], $command->email);
            }
        );
    }

    private function expectRegistrationComplete(): void
    {
        $this->expectCommandDispatch(
            CompletePasskeyRegistrationCommand::class,
            $this->createCredential(),
            function (CompletePasskeyRegistrationCommand $command): void {
                self::assertSame($this->fixture['challengeId'], $command->challengeId);
                self::assertSame($this->fixture['credentialPayload'], $command->credential);
                self::assertSame($this->fixture['label'], $command->label);
                self::assertSame($this->fixture['userId'], $command->currentUserId);
            }
        );
    }

    private function expectSignInCompletion(Request $request): void
    {
        $this->expectRequestContext($request);
        $this->expectCommandDispatch(
            CompletePasskeySignInCommand::class,
            new PasskeyAuthenticationResult(
                $this->fixture['accessToken'],
                $this->fixture['refreshToken'],
                false
            ),
            function (CompletePasskeySignInCommand $command): void {
                self::assertSame($this->fixture['challengeId'], $command->challengeId);
                self::assertSame($this->fixture['credentialPayload'], $command->credential);
                self::assertSame($this->fixture['ipAddress'], $command->ipAddress);
                self::assertSame($this->fixture['userAgent'], $command->userAgent);
            }
        );
        $this->expectAccessTokenCookie(false);
    }

    /**
     * @param class-string<CommandInterface> $commandClass
     */
    private function expectCommandDispatch(
        string $commandClass,
        object $response,
        ?callable $assertCommand = null
    ): void {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with(self::callback(static function (
                CommandInterface $command
            ) use ($commandClass, $response, $assertCommand): bool {
                self::assertInstanceOf($commandClass, $command);

                if ($assertCommand !== null) {
                    $assertCommand($command);
                }

                self::setPasskeyCommandResponse($command, $response);

                return true;
            }));
    }

    private static function setPasskeyCommandResponse(
        CommandInterface $command,
        object $response
    ): void {
        if (self::setOptionsCommandResponse($command, $response)) {
            return;
        }

        if (self::setAuthenticationCommandResponse($command, $response)) {
            return;
        }

        if (!$command instanceof CompletePasskeyRegistrationCommand) {
            self::fail('Unexpected passkey command.');
        }
        if (!$response instanceof PasskeyCredential) {
            self::fail('Expected passkey credential response.');
        }
        $command->setResponse($response);
    }

    private static function setOptionsCommandResponse(
        CommandInterface $command,
        object $response
    ): bool {
        if (!$command instanceof StartPasskeySignUpCommand
            && !$command instanceof StartPasskeyRegistrationCommand
            && !$command instanceof StartPasskeySignInCommand
        ) {
            return false;
        }

        if (!$response instanceof PasskeyOptionsResult) {
            self::fail('Expected passkey options response.');
        }
        $command->setResponse($response);

        return true;
    }

    private static function setAuthenticationCommandResponse(
        CommandInterface $command,
        object $response
    ): bool {
        if (!$command instanceof CompletePasskeySignUpCommand
            && !$command instanceof CompletePasskeySignInCommand
        ) {
            return false;
        }

        if (!$response instanceof PasskeyAuthenticationResult) {
            self::fail('Expected passkey authentication response.');
        }
        $command->setResponse($response);

        return true;
    }

    private function expectAccessTokenCookie(bool $rememberMe): void
    {
        $this->authCookieFactory->expects($this->once())
            ->method('create')
            ->with($this->fixture['accessToken'], $rememberMe)
            ->willReturn(new Cookie('access_token', $this->fixture['accessToken']));
    }

    private function assertOptionsResponse(Response $response): void
    {
        $payload = json_decode((string) $response->getContent(), true);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($this->fixture['challengeId'], $payload['challenge_id']);
        self::assertSame($this->fixture['rpId'], $payload['public_key']['rpId']);
    }

    private function assertTokenResponse(Response $response): void
    {
        $payload = json_decode((string) $response->getContent(), true);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($this->fixture['accessToken'], $payload['access_token']);
        self::assertSame($this->fixture['refreshToken'], $payload['refresh_token']);
        self::assertCount(1, $response->headers->getCookies());
    }

    private function assertCredentialResponse(Response $response): void
    {
        $payload = json_decode((string) $response->getContent(), true);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($this->fixture['credentialId'], $payload['credential_id']);
    }

    private function createSignUpCompleteProcessor(): PasskeySignUpCompleteProcessor
    {
        return new PasskeySignUpCompleteProcessor(
            $this->commandBus,
            new PasskeyResponseFactory(),
            $this->requestContextResolver,
            $this->authCookieFactory
        );
    }

    private function createRegistrationOptionsProcessor(): PasskeyRegistrationOptionsProcessor
    {
        return new PasskeyRegistrationOptionsProcessor(
            $this->commandBus,
            new PasskeyResponseFactory(),
            $this->createIdentityResolver(),
            $this->requestContextResolver,
            new EmptyJsonObjectRequestValidator($this->createJsonSerializer())
        );
    }

    private function createRegistrationCompleteProcessor(): PasskeyRegistrationCompleteProcessor
    {
        return new PasskeyRegistrationCompleteProcessor(
            $this->commandBus,
            $this->createIdentityResolver()
        );
    }

    private function createSignInCompleteProcessor(): PasskeySignInCompleteProcessor
    {
        return new PasskeySignInCompleteProcessor(
            $this->commandBus,
            new PasskeyResponseFactory(),
            $this->requestContextResolver,
            $this->authCookieFactory
        );
    }

    private function createOptionsResult(): PasskeyOptionsResult
    {
        $createdAt = new DateTimeImmutable();
        $challenge = new PasskeyChallenge(
            $this->fixture['challengeId'],
            PasskeyChallenge::PURPOSE_AUTHENTICATION,
            $this->faker->sha256(),
            json_encode(['challenge' => $this->faker->sha256()], JSON_THROW_ON_ERROR),
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext($this->fixture['email'], userId: $this->fixture['userId'])
        );

        return new PasskeyOptionsResult($challenge, ['rpId' => $this->fixture['rpId']]);
    }

    private function createIdentityResolver(): CurrentUserIdentityResolver
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new AuthorizationUserDto(
            $this->fixture['email'],
            $this->fixture['initials'],
            $this->faker->password(),
            (new UuidTransformer(new UuidFactory()))->transformFromString($this->fixture['userId']),
            true
        ));

        return new CurrentUserIdentityResolver($security);
    }

    private function createCredential(): PasskeyCredential
    {
        return new PasskeyCredential(
            $this->faker->uuid(),
            $this->fixture['userId'],
            $this->fixture['credentialId'],
            json_encode(['record' => $this->faker->boolean()], JSON_THROW_ON_ERROR),
            $this->fixture['label'],
            new DateTimeImmutable()
        );
    }
}
