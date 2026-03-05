<?php

declare(strict_types=1);

namespace App\Tests\Unit\DataFixtures\Command;

use App\Tests\Unit\UnitTestCase;
use App\User\Application\Generator\AccessTokenGeneratorInterface;
use App\User\Domain\Entity\AuthSession;
use App\User\Domain\Factory\AuthSessionFactory;
use App\User\Infrastructure\Fixture\Command\AttachLoadTestAccessTokensCommand;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Uid\Factory\UlidFactory;
use Symfony\Component\Uid\Factory\UuidFactory;

final class AttachLoadTestAccessTokensCommandTest extends UnitTestCase
{
    private string $tempDir;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/attach-tokens-test-' . uniqid();
        mkdir($this->tempDir . '/tests/Load', 0777, true);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $usersFile = $this->usersFilePath();
        if (file_exists($usersFile)) {
            unlink($usersFile);
        }
        rmdir($this->tempDir . '/tests/Load');
        rmdir($this->tempDir . '/tests');
        rmdir($this->tempDir);
        parent::tearDown();
    }

    public function testExecuteAttachesTokensToUsers(): void
    {
        $this->seedTwoUsers();
        $deps = $this->createDependenciesExpectingPersist(2);
        $tester = new CommandTester($deps['command']);

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
        $this->assertWrittenUsersHaveTokens(2);
        $this->assertStringContainsString('2 users', $tester->getDisplay());
    }

    public function testExecuteSkipsUsersWithMissingFields(): void
    {
        $this->seedUsersWithMissingFields();
        $deps = $this->createDependenciesExpectingPersist(1);

        $status = $this->executeCommand($deps['command']);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertOnlyThirdUserHasToken();
    }

    public function testExecuteFailsWhenUsersFileDoesNotExist(): void
    {
        $deps = $this->createDependencies();
        $deps['documentManager']
            ->expects($this->never())
            ->method('persist');

        $tester = new CommandTester($deps['command']);

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $this->assertStringContainsString(
            'Unable to read or decode users file.',
            $tester->getDisplay()
        );
    }

    public function testExecuteFailsWhenUsersFileContainsInvalidJson(): void
    {
        file_put_contents($this->usersFilePath(), 'not-json');

        $deps = $this->createDependencies();
        $tester = new CommandTester($deps['command']);

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $this->assertStringContainsString(
            'Unable to read or decode users file.',
            $tester->getDisplay()
        );
    }

    public function testExecuteFailsWhenUsersFileContainsNonArray(): void
    {
        file_put_contents($this->usersFilePath(), '"just-a-string"');

        $deps = $this->createDependencies();
        $tester = new CommandTester($deps['command']);

        $this->assertSame(Command::FAILURE, $tester->execute([]));
    }

    public function testExecuteFailsWhenUsersFileIsEmpty(): void
    {
        file_put_contents($this->usersFilePath(), '');

        $deps = $this->createDependencies();
        $tester = new CommandTester($deps['command']);

        $this->assertSame(Command::FAILURE, $tester->execute([]));
    }

    public function testExecuteHandlesEmptyUsersArray(): void
    {
        $this->writeUsersFile([]);
        $deps = $this->createDependencies();
        $deps['documentManager']
            ->expects($this->never())
            ->method('persist');
        $deps['documentManager']
            ->expects($this->once())
            ->method('flush');

        $tester = new CommandTester($deps['command']);

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testExecuteFailsWhenUsersFileCannotBeEncodedForWrite(): void
    {
        $this->writeUsersFile([
            ['id' => 'user-1', 'email' => 'a@example.com', 'confirmed' => false],
        ]);
        $deps = $this->createDependencies();
        $this->expectWriteFailureDuringEncoding(
            $deps['documentManager'],
            $deps['accessTokenGenerator']
        );

        $tester = new CommandTester($deps['command']);

        $this->assertSame(Command::FAILURE, $tester->execute([]));
        $this->assertStringContainsString('Unable to write users file.', $tester->getDisplay());
    }

    private function seedTwoUsers(): void
    {
        $this->writeUsersFile([
            ['id' => 'user-1', 'email' => 'a@example.com', 'confirmed' => false],
            ['id' => 'user-2', 'email' => 'b@example.com', 'confirmed' => true],
        ]);
    }

    private function seedUsersWithMissingFields(): void
    {
        $this->writeUsersFile([
            ['id' => 'user-1'],
            ['email' => 'b@example.com'],
            ['id' => 'user-3', 'email' => 'c@example.com'],
        ]);
    }

    private function executeCommand(
        AttachLoadTestAccessTokensCommand $command
    ): int {
        return (new CommandTester($command))->execute([]);
    }

    /**
     * @return array{
     *     command: AttachLoadTestAccessTokensCommand,
     *     documentManager: DocumentManager&MockObject,
     *     accessTokenGenerator: AccessTokenGeneratorInterface&MockObject
     * }
     */
    private function createDependenciesExpectingPersist(
        int $persistCount
    ): array {
        $deps = $this->createDependencies();
        $this->expectSessionPersistence($deps['documentManager'], $persistCount);
        $this->expectTokenGeneration($deps['accessTokenGenerator'], $persistCount);

        return $deps;
    }

    private function expectSessionPersistence(
        DocumentManager&MockObject $documentManager,
        int $persistCount
    ): void {
        $documentManager
            ->expects($this->exactly($persistCount))
            ->method('persist')
            ->with($this->callback(
                function (mixed $session): bool {
                    if (!$session instanceof AuthSession) {
                        return false;
                    }

                    $this->assertFalse($session->isRememberMe());

                    return true;
                }
            ));
        $documentManager
            ->expects($this->once())
            ->method('flush');
    }

    private function expectTokenGeneration(
        AccessTokenGeneratorInterface&MockObject $accessTokenGenerator,
        int $persistCount
    ): void {
        $accessTokenGenerator
            ->expects($this->exactly($persistCount))
            ->method('generate')
            ->with($this->callback(
                function (array $claims): bool {
                    $this->assertGreaterThan($claims['iat'], $claims['exp']);
                    $this->assertIsString($claims['sub']);
                    $this->assertIsString($claims['sid']);
                    $this->assertIsString($claims['jti']);
                    $this->assertSame(['ROLE_USER'], $claims['roles']);

                    return true;
                }
            ))->willReturn('fake-jwt-token');
    }

    private function expectWriteFailureDuringEncoding(
        DocumentManager&MockObject $documentManager,
        AccessTokenGeneratorInterface&MockObject $accessTokenGenerator
    ): void {
        $documentManager
            ->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(AuthSession::class));
        $documentManager
            ->expects($this->once())
            ->method('flush');
        $accessTokenGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn("\xB1\x31");
    }

    private function assertWrittenUsersHaveTokens(int $count): void
    {
        $written = $this->readWrittenUsers();
        $this->assertCount($count, $written);
        foreach ($written as $user) {
            $this->assertSame('fake-jwt-token', $user['accessToken']);
        }
    }

    private function assertOnlyThirdUserHasToken(): void
    {
        $written = $this->readWrittenUsers();
        $this->assertArrayNotHasKey('accessToken', $written[0]);
        $this->assertArrayNotHasKey('accessToken', $written[1]);
        $this->assertSame('fake-jwt-token', $written[2]['accessToken']);
    }

    /**
     * @return list<array<string, string|bool>>
     */
    private function readWrittenUsers(): array
    {
        return json_decode(
            (string) file_get_contents($this->usersFilePath()),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    /**
     * @return array{
     *     command: AttachLoadTestAccessTokensCommand,
     *     documentManager: DocumentManager&MockObject,
     *     accessTokenGenerator: AccessTokenGeneratorInterface&MockObject
     * }
     */
    private function createDependencies(): array
    {
        $documentManager = $this->createMock(DocumentManager::class);
        $accessTokenGenerator = $this->createMock(AccessTokenGeneratorInterface::class);

        $command = new AttachLoadTestAccessTokensCommand(
            $documentManager,
            $accessTokenGenerator,
            new AuthSessionFactory(),
            new UlidFactory(),
            new UuidFactory(),
            $this->tempDir,
        );

        return [
            'command' => $command,
            'documentManager' => $documentManager,
            'accessTokenGenerator' => $accessTokenGenerator,
        ];
    }

    /**
     * @param list<array<string, string|bool>> $users
     */
    private function writeUsersFile(array $users): void
    {
        file_put_contents(
            $this->usersFilePath(),
            json_encode($users, JSON_THROW_ON_ERROR)
        );
    }

    private function usersFilePath(): string
    {
        return $this->tempDir . '/tests/Load/users.json';
    }
}
