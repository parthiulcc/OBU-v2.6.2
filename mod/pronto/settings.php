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
defined('MOODLE_INTERNAL') || die;

global $CFG;

if ($ADMIN->fulltree) {
    require_once($CFG->dirroot . '/mod/pronto/settingslib.php');

    $settings->add(new admin_setting_heading('pronto_header',
        get_string('prontosetup', 'pronto'), ''));

    $settings->add(new admin_setting_configtext('pronto_url',
        get_string('urlconfig', 'pronto'), '', 'https://www.blackboardim.com/', PARAM_URL));
    $settings->add(new admin_setting_configtext('pronto_account',
        get_string('accountconfig', 'pronto'), '', '', PARAM_ALPHANUMEXT));
    $settings->add(new admin_setting_configpasswordunmask('pronto_secret',
        get_string('secretconfig', 'pronto'), '', '', PARAM_RAW_TRIMMED));

    $settings->add(new admin_setting_configcheckbox('pronto_ntp_synchronized', get_string('ntp', 'pronto'), get_string('ntphelp', 'pronto'), 1));

    $logchoices = array(
        '1' => 'DEBUG',
        '2' => 'INFO',
        '3' => 'WARN',
        '4' => 'ERROR'
    );

    $settings->add(new admin_setting_configselect('pronto_loglevel',
        get_string('loglevel', 'pronto'), '', 2, $logchoices));
    $settings->add(new admin_setting_pronto_checkconfig('pronto_checkconfig', get_string('connectiontest', 'pronto')));
    $settings->add(new admin_setting_pronto_loglevel('pronto_loglist',
        get_string('viewlogs', 'pronto'), '', 2, $logchoices));
}
