[![SWUbanner](https://raw.githubusercontent.com/vshymanskyy/StandWithUkraine/main/banner2-direct.svg)](https://supportukrainenow.org/)

# User Service

[![CodeScene Code Health](https://img.shields.io/badge/CodeScene%20%7C%20Hotspot%20Code%20Health-9.7-brightgreen)](https://codescene.io/projects/55791)
[![CodeScene System Mastery](https://img.shields.io/badge/CodeScene%20%7C%20Average%20Code%20Health-9.8-brightgreen)](https://codescene.io/projects/55791)
[![codecov](https://codecov.io/gh/VilnaCRM-Org/user-service/branch/1-implement-user-registration/graph/badge.svg?token=FgXtmFulVd)](https://codecov.io/gh/VilnaCRM-Org/user-service)
![PHPInsights code](https://img.shields.io/badge/PHPInsights%20%7C%20Code%20-100.0%25-success.svg)
![PHPInsights style](https://img.shields.io/badge/PHPInsights%20%7C%20Style%20-100.0%25-success.svg)
![PHPInsights complexity](https://img.shields.io/badge/PHPInsights%20%7C%20Complexity%20-98.9%25-success.svg)
![PHPInsights architecture](https://img.shields.io/badge/PHPInsights%20%7C%20Architecture%20-100.0%25-success.svg)
[![Maintainability](https://api.codeclimate.com/v1/badges/b69a1d3fcff78ca1f9d9/maintainability)](https://codeclimate.com/github/VilnaCRM-Org/user-service/maintainability)

## Possibilities

- Modern PHP stack for services: [API Platform 3](https://api-platform.com/), PHP 8, [Symfony 7](https://symfony.com/)
- [Hexagonal Architecture, DDD & CQRS in PHP](https://github.com/CodelyTV/php-ddd-example)
- Built-in docker environment and convenient `make` cli command
- A lot of CI checks to ensure the highest code quality that can be ([Psalm](https://psalm.dev/), [PHPInsights](https://phpinsights.com/), Security checks, Code style fixer)
- Configured testing tools: [PHPUnit](https://phpunit.de/), [Behat](https://docs.behat.org/)
- Much more!

## Why you might need it

The User Service is designed to manage user accounts and authentication within the VilnaCRM ecosystem. It provides essential functionalities such as user registration and authentication, implemented with OAuth Server, REST API, and GraphQL, ensuring seamless integration with other components of the CRM system.

## License

This software is distributed under the [Creative Commons Zero v1.0 Universal](https://creativecommons.org/publicdomain/zero/1.0/deed) license. Please read [LICENSE](https://github.com/VilnaCRM-Org/user-service/blob/main/LICENSE) for information on the software availability and distribution.

### Minimal installation

You can clone this repository locally or use Github functionality "Use this template"

Install the latest [docker](https://docs.docker.com/engine/install/) and [docker compose](https://docs.docker.com/compose/install/)

Use `make` command to set up project

> make start

Use `make` command to automatically install all needed dependencies

> make install

Use `make` command to run migrations

> make doctrine-migrations-migrate

Go to browser and open the link below to access REST API docs

> https://localhost/api/docs

And using the link below you can access the GraphQL documentation

> [GraphQL endpoint](https://localhost/api/graphql)

Also, you can see architecture diagram using link below

> http://localhost:8080/workspace/diagrams

That's it. You should now be ready to use user service!

## Using

You can use `make` command to easily control and work with project locally.

Execute `make` or `make help` to see the full list of project commands.

The list of the `make` possibilities:

```
all-tests                  Run unit, integration, and e2e tests
average-load-tests         Run load tests with average load
bats                         Bats is a TAP-compliant testing framework for Bash
behat                        A php framework for autotesting business expectations
build                        Builds the images (PHP, caddy)
cache-clear                  Clears and warms up the application cache for a given environment and debug mode
cache-warmup                 Warmup the Symfony cache
changelog-generate           Generate changelog from a project's commit messages
check-requirements           Checks requirements for running Symfony and gives useful recommendations to optimize PHP for Symfony.
check-security               Checks security issues in project dependencies. Without arguments, it looks for a "composer.lock" file in the current directory. Pass it explicitly to check a specific "composer.lock" file.
commands                     List all Symfony commands
composer-validate            The validate command validates a given composer.json and composer.lock
coverage                     Create the code coverage report with PHPUnit
create-oauth-client          Run mutation testing
doctrine-migrations-generate Generates a blank migration class
doctrine-migrations-migrate  Executes a migration to a specified version or the latest available version
e2e-tests                  Run end-to-end tests
down                         Stop the docker hub
infection                  Run mutation testing
install                      Install vendors according to the current composer.lock file
update                       update vendors according to the current composer.json file
load-fixtures                Build the DB, control the schema validity, load fixtures and check the migration status
logs                         Show all logs
new-logs                     Show live logs
phpcsfixer                   A tool to automatically fix PHP Coding Standards issues
phpinsights                  Instant PHP quality checks and static analysis tool
phpunit                      The PHP unit testing framework
psalm                        A static analysis tool for finding errors in PHP applications
psalm-security               Psalm security analysis
purge                        Purge cache and logs
sh                           Log to the docker container
start                        Start docker
stop                         Stop docker and the Symfony binary server
smoke-load-tests           Run load tests with minimal load
spike-load-tests           Run load tests with a spike of extreme load
up                           Start the docker hub (PHP, caddy)
```

## Documentation

Start reading at the [GitHub wiki](https://github.com/VilnaCRM-Org/user-service/wiki). If you're having trouble, head for [the troubleshooting guide](https://github.com/VilnaCRM-Org/user-service/wiki/Community-and-Support) as it's frequently updated.

You can generate complete API-level documentation by running `phpdoc` in the top-level folder, and documentation will appear in the `docs` folder, though you'll need to have [PHPDocumentor](http://www.phpdoc.org) installed.

If the documentation doesn't cover what you need, search the [many questions on Stack Overflow](http://stackoverflow.com/questions/tagged/vilnacrm), and before you ask a question, [read the troubleshooting guide](https://github.com/VilnaCRM-Org/user-service/wiki/Community-and-Support).

## Tests

[Tests](https://github.com/VilnaCRM-Org/user-service/tree/main/tests/) use PHPUnit 9 and [Behat](https://github.com/Behat/Behat).

[Test status](https://github.com/VilnaCRM-Org/user-service/actions)

If this isn't passing, is there something you can do to help?

## Repository Synchronization with Template

We have integrated an automated repository synchronization feature using the [actions-template-sync](https://github.com/marketplace/actions/actions-template-sync) GitHub Action. This allows the repository to stay in sync with a designated template repository.

### How It Works

This workflow automatically creates a pull request in this repository whenever changes are detected in the template repository, ensuring that the latest updates from the template are applied.

By default, the workflow runs every Monday at 9:00 AM UTC. You can also manually trigger it from the [GitHub Actions tab](https://github.com/VilnaCRM-Org/user-service/actions).

### Configuration

1. The synchronization is managed through a GitHub Actions workflow, which is triggered automatically via cron or manually.
2. The `source_repo_path` must point to the repository you want to sync from (e.g., `VilnaCRM-Org/php-service-template`).
3. Make sure you have the necessary permissions set up for the GitHub token to allow synchronization. You can learn more about [configuring permissions for GitHub Actions tokens](https://docs.github.com/en/actions/security-for-github-actions/security-guides/automatic-token-authentication#modifying-the-permissions-for-the-github_token) in the official GitHub documentation.

You can see a sample configuration for the synchronization workflow, stored in [.github/workflows/template-sync.yml](https://github.com/VilnaCRM-Org/user-service/blob/main/.github/workflows/template-sync.yml).

### Benefits of Synchronization

Automated synchronization ensures that projects relying on this template always benefit from the latest features, improvements, and bug fixes without the need for manual intervention. This helps maintain consistency across multiple projects, reduces the likelihood of outdated code, and simplifies maintenance by automating the propagation of changes from the template.
In turn, it saves time and reduces the effort required to keep dependent projects aligned with best practices and new developments.

## Security

Please disclose any vulnerabilities found responsibly – report security issues to the maintainers privately.

See [SECURITY](https://github.com/VilnaCRM-Org/user-service/tree/main/SECURITY.md) and [Security advisories on GitHub](https://github.com/VilnaCRM-Org/user-service/security).

## Contributing

Please submit bug reports, suggestions, and pull requests to the [GitHub issue tracker](https://github.com/VilnaCRM-Org/user-service/issues).

We're particularly interested in fixing edge cases, expanding test coverage, and updating translations.

If you found a mistake in the docs, or want to add something, go ahead and amend the wiki – anyone can edit it.

## Sponsorship

Development time and resources for this repository are provided by [VilnaCRM](https://vilnacrm.com/), the free and opensource CRM system.

Donations are very welcome, whether in beer 🍺, T-shirts 👕, or cold, hard cash 💰. Sponsorship through GitHub is a simple and convenient way to say "thank you" to maintainers and contributors – just click the "Sponsor" button [on the project page](https://github.com/VilnaCRM-Org/user-service).

## Changelog

See [changelog](CHANGELOG.md).
