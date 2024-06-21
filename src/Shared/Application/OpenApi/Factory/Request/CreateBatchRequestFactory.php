<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;

final class CreateBatchRequestFactory implements AbstractRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    public function getRequest(): RequestBody
    {
        return $this->requestBuilder->build(
            [
                new Parameter(
                    'users',
                    'array',
                    [
                        [
                            'email' => 'user@example.com',
                            'initials' => 'Name Surname',
                            'password' => 'passWORD1',
                        ],
                    ],
                ),
            ]
        );
    }
}
