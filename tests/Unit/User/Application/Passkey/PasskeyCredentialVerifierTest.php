<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Passkey;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Passkey\PasskeyAssertionCredentialRecordVerifier;
use App\User\Application\Passkey\PasskeyAttestationCredentialRecordVerifier;
use App\User\Application\Passkey\PasskeyConfiguration;
use App\User\Application\Passkey\PasskeyCredentialResponseResolver;
use App\User\Application\Passkey\PasskeyCredentialVerifier;
use App\User\Application\Passkey\PasskeyEncoding;
use App\User\Application\Passkey\PasskeyJsonCodecInterface;
use App\User\Application\Passkey\PasskeyVerifiedCredentialFactory;
use App\User\Application\Passkey\PasskeyWebauthnFactoryInterface;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Uid\Uuid;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions as RequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\EmptyTrustPath;

final class PasskeyCredentialVerifierTest extends UnitTestCase
{
    private PasskeyJsonCodecInterface&MockObject $jsonCodec;
    private PasskeyWebauthnFactoryInterface&MockObject $webauthnFactory;
    private PasskeyEncoding $encoding;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->jsonCodec = $this->createMock(PasskeyJsonCodecInterface::class);
        $this->webauthnFactory = $this->createMock(PasskeyWebauthnFactoryInterface::class);
        $this->encoding = new PasskeyEncoding();
    }

    public function testExtractCredentialIdReturnsEncodedRawId(): void
    {
        $credential = $this->createPublicKeyCredential(
            $this->createMock(AuthenticatorAssertionResponse::class)
        );
        $this->jsonCodec->expects($this->once())
            ->method('decodeCredential')
            ->with(['id' => 'payload'])
            ->willReturn($credential);

        self::assertSame(
            $this->encoding->encode('raw-credential-id'),
            $this->createVerifier()->extractCredentialId(['id' => 'payload'])
        );
    }

    public function testExtractCredentialIdRejectsInvalidPayload(): void
    {
        $this->jsonCodec->expects($this->once())
            ->method('decodeCredential')
            ->willThrowException(new RuntimeException('Broken payload.'));

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential payload.');

        $this->createVerifier()->extractCredentialId(['id' => 'payload']);
    }

    public function testVerifyAttestationReturnsVerifiedCredential(): void
    {
        $response = $this->createMock(AuthenticatorAttestationResponse::class);
        $record = $this->createCredentialRecord('raw-credential-id');

        $this->expectAttestationVerification($response, $record);
        $this->jsonCodec->method('encodeCredentialRecord')
            ->with($record)
            ->willReturn('record-json');

        $verified = $this->createVerifier()->verifyAttestation(
            $this->createChallenge(PasskeyChallenge::PURPOSE_SIGNUP),
            ['id' => 'payload']
        );

        self::assertSame(
            $this->encoding->encode('raw-credential-id'),
            $verified->getCredentialId()
        );
        self::assertSame('record-json', $verified->getCredentialRecord());
    }

    public function testVerifyAttestationRejectsAssertionResponse(): void
    {
        $this->jsonCodec->method('decodeCredential')->willReturn($this->createPublicKeyCredential(
            $this->createMock(AuthenticatorAssertionResponse::class)
        ));

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Passkey attestation response is required.');

        $this->createVerifier()->verifyAttestation(
            $this->createChallenge(PasskeyChallenge::PURPOSE_SIGNUP),
            ['id' => 'payload']
        );
    }

    public function testVerifyAttestationWrapsValidatorFailure(): void
    {
        $response = $this->createMock(AuthenticatorAttestationResponse::class);
        $validator = $this->createMock(AuthenticatorAttestationResponseValidator::class);

        $this->jsonCodec->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->jsonCodec->method('decodeCreationOptions')
            ->willReturn($this->createCreationOptions());
        $this->webauthnFactory->method('createAttestationValidator')->willReturn($validator);
        $validator->method('check')->willThrowException(new RuntimeException('Invalid signature.'));

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential.');

        $this->createVerifier()->verifyAttestation(
            $this->createChallenge(PasskeyChallenge::PURPOSE_SIGNUP),
            ['id' => 'payload']
        );
    }

    public function testVerifyAssertionReturnsVerifiedCredential(): void
    {
        $response = $this->createMock(AuthenticatorAssertionResponse::class);
        $storedRecord = $this->createCredentialRecord('stored-credential-id');
        $verifiedRecord = $this->createCredentialRecord('verified-credential-id');

        $this->expectAssertionVerification($response, $storedRecord, $verifiedRecord);
        $this->jsonCodec->method('encodeCredentialRecord')->with($verifiedRecord)
            ->willReturn('verified-record-json');

        $verified = $this->createVerifier()->verifyAssertion(
            $this->createChallenge(PasskeyChallenge::PURPOSE_AUTHENTICATION),
            ['id' => 'payload'],
            $this->createStoredCredential()
        );

        self::assertSame(
            $this->encoding->encode('verified-credential-id'),
            $verified->getCredentialId()
        );
        self::assertSame('verified-record-json', $verified->getCredentialRecord());
    }

    public function testVerifyAssertionRejectsAttestationResponse(): void
    {
        $this->jsonCodec->method('decodeCredential')->willReturn($this->createPublicKeyCredential(
            $this->createMock(AuthenticatorAttestationResponse::class)
        ));

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Passkey assertion response is required.');

        $this->createVerifier()->verifyAssertion(
            $this->createChallenge(PasskeyChallenge::PURPOSE_AUTHENTICATION),
            ['id' => 'payload'],
            $this->createStoredCredential()
        );
    }

    public function testVerifyAssertionWrapsValidatorFailure(): void
    {
        $response = $this->createMock(AuthenticatorAssertionResponse::class);
        $validator = $this->createMock(AuthenticatorAssertionResponseValidator::class);

        $this->jsonCodec->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->jsonCodec->method('decodeCredentialRecord')
            ->willReturn($this->createCredentialRecord('stored-credential-id'));
        $this->jsonCodec->method('decodeRequestOptions')->willReturn($this->createRequestOptions());
        $this->webauthnFactory->method('createAssertionValidator')->willReturn($validator);
        $validator->method('check')->willThrowException(new RuntimeException('Invalid signature.'));

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential.');

        $this->createVerifier()->verifyAssertion(
            $this->createChallenge(PasskeyChallenge::PURPOSE_AUTHENTICATION),
            ['id' => 'payload'],
            $this->createStoredCredential()
        );
    }

    private function expectAttestationVerification(
        AuthenticatorAttestationResponse $response,
        CredentialRecord $record
    ): void {
        $options = $this->createCreationOptions();
        $validator = $this->createMock(AuthenticatorAttestationResponseValidator::class);

        $this->jsonCodec->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->jsonCodec->method('decodeCreationOptions')->willReturn($options);
        $this->webauthnFactory->expects($this->once())
            ->method('createAttestationValidator')
            ->willReturn($validator);
        $validator->expects($this->once())
            ->method('check')
            ->with($response, $options, 'localhost')
            ->willReturn($record);
    }

    private function expectAssertionVerification(
        AuthenticatorAssertionResponse $response,
        CredentialRecord $storedRecord,
        CredentialRecord $verifiedRecord
    ): void {
        $options = $this->createRequestOptions();
        $validator = $this->createMock(AuthenticatorAssertionResponseValidator::class);

        $this->jsonCodec->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->jsonCodec->method('decodeCredentialRecord')->willReturn($storedRecord);
        $this->jsonCodec->method('decodeRequestOptions')->willReturn($options);
        $this->webauthnFactory->expects($this->once())
            ->method('createAssertionValidator')
            ->willReturn($validator);
        $validator->expects($this->once())
            ->method('check')
            ->with($storedRecord, $response, $options, 'localhost', 'user-id')
            ->willReturn($verifiedRecord);
    }

    private function createVerifier(): PasskeyCredentialVerifier
    {
        return new PasskeyCredentialVerifier(
            $this->jsonCodec,
            $this->encoding,
            new PasskeyAttestationCredentialRecordVerifier(
                $this->jsonCodec,
                $this->webauthnFactory,
                $this->createConfiguration(),
                new PasskeyCredentialResponseResolver()
            ),
            new PasskeyAssertionCredentialRecordVerifier(
                $this->jsonCodec,
                $this->webauthnFactory,
                $this->createConfiguration(),
                new PasskeyCredentialResponseResolver()
            ),
            new PasskeyVerifiedCredentialFactory($this->encoding, $this->jsonCodec)
        );
    }

    private function createConfiguration(): PasskeyConfiguration
    {
        return new PasskeyConfiguration(
            'localhost',
            'VilnaCRM User Service',
            'https://localhost',
            300,
            300
        );
    }

    private function createPublicKeyCredential(
        AuthenticatorAssertionResponse|AuthenticatorAttestationResponse $response
    ): PublicKeyCredential {
        return new PublicKeyCredential('public-key', 'raw-credential-id', $response);
    }

    private function createCreationOptions(): PublicKeyCredentialCreationOptions
    {
        return new PublicKeyCredentialCreationOptions(
            new PublicKeyCredentialRpEntity('VilnaCRM User Service', 'localhost'),
            new PublicKeyCredentialUserEntity('person@example.com', 'user-id', 'Person Example'),
            'challenge',
            attestation: PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE
        );
    }

    private function createRequestOptions(): RequestOptions
    {
        $userVerification = RequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED;

        return new RequestOptions(
            'challenge',
            'localhost',
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
            'credential-public-key',
            'user-id',
            0
        );
    }

    private function createChallenge(string $purpose): PasskeyChallenge
    {
        $createdAt = new DateTimeImmutable();

        return new PasskeyChallenge(
            'challenge-id',
            $purpose,
            'challenge',
            '{}',
            $createdAt,
            $createdAt->modify('+5 minutes'),
            new PasskeyChallengeContext('person@example.com', userId: 'user-id')
        );
    }

    private function createStoredCredential(): PasskeyCredential
    {
        return new PasskeyCredential(
            'passkey-id',
            'user-id',
            'credential-id',
            '{"record":true}',
            'Laptop',
            new DateTimeImmutable()
        );
    }
}
