<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

final class LeagueOAuth2ServerConfigTest extends UnitTestCase
{
    public function testImplicitGrantUsesEnvToggleParameter(): void
    {
        $config = Yaml::parseFile($this->configPath());

        $this->assertIsArray($config);
        $this->assertArrayHasKey('parameters', $config);
        $this->assertArrayHasKey('oauth.enable_implicit_grant', $config['parameters']);
        $this->assertSame(
            '%env(bool:OAUTH_ENABLE_IMPLICIT_GRANT)%',
            $config['parameters']['oauth.enable_implicit_grant']
        );

        $this->assertArrayHasKey('league_oauth2_server', $config);
        $authorizationServer = $config['league_oauth2_server']['authorization_server'] ?? null;
        $this->assertIsArray($authorizationServer);
        $this->assertArrayHasKey('enable_implicit_grant', $authorizationServer);
        $this->assertSame(
            '%oauth.enable_implicit_grant%',
            $authorizationServer['enable_implicit_grant']
        );
    }

    private function configPath(): string
    {
        return dirname(__DIR__, 3) . '/config/packages/league_oauth2_server.yaml';
    }
}
