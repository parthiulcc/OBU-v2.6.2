<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/local/ombiel_webservices/externallib.php');

class local_ombiel_webservices_testcase extends advanced_testcase {
    
    public function test_get_user_courses() {
        global $USER;

        $this->resetAfterTest(true);

        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();

        $this->setUser($user1);
        // test with login user
        
        // empty return
        $courses = local_ombiel_webservices::get_user_courses();
        $courses = external_api::clean_returnvalue(local_ombiel_webservices::get_user_courses_returns(), $courses);
        
        $this->assertSame(array(), $courses);        
        
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);  
        $this->getDataGenerator()->enrol_user($user2->id, $course2->id);  
        $this->getDataGenerator()->enrol_user($user3->id, $course2->id);  
        
        // Call the external function without a userid
        $courses = local_ombiel_webservices::get_user_courses();
        $courses = external_api::clean_returnvalue(local_ombiel_webservices::get_user_courses_returns(), $courses);
        
        $this->assertEquals(2, count($courses));
        
        // Call the external function with loggedin userid
        $courses = local_ombiel_webservices::get_user_courses($user1->id);
        $courses = external_api::clean_returnvalue(local_ombiel_webservices::get_user_courses_returns(), $courses);
        
        // Check we retrieve the good total number of courses.
        $this->assertEquals(2, count($courses));
        
        
        // Call the external function with other userid 
        $usercontext = context_user::instance($user2->id, MUST_EXIST);
        
        $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $roleid, $usercontext);
        role_assign($roleid, $USER->id, $usercontext);
        accesslib_clear_all_caches_for_unit_testing();
        $courses = local_ombiel_webservices::get_user_courses($user2->id);
        $courses = external_api::clean_returnvalue(local_ombiel_webservices::get_user_courses_returns(), $courses);
        // Check we retrieve the good total number of courses.
        $this->assertEquals(1, count($courses));
        $course = current($courses);
        $this->assertEquals($course2->id, $course['id']);
        
        
        // Call the external function with other userid - no access
        $this->setExpectedException('moodle_exception');
        $courses = local_ombiel_webservices::get_user_courses($user3->id);

     }
     public function test_get_course_sections() {
        global $CFG, $DB, $USER;

        $this->resetAfterTest(true);
        
        $course = self::getDataGenerator()->create_course();
        $user1 = self::getDataGenerator()->create_user();
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        
        $context = context_course::instance($course->id);
        
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        
        $visibleforuminstance = $generator->create_instance(array('course'=>$course->id, 'section'=>2));
        
        if ($CFG->version < 2013111800) { // Generator adds cm to section in 2.6 onwards
            course_add_cm_to_section($course, $visibleforuminstance->cmid, 2);
        }
        $visiblesection = $DB->get_record('course_sections', array('course'=>$course->id, 'section'=>2));
        
        $visiblesection->summary = '<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />';
        $visiblesection->summaryformat = 1;
        $visiblesection->name = 'Test topic';
        
        $DB->update_record('course_sections', $visiblesection);
        
        $hiddenforuminstance = $generator->create_instance(array('course'=>$course->id, 'section'=>3));
        
        if ($CFG->version < 2013111800) { // Generator adds cm to section in 2.6 onwards
            course_add_cm_to_section($course, $hiddenforuminstance->cmid, 3);
        }
        $hiddensection = $DB->get_record('course_sections', array('course'=>$course->id, 'section'=>3));

        $hiddensection->visible = 0;
        
        $DB->update_record('course_sections', $hiddensection);
        
        $_GET['wstoken'] = md5('test');
        
        $sections = local_ombiel_webservices::get_course_sections($course->id);
        $sections = external_api::clean_returnvalue(local_ombiel_webservices::get_course_sections_returns(), $sections);
        
        // should be an automatically generated section as well as the two created above
        $this->assertEquals(2, count($sections['sections']));
        $lastsection = end($sections['sections']);
        $this->assertEquals($visiblesection->id, $lastsection['id']);
        $this->assertEquals('<img src="'.$CFG->wwwroot.'/webservice/pluginfile.php/'.
                $context->id.'/course/section/'.$visiblesection->id.'/_dummy.jpg" height="20" width="20" alt="_dummy.jpg" />',
                $lastsection['summary']);
        $this->assertEquals($visiblesection->name, $lastsection['name']);
        $this->assertEquals($CFG->wwwroot.'/local/ombiel_webservices/login.php?wstoken='.md5('test').'&userid='.$user1->id.'&courseid='.$course->id, $sections['courselink']);
        $this->assertArrayNotHasKey('echo360link', $sections);
        $this->assertArrayNotHasKey('echo360link', $sections);
        
        $blockinstance = new stdClass();
        $blockinstance->blockname = 'echo360_echocenter';
        $blockinstance->parentcontextid = $context->id;
        $blockinstance->showinsubcontexts = 0;
        $blockinstance->pagetypepattern = 'course-view-*';
        $blockinstance->defaultregion = 'side-post';
        $blockinstance->defaultweight = 1;
        
        $DB->insert_record('block_instances', $blockinstance);
        
        $sections = local_ombiel_webservices::get_course_sections($course->id);
        $sections = external_api::clean_returnvalue(local_ombiel_webservices::get_course_sections_returns(), $sections);
        
        $this->assertEquals($CFG->wwwroot.'/local/ombiel_webservices/login.php?wstoken='.md5('test').'&userid='.$user1->id.'&echo360id='.$course->id,  $sections['echo360link']);
        
            
        // not enrolled on course
        $user2 = self::getDataGenerator()->create_user();
        $this->setUser($user2);
        $this->setExpectedException('moodle_exception');
        $sections = local_ombiel_webservices::get_course_sections($course->id);
         
     }
     public function test_get_section_content() {
        global $CFG, $DB, $USER;

        $this->resetAfterTest(true);
        
        $course = self::getDataGenerator()->create_course();
        $user1 = self::getDataGenerator()->create_user();
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id);
        
        $coursecontext = context_course::instance($course->id);
        
        $labelgenerator = $this->getDataGenerator()->get_plugin_generator('mod_label');
        
        $options = array(
            'course'=>$course->id,
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
            'section'=>2
        );
        $visiblelabelinstance = $labelgenerator->create_instance($options);
        
        if ($CFG->version < 2013111800) { // Generator adds cm to section in 2.6 onwards
            course_add_cm_to_section($course, $visiblelabelinstance->cmid, 2);
        }
        $options = array(
            'course'=>$course->id,
            'section'=>2
        );
        $hiddenlabelinstance = $labelgenerator->create_instance($options);
        
        if ($CFG->version < 2013111800) { // Generator adds cm to section in 2.6 onwards
            course_add_cm_to_section($course, $hiddenlabelinstance->cmid, 2);
        }
        $cm1 = $DB->get_record('course_modules', array('id'=>$hiddenlabelinstance->cmid));
        $cm1->visible = 0;
        $DB->update_record('course_modules', $cm1);
                
        if ($CFG->version >= 2013051400) { // resource generator added in 2.5 
            $resourcegenerator = $this->getDataGenerator()->get_plugin_generator('mod_resource');

            $options = array(
                'course'=>$course->id,
                'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
                'section'=>2,
            );

            $resourceinstance = $resourcegenerator->create_instance($options);
            
            if ($CFG->version < 2013111800) { // Generator adds cm to section in 2.6 onwards  
                course_add_cm_to_section($course, $resourceinstance->cmid, 2);
            }
            $cm2 = $DB->get_record('course_modules', array('id'=>$resourceinstance->cmid));
            $cm2->showdescription = 1;
            $DB->update_record('course_modules', $cm2);   
        }
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        
        $options = array(
            'course'=>$course->id,
            'name'=>'Forum 1',
            'section'=>2,
        );
        
        $foruminstance = $forumgenerator->create_instance($options);
        
        if ($CFG->version < 2013111800) { // Generator adds cm to section in 2.6 onwards  
            course_add_cm_to_section($course, $foruminstance->cmid, 2);
        }
        $cm3 = $DB->get_record('course_modules', array('id'=>$foruminstance->cmid));
        $cm3->indent = 2;
        $DB->update_record('course_modules', $cm3);
        
        $sectionin = $DB->get_record('course_sections', array('course'=>$course->id,'section'=>2));
        
        $sectionin->summary = '<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />';
        $sectionin->summaryformat = 1;
        $sectionin->name = 'Test topic';
        
        $DB->update_record('course_sections', $sectionin);
        
        $_GET['wstoken'] = md5('test');
        
        $sectionout = local_ombiel_webservices::get_section_content($course->id);
        $sectionout = external_api::clean_returnvalue(local_ombiel_webservices::get_section_content_returns(), $sectionout);
        
        $this->assertEquals($sectionin->id, $sectionout['id']);
        $this->assertEquals('<img src="'.$CFG->wwwroot.'/webservice/pluginfile.php/'.
                $coursecontext->id.'/course/section/'.$sectionin->id.'/_dummy.jpg" height="20" width="20" alt="_dummy.jpg" />',
                $sectionout['summary']);
        $this->assertEquals($sectionin->name, $sectionout['name']);         
        $this->assertEquals($CFG->wwwroot.'/local/ombiel_webservices/login.php?wstoken='.md5('test').'&userid='.$user1->id.'&cmid=',$sectionout['baselink']);
        
        if ($CFG->version >= 2013051400) { // resource generator added in 2.5 
            $this->assertEquals(3, count($sectionout['contents']));
        } else {
            $this->assertEquals(2, count($sectionout['contents']));
        }
        $labelout = current($sectionout['contents']);
        
        $cmcontext = context_module::instance($visiblelabelinstance->cmid);
        
        $this->assertEquals($visiblelabelinstance->cmid, $labelout['id']);
        $this->assertEquals('<img src="'.$CFG->wwwroot.'/webservice/pluginfile.php/'.
                $cmcontext->id.'/mod_label/intro/_dummy.jpg" height="20" width="20" />',
                $labelout['description']);        
                
        if ($CFG->version >= 2013051400) { // resource generator added in 2.5 
            $cmcontext = context_module::instance($resourceinstance->cmid);
            $resourceout = next($sectionout['contents']);

            $this->assertEquals($CFG->wwwroot.'/webservice/pluginfile.php/'.
                    $cmcontext->id.'/mod_resource/content/0/resource1.txt?forcedownload=1',
                    $resourceout['contents'][0]['fileurl']);
            $this->assertEquals('<img src="'.$CFG->wwwroot.'/webservice/pluginfile.php/'.
                    $cmcontext->id.'/mod_resource/intro/_dummy.jpg" height="20" width="20" />',
                    $resourceout['description']);      
        }
        $forumout = next($sectionout['contents']);
        $this->assertEquals($foruminstance->cmid, $forumout['id']);
        $this->assertEquals('Forum 1', $forumout['name']);
        $this->assertEquals('forum', $forumout['modname']);
        $this->assertEquals(2, $forumout['indent']);
        
                
        // not enrolled on course
        $user2 = self::getDataGenerator()->create_user();
        $this->setUser($user2);
        $this->setExpectedException('moodle_exception');
        $sectionout = local_ombiel_webservices::get_section_content($course->id);
        
         
     }
     public function test_get_user_assignments() {
        global $CFG, $DB, $USER;
        /**
         * @todo test grade
         */
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        
        $coursecontext = context_course::instance($course1->id);
        
        // stop cmid being the same as instance id (better test)
        $labelgenerator = $this->getDataGenerator()->get_plugin_generator('mod_label');
        $options = array(
            'course'=>$course1->id,
        );
        $labelgenerator->create_instance($options);
        /**
         * Create assignments
         */
        $assignmentgenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        
        $options = array(
            'name'=>'Assignment 1',
            'course'=>$course1->id,
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
        );
        $assignment1instance = $assignmentgenerator->create_instance($options);
        
        $submission = new stdClass;
        $submission->assignment = $assignment1instance->id;
        $submission->userid = $user1->id;
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $DB->insert_record('assign_submission', $submission);
        
        $sitecontext = context_system::instance();
        $roleid = create_role('Calendar', 'Calendar', 'dummy role description');
        assign_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $sitecontext);
        role_assign($roleid, $USER->id, $sitecontext);
        $options = array(
            'name'=>'Assignment 2',
            'course'=>$course2->id,
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
            'duedate'=>1642550401,
        );
        $assignment2instance = $assignmentgenerator->create_instance($options);
        
        $cm2 = $DB->get_record('course_modules', array('id'=>$assignment2instance->cmid));
        $cm2->showdescription = 1;
        $DB->update_record('course_modules', $cm2); 
        /**
         * Default user id - user has assignments
         */
        $assignments = local_ombiel_webservices::get_user_assignments();
        $assignments = external_api::clean_returnvalue(local_ombiel_webservices::get_user_assignments_returns(), $assignments);
  
        $this->assertEquals(2, count($assignments));        
        $this->assertEquals('Assignment 2', $assignments[0]['name']); 
        $cmcontext = context_module::instance($assignment2instance->cmid);
        $this->assertEquals('<img src="'.$CFG->wwwroot.
                '/webservice/pluginfile.php/'.$cmcontext->id.
                '/mod_assign/intro/_dummy.jpg" height="20" width="20" />', 
                $assignments[0]['description']); 
        $this->assertEquals($course2->id, $assignments[0]['courseid']); 
        $this->assertEquals(1642550401, $assignments[0]['deadline']);
        $this->assertEquals(1, $assignments[1]['status']);
        /**
         * Call with logged in user id - user has assignments
         */
        $assignments = local_ombiel_webservices::get_user_assignments($user1->id);
        $assignments = external_api::clean_returnvalue(local_ombiel_webservices::get_user_assignments_returns(), $assignments);
         
        $this->assertEquals(2, count($assignments));
        /**
         * Call with another persons user id with authority- they have no assignments
         */
        $usercontext = context_user::instance($user2->id, MUST_EXIST);
        
        $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $roleid, $usercontext);
        role_assign($roleid, $USER->id, $usercontext);
        accesslib_clear_all_caches_for_unit_testing();
        $assignments = local_ombiel_webservices::get_user_assignments($user2->id);
        $assignments = external_api::clean_returnvalue(local_ombiel_webservices::get_user_assignments_returns(), $assignments);

        $this->assertSame(array(), $assignments);  
        /**
         * Call with another persons user id without authority
         */
        $this->setExpectedException('moodle_exception');
        $courses = local_ombiel_webservices::get_user_assignments($user3->id);
         
     }
     public function test_get_course_assignments() {
        global $CFG, $DB, $USER;
        /**
         * @todo test grade
         */
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();
        $course3 = self::getDataGenerator()->create_course();
        $user1 = self::getDataGenerator()->create_user();
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        
        $coursecontext = context_course::instance($course1->id);

        // stop cmid being the same as instance id (better test)
        $labelgenerator = $this->getDataGenerator()->get_plugin_generator('mod_label');
        $options = array(
            'course'=>$course1->id,
        );
        $labelgenerator->create_instance($options);
         /**
         * Set up assignments
         */       
        $assignmentgenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        
        $options = array(
            'name'=>'Assignment 1',
            'course'=>$course1->id,
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
        );
        $assignment1instance = $assignmentgenerator->create_instance($options);
        
        $submission = new stdClass;
        $submission->assignment = $assignment1instance->id;
        $submission->userid = $user1->id;
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $DB->insert_record('assign_submission', $submission);
        
        $sitecontext = context_system::instance();
        $roleid = create_role('Calendar', 'Calendar', 'dummy role description');
        assign_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $sitecontext);
        role_assign($roleid, $USER->id, $sitecontext);
        $options = array(
            'name'=>'Assignment 2',
            'course'=>$course1->id,
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
            'duedate'=>1642550401,
        );
        $assignment2instance = $assignmentgenerator->create_instance($options);
        
        $cm2 = $DB->get_record('course_modules', array('id'=>$assignment2instance->cmid));
        $cm2->showdescription = 1;
        $DB->update_record('course_modules', $cm2); 
        /**
         * Course has assignments - user is enrolled
         */
        $assignments = local_ombiel_webservices::get_course_assignments($course1->id);
        $assignments = external_api::clean_returnvalue(local_ombiel_webservices::get_course_assignments_returns(), $assignments);
  
        $this->assertEquals(2, count($assignments)); 
        $this->assertArrayNotHasKey('description', $assignments[0]);    
        $this->assertEquals('Assignment 2', $assignments[1]['name']); 
        
        $cmcontext = context_module::instance($assignment2instance->cmid);
        $this->assertEquals('<img src="'.$CFG->wwwroot.
                '/webservice/pluginfile.php/'.$cmcontext->id
                .'/mod_assign/intro/_dummy.jpg" height="20" width="20" />', 
                $assignments[1]['description']); 
        $this->assertEquals($course1->id, $assignments[1]['courseid']);    
        $this->assertEquals(1642550401, $assignments[1]['deadline']);
        $this->assertEquals(1, $assignments[0]['status']);
        /**
         * Course has no assignments user is enrolled
         */
        $assignments = local_ombiel_webservices::get_course_assignments($course2->id);
        $assignments = external_api::clean_returnvalue(local_ombiel_webservices::get_course_assignments_returns(), $assignments);
         
        $this->assertSame(array(), $assignments);  
        /**
         * Course has no assignments user is not enrolled
         */
        $this->setExpectedException('moodle_exception');
        $assignments = local_ombiel_webservices::get_course_assignments($course3->id);
         
     }
     public function test_get_cm_assignment() { 
         global $CFG, $DB, $USER;
        /**
         * @todo test grade
         */
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();
        
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        
        $coursecontext = context_course::instance($course1->id);
        /**
         * Set up label for negative test
         */
        $labelgenerator = $this->getDataGenerator()->get_plugin_generator('mod_label');
        $options = array(
            'course'=>$course1->id,
        );
        $labelinstance = $labelgenerator->create_instance($options);
         /**
         * Set up assignment
         */       
        $assignmentgenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        
        $sitecontext = context_system::instance();
        $roleid = create_role('Calendar', 'Calendar', 'dummy role description');
        assign_capability('moodle/calendar:manageentries', CAP_ALLOW, $roleid, $sitecontext);
        role_assign($roleid, $USER->id, $sitecontext);
        
        $options = array(
            'course'=>$course1->id,
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
            'duedate'=>1642550401,
        );
        $assignmentinstance = $assignmentgenerator->create_instance($options);
        
        $submission = new stdClass;
        $submission->assignment = $assignmentinstance->id;
        $submission->userid = $user1->id;
        $submission->status = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        $DB->insert_record('assign_submission', $submission);
        /**
         * cm is an assignment - user is enrolled
         */
        $assignment = local_ombiel_webservices::get_cm_assignment($assignmentinstance->cmid);
        $assignment = external_api::clean_returnvalue(local_ombiel_webservices::get_cm_assignment_returns(), $assignment);
        
        $cmcontext = context_module::instance($assignmentinstance->cmid);
        $this->assertEquals('<img src="'.$CFG->wwwroot.
                '/webservice/pluginfile.php/'.$cmcontext->id
                .'/mod_assign/intro/_dummy.jpg" height="20" width="20" />', 
                $assignment['description']); 
        $this->assertEquals(1642550401, $assignment['deadline']);
        $this->assertEquals(1, $assignment['status']);
        /**
         * cm is a label - user is enrolled
         */
        $this->setExpectedException('moodle_exception');
        $assignment = local_ombiel_webservices::get_cm_assignment($labelinstance->cmid);
         
      
     }
     public function test_get_cm_assignment_not_enrolled() { 
        $this->resetAfterTest(true);
        
        $course = self::getDataGenerator()->create_course();
        
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);
        
        $coursecontext = context_course::instance($course->id);
         /**
         * Set up assignment
         */       
        $assignmentgenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        
        $options = array(
            'course'=>$course->id,
        );
        $assignmentinstance = $assignmentgenerator->create_instance($options);
        
        $this->setExpectedException('moodle_exception');
        $assignment = local_ombiel_webservices::get_cm_assignment($assignmentinstance->cmid);

        
     }
     public function test_get_user_grades() {
        global $DB, $USER;
        
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();
        
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        
        $this->setUser($user1);
        
        $assignmentgenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
         /**
         * First course
         */       
        
        $options = array(
            'course'=>$course1->id,
        );
        $assignmentinstance = $assignmentgenerator->create_instance($options);
                
        $course_item = $DB->get_record('grade_items', array('itemtype'=>'course', 'courseid'=>$course1->id));
        $course_item->grademax = 90;
        $course_item->grademin = 0;
        $DB->update_record('grade_items', $course_item);

        $grade_grade = new grade_grade();
        
        $grade_grade->itemid = $course_item->id;
        $grade_grade->userid = $user1->id;
        $grade_grade->finalgrade = 45;
        $grade_grade->feedback = 'Feedback 1';

        $grade_grade->insert();
        
         /**
         * Second course
         */       
        
        $options = array(
            'course'=>$course2->id,
        );
        $assignmentinstance = $assignmentgenerator->create_instance($options);
        
        $course_item = $DB->get_record('grade_items', array('itemtype'=>'course', 'courseid'=>$course2->id));

        $grade_grade = new grade_grade();
        
        $grade_grade->itemid = $course_item->id;
        $grade_grade->userid = $user1->id;
        $grade_grade->finalgrade = 55.01;
        $grade_grade->feedback = 'Feedback 2';

        $grade_grade->insert();
        
        /*
         * Test with logged in user
         */
        $grades = local_ombiel_webservices::get_user_grades();
        $grades = external_api::clean_returnvalue(local_ombiel_webservices::get_user_grades_returns(), $grades);
        
        $this->assertEquals(2, count($grades));
        $this->assertEquals("Test course 2", $grades[0]['fullname']);
        $this->assertEquals("55.01", $grades[0]['grade']);
        $this->assertEquals("0&ndash;100", $grades[0]['range']);
        $this->assertEquals("55.01 %", $grades[0]['percentage']);
        $this->assertEquals('<div class="text_to_html">Feedback 2</div>', $grades[0]['feedback']);
        
        $this->assertEquals("Test course 1", $grades[1]['fullname']);
        $this->assertEquals("45.00", $grades[1]['grade']);
        $this->assertEquals("0&ndash;90", $grades[1]['range']);
        $this->assertEquals("50.00 %", $grades[1]['percentage']);
        $this->assertEquals('<div class="text_to_html">Feedback 1</div>', $grades[1]['feedback']);
            
        /*
         * Test with other user
         */       
        
        $this->setUser($user2);
        $coursecontext = context_course::instance($course2->id);
        $roleid = create_role('Dummy', 'Dummy', 'dummy role description');
        assign_capability('moodle/grade:viewall', CAP_ALLOW, $roleid, $coursecontext);
        role_assign($roleid, $USER->id, $coursecontext);
    
        $grades = local_ombiel_webservices::get_user_grades($user1->id);
        $grades = external_api::clean_returnvalue(local_ombiel_webservices::get_user_grades_returns(), $grades);
        $this->assertEquals(1, count($grades));
         
     }

     public function test_get_course_grades() {
        global $DB, $USER;
        
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
                
        $this->setUser($user1);
        
        $assignmentgenerator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
         $options = array(
            'name'=>'Assignment 1',
            'course'=>$course1->id,
        );
        $assignmentinstance = $assignmentgenerator->create_instance($options);
                
        $grade_item = $DB->get_record('grade_items', array('itemmodule'=>'assign', 'iteminstance'=>$assignmentinstance->id));
        $grade_item->grademax = 90;
        $grade_item->grademin = 0;
        $DB->update_record('grade_items', $grade_item);

        $grade_grade = new grade_grade();
        
        $grade_grade->itemid = $grade_item->id;
        $grade_grade->userid = $user1->id;
        $grade_grade->rawgrade = 45.00;
        $grade_grade->finalgrade = 45.00;
        $grade_grade->feedback = 'Feedback 1';

        $grade_grade->insert();

         $options = array(
            'name'=>'Assignment 2',
            'course'=>$course1->id,
        );
        $assignmentinstance2 = $assignmentgenerator->create_instance($options);
                
        $grade_item = $DB->get_record('grade_items', array('itemmodule'=>'assign', 'iteminstance'=>$assignmentinstance2->id));

        $grade_grade = new grade_grade();
        
        $grade_grade->itemid = $grade_item->id;
        $grade_grade->userid = $user1->id;
        $grade_grade->rawgrade = 50.00;
        $grade_grade->finalgrade = 50.00;
        $grade_grade->feedback = 'Feedback 2';

        $grade_grade->insert();

        grade_regrade_final_grades($course1->id);
        
        /*
         * Test with logged in user
         */
        $grades = local_ombiel_webservices::get_course_grades($course1->id);
        $grades = external_api::clean_returnvalue(local_ombiel_webservices::get_course_grades_returns(), $grades);
        
        $this->assertEquals(3, count($grades));
        $this->assertEquals("Assignment 1", $grades[0]['gradeitem']);
        $this->assertEquals("45.00", $grades[0]['grade']);
        $this->assertEquals("0&ndash;90", $grades[0]['range']);
        $this->assertEquals("50.00 %", $grades[0]['percentage']);
        $this->assertEquals('<div class="text_to_html">Feedback 1</div>', $grades[0]['feedback']);
        
        $this->assertEquals("Assignment 2", $grades[1]['gradeitem']);
        $this->assertEquals("50.00", $grades[1]['grade']);
        $this->assertEquals("0&ndash;100", $grades[1]['range']);
        $this->assertEquals("50.00 %", $grades[1]['percentage']);
        $this->assertEquals('<div class="text_to_html">Feedback 2</div>', $grades[1]['feedback']);
        
        $this->assertEquals("Course total", $grades[2]['gradeitem']);
        $this->assertEquals("50.00", $grades[2]['grade']);
        $this->assertEquals("0&ndash;100", $grades[2]['range']);
        $this->assertEquals("50.00 %", $grades[2]['percentage']);
        
        /*
         * Test with other user access allowed
         */       
        
        $this->setUser($user2);
        $coursecontext = context_course::instance($course1->id);
        $roleid = create_role('Dummy', 'Dummy', 'dummy role description');
        assign_capability('moodle/grade:viewall', CAP_ALLOW, $roleid, $coursecontext);
        role_assign($roleid, $USER->id, $coursecontext);
    
        $grades = local_ombiel_webservices::get_course_grades($course1->id, $user1->id);
        $grades = external_api::clean_returnvalue(local_ombiel_webservices::get_course_grades_returns(), $grades);

        $this->assertEquals(1, count($grades));
        
        $this->assertEquals("Course total", $grades[0]['gradeitem']);
        $this->assertEquals("50.00", $grades[0]['grade']);
        $this->assertEquals("0&ndash;100", $grades[0]['range']);
        $this->assertEquals("50.00 %", $grades[0]['percentage']);
        
        /*
         * Test with other user no access 
         */       
        
        $this->setUser($user3);
    
        $this->setExpectedException('moodle_exception');
        $grades = local_ombiel_webservices::get_course_grades($course1->id, $user1->id);
         
     }
     public function test_get_course_grades_not_enrolled() {
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        
        $user1 = self::getDataGenerator()->create_user();
        $this->setUser($user1);
    
        $this->setExpectedException('moodle_exception');
        $grades = local_ombiel_webservices::get_course_grades($course1->id, $user1->id);
         
     }
     public function test_get_course_forums() {
        global $CFG, $DB, $USER;

        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();
        $course3 = self::getDataGenerator()->create_course();
        $user1 = self::getDataGenerator()->create_user();
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        
        $coursecontext = context_course::instance($course1->id);

         /**
         * Set up forums
         */       
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        
        $options = array(
            'course'=>$course1->id,
            'name'=>'Forum 1',
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
        );
        $forum1instance = $forumgenerator->create_instance($options);
       
        $options = array(
            'course'=>$course1->id,
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
        );
        $forum2instance = $forumgenerator->create_instance($options);        
        
        $cm2 = $DB->get_record('course_modules', array('id'=>$forum2instance->cmid));
        $cm2->showdescription = 1;
        $DB->update_record('course_modules', $cm2); 
     
        /**
         * Course has forums - user is enrolled
         */
        $forums = local_ombiel_webservices::get_course_forums($course1->id);
        $forums = external_api::clean_returnvalue(local_ombiel_webservices::get_course_forums_returns(), $forums);

        $this->assertEquals(2, count($forums));    
        
        $this->assertEquals('Forum 2', $forums[1]['name']); 
        
        $cm2context = context_module::instance($forum2instance->cmid);
        $this->assertEquals('<img src="'.$CFG->wwwroot.
                '/webservice/pluginfile.php/'.$cm2context->id
                .'/mod_forum/intro/_dummy.jpg" height="20" width="20" />', 
                $forums[1]['description']); 
        $this->assertEquals($forum2instance->cmid, $forums[1]['id']);   
        
        /**
         * Course has no forums user is enrolled
         */
        $forums = local_ombiel_webservices::get_course_forums($course2->id);
        $forums = external_api::clean_returnvalue(local_ombiel_webservices::get_course_forums_returns(), $forums);
         
        $this->assertSame(array(), $forums);  
        /**
         * Course has no forums user is not enrolled
         */
        $forums = local_ombiel_webservices::get_course_forums($course3->id);
        $forums = external_api::clean_returnvalue(local_ombiel_webservices::get_course_forums_returns(), $forums);
         
        $this->assertSame(array(), $forums);  
        
     }
     public function test_get_cm_forum() { 
         global $CFG, $DB, $USER;
        /**
         * @todo test grade
         */
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        
        $user1 = self::getDataGenerator()->create_user();
        
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        
        /**
         * Set up label for negative test
         */
        $labelgenerator = $this->getDataGenerator()->get_plugin_generator('mod_label');
        $options = array(
            'course'=>$course1->id,
        );
        $labelinstance = $labelgenerator->create_instance($options);
         /**
         * Set up forum
         */       
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
         
        $options = array(
            'course'=>$course1->id,
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
            'type'=>'single'
        );
        $forum1instance = $forumgenerator->create_instance($options);

        $cm1 = $DB->get_record('course_modules', array('id'=>$forum1instance->cmid));
        $DB->update_record('course_modules', $cm1); 
        
        /**
         * cm is a forum - user is enrolled - can't post
         */
        $forum = local_ombiel_webservices::get_cm_forum($forum1instance->cmid);
        $forum = external_api::clean_returnvalue(local_ombiel_webservices::get_cm_forum_returns(), $forum);
        
        $this->assertEquals('Forum 1', $forum['name']); 
        $this->assertEquals($forum1instance->id, $forum['id']); 
        $cmcontext = context_module::instance($forum1instance->cmid);
        $this->assertEquals('<img src="'.$CFG->wwwroot.
                '/webservice/pluginfile.php/'.$cmcontext->id
                .'/mod_forum/intro/_dummy.jpg" height="20" width="20" />', 
                $forum['description']); 
        $this->assertEquals(false, $forum['canpost']);
        
        /**
         * cm is a forum - user is enrolled - can post
         */
        $options = array(
            'course'=>$course1->id,
            'intro'=>'dummy',
        );
        $forum2instance = $forumgenerator->create_instance($options);
        
        $forum = local_ombiel_webservices::get_cm_forum($forum2instance->cmid);
        $forum = external_api::clean_returnvalue(local_ombiel_webservices::get_cm_forum_returns(), $forum);
          
        $this->assertEquals('Forum 2', $forum['name']); 
        
        $this->assertEquals(true, $forum['canpost']);
        /**
         * cm is a label - user is enrolled
         */
        $this->setExpectedException('moodle_exception');
        $forum = local_ombiel_webservices::get_cm_forum($labelinstance->cmid);
         
      
     }
     public function test_get_cm_forum_not_enrolled() { 
        $this->resetAfterTest(true);
        
        $course = self::getDataGenerator()->create_course();
        
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);
        
        $coursecontext = context_course::instance($course->id);
         /**
         * Set up forum
         */       
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        
        $options = array(
            'course'=>$course->id,
        );
        $foruminstance = $forumgenerator->create_instance($options);
        
        $this->setExpectedException('moodle_exception');
        $forum = local_ombiel_webservices::get_cm_forum($foruminstance->cmid);

        
     } 
     public function test_get_forum_discussions() { 
         global $CFG, $DB, $USER;
        /**
         * @todo test grade
         */
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        
        $user1 = self::getDataGenerator()->create_user(array('firstname'=>'First', 'lastname'=>'Last'));
        
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        
        $coursecontext = context_course::instance($course1->id);
        /**
         * Set up label for negative test
         */
        $labelgenerator = $this->getDataGenerator()->get_plugin_generator('mod_label');
        $options = array(
            'course'=>$course1->id,
        );
        $labelinstance = $labelgenerator->create_instance($options);
         /**
         * Set up forum
         */       
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
         
        $options = array(
            'course'=>$course1->id,
        );
        $forum1instance = $forumgenerator->create_instance($options);
        /**
         * cm is a forum - user is enrolled no discussions
         */
        $discussions = local_ombiel_webservices::get_forum_discussions($forum1instance->cmid);
        $discussions = external_api::clean_returnvalue(local_ombiel_webservices::get_forum_discussions_returns(), $discussions);
        
        $this->assertSame(array(), $discussions);  
                
        /**
         * Discussion with no replies
         */
        $discussion1 = new stdClass();
        $discussion1->forum = $forum1instance->id;
        $discussion1->name = 'subject';
        $discussion1->message = '<div>message</div>';
        $discussion1->messagetrust = false;
        $discussion1->messageformat = 1;
        $discussion1->mailnow = false;
        $discussion1->course = $course1->id;

        $discussion1id = forum_add_discussion($discussion1);
        /**
         * Discussion with two replies
         */
        $discussion2 = new stdClass();
        $discussion2->forum = $forum1instance->id;
        $discussion2->name = 'subject2';
        $discussion2->message = '<div>message2</div>';
        $discussion2->messagetrust = false;
        $discussion2->messageformat = 1;
        $discussion2->mailnow = false;
        $discussion2->course = $course1->id;

        $discussion2id = forum_add_discussion($discussion2);
        
        $post1 = new stdClass();
        $post1->discussion = $discussion2id;
        $post1->subject = 'Reply 1';
        $post1->message = 'Reply message 1';
        $post1->messageformat = 1;
        $post1->parent = $discussion2id;
        $post1->itemid = 0;
        $post1->course = $course1->id;
        
        forum_add_new_post($post1, null,$message);
        
        $post2 = new stdClass();
        $post2->discussion = $discussion2id;
        $post2->subject = 'Reply 2';
        $post2->message = 'Reply message 2';
        $post2->messageformat = 2;
        $post2->parent = $discussion2id;
        $post2->itemid = 0;
        $post2->course = $course1->id;

        $post2id = forum_add_new_post($post2, null,$message);
        
        $lastreply = $DB->get_field('forum_posts', 'modified', array('id'=>$post2id));
        
        /**
         * cm is a forum - user is enrolled 2 discussions
         */
        $discussions = local_ombiel_webservices::get_forum_discussions($forum1instance->cmid);
        $discussions = external_api::clean_returnvalue(local_ombiel_webservices::get_forum_discussions_returns(), $discussions);
                
        $assertDiscussion1 = array('id'=>1, 'name'=>"subject", "discussion"=>1, "author"=>"First Last", "content"=>"<div>message</div>", "replies"=>0, "lastreply"=>0);
        $assertDiscussion2 = array("id"=>2, "name"=>"subject2", "discussion"=>2, "author"=>"First Last", "content"=>"<div>message2</div>", "replies"=>2, "lastreply"=>$lastreply);
  
        $this->assertEquals(2, count($discussions)); 
        $this->assertContains($assertDiscussion1, $discussions);
        $this->assertContains($assertDiscussion2, $discussions); 

        /**
         * cm is a label - user is enrolled
         */
        $this->setExpectedException('moodle_exception');
        $discussions = local_ombiel_webservices::get_forum_discussions($labelinstance->cmid);
         
      
     }
     public function test_get_forum_discussions_not_enrolled() { 
        $this->resetAfterTest(true);
        
        $course = self::getDataGenerator()->create_course();
        
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);
        
        $coursecontext = context_course::instance($course->id);
         /**
         * Set up forum
         */       
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        
        $options = array(
            'course'=>$course->id,
        );
        $foruminstance = $forumgenerator->create_instance($options);
        
        $this->setExpectedException('moodle_exception');
        $forum = local_ombiel_webservices::get_forum_discussions($foruminstance->cmid);

        
     } 
     public function test_get_discussion_posts() { 
         global $CFG, $DB, $USER;
        /**
         * @todo test grade
         */
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        
        $user1 = self::getDataGenerator()->create_user(array('firstname'=>'First', 'lastname'=>'Last'));
        $user2 = self::getDataGenerator()->create_user();
        
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);

         /**
         * Set up forum
         */       
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
         
        $options = array(
            'course'=>$course1->id,
        );
        $forum1instance = $forumgenerator->create_instance($options);

        /**
         * Create discussion 
         */
        $discussion = new stdClass();
        $discussion->forum = $forum1instance->id;
        $discussion->name = 'subject2';
        $discussion->message = '<div>message2</div>';
        $discussion->messagetrust = false;
        $discussion->messageformat = 1;
        $discussion->mailnow = false;
        $discussion->course = $course1->id;

        $discussionid = forum_add_discussion($discussion);
        /**
         * No posts
         */
        $posts = local_ombiel_webservices::get_discussion_posts($discussionid);
        $posts = external_api::clean_returnvalue(local_ombiel_webservices::get_discussion_posts_returns(), $posts);
        
        $this->assertSame(array(), $posts);  
        
        /**
         * Add posts
         */
        $post1 = new stdClass();
        $post1->discussion = $discussionid;
        $post1->subject = 'Reply 1';
        $post1->message = 'Reply message 1';
        $post1->messageformat = 1;
        $post1->parent = $discussionid;
        $post1->itemid = 0;
        $post1->course = $course1->id;
        
        $post1id = forum_add_new_post($post1, null,$message);
        $post1time = $DB->get_field('forum_posts', 'modified', array('id'=>$post1id));
        
        $post2 = new stdClass();
        $post2->discussion = $discussionid;
        $post2->subject = 'Reply 2';
        $post2->message = 'Reply message 2';
        $post2->messageformat = 2;
        $post2->parent = $discussionid;
        $post2->itemid = 0;
        $post2->course = $course1->id;

        $post2id = forum_add_new_post($post2, null,$message);        
        $post2time = $DB->get_field('forum_posts', 'modified', array('id'=>$post2id));
     
        $posts = local_ombiel_webservices::get_discussion_posts($discussionid);
        $posts = external_api::clean_returnvalue(local_ombiel_webservices::get_discussion_posts_returns(), $posts);
  
	$assertPost1 = array("id"=>2, "parent"=>1, "subject"=>"Reply 1", "author"=>"First Last", "content"=>"Reply message 1", "date"=>$post1time);
	$assertPost2 = array("id"=>3, "parent"=>1, "subject"=>"Reply 2", "author"=>"First Last", "content"=>"Reply message 2", "date"=>$post2time);
	
        $this->assertEquals(2, count($posts));         
        $this->assertContains($assertPost1, $posts);
        $this->assertContains($assertPost2, $posts);
        
        /**
         * this user does not have access
         */
        $this->setUser($user2);
        $this->setExpectedException('moodle_exception');
        $posts = local_ombiel_webservices::get_discussion_posts($discussionid);
         
      
     } 
     public function test_add_forum_discussion() { 
         global $CFG, $DB, $USER;
        /**
         * @todo test grade
         */
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        
        $user1 = self::getDataGenerator()->create_user(array('firstname'=>'First', 'lastname'=>'Last'));
        
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);

         /**
         * Set up forum
         */       
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
         
        $options = array(
            'course'=>$course1->id,
            'type'=>'single'
        );
        $forum1instance = $forumgenerator->create_instance($options);

        /**
         * Can't add
         */
        $result = local_ombiel_webservices::add_forum_discussion($forum1instance->id, 'subject', 'message');
        $result = external_api::clean_returnvalue(local_ombiel_webservices::add_forum_discussion_returns(), $result);

        $this->assertEquals(0, $result['result']);
        
        $count = $DB->count_records('forum_discussions', array('forum'=>$forum1instance->id));
        $this->assertEquals(1, $count);
        
        $options = array(
            'course'=>$course1->id,
        );
        $forum2instance = $forumgenerator->create_instance($options);

        /**
         * Can add
         */
        $result = local_ombiel_webservices::add_forum_discussion($forum2instance->id, 'subject', 'message');
        $result = external_api::clean_returnvalue(local_ombiel_webservices::add_forum_discussion_returns(), $result);
        
        
        $discussions = $DB->get_records('forum_discussions', array('forum'=>$forum2instance->id));
        
        $latestdiscussion = end($discussions);
        
        $this->assertEquals($latestdiscussion->id, $result['result']);
        $this->assertEquals('subject',$latestdiscussion->name);
        
        $firstpost = $DB->get_record('forum_posts', array('id'=>$latestdiscussion->firstpost));

        $this->assertEquals('message',$firstpost->message);        
         
      
     } 
     public function test_add_discussion_post() { 
        global $CFG, $DB, $USER;
        
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        
        $user1 = self::getDataGenerator()->create_user(array('firstname'=>'First', 'lastname'=>'Last'));
        $user2 = self::getDataGenerator()->create_user();
        
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);

         /**
         * Set up forum
         */       
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
         
        $options = array(
            'course'=>$course1->id,
        );
        $forum1instance = $forumgenerator->create_instance($options);

        /**
         * Create discussion 
         */
        $discussion = new stdClass();
        $discussion->forum = $forum1instance->id;
        $discussion->name = 'discussion subject';
        $discussion->message = 'discussion message';
        $discussion->messagetrust = false;
        $discussion->messageformat = 1;
        $discussion->mailnow = false;
        $discussion->course = $course1->id;

        $discussionid = forum_add_discussion($discussion);
        /**
         * Post with out a parent
         */
        $results = local_ombiel_webservices::add_discussion_post($discussionid, 'subject1', 'message1');
        $results = external_api::clean_returnvalue(local_ombiel_webservices::add_discussion_post_returns(), $results);
                 
        $posts = $DB->get_records('forum_posts', array('discussion'=>$discussionid), 'id ASC');
        
        $post1 = end($posts);
        
        $this->assertEquals($results['result'], $post1->id);
        $this->assertEquals("subject1", $post1->subject);
        $this->assertEquals("message1", $post1->message);
        
        /**
         * Post with a parent (reply)
         */
        $results = local_ombiel_webservices::add_discussion_post($discussionid, 'subject2', 'message2', $results['result']);
        $results = external_api::clean_returnvalue(local_ombiel_webservices::add_discussion_post_returns(), $results);
       
        $post2 = $DB->get_record('forum_posts', array('discussion'=>$discussionid, 'parent'=>$post1->id));
        
        $this->assertEquals($results['result'], $post2->id);
        $this->assertEquals("subject2", $post2->subject);
        $this->assertEquals("message2", $post2->message);
        

        /**
         * this user does not have access
         */
        $this->setUser($user2);
        
        $countb4 = $DB->count_records('forum_posts', array('discussion'=>$discussionid));
        $results = local_ombiel_webservices::add_discussion_post($discussionid, 'subject3', 'message3');
         
        $this->assertEquals(0, $results['result']);
        
        $count = $DB->count_records('forum_posts', array('discussion'=>$discussionid));
        $this->assertEquals($countb4, $count);
      
     } 
     
     public function test_get_user_forums() {
        global $CFG, $DB, $USER;
        $CFG->forum_trackreadposts = true;
        
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();
        $user1 = self::getDataGenerator()->create_user(array('trackforums'=>true));
        $user2 = self::getDataGenerator()->create_user();
        $user3 = self::getDataGenerator()->create_user();
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        
        $coursecontext = context_course::instance($course1->id);
        
        // stop cmid being the same as instance id (better test)
        $labelgenerator = $this->getDataGenerator()->get_plugin_generator('mod_label');
        $options = array(
            'course'=>$course1->id,
        );
        $labelgenerator->create_instance($options);
        /**
         * Create forums
         */
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        
        $options = array(
            'course'=>$course1->id,
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
        );
        $forum1instance = $forumgenerator->create_instance($options);
               
        $options = array(
            'course'=>$course2->id,
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
            'duedate'=>1642550401,
            'trackingtype'=>2
        );
        $forum2instance = $forumgenerator->create_instance($options);
        
        /**
         * Create discussion 
         */
        $discussion = new stdClass();
        $discussion->forum = $forum2instance->id;
        $discussion->name = 'subject2';
        $discussion->message = 'message2';
        $discussion->messagetrust = false;
        $discussion->messageformat = 1;
        $discussion->mailnow = false;
        $discussion->course = $course1->id;

        $discussionid = forum_add_discussion($discussion);
        
        /**
         * Add post
         */
        $post1 = new stdClass();
        $post1->userid = $user2->id;
        $post1->forum = $forum2instance->id;
        $post1->discussion = $discussionid;
        $post1->subject = 'Reply 1';
        $post1->message = 'Reply message 1';
        $post1->messageformat = 1;
        $post1->parent = $discussionid;
        $post1->itemid = 0;
        $post1->course = $course1->id;
        
        $this->setUser($user2); # post as user 2 so that the post is unread for user1
        $post1id = forum_add_new_post($post1, null,$message);
        $post1time = $DB->get_field('forum_posts', 'modified', array('id'=>$post1id));
        
        $cm2 = $DB->get_record('course_modules', array('id'=>$forum2instance->cmid));
        $cm2->showdescription = 1;
        $DB->update_record('course_modules', $cm2); 
        
        $this->setUser($user1);        
        /**
         * Default user id - user has forums
         */
        $forums = local_ombiel_webservices::get_user_forums();
        $forums = external_api::clean_returnvalue(local_ombiel_webservices::get_user_forums_returns(), $forums);
  
        $this->assertEquals(2, count($forums));        
        $this->assertEquals('Forum 2', $forums[0]['name']); 
        $cmcontext = context_module::instance($forum2instance->cmid);
        $this->assertEquals('<img src="'.$CFG->wwwroot.
                '/webservice/pluginfile.php/'.$cmcontext->id.
                '/mod_forum/intro/_dummy.jpg" height="20" width="20" />', 
                $forums[0]['description']); 
        $this->assertEquals($course2->id, $forums[0]['courseid']); 
        $this->assertEquals(1, $forums[0]['unreadposts']);
        /**
         * Call with logged in user id - user has forums
         */
        $forums = local_ombiel_webservices::get_user_forums($user1->id);
        $forums = external_api::clean_returnvalue(local_ombiel_webservices::get_user_forums_returns(), $forums);
         
        $this->assertEquals(2, count($forums));
        /**
         * Call with another persons user id with authority- they have no forums
         */
        $usercontext = context_user::instance($user2->id, MUST_EXIST);
        
        $roleid = create_role('Dummy role', 'dummyrole', 'dummy role description');
        assign_capability('moodle/user:viewdetails', CAP_ALLOW, $roleid, $usercontext);
        role_assign($roleid, $USER->id, $usercontext);
        accesslib_clear_all_caches_for_unit_testing();
        $forums = local_ombiel_webservices::get_user_forums($user2->id);
        $forums = external_api::clean_returnvalue(local_ombiel_webservices::get_user_forums_returns(), $forums);

        $this->assertSame(array(), $forums);  
        /**
         * Call with another persons user id without authority
         */
        $this->setExpectedException('moodle_exception');
        $courses = local_ombiel_webservices::get_user_forums($user3->id);
         
     }
     public function test_get_coursenews() {
        global $CFG, $DB, $USER;
        $CFG->forum_trackreadposts = true;
        
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        $user1 = self::getDataGenerator()->create_user();
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);

        /**
         * default course to site
         */
        $results = local_ombiel_webservices::get_coursenews();
        $results = external_api::clean_returnvalue(local_ombiel_webservices::get_coursenews_returns(), $results);
  
        $forum = $DB->get_record('forum', array('course'=>SITEID, 'type'=>'news'));
        $cm = get_coursemodule_from_instance('forum', $forum->id);
        $this->assertEquals($forum->id, $results['coursemoduleid']);        

        /**
         * Call with logged in user id - user has forums
         */
        $results = local_ombiel_webservices::get_coursenews($course1->id);
        $results = external_api::clean_returnvalue(local_ombiel_webservices::get_coursenews_returns(), $results);
         
        $forum = $DB->get_record('forum', array('course'=>$course1->id, 'type'=>'news'));
        $cm = get_coursemodule_from_instance('forum', $forum->id);
        $this->assertEquals($forum->id, $results['coursemoduleid']);   
         
     }
     
     public function test_get_cm_choice() { 
         global $CFG, $DB, $USER;
         
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        
        $user1 = self::getDataGenerator()->create_user();
        
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        
        /**
         * Set up label for negative test
         */
        $labelgenerator = $this->getDataGenerator()->get_plugin_generator('mod_label');
        $options = array(
            'course'=>$course1->id,
        );
        $labelinstance = $labelgenerator->create_instance($options);
         /**
         * Set up choice
         */       
         
        require_once($CFG->dirroot . '/mod/choice/lib.php');
        $choice = new stdClass;
        $choice->course =$course1->id;
        $choice->name = 'Choice 1';
        $choice->timerestrict = true;
        $choice->timeopen = 1642550401;
        $choice->intro = '<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />';
        $choice->option = array('option 1', 'option 2');
        $choice->coursemodule = $this->precreate_course_module($choice->course, 'choice');
        
        $choice1instanceid = choice_add_instance($choice);

        $cm1 = $DB->get_record('course_modules', array('id'=>$choice->coursemodule));
        $cm1->instance = $choice1instanceid;
        $cm1->showdescription = 1;
        $DB->update_record('course_modules', $cm1); 
        
        /**
         * cm is a choice - user is enrolled
         */
        $choice = local_ombiel_webservices::get_cm_choice($choice->coursemodule);
        
        $choice = external_api::clean_returnvalue(local_ombiel_webservices::get_cm_choice_returns(), $choice);
        
        $this->assertEquals('Choice 1', $choice['name']); 
        $this->assertEquals($choice1instanceid, $choice['id']); 
        $cmcontext = context_module::instance($cm1->id);
        $this->assertEquals('<img src="'.$CFG->wwwroot.
                '/webservice/pluginfile.php/'.$cmcontext->id
                .'/mod_choice/intro/_dummy.jpg" height="20" width="20" />', 
                $choice['description']); 
        $this->assertEquals(1642550401, $choice['timeavailable']);

        /**
         * cm is a label - user is enrolled
         */
        $this->setExpectedException('moodle_exception');
        $choice = local_ombiel_webservices::get_cm_choice($labelinstance->cmid);
         
      
     }
     public function test_get_cm_choice_not_enrolled() { 
         global $CFG;
        $this->resetAfterTest(true);
        
        $course = self::getDataGenerator()->create_course();
        
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);
        
        $coursecontext = context_course::instance($course->id);
         /**
         * Set up choice
         */       
        require_once($CFG->dirroot . '/mod/choice/lib.php');
        $choice = new stdClass;
        $choice->course =$course->id;
        $choice->name = 'Choice 1';
        $choice->intro = '<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />';
        $choice->option = array('option 1', 'option 2');
        $choice->coursemodule = $this->precreate_course_module($choice->course, 'choice');
        
        $choiceinstance = choice_add_instance($choice);
        
        $this->setExpectedException('moodle_exception');
        $choice = local_ombiel_webservices::get_cm_choice($choice->coursemodule);

        
     } 
     public function test_get_choice_options() { 
         global $CFG, $DB, $USER;
         
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        
         /**
         * Set up choice
         */       
         
        require_once($CFG->dirroot . '/mod/choice/lib.php');
        $choice = new stdClass;
        $choice->course =$course1->id;
        $choice->name = 'Choice 1';
        $choice->timerestrict = true;
        $choice->timeopen = 1642550401;
        $choice->limit = array(1=>5);
        $choice->intro = '<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />';
        $choice->option = array('option 1', 'option 2');
        $choice->coursemodule = $this->precreate_course_module($choice->course, 'choice');
        
        $choice1instanceid = choice_add_instance($choice);

        $cm1 = $DB->get_record('course_modules', array('id'=>$choice->coursemodule));
        $cm1->instance = $choice1instanceid;
        $cm1->showdescription = 1;
        $DB->update_record('course_modules', $cm1); 
        $answer = new stdClass;
        $answer->choiceid = $choice1instanceid;
        $answer->userid = $user2->id;
        $answer->optionid = 2;
        $DB->insert_record('choice_answers', $answer);
        
        /**
         * cm is a choice - user is enrolled
         */
        $options = local_ombiel_webservices::get_choice_options($choice1instanceid);        
        $options = external_api::clean_returnvalue(local_ombiel_webservices::get_choice_options_returns(), $options);
        
        $this->assertEquals(2, count($options)); 
        $this->assertEquals('option 1', $options[0]['option']); 
        $this->assertEquals('option 2', $options[1]['option']); 
        $this->assertEquals(5, $options[1]['maxanswers']); 
        $this->assertEquals(1, $options[1]['count']); 
        
        /**
         * cm is a choice - user is not enrolled
         */
        $this->setUser($user2);
        $this->setExpectedException('moodle_exception');
        $options = local_ombiel_webservices::get_cm_choice($choice1instanceid);
         
      
     }
      public function test_user_choice_response() { 
         global $CFG, $DB, $USER;
         
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        
         /**
         * Set up choice
         */       
         
        require_once($CFG->dirroot . '/mod/choice/lib.php');
        $choice = new stdClass;
        $choice->course =$course1->id;
        $choice->name = 'Choice 1';
        $choice->timerestrict = true;
        $choice->timeopen = 1642550401;
        $choice->limit = array(1=>5);
        $choice->intro = '<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />';
        $choice->option = array('option 1', 'option 2');
        $choice->coursemodule = $this->precreate_course_module($choice->course, 'choice');
        
        $choice1instanceid = choice_add_instance($choice);

        $cm1 = $DB->get_record('course_modules', array('id'=>$choice->coursemodule));
        $cm1->instance = $choice1instanceid;
        $cm1->showdescription = 1;
        $DB->update_record('course_modules', $cm1); 
        
        $results = local_ombiel_webservices::user_choice_response(1, $choice1instanceid);        
        $results = external_api::clean_returnvalue(local_ombiel_webservices::user_choice_response_returns(), $results);
        
        $answers = $DB->get_records('choice_answers', array('choiceid'=>$choice1instanceid));
                
        $this->assertEquals(1, count($answers)); 
        $answer = current($answers);
        $this->assertEquals($user1->id, $answer->userid); 
        $this->assertEquals(1, $answer->optionid); 
        
        /**
         * cm is a choice - user is not enrolled
         */
        $this->setUser($user2);
        $this->setExpectedException('moodle_exception');
        $results = local_ombiel_webservices::get_cm_choice($choice1instanceid);
         
      
     }
     public function test_get_cm_page() {
         global $CFG, $DB, $USER;
         
        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        
        $user1 = self::getDataGenerator()->create_user();
        
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        
        /**
         * Set up label for negative test
         */
        $labelgenerator = $this->getDataGenerator()->get_plugin_generator('mod_label');
        $options = array(
            'course'=>$course1->id,
        );
        $labelinstance = $labelgenerator->create_instance($options);
         /**
         * Set up page
         */       
         
        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $pageoptions = array(
            'course'=>$course1->id,
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
            'content'=>'<img src="@@PLUGINFILE@@/_dummy1.jpg" height="40" width="40" /><p>paragraph</p>',
            'contentformat'=>1,
        );
        $pageinstance1 = $pagegenerator->create_instance($pageoptions);
        
        /**
         * cm is a page - user is enrolled - don't show description
         */
        $page = local_ombiel_webservices::get_cm_page($pageinstance1->cmid);
        
        $page = external_api::clean_returnvalue(local_ombiel_webservices::get_cm_page_returns(), $page);
        
        $this->assertEquals('Page 1', $page['name']); 
        $this->assertEquals($pageinstance1->id, $page['id']); 
        $cmcontext = context_module::instance($pageinstance1->cmid);
        $this->assertArrayNotHasKey('description', $page); 
        $this->assertEquals('<img src="'.$CFG->wwwroot.
                '/webservice/pluginfile.php/'.$cmcontext->id
                .'/mod_page/content/0/_dummy1.jpg" height="40" width="40" alt="_dummy1.jpg" /><p>paragraph</p>', 
                $page['content']); 

        $pageoptions['printintro'] = 1;
        $pageinstance2 = $pagegenerator->create_instance($pageoptions);
        /**
         * cm is a page - user is enrolled  show description
         */
        $page = local_ombiel_webservices::get_cm_page($pageinstance2->cmid);
        
        $page = external_api::clean_returnvalue(local_ombiel_webservices::get_cm_page_returns(), $page);
        
        $this->assertEquals('Page 2', $page['name']); 
        $this->assertEquals($pageinstance2->id, $page['id']); 
        $cmcontext = context_module::instance($pageinstance2->cmid);
        $this->assertEquals('<img src="'.$CFG->wwwroot.
                '/webservice/pluginfile.php/'.$cmcontext->id
                .'/mod_page/intro/_dummy.jpg" height="20" width="20" />', 
                $page['description']); 
        $this->assertEquals('<img src="'.$CFG->wwwroot.
                '/webservice/pluginfile.php/'.$cmcontext->id
                .'/mod_page/content/0/_dummy1.jpg" height="40" width="40" alt="_dummy1.jpg" /><p>paragraph</p>', 
                $page['content']); 
        /**
         * cm is a label - user is enrolled
         */
        $this->setExpectedException('moodle_exception');
        $page = local_ombiel_webservices::get_cm_page($labelinstance->cmid);
         
     }
     public function test_get_cm_page_not_enrolled() {
         global $CFG;
        $this->resetAfterTest(true);
        
        $course = self::getDataGenerator()->create_course();
        
        $user = self::getDataGenerator()->create_user();
        $this->setUser($user);
        
        $coursecontext = context_course::instance($course->id);
         
        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $pageoptions = array(
            'course'=>$course->id,
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
            'content'=>'<img src="@@PLUGINFILE@@/_dummy1.jpg" height="40" width="40" /><p>paragraph<p>',
        );
        $pageinstance = $pagegenerator->create_instance($pageoptions);
                
        $this->setExpectedException('moodle_exception');
        $page = local_ombiel_webservices::get_cm_page($pageinstance->cmid);
     
     }
     public function test_get_course_quizzes() {
        global $CFG, $DB, $USER;

        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();
        $course3 = self::getDataGenerator()->create_course();
        $user1 = self::getDataGenerator()->create_user();
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        
        $coursecontext = context_course::instance($course1->id);

        $_GET['wstoken'] = md5('test');
        // stop cmid being the same as instance id (better test)
        $labelgenerator = $this->getDataGenerator()->get_plugin_generator('mod_label');
        $options = array(
            'course'=>$course1->id,
        );
        $labelgenerator->create_instance($options);
         /**
         * Set up quizzes
         */       
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        
        $options = array(
            'name'=>'Quiz 1',
            'course'=>$course1->id,
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
        );
        
        $quiz1instance = $quizgenerator->create_instance($options);

        $options = array(
            'name'=>'Quiz 2',
            'course'=>$course1->id,
            'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
            'duedate'=>1642550401,
        );
        $quiz2instance = $quizgenerator->create_instance($options);
        
        $cm2 = $DB->get_record('course_modules', array('id'=>$quiz2instance->cmid));
        $cm2->showdescription = 1;
        $DB->update_record('course_modules', $cm2); 
        /**
         * Course has quizzes - user is enrolled
         */
        $response = local_ombiel_webservices::get_course_quizzes($course1->id);
        $response = external_api::clean_returnvalue(local_ombiel_webservices::get_course_quizzes_returns(), $response);
  
        $this->assertEquals(2, count($response)); 
        $this->assertArrayNotHasKey('description', $response['quizzes'][0]);  
        $this->assertEquals($quiz2instance->id, $response['quizzes'][1]['id']);    
        $this->assertEquals($quiz2instance->cmid, $response['quizzes'][1]['coursemoduleid']);    
        $this->assertEquals('Quiz 2', $response['quizzes'][1]['name']); 
        $this->assertEquals($CFG->wwwroot.'/local/ombiel_webservices/login.php?wstoken='.md5('test').'&userid='.$user1->id.'&cmid=',$response['baselink']);
        
        $cmcontext = context_module::instance($quiz2instance->cmid);
        $this->assertEquals('<img src="'.$CFG->wwwroot.
                '/webservice/pluginfile.php/'.$cmcontext->id
                .'/mod_quiz/intro/_dummy.jpg" height="20" width="20" />', 
                $response['quizzes'][1]['description']); 
        $this->assertEquals($course1->id, $response['quizzes'][1]['courseid']);    
        /**
         * Course has no quizzes user is enrolled
         */
        $response = local_ombiel_webservices::get_course_quizzes($course2->id);
        $response = external_api::clean_returnvalue(local_ombiel_webservices::get_course_quizzes_returns(), $response);
         
        $this->assertSame(array('baselink' => 'http://www.example.com/moodle/local/ombiel_webservices/login.php?wstoken=098f6bcd4621d373cade4e832627b4f6&userid=3&cmid=','quizzes'=>array()), $response);  
        /**
         * Course has no quizzes user is not enrolled
         */
        $this->setExpectedException('moodle_exception');
        $response = local_ombiel_webservices::get_course_quizzes($course3->id);
         
     }
     
     public function test_get_course_resources() {
        global $CFG, $DB, $USER;

        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();
        $course3 = self::getDataGenerator()->create_course();
        $user1 = self::getDataGenerator()->create_user();
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course2->id);
        
        
        if ($CFG->version >= 2013051400) { // resource generator added in 2.5 
            /**
            * Set up resources
            */       
           $resourcegenerator = $this->getDataGenerator()->get_plugin_generator('mod_resource');

           $options = array(
               'course'=>$course1->id,
               'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
           );
           $resource1instance = $resourcegenerator->create_instance($options);

           $options = array(
               'course'=>$course1->id,
               'intro'=>'<img src="@@PLUGINFILE@@/_dummy.jpg" height="20" width="20" />',
           );
           $resource2instance = $resourcegenerator->create_instance($options);        

           $cm2 = $DB->get_record('course_modules', array('id'=>$resource2instance->cmid));
           $cm2->showdescription = 1;
           $cm2->availablefrom = 1642550401;
           $DB->update_record('course_modules', $cm2); 

           /**
            * Course has resources - user is enrolled
            */
           $resources = local_ombiel_webservices::get_course_resources($course1->id);
           $resources = external_api::clean_returnvalue(local_ombiel_webservices::get_course_resources_returns(), $resources);

           $this->assertEquals(2, count($resources));    

           $this->assertEquals($resource1instance->cmid, $resources[0]['id']);           
           $cm1context = context_module::instance($resource1instance->cmid);
           $this->assertEquals('File 1', $resources[0]['name']);
           $this->assertEquals($CFG->wwwroot.'/webservice/pluginfile.php/'.
                   $cm1context->id.'/mod_resource/content/0/resource1.txt?forcedownload=1',
                   $resources[0]['contents'][0]['fileurl']);

           $this->assertEquals($resource2instance->cmid, $resources[1]['id']);           
           $cm2context = context_module::instance($resource2instance->cmid);
           $this->assertEquals('File 2', $resources[1]['name']);
           $this->assertEquals($CFG->wwwroot.'/webservice/pluginfile.php/'.
                   $cm2context->id.'/mod_resource/content/0/resource2.txt?forcedownload=1',
                   $resources[1]['contents'][0]['fileurl']);
        
        }
        /**
         * Course has no resources user is enrolled
         */
        $resources = local_ombiel_webservices::get_course_resources($course2->id);
        $resources = external_api::clean_returnvalue(local_ombiel_webservices::get_course_resources_returns(), $resources);
         
        $this->assertSame(array(), $resources);  
        /**
         * Course has no resources user is not enrolled
         */
        $this->setExpectedException('moodle_exception');
        $resources = local_ombiel_webservices::get_course_resources($course3->id);
        $resources = external_api::clean_returnvalue(local_ombiel_webservices::get_course_resources_returns(), $resources);
        
     }
     public function test_get_user_messages() {
        global $CFG, $DB, $USER;

        $this->resetAfterTest(true);
        $user1 = self::getDataGenerator()->create_user();
        $user2 = self::getDataGenerator()->create_user();
        $this->setUser($user1);
                
         /**
         * Set up sent messages
         */     
        $sentmessage = new stdClass();
        $sentmessage->useridfrom = $user1->id;
        $sentmessage->useridto = $user2->id;
        $sentmessage->fullmessage = 'Sent message 1';
        $sentmessage->format = 1;
        $sentmessage->timecreated = 1388679900;
                
        $DB->insert_record('message', $sentmessage);        
        
        $sentmessage->fullmessage = 'Sent message 2';
        $sentmessage->timeread = 1388679935;
        $DB->insert_record('message_read', $sentmessage);        
                
         /**
         * Set up incoming messages
         */     
        $sentmessage = new stdClass();
        $sentmessage->useridfrom = $user2->id;
        $sentmessage->useridto = $user1->id;
        $sentmessage->fullmessage = 'Incoming message 1';
        $sentmessage->format = 1;
        $sentmessage->timecreated = 1388679900;
                
        $DB->insert_record('message', $sentmessage);        
        
        $sentmessage->fullmessage = 'Incoming message 2';
        $sentmessage->timeread = 1388679935;
        $DB->insert_record('message_read', $sentmessage);
         /**
         * Sent messages
         */
        $messages = local_ombiel_webservices::get_user_messages(false, true);
        $messages = external_api::clean_returnvalue(local_ombiel_webservices::get_user_messages_returns(), $messages);
        $this->assertEquals(2, count($messages));    
             
        $this->assertEquals('Sent message 1', $messages[1]['message']);  
        $this->assertEquals('Sent message 2', $messages[0]['message']);    
        
         /**
         * Read messages
         */
        $messages = local_ombiel_webservices::get_user_messages(true);
        $messages = external_api::clean_returnvalue(local_ombiel_webservices::get_user_messages_returns(), $messages);
        $this->assertEquals(1, count($messages));    
             
        $this->assertEquals('Incoming message 2', $messages[0]['message']);  
         /**
         * Unread messages
         */
        $messages = local_ombiel_webservices::get_user_messages();
        $messages = external_api::clean_returnvalue(local_ombiel_webservices::get_user_messages_returns(), $messages);
        $this->assertEquals(1, count($messages));    
             
        $this->assertEquals('Incoming message 1', $messages[0]['message']);     

     }
     public function test_get_native_moodle_link() {
        global $CFG, $DB, $USER;

        $this->resetAfterTest(true);
        
        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();
        $user1 = self::getDataGenerator()->create_user();
        $this->setUser($user1);
        $this->getDataGenerator()->enrol_user($user1->id, $course1->id);
        
        $context = context_course::instance($course1->id);
       
        $_GET['wstoken'] = md5('test');
         /**
         * No course
         */
        $link = local_ombiel_webservices::get_native_moodle_link();
        $link = external_api::clean_returnvalue(local_ombiel_webservices::get_native_moodle_link_returns(), $link);
             
        $this->assertEquals($CFG->wwwroot.'/local/ombiel_webservices/login.php?wstoken='.md5('test').'&userid='.$user1->id, $link['link']);  
            
       
         /**
         * courseid
         */
        $link = local_ombiel_webservices::get_native_moodle_link($course1->id);
        $link = external_api::clean_returnvalue(local_ombiel_webservices::get_native_moodle_link_returns(), $link);
             
        $this->assertEquals($CFG->wwwroot.'/local/ombiel_webservices/login.php?wstoken='.md5('test').'&userid='.$user1->id.'&courseid='.$course1->id, $link['link']);  
        /**
         * User is not enrolled on course
         */
        $this->setExpectedException('moodle_exception');
        $link = local_ombiel_webservices::get_native_moodle_link($course2->id);
            

     }
     public function test_get_message_settings_link() {
        global $CFG, $DB, $USER;

        $this->resetAfterTest(true);
        
        $user1 = self::getDataGenerator()->create_user();
        $this->setUser($user1);
       
        $_GET['wstoken'] = md5('test');
         /**
         * No course
         */
        $link = local_ombiel_webservices::get_message_settings_link();
        $link = external_api::clean_returnvalue(local_ombiel_webservices::get_message_settings_link_returns(), $link);
             
        $this->assertEquals($CFG->wwwroot.'/local/ombiel_webservices/login.php?wstoken='.md5('test').'&userid='.$user1->id.'&messages=true', $link['link']);  

     }
     
    /**
     * Create course module and link it to course
     * @param integer $courseid
     * @return integer $cm instance id
     */
    protected function precreate_course_module($courseid, $modulename) {
        global $DB, $CFG;
        require_once("$CFG->dirroot/course/lib.php");

        $sectionnum =  0;

        $cm = new stdClass();
        $cm->course             = $courseid;
        $cm->module             = $DB->get_field('modules', 'id', array('name'=>$modulename));
        $cm->instance           = 0;
        $cm->section            = 0;
        $cm->idnumber           = 0;
        $cm->added              = time();

        $cm->id = $DB->insert_record('course_modules', $cm);

        course_add_cm_to_section($courseid, $cm->id, $sectionnum);

        return $cm->id;
    }
 }
