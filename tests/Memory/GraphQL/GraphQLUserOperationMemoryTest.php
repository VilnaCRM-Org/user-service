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
    /**
     * @var list<string>
     */
    private const USER_OPERATION_TARGETS = [
        'graphQLConfirmUser',
        'graphQLCreateUser',
        'graphQLDeleteUser',
        'graphQLGetUser',
        'graphQLGetUsers',
        'graphQLResendEmailToUser',
        'graphQLUpdateUser',
    ];

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

        $this->assertSame(
            $newUser->toArray()['email'],
            $payload['email'] ?? null,
        );
    }

    private function exerciseUpdateUser(KernelBrowser $client, int $iteration): void
    {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-update', $iteration),
        );
        $updatedEmail = $this->uniqueEmail('memory-graphql-updated', $iteration);
        $updatedPassword = $this->generatePassword();
        $accessToken = $this->issueAccessTokenForUser($fixture['user']);
        $payload = $this->extractGraphQlUserPayload(
            $this->executeGraphQl(
                $client,
                $this->buildUpdateUserMutation(
                    $fixture['user']->getId(),
                    $updatedEmail,
                    $fixture['password'],
                    $updatedPassword,
                    'UP',
                ),
                $accessToken,
            ),
            'updateUser',
        );

        $this->assertSame(
            $this->buildGetUserId($fixture['user']->getId()),
            $payload['id'] ?? null,
        );
        $this->assertSame(
            $updatedEmail,
            $payload['email'] ?? null,
        );
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
        $owner = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-get-users-owner', $iteration),
        );
        $peer = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-get-users-peer', $iteration),
        );
        $this->assertNotSame($owner['user']->getId(), $peer['user']->getId());
        $accessToken = $this->issueAccessTokenForUser($owner['user']);
        $payload = $this->extractGraphQlData(
            $this->executeGraphQl(
                $client,
                $this->buildGetUsersQuery(100),
                $accessToken,
            ),
            'users',
        );

        $edges = $payload['edges'] ?? null;

        $this->assertIsArray($edges);
        $this->assertNotSame([], $edges);
        $returnedUsers = array_map(
            static fn (array $edge): array => [
                'id' => (string) ($edge['node']['id'] ?? ''),
                'email' => (string) ($edge['node']['email'] ?? ''),
            ],
            $edges,
        );

        $this->assertContainsOnly('array', $returnedUsers);

        foreach ($returnedUsers as $returnedUser) {
            $this->assertArrayHasKey('id', $returnedUser);
            $this->assertArrayHasKey('email', $returnedUser);
            $this->assertStringStartsWith('/api/users/', $returnedUser['id']);
            $this->assertNotSame('', $returnedUser['email']);
        }
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
}
