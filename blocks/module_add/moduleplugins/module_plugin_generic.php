<?php

require_once('module_plugin_base.php');

class module_plugin_generic extends module_plugin_base {
    private $modulename;

    function __construct($paramobj, $moduleobj, $modulename) {
        parent::__construct($paramobj, $moduleobj);
        $this->modulename = $modulename;
    }

    protected function module_name() {
        return $this->modulename;
    }

    protected function set_module_instance_params() {
        return array(true, '');
    }

    static function check_params_xml($paramsxmlobj) {
        if (empty($paramsxmlobj->title) || empty($paramsxmlobj->description)) {
            return false;
        }
        return true;
    }
}
