<?php
ini_set('max_execution_time', 300);
ini_set('default_socket_timeout', 300);
ini_set('display_errors', 0);

$logFile = 'diffBuildLog.txt';
	
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

if($tier == "") {
	echo "Tier level not set";
	return;
}

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
		if(substr($file, 0, 1) != "." && substr($file,0,6) != "Diffs.") {
			$output .= "<option>" . $file . "</option>";
		}
	}
} else {
	// We're up to grabbing the image list

	// Sort out the two file paths
	$fullPath1 = $folder . "/" . $path;
	$fullPath2 = $folder . "/" . $compare;
	
	// Initialise output
//	$output = "<select id=\"$folder\" class=\"tier$tier\" data-path1=\"$fullPath1\" data-path2=\"$fullPath2\">";
//	$output .= "<option>Please select a file to compare</option>";
	
	$fileList = array();

	$fileList1 = scandir($fullPath1);
	$fileList2 = scandir($fullPath2);
	
	// Check if a file exists in both lists
	// Iterate through list one
	// If it has a list two match, mark it as matched and remove it from list two
	// If it doesn't, mark it as list A only
	$matchedCount = 0;
	foreach($fileList1 as $file) {
		if(substr($file,-3) == "png") {
			if(in_array($file, $fileList2)) {
				$fileList[] = "[Matched] " . $file;
				unset($fileList2[array_search($file,$fileList2)]);
				$matchedCount++;
			} else {
				$fileList[] = "[List A only] " . $file;
			}
		}
	}
	
	// Grab the left overs from list two and mark them as list B only
	foreach($fileList2 as $file) {
		if(substr($file,-3) == "png") {
			$fileList[] = "[List B only] " . $file;
		}
	}
	
	// Create the folder for diff output if required
	if($fullPath1 != $fullPath2 && $fullPath1 != "" && $fullPath2 != "") {
		file_put_contents("diffGenerationStatus.log","0 of " . $matchedCount . " - 0%");
		foreach($fileList as $file) {
			if(strpos($file,'[Matched]') !== null) {
				$diffsPath = substr($fullPath1,0,strrpos($fullPath1,"/")) . "/Diffs." . substr($fullPath1,strrpos($fullPath1,"/")+1) . "vs" . substr($fullPath2,strrpos($fullPath2,"/")+1);
				if(!is_dir($diffsPath)) {
					mkdir($diffsPath,0777,TRUE);
				}
				
				// Set variables ready for checking
				$fileName = substr($file,strpos($file,']')+2);
				$sanitisedFileName = str_replace(array("(",")"),array("\(","\)"),$fileName);
				$fullPath1E = $_SERVER['DOCUMENT_ROOT'] . "/" . $fullPath1 . "/";
				$fullPath2E = $_SERVER['DOCUMENT_ROOT'] . "/" . $fullPath2 . "/";
	
				// Check if the diff already exists and if not, create it
				if(!file_exists($_SERVER['DOCUMENT_ROOT'] . "/" .$diffsPath."/".$fileName)) {
						
					// Check the file sizes and if they're too different, skip the comparison and log via image and text file
					// Get the two image heights
					if(file_exists($fullPath1E.$fileName)) {
						$image1Height = getImageSize($fullPath1E.$fileName)[1];
					} else {
						$image1Height = 0;
					}
					if(file_exists($fullPath2E.$fileName)) {
						$image2Height = getImageSize($fullPath2E.$fileName)[1];
					} else {
						$image2Height = 0;
					}
					
					if($image1Height != $image2Height) {
						// The files aren't the same size
						if($image1Height > $image2Height) {
							$command = "convert ".$fullPath2E.$fileName." -background magenta -extent ".getImageSize($fullPath2E.$fileName)[0]."x".getImageSize($fullPath1E.$fileName)[1]." -gravity North " .  $fullPath2E.$fileName;
						} else {
							$command = "convert ".$fullPath1E.$fileName." -background magenta -extent ".getImageSize($fullPath1E.$fileName)[0]."x".getImageSize($fullPath2E.$fileName)[1]." -gravity North " .  $fullPath1E.$fileName;						
						}
						file_put_contents("diffBuildLog.txt", $command . "\n\n");
						shell_exec($command);
					}

					// Doesn't exist and sizes are OK - create the shell command
					$compareCommand = "compare -metric RMSE -fuzz 8% -highlight-color Magenta -subimage-search ".$fullPath1E.$sanitisedFileName . " " . $fullPath2E.$sanitisedFileName." " . $_SERVER['DOCUMENT_ROOT'] . "/" .$diffsPath."/".$sanitisedFileName;
					
					// Log the compare command
					file_put_contents($logFile,date("Y-m-d H:i:s")." ".$fileName . ": Comparing via: ".$compareCommand."\n",FILE_APPEND);
					
					// Run the compare command
					$diffOutput = shell_exec($compareCommand . " 2>&1");
					file_put_contents($logFile,date("Y-m-d H:i:s")." Diff output:\n".$diffOutput."\n",FILE_APPEND);
					
					if(strpos($diffOutput,"images too dissimilar") != false) {
						// Images are too dissimilar
						copy('Error-ImagesTooDifferent.png',$diffsPath."/".$fileName);
						file_put_contents($diffsPath."/".$fileName.".txt","Percent of image mismatched based on area of magenta found:\ntoo\n",FILE_APPEND);
						file_put_contents($logFile,date("Y-m-d H:i:s")." Too different to compare\n",FILE_APPEND);
					} else {
						// Seems fine: create the diff image
						$diffOutput = shell_exec("convert " . $diffsPath."/".$sanitisedFileName . " -fill black +opaque \"rgb(255,0,255)\" -format %c histogram:info:");
						file_put_contents($logFile,"\n".date("Y-m-d H:i:s")."\nShell exec: ".$diffOutput."\n\n",FILE_APPEND);
					
						// Images are fine
						$diffOutput = explode("\n",$diffOutput);

						$diffOutputFine = trim(substr($diffOutput[0],0,strpos($diffOutput[0],":")));
						$diffOutputBroke = trim(substr($diffOutput[1],0,strpos($diffOutput[1],":")));
						if($diffOutputBroke == "") { $diffOutputBroke = 0; };
					
						file_put_contents($logFile,date("Y-m-d H:i:s")." OK:".$diffOutputFine."|\n",FILE_APPEND);
						file_put_contents($logFile,date("Y-m-d H:i:s")." Broke:".$diffOutputBroke."|\n",FILE_APPEND);
					
						$pcBroken = round($diffOutputBroke/($diffOutputBroke+$diffOutputFine)*100);
						file_put_contents($diffsPath."/".$fileName.".txt","Percent of image mismatched based on area of magenta found:\n".$pcBroken."%\n",FILE_APPEND);
					}					
				} else {
					file_put_contents($logFile,date("Y-m-d H:i:s")." ".$fileName . ": diff already generated - no compare needed\n",FILE_APPEND);
				}
				$completedDiffs = count(glob($_SERVER['DOCUMENT_ROOT'] . "/" . $diffsPath ."/*.png"));
				file_put_contents("diffGenerationStatus.log", $completedDiffs . " of " . $matchedCount . " - " . round(($completedDiffs/$matchedCount)*100) . "%");
			}
			file_put_contents($logFile,"\n",FILE_APPEND);	
		}
	
		// Initialise output
		$output = "<select id=\"$folder\" class=\"tier$tier\" data-path1=\"$fullPath1\" data-path2=\"$fullPath2\" data-path-diffs=\"$diffsPath\">";
		$output .= "<option>Please select a file to compare</option>";
	
		// Sort the array and output the files
		sort($fileList);
		$counter = 0;

		$total = count($fileList);
		foreach($fileList as $file) {
			$fileName = substr($file,strpos($file,"] ")+2);
			$pcBroken = explode("\n",file_get_contents($diffsPath."/".$fileName.".txt"))[1];
			if($pcBroken == "too") {
				$pcBroken = "Incomparable";
			}
			$counter++;
			$output .= "<option data-pc-off=\"";
			if($pcBroken == "Incomparable") {
				$output .= "incomparably";
			} else {
				$output .= $pcBroken;
			}
			$output .= "\" value=\"" . $file . "\">[" . $pcBroken . "] " . $counter . "/" . $total . " " . $file . "</option>";
		}
		$output .= "<option>The end!</option>";
		$output .= '</select>';
	} else {
		$output = "Folders are the same";
		file_put_contents($logFile,date("Y-m-d H:i:s")." Selected paths are the same - no compare needed\n",FILE_APPEND);
	}
	
	file_put_contents($logFile,"\n----------\n\n",FILE_APPEND);
}

echo $output;

return;
	
?>