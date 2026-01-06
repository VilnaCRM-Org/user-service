<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Schema;

use App\Shared\Application\OpenApi\Builder\Parameter;

final readonly class ParameterSchemaFactory
{
    public function __construct(
        private ArraySchemaFactory $arraySchemaFactory
    ) {
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
