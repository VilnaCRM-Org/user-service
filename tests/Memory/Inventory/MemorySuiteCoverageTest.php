<?php

declare(strict_types=1);

namespace App\Tests\Memory\Inventory;

use App\Tests\Memory\Support\MemoryCoverageCatalog;

use function array_values;
use function basename;
use function glob;

use PHPUnit\Framework\TestCase;

use function sort;

final class MemorySuiteCoverageTest extends TestCase
{
    public function testRestLoadScriptsHaveFullMemoryCoverage(): void
    {
        $this->assertSameCoverage(
            $this->discoverRestLoadScriptNames(
                static fn (string $name): bool => !str_starts_with($name, 'oauth'),
            ),
            MemoryCoverageCatalog::coveredRestLoadScripts(),
        );
    }

    public function testGraphQlLoadScriptsHaveFullMemoryCoverage(): void
    {
        $this->assertSameCoverage(
            $this->discoverNames(
                dirname(__DIR__, 2) . '/Load/scripts/graphql/*.js',
                '.js',
            ),
            MemoryCoverageCatalog::coveredGraphQlLoadScripts(),
        );
    }

    public function testOAuthLoadScriptsHaveFullMemoryCoverage(): void
    {
        $this->assertSameCoverage(
            $this->discoverRestLoadScriptNames(
                static fn (string $name): bool => str_starts_with($name, 'oauth'),
            ),
            MemoryCoverageCatalog::coveredOAuthLoadScripts(),
        );
    }

    public function testBehatFeatureFilesHaveFullMemoryCoverage(): void
    {
        $this->assertSameCoverage(
            $this->discoverNames(
                dirname(__DIR__, 3) . '/features/*.feature',
                '.feature',
            ),
            MemoryCoverageCatalog::coveredFeatureFiles(),
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
     * @param callable(string): bool $filter
     *
     * @return list<string>
     */
    private function filterNames(array $names, callable $filter): array
    {
        return array_values(array_filter(
            $names,
            static fn (string $name): bool => $filter($name),
        ));
    }

    /**
     * @return list<string>
     */
    private function discoverNames(string $pattern, string $suffix): array
    {
        $files = glob($pattern);
        self::assertIsArray($files);

        return array_values(array_map(
            static fn (string $file): string => basename($file, $suffix),
            $files,
        ));
    }

    /**
     * @param list<string> $expected
     * @param list<string> $actual
     */
    private function assertSameCoverage(array $expected, array $actual): void
    {
        sort($expected);
        sort($actual);

        self::assertSame($expected, $actual);
    }
}
