<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Application\Factory\IdFactoryInterface;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use function trim;

final readonly class PasskeyCredentialStore
{
    private const DEFAULT_LABEL = 'Passkey';

    public function __construct(
        private PasskeyCredentialRepositoryInterface $credentialRepository,
        private IdFactoryInterface $idFactory
    ) {
    }

    /**
     * @return list<PasskeyCredential>
     */
    public function findByUserId(string $userId): array
    {
        return $this->credentialRepository->findByUserId($userId);
    }

    public function resolveForUser(string $credentialId, string $userId): PasskeyCredential
    {
        $storedCredential = $this->credentialRepository->findByCredentialId($credentialId);

        if (
            !$storedCredential instanceof PasskeyCredential
            || $storedCredential->getUserId() !== $userId
        ) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid passkey credential.');
        }

        return $storedCredential;
    }

    public function markUsed(
        PasskeyCredential $credential,
        string $credentialRecord,
        DateTimeImmutable $usedAt
    ): void {
        $credential->markUsed($credentialRecord, $usedAt);
        $this->credentialRepository->save($credential);
    }

    public function register(
        string $userId,
        VerifiedPasskeyCredential $verifiedCredential,
        string $label,
        DateTimeImmutable $createdAt
    ): PasskeyCredential {
        if (
            $this->credentialRepository->existsByCredentialId(
                $verifiedCredential->getCredentialId()
            )
        ) {
            throw new ConflictHttpException('Passkey credential is already registered.');
        }

        $credential = new PasskeyCredential(
            $this->idFactory->create(),
            $userId,
            $verifiedCredential->getCredentialId(),
            $verifiedCredential->getCredentialRecord(),
            $this->resolveLabel($label),
            $createdAt
        );

        $this->credentialRepository->save($credential);

        return $credential;
    }

    private function resolveLabel(string $label): string
    {
        $trimmedLabel = trim($label);

        return $trimmedLabel === '' ? self::DEFAULT_LABEL : $trimmedLabel;
    }
}
