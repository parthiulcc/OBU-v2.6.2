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
require_once('../../config.php');

  //The validation page for the module configuration
require_once($CFG->dirroot."/mod/pronto/prontolib.php");
   //Gather the configured parameter in the configuration
$secret = $_POST['s__pronto_secret'];
$account = $_POST['s__pronto_account'];
$url     = trim($_POST['s__pronto_url']);
$ntp_enabled = $_POST['s__pronto_ntp_synchronized'];

$time    = pronto_get_time(isset($ntp_enabled));

$username_utf8 = utf8_encode($USER->username);

$key = $account . $secret . $username_utf8 . $time;
$sig = pronto_sha_sign($key);

header('Content-Type: application/json; charset=UTF-8');

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
  $result = array(
    'success' => true,
    'url'     => $url . '/user/sso?account=' . $account .'&username=' .$username_utf8. '&ts=' . $time . '&sig=' . $sig . '&page=test_sso'
  );
} else {
  $result = array(
    'success' => false,
    'errorMessage' => get_string('validation_request_ajax', 'pronto')
  );
}
echo json_encode($result);
