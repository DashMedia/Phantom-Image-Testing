<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
		<title>Resemble.js : Image analysis</title>
		<meta name="description" content="">
		<meta name="viewport" content="width=device-width">
		<link rel="stylesheet" href="/resemble.css?v1">		
	</head>
	<body>
		<header>
			<div class="selectBoxes">
				<div id="tier1Box"></div>
				<div id="tier2Box"></div>
				<div id="tier3Box"></div>
				<div id="tier4Box"></div>
			</div>
			<div class="btn-group buttons">
				<button class="btn" id="raw">Ignore nothing</button>
				<button class="btn active" id="less">Ignore less</button>
				<button class="btn" id="colors">Ignore colors</button>
				<button class="btn" id="antialising">Ignore antialiasing</button>
			</div>
			<div class="btn-group buttons">
				<button class="btn active" id="original-size">Use original size</button>
				<button class="btn" id="same-size">Scale to same size</button>
			</div>
			<div class="btn-group buttons">
				<button class="btn active" id="pink">Pink</button>
				<button class="btn" id="yellow">Yellow</button>
			</div>
			<div class="btn-group buttons">
				<button class="btn active" id="flat">Flat</button>
				<button class="btn" id="movement">Movement</button>
				<button class="btn" id="flatDifferenceIntensity">Flat with diff intensity</button>
				<button class="btn" id="movementDifferenceIntensity">Movement with diff intensity</button>
			</div>
			<div class="btn-group buttons last">
				<button class="btn active" id="opaque">Opaque</button>
				<button class="btn" id="transparent">Transparent</button>
			</div>
			<div id="diff-results" style="display:none;">
				<p>Use the buttons above to change the comparison algorithm. Perhaps you don't care about color? Annoying antialiasing causing too much noise? Resemble.js offers multiple comparison options.</p>
				<p><strong>The second image is <span id="mismatch"></span>% different compared to the first.<span id="differentdimensions" style="display:none;">And they have different dimensions.</span></strong></p>
			</div>
			<p id="thesame" style="display:none;"><strong>These images are the same!</strong></p>
			<p id="urlLink" style="text-align: center; display: block; width: 100%;"><a target="_blank" href=""></a></p>
		</header>
		<section>
			<div id="dropzone1" class="oneThird">
				<p>Drop first image</p>
			</div>
			<div id="dropzone2" class="oneThird">
				<p>Drop second image</p>
			</div>
			<div class="oneThirdResult">
				<div id="image-diff" class="small-drop-zone">
					<p>Differential will appear here</p>
				</div>
			</div>
		</section>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
		<script src="/resemble.js"></script>
		<script src="/main.js"></script>
		<script>
			$(document).ready(function() {
				
				function listPopulationCall(folder,tier,path1,path2) {
					console.log("Fetching tier " + tier);
					console.log("Folder is: " + folder);
					console.log("Path 1 is: " + path1);
					console.log("Path 2 is: " + path2);
					$.ajax({
						method: "POST",
						url: "folderList.php",
						data: { folder: folder, tier: tier, path: path1, compare: path2 }
					})
						.done(function(output) {
							console.log(output);
							$('#tier'+tier+'Box').html(output);
						})
					
				};
				
				function onComplete(data){
					var time = Date.now();
					var diffImage = new Image();
					diffImage.src = data.getImageDataUrl();

					$('#image-diff').html(diffImage);

					$(diffImage).click(function(){
						window.open(diffImage.src, '_blank');
					});

					if(data.misMatchPercentage == 0){
						$('#thesame').show();
						$('#diff-results').hide();
					} else {
						$('#mismatch').text(data.misMatchPercentage);
						if(!data.isSameDimensions){
							$('#differentdimensions').show();
						} else {
							$('#differentdimensions').hide();
						}
						$('#diff-results').show();
						$('#thesame').hide();
					}
				}
				
				listPopulationCall("images",1,'');
					
				$(document).on('change','.tier1',function() {
					listPopulationCall(this.value,2,$(this).data('path'));
				});
				$(document).on('change','.tier2',function() {
					listPopulationCall($('.tier1').val(),3,$('.tier1').data('path'));
				});
				$(document).on('change','.tier3',function() {
					listPopulationCall($('.tier2').val(),4,$('.tier3').data('path'),$('.tier3').val());
				});
				
				$(document).on('change','.tier4',function() {
					console.log(this.value);
					$('#thesame').hide();
					$('#diff-results').hide();
					$('#image-diff').html('<p>Differential will appear here</p>');
					
					var closeBracketPosition = this.value.indexOf("]")+2;
					var imageName = this.value.substr(closeBracketPosition);
					
					var image1 = $('.tier4').data('path1') + "/" + imageName;
					var image2 = $('.tier4').data('path2') + "/" + imageName;
					
					$('#dropzone1').html('<img src="'+image1+'"/>');
					$('#dropzone2').html('<img src="'+image2+'"/>');
					
					resembleControl = resemble(image1).compareTo(image2).onComplete(onComplete);					
					console.log("Image 1: " + image1);
					console.log("Image 2: " + image2);
					
					$('#urlLink a').html('https://' + imageName.replace('.png','').split('_').join('/'));
					$('#urlLink a').attr('href','https://' + imageName.replace('.png','').split('_').join('/'));
				})
				
				
			});
		</script>
	</body>
</html>
