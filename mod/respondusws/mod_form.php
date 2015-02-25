<?php
// Respondus 4.0 Web Service Extension For Moodle
// Copyright (c) 2009-2014 Respondus, Inc.  All Rights Reserved.
// Date: July 24, 2014.
defined("MOODLE_INTERNAL") || die();
require_once("$CFG->dirroot/course/moodleform_mod.php");
class mod_respondusws_mod_form extends moodleform_mod {
    public function definition() {
        global $COURSE;
        $mform =& $this->_form;
        $mform->addElement("header", "general", get_string("general", "form"));
        $mform->addElement("text", "name", get_string("responduswsname",
          "respondusws"), array("size" => "64"));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType("name", PARAM_TEXT);
        } else {
            $mform->setType("name", PARAM_CLEANHTML);
        }
        $mform->addRule("name", null, "required", null, "client");
        $this->add_intro_editor(true,
          get_string("responduswsintro", "respondusws"));
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (count($errors) == 0) {
            return true;
        } else {
            return $errors;
        }
    }
}
