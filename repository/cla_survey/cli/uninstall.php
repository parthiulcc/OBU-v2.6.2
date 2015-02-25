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
 * Script to manually uninstall repository as Moodle only disables repos
 *
 * @package    repository_cla_survey
 * @copyright  2014 Copyright Licencing Authority (UK)
 * @author     Benjamin Ellis Mukudu Ltd - benjamin.c.ellis@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('CLI_SCRIPT', true);

require_once(dirname(__FILE__) . '/../../../config.php');
require_once(dirname(__FILE__) . '/../locallib.php');
require_once($CFG->libdir.'/ddllib.php');

$oldplugin = '';
if (!isset($plugin)) {
	$plugin = '';
}
if (!is_object($plugin)) {
	// save the details elsewhere
	if (!empty($plugin)) {
		$oldplugin = $plugin;
	}
	$plugin = new StdClass();
	require_once('../version.php');
}

// check that the plugin has been disabled - it will not be in the repository table
list($plugintype,$pluginname) = explode('_', $plugin->component, 2);
if (!$DB->record_exists('repository', array('type'=>$pluginname))) {
	// Moodle does not quite remove all records of the plugin - repository_cla_survey left in mdl_config_plugins
	if ($DB->delete_records('config_plugins', array('plugin'=>$plugin->component))) {
		// drop the repository table
		if ($dbman = $DB->get_manager()) { //; // Loads ddl manager and xmldb classes
			if ($dbman->table_exists($plugin->component)) {
				$dbman->drop_table(new xmldb_table($plugin->component));
				// remove the files from the file system
				echo "Now please remove the repository/cla_survey directory from the disk\n";
			}else{
				echo "Table does not exist\n";
			}
		}else{
			echo "Failed to get Database manager\n";
		}
	}else{
		 echo "Failed to delete plugin's record\n";
	}
}else{
	echo "Repository must be disabled, before running this script\n";
}

$cleancache = dirname(__FILE__) . '/../../../admin/cli/purge_caches.php';
if (file_exists($cleancache)) {
	require_once($cleancache);
}

/* ?> */
