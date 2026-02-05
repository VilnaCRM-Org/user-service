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

final class QueryParameterValidationListenerPaginationRejectionTest extends UnitTestCase
{
    public function testInvalidPaginationValueTriggersError(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/users', 'GET', ['itemsPerPage' => 0]);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            'Invalid pagination value',
            'Page and itemsPerPage must be greater than or equal to 1.'
        );
    }

    public function testRejectsNonIntegerPaginationValues(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/users', 'GET', ['page' => '2.5']);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            'Invalid pagination value',
            'Page and itemsPerPage must be greater than or equal to 1.'
        );
    }

    public function testRejectsItemsPerPageAboveLimit(): void
    {
        $listener = $this->createListener();
        $request = Request::create(
            '/api/users',
            'GET',
            ['itemsPerPage' => 101]
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            'Invalid pagination value',
            'Page and itemsPerPage must be greater than or equal to 1.'
        );
    }

    public function testBlankItemsPerPageTriggersViolation(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/users', 'GET', ['itemsPerPage' => '']);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            'Invalid pagination value',
            'Page and itemsPerPage must be greater than or equal to 1.'
        );
    }

    public function testEmptyArrayPaginationValuesTriggerViolation(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/users', 'GET');
        $request->query->set('itemsPerPage', []);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            'Invalid pagination value',
            'Page and itemsPerPage must be greater than or equal to 1.'
        );
    }

    public function testWhitespacePaginationValuesTriggerViolation(): void
    {
        $listener = $this->createListener();
        $request = Request::create(
            '/api/users',
            'GET',
            ['page' => '   ', 'itemsPerPage' => '   ']
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            'Invalid pagination value',
            'Page and itemsPerPage must be greater than or equal to 1.'
        );
    }

    public function testZeroPageTriggersViolation(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/users', 'GET', ['page' => 0]);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            'Invalid pagination value',
            'Page and itemsPerPage must be greater than or equal to 1.'
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
