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
 * Question type class for the vmatching question type.
 *
 * @package    qtype
 * @subpackage vmatch
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');


/**
 * The vmatching question type class.
 *
 * @copyright  1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_vmatch extends question_type {

    public function get_question_options($question) {
        global $DB;
        parent::get_question_options($question);
        $question->options = $DB->get_record('question_vmatch', array('question' => $question->id));
        $question->options->subquestions = $DB->get_records('question_vmatch_sub',
                array('question' => $question->id), 'id ASC');
        return true;
    }

    public function save_question_options($question) {
        global $DB;
        $context = $question->context;
        $result = new stdClass();

        $oldsubquestions = $DB->get_records('question_vmatch_sub',
                array('question' => $question->id), 'id ASC');

        // $subquestions will be an array with subquestion ids
        $subquestions = array();

        // Insert all the new question+answer pairs
        foreach ($question->subquestions as $key => $questiontext) {
            if ($questiontext['text'] == '' && trim($question->subanswers[$key]) == '') {
                continue;
            }
            if ($questiontext['text'] != '' && trim($question->subanswers[$key]) == '') {
                $result->notice = get_string('novmatchinganswer', 'qtype_vmatch', $questiontext);
            }

            // Update an existing subquestion if possible.
            $subquestion = array_shift($oldsubquestions);
            if (!$subquestion) {
                $subquestion = new stdClass();
                // Determine a unique random code
                $subquestion->code = rand(1, 999999999);
                while ($DB->record_exists('question_vmatch_sub',
                        array('code' => $subquestion->code, 'question' => $question->id))) {
                    $subquestion->code = rand(1, 999999999);
                }
                $subquestion->question = $question->id;
                $subquestion->questiontext = '';
                $subquestion->answertext = '';
                $subquestion->id = $DB->insert_record('question_vmatch_sub', $subquestion);
            }

            $subquestion->questiontext = $this->import_or_save_files($questiontext,
                    $context, 'qtype_vmatch', 'subquestion', $subquestion->id);
            $subquestion->questiontextformat = $questiontext['format'];
            $subquestion->answertext = trim($question->subanswers[$key]);

            $DB->update_record('question_vmatch_sub', $subquestion);

            $subquestions[] = $subquestion->id;
        }

        // Delete old subquestions records
        $fs = get_file_storage();
        foreach ($oldsubquestions as $oldsub) {
            $fs->delete_area_files($context->id, 'qtype_vmatch', 'subquestion', $oldsub->id);
            $DB->delete_records('question_vmatch_sub', array('id' => $oldsub->id));
        }

        // Save the question options.
        $options = $DB->get_record('question_vmatch', array('question' => $question->id));
        if (!$options) {
            $options = new stdClass();
            $options->question = $question->id;
            $options->correctfeedback = '';
            $options->partiallycorrectfeedback = '';
            $options->incorrectfeedback = '';
            $options->id = $DB->insert_record('question_vmatch', $options);
        }

        $options->subquestions = implode(',', $subquestions);
        $options->shuffleanswers = $question->shuffleanswers;
        $options = $this->save_combined_feedback_helper($options, $question, $context, true);
        $DB->update_record('question_vmatch', $options);

        $this->save_hints($question, true);

        if (!empty($result->notice)) {
            return $result;
        }

        if (count($subquestions) < 3) {
            $result->notice = get_string('notenoughanswers', 'question', 3);
            return $result;
        }

        return true;
    }

    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);

        $question->shufflestems = $questiondata->options->shuffleanswers;
        $this->initialise_combined_feedback($question, $questiondata, true);

        $question->stems = array();
        $question->choices = array();
        $question->right = array();

        foreach ($questiondata->options->subquestions as $vmatchsub) {
            $ans = $vmatchsub->answertext;
            $key = array_search($vmatchsub->answertext, $question->choices);
            if ($key === false) {
                $key = $vmatchsub->id;
                $question->choices[$key] = $vmatchsub->answertext;
            }

            if ($vmatchsub->questiontext !== '') {
                $question->stems[$vmatchsub->id] = $vmatchsub->questiontext;
                $question->stemformat[$vmatchsub->id] = $vmatchsub->questiontextformat;
                $question->right[$vmatchsub->id] = $key;
            }
        }
    }

    protected function make_hint($hint) {
        return question_hint_with_parts::load_from_record($hint);
    }

    public function delete_question($questionid, $contextid) {
        global $DB;
        $DB->delete_records('question_vmatch', array('question' => $questionid));
        $DB->delete_records('question_vmatch_sub', array('question' => $questionid));

        parent::delete_question($questionid, $contextid);
    }

    public function get_random_guess_score($questiondata) {
        $q = $this->make_question($questiondata);
        return 1 / count($q->choices);
    }

    public function get_possible_responses($questiondata) {
        $subqs = array();

        $q = $this->make_question($questiondata);

        foreach ($q->stems as $stemid => $stem) {

            $responses = array();
            foreach ($q->choices as $choiceid => $choice) {
                $responses[$choiceid] = new question_possible_response(
                        $q->html_to_text($stem, $q->stemformat[$stemid]) . ': ' . $choice,
                        ($choiceid == $q->right[$stemid]) / count($q->stems));
            }
            $responses[null] = question_possible_response::no_response();

            $subqs[$stemid] = $responses;
        }

        return $subqs;
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        global $DB;
        $fs = get_file_storage();

        parent::move_files($questionid, $oldcontextid, $newcontextid);

        $subquestionids = $DB->get_records_menu('question_vmatch_sub',
                array('question' => $questionid), 'id', 'id,1');
        foreach ($subquestionids as $subquestionid => $notused) {
            $fs->move_area_files_to_new_context($oldcontextid,
                    $newcontextid, 'qtype_vmatch', 'subquestion', $subquestionid);
        }

        $this->move_files_in_combined_feedback($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        global $DB;
        $fs = get_file_storage();

        parent::delete_files($questionid, $contextid);

        $subquestionids = $DB->get_records_menu('question_vmatch_sub',
                array('question' => $questionid), 'id', 'id,1');
        foreach ($subquestionids as $subquestionid => $notused) {
            $fs->delete_area_files($contextid, 'qtype_vmatch', 'subquestion', $subquestionid);
        }

        $this->delete_files_in_combined_feedback($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }
}
