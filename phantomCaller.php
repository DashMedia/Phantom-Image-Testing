<?php
// Makes the call to run the snapshot. Done from a separate PHP so we can multithread via curl_multi.

// Prepare variables for run
$urlLocation = urldecode($_REQUEST['urlLocation']);
$siteURL = urldecode($_REQUEST['siteURL']);
$count = $_REQUEST['count'];
$projectPath = $_REQUEST['projectPath'];
$reference = $_REQUEST['reference'];
$startTime = $_REQUEST['startTime'];
$totalURLs = $_REQUEST['totalURLs'];
$timeLimit = $_REQUEST['timeLimit'];

$timeLimit = intval($timeLimit/1000);
set_time_limit($timeLimit);

// Functions required
function convertSecondsToHMS($seconds) {
	$hours = 0;
	$minutes = 0;
	
	while ($seconds > 3600) {
		$hours++;
		$seconds = $seconds-3600;
	}
	
	while ($seconds > 60) {
		$minutes++;
		$seconds = $seconds - 60;
	}
	
	$time = "";
	if ($hours > 0) {
		$time .= $hours . "h ";
	}
	if ($minutes > 0) {
		$time .= $minutes . "m ";
	}
	$time .= $seconds . "s";
	
	return $time;
}

	
// Run the phantomjs call on a delay to let all assets come through
$imageFile = substr($urlLocation,strpos($siteURL,"://")+3);
$imageFile = str_replace(array("/","(",")"),array("_","\(","\)"),$imageFile);
if(substr($imageFile,0,1) == "_") {
	$imageFile = substr($imageFile,1);
}
$output = shell_exec("./phantomjs --disk-cache=true pageRender.js " . str_replace(array("(",")"),array("\(","\)"),$urlLocation) . " " . $projectPath . "/" . $reference . "/" . $imageFile . " 0");
	
echo $output;
?>