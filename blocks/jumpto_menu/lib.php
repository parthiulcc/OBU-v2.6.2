<?php
    require_once($CFG->dirroot."/blocks/jumpto_menu/block_jumpto_menu.php");

    function block_jumpto_menu_html()
    {
     global $COURSE, $PAGE, $CFG;
     $target="window";

     $jumpto_menu=new block_jumpto_menu();
     $jumpto_menu->init();

     if ($jumpto_menu->get_frame_name()=="_top")
         $target="window.parent";

     if ($PAGE->cm)
         return $jumpto_menu->get_navmenu($COURSE, true, $PAGE->cm, $target, false, "");
     else
         return $jumpto_menu->get_navmenu($COURSE, true, NULL, $target, false, "");
    }

?> 
