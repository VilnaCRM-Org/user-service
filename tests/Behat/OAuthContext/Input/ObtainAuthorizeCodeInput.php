<?php

declare(strict_types=1);

namespace App\Tests\Behat\OAuthContext\Input;

final class ObtainAuthorizeCodeInput
{
    private string $responseType = 'code';
    private ?string $scope = null;
    private ?string $codeChallenge = null;
    private ?string $codeChallengeMethod = null;

    public function __construct(
        private string $client_id,
        private string $redirect_uri
    ) {
    }

    public function setResponseType(string $responseType): void
    {
        $this->responseType = $responseType;
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
    }

    public function setCodeChallenge(string $codeChallenge, ?string $method = null): void
    {
        $this->codeChallenge = $codeChallenge;
        $this->codeChallengeMethod = $method;
    }

    public function toUriParams(): string
    {
        $queryParams = [
            'response_type' => $this->responseType,
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
        ];

        if ($this->scope !== null) {
            $queryParams['scope'] = $this->scope;
        }

        if ($this->codeChallenge !== null) {
            $queryParams['code_challenge'] = $this->codeChallenge;
        }

        if ($this->codeChallengeMethod !== null) {
            $queryParams['code_challenge_method'] = $this->codeChallengeMethod;
        }

        return http_build_query($queryParams);
    }
}
