<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure;

use App\Tests\Unit\UnitTestCase;
use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;

abstract class OAuthInfrastructureTestCase extends UnitTestCase
{
    /**
     * @param array<string, array|bool|float|int|object|string|null> $captures
     */
    protected function makeBuilder(
        array|bool|float|int|object|string|null $result,
        array &$captures = []
    ): Builder {
        $builder = $this->createMock(Builder::class);
        $currentField = null;

        $this->configureMockBuilder($builder, $currentField, $captures);
        $this->configureMockQuery($builder, $result);

        return $builder;
    }

    /**
     * @param array<string, array|bool|float|int|object|string|null> $captures
     */
    private function configureMockBuilder(
        Builder $builder,
        ?string &$currentField,
        array &$captures
    ): void {
        $this->configureFieldCapture($builder, $currentField, $captures);
        $this->configureArrayCapture($builder, 'all', $currentField, $captures);
        $this->configureValueCapture($builder, 'equals', $currentField, $captures);
        $this->configureArrayCapture($builder, 'in', $currentField, $captures);
        $this->configureValueCapture($builder, 'set', $currentField, $captures);
        $this->configureValueCapture($builder, 'lt', $currentField, $captures);
        $this->configureReferenceCapture($builder, $currentField, $captures);
        $this->configureFlagCapture($builder, 'updateMany', $captures);
        $this->configureFlagCapture($builder, 'remove', $captures);
    }

    /**
     * @param array<string, array|bool|float|int|object|string|null> $captures
     */
    private function configureFieldCapture(
        Builder $builder,
        ?string &$currentField,
        array &$captures
    ): void {
        $builder->method('field')->willReturnCallback(
            static function (string $field) use (
                &$currentField,
                $builder,
                &$captures
            ): Builder {
                $currentField = $field;
                $captures['fields'][] = $field;

                return $builder;
            }
        );
    }

    /**
     * @param array<string, array|bool|float|int|object|string|null> $captures
     */
    private function configureArrayCapture(
        Builder $builder,
        string $method,
        ?string &$currentField,
        array &$captures
    ): void {
        $builder->method($method)->willReturnCallback(
            static function (array $values) use (
                &$currentField,
                $builder,
                &$captures,
                $method
            ): Builder {
                $captures[$method][$currentField] = $values;

                return $builder;
            }
        );
    }

    /**
     * @param array<string, array|bool|float|int|object|string|null> $captures
     */
    private function configureValueCapture(
        Builder $builder,
        string $method,
        ?string &$currentField,
        array &$captures
    ): void {
        $builder->method($method)->willReturnCallback(
            static function (array|bool|float|int|object|string|null $value) use (
                &$currentField,
                $builder,
                &$captures,
                $method
            ): Builder {
                $captures[$method][$currentField] = $value;

                return $builder;
            }
        );
    }

    /**
     * @param array<string, array|bool|float|int|object|string|null> $captures
     */
    private function configureReferenceCapture(
        Builder $builder,
        ?string &$currentField,
        array &$captures
    ): void {
        $builder->method('references')->willReturnCallback(
            static function (object $document) use (
                &$currentField,
                $builder,
                &$captures
            ): Builder {
                $captures['references'][$currentField] = $document;

                return $builder;
            }
        );
    }

    /**
     * @param array<string, array|bool|float|int|object|string|null> $captures
     */
    private function configureFlagCapture(
        Builder $builder,
        string $method,
        array &$captures
    ): void {
        $builder->method($method)->willReturnCallback(
            static function () use ($builder, &$captures, $method): Builder {
                $captures[$method] = true;

                return $builder;
            }
        );
    }

    private function configureMockQuery(
        Builder $builder,
        array|bool|float|int|object|string|null $result
    ): void {
        $query = $this->createMock(Query::class);
        $query->method('execute')->willReturn($result);
        $builder->method('getQuery')->willReturn($query);
    }
}
