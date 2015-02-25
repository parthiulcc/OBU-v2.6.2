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


/**
 * Define the mapping between the different capabilities and their role in ims
 *
 */
class ImsUtils {

  //keys for course and system roles
  var $IMS_ADMINISTRATOR_ROLENAME = "Administrator";
  var $IMS_COURSECREATOR_ROLENAME = "Course creator";
  var $IMS_TEACHER_ROLENAME = "Teacher";
  var $IMS_NONEDITINGTEACHER_ROLENAME = "Non-editing teacher";
  var $IMS_STUDENT_ROLENAME = "Student";
  var $IMS_NONE_ROLENAME = "None";

  //Tab with the possible roletypes in ims for a course
  var $ims_courses_roles ;

  //Tab with the possible roletypes in ims for a system
  var $ims_system_roles ;


  /**
   * Initialize the arrays
   *
   * @return ImsUtils
   */
  function ImsUtils(){
    $this->ims_courses_roles =  array( $this->IMS_ADMINISTRATOR_ROLENAME => "07",
      $this->IMS_COURSECREATOR_ROLENAME => "03",
      $this->IMS_TEACHER_ROLENAME => "02",
      $this->IMS_NONEDITINGTEACHER_ROLENAME => "08",
      $this->IMS_STUDENT_ROLENAME => "01"
    );
    $this->ims_system_roles = array(
      $this->IMS_NONE_ROLENAME => "00",
      $this->IMS_ADMINISTRATOR_ROLENAME => "07"
    );

  }
  /**
   * Compute the user role in the system for pronto
   *
   * @param  user $user
   * @return the roleId in ims
   */
  function pronto_get_ims_role_in_system($user){
    //For moodle 17 and above
    $context_site = get_context_instance(CONTEXT_SYSTEM);
    if(is_siteadmin($user->id)){
      return $this->ims_system_roles[$this->IMS_ADMINISTRATOR_ROLENAME];
    }

    return $this->ims_system_roles[$this->IMS_NONE_ROLENAME];

  }

  /**
   * Get a list of visible roles for the user in the given context.
   */
  function pronto_get_visible_roles_in_context($context, $user) {
    return get_user_roles($context, $user->id, true);
  }

  /**
   * Flatten the access data so it can be checked against the
   * capabilities the user actually has.
   */
  function pronto_flatten_access_data($access_data) {
    $capabilities = array();

    foreach($access_data as $access_item) {
      pronto_add_log(PRONTO_DEBUG, "access item " . print_r($access_item, true));

      foreach($access_item["rdef"] as $rdef) {
        pronto_add_log(PRONTO_DEBUG, "rdef " . print_r($rdef, true));
        foreach(array_keys($rdef) as $capability) {
          if($rdef[$capability] > 0) {
            $capabilities[] = $capability;
          }
        }
      }
    }

    return $capabilities;
  }

  /**
   * Compute the user role in the context for pronto
   *
   * @param context $context (a context for moodle 17 and above, a course for moodle 16)
   * @param user $user
   * @return the roleId in ims
   */
  function pronto_get_ims_roles_in_context($context,$user){
    global $DB;

    // Get a list of non-hidden roles for the user in this context
    $user_roles = get_user_roles($context, $user->id, true);

    // If the user has no non-hidden roles, return
    if(empty($user_roles)) {
      return;
    }

    pronto_add_log(PRONTO_DEBUG, "User $user->id has roles in context $context->id of " . print_r($user_roles, true));
    
    $role_archetypes = array();

    foreach ($user_roles as $_ignore => $role) {
      $role_record = $DB->get_record('role', array('id' => $role->roleid));
      $role_archetypes[] = $role_record->archetype;
    }

    pronto_add_log(PRONTO_DEBUG, "User $user->id has role archetypes " . print_r($role_archetypes, true));

    if(in_array('editingteacher', $role_archetypes)) {
      return $this->ims_courses_roles[$this->IMS_TEACHER_ROLENAME];
    } elseif (in_array('teacher', $role_archetypes)) {
      return $this->ims_courses_roles[$this->IMS_NONEDITINGTEACHER_ROLENAME];
    } elseif (in_array('student', $role_archetypes)) {
      return $this->ims_courses_roles[$this->IMS_STUDENT_ROLENAME];
    }
  }

  /**
   *
   *
   * @param string_constant $imsRole (IMS_ADMINISTRATOR_ROLENAME,IMS_COURSECREATOR_ROLENAME...)
   * @return the roleId in ims
   */
  function getImsCourseRoleId($imsRole){
    return $this->ims_courses_roles[$imsRole];
  }

  /**
   *
   *
   * @param _string_constant $imsRole (IMS_ADMINISTRATOR_ROLENAME,IMS_NONE_ROLENAME)
   * @return string the roleId in ims
   */
  function getImsSystemRoleId($imsRole){
    return $this->ims_system_roles[$imsRole];
  }

  /**
   * This function is used for moodle 17 and above
   * Moodle 16 doesn't get the same role management with roleId
   *
   * @return The ids of the roles taken into account by pronto
   */
  function getProntoRolesIds(){
    global $DB;

    $pronto_role_ids_array = array();
    
    $roles = $DB->get_records('role');
    foreach ($roles as $role) {
      if (in_array($role->archetype, array('student', 'teacher', 'editingteacher'))) {
        $pronto_role_ids_array[] = $role->id;
      }
    }
    
    return $pronto_role_ids_array;
  }
}
