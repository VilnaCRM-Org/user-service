<?php

declare(strict_types=1);

namespace App\User\Application\Transformer;

interface PasskeyJsonTransformerInterface
{
    /**
     * @return array<string, scalar|array|null>
     */
    public function optionsToArray(object $options): array;

    public function encodeOptions(object $options): string;

    public function decodeCreationOptions(string $json): object;

    public function decodeRequestOptions(string $json): object;

    /**
     * @param array<string, scalar|array|null> $credential
     */
    public function decodeCredential(array $credential): object;

    public function encodeCredentialRecord(object $credentialRecord): string;

    public function decodeCredentialRecord(string $json): object;
}
