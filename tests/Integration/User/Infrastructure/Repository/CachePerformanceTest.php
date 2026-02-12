<?php

declare(strict_types=1);

namespace Tests\Integration\User\Infrastructure\Repository;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tests\Integration\IntegrationTestCase;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

/**
 * Performance tests to verify caching actually improves response times.
 *
 * These tests measure actual latency differences between cache hits and misses
 * to ensure the caching implementation provides real performance benefits.
 */
final class CachePerformanceTest extends IntegrationTestCase
{
    private const PERFORMANCE_ITERATIONS = 10;
    private const MAX_CACHE_HIT_LATENCY_MS = 10;
    private const MIN_SPEEDUP_FACTOR = 2.0;

    private UserRepositoryInterface $repository;
    private CacheItemPoolInterface $cachePool;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get(UserRepositoryInterface::class);
        $this->cachePool = self::getContainer()->get('cache.user');

        $this->cachePool->clear();
    }

    public function testCacheHitIsSignificantlyFasterThanMiss(): void
    {
        $user = $this->createTestUser();
        $userId = $user->getId();

        $cacheMissLatencyNs = $this->measureAverageLatency($userId, true);
        $cacheHitLatencyNs = $this->measureAverageLatency($userId, false);

        $this->assertCacheHitFasterThanMiss($cacheMissLatencyNs, $cacheHitLatencyNs);
    }

    public function testAverageCacheHitLatencyIsAcceptable(): void
    {
        $user = $this->createTestUser();
        $userId = $user->getId();

        $this->repository->find($userId);
        $averageLatencyMs = $this->measureAverageLatency($userId, false) / 1_000_000;

        $this->assertAverageLatencyIsAcceptable($averageLatencyMs);
    }

    public function testCacheHitRatioAfterWarmup(): void
    {
        $users = $this->createTestUsers(5);
        $this->warmupCache($users);

        $hitRatio = $this->calculateCacheHitRatio($users);

        $this->assertMinimumHitRatio($hitRatio);
    }

    public function testEmailLookupCachePerformance(): void
    {
        $email = $this->faker->unique()->safeEmail();
        $this->createTestUserWithEmail($email);
        $this->cachePool->clear();

        $cacheMissLatencyNs = $this->measureEmailLookupLatency($email);
        $cacheHitLatencyNs = $this->measureEmailLookupLatency($email);

        $this->assertCacheHitIsFaster($cacheMissLatencyNs, $cacheHitLatencyNs);
        $this->assertEmailLookupIsCached($email);
    }

    public function testCacheRecoveryAfterInvalidation(): void
    {
        $user = $this->createTestUser();
        $userId = $user->getId();

        $this->assertCachePopulatesOnFirstQuery($userId);
        $this->assertCacheClearsCorrectly($userId);
        $this->assertCacheRepopulatesAfterClear($userId);
        $this->assertCacheHitLatencyAfterReWarmup($userId);
    }

    private function measureAverageLatency(string $userId, bool $clearCacheEachTime): int
    {
        if (!$clearCacheEachTime) {
            $this->repository->find($userId);
        }

        $totalLatencyNs = 0;
        for ($i = 0; $i < self::PERFORMANCE_ITERATIONS; $i++) {
            if ($clearCacheEachTime) {
                $this->cachePool->clear();
            }

            $start = hrtime(true);
            $this->repository->find($userId);
            $end = hrtime(true);

            $totalLatencyNs += $end - $start;
        }

        return (int) ($totalLatencyNs / self::PERFORMANCE_ITERATIONS);
    }

    private function assertCacheHitFasterThanMiss(int $cacheMissLatencyNs, int $cacheHitLatencyNs): void
    {
        $cacheMissLatencyMs = $cacheMissLatencyNs / 1_000_000;
        $cacheHitLatencyMs = $cacheHitLatencyNs / 1_000_000;

        self::assertLessThan(
            $cacheMissLatencyMs,
            $cacheHitLatencyMs,
            sprintf(
                'Cache hit (%.2fms) should be faster than cache miss (%.2fms)',
                $cacheHitLatencyMs,
                $cacheMissLatencyMs
            )
        );

        $this->assertMinimumSpeedupFactor($cacheMissLatencyMs, $cacheHitLatencyMs);
    }

    private function assertMinimumSpeedupFactor(float $cacheMissLatencyMs, float $cacheHitLatencyMs): void
    {
        if ($cacheMissLatencyMs > 0) {
            $speedupFactor = $cacheMissLatencyMs / max($cacheHitLatencyMs, 0.001);
            self::assertGreaterThanOrEqual(
                self::MIN_SPEEDUP_FACTOR,
                $speedupFactor,
                sprintf(
                    'Cache should provide at least %.1fx speedup, got %.1fx (miss: %.2fms, hit: %.2fms)',
                    self::MIN_SPEEDUP_FACTOR,
                    $speedupFactor,
                    $cacheMissLatencyMs,
                    $cacheHitLatencyMs
                )
            );
        }
    }

    private function assertAverageLatencyIsAcceptable(float $averageLatencyMs): void
    {
        self::assertLessThanOrEqual(
            self::MAX_CACHE_HIT_LATENCY_MS,
            $averageLatencyMs,
            sprintf(
                'Average cache hit latency (%.2fms) exceeds maximum allowed (%dms)',
                $averageLatencyMs,
                self::MAX_CACHE_HIT_LATENCY_MS
            )
        );
    }

    /**
     * @return User[]
     *
     * @psalm-return list{0?: User,...}
     */
    private function createTestUsers(int $count): array
    {
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $users[] = $this->createTestUser();
        }

        return $users;
    }

    /**
     * @param array<int, User> $users
     */
    private function warmupCache(array $users): void
    {
        foreach ($users as $user) {
            $this->repository->find($user->getId());
        }
    }

    /**
     * @param array<int, User> $users
     */
    private function calculateCacheHitRatio(array $users): float
    {
        $hits = 0;
        $total = 0;

        foreach ($users as $user) {
            for ($j = 0; $j < 3; $j++) {
                $cacheKey = 'user.' . $user->getId();
                if ($this->cachePool->getItem($cacheKey)->isHit()) {
                    $hits++;
                }
                $total++;
                $this->repository->find($user->getId());
            }
        }

        return $hits / $total;
    }

    private function assertMinimumHitRatio(float $hitRatio): void
    {
        self::assertGreaterThanOrEqual(
            0.9,
            $hitRatio,
            sprintf(
                'Cache hit ratio (%.1f%%) should be at least 90%% after warmup',
                $hitRatio * 100
            )
        );
    }

    private function measureEmailLookupLatency(string $email): int
    {
        $start = hrtime(true);
        $this->repository->findByEmail($email);
        $end = hrtime(true);

        return $end - $start;
    }

    private function assertCacheHitIsFaster(int $cacheMissLatencyNs, int $cacheHitLatencyNs): void
    {
        self::assertLessThan(
            $cacheMissLatencyNs,
            $cacheHitLatencyNs,
            'Email lookup cache hit should be faster than cache miss'
        );
    }

    private function assertEmailLookupIsCached(string $email): void
    {
        $emailHash = hash('sha256', strtolower($email));
        self::assertTrue(
            $this->cachePool->getItem('user.email.' . $emailHash)->isHit(),
            'Email lookup should be cached after first query'
        );
    }

    private function assertCachePopulatesOnFirstQuery(string $userId): void
    {
        $this->repository->find($userId);
        self::assertTrue(
            $this->cachePool->getItem('user.' . $userId)->isHit(),
            'Cache should be populated after first query'
        );
    }

    private function assertCacheClearsCorrectly(string $userId): void
    {
        $this->cachePool->clear();
        self::assertFalse(
            $this->cachePool->getItem('user.' . $userId)->isHit(),
            'Cache should be empty after clear'
        );
    }

    private function assertCacheRepopulatesAfterClear(string $userId): void
    {
        $this->repository->find($userId);
        self::assertTrue(
            $this->cachePool->getItem('user.' . $userId)->isHit(),
            'Cache should be repopulated after query following clear'
        );
    }

    private function assertCacheHitLatencyAfterReWarmup(string $userId): void
    {
        $cacheHitStart = hrtime(true);
        $this->repository->find($userId);
        $cacheHitEnd = hrtime(true);
        $cacheHitLatencyMs = ($cacheHitEnd - $cacheHitStart) / 1_000_000;

        self::assertLessThanOrEqual(
            self::MAX_CACHE_HIT_LATENCY_MS,
            $cacheHitLatencyMs,
            sprintf(
                'Cache hit after re-warmup (%.2fms) should still be fast (<%dms)',
                $cacheHitLatencyMs,
                self::MAX_CACHE_HIT_LATENCY_MS
            )
        );
    }

    private function createTestUser(): User
    {
        $user = new User(
            email: $this->faker->unique()->safeEmail(),
            initials: $this->faker->name(),
            password: password_hash($this->faker->password(12), PASSWORD_BCRYPT),
            id: $this->generateUuid()
        );

        $this->repository->save($user);

        return $user;
    }

    private function createTestUserWithEmail(string $email): void
    {
        $user = new User(
            email: $email,
            initials: $this->faker->name(),
            password: password_hash($this->faker->password(12), PASSWORD_BCRYPT),
            id: $this->generateUuid()
        );

        $this->repository->save($user);
    }

    private function generateUuid(): Uuid
    {
        return new Uuid((string) SymfonyUuid::v4());
    }
}
