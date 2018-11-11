#!/usr/bin/php
<?php
/* Render screenshots from Sitemap

	Script designed to be run at the OSX command line to grab the sitemap.xml from
	a provided website and use Google's Puppeteer to create image snapshots of each page 
	listed in the site map.
	
	Will automatically create subfolders based on the URL and reference.
	
	Use: ./renderFromSitemap.php -s[site URL] -r[reference (optional)] -a[alternative remote sitemap file name (optional)] -l[local sitemap file name (optional)] -t[number of concurrent capture threads (optional)] -q[max number of screenshots (optional)]
	
	-s[siteURL] is required - the script will fail without it
	-r[reference] is optional - will be replaced with a timestamp if not supplied
	-a[alternative remote sitemap file name] is optional - the script will default to /sitemap.xml if not supplied
	-l[local sitemap file name] is optional - if present this will be used instead of the remote site map
	-t[integer number] is optional - if present will be the number of simultaneous threads of Chromium screen captures
	-q[integer number] is optional - if present will limit the total number of screenshots as follows.
		If the total number of pages in the sitemap is more than n^2, every nth page will be snapshotted.
		If the total number of pages in the sitemap is less than n^2, n will be the number of screens captured, spread evenly across the sitemap list.
		(If the total number of pages in the sitemap is less than or equal to n, every page will be captured.)
	
	e.g. ./renderFromSitemap.php -shttps://dash.marketing/ -r2.5.2 -ldash.sitemap.xml -t4 -q10

	Would use the local sitemap dash.sitemap.xml to capture screenshots of 4 pages at a time 
	from https://dash.marketing/ and place them in a created folder at ./images/dash.marketing/2.5.2 
	
	
	Written by Josh Curtis for Dash Media

	17 May 2017 - initial creation
	24 May 2017 - added multithreading of screen shot calls
	25 Oct 2017 - added the option to have a local sitemap after running into problems with Wordpress forbidding sitemap access
	6 Nov 2017 - made over to use Google's Puppeteer instead of the previous phantomJS and better handle command line variables
	20 Jul 2018 - add 'quick' option which limits the number of screenshots being captured to a hard limit. Good for fast checks.
*/


//-- Configuration --//

// Default time zone
date_default_timezone_set("Australia/Adelaide");

// How long should the script wait for a return from the screen shot (in milliseconds - 10s = 10000). Note that this won't kill the Chrome process - just the chance of a valid return from the process calling it.
$threadTimeOut = 130000;

// How many simultaneous threads should be the default?
$defaultThreads = 3;

// How many simultaneous threads should be the most allowed?
$maxThreads = 8;

//-- End Configuration --//


$options = getopt("s:r::l::t::a::q::");

if(!isset($options["s"])) {
	exit ("\n\nFAILED: please check usage:\n\n./renderFromSitemap.php -s[site URL] -r[reference (optional)] -l[local sitemap file (optional)] -t[number of threads (optional)] -a[alternate sitemap URL. Optional. sitemap.xml used if not defined]\n\ne.g. ./renderFromSitemap.php -shttps://dashmedia.marketing/ -r171120-2.6.0 -t4\n\n\n\n\n");
}


// Initialise variables
$rootPath = getcwd();
$siteURL = $options["s"];
$reference = isset($options["r"]) ? $options["r"] : date("Ymd-His");
$sitemapLocation = isset($options["a"]) ? $options["a"] : "sitemap.xml";
$sitemapLocal = isset($options["l"]) ? $options["l"] : false;
$numberOfThreads = isset($options["t"]) ? intval($options["t"]) : $defaultThreads;

$quickMode = isset($options["q"]) ? intval($options["q"]) : 0;


// QC on number of threads to check it's not too low or too high
if($numberOfThreads <= 0) {
	echo "\n\nNot a valid number of threads.\nSetting number of threads to the default of $defaultThreads.\n\n";
	$numberOfThreads = $defaultThreads;
}
if($numberOfThreads > $maxThreads) {
	echo "\n\n$numberOfThreads threads is above the hardcoded limit of $maxThreads.\nSetting number of threads to the default of $defaultThreads.\nThe maximum number of threads can be changed in renderFromSitemap.php in the 'configuration' section.\n\n";
	$numberOfThreads = $defaultThreads;	
}


// Turn down error reporting and look to get the output showing immediately
//error_reporting(E_ERROR);
ob_implicit_flush(true);
// Initialise output
echo "\n\n";


// Initialise queue manager
require('rollingcurlx.class.php');

//-- Function for display of XML Errors if there any in the sitemap --//
function display_xml_error($error, $xml) {
	$return  = $xml[$error->line - 1] . "\n";
	$return .= str_repeat('-', $error->column) . "^\n";

	switch ($error->level) {
		case LIBXML_ERR_WARNING:
			$return .= "Warning $error->code: ";
			break;
		case LIBXML_ERR_ERROR:
			$return .= "Error $error->code: ";
			break;
		case LIBXML_ERR_FATAL:
			$return .= "Fatal Error $error->code: ";
			break;
	}

	$return .= trim($error->message) . "\n  Line: $error->line" . "\n  Column: $error->column";

	if ($error->file) {
		$return .= "\n  File: $error->file";
	}

	return "$return\n\n--------------------------------------------\n\n";
}
//-- End function for display of XML Errors if there any in the sitemap --//


// Add a trailing slash to the URL if it doesn't have one
if(substr($siteURL,-1) != "/") {
	$siteURL .= "/";
}

// Get the sitemap in array urlList - if it fails exit with an error
$urlList = array();

if($sitemapLocal != "") {
	getSiteMapList($sitemapLocal);
} else {
	getSiteMapList($siteURL . $sitemapLocation);
}

echo "URL List Count: " . count($urlList);

// Convert the XML to an object
// Spit errors if it fails
// Return list of URLs in array form if it succeeds
function getSiteMapList($location) {
	global $urlList;
	
	// Echo file location (for debugging)
	//echo "In getSiteMapList function using $location\n\n";
	
	// Pretend to be Chrome to trick hosts using lack of user agent to block access
	$context = stream_context_create(
    	array(
        	"http" => array(
            	"header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
			)
		)
	);

	// Ask for the site map
	$siteMap = file_get_contents($location, false, $context)
		or exit("\n\nFAILED: couldn't retrieve site map at $location\nPlease check the URL exists and try again.\n\n\n\n\n");
	
	libxml_use_internal_errors(true);
	$siteMapObject = simplexml_load_string(trim($siteMap));

	if ($siteMapObject === false) {
    	$errors = libxml_get_errors();

		foreach ($errors as $error) {
        	echo display_xml_error($error, $xml);
    	}

		libxml_clear_errors();
	} else {
		
		// Print the sitemap object (debugging)
		//print_r($siteMapObject);
		
		// Check if URLs exist in this file
		if(count($siteMapObject->url) > 0) {

			foreach($siteMapObject->url as $url) {
				$urlList[] = $url->loc;
				
				// Echo URLList count and URL (for debugging)
				echo count($urlList) . " - " . $url->loc . "\n";
			}
		
		// Else check if sitemaps exist in this file
		} else if (count($siteMapObject->sitemap) > 0) {
			
			// Echo the number of sitemaps (for debugging)
			// echo "I see " . count($siteMapObject->sitemap) . " sitemaps\n";
			
			foreach($siteMapObject->sitemap as $sitemap) {
				getSiteMapList($sitemap->loc);
			}
			
		}		

		return;
	}
}


// We have all arguments and a sitemap - let's roll

// Check/create folder structure for image output
$projectPath = "images/" . str_replace("/","",substr($siteURL,strpos($siteURL,"://")+3));
if(!is_dir($projectPath."/".$reference)) {
	mkdir($projectPath."/".$reference,0777,TRUE);
}
if(!is_dir($projectPath."/".$reference."/code")) {
	mkdir($projectPath."/".$reference."/code",0777,TRUE);
}

// Output reference variables
//$output = shell_exec('ls -lart');
$output = "\n\n" .
	"Script:       " . $argv[0] . "\n" .
	"Root path:    " . $rootPath . "\n\n" .
	"Site URL:     " . $siteURL . "\n" .
	"Reference:    " . $reference . "\n" .
	"Project path: " . $projectPath . "\n\n";

echo $output;

// Prepare the log file
$logFile = $projectPath . "/" . $reference . "/" . str_replace("/","",substr($siteURL,strpos($siteURL,"://")+3)). "-" . $reference . ".log.txt";
file_put_contents($logFile, $output, FILE_APPEND);


// Loop through the URLs and make the shell call to run the Chromium render

// Get number of URLs
$totalURLs = count($urlList);

// Adjust total URLs calculations for quick mode
if($quickMode != 0) {
	if($totalURLs > $quickMode) {
		if($totalURLs > $quickMode*$quickMode) {
			$everyN = $quickMode;
		} else {
			$everyN = $totalURLs/$quickMode;
		}
	} else {
		$quickMode = 0;
	}
}

// Convert quickmode to an array of items to grab
if ($quickMode != 0) {
	$pagesToGrab = array();
	for($i=0;$i < $totalURLs;$i = $i + $everyN) {
		$pagesToGrab[] = round($i);
	}
	$originalURLs = $totalURLs;
	$totalURLs = count($pagesToGrab);
}

$startTime = time();
// Prepare the multithreader
$RCX = new RollingCurlX($numberOfThreads);
$RCX->setTimeout($threadTimeOut);

$grabbed = 0;
$count = 0;
foreach($urlList as $url) {
	
	if($quickMode == 0 || in_array($count,$pagesToGrab)) {
		$grabbed ++;
//		echo "$grabbed: Grabbing $count\n";

		$urlCall = "http://localhost/chromeCaller.php";
		$post_data = [
			'urlLocation' => urlencode($url),
			'siteURL' => urlencode($siteURL),
			'count' => $count+1,
			'totalURLs' => $totalURLs,
			'projectPath' => $projectPath,
			'reference' => $reference,
			'startTime' => $startTime,
			'timeLimit' => $threadTimeOut
		];
		$options = [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FAILONERROR => true,
			CURLOPT_RETURNTRANSFER => true,
		];

		$RCX->addRequest($urlCall, $post_data, 'returningOfficer', $user_data, $options, $headers);
	}
	$count++;
}

//echo "QM:$quickMode\n";

// Prepare to count the returns
$returnCounter = 0;
$total = $totalURLs;

// Prepare output start
$initialisationStatement = "Started ordering screenshots. ";
if($total < $numberOfThreads) {
	$initialisationStatement .= "Total of $total shot" . ($total > 1 ? "s" : "") . " requested.\n";
} else {
	$initialisationStatement .= "Total of $total shot" . ($total > 1 ? "s" : "") . " requested $numberOfThreads at a time.\n";
}
if($quickMode != 0) { $initialisationStatement .= "(Called in quick mode for reduced number of screenshots - reduced from $originalURLs to $totalURLs)"; }
$initialisationStatement .= "\n";

file_put_contents($logFile, $initialisationStatement);
echo $initialisationStatement . "\n";
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
	
	global $returnCounter,$totalURLs,$startTime,$logFile,$projectPath,$reference;
	
	$returnCounter++;
	
	$stockPath = $projectPath . "/" . $reference . "/";

	$stockFile = substr($response,strpos($response,"://")+3);
	$stockFile = str_replace(array("/","(",")"),array("_","\(","\)"),$stockFile);
	$stockFile = preg_replace("/[^A-Za-z0-9\-_.]/", "", $stockFile);
	if(substr($stockFile,0,1) == "_") {
		$stockFile = substr($stockFile,1);
	}

	if(file_exists($stockPath . $stockFile . ".st.png")) {
		unlink($stockPath . $stockFile . ".st.png");
//		echo "Exists - nuking";
	} else {
//		echo "No exists";
	}

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
		// It's the end of the run
		$output .= "\n\nAll done - total run time for $totalURLs pages was " . convertSecondsToHMS($runTime) . " with an average convert time of " . convertSecondsToHMS(round($runTime/$totalURLs)) . ".\n\n\n";
		
		// Grab a list of all .st.png files (residuals of screenshots that didn't beat the 30s timer
		$zombieFiles = glob($stockPath."*.st.png");
		$zombieFileCount = count($zombieFiles);
		
		// Report back on the status of the zombies and whether they ultimately finished or not		
		$output .= "There " . ($zombieFileCount == 1 ? "was " : "were ") . ($zombieFileCount > 0 ? $zombieFileCount . " screenshot" . ($zombieFileCount != 1 ? "s" : "") : "no screenshots") . " that didn't complete within 30 seconds\n\n";
		if(count($zombieFiles) > 0) {
			// Initialise array for counting properly failed files
			$deadFiles = array();
			$output .= "The file" . ($zombieFileCount == 1 ? " was" : "s were") . ":\n";
			foreach($zombieFiles as $zombieFile) {
				$output .= str_replace(".st.png",".png",$zombieFile) . "\n";
				$undeadFile = glob(str_replace(".st.png",".png",$zombieFile));
				if(count($undeadFile) > 0) {
					$output .= "But this file finished after the 30s window\n";
				} else {
					$output .= "And it appears this file failed\n";
					$deadFiles[] = $zombieFile;
				}
				// Remove the zombie file
				if(!unlink($zombieFile)) {
					$output .= "FYI: I was unable to delete the zombie file\n";
				} else {
//					$output .= "Zombie file deleted\n";
				}
				$output .= "\n";
			}
		}
		$output .= "\n";
		
		// If there's truly dead files, create a new local sitemap to work from
		if(count($deadFiles) > 0) {
			$sitemapString = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
			foreach($deadFiles as $deadFile) {
				$urlFromFile = "https://" . substr($deadFile,strrpos($deadFile,"/")+1);
				$urlFromFile = substr($urlFromFile,0,-7);
				$urlFromFile = str_replace("_", "/", $urlFromFile);
				$sitemapString .= "<url><loc>" . $urlFromFile . "</loc></url>\n";
			}
			$sitemapString .= '</urlset>';

			// Write the sitemap file
			$localSitemapFileName = substr($deadFile,strrpos($deadFile,"/")+1);
			$localSitemapFileName = substr($localSitemapFileName,0,strpos($localSitemapFileName,"_"));
			$localSitemapFileName .= "-" . date("ymdHis") . ".xml";
			file_put_contents($localSitemapFileName, $sitemapString);
			
			$output .= "A local sitemap has been written to " . $localSitemapFileName . " which can be used to run the script again for just the unfinished URLs.\n\nNote that Chromium may still have processes running (stalled on these URLs) which you'll need to kill manually.\n\n";
		}
		
		// If the equivalent finished files don't exist, check the time stamp on the placeholder - if it's been more than a minute, add it to the failed list (keep a count too)
		// If there's any left at the end of that run, pause for the amount of time until a minute has passed and check again



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

// Finalise output
echo "\n\n\n";
?>
