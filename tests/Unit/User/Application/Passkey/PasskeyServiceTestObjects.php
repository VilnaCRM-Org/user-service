<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Passkey;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Application\Passkey\PasskeyEncoding;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;
use Faker\Generator;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
final class PasskeyServiceTestObjects
{
    private readonly string $accessToken;
    private readonly string $authenticationEmail;
    private readonly string $challenge;
    private readonly string $challengeId;
    private readonly string $credentialId;
    private readonly string $credentialLabel;
    private readonly string $credentialRecord;
    private readonly string $hashedPassword;
    private readonly string $ipAddress;
    private readonly string $origin;
    private readonly string $passkeyId;
    private readonly string $rawCredentialId;
    private readonly string $refreshToken;
    private readonly string $registrationDisplayName;
    private readonly string $rpId;
    private readonly string $rpName;
    private readonly string $sessionId;
    private readonly string $signupDisplayName;
    private readonly string $signupEmail;
    private readonly string $signupInitials;
    private readonly string $signupLabel;
    private readonly string $signupUserId;
    private readonly string $unknownEmail;
    private readonly string $userAgent;
    private readonly string $userInitials;
    private readonly string $userPassword;

    public function __construct(private readonly Generator $faker)
    {
        $this->createTokenFixtures();
        $this->createCredentialFixtures();
        $this->createUserFixtures();
    }

    public function createAuthenticationChallenge(?string $userId): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            $this->challengeId,
            PasskeyChallenge::PURPOSE_AUTHENTICATION,
            $this->challenge,
            $this->optionsJson(),
            $createdAt,
            $createdAt->modify('+5 minutes'),
            $this->createAuthenticationContext($userId)
        );
    }

    public function createAuthenticationContext(?string $userId): PasskeyChallengeContext
    {
        return (new PasskeyChallengeContext(
            $this->authenticationEmail,
            userId: $userId
        ))->withRememberMe();
    }

    public function createCredential(string $userId): PasskeyCredential
    {
        return new PasskeyCredential(
            $this->passkeyId,
            $userId,
            $this->credentialId,
            $this->credentialRecord,
            $this->credentialLabel,
            new DateTimeImmutable()
        );
    }

    public function createSignupChallenge(): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            $this->challengeId,
            PasskeyChallenge::PURPOSE_SIGNUP,
            $this->challenge,
            $this->optionsJson(),
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext(
                $this->signupEmail,
                $this->signupInitials,
                $this->signupDisplayName,
                $this->signupUserId
            )
        );
    }

    public function createIncompleteSignupChallenge(): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            $this->challengeId,
            PasskeyChallenge::PURPOSE_SIGNUP,
            $this->challenge,
            $this->optionsJson(),
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext($this->signupEmail)
        );
    }

    public function createRegistrationChallenge(string $userId): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            $this->challengeId,
            PasskeyChallenge::PURPOSE_REGISTRATION,
            $this->challenge,
            $this->optionsJson(),
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext(
                $this->authenticationEmail,
                displayName: $this->registrationDisplayName,
                userId: $userId
            )
        );
    }

    public function createUser(string $id, string $email): User
    {
        return new User(
            $email,
            $this->userInitials,
            $this->userPassword,
            (new UuidTransformer(new SharedUuidFactory()))->transformFromString($id)
        );
    }

    public function accessToken(): string
    {
        return $this->accessToken;
    }

    public function authenticationEmail(): string
    {
        return $this->authenticationEmail;
    }

    public function challengeId(): string
    {
        return $this->challengeId;
    }

    public function credentialId(): string
    {
        return $this->credentialId;
    }

    public function credentialLabel(): string
    {
        return $this->credentialLabel;
    }

    public function credentialRecord(): string
    {
        return $this->credentialRecord;
    }

    public function hashedPassword(): string
    {
        return $this->hashedPassword;
    }

    public function ipAddress(): string
    {
        return $this->ipAddress;
    }

    public function origin(): string
    {
        return $this->origin;
    }

    public function passkeyId(): string
    {
        return $this->passkeyId;
    }

    public function rawCredentialId(): string
    {
        return $this->rawCredentialId;
    }

    public function refreshToken(): string
    {
        return $this->refreshToken;
    }

    public function rpId(): string
    {
        return $this->rpId;
    }

    public function rpName(): string
    {
        return $this->rpName;
    }

    public function sessionId(): string
    {
        return $this->sessionId;
    }

    public function signupEmail(): string
    {
        return $this->signupEmail;
    }

    public function signupInitials(): string
    {
        return $this->signupInitials;
    }

    public function signupDisplayName(): string
    {
        return $this->signupDisplayName;
    }

    public function signupLabel(): string
    {
        return $this->signupLabel;
    }

    public function signupUserId(): string
    {
        return $this->signupUserId;
    }

    public function unknownEmail(): string
    {
        return $this->unknownEmail;
    }

    public function userAgent(): string
    {
        return $this->userAgent;
    }

    private function createTokenFixtures(): void
    {
        $this->accessToken = $this->faker->sha256();
        $this->challenge = $this->faker->sha256();
        $this->challengeId = $this->faker->uuid();
        $this->refreshToken = $this->faker->sha256();
        $this->sessionId = $this->faker->uuid();
    }

    private function createCredentialFixtures(): void
    {
        $this->credentialLabel = $this->faker->words(2, true);
        $this->credentialRecord = json_encode(['record' => true], JSON_THROW_ON_ERROR);
        $this->passkeyId = $this->faker->uuid();
        $this->rawCredentialId = $this->faker->sha256();
        $this->credentialId = (new PasskeyEncoding())->encode($this->rawCredentialId);
    }

    private function createUserFixtures(): void
    {
        $this->authenticationEmail = $this->faker->unique()->safeEmail();
        $this->hashedPassword = $this->faker->password();
        $this->ipAddress = $this->faker->ipv4();
        $this->rpId = $this->faker->domainName();
        $this->rpName = $this->faker->company();
        $this->origin = sprintf('https://%s', $this->rpId);
        $this->registrationDisplayName = $this->faker->name();
        $this->signupDisplayName = $this->faker->name();
        $this->signupEmail = $this->faker->unique()->safeEmail();
        $this->signupInitials = strtoupper($this->faker->lexify('??'));
        $this->signupLabel = $this->faker->words(2, true);
        $this->signupUserId = $this->faker->uuid();
        $this->unknownEmail = $this->faker->unique()->safeEmail();
        $this->userAgent = $this->faker->userAgent();
        $this->userInitials = strtoupper($this->faker->lexify('??'));
        $this->userPassword = $this->faker->password();
    }

    private function optionsJson(): string
    {
        return json_encode(['challenge' => $this->challenge], JSON_THROW_ON_ERROR);
    }
}
