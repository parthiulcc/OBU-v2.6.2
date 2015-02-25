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
require_once("prontolib.php");

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted pronto record
 **/
function pronto_add_instance($pronto) {
    global $DB;
    $pronto->timemodified = time();

    # May have to add extra stuff in here #
    
    return $DB->insert_record("pronto", $pronto);
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function pronto_update_instance($pronto) {
    global $DB;

    $pronto->timemodified = time();
    $pronto->id = $pronto->instance;

    # May have to add extra stuff in here #

    return $DB->update_record("pronto", $pronto);
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it. 
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function pronto_delete_instance($id) {
    global $DB;
    if (! $pronto = $DB->get_record("pronto", array("id" => $id))) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! $DB->delete_records("pronto", array("id" => $pronto->id))) {
        $result = false;
    }

    return $result;
}

/**
 * Return a small object with summary information about what a 
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 **/
function pronto_user_outline() {
	$return = NULL;
    return $return;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function pronto_user_complete($course, $user, $mod, $pronto) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in pronto activities and print it out. 
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function pronto_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such 
 * as sending out mail, toggling flags etc ... 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function pronto_cron () {
    global $CFG;

    return true;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user.  It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $prontoid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function pronto_grades($prontoid) {
   return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of pronto. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $prontoid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function pronto_get_participants($prontoid) {
    return false;
}

/**
 * This function returns if a scale is being used by one pronto
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $prontoid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function pronto_scale_used ($prontoid,$scaleid) {
    $return = false;

    //$rec = get_record("pronto","id","$prontoid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other pronto functions go here.  Each of them must have a name that 
/// starts with pronto_

function pronto_supports($feature)
{
    switch($feature) {
        case FEATURE_BACKUP_MOODLE2:
          return true;
        default:
          return false;
    }
}
