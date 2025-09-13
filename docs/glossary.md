This glossary page aims to explain the naming conventions used for classes within the Domain layer of the `user-service` project. Understanding these conventions will help contributors and developers navigate and contribute to the project more effectively.

## General Naming Conventions

- **Aggregate**: Suffix is used to denote classes aggregated in the domain-driven design context. Aggregates are clusters of domain objects that can be treated as a single unit. Example: `ConfirmationEmailAggregate`.

- **Entity**: Indicates classes that have a distinct identity that runs through time and different states. Entities within our domain model are named to reflect their role in the business domain. Example: `User`.

- **Event**: Used for classes that represent something that happened in the domain. These classes are named after the domain event they represent. Example: `UserRegisteredEvent`.

- **Exception**: Prefix or suffix indicating classes that define specific domain exceptions. These exceptions are named based on the domain rule violation they represent. Example: `UserNotFoundException`.

- **Factory**: Denotes classes responsible for creating instances of entities or aggregates. Factories abstract the instantiation logic. Example: `UserFactory`.

- **Interface**: Prefix or suffix used to name interfaces, indicating a contract that classes must adhere to. Interfaces are named based on the role they play in the domain. Example: `UserRepositoryInterface`.

- **Repository**: Suffix indicating classes that provide a collection-like interface for accessing domain objects. Repositories abstract the underlying storage mechanism. Example: `TokenRepository`.

- **ValueObject**: Indicates classes that represent descriptive aspects of the domain with no conceptual identity. Value Objects are named based on what they describe. Example: `UserUpdate`.

- **Processor**: Suffix for classes that process requests, typically by taking a DTO as input, performing operations, and returning a response. Example: `RegisterUserProcessor`.

- **Resolver**: Suffix for classes that resolve GraphQL mutations or queries. They are part of the API layer that directly interacts with the GraphQL framework. Example: `UserMutationResolver`.

- **Transformer**: Suffix for classes that transform one type of object into another, often used for converting domain objects into DTOs or vice versa. Example: `UserToDTOTransformer`.

- **Command**: Suffix used to denote classes that represent an action or operation to be performed. Commands are simple DTOs that carry the data necessary for the action. Example: `RegisterUserCommand`.

- **DTO**: Suffix for classes that transfer data between processes or layers of the application. They are often used as input for commands. Example: `UserRegistrationDTO`.

## Ubiquitous Language

In software development, a shared vocabulary known as the "ubiquitous language" ensures clear communication between technical and non-technical stakeholders. It streamlines collaboration, minimizes misunderstandings, and aligns software solutions with business needs.

Here is a breakdown of the meaning of our classes from the Domain layer:

### Aggregate

- **ConfirmationEmail:** represents the process and data involved in sending a confirmation email to a user.

### Entity

- **ConfirmationToken:** represents a token to confirm a user's email address.

- **User:** represents a user in the system.

### Event

- **ConfirmationEmailSentEvent**: Published after the call of the `send()` function from the `ConfirmationEmail` aggregate.
- **EmailChangedEvent**: Published after the user's email is changed.
- **PasswordChangedEvent**: Published after the user's password is changed.
- **UserConfirmedEvent**: Published after the user is confirmed.
- **UserRegisteredEvent**: Published after the user is registered.

### Exception

- **InvalidPasswordException:** Thown, when the old password is invalid during user update.
- **TokenNotFoundException:** Thown, when Confirmation Token does not exist or has been expired.
- **UserNotFoundException:** Thown, when User was not found.
- **UserTimedOutException:** Thown, when User was timed out for too many confirmation attempts.

### ValueObject

- **UserUpdate:** Used to transfer user update data among different layers.

This glossary will be updated as the project evolves. If you encounter a term or class naming convention you believe should be included here, please [open an issue](https://github.com/vilnacrm-org/user-service/issues/new) to suggest it.

Learn more about [Versioning and Change Management](versioning.md).