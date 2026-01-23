<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;

final class MongoDBPasswordResetTokenRepository extends ServiceDocumentRepository implements PasswordResetTokenRepositoryInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly ManagerRegistry $registry,
    ) {
        parent::__construct($this->registry, PasswordResetToken::class);
    }

    public function save(
        PasswordResetTokenInterface $passwordResetToken
    ): void {
        $this->documentManager->persist($passwordResetToken);
        $this->documentManager->flush();
    }

    public function findByToken(string $token): ?PasswordResetTokenInterface
    {
        return $this->findOneBy(['tokenValue' => $token]);
    }

    public function findByUserID(
        string $userID
    ): ?PasswordResetTokenInterface {
        return $this->findOneBy(
            ['userID' => $userID],
            ['createdAt' => 'DESC']
        );
    }

    public function delete(
        PasswordResetTokenInterface $passwordResetToken
    ): void {
        $this->documentManager->remove($passwordResetToken);
        $this->documentManager->flush();
    }

    /**
     * @codeCoverageIgnore Tested in integration tests
     *
     * @infection-ignore-all Tested in integration tests
     */
    public function deleteAll(): void
    {
        $this->createQueryBuilder()
            ->remove()
            ->getQuery()
            ->execute();
    }

    /**
     * @param array<PasswordResetTokenInterface> $tokens
     */
    public function saveBatch(array $tokens): void
    {
        foreach ($tokens as $token) {
            $this->documentManager->persist($token);
        }
        $this->documentManager->flush();
    }
}
