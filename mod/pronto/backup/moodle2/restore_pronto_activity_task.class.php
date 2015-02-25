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
 * pronto restore task 
 */ 
 
require_once($CFG->dirroot . '/mod/pronto/backup/moodle2/restore_pronto_stepslib.php'); 
 
class restore_pronto_activity_task extends restore_activity_task {
 
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }
 
    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // pronto only has one structure step
       $this->add_step(new restore_pronto_activity_structure_step('pronto_structure', 'pronto.xml'));
    }
 
    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        //$contents = array();
 
        //$contents[] = new restore_decode_content('pronto', array('intro'), 'pronto');
 
        return array();
    }
 
    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
//        $rules = array();
// 
//        $rules[] = new restore_decode_rule('prontoVIEWBYID', '/mod/pronto/view.php?id=$1', 'course_module');
//        $rules[] = new restore_decode_rule('prontoINDEX', '/mod/pronto/index.php?id=$1', 'course');
// 
        return array();
 
    }
 
    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * pronto logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
//        $rules = array();
// 
//        $rules[] = new restore_log_rule('pronto', 'add', 'view.php?id={course_module}', 
//'{pronto}');
//        $rules[] = new restore_log_rule('pronto', 'update', 'view.php?id={course_module}', 
//'{pronto}');
//        $rules[] = new restore_log_rule('pronto', 'view', 'view.php?id={course_module}', 
//'{pronto}');
//        $rules[] = new restore_log_rule('pronto', 'choose', 'view.php?id={course_module}', 
//'{pronto}');
//        $rules[] = new restore_log_rule('pronto', 'choose again', 'view.php?id={course_module}', 
//'{pronto}');
//        $rules[] = new restore_log_rule('pronto', 'report', 'report.php?id={course_module}', 
//'{pronto}');
 
        return array();
    }
 
    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
//        $rules = array();
// 
//        // Fix old wrong uses (missing extension)
//        $rules[] = new restore_log_rule('pronto', 'view all', 'index?id={course}', null,
//                                        null, null, 'index.php?id={course}');
//        $rules[] = new restore_log_rule('pronto', 'view all', 'index.php?id={course}', null);
// 
        return array();
    }
 
}
