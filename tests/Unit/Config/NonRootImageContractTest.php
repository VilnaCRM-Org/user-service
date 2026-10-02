<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\AssertionFailedError;

final class NonRootImageContractTest extends UnitTestCase
{
    private const APP_UID = 10001;

    private const APP_GID = 10001;

    private const MINIMUM_NON_SYSTEM_ID = 1000;

    private const WEB_STAGE = 'frankenphp_prod';

    private const WORKER_STAGE = 'app_workers';

    private const WEB_PORT = 8080;

    private const WEB_TLS_PORT = 8443;

    private const SUPERVISOR_RUN_DIRECTORY = '/srv/app/var/run';

    private const WEB_WRITABLE_PATHS = ['/srv/app/var', '/data', '/config'];

    private const WORKER_WRITABLE_PATHS = ['/srv/app/var'];

    private const APPLICATION_VAR_DIRECTORIES = [
        '/srv/app/var/cache',
        '/srv/app/var/log',
        '/srv/app/var/run',
        '/srv/app/var/tmp',
    ];

    private const WEB_BOOTSTRAP_WRITABLE_PATHS = [
        '/srv/app/public/bundles',
        '/srv/app/config/jwt',
    ];

    private const APPLICATION_USER_COMMAND =
        'adduser -S -D -H -u %d -G app -h /nonexistent -s /sbin/nologin app';

    private const EXCLUDED_BUILD_CONTEXT_ENTRIES = [
        'config/jwt/',
        'config/reference.php',
        'tests/',
    ];

    private const LOCAL_ONLY_PATHS = [
        'config/jwt',
        'config/jwt/private.pem',
        'config/reference.php',
        'tests',
        'tests/Image/check-non-root-images.sh',
    ];

    private const STRIP_WORLD_WRITE_COMMAND =
        'find /srv/app /var/www/html -xdev -perm -o+w ! -type l -exec chmod o-w {} +';

    private const RUN_INSTRUCTION_PATTERN = '/^RUN (?:[^\n]*\\\\\n)*[^\n]*$/m';

    /**
     * @dataProvider runtimeStageProvider
     */
    public function testRuntimeImageEndsWithFixedNumericNonRootUser(string $stage): void
    {
        preg_match_all('/^USER\s+(\S+)\s*$/m', $this->dockerStage($stage), $users);
        $user = (string) end($users[1]);

        self::assertMatchesRegularExpression('/^\d+:\d+$/', $user);
        [$uid, $gid] = array_map('intval', explode(':', $user));
        self::assertGreaterThanOrEqual(self::MINIMUM_NON_SYSTEM_ID, $uid);
        self::assertGreaterThanOrEqual(self::MINIMUM_NON_SYSTEM_ID, $gid);
        self::assertSame([self::APP_UID, self::APP_GID], [$uid, $gid]);
    }

    public function testSharedBaseCreatesApplicationUserWithoutSwitchingToIt(): void
    {
        $base = $this->dockerStage('frankenphp_base');

        $development = $this->dockerStage('frankenphp_dev');

        self::assertStringContainsString(sprintf('addgroup -S -g %d app', self::APP_GID), $base);
        self::assertStringContainsString(
            sprintf(self::APPLICATION_USER_COMMAND, self::APP_UID),
            $base
        );
        self::assertDoesNotMatchRegularExpression('/^USER\s/m', $base);
        self::assertDoesNotMatchRegularExpression('/^USER\s/m', $development);
    }

    public function testFrankenPhpBinaryCarriesNoPrivilegedPortFileCapability(): void
    {
        self::assertStringContainsString(
            'setcap -r /usr/local/bin/frankenphp',
            $this->dockerStage('frankenphp_base')
        );
    }

    public function testProductionWebListensOnlyOnUnprivilegedPorts(): void
    {
        $caddyfile = $this->projectFile('infrastructure/docker/caddy/Caddyfile.prod');
        $web = $this->dockerStage(self::WEB_STAGE);

        self::assertMatchesRegularExpression(sprintf('/^:%d \{$/m', self::WEB_PORT), $caddyfile);
        self::assertMatchesRegularExpression(
            sprintf('/^https:\/\/:%d \{$/m', self::WEB_TLS_PORT),
            $caddyfile
        );
        preg_match_all('/^\S*:(\d+)(?:, \S+)* \{$/m', $caddyfile, $listeners);
        self::assertSame([], array_diff(
            array_map('intval', $listeners[1]),
            [self::WEB_PORT, self::WEB_TLS_PORT]
        ));
        self::assertStringContainsString('admin localhost:2019', $caddyfile);
        self::assertMatchesRegularExpression(
            sprintf('/^EXPOSE %d %d$/m', self::WEB_PORT, self::WEB_TLS_PORT),
            $web
        );
    }

    public function testProductionComposeServicesPublishTheUnprivilegedPort(): void
    {
        $compose = $this->projectFile('docker-compose.yml');

        self::assertStringContainsString(
            sprintf("- target: %d\n        published: \${HTTP_PORT:-80}", self::WEB_PORT),
            $compose
        );
        self::assertStringContainsString(sprintf("- '8082:%d'", self::WEB_PORT), $compose);
        self::assertStringNotContainsString("- '8082:80'\n", $compose);
    }

    public function testProductionWebHealthcheckProbesTheApplicationPort(): void
    {
        self::assertStringContainsString(
            sprintf(
                'CMD ["curl", "-fsS", "-o", "/dev/null", "http://127.0.0.1:%d/api/health"]',
                self::WEB_PORT
            ),
            $this->dockerStage(self::WEB_STAGE)
        );
    }

    /**
     * @dataProvider writableRuntimePathProvider
     *
     * @param list<string> $volumes
     * @param list<string> $ownedPaths
     */
    public function testRuntimeWritablePathsAreOwnedByTheApplicationUserBeforeTheSwitch(
        string $stage,
        array $volumes,
        array $ownedPaths
    ): void {
        $dockerStage = $this->dockerStage($stage);
        $ownership = $this->ownershipInstruction($dockerStage);

        foreach ($ownedPaths as $path) {
            self::assertMatchesRegularExpression(
                '#\s' . preg_quote($path, '#') . '(\s|\\\\|;|$)#',
                $ownership,
                sprintf('%s must own %s.', $stage, $path)
            );
        }

        self::assertStringContainsString(self::STRIP_WORLD_WRITE_COMMAND, $ownership);
        self::assertSame($volumes, $this->declaredVolumes($dockerStage));
        $this->assertSwitchesUserAfterOwnershipAndBeforeVolumes($dockerStage);
    }

    public function testSupervisorKeepsSocketPidAndLogInsideTheApplicationVarVolume(): void
    {
        $config = $this->projectFile('infrastructure/supervisor/supervisord.conf');
        $socket = self::SUPERVISOR_RUN_DIRECTORY . '/supervisor.sock';

        self::assertStringContainsString('file = ' . $socket, $config);
        self::assertStringContainsString('serverurl = unix://' . $socket, $config);
        self::assertStringContainsString(
            'pidfile = ' . self::SUPERVISOR_RUN_DIRECTORY . '/supervisord.pid',
            $config
        );
        self::assertStringContainsString('logfile = /srv/app/var/log/supervisord.log', $config);
        self::assertDoesNotMatchRegularExpression('#unix:///run/|=\s*/run/#', $config);
    }

    public function testWorkerImageCreatesNoRootOwnedRuntimeDirectory(): void
    {
        $worker = $this->dockerStage(self::WORKER_STAGE);

        self::assertStringNotContainsString('RUN mkdir -p /run', $worker);
        self::assertStringContainsString(
            '["/usr/bin/supervisord", "-c", "/etc/supervisor/supervisord.conf"]',
            $worker
        );
    }

    public function testBuildContextExcludesLocalKeysConfigReferenceAndTests(): void
    {
        $this->assertBuildContextExcludesLocalFiles($this->projectFile('.dockerignore'));
    }

    /**
     * @dataProvider reincludingNegationProvider
     */
    public function testBuildContextExclusionFailsOnALaterNegation(string $negation): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->assertBuildContextExcludesLocalFiles(
            $this->projectFile('.dockerignore') . $negation . "\n"
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function reincludingNegationProvider(): iterable
    {
        yield 'key directory' => ['!config/jwt'];
        yield 'key file' => ['!config/jwt/private.pem'];
        yield 'rooted key directory' => ['!/config/jwt/'];
        yield 'configuration reference' => ['!config/reference.php'];
        yield 'whole configuration tree' => ['!config/**'];
        yield 'dot-prefixed key directory' => ['!./config/jwt'];
        yield 'double-slash key directory' => ['!config//jwt'];
        yield 'test tree' => ['!tests'];
        yield 'image check script' => ['!tests/Image/check-non-root-images.sh'];
    }

    public function testProductionPhpPreloadsAsTheApplicationUser(): void
    {
        self::assertStringContainsString(
            'opcache.preload_user = app',
            $this->projectFile('infrastructure/docker/php/conf.d/app.prod.ini')
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function runtimeStageProvider(): iterable
    {
        yield 'web' => [self::WEB_STAGE];
        yield 'worker' => [self::WORKER_STAGE];
    }

    /**
     * @return iterable<string, array{string, list<string>, list<string>}>
     */
    public static function writableRuntimePathProvider(): iterable
    {
        yield 'web' => [
            self::WEB_STAGE,
            self::WEB_WRITABLE_PATHS,
            [
                ...self::WEB_WRITABLE_PATHS,
                ...self::APPLICATION_VAR_DIRECTORIES,
                ...self::WEB_BOOTSTRAP_WRITABLE_PATHS,
            ],
        ];
        yield 'worker' => [
            self::WORKER_STAGE,
            self::WORKER_WRITABLE_PATHS,
            [...self::WORKER_WRITABLE_PATHS, ...self::APPLICATION_VAR_DIRECTORIES],
        ];
    }

    private function assertBuildContextExcludesLocalFiles(string $dockerignore): void
    {
        $lines = array_map('trim', explode("\n", $dockerignore));

        foreach (self::EXCLUDED_BUILD_CONTEXT_ENTRIES as $entry) {
            self::assertContains($entry, $lines);
        }

        foreach ($lines as $line) {
            if (str_starts_with($line, '!')) {
                $this->assertNegationKeepsLocalFilesOut($this->cleanPattern(substr($line, 1)));
            }
        }
    }

    private function cleanPattern(string $pattern): string
    {
        $segments = array_filter(
            explode('/', $pattern),
            static fn (string $segment): bool => $segment !== '' && $segment !== '.'
        );

        return implode('/', $segments);
    }

    private function assertNegationKeepsLocalFilesOut(string $pattern): void
    {
        foreach (self::LOCAL_ONLY_PATHS as $path) {
            self::assertFalse(
                fnmatch($pattern, $path) || str_starts_with($path, $pattern . '/'),
                sprintf('The .dockerignore negation "!%s" re-includes %s.', $pattern, $path)
            );
        }
    }

    private function assertSwitchesUserAfterOwnershipAndBeforeVolumes(string $dockerStage): void
    {
        $ownership = strpos($dockerStage, $this->ownershipInstruction($dockerStage));
        $user = strpos($dockerStage, sprintf('USER %d:%d', self::APP_UID, self::APP_GID));
        $volume = strpos($dockerStage, 'VOLUME ');

        self::assertIsInt($ownership);
        self::assertIsInt($user);
        self::assertIsInt($volume);
        self::assertGreaterThan($ownership, $user);
        self::assertGreaterThan($user, $volume);
    }

    private function ownershipInstruction(string $dockerStage): string
    {
        $owner = sprintf('install -d -o %d -g %d', self::APP_UID, self::APP_GID);
        preg_match_all(self::RUN_INSTRUCTION_PATTERN, $dockerStage, $instructions);
        $ownership = array_values(array_filter(
            $instructions[0],
            static fn (string $instruction): bool => str_contains($instruction, $owner)
        ));

        self::assertCount(
            1,
            $ownership,
            'Expected one RUN instruction that creates the runtime paths for the application user.'
        );

        return $ownership[0];
    }

    /**
     * @return list<string>
     */
    private function declaredVolumes(string $dockerStage): array
    {
        self::assertSame(1, preg_match('/^VOLUME \[(.*)\]$/m', $dockerStage, $match));

        return array_map(
            static fn (string $path): string => trim($path, ' "'),
            explode(',', $match[1])
        );
    }

    private function dockerStage(string $stage): string
    {
        $dockerfile = $this->projectFile('Dockerfile');
        $pattern = '/^FROM \S+ AS ' . preg_quote($stage, '/') . '$(.*?)(?=^FROM |\z)/ms';
        self::assertSame(
            1,
            preg_match($pattern, $dockerfile, $match),
            sprintf('Expected the Dockerfile to define the %s stage.', $stage)
        );

        return $match[1];
    }

    private function projectFile(string $path): string
    {
        $contents = file_get_contents(dirname(__DIR__, 3) . '/' . $path);
        self::assertIsString($contents);

        return $contents;
    }
}
