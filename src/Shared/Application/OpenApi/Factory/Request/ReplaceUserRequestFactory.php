<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Builder\Requirement;

final class ReplaceUserRequestFactory implements AbstractRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    #[\Override]
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
            'update-user@example.com',
            255,
            'email'
        );
    }

    private function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            'UpdateUser',
            255,
            null,
            Requirement::REQUIRED,
            '^\\S+$'
        );
    }

    private function getOldPasswordParam(): Parameter
    {
        return new Parameter(
            'oldPassword',
            'string',
            'Password1!',
            64,
            null,
            Requirement::REQUIRED,
            '^(?=.*[0-9])(?=.*[A-Z]).{8,64}$'
        );
    }

    private function getNewPasswordParam(): Parameter
    {
        return new Parameter(
            'newPassword',
            'string',
            'Password1!',
            64,
            null,
            Requirement::REQUIRED,
            '^(?=.*[0-9])(?=.*[A-Z]).{8,64}$'
        );
    }
}
