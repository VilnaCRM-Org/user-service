<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Entity\PasswordResetToken;
use App\User\Domain\Entity\PasswordResetTokenInterface;
use App\User\Domain\Repository\PasswordResetTokenRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

final class MariaDBPasswordResetTokenRepository extends ServiceEntityRepository implements PasswordResetTokenRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ManagerRegistry $registry,
    ) {
        parent::__construct($this->registry, PasswordResetToken::class);
    }

    public function save(
        PasswordResetTokenInterface $passwordResetToken
    ): void {
        $this->entityManager->persist($passwordResetToken);
        $this->entityManager->flush();
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
        $this->entityManager->remove($passwordResetToken);
        $this->entityManager->flush();
    }

    public function countRecentRequestsByEmail(
        string $email,
        \DateTimeImmutable $since
    ): int {
        $sql = '
            SELECT COUNT(prt.token_value)
            FROM password_reset_tokens prt
            INNER JOIN user u ON LOWER(HEX(u.id)) = LOWER(REPLACE(prt.user_id, "-", ""))
            WHERE u.email = :email AND prt.created_at >= :since
        ';

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindValue('email', $email);
        $stmt->bindValue('since', $since->format('Y-m-d H:i:s'));

        $result = $stmt->executeQuery();
        return (int) $result->fetchOne();
    }
}
