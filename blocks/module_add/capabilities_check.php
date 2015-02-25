<?php

class capabilities_check {

    private $roles;
    private $capabilities;


    function __construct() {
        $this->get_roles();
        $this->get_capabilities();
    }


    private function get_roles() {
        global $DB;

        $sql = 'SELECT id, name FROM {role}';
        $instances = $DB->get_records_sql($sql);

        $this->roles = array();
        foreach ($instances as $instance) {
            $this->roles[$instance->id] = $instance->name;
        }
    }


    private function get_capabilities() {
        global $DB;

        $sql = 'SELECT id, name FROM {capabilities}';
        $instances = $DB->get_records_sql($sql);

        $this->capabilities = array();
        foreach ($instances as $instance) {
            $this->capabilities[$instance->id] = $instance->name;
        }
    }


    function valid_role($role) {
        // If passed role is an integer check for valid role ID, otherwise check
        // for valid role name
        if (is_numeric($role)) {
            return array_key_exists((int)$role, $this->roles);
        } else {
            return in_array($role, $this->roles);
        }
    }


    function valid_capability($capability) {
        // If passed capability is an integer check for valid capability ID, otherwise check
        // for valid capability name
        if (is_numeric($capability)) {
            return array_key_exists((int)$capability, $this->capabilities);
        } else {
            return in_array($capability, $this->capabilities);
        }
    }


    function valid_permission($permission) {
        if ($permission == 'allow' || $permission == 'prevent') {
            return true;
        } else {
            return false;
        }
    }

}

?>
