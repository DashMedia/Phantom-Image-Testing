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
*/


// Turn down error reporting and look to get the output showing immediately
error_reporting(E_ERROR);
ob_implicit_flush(true);

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
echo "\n\n";
echo "Script:       " . $argv[0] . "\n";
echo "URL passed:   " . $argv[1] . "\n";
echo "Ref passed:   " . $argv[2] . "\n\n";
echo "Root path:    " . $rootPath . "\n";
echo "Site URL:     " . $siteURL . "\n";
echo "Project path: " . $projectPath . "\n\n";

// Loop through the URLs and make the shell call to run the phantomJS render

// Initialise tracking variables
$count = 0;
$totalURLs = count($siteMapObject->url);
$startTime = time();

foreach($siteMapObject->url as $url) {
	$count++;
	
	// Run the phantomjs call on a delay to let all assets come through
	$imageFile = substr($url->loc,strpos($siteURL,"://")+3);
	$imageFile = str_replace(array("/","(",")"),array("_","\(","\)"),$imageFile);
	if(substr($imageFile,0,1) == "_") {
		$imageFile = substr($imageFile,1);
	}

	$output = "[$count of " . count($siteMapObject->url) . "] " . shell_exec("./phantomjs --disk-cache=true pageRender.js " . str_replace(array("(",")"),array("\(","\)"),$url->loc) . " " . $projectPath . "/" . $reference . "/" . $imageFile . " 0");

	
	// Perform some time reporting based on whether or not it's the last run
	$currentTime = time();
	$runTime = $currentTime-$startTime;
	if($count != $totalURLs) {
		$averageTime = $runTime/$count;
		$forecastRunTime = ($runTime/$count)*($totalURLs-$count);
		$forecastFinishTime = time() + $forecastRunTime;
	
		$output .= "Currently running for " . convertSecondsToHMS($runTime) . ". Averaging " . convertSecondsToHMS(round($averageTime)) . " per page. At this rate the site render is expected to finish in " . convertSecondsToHMS(round($forecastRunTime)) . " (at " . date("g:i:sa", $forecastFinishTime) . ")\n\n";
	} else {
		$output .= "\n\nAll done - total run time for $totalURLs pages was " . convertSecondsToHMS($runTime) . " with an average convert time of " . convertSecondsToHMS(round($runTime/$totalURLs)) . ".";
	}
	
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
