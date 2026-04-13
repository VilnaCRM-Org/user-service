<?php

declare(strict_types=1);

namespace App\Tests\Memory\Inventory;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('memory')]
final class WorkerModeEnvironmentCoverageTest extends TestCase
{
    public function testDockerfileEnablesWorkerModeForDevelopmentAndProductionImages(): void
    {
        $dockerfile = $this->readProjectFile('Dockerfile');

        self::assertMatchesRegularExpression(
            '/FROM frankenphp_base AS frankenphp_dev.*?ENV APP_ENV=dev \\\\\s+FRANKENPHP_CONFIG="import worker\\.Caddyfile".*?COPY --link infrastructure\\/docker\\/php\\/worker\\.Caddyfile \\/etc\\/caddy\\/worker\\.Caddyfile/s',
            $dockerfile,
        );
        self::assertMatchesRegularExpression(
            '/FROM frankenphp_base AS frankenphp_prod.*?ENV FRANKENPHP_CONFIG="import worker\\.Caddyfile".*?COPY --link infrastructure\\/docker\\/php\\/worker\\.Caddyfile \\/etc\\/caddy\\/worker\\.Caddyfile/s',
            $dockerfile,
        );
    }

    public function testComposeFilesKeepWorkerModeAndLoopFuseEnabledAcrossEnvironments(): void
    {
        $this->assertFileContainsAll(
            'docker-compose.yml',
            [
                'FRANKENPHP_CONFIG: import worker.Caddyfile',
                'FRANKENPHP_LOOP_MAX: ${FRANKENPHP_LOOP_MAX:-500}',
            ],
        );
        $this->assertFileContainsAll(
            'docker-compose.override.yml',
            [
                'FRANKENPHP_CONFIG: import worker.Caddyfile',
                'FRANKENPHP_LOOP_MAX: ${FRANKENPHP_LOOP_MAX:-500}',
                './infrastructure/docker/php/worker.Caddyfile:/etc/caddy/worker.Caddyfile:ro',
            ],
        );
        $this->assertFileContainsAll(
            'docker-compose.memory-tests.yml',
            [
                'FRANKENPHP_CONFIG: import worker.Caddyfile',
                'FRANKENPHP_LOOP_MAX: ${FRANKENPHP_LOOP_MAX:-500}',
            ],
        );
        $this->assertFileContainsAll(
            'docker-compose.load-tests.yml',
            [
                'FRANKENPHP_CONFIG: import worker.Caddyfile',
                'FRANKENPHP_LOOP_MAX: ${FRANKENPHP_LOOP_MAX:-500}',
            ],
        );
        $this->assertFileContainsAll(
            'docker-compose.schemathesis.yml',
            [
                'FRANKENPHP_CONFIG: import worker.Caddyfile',
                'FRANKENPHP_LOOP_MAX: ${FRANKENPHP_LOOP_MAX:-500}',
            ],
        );
    }

    /**
     * @param list<string> $expectedSnippets
     */
    private function assertFileContainsAll(string $path, array $expectedSnippets): void
    {
        $contents = $this->readProjectFile($path);

        foreach ($expectedSnippets as $expectedSnippet) {
            self::assertStringContainsString(
                $expectedSnippet,
                $contents,
                sprintf('Expected %s to contain "%s".', $path, $expectedSnippet),
            );
        }
    }

    private function readProjectFile(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3) . '/' . $path);

        self::assertIsString($contents, sprintf('Expected %s to be readable.', $path));

        return $contents;
    }
}
