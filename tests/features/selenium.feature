@selenium
Feature: Test selenium

  Scenario: Gateway reachable with Selenium
    Given I open 2 browser tabs identified by "a,b"
    And I switch to "a"
    And I go to "https://gateway.stepup.example.com/health"
    Then the response should contain 'UP'
    And I switch to "b"
    And I go to "https://gateway.stepup.example.com/info"
    Then the response should contain 'Gateway'
