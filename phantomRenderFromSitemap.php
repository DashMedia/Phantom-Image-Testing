<?php
/* phantom Render from Sitemap

	Script designed to be run at the OSX command line to grab the sitemap.xml from
	a provided website and use PhantomJS to create image snapshots of each page 
	listed in the site map.
	
	Will automatically create subfolders based on the URL and reference.
	
	Requires phantomjs executable to be in the same folder as this is run (available at phantomjs.org).
	
	Use: php phantomRenderFromSitemap.php [site URL] [reference]
	
	e.g. php phantomRenderFromSitemap.php https://dash.marketing/ 2.5.2
	
	URL and Reference must contain no spaces.
	
	
	Written by Josh Curtis for Dash Media

	17 May 2017 - initial creation
	24 May 2017 - added multithreading of screen shot calls
*/

// Configuration
// How many threads to have running on the site - keep in mind his will hit the site in question this many times simultaneously at startup
$numberOfThreads = 10;

// How long should the script wait for a return from the screen shot (in milliseconds - 10s = 10000). Note that this won't kill the phantomJS process - just the chance of a valid return from the process calling it.
$threadTimeOut = 300000;


// Turn down error reporting and look to get the output showing immediately
error_reporting(E_ERROR);
ob_implicit_flush(true);

require('rollingcurlx.class.php');

// Initialise variables
$rootPath = getcwd();
$siteURL = trim($argv[1]);
$reference = trim($argv[2]);
$sitemapLocation = "sitemap.xml";

// Add a trailing slash to the URL if it doesn't have one
if(substr($siteURL,-1) != "/") {
	$siteURL .= "/";
}

// Check we have all required arguments
if($siteURL == "" || $reference == "") {
	exit ("\n\nFAILED: please check usage:\n\nphp phantomRenderFromSitemap.php [site URL] [reference]\n\ne.g. php phantomSiteCompare.php https://dashmedia.marketing/ 170508\n\n\n\n\n");
}

// Get the sitemap - if it fails exit with an error
$siteMap = file_get_contents($siteURL . $sitemapLocation)
	or exit("\n\nFAILED: couldn't retrieve site map at $siteURL$sitemapLocation\nPlease check the URL exists and try again.\n\n\n\n\n");


// We have all arguments and a sitemap - let's roll

// Check/create folder structure for image output
$projectPath = "images/" . str_replace("/","",substr($siteURL,strpos($siteURL,"://")+3));
if(!is_dir($projectPath."/".$reference)) {
	mkdir($projectPath."/".$reference,0777,TRUE);
}

// Convert the XML to an object
$siteMapObject = simplexml_load_string($siteMap);

// Output reference variables
//$output = shell_exec('ls -lart');
$output = "\n\n" .
	"Script:       " . $argv[0] . "\n" .
	"URL passed:   " . $argv[1] . "\n" .
	"Ref passed:   " . $argv[2] . "\n\n" .
	"Root path:    " . $rootPath . "\n" .
	"Site URL:     " . $siteURL . "\n" .
	"Project path: " . $projectPath . "\n\n";

echo $output;

// Prepare the log file
$logFile = $projectPath . "/" . $reference . "/" . str_replace("/","",substr($siteURL,strpos($siteURL,"://")+3)). "-" . $reference . ".log.txt";
file_put_contents($logFile, $output, FILE_APPEND);

// Loop through the URLs and make the shell call to run the phantomJS render

// Initialise tracking variables
$count = 0;
$totalURLs = count($siteMapObject->url);
$startTime = time();

// Prepare the multithreader
$RCX = new RollingCurlX($numberOfThreads);
$RCX->setTimeout($threadTimeOut);

foreach($siteMapObject->url as $url) {
	$count++;
	
	$urlCall = "http://localhost/phantomCaller.php";
	$post_data = [
		'urlLocation' => urlencode($url->loc),
		'siteURL' => urlencode($siteURL),
		'count' => $count,
		'totalURLs' => $totalURLs,
		'projectPath' => $projectPath,
		'reference' => $reference,
		'startTime' => $startTime
	];
	$options = [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FAILONERROR => true,
		CURLOPT_RETURNTRANSFER => true,
	];

	$RCX->addRequest($urlCall, $post_data, 'returningOfficer', $user_data, $options, $headers);

}

$returnCounter = 0;
$total = count($siteMapObject->url);

file_put_contents($logFile, "Just started ordering screenshots. Total of $total shots requested $numberOfThreads at a time.\n\n");
echo "Just started ordering screenshots. Total of $total shots requested $numberOfThreads at a time.\n\n\n";
flush();

$RCX->execute();

function returningOfficer($response, $url, $request_info, $user_data, $time) {
/*	echo "Response    : $response\n";
	echo "URL         : $url\n";
	echo "Request info: <pre>";
	print_r($request_info);
	echo "\n";
	echo "User data   : $user_data\n";
	echo "Time        : $time\n\n"; */
	
	global $returnCounter,$totalURLs,$startTime,$logFile;
	
	$returnCounter++;

	// Perform some time reporting based on whether or not it's the last run
	$currentTime = time();
	$runTime = $currentTime-$startTime;

	$averageTime = $runTime/$returnCounter;
	$forecastRunTime = ($runTime/$returnCounter)*($totalURLs-$returnCounter);
	$forecastFinishTime = time() + $forecastRunTime;

	// Perform some time/status reporting based on whether or not it's the last run
	$output = "[$returnCounter of $totalURLs] ";
	$output .= $response;

	if($returnCounter == $totalURLs) {
		$output .= "\n\nAll done - total run time for $totalURLs pages was " . convertSecondsToHMS($runTime) . " with an average convert time of " . convertSecondsToHMS(round($runTime/$totalURLs)) . ".";
	} else {
		$output .= "Currently running for " . convertSecondsToHMS($runTime) . ". Averaging " . convertSecondsToHMS(round($averageTime)) . " per page. At this rate the site render is expected to finish in " . convertSecondsToHMS(round($forecastRunTime)) . " (at " . date("g:i:sa", $forecastFinishTime) . ")\n\n";
	}

	file_put_contents($logFile, $response.$output, FILE_APPEND);
	
	echo $output;
	flush();
}

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


//echo "Site map:\n" . $siteMap;

echo "\n\n\n\n\n";
?>
