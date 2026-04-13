<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime;

final class MockFrankenPhpFunctions
{
    public static array $handleRequestBehaviors = [];
    public static array $handleRequestResults = [];
    public static array $ignoreUserAbortArguments = [];
    public static array $requestParseBodyResults = [];
    public static int $handleRequestCalls = 0;
    public static int $gcCollectCyclesCalls = 0;
    public static int $gcMemCachesCalls = 0;
    public static int $requestParseBodyCalls = 0;
    public static ?\Throwable $requestParseBodyException = null;

    public static function reset(): void
    {
        self::$handleRequestBehaviors = [];
        self::$handleRequestResults = [];
        self::$ignoreUserAbortArguments = [];
        self::$requestParseBodyResults = [];
        self::$handleRequestCalls = 0;
        self::$gcCollectCyclesCalls = 0;
        self::$gcMemCachesCalls = 0;
        self::$requestParseBodyCalls = 0;
        self::$requestParseBodyException = null;
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

    $behavior = array_shift(MockFrankenPhpFunctions::$handleRequestBehaviors);
    if (is_array($behavior) && array_key_exists('server', $behavior)) {
        $_SERVER = $behavior['server'];
    }

    if (!is_array($behavior) || ($behavior['invoke'] ?? true)) {
        $callable();
    }

    if (is_array($behavior)) {
        return $behavior['result'] ?? false;
    }

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

function request_parse_body(): array
{
    ++MockFrankenPhpFunctions::$requestParseBodyCalls;

    if (MockFrankenPhpFunctions::$requestParseBodyException instanceof \Throwable) {
        throw MockFrankenPhpFunctions::$requestParseBodyException;
    }

    return array_shift(MockFrankenPhpFunctions::$requestParseBodyResults) ?? [[], []];
}
