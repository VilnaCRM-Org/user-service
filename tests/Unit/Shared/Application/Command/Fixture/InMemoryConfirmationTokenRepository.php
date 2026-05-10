<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\Fixture;

use App\User\Domain\Entity\ConfirmationToken;
use App\User\Domain\Entity\ConfirmationTokenInterface;
use App\User\Domain\Repository\TokenRepositoryInterface;

final class InMemoryConfirmationTokenRepository implements TokenRepositoryInterface
{
    private ?ConfirmationToken $token = null;
    private TokenMatcher $matcher;

    public function __construct()
    {
        $this->matcher = new TokenMatcher();
    }

    #[\Override]
    public function save(object $token): void
    {
        $this->storeIfValid($token);
    }

    #[\Override]
    public function delete(object $token): void
    {
        $this->deleteIfMatches($token);
    }

    #[\Override]
    public function find(string $tokenValue): ?ConfirmationTokenInterface
    {
        return $this->matcher->matchesByTokenValue($this->token, $tokenValue);
    }

    /**
     * @return ConfirmationToken|null
     */
    #[\Override]
    public function findByUserId(string $userID): ?ConfirmationTokenInterface
    {
        return $this->matcher->matchesByUserId($this->token, $userID);
    }

    public function getToken(): ?ConfirmationToken
    {
        return $this->token;
    }

    private function storeIfValid(object $token): void
    {
        if ($this->matcher->isConfirmationToken($token)) {
            assert($token instanceof ConfirmationToken);
            $this->token = $token;
        }
    }

    private function deleteIfMatches(object $token): void
    {
        if (!$this->matcher->isConfirmationToken($token)) {
            return;
        }
        assert($token instanceof ConfirmationToken);
        if ($this->matcher->tokensMatch($this->token, $token)) {
            $this->token = null;
        }
    }
}
