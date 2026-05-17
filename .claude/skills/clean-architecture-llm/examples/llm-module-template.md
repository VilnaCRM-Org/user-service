# LLM Module Template

This template shows the shape of an LLM-backed feature without binding the
Application layer to a provider SDK. Adapt names to the specific bounded context
and use case.

## Directory Shape

```text
src/{Context}/
├── Application/
│   ├── DTO/
│   │   ├── {Capability}Request.php
│   │   └── {Capability}Response.php
│   ├── Factory/
│   │   └── {Capability}PromptFactory.php
│   ├── {PortDirectory}/
│   │   └── {Capability}EvaluatorInterface.php
│   └── CommandHandler/
│       └── {Action}{Entity}CommandHandler.php
└── Infrastructure/
    ├── Adapter/
    │   └── {Provider}{Capability}Evaluator.php
    ├── Factory/
    │   └── {Provider}RequestFactory.php
    └── Parser/
        └── {Provider}{Capability}ResponseParser.php
```

Use existing repository directories when they already express the class type.
If a new class-type directory is needed, follow `code-organization/SKILL.md` and
choose only standard pattern names such as `Adapter`, `Factory`, `Parser`,
`Strategy`, or `Decorator`.

In the snippets below, `{PortDirectory}` means the existing Application
class-type directory that best matches the port role, for example `Provider/`,
`Validator/`, or `Resolver/`. Do not create `Port/` unless the user explicitly
approves that new directory.

## Application Port

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\{PortDirectory};

use App\{Context}\Application\DTO\{Capability}Request;
use App\{Context}\Application\DTO\{Capability}Response;

interface {Capability}EvaluatorInterface
{
    public function evaluate({Capability}Request $request): {Capability}Response;
}
```

## Typed Request and Response

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\DTO;

final readonly class {Capability}Request
{
    public function __construct(
        public string $subjectId,
        public string $normalizedInput,
        public string $correlationId,
    ) {
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\DTO;

final readonly class {Capability}Response
{
    public function __construct(
        public bool $accepted,
        public string $reasonCode,
        public float $confidence,
    ) {
    }
}
```

## Prompt Factory

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\Factory;

use App\{Context}\Application\DTO\{Capability}Request;

final readonly class {Capability}PromptFactory
{
    public function create({Capability}Request $request): string
    {
        return sprintf(
            "Evaluate the normalized input for subject %s.\nInput:\n%s",
            $request->subjectId,
            $request->normalizedInput,
        );
    }
}
```

Keep prompt variables explicit. If prompt text grows large or needs versioning,
move stable text to a named template collaborator and keep the factory
responsible for binding typed variables.

## Infrastructure Adapter

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Infrastructure\Adapter;

use App\{Context}\Application\DTO\{Capability}Request;
use App\{Context}\Application\DTO\{Capability}Response;
use App\{Context}\Application\Factory\{Capability}PromptFactory;
use App\{Context}\Application\{PortDirectory}\{Capability}EvaluatorInterface;
use App\{Context}\Infrastructure\Factory\{Provider}RequestFactory;
use App\{Context}\Infrastructure\Parser\{Provider}{Capability}ResponseParser;

final readonly class {Provider}{Capability}Evaluator implements {Capability}EvaluatorInterface
{
    public function __construct(
        private {Provider}ClientInterface $client,
        private {Capability}PromptFactory $promptFactory,
        private {Provider}RequestFactory $requestFactory,
        private {Provider}{Capability}ResponseParser $responseParser,
    ) {
    }

    public function evaluate({Capability}Request $request): {Capability}Response
    {
        $prompt = $this->promptFactory->create($request);
        $providerRequest = $this->requestFactory->create($prompt, $request->correlationId);
        $providerResponse = $this->client->complete($providerRequest);

        return $this->responseParser->parse($providerResponse);
    }
}
```

The provider client, request factory, and parser belong in Infrastructure
because they know provider payloads and failure formats.

## Strategy Example

```php
<?php

declare(strict_types=1);

namespace App\{Context}\Application\Strategy;

use App\{Context}\Application\DTO\{Capability}Request;

interface {Capability}ModelSelectionStrategyInterface
{
    public function modelAliasFor({Capability}Request $request): string;
}
```

Use a strategy only when the model, prompt, or policy truly varies by use case,
tenant, risk level, or environment.

## Deterministic Test Double

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\{Context}\Double;

use App\{Context}\Application\DTO\{Capability}Request;
use App\{Context}\Application\DTO\{Capability}Response;
use App\{Context}\Application\{PortDirectory}\{Capability}EvaluatorInterface;

final readonly class InMemory{Capability}Evaluator implements {Capability}EvaluatorInterface
{
    public function __construct(private {Capability}Response $response)
    {
    }

    public function evaluate({Capability}Request $request): {Capability}Response
    {
        return $this->response;
    }
}
```

Default tests should inject deterministic doubles or fixtures. Do not call a
live model from unit tests, integration tests, or normal CI.

## Review Evidence

A PR that adds an LLM-backed feature should show:

- The Application port and typed DTOs.
- The Infrastructure adapter that implements the port.
- Prompt factory/template tests.
- Provider response parser tests.
- Failure-path tests for timeout, invalid response, unsafe response, and
  provider errors.
- Documentation of any prompt/model configuration that affects behavior.
