<?php

/*****************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2007 Wimba, All Rights Reserved.                        *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Wimba.                               *
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
 *      along with the Wimba Moodle Integration;                              *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Frederic Mathiot                                                   *
 *                                                                            *
 * Date: 16 november 2007                                                     *
 *                                                                            *
 ******************************************************************************/

require_once ('../../config.php');
require_once ('./prontolib.php');
global $CFG;

require_login();

if (!has_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM))) {
    print_error('accessdenied', 'admin');
}

//Gets the parameters
$action = required_param("action", PARAM_ACTION);
$log = optional_param("log", "", PARAM_FILE);

//Set the log file name.
$file = realpath(PRONTO_LOGS_DIR."/".$log);

/**If action is list, lists all the logs in the level corresponding folder
 * If action is download, download the selected log
 */
if($action == "list"){
    pronto_render_log_list(new moodle_url('/admin/settings.php', array('section' => 'modsettingpronto')),
        pronto_get_log_list());
}

if ($action === 'download' && file_exists($file)) {
    if (dirname($file) === PRONTO_LOGS_DIR) {
        header("Content-type: text/plain; charset=UTF-8");
        header("Content-disposition: attachment; filename=$log");
        readfile($file);
    } else {
        header("HTTP/1.1 403 Forbidden");
?>
      <h1>Forbidden</h1>
      <p>You do not have permission to access this file.</p>
<?php
    }
} elseif ($action === 'download') {
    header("HTTP/1.1 404 Not Found");
?>
      <h1>Not Found</h1>
      <p>The file you requested, <?php echo $log ?>, was not found.</p>
<?php
}

function pronto_render_log_list($referer, $log_list) {
    $values = new stdClass;
    $values->referer = $referer;
    $values->log_list = $log_list;

    require_once("./prontologlist.html");
}

function pronto_get_log_list() {
    $logs_dir = @opendir(PRONTO_LOGS_DIR);

    $log_links = array();

    while ($log_file = readdir($logs_dir)) {
        if (!is_dir($log_file)) {
            $log_record = new stdClass;

            $log_record->filename = $log_file;
            $log_record->size = filesize(PRONTO_LOGS_DIR."/".$log_file);
            $log_record->url = new moodle_url('/mod/pronto/prontolog.php', array(
                'action' => 'download',
                'log' => $log_file
            ));

            $log_links[] = $log_record;
        }
    }

    closedir($logs_dir);

    return $log_links;
}
