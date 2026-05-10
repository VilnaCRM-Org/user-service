<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Adapter;

final class OpenSslEncryptTestDouble
{
    private static bool $forceFailure = false;

    public static function enableFailure(): void
    {
        self::$forceFailure = true;
    }

    public static function disableFailure(): void
    {
        self::$forceFailure = false;
    }

    public static function isFailureEnabled(): bool
    {
        return self::$forceFailure;
    }
}

function openssl_encrypt(
    string $data,
    string $cipherAlgorithm,
    string $passphrase,
    int $options = 0,
    string $iv = '',
    mixed &$tag = null,
    string $aad = '',
    int $tagLength = 16
): string|false {
    if (OpenSslEncryptTestDouble::isFailureEnabled()) {
        return false;
    }

    return \openssl_encrypt(
        $data,
        $cipherAlgorithm,
        $passphrase,
        $options,
        $iv,
        $tag,
        $aad,
        $tagLength
    );
}
