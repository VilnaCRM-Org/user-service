<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use App\User\Infrastructure\Repository\LoadTestUsersFileRepository;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function json_encode;
use const JSON_THROW_ON_ERROR;
use function mkdir;
use function rmdir;
use function sprintf;
use function sys_get_temp_dir;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use function unlink;

final class LoadTestUsersFileRepositoryTest extends UnitTestCase
{
    private string $tempDir;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sprintf(
            '%s/load-test-users-%s',
            sys_get_temp_dir(),
            $this->faker->uuid()
        );

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

    public function testLoadReturnsUsersFromExistingFile(): void
    {
        $users = [
            ['id' => $this->faker->uuid(), 'email' => $this->faker->email(), 'confirmed' => true],
        ];
        file_put_contents(
            $this->usersFilePath(),
            json_encode($users, JSON_THROW_ON_ERROR)
        );

        self::assertSame($users, $this->createRepository()->load());
    }

    public function testLoadUsesAssociativeDecodeContext(): void
    {
        $users = [
            ['id' => $this->faker->uuid(), 'email' => $this->faker->email(), 'confirmed' => true],
        ];
        $json = json_encode($users, JSON_THROW_ON_ERROR);
        file_put_contents($this->usersFilePath(), $json);
        $serializer = $this->createMock(Serializer::class);
        $serializer
            ->expects($this->once())
            ->method('decode')
            ->with(
                $json,
                JsonEncoder::FORMAT,
                [JsonDecode::ASSOCIATIVE => true]
            )
            ->willReturn($users);

        $repository = new LoadTestUsersFileRepository($serializer, $this->tempDir);

        self::assertSame($users, $repository->load());
    }

    public function testLoadReturnsNullWhenFileIsMissing(): void
    {
        self::assertNull($this->createRepository()->load());
    }

    public function testLoadReturnsNullWhenJsonIsInvalid(): void
    {
        file_put_contents($this->usersFilePath(), 'not-json');

        self::assertNull($this->createRepository()->load());
    }

    public function testLoadReturnsNullWhenPayloadIsNotArray(): void
    {
        file_put_contents(
            $this->usersFilePath(),
            json_encode($this->faker->word(), JSON_THROW_ON_ERROR)
        );

        self::assertNull($this->createRepository()->load());
    }

    public function testSaveWritesUsersToFile(): void
    {
        $users = [
            ['id' => $this->faker->uuid(), 'email' => $this->faker->email(), 'confirmed' => false],
        ];

        self::assertTrue($this->createRepository()->save($users));
        self::assertSame(
            $users,
            json_decode(
                (string) file_get_contents($this->usersFilePath()),
                true,
                512,
                JSON_THROW_ON_ERROR
            )
        );
    }

    public function testSaveReturnsFalseWhenEncodingFails(): void
    {
        $users = [
            ['id' => $this->faker->uuid(), 'email' => "\xB1\x31", 'confirmed' => false],
        ];

        self::assertFalse($this->createRepository()->save($users));
    }

    private function createRepository(): LoadTestUsersFileRepository
    {
        return new LoadTestUsersFileRepository(
            $this->createJsonSerializer(),
            $this->tempDir
        );
    }

    private function usersFilePath(): string
    {
        return $this->tempDir . '/tests/Load/users.json';
    }
}
