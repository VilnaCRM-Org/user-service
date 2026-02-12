<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Fixture\Seeder;

use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCodeInterface;

final class TestAuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
    private ?AuthorizationCodeInterface $savedCode = null;

    public function __construct(
        private readonly AuthorizationCodeInterface $existingCode
    ) {
    }

    #[\Override]
    public function find(string $identifier): ?AuthorizationCodeInterface
    {
        if ($this->existingCode->getIdentifier() !== $identifier) {
            return null;
        }

        return $this->existingCode;
    }

    #[\Override]
    public function save(AuthorizationCodeInterface $authCode): void
    {
        $this->savedCode = $authCode;
    }

    /**
     * @return int
     */
    #[\Override]
    public function clearExpired(): int
    {
        return 0;
    }

    public function savedCode(): ?AuthorizationCodeInterface
    {
        return $this->savedCode;
    }
}
