<?php

declare(strict_types=1);

namespace App\User\Application\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use App\User\Application\DTO\SetupTwoFactorDto;
use App\User\Application\Factory\SetupTwoFactorCommandFactoryInterface;
use App\User\Application\Resolver\CurrentUserIdentityResolver;
use App\User\Application\Resolver\HttpRequestContextResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\SerializerInterface;

use function get_object_vars;
use function trim;

/**
 * @implements ProcessorInterface<SetupTwoFactorDto, Response>
 */
final readonly class SetupTwoFactorProcessor implements ProcessorInterface
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private CurrentUserIdentityResolver $userIdentityResolver,
        private SetupTwoFactorCommandFactoryInterface $setupTwoFactorCommandFactory,
        private HttpRequestContextResolverInterface $httpRequestContextResolver,
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @param array<string, mixed> $uriVariables
     * @param array<string, mixed> $context
     *
     * @return JsonResponse
     */
    #[\Override]
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): Response {
        $request = $this->httpRequestContextResolver->resolveRequest($context['request'] ?? null);
        $this->assertEmptyRequestBody($request);

        $command = $this->setupTwoFactorCommandFactory->create(
            $this->userIdentityResolver->resolveEmail()
        );
        $this->commandBus->dispatch($command);
        $response = $command->getResponse();

        return new JsonResponse(
            [
                'otpauth_uri' => $response->getOtpauthUri(),
                'secret' => $response->getSecret(),
            ],
            Response::HTTP_OK
        );
    }

    private function assertEmptyRequestBody(mixed $request): void
    {
        if (!$request instanceof Request) {
            return;
        }

        $content = trim($request->getContent());
        if ($content === '') {
            return;
        }

        try {
            $decoded = $this->serializer->decode(
                $content,
                JsonEncoder::FORMAT,
                [JsonDecode::ASSOCIATIVE => false],
            );
        } catch (NotEncodableValueException) {
            throw new BadRequestHttpException(
                'This operation does not accept request body content.'
            );
        }

        if ($decoded instanceof \stdClass && get_object_vars($decoded) === []) {
            return;
        }

        throw new BadRequestHttpException(
            'This operation does not accept request body content.'
        );
    }
}
