<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Schema;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use ArrayObject;

final class ConfirmPasswordResetRequestFactory implements AbstractRequestFactory
{
    #[\Override]
    public function getRequest(): RequestBody
    {
        return new RequestBody(
            content: new ArrayObject([
                'application/json' => $this->createMediaType(
                    SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN
                ),
                'application/ld+json' => $this->createMediaType(
                    SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN_LD
                ),
            ]),
            required: true
        );
    }

    private function createMediaType(string $tokenValue): MediaType
    {
        return new MediaType(
            schema: $this->createSchema($tokenValue),
            example: [
                'token' => $tokenValue,
                'newPassword' => 'passWORD1',
            ]
        );
    }

    private function createSchema(string $tokenValue): Schema
    {
        $tokenSchema = new Schema();
        $tokenSchema['type'] = 'string';
        $tokenSchema['maxLength'] = 255;
        $tokenSchema['enum'] = [$tokenValue];

        $passwordSchema = new Schema();
        $passwordSchema['type'] = 'string';
        $passwordSchema['minLength'] = 8;
        $passwordSchema['maxLength'] = 64;
        $passwordSchema['pattern'] = '^(?=.*[0-9])(?=.*[A-Z]).{8,64}$';
        $passwordSchema['enum'] = ['passWORD1'];

        $schema = new Schema();
        $schema['type'] = 'object';
        $schema['required'] = ['token', 'newPassword'];
        $schema['properties'] = [
            'token' => $tokenSchema,
            'newPassword' => $passwordSchema,
        ];

        return $schema;
    }
}
