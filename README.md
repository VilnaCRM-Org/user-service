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

- Modern PHP stack for services: [API Platform 4](https://api-platform.com/), PHP 8.3+, [Symfony 7](https://symfony.com/)
- [MongoDB](https://www.mongodb.com/) with [Doctrine MongoDB ODM](https://www.doctrine-project.org/projects/mongodb-odm.html) for flexible, schemaless data persistence
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

Go to browser and open the link below to access REST API docs

> https://localhost/api/docs

You can access the GraphQL endpoint via the link below:

> [GraphQL endpoint](https://localhost/api/graphql)

Also, you can see architecture diagram using link below

> http://localhost:8080/workspace/diagrams

That's it. You should now be ready to use user service!

### Local Coder Workspaces

This repository ships with a devcontainer setup that is intended to run inside a local Coder workspace.

When a workspace is created, the setup script:

- installs `codex` CLI
- installs `bmalph` CLI when it is not already available
- provides `gh` CLI
- installs `bats` CLI for `make bats`
- starts the Docker stack with `make start`
- installs PHP dependencies with `make install` if needed

After startup, verify the environment:

```bash
gh --version
codex --version
bmalph --version
make help
```

#### Secure setup for autonomous AI coding agents

Use workspace secrets (do not commit credentials):
The default devcontainer bind mounts look for host-side directories under `${HOME}/.openclaw-host-secrets` and `${HOME}/.openclaw-host-codex`; in local Coder this is typically `/home/coder/...`, and the bootstrap skips host secret or Codex auth sync when those sources are absent.

- `OPENAI_API_KEY`: OpenAI API key for Codex CLI
- `GH_AUTOMATION_TOKEN`: GitHub token for non-interactive `gh` usage
- bootstrap sets git identity for automated commits to:
  - `vilnacrm ai bot <info@vilnacrm.com>`

The workspace `post-create` step runs secure bootstrap automatically and only executes `scripts/local-coder/startup-smoke-tests.sh` when secure bootstrap succeeds and the required tools/auth are ready; otherwise it prints a skip message. You can also run scripts manually:

```bash
bash scripts/local-coder/setup-secure-agent-env.sh
bash scripts/local-coder/startup-smoke-tests.sh VilnaCRM-Org
bash scripts/local-coder/verify-gh-codex.sh VilnaCRM-Org
```

What `startup-smoke-tests.sh` checks:

- `gh` authentication is available
- repository listing for `VilnaCRM-Org` works
- `bats` CLI is available
- `bmalph` is installed and its Codex dry-run init succeeds
- `codex` can execute one non-interactive task

Repository-tracked defaults for GitHub and Codex bootstrap are stored in:

- `.devcontainer/workspace-settings.env`
- `.devcontainer/post-create.sh`
- `scripts/local-coder/setup-secure-agent-env.sh`

What `verify-gh-codex.sh` checks:

- GitHub auth works
- repository listing for `VilnaCRM-Org` works
- current PR checks can be queried via `gh`
- current branch supports `git push --dry-run`
- `bmalph` is installed and its Codex dry-run init succeeds
- `codex` can run a basic non-interactive smoke task
- tool-calling smoke checks are skipped by default and can be enforced when autonomous mode is explicitly enabled

#### BMALPH for Codex and Claude

The workspace bootstrap makes `bmalph` available for local agent workflows. You can also install or verify it manually from the repository root:

```bash
# Install and verify BMALPH for Codex
make bmalph-codex

# Install and verify BMALPH for Claude Code
make bmalph-claude

# Generic install target
make bmalph-install BMALPH_PLATFORM=codex
```

To preview how BMALPH would initialize this repository without changing tracked files, run:

```bash
make bmalph-init BMALPH_PLATFORM=codex BMALPH_DRY_RUN=true
make bmalph-init BMALPH_PLATFORM=claude-code BMALPH_DRY_RUN=true
```

To install and initialize BMALPH for the current project in one command, run:

```bash
make bmalph-setup
make bmalph-setup BMALPH_PLATFORM=claude-code
```

This repository keeps BMAD planning artifacts under `specs/` instead of the upstream `_bmad-output/planning-artifacts` default. `make bmalph-setup` rewrites the local `_bmad/config.yaml` so future planning runs land in `specs/`; rerun it after any direct `bmalph upgrade --force` if you need to restore the repo defaults.

For specs-only planning from a short feature description, use the `bmad-autonomous-planning` skill from your current AI agent session. The canonical workflow lives in `.claude/skills/bmad-autonomous-planning/SKILL.md`, and Codex can start from `.agents/skills/bmad-autonomous-planning/SKILL.md`.

`bmalph init` writes local BMAD/Ralph files such as `_bmad/` and `.ralph/`. Those generated directories are ignored in git for this repository, so use the dry-run first and initialize locally only when you want the tooling available in your workspace.

Codex uses the local login profile when available, or `OPENAI_API_KEY` from workspace secrets as fallback.
If you need autonomous tool execution in a workspace, set overrides before bootstrap:

```bash
export CODEX_TOOL_SMOKE_MODE=enforce
```

Use autonomous mode only in trusted environments.

Run Codex directly:

```bash
codex exec "Reply with exactly one line: codex-ok"
codex exec "Refactor user update flow to reduce duplication"
```

Notes:

- secrets are never stored in git; keep them in workspace secrets
- workspace secrets are provided directly to the container runtime
- bootstrap persists required credentials into `~/.config/user-service/agent-secrets.env` with `chmod 600` for future shell sessions in the same workspace
- no token values are written to repository files
- if you do not provide `GH_AUTOMATION_TOKEN`, run interactive login:
  `gh auth login -h github.com -w && gh auth setup-git`

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
create-oauth-client          Create OAuth client for testing
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
validate-configuration       Validate configuration structure and detect locked file modifications
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
