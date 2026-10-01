<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

use App\Shared\Application\Provider\CurrentTimestampProviderInterface;
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
 * are always used.
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
    public function create(): string
    {
        $this->assertConfigured();
        $issuedAt = $this->timestampProvider->currentTimestamp();
        $signer = new SignatureV4(self::SIGNING_SERVICE, $this->region);
        $presignedRequest = $signer->presign(
            $this->requestFactory->createRequest('GET', $this->connectUrl()),
            ($this->credentialProvider)()->wait(),
            $issuedAt + self::TOKEN_LIFETIME_SECONDS,
            ['start_time' => $issuedAt]
        );

        return substr((string) $presignedRequest->getUri(), strlen(self::REQUEST_SCHEME));
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
