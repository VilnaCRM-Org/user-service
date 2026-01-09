<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\ResponseBuilder;
use App\Shared\Application\OpenApi\ValueObject\Parameter;

final class UserReturnedResponseFactory implements AbstractResponseFactory
{
    public function __construct(private ResponseBuilder $responseBuilder)
    {
    }

    /** @psalm-suppress PossiblyUnusedReturnValue Used by OpenApi decorator */
    #[\Override]
    public function getResponse(): Response
    {
        return $this->responseBuilder->build(
            'User returned',
            [
                $this->getConfirmedParam(),
                $this->getEmailParam(),
                $this->getInitialsParam(),
                $this->getIdParam(),
            ],
            []
        );
    }

    private function getConfirmedParam(): Parameter
    {
        return new Parameter(
            'confirmed',
            'boolean',
            false
        );
    }

    private function getEmailParam(): Parameter
    {
        return new Parameter(
            'email',
            'string',
            SchemathesisFixtures::USER_EMAIL
        );
    }

    private function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            SchemathesisFixtures::USER_INITIALS
        );
    }

    private function getIdParam(): Parameter
    {
        return new Parameter(
            'id',
            'string',
            SchemathesisFixtures::USER_ID
        );
    }
}
