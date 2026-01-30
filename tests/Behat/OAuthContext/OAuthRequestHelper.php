<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext;

use App\Tests\Behat\OAuthContext\Input\ObtainAccessTokenInput;
use App\Tests\Behat\OAuthContext\Input\ObtainAuthorizeCodeInput;
use League\Bundle\OAuth2ServerBundle\Event\AuthorizationRequestResolveEvent;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

final class OAuthRequestHelper
{
    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    public function approveAuthorization(): void
    {
        $this->kernel->getContainer()->get('event_dispatcher')
            ->addListener(
                OAuth2Events::AUTHORIZATION_REQUEST_RESOLVE,
                static function (AuthorizationRequestResolveEvent $event): void {
                    $event->resolveAuthorization(
                        AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED
                    );
                }
            );
    }

    public function sendAuthorizationRequest(ObtainAuthorizeCodeInput $input): Response
    {
        return $this->kernel->handle(Request::create(
            '/api/oauth/authorize?' . $input->toUriParams(),
            'GET',
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ]
        ));
    }

    public function sendTokenRequest(
        ObtainAccessTokenInput $input,
        ?string $clientId = null,
        ?string $clientSecret = null,
    ): Response {
        return $this->kernel->handle(Request::create(
            '/api/oauth/token',
            'POST',
            [],
            [],
            [],
            $this->buildRequestHeaders($clientId, $clientSecret),
            json_encode($input->toArray(), JSON_THROW_ON_ERROR)
        ));
    }

    /**
     * @param array<string, string|int|bool|null> $payload
     */
    public function sendTokenRequestWithPayload(
        array $payload,
        ?string $clientId = null,
        ?string $clientSecret = null,
    ): Response {
        return $this->kernel->handle(Request::create(
            '/api/oauth/token',
            'POST',
            [],
            [],
            [],
            $this->buildRequestHeaders($clientId, $clientSecret),
            json_encode($payload)
        ));
    }

    /**
     * @return array<string, string>
     */
    public function getRedirectParams(Response $response): array
    {
        $location = (string) $response->headers->get('location');
        $fragment = parse_url($location, PHP_URL_FRAGMENT);
        $query = parse_url($location, PHP_URL_QUERY);
        $params = $fragment ?? $query ?? '';
        parse_str($params, $parsed);

        return $parsed;
    }

    /**
     * @return array<string, string>
     */
    private function buildRequestHeaders(?string $clientId, ?string $clientSecret): array
    {
        $headers = [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ];

        if ($clientId !== null && $clientSecret !== null) {
            $headers['HTTP_AUTHORIZATION'] = 'Basic ' . base64_encode(
                $clientId . ':' . $clientSecret
            );
        }

        return $headers;
    }
}
