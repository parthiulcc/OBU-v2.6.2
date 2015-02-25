<?php
//// This file is part of Moodle - http://moodle.org/
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
 *
 * @copyright &copy; 2014 oMbiel
 * @author oMbiel
 * @package oMbiel_webservices
 * @version 1.0
 */
$capabilities = array(
    'local/ombiel_webservices:createtimelimitedtoken' => array(
        'captype' => 'write', 
        'contextlevel' => CONTEXT_SYSTEM, 
        'archetypes' => array(
            'user' => CAP_ALLOW,            
        ),
    ),
    // By default only allow students and non editing teachers to log in with a token.
    'local/ombiel_webservices:allowtokenlogin' => array(
        'captype' => 'write', 
        'contextlevel' => CONTEXT_SYSTEM, 
        'archetypes' => array(
            'user' => CAP_ALLOW,  
            'editingteacher' => CAP_PREVENT,     
            'manager' => CAP_PREVENT,            
        ),
    ),

);
