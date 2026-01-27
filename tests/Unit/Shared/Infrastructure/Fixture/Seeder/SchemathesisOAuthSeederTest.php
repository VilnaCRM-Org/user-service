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
        $client = new Client(
            $this->faker->company(),
            $this->faker->lexify('client_????????'),
            $this->faker->optional()->sha1()
        );
        $userId = $this->faker->uuid();
        $user = $this->createMock(UserInterface::class);
        $user->method('getId')->willReturn($userId);

        $existingCode = new AuthorizationCode(
            SchemathesisFixtures::AUTHORIZATION_CODE,
            new DateTimeImmutable('+10 minutes'),
            $client,
            $userId,
            [new Scope($this->faker->lexify('scope_????'))]
        );

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->once())->method('remove')->with($existingCode);
        $documentManager->expects($this->once())->method('flush');

        $authorizationCodeManager = new class($existingCode) implements AuthorizationCodeManagerInterface {
            private ?AuthorizationCodeInterface $savedCode = null;

            public function __construct(private readonly AuthorizationCodeInterface $existingCode)
            {
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

        $clientManager = $this->createMock(ClientManagerInterface::class);

        $seeder = new SchemathesisOAuthSeeder($clientManager, $documentManager, $authorizationCodeManager);
        $seeder->seedAuthorizationCode($client, $user);

        $savedCode = $authorizationCodeManager->savedCode();
        $this->assertNotNull($savedCode);
        $this->assertNotSame($existingCode, $savedCode);
        $this->assertSame(SchemathesisFixtures::AUTHORIZATION_CODE, $savedCode->getIdentifier());
        $this->assertSame($userId, $savedCode->getUserIdentifier());
    }
}
