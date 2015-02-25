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

define('PRONTO_SUCCESS_ELT','success');
define('PRONTO_PLUGIN_ELT','plugin');
define('PRONTO_VERSION_ELT','version');
define('PRONTO_SOURCE_ELT','source');
define('PRONTO_NAME_ELT','name');

class xmlinfo {

  var $xmldoc;
  var $root;


  function xmlinfo() {

    $this->xmldoc = domxml_new_doc("1.0");
    $this->root = $this->xmldoc->create_element(PRONTO_SUCCESS_ELT);
  }

  function addPluginElement($name,$version){
    $plugin_element = $this->xmldoc->create_element(PRONTO_PLUGIN_ELT);

    $name_element = $this->xmldoc->create_element(PRONTO_NAME_ELT);
    $name_element->append_child($this->xmldoc->create_text_node($name));
    $plugin_element->append_child($name_element);

    $version_element = $this->xmldoc->create_element(PRONTO_VERSION_ELT);
    $version_element->append_child($this->xmldoc->create_text_node($version));
    $plugin_element->append_child($version_element);

    $this->root->append_child($plugin_element);
  }

  function addSourceElement($name,$version){
    $source_element = $this->xmldoc->create_element(PRONTO_SOURCE_ELT);

    $name_element = $this->xmldoc->create_element(PRONTO_NAME_ELT);
    $name_element->append_child($this->xmldoc->create_text_node($name));
    $source_element->append_child($name_element);

    $version_element = $this->xmldoc->create_element(PRONTO_VERSION_ELT);
    $version_element->append_child($this->xmldoc->create_text_node($version));
    $source_element->append_child($version_element);

    $this->root->append_child($source_element);
  }

  function addElement($element_name,$text){
    $element = $this->xmldoc->create_element($element_name);
    $element->append_child($this->xmldoc->create_text_node($text));
    $this->root->append_child($element);
  }

  // Xml datas into a string
  function getXml() {

    $this->xmldoc->append_child($this->root);

    $xmlstring = $this->xmldoc->dump_mem(true);
    $finalstring = str_replace("\n", '', $xmlstring);

    return $finalstring;

  }
}
