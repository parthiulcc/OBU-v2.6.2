<?php
require_once('../../config.php');
require_once('module_add_form.php');
require_once('view.php');
require_once('course_mod_add.php');
require_once('capabilities_check.php');



function split_csv($rawcsv) {
    // Remove Windows \r\n new lines
    $rawcsv = str_replace("\r\n", "\n", $rawcsv);
    $csvrows = array();
    $lines = explode("\n", $rawcsv);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            $fields = str_getcsv($line);
            $csvrows[] = $fields;
        }
    }
    return $csvrows;
}



function process_form(module_add_view $view, module_add_form $form, $formdata) {
    global $DB;

    // Course list
    $courserows = split_csv($form->get_file_content('courses'));
    // Check basic validity of course list file
    $i = 1;
    foreach ($courserows as $courserow) {
        if (count($courserow) <> 2) {
            $view->maincsv_error('Incorrect number of fields on line ' . $i);
        }
        $i++;
    }

    // Parameters XML file
    $moduleparamsstr = $form->get_file_content('moduleparams');
    if (empty($moduleparamsstr)) {
        $moduleparamsstr = '<paramitems></paramitems>';
    }
    libxml_use_internal_errors(true);
    $paramsxmlobj = simplexml_load_string($moduleparamsstr, null, LIBXML_NOCDATA);
    $errors = libxml_get_errors();
    if (count($errors) > 0) {
        $errorstr = '';
        foreach ($errors as $error) {
            $errorstr .= '\non line ' . ($error->line-1) . ' - ' . $error->message;
        }
        $view->moduleparams_error($errorstr);
    } else {
        $numparamitems = count($paramsxmlobj->paramitem);
        if ($numparamitems <> 1 && $numparamitems <> count($courserows)) {
            $view->moduleparams_error('Module parameters file must have exactly one or same number of items as course list file');
        }
    }

    // If permissions override file exists, check it is valid
    $permissionscsvrows = split_csv($form->get_file_content('permsfile'));
    $capabilities_check = new capabilities_check();
    $i = 1;
    foreach ($permissionscsvrows as $csvrow) {
        if (!$capabilities_check->valid_role($csvrow[0])) {
            $msg = 'Invalid role ID found on line ' . $i . ', ' . $csvrow[0];
            $view->permissions_override_error($msg);
        } else if (!$capabilities_check->valid_capability($csvrow[1])) {
            $msg = 'Invalid capability found on line ' . $i . ', ' . $csvrow[1];
            $view->permissions_override_error($msg);
        } else if (!$capabilities_check->valid_permission($csvrow[2])) {
            $msg = 'Invalid permission found on line ' . $i . ', ' . $csvrow[2];
            $view->permissions_override_error($msg);
        }
        $i++;
    }

    $module = $formdata->module;
    $atstart = (isset($formdata->atstart))?true:false;

    $view->output_processing_start();
    $i = 0;
    foreach ($courserows as $courserow) {
        // If course ID is set, pass this to course_mod_add otherwise get course IDs for module code
        $courseid = (int) $courserow[0];
        if ($courseid > 0) {
            $courseIDs = array($courseid);
        } else {
            $like = $courserow[1] . '%';
            $rows = $DB->get_records_sql('SELECT id FROM {course} WHERE shortname LIKE ?', array($like));
            $courseIDs = array();
            foreach ($rows as $row) {
                $courseIDs[] = $row->id;
            }
        }

        // Select correct module parameters for current course
        if ($numparamitems == 1) {
            $moduleparams = $paramsxmlobj->paramitem[0];
        } else {
            $moduleparams = $paramsxmlobj->paramitem[$i];
        }

        $visible = isset($formdata->visible)?1:0;

        foreach ($courseIDs as $courseID) {
            $tablefields = array($courseID, $courserow[1]);
            $ret = course_mod_add::add($module, $courseID, $atstart, (int)$formdata->ifexists, $moduleparams, 0, $visible, $permissionscsvrows);
            if (!$ret[0]) {
                $tablefields[] = $ret[1];
            } else {
                $tablefields[] = 'Done';
            }
            $view->output_processing_row($tablefields);
            sleep(1);
        }

        $i++;
    }

    $view->output_processing_end();
}



// Check user has correct permissions
require_login();
require_capability('moodle/site:config', context_system::instance());

// Create view
$view = new module_add_view();

// Get list of available modules and set up UI form
$modules = $DB->get_records('modules', array('visible'=>1), 'name', 'name');
$modulesAssoc = array();
foreach($modules as $module) {
    $modulesAssoc[ $module->name ] = $module->name;
}
$moduleaddform = new module_add_form($modulesAssoc);
$view->set_form($moduleaddform);

if (!$formdata = $moduleaddform->get_data()) {
    $view->output_form();
} else {
    process_form($view, $moduleaddform, $formdata);
}
