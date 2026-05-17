<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Transformer\PasskeyCredentialRequestSchemaTransformer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class PasskeyCredentialRequestSchemaTransformerTest extends UnitTestCase
{
    public function testTransformDocumentsRequiredBrowserCredentialPayload(): void
    {
        $resultSchemas = $this->transformedCredentialSchemas();

        $this->assertAttestationCredentialSchema($resultSchemas->offsetGet(
            'EmptyResponse.PasskeyRegistrationCompleteDto'
        ));
        $this->assertAssertionCredentialSchema($resultSchemas->offsetGet(
            'EmptyResponse.PasskeySignInCompleteDto'
        ));
        $this->assertAttestationCredentialSchema($resultSchemas->offsetGet(
            'EmptyResponse.PasskeySignUpCompleteDto'
        ));
    }

    public function testTransformSkipsInvalidSchemaValues(): void
    {
        $schemas = new ArrayObject([
            'EmptyResponse.PasskeyRegistrationCompleteDto' => 'invalid',
            'EmptyResponse.PasskeySignUpCompleteDto' => [
                'required' => 'invalid',
                'properties' => 'invalid',
            ],
        ]);
        $result = (new PasskeyCredentialRequestSchemaTransformer())
            ->transform($this->createOpenApi($schemas));

        $resultSchemas = $result->getComponents()?->getSchemas();
        self::assertSame('invalid', $resultSchemas?->offsetGet(
            'EmptyResponse.PasskeyRegistrationCompleteDto'
        ));
        $this->assertSchemaCreatedFromInvalidCollections($resultSchemas?->offsetGet(
            'EmptyResponse.PasskeySignUpCompleteDto'
        ));
    }

    public function testTransformIgnoresOpenApiWithoutSchemas(): void
    {
        $openApi = new OpenApi($this->createMock(Info::class), [], new Paths());

        self::assertSame(
            $openApi,
            (new PasskeyCredentialRequestSchemaTransformer())->transform($openApi)
        );
    }

    private function credentialSchemas(): ArrayObject
    {
        return new ArrayObject([
            'EmptyResponse.PasskeyRegistrationCompleteDto' => [
                'type' => 'object',
            ],
            'EmptyResponse.PasskeySignInCompleteDto' => $this->signInCompleteSchema(),
            'EmptyResponse.PasskeySignUpCompleteDto' => [
                'type' => 'object',
            ],
        ]);
    }

    private function transformedCredentialSchemas(): ArrayObject
    {
        $result = (new PasskeyCredentialRequestSchemaTransformer())
            ->transform($this->createOpenApi($this->credentialSchemas()));
        $resultSchemas = $result->getComponents()?->getSchemas();
        self::assertInstanceOf(ArrayObject::class, $resultSchemas);

        return $resultSchemas;
    }

    private function signInCompleteSchema(): ArrayObject
    {
        return new ArrayObject([
            'type' => 'object',
            'required' => new ArrayObject(['challengeId']),
            'properties' => new ArrayObject([
                'challengeId' => ['type' => 'string'],
                'credential' => ['type' => 'array'],
            ]),
        ]);
    }

    /**
     * @param array<string, scalar|array|null> $schema
     */
    private function assertAttestationCredentialSchema(array $schema): void
    {
        $this->assertCredentialSchema($schema);
        self::assertSame(
            ['attestationObject', 'clientDataJSON'],
            $this->credentialResponseSchema($schema)['required']
        );
        self::assertArrayHasKey(
            'attestationObject',
            $this->credentialResponseSchema($schema)['properties']
        );
        self::assertArrayNotHasKey(
            'signature',
            $this->credentialResponseSchema($schema)['properties']
        );
    }

    /**
     * @param array<string, scalar|array|null> $schema
     */
    private function assertAssertionCredentialSchema(array $schema): void
    {
        $this->assertCredentialSchema($schema);
        self::assertSame(
            ['authenticatorData', 'clientDataJSON', 'signature'],
            $this->credentialResponseSchema($schema)['required']
        );
        self::assertArrayHasKey(
            'signature',
            $this->credentialResponseSchema($schema)['properties']
        );
        self::assertArrayNotHasKey(
            'attestationObject',
            $this->credentialResponseSchema($schema)['properties']
        );
    }

    /**
     * @param array<string, scalar|array|null> $schema
     */
    private function assertCredentialSchema(array $schema): void
    {
        self::assertSame(['challengeId', 'credential'], $schema['required']);
        self::assertSame('object', $schema['properties']['credential']['type']);
        self::assertTrue($schema['properties']['credential']['additionalProperties']);
        self::assertSame(
            ['id', 'rawId', 'response', 'type'],
            $schema['properties']['credential']['required']
        );
        self::assertArrayHasKey('response', $schema['properties']['credential']['properties']);
    }

    /**
     * @param array<string, scalar|array|null> $schema
     *
     * @return array<string, scalar|array|null>
     */
    private function credentialResponseSchema(array $schema): array
    {
        $responseSchema = $schema['properties']['credential']['properties']['response'];
        self::assertIsArray($responseSchema);

        return $responseSchema;
    }

    /**
     * @param array<string, scalar|array|null> $schema
     */
    private function assertSchemaCreatedFromInvalidCollections(array $schema): void
    {
        self::assertSame(['challengeId', 'credential'], $schema['required']);
        self::assertSame(['credential'], array_keys($schema['properties']));
    }

    private function createOpenApi(ArrayObject $schemas): OpenApi
    {
        return new OpenApi(
            $this->createMock(Info::class),
            [],
            new Paths(),
            components: new Components($schemas)
        );
    }
}
