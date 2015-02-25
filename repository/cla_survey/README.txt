Description
===========
Moodle Plugin: repository_cla_survey
====================================
Release : 0.1
Maturity : Beta

A Moodle repository plugin that allows the user to add copyright information for uploaded resources to allow the Copyright Licencing Authority to collect data for copyright surveys.

NB:
===
Two extenous files exist in the plugins directory for use with future developments of this plugin - cla_survey_form_moodle.php and the resultant html in moodle_form.html is an attempt to use formslib for the repository upload form.  See the comments in cla_survey_form_moodle.php to understand why they have not been used.

Pre-requisites
==============
You must have access to the server containing Moodle. This can be direct access, through a network or to a remote server through Internet with an FTP client, you can't do it from "inside" Moodle itself.

INSTALLATION
============
1.  Latest version of the zipped file for this plug is available from https://github.com/mukudu/moodle-repository_cla_survey

2.  In Moodle versions 2.5 and above, the zipped file can be dragged and dropped into the Moodle interface.

3.  In versions of Moodle below 2.3:

    3.1.  Unzip the zipped file somewhere on your local computer

    3.2.  Upload the unzipped folder to mod folder in the moodle root folder e.g /var/www/html/ on each of the Moodle servers

    3.3.  Alternatively the zip file can be uploaded to the folder in step 3 and the zipped file unzipped on the servers.

    3.4.  Ensure that the folder has the same permissions and owner as the other folders in the directory - e.g.

        3.4.1.  chown -R apache:apache cla_survey
        3.4.2.  chmod -R 755 cla_survey

    3.5.  In your browser, go to your Moodle site, login as administrator and choose Site Administration > Notifications  and click on the Continue Button.

4.  Moodle will report successful completion or any errors.

UNINSTALLATION
==============
Moodle Respository Plugins cannot be uninstalled, they have to be disabled via SiteAdministration -> Plugins -> plugins -> Manage plugins

