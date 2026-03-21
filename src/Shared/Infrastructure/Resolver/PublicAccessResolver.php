<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Resolver;

use Symfony\Component\HttpFoundation\Request;

/**
 * Determines whether a request targets a publicly-accessible route.
 *
 * This is the single source of truth for public-route patterns on the
 * PHP side.  Keep these patterns in sync with the `access_control` list
 * in config/packages/security.yaml.
 */
final readonly class PublicAccessResolver
{
    /**
     * @param array<int, array{pattern: string, methods?: list<string>}> $rules
     */
    public function __construct(
        private array $rules,
    ) {
    }

    public function isPublic(Request $request): bool
    {
        $path = $request->getPathInfo();
        $method = $request->getMethod();

        foreach ($this->rules as $rule) {
            if (preg_match($rule['pattern'], $path) !== 1) {
                continue;
            }

            $methods = $rule['methods'] ?? null;
            if (!is_array($methods) || $methods === []) {
                return true;
            }

            if (in_array($method, $methods, true)) {
                return true;
            }
        }

        return false;
    }
}
