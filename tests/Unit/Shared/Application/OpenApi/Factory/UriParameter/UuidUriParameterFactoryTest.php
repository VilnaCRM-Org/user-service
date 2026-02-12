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

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->builderMock = $this->createMock(UriParameterBuilder::class);
        $this->factory = new UuidUriParameterFactory($this->builderMock);
    }

    public function testGetParameter(): void
    {
        $testData = $this->createDefaultTestData();
        $this->setExpectations(
            $testData['name'],
            $testData['description'],
            $testData['example'],
            $testData['required'],
            $testData['type'],
            $testData['format'],
            $testData['enum']
        );

        $parameter = $this->factory->getParameter();

        $this->assertParameterMatchesExpectations($parameter, $testData);
    }

    public function testGetParameterForCustomId(): void
    {
        $example = '018dd6ba-e901-7a8c-b27d-65d122caca6c';
        $this->setupCustomIdExpectation($example);

        $parameter = $this->factory->getParameterFor($example);

        $this->assertSame($example, $parameter->getExample());
    }

    /**
     * @return (string|string[]|true)[]
     *
     * @psalm-return array{name: 'id', description: 'User identifier', example: '018dd6ba-e901-7a8c-b27d-65d122caca6b', required: true, type: 'string', format: 'uuid', enum: list{'018dd6ba-e901-7a8c-b27d-65d122caca6b'}}
     */
    private function createDefaultTestData(): array
    {
        $example = '018dd6ba-e901-7a8c-b27d-65d122caca6b';

        return [
            'name' => 'id',
            'description' => 'User identifier',
            'example' => $example,
            'required' => true,
            'type' => 'string',
            'format' => 'uuid',
            'enum' => [$example],
        ];
    }

    /**
     * @param array{
     *     name: string,
     *     description: string,
     *     example: string,
     *     type: string,
     *     format: string,
     *     enum: array<int, string>
     * } $testData
     */
    private function assertParameterMatchesExpectations(Parameter $parameter, array $testData): void
    {
        $this->assertInstanceOf(Parameter::class, $parameter);
        $this->assertEquals($testData['name'], $parameter->getName());
        $this->assertEquals('path', $parameter->getIn());
        $this->assertEquals($testData['description'], $parameter->getDescription());
        $this->assertTrue($parameter->getRequired());
        $this->assertEquals(
            [
                'type' => $testData['type'],
                'format' => $testData['format'],
                'enum' => $testData['enum'],
            ],
            $parameter->getSchema()
        );
        $this->assertEquals($testData['example'], $parameter->getExample());
    }

    private function setupCustomIdExpectation(string $example): void
    {
        $this->builderMock->expects($this->once())
            ->method('build')
            ->with('id', 'User identifier', true, $example, 'string', 'uuid', [$example])
            ->willReturn(
                $this->createParameter(
                    'id',
                    'User identifier',
                    $example,
                    'string',
                    'uuid',
                    [$example]
                )
            );
    }

    /**
     * @param array<int, string> $enum
     */
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
            ->with($name, $description, $required, $example, $type, $format, $enum)
            ->willReturn(
                $this->createParameter($name, $description, $example, $type, $format, $enum)
            );
    }

    /**
     * @param array<int, string> $enum
     */
    private function createParameter(
        string $name,
        string $description,
        string $example,
        string $type,
        string $format,
        array $enum
    ): Parameter {
        return new Parameter(
            name: $name,
            in: 'path',
            description: $description,
            required: true,
            schema: ['type' => $type, 'format' => $format, 'enum' => $enum],
            example: $example
        );
    }
}
