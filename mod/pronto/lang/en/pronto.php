<?PHP
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2011 Blackboard Inc., All Rights Reserved.                *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Blackboard Inc.                       *
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
 *      along with the Blackboard IM Moodle Integration;                      *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Jonathan Abourbih
 *                                                                            *
 * Date: 14 April 2011
 *                                                                            *
 ******************************************************************************/
//Module Level Help
$string['modulename_help'] = 'The Blackboard IM module enables teachers and students to quickly communicate through Blackboard Collaborate
Enterprise Instant Messaging. 

Blackboard IM makes it easy to see who is online and enables quick communication through:

* text chat
* audio and video calling
* interactive whiteboard, application and desktop sharing

You can communicate with one person, or invite others to join in.';

$string['modulename_link'] = 'http://www.blackboard.com/Platforms/Collaborate/Products/Blackboard-Collaborate/Enterprise-Instant-Messaging.aspx';               


//Wimba Pronto Module
$string['modulename'] = 'Blackboard IM';
$string['pluginname'] = 'Blackboard IM';
$string['modulenameplural'] = 'Blackboard IM';
$string['pluginadministration'] = 'Blackboard IM';
$string['pronto:addinstance'] = 'Ability to add a new Blackboard IM Instance';

//Configuration page
$string['prontosetup'] = $string['modulename'].' Setup';
$string['urlconfig'] = 'Server URL';
$string['accountconfig'] = 'Account Name';
$string['secretconfig'] = 'Account Secret';
$string['ntp'] = 'Using NTP';
$string['ntphelp'] = '(This box should remain checked, unless your server does not synchronize with Network Time Protocol)';

$string['troubleshooting'] = 'Troubleshooting';
$string['integrationversion'] = 'Integration Version:';
$string['loglevel'] = 'Set Log Level';
$string['viewlogs'] = 'View Logs';

$string['save'] = 'Save changes';

//Field validation error messages
$string['validation_request_ajax'] = 'Validation can only be performed from the settings page.';
$string['urlandretry'] = 'Please enter the server name provided by Blackboard, normally https://www.blackboardim.com/, and retry.';
$string['invalidlettersnumbers'] = 'This field is required and can only contain letters, numbers, underscores, and hyphens.';


//Logs page
$string['serverlogs'] = 'Server Logs';
$string['logsdir'] = 'Log file directory on disk: ';
$string['loglinks'] = 'Click on the links below to download the logs.';
$string['logback'] = 'Back to '.$string['modulename'].' Configuration';
$string['no_logs'] = 'There are no logs yet.';

//Validation page

$string['edit'] = 'Edit';
$string['continue'] = 'Continue';

$string['connectiontest'] = 'Connection test';
$string['editinstruction'] = 'If the test failed, click the <b>'.$string['edit'].'</b> button to verify your settings.';
$string['continueinstruction'] = 'Click the <b>'.$string['continue'].'</b> button to return to the Activities page.';

$string['pronto_activity_title'] = 'Register and Download '.$string['modulename'].'!';
$string['empty_activity_title_error'] = 'Please provide a name for this activity';

$string['admin_restriction_message']='You must be an admin to access here';
