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
$changeTime = set_time_limit($timeLimit);

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
$imageFile = substr($urlLocation,strpos($urlLocation,"://")+3);
$imageFile = str_replace(array("/","(",")"),array("_","\(","\)"),$imageFile);
if(substr($imageFile,0,1) == "_") {
	$imageFile = substr($imageFile,1);
}
if(substr($urlLocation,-4) == ".pdf" || substr($urlLocation,-4) == ".rss" || substr($urlLocation,-4) == ".xml") {
	$output = "Current page: " . $urlLocation . "\n" . "URL is a PDF, RSS or XML file - no snapshot taking place" . "\n";
} else {
	$output = shell_exec("node puppeteer-screenshots.js -w 1920 --url=" . str_replace(array("(",")"),array("\(","\)"),$urlLocation) . " -p=" . $projectPath . "/" . $reference . "/ -f=" . $imageFile);
}

echo $output;
?>