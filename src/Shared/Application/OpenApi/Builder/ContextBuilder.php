<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

final class ContextBuilder
{
    private ParameterSchemaFactory $parameterSchemaFactory;

    public function __construct(
        ?ParameterSchemaFactory $parameterSchemaFactory = null
    ) {
        $this->parameterSchemaFactory = $parameterSchemaFactory
            ?? new ParameterSchemaFactory();
    }

    /**
     * @param array<Parameter> $params
     */
    public function build(
        array $params,
        string $contentType = 'application/json'
    ): \ArrayObject {
        if (count($params) === 0) {
            return new \ArrayObject([
                $contentType => [
                    'example' => new \ArrayObject(),
                ],
            ]);
        }

        return $this->processParams($params, $contentType);
    }

    /**
     * @param array<Parameter> $params
     */
    private function processParams(
        array $params,
        string $contentType
    ): \ArrayObject {
        $properties = [];
        $example = [];
        $required = [];

        foreach ($params as $param) {
            if ($param->required) {
                $required[] = $param->name;
            }

            $properties[$param->name] = $this->parameterSchemaFactory
                ->create($param);
            $example[$param->name] = $param->example;
        }

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
     */
    private function buildContent(
        string $contentType,
        array $properties,
        array $example,
        array $required
    ): \ArrayObject {
        return new \ArrayObject([
            $contentType => [
                'schema' => array_filter(
                    [
                        'type' => 'object',
                        'properties' => $properties,
                        'required' => $required === [] ? null : $required,
                    ],
                    static fn ($value) => $value !== null
                ),
                'example' => $example,
            ],
        ]);
    }
}
