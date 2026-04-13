<?php

declare(strict_types=1);

namespace App\Tests\Memory\OAuth;

use App\OAuth\Domain\ValueObject\OAuthProvider;
use App\OAuth\Infrastructure\Provider\DeterministicOAuthProvider;
use App\User\Domain\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

#[Group('memory')]
#[Group('memory-oauth')]
final class OAuthSocialFlowMemoryTest extends OAuthSocialMemoryWebTestCase
{
    private const OAUTH_SOCIAL_TARGETS = [
        'oauthSocialCallbackAutoLinkExistingUser',
        'oauthSocialCallbackDirectSignIn',
        'oauthSocialCallbackProviderEmailUnavailable',
        'oauthSocialCallbackProviderMismatch',
        'oauthSocialCallbackReplay',
        'oauthSocialCallbackReturningLinkedUser',
        'oauthSocialCallbackTwoFactor',
        'oauthSocialCallbackUnverifiedProviderEmail',
        'oauthSocialInitiate',
    ];

    private const FEATURE_SCENARIOS = [
        'Social OAuth direct sign-in succeeds',
        'Social OAuth starts a 2FA challenge for linked users',
        'Replaying a consumed social OAuth callback fails',
        'Facebook social OAuth returns provider email unavailable',
    ];

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function socialTargets(): iterable
    {
        foreach (self::OAUTH_SOCIAL_TARGETS as $coverageTarget) {
            yield $coverageTarget => [$coverageTarget];
        }
    }

    public function testSocialTargetsProviderEnumeratesEveryTarget(): void
    {
        $targets = array_keys(iterator_to_array(self::socialTargets()));

        $this->assertSame(self::OAUTH_SOCIAL_TARGETS, $targets);
    }

    public function testOAuthSocialInventoryMatchesLoadAndFeatureSurface(): void
    {
        $this->assertSame(
            ['oauthSocialCallback', 'oauthSocialInitiate'],
            $this->oauthSocialRestLoadTargets(),
        );
        $this->assertSame(self::FEATURE_SCENARIOS, $this->oauthSocialFeatureScenarios());
    }

    #[DataProvider('socialTargets')]
    public function testOAuthSocialFlowsStayStableAcrossRepeatedSameKernelRequests(
        string $coverageTarget,
    ): void {
        $this->runRepeatedOAuthScenario(
            $coverageTarget,
            $this->scenarioHandler($coverageTarget),
        );
    }

    private function exerciseInitiate(KernelBrowser $client, int $iteration): void
    {
        $provider = $iteration % 2 === 0 ? 'github' : 'google';
        $flow = $this->initiateSocialFlow($client, $provider);

        $this->assertNotSame('', $flow['state']);
        $this->assertNotSame('', $flow['cookie']);
    }

    private function exerciseDirectSignIn(KernelBrowser $client, int $iteration): void
    {
        $provider = 'github';
        $flow = $this->initiateSocialFlow($client, $provider);
        $result = $this->completeSocialFlow(
            $client,
            $provider,
            $this->uniqueCode('memory-direct', $iteration),
            $flow['state'],
            $flow['cookie'],
        );

        $this->assertSame(200, $result['status']);
        $this->assertSame(false, $result['body']['2fa_enabled'] ?? null);
        $this->assertIsString($result['body']['access_token'] ?? null);
        $this->assertNotSame('', $result['body']['access_token'] ?? null);
        $this->assertIsString($result['body']['refresh_token'] ?? null);
        $this->assertNotSame('', $result['body']['refresh_token'] ?? null);
        $this->assertNotNull($result['responseCookie']);
    }

    private function exerciseTwoFactor(KernelBrowser $client, int $iteration): void
    {
        $provider = 'github';
        $code = $this->uniqueCode('memory-two-factor', $iteration);
        $email = DeterministicOAuthProvider::emailFor($provider, $code);
        $this->createLocalUser($email, true, true);
        $flow = $this->initiateSocialFlow($client, $provider);
        $result = $this->completeSocialFlow(
            $client,
            $provider,
            $code,
            $flow['state'],
            $flow['cookie'],
        );
        $pendingSessionId = $result['body']['pending_session_id'] ?? null;

        $this->assertSame(200, $result['status']);
        $this->assertSame(true, $result['body']['2fa_enabled'] ?? null);
        $this->assertIsString($pendingSessionId);
        $this->assertNotNull($this->pendingTwoFactorRepository->findById($pendingSessionId));
        $this->assertNull($result['responseCookie']);
    }

    private function exerciseReplay(KernelBrowser $client, int $iteration): void
    {
        $provider = 'google';
        $flow = $this->initiateSocialFlow($client, $provider);
        $code = $this->uniqueCode('memory-replay', $iteration);

        $this->completeSocialFlow(
            $client,
            $provider,
            $code,
            $flow['state'],
            $flow['cookie'],
        );
        $result = $this->completeSocialFlow(
            $client,
            $provider,
            $code,
            $flow['state'],
            $flow['cookie'],
        );

        $this->assertSame(422, $result['status']);
        $this->assertSame('invalid_state', $result['body']['error_code'] ?? null);
    }

    private function exerciseProviderEmailUnavailable(KernelBrowser $client): void
    {
        $provider = 'facebook';
        $flow = $this->initiateSocialFlow($client, $provider);
        $result = $this->completeSocialFlow(
            $client,
            $provider,
            'no-email',
            $flow['state'],
            $flow['cookie'],
        );

        $this->assertSame(422, $result['status']);
        $this->assertSame(
            'provider_email_unavailable',
            $result['body']['error_code'] ?? null,
        );
    }

    private function exerciseReturningLinkedUser(KernelBrowser $client, int $iteration): void
    {
        $provider = 'google';
        $code = $this->uniqueCode('memory-linked', $iteration);
        $user = $this->signInLinkedSocialUser($client, $provider, $code);

        $this->recordingOAuthPublisher->reset();
        $secondFlow = $this->initiateSocialFlow($client, $provider);
        $result = $this->completeSocialFlow(
            $client,
            $provider,
            $code,
            $secondFlow['state'],
            $secondFlow['cookie'],
        );

        $this->assertSame(200, $result['status']);
        $this->assertSame([], $this->recordingOAuthPublisher->createdEvents());
        $this->assertCount(1, $this->recordingOAuthPublisher->signedInEvents());
        $this->assertSame($user->getId(), $this->requireSocialUser($provider, $code)->getId());
    }

    private function exerciseAutoLinkExistingUser(
        KernelBrowser $client,
        int $iteration,
    ): void {
        $provider = 'github';
        $code = $this->uniqueCode('memory-auto-link', $iteration);
        $email = DeterministicOAuthProvider::emailFor($provider, $code);
        $existingUser = $this->createLocalUser($email, false, false);
        $result = $this->completeInitiatedFlow($client, $provider, $code);

        $this->assertSame(200, $result['status']);
        $this->assertSame(
            $existingUser->getId(),
            $this->requireUserByEmail($email)->getId(),
        );
        $this->assertNotNull(
            $this->socialIdentityRepository->findByUserIdAndProvider(
                $existingUser->getId(),
                OAuthProvider::fromString($provider),
            ),
        );
    }

    private function exerciseProviderMismatch(KernelBrowser $client, int $iteration): void
    {
        $flow = $this->initiateSocialFlow($client, 'github');
        $result = $this->completeSocialFlow(
            $client,
            'google',
            $this->uniqueCode('memory-provider-mismatch', $iteration),
            $flow['state'],
            $flow['cookie'],
        );

        $this->assertSame(400, $result['status']);
        $this->assertSame('provider_mismatch', $result['body']['error_code'] ?? null);
    }

    private function exerciseUnverifiedProviderEmail(KernelBrowser $client): void
    {
        $provider = 'google';
        $flow = $this->initiateSocialFlow($client, $provider);
        $result = $this->completeSocialFlow(
            $client,
            $provider,
            'unverified-email',
            $flow['state'],
            $flow['cookie'],
        );

        $this->assertSame(422, $result['status']);
        $this->assertSame(
            'unverified_provider_email',
            $result['body']['error_code'] ?? null,
        );
    }

    /**
     * @return array{status: int, body: array<string, array|bool|float|int|string|null>, responseCookie: \Symfony\Component\HttpFoundation\Cookie|null}
     */
    private function completeInitiatedFlow(
        KernelBrowser $client,
        string $provider,
        string $code,
    ): array {
        $flow = $this->initiateSocialFlow($client, $provider);

        return $this->completeSocialFlow(
            $client,
            $provider,
            $code,
            $flow['state'],
            $flow['cookie'],
        );
    }

    private function signInLinkedSocialUser(
        KernelBrowser $client,
        string $provider,
        string $code,
    ): User {
        $this->completeInitiatedFlow($client, $provider, $code);

        return $this->requireSocialUser($provider, $code);
    }

    private function requireSocialUser(string $provider, string $code): User
    {
        return $this->requireUserByEmail(DeterministicOAuthProvider::emailFor($provider, $code));
    }

    private function scenarioHandler(string $coverageTarget): callable
    {
        return match ($coverageTarget) {
            'oauthSocialInitiate' => $this->exerciseInitiate(...),
            'oauthSocialCallbackDirectSignIn' => $this->exerciseDirectSignIn(...),
            'oauthSocialCallbackTwoFactor' => $this->exerciseTwoFactor(...),
            'oauthSocialCallbackReplay' => $this->exerciseReplay(...),
            'oauthSocialCallbackProviderEmailUnavailable' => function (
                KernelBrowser $client,
                int $iteration,
            ): void {
                self::assertGreaterThanOrEqual(0, $iteration);
                $this->exerciseProviderEmailUnavailable($client);
            },
            'oauthSocialCallbackReturningLinkedUser' => $this->exerciseReturningLinkedUser(...),
            'oauthSocialCallbackAutoLinkExistingUser' => $this->exerciseAutoLinkExistingUser(...),
            'oauthSocialCallbackProviderMismatch' => $this->exerciseProviderMismatch(...),
            'oauthSocialCallbackUnverifiedProviderEmail' => function (
                KernelBrowser $client,
                int $iteration,
            ): void {
                self::assertGreaterThanOrEqual(0, $iteration);
                $this->exerciseUnverifiedProviderEmail($client);
            },
        };
    }
}
