Jump To Menu Block
------------------

This block has been provided by Tim Williams (tmw@autotrain.org) at AutoTrain eLearning

This block will provide a 'jump to' menu within Moodle 2.x, similar to that used by Moodle 1.x (much of the code
has been borrowed from 1.x). Install the block as normal by copying the contents of this download
into the moodle/blocks directory.

Once installed, the block can operate in a number of different ways :

1) By default, the block will use some clever javascript to display the jump to menu below the login/out link,
while hiding the actual block on the page when not in editing mode. If you place the block on a part of the page
with no other blocks, you will probably find that you get an empty column. Users without javascript will see the
menu inside a normal Moodle block.
2) If you prefer to have the menu displayed inside a normal Moodle block, then change the global config setting 
   "Show menu below login/logout link" (see Site Administration>Plugins>Blocks>Jump To Navigation)
3) If you want the menu to appear on content pages which can't display blocks (eg framed HTML), or would prefer
not to use javascript to place the menu, then you will need to modify your layout templates in order to display
the Jump to Menu in the correct place. You can find out how to do this by looking at the instructions on 
http://www.autotrain.org/misc/source/moodle2/jump_to/
