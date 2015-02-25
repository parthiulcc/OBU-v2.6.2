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

/**
 * Structure step to restore one pronto activity
 */
class restore_pronto_activity_structure_step extends restore_activity_structure_step {
 
    protected function define_structure() {
 
        $paths = array();
        //$userinfo = $this->get_setting_value('userinfo');
 
        $paths[] = new restore_path_element('pronto', '/activity/pronto');
        //$paths[] = new restore_path_element('pronto_option', '/activity/pronto/options/option');
        //if ($userinfo) {
        //    $paths[] = new restore_path_element('pronto_answer','/activity/pronto/answers/answer');
        //}
 
        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }
 
    protected function process_pronto($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
 
        $newitemid = $DB->insert_record('pronto', $data);

        $this->apply_activity_instance($newitemid);
    }
 
    //protected function process_pronto_option($data) {
    //    global $DB;
 
//        $data = (object)$data;
//        $oldid = $data->id;
// 
//        $data->prontoid = $this->get_new_parentid('pronto');
//        $data->timemodified = $this->apply_date_offset($data->timemodified);
// 
//        $newitemid = $DB->insert_record('pronto_options', $data);
//        $this->set_mapping('pronto_option', $oldid, $newitemid);
    //}
 
    //protected function process_pronto_answer($data) {
    //    global $DB;
 
//        $data = (object)$data;
//        $oldid = $data->id;
// 
//        $data->prontoid = $this->get_new_parentid('pronto');
//        $data->optionid = $this->get_mappingid('pronto_option', $oldid);
//        $data->userid = $this->get_mappingid('user', $data->userid);
//        $data->timemodified = $this->apply_date_offset($data->timemodified);
// 
//        $newitemid = $DB->insert_record('pronto_answers', $data);
//        // No need to save this mapping as far as nothing depend on it
//        // (child paths, file areas nor links decoder)
    //}
 
    //protected function after_execute() {
        // Add pronto related files, no need to match by itemname (just internally handled context)
        //$this->add_related_files('mod_pronto', 'intro', null);
    //}
}
