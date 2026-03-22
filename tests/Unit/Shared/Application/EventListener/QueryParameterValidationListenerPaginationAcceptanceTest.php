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

final class QueryParameterValidationListenerPaginationAcceptanceTest extends UnitTestCase
{
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

    public function testIsExplicitlyProvidedTrimsWhitespace(): void
    {
        $evaluator = new Evaluator\ExplicitValueEvaluator();

        $this->assertFalse($evaluator->isExplicitlyProvided('   '));
    }

    public function testIsExplicitlyProvidedDetectsEmptyArray(): void
    {
        $evaluator = new Evaluator\ExplicitValueEvaluator();

        $this->assertFalse($evaluator->isExplicitlyProvided([]));
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
