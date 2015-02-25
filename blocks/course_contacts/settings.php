<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox('block_co_co_simpleemail', get_string('simpleemail', 'block_course_contacts'),
                       get_string('simpleemaildesc', 'block_course_contacts'), 1));
    $settings->add(new admin_setting_configcheckbox('block_co_co_recaptcha', get_string('captcha', 'block_course_contacts'),
                       get_string('captchadesc', 'block_course_contacts'), 1));
}

