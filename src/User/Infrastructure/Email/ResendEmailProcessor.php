<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Email;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\Command\SendConfirmationEmailCommand;
use App\User\Domain\Aggregate\ConfirmationEmail;
use App\User\Domain\Entity\ConfirmationTokenFactory;
use App\User\Domain\TokenRepositoryInterface;
use App\User\Domain\UserRepositoryInterface;
use App\User\Infrastructure\Exception\TokenNotFoundException;
use App\User\Infrastructure\Exception\UserTimedOutException;
use Symfony\Component\HttpFoundation\Response;

/**
 * @implements ProcessorInterface<Response>
 */
class ResendEmailProcessor implements ProcessorInterface
{
    public function __construct(private CommandBus $commandBus, private UserRepositoryInterface $userRepository,
        private TokenRepositoryInterface $tokenRepository, private ConfirmationTokenFactory $tokenFactory)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $user = $this->userRepository->find((string)$uriVariables['id']);
        try {
            $token = $this->tokenRepository->findByUserId((string)$user->getId());
        } catch (TokenNotFoundException) {
            $token = $this->tokenFactory->create((string)$user->getId());
        }
        if ($token->getAllowedToSendAfter() > new \DateTime()) {
            throw new UserTimedOutException($token->getAllowedToSendAfter());
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
            new SendConfirmationEmailCommand(new ConfirmationEmail($token, $user)));

        return new Response(status: 200);
    }
}
