<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;

final class ConfirmUserRequestFactory implements AbstractRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    public function getRequest(): RequestBody
    {
        return $this->requestBuilder->build(
            [
                $this->getTokenParam(),
            ],
            contentType: 'application/merge-patch+json'
        );
    }

    private function getTokenParam(): Parameter
    {
        return new Parameter(
            'token',
            'string',
            SchemathesisFixtures::CONFIRMATION_TOKEN,
            enum: [SchemathesisFixtures::CONFIRMATION_TOKEN]
        );
    }
}
