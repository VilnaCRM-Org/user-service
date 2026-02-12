<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Schema;
use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;
use ArrayObject;

final class CreateBatchRequestFactory implements AbstractRequestFactory
{
    #[\Override]
    public function getRequest(): RequestBody
    {
        $schema = $this->createRootSchema();

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

    private function createRootSchema(): Schema
    {
        $schema = new Schema();
        $schema['type'] = 'object';
        $schema['required'] = ['users'];
        $schema['properties'] = [
            'users' => $this->createUsersSchema(),
        ];

        return $schema;
    }

    private function createUsersSchema(): Schema
    {
        $schema = new Schema();
        $schema['type'] = 'array';
        $schema['minItems'] = 1;
        $schema['uniqueItems'] = true;
        $schema['items'] = $this->createUserItemSchema();

        return $schema;
    }

    private function createUserItemSchema(): Schema
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
     * @return string[][][]
     *
     * @psalm-return array{users: list{array{email: string, initials: string, password: string}, array{email: string, initials: string, password: string}}}
     */
    private function createExample(): array
    {
        return [
            'users' => [
                $this->createExampleUser(
                    SchemathesisFixtures::CREATE_BATCH_FIRST_USER_EMAIL,
                    SchemathesisFixtures::CREATE_BATCH_FIRST_USER_INITIALS,
                    SchemathesisFixtures::CREATE_BATCH_FIRST_USER_PASSWORD,
                ),
                $this->createExampleUser(
                    SchemathesisFixtures::CREATE_BATCH_SECOND_USER_EMAIL,
                    SchemathesisFixtures::CREATE_BATCH_SECOND_USER_INITIALS,
                    SchemathesisFixtures::CREATE_BATCH_SECOND_USER_PASSWORD,
                ),
            ],
        ];
    }

    /**
     * @return array{email: string, initials: string, password: string}
     */
    private function createExampleUser(
        string $email,
        string $initials,
        string $password
    ): array {
        return [
            'email' => $email,
            'initials' => $initials,
            'password' => $password,
        ];
    }
}
