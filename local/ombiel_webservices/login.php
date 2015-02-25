<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version echo3 of the License, or
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
 * Log in with token
 *
 * @copyright &copy; 2014 oMbiel
 * @author oMbiel
 * @package oMbiel_webservices
 * @version 1.0
 */
require_once("../../config.php");

define('WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN', 1);

if (!$CFG->enablewebservices) {
    redirect(get_login_url());
}

if (empty($CFG->ombielallowtokenlogin)) {    
    redirect(get_login_url());
}

//HTTPS is required in this page when $CFG->loginhttps enabled
$PAGE->https_required();

$context = context_system::instance();
$PAGE->set_url("$CFG->httpswwwroot/local/ombiel_webservices/login.php");
$PAGE->set_context($context);

$userid = required_param('userid', PARAM_INT);
$token = required_param('wstoken', PARAM_ALPHANUM);
$courseid = optional_param('courseid', false, PARAM_INT);
$cmid = optional_param('cmid', false, PARAM_INT);
$echo360id = optional_param('echo360id', false, PARAM_INT);
$panoptourl = optional_param('panoptourl', false, PARAM_TEXT);
$messagesettings = optional_param('messages', false, PARAM_ALPHANUM);

if (isloggedin() and !isguestuser())  {
    if ($USER->id != $userid) {
        // Something's wrong. Logout, delete token and force new login
        require_logout();
        $DB->delete_records('external_tokens', array('token'=>$token));
        redirect(get_login_url());  
    }
} else {
    
    // Obtain token record
    if (!$tokenRecord = $DB->get_record('external_tokens', array('token' => $token, 'userid'=>$userid, 'tokentype'=>EXTERNAL_TOKEN_PERMANENT,'contextid'=>$context->id))) {
        redirect(get_login_url());  
    }
    
    // Validate that token not expired
    if (!empty($tokenRecord->validuntil) and $tokenRecord->validuntil < time()) {
        if ($CFG->version < 2014051200) {  # Moodle 2.7
            add_to_log(SITEID, 'webservice', get_string('tokenauthlog', 'webservice'), '', get_string('invalidtimedtoken', 'webservice'), 0);
            $DB->delete_records('external_tokens', array('token' => $tokenRecord->token));
        } else {    
            $params = array(
                'other' => array(
                    'method' => WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN,
                    'reason' => 'token_expired',
                    'tokenid' => $tokenRecord->id
                )
            );
            $event = \core\event\webservice_login_failed::create($params);
            $event->add_record_snapshot('external_tokens', $tokenRecord);
            $event->set_legacy_logdata(array(SITEID, 'webservice', get_string('tokenauthlog', 'webservice'), '',
                get_string('invalidtimedtoken', 'webservice'), 0));
            $event->trigger();
        }   
        // Don't error - could have legitimately expired
        redirect(get_login_url());  
    }
    // Check ip
    if ($tokenRecord->iprestriction and !address_in_subnet(getremoteaddr(), $tokenRecord->iprestriction)) {
        if ($CFG->version < 2014051200) {  # Moodle 2.7
            add_to_log(SITEID, 'webservice', get_string('tokenauthlog', 'webservice'), '', get_string('failedtolog', 'webservice') . ": " . getremoteaddr(), 0);
        } else {  
            $params = array(
                'other' => array(
                    'method' => WEBSERVICE_AUTHMETHOD_PERMANENT_TOKEN,
                    'reason' => 'ip_restricted',
                    'tokenid' => $tokenRecord->id
                )
            );
            $event = \core\event\webservice_login_failed::create($params);
            $event->add_record_snapshot('external_tokens', $tokenRecord);
            $event->set_legacy_logdata(array(SITEID, 'webservice', get_string('tokenauthlog', 'webservice'), '',
                get_string('failedtolog', 'webservice') . ": " . getremoteaddr(), 0));
            $event->trigger();
        }
        redirect(get_login_url());  
    }

    $user = $DB->get_record('user', array('id' => $tokenRecord->userid, 'deleted' => 0), '*', MUST_EXIST);

    // let enrol plugins deal with new enrolments if necessary
    enrol_check_plugins($user);

    // setup user session to check capability
    if ($CFG->version < 2013111800) { # Moodle 2.6
        session_set_user($user);
    } else {
        \core\session\manager::set_user($user);
    }
    
    if (is_siteadmin($user))  {
        $DB->delete_records('external_tokens', array('sid' => $tokenRecord->sid));
        if ($CFG->version < 2013111800) { # Moodle 2.6
            session_get_instance()->terminate_current();
        } else {
            \core\session\manager::terminate_current();
        }
        redirect($CFG->wwwroot); 
    }

    if (!has_capability('local/ombiel_webservices:allowtokenlogin', $context)){
        if ($CFG->version < 2013111800) { # Moodle 2.6
            session_get_instance()->terminate_current();
        } else {
            \core\session\manager::terminate_current();
        }
        redirect($CFG->wwwroot);         
    }
    
    //assumes that if sid is set then there must be a valid associated session no matter the token type
    if ($tokenRecord->sid) {
        $session = session_get_instance();
        if (!$session->session_exists($tokenRecord->sid)) {
            $DB->delete_records('external_tokens', array('sid' => $tokenRecord->sid));
            print_error('invalidtoken', 'webservice');
        }
    }

    //Can not authenticate if maintenance mode
    if (!empty($CFG->maintenance_enabled)) {
        redirect($CFG->wwwroot);
    }

    //retrieve web service record
    $service = $DB->get_record('external_services', array('id' => $tokenRecord->externalserviceid, 'enabled' => 1));
    if (empty($service)) {
        $DB->delete_records('external_tokens', array('token' => $tokenRecord->token));
        print_error('invalidtoken', 'webservice');
    }

    //check if there is any required system capability
    if ($service->requiredcapability and !has_capability($service->requiredcapability, context_system::instance(), $user)) {
        if ($CFG->version < 2013111800) { # Moodle 2.6
            session_get_instance()->terminate_current();
        } else {
            \core\session\manager::terminate_current();
        }
        redirect($CFG->wwwroot);         
    }

    //specific checks related to user restricted service
    if ($service->restrictedusers) {
        $authoriseduser = $DB->get_record('external_services_users', array('externalserviceid' => $service->id, 'userid' => $user->id));

        if (empty($authoriseduser)) {
            if ($CFG->version < 2013111800) { # Moodle 2.6
                session_get_instance()->terminate_current();
            } else {
                \core\session\manager::terminate_current();
            }
            redirect($CFG->wwwroot);         
        }

        if (!empty($authoriseduser->validuntil) and $authoriseduser->validuntil < time()) {
            if ($CFG->version < 2013111800) { # Moodle 2.6
                session_get_instance()->terminate_current();
            } else {
                \core\session\manager::terminate_current();
            }
            redirect($CFG->wwwroot);         
        }

        if (!empty($authoriseduser->iprestriction) and !address_in_subnet(getremoteaddr(), $authoriseduser->iprestriction)) {
            if ($CFG->version < 2013111800) { # Moodle 2.6
                session_get_instance()->terminate_current();
            } else {
                \core\session\manager::terminate_current();
            }
            redirect($CFG->wwwroot);         
        }
    }

    //only confirmed user should be able to call web service
    if (empty($user->confirmed)) {
        $DB->delete_records('external_tokens', array('token' => $tokenRecord->token));
        if ($CFG->version < 2014051200) {  # Moodle 2.7
            add_to_log(SITEID, 'webservice', 'user unconfirmed', '', $user->username);
        } else {
            $failurereason = AUTH_LOGIN_FAILED;
            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                    'other' => array('username' => $user->username, 'reason' => $failurereason)));
            $event->trigger();
            error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Unconfirmed Login:  $user->username  ".$_SERVER['HTTP_USER_AGENT']);    
        }
        print_error('invalidtoken', 'webservice');
    }

    //check the user is not suspended
    if (!empty($user->suspended)) {
        $DB->delete_records('external_tokens', array('token' => $tokenRecord->token));
        if ($CFG->version < 2014051200) {  # Moodle 2.7
            add_to_log(SITEID, 'webservice', 'user suspended', '', $user->username);
        } else {
            $failurereason = AUTH_LOGIN_SUSPENDED;
            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                    'other' => array('username' => $user->username, 'reason' => $failurereason)));
            $event->trigger();
            error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Suspended Login:  $user->username  ".$_SERVER['HTTP_USER_AGENT']);        
        }
        print_error('invalidtoken', 'webservice');
    }

    //check if the auth method is nologin (in this case refuse connection)
    if ($user->auth == 'nologin') {
        $DB->delete_records('external_tokens', array('token' => $tokenRecord->token));
        if ($CFG->version < 2014051200) {  # Moodle 2.7
            add_to_log(SITEID, 'webservice', 'nologin auth attempt with web service', '', $user->username);
        } else {
            $failurereason = AUTH_LOGIN_FAILED;
            // Trigger login failed event.
            $event = \core\event\user_login_failed::create(array('userid' => $user->id,
                    'other' => array('username' => $user->username, 'reason' => $failurereason)));
            $event->trigger();
            error_log('[client '.getremoteaddr()."]  $CFG->wwwroot  Nologin Login:  $user->username  ".$_SERVER['HTTP_USER_AGENT']);  
            
        }
        print_error('invalidtoken', 'webservice');
    }
    
    // Delete token - now it has been used to log in force a new token to be generated.
    $DB->delete_records('external_tokens', array('token' => $tokenRecord->token));
    
}
if($courseid) {
    redirect("{$CFG->wwwroot}/course/view.php?id={$courseid}");
} elseif ($cmid) {
    $cm = get_coursemodule_from_id(false, $cmid, 0, false, MUST_EXIST);
    redirect("{$CFG->wwwroot}/mod/{$cm->modname}/view.php?id={$cm->id}");
} elseif ($echo360id) {
    redirect("{$CFG->wwwroot}/blocks/echo360_echocenter/echocenter_frame.php?id={$echo360id}");
} elseif ($panoptourl) {
    redirect(urldecode($panoptourl));
} elseif ($messagesettings) {
    redirect("{$CFG->wwwroot}/message/edit.php");
} else {
    redirect($CFG->wwwroot);
}
