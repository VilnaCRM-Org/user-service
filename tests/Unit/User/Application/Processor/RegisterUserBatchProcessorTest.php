<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\Shared\Infrastructure\Factory\UuidFactory;
use App\Shared\Infrastructure\Transformer\UuidTransformer;
use App\Tests\Unit\UnitTestCase;
use App\User\Application\Command\RegisterUserBatchCommand;
use App\User\Application\Command\RegisterUserBatchCommandResponse;
use App\User\Application\DTO\UserRegisterBatchDto;
use App\User\Application\Factory\RegisterUserBatchCommandFactoryInterface;
use App\User\Application\Processor\RegisterUserBatchProcessor;
use App\User\Domain\Collection\UserCollection;
use App\User\Domain\Entity\UserInterface;
use App\User\Domain\Factory\UserFactory;
use App\User\Domain\Factory\UserFactoryInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

final class RegisterUserBatchProcessorTest extends UnitTestCase
{
    private const BATCH_SIZE = 2;
    private SerializerInterface $serializer;
    private CommandBusInterface $commandBus;
    private RegisterUserBatchCommandFactoryInterface $commandFactory;
    private RegisterUserBatchProcessor $processor;
    private UserFactoryInterface $userFactory;
    private UuidTransformer $transformer;
    private Operation $operation;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = $this->createMock(SerializerInterface::class);
        $this->commandBus = $this->createMock(CommandBusInterface::class);
        $this->commandFactory =
            $this->createMock(RegisterUserBatchCommandFactoryInterface::class);

        $this->processor = new RegisterUserBatchProcessor(
            $this->serializer,
            $this->commandBus,
            $this->commandFactory
        );
        $this->userFactory = new UserFactory();
        $this->transformer = new UuidTransformer(new UuidFactory());
        $this->operation = $this->createMock(Operation::class);
    }

    public function testProcess(): void
    {
        $users = $this->getUsers();

        $this->setExpectations($users);

        $response = $this->processor->process(
            new UserRegisterBatchDto($users),
            $this->operation,
            [],
            ['operation' => $this->operation]
        );

        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertEquals(
            HttpResponse::HTTP_CREATED,
            $response->getStatusCode()
        );
        $this->assertJsonStringEqualsJsonString(
            json_encode($users),
            $response->getContent()
        );
    }

    /**
     * @param array<UserInterface> $users
     */
    private function setExpectations(array $users): void
    {
        $this->operation->expects($this->once())
            ->method('getNormalizationContext')
            ->willReturn(['groups' => ['output']]);

        $userCollection = new UserCollection($users);
        $command = $this->createMock(RegisterUserBatchCommand::class);
        $commandResponse =
            new RegisterUserBatchCommandResponse($userCollection);
        $command->expects($this->once())
            ->method('getResponse')
            ->willReturn($commandResponse);

        $this->commandFactory->expects($this->once())
            ->method('create')
            ->with($userCollection)
            ->willReturn($command);

        $this->commandBus->expects($this->once())
            ->method('dispatch')
            ->with($command);

        $this->setExpectationsForSerializer($commandResponse, $users);
    }

    /**
     * @param array<UserInterface> $users
     */
    private function setExpectationsForSerializer(
        RegisterUserBatchCommandResponse $commandResponse,
        array $users
    ): void {
        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with(
                $commandResponse->users,
                JsonEncoder::FORMAT,
                ['groups' => ['output']]
            )
            ->willReturn(json_encode($users));
    }

    /**
     * @return array<UserInterface>
     */
    private function getUsers(): array
    {
        $users = [];
        for ($i = 0; $i < self::BATCH_SIZE; $i++) {
            $email = $this->faker->email();
            $initials = $this->faker->name();
            $password = $this->faker->password();

            $users[] = $this->userFactory->create(
                $email,
                $initials,
                $password,
                $this->transformer->transformFromString($this->faker->uuid())
            );
        }

        return $users;
    }
}
