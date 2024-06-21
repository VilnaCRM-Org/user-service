<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\UserRegisterBatchDto;
use App\User\Application\Factory\RegisterUserBatchCommandFactoryInterface;
use App\User\Domain\Collection\UserCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @implements ProcessorInterface<UserRegisterBatchDto, Response>
 */
final readonly class RegisterUserBatchProcessor implements ProcessorInterface
{
    public function __construct(
        private SerializerInterface $serializer,
        private CommandBusInterface $commandBus,
        private RegisterUserBatchCommandFactoryInterface $commandFactory
    ) {
    }

    /**
     * @param UserRegisterBatchDto $data
     * @param array<string,string> $context
     * @param array<string,string> $uriVariables
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Response {
        $normalizationGroups =
            $operation->getNormalizationContext()['groups'] ?? [];
        $command = $this->commandFactory->create(
            new UserCollection($data->users)
        );
        $this->commandBus->dispatch($command);

        return new Response(
            content: $this->serializer->serialize(
                $command->getResponse()->users,
                JsonEncoder::FORMAT,
                ['groups' => $normalizationGroups]
            ),
            status: HttpResponse::HTTP_CREATED
        );
    }
}
