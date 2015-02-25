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
 * @package    qtype
 * @subpackage vmatch
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * vmatching question type conversion handler
 */
class moodle1_qtype_vmatch_handler extends moodle1_qtype_handler {

    /**
     * @return array
     */
    public function get_question_subpaths() {
        return array(
            'VMATCHOPTIONS',
            'VMATCHS/VMATCH'
        );
    }

    /**
     * Appends the vmatch specific information to the question
     */
    public function process_question(array $data, array $raw) {
        global $CFG;

        // populate the list of vmatches first to get their ids
        // note that the field is re-populated on restore anyway but let us
        // do our best to produce valid backup files
        $vmatchids = array();
        if (isset($data['vmatchs']['vmatch'])) {
            foreach ($data['vmatchs']['vmatch'] as $vmatch) {
                $vmatchids[] = $vmatch['id'];
            }
        }

        // convert vmatch options
        if (isset($data['vmatchoptions'])) {
            $vmatchoptions = $data['vmatchoptions'][0];
        } else {
            $vmatchoptions = array('shuffleanswers' => 1);
        }
        $vmatchoptions['id'] = $this->converter->get_nextid();
        $vmatchoptions['subquestions'] = implode(',', $vmatchids);
        $this->write_xml('vmatchoptions', $vmatchoptions, array('/vmatchoptions/id'));

        // convert vmatches
        $this->xmlwriter->begin_tag('vmatches');
        if (isset($data['vmatchs']['vmatch'])) {
            foreach ($data['vmatchs']['vmatch'] as $vmatch) {
                // replay the upgrade step 2009072100
                $vmatch['questiontextformat'] = 0;
                if ($CFG->texteditors !== 'textarea' and $data['oldquestiontextformat'] == FORMAT_MOODLE) {
                    $vmatch['questiontext'] = text_to_html($vmatch['questiontext'], false, false, true);
                    $vmatch['questiontextformat'] = FORMAT_HTML;
                } else {
                    $vmatch['questiontextformat'] = $data['oldquestiontextformat'];
                }

                $this->write_xml('vmatch', $vmatch, array('/vmatch/id'));
            }
        }
        $this->xmlwriter->end_tag('vmatches');
    }
}
