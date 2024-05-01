<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;

final class UpdateUserRequestFactory implements AbstractRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    public function getRequest(): RequestBody
    {
        return $this->requestBuilder->build(
            [
                $this->getEmailParam(),
                $this->getInitialsParam(),
                $this->getOldPasswordParam(),
                $this->getNewPasswordParam(),
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
            'email',
            required: false
        );
    }

    private function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            'Name Surname',
            255,
            required: false
        );
    }

    private function getOldPasswordParam(): Parameter
    {
        return new Parameter(
            'oldPassword',
            'string',
            'passWORD1',
            255
        );
    }

    private function getNewPasswordParam(): Parameter
    {
        return new Parameter(
            'newPassword',
            'string',
            'PASSword2',
            255,
            required: false
        );
    }
}
