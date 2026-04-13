<?php

declare(strict_types=1);

namespace App\Tests\Memory\GraphQL;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[Group('memory')]
#[Group('memory-graphql')]
final class GraphQLAuthMemoryTest extends GraphQLMemoryWebTestCase
{
    /**
     * @var list<string>
     */
    private const AUTH_MUTATION_TARGETS = [
        'graphQLCompleteTwoFactor',
        'graphQLConfirmPasswordReset',
        'graphQLConfirmTwoFactor',
        'graphQLDisableTwoFactor',
        'graphQLRefreshToken',
        'graphQLRegenerateRecoveryCodes',
        'graphQLRequestPasswordReset',
        'graphQLSetupTwoFactor',
        'graphQLSignin',
        'graphQLSignout',
        'graphQLSignoutAll',
    ];

    public static function authMutationTargets(): iterable
    {
        foreach (self::AUTH_MUTATION_TARGETS as $coverageTarget) {
            yield $coverageTarget => [$coverageTarget];
        }
    }

    public function testAuthMutationTargetsProviderEnumeratesEveryTarget(): void
    {
        $targets = array_keys(iterator_to_array(self::authMutationTargets()));

        $this->assertSame(self::AUTH_MUTATION_TARGETS, $targets);
    }

    public function testAuthMutationInventoryMatchesGraphQlLoadScripts(): void
    {
        $actual = $this->graphQlLoadScriptTargets();
        sort($actual);

        $expected = array_values(array_unique(array_merge(
            self::AUTH_MUTATION_TARGETS,
            GraphQLUserOperationMemoryTest::inventoryTargets(),
        )));
        sort($expected);

        $this->assertSame($expected, $actual);
    }

    #[DataProvider('authMutationTargets')]
    public function testGraphQlAuthMutationsStayStableAcrossRepeatedSameKernelRequests(
        string $coverageTarget,
    ): void {
        $this->runRepeatedGraphQlScenario(
            $coverageTarget,
            match ($coverageTarget) {
                'graphQLSignin' => $this->exerciseSignIn(...),
                'graphQLRefreshToken' => $this->exerciseRefreshToken(...),
                'graphQLSetupTwoFactor' => $this->exerciseSetupTwoFactor(...),
                'graphQLConfirmTwoFactor' => $this->exerciseConfirmTwoFactor(...),
                'graphQLDisableTwoFactor' => $this->exerciseDisableTwoFactor(...),
                'graphQLRegenerateRecoveryCodes' => $this->exerciseRegenerateRecoveryCodes(...),
                'graphQLSignout' => $this->exerciseSignOut(...),
                'graphQLSignoutAll' => $this->exerciseSignOutAll(...),
                'graphQLCompleteTwoFactor' => $this->exerciseCompleteTwoFactor(...),
                'graphQLRequestPasswordReset' => $this->exerciseRequestPasswordReset(...),
                'graphQLConfirmPasswordReset' => $this->exerciseConfirmPasswordReset(...),
            },
        );
    }

    private function exerciseSignIn(KernelBrowser $client, int $iteration): void
    {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-signin', $iteration),
        );

        $payload = $this->signInGraphQl(
            $client,
            $fixture['user']->getEmail(),
            $fixture['password'],
        );

        $this->assertSame(true, $payload['success'] ?? null);
        $this->assertSame(false, $payload['twoFactorEnabled'] ?? null);
        $this->assertIsString($payload['accessToken'] ?? null);
        $this->assertNotSame('', $payload['accessToken'] ?? null);
        $this->assertIsString($payload['refreshToken'] ?? null);
        $this->assertNotSame('', $payload['refreshToken'] ?? null);
    }

    private function exerciseRefreshToken(KernelBrowser $client, int $iteration): void
    {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-refresh', $iteration),
        );
        $signIn = $this->signInGraphQl(
            $client,
            $fixture['user']->getEmail(),
            $fixture['password'],
        );
        $refreshToken = $signIn['refreshToken'] ?? null;

        $this->assertIsString($refreshToken);
        $this->assertNotSame('', $refreshToken);

        $payload = $this->refreshTokenGraphQl($client, $refreshToken);

        $this->assertSame(true, $payload['success'] ?? null);
        $this->assertIsString($payload['accessToken'] ?? null);
        $this->assertNotSame('', $payload['accessToken'] ?? null);
        $this->assertIsString($payload['refreshToken'] ?? null);
        $this->assertNotSame('', $payload['refreshToken'] ?? null);
    }

    private function exerciseSetupTwoFactor(KernelBrowser $client, int $iteration): void
    {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-setup-2fa', $iteration),
        );
        $signIn = $this->signInGraphQl(
            $client,
            $fixture['user']->getEmail(),
            $fixture['password'],
        );
        $accessToken = $signIn['accessToken'] ?? null;

        $this->assertIsString($accessToken);
        $this->assertNotSame('', $accessToken);

        $payload = $this->setupTwoFactorGraphQl($client, $accessToken);

        $this->assertSame(true, $payload['success'] ?? null);
        $this->assertIsString($payload['secret'] ?? null);
        $this->assertNotSame('', $payload['secret'] ?? null);
        $this->assertIsString($payload['otpauthUri'] ?? null);
        $this->assertStringStartsWith('otpauth://', (string) ($payload['otpauthUri'] ?? ''));
    }

    private function exerciseConfirmTwoFactor(KernelBrowser $client, int $iteration): void
    {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-confirm-2fa', $iteration),
        );
        $signIn = $this->signInGraphQl(
            $client,
            $fixture['user']->getEmail(),
            $fixture['password'],
        );
        $accessToken = $signIn['accessToken'] ?? null;

        $this->assertIsString($accessToken);
        $this->assertNotSame('', $accessToken);

        $enabled = $this->enableTwoFactorGraphQl($client, $accessToken);

        $this->assertNotSame([], $enabled['recoveryCodes']);
    }

    private function exerciseDisableTwoFactor(KernelBrowser $client, int $iteration): void
    {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-disable-2fa', $iteration),
        );
        $signIn = $this->signInGraphQl(
            $client,
            $fixture['user']->getEmail(),
            $fixture['password'],
        );
        $accessToken = $signIn['accessToken'] ?? null;

        $this->assertIsString($accessToken);
        $this->assertNotSame('', $accessToken);

        $enabled = $this->enableTwoFactorGraphQl($client, $accessToken);
        $payload = $this->disableTwoFactorGraphQl(
            $client,
            $accessToken,
            $enabled['recoveryCodes'][0],
        );

        $this->assertSame(true, $payload['success'] ?? null);
    }

    private function exerciseRegenerateRecoveryCodes(
        KernelBrowser $client,
        int $iteration,
    ): void {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-regenerate-recovery', $iteration),
        );
        $signIn = $this->signInGraphQl(
            $client,
            $fixture['user']->getEmail(),
            $fixture['password'],
        );
        $accessToken = $signIn['accessToken'] ?? null;

        $this->assertIsString($accessToken);
        $this->assertNotSame('', $accessToken);

        $this->enableTwoFactorGraphQl($client, $accessToken);
        $payload = $this->regenerateRecoveryCodesGraphQl($client, $accessToken);

        $this->assertSame(true, $payload['success'] ?? null);
        $this->assertIsArray($payload['recoveryCodes'] ?? null);
        $this->assertNotSame([], $payload['recoveryCodes'] ?? null);
    }

    private function exerciseSignOut(KernelBrowser $client, int $iteration): void
    {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-signout', $iteration),
        );
        $signIn = $this->signInGraphQl(
            $client,
            $fixture['user']->getEmail(),
            $fixture['password'],
        );
        $accessToken = $signIn['accessToken'] ?? null;

        $this->assertIsString($accessToken);
        $this->assertNotSame('', $accessToken);

        $payload = $this->signOutGraphQl($client, $accessToken);

        $this->assertSame(true, $payload['success'] ?? null);
    }

    private function exerciseSignOutAll(KernelBrowser $client, int $iteration): void
    {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-signout-all', $iteration),
        );
        $signIn = $this->signInGraphQl(
            $client,
            $fixture['user']->getEmail(),
            $fixture['password'],
        );
        $accessToken = $signIn['accessToken'] ?? null;

        $this->assertIsString($accessToken);
        $this->assertNotSame('', $accessToken);

        $payload = $this->signOutAllGraphQl($client, $accessToken);

        $this->assertSame(true, $payload['success'] ?? null);
    }

    private function exerciseCompleteTwoFactor(KernelBrowser $client, int $iteration): void
    {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-complete-2fa', $iteration),
        );
        $firstSignIn = $this->signInGraphQl(
            $client,
            $fixture['user']->getEmail(),
            $fixture['password'],
        );
        $accessToken = $firstSignIn['accessToken'] ?? null;

        $this->assertIsString($accessToken);
        $this->assertNotSame('', $accessToken);

        $enabled = $this->enableTwoFactorGraphQl($client, $accessToken);
        $secondSignIn = $this->signInGraphQl(
            $client,
            $fixture['user']->getEmail(),
            $fixture['password'],
        );
        $pendingSessionId = $secondSignIn['pendingSessionId'] ?? null;

        $this->assertSame(true, $secondSignIn['twoFactorEnabled'] ?? null);
        $this->assertIsString($pendingSessionId);
        $this->assertNotSame('', $pendingSessionId);

        $payload = $this->completeTwoFactorGraphQl(
            $client,
            $pendingSessionId,
            $enabled['recoveryCodes'][0],
        );

        $this->assertSame(true, $payload['success'] ?? null);
        $this->assertSame(true, $payload['twoFactorEnabled'] ?? null);
        $this->assertIsString($payload['accessToken'] ?? null);
        $this->assertNotSame('', $payload['accessToken'] ?? null);
        $this->assertIsString($payload['refreshToken'] ?? null);
        $this->assertNotSame('', $payload['refreshToken'] ?? null);
    }

    private function exerciseRequestPasswordReset(
        KernelBrowser $client,
        int $iteration,
    ): void {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-request-reset', $iteration),
        );

        $data = $this->extractGraphQlData(
            $this->executeGraphQl(
                $client,
                $this->buildRequestPasswordResetMutation($fixture['user']->getEmail()),
            ),
            'requestPasswordResetUser',
        );

        $this->assertArrayHasKey('user', $data);
        $this->assertNull($data['user']);
    }

    private function exerciseConfirmPasswordReset(
        KernelBrowser $client,
        int $iteration,
    ): void {
        $fixture = $this->createUserFixture(
            email: $this->uniqueEmail('memory-graphql-confirm-reset', $iteration),
        );
        $token = $this->seedPasswordResetToken($fixture['user']);
        $newPassword = $this->generatePassword();
        $data = $this->extractGraphQlData(
            $this->executeGraphQl(
                $client,
                $this->buildConfirmPasswordResetMutation($token, $newPassword),
            ),
            'confirmPasswordResetUser',
        );

        $this->assertArrayHasKey('user', $data);
        $this->assertNull($data['user']);

        $signIn = $this->signInGraphQl(
            $client,
            $fixture['user']->getEmail(),
            $newPassword,
        );

        $this->assertSame(true, $signIn['success'] ?? null);
    }
}
