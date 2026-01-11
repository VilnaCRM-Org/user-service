<?php

declare(strict_types=1);

namespace App\Shared\Application\OpenApi\Factory\Endpoint;

use ApiPlatform\OpenApi\Model\Parameter;
use App\Shared\Application\OpenApi\Builder\QueryParameterBuilder;
use App\Shared\Application\OpenApi\Enum\AllowEmptyValue;
use App\Shared\Application\OpenApi\Enum\Requirement;

final class OAuthAuthorizeQueryParametersFactory
{
    private const QUERY_PARAMETER_DEFINITIONS = [
        [
            'name' => 'response_type',
            'title' => 'Response type',
            'required' => true,
            'example' => 'code',
            'choices' => ['code'],
        ],
        [
            'name' => 'client_id',
            'title' => 'Client ID',
            'required' => true,
            'example' => 'dc0bc6323f16fecd4224a3860ca894c5',
        ],
        [
            'name' => 'redirect_uri',
            'title' => 'Redirect uri',
            'required' => true,
            'example' => 'https://example.com',
        ],
        [
            'name' => 'scope',
            'title' => 'Scope',
            'required' => false,
            'example' => 'profile email',
        ],
        [
            'name' => 'state',
            'title' => 'State',
            'required' => false,
            'example' => 'af0ifjsldkj',
        ],
    ];

    public function __construct(private QueryParameterBuilder $builder)
    {
    }

    /**
     * @return array<int, Parameter>
     */
    public function create(): array
    {
        $parameters = [];

        foreach (self::QUERY_PARAMETER_DEFINITIONS as $definition) {
            $parameters[] = $this->builder->build(
                $definition['name'],
                $definition['title'],
                $definition['required'] ? Requirement::REQUIRED : Requirement::OPTIONAL,
                $definition['example'],
                'string',
                1,
                AllowEmptyValue::DISALLOWED,
                $definition['choices'] ?? null
            );
        }

        return $parameters;
    }
}
