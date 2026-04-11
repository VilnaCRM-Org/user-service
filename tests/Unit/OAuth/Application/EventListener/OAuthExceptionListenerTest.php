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
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class OAuthExceptionListenerTest extends UnitTestCase
{
    private OAuthExceptionListener $listener;
    private HttpKernelInterface $kernel;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new OAuthExceptionListener();
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

    public function testNonOAuthExceptionIsIgnored(): void
    {
        $event = $this->createExceptionEvent(
            new RuntimeException($this->faker->sentence())
        );

        ($this->listener)($event);

        $this->assertNull($event->getResponse());
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

    private function createExceptionEvent(\Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->kernel,
            Request::create('/api/auth/social/github'),
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
}
