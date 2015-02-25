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

if (version_compare(PHP_VERSION, '5', '>=')) {
  require_once ('./domxml-php4-to-php5.php');
}

class datatoxml {
  var $xmldoc;
  var $root;
  var $entreprise;

  function datatoxml() {

    $this->xmldoc = domxml_new_doc("1.0");
    $this->root = $this->xmldoc->create_element("success");
    $this->entreprise = $this->xmldoc->create_element("enterprise");
  }

  function addPropertiesElement(){
    $properties = $this->xmldoc->create_element("properties");

    $comments = $this->xmldoc->create_element("comments");
    $comments->append_child($this->xmldoc->create_text_node("OK"));
    $properties->append_child($comments);

    $datetime = $this->xmldoc->create_element("datetime");
    $datetime->append_child($this->xmldoc->create_text_node(date("Y-m-d h:i:s")));
    $properties->append_child($datetime);

    $this->entreprise->append_child($properties);
  }

  /*Add a person
   *Params : username, firstname, lastname, email,  systemrole
   *
   * <person>
   *   <userid>[username]</userid>
   *   <name>
   *     <n>
   *       <family>[lastname]</family>
   *       <given>[firstname]</given>
   *     </n>
   *    </name>
   *    <email>[email]</email>
   *    <systemrole>[systemrole]</systemrole>
   * </person> 
   */
  function addPersonElement($uusername, $firstname = '', $lastname = '', $umail = '', $imsrole ='00') {


    $person = $this->xmldoc->create_element("person");
    $userid = $this->xmldoc->create_element("userid");

    $userid->append_child($this->xmldoc->create_text_node($uusername));
    $person->append_child($userid);

    $name = $this->xmldoc->create_element("name");
    $n = $this->xmldoc->create_element("n");

    $family = $this->xmldoc->create_element("family");
    $family->append_child($this->xmldoc->create_text_node($lastname));
    $n->append_child($family);

    $given = $this->xmldoc->create_element("given");
    $given->append_child($this->xmldoc->create_text_node($firstname));
    $n->append_child($given);

    $name->append_child($n);
    $person->append_child($name);

    $mail = $this->xmldoc->create_element("email");
    $mail->append_child($this->xmldoc->create_text_node($umail));
    $person->append_child($mail);


    $role = $this->xmldoc->create_element("systemrole");
    $role->append_child($this->xmldoc->create_text_node($imsrole));
    $person->append_child($role);

    $this->entreprise->append_child($person);
  }

  function addGroupIdElement($gid) {
    $group = $this->xmldoc->create_element("group");

    $sourcedid = $this->xmldoc->create_element("sourcedid");

    $id = $this->xmldoc->create_element("id");
    $id->append_child($this->xmldoc->create_text_node($gid));
    $sourcedid->append_child($id);

    $group->append_child($sourcedid);

    $this->entreprise->append_child($group);
  }

  /*Add a group element
   *Params : groupid, shortname, fullname, format, enrollability
   *
   * <group>
   *   <sourcedid>
   *    <id>[groupid]</id>
   *   </sourcedid>
   *   <timeframe>
   *     <begin>[timestart]</begin>
   *     <end>[timeend]</end>
   *   </timeframe>
   *   <grouptype>[format]</grouptype>
   *   <description>
   *     <short>[shortname]</short>
   *     <long>[fullname]</long>
   *     <full>[summary]</full>
   *   </description>
   *   <enrollcontrol>
   *     <enrollaccept>[enrollable]</enrollaccept>
   *   </enrollcontrol>
   *   <extension>
   *      <enrollmentcount>[enrollmentcount]</enrollmentcount>
   *   </extension>
   * </group>
   */
  function addGroupElement($gid, $gshortname, $gfullname, $gsummary, $gformat, $genrollable,$enrolment_count) {
    $group = $this->xmldoc->create_element("group");

    $sourcedid = $this->xmldoc->create_element("sourcedid");

    $id = $this->xmldoc->create_element("id");
    $id->append_child($this->xmldoc->create_text_node($gid));
    $sourcedid->append_child($id);

    $group->append_child($sourcedid);

    $timeframe = $this->xmldoc->create_element("timeframe");

    $begin = $this->xmldoc->create_element("begin");
    $begin->append_child($this->xmldoc->create_text_node(""));
    $timeframe->append_child($begin);

    $end = $this->xmldoc->create_element("end");
    $end->append_child($this->xmldoc->create_text_node(""));
    $timeframe->append_child($end);

    $group->append_child($timeframe);

    $grouptype = $this->xmldoc->create_element("grouptype");
    $grouptype->append_child($this->xmldoc->create_text_node($gformat));
    $group->append_child($grouptype);

    $description = $this->xmldoc->create_element("description");

    $short = $this->xmldoc->create_element("short");
    $short->append_child($this->xmldoc->create_text_node($gshortname));
    $description->append_child($short);

    $long = $this->xmldoc->create_element("long");
    $long->append_child($this->xmldoc->create_text_node($gfullname));
    $description->append_child($long);

    $full = $this->xmldoc->create_element("full");
    $full->append_child($this->xmldoc->create_text_node($gsummary));
    $description->append_child($full);


    $group->append_child($description);

    $enrollcontrol = $this->xmldoc->create_element("enrollcontrol");
    $enrollaccept = $this->xmldoc->create_element("enrollaccept");
    $enrollaccept->append_child($this->xmldoc->create_text_node($genrollable));
    $enrollcontrol->append_child($enrollaccept);
    $group->append_child($enrollcontrol);

    $extension = $this->xmldoc->create_element("extension");

    $enrolments = $this->xmldoc->create_element("enrollmentcount");
    $enrolments->append_child($this->xmldoc->create_text_node($enrolment_count));
    $extension->append_child($enrolments);;

    $group->append_child($extension);

    $this->entreprise->append_child($group);
  }

  /*Add a membership, for one group and one person
   *Params : courseid , user, contextid
   * <membership>
   *   <sourcedid>
   *     <id[courseid]</id>
   *   </sourcedid>
   *   <member>*
   *     <role>
   *       <userid>[user]</userid>
   *       <roletype>[role]</roletype>
   *     </role>
   *   </member>
   * </membership>
   */
  function addMembershipElement($courseid, $user, $imsroleid) {
    $membership = $this->xmldoc->create_element("membership");
    $sourcedid = $this->xmldoc->create_element("sourcedid");
    $id = $this->xmldoc->create_element("id");
    $id->append_child($this->xmldoc->create_text_node($courseid));
    $sourcedid->append_child($id);
    $membership->append_child($sourcedid);


    $member = $this->xmldoc->create_element("member");

    $role = $this->xmldoc->create_element("role");
    $userid = $this->xmldoc->create_element("userid");
    $userid->append_child($this->xmldoc->create_text_node($user->username));
    $role->append_child($userid);

    $status = $this->xmldoc->create_element("roletype");

    $status->append_child($this->xmldoc->create_text_node($imsroleid));
    $role->append_child($status);

    $member->append_child($role);
    $membership->append_child($member);

    $this->entreprise->append_child($membership);
  }

  // Xml datas into a string
  function getXml() {
    $this->root->append_child($this->entreprise);
    $this->xmldoc->append_child($this->root);

    $xmlstring = $this->xmldoc->dump_mem(true);
    $finalstring = str_replace("\n",'', $xmlstring);

    return $finalstring."\n";
  }
}
