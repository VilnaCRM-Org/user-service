<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\ArrayResponseBuilder;
use App\Shared\Application\OpenApi\Builder\Parameter;

final class UsersBatchCreatedResponseFactory implements AbstractResponseFactory
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
            SchemathesisFixtures::CREATE_BATCH_FIRST_USER_EMAIL
        );
    }

    private function getInitialsParam(): Parameter
    {
        return new Parameter(
            'initials',
            'string',
            SchemathesisFixtures::CREATE_BATCH_FIRST_USER_INITIALS
        );
    }

    private function getIdParam(): Parameter
    {
        return new Parameter(
            'id',
            'string',
            SchemathesisFixtures::CREATE_BATCH_FIRST_USER_ID
        );
    }
}
