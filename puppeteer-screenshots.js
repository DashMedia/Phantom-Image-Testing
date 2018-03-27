/** Pre requisites
 * Designed for use on MacOS only
 
 1) install node.js (https://nodejs.org/en/download/)
 
 2) install puppeteer
 	`npm i puppeteer`

 * USAGE:
 * `node pupeteer-screenshots.js -w 1024 -h 768 --url=http://google.com -p=projectPath -f=fileName`
 */
	
const puppeteer = require('puppeteer');
const fs = require('fs');

// Initialise input variables
const argv = require('minimist')(process.argv.slice(2));
const windowWidth = argv.w ? argv.w : 1024;
const windowHeight = argv.h ? argv.h : 1024;

let urls = argv.urls ? argv.urls.split(',') : [argv.url];
windowWidths = argv.widths ? argv.widths.split(',') : [windowWidth];
windowHeights = argv.heights ? argv.heights.split(',') : [windowHeight];

p = argv.p ? argv.p : "images/";
f = argv.f ? argv.f : "screenshot";

const stockFilename = `${p}/${f}.st.png`;
fs.writeFileSync(stockFilename," ");

// Function to perform the screen shot
async function saveScreenShotFromURL(pageURL, windowWidth, windowHeight, p, f) {
	// Open a browser and page and set the screen size

//	const browser = await puppeteer.launch({headless:false});
	const browser = await puppeteer.launch();

	const page = await browser.newPage();
	await page.setViewport({width: windowWidth, height: windowHeight});  
	
	// Load the URL in question
	await page.goto(pageURL,{timeout:180000,waitUntil:'networkidle0'});
	await page.waitForSelector('body');
	await page.waitFor(2500);
	
	console.log('Current page: ' + pageURL);
	
	const fullCode = await page.evaluate("document.documentElement.outerHTML");

	// Actually take the screenshot
	const filename = `${p}/${f}.png`;
	await page.screenshot({path: filename, fullPage: true});
	
	const codeFilename = `${p}/code/${f}.txt`;
	fs.writeFileSync(codeFilename,fullCode);

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