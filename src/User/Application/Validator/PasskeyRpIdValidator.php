<?php

declare(strict_types=1);

namespace App\User\Application\Validator;

use function explode;
use function idn_to_ascii;

use const IDNA_DEFAULT;
use const INTL_IDNA_VARIANT_UTS46;

use InvalidArgumentException;

use function is_readable;
use function is_string;

use SplFileObject;

use function str_contains;
use function str_starts_with;
use function strtolower;
use function substr;
use function trim;

/**
 * @psalm-type PublicSuffixRules=array{
 *     exact: array<string, true>,
 *     wildcard: array<string, true>,
 *     exception: array<string, true>
 * }
 */
final class PasskeyRpIdValidator
{
    public function assertProductionHostName(
        string $rpId,
        string $appEnv,
        string $publicSuffixListPath
    ): void {
        if ($appEnv !== 'prod') {
            return;
        }

        $normalizedRpId = $this->normalizeHostName($rpId);
        if (!str_contains($normalizedRpId, '.')) {
            $this->throwInvalidProductionRpId();
        }

        $isPublicSuffixOnly = $this->isPublicSuffixOnly(
            $normalizedRpId,
            $this->loadPublicSuffixRules($publicSuffixListPath)
        );
        if ($isPublicSuffixOnly) {
            $this->throwInvalidProductionRpId();
        }
    }

    /**
     * @return PublicSuffixRules
     */
    private function loadPublicSuffixRules(string $publicSuffixListPath): array
    {
        if (!is_readable($publicSuffixListPath)) {
            $this->throwInvalidProductionRpId();
        }

        $rules = ['exact' => [], 'wildcard' => [], 'exception' => []];
        $publicSuffixList = new SplFileObject($publicSuffixListPath);
        while (!$publicSuffixList->eof()) {
            $rule = trim($publicSuffixList->fgets());
            if ($rule === '') {
                continue;
            }

            $rules = $this->addPublicSuffixRule($rules, $rule);
        }

        return $rules;
    }

    /**
     * @param PublicSuffixRules $rules
     *
     * @return PublicSuffixRules
     */
    private function addPublicSuffixRule(array $rules, string $rule): array
    {
        if (str_starts_with($rule, '//')) {
            return $rules;
        }

        if (str_starts_with($rule, '!')) {
            $rules['exception'][$this->normalizeHostName(substr($rule, 1))] = true;

            return $rules;
        }

        if (str_starts_with($rule, '*.')) {
            $rules['wildcard'][$this->normalizeHostName(substr($rule, 2))] = true;

            return $rules;
        }

        $rules['exact'][$this->normalizeHostName($rule)] = true;

        return $rules;
    }

    private function normalizeHostName(string $hostName): string
    {
        $asciiHostName = idn_to_ascii($hostName, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        if (!is_string($asciiHostName) || $asciiHostName === '') {
            $this->throwInvalidProductionRpId();
        }

        return strtolower($asciiHostName);
    }

    /**
     * @param PublicSuffixRules $rules
     */
    private function isPublicSuffixOnly(string $rpId, array $rules): bool
    {
        if (isset($rules['exception'][$rpId])) {
            return false;
        }

        return isset($rules['exact'][$rpId])
            || $this->matchesWildcardPublicSuffix($rpId, $rules['wildcard']);
    }

    /**
     * @param array<string, true> $wildcardRules
     */
    private function matchesWildcardPublicSuffix(string $rpId, array $wildcardRules): bool
    {
        $rpIdParts = explode('.', $rpId, 2);

        return isset($wildcardRules[$rpIdParts[1] ?? '']);
    }

    private function throwInvalidProductionRpId(): never
    {
        throw new InvalidArgumentException(
            'Production passkey relying party ID must be a public host name.'
        );
    }
}
