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
 * The work horse of the plugin - displayed in a frame or 'object'
 *
 * Links back into Moodle via the required Javascript in cla_survey_js.php
 *
 * @package repository_cla_survey
 * @copyright 2014 Copyright Licencing Authority (UK)
 * @author   Benjamin Ellis Mukudu Ltd - benjamin.c.ellis@gmail.com
 * @license http:// www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('../lib.php');             // repository lib
require_once($CFG->libdir.'/filelib.php');

require_once($CFG->libdir . '/licenselib.php');     // for licences
// require_once('cla_survey_form_moodle.php');   // See the comments in cla_survey_form_moodle.php

global $CFG, $DB, $PAGE, $USER;

// require_sesskey();
require_login();

if ($CFG->version >= 2012061800) {
	// support for IE8 in versions above Moodle 2.3 is still frame based.
	$agent = $_SERVER['HTTP_USER_AGENT'];
	if (stristr($_SERVER['HTTP_USER_AGENT'],'MSIE 8')) {
		echo('IE8 is not supported by this plugin for Moodle version 2.3 and over');
		die;
	}
}


/*
 * INPUTS
 */
// required
$courseid = required_param('course', PARAM_INT);
$myrepoid = required_param('myrepoid', PARAM_INT);           // this is the final repo id - we just piggy back on other repos
// get context details
$context = get_context_instance(CONTEXT_COURSE, $courseid);
// other params
$theaction = optional_param('submit2', '', PARAM_TEXT) ?
                optional_param('submit2', '', PARAM_TEXT): optional_param('submit1', '', PARAM_TEXT);
$action = optional_param('action', '', PARAM_TEXT);
/* end inputs */

// default page setup
$PAGE->set_url('/repository/cla_survey/cla_survey_forms.php');
// user context
$usercontext = get_context_instance(CONTEXT_USER, $USER->id);
$PAGE->set_context($usercontext);

// course details
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourseid');
}
$PAGE->set_pagelayout('embedded');
$PAGE->set_course($course);
$PAGE->set_heading($SITE->fullname);
$PAGE->set_title('CLA Survey');

if ($action == 'browseserver') {
    // lets try and use the server files repository to do this
    require_once('../local/lib.php');       // upload repo
    $itemid      = optional_param('itemid', '',     PARAM_INT);

    // parameters for repository
    $contextid   = optional_param('ctx_id', $context->id, PARAM_INT);    // context ID
    $filename    = optional_param('filename', '',    PARAM_FILE);
    $fileurl     = optional_param('fileurl', '',     PARAM_RAW);
    $repositoryid     = optional_param('repo_id', 0,      PARAM_INT);    // repository ID
    $reqpath    = optional_param('p', '',           PARAM_RAW);    // the path in repository
    $currentpage   = optional_param('page', '',        PARAM_RAW);    // What page in repository?
    $maxbytes    = optional_param('maxbytes', 0,  PARAM_INT);    // maxbytes

    // the path to save files
    $savepath = optional_param('savepath', '/', PARAM_PATH);
    // path in draft area
    $draftpath = optional_param('draftpath', '/',    PARAM_PATH);

    // repository using local
    if ($repositoryid == 0) {
        if ($uploadrepo = $DB->get_record('repository', array('type' => 'local'))) {
            $repository = $DB->get_record('repository_instances', array('typeid' => $uploadrepo->id));
        }
        $repositoryid = $repository->id;
    } else {
        $repository = $DB->get_record('repository_instances', array('id' => $repositoryid));
    }

    $params = array(
        'action' => 'browseserver',
        'ctx_id' => $contextid,
        'itemid' => $itemid,
        'course' => $courseid,
        'maxbytes' => $maxbytes,
        'sesskey' => sesskey(),
        'savepath' => $savepath,
        'repo_id' => $repositoryid,
        'myrepoid' => $myrepoid
    );
    $url = new moodle_url($CFG->httpswwwroot."/repository/cla_survey/cla_survey.php", $params);
    $homeurl = new moodle_url($CFG->httpswwwroot."/repository/cla_survey/cla_survey.php",
        array('course' => $courseid, 'myrepoid' => $myrepoid));

    // use the local repository for this
    $serverepo = new repository_local($repositoryid, $context, array('ajax' => false, 'name' => $repository->name, 'type' => 'local'), true);

    echo $OUTPUT->header();

    echo $OUTPUT->container_start();
    $button = html_writer::start_tag('button', array('type' => 'button', 'id' => 'cancel', 'name' => 'cancel'));
    $button .= 'Cancel' . html_writer::end_tag('button');
    echo html_writer::link($homeurl, $button);      // this should goes back to theinput screen  and should be a button
    echo $OUTPUT->container_end();

    $list = $serverepo->get_listing($reqpath, $currentpage);
    $dynload = !empty($list['dynload'])?true:false;

    if (!empty($list['path'])) {
        foreach ($list['path'] as $p) {
            $pathurl = new moodle_url($url, array(
                'p' => $p['path'],
                'draftpath' => $draftpath,
                'savepath' => $savepath
                ));
            echo '<strong>' . html_writer::link($pathurl, s($p['name'])) . '</strong>';
            echo '<span> / </span>';
        }
    }
    if (!empty($list['page'])) {
        // TODO MDL-28482: need a better solution
        // paging_bar is not a good option because it starts page numbering from 0 and
        // repositories number pages starting from 1.
        $purl = "$CFG->httpswwwroot/repository/cla_survey.php?action=browseserver&itemid=$itemid";
        $purl = $purl . "&ctx_id=$contextid&repo_id=$repositoryid&course=$courseid&sesskey=".sesskey();
        $pagingurl = new moodle_url($purl);
        if (!isset($list['perpage']) && !isset($list['total'])) {
            $list['perpage'] = 10; // instead of setting perpage&total we use number of pages, the result is the same
        }
        if (empty($list['total'])) {
            if ($list['pages'] == -1) {
                $total = ($list['page'] + 2) * $list['perpage'];
            } else {
                $total = $list['pages'] * $list['perpage'];
            }
        } else {
            $total = $list['total'];
        }
        echo $OUTPUT->paging_bar($total, $list['page'], $list['perpage'], $pagingurl);
    }
    echo '<table>';
    foreach ($list['list'] as $item) {
        echo '<tr>';
        echo '<td><img src="'.$item['thumbnail'].'" />';
        echo '</td><td>';
        if (!empty($item['url'])) {
            echo html_writer::link($item['url'], s($item['title']), array('target' => '_blank'));
        } else {
            if (isset($item['children'])) {         // folder
                echo '<a href="'. $url . '&p=' . s($item['path']) . '">' . s($item['title']) . '</a>';
            } else {
                $fileurl = new moodle_url($url, array(
                    'fileurl' => s($item['source']),
                    'action' => 'confirm'
                ));
                echo '<a href="'. $fileurl . '&p=' . s($fileurl) . '">' . s($item['title']) . '</a>';
            }
        }
        echo '</td></tr>';
    }
    echo '</table>';

    echo $OUTPUT->footer();

    // die;          // hate doing this


} else if ($theaction) {                  // we have some input to work with
    // standard upload fields
    $saveas = optional_param('saveas', '', PARAM_FILE);             // save as file name
    $author = optional_param('author', '', PARAM_TEXT);
    $license = optional_param('license', 'public', PARAM_ALPHA);
    $maxbytes = get_max_upload_file_size($CFG->maxbytes);
    $copytype = optional_param('copytype', '', PARAM_TEXT);

    // server files specific
    $filename = optional_param('filename', '', PARAM_TEXT);         // Server File
    $source = optional_param('filesource', '', PARAM_RAW);          // server file source

    // CLA specific fields
    $sourceurl = optional_param('sourceurl', '', PARAM_URL);
    $copytitle = optional_param('copytitle', '', PARAM_TEXT);
    $copiedid = optional_param('copiedid', '', PARAM_INT);
    $copiedauthor = optional_param('copiedauthor', '', PARAM_TEXT);
    $publisher = optional_param('publisher', '', PARAM_TEXT);
    $frompage = optional_param('frompage', '', PARAM_INT);
    $topage = optional_param('topage', '', PARAM_INT);
    $totalpages = optional_param('totalpages', '', PARAM_INT);
    $sourcetype = optional_param('sourcetype', '', PARAM_TEXT);
    $saveaspath = optional_param('savepath', '/', PARAM_TEXT);
    $itemid = optional_param('itemid', 0, PARAM_INT);

    // fix up the values for the copy type
    if ($sourcetype) {
        if ($sourcetype != 'Web') {
            if ($sourcetype == 'Digital') {
                if ($copytype == 'book') {
                    $copytype = get_string('ebooklabel', 'repository_cla_survey');
                } else if ($copytype == 'magazine') {
                    $copytype = get_string('emagazinelabel', 'repository_cla_survey');
                }
            } else if ($sourcetype == 'Print') {
                if ($copytype == 'book') {
                    $copytype = get_string('booklabel', 'repository_cla_survey');
                } else if ($copytype == 'magazine') {
                    $copytype = get_string('magazinelabel', 'repository_cla_survey');
                }
            }
        }
    }

    // defaults
    $event = '';
    $url = '';
    $newfilename = '';
    $newfilepath = '';
    $existingfilename = '';
    $existingfilepath = '';

    // this should never happen - but this is a check for dev properties
    if ($itemid == 0) {
        die("There has been a programming error - item id is 0");
    }

    $repostype = $source ? 'local' : 'upload';              // are we uploading or using server files

    // get the repository id for upload repo - we are riding on the library's back
    if ($uploadrepo = $DB->get_record('repository', array('type' => $repostype))) {
        $repository = $DB->get_record('repository_instances', array('typeid' => $uploadrepo->id));
    }
    $repositoryid = $repository->id;

    if ($repostype == 'upload') {           // upload functionality
        require_once('../upload/lib.php');      // upload repo

        $uploadresult = '';
        // repository::check_capability($context->id, $repository);
        $uploadrepo = new repository_upload($repositoryid, $context,
            array('ajax' => false, 'name' => $repository->name, 'type' => 'upload'));

        // attempt the upload
        try {
            $uploadresult = $uploadrepo->upload($saveas, $maxbytes);
        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }

        $event = isset($uploadresult['event']) ? $uploadresult['event'] : '';
        if ($event) {
            if ($event == 'fileexists') {
                $newfilename = $uploadresult['newfile']->filename;
                $newfilepath = $uploadresult['newfile']->filepath;
                $existingfilename = $uploadresult['existingfile']->filename;
                $existingfilepath = $uploadresult['existingfile']->filepath;
                // for the javascript
                $filename = $uploadresult['newfile']->filename;
                $url = $uploadresult['newfile']->url;
                $source = base64_encode(serialize((object)array('url' => $url, 'filename' => $filename)));
            } else {
                print_error("Broken");
                // die;
            }
        } else {
            $filename = $uploadresult['file'];
            $itemid = $uploadresult['id'];
            $url = $uploadresult['url'];
            $source = base64_encode(serialize((object)array('url' => $url, 'filename' => $filename)));
        }
    }   // else filename and source are set from the form

    // save to database if required - checking for an error before
    if ($copycheck = optional_param('copyright', 0, PARAM_INT)) {
        if ((!$event) || ($event == 'fileexists')) {
            // CLA survey specific code
            $record = new stdClass();
            $record->thedate = time();
            $record->account = get_config('cla_survey', 'accountno');        // v
            $record->title = $copytitle ? $copytitle : $sourceurl;
            $record->istitleuri = $sourceurl ? 1 : 0;
            $record->publisher = $publisher;
            $record->author = $copiedauthor;
            $record->identifier = $copiedid;
            if ($record->identifier) {
                $record->identifiertype = (strlen($copiedid) == 8) ? 'ISSN' : 'ISBN';
            } else {
                $record->identifiertype = '';
            }
            $record->publicationtype = $copytype;   // E-book, E-magazine/journal, Book, Magazine/journal
            $record->usagetype = $sourcetype;
            $record->copies = 1;
            $record->pagefrom = $frompage;
            $record->pageto = $topage;
            $record->pagecount = $totalpages;
            $record->filesource = $source;

            try {
                $newid = $DB->insert_record('repository_cla_survey', $record, true);
            } catch (Exception $ex) {
                echo $ex->getMessage();
                if (!empty($ex->error)) {
                    echo $ex->error;// since dml_exception stores it here
                }
                die;
            }
        }
    }
    header('Content-Type: text/html; charset=utf-8');
    include_once('cla_survey_js.php');          // this is the javascipt to connect back to the filepicker
    die();
} else {          // default action

    if ($action == 'confirm') {             // this is the selected server file
        $source = required_param('fileurl', PARAM_RAW);
        $filedets = unserialize(base64_decode($source));
        $filename = $filedets['filename'];
    }

    // javascript stuff
    $jsmodule = array(
        'name' => 'repository_cla_survey',
        'fullpath' => '/repository/cla_survey/cla_survey.js',
        'requires' => array('base', 'node')
    );
    // load and initialise the js
    $PAGE->requires->js_init_call('M.repository_cla_survey.init', array(), false, $jsmodule);

    $formdata = array();
    $formdata['filename'] = isset($filename) ? $filename : '';
    $formdata['filesource'] = isset($source) ? $source : '';
    $formdata['author'] = fullname($USER);
    $formdata['myrepoid'] = $myrepoid;
    $formdata['licenses'] = array();
    if (!empty($CFG->licenses)) {
        $array = explode(',', $CFG->licenses);
        foreach ($array as $license) {
            $formdata['licenses'][$license] = get_string($license, 'license');
        }
    }
    $formdata['defaultlicense'] = empty($CFG->sitedefaultlicense)? $CFG->sitedefaultlicense: 'allrightsreserved';
    $ps = array('action' => 'browseserver', 'course' => $courseid, 'myrepoid' => $myrepoid);
    $formdata['serverfileurl'] = new moodle_url($CFG->httpswwwroot."/repository/cla_survey/cla_survey.php", $ps);

    // $claform = new cla_survey_form(null, $formdata);// See the comments in cla_survey_form_moodle.php

    // display the editing page
    echo $OUTPUT->header();
    // $claform->display();                  // See the comments in cla_survey_form_moodle.php
    require_once('cla_survey_form.php');    // See the comments in cla_survey_form_moodle.php
    echo $OUTPUT->footer();
}

/* ?> */
