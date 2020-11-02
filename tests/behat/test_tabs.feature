@format @format_topics2
Feature: Tabs can be used in topics2 format
  In order to rearrange my course contents
  As a teacher
  I need to move topics into tabs and back

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Course 1 | C1        | topics2 | 0             | 5           |
    And the following "activities" exist:
      | activity   | name                   | intro                         | course | idnumber    | section |
      | assign     | Test assignment name   | Test assignment description   | C1     | assign1     | 0       |
      | book       | Test book name         | Test book description         | C1     | book1       | 1       |
      | chat       | Test chat name         | Test chat description         | C1     | chat1       | 4       |
      | choice     | Test choice name       | Test choice description       | C1     | choice1     | 5       |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on

  @javascript
  Scenario: Move section 4 to tab 3 in topics2 format
    When I move section "4" to tab "3"
    Then section "4" should be hidden
    And section "5" should be visible
    And I click on tab "3"
    Then section "4" should be visible
    And section "1" should be hidden
    And section "2" should be hidden
    And section "3" should be hidden
    And section "5" should be hidden

  @javascript
  Scenario: Inline edit tab name in topics2 format
    When I move section "4" to tab "3"
    And I click on "Edit tab name" "link" in the "#tab3" "css_element"
    And I set the field "New value for {a}" to "Test Tab"
    And I press key "13" in the field "New value for {a}"
    Then I should not see "Tab 3" in the "region-main" "region"
    And "New value for {a}" "field" should not exist
    And I should see "Test Tab" in the "#tab3" "css_element"
    And I am on "Course 1" course homepage
    And I should not see "Tab 3" in the "region-main" "region"
    And I should see "Test Tab" in the "#tab3" "css_element"

