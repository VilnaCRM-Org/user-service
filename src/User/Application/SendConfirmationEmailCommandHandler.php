<?php

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\CommandHandler;
use App\User\Domain\TokenRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class SendConfirmationEmailCommandHandler implements CommandHandler
{
    public function __construct(private MailerInterface $mailer,
        private TokenRepositoryInterface $tokenRepository,
        private LoggerInterface $logger)
    {
    }

    public function __invoke(SendConfirmationEmailCommand $command)
    {
        $emailAddress = $command->getEmailAddress();
        $token = $command->getToken();
        $tokenValue = $token->getTokenValue();

        $token->setTimesSent($token->getTimesSent() + 1);
        $this->tokenRepository->save($token);

        $email = (new Email())
            ->to($emailAddress)
            ->subject('VilnaCRM email confirmation')
            ->text("Your email confirmation token - $tokenValue")
            ->html('<p>See Twig integration for better HTML integration!</p>');

        $this->mailer->send($email);

        $this->logger->info('Send confirmation token to '.$emailAddress);
    }
}
