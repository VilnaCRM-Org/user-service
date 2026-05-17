<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Validator;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\PasskeyConfiguration;
use App\User\Application\Factory\PasskeyVerifiedCredentialFactory;
use App\User\Application\Factory\PasskeyWebauthnFactoryInterface;
use App\User\Application\Resolver\PasskeyCredentialResponseResolver;
use App\User\Application\Transformer\PasskeyEncodingTransformer;
use App\User\Application\Transformer\PasskeyJsonTransformerInterface;
use App\User\Application\Validator\PasskeyAssertionCredentialRecordValidator;
use App\User\Application\Validator\PasskeyAttestationCredentialRecordValidator;
use App\User\Application\Validator\PasskeyCredentialValidator;
use App\User\Domain\Entity\PasskeyChallenge;
use App\User\Domain\Entity\PasskeyCredential;
use App\User\Domain\ValueObject\PasskeyChallengeContext;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use stdClass;
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

final class PasskeyCredentialValidatorTest extends UnitTestCase
{
    private PasskeyJsonTransformerInterface&MockObject $jsonTransformer;
    private PasskeyWebauthnFactoryInterface&MockObject $webauthnFactory;
    private PasskeyEncodingTransformer $encoding;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->jsonTransformer = $this->createMock(PasskeyJsonTransformerInterface::class);
        $this->webauthnFactory = $this->createMock(PasskeyWebauthnFactoryInterface::class);
        $this->encoding = new PasskeyEncodingTransformer();
    }

    public function testExtractCredentialIdReturnsEncodedRawId(): void
    {
        $credential = $this->createPublicKeyCredential(
            $this->createMock(AuthenticatorAssertionResponse::class)
        );
        $this->jsonTransformer->expects($this->once())
            ->method('decodeCredential')
            ->with(['id' => 'payload'])
            ->willReturn($credential);

        self::assertSame(
            $this->encoding->encode('raw-credential-id'),
            $this->createValidator()->extractCredentialId(['id' => 'payload'])
        );
    }

    public function testExtractCredentialIdRejectsInvalidPayload(): void
    {
        $this->jsonTransformer->expects($this->once())
            ->method('decodeCredential')
            ->willThrowException(new RuntimeException('Broken payload.'));

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential payload.');

        $this->createValidator()->extractCredentialId(['id' => 'payload']);
    }

    public function testVerifyAttestationReturnsVerifiedCredential(): void
    {
        $response = $this->createMock(AuthenticatorAttestationResponse::class);
        $record = $this->createCredentialRecord('raw-credential-id');

        $this->expectAttestationVerification($response, $record);
        $this->jsonTransformer->method('encodeCredentialRecord')
            ->with($record)
            ->willReturn('record-json');

        $verified = $this->createValidator()->verifyAttestation(
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
        $this->jsonTransformer->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential(
                $this->createMock(AuthenticatorAssertionResponse::class)
            ));

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Passkey attestation response is required.');

        $this->createValidator()->verifyAttestation(
            $this->createChallenge(PasskeyChallenge::PURPOSE_SIGNUP),
            ['id' => 'payload']
        );
    }

    public function testVerifyAttestationRejectsCredentialWithoutResponse(): void
    {
        $this->jsonTransformer->method('decodeCredential')->willReturn(new stdClass());

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential response.');

        $this->createValidator()->verifyAttestation(
            $this->createChallenge(PasskeyChallenge::PURPOSE_SIGNUP),
            ['id' => 'payload']
        );
    }

    public function testVerifyAttestationRejectsInvalidValidator(): void
    {
        $response = $this->createMock(AuthenticatorAttestationResponse::class);

        $this->jsonTransformer->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->jsonTransformer->method('decodeCreationOptions')
            ->willReturn($this->createCreationOptions());
        $this->webauthnFactory->method('createAttestationValidator')
            ->willReturn(new stdClass());

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential.');

        $this->createValidator()->verifyAttestation(
            $this->createChallenge(PasskeyChallenge::PURPOSE_SIGNUP),
            ['id' => 'payload']
        );
    }

    public function testVerifyAttestationRejectsInvalidValidatorResult(): void
    {
        $response = $this->createMock(AuthenticatorAttestationResponse::class);

        $this->jsonTransformer->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->jsonTransformer->method('decodeCreationOptions')
            ->willReturn($this->createCreationOptions());
        $this->webauthnFactory->method('createAttestationValidator')
            ->willReturn($this->createInvalidCredentialRecordValidator());

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential.');

        $this->createValidator()->verifyAttestation(
            $this->createChallenge(PasskeyChallenge::PURPOSE_SIGNUP),
            ['id' => 'payload']
        );
    }

    public function testVerifyAttestationWrapsValidatorFailure(): void
    {
        $response = $this->createMock(AuthenticatorAttestationResponse::class);
        $validator = $this->createMock(AuthenticatorAttestationResponseValidator::class);

        $this->jsonTransformer->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->jsonTransformer->method('decodeCreationOptions')
            ->willReturn($this->createCreationOptions());
        $this->webauthnFactory->method('createAttestationValidator')->willReturn($validator);
        $validator->method('check')->willThrowException(new RuntimeException('Invalid signature.'));

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential.');

        $this->createValidator()->verifyAttestation(
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
        $this->jsonTransformer->method('encodeCredentialRecord')->with($verifiedRecord)
            ->willReturn('verified-record-json');

        $verified = $this->createValidator()->verifyAssertion(
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
        $this->jsonTransformer->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential(
                $this->createMock(AuthenticatorAttestationResponse::class)
            ));

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Passkey assertion response is required.');

        $this->createValidator()->verifyAssertion(
            $this->createChallenge(PasskeyChallenge::PURPOSE_AUTHENTICATION),
            ['id' => 'payload'],
            $this->createStoredCredential()
        );
    }

    public function testVerifyAssertionRejectsInvalidValidator(): void
    {
        $response = $this->createMock(AuthenticatorAssertionResponse::class);

        $this->jsonTransformer->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->webauthnFactory->method('createAssertionValidator')
            ->willReturn(new stdClass());

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential.');

        $this->createValidator()->verifyAssertion(
            $this->createChallenge(PasskeyChallenge::PURPOSE_AUTHENTICATION),
            ['id' => 'payload'],
            $this->createStoredCredential()
        );
    }

    public function testVerifyAssertionRejectsInvalidValidatorResult(): void
    {
        $response = $this->createMock(AuthenticatorAssertionResponse::class);

        $this->jsonTransformer->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->jsonTransformer->method('decodeCredentialRecord')
            ->willReturn($this->createCredentialRecord('stored-credential-id'));
        $this->jsonTransformer->method('decodeRequestOptions')
            ->willReturn($this->createRequestOptions());
        $this->webauthnFactory->method('createAssertionValidator')
            ->willReturn($this->createInvalidCredentialRecordValidator());

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential.');

        $this->createValidator()->verifyAssertion(
            $this->createChallenge(PasskeyChallenge::PURPOSE_AUTHENTICATION),
            ['id' => 'payload'],
            $this->createStoredCredential()
        );
    }

    public function testVerifyAssertionWrapsValidatorFailure(): void
    {
        $response = $this->createMock(AuthenticatorAssertionResponse::class);
        $validator = $this->createMock(AuthenticatorAssertionResponseValidator::class);

        $this->jsonTransformer->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->jsonTransformer->method('decodeCredentialRecord')
            ->willReturn($this->createCredentialRecord('stored-credential-id'));
        $this->jsonTransformer->method('decodeRequestOptions')
            ->willReturn($this->createRequestOptions());
        $this->webauthnFactory->method('createAssertionValidator')->willReturn($validator);
        $validator->method('check')->willThrowException(new RuntimeException('Invalid signature.'));

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Invalid passkey credential.');

        $this->createValidator()->verifyAssertion(
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

        $this->jsonTransformer->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->jsonTransformer->method('decodeCreationOptions')->willReturn($options);
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

        $this->jsonTransformer->method('decodeCredential')
            ->willReturn($this->createPublicKeyCredential($response));
        $this->jsonTransformer->method('decodeCredentialRecord')->willReturn($storedRecord);
        $this->jsonTransformer->method('decodeRequestOptions')->willReturn($options);
        $this->webauthnFactory->expects($this->once())
            ->method('createAssertionValidator')
            ->willReturn($validator);
        $validator->expects($this->once())
            ->method('check')
            ->with($storedRecord, $response, $options, 'localhost', 'user-id')
            ->willReturn($verifiedRecord);
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

    private function createValidator(): PasskeyCredentialValidator
    {
        return new PasskeyCredentialValidator(
            $this->jsonTransformer,
            $this->encoding,
            new PasskeyAttestationCredentialRecordValidator(
                $this->jsonTransformer,
                $this->webauthnFactory,
                $this->createConfiguration(),
                new PasskeyCredentialResponseResolver()
            ),
            new PasskeyAssertionCredentialRecordValidator(
                $this->jsonTransformer,
                $this->webauthnFactory,
                $this->createConfiguration(),
                new PasskeyCredentialResponseResolver()
            ),
            new PasskeyVerifiedCredentialFactory($this->encoding, $this->jsonTransformer)
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
