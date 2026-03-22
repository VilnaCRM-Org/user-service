<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Schema;
use App\Shared\Application\OpenApi\Factory\Request\ConfirmPasswordResetRequestFactory;
use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;
use App\Tests\Unit\UnitTestCase;

final class ConfirmPasswordResetRequestFactoryTest extends UnitTestCase
{
    public function testGetRequestDefinesPasswordRules(): void
    {
        $factory = new ConfirmPasswordResetRequestFactory();
        $requestBody = $factory->getRequest();

        $this->assertBasicRequestBodyStructure($requestBody);
        $content = $requestBody->getContent();

        $this->assertJsonMediaTypeConfiguration($content);
        $this->assertLdMediaTypeConfiguration($content);
    }

    private function assertBasicRequestBodyStructure(RequestBody $requestBody): void
    {
        $this->assertInstanceOf(RequestBody::class, $requestBody);
        $this->assertTrue($requestBody->getRequired());
    }

    /**
     * @param \ArrayAccess<string, MediaType> $content
     */
    private function assertJsonMediaTypeConfiguration(\ArrayAccess $content): void
    {
        $jsonMediaType = $content->offsetGet('application/json');
        $this->assertInstanceOf(MediaType::class, $jsonMediaType);

        $jsonSchema = $jsonMediaType->getSchema();
        $this->assertNotNull($jsonSchema);
        $schemaData = $jsonSchema->getArrayCopy();

        $this->assertJsonSchemaStructure($schemaData);
        $this->assertJsonTokenSchema($schemaData['properties']['token']);
        $this->assertJsonPasswordSchema($schemaData['properties']['newPassword']);
        $this->assertJsonExample($jsonMediaType);
    }

    /**
     * @param array{type: string, required: array<int, string>} $schemaData
     */
    private function assertJsonSchemaStructure(array $schemaData): void
    {
        $this->assertSame('object', $schemaData['type']);
        $this->assertSame(['token', 'newPassword'], $schemaData['required']);
    }

    private function assertJsonTokenSchema(Schema $tokenSchema): void
    {
        $this->assertInstanceOf(Schema::class, $tokenSchema);
        $this->assertSame('string', $tokenSchema['type']);
        $this->assertSame(255, $tokenSchema['maxLength']);
        $this->assertSame(
            [SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN],
            $tokenSchema['enum']
        );
    }

    private function assertJsonPasswordSchema(Schema $passwordSchema): void
    {
        $this->assertInstanceOf(Schema::class, $passwordSchema);
        $this->assertSame('string', $passwordSchema['type']);
        $this->assertSame(8, $passwordSchema['minLength']);
        $this->assertSame(64, $passwordSchema['maxLength']);
        $this->assertSame('^(?=.*[0-9])(?=.*[A-Z]).{8,64}$', $passwordSchema['pattern']);
        $this->assertSame(['passWORD1'], $passwordSchema['enum']);
    }

    private function assertJsonExample(MediaType $mediaType): void
    {
        $this->assertSame([
            'token' => SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN,
            'newPassword' => 'passWORD1',
        ], $mediaType->getExample());
    }

    /**
     * @param \ArrayAccess<string, MediaType> $content
     */
    private function assertLdMediaTypeConfiguration(\ArrayAccess $content): void
    {
        $ldMediaType = $content->offsetGet('application/ld+json');
        $this->assertInstanceOf(MediaType::class, $ldMediaType);

        $ldSchema = $ldMediaType->getSchema();
        $this->assertNotNull($ldSchema);
        $ldSchemaData = $ldSchema->getArrayCopy();

        $this->assertLdSchemaStructure($ldSchemaData);
        $this->assertLdTokenSchema($ldSchemaData['properties']['token']);
        $this->assertLdExample($ldMediaType);
    }

    /**
     * @param array{type: string, required: array<int, string>} $ldSchemaData
     */
    private function assertLdSchemaStructure(array $ldSchemaData): void
    {
        $this->assertSame('object', $ldSchemaData['type']);
        $this->assertSame(['token', 'newPassword'], $ldSchemaData['required']);
    }

    private function assertLdTokenSchema(Schema $ldTokenSchema): void
    {
        $this->assertInstanceOf(Schema::class, $ldTokenSchema);
        $this->assertSame('string', $ldTokenSchema['type']);
        $this->assertSame(255, $ldTokenSchema['maxLength']);
        $this->assertSame(
            [SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN_LD],
            $ldTokenSchema['enum']
        );
    }

    private function assertLdExample(MediaType $mediaType): void
    {
        $this->assertSame([
            'token' => SchemathesisFixtures::PASSWORD_RESET_CONFIRM_TOKEN_LD,
            'newPassword' => 'passWORD1',
        ], $mediaType->getExample());
    }
}
