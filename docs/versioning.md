This document outlines the approach to versioning and change management for the User Service. We aim to maintain a clear, consistent, and predictable process for the development and release of new features, ensuring stability and reliability for our users and contributors.

## Versioning Policy

Unlike many projects that use semantic versioning, the User Service does not assign version numbers to its API. Instead, we follow a **Deprecation Policy** to manage changes and ensure backward compatibility.

We have chosen not to use semantic versioning for the User Service for the following reasons:

- **Continuous Deployment**: Our development process involves frequent, iterative updates. Assigning version numbers to each update could lead to an excessive number of versions that are difficult to track and manage.
- **Backward Compatibility**: Our focus is on maintaining backward compatibility through a well-defined deprecation policy rather than versioning. This approach ensures that developers have ample time to adapt to changes without being impacted by sudden version upgrades.
- **Simplified Communication**: By avoiding the complexity of version numbers, we streamline our communication about updates and changes. Our users can focus on the functionality and stability of the API rather than keeping track of version numbers.

### Deprecation Policy

Our deprecation policy is designed to give developers ample notice of breaking changes and deprecated features:

- **Announcement**: Deprecated features and impending removals are announced in the project's release notes and documentation at least one major release cycle in advance.
- **Transition Period**: After announcing a deprecation, we provide a minimum transition period of 6 months (or longer, depending on the impact) during which the deprecated feature remains available.
- **Removal**: Details about the removal of deprecated features, including the final date or version, are communicated through the project's official channels.

## Changelog

To see a detailed history of all changes, updates, and improvements, check out [CHANGELOG.md](https://github.com/VilnaCRM-Org/user-service/blob/main/CHANGELOG.md).

Learn more about [Release Notes](release-notes.md).
