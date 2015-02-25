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
require_once("lib.php");

$id = required_param('id', PARAM_INT);   // course

$PAGE->set_url('/mod/pronto/index.php', array('id'=>$id));

if (! $course = $DB->get_record("course", array("id" => $id))) {
  error("Course ID is incorrect");
}

require_login($course->id);

add_to_log($course->id, "pronto", "view all", "index.php?id=$course->id", "");


/// Get all required strings

$strprontos = get_string("modulenameplural", "pronto");
$strpronto  = get_string("modulename", "pronto");


/// Print the header

if ($course->category) {
  $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
} else {
  $navigation = '';
}

print_header("$course->shortname: $strprontos", "$course->fullname", "$navigation $strprontos", "", "", true, "", navmenu($course));

/// Get all the appropriate data

if (! $prontos = get_all_instances_in_course("pronto", $course)) {
  notice("There are no pronto", "../../course/view.php?id=$course->id");
die;
}

/// Print the list of instances (your module will probably extend this)

$timenow = time();
$strname  = get_string("name");
$strweek  = get_string("week");
$strtopic  = get_string("topic");

$listTable = new html_table();
$attribs['style'] ="margin-left:auto; margin-right:auto; width:90%";
$listTable->attributes = $attribs;
if ($course->format == "weeks") {
  $listTable->head  = array ($strweek, $strname);
  $listTable->align = array ("center", "left");
} else if ($course->format == "topics") {
  $listTable->head  = array ($strtopic, $strname);
  $listTable->align = array ("center", "left", "left", "left");
} else {
  $listTable->head  = array ($strname);
  $listTable->align = array ("left", "left", "left");
}

foreach ($prontos as $pronto) {
  if (!$pronto->visible) {
    //Show dimmed if the mod is hidden
    $link = "<a class=\"dimmed\" href=\"view.php?id=$pronto->coursemodule\">$pronto->name</a>";
  } else {
    //Show normal if the mod is visible
    $link = "<a href=\"view.php?id=$pronto->coursemodule\">$pronto->name</a>";
  }

  if ($course->format == "weeks" or $course->format == "topics") {
    $listTable->data[] = array ($pronto->section, $link);
  } else {
    $listTable->data[] = array ($link);
  }
}

echo $OUTPUT->box_start('generalbox', '');
echo html_writer::table($listTable);
echo $OUTPUT->box_end();

/// Finish the page

echo $OUTPUT->footer($course);
