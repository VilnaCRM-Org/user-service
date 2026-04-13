<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$privateKey = file_get_contents(__DIR__ . '/Memory/Fixtures/Jwt/private.pem');
$publicKey = file_get_contents(__DIR__ . '/Memory/Fixtures/Jwt/public.pem');

if (!is_string($privateKey) || !is_string($publicKey)) {
    throw new RuntimeException('Memory-suite JWT fixtures are missing or unreadable.');
}

$_ENV['OAUTH_PRIVATE_KEY'] = $privateKey;
$_SERVER['OAUTH_PRIVATE_KEY'] = $privateKey;
putenv('OAUTH_PRIVATE_KEY=' . $privateKey);

$_ENV['OAUTH_PUBLIC_KEY'] = $publicKey;
$_SERVER['OAUTH_PUBLIC_KEY'] = $publicKey;
putenv('OAUTH_PUBLIC_KEY=' . $publicKey);

$_ENV['OAUTH_PASSPHRASE'] = '';
$_SERVER['OAUTH_PASSPHRASE'] = '';
putenv('OAUTH_PASSPHRASE=');
