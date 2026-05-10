<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Post;
use App\Shared\Application\Validator\Http\EmptyJsonObjectRequestValidator;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\SetupTwoFactorCommand;
use App\User\Application\DTO\SetupTwoFactorCommandResponse;
use App\User\Application\DTO\SetupTwoFactorDto;
use App\User\Application\Factory\SetupTwoFactorCommandFactory;
use App\User\Application\Processor\SetupTwoFactorProcessor;
use App\User\Application\Resolver\CurrentUserIdentityResolver;
use App\User\Application\Resolver\HttpRequestContextResolver;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

final class SetupTwoFactorProcessorTest extends UnitTestCase
{
    private CommandBusInterface&MockObject $commandBus;
    private Security&MockObject $security;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->security = $this->createMock(Security::class);
    }

    public function testProcessDispatchesSetupCommandForAuthenticatedUser(): void
    {
        $email = $this->faker->email();
        $securityUser = $this->createSecurityUser($email);
        $this->security->expects($this->once())->method('getUser')->willReturn($securityUser);
        ['uri' => $uri, 'secret' => $secret] = $this->createSetupResponseData($email);
        $this->expectSetupDispatch($email, $uri, $secret);

        $response = $this->createProcessor()->process(new SetupTwoFactorDto(), new Post());

        $this->assertSetupResponse($response, $uri, $secret);
    }

    public function testProcessThrowsUnauthorizedWhenNoUserExists(): void
    {
        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');

        $this->createProcessor()
            ->process(new SetupTwoFactorDto(), new Post());
    }

    public function testProcessThrowsUnauthorizedWhenIdentifierIsEmpty(): void
    {
        $securityUser = $this->createSecurityUser('');

        $this->security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($securityUser);

        $this->commandBus
            ->expects($this->never())
            ->method('dispatch');

        $this->expectException(UnauthorizedHttpException::class);
        $this->expectExceptionMessage('Authentication required.');

        $this->createProcessor()
            ->process(new SetupTwoFactorDto(), new Post());
    }

    public function testProcessRejectsNonEmptyRequestBodiesResolvedFromRequestStack(): void
    {
        $this->expectRejectedRequestBody('[null, null]');
    }

    public function testProcessAcceptsEmptyJsonObjectRequestBody(): void
    {
        $email = $this->faker->email();
        $securityUser = $this->createSecurityUser($email);
        $this->security->expects($this->once())->method('getUser')->willReturn($securityUser);
        ['uri' => $uri, 'secret' => $secret] = $this->createSetupResponseData($email);
        $this->expectSetupDispatch($email, $uri, $secret);

        $requestStack = new RequestStack();
        $requestStack->push(
            Request::create(
                '/api/2fa/setup',
                Request::METHOD_POST,
                [],
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                '{}'
            )
        );

        $response = $this->createProcessor($requestStack)
            ->process(new SetupTwoFactorDto(), new Post());

        $this->assertSetupResponse($response, $uri, $secret);
    }

    public function testProcessAcceptsWhitespaceOnlyRequestBody(): void
    {
        $email = $this->faker->email();
        $securityUser = $this->createSecurityUser($email);
        $this->security->expects($this->once())->method('getUser')->willReturn($securityUser);
        ['uri' => $uri, 'secret' => $secret] = $this->createSetupResponseData($email);
        $this->expectSetupDispatch($email, $uri, $secret);

        $requestStack = new RequestStack();
        $requestStack->push(
            Request::create(
                '/api/2fa/setup',
                Request::METHOD_POST,
                [],
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                " \n\t "
            )
        );

        $response = $this->createProcessor($requestStack)
            ->process(new SetupTwoFactorDto(), new Post());

        $this->assertSetupResponse($response, $uri, $secret);
    }

    public function testProcessRejectsInvalidJsonRequestBody(): void
    {
        $this->expectRejectedRequestBody('{');
    }

    private function createProcessor(?RequestStack $requestStack = null): SetupTwoFactorProcessor
    {
        $requestStack ??= new RequestStack();

        return new SetupTwoFactorProcessor(
            $this->commandBus,
            new CurrentUserIdentityResolver($this->security),
            new SetupTwoFactorCommandFactory(),
            new HttpRequestContextResolver($requestStack),
            new EmptyJsonObjectRequestValidator($this->createJsonSerializer()),
        );
    }

    private function createJsonRequestStack(string $content): RequestStack
    {
        $requestStack = new RequestStack();
        $requestStack->push(
            Request::create(
                '/api/2fa/setup',
                Request::METHOD_POST,
                [],
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                $content,
            )
        );

        return $requestStack;
    }

    private function expectRejectedRequestBody(string $content): void
    {
        $this->security->expects($this->never())->method('getUser');
        $this->commandBus->expects($this->never())->method('dispatch');

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('This operation does not accept request body content.');

        $this->createProcessor($this->createJsonRequestStack($content))
            ->process(new SetupTwoFactorDto(), new Post());
    }

    /**
     * @return array{uri: string, secret: string}
     */
    private function createSetupResponseData(string $email): array
    {
        $secret = strtoupper($this->faker->bothify('??##??##'));

        return [
            'uri' => sprintf(
                'otpauth://totp/VilnaCRM:%s?secret=%s&issuer=VilnaCRM',
                rawurlencode($email),
                $secret,
            ),
            'secret' => $secret,
        ];
    }

    private function expectSetupDispatch(
        string $email,
        string $uri,
        string $secret
    ): void {
        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($this->callback(
                static function (SetupTwoFactorCommand $cmd) use ($email): bool {
                    return $cmd->userEmail === $email;
                }
            ))
            ->willReturn(new SetupTwoFactorCommandResponse($uri, $secret));
    }

    private function assertSetupResponse(
        mixed $response,
        string $uri,
        string $secret
    ): void {
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'otpauth_uri' => $uri,
                'secret' => $secret,
            ], JSON_THROW_ON_ERROR),
            (string) $response->getContent()
        );
    }

    private function createSecurityUser(string $identifier): UserInterface&MockObject
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn($identifier);
        $user->method('getRoles')->willReturn(['ROLE_USER']);

        return $user;
    }
}
