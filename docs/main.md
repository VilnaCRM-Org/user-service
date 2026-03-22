## What Is User Service?

The VilnaCRM User Service is designed to manage user accounts and authentication within the VilnaCRM ecosystem. It provides essential functionalities such as user registration and authentication, implemented with OAuth Server, REST API, and GraphQL, ensuring seamless integration with other components of the CRM system.

## What Is User Service For?

User Service is a fully functional microservice designed to manage user registration and authentication within a modern PHP application ecosystem. It is a critical component in any system requiring user management capabilities, providing a secure and scalable solution for handling user data and authentication processes.

This service is particularly beneficial for applications that need to:

- **Register new users**: Offering a streamlined and customizable registration process.
- **Authenticate users**: Securely managing user logins with multiple OAuth grants.
- **Integrate with other services**: Easily connecting with other microservices or external systems for a cohesive ecosystem.

By leveraging the User Service, developers and organizations can significantly reduce the time and effort required to implement robust user management functionality, allowing them to focus on developing the unique features of their applications.

## What Design Principles Underlie User Service?

UserService is built on several key design principles:

- **Hexagonal Architecture**: Ensures the separation of concerns by isolating the application's core logic from external influences.
- **Domain-Driven Design**: Focuses on the core domain logic, making the system more understandable and flexible.
- **CQRS**: Separates the read and write operations to improve performance, scalability, and security.
- **Modern PHP Stack**: Utilizes the latest PHP features and best practices to ensure a high-quality, maintainable codebase.
- **Event-Driven Architecture**: Utilizes an event-driven approach to handle user actions, making the system highly responsive and scalable.

## What Problem Does User Service Solve?

- **Modern PHP Stack Integration**: By providing a template that leverages a modern PHP stack, the user service aims to streamline the development of PHP services, ensuring they are built on a solid, up-to-date foundation.
- **Built-in Docker Environment**: The challenge of setting up consistent development environments across different machines is solved by providing a Docker-based setup. This ensures that service can be developed, tested, and deployed with the same configurations, reducing "works on my machine" problems.
- **Convenient Make CLI Commands**: The service solves the problem of remembering and managing multiple commands for different tasks by providing a `make` command interface. This simplifies the process of building, running, and managing the application.

## Key Features

- **User Registration**: Facilitates adding new users to the application, including validation and confirmation workflows.
- **Authentication**: Provides robust mechanisms for user authentication, ensuring secure access control.
- **Flexibility**: User Service functionality implemented with REST API and GraphQL to provide a versatile platform for interacting with it.
- **Localization**: User Service supports both English and Ukrainian languages.

## Code Quality

To maintain a high standard of code quality, User Service includes:

- Static analysis tools, such as PHPInsights and Psalm to help developers ensure code quality, identify potential issues, and enforce best practices in a project.
- Testing tools like PHPUnit and Behat for comprehensive test coverage and robustness of a PHP application.
- Mutation testing tools represented by Infection, to ensure the quality of tests.
- Load testing tools represented by K6, to ensure optimal performance.
- Continuous Integration (CI) checks.

## Architecture Diagram

![structurizr-1-Components_All (1)](https://github.com/VilnaCRM-Org/user-service/assets/81823080/e4feb1bc-5549-4bff-90d4-d898a6de2ca9)

Learn more about [Getting Started](getting-started.md).
