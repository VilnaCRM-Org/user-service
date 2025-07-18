Feature: Health Check Operations
  In order to ensure system components are functioning
  As an administrator
  I want to be able to verify the health of various subsystems

  Scenario: Checking the health of the entire system
    When I send a "GET" request to "/api/health"
    Then print last response
    Then the response status code should be 204

  Scenario: Checking the health when cache is unavailable
    Given the cache is not working
    When I send a "GET" request to "/api/health"
    Then the response status code should be 500
    And the response should contain "Something went wrong"

  Scenario: Checking the health when the database is unavailable
    Given the database is not available
    When I send a "GET" request to "/api/health"
    Then the response status code should be 500
    And the response should contain "Something went wrong"

  Scenario: Checking the health when the queue service is unavailable
    Given the message broker is not available
    When I send a "GET" request to "/api/health"
    Then the response status code should be 500
    And the response should contain "Something went wrong"