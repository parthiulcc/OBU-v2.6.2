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
 * */


//Verifify that the local hash matches with the remote one, and that the method can be executed.
//If not, return a XML error code
if ( $hash != $local_hash) {
  pronto_xml_error(PRONTO_SECRET_MISMATCH,__FILE__." : ".__LINE__." Plugin config secret mismatch in file ");
}

//Gets all courses in Moodle database
$courses = $DB->get_records('course', null, '', 'id');

//Creates the XML document
$xml_global = new xmlresponse("success");

//For each courses in the database, add its id to the XML response
foreach ($courses as $course) {
  if ($course->id != 1) {
    $xml_global->addElement("id",$course->id);
  }
}

echo $xml_global->getXml();
