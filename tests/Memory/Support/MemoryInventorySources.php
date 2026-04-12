<?php

declare(strict_types=1);

namespace App\Tests\Memory\Support;

final class MemoryInventorySources
{
    /**
     * @return list<string>
     */
    public static function behatFeatures(): array
    {
        return self::collectBasenames(self::projectRoot() . '/features/*.feature', '.feature');
    }

    /**
     * @return list<string>
     */
    public static function graphQlLoadScenarios(): array
    {
        return self::collectBasenames(
            self::projectRoot() . '/tests/Load/scripts/graphql/*.js',
            '.js'
        );
    }

    /**
     * @return list<string>
     */
    public static function restLoadScenarios(): array
    {
        return self::collectBasenames(
            self::projectRoot() . '/tests/Load/scripts/rest-api/*.js',
            '.js'
        );
    }

    private static function projectRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    /**
     * @return list<string>
     */
    private static function collectBasenames(string $pattern, string $suffix): array
    {
        $paths = self::normalizeGlobResult(glob($pattern));

        $basenames = array_map(
            static fn (string $path): string => basename($path, $suffix),
            $paths
        );
        $basenames = array_values(array_unique($basenames));
        sort($basenames);

        return $basenames;
    }

    /**
     * @param array<int, string>|false $paths
     *
     * @return list<string>
     */
    private static function normalizeGlobResult(array|false $paths): array
    {
        if ($paths === false) {
            return [];
        }

        return array_values($paths);
    }
}
