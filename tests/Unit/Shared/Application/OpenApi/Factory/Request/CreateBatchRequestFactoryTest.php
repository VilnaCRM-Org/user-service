<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Schema;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Factory\Request\CreateBatchRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class CreateBatchRequestFactoryTest extends UnitTestCase
{
    public function testGetRequestEnforcesBatchItemSchema(): void
    {
        $factory = new CreateBatchRequestFactory();
        $requestBody = $factory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $requestBody);
        $this->assertTrue($requestBody->getRequired());

        $mediaType = $this->assertAndGetMediaType($requestBody);
        $schemaData = $this->assertAndGetSchemaData($mediaType);
        $this->assertUsersSchema($schemaData);
        $this->assertBatchExample($mediaType);
    }

    private function assertAndGetMediaType(RequestBody $requestBody): MediaType
    {
        $content = $requestBody->getContent();
        $this->assertNotNull($content);
        $this->assertTrue($content->offsetExists('application/json'));
        $mediaType = $content->offsetGet('application/json');
        $this->assertInstanceOf(MediaType::class, $mediaType);
        return $mediaType;
    }

    /**
     * @return array<string, Schema|string|array<string>>
     */
    private function assertAndGetSchemaData(MediaType $mediaType): array
    {
        $schema = $mediaType->getSchema();
        $this->assertNotNull($schema);
        $schemaData = $schema->getArrayCopy();
        $this->assertSame('object', $schemaData['type']);
        $this->assertSame(['users'], $schemaData['required']);
        return $schemaData;
    }

    /**
     * @param array<string, Schema|string|array<string>> $schemaData
     */
    private function assertUsersSchema(array $schemaData): void
    {
        /** @var Schema $usersSchema */
        $usersSchema = $schemaData['properties']['users'];
        $this->assertInstanceOf(Schema::class, $usersSchema);
        $this->assertSame('array', $usersSchema['type']);
        $this->assertSame(1, $usersSchema['minItems']);
        $this->assertTrue($usersSchema['uniqueItems']);

        $this->assertItemSchema($usersSchema['items']);
    }

    private function assertItemSchema(Schema $itemSchema): void
    {
        $this->assertInstanceOf(Schema::class, $itemSchema);
        $this->assertSame(['email', 'initials', 'password'], $itemSchema['required']);
        $this->assertFalse($itemSchema['additionalProperties']);
        $this->assertFalse($itemSchema['unevaluatedProperties']);

        $this->assertItemProperties($itemSchema['properties']);
    }

    /**
     * @param array<string, array<string, string|int>> $itemProperties
     */
    private function assertItemProperties(array $itemProperties): void
    {
        $this->assertSame('string', $itemProperties['email']['type']);
        $this->assertSame('email', $itemProperties['email']['format']);
        $this->assertSame(255, $itemProperties['email']['maxLength']);
        $this->assertSame('string', $itemProperties['initials']['type']);
        $this->assertSame(255, $itemProperties['initials']['maxLength']);
        $this->assertSame('^(?!\\d).*\\S.*$', $itemProperties['initials']['pattern']);
        $this->assertSame('string', $itemProperties['password']['type']);
        $this->assertSame(8, $itemProperties['password']['minLength']);
        $this->assertSame(64, $itemProperties['password']['maxLength']);
        $passwordPattern = '^(?=.*[0-9])(?=.*[A-Z]).{8,64}$';
        $this->assertSame($passwordPattern, $itemProperties['password']['pattern']);
    }

    private function assertBatchExample(MediaType $mediaType): void
    {
        $this->assertSame([
            'users' => [
                [
                    'email' => SchemathesisFixtures::CREATE_BATCH_FIRST_USER_EMAIL,
                    'initials' => SchemathesisFixtures::CREATE_BATCH_FIRST_USER_INITIALS,
                    'password' => SchemathesisFixtures::CREATE_BATCH_FIRST_USER_PASSWORD,
                ],
                [
                    'email' => SchemathesisFixtures::CREATE_BATCH_SECOND_USER_EMAIL,
                    'initials' => SchemathesisFixtures::CREATE_BATCH_SECOND_USER_INITIALS,
                    'password' => SchemathesisFixtures::CREATE_BATCH_SECOND_USER_PASSWORD,
                ],
            ],
        ], $mediaType->getExample());
    }
}
