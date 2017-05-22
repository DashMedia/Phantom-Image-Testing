# Phantom-Image-Testing
PhantonJS diff tool comparing websites.

Script designed/tested to be run at the OSX command line to grab the sitemap.xml from a provided website URL and use PhantomJS to create image snapshots of each page listed in the site map. Then index.php is designed to be loaded at localhost to compare screenshots of two different site snapshots side by side.

## Installation
Place files in web root (/var/www under OSX).

Requires phantomjs executable to be in the same folder (available at phantomjs.org).

## Typical workflow
### Make screen shots
To make screen shots of a specific site, enter your command line and run the following:
> php phantomRenderFromSitemap.php [site URL] [arbitrary reference]
e.g. php phantomRenderFromSitemap.php https://dash.marketing/ 170101-2.5.2
	
URL and Reference must contain no spaces. The script will automatically create subfolders based on the URL and reference to house the images its generating.

Perform the changes you have planned to the site.

Run the screenshot script again, usually for the same site URL but with a different arbitrary reference.

### Compare versions
Load index.php at the localhost. Select your project from the the first select box (listed by URL).

Then select the first of the two versions you wish to compare. A third select box becomes available to choose the comparison version.

A final select box will show where you can select the images you wish to compare (named for their URLs in the site you took snapshots of). If an item is listed as [Matched] it means that the file (and therefore URL) exists in both versions. If it's listed as [List A only] or [List B only], the file has only appeared in the first-selected or second-selected version of screen shots respectively.

When you select a file image from the final select box, it will load and the browser will calculate a differential image and display a summary message about the percentage difference between the two images.


## Credits/history
Written by Josh Curtis for Dash Media
  17 May 2017 - initial creation
  22 May 2017 - updates to screen shot and comparison code pages
  
 ## Roadmap
- [ ] Multithreading of screen shot process to speed up the process
