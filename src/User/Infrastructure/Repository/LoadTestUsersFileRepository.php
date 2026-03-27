<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Repository;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class LoadTestUsersFileRepository
{
    private const USERS_FILE_RELATIVE_PATH = 'tests/Load/users.json';

    public function __construct(
        private SerializerInterface $serializer,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * @return list<array<string, string|bool>>|null
     */
    public function load(): ?array
    {
        $path = $this->path();
        if (!file_exists($path)) {
            return null;
        }

        try {
            $users = $this->serializer->decode(
                (string) file_get_contents($path),
                JsonEncoder::FORMAT,
                [JsonDecode::ASSOCIATIVE => true],
            );
        } catch (NotEncodableValueException) {
            return null;
        }

        return is_array($users) ? $users : null;
    }

    /**
     * @param list<array<string, string|bool>> $users
     */
    public function save(array $users): bool
    {
        try {
            $encoded = $this->serializer->encode($users, JsonEncoder::FORMAT);
        } catch (NotEncodableValueException) {
            return false;
        }

        return file_put_contents($this->path(), $encoded) !== false;
    }

    private function path(): string
    {
        return $this->projectDir . '/' . self::USERS_FILE_RELATIVE_PATH;
    }
}
