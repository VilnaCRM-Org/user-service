<?php

declare(strict_types=1);

namespace App\User\Application\Resolver;

use App\User\Domain\Collection\PasskeyCredentialCollection;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Repository\PasskeyCredentialRepositoryInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

final readonly class PasskeyCredentialResolver
{
    public function __construct(
        private PasskeyCredentialRepositoryInterface $credentialRepository
    ) {
    }

    public function findByUserId(string $userId): PasskeyCredentialCollection
    {
        return $this->credentialRepository->findByUserId($userId);
    }

    public function resolveForUser(string $credentialId, string $userId): PasskeyCredential
    {
        return $this->resolveForOptionalUser($credentialId, $userId);
    }

    public function resolveForOptionalUser(string $credentialId, ?string $userId): PasskeyCredential
    {
        $storedCredential = $this->credentialRepository->findByCredentialId($credentialId);

        if (
            !$storedCredential instanceof PasskeyCredential
            || $userId === null
            || $userId === ''
            || $storedCredential->getUserId() !== $userId
        ) {
            throw new UnauthorizedHttpException('Bearer', 'Invalid passkey credential.');
        }

        return $storedCredential;
    }

    public function saveUnique(PasskeyCredential $credential): void
    {
        try {
            $this->credentialRepository->save($credential);
        } catch (Throwable $exception) {
            if ($this->credentialRepository->existsByCredentialId($credential->getCredentialId())) {
                throw new ConflictHttpException(
                    'Passkey credential is already registered.',
                    $exception
                );
            }

            throw $exception;
        }
    }

    /**
     * @param callable(): void $afterSave
     * @param callable(): void $rollback
     */
    public function saveUniqueAndRun(
        PasskeyCredential $credential,
        callable $afterSave,
        callable $rollback
    ): void {
        $this->saveUnique($credential);

        try {
            $afterSave();
        } catch (Throwable $exception) {
            $this->delete($credential);
            $rollback();

            throw $exception;
        }
    }

    public function delete(PasskeyCredential $credential): void
    {
        $this->credentialRepository->delete($credential);
    }
}
