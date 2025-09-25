@local @local_urise @local_urise_navigation
Feature: Baisc functionality of local_urise works as expected

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | 1        |
      | student2 | Student   | 2        |
      | teacher1 | Teacher   | 1        |
      | teacher2 | Teacher   | 2        |
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
      | Cat 2 | 0        | CAT2     |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | CAT1     |
      | Course 2 | C2        | CAT2     |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |
      | teacher1 | C1     | editingteacher |
      | student2 | C2     | student        |
      | teacher2 | C2     | editingteacher |
    And the following "activities" exist:
      | activity | course | name       | intro               | bookingmanager | eventtype | autoenrol |
      | booking  | C1     | C1Booking1 | Booking description | teacher1       | Webinar   | 1         |
      | booking  | C2     | C2Booking1 | Booking description | teacher2       | Webinar   | 1         |
    And I change viewport size to "1366x10000"

  @javascript
  Scenario: Urise navigation: display main menu and goto dashboard
    Given I log in as "admin"
    And I click on "u:rise" "text" in the "nav div[data-id=\"urise-popover-region\"]" "css_element"
    And I click on "Dashboard" "text" in the "nav div[data-id=\"urise-popover-region\"] .dropdown-menu" "css_element"
    And I wait to be redirected
    ## Validate dashboard header tabs
    And I should see "My Dashboard" in the "#local-urise-app" "css_element"
    And I should see "General" in the "#local-urise-app .bgfull .overflow-tabs-container" "css_element"
    And I should see "Cat 1" in the "#local-urise-app .bgfull .overflow-tabs-container" "css_element"
    And I should see "Cat 2" in the "#local-urise-app .bgfull .overflow-tabs-container" "css_element"
    ## Validate dashboard active page
    And I should see "Overview" in the "#local-urise-app" "css_element"
    And I should see "Bookingoptionfields" in the "#local-urise-app" "css_element"
    And I should see "General" in the "#local-urise-app .content-container #home" "css_element"
    And I should not see "Booking instances" in the "#local-urise-app .content-container #home" "css_element"
    ## Validate categories and booking instances
    And I click on "Cat 1" "text" in the "#local-urise-app .bgfull .overflow-tabs-container" "css_element"
    And I should see "Booking instances" in the "#local-urise-app .content-container #home" "css_element"
    And I should see "C1Booking1" in the "#local-urise-app .content-container #home" "css_element"
    And I click on "Cat 2" "text" in the "#local-urise-app .bgfull .overflow-tabs-container" "css_element"
    And I should see "Booking instances" in the "#local-urise-app .content-container #home" "css_element"
    And I should see "C2Booking1" in the "#local-urise-app .content-container #home" "css_element"
    ## Validate additional tab menu
    And I click on "Bookingoptionfields" "text" in the "#local-urise-app" "css_element"
    And I should see "Bookingoption for experts" in the "#local-urise-app .content-container #profile" "css_element"
