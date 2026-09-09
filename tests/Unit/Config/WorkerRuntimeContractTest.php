<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;

final class WorkerRuntimeContractTest extends UnitTestCase
{
    private const SUPERVISOR_PATH = 'infrastructure/supervisor/supervisord.conf';

    private const PROCESS_NAME = 'process_name=messenger-consume_%(process_num)02d';

    private const SUPERVISOR_RPC_FACTORY = <<<'SUPERVISOR'
supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface
SUPERVISOR;

    private const SYSTEM_PATH = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';

    private const WORKER_COMMAND = <<<'COMMAND'
messenger:consume send-email insert-user-batch domain-events --time-limit=3600 --env=prod --no-debug
COMMAND;

    private const WORKER_HEALTHCHECK_PATH = 'infrastructure/supervisor/worker-healthcheck';

    private const SUPERVISORCTL_SCRIPT = <<<'SH'
#!/bin/sh
printf '%s\n' "$SUPERVISOR_STATUS"
exit "${SUPERVISORCTL_EXIT_CODE:-0}"
SH;

    public function testWorkerSupervisorStartsOnlyConfiguredProductionConsumers(): void
    {
        $config = $this->supervisorConfig();

        self::assertStringContainsString('[rpcinterface:supervisor]', $config);
        self::assertStringContainsString(self::SUPERVISOR_RPC_FACTORY, $config);
        self::assertStringContainsString('serverurl = unix:///run/supervisor.sock', $config);
        self::assertStringContainsString(
            self::WORKER_COMMAND,
            $config
        );
        self::assertStringNotContainsString('--env=test', $config);
        self::assertStringNotContainsString('messenger-consume-test', $config);
        self::assertStringNotContainsString('failed-send-email', $config);
        self::assertStringNotContainsString('failed-domain-events', $config);
        self::assertStringContainsString('numprocs=10', $config);
        self::assertStringContainsString(self::PROCESS_NAME, $config);
        self::assertStringContainsString('stdout_logfile=/dev/fd/1', $config);
        self::assertStringContainsString('stderr_logfile=/dev/fd/2', $config);
    }

    public function testWorkerImageUsesSupervisorProcessHealthcheck(): void
    {
        $workerStage = $this->workerDockerStage();

        self::assertStringContainsString(
            $this->workerHealthcheckCopy(),
            $workerStage
        );
        self::assertStringContainsString(
            $this->workerHealthcheckDirective(),
            $workerStage
        );
        self::assertStringNotContainsString('curl -f http://localhost:2019/metrics', $workerStage);
    }

    public function testWorkerHealthcheckPassesOnlyWhenEveryConsumerIsRunning(): void
    {
        self::assertSame(0, $this->runHealthcheck($this->supervisorStatus('RUNNING')));
    }

    /**
     * @dataProvider unhealthySupervisorStatusProvider
     */
    public function testWorkerHealthcheckRejectsUnhealthySupervisorStatus(string $status): void
    {
        self::assertSame(1, $this->runHealthcheck($status));
    }

    public function testWorkerHealthcheckFailsWhenSupervisorCannotBeReached(): void
    {
        self::assertSame(1, $this->runHealthcheck('', 1));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function unhealthySupervisorStatusProvider(): iterable
    {
        $runningStatus = self::buildSupervisorStatus('RUNNING');

        yield 'stopped consumer' => [self::replaceFirstState($runningStatus, 'STOPPED')];
        yield 'starting consumer' => [self::replaceFirstState($runningStatus, 'STARTING')];
        yield 'fatal consumer' => [self::replaceFirstState($runningStatus, 'FATAL')];
        yield 'missing consumer' => [self::withoutLastConsumer($runningStatus)];
        yield 'duplicate consumer' => [
            $runningStatus . "\n" . strtok($runningStatus, "\n"),
        ];
        yield 'extra consumer' => [$runningStatus . "\n" . self::extraConsumerStatus()];
    }

    private function supervisorConfig(): string
    {
        return (string) file_get_contents($this->projectPath(self::SUPERVISOR_PATH));
    }

    private function workerDockerStage(): string
    {
        $dockerfile = (string) file_get_contents($this->projectPath('Dockerfile'));
        $workerStage = strstr($dockerfile, 'FROM frankenphp_base AS app_workers');

        self::assertIsString($workerStage);

        return $workerStage;
    }

    private function runHealthcheck(string $status, int $supervisorctlExitCode = 0): int
    {
        $directory = sys_get_temp_dir() . '/worker-healthcheck-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory));

        $supervisorctl = $this->createSupervisorctl($directory);

        try {
            return $this->executeHealthcheck($directory, $status, $supervisorctlExitCode);
        } finally {
            unlink($supervisorctl);
            rmdir($directory);
        }
    }

    private function createSupervisorctl(string $directory): string
    {
        $supervisorctl = $directory . '/supervisorctl';
        file_put_contents(
            $supervisorctl,
            self::SUPERVISORCTL_SCRIPT
        );
        chmod($supervisorctl, 0755);

        return $supervisorctl;
    }

    private function executeHealthcheck(
        string $directory,
        string $status,
        int $supervisorctlExitCode
    ): int {
        $process = proc_open(
            ['/bin/sh', $this->projectPath(self::WORKER_HEALTHCHECK_PATH)],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            null,
            [
                'PATH' => $directory . ':' . self::SYSTEM_PATH,
                'SUPERVISOR_STATUS' => $status,
                'SUPERVISORCTL_EXIT_CODE' => (string) $supervisorctlExitCode,
            ]
        );

        self::assertIsResource($process);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);
    }

    private function supervisorStatus(string $state): string
    {
        return self::buildSupervisorStatus($state);
    }

    private static function buildSupervisorStatus(string $state): string
    {
        $status = [];

        for ($process = 0; $process < 10; $process++) {
            $status[] = sprintf(
                'messenger-consume:messenger-consume_%02d %s pid 1, uptime 0:00:01',
                $process,
                $state
            );
        }

        return implode("\n", $status);
    }

    private static function replaceFirstState(string $status, string $state): string
    {
        return preg_replace('/ RUNNING /', ' ' . $state . ' ', $status, 1) ?? $status;
    }

    private static function withoutLastConsumer(string $status): string
    {
        $consumers = explode("\n", $status);
        array_pop($consumers);

        return implode("\n", $consumers);
    }

    private static function extraConsumerStatus(): string
    {
        return 'messenger-consume:messenger-consume_10 RUNNING pid 1, uptime 0:00:01';
    }

    private function workerHealthcheckCopy(): string
    {
        return implode(' ', [
            'COPY',
            '--link',
            '--chmod=755',
            'infrastructure/supervisor/worker-healthcheck',
            '/usr/local/bin/worker-healthcheck',
        ]);
    }

    private function workerHealthcheckDirective(): string
    {
        return implode(' ', [
            'HEALTHCHECK',
            '--start-period=60s',
            '--interval=30s',
            '--timeout=5s',
            '--retries=3',
            'CMD',
            '["/usr/local/bin/worker-healthcheck"]',
        ]);
    }

    private function projectPath(string $path): string
    {
        return dirname(__DIR__, 3) . '/' . $path;
    }
}
