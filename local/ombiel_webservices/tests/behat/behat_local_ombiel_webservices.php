<?php

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Mink\Exception\ExpectationException as ExpectationException;

class behat_local_ombiel_webservices extends behat_base {

    private $token;

    /**
     * Gets a token for the the user. There should exist a user with the same value as username and password.
     *
     * @Given /^I get a token for "(?P<username_string>(?:[^"]|\\")*)"$/
     */
    public function i_get_a_token_for($username)
    {

        $path = "/local/ombiel_webservices/token.php?username={$username}&password={$username}&service=campusm";
        $this->getSession()->visit($this->locate_path($path));

        $response = $this->getSession()->getPage()->getContent();

        $fromTokenStart = substr($response, strpos($response, '{"token":"') + 10);

        $this->token = substr($fromTokenStart, 0, strpos($fromTokenStart, '"}'));
    }

    /**
     * Attempts to login with a token for username
     *
     * @Given /^I login with token for "(?P<username_string>(?:[^"]|\\")*)"$/
     */
    public function i_login_with_token_for($username)
    {
        global $DB;

        $userid = $DB->get_field('user', 'id', array('username' => $username));
        $path = "/local/ombiel_webservices/login.php?userid={$userid}&wstoken={$this->token}";

        $this->getSession()->visit($this->locate_path($path));
    }

    /**
     * Attempts to login with a token to course for username
     *
     * @Given /^I login with token to course "(?P<course_string>(?:[^"]|\\")*)" for "(?P<username_string>(?:[^"]|\\")*)"$/
     */
    public function i_login_with_token_to_course_for($courseshortname, $username)
    {
        global $DB;

        $userid = $DB->get_field('user', 'id', array('username' => $username));
        $courseid = $DB->get_field('course', 'id', array('shortname' => $courseshortname));
        $path = "/local/ombiel_webservices/login.php?userid={$userid}&wstoken={$this->token}&courseid={$courseid}";

        $this->getSession()->visit($this->locate_path($path));
    }

    /**
     * Attempts to login with a token to course module for username
     *
     * @Given /^I login with token to course module type "(?P<modname_string>(?:[^"]|\\")*)" instance "(?P<name_string>(?:[^"]|\\")*)" for "(?P<username_string>(?:[^"]|\\")*)"$/
     */
    public function i_login_with_token_to_course_module_for($modname, $name, $username)
    {
        global $DB;

        $userid = $DB->get_field('user', 'id', array('username' => $username));
        $instance = $DB->get_field($modname, 'id', array('name' => $name));
        $modid = $DB->get_field('modules', 'id', array('name' => $modname));

        $cmid = $DB->get_field('course_modules', 'id', array('module' => $modid, 'instance' => $instance));
        $path = "/local/ombiel_webservices/login.php?userid={$userid}&wstoken={$this->token}&cmid={$cmid}";
        echo $path;
        $this->getSession()->visit($this->locate_path($path));
    }

    /**
     * Attempts to login to the message settings with a token for username
     *
     * @Given /^I login to message settings with token for "(?P<username_string>(?:[^"]|\\")*)"$/
     */
    public function i_login_to_message_settings_with_token_for($username)
    {
        global $DB;

        $userid = $DB->get_field('user', 'id', array('username' => $username));
        $path = "/local/ombiel_webservices/login.php?userid={$userid}&wstoken={$this->token}&messages=true";

        $this->getSession()->visit($this->locate_path($path));
    }

    /**
     * Mock a non time limited token for the user.
     *
     * @Given /^I mock a non time limited token for "(?P<username_string>(?:[^"]|\\")*)"$/
     */
    public function i_mock_a_non_time_limited_token_for($username)
    {
        global $DB;

        $token = $DB->get_record('external_tokens', array('token' => $this->token));
        $token->validuntil = 0;
        $DB->update_record('external_tokens', $token);
    }

    /**
     * Mock an IP restricted token for the user.
     *
     * @Given /^I mock an ip restricted token for "(?P<username_string>(?:[^"]|\\")*)"$/
     */
    public function i_mock_an_ip_restricted_token_for($username)
    {
        global $DB;

        $token = $DB->get_record('external_tokens', array('token' => $this->token));
        $token->iprestriction = '192.168.88.88';
        $DB->update_record('external_tokens', $token);
    }

    /**
     * Mock a timing out the token  .
     *
     * @Given /^I mock timing out the token$/
     */
    public function i_mock_timing_out_the_token()
    {
        global $DB;

        $token = $DB->get_record('external_tokens', array('token' => $this->token));
        $token->validuntil = time() - 1;
        $DB->update_record('external_tokens', $token);
    }

    /**
     * Checks a valid token is returned
     *
     * @Then /^I should get a valid token$/
     * @throws ExpectationException
     */
    public function assert_valid_token()
    {
        $response = $this->getSession()->getPage()->getContent();

        if (!preg_match('/{"token":".*"}/', $response)) {
            throw new ExpectationException('Not a valid token', $this->getSession());
        }
    }

    /**
     * Checks a valid token is not returned
     *
     * @Then /^I should not get a valid token$/
     * @throws ExpectationException
     */
    public function assert_not_valid_token()
    {
        $response = $this->getSession()->getPage()->getContent();

        if (preg_match('/{"token":".*"}/', $response)) {
            throw new ExpectationException('Not a valid token', $this->getSession());
        }
    }

}
