<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Passkey;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Application\DTO\PasskeyRegistrationCompleteDto;
use App\User\Application\DTO\PasskeyRegistrationOptionsDto;
use App\User\Application\DTO\PasskeySignInCompleteDto;
use App\User\Application\DTO\PasskeySignInOptionsDto;
use App\User\Application\DTO\PasskeySignUpCompleteDto;
use App\User\Application\DTO\PasskeySignUpOptionsDto;
use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;

final class PasskeyDtoTest extends UnitTestCase
{
    public function testSignUpOptionsExposeInputValues(): void
    {
        $dto = new PasskeySignUpOptionsDto('person@example.com', 'PE', 'Person Example');

        self::assertSame('person@example.com', $dto->emailValue());
        self::assertSame('PE', $dto->initialsValue());
        self::assertSame('Person Example', $dto->displayNameValue());
    }

    public function testSignUpCompleteExposesCredentialAndRememberMe(): void
    {
        $credential = ['id' => 'credential-id'];
        $dto = new PasskeySignUpCompleteDto('challenge-id', $credential, 'Work laptop');

        self::assertFalse($dto->isRememberMe());
        $dto->setRememberMe(true);

        self::assertSame('challenge-id', $dto->challengeIdValue());
        self::assertSame($credential, $dto->credentialValue());
        self::assertSame('Work laptop', $dto->labelValue());
        self::assertTrue($dto->isRememberMe());
    }

    public function testSignInOptionsExposeEmailAndRememberMe(): void
    {
        $dto = new PasskeySignInOptionsDto('person@example.com');

        self::assertFalse($dto->isRememberMe());
        $dto->setRememberMe(true);

        self::assertSame('person@example.com', $dto->emailValue());
        self::assertTrue($dto->isRememberMe());
    }

    public function testCompleteDtosExposeCredentialValues(): void
    {
        $credential = ['id' => 'credential-id'];
        $signIn = new PasskeySignInCompleteDto('signin-challenge', $credential);
        $registration = new PasskeyRegistrationCompleteDto(
            'registration-challenge',
            $credential,
            'Security key'
        );

        self::assertSame('signin-challenge', $signIn->challengeIdValue());
        self::assertSame($credential, $signIn->credentialValue());
        self::assertSame('registration-challenge', $registration->challengeIdValue());
        self::assertSame($credential, $registration->credentialValue());
        self::assertSame('Security key', $registration->labelValue());
    }

    public function testResultDtosExposeValues(): void
    {
        $challenge = $this->createChallenge();
        $options = ['rpId' => 'localhost'];
        $optionsResult = new PasskeyOptionsResult($challenge, $options);
        $authResult = new PasskeyAuthenticationResult('access-token', 'refresh-token', true);
        $verified = new VerifiedPasskeyCredential('credential-id', '{"record":true}');

        self::assertInstanceOf(
            PasskeyRegistrationOptionsDto::class,
            new PasskeyRegistrationOptionsDto()
        );
        self::assertSame($challenge, $optionsResult->getChallenge());
        self::assertSame($options, $optionsResult->getPublicKeyOptions());
        self::assertSame('access-token', $authResult->getAccessToken());
        self::assertSame('refresh-token', $authResult->getRefreshToken());
        self::assertTrue($authResult->isRememberMe());
        self::assertSame('credential-id', $verified->getCredentialId());
        self::assertSame('{"record":true}', $verified->getCredentialRecord());
    }

    private function createChallenge(): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            'challenge-id',
            PasskeyChallenge::PURPOSE_AUTHENTICATION,
            'challenge',
            '{}',
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext('person@example.com', userId: 'user-id')
        );
    }
}
