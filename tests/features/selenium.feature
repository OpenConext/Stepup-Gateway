@selenium
Feature: Test selenium

  Scenario: Gateway reachable with Selenium
    Given I go to "https://gateway.stepup.example.com/health"
    Then the response should contain 'UP'
