PROJECT	= frontend-ssr-template

PNPM_BIN		= pnpm
DOCKER			= docker
DOCKER_COMPOSE	= docker compose

EXEC_NODEJS	= $(DOCKER_COMPOSE) exec nodejs
PNPM      	= $(EXEC_NODEJS) pnpm
PNPM_RUN    = $(PNPM_RUN) run
GIT         = git

.DEFAULT_GOAL = help
.RECIPEPREFIX +=
.PHONY: $(filter-out node_modules,$(MAKECMDGOALS))

ARTILLERY_FILES := $(notdir $(shell find ${PWD}/tests/Load -type f -name '*.yml'))

help:
	@printf "\033[33mUsage:\033[0m\n  make [target] [arg=\"val\"...]\n\n\033[33mTargets:\033[0m\n"
	@grep -E '^[-a-zA-Z0-9_\.\/]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%-15s\033[0m %s\n", $$1, $$2}'

build: 
	$(PNPM_RUN) build

lint-next: 
	$(PNPM_RUN) lint:next

lint-tsc: 
	$(PNPM_RUN) lint:tsc

lint-md:
	$(PNPM_RUN) lint:markdown

git-hooks-install
	$(PNPM_RUN) prepare

storybook-start:
	$(PNPM_RUN) storybook

storybook-build: 
	$(PNPM_RUN) build-storybook

generate-ts-doc:
	$(PNPM_RUN) doc

test-e2e:
	$(PNPM_RUN) test:e2e

test-e2e-local:
	$(PNPM_RUN) test:e2e:local

test-unit: 
	$(PNPM_RUN) test:unit

test-memory-leak:
	$(PNPM_RUN) test:memory-leak

lighthouse-desktop:
	$(PNPM_RUN) lighthouse:desktop

lighthouse-mobile: 
	$(PNPM_RUN) lighthouse:mobile

install: 
	$(PNPM) install

update: 
	$(PNPM) update

up: 
	$(DOCKER_COMPOSE) up -d

build: 
	$(DOCKER_COMPOSE) build --pull --no-cache

down:
	$(DOCKER_COMPOSE) down --remove-orphans

sh: 
	@$(EXEC_NODEJS) sh

ps: 
	@$(DOCKER_COMPOSE) ps

logs: 
	@$(DOCKER_COMPOSE) logs --follow

new-logs: 
	@$(DOCKER_COMPOSE) logs --tail=0 --follow

start: up

stop: 
	$(DOCKER_COMPOSE) stop
