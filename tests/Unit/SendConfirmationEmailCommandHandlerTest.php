<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Application\CommandHandler\SendConfirmationEmailCommandHandler;
use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;
use DG\BypassFinals;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Factory\UuidFactory;

class SendConfirmationEmailCommandHandlerTest extends TestCase
{
    private SendConfirmationEmailCommandHandler $handler;
    private EventBusInterface $eventBus;
    private UuidFactory $uuidFactory;
    private ConfirmationEmailSendEventFactoryInterface $eventFactory;
    private Generator $faker;

    protected function setUp(): void
    {
        BypassFinals::enable(bypassReadOnly: false);
        parent::setUp();

        $this->faker = Factory::create();

        $this->eventBus = $this->createMock(EventBusInterface::class);

        $this->uuidFactory = new UuidFactory();

        $this->eventFactory = $this->createMock(ConfirmationEmailSendEventFactoryInterface::class);

        $this->handler = new SendConfirmationEmailCommandHandler(
            $this->eventBus,
            $this->uuidFactory
        );
    }

    public function testInvoke(): void
    {
        $email = $this->faker->email();
        $name = $this->faker->name();
        $password = $this->faker->password();
        $userId = new Uuid($this->faker->uuid());

        $user = new User($email, $name, $password, $userId);
        $token = new ConfirmationToken(
            $this->faker->uuid(),
            $user->getId()
        );

        $confirmationEmail = new ConfirmationEmail($token, $user, $this->eventFactory);

        $command = new SendConfirmationEmailCommand($confirmationEmail);

        $this->eventBus->expects($this->once())
            ->method('publish');

        $this->handler->__invoke($command);
    }
}
