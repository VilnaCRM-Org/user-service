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

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordChangedEventFactory = new PasswordChangedEventFactory();
        $this->emailFactory = new EmailFactory();
    }

    public function testInvoke(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $emailFactory = $this->createMock(EmailFactoryInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $subscriber = new PasswordChangedEventSubscriber(
            $mailer,
            $emailFactory,
            $translator
        );

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
        $emailFactory->expects($this->once())
            ->method('create')
            ->willReturn($email);

        $mailer->expects($this->once())
            ->method('send')
            ->with($this->equalTo($email));

        $subscriber->__invoke($event);
    }

    public function testSubscribedTo(): void
    {
        $this->assertSame(
            [PasswordChangedEvent::class],
            PasswordChangedEventSubscriber::subscribedTo()
        );
    }
}
