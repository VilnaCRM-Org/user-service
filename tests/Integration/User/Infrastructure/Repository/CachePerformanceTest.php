<?php

declare(strict_types=1);

namespace Tests\Integration\User\Infrastructure\Repository;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;
use App\User\Domain\Repository\UserRepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid as SymfonyUuid;

/**
 * Performance tests to verify caching actually improves response times.
 *
 * These tests measure actual latency differences between cache hits and misses
 * to ensure the caching implementation provides real performance benefits.
 */
final class CachePerformanceTest extends KernelTestCase
{
    private const PERFORMANCE_ITERATIONS = 10;
    private const MAX_CACHE_HIT_LATENCY_MS = 10;
    private const MIN_SPEEDUP_FACTOR = 2.0;

    private UserRepositoryInterface $repository;
    private CacheItemPoolInterface $cachePool;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::getContainer()->get(UserRepositoryInterface::class);
        $this->cachePool = self::getContainer()->get('cache.user');

        $this->cachePool->clear();
    }

    public function testCacheHitIsSignificantlyFasterThanMiss(): void
    {
        $user = $this->createTestUser(
            'Performance Test',
            sprintf('perf+%s@example.com', (string) $this->generateUuid())
        );

        $this->cachePool->clear();

        $cacheMissStart = hrtime(true);
        $this->repository->find($user->getId());
        $cacheMissEnd = hrtime(true);
        $cacheMissLatencyNs = $cacheMissEnd - $cacheMissStart;

        $cacheHitStart = hrtime(true);
        $this->repository->find($user->getId());
        $cacheHitEnd = hrtime(true);
        $cacheHitLatencyNs = $cacheHitEnd - $cacheHitStart;

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

    public function testAverageCacheHitLatencyIsAcceptable(): void
    {
        $user = $this->createTestUser(
            'Latency Test',
            sprintf('latency+%s@example.com', (string) $this->generateUuid())
        );

        $this->repository->find($user->getId());

        $totalLatencyNs = 0;
        for ($i = 0; $i < self::PERFORMANCE_ITERATIONS; $i++) {
            $start = hrtime(true);
            $this->repository->find($user->getId());
            $end = hrtime(true);
            $totalLatencyNs += $end - $start;
        }

        $averageLatencyMs = $totalLatencyNs / self::PERFORMANCE_ITERATIONS / 1_000_000;

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

    public function testCacheHitRatioAfterWarmup(): void
    {
        $users = [];
        for ($i = 0; $i < 5; $i++) {
            $users[] = $this->createTestUser(
                sprintf('User %d', $i),
                sprintf('user%d+%s@example.com', $i, (string) $this->generateUuid())
            );
        }

        foreach ($users as $user) {
            $this->repository->find($user->getId());
        }

        $hits = 0;
        $total = 0;
        foreach ($users as $user) {
            for ($j = 0; $j < 3; $j++) {
                $cacheKey = 'user.' . $user->getId();
                $isHit = $this->cachePool->getItem($cacheKey)->isHit();
                if ($isHit) {
                    $hits++;
                }
                $total++;
                $this->repository->find($user->getId());
            }
        }

        $hitRatio = $hits / $total;

        self::assertGreaterThanOrEqual(
            0.9,
            $hitRatio,
            sprintf(
                'Cache hit ratio (%.1f%%) should be at least 90%% after warmup',
                $hitRatio * 100
            )
        );
    }

    public function testEmailLookupCachePerformance(): void
    {
        $email = sprintf('email-perf+%s@example.com', (string) $this->generateUuid());
        $this->createTestUser('Email Perf Test', $email);

        $this->cachePool->clear();

        $cacheMissStart = hrtime(true);
        $this->repository->findByEmail($email);
        $cacheMissEnd = hrtime(true);
        $cacheMissLatencyNs = $cacheMissEnd - $cacheMissStart;

        $cacheHitStart = hrtime(true);
        $this->repository->findByEmail($email);
        $cacheHitEnd = hrtime(true);
        $cacheHitLatencyNs = $cacheHitEnd - $cacheHitStart;

        self::assertLessThan(
            $cacheMissLatencyNs,
            $cacheHitLatencyNs,
            'Email lookup cache hit should be faster than cache miss'
        );

        $emailHash = hash('sha256', strtolower($email));
        self::assertTrue(
            $this->cachePool->getItem('user.email.' . $emailHash)->isHit(),
            'Email lookup should be cached after first query'
        );
    }

    public function testCacheRecoveryAfterInvalidation(): void
    {
        $user = $this->createTestUser(
            'Invalidation Perf',
            sprintf('invalidation+%s@example.com', (string) $this->generateUuid())
        );

        $this->repository->find($user->getId());
        self::assertTrue(
            $this->cachePool->getItem('user.' . $user->getId())->isHit(),
            'Cache should be populated after first query'
        );

        $this->cachePool->clear();
        self::assertFalse(
            $this->cachePool->getItem('user.' . $user->getId())->isHit(),
            'Cache should be empty after clear'
        );

        $this->repository->find($user->getId());
        self::assertTrue(
            $this->cachePool->getItem('user.' . $user->getId())->isHit(),
            'Cache should be repopulated after query following clear'
        );

        $cacheHitStart = hrtime(true);
        $this->repository->find($user->getId());
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

    private function createTestUser(string $initials, string $email): User
    {
        $user = new User(
            email: $email,
            initials: $initials,
            password: password_hash('test_password', PASSWORD_BCRYPT),
            id: $this->generateUuid()
        );

        $this->repository->save($user);

        return $user;
    }

    private function generateUuid(): Uuid
    {
        return new Uuid((string) SymfonyUuid::v4());
    }
}
