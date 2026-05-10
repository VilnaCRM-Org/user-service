<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Transformer;

use ApiPlatform\OpenApi\Model\Info;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use App\Shared\Application\OpenApi\Transformer\NoContentResponseTransformer;
use App\Shared\Application\OpenApi\Transformer\OperationNoContentTransformer;
use App\Shared\Application\OpenApi\Transformer\PathItemNoContentTransformer;
use App\Shared\Application\OpenApi\Transformer\ResponseContentTransformer;
use App\Tests\Unit\UnitTestCase;
use ArrayObject;

final class NoContentResponseTransformerTest extends UnitTestCase
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

        $transformed = $this->createTransformer()->transform($openApi);
        $transformedResponse = $transformed->getPaths()
            ->getPath('/resource')
            ->getPost()
            ->getResponses()['204'];

        $this->assertInstanceOf(Response::class, $transformedResponse);
        $this->assertEquals(new ArrayObject(), $transformedResponse->getContent());
    }

    public function testCleanLeavesNonNoContentResponsesUntouched(): void
    {
        $response = $this->createOkResponse();
        $openApi = $this->createOpenApiWith200And205Responses($response);

        $transformed = $this->createTransformer()->transform($openApi);

        $this->assertNonNoContentResponseUntouched($transformed, $response);
    }

    public function testCleanSkipsWhenResponsesCollectionIsNotArray(): void
    {
        $operation = new Operation();
        $pathItem = (new PathItem())->withDelete($operation);
        $paths = new Paths();
        $paths->addPath('/resource', $pathItem);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $transformed = $this->createTransformer()->transform($openApi);

        $this->assertSame(
            $operation,
            $transformed->getPaths()->getPath('/resource')->getDelete()
        );
    }

    public function testCleanSkipsNonResponseEntries(): void
    {
        $operation = new Operation(responses: ['204' => 'unexpected']);
        $pathItem = (new PathItem())->withPatch($operation);
        $paths = new Paths();
        $paths->addPath('/resource', $pathItem);
        $openApi = new OpenApi(new Info('Test', '1.0.0'), [], $paths);

        $transformed = $this->createTransformer()->transform($openApi);

        $this->assertSame(
            'unexpected',
            $transformed->getPaths()
                ->getPath('/resource')
                ->getPatch()
                ->getResponses()['204']
        );
    }

    public function testTransformerContinuesProcessingAfterNonResponseEntry(): void
    {
        $nonResponse = 'unexpected';
        $openApi = $this->createOpenApiWithNonResponseEntry($nonResponse);

        $transformed = $this->createTransformer()->transform($openApi);

        $this->assertNonResponseEntryHandled($transformed, $nonResponse);
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

    private function assertNonNoContentResponseUntouched(
        OpenApi $transformed,
        Response $response
    ): void {
        $path = $transformed->getPaths()->getPath('/resource');
        $transformedResponses = $path->getGet()->getResponses();

        $this->assertSame($response, $transformedResponses['200']);
        $this->assertEquals(new ArrayObject(), $transformedResponses['205']->getContent());
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

    private function assertNonResponseEntryHandled(
        OpenApi $transformed,
        string $nonResponse
    ): void {
        $path = $transformed->getPaths()->getPath('/resource');
        $transformedResponses = $path->getPut()->getResponses();

        $this->assertSame($nonResponse, $transformedResponses['204']);
        $this->assertEquals(new ArrayObject(), $transformedResponses['205']->getContent());
    }

    private function createTransformer(): NoContentResponseTransformer
    {
        return new NoContentResponseTransformer(
            new PathItemNoContentTransformer(
                new OperationNoContentTransformer(
                    new ResponseContentTransformer()
                )
            )
        );
    }
}
