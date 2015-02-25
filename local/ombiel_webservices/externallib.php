<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * External oMbiel web service api
 *
 * @copyright &copy; 2013 oMbiel
 * @author oMbiel
 * @package oMbiel_webservices
 * @version 1.0
 */


/** @var $CFG  */
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->libdir/filelib.php");

/**
 * Webservices for oMbiel Apps
 */
class local_ombiel_webservices extends external_api {

    /**
     * Get info for user dashboard
     * 
     * @param int $numberofitems The number of items in each list zero means all default is 10
     * @return array
     */
    public static function get_user_dashboard($numberofitems = 10) {
        global $CFG, $USER, $DB;
     
        $params = self::validate_parameters(self::get_user_dashboard_parameters(), array('numberofitems'=>$numberofitems));

        $userid = $USER->id;

        require_once($CFG->dirroot .'/mod/forum/lib.php');
        require_once($CFG->libdir . '/gradelib.php');
        
        $result = array();
        /**
         * Get courses and find due activities and unread forum posts
         * 
         * Activities are held in an array 'indexed' by time due concatenated with 
         * the cm id to prevent duplicates
         * 
         */
        
        $time = time();
        $activitiesdue = array();
        $unreadposts = array();
        $courseswithviewgrades= array();
        $courseList = enrol_get_users_courses($userid, true, 'id, fullname');
        $result['courses'] = array();
        
        if (!empty($courseList)) {
            foreach ($courseList as $course) { 
                // course for output
                $courseout = array();
                $courseout['id'] = $course->id;
                $courseout['fullname'] = $course->fullname;
                $courseout['grade'] = '';
                // Get grades for courses
                if (has_capability('moodle/grade:view', context_course::instance($course->id))) {
                    $courseswithviewgrades[] = $course->id;  

                    // Get course grade_item.
                    $course_item = grade_item::fetch_course_item($course->id);

                    // Get the stored grade.
                    $course_grade = new grade_grade(array('itemid' => $course_item->id, 'userid' => $userid), true);                
                    if (!$course_grade->is_hidden()) {
                        $courseout['grade'] = 
                                grade_format_gradevalue($finalgrade, $course_item, true, GRADE_DISPLAY_TYPE_PERCENTAGE);
                    }
                }
                // Get upcoming activites
                foreach(get_course_mods($course->id) as $cm) {
                    
                    if ($cm->visible) {
                        switch ($cm->modname) {
                            case 'assign':                            
                                $assign = $DB->get_record('assign', array('id'=>$cm->instance));   
                                if (!empty($assign->duedate)) {
                                    if ($assign->allowsubmissionsfromdate <= $time) {
                                        if (empty($assign->cutoffdate) or $assign->cutoffdate > $time) {
                                        // assignment is open
                                            if (!$DB->record_exists('assign_submission', array('assignment'=>$assign->id, 'userid'=>$userid))) {
                                                $activitiesdue[$assign->duedate.'-'.$cm->id] = array(
                                                    'courseid'=>$course->id,
                                                    'coursename'=>$course->fullname,
                                                    'cmid'=>$cm->id,
                                                    'section'=>$cm->section,
                                                    'modname'=>$cm->modname,
                                                    'name'=>$assign->name,
                                                    'type'=>get_string('assignment', 'local_ombiel_webservices'),
                                                    'duedate'=>$assign->duedate,
                                                    'cutoffdate'=>$assign->cutoffdate,
                                                    );
                                            }
                                        }
                                    }
                                }
                                break;
                            case 'assignment':
                                $assign = $DB->get_record('assignment', array('id'=>$cm->instance));   
                                if (!empty($assign->duedate)) {
                                    if ($assign->timeavailable <= $time) {
                                        if (empty($assign->preventlate) or $assign->timedue > $time) {
                                        // assignment is open
                                            if (!$DB->record_exists('assignment_submission', array('assignment'=>$assign->id, 'userid'=>$userid))) {
                                                $activitiesdue[$assign->timedue.'-'.$cm->id] = array(
                                                    'courseid'=>$course->id,
                                                    'coursename'=>$course->fullname,
                                                    'cmid'=>$cm->id,
                                                    'section'=>$cm->section,
                                                    'modname'=>$cm->modname,
                                                    'name'=>$assign->name,
                                                    'type'=>get_string('assignment', 'local_ombiel_webservices'),
                                                    'duedate'=>$assign->timedue,
                                                    'cutoffdate'=>empty($assign->preventlate)?false:$assign->timedue,
                                                    );       
                                            }
                                        }
                                    }
                                }
                                break;
                            case 'chat':
                                $chat = $DB->get_record('chat', array('id'=>$cm->instance)); 
                                if ($chat->schedule and $chat->chattime) {
                                    $activitiesdue[$chat->chattime.'-'.$cm->id] = array(
                                        'courseid'=>$course->id,
                                        'coursename'=>$course->fullname,
                                        'cmid'=>$cm->id,
                                        'section'=>$cm->section,
                                        'modname'=>$cm->modname,
                                        'name'=>$chat->name,
                                        'type'=>get_string('scheduledchat', 'local_ombiel_webservices'),
                                        'duedate'=>$chat->chattime,
                                        'cutoffdate'=>$chat->chattime,
                                        );                                
                                }
                                break;
                            case 'forum':
                                $forum = $DB->get_record('forum', array('id'=>$cm->instance)); 
                                if ($forum->assessed and $forum->assesstimestart <= $time and $forum->assesstimefinish > $time) {
                                    $activitiesdue[$forum->assesstimefinish.'-'.$cm->id] = array(
                                        'courseid'=>$course->id,
                                        'coursename'=>$course->fullname,
                                        'cmid'=>$cm->id,
                                        'section'=>$cm->section,
                                        'modname'=>$cm->modname,
                                        'name'=>$forum->name,
                                        'type'=>get_string('assessedforum', 'local_ombiel_webservices'),
                                        'duedate'=>$forum->assesstimefinish,
                                        'cutoffdate'=>$forum->assesstimefinish,
                                        );                                      
                                }

                                $count = forum_tp_count_forum_unread_posts($cm, $course);
                                if (!empty($count)) {
                                    $unreadposts[$forum->name] = array(
                                        'courseid'=>$course->id,
                                        'coursename'=>$course->fullname,
                                        'cmid'=>$cm->id,
                                        'section'=>$cm->section,
                                        'name'=>$forum->name,
                                        'count'=>$count,
                                        );      
                                }
                                break;
                            case 'lesson':
                                $lesson = $DB->get_record('lesson', array('id'=>$cm->instance)); 
                                if (empty($lesson->available) or $lesson->available <= $time) {
                                    if ($lesson->deadline >= $time) {
                                        $activitiesdue[$lesson->deadline.'-'.$cm->id] = array(
                                            'courseid'=>$course->id,
                                            'coursename'=>$course->fullname,
                                            'cmid'=>$cm->id,
                                            'section'=>$cm->section,
                                            'modname'=>$cm->modname,
                                            'name'=>$lesson->name,
                                            'type'=>get_string('lesson', 'local_ombiel_webservices'),
                                            'duedate'=>$lesson->deadline,
                                            'cutoffdate'=>$lesson->deadline,
                                            ); 
                                    }                                    
                                }
                                break;
                            case 'scorm':
                                $scorm = $DB->get_record('scorm', array('id'=>$cm->instance)); 
                                if ($scorm->timeopen and $scorm->timeopen <= $time){
                                    if ($scorm->timeclose > $time) {
                                        $activitiesdue["{$scorm->timeclose}-{$cm->id}"] = array(
                                            'courseid'=>$course->id,
                                            'coursename'=>$course->fullname,
                                            'cmid'=>$cm->id,
                                            'section'=>$cm->section,
                                            'modname'=>$cm->modname,
                                            'name'=>$scorm->name,
                                            'type'=>get_string('lesson', 'local_ombiel_webservices'),
                                            'duedate'=>$scorm->timeclose,
                                            'cutoffdate'=>$scorm->timeclose,
                                            );                                         
                                    }
                                }
                                break;
                            case 'quiz':
                                $quiz = $DB->get_record('quiz', array('id'=>$cm->instance)); 
                                if ($quiz->timeopen and $quiz->timeopen <= $time){
                                    $deadline = $quiz->timeclose + ($quiz->graceperiod * 60);
                                    if ($deadline > $time) {
                                        $activitiesdue["{$scorm->timeclose}-{$cm->id}"] = array(
                                            'courseid'=>$course->id,
                                            'coursename'=>$course->fullname,
                                            'cmid'=>$cm->id,
                                            'section'=>$cm->section,
                                            'modname'=>$cm->modname,
                                            'name'=>$scorm->name,
                                            'type'=>get_string('lesson', 'local_ombiel_webservices'),
                                            'duedate'=>$scorm->timeclose,
                                            'cutoffdate'=>$scorm->timeclose,
                                            );                                         
                                    }
                                }
                                break;
                        }
                    }
                }
                $result['courses'][] = $courseout;
            }
        }      
        ksort($activitiesdue);
        
        krsort($unreadposts);
        if (empty($numberofitems)) {
            $result['activitiesdue'] = $activitiesdue;
            $result['unreadposts'] =  $unreadposts;
            $gradelimit = '';
        } else {
            $result['activitiesdue'] = array_slice($activitiesdue, 0 , $numberofitems);
            $result['unreadposts'] =  array_slice($unreadposts, 0 , $numberofitems); 
            $gradelimit = "LIMIT {$numberofitems} ";
        }
        /** 
         * Get recent grades
         */
        $recentgrades = array();
        
        $gradesql = "SELECT i.id, g.finalgrade, g.feedback, g.feedbackformat "
                . "FROM {grade_items} i, {grade_grades} g "
                . "WHERE g.itemid = i.id AND g.timemodified IS NOT null AND g.userid = {$userid} AND i.courseid IN (".implode(',',$courseswithviewgrades).") "
                . "ORDER BY g.timemodified DESC {$gradelimit}";
        
        $gradelist = (array) $DB->get_records_sql($gradesql); 
        
        foreach($gradelist as $grade) {
            $item = new grade_item(array('id'=>$grade->id), true);
            if (empty($item->itemmodule) ) {
                $cmid = 0;
                $name = $courseList[$item->courseid]->fullname;
            } else {
                $cm = get_coursemodule_from_instance($item->itemmodule, $item->iteminstance);
                $cmid = $cm->id;
                $name = $item->itemname;
            }
            $recentgrades[] = array(
                'courseid'=>$item->courseid,
                'coursename'=>$courseList[$item->courseid]->fullname,
                'cmid'=>$cmid,
                'name'=>$name,
                'grade'=>grade_format_gradevalue($grade->finalgrade, $item, true),
                'range'=>number_format($item->grademin, 0) . "-" . number_format($item->grademax, 0),
                'percentage'=>grade_format_gradevalue($grade->finalgrade, $item, true, GRADE_DISPLAY_TYPE_PERCENTAGE),
                'feedback'=>format_text($grade->feedback, $grade->feedbackformat)
            );
        }
        
        $result['recentgrades'] = $recentgrades;
        return $result;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_user_dashboard_parameters() {
        return new external_function_parameters(
            array(
                'numberofitems' => new external_value(PARAM_INT, 'Number of items to get - all if not set.')
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_user_dashboard_returns() {
        return new external_single_structure(
            array(
                'courses' => 
                    new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'id' => new external_value(PARAM_INT, 'course id'),
                                'fullname' => new external_value(PARAM_TEXT, 'name of course'),
                                'grade' => new external_value(PARAM_TEXT, 'grade'),
                            )
                        ), 'List of all enroled courses
                        .'
                    ),
                'activitiesdue' => 
                    new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'courseid' => new external_value(PARAM_INT, 'course id'),
                                'coursename' => new external_value(PARAM_TEXT, 'name of course'),
                                'cmid' => new external_value(PARAM_INT, 'course module id'),
                                'section' => new external_value(PARAM_INT, 'section id'),
                                'modname' => new external_value(PARAM_TEXT, 'module name'),
                                'name' => new external_value(PARAM_TEXT, 'name of activity'),
                                'type' => new external_value(PARAM_TEXT, 'type of activity'),
                                'duedate' => new external_value(PARAM_INT, 'date the activity is due'),
                                'cutoffdate' => new external_value(PARAM_INT, 'last time the activity can be submitted'),
                            )
                        ), 'List of upcoming activities sorted in descending date due order
                        .'
                    ),
                'unreadposts' => 
                    new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'courseid' => new external_value(PARAM_INT, 'course id'),
                                'coursename' => new external_value(PARAM_TEXT, 'name of course'),
                                'cmid' => new external_value(PARAM_INT, 'course module id'),
                                'name' => new external_value(PARAM_TEXT, 'name of forum'),
                                'count' => new external_value(PARAM_INT, 'number of unread messages in forum'),
                            )
                        ), 'List of forums with unread posts in forum name order
                        .'
                    ),
                'recentgrades' => 
                    new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'courseid' => new external_value(PARAM_INT, 'course id'),
                                'coursename' => new external_value(PARAM_TEXT, 'name of course'),
                                'cmid' => new external_value(PARAM_INT, 'course module id (zero if grade is at course level)'),
                                'name' => new external_value(PARAM_TEXT, 'name of activity'),
                                'grade' => new external_value(PARAM_TEXT, 'grade'),
                                'range' => new external_value(PARAM_TEXT, 'range of grade'),
                                'percentage' => new external_value(PARAM_TEXT, 'grade as percentage'),
                                'feedback' => new external_value(PARAM_RAW, 'feedback from marker'),
                            )
                        ), 'List of recent grades sorted in descending date order
                        .'
                    )
                )
            );
    }
    /**
     * Get courses for the user
     * 
     * @param int $userid 
     * @return array
     */
    public static function  get_user_courses($userid = null) {
        global $USER;
        
        $params = self::validate_parameters(self::get_user_courses_parameters(), array('userid' => $userid));
        
        if (empty($userid)) {
            $userid = $USER->id;
        } elseif ($userid != $USER->id) {
            $usercontext = context_user::instance($userid, MUST_EXIST);
            if (!has_capability('moodle/user:viewdetails', $usercontext)) {
                throw new moodle_exception('errornoaccess', 'webservice');                
            }
        }
        
        $my_courses = enrol_get_users_courses($userid, true, 'id, fullname');
        
        $usercourses = array();
        if (!empty($my_courses)) {
            foreach ($my_courses as $course) {
                $usercourses[] = (array)$course;
            }
        }
        
        return $usercourses;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_user_courses_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'User ID')
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_user_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'course id'),
                    'fullname' => new external_value(PARAM_TEXT, 'course full name'),
                )
            ), 'List of user courses.'
        );
    }

    /*****
     * Returns a list of the sections within the course with the given id
     *
     * @static
     * @param int $courseid
     * @throws moodle_exception
     * @return array
     */
    public static function get_course_sections($courseid) {

        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . "/course/lib.php");

        // Validate the given parameter.
        $params = self::validate_parameters(self::get_course_sections_parameters(), array('courseid' => $courseid));

        $retvalue = array();
        
        $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
        
        $coursecontext = context_course::instance($course->id);
        $token = optional_param('wstoken', '', PARAM_RAW);
          
        if ($CFG->version >= 2012120300) { // Moodle 2.4 and above
            
            $retvalue['sections'] = array();
            require_once($CFG->dirroot . "/course/format/lib.php");
            $modinfo = get_fast_modinfo($course);
            $course = course_get_format($course)->get_course();
            
            if (!is_enrolled($coursecontext, $USER, '', true)) {
                throw new moodle_exception('userisnotenrolled', 'webservice');
            }
            foreach ($modinfo->get_section_info_all() as $section => $sectioninfo) {
                if ($section <= $course->numsections and $sectioninfo->uservisible) {
                    $sectionvalues = array();
                    $sectionvalues['id'] = $sectioninfo->id;
                    $sectionvalues['name'] = get_section_name($course, $sectioninfo);
                    $summary = file_rewrite_pluginfile_urls($sectioninfo->summary, 'webservice/pluginfile.php', $coursecontext->id, 'course',
                        'section', $sectioninfo->id);
                    $sectionvalues['summary'] = format_text($summary, $sectioninfo->summaryformat, array('filter'=>false));
                    $retvalue['sections'][] = $sectionvalues;
                }
            }         
        } else {
            $numsections = $course->numsections;
        
            $retvalue['sections'] = array();
            $sql = "SELECT *
                    FROM {course_sections}
                    WHERE course = :courseid
                    AND section <= :numsections ";
            $sections = $DB->get_records_sql($sql,array('courseid' => $courseid, 'numsections' => $numsections));

            if (!empty($sections)) {
                foreach ($sections as $s) {
                    if ($s->visible) {
                        $sectionvalues = array();
                        $sectionvalues['id'] = $s->id;
                        $sectionvalues['name'] = get_section_name($course, $s);
                        $summary = file_rewrite_pluginfile_urls($s->summary, 'webservice/pluginfile.php', $coursecontext->id, 'course',
                            'section', $s->id);
                        $sectionvalues['summary'] = format_text($summary, $s->summaryformat, array('filter'=>false));
                        $retvalue['sections'][] = $sectionvalues;
                    }
                }
            }
        }
        // If there is an echo 360 block on this course build a link to the echocenter
        if ($DB->record_exists('block_instances', array('blockname'=>'echo360_echocenter', 'parentcontextid'=>$coursecontext->id))) {
            $retvalue['echo360link'] = "{$CFG->wwwroot}/local/ombiel_webservices/login.php?wstoken={$token}&userid={$USER->id}&echo360id={$course->id}";
        }            
        $retvalue['courselink'] = "{$CFG->wwwroot}/local/ombiel_webservices/login.php?wstoken={$token}&userid={$USER->id}&courseid={$course->id}";
        
        return $retvalue;

    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_course_sections_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID')
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_course_sections_returns() {
        return new external_single_structure(
            array(
                'courselink' => new external_value(PARAM_URL, 'Link to native course page'),
                'echo360link' => new external_value(PARAM_URL, 'Link to echocenter', VALUE_OPTIONAL),
                'sections' => 
                    new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'id' => new external_value(PARAM_INT, 'section id'),
                                'name' => new external_value(PARAM_TEXT, 'name of section'),
                                'summary' => new external_value(PARAM_RAW, 'Summary of section content', VALUE_OPTIONAL),
                            )
                        ), 'List of section objects. A section has an id, a name, visible,a summary
                        .'
                    )
                )
            );
    }

    /*********
     * Gets the course modules within a section
     * 
     * @param $courseid
     * @throws moodle_exception
     * @return array
     */
    public static function get_section_content($sectionid) {

        global $CFG, $DB, $USER;

        require_once($CFG->dirroot . "/course/lib.php");
        require_once("$CFG->libdir/weblib.php");

        // Validate the given parameter.
        $params = self::validate_parameters(self::get_section_content_parameters(), array('sectionid' => $sectionid));

        $sectionRecord = $DB->get_record('course_sections', array('id'=>$sectionid), '*', MUST_EXIST);
        
        $course = $DB->get_record('course', array('id'=>$sectionRecord->course), '*', MUST_EXIST);
        
        $coursecontext = context_course::instance($course->id);
        
        $modinfo = get_fast_modinfo($course); 
        
        if (!is_enrolled($coursecontext, $USER, '', true)) {
            throw new moodle_exception('userisnotenrolled', 'webservice');
        }
        
        $sectionvalues = array();
        
        if ($CFG->version >= 2012120300) { // Moodle 2.4 and above
            require_once($CFG->dirroot . "/course/format/lib.php");
            $sectioninfo =  $modinfo->get_section_info($sectionRecord->section, MUST_EXIST);            
        } else {
            $sectioninfo = $sectionRecord;
        }
        $sectionvalues['id'] = $sectionid;
        $sectionvalues['name'] = get_section_name($course, $sectioninfo);
        $summary = file_rewrite_pluginfile_urls($sectioninfo->summary, 'webservice/pluginfile.php', $coursecontext->id, 'course',
                    'section', $sectioninfo->id);
        $sectionvalues['summary'] = format_text($summary, $sectioninfo->summaryformat, array('filter'=>false));
        
        $token = optional_param('wstoken', '', PARAM_RAW);
        $sectionvalues['baselink'] = "{$CFG->wwwroot}/local/ombiel_webservices/login.php?wstoken={$token}&userid={$USER->id}&cmid=";
        
        $sectionvalues['contents'] = array();
        foreach ($modinfo->sections[$sectionRecord->section] as $cmid) {
            $cm = $modinfo->cms[$cmid];
            if ($cm->uservisible) {
                $module = array();

                // Common info 
                $module['id'] = $cm->id;
                $module['name'] = format_string($cm->name, true);
                $module['modname'] = $cm->modname;
                $module['modplural'] = $cm->modplural;
                $module['modicon'] = $cm->get_icon_url()->out(false);
                $module['indent'] = $cm->indent;
                $instance = $DB->get_record($cm->modname, array('id'=>$cm->instance));       
                if (!empty($cm->showdescription) or $cm->modname == 'label') {
                    $cmcontext = context_module::instance($cm->id);
                    $module['description'] = file_rewrite_pluginfile_urls($instance->intro, 'webservice/pluginfile.php', $cmcontext->id, 'mod_'.$cm->modname, 'intro', null);
                }
                $baseurl = 'webservice/pluginfile.php';
                if ($cm->modname == 'panopto') {
                    require_once($CFG->dirroot . '/mod/panopto/locallib.php');
                    $module['contents'][0]['type'] = 'panopto';
                    $module['contents'][0]['content'] = urlencode(mod_panopto_get_full_panopto($instance, $cm, $course));
                    $module['contents'][0]['timemodified'] = $instance->timemodified;
                } else {
                    require_once($CFG->dirroot . '/mod/' . $cm->modname . '/lib.php');
                    // Call $modulename_export_contents
                    // ...(each module callback take care about checking the capabilities).
                    $getcontentfunction = $cm->modname . '_export_contents';
                    if (function_exists($getcontentfunction)) {
                        if ($contents = $getcontentfunction($cm, $baseurl)) {
                            $module['contents'] = $contents;
                        }
                    }
                }
                // Assign result to $sectioncontents.
                $sectionvalues['contents'][] = $module;

            }
        }
        return $sectionvalues;

    }

    /**
     * @return external_function_parameters
     */
    public static function get_section_content_parameters() {
        return new external_function_parameters(
            array(
                'sectionid' => new external_value(PARAM_INT, 'Section ID')
            )
        );
    }


    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_section_content_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'section id'),
                'name' => new external_value(PARAM_TEXT, 'name of section'),
                'summary' => new external_value(PARAM_RAW, 'Summary of section content', VALUE_OPTIONAL),
                'baselink' => new external_value(PARAM_URL, 'First part of link to native module page'),
                'contents' => 
                    new external_multiple_structure(
                        new external_single_structure(
                            array(
                                'id' => new external_value(PARAM_INT, 'activity id'),
                                'name' => new external_value(PARAM_TEXT, 'activity module name'),
                                'description' => new external_value(PARAM_RAW, 'activity description', VALUE_OPTIONAL),
                                'modicon' => new external_value(PARAM_URL, 'activity icon url'),
                                'modname' => new external_value(PARAM_PLUGIN, 'activity module type'),
                                'modplural' => new external_value(PARAM_TEXT, 'activity module plural name'),
                                'indent' => new external_value(PARAM_INT, 'number of identation in the site'),
                                'contents' => new external_multiple_structure(
                                    new external_single_structure(
                                        array(
                                            // Content info.
                                            'type' => new external_value(PARAM_TEXT, 'a file or a folder or external link'),
                                            'filename' => new external_value(PARAM_FILE, 'filename', VALUE_OPTIONAL),
                                            'filepath' => new external_value(PARAM_PATH, 'filepath', VALUE_OPTIONAL),
                                            'filesize' => new external_value(PARAM_INT, 'filesize', VALUE_OPTIONAL),
                                            'fileurl' => new external_value(PARAM_URL, 'downloadable file url', VALUE_OPTIONAL),
                                            'content' => new external_value(PARAM_RAW, 'Raw content, will be used when type is content',
                                                VALUE_OPTIONAL),
                                            'timecreated' => new external_value(PARAM_INT, 'Time created', VALUE_OPTIONAL),
                                            'timemodified' => new external_value(PARAM_INT, 'Time modified', VALUE_OPTIONAL),
                                            'sortorder' => new external_value(PARAM_INT, 'Content sort order', VALUE_OPTIONAL),

                                            // Copyright related info.
                                            'userid' => new external_value(PARAM_INT, 'User who added this content to moodle', VALUE_OPTIONAL),
                                            'author' => new external_value(PARAM_TEXT, 'Content owner', VALUE_OPTIONAL),
                                            'license' => new external_value(PARAM_TEXT, 'Content license', VALUE_OPTIONAL),
                                        )
                                    ), VALUE_DEFAULT, array()
                                )
                            )
                        ), 'List of section objects. A section has an id, a name, visible,a summary
                        .'
                    )
                )
        );
    }

    /**
     * Returns a list of assignments for the user with the given userid
     *
     * @param int $userid the id of the user whose assignments will be retrieved
     *
     * @return array
     */

    public static function  get_user_assignments($userid = null) {
        global $CFG, $DB, $USER;
        
        $params = self::validate_parameters(self::get_user_assignments_parameters(), array('userid' => $userid));
        
        if (empty($userid)) {
            $userid = $USER->id;
        } elseif ($userid != $USER->id) {
            $usercontext = context_user::instance($userid, MUST_EXIST);
            if (!has_capability('moodle/user:viewdetails', $usercontext)) {
                throw new moodle_exception('errornoaccess', 'webservice');                
            }
        }

        // Get all user courses.
        $my_courses = enrol_get_users_courses($userid, true);
        $userassignments = array();
        if (!empty($my_courses)) {
            foreach ($my_courses as $course) {
                $modinfo = get_fast_modinfo($course);
                $assignments = $modinfo->get_instances_of('assign');
                
                // Create.
                if (!empty($assignments)) {
                    foreach ($assignments as $cm) {
                        if ($cm->uservisible) {
                            $context = context_module::instance($cm->id);
                            $assign = new assign($context, $cm, $course);
                            $assignout = array();
                            $instance = $assign->get_instance();
                            $assignout['id'] = $cm->id;
                            $assignout['name'] = $cm->name;
                            if (!empty($cm->showdescription)) {
                                $assignout['description'] = file_rewrite_pluginfile_urls($instance->intro, 'webservice/pluginfile.php', $context->id, 'mod_assign', 'intro', null);
                            }
                            $assignout['courseid'] = $cm->course;
                            
                            $assignout['grade'] = $assign->get_user_grade($userid, false);
                            $submission = $assign->get_user_submission($userid, false);
                            $assignout['status'] = (!empty($submission) and $submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) ? 1 : 0;
                            $assignout['deadline'] = $instance->duedate;
                            $userassignments[] = $assignout;
                        }
                    }
                }
            }
        }
        return $userassignments;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_user_assignments_parameters() {
        return new external_function_parameters(
            array(

                'userid' => new external_value(PARAM_INT, 'User ID')

            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_user_assignments_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_RAW, 'assignment id'),
                    'name' => new external_value(PARAM_RAW, 'name of assignment'),
                    'description' => new external_value(PARAM_RAW, 'intro used for assignment', VALUE_OPTIONAL),
                    'courseid' => new external_value(PARAM_RAW, 'id of course that the assignment is in'),
                    'deadline' => new external_value(PARAM_RAW, 'timestamp of the deadline for the course'),
                    'status' => new external_value(PARAM_RAW, 'has the user created a submission for the assignment'),
                    'grade' => new external_value(PARAM_RAW, 'Grade'),

                )
            ), 'List of assignments the a user has been set in their various courses.'
        );
    }


    /**
     * Return all assignments for the current student in given course
     * @param int $courseid the id of the course that you want the assignments from
     * @return array An array of assignments.
     */
    public static function get_course_assignments($courseid)  {
        /**
         * @todo Add flag for all or new assignmentsDeleted
         */
        global $USER, $DB, $CFG;
        $params = self::validate_parameters(self::get_course_assignments_parameters(),array('courseid' => $courseid));
         
        $context = context_course::instance($courseid);
        
        if (!is_enrolled($context, $USER, '', true)) {
            throw new moodle_exception('userisnotenrolled', 'webservice');
        }
        $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
        
        $modinfo = get_fast_modinfo($course);
        $assignments = $modinfo->get_instances_of('assign');

        $assignmentsout = array();
        // Create.
        if (!empty($assignments)) {
            foreach ($assignments as $cm) {
                if ($cm->uservisible) {
                    $context = context_module::instance($cm->id);
                    $assign = new assign($context, $cm, $course);
                    $instance = $assign->get_instance();
                    $assignout = array();
                    $assignout['id'] = $cm->id;
                    $assignout['name'] = $cm->name;
                    if (!empty($cm->showdescription)) {
                        $assignout['description'] = file_rewrite_pluginfile_urls($instance->intro, 'webservice/pluginfile.php', $context->id, 'mod_assign', 'intro', null);
                    }
                    $assignout['courseid'] = $cm->course;
                    $assignout['grade'] = $assign->get_user_grade($USER->id, false);
                    $submission = $assign->get_user_submission($USER->id, false);
                    $assignout['status'] = (!empty($submission) and $submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) ? 1 : 0;
                    $assignout['deadline'] = $instance->duedate;
                    $assignmentsout[] = $assignout;
                }
            }
        }
        return $assignmentsout;
 
    }

    /**
     * @return external_function_parameters
     */
    public static function get_course_assignments_parameters(){
        return new external_function_parameters(
        array(
            'courseid' => new external_value(PARAM_INT, 'course id'),
        )
        );

    }

    /**
     * @return external_multiple_structure
     */
    public static function get_course_assignments_returns() {
        return new external_multiple_structure(
        new external_single_structure(
                array(
                    'id' => new external_value(PARAM_RAW, 'assignment id'),
                    'name' => new external_value(PARAM_RAW, 'name of assignment'),
                    'description' => new external_value(PARAM_RAW, 'intro used for assignment', VALUE_OPTIONAL),
                    'courseid' => new external_value(PARAM_RAW, 'id of course that the assignment is in'),
                    'deadline' => new external_value(PARAM_RAW, 'timestamp of the deadline for the course'),
                    'status' => new external_value(PARAM_RAW, 'has the user created a submission for the assignment'),
                    'grade' => new external_value(PARAM_RAW, 'Grade'),

                )
            ), 'List of assignments the a user has been set in this course.'
        );

    }

    /**
     * Returns assignment information based on the course module id provided. 
     *
     * @param int $cmid the course module id of the assignment
     *
     * @return array|\stdClass
     */
    public static function  get_cm_assignment($cmid) {

        global $USER, $CFG, $DB;
        /**
         * @todo - return more information
         */
        $params = self::validate_parameters(self::get_cm_assignment_parameters(), array('cmid' => $cmid));

        require_once ($CFG->dirroot . '/mod/assignment/lib.php');

        $cm = $DB->get_record('course_modules', array('id' => $cmid), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);   
        /**
         * @todo show legacy assignments on newer Moodles
         */
        if ($CFG->version >= 2012120300) { // Moodle 2.4 and above
            require_once ($CFG->dirroot . '/mod/assign/locallib.php');
        
            if (!$DB->record_exists('modules', array('id'=>$cm->module, 'name'=>'assign'))){
                throw new moodle_exception('notanassignment', 'webservice');
            } 
            $instance = $DB->get_record('assign', array('id'=>$cm->instance), '*', MUST_EXIST);
            $context = context_module::instance($cmid);

            if (!is_enrolled($context, $USER, '', true)) {
                throw new moodle_exception('userisnotenrolled', 'webservice');
            }

            $context = context_module::instance($cm->id);
            $assign = new assign($context, $cm, $course);

            $assignout = array();
            $assignout['name'] = $instance->name;

            $assignout['description'] = file_rewrite_pluginfile_urls($instance->intro, 'webservice/pluginfile.php', $context->id, 'mod_assign', 'intro', null);

            $grade  = $assign->get_user_grade($USER->id, false);
            $assignout['grade'] = (empty($grade))?'':$grade->grade;
            $submission = $assign->get_user_submission($USER->id, false);
            $assignout['status'] = (!empty($submission) and $submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) ? 1 : 0;
            $assignout['deadline'] = $instance->duedate;
        } else {
            
            if (!$DB->record_exists('modules', array('id'=>$cm->module, 'name'=>'assignment'))){
                throw new moodle_exception('notanassignment', 'webservice');
            } 
            $instance = $DB->get_record('assignment', array('id'=>$cm->instance), '*', MUST_EXIST);
            $context = context_module::instance($cmid);

            if (!is_enrolled($context, $USER, '', true)) {
                throw new moodle_exception('userisnotenrolled', 'webservice');
            }
            $assign = new stdClass();
            $assign->id = $instance->id;
            $assign->name = $instance->name;
            $assign->description = file_rewrite_pluginfile_urls($instance->intro, 'webservice/pluginfile.php', $context->id, 'mod_assignment', 'intro', null);
            $assign->deadline = $instance->timedue;
            $assignout = (array)$assign;

        }
        
        return $assignout;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_cm_assignment_parameters() {
        return new external_function_parameters(
            array(
                'cmid' => new external_value(PARAM_INT, 'Course module id')
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_cm_assignment_returns() {
        return new external_single_structure(
            array(
                'name' => new external_value(PARAM_RAW, 'name of assignment'),
                'description' => new external_value(PARAM_RAW, 'intro used for assignment', VALUE_OPTIONAL),
                'deadline' => new external_value(PARAM_RAW, 'timestamp of the deadline for the course'),
                'status' => new external_value(PARAM_RAW, 'has the user created a submission for the assignment', VALUE_OPTIONAL),
                'grade' => new external_value(PARAM_RAW, 'Grade', VALUE_OPTIONAL),
            )
        );
    }
     /**
     * @static returns a array containing student grades
     * @param int $userid the id of the user whose grades will be returned
     * @return array
     */
    public static function  get_user_grades($userid = null) {

        global $CFG, $USER;
        
        $params = self::validate_parameters(self::get_user_grades_parameters(), array('userid' => $userid));

        if (empty($userid)) {
            $userid = $USER->id;
        } 
        
        require_once($CFG->libdir . '/gradelib.php');

        // Get all user courses.
        $my_courses = enrol_get_users_courses($userid, false, 'id, shortname, fullname, showgrades, visible');

        $coursegradesout = array();

        if (!empty($my_courses)) {
            foreach ($my_courses as $course) {
                if ($course->showgrades) {
                    
                    $coursecontext = context_course::instance($course->id);
                    if ($userid != $USER->id){
                        if (!has_capability('moodle/grade:viewall', $coursecontext)) {
                            continue;
                        }
                    } else {
                        if (!has_capability('moodle/grade:view', $coursecontext)) {
                            continue;              
                        }                    
                    }

                    // Get course grade_item.
                    $course_item = grade_item::fetch_course_item($course->id);

                    // Get the stored grade.
                    $course_grade = new grade_grade(array('itemid' => $course_item->id, 'userid' => $userid), true);                
                    if (!$course_grade->is_hidden()) {

                        $course_grade->grade_item =& $course_item;
                        $finalgrade = $course_grade->finalgrade;
                        $grademax = $course_grade->grade_item->grademax;
                        $grademin = $course_grade->grade_item->grademin;

                        $gradeout = new stdClass();
                        $gradeout->id = $course->id;
                        $gradeout->fullname = $course->fullname;
                        $gradeout->grade = grade_format_gradevalue($finalgrade, $course_item, true);
                        $gradeout->range = number_format($grademin, 0) . "&ndash;" . number_format($grademax, 0);
                        $gradeout->percentage = grade_format_gradevalue($finalgrade, $course_item, true, GRADE_DISPLAY_TYPE_PERCENTAGE);
                        $gradeout->feedback = format_text($course_grade->feedback, $course_grade->feedbackformat);

                        $coursegradesout[] = (array)$gradeout;
                    }
            
                }
            }
        }

        return $coursegradesout;

    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_user_grades_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'User ID')
            )
        );
    }


    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_user_grades_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'course id'),
                    'fullname' => new external_value(PARAM_TEXT, 'course fullname'),
                    'grade' => new external_value(PARAM_TEXT, 'course grade '),
                    'range' => new external_value(PARAM_TEXT, 'course range '),
                    'percentage' => new external_value(PARAM_TEXT, 'course percentage '),
                    'feedback' => new external_value(PARAM_RAW, 'course feedback '),
                )
            ), 'List of courses the user is in and the grades the user has achieved.'
        );
    }


    /**
     * @static returns a array containing student grades
     * @param $courseid
     * @param int $userid the id of the user whose grades will be returned
     * @return array
     */
    public static function  get_course_grades($courseid, $userid = null) {

        global $CFG, $USER;
        // If user id is empty pass the global user id.
        $userid = (empty($userid)) ? $USER->id : $userid;
        $params = self::validate_parameters(self::get_course_grades_parameters(), array('courseid' => $courseid,
            'userid' => $userid));

        $context = context_course::instance($courseid);
        
        if ($userid != $USER->id){
            if (!has_capability('moodle/grade:viewall', $context)) {
                throw new moodle_exception('errornoaccess', 'webservice');    
            }
        } else {
            if (!has_capability('moodle/grade:view', $context)) {
                throw new moodle_exception('errornoaccess', 'webservice');                
            }                    
        }
        require_once($CFG->dirroot . '/grade/lib.php');
        require_once($CFG->dirroot . '/grade/report/user/lib.php');

        $gpr = new grade_plugin_return(array('type' => 'report', 'plugin' => 'user', 'courseid' => $courseid, 'userid' => $userid));

        // Grab the grade_tree for this course.
        $report = new grade_report_user($courseid, $gpr, $context, $userid);
        $report->fill_table();
        $grades = array();

        foreach ($report->tabledata as $data) {
            if (isset($data['grade'])) {
                $grade['gradeitem'] = strip_tags($data['itemname']['content']);
                $grade['grade'] = ($data['grade']['content'] == 'Error') ? '-' : $data['grade']['content'];

                $grade['range'] = $data['range']['content'];
                $grade['percentage'] = ($data['percentage']['content'] == 'Error') ? '-' : $data['percentage']['content'];
                $grade['feedback'] = $data['feedback']['content'];
                $grades[] = $grade;

            }
        }

        return $grades;

    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_course_grades_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'userid' => new external_value(PARAM_INT, 'User ID')
            )
        );
    }


    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_course_grades_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'gradeitem' => new external_value(PARAM_RAW, 'grade item name'),
                    'grade' => new external_value(PARAM_RAW, 'course grade '),
                    'range' => new external_value(PARAM_RAW, 'course range '),
                    'percentage' => new external_value(PARAM_RAW, 'course percentage '),
                    'feedback' => new external_value(PARAM_RAW, 'course feedback ')

                )
            ), 'List of grades achieved for individual work in a course.'
        );
    }

    /**
     * @static returns a array containing all forums within a course
     * @param int $courseid the id of the course whose forums will be retrieved
     * @param null $userid
     * @return array
     */
    public static function  get_course_forums($courseid) {

        global $CFG, $USER;
        
        $params = self::validate_parameters(self::get_course_forums_parameters(), array('courseid' => $courseid));

        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $records = forum_get_readable_forums($USER->id, $courseid);
      
        $courseforums = array();
        if (!empty($records)) {
            foreach ($records as $f) {
                $forum = new stdClass();

                $forum->id = $f->cm->id;
                $forum->name = $f->name;
                if (!empty($f->cm->showdescription)) {
                    $context = context_module::instance($f->cm->id);
                    $forum->description = file_rewrite_pluginfile_urls($f->intro, 'webservice/pluginfile.php', $context->id, 'mod_forum', 'intro', null);
                };

                $courseforums[] = (array)$forum;
            }
        }

        return $courseforums;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_course_forums_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_course_forums_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'coursemodule id '),
                    'name' => new external_value(PARAM_TEXT, 'name'),
                    'description' => new external_value(PARAM_RAW, 'intro used for forum', VALUE_OPTIONAL),
                )
            ), 'Forums available for the user to view in this course.'
        );
    }


    /**
     * @static returns a array containing all forums within a course
     * @param $coursemoduleid
     * @return array
     * @internal param int $courseid the id of the course whose forums will be retrieved
     */
    public static function  get_cm_forum($coursemoduleid) {

        global $USER, $CFG, $DB;

        $params = self::validate_parameters(self::get_cm_forum_parameters(), array('coursemoduleid' => $coursemoduleid));

        require_once ($CFG->dirroot . '/mod/forum/lib.php');

        $cm = $DB->get_record('course_modules', array('id' => $coursemoduleid),'*', MUST_EXIST);
        
        if (!$DB->record_exists('modules', array('id'=>$cm->module, 'name'=>'forum'))){
            throw new moodle_exception('notaforum', 'webservice');
        } 
        $context = context_module::instance($coursemoduleid); 
        
        if (!is_enrolled($context, $USER, '', true)) {
            throw new moodle_exception('userisnotenrolled', 'webservice');
        }
        
        $groupmode    = groups_get_activity_groupmode($cm, $cm->course);
        $currentgroup = groups_get_activity_group($cm);
        
        $courseforum = array();
        $f = $DB->get_record('forum', array('id' => $cm->instance), '*', MUST_EXIST);
        if (!empty($f)) {
            $forum = new stdClass();
            $forum->id = $f->id;
            $forum->coursemoduleid = $cm->id;
            $forum->name = $f->name;
            $context = context_module::instance($cm->id);
            $forum->description = file_rewrite_pluginfile_urls($f->intro, 'webservice/pluginfile.php', $context->id, 'mod_forum', 'intro', null);
            $forum->canpost = forum_user_can_post_discussion($f, $currentgroup, $groupmode, $cm, $context);
            $courseforum = (array)$forum;
        }

        return $courseforum;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_cm_forum_parameters() {
        return new external_function_parameters(
            array(
                'coursemoduleid' => new external_value(PARAM_INT, 'Course module id'),
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_cm_forum_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_TEXT, 'forum id for add forum discussion'),
                'name' => new external_value(PARAM_TEXT, 'name'),
                'description' => new external_value(PARAM_RAW, 'intro used for forum', VALUE_OPTIONAL),
                'canpost' => new external_value(PARAM_BOOL, 'Whether this user can add a post'),
            )
        );
    }

    /**
     * @static returns a array containing all discussions within a forum
     * @param int $coursemoduleid the coursemodule id of the forum whose discussions will be returned
     * will be returned
     */
    public static function get_forum_discussions($coursemoduleid) {

        global $CFG, $DB, $USER;

        $params = self::validate_parameters(self::get_forum_discussions_parameters(), array('coursemoduleid' => $coursemoduleid));

        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $coursemodule = $DB->get_record('course_modules', array('id' => $coursemoduleid), '*', MUST_EXIST);
        
        if (!$DB->record_exists('modules', array('id'=>$coursemodule->module, 'name'=>'forum'))){
            throw new moodle_exception('notaforum', 'webservice');
        }       

        $context = context_module::instance($coursemoduleid); 
        if (!is_enrolled($context, $USER, '', true)) {
            throw new moodle_exception('userisnotenrolled', 'webservice');
        }
        
        $records = forum_get_discussions($coursemodule);

        $recordsreplies = forum_count_discussion_replies($coursemodule->instance);

        $discussionrecords = array();

        if (!empty($records)) {
            foreach ($records as $d) {
                $discussion = new stdClass();
                $discussion->id = $d->id;
                $discussion->name = $d->name;
                $discussion->author = $d->firstname . ' ' . $d->lastname;
                $discussion->content = $d->message;
                $discussion->discussion = $d->discussion;

                if (isset($recordsreplies[$d->discussion])) {
                    $discussion->replies = $recordsreplies[$d->discussion]->replies;
                    $post = forum_get_post_full($recordsreplies[$d->discussion]->lastpostid);
                    $discussion->lastreply = $post->modified;
                } else {
                    $discussion->replies = 0;
                    $discussion->lastreply = 0;
                }

                $discussionrecords[] = (array)$discussion;
            }

        }

        return $discussionrecords;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_forum_discussions_parameters() {
        return new external_function_parameters(
            array(
                'coursemoduleid' => new external_value(PARAM_INT, 'Course module id of the discussion'),
            )
        );
    }


    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_forum_discussions_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'post id'),
                    'name' => new external_value(PARAM_TEXT, 'name of discussion'),
                    'discussion' => new external_value(PARAM_INT, 'discussion id '),
                    'author' => new external_value(PARAM_TEXT, 'author of discussion'),
                    'content' => new external_value(PARAM_RAW, 'content of discussion'),
                    'replies' => new external_value(PARAM_INT, 'the number of replies to this dsicussion'),
                    'lastreply' => new external_value(PARAM_INT,
                        'timestamp of the date & time of the last reply to this discussion'),
                )
            ), 'List of discussion in the given forum'
        );
    }


    /**
     * @static returns a array containing all posts within a discussion
     * @param int $discussionid the id of the discussion whose psts will be returned
     * will be returned
     * @return array
     */
    public static function  get_discussion_posts($discussionid) {

        global $CFG, $DB;

        $params = self::validate_parameters(self::get_discussion_posts_parameters(), array('discussionid' => $discussionid));

        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $discussion = $DB->get_record('forum_discussions', array('id' => $discussionid));

        $forum      = $DB->get_record('forum', array('id' => $discussion->forum));
        $cm         = get_coursemodule_from_instance('forum', $forum->id);
        $context    = context_module::instance($cm->id);
        
        if (!has_capability('mod/forum:viewdiscussion', $context)) { /// User must have perms to view discussions
            throw new moodle_exception('errornoaccess', 'webservice');  
        }
        
        $records = forum_get_discussion_posts($discussionid, 'order by modified', $discussion->forum);
        $postrecords = array();

        if (!empty($records)) {
            foreach ($records as $p) {
                $post = new stdClass();
                $post->id = $p->id;
                $post->parent = $p->parent;
                $post->subject = $p->subject;
                $post->content = $p->message;
                $post->author = $p->firstname . " " . $p->lastname;
                $post->date = $p->modified;

                $postrecords[] = (array)$post;
            }
        }

        return $postrecords;

    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_discussion_posts_parameters() {
        return new external_function_parameters(
            array(
                'discussionid' => new external_value(PARAM_INT, 'Disscussion id'),
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_discussion_posts_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id'),
                    'parent' => new external_value(PARAM_INT, 'parent post id'),
                    'subject' => new external_value(PARAM_TEXT, 'subject of post'),
                    'author' => new external_value(PARAM_TEXT, 'author of discussion'),
                    'content' => new external_value(PARAM_RAW, 'content of discussion'),
                    'date' => new external_value(PARAM_INT, 'timestamp of the date & time of the post was last modified'),
                )
            ), 'List of discussion in the given forum'
        );
    }


    /**
     * @static allows a discussion to be added to the forum with the given id
     * @param int $forumid the id of the forum that the discussion will be added to
     * @param int $userid  the id fo the user who is creating the discussion
     * @param string $subject the subject of the discussion
     * @param string $message the message in the discussion post
     * @param int $mailnow should all other forum users be mailed about this post
     *
     * return int id of post or false
     * @return array
     */
    public static function  add_forum_discussion($forumid, $subject, $message, $mailnow = 0) {

        global $CFG, $DB;
        
        $params = self::validate_parameters(self::add_forum_discussion_parameters(), array('forumid' => $forumid,
            'subject' => $subject, 'message' => $message, 'mailnow' => $mailnow));

        $result = array();

        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $forum = $DB->get_record('forum', array('id' => $forumid));
        $cm = get_coursemodule_from_instance('forum', $forum->id, $forum->course);

        //check if the user can  post a discussion if not return 0
        if (forum_user_can_post_discussion($forum, null, -1, $cm)) {

            $discussion = new stdClass();
            $discussion->forum = $forumid;
            $discussion->name = $subject;
            $discussion->message = $message;
            // Need to check the other possible values for these two.
            $discussion->messageformat = 1;
            $discussion->messagetrust = 0;
            $discussion->mailnow = $mailnow;
            $discussion->course = $forum->course;
            $message = '';

            $result['result'] = forum_add_discussion($discussion, null, $message);
        } else {
            $result['result'] = 0;
        }

        return $result;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function add_forum_discussion_parameters() {
        return new external_function_parameters(
            array(
                'forumid' => new external_value(PARAM_INT, 'Forum id'),
                'subject' => new external_value(PARAM_TEXT, 'subject id'),
                'message' => new external_value(PARAM_RAW, 'discussion message'),
                'mailnow' => new external_value(PARAM_INT, 'should all users be mailed now to inform them of this post'),
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function add_forum_discussion_returns() {
        return new external_single_structure(
            array(
                'result' => new external_value(PARAM_INT, 'result of discussion insert'),
            )
        );
    }

    /**
     * @static allows a discussion to be added to the forum with the given id
     * @param int $discussionid the id of the discussion the post will be added to
     * @param int $parentid the id of the post that the post is in reply should be 0 if not in reply to
     *            a post
     * @param string $subject the subject of the post
     * @param string $message the message in the post
     *
     * return int id of post or false
     * @return array
     */
    public static function  add_discussion_post($discussionid, $subject, $message, $parentid = 0) {

        global $CFG, $DB;

        $params = self::validate_parameters(self::add_discussion_post_parameters(), array('discussionid' => $discussionid,
            'subject' => $subject, 'message' => $message, 'parentid' => $parentid));

        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $result = array();

        $discussion = $DB->get_record('forum_discussions', array('id' => $discussionid));

        $forum = $DB->get_record('forum', array('id' => $discussion->forum));
        $cm = get_coursemodule_from_instance('forum', $forum->id, $forum->course);
        $course = $DB->get_record('course', array('id' =>  $forum->course));

        if (forum_user_can_post($forum, $discussion, null, $cm, $course)) {
            if (empty($parentid)) {
                $parentid = (!empty($discussion)) ? $discussion->firstpost : 0;
            }

            $post = new stdClass();
            $post->course = $discussion->course;
            $post->discussion = $discussionid;
            $post->subject = $subject;
            $post->message = $message;
            $post->messageformat = 1;
            $post->parent = $parentid;
            $post->itemid = 0;
            $message = '';
            $mform = null;

            $result['result'] = forum_add_new_post($post, $mform, $message);
        } else {
            $result['result'] = 0;
        }



        return $result;
    }


    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function add_discussion_post_parameters() {
        return new external_function_parameters(
            array(
                'discussionid' => new external_value(PARAM_INT, 'Discussion id'),
                'subject' => new external_value(PARAM_TEXT, 'subject id'),
                'message' => new external_value(PARAM_RAW, 'post message'),
                'parentid' => new external_value(PARAM_INT, 'parent post'),
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function add_discussion_post_returns() {
        return new external_single_structure(
            array(
                'result' => new external_value(PARAM_INT, 'result of post insert'),
            )
        );
    }

    /**
     * 
     * @param int $userid the id of the user whose forums will be returned
     *
     *
     * return int id of post or false
     * @return array
     */
    public static function  get_user_forums($userid = null) {

        global $CFG, $DB, $USER;
        
        $params = self::validate_parameters(self::get_user_forums_parameters(), array('userid' => $userid));

        if (empty($userid)) {
            $userid = $USER->id;
        } elseif ($userid != $USER->id) {
            $usercontext = context_user::instance($userid, MUST_EXIST);
            if (!has_capability('moodle/user:viewdetails', $usercontext)) {
                throw new moodle_exception('errornoaccess', 'webservice');                
            }
        }
                // Get all user courses.
        $my_courses = enrol_get_users_courses($userid, true);
        $userforums = array();
        if (!empty($my_courses)) {
            foreach ($my_courses as $course) {
                $modinfo = get_fast_modinfo($course);
                $forums = $modinfo->get_instances_of('forum');
                
                // Create.
                if (!empty($forums)) {
                    foreach ($forums as $cm) {
                        $instance = $DB->get_record('forum', array('id'=>$cm->instance), '*', MUST_EXIST);
                        $forumout = array();
                        $forumout['id'] = $instance->id;
                        $forumout['name'] = $instance->name;
                        if (!empty($cm->showdescription)) {
                            $context = context_module::instance($cm->id);
                            $forumout['description'] = file_rewrite_pluginfile_urls($instance->intro, 'webservice/pluginfile.php', $context->id, 'mod_forum', 'intro', null);
                        }
                        $forumout['courseid'] = $cm->course;
                        $forumtracked = forum_tp_is_tracked($instance);
                        if (!empty($forumtracked)) { 
                            $forumout['unreadposts'] = forum_tp_count_forum_unread_posts($cm, $course);  
                        } else {
                            $forumout['unreadposts'] = 0;
                        }
                        $userforums[] = $forumout;
                    }
                }
            }
        }

        return $userforums;
    }


    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_user_forums_parameters() {
        return new external_function_parameters(
            array(
                'userid' => new external_value(PARAM_INT, 'user id'),
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_user_forums_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id'),
                    'name' => new external_value(PARAM_TEXT, 'name of forum'),
                    'description' => new external_value(PARAM_RAW, 'intro', VALUE_OPTIONAL),
                    'courseid' => new external_value(PARAM_INT, 'courseid'),
                    'unreadposts' => new external_value(PARAM_INT, 'number of unread posts (if forum tracked)'),
                )
            ), 'List of forums that the user can post in'
        );
    }

    /**
     * 
     * @param int $courseid the id of the course that you want the news from
     * @return array course module id.
     */
    public static function get_coursenews($courseid = null) {
        global $CFG, $USER;

        $courseid = (empty($courseid))?SITEID:$courseid;
        
        $params = self::validate_parameters(self::get_coursenews_parameters(), array('courseid' => $courseid));
        
        $context = context_course::instance($courseid);
        
        if (!is_enrolled($context, $USER, '', true)) {
            throw new moodle_exception('userisnotenrolled', 'webservice');
        }

        require_once($CFG->dirroot .'/mod/forum/lib.php');

        $newsforum = forum_get_course_forum($courseid, 'news');
        $cm = get_coursemodule_from_instance('forum', $newsforum->id);
        
        return array('coursemoduleid'=>$cm->id);

    }


    /**
     * @return external_function_parameters
     */
    public static function get_coursenews_parameters(){
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
            )
        );

    }

    /**
     * @return external_multiple_structure
     */
    public static function get_coursenews_returns() {
        return new external_single_structure(
            array(
                'coursemoduleid' => new external_value(PARAM_INT, 'Course module id')
            ), 'Course news coursemodule '
        );

    }

    /**
     * @param $coursemoduleid
     * @return array
     *
     *
     * return int id of post or false
     */
    public static function  get_cm_choice($coursemoduleid) {

        global $CFG, $DB, $USER;

        $params = self::validate_parameters(self::get_cm_choice_parameters(), array('coursemoduleid' => $coursemoduleid));

        require_once($CFG->dirroot . '/mod/choice/lib.php');

        $cm = $DB->get_record('course_modules', array('id' => $coursemoduleid), '*', MUST_EXIST);

        if (!$DB->record_exists('modules', array('id'=>$cm->module, 'name'=>'choice'))){
            throw new moodle_exception('notachoice', 'webservice');
        } 
        $context = context_module::instance($coursemoduleid); 
        
        if (!is_enrolled($context, $USER, '', true)) {
            throw new moodle_exception('userisnotenrolled', 'webservice');
        }
        
        $choice = array();
        $choicerecord = choice_get_choice($cm->instance);

        $c = new   stdClass();
        $c->id = $cm->instance;
        $c->name = $choicerecord->name;
        $context = context_module::instance($cm->id);
        $c->description = file_rewrite_pluginfile_urls($choicerecord->intro, 'webservice/pluginfile.php', $context->id, 'mod_choice', 'intro', null);
        $c->timeavailable = $choicerecord->timeopen;

        $choice = (array)$c;
            
        return $choice;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_cm_choice_parameters() {
        return new external_function_parameters(
            array(
                'coursemoduleid' => new external_value(PARAM_INT, 'course module id'),
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_cm_choice_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'id'),
                'name' => new external_value(PARAM_TEXT, 'name of choice'),
                'description' => new external_value(PARAM_RAW, 'intro', VALUE_OPTIONAL),
                'timeavailable' => new external_value(PARAM_INT, 'timeavailable'),
            )
        );
    }

    /**
     * @static returns a list of options for the choice with the given if
     * @param int $choiceid the id of the choice whose options will be returned
     *
     * return array  of options or false
     * @return array
     */
    public static function  get_choice_options($choiceid) {

        global $CFG, $DB, $USER;

        $params = self::validate_parameters(self::get_choice_options_parameters(), array('choiceid' => $choiceid));

        $cm         = get_coursemodule_from_instance('choice', $choiceid);
        $context    = context_module::instance($cm->id);
        if (!is_enrolled($context, $USER, '', true)) {
            throw new moodle_exception('userisnotenrolled', 'webservice');
        } 
        
        require_once($CFG->dirroot . '/mod/choice/lib.php');
        $choiceoptions = $DB->get_records("choice_options", array("choiceid" => $choiceid));
        $options = array();
        
        if (!empty($choiceoptions)) {
            foreach ($choiceoptions as $op) {
                $opt = new stdClass();
                $opt->id = $op->id;
                $opt->option = $op->text;
                $opt->maxanswers = $op->maxanswers;
                $opt->count = $DB->count_records('choice_answers', array('choiceid' => $choiceid, 'optionid' => $op->id));
                $options[] = (array)$opt;
            }
        }

        return $options;
    }


    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_choice_options_parameters() {
        return new external_function_parameters(
            array(
                'choiceid' => new external_value(PARAM_INT, 'choice id'),
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_choice_options_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id'),
                    'option' => new external_value(PARAM_RAW, 'the option'),
                    'maxanswers' => new external_value(PARAM_INT, 'max amount of answers for this option'),
                    'count' => new external_value(PARAM_INT, 'count of responses'),
                )
            ), 'List of options for the choice'
        );
    }


    /**
     * @static submits a user response to a choice
     * @param int $optionid the id of option selected by the user
     * @param int $coursemoduleid id of the coursemodule relating to the  choice record
     *
     * return array  of options or false
     */
    public static function  user_choice_response($optionid, $coursemoduleid) {
        global $CFG, $DB, $USER;
        
        $params = self::validate_parameters(self::user_choice_response_parameters(), array('optionid' => $optionid,
            'coursemoduleid' => $coursemoduleid));
        require_once($CFG->dirroot . '/mod/choice/lib.php');
        
        $cm = $DB->get_record("course_modules", array("id" => $coursemoduleid), '*', MUST_EXIST);
        
        $context = context_module::instance($coursemoduleid); 
        
        if (!is_enrolled($context, NULL, 'mod/choice:choose', true)) {
            throw new moodle_exception('userisnotenrolled', 'webservice');
        }
        
        $choice = $DB->get_record("choice", array("id" => $cm->instance), '*', MUST_EXIST);
        $course = $DB->get_record("course", array("id" => $cm->course), '*', MUST_EXIST);
        choice_user_submit_response($optionid, $choice, $USER->id, $course, $cm);
        return array('result'=>true);
    }


    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function user_choice_response_parameters() {
        return new external_function_parameters(
            array(
                'optionid' => new external_value(PARAM_INT, 'option id'),
                'coursemoduleid' => new external_value(PARAM_INT, 'course module id'),
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function user_choice_response_returns() {
        return new external_single_structure(array(
                'result' => new external_value(PARAM_BOOL, 'result'),));
    }
    
    /**
     * @param $coursemoduleid
     * @return array
     *
     *
     * return int id of post or false
     */
    public static function  get_cm_page($coursemoduleid) {

        global $CFG, $DB, $USER;

        $params = self::validate_parameters(self::get_cm_page_parameters(), array('coursemoduleid' => $coursemoduleid));

        require_once($CFG->dirroot . '/mod/page/lib.php');

        $cm = $DB->get_record('course_modules', array('id' => $coursemoduleid), '*', MUST_EXIST);

        if (!$DB->record_exists('modules', array('id'=>$cm->module, 'name'=>'page'))){
            throw new moodle_exception('notapage', 'webservice');
        } 
        $context = context_module::instance($coursemoduleid); 
        
        if (!is_enrolled($context, $USER, '', true)) {
            throw new moodle_exception('userisnotenrolled', 'webservice');
        }
        
        $pagerecord = $DB->get_record('page', array('id'=>$cm->instance), '*', MUST_EXIST);
        $options = empty($pagerecord->displayoptions) ? array() : unserialize($pagerecord->displayoptions);
        
        // Update 'viewed' state if required by completion system
        require_once($CFG->libdir . '/completionlib.php');
        $completion = new completion_info($DB->get_record('course',array('id'=>$cm->course)));
        $completion->set_module_viewed($cm);
        
        $page = array();
        
        $page['id'] = $pagerecord->id;
        $page['name'] = $pagerecord->name;
        if (!empty($options['printintro'])) {
            $page['description'] = file_rewrite_pluginfile_urls($pagerecord->intro, 
                    'webservice/pluginfile.php', $context->id, 'mod_page', 'intro', null);
        }
        $page['content'] = file_rewrite_pluginfile_urls($pagerecord->content, 
                'webservice/pluginfile.php', $context->id, 'mod_page', 'content', $pagerecord->revision);
        $page['content'] = format_text($page['content'], $pagerecord->contentformat, array('filter'=>false));

        return $page;
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_cm_page_parameters() {
        return new external_function_parameters(
            array(
                'coursemoduleid' => new external_value(PARAM_INT, 'course module id'),
            )
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_cm_page_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'id'),
                'name' => new external_value(PARAM_TEXT, 'name of page'),
                'description' => new external_value(PARAM_RAW, 'description', VALUE_OPTIONAL),
                'content' => new external_value(PARAM_RAW, 'content'),
            )
        );
    }
    

    /**
     * Return all quizzes for the current student in given course
     * @param int $courseid the id of the course that you want the quizzes from
     * @return array An array of quizzes.
     */
    public static function get_course_quizzes($courseid)  {    
        global $USER, $DB, $CFG;
        
        $params = self::validate_parameters(self::get_course_quizzes_parameters(),array('courseid' => $courseid));
         
        $context = context_course::instance($courseid);
        
        if (!is_enrolled($context, $USER, '', true)) {
            throw new moodle_exception('userisnotenrolled', 'webservice');
        }
        $course = $DB->get_record('course', array('id'=>$courseid), '*', MUST_EXIST);
        
        $modinfo = get_fast_modinfo($course);
        $quizzes = $modinfo->get_instances_of('quiz');

        $quizzesout = array();
        
        $token = optional_param('wstoken', '', PARAM_RAW);
        $quizzesout['baselink'] = "{$CFG->wwwroot}/local/ombiel_webservices/login.php?wstoken={$token}&userid={$USER->id}&cmid=";
        $quizzesout['quizzes'] = array();
        // Create.
        if (!empty($quizzes)) {
            foreach ($quizzes as $cm) {
                if ($cm->uservisible) {
                    $context = context_module::instance($cm->id);
                    $instance = $DB->get_record('quiz', array('id'=>$cm->instance));  

                    $quizout = array();
                    $quizout['id'] = $cm->instance;
                    $quizout['coursemoduleid'] = $cm->id;
                    $quizout['name'] = $cm->name;
                    if (!empty($cm->showdescription)) {
                        $quizout['description'] = file_rewrite_pluginfile_urls($instance->intro, 'webservice/pluginfile.php', $context->id, 'mod_quiz', 'intro', null);
                    }
                    $quizout['courseid'] = $cm->course;
                    $quizzesout['quizzes'][] = $quizout;                  
                }
            }
        }
        return $quizzesout;
 
    }

    /**
     * @return external_function_parameters
     */
    public static function get_course_quizzes_parameters(){
        return new external_function_parameters(
        array(
            'courseid' => new external_value(PARAM_INT, 'course id'),
        )
        );

    }

    /**
     * @return external_single_structure
     */
    public static function get_course_quizzes_returns() {
        return new external_single_structure(
            array(
                'baselink' => new external_value(PARAM_URL, 'First part of link to native module page'),
                'quizzes' => 
                new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_RAW, 'quiz id'),
                            'coursemoduleid' => new external_value(PARAM_RAW, 'cm id'),
                            'name' => new external_value(PARAM_RAW, 'name of quiz'),
                            'description' => new external_value(PARAM_RAW, 'intro used for quiz', VALUE_OPTIONAL),
                            'courseid' => new external_value(PARAM_RAW, 'id of course that the quiz is in'),

                        )
                    )
                )
            )
        );
    }
    /**
     * Returns an array containing all resources for the current course
     * @param int $courseid The id of the course
     * @return array containing course resources
     */
    public static function get_course_resources($courseid) {

        global $USER, $DB, $CFG;
        $params = self::validate_parameters(self::get_course_resources_parameters(),array('courseid' => $courseid));

        $course = $DB->get_record("course", array("id" => $courseid), '*', MUST_EXIST);
         
        $context = context_course::instance($courseid);
        
        if (!is_enrolled($context, $USER, '', true)) {
            throw new moodle_exception('userisnotenrolled', 'webservice');
        }
        
        $modinfo = get_fast_modinfo($course);
        $course_modules = $modinfo->get_instances_of('resource');

        $res_array = array();
        
        if(!empty($course_modules)){
            foreach ($course_modules as $course_module) {
                if ($course_module->uservisible) {
                    $resourceout = array();
                    $resourceout['id'] = $course_module->id;
                    $resourceout['name'] = $course_module->name;                
                    if (!empty($course_module->showdescription)) {
                        $instance = $DB->get_record('resource', array('id'=>$course_module->instance));
                        $context = context_module::instance($course_module->id);
                        $resourceout['description'] = file_rewrite_pluginfile_urls($instance->intro, 'webservice/pluginfile.php', $context->id, 'mod_resource', 'intro', null);
                    };
                    $baseurl = 'webservice/pluginfile.php';

                    require_once($CFG->dirroot . '/mod/resource/lib.php');

                    if ($contents = resource_export_contents($course_module, $baseurl)) {
                        $resourceout['contents'] = $contents;
                    }
                    $res_array[] = $resourceout;
                }
            }
        } 

        return $res_array;
    }

    /**
     * @return external_function_parameters
     */
    public static function get_course_resources_parameters(){
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
            )
        );

    }


    /**
     * @return external_multiple_structure
     */
    public static function get_course_resources_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'coursemodule id '),
                    'name' => new external_value(PARAM_TEXT, 'name'),
                    'description' => new external_value(PARAM_RAW, 'intro used for forum', VALUE_OPTIONAL),
                    'contents' => new external_multiple_structure(
                        new external_single_structure(
                            array(
                                // Content info.
                                'type' => new external_value(PARAM_TEXT, 'a file or a folder or external link'),
                                'filename' => new external_value(PARAM_FILE, 'filename'),
                                'filepath' => new external_value(PARAM_PATH, 'filepath'),
                                'filesize' => new external_value(PARAM_INT, 'filesize'),
                                'fileurl' => new external_value(PARAM_URL, 'downloadable file url', VALUE_OPTIONAL),
                                'content' => new external_value(PARAM_RAW, 'Raw content, will be used when type is content',
                                    VALUE_OPTIONAL),
                                'timecreated' => new external_value(PARAM_INT, 'Time created'),
                                'timemodified' => new external_value(PARAM_INT, 'Time modified'),
                                'sortorder' => new external_value(PARAM_INT, 'Content sort order'),

                                // Copyright related info.
                                'userid' => new external_value(PARAM_INT, 'User who added this content to moodle'),
                                'author' => new external_value(PARAM_TEXT, 'Content owner'),
                                'license' => new external_value(PARAM_TEXT, 'Content license'),
                            )
                        ), VALUE_DEFAULT, array()
                    )
                ), 'List of course resources'
            )
        );

    }

    /**
     * Returns all messages either received or sent by the current user
     * @param int $read Are read or unread messages to be returned default
     *                  is null 'unread' 1 = read. is disregarded if sent
     *                  param is set
     * @param int $sent return all messages sent by the current user
     * @return array containing messages
     */
    public static function get_user_messages($read=null,$sent=null) {

        global $USER, $DB;

        $params = self::validate_parameters(self::get_user_messages_parameters(),array('read' => $read,
            'sent' => $sent));
        $userid = $USER->id;


        //if the sent flag is not sent then we will be retrieving messages
        //received by the user
        if (empty($sent)) {

            $messagetable = (!empty($read)) ? "message_read" : "message" ;
            $messages = $DB->get_records($messagetable,array('useridto'=>$USER->id));
            $msg = array();
            if (!empty($messages)) {

                foreach ($messages as $message) {
                    $act = new stdClass();
                    $act->id = $message->id;
                    $act->useridfrom = $message->useridfrom;
                    $act->useridto = $message->useridto;
                    $act->message = $message->fullmessage;
                    $act->timecreated = $message->timecreated;
                    $act->timeread = (isset($message->timeread))?$message->timeread:'';
                    $msg[] = (array)$act;
                }
            }
        } else {

            $read_messages = $DB->get_records("message_read",array('useridfrom'=>$USER->id));
            $read_msg = array();
            if (!empty($read_messages)) {

                foreach ($read_messages as $read_message) {
                    $act = new stdClass();
                    $act->id = $read_message->id;
                    $act->useridfrom = $read_message->useridfrom;
                    $act->useridto = $read_message->useridto;
                    $act->message = $read_message->fullmessage;
                    $act->timecreated = $read_message->timecreated;
                    $act->timeread = $read_message->timeread;
                    $read_msg[] = (array)$act;
                }
            }

            $unread_messages = $DB->get_records("message",array('useridfrom'=>$USER->id));
            $unread_msg = array();
            if (!empty($unread_messages)) {

                foreach ($unread_messages as $unread_message) {
                    $act = new stdClass();
                    $act->id = $unread_message->id;
                    $act->useridfrom = $unread_message->useridfrom;
                    $act->useridto = $unread_message->useridto;
                    $act->message = $unread_message->fullmessage;
                    $act->timecreated = $unread_message->timecreated;
                    $act->timeread = '';
                    $unread_msg[] = (array)$act;
                }
            }
            $msg = array_merge($read_msg, $unread_msg);

        }

        return $msg;
    }

    /**
     * @return external_function_parameters
     */
    public static function get_user_messages_parameters(){
        return new external_function_parameters(
            array(
                'read' => new external_value(PARAM_BOOL, 'read'),
                'sent' => new external_value(PARAM_BOOL, 'sent'),
            )
        );

    }


    /**
     * @return external_multiple_structure
     */
    public static function get_user_messages_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'id'),
                    'useridfrom'=> new external_value(PARAM_RAW, 'useridfrom'),
                    'useridto' => new external_value(PARAM_RAW, 'useridto'),
                    'message'=> new external_value(PARAM_RAW, 'message'),
                    'timecreated'=> new external_value(PARAM_RAW, 'timecreated'),
                    'timeread'=> new external_value(PARAM_RAW, 'timeread'),

                ), 'List of messages'
            )
        );

    }
   
    /**
     * Returns a link to log in to the course or front page of Moodle
     * @param courseid if null front page
     * @return string link to login to native moodle.
     */
    public static function get_native_moodle_link($courseid = null) {
        global $CFG, $DB, $USER;

        // Validate the given parameter.
        $params = self::validate_parameters(self::get_native_moodle_link_parameters(), array('courseid' => $courseid));
        
        $courseparam = '';
        if (!empty($courseid)) {

            $context = context_course::instance($courseid);
            if (!is_enrolled($context, $USER, '', true)) {
                throw new moodle_exception('userisnotenrolled', 'webservice');
            }
            $courseparam = "&courseid={$courseid}";
        }
        
        $result = array();
        $token = optional_param('wstoken', '', PARAM_RAW);
        $result['link'] = "{$CFG->wwwroot}/local/ombiel_webservices/login.php?wstoken={$token}&userid={$USER->id}{$courseparam}";
        
        return $result;
    }
    
    /**
     * @return external_function_parameters
     */
    public static function get_native_moodle_link_parameters() {
        return new external_function_parameters(array(
                'courseid' => new external_value(PARAM_INT, 'courseid'),
            )
        );
    }
    /**
     * @return external_multiple_structure
     */
    public static function get_native_moodle_link_returns() {
        return new external_single_structure(
            array(
                'link' => new external_value(PARAM_RAW, 'link to login to message settings'),
            )
        );
    }
    /**
     * Returns a link to log in to the message setting page of Moodle
     * @return string the link 
     */
    public static function get_message_settings_link() {
        global $CFG, $DB, $USER;
        
        $result = array();
        $token = optional_param('wstoken', '', PARAM_RAW);
        $result['link'] = "{$CFG->wwwroot}/local/ombiel_webservices/login.php?wstoken={$token}&userid={$USER->id}&messages=true";
        
        return $result;
    }
    
    /**
     * @return external_function_parameters
     */
    public static function get_message_settings_link_parameters() {
        return new external_function_parameters(array(
            )
        );
    }
    /**
     * @return external_multiple_structure
     */
    public static function get_message_settings_link_returns() {
        return new external_single_structure(
            array(
                'link' => new external_value(PARAM_RAW, 'link to login to message settings'),
            )
        );
    }
}
