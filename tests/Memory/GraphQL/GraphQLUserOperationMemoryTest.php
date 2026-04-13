<?php

declare(strict_types=1);

namespace App\Tests\Memory\GraphQL;

use App\Tests\Behat\UserGraphQLContext\Input\CreateUserGraphQLMutationInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[Group('memory')]
#[Group('memory-graphql')]
final class GraphQLUserOperationMemoryTest extends GraphQLMemoryWebTestCase
{
    private const USER_OPERATION_TARGETS = [
        'graphQLConfirmUser',
        'graphQLCreateUser',
        'graphQLDeleteUser',
        'graphQLGetUser',
        'graphQLGetUsers',
        'graphQLResendEmailToUser',
        'graphQLUpdateUser',
    ];

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function userOperationTargets(): iterable
    {
        foreach (self::USER_OPERATION_TARGETS as $coverageTarget) {
            yield $coverageTarget => [$coverageTarget];
        }
    }

    public function testUserOperationTargetsProviderEnumeratesEveryTarget(): void
    {
        $targets = array_keys(iterator_to_array(self::userOperationTargets()));

        $this->assertSame(self::USER_OPERATION_TARGETS, $targets);
    }

    public function testUserOperationInventoryMatchesGraphQlLoadScripts(): void
    {
        $actual = array_values(
            array_filter(
                $this->graphQlLoadScriptTargets(),
                static fn (string $target): bool => in_array(
                    $target,
                    self::USER_OPERATION_TARGETS,
                    true,
                ),
            ),
        );
        sort($actual);

        $expected = self::USER_OPERATION_TARGETS;
        sort($expected);

        $this->assertSame($expected, $actual);
    }

    /**
     * @return list<string>
     */
    public static function inventoryTargets(): array
    {
        return self::USER_OPERATION_TARGETS;
    }

    #[DataProvider('userOperationTargets')]
    public function testGraphQlUserOperationsStayStableAcrossRepeatedSameKernelRequests(
        string $coverageTarget,
    ): void {
        $this->runRepeatedGraphQlScenario(
            $coverageTarget,
            match ($coverageTarget) {
                'graphQLCreateUser' => $this->exerciseCreateUser(...),
                'graphQLUpdateUser' => $this->exerciseUpdateUser(...),
                'graphQLDeleteUser' => $this->exerciseDeleteUser(...),
                'graphQLGetUser' => $this->exerciseGetUser(...),
                'graphQLGetUsers' => $this->exerciseGetUsers(...),
                'graphQLConfirmUser' => $this->exerciseConfirmUser(...),
                'graphQLResendEmailToUser' => $this->exerciseResendEmailToUser(...),
            },
        );
    }

    private function exerciseCreateUser(KernelBrowser $client, int $iteration): void
    {
        $operator = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-create-operator', $iteration),
        );
        $accessToken = $this->issueAccessTokenForUser($operator['user']);
        $newUser = new CreateUserGraphQLMutationInput(
            $this->uniqueEmail('memory-graphql-create-target', $iteration),
            'MU',
            $this->generatePassword(),
        );

        $payload = $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                $this->buildCreateUserMutation($newUser),
                $accessToken,
            ),
            'createUser',
        );

        $this->assertSame($newUser->toArray()['email'], $payload['email'] ?? null);
    }

    private function exerciseUpdateUser(KernelBrowser $client, int $iteration): void
    {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-update', $iteration),
        );
        $updatedEmail = $this->uniqueEmail('memory-graphql-updated', $iteration);
        $payload = $this->updateUserPayload($client, $fixture, $updatedEmail);

        $this->assertReturnedUser($payload, $fixture['user']->getId(), $updatedEmail);
    }

    private function exerciseDeleteUser(KernelBrowser $client, int $iteration): void
    {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-delete', $iteration),
        );
        $accessToken = $this->issueAccessTokenForUser($fixture['user']);
        $payload = $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                $this->buildDeleteUserMutation($fixture['user']->getId()),
                $accessToken,
            ),
            'deleteUser',
        );

        $this->assertSame(
            $this->buildGetUserId($fixture['user']->getId()),
            $payload['id'] ?? null,
        );
    }

    private function exerciseGetUser(KernelBrowser $client, int $iteration): void
    {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-get-user', $iteration),
        );
        $accessToken = $this->issueAccessTokenForUser($fixture['user']);
        $payload = $this->extractGraphQlData(
            $this->executeGraphQl(
                $client,
                $this->buildGetUserQuery($fixture['user']->getId()),
                $accessToken,
            ),
            'user',
        );

        $this->assertSame(
            $this->buildGetUserId($fixture['user']->getId()),
            $payload['id'] ?? null,
        );
        $this->assertSame($fixture['user']->getEmail(), $payload['email'] ?? null);
    }

    private function exerciseGetUsers(KernelBrowser $client, int $iteration): void
    {
        ['owner' => $owner, 'peer' => $peer] = $this->createGetUsersFixtures($iteration);
        $returnedUsers = $this->fetchReturnedUsers($client, $owner['user']);

        $this->assertReturnedUsersAreWellFormed($returnedUsers);
        $this->assertReturnedFixturesArePresent($returnedUsers, $owner, $peer);
    }

    private function exerciseConfirmUser(KernelBrowser $client, int $iteration): void
    {
        $fixture = $this->createUserFixture(
            confirmed: false,
            email: $this->uniqueEmail('memory-graphql-confirm-user', $iteration),
        );
        $token = $this->seedConfirmationToken($fixture['user']);
        $payload = $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                $this->buildConfirmUserMutation($token),
            ),
            'confirmUser',
        );

        $this->assertSame(
            $this->buildGetUserId($fixture['user']->getId()),
            $payload['id'] ?? null,
        );
        $this->assertTrue(
            (bool) $this->userRepository->findByEmail($fixture['user']->getEmail())?->isConfirmed(),
        );
    }

    private function exerciseResendEmailToUser(KernelBrowser $client, int $iteration): void
    {
        $fixture = $this->createUserFixture(
            confirmed: false,
            email: $this->uniqueEmail('memory-graphql-resend-email', $iteration),
        );
        $accessToken = $this->issueAccessTokenForUser($fixture['user']);
        $payload = $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                $this->buildResendEmailMutation($fixture['user']->getId()),
                $accessToken,
            ),
            'resendEmailToUser',
        );

        $this->assertSame(
            $this->buildGetUserId($fixture['user']->getId()),
            $payload['id'] ?? null,
        );
    }

    private function buildGetUserId(string $userId): string
    {
        return sprintf('/api/users/%s', $userId);
    }

    /**
     * @param array<string, array|bool|float|int|string|null> $payload
     */
    private function assertReturnedUser(array $payload, string $userId, string $email): void
    {
        $this->assertSame($this->buildGetUserId($userId), $payload['id'] ?? null);
        $this->assertSame($email, $payload['email'] ?? null);
    }

    /**
     * @param array{user: \App\User\Domain\Entity\User, password: string} $fixture
     *
     * @return array<string, array|bool|float|int|string|null>
     */
    private function updateUserPayload(
        KernelBrowser $client,
        array $fixture,
        string $updatedEmail,
    ): array {
        return $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                $this->buildUpdateUserMutation(
                    $fixture['user']->getId(),
                    $updatedEmail,
                    $fixture['password'],
                    $this->generatePassword(),
                    'UP',
                ),
                $this->issueAccessTokenForUser($fixture['user']),
            ),
            'updateUser',
        );
    }

    /**
     * @param list<array{id: string, email: string}> $returnedUsers
     */
    private function assertReturnedUsersAreWellFormed(array $returnedUsers): void
    {
        foreach ($returnedUsers as $returnedUser) {
            $this->assertStringStartsWith('/api/users/', $returnedUser['id']);
            $this->assertNotSame('', $returnedUser['email']);
        }
    }

    /**
     * @param list<array{node: array{id?: string, email?: string}}> $edges
     *
     * @return list<array{id: string, email: string}>
     */
    private function returnedUsersFromEdges(array $edges): array
    {
        $returnedUsers = array_map(
            static fn (array $edge): array => [
                'id' => (string) ($edge['node']['id'] ?? ''),
                'email' => (string) ($edge['node']['email'] ?? ''),
            ],
            $edges,
        );

        $this->assertContainsOnly('array', $returnedUsers);

        return $returnedUsers;
    }

    /**
     * @return array{
     *     owner: array{user: \App\User\Domain\Entity\User, password: string},
     *     peer: array{user: \App\User\Domain\Entity\User, password: string}
     * }
     */
    private function createGetUsersFixtures(int $iteration): array
    {
        $owner = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-get-users-owner', $iteration),
        );
        $peer = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-get-users-peer', $iteration),
        );

        $this->assertNotSame($owner['user']->getId(), $peer['user']->getId());

        return [
            'owner' => $owner,
            'peer' => $peer,
        ];
    }

    /**
     * @return list<array{id: string, email: string}>
     */
    private function fetchReturnedUsers(
        KernelBrowser $client,
        \App\User\Domain\Entity\User $owner,
    ): array {
        $payload = $this->extractGraphQlData(
            $this->executeGraphQl(
                $client,
                $this->buildGetUsersQuery(100),
                $this->issueAccessTokenForUser($owner),
            ),
            'users',
        );
        $edges = $payload['edges'] ?? null;

        $this->assertIsArray($edges);
        $this->assertNotSame([], $edges);

        return $this->returnedUsersFromEdges($edges);
    }

    /**
     * @param list<array{id: string, email: string}> $returnedUsers
     */
    private function assertReturnedUsersContain(
        array $returnedUsers,
        string $userId,
        string $email,
    ): void {
        $expectedId = $this->buildGetUserId($userId);
        $matchesFixture = array_filter(
            $returnedUsers,
            static fn (array $returnedUser): bool => $returnedUser['id'] === $expectedId
                && $returnedUser['email'] === $email,
        );

        $this->assertNotSame([], $matchesFixture);
    }

    /**
     * @param array{user: \App\User\Domain\Entity\User, password: string} $owner
     * @param array{user: \App\User\Domain\Entity\User, password: string} $peer
     * @param list<array{id: string, email: string}> $returnedUsers
     */
    private function assertReturnedFixturesArePresent(
        array $returnedUsers,
        array $owner,
        array $peer,
    ): void {
        $this->assertReturnedUsersContain(
            $returnedUsers,
            $owner['user']->getId(),
            $owner['user']->getEmail(),
        );
        $this->assertReturnedUsersContain(
            $returnedUsers,
            $peer['user']->getId(),
            $peer['user']->getEmail(),
        );
    }
}
