<?php

declare(strict_types=1);

namespace App\Tests\Unit\Internal\HealthCheck\Infrastructure\EventSubscriber;

use App\Internal\HealthCheck\Domain\Event\HealthCheckEvent;
use App\Internal\HealthCheck\Infrastructure\EventSubscriber\BrokerCheckSubscriber;
use App\Tests\Unit\UnitTestCase;
use Aws\CommandInterface;
use Aws\Exception\AwsException;
use Aws\Result;
use Aws\Sqs\SqsClient;
use PHPUnit\Framework\MockObject\MockObject;

final class BrokerCheckSubscriberTest extends UnitTestCase
{
    private SqsClient&MockObject $sqsClient;
    private BrokerCheckSubscriber $subscriber;
    private string $queueName;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->sqsClient = $this->createMock(SqsClient::class);
        $this->queueName = $this->faker->lexify('health-check-????????');
        $this->subscriber = new BrokerCheckSubscriber($this->sqsClient, $this->queueName);
    }

    public function testOnHealthCheckReadsExistingQueue(): void
    {
        $result = new Result(
            ['QueueUrl' => $this->faker->url()]
        );

        $this->sqsClient->expects($this->once())
            ->method('__call')
            ->with($this->equalTo(
                'getQueueUrl'
            ), $this->equalTo([['QueueName' => $this->queueName]]))
            ->willReturn($result);

        $event = new HealthCheckEvent();
        $this->subscriber->onHealthCheck($event);
    }

    public function testOnHealthCheckPropagatesMissingQueueException(): void
    {
        $command = $this->createMock(CommandInterface::class);

        $this->sqsClient->expects($this->once())
            ->method('__call')
            ->with($this->equalTo(
                'getQueueUrl'
            ), $this->equalTo([['QueueName' => $this->queueName]]))
            ->willThrowException(new AwsException(
                'Queue does not exist',
                $command,
                [
                    'code' => 'AWS.SimpleQueueService.NonExistentQueue',
                ]
            ));

        $this->expectException(AwsException::class);
        $this->expectExceptionMessage('Queue does not exist');

        $this->subscriber->onHealthCheck(new HealthCheckEvent());
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertSame(
            [HealthCheckEvent::class => 'onHealthCheck'],
            BrokerCheckSubscriber::getSubscribedEvents()
        );
    }
}
