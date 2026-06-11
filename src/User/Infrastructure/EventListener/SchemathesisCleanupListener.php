<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventListener;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Factory\Event\UserDeletedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Resolver\SchemathesisCleanupResolver;
use App\User\Infrastructure\Resolver\SchemathesisEmailResolver;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class SchemathesisCleanupListener
{
    public const ENVIRONMENT = 'schemathesis';

    public function __construct(
        private readonly string $appEnv,
        private readonly UserRepositoryInterface $userRepository,
        private readonly EventBusInterface $eventBus,
        private readonly UuidFactory $uuidFactory,
        private readonly UserDeletedEventFactoryInterface $eventFactory,
        private readonly SchemathesisCleanupResolver $schemathesisCleanupMatcher,
        private readonly SchemathesisEmailResolver $emailExtractor,
        private readonly TagAwareCacheInterface $cache,
        private readonly CacheKeyBuilder $cacheKeyBuilder
    ) {
    }

    public function __invoke(TerminateEvent $event): void
    {
        if ($this->appEnv !== self::ENVIRONMENT) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        if (! $this->schemathesisCleanupMatcher->matches($request, $response)) {
            return;
        }

        // Best-effort, test-only cleanup running on kernel.terminate inside the
        // FrankenPHP worker: it must NEVER let an exception escape, or the worker
        // loop is disrupted and in-flight connections are reset mid-run
        // ("Connection reset by peer"). The response has already been sent, so a
        // failed cleanup is harmless beyond a leftover test user.
        try {
            $this->deleteUsers($this->emailExtractor->extract($request));
        } catch (\Throwable $exception) {
            error_log(sprintf(
                'Schemathesis cleanup skipped: %s',
                $exception->getMessage()
            ));
        }
    }

    /**
     * @param list<string> $emails
     */
    private function deleteUsers(array $emails): void
    {
        $users = array_values(
            array_filter(
                array_map(
                    fn (string $email) => $this->userRepository->findByEmail($email),
                    array_unique($emails)
                )
            )
        );

        if ($users === []) {
            return;
        }

        $collection = new UserCollection($users);
        $this->userRepository->deleteBatch($collection);
        $this->publishUserDeletedEvents($collection);
        $this->cache->invalidateTags($this->buildInvalidationTags($collection));
    }

    private function publishUserDeletedEvents(UserCollection $users): void
    {
        foreach ($users as $user) {
            $this->eventBus->publish(
                $this->eventFactory->create(
                    $user,
                    (string) $this->uuidFactory->create()
                )
            );
        }
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{0: string, 1?: string,...}
     */
    private function buildInvalidationTags(UserCollection $users): array
    {
        $tagsToInvalidate = ['user.collection'];

        foreach ($users as $user) {
            $tagsToInvalidate[] = 'user.' . $user->getId();
            $tagsToInvalidate[] = 'user.email.' . $this->cacheKeyBuilder->hashEmail(
                $user->getEmail()
            );
        }

        return $tagsToInvalidate;
    }
}
