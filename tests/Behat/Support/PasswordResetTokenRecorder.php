<?php

declare(strict_types=1);

namespace App\Tests\Behat\Support;

use App\Shared\Domain\Bus\Event\DomainEventSubscriberInterface;
use App\User\Domain\Event\PasswordResetRequestedEvent;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Captures the plaintext password reset token emitted while handling the reset
 * request. Only the hash is persisted, so the confirmation step replays the
 * plaintext recorded here instead of reading the stored hash.
 *
 * Behat drives the API over real HTTP (browserkit_http), so the reset event is
 * dispatched inside the web-server process while the confirmation step resolves
 * the token inside the Behat CLI process. The plaintext is therefore mirrored
 * through the shared Redis instance (both processes use the same REDIS_URL) so
 * it survives the process boundary. The in-memory copy still serves in-process
 * flows that dispatch the event within the test runner.
 */
final class PasswordResetTokenRecorder implements
    DomainEventSubscriberInterface,
    ResetInterface
{
    private const REDIS_KEY_PREFIX = 'behat:password-reset-token:';
    private const REDIS_LAST_KEY = 'behat:password-reset-token:__last__';
    private const REDIS_TTL_SECONDS = 600;

    private string $lastPlainToken = '';

    /** @var array<string, string> */
    private array $plainTokensByUserId = [];

    private ?\Redis $redis = null;
    private bool $redisUnavailable = false;

    public function __construct(
        #[Autowire('%env(REDIS_URL)%')]
        private readonly string $redisUrl = '',
    ) {
    }

    public function __invoke(PasswordResetRequestedEvent $event): void
    {
        $this->lastPlainToken = $event->token;
        $this->plainTokensByUserId[$event->userId] = $event->token;

        $this->writeToRedis($event->userId, $event->token);
    }

    public function getLastPlainToken(): string
    {
        if ($this->lastPlainToken !== '') {
            return $this->lastPlainToken;
        }

        return $this->readFromRedis(self::REDIS_LAST_KEY) ?? '';
    }

    public function getPlainTokenForUser(string $userId): ?string
    {
        if (isset($this->plainTokensByUserId[$userId])) {
            return $this->plainTokensByUserId[$userId];
        }

        return $this->readFromRedis(self::REDIS_KEY_PREFIX . $userId);
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{PasswordResetRequestedEvent::class}
     */
    #[\Override]
    public function subscribedTo(): array
    {
        return [PasswordResetRequestedEvent::class];
    }

    #[\Override]
    public function reset(): void
    {
        // Only the in-memory copy is reset here. The shared Redis mirror is
        // owned by the web-server process and is purged per scenario by
        // UserContext::clearCacheBeforeScenario(), so clearing it here could
        // race with a write the web server just made for the current scenario.
        $this->lastPlainToken = '';
        $this->plainTokensByUserId = [];
    }

    private function writeToRedis(string $userId, string $token): void
    {
        $redis = $this->connection();
        if ($redis === null) {
            return;
        }

        $redis->setex(self::REDIS_KEY_PREFIX . $userId, self::REDIS_TTL_SECONDS, $token);
        $redis->setex(self::REDIS_LAST_KEY, self::REDIS_TTL_SECONDS, $token);
    }

    private function readFromRedis(string $key): ?string
    {
        $redis = $this->connection();
        if ($redis === null) {
            return null;
        }

        $value = $redis->get($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function connection(): ?\Redis
    {
        if ($this->redis instanceof \Redis) {
            return $this->redis;
        }

        if ($this->redisUnavailable || $this->redisUrl === '') {
            return null;
        }

        $this->redis = $this->createConnection();
        if ($this->redis === null) {
            $this->redisUnavailable = true;
        }

        return $this->redis;
    }

    private function createConnection(): ?\Redis
    {
        $parts = parse_url($this->redisUrl);
        if (!is_array($parts) || !is_string($parts['host'] ?? null) || $parts['host'] === '') {
            return null;
        }

        try {
            $redis = new \Redis();
            if ($redis->connect($parts['host'], (int) ($parts['port'] ?? 6379)) !== true) {
                return null;
            }

            $password = $parts['pass'] ?? null;
            if (is_string($password) && $password !== '') {
                $redis->auth($password);
            }

            $database = isset($parts['path']) ? (int) ltrim((string) $parts['path'], '/') : 0;
            $redis->select($database);

            return $redis;
        } catch (\RedisException) {
            return null;
        }
    }
}
