<?php

declare(strict_types=1);

namespace App\Tests\Memory\Inventory;

use App\Tests\Memory\Support\MemoryCoverageCatalog;
use PHPUnit\Framework\TestCase;

final class MemorySuiteCoverageTest extends TestCase
{
    public function testRestLoadScriptsHaveFullMemoryCoverage(): void
    {
        $this->assertSameCoverage(
            MemoryCoverageCatalog::coveredRestLoadScripts(),
            $this->discoverRestLoadScriptNames(
                static fn (string $name): bool => !str_starts_with($name, 'oauth'),
            ),
        );
    }

    public function testGraphQlLoadScriptsHaveFullMemoryCoverage(): void
    {
        $this->assertSameCoverage(
            MemoryCoverageCatalog::coveredGraphQlLoadScripts(),
            $this->discoverNames(
                dirname(__DIR__, 2) . '/Load/scripts/graphql/*.js',
                '.js',
            ),
        );
    }

    public function testOAuthLoadScriptsHaveFullMemoryCoverage(): void
    {
        $this->assertSameCoverage(
            MemoryCoverageCatalog::coveredOAuthLoadScripts(),
            $this->discoverRestLoadScriptNames(
                static fn (string $name): bool => str_starts_with($name, 'oauth'),
            ),
        );
    }

    public function testBehatFeatureFilesHaveFullMemoryCoverage(): void
    {
        $this->assertSameCoverage(
            MemoryCoverageCatalog::coveredFeatureFiles(),
            $this->discoverNames(
                dirname(__DIR__, 3) . '/features/*.feature',
                '.feature',
            ),
        );
    }

    /**
     * @return list<string>
     */
    private function discoverRestLoadScriptNames(callable $filter): array
    {
        return $this->filterNames(
            $this->discoverNames(
                dirname(__DIR__, 2) . '/Load/scripts/rest-api/*.js',
                '.js',
            ),
            $filter,
        );
    }

    /**
     * @param list<string> $names
     * @param callable(string): bool $filter
     *
     * @return list<string>
     */
    private function filterNames(array $names, callable $filter): array
    {
        return \array_values(\array_filter(
            $names,
            static fn (string $name): bool => $filter($name),
        ));
    }

    /**
     * @return list<string>
     */
    private function discoverNames(string $pattern, string $suffix): array
    {
        $files = \glob($pattern);
        self::assertIsArray($files);

        return \array_values(\array_map(
            static fn (string $file): string => \basename($file, $suffix),
            $files,
        ));
    }

    /**
     * @param list<string> $cataloged
     * @param list<string> $discovered
     */
    private function assertSameCoverage(array $cataloged, array $discovered): void
    {
        \sort($cataloged);
        \sort($discovered);

        self::assertSame(
            $cataloged,
            $discovered,
            sprintf(
                'Expected catalog to match discovered coverage. Expected: [%s], actual: [%s].',
                implode(', ', $cataloged),
                implode(', ', $discovered)
            )
        );
    }
}
