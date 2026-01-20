<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventListener;

use App\Shared\Domain\Bus\Event\EventBusInterface;
use App\Shared\Infrastructure\Cache\CacheKeyBuilder;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\Event\UserDeletedEventFactoryInterface;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Evaluator\SchemathesisCleanupEvaluator;
use App\User\Infrastructure\Extractor\SchemathesisEmailExtractor;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Uid\Factory\UuidFactory;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsEventListener(event: KernelEvents::TERMINATE)]
final class SchemathesisCleanupListener
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EventBusInterface $eventBus,
        private readonly UuidFactory $uuidFactory,
        private readonly UserDeletedEventFactoryInterface $eventFactory,
        private readonly SchemathesisCleanupEvaluator $evaluator,
        private readonly SchemathesisEmailExtractor $emailExtractor,
        private readonly TagAwareCacheInterface $cache,
        private readonly CacheKeyBuilder $cacheKeyBuilder
    ) {
    }

    public function __invoke(TerminateEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();

        if (! $this->evaluator->shouldCleanup($request, $response)) {
            return;
        }

        $this->deleteUsers($this->emailExtractor->extract($request));
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

        $this->userRepository->deleteBatch($users);
        $this->publishUserDeletedEvents($users);
        $this->cache->invalidateTags($this->buildInvalidationTags($users));
    }

    /**
     * @param array<int, UserInterface> $users
     */
    private function publishUserDeletedEvents(array $users): void
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
     * @param array<int, UserInterface> $users
     *
     * @return array<int, string>
     */
    private function buildInvalidationTags(array $users): array
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
