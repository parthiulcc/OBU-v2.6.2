<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

// Background colour setting
$name = 'theme_obu/bodybgcolor';
$title = get_string('bodybgcolor','theme_obu');
$description = get_string('bodybgcolordesc', 'theme_obu');
$default = '#fff';
$setting = new admin_setting_configtext($name, $title, $description, $default, PARAM_CLEAN, 12);
$settings->add($setting);

// Logo file setting
$name = 'theme_obu/logo';
$title = get_string('logo','theme_obu');
$description = get_string('logodesc', 'theme_obu');
$setting = new admin_setting_configtext($name, $title, $description, '', PARAM_URL);
$settings->add($setting);

// link color setting
$name = 'theme_obu/linkcolor';
$title = get_string('linkcolor','theme_obu');
$description = get_string('linkcolordesc', 'theme_obu');
$default = '#3266CC';
$previewconfig = NULL;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$settings->add($setting);

// link hover color setting
$name = 'theme_obu/linkhover';
$title = get_string('linkhover','theme_obu');
$description = get_string('linkhoverdesc', 'theme_obu');
$default = '#6d1523';
$previewconfig = NULL;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$settings->add($setting);

// block background colour setting
$name = 'theme_obu/blockbgcolor';
$title = get_string('blockbgcolor','theme_obu');
$description = get_string('blockbgcolordesc', 'theme_obu');
$default = '#ffffff';
$previewconfig = NULL;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$settings->add($setting);

// block heading background setting
$name = 'theme_obu/blockbgheading';
$title = get_string('blockbgheading','theme_obu');
$description = get_string('blockbgheadingdesc', 'theme_obu');
$default = '#dddddd';
$previewconfig = NULL;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$settings->add($setting);

// block heading color setting
$name = 'theme_obu/blockheadingcolor';
$title = get_string('blockheadingcolor','theme_obu');
$description = get_string('blockheadingcolordesc', 'theme_obu');
$default = '#585858';
$previewconfig = NULL;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$settings->add($setting);

// block border color setting
$name = 'theme_obu/blockbordercolor';
$title = get_string('blockbordercolor','theme_obu');
$description = get_string('blockbordercolordesc', 'theme_obu');
$default = '#ddd';
$previewconfig = NULL;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$settings->add($setting);

// Buttons background colour setting
$name = 'theme_obu/buttonsbgcolor';
$title = get_string('buttonsbgcolor','theme_obu');
$description = get_string('buttonsbgcolor', 'theme_obu');
$default = '#32a7c8';
$previewconfig = NULL;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$settings->add($setting);

/*
// Block region width
$name = 'theme_obu/regionwidth';
$title = get_string('regionwidth','theme_obu');
$description = get_string('regionwidthdesc', 'theme_obu');
$default = 210;
$choices = array(200=>'200px', 240=>'240px', 290=>'290px', 350=>'350px', 420=>'420px');
$setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
$settings->add($setting);
*/
 
// Foot note setting
$name = 'theme_obu/footertext';
$title = get_string('footertext','theme_obu');
$description = get_string('footertextdesc', 'theme_obu');
$setting = new admin_setting_confightmleditor($name, $title, $description, '');
$settings->add($setting);

// Footer background colour
$name = 'theme_obu/footerbgcolor';
$title = get_string('footerbgcolor','theme_obu');
$description = get_string('footerbgcolordesc', 'theme_obu');
$default = '#f1f1f1';
$previewconfig = NULL;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$settings->add($setting);

// Footer text colour
$name = 'theme_obu/footercolor';
$title = get_string('footercolor','theme_obu');
$description = get_string('footercolordesc', 'theme_obu');
$default = '#585858';
$previewconfig = NULL;
$setting = new admin_setting_configcolourpicker($name, $title, $description, $default, $previewconfig);
$settings->add($setting);

// Custom CSS file
$name = 'theme_obu/customcss';
$title = get_string('customcss','theme_obu');
$description = get_string('customcssdesc', 'theme_obu');
$setting = new admin_setting_configtextarea($name, $title, $description, '');
$settings->add($setting);

}