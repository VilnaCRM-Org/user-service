<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Fixture\Seeder;

use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;
use App\Shared\Infrastructure\Fixture\Seeder\SchemathesisOAuthSeeder;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCodeInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

final class SchemathesisOAuthSeederTest extends UnitTestCase
{
    public function testSeedAuthorizationCodeRemovesExistingAuthorizationCode(): void
    {
        $client = $this->createTestClient();
        $user = $this->createTestUser();
        $userId = $user->getId();
        $existingCode = $this->createExistingAuthorizationCode($client, $userId);

        $documentManager = $this->createDocumentManagerMock($existingCode);
        $authorizationCodeManager = $this->createAuthCodeManager($existingCode);
        $clientManager = $this->createMock(ClientManagerInterface::class);

        $seeder = new SchemathesisOAuthSeeder(
            $clientManager,
            $documentManager,
            $authorizationCodeManager
        );
        $seeder->seedAuthorizationCode($client, $user);

        $this->assertNewCodeCreated($authorizationCodeManager, $existingCode, $userId);
    }

    private function createTestClient(): Client
    {
        return new Client(
            $this->faker->company(),
            $this->faker->lexify('client_????????'),
            $this->faker->optional()->sha1()
        );
    }

    private function createTestUser(): UserInterface
    {
        $userId = $this->faker->uuid();
        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($userId);

        return $user;
    }

    private function createExistingAuthorizationCode(
        Client $client,
        string $userId
    ): AuthorizationCode {
        return new AuthorizationCode(
            SchemathesisFixtures::AUTHORIZATION_CODE,
            new DateTimeImmutable('+10 minutes'),
            $client,
            $userId,
            [new Scope($this->faker->lexify('scope_????'))]
        );
    }

    private function createDocumentManagerMock(
        AuthorizationCode $existingCode
    ): DocumentManager {
        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())
            ->method('remove')
            ->with($existingCode);
        $documentManager->expects($this->once())->method('flush');

        return $documentManager;
    }

    private function createAuthCodeManager(
        AuthorizationCode $existingCode
    ): AuthorizationCodeManagerInterface {
        return new class($existingCode) implements AuthorizationCodeManagerInterface {
            private ?AuthorizationCodeInterface $savedCode = null;

            public function __construct(
                private readonly AuthorizationCodeInterface $existingCode
            ) {
            }

            #[\Override]
            public function find(string $identifier): ?AuthorizationCodeInterface
            {
                return $this->existingCode;
            }

            #[\Override]
            public function save(AuthorizationCodeInterface $authCode): void
            {
                $this->savedCode = $authCode;
            }

            #[\Override]
            public function clearExpired(): int
            {
                return 0;
            }

            public function savedCode(): ?AuthorizationCodeInterface
            {
                return $this->savedCode;
            }
        };
    }

    private function assertNewCodeCreated(
        AuthorizationCodeManagerInterface $manager,
        AuthorizationCode $existingCode,
        string $userId
    ): void {
        $savedCode = $manager->savedCode();
        $this->assertNotNull($savedCode);
        $this->assertNotSame($existingCode, $savedCode);
        $this->assertSame(
            SchemathesisFixtures::AUTHORIZATION_CODE,
            $savedCode->getIdentifier()
        );
        $this->assertSame($userId, $savedCode->getUserIdentifier());
    }
}
