<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Validator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\PasskeyConfiguration;
use App\User\Application\Factory\PasskeyWebauthnFactoryInterface;
use App\User\Application\Resolver\PasskeyCredentialResponseResolver;
use App\User\Application\Transformer\PasskeyJsonTransformerInterface;
use App\User\Application\Validator\PasskeyAssertionCredentialRecordValidator;
use App\User\Application\Validator\PasskeyAttestationCredentialRecordValidator;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use Symfony\Component\Uid\Uuid;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions as RequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\EmptyTrustPath;

final class PasskeyCredentialRecordValidatorTest extends UnitTestCase
{
    private PasskeyJsonTransformerInterface&MockObject $jsonTransformer;
    private PasskeyWebauthnFactoryInterface&MockObject $webauthnFactory;
    private string $rpId;
    private string $rpName;
    private string $origin;
    private string $email;
    private string $userId;
    private string $displayName;
    private string $rawCredentialId;
    private string $challengeId;
    private string $challenge;
    private string $passkeyId;
    private string $credentialId;
    private string $credentialPublicKey;
    private string $credentialLabel;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->jsonTransformer = $this->createMock(PasskeyJsonTransformerInterface::class);
        $this->webauthnFactory = $this->createMock(PasskeyWebauthnFactoryInterface::class);
        $this->rpId = $this->faker->domainName();
        $this->rpName = $this->faker->company();
        $this->origin = sprintf('https://%s', $this->rpId);
        $this->email = $this->faker->safeEmail();
        $this->userId = $this->faker->uuid();
        $this->displayName = $this->faker->name();
        $this->rawCredentialId = $this->faker->sha256();
        $this->challengeId = $this->faker->uuid();
        $this->challenge = $this->faker->sha256();
        $this->passkeyId = $this->faker->uuid();
        $this->credentialId = $this->faker->sha256();
        $this->credentialPublicKey = $this->faker->sha256();
        $this->credentialLabel = $this->faker->words(2, true);
    }

    public function testAttestationRejectsInvalidValidator(): void
    {
        $response = $this->createMock(AuthenticatorAttestationResponse::class);

        $this->jsonTransformer->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->jsonTransformer->method('decodeCreationOptions')
            ->willReturn($this->createCreationOptions());
        $this->webauthnFactory->method('createAttestationValidator')
            ->willReturn(new stdClass());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Passkey attestation validator is invalid.');

        $this->createAttestationValidator()->verify(
            $this->createChallenge(PasskeyChallenge::PURPOSE_SIGNUP),
            ['id' => 'payload']
        );
    }

    public function testAttestationRejectsInvalidValidatorResult(): void
    {
        $response = $this->createMock(AuthenticatorAttestationResponse::class);

        $this->jsonTransformer->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->jsonTransformer->method('decodeCreationOptions')
            ->willReturn($this->createCreationOptions());
        $this->webauthnFactory->method('createAttestationValidator')
            ->willReturn($this->createInvalidCredentialRecordValidator());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Passkey attestation validator returned invalid record.');

        $this->createAttestationValidator()->verify(
            $this->createChallenge(PasskeyChallenge::PURPOSE_SIGNUP),
            ['id' => 'payload']
        );
    }

    public function testAssertionRejectsInvalidValidator(): void
    {
        $response = $this->createMock(AuthenticatorAssertionResponse::class);

        $this->jsonTransformer->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->webauthnFactory->method('createAssertionValidator')
            ->willReturn(new stdClass());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Passkey assertion validator is invalid.');

        $this->createAssertionValidator()->verify(
            $this->createChallenge(PasskeyChallenge::PURPOSE_AUTHENTICATION),
            $this->createStoredCredential(),
            ['id' => 'payload']
        );
    }

    public function testAssertionRejectsInvalidValidatorResult(): void
    {
        $response = $this->createMock(AuthenticatorAssertionResponse::class);

        $this->jsonTransformer->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->jsonTransformer->method('decodeCredentialRecord')
            ->willReturn($this->createCredentialRecord($this->faker->sha256()));
        $this->jsonTransformer->method('decodeRequestOptions')
            ->willReturn($this->createRequestOptions());
        $this->webauthnFactory->method('createAssertionValidator')
            ->willReturn($this->createInvalidCredentialRecordValidator());

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Passkey assertion validator returned invalid record.');

        $this->createAssertionValidator()->verify(
            $this->createChallenge(PasskeyChallenge::PURPOSE_AUTHENTICATION),
            $this->createStoredCredential(),
            ['id' => 'payload']
        );
    }

    private function createInvalidCredentialRecordValidator(): object
    {
        return new class() {
            public function check(mixed ...$arguments): string
            {
                return 'invalid-record';
            }
        };
    }

    private function createAttestationValidator(): PasskeyAttestationCredentialRecordValidator
    {
        return new PasskeyAttestationCredentialRecordValidator(
            $this->jsonTransformer,
            $this->webauthnFactory,
            $this->createConfiguration(),
            new PasskeyCredentialResponseResolver()
        );
    }

    private function createAssertionValidator(): PasskeyAssertionCredentialRecordValidator
    {
        return new PasskeyAssertionCredentialRecordValidator(
            $this->jsonTransformer,
            $this->webauthnFactory,
            $this->createConfiguration(),
            new PasskeyCredentialResponseResolver()
        );
    }

    private function createConfiguration(): PasskeyConfiguration
    {
        return new PasskeyConfiguration(
            $this->rpId,
            $this->rpName,
            $this->origin,
            300,
            300
        );
    }

    private function createPublicKeyCredential(
        AuthenticatorAssertionResponse|AuthenticatorAttestationResponse $response
    ): PublicKeyCredential {
        return new PublicKeyCredential('public-key', $this->rawCredentialId, $response);
    }

    private function createCreationOptions(): PublicKeyCredentialCreationOptions
    {
        return new PublicKeyCredentialCreationOptions(
            new PublicKeyCredentialRpEntity($this->rpName, $this->rpId),
            new PublicKeyCredentialUserEntity($this->email, $this->userId, $this->displayName),
            $this->challenge,
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE
        );
    }

    private function createRequestOptions(): RequestOptions
    {
        $userVerification = RequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED;

        return new RequestOptions(
            $this->challenge,
            $this->rpId,
            userVerification: $userVerification
        );
    }

    private function createCredentialRecord(string $credentialId): CredentialRecord
    {
        return new CredentialRecord(
            $credentialId,
            'public-key',
            [],
            'none',
            new EmptyTrustPath(),
            Uuid::v4(),
            $this->credentialPublicKey,
            $this->userId,
            0
        );
    }

    private function createChallenge(string $purpose): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            $this->challengeId,
            $purpose,
            $this->challenge,
            '{}',
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext($this->email, userId: $this->userId)
        );
    }

    private function createStoredCredential(): PasskeyCredential
    {
        return new PasskeyCredential(
            $this->passkeyId,
            $this->userId,
            $this->credentialId,
            '{"record":true}',
            $this->credentialLabel,
            new DateTimeImmutable()
        );
    }
}
