<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Application\Validator\Http\EmptyJsonObjectRequestValidator;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
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
use App\User\Application\Passkey\PasskeyAuthenticationServiceInterface;
use App\User\Application\Passkey\PasskeyRegistrationServiceInterface;
use App\User\Application\Passkey\PasskeyResponseFactory;
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
    private PasskeyRegistrationServiceInterface&MockObject $registrationService;
    private PasskeyAuthenticationServiceInterface&MockObject $authenticationService;
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
        $this->registrationService = $this->createMock(PasskeyRegistrationServiceInterface::class);
        $this->authenticationService = $this->createMock(
            PasskeyAuthenticationServiceInterface::class
        );
        $this->requestContextResolver = $this->createMock(
            HttpRequestContextResolverInterface::class
        );
        $this->authCookieFactory = $this->createMock(AuthCookieFactoryInterface::class);
        $this->fixture = $this->createFixture();
    }

    public function testSignUpOptionsProcessorReturnsChallengeOptions(): void
    {
        $this->registrationService->expects($this->once())
            ->method('startSignup')
            ->with(
                $this->fixture['email'],
                $this->fixture['initials'],
                $this->fixture['displayName']
            )
            ->willReturn($this->createOptionsResult());

        $processor = new PasskeySignUpOptionsProcessor(
            $this->registrationService,
            new PasskeyResponseFactory()
        );

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
            $this->fixture['label'],
            true
        );
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
        $this->registrationService->expects($this->never())->method('startRegistration');

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
        $dto = new PasskeySignInOptionsDto($this->fixture['email'], true);
        $this->authenticationService->expects($this->once())
            ->method('start')
            ->with($this->fixture['email'], true)
            ->willReturn($this->createOptionsResult());

        $processor = new PasskeySignInOptionsProcessor(
            $this->authenticationService,
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
        $this->registrationService->expects($this->once())
            ->method('completeSignup')
            ->with(
                $this->fixture['challengeId'],
                $this->fixture['credentialPayload'],
                $this->fixture['label'],
                true,
                $this->fixture['ipAddress'],
                $this->fixture['userAgent']
            )
            ->willReturn(new PasskeyAuthenticationResult(
                $this->fixture['accessToken'],
                $this->fixture['refreshToken'],
                true
            ));
        $this->expectAccessTokenCookie(true);
    }

    private function expectRegistrationOptions(Request $request): void
    {
        $this->requestContextResolver->expects($this->once())
            ->method('resolveRequest')
            ->with($request)
            ->willReturn($request);
        $this->registrationService->expects($this->once())
            ->method('startRegistration')
            ->with($this->fixture['userId'], $this->fixture['email'])
            ->willReturn($this->createOptionsResult());
    }

    private function expectRegistrationComplete(): void
    {
        $this->registrationService->expects($this->once())
            ->method('completeRegistration')
            ->with(
                $this->fixture['challengeId'],
                $this->fixture['credentialPayload'],
                $this->fixture['label'],
                $this->fixture['userId']
            )
            ->willReturn($this->createCredential());
    }

    private function expectSignInCompletion(Request $request): void
    {
        $this->expectRequestContext($request);
        $this->authenticationService->expects($this->once())
            ->method('complete')
            ->with(
                $this->fixture['challengeId'],
                $this->fixture['credentialPayload'],
                $this->fixture['ipAddress'],
                $this->fixture['userAgent']
            )
            ->willReturn(new PasskeyAuthenticationResult(
                $this->fixture['accessToken'],
                $this->fixture['refreshToken'],
                false
            ));
        $this->expectAccessTokenCookie(false);
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
            $this->registrationService,
            new PasskeyResponseFactory(),
            $this->requestContextResolver,
            $this->authCookieFactory
        );
    }

    private function createRegistrationOptionsProcessor(): PasskeyRegistrationOptionsProcessor
    {
        return new PasskeyRegistrationOptionsProcessor(
            $this->registrationService,
            new PasskeyResponseFactory(),
            $this->createIdentityResolver(),
            $this->requestContextResolver,
            new EmptyJsonObjectRequestValidator($this->createJsonSerializer())
        );
    }

    private function createRegistrationCompleteProcessor(): PasskeyRegistrationCompleteProcessor
    {
        return new PasskeyRegistrationCompleteProcessor(
            $this->registrationService,
            $this->createIdentityResolver()
        );
    }

    private function createSignInCompleteProcessor(): PasskeySignInCompleteProcessor
    {
        return new PasskeySignInCompleteProcessor(
            $this->authenticationService,
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
