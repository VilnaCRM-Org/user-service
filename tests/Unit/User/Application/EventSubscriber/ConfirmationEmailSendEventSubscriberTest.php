<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\EventSubscriber;

use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\EventSubscriber\ConfirmationEmailSentEventSubscriber;
use App\User\Application\Factory\EmailFactoryInterface;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Event\ConfirmationEmailSentEvent;
use App\User\Domain\Factory\ConfirmationTokenFactory;
use App\User\Domain\Factory\ConfirmationTokenFactoryInterface;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactory;
use App\User\Domain\Factory\Event\ConfirmationEmailSendEventFactoryInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;
use App\User\Infrastructure\Factory\EmailFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ConfirmationEmailSendEventSubscriberTest extends UnitTestCase
{
    private UserFactoryInterface $userFactory;
    private ConfirmationTokenFactoryInterface $confirmationTokenFactory;
    private UuidTransformer $uuidTransformer;
    private ConfirmationEmailSendEventFactoryInterface $sendEventFactory;
    private EmailFactoryInterface $emailFactory;
    private MailerInterface $mailer;
    private TokenRepositoryInterface $tokenRepository;
    private LoggerInterface $logger;
    private TranslatorInterface $translator;
    private EmailFactoryInterface $mockEmailFactory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userFactory = new UserFactory();
        $this->confirmationTokenFactory = new ConfirmationTokenFactory(
            $this->faker->numberBetween(1, 10)
        );
        $this->uuidTransformer = new UuidTransformer(new UuidFactory());
        $this->sendEventFactory = new ConfirmationEmailSendEventFactory();
        $this->emailFactory = new EmailFactory();
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->tokenRepository =
            $this->createMock(TokenRepositoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->mockEmailFactory =
            $this->createMock(EmailFactoryInterface::class);
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

        $event = $this->sendEventFactory->create($token, $user, $eventID);
        $email = $this->emailFactory->create(
            $emailAddress,
            $this->faker->title(),
            $token->getTokenValue(),
            'email/confirm.html.twig'
        );

        $this->testInvokeSetExpectations($token, $email, $emailAddress);

        $this->getSubscriber()->__invoke($event);
    }

    public function testSubscribedTo(): void
    {
        $this->assertSame(
            [ConfirmationEmailSentEvent::class],
            $this->getSubscriber()->subscribedTo()
        );
    }

    private function getSubscriber(): ConfirmationEmailSentEventSubscriber
    {
        return new ConfirmationEmailSentEventSubscriber(
            $this->mailer,
            $this->tokenRepository,
            $this->logger,
            $this->translator,
            $this->mockEmailFactory
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
                'email/confirm.html.twig'
            )->willReturn($email);

        $this->mailer->expects($this->once())
            ->method('send')->with($this->equalTo($email));

        $this->logger->expects($this->once())
            ->method('info')->with($this->equalTo(
                'Confirmation token send to ' . $emailAddress
            ));
    }

    private function setTranslatorExpectation(
        Email $email,
        string $tokenValue
    ): void {
        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnCallback(
                $this->expectSequential(
                    [
                        ['email.confirm.subject'],
                        ['email.confirm.text', ['tokenValue' => $tokenValue]],
                    ],
                    [$email->getSubject(), $tokenValue]
                )
            );
    }
}
