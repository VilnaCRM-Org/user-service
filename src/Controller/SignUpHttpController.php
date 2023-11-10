<?php

declare(strict_types=1);

namespace App\Controller;

use App\Shared\Domain\Bus\Command\CommandBus;
use App\User\Application\SignUpCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
final class SignUpHttpController extends AbstractController
{
    #[Route('/user/sign_up', name: 'user_sign_up', methods: ['GET'])]
    public function __invoke(CommandBus $commandBus): Response
    {
        try {
            $commandBus->dispatch(new SignUpCommand(
                'test_email',
                'test_initials',
                'test_pass'
            ));

            return new Response('', Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new Response('An error occurred '.$e, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
