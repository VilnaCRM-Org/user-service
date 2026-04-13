<?php

declare(strict_types=1);

namespace App\Tests\Memory\Rest;

use PHPUnit\Framework\Attributes\Group;

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
}
