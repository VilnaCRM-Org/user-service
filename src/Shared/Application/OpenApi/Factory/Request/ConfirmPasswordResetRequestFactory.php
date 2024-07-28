<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;

final class ConfirmPasswordResetRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    public function getRequest(): RequestBody
    {
        return $this->requestBuilder->build(
            [
                $this->getTokenParam(),
                $this->getNewPasswordParam(),
            ],
            contentType: 'application/merge-patch+json'
        );
    }

    private function getTokenParam(): Parameter
    {
        return new Parameter(
            'token',
            'string',
            'token'
        );
    }

    private function getNewPasswordParam(): Parameter
    {
        return new Parameter(
            'newPassword',
            'string',
            'token'
        );
    }
}
