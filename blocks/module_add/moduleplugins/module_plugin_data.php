<?php

require_once('module_plugin_base.php');

class module_plugin_data extends module_plugin_base {
    protected function module_name() {
        return 'data';
    }

    protected function set_module_instance_params() {
        return array(true, '');
    }

    function post_create_setup() {
        global $DB;

        $course = $DB->get_record('course', array('id'=>$this->moduleobj->course), '*');
        $data = $DB->get_record('data', array('id'=>$this->moduleobj->instance), '*');
        $cm = $DB->get_record('course_modules', array('id'=>$this->moduleobj->coursemodule), '*');
        $data->instance = $data->id;
        $importer = new data_preset_existing_importer($course, $cm, $data, $this->paramobj->preset);
        $importer->import(false);

        return array(true, '');
    }

    static function check_params_xml($paramsxmlobj) {
        if (empty($paramsxmlobj->title) || empty($paramsxmlobj->description) || empty($paramsxmlobj->preset)) {
            return false;
        }
        return true;
    }
}
