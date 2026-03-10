<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SetupTwoFactorCommand;
use App\User\Application\CommandHandler\SetupTwoFactorCommandHandler;
use App\User\Application\Encryptor\TwoFactorSecretEncryptorInterface;
use App\User\Application\Generator\TOTPSecretGeneratorInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class SetupTwoFactorCommandHandlerTest extends UnitTestCase
{
    private UserRepositoryInterface&MockObject $userRepository;
    private TwoFactorSecretEncryptorInterface&MockObject $twoFactorSecretEncryptor;
    private TOTPSecretGeneratorInterface&MockObject $totpSecretGenerator;
    private UserFactory $userFactory;
    private UuidTransformer $uuidTransformer;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->twoFactorSecretEncryptor =
            $this->createMock(TwoFactorSecretEncryptorInterface::class);
        $this->totpSecretGenerator = $this->createMock(TOTPSecretGeneratorInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testInvokeGeneratesAndStoresEncryptedTwoFactorSecret(): void
    {
        $user = $this->createUser($this->faker->email());
        $secret = 'JBSWY3DPEHPK3PXP';
        $otpauthUri = $this->buildOtpauthUri($user->getEmail(), $secret);
        $this->expectUserLookup($user);
        $this->expectTotpGeneration($user->getEmail(), $secret, $otpauthUri);
        $this->expectSecretEncryption($secret);
        $this->expectUserSaveWithEncryptedSecret($user);
        $command = new SetupTwoFactorCommand($user->getEmail());
        $this->createHandler()->__invoke($command);
        $this->assertSetupTwoFactorResponse($command, $secret, $otpauthUri, $user->getEmail());
    }

    public function testInvokeThrowsUnauthorizedWhenAuthenticatedUserIsMissing(): void
    {
        $email = $this->faker->email();
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($email)
            ->willReturn(null);
        $this->totpSecretGenerator
            ->expects($this->never())
            ->method('generate');
        $this->twoFactorSecretEncryptor
            ->expects($this->never())
            ->method('encrypt');
        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');
        $handler = $this->createHandler();
        $handler->__invoke(new SetupTwoFactorCommand($email));
    }

    public function testInvokeThrowsConflictWhenTwoFactorAlreadyEnabled(): void
    {
        $user = $this->createUser($this->faker->email());
        $user->setTwoFactorEnabled(true);
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($user->getEmail())
            ->willReturn($user);
        $this->totpSecretGenerator
            ->expects($this->never())
            ->method('generate');
        $this->twoFactorSecretEncryptor
            ->expects($this->never())
            ->method('encrypt');
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Two-factor authentication is already enabled.');
        $handler = $this->createHandler();
        $handler->__invoke(new SetupTwoFactorCommand($user->getEmail()));
    }

    private function createHandler(): SetupTwoFactorCommandHandler
    {
        return new SetupTwoFactorCommandHandler(
            $this->userRepository,
            $this->twoFactorSecretEncryptor,
            $this->totpSecretGenerator,
        );
    }

    private function buildOtpauthUri(string $email, string $secret): string
    {
        return sprintf(
            'otpauth://totp/VilnaCRM:%s?secret=%s&issuer=VilnaCRM',
            rawurlencode($email),
            $secret
        );
    }

    private function expectUserLookup(User $user): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($user->getEmail())
            ->willReturn($user);
    }

    private function expectTotpGeneration(string $email, string $secret, string $otpauthUri): void
    {
        $this->totpSecretGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($email)
            ->willReturn([
                'secret' => $secret,
                'otpauth_uri' => $otpauthUri,
            ]);
    }

    private function expectSecretEncryption(string $secret): void
    {
        $this->twoFactorSecretEncryptor
            ->expects($this->once())
            ->method('encrypt')
            ->with($secret)
            ->willReturn('encrypted-secret');
    }

    private function expectUserSaveWithEncryptedSecret(User $user): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (User $savedUser): bool => $savedUser->getEmail() === $user->getEmail()
                    && $savedUser->getTwoFactorSecret() === 'encrypted-secret'
                    && $savedUser->isTwoFactorEnabled() === false
            ));
    }

    private function assertSetupTwoFactorResponse(
        SetupTwoFactorCommand $command,
        string $secret,
        string $otpauthUri,
        string $email
    ): void {
        $response = $command->getResponse();
        $this->assertSame($secret, $response->getSecret());
        $this->assertSame($otpauthUri, $response->getOtpauthUri());
        $this->assertStringContainsString(rawurlencode($email), $response->getOtpauthUri());
        $this->assertStringContainsString('issuer=VilnaCRM', $response->getOtpauthUri());
    }

    private function createUser(string $email): User
    {
        return $this->userFactory->create(
            $email,
            $this->faker->firstName(),
            $this->faker->password(),
            $this->uuidTransformer->transformFromString($this->faker->uuid())
        );
    }
}
