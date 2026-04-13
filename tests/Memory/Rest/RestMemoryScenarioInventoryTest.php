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
        self::assertSame(
            $this->expectedScenarios(),
            $this->discoveredScenarios(),
            'Expected memory scenario catalog to match rest load scripts.',
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

    /**
     * @return list<string>
     */
    private function expectedScenarios(): array
    {
        $expectedScenarios = array_merge(
            array_keys(RestMemoryScenarioInventory::COVERED_LOAD_SCENARIOS),
            RestMemoryScenarioInventory::DEFERRED_LOAD_SCENARIOS,
        );
        sort($expectedScenarios);

        return $expectedScenarios;
    }

    /**
     * @return list<string>
     */
    private function discoveredScenarios(): array
    {
        $scripts = glob(dirname(__DIR__, 2) . '/Load/scripts/rest-api/*.js');
        self::assertIsArray($scripts);

        $actualScenarios = array_map(
            static fn (string $path): string => basename($path, '.js'),
            $scripts,
        );
        sort($actualScenarios);

        return $actualScenarios;
    }
}
