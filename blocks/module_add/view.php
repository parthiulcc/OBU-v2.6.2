<?php

require_once("{$CFG->libdir}/formslib.php");

class module_add_view {

    private $form = null;


    function __construct() {
        $this->setup_page();
    }


    function set_form(moodleform $form) {
        $this->form = $form;
    }

    
    private function setup_page() {
        global $PAGE;
        $PAGE->set_url('/blocks/module_add/controller.php');
        $PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
        $PAGE->set_pagelayout('standard');
        $PAGE->set_title(get_string('addmodule', 'block_module_add'));
        $PAGE->set_heading(get_string('addmodule', 'block_module_add'));
    }

    

    private function op_header() {
        global $OUTPUT;
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('addmodule', 'block_module_add'));
    }


    private function op_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
        die();
    }


    function output_form() {
        $this->setup_page();
        $this->op_header();
        echo '<p><a href="help_page.php">Help page</a></p>';
        $this->form->display();
        $this->op_footer();
    }


    function permissions_override_error($error='') {
        global $CFG;
        $this->setup_page();
        $this->op_header();
        echo '<p>An error was found while checking the permissions override file for validity</p>';
        if (!empty($error)) {
            echo '<p>The error is: ' . htmlspecialchars($error) . '</p>';
        }
        echo '<p><a href="' . $CFG->wwwroot . '/blocks/module_add/controller.php">Back to add module form</a></p>';
        $this->op_footer();
    }


    function maincsv_error($error='') {
        global $CFG;
        $this->setup_page();
        $this->op_header();
        echo '<p>An error was found while checking the course list file for validity</p>';
        if (!empty($error)) {
            echo '<p>The error is: ' . htmlspecialchars($error) . '</p>';
        }
        echo '<p><a href="' . $CFG->wwwroot . '/blocks/module_add/controller.php">Back to add module form</a></p>';
        $this->op_footer();
    }


    function moduleparams_error($errors='') {
        global $CFG;
        $this->setup_page();
        $this->op_header();
        echo '<p>Errors were found while checking the module parameters file for validity.</p>';
        if (!empty($errors)) {
            echo '<p>The errors are: ' . str_replace('\n', '<br />', htmlspecialchars($errors)) . '</p>';
        }
        echo '<p><a href="' . $CFG->wwwroot . '/blocks/module_add/controller.php">Back to add module form</a></p>';
        $this->op_footer();
    }


    function output_processing_start() {
        $this->setup_page();
        $this->op_header();
        echo '<table class="generaltable boxaligncenter">';
        echo '<tr class="heading">';
        echo '<th>Course ID</td><th>Module Code</th><th>Status</th>';
        echo '</tr>';
    }


    function output_processing_row(array $fields) {
        echo '<tr>';
        foreach ($fields as $field) {
            echo '<td>' . s($field) . '</td>';
        }
        echo '</tr>';
    }


    function output_processing_end() {
        global $CFG;
        echo '</table>';
        echo '<p><a href="' . $CFG->wwwroot . '/blocks/module_add/controller.php">Back to add module form</a></p>';
        $this->op_footer();
    }

}
