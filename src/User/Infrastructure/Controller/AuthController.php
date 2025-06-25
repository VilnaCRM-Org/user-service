<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Controller;

use App\User\Application\DTO\SignInDto;
use App\User\Application\Command\SignInCommand;
use App\User\Application\CommandHandler\SignInCommandHandler;
use App\User\Domain\Exception\InvalidCredentialsException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class AuthController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly SignInCommandHandler $signInHandler,
    ) {
    }

    public function signin(Request $request): JsonResponse
    {
        try {
            $signInDto = $this->serializer->deserialize(
                $request->getContent(),
                SignInDto::class,
                'json'
            );

            $violations = $this->validator->validate($signInDto);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }
                return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $command = new SignInCommand(
                email: $signInDto->email,
                password: $signInDto->password,
                rememberMe: $signInDto->rememberMe
            );

            $result = $this->signInHandler->handle($command);

            return new JsonResponse([
                'twoFactorEnabled' => $result->twoFactorEnabled,
                'sessionId' => $result->sessionId,
            ]);
        } catch (InvalidCredentialsException) {
            return new JsonResponse(
                ['error' => 'Invalid credentials'],
                Response::HTTP_UNAUTHORIZED
            );
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'Internal server error'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
