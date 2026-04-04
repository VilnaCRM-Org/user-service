<?php

declare(strict_types=1);

namespace App\OAuth\Application\Controller;

use App\OAuth\Application\Command\InitiateOAuthCommand;
use App\OAuth\Application\Factory\OAuthFlowCookieFactory;
use App\Shared\Domain\Bus\Command\CommandBusInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;

/**
 * @psalm-api
 */
#[AsController]
final readonly class OAuthInitiateController
{
    public function __construct(
        private CommandBusInterface $commandBus,
        private OAuthFlowCookieFactory $flowCookieFactory,
    ) {
    }

    public function __invoke(string $provider, Request $request): Response
    {
        $command = new InitiateOAuthCommand(
            $provider,
            $request->getSchemeAndHttpHost() . '/api/auth/social/' . $provider . '/callback',
        );

        $this->commandBus->dispatch($command);

        $commandResponse = $command->getResponse();

        $response = new RedirectResponse(
            $commandResponse->authorizationUrl,
            Response::HTTP_FOUND,
        );

        $response->headers->setCookie(
            $this->flowCookieFactory->create($commandResponse->flowBindingToken)
        );

        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
