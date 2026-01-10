<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\OpenApi\Factory\Request\CreateUserRequestFactory;
use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;
use App\Tests\Unit\UnitTestCase;

final class CreateUserRequestFactoryTest extends UnitTestCase
{
    public function testGetRequestDefinesStrongPasswordConstraints(): void
    {
        $factory = new CreateUserRequestFactory();
        $request = $factory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $request);
        $this->assertTrue($request->getRequired());

        $mediaType = $this->assertAndGetMediaType($request);
        $schemaData = $this->assertAndGetSchemaData($mediaType);
        $this->assertPropertyConstraints($schemaData['properties']);
        $this->assertRequestExample($mediaType);
    }

    private function assertAndGetMediaType(RequestBody $request): MediaType
    {
        $content = $request->getContent();
        $this->assertNotNull($content);
        $this->assertTrue($content->offsetExists('application/json'));
        $mediaType = $content->offsetGet('application/json');
        $this->assertInstanceOf(MediaType::class, $mediaType);
        return $mediaType;
    }

    /**
     * @return array<string, array<string, string|int|bool>|string|bool>
     */
    private function assertAndGetSchemaData(MediaType $mediaType): array
    {
        $schema = $mediaType->getSchema();
        $this->assertNotNull($schema);
        $schemaData = $schema->getArrayCopy();
        $this->assertSame('object', $schemaData['type']);
        $this->assertSame(['email', 'initials', 'password'], $schemaData['required']);
        $this->assertFalse($schemaData['additionalProperties']);
        $this->assertFalse($schemaData['unevaluatedProperties']);
        return $schemaData;
    }

    /**
     * @param array<string, array<string, string|int>> $properties
     */
    private function assertPropertyConstraints(array $properties): void
    {
        $this->assertSame('string', $properties['email']['type']);
        $this->assertSame('email', $properties['email']['format']);
        $this->assertSame(255, $properties['email']['maxLength']);
        $this->assertSame('string', $properties['initials']['type']);
        $this->assertSame(255, $properties['initials']['maxLength']);
        $this->assertSame('^(?!\\d).*\\S.*$', $properties['initials']['pattern']);
        $this->assertSame('string', $properties['password']['type']);
        $this->assertSame(8, $properties['password']['minLength']);
        $this->assertSame(64, $properties['password']['maxLength']);
        $this->assertSame('^(?=.*[0-9])(?=.*[A-Z]).{8,64}$', $properties['password']['pattern']);
    }

    private function assertRequestExample(MediaType $mediaType): void
    {
        $this->assertSame([
            'email' => SchemathesisFixtures::CREATE_USER_EMAIL,
            'initials' => SchemathesisFixtures::CREATE_USER_INITIALS,
            'password' => SchemathesisFixtures::CREATE_USER_PASSWORD,
        ], $mediaType->getExample());
    }
}
