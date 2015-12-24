<?php // $Id$

/**
 * repository_poodll
 * Moodle user can record/play poodll audio/video items
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

//added for moodle 2
require_once($CFG->libdir . '/filelib.php');
 
class repository_poodll extends repository {


	//here we add some constants to keep it readable
	const POODLLAUDIO = 0;
	const POODLLVIDEO = 1;
	const POODLLSNAPSHOT = 2;
	const MP3AUDIO = 3;
	const POODLLWIDGET = 4;
	const POODLLWHITEBOARD = 5;
	



    /*
     * Begin of File picker API implementation
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        global $CFG, $PAGE;
        
        parent::__construct ($repositoryid, $context, $options);
     
    }
    
    public static function get_instance_option_names() {
    	return array('recording_format','hide_player_opts');
    }
    
    //2.3 requires static, 2.2 non static, what to do? Justin 20120616 
    // created a 2.3 repo Justin 20120621
    public static function instance_config_form($mform) {
        $recording_format_options = array(
        	get_string('audio', 'repository_poodll'),
        	get_string('video', 'repository_poodll'),
			get_string('snapshot', 'repository_poodll'),
			get_string('mp3recorder', 'repository_poodll'),
			get_string('widget', 'repository_poodll'),
			get_string('whiteboard', 'repository_poodll')
        );
        
        $mform->addElement('select', 'recording_format', get_string('recording_format', 'repository_poodll'), $recording_format_options);  
        $mform->addRule('recording_format', get_string('required'), 'required', null, 'client');
		/* $mform->addElement('checkbox', 'hide_player_opts', 
			get_string('hide_player_opts', 'repository_poodll'),
			get_string('hide_player_opts_details', 'repository_poodll'));
			*/
		$hide_player_opts_options = array(
        	get_string('hide_player_opts_show', 'repository_poodll'),
        	get_string('hide_player_opts_hide', 'repository_poodll'));
		 $mform->addElement('select', 'hide_player_opts', get_string('hide_player_opts', 'repository_poodll'), $hide_player_opts_options);
		//$mform->setDefault('hide_player_opts', 0);
		$mform->disabledIf('hide_player_opts', 'recording_format', 'eq', self::POODLLVIDEO);
		$mform->disabledIf('hide_player_opts', 'recording_format', 'eq', self::POODLLSNAPSHOT);
		$mform->disabledIf('hide_player_opts', 'recording_format', 'eq', self::POODLLWIDGET);
		$mform->disabledIf('hide_player_opts', 'recording_format', 'eq', self::POODLLWHITEBOARD);

    }

	//login overrride start
	//*****************************************************************
	//
     // Generate search form
     //
    public function print_login($ajax = true) {
		global $CFG,$PAGE,$USER;

		//In Moodle 2.3 early version, screen was too narrow
		$screenistoonarrow=false;
		
		//Init our array
        $ret = array();

		
		//If we are selecting PoodLL Widgets, we don't need to show a login/search screen
		//just list the widgets
		if ($this->options['recording_format'] == self::POODLLWIDGET){
			$ret = array();
			$ret['dynload'] = true;
			$ret['nosearch'] = true;
			$ret['nologin'] = true;
			$ret['list'] = $this->fetch_poodllwidgets();
			return $ret;
		
		}	
		
		//If we are using an iframe based repo
        $search = new stdClass();
        $search->type = 'hidden';
        $search->id   = 'filename' . '_' . $this->options['recording_format'] ;
        $search->name = 's';
       // $search->value = 'winkle.mp3';
        
        //change next button and iframe proportions depending on recorder
        switch($this->options['recording_format']){
        	//video,snapshot
        	case self::POODLLVIDEO: 
			case self::POODLLSNAPSHOT: 	
					$height=350;
					$width=330;
					if($screenistoonarrow){
						$button = "<button class=\"fp-login-submit\" style=\"position:relative; top:-200px;\" >Next >>></button>";
					}else{
						$button= "";
					}
					break;
			//audio		
			case self::POODLLAUDIO:
			case self::MP3AUDIO:
					$height=220;
					$width=450;
					$button = "";
					break;
			
			case self::POODLLWHITEBOARD:
					$height=380;
					$width=520;
					$button = "";
					break;
        }


		$search->label = "<iframe scrolling=\"no\" frameBorder=\"0\" src=\"{$CFG->wwwroot}/repository/poodll/recorder.php?repo_id={$this->id}\" height=\"". $height ."\" width=\"" . $width . "\"></iframe>" . $button;

		$sort = new stdClass();
        $sort->type = 'hidden';
        $sort->options = array();
        $sort->id = 'poodll_sort';
        $sort->name = 'poodll_sort';
        $sort->label = '';

        $ret['login'] = array($search, $sort);
        $ret['login_btn_label'] = 'Next >>>';
        $ret['login_btn_action'] = 'search';
	
        return $ret;

    }
    

	public function check_login() {
        return !empty($this->keyword);
    }

		
	  
     // Method to get the repository content.
     //
     // @param string $path current path in the repository
     // @param string $page current page in the repository path
     // @return array structure of listing information
     //
    public function get_listing($path='', $page='') {
			return array();
		
   }
   
   ///
     // Return search results
     // @param string $search_text
     // @return array
     //
     //added $page=0 param for 2.3 compat justin 20120524
    public function search($filename, $page=0) {
        $this->keyword = $filename;
        $ret  = array();
        $ret['nologin'] = true;
		$ret['nosearch'] = true;
        $ret['norefresh'] = true;
        //echo $filename;
		$ret['list'] = $this->fetch_filelist($filename);
		
        return $ret;
    }
	
	    /**
     * Private method to fetch details on our recorded file,
	 * and filter options
     * @param string $keyword
     * @param int $start
     * @param int $max max results
     * @param string $sort
     * @return array
     */
    private function fetch_filelist($filename) {
		global $CFG,$USER;
	
		$hideoptions=false;
		if(!empty($this->options['hide_player_opts'])){
			$hideoptions=$this->options['hide_player_opts'];
		}
	
	
        $list = array();
		
		//if user did not record anything, or the recording copy failed can out sadly.
		if(!$filename){return $list;}
		//if(!$filename){$filename="houses.jpg";}
		
		//determine the file extension
		$ext = substr($filename,-4); 
		
		//determine the download source
		switch($this->options['recording_format']){
			case self::POODLLAUDIO:
			case self::POODLLVIDEO:
			
				if (\filter_poodll\poodlltools::isMobile($CFG->filter_poodll_html5rec)){
					$urltofile = moodle_url::make_draftfile_url("0", "/", $filename)->out(false);
					$source=$urltofile;
					
				}else{
					//set up auto transcoding (mp3) or not
					//The jsp to call is different.
					$jsp="download.jsp";
					if($ext ==".mp4" || $ext ==".mp3"){
						$jsp = "convert.jsp";
					}
						
					$source="http://" . $CFG->filter_poodll_servername . 
						":" . $CFG->filter_poodll_serverhttpport . "/poodll/" . $jsp. "?poodllserverid=" . 
						$CFG->filter_poodll_serverid . "&filename=" . $filename . "&caller=" . urlencode($CFG->wwwroot);
				}
				break;
			
			//this was the download script for snapshots and direct uploads
			//the upload script is the same file, called from widget directly. Callback posted filename back to form
			//case self::POODLLSNAPSHOT:
			//	$source=$CFG->wwwroot . '/repository/poodll/uploadHandler.php?filename=' . $filename;
			//	break;
			
			case self::POODLLSNAPSHOT:	
			case self::MP3AUDIO:
			case self::POODLLWHITEBOARD:
				//$source=$CFG->wwwroot . '/repository/poodll/uploadHandler.php?filename=' . $filename;
				//$source = $filename;
				
				/*
				$browser = get_file_browser();
				$fileinfo = $browser->get_file_info($context->id,"user","draft","0","/",$filename );
				$context = get_context_instance(CONTEXT_USER, $USER->id);
				
				//If we could get an info object, process. But if we couldn't, although we have info via $f, we don't have permissions
				//so we don't reveal it
				$urltofile = "no title";
				if($fileinfo){
					$urltofile = $fileinfo->get_url();
				}
				
				*/
				
				$urltofile = moodle_url::make_draftfile_url("0", "/", $filename)->out(false);
				$source=$urltofile;
		
		}
        
		//determine the player options
		switch($this->options['recording_format']){
			case self::POODLLAUDIO:
			case self::MP3AUDIO:
				
					//normal player
					if($ext==".mp3"){
						$list[] = array(
							'title'=> $filename,
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/audionormal.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>$source
						);
					
					}else{
						$list[] = array(
							'title'=> substr_replace($filename,'.audio' . $ext,-4),
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/audionormal.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>$source
						);
					}
				
				if(!$hideoptions){
					$list[] = array(
							'title'=> substr_replace($filename,'.mini'. $ext,-4),
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/miniplayer.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>$source
						);
					
					$list[] = array(
							'title'=> substr_replace($filename,'.word'. $ext,-4),
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/wordplayer.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>$source
						);
						
					$list[] = array(
							'title'=> substr_replace($filename,'.inlineword'. $ext,-4),
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/inlinewordplayer.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>$source
						);
					
					$list[] = array(
							'title'=> substr_replace($filename,'.once'. $ext,-4),
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/onceplayer.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>$source
						);
				}
				break;
		default:
				
			 $list[] = array(
                'title'=>$filename,
                'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/bigicon.png",
                'thumbnail_width'=>330,
                'thumbnail_height'=>115,
                'size'=>'',
                'date'=>'',
                'source'=>$source
            );
		
		}
           
       //return the list of files/player options
        return $list;
		
    }
	
	   /**
     * Download a file, this function can be overridden by subclass. {@link curl}
     *
     * @param string $url the url of file
     * @param string $filename save location
     * @return array with elements:
     *   path: internal location of the file
     *   url: URL to the source (from parameters)
     */
    public function get_file($url, $filename = '') {
        global $CFG,$USER;
		
		//if its mobile then we need to treat it as an upload
		if(\filter_poodll\poodlltools::isMobile($CFG->filter_poodll_html5rec)){
			//get the filename as used by our recorder
					$recordedname = basename($url);
					
					//get a temporary download path
					$path = $this->prepare_file($filename);

					//fetch the file we submitted earlier
				   $fs = get_file_storage();
				   $context = context_user::instance($USER->id);
					$f = $fs->get_file($context->id, "user", "draft",
                        "0", "/", $recordedname);
				
					//write the file out to the temporary location
					$fhandle = fopen($path, 'w');
					$data = $f->get_content();
					$result= fwrite($fhandle,$data);

					// Close file handler.
					fclose($fhandle);
					
					//bail if we errored out
					if ($result===false) {
						unlink($path);
						return null;
					}else{
						//clear up the original file which we no longer need
						self::delete_tempfile_from_draft("0", "/", $recordedname); 
					}
				
				//return to Moodle what it needs to know
				return array('path'=>$path, 'url'=>$url);
		
		}
		
		//if not mobile, determine the player options
		switch($this->options['recording_format']){
			case self::POODLLSNAPSHOT:
			case self::MP3AUDIO:
			case self::POODLLWHITEBOARD:
					//get the filename as used by our recorder
					$recordedname = basename($url);
					
					//get a temporary download path
					$path = $this->prepare_file($filename);

					//fetch the file we submitted earlier
				   $fs = get_file_storage();
				   $context = context_user::instance($USER->id);
					$f = $fs->get_file($context->id, "user", "draft",
                        "0", "/", $recordedname);
				
					//write the file out to the temporary location
					$fhandle = fopen($path, 'w');
					$data = $f->get_content();
					$result= fwrite($fhandle,$data);

					// Close file handler.
					fclose($fhandle);
					
					//bail if we errored out
					if ($result===false) {
						unlink($path);
						return null;
					}else{
						//clear up the original file which we no longer need
						self::delete_tempfile_from_draft("0", "/", $recordedname); 
					}
				
				//return to Moodle what it needs to know
				return array('path'=>$path, 'url'=>$url);
				break;
			
			default:
				return parent::get_file($url,$filename);
			
		}
			
      
    }
	
	/**
     *	Return an array of widget selectors, to be displayed in search results screen 
     * @return array
     */
	private function fetch_poodllwidgets(){
	global $CFG;
					
					$list = array();
	
						//stopwatch
						$list[] = array(
							'title'=> "stopwatch.pdl.mp4",
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/repostopwatch.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>'stopwatch.pdl'
						);
						//calculator
						$list[] = array(
							'title'=> "calculator.pdl.mp4",
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/repocalculator.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>'calculator.pdl'
						);
						//countdown timer
						$list[] = array(
							'title'=> "countdown_60.pdl.mp4",
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/repocountdown.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>'countdown_60.pdl'
						);
						//dice
						$list[] = array(
							'title'=> "dice_2.pdl.mp4",
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/repodice.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>'dice_2.pdl'
						);
						//simplewhiteboard
						$list[] = array(
							'title'=> "whiteboardsimple.pdl.mp4",
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/reposimplewhiteboard.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>'whiteboardsimple.pdl'
						);
						//fullwhiteboard
						$list[] = array(
							'title'=> "whiteboardfull.pdl.mp4",
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/repofullwhiteboard.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>'whiteboardfull.pdl'
						);
						//audiorecorder
						$list[] = array(
							'title'=> "audiorecorder.pdl.mp4",
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/repoaudiorecorder.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>'audiorecorder.pdl'
						);
						//videorecorder
						$list[] = array(
							'title'=> "videorecorder.pdl.mp4",
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/repovideorecorder.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>'videorecorder.pdl'
						);
						//click counter
						$list[] = array(
							'title'=> "counter.pdl.mp4",
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/repoclickcounter.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>'counter.pdl'
						);
						//sliderocket
						$list[] = array(
							'title'=> "sliderocket_1234567.pdl.mp4",
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/reposliderocket.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>'sliderocket_1234567.pdl'
						);
						//quizlet
						$list[] = array(
							'title'=> "quizlet_1234567.pdl.mp4",
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/repoquizlet.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>'quizlet_1234567.pdl'
						);
						//snapshot
						$list[] = array(
							'title'=> "snapshot.pdl.mp4",
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/reposnapshot.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>'snapshot.pdl'
						);
						//flashcards
						$list[] = array(
							'title'=> "flashcards_1234.pdl.mp4",
							'thumbnail'=>"{$CFG->wwwroot}/repository/poodll/pix/repoflashcards.jpg",
							'thumbnail_width'=>280,
							'thumbnail_height'=>100,
							'size'=>'',
							'date'=>'',
							'source'=>'flashcards_1234.pdl'
						);
	
			return $list;
	
	}
	

	  /**
     * 
     * @return string
     */
    public function supported_returntypes() {

		if(!empty($this->options['recording_format']) && $this->options['recording_format'] == self::POODLLWIDGET){
			return FILE_EXTERNAL;
		}else{
			return FILE_INTERNAL;
		}
    }
	


    /**
     * Returns the suported returns values.
     * 
     * @return string supported return value
     */
    public function supported_return_value() {
        return 'ref_id';
    }

    /**
     * Returns the suported file types
     *
     * @return array of supported file types and extensions.
     */
    public function supported_filetypes() {
		
		switch($this->options['recording_format']){
			case self::POODLLAUDIO:
			case self::POODLLVIDEO:
				$ret= array('.flv','.mp4','.mp3');
				break;
				
			case self::POODLLSNAPSHOT:
			case self::POODLLWHITEBOARD:
				$ret = array('.jpg');
				break;
				
			case self::MP3AUDIO:
				$ret = array('.mp3');
				break;
				
			case self::POODLLWIDGET:
				$ret = array('.pdl','.mp4');
				break;
		}
		return $ret;
    }
	
	   /*
     * Fetch the recorder widget
     */
    public function fetch_recorder() {
        global $USER,$CFG;
        
        $ret ="";
	
      //we get necessary info
	 $context = context_user::instance($USER->id);	
     $filename = 'filename' . '_' . $this->options['recording_format'] ;

	 //HTML5 Recording and Uploading audio/video
	if(\filter_poodll\poodlltools::isMobile($CFG->filter_poodll_html5rec) && 
		($this->options['recording_format'] == self::POODLLAUDIO ||
		$this->options['recording_format'] == self::MP3AUDIO ||
		$this->options['recording_format'] == self::POODLLVIDEO )){

			switch($this->options['recording_format']){
				case self::POODLLVIDEO:
					//we load up the file upload HTML5
					$ret .= \filter_poodll\poodlltools::fetch_HTML5RecorderForSubmission($filename, $context->id,"user","draft","0", "video", true);
					break;
					
				case self::POODLLAUDIO:
				case self::MP3AUDIO:
					//we load up the file upload HTML5
					$ret .= \filter_poodll\poodlltools::fetch_HTML5RecorderForSubmission($filename, $context->id,"user","draft","0", "audio", true);
					break;
			
			}//end of switch
    		/*
    		//we need a dummy M object so we can reuse module js here
    		$ret .= "<script type='text/javascript'>";
    		$ret .= "var M = new Object();";
    		$ret .= "</script>";
    		
    		//we load the poodll filter module JS for the HTML5 recording logic	
			$ret .= "<script type=\"text/javascript\" src=\"{$CFG->wwwroot}/filter/poodll/module.js\"></script> ";
        
			
        	//this calls the script we loaded just above, after we have a fileupload area to attach events to
        	$ret .= "<script type='text/javascript'>";
    		$ret .= "M.filter_poodll.loadmobileupload(0,0);";
    		$ret .= "</script>";
    		*/
    		
				
        	return $ret;
        }//end of if is mobile
		
		//HTML5 WIdgets and Uploading Images
		if(\filter_poodll\poodlltools::isMobile($CFG->filter_poodll_html5widgets) && 
				($this->options['recording_format'] == self::POODLLWHITEBOARD ||
				$this->options['recording_format'] == self::POODLLSNAPSHOT )){
				
				//we load up the file upload HTML5
				$ret .= \filter_poodll\poodlltools::fetch_HTML5RecorderForSubmission($filename, $context->id,"user","draft","0", "image", true);
				/*
				//we need a dummy M object so we can reuse module js here
				$ret .= "<script type='text/javascript'>";
				$ret .= "var M = new Object();";
				$ret .= "</script>";
				
				//we load the poodll filter module JS for the HTML5 recording logic	
				$ret .= "<script type=\"text/javascript\" src=\"{$CFG->wwwroot}/filter/poodll/module.js\"></script> ";
			
				
				//this calls the script we loaded just above, after we have a fileupload area to attach events to
				$ret .= "<script type='text/javascript'>";
				$ret .= "M.filter_poodll.loadmobileupload(0,0);";
				$ret .= "</script>";
				*/
				
				return $ret;
		}
		
      
     //   $usercontextid = get_context_instance(CONTEXT_USER, $USER->id)->id;
	//	$draftitemid=0;
	//	$ret .= '<form name="poodll_repository" action="' . $CFG->wwwroot . '/repository/poodll/recorder.php">';
	//	$filename = 'filename' . '_' . $this->options['recording_format'] ;
		switch($this->options['recording_format']){
			case self::POODLLAUDIO:
				//$ret .= \filter_poodll\poodlltools::fetchSimpleAudioRecorder('swf','poodllrepository',$USER->id,$filename);
				$ret .= \filter_poodll\poodlltools::fetchAudioRecorderForSubmission('auto','poodllrepository', $filename,$context->id,'user','draft',0,0);
				break;
			case self::POODLLVIDEO:
				//$ret .= \filter_poodll\poodlltools::fetchSimpleVideoRecorder('swf','poodllrepository',$USER->id,$filename,'','298', '340');
				$ret .= \filter_poodll\poodlltools::fetchVideoRecorderForSubmission('swf', 'poodllrepository', $filename, $context->id,'user','draft',0,0);
				break;
			case self::MP3AUDIO:
				//this is the mp3 recorder, by Paul Nichols
				//$ret = $this->fetchMP3PostRecorder("filename","apic.jpg", '290','340');
				//$ret = fetchMP3RecorderForRepo("filename");
				$ret .= \filter_poodll\poodlltools::fetchMP3RecorderForSubmission($filename,$context->id,"user","draft","0" );
				break;
			case self::POODLLWHITEBOARD:
				$ret .= \filter_poodll\poodlltools::fetchWhiteboardForSubmission($filename,$context->id,"user","draft","0",400,300,"",'drawingboard');
				break;
				
			case self::POODLLSNAPSHOT:
				$ret .= \filter_poodll\poodlltools::fetchSnapshotCameraforSubmission($filename,"apic.jpg", '290','340',$context->id,"user","draft","0");
	
				break;
		}
		return $ret;

	}
	
	
	//=====================================================================================
	//Start of  Paul Nichols MP3 Recorder
	//====================================================================================
	
	
	public function fetchMP3PostRecorder($param1,$param2,$param3,$param4){
		 global $CFG;
		// return fetch_mp3recorder();
       //initialize our return string
	   $recorder = "";
	 //  $filename ="pp.mp3";
       
	   //set up params for mp3 recorder
	   $url=$CFG->wwwroot.'/filter/poodll/flash/mp3recorder.swf?gateway=' . $CFG->wwwroot . '/repository/poodll/uploadHandler.php'; // /recorder=mp3/filename=' . $filename;//?filename=' . $filename;
		//$callback = urlencode("(function(a, b){d=parent.document;d.g=d.getElementById;fn=d.g('filename');fn.value=a;fd=d.g('upload_filedata');fd.value=b;f=fn;while(f.tagName!='FORM')f=f.parentNode;f.repo_upload_file.type='text';f.repo_upload_file.value='bogus.mp3';while(f.tagName!='DIV')f=f.nextSibling;f.getElementsByTagName('button')[0].click();})");
		 // $flashvars="&callback={$callback}&forcename=winkle";
		$flashvars="&filename=audio" . rand(10000,99999);
		  
		  
		//make our insert string
        $recorder = '<div style="position:absolute; top:0;left:0;right:0;bottom:0; background-color:#fff;">
                <input type="hidden"  name="upload_filename" id="upload_filename" value="sausages.mp3"/>
                <textarea name="upload_filedata" id="upload_filedata" style="display:none;"></textarea>

                <div id="onlineaudiorecordersection" style="margin:20% auto; text-align:center;">
                    <object id="onlineaudiorecorder" classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="215" height="138">
                        <param name="movie" value="'.$url.$flashvars.'" />
                        <param name="wmode" value="transparent" />
                        <!--[if !IE]>-->
                        <object type="application/x-shockwave-flash" data="'.$url.$flashvars.'" width="215" height="138">
                        <!--<![endif]-->
                        <div>
                                <p><a href="http://www.adobe.com/go/getflashplayer"><img src="http://www.adobe.com/images/shared/download_buttons/get_flash_player.gif" alt="Get Adobe Flash player" /></a></p>
                        </div>
                        <!--[if !IE]>-->
                        </object>
                        <!--<![endif]-->
                    </object>
                </div>
            </div>';
			
			//return the recorder string
			return $recorder;
	
	}
	

	
	//=====================================================================================
	//End of  Paul Nichols MP3 Recorder
	//====================================================================================
	
}