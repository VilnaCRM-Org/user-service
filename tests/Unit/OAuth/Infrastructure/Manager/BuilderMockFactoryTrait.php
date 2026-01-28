<?php

declare(strict_types=1);

namespace App\Tests\Unit\OAuth\Infrastructure\Manager;

use Doctrine\ODM\MongoDB\Query\Builder;
use Doctrine\ODM\MongoDB\Query\Query;

trait BuilderMockFactoryTrait
{
    /**
     * @param array<string, list<string>|array<string, array|object|string|int|bool|null>|bool> $captures
     */
    private function makeBuilder(array|object|int|null $result, array &$captures = []): Builder
    {
        $builder = $this->createMock(Builder::class);
        $currentField = null;

        $builder->method('field')->willReturnCallback(static function (string $field) use (&$currentField, $builder, &$captures): Builder {
            $currentField = $field;
            $captures['fields'][] = $field;

            return $builder;
        });

        $builder->method('all')->willReturnCallback(
            static function (array $values) use (&$currentField, $builder, &$captures): Builder {
                $captures['all'][$currentField] = $values;

                return $builder;
            }
        );

        $builder->method('equals')->willReturnCallback(
            static function (mixed $value) use (&$currentField, $builder, &$captures): Builder {
                $captures['equals'][$currentField] = $value;

                return $builder;
            }
        );

        $builder->method('in')->willReturnCallback(
            static function (array $values) use (&$currentField, $builder, &$captures): Builder {
                $captures['in'][$currentField] = $values;

                return $builder;
            }
        );

        $builder->method('set')->willReturnCallback(
            static function (mixed $value) use (&$currentField, $builder, &$captures): Builder {
                $captures['set'][$currentField] = $value;

                return $builder;
            }
        );

        $builder->method('lt')->willReturnCallback(
            static function (mixed $value) use (&$currentField, $builder, &$captures): Builder {
                $captures['lt'][$currentField] = $value;

                return $builder;
            }
        );

        $builder->method('references')->willReturnCallback(
            static function (object $document) use (&$currentField, $builder, &$captures): Builder {
                $captures['references'][$currentField] = $document;

                return $builder;
            }
        );

        $builder->method('updateMany')->willReturnCallback(
            static function () use ($builder, &$captures): Builder {
                $captures['updateMany'] = true;

                return $builder;
            }
        );

        $builder->method('remove')->willReturnCallback(
            static function () use ($builder, &$captures): Builder {
                $captures['remove'] = true;

                return $builder;
            }
        );

        $query = $this->createMock(Query::class);
        $query->method('execute')->willReturn($result);
        $builder->method('getQuery')->willReturn($query);

        return $builder;
    }
}
