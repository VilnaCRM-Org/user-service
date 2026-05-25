<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;

/**
 * @psalm-type OpenApiSchemaValue = ArrayObject<array-key, scalar|array|null>|array<array-key, scalar|array|null>|scalar|null
 * @psalm-type OpenApiSchema = array<string, OpenApiSchemaValue>
 */
final class PasskeyCredentialRequestSchemaTransformer
{
    private const ATTESTATION_SCHEMA_NAMES = [
        'EmptyResponse.PasskeyRegistrationCompleteDto',
        'EmptyResponse.PasskeySignUpCompleteDto',
    ];

    private const ASSERTION_SCHEMA_NAMES = [
        'EmptyResponse.PasskeySignInCompleteDto',
    ];

    private const BASE_CREDENTIAL_SCHEMA = [
        'type' => 'object',
        'additionalProperties' => true,
        'required' => ['id', 'rawId', 'response', 'type'],
        'properties' => [
            'id' => [
                'type' => 'string',
                'minLength' => 1,
            ],
            'rawId' => [
                'type' => 'string',
                'minLength' => 1,
            ],
            'type' => [
                'type' => 'string',
                'enum' => ['public-key'],
            ],
            'authenticatorAttachment' => [
                'type' => ['string', 'null'],
            ],
            'clientExtensionResults' => [
                'type' => 'object',
                'additionalProperties' => true,
            ],
        ],
    ];

    private const ATTESTATION_RESPONSE_SCHEMA = [
        'type' => 'object',
        'additionalProperties' => true,
        'required' => ['attestationObject', 'clientDataJSON'],
        'properties' => [
            'attestationObject' => [
                'type' => 'string',
                'minLength' => 1,
            ],
            'clientDataJSON' => [
                'type' => 'string',
                'minLength' => 1,
            ],
            'transports' => [
                'type' => 'array',
                'items' => ['type' => 'string'],
            ],
        ],
    ];

    private const ASSERTION_RESPONSE_SCHEMA = [
        'type' => 'object',
        'additionalProperties' => true,
        'required' => ['authenticatorData', 'clientDataJSON', 'signature'],
        'properties' => [
            'authenticatorData' => [
                'type' => 'string',
                'minLength' => 1,
            ],
            'clientDataJSON' => [
                'type' => 'string',
                'minLength' => 1,
            ],
            'signature' => [
                'type' => 'string',
                'minLength' => 1,
            ],
            'userHandle' => [
                'type' => ['string', 'null'],
            ],
        ],
    ];

    private const CREDENTIAL_RESPONSE_PROPERTY = 'response';

    public function transform(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        $schemas = $components?->getSchemas();

        if ($components === null || $schemas === null) {
            return $openApi;
        }

        foreach (self::ATTESTATION_SCHEMA_NAMES as $schemaName) {
            $this->transformSchema($schemas, $schemaName, self::ATTESTATION_RESPONSE_SCHEMA);
        }

        foreach (self::ASSERTION_SCHEMA_NAMES as $schemaName) {
            $this->transformSchema($schemas, $schemaName, self::ASSERTION_RESPONSE_SCHEMA);
        }

        return $openApi->withComponents($components->withSchemas($schemas));
    }

    /**
     * @param ArrayObject<string, OpenApiSchema|ArrayObject<array-key, scalar|array|null>> $schemas
     * @param array<string, scalar|array|null> $responseSchema
     */
    private function transformSchema(
        ArrayObject $schemas,
        string $schemaName,
        array $responseSchema
    ): void {
        if (!isset($schemas[$schemaName])) {
            return;
        }

        $schema = $this->schemaArray($schemas[$schemaName]);

        if ($schema === null) {
            return;
        }

        $schema['required'] = $this->requiredProperties($schema);
        $schema['properties'] = $this->credentialProperties($schema, $responseSchema);
        $schemas[$schemaName] = $schema;
    }

    /**
     * @param array<string, scalar|array|null> $responseSchema
     *
     * @return OpenApiSchema
     */
    private function credentialSchema(array $responseSchema): array
    {
        $schema = self::BASE_CREDENTIAL_SCHEMA;
        $schema['properties'][self::CREDENTIAL_RESPONSE_PROPERTY] = $responseSchema;

        return $schema;
    }

    /**
     * @param OpenApiSchema|ArrayObject<array-key, scalar|array|null>|scalar|null $schema
     *
     * @return OpenApiSchema|null
     */
    private function schemaArray(
        array|ArrayObject|bool|float|int|string|null $schema
    ): ?array {
        if ($schema instanceof ArrayObject) {
            /** @var OpenApiSchema $schema */
            $schema = $schema->getArrayCopy();
        }

        if (!is_array($schema)) {
            return null;
        }

        return $schema;
    }

    /**
     * @param OpenApiSchema $schema
     *
     * @return list<string>
     */
    private function requiredProperties(array $schema): array
    {
        $required = $schema['required'] ?? [];

        if ($required instanceof ArrayObject) {
            $required = $required->getArrayCopy();
        }

        if (!is_array($required)) {
            $required = [];
        }

        return array_values(array_unique([
            ...array_filter($required, 'is_string'),
            'challengeId',
            'credential',
        ]));
    }

    /**
     * @param OpenApiSchema $schema
     * @param array<string, scalar|array|null> $responseSchema
     *
     * @return OpenApiSchema
     */
    private function credentialProperties(array $schema, array $responseSchema): array
    {
        $properties = $schema['properties'] ?? [];

        if ($properties instanceof ArrayObject) {
            $properties = $properties->getArrayCopy();
        }

        if (!is_array($properties)) {
            $properties = [];
        }

        $properties['credential'] = $this->credentialSchema($responseSchema);

        return $properties;
    }
}
