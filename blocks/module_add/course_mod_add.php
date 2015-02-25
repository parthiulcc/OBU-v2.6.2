<?php

require_once("$CFG->dirroot/course/lib.php");
require_once("$CFG->dirroot/lib/accesslib.php");


class course_mod_add {

    /* Delete existing course module(s). Deletes all instances of the matching module
        in the passed course.

        $modulename is the name (type) of the module to be deleted
        $course is an object representation of the mdl_course DB row
        $title is the module instance title in the passed course
        $section is the section number the module is in

        Returns true on success, false on failure
    */
    static private function delete($modulename, $course, $title, $section) {
        global  $DB,
                $CFG;

        $sql = 'SELECT cm.id FROM {course_sections} AS cs JOIN {course_modules} AS cm ON cm.section = cs.id JOIN {modules} AS ms ON ms.id = cm.module JOIN {' . $modulename . '} AS m ON m.id = cm.instance WHERE cs.course = ? AND cs.section = ? AND m.name = ? AND ms.name = ?';
        $instances = $DB->get_records_sql($sql, array($course->id, $section, $title, $modulename));

        foreach ($instances as $instance) {
            $cm = get_coursemodule_from_id('', $instance->id);

            $modlib = "$CFG->dirroot/mod/$cm->modname/lib.php";
            if (file_exists($modlib)) {
                require_once($modlib);
            } else {
                return false;
            }

            $deleteinstancefunction = $cm->modname."_delete_instance";

            if (!$deleteinstancefunction($cm->instance)) {
                return false;
            }

            if (!delete_course_module($cm->id)) {
                return false;
            }
            if (!delete_mod_from_section($cm->id, $cm->section)) {
                return false;
            }

            // Trigger a mod_deleted event with information about this module.
            $eventdata = new stdClass();
            $eventdata->modulename = $cm->modname;
            $eventdata->cmid       = $cm->id;
            $eventdata->courseid   = $course->id;
            $eventdata->userid     = 0;
            events_trigger('mod_deleted', $eventdata);
        }

        return true;
    }


    /* Add a new course module(s).

        $modulename is the name (type) of the module to be added
        $courseid is the ID of the course
        $atstart specifies whether the module should be added at the start (1) or end (0) of the section
        $ifexists specifies what should happen if the module already exists (0 - skip addition, 1 - add a new instance,
            2 - delete the existing instance and add a new one)
        $moduleparams is an SimpleXML object with the parameters specific to the module including title and any content / presets for the selected module.
            Parameters are passed to the module instance creation object.
        $section is the section number to add the module to.
        $visible sets intiial visibility state of new course module. If > 0, course module is visible.
        $permissionsoverrides is an array of role capabilities to be allowed or prevented on the newly added course module. Each cell of array should be array of
        role id, capability, permission ("allow", "prevent").

        Returns array where first element is true on success or false on failure and second element is the error message.
    */
    static function add($modulename, $courseid, $atstart=0, $ifexists=0, $moduleparams=null, $section=0, $visible=0, $permissionsoverrides=array()) {
        global  $DB,
                $CFG;

        // Check module type exists
        if (!$module = $DB->get_record('modules', array('name'=>$modulename), '*')) {
            return array(false, 'Module type not found');
        }

        // Check course exists
        if (!$course = $DB->get_record('course', array('id'=>$courseid), '*')) {
            return array(false, 'Course not found');
        }

        // Check course is correct format
        if ($course->format == 'site' || $course->format == 'social' || $course->format == 'scorm') {
            return array(false, 'Course is not a weekly or topic type, skipping');
        }

        // Check user has required permissions to add course module
        $requiredcapabilities = array(
            'moodle/course:manageactivities',
            'moodle/course:activityvisibility',
            'moodle/role:override');
        if (!has_all_capabilities($requiredcapabilities, context_course::instance($courseid))) {
            return array(false, 'Insufficient permissions to add course module');
        }

        // Set course module initial data
        $newcm = new stdClass();
        $newcm->course = $course->id;
        $newcm->module = $module->id;
        $newcm->modulename = $module->name;
        $newcm->section = 0;
        $newcm->instance = 0;
        $newcm->visible = $visible;
        $newcm->groupmode = 0; // No groups
        $newcm->groupingid = 0;
        $newcm->groupmembersonly = 0;
        $newcm->showdescription = 0;
        $newcm->cmidnumber = '';

        // Check whether module plugin class exists for selected module otherwise use generic module plugin
        $modulepluginclass = 'module_plugin_' . $modulename;
        $modulepluginfilename = 'moduleplugins/' . $modulepluginclass . '.php';
        if (file_exists($modulepluginfilename)) {
            include_once($modulepluginfilename);
            $moduleplugin = new $modulepluginclass($moduleparams, $newcm);
        } else {
            include_once('moduleplugins/module_plugin_generic.php');
            $moduleplugin = new module_plugin_generic($moduleparams, $newcm, $modulename);
            $modulepluginclass = 'module_plugin_generic';
        }

        // Check that module params XML is valid
        if (!$modulepluginclass::check_params_xml($moduleparams)) {
            return array(false, 'Module parameters not valid');
        }

        $newcm->name = (string)$moduleparams->title;
        $newcm->intro = (string)$moduleparams->description;
        $newcm->introformat = 1;

        // Check whether module instance with title already exists
        $sql = 'SELECT COUNT(*) AS count FROM {course_sections} AS cs JOIN {course_modules} AS cm ON cm.section = cs.id JOIN {modules} AS ms ON ms.id = cm.module JOIN {' . $module->name . '} AS m ON m.id = cm.instance WHERE cs.course = ? AND cs.section = ? AND m.name = ? AND ms.name = ?';
        $instances = $DB->get_record_sql($sql, array($course->id, $section, $newcm->name, $module->name));
        if ($instances->count > 0) {
            if ($ifexists == 0) {
                return array(false, 'Already exists, skipping');
            } else if ($ifexists == 2) {
                if (!self::delete($modulename, $course, $newcm->name, $section)) {
                    return array(false, 'Error removing existing module instance(s), could not replace');
                }
            }
        }

        // Create course module
        if (!$newcm->coursemodule = add_course_module($newcm)) {
            return array(false, 'Could not create course module');
        }
 
        // Create module instance 
        $ret = $moduleplugin->create_instance();
        if (!$ret[0]) return $ret;
 
        // Update course_modules DB row to reference new module instance
        $DB->set_field('course_modules', 'instance', $newcm->instance, array('id'=>$newcm->coursemodule));
    
        // course_modules and course_sections each contain a reference
        // to each other, so we have to update one of them twice.
        if ($atstart) {
            if (!$section = $DB->get_record('course_sections', array('course'=>$newcm->course, 'section'=>$newcm->section))) {
                // Section doesn't already exist so create it in normal manner
                $sectionid = add_mod_to_section($newcm);
            } else {
                // Moodle's add_mod_to_section add before functionality is broken so we have to do this here
                $section->sequence = trim($section->sequence);
                if (empty($section->sequence)) {
                    $newsequence = "$newcm->coursemodule";
                } else {
                    $newsequence = "$newcm->coursemodule,$section->sequence";
                }
                $DB->set_field("course_sections", "sequence", $newsequence, array("id"=>$section->id));
                $sectionid = $section->id;
            }
        } else {
            $sectionid = add_mod_to_section($newcm);
        }
        $DB->set_field('course_modules', 'section', $sectionid, array('id'=>$newcm->coursemodule));

        // Trigger post create actions
        $ret = $moduleplugin->post_create_setup();
        if (!$ret[0]) {
          self::delete($modulename, $course, $newcm->name, $section);
          return array(false, 'Error carrying out post creation setup. Error was: ' . $ret[1]);
        }

        // If $permissionsoverrides is not empty, override permissions of specified role capabilites
        if (count($permissionsoverrides) > 0) {
            $modcontext = context_module::instance($newcm->coursemodule);
            foreach ($permissionsoverrides as $permissionoverride) {
                $permission = ($permissionoverride[2]=='allow')?CAP_ALLOW:CAP_PREVENT;
                role_change_permission($permissionoverride[0], $modcontext, $permissionoverride[1], $permission);
            }
        }
 
        // Trigger mod_created event with information about this module.
        $eventname = 'mod_created';
        $eventdata = new stdClass();
        $eventdata->modulename = $module->name;
        $eventdata->name       = $newcm->name;
        $eventdata->cmid       = $newcm->coursemodule;
        $eventdata->courseid   = $course->id;
        $eventdata->userid     = 0;
        events_trigger($eventname, $eventdata);
    
        // Rebuild course cache
        rebuild_course_cache($course->id);

        return array(true, '');
    }

}
