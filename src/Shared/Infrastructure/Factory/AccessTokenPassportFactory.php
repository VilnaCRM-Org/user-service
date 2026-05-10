<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Factory;

use App\Shared\Infrastructure\Resolver\AccessTokenUserResolver;
use App\Shared\Infrastructure\Validator\AccessTokenValidator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final readonly class AccessTokenPassportFactory
{
    public function __construct(
        private AccessTokenValidator $accessTokenValidator,
        private AccessTokenUserResolver $accessTokenUserResolver,
    ) {
    }

    public function create(string $token): Passport
    {
        $claims = $this->accessTokenValidator->validate($token);
        $subject = $claims['subject'];
        $roles = $claims['roles'];
        $sid = $claims['sid'];
        $user = $this->accessTokenUserResolver->resolve($subject, $roles, $sid);

        $passport = new SelfValidatingPassport(
            new UserBadge(
                $subject,
                static fn (string $_identifier) => $user
            )
        );
        $passport->setAttribute('roles', $roles);
        $passport->setAttribute('sid', $sid);

        return $passport;
    }
}
