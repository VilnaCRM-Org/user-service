<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Factory;

/**
 * Independent SigV4 (query-string) check of an ElastiCache IAM auth token,
 * written from the AWS SigV4 specification rather than the SDK signer.
 */
final class SigV4PresignedTokenVerifier
{
    private const EMPTY_PAYLOAD_SHA256 =
        'e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855';

    public function expectedSignature(string $token, string $secretKey, string $region): string
    {
        [$host, $query] = $this->split($token);
        $parameters = $this->parameters($query);
        unset($parameters['X-Amz-Signature']);
        $date = $parameters['X-Amz-Date'];
        $shortDate = substr($date, 0, 8);
        $stringToSign = implode("\n", [
            'AWS4-HMAC-SHA256',
            $date,
            sprintf('%s/%s/elasticache/aws4_request', $shortDate, $region),
            hash('sha256', $this->canonicalRequest($host, $parameters)),
        ]);
        $signingKey = $this->signingKey($secretKey, $shortDate, $region);

        return hash_hmac('sha256', $stringToSign, $signingKey);
    }

    /**
     * @return array<string, string>
     */
    public function parameters(string $query): array
    {
        $parameters = [];
        foreach (explode('&', $query) as $pair) {
            [$name, $value] = explode('=', $pair, 2);
            $parameters[rawurldecode($name)] = rawurldecode($value);
        }

        return $parameters;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function split(string $token): array
    {
        [$hostAndPath, $query] = explode('?', $token, 2);

        return [rtrim($hostAndPath, '/'), $query];
    }

    /**
     * @param array<string, string> $parameters
     */
    private function canonicalRequest(string $host, array $parameters): string
    {
        return implode("\n", [
            'GET',
            '/',
            $this->canonicalQuery($parameters),
            'host:' . $host,
            '',
            'host',
            self::EMPTY_PAYLOAD_SHA256,
        ]);
    }

    /**
     * @param array<string, string> $parameters
     */
    private function canonicalQuery(array $parameters): string
    {
        ksort($parameters, SORT_STRING);
        $pairs = [];
        foreach ($parameters as $name => $value) {
            $pairs[] = rawurlencode($name) . '=' . rawurlencode($value);
        }

        return implode('&', $pairs);
    }

    private function signingKey(string $secretKey, string $shortDate, string $region): string
    {
        $dateKey = hash_hmac('sha256', $shortDate, 'AWS4' . $secretKey, true);
        $regionKey = hash_hmac('sha256', $region, $dateKey, true);
        $serviceKey = hash_hmac('sha256', 'elasticache', $regionKey, true);

        return hash_hmac('sha256', 'aws4_request', $serviceKey, true);
    }
}
