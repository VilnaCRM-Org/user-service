<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 */
final class DualAuthenticator extends AbstractAuthenticator implements
    AuthenticationEntryPointInterface
{
    private const AUTH_COOKIE_NAME = '__Host-auth_token';

    public function __construct(
        private readonly AccessTokenPassportFactory $accessTokenPassportFactory,
        private readonly PublicAccessMatcher $publicAccessMatcher,
    ) {
    }

    #[\Override]
    public function supports(Request $request): ?bool
    {
        if ($this->publicAccessMatcher->isPublic($request)) {
            return false;
        }

        return $this->extractToken($request) !== null;
    }

    #[\Override]
    public function authenticate(Request $request): Passport
    {
        return $this->accessTokenPassportFactory->create(
            $this->requireToken($request)
        );
    }

    #[\Override]
    public function createToken(
        Passport $passport,
        string $firewallName
    ): TokenInterface {
        $token = new PostAuthenticationToken(
            $passport->getUser(),
            $firewallName,
            $this->extractTokenRoles($passport)
        );

        $sid = $passport->getAttribute('sid');
        if (is_string($sid) && $sid !== '') {
            $token->setAttribute('sid', $sid);
        }

        return $token;
    }

    #[\Override]
    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return null;
    }

    #[\Override]
    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return $this->buildFailureResponse('Authentication required.');
    }

    #[\Override]
    public function start(
        Request $request,
        ?AuthenticationException $authException = null
    ): Response {
        return $this->buildFailureResponse('Authentication required.');
    }

    private function extractToken(Request $request): ?string
    {
        $authorization = $request->headers->get('Authorization', '');
        if (
            str_starts_with($authorization, 'Bearer ')
            && strlen($authorization) > 7
        ) {
            return substr($authorization, 7);
        }

        $cookieToken = $request->cookies->get(self::AUTH_COOKIE_NAME);
        if (is_string($cookieToken) && $cookieToken !== '') {
            return $cookieToken;
        }

        return null;
    }

    private function requireToken(Request $request): string
    {
        $token = $this->extractToken($request);
        if ($token === null) {
            throw new CustomUserMessageAuthenticationException(
                'Authentication required.'
            );
        }

        return $token;
    }

    /**
     * @return array<string>
     *
     * @psalm-return list{string,...}
     */
    private function extractTokenRoles(Passport $passport): array
    {
        $rawRoles = $passport->getAttribute('roles');
        if (!is_array($rawRoles)) {
            return ['ROLE_USER'];
        }

        $roles = [];
        foreach ($rawRoles as $role) {
            if (is_string($role) && $role !== '') {
                $roles[] = $role;
            }
        }

        return $roles === [] ? ['ROLE_USER'] : array_values($roles);
    }

    private function buildFailureResponse(string $detail): JsonResponse
    {
        return new JsonResponse(
            [
                'type' => 'about:blank',
                'title' => 'Unauthorized',
                'status' => Response::HTTP_UNAUTHORIZED,
                'detail' => $detail,
            ],
            Response::HTTP_UNAUTHORIZED,
            [
                'Content-Type' => 'application/problem+json',
                'WWW-Authenticate' => 'Bearer',
            ]
        );
    }
}
