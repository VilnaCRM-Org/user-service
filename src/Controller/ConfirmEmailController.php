<?php

declare(strict_types=1);

namespace App\Controller;

use App\Shared\Domain\Bus\Command\CommandBus;
use App\Shared\Infrastructure\TokenNotFoundError;
use App\User\Application\ConfirmEmailCommand;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Exception;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
final class ConfirmEmailController extends AbstractController
{
    #[Route('/confirm', name: 'confirm', methods: ['GET'])]
    public function __invoke(Request $request, CommandBus $commandBus): Response
    {
        try {
            $token = $request->query->get('token');
            if(!$token){
                throw new InvalidArgumentException('Token was empty');
            }

            $commandBus->dispatch(new ConfirmEmailCommand($token));

            return new Response('', Response::HTTP_CREATED);
        } catch (TokenNotFoundError $error) {
            return new Response($error->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (InvalidArgumentException $error){
            return new Response($error->getMessage(), Response::HTTP_NOT_FOUND);
        }
    }
}
