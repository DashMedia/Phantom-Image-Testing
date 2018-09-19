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
			<p id="thesame" style="display:none;"><strong>These images are the same!</strong></p>
			<div id="diff-results" style="display:none;"></div>
			<p id="urlLink" style="text-align: center; display: block; width: 100%;"><a target="_blank" href=""></a></p>
		</header>
		<section>
			<div id="imageA" class="oneThird">
				<p>Image A</p>
			</div>
			<div id="imageB" class="oneThird">
				<p>Image B</p>
			</div>
			<div id="imageDiff" class="oneThird">
				<p>Image diff</p>
			</div>
		</section>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<script src="/jquery.hotkeys.js"></script>
		<script src="/main.js"></script>
		<script>
			$(document).ready(function() {
				
				function listPopulationCall(folder,tier,path1,path2) {
					console.log("Fetching tier " + tier);
					console.log("Folder is: " + folder);
					console.log("Path 1 is: " + path1);
					console.log("Path 2 is: " + path2);
					
					if(tier == 4) {
						$('#tier4Box').html('<div id="diffGenStatus">Generating diffs</div>');
						console.log("Tier 4");
						var $count = 1;
						
						$.ajax({
							method: "POST",
							url: "folderList.php",
							data: { folder: folder, tier: tier, path: path1, compare: path2 }
						})
						.done(function(output) {
							console.log(output);
						})

						var $diffChecks = setInterval(function() {
							console.log("I'm looping");
							console.log("L Fetching tier " + tier);
							console.log("L Folder is: " + folder);
							console.log("L Path 1 is: " + path1);
							console.log("L Path 2 is: " + path2);
							
								$.get("diffGenerationStatus.php",function(data) {
								console.log("dGS: " + data);
								$('#diffGenStatus').html("Generating diffs (" + data + ")");
								if(data.indexOf('100%') != -1) {
									console.log("100% detected - loop ceased");
									clearInterval($diffChecks);
									console.log("About to get");

							console.log("AG Fetching tier " + tier);
							console.log("AG Folder is: " + folder);
							console.log("AG Path 1 is: " + path1);
							console.log("AG Path 2 is: " + path2);
									
									$.get("folderList.php", {folder: folder, tier: tier, path: path1, compare: path2 },function(data) {
										console.log("Tier 4 get data: " + data);
										$('#tier4Box').html(data);
									});

								}
							});
						},2500);
					} else {
						$.ajax({
							method: "POST",
							url: "folderList.php",
							data: { folder: folder, tier: tier, path: path1, compare: path2 }
						})
						.done(function(output) {
							clearInterval($diffChecks);
							$diffsReturned = true;
							console.log(output);
							$('#tier'+tier+'Box').html(output);
						})
					}
				}
				
/*				function onComplete(data){
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
*/				
				listPopulationCall("images",1,'');
					
				$(document).on('change','.tier1',function() {
					listPopulationCall(this.value,2,$(this).data('path'));
				});
				$(document).on('change','.tier2',function() {
					listPopulationCall($('.tier1').val(),3,$('.tier1').data('path'));
				});
				$(document).on('change','.tier3',function() {
					listPopulationCall($('.tier2').data('path'),4,$('.tier2').val(),$('.tier3').val());
				});
				
				$(document).on('keydown',null,',',function() {
					console.log("prev");
					$('.tier4 option:selected').removeAttr('selected').prev().attr('selected','selected');
					$('.tier4').change();
				});
				$(document).on('keydown',null,'.',function() {
					console.log("next");
					$('.tier4 option:selected').removeAttr('selected').next().attr('selected','selected');
					$('.tier4').change();
				});
				
				$(document).on('change','.tier4',function() {
					if(this.value != "Select comparison site version" && this.value != "The end!") {
						console.log(this.value);
						console.log($(this).find(":selected").data('pc-off'));
					
//						$('#thesame').hide();
//						$('#diff-results').hide();
//						$('#image-diff').html('<p>Differential will appear here</p>');

						var $diffValue = $(this).find(":selected").data('pc-off');
					
						if($diffValue == "0%") {
							$('#thesame').show();
							$('#diff-results').hide();
						} else {
							$('#diff-results').html("Images are " + $diffValue + " different").show();
							$('#thesame').hide();
						}
					
						var closeBracketPosition = this.value.indexOf("]")+2;
						var imageName = this.value.substr(closeBracketPosition);
					
						var image1 = $('.tier4').data('path1') + "/" + imageName;
						var image2 = $('.tier4').data('path2') + "/" + imageName;
						var imagediff = $('.tier4').data('path-diffs') + "/" + imageName;
					
						$('#imageA').html('<img src="'+image1+'"/>');
						$('#imageB').html('<img src="'+image2+'"/>');
						$('#imageDiff').html('<img src="'+imagediff+'"/>');
					
						$('#urlLink a').html('https://' + imageName.replace('.png','').split('_').join('/'));
						$('#urlLink a').attr('href','https://' + imageName.replace('.png','').split('_').join('/'));
					
						$('.tier4').blur();
					}
				})				
				
			});
		</script>
	</body>
</html>
