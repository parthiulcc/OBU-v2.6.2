<?php
// The name of the theme
$THEME->name = 'obumobile';

// This theme relies on mymobile theme
$THEME->parents = array('mymobile');

// Sets a custom render factory to use with the theme, used when working with custom renderers.
$THEME->rendererfactory = 'theme_overridden_renderer_factory';

$THEME->sheets = array('obumobile');
