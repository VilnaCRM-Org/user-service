Welcome to the developer guide for the User Service. This guide aims to provide you with all the necessary information to get started with development, including an overview of the code structure.

## Code Structure

The User Service repository is structured to support a modern PHP microservice architecture, utilizing Hexagonal Architecture and DDD principles.

There are 3 bounded contexts in User Service:

### Shared

The Shared context provides foundational support across the User Service application. It includes utilities and infrastructure components common to other contexts, ensuring consistency and reducing duplication.

- **Application:** This layer mainly consists of classes, responsible for handling cross-cutting concerns across the application, such as Validators and Exception Normalizers. Also, it has an OpenApi folder, which is responsible for building OpenAPI docs for the User Service, facilitating API discoverability and usability by generating detailed documentation for various API endpoints, request bodies, and response structures.

```bash
Shared/Application
├── ErrorHandling
├── OpenApi
├── Transformer
└── Validator
```

- **Domain:** This layer mainly consists of interfaces for classes in the Infrastructure layer, and abstract classes to be inherited in other bounded contexts. Also, it has entities, that can not be encapsulated in a specific bounded context.

```bash
Shared/Domain
├── Aggregate
├── Bus
│   ├── Command
│   └── Event
└── ValueObject
```

- **Infrastructure:** This layer mainly consists of services used to support the whole application infrastructure, such as Message Buses and utils for them. Also, some additional tools can be used for configuration, such as custom Retry Strategies for Message busses, or to override existing solutions, like custom database types.

```bash
Shared/Infrastructure
├── Bus
│   ├── Command
│   └── Event
├── Controller
├── DoctrineType
└── RetryStrategy
```

### User

The User context encapsulates all functionality related to user management within the service. It is comprehensive, covering aspects from user creation to authentication.

- **Application:** This layer consists of classes, responsible for handling requests, such as HTTP Request Processors and GraphQL Mutation resolvers, and classes, that encapsulate behavior, such as Command and Event Handlers.

```bash
User/Application
├── Command
├── CommandHandler
├── DTO
├── EventListener
├── EventSubscriber
├── Factory
├── InputValidation
├── Processor
├── Resolver
└── Transformer
```

- **Domain:** This layer consists of Entities, Value Objects, Aggregates, Domain Events, and Domain Exceptions, which represent everything related to business logic in the User bounded context.

```bash
User/Domain
├── Aggregate
├── Entity
├── Event
├── Exception
├── Factory
├── Repository
└── ValueObject
```

- **Infrastructure:** This layer consists of various Repositories for Entities from the Domain layer.

```bash
User/Infrastructure
├── Factory
└── Repository
```

### OAuth

This bounded context is very thin and contains only an empty entity, to map OpenApi docs on it, because the OAuth server is implemented using [this bundle](https://oauth2.thephpleague.com/).

### Deptrac

Deptrac is an architecture static analysis tool designed for PHP projects. It helps maintain the clean architecture of applications by ensuring that layers in the application adhere to predefined rules, preventing unwanted dependencies between them.

In the context of the User Service, Deptrac is configured to enforce architectural constraints across different parts of the application, ensuring a clean separation of concerns and adherence to the project's architectural design.

[Here](https://github.com/VilnaCRM-Org/user-service/blob/main/deptrac.yaml) you can find our Deptrac config, which will help you to see out code structure comprehensively.

Locally, you can run `make deptrac` command to see the result of the Deptrac execution, and [here](https://github.com/VilnaCRM-Org/user-service/actions/workflows/deptrac.yml?query=branch%3Amain) you can find results of last execution in GitHub CI.

Learn more about [Deptrac](https://qossmic.github.io/deptrac/), and how to [configure it](https://qossmic.github.io/deptrac/#configuration).

Learn more about [Operational Documentation](operational.md).
