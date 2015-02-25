<?php

/**
 * Planet eStream v5 Repository Plugin
 *
 * @since 2.0
 * @package    repository_planetestream
 * @copyright  2012 Planet eStream
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

require_once($CFG->dirroot . '/config.php');
require_once($CFG->dirroot . '/repository/lib.php');
defined('MOODLE_INTERNAL') || die();
class repository_planetestream extends repository
{

    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array())
    {
        parent::__construct($repositoryid, $context, $options);
  
    }
    
    public function check_login()
    {
        return !empty($this->keyword);
    }
    
    public function search($search_text, $page = 0) {        
   	global $SESSION;

        $sess_keyword  = 'planetestream_' . $this->id . '_keyword';


        $sess_sort     = 'planetestream_' . $this->id . '_sort';
	$sess_cat      = 'planetestream_' . $this->id . '_category';
	$sess_show     = 'planetestream_' . $this->id . '_mediatype';
	$sess_chapters = 'planetestream_' . $this->id . '_chapters';

        $sort          = optional_param('planetestream_sort', null, PARAM_TEXT);
        $cat           = optional_param('planetestream_cat', null, PARAM_TEXT);        
	$show          = optional_param('planetestream_show', null, PARAM_TEXT);        

	if (isset($_POST['planetestream_chapters'])) {
	    $chapters = 'on';
	}

        if ($page && !$search_text && isset($SESSION->{$sess_keyword})) {
            $search_text = $SESSION->{$sess_keyword};
        }
        if ($page && !$sort && isset($SESSION->{$sess_sort})) {
            $sort = $SESSION->{$sess_sort};
        }
        if (!$sort) {
            $sort = 'relevance'; // default
        }
        if ($page && !$cat && isset($SESSION->{$sess_cat})) {
           $cat = $SESSION->{$sess_cat};
        }

        if ($page && !$show && isset($SESSION->{$sess_show})) {
           $show = $SESSION->{$sess_show};
        }

        if ($page && !$chapters && isset($SESSION->{$sess_chapters})) {
           $chapters = $SESSION->{$sess_chapters};
        }

        $SESSION->{$sess_keyword} = $search_text;
        $SESSION->{$sess_sort}    = $sort;
        $SESSION->{$sess_cat}     = (string) $cat;
	$SESSION->{$sess_show}    = (string) $show;
	$SESSION->{$sess_chapters} = (string) $chapters;
        
        //$this->keyword  = $search_text;

        $ret            = array();
        $ret['nologin'] = true;
        $ret['page']    = (int) $page;

        if ($ret['page'] < 1) {
            $ret['page'] = 1;
        }

	if ($search_text=='') {
	    $search_text='*';
	}

        $ret['list']      = $this->funcGetList($search_text, $ret['page']-1, $sort, $cat, $show, $chapters);

        $ret['norefresh'] = true;
        $ret['nosearch']  = true;
        $ret['pages']     = -1; 

        if ((count($ret['list']) < 10) && (1==2)) { // CR170214
            $ret['pages'] = 0;
	    $ret['page']  = 0;
        }

        return $ret;

    }
    
    private function funcGetList($keyword, $pageindex, $sort, $cat, $show, $chapters)
    {
	global $USER;			
	global $SESSION;

        $list = array();

        $this->feed_url = $this->get_url() . '/VLE/Moodle/Default.aspx?search=' . urlencode($keyword) . '&format=5&pageindex=' . $pageindex . '&orderby=' . $sort . '&cat=' . $cat . '&show=' . $show . '&delta=' . $this->funcObfuscate($USER->username) . '&checksum=' . $this->funcGetChecksum() . '&mc=' . $chapters;
	
	$c   = new curl(array(
            'cache' => false,
            'module_cache' => false
        ));
        $content        = $c->get($this->feed_url);
        $xml            = simplexml_load_string($content);
        
	$dimensions = '';

	if (isset($SESSION->{$sess_dimensions})) {
           $dimensions = $SESSION->{$sess_dimensions};
        }

        foreach ($xml->item as $item) {
            $title       = (string) $item ->title;
            $description = (string) $item ->description;
            $description = str_replace('[ No Description ]', '', $description);
            $source      = (string) $this->get_url() . '/VLE/Moodle/Video/' . $item ->file . '.swf';
            $recordtype  = (string) 'Recording';
	    $tumbnailurl = (string) $this->get_url() . '/GetImage.aspx';
	    if ($item ->recordtype=='2') {
		$recordtype = 'Playlist';
	    } else if ($item ->recordtype=='4') {
		$recordtype = 'Photoset';
	    } else if ($item ->recordtype=='-99') {
		$recordtype = 'Chapter';
	    }
	    $shorttitle  = (string) '';
	    
	    $thumbnail_container_height = (string) '190';
	    $idparts = explode('~', $item->file);

	    if (count($idparts)==4) {

    		$thumbnail_container_height = (string) '90';
		$tumbnailurl .= '?type=chap&width=120&height=90&id='.$idparts[3];
		$shorttitle  = $title . ' (Chapter) ' . $description;

	    } else {

		$tumbnailurl .= '?type=cd&width=354&height=190&forceoverlay=true&id='.$idparts[0].'~'.$idparts[1];
		$shorttitle  = $recordtype . ' ' . get_string('addedon', 'repository_planetestream') . ' ' . date('d/m/Y',(integer)$item ->addedat) . ' ' . get_string('addedby', 'repository_planetestream') . ' ' . $item ->addedby . ' ' . $title . ' ' . $description;

	    }

	    //file_put_contents($_SERVER['DOCUMENT_ROOT'] .'/repository/planetestream/log.txt', '', FILE_APPEND | LOCK_EX);

	    $dimensions  = (string) $SESSION->{'planetestream_' . $this->id . '_dimensions'};
            $list[]      = array(
                'shorttitle' => $shorttitle,
                'thumbnail_title' => $title,
                'title' => $title . '.m4v', // extension required
                'thumbnail' => $tumbnailurl,
                'thumbnail_width' => '600',
                'thumbnail_height' => $thumbnail_container_height,
                'license' => 'Other',
                'size' => '', 
                'date' => '',
                'lastmodified' => '',
                'datecreated' => $item ->addedat,
                'author' => $item ->addedby,
                'dimensions' => str_replace('?d=','',$dimensions),
                'source' => $source . $dimensions
            );
        }
        return $list;
    }
    
    /**
     * planetestream plugin doesn't support global search
     */
    
    public function global_search()
    {
        return false;
    }
    
    private function funcGetChecksum()
    {
	$decChecksum = (float)(date('d')+date('m'))+(date('m')*date('d'))+(date('Y')*date('d'));
	$decChecksum += $decChecksum*(date('d')*2.27409)*.689274;
	return md5(floor($decChecksum));
    }

    private function funcObfuscate($strX) {	
	$strBase64Chars = '0123456789aAbBcCDdEeFfgGHhiIJjKklLmMNnoOpPQqRrsSTtuUvVwWXxyYZz/+=';			
	$strBase64String = base64_encode($strX);
	if ($strBase64String=='') {			
		return '';
	}
	$strObfuscated = '';							
	for ($i=0;$i<strlen($strBase64String);$i++) 
		{				
		$intPos = strpos($strBase64Chars, substr($strBase64String, $i , 1));
		if ($intPos==-1)	
		{
			return '';				
		}			
		$intPos += strlen($strBase64String) + $i;
		$intPos = $intPos%strlen($strBase64Chars);
            	$strObfuscated .= substr($strBase64Chars, $intPos, 1);
		}

	return $strObfuscated;

	}

    /**
     * get_listing..
     *
     * @param string $path
     * @param int $page
     * @return array
     */
    
    public function get_listing($path = '', $page = '')
    {
        return array();
    }
    
    
    /**
     * Generate search form
     */
    
    public function print_login($ajax = true)
    {
	global $USER;
	global $SESSION;
        $ret           = array();
	// help
	$help          = new stdClass();
        $help->type  = 'hidden';
	$help->label = '	<div style="position: absolute; left: 566px; top: 80px; width: 50px; text-align: center; white-space: nowrap; font-size: 14px;">
					<a title="add media" onclick="window.open(\'' . $this->get_url() . '/UploadContentVLE.aspx?sourceID=11\', \'add\', \'width=720,height=680,left=100,top=100\'); return false;" href="#">
					<img style="border-width: 0px; height: 24px; width: 24px;" alt="add media" src="/repository/planetestream/pix/addmedia.png" align="middle">&nbsp;add media</a>
				</div>
				<div style="position: absolute; left: 680px; top: 80px; width: 50px; text-align: center; white-space: nowrap; font-size: 14px;">
					<a title="view help" onclick="window.open(\'' . $this->get_url() . '/VLE/Moodle/Help.aspx\'); return false;" href="#">
					<img style="border-width: 0px; height: 24px; width: 24px;" alt="view help" src="/repository/planetestream/pix/help.png" align="middle">&nbsp;help</a>
				</div>';
	// searchbox
        $search        = new stdClass();
        $search->type  = 'text';
        $search->id    = 'planetestream_search';
        $search->name  = 's'; // tic
        $search->label = get_string('search', 'repository_planetestream') . ': ';
	// media type        
	$show          = new stdClass();
        $show->type    = 'select';
        $show->options = array(
            (object) array(
                'value' => '0',
                'label' => get_string('show_all', 'repository_planetestream')
            ),
            (object) array(
                'value' => '7',
                'label' => get_string('show_video', 'repository_planetestream')
            ),
            (object) array(
                'value' => '2',
                'label' => get_string('show_playlist', 'repository_planetestream')
            ),
            (object) array(
                'value' => '4',
                'label' => get_string('show_photoset', 'repository_planetestream')
            ),
        );
        $show->id      = 'planetestream_show';
        $show->name    = 'planetestream_show';
        $show->label   = get_string('show', 'repository_planetestream') . ': ';
	// sort cb
        $sort          = new stdClass();
        $sort->type    = 'select';
        $sort->options = array(
            (object) array(
                'value' => '0',
                'label' => get_string('sort_orderby_relevance', 'repository_planetestream')
            ),
            (object) array(
                'value' => '8',
                'label' => get_string('sort_orderby_date', 'repository_planetestream')
            ),
            (object) array(
                'value' => '7',
                'label' => get_string('sort_orderby_rating', 'repository_planetestream')
            ),
            (object) array(
                'value' => '3',
                'label' => get_string('sort_orderby_popularity', 'repository_planetestream')
            )
        );
        $sort->id      = 'planetestream_sort';
        $sort->name    = 'planetestream_sort';
        $sort->label   = get_string('sort_orderby', 'repository_planetestream') . ': ';
        // category cb
        $category           = new stdClass();
        $category->type     = 'select';
        $url           = (string) $this->get_url() . '/VLE/Moodle/Default.aspx?show=info&delta=' . $this->funcObfuscate($USER->username) . '&checksum=' . $this->funcGetChecksum();

        $c             = new curl(array(
            'cache' => false,
            'module_cache' => false
        ));
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT ,6); 
	curl_setopt($c, CURLOPT_TIMEOUT, 12); 
        $content       = $c->get($url);
        $xml           = simplexml_load_string($content);
        $cats          = array();
        $cats[]	       = array(
		'value' => '0',
		'label' => 'All'
            );

        foreach ($xml->cats->cat as $catitem) {   
		$cats[] = array(
                'value' => (string) $catitem->id,
                'label' => (string) $catitem->name
            );
        }
        if ($xml->catname[0] != '') {
            $category->label = $xml->catname[0] . ':';
        } else {
            $category->label = 'Category:';
        }
        $category->id            = 'planetestream_cat';
        $category->name          = 'planetestream_cat';
        $category->options       = $cats;
	//
        $chapters        	 = new stdClass();
        $chapters->type  	 = 'checkbox';
        $chapters->id    	 = 'planetestream_chapters';
        $chapters->name  	 = 'planetestream_chapters';
        $chapters->label 	 = get_string('sort_includechapters', 'repository_planetestream') . ': ';
	//
        $ret['login']            = array(
	    $help,
            $search,
	    $show,
            $sort,
            $category,
	    $chapters
        );
        $ret['login_btn_label']  = get_string('search');
        $ret['login_btn_action'] = 'search';
        $ret['allowcaching']     = false;
	
	$strDimensions = $xml->dimensions[0];
	if ($strDimensions != '') {
		$SESSION->{'planetestream_' . $this->id . '_dimensions'} = (string) '?d=' . $strDimensions;
	}

        return $ret;

    }
    
    /**
     * file types supported by planetestream plugin
     * @return array
     */
    
    public function supported_filetypes()
    {
        return array(
            'video'
        );
    }
    
    /**
     * Gets the names of the repository config options as an array
     * @return array The array of config option names
     */
    
    public static function get_type_option_names()
    {
        return array(
            'url',
            'pluginname'
        );
    }
    
    /**
     * Edit/Create Admin Settings Moodle form
     *
     * @param moodleform $mform Moodle form (passed by reference)
     * @param string $classname repository class name
     */
    
    public static function type_config_form($mform, $classname = 'repository')
    {
        parent::type_config_form($mform, $classname);
        
        $mform->addElement('text', 'url', get_string('settingsurl', 'repository_planetestream'));
        $mform->addElement('static', null, '', get_string('settingsurl_text', 'repository_planetestream'));
        
        $mform->addElement('static', null, '', '<p>&nbsp;</p><p>Please note: The remainder of the configuration options can be found on your Planet eStream Website Administration Console, within the <span style="font-style: italic">VLE Integration section.</span></p>');
    }
    
    /**
     * planetestream plugin only return external links
     * @return int
     */
    
    public function supported_returntypes()
    {
        return FILE_EXTERNAL;
    }

    function get_url()
    {
	$url = (string) get_config('planetestream', 'url');
	$intPos = (int) strpos($url, '://');
	if ($intPos != 0) {
		$intPos = strpos($url, '/', $intPos+3);
		if ($intPos != 0) {
			$url = substr($url, 0, $intPos);
		}
	}
	return $url;
    }

}