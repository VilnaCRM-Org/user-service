<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ArrayResponseBuilder;
use App\Shared\Application\OpenApi\Builder\Parameter;

final class UsersReturnedFactory implements AbstractResponseFactory
{
    public function __construct(private ArrayResponseBuilder $responseBuilder)
    {
    }

    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'Users returned',
            [
                $this->getConfirmedParam(),
                $this->getEmailParam(),
                $this->getInitialsParam(),
                $this->getIdParam(),
            ],
            []
        );
    }

    public function getConfirmedParam(): Parameter
    {
        return new Parameter(
            'confirmed',
            'boolean',
            false
        );
    }

    public function getEmailParam(): Parameter
    {
        return new Parameter(
            'email',
            'string',
            'user@example.com'
        );
    }

    public function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            'Name Surname'
        );
    }

    public function getIdParam(): Parameter
    {
        return new Parameter(
            'id',
            'string',
            '018dd6ba-e901-7a8c-b27d-65d122caca6b'
        );
    }
}
