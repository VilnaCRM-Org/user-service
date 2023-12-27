<?php

namespace App\User\Infrastructure\Email;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\SendConfirmationEmailCommand;
use App\User\Domain\Entity\Email\RetryDto;
use App\User\Domain\Entity\Token\ConfirmationToken;
use App\User\Domain\TokenRepositoryInterface;
use App\User\Domain\UserRepositoryInterface;
use App\User\Infrastructure\Exception\TokenNotFoundException;
use App\User\Infrastructure\Exception\UserTimedOutException;

class ResendEmailMutationResolver implements MutationResolverInterface
{
    public function __construct(private CommandBus $commandBus, private UserRepositoryInterface $userRepository,
        private TokenRepositoryInterface $tokenRepository)
    {
    }

    /**
     * @param RetryDto $item
     */
    public function __invoke(?object $item, array $context): ?object
    {
        $user = $this->userRepository->find($item->userId);
        try {
            $token = $this->tokenRepository->findByUserId($user->getId());
        } catch (TokenNotFoundException) {
            $token = ConfirmationToken::generateToken($user->getId());
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
            new SendConfirmationEmailCommand($user->getEmail(), $token));

        return $user;
    }
}
