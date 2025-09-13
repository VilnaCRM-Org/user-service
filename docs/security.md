This document outlines the security measures and best practices implemented in the User Service project. Our goal is to maintain the highest standards of security to protect our data and operations from unauthorized access and vulnerabilities.

## Security Best Practices

### Code Reviews

- **Peer Review:** All code changes are subject to peer review before being merged into the main branch. This ensures that more than one set of eyes has examined the logic and security implications of the changes.

### Code Analysis and Quality Checks
Our CI/CD pipeline incorporates several automated tools to ensure code quality and detect security vulnerabilities:

- **Psalm:** Psalm is integrated into your workflows to perform static code analysis. This tool is crucial for identifying potential errors and security risks in the code without executing it. By analyzing code statically, Psalm can detect issues like type mismatches, unnecessary condition checks, and more. The configuration in your pipeline not only checks for common issues but also includes a security analysis step, which specifically looks for security vulnerabilities. This means developers need to ensure their code is not only functionally correct but also adheres to security best practices to pass the CI checks
- **PHP Insights:** PHP Insights is another tool in your arsenal, focused on maintaining high code quality standards. It provides an instant PHP quality check, analyzing code for adherence to coding standards, architecture, complexity, and more. By integrating PHP Insights into your CI pipeline, you enforce a consistent code quality standard across the project. Developers are encouraged to write clean, maintainable, and efficient code to meet the criteria set by PHP Insights.

Both tools play a significant role in your development process:
- **Early Detection:** They help in the early detection of issues, which can significantly reduce the time and cost associated with fixing bugs in later stages of development.
- **Code Quality and Security:** By enforcing coding standards and detecting security vulnerabilities, they ensure that the codebase remains robust, secure, and easy to maintain.
- **Developer Education:** Continuous feedback from these tools educates developers about best practices in coding and security, gradually improving the overall quality of contributions.

### Dependency Management
We regularly update project dependencies to mitigate vulnerabilities in third-party packages:
* **Dependabot:** [Dependabot](https://github.com/dependabot) is a vital tool integrated into our GitHub repository to monitor the dependencies defined in `composer.json`, `Dockerfile`, and other configuration files for known vulnerabilities. When Dependabot detects a vulnerable dependency, it automatically opens a pull request to update the dependency to a secure version, and we fix it ASAP.
* **Snyk:** [Snyk](https://snyk.io/) is a leading security platform that helps ensure code security by identifying and fixing vulnerabilities in open-source dependencies and container images. It provides real-time scanning and alerts for vulnerabilities, allowing developers to address issues promptly to maintain the security of their applications. By integrating Snyk into CI/CD pipelines, teams can automate vulnerability checks and prioritize fixing warnings as soon as they arise, thereby minimizing the risk of security breaches.

### Testing
- Comprehensive **unit and integration tests** are run to ensure the security and functionality of our code.
- **End-to-end tests** verify the application's behavior from the user's perspective, ensuring that security controls are effective.
- **Load tests** are conducted to ensure the application can handle high traffic without compromising security.

### GitHub CI Security Checks

Our CI pipeline incorporates security checks to ensure the ongoing security of the application:
* **Symfony Security Check:** The Symfony security check is part of our CI pipeline, ensuring that our Symfony application does not have known vulnerabilities. This check is performed by the Symfony CLI tool, which scans the project's dependencies against the Symfony security advisories database. You can check the last execution result [here](https://github.com/VilnaCRM-Org/user-service/actions/workflows/symfony.yml?query=branch%3Amain).


## Reporting Security Vulnerabilities
- Please disclose any security issues or vulnerabilities found to the maintainers privately through the [GitHub security system](https://docs.github.com/en/code-security/security-advisories/guidance-on-reporting-and-writing/privately-reporting-a-security-vulnerability) and do not disclose them publicly until they have been addressed.

## Known Vulnerabilities

TBD

Learn more about [Performance and Optimization](performance.md).