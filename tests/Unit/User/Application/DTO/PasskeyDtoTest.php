<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\DTO;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\PasskeyAuthenticationResult;
use App\User\Application\DTO\PasskeyOptionsResult;
use App\User\Application\DTO\PasskeyRegistrationCompleteDto;
use App\User\Application\DTO\PasskeySignInCompleteDto;
use App\User\Application\DTO\PasskeySignInOptionsDto;
use App\User\Application\DTO\PasskeySignUpCompleteDto;
use App\User\Application\DTO\PasskeySignUpOptionsDto;
use App\User\Application\DTO\VerifiedPasskeyCredential;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final class PasskeyDtoTest extends UnitTestCase
{
    public function testSignUpOptionsExposeInputValues(): void
    {
        $email = $this->faker->safeEmail();
        $initials = $this->faker->lexify('??');
        $displayName = $this->faker->name();
        $dto = new PasskeySignUpOptionsDto($email, $initials, $displayName);

        self::assertSame($email, $dto->email);
        self::assertSame($initials, $dto->initials);
        self::assertSame($displayName, $dto->displayName);
    }

    public function testSignUpCompleteExposesCredentialAndRememberMe(): void
    {
        $challengeId = $this->faker->uuid();
        $credential = ['id' => $this->faker->uuid()];
        $label = $this->faker->words(2, true);
        $dto = new PasskeySignUpCompleteDto($challengeId, $credential, $label);
        $dto->setRememberMe(true);

        self::assertSame($challengeId, $dto->challengeId);
        self::assertSame($credential, $dto->credential);
        self::assertSame($label, $dto->label);
        self::assertTrue($dto->isRememberMe());
    }

    public function testRememberMeDefaultsToFalse(): void
    {
        self::assertFalse((new PasskeySignUpCompleteDto())->isRememberMe());
        self::assertFalse((new PasskeySignInOptionsDto())->isRememberMe());
    }

    public function testSignInOptionsExposeEmailAndRememberMe(): void
    {
        $email = $this->faker->safeEmail();
        $dto = new PasskeySignInOptionsDto($email);
        $dto->setRememberMe(true);

        self::assertSame($email, $dto->email);
        self::assertTrue($dto->isRememberMe());
    }

    public function testCompleteDtosExposeCredentialValues(): void
    {
        $credential = ['id' => $this->faker->uuid()];
        $signInChallengeId = $this->faker->uuid();
        $registrationChallengeId = $this->faker->uuid();
        $label = $this->faker->words(2, true);
        $signIn = new PasskeySignInCompleteDto($signInChallengeId, $credential);
        $registration = new PasskeyRegistrationCompleteDto(
            $registrationChallengeId,
            $credential,
            $label
        );

        self::assertSame($signInChallengeId, $signIn->challengeId);
        self::assertSame($credential, $signIn->credential);
        self::assertSame($registrationChallengeId, $registration->challengeId);
        self::assertSame($credential, $registration->credential);
        self::assertSame($label, $registration->label);
    }

    public function testResultDtosExposeValues(): void
    {
        $challenge = $this->createChallenge();
        $rpId = $this->faker->domainName();
        $options = ['rpId' => $rpId];
        $optionsResult = new PasskeyOptionsResult($challenge, $options);
        $accessToken = $this->faker->sha256();
        $refreshToken = $this->faker->sha256();
        $credentialId = $this->faker->uuid();
        $credentialRecord = json_encode(['record' => $this->faker->boolean()], JSON_THROW_ON_ERROR);
        $authResult = new PasskeyAuthenticationResult($accessToken, $refreshToken, true);
        $verified = new VerifiedPasskeyCredential($credentialId, $credentialRecord);

        $this->assertOptionsResult($optionsResult, $challenge, $options);
        $this->assertAuthenticationResult($authResult, $accessToken, $refreshToken);
        $this->assertVerifiedCredential($verified, $credentialId, $credentialRecord);
    }

    /**
     * @param array<string, string> $options
     */
    private function assertOptionsResult(
        PasskeyOptionsResult $optionsResult,
        PasskeyChallenge $challenge,
        array $options
    ): void {
        self::assertSame($challenge, $optionsResult->getChallenge());
        self::assertSame($options, $optionsResult->getPublicKeyOptions());
    }

    private function assertAuthenticationResult(
        PasskeyAuthenticationResult $authResult,
        string $accessToken,
        string $refreshToken
    ): void {
        self::assertSame($accessToken, $authResult->getAccessToken());
        self::assertSame($refreshToken, $authResult->getRefreshToken());
        self::assertTrue($authResult->isRememberMe());
    }

    private function assertVerifiedCredential(
        VerifiedPasskeyCredential $verified,
        string $credentialId,
        string $credentialRecord
    ): void {
        self::assertSame($credentialId, $verified->getCredentialId());
        self::assertSame($credentialRecord, $verified->getCredentialRecord());
    }

    private function createChallenge(): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            $this->faker->uuid(),
            PasskeyChallenge::PURPOSE_AUTHENTICATION,
            $this->faker->sha256(),
            json_encode(['challenge' => $this->faker->sha256()], JSON_THROW_ON_ERROR),
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext(
                $this->faker->safeEmail(),
                userId: $this->faker->uuid()
            )
        );
    }
}
