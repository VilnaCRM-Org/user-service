<?php

declare(strict_types=1);

function base64UrlEncode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

$privateKeyPath = dirname(__DIR__, 2) . '/config/jwt/private.pem';
$privateKeyContents = @file_get_contents($privateKeyPath);
if (!is_string($privateKeyContents) || $privateKeyContents === '') {
    fail('Unable to read JWT private key.');
}

$privateKey = openssl_pkey_get_private($privateKeyContents);
if ($privateKey === false) {
    fail('Unable to load JWT private key.');
}

$now = time();
$header = [
    'alg' => 'RS256',
    'typ' => 'JWT',
];
$payload = [
    'sub' => 'load-test-service',
    'iss' => 'vilnacrm-user-service',
    'aud' => 'vilnacrm-api',
    'exp' => $now + 900,
    'iat' => $now,
    'nbf' => $now,
    'jti' => bin2hex(random_bytes(16)),
    'sid' => bin2hex(random_bytes(16)),
    'roles' => ['ROLE_SERVICE'],
];

try {
    $encodedHeader = base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
    $encodedPayload = base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
} catch (JsonException $exception) {
    fail('Unable to encode JWT header/payload.');
}

$unsignedToken = $encodedHeader . '.' . $encodedPayload;
$signature = '';
if (!openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
    fail('Unable to sign JWT token.');
}

echo $unsignedToken . '.' . base64UrlEncode($signature);
