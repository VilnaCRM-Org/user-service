<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use Symfony\Component\Yaml\Yaml;

final class GraphQLAuthExclusionTest extends AuthIntegrationTestCase
{
    private const EMPTY_RESPONSE_CONFIG_PATH =
        __DIR__ . '/../../../config/api_platform/resources/EmptyResponse.yaml';

    /**
     * AC: NFR-62 - Auth operations must have graphql: true configured
     */
    public function testAuthOperationsHaveGraphqlEnabledInConfig(): void
    {
        $this->assertFileExists(self::EMPTY_RESPONSE_CONFIG_PATH, 'EmptyResponse.yaml must exist');
        $operations = $this->loadEmptyResponseOperations();
        foreach ($this->getRequiredOperations() as $operationName) {
            $this->assertOperationHasGraphqlEnabled($operations, $operationName);
        }
    }

    /**
     * AC: NFR-62 - Verify all 9 auth operations have graphql configured
     */
    public function testAllNineAuthOperationsHaveGraphqlEnabled(): void
    {
        $operations = $this->loadEmptyResponseOperations();
        $enabledCount = 0;
        foreach ($operations as $operation) {
            if (isset($operation['graphql']) && $operation['graphql'] === true) {
                ++$enabledCount;
            }
        }
        $this->assertGreaterThanOrEqual(
            9,
            $enabledCount,
            'At least 9 auth operations must have graphql: true (AC: NFR-62)'
        );
    }

    /**
     * @return array<string, array<string, bool|string>>
     */
    private function loadEmptyResponseOperations(): array
    {
        $config = Yaml::parseFile(self::EMPTY_RESPONSE_CONFIG_PATH);
        $key = 'App\Shared\Application\DTO\EmptyResponse';

        return $config['resources'][$key]['operations'] ?? [];
    }

    /**
     * @return array<string>
     */
    private function getRequiredOperations(): array
    {
        return [
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
    }

    /**
     * @param array<string, array<string, bool|string>> $operations
     */
    private function assertOperationHasGraphqlEnabled(
        array $operations,
        string $operationName
    ): void {
        $this->assertArrayHasKey(
            $operationName,
            $operations,
            sprintf('Operation "%s" must be defined in EmptyResponse.yaml', $operationName)
        );
        $operation = $operations[$operationName];
        $this->assertArrayHasKey('graphql', $operation, sprintf(
            'Operation "%s" must have "graphql" key configured (AC: NFR-62)',
            $operationName
        ));
        $this->assertTrue($operation['graphql'], sprintf(
            'Operation "%s" must have "graphql: true" (AC: NFR-62)',
            $operationName
        ));
    }
}
