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
 * local library routines - including the cron's grunt work
 *
 * @package    repository_cla_survey
 * @copyright  2014 Copyright Licencing Authority (UK)
 * @author     Benjamin Ellis Mukudu Ltd - benjamin.c.ellis@gmail.com
 * @license    http:// www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This function is called by the cli script and Moodle's cron via lib.php
 *
 * Users have the opportunity to decide whether the cron work will be done from the command line
 * (even by the system's cron) or by Moodle's cron - choice is made in settings
 *
 * @return bool always true
 */
function run_cron() {
    global $DB, $CFG;
    $updatedids = array();          // array of ids - in case new records are added whilst we are running

    // get email settings - this are set by default see - set_default_config()
    $emailaddress = get_config('cla_survey', 'mailto'); // Moodledata.FE@cla.co.uk.
    // this comes from the settings page
    $accountno = get_config('cla_survey', 'accountno');

    // csv column headings and the fields they relate to - could have used the field's comments
    $headings = array(
        'id' => 'Event Id',
        'thedate ' => 'Timestamp',
        'ipaddr' => 'IP Address',
        'datagathering ' => 'Data Gathering',
        'account ' => 'Licensee Account',
        'site' => 'Site/Dept',
        'device' => 'Device',
        'nil ' => 'Nil Return',
        'title ' => 'Title or URI',
        'istitleuri' => 'Title has URI',
        'publisher ' => 'Publisher',
        'author' => 'Author',
        'identifier' => 'Identifier',
        'identifiertype' => 'Identifier Type',
        'valid_isn' => 'Valid ISN',
        'publicationtype ' => 'Publication Type',
        'usagetype ' => 'Usage Type',
        'copies' => 'Number of copies',
        'pagefrom' => 'Page From',
        'pageto' => 'Page To',
        'pagecount ' => 'Page Count',
        'broadcast ' => 'Broadcast method'
    );

    $tmpfname = tempnam(sys_get_temp_dir(), "cla_");    // temp file for mail attachment
    $handle = fopen($tmpfname, "w");

    fwrite($handle, _makecsvline(array_values($headings)));

    $outstanding = $DB->get_records('repository_cla_survey', array('sent' => 0));
    foreach ($outstanding as $os) {
        $updatedids[] = $os->id;
        $data = (array) $os;
        // fix up the date field - 05/12/2011  12:00:00
        $data['thedate'] = date('d/m/Y H:i:s', $data['thedate']);
        // get rid of non required keys
        fwrite($handle, _makecsvline(array_values(array_slice(array_values($data), 0, (count($data) - 3)))));
    }
    fclose($handle);

    require_once($CFG->libdir . '/phpmailer/moodle_phpmailer.php');     // https:// github.com/PHPMailer/PHPMailer
    $mail = new PHPMailer;
    $mail->IsMail();

    $mail->addAddress($emailaddress, 'CLA Survey');  // Add a recipient

    // can we get the domain of the server
    $domain = parse_url($CFG->wwwroot, PHP_URL_HOST);
    $mail->From = 'moodle@' . $domain;
    $mail->FromName = get_string('mailfrom', 'repository_cla_survey');
    $mail->addReplyTo('moodle@' . $domain);
    $mail->isHTML(false);
    $mail->WordWrap = 50;

    $mail->Subject = get_string('mailsubject', 'repository_cla_survey') . $accountno;

    if (count($updatedids)) {       // a report is only attached if any records are collected
        $ids = join(', ', $updatedids);
        $mail->Body = get_string('mailattached', 'repository_cla_survey');
        $mail->addAttachment($tmpfname, $accountno . '_weekly_report.csv');         // Add attachments
    } else {                  // else we send a nil return mail
        $mail->Body = get_string('mailnoreturn', 'repository_cla_survey');
    }

    if ($mail->send()) {
        // update the table
        if (count($updatedids)) {
            $timestamp = time();        // NOW() not working in sql
            $sql = "UPDATE {repository_cla_survey} SET sent = 1, datesent=$timestamp WHERE id IN ($ids)";
            $DB->execute($sql);
        }
    }
    unlink($tmpfname);      // delete temp file
    return true;            // always return true
}

/**
 * Function to store the default config settings - called from 2 places - db/upgrade.php, db/install.php
 *
 * @return bool A status indicating success or failure
 */
function set_default_config() {
	// hidden setting for repository
	// other settings have to be entered in by the installer/upgrader
    if (set_config('mailto', 'Moodledata.FE@cla.co.uk', 'cla_survey')) {     // Moodledata.FE@cla.co.uk as advised by customer
    	return true;
    }
    return false;
}

/**
 * Make a 'proper' csv line - all values are quoted including numerical
 *
 * PHP's putscsv just seems not to do exactly what was required
 *
 * @param array $arr array of fields for the CSV line
 * @return string a line of quoted CSV values to be written to the CSV file
 */
function _makecsvline($arr) {
    $csv = join(', ', array_map('_quotevalues', $arr)) . "\n";
    return $csv;
}

/**
 * called by array map in _makecsvline() to enclose values in quotes
 *
 * @param string $n the value that needs to be encompaseed in quote marks
 * @return string $n enclosed in quotes
 */
function _quotevalues($n) {
    return '"' . $n . '"';
}

/* ?> */
