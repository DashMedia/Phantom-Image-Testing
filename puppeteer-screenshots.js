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

//	 console.log('About to load browser');
	
//	const browser = await puppeteer.launch({headless:false});
	const browser = await puppeteer.launch();

//	 console.log('Browser loaded');
//	 console.log('About to open new page');

	const page = await browser.newPage();
	await page.setViewport({width: windowWidth, height: windowHeight});  

//	 console.log('New page open and viewport set');
//	 console.log('About to open the URL');
	
	// Load the URL in question
	await page.goto(pageURL,{timeout:180000,waitUntil:'networkidle0'});
	
//	 console.log('URL Loaded');
//	 console.log('Waiting for body selector');
	
	await page.waitForSelector('body');

//	 console.log('Body selector found');
//	 console.log('Waiting for 2.5 seconds');

	await page.waitFor(2500);

//	 console.log('Done waiting for 2.5 seconds');	
	console.log('Current page: ' + pageURL);
//	 console.log('About to write code to file');
	
	const fullCode = await page.evaluate("document.documentElement.outerHTML");

//	 console.log('Code written to file');
//	 console.log('About to set screenshot file name');

	// Actually take the screenshot
	const filename = `${p}/${f}.png`;
//	await page.screenshot({path: filename, fullPage: true});

//	 console.log('Screenshot file name set');
//	 console.log('Awaiting page body handle');

	const bodyHandle = await page.$('body');
	const { width, height } = await bodyHandle.boundingBox();

//	 console.log('Got page body handle and bounding box size');
//	 console.log('Removing animation data attributes');

	try {
		await page.evaluate("jQuery('[data-aos]').attr('data-aos','')");
	} catch (error) {
		
	}

//	 console.log('Removed animation data attributes');
//	 console.log('About to take screen shot');
	
	await page.screenshot({
		path: filename,
//		fullPage: true,
		clip: {
			x: 0,
			y: 0,
			width,
			height
		}
	});

//	 console.log('Screenshot taken');
//	 console.log('About to write code to file');
	
	const codeFilename = `${p}/code/${f}.txt`;
	fs.writeFileSync(codeFilename,fullCode);

//	 console.log('Code written to file');

// Optional page reload for troubleshooting
//  console.log("Wait before reload");
//  await page.waitFor(30000);
//  console.log("Reloading");
//  await page.reload();
//  await page.screenshot({path: 'abouthomesnt.com.au_designs_reload.png'});

//	 console.log('About to close the browser');

	await browser.close();

//	 console.log('Browser closed');
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