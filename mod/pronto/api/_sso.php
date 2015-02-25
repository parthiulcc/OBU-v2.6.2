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

/*Needed values :  
 * $uri_param : the URI parameter, representing the action to apply
 * $hash : the sig URL parameter
 * */

//If uri parameter is redirect, call the redirect mechanism
if ($uri_param == "redirect"){
  //Check if the userid parameter is given
  if (isset($userid)){

    /*Compute a signature with the userid, and compare it to the sig parameter
     * If they match, redirects the user to the required course, already logged in.
     * If not, redirects him to the Moodle error page
     */
    $local_hash = pronto_sha_sign($configured_account.$configured_secret.$userid.$timestamp);
    if ($local_hash!=$hash){
      pronto_add_log(PRONTO_ERROR,__FILE__." : ".__LINE__." Plugin config secret mismatch in file ");
      error("There was a problem with the system while trying to connect to course. Please contact your administrator and tell that you encoutered an error a this time ".date('r',time()).".");
    }

    $USER = get_complete_user_data('username',$userid);
    // In 1.6, get_complete_user_data() reads enrolment data too.
    // in later versions we need additional calls.
    if (function_exists('complete_user_login')) { // 1.9 onwards
      $USER=complete_user_login($USER);
    } elseif (function_exists('load_all_capabilities')) { // 1.7, 1.8
      load_all_capabilities();
    }
    header("location:".$CFG->wwwroot."/course/view.php?id=".$groupid); 

  }
  else { 

    /*Compare the local hash and the parameter one
     * If they match, redirects the user to the required course page without logging him automatically
     * If not, redirects him to the Moodle error page
     */
    if ($local_hash!=$hash){
      pronto_add_log(PRONTO_ERROR,__FILE__." : ".__LINE__." Plugin config secret mismatch in file ");
      error("There was a problem with the system while trying to connect to course. Please contact your administrator and tell that you encoutered an error a this time ".date('r',time()).".");
    }else{
      header("location:".$CFG->wwwroot."/course/view.php?id=".$groupid); 
    }	 	
  }
}
