<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) { 
    $settings = new admin_settingpage('local_ombiel_webservices', get_string('pluginname', 'local_ombiel_webservices'));

    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configselect('ombieltokentimeout', 
            get_string('tokentimeout', 'local_ombiel_webservices'), 
            get_string('configtokentimeout', 'local_ombiel_webservices'),
            $CFG->sessiontimeout,
            array(14400 => get_string('numhours', '', 4),
            10800 => get_string('numhours', '', 3),
            7200 => get_string('numhours', '', 2),
            5400 => get_string('numhours', '', '1.5'),
            3600 => get_string('numminutes', '', 60),
            2700 => get_string('numminutes', '', 45),
            1800 => get_string('numminutes', '', 30),
            900 => get_string('numminutes', '', 15),
            300 => get_string('numminutes', '', 5))
        ));
    $settings->add(new admin_setting_configcheckbox('ombielallowtokenlogin', 
               get_string('ombielallowtokenlogin', 'local_ombiel_webservices'), 
               get_string('configombielallowtokenlogin', 'local_ombiel_webservices'), 
               1
           ));
    $settings->add(new admin_setting_configtext('campusmldapendpoint', 
            get_string('campusmldapendpoint', 'local_ombiel_webservices'), 
            get_string('configcampusmldapendpoint', 'local_ombiel_webservices'), 
            '',
            PARAM_URL,
            100
        ));
    
    $settings->add(new admin_setting_configtext('campusmldapusername', 
            get_string('campusmldapusername', 'local_ombiel_webservices'), 
            get_string('configcampusmldapusername', 'local_ombiel_webservices'), 
            '',
            PARAM_RAW,
            100
        ));
    
    $settings->add(new admin_setting_configpasswordunmask('campusmldappassword', 
            get_string('campusmldappassword', 'local_ombiel_webservices'), 
            get_string('configcampusmldappassword', 'local_ombiel_webservices'), 
            '',
            PARAM_RAW,
            100
        ));
    
    $settings->add(new admin_setting_configtext('campusmldapmethod', 
            get_string('campusmldapmethod', 'local_ombiel_webservices'), 
            get_string('configcampusmldapmethod', 'local_ombiel_webservices'), 
            'login',
            PARAM_RAW,
            100
        ));
}
