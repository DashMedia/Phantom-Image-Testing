# Puppeteer image testing
A set of tools to perform website screen shots via Chrome and the node Puppeteer API and then compare screen shot sets using an image diff tool.

Script designed/tested to be run at the OSX command line to grab the sitemap.xml from a provided website URL and use Puppeteer to create image snapshots of each page listed in the site map. Then index.php is designed to be loaded at localhost to compare screenshots of two different site snapshots side by side.

## Installation
Place files in web root (/var/www under OSX).

Requires node.js to be installed (available at https://nodejs.org/en/download/).

Requires the Puppeteer node package. Run `npm i puppeteer` at the command line.

Requres rollingcurlx.class.php to be in the same folder (available at https://github.com/marcushat/rollingcurlx in src folder)

## Typical workflow
### Make screen shots
To make screen shots of a specific site, enter your command line and run the following:
> ./renderFromSitemap.php -s[site URL] -r[reference]

e.g. ./renderFromSitemap.php -shttps://dash.marketing/ -r170101-2.5.2
	
URL and Reference must contain no spaces. The script will automatically create subfolders based on the URL and reference to house the images its generating. The site must have a list of pages available at /sitemap.xml. A log of the screenshot process along with timing report will be saved to the folder along with the images.

Perform the changes you have planned to the site.

Run the screenshot script again, usually for the same site URL but with a different arbitrary reference.

### Compare versions
Load index.php at the localhost. Select your project from the the first select box (listed by URL).

Then select the first of the two versions you wish to compare. A third select box becomes available to choose the comparison version.

A final select box will show where you can select the images you wish to compare (named for their URLs in the site you took snapshots of).

If an item is listed as [Matched] it means that the file (and therefore URL) exists in both versions. If it's listed as [List A only] or [List B only], the file has only appeared in the first-selected or second-selected version of screen shots respectively.

When you select a file image from the final select box, it will load those images side by side and the browser will calculate a differential image and display a summary message about the percentage difference between the two images.


## Credits/history
Written by Josh Curtis for Dash Media
- 17 May 2017 - initial creation
- 22 May 2017 - updates to screen shot and comparison code pages
- 24 May 2017 - added multithreading of screen shots and saving of log file with the image screen shots
- 6 Nov 2017 - refactored to use Chromium via Puppeteer instead of phantomJS and accept local sitemaps and alternative remote sitemap URLs
  
 ## Roadmap/possible improvements
- [ ] Preloading of image comparisons rather than selecting from a list
- [ ] Refactoring to allow for multiple widths to be provided via PHP
