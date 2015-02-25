<?php
/*****************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2007 Blackboard Inc., All Rights Reserved.              *
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
 *      along with the Blackboard IM Moodle Integration;                      *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Frederic Mathiot                                                   *
 *                                                                            *
 * Date: 16 november 2007                                                     *
 *                                                                            *
 ******************************************************************************/

require_once( $CFG->dirroot."/mod/pronto/prontolib.php" );

class block_pronto extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_pronto');
        $this->version = PRONTO_PLUGIN_VERSION;
    }

    function get_content() {
        if ($this->content === NULL) {
            $this->page->requires->js_init_call('M.block_pronto.init');

            $this->content = new stdClass;

            $redirect_url = new moodle_url('/mod/pronto/redirect.php', array('sesskey' => sesskey()));
            $img_url = new moodle_url('/blocks/pronto/pix/prontoblock.jpg');

            $img_link = html_writer::empty_tag('img',
                array('src' => $img_url,
                'alt' => get_string('pluginname', 'block_pronto'),
                'height' => '154',
                'width' => '170'));
            $this->content->text = html_writer::link($redirect_url, $img_link, array('data-popup' => 'bbim-popup', 'target' => '_blank'));

            $this->content->footer = '';
        }

        return $this->content;
    }

    function has_config() {
        return false;
    }

    function applicable_formats() {
        return array (
            'site-index' => true,
            'course-view' => true,
            'course-view-social' => false,
            'mod' => true,
            'mod-quiz' => false
        );
    }

    function preferred_width() {
        return 200;
    }

    function hide_header() {
        return true;
    }
}
