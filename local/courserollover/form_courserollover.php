<?php

require_once("{$CFG->libdir}/formslib.php");


class form_courserollover extends moodleform {

    function definition() {
        $mform =& $this->_form;

        $mform->addElement('filepicker', 'rolloverids', 'Roll-over course IDs');
        $mform->addRule('rolloverids', null, 'required');

        $mform->addElement('filepicker', 'excludeactivities', 'Excluded activities');

        $this->add_action_buttons(false, 'Roll-over courses');
    }

}
