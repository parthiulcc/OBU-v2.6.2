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

/*Creates a XMLinfo document, that will provide information about the system and the module
 * The pronto plugin version
 * The Moodle site version
 * The list of supported commands
 * The version of the api used in the plugin
 */

/*Needed values :  none */

$xml_global = new xmlinfo();

$xml_global->addPluginElement("Pronto in  Moodle",PRONTO_PLUGIN_VERSION);
$xml_global->addSourceElement("Moodle",$CFG->release);
$xml_global->addElement("supported-methods",PRONTO_SUPPORTED_METHODS);
$xml_global->addElement("apiversion","1.0");

echo $xml_global->getXml();
