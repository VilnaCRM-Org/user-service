<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

final class JwtKeyPermissionsTest extends AuthIntegrationTestCase
{
    private const PRIVATE_KEY_PATH = __DIR__ . '/../../../config/jwt/private.pem';
    private const PUBLIC_KEY_PATH = __DIR__ . '/../../../config/jwt/public.pem';

    /**
     * AC: NFR-61 - Private key must have 600 permissions (owner read/write only)
     */
    public function testPrivateKeyHas600Permissions(): void
    {
        $this->assertTrue(
            file_exists(self::PRIVATE_KEY_PATH),
            'Private key file must exist at ' . self::PRIVATE_KEY_PATH
        );

        $perms = fileperms(self::PRIVATE_KEY_PATH);
        $octal = decoct($perms & 0777);

        $format = implode('', [
            'Private key must have 600 permissions ',
            '(owner read/write only), got %s. ',
            'Run: chmod 600 %s',
        ]);
        $this->assertSame(
            '600',
            $octal,
            sprintf($format, $octal, self::PRIVATE_KEY_PATH)
        );
    }

    /**
     * AC: NFR-61 - Public key must have 644 permissions (owner read/write, group/others read)
     */
    public function testPublicKeyHas644Permissions(): void
    {
        $this->assertTrue(
            file_exists(self::PUBLIC_KEY_PATH),
            'Public key file must exist at ' . self::PUBLIC_KEY_PATH
        );

        $perms = fileperms(self::PUBLIC_KEY_PATH);
        $octal = decoct($perms & 0777);

        $format = implode('', [
            'Public key must have 644 permissions ',
            '(owner read/write, others read), ',
            'got %s. Run: chmod 644 %s',
        ]);
        $this->assertSame(
            '644',
            $octal,
            sprintf($format, $octal, self::PUBLIC_KEY_PATH)
        );
    }

    /**
     * AC: NFR-61 - Private key must not be world-readable
     */
    public function testPrivateKeyIsNotWorldReadable(): void
    {
        $this->assertTrue(
            file_exists(self::PRIVATE_KEY_PATH),
            'Private key file must exist'
        );

        $perms = fileperms(self::PRIVATE_KEY_PATH);
        $worldReadable = ($perms & 0004) !== 0;

        $this->assertFalse(
            $worldReadable,
            'Private key MUST NOT be world-readable (RC-03 critical vulnerability)'
        );
    }

    /**
     * AC: NFR-61 - Private key must not be group-readable
     */
    public function testPrivateKeyIsNotGroupReadable(): void
    {
        $this->assertTrue(
            file_exists(self::PRIVATE_KEY_PATH),
            'Private key file must exist'
        );

        $perms = fileperms(self::PRIVATE_KEY_PATH);
        $groupReadable = ($perms & 0040) !== 0;

        $this->assertFalse(
            $groupReadable,
            'Private key MUST NOT be group-readable (security best practice)'
        );
    }
}
