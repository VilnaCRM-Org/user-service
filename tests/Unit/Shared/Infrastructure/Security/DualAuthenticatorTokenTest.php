<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Infrastructure\Security;

use Symfony\Component\HttpFoundation\Request;

final class DualAuthenticatorTokenTest extends DualAuthenticatorTestCase
{
    public function testCreateTokenFallsBackToRoleUserWhenRolesMissing(): void
    {
        $passport = $this->createPassportWithoutRoles(
            $this->faker->email()
        );
        $token = $this->createAuthenticator()->createToken(
            $passport,
            'api'
        );

        $this->assertSame(['ROLE_USER'], $token->getRoleNames());
    }

    public function testCreateTokenFallsBackToRoleUserWhenRolesAreInvalid(): void
    {
        $passport = $this->createPassportWithoutRoles(
            $this->faker->email()
        );
        $passport->setAttribute('roles', [1, null, '']);

        $token = $this->createAuthenticator()->createToken(
            $passport,
            'api'
        );

        $this->assertSame(['ROLE_USER'], $token->getRoleNames());
    }

    public function testSupportsReturnsFalseWhenBearerHasNoTokenAfterPrefix(): void
    {
        $request = Request::create('/api/users');
        $request->headers->set('Authorization', 'Bearer ');

        $this->assertFalse(
            $this->createAuthenticator()->supports($request)
        );
    }

    public function testSupportsReturnsFalseWhenCookieIsEmptyString(): void
    {
        $request = Request::create('/api/users');
        $request->cookies->set('__Host-auth_token', '');

        $this->assertFalse(
            $this->createAuthenticator()->supports($request)
        );
    }

    public function testCreateTokenDoesNotSetSidWhenAttributeIsNull(): void
    {
        $passport = $this->createPassportWithoutRoles(
            $this->faker->email()
        );

        $token = $this->createAuthenticator()->createToken(
            $passport,
            'api'
        );

        $this->assertFalse($token->hasAttribute('sid'));
    }

    public function testCreateTokenDoesNotSetSidWhenAttributeIsEmptyString(): void
    {
        $passport = $this->createPassportWithoutRoles(
            $this->faker->email()
        );
        $passport->setAttribute('sid', '');

        $token = $this->createAuthenticator()->createToken(
            $passport,
            'api'
        );

        $this->assertFalse($token->hasAttribute('sid'));
    }

    public function testCreateTokenDoesNotSetSidWhenAttributeIsNotString(): void
    {
        $passport = $this->createPassportWithoutRoles(
            $this->faker->email()
        );
        $passport->setAttribute('sid', 42);

        $token = $this->createAuthenticator()->createToken(
            $passport,
            'api'
        );

        $this->assertFalse($token->hasAttribute('sid'));
    }

    public function testCreateTokenFiltersOutEmptyStringRoles(): void
    {
        $passport = $this->createPassportWithoutRoles(
            $this->faker->email()
        );
        $passport->setAttribute('roles', ['', 'ROLE_ADMIN', '']);

        $token = $this->createAuthenticator()->createToken(
            $passport,
            'api'
        );

        $this->assertSame(
            ['ROLE_ADMIN'],
            $token->getRoleNames()
        );
    }
}
