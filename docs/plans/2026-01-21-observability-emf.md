# Observability EMF Instrumentation Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Port the core-service observability skill and EMF-based business metrics into user-service, including resilient domain event handling and user-focused metrics.

**Architecture:** Add shared observability abstractions (metrics, dimensions, EMF payload factory/validator, emitter), wire a resilient async domain event bus on Messenger+SQS, and emit user domain metrics via dedicated subscribers/factories (no metrics in handlers). Keep observability failures non-breaking; log + emit failure metrics instead.

**Tech Stack:** PHP 8.2+, Symfony 7.3, Messenger (SQS), Monolog, AWS EMF (structured logs), PHPUnit.

### Task 1: Import observability skill docs

**Files:**
- Create: `.claude/skills/observability-instrumentation/SKILL.md`
- Create: `.claude/skills/observability-instrumentation/examples/*`
- Create: `.claude/skills/observability-instrumentation/reference/*`

**Step 1: Copy skill content from core-service PR #88 (or main) into the repo.**
- Command: `cp -r /tmp/tmp.oLLMLhYeDD/.claude/skills/observability-instrumentation .claude/skills/`
- Expected: skill folder exists locally.

**Step 2: Adapt examples to User context (UserRegistered, EmailChanged, PasswordResetRequested) while keeping EMF patterns identical.**
- Edit example metric/subscriber snippets to reference `App\User\...` endpoints/operations.

**Step 3: Sanity-check language for this service (API prefix, namespaces) and keep instructions aligned with repo rules (make commands, domain purity).**

### Task 2: Add shared EMF foundations

**Files:**
- Create: `src/Shared/Application/Observability/{Emitter,Factory,Metric,ValueObject}/...`
- Create: `src/Shared/Infrastructure/Observability/{Collection,Emitter,Factory,Formatter,Provider,Validator,ValueObject}/...`
- Modify: `src/Shared/Application/Validator/{EmfKey,EmfNamespace,EmfValue}.php`
- Modify: `src/Shared/Application/Validator/Guard/EmptyValueGuard.php`
- Modify: `translations/validators+intl-icu.en.yaml`
- Modify: `.env`, `.env.test` (add `AWS_EMF_NAMESPACE`)
- Modify: `config/services.yaml` (bind emitter/factories), `config/services_test.yaml` (test emitter spy)

**Step 1: Port metric value objects/collections, factories, emitter interfaces, and EMF payload/formatter/validator classes from core-service.**
- Ensure namespaces stay `App\Shared\...`; keep pure VO constructors validation in validators (not Domain layer).

**Step 2: Wire DI defaults.**
- Add EMF namespace parameter via env, register emitter, payload factory, validators, and validator constraints services.
- Test env: alias `BusinessMetricsEmitterInterface` to spy; keep cache overrides.

**Step 3: Update validation messages.**
- Add EMF constraint messages to `translations/validators+intl-icu.en.yaml`.

**Step 4: Quick self-check.**
- Command: `make unit-tests tests=tests/Unit/Shared/Infrastructure/Observability/Formatter/EmfLogFormatterTest.php` (after tests exist) expecting pass.

### Task 3: Resilient async domain event pipeline

**Files:**
- Create: `src/Shared/Application/Bus/Event/AsyncEventDispatcherInterface.php`
- Modify/Create: `src/Shared/Infrastructure/Bus/{CallableFirstParameterExtractor.php,InvokeParameterExtractor.php,MessageBusFactory.php}`
- Create: `src/Shared/Infrastructure/Bus/Event/Async/{DomainEventEnvelope.php,DomainEventMessageHandler.php,ResilientAsyncEventBus.php,ResilientAsyncEventDispatcher.php}`
- Create: `src/Shared/Application/Observability/Metric/{EventSubscriberFailureMetric.php,SqsDispatchFailureMetric.php}`
- Create: `src/Shared/Application/Observability/Factory/{EventSubscriberFailureMetricFactory.php,SqsDispatchFailureMetricFactory.php}`
- Modify: `config/packages/messenger.yaml` (domain-events + failed-domain-events transports, serializer)
- Modify: `config/services.yaml` (alias `EventBusInterface` to resilient bus, register handler/dispatcher/factories)
- Modify: `config/services_test.yaml` (alias EventBusInterface back to in-memory)
- Modify: `.env*` (SQS domain event DSNs if missing)

**Step 1: Port extractor/MessageBusFactory updates to support typed invoke parameter handling.**
- Ensure compatibility with existing command/event bus wiring.

**Step 2: Add async envelope/dispatcher/handler with failure metrics emission (do not let observability break request).**

**Step 3: Wire Messenger transports for domain events with retry/failure transport; keep tests using in-memory transports.**
- Command (verification later): `make unit-tests tests=tests/Unit/Shared/Infrastructure/Bus/Event/Async/DomainEventMessageHandlerTest.php`.

**Step 4: Ensure SQS/failed transport env vars exist with sane defaults (localstack).**

### Task 4: User-focused business metrics

**Files:**
- Create: `src/User/Application/Metric/{UsersRegisteredMetric.php,UsersUpdatedMetric.php,PasswordResetRequestedMetric.php}` (endpoint=User, operations create/update/request-password-reset)
- Create: `src/User/Application/Factory/{UsersRegisteredMetricFactory.php,...}` interfaces + impls as needed
- Create: `src/User/Application/EventSubscriber/{UserRegisteredMetricsSubscriber.php,UserUpdatedMetricsSubscriber.php,PasswordResetRequestedMetricsSubscriber.php}`
- Modify: `config/services.yaml` if any manual bindings required (factories optional if autowired)
- Modify: tests under `tests/Unit/User/Application/Metric/*` and `tests/Unit/User/Application/EventSubscriber/*`

**Step 1: Implement metrics extending `EndpointOperationBusinessMetric`; default value=1, MetricUnit::COUNT.**

**Step 2: Emit metrics from dedicated subscribers responding to domain events (no handler instrumentation).**
- Subscribers dispatch metric factories through `BusinessMetricsEmitterInterface`.

**Step 3: Add factories to encapsulate dimensions creation (inject `MetricDimensionsFactoryInterface`).**

**Step 4: Write PHPUnit unit tests for metrics (dimensions, name, unit) and subscribers (emits via spy).**
- Command: `make unit-tests tests=tests/Unit/User/Application/Metric/UsersRegisteredMetricTest.php`.

### Task 5: Failure metrics coverage

**Files:**
- Create tests for `EventSubscriberFailureMetric`, `SqsDispatchFailureMetric`, and emitter payload/validator collections (`tests/Unit/Shared/Observability/...` ported from core-service).

**Step 1: Port spy helpers (e.g., `BusinessMetricsEmitterSpy`, stub dimensions) into `tests/Unit/Shared/Infrastructure/Observability`.**

**Step 2: Ensure translated validation errors surface (assert message keys).**

### Task 6: Documentation and diagrams

**Files:**
- Modify: `docs/operational.md` or `docs/design-and-architecture.md` (brief note on EMF metrics + domain event async pipeline)
- Modify: `workspace.dsl` if architecture diagrams are maintained (add emitter/bus components similar to core-service). Skip if not present.

**Step 1: Add short operational note on AWS_EMF_NAMESPACE and log channel usage.**

### Task 7: Verification

**Step 1: Run targeted unit tests for observability + user metrics.**
- Command: `make unit-tests tests=tests/Unit/Shared/Infrastructure/Observability/Emitter/AwsEmfBusinessMetricsEmitterTest.php`

**Step 2: Run broader suite to ensure regressions caught.**
- Command: `make unit-tests`
- Command: `make integration-tests` (if time permits)

**Step 3: Re-run deptrac/phpinsights if observability introduces new deps.**
- Command: `make deptrac`
- Command: `make phpinsights`
