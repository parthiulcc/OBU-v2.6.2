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
 * Author: Frederic Mathiot                                                   *
 *                                                                            *
 * Date: 16th November 2007                                                   *
 *                                                                            *
 ******************************************************************************/

defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/course/moodleform_mod.php');

class mod_pronto_mod_form extends moodleform_mod {

  function definition() {
    global $COURSE;

    $mform =& $this->_form;

    $mform->addElement('header', 'general', get_string('general', 'form'));

    $mform->addElement('text', 'name', 'Name', get_string('pronto_activity_title', 'pronto'));
    $mform->addRule('name', null, 'required', null, 'client');

    $this->standard_coursemodule_elements();
    $this->add_action_buttons();
  }


}
