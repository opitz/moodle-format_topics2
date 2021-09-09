@format @format_topics2 @format_topics2_collapse
Feature: Sections can be collapsed and expanded in topics2 format
  In order to keep an overview
  As a student
  I need to be able to collapse and expand sections

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@example.com |
      | student1 | Student   | 1        | student1@example.com |
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
      | student1 | C1     | student |
    And I log in as "student1"
    And I am on "Course 1" course homepage

  @javascript
  Scenario: Collapsing and uncollapsing section 1
    When I uncollapse section "1"
    Then the sectionbody of section "1" should be visible
    And the sectionbody of section "2" should be hidden
    And I collapse section "1"
    Then the sectionbody of section "1" should be hidden
