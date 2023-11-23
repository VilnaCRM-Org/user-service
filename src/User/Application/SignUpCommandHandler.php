<?php

declare(strict_types=1);

namespace App\User\Application;

use App\Shared\Domain\Bus\Command\CommandHandler;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\Token\ConfirmationToken;
use App\User\Domain\Entity\User\User;
use App\User\Domain\TokenRepository;
use App\User\Domain\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class SignUpCommandHandler implements CommandHandler
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer,
        private UserRepository $userRepository,
        private TokenRepository $tokenRepository,
    ) {
    }

    public function __invoke(SignUpCommand $command): SignUpCommandResponse
    {
        $id = Uuid::random()->value();
        $email = $command->getEmail();
        $initials = $command->getInitials();
        $password = $command->getPassword();

        $user = new User($id, $email, $initials, $password);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $password
        );
        $user->setPassword($hashedPassword);

        $this->userRepository->save($user);

        $token = ConfirmationToken::generateToken($user->getId());

        $this->tokenRepository->save($token);

        $tokenValue = $token->getTokenValue();

        $email = (new Email())
            ->from('hello@example.com')
            ->to($user->getEmail())
            ->subject('Time for Symfony Mailer!')
            ->text("Your email confirmation token - $tokenValue")
            ->html('<p>See Twig integration for better HTML integration!</p>');

        $this->mailer->send($email);

        return new SignUpCommandResponse();
    }
}
