<?php

declare(strict_types=1);

namespace App\Tests\Memory\Inventory;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('memory')]
final class WorkerModeEnvironmentCoverageTest extends TestCase
{
    private const COMPOSE_WORKER_SNIPPETS = [
        'FRANKENPHP_CONFIG: import worker.Caddyfile',
        'FRANKENPHP_LOOP_MAX: ${FRANKENPHP_LOOP_MAX:-500}',
    ];

    private const OVERRIDE_WORKER_SNIPPETS = [
        'FRANKENPHP_CONFIG: import worker.Caddyfile',
        'FRANKENPHP_LOOP_MAX: ${FRANKENPHP_LOOP_MAX:-500}',
        './infrastructure/docker/php/worker.Caddyfile:/etc/caddy/worker.Caddyfile:ro',
    ];

    private const COMPOSE_WORKER_FILES = [
        'docker-compose.yml',
        'docker-compose.memory-tests.yml',
        'docker-compose.load-tests.yml',
        'docker-compose.schemathesis.yml',
        'docker-compose.prod.yml',
    ];

    private const LOOP_FUSE_ENV_FILES = [
        '.env',
        '.env.test',
        '.env.load_test',
    ];

    public function testDockerfileEnablesWorkerModeForDevelopmentAndProductionImages(): void
    {
        $dockerfile = $this->readProjectFile('Dockerfile');

        self::assertMatchesRegularExpression($this->devWorkerRegex(), $dockerfile);
        self::assertMatchesRegularExpression($this->prodWorkerRegex(), $dockerfile);
    }

    public function testComposeFilesKeepWorkerModeAndLoopFuseEnabledAcrossEnvironments(): void
    {
        foreach (self::COMPOSE_WORKER_FILES as $path) {
            $this->assertComposeWorkerMode($path, self::COMPOSE_WORKER_SNIPPETS);
        }

        $this->assertComposeWorkerMode(
            'docker-compose.override.yml',
            self::OVERRIDE_WORKER_SNIPPETS,
        );
    }

    public function testEnvironmentFilesKeepTheWorkerLoopFuseConfigured(): void
    {
        foreach (self::LOOP_FUSE_ENV_FILES as $path) {
            $this->assertFileContainsAll($path, ['FRANKENPHP_LOOP_MAX=500']);
        }
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

    /**
     * @param list<string> $expectedSnippets
     */
    private function assertComposeWorkerMode(string $path, array $expectedSnippets): void
    {
        $this->assertFileContainsAll($path, $expectedSnippets);
    }

    private function readProjectFile(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3) . '/' . $path);

        self::assertIsString($contents, sprintf('Expected %s to be readable.', $path));

        return $contents;
    }

    private function devWorkerRegex(): string
    {
        return implode('', [
            '/FROM frankenphp_base AS frankenphp_dev.*?',
            'ENV APP_ENV=dev \\\\\s+FRANKENPHP_CONFIG="import worker\\.Caddyfile".*?',
            'COPY --link infrastructure\\/docker\\/php\\/worker\\.Caddyfile ',
            '\\/etc\\/caddy\\/worker\\.Caddyfile/s',
        ]);
    }

    private function prodWorkerRegex(): string
    {
        return implode('', [
            '/FROM frankenphp_base AS frankenphp_prod.*?',
            'ENV FRANKENPHP_CONFIG="import worker\\.Caddyfile".*?',
            'COPY --link infrastructure\\/docker\\/php\\/worker\\.Caddyfile ',
            '\\/etc\\/caddy\\/worker\\.Caddyfile/s',
        ]);
    }
}
