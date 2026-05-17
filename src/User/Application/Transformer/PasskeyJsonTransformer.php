<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

use App\User\Application\Factory\PasskeyWebauthnFactoryInterface;

use function is_array;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

final class PasskeyJsonTransformer implements PasskeyJsonTransformerInterface
{
    private const CREDENTIAL_CLASS = 'Webauthn\\PublicKeyCredential';
    private const CREDENTIAL_RECORD_CLASS = 'Webauthn\\CredentialRecord';
    private const CREATION_OPTIONS_CLASS = 'Webauthn\\PublicKeyCredentialCreationOptions';
    private const REQUEST_OPTIONS_CLASS = 'Webauthn\\PublicKeyCredentialRequestOptions';

    private SerializerInterface $serializer;
    private JsonEncoder $jsonEncoder;

    public function __construct(
        PasskeyWebauthnFactoryInterface $factory,
        ?SerializerInterface $serializer = null,
        ?JsonEncoder $jsonEncoder = null
    ) {
        $this->serializer = $serializer ?? $factory->createSerializer();
        $this->jsonEncoder = $jsonEncoder ?? new JsonEncoder();
    }

    /**
     * @return array<string, scalar|array|null>
     */
    #[\Override]
    public function optionsToArray(object $options): array
    {
        return $this->decodeArray($this->serializer->serialize($options, 'json'));
    }

    #[\Override]
    public function encodeOptions(object $options): string
    {
        return $this->serializer->serialize($options, 'json');
    }

    #[\Override]
    public function decodeCreationOptions(string $json): object
    {
        $options = $this->serializer->deserialize(
            $json,
            self::CREATION_OPTIONS_CLASS,
            'json'
        );

        if (!is_a($options, self::CREATION_OPTIONS_CLASS)) {
            throw new BadRequestHttpException('Invalid passkey creation options.');
        }

        return $options;
    }

    #[\Override]
    public function decodeRequestOptions(string $json): object
    {
        $options = $this->serializer->deserialize(
            $json,
            self::REQUEST_OPTIONS_CLASS,
            'json'
        );

        if (!is_a($options, self::REQUEST_OPTIONS_CLASS)) {
            throw new BadRequestHttpException('Invalid passkey request options.');
        }

        return $options;
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    #[\Override]
    public function decodeCredential(array $credential): object
    {
        try {
            $json = $this->jsonEncoder->encode($credential, JsonEncoder::FORMAT);
        } catch (NotEncodableValueException $exception) {
            throw new BadRequestHttpException('Invalid passkey credential payload.', $exception);
        }

        $publicKeyCredential = $this->serializer->deserialize(
            $json,
            self::CREDENTIAL_CLASS,
            'json'
        );

        if (!is_a($publicKeyCredential, self::CREDENTIAL_CLASS)) {
            throw new BadRequestHttpException('Invalid passkey credential payload.');
        }

        return $publicKeyCredential;
    }

    #[\Override]
    public function encodeCredentialRecord(object $credentialRecord): string
    {
        return $this->serializer->serialize($credentialRecord, 'json');
    }

    #[\Override]
    public function decodeCredentialRecord(string $json): object
    {
        $credentialRecord = $this->serializer->deserialize(
            $json,
            self::CREDENTIAL_RECORD_CLASS,
            'json'
        );

        if (!is_a($credentialRecord, self::CREDENTIAL_RECORD_CLASS)) {
            throw new BadRequestHttpException('Invalid passkey credential record.');
        }

        return $credentialRecord;
    }

    /**
     * @return array<string, scalar|array|null>
     */
    private function decodeArray(string $json): array
    {
        try {
            $payload = $this->jsonEncoder->decode(
                $json,
                JsonEncoder::FORMAT,
                [JsonDecode::ASSOCIATIVE => true]
            );
        } catch (NotEncodableValueException $exception) {
            throw new BadRequestHttpException('Invalid passkey JSON payload.', $exception);
        }

        if (!is_array($payload)) {
            throw new BadRequestHttpException('Invalid passkey JSON payload.');
        }

        return $payload;
    }
}
