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
 * This is the type_config_form definition for Moodle above 2.3 - see lib.php for test
 *
 * @package    repository_cla_survey
 * @copyright  2014 Copyright Licencing Authority (UK)
 * @author	   Benjamin Ellis Mukudu Ltd - benjamin.c.ellis@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This is the type_config_form definition for Moodle above 2.3 - see lib.php for test
 *
 * @copyright  2014 Copyright Licencing Authority (UK)
 * @author	   Benjamin Ellis Mukudu Ltd - benjamin.c.ellis@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_cla_survey_ext extends repository {

    /**
     * Edit/Create Admin Settings Moodle form
     *
     * @param object $mform Moodle form (passed by reference)
     * @return void
     */
    public static function type_config_form($mform, $classname = 'repository') {
        parent::type_config_form($mform);
        require_once(dirname(__FILE__) . '/cla_survey_config_form.php');		// The config form used by this lib and lib20
    }

}

/* ?> */