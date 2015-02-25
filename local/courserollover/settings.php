<?php
defined('MOODLE_INTERNAL') || die;

if (has_capability('moodle/backup:backuptargetimport', context_system::instance())) {
    $ADMIN->add('courses', new admin_externalpage('local_courserollover', 'Course roll-over', "$CFG->wwwroot/local/courserollover/courserollover.php"));
}
