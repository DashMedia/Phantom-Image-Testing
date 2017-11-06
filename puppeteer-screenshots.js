/** Pre requisites
 * Designed for use on MacOS only
 
 1) install node.js (https://nodejs.org/en/download/)
 
 2) install puppeteer
 	`npm i puppeteer`

 * USAGE:
 * `node pupeteer-screenshots.js -w 1024 -h 768 --url=http://google.com -p=projectPath -f=fileName`
 * `node pupeteer-screenshot.js --widths=1024,768 -h 768 --url=http://google.com`
 * `node pupeteer-screenshot.js --widths=1024,768 --heights=1024,768 --url=http://google.com`
 * `node pupeteer-screenshot.js --widths=1024,768 --heights=1024,768 --urls=http://google.com, http://amazon.com`
 *  myurls.json should be an array of urls
 */
	
const puppeteer = require('puppeteer');
const fs = require('fs');

// Initialise input variables
const argv = require('minimist')(process.argv.slice(2));
const windowWidth = argv.w ? argv.w : 1024;
const windowHeight = argv.h ? argv.h : 100;

let urls = argv.urls ? argv.urls.split(',') : [argv.url];
windowWidths = argv.widths ? argv.widths.split(',') : [windowWidth];
windowHeights = argv.heights ? argv.heights.split(',') : [windowHeight];

p = argv.p ? argv.p : "images/";
f = argv.f ? argv.f : "screenshot";


// Function to perform the screen shot
async function saveScreenShotFromURL(pageURL, windowWidth, windowHeight, p, f) {
	// Open a browser and page and set the screen size
	const browser = await puppeteer.launch({headless:false});
	const page = await browser.newPage();
	await page.setViewport({width: windowWidth, height: windowHeight});  
	
	// Load the URL in question
	await page.goto(pageURL,{timeout:60000,waitUntil:'networkidle',networkIdleInflight:0,networkIdleTimeout:3000});
	await page.setViewport({width: windowWidth, height: (await page.evaluate("Math.max(window.innerHeight, document.body.clientHeight)"))});
	await page.waitFor(1000);
	
	// Grab variables from the page
	const pageTitle = await page.evaluate("document.querySelector('title').textContent");
	const pageHeight = await page.evaluate("document.body.scrollHeight");
	const fullCode = await page.evaluate("document.documentElement.innerHTML");
	
	// Output information
	console.log(`Title of page: ${pageTitle}`);
	console.log(`Height of page: ${pageHeight}`);
	console.log(`Width of page: ` + parseInt(windowWidth));

	// Actually take the screenshot
	const filename = `${p}/${f}.png`;
	await page.screenshot({path: filename});
	
	const codeFilename = `${p}/code/${f}.txt`;
	fs.writeFile(codeFilename,fullCode);

// Optional page reload for troubleshooting
//  console.log("Wait before reload");
//  await page.waitFor(30000);
//  console.log("Reloading");
//  await page.reload();
//  await page.screenshot({path: 'abouthomesnt.com.au_designs_reload.png'});

	await browser.close();
};


// Function to loop through the screen shot calls
async function loopOverParameters(urls, widths, heights, p, f) {
	const screenshots = [];

	urls.forEach((url) => {
		widths.forEach((width) => {
			heights.forEach((height) => {
				screenshots.push(saveScreenShotFromURL(url,width,height,p, f));
			});
		});
	});
	await Promise.all(screenshots);
}

// Call the looping function
loopOverParameters(urls, windowWidths, windowHeights, p, f);