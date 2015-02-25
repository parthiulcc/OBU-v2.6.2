<?php
class block_course_contacts extends block_base {
    public function init() {
		global $USER;
        $this->title = get_string('course_contacts', 'block_course_contacts');
    }
	
	//a custom function for shortening names
	public function shorten_name($lname){
		if (strpos($lname, '-')){
			$names = explode('-', $lname);
			$lname = '';
			foreach($names as $name){
				if (strlen($name)>6){
					$name = substr($name, 0, 1);
				}
				$lname .= $name."-";
			}
			$lname = substr($lname, 0, -1);
		}	
		if (strpos($lname, ' ')){
			$names = explode(' ', $lname);
			$lname = $names[0];
		}		
		return $lname;
	}	
	
	/**
	 * Gets all the users assigned this role in this context or higher
	 *
	 * @param int $roleid (can also be an array of ints!)
	 * @param context $context
	 * @param bool $parent if true, get list of users assigned in higher context too
	 * @param string $fields fields from user (u.) , role assignment (ra) or role (r.)
	 * @param string $sort sort from user (u.) , role assignment (ra) or role (r.)
	 * @param bool $gethidden_ignored use enrolments instead
	 * @param string $group defaults to ''
	 * @param mixed $limitfrom defaults to ''
	 * @param mixed $limitnum defaults to ''
	 * @param string $extrawheretest defaults to ''
	 * @param string|array $whereparams defaults to ''
	 * @return array
	 */
	private function get_role_users($roleid, $context, $parent = false, $fields = '',
			$sort = 'u.lastname, u.firstname', $gethidden_ignored = null, $group = '',
			$limitfrom = '', $limitnum = '', $extrawheretest = '', $whereparams = array()) {
		global $DB;

		if (empty($fields)) {
			$fields = 'u.id, u.confirmed, u.username, u.firstname, u.lastname, '.
					  'u.maildisplay, u.mailformat, u.maildigest, u.email, u.emailstop, u.city, '.
					  'u.country, u.picture, u.idnumber, u.department, u.institution, '.
					  'u.lang, u.timezone, u.lastaccess, u.mnethostid, r.name AS rolename, r.sortorder';
		}

		$parentcontexts = '';
		if ($parent) {
			$parentcontexts = substr($context->path, 1); // kill leading slash
			$parentcontexts = str_replace('/', ',', $parentcontexts);
			if ($parentcontexts !== '') {
				$parentcontexts = ' OR ra.contextid IN ('.$parentcontexts.' )';
			}
		}

		if ($roleid) {
			list($rids, $params) = $DB->get_in_or_equal($roleid, SQL_PARAMS_QM);
			$roleselect = "AND ra.roleid $rids";
		} else {
			$params = array();
			$roleselect = '';
		}

		if ($group) {
			$groupjoin   = "JOIN {groups_members} gm ON gm.userid = u.id";
			$groupselect = " AND gm.groupid = ? ";
			$params[] = $group;
		} else {
			$groupjoin   = '';
			$groupselect = '';
		}

		array_unshift($params, $context->id);

		if ($extrawheretest) {
			$extrawheretest = ' AND ' . $extrawheretest;
			$params = array_merge($params, $whereparams);
		}

		$sql = "SELECT $fields, ra.roleid
				  FROM {role_assignments} ra
				  JOIN {user} u ON u.id = ra.userid
				  JOIN {role} r ON ra.roleid = r.id
			$groupjoin
				 WHERE (ra.contextid = ? $parentcontexts)
					   $roleselect
					   $groupselect
					   $extrawheretest
			  GROUP BY $fields, ra.roleid
			  ORDER BY $sort";                  // join now so that we can just use fullname() later

		return $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
	}
	
	public function get_content(){
		global $CFG, $DB, $OUTPUT, $USER, $COURSE;
		
		//if the user hasnt configured the plugin, set these as defaults
		if (empty($this->config)){
			$this->config = new stdclass();
			$this->config->role_3 = 1;
			$this->config->email = 1;
			$this->config->message = 1;
			$this->config->phone = 0;
		}
		
		$courseid = $this->page->course->id;
		
		if ($this->content !== null){
			return $this->content;
		}
		
		$this->content = new stdClass;		

		$context = $this->page->context;
		$content = '';
		//find the roles available on this course
		$roles = array_reverse(get_default_enrol_roles($context,NULL), TRUE);
		$content .= html_writer::start_tag('div', array('class' => 'box'));
		
		//how are we going to sort the contacts?
		$orderby = 'u.lastname'; //default
		if(!empty($this->config->sortby)){
			switch($this->config->sortby){
				case 0:
					$orderby = 'u.lastname, u.firstname';
					break;
				case 1:
					$orderby = 'u.lastaccess DESC';
					break;
				case 2:
					$orderby = 'MIN(ra.timemodified)';
					break;
				default:
					$orderby = 'u.lastname, u.firstname';
					break;
			}
		}
		
		//step through each role and check that the config is set to display
		$inherit = 0;
		if(isset($this->config->inherit)){
			$inherit = $this->config->inherit;
		}
		$userfields = 'u.id,u.lastaccess,u.firstname,u.lastname,u.email,u.phone1,u.picture,u.imagealt';
		//debugging($context->id);
		foreach($roles as $key=>$role){
			$att = 'role_'.$key;
			if(!empty($this->config->$att)){
				if($this->config->$att == 1){
					$contacts = $this->get_role_users($key, $context, $inherit, $userfields, $orderby, null, '', '', 30);
					
					//because the role search finds the custom name and the proper name in brackets
					if(!empty($contacts)){
						if($shortened = strstr($role, '(', TRUE)){
							$content .= html_writer::tag('h2', trim($shortened));
						}else{
							$content .= html_writer::tag('h2', $role);				
						}
					}
					//now display each contact
					foreach ($contacts as $contact){
						
						$content .= html_writer::start_tag('div', array('class' => 'ccard'));
						$content .= $OUTPUT->user_picture($contact, array('size'=>50));
						$content .= html_writer::start_tag('div', array('class' => 'info'));
						if($contact->lastaccess > (time()-300)){
							//online :)!
							$status='online';
						}else{
							//offline :(!	
							$status='offline';
						}
						$content .= html_writer::start_tag('div', array('class' => 'name '.$status));
						$content .= $this->shorten_name($contact->firstname)." ".$this->shorten_name($contact->lastname);
						$content .= html_writer::end_tag('div');
						$content .= html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url($status, 'block_course_contacts'), 'title'=>get_string($status, 'block_course_contacts'),'alt'=>get_string($status, 'block_course_contacts'), 'class'=>'status'));
						$content .= html_writer::empty_tag('hr');
						$content .= html_writer::start_tag('div', array('class' => 'comms'));
					    
						//unless they are us
						if ($USER->id != $contact->id){
							//should we display email?
							if ($this->config->email == 1){
								if ($CFG->block_co_co_simpleemail){
									$url = new moodle_url('/blocks/course_contacts/email.php', array('touid'=>$contact->id, 'cid'=>$COURSE->id));
								}
								else{
									$url = 'mailto:'.strtolower($contact->email);
								}
								$content .= html_writer::link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('mail', 'block_course_contacts'), 'title'=>get_string('email', 'block_course_contacts').' '.$contact->firstname,'alt'=>get_string('email', 'block_course_contacts').' '.$contact->firstname)),array('target'=>'_blank'));
							}
							//what about messages?
							if ($this->config->message == 1){
								$url = new moodle_url('/message/index.php', array('id'=>$contact->id));
								$content .= html_writer::link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('message', 'block_course_contacts'), 'title'=>get_string('message', 'block_course_contacts').' '.$contact->firstname,'alt'=>get_string('message', 'block_course_contacts').' '.$contact->firstname)),array('target'=>'_blank'));
							}
							//and phone numbers?
							if ($this->config->phone == 1 && $contact->phone1 != ""){
								$url = 'tel:'.$contact->phone1;
								$content .= html_writer::link($url, html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('phone', 'block_course_contacts'), 'title'=>get_string('phone', 'block_course_contacts').' '.$contact->phone1,'alt'=>get_string('phone', 'block_course_contacts').' '.$contact->phone1)),array());
							}
						}
						
						$content .= html_writer::end_tag('div');				
						$content .= html_writer::end_tag('div');				
						$content .= html_writer::end_tag('div');				
					}
				}
			}
		}
		$content .= html_writer::end_tag('div');
		
		$this->content->text = $content;
		return $this->content;
	}
	
	public function instance_allow_config() {
		return true;
	}
	
}
?>