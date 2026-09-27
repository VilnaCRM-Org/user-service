# Issue #231: Defensive Domain Event Subscriber Decorator

## Problem

Domain event subscribers are isolated in async worker processing, but the
synchronous `InMemorySymfonyEventBus` still delegates directly to subscribers
through Symfony Messenger. A subscriber exception can therefore bubble through
the synchronous event chain and prevent later subscribers from handling the same
domain event.

Issue #231 asks for a try-catch decorator that catches subscriber failures,
logs them, and prevents one subscriber failure from breaking the whole chain.
Issue #228 describes the same defensive-programming requirement for all event
subscribers, so this implementation should satisfy both requirements once it
is merged.

## Goals

- Add a reusable defensive decorator for `DomainEventSubscriberInterface`
  implementations.
- Apply the decorator to every subscriber used by `InMemorySymfonyEventBus`.
- Preserve existing subscriber routing through `subscribedTo()`.
- Log subscriber failures without logging event payloads.
- Emit `EventSubscriberFailures` metrics for synchronous subscriber failures.
- Keep command handlers and non-subscriber message handlers unchanged.

## Non-Goals

- Do not change individual user event subscriber business logic.
- Do not alter async queue behavior; `DomainEventMessageHandler` already has
  equivalent resilience.
- Do not change domain event contracts or event payload serialization.
- Do not add new API behavior, persistence schema, or load-test scenarios.

## Proposed Design

Add `App\Shared\Infrastructure\Bus\Event\DefensiveEventSubscriberDecorator`
that implements `DomainEventSubscriberInterface` and wraps one inner domain
event subscriber.

The decorator delegates `subscribedTo()` directly to the inner subscriber.
Its `__invoke()` method calls the inner subscriber inside a try-catch block.
On failure, it logs contextual metadata and emits an
`EventSubscriberFailureMetric`; metric emission failure is also caught and
logged as a warning.

Update `InMemorySymfonyEventBus` so it decorates the tagged
`app.event_subscriber` iterator before building the Messenger bus. This keeps
existing `MessageBusFactory` routing intact because subscriber routing remains
based on `subscribedTo()`.

## Acceptance Mapping

- All subscribers use defensive programming:
  `InMemorySymfonyEventBus` wraps all tagged domain event subscribers before
  routing them.
- No unhandled exceptions bubble up from subscribers:
  the decorator catches `Throwable` from inner subscriber invocation.
- Adequate logging/reporting:
  failures are logged with subscriber class, event id, event type, event name,
  error message, and exception class; failure metrics are emitted.
- Tests pass:
  add focused unit coverage for the decorator and the synchronous event bus
  continuing after one subscriber fails.

## Validation Plan

- `make unit-tests`
- `make ci`
- `make ai-review-loop`

## Performance Impact

Runtime impact is minimal: synchronous event publishing adds one lightweight
decorator object per subscriber at bus construction time and one try-catch
boundary per subscriber invocation. No extra I/O occurs on successful
subscriber execution. On failures, logging and metric emission add the intended
observability work.
