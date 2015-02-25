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
 * This the Javascript HTML that replaces the callback.php in the docs
 *
 * @package    repository_cla_survey
 * @copyright  2014 Copyright Licencing Authority (UK)
 * @author     Benjamin Ellis Mukudu Ltd - benjamin.c.ellis@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
?>
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<script type="text/javascript">
window.onload = function() {
    var fpicker = window.parent.M.core_filepicker.active_filepicker;            // later versions of Moodle
    if (fpicker === null) {
        // here we attempt to work out a filemanger instance
        if (!('keys' in Object)) {          // check for earlier browser versions
            for (var i in window.parent.M.core_filepicker.instances ) {
                mykey = i;
                break;
            }
        }else{
            mykey = Object.keys(window.parent.M.core_filepicker.instances)[0];
        }
        if (!mykey) {
            alert('error - cannot identify filemanager instance.');
            return;
        }
        fpicker = window.parent.M.core_filepicker.instances[mykey];
    }

    var event = "<?php echo $event ?>";
    if (event !== '') {
        var resource = {};
        resource.event = event;
        resource.existingfile = {};
        resource.existingfile.filename = "<?php echo $existingfilename ?>";
        resource.existingfile.filepath = "<?php echo $existingfilepath ?>";
        resource.newfile = {};
        resource.newfile.filename = "<?php echo $newfilename ?>";
        resource.newfile.filepath = "<?php echo $newfilepath ?>";
        fpicker.process_existing_file(resource);
    } else {
        var repotype = "<?php echo $repostype ?>";
        if (repotype == 'upload') {
            var obj = {
                'url':"<?php echo $url ?>",
                'file':"<?php echo $filename ?>",
                'source':"<?php echo $source ?>",
                'itemid': "<?php echo $itemid ?>"
            };
            fpicker.hide();
            if (fpicker.options.editor_target && fpicker.options.env=='editor') {
                fpicker.options.editor_target.value=obj.url;
                fpicker.options.editor_target.onchange();
            }
            obj.client_id = fpicker.options.client_id;
            var formcallback_scope = fpicker.options.magicscope ? fpicker.options.magicscope : fpicker;
            fpicker.options.formcallback.apply(formcallback_scope, [obj]);
        }else{
            var savepath = "<?php echo $saveaspath ?>";
            if (fpicker.options.env == 'editor') {
                // in editor, images are stored in '/' only
                savepath = '/';
            }
            var params = {
                'title':"<?php echo $filename ?>",
                'source':"<?php echo $source ?>",
                'savepath':savepath,
                'license':"<?php echo $license ?>",
                'author':"<?php echo $author ?>"
            };

            fpicker.wait('download', params['title']);  // display downloading message
            fpicker.request({
                action:'download',
                client_id: fpicker.options.client_id,
                repository_id: <?php echo $myrepoid ?>,
                'params': params,
                onerror: function(id, obj, args) {
                    fpicker.view_files();
                },
                callback: function(id, obj, args) {
                    if (fpicker.options.editor_target && fpicker.options.env=='editor') {
                        fpicker.options.editor_target.value=obj.url;
                        fpicker.options.editor_target.onchange();
                    }
                    fpicker.hide();
                    obj.client_id = fpicker.options.client_id;
                    var formcallback_scope = fpicker.options.magicscope ? fpicker.options.magicscope : fpicker;
                    fpicker.options.formcallback.apply(formcallback_scope, [obj]);
                }
            }, true);
        }
    }
}
</script>
</head>
<body><noscript></noscript></body>
</html>
