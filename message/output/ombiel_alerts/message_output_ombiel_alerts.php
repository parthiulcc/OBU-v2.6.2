<?php
/**
 * Version details
 *
 * @copyright &copy; 2014 oMbiel
 * @author oMbiel
 * @package oMbiel_webservices
 * @version 1.0
 */

require_once($CFG->dirroot.'/message/output/lib.php');

/**
 * The alerts message processor
 *
 */
class message_output_ombiel_alerts extends message_output {

    /**
     * Processes the message and sends a notification via the oMbiel alerts web service
     *
     * @param stdClass $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     * @return true if ok, false if error
     */
    function send_message($eventdata){
        global $CFG, $SITE;

        if (!empty($CFG->noemailever)) {
            // hidden setting for development sites, set in config.php if needed
            debugging('$CFG->noemailever active, no message sent.', DEBUG_MINIMAL);
            return true;
        }

        // skip any messaging suspended and deleted users
        if ($eventdata->userto->auth === 'nologin' or $eventdata->userto->suspended or $eventdata->userto->deleted) {
            return true;
        }
        
        $note = $SITE->shortname.': '.$eventdata->subject;

        $message = $eventdata->smallmessage;
        
        $soapOptions = array(
            'login' => $CFG->ombielalertsserversserverusername,
            'password' => $CFG->ombielalertsserverpassword,
            'location' => $CFG->ombielalertsserverendpoint,
        );
        
        $soapclient = new SoapClient($CFG->ombielalertsserverendpoint.'?wsdl', $soapOptions);
        
        $request = array(
            'orgCode' => $CFG->ombielalertsorgcode,
            'password' => $CFG->ombielalertsorgpassword,
            'notifications'=> array(
                'notification'=> array(
                    'notificationTargets' => array(
                        'notificationTarget'=> array(
                            'emailAddress'=>$eventdata->userto->email,                    
                        )
                    ),
                    'note' => $note,
                    'message' => $message,
                    'forceSms' => 'N',
                    'forceEmail' => 'N',
                    'forceCampusmNotification' => 'Y',
                 )                    
             ),
        );
        
        try {            
            $result = $soapclient->sendAlerts($request);
        } catch(SoapFault $e) {
            debugging($e->getMessage());
            return false;
        }
        return ($result->desc == 'Successful');
        
    }

    /**
     * Creates necessary fields in the messaging config form.
     *
     * @param array $preferences An array of user preferences
     */
    function config_form($preferences){
        return null;
    }

    /**
     * Parses the submitted form data and saves it into preferences array.
     *
     * @param stdClass $form preferences form class
     * @param array $preferences preferences array
     */
    function process_form($form, &$preferences){
        return null;
    }

    /**
     * Loads the config data from database to put on the form during initial form display
     *
     * @param array $preferences preferences array
     * @param int $userid the user id
     */
    function load_data(&$preferences, $userid){
        return null;
    }

    /**
     * Tests whether the alerts web service is configured
     * @return boolean true if the alerts web service is configured
     */
    function is_system_configured() {
        global $CFG;
        return (!empty($CFG->ombielalertsserverendpoint) && 
                !empty($CFG->ombielalertsserversserverusername) && 
                !empty($CFG->ombielalertsserverpassword) && 
                !empty($CFG->ombielalertsorgcode) && 
                !empty($CFG->ombielalertsorgpassword)
                );
    }

}

