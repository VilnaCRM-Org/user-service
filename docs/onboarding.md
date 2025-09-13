Welcome to **VilnaCRM**!

We’re excited to have you join us as a new back-end contributor. This document will help you set up your development environment, understand our project architecture and tech stack, and familiarize yourself with our coding standards and processes.

VilnaCRM is an open-source CRM system developed by a Ukrainian team to automate sales for small and medium-sized businesses. The landing page's primary audience includes business owners and sales managers, while the CRM system targets sales departments, accountants, IT specialists, and management teams.

This onboarding document aims to help new interns, trainees, and contributors quickly learn and contribute effectively to our backend codebase.

---

## Table of Contents

1. [Development Environment & Setup](#development-environment--setup)
2. [Project Architecture & Tech Stack](#project-architecture--tech-stack)
3. [Coding Standards & Best Practices](#coding-standards--best-practices)
4. [Testing & QA](#testing--qa)
5. [Continuous Integration & Deployment](#continuous-integration--deployment)
6. [Useful Resources & References](#useful-resources--references)
7. [Mentoring & Support](#mentoring--support)
8. [Next Steps](#next-steps)

---

## Development Environment & Setup

### Operating System Requirements
- **Ubuntu** or **macOS** (Unix-based, CI-tested)
- **Docker and docker compose** required
- **No Windows/WSL** or proprietary tools (not supported)

### IDE Recommendations
- We recommend using **PHPStorm** with AI Assistant or **Cursor AI**. If you are a student, you can obtain a student license for free.

Also, please install the [Coderabbit AI Plugin](https://www.coderabbit.ai/blog/www.coderabbit.ai/blog/ai-code-reviews-vscode-cursor-windsurf) into your IDE.

### Cloning Repositories & Installing Dependencies
1. Navigate to the [VilnaCRM User Service repository](https://github.com/VilnaCRM-Org/user-service).
2. Follow the “Minimal Installation” instructions in the repository’s README.
3. Once the repository is cloned locally, run service:
   ```bash
   make start
   ```

### Version Control Configuration
- We use **Git** with a standard **branching strategy** (e.g., feature branches, main/master as the production branch).
- Please adhere to our **commit message conventions** specified [in our CONTRIBUTING.md file](https://github.com/VilnaCRM-Org/user-service/blob/main/CONTRIBUTING.md).

---

## Project Architecture & Tech Stack

### Frameworks & Libraries
- We mainly use **Symfony** with **API Platform**.

### Folder Structure & Key Components
Our backend code is loosely based on the principles outlined in [php-ddd-example](https://github.com/CodelyTV/php-ddd-example).

### Code Style Approach
In general, we use **PSR-12**. Check the specific repository’s documentation.

### Modern PHP Stack
- **Symfony** + **API Platform** are our primary tools.
- We have **CI checks** to ensure high code quality (security checks, style fixing, static linters, DeepScan, Snyk, and more).
- Configured testing tools include **PHPUnit** and **Behat**.
- The service is based on [php service template](https://github.com/VilnaCRM-Org/php-service-template).

---

## Coding Standards & Best Practices

### Style Guidelines
- We use **PHP CS Fixer**, **PHPInsights**, **Psalm** and other tools. Always check CI rules and run:
  ```bash
    make phpcsfixer
    make psalm
    make phpinsights
  ```
  for instructions on how to fix code style automatically.

### Component Organization & Reusability
- Refer to patterns in [refactoring.guru](https://refactoring.guru). Reuse components whenever possible.

### State Management
- Currently, we are using different databases such as MariaDB and MongoDB.

### Security & Performance
- Always be mindful of **lazy loading**, **load testing**, and other performance optimizations.
- Handle sensitive data on the **server-side** where possible; limit client exposure.
- CI checks in GitHub will flag potential security issues.

---

## Testing & QA

### Testing Tools
- **PHPUnit** for unit tests.
- **Behat** for acceptance tests.
- **API Platform [ApiTestCase](https://api-platform.com/docs/symfony/testing/#writing-functional-tests)** for integration testing (where applicable).

### Best Practices for Writing Tests
- Keep tests **short**, **focused**, and **descriptive**.
- Use descriptive test names.
- Mock dependencies where necessary to keep tests deterministic.

### Running & Interpreting Tests
- Run your tests locally before pushing any changes:
  ```bash
  make all-tests
  make infection
  ```
- Check the terminal output or CI logs for errors or coverage summaries.

---

## Continuous Integration & Deployment

We use **GitHub Actions** with a variety of checks (17 CI checks, specifically), including:

1. **CLI testing / Bats Tests**
2. **GraphQL spec backward compatibility / Openapi-diff**
3. **Static analysis and fixers / lint**
4. **Unit and Integration testing / PHPUnit**
5. **Code coverage / codecov**
6. **Architecture static analysis tool / Deptrac**
7. **Static checks / symfony-checks**
8. **Code quality / PHP Insights checks**
9. **Static code analysis / Psalm**
10. **AI code review / Coderabbit**
11. **E2E testing / Behat**
12. **Load testing / K6**
13. **Mutation testing / Infection**
14. **REST API backward compatibility / openapi-diff**
15. **Security / Snyk**
16. **Static analysis for load tests / eslint**

Additionally, we use:
- **CodeRabbit** for code reviews and AI suggestions.
- **Snyk** for security scanning.

### Code Reviews & Approvals
- All PRs require approval from **@kravalg** and **coderabbit ai** before merging.

---

## Useful Resources & References

- **PHP Service Template**: [GitHub - php-service-template](https://github.com/VilnaCRM-Org/php-service-template)
- **Figma** (UI/UX designs): [VilnaCRM Figma Project](https://www.figma.com/design/cbyqPMtiPNJGIIQH9eKZ9Y/VilnaCRM?node-id=19-965&t=WaLtNPlYvfpvAIJz-1)
- **Architecture & Best Practices**: [php-ddd-example](https://github.com/CodelyTV/php-ddd-example)
- **User Flow**: [miro board](https://miro.com/app/board/uXjVPQ3J5kI=/)
- **C4 diagram**: [miro board](https://miro.com/app/board/uXjVPRE64mI=/)
- **User Service**: [GitHub - website](https://github.com/VilnaCRM-Org/user-service) and its [Wiki](https://github.com/VilnaCRM-Org/user-service/wiki)
- **Core Service**: [GitHub - core service](https://github.com/VilnaCRM-Org/core-service)
- **Pull Requests**: [How to work with Pull Requests](https://github.com/VilnaCRM-Org/docs/wiki/Pull-Requests,-CI-Pipelines,-and-Review-Process)

### Recommended Online Courses & Tutorials
- **Symfony Official Documentation**: [symfony](https://symfony.com/)
- **API Platform Official Documentation**: [api-platform.com](https://api-platform.com/)
- **Behat Official Documentation**: [behat.org](https://docs.behat.org/en/latest/)

### Coding Challenge Platforms
- [LeetCode](https://leetcode.com/)
- [HackerRank](https://www.hackerrank.com/)
- [Codewars](https://www.codewars.com/)

---

## Mentoring & Support

If you need help, reach out:
- **Slack** is our main communication channel. Join the **backend** chat for quick questions and collaboration.
- You can also schedule **1:1 sessions** with your assigned mentor or the Backend Lead.
- Key contact: **@kravalg** (Solutions Architect).

---

## Next Steps

Please read [day to day workflow](https://github.com/VilnaCRM-Org/docs/wiki/Daily-Work-Guide)

Congratulations on taking the first step in your VilnaCRM journey

We appreciate your time and effort to learn our processes, and we are here to support you.

Start exploring the repositories, experiment with small fixes or features, and don’t hesitate to ask questions in Slack.

**Thank you for joining the VilnaCRM team, and we look forward to building great software together!**