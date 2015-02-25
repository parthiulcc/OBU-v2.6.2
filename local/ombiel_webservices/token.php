<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Return sessiion token
 *
 * @copyright &copy; 2014 oMbiel
 * @author oMbiel
 * @package oMbiel_webservices
 * @version 1.0
 */
define('AJAX_SCRIPT', true);
define('REQUIRE_CORRECT_ACCESS', true);
define('NO_MOODLE_COOKIES', true);

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once("{$CFG->libdir}/externallib.php");

$username = required_param('username', PARAM_USERNAME);
$password = required_param('password', PARAM_RAW);
$serviceshortname = required_param('service', PARAM_ALPHANUMEXT);
echo $OUTPUT->header();

if (!$CFG->enablewebservices) {
    throw new moodle_exception('enablewsdescription', 'webservice');
}
$username = trim(textlib::strtolower($username));
if (is_restored_user($username)) {
    throw new moodle_exception('restoredaccountresetpassword', 'webservice');
}
$user = authenticate_user_login($username, $password);

$userparams = array('username' => $username, 'deleted' => 0);
if (empty($user) and !empty($CFG->campusmldapendpoint) and $DB->record_exists('user', $userparams)) {

    $soapOptions = array(
        'login' => $CFG->campusmldapusername,
        'password' => $CFG->campusmldappassword,
        'location' => $CFG->campusmldapendpoint
    );
    
    $method = $CFG->campusmldapmethod;
    
    $soapclient = new SoapClient($CFG->campusmldapendpoint . '.wsdl', $soapOptions);
    try {
        $request = array('username' => $username, 'password' => $password);
        $soapclient->$method($request);
        $user = $DB->get_record('user', $userparams);
    } catch (Exception $e) {
        $user = false;
    }
}
if (!empty($user)) {

    //Non admin can not authenticate if maintenance mode
    $hassiteconfig = has_capability('moodle/site:config', context_system::instance(), $user);
    if (!empty($CFG->maintenance_enabled) and !$hassiteconfig) {
        throw new moodle_exception('sitemaintenance', 'admin');
    }

    if (isguestuser($user)) {
        throw new moodle_exception('noguest');
    }
    if (empty($user->confirmed)) {
        throw new moodle_exception('usernotconfirmed', 'moodle', '', $user->username);
    }
    // check credential expiry
    $userauth = get_auth_plugin($user->auth);
    if (!empty($userauth->config->expiration) and $userauth->config->expiration == 1) {
        $days2expire = $userauth->password_expire($user->username);
        if (intval($days2expire) < 0) {
            throw new moodle_exception('passwordisexpired', 'webservice');
        }
    }

    // let enrol plugins deal with new enrolments if necessary
    enrol_check_plugins($user);

    // setup user session to check capability
    if ($CFG->version < 2013111800) { # Moodle 2.6
        session_set_user($user);
    } else {
        \core\session\manager::set_user($user);
    }

    //check if the service exists and is enabled
    $service = $DB->get_record('external_services', array('shortname' => $serviceshortname, 'enabled' => 1));
    if (empty($service)) {
        // will throw exception if no token found
        throw new moodle_exception('servicenotavailable', 'webservice');
    }

    //check if there is any required system capability
    if ($service->requiredcapability and !has_capability($service->requiredcapability, context_system::instance(), $user)) {
        throw new moodle_exception('missingrequiredcapability', 'webservice', '', $service->requiredcapability);
    }

    //specific checks related to user restricted service
    if ($service->restrictedusers) {
        $authoriseduser = $DB->get_record('external_services_users', array('externalserviceid' => $service->id, 'userid' => $user->id));

        if (empty($authoriseduser)) {
            throw new moodle_exception('usernotallowed', 'webservice', '', $serviceshortname);
        }

        if (!empty($authoriseduser->validuntil) and $authoriseduser->validuntil < time()) {
            throw new moodle_exception('invalidtimedtoken', 'webservice');
        }

        if (!empty($authoriseduser->iprestriction) and !address_in_subnet(getremoteaddr(), $authoriseduser->iprestriction)) {
            throw new moodle_exception('invalidiptoken', 'webservice');
        }
    }
    //
    //Check if a time limited token has already been created for this user and this service
    //
    $tokenssql = "  SELECT t.id, t.sid, t.token, t.validuntil, t.iprestriction
                    FROM {external_tokens} t
                    WHERE t.userid = ? AND t.externalserviceid = ? AND t.tokentype = ? AND t.validuntil > 0
                    ORDER BY t.timecreated ASC";
    $tokens = $DB->get_records_sql($tokenssql, array($user->id, $service->id, EXTERNAL_TOKEN_PERMANENT));

    foreach ($tokens as $key => $token) {

        /// Checks related to a specific token.
        $unsettoken = false;
        //if sid is set then there must be a valid associated session no matter the token type
        if (!empty($token->sid)) {
            $session = session_get_instance();
            if (!$session->session_exists($token->sid)) {
                //this token will never be valid anymore, delete it
                $DB->delete_records('external_tokens', array('sid' => $token->sid));
                $unsettoken = true;
            }
        }

        //remove token if expired 
        if ($token->validuntil < time()) {
            $DB->delete_records('external_tokens', array('token' => $token->token, 'tokentype' => EXTERNAL_TOKEN_PERMANENT));
            $unsettoken = true;
        }

        // remove token if its ip not in whitelist
        if (isset($token->iprestriction) and !address_in_subnet(getremoteaddr(), $token->iprestriction)) {
            $unsettoken = true;
        }

        if ($unsettoken) {
            unset($tokens[$key]);
        }
    }
    // if valid tokens exist then use the most recent
    if (count($tokens) > 0) {
        $token = array_pop($tokens);
        $token->lastaccess = time();
        $token->validuntil = time() + $CFG->ombieltokentimeout;
        $DB->update_record('external_tokens', $token);
    } else {
        if (has_capability('local/ombiel_webservices:createtimelimitedtoken', context_system::instance())) {
            $validuntil = time() + $CFG->ombieltokentimeout;
            $token = new stdClass();
            $token->token = external_generate_token(EXTERNAL_TOKEN_PERMANENT, $service, $user->id, context_system::instance(), $validuntil);
        } else {
            throw new moodle_exception('cannotcreatetoken', 'webservice', '', $serviceshortname);
        }
    }

    if ($CFG->version < 2014051200) {  # Moodle 2.7
        add_to_log(SITEID, 'webservice', 'sending requested user token', '', 'User ID: ' . $user->id);
    } else {
        $params = array(
            'objectid' => $token->id,
        );
        $event = \core\event\webservice_token_sent::create($params);
    }
    
    $usertoken = new stdClass;
    $usertoken->token = $token->token;
    echo json_encode($usertoken);
} else {
    throw new moodle_exception('usernamenotfound', 'moodle');
}
