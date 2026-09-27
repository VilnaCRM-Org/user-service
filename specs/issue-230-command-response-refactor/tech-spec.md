# Issue 230 Command Response Refactor Tech Spec

## BMAD Planning Trace

- Issue: #230 Refactor command handlers to return response instead of setting on command object
- Planning method: BMAD planning-first workflow via BMALPH
- BMALPH check: `bmalph -C /home/kravtsov/Projects/user-service-issue230 status`
- BMALPH status at trace capture: Phase 1 - Analysis, Agent: Analyst, Status: planning
- Implementation PR: #285

## Problem

Command handlers currently mutate command objects by calling `setResponse()`.
That makes commands carry both request input and response state, which weakens
CQRS separation and forces processors, resolvers, and controllers to read mutable
state back from dispatched commands.

## Scope

- Make command handlers return typed response DTOs directly.
- Keep command objects as immutable or request-only input carriers.
- Update the command bus contract to return handler results.
- Add runtime guardrails for unsupported handler return values.
- Update processors, GraphQL resolvers, controllers, and tests to consume
  dispatch return values instead of command response state.

## Out of Scope

- Changing user-facing API contracts beyond preserving existing response shapes.
- Reworking unrelated query handlers or event subscriber behavior.
- Removing existing security and rate-limit behavior.

## Design

Command handlers return `CommandResponseInterface` implementations where a
response is expected. The Symfony Messenger adapter extracts the handled result
and returns it from `CommandBusInterface::dispatch()`. Application entry points
use `CommandResponseTypeGuard` to assert the exact expected response type before
building REST or GraphQL output.

The design keeps command DTOs focused on input data, moves response creation to
handler return values, and centralizes response-type validation so misuse fails
early in tests and runtime.

## Acceptance Criteria Mapping

- Command handlers return response DTOs instead of mutating commands.
- Processors, resolvers, and controllers consume dispatch return values.
- Command bus contract supports returning handler results.
- Invalid or missing command responses are guarded.
- Existing authentication, registration, password-reset, two-factor, and recovery
  code flows keep their public response shapes.

## Verification Plan

- Focused PHPUnit coverage for command handlers, command bus, guards, processors,
  resolvers, and controllers.
- Psalm static analysis.
- PHP CS Fixer dry-run.
- `git diff --check`.
- GitHub CI checks for the pull request.
