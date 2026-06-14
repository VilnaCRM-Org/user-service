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
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class OAuthExceptionListenerLoggingTest extends UnitTestCase
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
}
