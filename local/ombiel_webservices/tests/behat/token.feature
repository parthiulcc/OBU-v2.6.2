@local @local_ombiel_webservices
Feature: Get token for user
  In order to validate my credentials in the system
  As a user
  I need to get a token

  Background:
    And I log in as "admin"
    And I expand "Site administration" node
    And I follow "Advanced features"
    And I check "Enable web services"
    And I press "Save changes"   
    And I log out  
  
  @javascript
  Scenario: Get a valid token  
    Given the following "users" exists:
      | username | password | firstname | lastname | email |
      | testuser | testuser | Test | User | moodle@moodlemoodle.com |  
    And I get a token for "testuser"
    Then I should get a valid token
    
  @javascript
  Scenario: Invalid password
    Given the following "users" exists:
      | username | password | firstname | lastname | email |
      | testuser | password | Test | User | moodle@moodlemoodle.com |  
    And I get a token for "testuser"
    Then I should not get a valid token
    
  @javascript
  Scenario: 'restored' user 
    Given the following "users" exists:
      | username | password | firstname | lastname | email |
      | testuser | restored | Test | User | moodle@moodlemoodle.com |  
    And I get a token for "testuser"
    Then I should not get a valid token

  @javascript
  Scenario: Suspended user
    Given the following "users" exists:
      | username | password | firstname | lastname | email | suspended |
      | suspended | suspended | Suspended | User | moodle@moodlemoodle.com | 1 |
    And I get a token for "suspended"
    Then I should not get a valid token
    
  @javascript
  Scenario: Un-confirmed user
    Given the following "users" exists:
      | username | password | firstname | lastname | email | confirmed |
      | notconfirmed | notconfirmed | Not | Confirmed | moodle@moodlemoodle.com | 0 |
    And I get a token for "notconfirmed"
    Then I should not get a valid token
    
  @javascript
  Scenario: nologin user
    Given the following "users" exists:
      | username | password | firstname | lastname | email | auth |
      | nologin | nologin | No | Login | moodle@moodlemoodle.com | nologin |
    And I get a token for "nologin"
    Then I should not get a valid token      
      
  @javascript
  Scenario: Deleted user
    Given the following "users" exists:
      | username | password | firstname | lastname | email | deleted |
      | deleted | deleted | No | Login | moodle@moodlemoodle.com | 1 |
    And I get a token for "deleted"
    Then I should not get a valid token         
    
  @javascript
  Scenario: Webservices is disabled    
    And I get a token for "testuser"
    Given I log in as "admin"
    And I set the following administration settings values:
      | Enable web services | 0 |
    And I log out        
    And I get a token for "testuser"
    Then I should not get a valid token