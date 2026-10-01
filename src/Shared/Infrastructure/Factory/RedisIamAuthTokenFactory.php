<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
use App\Shared\Infrastructure\Adapter\RedisIamAuthToken;
use Aws\Credentials\CredentialsInterface;
use Aws\Signature\SignatureV4;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * Creates an ElastiCache IAM authentication token: a SigV4 query-string
 * presigned GET http://<replication-group-id>/?Action=connect&User=<user-id>
 * for the service "elasticache", valid for 900 seconds, without the scheme.
 * The PSR-7 URI lower-cases the host, so the replication-group id is signed
 * in lower case as ElastiCache requires.
 * Credentials are resolved for every token so rotated task-role credentials
 * are always used. A token stops being accepted when the credentials that
 * signed it expire, so its validity is the shorter of 900 seconds and the
 * credentials' expiry (the AWS SDK refreshes cached credentials only within
 * 60 seconds of their expiry).
 * The request is built with Symfony's Psr18Client (a PSR-17 request factory),
 * so symfony/http-client is a direct requirement in composer.json.
 */
final readonly class RedisIamAuthTokenFactory implements RedisIamAuthTokenFactoryInterface
{
    public const TOKEN_LIFETIME_SECONDS = 900;

    private const SIGNING_SERVICE = 'elasticache';
    private const REQUEST_SCHEME = 'http://';

    public function __construct(
        private Psr18Client $requestFactory,
        private \Closure $credentialProvider,
        private CurrentTimestampProviderInterface $timestampProvider,
        private string $replicationGroupId,
        private string $userId,
        private string $region
    ) {
    }

    #[\Override]
    public function create(): RedisIamAuthToken
    {
        $this->assertConfigured();
        $issuedAt = $this->timestampProvider->currentTimestamp();
        $credentials = ($this->credentialProvider)()->wait();
        $signer = new SignatureV4(self::SIGNING_SERVICE, $this->region);
        $presignedRequest = $signer->presign(
            $this->requestFactory->createRequest('GET', $this->connectUrl()),
            $credentials,
            $issuedAt + self::TOKEN_LIFETIME_SECONDS,
            ['start_time' => $issuedAt]
        );

        return new RedisIamAuthToken(
            substr((string) $presignedRequest->getUri(), strlen(self::REQUEST_SCHEME)),
            $this->validUntil($issuedAt, $credentials)
        );
    }

    private function validUntil(int $issuedAt, CredentialsInterface $credentials): int
    {
        $tokenExpiry = $issuedAt + self::TOKEN_LIFETIME_SECONDS;
        $credentialsExpiry = $credentials->getExpiration();

        if ($credentialsExpiry === null) {
            return $tokenExpiry;
        }

        return min($tokenExpiry, $credentialsExpiry);
    }

    private function connectUrl(): string
    {
        return sprintf(
            '%s%s/?Action=connect&User=%s',
            self::REQUEST_SCHEME,
            $this->replicationGroupId,
            rawurlencode($this->userId)
        );
    }

    private function assertConfigured(): void
    {
        $this->assertNotEmpty($this->replicationGroupId, 'REDIS_REPLICATION_GROUP_ID');
        $this->assertNotEmpty($this->userId, 'REDIS_IAM_USER_ID');
        $this->assertNotEmpty($this->region, 'AWS_REGION');
    }

    private function assertNotEmpty(string $value, string $variable): void
    {
        if ($value === '') {
            throw new InvalidArgumentException(
                sprintf('Redis IAM authentication requires %s.', $variable)
            );
        }
    }
}
