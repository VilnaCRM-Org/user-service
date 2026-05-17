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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->jsonTransformer = $this->createMock(PasskeyJsonTransformerInterface::class);
        $this->webauthnFactory = $this->createMock(PasskeyWebauthnFactoryInterface::class);
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
            ->willReturn($this->createCredentialRecord('stored-credential-id'));
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
