<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth;

use App\Tests\Integration\IntegrationTestCase;

/**
 * @covers JWT key file permissions
 */
final class JwtKeyPermissionsTest extends IntegrationTestCase
{
    private const PRIVATE_KEY_PATH = __DIR__ . '/../../../config/jwt/private.pem';
    private const PUBLIC_KEY_PATH = __DIR__ . '/../../../config/jwt/public.pem';

    /**
     * @test
     * AC: NFR-61 - Private key must have 600 permissions (owner read/write only)
     */
    public function private_key_has_600_permissions(): void
    {
        $this->assertTrue(
            file_exists(self::PRIVATE_KEY_PATH),
            'Private key file must exist at ' . self::PRIVATE_KEY_PATH
        );

        $perms = fileperms(self::PRIVATE_KEY_PATH);
        $octal = decoct($perms & 0777);

        $this->assertSame(
            '600',
            $octal,
            sprintf(
                'Private key must have 600 permissions (owner read/write only), got %s. ' .
                'Run: chmod 600 %s',
                $octal,
                self::PRIVATE_KEY_PATH
            )
        );
    }

    /**
     * @test
     * AC: NFR-61 - Public key must have 644 permissions (owner read/write, group/others read)
     */
    public function public_key_has_644_permissions(): void
    {
        $this->assertTrue(
            file_exists(self::PUBLIC_KEY_PATH),
            'Public key file must exist at ' . self::PUBLIC_KEY_PATH
        );

        $perms = fileperms(self::PUBLIC_KEY_PATH);
        $octal = decoct($perms & 0777);

        $this->assertSame(
            '644',
            $octal,
            sprintf(
                'Public key must have 644 permissions (owner read/write, others read), got %s. ' .
                'Run: chmod 644 %s',
                $octal,
                self::PUBLIC_KEY_PATH
            )
        );
    }

    /**
     * @test
     * AC: NFR-61 - Private key must not be world-readable
     */
    public function private_key_is_not_world_readable(): void
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
     * @test
     * AC: NFR-61 - Private key must not be group-readable
     */
    public function private_key_is_not_group_readable(): void
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
