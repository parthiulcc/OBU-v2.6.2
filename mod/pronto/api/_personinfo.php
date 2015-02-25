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

/*Needed values :  
 * hash : the sig URL parameter
 * uri_param : the URI parameter, representing the username of the concerned person
 * */

//Verifify that the local hash matches with the remote one, and that the method can be executed.
//If not, return a XML error code
if ($hash != $local_hash){
  pronto_xml_error(PRONTO_SECRET_MISMATCH,"Plugin config secret mismatch in file : ".__FILE__." at line : ".__LINE__);
}

//Verify that the URI param exists, and set it to username
if (!isset($uri_param)) {
  pronto_xml_error(PRONTO_UNKNOWN_ERROR,"Userid parameter missing in file ".__FILE__." at line : ".__LINE__);
}

$username = $uri_param;

//Creates the XML document
$xml_global = new datatoxml();
$imsUtils = new ImsUtils();

//Get the local user corresponding to the username
$user = $DB->get_record("user", array('username' => $username));
pronto_add_log(PRONTO_DEBUG, "Got $username profile: " . print_r( $user, true ) );

/*If the users exists, his id is not empty
 * Gets its systemrole
 * Add the person datas in the XML document
 * Gets all its courses
 * For each course, gets its memberships, and add them in the XML document
 */

if (!empty($user) && $user->id != "") {
  $courses = enrol_get_users_courses($user->id, false);

  //Get the roleid (ims) in system
  $ims_system_role = $imsUtils->pronto_get_ims_role_in_system($user);

  pronto_add_log(PRONTO_DEBUG, "User: $userid has system role: $ims_system_role");
  //Add the system role in xml results
  $xml_global->addPersonElement($user->username, $user->firstname, $user->lastname, $user->email,$ims_system_role);
  foreach ($courses as $course) {
    pronto_add_log(PRONTO_DEBUG, "Processing course $course->id for $user->username");
    $roles_names= array();

    $context =  get_context_instance(CONTEXT_COURSE, $course->id);

    //Get the roleid (ims) in course
    $ims_courses_role = $imsUtils->pronto_get_ims_roles_in_context($context,$user);
    pronto_add_log(PRONTO_DEBUG, "User: $user->id has course role: $ims_courses_role in course $context->id");
    //Add the membership in xml results
    if($ims_courses_role){
      $xml_global->addMembershipElement($course->id, $user, $ims_courses_role);
    }
  }
}
//Returns the XML datas
echo $xml_global->getXml();
