<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use App\Tests\Unit\UnitTestCase;
use Symfony\Component\Yaml\Yaml;

final class E2eWorkflowContractTest extends UnitTestCase
{
    public function testE2eWorkflowRunsTheBehatTarget(): void
    {
        $workflow = Yaml::parseFile(
            dirname(__DIR__, 3) . '/.github/workflows/E2Etests.yml'
        );
        $steps = $workflow['jobs']['behat']['steps'];
        $runCommands = array_column($steps, 'run', 'name');

        self::assertSame('make behat', $runCommands['Run Behat Tests']);
        self::assertNotContains('make e2e-tests', $runCommands);
    }
}
