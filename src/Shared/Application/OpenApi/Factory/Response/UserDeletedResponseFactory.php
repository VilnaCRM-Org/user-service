<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;

final class UserDeletedResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    /** @psalm-suppress PossiblyUnusedReturnValue Used by OpenApi decorator */
    #[\Override]
    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'User resource deleted',
            [],
            []
        );
    }
}
