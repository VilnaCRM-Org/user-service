<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

final class ContextBuilder
{
    /**
     * @param array<Parameter> $params
     */
    public function build(array $params): \ArrayObject
    {
        $content = new \ArrayObject([
            'application/json' => [
                'example' => '',
            ],
        ]);

        if (count($params) > 0) {
            $properties = [];
            $example = [];
            $required = [];

            foreach ($params as $param) {
                if ($param->required) {
                    $required[] = $param->name;
                }
                $this->addParameterToProperties($properties, $param);
                $example[$param->name] = $param->example;
            }

            $content = $this->buildContent($properties, $example, $required);
        }

        return $content;
    }

    /**
     * @param array<string, string> $properties
     */
    private function addParameterToProperties(
        array &$properties,
        Parameter $param
    ): void {
        $properties[$param->name] = [
            'type' => $param->type,
            'maxLength' => $param->maxLength,
            'format' => $param->format,
        ];
    }

    /**
     * @param array<string, string> $properties
     * @param array<string, string|int|array> $example
     * @param array<string> $required
     */
    private function buildContent(
        array $properties,
        array $example,
        array $required
    ): \ArrayObject {
        return new \ArrayObject([
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => $properties,
                    'required' => $required,
                ],
                'example' => $example,
            ],
        ]);
    }
}
