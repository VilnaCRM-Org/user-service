<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Schema;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Factory\Request\ConfirmPasswordResetRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class ConfirmPasswordResetRequestFactoryTest extends UnitTestCase
{
    public function testGetRequestDefinesPasswordRules(): void
    {
        $factory = new ConfirmPasswordResetRequestFactory();

        $requestBody = $factory->getRequest();
        $this->assertInstanceOf(RequestBody::class, $requestBody);
        $this->assertTrue($requestBody->getRequired());

        $content = $requestBody->getContent();
        $this->assertNotNull($content);

        $jsonMediaType = $content->offsetGet('application/json');
        $this->assertInstanceOf(MediaType::class, $jsonMediaType);

        $jsonSchema = $jsonMediaType->getSchema();
        $this->assertNotNull($jsonSchema);

        $schemaData = $jsonSchema->getArrayCopy();
        $this->assertSame('object', $schemaData['type']);
        $this->assertSame(['token', 'newPassword'], $schemaData['required']);

        /** @var Schema $jsonTokenSchema */
        $jsonTokenSchema = $schemaData['properties']['token'];
        $this->assertInstanceOf(Schema::class, $jsonTokenSchema);
        $this->assertSame('string', $jsonTokenSchema['type']);
        $this->assertSame(255, $jsonTokenSchema['maxLength']);
        $this->assertSame([SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN], $jsonTokenSchema['enum']);

        /** @var Schema $jsonPasswordSchema */
        $jsonPasswordSchema = $schemaData['properties']['newPassword'];
        $this->assertInstanceOf(Schema::class, $jsonPasswordSchema);
        $this->assertSame('string', $jsonPasswordSchema['type']);
        $this->assertSame(8, $jsonPasswordSchema['minLength']);
        $this->assertSame(64, $jsonPasswordSchema['maxLength']);
        $this->assertSame('^(?=.*[0-9])(?=.*[A-Z]).{8,64}$', $jsonPasswordSchema['pattern']);
        $this->assertSame(['passWORD1'], $jsonPasswordSchema['enum']);

        $this->assertSame([
            'token' => SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN,
            'newPassword' => 'passWORD1',
        ], $jsonMediaType->getExample());

        $ldMediaType = $content->offsetGet('application/ld+json');
        $this->assertInstanceOf(MediaType::class, $ldMediaType);

        $ldSchema = $ldMediaType->getSchema();
        $this->assertNotNull($ldSchema);

        $ldSchemaData = $ldSchema->getArrayCopy();
        $this->assertSame('object', $ldSchemaData['type']);
        $this->assertSame(['token', 'newPassword'], $ldSchemaData['required']);

        /** @var Schema $ldTokenSchema */
        $ldTokenSchema = $ldSchemaData['properties']['token'];
        $this->assertInstanceOf(Schema::class, $ldTokenSchema);
        $this->assertSame('string', $ldTokenSchema['type']);
        $this->assertSame(255, $ldTokenSchema['maxLength']);
        $this->assertSame([SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN_LD], $ldTokenSchema['enum']);

        $this->assertSame([
            'token' => SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN_LD,
            'newPassword' => 'passWORD1',
        ], $ldMediaType->getExample());
    }
}
