<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Cleaner;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Transformer\ResponseContentTransformer;
use ArrayObject;

final class OperationNoContentCleaner
{
    public function __construct(
        private readonly ResponseContentTransformer $responseContentTransformer
    ) {
    }

    public function clean(?Operation $operation): ?Operation
    {
        if ($operation === null || !\is_array($operation->getResponses())) {
            return $operation;
        }

        return $operation->withResponses(
            $this->cleanResponses($operation->getResponses())
        );
    }

    /**
     * @param array<string|int,
     *     Response|ArrayObject|array|string|int|bool|null> $responses
     *
     * @return array<string|int,
     *     Response|ArrayObject|array|string|int|bool|null>
     */
    private function cleanResponses(array $responses): array
    {
        foreach ($responses as $status => $response) {
            $responses[$status] = $this->responseContentTransformer->transform(
                $response,
                (string) $status
            );
        }

        return $responses;
    }
}
