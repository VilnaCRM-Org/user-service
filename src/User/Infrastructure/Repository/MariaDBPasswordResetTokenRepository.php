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
        $qb = $this->createQueryBuilder('prt')
            ->select('COUNT(prt.tokenValue)')
            ->join(
                'App\User\Domain\Entity\User',
                'u',
                'WITH',
                'u.id = prt.userID'
            )
            ->where('u.email = :email')
            ->andWhere('prt.createdAt >= :since')
            ->setParameter('email', $email)
            ->setParameter('since', $since);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
