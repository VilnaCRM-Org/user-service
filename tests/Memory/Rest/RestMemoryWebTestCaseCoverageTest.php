<?php

declare(strict_types=1);

namespace App\Tests\Memory\Rest;

use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Response;

#[Group('memory')]
#[Group('memory-rest')]
final class RestMemoryWebTestCaseCoverageTest extends RestMemoryWebTestCase
{
    public function testRepeatSameKernelScenarioRejectsNonPositiveIterations(): void
    {
        $repeatSameKernelScenario = \Closure::bind(
            function (callable $scenario, int $iterations): void {
                $this->repeatSameKernelScenario($scenario, $iterations);
            },
            $this,
            RestMemoryWebTestCase::class,
        );

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Iterations must be greater than zero.');

        $repeatSameKernelScenario(static function (): void {
        }, 0);
    }

    public function testEncodeRequestContentSupportsFormAndFallbackPayloads(): void
    {
        $encodeRequestContent = \Closure::bind(
            function (string $method, array $payload, string $contentType): ?string {
                return $this->encodeRequestContent($method, $payload, $contentType);
            },
            $this,
            RestMemoryWebTestCase::class,
        );

        self::assertSame(
            'grant_type=client_credentials',
            $encodeRequestContent(
                'POST',
                ['grant_type' => 'client_credentials'],
                'application/x-www-form-urlencoded',
            ),
        );
        self::assertSame(
            '{"hello":"world"}',
            $encodeRequestContent('POST', ['hello' => 'world'], 'text/plain'),
        );
        self::assertNull($encodeRequestContent('POST', [], 'text/plain'));
    }

    public function testDecodeJsonRejectsMalformedJson(): void
    {
        $decodeJson = \Closure::bind(
            function (Response $response): array {
                return $this->decodeJson($response);
            },
            $this,
            RestMemoryWebTestCase::class,
        );

        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Failed to decode JSON response');

        $decodeJson(new Response('{invalid-json'));
    }

    public function testDecodeJsonRejectsScalarJson(): void
    {
        $decodeJson = \Closure::bind(
            function (Response $response): array {
                return $this->decodeJson($response);
            },
            $this,
            RestMemoryWebTestCase::class,
        );

        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('Expected decoded JSON response to be an array');

        $decodeJson(new Response('"scalar"'));
    }
}
