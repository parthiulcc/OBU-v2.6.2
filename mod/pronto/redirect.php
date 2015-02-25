<?php
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2011 Blackboard Inc., All Rights Reserved.                *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Blackboard Inc.                       *
 *      You can redistribute it and/or modify it under the terms of           *
 *      the GNU General Public License as published by the                    *
 *      Free Software Foundation.                                             *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is distributed in the hope that it will be useful,      *
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *      GNU General Public License for more details.                          *
 *                                                                            *
 *      You should have received a copy of the GNU General Public License     *
 *      along with the Wimba Probto Moodle Integration;                      *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Jonathan Abourbih
 *                                                                            *
 * Date: 14 April 2011
 *                                                                            *
 ******************************************************************************/

//Builds the SSO URL, and redirects the navigator to it.
require_once('../../config.php');
global $CFG;
global $USER;
require_once($CFG->dirroot . '/lib/datalib.php');
require_once('prontolib.php');
global $DB;
/*
 With this patch, we enforce checks for module->visible
 in the 2 key points-of-transit for SSO. With this,
 a Moodle admin that has disabled the module can be
 certain that no SSO will happen in either direction.
 */
if ($DB->get_field('modules', 'visible', array('name' => 'pronto')) == 0) {
  print_error("This module is disabled");
}

/*
 Check for the sesskey (pages that link to it to include the sesskey)
 */
if (!confirm_sesskey()) {
  print_error(get_string('confirmsesskeybad', 'error'));
}

/**Gets the necessary parameters
 * ts : the remote timestamp, using the prontolib pronto_get_time method
 * referer : the url of the page
 * sig = the sha hashed signature, hashing the concatenation of
 * 		the configured account
 * 		the configured secret
 * 		the name of the user calling the redirect
 * 		the pre-computed timestamp
 */
$username = "guest";

//Check whether the user is a guest. In future versions of Moodle the
//guest acct may be called something different from 'guest'.
if (function_exists('isguestuser')) {
  if (!isguestuser() && $USER->id != 0) {
    $username = $USER->username;
  }
} else {
  if (!isguest() && $USER->id != 0) {
    $username = $USER->username;
  }
}

$username_utf8 = utf8_encode($username);
$ts = pronto_get_time($CFG->pronto_ntp_synchronized);
$referer = $_SERVER['HTTP_REFERER'];
$sig = pronto_sha_sign(@$CFG->pronto_account . @$CFG->pronto_secret . $username_utf8 . $ts);

//Redirects the navigator to the built URL
$url = $CFG->pronto_url .
      '/user/sso?account=' . @$CFG->pronto_account . 
      '&username=' . $username_utf8 . '&ts=' . $ts . 
      '&sig=' . $sig .'&referer=' . @$referer;

pronto_add_log(PRONTO_DEBUG, "Redirecting to $url");
header("location: $url");
