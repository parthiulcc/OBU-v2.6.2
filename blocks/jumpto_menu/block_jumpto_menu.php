<?php

/**
 * block class for jump to navigation menu
 *
 * @package   block_jumpto_menu
 * @copyright 2010 Tim Williams (tmw@autotrain.org)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
global $CFG;

require_once($CFG->dirroot."/blocks/moodleblock.class.php");

class block_jumpto_menu extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_jumpto_menu');
    }

    function has_config() {
        return true;
    }

    function applicable_formats() {
        return array('all' => true);
    }

    function get_content () {
        global $CFG, $COURSE, $PAGE;
        $this->content = new stdClass;
        $this->content->footer = '';

        if ($this->page->cm)
            $main=$this->get_navmenu($COURSE, true , $this->page->cm, "window", $CFG->block_jumpto_menu_jsmove, $this->instance->id);
        else
            $main=$this->get_navmenu($COURSE, false, NULL, "window", $CFG->block_jumpto_menu_jsmove, $this->instance->id);

        $this->content->text=$main;
        return $this->content;
    }

    function hide_header() {
        global $PAGE;
        return !$PAGE->user_is_editing();
    }

    function html_attributes() {
        global $PAGE, $CFG;
        $b="";
        if ($PAGE->user_is_editing() || !$CFG->block_jumpto_menu_hide_borders)
         $b='  block';

        $attributes = array(
            'id' => 'inst' . $this->instance->id,
            'class' => 'block_' . $this->name().$b
        );
        if ($this->instance_can_be_docked() && get_user_preferences('docked_block_instance_'.$this->instance->id, 0)) {
            $attributes['class'] .= ' dock_on_load';
        }
        return $attributes;
    }

    /**
     * Given a course and a (current) coursemodule
     * This function returns a small popup menu with all the
     * course activity modules in it, as a navigation menu
     * The data is taken from the serialised array stored in
     * the course record
     *
     * @param course $course A {@link $COURSE} object.
     * @param nav_buttons true if the next/back buttons are to be shown
     * @param course $cm A {@link $COURSE} object.
     * @param string $targetwindow ?
     * @param boolean
     * @return string scriptembed true if the menu should be encapsulated in javascript to move it to the headermenu div
     * @todo Finish documenting this function
     */
    function get_navmenu($course, $nav_buttons=true, $cm=NULL, $targetwindow="window", $scriptembed=true, $instance) {

        global $CFG, $THEME, $USER, $DB, $OUTPUT;

        if (empty($THEME->navmenuwidth)) {
            $width = 50;
        } else {
            $width = $THEME->navmenuwidth;
        }

        if ($cm) {
            $cm = $cm->id;
        }

        if ($course->format == 'weeks') {
            $strsection = get_string('week');
        } else {
            $strsection = get_string('topic');
        }
        $strjumpto = get_string('jumpto');

        $modinfo = get_fast_modinfo($course);
        $context = get_context_instance(CONTEXT_COURSE, $course->id);

        $section = -1;
        $selected = '';
        $url = '';
        $previousmod = NULL;
        $backmod = NULL;
        $nextmod = NULL;
        $selectmod = NULL;
        $logslink = NULL;
        $flag = false;
        $menu = array();
        $menustyle = array();

        $sections = $DB->get_records('course_sections', array ('course'=>$course->id),'section','section,visible,summary');

        if (!empty($THEME->makenavmenulist)) {   /// A hack to produce an XHTML navmenu list for use in themes
            $THEME->navmenulist = navmenulist($course, $sections, $modinfo, $strsection, $strjumpto, $width, $cm);
        }

        foreach ($modinfo->cms as $mod) {
            if ($mod->modname == 'label') {
                continue;
            }

            //$course->numsections no longer exists in MDL2.4, so test for it here
            $numsections=0;
            if (isset($course->numsections))
                $numsections=$course->numsections;
            else
                $numsections=count($sections)-1;

            if ($mod->sectionnum > $numsections) {   /// Don't show excess hidden sections
                break;
            }

            if (!$mod->uservisible) { // do not include empty sections at all
                continue;
            }

            if ($mod->sectionnum > 0 and $section != $mod->sectionnum) {
                $thissection = $sections[$mod->sectionnum];

                if ($thissection->visible or !$course->hiddensections or
                    has_capability('moodle/course:viewhiddensections', $context)) {
                    $thissection->summary = strip_tags(format_string($thissection->summary,true));
                    if ($course->format == 'weeks' or empty($thissection->summary)) {
                        $menu[] = '--'.$strsection ." ". $mod->sectionnum;
                    } else {
                        if (strlen($thissection->summary) < ($width-3)) {
                            $menu[] = '--'.$thissection->summary;
                        } else {
                            $menu[] = '--'.substr($thissection->summary, 0, $width).'...';
                        }
                    }
                    $section = $mod->sectionnum;
                } else {
                    // no activities from this hidden section shown
                    continue;
                }
            }

            $url = $mod->modname.'/view.php?id='. $mod->id;
            if ($flag) { // the current mod is the "next" mod
                $nextmod = $mod;
                $flag = false;
            }
            $localname = $mod->name;
            if ($cm == $mod->id) {
                $selected = $url;
                $selectmod = $mod;
                $backmod = $previousmod;
                $flag = true; // set flag so we know to use next mod for "next"
                $localname = $strjumpto;
                $strjumpto = '';
            } else {
                $localname = strip_tags(format_string($localname,true));
                $tl=new textlib();
                if ($tl->strlen($localname) > ($width+5)) {
                    $localname = $tl->substr($localname, 0, $width).'...';
                }
                if (!$mod->visible) {
                    $localname = '('.$localname.')';
                }
            }
            $menu[$url] = $localname;
            if (empty($THEME->navmenuiconshide)) {
                $menustyle[$url] = 'class="jumpto_menu_background_image" style="background-image: url('.$OUTPUT->pix_url('icon', $mod->modname).');"';
            }
            $previousmod = $mod;
        }
        //Accessibility: added Alt text, replaced &gt; &lt; with 'silent' character and 'accesshide' text.

        /****Not sure this is needed anymore****
        if ($selectmod and has_capability('coursereport/log:view', $context)) {
            $logstext = get_string('alllogs');
            $logslink = '<li style="list-style-type:none">'.'<a title="'.$logstext.'" '.
                        ' onclick="setTarget(this);"'.' href="'.
                        $CFG->wwwroot.'/course/report/log/index.php?chooselog=1&amp;user=0&amp;date=0&amp;id='.
                           $course->id.'&amp;modid='.$selectmod->id.'">'.
                        '<img class="icon log" src="'.$CFG->pixpath.'/i/log.gif" alt="'.$logstext.'" /></a>'.'</li>';

        }
        ***************************************/
        if ($nav_buttons && $backmod) {
            $backtext= get_string('activityprev', 'access');
              $backmod = '<li><form action="'.$CFG->wwwroot.'/mod/'.$backmod->modname.'/view.php" '.
                       'onclick="setTarget(this);"'.'><fieldset class="invisiblefieldset">'.
                       '<input type="hidden" name="id" value="'.$backmod->id.'" />'.
                       '<button type="submit" title="'.$backtext.'" >'.get_string('previous', 'block_jumpto_menu').
                       '</button></fieldset></form></li>';
        }
        if ($nav_buttons && $nextmod) {
            $nexttext= get_string('activitynext', 'access');
            $nextmod = '<li><form action="'.$CFG->wwwroot.'/mod/'.$nextmod->modname.'/view.php"  '.
                   'onclick="setTarget(this);"'.'><fieldset class="invisiblefieldset">'.
                   '<input type="hidden" name="id" value="'.$nextmod->id.'" />'.
                     '<button type="submit" title="'.$nexttext.'" >'.get_string('next', 'block_jumpto_menu').
                   '</button></fieldset></form></li>';
        }

        $popup=$this->get_popup_form($CFG->wwwroot .'/mod/', $menu, 'navmenupopup', $selected, $strjumpto,
                       '', '', true, $targetwindow, '', $menustyle);

        $divtext='<div '.
            'class="jumpto_menu" style="text-align:center;margin-top:2px;">'.'<ul>'.$logslink . $backmod .
            '<li>'.$popup.'</li>'.
            $nextmod . '</ul>'.'</div>';

        $final="<script type=\"text/javascript\" src=\"".$CFG->wwwroot."/blocks/jumpto_menu/dropdown.js\"></script>";

        if ($scriptembed)
        {
            $final.="<script type=\"text/javascript\">\n".
                " //<!--\n".
                " var text=".json_encode($divtext).";\n".
                " //-->\n".
                "</script>\n";
                //"<noscript>".$divtext."</noscript>\n";
        }
        else
            $final.=$divtext;

        $final.=$this->get_basic_script($targetwindow, $scriptembed, $instance);

        return $final;
    }

    function get_basic_script($targetwindow, $scriptembed, $instance)
    {
        global $CFG, $PAGE;

        $final="<script type=\"text/javascript\">\n".
           " //<!--\n".
           "function setTarget(w)\n".
           "{\n".
           " w.target=\"".$this->get_frame_name()."\";\n".
           "}\n\n".
           "function jumpTo()\n".
           "{\n".
           " ".$targetwindow.".location=document.getElementById(\"navmenupopup\").jump.options[document.getElementById(\"navmenupopup\").jump.selectedIndex].value;\n".
           "}\n".
           "function jumpToIE()\n".
           "{\n".
           " initSelect(\"navmenupopup\",".$targetwindow.");\n".
           "}\n".
           "\n";
        if ($scriptembed)
        {
            if (!$PAGE->user_is_editing())
                $final.=
                   "var block=document.getElementById(\"inst".$instance."\");\n".
                   "if (block!=null)\n".
                   " block.style.display=\"none\";\n";

            $final.=" var element=document.getElementById(\"page-header\");\n".
               "if (element!=null)\n".
               "{\n".
               "\n".
               " var ia=element.innerHTML.toLowerCase().indexOf(\"<div class=\\\"logininfo\\\">\");\n".
               " var ib=element.innerHTML.toLowerCase().indexOf(\"</div>\", ia);\n".
               " element.innerHTML=element.innerHTML.substring(0,ib+6)+text+element.innerHTML.substring(ib+6);\n".
               "}\n";
        }
        $final.=" //-->\n".
            "</script>\n";

        return $final;
    }

    function get_frame_name()
    {
        global $CFG;
        if (isset($CFG->framename))
            return $CFG->framename;

        return "_top";
    }

    /**
     * Implements a complete little popup form
     *
     * @uses $CFG
     * @param string $common  The URL up to the point of the variable that changes
     * @param array $options  Alist of value-label pairs for the popup list
     * @param string $formid Id must be unique on the page (originaly $formname)
     * @param string $selected The option that is already selected
     * @param string $nothing The label for the "no choice" option
     * @param string $help The name of a help page if help is required
     * @param string $helptext The name of the label for the help button
     * @param boolean $return Indicates whether the function should return the text
     *         as a string or echo it directly to the page being rendered
     * @param string $targetwindow The name of the target page to open the linked page in.
     * @param string $selectlabel Text to place in a [label] element - preferred for accessibility.
     * @param array $optionsextra TODO, an array?
     * @param mixed $gobutton If set, this turns off the JavaScript and uses a 'go'
     *   button instead (as is always included for JS-disabled users). Set to true
     *   for a literal 'Go' button, or to a string to change the name of the button.
     * @return string If $return is true then the entire form is returned as a string.
     * @todo Finish documenting this function<br>
     */
    function get_popup_form($common, $options, $formid, $selected='', $nothing='choose', $help='', $helptext='', $return=false,
        $targetwindow='window', $selectlabel='', $optionsextra=NULL, $gobutton=NULL) {

        global $CFG;
        static $go, $choose;   /// Locally cached, in case there's lots on a page

        if (empty($options)) {
            return '';
        }

        if (!isset($go)) {
            $go = get_string('go');
        }

        if ($nothing == 'choose') {
            if (!isset($choose)) {
                $choose = get_string('choose');
            }
            $nothing = $choose.'...';
        }

        $output = '<form action="'.$CFG->wwwroot.'/blocks/jumpto_menu/jumpto.php"'.
                        ' method="get" '.
                        ' target="'.$this->get_frame_name().'" '.
                        ' id="'.$formid.'"'.
                        ' class="popupform">';
        if ($help) {
            $button = helpbutton($help, $helptext, 'moodle', true, false, '', true);
        } else {
            $button = '';
        }

        if ($selectlabel) {
            $selectlabel = '<label for="'.$formid.'_jump">'.$selectlabel.'</label>';
        }

        if ($gobutton) {
            // Using the no-JavaScript version
            $javascript = '';
        } else
        if (check_browser_version('MSIE') || 
            (check_browser_version('Opera') && !check_browser_operating_system("Linux"))) {
            //IE and Opera fire the onchange when ever you move into a dropdown list with the keyboard.
            //onfocus will call a function inside dropdown.js. It fixes this IE/Opera behavior.
            //Note: There is a bug on Opera+Linux with the javascript code (first mouse selection is inactive),
            //so we do not fix the Opera behavior on Linux
            $javascript = ' onfocus="jumpToIE();"';
        } else
        {
            //Other browser
            $javascript = ' onchange="jumpTo()"';
        }    

        $output .= '<div>'.$selectlabel.$button.'<select id="'.$formid.'_jump" name="jump"'.$javascript.'>';

        if ($nothing != '') {
            $output .= "   <option value=\"javascript:void(0)\">$nothing</option>";
        }

        $inoptgroup = false;

        foreach ($options as $value => $label) {

            if ($label == '--') { /// we are ending previous optgroup
                /// Check to see if we already have a valid open optgroup
                /// XHTML demands that there be at least 1 option within an optgroup
                if ($inoptgroup and (count($optgr) > 1) ) {
                    $output .= implode('', $optgr);
                    $output .= '   </optgroup>';
                }
                $optgr = array();
                $inoptgroup = false;
                continue;
            } else if (substr($label,0,2) == '--') { /// we are starting a new optgroup

                /// Check to see if we already have a valid open optgroup
                /// XHTML demands that there be at least 1 option within an optgroup
                if ($inoptgroup and (count($optgr) > 1) ) {
                    $output .= implode('', $optgr);
                    $output .= '   </optgroup>';
                }

                unset($optgr);
                $optgr = array();
    
                $optgr[]  = '   <optgroup label="'. s(format_string(substr($label,2))) .'">';   // Plain labels

                $inoptgroup = true; /// everything following will be in an optgroup
                continue;

            } else {
               if (!empty($CFG->usesid) && !isset($_COOKIE[session_name()]))
                {
                    $url=sid_process_url( $common . $value );
                } else
                {
                    $url=$common . $value;
                }
                $optstr = '   <option value="' . $url . '"';

                if ($value == $selected) {
                    $optstr .= ' selected="selected"';
                }

                if (!empty($optionsextra[$value])) {
                    $optstr .= ' '.$optionsextra[$value];
                }

                if ($label) {
                    $optstr .= '>'. $label .'</option>';
                } else {
                    $optstr .= '>'. $value .'</option>';
                }

                if ($inoptgroup) {
                    $optgr[] = $optstr;
                } else {
                    $output .= $optstr;
                }
            }

        }

        /// catch the final group if not closed
        if ($inoptgroup and count($optgr) > 1) {
            $output .= implode('', $optgr);
            $output .= '    </optgroup>';
        }

        $output .= '</select>';
        $output .= '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
        if ($gobutton) {
            $output .= '<input type="submit" value="'.
                ($gobutton===true ? $go : $gobutton).'" />';
        } else {
            $output .= '<noscript><div id="noscript'.$formid.'" style="display: inline;">';
            $output .= '<input type="submit" value="'.$go.'" /></div></noscript>';
        }
        $output .= '</div></form>';

        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

}


?>
