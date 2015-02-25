<?php
require_once($CFG->libdir . '/formslib.php');
class simple_email_form extends moodleform {
    public function definition() {
        global $CFG, $USER, $COURSE, $OUTPUT;

        $mform =& $this->_form;
		
        $mailto = $this->_customdata['mailto'];
        $touid = $this->_customdata['touid'];

        $context= get_context_instance(CONTEXT_COURSE, $COURSE->id);

		$mform->addElement('header', 'simpleemail_topsection',
		get_string('sendanemail', 'block_course_contacts'));

		
		$mform->addElement('hidden', 'mailto', $mailto);
		$mform->addElement('hidden', 'cid', $COURSE->id);
		$mform->addElement('static', 'emailinfo', '', str_replace('{recipient}', strtolower($mailto), get_string('emailinfo', 'block_course_contacts')));
		$mform->addElement('html', '<br />');
        $mform->addElement('static', 'from', get_string('from', 'block_course_contacts'), strtolower($USER->email));
        $mform->addElement('static', 'to', get_string('to', 'block_course_contacts'), strtolower($mailto)); 

        $mform->addElement('text', 'subject', get_string('subject', 'block_course_contacts'));
        $mform->setType('subject', PARAM_TEXT);
		$mform->setDefault('subject', $COURSE->fullname);
        $mform->addRule('subject', null, 'required');

        $mform->addElement('editor', 'message', get_string('message', 'block_course_contacts'));
		$mform->addRule('message', null, 'required');

		if (!empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey) && $CFG->block_co_co_recaptcha) {
			$mform->addElement('recaptcha', 'recaptcha_element', get_string('recaptcha', 'auth'));
		}

        $buttons = array();
        $buttons[] =& $mform->createElement('submit', 'send', get_string('send', 'block_course_contacts'));
        $buttons[] =& $mform->createElement('cancel', 'cancel', get_string('cancel'));

        $mform->addGroup($buttons, 'buttons', '', array(' '), false);
    }

    function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);
		if (!empty($CFG->recaptchapublickey) && !empty($CFG->recaptchaprivatekey) && $CFG->block_co_co_recaptcha) {
            $recaptcha_element = $this->_form->getElement('recaptcha_element');
            if (!empty($this->_form->_submitValues['recaptcha_challenge_field'])) {
                $challenge_field = $this->_form->_submitValues['recaptcha_challenge_field'];
                $response_field = $this->_form->_submitValues['recaptcha_response_field'];
                if (true !== ($result = $recaptcha_element->verify($challenge_field, $response_field))) {
                    $errors['recaptcha'] = $result;
                }
            } else {
                $errors['recaptcha'] = get_string('missingrecaptchachallengefield');
            }
        }
		return $errors;
	}
	
}