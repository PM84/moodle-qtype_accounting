@qtype @qtype_accounting @javascript
Feature: Confirm chart of accounts deletion
  As a teacher
  I need to review the consequences before deleting a chart of accounts
  So that I do not accidentally break questions that use it

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the accounting chart "Main" with accounts "Bank, Cash" exists in course "Course 1"
    And I am on the "Course 1" "core_question > course question bank" page logged in as "teacher1"
    And I press "Create a new question ..."
    And I set the field "Accounting Entry" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I click on "Manage chart of accounts" "link"
    And I switch to a second window

  Scenario: Cancelling deletion preserves the chart and its accounts
    When I click on "Delete" "link" in the "Main" "table_row"
    Then I should see "Are you sure you want to permanently delete the chart of accounts" in the "Delete chart" "dialogue"
    And I should see "Main"
    And I should see "All accounts in this chart will also be deleted."
    And I should see "Questions that use this chart will no longer work correctly."
    And I should see "This action cannot be undone."
    When I click on "Cancel" "button" in the "Delete chart" "dialogue"
    Then "Main" "link" should exist
    When I click on "Main" "link"
    Then I should see "Bank"
    And I should see "Cash"

  Scenario: Deleting a chart requires explicit confirmation
    When I click on "Delete" "link" in the "Main" "table_row"
    Then "Delete chart" "dialogue" should exist
    When I click on "Delete chart" "button" in the "Delete chart" "dialogue"
    Then I should see "Chart of accounts deleted successfully."
    And "Main" "link" should not exist
    And I should see "No charts of accounts have been created yet."
