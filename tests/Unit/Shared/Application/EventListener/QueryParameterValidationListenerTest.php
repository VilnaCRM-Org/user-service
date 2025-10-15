<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\QueryParameter as QP;
use App\Shared\Application\EventListener\QueryParameter\Pagination as QPP;
use App\Shared\Application\EventListener\QueryParameter\QueryParameterViolationFactory;
use App\Shared\Application\EventListener\QueryParameter\QueryViolationFinder;
use App\Shared\Application\EventListener\QueryParameterValidationListener;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class QueryParameterValidationListenerTest extends UnitTestCase
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

        $this->assertTrue($event->hasResponse());
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

        $this->assertTrue($event->hasResponse());
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

        $this->assertTrue($event->hasResponse());
        $this->assertProblemJson(
            $event,
            'Invalid pagination value',
            'Page and itemsPerPage must be greater than or equal to 1.'
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

    public function testStringPaginationValuesAreAccepted(): void
    {
        $listener = $this->createListener();
        $request = Request::create(
            '/api/users',
            'GET',
            ['page' => '3', 'itemsPerPage' => '10']
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testNullPaginationValuesAreIgnored(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/users', 'GET');
        $request->query->set('page', null);
        $request->query->set('itemsPerPage', null);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
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

    public function testIsExplicitlyProvidedTrimsWhitespace(): void
    {
        $evaluator = new QPP\ExplicitValueEvaluator();

        $this->assertFalse($evaluator->isExplicitlyProvided('   '));
    }

    public function testIsExplicitlyProvidedDetectsEmptyArray(): void
    {
        $evaluator = new QPP\ExplicitValueEvaluator();

        $this->assertFalse($evaluator->isExplicitlyProvided([]));
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

    public function testItemsPerPageOfOneIsAccepted(): void
    {
        $listener = $this->createListener();
        $request = Request::create('/api/users', 'GET', ['itemsPerPage' => 1]);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
    }

    public function testItemsPerPageOfHundredIsAccepted(): void
    {
        $listener = $this->createListener();
        $request = Request::create(
            '/api/users',
            'GET',
            ['itemsPerPage' => 100]
        );

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertFalse($event->hasResponse());
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
        $valueEvaluator = new QPP\ExplicitValueEvaluator();
        $normalizer = new QPP\PositiveIntegerNormalizer();
        $violationFactory = new QueryParameterViolationFactory();
        $itemsPerPageRule = new QPP\ItemsPerPageRule(
            $valueEvaluator,
            $normalizer,
            $violationFactory
        );

        $allowedParametersRule = new QP\AllowedParametersRule([
            '/api/users' => ['page', 'itemsPerPage'],
        ], $violationFactory);

        $paginationRule = new QPP\PaginationRule(
            new QPP\PageParameterValidator(
                $valueEvaluator,
                $normalizer,
                $violationFactory
            ),
            new QPP\ItemsPerPageParameterValidator(
                $itemsPerPageRule
            )
        );

        return new QueryParameterValidationListener(
            [
                $allowedParametersRule,
                $paginationRule,
            ],
            new QueryViolationFinder()
        );
    }
}
