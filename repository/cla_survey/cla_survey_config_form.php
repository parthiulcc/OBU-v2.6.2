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
 * Config form for the CLA repository
 *
 * Declared to be required by lib20 or lib23 depending on the Moodle Version
 *
 * @package    repository_cla_survey
 * @copyright  2014 Copyright Licencing Authority (UK)
 * @author     Benjamin Ellis Mukudu Ltd - benjamin.c.ellis@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

$strrequired = get_string('required');
$tandclink = $CFG->wwwroot . "/repository/cla_survey/clatermsofuse.pdf"; 	// URI for the Terms of Use Document.

$cfg = get_config('cla_survey');
if (!isset($cfg->mailto)) {
	set_default_config();			// set the hidden email account
}

// Terms and Conditions
$mform->addElement('header', 'tandcheader', get_string('tandcheader', 'repository_cla_survey'));
$mform->addElement('static', 'tandcinto', '', get_string('tandcintro', 'repository_cla_survey'));

$mform->addElement('static', 'tandclink', '',
	html_writer::link($tandclink, get_string('tandclinktext', 'repository_cla_survey'), array('target'=>'_blank'))
);

$mform->addElement('checkbox', 'termsacceptance', '', get_string('tandcacceptance', 'repository_cla_survey'));
$mform->addRule('termsacceptance', $strrequired, 'required', null, 'client');		// this should force choice
$mform->closeHeaderBefore('accountno');

// account number from the CLA
$mform->addElement('text', 'accountno', get_string('acct_prompt', 'repository_cla_survey'),
	array('size' => '20'));
if (isset($cfg->accountno)) {
	$mform->setDefault('usecron', $cfg->accountno);
}
$mform->addRule('accountno', $strrequired, 'required', null, 'client');

// update - moodle cron is not supported by Moodles < 2.3????
// use moodle cron - switching off means cron run from Linux system or on the command line
if ($CFG->version >= 2012061800) {
	$mform->addElement('selectyesno', 'usecron', get_string('cron_prompt', 'repository_cla_survey'));
	if (isset($cfg->usecron)) {
		$mform->setDefault('usecron', $cfg->usecron);
	}
}else{
	// hide the usecron setting
	$mform->addElement('hidden', 'usecron', 0); 	// turn it off
	// inform user of the need to set up a cron job
	$mform->addElement('static', 'tandcnomoodle', '', get_string('nomoodlecron', 'repository_cla_survey'));
}
$mform->setType('usecron', PARAM_INT);

/* ?> */
