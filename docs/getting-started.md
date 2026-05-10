Welcome to the User Service, a modern PHP microservice for user management, including registration and authentication. This guide will help you set up the service, configure it, and quickly get started with its basic functionalities.

## Installation Instructions

### Prerequisites

Before you begin, ensure you have the following installed on your system:

- Docker 25.0.3+
- Docker Compose 2.24.5+
- Git 2.34.1+

If you prefer a workspace-managed environment, use the included devcontainer setup in `.devcontainer/` and skip local prerequisite installation.

### CLI commands

As you will see, we use Make commands to manage the project. Run `make help` after setting up User Service to see a list of all available commands.

### Steps

1. **Clone the Repository**

   We recommend using Linux to set up this service.

   Then, start by cloning the repository to your local machine. Note, that the recommended way of doing it is using SSH. Check [this link](https://docs.github.com/en/authentication/connecting-to-github-with-ssh/adding-a-new-ssh-key-to-your-github-account) for more information.

   ```bash
   git clone git@github.com:VilnaCRM-Org/user-service.git
   cd user-service
   ```

2. **Configuration**

   Configuration is managed through environment variables. You can copy `.env` to `.env.local` and customize the environment variables for local development.
   Here's an example configuration:

   ```bash
   MONGODB_URL="mongodb://user:password@database:27017/db"
   REDIS_URL=redis://redis:6379/0
   MAILER_DSN=smtp://mailer:1025
   API_BASE_URL=https://localhost
   ```

3. **Start the project**

   Use the make command to start the project. It will up the container, install dependencies, and run migrations to the DB.

   ```bash
   make start
   ```

   **It will be better to wait a few minutes after this command executes, before moving further. You can run `make logs` to check the state of service**

   That's it! Now the service is ready for work.

4. **Quick start guide**

   Once the service runs, you can check these **local** URLs for a list of available endpoints and detailed info about them.

   [REST API docs](https://localhost/api/docs) (available when running locally)

   [GraphQL docs](https://localhost/api/graphql/graphql_playground) (available when running locally)

   You can also view the API specifications directly on GitHub:

   - [OpenAPI Specification](https://github.com/VilnaCRM-Org/user-service/blob/main/.github/openapi-spec/spec.yaml)
   - [GraphQL Specification](https://github.com/VilnaCRM-Org/user-service/blob/main/.github/graphql-spec/spec)

5. **FAQ**

   MongoDB is schemaless, so no migrations are needed. Document structures are defined in XML mappings in `config/doctrine/*.mongodb.xml`.

   If something goes wrong, try executing this sequence of commands:

   ```bash
   make cache-clear
   make install
   ```

Learn more about [Design and Architecture Documentation](design-and-architecture.md).

## Local Coder Workspace Setup

This repository includes a ready-to-use devcontainer environment in `.devcontainer/devcontainer.json` for local Coder workspaces.

### What you get in a local workspace

- Docker support so all existing `make` commands continue to work
- GitHub CLI (`gh`)
- Codex CLI (`codex`)
- BMALPH CLI (`bmalph`)
- Bats CLI (`bats`) for `make bats`
- Automatic bootstrap on create:
  - secure agent bootstrap (`scripts/local-coder/setup-secure-agent-env.sh`)
  - `make start`
  - `make install` (when `vendor/autoload.php` is missing)

### How to start

1. Create or start the local Coder workspace for this repository.
2. Wait for the post-create setup to finish.
3. Verify tools:

```bash
gh --version
codex --version
bmalph --version
make help
```

For autonomous AI coding in local workspaces, set workspace secrets:

- `OPENAI_API_KEY`
- `GH_AUTOMATION_TOKEN`
- bootstrap sets git identity for automated commits to `vilnacrm ai bot <info@vilnacrm.com>`

The default devcontainer bind mounts look for host-side directories under `${HOME}/.openclaw-host-secrets` and `${HOME}/.openclaw-host-codex`; in local Coder that usually resolves to `/home/coder/...`, and the bootstrap skips host secret or Codex auth sync when those sources are absent.

These secrets are provided directly to the container runtime, so `gh`, `git`, and `codex` can use them in normal terminal sessions.
The bootstrap persists required credentials into `~/.config/user-service/agent-secrets.env` with `chmod 600` so future login shells in the same workspace keep the same auth state.

Non-secret defaults for GitHub CLI and Codex are persisted in git:

- `.devcontainer/workspace-settings.env`
- `.devcontainer/post-create.sh`
- `scripts/local-coder/setup-secure-agent-env.sh`

If you prefer manual authentication inside the workspace:

```bash
gh auth login -h github.com -w
gh auth setup-git
```

Then run:

```bash
make bmalph-codex
bash scripts/local-coder/startup-smoke-tests.sh VilnaCRM-Org
bash scripts/local-coder/verify-gh-codex.sh VilnaCRM-Org
```

`startup-smoke-tests.sh` runs the default startup checks:

- `gh` is authenticated
- org repository listing works
- `bats` CLI is available
- `bmalph` is installed and its Codex dry-run init succeeds
- `codex` can execute one non-interactive task

`verify-gh-codex.sh` includes the Codex basic smoke check by default.
Tool-calling smoke checks are skipped by default and only run when autonomous mode is explicitly enabled.
This setup uses Codex with:

- local login profile or `OPENAI_API_KEY`
- `CODEX_TOOL_SMOKE_MODE=skip` by default

If you need to adjust autonomous Codex tool checks in a workspace, set overrides before bootstrap:

```bash
export CODEX_TOOL_SMOKE_MODE=enforce
```

### BMALPH and BMAD planning

Use the repository make targets to install or verify BMALPH manually:

```bash
make bmalph-codex
make bmalph-claude
make bmalph-init BMALPH_PLATFORM=codex BMALPH_DRY_RUN=true
make bmalph-setup
```

This repository keeps BMAD planning artifacts under `specs/` instead of the upstream `_bmad-output/planning-artifacts` default. `make bmalph-setup` rewrites the local `_bmad/config.yaml` so planning runs use `specs/`; rerun it after any direct `bmalph upgrade --force` if you need to restore the repo defaults.

For autonomous specs-first planning from a short request, use the `bmad-autonomous-planning` skill in the current AI session. The canonical workflow lives in `.claude/skills/bmad-autonomous-planning/SKILL.md`, and Codex can start from `.agents/skills/bmad-autonomous-planning/SKILL.md`.

`bmalph init` writes local `_bmad/` and `.ralph/` assets into the workspace. Those directories are ignored in git here, so prefer the dry-run preview before enabling the local workflow files.

### Working in Local Coder Workspaces

All project operations remain the same as local usage:

```bash
make start
make install
make ci
```

Use the forwarded ports/features in your Coder client to access the service endpoints exposed by Docker Compose.
