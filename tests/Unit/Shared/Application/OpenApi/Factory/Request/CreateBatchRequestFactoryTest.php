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

        $content = $requestBody->getContent();
        $this->assertNotNull($content);
        $this->assertTrue($content->offsetExists('application/json'));

        $mediaType = $content->offsetGet('application/json');
        $this->assertInstanceOf(MediaType::class, $mediaType);

        $schema = $mediaType->getSchema();
        $this->assertNotNull($schema);

        $schemaData = $schema->getArrayCopy();
        $this->assertSame('object', $schemaData['type']);
        $this->assertSame(['users'], $schemaData['required']);

        /** @var Schema $usersSchema */
        $usersSchema = $schemaData['properties']['users'];
        $this->assertInstanceOf(Schema::class, $usersSchema);
        $this->assertSame('array', $usersSchema['type']);
        $this->assertSame(1, $usersSchema['minItems']);
        $this->assertTrue($usersSchema['uniqueItems']);

        /** @var Schema $itemSchema */
        $itemSchema = $usersSchema['items'];
        $this->assertInstanceOf(Schema::class, $itemSchema);
        $this->assertSame(['email', 'initials', 'password'], $itemSchema['required']);
        $this->assertFalse($itemSchema['additionalProperties']);
        $this->assertFalse($itemSchema['unevaluatedProperties']);

        $itemProperties = $itemSchema['properties'];

        $this->assertSame('string', $itemProperties['email']['type']);
        $this->assertSame('email', $itemProperties['email']['format']);
        $this->assertSame(255, $itemProperties['email']['maxLength']);

        $this->assertSame('string', $itemProperties['initials']['type']);
        $this->assertSame(255, $itemProperties['initials']['maxLength']);
        $this->assertSame('^\\S+$', $itemProperties['initials']['pattern']);

        $this->assertSame('string', $itemProperties['password']['type']);
        $this->assertSame(8, $itemProperties['password']['minLength']);
        $this->assertSame(64, $itemProperties['password']['maxLength']);
        $this->assertSame('^(?=.*[0-9])(?=.*[A-Z]).{8,64}$', $itemProperties['password']['pattern']);

        $this->assertSame(
            [
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
            ],
            $mediaType->getExample()
        );
    }
}
