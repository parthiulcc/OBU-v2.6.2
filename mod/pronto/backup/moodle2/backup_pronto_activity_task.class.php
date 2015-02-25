<?php
/******************************************************************************
 *                                                                            *
 * Copyright (c) 1999-2013 Blackboard Inc., All Rights Reserved.              *
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
 *      along with the Blackboard Instant Messenger Moodle Integration;       *
 *      if not, write to the Free Software Foundation, Inc.,                  *
 *      51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA                *
 *                                                                            *
 * Author: Jonathan Abourbih                                                  *
 *                                                                            *
 ******************************************************************************/
require_once($CFG->dirroot . "/mod/pronto/backup/moodle2/backup_pronto_stepslib.php");

class backup_pronto_activity_task extends backup_activity_task {
    protected function define_my_settings()
    {

    }

    protected function define_my_steps()
    {
        $this->add_step(new backup_pronto_activity_structure_step('pronto_structure', 'pronto.xml'));
    }

    static public function encode_content_links($content)
    {
        return $content;
    }
}
