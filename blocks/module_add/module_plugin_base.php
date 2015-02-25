<?php

abstract class module_plugin_base {
    protected $paramobj;
    protected $moduleobj;

    function __construct($paramobj, $moduleobj) {
        $this->paramobj = $paramobj;
        $this->moduleobj = $moduleobj;
    }

    function create_instance() {
        // Include module lib
        $modlib = '../../mod/' . $this->module_name() . '/lib.php';
        if (file_exists($modlib)) {
            global $CFG;
            require_once($modlib);
        } else {
            return array(false, 'Module lib not found');
        }

        $ret = $this->set_module_instance_params();
        if (!$ret[0]) {
            return $ret;
        }

        // Add instance and update course_modules DB row
        $addinstancefunction = $this->module_name() . '_add_instance';
        if ($this->get_num_instance_function_params() == 1) {
            $returnfromfunc = $addinstancefunction($this->moduleobj);
        } else {
            $returnfromfunc = $addinstancefunction($this->moduleobj, true);
        }
        if (!$returnfromfunc or !is_number($returnfromfunc)) {
            // undo everything we can
            $modcontext = context_module::instance($this->moduleobj->coursemodule);
            $modcontext->delete();
            $DB->delete_records('course_modules', array('id'=>$this->moduleobj->coursemodule));
    
            if (!is_number($returnfromfunc)) {
                return array(false, "$addinstancefunction is not a valid function");
            } else {
                return array(false, 'Cannot add new module');
            }
        }
        $this->moduleobj->instance = $returnfromfunc;

        return array(true, '');
    }

    /* The add instance function for some modules takes a different number
        of parameters */
    protected function get_num_instance_function_params() {
        return 1;
    }

    function post_create_setup() {
        return array(true, '');
    }

    abstract protected function module_name();

    abstract protected function set_module_instance_params();

    static abstract function check_params_xml($paramsxmlobj);
}
