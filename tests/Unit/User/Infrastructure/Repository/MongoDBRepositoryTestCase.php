<?php

declare(strict_types=1);

namespace App\Tests\Unit\User\Infrastructure\Repository;

use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\Query\Builder;
use PHPUnit\Framework\MockObject\MockObject;

abstract class MongoDBRepositoryTestCase extends UnitTestCase
{
    /**
     * @template T of object
     *
     * @param class-string<T> $repositoryClass
     * @param list<object>   $constructorArgs
     * @param list<string>   $methods
     *
     * @return T&MockObject
     */
    protected function createRepositoryMock(
        string $repositoryClass,
        array $constructorArgs,
        array $methods
    ): MockObject {
        return $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs($constructorArgs)
            ->onlyMethods($methods)
            ->getMock();
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $repositoryClass
     * @param list<object>   $constructorArgs
     * @param list<string>   $extraMethods
     *
     * @return T&MockObject
     */
    protected function createRepositoryMockWithQueryBuilder(
        string $repositoryClass,
        array $constructorArgs,
        Builder $queryBuilder,
        array $extraMethods = []
    ): MockObject {
        $repository = $this->createRepositoryMock(
            $repositoryClass,
            $constructorArgs,
            array_values(array_unique(['createQueryBuilder', ...$extraMethods]))
        );
        $repository->method('createQueryBuilder')->willReturn($queryBuilder);

        return $repository;
    }
}
