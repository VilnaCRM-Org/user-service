<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\ArrayRequestBuilder;
use App\Shared\Application\OpenApi\Builder\Parameter;

final class CreateBatchRequestFactory implements AbstractRequestFactory
{
    public function __construct(private ArrayRequestBuilder $requestBuilder)
    {
    }

    public function getRequest(): RequestBody
    {
        return $this->requestBuilder->build(
            [
                $this->getEmailParam(),
                $this->getInitialsParam(),
                $this->getPasswordParam(),
            ]
        );
    }

    private function getEmailParam(): Parameter
    {
        return new Parameter(
            'email',
            'string',
            'user@example.com',
            255,
            'email'
        );
    }

    private function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            'Name Surname',
            255
        );
    }

    private function getPasswordParam(): Parameter
    {
        return new Parameter(
            'password',
            'string',
            'passWORD1',
            255
        );
    }
}
