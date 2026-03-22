<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Schema;
use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;
use ArrayObject;

final class RequestPasswordResetRequestFactory implements AbstractRequestFactory
{
    #[\Override]
    public function getRequest(): RequestBody
    {
        return new RequestBody(
            content: new ArrayObject([
                'application/json' => $this->createPayloadDefinition(),
                'application/ld+json' => $this->createPayloadDefinition(),
            ]),
            required: true
        );
    }

    private function createPayloadDefinition(): MediaType
    {
        return new MediaType(
            schema: $this->buildRequestSchema(),
            example: [
                'email' => SchemathesisFixtures::PASSWORD_RESET_REQUEST_EMAIL,
            ]
        );
    }

    private function buildRequestSchema(): Schema
    {
        $schema = new Schema();
        $schema['type'] = 'object';
        $schema['required'] = ['email'];
        $schema['properties'] = ['email' => $this->buildEmailSchema()];

        return $schema;
    }

    private function buildEmailSchema(): Schema
    {
        $emailSchema = new Schema();
        $emailSchema['type'] = 'string';
        $emailSchema['format'] = 'email';
        $emailSchema['maxLength'] = 255;

        return $emailSchema;
    }
}
