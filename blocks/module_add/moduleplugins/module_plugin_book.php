<?php

require_once('module_plugin_base.php');

class module_plugin_book extends module_plugin_base {
    private $currpage;


    protected function module_name() {
        return 'book';
    }

    protected function set_module_instance_params() {
    	switch ($this->paramobj->numbering) {
            case 'none':
                $this->moduleobj->numbering = 0;
                break;
            case 'numbers':
                $this->moduleobj->numbering = 1;
                break;
            case 'bullets':
                $this->moduleobj->numbering = 2;
                break;
            case 'indented':
                $this->moduleobj->numbering = 3;
                break;
        }

        return array(true, '');
    }

    protected function get_num_instance_function_params() {
        return 2;
    }

    private function add_chapter($title, $content, $subchapter=false) {
        global $DB;

        $bookchapterobj = new stdClass();
        $bookchapterobj->bookid = $this->moduleobj->instance;
        $bookchapterobj->pagenum = $this->currpage++;
        $bookchapterobj->title = $title;
        $bookchapterobj->content = $content;
        $bookchapterobj->contentformat = 1; // HTML format
        $bookchapterobj->subchapter = $subchapter?1:0;

        if (!$DB->insert_record('book_chapters', $bookchapterobj)) {
            return array(false, 'Error adding book chapter');
        }

        return array(true, '');
    }

    function post_create_setup() {
        $this->currpage = 1;

        foreach ($this->paramobj->chapters->chapter as $chapterobj) {
            $ret = $this->add_chapter((string)$chapterobj->title, (string)$chapterobj->content);
            if (!$ret[0]) {
                return $ret;
            }
            if (count($chapterobj->subchapters)) {
                foreach ($chapterobj->subchapters->chapter as $subchapterobj) {
                    $ret = $this->add_chapter((string)$subchapterobj->title, (string)$subchapterobj->content, true);
                    if (!$ret[0]) {
                        return $ret;
                    }
                }
            }
        }

        return array(true, '');
    }

    static function check_params_xml($paramsxmlobj) {
        if (empty($paramsxmlobj->title) ||
            empty($paramsxmlobj->description) ||
            empty($paramsxmlobj->numbering) ||
            !in_array($paramsxmlobj->numbering, array('none', 'numbers', 'bullets', 'indented')) ||
            !count($paramsxmlobj->chapters)) {
            return false;
        }
        return true;
    }
}
