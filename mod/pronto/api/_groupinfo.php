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
 * separator : the separator URL parameter
 * id : the id URL parameter
 * */

//Verify that the local hash matches with the remote one, and that the method can be executed.
//If not, return a XML error code

if ($hash != $local_hash){
    pronto_xml_error(PRONTO_SECRET_MISMATCH,"Plugin config secret mismatch in file : ".__FILE__." at line : ".__LINE__);
}

$imsUtils = new ImsUtils();
$pronto_roles_ids = $imsUtils->getProntoRolesIds();

//. If ids parameter is set, we will have several idsin one string, separated by the char in "separator"
if (isset($ids) && !empty($ids)) {
    //Verify that the the separator parameter is set
    if (!isset($separator)) {
        pronto_xml_error(PRONTO_UNKNOWN_ERROR,"Separator parameter missing in file ".__FILE__." at line : ".__LINE__);
    }

    //Creates the courses id tokens
    $tokens = explode($separator, $ids);

    $xml_global = new datatoxml();

    //For all courses id, add a corresponding group element in the XML datas
    foreach ($tokens as $token) {
        $course = $DB->get_record("course",array("id" => $token));
        if ($course->id != ""){
            $enrollmentcount = pronto_get_enrollment_in_course($course);
            $xml_global->addGroupElement($course->id,$course->shortname, $course->fullname,$course->summary,$course->format ,$course->visible,$enrollmentcount);
        }
    }

    echo $xml_global->getXml();
} elseif (isset($id) && !empty($id)) {
        $xml_global = new datatoxml("" . $COURSE->shortname . "");
        $course = $DB->get_record("course",array("id" => $id));
        if ($course->id != ""){
            $enrollmentcount = pronto_get_enrollment_in_course($course);
        }
        $xml_global->addGroupElement($course->id, $course->shortname, $course->fullname, $course->summary, $course->format, $course->visible, $enrollmentcount);

        echo $xml_global->getXml();
} else {
    //A paremeter id missing.
    pronto_xml_error(PRONTO_UNKNOWN_ERROR,"id or ids parameter missing in file ".__FILE__." at line : ".__LINE__);
}

/**
 * return the number of students + editing teacher + teacher in this course
 * This function takes into account the roles in higher contexts above the course 
 *
 * @param unknown_type $course
 * @param unknown_type $pronto_role_ids_array
 * @return unknown
 */
function pronto_get_enrollment_in_course($course){
    $context = get_context_instance(CONTEXT_COURSE, $course->id);

    return count_enrolled_users($context);
}
