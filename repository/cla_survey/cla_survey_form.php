<?php
// This file is part of Moodle - http://moodle.org/
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
 * HTML form  - does not use Moodle form API
 *
 * Moodle form API not used simply because of the formatting and
 * the extensive validation which will not fit in the timescales
 *
 * @package    repository_cla_survey
 * @copyright  2014 Copyright Licencing Authority (UK)
 * @author     Benjamin Ellis Mukudu Ltd - benjamin.c.ellis@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
?>
<div class="mdl-align">
    <form enctype="multipart/form-data" method="POST" id='uploadform'>
        <div id="cla_survey_page1">
            <input type="hidden" name="myrepoid" value="<?php echo $formdata['myrepoid'] ?>" />
            <input type="hidden" id="serverfilename" name="filename" value="<?php echo $formdata['filename'] ?>" />
            <input type="hidden" name="filesource" value="<?php echo $formdata['filesource'] ?>" />
            <input type="hidden" name="itemid" id="itemid" value="0" />
            <table >
                <tr>
                    <td class="mdl-right"><label><?php echo get_string('fileupload', 'repository_cla_survey') ?>:</label></td>
                    <td class="mdl-left">
                        <input name="repo_upload_file" id="uploadfile" type="file"
<?php
if ($formdata['filesource']) {
    echo 'disabled=true';
}
?> />
                    </td>
                </tr>
                <tr>
                    <td class="mdl-right"><label><?php echo get_string('serverfile', 'repository_cla_survey') ?>:</label></td>
                    <td class="mdl-left">
                        <a href="<?php echo $formdata['serverfileurl'] ?>">Select...</a>
<?php
if ($formdata['filename']) {
    echo '<br/><strong>' . $formdata['filename'] . '</strong>';
}
?>
                    </td>
                </tr>
                <tr>
                    <td class="mdl-right"><label><?php echo get_string('renamefile', 'repository_cla_survey') ?>:</label></td>
                    <td class="mdl-left"><input name="saveas" id="saveas" type="text"/></td>
                </tr>
                <tr>
                    <td class="mdl-right"><label><?php echo get_string('authorname', 'repository_cla_survey') ?>:</label></td>
                    <td class="mdl-left">
                        <input name="author" id="author" type="text" value="<?php echo $formdata['author'] ?>"/>
                    </td>
                </tr>
                <tr>
                    <td class="mdl-right"><label><?php echo get_string('license') ?>:</label></td>
                    <td class="mdl-left">
                        <select name="license" id="select-license">
<?php
foreach ($formdata['licenses'] as $key => $val) {
    if ($key == $formdata['defaultlicense']) {
        echo "<option value='$key' selected='selected'>$val</option>\n";
    } else {
        echo "<option value='$key'>$val</option>\n";
    }
}
?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="mdl-right">
                        <label><?php echo get_string('copyrightprompt', 'repository_cla_survey') ?>:</label>
                    </td>
                    <td class="mdl-left"><input id="copycheck" name="copyright" value="1" type="checkbox" checked="checked"/></td>
                </tr>
                <tr>
                    <td class="mdl-right"><label><?php echo get_string('sourcegroup', 'repository_cla_survey') ?>:</label></td>
                    <td class="mdl-center">
                        <input type="radio" id="radioWeb" name="sourcetype" value="Web">
                            <?php echo get_string('weblabel', 'repository_cla_survey') ?>&nbsp;
                        </input>
                        <input type="radio" id="radioDigital" name="sourcetype" checked='checked' value="Digital">
                            <?php echo get_string('digitallabel', 'repository_cla_survey') ?>&nbsp;
                        </input>
                        <input type="radio" id="radioPrint" name="sourcetype" value="Print">
                            <?php echo get_string('printlabel', 'repository_cla_survey') ?>&nbsp;
                        </input>
                    </td>
                </tr>
                <tr id="URLinput" style="display: none;">
                    <td class="mdl-right"><label><?php echo get_string('urlprompt', 'repository_cla_survey') ?>:</label></td>
                    <td class="mdl-left">
                        <input name="sourceurl" id="sourceurl" type="text"/>
                        <div id="sourceurlError" class="color: red">
                            <?php echo get_string('urlreqerror', 'repository_cla_survey') ?>
                        </div>
                    </td>
                </tr>
            </table>
            <div>
                <button type="submit" id="gotopage2" name="submit1" value="savefile" >
                    <?php echo get_string('submitprompt', 'repository_cla_survey') ?>
                </button>
            </div>
        </div>
        <div id="cla_survey_page2" style="display: none;">
            <table>
                <!-- only publications -->
                <tr>
                    <td class="mdl-right" width="25%">
                        <label>
                            <?php echo get_string('copytitleprompt', 'repository_cla_survey') ?>:
                        </label>
                    </td>
                    <td class="mdl-left">
                        <input name="copytitle" id="copytitle" type="text"/>
                        <div id="titleError" class="color: red">
                            <?php echo get_string('fieldreqerror', 'repository_cla_survey') ?>
                        </div>
                    </td>
                </tr>
                <!-- only publications -->
                <!--
                <tr>
                    <td colspan="2"><b>Publication Type</b></td>
                </tr>
                -->
                <tr>
                    <td class="mdl-right"><label><?php echo get_string('copygroup', 'repository_cla_survey') ?>:</label></td>
                    <td class="mdl-left">
                        <input type="radio" name="copytype" id="copytypebook" selected="selected" value="book">
                            <span id="bookprompt">
                                <?php echo get_string('booklabel', 'repository_cla_survey') ?>&nbsp;
                            </span>
                            <span id="ebookprompt">
                                <?php echo get_string('ebooklabel', 'repository_cla_survey') ?>&nbsp;
                            </span>
                        </input>
                        <input type="radio" name="copytype" id="copytypemag" value="magazine">
                            <span id="magazineprompt">
                                <?php echo get_string('magazinelabel', 'repository_cla_survey') ?>&nbsp;
                            </span>
                            <span id="emagazineprompt">
                                <?php echo get_string('emagazinelabel', 'repository_cla_survey') ?>&nbsp;
                            </span>
                        </input>
                        <input type="radio" name="copytype" id="copytypeother" value="other">
                            <span id="copytypeprompt">
                                <?php echo get_string('otherlabel', 'repository_cla_survey') ?>
                            </span>&nbsp;
                        </input>
                    </td>
                </tr>
                <!-- only publications -->
                <!--
                <tr>
                    <td colspan="2"><b>Publication Identification</b></td>
                </tr>
                -->
                <tr>
                    <td class="mdl-right"><label><?php echo get_string('copyidlabel', 'repository_cla_survey') ?>:</label></td>
                    <td class="mdl-left">
                        <input name="copiedid" id="copiedid" type="text"/>
                        <div id="copiedidError" class="color: red">
                            <?php echo get_string('fieldreqerror', 'repository_cla_survey') ?>
                        </div>
                    </td>
                </tr>
                <!-- only ebooks/book -->
                <tr id="authorrow">
                    <td class="mdl-right">
                        <label><?php echo get_string('copiedauthorlabel', 'repository_cla_survey') ?>:</label>
                    </td>
                    <td class="mdl-left">
                        <input name="copiedauthor" id="copiedauthor" type="text"/>
                        <div id="copiedauthorError" class="color: red">
                            <?php echo get_string('fieldreqerror', 'repository_cla_survey') ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="mdl-right">
                        <label><?php echo get_string('publisherlabel', 'repository_cla_survey') ?>:</label>
                    </td>
                    <td class="mdl-left">
                        <input name="publisher" id="publisher" type="text"/>
                        <div id="publisherError" class="color: red">
                            <?php echo get_string('fieldreqerror', 'repository_cla_survey') ?>
                        </div>
                    </td>
                </tr>
                <!-- all -->
                <!--
                <tr>
                    <td colspan="2"><b>Distribution</b></td>
                </tr>
                -->
                <tr>
                    <td class="mdl-right">
                        <label>
                            <?php echo get_string('pageslabel', 'repository_cla_survey') ?>:
                        </label>
                    </td>
                    <td class="mdl-left">
                        <div id="pagesError" class="color: red">
                            <?php echo get_string('pageserrorlabel', 'repository_cla_survey') ?>
                        </div>
                        <?php echo get_string('frompagelabel', 'repository_cla_survey') ?>:
                            <input type="text" name="frompage" id="frompage" size="4"/>&nbsp;
                        <?php echo get_string('topagelabel', 'repository_cla_survey') ?>:
                            <input type="text" name="topage" id="topage" size="4"/>&nbsp;
                        <div id="fromtoError" class="color: red">
                            <?php echo get_string('fieldnumerror', 'repository_cla_survey') ?>
                        </div>
                        <b><?php echo get_string('or', 'repository_cla_survey') ?></b>
                    </td>
                </tr>
                <tr>
                    <td class="mdl-right">
                        <label><?php echo get_string('totalpageslabel', 'repository_cla_survey') ?>:</label>
                    </td>
                    <td class="mdl-left">
                        <input name="totalpages" id="totalpages" type="text"/>
                        <div id="totalpagesError" class="color: red">
                            <?php echo get_string('fieldnumerror', 'repository_cla_survey') ?>
                        </div>
                    </td>
                </tr>
            </table>
            <div>
                <button type="button" id="goback" name="goback"><?php echo get_string('back') ?></button>
                <button type="submit" id="fullsubmit" name="submit2" value="savefile" >
                    <?php echo get_string('submitprompt', 'repository_cla_survey') ?>
                </button>
            </div>
        </div>
    </form>
</div>
