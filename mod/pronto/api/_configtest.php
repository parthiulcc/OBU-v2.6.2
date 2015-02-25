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
 * account : the account URL parameter
 * hash : the sig URL parameter
 * */

//Tests if the account matches with the locally configured account
if ($account != $configured_account) {
  pronto_add_log("error","Configtest : account mismatch");
  pronto_xml_error(PRONTO_ACCOUNT_MISMATCH,"Plugin config account mismatch in file : ".__FILE__." at line : ".__LINE__);
}


//Tests if the local signature matches with the remote sig
if ($hash != $local_hash){
  pronto_add_log("error","Configtest : signature mismatch");
  pronto_xml_error(PRONTO_SECRET_MISMATCH,"Plugin config secret mismatch in file : ".__FILE__." at line : ".__LINE__);
}

//If all tests passed, returns a empty success tag
$xml_global = new xmlresponse("success");

echo $xml_global->getXml();
