<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\PasswordChangedEventSubscriber;
use App\User\Domain\Event\PasswordChangedEvent;
use App\User\Domain\Factory\Event\PasswordChangedEventFactory;
use App\User\Domain\Factory\Event\PasswordChangedEventFactoryInterface;
use App\User\Infrastructure\Factory\EmailFactory;
use App\User\Infrastructure\Factory\EmailFactoryInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordChangedEventSubscriberTest extends UnitTestCase
{
    private PasswordChangedEventFactoryInterface $passwordChangedEventFactory;
    private EmailFactoryInterface $emailFactory;
    private MailerInterface $mailer;
    private EmailFactoryInterface $mockEmailFactory;
    private TranslatorInterface $translator;
    private PasswordChangedEventSubscriber $subscriber;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordChangedEventFactory = new PasswordChangedEventFactory();
        $this->emailFactory = new EmailFactory();
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->mockEmailFactory =
            $this->createMock(EmailFactoryInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->subscriber = new PasswordChangedEventSubscriber(
            $this->mailer,
            $this->mockEmailFactory,
            $this->translator
        );
    }

    public function testInvoke(): void
    {
        $emailAddress = $this->faker->email();
        $event = $this->passwordChangedEventFactory->create(
            $emailAddress,
            $this->faker->uuid()
        );

        $email = $this->emailFactory->create(
            $emailAddress,
            $this->faker->text(),
            $this->faker->text(),
            ''
        );
        $this->mockEmailFactory->expects($this->once())
            ->method('create')
            ->willReturn($email);

        $this->mailer->expects($this->once())
            ->method('send')
            ->with($this->equalTo($email));

        $this->subscriber->__invoke($event);
    }

    public function testSubscribedTo(): void
    {
        $this->assertSame(
            [PasswordChangedEvent::class],
            $this->subscriber->subscribedTo()
        );
    }
}
