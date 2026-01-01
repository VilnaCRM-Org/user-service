<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Processor;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Processor\NoContentResponseCleaner;
use App\Shared\Application\OpenApi\Processor\OperationNoContentCleaner;
use App\Shared\Application\OpenApi\Processor\PathItemNoContentCleaner;
use App\Shared\Application\OpenApi\Processor\ResponseContentCleaner;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class NoContentResponseCleanerTest extends UnitTestCase
{
    public function testCleanRemovesContentForNoContentStatus(): void
    {
        $response = new Response(
            content: new ArrayObject(['application/json' => []])
        );
        $operation = new Operation(responses: ['204' => $response]);
        $pathItem = (new PathItem())->withPost($operation);
        $paths = new Paths();
        $paths->addPath('/resource', $pathItem);

        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $cleaned = $this->createCleaner()->clean($openApi);
        $cleanedResponse = $cleaned->getPaths()
            ->getPath('/resource')
            ->getPost()
            ->getResponses()['204'];

        $this->assertInstanceOf(Response::class, $cleanedResponse);
        $this->assertEquals(new ArrayObject(), $cleanedResponse->getContent());
    }

    public function testCleanLeavesNonNoContentResponsesUntouched(): void
    {
        $response = $this->createOkResponse();
        $openApi = $this->createOpenApiWith200And205Responses($response);

        $cleaned = $this->createCleaner()->clean($openApi);

        $this->assertNonNoContentResponseUntouched($cleaned, $response);
    }

    public function testCleanSkipsWhenResponsesCollectionIsNotArray(): void
    {
        $operation = new Operation();
        $pathItem = (new PathItem())->withDelete($operation);
        $paths = new Paths();
        $paths->addPath('/resource', $pathItem);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $cleaned = $this->createCleaner()->clean($openApi);

        $this->assertSame(
            $operation,
            $cleaned->getPaths()->getPath('/resource')->getDelete()
        );
    }

    public function testCleanSkipsNonResponseEntries(): void
    {
        $operation = new Operation(responses: ['204' => 'unexpected']);
        $pathItem = (new PathItem())->withPatch($operation);
        $paths = new Paths();
        $paths->addPath('/resource', $pathItem);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $cleaned = $this->createCleaner()->clean($openApi);

        $this->assertSame(
            'unexpected',
            $cleaned->getPaths()
                ->getPath('/resource')
                ->getPatch()
                ->getResponses()['204']
        );
    }

    public function testCleanerContinuesProcessingAfterNonResponseEntry(): void
    {
        $nonResponse = 'unexpected';
        $openApi = $this->createOpenApiWithNonResponseEntry($nonResponse);

        $cleaned = $this->createCleaner()->clean($openApi);

        $this->assertNonResponseEntryHandled($cleaned, $nonResponse);
    }

    private function createOkResponse(): Response
    {
        return new Response(
            description: 'OK',
            content: new ArrayObject(['application/json' => []])
        );
    }

    private function createOpenApiWith200And205Responses(Response $response): OpenApi
    {
        $operation = new Operation(responses: ['200' => $response, '205' => $response]);
        $pathItem = (new PathItem())->withGet($operation);
        $paths = new Paths();
        $paths->addPath('/resource', $pathItem);

        return new OpenApi(new Info('Test', '1.0.0'), [], $paths);
    }

    private function assertNonNoContentResponseUntouched(OpenApi $cleaned, Response $response): void
    {
        $cleanedResponses = $cleaned->getPaths()->getPath('/resource')->getGet()->getResponses();

        $this->assertSame($response, $cleanedResponses['200']);
        $this->assertEquals(new ArrayObject(), $cleanedResponses['205']->getContent());
    }

    private function createOpenApiWithNonResponseEntry(string $nonResponse): OpenApi
    {
        $response = new Response(content: new ArrayObject(['application/json' => []]));
        $operation = new Operation(responses: ['204' => $nonResponse, '205' => $response]);
        $pathItem = (new PathItem())->withPut($operation);
        $paths = new Paths();
        $paths->addPath('/resource', $pathItem);

        return new OpenApi(new Info('Test', '1.0.0'), [], $paths);
    }

    private function assertNonResponseEntryHandled(OpenApi $cleaned, string $nonResponse): void
    {
        $cleanedResponses = $cleaned->getPaths()->getPath('/resource')->getPut()->getResponses();

        $this->assertSame($nonResponse, $cleanedResponses['204']);
        $this->assertEquals(new ArrayObject(), $cleanedResponses['205']->getContent());
    }

    private function createCleaner(): NoContentResponseCleaner
    {
        return new NoContentResponseCleaner(
            new PathItemNoContentCleaner(
                new OperationNoContentCleaner(
                    new ResponseContentCleaner()
                )
            )
        );
    }
}
