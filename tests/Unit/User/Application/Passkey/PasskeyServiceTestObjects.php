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

final class PasskeyServiceTestObjects
{
    public function createAuthenticationChallenge(?string $userId): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            'challenge-id',
            PasskeyChallenge::PURPOSE_AUTHENTICATION,
            'challenge',
            '{}',
            $createdAt,
            $createdAt->modify('+5 minutes'),
            $this->createAuthenticationContext($userId)
        );
    }

    public function createAuthenticationContext(?string $userId): PasskeyChallengeContext
    {
        return (new PasskeyChallengeContext(
            'person@example.com',
            userId: $userId
        ))->withRememberMe();
    }

    public function createCredential(string $userId): PasskeyCredential
    {
        return new PasskeyCredential(
            'passkey-id',
            $userId,
            (new PasskeyEncoding())->encode('raw-credential-id'),
            '{"record":false}',
            'Laptop',
            new DateTimeImmutable()
        );
    }

    public function createSignupChallenge(): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            'challenge-id',
            PasskeyChallenge::PURPOSE_SIGNUP,
            'challenge',
            '{}',
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext(
                'new@example.com',
                'NE',
                'New Example',
                '018f33bb-1111-7222-8333-111111111111'
            )
        );
    }

    public function createIncompleteSignupChallenge(): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            'challenge-id',
            PasskeyChallenge::PURPOSE_SIGNUP,
            'challenge',
            '{}',
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext('new@example.com')
        );
    }

    public function createRegistrationChallenge(string $userId): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            'challenge-id',
            PasskeyChallenge::PURPOSE_REGISTRATION,
            'challenge',
            '{}',
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext(
                'person@example.com',
                displayName: 'Person Example',
                userId: $userId
            )
        );
    }

    public function createUser(string $id, string $email): User
    {
        return new User(
            $email,
            'PE',
            'hashed-password',
            (new UuidTransformer(new SharedUuidFactory()))->transformFromString($id)
        );
    }
}
