<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Transformer;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\DTO\PasskeyConfiguration;
use App\User\Application\Factory\PasskeyPublicKeyOptionsFactory;
use App\User\Application\Factory\PasskeyWebauthnFactory;
use App\User\Application\Factory\PasskeyWebauthnFactoryInterface;
use App\User\Application\Transformer\PasskeyEncodingTransformer;
use App\User\Application\Transformer\PasskeyJsonTransformer;
use App\User\Domain\Entity\PasskeyCredential;

use function array_map;

use DateTimeImmutable;

use const JSON_THROW_ON_ERROR;

use ReflectionProperty;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\CeremonyStep\CheckAllowedOrigins;
use Webauthn\CeremonyStep\CheckOrigin;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions as RequestOptions;
use Webauthn\TrustPath\EmptyTrustPath;

final class PasskeyJsonTransformerTest extends UnitTestCase
{
    private PasskeyEncodingTransformer $encoding;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->encoding = new PasskeyEncodingTransformer();
    }

    public function testCreationAndRequestOptionsRoundTripThroughJson(): void
    {
        $factory = $this->createPublicKeyOptionsFactory();
        $codec = $this->createCodec();

        $this->assertCreationOptionsRoundTrip($codec, $factory);
        $this->assertRequestOptionsRoundTrip($codec, $factory);
    }

    public function testDecodeCredentialBuildsPublicKeyAssertionCredential(): void
    {
        $rawCredentialId = $this->faker->sha256();
        $credential = $this->createCodec()->decodeCredential(
            $this->createAssertionPayload($rawCredentialId)
        );

        self::assertInstanceOf(PublicKeyCredential::class, $credential);
        self::assertSame($rawCredentialId, $credential->rawId);
        self::assertInstanceOf(AuthenticatorAssertionResponse::class, $credential->response);
    }

    public function testCredentialRecordRoundTripThroughJson(): void
    {
        $codec = $this->createCodec();
        $credentialId = $this->faker->sha256();
        $userId = $this->faker->uuid();
        $credentialPublicKey = $this->faker->sha256();
        $record = new CredentialRecord(
            $credentialId,
            'public-key',
            [],
            'none',
            new EmptyTrustPath(),
            Uuid::v4(),
            $credentialPublicKey,
            $userId,
            1
        );

        self::assertSame(
            $credentialId,
            $codec->decodeCredentialRecord($codec->encodeCredentialRecord($record))
                ->publicKeyCredentialId
        );
    }

    public function testDecodeCredentialRejectsNonEncodablePayload(): void
    {
        $handle = tmpfile();

        try {
            $this->expectException(BadRequestHttpException::class);
            $this->expectExceptionMessage('Invalid passkey credential payload.');

            $this->createCodec()->decodeCredential(['resource' => $handle]);
        } finally {
            if ($handle !== false) {
                fclose($handle);
            }
        }
    }

    public function testDecodeMethodsRejectUnexpectedSerializerTypes(): void
    {
        $codec = $this->createUnexpectedTypeCodec();

        $this->assertBadRequest(
            static fn () => $codec->decodeCreationOptions('{}'),
            'Invalid passkey creation options.'
        );
        $this->assertBadRequest(
            static fn () => $codec->decodeRequestOptions('{}'),
            'Invalid passkey request options.'
        );
        $this->assertBadRequest(
            static fn () => $codec->decodeCredential([]),
            'Invalid passkey credential payload.'
        );
        $this->assertBadRequest(
            static fn () => $codec->decodeCredentialRecord('{}'),
            'Invalid passkey credential record.'
        );
    }

    public function testOptionsToArrayRejectsInvalidJson(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('serialize')->willReturn('{');

        $this->assertBadRequest(
            fn () => $this->createCodecWithSerializer($serializer)
                ->optionsToArray($this->createRequestOptions()),
            'Invalid passkey JSON payload.'
        );
    }

    public function testOptionsToArrayRejectsNonArrayJsonPayload(): void
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('serialize')->willReturn('"text"');

        $this->assertBadRequest(
            fn () => $this->createCodecWithSerializer($serializer)
                ->optionsToArray($this->createRequestOptions()),
            'Invalid passkey JSON payload.'
        );
    }

    public function testOptionsToArrayUsesInjectedSerializerAndAssociativeJsonDecoder(): void
    {
        $options = $this->createRequestOptions();
        $json = '{"publicKey":{"challenge":"abc"}}';
        $payload = ['publicKey' => ['challenge' => 'abc']];
        $factory = $this->createMock(PasskeyWebauthnFactoryInterface::class);
        $factory->expects($this->never())->method('createSerializer');
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->expects($this->once())
            ->method('serialize')
            ->with($options, JsonEncoder::FORMAT)
            ->willReturn($json);
        $jsonEncoder = $this->createMock(JsonEncoder::class);
        $jsonEncoder->expects($this->once())
            ->method('decode')
            ->with($json, JsonEncoder::FORMAT, [JsonDecode::ASSOCIATIVE => true])
            ->willReturn($payload);

        $transformer = new PasskeyJsonTransformer($factory, $serializer, $jsonEncoder);

        self::assertSame($payload, $transformer->optionsToArray($options));
    }

    public function testFactoryCreatesWebauthnCollaborators(): void
    {
        $factory = new PasskeyWebauthnFactory($this->createConfiguration());
        $attestationValidator = $factory->createAttestationValidator();
        $assertionValidator = $factory->createAssertionValidator();

        self::assertInstanceOf(SerializerInterface::class, $factory->createSerializer());
        self::assertNotSame($attestationValidator, $factory->createAttestationValidator());
        self::assertNotSame($assertionValidator, $factory->createAssertionValidator());
        $this->assertValidatorUsesAllowedOrigins($attestationValidator);
        $this->assertValidatorUsesAllowedOrigins($assertionValidator);
    }

    private function createPublicKeyOptionsFactory(): PasskeyPublicKeyOptionsFactory
    {
        return new PasskeyPublicKeyOptionsFactory($this->createConfiguration(), $this->encoding);
    }

    private function assertCreationOptionsRoundTrip(
        PasskeyJsonTransformer $codec,
        PasskeyPublicKeyOptionsFactory $factory
    ): void {
        $email = $this->faker->safeEmail();
        $userId = $this->faker->uuid();
        $displayName = $this->faker->name();
        $options = $factory->createRegistrationOptions(
            $email,
            $userId,
            $displayName,
            $this->faker->sha256(),
            [$this->createPasskeyCredential()]
        );

        self::assertSame(
            $email,
            $codec->decodeCreationOptions($codec->encodeOptions($options))->user->name
        );
    }

    private function assertRequestOptionsRoundTrip(
        PasskeyJsonTransformer $codec,
        PasskeyPublicKeyOptionsFactory $factory
    ): void {
        $options = $factory->createAuthenticationOptions(
            $this->faker->sha256(),
            [$this->createPasskeyCredential()]
        );

        self::assertSame(
            'localhost',
            $codec->decodeRequestOptions($codec->encodeOptions($options))->rpId
        );
    }

    private function createUnexpectedTypeCodec(): PasskeyJsonTransformer
    {
        $serializer = $this->createMock(SerializerInterface::class);
        $serializer->method('deserialize')->willReturn(new \stdClass());

        return $this->createCodecWithSerializer($serializer);
    }

    private function createCodec(): PasskeyJsonTransformer
    {
        return new PasskeyJsonTransformer(
            new PasskeyWebauthnFactory($this->createConfiguration())
        );
    }

    private function createCodecWithSerializer(
        SerializerInterface $serializer
    ): PasskeyJsonTransformer {
        return new PasskeyJsonTransformer(
            $this->createMock(PasskeyWebauthnFactoryInterface::class),
            $serializer,
            new JsonEncoder()
        );
    }

    private function createRequestOptions(): RequestOptions
    {
        $userVerification = RequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED;

        return new RequestOptions(
            $this->faker->sha256(),
            'localhost',
            userVerification: $userVerification
        );
    }

    private function assertBadRequest(callable $callback, string $message): void
    {
        try {
            $callback();
            self::fail('Expected BadRequestHttpException was not thrown.');
        } catch (BadRequestHttpException $exception) {
            self::assertSame($message, $exception->getMessage());
        }
    }

    private function assertValidatorUsesAllowedOrigins(object $validator): void
    {
        $manager = $this->readPrivateProperty($validator, 'ceremonyStepManager');
        self::assertIsObject($manager);
        $steps = $this->readPrivateProperty($manager, 'steps');
        self::assertIsArray($steps);

        $stepClasses = array_map(static fn (object $step): string => $step::class, $steps);
        self::assertContains(CheckAllowedOrigins::class, $stepClasses);
        self::assertNotContains(CheckOrigin::class, $stepClasses);
    }

    private function readPrivateProperty(object $object, string $property): mixed
    {
        return (new ReflectionProperty($object, $property))->getValue($object);
    }

    /**
     * @return array<string, scalar|array<string, scalar|null>|null>
     */
    private function createAssertionPayload(string $rawCredentialId): array
    {
        return [
            'id' => $this->encoding->encode($rawCredentialId),
            'rawId' => $this->encoding->encode($rawCredentialId),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => $this->encoding->encode($this->createClientDataJson()),
                'authenticatorData' => $this->encoding->encode($this->createAuthenticatorData()),
                'signature' => $this->encoding->encode($this->faker->sha256()),
                'userHandle' => null,
            ],
        ];
    }

    private function createClientDataJson(): string
    {
        return json_encode([
            'type' => 'webauthn.get',
            'challenge' => $this->encoding->encode($this->faker->sha256()),
            'origin' => 'https://localhost',
        ], JSON_THROW_ON_ERROR);
    }

    private function createAuthenticatorData(): string
    {
        return str_repeat("\0", 32) . chr(1) . pack('N', 1);
    }

    private function createPasskeyCredential(): PasskeyCredential
    {
        $rawCredentialId = $this->faker->sha256();

        return new PasskeyCredential(
            $this->faker->uuid(),
            $this->faker->uuid(),
            $this->encoding->encode($rawCredentialId),
            '{}',
            $this->faker->words(2, true),
            new DateTimeImmutable()
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
}
