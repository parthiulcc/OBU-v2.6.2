<?php 
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2011 Blackboard Inc., All Rights Reserved.              *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Blackboard Inc.                      *
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
 *      along with the Wimba Pronto Moodle Integration;                      *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Jonathan Abourbih
 *                                                                            *
 * Date: 14 April 2011
 *                                                                            *
 ******************************************************************************/

require_once("../../config.php");
require_once("lib.php");

$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$p  = optional_param('p', 0, PARAM_INT);  // pronto ID

if ($id) {
  $cm = get_coursemodule_from_id('pronto', $id, 0, false, MUST_EXIST);
  $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
  $pronto = $DB->get_record('pronto', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
  $pronto = $DB->get_record('pronto', array('id' => $p), '*', MUST_EXIST);
  $course = $DB->get_record('course', array('id' => $pronto->course), '*', MUST_EXIST); 
  $cm = get_coursemodule_from_instance('pronto', $pronto->id, $course->id, false, MUST_EXIST);
}

require_login($course->id, true, $cm);

add_to_log($course->id, "pronto", "view", "view.php?id=$cm->id", "$pronto->id");

$strprontos = get_string("modulenameplural", "pronto");
$strpronto  = get_string("modulename", "pronto");

$PAGE->set_url('/mod/pronto/view.php', array('id' => $cm->id));
$PAGE->set_title($strpronto);
$PAGE->set_heading($course->shortname);
$PAGE->set_button(update_module_button($cm->id, $course->id, get_string('modulename', 'pronto')));

$PAGE->set_cacheable(false);

$redirect_url = $CFG->wwwroot.'/mod/pronto/redirect.php?sesskey='.sesskey();
$content_html = '<a href="' . $redirect_url .'" target="_blank">'
  . get_string('pronto_activity_title', 'pronto')
  . '</a>';

echo $OUTPUT->header();
echo $OUTPUT->box($content_html);
echo $OUTPUT->footer();
?>
