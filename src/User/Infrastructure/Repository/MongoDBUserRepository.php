<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\DuplicateEmailException;
use App\User\Domain\Repository\UserRepositoryInterface;

use function array_map;
use function array_merge;
use function array_unique;
use function array_values;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use InvalidArgumentException;
use function mb_strtolower;
use MongoDB\BSON\Regex;
use function preg_quote;
use function str_contains;
use Throwable;
use function trim;

/**
 * @extends ServiceDocumentRepository<User>
 */
final class MongoDBUserRepository extends ServiceDocumentRepository implements
    UserRepositoryInterface
{
    private const DUPLICATE_KEY_ERROR_CODE = 11000;

    public function __construct(
        private readonly DocumentManager $documentManager,
        ManagerRegistry $registry,
        private readonly int $batchSize,
    ) {
        if ($batchSize <= 0) {
            throw new InvalidArgumentException('Batch size must be greater than zero.');
        }
        parent::__construct($registry, User::class);
    }

    #[\Override]
    public function save(object $user): void
    {
        try {
            $this->documentManager->persist($user);
            $this->documentManager->flush();
        } catch (Throwable $error) {
            $this->documentManager->clear(User::class);

            if (
                $user instanceof UserInterface
                && $this->isDuplicateEmailKeyError($error)
            ) {
                throw new DuplicateEmailException($user->getEmail(), $error);
            }

            throw $error;
        }
    }

    #[\Override]
    public function delete(object $user): void
    {
        $this->documentManager->remove($user);
        $this->documentManager->flush();
    }

    /**
     * @return User|null
     */
    #[\Override]
    public function findByEmail(string $email): ?UserInterface
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * @param array<int, string> $emails
     *
     * Matches exact, trimmed, lowercase, and legacy mixed-case email variants.
     */
    #[\Override]
    public function findByEmails(array $emails): UserCollection
    {
        $uniqueEmails = $this->uniqueEmailCandidates($emails);

        if ($uniqueEmails === []) {
            return new UserCollection();
        }

        $result = $this->createQueryBuilder()
            ->field('email')->in($this->emailLookupExpressions($uniqueEmails))
            ->getQuery()
            ->execute();
        $users = [];

        foreach ($result as $user) {
            if (!$user instanceof UserInterface) {
                continue;
            }

            $users[] = $user;
        }

        return new UserCollection($users);
    }

    /**
     * @return User|null
     */
    #[\Override]
    public function findById(string $id): ?UserInterface
    {
        return $this->find($id);
    }

    #[\Override]
    public function saveBatch(UserCollection $users): void
    {
        $this->persistUsersInBatch($users);
    }

    #[\Override]
    public function deleteBatch(UserCollection $users): void
    {
        $this->removeUsersInBatch($users);
    }

    #[\Override]
    public function deleteAll(): void
    {
        $this->createQueryBuilder()
            ->remove()
            ->getQuery()
            ->execute();
    }

    /**
     * @param array<int, string> $emails
     *
     * @return list<string>
     */
    private function uniqueEmailCandidates(array $emails): array
    {
        $trimmedEmails = array_map(
            static fn (string $email): string => trim($email),
            $emails
        );

        return array_values(array_unique(array_merge(
            $emails,
            $trimmedEmails,
            array_map(
                static fn (string $email): string => mb_strtolower($email, 'UTF-8'),
                $trimmedEmails
            )
        )));
    }

    /**
     * @param list<string> $emails
     *
     * @return list<string|Regex>
     */
    private function emailLookupExpressions(array $emails): array
    {
        return array_values(array_merge(
            $emails,
            array_map(
                static fn (string $email): Regex => new Regex(
                    '^' . preg_quote($email, '/') . '$',
                    'i'
                ),
                $emails
            )
        ));
    }

    private function isDuplicateEmailKeyError(Throwable $error): bool
    {
        return $this->isDuplicateKeyError($error)
            && str_contains($error->getMessage(), 'email');
    }

    private function isDuplicateKeyError(Throwable $error): bool
    {
        return $error->getCode() === self::DUPLICATE_KEY_ERROR_CODE
            || str_contains($error->getMessage(), 'E11000');
    }

    private function persistUsersInBatch(UserCollection $users): void
    {
        $usersForPersistence = $users->users;

        array_walk(
            $usersForPersistence,
            function (User $user, int $index): void {
                $position = $index + 1;
                $this->documentManager->persist($user);

                if ($position % $this->batchSize === 0) {
                    $this->documentManager->flush();
                    $this->documentManager->clear();
                }
            }
        );
        $this->documentManager->flush();
        $this->documentManager->clear();
    }

    private function removeUsersInBatch(UserCollection $users): void
    {
        $usersForRemoval = $users->users;

        array_walk(
            $usersForRemoval,
            function (User $user, int $index): void {
                $position = $index + 1;
                $this->documentManager->remove($user);

                if ($position % $this->batchSize === 0) {
                    $this->documentManager->flush();
                    $this->documentManager->clear();
                }
            }
        );
        $this->documentManager->flush();
        $this->documentManager->clear();
    }
}
