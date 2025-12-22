<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Command;

use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\User\Domain\Entity\UserInterface;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use League\Bundle\OAuth2ServerBundle\Manager;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

final readonly class SchemathesisOAuthSeeder
{
    private Manager\AuthorizationCodeManagerInterface $authorizationCodeManager;

    public function __construct(
        private ClientManagerInterface $clientManager,
        private Connection $connection,
        Manager\AuthorizationCodeManagerInterface $authorizationCodeManager
    ) {
        $this->authorizationCodeManager = $authorizationCodeManager;
    }

    public function seedClient(): Client
    {
        $this->removeExistingClient();

        $client = $this->buildClient();
        $this->clientManager->save($client);

        return $client;
    }

    public function seedAuthorizationCode(
        Client $client,
        UserInterface $user
    ): void {
        $this->connection->delete(
            'oauth2_authorization_code',
            ['identifier' => SchemathesisFixtures::AUTHORIZATION_CODE]
        );

        $authorizationCode = new AuthorizationCode(
            SchemathesisFixtures::AUTHORIZATION_CODE,
            new DateTimeImmutable('+15 minutes'),
            $client,
            $user->getId(),
            [new Scope('email')]
        );

        $this->authorizationCodeManager->save($authorizationCode);
    }

    private function removeExistingClient(): void
    {
        $existingClient = $this->clientManager->find(
            SchemathesisFixtures::OAUTH_CLIENT_ID
        );

        if ($existingClient instanceof Client) {
            $this->clientManager->remove($existingClient);
        }
    }

    private function buildClient(): Client
    {
        return (new Client(
            SchemathesisFixtures::OAUTH_CLIENT_NAME,
            SchemathesisFixtures::OAUTH_CLIENT_ID,
            SchemathesisFixtures::OAUTH_CLIENT_SECRET
        ))
            ->setRedirectUris(
                new RedirectUri(
                    SchemathesisFixtures::OAUTH_REDIRECT_URI
                )
            )
            ->setGrants(
                new Grant('authorization_code'),
                new Grant('refresh_token'),
                new Grant('password')
            )
            ->setScopes(new Scope('email'), new Scope('profile'))
            ->setActive(true)
            ->setAllowPlainTextPkce(false);
    }
}
