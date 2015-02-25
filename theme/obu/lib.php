<?php

/**
 * custom theme post process function for CSS
 * @param string $css Incoming CSS to process
 * @param stdClass $theme The theme object
 * @return string The processed CSS
 */
 
 function obu_process_css($css, $theme) {
 
	// Set the body background colour
    if (!empty($theme->settings->bodybgcolor)) {
        $bodybgcolor = $theme->settings->bodybgcolor;
    } else {
        $bodybgcolor = null;
    }
    $css = obu_set_bodybgcolor($css, $bodybgcolor);
	
    // Set the logo image
    if (!empty($theme->settings->logo)) {
        $logo = $theme->settings->logo;
    } else {
        $logo = null;
    }
    $css = obu_set_logo($css, $logo); 
 
    // Set the link color
    if (!empty($theme->settings->linkcolor)) {
        $linkcolor = $theme->settings->linkcolor;
    } else {
        $linkcolor = null;
    }
    $css = obu_set_linkcolor($css, $linkcolor);

	// Set the link hover color
    if (!empty($theme->settings->linkhover)) {
        $linkhover = $theme->settings->linkhover;
    } else {
        $linkhover = null;
    }
    $css = obu_set_linkhover($css, $linkhover);
	
	// Set the block heading background
    if (!empty($theme->settings->blockbgheading)) {
        $blockbgheading = $theme->settings->blockbgheading;
    } else {
        $blockbgheading = null;
    }
    $css = obu_set_blockbgheading($css, $blockbgheading);
	
	// Set the block background colour
    if (!empty($theme->settings->blockbgcolor)) {
        $blockbgcolor = $theme->settings->blockbgcolor;
    } else {
        $blockbgcolor = null;
    }
    $css = obu_set_blockbgcolor($css, $blockbgcolor);
	
	// Set the block heading colour
    if (!empty($theme->settings->blockheadingcolor)) {
        $blockheadingcolor = $theme->settings->blockheadingcolor;
    } else {
        $blockheadingcolor = null;
    }
    $css = obu_set_blockheadingcolor($css, $blockheadingcolor);
	
	// Set the block BORDER colour
    if (!empty($theme->settings->blockbordercolor)) {
        $blockbordercolor = $theme->settings->blockbordercolor;
    } else {
        $blockbordercolor = null;
    }
    $css = obu_set_blockbordercolor($css, $blockbordercolor);
	
	// Set the BUTTONS background colour
    if (!empty($theme->settings->buttonsbgcolor)) {
        $buttonsbgcolor = $theme->settings->buttonsbgcolor;
    } else {
        $buttonsbgcolor = null;
    }
    $css = obu_set_buttonsbgcolor($css, $buttonsbgcolor);

	// Set the background footer
    if (!empty($theme->settings->footerbgcolor)) {
        $footerbgcolor = $theme->settings->footerbgcolor;
    } else {
        $footerbgcolor = null;
    }
    $css = obu_set_footerbgcolor($css, $footerbgcolor);
	
	// Set the footer text colour
    if (!empty($theme->settings->footercolor)) {
        $footercolor = $theme->settings->footercolor;
    } else {
        $footercolor = null;
    }
    $css = obu_set_footercolor($css, $footercolor);
    
   // Set custom css
   if (!empty($theme->settings->customcss)) {
        $customcss = $theme->settings->customcss;
    } else {
        $customcss = null;
    }
    $css = obu_set_customcss($css, $customcss);    

    // Return the CSS
    return $css;
} 
 
/**
 * Sets the custom css variable in CSS
 *
 */
 
function obu_set_bodybgcolor($css, $bodybgcolor) {
	global $OUTPUT;
	$tag = '[[setting:bodybgcolor]]';
	$replacement = $bodybgcolor;
	if (is_null($replacement)) {
		$replacement = $OUTPUT->pix_url('bg4', 'theme');
 	}
	$css = str_replace($tag, $replacement, $css);
	return $css;
} 
 
function obu_set_logo($css, $logo) {
	global $OUTPUT;
	$tag = '[[setting:logo]]';
	$replacement = $logo;
	if (is_null($replacement)) {
 		$replacement = $OUTPUT->pix_url('logo', 'theme');
 	}
	$css = str_replace($tag, $replacement, $css);
	return $css;
}   
 
function obu_set_linkcolor($css, $linkcolor) {
    $tag = '[[setting:linkcolor]]';
    $replacement = $linkcolor;
    if (is_null($replacement)) {
        $replacement = '#3266CC';
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}

function obu_set_linkhover($css, $linkhover) {
    $tag = '[[setting:linkhover]]';
    $replacement = $linkhover;
    if (is_null($replacement)) {
        $replacement = '#6d1523';
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}
function obu_set_blockbgcolor($css, $blockbgcolor) {
    $tag = '[[setting:blockbgcolor]]';
    $replacement = $blockbgcolor;
    if (is_null($replacement)) {
        $replacement = '#fff';
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}
function obu_set_blockbgheading($css, $blockbgheading) {
    $tag = '[[setting:blockbgheading]]';
    $replacement = $blockbgheading;
    if (is_null($replacement)) {
        $replacement = '#dddddd';
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}
function obu_set_blockheadingcolor($css, $blockheadingcolor) {
    $tag = '[[setting:blockheadingcolor]]';
    $replacement = $blockheadingcolor;
    if (is_null($replacement)) {
        $replacement = '#585858';
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}
function obu_set_blockbordercolor($css, $blockbordercolor) {
    $tag = '[[setting:blockbordercolor]]';
    $replacement = $blockbordercolor;
    if (is_null($replacement)) {
        $replacement = '#ddd';
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}
function obu_set_buttonsbgcolor($css, $buttonsbgcolor) {
    $tag = '[[setting:buttonsbgcolor]]';
    $replacement = $buttonsbgcolor;
    if (is_null($replacement)) {
        $replacement = '#32a7c8';
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}
function obu_set_footerbgcolor($css, $footerbgcolor) {
    $tag = '[[setting:footerbgcolor]]';
    $replacement = $footerbgcolor;
    if (is_null($replacement)) {
        $replacement = '#f1f1f1';
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}
function obu_set_footercolor($css, $footercolor) {
    $tag = '[[setting:footercolor]]';
    $replacement = $footercolor;
    if (is_null($replacement)) {
        $replacement = '#585858';
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}
function obu_set_customcss($css, $customcss) {
    $tag = '[[setting:customcss]]';
    $replacement = $customcss;
    if (is_null($replacement)) {
        $replacement = '';
    }
    $css = str_replace($tag, $replacement, $css);
    return $css;
}