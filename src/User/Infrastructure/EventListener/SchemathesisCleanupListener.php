<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventListener;

use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Resolver\SchemathesisCleanupResolver;
use App\User\Infrastructure\Resolver\SchemathesisEmailResolver;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

final class SchemathesisCleanupListener
{
    public const ENVIRONMENT = 'schemathesis';

    public function __construct(
        private readonly string $appEnv,
        private readonly UserRepositoryInterface $userRepository,
        private readonly SchemathesisCleanupResolver $schemathesisCleanupMatcher,
        private readonly SchemathesisEmailResolver $emailExtractor
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
        //
        // The cleanup is intentionally DELETE-ONLY: it must not emit domain
        // events or invalidate cache. That terminate-phase work runs synchronous
        // subscribers inside the worker loop and was the source of the connection
        // resets. The test database is reset between Schemathesis runs, so cache
        // consistency during a run is irrelevant.
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

        $this->userRepository->deleteBatch(new UserCollection($users));
    }
}
