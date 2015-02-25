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
 * Version details
 *
 * @package    repository_cla_survey
 * @copyright  2014 Copyright Licencing Authority (UK)
 * @author     Benjamin Ellis Mukudu Ltd - benjamin.c.ellis@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
global $CFG;

$plugin->version = 2014042301;                    // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires = 2011070100;                     // Requires this Moodle version.
$plugin->component = 'repository_cla_survey';       // Full name of the plugin (used for diagnostics).
$plugin->pluginname = 'CLA Survey Repository';
$plugin->cron = 604800;                                  // 86400 for daily reports.
if ($CFG->version >= 2011120500) {
	$plugin->dependencies = array(
	    'repository_local' => ANY_VERSION,
	    'repository_upload' => ANY_VERSION
	);
}

/* ?> */
