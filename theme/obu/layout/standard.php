<?php

$custommenu = $OUTPUT->custom_menu();
$hascustommenu = (empty($PAGE->layout_options['nocustommenu']) && !empty($custommenu));
$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);

$bodyclasses = array();
if ($hassidepre && !$hassidepost) {
    $bodyclasses[] = 'side-pre-only';
} else if ($hassidepost && !$hassidepre) {
    $bodyclasses[] = 'side-post-only';
} else if (!$hassidepost && !$hassidepre) {
    $bodyclasses[] = 'content-only';
}
if (!empty($PAGE->theme->settings->logo)) {
    $logourl = $PAGE->theme->settings->logo;
} else {
    $logourl = NULL;
}

if (!empty($PAGE->theme->settings->footertext)) {
    $footnote = $PAGE->theme->settings->footertext;
} else {
    $footnote = '<!-- There was no custom footnote set -->';
}
echo $OUTPUT->doctype(); ?>

<html <?php echo $OUTPUT->htmlattributes() ?>>

<head>
    <title><?php echo $PAGE->title ?></title>
    <?php echo $OUTPUT->standard_head_html() ?>
</head>

<body id="<?php p($PAGE->bodyid); ?>" class="<?php echo $PAGE->bodyclasses.' '.join(' ', $bodyclasses) ?>">
<?php echo $OUTPUT->standard_top_of_body_html() ?>

<div id="page">

<?php if ($PAGE->heading || (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar())) { ?>
    <div id="page-header">
	
	<?php if($logourl == NULL) { ?>
		<h1 class="headermain">
	       	<?php echo $PAGE->heading ?>
	    </h1>
	        <?php } else { ?>
	        <a href="/"><img src="<?php echo $OUTPUT->pix_url('logo', 'theme');?>" alt="Oxford Brookes University" /></a>
	<?php } ?>	
	
    <?php if ($PAGE->heading) { ?>   
	
			<div class="headermenu">
			<?php
                echo $OUTPUT->login_info();
                    if (!empty($PAGE->layout_options['langmenu'])) {
                        echo $OUTPUT->lang_menu();
                        }
                        echo $PAGE->headingmenu ?>
			</div>
			<?php } ?>
 
	<?php } ?>
	
	<?php if ($hascustommenu) { ?>
                <div id="custommenu"><?php echo $custommenu; ?></div>
	<?php } ?>
 
 </div>
 
    <?php if (empty($PAGE->layout_options['nonavbar']) && $PAGE->has_navbar()) { ?>           
			<div class="navbar clearfix">
                <div class="breadcrumb"><?php echo $OUTPUT->navbar(); ?></div>
                <div class="navbutton"> <?php echo $PAGE->button; ?></div>
            </div>
	<?php } ?>  


<!-- END OF HEADER -->

<div id="page-content">
    <div id="region-main-box">
        <div id="region-post-box">
            <div id="region-main-wrap">
                <div id="region-main">
                    <div class="region-content">
			<?php echo $OUTPUT->main_content(); ?>
                    </div>
                </div>
            </div>
            <?php if ($hassidepre) { ?>
                <div id="region-pre" class="block-region">
                    <div class="region-content">
                        <?php echo $OUTPUT->blocks_for_region('side-pre') ?>
                    </div>
                </div>
                <?php } ?>
 
                <?php if ($hassidepost) { ?>
                <div id="region-post" class="block-region">
                    <div class="region-content">
                        <?php echo $OUTPUT->blocks_for_region('side-post') ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
</div>
<!-- START OF FOOTER -->
<?php if (empty($PAGE->layout_options['nofooter'])) { ?>
    <div id="page-footer" class="clearfix">
        <div class="page-footer-wrapper">			
			<div class="footer-left"><?php echo $footnote ?>
			<p class="helplink"><?php echo page_doc_link(get_string('moodledocslink')) ?></p>
			</div>
			<div class="powered" >
			Powered by <a href="#">Moodle</a> | Hosted by <a href="#">ULCC</a>
			</div>
            <?php echo $OUTPUT->standard_footer_html(); ?>
		</div>
	</div>
<?php } ?>

<?php echo $OUTPUT->standard_end_of_body_html() ?>
</body>
</html>
