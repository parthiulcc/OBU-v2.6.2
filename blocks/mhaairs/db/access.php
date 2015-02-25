<?php
/**
 * Block MHAAIRS Improved
 *
 * @package    block
 * @subpackage mhaairs
 * @copyright  2013-2014 Moodlerooms inc.
 * @author     Teresa Hardy <thardy@moodlerooms.com>
 * @author     Darko Miletic <dmiletic@moodlerooms.xom>
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'block/mhaairs:addinstance' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'coursecreator' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),
    'block/mhaairs:viewadmindoc' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_COURSE,
            'legacy' => array(
                    'manager' => CAP_ALLOW
            )
    ),
    'block/mhaairs:viewteacherdoc' => array(
            'captype' => 'read',
            'contextlevel' => CONTEXT_COURSE,
            'legacy' => array(
                    'teacher' => CAP_ALLOW,
                    'editingteacher' => CAP_ALLOW,
                    'coursecreator' => CAP_ALLOW,
                    'manager' => CAP_ALLOW
            )
     ),
     'block/mhaairs:myaddinstance' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'moodle/my:manageblocks'
     )
);
