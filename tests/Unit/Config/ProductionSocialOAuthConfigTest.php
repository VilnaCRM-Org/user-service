<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;

final class ProductionSocialOAuthConfigTest extends UnitTestCase
{
    public function testFlagPreservesEnabledDefaultsAndAllowsExplicitOptOut(): void
    {
        $config = $this->servicesConfig();

        self::assertStringContainsString('social_oauth_enabled_default: true', $config);
        self::assertStringContainsString(
            '%env(bool:default:social_oauth_enabled_default:SOCIAL_OAUTH_ENABLED)%',
            $config
        );
    }

    public function testBothCollectionFactoriesReceiveTheSameFlag(): void
    {
        $binding = "\$enabled: '%social_oauth_enabled%'";

        self::assertSame(2, substr_count($this->servicesConfig(), $binding));
    }

    public function testProductionRetainsConfiguredProviderRegistrations(): void
    {
        $config = $this->servicesConfig();

        foreach (['GITHUB', 'GOOGLE', 'FACEBOOK', 'TWITTER'] as $provider) {
            self::assertStringContainsString('%env(OAUTH_' . $provider . '_CLIENT_ID)%', $config);
            self::assertStringContainsString(
                '%env(OAUTH_' . $provider . '_CLIENT_SECRET)%',
                $config
            );
        }
    }

    private function servicesConfig(): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3) . '/config/services.yaml');
        self::assertIsString($contents);

        return $contents;
    }
}
