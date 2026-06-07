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

    public function testTransformDocumentsWebAuthnLevelThreeRegistrationResponseJson(): void
    {
        $this->assertWebAuthnLevelThreeRegistrationResponseJson(
            $this->registrationCompleteSchema()
        );
    }

    public function testTransformDocumentsPasskeySignUpOptionsValidationSchema(): void
    {
        $resultSchemas = $this->transformedCredentialSchemas();
        $schema = $resultSchemas->offsetGet('EmptyResponse.PasskeySignUpOptionsDto');

        self::assertIsArray($schema);
        self::assertSame(
            '^(?!\\d).*\\S.*$',
            $schema['properties']['initials']['pattern']
        );
    }

    public function testTransformDocumentsPasskeySignUpOptionsArrayObjectValidationSchema(): void
    {
        $schemas = new ArrayObject([
            'EmptyResponse.PasskeySignUpOptionsDto' => [
                'type' => 'object',
                'properties' => [
                    'initials' => new ArrayObject([
                        'type' => 'string',
                    ]),
                ],
            ],
        ]);
        $result = (new PasskeyCredentialRequestSchemaTransformer())
            ->transform($this->createOpenApi($schemas));
        $schema = $result->getComponents()?->getSchemas()?->offsetGet(
            'EmptyResponse.PasskeySignUpOptionsDto'
        );

        self::assertIsArray($schema);
        self::assertSame(
            '^(?!\\d).*\\S.*$',
            $schema['properties']['initials']['pattern']
        );
    }

    public function testTransformSkipsInvalidSchemaValues(): void
    {
        $schemas = new ArrayObject([
            'EmptyResponse.PasskeyRegistrationCompleteDto' => 'invalid',
            'EmptyResponse.PasskeySignUpCompleteDto' => [
                'required' => 'invalid',
                'properties' => 'invalid',
            ],
            'EmptyResponse.PasskeySignUpOptionsDto' => 'invalid',
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
        self::assertSame('invalid', $resultSchemas?->offsetGet(
            'EmptyResponse.PasskeySignUpOptionsDto'
        ));
    }

    public function testTransformCreatesInitialsSchemaFromInvalidGeneratedSchema(): void
    {
        $schemas = new ArrayObject([
            'EmptyResponse.PasskeySignUpOptionsDto' => [
                'type' => 'object',
                'properties' => [
                    'initials' => 'invalid',
                ],
            ],
        ]);
        $result = (new PasskeyCredentialRequestSchemaTransformer())
            ->transform($this->createOpenApi($schemas));
        $schema = $result->getComponents()?->getSchemas()?->offsetGet(
            'EmptyResponse.PasskeySignUpOptionsDto'
        );

        self::assertIsArray($schema);
        self::assertSame(
            '^(?!\\d).*\\S.*$',
            $schema['properties']['initials']['pattern']
        );
    }

    public function testTransformIgnoresMissingPasskeySignUpOptionsSchema(): void
    {
        $schemas = new ArrayObject([
            'EmptyResponse.PasskeyRegistrationCompleteDto' => [
                'type' => 'object',
            ],
        ]);
        $result = (new PasskeyCredentialRequestSchemaTransformer())
            ->transform($this->createOpenApi($schemas));
        $resultSchemas = $result->getComponents()?->getSchemas();

        self::assertInstanceOf(ArrayObject::class, $resultSchemas);
        self::assertFalse($resultSchemas->offsetExists('EmptyResponse.PasskeySignUpOptionsDto'));
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
            'EmptyResponse.PasskeySignUpOptionsDto' => [
                'type' => 'object',
                'properties' => [
                    'initials' => [
                        'type' => 'string',
                        'pattern' => '^(.*\\S.*)$',
                    ],
                ],
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
        $responseSchema = $this->credentialResponseSchema($schema);

        $this->assertAttestationResponseRequiredProperties($responseSchema);
        $this->assertAttestationResponseDocumentedProperties($responseSchema);
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
        self::assertFalse($schema['properties']['credential']['additionalProperties']);
        self::assertSame(
            ['id', 'rawId', 'response', 'type'],
            $schema['properties']['credential']['required']
        );
        self::assertSame(
            '^[A-Za-z0-9_-]+$',
            $schema['properties']['credential']['properties']['id']['pattern']
        );
        self::assertArrayHasKey('response', $schema['properties']['credential']['properties']);
    }

    /**
     * @return array<string, scalar|array|null>
     */
    private function registrationCompleteSchema(): array
    {
        $schema = $this->transformedCredentialSchemas()
            ->offsetGet('EmptyResponse.PasskeyRegistrationCompleteDto');
        self::assertIsArray($schema);

        return $schema;
    }

    /**
     * @param array<string, scalar|array|null> $schema
     */
    private function assertWebAuthnLevelThreeRegistrationResponseJson(array $schema): void
    {
        $responseSchema = $this->credentialResponseSchema($schema);
        self::assertSame([], array_diff_key(
            $this->registrationResponseJson(),
            $this->assertResponseProperties($responseSchema)
        ));
        $this->assertRequiredRegistrationResponseProperties($responseSchema);
    }

    /**
     * @return array<string, scalar|array|null>
     */
    private function registrationResponseJson(): array
    {
        return [
            'attestationObject' => $this->faker->sha256(),
            'authenticatorData' => $this->faker->sha256(),
            'clientDataJSON' => $this->faker->sha256(),
            'publicKey' => $this->faker->sha256(),
            'publicKeyAlgorithm' => $this->faker->randomElement([-7, -257]),
            'transports' => [$this->faker->randomElement(['hybrid', 'internal', 'usb'])],
        ];
    }

    /**
     * @param array<string, scalar|array|null> $responseSchema
     *
     * @return array<string, scalar|array|null>
     */
    private function assertResponseProperties(array $responseSchema): array
    {
        $responseProperties = $responseSchema['properties'];
        self::assertIsArray($responseProperties);

        return $responseProperties;
    }

    /**
     * @param array<string, scalar|array|null> $responseSchema
     */
    private function assertRequiredRegistrationResponseProperties(array $responseSchema): void
    {
        $registrationResponse = $this->registrationResponseJson();
        $requiredProperties = $responseSchema['required'];
        self::assertIsArray($requiredProperties);

        foreach ($requiredProperties as $requiredProperty) {
            self::assertIsString($requiredProperty);
            self::assertArrayHasKey($requiredProperty, $registrationResponse);
        }
    }

    /**
     * @param array<string, scalar|array|null> $responseSchema
     */
    private function assertAttestationResponseRequiredProperties(array $responseSchema): void
    {
        self::assertSame([
            'attestationObject',
            'authenticatorData',
            'clientDataJSON',
            'publicKeyAlgorithm',
            'transports',
        ], $responseSchema['required']);
        self::assertNotContains('publicKey', $responseSchema['required']);
    }

    /**
     * @param array<string, scalar|array|null> $responseSchema
     */
    private function assertAttestationResponseDocumentedProperties(array $responseSchema): void
    {
        self::assertSame([
            'attestationObject',
            'authenticatorData',
            'clientDataJSON',
            'publicKey',
            'publicKeyAlgorithm',
            'transports',
        ], array_keys($this->assertResponseProperties($responseSchema)));
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
