# Phantom-Image-Testing
PhantonJS diff tool comparing websites.

Script designed/tested to be run at the OSX command line to grab the sitemap.xml from a provided website URL and use PhantomJS to create image snapshots of each page listed in the site map.
	
Requires phantomjs executable to be in the same folder as this is run (available at phantomjs.org).
	
Use: php phantomRenderFromSitemap.php [site URL] [reference]
	
e.g. php phantomRenderFromSitemap.php https://dash.marketing/ 2.5.2
	
URL and Reference must contain no spaces. The script will automatically create subfolders based on the URL and reference to house the images its generating.

Written by Josh Curtis for Dash Media
  17 May 2017 - initial creation
