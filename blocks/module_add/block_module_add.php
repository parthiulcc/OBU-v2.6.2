<?php

class block_module_add extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_module_add');
    }


    function get_content() {
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $this->content->text = '';
        $this->content->footer = '';

		if (has_capability('moodle/site:config', context_system::instance())) {
            $this->content->text = html_writer::link('blocks/module_add/controller.php', 'Add module to course(s)');
		}

        return $this->content;
    }


    function applicable_formats() {
        return array('site-index' => true);
    }
}


