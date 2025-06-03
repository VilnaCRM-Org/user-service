Feature: Health Check
  In order to monitor system health
  As a system administrator
  I need to be able to check the status of various services

  Scenario: Check system health when all services are working
    When "GET" request is sent to "/health"
    Then the response status code should be "204"

  Scenario: Check system health when cache is not working
    Given the cache is not working
    When "GET" request is sent to "/health"
    Then the response status code should be "500"
    And the response body should contain "Cache is not working"

  Scenario: Check system health when database is not available
    Given the database is not available
    When "GET" request is sent to "/health"
    Then the response status code should be "500"
    And the response body should contain "Database is not available"

  Scenario: Check system health when message broker is not available
    Given the message broker is not available
    When "GET" request is sent to "/health"
    Then the response status code should be "500"
    And the response body should contain "Message broker is not available" 