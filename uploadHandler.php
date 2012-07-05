<?php
require_once("../../config.php");



//if receiving a file write it to temp
if(isset($GLOBALS["HTTP_RAW_POST_DATA"])) {
	$filename = date("Y-m-d_H_i_s", time())."_".uniqid(mt_rand(), true).".jpg";
	file_put_contents($CFG->dataroot . '/temp/download/' . $filename ,$GLOBALS["HTTP_RAW_POST_DATA"] );
	//tell our widget what the filename we made up is 
	echo $filename; 
	
//if sending a file force download
}else{
	
	$filename = optional_param('filename', "", PARAM_TEXT);
	$fullPath=$CFG->dataroot . '/temp/download/' . $filename;
	if ($fd = fopen ($fullPath, "r")) {
    $fsize = filesize($fullPath);
    $path_parts = pathinfo($fullPath);
    $ext = strtolower($path_parts["extension"]);
    switch ($ext) {
        case "jpg":
        header("Content-type: image/jpeg"); // add here more headers for diff. extensions
        header("Content-Disposition: attachment; filename=\"".$path_parts["basename"]."\""); // use 'attachment' to force a download
        break;
        default;
        header("Content-type: application/octet-stream");
        header("Content-Disposition: filename=\"".$path_parts["basename"]."\"");
    }
    header("Content-length: $fsize");
    header("Cache-control: private"); //use this to open files directly
    while(!feof($fd)) {
        $buffer = fread($fd, 2048);
        echo $buffer;
    }
}
fclose ($fd);
exit;
}

?>