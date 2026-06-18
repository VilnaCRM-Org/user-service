# Issue 116 Production Deployments Tech Spec

## BMAD Planning Trace

- Issue: #116 Add production deployments
- Planning method: BMAD planning-first workflow via BMALPH
- BMALPH check: `bmalph -C /home/kravtsov/Projects/user-service-pr117 status`
- BMALPH status at trace capture: Phase 1 - Analysis, Agent: Analyst, Status: planning
- Implementation PR: #117

## Problem

Production deployment currently requires manual Docker image build and push steps.
The service needs a repeatable GitHub Actions workflow that builds the production
Docker image and publishes it to Amazon ECR from the main branch.

## Scope

- Add a GitHub Actions workflow for production image build and push.
- Authenticate to AWS ECR using GitHub Secrets.
- Build the production Docker image for the user service.
- Tag and push the image to the configured ECR repository.
- Document the required deployment secrets.

## Out of Scope

- Creating or rotating AWS credentials.
- Creating the target ECR repository.
- Automatically merging or deploying unreviewed pull requests.

## Design

The deployment workflow lives in `.github/workflows/build-and-push.yml` and
uses GitHub-hosted CI to build the production image, authenticate through
`aws-actions/amazon-ecr-login`, tag the image, and push it to ECR.

The workflow depends on repository or environment secrets for AWS credentials,
region, account, and repository configuration. Secret creation is an operational
step and cannot be validated inside the pull request without live AWS access.

## Acceptance Criteria Mapping

- Workflow exists: `.github/workflows/build-and-push.yml`.
- ECR login is implemented with `aws-actions/amazon-ecr-login`.
- Image build/tag/push steps are present in the workflow.
- Required secrets are documented in `.github/deploy.md`.
- GitHub CI checks pass for the pull request.

## Verification Plan

- Validate workflow YAML through GitHub checks.
- Review `.github/deploy.md` for required secret names and operator setup.
- Confirm the PR remains mergeable and all required CI checks are green.
- After merge, validate the real ECR push through the main-branch workflow run
  because live AWS credentials are only available in the protected environment.
