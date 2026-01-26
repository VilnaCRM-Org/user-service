<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Fixture\Seeder;

use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;
use App\User\Domain\Entity\UserInterface;
use DateTimeImmutable;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

final readonly class SchemathesisOAuthSeeder
{
    public function __construct(
        private ClientManagerInterface $clientManager,
        private DocumentManager $documentManager,
        private Manager\AuthorizationCodeManagerInterface $authorizationCodeManager
    ) {
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
        // Remove existing authorization code if it exists
        $existingAuthCode = $this->authorizationCodeManager->find(
            SchemathesisFixtures::AUTHORIZATION_CODE
        );

        if ($existingAuthCode !== null) {
            $this->documentManager->remove($existingAuthCode);
            $this->documentManager->flush();
        }

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
