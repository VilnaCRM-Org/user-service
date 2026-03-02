<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Fixture\Command;

use App\User\Application\Generator\AccessTokenGeneratorInterface;
use App\User\Domain\Entity\AuthSession;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

/**
 * @psalm-api
 */
#[AsCommand(
    name: 'app:load-test:attach-access-tokens',
    description: self::COMMAND_DESCRIPTION
)]
final class AttachLoadTestAccessTokensCommand extends Command
{
    private const COMMAND_DESCRIPTION = 'Attach JWT access tokens to load-test users file.';
    private const USERS_FILE_RELATIVE_PATH = 'tests/Load/users.json';
    private const SESSION_IP_ADDRESS = '127.0.0.1';
    private const SESSION_USER_AGENT = 'k6-load-test';
    private const JWT_ISSUER = 'vilnacrm-user-service';
    private const JWT_AUDIENCE = 'vilnacrm-api';
    private const ACCESS_TOKEN_TTL_SECONDS = 900;

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly AccessTokenGeneratorInterface $accessTokenGenerator,
        private readonly UlidFactory $ulidFactory,
        private readonly UuidFactory $uuidFactory,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $io = new SymfonyStyle($input, $output);

        $usersFilePath = $this->projectDir
            . '/' . self::USERS_FILE_RELATIVE_PATH;

        $users = $this->readUsersFile($usersFilePath);
        if ($users === null) {
            $io->error('Unable to read or decode users file.');

            return Command::FAILURE;
        }

        $this->attachTokens($users);
        $this->documentManager->flush();

        // @codeCoverageIgnoreStart
        /** @infection-ignore-all */
        if (!$this->writeUsersFile($usersFilePath, $users)) {
            $io->error('Unable to write users file.');

            return Command::FAILURE;
        }
        // @codeCoverageIgnoreEnd

        $io->success(
            sprintf('Attached access tokens to %d users.', count($users))
        );

        return Command::SUCCESS;
    }

    /**
     * @return list<array<string, string|bool>>|null
     */
    private function readUsersFile(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }

        try {
            /** @infection-ignore-all */
            $users = json_decode(
                (string) file_get_contents($path),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\JsonException) {
            return null;
        }

        return is_array($users) ? $users : null;
    }

    /**
     * @param list<array<string, string|bool>> $users
     */
    private function attachTokens(array &$users): void
    {
        foreach ($users as &$user) {
            $this->processUser($user);
        }
        unset($user);
    }

    /**
     * @param array<string, string|bool> $user
     */
    private function processUser(array &$user): void
    {
        $userId = $user['id'] ?? null;
        $userEmail = $user['email'] ?? null;

        if (!is_string($userId) || !is_string($userEmail)) {
            return;
        }

        $now = new DateTimeImmutable();
        $sessionId = (string) $this->ulidFactory->create();

        $this->persistSession($sessionId, $userId, $now);
        $user['accessToken'] = $this->generateToken(
            $userEmail,
            $sessionId,
            $now
        );
    }

    private function persistSession(
        string $sessionId,
        string $userId,
        DateTimeImmutable $now,
    ): void {
        $this->documentManager->persist(
            new AuthSession(
                $sessionId,
                $userId,
                self::SESSION_IP_ADDRESS,
                self::SESSION_USER_AGENT,
                $now,
                $now->modify('+15 minutes'),
                false
            )
        );
    }

    private function generateToken(
        string $userEmail,
        string $sessionId,
        DateTimeImmutable $now,
    ): string {
        $issuedAt = $now->getTimestamp();

        return $this->accessTokenGenerator->generate([
            'sub' => $userEmail,
            'iss' => self::JWT_ISSUER,
            'aud' => self::JWT_AUDIENCE,
            'exp' => $issuedAt + self::ACCESS_TOKEN_TTL_SECONDS,
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'jti' => (string) $this->uuidFactory->create(),
            'sid' => $sessionId,
            'roles' => ['ROLE_USER'],
        ]);
    }

    /**
     * @param list<array<string, string|bool>> $users
     *
     * @codeCoverageIgnore Cannot test filesystem write failures as root in Docker
     *
     * @infection-ignore-all
     */
    private function writeUsersFile(string $path, array $users): bool
    {
        $encoded = json_encode($users);
        if ($encoded === false) {
            return false;
        }

        return file_put_contents($path, $encoded) !== false;
    }
}
