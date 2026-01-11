<?php

declare(strict_types=1);

namespace App\Internal\HealthCheck\Infrastructure\EventSubscriber;

use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use Aws\Sqs\SqsClient;

final class BrokerCheckSubscriber extends BaseHealthCheckSubscriber
{
    public function __construct(
        private readonly SqsClient $sqsClient,
        private readonly string $queueName = 'health-check-queue'
    ) {
    }

    #[\Override]
    public function onHealthCheck(HealthCheckEvent $event): void
    {
        $this->createQueue($this->queueName);
    }

    private function createQueue(string $queueName): void
    {
        $this->sqsClient->createQueue(['QueueName' => $queueName]);
    }
}
