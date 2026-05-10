<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Domain\Bus\Command\CommandInterface;
use App\Shared\Domain\Bus\Command\CommandResponseInterface;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;

abstract class AuthProcessorTestCase extends UnitTestCase
{
    /**
     * @template TCommand of CommandInterface
     *
     * @param class-string<TCommand> $commandClass
     * @param callable(TCommand): void $assertCommand
     */
    protected function expectDispatchMatchingCommand(
        CommandBusInterface&MockObject $commandBus,
        string $commandClass,
        CommandResponseInterface $response,
        callable $assertCommand,
    ): void {
        $commandBus->expects($this->once())->method('dispatch')
            ->with($this->callback(
                static function (CommandInterface $command) use (
                    $commandClass,
                    $assertCommand,
                ): bool {
                    self::assertInstanceOf($commandClass, $command);
                    $assertCommand($command);

                    return true;
                }
            ))
            ->willReturn($response);
    }

    /**
     * @param class-string $commandClass
     */
    protected function expectDispatchWithRequestMetadata(
        CommandBusInterface&MockObject $commandBus,
        string $commandClass,
        CommandResponseInterface $response,
        string $expectedIpAddress,
        string $expectedUserAgent,
    ): void {
        $this->expectDispatchMatchingCommand(
            $commandBus,
            $commandClass,
            $response,
            function (CommandInterface $cmd) use (
                $expectedIpAddress,
                $expectedUserAgent,
            ): void {
                $this->assertSame(
                    $expectedIpAddress,
                    $this->readStringProperty($cmd, 'ipAddress')
                );
                $this->assertSame(
                    $expectedUserAgent,
                    $this->readStringProperty($cmd, 'userAgent')
                );
            }
        );
    }

    protected function stubRequestContextResolver(
        HttpRequestContextResolverInterface&MockObject $resolver,
        ?Request $request,
        string $ipAddress,
        string $userAgent,
    ): void {
        $resolver->method('resolveRequest')->willReturn($request);
        $this->stubRequestContextMetadata(
            $resolver,
            $request,
            $ipAddress,
            $userAgent
        );
    }

    protected function stubRandomRequestContext(
        HttpRequestContextResolverInterface&MockObject $resolver,
        ?Request $request = null,
    ): Request {
        $request ??= $this->createMock(Request::class);
        $this->stubRequestContextResolver(
            $resolver,
            $request,
            $this->faker->ipv4(),
            $this->faker->userAgent()
        );

        return $request;
    }

    /**
     * @return array{string, string}
     */
    protected function expectResolvedRequestContext(
        HttpRequestContextResolverInterface&MockObject $resolver,
        ?Request $expectedRequest,
        Request $resolvedRequest,
    ): array {
        $ipAddress = $this->faker->ipv4();
        $userAgent = $this->faker->userAgent();
        $resolver->expects($this->once())
            ->method('resolveRequest')->with($expectedRequest)
            ->willReturn($resolvedRequest);
        $this->stubRequestContextMetadata($resolver, $resolvedRequest, $ipAddress, $userAgent);

        return [$ipAddress, $userAgent];
    }

    protected function stubRequestContextMetadata(
        HttpRequestContextResolverInterface&MockObject $resolver,
        ?Request $request,
        string $ipAddress,
        string $userAgent,
    ): void {
        $resolver->method('resolveIpAddress')
            ->with($request)->willReturn($ipAddress);
        $resolver->method('resolveUserAgent')
            ->with($request)->willReturn($userAgent);
    }

    private function readStringProperty(object $object, string $property): string
    {
        if (!property_exists($object, $property)) {
            self::fail(sprintf('Expected %s to expose property "%s".', $object::class, $property));
        }

        $value = $object->{$property};

        self::assertIsString($value);

        return $value;
    }
}
