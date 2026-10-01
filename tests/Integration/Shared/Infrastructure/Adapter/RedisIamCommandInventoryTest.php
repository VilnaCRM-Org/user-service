<?php

declare(strict_types=1);

namespace App\Tests\Integration\Shared\Infrastructure\Adapter;

use App\OAuth\Domain\ValueObject\OAuthStatePayload;
use App\OAuth\Infrastructure\Repository\RedisOAuthStateRepository;
use App\User\Infrastructure\Provider\RedisAccountLockoutProvider;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * The app's Redis users run under the reviewed AD-02 access string
 * "on ~* +@all -@dangerous" through an IAM-authenticated connection.
 */
final class RedisIamCommandInventoryTest extends RedisIamIntegrationTestCase
{
    private \Redis $redis;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->acceptOnlyTokens($this->currentToken());
        $this->redis = $this->connectionFactory()->create($this->dsn);
    }

    public function testAccountLockoutRunsUnderReviewedAccessString(): void
    {
        $lockout = new RedisAccountLockoutProvider($this->redis);
        $email = $this->faker->unique()->safeEmail();

        self::assertFalse($lockout->recordFailure($email));
        self::assertFalse($lockout->isLocked($email));
        $lockout->clearFailures($email);
        self::assertFalse($lockout->isLocked($email));
    }

    public function testOAuthStateRunsUnderReviewedAccessString(): void
    {
        $serializer = $this->container->get(SerializerInterface::class);
        $repository = new RedisOAuthStateRepository($this->redis, $serializer);
        $state = $this->faker->sha256();
        $flowBinding = $this->faker->sha256();
        $provider = $this->faker->randomElement(['github', 'google']);
        $payload = new OAuthStatePayload(
            $provider,
            $this->faker->sha256(),
            hash('sha256', $flowBinding),
            $this->faker->url(),
            new \DateTimeImmutable()
        );

        $repository->save($state, $payload, 60);

        self::assertSame(
            $payload->codeVerifier,
            $repository->validateAndConsume($state, $provider, $flowBinding)->codeVerifier
        );
    }

    public function testTagAwareCacheAndRateLimiterRunUnderReviewedAccessString(): void
    {
        $namespace = $this->faker->lexify('it????');
        $cache = new TagAwareAdapter(new RedisAdapter($this->redis, $namespace));
        $tag = $this->faker->word();
        $item = $cache->getItem($this->faker->uuid())->set($this->faker->sentence())->tag($tag);

        self::assertTrue($cache->save($item));
        self::assertTrue($cache->getItem($item->getKey())->isHit());
        self::assertTrue($cache->invalidateTags([$tag]));
        self::assertFalse($cache->getItem($item->getKey())->isHit());

        $limiter = (new RateLimiterFactory(
            [
                'id' => $namespace,
                'policy' => 'token_bucket',
                'limit' => 2,
                'rate' => ['interval' => '1 minute'],
            ],
            new CacheStorage(new RedisAdapter($this->redis, $namespace))
        ))->create($this->faker->ipv4());

        self::assertTrue($limiter->consume()->isAccepted());
        self::assertTrue($limiter->consume()->isAccepted());
        self::assertFalse($limiter->consume()->isAccepted());
    }

    public function testCachePoolClearCommandsAreOutsideReviewedAccessString(): void
    {
        foreach (['INFO', 'FLUSHDB', 'KEYS'] as $command) {
            $result = $this->admin->rawCommand('ACL', 'DRYRUN', $this->userId, $command, '*');

            self::assertIsString($result);
            self::assertStringContainsString('has no permissions', $result);
        }

        self::assertTrue($this->admin->rawCommand('ACL', 'DRYRUN', $this->userId, 'SCAN', '0'));
    }
}
