<?php

declare(strict_types=1);

namespace App\Tests\Unit\Shared\Application\OpenApi\Factory\UriParameter;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Builder\UriParameterBuilder;
use App\Shared\Application\OpenApi\Factory\UriParameter\UuidUriParameterFactory;
use App\Tests\Unit\UnitTestCase;

final class UuidUriParameterFactoryTest extends UnitTestCase
{
    private UuidUriParameterFactory $factory;
    private UriParameterBuilder $builderMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builderMock = $this->createMock(UriParameterBuilder::class);
        $this->factory = new UuidUriParameterFactory($this->builderMock);
    }

    public function testGetParameter(): void
    {
        $name = 'id';
        $description = 'User identifier';
        $example = '018dd6ba-e901-7a8c-b27d-65d122caca6b';
        $required = true;
        $type = 'string';
        $format = 'uuid';

        $enum = [$example];

        $this->setExpectations(
            $name,
            $description,
            $example,
            $required,
            $type,
            $format,
            $enum
        );

        $parameter = $this->factory->getParameter();

        $this->assertInstanceOf(Parameter::class, $parameter);
        $this->assertEquals($name, $parameter->getName());
        $this->assertEquals('path', $parameter->getIn());
        $this->assertEquals($description, $parameter->getDescription());
        $this->assertTrue($parameter->getRequired());
        $this->assertEquals(
            ['type' => $type, 'format' => $format, 'enum' => $enum],
            $parameter->getSchema()
        );
        $this->assertEquals($example, $parameter->getExample());
    }

    public function testGetParameterForCustomId(): void
    {
        $example = '018dd6ba-e901-7a8c-b27d-65d122caca6c';
        $this->builderMock->expects($this->once())
            ->method('build')
            ->with(
                'id',
                'User identifier',
                true,
                $example,
                'string',
                'uuid',
                [$example]
            )
            ->willReturn(
                new Parameter(
                    name: 'id',
                    in: 'path',
                    description: 'User identifier',
                    required: true,
                    schema: ['type' => 'string', 'format' => 'uuid', 'enum' => [$example]],
                    example: $example
                )
            );

        $parameter = $this->factory->getParameterFor($example);

        $this->assertSame($example, $parameter->getExample());
    }

    private function setExpectations(
        string $name,
        string $description,
        string $example,
        bool $required,
        string $type,
        string $format,
        array $enum
    ): void {
        $this->builderMock->expects($this->once())
            ->method('build')
            ->with(
                $name,
                $description,
                $required,
                $example,
                $type,
                $format,
                $enum
            )
            ->willReturn(
                new Parameter(
                    name: $name,
                    in: 'path',
                    description: $description,
                    required: true,
                    schema: ['type' => $type, 'format' => $format, 'enum' => $enum],
                    example: $example
                )
            );
    }
}
