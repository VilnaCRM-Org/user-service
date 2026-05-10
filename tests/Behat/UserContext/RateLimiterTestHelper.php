<?php

declare(strict_types=1);

namespace App\Tests\Behat\UserContext;

use Symfony\Component\RateLimiter\RateLimiterFactory;

final readonly class RateLimiterTestHelper
{
    private const LOOPBACK_IPS = ['127.0.0.1', '::1'];

    public function __construct(private RedisDatabaseMirror $redisDatabaseMirror)
    {
    }

    public function consume(RateLimiterFactory $limiter, string $key, int $count): void
    {
        for ($i = 0; $i < $count; ++$i) {
            $limiter->create($key)->consume();
        }

        $this->redisDatabaseMirror->mirrorDefaultLimiterStateToHttpDatabase();
    }

    public function consumeLoopbackLimiter(
        RateLimiterFactory $limiter,
        UserOperationsState $state,
        int $count
    ): void {
        $state->clientIpAddress = self::LOOPBACK_IPS[0];

        foreach ($this->loopbackIpKeys() as $loopbackIpKey) {
            $this->consume($limiter, $loopbackIpKey, $count);
        }
    }

    public function buildUserKey(string $userId): string
    {
        return sprintf('user:%s', $userId);
    }

    public function buildEmailKey(string $email): string
    {
        return sprintf('email:%s', strtolower(trim($email)));
    }

    /**
     * @return list<string>
     */
    private function loopbackIpKeys(): array
    {
        return array_map(
            static fn (string $loopbackIp): string => sprintf('ip:%s', $loopbackIp),
            self::LOOPBACK_IPS
        );
    }
}
