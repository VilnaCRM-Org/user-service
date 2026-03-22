<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Fixture\Seeder;

use App\Shared\Infrastructure\Fixture\Seeder\SchemathesisOAuthSeeder;
use App\Tests\Unit\UnitTestCase;
use App\User\Domain\Entity\UserInterface;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

final class SchemathesisOAuthSeederTest extends UnitTestCase
{
    public function testSeedAuthorizationCodeRemovesExistingAuthorizationCode(): void
    {
        $client = $this->createTestClient();
        $user = $this->createTestUser();
        $userId = $user->getId();
        $authorizationCodeId = $this->faker->lexify('auth_code_????????');
        $existingCode = $this->createExistingAuthorizationCode(
            $client,
            $userId,
            $authorizationCodeId
        );
        $authorizationCodeManager = $this->createAuthCodeManager($existingCode);
        $seeder = $this->createSeeder($existingCode, $authorizationCodeManager);

        $seeder->seedAuthorizationCode($client, $user, $authorizationCodeId);

        $this->assertNewCodeCreated(
            $authorizationCodeManager,
            $existingCode,
            $userId,
            $authorizationCodeId
        );
    }

    private function createTestClient(): Client
    {
        return new Client(
            $this->faker->company(),
            $this->faker->lexify('client_????????'),
            $this->faker->sha1()
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
        string $userId,
        string $authorizationCodeId
    ): AuthorizationCode {
        return new AuthorizationCode(
            $authorizationCodeId,
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
    ): TestAuthorizationCodeManager {
        return new TestAuthorizationCodeManager($existingCode);
    }

    private function createSeeder(
        AuthorizationCode $existingCode,
        TestAuthorizationCodeManager $authorizationCodeManager
    ): SchemathesisOAuthSeeder {
        $documentManager = $this->createDocumentManagerMock($existingCode);
        $clientManager = $this->createMock(ClientManagerInterface::class);

        return new SchemathesisOAuthSeeder(
            $clientManager,
            $documentManager,
            $authorizationCodeManager
        );
    }

    private function assertNewCodeCreated(
        TestAuthorizationCodeManager $manager,
        AuthorizationCode $existingCode,
        string $userId,
        string $authorizationCodeId
    ): void {
        $savedCode = $manager->savedCode();
        $this->assertNotNull($savedCode);
        $this->assertNotSame($existingCode, $savedCode);
        $this->assertSame($authorizationCodeId, $savedCode->getIdentifier());
        $this->assertSame($userId, $savedCode->getUserIdentifier());
    }
}
