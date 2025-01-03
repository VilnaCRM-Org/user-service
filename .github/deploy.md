# Setup GitHub Action - Deploy

This GitHub Action triggers on pushes to the `main` branch, checks out the repository, logs in to Amazon ECR using stored AWS credentials, then builds the Docker image (using the `app_php` stage) and pushes it to the specified ECR repository with the `latest` tag.

**To setup this action please add the following secrets** to your repository under **Settings** → **Secrets and variables** → **Actions**:

**AWS_ACCESS_KEY_ID**  
A unique identifier associated with your AWS user account. It’s used, along with the secret access key, to authenticate against AWS services.

---

**AWS_SECRET_ACCESS_KEY**  
A confidential key paired with the access key ID. It must be kept secret and is used to sign programmatic requests to AWS services.

---

**AWS_REGION**  
The AWS region where your resources (ECR repository) reside (for example, `us-east-1`).

---

**ECR_REGISTRY**  
The full URL to your Amazon ECR registry (for example, `123456789012.dkr.ecr.us-east-1.amazonaws.com`).

---

**ECR_REPOSITORY**  
The specific name of the repository in ECR where your Docker images should be pushed (for example, `my-php-app`).
