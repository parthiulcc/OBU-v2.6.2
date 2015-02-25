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
 * Main repository API plugin files - overrides methods required for operation
 * @package    repository_cla_survey
 * @copyright  2014 Copyright Licencing Authority (UK)
 * @author	   Benjamin Ellis Mukudu Ltd - benjamin.c.ellis@gmail.com
 * @license    http:// www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/*
  The following is required because some of the classes repository
  in Moodle 2.3 are not backwards compatible with Moodle 2.2 (different
  signature - static vs. not).
*/
if ($CFG->version >= 2012061800) {
	require_once($CFG->dirroot."/repository/cla_survey/lib23.php");
} else {
	require_once($CFG->dirroot."/repository/cla_survey/lib20.php");
}
require_once($CFG->dirroot."/repository/cla_survey/locallib.php");

/**
 * the plugin's class extention -
 *
 * note extending repository_cla_survey_ext rather than moodle's repository class
 * so we can manage pre and post Moodle version 2.3 - see above requires
 *
 * @package    repository_cla_survey
 * @copyright  2014 Copyright Licencing Authority (UK)
 * @author	   Benjamin Ellis Mukudu Ltd - benjamin.c.ellis@gmail.com
 * @license    http:// www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_cla_survey extends repository_cla_survey_ext {

	/** @var $myrepoid private variable to store our repo id as we piggy back on the uploads and local repository*/
	private $myrepoid;

	/**
	 * Constructor
	 *
	 * @param int $repositoryid
	 * @param stdClass $context
	 * @param array $options
	 */
	public function __construct($repositoryid, $context = SITEID, $options = array()) {
		parent::__construct($repositoryid, $context, $options);
		$this->myrepoid = $repositoryid;
	}

	/**
	 * Get file listing
	 *
	 * Post 2.3 - this function uses the new object functionality - may have been able to the upload filepicker templates
	 * but we need to keep things simple
	 * Pre 2.3 version highjacks the message attribute to display the cla_survey form and break out
	 * due to the inflexibility of the upload functionality in these versions
	 *
	 * @param string $path
	 * @param string $page
	 */
	public function get_listing($path = '', $page = '') {
		global $CFG, $COURSE;
		$list = array();
		$list['list'] = array();
		$list['nologin']  = true;
		$list['nosearch'] = true;
		$list['norefresh'] = true;
		$link = $CFG->wwwroot . '/repository/cla_survey/cla_survey.php?course=' . $COURSE->id;
		$link .= '&myrepoid=' . $this->myrepoid;

		if ($CFG->version >= 2012061800) {
			// This code bit is for Moodle 2.3 or higher
			$list['object'] = array();
			$list['object']['type'] = 'text/html';
			$list['object']['src'] = $link;
		} else {
			// this is the workaround for the missing object param in versions 2 below Moodle 2.3
			$list['message'] = '<iframe type="text/html" width="100%" height="410px" src="' . $link . '"></iframe>';
		}

		return $list;
	}

	/**
	 * cron function - this checks to see if cron is required and if so calls functionality in locallib.php
	 *
	 * @return boolean true all the time
	 */
	public function cron() {
		if (get_config('cla_survey', 'usecron')) {
			require_once('locallib.php');
			return run_cron();
		}
		return true;		// always return true
	}

	/**
	 * Configure Plugin settings input to Moodle form - called by lib20.php or lib23.php
	 *
	 * @param object $mform
	 * @return void
	 */

	/*
		In Moodle versions < 2.2
		public function type_config_form($mform, , $classname = 'repository')

		In Moodle versions > 2.2
		public static function type_config_form($mform,, $classname = 'repository');

		Declarations now in lib20 and lib 23
	*/

	/**
	 * Has internal files????
	 *
	 * @return boolean
	 */
	public function has_moodle_files() {
		return true;
	}

	/**
	 * Names of the plugin settings
	 *
	 * @return array
	 */
	public static function get_type_option_names() {
		return array('pluginname', 'accountno', 'usecron', 'termsacceptance', 'mailto');
	}

	/**
	 * Supports file linking and copying
	 *
	 * @return int
	 */
	public function supported_returntypes() {
		return FILE_INTERNAL;
	}

	/**
	 * Return size of a file in bytes.
	 *
	 * Addition of this functionality because we are getting a lot of file size errors
	 *
	 * @param string $source encoded and serialized data of file
	 * @return integer file size in bytes
	 */
	public function get_file_size($source) {
		if ($source) {
			if ($params 	= unserialize(base64_decode($source))) {
				return parent::get_file_size($source);			// this is good
			}
		}
		return null;
	}
}

/**
 * CLA Survey plugin cron task
 */
function repository_cla_survey_cron() {
	$instances = repository::get_instances(array('type'=>'cla_survey'));
	foreach ($instances as $instance) {
		$instance->cron();
	}
}



/* ?>*/
