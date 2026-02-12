<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\CommandHandler;

use App\Shared\Infrastructure\Factory\UuidFactory as SharedUuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SetupTwoFactorCommand;
use App\User\Application\CommandHandler\SetupTwoFactorCommandHandler;
use App\User\Domain\Contract\TOTPSecretGeneratorInterface;
use App\User\Domain\Contract\TwoFactorSecretEncryptorInterface;
use App\User\Domain\Entity\User;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
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
        $this->twoFactorSecretEncryptor = $this->createMock(TwoFactorSecretEncryptorInterface::class);
        $this->totpSecretGenerator = $this->createMock(TOTPSecretGeneratorInterface::class);
        $this->userFactory = new UserFactory();
        $this->uuidTransformer = new UuidTransformer(new SharedUuidFactory());
    }

    public function testInvokeGeneratesAndStoresEncryptedTwoFactorSecret(): void
    {
        $user = $this->createUser($this->faker->email());
        $user->setTwoFactorEnabled(true);
        $secret = 'JBSWY3DPEHPK3PXP';
        $otpauthUri = sprintf(
            'otpauth://totp/VilnaCRM:%s?secret=%s&issuer=VilnaCRM',
            rawurlencode($user->getEmail()),
            $secret
        );

        $this->userRepository
            ->expects($this->once())
            ->method('findByEmail')
            ->with($user->getEmail())
            ->willReturn($user);

        $this->totpSecretGenerator
            ->expects($this->once())
            ->method('generate')
            ->with($user->getEmail())
            ->willReturn([
                'secret' => $secret,
                'otpauth_uri' => $otpauthUri,
            ]);

        $this->twoFactorSecretEncryptor
            ->expects($this->once())
            ->method('encrypt')
            ->with($secret)
            ->willReturn('encrypted-secret');

        $this->userRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                static fn (User $savedUser): bool => $savedUser->getEmail() === $user->getEmail()
                    && $savedUser->getTwoFactorSecret() === 'encrypted-secret'
                    && $savedUser->isTwoFactorEnabled() === false
            ));

        $handler = $this->createHandler();
        $command = new SetupTwoFactorCommand($user->getEmail());

        $handler->__invoke($command);

        $response = $command->getResponse();

        $this->assertSame($secret, $response->getSecret());
        $this->assertSame($otpauthUri, $response->getOtpauthUri());
        $this->assertStringContainsString(
            rawurlencode($user->getEmail()),
            $response->getOtpauthUri()
        );
        $this->assertStringContainsString('issuer=VilnaCRM', $response->getOtpauthUri());
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

    private function createHandler(): SetupTwoFactorCommandHandler
    {
        return new SetupTwoFactorCommandHandler(
            $this->userRepository,
            $this->twoFactorSecretEncryptor,
            $this->totpSecretGenerator,
        );
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
