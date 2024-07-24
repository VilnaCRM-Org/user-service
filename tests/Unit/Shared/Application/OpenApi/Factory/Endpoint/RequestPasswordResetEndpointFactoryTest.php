<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Factory\Endpoint\RequestPasswordResetEndpointFactory;
use App\Shared\Application\OpenApi\Factory\Request\PasswordResetRequestFactory;
use App\Shared\Application\OpenApi\Factory\Response\PasswordResetTokenEmailSendFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserByEmailNotFoundResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserIsNotConfirmedResponseFactory;
use App\Shared\Application\OpenApi\Factory\Response\UserTimedOutResponseFactory;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class RequestPasswordResetEndpointFactoryTest extends UnitTestCase
{
    private Response $passwordResetTokenEmailSend;
    private Response $userIsNotConfirmed;
    private Response $userWithProvidedEmailNotFound;
    private Response $timedOutResponse;

    private PasswordResetTokenEmailSendFactory $passwordResetTokenEmailSendFactory;
    private UserIsNotConfirmedResponseFactory $userIsNotConfirmedResponseFactory;
    private UserByEmailNotFoundResponseFactory $userByEmailNotFoundResponseFactory;
    private UserTimedOutResponseFactory $timedOutResponseFactory;

    private PathItem $pathItem;
    private Operation $postOperation;
    private PasswordResetRequestFactory $passwordResetRequestFactory;

    private OpenApi $openApi;
    private Paths $paths;

    protected function setUp(): void
    {
        parent::setUp();

        $this->passwordResetTokenEmailSend = new Response();
        $this->userIsNotConfirmed = new Response();
        $this->userWithProvidedEmailNotFound = new Response();
        $this->timedOutResponse = new Response();

        $this->passwordResetTokenEmailSendFactory =
            $this->createMock(PasswordResetTokenEmailSendFactory::class);
        $this->userByEmailNotFoundResponseFactory =
            $this->createMock(UserByEmailNotFoundResponseFactory::class);
        $this->timedOutResponseFactory =
            $this->createMock(UserTimedOutResponseFactory::class);
        $this->userIsNotConfirmedResponseFactory =
            $this->createMock(UserIsNotConfirmedResponseFactory::class);
        $this->passwordResetRequestFactory =
            $this->createMock(PasswordResetRequestFactory::class);

        $this->postOperation = $this->createMock(Operation::class);

        $this->pathItem = new PathItem();
        $this->pathItem = $this->pathItem->withPost($this->postOperation);

        $this->openApi = $this->createMock(OpenApi::class);
        $this->paths = $this->createMock(Paths::class);
    }

    public function testCreateEndpoint(): void
    {
        $this->setExpectations();

        $factory = new RequestPasswordResetEndpointFactory(
            $this->passwordResetTokenEmailSendFactory,
            $this->userByEmailNotFoundResponseFactory,
            $this->timedOutResponseFactory,
            $this->userIsNotConfirmedResponseFactory,
            $this->passwordResetRequestFactory
        );

        $factory->createEndpoint($this->openApi);
        $this->postOperation = $this->pathItem->getPost();
        $responses = $this->postOperation->getResponses() ?? [];
        $this->assertEquals(
            [
                HttpResponse::HTTP_OK => $this->passwordResetTokenEmailSend,
                HttpResponse::HTTP_FORBIDDEN => $this->userIsNotConfirmed,
                HttpResponse::HTTP_NOT_FOUND => $this->userWithProvidedEmailNotFound,
                HttpResponse::HTTP_TOO_MANY_REQUESTS => $this->timedOutResponse,
            ],
            $responses
        );
    }

    private function setExpectations(): void
    {
        $this->passwordResetTokenEmailSendFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->passwordResetTokenEmailSend);
        $this->userIsNotConfirmedResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->userIsNotConfirmed);
        $this->userByEmailNotFoundResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->userWithProvidedEmailNotFound);
        $this->timedOutResponseFactory->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->userWithProvidedEmailNotFound);

        $this->postOperation->method('getResponses')->willReturn([
            HttpResponse::HTTP_OK => $this->passwordResetTokenEmailSend,
            HttpResponse::HTTP_FORBIDDEN => $this->userIsNotConfirmed,
            HttpResponse::HTTP_NOT_FOUND => $this->userWithProvidedEmailNotFound,
            HttpResponse::HTTP_TOO_MANY_REQUESTS => $this->timedOutResponse,
        ]);
        $this->paths->method('getPath')->willReturn($this->pathItem);
        $this->openApi->method('getPaths')->willReturn($this->paths);
    }
}
