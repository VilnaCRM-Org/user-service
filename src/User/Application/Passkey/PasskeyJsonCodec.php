<?php

declare(strict_types=1);

namespace App\User\Application\Passkey;

use function is_array;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;

use Webauthn\PublicKeyCredentialRequestOptions;

final class PasskeyJsonCodec implements PasskeyJsonCodecInterface
{
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
    public function optionsToArray(
        PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options
    ): array {
        return $this->decodeArray($this->serializer->serialize($options, 'json'));
    }

    #[\Override]
    public function encodeOptions(
        PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options
    ): string {
        return $this->serializer->serialize($options, 'json');
    }

    #[\Override]
    public function decodeCreationOptions(string $json): PublicKeyCredentialCreationOptions
    {
        $options = $this->serializer->deserialize(
            $json,
            PublicKeyCredentialCreationOptions::class,
            'json'
        );

        if (!$options instanceof PublicKeyCredentialCreationOptions) {
            throw new BadRequestHttpException('Invalid passkey creation options.');
        }

        return $options;
    }

    #[\Override]
    public function decodeRequestOptions(string $json): PublicKeyCredentialRequestOptions
    {
        $options = $this->serializer->deserialize(
            $json,
            PublicKeyCredentialRequestOptions::class,
            'json'
        );

        if (!$options instanceof PublicKeyCredentialRequestOptions) {
            throw new BadRequestHttpException('Invalid passkey request options.');
        }

        return $options;
    }

    /**
     * @param array<string, scalar|array|null> $credential
     */
    #[\Override]
    public function decodeCredential(array $credential): PublicKeyCredential
    {
        try {
            $json = $this->jsonEncoder->encode($credential, JsonEncoder::FORMAT);
        } catch (NotEncodableValueException $exception) {
            throw new BadRequestHttpException('Invalid passkey credential payload.', $exception);
        }

        $publicKeyCredential = $this->serializer->deserialize(
            $json,
            PublicKeyCredential::class,
            'json'
        );

        if (!$publicKeyCredential instanceof PublicKeyCredential) {
            throw new BadRequestHttpException('Invalid passkey credential payload.');
        }

        return $publicKeyCredential;
    }

    #[\Override]
    public function encodeCredentialRecord(CredentialRecord $credentialRecord): string
    {
        return $this->serializer->serialize($credentialRecord, 'json');
    }

    #[\Override]
    public function decodeCredentialRecord(string $json): CredentialRecord
    {
        $credentialRecord = $this->serializer->deserialize(
            $json,
            CredentialRecord::class,
            'json'
        );

        if (!$credentialRecord instanceof CredentialRecord) {
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
