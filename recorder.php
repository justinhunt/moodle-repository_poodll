<?php
require_once('../../config.php');
require_once($CFG->dirroot.'/repository/lib.php');
global $PAGE, $USER;

//this doesnt seem to work here. So had to put an echo "...embed-comressed.jpg" code below
//$PAGE->requires->js(new moodle_url($CFG->httpswwwroot . '/filter/poodll/flash/embed-compressed.js'),true);

// we get the request parameters:
// the repository ID controls where the file will be added
$repo_id = required_param('repo_id', PARAM_INT); // repository ID
$filename = optional_param('filename', '', PARAM_TEXT); // filename

// load the repository 
$repo = repository::get_instance($repo_id);
if(empty($repo)) {
    die;
}

// we output a simple HTML page with the poodll recorder code in it
//$PAGE->set_generaltype('popup');
$PAGE->set_context(get_context_instance(CONTEXT_USER, $USER->id));
$PAGE->set_url($CFG->wwwroot.'/repository/poodll/record.php', array('repo_id' => $repo_id));
//print_header(null, get_string('recordnew', 'repository_poodll'),null, null, null, false);
?>

<div style="text-align: center;">
<?php if($filename==''){
			echo "<script type=\"text/javascript\" src=\"{$CFG->wwwroot}/filter/poodll/flash/embed-compressed.js\"></script> ";
			$repo->fetch_recorder();
		}else{
			echo 'filename:' . $filename ;
		} 
?>
</div>
<?php
//$OUTPUT->footer();
//print_footer();
