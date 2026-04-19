<?php

declare(strict_types=1);

namespace App\Tests\Memory\Rest;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

#[Group('memory')]
#[Group('memory-rest')]
final class PublicApiSurfaceMemoryTest extends RestMemoryWebTestCase
{
    private const REST_API_SURFACE_TARGETS = [
        'apiContextUser' => [
            'uri' => '/api/contexts/User',
            'status' => Response::HTTP_UNAUTHORIZED,
            'expectedBodyField' => 'title',
            'expectedBodyValue' => 'Unauthorized',
            'headers' => [],
        ],
        'apiDocs' => [
            'uri' => '/api/docs',
            'status' => Response::HTTP_OK,
            'expectedBodyField' => 'title',
            'expectedBodyValue' => 'User Service API',
            'headers' => ['HTTP_ACCEPT' => 'application/ld+json'],
        ],
        'apiEntrypoint' => [
            'uri' => '/api/',
            'status' => Response::HTTP_UNAUTHORIZED,
            'expectedBodyField' => 'title',
            'expectedBodyValue' => 'Unauthorized',
            'headers' => [],
        ],
        'apiErrors400' => [
            'uri' => '/api/errors/400',
            'status' => Response::HTTP_UNAUTHORIZED,
            'expectedBodyField' => 'title',
            'expectedBodyValue' => 'Unauthorized',
            'headers' => [],
        ],
        'apiValidationErrors' => [
            'uri' => '/api/validation_errors/validation',
            'status' => Response::HTTP_UNAUTHORIZED,
            'expectedBodyField' => 'title',
            'expectedBodyValue' => 'Unauthorized',
            'headers' => [],
        ],
        'apiWellKnownGenid' => [
            'uri' => '/api/.well-known/genid/memory-surface',
            'status' => Response::HTTP_NOT_FOUND,
            'expectedBodyField' => 'title',
            'expectedBodyValue' => 'An error occurred',
            'headers' => [],
        ],
    ];

    /**
     * @return iterable<string, array{0: string, 1: string, 2: int, 3: string, 4: string, 5: array<string, string>}>
     */
    public static function publicApiSurfaceTargets(): iterable
    {
        foreach (self::apiSurfaceTargets() as $coverageTarget => $scenario) {
            yield $coverageTarget => [
                $coverageTarget,
                $scenario['uri'],
                $scenario['status'],
                $scenario['expectedBodyField'],
                $scenario['expectedBodyValue'],
                $scenario['headers'],
            ];
        }
    }

    public function testPublicApiSurfaceTargetsProviderEnumeratesEveryTarget(): void
    {
        self::assertSame(
            array_keys(self::apiSurfaceTargets()),
            array_keys(iterator_to_array(self::publicApiSurfaceTargets())),
        );
    }

    public function testPublicApiSurfaceInventoryMatchesLoadScripts(): void
    {
        $expected = array_keys(self::apiSurfaceTargets());
        sort($expected);

        $actual = array_values(array_filter(
            $this->restLoadScriptTargets(),
            static fn (string $target): bool => in_array($target, $expected, true),
        ));
        sort($actual);

        self::assertSame($expected, $actual);
    }

    #[DataProvider('publicApiSurfaceTargets')]
    /**
     * @param array<string, string> $headers
     */
    public function testApiPlatformSurfaceScenariosStayStableAcrossRepeatedSameKernelRequests(
        string $coverageTarget,
        string $uri,
        int $expectedStatus,
        string $expectedBodyField,
        string $expectedBodyValue,
        array $headers,
    ): void {
        $this->runRepeatedRestScenario($coverageTarget, function () use (
            $uri,
            $expectedStatus,
            $expectedBodyField,
            $expectedBodyValue,
            $headers,
        ): void {
            ['response' => $response, 'body' => $body] = $this->requestJson(
                'GET',
                $uri,
                [],
                $headers,
            );

            self::assertSame($expectedStatus, $response->getStatusCode());
            self::assertSame($expectedBodyValue, $body[$expectedBodyField] ?? null);
        }, 5);
    }

    /**
     * @return list<string>
     */
    private function restLoadScriptTargets(): array
    {
        $files = glob(dirname(__DIR__, 3) . '/tests/Load/scripts/rest-api/*.js');
        $paths = is_array($files) ? $files : [];
        $targets = array_map(
            static fn (string $path): string => pathinfo($path, PATHINFO_FILENAME),
            $paths,
        );
        sort($targets);

        return array_values($targets);
    }

    /**
     * @return array<string, array{
     *     uri: string,
     *     status: int,
     *     expectedBodyField: string,
     *     expectedBodyValue: string,
     *     headers: array<string, string>
     * }>
     */
    private static function apiSurfaceTargets(): array
    {
        return self::REST_API_SURFACE_TARGETS + [
            'oauthAuthorize' => [
                'uri' => self::oauthAuthorizeUri(),
                'status' => Response::HTTP_UNAUTHORIZED,
                'expectedBodyField' => 'title',
                'expectedBodyValue' => 'Unauthorized',
                'headers' => [],
            ],
        ];
    }

    private static function oauthAuthorizeUri(): string
    {
        return '/api/oauth/authorize?' . http_build_query(
            [
                'response_type' => 'code',
                'client_id' => 'memory-suite',
                'redirect_uri' => 'https://example.com/callback',
                'scope' => 'read',
                'state' => 'memory-state',
            ],
            '',
            '&',
            PHP_QUERY_RFC3986,
        );
    }
}
