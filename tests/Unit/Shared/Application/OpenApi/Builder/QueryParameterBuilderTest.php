<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Builder;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Builder\AllowEmptyValue;
use App\Shared\Application\OpenApi\Builder\QueryParameterBuilder;
use App\Shared\Application\OpenApi\Builder\Requirement;
use App\Tests\Unit\UnitTestCase;

final class QueryParameterBuilderTest extends UnitTestCase
{
    private QueryParameterBuilder $builder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new QueryParameterBuilder();
    }

    public function testBuildWithValidData(): void
    {
        $name = $this->faker->name();
        $description = $this->faker->title();
        $requirement = Requirement::REQUIRED;
        $example = $this->faker->word();
        $type = $this->faker->word();

        $parameter = $this->builder->build(
            $name,
            $description,
            $requirement,
            $example,
            $type,
            1,
            AllowEmptyValue::DISALLOWED,
            ['value']
        );

        $this->assertInstanceOf(Parameter::class, $parameter);
        $this->assertEquals($name, $parameter->getName());
        $this->assertEquals('query', $parameter->getIn());
        $this->assertEquals($description, $parameter->getDescription());
        $this->assertTrue($parameter->getRequired());
        $this->assertEquals([
            'type' => $type,
            'minLength' => 1,
            'enum' => ['value'],
        ], $parameter->getSchema());
        $this->assertEquals($example, $parameter->getExample());
    }

    public function testBuildWithOptionalParameter(): void
    {
        $name = $this->faker->name();
        $description = $this->faker->title();
        $requirement = Requirement::OPTIONAL;
        $example = $this->faker->word();
        $type = $this->faker->word();

        $parameter = $this->builder->build(
            $name,
            $description,
            $requirement,
            $example,
            $type,
            null,
            AllowEmptyValue::ALLOWED,
            null
        );

        $this->assertFalse($parameter->getRequired());
        $this->assertTrue($parameter->canAllowEmptyValue());
    }

    public function testBuildOmitsNullSchemaAttributes(): void
    {
        $name = $this->faker->name();
        $description = $this->faker->sentence();
        $type = 'string';

        $parameter = $this->builder->build(
            $name,
            $description,
            Requirement::OPTIONAL,
            null,
            $type,
            null,
            AllowEmptyValue::DISALLOWED,
            null
        );

        $this->assertSame(
            ['type' => $type],
            $parameter->getSchema()
        );
    }

    public function testBuildDefaultsToDisallowEmptyValues(): void
    {
        $parameter = $this->builder->build(
            $this->faker->word(),
            $this->faker->sentence(),
            Requirement::REQUIRED,
            null,
            'string'
        );

        $this->assertFalse($parameter->canAllowEmptyValue());
    }
}
