<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Request;

use ApiPlatform\OpenApi\Model\RequestBody;
use App\Shared\Infrastructure\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\RequestBuilder;
use App\Shared\Application\OpenApi\Enum\Requirement;
use App\Shared\Application\OpenApi\Factory\Request\OAuthTokenRequestFactory;
use App\Shared\Application\OpenApi\ValueObject\Parameter;
use App\Tests\Unit\UnitTestCase;

final class OAuthTokenRequestFactoryTest extends UnitTestCase
{
    public function testGetRequest(): void
    {
        $requestBuilder = $this->createMock(RequestBuilder::class);

        $factory = new OAuthTokenRequestFactory($requestBuilder);

        $requestBuilder->expects($this->once())
            ->method('build')
            ->with(
                $this->getParams()
            )
            ->willReturn(new RequestBody());

        $request = $factory->getRequest();

        $this->assertInstanceOf(RequestBody::class, $request);
    }

    /**
     * @return array<Parameter>
     */
    private function getParams(): array
    {
        return [
            $this->getGrantTypeParam(),
            $this->getClientIdParam(),
            $this->getClientSecretParam(),
            $this->getRedirectUriParam(),
            $this->getCodeParam(),
            $this->getRefreshTokenParam(),
            $this->getUsernameParam(),
            $this->getPasswordParam(),
            $this->getScopeParam(),
        ];
    }

    private function getGrantTypeParam(): Parameter
    {
        return new Parameter(
            'grant_type',
            'string',
            'password',
            null,
            null,
            Requirement::REQUIRED,
            '^(authorization_code|refresh_token|password)$',
            enum: ['password']
        );
    }

    private function getClientIdParam(): Parameter
    {
        return new Parameter(
            'client_id',
            'string',
            SchemathesisFixtures::OAUTH_CLIENT_ID,
            null,
            null,
            Requirement::OPTIONAL,
            '^.+$',
            enum: [SchemathesisFixtures::OAUTH_CLIENT_ID]
        );
    }

    private function getClientSecretParam(): Parameter
    {
        return new Parameter(
            'client_secret',
            'string',
            SchemathesisFixtures::OAUTH_CLIENT_SECRET,
            null,
            null,
            Requirement::OPTIONAL,
            '^.+$',
            enum: [SchemathesisFixtures::OAUTH_CLIENT_SECRET]
        );
    }

    private function getRedirectUriParam(): Parameter
    {
        return new Parameter(
            'redirect_uri',
            'string',
            SchemathesisFixtures::OAUTH_REDIRECT_URI,
            null,
            'uri',
            Requirement::OPTIONAL,
            '^https?://.+$',
            enum: [SchemathesisFixtures::OAUTH_REDIRECT_URI]
        );
    }

    private function getCodeParam(): Parameter
    {
        return new Parameter(
            'code',
            'string',
            SchemathesisFixtures::AUTHORIZATION_CODE,
            null,
            null,
            Requirement::OPTIONAL,
            '^.+$',
            enum: [SchemathesisFixtures::AUTHORIZATION_CODE]
        );
    }

    private function getRefreshTokenParam(): Parameter
    {
        return new Parameter(
            'refresh_token',
            'string',
            'f7a5a9dca1fe7f8c62113a47',
            null,
            null,
            Requirement::OPTIONAL,
            '^.+$'
        );
    }

    private function getUsernameParam(): Parameter
    {
        return new Parameter(
            'username',
            'string',
            SchemathesisFixtures::PASSWORD_RESET_CONFIRM_EMAIL,
            null,
            null,
            Requirement::REQUIRED,
            '^.+$',
            enum: [SchemathesisFixtures::PASSWORD_RESET_CONFIRM_EMAIL]
        );
    }

    private function getPasswordParam(): Parameter
    {
        return new Parameter(
            'password',
            'string',
            SchemathesisFixtures::USER_PASSWORD,
            null,
            null,
            Requirement::REQUIRED,
            '^.+$',
            enum: [SchemathesisFixtures::USER_PASSWORD]
        );
    }

    private function getScopeParam(): Parameter
    {
        return new Parameter(
            'scope',
            'string',
            SchemathesisFixtures::OAUTH_SCOPE,
            null,
            null,
            Requirement::OPTIONAL,
            '^.+$',
            enum: [SchemathesisFixtures::OAUTH_SCOPE]
        );
    }
}
