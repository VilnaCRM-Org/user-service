<?php

namespace App\User\Infrastructure\User;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\Token\ConfirmationToken;
use App\User\Domain\Entity\User\User;
use App\User\Domain\Entity\User\UserInputDto;
use App\User\Domain\TokenRepository;
use App\User\Domain\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class RegisterUserProcessor implements ProcessorInterface
{
    public function __construct(private UserRepository $userRepository, private UserPasswordHasherInterface $passwordHasher,
        private MailerInterface $mailer, private TokenRepository $tokenRepository)
    {
    }

    /**
     * @param UserInputDto $data
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        $id = Uuid::random()->value();
        $plaintextPassword = $data->password;
        $user = new User($id, $data->email, $data->initials, $plaintextPassword);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plaintextPassword
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

        return $user;
    }
}
