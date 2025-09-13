This page provides an overview of the release history and significant updates made to the User Service. Each release note is a summary of new features, improvements, and bug fixes that have been implemented, providing users and developers with a clear understanding of the project's evolution.

## Changelog

Check out [CHANGELOG.md](https://github.com/VilnaCRM-Org/user-service/blob/main/CHANGELOG.md) to see a detailed history of all changes, updates, and improvements made to the user service codebase.

## Milestones

Check out [Milestones](https://github.com/VilnaCRM-Org/user-service/milestones), to see the progress and upcoming goals for our project.

## Automatic Releases

We have automated our release process using conventional commits and GitHub Actions. This integration helps maintain consistency in our commit messages, which in turn facilitates automatic versioning and changelog generation.

### Conventional Commits

The [conventional commits specification](https://www.conventionalcommits.org/en/v1.0.0/) provides a standardized way of writing commit messages. Each commit message includes a type and a description. This standardization allows tools to parse the commit history and generate release notes, version numbers, and changelogs automatically.

Types of Commits:
* **feat**: A new feature.
* **fix**: A bug or vulnerability fix.
* **refactor**: A code change that neither fixes a bug nor adds a feature, but improves the quality of existing code.

### GitHub Actions

GitHub Actions is a powerful automation platform that allows us to create custom workflows. We use GitHub Actions to automate the release process, triggered by the appropriate commit messages.

Release Workflow:
1. **Commit Parsing**: Once the commit is validated and pushed, [CaptainHook](http://captainhook.info/) parses the commit messages according to the conventional commit specification.
2. **Changelog Generation**: A new changelog entry is generated automatically from the commit messages via [this](https://github.com/VilnaCRM-Org/user-service/actions/workflows/autorelease.yml?query=branch%3Amain) GitHub action.
3. **Deployment to AWS**: The new version from the main branch is automatically deployed to our AWS services, ensuring that the latest updates are immediately available in our production environment.
4. **Notification**: Notifications can be sent to the team or community about the new release.

## Best Practices

- **Clear Communication**: All changes, especially deprecations and breaking changes, are communicated in the project's documentation and release notes.
- **Gradual Deprecation**: We aim to make transitions as smooth as possible by providing clear migration paths and ample notice for deprecated features.
- **Community Feedback**: We value community feedback and consider it when planning deprecations and introducing new features.
- **Consistent Commit Messages**: By adhering to the conventional commits specification, we ensure that commit messages are clear, consistent, and informative. This practice facilitates automated release processes and helps maintain a clean project history.

By adhering to these policies and practices, we strive to maintain a robust, stable, and forward-looking project that meets the needs of our users and contributors.