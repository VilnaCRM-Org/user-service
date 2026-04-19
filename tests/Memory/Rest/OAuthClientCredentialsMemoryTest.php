<?php

declare(strict_types=1);

namespace App\Tests\Memory\Rest;

use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use Symfony\Component\HttpFoundation\Response;

final class OAuthClientCredentialsMemoryTest extends RestMemoryWebTestCase
{
    public function testOauthClientCredentialsScenarioReusesSameKernelAcrossRepeatedRequests(): void
    {
        $this->runRepeatedRestScenario('oauth', function (): void {
            $clientId = strtolower($this->faker->bothify('memory-client-????-####'));
            $clientSecret = $this->faker->sha1();
            $this->registerClientCredentialsClient($clientId, $clientSecret);

            ['response' => $response, 'body' => $body] = $this->requestJson(
                'POST',
                '/api/oauth/token',
                $this->createOauthClientPayload(),
                $this->createBasicAuthorizationHeader($clientId, $clientSecret),
            );

            self::assertSame(Response::HTTP_OK, $response->getStatusCode());
            self::assertIsString($body['access_token'] ?? null);
            self::assertNotSame('', $body['access_token'] ?? null);
            self::assertSame('Bearer', $body['token_type'] ?? null);
        }, 5);
    }

    /**
     * @return array{HTTP_AUTHORIZATION: string}
     */
    private function createBasicAuthorizationHeader(string $clientId, string $clientSecret): array
    {
        return [
            'HTTP_AUTHORIZATION' => 'Basic ' . base64_encode(
                sprintf('%s:%s', $clientId, $clientSecret),
            ),
        ];
    }

    private function registerClientCredentialsClient(string $clientId, string $clientSecret): void
    {
        $oauthClient = new Client($this->faker->company(), $clientId, $clientSecret);
        $oauthClient->setGrants(new Grant('client_credentials'));
        $oauthClient->setActive(true);
        $this->container->get(ClientManagerInterface::class)->save($oauthClient);
    }
}
