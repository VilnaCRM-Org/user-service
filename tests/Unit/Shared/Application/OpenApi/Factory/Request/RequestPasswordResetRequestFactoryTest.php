<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Schema;
use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Factory\Request\RequestPasswordResetRequestFactory;
use App\Tests\Unit\UnitTestCase;

final class RequestPasswordResetRequestFactoryTest extends UnitTestCase
{
    public function testGetRequestDefinesEmailExample(): void
    {
        $factory = new RequestPasswordResetRequestFactory();
        $requestBody = $factory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $requestBody);
        $this->assertTrue($requestBody->getRequired());

        $content = $this->assertAndGetContent($requestBody);
        $this->assertEmailSchemaAndExample($content);
    }

    /**
     * @return \ArrayObject<string, MediaType>
     */
    private function assertAndGetContent(RequestBody $requestBody): \ArrayObject
    {
        $content = $requestBody->getContent();
        $this->assertNotNull($content);
        $this->assertTrue($content->offsetExists('application/json'));
        $this->assertTrue($content->offsetExists('application/ld+json'));
        return $content;
    }

    /**
     * @param \ArrayObject<string, MediaType> $content
     */
    private function assertEmailSchemaAndExample(\ArrayObject $content): void
    {
        $mediaType = $content->offsetGet('application/json');
        $this->assertInstanceOf(MediaType::class, $mediaType);

        /** @var Schema $schema */
        $schema = $mediaType->getSchema();
        $this->assertInstanceOf(Schema::class, $schema);

        $schemaData = $schema->getArrayCopy();
        $this->assertSame('object', $schemaData['type']);
        $this->assertSame(['email'], $schemaData['required']);

        $this->assertEmailProperty($schemaData);
        $expectedExample = ['email' => SchemathesisFixtures::PASSWORD_RESET_REQUEST_EMAIL];
        $this->assertSame($expectedExample, $mediaType->getExample());
        $this->assertEquals($mediaType, $content->offsetGet('application/ld+json'));
    }

    /**
     * @param array<string, Schema|string|array<string>> $schemaData
     */
    private function assertEmailProperty(array $schemaData): void
    {
        /** @var Schema $emailSchema */
        $emailSchema = $schemaData['properties']['email'];
        $this->assertInstanceOf(Schema::class, $emailSchema);
        $this->assertSame('string', $emailSchema['type']);
        $this->assertSame('email', $emailSchema['format']);
        $this->assertSame(255, $emailSchema['maxLength']);
    }
}
