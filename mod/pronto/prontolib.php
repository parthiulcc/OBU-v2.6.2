<?php
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2011 Blackboard Inc., All Rights Reserved.                *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Blackboard Inc.                       *
 *      You can redistribute it and/or modify it under the terms of           *
 *      the GNU General Public License as published by the                    *
 *      Free Software Foundation.                                             *
 *                                                                            *
 * WARRANTIES:                                                                *
 *      This software is distributed in the hope that it will be useful,      *
 *      but WITHOUT ANY WARRANTY; without even the implied warranty of        *
 *      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         *
 *      GNU General Public License for more details.                          *
 *                                                                            *
 *      You should have received a copy of the GNU General Public License     *
 *      along with the Wimba Probto Moodle Integration;                      *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Jonathan Abourbih
 *                                                                            *
 * Date: 14 April 2011
 *                                                                            *
 ******************************************************************************/
global $CFG;


///Set all required constants by the plugin
define( 'PRONTO_PLUGIN_VERSION', '5.2.0-8 (g9bef63e)' );
define( 'PRONTO_TIMESTAMP_LAG', '300000' ); //
define( 'PRONTO_SUPPORTED_METHODS', 'groupinfo,grouplist,personinfo,info,configtest');
define( 'PRONTO_DEFAULT_SERVER_URL', 'https://pronto.wimba.com');
define( 'PRONTO_WIMBA_DIR', $CFG->dataroot."/wimba");
define( 'PRONTO_DIR', $CFG->dataroot."/wimba/pronto");
define( 'PRONTO_LOGS_DIR', $CFG->dataroot."/wimba/pronto/logs");
define( 'PRONTO_TIME_DIFF_DELAY',       1800);

//Set error values, compatible with server standards.
define( 'PRONTO_UNKNOWN_ERROR',     128);
define( 'PRONTO_ACCOUNT_EMPTY',     256);
define( 'PRONTO_ACCOUNT_MISMATCH',  512);
define( 'PRONTO_SECRET_MISMATCH',  1024);
define( 'PRONTO_TS_EMPTY',         2048);
define( 'PRONTO_TS_EXPIRED',       4096);

//Setting the moodle  versions
define( 'PRONTO_MOODLE_16_VERSION',     16);
define( 'PRONTO_MOODLE_17_VERSION',     17);
define( 'PRONTO_MOODLE_18_VERSION',     18);
define( 'PRONTO_MOODLE_19_VERSION',     19);
define( 'PRONTO_MOODLE_20_VERSION',     20);

define('PRONTO_INFO',  'info');
define('PRONTO_WARN',  'warn');
define('PRONTO_ERROR', 'error');
define('PRONTO_DEBUG', 'debug');

// TODO Remove
$CFG->pronto_log_level = PRONTO_DEBUG;

define( 'MOODLE_RELEASE', $CFG->release);


///Set XML element name constants
define('PRONTO_NUMBER_ELT','number');
define('PRONTO_MESSAGE_ELT','message');
define('PRONTO_ERROR_ELT','error');

//Set parameters constant
define('PRONTO_ID_PARAMETER','id');
define('PRONTO_IDS_PARAMETER','ids');
define('PRONTO_GROUPID_PARAMETER','groupid');
define('PRONTO_SEPARATOR_PARAMETER','separator');
define('PRONTO_ACCOUNT_PARAMETER','account');
define('PRONTO_USERID_PARAMETER','userid');

/**Encrypt a string with the sha algorithm, and remove the prefix 0 that can appear
 * param : string
 * returns : a string, representing an hexadecimal number
 */
function pronto_sha_sign ($string){
	$string = sha1($string);
	while (substr($string, 0, 1) == "0"){
		$length = strlen($string);
		$string = substr($string, 1 , $length);
	}
	return $string;
}

/**Returns the http header of the url's call response
 * param : an url string
 * returns : a string, containing the header if the http response
 */
function pronto_get_header($url){
    global $CFG;

	$ch = curl_init();    // initialize curl handle
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    # use a proxy server if configured
    if (!empty($CFG->proxyhost)) {
        if (empty($CFG->proxyport)) {
            curl_setopt($ch, CURLOPT_PROXY, $CFG->proxyhost);
        } else {
            curl_setopt($ch, CURLOPT_PROXY, $CFG->proxyhost.':'.$CFG->proxyport);
        }
    }

	$result = curl_exec($ch); // run the whole process
	curl_close($ch);
	return $result;
}

/**Finds the date information in the http header, in gmt
 * param : a string representing the header
 * returns : a timestamp, corresponding to the date
 */
function pronto_find_date($s){
	$st = strpos($s,"Date") + 5;

	$date =  substr($s,$st);
	$end = strpos($date,"\n") - 1;
	$date = substr($date,0,$end);
	if ($date == "") {return "-1";} else {
		return @strtotime($date);}
}

/**Finds the header date informatio for an url
 * param : an url string
 * returns : a timestamp, corresponding to the date
 */
function pronto_get_url_timestamp($url){
	return pronto_find_date(pronto_get_header($url));
}

/**
 * Returns the Pronto timestamps as a string depending on the configuration
 * If the server is synchronized to NTP : compute the remote timestamp depending on the cached difference and the local time
 * If not : gets the remote timestamp
 */
function pronto_get_time ($is_ntp_enabled) {
	global $CFG;
	$timestamp = 0;
	$time  = time();

	//If the server is synchronized to NTP, returns the GMT time from the local server
	if ($is_ntp_enabled){
		$timestamp = $time."000";
	}else{
		//Else, verify than the last time caching has been done less than PRONTO_TIME_DIFF_DELAY seconds ago
		if (isset($CFG->pronto_server_time_diff) && isset($CFG->pronto_last_cache_time) && ($time - @$CFG->pronto_last_cache_time) < PRONTO_TIME_DIFF_DELAY) {
			//Builds the timestamp from the local gmt one and the cached difference
			$timestamp = ($time - $CFG->pronto_server_time_diff )."000";
		}else{
			//Computes the difference with the remote server, and cache it.
			$srv_time = pronto_get_url_timestamp($CFG->pronto_server_url);
			if ($srv_time == "-1") {
				//If there is a problem to retrieve the pronto server time we use the current server time (to try)
				$timestamp = $time."000";
			}
			else {
				$time_difference = $time - $srv_time;
				set_config("pronto_server_time_diff",$time_difference);
				set_config("pronto_last_cache_time",$time);
				$timestamp = $srv_time."000";}
		}
	}
	return $timestamp;
}

function pronto_set_to_iso_date($timestamp){
	if ($timestamp == 0) {
		return "";
	}else{
		$gmt_hour = date("O",$timestamp);

		return  date("Y-m-d\TH:i:s",$timestamp).substr($gmt_hour,0,3).":".substr($gmt_hour,3,2);
	}
}


/**Add logs in moodledata
 * param : the level og the log ('info', 'warn','error' or 'debug'), the message to display on the log
 * return : void, store the log in MOODLEDATADIR/pronto/logs/LEVEL
 */
function pronto_add_log($level,$message){

	//Set the log level values.
	$level_values = array(
	PRONTO_DEBUG => 1,
	PRONTO_INFO => 2,
	PRONTO_WARN => 3,
	PRONTO_ERROR => 4
	);
	global $CFG;
  global $SITE;

	//Write on the logs only if the configured level allows it.
	if ($level_values[$level] >= intval($CFG->pronto_log_level)){

		//Computes the timestamp corresponding to the day (at 00:00:00).
		$today_timestamp = @mktime(0, 0, 0, date("m"), date("d"), date("Y"));

		//If it doesn't exist, create the log folder'
		@mkdir(PRONTO_WIMBA_DIR, 0700);
			
		@mkdir(PRONTO_DIR, 0700);

		@mkdir(PRONTO_LOGS_DIR, 0700);

		//Computes the log filename. Space characters are replaced by unerscore, to have a correct filename.
		$file = PRONTO_LOGS_DIR."/".str_replace(' ','_',$SITE->shortname)."-".$today_timestamp."-pronto.log";

		//Writes the message in the log, and close it
		$fh = @fopen($file, "a");
		@fwrite($fh,gmdate("Y-m-d H:i:s")." ".strtoupper($level)." pronto - ".$message."\n");
		@fclose($fh);
	}
}

/**Returns an xml formatted error, and exit
 * param : the error number according to remote server specs, the message to describe the error
 * returns : exit returing the xml error code
 */
function pronto_xml_error($number,$message){
	pronto_add_log(PRONTO_ERROR,$message);
	$xml_global = new xmlresponse(PRONTO_ERROR_ELT);

	$xml_global->addElement(PRONTO_NUMBER_ELT,$number);
	$xml_global->addElement(PRONTO_MESSAGE_ELT,$message);

	exit($xml_global->getXml());
}
