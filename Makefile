# Parameters
PROJECT	= frontend-ssr-template
K6 = $(DOCKER) run -v ./src/test/load:/loadTests --net=host --rm k6 run --summary-trend-stats="avg,min,med,max,p(95),p(99)"

# Executables: local only
PNPM_BIN		= pnpm
DOCKER			= docker
DOCKER_COMPOSE	= docker compose

# Executables
EXEC_NODEJS	= $(DOCKER_COMPOSE) exec nodejs
PNPM      	= $(EXEC_NODEJS) pnpm
PNPM_RUN    = $(PNPM) run
GIT         = git

# Misc
.DEFAULT_GOAL = help
.RECIPEPREFIX +=
.PHONY: $(filter-out node_modules,$(MAKECMDGOALS))

# Variables
REPORT_FILENAME ?= default_value

help:
	@printf "\033[33mUsage:\033[0m\n  make [target] [arg=\"val\"...]\n\n\033[33mTargets:\033[0m\n"
	@grep -E '^[-a-zA-Z0-9_\.\/]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%-15s\033[0m %s\n", $$1, $$2}'

build: ## A tool build the project
	$(PNPM_RUN) build

lint-next: ## This command executes ESLint
	$(PNPM_RUN) lint:next

lint-tsc: ## This command executes Typescript linter
	$(PNPM_RUN) lint:tsc

lint-md: ## This command executes Markdown linter
	$(PNPM_RUN) lint:markdown

git-hooks-install: ## Install git hooks
	$(PNPM_RUN) prepare

storybook-start: ## Start Storybook UI. Storybook is a frontend workshop for building UI components and pages in isolation.
	$(PNPM_RUN) storybook

storybook-build: ## Build Storybook UI. Storybook is a frontend workshop for building UI components and pages in isolation.
	$(PNPM_RUN) build-storybook

generate-ts-doc: ## This command generates documentation from the typescript files.
	$(PNPM_RUN) doc

test-e2e: ## This command executes cypress tests.
	$(PNPM_RUN) test:e2e

test-e2e-local: ## This command opens management UI for cypress tests.
	$(PNPM_RUN) test:e2e:local

test-unit: ## This command executes unit tests using Jest library.
	$(PNPM_RUN) test:unit

test-memory-leak: ## This command executes memory leaks tests using Memlab library.
	$(PNPM_RUN) test:memory-leak

lighthouse-desktop: ## This command executes lighthouse tests for desktop.
	$(PNPM_RUN) lighthouse:desktop

lighthouse-mobile: ## This command executes lighthouse tests for mobile.
	$(PNPM_RUN) lighthouse:mobile

install: ## Install node modules according to the current pnpm-lock.yaml file
	$(PNPM) install

update: ## Update node modules according to the current package.json file
	$(PNPM) update

up: ## Start the docker hub (Nodejs)
	$(DOCKER_COMPOSE) up -d

down: ## Stop the docker hub
	$(DOCKER_COMPOSE) down --remove-orphans

sh: ## Log to the docker container
	@$(EXEC_NODEJS) sh

ps: ## Log to the docker container
	@$(DOCKER_COMPOSE) ps

logs: ## Show all logs
	@$(DOCKER_COMPOSE) logs --follow

new-logs: ## Show live logs
	@$(DOCKER_COMPOSE) logs --tail=0 --follow

start: up ## Start docker

stop: ## Stop docker
	$(DOCKER_COMPOSE) stop

build-k6-docker:
	$(DOCKER) build -t k6 -f ./src/test/load/Dockerfile .

load-tests: build-k6-docker
	$(K6) --out 'web-dashboard=period=1s&export=/loadTests/results/homepage.html' /loadTests/homepage.js
