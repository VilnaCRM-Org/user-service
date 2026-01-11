<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;

final class UserCreatedResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    #[\Override]
    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'User created',
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
            SchemathesisFixtures::CREATE_USER_EMAIL
        );
    }

    public function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            SchemathesisFixtures::CREATE_USER_INITIALS
        );
    }

    public function getIdParam(): Parameter
    {
        return new Parameter(
            'id',
            'string',
            SchemathesisFixtures::CREATE_USER_ID
        );
    }
}
