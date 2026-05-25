<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @phpstan-type JsonScalar bool|float|int|string|null
 * @phpstan-type JsonBody array<string, array<string, mixed>|JsonScalar>
 * @phpstan-type JsonResponse array{response: Response, body: JsonBody}
 */
final class PasskeyAuthEndpointsIntegrationTest extends IntegrationTestCase
{
    private HttpKernelInterface $httpKernel;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $kernel = $this->container->get('kernel');
        $this->assertInstanceOf(HttpKernelInterface::class, $kernel);
        $this->httpKernel = $kernel;
    }

    public function testSignupOptionsReturnsBrowserSafeWebauthnJson(): void
    {
        $response = $this->requestJson(
            '/api/passkeys/signup/options',
            [
                'email' => $this->faker->safeEmail(),
                'initials' => 'PK',
                'displayName' => 'Passkey Integration',
            ]
        );

        $this->assertSame(Response::HTTP_OK, $response['response']->getStatusCode());
        $challengeId = $this->requireStringKey($response['body'], 'challenge_id');
        $this->assertNotSame('', $challengeId);

        $publicKey = $response['body']['public_key'] ?? null;
        $this->assertIsArray($publicKey);
        $this->assertMatchesRegularExpression(
            '/^[A-Za-z0-9_-]+$/',
            $this->requireStringKey($publicKey, 'challenge')
        );
        $this->assertSame('localhost', $publicKey['rp']['id'] ?? null);
        $this->assertSame('required', $publicKey['authenticatorSelection']['userVerification'] ?? null);
    }

    /**
     * @param JsonBody $body
     */
    private function requireStringKey(array $body, string $key): string
    {
        $value = $body[$key] ?? null;
        $this->assertIsString($value);
        $this->assertNotSame('', $value);

        return $value;
    }

    /**
     * @param array<string, string> $payload
     *
     * @return JsonResponse
     */
    private function requestJson(string $uri, array $payload): array
    {
        $response = $this->httpKernel->handle(
            Request::create(
                $uri,
                Request::METHOD_POST,
                [],
                [],
                [],
                [
                    'REMOTE_ADDR' => $this->faker->ipv4(),
                    'HTTP_ACCEPT' => 'application/json',
                    'CONTENT_TYPE' => 'application/json',
                ],
                json_encode($payload, JSON_THROW_ON_ERROR)
            )
        );

        return ['response' => $response, 'body' => $this->decodeBody($response)];
    }

    /**
     * @return JsonBody
     */
    private function decodeBody(Response $response): array
    {
        $decoded = json_decode((string) $response->getContent(), true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }
}
