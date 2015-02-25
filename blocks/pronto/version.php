<?php
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2011 Blackboard Inc., All Rights Reserved.              *
 *                                                                            *
 * COPYRIGHT:                                                                 *
 *      This software is the property of Blackboard Inc.                      *
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
 *      along with the Blackboard Instant Messaging Moodle Integration;       *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Jonathan Abourbih                                                  *
 *                                                                            *
 * Date: 14 April 2011                                                        *
 *                                                                            *
 ******************************************************************************/
defined('MOODLE_INTERNAL') || die();

$plugin->component = 'block_pronto';

$plugin->version  = 2013041201;   // The current module version (Date: YYYYMMDDXX)
$plugin->release  = "5.2.0-8 (g9bef63e)";        // Human Readable Version Number
$plugin->maturity = MATURITY_STABLE;

$plugin->requires = 2011120500; // Requires moodle version

$plugin->dependencies = array(
    'mod_pronto' => 2013041201
);
