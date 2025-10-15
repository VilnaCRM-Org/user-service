<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Factory\Request\CreateUserRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class CreateUserRequestFactoryTest extends UnitTestCase
{
    public function testGetRequestDefinesStrongPasswordConstraints(): void
    {
        $factory = new CreateUserRequestFactory();

        $request = $factory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $request);
        $this->assertTrue($request->getRequired());

        $content = $request->getContent();
        $this->assertNotNull($content);
        $this->assertTrue($content->offsetExists('application/json'));

        $mediaType = $content->offsetGet('application/json');
        $this->assertInstanceOf(MediaType::class, $mediaType);

        $schema = $mediaType->getSchema();
        $this->assertNotNull($schema);

        $schemaData = $schema->getArrayCopy();
        $this->assertSame('object', $schemaData['type']);
        $this->assertSame(['email', 'initials', 'password'], $schemaData['required']);
        $this->assertFalse($schemaData['additionalProperties']);
        $this->assertFalse($schemaData['unevaluatedProperties']);

        $properties = $schemaData['properties'];

        $this->assertSame('string', $properties['email']['type']);
        $this->assertSame('email', $properties['email']['format']);
        $this->assertSame(255, $properties['email']['maxLength']);

        $this->assertSame('string', $properties['initials']['type']);
        $this->assertSame(255, $properties['initials']['maxLength']);
        $this->assertSame('^\\S+$', $properties['initials']['pattern']);

        $this->assertSame('string', $properties['password']['type']);
        $this->assertSame(8, $properties['password']['minLength']);
        $this->assertSame(64, $properties['password']['maxLength']);
        $this->assertSame('^(?=.*[0-9])(?=.*[A-Z]).{8,64}$', $properties['password']['pattern']);

        $this->assertSame(
            [
                'email' => SchemathesisFixtures::CREATE_USER_EMAIL,
                'initials' => SchemathesisFixtures::CREATE_USER_INITIALS,
                'password' => SchemathesisFixtures::CREATE_USER_PASSWORD,
            ],
            $mediaType->getExample()
        );
    }
}
