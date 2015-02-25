<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2014081900;

$plugin->requires = 2012120301;//Optional - minimum version number of Moodle that this plugin requires
//(Moodle 1.9 = 2007101509; Moodle 2.0 = 2010112400; Moodle 2.1 = 2011070100; Moodle 2.2 = 2011120100; Moodle 2.4 = 2012120301)

$plugin->component = 'local_courserollover'; // Full name of the plugin (used for diagnostics): plugintype_pluginname
//Optional - frankenstyle plugin name, strongly recommended. It is used for installation and upgrade diagnostics.

$plugin->maturity = MATURITY_BETA;//Optional - how stable the plugin is:
//MATURITY_ALPHA, MATURITY_BETA, MATURITY_RC, MATURITY_STABLE (Moodle 2.0 and above)

$plugin->release = '0.4 (Build: 2014081900)';//Optional - Human-readable version name
?>
