<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext;

use App\OAuth\Application\Factory\OAuthFlowCookieFactory;
use App\Tests\Behat\UserContext\UserOperationsState;
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

final class SocialOAuthContext implements Context
{
    public function __construct(
        private readonly UserOperationsState $state,
        private readonly KernelInterface $kernel,
    ) {
    }

    /**
     * @Then I store the OAuth state from the redirect location as :key
     */
    public function iStoreTheOAuthStateFromTheRedirectLocationAs(
        string $key,
    ): void {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        $location = $response->headers->get('Location');
        Assert::assertIsString($location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        Assert::assertIsString($query['state'] ?? null);

        $this->state->{$key} = $query['state'];
    }

    /**
     * @Then I store the :cookieName cookie from the response as :key
     */
    public function iStoreTheCookieFromTheResponseAs(
        string $cookieName,
        string $key,
    ): void {
        $response = $this->state->response;
        Assert::assertNotNull($response);

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $cookieName) {
                $this->state->{$key} = $cookie->getValue();

                return;
            }
        }

        throw new \RuntimeException(
            sprintf('Cookie "%s" was not found in the response.', $cookieName),
        );
    }

    /**
     * @When I complete social OAuth for provider :provider with code :code using stored state :stateKey and cookie :cookieKey
     */
    public function iCompleteSocialOAuthUsingStoredStateAndCookie(
        string $provider,
        string $code,
        string $stateKey,
        string $cookieKey,
    ): void {
        $this->state->response = $this->kernel->handle(
            $this->createCallbackRequest(
                $provider,
                $code,
                $this->requireStoredValue($stateKey),
                $this->requireStoredValue($cookieKey),
            ),
        );
    }

    private function requireStoredValue(string $key): string
    {
        $value = $this->state->{$key};

        Assert::assertIsString($value);
        Assert::assertNotSame('', $value);

        return $value;
    }

    private function createCallbackRequest(
        string $provider,
        string $code,
        string $state,
        string $cookie,
    ): Request {
        return Request::create(
            $this->buildCallbackPath($provider, $code, $state),
            'GET',
            [],
            [OAuthFlowCookieFactory::COOKIE_NAME => $cookie],
            [],
            $this->createServerParameters(),
        );
    }

    private function buildCallbackPath(
        string $provider,
        string $code,
        string $state,
    ): string {
        return sprintf(
            '/api/auth/social/%s/callback?%s',
            $provider,
            http_build_query([
                'code' => $code,
                'state' => $state,
            ]),
        );
    }

    /**
     * @return array<string, string>
     */
    private function createServerParameters(): array
    {
        return [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_USER_AGENT' => 'BehatSocialOAuthContext',
            'REMOTE_ADDR' => '127.0.0.1',
        ];
    }
}
