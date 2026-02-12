<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\CompleteTwoFactorCommand;
use App\User\Application\Command\CompleteTwoFactorCommandResponse;
use App\User\Application\DTO\CompleteTwoFactorDto;
use App\User\Application\Processor\CompleteTwoFactorProcessor;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class CompleteTwoFactorProcessorTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private RequestStack $requestStack;
    private Operation $operation;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->requestStack = new RequestStack();
        $this->operation = $this->createMock(Operation::class);
    }

    public function testProcessReturnsTokensAndSetsCookie(): void
    {
        $pendingSessionId = '01ARZ3NDEKTSV4RRFFQ69G5FAZ';
        $totpCode = '123456';
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $request = $this->createRequest($ipAddress, $userAgent);
        $dto = new CompleteTwoFactorDto($pendingSessionId, $totpCode);

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            function (CompleteTwoFactorCommand $command) use ($pendingSessionId, $totpCode, $ipAddress, $userAgent): bool {
                $this->assertSame($pendingSessionId, $command->pendingSessionId);
                $this->assertSame($totpCode, $command->twoFactorCode);
                $this->assertSame($ipAddress, $command->ipAddress);
                $this->assertSame($userAgent, $command->userAgent);

                $command->setResponse(
                    new CompleteTwoFactorCommandResponse(
                        'issued-access-token',
                        'issued-refresh-token'
                    )
                );

                return true;
            }));

        $processor = new CompleteTwoFactorProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation, [], ['request' => $request]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                '2fa_enabled' => true,
                'access_token' => 'issued-access-token',
                'refresh_token' => 'issued-refresh-token',
            ]),
            (string) $response->getContent()
        );

        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        $cookie = $cookies[0];

        $this->assertSame('__Host-auth_token', $cookie->getName());
        $this->assertSame('issued-access-token', $cookie->getValue());
        $this->assertSame('/', $cookie->getPath());
        $this->assertNull($cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        $this->assertGreaterThanOrEqual(899, $cookie->getMaxAge());
        $this->assertLessThanOrEqual(900, $cookie->getMaxAge());
    }

    public function testProcessDoesNotSetCookieWhenAccessTokenIsEmpty(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new CompleteTwoFactorDto('01ARZ3NDEKTSV4RRFFQ69G5FB0', '123456');

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            static function (CompleteTwoFactorCommand $command): bool {
                $command->setResponse(
                    new CompleteTwoFactorCommandResponse(
                        '',
                        'issued-refresh-token'
                    )
                );

                return true;
            }));

        $processor = new CompleteTwoFactorProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation, [], ['request' => $request]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(0, $response->headers->getCookies());
    }

    public function testProcessUsesRequestStackWhenContextRequestIsMissing(): void
    {
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $request = $this->createRequest($ipAddress, $userAgent);
        $this->requestStack->push($request);

        $dto = new CompleteTwoFactorDto('01ARZ3NDEKTSV4RRFFQ69G5FB1', '123456');

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            function (CompleteTwoFactorCommand $command) use ($ipAddress, $userAgent): bool {
                $this->assertSame($ipAddress, $command->ipAddress);
                $this->assertSame($userAgent, $command->userAgent);

                $command->setResponse(
                    new CompleteTwoFactorCommandResponse(
                        'stack-access-token',
                        'stack-refresh-token'
                    )
                );

                return true;
            }));

        $processor = new CompleteTwoFactorProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessFallsBackToEmptyRequestMetadataWhenNoRequestAvailable(): void
    {
        $dto = new CompleteTwoFactorDto('01ARZ3NDEKTSV4RRFFQ69G5FB9', '123456');

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            function (CompleteTwoFactorCommand $command): bool {
                $this->assertSame('', $command->ipAddress);
                $this->assertSame('', $command->userAgent);

                $command->setResponse(
                    new CompleteTwoFactorCommandResponse(
                        'no-request-access-token',
                        'no-request-refresh-token'
                    )
                );

                return true;
            }));

        $processor = new CompleteTwoFactorProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation);

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProcessIncludesRecoveryCodeWarningFieldsInResponse(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new CompleteTwoFactorDto('01ARZ3NDEKTSV4RRFFQ69G5FB2', 'AB12-CD34');

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            static function (CompleteTwoFactorCommand $command): bool {
                $command->setResponse(
                    new CompleteTwoFactorCommandResponse(
                        'recovery-access-token',
                        'recovery-refresh-token',
                        1,
                        'Only 1 recovery code(s) remaining. Regenerate soon.'
                    )
                );

                return true;
            }));

        $processor = new CompleteTwoFactorProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation, [], ['request' => $request]);

        $body = json_decode((string) $response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $body['recovery_codes_remaining']);
        $this->assertSame(
            'Only 1 recovery code(s) remaining. Regenerate soon.',
            $body['warning']
        );
    }

    public function testProcessOmitsRecoveryCodeFieldsWhenNull(): void
    {
        $request = $this->createRequest($this->faker->ipv4(), $this->faker->userAgent());
        $dto = new CompleteTwoFactorDto('01ARZ3NDEKTSV4RRFFQ69G5FB3', '123456');

        $this->commandBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(/**
             * @return true
             */
            static function (CompleteTwoFactorCommand $command): bool {
                $command->setResponse(
                    new CompleteTwoFactorCommandResponse(
                        'totp-access-token',
                        'totp-refresh-token'
                    )
                );

                return true;
            }));

        $processor = new CompleteTwoFactorProcessor($this->commandBus, $this->requestStack);
        $response = $processor->process($dto, $this->operation, [], ['request' => $request]);

        $body = json_decode((string) $response->getContent(), true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayNotHasKey('recovery_codes_remaining', $body);
        $this->assertArrayNotHasKey('warning', $body);
    }

    private function createRequest(string $ipAddress, string $userAgent): Request
    {
        return Request::create(
            '/api/signin/2fa',
            'POST',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => $ipAddress,
                'HTTP_USER_AGENT' => $userAgent,
            ]
        );
    }
}
