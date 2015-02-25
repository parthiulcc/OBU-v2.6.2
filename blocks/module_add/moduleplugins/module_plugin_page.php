<?php

require_once('module_plugin_base.php');

class module_plugin_page extends module_plugin_base {
    protected function module_name() {
        return 'page';
    }

    protected function set_module_instance_params() {
        $this->moduleobj->display = 0; // Display auto
        if ($this->paramobj) {
            $text = (string) $this->paramobj->text;
        } else {
            $text = '';
        }
        $this->moduleobj->page = array('itemid'=>0, 'text'=>$text, 'format'=>1);
        $this->moduleobj->printheading = 1;
        $this->moduleobj->printintro = 0;

        return array(true, '');
    }

    protected function get_num_instance_function_params() {
        return 2;
    }

    static function check_params_xml($paramsxmlobj) {
        if (empty($paramsxmlobj->title) || empty($paramsxmlobj->description) || empty($paramsxmlobj->text)) {
            return false;
        }
        return true;
    }
}
