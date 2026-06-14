<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Application\EventListener;

use App\OAuth\Application\EventListener\OAuthExceptionListener;
use App\OAuth\Domain\Exception\InvalidStateException;
use App\OAuth\Domain\Exception\MissingOAuthParametersException;
use App\OAuth\Domain\Exception\OAuthEmailUnavailableException;
use App\OAuth\Domain\Exception\OAuthProviderException;
use App\OAuth\Domain\Exception\ProviderMismatchException;
use App\OAuth\Domain\Exception\StateExpiredException;
use App\OAuth\Domain\Exception\UnsupportedProviderException;
use App\OAuth\Domain\Exception\UnverifiedProviderEmailException;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Exception\DuplicateEmailException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class OAuthExceptionListenerTest extends UnitTestCase
{
    private OAuthExceptionListener $listener;
    private HttpKernelInterface $kernel;
    private LoggerInterface $logger;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new OAuthExceptionListener($this->logger);
        $this->kernel = $this->createMock(HttpKernelInterface::class);
    }

    public function testUnsupportedProviderReturns400(): void
    {
        $provider = $this->faker->word();
        $event = $this->createExceptionEvent(
            new UnsupportedProviderException($provider)
        );

        ($this->listener)($event);

        $this->assertProblemResponse(
            $event,
            Response::HTTP_BAD_REQUEST,
            'unsupported_provider'
        );
    }

    public function testProviderMismatchReturns400(): void
    {
        $event = $this->createExceptionEvent(
            new ProviderMismatchException(
                $this->faker->word(),
                $this->faker->word()
            )
        );

        ($this->listener)($event);

        $this->assertProblemResponse(
            $event,
            Response::HTTP_BAD_REQUEST,
            'provider_mismatch'
        );
    }

    public function testInvalidStateReturns422(): void
    {
        $event = $this->createExceptionEvent(
            new InvalidStateException()
        );

        ($this->listener)($event);

        $this->assertProblemResponse(
            $event,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'invalid_state'
        );
    }

    public function testStateExpiredReturns422(): void
    {
        $event = $this->createExceptionEvent(
            new StateExpiredException()
        );

        ($this->listener)($event);

        $this->assertProblemResponse(
            $event,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'state_expired'
        );
    }

    public function testEmailUnavailableReturns422(): void
    {
        $event = $this->createExceptionEvent(
            new OAuthEmailUnavailableException($this->faker->word())
        );

        ($this->listener)($event);

        $this->assertProblemResponse(
            $event,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'provider_email_unavailable'
        );
    }

    public function testUnverifiedEmailReturns422(): void
    {
        $event = $this->createExceptionEvent(
            new UnverifiedProviderEmailException($this->faker->word())
        );

        ($this->listener)($event);

        $this->assertProblemResponse(
            $event,
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'unverified_provider_email'
        );
    }

    public function testMissingOAuthParametersReturns400(): void
    {
        $event = $this->createExceptionEvent(
            new MissingOAuthParametersException()
        );

        ($this->listener)($event);

        $this->assertProblemResponse(
            $event,
            Response::HTTP_BAD_REQUEST,
            'missing_oauth_parameters'
        );
    }

    public function testProviderExceptionReturns503(): void
    {
        $event = $this->createExceptionEvent(
            new OAuthProviderException(
                $this->faker->word(),
                $this->faker->sentence()
            )
        );

        ($this->listener)($event);

        $this->assertProblemResponse(
            $event,
            Response::HTTP_SERVICE_UNAVAILABLE,
            'provider_unavailable'
        );
    }

    public function testDuplicateEmailReturns409(): void
    {
        $email = $this->faker->safeEmail();
        $event = $this->createExceptionEvent(
            new DuplicateEmailException($email),
            sprintf(
                '/api/auth/social/%s/callback',
                $this->faker->randomElement(['facebook', 'github', 'google', 'twitter'])
            )
        );

        ($this->listener)($event);

        $this->assertProblemResponse(
            $event,
            Response::HTTP_CONFLICT,
            'duplicate_email'
        );
        $this->assertResponseDetail(
            $event,
            'Email address matches multiple local users; automatic linking is blocked.'
        );
        $this->assertResponseDetailDoesNotContain($event, $email);
    }

    public function testDuplicateEmailOutsideOAuthSocialCallbackIsIgnored(): void
    {
        $event = $this->createExceptionEvent(
            new DuplicateEmailException($this->faker->safeEmail()),
            $this->faker->randomElement(['/api/users', '/api/graphql', '/api/auth/social/github'])
        );

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testNonOAuthExceptionIsIgnored(): void
    {
        $event = $this->createExceptionEvent(
            new RuntimeException($this->faker->sentence())
        );

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
    }

    public function testProviderExceptionDoesNotLeakRawUpstreamMessage(): void
    {
        $clientId = 'Iv1.' . $this->faker->bothify('????????????????');
        $secret = $this->faker->sha256();
        $rawUpstreamMessage = $this->buildLeakyUpstreamMessage($clientId, $secret);

        $event = $this->createExceptionEvent(
            new OAuthProviderException('github', $rawUpstreamMessage)
        );

        ($this->listener)($event);

        $detail = $this->extractDetail($event);

        $this->assertStringNotContainsString($rawUpstreamMessage, $detail);
        $this->assertStringNotContainsString($clientId, $detail);
        $this->assertStringNotContainsString($secret, $detail);
        $this->assertStringNotContainsString('github.com', $detail);
        $this->assertStringNotContainsString('internal-host-12.local', $detail);
        $this->assertStringNotContainsString('401', $detail);
        $this->assertSame(
            'The authentication provider is currently unavailable. Try again later.',
            $detail,
        );
    }

    public function testHandledExceptionsNeverLeakTheirRawMessage(): void
    {
        $secretToken = 'leak-token-' . $this->faker->uuid();

        foreach ($this->handledExceptionFactories($secretToken) as $factory) {
            $event = $this->createExceptionEvent($factory());

            ($this->listener)($event);

            $detail = $this->extractDetail($event);

            $this->assertStringNotContainsString($secretToken, $detail);
        }
    }

    public function testFullExceptionMessageIsLoggedServerSide(): void
    {
        $rawUpstreamMessage = 'sensitive upstream detail ' . $this->faker->uuid();
        $exception = new OAuthProviderException('github', $rawUpstreamMessage);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains($rawUpstreamMessage),
                $this->callback(
                    static function (array $context) use ($exception): bool {
                        return ($context['exception'] ?? null) === $exception
                            && $context['error_code'] === 'provider_unavailable';
                    }
                )
            );

        $listener = new OAuthExceptionListener($logger);
        $listener($this->createExceptionEvent($exception));
    }

    public function testLoggedMessagePrefixesTheRawExceptionMessage(): void
    {
        $rawUpstreamMessage = 'sensitive upstream detail ' . $this->faker->uuid();
        $exception = new OAuthProviderException('github', $rawUpstreamMessage);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->identicalTo(
                    'OAuth flow failed: ' . $exception->getMessage()
                ),
                $this->anything()
            );

        $listener = new OAuthExceptionListener($logger);
        $listener($this->createExceptionEvent($exception));
    }

    public function testIgnoredExceptionIsNotLogged(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('error');

        $listener = new OAuthExceptionListener($logger);
        $listener($this->createExceptionEvent(
            new RuntimeException($this->faker->sentence())
        ));
    }

    public function testResponseContentTypeIsProblemJson(): void
    {
        $event = $this->createExceptionEvent(
            new UnsupportedProviderException($this->faker->word())
        );

        ($this->listener)($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(
            'application/problem+json',
            $response->headers->get('Content-Type')
        );
    }

    public function testResponseBodyContainsRequiredFields(): void
    {
        $provider = $this->faker->word();
        $event = $this->createExceptionEvent(
            new UnsupportedProviderException($provider)
        );

        ($this->listener)($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);

        $body = json_decode(
            (string) $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertArrayHasKey('type', $body);
        $this->assertArrayHasKey('title', $body);
        $this->assertArrayHasKey('detail', $body);
        $this->assertArrayHasKey('status', $body);
        $this->assertArrayHasKey('error_code', $body);
    }

    private function buildLeakyUpstreamMessage(string $clientId, string $secret): string
    {
        $url = sprintf(
            'https://github.com/login/oauth/access_token?client_id=%s&client_secret=%s',
            $clientId,
            $secret,
        );

        return sprintf(
            'Client error: `POST %s` resulted in a 401 Unauthorized %s at %s',
            $url,
            '{"error":"bad_verification_code"}',
            'internal-host-12.local',
        );
    }

    private function extractDetail(ExceptionEvent $event): string
    {
        $response = $event->getResponse();
        $this->assertNotNull($response);

        $body = json_decode(
            (string) $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertArrayHasKey('detail', $body);
        $this->assertIsString($body['detail']);

        return $body['detail'];
    }

    /**
     * @return list<callable(): \Throwable>
     */
    private function handledExceptionFactories(string $secret): array
    {
        return [
            static fn (): \Throwable => new UnsupportedProviderException($secret),
            static fn (): \Throwable => new MissingOAuthParametersException(),
            static fn (): \Throwable => new ProviderMismatchException($secret, $secret),
            static fn (): \Throwable => new InvalidStateException($secret),
            static fn (): \Throwable => new StateExpiredException(),
            static fn (): \Throwable => new OAuthEmailUnavailableException($secret),
            static fn (): \Throwable => new UnverifiedProviderEmailException($secret),
            static fn (): \Throwable => new OAuthProviderException($secret, $secret),
        ];
    }

    private function createExceptionEvent(
        \Throwable $exception,
        string $path = '/api/auth/social/github'
    ): ExceptionEvent {
        return new ExceptionEvent(
            $this->kernel,
            Request::create($path),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }

    private function assertProblemResponse(
        ExceptionEvent $event,
        int $expectedStatus,
        string $expectedErrorCode,
    ): void {
        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame($expectedStatus, $response->getStatusCode());

        $body = json_decode(
            (string) $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame($expectedErrorCode, $body['error_code']);
        $this->assertSame($expectedStatus, $body['status']);
    }

    private function assertResponseDetail(
        ExceptionEvent $event,
        string $expectedDetail,
    ): void {
        $this->assertSame($expectedDetail, $this->responseDetail($event));
    }

    private function assertResponseDetailDoesNotContain(
        ExceptionEvent $event,
        string $unexpectedValue,
    ): void {
        $this->assertStringNotContainsString($unexpectedValue, $this->responseDetail($event));
    }

    private function responseDetail(ExceptionEvent $event): string
    {
        $response = $event->getResponse();
        $this->assertNotNull($response);

        $body = json_decode(
            (string) $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $this->assertIsString($body['detail']);

        return $body['detail'];
    }
}
