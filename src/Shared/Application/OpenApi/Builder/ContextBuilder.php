<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

use App\Shared\Application\OpenApi\Factory\Schema\ParameterSchemaFactory;
use App\Shared\Application\OpenApi\ValueObject\Parameter;

use function array_combine;
use function array_filter;
use function array_map;
use function array_values;

use ArrayObject;

final readonly class ContextBuilder
{
    public function __construct(
        private ParameterSchemaFactory $parameterSchemaFactory
    ) {
    }

    /**
     * @param array<Parameter> $params
     */
    public function build(
        array $params,
        string $contentType = 'application/json'
    ): ArrayObject {
        if (count($params) === 0) {
            return new ArrayObject([
                $contentType => [
                    'example' => new ArrayObject(),
                ],
            ]);
        }

        return $this->processParams($params, $contentType);
    }

    /**
     * @param array<Parameter> $params
     *
     * @psalm-return ArrayObject<string, array<'example'|'schema', array<int|string, array<array<int|mixed|string>|bool|int|mixed|string>|bool|int|string>>>
     */
    private function processParams(
        array $params,
        string $contentType
    ): ArrayObject {
        $properties = $this->collectProperties($params);
        $example = $this->collectExamples($params);
        $required = $this->collectRequired($params);

        return $this->buildContent(
            $contentType,
            $properties,
            $example,
            $required
        );
    }

    /**
     * @param array<string, array<string, string|int>> $properties
     * @param array<string, string|int|array|bool> $example
     * @param array<int, string> $required
     *
     * @psalm-return ArrayObject<string, array<'example'|'schema', array<int|string, array<array<int|mixed|string>|bool|int|mixed|string>|bool|int|string>>>
     */
    private function buildContent(
        string $contentType,
        array $properties,
        array $example,
        array $required
    ): ArrayObject {
        return new ArrayObject([
            $contentType => array_filter(
                [
                    'schema' => array_filter(
                        [
                            'type' => 'object',
                            'properties' => $properties,
                            'required' => $this->emptyArrayToNull($required),
                        ],
                        static fn (mixed $value) => $value !== null
                    ),
                    'example' => $this->emptyArrayToNull($example),
                ],
                static fn (mixed $value) => $value !== null
            ),
        ]);
    }

    /**
     * @param array<Parameter> $params
     *
     * @return array<array<int|string|array<string>>>
     *
     * @psalm-return array<string, array<string, array<string, string>|int|string>>
     */
    private function collectProperties(array $params): array
    {
        $names = array_map(
            static fn (Parameter $parameter) => $parameter->name,
            $params
        );
        $schemas = array_map(
            fn (Parameter $parameter) => $this->parameterSchemaFactory
                ->create($parameter),
            $params
        );

        return array_combine($names, $schemas);
    }

    /**
     * @param array<Parameter> $params
     *
     * @return array<string, string|int|array|bool>
     */
    private function collectExamples(array $params): array
    {
        $names = array_map(
            static fn (Parameter $parameter) => $parameter->name,
            $params
        );
        $examples = array_map(
            static fn (Parameter $parameter) => $parameter->example,
            $params
        );

        $combined = array_combine($names, $examples);

        return array_filter(
            $combined,
            static fn (mixed $value) => $value !== null
        );
    }

    /**
     * @param array<Parameter> $params
     *
     * @return string[]
     *
     * @psalm-return list<string>
     */
    private function collectRequired(array $params): array
    {
        return array_values(
            array_map(
                static fn (Parameter $parameter) => $parameter->name,
                array_filter(
                    $params,
                    static fn (Parameter $parameter) => $parameter->isRequired()
                )
            )
        );
    }

    /**
     * @param array<int|string, array|string|int|bool> $values
     *
     * @return (array|bool|int|string)[]|null
     *
     * @psalm-return array<int|string, array|bool|int|string>|null
     */
    private function emptyArrayToNull(array $values): array|null|null
    {
        if ($values === []) {
            return null;
        }

        return $values;
    }
}
