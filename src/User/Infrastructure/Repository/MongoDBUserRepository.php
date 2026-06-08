<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\User;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Exception\DuplicateEmailException;
use App\User\Domain\Repository\UserRepositoryInterface;

use function array_map;
use function array_unique;
use function array_values;

use Doctrine\Bundle\MongoDBBundle\ManagerRegistry;
use Doctrine\Bundle\MongoDBBundle\Repository\ServiceDocumentRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use InvalidArgumentException;

use function mb_strtolower;
use function preg_quote;
use function spl_object_id;
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
            $this->prepareForPersistence($user);
            $this->documentManager->persist($user);
            $this->documentManager->flush();
        } catch (Throwable $error) {
            $this->documentManager->detach($user);

            if (
                $user instanceof UserInterface
                && $this->isDuplicateEmailKeyError($error, $user->getEmail())
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
     * Matches trimmed and lowercase email candidates through the normalizedEmail index.
     */
    #[\Override]
    public function findByEmails(array $emails): UserCollection
    {
        $uniqueEmails = $this->uniqueNormalizedEmailCandidates($emails);

        if ($uniqueEmails === []) {
            return new UserCollection();
        }

        return $this->findByNormalizedEmails($uniqueEmails);
    }

    #[\Override]
    public function findByEmailCaseInsensitive(string $email): UserCollection
    {
        $normalizedEmail = $this->normalizeEmail($email);

        return $this->combineUserCollections(
            $this->findByNormalizedEmail($normalizedEmail),
            $this->findLegacyByNormalizedEmails([$normalizedEmail])
        );
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
    private function uniqueNormalizedEmailCandidates(array $emails): array
    {
        return array_values(array_unique(array_map(
            $this->normalizeEmail(...),
            $emails
        )));
    }

    private function isDuplicateEmailKeyError(Throwable $error, string $email): bool
    {
        return $this->isDuplicateKeyError($error)
            && str_contains($error->getMessage(), 'normalizedEmail')
            && str_contains($error->getMessage(), $this->normalizeEmail($email));
    }

    private function prepareForPersistence(object $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        $user->setNormalizedEmail($this->normalizeEmail($user->getEmail()));
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email), 'UTF-8');
    }

    /**
     * @param list<string> $normalizedEmails
     */
    private function findByNormalizedEmails(array $normalizedEmails): UserCollection
    {
        $result = $this->createQueryBuilder()
            ->field('normalizedEmail')->in($normalizedEmails)
            ->getQuery()
            ->execute();

        return $this->userCollectionFromResult($result);
    }

    private function findByNormalizedEmail(string $normalizedEmail): UserCollection
    {
        $result = $this->createQueryBuilder()
            ->field('normalizedEmail')
            ->equals($normalizedEmail)
            ->getQuery()
            ->execute();

        return $this->userCollectionFromResult($result);
    }

    /**
     * @param list<string> $normalizedEmails
     */
    private function findLegacyByNormalizedEmails(array $normalizedEmails): UserCollection
    {
        $queryBuilder = $this->createQueryBuilder();

        $result = $queryBuilder
            ->addAnd(
                $this->legacyNormalizedEmailExpression(),
                $this->legacyEmailExpression($normalizedEmails)
            )
            ->getQuery()
            ->execute();

        return $this->userCollectionFromResult($result);
    }

    /** @return array{'$or': list<array{normalizedEmail: array{'$exists': false}|string}>} */
    private function legacyNormalizedEmailExpression(): array
    {
        return [
            '$or' => [
                ['normalizedEmail' => ['$exists' => false]],
                ['normalizedEmail' => ''],
            ],
        ];
    }

    /**
     * @param list<string> $normalizedEmails
     *
     * @return array{'$or': list<array{email: array{'$regex': string, '$options': string}}>}
     */
    private function legacyEmailExpression(array $normalizedEmails): array
    {
        return [
            '$or' => array_map(
                /** @return array{email: array{'$regex': string, '$options': string}} */
                fn (string $normalizedEmail): array => [
                    'email' => $this->caseInsensitiveEmailMatch($normalizedEmail),
                ],
                $normalizedEmails
            ),
        ];
    }

    private function combineUserCollections(
        UserCollection $currentUsers,
        UserCollection $legacyUsers
    ): UserCollection {
        $users = [];
        $indexedUsers = [];

        foreach ([$currentUsers, $legacyUsers] as $collection) {
            foreach ($collection->users as $user) {
                $userId = $user->getId();
                $index = $userId !== ''
                    ? $userId
                    : 'object:' . spl_object_id($user);

                if (isset($indexedUsers[$index])) {
                    continue;
                }

                $indexedUsers[$index] = true;
                $users[] = $user;
            }
        }

        return new UserCollection($users);
    }

    /**
     * @return array{'$regex': string, '$options': string}
     */
    private function caseInsensitiveEmailMatch(string $email): array
    {
        return [
            '$regex' => '^' . preg_quote($email, '/') . '$',
            '$options' => 'i',
        ];
    }

    /**
     * @param iterable<object> $result
     */
    private function userCollectionFromResult(iterable $result): UserCollection
    {
        $users = [];

        foreach ($result as $user) {
            if (!$user instanceof UserInterface) {
                continue;
            }

            $users[] = $user;
        }

        return new UserCollection($users);
    }

    private function isDuplicateKeyError(Throwable $error): bool
    {
        return $error->getCode() === self::DUPLICATE_KEY_ERROR_CODE
            || str_contains($error->getMessage(), 'E11000');
    }

    private function persistUsersInBatch(UserCollection $users): void
    {
        $usersForPersistence = $users->users;
        $persistedUsers = new UserCollection();

        foreach ($usersForPersistence as $index => $user) {
            $position = $index + 1;
            $persistedUsers->add($user);
            $this->prepareForPersistence($user);
            $this->documentManager->persist($user);

            if ($position % $this->batchSize === 0) {
                $this->flushPersistedUsers($persistedUsers);
                $persistedUsers = new UserCollection();
            }
        }
        $this->flushPersistedUsers($persistedUsers);
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

    private function flushPersistedUsers(UserCollection $users): void
    {
        try {
            $this->documentManager->flush();
        } catch (Throwable $error) {
            $this->documentManager->clear();
            $this->throwDuplicateEmailExceptionForBatchFlush($error, $users);

            throw $error;
        }

        $this->documentManager->clear();
    }

    private function throwDuplicateEmailExceptionForBatchFlush(
        Throwable $error,
        UserCollection $users
    ): void {
        foreach ($users as $user) {
            if (!$this->isDuplicateEmailKeyError($error, $user->getEmail())) {
                continue;
            }

            throw new DuplicateEmailException($user->getEmail(), $error);
        }
    }
}
