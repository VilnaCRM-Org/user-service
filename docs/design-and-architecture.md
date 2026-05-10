The User Service is architected around several foundational design principles, each contributing to the system's robustness, scalability, and maintainability. Below, we delve into how these principles are reflected in the codebase and folder structure.

## Domain-Driven Design (DDD)

DDD is an approach to software development that aims to align the implementation of complex systems with the domain they are intended to serve. It emphasizes collaboration between domain experts, software developers, and other stakeholders to iteratively refine the conceptual model of the domain and translate it into a well-designed software system.

### Dividing codebase into bounded contexts:

Dividing a codebase into bounded contexts allows for better organization and management of complexity in large systems. Each bounded context represents a specific area of the domain and encapsulates its models, language, and rules. This approach offers several benefits:

- **Clearer Understanding:** Bounded contexts provide clear boundaries for different parts of the system, making it easier for developers to understand and reason about each component independently.
- **Isolation of Complexity:** By isolating complexity within bounded contexts, changes and updates to one part of the system are less likely to have unintended consequences on other parts.
- **Scalability:** Bounded contexts can be developed, deployed, and scaled independently, allowing teams to work autonomously and efficiently.

Currently, we have 3 bounded contexts in User Service:

1. **Shared**: Provides foundational support across the service.
2. **OAuth**: Designed to manage OAuth entities and related processes.
3. **User**: Encompasses the user management system, including authentication and confirmation functionalities.

### Having a predictable structure for each bounded context:

In DDD, each bounded context typically follows a predictable structure consisting of three main layers: Domain, Application, and Infrastructure.

#### Domain Layer:

The Domain Layer encapsulates the core business logic and rules of the bounded context. It represents the heart of the application and contains domain entities, value objects, and aggregates. The purpose of the Domain Layer is to model and implement the concepts, behaviors, and relationships relevant to the specific domain it serves. By focusing on the domain logic, this layer ensures that the software system accurately reflects the problem domain and enforces the business rules effectively.

#### Application Layer:

The Application Layer sits between the Domain Layer and the external interfaces of the system. It orchestrates interactions between the domain objects and the outside world, handling requests, executing use cases, and coordinating the flow of data and control. Within the Application Layer, application services define the use cases and business workflows of the bounded context. These services coordinate the execution of domain logic by interacting with domain objects and enforcing business rules. By separating application-specific concerns from the domain logic, the Application Layer promotes maintainability, testability, and flexibility in the system.

#### Infrastructure Layer:

The Infrastructure Layer provides implementations for external concerns such as persistence, communication, and integration with external systems. It serves as the bridge between the application and the underlying infrastructure components, including databases, message queues, APIs, and third-party services. The Infrastructure Layer includes components such as repositories, data access objects, external service clients, and communication adapters. These components abstract away the details of interacting with external systems, allowing the application to focus on its core domain logic. By decoupling the application from its infrastructure dependencies, the Infrastructure Layer facilitates portability, scalability, and maintainability of the system.

By following this predictable structure for each bounded context, teams can maintain consistency and clarity throughout the codebase, making it easier to understand, modify, and extend the system over time.

## Hexagonal Architecture

Hexagonal Architecture, also known as Ports and Adapters Architecture, is a software design pattern that promotes loose coupling and separation of concerns in systems. In this architectural style, the core business logic of an application is encapsulated within the innermost layer, often referred to as the "hexagon" or "core." This core does not depend on any external systems or interfaces, making it highly testable and independent.

The key concept of Hexagonal Architecture is the distinction between the application's core business logic and its external interfaces, such as databases, user interfaces, or external services. These external interfaces are represented by ports, which define the contract or interface that the core logic interacts with. Adapters are then used to connect these ports to the external systems, translating between the core's interface and the specific technology used by the external system.

For User Service, it means, that the Domain layer in each bounded context, discussed above, stays independent from any external dependencies. For example, User Service can work with HTTP and GraphQL requests, but business logic stays the same, since it is encapsulated in the Domain layer, and does not depend on the type of request being made. Also, the way of storing data can be easily changed by introducing new Repositories in the Infrastructure layer, because the is no dependency on a specific way of saving data.

## Command Query Responsibility Segregation (CQRS)

CQRS is a design pattern that separates the responsibilities of handling commands (write operations) and queries (read operations) into distinct components. In a CQRS architecture, the system's data model is split into separate models optimized for reads and writes, allowing for more efficient data access and processing.

### How it's implemented

In User Service, CQRS allows us to encapsulate behavior and reuse it in different scenarios. Commands and Queries have all the needed data for instructions, that were encapsulated, to be completed. After being dispatched, they are automatically processed by corresponding handlers.

All interfaces related to Commands can be found in `Shared/Domain/Bus/Command`.

This is the list of currently available Commands, which can be found in `User/Application/Command` folder:

1. **ConfirmUserCommand**: Dispatched to confirm a user.
2. **RegisterUserCommand**: Dispatched to register a new user.
3. **RegisterUserBatchCommand**: Dispatched to register multiple users in batch.
4. **SendConfirmationEmailCommand**: Dispatched to send confirmation email.
5. **UpdateUserCommand**: Dispatched to update a user.
6. **RequestPasswordResetCommand**: Dispatched to request password reset.
7. **ConfirmPasswordResetCommand**: Dispatched to confirm password reset.
8. **SendPasswordResetEmailCommand**: Dispatched to send password reset email.

Also, you can find Handlers for these commands in `User/Application/CommandHandler`, and a Message Bus for them in `Shared/Infrastructure/Bus/Command`.

## Event-Driven Architecture

Event-driven architecture is a design pattern that emphasizes the use of events to trigger and communicate changes within a system. In an Event-Driven Architecture, components communicate asynchronously through the exchange of events, allowing for loosely coupled and scalable systems.

### How it's implemented

In User Service, we use Domain Events to have a flexible way to extend our system. Domain Events can be published from the Domain layer, or even from Command or Query handlers, and then consumed by any amount of Subscribers, which gives an opportunity to easily add new functionality.

All interfaces and Abstract classes related to Domain Events can be found in `Shared/Domain/Bus/Event`.

This is the list of currently available Domain Events, which can be found in `User/Domain/Event` folder:

1. **ConfirmationEmailSentEvent**: Published after the call of the `send()` function from the `ConfirmationEmail` aggregate.
2. **EmailChangedEvent**: Published after the user's email is changed.
3. **PasswordChangedEvent**: Published after the user's password is changed.
4. **PasswordResetEmailSentEvent**: Published after password reset email is sent.
5. **PasswordResetRequestedEvent**: Published after password reset is requested.
6. **PasswordResetConfirmedEvent**: Published after password reset is confirmed.
7. **UserConfirmedEvent**: Published after the user is confirmed.
8. **UserRegisteredEvent**: Published after the user is registered.

Also, you can find Subscribers for these events in `src/User/Application/EventSubscriber`, and a Message Bus for them in `Shared/Infrastructure/Bus/Event`.

## Observability

- Business metrics are emitted in AWS Embedded Metric Format (EMF) via `App\Shared\Application\Observability` value objects and the `AwsEmfBusinessMetricsEmitter` logger. Set the namespace with `AWS_EMF_NAMESPACE` (default `UserService/BusinessMetrics`).
- HTTP responses publish endpoint-level metrics through `ApiEndpointBusinessMetricsSubscriber`, which records `EndpointInvocations` with `Endpoint` and `Operation` dimensions.
- User domain events emit User-specific metrics: `UsersRegistered` (registration), `UsersUpdated` (email/password changes), and `PasswordResetRequests` (reset flow entry). Metrics live in `User/Application/Metric/*` with dedicated subscribers under `User/Application/EventSubscriber/*MetricsSubscriber`.
- Domain-event infrastructure now includes resilient async components (envelope, dispatcher, handler) and failure metrics for queue/subscriber issues; the default bus remains in-memory, keeping metrics best-effort and non-blocking.

## Architecture Diagram

This is the architecture diagram of User Service. When running the service locally, you can view interactive diagrams at [http://localhost:8080/workspace/diagrams](http://localhost:8080/workspace/diagrams).

Also, check [this link](https://structurizr.com/) to learn about the tool we used to create this diagram.

![structurizr-1-Components_All (1)](https://github.com/VilnaCRM-Org/user-service/assets/81823080/e4feb1bc-5549-4bff-90d4-d898a6de2ca9)

[Here](https://github.com/CodelyTV/php-ddd-example) you can check another implementation of the principles mentioned above.

Learn more about [User Guide](user-guide.md)
