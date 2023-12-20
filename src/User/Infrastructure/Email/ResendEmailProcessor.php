<?php

namespace App\User\Infrastructure\Email;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\SendConfirmationEmailCommand;
use App\User\Domain\Entity\Token\ConfirmationToken;
use App\User\Domain\TokenRepository;
use App\User\Domain\UserRepository;
use App\User\Infrastructure\Exceptions\TokenNotFoundError;
use App\User\Infrastructure\Exceptions\UserTimedOutError;
use Symfony\Component\HttpFoundation\Response;

class ResendEmailProcessor implements ProcessorInterface
{
    public function __construct(private CommandBus $commandBus, private UserRepository $userRepository,
        private TokenRepository $tokenRepository)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $user = $this->userRepository->find($uriVariables['id']);
        try {
            $token = $this->tokenRepository->findByUserId($user->getId());
        } catch (TokenNotFoundError) {
            $token = ConfirmationToken::generateToken($user->getId());
        }

        if ($token->getAllowedToSendAfter() > new \DateTime()) {
            throw new UserTimedOutError($token->getAllowedToSendAfter());
        }

        $datetime = new \DateTime();
        switch ($token->getTimesSent()) {
            case 1:
                $token->setAllowedToSendAfter($datetime->modify('+1 minute'));
                break;
            case 2:
                $token->setAllowedToSendAfter($datetime->modify('+3 minute'));
                break;
            case 3:
                $token->setAllowedToSendAfter($datetime->modify('+4 minute'));
                break;
            case 4:
                $token->setAllowedToSendAfter($datetime->modify('+24 hours'));
        }

        $this->commandBus->dispatch(
            new SendConfirmationEmailCommand($user->getEmail(), $token));

        return new Response(status: 200);
    }
}
