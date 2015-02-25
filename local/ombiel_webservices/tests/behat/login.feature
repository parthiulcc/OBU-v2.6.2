@local @local_ombiel_webservices
Feature: Login from token
  In order to validate my credentials in the system
  As a user
  I need to log into the system with a token

# @todo add service restrictions

  Background:
    Given the following "users" exists:
      | username | password | firstname | lastname | email |
      | testuser | testuser | Test | User | moodle@moodlemoodle.com |  
    And I log in as "admin"
    And I expand "Site administration" node
    And I follow "Advanced features"
    And I check "Enable web services"
    And I press "Save changes"
    And I expand "Plugins" node
    And I expand "Local plugins" node
    And I follow "oMbiel Webservices"
    And I check "Allow token login" 
    And I press "Save changes"   
    And I log out  

  @javascript
  Scenario: Login as an existing user 
    Given I get a token for "testuser"
    And I login with token for "testuser"
    Then I should see "You are logged in as Test User" 

  @javascript
  Scenario: Try to login with deleted token
    Given I get a token for "testuser"
    And I login with token for "testuser"
    Then I should see "You are logged in as Test User" 
    And I log out    
    And I login with token for "testuser"
    Then I should see "You are not logged in."

  @javascript
  Scenario: Try to login with expired token
    Given I get a token for "testuser"
    And I mock timing out the token
    And I login with token for "testuser"
    Then I should see "You are not logged in." 

  @javascript
  Scenario: Login with when this user is already logged in
    Given I log in as "testuser"
    Given I get a token for "testuser"
    And I login with token for "testuser"
    Then I should see "You are logged in as Test User" 
    
  @javascript
  Scenario: Try to login with when another user is already logged in
    Given I log in as "admin"
    Given I get a token for "testuser"
    And I login with token for "testuser"
    Then I should see "You are not logged in." 
    
  @javascript
  Scenario: Login with when guest is already logged in
    Given I log in as "guest"
    Given I get a token for "testuser"
    And I login with token for "testuser"
    Then I should see "You are logged in as Test User" 
  
  @javascript
  Scenario: login with non time limited token
    Given I get a token for "testuser"
    And I mock a non time limited token for "testuser"
    And I login with token for "testuser"
    Then I should see "You are logged in as Test User" 
    
  @javascript
  Scenario: Try to login with IP restriction
    Given I get a token for "testuser"
    And I mock an ip restricted token for "testuser"
    And I login with token for "testuser"
    Then I should see "You are not logged in." 
    
  @javascript
  Scenario: Try to login as admin
    Given I get a token for "admin"
    And I login with token for "admin"
    Then I should see "You are not logged in."
    
  @javascript
  Scenario: Try to login with suspended user
    Given the following "users" exists:
      | username | password | firstname | lastname | email | suspended |
      | suspended | suspended | Suspended | User | moodle@moodlemoodle.com | 1 |
    Given I get a token for "suspended"
    And I login with token for "suspended"
    Then I should see "You are not logged in." 
    
  @javascript
  Scenario: Try to login with un-confirmed user
    Given the following "users" exists:
      | username | password | firstname | lastname | email | confirmed |
      | notconfirmed | notconfirmed | Not | Confirmed | moodle@moodlemoodle.com | 0 |
    Given I get a token for "notconfirmed"
    And I login with token for "notconfirmed"
    Then I should see "You are not logged in." 
    
  @javascript
  Scenario: Try to login with nologin user
    Given the following "users" exists:
      | username | password | firstname | lastname | email | auth |
      | nologin | nologin | No | Login | moodle@moodlemoodle.com | nologin |
    Given I get a token for "nologin"
    And I login with token for "nologin"
    Then I should see "You are not logged in." 

  @javascript
  Scenario: Try to login with deleted user
    Given the following "users" exists:
      | username | password | firstname | lastname | email | deleted |
      | deleted | deleted | No | Login | moodle@moodlemoodle.com | 1 |
    Given I get a token for "deleted"
    And I login with token for "deleted"
    Then I should see "You are not logged in." 
    
  @javascript
  Scenario: Login to course 
    Given the following "courses" exists:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exists:
      | user | course | role |
      | testuser | C1 | student |
    Given I get a token for "testuser"
    And I login with token to course "C1" for "testuser"
    Then I should see "You are logged in as Test User" 
    Then I should see "Course 1" 
    Then I should not see "Enrolment options" 
    
  @javascript
  Scenario: Login to course when this user is already logged in
    Given the following "courses" exists:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And the following "course enrolments" exists:
      | user | course | role |
      | testuser | C1 | student |
    Given I log in as "testuser"
    Given I get a token for "testuser"
    And I login with token for "testuser"
    Then I should see "You are logged in as Test User" 
    Then I should see "Course 1" 

  @javascript
  Scenario: Login to course (not enrolled)
    Given the following "courses" exists:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    Given I get a token for "testuser"
    And I login with token to course "C1" for "testuser"
    Then I should see "Enrolment options" 
    Then I should see "Course 1" 
    
  @javascript
  Scenario: Login to course module 
    Given the following "courses" exists:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And I log in as "admin"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Choice" to section "1" and I fill the form with:
      | Choice name | Choice name 1 |
      | Description | Choice Description 1 |
      | option[0] | Option 1 |
      | option[1] | Option 2 | 
    And I log out   
    And the following "course enrolments" exists:
      | user | course | role |
      | testuser | C1 | student |
      
    Given I get a token for "testuser"
    And I login with token to course module type "choice" instance "Choice name 1" for "testuser"
    Then I should see "You are logged in as Test User" 
    And I should see "Choice Description 1" 
    And I should see "Option 1"  
    
  @javascript
  Scenario: Login to course module (not enrolled)
    Given the following "courses" exists:
      | fullname | shortname | category |
      | Course 1 | C1 | 0 |
    And I log in as "admin"
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "Choice" to section "1" and I fill the form with:
      | Choice name | Choice name 1 |
      | Description | Choice Description 1 |
      | option[0] | Option 1 |
      | option[1] | Option 2 | 
    And I log out   
      
    Given I get a token for "testuser"
    And I login with token to course module type "choice" instance "Choice name 1" for "testuser"
    Then I should see "You are logged in as Test User" 
    And I should not see "Choice Description 1" 
    Then I should see "Enrolment options" 
    Then I should see "Course 1" 

  @javascript
  Scenario: Login to message settings 
    Given I get a token for "testuser"
    And I login to message settings with token for "testuser"
    Then I should see "You are logged in as Test User" 
    Then I should see "Configure notification methods for incoming messages" 

  @javascript
  Scenario: Login to message settings  when this user is already logged in
    Given I log in as "testuser"
    Given I get a token for "testuser"
    And I login to message settings with token for "testuser"
    Then I should see "You are logged in as Test User" 
    Then I should see "Configure notification methods for incoming messages" 

  @javascript     
  Scenario: Try to login without user permission
    Given I log in as "admin"
    And I set the following system permissions of "Authenticated user" role:
      | local/ombiel_webservices:allowtokenlogin | Prevent |
    And I log out  
    Given I get a token for "testuser"
    And I login with token for "testuser"
    Then I should see "You are not logged in." 

  @javascript
  Scenario: Try to login with when webservices is disabled    
    And I get a token for "testuser"
    Given I log in as "admin"
    And I set the following administration settings values:
      | Enable web services | 0 |
    And I log out        
    And I login with token for "testuser"
    Then I should see "You are not logged in." 
    
  @javascript
    Scenario: Try to login with when Allow token login is disabled
    Given I get a token for "testuser"
    Given I log in as "admin"
    And I set the following administration settings values:
      | Allow token login | 0 |
    And I log out     
    And I login with token for "testuser"
    Then I should see "You are not logged in." 

    