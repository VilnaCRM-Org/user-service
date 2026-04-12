<?php

declare(strict_types=1);

namespace App\Tests\Memory\Inventory;

use App\Tests\Memory\Support\MemoryInventoryCoverage;
use App\Tests\Memory\Support\MemoryInventorySources;
use PHPUnit\Framework\TestCase;

final class MemoryCoverageInventoryTest extends TestCase
{
    public function testEmptyExpectedInventoryDefaultsToOneHundredPercentCoverage(): void
    {
        self::assertSame(100, MemoryInventoryCoverage::percentage([], []));
        self::assertSame([], MemoryInventoryCoverage::missing([], []));
        self::assertSame([], MemoryInventoryCoverage::unexpected([], []));
    }

    public function testBehatFeatureInventoryCoverageStaysAtOneHundredPercent(): void
    {
        $this->assertInventoryCoverage(
            MemoryInventorySources::behatFeatures(),
            MemoryCoverageInventory::BEHAT_FEATURES,
            'Behat feature memory inventory'
        );
    }

    public function testGraphQlLoadInventoryCoverageStaysAtOneHundredPercent(): void
    {
        $this->assertInventoryCoverage(
            MemoryInventorySources::graphQlLoadScenarios(),
            MemoryCoverageInventory::GRAPHQL_LOAD_SCENARIOS,
            'GraphQL memory inventory'
        );
    }

    public function testImplementedMemoryCoveragePointsToRealTests(): void
    {
        foreach (MemoryCoverageInventory::IMPLEMENTED_MEMORY_TESTS as $key => $coverage) {
            self::assertTrue(
                method_exists($coverage['class'], $coverage['method']),
                sprintf(
                    'Memory coverage entry "%s" expects %s::%s to exist.',
                    $key,
                    $coverage['class'],
                    $coverage['method']
                )
            );
        }
    }

    public function testRestLoadInventoryCoverageStaysAtOneHundredPercent(): void
    {
        $this->assertInventoryCoverage(
            MemoryInventorySources::restLoadScenarios(),
            MemoryCoverageInventory::REST_LOAD_SCENARIOS,
            'REST memory inventory'
        );
    }

    /**
     * @param list<string> $expectedItems
     * @param list<string> $accountedItems
     */
    private function assertInventoryCoverage(
        array $expectedItems,
        array $accountedItems,
        string $label
    ): void {
        self::assertSame(
            MemoryCoverageInventory::INVENTORY_COVERAGE_THRESHOLD,
            MemoryInventoryCoverage::percentage($expectedItems, $accountedItems),
            sprintf(
                '%s must stay at %d%% coverage.',
                $label,
                MemoryCoverageInventory::INVENTORY_COVERAGE_THRESHOLD
            )
        );
        self::assertSame(
            [],
            MemoryInventoryCoverage::missing($expectedItems, $accountedItems),
            sprintf('%s is missing baseline entries.', $label)
        );
        self::assertSame(
            [],
            MemoryInventoryCoverage::unexpected($expectedItems, $accountedItems),
            sprintf('%s contains unexpected entries.', $label)
        );
    }
}
