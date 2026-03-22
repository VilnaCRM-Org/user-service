<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\QueryParameterValidationListener;
use App\Shared\Application\Factory\QueryParameterViolationFactory;
use App\Shared\Application\Finder\QueryViolationFinder;
use App\Shared\Application\QueryParameter as QP;
use App\Shared\Application\QueryParameter\Evaluator;
use App\Shared\Application\QueryParameter\Normalizer;
use App\Shared\Application\QueryParameter\Pagination as QPP;
use App\Shared\Application\Validator\Pagination as VP;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class QueryParameterValidationListenerAllowedParametersTest extends UnitTestCase
{
    public function testAllowsKnownParameters(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/users', 'GET', ['page' => 1]);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testBlocksUnknownParameters(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/users', 'GET', ['unexpected' => 1]);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertTrue($event->hasResponse());
        $this->assertProblemJson(
            $event,
            'Invalid query parameter',
            'Unknown query parameter(s): unexpected'
        );
    }

    public function testIgnoresSubRequest(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/users', 'GET', ['unexpected' => 1]);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testPathWithoutRestrictionsIsIgnored(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/health', 'GET', ['unexpected' => 1]);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testUnknownParameterOverridesPaginationErrors(): void
    {
        $listener = $this->createListener();
        $request = Request::create(
            '/api/users',
            'GET',
            ['unexpected' => 1, 'itemsPerPage' => 0]
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            'Invalid query parameter',
            'Unknown query parameter(s): unexpected'
        );
    }

    private function assertProblemJson(
        RequestEvent $event,
        string $expectedTitle,
        string $expectedDetail
    ): void {
        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(
            'application/problem+json',
            $response->headers->get('Content-Type')
        );

        $payload = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertSame('/errors/400', $payload['type']);
        $this->assertSame($expectedTitle, $payload['title']);
        $this->assertSame($expectedDetail, $payload['detail']);
        $this->assertSame(400, $payload['status']);
    }

    private function createListener(): QueryParameterValidationListener
    {
        return new QueryParameterValidationListener(
            [$this->createAllowedParametersRule(), $this->createPaginationRule()],
            new QueryViolationFinder()
        );
    }

    private function createAllowedParametersRule(): QP\AllowedParametersRule
    {
        $violationFactory = new QueryParameterViolationFactory();
        $params = ['/api/users' => ['page', 'itemsPerPage']];
        return new QP\AllowedParametersRule($params, $violationFactory);
    }

    private function createPaginationRule(): QPP\PaginationRule
    {
        $valueEvaluator = new Evaluator\ExplicitValueEvaluator();
        $normalizer = new Normalizer\PositiveIntegerNormalizer();
        $violationFactory = new QueryParameterViolationFactory();

        return new QPP\PaginationRule(
            new VP\PageParameterValidator($valueEvaluator, $normalizer, $violationFactory),
            new VP\ItemsPerPageParameterValidator(
                new QPP\ItemsPerPageRule($valueEvaluator, $normalizer, $violationFactory)
            )
        );
    }
}
