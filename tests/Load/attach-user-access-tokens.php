<?php

declare(strict_types=1);

use App\Shared\Kernel;
use App\User\Domain\Entity\AuthSession;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

$base64UrlEncode = static fn (string $value): string => rtrim(
    strtr(base64_encode($value), '+/', '-_'),
    '='
);

$fail = static function (string $message): never {
    fwrite(STDERR, $message . PHP_EOL);
    throw new RuntimeException($message);
};

/**
 * @param array<string, int|string|array<string>> $payload
 */
$signJwt = static function (array $payload, OpenSSLAsymmetricKey $privateKey) use (
    $base64UrlEncode
): string {
    $header = [
        'alg' => 'RS256',
        'typ' => 'JWT',
    ];

    $encodedHeader = $base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
    $encodedPayload = $base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
    $unsignedToken = $encodedHeader . '.' . $encodedPayload;

    $signature = '';
    if (!openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('Unable to sign JWT token.');
    }

    return $unsignedToken . '.' . $base64UrlEncode($signature);
};

$usersFilePath = dirname(__DIR__) . '/Load/users.json';
$rawUsers = file_get_contents($usersFilePath);
if (!is_string($rawUsers) || $rawUsers === '') {
    $fail('Unable to read load-test users file.');
}

try {
    $users = json_decode($rawUsers, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    $fail('Unable to decode load-test users file.');
}

if (!is_array($users)) {
    $fail('Load-test users file must contain a JSON array.');
}

$projectDir = dirname(__DIR__, 2);
require $projectDir . '/vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->usePutenv()->bootEnv($projectDir . '/.env', 'test');

$kernel = new Kernel('test', false);
$kernel->boot();
$container = $kernel->getContainer();
$container = $container->has('test.service_container')
    ? $container->get('test.service_container')
    : $container;

$documentManager = $container->get('doctrine_mongodb.odm.default_document_manager');
if (!$documentManager instanceof DocumentManager) {
    $fail('DocumentManager service is not available.');
}

$privateKeyPath = $projectDir . '/config/jwt/private.pem';
$privateKeyContents = file_get_contents($privateKeyPath);
if (!is_string($privateKeyContents) || $privateKeyContents === '') {
    $fail('Unable to read JWT private key.');
}

$privateKey = openssl_pkey_get_private($privateKeyContents);
if (!$privateKey instanceof OpenSSLAsymmetricKey) {
    $fail('Unable to load JWT private key.');
}

$uuidFactory = new UuidFactory();
$ulidFactory = new UlidFactory();

foreach ($users as $index => &$user) {
    $userId = is_array($user) ? ($user['id'] ?? null) : null;
    $userEmail = is_array($user) ? ($user['email'] ?? null) : null;
    if (!is_string($userId) || $userId === '') {
        $fail(sprintf('User at index %d does not contain a valid id.', $index));
    }
    if (!is_string($userEmail) || $userEmail === '') {
        $fail(sprintf('User at index %d does not contain a valid email.', $index));
    }

    $now = new DateTimeImmutable();
    $sessionId = (string) $ulidFactory->create();
    $issuedAt = $now->getTimestamp();

    $documentManager->persist(
        new AuthSession(
            $sessionId,
            $userId,
            '127.0.0.1',
            'k6-load-test',
            $now,
            $now->modify('+15 minutes'),
            false
        )
    );

    try {
        $user['accessToken'] = $signJwt(
            [
                'sub' => $userEmail,
                'iss' => 'vilnacrm-user-service',
                'aud' => 'vilnacrm-api',
                'exp' => $issuedAt + 900,
                'iat' => $issuedAt,
                'nbf' => $issuedAt,
                'jti' => (string) $uuidFactory->create(),
                'sid' => $sessionId,
                'roles' => ['ROLE_USER'],
            ],
            $privateKey
        );
    } catch (JsonException|RuntimeException $exception) {
        $fail($exception->getMessage());
    }
}
unset($user);

$documentManager->flush();

try {
    $encodedUsers = json_encode($users, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    $fail('Unable to encode users with attached access tokens.');
}

if (file_put_contents($usersFilePath, $encodedUsers) === false) {
    $fail('Unable to persist users with attached access tokens.');
}
