<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\ConfirmationEmailSendEventSubscriber;
use App\User\Domain\Event\ConfirmationEmailSendEvent;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactory;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Infrastructure\Factory\EmailFactory;
use App\User\Infrastructure\Factory\EmailFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfirmationEmailSendEventSubscriberTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private UuidTransformer $uuidTransformer;
    private ConfirmationEmailSendEventFactoryInterface $sendEventFactory;
    private EmailFactoryInterface $emailFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory($this->faker->numberBetween(1, 10));
        $this->uuidTransformer = new UuidTransformer();
        $this->sendEventFactory = new ConfirmationEmailSendEventFactory();
        $this->emailFactory = new EmailFactory();
    }

    public function testInvoke(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $tokenRepository = $this->createMock(TokenRepositoryInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $mockEmailFactory = $this->createMock(EmailFactoryInterface::class);

        $subscriber = new ConfirmationEmailSendEventSubscriber(
            $mailer,
            $tokenRepository,
            $logger,
            $translator,
            $mockEmailFactory
        );

        $emailAddress = $this->faker->email();
        $userId = $this->faker->uuid();
        $token = $this->confirmationTokenFactory->create($userId);
        $tokenValue = $token->getTokenValue();
        $user = $this->userFactory->create(
            $emailAddress,
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($userId)
        );

        $event = $this->sendEventFactory->create($token, $user, $this->faker->uuid());

        $tokenRepository->expects($this->once())
            ->method('save')
            ->with($this->equalTo($token));

        $subject = $this->faker->title();

        $translator->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(['email.confirm.subject'], ['email.confirm.text', ['tokenValue' => $tokenValue]])
            ->willReturnOnConsecutiveCalls($subject, $tokenValue);

        $email = $this->emailFactory->create(
            $emailAddress,
            $subject,
            $tokenValue,
            'email/confirm.html.twig'
        );
        $mockEmailFactory->expects($this->once())
            ->method('create')
            ->with(
                $emailAddress,
                $subject,
                $tokenValue,
                'email/confirm.html.twig'
            )
            ->willReturn($email);

        $mailer->expects($this->once())
            ->method('send')
            ->with($this->equalTo($email));

        $logger->expects($this->once())
            ->method('info')
            ->with($this->equalTo('Confirmation token send to ' . $emailAddress));

        $subscriber->__invoke($event);
    }

    public function testSubscribedTo(): void
    {
        $this->assertSame(
            [ConfirmationEmailSendEvent::class],
            ConfirmationEmailSendEventSubscriber::subscribedTo()
        );
    }
}
