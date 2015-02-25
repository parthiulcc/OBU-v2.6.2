<?php
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2013 Blackboard Inc., All Rights Reserved.              *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Blackboard Inc.                      *
 *      You can redistribute it and/or modify it under the terms of           *
 *      the GNU General Public License as published by the                    *
 *      Free Software Foundation.                                             *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is distributed in the hope that it will be useful,      *
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *      GNU General Public License for more details.                          *
 *                                                                            *
 *      You should have received a copy of the GNU General Public License     *
 *      along with the Blackboard Instant Messenger Moodle Integration;       *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Jonathan Abourbih                                                  *
 *                                                                            *
 ******************************************************************************/
class admin_setting_pronto_loglevel extends admin_setting {
    public function __construct($name, $heading, $description, $defaultsetting = 2) {
        $this->nosave = true;
        parent::__construct($name, $heading, $description, $defaultsetting);
    }

    /**
     * Return the setting
     *
     * @return mixed returns config if successful else null
     */
    public function get_setting() {
        return true;
    }

    public function write_setting($data) {
        return '';
    }

    /**
     * Returns HTML log listing
     *
     * @param string $data the option to show as selected
     * @param string $query
     * @return string HTML field and wrapping div
     */
    public function output_html($data, $query = '') {
        global $CFG;

        $return = '<a target="blank" href="' . $CFG->wwwroot . '/mod/pronto/prontolog.php?action=list">' . get_string('viewlogs', 'pronto') . '...</a>';

        return format_admin_setting($this, $this->visiblename, $return, '', true);
    }
}

/**
 * Fake setting control for the configuration check screen
 */
class admin_setting_pronto_checkconfig extends admin_setting {
    public function __construct($name, $heading, $description = '', $defaultvalue = true) {
        parent::__construct($name, $heading, $description, $defaultvalue);
    }

    public function get_setting() {
        return $this->config_read($this->name);
    }

    public function write_setting($data) {
        return $this->config_write($this->name, "winning") ? '' : '';
    }

    public function output_html($data, $query = '') {
        global $PAGE;

        $jsconfig = array(
            'name' => 'mod_pronto',
            'fullpath' => '/mod/pronto/module.js',
            'requires' => array('node', 'node-event-html5', 'io', 'dom', 'json'),
            'strings' => array(
                array('invalidlettersnumbers', 'pronto')
            )
        );
        $PAGE->requires->js_init_call('M.mod_pronto.init', array(), false, $jsconfig);

        $html = '<div id="pronto_checkconfig">';
        $html .= html_writer::empty_tag('input',
                    array('type' => 'button',
                        'id' => 'pronto_checkconfig_btn',
                        'value' => get_string('connectiontest', 'pronto'),
                    ));
        $html .= html_writer::empty_tag('input',
                    array('type' => 'hidden',
                    'id' => $this->get_id(),
                    'name' => $this->get_full_name(),
                    'value' => 'true'));
        $html .= '<div id="pronto_checkconfig_result"></div>';
        $html .= '</div>';

        return format_admin_setting($this, $this->visiblename, $html, $this->description, true);
    }
}
