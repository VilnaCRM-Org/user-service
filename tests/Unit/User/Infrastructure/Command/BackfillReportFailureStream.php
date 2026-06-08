<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Command;

#[\AllowDynamicProperties]
final class BackfillReportFailureStream
{
    private static int $directoryMode = 0040777;

    /**
     * @param list<array|bool|float|int|object|string|null> $_arguments
     *
     * @return array{mode: int}|bool
     */
    public function __call(string $name, array $_arguments): array|bool
    {
        return match ($name) {
            'stream_open' => true,
            'stream_write' => false,
            'url_stat' => ['mode' => self::$directoryMode],
            default => false,
        };
    }

    public static function useDirectoryMode(int $directoryMode): void
    {
        self::$directoryMode = $directoryMode;
    }
}
