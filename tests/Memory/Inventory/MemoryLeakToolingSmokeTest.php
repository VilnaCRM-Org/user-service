<?php

declare(strict_types=1);

namespace App\Tests\Memory\Inventory;

use App\Tests\Memory\Support\MemoryLeakAwareWebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class MemoryLeakToolingSmokeTest extends MemoryLeakAwareWebTestCase
{
    public function testHealthEndpointDoesNotRetainKernelState(): void
    {
        $client = $this->createMemoryClient();
        $client->request(
            'GET',
            '/api/health',
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_USER_AGENT' => 'MemoryLeakToolingSmokeTest',
                'REMOTE_ADDR' => '127.0.0.1',
            ]
        );

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $this->finishMemoryRequestCycle();
    }
}
