<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventListener;

use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Repository\UserRepositoryInterface;
use App\User\Infrastructure\Resolver\SchemathesisCleanupResolver;
use App\User\Infrastructure\Resolver\SchemathesisEmailResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

final class SchemathesisCleanupListener
{
    public const ENVIRONMENT = 'schemathesis';

    public function __construct(
        private readonly string $appEnv,
        private readonly UserRepositoryInterface $userRepository,
        private readonly SchemathesisCleanupResolver $schemathesisCleanupMatcher,
        private readonly SchemathesisEmailResolver $emailExtractor,
        private readonly LoggerInterface $logger
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

        $this->performBestEffortCleanup($request);
    }

    private function performBestEffortCleanup(Request $request): void
    {
        try {
            $this->deleteUsers($this->emailExtractor->extract($request));
        } catch (\Throwable $exception) {
            $this->logCleanupFailure($exception);
        }
    }

    private function logCleanupFailure(\Throwable $exception): void
    {
        try {
            $this->logger->warning(
                'Schemathesis test cleanup skipped after a failure.',
                ['exception' => $exception]
            );
        } catch (\Throwable) {
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
