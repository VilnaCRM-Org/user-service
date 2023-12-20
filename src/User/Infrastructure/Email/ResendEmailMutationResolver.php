<?php

namespace App\User\Infrastructure\Email;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\SendConfirmationEmailCommand;
use App\User\Domain\Entity\Email\RetryDto;
use App\User\Domain\Entity\Token\ConfirmationToken;
use App\User\Domain\TokenRepository;
use App\User\Domain\UserRepository;
use App\User\Infrastructure\Exceptions\TokenNotFoundError;
use App\User\Infrastructure\Exceptions\UserTimedOutError;

class ResendEmailMutationResolver implements MutationResolverInterface
{
    public function __construct(private CommandBus $commandBus, private UserRepository $userRepository,
        private TokenRepository $tokenRepository)
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

        return $user;
    }
}
