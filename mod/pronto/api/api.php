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

require_once ('../../../config.php');
require_once ('../../../version.php');
require_once ('./roles/imsroles.php');
require_once ('./datatoxml.php');
require_once ('./xmlresponse.php');
require_once ('./xmlinfo.php');
require_once ('../lib.php');
global $CFG;

//Gets the path info, to parse api parameters
$path = @$_SERVER['PATH_INFO'];

/* always set the content type to text/xml */
header("Content-Type: text/xml; charset=utf-8");

 /*
  With this patch, we enforce checks for module->visible
  in the 2 key points-of-transit for SSO. With this,
  a Moodle admin that has disabled the module can be
  certain that no SSO will happen in either direction.
  */
if ($DB->get_field('modules', 'visible', array('name' => 'pronto'))==0) {
  pronto_xml_error(PRONTO_UNKNOWN_ERROR,__FILE__.":".__LINE__." The module is disabled.");
}

//Check if the first uri parameter is set, and set it to $version
if(!$apiversion = strtok($path,"/")){
  pronto_xml_error(PRONTO_UNKNOWN_ERROR,__FILE__.":".__LINE__." Version parameter is missing.");
}

//Check if the second uri parameter is set, and set it to $command
  /*
     The $command parameter in api.php could allow a shell
     execution if the caller used backticks. Limited to an
     attacker that knows the shared secret, but still dangerous.
   */

if (!$command = clean_param(strtok("/"), PARAM_ACTION)){
  pronto_xml_error(PRONTO_UNKNOWN_ERROR,__FILE__.":".__LINE__." Command parameter is missing.");
}

//Log : INFO : the "command" has been called
pronto_add_log(PRONTO_INFO,__FILE__.":".__LINE__." : ".$command." method called.");


if ($command!="info"){	
  //Check if the ts parameter is set, and set it to $timestamp
  $timestamp = required_param("ts", PARAM_RAW);
  if (!isset($timestamp)) {
    pronto_xml_error(PRONTO_TS_EMPTY,__FILE__.":".__LINE__." Plugin Timestamp from Kiwi to DataProvider empty.");
  }

  //Calculate the diff�rence between he local timestamp and the parameter one
  $time = time() * 1000;
  pronto_add_log(PRONTO_DEBUG, __FILE__.":".__LINE__." local time: $time");
  $time_diff = abs($timestamp - $time);
  pronto_add_log(PRONTO_DEBUG, __FILE__.":".__LINE__." time difference: $time_diff");

  //Verifies that the difference is valid, according to the timestamp lag limit set in prontolib.php
  if ($time_diff >= PRONTO_TIMESTAMP_LAG){
    pronto_xml_error(PRONTO_TS_EXPIRED,__FILE__.":".__LINE__." Plugin Timestamp from Kiwi to DataProvider expired.");
  }

  //Check if the sig parameter is set, and set it to $hash
  $hash = required_param("sig", PARAM_ALPHANUMEXT);
  if (!isset($hash)) {
    pronto_xml_error(PRONTO_UNKNOWN_ERROR,__FILE__.":".__LINE__." Signature parameter missing.");
  }

  //Gets the account and secret configured by the admin in the module.
  $configured_account = @$CFG->pronto_account;
  $configured_secret = @$CFG->pronto_secret;

  //Tests if the account parameter is not empty
  if ($configured_account == "") {
    pronto_xml_error(PRONTO_ACCOUNT_EMPTY,__FILE__.":".__LINE__." Plugin config account empty.");
  }

  //Creates the hash, with the configured account, secret and the timestamp in parameter
  $local_hash = pronto_sha_sign($configured_account.$configured_secret.$timestamp);
}

/*Gets parameters, that can be used by methods
 * id or ids : used by groupinfo
 * separator : used by groupinfo
 * groupid : used by sso
 * account : used by info
 * userid = used by sso
 */
$id = optional_param(PRONTO_ID_PARAMETER, "", PARAM_RAW);
$ids = optional_param(PRONTO_IDS_PARAMETER, "", PARAM_RAW);
$groupid = optional_param(PRONTO_GROUPID_PARAMETER, "", PARAM_RAW);
$separator = optional_param(PRONTO_SEPARATOR_PARAMETER, ",", PARAM_RAW);
$account = optional_param(PRONTO_ACCOUNT_PARAMETER, "", PARAM_RAW);
$userid = optional_param(PRONTO_USERID_PARAMETER, "", PARAM_RAW);

/*Gets optional uri_param, should be :
 * redirect param for sso
 * username param for personinfo
 */
$uri_param = @strtok("/");

//Validate the command name
if(!file_exists("./_".$command.".php") ) {
  pronto_xml_error(PRONTO_UNKNOWN_ERROR,__FILE__.":".__LINE__." Called method doesn't exist.");
} else {
  pronto_add_log(PRONTO_DEBUG, __FILE__.":".__LINE__." Calling $command");
  require_once("./_".$command.".php");
}

