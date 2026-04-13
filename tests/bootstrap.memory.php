<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

$privateKey = file_get_contents(__DIR__ . '/Memory/Fixtures/Jwt/private.pem');
$publicKey = file_get_contents(__DIR__ . '/Memory/Fixtures/Jwt/public.pem');

if (!is_string($privateKey) || !is_string($publicKey)) {
    throw new RuntimeException('Memory-suite JWT fixtures are missing or unreadable.');
}

putenv('OAUTH_PRIVATE_KEY=' . $privateKey);
putenv('OAUTH_PUBLIC_KEY=' . $publicKey);
putenv('OAUTH_PASSPHRASE=');
