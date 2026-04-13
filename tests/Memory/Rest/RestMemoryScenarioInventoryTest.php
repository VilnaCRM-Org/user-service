<?php

declare(strict_types=1);

namespace App\Tests\Memory\Rest;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('memory')]
#[Group('memory-rest')]
final class RestMemoryScenarioInventoryTest extends TestCase
{
    public function testRestLoadScriptInventoryIsFullyMappedByMemoryCoverage(): void
    {
        $scripts = glob(dirname(__DIR__, 2) . '/Load/scripts/rest-api/*.js');
        self::assertIsArray($scripts);

        $expectedScenarios = array_merge(
            array_keys(RestMemoryScenarioInventory::COVERED_LOAD_SCENARIOS),
            RestMemoryScenarioInventory::DEFERRED_LOAD_SCENARIOS
        );
        sort($expectedScenarios);

        $actualScenarios = array_map(
            static fn (string $path): string => basename($path, '.js'),
            $scripts
        );
        sort($actualScenarios);

        self::assertSame(
            $expectedScenarios,
            $actualScenarios,
            sprintf(
                'Expected memory scenario catalog to match rest load scripts. Expected: [%s], actual: [%s].',
                implode(', ', $expectedScenarios),
                implode(', ', $actualScenarios)
            )
        );
    }

    public function testCoveredScenariosPointToRealTestMethods(): void
    {
        foreach (RestMemoryScenarioInventory::COVERED_LOAD_SCENARIOS as $scenario => $coverage) {
            self::assertTrue(
                method_exists($coverage['class'], $coverage['method']),
                sprintf(
                    'Expected scenario "%s" to resolve to %s::%s.',
                    $scenario,
                    $coverage['class'],
                    $coverage['method']
                )
            );
        }
    }
}
