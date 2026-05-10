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
    }

    public function testSignUpOptionsProcessorReturnsChallengeOptions(): void
    {
        $this->registrationService->expects($this->once())
            ->method('startSignup')
            ->with('person@example.com', 'PE', 'Person Example')
            ->willReturn($this->createOptionsResult());

        $processor = new PasskeySignUpOptionsProcessor(
            $this->registrationService,
            new PasskeyResponseFactory()
        );

        $this->assertOptionsResponse($processor->process(
            new PasskeySignUpOptionsDto('person@example.com', 'PE', 'Person Example'),
            $this->operation
        ));
    }

    public function testSignUpCompleteProcessorIssuesTokensAndCookie(): void
    {
        $request = new Request();
        $dto = new PasskeySignUpCompleteDto('challenge-id', ['id' => 'credential-id'], 'Phone');
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
                'challenge-id',
                ['id' => 'credential-id'],
                'Security key'
            ),
            $this->operation
        );
        $this->assertCredentialResponse($response);
    }

    public function testSignInOptionsProcessorReturnsAuthenticationOptions(): void
    {
        $dto = new PasskeySignInOptionsDto('person@example.com');
        $dto->setRememberMe(true);
        $this->authenticationService->expects($this->once())
            ->method('start')
            ->with('person@example.com', true)
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
            new PasskeySignInCompleteDto('challenge-id', ['id' => 'credential-id']),
            $this->operation,
            [],
            ['request' => $request]
        ));
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
            ->willReturn('203.0.113.10');
        $this->requestContextResolver->expects($this->once())
            ->method('resolveUserAgent')
            ->with($request)
            ->willReturn('Browser');
    }

    private function expectSignUpCompletion(Request $request): void
    {
        $this->expectRequestContext($request);
        $this->registrationService->expects($this->once())
            ->method('completeSignup')
            ->with(
                'challenge-id',
                ['id' => 'credential-id'],
                'Phone',
                true,
                '203.0.113.10',
                'Browser'
            )
            ->willReturn(new PasskeyAuthenticationResult('access-token', 'refresh-token', true));
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
            ->with('018f33bb-1111-7222-8333-111111111111', 'person@example.com')
            ->willReturn($this->createOptionsResult());
    }

    private function expectRegistrationComplete(): void
    {
        $this->registrationService->expects($this->once())
            ->method('completeRegistration')
            ->with(
                'challenge-id',
                ['id' => 'credential-id'],
                'Security key',
                '018f33bb-1111-7222-8333-111111111111'
            )
            ->willReturn($this->createCredential());
    }

    private function expectSignInCompletion(Request $request): void
    {
        $this->expectRequestContext($request);
        $this->authenticationService->expects($this->once())
            ->method('complete')
            ->with('challenge-id', ['id' => 'credential-id'], '203.0.113.10', 'Browser')
            ->willReturn(new PasskeyAuthenticationResult('access-token', 'refresh-token', false));
        $this->expectAccessTokenCookie(false);
    }

    private function expectAccessTokenCookie(bool $rememberMe): void
    {
        $this->authCookieFactory->expects($this->once())
            ->method('create')
            ->with('access-token', $rememberMe)
            ->willReturn(new Cookie('access_token', 'access-token'));
    }

    private function assertOptionsResponse(Response $response): void
    {
        $payload = json_decode((string) $response->getContent(), true);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('challenge-id', $payload['challenge_id']);
        self::assertSame('localhost', $payload['public_key']['rpId']);
    }

    private function assertTokenResponse(Response $response): void
    {
        $payload = json_decode((string) $response->getContent(), true);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('access-token', $payload['access_token']);
        self::assertSame('refresh-token', $payload['refresh_token']);
        self::assertCount(1, $response->headers->getCookies());
    }

    private function assertCredentialResponse(Response $response): void
    {
        $payload = json_decode((string) $response->getContent(), true);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('credential-id', $payload['credential_id']);
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
            'challenge-id',
            PasskeyChallenge::PURPOSE_AUTHENTICATION,
            'challenge',
            '{}',
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext('person@example.com', userId: 'user-id')
        );

        return new PasskeyOptionsResult($challenge, ['rpId' => 'localhost']);
    }

    private function createIdentityResolver(): CurrentUserIdentityResolver
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(new AuthorizationUserDto(
            'person@example.com',
            'PE',
            'hashed-password',
            (new UuidTransformer(new UuidFactory()))->transformFromString(
                '018f33bb-1111-7222-8333-111111111111'
            ),
            true
        ));

        return new CurrentUserIdentityResolver($security);
    }

    private function createCredential(): PasskeyCredential
    {
        return new PasskeyCredential(
            'passkey-id',
            'user-id',
            'credential-id',
            '{"record":true}',
            'Security key',
            new DateTimeImmutable()
        );
    }
}
