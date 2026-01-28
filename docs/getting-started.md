Welcome to the User Service, a modern PHP microservice for user management, including registration and authentication. This guide will help you set up the service, configure it, and quickly get started with its basic functionalities.

## Installation Instructions

### Prerequisites

Before you begin, ensure you have the following installed on your system:

- Docker 25.0.3+
- Docker Compose 2.24.5+
- Git 2.34.1+

### CLI commands

As you will see, we use Make commands to manage the project. Run `make help` after setting up User Service to see a list of all available commands.

### Steps

1. **Clone the Repository**

   We recommend using Linux to set up this service.

   Then, start by cloning the repository to your local machine. Note, that the recommended way of doing it is using SSH. Check [this link](https://docs.github.com/en/authentication/connecting-to-github-with-ssh/adding-a-new-ssh-key-to-your-github-account) for more information.

   ```bash
   git clone git@github.com:VilnaCRM-Org/user-service.git
   cd user-service
   ```

2. **Configuration**

   Configuration is managed through environment variables. You can copy `.env` to `.env.local` and customize the environment variables for local development.
   Here's an example configuration:

   ```bash
   MONGODB_URL="mongodb://user:password@database:27017/db"
   REDIS_URL=redis://redis:6379/0
   MAILER_DSN=smtp://mailer:1025
   API_BASE_URL=https://localhost
   ```

3. **Start the project**

   Use the make command to start the project. It will up the container, install dependencies, and run migrations to the DB.

   ```bash
   make start
   ```

   **It will be better to wait a few minutes after this command executes, before moving further. You can run `make logs` to check the state of service**

   That's it! Now the service is ready for work.

4. **Quick start guide**

   Once the service runs, you can check these **local** URLs for a list of available endpoints and detailed info about them.

   [REST API docs](https://localhost/api/docs) (available when running locally)

   [GraphQL docs](https://localhost/api/graphql/graphql_playground) (available when running locally)

   You can also view the API specifications directly on GitHub:

   - [OpenAPI Specification](https://github.com/VilnaCRM-Org/user-service/blob/main/.github/openapi-spec/spec.yaml)
   - [GraphQL Specification](https://github.com/VilnaCRM-Org/user-service/blob/main/.github/graphql-spec/spec)

5. **FAQ**

   MongoDB is schemaless, so no migrations are needed. Document structures are defined in XML mappings in `config/doctrine/*.mongodb.xml`.

   If something goes wrong, try executing this sequence of commands:

   ```bash
   make cache-clear
   make install
   ```

Learn more about [Design and Architecture Documentation](design-and-architecture.md).
