<?php
	
// Grab folder and clean up to ensure it's not breaking out of the root domain
$folder = $_REQUEST['folder'];
while(substr($folder,0,1) == '/' || substr($folder,0,1) == '.') {
	$folder = substr($folder,1);
}

$path = $_REQUEST['path'];
while(substr($path,0,1) == '/' || substr($path,0,1) == '.') {
	$path = substr($path,1);
}
$fullPath = "";
if($path != "") {
	$fullPath .= $path . "/";
}
$fullPath .= $folder;


if(isset($_REQUEST['compare'])) {
	$compare = $_REQUEST['compare'];
	while(substr($compare,0,1) == '/' || substr($compare,0,1) == '.') {
		$compare = substr($compare,1);
	}
}


// Grab the tier
$tier = $_REQUEST['tier'];

if($tier != 4) {
	// We're grabbing folder lists

	// Initialise output
	$output = "<select id=\"$folder\" class=\"tier$tier\" data-path=\"$fullPath\" data-tier=\"$tier\">";

	// Grab the file list
	$fileList = scandir($fullPath);
	
	if($tier == 1) {
		$output .= "<option>Select a site</option>";
	} elseif ($tier == 2) {
		$output .= "<option>Select original site version</option>";
	} else {
		$output .= "<option>Select comparison site version</option>";
	}
	foreach($fileList as $file) {
		if(substr($file, 0, 1) != ".") {
			$output .= "<option>" . $file . "</option>";
			}
	}
} else {
	// We're up to grabbing the image list

	// Sort out the two file paths
	$fullPath1 = $fullPath;
	$fullPath2 = $path . "/" . $compare;
	
	// Initialise output
	$output = "<select id=\"$folder\" class=\"tier$tier\" data-path1=\"$fullPath1\" data-path2=\"$fullPath2\">";
	$output .= "<option>Please select a file to compare</option>";
	
	$fileList = array();

	$fileList1 = scandir($fullPath1);
	$fileList2 = scandir($fullPath2);
	
	// Check if a file exists in both lists
	// Iterate through list one
	// If it has a list two match, mark it as matched and remove it from list two
	// If it doesn't, mark it as list A only
	foreach($fileList1 as $file) {
		if(in_array($file, $fileList2)) {
			if(substr($file,0,1) != ".") {
				$fileList[] = "[Matched] " . $file;
			}
			unset($fileList2[array_search($file,$fileList2)]);
		} else {
			$fileList[] = "[List A only] " . $file;
		}
	}
	
	// Grab the left overs from list two and mark them as list B only
	foreach($fileList2 as $file) {
		$fileList[] = "[List B only] " . $file;
	}
	
	// Sort the array and output the files
	sort($fileList);
	foreach($fileList as $file) {
		$output .= "<option>" . $file . "</option>";
	}
		
}

$output .= '</select>';

echo $output;

return;
	
?>