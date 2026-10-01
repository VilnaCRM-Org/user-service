<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Provider;

use App\Shared\Application\Provider\KmsEndpointProviderInterface;
use Aws\Kms\KmsClient;

/**
 * Reports the endpoint of the configured AWS KMS client, including endpoints
 * set by configuration or by AWS_ENDPOINT_URL[_KMS].
 */
final readonly class AwsKmsEndpointProvider implements KmsEndpointProviderInterface
{
    public function __construct(private KmsClient $kmsClient)
    {
    }

    #[\Override]
    public function endpoint(): string
    {
        return (string) $this->kmsClient->getEndpoint();
    }
}
