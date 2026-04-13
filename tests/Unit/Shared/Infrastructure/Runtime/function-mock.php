<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime;

final class MockFrankenPhpFunctions
{
    public static array $handleRequestResults = [];
    public static array $ignoreUserAbortArguments = [];
    public static int $handleRequestCalls = 0;
    public static int $gcCollectCyclesCalls = 0;
    public static int $gcMemCachesCalls = 0;

    public static function reset(): void
    {
        self::$handleRequestResults = [];
        self::$ignoreUserAbortArguments = [];
        self::$handleRequestCalls = 0;
        self::$gcCollectCyclesCalls = 0;
        self::$gcMemCachesCalls = 0;
    }
}

function ignore_user_abort(bool $enable): int
{
    MockFrankenPhpFunctions::$ignoreUserAbortArguments[] = $enable;

    return 0;
}

function frankenphp_handle_request(callable $callable): bool
{
    ++MockFrankenPhpFunctions::$handleRequestCalls;
    $callable();

    return array_shift(MockFrankenPhpFunctions::$handleRequestResults) ?? false;
}

function gc_collect_cycles(): int
{
    ++MockFrankenPhpFunctions::$gcCollectCyclesCalls;

    return 0;
}

function gc_mem_caches(): int
{
    ++MockFrankenPhpFunctions::$gcMemCachesCalls;

    return 0;
}
