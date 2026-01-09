<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Schema;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use ArrayObject;

final class CreateUserRequestFactory implements AbstractRequestFactory
{
    #[\Override]
    public function getRequest(): RequestBody
    {
        $schema = $this->createSchema();

        return new RequestBody(
            content: new ArrayObject([
                'application/json' => new MediaType(
                    schema: $schema,
                    example: $this->createExample()
                ),
            ]),
            required: true
        );
    }

    private function createSchema(): Schema
    {
        $schema = new Schema();
        $schema['type'] = 'object';
        $schema['required'] = ['email', 'initials', 'password'];
        $schema['properties'] = [
            'email' => $this->createEmailSchema(),
            'initials' => $this->createInitialsSchema(),
            'password' => $this->createPasswordSchema(),
        ];
        $schema['additionalProperties'] = false;
        $schema['unevaluatedProperties'] = false;

        return $schema;
    }

    private function createEmailSchema(): Schema
    {
        $schema = new Schema();
        $schema['type'] = 'string';
        $schema['format'] = 'email';
        $schema['maxLength'] = 255;

        return $schema;
    }

    private function createInitialsSchema(): Schema
    {
        $schema = new Schema();
        $schema['type'] = 'string';
        $schema['maxLength'] = 255;
        $schema['pattern'] = '^(?!\\d).*\\S.*$';

        return $schema;
    }

    private function createPasswordSchema(): Schema
    {
        $schema = new Schema();
        $schema['type'] = 'string';
        $schema['minLength'] = 8;
        $schema['maxLength'] = 64;
        $schema['pattern'] = '^(?=.*[0-9])(?=.*[A-Z]).{8,64}$';

        return $schema;
    }

    /**
     * @return array{email: string, initials: string, password: string}
     */
    private function createExample(): array
    {
        return [
            'email' => SchemathesisFixtures::CREATE_USER_EMAIL,
            'initials' => SchemathesisFixtures::CREATE_USER_INITIALS,
            'password' => SchemathesisFixtures::CREATE_USER_PASSWORD,
        ];
    }
}
