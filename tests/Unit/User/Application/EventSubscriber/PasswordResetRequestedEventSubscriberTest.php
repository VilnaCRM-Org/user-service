<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Application\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\PasswordResetRequestedEventSubscriber;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactory;
use App\User\Domain\Factory\Event\PasswordResetRequestedEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Infrastructure\Factory\EmailFactory;
use App\User\Infrastructure\Factory\EmailFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

final class PasswordResetRequestedEventSubscriberTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private UuidTransformer $uuidTransformer;
    private PasswordResetRequestedEventFactoryInterface $passwordResetRequestedEventFactory;
    private EmailFactoryInterface $emailFactory;
    private MailerInterface $mailer;
    private TokenRepositoryInterface $tokenRepository;
    private LoggerInterface $logger;
    private TranslatorInterface $translator;
    private EmailFactoryInterface $mockEmailFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->emailFactory = new EmailFactory();
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->userFactory = new UserFactory();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->uuidTransformer = new UuidTransformer();
        $this->passwordResetRequestedEventFactory = new PasswordResetRequestedEventFactory();
        $this->emailFactory = new EmailFactory();

        $this->tokenRepository = $this->createMock(TokenRepositoryInterface::class);

        $this->mockEmailFactory = $this->createMock(EmailFactoryInterface::class);
    }

    public function testInvoke(): void
    {
        $eventID = $this->faker->uuid();
        $emailAddress = $this->faker->email();
        $userId = $this->faker->uuid();
        $token = $this->confirmationTokenFactory->create($userId);
        $user = $this->userFactory->create(
            $emailAddress,
            $this->faker->name(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($userId)
        );

        $event = $this->passwordResetRequestedEventFactory->create($token, $user, $eventID);
        $email = $this->emailFactory->create(
            $emailAddress,
            $this->faker->title(),
            $token->getTokenValue(),
            'email/password-reset.html.twig'
        );

        $this->testInvokeSetExpectations($token, $email, $emailAddress);

        $this->getSubscriber()->__invoke($event);
    }

    public function testSubscribedTo(): void
    {
        $this->assertSame(
            [PasswordResetRequestedEvent::class],
            $this->getSubscriber()->subscribedTo()
        );
    }

    private function getSubscriber(): PasswordResetRequestedEventSubscriber
    {
        return new PasswordResetRequestedEventSubscriber(
            $this->mailer,
            $this->tokenRepository,
            $this->logger,
            $this->mockEmailFactory,
            $this->translator,
        );
    }

    private function testInvokeSetExpectations(
        ConfirmationTokenInterface $token,
        Email $email,
        string $emailAddress
    ): void {
        $tokenValue = $token->getTokenValue();
        $subject = $email->getSubject();

        $this->tokenRepository->expects($this->once())
            ->method('save')->with($this->equalTo($token));

        $this->setTranslatorExpectation($email, $tokenValue);

        $this->mockEmailFactory->expects($this->once())
            ->method('create')->with(
                $emailAddress,
                $subject,
                $tokenValue,
                'email/password-reset.html.twig'
            )->willReturn($email);

        $this->mailer->expects($this->once())
            ->method('send')->with($this->equalTo($email));

        $this->logger->expects($this->once())
            ->method('info')->with($this->equalTo(
                'Reset password token send to ' . $emailAddress
            ));
    }

    private function setTranslatorExpectation(
        Email $email,
        string $tokenValue
    ): void {
        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->withConsecutive(
                ['email.password.reset.requested.subject'],
                ['email.password.reset.requested.test', ['tokenValue' => $tokenValue]]
            )->willReturnOnConsecutiveCalls($email->getSubject(), $tokenValue);
    }
}
