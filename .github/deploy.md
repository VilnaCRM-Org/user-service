# Set up GitHub Action - Deploy

This GitHub Action builds the production Docker image from the `app_php`
Dockerfile stage, pushes both an immutable commit tag and the `latest` tag to
Amazon ECR, and requests an ECR image scan for the pushed commit tag.

## Workflow Triggers

The workflow runs when:

- A push is made to the `main` branch.
- A maintainer starts it manually with `workflow_dispatch`.

## Required GitHub Configuration

To set up this action, please add the following secrets to your repository under
**Settings** > **Secrets and variables** > **Actions**:

- `AWS_ROLE_TO_ASSUME`: The ARN of the AWS IAM role that GitHub Actions
  assumes through OpenID Connect (OIDC). Prefer OIDC over long-lived AWS access
  keys.
- `AWS_REGION`: The AWS region where the ECR repository exists, for example `us-east-1`.
- `ECR_REGISTRY`: The full Amazon ECR registry URL, for example
  `123456789012.dkr.ecr.us-east-1.amazonaws.com`.
- `ECR_REPOSITORY`: The ECR repository name where the user-service Docker image
  is pushed, for example `user-service`.

Configure the repository `production` environment with any required reviewers
before enabling automatic production pushes from `main`.

## AWS OIDC Setup

Create an IAM role that trusts GitHub's OIDC provider and restricts access to
this repository and branch. The role should follow least privilege and only allow
the ECR operations needed to authenticate, upload layers, and push images for the
configured repository.

Recommended role permissions include:

```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "ecr:BatchCheckLayerAvailability",
        "ecr:CompleteLayerUpload",
        "ecr:GetAuthorizationToken",
        "ecr:InitiateLayerUpload",
        "ecr:PutImage",
        "ecr:UploadLayerPart"
      ],
      "Resource": "*"
    }
  ]
}
```

`ecr:GetAuthorizationToken` requires `"Resource": "*"`. Scope the remaining ECR
permissions to the target repository ARN when creating the final production
policy.

## ECR Repository Setup

Create the repository before the first deployment if it does not already exist:

```bash
aws ecr create-repository \
  --repository-name user-service \
  --image-scanning-configuration scanOnPush=true \
  --encryption-configuration encryptionType=AES256
```

Configure a lifecycle policy so old commit-tagged images are cleaned up:

```bash
aws ecr put-lifecycle-policy \
  --repository-name user-service \
  --lifecycle-policy-text file://lifecycle-policy.json
```

## Image Tags

Each successful run pushes two tags:

- `${GITHUB_SHA::12}` for an immutable image tied to the deployed commit.
- `latest` for consumers that intentionally follow the newest production image.

Use the immutable commit tag for production rollbacks and audits.

## Troubleshooting

Authentication failures usually mean the OIDC trust policy,
`AWS_ROLE_TO_ASSUME`, or `AWS_REGION` is incorrect. Build failures usually mean
the Dockerfile `app_php` stage changed or the build context is incomplete. Push
failures usually mean the target ECR repository does not exist or the IAM role is
missing ECR permissions.
