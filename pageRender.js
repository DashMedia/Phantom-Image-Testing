var page = require('webpage').create(),
	system = require('system'),
	webAddress, imageName, imageQuality, t;
	
if(system.args.length === 1) {
	console.log('Usage: pageRender.js <URL (with http prefix)> <Image name (no extension)> <Image quality (0-100)>');
	phantom.exit();
}

webAddress = system.args[1];
imageName = system.args[2];
imageQuality = system.args[3];

t = Date.now();
page.viewportSize = { width: 1920, height: 1080 };
page.open(webAddress, function start(status) {
	page.evaluate(function() {
		document.body.bgColor = 'white';
	});
	page.render(imageName + '.png', {format: 'png', quality: imageQuality});
	t = Date.now() - t;
	console.log('All done rendering [' + webAddress + '] to [' + imageName + '.png] - it took ' + t + ' msec');
	phantom.exit();
});
