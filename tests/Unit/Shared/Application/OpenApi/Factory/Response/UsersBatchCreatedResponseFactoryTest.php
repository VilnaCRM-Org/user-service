<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\Response;

use ApiPlatform\OpenApi\Model\Response;
use App\Shared\Application\Fixture\SchemathesisFixtures;
use App\Shared\Application\OpenApi\Builder\ArrayResponseBuilder;
use App\Shared\Application\OpenApi\Builder\Parameter;
use App\Shared\Application\OpenApi\Factory\Response\UsersBatchCreatedResponseFactory;
use App\Tests\Unit\UnitTestCase;

final class UsersBatchCreatedResponseFactoryTest extends UnitTestCase
{
    public function testGetResponseProvidesBatchSpecificExamples(): void
    {
        $responseBuilder = $this->createMock(ArrayResponseBuilder::class);

        $factory = new UsersBatchCreatedResponseFactory($responseBuilder);

        $responseBuilder->expects($this->once())
            ->method('build')
            ->with(
                'Users returned',
                [
                    new Parameter('confirmed', 'boolean', false),
                    new Parameter(
                        'email',
                        'string',
                        SchemathesisFixtures::CREATE_BATCH_FIRST_USER_EMAIL
                    ),
                    new Parameter(
                        'initials',
                        'string',
                        SchemathesisFixtures::CREATE_BATCH_FIRST_USER_INITIALS
                    ),
                    new Parameter('id', 'string', SchemathesisFixtures::CREATE_BATCH_FIRST_USER_ID),
                ],
                []
            )
            ->willReturn($this->createStub(Response::class));

        $factory->getResponse();
    }
}
