<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configcheckbox('block_jumpto_menu_jsmove', get_string('jsmove', 'block_jumpto_menu'),
                       get_string('config_jsmove', 'block_jumpto_menu'), 1));

    $settings->add(new admin_setting_configcheckbox('block_jumpto_menu_hide_borders', get_string('hide_borders', 'block_jumpto_menu'),
                       get_string('config_hide_borders', 'block_jumpto_menu'), 0));
}


