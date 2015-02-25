<?php

require_once('../../config.php');
require_once('email_form.php');
require_login();
$courseid = optional_param('cid', 0, PARAM_INT);
$touid = optional_param('touid', 0, PARAM_INT);
$messages = array();

//sort out the course
if($courseid <= 0){
	$courseid = $SESSION->block_course_contacts_lastcourse;
}
$course = $DB->get_record('course', array('id' => $courseid));
if(!$course){
    //print_error('no_course', 'block_course_contacts', '', $courseid);
	$messages[] = get_string('no_course', 'block_course_contacts');
}
$SESSION->block_course_contacts_lastcourse = $course->id;
require_login($course);
$context = get_context_instance(CONTEXT_COURSE, $courseid);

// Get the email address for our contact
if($touid <= 0){
	$touid = $SESSION->block_course_contacts_lastrecipient;
}
if(!is_enrolled($context,$touid, null, true)){
	//print_error('recipient_not_enrolled', 'block_course_contacts', '', $courseid);
	$messages[] = get_string('recipient_not_enrolled', 'block_course_contacts');
}
$mailto = $DB->get_record('user', array('id' => $touid));
$SESSION->block_course_contacts_lastrecipient = $touid;

$modname = get_string('pluginname', 'block_course_contacts');
$header = get_string('sendanemail', 'block_course_contacts');

$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->navbar->add($modname);
$PAGE->navbar->add($header);
$PAGE->set_title($modname . ': '. $header);
$PAGE->set_heading($modname . ': '.$header);
$PAGE->set_url('/course/view.php?id='.$courseid);
//$PAGE->set_pagelayout('popup');


$form = new simple_email_form(null, array(
	'mailto' => $mailto->email,
	'touid' => $touid,
	'cid'=>$course->id
));

if ($form->is_cancelled()) {
	unset($SESSION->block_course_contacts_lastcourse);
	unset($SESSION->block_course_contacts_lastrecipient);
    redirect(new moodle_url('/course/view.php?id='.$courseid));
} else if ($data = $form->get_data()) {
    $email = $data;
    $email->message = $email->message['text'];
	$result = false;
	if($data->mailto == $mailto->email && $data->cid == $courseid){	
		$result = email_to_user($mailto, $USER, $email->subject, strip_tags($email->message),$email->message);
	} 
	else{
		//debugging($data->mailto.' == '.$mailto->email);
		//debugging($data->cid.' == '.$courseid);
		$messages[] = get_string('invalid_request', 'block_course_contacts');
	}
	
	if($result){
		$messages[] = get_string('email_sent', 'block_course_contacts');
	}
	else{
		$messages[] = get_string('email_not_sent', 'block_course_contacts');
	}
}

echo $OUTPUT->header();
echo $OUTPUT->heading($modname);

// Print out messages
if(count($messages)>0){
	echo html_writer::start_tag('div', array('class' => 'cocoemailmsgs'));
	foreach($messages as $message) {
		echo $OUTPUT->notification($message);
	}
	$url = new moodle_url('/course/view.php?id='.$courseid);
	echo html_writer::link($url, get_string('return_to_course', 'block_course_contacts'),array('id' => 'returnlink'));
	echo html_writer::end_tag('div');
}
else{
	if(!$data){
		$form->display();
	}
}
echo $OUTPUT->footer();
