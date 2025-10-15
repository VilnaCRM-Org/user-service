<?php

declare(strict_types=1);

namespace App\User\Infrastructure\EventListener;

use App\User\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::TERMINATE)]
final class SchemathesisCleanupListener
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly SchemathesisCleanupEvaluator $evaluator,
        private readonly SchemathesisEmailExtractor $emailExtractor
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
        $users = array_filter(
            array_map(
                fn (string $email) => $this->userRepository
                    ->findByEmail($email),
                array_unique($emails)
            )
        );

        foreach ($users as $user) {
            $this->userRepository->delete($user);
        }
    }
}
