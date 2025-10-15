<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Builder;

final class ParameterSchemaFactory
{
    private ArraySchemaFactory $arraySchemaFactory;

    public function __construct(
        ?ArraySchemaFactory $arraySchemaFactory = null
    ) {
        $this->arraySchemaFactory = $arraySchemaFactory
            ?? new ArraySchemaFactory();
    }

    /**
     * @return array<string, string|int|array<string, string>>
     */
    public function create(Parameter $param): array
    {
        if ($param->type === 'array') {
            return $this->arraySchemaFactory->create($param);
        }

        return array_filter(
            [
                'type' => $param->type,
                'maxLength' => $param->maxLength,
                'format' => $param->format,
                'pattern' => $param->pattern,
                'enum' => $param->enum,
            ],
            static fn ($value) => $value !== null
        );
    }
}
