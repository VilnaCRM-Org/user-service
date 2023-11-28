<?php

namespace App\User\Infrastructure\Email;

use App\Shared\Domain\Bus\Event\DomainEventSubscriber;
use App\Shared\Infrastructure\Bus\Event\UserRegisteredEvent;
use App\User\Domain\Entity\Token\ConfirmationToken;
use App\User\Domain\TokenRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class UserRegisteredEventSubscriber implements DomainEventSubscriber
{
    public function __construct(private MailerInterface $mailer,
        private TokenRepository $tokenRepository,
        private LoggerInterface $logger)
    {
    }

    public static function subscribedTo(): array
    {
        return [UserRegisteredEvent::class];
    }

    public function __invoke(UserRegisteredEvent $userRegisteredEvent)
    {
        $emailAddress = $userRegisteredEvent->getEmail();
        $userID = $userRegisteredEvent->aggregateId();
        $token = ConfirmationToken::generateToken($userID);
        $this->tokenRepository->save($token);
        $tokenValue = $token->getTokenValue();

        $email = (new Email())
            ->to($emailAddress)
            ->subject('VilnaCRM email confirmation')
            ->text("Your email confirmation token - $tokenValue")
            ->html('<p>See Twig integration for better HTML integration!</p>');

        $this->mailer->send($email);

        $this->logger->info('Send confirmation token to '.$emailAddress);
    }
}
