This document provides an overview of the testing strategies employed in the User Service. Our comprehensive testing approach ensures high-quality software delivery, encompassing unit tests, integration tests, end-to-end (E2E) tests, mutation tests, load tests, and more.

## Prerequisites

Before executing tests, run `make setup-test-db`. This command will create a separate database for testing purposes.

## Unit Testing

Unit tests are the foundation of our testing strategy, focusing on small, isolated parts of the application to ensure they behave as expected. We write unit tests for individual classes and methods, mocking dependencies to isolate the unit of work.

Our Unit and Integration test coverage is **100%**. You can check [this](https://github.com/VilnaCRM-Org/user-service/actions/workflows/tests.yml?query=branch%3Amain) GitHub CI workflow to see the results of the latest Unit and Integration test execution.

### Location:

Tests are organized under the `/tests/Unit` directory, with further categorization, similar to folders in `src`.

### Tools:

We primarily use PHPUnit for unit testing, along with mock objects for dependencies.

### Best Practices:

- **Test One Thing at a Time**: Each test should focus on a single behavior or aspect of the component under the test. This approach makes it easier to identify what is broken when a test fails and ensures that each test is only for one purpose.
- **Use Descriptive Test Names**: Test method names should clearly describe what they are testing. A well-named test can serve as documentation for what a piece of code is supposed to do. Descriptive names make it easier to understand the purpose of the test without having to dive into the implementation details.
- **Keep Tests Independent**: Tests should not rely on the state produced by other tests. Each test should set up its data and not depend on the order of test execution. This practice ensures that tests can be run in any order and that the outcome of a test is not affected by the preceding tests.
- **Mock External Dependencies**: Use mocks for any external service or database interaction to isolate the component under test. Mocking external dependencies allows us to test the internal logic of a component without worrying about the setup and behavior of external systems.

### Execution:

Run `make unit-tests` to execute unit tests.

## Integration Testing

Our Unit and Integration test coverage is **100%**. You can check [this](https://github.com/VilnaCRM-Org/user-service/actions/workflows/tests.yml?query=branch%3Amain) GitHub CI workflow to see the results of the latest Unit and Integration test execution.

Integration tests assess the interaction between different parts of the application, such as database access and external services, to ensure they work together as expected. These tests are crucial for identifying issues that may not be visible through unit testing alone.

### Location:

Integration tests are located in the `/tests/Integration` directory. This organization mirrors the structure of the `src` directory to make it easier to find the tests relevant to specific components or functionalities.

### Tools:

For integration testing, we use PHPUnit in conjunction with real database connections and external services. This approach allows us to test the application in an environment that closely resembles production.

### Best Practices:

- **Test Real Interactions**: Unlike unit tests, integration tests should use real instances of classes and services to ensure that their interactions are tested accurately.
- **Use Transactional Rollbacks**: To maintain a consistent state and avoid polluting the database, use transactional rollbacks after each test. This ensures that each test starts in a clean state.
- **Focus on Critical Paths**: Given the potentially large scope of integration testing, focus on critical paths through the application. This includes user registration, login flows, and data processing tasks.
- **Monitor Performance**: Integration tests can be slower than unit tests due to their reliance on external services and databases. Monitor performance to ensure that the test suite remains efficient and manageable.

### Execution:

Run `make integration-tests` to execute the integration tests. This command ensures that all dependencies are correctly set up and that the tests are run against the configured test database and external services.

## Mutation Testing

Mutation testing is a rigorous approach to testing that involves making small, deliberate modifications to our code (mutants) to verify that our tests can detect these changes. This method helps in evaluating the quality and effectiveness of our test suites.

We have **0** escaped and uncovered mutants in User Service. You can check [this](https://github.com/VilnaCRM-Org/user-service/actions/workflows/infection.yml?query=branch%3Amain) GitHub CI workflow to see the results of the latest Mutation test execution.

### Tools

We utilize **Infection**, a powerful PHP mutation testing framework. Infection automatically generates mutants by altering our codebase in small ways, then runs our test suite against each mutant. The goal is to have our tests fail for each mutant, indicating that our tests are effectively covering our code.

### Execution

Run `make infection`, to run mutation testing and see a comprehensive report about the quality of our test.

## Load Testing

Load testing is a critical aspect of our application's development lifecycle, designed to ensure that our application can handle expected traffic volumes and maintain performance under stress. This process helps us identify bottlenecks and optimize the application's scalability.

We test each available endpoint of our service with multiple loads. For each endpoint, we have **Smoke**, **Average**, **Stress**, and **Spike** load tests. You can check [this link](https://grafana.com/docs/k6/latest/testing-guides/test-types/) for more information about them.

Also, you can check [this](https://github.com/VilnaCRM-Org/user-service/actions/workflows/load-tests.yml?query=branch%3Amain) GitHub CI workflow to see the results of the latest Load test execution.

### Location

Our load testing scripts are organized within the `/tests/Load` directory. These scripts are crafted to simulate various realistic usage scenarios that our application might face in production. By doing so, we can accurately assess how our system behaves under different levels of demand.

### Tools

For scripting and executing our load tests, we rely on **k6**, a powerful and modern load-testing tool. k6 allows us to script complex user behavior and supports running these tests at scale. The scripts cover a wide range of API endpoints and user actions, ensuring comprehensive coverage of our application's functionality.

### Configuration

The 'config.json.dist' in the `/tests/Load` directory serves as an example of load tests configuration. You can copy `config.json.dist` to `config.json` and customize variables for local development.

There is a wide range of customizable options, starting with a global setting for all load test scripts. Here is an example of them:

```bash
    "apiHost":"localhost",
    "apiPort":"8081",
    "mailCatcherPort":"1080",
    "batchSize":5000,
    "delayBetweenScenarios":30,
    "gettingEmailMaxRetries":300,
    "usersFileLocation":"/loadTests/",
    "usersFileName":"users.json",
```

**Note:** Update `apiHost` with your actual domain when running load tests against production or staging environments.

Also, you can customize plenty of options for each separate script, and even for each load type. Here is an example:

```bash
    "getUser": {
            "setupTimeoutInMinutes": 10,
            "teardownTimeoutInMinutes":10,
            "smoke": {
                "threshold": 60,
                "rps": 10,
                "vus": 10,
                "duration": 10
            },
            "average": {
                "threshold": 200,
                "rps": 50,
                "vus": 50,
                "duration": {
                    "rise": 5,
                    "plateau": 20,
                    "fall": 5
                }
            },
            "stress": {
                "threshold": 2000,
                "rps": 300,
                "vus": 300,
                "duration": {
                    "rise": 5,
                    "plateau": 20,
                    "fall": 5
                }
            },
            "spike": {
                "threshold": 8000,
                "rps": 400,
                "vus": 400,
                "duration": {
                    "rise": 30,
                    "fall": 10
                }
            }
```

### Execution

Run `make load-tests` to execute load tests for each endpoint with all load scenarios. Also, you can test only specific load types by using one of the following commands:

```bash
    make smoke-load-tests
    make average-load-tests
    make stress-load-tests
    make spike-load-tests
```

To run only one load test, you can use the following command:

```bash
    make execute-load-tests-script scenario=<LOAD_TEST_NAME>
```

<LOAD_TEST_NAME> should be equal to a load test script from a `/tests/Load/scripts` folder without a `.js` suffix.

By default, all load types will be executed, but you can disable some of them by adding the following CLI options:

```bash
    runSmoke=false
    runAverage=false
    runStress=false
    runSpike=false
```

After the load test execution, you'll find `.html` reports in a `/tests/Load/loadTestsResults` folder.

## End-to-End (E2E) Testing

End-to-end (E2E) testing is a critical phase in the application development lifecycle, aimed at simulating real user scenarios to ensure the system meets external requirements and behaves as expected across the entire application. This type of testing validates the integrated components of the application in a production-like environment, from the user interface down to the database operations and network communications.

We cover each possible response with a separate test. You can check [this](https://github.com/VilnaCRM-Org/user-service/actions/workflows/E2Etests.yml?query=branch%3Amain) GitHub CI workflow to see the results of the latest E2E test execution.

### Location:

Testing scenarios, written in BDD style, are located in the `/features` folder.

Code, responsible for processing the scenarios, is located in the `/tests/Behat` folder, with further categorization for each scenario.

### Tools:

For E2E testing, our project utilizes **Behat**, a PHP framework for auto-testing your business expectations. Behat allows us to write tests in a human-readable format, using the Gherkin language, which makes it accessible not only to developers but also to non-technical stakeholders.

### Best Practices for writing scenarios:

Check [this link](https://behat.org/en/latest/user_guide/writing_scenarios.html) to learn how to write features for Behat.

### Execution:

Run `make behat` to execute end-to-end tests.

### GraphQL Password Reset E2E Tests

The password reset feature includes comprehensive GraphQL E2E tests in `/features/graphql_password_reset.feature`:

- **Requesting password reset for existing user via GraphQL**: Verifies the `requestPasswordResetUser` mutation returns success without leaking user existence
- **Requesting password reset for non-existing user via GraphQL**: Confirms no user enumeration (same response for non-existent users)
- **Confirming password reset with valid token via GraphQL**: Tests the complete `confirmPasswordResetUser` mutation flow
- **Confirming password reset with invalid token via GraphQL**: Validates proper error handling for invalid tokens

## OpenAPI Validation Testing (Schemathesis)

Schemathesis is used to automatically validate API endpoints against the OpenAPI specification, ensuring the implementation matches the documented contract.

### Tools:

We use **Schemathesis**, a property-based testing tool that generates test cases from OpenAPI specifications. It automatically tests all documented endpoints with various valid and invalid inputs.

### Execution:

Run `make schemathesis-validate` to execute OpenAPI validation tests. This command:
1. Seeds deterministic test data via `app:seed-schemathesis-data`
2. Runs Schemathesis against all API endpoints
3. Validates responses match the OpenAPI schema

### Best Practices:

- Keep OpenAPI examples consistent with seeded fixture data
- Ensure validators return proper error responses (not HTML error pages)
- Use `application/problem+json` content type for error responses

Learn more about [Advanced Configuration Guide](advanced-configuration.md).
