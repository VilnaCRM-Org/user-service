<?php

declare(strict_types=1);

namespace App\Tests\Memory\GraphQL;

use PHPUnit\Framework\Attributes\Group;

#[Group('memory')]
#[Group('memory-graphql')]
final class GraphQLMemoryWebTestCaseCoverageTest extends GraphQLMemoryWebTestCase
{
    public function testRunRepeatedGraphQlScenarioRejectsNonPositiveIterations(): void
    {
        $runRepeatedGraphQlScenario = \Closure::bind(
            function (callable $scenario, int $iterations): void {
                $this->runRepeatedGraphQlScenario(
                    'graphql-coverage',
                    $scenario,
                    $iterations,
                );
            },
            $this,
            GraphQLMemoryWebTestCase::class,
        );

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Iterations must be greater than zero.');

        $runRepeatedGraphQlScenario(static function (): void {
        }, 0);
    }
}
