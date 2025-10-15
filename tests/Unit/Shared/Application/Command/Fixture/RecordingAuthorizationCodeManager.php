<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\Command\Fixture;

use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCodeInterface;

final class RecordingAuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
    private ?AuthorizationCodeInterface $savedCode = null;

    public function find(string $identifier): ?AuthorizationCodeInterface
    {
        return null;
    }

    public function save(AuthorizationCodeInterface $authCode): void
    {
        $this->savedCode = $authCode;
    }

    public function clearExpired(): int
    {
        return 0;
    }

    public function savedCode(): ?AuthorizationCodeInterface
    {
        return $this->savedCode;
    }
}
