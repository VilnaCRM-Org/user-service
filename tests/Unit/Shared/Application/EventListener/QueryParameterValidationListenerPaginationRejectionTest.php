<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\EventListener;

use App\Shared\Application\EventListener\QueryParameterValidationListener;
use App\Shared\Application\Factory\QueryParameterViolationFactory;
use App\Shared\Application\QueryParameter as QP;
use App\Shared\Application\QueryParameter\Normalizer;
use App\Shared\Application\QueryParameter\Pagination as QPP;
use App\Shared\Application\QueryParameter\Validator;
use App\Shared\Application\Resolver\QueryViolationResolver;
use App\Shared\Application\Validator\Pagination as VP;
use App\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class QueryParameterValidationListenerPaginationRejectionTest extends UnitTestCase
{
    private const USERS_PATH = '/api/users';
    private const INVALID_PAGINATION_TITLE = 'Invalid pagination value';
    private const PAGINATION_DETAIL = 'Page and itemsPerPage must be greater than or equal to 1.';
    private const INVALID_PARTIAL_TITLE = 'Invalid partial pagination value';
    private const INVALID_PARTIAL_DETAIL = 'The partial parameter must be either true or false.';

    public function testInvalidPaginationValueTriggersError(): void
    {
        $listener = $this->createListener();
        $request = Request::create(self::USERS_PATH, 'GET', ['itemsPerPage' => 0]);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            self::INVALID_PAGINATION_TITLE,
            self::PAGINATION_DETAIL
        );
    }

    public function testRejectsNonIntegerPaginationValues(): void
    {
        $listener = $this->createListener();
        $request = Request::create(self::USERS_PATH, 'GET', ['page' => '2.5']);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            self::INVALID_PAGINATION_TITLE,
            self::PAGINATION_DETAIL
        );
    }

    public function testRejectsItemsPerPageAboveLimit(): void
    {
        $listener = $this->createListener();
        $request = Request::create(
            self::USERS_PATH,
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
            self::INVALID_PAGINATION_TITLE,
            self::PAGINATION_DETAIL
        );
    }

    public function testBlankItemsPerPageTriggersViolation(): void
    {
        $listener = $this->createListener();
        $request = Request::create(self::USERS_PATH, 'GET', ['itemsPerPage' => '']);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            self::INVALID_PAGINATION_TITLE,
            self::PAGINATION_DETAIL
        );
    }

    public function testEmptyArrayPaginationValuesTriggerViolation(): void
    {
        $listener = $this->createListener();
        $request = Request::create(self::USERS_PATH, 'GET');
        $request->query->set('itemsPerPage', []);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            self::INVALID_PAGINATION_TITLE,
            self::PAGINATION_DETAIL
        );
    }

    public function testWhitespacePaginationValuesTriggerViolation(): void
    {
        $listener = $this->createListener();
        $request = Request::create(
            self::USERS_PATH,
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
            self::INVALID_PAGINATION_TITLE,
            self::PAGINATION_DETAIL
        );
    }

    public function testZeroPageTriggersViolation(): void
    {
        $listener = $this->createListener();
        $request = Request::create(self::USERS_PATH, 'GET', ['page' => 0]);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            self::INVALID_PAGINATION_TITLE,
            self::PAGINATION_DETAIL
        );
    }

    public function testBlankPartialValueTriggersViolation(): void
    {
        $listener = $this->createListener();
        $request = Request::create(self::USERS_PATH, 'GET', ['partial' => '']);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            self::INVALID_PARTIAL_TITLE,
            self::INVALID_PARTIAL_DETAIL
        );
    }

    public function testInvalidPartialValueTriggersViolation(): void
    {
        $listener = $this->createListener();
        $request = Request::create(self::USERS_PATH, 'GET', ['partial' => 'garbage']);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            self::INVALID_PARTIAL_TITLE,
            self::INVALID_PARTIAL_DETAIL
        );
    }

    public function testNumericPartialValueTriggersViolation(): void
    {
        $listener = $this->createListener();
        $request = Request::create(self::USERS_PATH, 'GET', ['partial' => '1']);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            self::INVALID_PARTIAL_TITLE,
            self::INVALID_PARTIAL_DETAIL
        );
    }

    public function testAliasPartialValueTriggersViolation(): void
    {
        $listener = $this->createListener();
        $request = Request::create(self::USERS_PATH, 'GET', ['partial' => 'yes']);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            self::INVALID_PARTIAL_TITLE,
            self::INVALID_PARTIAL_DETAIL
        );
    }

    public function testArrayPartialValueTriggersViolation(): void
    {
        $listener = $this->createListener();
        $request = Request::create(self::USERS_PATH, 'GET');
        $request->query->set('partial', ['true']);

        $event = new RequestEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $listener($event);

        $this->assertProblemJson(
            $event,
            self::INVALID_PARTIAL_TITLE,
            self::INVALID_PARTIAL_DETAIL
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
            new QueryViolationResolver()
        );
    }

    private function createAllowedParametersRule(): QP\AllowedParametersRule
    {
        $violationFactory = new QueryParameterViolationFactory();
        $params = ['/api/users' => ['page', 'itemsPerPage', 'partial']];
        return new QP\AllowedParametersRule($params, $violationFactory);
    }

    private function createPaginationRule(): QPP\PaginationRule
    {
        $valueValidator = new Validator\ExplicitValueValidator();
        $integerNormalizer = new Normalizer\PositiveIntegerNormalizer();
        $booleanNormalizer = new Normalizer\BooleanNormalizer();
        $violationFactory = new QueryParameterViolationFactory();

        return new QPP\PaginationRule(
            new VP\PageParameterValidator($valueValidator, $integerNormalizer, $violationFactory),
            new VP\ItemsPerPageParameterValidator(
                new QPP\ItemsPerPageRule($valueValidator, $integerNormalizer, $violationFactory)
            ),
            new VP\PartialParameterValidator($valueValidator, $booleanNormalizer, $violationFactory)
        );
    }
}
