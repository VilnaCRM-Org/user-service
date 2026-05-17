<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\User\Application\Transformer\PasskeyEncodingTransformer;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\Entity\User;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;
use Faker\Generator;

final class PasskeyCommandHandlerTestObjects
{
    /** @var array<string, string> */
    private array $tokens;
    /** @var array<string, string> */
    private array $credentials;
    /** @var array<string, string> */
    private array $users;

    public function __construct(private readonly Generator $faker)
    {
        $this->tokens = $this->createTokenFixtures();
        $this->credentials = $this->createCredentialFixtures();
        $this->users = $this->createUserFixtures();
    }

    public function createAuthenticationChallenge(?string $userId): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            $this->token('challengeId'),
            PasskeyChallenge::PURPOSE_AUTHENTICATION,
            $this->token('challenge'),
            $this->optionsJson(),
            $createdAt,
            $createdAt->modify('+5 minutes'),
            $this->createAuthenticationContext($userId)
        );
    }

    public function createAuthenticationContext(?string $userId): PasskeyChallengeContext
    {
        return (new PasskeyChallengeContext(
            $this->user('authenticationEmail'),
            userId: $userId
        ))->withRememberMe();
    }

    public function createCredential(string $userId): PasskeyCredential
    {
        return new PasskeyCredential(
            $this->credential('passkeyId'),
            $userId,
            $this->credential('credentialId'),
            $this->credential('credentialRecord'),
            $this->credential('credentialLabel'),
            new DateTimeImmutable()
        );
    }

    public function createSignupChallenge(): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            $this->token('challengeId'),
            PasskeyChallenge::PURPOSE_SIGNUP,
            $this->token('challenge'),
            $this->optionsJson(),
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext(
                $this->user('signupEmail'),
                $this->user('signupInitials'),
                $this->user('signupDisplayName'),
                $this->user('signupUserId')
            )
        );
    }

    public function createIncompleteSignupChallenge(): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            $this->token('challengeId'),
            PasskeyChallenge::PURPOSE_SIGNUP,
            $this->token('challenge'),
            $this->optionsJson(),
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext($this->user('signupEmail'))
        );
    }

    public function createRegistrationChallenge(string $userId): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            $this->token('challengeId'),
            PasskeyChallenge::PURPOSE_REGISTRATION,
            $this->token('challenge'),
            $this->optionsJson(),
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext(
                $this->user('authenticationEmail'),
                displayName: $this->user('registrationDisplayName'),
                userId: $userId
            )
        );
    }

    public function createUser(string $id, string $email): User
    {
        return new User(
            $email,
            $this->user('userInitials'),
            $this->user('userPassword'),
            (new UuidTransformer(new SharedUuidFactory()))->transformFromString($id)
        );
    }

    public function token(string $name): string
    {
        return $this->tokens[$name];
    }

    public function credential(string $name): string
    {
        return $this->credentials[$name];
    }

    public function user(string $name): string
    {
        return $this->users[$name];
    }

    /**
     * @return array<string, string>
     */
    private function createTokenFixtures(): array
    {
        return [
            'accessToken' => $this->faker->sha256(),
            'challenge' => $this->faker->sha256(),
            'challengeId' => $this->faker->uuid(),
            'refreshToken' => $this->faker->sha256(),
            'sessionId' => $this->faker->uuid(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function createCredentialFixtures(): array
    {
        $rawCredentialId = $this->faker->sha256();

        return [
            'credentialLabel' => $this->faker->words(2, true),
            'credentialRecord' => json_encode(['record' => true], JSON_THROW_ON_ERROR),
            'passkeyId' => $this->faker->uuid(),
            'rawCredentialId' => $rawCredentialId,
            'credentialId' => (new PasskeyEncodingTransformer())->encode($rawCredentialId),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function createUserFixtures(): array
    {
        $rpId = $this->faker->domainName();

        return [
            'authenticationEmail' => $this->faker->unique()->safeEmail(),
            'hashedPassword' => $this->faker->password(),
            'ipAddress' => $this->faker->ipv4(),
            'origin' => sprintf('https://%s', $rpId),
            'registrationDisplayName' => $this->faker->name(),
            'rpId' => $rpId,
            'rpName' => $this->faker->company(),
            'signupDisplayName' => $this->faker->name(),
            'signupEmail' => $this->faker->unique()->safeEmail(),
            'signupInitials' => strtoupper($this->faker->lexify('??')),
            'signupLabel' => $this->faker->words(2, true),
            'signupUserId' => $this->faker->uuid(),
            'unknownEmail' => $this->faker->unique()->safeEmail(),
            'userAgent' => $this->faker->userAgent(),
            'userInitials' => strtoupper($this->faker->lexify('??')),
            'userPassword' => $this->faker->password(),
        ];
    }

    private function optionsJson(): string
    {
        return json_encode(['challenge' => $this->token('challenge')], JSON_THROW_ON_ERROR);
    }
}
