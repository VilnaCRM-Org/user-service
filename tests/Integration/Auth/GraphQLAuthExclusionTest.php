<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @covers GraphQL auth operation exclusion
 */
final class GraphQLAuthExclusionTest extends IntegrationTestCase
{
    private const EMPTY_RESPONSE_CONFIG_PATH = __DIR__ . '/../../../config/api_platform/resources/EmptyResponse.yaml';

    /**
     * @test
     * AC: NFR-62 - Auth operations must have graphql: false configured
     */
    public function auth_operations_have_graphql_disabled_in_config(): void
    {
        $this->assertFileExists(
            self::EMPTY_RESPONSE_CONFIG_PATH,
            'EmptyResponse.yaml must exist'
        );

        $config = Yaml::parseFile(self::EMPTY_RESPONSE_CONFIG_PATH);
        $operations = $config['resources']['App\Shared\Application\DTO\EmptyResponse']['operations'] ?? [];

        // AC: NFR-62 - These auth operations MUST have "graphql: false"
        $requiredDisabledOperations = [
            'refresh_token_http',
            'signin_http',
            'signin_2fa_http',
            'confirm_2fa_http',
            'disable_2fa_http',
            'regenerate_recovery_codes_http',
            'setup_2fa_http',
            'request_password_reset',
            'confirm_password_reset',
        ];

        foreach ($requiredDisabledOperations as $operationName) {
            $this->assertArrayHasKey(
                $operationName,
                $operations,
                sprintf('Operation "%s" must be defined in EmptyResponse.yaml', $operationName)
            );

            $operation = $operations[$operationName];

            $this->assertArrayHasKey(
                'graphql',
                $operation,
                sprintf(
                    'Operation "%s" must have "graphql" key configured (AC: NFR-62)',
                    $operationName
                )
            );

            $this->assertFalse(
                $operation['graphql'],
                sprintf(
                    'Operation "%s" must have "graphql: false" to exclude from GraphQL (AC: NFR-62). ' .
                    'This prevents rate limit bypass via GraphQL batching (RC-01).',
                    $operationName
                )
            );
        }
    }

    /**
     * @test
     * AC: NFR-62 - Verify all 9 auth operations are excluded
     */
    public function all_nine_auth_operations_are_excluded_from_graphql(): void
    {
        $config = Yaml::parseFile(self::EMPTY_RESPONSE_CONFIG_PATH);
        $operations = $config['resources']['App\Shared\Application\DTO\EmptyResponse']['operations'] ?? [];

        $disabledCount = 0;
        foreach ($operations as $operationName => $operation) {
            if (isset($operation['graphql']) && $operation['graphql'] === false) {
                ++$disabledCount;
            }
        }

        $this->assertGreaterThanOrEqual(
            9,
            $disabledCount,
            'At least 9 auth operations must have graphql: false (AC: NFR-62)'
        );
    }
}
