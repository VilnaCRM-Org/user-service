<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Runtime;

final class MockFrankenPhpFunctions
{
    /** @var array<int, array{invoke?: bool, result?: bool, server?: array<string, mixed>}> */
    public static array $handleRequestBehaviors = [];
    /** @var list<bool> */
    public static array $handleRequestResults = [];
    /** @var list<?bool> */
    public static array $ignoreUserAbortArguments = [];
    /**
     * @var list<array{
     *     filename: string,
     *     use_include_path: bool,
     *     context: mixed,
     *     offset: int,
     *     length: ?int
     * }>
     */
    public static array $fileGetContentsArguments = [];
    /** @var list<array{0: array<string, mixed>, 1: array<string, mixed>}> */
    public static array $requestParseBodyResults = [];
    /** @var list<?array> */
    public static array $requestParseBodyArguments = [];
    public static int $handleRequestCalls = 0;
    public static int $interceptedPhpInputCalls = 0;
    public static int $gcCollectCyclesCalls = 0;
    public static int $gcMemCachesCalls = 0;
    public static int $requestParseBodyCalls = 0;
    public static string|false|null $fileGetContentsResult = null;
    public static ?\Throwable $requestParseBodyException = null;

    public static function reset(): void
    {
        self::$handleRequestBehaviors = [];
        self::$handleRequestResults = [];
        self::$ignoreUserAbortArguments = [];
        self::$fileGetContentsArguments = [];
        self::$requestParseBodyResults = [];
        self::$requestParseBodyArguments = [];
        self::$handleRequestCalls = 0;
        self::$interceptedPhpInputCalls = 0;
        self::$gcCollectCyclesCalls = 0;
        self::$gcMemCachesCalls = 0;
        self::$requestParseBodyCalls = 0;
        self::$fileGetContentsResult = null;
        self::$requestParseBodyException = null;
    }

    /**
     * @return array{
     *     server: array<string, mixed>,
     *     post: array<string, mixed>,
     *     files: array<string, mixed>
     * }
     */
    public static function snapshotRequestGlobals(): array
    {
        return [
            'server' => $_SERVER,
            'post' => $_POST,
            'files' => $_FILES,
        ];
    }

    /**
     * @param array{
     *     server: array<string, mixed>,
     *     post: array<string, mixed>,
     *     files: array<string, mixed>
     * } $snapshot
     */
    public static function restoreRequestGlobals(array $snapshot): void
    {
        $_SERVER = $snapshot['server'];
        $_POST = $snapshot['post'];
        $_FILES = $snapshot['files'];
    }

    /** @param array<string, mixed> $server */
    public static function replaceServer(array $server): void
    {
        $_SERVER = $server;
    }

    /** @param array<string, mixed> $post */
    public static function replacePost(array $post): void
    {
        $_POST = $post;
    }

    /** @param array<string, mixed> $files */
    public static function replaceFiles(array $files): void
    {
        $_FILES = $files;
    }

    /**
     * @param array<int, array{invoke?: bool, result?: bool, server?: array<string, mixed>}> $behaviors
     */
    public static function setHandleRequestBehaviors(array $behaviors): void
    {
        self::$handleRequestBehaviors = $behaviors;
    }

    /** @param list<bool> $results */
    public static function setHandleRequestResults(array $results): void
    {
        self::$handleRequestResults = $results;
    }

    /**
     * @param list<array{0: array<string, mixed>, 1: array<string, mixed>}> $results
     */
    public static function setRequestParseBodyResults(array $results): void
    {
        self::$requestParseBodyResults = $results;
    }

    public static function setRequestParseBodyException(?\Throwable $exception): void
    {
        self::$requestParseBodyException = $exception;
    }

    public static function setFileGetContentsResult(string|false|null $result): void
    {
        self::$fileGetContentsResult = $result;
    }
}

function ignore_user_abort(?bool $enable = null): int
{
    MockFrankenPhpFunctions::$ignoreUserAbortArguments = [
        ...MockFrankenPhpFunctions::$ignoreUserAbortArguments,
        $enable,
    ];

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

function file_get_contents(
    string $filename,
    bool $use_include_path = false,
    mixed $context = null,
    int $offset = 0,
    ?int $length = null,
): string|false
{
    MockFrankenPhpFunctions::$fileGetContentsArguments = [
        ...MockFrankenPhpFunctions::$fileGetContentsArguments,
        [
            'filename' => $filename,
            'use_include_path' => $use_include_path,
            'context' => $context,
            'offset' => $offset,
            'length' => $length,
        ],
    ];

    if ($filename !== 'php://input' || MockFrankenPhpFunctions::$fileGetContentsResult === null) {
        return \file_get_contents(
            $filename,
            $use_include_path,
            $context,
            $offset,
            $length,
        );
    }

    ++MockFrankenPhpFunctions::$interceptedPhpInputCalls;

    return MockFrankenPhpFunctions::$fileGetContentsResult;
}

/**
 * @return array{0: array<string, mixed>, 1: array<string, mixed>}
 */
function request_parse_body(?array $options = null): array
{
    MockFrankenPhpFunctions::$requestParseBodyArguments = [
        ...MockFrankenPhpFunctions::$requestParseBodyArguments,
        $options,
    ];

    ++MockFrankenPhpFunctions::$requestParseBodyCalls;

    if (MockFrankenPhpFunctions::$requestParseBodyException instanceof \Throwable) {
        throw MockFrankenPhpFunctions::$requestParseBodyException;
    }

    return array_shift(MockFrankenPhpFunctions::$requestParseBodyResults) ?? [[], []];
}

namespace App\Shared\Infrastructure\Runtime\Factory;

/**
 * @return array{0: array<string, mixed>, 1: array<string, mixed>}
 */
function request_parse_body(?array $options = null): array
{
    return \App\Shared\Infrastructure\Runtime\request_parse_body($options);
}

function file_get_contents(
    string $filename,
    bool $use_include_path = false,
    mixed $context = null,
    int $offset = 0,
    ?int $length = null,
): string|false
{
    return \App\Shared\Infrastructure\Runtime\file_get_contents(
        $filename,
        $use_include_path,
        $context,
        $offset,
        $length,
    );
}
