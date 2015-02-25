<?php

require_once("{$CFG->libdir}/formslib.php");
 
class module_add_form extends moodleform {

    private $modules;


    function __construct(array $modules) {
        $this->modules = $modules;
        parent::__construct();
    }


    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', 'Add');

        $mform->addElement('select', 'module', 'Module', $this->modules);

        $mform->addElement('filepicker', 'courses', 'Course list');
        $mform->addRule('courses', null, 'required');

        $mform->addElement('filepicker', 'moduleparams', 'Module parameters');
        $mform->addRule('moduleparams', null, 'required');

        $options = array('0'=>'Skip', '1'=>'Add new', '2'=>'Replace');
        $mform->addElement('select', 'ifexists', 'If exists', $options);

        $mform->addElement('checkbox', 'atstart', 'Add at start', 'Adds module at end of section if unchecked');

        $mform->addElement('checkbox', 'visible', 'Visible');

        $mform->addElement('filepicker', 'permsfile', 'Permissions override file');

        $this->add_action_buttons(false, 'Add module');
    }

}
