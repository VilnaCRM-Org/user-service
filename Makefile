# Parameters
PROJECT	= frontend-ssr-template
K6 = $(DOCKER) run -v ./src/test/load:/loadTests --net=host --rm k6 run --summary-trend-stats="avg,min,med,max,p(95),p(99)"
K6_BIN = ./k6

# Executables: local only
PNPM_BIN		= pnpm
DOCKER			= docker
DOCKER_COMPOSE	= docker compose
MAKE 			= make

# Executables
EXEC_NODEJS	= $(DOCKER_COMPOSE) exec nodejs
PNPM      	= $(EXEC_NODEJS) pnpm
PNPM_RUN    = $(PNPM) run
GIT         = git

# CI variable
CI ?= 0

# Conditional PNPM_EXEC based on CI
ifeq ($(CI), 1)
    PNPM_EXEC = $(PNPM_BIN)
	LHCI_DESKTOP = lighthouse:desktop-autorun
	LHCI_MOBILE = lighthouse:mobile-autorun
	VISUAL_EXEC = $(PNPM_EXEC)
	LOAD_TESTS_RUN = $(K6_BIN) run --summary-trend-stats="avg,min,med,max,p(95),p(99)" --out "web-dashboard=period=1s&export=./src/test/load/results/index.html" ./src/test/load/homepage.js
	BUILD_K6_DOCKER =
else
    PNPM_EXEC = $(PNPM_RUN)
	LHCI_DESKTOP = lighthouse:desktop
	LHCI_MOBILE = lighthouse:mobile
	VISUAL_EXEC = $(DOCKER) exec website-playwright-1 pnpm run
	LOAD_TESTS_RUN = $(K6) --out 'web-dashboard=period=1s&export=/loadTests/results/homepage.html' /loadTests/homepage.js
	BUILD_K6_DOCKER = $(MAKE) build-k6-docker
endif

# To Run in CI mode specify CI variable. Example: make lint-md CI=1

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
	$(PNPM_EXEC) build

format: ## This command executes Prettier Formating
	$(PNPM_EXEC) format

lint-next: ## This command executes ESLint
	$(PNPM_EXEC) lint:next

lint-tsc: ## This command executes Typescript linter
	$(PNPM_EXEC) lint:tsc

lint-md: ## This command executes Markdown linter
	$(PNPM_EXEC) lint:md

git-hooks-install: ## Install git hooks
	$(PNPM_EXEC) prepare

storybook-start: ## Start Storybook UI. Storybook is a frontend workshop for building UI components and pages in isolation.
	$(PNPM_EXEC) storybook

storybook-build: ## Build Storybook UI. Storybook is a frontend workshop for building UI components and pages in isolation.
	$(PNPM_EXEC) build-storybook

generate-ts-doc: ## This command generates documentation from the typescript files.
	$(PNPM_EXEC) doc

test-e2e: ## This command executes cypress tests.
	$(PNPM_EXEC) test:e2e

test-e2e-local: ## This command opens management UI for cypress tests.
	$(PNPM_EXEC) test:e2e-local

test-visual: ## This command executes playwright visual tests.
	$(VISUAL_EXEC) test:visual
	
test-unit: ## This command executes unit tests using Jest library.
	$(PNPM_EXEC) test:unit

test-memory-leak: ## This command executes memory leaks tests using Memlab library.
	$(PNPM_EXEC) test:memory-leak

test-mutation:
	$(PNPM_EXEC) test:mutation

build-k6-docker: ## This command build K6 image
	$(DOCKER) build -t k6 -f ./src/test/load/Dockerfile .

load-tests: ## This command executes load tests using K6 library.
	$(BUILD_K6_DOCKER)
	$(LOAD_TESTS_RUN)

lighthouse-desktop: ## This command executes lighthouse tests for desktop.
	$(PNPM_EXEC) $(LHCI_DESKTOP)

lighthouse-mobile: ## This command executes lighthouse tests for mobile.
	$(PNPM_EXEC) $(LHCI_MOBILE)

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
