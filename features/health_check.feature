Feature: Health Check Operations
  In order to ensure system components are functioning
  As an administrator
  I want to be able to verify the health of various subsystems

  Scenario: Checking the health of the entire system
    When I send a "GET" request to "/api/health"
    Then print last response
    Then the response status code should be 204
