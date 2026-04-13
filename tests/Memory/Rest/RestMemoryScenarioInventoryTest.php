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

        $actualScenarios = array_map(
            static fn (string $path): string => basename($path, '.js'),
            $scripts
        );
        sort($actualScenarios);

        $accountedScenarios = array_merge(
            array_keys(RestMemoryScenarioInventory::COVERED_LOAD_SCENARIOS),
            RestMemoryScenarioInventory::DEFERRED_LOAD_SCENARIOS
        );
        sort($accountedScenarios);

        self::assertSame($actualScenarios, $accountedScenarios);
    }

    public function testCoveredScenariosPointToRealTestMethods(): void
    {
        foreach (RestMemoryScenarioInventory::COVERED_LOAD_SCENARIOS as $scenario => $coverage) {
            self::assertTrue(
                method_exists($coverage['class'], $coverage['method']),
                sprintf(
                    'Scenario "%s" expects %s::%s to exist.',
                    $scenario,
                    $coverage['class'],
                    $coverage['method']
                )
            );
        }
    }
}
