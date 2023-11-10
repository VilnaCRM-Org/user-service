<?php

declare(strict_types=1);

namespace App\Controller;

use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\SignUpCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[AsController]
final class SignUpHttpController extends AbstractController
{
    #[Route('/user/sign_up', name: 'user_sign_up', methods: ['POST'])]
    public function __invoke(Request $request, CommandBus $commandBus): Response
    {
        try {
            $data = json_decode($request->getContent(), true);

            $email = $data['email'];
            $initials = $data['initials'];
            $password = $data['password'];

            $commandBus->dispatch(new SignUpCommand($email, $initials, $password));

            return new Response('', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new Response('An error occurred', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
