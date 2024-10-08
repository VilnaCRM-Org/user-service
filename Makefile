# Load environment variables from .env.test
include .env.test

# Parameters
PROJECT       = php-service-template
GIT_AUTHOR    = Kravalg

# Executables: local only
SYMFONY_BIN   = symfony
DOCKER        = docker
DOCKER_COMPOSE   = docker compose

# Executables
EXEC_PHP      = $(DOCKER_COMPOSE) exec php
EXEC_WORKERS  = $(DOCKER_COMPOSE) exec workers
COMPOSER      = $(EXEC_PHP) composer
GIT           = git
EXEC_PHP_TEST_ENV = $(DOCKER_COMPOSE) exec -e APP_ENV=test php

# Alias
SYMFONY       = $(EXEC_PHP) bin/console
SYMFONY_BIN   = $(EXEC_PHP) symfony
SYMFONY_TEST_ENV = $(EXEC_PHP_TEST_ENV) bin/console

# Executables: vendors
PHPUNIT       = ./vendor/bin/phpunit
PSALM         = $(EXEC_PHP) ./vendor/bin/psalm
PHP_CS_FIXER  = ./vendor/bin/php-cs-fixer
DEPTRAC 	  = ./vendor/bin/deptrac
INFECTION 	  = ./vendor/bin/infection

# Misc
.DEFAULT_GOAL = help
.RECIPEPREFIX +=
.PHONY: $(filter-out vendor node_modules,$(MAKECMDGOALS))

# Variables

#Input
CLIENT_NAME ?= default_value

export SYMFONY

help:
	@printf "\033[33mUsage:\033[0m\n  make [target] [arg=\"val\"...]\n\n\033[33mTargets:\033[0m\n"
	@grep -E '^[-a-zA-Z0-9_\.\/]+:.*?## .*$$' Makefile | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%-15s\033[0m %s\n", $$1, $$2}'

phpcsfixer: ## A tool to automatically fix PHP Coding Standards issues
	$(DOCKER_COMPOSE) exec -e PHP_CS_FIXER_IGNORE_ENV=1 php ./vendor/bin/php-cs-fixer fix $(git ls-files -om --exclude-standard) --allow-risky=yes --config .php-cs-fixer.dist.php

composer-validate: ## The validate command validates a given composer.json and composer.lock
	$(COMPOSER) validate

check-requirements: ## Checks requirements for running Symfony and gives useful recommendations to optimize PHP for Symfony.
	$(SYMFONY_BIN) check:requirements

check-security: ## Checks security issues in project dependencies. Without arguments, it looks for a "composer.lock" file in the current directory. Pass it explicitly to check a specific "composer.lock" file.
	$(SYMFONY_BIN) security:check

psalm: ## A static analysis tool for finding errors in PHP applications
	$(PSALM)

psalm-security: ## Psalm security analysis
	$(PSALM) --taint-analysis

phpinsights: ## Instant PHP quality checks and static analysis tool
	$(EXEC_PHP) ./vendor/bin/phpinsights --no-interaction
	$(EXEC_PHP) ./vendor/bin/phpinsights analyse tests --no-interaction

ci-phpinsights:
	vendor/bin/phpinsights -n --ansi --format=github-action
	vendor/bin/phpinsights analyse tests -n --ansi --format=github-action

unit-tests: ## Run unit tests
	$(EXEC_PHP_TEST_ENV) ./vendor/bin/phpunit --testsuite=Unit

integration-tests: ## Run integration tests
	$(EXEC_PHP_TEST_ENV) ./vendor/bin/phpunit --testsuite=Integration

ci-tests:
	$(DOCKER_COMPOSE) exec -e XDEBUG_MODE=coverage -e APP_ENV=test php sh -c 'php -d memory_limit=-1 ./vendor/bin/phpunit --coverage-clover /coverage/coverage.xml'

e2e-tests: ## Run end-to-end tests
	$(EXEC_PHP_TEST_ENV) ./vendor/bin/behat

setup-test-db: ## Create database for testing purposes
	$(SYMFONY_TEST_ENV) c:c
	$(SYMFONY_TEST_ENV) doctrine:database:drop --force --if-exists
	$(SYMFONY_TEST_ENV) doctrine:database:create
	$(SYMFONY_TEST_ENV) doctrine:migrations:migrate --no-interaction

all-tests: unit-tests integration-tests e2e-tests ## Run unit, integration and e2e tests

smoke-load-tests: build-k6-docker ## Run load tests with minimal load
	tests/Load/load-tests-prepare-oauth-client.sh $$(jq -r '.endpoints.oauth.clientName' $(LOAD_TEST_CONFIG)) $$(jq -r '.endpoints.oauth.clientID' $(LOAD_TEST_CONFIG)) $$(jq -r '.endpoints.oauth.clientSecret' $(LOAD_TEST_CONFIG)) --redirect-uri=$$(jq -r '.endpoints.oauth.clientRedirectUri' $(LOAD_TEST_CONFIG))
	tests/Load/run-smoke-load-tests.sh

average-load-tests: build-k6-docker ## Run load tests with average load
	tests/Load/load-tests-prepare-oauth-client.sh $$(jq -r '.endpoints.oauth.clientName' $(LOAD_TEST_CONFIG)) $$(jq -r '.endpoints.oauth.clientID' $(LOAD_TEST_CONFIG)) $$(jq -r '.endpoints.oauth.clientSecret' $(LOAD_TEST_CONFIG)) --redirect-uri=$$(jq -r '.endpoints.oauth.clientRedirectUri' $(LOAD_TEST_CONFIG))
	tests/Load/run-average-load-tests.sh

stress-load-tests: build-k6-docker ## Run load tests with high load
	tests/Load/load-tests-prepare-oauth-client.sh $$(jq -r '.endpoints.oauth.clientName' $(LOAD_TEST_CONFIG)) $$(jq -r '.endpoints.oauth.clientID' $(LOAD_TEST_CONFIG)) $$(jq -r '.endpoints.oauth.clientSecret' $(LOAD_TEST_CONFIG)) --redirect-uri=$$(jq -r '.endpoints.oauth.clientRedirectUri' $(LOAD_TEST_CONFIG))
	tests/Load/run-stress-load-tests.sh

spike-load-tests: build-k6-docker ## Run load tests with a spike of extreme load
	tests/Load/load-tests-prepare-oauth-client.sh $$(jq -r '.endpoints.oauth.clientName' $(LOAD_TEST_CONFIG)) $$(jq -r '.endpoints.oauth.clientID' $(LOAD_TEST_CONFIG)) $$(jq -r '.endpoints.oauth.clientSecret' $(LOAD_TEST_CONFIG)) --redirect-uri=$$(jq -r '.endpoints.oauth.clientRedirectUri' $(LOAD_TEST_CONFIG))
	tests/Load/run-spike-load-tests.sh

load-tests: build-k6-docker ## Run load tests
	tests/Load/load-tests-prepare-oauth-client.sh $$(jq -r '.endpoints.oauth.clientName' $(LOAD_TEST_CONFIG)) $$(jq -r '.endpoints.oauth.clientID' $(LOAD_TEST_CONFIG)) $$(jq -r '.endpoints.oauth.clientSecret' $(LOAD_TEST_CONFIG)) --redirect-uri=$$(jq -r '.endpoints.oauth.clientRedirectUri' $(LOAD_TEST_CONFIG))
	tests/Load/run-load-tests.sh

execute-load-tests-script: build-k6-docker ## Execute single load test scenario.
	tests/Load/execute-load-test.sh $(scenario) $(or $(runSmoke),true) $(or $(runAverage),true) $(or $(runStress),true) $(or $(runSpike),true)

build-k6-docker:
	$(DOCKER) build -t k6 -f ./tests/Load/Dockerfile .

infection: ## Run mutation testing
	$(EXEC_PHP) sh -c 'php -d memory_limit=-1 ./vendor/bin/infection --test-framework-options="--testsuite=Unit" --show-mutations -j8'

ci-infection:
	$(INFECTION) --min-msi=100 --min-covered-msi=100 --test-framework-options="--testsuite=Unit" --show-mutations -j8

phpunit-codecov: ## Get code coverage report
	$(DOCKER_COMPOSE) exec -e XDEBUG_MODE=coverage php sh -c 'php -d memory_limit=-1 ./vendor/bin/phpunit --coverage-html coverage'

create-oauth-client: ## Run mutation testing
	$(EXEC_PHP) sh -c 'bin/console league:oauth2-server:create-client $(clientName)'

phpmetrics: ## Get mathematical metrics
	$(EXEC_PHP) ./vendor/bin/phpmetrics --report-html=metrics-report src

deptrac: ## Check directory structure
	$(DEPTRAC) analyse --config-file=deptrac.yaml --report-uncovered --fail-on-uncovered

deptrac-debug: ## Find files unassigned for Deptrac
	$(DEPTRAC) debug:unassigned --config-file=deptrac.yaml

doctrine-migrations-migrate: ## Executes a migration to a specified version or the latest available version
	$(SYMFONY) d:m:m -n

doctrine-migrations-generate: ## Generates a blank migration class
	$(SYMFONY) d:m:g

doctrine-migrations-create: ## Generates migrations from entities
	$(SYMFONY_BIN) console make:migration

cache-clear: ## Clears and warms up the application cache for a given environment and debug mode
	$(SYMFONY) c:c

first-release: ## Generate changelog from a project's commit messages for the first release
	$(EXEC_PHP) ./vendor/bin/conventional-changelog --first-release --commit --no-change-without-commits

changelog-generate: ## Generate changelog from a project's commit messages
	$(EXEC_PHP) ./vendor/bin/conventional-changelog

release: ## Generate changelogs and release notes from a project's commit messages for the first release
	$(EXEC_PHP) ./vendor/bin/conventional-changelog --commit --no-change-without-commits

release-patch: ## Generate changelogs and commit new patch tag from a project's commit messages
	$(EXEC_PHP) ./vendor/bin/conventional-changelog --patch --commit --no-change-without-commits

release-minor: ## Generate changelogs and commit new minor tag from a project's commit messages
	$(EXEC_PHP) ./vendor/bin/conventional-changelog --minor --commit --no-change-without-commits

release-major: ## Generate changelogs and commit new major tag from a project's commit messages
	$(EXEC_PHP) ./vendor/bin/conventional-changelog --major --commit --no-change-without-commits

install: composer.lock ## Install vendors according to the current composer.lock file
	@$(COMPOSER) install --no-progress --prefer-dist --optimize-autoloader

update: ## Update vendors according to the current composer.json file
	@$(COMPOSER) update --no-progress --prefer-dist --optimize-autoloader

cache-warmup: ## Warmup the Symfony cache
	@$(SYMFONY) cache:warmup

purge: ## Purge cache and logs
	@rm -rf var/cache/* var/logs/*

up: ## Start the docker hub (PHP, caddy)
	$(DOCKER_COMPOSE) up --detach

build: ## Builds the images (PHP, caddy)
	$(DOCKER_COMPOSE) build --pull --no-cache

down: ## Stop the docker hub
	$(DOCKER_COMPOSE) down --remove-orphans

sh: ## Log to the docker container
	@$(EXEC_PHP) sh

logs: ## Show all logs
	@$(DOCKER_COMPOSE) logs --follow

new-logs: ## Show live logs
	@$(DOCKER_COMPOSE) logs --tail=0 --follow

start: up doctrine-migrations-migrate## Start docker

stop: down ## Stop docker and the Symfony binary server

commands: ## List all Symfony commands
	@$(SYMFONY) list

load-fixtures: ## Build the DB, control the schema validity, load fixtures and check the migration status
	@$(SYMFONY) doctrine:cache:clear-metadata
	@$(SYMFONY) doctrine:database:create --if-not-exists
	@$(SYMFONY) d:f:l -n

coverage-html: ## Create the code coverage report with PHPUnit
	$(EXEC_PHP) php -d memory_limit=-1 vendor/bin/phpunit --coverage-html=var/coverage

coverage-xml: ## Create the code coverage report with PHPUnit
	$(EXEC_PHP) php -d memory_limit=-1 vendor/bin/phpunit --coverage-clover coverage.xml

generate-openapi-spec:
	$(EXEC_PHP) php bin/console api:openapi:export --yaml --output=.github/openapi-spec/spec.yaml

generate-graphql-spec:
	$(EXEC_PHP) php bin/console api:graphql:export --output=.github/graphql-spec/spec
