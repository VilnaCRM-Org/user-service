<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;

final class ConfirmUserRequestFactory implements AbstractRequestFactory
{
    public function __construct(private RequestBuilder $requestBuilder)
    {
    }

    #[\Override]
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
