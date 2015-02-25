<?php

require_once('module_plugin_base.php');

class module_plugin_feedback extends module_plugin_base {
    protected function module_name() {
        return 'feedback';
    }

    protected function set_module_instance_params() {
        $this->moduleobj->multiple_submit = 0;
        $this->moduleobj->autonumbering = 0;
        $this->moduleobj->page_after_submit = '';

        return array(true, '');
    }

    function post_create_setup() {
        global $DB;

        $feedback = $DB->get_record('feedback', array('id'=>$this->moduleobj->instance), '*');
        feedback_items_from_template($feedback, (int)$this->paramobj->template);

        return array(true, '');
    }

    static function check_params_xml($paramsxmlobj) {
        if (empty($paramsxmlobj->title) || empty($paramsxmlobj->description) || (int)$paramsxmlobj->template < 1) {
            return false;
        }
        return true;
    }
}
